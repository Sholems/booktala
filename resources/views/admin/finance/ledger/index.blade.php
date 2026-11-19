@extends('layouts.admin')
@section('page_title', 'Statutory General Ledger')
@section('content')
<div class="mb-6 flex flex-col md:flex-row md:items-end md:justify-between gap-4">
    <div>
        <h2 class="text-2xl font-bold">Statutory General Ledger</h2>
        <p class="text-sm text-gray-500">Double-entry operational journal, audit trail & accounting exports (QuickBooks, Xero, CSV).</p>
    </div>
    <div class="flex flex-wrap items-center gap-3">
        <!-- Export Dropdown -->
        <div class="relative" x-data="{ open: false }" @click.outside="open = false">
            <button @click="open = !open" type="button" class="inline-flex items-center gap-2 bg-primary hover:bg-primary-dark text-white px-4 py-2 rounded-lg text-sm font-medium shadow-sm transition">
                <i class="fas fa-file-export"></i>
                <span>Export Journal</span>
                <i class="fas fa-chevron-down text-xs ml-1"></i>
            </button>
            <div x-show="open" x-transition class="absolute right-0 mt-2 w-56 bg-white border border-slate-200 rounded-xl shadow-lg z-20 py-1.5" style="display: none;">
                <a href="{{ route('admin.finance.ledger.export', array_merge(request()->query(), ['format' => 'standard'])) }}" class="flex items-center gap-2.5 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 transition">
                    <i class="fas fa-file-csv text-emerald-600 w-4"></i>
                    <div>
                        <span class="font-medium">Standard GL CSV</span>
                        <p class="text-xs text-slate-400">Statutory audit format</p>
                    </div>
                </a>
                <a href="{{ route('admin.finance.ledger.export', array_merge(request()->query(), ['format' => 'quickbooks'])) }}" class="flex items-center gap-2.5 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 transition">
                    <i class="fas fa-table text-blue-600 w-4"></i>
                    <div>
                        <span class="font-medium">QuickBooks Journal</span>
                        <p class="text-xs text-slate-400">QBO / Desktop CSV</p>
                    </div>
                </a>
                <a href="{{ route('admin.finance.ledger.export', array_merge(request()->query(), ['format' => 'xero'])) }}" class="flex items-center gap-2.5 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 transition">
                    <i class="fas fa-file-invoice text-sky-600 w-4"></i>
                    <div>
                        <span class="font-medium">Xero Manual Journal</span>
                        <p class="text-xs text-slate-400">Xero format with tracking</p>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Ledger Summary Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
    <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider mb-1">Total Debits</p>
        <p class="text-xl font-bold text-slate-800">₦{{ number_format($summary['total_debits'], 2) }}</p>
        <span class="text-xs text-slate-400">Assets & Expenses</span>
    </div>
    <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider mb-1">Total Credits</p>
        <p class="text-xl font-bold text-slate-800">₦{{ number_format($summary['total_credits'], 2) }}</p>
        <span class="text-xs text-slate-400">Liabilities & Revenues</span>
    </div>
    <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider mb-1">Net Variance</p>
        <p class="text-xl font-bold {{ $summary['net_balance'] == 0 ? 'text-emerald-600' : 'text-rose-600' }}">₦{{ number_format($summary['net_balance'], 2) }}</p>
        <span class="text-xs text-slate-400">{{ $summary['net_balance'] == 0 ? 'Debits equal credits' : 'Imbalance detected' }}</span>
    </div>
    <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider mb-1">Balance Integrity</p>
        <div class="mt-1">
            @if($summary['is_balanced'])
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                <i class="fas fa-check-circle text-emerald-600"></i> Balanced (Zero-Sum)
            </span>
            @else
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                <i class="fas fa-exclamation-triangle text-rose-600"></i> Imbalanced
            </span>
            @endif
        </div>
        <p class="text-xs text-slate-400 mt-1">GAAP / IFRS compliant</p>
    </div>
    <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm">
        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider mb-1">Transactions / Entries</p>
        <p class="text-xl font-bold text-slate-800">{{ number_format($summary['transaction_count']) }}</p>
        <span class="text-xs text-slate-400">{{ number_format($summary['entry_count']) }} journal entry lines</span>
    </div>
</div>

