<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\OutstandingSubscription;
use App\Models\OutstandingSubscriptionPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OutstandingSubscriptionController extends Controller
{
    /**
     * Search outstanding balances.
     *
     * Examples:
     * GET /api/admin/outstanding-subscriptions?member_id=123
     * GET /api/admin/outstanding-subscriptions?member_id=123&financial_year=2025-2026
     */
    public function index(Request $request)
    {
        $data = $request->validate([
            'member_id' => ['nullable', 'integer', 'min:1'],
            'financial_year' => [
                'nullable',
                'regex:/^\d{4}-\d{4}$/',
            ],
        ]);

        $query = OutstandingSubscription::query()
            ->with([
                'member:id,family_name,first_name,last_name',
            ])
            ->where('balance_amount', '>', 0);

        if (!empty($data['member_id'])) {
            $query->where('member_id', $data['member_id']);
        }

        if (!empty($data['financial_year'])) {
            $query->where('financial_year', $data['financial_year']);
        }

        $records = $query
            ->orderByDesc('financial_year')
            ->orderBy('member_id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $records->map(function ($record) {
                return [
                    'id' => $record->id,
                    'member_id' => $record->member_id,
                    'member_name' => trim(
                        ($record->member->family_name ?? '') . ' ' .
                            ($record->member->first_name ?? '') . ' ' .
                            ($record->member->last_name ?? '')
                    ),
                    'financial_year' => $record->financial_year,
                    'months_outstanding' => $record->months_outstanding,
                    'original_amount' => $record->original_amount,
                    'amount_paid' => $record->amount_paid,
                    'balance_amount' => $record->balance_amount,
                ];
            }),
        ]);
    }

    /**
     * View one outstanding balance and its collection history.
     */
    public function show(OutstandingSubscription $outstandingSubscription)
    {
        $outstandingSubscription->load([
            'member:id,family_name,first_name,last_name',
            'payments' => function ($query) {
                $query->with('admin:id,first_name,last_name')
                    ->orderByDesc('paid_at');
            },
        ]);

        return response()->json([
            'success' => true,
            'data' => $outstandingSubscription,
        ]);
    }

    /**
     * Record an offline collection against a previous-year balance.
     */
    public function collectPayment(
        Request $request,
        OutstandingSubscription $outstandingSubscription
    ) {
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'payment_mode' => ['required', 'in:cash,upi'],
            'reference_no' => ['nullable', 'string', 'max:255'],
        ]);

        $adminId = Auth::id();

        $result = DB::connection('tenant')->transaction(
            function () use (
                $outstandingSubscription,
                $data,
                $adminId
            ) {
                $outstanding = OutstandingSubscription::query()
                    ->whereKey($outstandingSubscription->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($outstanding->balance_amount <= 0) {
                    abort(422, 'This outstanding balance has already been paid.');
                }

                if ($data['amount'] > $outstanding->balance_amount) {
                    abort(
                        422,
                        'Collection amount cannot exceed the remaining balance.'
                    );
                }

                $payment = OutstandingSubscriptionPayment::create([
                    'outstanding_subscription_id' => $outstanding->id,
                    'member_id' => $outstanding->member_id,
                    'amount' => $data['amount'],
                    'payment_mode' => $data['payment_mode'],
                    'reference_no' => $data['reference_no'] ?? null,
                    'admin_id' => $adminId,
                    'paid_at' => now(),
                ]);

                $outstanding->amount_paid += $data['amount'];
                $outstanding->balance_amount -= $data['amount'];
                $outstanding->save();

                return [
                    'payment' => $payment,
                    'outstanding' => $outstanding->fresh(),
                ];
            }
        );

        return response()->json([
            'success' => true,
            'message' => 'Outstanding subscription payment recorded successfully.',
            'payment' => $result['payment'],
            'outstanding' => $result['outstanding'],
        ]);
    }
}
