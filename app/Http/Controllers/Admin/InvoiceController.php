<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;

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

    public function store(Request $request)
    {
        $request->validate([
            'reservation_id' => 'required|exists:reservations,id',
            'due_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $reservation = Reservation::with(['property', 'user', 'payments'])->find($request->reservation_id);

        // Use the amounts frozen on the reservation so the invoice matches what was charged.
        $subtotal = (float) $reservation->subtotal;
        $vatAmount = (float) $reservation->tax_amount;
        $discountAmount = (float) ($reservation->discount_amount ?? 0);
        $totalAmount = (float) $reservation->total_amount;
        $paidAmount = $reservation->payments()
            ->where('type', 'payment')
            ->where('paystack_status', 'success')
            ->sum('amount');
        $balance = $totalAmount - $paidAmount;

        $invoice = Invoice::create([
            'invoice_number' => Invoice::generateInvoiceNumber(),
            'reservation_id' => $request->reservation_id,
            'user_id' => $reservation->user_id,
            'subtotal' => $subtotal,
            'vat_amount' => $vatAmount,
            'discount_amount' => $discountAmount,
            'total_amount' => $totalAmount,
            'paid_amount' => $paidAmount,
            'balance' => $balance,
            'status' => 'issued',
            'due_date' => $request->due_date ?? now()->addDays(7),
            'notes' => $request->notes,
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'Accommodation - ' . $reservation->property->title,
            'quantity' => $reservation->nights,
            'unit_price' => $reservation->property->price_per_night,
            'total' => $subtotal,
        ]);

        return redirect()->route('admin.invoices.show', $invoice)
            ->with('success', 'Invoice created successfully');
    }

    public function generatePdf(Invoice $invoice)
    {
        $invoice->load(['reservation.property', 'user', 'items']);
        
        $siteName = \App\Models\Setting::get('site_name', config('brand.name'));
        $siteEmail = \App\Models\Setting::get('email', config('brand.email'));
        $sitePhone = \App\Models\Setting::get('phone', config('brand.phone'));
        
        $pdf = Pdf::loadView('admin.invoices.pdf', [
            'invoice' => $invoice,
            'siteName' => $siteName,
            'siteEmail' => $siteEmail,
            'sitePhone' => $sitePhone,
        ]);
        
        return $pdf->download('invoice-' . $invoice->invoice_number . '.pdf');
    }

    public function sendEmail(Request $request, Invoice $invoice)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $invoice->load(['reservation.property', 'user', 'items']);
        
        $siteName = \App\Models\Setting::get('site_name', config('brand.name'));
        $siteEmail = \App\Models\Setting::get('email', config('brand.email'));
        
        $pdf = Pdf::loadView('admin.invoices.pdf', [
            'invoice' => $invoice,
            'siteName' => $siteName,
            'siteEmail' => $siteEmail,
            'sitePhone' => \App\Models\Setting::get('phone', config('brand.phone')),
        ]);

        $content = view('admin.invoices.email-template', [
            'invoice' => $invoice,
            'siteName' => $siteName,
        ])->render();

        Mail::html($content, function ($message) use ($request, $invoice, $pdf, $siteName) {
            $message->to($request->email)
                ->subject('Invoice #' . $invoice->invoice_number . ' from ' . $siteName)
                ->attachData($pdf->output(), 'invoice-' . $invoice->invoice_number . '.pdf');
        });

        return back()->with('success', 'Invoice sent to ' . $request->email);
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

    public function updateStatus(Request $request, Invoice $invoice)
    {
        $request->validate([
            'status' => 'required|in:draft,issued,partial,paid,overdue,cancelled',
        ]);

        $invoice->update(['status' => $request->status]);

        return back()->with('success', 'Invoice status updated');
    }
}