<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\KodeProyek;

class ShowKodeProyek extends Command
{
    protected $signature = 'show:kode-proyek';
    protected $description = 'Show available kode proyek';

    public function handle()
    {
        $this->info('Available Kode Proyek:');
        
        KodeProyek::take(5)->get()->each(function($item) {
            $this->line($item->kode . ' - ' . $item->name);
        });
        
        return 0;
    }
}