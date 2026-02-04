<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\EventRequest;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Arr;
use App\Models\DeviceToken;
use App\Services\ExpoPushService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class EventController extends Controller
{
    /**
     * Public: list events (paginated)
     */
    public function index(Request $request): JsonResponse
    {

        $data = Event::query()->orderByDesc('date_of_event')->get();

        // optional filtering: upcoming only
        //  if ($request->boolean('upcoming')) {
        //   $query->where('date_of_event', '>=', now()->toDateString());
        //   }

        $events = $data;
        return response()->json(['success' => true, 'data' => $events]);
    }

    /**
     * Public: show single event
     */
    public function show(Event $event): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $event]);
    }

    /**
     * Admin: store event (supports multiple photos upload)
     */
    public function store(EventRequest $request): JsonResponse
    {
        $data = $request->validated();

        $photos = [];

        if ($request->hasFile('event_photos')) {
            foreach ($request->file('event_photos') as $file) {
                $path = $file->store('events/photos', 'public');
                $photos[] = $path;
            }
        }

        $data['event_photos'] = $photos;

        $event = Event::create($data);

        return response()->json(['success' => true, 'event' => $event], 201);
    }

    /**
     * Admin: update event (can replace photos or append)
     *
     * Query parameter `append_photos=1` will append uploaded photos
     * Otherwise uploaded photos replace existing photo list.
     */
    public function update(EventRequest $request, Event $event): JsonResponse
    {
        $data = $request->validated();

        $existing = $event->event_photos ?? [];

        if ($request->hasFile('event_photos')) {
            $uploaded = [];
            foreach ($request->file('event_photos') as $file) {
                $uploaded[] = $file->store('events/photos', 'public');
            }

            if ($request->boolean('append_photos')) {
                $data['event_photos'] = array_values(array_merge($existing, $uploaded));
            } else {
                // Optionally delete old photos when replacing
                foreach ($existing as $oldPath) {
                    if ($oldPath && Storage::disk('public')->exists($oldPath)) {
                        Storage::disk('public')->delete($oldPath);
                    }
                }
                $data['event_photos'] = $uploaded;
            }
        }

        $event->update($data);

        return response()->json(['success' => true, 'event' => $event]);
    }


    public function hide(Event $event)
    {

        if (!$event->published) {
            return response()->json([
                'message' => 'Message already hidden'
            ], 409);
        }

        $event->update([
            'published' => 0,

        ]);



        $tokens = DeviceToken::pluck('token')->toArray();

        ExpoPushService::send(
            $tokens,
            $event->name_of_event,
            Str::limit($event->description, 80),
            [
                'type' => 'message',
                'message_id' => $event->id,

            ]
        );


        return response()->json(['success' => true]);
    }

    public function display(Event $event)
    {


        if ($event->published) {
            return response()->json([
                'message' => 'Message already on display'
            ], 409);
        }

        $event->update([
            'published' => 1,

        ]);



        $tokens = DeviceToken::pluck('token')->toArray();

        ExpoPushService::send(
            $tokens,
            $event->name_of_event,
            Str::limit($event->description, 80),
            [
                'type' => 'message',
                'message_id' => $event->id,

            ]
        );


        return response()->json(['success' => true]);
    }

    /**
     * Admin: delete event (and delete stored photos)
     */
    public function destroy(Event $event): JsonResponse
    {
        // delete photo files
        foreach ($event->event_photos ?? [] as $path) {
            if ($path && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }

        $event->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Optional helper: remove a single photo from an event
     */
    public function removePhoto(Request $request, Event $event): JsonResponse
    {
        $photo = (string) $request->input('photo_path');
        if (! $photo) {
            return response()->json(['success' => false, 'message' => 'photo_path required'], 422);
        }

        $photos = $event->event_photos ?? [];
        $new = Arr::where($photos, fn($p) => $p !== $photo);

        if (in_array($photo, $photos) && Storage::disk('public')->exists($photo)) {
            Storage::disk('public')->delete($photo);
        }

        $event->event_photos = array_values($new);
        $event->save();

        return response()->json(['success' => true, 'event' => $event]);
    }
}
