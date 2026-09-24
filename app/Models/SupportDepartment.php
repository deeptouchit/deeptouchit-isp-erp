<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportDepartment extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'email',
        'description',
        'assigned_staff_ids',
        'is_client_selectable',
        'is_active',
        'sla_response_hours',
        'sort_order',
    ];

    protected $casts = [
        'assigned_staff_ids' => 'array',
        'is_client_selectable' => 'boolean',
        'is_active' => 'boolean',
        'sla_response_hours' => 'integer',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function ($dept) {
            if (empty($dept->slug) && !empty($dept->name)) {
                $dept->slug = \Illuminate\Support\Str::slug($dept->name);
            }
        });
    }

    public function tickets()
    {
        return $this->hasMany(SupportTicket::class, 'department', 'slug');
    }
}
