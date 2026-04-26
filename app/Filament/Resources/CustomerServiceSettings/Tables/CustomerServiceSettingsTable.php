<?php

namespace App\Filament\Resources\CustomerServiceSettings\Tables;

use App\Filament\Resources\CustomerServiceSettings\Schemas\CustomerServiceSettingForm;
use App\Models\CustomerServiceSetting;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Throwable;

class CustomerServiceSettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('title_ar')
                    ->label('الصفحة')
                    ->searchable()
                    ->badge(),
                TextColumn::make('setting_key')
                    ->label('المفتاح')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('content_ar')
                    ->label('المحتوى العربي')
                    ->limit(80)
                    ->placeholder('-'),
                TextColumn::make('content_en')
                    ->label('Content')
                    ->limit(80)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('app_ios_url')
                    ->label('iOS')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('is_active')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'فعال' : 'غير فعال'),
                TextColumn::make('sort_order')
                    ->label('الترتيب')
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('آخر تحديث')
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('editCustomerService')
                    ->label('تعديل')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->color('gray')
                    ->modalHeading(fn (CustomerServiceSetting $record): string => 'تعديل: ' . $record->title_ar)
                    ->modalSubmitActionLabel('حفظ')
                    ->modalWidth('5xl')
                    ->schema(fn (CustomerServiceSetting $record) => CustomerServiceSettingForm::components($record))
                    ->fillForm(fn (CustomerServiceSetting $record): array => $record->only([
                        'setting_key',
                        'title_ar',
                        'title_en',
                        'content_ar',
                        'content_en',
                        'app_ios_url',
                        'app_android_url',
                        'app_direct_url',
                        'sort_order',
                        'is_active',
                    ]))
                    ->action(function (CustomerServiceSetting $record, array $data): void {
                        try {
                            $data['setting_key'] = $record->setting_key;
                            $record->update($data);

                            Notification::make()
                                ->title('تم تحديث الإعداد بنجاح.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل تحديث الإعداد.')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->toolbarActions([]);
    }
}
