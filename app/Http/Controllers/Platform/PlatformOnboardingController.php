<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChurchRegistrationRequest;
use App\Mail\ChurchAdminSetupLinkMail;
use App\Models\Church;
use App\Models\ChurchRegistrationRequest as ChurchRegistrationRequestModel;
use App\Models\PlatformSetupToken;
use App\Models\PlatformUser;
use App\Services\Platform\ChurchRegistrationService;
use App\Services\Platform\PlatformOnboardingService;
use App\Services\Platform\SetupAdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use RuntimeException;


class PlatformOnboardingController extends Controller
{
    public function __construct(
        private ChurchRegistrationService $churchRegistrationService
    ) {}

    /**
     * Register a church from the platform admin.
     *
     * If registration_request_id is supplied, the original
     * church registration request is marked as approved and
     * a secure one-time setup-admin link is emailed to the
     * applicant.
     */
    public function register(
        ChurchRegistrationRequest $request
    ): JsonResponse {
        $validated = $request->validated();

        /*
         * ---------------------------------------------------------
         * 1. Find the original registration request, if supplied
         * ---------------------------------------------------------
         */
        $registrationRequest = null;

        if (!empty($validated['registration_request_id'])) {
            $registrationRequest = ChurchRegistrationRequestModel::on('platform')
                ->where('id', $validated['registration_request_id'])
                ->where('status', 'pending')
                ->first();

            if (!$registrationRequest) {
                return response()->json([
                    'success' => false,
                    'message' =>
                    'The church registration request is invalid, already processed, or no longer available.',
                ], 422);
            }
        }

        try {

            /*
             * ---------------------------------------------------------
             * 2. Register the church
             *
             * This continues to use the existing service responsible
             * for church registration and tenant provisioning.
             * ---------------------------------------------------------
             */
            $church = $this->churchRegistrationService->register(
                $validated
            );

            /*
             * ---------------------------------------------------------
             * 3. Mark the original registration request as approved
             * ---------------------------------------------------------
             */
            if ($registrationRequest) {
                $registrationRequest->forceFill([
                    'status' => 'approved',
                    'church_id' => $church->id,
                    'reviewed_at' => now(),
                ])->save();
            }

            /*
             * ---------------------------------------------------------
             * 4. Send secure setup-admin link
             *
             * Do not allow email failure to make the church
             * registration itself appear unsuccessful.
             * ---------------------------------------------------------
             */
            if ($registrationRequest) {
                try {
                    $this->sendSetupAdminLink(
                        $registrationRequest,
                        $church
                    );
                } catch (\Throwable $mailException) {

                    Log::error(
                        'Church registered but setup-admin email could not be sent.',
                        [
                            'church_id' =>
                            $church->id,

                            'church_code' =>
                            $church->church_code,

                            'registration_request_id' =>
                            $registrationRequest->id,

                            'admin_email' =>
                            $registrationRequest->admin_email,

                            'error' =>
                            $mailException->getMessage(),
                        ]
                    );
                }
            }

            /*
             * ---------------------------------------------------------
             * 5. Return successful registration response
             * ---------------------------------------------------------
             */
            return response()->json([
                'success' => true,
                'message' =>
                'Church registered successfully.',

                'data' => [
                    'church' => [
                        'id' =>
                        $church->id,

                        'church_name' =>
                        $church->church_name,

                        'short_name' =>
                        $church->short_name,

                        'church_code' =>
                        $church->church_code,

                        'status' =>
                        $church->status,
                    ],

                    'setup_admin_email_sent' =>
                    $registrationRequest !== null,
                ],
            ], 201);
        } catch (\Throwable $e) {

            report($e);

            return response()->json([
                'success' => false,
                'message' =>
                'Unable to complete church registration. '
                    . 'Please try again.',
            ], 500);
        }
    }

