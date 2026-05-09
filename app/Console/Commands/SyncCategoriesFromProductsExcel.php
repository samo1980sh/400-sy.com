<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Services\RetailExcelImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncCategoriesFromProductsExcel extends Command
{
    protected $signature = 'categories:sync-from-products-excel
        {path : Path to the products Excel file}
        {--dry-run : Preview the category tree sync without saving}
        {--apply : Apply the category tree sync to the database}';

    protected $description = 'Build or sync the category tree from the full products Excel file.';

    public function handle(RetailExcelImportService $service): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $apply = (bool) $this->option('apply');

        if ($dryRun === $apply) {
            $this->error('Choose exactly one mode: --dry-run or --apply.');

            return self::FAILURE;
        }

        $path = (string) $this->argument('path');

        if (! is_file($path)) {
            $this->error('Products Excel file not found: ' . $path);

            return self::FAILURE;
        }

        $rows = $service->readRows($path);
        $summary = [
            'total_rows_scanned' => count($rows),
            'valid_paths_found' => 0,
            'main_categories_created' => 0,
            'intermediate_categories_created' => 0,
            'final_categories_created' => 0,
            'skipped_rows_due_to_missing_values' => 0,
            'duplicate_paths_ignored' => 0,
        ];

        $paths = $this->extractCategoryPaths($rows, $service, $summary);

        if ($paths === []) {
            $this->warn('No valid category paths were found in the file.');
            $this->printSummary($summary, $dryRun);

            return self::SUCCESS;
        }

        DB::transaction(function () use ($paths, $dryRun, $service, &$summary): void {
            foreach ($paths as $pathData) {
                $main = $this->syncCategoryNode(
                    titleAr: $pathData['main_title_ar'],
                    parentId: null,
                    level: 'main',
                    dryRun: $dryRun,
                    service: $service,
                    summary: $summary,
                );

                $intermediate = $this->syncCategoryNode(
                    titleAr: $pathData['intermediate_title_ar'],
                    parentId: $main?->id,
                    level: 'intermediate',
                    dryRun: $dryRun,
                    service: $service,
                    summary: $summary,
                );

                $leaf = $this->syncCategoryNode(
                    titleAr: $pathData['leaf_title_ar'],
                    parentId: $intermediate?->id,
                    level: 'final',
                    dryRun: $dryRun,
                    service: $service,
                    summary: $summary,
                );

                if ($dryRun) {
                    $this->line(sprintf(
                        '[dry] %s > %s > %s',
                        $pathData['main_title_ar'],
                        $pathData['intermediate_title_ar'],
                        $pathData['leaf_title_ar'],
                    ));
                } elseif ($leaf) {
                    $this->line(sprintf(
                        'synced %s > %s > %s',
                        $pathData['main_title_ar'],
                        $pathData['intermediate_title_ar'],
                        $pathData['leaf_title_ar'],
                    ));
                }
            }
        });

        $this->printSummary($summary, $dryRun);

        return self::SUCCESS;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array{
     *     main_title_ar:string,
     *     intermediate_title_ar:string,
     *     leaf_title_ar:string,
     *     main_key:string,
     *     intermediate_key:string,
     *     leaf_key:string
     * }>
     */
    private function extractCategoryPaths(array $rows, RetailExcelImportService $service, array &$summary): array
    {
        $paths = [];

        foreach ($rows as $row) {
            $main = $service->normalizeCategory($this->rowValue($row, 'المدخل الرئيسي'));
            $intermediate = $service->normalizeCategory($this->rowValue($row, 'المدخل الفرعي'));
            $leaf = $service->normalizeCategory($this->rowValue($row, 'التصنيف'));

            if ($main === '' || $intermediate === '' || $leaf === '') {
                $summary['skipped_rows_due_to_missing_values']++;
                continue;
            }

            $mainKey = $service->normalizeCategoryKey($main);
            $intermediateKey = $service->normalizeCategoryKey($intermediate);
            $leafKey = $service->normalizeCategoryKey($leaf);
            $pathKey = implode('>', [$mainKey, $intermediateKey, $leafKey]);

            if (isset($paths[$pathKey])) {
                $summary['duplicate_paths_ignored']++;
                continue;
            }

            $paths[$pathKey] = [
                'main_title_ar' => $main,
                'intermediate_title_ar' => $intermediate,
                'leaf_title_ar' => $leaf,
                'main_key' => $mainKey,
                'intermediate_key' => $intermediateKey,
                'leaf_key' => $leafKey,
            ];
        }

        $summary['valid_paths_found'] = count($paths);

        return array_values($paths);
    }

    /**
     * @param 'main'|'intermediate'|'final' $level
     */
    private function syncCategoryNode(
        string $titleAr,
        ?int $parentId,
        string $level,
        bool $dryRun,
        RetailExcelImportService $service,
        array &$summary,
    ): ?Category {
        $existing = $this->findExistingCategory($titleAr, $parentId, $service);

        if ($existing) {
            return $existing;
        }

        $payload = [
            'parent_id' => $parentId,
            'title_ar' => $titleAr,
            'title_en' => $titleAr,
            'slug' => $this->generateUniqueSlug($titleAr),
            'show_in_home' => 0,
            'sort_order' => $this->nextSortOrder($parentId),
        ];

        match ($level) {
            'main' => $summary['main_categories_created']++,
            'intermediate' => $summary['intermediate_categories_created']++,
            'final' => $summary['final_categories_created']++,
        };

        if ($dryRun) {
            $category = new Category($payload);
            $category->id = -1;

            return $category;
        }

        return Category::query()->create($payload);
    }

    private function findExistingCategory(string $titleAr, ?int $parentId, RetailExcelImportService $service): ?Category
    {
        /** @var Collection<int, Category> $categories */
        $categories = Category::query()
            ->where(function ($query) use ($parentId): void {
                if ($parentId === null) {
                    $query->whereNull('parent_id')->orWhere('parent_id', 0);

                    return;
                }

                $query->where('parent_id', $parentId);
            })
            ->get(['id', 'parent_id', 'title_ar', 'title_en', 'slug', 'sort_order', 'show_in_home']);

        $targetKey = $service->normalizeCategoryKey($titleAr);

        return $categories->first(function (Category $category) use ($service, $targetKey): bool {
            return $service->normalizeCategoryKey($category->title_ar) === $targetKey;
        });
    }

    private function nextSortOrder(?int $parentId): int
    {
        return ((int) Category::query()
            ->where(function ($query) use ($parentId): void {
                if ($parentId === null) {
                    $query->whereNull('parent_id')->orWhere('parent_id', 0);

                    return;
                }

                $query->where('parent_id', $parentId);
            })
            ->max('sort_order')) + 1;
    }

    private function generateUniqueSlug(string $title): string
    {
        $baseSlug = Str::slug($title);
        $slug = $baseSlug !== '' ? $baseSlug : 'category';
        $candidate = $slug;
        $counter = 2;

        while (Category::query()->where('slug', $candidate)->exists()) {
            $candidate = $slug . '-' . $counter;
            $counter++;
        }

        return $candidate;
    }

    private function rowValue(array $row, string ...$keys): string
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $row)) {
                return trim((string) $row[$key]);
            }
        }

        return '';
    }

    private function printSummary(array $summary, bool $dryRun): void
    {
        $this->newLine();
        $this->info($dryRun ? 'Dry run completed.' : 'Category sync applied.');
        $this->line('Total rows scanned: ' . $summary['total_rows_scanned']);
        $this->line('Valid paths found: ' . $summary['valid_paths_found']);
        $this->line('Main categories created: ' . $summary['main_categories_created']);
        $this->line('Intermediate categories created: ' . $summary['intermediate_categories_created']);
        $this->line('Final categories created: ' . $summary['final_categories_created']);
        $this->line('Skipped rows due to missing values: ' . $summary['skipped_rows_due_to_missing_values']);
        $this->line('Duplicate paths ignored: ' . $summary['duplicate_paths_ignored']);
    }
}
