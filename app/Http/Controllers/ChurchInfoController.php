<?php

namespace App\Http\Controllers;

use App\Models\Church;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;


class ChurchInfoController extends Controller
{
    public function show(Request $request)
    {
        $churchCode = strtoupper(trim(
            $request->header('X-Church-Code', '')
        ));

        if (!$churchCode) {
            return response()->json([
                'success' => false,
                'message' => 'Church code is required.',
            ], 422);
        }

        $church = Church::where('church_code', $churchCode)
            ->where('status', 'active')
            ->first();

        if (!$church) {
            return response()->json([
                'success' => false,
                'message' => 'Church not found or inactive.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'church_code' => $church->church_code,
                'church_name' => $church->church_name,
                'short_name' => $church->short_name,
                'logo_url' => $church->logo
                    ? asset('storage/' . $church->logo)
                    : null,
            ],
        ]);
    }
}
