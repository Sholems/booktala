<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendInvoiceEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(
        public Invoice $invoice,
        public string $recipientEmail
    ) {}

    public function handle(): void
    {
        $this->invoice->loadMissing(['reservation.property', 'reservation.unit.site', 'user', 'items']);

        $siteName = Setting::get('site_name', config('brand.name', 'BookTala'));
        $siteEmail = Setting::get('email', config('brand.email'));
        $sitePhone = Setting::get('phone', config('brand.phone'));
        $siteAddress = Setting::get('address', config('brand.address'));
        $companyName = Setting::get('company_name', 'G-Briel Property Ltd');

        $logoPath = public_path('images/booktala-logo.png');
        $logoBase64 = file_exists($logoPath) ? 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath)) : null;

        $pdf = Pdf::loadView('admin.invoices.pdf', [
            'invoice' => $this->invoice,
            'siteName' => $siteName,
            'siteEmail' => $siteEmail,
            'sitePhone' => $sitePhone,
            'siteAddress' => $siteAddress,
            'companyName' => $companyName,
            'logoBase64' => $logoBase64,
        ]);

        $content = view('admin.invoices.email-template', [
            'invoice' => $this->invoice,
            'siteName' => $siteName,
        ])->render();

        $invoiceNumber = $this->invoice->invoice_number;
        $pdfData = $pdf->output();

        Mail::html($content, function ($message) use ($invoiceNumber, $siteName, $pdfData) {
            $message->to($this->recipientEmail)
                ->subject("Invoice #{$invoiceNumber} from {$siteName}")
                ->attachData($pdfData, "invoice-{$invoiceNumber}.pdf", [
                    'mime' => 'application/pdf',
                ]);
        });
    }
}
