<?php

namespace App\Console\Commands;

use App\Models\Category;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ApplyCategoryHierarchy extends Command
{
    protected $signature = 'categories:apply-hierarchy {--dry-run : Preview the category hierarchy changes without saving them}';

    protected $description = 'Apply the confirmed category hierarchy to the database.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $summary = [
            'created' => 0,
            'updated' => 0,
            'moved' => 0,
            'missing' => 0,
        ];

        $plan = $this->plan();

        DB::transaction(function () use ($plan, $dryRun, &$summary): void {
            foreach ($plan as $rootOrder => $rootPlan) {
                $root = $this->syncNode(
                    titleAr: $rootPlan['title_ar'],
                    titleEn: $rootPlan['title_en'],
                    parent: null,
                    sortOrder: $rootOrder,
                    dryRun: $dryRun,
                    summary: $summary,
                );

                foreach ($rootPlan['children'] as $childOrder => $childPlan) {
                    $child = $this->syncNode(
                        titleAr: $childPlan['title_ar'],
                        titleEn: $childPlan['title_en'],
                        parent: $root,
                        sortOrder: $childOrder,
                        dryRun: $dryRun,
                        summary: $summary,
                    );

                    if (! empty($childPlan['move_from_current_parent'])) {
                        $this->moveChildrenByTitle(
                            sourceParent: $root,
                            targetParent: $child,
                            titles: $childPlan['move_from_current_parent'],
                            dryRun: $dryRun,
                            summary: $summary,
                        );
                    }

                    if (! empty($childPlan['children'])) {
                        foreach ($childPlan['children'] as $grandOrder => $grandPlan) {
                            $grand = $this->syncNode(
                                titleAr: $grandPlan['title_ar'],
                                titleEn: $grandPlan['title_en'],
                                parent: $child,
                                sortOrder: $grandOrder,
                                dryRun: $dryRun,
                                summary: $summary,
                            );

                            if (! empty($grandPlan['move_from_current_parent'])) {
                                $this->moveChildrenByTitle(
                                    sourceParent: $child,
                                    targetParent: $grand,
                                    titles: $grandPlan['move_from_current_parent'],
                                    dryRun: $dryRun,
                                    summary: $summary,
                                );
                            }
                        }
                    }
                }
            }

            $this->applyFinalCorrections($dryRun, $summary);
        });

        $this->line($dryRun ? 'Dry run completed.' : 'Hierarchy applied.');
        $this->line('Created: ' . $summary['created']);
        $this->line('Updated: ' . $summary['updated']);
        $this->line('Moved: ' . $summary['moved']);
        $this->line('Missing: ' . $summary['missing']);

        return self::SUCCESS;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function plan(): array
    {
        return [
            1 => [
                'title_ar' => 'إكسسوارات',
                'title_en' => 'Accessories',
                'children' => [
                    1 => [
                        'title_ar' => 'حذاء',
                        'title_en' => 'Shoes',
                        'move_from_current_parent' => [
                            'حذاء رسمي',
                            'حذاء سبور',
                            'حذاء شتوي',
                        ],
                    ],
                    2 => [
                        'title_ar' => 'إكسسوارات',
                        'title_en' => 'Accessories',
                        'move_from_current_parent' => [
                            'حمالات',
                            'كرافه',
                            'حقيبه',
                            'كافلينج و بروش',
                            'ببيون و فولار',
                            'لحشه',
                            'حزام جلد طبيعي',
                        ],
                    ],
                    3 => [
                        'title_ar' => 'ملابس داخلية وجوارب',
                        'title_en' => 'Underwear & Socks',
                        'move_from_current_parent' => [
                            'جوارب',
                            'جوارب شتوية',
                            'ملابس داخلية',
                        ],
                    ],
                    4 => [
                        'title_ar' => 'أكياس وكفرات',
                        'title_en' => 'Bags & Covers',
                        'move_from_current_parent' => [
                            'أكياس',
                            'كفرات',
                            'علبه',
                        ],
                    ],
                ],
            ],
            2 => [
                'title_ar' => 'رجالي',
                'title_en' => 'Men',
                'children' => [
                    1 => [
                        'title_ar' => 'طقم وبليزر',
                        'title_en' => 'Suit & Blazer',
                        'move_from_current_parent' => [
                            'توكسيدو',
                            'بليزر',
                            'طقم رسمي',
                            'طقم كاجوال',
                            'طقم سواريه',
                            'طقم مقاسات خاصة',
                            'صدريه',
                        ],
                    ],
                    2 => [
                        'title_ar' => 'بنطال',
                        'title_en' => 'Trousers',
                        'move_from_current_parent' => [
                            'بنطال جينز',
                            'بنطال سبور',
                            'بنطال رسمي',
                            'بنطال مخمل',
                            'بنطال بيجامه',
                        ],
                    ],
                    3 => [
                        'title_ar' => 'كنزة وبيجامة',
                        'title_en' => 'Knitwear & Pyjamas',
                        'move_from_current_parent' => [
                            'بيجامه',
                            'كنزه فليس',
                            'كنزه تريكو سبور',
                            'كنزه تريكو ساده',
                            'كنزه قطن',
                        ],
                    ],
                    4 => [
                        'title_ar' => 'قميص',
                        'title_en' => 'Shirts',
                        'move_from_current_parent' => [
                            'قميص سبور',
                            'قميص رسمي',
                            'قميص ساده',
                            'قميص شتوي',
                        ],
                    ],
                    5 => [
                        'title_ar' => 'جاكيت ومانطو',
                        'title_en' => 'Jackets & Coats',
                        'move_from_current_parent' => [
                            'ستره جلد',
                            'مانطو جوخ',
                            'جاكيت سبور',
                        ],
                    ],
                ],
            ],
            3 => [
                'title_ar' => 'محير',
                'title_en' => 'Mixed',
                'children' => [
                    1 => [
                        'title_ar' => 'ملابس داخلية وجوارب',
                        'title_en' => 'Underwear & Socks',
                        'move_from_current_parent' => [
                            'ملابس داخلية',
                        ],
                    ],
                    2 => [
                        'title_ar' => 'طقم وجاكيت',
                        'title_en' => 'Suit & Jacket',
                        'move_from_current_parent' => [
                            'طقم رسمي',
                            'بليزر',
                            'جاكيت سبور',
                        ],
                    ],
                    3 => [
                        'title_ar' => 'كنزة وبيجامة',
                        'title_en' => 'Knitwear & Pyjamas',
                        'move_from_current_parent' => [
                            'كنزه فليس',
                            'كنزه تريكو',
                            'بيجامه شتوي',
                        ],
                    ],
                    4 => [
                        'title_ar' => 'بنطال',
                        'title_en' => 'Trousers',
                        'move_from_current_parent' => [
                            'بنطال جينز',
                            'بنطال سبور',
                            'بنطال شتوي',
                        ],
                    ],
                    5 => [
                        'title_ar' => 'قميص',
                        'title_en' => 'Shirts',
                        'move_from_current_parent' => [
                            'قميص سبور',
                            'قميص شتوي',
                        ],
                    ],
                ],
            ],
            4 => [
                'title_ar' => 'ولادي',
                'title_en' => 'Boys',
                'children' => [
                    1 => [
                        'title_ar' => 'بنطال',
                        'title_en' => 'Trousers',
                        'move_from_current_parent' => [
                            'بنطال شتوي',
                            'بنطال جينز',
                            'جينز',
                            'بنطال سبور',
                        ],
                    ],
                    2 => [
                        'title_ar' => 'طقم وجاكيت',
                        'title_en' => 'Suit & Jacket',
                        'move_from_current_parent' => [
                            'طقم رسمي',
                            'بليزر',
                            'جاكيت سبور',
                        ],
                    ],
                    3 => [
                        'title_ar' => 'كنزة وبيجامة',
                        'title_en' => 'Knitwear & Pyjamas',
                        'move_from_current_parent' => [
                            'بيجامه',
                            'كنزه فليس',
                            'كنزه تريكو',
                            'بيجامه شتوي',
                        ],
                    ],
                    4 => [
                        'title_ar' => 'قميص',
                        'title_en' => 'Shirts',
                        'move_from_current_parent' => [
                            'قميص سبور',
                            'قميص شتوي',
                        ],
                    ],
                    5 => [
                        'title_ar' => 'ملابس داخلية وجوارب',
                        'title_en' => 'Underwear & Socks',
                        'move_from_current_parent' => [
                            'ملابس داخلية',
                            'جوارب',
                        ],
                    ],
                ],
            ],
            5 => [
                'title_ar' => 'يونيفورم',
                'title_en' => 'Uniform',
                'children' => [
                    1 => [
                        'title_ar' => 'قميص يونيفورم',
                        'title_en' => 'Uniform Shirt',
                    ],
                    2 => [
                        'title_ar' => 'مدارس حكومية',
                        'title_en' => 'Public Schools',
                        'children' => [
                            1 => [
                                'title_ar' => 'ولادي',
                                'title_en' => 'Boys',
                            ],
                            2 => [
                                'title_ar' => 'بناتي',
                                'title_en' => 'Girls',
                            ],
                        ],
                    ],
                    3 => [
                        'title_ar' => 'مريول',
                        'title_en' => 'Apron',
                    ],
                ],
            ],
            6 => [
                'title_ar' => 'بطاقة هدية',
                'title_en' => 'Gift Card',
                'children' => [
                    1 => [
                        'title_ar' => 'قسائم مدفوعة',
                        'title_en' => 'Paid Vouchers',
                        'children' => [
                            1 => [
                                'title_ar' => 'فئات',
                                'title_en' => 'Categories',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function syncNode(
        string $titleAr,
        string $titleEn,
        ?Category $parent,
        ?int $sortOrder,
        bool $dryRun,
        array &$summary,
    ): Category {
        $category = Category::query()
            ->where('title_ar', $titleAr)
            ->where('parent_id', $parent?->getKey())
            ->first();

        if (! $category) {
            $category = new Category();
            $category->parent_id = $parent?->getKey();
            $category->title_ar = $titleAr;
            $category->title_en = $titleEn;
            $category->sort_order = $sortOrder;
            $category->show_in_home = $category->show_in_home ?? false;

            if (! $dryRun) {
                $category->save();
            }

            $summary['created']++;
            $this->line(($dryRun ? '[dry] create ' : 'create ') . $this->formatNode($titleAr, $parent));

            return $category;
        }

        $changed = false;

        if (blank($category->title_en) && filled($titleEn)) {
            $category->title_en = $titleEn;
            $changed = true;
        }

        if ($sortOrder !== null && (int) $category->sort_order !== $sortOrder) {
            $category->sort_order = $sortOrder;
            $changed = true;
        }

        if ((int) ($category->parent_id ?? 0) !== (int) ($parent?->getKey() ?? 0)) {
            $category->parent_id = $parent?->getKey();
            $changed = true;
        }

        if ($changed) {
            if (! $dryRun) {
                $category->save();
            }

            $summary['updated']++;
            $this->line(($dryRun ? '[dry] update ' : 'update ') . $this->formatNode($titleAr, $parent));
        }

        return $category;
    }

    /**
     * @param array<int, string> $titles
     */
    private function moveChildrenByTitle(
        Category $sourceParent,
        Category $targetParent,
        array $titles,
        bool $dryRun,
        array &$summary,
    ): void {
        foreach (array_values($titles) as $index => $title) {
            $child = Category::query()
                ->where('parent_id', $sourceParent->getKey())
                ->where('title_ar', $title)
                ->first();

            if (! $child) {
                $summary['missing']++;
                $this->warn('missing ' . $title . ' under ' . $sourceParent->title_ar . ' -> creating under ' . $targetParent->title_ar);

                $this->syncNode(
                    titleAr: $title,
                    titleEn: $this->fallbackEnglishTitle($title),
                    parent: $targetParent,
                    sortOrder: $index + 1,
                    dryRun: $dryRun,
                    summary: $summary,
                );

                continue;
            }

            $child->parent_id = $targetParent->getKey();
            $child->sort_order = $index + 1;

            if (! $dryRun) {
                $child->save();
            }

            $summary['moved']++;
            $this->line(($dryRun ? '[dry] move ' : 'move ') . $title . ' => ' . $targetParent->title_ar);
        }
    }

    private function formatNode(string $titleAr, ?Category $parent): string
    {
        if ($parent === null) {
            return $titleAr . ' [root]';
        }

        return $titleAr . ' [' . $parent->title_ar . ']';
    }

    private function fallbackEnglishTitle(string $titleAr): string
    {
        return match ($titleAr) {
            'حمالات' => 'Suspenders',
            'حقيبه' => 'Bag',
            'كافلينج و بروش' => 'Cufflinks & Brooch',
            'ببيون و فولار' => 'Bow Tie & Scarf',
            'لحشه' => 'Pin',
            'حزام جلد طبيعي' => 'Natural Leather Belt',
            'أكياس' => 'Bags',
            'كفرات' => 'Covers',
            'علبه' => 'Box',
            'طقم سواريه' => 'Evening Suit',
            'صدريه' => 'Vest',
            'بيجامه' => 'Pyjamas',
            'ملابس داخلية' => 'Underwear',
            'جوارب' => 'Socks',
            'قميص يونيفورم' => 'Uniform Shirt',
            'مدارس حكومية' => 'Public Schools',
            'مريول' => 'Apron',
            'فئات' => 'Categories',
            default => $titleAr,
        };
    }

    private function applyFinalCorrections(bool $dryRun, array &$summary): void
    {
        $jins = Category::query()
            ->where('parent_id', 3)
            ->where('title_ar', 'جينز')
            ->first();

        if ($jins && (int) $jins->parent_id !== 151) {
            $jins->parent_id = 151;
            $jins->sort_order = 4;

            if (! $dryRun) {
                $jins->save();
            }

            $summary['updated']++;
            $this->line(($dryRun ? '[dry] update ' : 'update ') . 'جينز [بنطال]');
        }

        $bibyon = Category::query()
            ->where('parent_id', 140)
            ->where('title_ar', 'ببيون و فولار')
            ->first();

        if ($bibyon && $bibyon->title_ar !== 'ببيون وفولار') {
            $bibyon->title_ar = 'ببيون وفولار';
            $bibyon->slug = null;

            if (! $dryRun) {
                $bibyon->save();
            }

            $summary['updated']++;
            $this->line(($dryRun ? '[dry] update ' : 'update ') . 'ببيون وفولار [إكسسوارات]');
        }

        $uniformShirt = Category::query()->find(159);
        if ($uniformShirt) {
            $this->syncNode(
                titleAr: 'قميص رجالي',
                titleEn: 'Men Shirt',
                parent: $uniformShirt,
                sortOrder: 1,
                dryRun: $dryRun,
                summary: $summary,
            );
        }

        $apron = Category::query()->find(163);
        if ($apron) {
            $this->syncNode(
                titleAr: 'مريول طبي',
                titleEn: 'Medical Apron',
                parent: $apron,
                sortOrder: 1,
                dryRun: $dryRun,
                summary: $summary,
            );
        }
    }
}
