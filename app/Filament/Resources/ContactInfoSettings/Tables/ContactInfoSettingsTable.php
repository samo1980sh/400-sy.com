<?php

namespace App\Filament\Resources\ContactInfoSettings\Tables;

use App\Filament\Resources\ContactInfoSettings\Schemas\ContactInfoSettingForm;
use App\Models\ContactInfoSetting;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Throwable;

class ContactInfoSettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company_name_ar')
                    ->label('اسم الشركة بالعربية')
                    ->badge(),
                TextColumn::make('company_name_en')
                    ->label('اسم الشركة بالانكليزية')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('phone')
                    ->label('الهاتف')
                    ->extraAttributes([
                        'dir' => 'ltr',
                        'style' => 'direction: ltr; unicode-bidi: isolate; text-align: left;',
                    ]),
                TextColumn::make('mobile')
                    ->label('الموبايل')
                    ->extraAttributes([
                        'dir' => 'ltr',
                        'style' => 'direction: ltr; unicode-bidi: isolate; text-align: left;',
                    ])
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('email')
                    ->label('البريد الإلكتروني')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                Action::make('editContactInfo')
                    ->label('تعديل')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->color('gray')
                    ->modalHeading('تعديل معلومات الاتصال')
                    ->modalSubmitActionLabel('حفظ')
                    ->modalWidth('4xl')
                    ->schema(fn (ContactInfoSetting $record) => ContactInfoSettingForm::components())
                    ->fillForm(fn (ContactInfoSetting $record): array => $record->only([
                        'company_name_ar',
                        'company_name_en',
                        'address_ar',
                        'address_en',
                        'phone',
                        'mobile',
                        'whatsapp',
                        'email',
                        'map_url',
                        'facebook_url',
                        'instagram_url',
                        'x_url',
                        'youtube_url',
                        'working_hours_ar',
                        'working_hours_en',
                        'notes_ar',
                        'notes_en',
                    ]))
                    ->action(function (ContactInfoSetting $record, array $data): void {
                        try {
                            $record->update($data);

                            Notification::make()
                                ->title('تم تحديث معلومات الاتصال بنجاح.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل تحديث معلومات الاتصال.')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ]);
    }
}
