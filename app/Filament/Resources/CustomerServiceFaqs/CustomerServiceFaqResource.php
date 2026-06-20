<?php

namespace App\Filament\Resources\CustomerServiceFaqs;

use App\Filament\Resources\CustomerServiceFaqs\Pages\ListCustomerServiceFaqs;
use App\Filament\Resources\CustomerServiceFaqs\Schemas\CustomerServiceFaqForm;
use App\Filament\Resources\CustomerServiceFaqs\Tables\CustomerServiceFaqsTable;
use App\Models\CustomerServiceFaq;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CustomerServiceFaqResource extends \App\Filament\Resources\RbacResource
{
    protected static ?string $model = CustomerServiceFaq::class;
    protected static ?string $permissionPrefix = 'customer-service-faqs';
    protected static ?string $modelLabel = 'سؤال شائع';
    protected static ?string $pluralModelLabel = 'الأسئلة الشائعة';
    protected static string|UnitEnum|null $navigationGroup = 'محتوى الموقع والواجهة';
    protected static ?string $navigationLabel = 'الأسئلة الشائعة';
    protected static ?int $navigationSort = 10;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQuestionMarkCircle;

    public static function form(Schema $schema): Schema
    {
        return CustomerServiceFaqForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomerServiceFaqsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomerServiceFaqs::route('/'),
        ];
    }
}
