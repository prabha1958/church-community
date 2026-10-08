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
}
