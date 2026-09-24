<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Website extends Model
{
    protected $fillable = [
        'subscription_id', 'domain', 'subdomain', 'document_root',
        'php_version', 'ssl_status', 'ssl_expires_at', 'ssl_last_renewed_at',
        'auto_ssl', 'is_primary', 'status',
        'cdn_enabled', 'cdn_dev_mode', 'cdn_always_online', 'cdn_brotli',
        'cdn_waf_enabled', 'cdn_cache_level', 'cdn_browser_ttl'
    ];
    
    protected $casts = [
        'ssl_expires_at' => 'datetime',
        'ssl_last_renewed_at' => 'datetime',
        'auto_ssl' => 'boolean',
        'is_primary' => 'boolean',
        'cdn_enabled' => 'boolean',
        'cdn_dev_mode' => 'boolean',
        'cdn_always_online' => 'boolean',
        'cdn_brotli' => 'boolean',
        'cdn_waf_enabled' => 'boolean',
        'cdn_browser_ttl' => 'integer',
    ];
    
    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }
}
