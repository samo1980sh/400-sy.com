<?php

namespace App\Filament\Resources\Customers\Tables;

use App\Filament\Resources\Customers\Schemas\CustomerForm;
use App\Models\Customer;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Throwable;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query
                ->with('retailGroups')
                ->withCount('addresses'))
            ->columns([
                TextColumn::make('account_no')
                    ->label('رقم الحساب')
                    ->searchable()
                    ->extraAttributes(['dir' => 'ltr']),
                TextColumn::make('name')
                    ->label('الاسم الكامل')
                    ->searchable(),
                TextColumn::make('retail_groups')
                    ->label('فئات المفرق')
                    ->badge()
                    ->formatStateUsing(fn ($state, Customer $record): string => $record->retailGroups
                        ->pluck('name')
                        ->filter()
                        ->implode('، '))
                    ->placeholder('—')
                    ->wrap(),
                TextColumn::make('addresses_count')
                    ->label('عدد العناوين')
                    ->badge(),
                TextColumn::make('mobile')
                    ->label('رقم الموبايل')
                    ->searchable()
                    ->copyable()
                    ->extraAttributes(['dir' => 'ltr']),
                TextColumn::make('city')
                    ->label('المدينة')
                    ->searchable(),
                TextColumn::make('area')
                    ->label('المنطقة')
                    ->searchable(),
                TextColumn::make('gender')
                    ->label('الجنس')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'male' => 'ذكر',
                        'female' => 'أنثى',
                        default => (string) $state,
                    }),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'active' => 'فعال',
                        'inactive' => 'غير فعال',
                        default => (string) $state,
                    }),
                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable(),
            ])
            ->headerActions([
                Action::make('createCustomer')
                    ->label('إضافة زبون')
                    ->icon(Heroicon::OutlinedPlusCircle)
                    ->color('primary')
                    ->modalHeading('إضافة زبون')
                    ->modalSubmitActionLabel('حفظ')
                    ->modalWidth('5xl')
                    ->schema(CustomerForm::components())
                    ->action(function (array $data): void {
                        try {
                            $retailGroupIds = collect(Arr::pull($data, 'retail_group_ids', []))
                                ->filter()
                                ->map(fn ($id): int => (int) $id)
                                ->values()
                                ->all();

                            $customer = Customer::create($data);
                            $customer->retailGroups()->sync($retailGroupIds);

                            Notification::make()
                                ->title('تمت إضافة الزبون بنجاح.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل إضافة الزبون.')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->recordActions([
                Action::make('manageAddresses')
                    ->label('العناوين')
                    ->icon(Heroicon::OutlinedMapPin)
                    ->color('gray')
                    ->slideOver()
                    ->modalHeading(fn (Customer $record): string => 'عناوين الزبون: ' . $record->name)
                    ->modalDescription('يمكن إضافة العناوين وتعديلها وحذفها من هنا دون مغادرة صفحة الزبائن.')
                    ->modalSubmitActionLabel('حفظ العناوين')
                    ->modalCancelActionLabel('إلغاء')
                    ->modalWidth('5xl')
                    ->schema([
                        Repeater::make('addresses')
                            ->label('عناوين الزبون')
                            ->addActionLabel('إضافة عنوان')
                            ->itemLabel(fn (array $state): string => filled($state['label'] ?? null)
                                ? (string) $state['label']
                                : 'عنوان جديد')
                            ->itemNumbers()
                            ->collapsible()
                            ->reorderable(false)
                            ->defaultItems(0)
                            ->columns(2)
                            ->schema([
                                Hidden::make('id'),
                                TextInput::make('label')
                                    ->label('اسم العنوان')
                                    ->placeholder('المنزل، العمل...')
                                    ->required()
                                    ->maxLength(255),
                                Select::make('address_type')
                                    ->label('نوع العنوان')
                                    ->options([
                                        'home' => 'منزل',
                                        'work' => 'عمل',
                                        'other' => 'أخرى',
                                    ])
                                    ->default('home')
                                    ->required(),
                                TextInput::make('contact_name')
                                    ->label('اسم المستلم')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('mobile')
                                    ->label('رقم الموبايل')
                                    ->required()
                                    ->maxLength(255)
                                    ->extraInputAttributes(['dir' => 'ltr']),
                                TextInput::make('city')
                                    ->label('المدينة')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('area')
                                    ->label('المنطقة')
                                    ->maxLength(255),
                                Textarea::make('address_line')
                                    ->label('العنوان التفصيلي')
                                    ->required()
                                    ->rows(3)
                                    ->columnSpanFull(),
                                Toggle::make('is_default')
                                    ->label('العنوان الافتراضي')
                                    ->helperText('سيتم اعتماد عنوان افتراضي واحد فقط.')
                                    ->default(false),
                                Textarea::make('notes')
                                    ->label('ملاحظات')
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ])
                            ->columnSpanFull(),
                    ])
                    ->fillForm(fn (Customer $record): array => [
                        'addresses' => $record->addresses()
                            ->orderByDesc('is_default')
                            ->orderBy('id')
                            ->get()
                            ->map(fn ($address): array => [
                                'id' => $address->getKey(),
                                'label' => $address->label,
                                'contact_name' => $address->contact_name,
                                'mobile' => $address->mobile,
                                'city' => $address->city,
                                'area' => $address->area,
                                'address_line' => $address->address_line,
                                'address_type' => $address->address_type,
                                'is_default' => (bool) $address->is_default,
                                'notes' => $address->notes,
                            ])
                            ->all(),
                    ])
                    ->action(function (Customer $record, array $data): void {
                        try {
                            DB::transaction(function () use ($record, $data): void {
                                $rows = collect($data['addresses'] ?? [])->values();
                                $currentDefaultId = (int) ($record->addresses()
                                    ->where('is_default', true)
                                    ->value('customer_addresses.id') ?? 0);

                                $defaultIndex = $rows->search(
                                    fn (array $row): bool => (bool) ($row['is_default'] ?? false)
                                );

                                if ($defaultIndex === false && $currentDefaultId > 0) {
                                    $defaultIndex = $rows->search(
                                        fn (array $row): bool => (int) ($row['id'] ?? 0) === $currentDefaultId
                                    );
                                }

                                if ($defaultIndex === false && $rows->isNotEmpty()) {
                                    $defaultIndex = 0;
                                }

                                $submittedIds = [];

                                foreach ($rows as $index => $row) {
                                    $addressId = filled($row['id'] ?? null)
                                        ? (int) $row['id']
                                        : null;

                                    $payload = Arr::only($row, [
                                        'label',
                                        'contact_name',
                                        'mobile',
                                        'city',
                                        'area',
                                        'address_line',
                                        'address_type',
                                        'notes',
                                    ]);
                                    $payload['is_default'] = $index === $defaultIndex;

                                    if ($addressId !== null) {
                                        $address = $record->addresses()
                                            ->whereKey($addressId)
                                            ->firstOrFail();
                                        $address->update($payload);
                                    } else {
                                        $address = $record->addresses()->create($payload);
                                    }

                                    $submittedIds[] = (int) $address->getKey();
                                }

                                $addressesToDelete = $record->addresses();

                                if ($submittedIds !== []) {
                                    $addressesToDelete->whereNotIn('customer_addresses.id', $submittedIds);
                                }

                                $addressesToDelete->get()->each->delete();
                            });

                            Notification::make()
                                ->title('تم حفظ عناوين الزبون بنجاح.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل حفظ عناوين الزبون.')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('editCustomer')
                    ->label('تعديل')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->color('gray')
                    ->modalHeading('تعديل زبون')
                    ->modalSubmitActionLabel('حفظ')
                    ->modalWidth('5xl')
                    ->schema(CustomerForm::components())
                    ->fillForm(fn (Customer $record): array => $record->only([
                        'account_no',
                        'name',
                        'birth_date',
                        'nationality',
                        'mobile',
                        'secondary_mobile',
                        'gender',
                        'city',
                        'area',
                        'job_title',
                        'marital_status',
                        'email',
                        'status',
                        'notes',
                    ]) + [
                        'retail_group_ids' => $record->retailGroups()
                            ->pluck('retail_customer_groups.id')
                            ->all(),
                    ])
                    ->action(function (Customer $record, array $data): void {
                        try {
                            $retailGroupIds = collect(Arr::pull($data, 'retail_group_ids', []))
                                ->filter()
                                ->map(fn ($id): int => (int) $id)
                                ->values()
                                ->all();

                            if (empty($data['password'])) {
                                unset($data['password']);
                            }

                            $record->update($data);
                            $record->retailGroups()->sync($retailGroupIds);

                            Notification::make()
                                ->title('تم تحديث الزبون بنجاح.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل تحديث الزبون.')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('deleteCustomer')
                    ->label('حذف')
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Customer $record): void {
                        try {
                            $record->delete();

                            Notification::make()
                                ->title('تم حذف الزبون بنجاح.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل حذف الزبون.')
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
