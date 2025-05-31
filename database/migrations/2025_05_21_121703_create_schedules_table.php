<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->foreignId('shift_id')->constrained('shifts')->cascadeOnDelete();
            $table->foreignId('office_id')->constrained('offices')->cascadeOnDelete();
            $table->boolean('is_wfa')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