    /**
     * Generate a secure, one-time setup-admin token and
     * email the setup link to the original applicant.
     *
     * The raw token is NEVER stored in the database.
     * Only its SHA-256 hash is stored.
     */
    private function sendSetupAdminLink(
        ChurchRegistrationRequestModel $registrationRequest,
        Church $church
    ): void {

        /*
         * ---------------------------------------------------------
         * Generate cryptographically random token
         * ---------------------------------------------------------
         */
        $rawToken = Str::random(64);

        $tokenHash = hash(
            'sha256',
            $rawToken
        );

        /*
         * ---------------------------------------------------------
         * Invalidate any previous unused setup tokens
         * for this church.
         * ---------------------------------------------------------
         */
        PlatformSetupToken::on('platform')
            ->where('church_id', $church->id)
            ->whereNull('used_at')
            ->delete();

        /*
         * ---------------------------------------------------------
         * Store only the token hash
         * ---------------------------------------------------------
         */
        PlatformSetupToken::on('platform')->create([
            'church_id' =>
            $church->id,

            'registration_request_id' =>
            $registrationRequest->id,

            'email' =>
            strtolower(
                trim(
                    $registrationRequest->admin_email
                )
            ),

            'token_hash' =>
            $tokenHash,

            'expires_at' =>
            now()->addHours(24),

            'used_at' =>
            null,
        ]);

        /*
         * ---------------------------------------------------------
         * Build frontend setup URL
         * ---------------------------------------------------------
         */
        $frontendUrl = rtrim(
            (string) config(
                'services.platform.frontend_url'
            ),
            '/'
        );

        if (!$frontendUrl) {
            throw new RuntimeException(
                'Platform frontend URL is not configured.'
            );
        }

        $setupUrl =
            $frontendUrl
            . '/platform/setup-admin?token='
            . urlencode($rawToken);

        /*
         * ---------------------------------------------------------
         * Send email
         *
         * IMPORTANT:
         * No password is included.
         * The applicant creates their own password.
         * ---------------------------------------------------------
         */
        Mail::to(
            strtolower(
                trim(
                    $registrationRequest->admin_email
                )
            )
        )->queue(
            new ChurchAdminSetupLinkMail(
                $registrationRequest,
                $church,
                $setupUrl
            )
        );
    }

