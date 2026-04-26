<?php

namespace App\Filament\Resources\Permissions\Pages;

use App\Filament\Resources\Permissions\PermissionResource;
use App\Filament\Resources\Permissions\Schemas\PermissionForm;
use App\Models\Permission;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Throwable;

class ListPermissions extends ListRecords
{
    protected static string $resource = PermissionResource::class;
    protected static ?string $title = 'الصلاحيات';
    protected static ?string $breadcrumb = 'الصلاحيات';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createPermission')
                ->label('إضافة صلاحية')
                ->icon(Heroicon::OutlinedPlusCircle)
                ->color('primary')
                ->modalHeading('إضافة صلاحية')
                ->modalSubmitActionLabel('حفظ')
                ->modalWidth('4xl')
                ->schema(PermissionForm::components())
                ->action(function (array $data): void {
                    try {
                        Permission::create($data);

                        Notification::make()
                            ->title('تمت إضافة الصلاحية بنجاح.')
                            ->success()
                            ->send();
                    } catch (Throwable $exception) {
                        report($exception);

                        Notification::make()
                            ->title('فشل إضافة الصلاحية.')
                            ->body($exception->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
