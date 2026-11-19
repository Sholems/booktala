<?php

namespace App\Services\Finance;

use App\Models\FinanceEntry;
use App\Models\User;
use App\Support\PropertyAccess;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class TaxComplianceReportService
{
    public const VAT_RATE = 0.075; // 7.5% Nigerian VAT (FIRS)

    public const RENTAL_WHT_RATE = 0.10; // 10% WHT on Rental Income (PITA/CITA)

    public const COMMISSION_WHT_CORPORATE = 0.10; // 10% WHT on Management Fees for Companies (FIRS)

    public const COMMISSION_WHT_INDIVIDUAL = 0.05; // 5% WHT on Management Fees for Individuals (State IRS)

    /**
     * Compute full tax & WHT compliance report.
     */
    public function generateReport(array $filters, ?User $user = null): array
    {
        $start = ! empty($filters['start_date'])
            ? CarbonImmutable::parse($filters['start_date'])->startOfDay()
            : CarbonImmutable::now()->startOfMonth();

        $end = ! empty($filters['end_date'])
            ? CarbonImmutable::parse($filters['end_date'])->endOfDay()
            : CarbonImmutable::now()->endOfMonth();

        $siteId = ! empty($filters['site_id']) ? (int) $filters['site_id'] : null;

        // Query entries in the date range with full dimensional relations
        $entriesQuery = FinanceEntry::query()
            ->with(['site', 'unit', 'owner', 'transaction'])
            ->whereBetween('occurred_on', [$start->toDateString(), $end->toDateString()]);

        if ($siteId !== null) {
            $entriesQuery->where('site_id', $siteId);
        }

        if ($user !== null && ! PropertyAccess::seesAll($user)) {
            $entriesQuery->whereIn('unit_id', PropertyAccess::visiblePropertyIds($user));
        }

        $entries = $entriesQuery->get();

        // 1. VAT Compliance (Output VAT, Input VAT if any, Net VAT Payable to FIRS)
        $vatData = $this->calculateVat($entries);

        // 2. Commission WHT Compliance (BookTala Management Commission Income)
        $commissionData = $this->calculateCommissionWht($entries);

        // 3. Landlord Rental WHT Compliance (Disbursements to 3rd party owners, PITA/CITA)
        $landlordWhtData = $this->calculateLandlordRentalWht($entries);

        // 4. State vs Federal Tax Authority Summary (FIRS, LIRS, FCT-IRS, etc.)
        $jurisdictionSummary = $this->calculateJurisdictionSummary($vatData, $commissionData, $landlordWhtData);

        return [
            'period' => [
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'label' => $start->format('M j, Y').' - '.$end->format('M j, Y'),
            ],
            'vat' => $vatData,
            'commission' => $commissionData,
            'landlord_wht' => $landlordWhtData,
            'jurisdictions' => $jurisdictionSummary,
            'totals' => [
                'total_firs_liability' => $jurisdictionSummary['FIRS']['total_liability'] ?? 0.0,
                'total_state_liability' => $jurisdictionSummary['state_total'] ?? 0.0,
                'grand_total_tax_liability' => ($jurisdictionSummary['FIRS']['total_liability'] ?? 0.0) + ($jurisdictionSummary['state_total'] ?? 0.0),
            ],
        ];
    }

    /**
     * Compute VAT details from ledger entries.
     */
    protected function calculateVat(Collection $entries): array
    {
        $accommodationTurnoverMinor = 0;
        $vatPayableMinor = 0;

        foreach ($entries as $entry) {
            // Explicit VAT collected entries
            if ($entry->account === FinanceEntry::ACCOUNT_VAT_PAYABLE) {
                // VAT liability credits are negative amount_minor, debits are settlements/reversals
                $vatPayableMinor += abs($entry->amount_minor);
            }

            // Realized accommodation turnover from stay completions
            if (
                $entry->account === FinanceEntry::ACCOUNT_DEFERRED_ACCOMMODATION &&
                $entry->amount_minor > 0 &&
                $entry->transaction?->event_type === 'stay_completion'
            ) {
                $accommodationTurnoverMinor += $entry->amount_minor;
            } elseif (
                $entry->account === FinanceEntry::ACCOUNT_ACCOMMODATION_REVENUE &&
                $entry->amount_minor < 0
            ) {
                $accommodationTurnoverMinor += abs($entry->amount_minor);
            }
        }

        $taxableRevenue = $accommodationTurnoverMinor / 100;
        $vatCollected = $vatPayableMinor / 100;

        // If no explicit VAT entries were posted (e.g. standard accommodation billing), compute statutory 7.5%
        if ($vatCollected == 0 && $taxableRevenue > 0) {
            $vatCollected = round($taxableRevenue * self::VAT_RATE, 2);
        }

        return [
            'taxable_turnover' => $taxableRevenue,
            'rate_percent' => self::VAT_RATE * 100,
            'output_vat' => $vatCollected,
            'allowable_input_vat' => 0.0,
            'net_vat_payable' => $vatCollected,
            'tax_authority' => 'FIRS (Federal Inland Revenue Service)',
        ];
    }

    /**
     * Compute Commission WHT on BookTala's management fees.
     */
    protected function calculateCommissionWht(Collection $entries): array
    {
        $commissionMinor = 0;
        $commissionByOwner = [];

        foreach ($entries as $entry) {
            if ($entry->account === FinanceEntry::ACCOUNT_COMMISSION_INCOME && $entry->amount_minor < 0) {
                $commissionMinor += abs($entry->amount_minor);
                $ownerId = $entry->owner_id ?? 0;
                $ownerName = $entry->owner?->name ?? 'G-Briel Ltd / Internal';
                $ownerType = $entry->owner?->type ?? 'company';
                $taxId = $entry->owner?->tax_id ?? 'N/A';

                if (! isset($commissionByOwner[$ownerId])) {
                    $commissionByOwner[$ownerId] = [
                        'owner_name' => $ownerName,
                        'owner_type' => $ownerType,
                        'tax_id' => $taxId,
                        'commission_minor' => 0,
                    ];
                }
                $commissionByOwner[$ownerId]['commission_minor'] += abs($entry->amount_minor);
            }
        }

        $totalCommission = $commissionMinor / 100;
        $schedule = [];
        $corporateWhtTotal = 0;
        $individualWhtTotal = 0;

        foreach ($commissionByOwner as $ownerId => $data) {
            $commAmount = $data['commission_minor'] / 100;
            $rate = ($data['owner_type'] === 'company') ? self::COMMISSION_WHT_CORPORATE : self::COMMISSION_WHT_INDIVIDUAL;
            $whtAmount = round($commAmount * $rate, 2);
            $auth = ($data['owner_type'] === 'company') ? 'FIRS' : 'State IRS';

            if ($data['owner_type'] === 'company') {
                $corporateWhtTotal += $whtAmount;
            } else {
                $individualWhtTotal += $whtAmount;
            }

            $schedule[] = [
                'owner_name' => $data['owner_name'],
                'owner_type' => ucfirst($data['owner_type']),
                'tax_id' => $data['tax_id'],
                'commission_amount' => $commAmount,
                'wht_rate_percent' => $rate * 100,
                'wht_amount' => $whtAmount,
                'tax_authority' => $auth,
            ];
        }

        return [
            'total_commission' => $totalCommission,
            'corporate_wht' => $corporateWhtTotal,
            'individual_wht' => $individualWhtTotal,
            'total_wht' => $corporateWhtTotal + $individualWhtTotal,
            'schedule' => $schedule,
        ];
    }

    /**
     * Compute Landlord Rental WHT (10% standard statutory WHT under PITA/CITA).
     */
    protected function calculateLandlordRentalWht(Collection $entries): array
    {
        $landlordTotals = [];

        foreach ($entries as $entry) {
            // Identify accommodation revenue realized on stay completion for 3rd party owners
            if (
                $entry->account === FinanceEntry::ACCOUNT_DEFERRED_ACCOMMODATION &&
                $entry->amount_minor > 0 &&
                $entry->transaction?->event_type === 'stay_completion' &&
                $entry->owner &&
                ! $entry->owner->is_company_owned
            ) {
                $owner = $entry->owner;
                $ownerId = $owner->id;
                $stateName = $entry->site?->state ?? 'Lagos';

                if (! isset($landlordTotals[$ownerId])) {
                    $landlordTotals[$ownerId] = [
                        'owner_id' => $owner->id,
                        'owner_name' => $owner->name,
                        'owner_type' => $owner->type ?? 'individual',
                        'tax_id' => $owner->tax_id ?? 'N/A',
                        'state' => $stateName,
                        'gross_revenue_minor' => 0,
                        'units' => [],
                    ];
                }

                $landlordTotals[$ownerId]['gross_revenue_minor'] += $entry->amount_minor;
                if ($entry->unit && ! in_array($entry->unit->title, $landlordTotals[$ownerId]['units'])) {
                    $landlordTotals[$ownerId]['units'][] = $entry->unit->title;
                }
            }
        }

        $schedule = [];
        $totalGrossRent = 0;
        $totalRentalWht = 0;

        foreach ($landlordTotals as $ownerId => $data) {
            $grossRent = $data['gross_revenue_minor'] / 100;
            $whtAmount = round($grossRent * self::RENTAL_WHT_RATE, 2);
            $netDisbursement = round($grossRent - $whtAmount, 2);

            $auth = $this->determineAuthority($data['owner_type'], $data['state']);

            $totalGrossRent += $grossRent;
            $totalRentalWht += $whtAmount;

            $schedule[] = [
                'owner_id' => $data['owner_id'],
                'owner_name' => $data['owner_name'],
                'owner_type' => ucfirst($data['owner_type']),
                'tax_id' => $data['tax_id'],
                'state' => $data['state'],
                'tax_authority' => $auth,
                'properties' => implode(', ', $data['units']),
                'gross_rent' => $grossRent,
                'wht_rate_percent' => self::RENTAL_WHT_RATE * 100,
                'wht_deducted' => $whtAmount,
                'net_payable_to_owner' => $netDisbursement,
            ];
        }

        return [
            'total_gross_rent' => $totalGrossRent,
            'total_rental_wht' => $totalRentalWht,
            'wht_rate_percent' => self::RENTAL_WHT_RATE * 100,
            'schedule' => $schedule,
        ];
    }

    /**
     * Group tax liabilities by Tax Authority (FIRS vs State IRS).
     */
    protected function calculateJurisdictionSummary(array $vat, array $commission, array $landlordWht): array
    {
        $jurisdictions = [
            'FIRS' => [
                'name' => 'Federal Inland Revenue Service (FIRS)',
                'vat_liability' => $vat['net_vat_payable'],
                'corporate_rental_wht' => 0.0,
                'corporate_commission_wht' => $commission['corporate_wht'],
                'total_liability' => $vat['net_vat_payable'] + $commission['corporate_wht'],
            ],
            'states' => [],
            'state_total' => 0.0,
        ];

        foreach ($landlordWht['schedule'] as $item) {
            $auth = $item['tax_authority'];
            $wht = $item['wht_deducted'];

            if ($item['owner_type'] === 'Company') {
                $jurisdictions['FIRS']['corporate_rental_wht'] += $wht;
                $jurisdictions['FIRS']['total_liability'] += $wht;
            } else {
                if (! isset($jurisdictions['states'][$auth])) {
                    $jurisdictions['states'][$auth] = [
                        'authority' => $auth,
                        'state' => $item['state'],
                        'landlord_rental_wht' => 0.0,
                        'individual_commission_wht' => 0.0,
                        'total_liability' => 0.0,
                    ];
                }
                $jurisdictions['states'][$auth]['landlord_rental_wht'] += $wht;
                $jurisdictions['states'][$auth]['total_liability'] += $wht;
                $jurisdictions['state_total'] += $wht;
            }
        }

        if ($commission['individual_wht'] > 0) {
            $defaultState = 'LIRS (Lagos State Internal Revenue Service)';
            if (! isset($jurisdictions['states'][$defaultState])) {
                $jurisdictions['states'][$defaultState] = [
                    'authority' => $defaultState,
                    'state' => 'Lagos',
                    'landlord_rental_wht' => 0.0,
                    'individual_commission_wht' => 0.0,
                    'total_liability' => 0.0,
                ];
            }
            $jurisdictions['states'][$defaultState]['individual_commission_wht'] += $commission['individual_wht'];
            $jurisdictions['states'][$defaultState]['total_liability'] += $commission['individual_wht'];
            $jurisdictions['state_total'] += $commission['individual_wht'];
        }

        return $jurisdictions;
    }

    /**
     * Map entity type and Nigerian state to the appropriate revenue service.
     */
    protected function determineAuthority(string $entityType, string $state): string
    {
        if (strtolower($entityType) === 'company') {
            return 'FIRS (Federal Inland Revenue Service)';
        }

        $s = strtolower(trim($state));
        if ($s === 'lagos') {
            return 'LIRS (Lagos State Internal Revenue Service)';
        }
        if ($s === 'abuja' || $s === 'fct' || str_contains($s, 'federal capital')) {
            return 'FCT-IRS (Federal Capital Territory Internal Revenue Service)';
        }
        if ($s === 'rivers') {
            return 'RIRS (Rivers State Internal Revenue Service)';
        }
        if ($s === 'oyo') {
            return 'OYSIRS (Oyo State Internal Revenue Service)';
        }
        if ($s === 'ogun') {
            return 'OGIRS (Ogun State Internal Revenue Service)';
        }

        return ucfirst($state).' State Internal Revenue Service';
    }

    /**
     * Export complete tax and WHT compliance schedule as CSV.
     */
    public function exportTaxReportCsv(array $filters, ?User $user = null): string
    {
        $report = $this->generateReport($filters, $user);

        $rows = [];
        $rows[] = ['BOOKTALA STATUTORY TAX & WITHHOLDING TAX (WHT) COMPLIANCE REPORT'];
        $rows[] = ['Period', $report['period']['label']];
        $rows[] = ['Generated At', now()->format('Y-m-d H:i:s')];
        $rows[] = [];

        // Section 1: Executive Tax Summary
        $rows[] = ['--- SECTION 1: STATUTORY TAX LIABILITIES SUMMARY ---'];
        $rows[] = ['Tax Category', 'Jurisdiction / Authority', 'Taxable Base (NGN)', 'Statutory Rate', 'Tax Liability (NGN)'];
        $rows[] = [
            'Value Added Tax (VAT)',
            'FIRS',
            number_format($report['vat']['taxable_turnover'], 2, '.', ''),
            $report['vat']['rate_percent'].'%',
            number_format($report['vat']['net_vat_payable'], 2, '.', ''),
        ];
        $rows[] = [
            'Commission Withholding Tax (Corporate)',
            'FIRS',
            number_format($report['commission']['total_commission'], 2, '.', ''),
            '10.0%',
            number_format($report['commission']['corporate_wht'], 2, '.', ''),
        ];
        $rows[] = [
            'Commission Withholding Tax (Individual)',
            'State IRS',
            number_format($report['commission']['total_commission'], 2, '.', ''),
            '5.0%',
            number_format($report['commission']['individual_wht'], 2, '.', ''),
        ];
        $rows[] = [
            'Landlord Rental Income WHT',
            'FIRS (Corporate) & State IRS / LIRS (Individual)',
            number_format($report['landlord_wht']['total_gross_rent'], 2, '.', ''),
            '10.0%',
            number_format($report['landlord_wht']['total_rental_wht'], 2, '.', ''),
        ];
        $rows[] = ['TOTAL FIRS STATUTORY LIABILITY (NGN)', '', '', '', number_format($report['totals']['total_firs_liability'], 2, '.', '')];
        $rows[] = ['TOTAL STATE REVENUE SERVICES LIABILITY (NGN)', '', '', '', number_format($report['totals']['total_state_liability'], 2, '.', '')];
        $rows[] = ['GRAND TOTAL STATUTORY TAX (NGN)', '', '', '', number_format($report['totals']['grand_total_tax_liability'], 2, '.', '')];
        $rows[] = [];

        // Section 2: Landlord Rental WHT Schedule
        $rows[] = ['--- SECTION 2: LANDLORD RENTAL WHT AUDIT SCHEDULE ---'];
        $rows[] = [
            'Owner / Landlord Name',
            'Entity Type',
            'Tax ID / TIN',
            'State',
            'Tax Authority',
            'Managed Properties',
            'Gross Rental (NGN)',
            'WHT Rate',
            'WHT Deducted (NGN)',
            'Net Disbursed (NGN)',
        ];

        foreach ($report['landlord_wht']['schedule'] as $row) {
            $rows[] = [
                $row['owner_name'],
                $row['owner_type'],
                $row['tax_id'],
                $row['state'],
                $row['tax_authority'],
                $row['properties'],
                number_format($row['gross_rent'], 2, '.', ''),
                $row['wht_rate_percent'].'%',
                number_format($row['wht_deducted'], 2, '.', ''),
                number_format($row['net_payable_to_owner'], 2, '.', ''),
            ];
        }

        $rows[] = [];

        // Section 3: Commission WHT Schedule
        $rows[] = ['--- SECTION 3: COMMISSION WHT SCHEDULE ---'];
        $rows[] = [
            'Client / Landlord Name',
            'Entity Type',
            'Tax ID / TIN',
            'Commission Amount (NGN)',
            'WHT Rate',
            'WHT Amount (NGN)',
            'Remittance Authority',
        ];

        foreach ($report['commission']['schedule'] as $row) {
            $rows[] = [
                $row['owner_name'],
                $row['owner_type'],
                $row['tax_id'],
                number_format($row['commission_amount'], 2, '.', ''),
                $row['wht_rate_percent'].'%',
                number_format($row['wht_amount'], 2, '.', ''),
                $row['tax_authority'],
            ];
        }

        $stream = fopen('php://temp', 'r+');
        foreach ($rows as $row) {
            fputcsv($stream, $row);
        }
        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        return $csv;
    }
}
