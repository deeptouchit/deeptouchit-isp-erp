<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\AuditLoggable;

class Subscription extends Model
{
    use AuditLoggable;
    
    protected $fillable = [
        'user_id', 'plan_id', 'server_id', 'domain', 'username',
        'document_root', 'php_version', 'custom_disk_space', 'custom_inodes',
        'status', 'period', 'price', 'next_billing_date', 'suspended_at',
        'expires_at', 'cancelled_at'
    ];
    
    protected $casts = [
        'next_billing_date' => 'date',
        'expires_at' => 'datetime',
        'suspended_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'price' => 'decimal:2'
    ];
    
    protected static function booted()
    {
        static::creating(function ($subscription) {
            $subscription->username = self::generateUsername($subscription->domain);
            $subscription->document_root = "/var/www/vhosts/{$subscription->username}/{$subscription->domain}/public_html";
        });
    }
    
    protected static function generateUsername($domain)
    {
        $base = explode('.', $domain)[0];
        $username = strtolower(preg_replace('/[^a-z0-9]/', '', $base));
        $original = $username;
        $counter = 1;
        
        while (self::where('username', $username)->exists()) {
            $username = $original . $counter;
            $counter++;
        }
        
        return $username;
    }
    
    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function plan()
    {
        return $this->belongsTo(HostingPlan::class);
    }
    
    public function server()
    {
        return $this->belongsTo(Server::class);
    }
    
    public function websites()
    {
        return $this->hasMany(Website::class);
    }

    public function domainAliases()
    {
        return $this->hasMany(DomainAlias::class);
    }
    
    public function databases()
    {
        return $this->hasMany(Database::class);
    }
    
    public function emailAccounts()
    {
        return $this->hasMany(EmailAccount::class);
    }
    
    public function ftpAccounts()
    {
        return $this->hasMany(FtpAccount::class);
    }
    
    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
    
    public function backupJobs()
    {
        return $this->hasMany(BackupJob::class);
    }
    
    public function resellerSales()
    {
        return $this->hasMany(ResellerSale::class);
    }
    
    // Accessors
    public function getDiskUsageAttribute()
    {
        $path = $this->document_root;
        if (!is_dir($path)) return 0;
        
        $size = 0;
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($files as $file) {
            $size += $file->getSize();
        }
        return round($size / 1024 / 1024, 2);
    }
    
    public function getDiskUsagePercentageAttribute()
    {
        $usage = $this->disk_usage;
        $limit = $this->plan->disk_space;
        return $limit > 0 ? round(($usage / $limit) * 100, 2) : 0;
    }
    
    public function getIsActiveAttribute()
    {
        return $this->status === 'active' && $this->expires_at > now();
    }
    
    public function getIsSuspendedAttribute()
    {
        return $this->status === 'suspended';
    }
    
    public function getIsExpiredAttribute()
    {
        return $this->status === 'expired' || $this->expires_at < now();
    }
    
    // Methods
    public function suspend($reason = null)
    {
        $this->update([
            'status' => 'suspended',
            'suspended_at' => now()
        ]);
        
        $this->logActivity('subscription_suspended', [
            'reason' => $reason,
            'subscription_id' => $this->id
        ]);
    }
    
    public function unsuspend()
    {
        $this->update([
            'status' => 'active',
            'suspended_at' => null
        ]);
        
        $this->logActivity('subscription_unsuspended', [
            'subscription_id' => $this->id
        ]);
    }
    
    public function cancel()
    {
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now()
        ]);
        
        $this->logActivity('subscription_cancelled', [
            'subscription_id' => $this->id
        ]);
    }
    
    public function renew()
    {
        $this->update([
            'expires_at' => $this->expires_at->addMonth(),
            'status' => 'active'
        ]);
        
        $this->logActivity('subscription_renewed', [
            'subscription_id' => $this->id,
            'new_expiry' => $this->expires_at
        ]);
    }
}
