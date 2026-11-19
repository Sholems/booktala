<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FinanceTransaction;
use App\Models\Owner;
use App\Models\Site;
use App\Models\Unit;
use App\Services\Finance\GeneralLedgerExportService;
use App\Support\PropertyAccess;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FinanceLedgerController extends Controller
{
    public function __construct(
        protected GeneralLedgerExportService $ledgerService
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();

        // Default to current month if no dates specified
        $startDate = $request->filled('start_date')
            ? $request->string('start_date')->toString()
            : CarbonImmutable::now()->startOfMonth()->toDateString();

        $endDate = $request->filled('end_date')
            ? $request->string('end_date')->toString()
            : CarbonImmutable::now()->endOfMonth()->toDateString();

        $filters = [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'site_id' => $request->filled('site_id') ? $request->integer('site_id') : null,
            'unit_id' => $request->filled('unit_id') ? $request->integer('unit_id') : null,
            'owner_id' => $request->filled('owner_id') ? $request->integer('owner_id') : null,
            'account' => $request->filled('account') ? $request->string('account')->toString() : null,
            'event_type' => $request->filled('event_type') ? $request->string('event_type')->toString() : null,
            'search' => $request->filled('search') ? $request->string('search')->toString() : null,
        ];

        // Site scoping
        if ($filters['site_id'] !== null && ! PropertyAccess::seesAll($user)) {
            $allowedSiteIds = PropertyAccess::activeSiteIds($user)->pluck('site_id')->all();
            if (! in_array($filters['site_id'], $allowedSiteIds)) {
                abort(404);
            }
        }

        $summary = $this->ledgerService->calculateSummary($filters, $user);
        $transactions = $this->ledgerService->queryLedger($filters, $user, 25);

        $sites = PropertyAccess::seesAll($user)
            ? Site::orderBy('name')->get()
            : Site::whereIn('id', PropertyAccess::activeSiteIds($user))->orderBy('name')->get();

        $units = PropertyAccess::seesAll($user)
            ? Unit::orderBy('title')->get()
            : Unit::whereIn('id', PropertyAccess::visiblePropertyIds($user))->orderBy('title')->get();

        $owners = Owner::orderBy('name')->get();
        $accountChart = GeneralLedgerExportService::ACCOUNT_CHART;
        $eventTypes = [
            'collection' => 'Payment Collection',
            'stay_completion' => 'Stay Completion (Revenue Realization)',
            'caution_refund' => 'Caution Refund',
            'caution_forfeiture' => 'Caution Forfeiture',
            'expense' => 'Property Expense',
            'payout' => 'Owner Payout',
            'reversal' => 'Transaction Reversal',
        ];

        return view('admin.finance.ledger.index', compact(
            'transactions',
            'summary',
            'filters',
            'sites',
            'units',
            'owners',
            'accountChart',
            'eventTypes'
        ));
    }

    public function export(Request $request): Response
    {
        $user = $request->user();

        $filters = [
            'start_date' => $request->string('start_date')->toString() ?: null,
            'end_date' => $request->string('end_date')->toString() ?: null,
            'site_id' => $request->filled('site_id') ? $request->integer('site_id') : null,
            'unit_id' => $request->filled('unit_id') ? $request->integer('unit_id') : null,
            'owner_id' => $request->filled('owner_id') ? $request->integer('owner_id') : null,
            'account' => $request->filled('account') ? $request->string('account')->toString() : null,
            'event_type' => $request->filled('event_type') ? $request->string('event_type')->toString() : null,
            'search' => $request->filled('search') ? $request->string('search')->toString() : null,
        ];

        if ($filters['site_id'] !== null && ! PropertyAccess::seesAll($user)) {
            $allowedSiteIds = PropertyAccess::activeSiteIds($user)->pluck('site_id')->all();
            if (! in_array($filters['site_id'], $allowedSiteIds)) {
                abort(404);
            }
        }

        $format = $request->string('format', 'standard')->toString();
        $dateSuffix = now()->format('Ymd_His');

        switch ($format) {
            case 'quickbooks':
                $csv = $this->ledgerService->exportQuickBooks($filters, $user);
                $filename = "quickbooks_gl_journal_{$dateSuffix}.csv";
                break;
            case 'xero':
                $csv = $this->ledgerService->exportXero($filters, $user);
                $filename = "xero_gl_journal_{$dateSuffix}.csv";
                break;
            case 'standard':
            default:
                $csv = $this->ledgerService->exportStandardCsv($filters, $user);
                $filename = "statutory_general_ledger_{$dateSuffix}.csv";
                break;
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    public function show(FinanceTransaction $transaction, Request $request)
    {
        $user = $request->user();

        if (! PropertyAccess::seesAll($user)) {
            $visiblePropertyIds = PropertyAccess::visiblePropertyIds($user)->pluck('properties.id')->all();
            $hasAccess = $transaction->entries()->where(function ($q) use ($visiblePropertyIds) {
                $q->whereIn('unit_id', $visiblePropertyIds)
                    ->orWhereNull('unit_id');
            })->exists();

            abort_unless($hasAccess, 404);
        }

        $transaction->load([
            'entries.site',
            'entries.unit',
            'entries.owner',
            'entries.reservation',
            'entries.managementAgreement',
            'reversalOf',
            'reversal',
            'creator',
        ]);

        return response()->json([
            'transaction' => $transaction,
            'account_chart' => GeneralLedgerExportService::ACCOUNT_CHART,
        ]);
    }
}
