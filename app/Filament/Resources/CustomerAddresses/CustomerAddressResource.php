<?php

namespace App\Filament\Resources\CustomerAddresses;

use App\Filament\Resources\CustomerAddresses\Pages\ListCustomerAddresses;
use App\Filament\Resources\CustomerAddresses\Schemas\CustomerAddressForm;
use App\Filament\Resources\CustomerAddresses\Tables\CustomerAddressesTable;
use App\Models\CustomerAddress;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class CustomerAddressResource extends \App\Filament\Resources\RbacResource
{
    protected static ?string $model = CustomerAddress::class;
    protected static ?string $permissionPrefix = 'customer-addresses';
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $modelLabel = 'عنوان زبون';
    protected static ?string $pluralModelLabel = 'عناوين الزبائن';
    protected static string|UnitEnum|null $navigationGroup = 'إدارة الزبائن';
    protected static ?string $navigationLabel = 'عناوين الزبائن';
    protected static ?int $navigationSort = 3;
    protected static string|BackedEnum|null $navigationIcon = null;

    public static function form(Schema $schema): Schema
    {
        return CustomerAddressForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomerAddressesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomerAddresses::route('/'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::$shouldRegisterNavigation && parent::shouldRegisterNavigation();
    }
}
