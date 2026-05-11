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
        Schema::create('animation_usages', function (Blueprint $table) {
            $table->id();
            $table->string('animation_type', 50);
            $table->string('visitor_token', 100);
            $table->string('ip_hash', 64)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('country', 100)->nullable();
            $table->timestamps();

            $table->index(['animation_type', 'visitor_token', 'created_at']);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('animation_usages');
    }
};
