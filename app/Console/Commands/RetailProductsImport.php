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
        $this->line('Products imported: ' . ($summary['products_imported'] ?? (($summary['products_created'] ?? 0) + ($summary['products_updated'] ?? 0))));
        $this->line('Products created: ' . $summary['products_created']);
        $this->line('Products updated: ' . $summary['products_updated']);
        $this->line('Products skipped: ' . ($summary['products_skipped'] ?? 0));
        $this->line('New structure colors created: ' . ($summary['new_structure_colors_created'] ?? 0));
        $this->line('Rows with empty structure: ' . ($summary['empty_structure_rows'] ?? 0));
        $this->line('Rows with composite structure: ' . ($summary['composite_structure_rows'] ?? 0));
        $this->line('Variants created: ' . $summary['variants_created']);
        $this->line('Variants updated: ' . $summary['variants_updated']);
        $this->line('Variants skipped: ' . $summary['variants_skipped']);
        $this->line('Skipped missing code: ' . ($summary['variants_skipped_missing_code'] ?? 0));
        $this->line('Skipped unknown product: ' . ($summary['variants_skipped_unknown_product'] ?? 0));
        $this->line('Skipped unknown color: ' . ($summary['variants_skipped_unknown_color'] ?? 0));
        $this->line('Skipped missing size: ' . ($summary['variants_skipped_missing_size'] ?? 0));
        $this->line('Skipped unknown size: ' . ($summary['variants_skipped_unknown_size'] ?? 0));
        $this->printVariantSkipSamples($summary);

        return self::SUCCESS;
    }

    private function printVariantSkipSamples(array $summary): void
    {
        $details = $summary['variant_skip_details'] ?? [];

        $unknownProducts = $details['unknown_product_code'] ?? [];

        if ($unknownProducts !== []) {
            $this->newLine();
            $this->line('Unknown product samples:');

            foreach (array_slice($unknownProducts, 0, 30) as $sample) {
                $code = (string) ($sample['الرمز'] ?? '');

                if ($code === '') {
                    continue;
                }

                $this->line('- ' . $code);
            }
        }

        $unknownSizes = $details['unknown_size'] ?? [];

        if ($unknownSizes !== []) {
            $this->newLine();
            $this->line('Unknown size samples:');

            foreach (array_slice($unknownSizes, 0, 30) as $sample) {
                $raw = (string) ($sample['raw_size'] ?? '');
                $normalized = (string) ($sample['normalized_size'] ?? '');
                $key = (string) ($sample['normalized_size_key'] ?? '');

                $this->line(sprintf(
                    '- raw: %s | normalized: %s | key: %s',
                    $raw !== '' ? $raw : '-',
                    $normalized !== '' ? $normalized : '-',
                    $key !== '' ? $key : '-',
                ));
            }
        }

        $unknownColorsCount = (int) ($summary['variants_skipped_unknown_color'] ?? 0);
        $unknownColors = $details['unknown_product_color'] ?? [];

        if ($unknownColorsCount > 0 && $unknownColors !== []) {
            $this->newLine();
            $this->line('Unknown color samples:');

            foreach (array_slice($unknownColors, 0, 30) as $sample) {
                $code = (string) ($sample['الرمز'] ?? '-');
                $color = (string) ($sample['اللون'] ?? '-');
                $colorCode = (string) ($sample['رمز اللون'] ?? '-');
                $normalizedColor = (string) ($sample['normalized_color'] ?? '-');
                $normalizedColorCode = (string) ($sample['normalized_color_code'] ?? '-');

                $this->line(sprintf(
                    '- code: %s | color: %s | color_code: %s | normalized_color: %s | normalized_color_code: %s',
                    $code !== '' ? $code : '-',
                    $color !== '' ? $color : '-',
                    $colorCode !== '' ? $colorCode : '-',
                    $normalizedColor !== '' ? $normalizedColor : '-',
                    $normalizedColorCode !== '' ? $normalizedColorCode : '-',
                ));
            }
        }
    }
}
