@extends($layout)

@section('title', 'Booking Receipt - ' . $reservation->booking_ref)

@php
    $siteName = \App\Models\Setting::get('site_name', config('brand.name'));
    $siteEmail = \App\Models\Setting::get('email', config('brand.email'));
    $sitePhone = \App\Models\Setting::get('phone', config('brand.phone'));
    $siteAddress = \App\Models\Setting::get('address', config('brand.address'));
    $companyName = \App\Models\Setting::get('company_name', 'G-Briel Property Ltd');
    $bankName = \App\Models\Setting::get('bank_name', 'First Bank of Nigeria / Providus Bank');
    $accountNumber = \App\Models\Setting::get('account_number', '0123456789');
    $accountName = \App\Models\Setting::get('account_name', 'BookTala (G-Briel Property Ltd)');

    $guestName = $reservation->guest?->name ?? $reservation->user?->name ?? 'Valued Guest';
    $guestPhone = $reservation->guest?->phone ?? $reservation->user?->phone ?? 'N/A';
    $guestEmail = $reservation->guest?->email ?? $reservation->user?->email ?? '';
    $unitTitle = $reservation->unit?->title ?? $reservation->property?->title ?? $reservation->property?->name ?? 'Luxury Apartment';
    $siteLocation = $reservation->unit?->site?->name ?? ($reservation->property?->city ? $reservation->property?->city . ', ' . $reservation->property?->state : 'Lagos, Nigeria');
    $collector = $reservation->payments->first()?->collector?->name ?? $reservation->createdBy?->name ?? 'Front Desk Manager';
    $collectedAt = $reservation->payments->first()?->collected_at ?? $reservation->created_at;
    $isPaidFull = (float) $invoice->balance <= 0.01;
    $logoUrl = asset('images/booktala-logo.png');
@endphp

