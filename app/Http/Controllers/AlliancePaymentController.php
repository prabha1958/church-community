<?php

namespace App\Http\Controllers;

use App\Models\Alliance;
use App\Models\AlliancePayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AlliancePaymentController extends Controller
{
    // minimum rupees
    protected int $minimumRupees = 3000;

    protected function razorpayApi(): Api
    {
        $cfg = config('services.razorpay', []);

        $key    = $cfg['key']         ?? $cfg['key_id']     ?? env('RAZORPAY_KEY_ID');
        $secret = $cfg['secret']      ?? $cfg['key_secret'] ?? env('RAZORPAY_KEY_SECRET');

        if (empty($key) || empty($secret)) {
            Log::error('Razorpay credentials missing', ['config' => $cfg]);
            throw new \RuntimeException('Payment gateway not configured.');
        }

        return new \Razorpay\Api\Api(trim($key), trim($secret));
    }

    /**
     * Create a Razorpay order and local AlliancePayment row.
     * Request body: { amount: <number in rupees> }
     */
    public function createOrder(Request $request, Alliance $alliance)
    {
        $user = $request->user();

        // Authorization: member who owns alliance or admin
        if ($user->id !== $alliance->member_id && ($user->role ?? '') !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'amount' => ['required', 'numeric', 'min:' . $this->minimumRupees],
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $amountRupees = (float) $request->input('amount');
        $amountPaise = (int) round($amountRupees * 100); // integer paise

        $cfg = config('services.razorpay', []);
        $key = $cfg['key'] ?? $cfg['key_id'] ?? env('RAZORPAY_KEY_ID') ?? env('RAZORPAY_KEY');
        $secret = $cfg['secret'] ?? $cfg['key_secret'] ?? env('RAZORPAY_KEY_SECRET') ?? env('RAZORPAY_SECRET');

        try {
            $api = new \Razorpay\Api\Api(trim($key), trim($secret));

            $orderData = [
                'receipt' => 'alli_' . $alliance->id . '_' . time(),
                'amount'  => $amountPaise,
                'currency' => 'INR',
                'payment_capture' => 1,
            ];

            $razorpayOrder = $api->order->create($orderData);

            // Save local payment record (amount stored as integer paise in your schema)
            $payment = AlliancePayment::create([
                'alliance_id' => $alliance->id,
                'member_id'   => $alliance->member_id,
                'payment_gateway' => 'razorpay',
                'payment_gateway_order_id' => $razorpayOrder['id'],
                'payment_gateway_payment_id' => null,
                'payment_gateway_signature' => null,
                'amount' => $amountPaise / 100, // store as integer paise
                'currency' => 'INR',
                'status' => 'created',
                'raw' => json_encode($razorpayOrder),
            ]);

            return response()->json([
                'success' => true,
                'order' => [
                    'id' => $razorpayOrder['id'],
                    'amount' => $razorpayOrder['amount'], // paise
                    'currency' => $razorpayOrder['currency'],
                ],
                'payment_id' => $payment->id,
                // return the public key string used for initialising the SDK (trimmed)
                'razorpay_key' => $key ? trim($key) : null,
            ]);
        } catch (\Throwable $e) {
            Log::error('Razorpay order create failed: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['success' => false, 'message' => 'Failed to create payment order.'], 500);
        }
    }

    /**
     * Verify Razorpay payment after checkout.
     * Expected payload:
     * {
     *   razorpay_payment_id,
     *   razorpay_order_id,
     *   razorpay_signature,
     *   payment_id (local alliance_payments.id)
     * }
     */
    public function verify(Request $request, Alliance $alliance)
    {
        $user = $request->user();

        if ($user->id !== $alliance->member_id && ($user->role ?? '') !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = Validator::make($request->all(), [
            'razorpay_payment_id' => 'required|string',
            'razorpay_order_id'   => 'required|string',
            'razorpay_signature'  => 'required|string',
            'payment_id'          => 'required|integer|exists:alliance_payments,id',
        ])->validate();

        $api = $this->razorpayApi();

        try {
            $api->utility->verifyPaymentSignature([
                'razorpay_order_id'   => $data['razorpay_order_id'],
                'razorpay_payment_id' => $data['razorpay_payment_id'],
                'razorpay_signature'  => $data['razorpay_signature'],
            ]);
        } catch (SignatureVerificationError $e) {
            return response()->json(['success' => false, 'message' => 'Invalid payment signature'], 400);
        }

        DB::transaction(function () use ($data, $api, $alliance) {

            /** @var AlliancePayment $payment */
            $payment = AlliancePayment::lockForUpdate()->findOrFail($data['payment_id']);

            // Update payment record
            $payment->update([
                'payment_gateway_payment_id' => $data['razorpay_payment_id'],
                'payment_gateway_signature'  => $data['razorpay_signature'],
                'status'                     => 'paid',
                'paid_at'                    => now(),
            ]);

            // Fetch Razorpay payment details (optional but good)
            try {
                $paymentDetails = $api->payment->fetch($data['razorpay_payment_id']);
                $payment->raw = json_encode($paymentDetails);
                $payment->save();
            } catch (\Throwable $e) {
                Log::warning('Failed to fetch Razorpay payment details', [
                    'error' => $e->getMessage()
                ]);
            }

            // 🔑 UPDATE ALLIANCE TABLE
            $alliance->applyPayment($payment);
        });

        return response()->json([
            'success' => true,
            'message' => 'Payment verified and alliance updated successfully',
        ]);
    }
}
