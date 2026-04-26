<?php

namespace App\Filament\Resources\PointsVouchers;

use App\Filament\Resources\PointsVouchers\Pages\ListPointsVouchers;
use App\Filament\Resources\PointsVouchers\Schemas\PointsVoucherForm;
use App\Filament\Resources\PointsVouchers\Tables\PointsVouchersTable;
use App\Models\PointsVoucher;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PointsVoucherResource extends \App\Filament\Resources\RbacResource
{
    protected static ?string $model = PointsVoucher::class;
    protected static ?string $permissionPrefix = 'points-vouchers';
    protected static ?string $modelLabel = 'قسيمة نقاط';
    protected static ?string $pluralModelLabel = 'قسائم النقاط';
    protected static string|UnitEnum|null $navigationGroup = 'إدارة الزبائن';
    protected static ?string $navigationLabel = 'قسائم النقاط';
    protected static ?int $navigationSort = 6;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    public static function form(Schema $schema): Schema
    {
        return PointsVoucherForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PointsVouchersTable::configure($table);
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
            'index' => ListPointsVouchers::route('/'),
        ];
    }
}
