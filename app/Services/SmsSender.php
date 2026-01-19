<?php

namespace App\Services;

use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;

class SmsSender
{
    protected ?Client $client = null;
    protected ?string $from = null;

    public function __construct()
    {
        $sid = config('services.twilio.sid');
        $token = config('services.twilio.token');
        $this->from = config('services.twilio.whatsapp_from'); // e.g. '+1415...' or 'whatsapp:+...'

        if (!empty($sid) && !empty($token)) {
            $this->client = new Client($sid, $token);
        } else {
            Log::warning('Twilio not configured - SmsSender inactive.');
        }
    }

    /**
     * Send an SMS (not WhatsApp). Normalizes Indian 10-digit numbers to +91.
     * Returns boolean success.
     */
    public function send(string $to, string $message): bool
    {
        // sanitize digits and plus
        $raw = preg_replace('/[^0-9+]/', '', $to);

        // If 10-digit, assume Indian local and prefix +91
        if (preg_match('/^[0-9]{10}$/', $raw)) {
            $raw = '+91' . $raw;
        }

        if (preg_match('/^[0-9]+$/', $raw)) {
            $raw = '+' . $raw;
        }

        if (!$this->client || empty($this->from)) {
            Log::warning("SmsSender inactive. Would have sent to {$raw}: {$message}");
            // For local testing we treat as success (or return false if you prefer)
            return false;
        }

        try {
            $msg = $this->client->messages->create('whatsapp:' . $raw, [
                'from' => config('services.twilio.whatsapp_from'),
                'body' => $message,
            ]);
            Log::info('SMS sent', ['sid' => $msg->sid, 'to' => $raw]);
            return true;
        } catch (\Throwable $e) {
            Log::error('SMS send failed: ' . $e->getMessage(), ['to' => $raw]);
            return false;
        }
    }
}
