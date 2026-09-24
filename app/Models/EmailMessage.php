<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'email_account_id',
        'folder',
        'from_name',
        'from_email',
        'to',
        'cc',
        'bcc',
        'subject',
        'snippet',
        'body',
        'is_read',
        'is_starred',
        'has_attachments',
        'attachments',
        'size_kb',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'is_starred' => 'boolean',
        'has_attachments' => 'boolean',
        'attachments' => 'array',
        'size_kb' => 'integer',
    ];

    public function emailAccount(): BelongsTo
    {
        return $this->belongsTo(EmailAccount::class);
    }
}
