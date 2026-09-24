<?php

namespace App\Services\Tenancy;

use App\Models\Tenant;
use Closure;

class TenantContext
{
    protected static ?Tenant $currentTenant = null;
    protected static bool $bypassScope = false;

    /**
     * Set the current active tenant in request context.
     */
    public static function setTenant(?Tenant $tenant): void
    {
        static::$currentTenant = $tenant;
    }

    /**
     * Get the current active tenant.
     */
    public static function getTenant(): ?Tenant
    {
        return static::$currentTenant;
    }

    /**
     * Get the active tenant ID if present.
     */
    public static function getTenantId(): ?int
    {
        return static::$currentTenant?->id;
    }

    /**
     * Check if a tenant context is actively loaded.
     */
    public static function hasTenant(): bool
    {
        return static::$currentTenant !== null;
    }

    /**
     * Check if the tenant global scope is bypassed.
     */
    public static function isBypassed(): bool
    {
        return static::$bypassScope;
    }

    /**
     * Run a given callback inside a specific tenant context.
     */
    public static function runInTenantContext(Tenant $tenant, Closure $callback): mixed
    {
        $previousTenant = static::$currentTenant;
        static::setTenant($tenant);

        try {
            return $callback($tenant);
        } finally {
            static::setTenant($previousTenant);
        }
    }

    /**
     * Execute a closure bypassing tenant scope entirely.
     */
    public static function bypassTenantScope(Closure $callback): mixed
    {
        $previousState = static::$bypassScope;
        static::$bypassScope = true;

        try {
            return $callback();
        } finally {
            static::$bypassScope = $previousState;
        }
    }

    /**
     * Clear the current tenant context.
     */
    public static function clear(): void
    {
        static::$currentTenant = null;
        static::$bypassScope = false;
    }
}
