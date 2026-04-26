<?php

namespace App\Filament\Resources\Permissions\Tables;

use App\Filament\Resources\Permissions\Schemas\PermissionForm;
use App\Models\Permission;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Throwable;

class PermissionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('اسم الصلاحية')
                    ->searchable(),
                TextColumn::make('slug')
                    ->label('المعرف')
                    ->searchable(),
                TextColumn::make('group')
                    ->label('المجموعة')
                    ->searchable(),
                IconColumn::make('is_active')
                    ->label('الحالة')
                    ->boolean(),
                TextColumn::make('roles_count')
                    ->label('عدد الأدوار')
                    ->counts('roles'),
            ])
            ->recordActions([
                Action::make('editPermission')
                    ->label('تعديل')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->color('gray')
                    ->modalHeading('تعديل صلاحية')
                    ->modalSubmitActionLabel('حفظ')
                    ->modalWidth('4xl')
                    ->schema(PermissionForm::components())
                    ->fillForm(fn (Permission $record): array => [
                        'name' => $record->name,
                        'slug' => $record->slug,
                        'group' => $record->group,
                        'description' => $record->description,
                        'is_active' => $record->is_active ? 1 : 0,
                    ])
                    ->action(function (Permission $record, array $data): void {
                        try {
                            $record->update($data);

                            Notification::make()
                                ->title('تم تحديث الصلاحية بنجاح.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل تحديث الصلاحية.')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('deletePermission')
                    ->label('حذف')
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Permission $record): void {
                        try {
                            $record->delete();

                            Notification::make()
                                ->title('تم حذف الصلاحية بنجاح.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل حذف الصلاحية.')
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
