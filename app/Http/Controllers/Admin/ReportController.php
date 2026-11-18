<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GuestProfile;
use App\Models\Payment;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\SystemSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $isHotelMode = SystemSetting::get('hotel_mode', false);

        $startDate = $request->start_date ?? Carbon::now()->startOfMonth()->toDateString();
        $endDate = $request->end_date ?? Carbon::now()->endOfMonth()->toDateString();

        if ($isHotelMode) {
            return $this->hotelReports($request, $startDate, $endDate);
        } else {
            return $this->shortletReports($request, $startDate, $endDate);
        }
    }

    protected function hotelReports(Request $request, $startDate, $endDate)
    {
        $totalRooms = Room::count();
        $availableRooms = Room::where('status', 'available')->count();
        $occupiedRooms = Room::where('status', 'occupied')->count();
        $occupancyRate = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100) : 0;

        $reservations = Reservation::whereBetween('check_in', [$startDate, $endDate])->get();
        $totalBookings = $reservations->count();
        $confirmedBookings = $reservations->where('booking_status', 'confirmed')->count();
        $checkedIn = $reservations->where('booking_status', 'checked_in')->count();
        $cancelledBookings = $reservations->where('booking_status', 'cancelled')->count();

        $revenue = Payment::whereBetween('created_at', [$startDate, $endDate])
            ->where('paystack_status', 'successful')
            ->sum('amount');

        $monthlyRevenue = Payment::whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->where('paystack_status', 'successful')
            ->sum('amount');

        $lastMonthRevenue = Payment::whereMonth('created_at', Carbon::now()->subMonth()->month)
            ->whereYear('created_at', Carbon::now()->subMonth()->year)
            ->where('paystack_status', 'successful')
            ->sum('amount');

        $revenueGrowth = $lastMonthRevenue > 0 ? round((($monthlyRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100) : 0;

        $roomTypes = RoomType::withCount(['rooms'])->get();
        $roomTypeOccupancy = [];
        foreach ($roomTypes as $type) {
            $occupied = $type->rooms()->where('paystack_status', 'occupied')->count();
            $roomTypeOccupancy[] = [
                'name' => $type->name,
                'total' => $type->total_rooms,
                'occupied' => $occupied,
                'rate' => $type->total_rooms > 0 ? round(($occupied / $type->total_rooms) * 100) : 0,
            ];
        }

        $topRoomTypes = RoomType::orderBy('total_rooms', 'desc')->take(5)->get();

        $recentBookings = Reservation::with(['user', 'property'])
            ->whereBetween('check_in', [$startDate, $endDate])
            ->latest()
            ->take(10)
            ->get();

        $dailyBookings = Reservation::whereBetween('check_in', [$startDate, $endDate])
            ->selectRaw('DATE(check_in) as date, COUNT(*) as count')
            ->groupBy('date')
            ->get();

        $guestStats = [
            'total' => User::role('customer')->count(),
            'newThisMonth' => User::role('customer')
                ->whereMonth('created_at', Carbon::now()->month)
                ->count(),
            'withLoyalty' => GuestProfile::count(),
            'avgNights' => round(GuestProfile::avg('total_nights') ?? 0),
        ];

        $dailyRevenue = Payment::whereBetween('created_at', [$startDate, $endDate])
            ->where('paystack_status', 'successful')
            ->selectRaw('DATE(created_at) as date, SUM(amount) as total')
            ->groupBy('date')
            ->get();

        return view('admin.reports.index', compact(
            'totalRooms', 'availableRooms', 'occupiedRooms', 'occupancyRate',
            'totalBookings', 'confirmedBookings', 'checkedIn', 'cancelledBookings',
            'revenue', 'monthlyRevenue', 'revenueGrowth', 'roomTypeOccupancy',
            'topRoomTypes', 'recentBookings', 'dailyBookings', 'guestStats',
            'dailyRevenue', 'startDate', 'endDate'
        ));
    }

    protected function shortletReports(Request $request, $startDate, $endDate)
    {
        $totalProperties = Property::active()->count();
        $featuredProperties = Property::where('is_featured', true)->count();

        $reservations = Reservation::whereBetween('check_in', [$startDate, $endDate])->get();
        $totalBookings = $reservations->count();
        $confirmedBookings = $reservations->where('booking_status', 'confirmed')->count();
        $completedBookings = $reservations->where('booking_status', 'completed')->count();
        $cancelledBookings = $reservations->where('booking_status', 'cancelled')->count();

        $revenue = Payment::whereBetween('created_at', [$startDate, $endDate])
            ->where('paystack_status', 'successful')
            ->sum('amount');

        $monthlyRevenue = Payment::whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->where('paystack_status', 'successful')
            ->sum('amount');

        $lastMonthRevenue = Payment::whereMonth('created_at', Carbon::now()->subMonth()->month)
            ->whereYear('created_at', Carbon::now()->subMonth()->year)
            ->where('paystack_status', 'successful')
            ->sum('amount');

        $revenueGrowth = $lastMonthRevenue > 0 ? round((($monthlyRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100) : 0;

        $topProperties = Property::withCount(['reservations' => function ($q) use ($startDate, $endDate) {
            $q->whereBetween('check_in', [$startDate, $endDate]);
        }])
            ->orderBy('reservations_count', 'desc')
            ->take(5)
            ->get();

        $propertiesByCity = Property::selectRaw('city, COUNT(*) as count')
            ->groupBy('city')
            ->orderBy('count', 'desc')
            ->get();

        $recentBookings = Reservation::with(['user', 'property'])
            ->whereBetween('check_in', [$startDate, $endDate])
            ->latest()
            ->take(10)
            ->get();

        $dailyBookings = Reservation::whereBetween('check_in', [$startDate, $endDate])
            ->selectRaw('DATE(check_in) as date, COUNT(*) as count')
            ->groupBy('date')
            ->get();

        $dailyRevenue = Payment::whereBetween('created_at', [$startDate, $endDate])
            ->where('paystack_status', 'successful')
            ->selectRaw('DATE(created_at) as date, SUM(amount) as total')
            ->groupBy('date')
            ->get();

        $guestStats = [
            'total' => User::role('customer')->count(),
            'newThisMonth' => User::role('customer')
                ->whereMonth('created_at', Carbon::now()->month)
                ->count(),
        ];

        $avgPropertyPrice = Property::active()->avg('price_per_night') ?? 0;

        return view('admin.reports.index', compact(
            'totalProperties', 'featuredProperties',
            'totalBookings', 'confirmedBookings', 'completedBookings', 'cancelledBookings',
            'revenue', 'monthlyRevenue', 'revenueGrowth',
            'topProperties', 'propertiesByCity',
            'recentBookings', 'dailyBookings', 'guestStats',
            'dailyRevenue', 'avgPropertyPrice',
            'startDate', 'endDate'
        ));
    }

    public function revenue(Request $request)
    {
        $startDate = $request->start_date ?? Carbon::now()->startOfYear()->toDateString();
        $endDate = $request->end_date ?? Carbon::now()->toDateString();
        [$yearExpression, $monthExpression] = $this->yearMonthExpressions('created_at');

        $revenueByMonth = Reservation::whereBetween('created_at', [$startDate, $endDate])
            ->paid()
            ->selectRaw("{$yearExpression} as year, {$monthExpression} as month, SUM(total_amount - caution_fee) as total")
            ->groupByRaw("{$yearExpression}, {$monthExpression}")
            ->orderByRaw($yearExpression)
            ->orderByRaw($monthExpression)
            ->get();

        $revenueByProperty = Reservation::whereBetween('check_in', [$startDate, $endDate])
            ->where('booking_status', 'checked_out')
            ->with('property')
            ->get()
            ->groupBy('property_id')
            ->map(function ($reservations) {
                return [
                    'property' => $reservations->first()->property->title ?? 'Unknown',
                    'total' => $reservations->sum('revenue_amount'),
                    'count' => $reservations->count(),
                ];
            })
            ->sortByDesc('total')
            ->take(10);

        return view('admin.reports.revenue', compact('revenueByMonth', 'revenueByProperty', 'startDate', 'endDate'));
    }

    public function occupancy(Request $request)
    {
        $isHotelMode = SystemSetting::get('hotel_mode', false);
        $year = $request->year ?? Carbon::now()->year;

        if ($isHotelMode) {
            $monthlyOccupancy = [];
            for ($month = 1; $month <= 12; $month++) {
                $startOfMonth = Carbon::create($year, $month, 1);
                $endOfMonth = $startOfMonth->copy()->endOfMonth();

                $totalRoomDays = Room::count() * $startOfMonth->daysInMonth;
                $occupiedDays = Reservation::whereBetween('check_in', [$startOfMonth, $endOfMonth])
                    ->whereIn('booking_status', ['confirmed', 'checked_in', 'completed'])
                    ->count();

                $monthlyOccupancy[] = [
                    'month' => $startOfMonth->format('M'),
                    'rate' => $totalRoomDays > 0 ? round(($occupiedDays / $totalRoomDays) * 100) : 0,
                    'occupied' => $occupiedDays,
                    'available' => $totalRoomDays - $occupiedDays,
                ];
            }

            $roomTypeStats = RoomType::with('rooms')->get()->map(function ($type) {
                $occupied = $type->rooms()->where('paystack_status', 'occupied')->count();

                return [
                    'name' => $type->name,
                    'total' => $type->total_rooms,
                    'occupied' => $occupied,
                    'available' => $type->total_rooms - $occupied,
                    'rate' => $type->total_rooms > 0 ? round(($occupied / $type->total_rooms) * 100) : 0,
                ];
            });
        } else {
            $monthlyOccupancy = [];
            for ($month = 1; $month <= 12; $month++) {
                $startOfMonth = Carbon::create($year, $month, 1);
                $endOfMonth = $startOfMonth->copy()->endOfMonth();

                $totalProperties = Property::active()->count();
                $occupiedDays = Reservation::whereBetween('check_in', [$startOfMonth, $endOfMonth])
                    ->whereIn('booking_status', ['confirmed', 'checked_in', 'completed'])
                    ->count();

                $monthlyOccupancy[] = [
                    'month' => $startOfMonth->format('M'),
                    'rate' => $totalProperties > 0 ? round(($occupiedDays / ($totalProperties * $startOfMonth->daysInMonth)) * 100) : 0,
                    'occupied' => $occupiedDays,
                ];
            }

            $roomTypeStats = null;
        }

        return view('admin.reports.occupancy', compact('monthlyOccupancy', 'roomTypeStats', 'year', 'isHotelMode'));
    }

    public function guests(Request $request)
    {
        $startDate = $request->start_date ?? Carbon::now()->startOfYear()->toDateString();
        $endDate = $request->end_date ?? Carbon::now()->toDateString();
        [$yearExpression, $monthExpression] = $this->yearMonthExpressions('created_at');

        $guests = User::role('customer')
            ->withCount(['reservations' => function ($q) use ($startDate, $endDate) {
                $q->whereBetween('check_in', [$startDate, $endDate]);
            }])
            ->with(['guestProfile'])
            ->orderBy('reservations_count', 'desc')
            ->paginate(20);

        $newGuestsByMonth = User::role('customer')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw("{$yearExpression} as year, {$monthExpression} as month, COUNT(*) as count")
            ->groupByRaw("{$yearExpression}, {$monthExpression}")
            ->orderByRaw($yearExpression)
            ->orderByRaw($monthExpression)
            ->get();

        $loyaltyTiers = GuestProfile::selectRaw('loyalty_tier, COUNT(*) as count')
            ->groupBy('loyalty_tier')
            ->get();

        $topGuests = User::role('customer')
            ->with('guestProfile')
            ->get()
            ->filter(fn ($u) => $u->guestProfile)
            ->sortByDesc(fn ($u) => $u->guestProfile->total_spent ?? 0)
            ->take(10);

        return view('admin.reports.guests', compact('guests', 'newGuestsByMonth', 'loyaltyTiers', 'topGuests', 'startDate', 'endDate'));
    }

    public function export(Request $request)
    {
        $type = $request->type ?? 'bookings';
        $startDate = $request->start_date ?? Carbon::now()->startOfMonth()->toDateString();
        $endDate = $request->end_date ?? Carbon::now()->toDateString();

        if ($type === 'bookings') {
            $data = Reservation::with(['user', 'property', 'property'])
                ->whereBetween('check_in', [$startDate, $endDate])
                ->get()
                ->map(fn ($r) => [
                    'ID' => $r->id,
                    'Guest' => $r->user->name ?? 'N/A',
                    'Property' => $r->property->title ?? 'N/A',
                    'Check In' => $r->check_in,
                    'Check Out' => $r->check_out,
                    'Status' => $r->booking_status,
                    'Collected Amount' => $r->total_amount,
                    'Caution Fee' => $r->caution_fee,
                    'Revenue Amount' => $r->revenue_amount,
                ]);
        } else {
            $data = Payment::whereBetween('created_at', [$startDate, $endDate])
                ->where('paystack_status', 'successful')
                ->get()
                ->map(fn ($p) => [
                    'ID' => $p->id,
                    'Amount' => $p->amount,
                    'Status' => $p->paystack_status,
                    'Date' => $p->created_at,
                ]);
        }

        return response()->json($data);
    }

    private function yearMonthExpressions(string $column): array
    {
        return match (DB::getDriverName()) {
            'mysql', 'mariadb' => ["YEAR({$column})", "MONTH({$column})"],
            'pgsql' => [
                "CAST(EXTRACT(YEAR FROM {$column}) AS INTEGER)",
                "CAST(EXTRACT(MONTH FROM {$column}) AS INTEGER)",
            ],
            default => [
                "CAST(strftime('%Y', {$column}) AS INTEGER)",
                "CAST(strftime('%m', {$column}) AS INTEGER)",
            ],
        };
    }
}
