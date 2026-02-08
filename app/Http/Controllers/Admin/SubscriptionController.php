<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\Payment;
use App\Models\Member;
use App\Services\RazorPayService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Mail\SubscriptionReceiptMail;
use Illuminate\Support\Facades\Mail;
use App\Services\AdminActionLogger;


class SubscriptionController extends Controller
{
    protected RazorPayService $rz;

    public function __construct(RazorPayService $rz)
    {
        $this->rz = $rz;
    }

    /**
     * Create/ensure subscription for member
     */
    public function createSubscription(Request $request, Member $member)

    {


        $fy = Subscription::financialYearForDate();
        $sub = Subscription::firstOrCreate(
            ['member_id' => $member->id, 'financial_year' => $fy],
            ['monthly_fee' => $member->membership_fee]
        );

        return response()->json(['success' => true, 'subscription' => $sub]);
    }

    /**
     * Admin: compute due for a member
     */
    public function due(Request $request, Member $member)
    {
        $fy = Subscription::financialYearForDate();
        $sub = Subscription::firstOrCreate(['member_id' => $member->id, 'financial_year' => $fy], ['monthly_fee' => $member->membership_fee]);
        $unpaid = $sub->unpaidMonthsUpTo(Carbon::now());
        $dueAmount = count($unpaid) * $sub->monthly_fee;
        return response()->json(['success' => true, 'unpaid_months' => $unpaid, 'due_amount' => $dueAmount, 'subscription' => $sub]);
    }

    /**
     * Admin: pay on behalf. Admin chooses months to mark paid and supplies a payment source.
     *
     * For admin, you might:
     * - Create Razorpay order and let admin pay via UI.
     * - Or record an offline payment (cash/cheque) and directly mark months paid by storing a payment record.
     *
     * This method creates a Razorpay order similar to member pay, intended for admin UI to complete checkout.
     */
    public function payOnBehalf(Request $request, Member $member)
    {
        $months = (array)$request->input('months', []);
        if (empty($months)) {
            return response()->json(['success' => false, 'message' => 'No months selected'], 422);
        }

        $fy = Subscription::financialYearForDate();
        $sub = Subscription::firstOrCreate(['member_id' => $member->id, 'financial_year' => $fy], ['monthly_fee' => $member->membership_fee]);

        foreach ($months as $m) {
            if (! in_array($m, Subscription::fyMonths())) {
                return response()->json(['success' => false, 'message' => "Invalid month {$m}"], 422);
            }
            if (! empty($sub->{"{$m}_payment_id"})) {
                return response()->json(['success' => false, 'message' => "Month {$m} already paid"], 422);
            }
        }

        $amount = count($months) * $sub->monthly_fee;
        $amountPaise = $amount * 100;
        $receipt = 'admin_sub_' . $sub->id . '_' . time();

        $order = $this->rz->createOrder($amountPaise, 'INR', $receipt);

        $payment = Payment::create([
            'member_id' => $member->id,
            'subscription_id' => $sub->id,
            'razorpay_order_id' => $order['id'],
            'amount' => $amount,
            'status' => 'created',
            'raw' => array_merge($order, ['months' => $months])
        ]);

        AdminActionLogger::log(
            action: 'Subscription.create',
            description: "Subscription created'{$payment->razorpay_order_id}'",
            modelType: Subscription::class,
            modelId: $payment->subscription_id
        );

        return response()->json(['success' => true, 'order' => $order, 'payment_id' => $payment->id, 'amount' => $amount, 'months' => $months]);
    }




