<?php

namespace App\Filament\Resources\PointVoucherRedemptions;

use App\Filament\Resources\PointVoucherRedemptions\Pages\ListPointVoucherRedemptions;
use App\Filament\Resources\PointVoucherRedemptions\Schemas\PointVoucherRedemptionForm;
use App\Filament\Resources\PointVoucherRedemptions\Tables\PointVoucherRedemptionsTable;
use App\Models\PointVoucherRedemption;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PointVoucherRedemptionResource extends \App\Filament\Resources\RbacResource
{
    protected static ?string $model = PointVoucherRedemption::class;
    protected static ?string $permissionPrefix = 'point-voucher-redemptions';
    protected static ?string $modelLabel = 'صرف قسيمة';
    protected static ?string $pluralModelLabel = 'صرف قسائم النقاط';
    protected static string|UnitEnum|null $navigationGroup = 'إدارة الزبائن';
    protected static ?string $navigationLabel = 'سجل قسائم النقاط';
    protected static ?int $navigationSort = 7;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    public static function form(Schema $schema): Schema
    {
        return PointVoucherRedemptionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PointVoucherRedemptionsTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
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
            'index' => ListPointVoucherRedemptions::route('/'),
        ];
    }
}
