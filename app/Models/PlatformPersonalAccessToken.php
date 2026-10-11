<?php

namespace App\Models;

use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

class PlatformPersonalAccessToken extends SanctumPersonalAccessToken
{
    protected $connection = 'platform';
    protected $table = 'personal_access_tokens';

    public static function findToken($token)
    {
        $model = new static();
        $model->setConnection('platform');

        if (strpos($token, '|') === false) {
            return $model->newQuery()
                ->where('token', hash('sha256', $token))
                ->first();
        }

        [$id, $plainToken] = explode('|', $token, 2);

        $accessToken = $model->newQuery()->find($id);

        if (!$accessToken) {
            return null;
        }

        return hash_equals(
            $accessToken->token,
            hash('sha256', $plainToken)
        ) ? $accessToken : null;
    }
}
