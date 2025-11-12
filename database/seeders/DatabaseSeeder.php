<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
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
