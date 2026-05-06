<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeasurementChart extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'measurement_chart_group_id',
        'name',
        'size_code',
        'chest',
        'shoulder',
        'waist',
        'length',
        'sleeve',
        'collar',
        'inside_leg',
        'waistline',
        'thigh_width',
        'leg_width',
        'leg_length',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(MeasurementChartGroup::class, 'measurement_chart_group_id');
    }
}
