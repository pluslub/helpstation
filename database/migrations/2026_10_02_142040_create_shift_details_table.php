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
        Schema::create('shift_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_id')->constrained('shifts');
            $table->date('date');
            $table->time('applied_start_time')->nullable();
            $table->time('applied_end_time')->nullable();
            $table->boolean('applied_am_off')->default(false);
            $table->boolean('applied_pm_off')->default(false);
            $table->boolean('applied_desired_off')->default(false);
            $table->boolean('approval_flag')->default(false);
            $table->boolean('admin_modified_flag')->default(false);
            $table->time('modified_start_time')->nullable();
            $table->time('modified_end_time')->nullable();
            $table->boolean('modified_am_off')->default(false);
            $table->boolean('modified_pm_off')->default(false);
            $table->boolean('modified_approval_flag')->default(false);

            $table->unique(['shift_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shift_details');
    }
};
