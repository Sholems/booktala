<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Invoice #{{ $invoice->invoice_number }}</title>
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
        .invoice-badge {
            background-color: #0B2D5B;
            color: #ffffff;
            padding: 10px 14px;
            border-radius: 8px;
            text-align: right;
        }
        .invoice-badge .title {
            font-size: 10px;
            font-weight: bold;
            letter-spacing: 1.5px;
            color: #cbd5e1;
            text-transform: uppercase;
        }
        .invoice-badge .ref {
            font-size: 14px;
            font-weight: bold;
            color: #F59E0B;
            margin-top: 2px;
        }
        .invoice-badge .booking-no {
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
        $reservation = $invoice->reservation;
        $guestName = $invoice->user?->name ?? $reservation?->guest?->name ?? $reservation?->user?->name ?? 'Valued Guest';
        $guestPhone = $invoice->user?->phone ?? $reservation?->guest?->phone ?? $reservation?->user?->phone ?? 'N/A';
        $guestEmail = $invoice->user?->email ?? $reservation?->guest?->email ?? $reservation?->user?->email ?? '';
        $unitTitle = $reservation?->unit?->title ?? $reservation?->property?->title ?? $reservation?->property?->name ?? 'BookTala Residence';
        $siteLocation = $reservation?->unit?->site?->name ?? ($reservation?->property?->city ? $reservation?->property?->city . ', ' . $reservation?->property?->state : 'Lagos, Nigeria');
        $isPaidFull = (float) $invoice->balance <= 0.01;
        $bookingRef = $reservation?->booking_ref ?? ('INV-' . $invoice->id);
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
                <div class="invoice-badge">
                    <div class="title">OFFICIAL INVOICE</div>
                    <div class="ref">#{{ $invoice->invoice_number }}</div>
                    @if($reservation)
                        <div class="booking-no">Booking Ref: {{ $bookingRef }}</div>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <!-- 2-Column Info Cards -->
    <table class="info-table">
        <tr>
            <!-- Left: BILL TO -->
            <td style="width: 55%; padding-right: 8px;">
                <div class="info-card bill-to-card">
                    <div class="card-header">BILL TO / CLIENT DETAILS</div>
                    <div class="guest-name">{{ $guestName }}</div>
                    <div class="card-meta-line"><strong>Account:</strong> {{ $guestEmail ?: 'Guest Checkout' }}</div>
                    <div class="card-meta-line"><strong>Phone:</strong> {{ $guestPhone }}</div>
                    @if($reservation)
                        <div class="card-meta-line"><strong>Assigned Suite:</strong> {{ $unitTitle }} ({{ $siteLocation }})</div>
                    @endif
                </div>
            </td>

            <!-- Right: INVOICE METADATA -->
            <td style="width: 45%; padding-left: 8px;">
                <div class="info-card" style="background-color: #ffffff;">
                    <div class="card-header" style="color: #475569;">INVOICE & BILLING SCHEDULE</div>
                    <table class="meta-table">
                        <tr>
                            <td style="color: #64748b;">Invoice Date:</td>
                            <td style="text-align: right; font-weight: bold;">{{ $invoice->created_at ? $invoice->created_at->format('d M Y') : now()->format('d M Y') }}</td>
                        </tr>
                        <tr>
                            <td style="color: #64748b;">Due Date:</td>
                            <td style="text-align: right; font-weight: bold;">{{ $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('d M Y') : now()->addDays(7)->format('d M Y') }}</td>
                        </tr>
                        @if($reservation)
                            <tr>
                                <td style="color: #64748b;">Check-In / Out:</td>
                                <td style="text-align: right; font-weight: bold;">{{ $reservation->check_in->format('d M') }} – {{ $reservation->check_out->format('d M Y') }}</td>
                            </tr>
                            <tr>
                                <td style="color: #64748b;">Stay Duration:</td>
                                <td style="text-align: right; font-weight: bold;">{{ $reservation->nights }} Night(s)</td>
                            </tr>
                        @endif
                        <tr>
                            <td style="color: #64748b;">Invoice Status:</td>
                            <td style="text-align: right; font-weight: bold; color: {{ $isPaidFull ? '#059669' : ($invoice->status === 'overdue' ? '#dc2626' : '#d97706') }};">
                                {{ strtoupper($invoice->status) }}
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
                <td colspan="5">◆ ACCOMMODATION & SERVICES BILLED</td>
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
                @if($reservation)
                    <tr>
                        <td class="text-center" style="color: #64748b;">1</td>
                        <td><strong>Accommodation Stay: {{ $unitTitle }}</strong></td>
                        <td class="text-center">{{ $reservation->nights }}</td>
                        <td class="text-right">₦{{ number_format((float) ($reservation->nights ? $reservation->subtotal / $reservation->nights : $reservation->subtotal), 2) }}</td>
                        <td class="text-right" style="font-weight: bold;">₦{{ number_format((float) $reservation->subtotal, 2) }}</td>
                    </tr>
                @else
                    <tr>
                        <td colspan="5" class="text-center" style="padding: 12px; color: #64748b;">No line items recorded on this invoice.</td>
                    </tr>
                @endif
            @endforelse
        </tbody>
    </table>

    <!-- Calculations Summary Table -->
    <div class="summary-wrapper">
        <table class="summary-table">
            <tr>
                <td style="color: #64748b;">Gross Subtotal:</td>
                <td class="text-right">₦{{ number_format((float) $invoice->subtotal, 2) }}</td>
            </tr>
            @if((float) $invoice->discount_amount > 0)
                <tr style="color: #059669;">
                    <td>Total Discount:</td>
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
                <td>Amount Settled / Paid:</td>
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
            Payment Reference: <span class="ref-pill">{{ $bookingRef }}</span> (Please state this reference on all payments)
        </div>
    </div>

    <!-- Important Notes Box -->
    <div class="note-box">
        <strong>Important Payment Information:</strong> Payment is strictly due by the stated due date. Payments can be settled online via card, bank transfer, or at our front desk. For questions regarding this invoice, please reach out to <a href="mailto:{{ $siteEmail ?? 'concierge@booktala.com' }}">{{ $siteEmail ?? 'concierge@booktala.com' }}</a> quoting invoice #{{ $invoice->invoice_number }}.
        @if($invoice->notes)
            <div style="margin-top: 4px; font-style: italic;"><strong>Additional Notes:</strong> {{ $invoice->notes }}</div>
        @endif
    </div>

    <!-- Signatures & Status Stamp Block -->
    <table class="signatures-table">
        <tr>
            <td style="width: 33%;">
                <div class="sig-line">Finance Desk</div>
                <div style="font-weight: bold; margin-top: 3px; color: #0f172a;">Billing &amp; Revenue Officer</div>
                <div style="color: #64748b; font-size: 8px;">G-Briel Property Operations Desk</div>
            </td>
            <td style="width: 34%;">
                @if($isPaidFull)
                    <div class="stamp-badge">
                        <div>✔ PAID IN FULL</div>
                        <div style="font-size: 7.5px; font-weight: normal; margin-top: 1px;">Confirmed: {{ now()->format('d M Y') }}</div>
                    </div>
                @else
                    <div class="stamp-badge stamp-due">
                        <div>⚠ PAYMENT DUE</div>
                        <div style="font-size: 7.5px; font-weight: normal; margin-top: 1px;">Due: {{ $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('d M Y') : now()->addDays(7)->format('d M Y') }}</div>
                    </div>
                @endif
            </td>
            <td style="width: 33%;">
                <div class="sig-line">G-Briel Property Executive</div>
                <div style="font-weight: bold; margin-top: 3px; color: #0f172a;">Managing Director</div>
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
