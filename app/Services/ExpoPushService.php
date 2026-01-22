<?php

namespace App\Services;



use Illuminate\Support\Facades\Http;

class ExpoPushService
{
    public static function send(array $tokens, string $title, string $body, array $data = [])
    {
        $messages = collect($tokens)->map(fn($token) => [
            'to' => $token,
            'sound' => 'default',
            'title' => $title,
            'body' => $body,
            'data' => $data,
        ])->values();

        Http::post('https://exp.host/--/api/v2/push/send', $messages->all());
    }
}
