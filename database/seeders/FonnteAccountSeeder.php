<?php

namespace Database\Seeders;

use App\Models\FonnteAccount;
use Illuminate\Database\Seeder;

class FonnteAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $accounts = [
            [
                'event_type' => FonnteAccount::EVENT_CHECK_IN,
                'account_name' => 'Fonnte Masuk',
                'base_url' => 'https://api.fonnte.com',
                'token' => null,
                'parent_group_target' => null,
                'timeout' => 10,
                'is_active' => true,
            ],
            [
                'event_type' => FonnteAccount::EVENT_CHECK_OUT,
                'account_name' => 'Fonnte Pulang',
                'base_url' => 'https://api.fonnte.com',
                'token' => null,
                'parent_group_target' => null,
                'timeout' => 10,
                'is_active' => true,
            ],
        ];

        foreach ($accounts as $account) {
            FonnteAccount::query()->updateOrCreate(
                ['event_type' => $account['event_type']],
                $account,
            );
        }
    }
}
