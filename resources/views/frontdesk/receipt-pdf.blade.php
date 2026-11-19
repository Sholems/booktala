<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Receipt #{{ $reservation->booking_ref }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 7mm 10mm 6mm 10mm;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, "Helvetica Neue", Helvetica, sans-serif;
            font-size: 10px;
            color: #1e293b;
            line-height: 1.3;
            background: #ffffff;
            height: 100%;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            page-break-inside: avoid;
        }
        .header-table td {
            vertical-align: middle;
        }
        .logo-img {
            max-height: 44px;
            width: auto;
        }
        .company-title {
            font-size: 14px;
            font-weight: bold;
            color: #0B2D5B;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            line-height: 1.1;
        }
        .company-sub {
            font-size: 8px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: bold;
            margin-top: 2px;
        }
        .company-contact {
            font-size: 8.5px;
            color: #475569;
            margin-top: 2px;
            line-height: 1.25;
        }
        .receipt-badge {
            background-color: #0B2D5B;
            color: #ffffff;
            padding: 8px 12px;
            border-radius: 6px;
            text-align: right;
        }
        .receipt-badge .title {
            font-size: 9px;
            font-weight: bold;
            letter-spacing: 1.5px;
            color: #cbd5e1;
            text-transform: uppercase;
        }
        .receipt-badge .ref {
            font-size: 13px;
            font-weight: bold;
            color: #F59E0B;
            margin-top: 1px;
        }
        .receipt-badge .inv-no {
            font-size: 8px;
            color: #94a3b8;
            margin-top: 1px;
        }

        /* 2-Column Info Cards */
        .info-table {
            margin-top: 8px;
            margin-bottom: 8px;
        }
        .info-card {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 7px 10px;
            vertical-align: top;
        }
        .bill-to-card {
            background-color: #f8fafc;
            border-left: 3.5px solid #0B2D5B;
        }
        .card-header {
            font-size: 8.5px;
            font-weight: bold;
            color: #0B2D5B;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
        }
        .guest-name {
            font-size: 11.5px;
            font-weight: bold;
            color: #0f172a;
            margin-bottom: 2px;
        }
        .card-meta-line {
            font-size: 8.5px;
            color: #475569;
            margin-bottom: 1px;
        }
        .card-meta-line strong {
            color: #1e293b;
        }
        .meta-table td {
            padding: 2px 0;
            font-size: 8.5px;
            border-bottom: 1px solid #f1f5f9;
        }
        .meta-table tr:last-child td {
            border-bottom: none;
        }

        /* Line Items Table */
        .items-table {
            margin-top: 6px;
            border: 1px solid #cbd5e1;
            border-radius: 5px;
            overflow: hidden;
        }
        .items-table th {
            background-color: #0B2D5B;
            color: #ffffff;
            font-size: 8.5px;
            font-weight: bold;
            padding: 5px 6px;
            text-align: left;
        }
        .items-table td {
            padding: 4.5px 6px;
            font-size: 8.5px;
            border-bottom: 1px solid #e2e8f0;
            color: #1e293b;
        }
        .section-row td {
            background-color: #f1f5f9;
            font-weight: bold;
            font-size: 8px;
            letter-spacing: 0.5px;
            color: #334155;
            padding: 4px 6px;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }

        /* Summary Block */
        .summary-wrapper {
            margin-top: 5px;
            margin-bottom: 6px;
        }
        .summary-table {
            width: 290px;
            float: right;
        }
        .summary-table td {
            padding: 2px 5px;
            font-size: 8.5px;
            border-bottom: 1px solid #f1f5f9;
        }
        .balance-banner {
            background-color: #0B2D5B;
            color: #ffffff;
            font-weight: bold;
            border-radius: 4px;
        }
        .balance-banner td {
            padding: 4.5px 8px;
            font-size: 9.5px;
            border-bottom: none;
        }
        .balance-amount {
            color: #F59E0B;
            font-size: 11.5px;
            font-weight: bold;
            text-align: right;
        }

        /* Clearfix */
        .clearfix { clear: both; }

        /* Bank Details Box */
        .bank-box {
            border: 1.5px solid #D97706;
            background-color: #FFFBEB;
            border-radius: 5px;
            padding: 5px 8px;
            margin-top: 4px;
            margin-bottom: 5px;
            font-size: 8.5px;
        }
        .bank-header {
            font-size: 8.5px;
            font-weight: bold;
            color: #B45309;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }
        .bank-table td {
            padding: 1px 3px;
            font-size: 8.5px;
        }
        .ref-pill {
            background-color: #0B2D5B;
            color: #ffffff;
            padding: 1.5px 5px;
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
            padding: 4px 8px;
            margin-bottom: 6px;
            font-size: 7.5px;
            color: #475569;
            line-height: 1.25;
        }
        .note-box strong { color: #0f172a; }

        /* Signatures & Stamp */
        .signatures-table {
            margin-top: 4px;
            border-top: 1px solid #cbd5e1;
            padding-top: 6px;
        }
        .signatures-table td {
            vertical-align: top;
            text-align: center;
            font-size: 8px;
        }
        .sig-line {
            display: inline-block;
            border-bottom: 1px solid #64748b;
            width: 120px;
            height: 14px;
            font-style: italic;
            font-size: 8.5px;
            color: #334155;
        }
        .stamp-badge {
            display: inline-block;
            border: 1.5px dashed #059669;
            background-color: #f0fdf4;
            color: #065f46;
            padding: 3px 8px;
            border-radius: 5px;
            font-weight: bold;
            font-size: 8.5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .stamp-due {
            border-color: #d97706;
            background-color: #fffbeb;
            color: #b45309;
        }

        /* Footer */
        .doc-footer {
            margin-top: 4px;
            border-top: 1px solid #f1f5f9;
            padding-top: 3px;
            text-align: center;
            font-size: 7px;
            color: #94a3b8;
        }

        div, p, table, tr, td, th {
            page-break-inside: avoid;
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
        $collector = $reservation->payments->first()?->collector?->name ?? $reservation->createdBy?->name ?? 'Front Desk Manager';
        $collectedAt = $reservation->payments->first()?->collected_at ?? $reservation->created_at;
        $isPaidFull = (float) $invoice->balance <= 0.01;
    @endphp

    <!-- Header Block -->
    <table class="header-table">
        <tr>
            <td style="width: 65%;">
                <table>
                    <tr>
                        @if(!empty($logoBase64))
                            <td style="width: 55px; vertical-align: middle;">
                                <img src="{{ $logoBase64 }}" class="logo-img" alt="Logo">
                            </td>
                        @endif
                        <td style="vertical-align: middle; padding-left: 5px;">
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
            <td style="width: 35%; text-align: right; vertical-align: middle;">
                <div class="receipt-badge">
                    <div class="title">OFFICIAL RECEIPT</div>
                    <div class="ref">#{{ $reservation->booking_ref }}</div>
                    <div class="inv-no">Invoice: {{ $invoice->invoice_number }}</div>
                </div>
            </td>
        </tr>
    </table>

    <!-- 2-Column Info Cards -->
    <table class="info-table">
        <tr>
            <!-- Left: BILL TO -->
            <td style="width: 55%; padding-right: 6px;">
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

            <!-- Right: STAY & RECEIPT METADATA -->
            <td style="width: 45%; padding-left: 6px;">
                <div class="info-card" style="background-color: #ffffff;">
                    <div class="card-header" style="color: #475569;">STAY &amp; FOLIO DETAILS</div>
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
                            <td style="color: #64748b;">Duration / Guests:</td>
                            <td style="text-align: right; font-weight: bold;">{{ $reservation->nights }} Night(s) · {{ $reservation->guests }} Guest(s)</td>
                        </tr>
                        <tr>
                            <td style="color: #64748b;">Payment Status:</td>
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
                <th class="text-right" style="width: 14%;">Unit Rate (₦)</th>
                <th class="text-right" style="width: 14%;">Amount (₦)</th>
            </tr>
        </thead>
        <tbody>
            <tr class="section-row">
                <td colspan="5">◆ ACCOMMODATION & SERVICES CHARGED</td>
            </tr>

            @php $sn = 1; @endphp
            @if($invoice->items && $invoice->items->count())
                @foreach($invoice->items as $item)
                    <tr>
                        <td class="text-center" style="color: #64748b;">{{ $sn++ }}</td>
                        <td><strong>{{ $item->description }}</strong></td>
                        <td class="text-center">{{ $item->quantity }}</td>
                        <td class="text-right">₦{{ number_format((float) ($item->unit_price ?? ($item->quantity ? $item->total / $item->quantity : $item->total)), 2) }}</td>
                        <td class="text-right" style="font-weight: bold;">₦{{ number_format((float) $item->total, 2) }}</td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td class="text-center" style="color: #64748b;">1</td>
                    <td><strong>Accommodation - {{ $unitTitle }}</strong></td>
                    <td class="text-center">{{ $reservation->nights }}</td>
                    <td class="text-right">₦{{ number_format((float) ($reservation->nights ? $reservation->subtotal / $reservation->nights : $reservation->subtotal), 2) }}</td>
                    <td class="text-right" style="font-weight: bold;">₦{{ number_format((float) $reservation->subtotal, 2) }}</td>
                </tr>

                @if((float) $reservation->tax_amount > 0)
                    <tr>
                        <td class="text-center" style="color: #64748b;">2</td>
                        <td>Value Added Tax (VAT 7.5%)</td>
                        <td class="text-center">1</td>
                        <td class="text-right">₦{{ number_format((float) $reservation->tax_amount, 2) }}</td>
                        <td class="text-right" style="font-weight: bold;">₦{{ number_format((float) $reservation->tax_amount, 2) }}</td>
                    </tr>
                @endif

                @if((float) $reservation->processing_fee > 0)
                    <tr>
                        <td class="text-center" style="color: #64748b;">3</td>
                        <td>Guest Verification & Service Fee</td>
                        <td class="text-center">1</td>
                        <td class="text-right">₦{{ number_format((float) $reservation->processing_fee, 2) }}</td>
                        <td class="text-right" style="font-weight: bold;">₦{{ number_format((float) $reservation->processing_fee, 2) }}</td>
                    </tr>
                @endif

                @if((float) $reservation->caution_fee > 0)
                    <tr>
                        <td class="text-center" style="color: #64748b;">4</td>
                        <td>Refundable Caution Deposit (Escrow)</td>
                        <td class="text-center">1</td>
                        <td class="text-right">₦{{ number_format((float) $reservation->caution_fee, 2) }}</td>
                        <td class="text-right" style="font-weight: bold;">₦{{ number_format((float) $reservation->caution_fee, 2) }}</td>
                    </tr>
                @endif
            @endif
        </tbody>
    </table>

    <!-- Calculations Summary Table -->
    <div class="summary-wrapper">
        <table class="summary-table">
            <tr>
                <td style="color: #64748b;">Gross Accommodation Subtotal:</td>
                <td class="text-right">₦{{ number_format((float) $reservation->subtotal, 2) }}</td>
            </tr>
            @if((float) $reservation->discount_amount > 0)
                <tr style="color: #059669;">
                    <td>Applied Discount:</td>
                    <td class="text-right" style="font-weight: bold;">-₦{{ number_format((float) $reservation->discount_amount, 2) }}</td>
                </tr>
            @endif
            @if((float) $reservation->tax_amount > 0)
                <tr>
                    <td style="color: #64748b;">Statutory VAT (7.5%):</td>
                    <td class="text-right">₦{{ number_format((float) $reservation->tax_amount, 2) }}</td>
                </tr>
            @endif
            @if((float) $reservation->processing_fee > 0)
                <tr>
                    <td style="color: #64748b;">Processing Fee:</td>
                    <td class="text-right">₦{{ number_format((float) $reservation->processing_fee, 2) }}</td>
                </tr>
            @endif
            @if((float) $reservation->caution_fee > 0)
                <tr>
                    <td style="color: #334155;">Refundable Caution Deposit:</td>
                    <td class="text-right" style="font-weight: bold;">₦{{ number_format((float) $reservation->caution_fee, 2) }}</td>
                </tr>
            @endif
            <tr style="font-weight: bold; border-top: 1.5px solid #cbd5e1;">
                <td style="font-size: 9px;">Net Total Payable:</td>
                <td class="text-right" style="font-size: 9px;">₦{{ number_format((float) $invoice->total_amount, 2) }}</td>
            </tr>
            <tr style="color: #059669; font-weight: bold;">
                <td>Total Amount Settled / Received:</td>
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
        <div style="margin-top: 3px; padding-top: 3px; border-top: 1px solid #fde68a; font-size: 8px; color: #78350f;">
            Payment Reference: <span class="ref-pill">{{ $reservation->booking_ref }}</span> (Quote on all transfers)
        </div>
    </div>

    <!-- Caution Deposit Policy Note Box -->
    <div class="note-box">
        <strong>Caution Deposit & Stay Policies:</strong> Refundable caution deposit is held in escrow and released within 24 hours post-checkout inspection. Check-in: 2:00 PM; Check-out: 11:00 AM. For billing questions or concierge assistance, contact <a href="mailto:{{ $siteEmail ?? 'concierge@booktala.com' }}">{{ $siteEmail ?? 'concierge@booktala.com' }}</a>.
    </div>

    <!-- Signatures & Status Stamp Block -->
    <table class="signatures-table">
        <tr>
            <td style="width: 33%;">
                <div class="sig-line">{{ $collector }}</div>
                <div style="font-weight: bold; margin-top: 2px; color: #0f172a; font-size: 8px;">Front Desk Cashier / Officer</div>
                <div style="color: #64748b; font-size: 7.5px;">Collected: {{ $collectedAt ? \Carbon\Carbon::parse($collectedAt)->format('d M Y, h:ia') : 'At Reception' }}</div>
            </td>
            <td style="width: 34%;">
                @if($isPaidFull)
                    <div class="stamp-badge">
                        <div>✔ PAYMENT COMPLETED</div>
                        <div style="font-size: 7px; font-weight: normal; margin-top: 1px;">Official Stamped Receipt</div>
                    </div>
                @else
                    <div class="stamp-badge stamp-due">
                        <div>⚠ PARTIAL PAYMENT</div>
                        <div style="font-size: 7px; font-weight: normal; margin-top: 1px;">Balance Pending</div>
                    </div>
                @endif
            </td>
            <td style="width: 33%;">
                <div class="sig-line">{{ $guestName }}</div>
                <div style="font-weight: bold; margin-top: 2px; color: #0f172a; font-size: 8px;">Guest Signature / Acceptance</div>
                <div style="color: #64748b; font-size: 7.5px;">Folio Acknowledged</div>
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
