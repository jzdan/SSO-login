<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Semua opsional (mis. mitra tanpa NIP), tapi bila diisi harus unik karena dipakai untuk login.
            $table->char('nip_lama', 9)->nullable()->unique()->after('name');
            $table->char('nip_baru', 12)->nullable()->unique()->after('nip_lama');
            $table->string('email_bps')->nullable()->unique()->after('email_verified_at');
            $table->timestamp('email_bps_verified_at')->nullable()->after('email_bps');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['nip_lama']);
            $table->dropUnique(['nip_baru']);
            $table->dropUnique(['email_bps']);
            $table->dropColumn(['nip_lama', 'nip_baru', 'email_bps', 'email_bps_verified_at']);
        });
    }
};
