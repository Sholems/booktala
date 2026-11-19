@extends('layouts.admin')

@section('title', 'Invoice #' . $invoice->invoice_number)
@section('page_title', 'Invoice #' . $invoice->invoice_number)

@php
    $siteName = \App\Models\Setting::get('site_name', config('brand.name'));
    $siteEmail = \App\Models\Setting::get('email', config('brand.email'));
    $sitePhone = \App\Models\Setting::get('phone', config('brand.phone'));
    $siteAddress = \App\Models\Setting::get('address', config('brand.address'));
    $companyName = \App\Models\Setting::get('company_name', 'G-Briel Property Ltd');
    $bankName = \App\Models\Setting::get('bank_name', 'First Bank of Nigeria / Providus Bank');
    $accountNumber = \App\Models\Setting::get('account_number', '0123456789');
    $accountName = \App\Models\Setting::get('account_name', 'BookTala (G-Briel Property Ltd)');

    $reservation = $invoice->reservation;
    $guestName = $invoice->user?->name ?? $reservation?->guest?->name ?? $reservation?->user?->name ?? 'Valued Client';
    $guestPhone = $invoice->user?->phone ?? $reservation?->guest?->phone ?? $reservation?->user?->phone ?? 'N/A';
    $guestEmail = $invoice->user?->email ?? $reservation?->guest?->email ?? $reservation?->user?->email ?? '';
    $unitTitle = $reservation?->unit?->title ?? $reservation?->property?->title ?? $reservation?->property?->name ?? 'BookTala Residence';
    $siteLocation = $reservation?->unit?->site?->name ?? ($reservation?->property?->city ? $reservation?->property?->city . ', ' . $reservation?->property?->state : 'Lagos, Nigeria');
    $isPaidFull = (float) $invoice->balance <= 0.01;
    $bookingRef = $reservation?->booking_ref ?? ('INV-' . $invoice->id);
    $logoUrl = asset('images/booktala-logo.png');
@endphp

