<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\PlatformUser;
use App\Services\Platform\PlatformOnboardingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use App\Services\Platform\SetupAdminService;
use Illuminate\Support\Facades\Log;
use App\Mail\SetupAdminWelcomeMail;
use Illuminate\Support\Facades\Mail;
use App\Services\Platform\ChurchRegistrationService;
use App\Http\Requests\ChurchRegistrationRequest;
use App\Models\Church;

class PlatformOnboardingController extends Controller
{


    public function __construct(
        private ChurchRegistrationService $churchRegistrationService
    ) {}

    public function register(
        ChurchRegistrationRequest $request
    ): JsonResponse {
        try {
            $church = $this->churchRegistrationService->register(
                $request->validated()
            );

            return response()->json([
                'success' => true,

                'message' =>
                'Church registered successfully.',

                'data' => [
                    'church' => [
                        'id' => $church->id,
                        'church_name' => $church->church_name,
                        'short_name' => $church->short_name,
                        'church_code' => $church->church_code,
                        'status' => $church->status,
                    ],
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
                'message' => 'Church onboarding completed successfully.',
                'data' => [
                    'church' => [
                        'id' => $church->id,
                        'church_code' => $church->church_code,
                        'church_name' => $church->church_name,
                        'short_name' => $church->short_name,
                        'logo' => $church->logo,
                        'status' => $church->status,
                    ],
                ],
            ]);
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function createSetupAdmin(
        Request $request,
        SetupAdminService $setupAdminService
    ): JsonResponse {
        $validated = $request->validate([
            'church_code' => ['required', 'string', 'max:30'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
        ]);



        /*
    |--------------------------------------------------------------------------
    | 1. Resolve the church from the church code
    |--------------------------------------------------------------------------
    |
    | This is the initial onboarding step, so there is no authenticated
    | platform user yet.
    |
    */

        $church = Church::on('platform')
            ->where('church_code', trim($validated['church_code']))
            ->first();

        if (!$church) {
            return response()->json([
                'success' => false,
                'message' => 'Church not found.',
            ], 404);
        }

        /*
    |--------------------------------------------------------------------------
    | 2. Church must still be in onboarding
    |--------------------------------------------------------------------------
    */

        if (!in_array(
            strtolower((string) $church->status),
            ['pending', 'provisioning'],
            true
        )) {
            return response()->json([
                'success' => false,
                'message' =>
                'Setup administrator creation is not available for this church.',
            ], 422);
        }

        /*
    |--------------------------------------------------------------------------
    | 3. There must not already be an active setup administrator
    |--------------------------------------------------------------------------
    |
    | This is important because this endpoint is for INITIAL setup only.
    | Administrator replacement should be a separate authenticated
    | operation later.
    |
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
    |--------------------------------------------------------------------------
    | 4. Normalize and validate email
    |--------------------------------------------------------------------------
    */

        $email = strtolower(trim($validated['email']));

        /*
    |--------------------------------------------------------------------------
    | 5. Do not allow an email that already belongs to a platform account
    |--------------------------------------------------------------------------
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

        /*
    |--------------------------------------------------------------------------
    | 6. Create the initial setup administrator
    |--------------------------------------------------------------------------
    */

        try {
            /*
        |--------------------------------------------------------------------------
        | The onboarding service owns the transaction and actual mutation.
        |
        | There is NO authenticated PlatformUser at this stage, so we do
        | not pass $user.
        |--------------------------------------------------------------------------
        */

            $result = $setupAdminService->createForOnboarding(
                $church,
                trim($validated['name']),
                $email
            );

            /*
        |--------------------------------------------------------------------------
        | 7. Queue welcome email after successful database creation
        |--------------------------------------------------------------------------
        |
        | Do not return the temporary password to the browser.
        |
        */

            Mail::to($result['user']->email)->queue(
                new SetupAdminWelcomeMail(
                    $result['user'],
                    $church,
                    $result['temporary_password']
                )
            );

            /*
        |--------------------------------------------------------------------------
        | 8. Return success
        |--------------------------------------------------------------------------
        */

            return response()->json([
                'success' => true,
                'message' =>
                'Setup administrator created successfully. Login instructions will be sent by email.',

                'data' => [
                    'user' => [
                        'id' => $result['user']->id,
                        'name' => $result['user']->name,
                        'email' => $result['user']->email,
                        'church_id' => $result['user']->church_id,
                        'role' => $result['user']->role,
                        'status' => $result['user']->status,
                        'must_change_password' =>
                        $result['user']->must_change_password,
                    ],
                ],
            ], 201);
        } catch (RuntimeException $e) {

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {

            Log::error(
                'Unable to create initial onboarding setup administrator.',
                [
                    'church_id' => $church->id,
                    'church_code' => $church->church_code,
                    'error' => $e->getMessage(),
                ]
            );

            return response()->json([
                'success' => false,
                'message' => 'Unable to create the setup administrator.',
            ], 500);
        }
    }
}
