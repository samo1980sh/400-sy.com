<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_no',
        'username',
        'country',
        'account_type',
        'name',
        'mobile',
        'secondary_mobile',
        'email',
        'password',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }
}
