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
        Schema::create('client_default_staff', function (Blueprint $table) {
            $table->foreignId('client_id')->constrained('clients');
            $table->foreignId('staff_id')->constrained('staff');

            $table->primary(['client_id', 'staff_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_default_staff');
    }
};
