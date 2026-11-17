<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\MaintenanceReport;
use App\Models\Property;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Property expenses: what was spent, on which property, and who bears it.
 */
class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $month = CarbonImmutable::parse($request->query('month', now()->format('Y-m')) . '-01');

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

    public function store(Request $request)
    {
        $data = $request->validate([
            'property_id' => 'required|exists:properties,id',
            'category' => ['required', Rule::in(array_keys(Expense::CATEGORIES))],
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01|max:100000000',
            'spent_on' => 'required|date|before_or_equal:today',
            'charge_to' => 'required|in:owner,company',
            'maintenance_report_id' => 'nullable|exists:maintenance_reports,id',
            'receipt' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        if ($request->hasFile('receipt')) {
            // Receipts are private: stored outside the public folder, downloaded through the app.
            $data['receipt_path'] = $request->file('receipt')->store('receipts', 'local');
        }
        unset($data['receipt']);

        Expense::create($data + ['recorded_by' => $request->user()->id]);

        return back()->with('success', 'Expense recorded');
    }

    public function receipt(Expense $expense)
    {
        abort_unless($expense->receipt_path && Storage::disk('local')->exists($expense->receipt_path), 404);

        return Storage::disk('local')->download($expense->receipt_path);
    }

    public function destroy(Expense $expense)
    {
        if ($expense->isLocked()) {
            return back()->with('error', 'This expense is on an approved owner statement and cannot be deleted.');
        }

        if ($expense->receipt_path) {
            Storage::disk('local')->delete($expense->receipt_path);
        }
        $expense->delete();

        return back()->with('success', 'Expense deleted');
    }
}
