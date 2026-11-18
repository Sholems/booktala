<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->string('unit_code')->nullable()->after('id');
            $table->string('unit_type', 40)->default('apartment')->after('unit_code');
        });

        $this->backfillUnitDimensions();

        Schema::table('properties', function (Blueprint $table) {
            $table->dropForeign(['owner_id']);
            $table->dropForeign(['site_id']);
            $table->unsignedBigInteger('owner_id')->nullable(false)->change();
            $table->unsignedBigInteger('site_id')->nullable(false)->change();
            $table->string('unit_code')->nullable(false)->change();
            $table->unique('unit_code');
            $table->foreign('owner_id')->references('id')->on('owners')->restrictOnDelete();
            $table->foreign('site_id')->references('id')->on('sites')->restrictOnDelete();
        });

        Schema::table('rooms', function (Blueprint $table) {
            $table->foreignId('unit_id')->nullable()->after('room_type_id')->constrained('properties')->restrictOnDelete();
            $table->string('unit_mapping_status', 20)->nullable()->after('unit_id');
            $table->text('unit_mapping_notes')->nullable()->after('unit_mapping_status');
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropConstrainedForeignId('unit_id');
            $table->dropColumn(['unit_mapping_status', 'unit_mapping_notes']);
        });

        Schema::table('properties', function (Blueprint $table) {
            $table->dropForeign(['owner_id']);
            $table->dropForeign(['site_id']);
            $table->unsignedBigInteger('owner_id')->nullable()->change();
            $table->unsignedBigInteger('site_id')->nullable()->change();
            $table->foreign('owner_id')->references('id')->on('owners')->nullOnDelete();
            $table->foreign('site_id')->references('id')->on('sites')->nullOnDelete();
            $table->dropUnique(['unit_code']);
            $table->dropColumn(['unit_code', 'unit_type']);
        });
    }

    private function backfillUnitDimensions(): void
    {
        $now = now();
        $ownerId = DB::table('owners')->where('is_company_owned', true)->orderBy('id')->value('id');

        if (! $ownerId) {
            $ownerId = DB::table('owners')->insertGetId([
                'name' => 'G-Briel Property Ltd',
                'type' => 'company',
                'is_company_owned' => true,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        foreach (DB::table('properties')->orderBy('id')->get() as $property) {
            $siteId = $property->site_id;

            if (! $siteId) {
                $siteId = DB::table('sites')->insertGetId([
                    'name' => $property->title,
                    'slug' => $this->uniqueSiteSlug($property->title),
                    'address' => $property->address,
                    'city' => $property->city,
                    'state' => $property->state,
                    'country' => $property->country ?? 'Nigeria',
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            DB::table('properties')->where('id', $property->id)->update([
                'owner_id' => $property->owner_id ?: $ownerId,
                'site_id' => $siteId,
                'unit_code' => sprintf('UNIT-%06d', $property->id),
            ]);
        }
    }

    private function uniqueSiteSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'site';
        $slug = $base;

        for ($suffix = 2; DB::table('sites')->where('slug', $slug)->exists(); $suffix++) {
            $slug = $base.'-'.$suffix;
        }

        return $slug;
    }
};
