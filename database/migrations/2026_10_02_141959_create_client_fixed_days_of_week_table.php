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
        Schema::create('client_fixed_days_of_week', function (Blueprint $table) {
            $table->foreignId('client_id')->constrained('clients');
            $table->unsignedTinyInteger('day_of_week');
            $table->time('fixed_start_time');
            $table->time('fixed_end_time');

            $table->primary(['client_id', 'day_of_week']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_fixed_days_of_week');
    }
};
