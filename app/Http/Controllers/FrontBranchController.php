<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\BranchCategory;
use App\Services\FrontHomePageDataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class FrontBranchController extends Controller
{
    public function __construct(protected FrontHomePageDataService $homePageData)
    {
    }

    public function index(Request $request): View
    {
        $locale = app()->getLocale();
        $selectedCategory = trim((string) $request->query('category', ''));
        $shell = $this->homePageData->build();

        $categories = BranchCategory::query()
            ->where('status', 'active')
            ->with(['branches' => function ($query): void {
                $query->where('status', 'active')
                    ->orderBy('sort_order')
                    ->orderBy('name_ar');
            }])
            ->orderBy('sort_order')
            ->orderBy('name_ar')
            ->get()
            ->filter(fn (BranchCategory $category): bool => $category->branches->isNotEmpty())
            ->values();

        $selectedCategoryModel = $selectedCategory !== ''
            ? $categories->firstWhere('slug', $selectedCategory)
            : null;

        $branches = $selectedCategoryModel
            ? $selectedCategoryModel->branches->values()
            : $categories->flatMap(fn (BranchCategory $category) => $category->branches)->values();

        return view('frontend.pages.branches.index', array_merge($shell, [
            'page_title' => $locale === 'ar' ? 'الفروع والصالات' : 'Branches & Showrooms',
            'page_subtitle' => $locale === 'ar' ? 'تصفح فروع وصالات 400 حسب المحافظة.' : 'Browse 400 branches and showrooms by governorate.',
            'breadcrumb_items' => [
                ['label' => __('front.nav.home'), 'url' => route('front.home')],
                ['label' => $locale === 'ar' ? 'الفروع والصالات' : 'Branches & Showrooms', 'url' => null],
            ],
            'branch_categories' => $categories,
            'branches' => $branches,
            'selected_branch_category' => $selectedCategoryModel,
            'selected_branch_category_slug' => $selectedCategoryModel?->slug,
        ]));
    }

    public function show(Branch $branch): View
    {
        abort_unless($branch->status === 'active', 404);

        $locale = app()->getLocale();
        $branch->loadMissing('category');
        $shell = $this->homePageData->build();
        $name = $this->localized($branch->name_ar, $branch->name_en, $locale) ?: ($locale === 'ar' ? 'فرع' : 'Branch');
        $categoryName = $branch->category
            ? $this->localized($branch->category->name_ar, $branch->category->name_en, $locale)
            : null;

        return view('frontend.pages.branches.show', array_merge($shell, [
            'page_title' => $name,
            'page_subtitle' => $categoryName ?: ($locale === 'ar' ? 'الفروع والصالات' : 'Branches & Showrooms'),
            'breadcrumb_items' => [
                ['label' => __('front.nav.home'), 'url' => route('front.home')],
                ['label' => $locale === 'ar' ? 'الفروع والصالات' : 'Branches & Showrooms', 'url' => route('front.branches.index')],
                ['label' => $name, 'url' => null],
            ],
            'branch' => $branch,
            'branch_name' => $name,
            'branch_category_name' => $categoryName,
            'branch_address' => $this->localized($branch->address_ar, $branch->address_en, $locale),
            'branch_description' => $this->localized($branch->description_ar, $branch->description_en, $locale),
            'branch_type_label' => $this->typeLabel((string) $branch->type, $locale),
            'branch_image_url' => $this->imageUrl($branch->main_image),
            'branch_gallery_urls' => collect($branch->gallery_images ?? [])
                ->map(fn ($image) => $this->imageUrl((string) $image))
                ->filter()
                ->values(),
        ]));
    }

    protected function localized(?string $ar, ?string $en, string $locale): string
    {
        $primary = $locale === 'ar' ? $ar : $en;
        $fallback = $locale === 'ar' ? $en : $ar;

        return trim((string) ($primary ?: $fallback ?: ''));
    }

    protected function imageUrl(?string $path): string
    {
        $path = trim((string) $path);

        return $path !== ''
            ? Storage::disk('public')->url($path)
            : asset('images/shop/store/ourstore1.png');
    }

    protected function typeLabel(string $type, string $locale): string
    {
        return match ($type) {
            'hall' => $locale === 'ar' ? 'صالة' : 'Showroom',
            default => $locale === 'ar' ? 'فرع' : 'Branch',
        };
    }
}
