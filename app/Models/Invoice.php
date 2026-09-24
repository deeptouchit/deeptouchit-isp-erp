<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'invoice_no', 'user_id', 'subscription_id',
        'total_amount', 'tax_amount', 'discount_amount',
        'paid_amount', 'due_amount', 'currency',
        'status', 'issue_date', 'due_date', 'paid_at', 'notes'
    ];
    
    protected $casts = [
        'total_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_amount' => 'decimal:2',
        'issue_date' => 'date',
        'due_date' => 'date',
        'paid_at' => 'datetime'
    ];

    protected $appends = [
        'formatted_issue_date',
        'formatted_due_date',
        'formatted_paid_at'
    ];

    public function getFormattedIssueDateAttribute(): ?string
    {
        return $this->issue_date ? $this->issue_date->format('M d, Y') : null;
    }

    public function getFormattedDueDateAttribute(): ?string
    {
        return $this->due_date ? $this->due_date->format('M d, Y') : null;
    }

    public function getFormattedPaidAtAttribute(): ?string
    {
        return $this->paid_at ? $this->paid_at->format('M d, Y') : null;
    }
    
    protected static function booted()
    {
        static::creating(function ($invoice) {
            if (empty($invoice->invoice_no)) {
                $prefix = config('panel.billing.invoice_prefix', 'INV-');
                $year = now()->format('Y');
                $month = now()->format('m');
                $last = self::whereYear('created_at', $year)
                            ->whereMonth('created_at', $month)
                            ->orderBy('id', 'desc')
                            ->first();
                $number = $last ? intval(substr($last->invoice_no, -4)) + 1 : 1;
                $invoice->invoice_no = $prefix . $year . $month . str_pad($number, 4, '0', STR_PAD_LEFT);
            }
        });
    }
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }
    
    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }
    
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