<!-- Filters Panel -->
<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden mb-6" x-data="{ expanded: false }">
    <div class="p-4 border-b border-slate-200 bg-slate-50/50">
        <form method="GET" class="space-y-3">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">From Date</label>
                    <input type="date" name="start_date" value="{{ $filters['start_date'] }}" class="w-full border border-slate-300 rounded-lg px-3 py-1.5 text-sm bg-white focus:ring-2 focus:ring-primary focus:border-transparent">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">To Date</label>
                    <input type="date" name="end_date" value="{{ $filters['end_date'] }}" class="w-full border border-slate-300 rounded-lg px-3 py-1.5 text-sm bg-white focus:ring-2 focus:ring-primary focus:border-transparent">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Site / Estate</label>
                    <select name="site_id" class="w-full border border-slate-300 rounded-lg px-3 py-1.5 text-sm bg-white focus:ring-2 focus:ring-primary focus:border-transparent">
                        <option value="">All Sites</option>
                        @foreach($sites as $site)
                        <option value="{{ $site->id }}" @selected($filters['site_id'] == $site->id)>{{ $site->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Property / Unit</label>
                    <select name="unit_id" class="w-full border border-slate-300 rounded-lg px-3 py-1.5 text-sm bg-white focus:ring-2 focus:ring-primary focus:border-transparent">
                        <option value="">All Properties</option>
                        @foreach($units as $unit)
                        <option value="{{ $unit->id }}" @selected($filters['unit_id'] == $unit->id)>{{ $unit->title }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Account</label>
                    <select name="account" class="w-full border border-slate-300 rounded-lg px-3 py-1.5 text-sm bg-white focus:ring-2 focus:ring-primary focus:border-transparent">
                        <option value="">All Accounts</option>
                        @foreach($accountChart as $key => $meta)
                        <option value="{{ $key }}" @selected($filters['account'] === $key)>{{ $meta['code'] }} - {{ $meta['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Event Type</label>
                    <select name="event_type" class="w-full border border-slate-300 rounded-lg px-3 py-1.5 text-sm bg-white focus:ring-2 focus:ring-primary focus:border-transparent">
                        <option value="">All Event Types</option>
                        @foreach($eventTypes as $key => $label)
                        <option value="{{ $key }}" @selected($filters['event_type'] === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Owner / Landlord</label>
                    <select name="owner_id" class="w-full border border-slate-300 rounded-lg px-3 py-1.5 text-sm bg-white focus:ring-2 focus:ring-primary focus:border-transparent">
                        <option value="">All Owners</option>
                        @foreach($owners as $owner)
                        <option value="{{ $owner->id }}" @selected($filters['owner_id'] == $owner->id)>{{ $owner->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Search Keywords</label>
                    <input type="text" name="search" value="{{ $filters['search'] }}" placeholder="Reference, memo, key..." class="w-full border border-slate-300 rounded-lg px-3 py-1.5 text-sm bg-white focus:ring-2 focus:ring-primary focus:border-transparent">
                </div>
            </div>

            <div class="flex items-center justify-between pt-2">
                <div class="text-xs text-slate-500">
                    Showing results for period <span class="font-semibold text-slate-700">{{ $filters['start_date'] }}</span> to <span class="font-semibold text-slate-700">{{ $filters['end_date'] }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('admin.finance.ledger.index') }}" class="px-3 py-1.5 border border-slate-300 text-slate-600 hover:bg-slate-100 rounded-lg text-xs font-medium transition">
                        Reset Filters
                    </a>
                    <button type="submit" class="px-4 py-1.5 bg-primary hover:bg-primary-dark text-white rounded-lg text-xs font-medium shadow-sm transition">
                        <i class="fas fa-filter mr-1"></i> Apply Filters
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Transactions Journal Table -->
<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden" x-data="{ expandedTx: null }">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-slate-600">
            <thead class="bg-slate-50 text-slate-700 font-semibold border-b border-slate-200 text-xs uppercase tracking-wider">
                <tr>
                    <th class="py-3 px-4">Date & Ref</th>
                    <th class="py-3 px-4">Event Type</th>
                    <th class="py-3 px-4">Description / Memo</th>
                    <th class="py-3 px-4 text-right">Debit (NGN)</th>
                    <th class="py-3 px-4 text-right">Credit (NGN)</th>
                    <th class="py-3 px-4">Associated Entity</th>
                    <th class="py-3 px-4 text-center">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($transactions as $tx)
                @php
                    $txDebits = $tx->entries->where('amount_minor', '>', 0)->sum('amount_minor') / 100;
                    $txCredits = abs($tx->entries->where('amount_minor', '<', 0)->sum('amount_minor')) / 100;
                    $firstEntry = $tx->entries->first();
                @endphp
                <tr class="hover:bg-slate-50/70 transition cursor-pointer" @click="expandedTx = (expandedTx === {{ $tx->id }} ? null : {{ $tx->id }})">
                    <td class="py-3 px-4">
                        <div class="font-medium text-slate-800">{{ $tx->reference }}</div>
                        <div class="text-xs text-slate-400">{{ $tx->occurred_at?->format('M j, Y H:i') }}</div>
                    </td>
                    <td class="py-3 px-4">
                        @switch($tx->event_type)
                            @case('collection')
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-800">Collection</span>
                                @break
                            @case('stay_completion')
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800">Stay Completed</span>
                                @break
                            @case('caution_refund')
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">Caution Refund</span>
                                @break
                            @case('expense')
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800">Expense</span>
                                @break
                            @case('payout')
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">Owner Payout</span>
                                @break
                            @case('reversal')
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-rose-100 text-rose-800">Reversal</span>
                                @break
                            @default
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-800">{{ $tx->event_type }}</span>
                        @endswitch
                    </td>
                    <td class="py-3 px-4 max-w-xs truncate" title="{{ $tx->description }}">
                        <span class="text-slate-700">{{ $tx->description ?: 'Operational transaction' }}</span>
                        @if($tx->reversal_of_id)
                        <span class="block text-xs text-rose-500 font-semibold">Reversal of #{{ $tx->reversal_of_id }}</span>
                        @endif
                    </td>
                    <td class="py-3 px-4 text-right font-medium text-slate-800">
                        ₦{{ number_format($txDebits, 2) }}
                    </td>
                    <td class="py-3 px-4 text-right font-medium text-slate-800">
                        ₦{{ number_format($txCredits, 2) }}
                    </td>
                    <td class="py-3 px-4">
                        <div class="text-xs text-slate-700">{{ $firstEntry?->site?->name ?? 'Corporate / Portfolio' }}</div>
                        @if($firstEntry?->unit)
                        <div class="text-xs text-slate-400">{{ $firstEntry->unit->title }}</div>
                        @elseif($firstEntry?->owner)
                        <div class="text-xs text-slate-400">{{ $firstEntry->owner->name }}</div>
                        @endif
                    </td>
                    <td class="py-3 px-4 text-center">
                        <button type="button" class="text-slate-400 hover:text-primary transition p-1" title="Toggle entry legs">
                            <i class="fas" :class="expandedTx === {{ $tx->id }} ? 'fa-chevron-up text-primary' : 'fa-chevron-down'"></i>
                        </button>
                    </td>
                </tr>

                <!-- Expanded Ledger Legs Drawer -->
                <tr x-show="expandedTx === {{ $tx->id }}" x-transition style="display: none;" class="bg-slate-50/90 border-t border-b border-slate-200">
                    <td colspan="7" class="p-4">
                        <div class="bg-white rounded-lg border border-slate-200 p-3 shadow-inner">
                            <div class="flex items-center justify-between mb-2 pb-2 border-b border-slate-100">
                                <div class="text-xs font-bold uppercase tracking-wider text-slate-500">
                                    Double-Entry Legs for {{ $tx->reference }}
                                </div>
                                <div class="text-xs text-slate-400 font-mono">
                                    Idempotency: {{ $tx->idempotency_key }}
                                </div>
                            </div>
                            <table class="w-full text-xs">
                                <thead>
                                    <tr class="text-slate-500 border-b border-slate-100">
                                        <th class="py-1.5 px-2 text-left">Account</th>
                                        <th class="py-1.5 px-2 text-left">Property / Dimension</th>
                                        <th class="py-1.5 px-2 text-right">Debit (NGN)</th>
                                        <th class="py-1.5 px-2 text-right">Credit (NGN)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($tx->entries as $entry)
                                    @php
                                        $meta = \App\Services\Finance\GeneralLedgerExportService::accountMeta($entry->account);
                                    @endphp
                                    <tr class="border-b border-slate-50 hover:bg-slate-50">
                                        <td class="py-1.5 px-2">
                                            <span class="font-mono font-medium text-slate-700">{{ $meta['code'] }}</span>
                                            <span class="ml-1 text-slate-600">{{ $meta['name'] }}</span>
                                            <span class="text-[10px] text-slate-400 ml-1">({{ $meta['type'] }})</span>
                                        </td>
                                        <td class="py-1.5 px-2 text-slate-500">
                                            {{ $entry->unit?->title ?? ($entry->site?->name ?? 'Corporate') }}
                                            @if($entry->owner)
                                            • <span class="text-slate-400">{{ $entry->owner->name }}</span>
                                            @endif
                                            @if($entry->reservation)
                                            • <span class="text-slate-400">{{ $entry->reservation->booking_ref }}</span>
                                            @endif
                                        </td>
                                        <td class="py-1.5 px-2 text-right font-medium {{ $entry->amount_minor > 0 ? 'text-slate-800' : 'text-slate-300' }}">
                                            {{ $entry->amount_minor > 0 ? number_format($entry->amount_minor / 100, 2) : '—' }}
                                        </td>
                                        <td class="py-1.5 px-2 text-right font-medium {{ $entry->amount_minor < 0 ? 'text-slate-800' : 'text-slate-300' }}">
                                            {{ $entry->amount_minor < 0 ? number_format(abs($entry->amount_minor) / 100, 2) : '—' }}
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="font-bold bg-slate-50/50">
                                        <td colspan="2" class="py-1.5 px-2 text-right text-slate-600">Total Transaction Leg Balance:</td>
                                        <td class="py-1.5 px-2 text-right text-slate-800">₦{{ number_format($txDebits, 2) }}</td>
                                        <td class="py-1.5 px-2 text-right text-slate-800">₦{{ number_format($txCredits, 2) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="py-8 text-center text-slate-400">
                        <i class="fas fa-book-open text-2xl mb-2 text-slate-300"></i>
                        <p>No journal transactions found matching the selected filters.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($transactions->hasPages())
    <div class="p-4 border-t border-slate-200">
        {{ $transactions->links() }}
    </div>
    @endif
</div>
@endsection
