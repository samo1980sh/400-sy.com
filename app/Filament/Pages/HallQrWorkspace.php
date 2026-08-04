<?php

namespace App\Filament\Pages;

use App\Filament\Resources\CustomerLoyaltySettings\CustomerLoyaltySettingResource;
use App\Filament\Resources\CustomerQrCodes\CustomerQrCodeResource;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerLoyaltySetting;
use App\Models\PointVoucherRedemption;
use App\Models\User;
use App\Services\CustomerQrScanService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;
use UnitEnum;

/**
 * @property-read Schema $form
 */
class HallQrWorkspace extends Page
{
    protected static ?string $title = 'تشغيل QR في الصالة';

    protected static ?string $navigationLabel = 'تشغيل QR في الصالة';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static string|UnitEnum|null $navigationGroup = 'الولاء والنقاط و QR';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'hall-qr-workspace';

    protected string $view = 'filament.pages.hall-qr-workspace';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public ?int $customerId = null;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $lastSale = null;

    protected ?Customer $workspaceCustomerCache = null;

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);

        $this->form->fill([
            'branch_id' => Branch::query()
                ->where('status', 'active')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->value('id'),
            'identifier' => '',
            'reference_no' => '',
            'sale_amount' => null,
            'additional_discount_amount' => 0,
            'point_voucher_code' => null,
            'notes' => null,
        ]);
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && $user->status === 'active'
            && $user->user_type === 'staff'
            && ($user->isSuperAdmin() || $user->hasPermission('customer-qr-codes.update'));
    }

    public function getSubheading(): ?string
    {
        return 'امسح QR أو أدخل رقم الحساب، ثم سجّل فاتورة الصالة من نموذج واحد.';
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('qrCodes')
                ->label('إدارة رموز QR')
                ->icon(Heroicon::OutlinedQrCode)
                ->color('gray')
                ->url(CustomerQrCodeResource::getUrl())
                ->visible(fn (): bool => CustomerQrCodeResource::canViewAny()),
            Action::make('loyaltySettings')
                ->label('إعدادات الولاء')
                ->icon(Heroicon::OutlinedCog6Tooth)
                ->color('gray')
                ->url(CustomerLoyaltySettingResource::getUrl())
                ->visible(fn (): bool => CustomerLoyaltySettingResource::canViewAny()),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([
                    Section::make('التعرف على حساب الزبون')
                        ->description('اختر الصالة ثم امسح QR أو أدخل رقم الحساب.')
                        ->icon(Heroicon::OutlinedQrCode)
                        ->columns(2)
                        ->schema([
                            Select::make('branch_id')
                                ->label('الصالة / الفرع')
                                ->options(fn (): array => $this->branchOptions())
                                ->searchable()
                                ->preload()
                                ->required()
                                ->disabled(fn (): bool => $this->customerId !== null)
                                ->dehydrated(),
                            TextInput::make('identifier')
                                ->label('QR أو رقم حساب الزبون')
                                ->helperText('قارئ الباركود يكتب داخل هذا الحقل مباشرة مثل لوحة المفاتيح.')
                                ->prefixIcon(Heroicon::OutlinedQrCode)
                                ->required()
                                ->maxLength(255)
                                ->autocomplete(false)
                                ->autofocus()
                                ->extraInputAttributes(['dir' => 'ltr'])
                                ->disabled(fn (): bool => $this->customerId !== null)
                                ->dehydrated(),
                        ])
                        ->footer([
                            Actions::make([
                                Action::make('identifyCustomer')
                                    ->label('التعرف على الحساب')
                                    ->icon(Heroicon::OutlinedMagnifyingGlass)
                                    ->action(fn () => $this->identifyCustomer())
                                    ->visible(fn (): bool => $this->customerId === null),
                                Action::make('clearCustomer')
                                    ->label('حساب جديد')
                                    ->icon(Heroicon::OutlinedArrowPath)
                                    ->color('gray')
                                    ->action(fn () => $this->clearCustomer())
                                    ->visible(fn (): bool => $this->customerId !== null),
                            ]),
                        ]),

                    Section::make('حساب الزبون')
                        ->description('بيانات الحساب والمزايا المتاحة في الصالة المختارة.')
                        ->icon(Heroicon::OutlinedIdentification)
                        ->compact()
                        ->columns([
                            'default' => 1,
                            'md' => 2,
                            'xl' => 5,
                        ])
                        ->visible(fn (): bool => $this->customerId !== null)
                        ->schema([
                            TextEntry::make('customer_name')
                                ->label('الزبون')
                                ->state(fn (): string => $this->workspaceCustomer()?->name ?: '—'),
                            TextEntry::make('account_no')
                                ->label('رقم الحساب')
                                ->state(fn (): string => $this->workspaceCustomer()?->account_no ?: '—')
                                ->copyable(),
                            TextEntry::make('mobile')
                                ->label('الموبايل')
                                ->state(fn (): string => $this->workspaceCustomer()?->mobile ?: '—'),
                            TextEntry::make('retail_groups')
                                ->label('فئة الزبون')
                                ->state(fn (): string => $this->workspaceCustomer()?->retailGroups
                                    ->pluck('name')
                                    ->filter()
                                    ->implode('، ') ?: 'غير محددة'),
                            TextEntry::make('points_balance')
                                ->label('رصيد النقاط')
                                ->state(fn (): string => number_format(
                                    (float) ($this->workspaceCustomer()?->loyaltyWallet?->points_balance ?? 0),
                                    2,
                                ))
                                ->badge()
                                ->color('success'),
                        ]),

                    Section::make('آخر عملية ناجحة')
                        ->description(fn (): ?string => $this->lastSale
                            ? 'تم ربط الفاتورة ' . $this->lastSale['reference_no'] . ' بحساب الزبون.'
                            : null)
                        ->icon(Heroicon::OutlinedCheckCircle)
                        ->compact()
                        ->columns(4)
                        ->visible(fn (): bool => ! empty($this->lastSale))
                        ->schema([
                            TextEntry::make('last_sale_net')
                                ->label('الصافي')
                                ->state(fn (): string => number_format((float) ($this->lastSale['net_amount'] ?? 0), 2)),
                            TextEntry::make('last_sale_points')
                                ->label('النقاط المضافة')
                                ->state(fn (): string => number_format((float) ($this->lastSale['points_earned'] ?? 0), 2)),
                            TextEntry::make('last_sale_balance')
                                ->label('الرصيد الجديد')
                                ->state(fn (): string => number_format((float) ($this->lastSale['points_balance'] ?? 0), 2)),
                            TextEntry::make('last_sale_branch')
                                ->label('الصالة')
                                ->state(fn (): string => (string) ($this->lastSale['branch'] ?? '—')),
                        ]),

                    Section::make('فاتورة الصالة')
                        ->description('أدخل بيانات الفاتورة، ثم راجع الصافي والنقاط قبل التأكيد.')
                        ->icon(Heroicon::OutlinedReceiptPercent)
                        ->columns(2)
                        ->visible(fn (): bool => $this->customerId !== null)
                        ->schema([
                            TextInput::make('reference_no')
                                ->label('رقم فاتورة / مرجع الصالة')
                                ->required()
                                ->maxLength(255)
                                ->extraInputAttributes(['dir' => 'ltr']),
                            TextInput::make('sale_amount')
                                ->label('قيمة الفاتورة قبل الحسم')
                                ->numeric()
                                ->required()
                                ->minValue(0.01)
                                ->live(debounce: 350),
                            Select::make('point_voucher_code')
                                ->label('قسيمة النقاط')
                                ->placeholder('دون قسيمة')
                                ->options(fn (): array => $this->voucherOptions())
                                ->searchable()
                                ->preload()
                                ->live(),
                            TextInput::make('additional_discount_amount')
                                ->label('الحسم الإضافي')
                                ->numeric()
                                ->minValue(0)
                                ->default(0)
                                ->live(debounce: 350),
                            Textarea::make('notes')
                                ->label('ملاحظات')
                                ->rows(2)
                                ->maxLength(2000)
                                ->columnSpanFull(),
                            Section::make('ملخص العملية')
                                ->secondary()
                                ->compact()
                                ->columns(4)
                                ->columnSpanFull()
                                ->schema([
                                    TextEntry::make('preview_sale_amount')
                                        ->label('قيمة الفاتورة')
                                        ->state(fn (): string => $this->formattedPreviewValue('sale_amount')),
                                    TextEntry::make('preview_total_discount')
                                        ->label('إجمالي الحسومات')
                                        ->state(fn (): string => $this->formattedPreviewValue('total_discount')),
                                    TextEntry::make('preview_net_amount')
                                        ->label('الصافي')
                                        ->state(fn (): string => $this->formattedPreviewValue('net_amount'))
                                        ->badge()
                                        ->color(fn (): string => $this->salePreview()['discount_is_valid'] ? 'success' : 'danger'),
                                    TextEntry::make('preview_points_earned')
                                        ->label('النقاط المتوقعة')
                                        ->state(fn (): string => $this->formattedPreviewValue('points_earned')),
                                ]),
                        ])
                        ->footer([
                            Actions::make([
                                Action::make('recordSale')
                                    ->label('تأكيد الفاتورة وتحديث النقاط')
                                    ->icon(Heroicon::OutlinedCheck)
                                    ->disabled(fn (): bool => ! $this->salePreview()['discount_is_valid'])
                                    ->requiresConfirmation()
                                    ->modalHeading('تأكيد فاتورة الصالة')
                                    ->modalDescription('سيتم تطبيق الحسومات وتسجيل الفاتورة وتحديث رصيد النقاط.')
                                    ->action(fn () => $this->recordSale()),
                            ])
                                ->alignment(Alignment::End),
                        ]),
                ])
                    ->livewireSubmitHandler(null),
            ])
            ->statePath('data');
    }

    public function identifyCustomer(): void
    {
        $data = $this->form->getState();

        $branch = Branch::query()->findOrFail((int) $data['branch_id']);
        $operator = auth()->user();

        $log = app(CustomerQrScanService::class)->recordIdentification(
            identifier: (string) $data['identifier'],
            branch: $branch,
            operator: $operator instanceof User ? $operator : null,
            data: [
                'reference_no' => 'LOOKUP-' . now()->format('Ymd-His') . '-' . strtoupper(str()->random(4)),
                'notes' => 'تعرف على الحساب من شاشة تشغيل QR في الصالة.',
            ],
        );

        $customer = $log->customer;

        if (! $customer instanceof Customer) {
            throw ValidationException::withMessages([
                'data.identifier' => 'تعذر تحميل حساب الزبون بعد قراءة QR.',
            ]);
        }

        $this->customerId = (int) $customer->getKey();
        $this->workspaceCustomerCache = null;
        $this->lastSale = null;

        $this->form->fill([
            'branch_id' => (int) $data['branch_id'],
            'identifier' => (string) $customer->account_no,
            'reference_no' => '',
            'sale_amount' => null,
            'additional_discount_amount' => 0,
            'point_voucher_code' => null,
            'notes' => null,
        ]);

        Notification::make()
            ->title('تم التعرف على حساب الزبون')
            ->body('أدخل بيانات فاتورة الصالة في النموذج الظاهر أدناه.')
            ->success()
            ->send();
    }

    public function recordSale(): void
    {
        if (! $this->customerId) {
            throw ValidationException::withMessages([
                'data.identifier' => 'يجب التعرف على حساب الزبون أولاً.',
            ]);
        }

        $data = $this->form->getState();

        $customer = Customer::query()
            ->whereKey($this->customerId)
            ->where('status', 'active')
            ->first();

        if (! $customer instanceof Customer) {
            $this->clearCustomer();

            throw ValidationException::withMessages([
                'data.identifier' => 'حساب الزبون لم يعد فعالاً. أعد قراءة QR.',
            ]);
        }

        $branch = Branch::query()->findOrFail((int) $data['branch_id']);
        $operator = auth()->user();

        $log = app(CustomerQrScanService::class)->recordHallSale(
            identifier: (string) $customer->account_no,
            branch: $branch,
            operator: $operator instanceof User ? $operator : null,
            data: [
                'reference_no' => trim((string) $data['reference_no']),
                'sale_amount' => (float) $data['sale_amount'],
                'additional_discount_amount' => (float) ($data['additional_discount_amount'] ?: 0),
                'point_voucher_code' => trim((string) ($data['point_voucher_code'] ?? '')),
                'notes' => trim((string) ($data['notes'] ?? '')),
            ],
        );

        $this->workspaceCustomerCache = null;

        $this->lastSale = [
            'reference_no' => $log->reference_no,
            'sale_amount' => (float) $log->sale_amount,
            'discount_amount' => (float) $log->discount_amount,
            'net_amount' => (float) $log->net_amount,
            'points_earned' => (float) $log->points_earned,
            'points_spent' => (float) $log->points_spent,
            'points_balance' => (float) ($log->customer?->loyaltyWallet?->points_balance ?? 0),
            'branch' => $log->branch,
        ];

        $this->form->fill([
            'branch_id' => (int) $data['branch_id'],
            'identifier' => (string) $customer->account_no,
            'reference_no' => '',
            'sale_amount' => null,
            'additional_discount_amount' => 0,
            'point_voucher_code' => null,
            'notes' => null,
        ]);

        Notification::make()
            ->title('تم تسجيل فاتورة الصالة')
            ->body(
                'الصافي: ' . number_format((float) $log->net_amount, 2)
                . ' — النقاط المضافة: ' . number_format((float) $log->points_earned, 2)
            )
            ->success()
            ->send();
    }

    public function clearCustomer(): void
    {
        $branchId = (int) ($this->data['branch_id'] ?? 0);

        if ($branchId < 1) {
            $branchId = (int) Branch::query()
                ->where('status', 'active')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->value('id');
        }

        $this->customerId = null;
        $this->workspaceCustomerCache = null;
        $this->lastSale = null;

        $this->form->fill([
            'branch_id' => $branchId ?: null,
            'identifier' => '',
            'reference_no' => '',
            'sale_amount' => null,
            'additional_discount_amount' => 0,
            'point_voucher_code' => null,
            'notes' => null,
        ]);

        $this->resetValidation();
    }

    /**
     * @return array<int, string>
     */
    protected function branchOptions(): array
    {
        return Branch::query()
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'name_ar', 'name_en', 'slug'])
            ->mapWithKeys(fn (Branch $branch): array => [
                (int) $branch->getKey() => $branch->name_ar ?: $branch->name_en ?: $branch->slug,
            ])
            ->all();
    }

    protected function workspaceCustomer(): ?Customer
    {
        if (! $this->customerId) {
            return null;
        }

        if ($this->workspaceCustomerCache?->getKey() === $this->customerId) {
            return $this->workspaceCustomerCache;
        }

        return $this->workspaceCustomerCache = Customer::query()
            ->with(['loyaltyWallet', 'retailGroups'])
            ->find($this->customerId);
    }

    protected function currentBranch(): ?Branch
    {
        $branchId = (int) ($this->data['branch_id'] ?? 0);

        return $branchId > 0
            ? Branch::query()->find($branchId)
            : null;
    }

    /**
     * @return Collection<int, PointVoucherRedemption>
     */
    protected function availableVouchers(): Collection
    {
        $customer = $this->workspaceCustomer();
        $branch = $this->currentBranch();

        if (! $customer instanceof Customer || ! $branch instanceof Branch) {
            return new Collection();
        }

        $branchNames = collect([
            $branch->name_ar,
            $branch->name_en,
            $branch->slug,
        ])->filter()
            ->map(fn ($value): string => trim((string) $value))
            ->values()
            ->all();

        return PointVoucherRedemption::query()
            ->with('voucher')
            ->where('customer_id', $customer->getKey())
            ->where('usage_method', 'in_store')
            ->where('status', 'available')
            ->whereNull('order_id')
            ->where(function ($query): void {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', now());
            })
            ->where(function ($query) use ($branchNames): void {
                $query->whereNull('branch')
                    ->orWhere('branch', '')
                    ->orWhereIn('branch', $branchNames);
            })
            ->orderBy('expires_at')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return array<string, string>
     */
    protected function voucherOptions(): array
    {
        return $this->availableVouchers()
            ->mapWithKeys(function (PointVoucherRedemption $redemption): array {
                $label = $redemption->order_no
                    . ' — ' . number_format((float) $redemption->voucher_value, 2);

                if ($redemption->expires_at) {
                    $label .= ' — حتى ' . $redemption->expires_at->format('Y-m-d');
                }

                return [(string) $redemption->order_no => $label];
            })
            ->all();
    }

    /**
     * @return array<string, float|bool>
     */
    protected function salePreview(): array
    {
        $saleAmount = max(0, (float) ($this->data['sale_amount'] ?? 0));
        $additionalDiscount = max(0, (float) ($this->data['additional_discount_amount'] ?? 0));
        $pointVoucherCode = trim((string) ($this->data['point_voucher_code'] ?? ''));

        $redemption = $pointVoucherCode !== ''
            ? $this->availableVouchers()->firstWhere('order_no', $pointVoucherCode)
            : null;

        $voucherDiscount = $redemption instanceof PointVoucherRedemption
            ? min($saleAmount, max(0, (float) $redemption->voucher_value))
            : 0.0;

        $totalDiscount = round($additionalDiscount + $voucherDiscount, 2);
        $discountIsValid = $totalDiscount <= $saleAmount;
        $netAmount = round(max(0, $saleAmount - $totalDiscount), 2);
        $setting = CustomerLoyaltySetting::query()->first();
        $pointsRate = $setting?->enabled
            ? max(0, (float) $setting->points_per_currency)
            : 0.0;

        return [
            'sale_amount' => round($saleAmount, 2),
            'voucher_discount' => round($voucherDiscount, 2),
            'additional_discount' => round($additionalDiscount, 2),
            'total_discount' => $totalDiscount,
            'net_amount' => $netAmount,
            'points_rate' => $pointsRate,
            'points_earned' => round($netAmount * $pointsRate, 2),
            'discount_is_valid' => $discountIsValid,
            'loyalty_enabled' => (bool) ($setting?->enabled ?? false),
        ];
    }

    protected function formattedPreviewValue(string $key): string
    {
        return number_format((float) ($this->salePreview()[$key] ?? 0), 2);
    }
}
