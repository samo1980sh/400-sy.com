<?php

namespace App\Filament\Resources\CompanyHeaderImages\Tables;

use App\Filament\Resources\CompanyHeaderImages\Schemas\CompanyHeaderImageForm;
use App\Models\CompanyHeaderImage;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class CompanyHeaderImagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                ViewColumn::make('preview')
                    ->label('الملف')
                    ->view('filament.tables.columns.company-header-image-preview'),
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
                Action::make('createCompanyHeaderImage')
                    ->label('إضافة سلايدر')
                    ->icon(Heroicon::OutlinedPlusCircle)
                    ->color('primary')
                    ->modalHeading('إضافة سلايدر')
                    ->modalSubmitActionLabel('حفظ')
                    ->modalWidth('4xl')
                    ->schema(fn () => CompanyHeaderImageForm::components())
                    ->action(function (array $data): void {
                        try {
                            self::persistMediaPayload($data);

                            Notification::make()
                                ->title('تمت إضافة الصورة بنجاح.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل إضافة الصورة.')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->recordActions([
                Action::make('editCompanyHeaderImage')
                    ->label('تعديل')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->color('gray')
                    ->modalHeading('تعديل سلايدر')
                    ->modalSubmitActionLabel('حفظ')
                    ->modalWidth('4xl')
                    ->schema(fn (CompanyHeaderImage $record) => CompanyHeaderImageForm::components($record))
                    ->fillForm(fn (CompanyHeaderImage $record): array => $record->only([
                        'image',
                        'video',
                        'sort_order',
                    ]) + [
                        'media' => $record->video ?: $record->image,
                        'status' => in_array($record->status, ['active', '1', 1, true], true),
                    ])
                    ->action(function (CompanyHeaderImage $record, array $data): void {
                        try {
                            self::persistMediaPayload($data, $record);

                            Notification::make()
                                ->title('تم تحديث الصورة بنجاح.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل تحديث الصورة.')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('deleteCompanyHeaderImage')
                    ->label('حذف')
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (CompanyHeaderImage $record): void {
                        try {
                            if (filled($record->image)) {
                                Storage::disk('public')->delete((string) $record->image);
                            }

                            if (filled($record->video)) {
                                Storage::disk('public')->delete((string) $record->video);
                            }

                            $record->delete();

                            Notification::make()
                                ->title('تم حذف السلايدر بنجاح.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل حذف السلايدر.')
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

    protected static function persistMediaPayload(array $data, ?CompanyHeaderImage $record = null): void
    {
        $mediaPath = (string) ($data['media'] ?? '');
        unset($data['media']);

        $normalized = collect($data)
            ->only(['sort_order', 'status'])
            ->all();

        $previousImage = $record?->image;
        $previousVideo = $record?->video;

        $extension = Str::lower(pathinfo($mediaPath, PATHINFO_EXTENSION));
        if (in_array($extension, ['mp4', 'mov', 'webm'], true)) {
            $normalized['image'] = null;
            $normalized['video'] = $mediaPath ?: null;
        } else {
            $normalized['image'] = $mediaPath ?: null;
            $normalized['video'] = null;
        }

        if ($record === null) {
            CompanyHeaderImage::create($normalized);
            return;
        }

        $record->update($normalized);

        if (filled($previousImage) && $previousImage !== $record->image) {
            Storage::disk('public')->delete((string) $previousImage);
        }

        if (filled($previousVideo) && $previousVideo !== $record->video) {
            Storage::disk('public')->delete((string) $previousVideo);
        }
    }
}
