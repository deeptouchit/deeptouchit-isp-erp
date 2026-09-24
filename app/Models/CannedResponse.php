<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CannedResponse extends Model
{
    protected $fillable = [
        'title',
        'category',
        'shortcut_code',
        'content',
        'usage_count',
        'is_shared',
        'created_by',
        'sort_order',
    ];

    protected $casts = [
        'usage_count' => 'integer',
        'is_shared' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
