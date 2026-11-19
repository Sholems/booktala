@extends('layouts.admin')
@section('page_title', 'Tax & WHT Compliance Reports')
@section('content')
<div class="mb-6 flex flex-col md:flex-row md:items-end md:justify-between gap-4">
    <div>
        <h2 class="text-2xl font-bold">Tax & WHT Compliance Reports</h2>
        <p class="text-sm text-gray-500">Statutory VAT (FIRS) and Withholding Tax schedules (LIRS, FCT-IRS & FIRS) for corporate filings.</p>
    </div>
    <div class="flex flex-wrap items-center gap-3">
        <a href="{{ route('admin.finance.tax.export', request()->query()) }}" class="inline-flex items-center gap-2 bg-primary hover:bg-primary-dark text-white px-4 py-2 rounded-lg text-sm font-medium shadow-sm transition">
            <i class="fas fa-file-csv"></i>
            <span>Export Tax Schedule (CSV)</span>
        </a>
    </div>
</div>

<!-- Filters Panel -->
<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden mb-6">
    <div class="p-4 border-b border-slate-200 bg-slate-50/50">
        <form method="GET" class="flex flex-wrap gap-3 items-end justify-between">
            <div class="flex flex-wrap gap-3 items-center">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">From Date</label>
                    <input type="date" name="start_date" value="{{ $filters['start_date'] }}" class="border border-slate-300 rounded-lg px-3 py-1.5 text-sm bg-white focus:ring-2 focus:ring-primary focus:border-transparent">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">To Date</label>
                    <input type="date" name="end_date" value="{{ $filters['end_date'] }}" class="border border-slate-300 rounded-lg px-3 py-1.5 text-sm bg-white focus:ring-2 focus:ring-primary focus:border-transparent">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Site / Estate</label>
                    <select name="site_id" class="border border-slate-300 rounded-lg px-3 py-1.5 text-sm bg-white focus:ring-2 focus:ring-primary focus:border-transparent min-w-[180px]">
                        <option value="">All Sites & Jurisdictions</option>
                        @foreach($sites as $site)
                        <option value="{{ $site->id }}" @selected($filters['site_id'] == $site->id)>{{ $site->name }} ({{ $site->state }})</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.finance.tax.index') }}" class="px-3 py-1.5 border border-slate-300 text-slate-600 hover:bg-slate-100 rounded-lg text-xs font-medium transition">
                    Reset
                </a>
                <button type="submit" class="px-4 py-1.5 bg-primary hover:bg-primary-dark text-white rounded-lg text-xs font-medium shadow-sm transition">
                    <i class="fas fa-filter mr-1"></i> Generate Schedule
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Executive Summary Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider mb-1">Grand Total Tax Liability</p>
        <p class="text-2xl font-bold text-slate-800">₦{{ number_format($report['totals']['grand_total_tax_liability'], 2) }}</p>
        <span class="text-xs text-slate-400">Total Statutory Remittance</span>
    </div>
    <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider mb-1">Federal Remittance (FIRS)</p>
        <p class="text-2xl font-bold text-indigo-700">₦{{ number_format($report['totals']['total_firs_liability'], 2) }}</p>
        <span class="text-xs text-slate-400">VAT (7.5%) + Corporate WHT (10%)</span>
    </div>
    <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider mb-1">State Revenue (LIRS / IRS)</p>
        <p class="text-2xl font-bold text-emerald-700">₦{{ number_format($report['totals']['total_state_liability'], 2) }}</p>
        <span class="text-xs text-slate-400">Individual Landlord & Comm WHT</span>
    </div>
    <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider mb-1">Taxable Accommodation</p>
        <p class="text-2xl font-bold text-slate-800">₦{{ number_format($report['vat']['taxable_turnover'], 2) }}</p>
        <span class="text-xs text-slate-400">Turnover for {{ $report['period']['label'] }}</span>
    </div>
</div>

