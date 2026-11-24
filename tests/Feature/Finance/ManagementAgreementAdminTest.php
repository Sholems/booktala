<?php

namespace Tests\Feature\Finance;

use App\Models\Expense;
use App\Models\ManagementAgreement;
use App\Models\Owner;
use App\Models\OwnerStatement;
use App\Models\OwnerStatementLine;
use App\Models\Position;
use App\Models\Site;
use App\Models\Staff;
use App\Models\StaffAssignment;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ManagementAgreementAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Site $siteA;

    private Site $siteB;

    private Unit $unitA;

    private Unit $unitB;

    private Owner $externalOwner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->siteA = Site::create(['name' => 'Site Alpha', 'address' => '1 Alpha Way', 'city' => 'Lagos', 'state' => 'Lagos']);
        $this->siteB = Site::create(['name' => 'Site Beta', 'address' => '2 Beta Way', 'city' => 'Abuja', 'state' => 'FCT']);

        $this->externalOwner = Owner::create([
            'name' => 'External Landlord',
            'type' => 'individual',
            'is_company_owned' => false,
        ]);

        $this->unitA = Unit::factory()->create([
            'title' => 'Unit Alpha 1',
            'site_id' => $this->siteA->id,
            'owner_id' => $this->externalOwner->id,
        ]);

        $this->unitB = Unit::factory()->create([
            'title' => 'Unit Beta 1',
            'site_id' => $this->siteB->id,
            'owner_id' => $this->externalOwner->id,
        ]);
    }

    public function test_authorized_finance_staff_can_draft_and_review_an_agreement(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.agreements.store'), [
            'unit_id' => $this->unitA->id,
            'owner_id' => $this->externalOwner->id,
            'commission_rate' => 22.5,
            'deduct_expenses' => 1,
            'caution_forfeiture_recipient' => 'company',
            'effective_from' => '2026-01-01',
            'effective_to' => '2026-12-31',
            'notes' => 'Test commercial terms',
        ]);

        $agreement = ManagementAgreement::where('unit_id', $this->unitA->id)->firstOrFail();
        $response->assertRedirect(route('admin.agreements.show', $agreement));

        $this->assertSame('draft', $agreement->status);
        $this->assertSame(22.5, (float) $agreement->commission_rate);
        $this->assertSame($this->admin->id, $agreement->created_by);

        // Review the agreement
        $reviewResponse = $this->actingAs($this->admin)->patch(route('admin.agreements.review', $agreement));
        $reviewResponse->assertRedirect();

        $agreement->refresh();
        $this->assertSame('reviewed', $agreement->status);
        $this->assertSame($this->admin->id, $agreement->reviewed_by);
        $this->assertNotNull($agreement->reviewed_at);

        // Retire the agreement
        $retireResponse = $this->actingAs($this->admin)->patch(route('admin.agreements.retire', $agreement));
        $retireResponse->assertRedirect();

        $agreement->refresh();
        $this->assertSame('retired', $agreement->status);
    }

    public function test_staff_without_access_to_the_site_cannot_view_or_create_agreements(): void
    {
        // Admin assigned only to Site A, without portfolio.view_all
        $adminRole = Role::findByName('admin');
        $adminRole->revokePermissionTo('portfolio.view_all');

        $scopedStaff = User::factory()->create();
        $scopedStaff->assignRole('admin');
        $staffProfile = Staff::create([
            'user_id' => $scopedStaff->id,
            'department' => 'frontdesk',
            'employee_id' => 'EMP-001',
            'hire_date' => '2026-01-01',
            'is_active' => true,
        ]);
        $position = Position::where('name', 'Receptionist')->first()
            ?? Position::create(['name' => 'Receptionist', 'department' => 'frontdesk', 'level' => 'staff']);

        StaffAssignment::create([
            'staff_id' => $staffProfile->id,
            'site_id' => $this->siteA->id,
            'position_id' => $position->id,
            'is_primary' => true,
            'starts_on' => today()->subMonth(),
        ]);

        // Attempt to create agreement for Unit B (at Site B)
        $response = $this->actingAs($scopedStaff)->post(route('admin.agreements.store'), [
            'unit_id' => $this->unitB->id,
            'owner_id' => $this->externalOwner->id,
            'commission_rate' => 15,
            'effective_from' => '2026-01-01',
        ]);
        $response->assertStatus(404);

        // Attempt to view agreement for Unit B
        $agreementB = ManagementAgreement::create([
            'unit_id' => $this->unitB->id,
            'owner_id' => $this->externalOwner->id,
            'commission_rate' => 15,
            'effective_from' => '2026-01-01',
            'status' => 'draft',
        ]);

        $viewResponse = $this->actingAs($scopedStaff)->get(route('admin.agreements.show', $agreementB));
        $viewResponse->assertStatus(404);
    }

    public function test_site_scoped_finance_admin_cannot_read_or_mutate_another_sites_finances(): void
    {
        $adminRole = Role::findByName('admin');
        $adminRole->revokePermissionTo('portfolio.view_all');

        $scopedAdmin = User::factory()->create();
        $scopedAdmin->assignRole('admin');
        $staff = Staff::create([
            'user_id' => $scopedAdmin->id,
            'department' => 'finance',
            'employee_id' => 'FIN-001',
            'hire_date' => '2026-01-01',
            'is_active' => true,
        ]);
        $position = Position::firstOrCreate(
            ['name' => 'Site Accountant'],
            ['department' => 'finance', 'level' => 'manager'],
        );
        StaffAssignment::create([
            'staff_id' => $staff->id,
            'site_id' => $this->siteA->id,
            'position_id' => $position->id,
            'is_primary' => true,
            'starts_on' => today()->subMonth(),
        ]);

        $ownerB = Owner::create(['name' => 'Site Beta Owner', 'type' => 'individual', 'is_active' => true]);
        $this->unitB->update(['owner_id' => $ownerB->id]);
        Storage::fake('local');
        Storage::disk('local')->put('receipts/site-beta.jpg', 'private receipt');

        $expenseA = Expense::create([
            'property_id' => $this->unitA->id,
            'category' => 'repairs',
            'description' => 'Alpha repair',
            'amount' => 1000,
            'spent_on' => today(),
            'charge_to' => 'company',
        ]);
        $expenseB = Expense::create([
            'property_id' => $this->unitB->id,
            'category' => 'repairs',
            'description' => 'Beta confidential repair',
            'amount' => 2000,
            'spent_on' => today(),
            'charge_to' => 'company',
            'receipt_path' => 'receipts/site-beta.jpg',
        ]);

        $expenseIndex = $this->actingAs($scopedAdmin)->get(route('admin.expenses.index', ['month' => today()->format('Y-m')]));
        $expenseIndex->assertOk()->assertSee($expenseA->description)->assertDontSee($expenseB->description);
        $this->actingAs($scopedAdmin)->get(route('admin.expenses.receipt', $expenseB))->assertNotFound();
        $this->actingAs($scopedAdmin)->delete(route('admin.expenses.destroy', $expenseB))->assertNotFound();
        $this->assertModelExists($expenseB);

        $statementB = OwnerStatement::create([
            'owner_id' => $ownerB->id,
            'period_start' => today()->startOfMonth(),
            'period_end' => today()->endOfMonth(),
            'gross_revenue' => 10000,
            'commission_rate' => 20,
            'commission_amount' => 2000,
            'expenses_total' => 0,
            'net_payout' => 8000,
            'nights_booked' => 1,
            'status' => 'draft',
        ]);
        OwnerStatementLine::create([
            'owner_statement_id' => $statementB->id,
            'property_id' => $this->unitB->id,
            'type' => 'stay',
            'date' => today(),
            'description' => 'Beta stay',
            'nights' => 1,
            'amount' => 10000,
        ]);

        $statementIndex = $this->actingAs($scopedAdmin)->get(route('admin.statements.index', ['month' => today()->format('Y-m')]));
        $statementIndex->assertOk()->assertDontSee($ownerB->name);
        $this->actingAs($scopedAdmin)->get(route('admin.statements.show', $statementB))->assertNotFound();
        $this->actingAs($scopedAdmin)->get(route('admin.statements.pdf', $statementB))->assertNotFound();
        $this->actingAs($scopedAdmin)->patch(route('admin.statements.approve', $statementB))->assertNotFound();
        $this->actingAs($scopedAdmin)->patch(route('admin.statements.paid', $statementB))->assertNotFound();
        $this->actingAs($scopedAdmin)->delete(route('admin.statements.destroy', $statementB))->assertNotFound();
        $this->assertSame('draft', $statementB->fresh()->status);
    }

    public function test_review_fails_when_agreement_overlaps_existing_reviewed_period(): void
    {
        // First reviewed agreement
        $first = ManagementAgreement::create([
            'unit_id' => $this->unitA->id,
            'owner_id' => $this->externalOwner->id,
            'commission_rate' => 20,
            'effective_from' => '2026-01-01',
            'effective_to' => '2026-12-31',
            'status' => 'reviewed',
        ]);

        // Second draft agreement that overlaps
        $overlap = ManagementAgreement::create([
            'unit_id' => $this->unitA->id,
            'owner_id' => $this->externalOwner->id,
            'commission_rate' => 25,
            'effective_from' => '2026-06-01',
            'effective_to' => '2027-05-31',
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->admin)->patch(route('admin.agreements.review', $overlap));
        $response->assertSessionHas('error', 'This unit already has an agreement covering part of that period.');

        $this->assertSame('draft', $overlap->fresh()->status);
    }

    public function test_coverage_audit_identifies_uncovered_external_units_in_scope(): void
    {
        // Company-owned unit should be ignored by audit
        $companyOwner = Owner::company();
        $companyUnit = Unit::factory()->create([
            'site_id' => $this->siteA->id,
            'owner_id' => $companyOwner->id,
        ]);

        // Unit A has no agreements yet -> should appear in audit
        $response = $this->actingAs($this->admin)->get(route('admin.agreements.audit', ['site_id' => $this->siteA->id]));
        $response->assertOk();
        $response->assertSee('Unit Alpha 1');
        $response->assertDontSee($companyUnit->title);

        // Add reviewed agreement for Unit A covering today
        ManagementAgreement::create([
            'unit_id' => $this->unitA->id,
            'owner_id' => $this->externalOwner->id,
            'commission_rate' => 20,
            'effective_from' => today()->subMonth()->toDateString(),
            'status' => 'reviewed',
        ]);

        $reAudit = $this->actingAs($this->admin)->get(route('admin.agreements.audit', ['site_id' => $this->siteA->id]));
        $reAudit->assertOk();
        $reAudit->assertDontSee('Unit Alpha 1');
        $reAudit->assertSee('All external units in this scope have active reviewed management agreements');
    }
}
