<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Announcement extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'type',
        'severity',
        'target_audience',
        'summary',
        'content',
        'is_published',
        'is_pinned',
        'show_banner',
        'views_count',
        'published_at',
        'created_by',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'is_pinned' => 'boolean',
        'show_banner' => 'boolean',
        'views_count' => 'integer',
        'published_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function ($announcement) {
            if (empty($announcement->slug) && !empty($announcement->title)) {
                $announcement->slug = Str::slug($announcement->title);
            }
        });
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
