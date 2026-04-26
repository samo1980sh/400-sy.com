<?php

namespace App\Filament\Resources\CompanySocialLinks\Tables;

use App\Filament\Resources\CompanySocialLinks\Schemas\CompanySocialLinkForm;
use App\Models\CompanySocialLink;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Throwable;

class CompanySocialLinksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('platform_key')
                    ->label('المنصة')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'facebook' => 'Facebook',
                        'instagram' => 'Instagram',
                        'x' => 'X',
                        'youtube' => 'YouTube',
                        'tiktok' => 'TikTok',
                        'whatsapp' => 'WhatsApp',
                        'snapchat' => 'Snapchat',
                        'linkedin' => 'LinkedIn',
                        default => (string) $state,
                    }),
                TextColumn::make('url')
                    ->label('الرابط')
                    ->limit(60),
                TextColumn::make('sort_order')
                    ->label('الترتيب')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'active' => 'فعال',
                        'inactive' => 'غير فعال',
                        default => (string) $state,
                    }),
            ])
            ->headerActions([
                Action::make('createCompanySocialLink')
                    ->label('إضافة رابط')
                    ->icon(Heroicon::OutlinedPlusCircle)
                    ->color('primary')
                    ->modalHeading('إضافة رابط تواصل اجتماعي')
                    ->modalSubmitActionLabel('حفظ')
                    ->modalWidth('4xl')
                    ->schema(CompanySocialLinkForm::components())
                    ->action(function (array $data): void {
                        try {
                            $data['status'] = ! empty($data['status']) ? 'active' : 'inactive';
                            $data['sort_order'] = (int) (CompanySocialLink::query()->max('sort_order') ?? 0) + 1;
                            CompanySocialLink::create($data);

                            Notification::make()
                                ->title('تمت إضافة الرابط بنجاح.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل إضافة الرابط.')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->recordActions([
                Action::make('editCompanySocialLink')
                    ->label('تعديل')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->color('gray')
                    ->modalHeading(fn (CompanySocialLink $record): string => 'تعديل: ' . $record->platform_key)
                    ->modalSubmitActionLabel('حفظ')
                    ->modalWidth('4xl')
                    ->schema(fn (CompanySocialLink $record) => CompanySocialLinkForm::components($record))
                    ->fillForm(fn (CompanySocialLink $record): array => [
                        'platform_key' => $record->platform_key,
                        'url' => $record->url,
                        'status' => $record->status === 'active',
                    ])
                    ->action(function (CompanySocialLink $record, array $data): void {
                        try {
                            $data['status'] = ! empty($data['status']) ? 'active' : 'inactive';
                            $data['sort_order'] = $record->sort_order;
                            $record->update($data);

                            Notification::make()
                                ->title('تم تحديث الرابط بنجاح.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل تحديث الرابط.')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('deleteCompanySocialLink')
                    ->label('حذف')
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (CompanySocialLink $record): void {
                        try {
                            $record->delete();

                            Notification::make()
                                ->title('تم حذف الرابط بنجاح.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل حذف الرابط.')
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
