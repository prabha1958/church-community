<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\StoreMemberRequest;
use App\Mail\MemberWelcomeMail;
use App\Models\Member;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\UpdateMemberRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class MemberController extends Controller

{
    public function store(StoreMemberRequest $request)
    {
        $data = $request->validated();



        // handle profile photo if your app uses it
        if ($request->hasFile('profile_photo')) {
            $data['profile_photo'] = $request->file('profile_photo')->store('members/photos', 'public');
        }

        // handle couple photo if your app uses it
        if ($request->hasFile('couple_pic')) {
            $data['couple_pic'] = $request->file('couple_pic')->store('members/couplepics', 'public');
        }

        // set defaults if not provided
        if (! array_key_exists('status_flag', $data)) {
            $data['status_flag'] = true; // default active
        }

        if (isset($data['status_flag']) && ! $request->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized to update status'], 403);
        }

        $member = Member::create($data);


        // Send welcome email if email is present
        if (!empty($member->email)) {
            try {
                Mail::to($member->email)->send(new MemberWelcomeMail($member));
            } catch (\Throwable $mailEx) {
                // Log the mail error (optional). Do NOT rollback the DB transaction here.
                // \Log::error('Member welcome mail failed: '.$mailEx->getMessage());
            }
        }


        return response()->json([
            'success' => true,
            'message' => 'Member created.',
            'data' => $member,
        ], 201);
    }

    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 20);
        $query = Member::query()->where('status_flag', true)
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = $request->search;
                $q->where('id', $s)
                    ->orWhere('first_name', 'like', "%{$s}%")
                    ->orWhere('last_name', 'like', "%{$s}%");
            })
            ->orderBy('created_at', 'desc');

        // optional filters (active members)
        if ($request->has('active')) {
            $active = filter_var($request->query('active'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($active !== null) {
                $query->where('status_flag', (bool) $active);
            }
        }

        $page = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $page,
        ]);
    }

    public function show(Member $member)
    {
        return response()->json([
            'success' => true,
            'data' => $member,
        ]);
    }

    public function update(UpdateMemberRequest $request, Member $member)
    {

        Log::info('FILES', $request->allFiles());
        $data = $request->validated();



        if ($request->hasFile('profile_photo')) {
            // delete old photo if present
            if ($member->profile_photo && Storage::disk('public')->exists($member->profile_photo)) {
                Storage::disk('public')->delete($member->profile_photo);
            }
            $data['profile_photo'] = $request->file('profile_photo')->store('members/photos', 'public');
        }

        if ($request->hasFile('couple_pic')) {
            // delete old photo if present
            if ($member->couple_pic && Storage::disk('public')->exists($member->couple_pic)) {
                Storage::disk('public')->delete($member->couple_pic);
            }
            $data['couple_pic'] = $request->file('couple_pic')->store('members/photos', 'public');
        }

        if (isset($data['status_flag']) && ! $request->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized to update status'], 403);
        }

        $member->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Member updated.',
            'data' => $member,
        ]);
    }

    public function deactivate(Request $request, Member $member)
    {
        $member->update([
            'status_flag' => 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Member deactivated successfully.',
        ]);
    }

    public function updateEmail(Request $request, Member $member)
    {
        $data = $request->validate([
            'email' => [
                'sometimes',
                'email',
                Rule::unique('members', 'email')->ignore($member->id),
            ],
            'mobile_number' => [
                'sometimes',
                'string',
                Rule::unique('members', 'mobile_number')->ignore($member->id),
            ],
        ]);

        $member->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Email details updated',
            'data' => $member->only(['email']),
        ]);
    }

    public function updateMobile(Request $request, Member $member)
    {
        $data = $request->validate([

            'mobile_number' => [
                'sometimes',
                'string',
                Rule::unique('members', 'mobile_number')->ignore($member->id),
            ],
        ]);

        $member->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Mobile details updated',
            'data' => $member->only(['mobile_number']),
        ]);
    }
}
