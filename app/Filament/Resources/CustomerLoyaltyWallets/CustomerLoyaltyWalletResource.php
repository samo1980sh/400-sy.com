<?php

namespace App\Filament\Resources\CustomerLoyaltyWallets;

use App\Filament\Resources\CustomerLoyaltyWallets\Pages\ListCustomerLoyaltyWallets;
use App\Filament\Resources\CustomerLoyaltyWallets\Schemas\CustomerLoyaltyWalletForm;
use App\Filament\Resources\CustomerLoyaltyWallets\Tables\CustomerLoyaltyWalletsTable;
use App\Models\CustomerLoyaltyWallet;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CustomerLoyaltyWalletResource extends \App\Filament\Resources\RbacResource
{
    protected static ?string $model = CustomerLoyaltyWallet::class;
    protected static ?string $permissionPrefix = 'customer-loyalty-wallets';
    protected static ?string $modelLabel = 'محفظة نقاط';
    protected static ?string $pluralModelLabel = 'محافظ النقاط';
    protected static string|UnitEnum|null $navigationGroup = 'الولاء والنقاط و QR';
    protected static ?string $navigationLabel = 'محافظ الولاء';
    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    public static function form(Schema $schema): Schema
    {
        return CustomerLoyaltyWalletForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomerLoyaltyWalletsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomerLoyaltyWallets::route('/'),
        ];
    }
}
