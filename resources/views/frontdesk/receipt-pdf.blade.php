<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Receipt #{{ $reservation->booking_ref }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 10mm 12mm;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, "Helvetica Neue", Helvetica, sans-serif;
            font-size: 11px;
            color: #1e293b;
            line-height: 1.4;
            background: #ffffff;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table td {
            vertical-align: top;
        }
        .logo-img {
            max-height: 52px;
            width: auto;
        }
        .company-title {
            font-size: 16px;
            font-weight: bold;
            color: #0B2D5B;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .company-sub {
            font-size: 9px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: bold;
            margin-top: 2px;
        }
        .company-contact {
            font-size: 9.5px;
            color: #475569;
            margin-top: 4px;
            line-height: 1.35;
        }
        .receipt-badge {
            background-color: #0B2D5B;
            color: #ffffff;
            padding: 10px 14px;
            border-radius: 8px;
            text-align: right;
        }
        .receipt-badge .title {
            font-size: 10px;
            font-weight: bold;
            letter-spacing: 1.5px;
            color: #cbd5e1;
            text-transform: uppercase;
        }
        .receipt-badge .ref {
            font-size: 14px;
            font-weight: bold;
            color: #F59E0B;
            margin-top: 2px;
        }
        .receipt-badge .inv-no {
            font-size: 8.5px;
            color: #94a3b8;
            margin-top: 2px;
        }

        /* 2-Column Info Cards */
        .info-table {
            margin-top: 14px;
            margin-bottom: 14px;
        }
        .info-card {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px 12px;
            vertical-align: top;
        }
        .bill-to-card {
            background-color: #f8fafc;
            border-left: 4px solid #0B2D5B;
        }
        .card-header {
            font-size: 9px;
            font-weight: bold;
            color: #0B2D5B;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }
        .guest-name {
            font-size: 13px;
            font-weight: bold;
            color: #0f172a;
            margin-bottom: 4px;
        }
        .card-meta-line {
            font-size: 9.5px;
            color: #475569;
            margin-bottom: 2px;
        }
        .card-meta-line strong {
            color: #1e293b;
        }
        .meta-table td {
            padding: 2.5px 0;
            font-size: 9.5px;
            border-bottom: 1px solid #f1f5f9;
        }
        .meta-table tr:last-child td {
            border-bottom: none;
        }

        /* Line Items Table */
        .items-table {
            margin-top: 10px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            overflow: hidden;
        }
        .items-table th {
            background-color: #0B2D5B;
            color: #ffffff;
            font-size: 9.5px;
            font-weight: bold;
            padding: 7px 8px;
            text-align: left;
        }
        .items-table td {
            padding: 7px 8px;
            font-size: 9.5px;
            border-bottom: 1px solid #e2e8f0;
            color: #1e293b;
        }
        .section-row td {
            background-color: #f1f5f9;
            font-weight: bold;
            font-size: 8.5px;
            letter-spacing: 0.5px;
            color: #334155;
            padding: 5px 8px;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }

        /* Summary Block */
        .summary-wrapper {
            margin-top: 10px;
            margin-bottom: 12px;
        }
        .summary-table {
            width: 320px;
            float: right;
        }
        .summary-table td {
            padding: 3px 6px;
            font-size: 9.5px;
            border-bottom: 1px solid #f1f5f9;
        }
        .balance-banner {
            background-color: #0B2D5B;
            color: #ffffff;
            font-weight: bold;
            border-radius: 5px;
        }
        .balance-banner td {
            padding: 6px 10px;
            font-size: 11px;
            border-bottom: none;
        }
        .balance-amount {
            color: #F59E0B;
            font-size: 13px;
            font-weight: bold;
            text-align: right;
        }

        /* Clearfix */
        .clearfix { clear: both; }

        /* Bank Details Box */
        .bank-box {
            border: 1.5px solid #D97706;
            background-color: #FFFBEB;
            border-radius: 6px;
            padding: 8px 10px;
            margin-top: 8px;
            margin-bottom: 10px;
            font-size: 9.5px;
        }
        .bank-header {
            font-size: 9.5px;
            font-weight: bold;
            color: #B45309;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .bank-table td {
            padding: 2px 4px;
            font-size: 9.5px;
        }
        .ref-pill {
            background-color: #0B2D5B;
            color: #ffffff;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: bold;
        }

        /* Note Box */
        .note-box {
            background-color: #f8fafc;
            border-left: 3px solid #D97706;
            border-top: 1px solid #e2e8f0;
            border-right: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 7px 10px;
            margin-bottom: 14px;
            font-size: 8.5px;
            color: #475569;
            line-height: 1.35;
        }
        .note-box strong { color: #0f172a; }

        /* Signatures & Stamp */
        .signatures-table {
            margin-top: 8px;
            border-top: 1px solid #cbd5e1;
            padding-top: 10px;
        }
        .signatures-table td {
            vertical-align: top;
            text-align: center;
            font-size: 9px;
        }
        .sig-line {
            display: inline-block;
            border-bottom: 1px solid #64748b;
            width: 140px;
            height: 18px;
            font-style: italic;
            font-size: 10px;
            color: #334155;
        }
        .stamp-badge {
            display: inline-block;
            border: 2px dashed #059669;
            background-color: #f0fdf4;
            color: #065f46;
            padding: 5px 12px;
            border-radius: 6px;
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .stamp-due {
            border-color: #dc2626;
            background-color: #fef2f2;
            color: #991b1b;
        }

        /* Footer */
        .doc-footer {
            margin-top: 12px;
            border-top: 1px solid #f1f5f9;
            padding-top: 6px;
            text-align: center;
            font-size: 8px;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    @php
        $guestName = $reservation->guest?->name ?? $reservation->user?->name ?? 'Valued Guest';
        $guestPhone = $reservation->guest?->phone ?? $reservation->user?->phone ?? 'N/A';
        $guestEmail = $reservation->guest?->email ?? $reservation->user?->email ?? '';
        $unitTitle = $reservation->unit?->title ?? $reservation->property?->title ?? $reservation->property?->name ?? 'Luxury Apartment';
        $siteLocation = $reservation->unit?->site?->name ?? ($reservation->property?->city ? $reservation->property?->city . ', ' . $reservation->property?->state : 'Lagos, Nigeria');
        $collector = $reservation->payments->first()?->collector?->name ?? $reservation->createdBy?->name ?? 'Front Desk Operations';
        $isPaidFull = (float) $invoice->balance <= 0.01;
    @endphp

    <!-- Header Block -->
    <table class="header-table">
        <tr>
            <td style="width: 65%;">
                <table>
                    <tr>
                        @if(!empty($logoBase64))
                            <td style="width: 65px; vertical-align: top;">
                                <img src="{{ $logoBase64 }}" class="logo-img" alt="Logo">
                            </td>
                        @endif
                        <td style="vertical-align: top; padding-left: 6px;">
                            <div class="company-title">{{ $siteName ?? 'BookTala' }} Luxury Stays</div>
                            <div class="company-sub">Operated by {{ $companyName ?? 'G-Briel Property Ltd' }}</div>
                            <div class="company-contact">
                                {{ $siteAddress ?? 'Plot 14, Admiralty Way, Lekki Phase 1, Lagos, Nigeria' }}<br>
                                Tel: {{ $sitePhone ?? '+234 814 000 8252' }} &nbsp;|&nbsp; Email: {{ $siteEmail ?? 'concierge@booktala.com' }}
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
            <td style="width: 35%; text-align: right;">
                <div class="receipt-badge">
                    <div class="title">OFFICIAL RECEIPT</div>
                    <div class="ref">#{{ $reservation->booking_ref }}</div>
                    <div class="inv-no">Invoice Ref: {{ $invoice->invoice_number }}</div>
                </div>
            </td>
        </tr>
    </table>

    <!-- 2-Column Information Cards -->
    <table class="info-table">
        <tr>
            <!-- Left: BILL TO -->
            <td style="width: 55%; padding-right: 8px;">
                <div class="info-card bill-to-card">
                    <div class="card-header">BILL TO / GUEST DETAILS</div>
                    <div class="guest-name">{{ $guestName }}</div>
                    <div class="card-meta-line"><strong>Guest ID:</strong> BTG-{{ str_pad($reservation->guest_id ?? $reservation->user_id ?? 1, 4, '0', STR_PAD_LEFT) }}</div>
                    <div class="card-meta-line"><strong>Phone:</strong> {{ $guestPhone }}</div>
                    @if($guestEmail)
                        <div class="card-meta-line"><strong>Email:</strong> {{ $guestEmail }}</div>
                    @endif
                    <div class="card-meta-line"><strong>Assigned Suite:</strong> {{ $unitTitle }} ({{ $siteLocation }})</div>
                </div>
            </td>

            <!-- Right: STAY & RECEIPT DETAILS -->
            <td style="width: 45%; padding-left: 8px;">
                <div class="info-card" style="background-color: #ffffff;">
                    <div class="card-header" style="color: #475569;">STAY & RESERVATION DETAILS</div>
                    <table class="meta-table">
                        <tr>
                            <td style="color: #64748b;">Receipt Date:</td>
                            <td style="text-align: right; font-weight: bold;">{{ $invoice->created_at ? $invoice->created_at->format('d M Y') : now()->format('d M Y') }}</td>
                        </tr>
                        <tr>
                            <td style="color: #64748b;">Check-In:</td>
                            <td style="text-align: right; font-weight: bold;">{{ $reservation->check_in->format('d M Y') }} (2:00 PM)</td>
                        </tr>
                        <tr>
                            <td style="color: #64748b;">Check-Out:</td>
                            <td style="text-align: right; font-weight: bold;">{{ $reservation->check_out->format('d M Y') }} (11:00 AM)</td>
                        </tr>
                        <tr>
                            <td style="color: #64748b;">Stay Duration:</td>
                            <td style="text-align: right; font-weight: bold;">{{ $reservation->nights }} Night(s) · {{ $reservation->guests }} Guest(s)</td>
                        </tr>
                        <tr>
                            <td style="color: #64748b;">Folio Status:</td>
                            <td style="text-align: right; font-weight: bold; color: {{ $isPaidFull ? '#059669' : '#d97706' }};">
                                {{ $isPaidFull ? 'PAID IN FULL' : 'PARTIAL / BALANCE DUE' }}
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <!-- Line Items Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th class="text-center" style="width: 6%;">S/N</th>
                <th style="width: 52%;">Description</th>
                <th class="text-center" style="width: 14%;">Qty / Nights</th>
                <th class="text-right" style="width: 14%;">Rate (₦)</th>
                <th class="text-right" style="width: 14%;">Amount (₦)</th>
            </tr>
        </thead>
        <tbody>
            <tr class="section-row">
                <td colspan="5">◆ ACCOMMODATION & RESIDENCE CHARGES</td>
            </tr>
            @php $sn = 1; @endphp
            @forelse($invoice->items as $item)
                <tr>
                    <td class="text-center" style="color: #64748b;">{{ $sn++ }}</td>
                    <td><strong>{{ $item->description }}</strong></td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-right">₦{{ number_format((float) ($item->unit_price ?? ($item->quantity ? $item->total / $item->quantity : $item->total)), 2) }}</td>
                    <td class="text-right" style="font-weight: bold;">₦{{ number_format((float) $item->total, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td class="text-center" style="color: #64748b;">1</td>
                    <td><strong>Accommodation Stay: {{ $unitTitle }}</strong></td>
                    <td class="text-center">{{ $reservation->nights }}</td>
                    <td class="text-right">₦{{ number_format((float) ($reservation->nights ? $reservation->subtotal / $reservation->nights : $reservation->subtotal), 2) }}</td>
                    <td class="text-right" style="font-weight: bold;">₦{{ number_format((float) $reservation->subtotal, 2) }}</td>
                </tr>
                @if((float) $reservation->processing_fee > 0)
                    <tr>
                        <td class="text-center" style="color: #64748b;">2</td>
                        <td><strong>Processing Fee (Non-refundable)</strong></td>
                        <td class="text-center">1</td>
                        <td class="text-right">₦{{ number_format((float) $reservation->processing_fee, 2) }}</td>
                        <td class="text-right" style="font-weight: bold;">₦{{ number_format((float) $reservation->processing_fee, 2) }}</td>
                    </tr>
                @endif
                @if((float) $reservation->caution_fee > 0)
                    <tr>
                        <td class="text-center" style="color: #64748b;">3</td>
                        <td><strong>Refundable Caution Deposit (Held in Escrow)</strong></td>
                        <td class="text-center">1</td>
                        <td class="text-right">₦{{ number_format((float) $reservation->caution_fee, 2) }}</td>
                        <td class="text-right" style="font-weight: bold;">₦{{ number_format((float) $reservation->caution_fee, 2) }}</td>
                    </tr>
                @endif
            @endforelse
        </tbody>
    </table>

    <!-- Calculations Summary Table -->
    <div class="summary-wrapper">
        <table class="summary-table">
            <tr>
                <td style="color: #64748b;">Gross Accommodation Bill:</td>
                <td class="text-right">₦{{ number_format((float) ($invoice->subtotal ?? $reservation->subtotal), 2) }}</td>
            </tr>
            @if((float) $invoice->discount_amount > 0)
                <tr style="color: #059669;">
                    <td>Total Promo Discount:</td>
                    <td class="text-right" style="font-weight: bold;">-₦{{ number_format((float) $invoice->discount_amount, 2) }}</td>
                </tr>
            @endif
            @if((float) $invoice->vat_amount > 0)
                <tr>
                    <td style="color: #64748b;">Statutory VAT (7.5%):</td>
                    <td class="text-right">₦{{ number_format((float) $invoice->vat_amount, 2) }}</td>
                </tr>
            @endif
            @if((float) $invoice->processing_fee > 0)
                <tr>
                    <td style="color: #64748b;">Processing Fee:</td>
                    <td class="text-right">₦{{ number_format((float) $invoice->processing_fee, 2) }}</td>
                </tr>
            @endif
            @if((float) $invoice->caution_fee > 0)
                <tr>
                    <td style="color: #334155;">Refundable Caution Deposit:</td>
                    <td class="text-right" style="font-weight: bold;">₦{{ number_format((float) $invoice->caution_fee, 2) }}</td>
                </tr>
            @endif
            <tr style="font-weight: bold; border-top: 1.5px solid #cbd5e1;">
                <td style="font-size: 10px;">Net Total Payable:</td>
                <td class="text-right" style="font-size: 10px;">₦{{ number_format((float) $invoice->total_amount, 2) }}</td>
            </tr>
            <tr style="color: #059669; font-weight: bold;">
                <td>Total Amount Received:</td>
                <td class="text-right">₦{{ number_format((float) $invoice->paid_amount, 2) }}</td>
            </tr>
            <tr class="balance-banner">
                <td>BALANCE DUE:</td>
                <td class="balance-amount">₦{{ number_format((float) $invoice->balance, 2) }}</td>
            </tr>
        </table>
        <div class="clearfix"></div>
    </div>

    <!-- Bank Payment Details Box -->
    <div class="bank-box">
        <div class="bank-header">◆ BANK PAYMENT DETAILS & SETTLEMENT</div>
        <table class="bank-table">
            <tr>
                <td style="width: 33%;"><strong>Bank:</strong> {{ $bankName ?? 'First Bank of Nigeria / Providus Bank' }}</td>
                <td style="width: 33%;"><strong>Account No:</strong> {{ $accountNumber ?? '0123456789' }}</td>
                <td style="width: 34%;"><strong>Account Name:</strong> {{ $accountName ?? 'BookTala (G-Briel Property Ltd)' }}</td>
            </tr>
        </table>
        <div style="margin-top: 4px; padding-top: 4px; border-top: 1px solid #fde68a; font-size: 9px; color: #78350f;">
            Payment Reference: <span class="ref-pill">{{ $reservation->booking_ref }}</span> (Quote this reference on all bank transfers)
            @if($reservation->payments->isNotEmpty() && $reservation->payments->first()->paystack_reference)
                &nbsp;|&nbsp; Paystack Ref: <strong>{{ $reservation->payments->first()->paystack_reference }}</strong>
            @endif
        </div>
    </div>

    <!-- Important Notes Box -->
    <div class="note-box">
        <strong>Important Guest Notes:</strong> Check-in commences strictly at 2:00 PM (14:00 WAT) and check-out is strictly by 11:00 AM (11:00 WAT). The refundable caution deposit of ₦{{ number_format((float) $reservation->caution_fee, 2) }} is held in escrow and released to your bank account within 24–48 hours following turnover inspection. All indoor apartments are 100% smoke-free.
    </div>

    <!-- Signatures & Status Stamp Block -->
    <table class="signatures-table">
        <tr>
            <td style="width: 33%;">
                <div class="sig-line">{{ $collector }}</div>
                <div style="font-weight: bold; margin-top: 3px; color: #0f172a;">Authorized Front Desk</div>
                <div style="color: #64748b; font-size: 8px;">G-Briel Property Operations Desk</div>
            </td>
            <td style="width: 34%;">
                @if($isPaidFull)
                    <div class="stamp-badge">
                        <div>✔ PAYMENT COMPLETED</div>
                        <div style="font-size: 7.5px; font-weight: normal; margin-top: 1px;">Confirmed: {{ now()->format('d M Y') }}</div>
                    </div>
                @else
                    <div class="stamp-badge stamp-due">
                        <div>⚠ PAYMENT DUE</div>
                        <div style="font-size: 7.5px; font-weight: normal; margin-top: 1px;">Due: {{ $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('d M Y') : $reservation->check_in->format('d M Y') }}</div>
                    </div>
                @endif
            </td>
            <td style="width: 33%;">
                <div class="sig-line">G-Briel Property Executive</div>
                <div style="font-weight: bold; margin-top: 3px; color: #0f172a;">Head of Hospitality</div>
                <div style="color: #64748b; font-size: 8px;">BookTala Residential Portfolio</div>
            </td>
        </tr>
    </table>

    <!-- Document Footer -->
    <div class="doc-footer">
        <em>"...stays, travels &amp; better living"</em><br>
        Generated: {{ now()->format('d/m/Y, H:i:s') }} | &copy; {{ date('Y') }} {{ $siteName ?? 'BookTala' }}. All Rights Reserved. A brand of {{ $companyName ?? 'G-Briel Property Ltd' }}.
    </div>
</body>
</html>
