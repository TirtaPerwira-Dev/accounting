<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class GenerateTestData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pdam:generate-test-data {--quick : Generate quick test data only} {--full : Generate full test data (50 records each)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate test data for PDAM accounting system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 PDAM Accounting System - Test Data Generator');
        $this->info('===========================================');

        if ($this->option('quick')) {
            $this->info('⚡ Generating quick test data...');
            Artisan::call('db:seed --class=QuickTestDataSeeder');
            $this->info('✅ Quick test data generated successfully!');
        } elseif ($this->option('full')) {
            $this->info('📊 Generating full test data (50 records each)...');
            $this->warn('⚠️  This will take several minutes and generate a lot of data.');

            if ($this->confirm('Continue?')) {
                Artisan::call('db:seed --class=TestDataSeeder');
                $this->info('✅ Full test data generated successfully!');
            } else {
                $this->info('❌ Operation cancelled.');
                return;
            }
        } else {
            $this->info('Please specify an option:');
            $this->info('  --quick  : Generate quick test data (recommended for development)');
            $this->info('  --full   : Generate full test data (50 records each resource)');
            $this->info('');
            $this->info('Examples:');
            $this->info('  php artisan pdam:generate-test-data --quick');
            $this->info('  php artisan pdam:generate-test-data --full');
        }

        $this->info('');
        $this->info('🎉 Test data generation completed!');
        $this->info('');
        $this->info('📧 Default test credentials:');
        $this->info('   direktur@pdam.test / password');
        $this->info('   akuntan@pdam.test / password');
        $this->info('   kasir@pdam.test / password');
        $this->info('');
        $this->info('🌐 Access your application at: http://localhost/admin');
    }
}
