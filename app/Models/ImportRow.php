<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportRow extends Model
{
    /** @use HasFactory<\Database\Factories\ImportRowFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'batch_id',
        'row_number',
        'raw_payload',
        'status',
        'error_message',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class, 'batch_id');
    }
}
