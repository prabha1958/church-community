<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Alliance;
use Carbon\Carbon;


class AllianceController extends Controller
{

    public function index(Request $request)
    {
        $sixMonthsAgo = Carbon::now()->subMonths(6);

        $alliances = Alliance::query()
            // ✅ payment made
            ->whereNotNull('payment_date')

            // ✅ payment within last 6 months
            ->where('payment_date', '>=', $sixMonthsAgo)

            ->with([
                'member:id,first_name,last_name,email,mobile_number'
            ])
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = $request->search;

                $q->where('id', $s)
                    ->orWhereHas('member', function ($mq) use ($s) {
                        $mq->where('first_name', 'like', "%{$s}%")
                            ->orWhere('last_name', 'like', "%{$s}%")
                            ->orWhere('email', 'like', "%{$s}%")
                            ->orWhere('mobile_number', 'like', "%{$s}%");
                    });
            })
            ->orderByDesc('payment_date')
            ->get()
            ->map(function (Alliance $a) {
                return [
                    'alliance' => $a->makeHidden('member'),
                    'age'           => $a->date_of_birth
                        ? \Carbon\Carbon::parse($a->date_of_birth)->age
                        : null,
                    'member' => $a->member ? [
                        'member_id'     => $a->member->id,
                        'member_name'   => trim(
                            $a->member->first_name . ' ' . $a->member->last_name
                        ),
                        'email'         => $a->member->email,
                        'mobile_number' => $a->member->mobile_number,
                    ] : null,
                ];
            });

        return response()->json([
            'success' => true,
            'data'    => $alliances,
        ]);
    }

    public function show(Alliance $alliance)
    {

        $alliance->load([
            'member:id,family_name,first_name,last_name,email,mobile_number'
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'alliance' => $alliance->makeHidden('member'),
                'age'           => $alliance->date_of_birth
                    ? \Carbon\Carbon::parse($alliance->date_of_birth)->age
                    : null,
                'member' => $alliance->member ? [
                    'family_name' => $alliance->member->family_name,
                    'first_name'  => $alliance->member->first_name,
                    'last_name'   => $alliance->member->last_name,
                    'email'       => $alliance->member->email,
                    'mobile_number' => $alliance->member->mobile_number
                ] : null,
            ],
        ]);
    }
}
