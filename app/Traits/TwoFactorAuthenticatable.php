<?php
namespace App\Traits;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

trait TwoFactorAuthenticatable
{
    public function hasTwoFactorEnabled(): bool
    {
        return !is_null($this->two_factor_secret) && !is_null($this->two_factor_confirmed_at);
    }
    
    public function twoFactorQrCodeUrl(): string
    {
        $label = $this->email ?? $this->username;
        $secret = $this->two_factor_secret;
        $issuer = config('app.name');
        
        return "otpauth://totp/{$issuer}:{$label}?secret={$secret}&issuer={$issuer}";
    }
    
    public function twoFactorRecoveryCodes(): array
    {
        return json_decode($this->two_factor_recovery_codes, true) ?? [];
    }
    
    public function replaceRecoveryCodes(array $codes): void
    {
        $this->forceFill([
            'two_factor_recovery_codes' => json_encode($codes),
        ])->save();
    }
    
    public function confirmTwoFactor(string $code): bool
    {
        if (!hash_equals($this->two_factor_secret, $code)) {
            return false;
        }
        
        $this->forceFill([
            'two_factor_confirmed_at' => now(),
        ])->save();
        
        return true;
    }
}
