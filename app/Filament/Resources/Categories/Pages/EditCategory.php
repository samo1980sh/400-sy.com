<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use App\Models\Category;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;

class EditCategory extends EditRecord
{
    protected static string $resource = CategoryResource::class;
    protected static ?string $title = 'تعديل تصنيف';
    protected static ?string $breadcrumb = 'التصنيفات';

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
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

        foreach ($this->record->breadcrumbTrail() as $category) {
            $breadcrumbs[static::getResource()::getUrl('index', ['parent' => $category->id])] = $category->title_ar;
        }

        $breadcrumbs[] = $this->getBreadcrumb();

        return $breadcrumbs;
    }
}
