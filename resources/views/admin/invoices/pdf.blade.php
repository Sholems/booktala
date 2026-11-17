<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 14px; color: #333; }
        .invoice-container { max-width: 800px; margin: 0 auto; padding: 40px; }
        .header { display: flex; justify-content: space-between; margin-bottom: 40px; }
        .company-info h1 { font-size: 24px; color: #0B2D5B; }
        .company-info p { color: #666; font-size: 12px; margin-top: 4px; }
        .invoice-details { text-align: right; }
        .invoice-details h2 { font-size: 28px; color: #E11D2E; margin-bottom: 10px; }
        .invoice-details p { font-size: 12px; color: #666; }
        .invoice-details .status { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .status-paid { background: #d1fae5; color: #065f46; }
        .status-issued { background: #dbeafe; color: #1e40af; }
        .status-partial { background: #fef3c7; color: #92400e; }
        .status-overdue { background: #fee2e2; color: #991b1b; }
        .section { margin-bottom: 30px; }
        .section-title { font-size: 12px; text-transform: uppercase; color: #666; margin-bottom: 10px; border-bottom: 1px solid #eee; padding-bottom: 5px; }
        .billing-info p { margin-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        table th { background: #f8f9fa; padding: 12px; text-align: left; font-size: 12px; color: #666; border-bottom: 2px solid #eee; }
        table td { padding: 12px; border-bottom: 1px solid #eee; }
        table .text-right { text-align: right; }
        .totals { margin-top: 20px; }
        .totals .row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee; }
        .totals .row.total { font-size: 18px; font-weight: bold; border-top: 2px solid #333; margin-top: 10px; padding-top: 15px; }
        .totals .row.paid { color: #059669; }
        .totals .row.balance { color: #dc2626; font-weight: bold; }
        .notes { background: #f8f9fa; padding: 15px; border-radius: 8px; margin-top: 30px; }
        .notes h4 { font-size: 12px; color: #666; margin-bottom: 8px; }
        .notes p { font-size: 12px; color: #666; }
        .footer { margin-top: 40px; text-align: center; font-size: 12px; color: #999; }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="header">
            <div class="company-info">
                <h1>{{ $siteName ?? 'BookTala' }}</h1>
                <p>{{ $siteEmail ?? config('brand.email') }}</p>
                <p>{{ $sitePhone ?? config('brand.phone') }}</p>
            </div>
            <div class="invoice-details">
                <h2>INVOICE</h2>
                <p><strong>{{ $invoice->invoice_number }}</strong></p>
                <p>Date: {{ $invoice->created_at->format('M d, Y') }}</p>
                @if($invoice->due_date)
                <p>Due: {{ \Carbon\Carbon::parse($invoice->due_date)->format('M d, Y') }}</p>
                @endif
                <span class="status status-{{ $invoice->status }}">{{ ucfirst($invoice->status) }}</span>
            </div>
        </div>

        <div class="section">
            <div class="section-title">Bill To</div>
            <div class="billing-info">
                <p><strong>{{ $invoice->user->name ?? 'Guest' }}</strong></p>
                <p>{{ $invoice->user->email ?? '' }}</p>
                <p>{{ $invoice->user->phone ?? '' }}</p>
            </div>
        </div>

        <div class="section">
            <div class="section-title">Reservation Details</div>
            <p><strong>{{ $invoice->reservation->property->name ?? 'N/A' }}</strong></p>
            <p>Check-in: {{ \Carbon\Carbon::parse($invoice->reservation->check_in)->format('M d, Y') }}</p>
            <p>Check-out: {{ \Carbon\Carbon::parse($invoice->reservation->check_out)->format('M d, Y') }}</p>
            <p>{{ $invoice->reservation->nights ?? 0 }} nights</p>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="text-right">Qty</th>
                    <th class="text-right">Unit Price</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoice->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td class="text-right">{{ $item->quantity }}</td>
                    <td class="text-right">₦{{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right">₦{{ number_format($item->total, 2) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" style="text-align: center;">No items</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <div class="totals">
            <div class="row">
                <span>Subtotal</span>
                <span>₦{{ number_format($invoice->subtotal, 2) }}</span>
            </div>
            @if($invoice->vat_amount > 0)
            <div class="row">
                <span>VAT (7.5%)</span>
                <span>₦{{ number_format($invoice->vat_amount, 2) }}</span>
            </div>
            @endif
            @if($invoice->discount_amount > 0)
            <div class="row">
                <span>Discount</span>
                <span style="color: #059669;">-₦{{ number_format($invoice->discount_amount, 2) }}</span>
            </div>
            @endif
            <div class="row total">
                <span>Total</span>
                <span>₦{{ number_format($invoice->total_amount, 2) }}</span>
            </div>
            <div class="row paid">
                <span>Paid</span>
                <span>₦{{ number_format($invoice->paid_amount, 2) }}</span>
            </div>
            @if($invoice->balance > 0)
            <div class="row balance">
                <span>Balance Due</span>
                <span>₦{{ number_format($invoice->balance, 2) }}</span>
            </div>
            @endif
        </div>

        @if($invoice->notes)
        <div class="notes">
            <h4>Notes</h4>
            <p>{{ $invoice->notes }}</p>
        </div>
        @endif

        <div class="footer">
            <p>Thank you for your business!</p>
            <p>Powered by BookTala Management System</p>
        </div>
    </div>
</body>
</html>