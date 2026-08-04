<?php

namespace App\Filament\Resources\CustomerLoyaltyTransactions;

use App\Filament\Resources\CustomerLoyaltyTransactions\Pages\ListCustomerLoyaltyTransactions;
use App\Filament\Resources\CustomerLoyaltyTransactions\Schemas\CustomerLoyaltyTransactionForm;
use App\Filament\Resources\CustomerLoyaltyTransactions\Tables\CustomerLoyaltyTransactionsTable;
use App\Models\CustomerLoyaltyTransaction;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CustomerLoyaltyTransactionResource extends \App\Filament\Resources\RbacResource
{
    protected static ?string $model = CustomerLoyaltyTransaction::class;
    protected static ?string $permissionPrefix = 'customer-loyalty-transactions';
    protected static ?string $modelLabel = 'حركة نقطة';
    protected static ?string $pluralModelLabel = 'حركات النقاط';
    protected static string|UnitEnum|null $navigationGroup = 'الولاء والنقاط و QR';
    protected static ?string $navigationLabel = 'سجل النقاط';
    protected static ?int $navigationSort = 4;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowTrendingUp;

    public static function form(Schema $schema): Schema
    {
        return CustomerLoyaltyTransactionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomerLoyaltyTransactionsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomerLoyaltyTransactions::route('/'),
        ];
    }
}
