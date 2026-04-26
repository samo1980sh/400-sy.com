<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportBatch extends Model
{
    /** @use HasFactory<\Database\Factories\ImportBatchFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'type',
        'source_file',
        'status',
        'started_at',
        'finished_at',
        'created_by',
        'note',
    ];

    public function rows(): HasMany
    {
        return $this->hasMany(ImportRow::class, 'batch_id');
    }
}
