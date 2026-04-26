<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use App\Filament\Resources\Roles\Schemas\RoleForm;
use App\Models\Role;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Throwable;

class ListRoles extends ListRecords
{
    protected static string $resource = RoleResource::class;
    protected static ?string $title = 'الأدوار';
    protected static ?string $breadcrumb = 'الأدوار';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createRole')
                ->label('إضافة دور')
                ->icon(Heroicon::OutlinedPlusCircle)
                ->color('primary')
                ->modalHeading('إضافة دور')
                ->modalSubmitActionLabel('حفظ')
                ->modalWidth('4xl')
                ->schema(RoleForm::components())
                ->action(function (array $data): void {
                    try {
                        $permissions = $data['permissions'] ?? [];
                        unset($data['permissions']);

                        $role = Role::create($data);
                        $role->permissions()->sync($permissions);

                        Notification::make()
                            ->title('تمت إضافة الدور بنجاح.')
                            ->success()
                            ->send();
                    } catch (Throwable $exception) {
                        report($exception);

                        Notification::make()
                            ->title('فشل إضافة الدور.')
                            ->body($exception->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
