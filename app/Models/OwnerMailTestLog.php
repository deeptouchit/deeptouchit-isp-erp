<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OwnerMailTestLog extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'tested_at' => 'datetime',
    ];
}
