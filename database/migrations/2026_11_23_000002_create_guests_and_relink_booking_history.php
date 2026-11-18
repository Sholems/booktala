<?php

use App\Services\GuestService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('normalized_email')->nullable()->index();
            $table->string('phone', 30)->nullable();
            $table->string('normalized_phone', 30)->nullable()->index();
            $table->string('status', 20)->default('active');
            $table->timestamps();
        });

        Schema::table('reservations', fn (Blueprint $table) => $table->foreignId('guest_id')->nullable()->after('user_id')->constrained()->restrictOnDelete());
        Schema::table('payments', fn (Blueprint $table) => $table->foreignId('guest_id')->nullable()->after('user_id')->constrained()->nullOnDelete());
        Schema::table('invoices', fn (Blueprint $table) => $table->foreignId('guest_id')->nullable()->after('user_id')->constrained()->nullOnDelete());
        Schema::table('guest_profiles', fn (Blueprint $table) => $table->foreignId('guest_id')->nullable()->unique()->after('user_id')->constrained()->nullOnDelete());

        $this->backfillGuests();
        $this->preserveCheckinIdentification();

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->unsignedBigInteger('guest_id')->nullable(false)->change();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
        foreach (['payments', 'invoices', 'guest_profiles'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->unsignedBigInteger('user_id')->nullable()->change();
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            });
        }
        Schema::table('guest_checkins', function (Blueprint $table) {
            $table->text('id_number')->change();
            $table->text('id_document_path')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('guest_checkins', function (Blueprint $table) {
            $table->string('id_number')->change();
            $table->string('id_document_path')->nullable()->change();
        });
        foreach (['guest_profiles', 'invoices', 'payments'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->unsignedBigInteger('user_id')->nullable(false)->change();
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                $table->dropConstrainedForeignId('guest_id');
            });
        }
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->dropConstrainedForeignId('guest_id');
        });
        Schema::dropIfExists('guests');
    }

    private function backfillGuests(): void
    {
        $now = now();
        foreach (DB::table('users')->whereIn('id', DB::table('reservations')->whereNotNull('user_id')->select('user_id'))->orderBy('id')->get() as $user) {
            $guestId = DB::table('guests')->insertGetId([
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'normalized_email' => GuestService::normalizeEmail($user->email),
                'phone' => $user->phone,
                'normalized_phone' => GuestService::normalizePhone($user->phone),
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            DB::table('reservations')->where('user_id', $user->id)->update(['guest_id' => $guestId]);
            DB::table('payments')->where('user_id', $user->id)->update(['guest_id' => $guestId]);
            DB::table('invoices')->where('user_id', $user->id)->update(['guest_id' => $guestId]);
            DB::table('guest_profiles')->where('user_id', $user->id)->update(['guest_id' => $guestId]);
        }
    }

    private function preserveCheckinIdentification(): void
    {
        DB::table('guest_checkins')->orderBy('id')->each(function (object $checkin) {
            DB::table('guest_checkins')->where('id', $checkin->id)->update([
                'id_number' => Crypt::encryptString($checkin->id_number),
                'id_document_path' => $checkin->id_document_path ? Crypt::encryptString($checkin->id_document_path) : null,
            ]);
        });
    }
};
