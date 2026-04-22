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
        Schema::table('settings', function (Blueprint $table) {
            $table->boolean('backup_attendance_enabled')
                ->default(false)
                ->after('early_leave_tolerance');

            $table->unsignedInteger('backup_attendance_radius_meters')
                ->default(100)
                ->after('backup_attendance_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'backup_attendance_enabled',
                'backup_attendance_radius_meters',
            ]);
        });
    }
};
