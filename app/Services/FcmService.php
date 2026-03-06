<?php

namespace App\Services;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmService
{
    protected static function getAccessToken(): ?string
    {
        try {
            $credentialsPath = storage_path('app/firebase-service-account.json');

            $scopes = ['https://www.googleapis.com/auth/firebase.messaging'];

            $credentials = new ServiceAccountCredentials($scopes, $credentialsPath);

            $token = $credentials->fetchAuthToken();

            return $token['access_token'] ?? null;
        } catch (\Throwable $e) {
            Log::error('FCM access token error', [
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    public static function send(array $tokens, string $title, string $body, array $data = []): void
    {
        if (empty($tokens)) {
            return;
        }

        $accessToken = self::getAccessToken();

        if (!$accessToken) {
            Log::error('FCM access token missing');
            return;
        }

        $projectId = config('services.fcm.project_id');

        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

        foreach ($tokens as $token) {

            $payload = [
                "message" => [
                    "token" => $token,
                    "notification" => [
                        "title" => $title,
                        "body" => $body
                    ],
                    "data" => $data,
                    "android" => [
                        "priority" => "high"
                    ]
                ]
            ];

            try {
                Http::withToken($accessToken)
                    ->timeout(10)
                    ->post($url, $payload);
            } catch (\Throwable $e) {

                Log::error('FCM push exception', [
                    'token' => $token,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
