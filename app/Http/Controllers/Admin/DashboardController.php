<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceReport;
use App\Models\Message;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\Review;
use App\Models\Task;
use App\Services\OperationsOverview;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index()
    {
        $totalProperties = Property::count();
        $activeProperties = Property::active()->count();

        $totalBookings = Reservation::count();
        $confirmedBookings = Reservation::where('booking_status', 'confirmed')->count();
        $checkedInBookings = Reservation::where('booking_status', 'checked_in')->count();

        $totalRevenue = Reservation::paid()->sum(DB::raw('total_amount - caution_fee'));
        $thisMonthRevenue = Reservation::paid()
            ->whereMonth('created_at', now()->month)
            ->sum(DB::raw('total_amount - caution_fee'));
        $lastMonthRevenue = Reservation::paid()
            ->whereMonth('created_at', now()->subMonth()->month)
            ->sum(DB::raw('total_amount - caution_fee'));

        $occupancyRate = $totalProperties > 0 ? round(($checkedInBookings / $totalProperties) * 100) : 0;

        $thisMonthBookings = Reservation::whereMonth('created_at', now()->month)->count();
        $lastMonthBookings = Reservation::whereMonth('created_at', now()->subMonth()->month)->count();

        $monthExpression = $this->monthExpression('created_at');

        $revenueByMonth = Reservation::selectRaw("{$monthExpression} as month, SUM(total_amount - caution_fee) as total")
            ->paid()
            ->whereYear('created_at', now()->year)
            ->groupByRaw($monthExpression)
            ->pluck('total', 'month')
            ->toArray();

        $monthlyRevenue = [];
        for ($i = 1; $i <= 12; $i++) {
            $monthlyRevenue[$i] = (int) ($revenueByMonth[$i] ?? 0);
        }

        $topProperties = Property::withCount(['reservations' => function ($q) {
            $q->where('payment_status', 'paid');
        }])
            ->orderByDesc('reservations_count')
            ->take(5)
            ->get();

        $stats = [
            'total_properties' => $totalProperties,
            'active_properties' => $activeProperties,
            'total_bookings' => $totalBookings,
            'confirmed_bookings' => $confirmedBookings,
            'today_checkins' => Reservation::whereDate('check_in', today())->where('booking_status', 'confirmed')->count(),
            'today_checkouts' => Reservation::whereDate('check_out', today())->where('booking_status', 'checked_in')->count(),
            'pending_tasks' => Task::where('status', 'pending')->count(),
            'total_revenue' => $totalRevenue,
            'this_month_revenue' => $thisMonthRevenue,
            'last_month_revenue' => $lastMonthRevenue,
            'revenue_growth' => $lastMonthRevenue > 0 ? round((($thisMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100) : 0,
            'occupancy_rate' => $occupancyRate,
            'this_month_bookings' => $thisMonthBookings,
            'last_month_bookings' => $lastMonthBookings,
            'bookings_growth' => $lastMonthBookings > 0 ? round((($thisMonthBookings - $lastMonthBookings) / $lastMonthBookings) * 100) : 0,
            'monthly_revenue' => $monthlyRevenue,
        ];

        $recentBookings = Reservation::with(['user', 'property'])->latest()->take(10)->get();
        $upcomingCheckins = Reservation::whereDate('check_in', '>=', today())->whereDate('check_in', '<=', now()->addDays(7))->where('booking_status', 'confirmed')->with(['user', 'property'])->get();

        // Generate alerts
        $alerts = [];
        $pendingTasks = Task::where('status', 'pending')->count();
        $pendingMaintenance = MaintenanceReport::where('status', 'pending')->count();
        $unreadMessages = $this->countUnreadMessages();
        $pendingReviews = $this->countPendingReviews();

        if ($pendingTasks > 0) {
            $alerts[] = "You have {$pendingTasks} pending task(s) awaiting action";
        }
        if ($pendingMaintenance > 0) {
            $alerts[] = "{$pendingMaintenance} maintenance report(s) need attention";
        }
        if ($unreadMessages > 0) {
            $alerts[] = "You have {$unreadMessages} unread message(s)";
        }
        if ($pendingReviews > 0) {
            $alerts[] = "{$pendingReviews} review(s) awaiting moderation";
        }

        $overview = app(OperationsOverview::class);
        $queues = $overview->adminQueues();
        $activity = $overview->recentActivity();

        return view('admin.dashboard', compact('stats', 'recentBookings', 'upcomingCheckins', 'topProperties', 'alerts', 'queues', 'activity'));
    }

    public function activityLogs()
    {
        return view('admin.activity-logs');
    }

    private function monthExpression(string $column): string
    {
        return match (DB::getDriverName()) {
            'mysql', 'mariadb' => "MONTH({$column})",
            'pgsql' => "CAST(EXTRACT(MONTH FROM {$column}) AS INTEGER)",
            default => "CAST(strftime('%m', {$column}) AS INTEGER)",
        };
    }

    private function countUnreadMessages(): int
    {
        if (Schema::hasColumn('messages', 'is_read')) {
            return Message::where('is_read', false)->count();
        }

        if (Schema::hasColumn('messages', 'read')) {
            return Message::where('read', false)->count();
        }

        return 0;
    }

    private function countPendingReviews(): int
    {
        if (Schema::hasColumn('reviews', 'status')) {
            return Review::where('status', 'pending')->count();
        }

        if (Schema::hasColumn('reviews', 'is_published')) {
            return Review::where('is_published', false)->count();
        }

        return 0;
    }
}
