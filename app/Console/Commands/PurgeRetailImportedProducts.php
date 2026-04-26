<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PurgeRetailImportedProducts extends Command
{
    protected $signature = 'retail:purge-imported-products {--force : Skip the confirmation prompt}';

    protected $description = 'Remove imported retail product data and keep reference data intact.';

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('This will delete imported products, variants, and import logs. Continue?')) {
            return self::SUCCESS;
        }

        $countsBefore = $this->counts();

        DB::transaction(function (): void {
            foreach ([
                'product_variants',
                'products',
                'import_rows',
                'import_batches',
            ] as $table) {
                DB::table($table)->delete();
            }
        });

        $countsAfter = $this->counts();

        $this->line('Purged imported retail product data.');
        $this->table(
            ['Table', 'Before', 'After'],
            [
                ['products', $countsBefore['products'], $countsAfter['products']],
                ['product_variants', $countsBefore['product_variants'], $countsAfter['product_variants']],
                ['import_batches', $countsBefore['import_batches'], $countsAfter['import_batches']],
                ['import_rows', $countsBefore['import_rows'], $countsAfter['import_rows']],
            ],
        );

        return self::SUCCESS;
    }

    /**
     * @return array<string, int>
     */
    private function counts(): array
    {
        return [
            'products' => DB::table('products')->count(),
            'product_variants' => DB::table('product_variants')->count(),
            'import_batches' => DB::table('import_batches')->count(),
            'import_rows' => DB::table('import_rows')->count(),
        ];
    }
}
