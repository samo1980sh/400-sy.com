<?php

namespace App\Filament\Resources\Users\Tables;

use App\Filament\Resources\Users\Schemas\UserForm;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Throwable;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('avatar')
                    ->label('الصورة'),
                TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('البريد الإلكتروني')
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('الهاتف')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'active' => 'فعال',
                        'inactive' => 'غير فعال',
                        default => (string) $state,
                    }),
                TextColumn::make('user_type')
                    ->label('النوع')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'staff' => 'موظف',
                        'customer' => 'عميل',
                        default => (string) $state,
                    }),
                TextColumn::make('roles.name')
                    ->label('الأدوار')
                    ->badge()
                    ->separator(','),
            ])
            ->recordActions([
                Action::make('editUser')
                    ->label('تعديل')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->color('gray')
                    ->modalHeading('تعديل مستخدم')
                    ->modalSubmitActionLabel('حفظ')
                    ->modalWidth('4xl')
                    ->schema(UserForm::components())
                    ->fillForm(fn (User $record): array => [
                        'name' => $record->name,
                        'email' => $record->email,
                        'phone' => $record->phone,
                        'avatar' => $record->avatar,
                        'status' => $record->status,
                        'user_type' => $record->user_type,
                        'roles' => $record->roles()->pluck('roles.id')->all(),
                    ])
                    ->action(function (User $record, array $data): void {
                        try {
                            $roles = $data['roles'] ?? [];
                            unset($data['roles']);

                            if (empty($data['password'])) {
                                unset($data['password']);
                            }

                            $record->update($data);
                            $record->roles()->sync($roles);

                            Notification::make()
                                ->title('تم تحديث المستخدم بنجاح.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل تحديث المستخدم.')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('deleteUser')
                    ->label('حذف')
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (User $record): void {
                        try {
                            $record->delete();

                            Notification::make()
                                ->title('تم حذف المستخدم بنجاح.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل حذف المستخدم.')
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
            ]);
    }
}
