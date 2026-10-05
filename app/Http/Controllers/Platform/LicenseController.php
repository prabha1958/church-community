<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Church;
use App\Models\License;
use App\Services\LicenseService;
use Illuminate\Http\Request;

class LicenseController extends Controller
{
    public function index(Request $request)
    {
        $licenses = License::on('platform')
            ->with('church')
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $licenses,
        ]);
    }

    public function show(License $license)
    {
        $license->load('church');

        return response()->json([
            'success' => true,
            'data' => $license,
        ]);
    }

    public function store(
        Request $request,
        LicenseService $licenseService
    ) {
        $validated = $request->validate([
            'church_id' => [
                'required',
                'integer',
                'exists:platform.churches,id',
            ],

            'plan' => [
                'required',
                'in:monthly,annual,lifetime',
            ],
        ]);

        $church = Church::on('platform')
            ->findOrFail($validated['church_id']);

        $license = $licenseService->activate(
            $church,
            $validated['plan']
        );

        return response()->json([
            'success' => true,
            'message' => 'License activated successfully.',
            'data' => $license,
        ], 201);
    }

    public function renew(
        Request $request,
        License $license,
        LicenseService $licenseService
    ) {
        $validated = $request->validate([
            'plan' => [
                'required',
                'in:monthly,annual,lifetime',
            ],
        ]);

        $license = $licenseService->renew(
            $license,
            $validated['plan']
        );

        return response()->json([
            'success' => true,
            'message' => 'License renewed successfully.',
            'data' => $license,
        ]);
    }

    public function suspend(
        License $license,
        LicenseService $licenseService
    ) {
        $license = $licenseService->suspend($license);

        return response()->json([
            'success' => true,
            'message' => 'License suspended.',
            'data' => $license,
        ]);
    }

    public function cancel(
        License $license,
        LicenseService $licenseService
    ) {
        $license = $licenseService->cancel($license);

        return response()->json([
            'success' => true,
            'message' => 'License cancelled.',
            'data' => $license,
        ]);
    }
}
