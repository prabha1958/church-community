<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\PastorRequest;
use App\Models\Pastor;
use Illuminate\Support\Facades\Storage;

class PastorController extends Controller
{
    /**
     * List pastors (paginated and ordered by order_no).
     * Public or admin depending on route registration.
     */
    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 20);
        $data = Pastor::orderBy('order_no');


        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * Show single pastor
     */
    public function show(Pastor $pastor)
    {
        return response()->json(['success' => true, 'data' => $pastor]);
    }

    /**
     * Store new pastor (admin)
     */
    public function store(PastorRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('pastors/photos', 'public');
        }

        $pastor = Pastor::create($data);

        return response()->json(['success' => true, 'message' => 'Pastor created', 'data' => $pastor], 201);
    }

    /**
     * Update existing pastor (admin)
     */
    public function update(PastorRequest $request, Pastor $pastor)
    {
        $data = $request->validated();

        if ($request->hasFile('photo')) {
            // delete old photo if exists
            if ($pastor->photo && Storage::disk('public')->exists($pastor->photo)) {
                Storage::disk('public')->delete($pastor->photo);
            }
            $data['photo'] = $request->file('photo')->store('pastors/photos', 'public');
        }

        $pastor->update($data);

        return response()->json(['success' => true, 'message' => 'Pastor updated', 'data' => $pastor]);
    }

    /**
     * Delete pastor (admin)
     */
    public function destroy(Pastor $pastor)
    {
        // delete photo file
        if ($pastor->photo && Storage::disk('public')->exists($pastor->photo)) {
            Storage::disk('public')->delete($pastor->photo);
        }

        $pastor->delete();

        return response()->json(['success' => true, 'message' => 'Pastor deleted']);
    }
}