<div class="space-y-6">
    <!-- Section 1: FIRS Value Added Tax (VAT) Schedule -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-4 bg-slate-50 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <h3 class="text-base font-bold text-slate-800 flex items-center gap-2">
                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold">1</span>
                    Federal Inland Revenue Service (FIRS) — Value Added Tax (VAT)
                </h3>
                <p class="text-xs text-slate-500">Statutory 7.5% VAT on accommodation and guest services under the Nigerian VAT Act.</p>
            </div>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">
                Remit by 21st (TaxPro-Max)
            </span>
        </div>
        <div class="p-4 grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-slate-50 rounded-lg p-3 border border-slate-100">
                <span class="text-xs text-slate-500">Taxable Gross Turnover:</span>
                <p class="text-lg font-bold text-slate-800">₦{{ number_format($report['vat']['taxable_turnover'], 2) }}</p>
            </div>
            <div class="bg-slate-50 rounded-lg p-3 border border-slate-100">
                <span class="text-xs text-slate-500">Output VAT Charged (7.5%):</span>
                <p class="text-lg font-bold text-slate-800">₦{{ number_format($report['vat']['output_vat'], 2) }}</p>
            </div>
            <div class="bg-indigo-50/50 rounded-lg p-3 border border-indigo-100">
                <span class="text-xs text-indigo-600 font-medium">Net VAT Remittable to FIRS:</span>
                <p class="text-lg font-bold text-indigo-700">₦{{ number_format($report['vat']['net_vat_payable'], 2) }}</p>
            </div>
        </div>
    </div>

    <!-- Section 2: Landlord Rental Income Withholding Tax (WHT - 10%) -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-4 bg-slate-50 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <h3 class="text-base font-bold text-slate-800 flex items-center gap-2">
                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-emerald-100 text-emerald-700 text-xs font-bold">2</span>
                    Landlord Rental Income Withholding Tax (WHT — 10%)
                </h3>
                <p class="text-xs text-slate-500">Statutory 10% WHT on rental earnings remitted to State IRS (LIRS in Lagos) or FIRS (Corporate entities).</p>
            </div>
            <div class="text-xs text-slate-600">
                Total Rental WHT: <span class="font-bold text-slate-800">₦{{ number_format($report['landlord_wht']['total_rental_wht'], 2) }}</span>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-slate-700 font-semibold border-b border-slate-200 text-xs uppercase tracking-wider">
                    <tr>
                        <th class="py-2.5 px-4">Landlord / Owner</th>
                        <th class="py-2.5 px-4">Type & TIN</th>
                        <th class="py-2.5 px-4">Property / State</th>
                        <th class="py-2.5 px-4">Tax Authority</th>
                        <th class="py-2.5 px-4 text-right">Gross Rent (NGN)</th>
                        <th class="py-2.5 px-4 text-center">Rate</th>
                        <th class="py-2.5 px-4 text-right">WHT Deducted (NGN)</th>
                        <th class="py-2.5 px-4 text-right">Net Landlord (NGN)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($report['landlord_wht']['schedule'] as $item)
                    <tr class="hover:bg-slate-50/70 transition">
                        <td class="py-2.5 px-4 font-medium text-slate-800">
                            {{ $item['owner_name'] }}
                        </td>
                        <td class="py-2.5 px-4">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $item['owner_type'] === 'Company' ? 'bg-indigo-100 text-indigo-800' : 'bg-slate-100 text-slate-800' }}">
                                {{ $item['owner_type'] }}
                            </span>
                            <div class="text-xs text-slate-400 font-mono mt-0.5">{{ $item['tax_id'] }}</div>
                        </td>
                        <td class="py-2.5 px-4 text-xs">
                            <div class="text-slate-700 font-medium">{{ $item['properties'] }}</div>
                            <div class="text-slate-400">{{ $item['state'] }}</div>
                        </td>
                        <td class="py-2.5 px-4 text-xs font-medium text-slate-700">
                            {{ $item['tax_authority'] }}
                        </td>
                        <td class="py-2.5 px-4 text-right font-medium text-slate-800">
                            ₦{{ number_format($item['gross_rent'], 2) }}
                        </td>
                        <td class="py-2.5 px-4 text-center text-xs">
                            {{ $item['wht_rate_percent'] }}%
                        </td>
                        <td class="py-2.5 px-4 text-right font-medium text-rose-600">
                            ₦{{ number_format($item['wht_deducted'], 2) }}
                        </td>
                        <td class="py-2.5 px-4 text-right font-medium text-emerald-600">
                            ₦{{ number_format($item['net_payable_to_owner'], 2) }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="py-6 text-center text-slate-400">
                            No third-party landlord rental transactions recorded in this period.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if(!empty($report['landlord_wht']['schedule']))
                <tfoot class="bg-slate-50 font-semibold text-slate-700">
                    <tr>
                        <td colspan="4" class="py-2.5 px-4 text-right">Totals:</td>
                        <td class="py-2.5 px-4 text-right">₦{{ number_format($report['landlord_wht']['total_gross_rent'], 2) }}</td>
                        <td></td>
                        <td class="py-2.5 px-4 text-right text-rose-600">₦{{ number_format($report['landlord_wht']['total_rental_wht'], 2) }}</td>
                        <td class="py-2.5 px-4 text-right text-emerald-600">₦{{ number_format($report['landlord_wht']['total_gross_rent'] - $report['landlord_wht']['total_rental_wht'], 2) }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

    <!-- Section 3: Management Commission Withholding Tax -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-4 bg-slate-50 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <h3 class="text-base font-bold text-slate-800 flex items-center gap-2">
                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-amber-100 text-amber-700 text-xs font-bold">3</span>
                    Property Management Commission WHT Schedule
                </h3>
                <p class="text-xs text-slate-500">Withholding tax applicable to BookTala management commission earnings (10% Corporate / 5% Individual).</p>
            </div>
            <div class="text-xs text-slate-600">
                Total Commission WHT: <span class="font-bold text-slate-800">₦{{ number_format($report['commission']['total_wht'], 2) }}</span>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-slate-700 font-semibold border-b border-slate-200 text-xs uppercase tracking-wider">
                    <tr>
                        <th class="py-2.5 px-4">Client / Landlord</th>
                        <th class="py-2.5 px-4">Entity Type</th>
                        <th class="py-2.5 px-4">Tax ID / TIN</th>
                        <th class="py-2.5 px-4 text-right">Commission (NGN)</th>
                        <th class="py-2.5 px-4 text-center">WHT Rate</th>
                        <th class="py-2.5 px-4 text-right">WHT Amount (NGN)</th>
                        <th class="py-2.5 px-4">Remittance Authority</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($report['commission']['schedule'] as $item)
                    <tr class="hover:bg-slate-50/70 transition">
                        <td class="py-2.5 px-4 font-medium text-slate-800">{{ $item['owner_name'] }}</td>
                        <td class="py-2.5 px-4">{{ $item['owner_type'] }}</td>
                        <td class="py-2.5 px-4 font-mono text-xs text-slate-400">{{ $item['tax_id'] }}</td>
                        <td class="py-2.5 px-4 text-right font-medium text-slate-800">₦{{ number_format($item['commission_amount'], 2) }}</td>
                        <td class="py-2.5 px-4 text-center text-xs">{{ $item['wht_rate_percent'] }}%</td>
                        <td class="py-2.5 px-4 text-right font-medium text-amber-700">₦{{ number_format($item['wht_amount'], 2) }}</td>
                        <td class="py-2.5 px-4 text-xs font-medium text-slate-700">{{ $item['tax_authority'] }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-6 text-center text-slate-400">
                            No management commissions recorded in this period.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Section 4: Revenue Authority Remittance Summary -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-4 bg-slate-50 border-b border-slate-200">
            <h3 class="text-base font-bold text-slate-800 flex items-center gap-2">
                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-slate-200 text-slate-700 text-xs font-bold">4</span>
                Summary of Statutory Remittances by Revenue Authority
            </h3>
        </div>
        <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Federal Inland Revenue Service Card -->
            <div class="border border-indigo-100 bg-indigo-50/30 rounded-xl p-4">
                <div class="flex items-center justify-between mb-3 pb-2 border-b border-indigo-100">
                    <span class="font-bold text-slate-800">Federal Inland Revenue Service (FIRS)</span>
                    <span class="text-xs px-2 py-0.5 rounded font-semibold bg-indigo-100 text-indigo-700">Federal</span>
                </div>
                <div class="space-y-2 text-xs">
                    <div class="flex justify-between">
                        <span class="text-slate-600">Value Added Tax (7.5%):</span>
                        <span class="font-bold text-slate-800">₦{{ number_format($report['jurisdictions']['FIRS']['vat_liability'], 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-600">Corporate Landlord Rental WHT (10%):</span>
                        <span class="font-bold text-slate-800">₦{{ number_format($report['jurisdictions']['FIRS']['corporate_rental_wht'], 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-600">Corporate Commission WHT (10%):</span>
                        <span class="font-bold text-slate-800">₦{{ number_format($report['jurisdictions']['FIRS']['corporate_commission_wht'], 2) }}</span>
                    </div>
                    <div class="flex justify-between pt-2 border-t border-indigo-100 font-bold text-sm text-indigo-800">
                        <span>Total FIRS Liability:</span>
                        <span>₦{{ number_format($report['jurisdictions']['FIRS']['total_liability'], 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- State Internal Revenue Services Card -->
            <div class="border border-emerald-100 bg-emerald-50/30 rounded-xl p-4">
                <div class="flex items-center justify-between mb-3 pb-2 border-b border-emerald-100">
                    <span class="font-bold text-slate-800">State Internal Revenue Services (LIRS & States)</span>
                    <span class="text-xs px-2 py-0.5 rounded font-semibold bg-emerald-100 text-emerald-700">State</span>
                </div>
                <div class="space-y-2 text-xs">
                    @forelse($report['jurisdictions']['states'] as $stateData)
                    <div class="flex justify-between py-1 border-b border-emerald-100/60">
                        <span class="text-slate-600">{{ $stateData['authority'] }} ({{ $stateData['state'] }}):</span>
                        <span class="font-bold text-slate-800">₦{{ number_format($stateData['total_liability'], 2) }}</span>
                    </div>
                    @empty
                    <div class="text-slate-400 py-1">No individual state tax liabilities for this period.</div>
                    @endforelse
                    <div class="flex justify-between pt-2 border-t border-emerald-100 font-bold text-sm text-emerald-800">
                        <span>Total State Liabilities:</span>
                        <span>₦{{ number_format($report['jurisdictions']['state_total'], 2) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
