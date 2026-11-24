<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ExpenseStoreRequest;
use App\Models\Expense;
use App\Models\MaintenanceReport;
use App\Models\Property;
use App\Services\Finance\LedgerPostingService;
use App\Support\PrivateStorage;
use App\Support\PropertyAccess;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Property expenses: what was spent, on which property, and who bears it.
 */
class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $month = CarbonImmutable::parse($request->query('month', now()->format('Y-m')).'-01');

        $expensesQuery = Expense::with(['property.owner', 'statement', 'recordedBy']);
        PropertyAccess::scope($expensesQuery, $user);
        $expenses = $expensesQuery
            ->whereDate('spent_on', '>=', $month->startOfMonth())
            ->whereDate('spent_on', '<=', $month->endOfMonth())
            ->when($request->filled('property_id'), fn ($q) => $q->where('property_id', $request->integer('property_id')))
            ->latest('spent_on')
            ->paginate(50)
            ->withQueryString();

        $totalsQuery = Expense::query();
        PropertyAccess::scope($totalsQuery, $user);
        $totals = $totalsQuery->whereDate('spent_on', '>=', $month->startOfMonth())
            ->whereDate('spent_on', '<=', $month->endOfMonth())
            ->when($request->filled('property_id'), fn ($q) => $q->where('property_id', $request->integer('property_id')))
            ->selectRaw('charge_to, SUM(amount) as total')
            ->groupBy('charge_to')
            ->pluck('total', 'charge_to');

        return view('admin.expenses.index', [
            'expenses' => $expenses,
            'totals' => $totals,
            'month' => $month,
            'properties' => Property::query()->visibleTo($user)->with('owner')->orderBy('title')->get(),
            'repairs' => PropertyAccess::scope(MaintenanceReport::with('property'), $user)->latest()->limit(50)->get(),
        ]);
    }

    public function store(ExpenseStoreRequest $request)
    {
        $data = $request->validated();
        PropertyAccess::authorize($request->user(), $data['property_id']);

        if ($request->hasFile('receipt')) {
            $disk = PrivateStorage::disk();
            $data['receipt_path'] = $request->file('receipt')->store('receipts', $disk);
        }
        unset($data['receipt']);

        DB::transaction(function () use ($data, $request) {
            $expense = Expense::create($data + ['recorded_by' => $request->user()->id]);
            app(LedgerPostingService::class)->postExpense($expense);
        });

        return back()->with('success', 'Expense recorded');
    }

    public function receipt(Expense $expense)
    {
        PropertyAccess::authorize(auth()->user(), $expense->property_id);
        abort_unless($expense->receipt_path, 404);

        return PrivateStorage::response($expense->receipt_path);
    }

    public function destroy(Expense $expense)
    {
        PropertyAccess::authorize(auth()->user(), $expense->property_id);

        if ($expense->isLocked()) {
            return back()->with('error', 'This expense is on an approved owner statement and cannot be deleted.');
        }

        if ($expense->receipt_path) {
            Storage::disk('r2')->delete($expense->receipt_path);
            Storage::disk('local')->delete($expense->receipt_path);
        }
        $expense->delete();

        return back()->with('success', 'Expense deleted');
    }
}
