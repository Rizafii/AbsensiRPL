<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->time('monday_check_in_time')->nullable()->after('check_out_time');
            $table->time('monday_check_out_time')->nullable()->after('monday_check_in_time');
            $table->time('tuesday_check_in_time')->nullable()->after('monday_check_out_time');
            $table->time('tuesday_check_out_time')->nullable()->after('tuesday_check_in_time');
            $table->time('wednesday_check_in_time')->nullable()->after('tuesday_check_out_time');
            $table->time('wednesday_check_out_time')->nullable()->after('wednesday_check_in_time');
            $table->time('thursday_check_in_time')->nullable()->after('wednesday_check_out_time');
            $table->time('thursday_check_out_time')->nullable()->after('thursday_check_in_time');
            $table->time('friday_check_in_time')->nullable()->after('thursday_check_out_time');
            $table->time('friday_check_out_time')->nullable()->after('friday_check_in_time');
        });

        DB::table('settings')
            ->select(['id', 'check_in_time', 'check_out_time'])
            ->orderBy('id')
            ->chunkById(100, function ($settings): void {
                foreach ($settings as $setting) {
                    DB::table('settings')
                        ->where('id', $setting->id)
                        ->update([
                            'monday_check_in_time' => $setting->check_in_time,
                            'monday_check_out_time' => $setting->check_out_time,
                            'tuesday_check_in_time' => $setting->check_in_time,
                            'tuesday_check_out_time' => $setting->check_out_time,
                            'wednesday_check_in_time' => $setting->check_in_time,
                            'wednesday_check_out_time' => $setting->check_out_time,
                            'thursday_check_in_time' => $setting->check_in_time,
                            'thursday_check_out_time' => $setting->check_out_time,
                            'friday_check_in_time' => $setting->check_in_time,
                            'friday_check_out_time' => $setting->check_out_time,
                        ]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'monday_check_in_time',
                'monday_check_out_time',
                'tuesday_check_in_time',
                'tuesday_check_out_time',
                'wednesday_check_in_time',
                'wednesday_check_out_time',
                'thursday_check_in_time',
                'thursday_check_out_time',
                'friday_check_in_time',
                'friday_check_out_time',
            ]);
        });
    }
};
