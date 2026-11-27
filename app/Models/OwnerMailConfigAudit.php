<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OwnerMailConfigAudit extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['changed_keys' => 'array'];
}
