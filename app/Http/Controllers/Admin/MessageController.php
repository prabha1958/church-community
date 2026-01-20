<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Message;

class MessageController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'member_id' => 'nullable|exists:members,id',
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'image_path' => 'nullable|image|max:2048',
            'message_type' => 'required|in:general,otp,birthday,anniversary',
        ]);

        if ($request->hasFile('image_path')) {
            $data['image_path'] = $request->file('image_path')
                ->store('messages', 'public');
        }

        return Message::create($data);
    }

    // Publish (triggers notification)
    public function publish(Message $message)
    {
        if ($message->is_published) {
            return response()->json([
                'message' => 'Message already published'
            ], 409);
        }

        $message->update([
            'is_published' => 1,
            'published_at' => now(),
        ]);

        // dispatch push job here

        return response()->json(['success' => true]);
    }

    public function index()
    {
        $data = Message::orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function update(Request $request, Message $message)
    {
        if ($message->is_published) {
            return response()->json([
                'message' => 'Published messages cannot be edited'
            ], 409);
        }

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'image_path' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('image_path')) {
            $data['image_path'] = $request->file('image_path')
                ->store('messages', 'public');
        }

        $message->update($data);

        return response()->json([
            'success' => true,
            'message' => $message
        ]);
    }
}
