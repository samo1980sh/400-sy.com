<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Services\ProductImageCatalogService;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\View as SchemaView;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class ProductImagesRelationManager extends RelationManager
{
    protected static string $relationship = 'productColors';
    protected static ?string $title = 'ألوان المنتج وصورها';

    public function content(Schema $schema): Schema
    {
        return $schema->components([
                SchemaView::make('filament.products.relation-managers.product-images')
                ->viewData(fn (): array => [
                    'ownerRecord' => $this->getOwnerRecord(),
                    'colors' => app(ProductImageCatalogService::class)->availableColors($this->getOwnerRecord()),
                ]),
        ]);
    }
}
