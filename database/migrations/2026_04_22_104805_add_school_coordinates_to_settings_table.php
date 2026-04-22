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
            $table->decimal('school_latitude', 10, 7)
                ->nullable()
                ->after('backup_attendance_radius_meters');

            $table->decimal('school_longitude', 10, 7)
                ->nullable()
                ->after('school_latitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'school_latitude',
                'school_longitude',
            ]);
        });
    }
};
