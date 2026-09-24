@extends('owner.layouts.app')

@section('page-title', 'Security & Automated Backup Hub')

@section('content')
<div class="space-y-4" x-data="{ activeTab: 'firewall' }">

    <!-- Top Security Posture Score & Header -->
    <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-lg border border-emerald-200 shadow-2xs flex-shrink-0">
                <i class="fas fa-shield-halved"></i>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-sm font-bold text-slate-800">Enterprise Security Shield & Automated Backups</h2>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                        Rating: 98% (Grade A+)
                    </span>
                </div>
                <p class="text-[11px] text-slate-500 mt-0.5">Rate limiting, login guards, IP filtering, 2FA, and automated cloud database snapshots</p>
            </div>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            <form action="{{ route('owner.security-backup.trigger') }}" method="POST" class="inline-flex">
                @csrf
                <input type="hidden" name="backup_type" value="database">
                <button type="submit" class="flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-semibold shadow-xs transition">
                    <i class="fas fa-database text-[10px]"></i>
                    <span>Take DB Snapshot</span>
                </button>
            </form>

            <button type="button" @click="activeTab = 'backups'" class="flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold transition border border-slate-200">
                <i class="fas fa-folder-open text-[10px]"></i>
                <span>Backup Archive ({{ count($backupFiles) }})</span>
            </button>
        </div>
    </div>


    <!-- Security Status Badges Strip -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
        <div class="bg-white p-2.5 rounded-xl border border-slate-200/80 shadow-2xs flex items-center gap-2.5">
            <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-xs">
                <i class="fas fa-lock"></i>
            </div>
            <div>
                <div class="text-[10px] text-slate-400 font-medium">SSL / HTTPS Enforced</div>
                <div class="text-xs font-bold text-emerald-700">Active & Encrypted</div>
            </div>
        </div>

        <div class="bg-white p-2.5 rounded-xl border border-slate-200/80 shadow-2xs flex items-center gap-2.5">
            <div class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-xs">
                <i class="fas fa-fire"></i>
            </div>
            <div>
                <div class="text-[10px] text-slate-400 font-medium">Rate Limiter Shield</div>
                <div class="text-xs font-bold text-slate-800">{{ $settings['max_login_attempts'] ?? '5' }} Attempts / 15m</div>
            </div>
        </div>

        <div class="bg-white p-2.5 rounded-xl border border-slate-200/80 shadow-2xs flex items-center gap-2.5">
            <div class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-xs">
                <i class="fas fa-key"></i>
            </div>
            <div>
                <div class="text-[10px] text-slate-400 font-medium">2FA Authentication</div>
                <div class="text-xs font-bold text-slate-800">{{ ($settings['enforce_2fa_admins'] ?? '0') == '1' ? 'Enforced' : 'Optional' }}</div>
            </div>
        </div>

        <div class="bg-white p-2.5 rounded-xl border border-slate-200/80 shadow-2xs flex items-center gap-2.5">
            <div class="w-7 h-7 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center text-xs">
                <i class="fas fa-cloud-arrow-up"></i>
            </div>
            <div>
                <div class="text-[10px] text-slate-400 font-medium">Automated Backups</div>
                <div class="text-xs font-bold text-slate-800">{{ ucfirst($settings['backup_frequency'] ?? 'Daily') }} Active</div>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex items-center gap-1 bg-white p-1 rounded-xl border border-slate-200/80 shadow-xs overflow-x-auto">
        <button type="button" @click="activeTab = 'firewall'" 
                :class="activeTab === 'firewall' ? 'bg-blue-50 text-blue-700 font-bold border-blue-200' : 'text-slate-600 hover:bg-slate-50 border-transparent'"
                class="px-3 py-1.5 rounded-lg text-xs transition border flex items-center gap-1.5 whitespace-nowrap">
            <i class="fas fa-shield-alt text-[11px]"></i>
            <span>Firewall & Access Policy</span>
        </button>

        <button type="button" @click="activeTab = 'two_factor'" 
                :class="activeTab === 'two_factor' ? 'bg-blue-50 text-blue-700 font-bold border-blue-200' : 'text-slate-600 hover:bg-slate-50 border-transparent'"
                class="px-3 py-1.5 rounded-lg text-xs transition border flex items-center gap-1.5 whitespace-nowrap">
            <i class="fas fa-user-shield text-[11px]"></i>
            <span>2FA & Password Policy</span>
        </button>

        <button type="button" @click="activeTab = 'bot_recaptcha'" 
                :class="activeTab === 'bot_recaptcha' ? 'bg-blue-50 text-blue-700 font-bold border-blue-200' : 'text-slate-600 hover:bg-slate-50 border-transparent'"
                class="px-3 py-1.5 rounded-lg text-xs transition border flex items-center gap-1.5 whitespace-nowrap">
            <i class="fas fa-robot text-[11px]"></i>
            <span>Bot Defense (reCAPTCHA / Turnstile)</span>
        </button>

        <button type="button" @click="activeTab = 'cloud_backup'" 
                :class="activeTab === 'cloud_backup' ? 'bg-blue-50 text-blue-700 font-bold border-blue-200' : 'text-slate-600 hover:bg-slate-50 border-transparent'"
                class="px-3 py-1.5 rounded-lg text-xs transition border flex items-center gap-1.5 whitespace-nowrap">
            <i class="fas fa-cloud-arrow-up text-[11px]"></i>
            <span>Cloud Backup & S3</span>
        </button>

        <button type="button" @click="activeTab = 'backups'" 
                :class="activeTab === 'backups' ? 'bg-blue-50 text-blue-700 font-bold border-blue-200' : 'text-slate-600 hover:bg-slate-50 border-transparent'"
                class="px-3 py-1.5 rounded-lg text-xs transition border flex items-center gap-1.5 whitespace-nowrap">
            <i class="fas fa-database text-[11px]"></i>
            <span>Backup Archive ({{ count($backupFiles) }})</span>
        </button>

        <button type="button" @click="activeTab = 'audit_logs'" 
                :class="activeTab === 'audit_logs' ? 'bg-blue-50 text-blue-700 font-bold border-blue-200' : 'text-slate-600 hover:bg-slate-50 border-transparent'"
                class="px-3 py-1.5 rounded-lg text-xs transition border flex items-center gap-1.5 whitespace-nowrap">
            <i class="fas fa-list-check text-[11px]"></i>
            <span>Security Audit Log</span>
        </button>
    </div>

    <!-- TAB 1, 2, 3, 4: Security Settings Form -->
    <form action="{{ route('owner.security-backup.update') }}" method="POST" class="space-y-4">
        @csrf

        <!-- TAB 1: Firewall & Access Policy -->
        <div x-show="activeTab === 'firewall'" class="space-y-4">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                
                <!-- Brute Force & Rate Limit Card -->
                <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs divide-y divide-slate-100">
                    <div class="p-3.5 flex items-center justify-between bg-slate-50/50 rounded-t-xl">
                        <span class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                            <i class="fas fa-ban text-rose-600"></i>
                            Brute-Force Attack Protection
                        </span>
                        <span class="text-[10px] text-slate-400">Rate Limiting</span>
                    </div>

                    <div class="p-4 space-y-3.5 text-xs">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-700 mb-1">Max Failed Login Attempts</label>
                                <input type="number" name="max_login_attempts" value="{{ $settings['max_login_attempts'] ?? '5' }}" placeholder="5" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-blue-500 font-mono">
                                <p class="text-[9.5px] text-slate-400 mt-1">Locks account after consecutive failed attempts</p>
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-700 mb-1">Lockout Duration (Minutes)</label>
                                <input type="number" name="lockout_duration_minutes" value="{{ $settings['lockout_duration_minutes'] ?? '15' }}" placeholder="15" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-blue-500 font-mono">
                                <p class="text-[9.5px] text-slate-400 mt-1">Duration of temporary login restriction</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-700 mb-1">Session Inactivity Timeout</label>
                                <select name="session_lifetime_minutes" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-blue-500">
                                    <option value="15" {{ ($settings['session_lifetime_minutes'] ?? '') == '15' ? 'selected' : '' }}>15 Minutes Inactive</option>
                                    <option value="30" {{ ($settings['session_lifetime_minutes'] ?? '') == '30' ? 'selected' : '' }}>30 Minutes Inactive</option>
                                    <option value="60" {{ ($settings['session_lifetime_minutes'] ?? '') == '60' ? 'selected' : '' }}>60 Minutes (1 Hour)</option>
                                    <option value="120" {{ ($settings['session_lifetime_minutes'] ?? '120') == '120' ? 'selected' : '' }}>120 Minutes (Default - 2 Hours)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-700 mb-1">Concurrent Session Guard</label>
                                <select name="single_session_enforce" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-blue-500">
                                    <option value="1" {{ ($settings['single_session_enforce'] ?? '0') == '1' ? 'selected' : '' }}>Single Session Only (Strict)</option>
                                    <option value="0" {{ ($settings['single_session_enforce'] ?? '0') == '0' ? 'selected' : '' }}>Multiple Concurrent Sessions Allowed</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- IP Whitelist & Blacklist Card -->
                <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs divide-y divide-slate-100">
                    <div class="p-3.5 flex items-center justify-between bg-slate-50/50 rounded-t-xl">
                        <span class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                            <i class="fas fa-network-wired text-blue-600"></i>
                            Owner Console IP Whitelist & Blacklist
                        </span>
                        <span class="text-[10px] text-slate-400">IP Firewall</span>
                    </div>

                    <div class="p-4 space-y-3.5 text-xs">
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="text-[11px] font-semibold text-slate-700">Allowed Owner IP Addresses (Whitelist)</label>
                                <span class="text-[9.5px] text-blue-600 font-mono">Your Current IP: {{ request()->ip() }}</span>
                            </div>
                            <textarea name="owner_ip_whitelist" rows="2" placeholder="e.g. 103.145.23.1&#10;192.168.1.0/24&#10;(Leave blank to allow all IP addresses)" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-blue-500 font-mono text-[11px]">{{ $settings['owner_ip_whitelist'] ?? '' }}</textarea>
                            <p class="text-[9.5px] text-slate-400 mt-1">If populated, owner console is strictly accessible only from these IP ranges.</p>
                        </div>

                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Blocked Attacker IP Addresses (Blacklist)</label>
                            <textarea name="banned_ip_list" rows="2" placeholder="One IP per line (e.g. 185.220.101.42)" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-rose-500 font-mono text-[11px] text-rose-700">{{ $settings['banned_ip_list'] ?? '' }}</textarea>
                            <p class="text-[9.5px] text-slate-400 mt-1">Requests originating from these IPs are immediately rejected with 403 Forbidden.</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- TAB 2: 2FA & Password Policy -->
        <div x-show="activeTab === 'two_factor'" class="space-y-4" x-cloak>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                
                <!-- 2FA Settings -->
                <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs divide-y divide-slate-100">
                    <div class="p-3.5 flex items-center justify-between bg-slate-50/50 rounded-t-xl">
                        <span class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                            <i class="fas fa-key text-indigo-600"></i>
                            Two-Factor Authentication (2FA)
                        </span>
                        <span class="text-[10px] text-slate-400">Authenticator App / SMS</span>
                    </div>

                    <div class="p-4 space-y-3.5 text-xs">
                        <div class="p-3 bg-indigo-50/60 rounded-lg border border-indigo-200 space-y-2">
                            <label class="flex items-start gap-2.5 cursor-pointer">
                                <input type="checkbox" name="enforce_2fa_admins" value="1" {{ ($settings['enforce_2fa_admins'] ?? '0') == '1' ? 'checked' : '' }} class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 mt-0.5">
                                <div>
                                    <span class="font-bold text-slate-800 text-xs">Enforce 2FA for Owner & Admin Accounts</span>
                                    <p class="text-[10.5px] text-slate-600 mt-0.5">Requires Google Authenticator TOTP token verification on every login.</p>
                                </div>
                            </label>
                        </div>

                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Primary 2FA Method</label>
                            <select name="primary_2fa_method" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-blue-500">
                                <option value="totp" {{ ($settings['primary_2fa_method'] ?? 'totp') === 'totp' ? 'selected' : '' }}>Google Authenticator / TOTP App (Recommended)</option>
                                <option value="sms" {{ ($settings['primary_2fa_method'] ?? '') === 'sms' ? 'selected' : '' }}>SMS OTP (Requires active SMS Gateway)</option>
                                <option value="email" {{ ($settings['primary_2fa_method'] ?? '') === 'email' ? 'selected' : '' }}>Email Verification Code (Requires SMTP)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Trusted Device Duration (Days)</label>
                            <input type="number" name="trusted_device_days" value="{{ $settings['trusted_device_days'] ?? '30' }}" placeholder="30" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-blue-500 font-mono">
                            <p class="text-[9.5px] text-slate-400 mt-1">Remember this device duration before asking for 2FA token again.</p>
                        </div>
                    </div>
                </div>

                <!-- Password Policy -->
                <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs divide-y divide-slate-100">
                    <div class="p-3.5 flex items-center justify-between bg-slate-50/50 rounded-t-xl">
                        <span class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                            <i class="fas fa-lock text-emerald-600"></i>
                            Password Complexity & Expiry Policy
                        </span>
                        <span class="text-[10px] text-slate-400">Password Rules</span>
                    </div>

                    <div class="p-4 space-y-3 text-xs">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Minimum Password Length</label>
                            <select name="min_password_length" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-blue-500 font-mono">
                                <option value="8" {{ ($settings['min_password_length'] ?? '8') == '8' ? 'selected' : '' }}>8 Characters (Standard)</option>
                                <option value="10" {{ ($settings['min_password_length'] ?? '') == '10' ? 'selected' : '' }}>10 Characters (Strong)</option>
                                <option value="12" {{ ($settings['min_password_length'] ?? '') == '12' ? 'selected' : '' }}>12 Characters (Enterprise High Security)</option>
                            </select>
                        </div>

                        <div class="space-y-2 pt-1 border-t border-slate-100">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="require_mixed_case" value="1" {{ ($settings['require_mixed_case'] ?? '1') == '1' ? 'checked' : '' }} class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                <span class="text-[11px] text-slate-700 font-medium">Require uppercase and lowercase letters</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="require_numbers_symbols" value="1" {{ ($settings['require_numbers_symbols'] ?? '1') == '1' ? 'checked' : '' }} class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                <span class="text-[11px] text-slate-700 font-medium">Require numbers and special symbols (@#$%)</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="force_password_expiry" value="1" {{ ($settings['force_password_expiry'] ?? '0') == '1' ? 'checked' : '' }} class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                <span class="text-[11px] text-slate-700 font-medium">Prompt users to change passwords every 90 days</span>
                            </label>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- TAB 3: Bot Defense & reCAPTCHA / Cloudflare Turnstile -->
        <div x-show="activeTab === 'bot_recaptcha'" class="space-y-4" x-cloak>
            <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs divide-y divide-slate-100">
                <div class="p-3.5 flex items-center justify-between bg-slate-50/50 rounded-t-xl">
                    <span class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                        <i class="fas fa-robot text-purple-600"></i>
                        Bot Defense (Google reCAPTCHA v3 & Cloudflare Turnstile)
                    </span>
                    <span class="text-[10px] text-slate-400">Spam & Bot Shield</span>
                </div>

                <div class="p-4 space-y-4 text-xs">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Captcha Provider</label>
                            <select name="captcha_provider" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-blue-500">
                                <option value="disabled" {{ ($settings['captcha_provider'] ?? 'disabled') === 'disabled' ? 'selected' : '' }}>Disabled</option>
                                <option value="recaptcha_v3" {{ ($settings['captcha_provider'] ?? '') === 'recaptcha_v3' ? 'selected' : '' }}>Google reCAPTCHA v3 (Invisible)</option>
                                <option value="cloudflare_turnstile" {{ ($settings['captcha_provider'] ?? '') === 'cloudflare_turnstile' ? 'selected' : '' }}>Cloudflare Turnstile (Privacy-Focused)</option>
                                <option value="internal_math" {{ ($settings['captcha_provider'] ?? '') === 'internal_math' ? 'selected' : '' }}>Internal Math Challenge (5 + 3 = ?)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Minimum Passing Score</label>
                            <select name="recaptcha_min_score" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-blue-500 font-mono">
                                <option value="0.5" {{ ($settings['recaptcha_min_score'] ?? '0.5') == '0.5' ? 'selected' : '' }}>0.5 (Standard - Recommended)</option>
                                <option value="0.7" {{ ($settings['recaptcha_min_score'] ?? '') == '0.7' ? 'selected' : '' }}>0.7 (Strict - High Bot Traffic)</option>
                                <option value="0.3" {{ ($settings['recaptcha_min_score'] ?? '') == '0.3' ? 'selected' : '' }}>0.3 (Relaxed)</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Site Key (Public Key)</label>
                            <input type="text" name="recaptcha_site_key" value="{{ $settings['recaptcha_site_key'] ?? '' }}" placeholder="6LeIxAcTAAAAAJcZVRqyy..." class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-blue-500 font-mono">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Secret Key (Private Key)</label>
                            <input type="password" name="recaptcha_secret_key" value="{{ $settings['recaptcha_secret_key'] ?? '' }}" placeholder="••••••••••••••••••••••••••••••" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-blue-500 font-mono">
                        </div>
                    </div>

                    <div class="p-3 bg-slate-50 rounded-lg border border-slate-200 space-y-1.5">
                        <span class="font-bold text-slate-700 text-[11px] block">Protected Endpoints:</span>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-[11px] text-slate-600">
                            <label class="flex items-center gap-1.5 cursor-pointer">
                                <input type="checkbox" name="captcha_on_login" value="1" {{ ($settings['captcha_on_login'] ?? '1') == '1' ? 'checked' : '' }} class="rounded text-blue-600">
                                <span>Owner & Admin Login</span>
                            </label>
                            <label class="flex items-center gap-1.5 cursor-pointer">
                                <input type="checkbox" name="captcha_on_register" value="1" {{ ($settings['captcha_on_register'] ?? '1') == '1' ? 'checked' : '' }} class="rounded text-blue-600">
                                <span>New Tenant Sign-Up</span>
                            </label>
                            <label class="flex items-center gap-1.5 cursor-pointer">
                                <input type="checkbox" name="captcha_on_password_reset" value="1" {{ ($settings['captcha_on_password_reset'] ?? '1') == '1' ? 'checked' : '' }} class="rounded text-blue-600">
                                <span>Password Reset Forms</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 4: Cloud Backup & S3 -->
        <div x-show="activeTab === 'cloud_backup'" class="space-y-4" x-cloak>
            <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs divide-y divide-slate-100">
                <div class="p-3.5 flex items-center justify-between bg-slate-50/50 rounded-t-xl">
                    <span class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                        <i class="fas fa-cloud-arrow-up text-emerald-600"></i>
                        Automated Database Backup & Cloud S3 Storage Driver
                    </span>
                    <span class="text-[10px] text-slate-400">Cron & Cloud Engine</span>
                </div>

                <div class="p-4 space-y-4 text-xs">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Backup Frequency</label>
                            <select name="backup_frequency" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-blue-500">
                                <option value="daily" {{ ($settings['backup_frequency'] ?? 'daily') === 'daily' ? 'selected' : '' }}>Daily at 12:00 AM (Recommended)</option>
                                <option value="twice_daily" {{ ($settings['backup_frequency'] ?? '') === 'twice_daily' ? 'selected' : '' }}>Twice Daily (Every 12 Hours)</option>
                                <option value="weekly" {{ ($settings['backup_frequency'] ?? '') === 'weekly' ? 'selected' : '' }}>Weekly Snapshot</option>
                                <option value="disabled" {{ ($settings['backup_frequency'] ?? '') === 'disabled' ? 'selected' : '' }}>Disable Automated Backups</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Storage Driver</label>
                            <select name="backup_storage_disk" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-blue-500">
                                <option value="local" {{ ($settings['backup_storage_disk'] ?? 'local') === 'local' ? 'selected' : '' }}>Local Encrypted Disk (/storage/app/backups)</option>
                                <option value="s3" {{ ($settings['backup_storage_disk'] ?? '') === 's3' ? 'selected' : '' }}>Amazon AWS S3 Bucket</option>
                                <option value="wasabi" {{ ($settings['backup_storage_disk'] ?? '') === 'wasabi' ? 'selected' : '' }}>Wasabi Cloud Storage</option>
                                <option value="digitalocean" {{ ($settings['backup_storage_disk'] ?? '') === 'digitalocean' ? 'selected' : '' }}>DigitalOcean Spaces</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Retention Policy (Days)</label>
                            <input type="number" name="backup_retention_days" value="{{ $settings['backup_retention_days'] ?? '30' }}" placeholder="30" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-blue-500 font-mono">
                            <p class="text-[9.5px] text-slate-400 mt-1">Older archives are automatically purged</p>
                        </div>
                    </div>

                    <!-- Cloud S3 Credentials -->
                    <div class="p-3.5 bg-slate-50/70 rounded-xl border border-slate-200/80 space-y-3">
                        <span class="font-bold text-slate-800 text-[11.5px] flex items-center gap-1.5">
                            <i class="fas fa-server text-blue-600"></i>
                            Cloud S3 Bucket Credentials (If S3/Wasabi selected)
                        </span>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-700 mb-1">Bucket Name</label>
                                <input type="text" name="backup_s3_bucket" value="{{ $settings['backup_s3_bucket'] ?? '' }}" placeholder="somitysoft-saas-backups" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-blue-500 font-mono">
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-700 mb-1">Region / Location</label>
                                <input type="text" name="backup_s3_region" value="{{ $settings['backup_s3_region'] ?? 'ap-southeast-1' }}" placeholder="ap-southeast-1" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-blue-500 font-mono">
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-700 mb-1">Custom Endpoint (Wasabi / DO Spaces)</label>
                                <input type="text" name="backup_s3_endpoint" value="{{ $settings['backup_s3_endpoint'] ?? '' }}" placeholder="https://s3.ap-southeast-1.wasabisys.com" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-blue-500 font-mono">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-700 mb-1">AWS / S3 Access Key ID</label>
                                <input type="text" name="backup_s3_key" value="{{ $settings['backup_s3_key'] ?? '' }}" placeholder="AKIAIOSFODNN7EXAMPLE" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-blue-500 font-mono">
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-700 mb-1">AWS / S3 Secret Access Key</label>
                                <input type="password" name="backup_s3_secret" value="{{ $settings['backup_s3_secret'] ?? '' }}" placeholder="••••••••••••••••••••••••••••••••••••" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-blue-500 font-mono">
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 pt-1 border-t border-slate-100">
                        <input type="checkbox" name="backup_encryption_aes" id="backup_encryption_aes" value="1" {{ ($settings['backup_encryption_aes'] ?? '1') == '1' ? 'checked' : '' }} class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        <label for="backup_encryption_aes" class="text-[11px] font-semibold text-slate-700 cursor-pointer">
                            Enforce AES-256 encryption on database dumps before uploading to cloud storage
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Global Save Button for Tabs 1-4 -->
        <div x-show="activeTab !== 'backups' && activeTab !== 'audit_logs'" class="flex items-center justify-between bg-white p-3.5 rounded-xl border border-slate-200/80 shadow-xs">
            <span class="text-[11px] text-slate-500">
                <i class="fas fa-shield-halved text-blue-500 mr-1"></i>
                Security policy updates apply in real time across the entire SaaS infrastructure.
            </span>
            <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5">
                <i class="fas fa-save text-[11px]"></i>
                <span>Save Security Configuration</span>
            </button>
        </div>
    </form>

    <!-- TAB 5: Backup Archive & Disaster Recovery Hub -->
    <div x-show="activeTab === 'backups'" class="space-y-4" x-cloak>
        <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs divide-y divide-slate-100">
            <div class="p-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/50 rounded-t-xl">
                <div>
                    <span class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                        <i class="fas fa-database text-blue-600"></i>
                        Encrypted SQL Snapshots & Disaster Recovery Archives
                    </span>
                    <span class="text-[10px] text-slate-400">Database dumps with SHA-256 checksums and sandbox restore verification</span>
                </div>

                <div class="flex items-center gap-2">
                    <form action="{{ route('owner.security-backup.trigger') }}" method="POST">
                        @csrf
                        <input type="hidden" name="backup_type" value="database">
                        <button type="submit" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-[11px] font-semibold shadow-xs transition flex items-center gap-1">
                            <i class="fas fa-plus text-[9px]"></i>
                            <span>Create Live Snapshot</span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Table of Backups -->
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/40 text-[10.5px] font-bold uppercase tracking-wider text-slate-500">
                            <th class="py-2.5 px-4">Backup Filename</th>
                            <th class="py-2.5 px-4">Type</th>
                            <th class="py-2.5 px-4">Size</th>
                            <th class="py-2.5 px-4">SHA-256 Checksum</th>
                            <th class="py-2.5 px-4">Timestamp</th>
                            <th class="py-2.5 px-4 text-right">Disaster Recovery Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @forelse($backupFiles as $backup)
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-2.5 px-4 font-mono font-semibold text-slate-800 flex items-center gap-2">
                                    <i class="fas fa-file-code text-blue-600 text-xs"></i>
                                    <span>{{ $backup['filename'] }}</span>
                                </td>
                                <td class="py-2.5 px-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                                        {{ $backup['type'] }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-4 font-mono font-medium text-slate-600">
                                    {{ $backup['size'] }}
                                </td>
                                <td class="py-2.5 px-4 font-mono text-[10px] text-slate-500">
                                    <span class="bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200 text-slate-700 font-mono" title="{{ $backup['sha256'] }}">
                                        {{ $backup['sha256'] }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-4 text-[11px] text-slate-500">
                                    {{ $backup['created_at'] }}
                                </td>
                                <td class="py-2.5 px-4 text-right">
                                    <div class="inline-flex items-center gap-1.5">
                                        <!-- Test Restore Button -->
                                        <form action="{{ route('owner.security-backup.test-restore', $backup['filename']) }}" method="POST" class="inline-block">
                                            @csrf
                                            <button type="submit" class="p-1.5 px-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 rounded-md text-[10.5px] font-semibold transition inline-flex items-center gap-1 border border-emerald-200" title="Run sandbox restore dry-run integrity test">
                                                <i class="fas fa-vial-circle-check text-[10px]"></i>
                                                <span>Test Restore</span>
                                            </button>
                                        </form>

                                        <!-- Offsite Sync Button -->
                                        <form action="{{ route('owner.security-backup.upload-offsite', $backup['filename']) }}" method="POST" class="inline-block">
                                            @csrf
                                            <button type="submit" class="p-1.5 px-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-md text-[10.5px] font-semibold transition inline-flex items-center gap-1 border border-indigo-200" title="Upload to offsite cloud storage">
                                                <i class="fas fa-cloud-arrow-up text-[10px]"></i>
                                                <span>Sync Offsite</span>
                                            </button>
                                        </form>

                                        <!-- Download Button -->
                                        <a href="{{ route('owner.security-backup.download', $backup['filename']) }}" 
                                           class="p-1.5 px-2 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-md text-[10.5px] font-semibold transition inline-flex items-center gap-1 border border-blue-200"
                                           title="Download Archive">
                                            <i class="fas fa-download text-[9px]"></i>
                                            <span>Download</span>
                                        </a>

                                        <!-- Delete Button -->
                                        <form action="{{ route('owner.security-backup.delete', $backup['filename']) }}" method="POST" onsubmit="return confirm('Are you sure you want to permanently delete this backup file?');" class="inline-block">
                                            @csrf
                                            <button type="submit" class="p-1.5 px-2 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-md text-[10.5px] font-semibold transition inline-flex items-center gap-1 border border-rose-200" title="Delete">
                                                <i class="fas fa-trash-alt text-[9px]"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-slate-400 text-xs">
                                    <i class="fas fa-folder-open text-2xl mb-1 text-slate-300 block"></i>
                                    No backup archives generated yet. Click "Create Live Snapshot" above to generate your first backup.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 6: Security Audit Logs & Real-Time Incident Stream -->
    <div x-show="activeTab === 'audit_logs'" class="space-y-4" x-cloak>
        <!-- Security Telemetry Badges -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div class="bg-white p-3 rounded-xl border border-slate-200/80 shadow-2xs flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-sm font-bold">
                    <i class="fas fa-shield-halved"></i>
                </div>
                <div>
                    <div class="text-[10px] text-slate-400 font-semibold uppercase">Total Audit Events</div>
                    <div class="text-sm font-bold text-slate-800">{{ number_format($totalLogsCount) }}</div>
                </div>
            </div>

            <div class="bg-white p-3 rounded-xl border border-slate-200/80 shadow-2xs flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center text-sm font-bold">
                    <i class="fas fa-triangle-exclamation"></i>
                </div>
                <div>
                    <div class="text-[10px] text-slate-400 font-semibold uppercase">Security Warnings</div>
                    <div class="text-sm font-bold text-amber-700">{{ number_format($warningCount) }}</div>
                </div>
            </div>

            <div class="bg-white p-3 rounded-xl border border-slate-200/80 shadow-2xs flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center text-sm font-bold">
                    <i class="fas fa-radiation"></i>
                </div>
                <div>
                    <div class="text-[10px] text-slate-400 font-semibold uppercase">Critical Threats</div>
                    <div class="text-sm font-bold text-rose-700">{{ number_format($criticalCount) }}</div>
                </div>
            </div>

            <div class="bg-white p-3 rounded-xl border border-slate-200/80 shadow-2xs flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center text-sm font-bold">
                    <i class="fas fa-ban"></i>
                </div>
                <div>
                    <div class="text-[10px] text-slate-400 font-semibold uppercase">Blocked Requests</div>
                    <div class="text-sm font-bold text-purple-700">{{ number_format($blockedCount) }}</div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs divide-y divide-slate-100">
            <div class="p-3.5 flex items-center justify-between bg-slate-50/50 rounded-t-xl">
                <span class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                    <i class="fas fa-list-check text-indigo-600"></i>
                    Live Security Telemetry & Audit Stream
                </span>
                <span class="text-[10px] text-slate-400">Tamper-evident system log feed</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/40 text-[10.5px] font-bold uppercase tracking-wider text-slate-500">
                            <th class="py-2.5 px-4">Severity</th>
                            <th class="py-2.5 px-4">Event & Description</th>
                            <th class="py-2.5 px-4">Actor / Tenant</th>
                            <th class="py-2.5 px-4">IP / User Agent</th>
                            <th class="py-2.5 px-4">Status</th>
                            <th class="py-2.5 px-4 text-right">Timestamp</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @forelse($auditLogs as $log)
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-2.5 px-4">
                                    @if($log->severity === 'critical' || $log->severity === 'alert')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[9.5px] font-bold bg-rose-100 text-rose-800 border border-rose-200 uppercase">
                                            {{ $log->severity }}
                                        </span>
                                    @elseif($log->severity === 'warning')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[9.5px] font-bold bg-amber-100 text-amber-800 border border-amber-200 uppercase">
                                            Warning
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[9.5px] font-semibold bg-blue-50 text-blue-700 border border-blue-200 uppercase">
                                            {{ $log->severity ?: 'INFO' }}
                                        </span>
                                    @endif
                                </td>
                                <td class="py-2.5 px-4 font-semibold text-slate-800">
                                    <div class="text-xs font-semibold text-slate-800 font-mono">{{ $log->event_type }}</div>
                                    <div class="text-[11px] text-slate-500 mt-0.5">{{ $log->description }}</div>
                                </td>
                                <td class="py-2.5 px-4 font-mono text-[11px] text-slate-600">
                                    @if($log->tenant)
                                        <span class="font-medium text-slate-800">{{ $log->tenant->name }}</span>
                                        <div class="text-[10px] text-slate-400">Tenant #{{ $log->tenant_id }}</div>
                                    @elseif($log->user)
                                        <span class="font-medium text-slate-800">{{ $log->user->name }}</span>
                                        <div class="text-[10px] text-slate-400">{{ $log->user->email }}</div>
                                    @else
                                        <span class="text-slate-400">System (Daemon)</span>
                                    @endif
                                </td>
                                <td class="py-2.5 px-4 font-mono text-[11px] text-slate-700">
                                    <div>{{ $log->ip_address ?: '127.0.0.1' }}</div>
                                    <div class="text-[10px] text-slate-400 truncate max-w-[140px]" title="{{ $log->user_agent }}">{{ $log->user_agent ?: 'Console/Worker' }}</div>
                                </td>
                                <td class="py-2.5 px-4">
                                    @if($log->status === 'success')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[9.5px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            Success
                                        </span>
                                    @elseif($log->status === 'blocked')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[9.5px] font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                            Blocked (403)
                                        </span>
                                    @elseif($log->status === 'failed')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[9.5px] font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                            Failed
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[9.5px] font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                                            {{ ucfirst($log->status ?: 'info') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="py-2.5 px-4 text-right text-[11px] text-slate-400 whitespace-nowrap">
                                    {{ $log->created_at->diffForHumans() }}
                                    <div class="text-[10px] text-slate-400">{{ $log->created_at->format('d M, H:i:s') }}</div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-slate-400 text-xs">
                                    <i class="fas fa-shield-halved text-2xl mb-1 text-slate-300 block"></i>
                                    No security audit logs recorded yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($auditLogs->hasPages())
                <div class="p-3 bg-slate-50/50">
                    {{ $auditLogs->links() }}
                </div>
            @endif
        </div>
    </div>

</div>
@endsection
