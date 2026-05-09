<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cas_logs', function (Blueprint $table) {
            // SHA-256 hash IP adresy ma 64 znakov, povodna dlzka 45 stacila iba na citatelnu IP.
            $table->string('ip_address', 64)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cas_logs', function (Blueprint $table) {
            $table->string('ip_address', 45)->nullable()->change();
        });
    }
};
