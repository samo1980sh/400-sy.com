<?php

namespace App\Filament\Resources\ContactInfoSettings;

use App\Filament\Resources\ContactInfoSettings\Pages\ListContactInfoSettings;
use App\Filament\Resources\ContactInfoSettings\Schemas\ContactInfoSettingForm;
use App\Filament\Resources\ContactInfoSettings\Tables\ContactInfoSettingsTable;
use App\Models\ContactInfoSetting;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ContactInfoSettingResource extends \App\Filament\Resources\RbacResource
{
    protected static ?string $model = ContactInfoSetting::class;
    protected static ?string $permissionPrefix = 'contact-info-settings';
    protected static ?string $modelLabel = 'معلومات الاتصال العامة';
    protected static ?string $pluralModelLabel = 'معلومات الاتصال العامة';
    protected static string|UnitEnum|null $navigationGroup = 'محتوى الموقع والواجهة';
    protected static ?string $navigationLabel = 'معلومات اتصال عامة';
    protected static ?int $navigationSort = 7;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhone;

    public static function form(Schema $schema): Schema
    {
        return ContactInfoSettingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ContactInfoSettingsTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return true;
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
            'index' => ListContactInfoSettings::route('/'),
        ];
    }
}
