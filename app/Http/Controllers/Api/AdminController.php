<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\User;
use App\Services\ReservationLifecycle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    private const BOOKING_STATUSES = 'pending,confirmed,checked_in,checked_out,cancelled,no_show';

    public function dashboard()
    {
        $totalProperties = Property::count();
        $totalReservations = Reservation::count();
        $pendingReservations = Reservation::where('booking_status', 'pending')->count();
        $confirmedReservations = Reservation::where('booking_status', 'confirmed')->count();

        $totalRevenue = Reservation::paid()->sum(DB::raw('total_amount - caution_fee'));

        $recentReservations = Reservation::with('property', 'user')
            ->latest()
            ->limit(10)
            ->get();

        return response()->json([
            'total_properties' => $totalProperties,
            'total_reservations' => $totalReservations,
            'pending_reservations' => $pendingReservations,
            'confirmed_reservations' => $confirmedReservations,
            'total_revenue' => $totalRevenue,
            'recent_reservations' => $recentReservations,
        ]);
    }

    public function reservations(Request $request)
    {
        $query = Reservation::with('property', 'user');

        if ($request->status) {
            $query->where('booking_status', $request->status);
        }

        if ($request->payment_status) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->from_date) {
            $query->whereDate('check_in', '>=', $request->from_date);
        }

        if ($request->to_date) {
            $query->whereDate('check_out', '<=', $request->to_date);
        }

        $reservations = $query->latest()->paginate(20);

        return response()->json($reservations);
    }

    public function updateReservation(Request $request, $id, ReservationLifecycle $lifecycle)
    {
        $reservation = Reservation::findOrFail($id);

        $request->validate([
            'status' => 'required|in:'.self::BOOKING_STATUSES,
            'reason' => 'nullable|string|max:500',
        ]);

        $newStatus = $request->status;

        if ($reservation->booking_status !== $newStatus) {
            match ($newStatus) {
                'confirmed' => $lifecycle->confirm($reservation, $request->user()),
                'checked_in' => $lifecycle->checkIn($reservation, $request->user(), $request->boolean('allow_unpaid')),
                'checked_out' => $lifecycle->checkOut($reservation, $request->user()),
                'cancelled' => $lifecycle->cancel($reservation, $request->user(), $request->reason),
                'no_show' => $lifecycle->markNoShow($reservation, $request->user()),
                default => abort(422, 'The requested reservation transition is not allowed.'),
            };
        }

        return response()->json(['reservation' => $reservation]);
    }

    public function properties(Request $request)
    {
        $query = Property::with('images');

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $properties = $query->latest()->paginate(20);

        return response()->json($properties);
    }

    public function createProperty(Request $request)
    {
        $data = $request->validate($this->propertyRules(true));
        $data['created_by'] = $request->user()->id;

        $property = Property::create($data);

        return response()->json(['property' => $property], 201);
    }

    public function updateProperty(Request $request, $id)
    {
        $property = Property::findOrFail($id);

        $property->update($request->validate($this->propertyRules(false)));

        return response()->json(['property' => $property]);
    }

    public function deleteProperty($id)
    {
        $property = Property::findOrFail($id);
        $property->delete();

        return response()->json(['message' => 'Property deleted']);
    }

    public function customers(Request $request)
    {
        $customers = User::role('customer')
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->paginate(20);

        return response()->json($customers);
    }

    public function payments(Request $request)
    {
        $query = Payment::with('reservation.property', 'reservation.user');

        if ($request->status) {
            $query->where('paystack_status', $request->status);
        }

        if ($request->type) {
            $query->where('type', $request->type);
        }

        $payments = $query->latest()->paginate(20);

        return response()->json($payments);
    }

    private function propertyRules(bool $creating): array
    {
        $required = $creating ? 'required' : 'sometimes';

        return [
            'title' => "{$required}|string|max:255",
            'description' => "{$required}|string",
            'short_description' => 'nullable|string|max:255',
            'address' => "{$required}|string|max:255",
            'city' => "{$required}|string|max:100",
            'state' => "{$required}|string|max:100",
            'country' => 'sometimes|string|max:100',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'price_per_night' => "{$required}|numeric|min:0",
            'weekend_price' => 'nullable|numeric|min:0',
            'price_per_week' => 'nullable|numeric|min:0',
            'price_per_month' => 'nullable|numeric|min:0',
            'min_nights' => 'sometimes|integer|min:1',
            'max_nights' => 'sometimes|integer|min:1',
            'max_guests' => "{$required}|integer|min:1",
            'bedrooms' => "{$required}|integer|min:0",
            'bathrooms' => "{$required}|integer|min:0",
            'toilets' => "{$required}|integer|min:0",
            'status' => 'sometimes|in:active,inactive,maintenance',
            'is_featured' => 'sometimes|boolean',
            'check_in_time' => 'sometimes|date_format:H:i',
            'check_out_time' => 'sometimes|date_format:H:i',
            'house_rules' => 'nullable|string',
            'cancellation_policy' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string|max:255',
        ];
    }
}
