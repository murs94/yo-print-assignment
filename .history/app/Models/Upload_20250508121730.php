<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Upload extends Model
{
    protected $fillable = ['filename', 'path', 'status', 'uploaded_at'];

    protected $casts = [
        'uploaded_at' => 'datetime',
    ];
}
