<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use App\Models\MeasurementChart;
use App\Models\MeasurementChartGroup;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'model_no',
        'category_id',
        'title_ar',
        'title_en',
        'description_ar',
        'description_en',
        'price',
        'compare_price',
        'country',
        'structure',
        'structure_color_id',
        'collection',
        'body_fit',
        'drop_type',
        'currency_ar',
        'currency_en',
        'visibility_targets',
        'show_web',
        'show_app',
        'show_retail',
        'show_wholesale',
        'is_best_seller',
        'is_new',
        'is_special_offer',
        'display_channels',
        'measurement_group',
        'is_active',
        'deleted_by',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'compare_price' => 'decimal:2',
        'is_best_seller' => 'boolean',
        'is_new' => 'boolean',
        'is_special_offer' => 'boolean',
        'show_web' => 'boolean',
        'show_app' => 'boolean',
        'show_retail' => 'boolean',
        'show_wholesale' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function productColors(): HasMany
    {
        return $this->hasMany(ProductColor::class)->orderBy('sort_order');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function structureColor(): BelongsTo
    {
        return $this->belongsTo(Color::class, 'structure_color_id');
    }

    public function measurementCharts(): HasMany
    {
        return $this->hasMany(MeasurementChart::class, 'name', 'measurement_group');
    }

    public function measurementChartGroup(): BelongsTo
    {
        return $this->belongsTo(MeasurementChartGroup::class, 'measurement_group', 'name');
    }

    public function wholesaleQuantities(): HasMany
    {
        return $this->hasMany(ProductWholesaleQuantity::class);
    }

    public function retailGroupAssignments(): HasMany
    {
        return $this->hasMany(ProductRetailGroupAssignment::class);
    }

    public function wholesaleGroupAssignments(): HasMany
    {
        return $this->hasMany(ProductWholesaleGroupAssignment::class);
    }

    public function wholesaleColors(): HasMany
    {
        return $this->hasMany(ProductWholesaleColor::class);
    }

    public function wholesaleSeries(): HasMany
    {
        return $this->hasMany(ProductWholesaleQuantity::class);
    }

    public function wholesaleAvailabilities(): HasMany
    {
        return $this->hasMany(ProductWholesaleAvailability::class);
    }

    public function complements(): HasMany
    {
        return $this->hasMany(ProductComplement::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(ProductDetail::class)->orderBy('sort_order');
    }

    public function scopeVisibleToFrontendVisitor(Builder $query): Builder
    {
        return $query
            ->where('show_web', true)
            ->where('show_retail', true);
    }

    public function isVisibleToFrontendVisitor(): bool
    {
        return (bool) $this->show_web
            && (bool) $this->show_retail
            && (bool) $this->is_active;
    }

    public function scopeVisibleOnWeb(Builder $query): Builder
    {
        return $query->where('show_web', true);
    }

    public function scopeVisibleOnApp(Builder $query): Builder
    {
        return $query->where('show_app', true);
    }

    public function scopeVisibleForRetail(Builder $query): Builder
    {
        return $query->where('show_retail', true);
    }

    public function scopeVisibleForWholesale(Builder $query): Builder
    {
        return $query->where('show_wholesale', true);
    }

    public function scopeVisibleForChannel(Builder $query, string $channel): Builder
    {
        return match ($channel) {
            'web' => $query->where('show_web', true),
            'app' => $query->where('show_app', true),
            default => $query,
        };
    }

    public function scopeVisibleForAccountType(Builder $query, string $accountType): Builder
    {
        return match ($accountType) {
            'retail' => $query->where('show_retail', true),
            'wholesale' => $query->where('show_wholesale', true),
            default => $query,
        };
    }

    public function scopeVisibleTo(Builder $query, ?string $channel = null, ?string $accountType = null): Builder
    {
        if ($channel !== null) {
            $query = $query->visibleForChannel($channel);
        }

        if ($accountType !== null) {
            $query = $query->visibleForAccountType($accountType);
        }

        return $query;
    }

    protected static function booted(): void
    {
        static::addGlobalScope('notDeleted', function (Builder $builder): void {
            $builder->whereNull('deleted_at');
        });

        static::creating(function (self $product): void {
            $product->slug = static::slugFromModelCode($product->model_no);
        });

        static::updating(function (self $product): void {
            $product->slug = static::slugFromModelCode($product->model_no);
        });
    }

    public static function slugFromModelCode(?string $modelNo): string
    {
        $slug = Str::slug(trim((string) $modelNo));

        if ($slug === '') {
            throw new \InvalidArgumentException('Product model code is required to generate the product URL.');
        }

        return $slug;
    }
}
