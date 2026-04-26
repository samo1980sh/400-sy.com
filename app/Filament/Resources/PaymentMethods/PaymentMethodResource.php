<?php

namespace App\Filament\Resources\PaymentMethods;

use App\Filament\Resources\PaymentMethods\Pages\ListPaymentMethods;
use App\Filament\Resources\PaymentMethods\Schemas\PaymentMethodForm;
use App\Filament\Resources\PaymentMethods\Tables\PaymentMethodsTable;
use App\Models\PaymentMethod;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PaymentMethodResource extends \App\Filament\Resources\RbacResource
{
    protected static ?string $model = PaymentMethod::class;
    protected static ?string $permissionPrefix = 'payment-methods';
    protected static ?string $modelLabel = 'طريقة دفع';
    protected static ?string $pluralModelLabel = 'طرق الدفع';
    protected static string|UnitEnum|null $navigationGroup = 'إدارة الطلبات';
    protected static ?string $navigationLabel = 'طرق الدفع';
    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    public static function form(Schema $schema): Schema
    {
        return PaymentMethodForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PaymentMethodsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPaymentMethods::route('/'),
        ];
    }
}
