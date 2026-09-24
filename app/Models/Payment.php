<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'invoice_id', 'user_id', 'transaction_id', 'gateway',
        'amount', 'currency', 'status', 'gateway_data',
        'refunded_at', 'paid_at'
    ];
    
    protected $casts = [
        'amount' => 'decimal:2',
        'gateway_data' => 'array',
        'refunded_at' => 'datetime',
        'paid_at' => 'datetime'
    ];
    
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
