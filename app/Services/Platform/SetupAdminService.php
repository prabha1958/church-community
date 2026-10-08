<?php

namespace App\Services\Platform;

use App\Models\Church;
use App\Models\PlatformPersonalAccessToken;
use App\Models\PlatformUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class SetupAdminService
{
    /**
     * Existing workflow for an active church.
     *
     * Generates a temporary password.
     */
    public function create(
        Church $church,
        string $name,
        string $email
    ): array {
        if ($church->status !== 'active') {
            throw new RuntimeException(
                'Church is not active.'
            );
        }

        return $this->createSetupAdmin(
            $church,
            $name,
            $email
        );
    }

    /**
     * Existing onboarding workflow.
     *
     * Generates a temporary password.
     */
    public function createForOnboarding(
        Church $church,
        string $name,
        string $email
    ): array {
        if ($church->status !== 'active') {
            throw new RuntimeException(
                "Church {$church->church_code} is not ready for setup administrator creation."
            );
        }

        return $this->createSetupAdmin(
            $church,
            $name,
            $email
        );
    }

    /**
     * Secure onboarding workflow.
     *
     * The applicant has already received a secure one-time
     * setup token and chooses their own password.
     *
     * No temporary password is generated.
     */
    public function createWithPassword(
        Church $church,
        string $name,
        string $email,
        string $password
    ): PlatformUser {
        if ($church->status !== 'active') {
            throw new RuntimeException(
                "Church {$church->church_code} is not ready for setup administrator creation."
            );
        }

        if (trim($password) === '') {
            throw new RuntimeException(
                'Password cannot be empty.'
            );
        }

        return DB::connection('platform')->transaction(
            function () use (
                $church,
                $name,
                $email,
                $password
            ): PlatformUser {

                /*
                 * Lock the church row so that two setup-admin
                 * creation requests cannot modify the same church
                 * simultaneously.
                 */
                $church = Church::on('platform')
                    ->lockForUpdate()
                    ->findOrFail($church->id);

                /*
                 * Re-check the church status after acquiring
                 * the database lock.
                 */
                if ($church->status !== 'active') {
                    throw new RuntimeException(
                        "Church {$church->church_code} is not ready for setup administrator creation."
                    );
                }

                $email = strtolower(trim($email));
                $name = trim($name);

                /*
                 * Do not allow the same email to belong to
                 * another platform account.
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
                 * Replace an existing active setup administrator.
                 *
                 * This preserves the behaviour of your existing
                 * createSetupAdmin() method.
                 */
                $replacedUserId = null;

                if ($currentAdmin) {
                    $replacedUserId = $currentAdmin->id;

                    $currentAdmin->update([
                        'status' => 'inactive',
                    ]);

                    /*
                     * Revoke all platform tokens belonging to
                     * the replaced administrator.
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
                 * Create the setup administrator using the
                 * password selected by the applicant.
                 *
                 * IMPORTANT:
                 *
                 * must_change_password = false because the
                 * applicant has already selected their permanent
                 * password.
                 */
                $user = PlatformUser::on('platform')->create([
                    'church_id' => $church->id,
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make($password),
                    'role' => 'setup_admin',
                    'status' => 'active',
                    'must_change_password' => false,
                ]);

                return $user;
            }
        );
    }

    /**
     * Generate a temporary password for the existing
     * temporary-password workflow.
     */
    private function createSetupAdmin(
        Church $church,
        string $name,
        string $email
    ): array {
        return DB::connection('platform')->transaction(
            function () use (
                $church,
                $name,
                $email
            ) {

                /*
                 * Lock the church row so that two setup-admin
                 * creation requests cannot modify the same church
                 * simultaneously.
                 */
                $church = Church::on('platform')
                    ->lockForUpdate()
                    ->findOrFail($church->id);

                $email = strtolower(trim($email));

                /*
                 * Do not allow the same email to belong to
                 * another platform account.
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
                 * Existing behaviour:
                 *
                 * If another active setup administrator exists,
                 * deactivate that account and revoke its tokens.
                 */
                $replacedUserId = null;

                if ($currentAdmin) {
                    $replacedUserId = $currentAdmin->id;

                    $currentAdmin->update([
                        'status' => 'inactive',
                    ]);

                    /*
                     * Revoke all platform tokens belonging to
                     * the replaced administrator.
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
     * Generate a new temporary password for an existing
     * onboarding setup administrator.
     *
     * This is only allowed while the church is active.
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

                if ($church->status !== 'active') {
                    throw new RuntimeException(
                        "Church {$church->church_code} is not ready for setup administrator operations."
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
                 * This ensures previously issued login sessions
                 * cannot remain active after the temporary password
                 * is regenerated.
                 */
                PlatformPersonalAccessToken::on('platform')
                    ->where(
                        'tokenable_type',
                        PlatformUser::class
                    )
                    ->where(
                        'tokenable_id',
                        $user->id
                    )
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
