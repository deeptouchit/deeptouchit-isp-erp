<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

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

            return $setting ? static::decodeValue($key, $setting->value) : $default;
        });
    }

    /**
     * Set a setting value
     */
    public static function set(string $key, $value, string $group = 'general', ?int $tenantId = null): self
    {
        $cacheKey = "setting_{$tenantId}_{$key}";
        Cache::forget($cacheKey);

        $storedValue = static::isSecureKey($key) && $value !== '' && !static::isEncryptedValue($value)
            ? Crypt::encryptString((string) $value)
            : $value;

        return static::updateOrCreate(
            ['key' => $key, 'tenant_id' => $tenantId],
            ['value' => $storedValue, 'group' => $group]
        );
    }

    /**
     * Get all settings grouped
     */
    public static function getGroup(string $group, ?int $tenantId = null): array
    {
        $values = static::where('group', $group)
            ->where('tenant_id', $tenantId)
            ->pluck('value', 'key')
            ->toArray();

        foreach ($values as $key => $value) {
            $values[$key] = static::decodeValue($key, $value);
        }

        return $values;
    }

    public static function isSecureKey(string $key): bool
    {
        return in_array($key, config('secure_settings.keys', []), true);
    }

    private static function decodeValue(string $key, $value)
    {
        if (!static::isSecureKey($key) || $value === null || $value === '') {
            return $value;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Throwable) {
            return $value;
        }
    }

    private static function isEncryptedValue($value): bool
    {
        try {
            Crypt::decryptString((string) $value);
            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
