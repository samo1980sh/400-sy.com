<?php

namespace App\Filament\Resources\ExchangeRateSettings;

use App\Filament\Resources\ExchangeRateSettings\Pages\ListExchangeRateSettings;
use App\Filament\Resources\ExchangeRateSettings\Schemas\ExchangeRateSettingForm;
use App\Filament\Resources\ExchangeRateSettings\Tables\ExchangeRateSettingsTable;
use App\Models\ExchangeRateSetting;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ExchangeRateSettingResource extends \App\Filament\Resources\RbacResource
{
    protected static ?string $model = ExchangeRateSetting::class;
    protected static ?string $permissionPrefix = 'exchange-rate-settings';
    protected static ?string $modelLabel = 'إعداد سعر الصرف';
    protected static ?string $pluralModelLabel = 'إعدادات سعر الصرف';
    protected static string|UnitEnum|null $navigationGroup = 'الإعدادات العامة';
    protected static ?string $navigationLabel = 'سعر الصرف';
    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    public static function form(Schema $schema): Schema
    {
        return ExchangeRateSettingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ExchangeRateSettingsTable::configure($table);
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
            'index' => ListExchangeRateSettings::route('/'),
        ];
    }
}
