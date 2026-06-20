<?php

namespace App\Filament\Resources\CouponSettings;

use App\Filament\Resources\CouponSettings\Pages\ListCouponSettings;
use App\Filament\Resources\CouponSettings\Schemas\CouponSettingForm;
use App\Filament\Resources\CouponSettings\Tables\CouponSettingsTable;
use App\Models\CouponSetting;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CouponSettingResource extends \App\Filament\Resources\RbacResource
{
    protected static ?string $model = CouponSetting::class;
    protected static ?string $permissionPrefix = 'coupon-settings';
    protected static ?string $modelLabel = 'إعداد كوبون';
    protected static ?string $pluralModelLabel = 'إعدادات الكوبونات';
    protected static string|UnitEnum|null $navigationGroup = 'الهدايا والكوبونات';
    protected static ?string $navigationLabel = 'إعدادات الكوبونات';
    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    public static function form(Schema $schema): Schema
    {
        return CouponSettingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CouponSettingsTable::configure($table);
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
            'index' => ListCouponSettings::route('/'),
        ];
    }
}
