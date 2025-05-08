<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $fillable = [
        'unique_key',
        'item_code',
        'piece_price',
    ];

    protected $casts = [
        'piece_price' => 'decimal:2',
    ];
}
