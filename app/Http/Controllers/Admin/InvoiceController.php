<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\InvoiceUpdateStatusRequest;
use App\Http\Requests\Admin\SendInvoiceEmailRequest;
use App\Http\Requests\Admin\StoreInvoiceRequest;
use App\Jobs\SendInvoiceEmailJob;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Reservation;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index()
    {
        $invoices = Invoice::with(['reservation.property', 'user'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.invoices.index', compact('invoices'));
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['reservation.property', 'user', 'items']);

        return view('admin.invoices.show', compact('invoice'));
    }

    public function create(Request $request)
    {
        $reservation = null;
        if ($request->has('reservation_id')) {
            $reservation = Reservation::with(['property', 'user', 'payments'])->find($request->reservation_id);
        }

        $reservations = Reservation::whereDoesntHave('invoice')
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->with(['property', 'user'])
            ->get();

        return view('admin.invoices.create', compact('reservation', 'reservations'));
    }

    public function store(StoreInvoiceRequest $request)
    {
        $validated = $request->validated();
        $reservation = Reservation::with(['property', 'user', 'payments'])->findOrFail($validated['reservation_id']);

        // Use the amounts frozen on the reservation so the invoice matches what was charged.
        $subtotal = (float) $reservation->subtotal;
        $vatAmount = (float) $reservation->tax_amount;
        $discountAmount = (float) ($reservation->discount_amount ?? 0);
        $processingFee = (float) ($reservation->processing_fee ?? 0);
        $cautionFee = (float) ($reservation->caution_fee ?? 0);
        $totalAmount = (float) $reservation->total_amount;
        $paidAmount = $reservation->payments()
            ->where('type', 'payment')
            ->where('paystack_status', 'success')
            ->sum('amount');
        $balance = $totalAmount - $paidAmount;

        $invoice = Invoice::create([
            'invoice_number' => Invoice::generateInvoiceNumber(),
            'reservation_id' => $reservation->id,
            'user_id' => $reservation->user_id,
            'subtotal' => $subtotal,
            'vat_amount' => $vatAmount,
            'discount_amount' => $discountAmount,
            'processing_fee' => $processingFee,
            'caution_fee' => $cautionFee,
            'total_amount' => $totalAmount,
            'paid_amount' => $paidAmount,
            'balance' => $balance,
            'status' => 'issued',
            'due_date' => $validated['due_date'] ?? now()->addDays(7),
            'notes' => $validated['notes'] ?? null,
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'Accommodation - '.$reservation->property->title,
            'quantity' => $reservation->nights,
            'unit_price' => $reservation->property->price_per_night,
            'total' => $subtotal,
        ]);

        return redirect()->route('admin.invoices.show', $invoice)
            ->with('success', 'Invoice created successfully');
    }

    public function generatePdf(Invoice $invoice)
    {
        $invoice->load(['reservation.property', 'reservation.unit.site', 'user', 'items']);

        $siteName = Setting::get('site_name', config('brand.name'));
        $siteEmail = Setting::get('email', config('brand.email'));
        $sitePhone = Setting::get('phone', config('brand.phone'));
        $siteAddress = Setting::get('address', config('brand.address'));
        $companyName = Setting::get('company_name', 'G-Briel Property Ltd');

        $logoPath = public_path('images/booktala-logo.png');
        $logoBase64 = file_exists($logoPath) ? 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath)) : null;

        $pdf = Pdf::loadView('admin.invoices.pdf', [
            'invoice' => $invoice,
            'siteName' => $siteName,
            'siteEmail' => $siteEmail,
            'sitePhone' => $sitePhone,
            'siteAddress' => $siteAddress,
            'companyName' => $companyName,
            'logoBase64' => $logoBase64,
        ]);

        return $pdf->download('invoice-'.$invoice->invoice_number.'.pdf');
    }

    public function sendEmail(SendInvoiceEmailRequest $request, Invoice $invoice)
    {
        $recipientEmail = $request->validated('email');

        SendInvoiceEmailJob::dispatch($invoice, $recipientEmail);

        return back()->with('success', 'Invoice delivery queued successfully to '.$recipientEmail);
    }

    public function markAsPaid(Request $request, Invoice $invoice)
    {
        $invoice->update([
            'status' => 'paid',
            'paid_at' => now(),
            'paid_amount' => $invoice->total_amount,
            'balance' => 0,
        ]);

        return back()->with('success', 'Invoice marked as paid');
    }

    public function updateStatus(InvoiceUpdateStatusRequest $request, Invoice $invoice)
    {
        $request->validated();

        $invoice->update(['status' => $request->status]);

        return back()->with('success', 'Invoice status updated');
    }
}
