<?php

namespace App\Http\Controllers\Platform;


use App\Http\Controllers\Controller;
use App\Http\Requests\PlatformLoginRequest;
use App\Models\PlatformPersonalAccessToken;
use App\Models\PlatformUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\PlatformChangePasswordRequest;

class PlatformAuthController extends Controller
{
    public function login(PlatformLoginRequest $request): JsonResponse
    {
        $email = strtolower(trim($request->input('email')));

        $user = PlatformUser::on('platform')
            ->where('email', $email)
            ->first();

        if (!$user || !Hash::check($request->input('password'), $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email or password.',
            ], 401);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Your account is not active.',
            ], 403);
        }

        if (!$user->church_id) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is not associated with a church.',
            ], 403);
        }

        $church = $user->church()->first();

        if (!$church) {
            return response()->json([
                'success' => false,
                'message' => 'Your church could not be found.',
            ], 403);
        }

        $churchStatus = strtolower((string) $church->status);

        $isOnboardingAdmin =
            $user->role === 'setup_admin'
            && $user->status === 'active'
            && in_array($churchStatus, ['pending', 'provisioning'], true);

        if ($churchStatus !== 'active' && !$isOnboardingAdmin) {
            return response()->json([
                'success' => false,
                'message' => 'Your church is not active.',
            ], 403);
        }

        /*
         * Remove any existing platform tokens for this user.
         *
         * This keeps the setup-admin account limited to the
         * current login session.
         */
        PlatformPersonalAccessToken::on('platform')
            ->where('tokenable_type', PlatformUser::class)
            ->where('tokenable_id', $user->id)
            ->delete();

        $tokenResult = PlatformPersonalAccessToken::createForUser(
            $user,
            'platform-web',
            ['*']
        );

        $user->forceFill([
            'last_login_at' => now(),
        ])->save();

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'data' => [
                'token' => $tokenResult['plainTextToken'],
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'church_id' => $user->church_id,
                    'role' => $user->role,
                    'must_change_password' => $user->must_change_password,
                ],
                'church' => [
                    'id' => $church->id,
                    'church_code' => $church->church_code,
                    'church_name' => $church->church_name,
                    'short_name' => $church->short_name,
                    'logo' => $church->logo,
                ],
            ],
        ]);
    }

    public function changePassword(
        PlatformChangePasswordRequest $request
    ): JsonResponse {
        $user = $request->user();

        if (!$user instanceof PlatformUser) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Your account is not active.',
            ], 403);
        }

        if (!Hash::check(
            $request->input('current_password'),
            $user->password
        )) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect.',
            ], 422);
        }

        $user->forceFill([
            'password' => Hash::make(
                $request->input('new_password')
            ),
            'must_change_password' => false,
        ])->save();

        /*
     * Revoke all existing platform tokens for this user.
     */
        PlatformPersonalAccessToken::on('platform')
            ->where('tokenable_type', PlatformUser::class)
            ->where('tokenable_id', $user->id)
            ->delete();

        /*
     * Issue a fresh token for the new password session.
     */
        $tokenResult = PlatformPersonalAccessToken::createForUser(
            $user,
            'platform-web',
            ['*']
        );

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully.',
            'data' => [
                'token' => $tokenResult['plainTextToken'],

                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'church_id' => $user->church_id,
                    'role' => $user->role,
                    'must_change_password' => $user->must_change_password,
                ],

                'church' => [
                    'id' => $user->church->id,
                    'church_code' => $user->church->church_code,
                    'church_name' => $user->church->church_name,
                    'short_name' => $user->church->short_name,
                    'logo' => $user->church->logo,
                ],
            ],
        ]);
    }
}
