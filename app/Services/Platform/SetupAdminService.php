<?php

namespace App\Services\Platform;

use App\Models\Church;
use App\Models\PlatformUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;
use App\Models\PlatformPersonalAccessToken;

class SetupAdminService
{
    /**
     * Existing workflow for an active church.
     */
    public function create(
        Church $church,
        string $name,
        string $email
    ): array {
        if ($church->status !== 'active') {
            throw new RuntimeException('Church is not active.');
        }

        return $this->createSetupAdmin($church, $name, $email);
    }

    /**
     * Onboarding workflow.
     *
     * Allows the first setup admin to be created while the
     * church is still pending or provisioning.
     */
    public function createForOnboarding(
        Church $church,
        string $name,
        string $email
    ): array {
        if (!in_array($church->status, ['pending', 'provisioning'], true)) {
            throw new RuntimeException(
                "Church {$church->church_code} is not in an onboarding state."
            );
        }

        return $this->createSetupAdmin($church, $name, $email);
    }

    /**
     * Common setup-admin creation/replacement logic.
     */
    private function createSetupAdmin(
        Church $church,
        string $name,
        string $email,

    ): array {
        return DB::connection('platform')->transaction(
            function () use ($church, $name, $email) {

                /*
             * Lock the church row so that two setup-admin creation
             * requests cannot modify the same church simultaneously.
             */
                $church = Church::on('platform')
                    ->lockForUpdate()
                    ->findOrFail($church->id);

                $email = strtolower(trim($email));

                /*
             * Do not allow the same email to belong to another
             * platform account.
             */
                $existingUser = PlatformUser::on('platform')
                    ->where('email', $email)
                    ->first();

                if ($existingUser) {
                    throw new RuntimeException(
                        'A platform account already exists for this email.'
                    );
                }

                /*
             * Find the currently active setup administrator
             * for this church.
             */
                $currentAdmin = PlatformUser::on('platform')
                    ->where('church_id', $church->id)
                    ->where('role', 'setup_admin')
                    ->where('status', 'active')
                    ->lockForUpdate()
                    ->first();

                /*
             * SAFEGUARD:
             *
             * Do not allow the currently logged-in setup administrator
             * to replace/deactivate their own account.
             */


                /*
             * Replace an existing active setup administrator.
             *
             * This is allowed only when the requesting user is not
             * the administrator being replaced.
             */
                $replacedUserId = null;

                if ($currentAdmin) {
                    $replacedUserId = $currentAdmin->id;

                    $currentAdmin->update([
                        'status' => 'inactive',
                    ]);

                    /*
                 * Revoke all platform tokens belonging to the
                 * replaced administrator.
                 */
                    PlatformPersonalAccessToken::on('platform')
                        ->where(
                            'tokenable_type',
                            PlatformUser::class
                        )
                        ->where(
                            'tokenable_id',
                            $currentAdmin->id
                        )
                        ->delete();
                }

                /*
             * Generate temporary password.
             */
                $temporaryPassword = Str::random(12);

                /*
             * Create the new setup administrator.
             */
                $user = PlatformUser::on('platform')->create([
                    'church_id' => $church->id,
                    'name' => trim($name),
                    'email' => $email,
                    'password' => Hash::make($temporaryPassword),
                    'role' => 'setup_admin',
                    'status' => 'active',
                    'must_change_password' => true,
                ]);

                return [
                    'user' => $user,
                    'username' => $email,
                    'temporary_password' => $temporaryPassword,
                    'replaced_user_id' => $replacedUserId,
                ];
            }
        );
    }

    /**
     * Generate a new temporary password for an existing onboarding
     * setup administrator.
     *
     * This is only allowed while the church is pending or provisioning.
     */
    public function resetTemporaryPasswordForOnboarding(
        Church $church,
        PlatformUser $user
    ): array {
        return DB::connection('platform')->transaction(
            function () use ($church, $user) {

                $church = Church::on('platform')
                    ->lockForUpdate()
                    ->findOrFail($church->id);

                if (!in_array($church->status, ['pending', 'provisioning'], true)) {
                    throw new RuntimeException(
                        "Church {$church->church_code} is not in an onboarding state."
                    );
                }

                $user = PlatformUser::on('platform')
                    ->lockForUpdate()
                    ->findOrFail($user->id);

                if (
                    (int) $user->church_id !== (int) $church->id ||
                    $user->role !== 'setup_admin' ||
                    $user->status !== 'active'
                ) {
                    throw new RuntimeException(
                        'The specified user is not the active setup administrator for this church.'
                    );
                }

                $temporaryPassword = Str::random(12);

                $user->forceFill([
                    'password' => Hash::make($temporaryPassword),
                    'must_change_password' => true,
                ])->save();

                /*
             * Revoke any existing platform tokens.
             *
             * This ensures that previously issued login sessions
             * cannot remain active after the temporary password
             * is regenerated.
             */
                \App\Models\PlatformPersonalAccessToken::on('platform')
                    ->where('tokenable_type', PlatformUser::class)
                    ->where('tokenable_id', $user->id)
                    ->delete();

                return [
                    'user' => $user,
                    'username' => $user->email,
                    'temporary_password' => $temporaryPassword,
                ];
            }
        );
    }
}
