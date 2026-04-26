<?php

namespace App\Filament\Resources\BranchCategories\Pages;

use App\Filament\Resources\BranchCategories\BranchCategoryResource;
use Filament\Resources\Pages\ListRecords;

class ListBranchCategories extends ListRecords
{
    protected static string $resource = BranchCategoryResource::class;
}
