<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminCreateAllianceRequest;
use App\Http\Requests\AdminUpdateAllianceRequest;
use App\Models\Alliance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Mail\AllianceCreatedMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class AllianceController extends Controller
{
    /**
     * Admin creates an alliance profile for any member (member_id required).
     */

    public function store(AdminCreateAllianceRequest $request): JsonResponse
    {
        $data = $request->validated();

        foreach (['profile_photo', 'photo1', 'photo2', 'photo3'] as $field) {
            if ($request->hasFile($field)) {
                $data[$field] = $request->file($field)->store('alliances/photos', 'public');
            }
        }

        $alliance = Alliance::create($data);
        $alliance->load('member');

        if ($alliance->member?->email) {
            Mail::to($alliance->member->email)
                ->send(new AllianceCreatedMail($alliance));
        }

        return response()->json([
            'success' => true,
            'message' => 'Alliance created successfully.',
            'alliance' => $alliance,
        ], 201);
    }

    public function index(Request $request)
    {
        $alliances = Alliance::query()
            ->with(['member:id,first_name,last_name'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = $request->search;

                $q->where(function ($qq) use ($s) {
                    $qq->where('id', $s) // alliance id exact
                        ->orWhere('first_name', 'like', "%{$s}%");
                });
            })
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($a) {
                return [
                    'id'            => $a->id,
                    'profile_photo' => $a->profile_photo,
                    'family_name'   => $a->family_name,
                    'first_name'    => $a->first_name,
                    'last_name'     => $a->last_name,
                    'age'           => $a->date_of_birth
                        ? \Carbon\Carbon::parse($a->date_of_birth)->age
                        : null,
                    'profession'    => $a->profession,
                    'member_name'   => optional($a->member)
                        ? $a->member->first_name . ' ' . $a->member->last_name
                        : null,
                    'is_published'  => (bool) $a->is_published,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $alliances,
        ]);
    }


    public function show(Alliance $alliance)
    {
        $alliance->load('member');

        return response()->json([
            'success' => true,
            'data' => $alliance,
        ]);
    }

    public function update(AdminUpdateAllianceRequest $request, Alliance $alliance)
    {
        $data = $request->validated();

        foreach (['profile_photo', 'photo1', 'photo2', 'photo3'] as $field) {
            if ($request->hasFile($field)) {
                if ($alliance->$field && Storage::disk('public')->exists($alliance->$field)) {
                    Storage::disk('public')->delete($alliance->$field);
                }
                $data[$field] = $request->file($field)->store('alliances/photos', 'public');
            }
        }

        $alliance->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Alliance updated successfully',
            'data' => $alliance->fresh(),
        ]);
    }

    public function togglePublish(Request $request, Alliance $alliance)
    {
        $alliance->update([
            'is_published' => ! $alliance->is_published,
        ]);

        return response()->json([
            'success' => true,
            'message' => $alliance->is_published
                ? 'Alliance published successfully'
                : 'Alliance unpublished successfully',
            'is_published' => $alliance->is_published,
        ]);
    }
}
