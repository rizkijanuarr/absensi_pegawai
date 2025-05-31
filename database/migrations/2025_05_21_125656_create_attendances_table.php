<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->double('schedule_latitude')->nullable();
            $table->double('schedule_longitude')->nullable();
            $table->time('schedule_start_time', 0)->nullable();
            $table->time('schedule_end_time', 0)->nullable();
            $table->double('start_latitude')->nullable();
            $table->double('start_longitude')->nullable();
            $table->time('start_time', 0)->nullable();
            $table->string('start_attendance_photo')->nullable();
            $table->double('end_latitude')->nullable();
            $table->double('end_longitude')->nullable();
            $table->time('end_time', 0)->nullable();
            $table->string('end_attendance_photo')->nullable();
            $table->enum('overdue', ['on_time', 'tl_1', 'tl_2', 'not_present'])->nullable();
            $table->integer('overdue_minutes')->default(0);
            $table->enum('return', ['on_time', 'psw_1', 'psw_2', 'not_present'])->nullable();
            $table->integer('return_minutes')->default(0);
            $table->integer('work_duration')->unsigned()->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
