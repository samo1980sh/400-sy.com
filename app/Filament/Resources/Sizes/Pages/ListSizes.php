<?php

namespace App\Filament\Resources\Sizes\Pages;

use App\Filament\Resources\Sizes\SizeResource;
use App\Filament\Resources\Sizes\Schemas\SizeForm;
use App\Models\Size;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Throwable;

class ListSizes extends ListRecords
{
    protected static string $resource = SizeResource::class;
    protected static ?string $title = 'القياسات';
    protected static ?string $breadcrumb = 'القياسات';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createSize')
                ->label('إضافة قياس')
                ->icon(Heroicon::OutlinedPlusCircle)
                ->color('primary')
                ->modalHeading('إضافة قياس')
                ->modalSubmitActionLabel('حفظ')
                ->modalWidth('4xl')
                ->schema(SizeForm::components())
                ->action(function (array $data): void {
                    try {
                        Size::create($data);

                        Notification::make()
                            ->title('تمت إضافة القياس بنجاح.')
                            ->success()
                            ->send();
                    } catch (Throwable $exception) {
                        report($exception);

                        Notification::make()
                            ->title('فشل إضافة القياس.')
                            ->body($exception->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
