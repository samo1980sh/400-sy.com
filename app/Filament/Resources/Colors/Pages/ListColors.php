<?php

namespace App\Filament\Resources\Colors\Pages;

use App\Filament\Resources\Colors\ColorResource;
use App\Filament\Resources\Colors\Schemas\ColorForm;
use App\Models\Color;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use Throwable;

class ListColors extends ListRecords
{
    protected static string $resource = ColorResource::class;
    protected static ?string $title = 'الألوان';
    protected static ?string $breadcrumb = 'الألوان';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('purgeColors')
                ->label('تفريغ')
                ->icon(Heroicon::OutlinedTrash)
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('تفريغ الألوان')
                ->modalDescription('سيتم حذف جميع الألوان وإعادة ترقيم الجدول من 1.')
                ->modalSubmitActionLabel('تفريغ')
                ->action(function (): void {
                    DB::statement('SET FOREIGN_KEY_CHECKS=0');
                    DB::table('colors')->truncate();
                    DB::statement('SET FOREIGN_KEY_CHECKS=1');

                    Notification::make()
                        ->title('تم تفريغ الألوان بنجاح.')
                        ->success()
                        ->send();
                }),
            Action::make('createColor')
                ->label('إضافة لون')
                ->icon(Heroicon::OutlinedPlusCircle)
                ->color('primary')
                ->modalHeading('إضافة لون')
                ->modalSubmitActionLabel('حفظ')
                ->modalWidth('4xl')
                ->schema(ColorForm::components())
                ->action(function (array $data): void {
                    try {
                        Color::create($data);

                        Notification::make()
                            ->title('تمت إضافة اللون بنجاح.')
                            ->success()
                            ->send();
                    } catch (Throwable $exception) {
                        report($exception);

                        Notification::make()
                            ->title('فشل إضافة اللون.')
                            ->body($exception->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
