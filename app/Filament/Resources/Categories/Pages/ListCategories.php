<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Categories\Schemas\CategoryForm;
use App\Models\Category;
use App\Services\CategoryExportService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Livewire\Attributes\Url;
use Filament\Resources\Pages\ListRecords;
use Throwable;

class ListCategories extends ListRecords
{
    protected static string $resource = CategoryResource::class;
    protected static ?string $title = 'التصنيفات';
    protected static ?string $breadcrumb = 'التصنيفات';

    #[Url(as: 'parent')]
    public ?int $parent = null;

    public function getTitle(): string
    {
        if (! $this->parentCategory()) {
            return 'التصنيفات الرئيسية';
        }

        return 'التصنيفات الفرعية لـ ' . $this->parentCategory()->title_ar;
    }

    public function getBreadcrumb(): ?string
    {
        $parent = $this->parentCategory();

        if (! $parent) {
            return 'التصنيفات';
        }

        return $parent->title_ar;
    }

    public function getBreadcrumbs(): array
    {
        $breadcrumbs = [
            static::getResource()::getUrl('index') => 'التصنيفات',
        ];

        foreach (Category::breadcrumbTrailFor($this->parent) as $category) {
            $breadcrumbs[static::getResource()::getUrl('index', ['parent' => $category->id])] = $category->title_ar;
        }

        return $breadcrumbs;
    }

    protected function getHeaderActions(): array
    {
        $actions = [];

        if ($this->parentCategory()?->parent_id) {
            $actions[] = Action::make('back')
                ->label('رجوع')
                ->color('gray')
                ->icon(Heroicon::OutlinedArrowLeft)
                ->url(static::getResource()::getUrl('index', ['parent' => $this->parentCategory()->parent_id]));
        } elseif ($this->parent) {
            $actions[] = Action::make('backToRoot')
                ->label('رجوع إلى الجذر')
                ->color('gray')
                ->icon(Heroicon::OutlinedArrowLeft)
                ->url(static::getResource()::getUrl('index'));
        }

        $actions[] = $this->exportAction();

        $actions[] = Action::make('createCategory')
            ->label('إضافة تصنيف')
            ->icon(Heroicon::OutlinedPlus)
            ->color('primary')
            ->modalHeading('إضافة تصنيف')
            ->modalSubmitActionLabel('حفظ')
            ->modalWidth('4xl')
            ->schema(CategoryForm::components())
            ->action(function (array $data): void {
                $data['parent_id'] = $data['parent_id'] ?? $this->parent;

                try {
                    Category::create($data);

                    Notification::make()
                        ->title('تمت إضافة التصنيف بنجاح.')
                        ->success()
                        ->send();
                } catch (Throwable $exception) {
                    report($exception);

                    Notification::make()
                        ->title('فشل إضافة التصنيف.')
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();
                }
            });

        return $actions;
    }

    protected function exportAction(): Action
    {
        return Action::make('exportCategories')
            ->label('تصدير')
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->color('success')
            ->action(fn () => app(CategoryExportService::class)->download());
    }

    public function table(\Filament\Tables\Table $table): \Filament\Tables\Table
    {
        return parent::table($table)->recordUrl(function (Category $record): ?string {
            if ($record->children_count > 0) {
                return static::getResource()::getUrl('index', ['parent' => $record->id]);
            }

            return null;
        });
    }

    protected function getTableQuery(): Builder | Relation | null
    {
        return Category::query()
            ->withCount('children')
            ->when(
                $this->parent,
                fn (Builder $query): Builder => $query->where('parent_id', $this->parent),
                fn (Builder $query): Builder => $query->whereNull('parent_id'),
            )
            ->orderBy('sort_order')
            ->orderBy('title_ar');
    }

    protected function parentCategory(): ?Category
    {
        if (! $this->parent) {
            return null;
        }

        return Category::query()->find($this->parent);
    }
}
