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
        Schema::create('staff_default_schedule', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff');
            $table->unsignedTinyInteger('day_of_week');
            $table->boolean('is_am_off')->default(false);
            $table->boolean('is_pm_off')->default(false);
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();

            $table->unique(['staff_id', 'day_of_week']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_default_schedule');
    }
};
