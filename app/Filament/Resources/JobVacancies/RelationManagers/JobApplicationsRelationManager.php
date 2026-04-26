<?php

namespace App\Filament\Resources\JobVacancies\RelationManagers;

use App\Filament\Resources\JobVacancies\RelationManagers\Schemas\JobApplicationForm;
use App\Models\JobApplication;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Resources\RelationManagers\RelationManager;
use Throwable;

class JobApplicationsRelationManager extends RelationManager
{
    protected static string $relationship = 'applications';
    protected static ?string $title = 'طلبات التوظيف';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('full_name')
                    ->label('الاسم الكامل')
                    ->badge()
                    ->searchable(),
                TextColumn::make('mobile')
                    ->label('الموبايل')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('البريد الإلكتروني')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('city')
                    ->label('المدينة')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge(),
                TextColumn::make('created_at')
                    ->label('تاريخ الطلب')
                    ->dateTime(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'new' => 'جديد',
                        'reviewing' => 'قيد المراجعة',
                        'accepted' => 'مقبول',
                        'rejected' => 'مرفوض',
                    ]),
            ])
            ->recordActions([
                Action::make('processApplication')
                    ->label('معالجة')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->color('gray')
                    ->modalHeading('معالجة طلب التوظيف')
                    ->modalSubmitActionLabel('حفظ')
                    ->modalWidth('4xl')
                    ->schema(fn () => JobApplicationForm::components())
                    ->fillForm(fn (JobApplication $record): array => $record->only([
                        'status',
                        'notes',
                    ]))
                    ->action(function (JobApplication $record, array $data): void {
                        try {
                            $record->update($data);

                            Notification::make()
                                ->title('تم تحديث طلب التوظيف بنجاح.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل تحديث طلب التوظيف.')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ]);
    }
}
