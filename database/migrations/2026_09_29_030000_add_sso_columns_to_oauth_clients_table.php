<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('oauth_clients', function (Blueprint $table) {
            $table->string('description')->nullable()->after('name');
            $table->string('homepage_url')->nullable()->after('description');
            $table->boolean('skip_authorization')->default(true)->after('homepage_url');
            $table->boolean('show_on_dashboard')->default(true)->after('skip_authorization');
        });
    }

    public function down(): void
    {
        Schema::table('oauth_clients', function (Blueprint $table) {
            $table->dropColumn(['description', 'homepage_url', 'skip_authorization', 'show_on_dashboard']);
        });
    }
};
