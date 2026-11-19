<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Services\Finance\TaxComplianceReportService;
use App\Support\PropertyAccess;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TaxComplianceController extends Controller
{
    public function __construct(
        protected TaxComplianceReportService $taxService
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();

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
        ];

        if ($filters['site_id'] !== null && ! PropertyAccess::seesAll($user)) {
            $allowedSiteIds = PropertyAccess::activeSiteIds($user)->pluck('site_id')->all();
            if (! in_array($filters['site_id'], $allowedSiteIds)) {
                abort(404);
            }
        }

        $report = $this->taxService->generateReport($filters, $user);

        $sites = PropertyAccess::seesAll($user)
            ? Site::orderBy('name')->get()
            : Site::whereIn('id', PropertyAccess::activeSiteIds($user))->orderBy('name')->get();

        return view('admin.finance.tax.index', compact('report', 'filters', 'sites'));
    }

    public function export(Request $request): Response
    {
        $user = $request->user();

        $filters = [
            'start_date' => $request->string('start_date')->toString() ?: null,
            'end_date' => $request->string('end_date')->toString() ?: null,
            'site_id' => $request->filled('site_id') ? $request->integer('site_id') : null,
        ];

        if ($filters['site_id'] !== null && ! PropertyAccess::seesAll($user)) {
            $allowedSiteIds = PropertyAccess::activeSiteIds($user)->pluck('site_id')->all();
            if (! in_array($filters['site_id'], $allowedSiteIds)) {
                abort(404);
            }
        }

        $csv = $this->taxService->exportTaxReportCsv($filters, $user);
        $dateSuffix = now()->format('Ymd_His');
        $filename = "tax_compliance_report_{$dateSuffix}.csv";

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }
}
