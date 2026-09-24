<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    /**
     * Get a setting value with default fallback
     */
    public static function get(string $key, $default = null, ?int $tenantId = null)
    {
        $cacheKey = "setting_{$tenantId}_{$key}";

        return Cache::remember($cacheKey, 3600, function () use ($key, $default, $tenantId) {
            $setting = static::where('key', $key)
                ->where('tenant_id', $tenantId)
                ->first();

            if (!$setting && $tenantId !== null) {
                // Fallback to global setting
                $setting = static::where('key', $key)->whereNull('tenant_id')->first();
            }

            return $setting ? $setting->value : $default;
        });
    }

    /**
     * Set a setting value
     */
    public static function set(string $key, $value, string $group = 'general', ?int $tenantId = null): self
    {
        $cacheKey = "setting_{$tenantId}_{$key}";
        Cache::forget($cacheKey);

        return static::updateOrCreate(
            ['key' => $key, 'tenant_id' => $tenantId],
            ['value' => $value, 'group' => $group]
        );
    }

    /**
     * Get all settings grouped
     */
    public static function getGroup(string $group, ?int $tenantId = null): array
    {
        return static::where('group', $group)
            ->where('tenant_id', $tenantId)
            ->pluck('value', 'key')
            ->toArray();
    }
}
