<?php

namespace App\Filament\Resources\CustomerServiceSettings;

use App\Filament\Resources\CustomerServiceSettings\Pages\ListCustomerServiceSettings;
use App\Filament\Resources\CustomerServiceSettings\Schemas\CustomerServiceSettingForm;
use App\Filament\Resources\CustomerServiceSettings\Tables\CustomerServiceSettingsTable;
use App\Models\CustomerServiceSetting;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CustomerServiceSettingResource extends \App\Filament\Resources\RbacResource
{
    protected static ?string $model = CustomerServiceSetting::class;
    protected static ?string $permissionPrefix = 'customer-service-settings';
    protected static ?string $modelLabel = 'صفحة إعداد';
    protected static ?string $pluralModelLabel = 'إعدادات خدمة الزبائن';
    protected static string|UnitEnum|null $navigationGroup = 'محتوى الموقع والواجهة';
    protected static ?string $navigationLabel = 'إعدادات خدمة الزبائن';
    protected static ?int $navigationSort = 9;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    public static function form(Schema $schema): Schema
    {
        return CustomerServiceSettingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomerServiceSettingsTable::configure($table);
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
            'index' => ListCustomerServiceSettings::route('/'),
        ];
    }
}
