<?php

namespace App\Services;

use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;
use Exception;

class WhatsAppSender
{
    protected Client $client;
    protected string $from;

    public function __construct()
    {
        $sid = config('services.twilio.sid');
        $token = config('services.twilio.token');
        $from = config('services.twilio.whatsapp_from');

        if (empty($sid) || empty($token) || empty($from)) {
            throw new Exception('Twilio credentials or WhatsApp sender not configured. Check your .env file.');
        }

        $this->client = new Client($sid, $token);
        $this->from = str_starts_with($from, 'whatsapp:') ? $from : 'whatsapp:' . $from;
    }

    public function send(string $to, string $message): bool
    {
        try {
            $to = str_starts_with($to, 'whatsapp:') ? $to : 'whatsapp:' . $to;

            $this->client->messages->create($to, [
                'from' => $this->from,
                'body' => $message,
            ]);

            Log::info("WhatsApp message sent to {$to}");
            return true;
        } catch (Exception $e) {
            Log::error('WhatsApp send failed: ' . $e->getMessage(), ['to' => $to]);
            return false;
        }
    }
}
