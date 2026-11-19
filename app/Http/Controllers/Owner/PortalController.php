<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Admin\OwnerStatementController;
use App\Http\Controllers\Controller;
use App\Models\Owner;
use App\Models\OwnerStatement;
use App\Models\Reservation;
use App\Services\Finance\OwnerFinanceQueryService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * The read-only owner portal: an owner sees only their own properties'
 * occupancy, upcoming stays (without guest details) and approved statements.
 */
class PortalController extends Controller
{
    public function __construct(
        private OwnerFinanceQueryService $financeService,
    ) {}

    public function dashboard(Request $request)
    {
        $owner = $this->owner($request);
        $period = $request->query('month');
        $selectedPropertyId = $request->filled('property_id') ? $request->integer('property_id') : null;

        // If property_id is specified, ensure it belongs to owner to prevent cross-owner snooping (AE5)
        if ($selectedPropertyId !== null && ! $owner->units()->where('id', $selectedPropertyId)->exists()) {
            abort(404);
        }

        $finance = $this->financeService->queryDashboard($owner, $period, $selectedPropertyId);

        $properties = $owner->properties()->with(['site', 'primaryImage'])->orderBy('title')->get();
        if ($selectedPropertyId !== null) {
            $properties = $properties->where('id', $selectedPropertyId);
        }

        $monthStart = $period ? CarbonImmutable::parse($period.'-01')->startOfMonth() : CarbonImmutable::now()->startOfMonth();
        $monthEnd = $monthStart->endOfMonth();
        $daysInMonth = $monthStart->daysInMonth;

        $stays = Reservation::whereIn('property_id', $properties->pluck('id'))
            ->whereIn('booking_status', ['confirmed', 'checked_in', 'checked_out'])
            ->whereDate('check_in', '<=', $monthEnd)
            ->whereDate('check_out', '>', $monthStart)
            ->get();

        // Nights booked this month per property (stays that cross month ends count only their nights in it).
        $nights = $stays->groupBy('property_id')->map(fn ($rows) => $rows->sum(function ($r) use ($monthStart, $monthEnd) {
            $from = CarbonImmutable::parse($r->check_in)->max($monthStart);
            $to = CarbonImmutable::parse($r->check_out)->min($monthEnd->addDay()->startOfDay());

            return max(0, (int) $from->diffInDays($to));
        }));

        $upcoming = Reservation::whereIn('property_id', $properties->pluck('id'))
            ->whereIn('booking_status', ['confirmed', 'checked_in'])
            ->whereDate('check_out', '>=', today())
            ->with('property')
            ->orderBy('check_in')
            ->limit(15)
            ->get();

        $latest = $owner->statements()->visibleToOwner()->first();

        return view('owner.dashboard', compact(
            'owner', 'properties', 'nights', 'daysInMonth', 'upcoming', 'latest',
            'monthStart', 'finance', 'selectedPropertyId'
        ));
    }

    public function statements(Request $request)
    {
        $owner = $this->owner($request);
        $statements = $owner->statements()->visibleToOwner()->paginate(24);

        return view('owner.statements.index', compact('owner', 'statements'));
    }

    public function statement(Request $request, OwnerStatement $statement)
    {
        $this->authorizeStatement($request, $statement);
        $statement->load('lines.property');

        return view('owner.statements.show', compact('statement'));
    }

    public function statementPdf(Request $request, OwnerStatement $statement)
    {
        $this->authorizeStatement($request, $statement);

        return OwnerStatementController::download($statement);
    }

    private function owner(Request $request): Owner
    {
        $owner = Owner::where('user_id', $request->user()->id)->where('is_active', true)->first();
        abort_unless($owner, 403, 'Your login is not linked to an active owner account.');

        return $owner;
    }

    private function authorizeStatement(Request $request, OwnerStatement $statement): void
    {
        $owner = $this->owner($request);
        abort_unless($statement->owner_id === $owner->id && $statement->status !== 'draft', 404);
    }
}
