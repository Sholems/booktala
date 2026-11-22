<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ExpenseStoreRequest;
use App\Models\Expense;
use App\Models\MaintenanceReport;
use App\Models\Property;
use App\Services\Finance\LedgerPostingService;
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
        $month = CarbonImmutable::parse($request->query('month', now()->format('Y-m')).'-01');

        $expenses = Expense::with(['property.owner', 'statement', 'recordedBy'])
            ->whereDate('spent_on', '>=', $month->startOfMonth())
            ->whereDate('spent_on', '<=', $month->endOfMonth())
            ->when($request->filled('property_id'), fn ($q) => $q->where('property_id', $request->integer('property_id')))
            ->latest('spent_on')
            ->paginate(50)
            ->withQueryString();

        $totals = Expense::whereDate('spent_on', '>=', $month->startOfMonth())
            ->whereDate('spent_on', '<=', $month->endOfMonth())
            ->when($request->filled('property_id'), fn ($q) => $q->where('property_id', $request->integer('property_id')))
            ->selectRaw('charge_to, SUM(amount) as total')
            ->groupBy('charge_to')
            ->pluck('total', 'charge_to');

        return view('admin.expenses.index', [
            'expenses' => $expenses,
            'totals' => $totals,
            'month' => $month,
            'properties' => Property::with('owner')->orderBy('title')->get(),
            'repairs' => MaintenanceReport::with('property')->latest()->limit(50)->get(),
        ]);
    }

    public function store(ExpenseStoreRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('receipt')) {
            // Receipts are private: stored on default/r2 storage, downloaded through the app.
            $disk = config('filesystems.default') === 'r2' ? 'r2' : 'local';
            $data['receipt_path'] = $request->file('receipt')->store('receipts', $disk);
        }
        unset($data['receipt']);

        PropertyAccess::authorize($request->user(), $data['property_id']);

        DB::transaction(function () use ($data, $request) {
            $expense = Expense::create($data + ['recorded_by' => $request->user()->id]);
            app(LedgerPostingService::class)->postExpense($expense);
        });

        return back()->with('success', 'Expense recorded');
    }

    public function receipt(Expense $expense)
    {
        $disk = config('filesystems.default') === 'r2' ? Storage::disk('r2') : Storage::disk('local');
        if (! ($expense->receipt_path && $disk->exists($expense->receipt_path))) {
            $fallback = Storage::disk('local');
            abort_unless($expense->receipt_path && $fallback->exists($expense->receipt_path), 404);
            $disk = $fallback;
        }

        return $disk->download($expense->receipt_path);
    }

    public function destroy(Expense $expense)
    {
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
