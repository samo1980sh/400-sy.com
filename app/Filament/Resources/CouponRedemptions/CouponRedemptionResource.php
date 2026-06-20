<?php

namespace App\Filament\Resources\CouponRedemptions;

use App\Filament\Resources\CouponRedemptions\Pages\ListCouponRedemptions;
use App\Filament\Resources\CouponRedemptions\Tables\CouponRedemptionsTable;
use App\Models\CouponRedemption;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CouponRedemptionResource extends \App\Filament\Resources\RbacResource
{
    protected static ?string $model = CouponRedemption::class;
    protected static ?string $permissionPrefix = 'coupon-redemptions';
    protected static ?string $modelLabel = 'سجل كوبون';
    protected static ?string $pluralModelLabel = 'سجل الكوبونات';
    protected static string|UnitEnum|null $navigationGroup = 'الهدايا والكوبونات';
    protected static ?string $navigationLabel = 'سجل الكوبونات';
    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    public static function table(Table $table): Table
    {
        return CouponRedemptionsTable::configure($table);
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
            'index' => ListCouponRedemptions::route('/'),
        ];
    }
}
