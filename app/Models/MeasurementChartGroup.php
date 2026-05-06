<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MeasurementChartGroup extends Model
{
    protected $fillable = [
        'name',
        'guide_image',
    ];

    public function charts(): HasMany
    {
        return $this->hasMany(MeasurementChart::class);
    }

    protected static function booted(): void
    {
        static::saved(function (self $group): void {
            $group->charts()->update(['name' => $group->name]);
        });
    }
}
