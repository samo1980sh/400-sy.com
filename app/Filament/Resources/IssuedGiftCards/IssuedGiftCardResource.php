<?php

namespace App\Filament\Resources\IssuedGiftCards;

use App\Filament\Resources\IssuedGiftCards\Pages\ListIssuedGiftCards;
use App\Filament\Resources\IssuedGiftCards\Tables\IssuedGiftCardsTable;
use App\Models\GiftCard;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class IssuedGiftCardResource extends \App\Filament\Resources\RbacResource
{
    protected static ?string $model = GiftCard::class;
    protected static ?string $permissionPrefix = 'gift-cards';
    protected static ?string $modelLabel = 'بطاقة هدية صادرة';
    protected static ?string $pluralModelLabel = 'بطاقات الهدايا الصادرة';
    protected static string|UnitEnum|null $navigationGroup = 'إدارة الزبائن';
    protected static ?string $navigationLabel = 'بطاقات الهدايا الصادرة';
    protected static ?int $navigationSort = 9;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    public static function table(Table $table): Table
    {
        return IssuedGiftCardsTable::configure($table);
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
        return true;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListIssuedGiftCards::route('/'),
        ];
    }
}
