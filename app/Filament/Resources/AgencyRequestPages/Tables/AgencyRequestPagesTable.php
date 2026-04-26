<?php

namespace App\Filament\Resources\AgencyRequestPages\Tables;

use App\Filament\Resources\AgencyRequestPages\Schemas\AgencyRequestPageForm;
use App\Models\AgencyRequestPage;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Throwable;

class AgencyRequestPagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title_ar')
                    ->label('العنوان بالعربية')
                    ->badge(),
                TextColumn::make('title_en')
                    ->label('العنوان بالانكليزية')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                Action::make('editAgencyPage')
                    ->label('تعديل')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->color('gray')
                    ->modalHeading('تعديل طلب وكالة')
                    ->modalSubmitActionLabel('حفظ')
                    ->modalWidth('4xl')
                    ->schema(fn () => AgencyRequestPageForm::components())
                    ->fillForm(fn (AgencyRequestPage $record): array => $record->only([
                        'title_ar',
                        'title_en',
                        'content_ar',
                        'content_en',
                        'terms_ar',
                        'terms_en',
                    ]))
                    ->action(function (AgencyRequestPage $record, array $data): void {
                        try {
                            $record->update($data);

                            Notification::make()
                                ->title('تم تحديث صفحة طلب الوكالة بنجاح.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل تحديث صفحة طلب الوكالة.')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ;
    }
}
