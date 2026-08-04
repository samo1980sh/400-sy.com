<?php

namespace App\Filament\Resources\CustomerQrLogs;

use App\Filament\Resources\CustomerQrLogs\Pages\ListCustomerQrLogs;
use App\Filament\Resources\CustomerQrLogs\Tables\CustomerQrLogsTable;
use App\Models\CustomerQrLog;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CustomerQrLogResource extends \App\Filament\Resources\RbacResource
{
    protected static ?string $model = CustomerQrLog::class;
    protected static ?string $permissionPrefix = 'customer-qr-logs';
    protected static ?string $modelLabel = 'سجل QR';
    protected static ?string $pluralModelLabel = 'سجل QR';
    protected static string|UnitEnum|null $navigationGroup = 'الولاء والنقاط و QR';
    protected static ?string $navigationLabel = 'سجل استخدام QR';
    protected static ?int $navigationSort = 8;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return CustomerQrLogsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomerQrLogs::route('/'),
        ];
    }
}
