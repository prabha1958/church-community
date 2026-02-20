<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Http\Requests\PoorFeedingRequest;
use App\Models\PoorFeeding;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use App\Models\DeviceToken;
use App\Services\ExpoPushService;
use Illuminate\Support\Str;

class PoorFeedingController extends Controller
{
    /**
     * Public: paginated list of poor feedings
     */
    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 15);

        $query = PoorFeeding::with('sponsor')->orderByDesc('id');

        // optional filters


        $page = $query->paginate($perPage);

        // Optionally convert storage paths to public urls:
        // $page->getCollection()->transform(function ($item) {
        //     $item->event_photos = array_map(fn($p) => $p ? Storage::url($p) : null, $item->event_photos ?? []);
        //    return $item;
        //   });

        return response()->json(['success' => true, 'data' => $page]);
    }

    /**
     * Public: show single poor feeding event
     */
    public function show(PoorFeeding $poorFeeding)
    {
        $poorFeeding->load('sponsor');
        $poorFeeding->event_photos = array_map(fn($p) => $p ? Storage::url($p) : null, $poorFeeding->event_photos ?? []);
        return response()->json(['success' => true, 'data' => $poorFeeding]);
    }

    /**
     * Admin: create poor feeding event (supports multiple photos)
     */
    public function store(PoorFeedingRequest $request)
    {
        $data = $request->validated();

        $photos = [];
        if ($request->hasFile('event_photos')) {
            foreach ($request->file('event_photos') as $file) {
                $photos[] = $file->store('poor_feedings/photos', 'public');
            }
        }

        $data['event_photos'] = $photos;

        $pf = PoorFeeding::create($data);

        $pf->event_photos = array_map(fn($p) => $p ? Storage::url($p) : null, $pf->event_photos ?? []);
        return response()->json(['success' => true, 'data' => $pf], 201);
    }

    /**
     * Admin: update event. If files provided and append_photos=1, append; else replace.
     */
    public function update(PoorFeedingRequest $request, PoorFeeding $poorFeeding)
    {
        $data = $request->validated();

        $existing = $poorFeeding->event_photos ?? [];

        if ($request->hasFile('event_photos')) {
            $uploaded = [];
            foreach ($request->file('event_photos') as $file) {
                $uploaded[] = $file->store('poor_feedings/photos', 'public');
            }

            if ($request->boolean('append_photos')) {
                $data['event_photos'] = array_values(array_merge($existing, $uploaded));
            } else {
                // delete old photos
                foreach ($existing as $path) {
                    if ($path && Storage::disk('public')->exists($path)) {
                        Storage::disk('public')->delete($path);
                    }
                }
                $data['event_photos'] = $uploaded;
            }
        }

        $poorFeeding->update($data);

        $poorFeeding->event_photos = array_map(fn($p) => $p ? Storage::url($p) : null, $poorFeeding->event_photos ?? []);
        return response()->json(['success' => true, 'data' => $poorFeeding]);
    }

    /**
     * Admin: delete event and stored photos
     */
    public function destroy(PoorFeeding $poorFeeding)
    {
        foreach ($poorFeeding->event_photos ?? [] as $path) {
            if ($path && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }

        $poorFeeding->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Admin: remove a single photo by path (body: photo_path = storage path e.g. 'poor_feedings/photos/abc.jpg')
     */
    public function removePhoto(Request $request, PoorFeeding $poorFeeding)
    {
        $photoPath = (string) $request->input('photo_path');
        if ($photoPath === '') {
            return response()->json(['success' => false, 'message' => 'photo_path required'], 422);
        }

        $photos = $poorFeeding->event_photos ?? [];
        $new = Arr::where($photos, fn($p) => $p !== $photoPath);

        if (in_array($photoPath, $photos) && Storage::disk('public')->exists($photoPath)) {
            Storage::disk('public')->delete($photoPath);
        }

        $poorFeeding->event_photos = array_values($new);
        $poorFeeding->save();

        $poorFeeding->event_photos = array_map(fn($p) => $p ? Storage::url($p) : null, $poorFeeding->event_photos ?? []);

        return response()->json(['success' => true, 'data' => $poorFeeding]);
    }

    public function hide(PoorFeeding $pfeeding)
    {

        if (!$pfeeding->published) {
            return response()->json([
                'message' => 'Message already hidden'
            ], 409);
        }

        $pfeeding->update([
            'published' => 0,

        ]);



        $tokens = DeviceToken::pluck('token')->toArray();

        ExpoPushService::send(
            $tokens,
            "poor feeding",
            Str::limit($pfeeding->brief_description, 80),
            [
                'type' => 'message',
                'message_id' => $pfeeding->id,

            ]
        );


        return response()->json(['success' => true]);
    }

    public function display(PoorFeeding $pfeeding)
    {


        if ($pfeeding->published) {
            return response()->json([
                'message' => 'Message already on display'
            ], 409);
        }

        $pfeeding->update([
            'published' => 1,

        ]);



        $tokens = DeviceToken::pluck('token')->toArray();

        ExpoPushService::send(
            $tokens,
            "poor feeding",
            Str::limit($pfeeding->brief_description, 80),
            [
                'type' => 'message',
                'message_id' => $pfeeding->id,

            ]
        );


        return response()->json(['success' => true]);
    }
}
