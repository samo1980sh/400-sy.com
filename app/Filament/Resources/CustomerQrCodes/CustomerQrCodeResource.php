<?php

namespace App\Filament\Resources\CustomerQrCodes;

use App\Filament\Resources\CustomerQrCodes\Pages\ListCustomerQrCodes;
use App\Filament\Resources\CustomerQrCodes\Schemas\CustomerQrCodeForm;
use App\Filament\Resources\CustomerQrCodes\Tables\CustomerQrCodesTable;
use App\Models\CustomerQrCode;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CustomerQrCodeResource extends \App\Filament\Resources\RbacResource
{
    protected static ?string $model = CustomerQrCode::class;
    protected static ?string $permissionPrefix = 'customer-qr-codes';
    protected static ?string $modelLabel = 'QR للزبون';
    protected static ?string $pluralModelLabel = 'رموز QR';
    protected static string|UnitEnum|null $navigationGroup = 'إدارة الزبائن';
    protected static ?string $navigationLabel = 'QR Code';
    protected static ?int $navigationSort = 10;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQrCode;

    public static function form(Schema $schema): Schema
    {
        return CustomerQrCodeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomerQrCodesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomerQrCodes::route('/'),
        ];
    }
}
