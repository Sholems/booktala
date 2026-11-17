<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RoomType;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RoomTypeController extends Controller
{
    public function index()
    {
        $roomTypes = RoomType::with('rooms')->orderBy('sort_order')->get();
        return view('admin.room-types.index', compact('roomTypes'));
    }

    public function create()
    {
        return view('admin.room-types.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'base_price' => 'required|numeric|min:0',
            'total_rooms' => 'required|integer|min:1',
            'max_adults' => 'required|integer|min:1',
            'max_children' => 'nullable|integer|min:0',
        ]);

        $roomType = RoomType::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
            'short_description' => $request->short_description,
            'base_price' => $request->base_price,
            'weekend_price' => $request->weekend_price,
            'price_per_week' => $request->price_per_week,
            'price_per_month' => $request->price_per_month,
            'max_adults' => $request->max_adults,
            'max_children' => $request->max_children ?? 0,
            'total_rooms' => $request->total_rooms,
            'bed_type' => $request->bed_type,
            'room_size' => $request->room_size,
            'amenities' => $request->amenities ?? [],
            'is_active' => $request->is_active ?? true,
            'sort_order' => $request->sort_order ?? 0,
        ]);

        $roomCount = (int) $request->total_rooms;
        for ($i = 1; $i <= $roomCount; $i++) {
            Room::create([
                'room_type_id' => $roomType->id,
                'room_number' => (string) $i,
                'floor' => $request->floor ?? 1,
                'building' => $request->building,
                'status' => 'available',
            ]);
        }

        return redirect()->route('admin.room-types.index')->with('success', 'Room type created successfully');
    }

    public function edit(RoomType $roomType)
    {
        return view('admin.room-types.edit', compact('roomType'));
    }

    public function update(Request $request, RoomType $roomType)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'base_price' => 'required|numeric|min:0',
            'total_rooms' => 'required|integer|min:1',
            'max_adults' => 'required|integer|min:1',
        ]);

        $roomType->update([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
            'short_description' => $request->short_description,
            'base_price' => $request->base_price,
            'weekend_price' => $request->weekend_price,
            'price_per_week' => $request->price_per_week,
            'price_per_month' => $request->price_per_month,
            'max_adults' => $request->max_adults,
            'max_children' => $request->max_children ?? 0,
            'total_rooms' => $request->total_rooms,
            'bed_type' => $request->bed_type,
            'room_size' => $request->room_size,
            'amenities' => $request->amenities ?? [],
            'is_active' => $request->is_active ?? true,
            'sort_order' => $request->sort_order ?? 0,
        ]);

        return redirect()->route('admin.room-types.index')->with('success', 'Room type updated successfully');
    }

    public function destroy(RoomType $roomType)
    {
        $roomType->delete();
        return redirect()->route('admin.room-types.index')->with('success', 'Room type deleted successfully');
    }

    public function rooms(RoomType $roomType)
    {
        $rooms = $roomType->rooms()->orderBy('room_number')->get();
        return view('admin.room-types.rooms', compact('roomType', 'rooms'));
    }

    public function updateRoomStatus(Request $request, Room $room)
    {
        $request->validate([
            'status' => 'required|in:available,occupied,reserved,maintenance,cleaning',
        ]);

        $room->update(['status' => $request->status]);

        return back()->with('success', 'Room status updated');
    }
}