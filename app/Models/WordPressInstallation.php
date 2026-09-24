<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WordPressInstallation extends Model
{
    use HasFactory;

    protected $table = 'wordpress_installations';

    protected $fillable = [
        'user_id',
        'subscription_id',
        'website_id',
        'site_title',
        'domain',
        'install_path',
        'version',
        'admin_username',
        'admin_email',
        'db_name',
        'db_user',
        'db_prefix',
        'php_version',
        'ssl_enabled',
        'auto_update_core',
        'auto_update_plugins',
        'auto_update_themes',
        'maintenance_mode',
        'status',
    ];

    protected $casts = [
        'ssl_enabled' => 'boolean',
        'auto_update_core' => 'boolean',
        'auto_update_plugins' => 'boolean',
        'auto_update_themes' => 'boolean',
        'maintenance_mode' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }
}
