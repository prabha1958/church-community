<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\PlatformChurchUpdateRequest;
use App\Models\Church;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Requests\PlatformChurchLogoRequest;
use Illuminate\Support\Facades\Storage;

class PlatformChurchController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user || !$user->church_id) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is not associated with a church.',
            ], 403);
        }

        $church = Church::on('platform')
            ->find($user->church_id);

        if (!$church) {
            return response()->json([
                'success' => false,
                'message' => 'Your church could not be found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'church' => $church,
            ],
        ]);
    }

    public function update(
        PlatformChurchUpdateRequest $request
    ): JsonResponse {
        $user = $request->user();

        if (!$user || !$user->church_id) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is not associated with a church.',
            ], 403);
        }

        $church = Church::on('platform')
            ->find($user->church_id);

        if (!$church) {
            return response()->json([
                'success' => false,
                'message' => 'Your church could not be found.',
            ], 404);
        }

        $church->forceFill([
            'church_name' => trim($request->input('church_name')),
            'short_name' => $request->input('short_name')
                ? trim($request->input('short_name'))
                : null,
            'email' => $request->input('email')
                ? strtolower(trim($request->input('email')))
                : null,
            'mobile' => $request->input('mobile')
                ? trim($request->input('mobile'))
                : null,
            'address' => $request->input('address')
                ? trim($request->input('address'))
                : null,
            'city' => $request->input('city')
                ? trim($request->input('city'))
                : null,
            'state' => $request->input('state')
                ? trim($request->input('state'))
                : null,
            'country' => $request->input('country')
                ? trim($request->input('country'))
                : null,
        ])->save();

        return response()->json([
            'success' => true,
            'message' => 'Church information updated successfully.',
            'data' => [
                'church' => $church->fresh(),
            ],
        ]);
    }

    public function joinUrl(Church $church): JsonResponse
    {
        if (strtolower((string) $church->status) !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Church is not active.',
            ], 422);
        }

        $churchCode = strtoupper(
            trim($church->church_code)
        );

        $joinUrl = 'churchmsg://join/' . $churchCode;

        return response()->json([
            'success' => true,
            'data' => [
                'church_code' => $churchCode,
                'church_name' => $church->church_name,
                'join_url' => $joinUrl,
                'qr_data' => $joinUrl,
            ],
        ]);
    }

    public function uploadLogo(
        PlatformChurchLogoRequest $request
    ): JsonResponse {
        $user = $request->user();

        if (!$user || !$user->church_id) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is not associated with a church.',
            ], 403);
        }

        $church = Church::on('platform')
            ->find($user->church_id);

        if (!$church) {
            return response()->json([
                'success' => false,
                'message' => 'Your church could not be found.',
            ], 404);
        }

        if (strtolower((string) $church->status) !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Your church is not active.',
            ], 403);
        }

        $file = $request->file('logo');

        /*
     * Store each church's logo in its own directory.
     *
     * Example:
     * storage/app/public/churches/TEST001/logo.png
     */
        $directory = 'churches/' . strtoupper(
            trim($church->church_code)
        );

        /*
     * Delete the previous logo if one exists.
     */
        if ($church->logo) {
            Storage::disk('public')->delete(
                $church->logo
            );
        }

        /*
     * Store the new logo with a stable filename.
     */
        $extension = strtolower(
            $file->getClientOriginalExtension()
        );

        $path = $file->storeAs(
            $directory,
            'logo.' . $extension,
            'public'
        );

        /*
     * Save the relative storage path in the database.
     */
        $church->forceFill([
            'logo' => $path,
        ])->save();

        $church->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Church logo uploaded successfully.',
            'data' => [
                'church' => $church,
            ],
        ]);
    }
}
