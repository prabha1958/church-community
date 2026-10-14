
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

class PlatformPersonalAccessToken extends SanctumPersonalAccessToken
{
    protected $connection = 'platform';

    protected $table = 'personal_access_tokens';

    /**
     * Create a Sanctum-compatible token for a platform user.
     *
     * The token is stored in the platform database, not the tenant database.
     */
    public static function createForUser(
        Model $user,
        string $name = 'platform-web',
        array $abilities = ['*']
    ): array {
        $plainToken = Str::random(40);

        $token = new static();

        $token->setConnection('platform');

        $token->forceFill([
            'tokenable_type' => $user->getMorphClass(),
            'tokenable_id' => $user->getKey(),
            'name' => $name,
            'token' => hash('sha256', $plainToken),
            'abilities' => $abilities,
        ]);

        $token->save();

        return [
            'accessToken' => $token,
            'plainTextToken' => $token->getKey() . '|' . $plainToken,
        ];
    }

    /**
     * Find a token using either its plain value or Sanctum's id|token format.
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
}