    /**
     * Complete platform onboarding for an authenticated
     * platform user.
     */
    public function complete(
        Request $request,
        PlatformOnboardingService $platformOnboardingService
    ): JsonResponse {

        $user = $request->user();

        if (!$user instanceof PlatformUser) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        try {

            $church = $platformOnboardingService->complete(
                $user->church,
                $user
            );

            return response()->json([
                'success' => true,
                'message' =>
                'Church onboarding completed successfully.',

                'data' => [
                    'church' => [
                        'id' =>
                        $church->id,

                        'church_code' =>
                        $church->church_code,

                        'church_name' =>
                        $church->church_name,

                        'short_name' =>
                        $church->short_name,

                        'logo' =>
                        $church->logo,

                        'status' =>
                        $church->status,
                    ],
                ],
            ]);
        } catch (RuntimeException $e) {

            return response()->json([
                'success' => false,
                'message' =>
                $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Create the initial setup administrator using
     * the secure one-time token.
     *
     * The applicant supplies their own password.
     */
    public function createSetupAdmin(
        Request $request,
        SetupAdminService $setupAdminService
    ): JsonResponse {

        /*
         * ---------------------------------------------------------
         * 1. Validate request
         * ---------------------------------------------------------
         */
        $validated = $request->validate([
            'token' => [
                'required',
                'string',
                'size:64',
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ]);

        /*
         * ---------------------------------------------------------
         * 2. Hash the supplied token
         *
         * The database contains only this hash.
         * ---------------------------------------------------------
         */
        $tokenHash = hash(
            'sha256',
            $validated['token']
        );

        /*
         * ---------------------------------------------------------
         * 3. Find unused token
         * ---------------------------------------------------------
         */
        $setupToken = PlatformSetupToken::on('platform')
            ->where('token_hash', $tokenHash)
            ->whereNull('used_at')
            ->first();

        if (!$setupToken) {
            return response()->json([
                'success' => false,
                'message' =>
                'This setup link is invalid or has already been used.',
            ], 422);
        }

        /*
         * ---------------------------------------------------------
         * 4. Check expiration
         * ---------------------------------------------------------
         */
        if ($setupToken->expires_at->isPast()) {

            return response()->json([
                'success' => false,
                'message' =>
                'This setup link has expired. Please contact the platform administrator for a new link.',
            ], 422);
        }

        /*
         * ---------------------------------------------------------
         * 5. Find church
         * ---------------------------------------------------------
         */
        $church = Church::on('platform')
            ->where('id', $setupToken->church_id)
            ->first();

        if (!$church) {

            return response()->json([
                'success' => false,
                'message' =>
                'Church not found.',
            ], 404);
        }

        /*
         * ---------------------------------------------------------
         * 6. Church must be active
         * ---------------------------------------------------------
         */
        if (
            strtolower(
                (string) $church->status
            ) !== 'active'
        ) {

            return response()->json([
                'success' => false,
                'message' =>
                'Administrator setup is not available because the church is not active.',
            ], 422);
        }

        /*
         * ---------------------------------------------------------
         * 7. Check whether setup admin already exists
         * ---------------------------------------------------------
         */
        $existingSetupAdmin = PlatformUser::on('platform')
            ->where('church_id', $church->id)
            ->where('role', 'setup_admin')
            ->where('status', 'active')
            ->first();

        if ($existingSetupAdmin) {

            return response()->json([
                'success' => false,
                'message' =>
                'A setup administrator already exists for this church.',
            ], 422);
        }

        /*
         * ---------------------------------------------------------
         * 8. Get email from the secure token
         *
         * DO NOT accept email from the browser.
         * ---------------------------------------------------------
         */
        $email = strtolower(
            trim(
                $setupToken->email
            )
        );

        /*
         * ---------------------------------------------------------
         * 9. Make sure this email does not already have
         *    a platform account.
         * ---------------------------------------------------------
         */
        $existingUser = PlatformUser::on('platform')
            ->where('email', $email)
            ->first();

        if ($existingUser) {

            return response()->json([
                'success' => false,
                'message' =>
                'A platform account already exists for this email.',
            ], 422);
        }

        try {

            /*
             * -----------------------------------------------------
             * 10. Create setup administrator
             *
             * This requires SetupAdminService to provide:
             *
             * createWithPassword(
             *     $church,
             *     $name,
             *     $email,
             *     $password
             * )
             * -----------------------------------------------------
             */
            $user = $setupAdminService->createWithPassword(
                $church,
                trim($validated['name']),
                $email,
                $validated['password']
            );

            /*
             * -----------------------------------------------------
             * 11. Mark token as used
             * -----------------------------------------------------
             */
            $setupToken->forceFill([
                'used_at' => now(),
            ])->save();

            /*
             * -----------------------------------------------------
             * 12. Return success
             * -----------------------------------------------------
             */
            return response()->json([
                'success' => true,

                'message' =>
                'Setup administrator created successfully. You can now log in.',

                'data' => [
                    'user' => [
                        'id' =>
                        $user->id,

                        'name' =>
                        $user->name,

                        'email' =>
                        $user->email,

                        'church_id' =>
                        $user->church_id,

                        'role' =>
                        $user->role,

                        'status' =>
                        $user->status,

                        'must_change_password' =>
                        $user->must_change_password,
                    ],

                    'church' => [
                        'id' =>
                        $church->id,

                        'church_name' =>
                        $church->church_name,

                        'short_name' =>
                        $church->short_name,

                        'church_code' =>
                        $church->church_code,

                        'status' =>
                        $church->status,
                    ],
                ],
            ], 201);
        } catch (RuntimeException $e) {

            return response()->json([
                'success' => false,
                'message' =>
                $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {

            Log::error(
                'Unable to create onboarding setup administrator.',
                [
                    'church_id' =>
                    $church->id,

                    'church_code' =>
                    $church->church_code,

                    'registration_request_id' =>
                    $setupToken->registration_request_id,

                    'email' =>
                    $email,

                    'error' =>
                    $e->getMessage(),
                ]
            );

            return response()->json([
                'success' => false,
                'message' =>
                'Unable to create the setup administrator.',
            ], 500);
        }
    }
}
