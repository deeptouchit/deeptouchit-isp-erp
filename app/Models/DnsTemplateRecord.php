<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DnsTemplateRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'dns_template_id',
        'name',
        'type',
        'content',
        'ttl',
        'priority',
        'port',
        'weight',
    ];

    protected $casts = [
        'ttl' => 'integer',
        'priority' => 'integer',
        'port' => 'integer',
        'weight' => 'integer',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(DnsTemplate::class, 'dns_template_id');
    }
}
