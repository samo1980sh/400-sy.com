<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeasurementChart extends Model
{
    public $timestamps = false;

    protected $fillable = [
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
}
