<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerPaymentIntent extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['amount' => 'decimal:4'];

    public function tenant() { return $this->belongsTo(Tenant::class); }
    public function customer() { return $this->belongsTo(TenantCustomer::class); }
    public function invoice() { return $this->belongsTo(TenantCustomerInvoice::class, 'invoice_id'); }
}
