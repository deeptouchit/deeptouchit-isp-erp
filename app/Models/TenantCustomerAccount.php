<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class TenantCustomerAccount extends Authenticatable
{
    use Notifiable;

    protected $table = 'tenant_customer_accounts';
    protected $guarded = ['id'];
    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'force_password_change' => 'boolean',
        ];
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function customer()
    {
        return $this->belongsTo(TenantCustomer::class, 'customer_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active'
            && $this->customer?->tenant_id === $this->tenant_id
            && !in_array($this->customer?->status, ['disabled'], true);
    }
}