@push('styles')
<style>
    /* Robust screen flexbox layout that never squishes or depends on external tailwind compilation */
    .invoice-wrapper {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        box-sizing: border-box;
    }
    .doc-info-row {
        display: flex;
        flex-direction: row;
        gap: 16px;
        margin: 16px 0;
        width: 100%;
        box-sizing: border-box;
    }
    .doc-info-billto {
        flex: 7;
        min-width: 0;
        box-sizing: border-box;
    }
    .doc-info-meta {
        flex: 5;
        min-width: 0;
        box-sizing: border-box;
    }
    @media (max-width: 768px) {
        .doc-info-row {
            flex-direction: column;
        }
    }

    /* Print-specific layout isolation & STRICT 1-PAGE BUDGET */
    @media print {
        @page {
            size: A4 portrait;
            margin: 6mm 8mm;
        }

        /* Hide all admin shell chrome */
        header,
        nav,
        aside,
        footer,
        .admin-sidebar,
        .admin-header,
        .sidebar,
        .desktop-nav,
        .mobile-menu,
        #mobile-menu,
        .print\:hidden,
        .no-print,
        [role="navigation"],
        button,
        .admin-topbar {
            display: none !important;
            visibility: hidden !important;
            height: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        html, body {
            background: #ffffff !important;
            color: #000000 !important;
            margin: 0 !important;
            padding: 0 !important;
            height: 100% !important;
            max-height: 100% !important;
            font-size: 8.5pt !important;
            line-height: 1.25 !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        main {
            padding: 0 !important;
            margin: 0 !important;
            display: block !important;
            width: 100% !important;
        }

        .invoice-wrapper {
            box-shadow: none !important;
            border: none !important;
            padding: 0 !important;
            margin: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
            page-break-after: avoid !important;
            page-break-inside: avoid !important;
        }

        .doc-header {
            padding-bottom: 6px !important;
            margin-bottom: 6px !important;
        }
        .doc-logo {
            height: 38px !important;
        }
        .doc-title {
            font-size: 13pt !important;
        }
        .doc-info-row {
            margin: 6px 0 !important;
            gap: 10px !important;
        }
        .doc-card {
            padding: 6px 8px !important;
        }
        .doc-table {
            margin: 6px 0 !important;
        }
        .doc-table th, .doc-table td {
            padding: 3.5px 6px !important;
            font-size: 8pt !important;
        }
        .doc-summary {
            margin: 4px 0 6px 0 !important;
        }
        .doc-bank-box {
            padding: 5px 8px !important;
            margin: 4px 0 !important;
            font-size: 8pt !important;
        }
        .doc-note-box {
            padding: 4px 8px !important;
            margin: 4px 0 !important;
            font-size: 7.5pt !important;
            line-height: 1.2 !important;
        }
        .doc-signatures {
            margin-top: 6px !important;
            padding-top: 4px !important;
        }
        .doc-footer {
            margin-top: 4px !important;
            font-size: 7pt !important;
        }

        * {
            page-break-inside: avoid !important;
        }
    }
</style>
@endpush

@section('content')
<div class="space-y-6 py-2">
    <!-- Action Toolbar (Hidden in Print) -->
    <div class="flex flex-wrap items-center justify-between gap-4 bg-white border border-gray-200 rounded-xl p-4 shadow-sm print:hidden">
        <div>
            <a href="{{ route('admin.invoices.index') }}" class="inline-flex items-center gap-1.5 text-primary hover:underline text-xs font-semibold">
                <i class="fas fa-arrow-left"></i> Back to Invoices
            </a>
            <h2 class="text-xl font-bold text-gray-900 mt-1">Invoice #{{ $invoice->invoice_number }}</h2>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <!-- Print Button -->
            <button type="button" onclick="window.print()" class="inline-flex items-center gap-2 px-4 py-2 bg-primary hover:bg-primary/90 text-white rounded-lg text-xs font-bold transition shadow-sm" title="Print invoice without admin navigation">
                <i class="fas fa-print"></i> Print Invoice
            </button>

            <!-- Download PDF Button -->
            <a href="{{ route('admin.invoices.pdf', $invoice) }}" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-xs font-bold transition shadow-sm">
                <i class="fas fa-file-pdf"></i> Download PDF
            </a>

            <!-- Send Email Button / Modal Trigger -->
            <form action="{{ route('admin.invoices.send', $invoice) }}" method="POST" class="inline" onsubmit="return confirm('Send invoice PDF to {{ $invoice->user->email ?? $guestEmail }}?')">
                @csrf
                <input type="hidden" name="email" value="{{ $invoice->user->email ?? $guestEmail }}">
                <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-bold transition shadow-sm" {{ empty($invoice->user->email ?? $guestEmail) ? 'disabled' : '' }}>
                    <i class="fas fa-envelope"></i> Email Invoice
                </button>
            </form>

            @if($invoice->status !== 'paid')
                <!-- Mark as Paid Button -->
                <form action="{{ route('admin.invoices.mark-paid', $invoice) }}" method="POST" class="inline" onsubmit="return confirm('Mark invoice #{{ $invoice->invoice_number }} as paid?')">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold transition shadow-sm">
                        <i class="fas fa-check"></i> Mark as Paid
                    </button>
                </form>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-emerald-800 text-sm flex items-center justify-between print:hidden shadow-sm">
            <div class="flex items-center gap-2">
                <i class="fas fa-check-circle text-emerald-500"></i>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-800">&times;</button>
        </div>
    @endif

    <!-- Official Printable Invoice Sheet -->
    <div id="printable-invoice" class="invoice-wrapper mx-auto max-w-4xl bg-white rounded-2xl border border-gray-200 p-6 sm:p-8 shadow-lg">
        
        <!-- Document Header -->
        <div class="doc-header flex flex-col sm:flex-row justify-between items-start gap-4 border-b-2 border-slate-100 pb-4">
            <!-- Left: Brand Logo & Company Info -->
            <div class="flex items-start gap-3.5">
                <img src="{{ $logoUrl }}" alt="{{ $siteName }}" class="doc-logo h-12 sm:h-14 w-auto object-contain">
                <div>
                    <h1 class="doc-title text-lg sm:text-xl font-black tracking-tight text-primary uppercase" style="color: #0B2D5B;">{{ $siteName }} LUXURY STAYS</h1>
                    <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide">Operated by {{ $companyName }}</p>
                    <p class="text-[11px] text-slate-600 mt-0.5 max-w-sm leading-snug">{{ $siteAddress }}</p>
                    <p class="text-[11px] text-slate-600 mt-0.5">
                        <span class="font-medium">Tel:</span> {{ $sitePhone }} &nbsp;|&nbsp; 
                        <span class="font-medium">Email:</span> {{ $siteEmail }}
                    </p>
                </div>
            </div>

            <!-- Right: Dark Blue Document Identifier Badge -->
            <div class="w-full sm:w-auto text-left sm:text-right shrink-0">
                <div class="inline-block bg-primary text-white rounded-xl px-4 py-2.5 shadow-sm text-left sm:text-right w-full sm:w-auto" style="background-color: #0B2D5B; color: #ffffff;">
                    <div class="text-[10px] font-bold tracking-widest text-slate-200 uppercase">OFFICIAL INVOICE</div>
                    <div class="text-sm sm:text-base font-black tracking-wider text-amber-400 mt-0.5" style="color: #F59E0B;">
                        #{{ $invoice->invoice_number }}
                    </div>
                    @if($reservation)
                        <div class="text-[10px] text-slate-300 mt-0.5">
                            Booking: <span class="font-mono text-white">{{ $bookingRef }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- 2-Column Information Cards: BILL TO & INVOICE METADATA -->
        <div class="doc-info-row">
            <!-- Left: BILL TO Card -->
            <div class="doc-info-billto doc-card bg-slate-50 border border-slate-200 rounded-xl p-3.5 sm:p-4 relative" style="border-left: 4px solid #0B2D5B;">
                <div class="text-[10px] font-bold text-primary uppercase tracking-wider mb-1 flex items-center gap-1.5" style="color: #0B2D5B;">
                    <i class="fas fa-user-tag text-xs"></i> BILL TO / CLIENT DETAILS
                </div>
                <h3 class="text-sm sm:text-base font-black text-slate-900 leading-tight">{{ $guestName }}</h3>
                <div class="mt-1.5 space-y-0.5 text-xs text-slate-600">
                    <p><span class="font-semibold text-slate-700">Account / Email:</span> {{ $guestEmail ?: 'Guest Reservation' }}</p>
                    <p><span class="font-semibold text-slate-700">Phone:</span> {{ $guestPhone }}</p>
                    @if($reservation)
                        <p><span class="font-semibold text-slate-700">Assigned Suite:</span> <span class="font-bold text-slate-900">{{ $unitTitle }}</span> ({{ $siteLocation }})</p>
                    @endif
                </div>
            </div>

            <!-- Right: INVOICE METADATA Card -->
            <div class="doc-info-meta doc-card bg-white border border-slate-200 rounded-xl p-3.5 sm:p-4 text-xs text-slate-700 space-y-1.5">
                <div class="flex justify-between border-b border-slate-100 pb-1">
                    <span class="text-slate-500">Invoice Date:</span>
                    <strong class="text-slate-900">{{ $invoice->created_at ? $invoice->created_at->format('d M Y') : now()->format('d M Y') }}</strong>
                </div>
                <div class="flex justify-between border-b border-slate-100 pb-1">
                    <span class="text-slate-500">Payment Due:</span>
                    <strong class="text-slate-900">{{ $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('d M Y') : now()->addDays(7)->format('d M Y') }}</strong>
                </div>
                @if($reservation)
                    <div class="flex justify-between border-b border-slate-100 pb-1">
                        <span class="text-slate-500">Stay Period:</span>
                        <strong class="text-slate-900">{{ $reservation->check_in->format('d M') }} – {{ $reservation->check_out->format('d M Y') }} ({{ $reservation->nights }} nights)</strong>
                    </div>
                @endif
                <div class="flex justify-between pt-0.5 items-center">
                    <span class="text-slate-500">Invoice Status:</span>
                    @if($isPaidFull)
                        <span class="inline-flex items-center gap-1 bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded-full font-bold text-[10px] uppercase">
                            <i class="fas fa-check-circle text-xs"></i> Paid in Full
                        </span>
                    @elseif($invoice->status === 'overdue')
                        <span class="inline-flex items-center gap-1 bg-red-100 text-red-800 px-2 py-0.5 rounded-full font-bold text-[10px] uppercase">
                            <i class="fas fa-exclamation-circle text-xs"></i> Overdue
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 bg-amber-100 text-amber-800 px-2 py-0.5 rounded-full font-bold text-[10px] uppercase">
                            <i class="fas fa-clock text-xs"></i> {{ ucfirst($invoice->status) }}
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Line Items Table -->
        <div class="doc-table overflow-x-auto rounded-xl border border-slate-200 my-4">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-primary text-white font-bold" style="background-color: #0B2D5B; color: #ffffff;">
                        <th class="py-2.5 px-3 text-center w-12 border-b border-primary/20">S/N</th>
                        <th class="py-2.5 px-4 border-b border-primary/20">Description</th>
                        <th class="py-2.5 px-3 text-center w-24 border-b border-primary/20">Qty / Nights</th>
                        <th class="py-2.5 px-4 text-right w-32 border-b border-primary/20">Rate (₦)</th>
                        <th class="py-2.5 px-4 text-right w-36 border-b border-primary/20">Amount (₦)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-800">
                    <tr class="bg-slate-100 font-bold text-slate-700 text-[11px]">
                        <td colspan="5" class="py-1 px-4 tracking-wider">
                            <span class="text-primary font-black mr-1" style="color: #0B2D5B;">◆</span> ACCOMMODATION & SERVICES BILLED
                        </td>
                    </tr>

                    @php $sn = 1; @endphp
                    @forelse($invoice->items as $item)
                        <tr class="hover:bg-slate-50/50">
                            <td class="py-2 px-3 text-center text-slate-500 font-mono">{{ $sn++ }}</td>
                            <td class="py-2 px-4">
                                <span class="font-bold text-slate-900">{{ $item->description }}</span>
                            </td>
                            <td class="py-2 px-3 text-center font-mono">{{ $item->quantity }}</td>
                            <td class="py-2 px-4 text-right font-mono">
                                ₦{{ number_format((float) ($item->unit_price ?? ($item->quantity ? $item->total / $item->quantity : $item->total)), 2) }}
                            </td>
                            <td class="py-2 px-4 text-right font-mono font-bold text-slate-900">
                                ₦{{ number_format((float) $item->total, 2) }}
                            </td>
                        </tr>
                    @empty
                        @if($reservation)
                            <tr class="hover:bg-slate-50/50">
                                <td class="py-2 px-3 text-center text-slate-500 font-mono">1</td>
                                <td class="py-2 px-4">
                                    <span class="font-bold text-slate-900">Accommodation - {{ $unitTitle }}</span>
                                    <div class="text-[10px] text-slate-500">Check-in: {{ $reservation->check_in->format('d M Y') }} &bull; Check-out: {{ $reservation->check_out->format('d M Y') }}</div>
                                </td>
                                <td class="py-2 px-3 text-center font-mono">{{ $reservation->nights }}</td>
                                <td class="py-2 px-4 text-right font-mono">
                                    ₦{{ number_format((float) ($reservation->nights ? $reservation->subtotal / $reservation->nights : $reservation->subtotal), 2) }}
                                </td>
                                <td class="py-2 px-4 text-right font-mono font-bold text-slate-900">
                                    ₦{{ number_format((float) $reservation->subtotal, 2) }}
                                </td>
                            </tr>
                        @else
                            <tr>
                                <td colspan="5" class="py-4 px-4 text-center text-slate-400 italic">No line items recorded on this invoice.</td>
                            </tr>
                        @endif
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Calculations Summary (Right-Aligned) -->
        <div class="doc-summary flex justify-end my-3">
            <div class="w-full sm:w-80 space-y-1 text-xs">
                <div class="flex justify-between py-0.5 border-b border-slate-100">
                    <span class="text-slate-500">Gross Subtotal:</span>
                    <span class="font-mono font-semibold text-slate-800">₦{{ number_format((float) $invoice->subtotal, 2) }}</span>
                </div>

                @if((float) $invoice->discount_amount > 0)
                    <div class="flex justify-between py-0.5 border-b border-slate-100 text-emerald-600">
                        <span>Special Discount:</span>
                        <span class="font-mono font-semibold">-₦{{ number_format((float) $invoice->discount_amount, 2) }}</span>
                    </div>
                @endif

                @if((float) $invoice->vat_amount > 0)
                    <div class="flex justify-between py-0.5 border-b border-slate-100">
                        <span class="text-slate-500">Statutory VAT (7.5%):</span>
                        <span class="font-mono font-medium">₦{{ number_format((float) $invoice->vat_amount, 2) }}</span>
                    </div>
                @endif

                @if((float) $invoice->processing_fee > 0)
                    <div class="flex justify-between py-0.5 border-b border-slate-100">
                        <span class="text-slate-500">Processing Fee:</span>
                        <span class="font-mono font-medium">₦{{ number_format((float) $invoice->processing_fee, 2) }}</span>
                    </div>
                @endif

                @if((float) $invoice->caution_fee > 0)
                    <div class="flex justify-between py-0.5 border-b border-slate-100 text-slate-800">
                        <span class="text-slate-600 font-medium">Refundable Caution Deposit:</span>
                        <span class="font-mono font-semibold">₦{{ number_format((float) $invoice->caution_fee, 2) }}</span>
                    </div>
                @endif

                <div class="flex justify-between py-1 border-b-2 border-slate-200 text-xs font-bold text-slate-900">
                    <span>Net Total Payable:</span>
                    <span class="font-mono">₦{{ number_format((float) $invoice->total_amount, 2) }}</span>
                </div>

                <div class="flex justify-between py-0.5 border-b border-slate-100 text-emerald-700 text-xs font-bold">
                    <span>Total Amount Settled:</span>
                    <span class="font-mono">₦{{ number_format((float) $invoice->paid_amount, 2) }}</span>
                </div>

                <!-- Balance Due Highlight Banner -->
                <div class="bg-primary text-white rounded-lg p-2.5 flex justify-between items-center shadow-sm mt-1" style="background-color: #0B2D5B; color: #ffffff;">
                    <span class="font-bold text-xs uppercase tracking-wider text-slate-200">Balance Due</span>
                    <span class="text-sm sm:text-base font-black font-mono text-amber-400" style="color: #F59E0B;">
                        ₦{{ number_format((float) $invoice->balance, 2) }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Bank Payment Details Box (Gold Frame with Diamond Header) -->
        <div class="doc-bank-box rounded-xl p-3 sm:p-4 my-3 text-xs text-slate-800" style="border: 1.5px solid #D97706; background-color: #FFFBEB;">
            <div class="font-black uppercase tracking-wider mb-1.5 text-amber-800 flex items-center gap-1.5 text-[11px]">
                <span class="text-xs">◆</span> BANK PAYMENT DETAILS & SETTLEMENT
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                <div>
                    <span class="text-slate-500 block text-[10px]">Bank:</span>
                    <strong class="text-slate-900 font-semibold">{{ $bankName }}</strong>
                </div>
                <div>
                    <span class="text-slate-500 block text-[10px]">Account Number:</span>
                    <strong class="text-slate-900 font-mono text-xs tracking-wider font-bold">{{ $accountNumber }}</strong>
                </div>
                <div>
                    <span class="text-slate-500 block text-[10px]">Account Name:</span>
                    <strong class="text-slate-900 font-semibold">{{ $accountName }}</strong>
                </div>
            </div>
            <div class="mt-2 pt-2 border-t border-amber-200/80 flex flex-wrap items-center justify-between gap-1">
                <div class="text-[10px] text-slate-700">
                    Payment Reference: <span class="bg-primary text-white px-2 py-0.5 rounded font-mono font-bold tracking-wide text-[10px]" style="background-color: #0B2D5B; color: #ffffff;">{{ $bookingRef }}</span> (Quote on all transfers)
                </div>
            </div>
        </div>

        <!-- Notes Box -->
        <div class="doc-note-box bg-slate-50 border border-slate-200 rounded-xl p-3 my-3 text-[11px] text-slate-600 leading-snug" style="border-left: 3px solid #D97706;">
            <strong class="text-slate-900 uppercase tracking-wider block mb-0.5 text-[10px]">Important Payment Terms</strong>
            <p>Payment is strictly due by the stated due date. For billing inquiries or transfer confirmations, please contact <a href="mailto:{{ $siteEmail }}" class="text-primary font-semibold underline">{{ $siteEmail }}</a> quoting Invoice #{{ $invoice->invoice_number }}.</p>
            @if($invoice->notes)
                <p class="mt-1 text-slate-700 font-medium italic">Note: {{ $invoice->notes }}</p>
            @endif
        </div>

        <!-- Official Signatures & Status Stamp Block -->
        <div class="doc-signatures grid grid-cols-1 sm:grid-cols-3 gap-4 pt-4 border-t border-slate-200 items-center text-center">
            <!-- Left: Finance Desk -->
            <div>
                <div class="h-7 flex items-end justify-center">
                    <span class="font-serif italic text-slate-700 text-xs border-b border-slate-400 pb-0.5 px-4">Billing Officer</span>
                </div>
                <p class="text-[11px] font-bold text-slate-900 mt-0.5">Authorized Billing Desk</p>
                <p class="text-[9px] text-slate-500">G-Briel Property Accounts</p>
            </div>

            <!-- Center: Status Stamp Badge -->
            <div class="flex justify-center">
                @if($isPaidFull)
                    <div class="inline-block border-2 border-emerald-600 bg-emerald-50 text-emerald-800 rounded-lg px-3 py-1.5 font-black text-[11px] uppercase tracking-wider shadow-sm" style="border: 2px dashed #059669;">
                        <div>✔ PAID IN FULL</div>
                        <div class="text-[9px] font-medium text-emerald-700">Confirmed: {{ now()->format('d M Y') }}</div>
                    </div>
                @elseif($invoice->status === 'overdue')
                    <div class="inline-block border-2 border-red-600 bg-red-50 text-red-800 rounded-lg px-3 py-1.5 font-black text-[11px] uppercase tracking-wider shadow-sm" style="border: 2px dashed #DC2626;">
                        <div>⚠ OVERDUE</div>
                        <div class="text-[9px] font-medium text-red-700">Past Due Date</div>
                    </div>
                @else
                    <div class="inline-block border-2 border-amber-600 bg-amber-50 text-amber-800 rounded-lg px-3 py-1.5 font-black text-[11px] uppercase tracking-wider shadow-sm" style="border: 2px dashed #D97706;">
                        <div>⏳ PAYMENT PENDING</div>
                        <div class="text-[9px] font-medium text-amber-700">Due: {{ $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('d M Y') : now()->addDays(7)->format('d M Y') }}</div>
                    </div>
                @endif
            </div>

            <!-- Right: General Manager Signature -->
            <div>
                <div class="h-7 flex items-end justify-center">
                    <span class="font-serif italic text-slate-700 text-xs border-b border-slate-400 pb-0.5 px-4">G-Briel Property Executive</span>
                </div>
                <p class="text-[11px] font-bold text-slate-900 mt-0.5">Managing Director</p>
                <p class="text-[9px] text-slate-500">BookTala Residential Portfolio</p>
            </div>
        </div>

        <!-- Footer Note -->
        <div class="doc-footer mt-4 pt-3 border-t border-slate-100 text-center text-[9px] text-slate-400 space-y-0.5">
            <p class="italic text-slate-500">"...stays, travels &amp; better living"</p>
            <p>Generated: {{ now()->format('d/m/Y, H:i:s') }} | &copy; {{ date('Y') }} {{ $siteName }}. All Rights Reserved. A brand of {{ $companyName }}.</p>
        </div>
    </div>
</div>
@endsection