@push('styles')
<style>
    /* Robust screen flexbox layout that never squishes */
    .receipt-sheet {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        color: #1e293b;
        background: #ffffff;
        box-sizing: border-box;
    }
    .doc-info-row {
        display: flex;
        flex-direction: row;
        flex-wrap: wrap;
        gap: 16px;
        margin: 16px 0;
        width: 100%;
        box-sizing: border-box;
    }
    .doc-info-billto {
        flex: 1 1 54%;
        min-width: 280px;
        box-sizing: border-box;
    }
    .doc-info-meta {
        flex: 1 1 38%;
        min-width: 260px;
        box-sizing: border-box;
    }
    @media (max-width: 768px) {
        .doc-info-row {
            flex-direction: column;
        }
        .doc-info-billto,
        .doc-info-meta {
            flex: 1 1 100%;
            min-width: 100%;
        }
    }

    /* Print-specific layout isolation: HIDE EVERYTHING EXCEPT THE RECEIPT (STRICT 1-PAGE) */
    @media print {
        @page {
            size: A4 portrait;
            margin: 6mm 8mm;
        }

        /* Hide all website/admin UI chrome */
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
        button {
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

        .receipt-container-wrapper {
            margin: 0 !important;
            padding: 0 !important;
            max-width: 100% !important;
            width: 100% !important;
        }

        .receipt-sheet {
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
<div class="receipt-container-wrapper max-w-4xl mx-auto py-2 sm:py-4 px-2 sm:px-4 space-y-4">

    @if(session('success'))
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-emerald-800 text-sm flex items-center justify-between print:hidden shadow-sm">
            <div class="flex items-center gap-2">
                <i class="fas fa-check-circle text-emerald-500"></i>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-800">&times;</button>
        </div>
    @endif

    <!-- Screen-only Action Toolbar -->
    <div class="flex flex-wrap items-center justify-between gap-3 bg-white border border-gray-200 rounded-xl p-4 shadow-sm print:hidden">
        <div class="flex items-center gap-3">
            <a href="{{ route('frontdesk.reservations.create') }}" class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition">
                <i class="fas fa-plus"></i> New Walk-In
            </a>
            <span class="text-gray-300">|</span>
            <span class="text-xs text-gray-500">Official Guest Folio & Payment Voucher</span>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" onclick="window.print()" class="inline-flex items-center gap-2 px-4 py-2 bg-primary hover:bg-primary/90 text-white rounded-lg text-xs font-bold transition shadow-sm" title="Print clean receipt without website heading">
                <i class="fas fa-print"></i> Print Receipt
            </button>
            @if(Route::has('frontdesk.reservations.receipt.pdf'))
                <a href="{{ route('frontdesk.reservations.receipt.pdf', $reservation) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-accent hover:bg-accent/90 text-white rounded-lg text-xs font-bold transition shadow-sm">
                    <i class="fas fa-file-pdf"></i> Download PDF
                </a>
            @endif
        </div>
    </div>

    <!-- Official Printable Receipt Document -->
    <div id="printable-receipt" class="receipt-sheet rounded-2xl border border-gray-200 bg-white p-6 sm:p-8 shadow-lg">
        
        <!-- Document Header -->
        <div class="doc-header flex flex-col sm:flex-row justify-between items-start gap-4 border-b-2 border-slate-100 pb-4">
            <!-- Left: Brand Logo & Legal Entity -->
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
                    <div class="text-[10px] font-bold tracking-widest text-slate-200 uppercase">OFFICIAL RECEIPT</div>
                    <div class="text-sm sm:text-base font-black tracking-wider text-amber-400 mt-0.5" style="color: #F59E0B;">
                        #{{ $reservation->booking_ref }}
                    </div>
                    <div class="text-[10px] text-slate-300 mt-0.5">
                        Invoice: <span class="font-mono text-white">{{ $invoice->invoice_number }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2-Column Information Cards: BILL TO & STAY DETAILS -->
        <div class="doc-info-row" style="display: flex; flex-direction: row; flex-wrap: wrap; gap: 16px; margin: 16px 0; width: 100%; box-sizing: border-box;">
            <!-- Left: BILL TO Card -->
            <div class="doc-info-billto doc-card bg-slate-50 border border-slate-200 rounded-xl p-3.5 sm:p-4 relative" style="flex: 1 1 54%; min-width: 280px; box-sizing: border-box; border-left: 4px solid #0B2D5B;">
                <div class="text-[10px] font-bold text-primary uppercase tracking-wider mb-1 flex items-center gap-1.5" style="color: #0B2D5B;">
                    <i class="fas fa-user-tag text-xs"></i> BILL TO / GUEST DETAILS
                </div>
                <h3 class="text-sm sm:text-base font-black text-slate-900 leading-tight">{{ $guestName }}</h3>
                <div class="mt-1.5 space-y-0.5 text-xs text-slate-600">
                    <p><span class="font-semibold text-slate-700">Guest ID:</span> BTG-{{ str_pad($reservation->guest_id ?? $reservation->user_id ?? 1, 4, '0', STR_PAD_LEFT) }}</p>
                    <p><span class="font-semibold text-slate-700">Phone:</span> {{ $guestPhone }}</p>
                    @if($guestEmail)
                        <p><span class="font-semibold text-slate-700">Email:</span> {{ $guestEmail }}</p>
                    @endif
                    <p><span class="font-semibold text-slate-700">Assigned Suite:</span> <span class="font-bold text-slate-900">{{ $unitTitle }}</span> ({{ $siteLocation }})</p>
                </div>
            </div>

            <!-- Right: STAY & RECEIPT METADATA Card -->
            <div class="doc-info-meta doc-card bg-white border border-slate-200 rounded-xl p-3.5 sm:p-4 text-xs text-slate-700 space-y-1.5" style="flex: 1 1 38%; min-width: 260px; box-sizing: border-box;">
                <div class="flex justify-between border-b border-slate-100 pb-1">
                    <span class="text-slate-500">Receipt Date:</span>
                    <strong class="text-slate-900">{{ $invoice->created_at ? $invoice->created_at->format('d M Y') : now()->format('d M Y') }}</strong>
                </div>
                <div class="flex justify-between border-b border-slate-100 pb-1">
                    <span class="text-slate-500">Check-In:</span>
                    <strong class="text-slate-900">{{ $reservation->check_in->format('d M Y') }} <span class="text-[10px] text-slate-500">(2:00 PM)</span></strong>
                </div>
                <div class="flex justify-between border-b border-slate-100 pb-1">
                    <span class="text-slate-500">Check-Out:</span>
                    <strong class="text-slate-900">{{ $reservation->check_out->format('d M Y') }} <span class="text-[10px] text-slate-500">(11:00 AM)</span></strong>
                </div>
                <div class="flex justify-between border-b border-slate-100 pb-1">
                    <span class="text-slate-500">Duration / Guests:</span>
                    <strong class="text-slate-900">{{ $reservation->nights }} Night(s) · {{ $reservation->guests }} Guest(s)</strong>
                </div>
                <div class="flex justify-between pt-0.5 items-center">
                    <span class="text-slate-500">Payment Status:</span>
                    @if($isPaidFull)
                        <span class="inline-flex items-center gap-1 bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded-full font-bold text-[10px] uppercase">
                            <i class="fas fa-check-circle text-xs"></i> Paid in Full
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 bg-amber-100 text-amber-800 px-2 py-0.5 rounded-full font-bold text-[10px] uppercase">
                            <i class="fas fa-clock text-xs"></i> Partial / Due
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
                            <span class="text-primary font-black mr-1" style="color: #0B2D5B;">◆</span> ACCOMMODATION & SERVICES CHARGED
                        </td>
                    </tr>

                    @php $sn = 1; @endphp
                    @if($invoice->items && $invoice->items->count())
                        @foreach($invoice->items as $item)
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
                        @endforeach
                    @else
                        <!-- Primary Accommodation Line Item -->
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

                        <!-- VAT Line Item -->
                        @if((float) $reservation->tax_amount > 0)
                            <tr class="hover:bg-slate-50/50">
                                <td class="py-2 px-3 text-center text-slate-500 font-mono">2</td>
                                <td class="py-2 px-4">
                                    <span class="font-semibold text-slate-800">Value Added Tax (VAT 7.5%)</span>
                                </td>
                                <td class="py-2 px-3 text-center font-mono">1</td>
                                <td class="py-2 px-4 text-right font-mono">₦{{ number_format((float) $reservation->tax_amount, 2) }}</td>
                                <td class="py-2 px-4 text-right font-mono font-semibold text-slate-900">₦{{ number_format((float) $reservation->tax_amount, 2) }}</td>
                            </tr>
                        @endif

                        <!-- Processing Fee -->
                        @if((float) $reservation->processing_fee > 0)
                            <tr class="hover:bg-slate-50/50">
                                <td class="py-2 px-3 text-center text-slate-500 font-mono">3</td>
                                <td class="py-2 px-4">
                                    <span class="font-semibold text-slate-800">Guest Verification & Service Fee</span>
                                </td>
                                <td class="py-2 px-3 text-center font-mono">1</td>
                                <td class="py-2 px-4 text-right font-mono">₦{{ number_format((float) $reservation->processing_fee, 2) }}</td>
                                <td class="py-2 px-4 text-right font-mono font-semibold text-slate-900">₦{{ number_format((float) $reservation->processing_fee, 2) }}</td>
                            </tr>
                        @endif

                        <!-- Refundable Caution Deposit -->
                        @if((float) $reservation->caution_fee > 0)
                            <tr class="hover:bg-slate-50/50">
                                <td class="py-2 px-3 text-center text-slate-500 font-mono">4</td>
                                <td class="py-2 px-4">
                                    <span class="font-bold text-slate-900">Refundable Caution Deposit (Escrow)</span>
                                    <div class="text-[10px] text-amber-700">Refundable post-checkout inspection within 24 hours.</div>
                                </td>
                                <td class="py-2 px-3 text-center font-mono">1</td>
                                <td class="py-2 px-4 text-right font-mono">₦{{ number_format((float) $reservation->caution_fee, 2) }}</td>
                                <td class="py-2 px-4 text-right font-mono font-bold text-slate-900">₦{{ number_format((float) $reservation->caution_fee, 2) }}</td>
                            </tr>
                        @endif
                    @endif
                </tbody>
            </table>
        </div>

        <!-- Calculations Summary (Right-Aligned) -->
        <div class="doc-summary flex justify-end my-3">
            <div class="w-full sm:w-80 space-y-1 text-xs">
                <div class="flex justify-between py-0.5 border-b border-slate-100">
                    <span class="text-slate-500">Gross Accommodation Subtotal:</span>
                    <span class="font-mono font-semibold text-slate-800">₦{{ number_format((float) $reservation->subtotal, 2) }}</span>
                </div>

                @if((float) $reservation->discount_amount > 0)
                    <div class="flex justify-between py-0.5 border-b border-slate-100 text-emerald-600">
                        <span>Applied Discount:</span>
                        <span class="font-mono font-semibold">-₦{{ number_format((float) $reservation->discount_amount, 2) }}</span>
                    </div>
                @endif

                @if((float) $reservation->tax_amount > 0)
                    <div class="flex justify-between py-0.5 border-b border-slate-100">
                        <span class="text-slate-500">Statutory VAT (7.5%):</span>
                        <span class="font-mono font-medium">₦{{ number_format((float) $reservation->tax_amount, 2) }}</span>
                    </div>
                @endif

                @if((float) $reservation->processing_fee > 0)
                    <div class="flex justify-between py-0.5 border-b border-slate-100">
                        <span class="text-slate-500">Processing Fee:</span>
                        <span class="font-mono font-medium">₦{{ number_format((float) $reservation->processing_fee, 2) }}</span>
                    </div>
                @endif

                @if((float) $reservation->caution_fee > 0)
                    <div class="flex justify-between py-0.5 border-b border-slate-100 text-slate-800">
                        <span class="text-slate-600 font-medium">Refundable Caution Deposit:</span>
                        <span class="font-mono font-semibold">₦{{ number_format((float) $reservation->caution_fee, 2) }}</span>
                    </div>
                @endif

                <div class="flex justify-between py-1 border-b-2 border-slate-200 text-xs font-bold text-slate-900">
                    <span>Net Total Payable:</span>
                    <span class="font-mono">₦{{ number_format((float) $invoice->total_amount, 2) }}</span>
                </div>

                <div class="flex justify-between py-0.5 border-b border-slate-100 text-emerald-700 text-xs font-bold">
                    <span>Total Amount Settled / Received:</span>
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
                    Payment Reference: <span class="bg-primary text-white px-2 py-0.5 rounded font-mono font-bold tracking-wide text-[10px]" style="background-color: #0B2D5B; color: #ffffff;">{{ $reservation->booking_ref }}</span> (Quote on all transfers)
                </div>
            </div>
        </div>

        <!-- Caution Deposit & Checkout Policy Note Box -->
        <div class="doc-note-box bg-slate-50 border border-slate-200 rounded-xl p-3 my-3 text-[11px] text-slate-600 leading-snug" style="border-left: 3px solid #D97706;">
            <strong class="text-slate-900 uppercase tracking-wider block mb-0.5 text-[10px]">Caution Deposit & Stay Policies</strong>
            <p>The refundable caution deposit is held securely in escrow and released within 24 hours after checkout inspection. Standard check-in begins at 2:00 PM; check-out is strictly by 11:00 AM. For billing questions or concierge assistance, reach out to <a href="mailto:{{ $siteEmail }}" class="text-primary font-semibold underline">{{ $siteEmail }}</a>.</p>
        </div>

        <!-- Official Signatures & Status Stamp Block -->
        <div class="doc-signatures grid grid-cols-1 sm:grid-cols-3 gap-4 pt-4 border-t border-slate-200 items-center text-center">
            <!-- Left: Front Desk Cashier / Officer -->
            <div>
                <div class="h-7 flex items-end justify-center">
                    <span class="font-serif italic text-slate-700 text-xs border-b border-slate-400 pb-0.5 px-4">{{ $collector }}</span>
                </div>
                <p class="text-[11px] font-bold text-slate-900 mt-0.5">Front Desk Cashier / Officer</p>
                <p class="text-[9px] text-slate-500">Collected: {{ $collectedAt ? \Carbon\Carbon::parse($collectedAt)->format('d M Y, h:ia') : 'At Reception' }}</p>
            </div>

            <!-- Center: Status Stamp Badge -->
            <div class="flex justify-center">
                @if($isPaidFull)
                    <div class="inline-block border-2 border-emerald-600 bg-emerald-50 text-emerald-800 rounded-lg px-3 py-1.5 font-black text-[11px] uppercase tracking-wider shadow-sm" style="border: 2px dashed #059669;">
                        <div>✔ PAYMENT COMPLETED</div>
                        <div class="text-[9px] font-medium text-emerald-700">Official Stamped Receipt</div>
                    </div>
                @else
                    <div class="inline-block border-2 border-amber-600 bg-amber-50 text-amber-800 rounded-lg px-3 py-1.5 font-black text-[11px] uppercase tracking-wider shadow-sm" style="border: 2px dashed #D97706;">
                        <div>⚠ PARTIAL PAYMENT</div>
                        <div class="text-[9px] font-medium text-amber-700">Balance Pending</div>
                    </div>
                @endif
            </div>

            <!-- Right: Guest Acceptance -->
            <div>
                <div class="h-7 flex items-end justify-center">
                    <span class="font-serif italic text-slate-700 text-xs border-b border-slate-400 pb-0.5 px-4">{{ $guestName }}</span>
                </div>
                <p class="text-[11px] font-bold text-slate-900 mt-0.5">Guest Signature / Acceptance</p>
                <p class="text-[9px] text-slate-500">Folio Acknowledged</p>
            </div>
        </div>

        <!-- Document Footer -->
        <div class="doc-footer mt-4 pt-3 border-t border-slate-100 text-center text-[9px] text-slate-400 space-y-0.5">
            <p class="italic text-slate-500">"...stays, travels &amp; better living"</p>
            <p>Generated: {{ now()->format('d/m/Y, H:i:s') }} | &copy; {{ date('Y') }} {{ $siteName }}. All Rights Reserved. A brand of {{ $companyName }}.</p>
        </div>
    </div>
</div>
@endsection
