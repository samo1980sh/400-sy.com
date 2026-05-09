<?php

namespace App\Filament\Resources\Colors\Tables;

use App\Filament\Resources\Colors\Schemas\ColorForm;
use App\Models\Color;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Throwable;

class ColorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name_ar')
                    ->label('الاسم بالعربي')
                    ->searchable(),
                TextColumn::make('name_en')
                    ->label('الاسم بالإنكليزي')
                    ->searchable(),
                TextColumn::make('code')
                    ->label('رمز اللون')
                    ->searchable(),
                TextColumn::make('hex')
                    ->label('Hex Code')
                    ->searchable(),
                ImageColumn::make('image')
                    ->label('صورة السواش'),
                TextColumn::make('sort_order')
                    ->label('الترتيب')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'active' => 'فعال',
                        'inactive' => 'غير فعال',
                        default => (string) $state,
                    }),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('editColor')
                    ->label('تعديل')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->color('gray')
                    ->modalHeading('تعديل لون')
                    ->modalSubmitActionLabel('حفظ')
                    ->modalWidth('4xl')
                    ->schema(fn (Color $record) => ColorForm::components())
                    ->fillForm(fn (Color $record): array => $record->only([
                        'name_ar',
                        'name_en',
                        'code',
                        'hex',
                        'image',
                        'sort_order',
                        'status',
                    ]))
                    ->action(function (Color $record, array $data): void {
                        try {
                            $record->update($data);

                            Notification::make()
                                ->title('تم تحديث اللون بنجاح.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل تحديث اللون.')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('حذف')
                        ->icon(Heroicon::OutlinedTrash),
                ]),
            ]);
    }
}
