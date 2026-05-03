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
        Schema::create('cas_command_histories', function (Blueprint $table) {
            $table->id();
            $table->string('session_token', 100);
            $table->unsignedInteger('sequence');
            $table->text('command');
            $table->timestamps();
            $table->index('session_token');
            $table->unique(['session_token', 'sequence']);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cas_command_histories');
    }
};
