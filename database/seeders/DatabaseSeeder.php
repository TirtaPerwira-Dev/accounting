<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Generate permissions first before seeding roles
        $this->command->info('Generating permissions...');
        Artisan::call('shield:generate', ['--all' => true, '--panel' => 'admin']);
        $this->command->info('Permissions generated successfully!');

        $this->call([
            RolePermissionSeeder::class,
            AdminUserSeeder::class,
            CompanySeeder::class,
            KelompokSeeder::class,
            RekeningSeeder::class,
            NomorBantuSeeder::class,
            // SakepCoaSeeder::class,  // Use new hierarchical seeders instead
            // ChartOfAccountSeeder::class, // Old seeder
            // Uncomment for full test data (takes longer)
            // TestDataSeeder::class,

            // Quick test data for immediate testing
            //QuickTestDataSeeder::class,
        ]);
    }
}
