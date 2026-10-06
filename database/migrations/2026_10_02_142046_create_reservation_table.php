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
        Schema::create('reservation', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('client');
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->foreignId('support_type_id')->constrained('support_type');
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicle');
            $table->enum('vehicle_type_choice', ['care_vehicle', 'company_car', 'private_car', 'none']);
            $table->text('remarks')->nullable();
            $table->enum('status', ['provisional', 'approved'])->default('provisional');
            $table->boolean('vehicle_reassigned_flag')->default(false);
            $table->timestamps();

            $table->index('date');
            $table->index(['vehicle_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservation');
    }
};
