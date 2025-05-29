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
        Schema::table('attendances', function (Blueprint $table) {
            $table->enum('overdue', ['on_time', 'tl_1', 'tl_2', 'not_present'])->default('on_time')->after('start_attendance_photo');
            $table->enum('return', ['on_time', 'psw_1', 'psw_2', 'not_present'])->default('on_time')->after('end_attendance_photo');
            $table->integer('overdue_minutes')->default(0)->after('overdue');
            $table->integer('return_minutes')->default(0)->after('return');
            $table->enum('overall_status', ['perfect', 'overdue_only', 'return_only', 'red_flag', 'absent'])->after('return_minutes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn('overdue');
            $table->dropColumn('return');
            $table->dropColumn('overdue_minutes');
            $table->dropColumn('return_minutes');
            $table->dropColumn('overall_status');
        });
    }
};
