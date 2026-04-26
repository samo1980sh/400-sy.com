<?php

namespace App\Filament\Resources\ProductVariants\Pages;

use App\Filament\Resources\ProductVariants\ProductVariantResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProductVariant extends CreateRecord
{
    protected static string $resource = ProductVariantResource::class;
    protected static ?string $title = 'إضافة متغير';
    protected static ?string $breadcrumb = 'المتغيرات';
}
