<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantAutomationSetting extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'auto_billing_enabled' => 'boolean',
        'auto_send_bill_sms' => 'boolean',
        'auto_cut_enabled' => 'boolean',
        'auto_send_cut_sms' => 'boolean',
        'auto_reconnect_enabled' => 'boolean',
        'auto_send_restore_sms' => 'boolean',
        'expiry_reminders_enabled' => 'boolean',
        'auto_backup_enabled' => 'boolean',
        'auto_backup_email_enabled' => 'boolean',
        'min_due_threshold' => 'float',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
