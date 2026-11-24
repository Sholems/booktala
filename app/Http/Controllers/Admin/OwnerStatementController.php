<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OwnerStatementGenerateRequest;
use App\Http\Requests\Admin\OwnerStatementMarkPaidRequest;
use App\Models\Expense;
use App\Models\Owner;
use App\Models\OwnerStatement;
use App\Services\OwnerStatementService;
use App\Support\PropertyAccess;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Monthly owner statements: generate drafts, review, approve (owner can then
 * see it), and record the payout.
 */
class OwnerStatementController extends Controller
{
    public function __construct(private OwnerStatementService $statements) {}

    public function index(Request $request)
    {
        $user = $request->user();
        // Default to last month: statements are usually prepared after the month closes.
        $month = CarbonImmutable::parse($request->query('month', now()->subMonthNoOverflow()->format('Y-m')).'-01');

        $statements = OwnerStatement::with('owner')
            ->whereDate('period_start', $month)
            ->when(! PropertyAccess::seesAll($user), fn ($query) => $query
                ->whereHas('owner.properties')
                ->whereDoesntHave('owner.properties', fn ($properties) => $properties
                    ->whereNotIn('properties.id', PropertyAccess::visiblePropertyIds($user))))
            ->get()
            ->sortBy('owner.name');

        $ownersWithout = Owner::where('is_active', true)
            ->whereHas('properties')
            ->when(! PropertyAccess::seesAll($user), fn ($query) => $query
                ->whereDoesntHave('properties', fn ($properties) => $properties
                    ->whereNotIn('properties.id', PropertyAccess::visiblePropertyIds($user))))
            ->whereNotIn('id', $statements->pluck('owner_id'))
            ->orderBy('name')
            ->get();

        return view('admin.statements.index', compact('statements', 'ownersWithout', 'month'));
    }

    public function generate(OwnerStatementGenerateRequest $request)
    {
        $data = $request->validated();
        $month = CarbonImmutable::parse($data['month'].'-01');
        abort_if($month->isAfter(now()->startOfMonth()), 422, 'Statements cannot be prepared for future months.');

        if (! empty($data['owner_id'])) {
            $owner = Owner::findOrFail($data['owner_id']);
            $this->authorizeOwner($owner);
            $statement = $this->statements->generate($owner, $month);

            return redirect()->route('admin.statements.show', $statement)->with('success', 'Statement prepared');
        }

        abort_unless(PropertyAccess::seesAll($request->user()), 404);
        $count = $this->statements->generateForAll($month);

        return redirect()->route('admin.statements.index', ['month' => $data['month']])
            ->with('success', $count.' draft statement(s) prepared for '.$month->format('F Y'));
    }

    public function show(OwnerStatement $statement)
    {
        $this->authorizeStatement($statement);
        $statement->load(['owner', 'lines.property', 'approvedBy']);

        return view('admin.statements.show', compact('statement'));
    }

    public function approve(Request $request, OwnerStatement $statement)
    {
        $this->authorizeStatement($statement);
        $this->statements->approve($statement, $request->user());

        return back()->with('success', 'Statement approved'.($statement->owner->user_id ? ' and visible in the owner portal' : ''));
    }

    public function markPaid(OwnerStatementMarkPaidRequest $request, OwnerStatement $statement)
    {
        $this->authorizeStatement($statement);
        $data = $request->validated();
        $this->statements->markPaid($statement, $data['payment_reference'] ?? null);

        return back()->with('success', 'Payout recorded');
    }

    public function destroy(OwnerStatement $statement)
    {
        $this->authorizeStatement($statement);
        if (! $statement->isDraft()) {
            return back()->with('error', 'Only draft statements can be deleted.');
        }
        $month = $statement->period_start->format('Y-m');
        Expense::where('owner_statement_id', $statement->id)->update(['owner_statement_id' => null]);
        $statement->delete();

        return redirect()->route('admin.statements.index', ['month' => $month])->with('success', 'Draft statement deleted');
    }

    public function pdf(OwnerStatement $statement)
    {
        $this->authorizeStatement($statement);

        return self::download($statement);
    }

    private function authorizeStatement(OwnerStatement $statement): void
    {
        $this->authorizeOwner($statement->owner_id);
    }

    private function authorizeOwner(Owner|int $owner): void
    {
        abort_unless(PropertyAccess::canAccessOwner(auth()->user(), $owner), 404);
    }

    /** Shared with the owner portal. */
    public static function download(OwnerStatement $statement)
    {
        $statement->load(['owner', 'lines.property']);
        $filename = 'statement-'.Str::slug($statement->owner->name).'-'.$statement->period_start->format('Y-m').'.pdf';

        return Pdf::loadView('statements.pdf', compact('statement'))->download($filename);
    }
}
