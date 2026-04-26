<?php

namespace App\Filament\Resources\CustomerServiceFaqs\Tables;

use App\Filament\Resources\CustomerServiceFaqs\Schemas\CustomerServiceFaqForm;
use App\Models\CustomerServiceFaq;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Throwable;

class CustomerServiceFaqsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('sort_order')
                    ->label('الترتيب')
                    ->sortable(),
                TextColumn::make('question_ar')
                    ->label('السؤال بالعربية')
                    ->searchable()
                    ->limit(60),
                TextColumn::make('question_en')
                    ->label('Question in English')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->limit(60),
                IconColumn::make('is_active')
                    ->label('فعال')
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->label('آخر تحديث')
                    ->dateTime()
                    ->sortable(),
            ])
            ->headerActions([
                Action::make('createCustomerServiceFaq')
                    ->label('إضافة سؤال شائع')
                    ->icon(Heroicon::OutlinedPlusCircle)
                    ->color('primary')
                    ->modalHeading('إضافة سؤال شائع')
                    ->modalSubmitActionLabel('حفظ')
                    ->modalWidth('5xl')
                    ->schema(CustomerServiceFaqForm::components())
                    ->action(function (array $data): void {
                        try {
                            CustomerServiceFaq::create($data);

                            Notification::make()
                                ->title('تمت إضافة السؤال الشائع بنجاح.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل إضافة السؤال الشائع.')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->recordActions([
                Action::make('editFaq')
                    ->label('تعديل')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->color('gray')
                    ->modalHeading('تعديل سؤال شائع')
                    ->modalSubmitActionLabel('حفظ')
                    ->modalWidth('5xl')
                    ->schema(fn (CustomerServiceFaq $record) => CustomerServiceFaqForm::components($record))
                    ->fillForm(fn (CustomerServiceFaq $record): array => $record->only([
                        'sort_order',
                        'is_active',
                        'question_ar',
                        'question_en',
                        'answer_ar',
                        'answer_en',
                    ]))
                    ->action(function (CustomerServiceFaq $record, array $data): void {
                        try {
                            $record->update($data);

                            Notification::make()
                                ->title('تم تحديث السؤال الشائع بنجاح.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل تحديث السؤال الشائع.')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('deleteFaq')
                    ->label('حذف')
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('حذف سؤال شائع')
                    ->modalDescription('سيتم حذف السؤال الشائع نهائيًا.')
                    ->modalSubmitActionLabel('حذف')
                    ->action(function (CustomerServiceFaq $record): void {
                        try {
                            $record->delete();

                            Notification::make()
                                ->title('تم حذف السؤال الشائع بنجاح.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل حذف السؤال الشائع.')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
