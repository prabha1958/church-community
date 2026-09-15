<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\DeviceToken;
use App\Services\ExpoPushService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MessageController extends Controller
{
    /**
     * Create a message.
     *
     * Admin can create ONLY general messages.
     *
     * General admin message:
     *     member_id    = null
     *     message_type = general
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'title'      => 'required|string|max:255',
            'body'       => 'required|string',
            'image_path' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('image_path')) {
            $data['image_path'] = $request
                ->file('image_path')
                ->store('messages', 'public');
        }

        // Admin-created messages are always general messages.
        $data['member_id'] = null;
        $data['message_type'] = 'general';
        $data['is_published'] = false;

        $message = Message::create($data);

        return response()->json([
            'success' => true,
            'message' => $message,
        ], 201);
    }


    /**
     * Publish/send a message.
     *
     * If member_id is NULL:
     *     send to every device in this tenant.
     *
     * If member_id is present:
     *     send only to that member's devices.
     */
    public function publish(Message $message)
    {
        if ($message->is_published) {
            return response()->json([
                'success' => false,
                'message' => 'Message already published.',
            ], 409);
        }

        $message->update([
            'is_published' => true,
            'published_at' => now(),
        ]);

        $tokens = $this->recipientTokens($message);

        if (!empty($tokens)) {
            ExpoPushService::send(
                $tokens,
                $message->title,
                Str::limit($message->body, 80),
                [
                    'type' => 'message',
                    'id'   => $message->id,
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'sent_to' => count($tokens),
        ]);
    }


    /**
     * List admin-created general messages.
     *
     * System-generated birthday/anniversary/OTP messages
     * are not shown in this admin general-message list.
     */
    public function index()
    {
        $messages = Message::where('message_type', 'general')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $messages,
        ]);
    }


    /**
     * Update an unpublished general message.
     */
    public function update(Request $request, Message $message)
    {
        if ($message->is_published) {
            return response()->json([
                'success' => false,
                'message' => 'Published messages cannot be edited.',
            ], 409);
        }

        // Admin can edit only general messages.
        if ($message->message_type !== 'general') {
            return response()->json([
                'success' => false,
                'message' => 'System-generated messages cannot be edited here.',
            ], 403);
        }

        $data = $request->validate([
            'title'      => 'required|string|max:255',
            'body'       => 'required|string',
            'image_path' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('image_path')) {

            // Delete old image if one exists.
            if (
                $message->image_path &&
                Storage::disk('public')->exists($message->image_path)
            ) {
                Storage::disk('public')->delete($message->image_path);
            }

            $data['image_path'] = $request
                ->file('image_path')
                ->store('messages', 'public');
        }

        $message->update($data);
        $message->refresh();

        return response()->json([
            'success' => true,
            'message' => $message,
        ]);
    }


    /**
     * Hide a published message.
     *
     * This does not delete the message.
     */
    public function hide(Message $message)
    {
        $message->update([
            'is_published' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => $message,
        ]);
    }


    /**
     * Display/send a message again.
     *
     * This is different from publish():
     * it can be used to re-display an already existing message.
     *
     * Recipient selection is still based on member_id.
     */
    public function display(Message $message)
    {
        $message->update([
            'is_published' => true,
            'published_at' => now(),
        ]);

        $tokens = $this->recipientTokens($message);

        if (!empty($tokens)) {
            ExpoPushService::send(
                $tokens,
                $message->title,
                Str::limit($message->body, 80),
                [
                    'type' => 'message',
                    'id'   => $message->id,
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'sent_to' => count($tokens),
        ]);
    }


    /**
     * Show a single message.
     */
    public function show(Message $message)
    {
        return response()->json([
            'success' => true,
            'data' => $message,
        ]);
    }


    /**
     * Determine push recipients.
     *
     * member_id = NULL
     *     => all devices belonging to this church/tenant
     *
     * member_id present
     *     => only devices belonging to that member
     */
    protected function recipientTokens(Message $message): array
    {
        if ($message->member_id !== null) {
            return DeviceToken::where(
                'member_id',
                $message->member_id
            )
                ->pluck('token')
                ->toArray();
        }

        return DeviceToken::pluck('token')->toArray();
    }
}
