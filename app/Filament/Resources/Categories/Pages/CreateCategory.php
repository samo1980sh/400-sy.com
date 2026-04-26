<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use App\Models\Category;
use Filament\Support\Enums\Width;
use Livewire\Attributes\Url;
use Filament\Resources\Pages\CreateRecord;

class CreateCategory extends CreateRecord
{
    protected static string $resource = CategoryResource::class;
    protected static ?string $title = 'إضافة تصنيف';
    protected static ?string $breadcrumb = 'التصنيفات';

    #[Url(as: 'parent')]
    public ?int $parent = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if ($this->parent) {
            $data['parent_id'] = $this->parent;
        }

        return $data;
    }

    public function getMaxContentWidth(): Width | string | null
    {
        return Width::SevenExtraLarge;
    }

    public function getBreadcrumbs(): array
    {
        $breadcrumbs = [
            static::getResource()::getUrl('index') => 'التصنيفات',
        ];

        foreach (Category::breadcrumbTrailFor($this->parent) as $category) {
            $breadcrumbs[static::getResource()::getUrl('index', ['parent' => $category->id])] = $category->title_ar;
        }

        $breadcrumbs[] = $this->getBreadcrumb();

        return $breadcrumbs;
    }
}
