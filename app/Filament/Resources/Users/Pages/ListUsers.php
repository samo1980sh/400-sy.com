<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Throwable;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;
    protected static ?string $title = 'المستخدمون';
    protected static ?string $breadcrumb = 'المستخدمون';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createUser')
                ->label('إضافة مستخدم')
                ->icon(Heroicon::OutlinedPlusCircle)
                ->color('primary')
                ->modalHeading('إضافة مستخدم')
                ->modalSubmitActionLabel('حفظ')
                ->modalWidth('4xl')
                ->schema(UserForm::components())
                ->action(function (array $data): void {
                    try {
                        $roles = $data['roles'] ?? [];
                        unset($data['roles']);

                        $user = User::create($data);
                        $user->roles()->sync($roles);

                        Notification::make()
                            ->title('تمت إضافة المستخدم بنجاح.')
                            ->success()
                            ->send();
                    } catch (Throwable $exception) {
                        report($exception);

                        Notification::make()
                            ->title('فشل إضافة المستخدم.')
                            ->body($exception->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
