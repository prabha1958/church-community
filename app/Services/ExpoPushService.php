<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\DeviceToken;
use Illuminate\Http\Client\Response;

class ExpoPushService
{
    const EXPO_URL = 'https://exp.host/--/api/v2/push/send';
    const CHUNK_SIZE = 100;

    public static function send(array $tokens, string $title, string $body, array $data = []): void
    {
        if (empty($tokens)) {
            return;
        }

        // Split tokens into chunks (Expo limit = 100)
        $chunks = array_chunk($tokens, self::CHUNK_SIZE);

        foreach ($chunks as $chunk) {

            $messages = collect($chunk)->map(function ($token) use ($title, $body, $data) {
                return [
                    'to' => $token,
                    'sound' => 'default',
                    'title' => $title,
                    'body' => $body,
                    'data' => $data,
                ];
            })->values()->all();

            try {

                $response = Http::withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                    ->timeout(15)
                    ->post(self::EXPO_URL, $messages);

                $result = $response->json();

                self::handleResponse($result, $chunk);
            } catch (\Throwable $e) {

                Log::error('Expo push exception', [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    protected static function handleResponse(array $response, array $tokens): void
    {
        if (!isset($response['data'])) {
            return;
        }

        foreach ($response['data'] as $index => $ticket) {

            if (($ticket['status'] ?? '') !== 'error') {
                continue;
            }

            $token = $tokens[$index] ?? null;

            if (!$token) {
                continue;
            }

            $error = $ticket['details']['error'] ?? 'unknown';

            Log::warning('Expo push failed', [
                'token' => $token,
                'error' => $error
            ]);

            // Remove invalid tokens automatically
            if ($error === 'DeviceNotRegistered') {

                DeviceToken::where('token', $token)->delete();

                Log::info('Removed invalid Expo token', [
                    'token' => $token
                ]);
            }
        }
    }
}
