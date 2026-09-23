<?php

namespace App\Models;

use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

class PlatformPersonalAccessToken extends SanctumPersonalAccessToken
{


    /**
     * Platform tokens are stored in the platform database.
     */
    protected $connection = 'platform';
    protected $table = 'personal_access_tokens';
    protected $fillable = [
        'tokenable_type',
        'tokenable_id',
        'name',
        'token',
        'abilities',
        'expires_at',
    ];

    /**
     * Find a platform token from the plain-text token.
     *
     * Sanctum tokens are normally in the form:
     *
     *     {id}|{plain-text-token}
     *
     * The database stores only the SHA-256 hash of the
     * plain-text portion.
     */
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

    public static function createForUser(
        PlatformUser $user,
        string $name = 'platform-api',
        array $abilities = ['*'],
        $expiresAt = null
    ): array {
        $plainTextToken = bin2hex(random_bytes(32));

        $accessToken = new static();

        $accessToken->forceFill([
            'tokenable_type' => PlatformUser::class,
            'tokenable_id'   => $user->getKey(),
            'name'           => $name,
            'token'          => hash('sha256', $plainTextToken),
            'abilities'      => $abilities,
            'expires_at'     => $expiresAt,
        ]);

        $accessToken->save();

        return [
            'accessToken' => $accessToken,
            'plainTextToken' => $accessToken->getKey() . '|' . $plainTextToken,
        ];
    }
}
