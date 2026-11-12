<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        Artisan::call('migrate', [
            '--path' => 'database/migrations/2026_11_11_193430_seed_master_data_from_local.php',
            '--force' => true,
        ]);

        $this->command?->info(Artisan::output());
    }
}
