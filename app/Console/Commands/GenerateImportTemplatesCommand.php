<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Exports\JurnalPembelianTemplateExport;
use App\Exports\JurnalRekeningAirTemplateExport;
use App\Exports\JurnalPenerimaanKasTemplateExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;

class GenerateImportTemplatesCommand extends Command
{
    protected $signature = 'accounting:generate-templates';
    
    protected $description = 'Generate Excel templates for import functionality';

    public function handle()
    {
        $this->info('Generating Excel templates for import functionality...');

        try {
            // Create templates directory if not exists
            if (!Storage::disk('public')->exists('templates')) {
                Storage::disk('public')->makeDirectory('templates');
                $this->info('Created templates directory');
            }

            // Generate Jurnal Pembelian Template
            $this->info('Generating Jurnal Pembelian template...');
            Excel::store(
                new JurnalPembelianTemplateExport(), 
                'templates/template-jurnal-pembelian.xlsx',
                'public'
            );
            $this->line('✓ template-jurnal-pembelian.xlsx created');

            // Generate Jurnal Rekening Air Template  
            $this->info('Generating Jurnal Rekening Air template...');
            Excel::store(
                new JurnalRekeningAirTemplateExport(), 
                'templates/template-jurnal-rekening-air.xlsx',
                'public'
            );
            $this->line('✓ template-jurnal-rekening-air.xlsx created');

            // Generate Jurnal Penerimaan Kas Template
            $this->info('Generating Jurnal Penerimaan Kas template...');
            Excel::store(
                new JurnalPenerimaanKasTemplateExport(), 
                'templates/template-jurnal-penerimaan-kas.xlsx',
                'public'
            );
            $this->line('✓ template-jurnal-penerimaan-kas.xlsx created');

            $this->newLine();
            $this->info('All templates generated successfully!');
            $this->line('Templates location: storage/app/public/templates/');
            $this->line('Access via: /storage/templates/[filename]');
            
            $this->newLine();
            $this->comment('Available templates:');
            $this->line('1. template-jurnal-pembelian.xlsx');
            $this->line('2. template-jurnal-rekening-air.xlsx'); 
            $this->line('3. template-jurnal-penerimaan-kas.xlsx');

        } catch (\Exception $e) {
            $this->error('Error generating templates: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}