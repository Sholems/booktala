<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Expense;
use App\Models\Owner;
use App\Models\OwnerStatement;
use App\Models\PayRun;
use App\Models\Position;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\SalaryStructure;
use App\Models\Site;
use App\Models\Staff;
use App\Models\StaffDeduction;
use App\Models\StaffSchedule;
use App\Models\User;
use App\Services\PayrollCalculator;
use App\Services\PayrollSettings;
use Carbon\CarbonImmutable;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Phase 3: owner agreements, expenses and statements, the owner portal, and payroll.
 * The clock is fixed at 15 November 2026; statements and pay runs cover October.
 */
class FinanceAndPayrollTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $admin;
    private Owner $landlord;
    private Property $flat;
    private Site $site;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->seed(RolePermissionSeeder::class);
        $this->travelTo(CarbonImmutable::parse('2026-11-15 10:00'));

        $this->superAdmin = $this->makeUser('super_admin');
        $this->admin = $this->makeUser('admin');
        $this->site = Site::create(['name' => 'Lekki Gardens', 'address' => '1 Way', 'city' => 'Lekki', 'state' => 'Lagos']);
        $this->landlord = Owner::create(['name' => 'Mrs Adeyemi', 'type' => 'individual', 'commission_rate' => 20, 'deduct_expenses' => true, 'is_active' => true]);
        $this->flat = $this->makeProperty('Adeyemi Flat', $this->landlord);
    }

    // --- Helpers -----------------------------------------------------------------------------

    private function makeUser(string $role, string $name = null): User
    {
        $user = User::create(['name' => $name ?? ucfirst($role) . ' ' . uniqid(), 'email' => $role . uniqid() . '@example.com', 'password' => 'password123', 'status' => 'active']);
        $user->assignRole($role);

        return $user;
    }

    private function makeProperty(string $title, Owner $owner): Property
    {
        return Property::create([
            'title' => $title, 'description' => 'Nice', 'address' => '1 Street', 'city' => 'Lagos', 'state' => 'Lagos',
            'price_per_night' => 10000, 'max_guests' => 2, 'bedrooms' => 1, 'bathrooms' => 1, 'toilets' => 1, 'status' => 'active',
            'owner_id' => $owner->id, 'site_id' => $this->site->id,
        ]);
    }

    private function stay(Property $property, string $out, array $attrs = []): Reservation
    {
        $guest = $this->makeUser('customer');

        return Reservation::create(array_merge([
            'booking_ref' => Reservation::generateBookingRef(), 'user_id' => $guest->id, 'property_id' => $property->id,
            'check_in' => CarbonImmutable::parse($out)->subDays(4)->toDateString(), 'check_out' => $out, 'nights' => 4, 'guests' => 2,
            'subtotal' => 100000, 'discount_amount' => 10000, 'tax_amount' => 7500, 'total_amount' => 97500,
            'payment_status' => 'paid', 'booking_status' => 'checked_out',
        ], $attrs));
    }

    private function expense(Property $property, string $date, float $amount, string $chargeTo = 'owner'): Expense
    {
        return Expense::create(['property_id' => $property->id, 'category' => 'repairs', 'description' => 'Fix ' . uniqid(), 'amount' => $amount, 'spent_on' => $date, 'charge_to' => $chargeTo]);
    }

    private function makeStaff(array $salary = []): User
    {
        $user = $this->makeUser('staff', 'Ngozi ' . uniqid());
        $staff = Staff::create(['user_id' => $user->id, 'employee_id' => 'WP-' . $user->id, 'hire_date' => '2026-01-01', 'bank_name' => 'GTBank', 'bank_account_name' => 'Ngozi', 'bank_account_number' => '0123456789']);
        $staff->assignTo($this->site, Position::where('name', 'Receptionist')->firstOrFail(), true, '2026-01-01');
        SalaryStructure::create(array_merge([
            'staff_id' => $staff->id, 'basic' => 150000, 'housing' => 50000, 'transport' => 30000, 'other_allowance' => 20000,
            'pension_enabled' => true, 'nhf_enabled' => false, 'deduct_absences' => true,
        ], $salary));

        return $user->fresh();
    }

    // --- Owner statements ------------------------------------------------------------------------

    public function test_statement_counts_paid_completed_stays_less_commission_and_owner_expenses(): void
    {
        $this->stay($this->flat, '2026-10-12');                                  // counts: 100,000 - 10,000 = 90,000
        $this->stay($this->flat, '2026-11-02');                                  // next month
        $this->stay($this->flat, '2026-10-20', ['booking_status' => 'cancelled']);
        $this->stay($this->flat, '2026-10-22', ['payment_status' => 'pending']);
        $this->expense($this->flat, '2026-10-05', 5000);
        $this->expense($this->flat, '2026-09-28', 2000);                         // recorded late, not on any statement
        $this->expense($this->flat, '2026-10-06', 3000, 'company');
        $this->expense($this->flat, '2026-11-01', 9000);                         // next month

        $this->actingAs($this->admin)->post(route('admin.statements.generate'), ['month' => '2026-10', 'owner_id' => $this->landlord->id])->assertSessionHasNoErrors();

        $statement = OwnerStatement::firstOrFail();
        $this->assertSame('90000.00', $statement->gross_revenue);
        $this->assertSame('18000.00', $statement->commission_amount);
        $this->assertSame('7000.00', $statement->expenses_total);
        $this->assertSame('65000.00', $statement->net_payout);
        $this->assertSame(4, $statement->nights_booked);
        $this->assertSame(3, $statement->lines()->count());
        $this->actingAs($this->admin)->get(route('admin.statements.show', $statement))->assertOk()->assertSee('65,000.00');
    }

    public function test_draft_can_be_recalculated_without_double_counting_and_approval_freezes_it(): void
    {
        $this->stay($this->flat, '2026-10-12');
        $expense = $this->expense($this->flat, '2026-10-05', 5000);
        $month = ['month' => '2026-10', 'owner_id' => $this->landlord->id];

        $this->actingAs($this->admin)->post(route('admin.statements.generate'), $month);
        $this->actingAs($this->admin)->post(route('admin.statements.generate'), $month);
        $statement = OwnerStatement::firstOrFail();
        $this->assertSame(1, OwnerStatement::count());
        $this->assertSame('5000.00', $statement->expenses_total);

        $this->actingAs($this->admin)->patch(route('admin.statements.approve', $statement))->assertSessionHas('success');
        $this->actingAs($this->admin)->post(route('admin.statements.generate'), $month)->assertSessionHasErrors('month');
        $this->actingAs($this->admin)->delete(route('admin.expenses.destroy', $expense))->assertSessionHas('error');
        $this->assertModelExists($expense);

        // November's statement does not repeat October's expense.
        $this->travelTo(CarbonImmutable::parse('2026-12-03'));
        $this->actingAs($this->admin)->post(route('admin.statements.generate'), ['month' => '2026-11', 'owner_id' => $this->landlord->id]);
        $this->assertSame('0.00', OwnerStatement::whereDate('period_start', '2026-11-01')->firstOrFail()->expenses_total);

        $this->actingAs($this->admin)->patch(route('admin.statements.paid', $statement), ['payment_reference' => 'TRF-123'])->assertSessionHas('success');
        $this->assertSame('paid', $statement->fresh()->status);
    }

    public function test_expenses_are_not_deducted_when_the_agreement_says_so(): void
    {
        $this->landlord->update(['deduct_expenses' => false]);
        $this->stay($this->flat, '2026-10-12');
        $this->expense($this->flat, '2026-10-05', 5000);

        $this->actingAs($this->admin)->post(route('admin.statements.generate'), ['month' => '2026-10']);

        $this->assertSame('72000.00', OwnerStatement::firstOrFail()->net_payout);
    }

    public function test_admin_records_an_expense_with_a_private_receipt(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');

        $this->actingAs($this->admin)->post(route('admin.expenses.store'), [
            'property_id' => $this->flat->id, 'category' => 'diesel', 'description' => 'Generator diesel', 'amount' => 45000,
            'spent_on' => '2026-11-10', 'charge_to' => 'owner',
            'receipt' => \Illuminate\Http\UploadedFile::fake()->image('receipt.jpg'),
        ])->assertSessionHasNoErrors();

        $expense = Expense::firstOrFail();
        \Illuminate\Support\Facades\Storage::disk('local')->assertExists($expense->receipt_path);
        $this->actingAs($this->admin)->get(route('admin.expenses.index', ['month' => '2026-11']))->assertOk()->assertSee('Generator diesel');
        $this->actingAs($this->admin)->get(route('admin.expenses.receipt', $expense))->assertOk();
        $this->actingAs($this->makeUser('customer'))->get(route('admin.expenses.receipt', $expense))->assertForbidden();
    }

    // --- Owner portal ------------------------------------------------------------------------------

    public function test_owner_portal_shows_only_their_own_approved_statements_and_properties(): void
    {
        $this->actingAs($this->admin)->post(route('admin.owners.portal', $this->landlord), [
            'email' => 'adeyemi@example.com', 'password' => 'OwnerPass123', 'password_confirmation' => 'OwnerPass123',
        ])->assertSessionHas('success');
        $ownerUser = User::where('email', 'adeyemi@example.com')->firstOrFail();
        $this->assertTrue($ownerUser->hasRole('owner'));

        $other = Owner::create(['name' => 'Chief Okafor', 'type' => 'individual', 'is_active' => true]);
        $otherFlat = $this->makeProperty('Okafor Flat', $other);
        $this->stay($this->flat, '2026-10-12');
        $this->stay($otherFlat, '2026-10-12');
        $this->actingAs($this->admin)->post(route('admin.statements.generate'), ['month' => '2026-10']);
        $mine = OwnerStatement::where('owner_id', $this->landlord->id)->firstOrFail();
        $theirs = OwnerStatement::where('owner_id', $other->id)->firstOrFail();

        // Login lands on the owner portal.
        auth()->logout();
        $this->post(route('login'), ['email' => 'adeyemi@example.com', 'password' => 'OwnerPass123'])->assertRedirect(route('owner.dashboard'));

        $this->actingAs($ownerUser)->get(route('owner.dashboard'))->assertOk()->assertSee('Adeyemi Flat')->assertDontSee('Okafor Flat');
        $this->actingAs($ownerUser)->get(route('owner.statements.show', $mine))->assertNotFound(); // still a draft

        $this->actingAs($this->admin)->patch(route('admin.statements.approve', $mine));
        $this->actingAs($this->admin)->patch(route('admin.statements.approve', $theirs));
        $this->actingAs($ownerUser)->get(route('owner.statements.index'))->assertOk()->assertSee('October 2026');
        $this->actingAs($ownerUser)->get(route('owner.statements.show', $mine))->assertOk()->assertSee('72,000.00');
        $this->actingAs($ownerUser)->get(route('owner.statements.pdf', $mine))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->actingAs($ownerUser)->get(route('owner.statements.show', $theirs))->assertNotFound();

        $this->actingAs($ownerUser)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($this->makeUser('customer'))->get(route('owner.dashboard'))->assertForbidden();
    }

    // --- Payroll ----------------------------------------------------------------------------------------

    public function test_payslip_statutory_deductions(): void
    {
        $staff = $this->makeStaff()->staff;
        $settings = app(PayrollSettings::class)->all();
        $slip = app(PayrollCalculator::class)->calculate($staff, CarbonImmutable::parse('2026-10-01'), CarbonImmutable::parse('2026-10-31'), $settings);

        // Gross 250,000; pension 8% / 10% of 230,000; PAYE on 3,000,000 - 220,800 = 2,779,200 a year:
        // 0% on the first 800,000, 15% on the next 1,979,200 = 296,880 a year = 24,740 a month.
        $this->assertEquals(250000, $slip['gross']);
        $this->assertEquals(18400, $slip['pension_employee']);
        $this->assertEquals(23000, $slip['pension_employer']);
        $this->assertEquals(0, $slip['nhf']);
        $this->assertEquals(24740, $slip['paye']);
        $this->assertEquals(206860, $slip['net']);

        // Rent relief: 20% of 1,200,000 rent = 240,000 off taxable income.
        $staff->salaryStructure->update(['annual_rent' => 1200000, 'nhf_enabled' => true]);
        $slip = app(PayrollCalculator::class)->calculate($staff->fresh(), CarbonImmutable::parse('2026-10-01'), CarbonImmutable::parse('2026-10-31'), $settings);
        $this->assertEquals(3750, $slip['nhf']); // 2.5% of 150,000
        // Taxable: 3,000,000 - (18,400 + 3,750) x 12 - 240,000 = 2,494,200 -> 15% of 1,694,200 = 254,130 / 12
        $this->assertEquals(21177.5, $slip['paye']);
    }

    public function test_overtime_and_missed_shifts_come_from_attendance(): void
    {
        $user = $this->makeStaff(['overtime_rate' => 1000]);
        $staff = $user->staff;
        $day = fn ($date) => StaffSchedule::create(['staff_id' => $staff->id, 'site_id' => $this->site->id, 'date' => $date, 'shift_type' => 'day', 'start_time' => '08:00', 'end_time' => '18:00', 'status' => 'scheduled']);

        $worked = $day('2026-10-06');
        Attendance::create(['staff_id' => $staff->id, 'site_id' => $this->site->id, 'staff_schedule_id' => $worked->id, 'clock_in_at' => '2026-10-06 08:00', 'clock_out_at' => '2026-10-06 20:00', 'worked_minutes' => 720]);
        Attendance::create(['staff_id' => $staff->id, 'site_id' => $this->site->id, 'clock_in_at' => '2026-10-11 09:00', 'clock_out_at' => '2026-10-11 12:00', 'worked_minutes' => 180]);
        $day('2026-10-07'); // never came
        $day('2026-10-08')->update(['status' => 'cancelled']);

        $slip = app(PayrollCalculator::class)->calculate($staff->fresh(), CarbonImmutable::parse('2026-10-01'), CarbonImmutable::parse('2026-10-31'), app(PayrollSettings::class)->all());

        $this->assertEquals(5, $slip['overtime_hours']); // 2 h over the shift + 3 h unscheduled
        $this->assertEquals(5000, $slip['overtime_pay']);
        $this->assertSame(1, $slip['absent_days']);
        $this->assertEquals(11363.64, $slip['absence_deduction']); // 250,000 / 22
        $this->assertEquals(243636.36, $slip['gross']);
        $this->assertEquals(15, $slip['details']['hours_worked']);
        $this->assertSame([$this->site->id => 1.0], $slip['details']['site_split']);
    }

    public function test_pay_run_lifecycle_payslips_bank_schedule_and_loans(): void
    {
        $user = $this->makeStaff();
        $other = $this->makeStaff();
        StaffDeduction::create(['staff_id' => $user->staff->id, 'type' => 'loan', 'description' => 'Laptop loan', 'monthly_amount' => 10000, 'balance' => 15000, 'is_active' => true]);

        $this->actingAs($this->superAdmin)->post(route('admin.payroll.generate'), ['month' => '2026-10'])->assertSessionHasNoErrors();
        $run = PayRun::firstOrFail();
        $slip = $run->payslips()->where('staff_id', $user->staff->id)->firstOrFail();
        $this->assertEquals(10000, $slip->other_deductions);
        $this->assertEquals(196860, $slip->net);

        // Staff cannot see a draft payslip.
        $this->actingAs($user)->get(route('staff.payslips.show', $slip))->assertNotFound();

        $this->actingAs($this->superAdmin)->patch(route('admin.payroll.approve', $run))->assertSessionHas('success');
        $this->actingAs($this->superAdmin)->post(route('admin.payroll.generate'), ['month' => '2026-10'])->assertSessionHasErrors('month');

        $this->actingAs($user)->get(route('staff.payslips.index'))->assertOk()->assertSee('October 2026')->assertSee('196,860.00');
        $this->actingAs($user)->get(route('staff.payslips.show', $slip))->assertOk()->assertSee('Laptop loan');
        $this->actingAs($user)->get(route('staff.payslips.pdf', $slip))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->actingAs($other)->get(route('staff.payslips.show', $slip))->assertNotFound();

        $csv = $this->actingAs($this->superAdmin)->get(route('admin.payroll.bank-schedule', $run))->assertOk()->streamedContent();
        $this->assertStringContainsString("'0123456789", $csv);
        $this->assertStringContainsString('196860.00', $csv);
        $this->actingAs($this->superAdmin)->get(route('admin.payroll.show', $run))->assertOk()->assertSee('Lekki Gardens');

        $this->actingAs($this->superAdmin)->patch(route('admin.payroll.paid', $run))->assertSessionHas('success');
        $loan = StaffDeduction::firstOrFail();
        $this->assertEquals(5000, $loan->balance);
        $this->assertTrue($loan->is_active);

        // November takes only what is left, then the loan closes.
        $this->travelTo(CarbonImmutable::parse('2026-12-02'));
        $this->actingAs($this->superAdmin)->post(route('admin.payroll.generate'), ['month' => '2026-11']);
        $november = PayRun::whereDate('period_start', '2026-11-01')->firstOrFail();
        $this->assertEquals(5000, $november->payslips()->where('staff_id', $user->staff->id)->value('other_deductions'));
        $this->actingAs($this->superAdmin)->patch(route('admin.payroll.approve', $november));
        $this->actingAs($this->superAdmin)->patch(route('admin.payroll.paid', $november));
        $this->assertEquals(0, $loan->fresh()->balance);
        $this->assertFalse($loan->fresh()->is_active);
    }

    public function test_payroll_is_super_admin_only_by_default_and_finance_is_open_to_admins(): void
    {
        $this->actingAs($this->admin)->get(route('admin.payroll.index'))->assertForbidden();
        $this->actingAs($this->admin)->get(route('admin.expenses.index'))->assertOk();
        $this->actingAs($this->admin)->get(route('admin.statements.index'))->assertOk();
        $this->actingAs($this->superAdmin)->get(route('admin.payroll.index'))->assertOk();

        // The pay panel on the staff page is hidden from admins without payroll access.
        $staff = $this->makeStaff();
        $this->actingAs($this->admin)->get(route('admin.staff.edit', $staff))->assertOk()->assertDontSee('Loans &amp; deductions', false);
        $this->actingAs($this->superAdmin)->get(route('admin.staff.edit', $staff))->assertOk()->assertSee('Loans &amp; deductions', false);
    }

    public function test_super_admin_sets_salary_and_rates(): void
    {
        $user = $this->makeUser('staff');
        Staff::create(['user_id' => $user->id, 'employee_id' => 'WP-9', 'hire_date' => '2026-01-01']);

        $this->actingAs($this->superAdmin)->put(route('admin.payroll.salary', $user), [
            'basic' => 120000, 'housing' => 30000, 'pension_enabled' => 1, 'nhf_enabled' => 0, 'deduct_absences' => 1,
            'bank_account_number' => '12345',
        ])->assertSessionHasErrors('bank_account_number');

        $this->actingAs($this->superAdmin)->put(route('admin.payroll.salary', $user), [
            'basic' => 120000, 'housing' => 30000, 'pension_enabled' => 1, 'nhf_enabled' => 0, 'deduct_absences' => 1,
            'bank_name' => 'Access', 'bank_account_number' => '0987654321',
        ])->assertSessionHasNoErrors();
        $this->assertEquals(150000, $user->staff->fresh()->salaryStructure->monthlyGross());

        $bands = [['width' => 1000000, 'rate' => 0], ['width' => '', 'rate' => 10], ['width' => 500, 'rate' => 20]];
        $rates = ['rent_relief_rate' => 20, 'rent_relief_cap' => 500000, 'pension_employee_rate' => 8, 'pension_employer_rate' => 10, 'nhf_rate' => 2.5, 'working_days_per_month' => 22];
        $this->actingAs($this->superAdmin)->post(route('admin.payroll.settings.update'), ['bands' => $bands] + $rates)->assertSessionHasErrors('bands');

        $bands = [['width' => 1000000, 'rate' => 0], ['width' => '', 'rate' => 10]];
        $this->actingAs($this->superAdmin)->post(route('admin.payroll.settings.update'), ['bands' => $bands] + $rates)->assertSessionHasNoErrors();
        $this->assertEquals([[1000000, 0], [null, 10]], app(PayrollSettings::class)->all()['paye_bands']);
        $this->actingAs($this->superAdmin)->get(route('admin.payroll.settings'))->assertOk();
    }
}
