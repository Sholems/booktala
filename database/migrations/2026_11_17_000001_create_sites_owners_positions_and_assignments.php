<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Multi-property foundation: who owns a property (Owner), where it is (Site),
 * what a staff member does (Position) and where they do it (StaffAssignment).
 *
 * Existing data is carried over: every property goes to the company owner and
 * gets its own one-unit site, and every existing staff member keeps access to
 * all sites (as before) until an admin narrows their assignments.
 */
return new class extends Migration
{
    public const COMPANY_OWNER = 'G-Briel Property Ltd';

    /** name => [department, level] */
    public const DEFAULT_POSITIONS = [
        'Receptionist' => ['frontdesk', 'staff'],
        'Cleaner' => ['housekeeping', 'staff'],
        'Housekeeping Supervisor' => ['housekeeping', 'supervisor'],
        'Laundry Attendant' => ['laundry', 'staff'],
        'Maintenance Technician' => ['maintenance', 'staff'],
        'Security Guard' => ['security', 'staff'],
        'Head of Security' => ['security', 'supervisor'],
        'Site Supervisor' => ['management', 'supervisor'],
        'Operations Manager' => ['management', 'manager'],
    ];

    /** Old fixed staff departments => the position they become. */
    private const DEPARTMENT_POSITIONS = [
        'frontdesk' => 'Receptionist',
        'housekeeping' => 'Cleaner',
        'maintenance' => 'Maintenance Technician',
    ];

    public function up(): void
    {
        Schema::create('owners', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['company', 'individual'])->default('individual');
            $table->boolean('is_company_owned')->default(false); // G-Briel's own portfolio
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('address')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // owner portal login (later)
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('address');
            $table->string('city');
            $table->string('state');
            $table->string('country')->default('Nigeria');
            $table->string('phone', 30)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('department', 30);
            $table->enum('level', ['staff', 'supervisor', 'manager'])->default('staff');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('staff_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('position_id')->constrained()->restrictOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->timestamps();

            $table->index(['site_id', 'ends_on']);
            $table->index(['staff_id', 'ends_on']);
        });

        Schema::table('properties', function (Blueprint $table) {
            $table->foreignId('owner_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->foreignId('site_id')->nullable()->after('owner_id')->constrained()->nullOnDelete();
        });

        // Departments now come from positions; the old fixed list is too narrow.
        Schema::table('staff', function (Blueprint $table) {
            $table->string('department', 30)->nullable()->change();
        });

        $this->backfill();
    }

    private function backfill(): void
    {
        $now = now();

        foreach (self::DEFAULT_POSITIONS as $name => [$department, $level]) {
            DB::table('positions')->insert([
                'name' => $name, 'department' => $department, 'level' => $level,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        $ownerId = DB::table('owners')->insertGetId([
            'name' => self::COMPANY_OWNER, 'type' => 'company', 'is_company_owned' => true,
            'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
        ]);

        $siteIds = [];
        foreach (DB::table('properties')->orderBy('id')->get() as $property) {
            $siteId = DB::table('sites')->insertGetId([
                'name' => $property->title,
                'slug' => $this->uniqueSlug($property->title),
                'address' => $property->address,
                'city' => $property->city,
                'state' => $property->state,
                'country' => $property->country ?? 'Nigeria',
                'is_active' => true,
                'created_at' => $now, 'updated_at' => $now,
            ]);
            $siteIds[] = $siteId;

            DB::table('properties')->where('id', $property->id)
                ->update(['owner_id' => $ownerId, 'site_id' => $siteId]);
        }

        $positionIds = DB::table('positions')->pluck('id', 'name');
        foreach (DB::table('staff')->get() as $staff) {
            $positionId = $positionIds[self::DEPARTMENT_POSITIONS[$staff->department] ?? 'Receptionist'];

            foreach ($siteIds as $i => $siteId) {
                DB::table('staff_assignments')->insert([
                    'staff_id' => $staff->id,
                    'site_id' => $siteId,
                    'position_id' => $positionId,
                    'is_primary' => $i === 0,
                    'starts_on' => $staff->hire_date ?? $now->toDateString(),
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'site';
        $slug = $base;
        for ($i = 2; DB::table('sites')->where('slug', $slug)->exists(); $i++) {
            $slug = $base.'-'.$i;
        }

        return $slug;
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropConstrainedForeignId('site_id');
            $table->dropConstrainedForeignId('owner_id');
        });

        Schema::dropIfExists('staff_assignments');
        Schema::dropIfExists('positions');
        Schema::dropIfExists('sites');
        Schema::dropIfExists('owners');
        // staff.department stays a nullable string: positions may have added departments the old enum cannot hold.
    }
};
