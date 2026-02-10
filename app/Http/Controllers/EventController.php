<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\EventRequest;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
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
