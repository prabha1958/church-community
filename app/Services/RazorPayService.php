<?php

namespace App\Services;

use Razorpay\Api\Api;
use Exception;
use Illuminate\Support\Facades\Log;

class RazorpayService
{
    protected Api $api;

    public function __construct()
    {
        $this->api = new Api(config('services.razorpay.key'), config('services.razorpay.secret'));
    }

    /**
     * Create an order.
     * $amount: in paise (smallest unit) — REQUIRED by Razorpay.
     * $currency: 'INR'
     * $receipt: optional string to track
     */
    public function createOrder(int $amount, string $currency = 'INR', string $receipt = null): array
    {
        $options = [
            'amount' => $amount,
            'currency' => $currency,
            'payment_capture' => 1, // auto-capture; or 0 if you capture later
        ];
        if ($receipt) $options['receipt'] = $receipt;

        $order = $this->api->order->create($options);
        return $order->toArray();
    }

    /**
     * Verify signature (after checkout success)
     */
    public function verifyPaymentSignature(array $attributes): bool
    {
        try {
            $this->api->utility->verifyPaymentSignature($attributes);
            return true;
        } catch (Exception $e) {
            Log::error('Razorpay verify failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Validate webhook signature (payload raw body + signature header)
     */
    public function verifyWebhookSignature(string $payload, string $signature, string $secret): bool
    {
        $expected = hash_hmac('sha256', $payload, $secret);
        return hash_equals($expected, $signature);
    }
}
