<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Mail\ChurchRegistrationRequestMail;
use App\Models\ChurchRegistrationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;


class ChurchRegistrationRequestController extends Controller
{



    /**
     * Submit a new church registration request.
     *
     * This endpoint is public because the church in-charge
     * does not have a platform account yet.
     */
    public function store(Request $request): JsonResponse
    {

        Log::info('CHURCH REGISTRATION CONTROLLER REACHED');
        /*
         * ---------------------------------------------------------
         * 1. Validate request
         * ---------------------------------------------------------
         */
        $validated = $request->validate([
            'church_name' => [
                'required',
                'string',
                'max:255',
            ],

            'address' => [
                'required',
                'string',
                'max:1000',
            ],

            'city' => [
                'required',
                'string',
                'max:100',
            ],

            'admin_name' => [
                'required',
                'string',
                'max:255',
            ],

            'admin_email' => [
                'required',
                'email',
                'max:255',
            ],
        ]);

        /*
         * ---------------------------------------------------------
         * 2. Normalize values
         * ---------------------------------------------------------
         */
        $churchName = trim($validated['church_name']);
        $address = trim($validated['address']);
        $city = trim($validated['city']);
        $adminName = trim($validated['admin_name']);
        $adminEmail = strtolower(
            trim($validated['admin_email'])
        );

        /*
         * ---------------------------------------------------------
         * 3. Prevent duplicate pending requests
         *
         * We don't want the same email submitting the same request
         * repeatedly while the first request is still pending.
         * ---------------------------------------------------------
         */
        $existingRequest = ChurchRegistrationRequest::on('platform')
            ->where('admin_email', $adminEmail)
            ->where('status', 'pending')
            ->first();

        if ($existingRequest) {
            return response()->json([
                'success' => false,
                'message' =>
                'A registration request from this email address is already pending review.',
            ], 422);
        }

        try {

            /*
             * -----------------------------------------------------
             * 4. Create registration request
             * -----------------------------------------------------
             */
            $registrationRequest =
                ChurchRegistrationRequest::on('platform')->create([
                    'church_name' => $churchName,
                    'address' => $address,
                    'city' => $city,
                    'admin_name' => $adminName,
                    'admin_email' => $adminEmail,
                    'status' => 'pending',
                ]);

            /*
             * -----------------------------------------------------
             * 5. Send notification to application owner
             * -----------------------------------------------------
             */
            $ownerEmail = config(
                'services.platform.owner_email'
            );

            if (!$ownerEmail) {

                Log::warning(
                    'Church registration request created but platform owner email is not configured.',
                    [
                        'registration_request_id' =>
                        $registrationRequest->id,

                        'church_name' =>
                        $registrationRequest->church_name,

                        'admin_email' =>
                        $registrationRequest->admin_email,
                    ]
                );
            } else {

                Mail::to($ownerEmail)->queue(
                    new ChurchRegistrationRequestMail(
                        $registrationRequest
                    )
                );
            }

            /*
             * -----------------------------------------------------
             * 6. Return success
             * -----------------------------------------------------
             */
            return response()->json([
                'success' => true,

                'message' =>
                'Your church registration request has been submitted successfully.',

                'data' => [
                    'registration_request_id' =>
                    $registrationRequest->id,

                    'status' =>
                    $registrationRequest->status,
                ],
            ], 201);
        } catch (\Throwable $e) {

            Log::error(
                'Unable to create church registration request.',
                [
                    'church_name' =>
                    $churchName,

                    'admin_name' =>
                    $adminName,

                    'admin_email' =>
                    $adminEmail,

                    'error' =>
                    $e->getMessage(),
                ]
            );

            return response()->json([
                'success' => false,

                'message' =>
                'Unable to submit your church registration request. Please try again.',
            ], 500);
        }
    }

    /**
     * List church registration requests.
     *
     * By default, only pending requests are returned.
     */
    public function index(Request $request): JsonResponse
    {
        $status = $request->input('status', 'pending');

        if (!in_array($status, ['pending', 'approved', 'rejected'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid registration request status.',
            ], 422);
        }

        $requests = ChurchRegistrationRequest::on('platform')
            ->where('status', $status)
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => [
                'requests' => $requests,
            ],
        ]);
    }


    /**
     * Approve a registration request.
     *
     * This stage only records the approval.
     * Tenant provisioning will be connected in the next step.
     */
    public function approve(
        Request $request,
        ChurchRegistrationRequest $registrationRequest
    ): JsonResponse {
        if ($registrationRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending registration requests can be approved.',
            ], 422);
        }

        $registrationRequest->update([
            'status' => 'approved',
            'reviewed_at' => now(),
            'reviewed_by' => $request->user()?->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Church registration request approved successfully.',
            'data' => [
                'registration_request' => $registrationRequest->fresh(),
            ],
        ]);
    }


    /**
     * Reject a registration request.
     */
    public function reject(
        Request $request,
        ChurchRegistrationRequest $registrationRequest
    ): JsonResponse {
        if ($registrationRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending registration requests can be rejected.',
            ], 422);
        }

        $validated = $request->validate([
            'admin_notes' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ]);

        $registrationRequest->update([
            'status' => 'rejected',
            'reviewed_at' => now(),
            'reviewed_by' => $request->user()?->id,
            'admin_notes' => $validated['admin_notes'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Church registration request rejected.',
            'data' => [
                'registration_request' => $registrationRequest->fresh(),
            ],
        ]);
    }
}
