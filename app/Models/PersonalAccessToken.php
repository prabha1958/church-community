<?php

namespace App\Models;

use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

class PersonalAccessToken extends SanctumPersonalAccessToken
{
    protected $connection = 'tenant';

    public static function findToken($token)
    {
        $model = new static();

        $model->setConnection('tenant');

        if (strpos($token, '|') === false) {
            return $model->newQuery()
                ->where('token', hash('sha256', $token))
                ->first();
        }

        [$id, $plainToken] = explode('|', $token, 2);

        $accessToken = $model->newQuery()->find($id);

        if (! $accessToken) {
            return null;
        }

        return hash_equals(
            $accessToken->token,
            hash('sha256', $plainToken)
        ) ? $accessToken : null;
    }
}
