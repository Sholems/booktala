<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReportPeriodRequest;
use App\Services\SiteReport;
use Carbon\CarbonImmutable;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Head office comparison of all sites over a period.
 */
class SiteReportController extends Controller
{
    public function index(ReportPeriodRequest $request, SiteReport $report)
    {
        [$from, $to] = $this->period($request);

        return view('admin.reports.sites', [
            'rows' => $report->rows($from, $to),
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function export(ReportPeriodRequest $request, SiteReport $report): StreamedResponse
    {
        [$from, $to] = $this->period($request);
        $rows = $report->rows($from, $to);

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, array_merge(['Site', 'City'], array_values(SiteReport::COLUMNS)));
            foreach ($rows as $row) {
                fputcsv($out, array_merge(
                    [$row['site']->name, $row['site']->city],
                    array_map(fn ($key) => $row[$key] ?? '', array_keys(SiteReport::COLUMNS))
                ));
            }
            fclose($out);
        }, 'site-report-'.$from->format('Ymd').'-'.$to->format('Ymd').'.csv', ['Content-Type' => 'text/csv']);
    }

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable} */
    private function period(ReportPeriodRequest $request): array
    {
        return [
            CarbonImmutable::parse($request->query('from', today()->startOfMonth()->toDateString())),
            CarbonImmutable::parse($request->query('to', today()->toDateString())),
        ];
    }
}
