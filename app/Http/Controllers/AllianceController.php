<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Alliance;
use Carbon\Carbon;

class AllianceController extends Controller
{
    /**
     * Return the member's currently in-force alliance.
     */
    private function getMyInForceAlliance(Request $request): ?Alliance
    {
        $member = $request->user();

        if (!$member) {
            return null;
        }

        $sixMonthsAgo = Carbon::now()->subMonths(6);

        return Alliance::where('member_id', $member->id)
            ->whereNotNull('payment_date')
            ->where('payment_date', '>=', $sixMonthsAgo)
            ->latest('payment_date')
            ->first();
    }

    /**
     * Check whether the logged-in member is allowed
     * to view the specified alliance.
     */
    private function canViewAlliance(
        Alliance $alliance,
        Alliance $myAlliance
    ): bool {
        // Must be published.
        if (!$alliance->is_published) {
            return false;
        }

        // Must have a payment.
        if (!$alliance->payment_date) {
            return false;
        }

        // Must still be within the six-month validity period.
        if (Carbon::parse($alliance->payment_date)
            ->lt(Carbon::now()->subMonths(6))
        ) {
            return false;
        }

        // Member cannot view their own alliance.
        if ((int) $alliance->member_id === (int) $myAlliance->member_id) {
            return false;
        }

        // Must be the opposite alliance type.
        if ($myAlliance->alliance_type === 'bride') {
            return $alliance->alliance_type === 'bridegroom';
        }

        if ($myAlliance->alliance_type === 'bridegroom') {
            return $alliance->alliance_type === 'bride';
        }

        return false;
    }


    public function index(Request $request)
    {
        /*
         * The logged-in member must have their own
         * currently in-force alliance.
         */
        $myAlliance = $this->getMyInForceAlliance($request);

        if (!$myAlliance) {
            return response()->json([
                'success' => false,
                'message' => 'You must have an active alliance to view other alliances.',
                'data' => [],
            ], 403);
        }

        /*
         * Determine which type this member is allowed to see.
         */
        $oppositeType = $myAlliance->alliance_type === 'bride'
            ? 'bridegroom'
            : 'bride';

        $sixMonthsAgo = Carbon::now()->subMonths(6);

        $alliances = Alliance::query()

            // Only published alliances.
            ->where('is_published', true)

            // Only the opposite type.
            ->where('alliance_type', $oppositeType)

            // Payment must exist.
            ->whereNotNull('payment_date')

            // Payment must be within six months.
            ->where('payment_date', '>=', $sixMonthsAgo)

            // Do not show the member's own alliance.
            ->where('member_id', '!=', $myAlliance->member_id)

            ->with([
                'member:id,first_name,last_name,email,mobile_number'
            ])

            ->when($request->filled('search'), function ($q) use ($request) {

                $s = $request->search;

                $q->where(function ($query) use ($s) {

                    $query->where('id', $s)

                        ->orWhereHas('member', function ($mq) use ($s) {

                            $mq->where('first_name', 'like', "%{$s}%")
                                ->orWhere('last_name', 'like', "%{$s}%")
                                ->orWhere('email', 'like', "%{$s}%")
                                ->orWhere('mobile_number', 'like', "%{$s}%");
                        });
                });
            })

            ->orderByDesc('payment_date')
            ->get()

            ->map(function (Alliance $a) {

                return [
                    'alliance' => $a->makeHidden('member'),

                    'age' => $a->date_of_birth
                        ? Carbon::parse($a->date_of_birth)->age
                        : null,

                    'member' => $a->member ? [
                        'member_id' => $a->member->id,

                        'member_name' => trim(
                            $a->member->first_name . ' ' .
                                $a->member->last_name
                        ),

                        'email' => $a->member->email,

                        'mobile_number' => $a->member->mobile_number,
                    ] : null,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $alliances,
        ]);
    }


    public function show(Request $request, $allianceId)
    {
        /*
         * Member must have their own active alliance.
         */
        $myAlliance = $this->getMyInForceAlliance($request);

        if (!$myAlliance) {
            return response()->json([
                'success' => false,
                'message' => 'You must have an active alliance to view other alliances.',
            ], 403);
        }

        /*
         * Explicitly retrieve the alliance from the tenant DB.
         * This is preferable to implicit model binding in our
         * dynamic tenant architecture.
         */
        $alliance = Alliance::on('tenant')->find($allianceId);

        if (!$alliance) {
            return response()->json([
                'success' => false,
                'message' => 'Alliance not found.',
            ], 404);
        }

        /*
         * Apply exactly the same visibility rules as index().
         */
        if (!$this->canViewAlliance($alliance, $myAlliance)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to view this alliance.',
            ], 403);
        }

        $alliance->load([
            'member:id,family_name,first_name,last_name,email,mobile_number'
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'alliance' => $alliance->makeHidden('member'),

                'age' => $alliance->date_of_birth
                    ? Carbon::parse($alliance->date_of_birth)->age
                    : null,

                'member' => $alliance->member ? [
                    'family_name' => $alliance->member->family_name,
                    'first_name' => $alliance->member->first_name,
                    'last_name' => $alliance->member->last_name,
                    'email' => $alliance->member->email,
                    'mobile_number' => $alliance->member->mobile_number,
                ] : null,
            ],
        ]);
    }
}
