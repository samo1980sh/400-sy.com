<?php

namespace App\Console\Commands;

use App\Services\RetailExcelImportService;
use Illuminate\Console\Command;

class RetailProductsDryRun extends Command
{
    protected $signature = 'retail:products-dry-run
        {--products= : Path to the retail products Excel file}
        {--variants= : Path to the retail variants Excel file}
        {--batch=50 : Number of product codes to test}';

    protected $description = 'Run a dry import check for the retail product Excel files.';

    public function handle(RetailExcelImportService $service): int
    {
        $productsPath = (string) $this->option('products');
        $variantsPath = (string) $this->option('variants');
        $batchSize = (int) $this->option('batch');

        if ($productsPath === '' || $variantsPath === '') {
            $this->error('Both --products and --variants are required.');

            return self::FAILURE;
        }

        $summary = $service->preview(
            $service->readRows($productsPath),
            $service->readRows($variantsPath),
            $batchSize,
        );

        $this->line('# Retail Dry Run Report');
        $this->newLine();
        $this->line('Batch size: ' . $batchSize);
        $this->line('Accepted products: ' . $summary['accepted_products']);
        $this->line('Rejected products: ' . $summary['rejected_products']);
        $this->line('Accepted variants: ' . $summary['accepted_variants']);
        $this->line('Rejected variants: ' . $summary['rejected_variants']);

        return self::SUCCESS;
    }
}
