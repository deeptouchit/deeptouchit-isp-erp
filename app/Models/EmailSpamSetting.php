<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailSpamSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'email_domain_id',
        'required_score',
        'rewrite_subject',
        'subject_tag',
        'auto_delete_score',
        'is_auto_delete_enabled',
        'whitelist',
        'blacklist',
        'bayesian_filter_enabled',
        'status',
    ];

    protected $casts = [
        'required_score' => 'float',
        'auto_delete_score' => 'float',
        'rewrite_subject' => 'boolean',
        'is_auto_delete_enabled' => 'boolean',
        'bayesian_filter_enabled' => 'boolean',
    ];

    public function emailDomain(): BelongsTo
    {
        return $this->belongsTo(EmailDomain::class);
    }
}
