<?php

namespace Tests\Feature\Platform;

use App\Mail\SetupAdminWelcomeMail;
use App\Models\Church;
use App\Models\PlatformUser;
use App\Services\Platform\SetupAdminService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;


class PlatformOnboardingSetupAdminTest extends TestCase
{
    use RefreshDatabase;

    protected SetupAdminService $setupAdminService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setupAdminService = app(SetupAdminService::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    protected function createChurch(
        string $status = 'pending',
        ?string $churchCode = null
    ): Church {
        return Church::on('platform')->create([
            'church_name' => 'Test Church',
            'church_code' => $churchCode ?? 'TEST' . strtoupper(Str::random(6)),
            'status' => $status,
        ]);
    }

    protected function createPlatformUser(
        Church $church,
        array $attributes = []
    ): PlatformUser {
        return PlatformUser::on('platform')->create(array_merge([
            'church_id' => $church->id,
            'name' => 'Existing Admin',
            'email' => 'admin-' . Str::lower(Str::random(8)) . '@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'setup_admin',
            'status' => 'active',
            'must_change_password' => false,
        ], $attributes));
    }

    /*
    |--------------------------------------------------------------------------
    | Successful creation
    |--------------------------------------------------------------------------
    */

    public function test_setup_admin_can_be_created_during_onboarding(): void
    {
        Mail::fake();

        $church = $this->createChurch('pending');

        $result = $this->setupAdminService->createForOnboarding(
            $church,
            'John Doe',
            'john@example.com'
        );

        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('temporary_password', $result);
        $this->assertArrayHasKey('username', $result);

        $user = $result['user'];

        $this->assertInstanceOf(PlatformUser::class, $user);

        $this->assertSame($church->id, (int) $user->church_id);
        $this->assertSame('John Doe', $user->name);
        $this->assertSame('john@example.com', $user->email);
        $this->assertSame('setup_admin', $user->role);
        $this->assertSame('active', $user->status);
        $this->assertTrue((bool) $user->must_change_password);

        $this->assertNotEmpty($result['temporary_password']);

        $this->assertTrue(
            Hash::check(
                $result['temporary_password'],
                $user->password
            )
        );

        Mail::assertNothingSent();
    }

    /*
    |--------------------------------------------------------------------------
    | Welcome email
    |--------------------------------------------------------------------------
    */

    public function test_setup_admin_welcome_email_can_be_queued_after_creation(): void
    {
        Mail::fake();

        $church = $this->createChurch('pending');

        $result = $this->setupAdminService->createForOnboarding(
            $church,
            'John Doe',
            'john@example.com'
        );

        Mail::to($result['user']->email)->queue(
            new SetupAdminWelcomeMail(
                $result['user'],
                $church,
                $result['temporary_password']
            )
        );

        Mail::assertQueued(
            SetupAdminWelcomeMail::class,
            function (SetupAdminWelcomeMail $mail) use ($result, $church) {
                return
                    $mail->user->id === $result['user']->id &&
                    $mail->church->id === $church->id &&
                    $mail->temporaryPassword === $result['temporary_password'];
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Church status
    |--------------------------------------------------------------------------
    */

    public function test_setup_admin_cannot_be_created_for_active_church(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Church is not in an onboarding state.');

        $church = $this->createChurch('active');

        $this->setupAdminService->createForOnboarding(
            $church,
            'John Doe',
            'john@example.com'
        );
    }

    public function test_setup_admin_can_be_created_for_provisioning_church(): void
    {
        $church = $this->createChurch('provisioning');

        $result = $this->setupAdminService->createForOnboarding(
            $church,
            'John Doe',
            'john@example.com'
        );

        $this->assertSame(
            $church->id,
            (int) $result['user']->church_id
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Duplicate email
    |--------------------------------------------------------------------------
    */

    public function test_existing_platform_email_is_rejected(): void
    {
        $church = $this->createChurch('pending');

        PlatformUser::on('platform')->create([
            'church_id' => $church->id,
            'name' => 'Existing User',
            'email' => 'john@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'setup_admin',
            'status' => 'active',
            'must_change_password' => false,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'A platform account already exists for this email.'
        );

        $this->setupAdminService->createForOnboarding(
            $church,
            'New Admin',
            'john@example.com'
        );
    }

    public function test_email_is_normalized_before_creation(): void
    {
        $church = $this->createChurch('pending');

        $result = $this->setupAdminService->createForOnboarding(
            $church,
            'John Doe',
            '  JOHN@EXAMPLE.COM  '
        );

        $this->assertSame(
            'john@example.com',
            $result['user']->email
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Self replacement protection
    |--------------------------------------------------------------------------
    */

    public function test_current_setup_admin_cannot_replace_themselves(): void
    {
        $church = $this->createChurch('pending');

        $currentAdmin = $this->createPlatformUser(
            $church,
            [
                'email' => 'current@example.com',
            ]
        );

        $this->expectException(RuntimeException::class);

        $this->expectExceptionMessage(
            'You are already the active setup administrator for this church. You cannot replace your own account.'
        );

        $this->setupAdminService->createForOnboarding(
            $church,
            'Replacement Admin',
            'replacement@example.com',
            $currentAdmin
        );

        $this->assertDatabaseMissing(
            'platform_users',
            [
                'email' => 'replacement@example.com',
            ],
            'platform'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Legitimate replacement
    |--------------------------------------------------------------------------
    |
    | This tests the service-level replacement logic.
    | The current onboarding controller should NOT expose this operation
    | to the current setup admin because self-replacement is prohibited.
    |
    */

    public function test_authorized_replacement_deactivates_old_admin(): void
    {
        $church = $this->createChurch('pending');

        $oldAdmin = $this->createPlatformUser(
            $church,
            [
                'email' => 'old-admin@example.com',
            ]
        );

        $result = $this->setupAdminService->createForOnboarding(
            $church,
            'New Admin',
            'new-admin@example.com',
            null
        );

        $oldAdmin->refresh();

        $this->assertSame('inactive', $oldAdmin->status);

        $this->assertSame(
            $oldAdmin->id,
            $result['replaced_user_id']
        );

        $newAdmin = $result['user'];

        $this->assertSame(
            $church->id,
            (int) $newAdmin->church_id
        );

        $this->assertSame(
            'new-admin@example.com',
            $newAdmin->email
        );

        $this->assertSame(
            'active',
            $newAdmin->status
        );

        $this->assertTrue(
            (bool) $newAdmin->must_change_password
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Token revocation
    |--------------------------------------------------------------------------
    */

    public function test_replaced_admin_tokens_are_revoked(): void
    {
        $church = $this->createChurch('pending');

        $oldAdmin = $this->createPlatformUser(
            $church,
            [
                'email' => 'old-admin@example.com',
            ]
        );

        /*
         * Create a fake platform token record for the old admin.
         *
         * This assumes your PlatformPersonalAccessToken table uses:
         * tokenable_type
         * tokenable_id
         * token
         *
         * Adjust only this section if your platform token schema differs.
         */
        DB::connection('platform')
            ->table('platform_personal_access_tokens')
            ->insert([
                'tokenable_type' => PlatformUser::class,
                'tokenable_id' => $oldAdmin->id,
                'name' => 'test-token',
                'token' => hash('sha256', 'test-token'),
                'abilities' => json_encode(['*']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        $this->assertDatabaseHas(
            'platform_personal_access_tokens',
            [
                'tokenable_type' => PlatformUser::class,
                'tokenable_id' => $oldAdmin->id,
            ],
            'platform'
        );

        $this->setupAdminService->createForOnboarding(
            $church,
            'New Admin',
            'new-admin@example.com'
        );

        $this->assertDatabaseMissing(
            'platform_personal_access_tokens',
            [
                'tokenable_type' => PlatformUser::class,
                'tokenable_id' => $oldAdmin->id,
            ],
            'platform'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Failure should not create a replacement
    |--------------------------------------------------------------------------
    */

    public function test_duplicate_email_does_not_create_new_user(): void
    {
        $church = $this->createChurch('pending');

        PlatformUser::on('platform')->create([
            'church_id' => $church->id,
            'name' => 'Existing User',
            'email' => 'existing@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'setup_admin',
            'status' => 'active',
            'must_change_password' => false,
        ]);

        $before = PlatformUser::on('platform')->count();

        try {
            $this->setupAdminService->createForOnboarding(
                $church,
                'Another Admin',
                'existing@example.com'
            );
        } catch (RuntimeException $e) {
            // Expected.
        }

        $after = PlatformUser::on('platform')->count();

        $this->assertSame($before, $after);
    }

    /*
    |--------------------------------------------------------------------------
    | Email must not be sent when creation fails
    |--------------------------------------------------------------------------
    */

    public function test_welcome_email_is_not_queued_when_creation_fails(): void
    {
        Mail::fake();

        $church = $this->createChurch('pending');

        PlatformUser::on('platform')->create([
            'church_id' => $church->id,
            'name' => 'Existing User',
            'email' => 'existing@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'setup_admin',
            'status' => 'active',
            'must_change_password' => false,
        ]);

        try {
            $this->setupAdminService->createForOnboarding(
                $church,
                'Another Admin',
                'existing@example.com'
            );
        } catch (RuntimeException $e) {
            // Expected.
        }

        Mail::assertNotQueued(
            SetupAdminWelcomeMail::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Temporary password
    |--------------------------------------------------------------------------
    */

    public function test_temporary_password_is_different_from_email(): void
    {
        $church = $this->createChurch('pending');

        $result = $this->setupAdminService->createForOnboarding(
            $church,
            'John Doe',
            'john@example.com'
        );

        $this->assertNotSame(
            'john@example.com',
            $result['temporary_password']
        );

        $this->assertGreaterThanOrEqual(
            12,
            strlen($result['temporary_password'])
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Multiple churches
    |--------------------------------------------------------------------------
    */

    public function test_setup_admin_belongs_only_to_selected_church(): void
    {
        $churchOne = $this->createChurch(
            'pending',
            'CHURCH001'
        );

        $churchTwo = $this->createChurch(
            'pending',
            'CHURCH002'
        );

        $result = $this->setupAdminService->createForOnboarding(
            $churchOne,
            'Church One Admin',
            'admin1@example.com'
        );

        $this->assertSame(
            $churchOne->id,
            (int) $result['user']->church_id
        );

        $this->assertNotSame(
            $churchTwo->id,
            (int) $result['user']->church_id
        );
    }
}
