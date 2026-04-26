<?php

namespace App\Filament\Resources\JobVacancies\Tables;

use App\Filament\Resources\JobVacancies\Schemas\JobVacancyForm;
use App\Filament\Resources\JobVacancies\JobVacancyResource;
use App\Models\JobVacancy;
use App\Models\RecruitmentSetting;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Throwable;

class JobVacanciesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title_ar')
                    ->label('العنوان بالعربية')
                    ->badge()
                    ->searchable(),
                TextColumn::make('title_en')
                    ->label('العنوان بالانكليزية')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('location_ar')
                    ->label('الموقع')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('applications_count')
                    ->label('الطلبات')
                    ->badge()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'active' => 'فعال',
                        'inactive' => 'غير فعال',
                        default => (string) $state,
                    }),
                TextColumn::make('deadline_at')
                    ->label('آخر موعد')
                    ->date()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->columnManager()
            ->columnManagerTriggerAction(fn (Action $action) => $action
                ->label('إظهار / إخفاء الأعمدة')
                ->icon(Heroicon::OutlinedViewColumns))
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'active' => 'فعال',
                        'inactive' => 'غير فعال',
                    ]),
            ])
            ->headerActions([
                Action::make('recruitmentSettings')
                    ->label('إعدادات التوظيف')
                    ->icon(Heroicon::OutlinedCog6Tooth)
                    ->color('gray')
                    ->modalHeading('إعدادات التوظيف')
                    ->modalSubmitActionLabel('حفظ')
                    ->modalWidth('4xl')
                    ->schema(fn () => \App\Filament\Resources\RecruitmentSettings\Schemas\RecruitmentSettingForm::components())
                    ->fillForm(fn (): array => RecruitmentSetting::singleton()->only([
                        'is_enabled',
                        'title_ar',
                        'title_en',
                        'intro_ar',
                        'intro_en',
                    ]))
                    ->action(function (array $data): void {
                        try {
                            RecruitmentSetting::singleton()->update($data);

                            Notification::make()
                                ->title('تم تحديث إعدادات التوظيف بنجاح.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل تحديث إعدادات التوظيف.')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('createJobVacancy')
                    ->label('إضافة وظيفة')
                    ->icon(Heroicon::OutlinedPlusCircle)
                    ->color('primary')
                    ->modalHeading('إضافة وظيفة')
                    ->modalSubmitActionLabel('حفظ')
                    ->modalWidth('4xl')
                    ->schema(JobVacancyForm::components())
                    ->action(function (array $data): void {
                        try {
                            JobVacancy::create($data);

                            Notification::make()
                                ->title('تمت إضافة الوظيفة بنجاح.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل إضافة الوظيفة.')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->recordActions([
                Action::make('viewJobVacancy')
                    ->label('تفاصيل')
                    ->icon(Heroicon::OutlinedEye)
                    ->color('gray')
                    ->url(fn (JobVacancy $record): string => JobVacancyResource::getUrl('view', ['record' => $record])),
                Action::make('editJobVacancy')
                    ->label('تعديل')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->color('gray')
                    ->modalHeading('تعديل وظيفة')
                    ->modalSubmitActionLabel('حفظ')
                    ->modalWidth('4xl')
                    ->schema(fn (JobVacancy $record) => JobVacancyForm::components($record))
                    ->fillForm(fn (JobVacancy $record): array => $record->only([
                        'sort_order',
                        'title_ar',
                        'title_en',
                        'location_ar',
                        'location_en',
                        'deadline_at',
                        'description_ar',
                        'description_en',
                        'requirements_ar',
                        'requirements_en',
                        'status',
                    ]))
                    ->action(function (JobVacancy $record, array $data): void {
                        try {
                            $record->update($data);

                            Notification::make()
                                ->title('تم تحديث الوظيفة بنجاح.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل تحديث الوظيفة.')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('deleteJobVacancy')
                    ->label('حذف')
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (JobVacancy $record): void {
                        try {
                            $record->delete();

                            Notification::make()
                                ->title('تم حذف الوظيفة بنجاح.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل حذف الوظيفة.')
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
            ])
            ->modifyQueryUsing(fn ($query) => $query->withCount('applications')->orderBy('sort_order')->orderBy('title_ar'));
    }
}