    public function verifyPayment(Request $request, Member $member)
    {


        $data = $request->validate([
            'razorpay_order_id'   => 'required|string',
            'razorpay_payment_id' => 'required|string',
            'razorpay_signature'  => 'required|string',
        ]);

        try {
            $this->rz->verifyPaymentSignature([
                'razorpay_order_id'   => $data['razorpay_order_id'],
                'razorpay_payment_id' => $data['razorpay_payment_id'],
                'razorpay_signature'  => $data['razorpay_signature'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid payment signature',
            ], 400);
        }

        /** ✅ IMPORTANT: capture payment OUTSIDE transaction */
        $payment = DB::transaction(function () use ($data) {

            $payment = Payment::where('razorpay_order_id', $data['razorpay_order_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $months = $payment->raw['months'] ?? [];

            $sub = Subscription::lockForUpdate()
                ->findOrFail($payment->subscription_id);

            $payment->update([
                'razorpay_payment_id' => $data['razorpay_payment_id'],
                'razorpay_signature'  => $data['razorpay_signature'],
                'status'              => 'paid',
            ]);

            foreach ($months as $m) {
                $sub->update([
                    "{$m}_payment_id" => $payment->id,
                    "{$m}_paid_at"    => now(),
                ]);
            }

            return $payment; // 🔑 RETURN IT
        });

        /** 📧 EMAIL AFTER COMMIT */
        try {
            $fy = Subscription::financialYearForDate();
            $payment->load('member', 'subscription');

            if ($payment->member && $payment->member->email) {
                Mail::to($payment->member->email)
                    ->send(new SubscriptionReceiptMail($payment, $fy, $member));
            }
        } catch (\Throwable $e) {
            Log::error('Receipt email failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);
        }

        AdminActionLogger::log(
            action: 'Subscription.pay',
            description: "Subscription paid'{$payment->razorpay_payment_id}'",
            modelType: Subscription::class,
            modelId: $payment->subscription_id
        );

        return response()->json([
            'success' => true,
            'message' => 'Payment verified and receipt emailed',
        ]);
    }



    public function verifySignature($orderId, $paymentId, $signature)
    {
        $generated = hash_hmac(
            'sha256',
            $orderId . '|' . $paymentId,
            config('services.razorpay.secret')
        );

        if ($generated !== $signature) {
            throw new \Exception('Invalid Razorpay signature');
        }
    }




    public function index(Request $request)
    {
        $fy = Subscription::financialYearForDate();
        $now = Carbon::now();

        $members = Member::query()
            ->select('id', 'first_name', 'last_name', 'membership_fee')
            ->with(['subscriptions' => function ($q) use ($fy) {
                $q->where('financial_year', $fy);
            }])
            ->where('status_flag', true)
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = $request->search;
                $q->where('id', $s)
                    ->orWhere('first_name', 'like', "%{$s}%")
                    ->orWhere('last_name', 'like', "%{$s}%");
            })
            ->orderBy('id')
            ->get();

        $rows = $members->map(function ($member) use ($fy, $now) {

            $sub = $member->subscriptions->first();
            $monthlyFee = $sub?->monthly_fee ?? $member->membership_fee ?? 0;

            // ✅ CASE 1: Subscription exists
            if ($sub) {
                $unpaidMonths = $sub->unpaidMonthsUpTo($now);

                $paidMonths = collect(Subscription::fyMonths())
                    ->diff($unpaidMonths)
                    ->filter(function ($m) use ($sub) {
                        return !empty($sub->{"{$m}_payment_id"});
                    });
            }
            // ✅ CASE 2: No subscription row yet
            else {
                // Create a temporary FY subscription context
                $tempSub = new Subscription([
                    'financial_year' => $fy,
                    'monthly_fee' => $monthlyFee,
                ]);

                $unpaidMonths = $tempSub->unpaidMonthsUpTo($now);
                $paidMonths = collect(); // none paid
            }

            return [
                'member_id'      => $member->id,
                'name'           => trim($member->first_name . ' ' . $member->last_name),
                'membership_fee' => $monthlyFee,
                'paid_amount'    => count($paidMonths) * $monthlyFee,
                'due_amount'     => count($unpaidMonths) * $monthlyFee,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $rows,
        ]);
    }

    public function show(Member $member)
    {
        $fy = Subscription::financialYearForDate();

        $sub = Subscription::where('member_id', $member->id)
            ->where('financial_year', $fy)
            ->first();

        return response()->json([
            'success' => true,
            'member' => [
                'id' => $member->id,
                'name' => trim($member->first_name . ' ' . $member->last_name),
                'membership_fee' => $member->membership_fee,
            ],
            'subscription' => $sub,
            'months' => Subscription::fyMonths(),
        ]);
    }

    public function receipt(Payment $payment)
    {
        $payment->load(['member', 'subscription']);

        $months = $payment->raw['months'] ?? [];

        $data = [
            'receipt_no' => $payment->id,
            'date'       => $payment->created_at->format('d-m-Y'),
            'member'     => $payment->member,
            'subscription' => $payment->subscription,
            'months'     => $months,
            'amount'     => $payment->amount,
            'razorpay_order_id'   => $payment->razorpay_order_id,
            'razorpay_payment_id' => $payment->razorpay_payment_id,
            'financial_year' => $payment->subscription->financial_year,
        ];

        $pdf = Pdf::loadView('pdf.subscription-receipt', $data);

        return $pdf->download(
            'subscription-receipt-' . $payment->id . '.pdf'
        );
    }

    public function payOffline(Request $request, Member $member)
    {


        $data = $request->validate([
            'months'        => 'required|array|min:1',
            'payment_mode'  => 'required|in:cash,upi',
            'reference_no'  => 'required|string|max:255',
        ]);

        $fy = Subscription::financialYearForDate();

        $sub = Subscription::firstOrCreate(
            ['member_id' => $member->id, 'financial_year' => $fy],
            ['monthly_fee' => $member->membership_fee]
        );

        foreach ($data['months'] as $m) {
            if (!in_array($m, Subscription::fyMonths())) {
                return response()->json(['message' => "Invalid month {$m}"], 422);
            }
            if (!empty($sub->{"{$m}_payment_id"})) {
                return response()->json(['message' => "Month {$m} already paid"], 422);
            }
        }

        $amount = count($data['months']) * $sub->monthly_fee;

        DB::transaction(function () use ($member, $sub, $data, $amount) {

            $payment = Payment::create([
                'member_id'       => $member->id,
                'subscription_id' => $sub->id,
                'amount'          => $amount,
                'status'          => 'paid',
                'payment_mode'    => $data['payment_mode'],
                'reference_no'    => $data['reference_no'],
                'raw'             => ['months' => $data['months']],
            ]);



            foreach ($data['months'] as $m) {
                $sub->update([
                    "{$m}_payment_id" => $payment->id,
                    "{$m}_paid_at"    => now(),
                ]);
            }
        });

        // 📧 email receipt (same mail class)
        $payment = Payment::latest()->first();
        Mail::to($member->email)->send(new SubscriptionReceiptMail($payment, $fy, $member));

        AdminActionLogger::log(
            action: 'Subscription.offline_pay',
            description: "Subscription paid'{$payment->reference_no}'",
            modelType: Subscription::class,
            modelId: $payment->subscription_id
        );

        return response()->json([
            'success' => true,
            'message' => 'Offline payment recorded successfully',
        ]);
    }
}
