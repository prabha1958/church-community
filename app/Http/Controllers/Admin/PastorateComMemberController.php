<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PastorateComMember;
use Illuminate\Support\Facades\Log;


class PastorateComMemberController extends Controller
{
    public function index()

    {
        $pcm = PastorateComMember::orderBy('dt_from', 'desc')->get();
        return response()->json([
            'success' => true,
            'data' => $pcm
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'family_name' => 'nullable|string|max:255',
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'date_of_birth' => 'nullable|date',
            'dt_from' => 'nullable|date',
            'dt_to' => 'nullable|date',
            'status' => 'string|in:in,out',
            'designation' => 'nullable|string|max:255',
            'profile_photo' => 'nullable|image|max:5120',
            'achievements' => 'nullable|string',
        ]);

        // Handle image upload
        if ($request->hasFile('profile_photo')) {
            $data['profile_photo'] = $request->file('profile_photo')->store('pastorate/photos', 'public');
        }

        $member = PastorateComMember::create($data);

        return response()->json(['success' => true, 'member' => $member], 201);
    }

    public function show(PastorateComMember $pastorateComMember)
    {
        return response()->json($pastorateComMember);
    }

    public function update(Request $request, PastorateComMember $commember)
    {


        $data = $request->validate([
            'family_name' => 'nullable|string|max:255',
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'date_of_birth' => 'nullable|date',
            'dt_from' => 'nullable|date',
            'dt_to' => 'nullable|date',
            'status' => 'sometimes|in:in,out',
            'designation' => 'nullable|string|max:255',
            'profile_photo' => 'nullable|image|max:5120',
            'achievements' => 'nullable|string',
        ]);

        if ($request->hasFile('profile_photo')) {
            $data['profile_photo'] = $request->file('profile_photo')->store('pastorate/photos', 'public');
        }

        $commember->update($data);

        return response()->json(['success' => true, 'member' => $commember]);
    }

    public function destroy(PastorateComMember $pastorateComMember)
    {
        $pastorateComMember->delete();
        return response()->json(['success' => true]);
    }
}
