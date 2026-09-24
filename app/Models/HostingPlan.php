<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HostingPlan extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'disk_space', 'bandwidth',
        'max_domains', 'max_subdomains', 'max_databases', 'max_email_accounts',
        'max_ftp_accounts', 'cpu_limit', 'ram_limit', 'php_version_default',
        'allow_custom_php_ini', 'allow_ssh_access', 'allow_git_deploy',
        'allow_redis', 'redis_memory_mb', 'allow_memcached',
        'allow_nodejs', 'allow_python', 'allow_cron_jobs', 'allow_backups',
        'auto_ssl', 'price_monthly', 'price_yearly', 'setup_fee',
        'sort_order', 'is_active'
    ];
    
    protected $casts = [
        'price_monthly' => 'decimal:2',
        'price_yearly' => 'decimal:2',
        'setup_fee' => 'decimal:2',
        'redis_memory_mb' => 'integer',
        'allow_custom_php_ini' => 'boolean',
        'allow_ssh_access' => 'boolean',
        'allow_git_deploy' => 'boolean',
        'allow_redis' => 'boolean',
        'allow_memcached' => 'boolean',
        'allow_nodejs' => 'boolean',
        'allow_python' => 'boolean',
        'allow_cron_jobs' => 'boolean',
        'allow_backups' => 'boolean',
        'auto_ssl' => 'boolean',
        'is_active' => 'boolean'
    ];
    
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class, 'plan_id');
    }
    
    public function getPriceForPeriod($period)
    {
        return $period === 'monthly' ? $this->price_monthly : $this->price_yearly;
    }
    
    public function getFormattedPriceAttribute()
    {
        return number_format($this->price_monthly, 2) . ' BDT';
    }
}
