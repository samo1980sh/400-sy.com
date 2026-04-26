<?php

namespace App\Filament\Resources\Roles\Tables;

use App\Filament\Resources\Roles\Schemas\RoleForm;
use App\Models\Role;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Throwable;

class RolesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('اسم الدور')
                    ->searchable(),
                TextColumn::make('slug')
                    ->label('المعرف')
                    ->searchable(),
                TextColumn::make('description')
                    ->label('الوصف')
                    ->limit(60),
                IconColumn::make('is_active')
                    ->label('الحالة')
                    ->boolean(),
                TextColumn::make('permissions_count')
                    ->label('عدد الصلاحيات')
                    ->counts('permissions'),
            ])
            ->recordActions([
                Action::make('editRole')
                    ->label('تعديل')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->color('gray')
                    ->modalHeading('تعديل دور')
                    ->modalSubmitActionLabel('حفظ')
                    ->modalWidth('4xl')
                    ->schema(RoleForm::components())
                    ->fillForm(fn (Role $record): array => [
                        'name' => $record->name,
                        'slug' => $record->slug,
                        'description' => $record->description,
                        'is_active' => $record->is_active ? 1 : 0,
                        'permissions' => $record->permissions()->pluck('permissions.id')->all(),
                    ])
                    ->action(function (Role $record, array $data): void {
                        try {
                            $permissions = $data['permissions'] ?? [];
                            unset($data['permissions']);

                            $record->update($data);
                            $record->permissions()->sync($permissions);

                            Notification::make()
                                ->title('تم تحديث الدور بنجاح.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل تحديث الدور.')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('deleteRole')
                    ->label('حذف')
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Role $record): void {
                        try {
                            $record->delete();

                            Notification::make()
                                ->title('تم حذف الدور بنجاح.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل حذف الدور.')
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
