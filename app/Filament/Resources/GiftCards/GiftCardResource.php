<?php

namespace App\Filament\Resources\GiftCards;

use App\Filament\Resources\GiftCards\Pages\ListGiftCards;
use App\Filament\Resources\GiftCards\Schemas\GiftCardForm;
use App\Filament\Resources\GiftCards\Tables\GiftCardsTable;
use App\Models\GiftCard;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class GiftCardResource extends \App\Filament\Resources\RbacResource
{
    protected static ?string $model = GiftCard::class;
    protected static ?string $permissionPrefix = 'gift-cards';
    protected static ?string $modelLabel = 'طلب بطاقة هدية';
    protected static ?string $pluralModelLabel = 'طلبات بطاقات الهدايا';
    protected static string|UnitEnum|null $navigationGroup = 'إدارة الزبائن';
    protected static ?string $navigationLabel = 'طلبات بطاقات الهدايا';
    protected static ?int $navigationSort = 8;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGift;

    public static function form(Schema $schema): Schema
    {
        return GiftCardForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GiftCardsTable::configure($table);
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
            'index' => ListGiftCards::route('/'),
        ];
    }
}
