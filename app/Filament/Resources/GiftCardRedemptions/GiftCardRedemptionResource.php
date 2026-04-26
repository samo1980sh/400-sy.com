<?php

namespace App\Filament\Resources\GiftCardRedemptions;

use App\Filament\Resources\GiftCardRedemptions\Pages\ListGiftCardRedemptions;
use App\Filament\Resources\GiftCardRedemptions\Tables\GiftCardRedemptionsTable;
use App\Models\GiftCardRedemption;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class GiftCardRedemptionResource extends \App\Filament\Resources\RbacResource
{
    protected static ?string $model = GiftCardRedemption::class;
    protected static ?string $permissionPrefix = 'gift-card-redemptions';
    protected static ?string $modelLabel = 'استخدام بطاقة هدية';
    protected static ?string $pluralModelLabel = 'سجل بطاقات الهدايا';
    protected static string|UnitEnum|null $navigationGroup = 'إدارة الزبائن';
    protected static ?string $navigationLabel = 'سجل بطاقات الهدايا';
    protected static ?int $navigationSort = 9;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    public static function table(Table $table): Table
    {
        return GiftCardRedemptionsTable::configure($table);
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
            'index' => ListGiftCardRedemptions::route('/'),
        ];
    }
}
