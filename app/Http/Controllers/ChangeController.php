<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Change;
use App\Models\Message;
use Illuminate\Support\Facades\Log;
use App\Models\Member;

class ChangeController extends Controller
{
    public function store(Request $request, Member $member)
    {
        Log::info('method reached');

        $data = $request->validate([
            'member_id' => 'nullable|exists:members,id',
            'chng_field' => 'required|string|max:255',
            'message' => 'required|string',
            'image_path' => 'nullable|image|max:2048',

        ]);

        if ($request->hasFile('image_path')) {
            $data['image_path'] = $request->file('image_path')
                ->store('changes', 'public');
        }

        $change = Change::create($data);

        if ($change) {
            $message = "We have just received a request to effact change .$change->chng_field. of your profile. Kindly wait for the change to be effected by the Admin";

            try {

                Message::create([
                    'member_id' => $member->id,
                    'title' => 'Request for change of' . $change->chng_field,
                    'body' => $message,
                    'message_type' => 'changes',
                    'is_published' => 1,
                    'published_at' => now(),
                ]);
            } catch (\Throwable $e) {
                Log::error('Failed to persist birthday greeting', [
                    'member_id' => $member->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
}
