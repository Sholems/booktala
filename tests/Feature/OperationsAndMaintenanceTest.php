<?php

namespace Tests\Feature;

use App\Models\FacilityMaintenanceSchedule;
use App\Models\GeneratorFuelLog;
use App\Models\HousekeepingInspection;
use App\Models\Owner;
use App\Models\Site;
use App\Models\Task;
use App\Models\Unit;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OperationsAndMaintenanceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $cleaner;

    private Site $site;

    private Owner $owner;

    private Unit $unit;

    private Task $task;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->travelTo(CarbonImmutable::parse('2026-11-22 10:00:00'));

        $this->admin = User::create([
            'name' => 'Supervisor Chief',
            'email' => 'supervisor@booktala.com',
            'password' => 'password123',
            'status' => 'active',
        ]);
        $this->admin->assignRole('super_admin');

        $this->cleaner = User::create([
            'name' => 'Cleaner Blessing',
            'email' => 'cleaner@booktala.com',
            'password' => 'password123',
            'status' => 'active',
        ]);
        $this->cleaner->assignRole('staff');

        $this->site = Site::create([
            'name' => 'Victoria Island Penthouse Tower',
            'address' => 'Plot 14 Victoria Island',
            'city' => 'Lagos',
            'state' => 'Lagos',
            'country' => 'Nigeria',
            'currency' => 'NGN',
            'is_active' => true,
        ]);

        $this->owner = Owner::create([
            'name' => 'Tower Landlord',
            'type' => 'individual',
            'is_company_owned' => false,
            'is_active' => true,
        ]);

        $this->unit = Unit::create([
            'unit_code' => 'VIP-401',
            'title' => 'VIP Suite 401',
            'description' => 'Luxury VIP suite',
            'address' => 'Plot 14 Victoria Island',
            'city' => 'Lagos',
            'state' => 'Lagos',
            'price_per_night' => 120000,
            'max_guests' => 4,
            'bedrooms' => 2,
            'bathrooms' => 2,
            'toilets' => 2,
            'status' => 'cleaning',
            'site_id' => $this->site->id,
            'owner_id' => $this->owner->id,
        ]);

        $this->task = Task::create([
            'title' => 'Turnover Clean VIP Suite 401',
            'description' => 'Complete clean and turnover checklist after departure.',
            'property_id' => $this->unit->id,
            'assigned_to' => $this->cleaner->id,
            'assigned_by' => $this->admin->id,
            'type' => 'cleaning',
            'priority' => 'urgent',
            'status' => 'in_progress',
            'due_date' => today(),
        ]);
    }

    public function test_cleaner_submits_inspection_with_checklist_and_photos(): void
    {
        Storage::fake('local');

        $beforePhoto = UploadedFile::fake()->image('before_bed.jpg');
        $afterPhoto = UploadedFile::fake()->image('after_bed.jpg');

        $response = $this->actingAs($this->cleaner)->post(route('staff.inspections.store', $this->task), [
            'checklist' => HousekeepingInspection::defaultChecklist(),
            'cleaner_notes' => 'Room thoroughly cleaned, fresh towels placed and air fresh sprayed.',
            'before_images' => [$beforePhoto],
            'after_images' => [$afterPhoto],
        ]);

        $response->assertRedirect(route('staff.tasks.show', $this->task));
        $response->assertSessionHas('success');

        $inspection = HousekeepingInspection::latest()->first();
        $this->assertNotNull($inspection);
        $this->assertSame(HousekeepingInspection::STATUS_PENDING_REVIEW, $inspection->status);
        $this->assertSame($this->cleaner->id, $inspection->cleaner_id);
        $this->assertSame($this->unit->id, $inspection->unit_id);
        $this->assertSame($this->task->id, $inspection->task_id);
        $this->assertCount(1, $inspection->before_photos);
        $this->assertCount(1, $inspection->after_photos);
        Storage::disk('local')->assertExists($inspection->before_photos[0]);
        Storage::disk('local')->assertExists($inspection->after_photos[0]);
        $this->get('/storage/'.$inspection->before_photos[0])->assertNotFound();
        $this->actingAs($this->admin)
            ->get(route('admin.inspections.photo', [$inspection, 'before', 0]))
            ->assertOk()
            ->assertHeader('cache-control', 'no-store, private');

        // Unit remains in cleaning status until supervisor verifies
        $this->assertSame('cleaning', $this->unit->fresh()->status);
    }

    public function test_supervisor_approves_inspection_and_room_becomes_available(): void
    {
        $inspection = HousekeepingInspection::create([
            'task_id' => $this->task->id,
            'unit_id' => $this->unit->id,
            'cleaner_id' => $this->cleaner->id,
            'status' => HousekeepingInspection::STATUS_PENDING_REVIEW,
            'checklist' => HousekeepingInspection::defaultChecklist(),
            'before_photos' => ['/storage/housekeeping_inspections/before1.jpg'],
            'after_photos' => ['/storage/housekeeping_inspections/after1.jpg'],
            'cleaner_notes' => 'All areas sanitized.',
            'submitted_at' => now(),
        ]);

        $this->assertSame('cleaning', $this->unit->fresh()->status);
        $this->assertSame('in_progress', $this->task->fresh()->status);

        $response = $this->actingAs($this->admin)->patch(route('admin.inspections.approve', $inspection), [
            'notes' => 'Checked linen, bathroom and fragrance - 5 star readiness.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $inspection->refresh();
        $this->assertSame(HousekeepingInspection::STATUS_APPROVED, $inspection->status);
        $this->assertSame($this->admin->id, $inspection->supervisor_id);
        $this->assertSame('Checked linen, bathroom and fragrance - 5 star readiness.', $inspection->supervisor_notes);
        $this->assertNotNull($inspection->reviewed_at);

        // Crucial: Task completes and unit switches from cleaning to available!
        $this->assertSame('completed', $this->task->fresh()->status);
        $this->assertSame('available', $this->unit->fresh()->status);
    }

    public function test_supervisor_rejects_inspection_unit_stays_in_cleaning(): void
    {
        $inspection = HousekeepingInspection::create([
            'task_id' => $this->task->id,
            'unit_id' => $this->unit->id,
            'cleaner_id' => $this->cleaner->id,
            'status' => HousekeepingInspection::STATUS_PENDING_REVIEW,
            'checklist' => HousekeepingInspection::defaultChecklist(),
            'cleaner_notes' => 'Turnover done.',
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->patch(route('admin.inspections.reject', $inspection), [
            'notes' => 'Mirror has water streaks and dust on AC vent. Please wipe down.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $inspection->refresh();
        $this->assertSame(HousekeepingInspection::STATUS_REJECTED, $inspection->status);
        $this->assertSame($this->admin->id, $inspection->supervisor_id);
        $this->assertSame('Mirror has water streaks and dust on AC vent. Please wipe down.', $inspection->supervisor_notes);

        // Unit stays in cleaning
        $this->assertSame('cleaning', $this->unit->fresh()->status);
        $this->assertSame('in_progress', $this->task->fresh()->status);
    }

    public function test_preventative_facility_maintenance_schedule_creation_and_completion(): void
    {
        // 1. Create schedule
        $response = $this->actingAs($this->admin)->post(route('admin.facilities.store'), [
            'site_id' => $this->site->id,
            'property_id' => $this->unit->id,
            'equipment_type' => 'ac',
            'title' => 'VIP Suite Split AC Filter & Gas Routine',
            'frequency_days' => 45,
            'next_due_on' => '2026-11-25',
            'vendor_or_staff' => 'CoolBreeze HVAC Ltd',
            'estimated_cost' => 25000,
            'notes' => 'Check R410 gas pressure and clean evaporator coil.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $schedule = FacilityMaintenanceSchedule::latest()->first();
        $this->assertNotNull($schedule);
        $this->assertSame('VIP Suite Split AC Filter & Gas Routine', $schedule->title);
        $this->assertSame(45, $schedule->frequency_days);
        $this->assertEquals(25000, (float) $schedule->estimated_cost);

        // 2. Complete service
        $completeResponse = $this->actingAs($this->admin)->patch(route('admin.facilities.complete', $schedule), [
            'service_notes' => 'Coils washed, gas topped up by 1.5kg.',
            'actual_cost' => 28000,
        ]);

        $completeResponse->assertRedirect();
        $completeResponse->assertSessionHas('success');

        $schedule->refresh();
        $this->assertSame('2026-11-22', $schedule->last_serviced_on->format('Y-m-d'));
        // Next due should be today + 45 days
        $this->assertSame('2027-01-06', $schedule->next_due_on->format('Y-m-d'));
        $this->assertEquals(28000, (float) $schedule->estimated_cost);
    }

    public function test_generator_fuel_and_run_hour_logging(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.facilities.generator-logs.store'), [
            'site_id' => $this->site->id,
            'generator_name' => '100kVA Perkins Standby Gen',
            'run_hours_start' => 2450.0,
            'run_hours_end' => 2462.5,
            'litres_added' => 200.0,
            'diesel_cost' => 250000.0,
            'fuel_level_before_percent' => 20,
            'fuel_level_after_percent' => 95,
            'logged_at' => '2026-11-22 09:00:00',
            'notes' => 'Filled up from Conoil diesel tanker.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $log = GeneratorFuelLog::latest()->first();
        $this->assertNotNull($log);
        $this->assertEquals(12.5, (float) $log->hours_run);
        $this->assertEquals(200.0, (float) $log->litres_added);
        $this->assertEquals(250000.0, (float) $log->diesel_cost);
        $this->assertSame(95, $log->fuel_level_after_percent);
        $this->assertSame($this->admin->id, $log->logged_by);

        // Fuel burn efficiency: 200L / 12.5 hrs = 16.0 L/hr
        $this->assertEquals(16.0, $log->litres_per_hour);

        // View index
        $indexResponse = $this->actingAs($this->admin)->get(route('admin.facilities.generator-logs'));
        $indexResponse->assertOk();
        $indexResponse->assertSee('100kVA Perkins Standby Gen');
        $indexResponse->assertSee('12.5 hrs');
        $indexResponse->assertSee('200.0 L');
    }
}
