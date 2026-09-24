<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class TenantNas extends Model
{
    use HasFactory;

    protected $table = 'tenant_nas';

    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
        'ports' => 'integer',
        'coa_port' => 'integer',
        'last_auth_at' => 'datetime',
        'last_accounting_at' => 'datetime',
    ];

    /**
     * Set the RADIUS secret (encrypted with vault encryption).
     */
    public function setSecretAttribute($value): void
    {
        $this->attributes['secret'] = !empty($value) ? Crypt::encryptString($value) : null;
    }

    /**
     * Get the decrypted RADIUS secret.
     */
    public function getDecryptedSecretAttribute(): ?string
    {
        try {
            return !empty($this->attributes['secret']) ? Crypt::decryptString($this->attributes['secret']) : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Belongs to an ISP Tenant.
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Optional link to a physical MikroTik Router gateway in the fleet.
     */
    public function router()
    {
        return $this->belongsTo(TenantRouter::class, 'router_id');
    }
}
