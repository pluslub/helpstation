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
        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->string('login_id', 50)->unique();
            $table->string('password');
            $table->string('name', 100);
            $table->enum('role', ['staff', 'admin']);
            $table->unsignedInteger('annual_work_hour_limit')->nullable();
            $table->unsignedTinyInteger('failed_login_count')->default(0);
            $table->dateTime('last_failed_login_at')->nullable();
            $table->dateTime('locked_until')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};
