<?php

namespace App\Filament\Resources\CustomerLoyaltySettings;

use App\Filament\Resources\CustomerLoyaltySettings\Pages\ListCustomerLoyaltySettings;
use App\Filament\Resources\CustomerLoyaltySettings\Schemas\CustomerLoyaltySettingForm;
use App\Filament\Resources\CustomerLoyaltySettings\Tables\CustomerLoyaltySettingsTable;
use App\Models\CustomerLoyaltySetting;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CustomerLoyaltySettingResource extends \App\Filament\Resources\RbacResource
{
    protected static ?string $model = CustomerLoyaltySetting::class;
    protected static ?string $permissionPrefix = 'customer-loyalty-settings';
    protected static ?string $modelLabel = 'إعداد الولاء';
    protected static ?string $pluralModelLabel = 'إعدادات الولاء';
    protected static string|UnitEnum|null $navigationGroup = 'إدارة الزبائن';
    protected static ?string $navigationLabel = 'إعدادات الولاء';
    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    public static function form(Schema $schema): Schema
    {
        return CustomerLoyaltySettingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomerLoyaltySettingsTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomerLoyaltySettings::route('/'),
        ];
    }
}
