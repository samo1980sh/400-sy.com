<?php

namespace App\Console\Commands;

use App\Services\RetailExcelImportService;
use Illuminate\Console\Command;

class RetailProductsImport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'retail:products-import
        {--products= : Path to the retail products Excel file}
        {--variants= : Path to the retail variants Excel file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import the full retail product Excel files into the database.';

    /**
     * Execute the console command.
     */
    public function handle(RetailExcelImportService $service): int
    {
        $productsPath = (string) $this->option('products');
        $variantsPath = (string) $this->option('variants');

        if ($productsPath === '' || $variantsPath === '') {
            $this->error('Both --products and --variants are required.');

            return self::FAILURE;
        }

        $summary = $service->importRetailFiles($productsPath, $variantsPath);

        $this->info('Retail import completed.');
        $this->line('Products created: ' . $summary['products_created']);
        $this->line('Products updated: ' . $summary['products_updated']);
        $this->line('Variants created: ' . $summary['variants_created']);
        $this->line('Variants updated: ' . $summary['variants_updated']);
        $this->line('Variants skipped: ' . $summary['variants_skipped']);

        return self::SUCCESS;
    }
}
