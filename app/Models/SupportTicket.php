<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportTicket extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'last_reply_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function messages()
    {
        return $this->hasMany(TicketMessage::class)->orderBy('created_at', 'asc');
    }

    public function publicMessages()
    {
        return $this->hasMany(TicketMessage::class)->where('is_internal_note', false)->orderBy('created_at', 'asc');
    }

    public function latestMessage()
    {
        return $this->hasOne(TicketMessage::class)->latestOfMany();
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['open', 'in_progress', 'answered']);
    }

    public function scopeClosed($query)
    {
        return $query->whereIn('status', ['resolved', 'closed']);
    }

    public function scopeUrgent($query)
    {
        return $query->where('priority', 'urgent');
    }

    public function isUrgent(): bool
    {
        return $this->priority === 'urgent';
    }

    public function isOpen(): bool
    {
        return in_array($this->status, ['open', 'in_progress', 'answered']);
    }

    public function getPriorityBadgeColorAttribute(): string
    {
        return match ($this->priority) {
            'urgent' => 'bg-rose-100 text-rose-700 border-rose-200',
            'high' => 'bg-amber-100 text-amber-800 border-amber-200',
            'medium' => 'bg-blue-100 text-blue-700 border-blue-200',
            'low' => 'bg-slate-100 text-slate-700 border-slate-200',
            default => 'bg-slate-100 text-slate-700 border-slate-200',
        };
    }

    public function getStatusBadgeColorAttribute(): string
    {
        return match ($this->status) {
            'open' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
            'in_progress' => 'bg-indigo-100 text-indigo-800 border-indigo-200',
            'answered' => 'bg-purple-100 text-purple-800 border-purple-200',
            'resolved' => 'bg-teal-100 text-teal-800 border-teal-200',
            'closed' => 'bg-slate-100 text-slate-600 border-slate-200',
            default => 'bg-slate-100 text-slate-700 border-slate-200',
        };
    }

    public function getDepartmentNameAttribute(): string
    {
        return match ($this->department) {
            'billing' => 'Billing & Accounts',
            'technical' => 'Technical & Server',
            'sms_gateway' => 'SMS Gateway / API',
            'payment_gateway' => 'Payment Gateway (PGW)',
            default => 'General Support',
        };
    }
}
