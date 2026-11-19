<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Owner;
use App\Models\Position;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\Site;
use App\Models\Staff;
use App\Models\Unit;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceiptAndInvoiceStandardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $staff;

    private Site $site;

    private Unit $unit;

    private Reservation $reservation;

    private Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->create([
            'email' => 'admin_test@booktala.com',
            'phone' => '08011112222',
        ]);
        $this->admin->assignRole('admin');

        $this->site = Site::create([
            'name' => 'Lekki Admiralty Suites',
            'address' => 'Plot 14, Admiralty Way, Lekki Phase 1',
            'city' => 'Lagos',
            'state' => 'Lagos',
        ]);

        $this->staff = User::factory()->create([
            'email' => 'frontdesk_test@booktala.com',
            'phone' => '08022223333',
        ]);
        $this->staff->assignRole('staff');
        $this->staff->givePermissionTo('reservation.create');
        $this->staff->givePermissionTo('reservation.view');

        $position = Position::firstOrCreate(
            ['name' => 'Receptionist'],
            ['department' => 'Front Desk', 'description' => 'Receptionist']
        );
        $staffModel = Staff::create([
            'user_id' => $this->staff->id,
            'employee_id' => 'STF-TEST-'.$this->staff->id,
            'hire_date' => today(),
        ]);
        $staffModel->assignTo($this->site, $position);

        $owner = Owner::create([
            'name' => 'Owner Alpha',
            'type' => 'individual',
            'email' => 'owner_alpha@test.com',
            'phone' => '08033334444',
            'is_company_owned' => false,
        ]);

        $property = Property::create([
            'title' => 'Penthouse 4B Victoria',
            'description' => 'Test apartment',
            'address' => 'Plot 14, Admiralty Way, Lekki Phase 1',
            'city' => 'Lagos',
            'state' => 'Lagos',
            'price_per_night' => 50000,
            'max_guests' => 4,
            'bedrooms' => 2,
            'bathrooms' => 2,
            'toilets' => 2,
            'status' => 'active',
            'site_id' => $this->site->id,
            'owner_id' => $owner->id,
        ]);

        $this->unit = $property;

        $guestUser = User::factory()->create([
            'name' => 'Adekunle Gold',
            'email' => 'adekunle@example.com',
            'phone' => '08099998888',
        ]);

        $this->reservation = Reservation::create([
            'user_id' => $guestUser->id,
            'property_id' => $property->id,
            'booking_ref' => 'BKT-REC-998877',
            'check_in' => now()->addDays(2)->format('Y-m-d'),
            'check_out' => now()->addDays(5)->format('Y-m-d'),
            'nights' => 3,
            'guests' => 2,
            'subtotal' => 150000,
            'tax_amount' => 11250,
            'processing_fee' => 2500,
            'caution_fee' => 30000,
            'total_amount' => 193750,
            'status' => 'confirmed',
            'payment_status' => 'paid',
        ]);

        $this->invoice = Invoice::create([
            'reservation_id' => $this->reservation->id,
            'user_id' => $guestUser->id,
            'invoice_number' => 'INV-2026-998877',
            'subtotal' => 150000,
            'vat_amount' => 11250,
            'discount_amount' => 0,
            'processing_fee' => 2500,
            'caution_fee' => 30000,
            'total_amount' => 193750,
            'paid_amount' => 193750,
            'balance' => 0,
            'status' => 'paid',
            'due_date' => now()->addDays(2),
        ]);

        InvoiceItem::create([
            'invoice_id' => $this->invoice->id,
            'description' => 'Accommodation - Penthouse 4B Victoria',
            'quantity' => 3,
            'unit_price' => 50000,
            'total' => 150000,
        ]);
    }

    public function test_frontdesk_receipt_displays_standard_brand_elements_and_avoids_website_header(): void
    {
        $response = $this->actingAs($this->admin)->get(route('frontdesk.reservations.receipt', $this->reservation));

        $response->assertOk();
        // Check for official branding
        $response->assertSee('LUXURY STAYS');
        $response->assertSee('OFFICIAL RECEIPT');
        $response->assertSee('#'.$this->reservation->booking_ref);
        $response->assertSee('Adekunle Gold');
        $response->assertSee('08099998888');
        $response->assertSee('Penthouse 4B Victoria');
        $response->assertSee('BANK PAYMENT DETAILS');
        $response->assertSee('Balance Due');
        $response->assertSee('Refundable Caution Deposit');
        $response->assertSee('PAYMENT COMPLETED');
        // Check that layout print isolation hides admin headers and sidebars
        $response->assertSee('print:hidden');
        $response->assertSee('id="printable-receipt"', false);
    }

    public function test_frontdesk_receipt_pdf_downloads_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('frontdesk.reservations.receipt.pdf', $this->reservation));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('receipt-BKT-REC-998877.pdf', $response->headers->get('Content-Disposition'));
    }

    public function test_admin_invoice_show_displays_standard_branding_and_print_isolation(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.invoices.show', $this->invoice));

        $response->assertOk();
        $response->assertSee('OFFICIAL INVOICE');
        $response->assertSee('#'.$this->invoice->invoice_number);
        $response->assertSee('Adekunle Gold');
        $response->assertSee('Download PDF');
        $response->assertSee('Print Invoice');
        $response->assertSee('BANK PAYMENT DETAILS');
        $response->assertSee('id="printable-invoice"', false);
    }

    public function test_admin_invoice_pdf_downloads_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.invoices.pdf', $this->invoice));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('invoice-INV-2026-998877.pdf', $response->headers->get('Content-Disposition'));
    }

    public function test_receipt_and_invoice_pdfs_are_strictly_one_page(): void
    {
        $siteName = 'BookTala';
        $siteEmail = 'concierge@booktala.com';
        $sitePhone = '+234 814 000 8252';
        $siteAddress = 'Plot 14, Admiralty Way, Lekki Phase 1, Lagos, Nigeria';
        $companyName = 'G-Briel Property Ltd';
        $bankName = 'First Bank of Nigeria / Providus Bank';
        $accountNumber = '0123456789';
        $accountName = 'BookTala (G-Briel Property Ltd)';
        $logoBase64 = null;

        // Invoice PDF 1-page assertion
        $invoicePdf = Pdf::loadView('admin.invoices.pdf', [
            'invoice' => $this->invoice,
            'siteName' => $siteName,
            'siteEmail' => $siteEmail,
            'sitePhone' => $sitePhone,
            'siteAddress' => $siteAddress,
            'companyName' => $companyName,
            'logoBase64' => $logoBase64,
        ]);
        $invoicePdf->render();
        $this->assertSame(1, $invoicePdf->getDomPDF()->getCanvas()->get_page_count(), 'Invoice PDF must not exceed 1 page.');

        // Receipt PDF 1-page assertion
        $receiptPdf = Pdf::loadView('frontdesk.receipt-pdf', [
            'reservation' => $this->reservation,
            'invoice' => $this->invoice,
            'siteName' => $siteName,
            'siteEmail' => $siteEmail,
            'sitePhone' => $sitePhone,
            'siteAddress' => $siteAddress,
            'companyName' => $companyName,
            'bankName' => $bankName,
            'accountNumber' => $accountNumber,
            'accountName' => $accountName,
            'logoBase64' => $logoBase64,
        ]);
        $receiptPdf->render();
        $this->assertSame(1, $receiptPdf->getDomPDF()->getCanvas()->get_page_count(), 'Receipt PDF must not exceed 1 page.');
    }

    public function test_send_invoice_email_dispatches_queued_job_asynchronously(): void
    {
        \Illuminate\Support\Facades\Queue::fake();

        $response = $this->actingAs($this->admin)->post(route('admin.invoices.send', $this->invoice), [
            'email' => 'guest@example.com',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\SendInvoiceEmailJob::class, function ($job) {
            return $job->invoice->id === $this->invoice->id && $job->recipientEmail === 'guest@example.com';
        });
    }

    public function test_store_invoice_uses_validated_form_request(): void
    {
        // Unbilled reservation
        $newReservation = Reservation::create([
            'booking_ref' => 'BK-UNBILLED-99',
            'user_id' => $this->admin->id,
            'property_id' => $this->unit->id,
            'check_in' => '2026-12-10',
            'check_out' => '2026-12-12',
            'nights' => 2,
            'guests' => 2,
            'subtotal' => 180000,
            'total_amount' => 180000,
            'booking_status' => 'confirmed',
            'payment_status' => 'paid',
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.invoices.store'), [
            'reservation_id' => $newReservation->id,
            'notes' => 'Corporate retreat invoice',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('invoices', [
            'reservation_id' => $newReservation->id,
            'notes' => 'Corporate retreat invoice',
        ]);
    }
}
