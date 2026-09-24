@extends('tenant.layouts.app')

@section('title', 'Payment Gateway Settings - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
    {{-- Page-specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" x-data="paymentGatewayManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">
    
    {{-- 1. TOP HEADER BAR (AGENTS.md Rule 2.A: Icon + Title + Action buttons ONLY) --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 bg-white p-3 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-pink-50 text-pink-600 border border-pink-100 flex items-center justify-center text-sm shadow-2xs flex-shrink-0">
                <i class="fas fa-credit-card"></i>
            </div>
            <h1 class="text-sm font-bold text-slate-800 tracking-tight">Payment Gateways &amp; Merchant API Settings</h1>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" 
                    @click="activeTab === 'bkash' ? testBkashConnection() : testNagadConnection()" 
                    class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-plug text-slate-500 text-xs"></i>
                <span>Test API Handshake</span>
            </button>
            <a href="{{ route('tenant.finance.gateway-transactions') }}" class="px-3 py-1.5 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-receipt text-xs"></i>
                <span>View Transactions</span>
            </a>
        </div>
    </div>

    {{-- 2. KPI SUMMARY STRIP (Strictly 6 Cards - AGENTS.md Rule 2.B) --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        
        {{-- Card 1: Active Channels --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Active Channels</span>
                <span class="text-[13px] font-bold font-mono text-slate-800 leading-tight block truncate">
                    {{ $stats['active_channels'] }} Gateways
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-cyan-200 bg-cyan-50 text-cyan-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-toggle-on"></i>
            </div>
        </div>

        {{-- Card 2: bKash Mode --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">bKash Status</span>
                <span class="text-[13px] font-bold font-mono text-pink-600 leading-tight block truncate">
                    {{ $stats['bkash_mode'] }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-pink-200 bg-pink-50 text-pink-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-mobile-screen"></i>
            </div>
        </div>

        {{-- Card 3: Nagad Mode --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Nagad Status</span>
                <span class="text-[13px] font-bold font-mono text-orange-600 leading-tight block truncate">
                    {{ $stats['nagad_mode'] }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-orange-200 bg-orange-50 text-orange-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-mobile-screen"></i>
            </div>
        </div>

        {{-- Card 4: Total Gateway Collections --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Online Collected</span>
                <span class="text-[13px] font-bold font-mono text-emerald-600 leading-tight block truncate">
                    @currency($stats['total_collected'])
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-emerald-200 bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-coins"></i>
            </div>
        </div>

        {{-- Card 5: Successful Payments --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Successful</span>
                <span class="text-[13px] font-bold font-mono text-blue-600 leading-tight block truncate">
                    {{ number_format($stats['successful_tx']) }} TXNs
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-blue-200 bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-circle-check"></i>
            </div>
        </div>

        {{-- Card 6: Failed / Expired --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Failed / Dropped</span>
                <span class="text-[13px] font-bold font-mono text-amber-600 leading-tight block truncate">
                    {{ number_format($stats['failed_tx']) }} TXNs
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-amber-200 bg-amber-50 text-amber-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-triangle-exclamation"></i>
            </div>
        </div>

    </div>

    {{-- 3. TABBED CONFIGURATION HUB --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        
        <!-- Segmented Tab Header -->
        <div class="flex items-center border-b border-slate-200 bg-slate-50/70 px-3 pt-2 gap-1 overflow-x-auto">
            <button type="button" 
                    @click="activeTab = 'bkash'" 
                    class="px-4 py-2 text-xs font-bold rounded-t-lg transition flex items-center gap-2 border-t-2 cursor-pointer"
                    :class="activeTab === 'bkash' ? 'bg-white text-pink-700 border-pink-600 shadow-xs' : 'text-slate-600 hover:text-slate-900 border-transparent'">
                <i class="fas fa-mobile-screen text-pink-600 text-xs"></i>
                <span>bKash Merchant API</span>
                @if($bkashSettings['is_active'])
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                @endif
            </button>

            <button type="button" 
                    @click="activeTab = 'nagad'" 
                    class="px-4 py-2 text-xs font-bold rounded-t-lg transition flex items-center gap-2 border-t-2 cursor-pointer"
                    :class="activeTab === 'nagad' ? 'bg-white text-orange-700 border-orange-600 shadow-xs' : 'text-slate-600 hover:text-slate-900 border-transparent'">
                <i class="fas fa-mobile-screen text-orange-600 text-xs"></i>
                <span>Nagad Merchant API</span>
                @if($nagadSettings['is_active'])
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                @endif
            </button>

            <button type="button" 
                    @click="activeTab = 'bank_qr'" 
                    class="px-4 py-2 text-xs font-bold rounded-t-lg transition flex items-center gap-2 border-t-2 cursor-pointer"
                    :class="activeTab === 'bank_qr' ? 'bg-white text-teal-700 border-teal-600 shadow-xs' : 'text-slate-600 hover:text-slate-900 border-transparent'">
                <i class="fas fa-qrcode text-teal-600 text-xs"></i>
                <span>Bangla QR &amp; Bank Info</span>
                @if($bankQrSettings['is_active'])
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                @endif
            </button>

            <button type="button" 
                    @click="activeTab = 'logs'" 
                    class="px-4 py-2 text-xs font-bold rounded-t-lg transition flex items-center gap-2 border-t-2 cursor-pointer"
                    :class="activeTab === 'logs' ? 'bg-white text-blue-700 border-blue-600 shadow-xs' : 'text-slate-600 hover:text-slate-900 border-transparent'">
                <i class="fas fa-list-check text-blue-600 text-xs"></i>
                <span>Gateway Logs &amp; Webhooks</span>
            </button>
        </div>

        <div class="p-4 sm:p-5">
            
            {{-- TAB 1: bKash Merchant API Configuration --}}
            <div x-show="activeTab === 'bkash'" x-cloak class="space-y-4 max-w-4xl">
                <form action="{{ route('tenant.settings.payment-gateways.bkash') }}" method="POST">
                    @csrf
                    
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3.5 rounded-xl bg-pink-50/50 border border-pink-100 mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-pink-600 text-white flex items-center justify-center text-lg shadow-xs flex-shrink-0">
                                <i class="fas fa-mobile-screen"></i>
                            </div>
                            <div>
                                <h3 class="text-xs font-bold text-pink-950">bKash Tokenized Checkout API</h3>
                                <p class="text-[11px] text-pink-800">Automated 1-click customer invoice payment and reseller wallet recharge via bKash PGW</p>
                            </div>
                        </div>

                        <!-- Active Toggle -->
                        <div class="flex items-center gap-3">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="is_active" value="1" {{ $bkashSettings['is_active'] ? 'checked' : '' }} class="sr-only peer">
                                <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-pink-600"></div>
                                <span class="ml-2 text-xs font-bold text-slate-800">{{ $bkashSettings['is_active'] ? 'Enabled' : 'Disabled' }}</span>
                            </label>
                        </div>
                    </div>

                    <!-- Environment Mode Selector -->
                    <div class="p-3 rounded-lg bg-slate-50 border border-slate-200 mb-4">
                        <label class="block text-[11px] font-bold text-slate-700 mb-1.5">Environment Mode <span class="text-rose-500">*</span></label>
                        <div class="grid grid-cols-2 gap-2 sm:w-80">
                            <label class="flex items-center gap-2 p-2 rounded-lg border cursor-pointer transition {{ $bkashSettings['mode'] === 'sandbox' ? 'bg-white border-pink-500 ring-1 ring-pink-400 font-bold text-pink-900' : 'bg-white border-slate-200 text-slate-700' }}">
                                <input type="radio" name="mode" value="sandbox" {{ $bkashSettings['mode'] === 'sandbox' ? 'checked' : '' }} class="text-pink-600 focus:ring-pink-500">
                                <span class="text-xs">Sandbox (Testing)</span>
                            </label>
                            <label class="flex items-center gap-2 p-2 rounded-lg border cursor-pointer transition {{ $bkashSettings['mode'] === 'live' ? 'bg-white border-emerald-500 ring-1 ring-emerald-400 font-bold text-emerald-900' : 'bg-white border-slate-200 text-slate-700' }}">
                                <input type="radio" name="mode" value="live" {{ $bkashSettings['mode'] === 'live' ? 'checked' : '' }} class="text-emerald-600 focus:ring-emerald-500">
                                <span class="text-xs">Live (Production)</span>
                            </label>
                        </div>
                    </div>

                    <!-- API Credentials Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                        
                        <!-- App Key -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">App Key <span class="text-rose-500">*</span></label>
                            <input type="text" 
                                   name="app_key" 
                                   value="{{ $bkashSettings['app_key'] }}" 
                                   placeholder="e.g. 4fxxxxxxxxxxxxxxxxxxxxxx" 
                                   class="w-full px-3 py-1.5 rounded-lg border border-slate-200 bg-slate-50 font-mono text-xs text-slate-800 focus:bg-white focus:outline-none focus:border-pink-500 shadow-2xs">
                        </div>

                        <!-- App Secret -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">App Secret <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <input :type="showBkashSecret ? 'text' : 'password'" 
                                       name="app_secret" 
                                       value="{{ $bkashSettings['app_secret'] }}" 
                                       placeholder="••••••••••••••••••••••••" 
                                       class="w-full pl-3 pr-8 py-1.5 rounded-lg border border-slate-200 bg-slate-50 font-mono text-xs text-slate-800 focus:bg-white focus:outline-none focus:border-pink-500 shadow-2xs">
                                <button type="button" @click="showBkashSecret = !showBkashSecret" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 cursor-pointer">
                                    <i class="fas" :class="showBkashSecret ? 'fa-eye-slash' : 'fa-eye'"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Username -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">Username <span class="text-rose-500">*</span></label>
                            <input type="text" 
                                   name="username" 
                                   value="{{ $bkashSettings['username'] }}" 
                                   placeholder="e.g. sandboxTestUser" 
                                   class="w-full px-3 py-1.5 rounded-lg border border-slate-200 bg-slate-50 font-mono text-xs text-slate-800 focus:bg-white focus:outline-none focus:border-pink-500 shadow-2xs">
                        </div>

                        <!-- Password -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">Password <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <input :type="showBkashPass ? 'text' : 'password'" 
                                       name="password" 
                                       value="{{ $bkashSettings['password'] }}" 
                                       placeholder="••••••••••••••••" 
                                       class="w-full pl-3 pr-8 py-1.5 rounded-lg border border-slate-200 bg-slate-50 font-mono text-xs text-slate-800 focus:bg-white focus:outline-none focus:border-pink-500 shadow-2xs">
                                <button type="button" @click="showBkashPass = !showBkashPass" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 cursor-pointer">
                                    <i class="fas" :class="showBkashPass ? 'fa-eye-slash' : 'fa-eye'"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Merchant Phone / Number -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">Merchant Account / Till Number</label>
                            <input type="text" 
                                   name="merchant_number" 
                                   value="{{ $bkashSettings['merchant_number'] }}" 
                                   placeholder="e.g. 01819000000" 
                                   class="w-full px-3 py-1.5 rounded-lg border border-slate-200 bg-slate-50 font-mono text-xs text-slate-800 focus:bg-white focus:outline-none focus:border-pink-500 shadow-2xs">
                        </div>

                        <!-- Transaction Fee Percentage -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">Gateway Fee (%)</label>
                            <input type="number" 
                                   step="0.01" 
                                   name="fee_value" 
                                   value="{{ $bkashSettings['fee_value'] }}" 
                                   placeholder="1.50" 
                                   class="w-full px-3 py-1.5 rounded-lg border border-slate-200 bg-slate-50 font-mono text-xs text-slate-800 focus:bg-white focus:outline-none focus:border-pink-500 shadow-2xs">
                        </div>

                    </div>

                    <!-- Integration URLs / Webhook Box -->
                    <div class="mt-4 p-3 rounded-lg bg-slate-50 border border-slate-200 space-y-2">
                        <div class="text-[11px] font-bold text-slate-700">bKash Webhook &amp; Callback Endpoint URLs</div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                            <div class="p-2 rounded bg-white border border-slate-200 flex items-center justify-between">
                                <div class="min-w-0 pr-2">
                                    <span class="text-[9.5px] text-slate-400 block uppercase font-bold">IPN / Webhook URL</span>
                                    <span class="font-mono text-[10.5px] text-slate-800 truncate block">{{ url('/api/payment/webhook/bkash') }}</span>
                                </div>
                                <button type="button" @click="copyText('{{ url('/api/payment/webhook/bkash') }}', 'Webhook URL')" class="px-2 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] font-semibold border border-slate-300 transition cursor-pointer">
                                    Copy
                                </button>
                            </div>

                            <div class="p-2 rounded bg-white border border-slate-200 flex items-center justify-between">
                                <div class="min-w-0 pr-2">
                                    <span class="text-[9.5px] text-slate-400 block uppercase font-bold">Callback Return URL</span>
                                    <span class="font-mono text-[10.5px] text-slate-800 truncate block">{{ url('/api/payment/callback/bkash') }}</span>
                                </div>
                                <button type="button" @click="copyText('{{ url('/api/payment/callback/bkash') }}', 'Callback URL')" class="px-2 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] font-semibold border border-slate-300 transition cursor-pointer">
                                    Copy
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Form Action Buttons -->
                    <div class="mt-4 pt-3 border-t border-slate-200 flex items-center justify-between">
                        <button type="button" 
                                @click="testBkashConnection()" 
                                :disabled="isTestingBkash"
                                class="px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-lg border border-slate-300 transition flex items-center gap-1.5 cursor-pointer">
                            <template x-if="isTestingBkash">
                                <i class="fas fa-spinner fa-spin text-xs"></i>
                            </template>
                            <template x-if="!isTestingBkash">
                                <i class="fas fa-bolt text-pink-600 text-xs"></i>
                            </template>
                            <span>Test Live Token Grant</span>
                        </button>

                        <button type="submit" class="bg-pink-600 hover:bg-pink-700 text-white font-semibold text-xs px-5 py-2 rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                            <i class="fas fa-save text-[11px]"></i>
                            <span>Save bKash Settings</span>
                        </button>
                    </div>

                </form>
            </div>

            {{-- TAB 2: Nagad Merchant API Configuration --}}
            <div x-show="activeTab === 'nagad'" x-cloak class="space-y-4 max-w-4xl">
                <form action="{{ route('tenant.settings.payment-gateways.nagad') }}" method="POST">
                    @csrf
                    
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3.5 rounded-xl bg-orange-50/50 border border-orange-100 mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-orange-600 text-white flex items-center justify-center text-lg shadow-xs flex-shrink-0">
                                <i class="fas fa-mobile-screen"></i>
                            </div>
                            <div>
                                <h3 class="text-xs font-bold text-orange-950">Nagad Direct Payment Gateway</h3>
                                <p class="text-[11px] text-orange-800">Secure automated merchant clearance via Nagad Public/Private RSA signature exchange</p>
                            </div>
                        </div>

                        <!-- Active Toggle -->
                        <div class="flex items-center gap-3">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="is_active" value="1" {{ $nagadSettings['is_active'] ? 'checked' : '' }} class="sr-only peer">
                                <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-orange-600"></div>
                                <span class="ml-2 text-xs font-bold text-slate-800">{{ $nagadSettings['is_active'] ? 'Enabled' : 'Disabled' }}</span>
                            </label>
                        </div>
                    </div>

                    <!-- Environment Mode Selector -->
                    <div class="p-3 rounded-lg bg-slate-50 border border-slate-200 mb-4">
                        <label class="block text-[11px] font-bold text-slate-700 mb-1.5">Environment Mode <span class="text-rose-500">*</span></label>
                        <div class="grid grid-cols-2 gap-2 sm:w-80">
                            <label class="flex items-center gap-2 p-2 rounded-lg border cursor-pointer transition {{ $nagadSettings['mode'] === 'sandbox' ? 'bg-white border-orange-500 ring-1 ring-orange-400 font-bold text-orange-900' : 'bg-white border-slate-200 text-slate-700' }}">
                                <input type="radio" name="mode" value="sandbox" {{ $nagadSettings['mode'] === 'sandbox' ? 'checked' : '' }} class="text-orange-600 focus:ring-orange-500">
                                <span class="text-xs">Sandbox (Testing)</span>
                            </label>
                            <label class="flex items-center gap-2 p-2 rounded-lg border cursor-pointer transition {{ $nagadSettings['mode'] === 'live' ? 'bg-white border-emerald-500 ring-1 ring-emerald-400 font-bold text-emerald-900' : 'bg-white border-slate-200 text-slate-700' }}">
                                <input type="radio" name="mode" value="live" {{ $nagadSettings['mode'] === 'live' ? 'checked' : '' }} class="text-emerald-600 focus:ring-emerald-500">
                                <span class="text-xs">Live (Production)</span>
                            </label>
                        </div>
                    </div>

                    <!-- Credentials -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs mb-3">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">Merchant ID <span class="text-rose-500">*</span></label>
                            <input type="text" 
                                   name="merchant_id" 
                                   value="{{ $nagadSettings['merchant_id'] }}" 
                                   placeholder="e.g. 68xxxxxxxxxxxxx" 
                                   class="w-full px-3 py-1.5 rounded-lg border border-slate-200 bg-slate-50 font-mono text-xs text-slate-800 focus:bg-white focus:outline-none focus:border-orange-500 shadow-2xs">
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">Merchant Phone / A/C</label>
                            <input type="text" 
                                   name="merchant_phone" 
                                   value="{{ $nagadSettings['merchant_phone'] }}" 
                                   placeholder="e.g. 01711000000" 
                                   class="w-full px-3 py-1.5 rounded-lg border border-slate-200 bg-slate-50 font-mono text-xs text-slate-800 focus:bg-white focus:outline-none focus:border-orange-500 shadow-2xs">
                        </div>
                    </div>

                    <!-- Public & Private Keys -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">Nagad PGW Public Key</label>
                            <textarea name="public_key" 
                                      rows="5" 
                                      placeholder="-----BEGIN PUBLIC KEY-----&#10;MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA...&#10;-----END PUBLIC KEY-----" 
                                      class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-slate-50 font-mono text-[10.5px] text-slate-800 focus:bg-white focus:outline-none focus:border-orange-500 shadow-2xs">{{ $nagadSettings['public_key'] }}</textarea>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">Merchant Private Key</label>
                            <textarea name="private_key" 
                                      rows="5" 
                                      placeholder="-----BEGIN RSA PRIVATE KEY-----&#10;MIIEowIBAAKCAQEA0wZk...&#10;-----END RSA PRIVATE KEY-----" 
                                      class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-slate-50 font-mono text-[10.5px] text-slate-800 focus:bg-white focus:outline-none focus:border-orange-500 shadow-2xs">{{ $nagadSettings['private_key'] }}</textarea>
                        </div>
                    </div>

                    <!-- Form Action Buttons -->
                    <div class="mt-4 pt-3 border-t border-slate-200 flex items-center justify-between">
                        <button type="button" 
                                @click="testNagadConnection()" 
                                class="px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-lg border border-slate-300 transition flex items-center gap-1.5 cursor-pointer">
                            <i class="fas fa-shield-halved text-orange-600 text-xs"></i>
                            <span>Verify RSA Keys</span>
                        </button>

                        <button type="submit" class="bg-orange-600 hover:bg-orange-700 text-white font-semibold text-xs px-5 py-2 rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                            <i class="fas fa-save text-[11px]"></i>
                            <span>Save Nagad Settings</span>
                        </button>
                    </div>

                </form>
            </div>

            {{-- TAB 3: Bangla QR & Bank Info Configuration --}}
            <div x-show="activeTab === 'bank_qr'" x-cloak class="space-y-4 max-w-4xl">
                <form action="{{ route('tenant.settings.payment-gateways.bank-qr') }}" method="POST">
                    @csrf
                    
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3.5 rounded-xl bg-teal-50/50 border border-teal-100 mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-teal-600 text-white flex items-center justify-center text-lg shadow-xs flex-shrink-0">
                                <i class="fas fa-qrcode"></i>
                            </div>
                            <div>
                                <h3 class="text-xs font-bold text-teal-950">Interoperable Bangla QR &amp; Official Bank Account</h3>
                                <p class="text-[11px] text-teal-800">Display official company bank transfer info and Bangla QR on invoices and reseller recharge portal</p>
                            </div>
                        </div>

                        <!-- Active Toggle -->
                        <div class="flex items-center gap-3">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="is_active" value="1" {{ $bankQrSettings['is_active'] ? 'checked' : '' }} class="sr-only peer">
                                <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-teal-600"></div>
                                <span class="ml-2 text-xs font-bold text-slate-800">{{ $bankQrSettings['is_active'] ? 'Enabled' : 'Disabled' }}</span>
                            </label>
                        </div>
                    </div>

                    <!-- Bank Account Details Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs mb-3">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">Bank Name <span class="text-rose-500">*</span></label>
                            <input type="text" 
                                   name="bank_name" 
                                   value="{{ $bankQrSettings['bank_name'] }}" 
                                   required 
                                   placeholder="e.g. The City Bank Ltd." 
                                   class="w-full px-3 py-1.5 rounded-lg border border-slate-200 bg-slate-50 text-xs text-slate-800 focus:bg-white focus:outline-none focus:border-teal-500 shadow-2xs">
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">Account Holder Name <span class="text-rose-500">*</span></label>
                            <input type="text" 
                                   name="account_name" 
                                   value="{{ $bankQrSettings['account_name'] }}" 
                                   required 
                                   placeholder="e.g. SpeedNet Online" 
                                   class="w-full px-3 py-1.5 rounded-lg border border-slate-200 bg-slate-50 text-xs text-slate-800 focus:bg-white focus:outline-none focus:border-teal-500 shadow-2xs">
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">Account Number <span class="text-rose-500">*</span></label>
                            <input type="text" 
                                   name="account_number" 
                                   value="{{ $bankQrSettings['account_number'] }}" 
                                   required 
                                   placeholder="e.g. 1102983746001" 
                                   class="w-full px-3 py-1.5 rounded-lg border border-slate-200 bg-slate-50 font-mono text-xs text-slate-800 focus:bg-white focus:outline-none focus:border-teal-500 shadow-2xs">
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">Branch Name</label>
                            <input type="text" 
                                   name="branch_name" 
                                   value="{{ $bankQrSettings['branch_name'] }}" 
                                   placeholder="e.g. Principal Branch" 
                                   class="w-full px-3 py-1.5 rounded-lg border border-slate-200 bg-slate-50 text-xs text-slate-800 focus:bg-white focus:outline-none focus:border-teal-500 shadow-2xs">
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">Routing Number</label>
                            <input type="text" 
                                   name="routing_number" 
                                   value="{{ $bankQrSettings['routing_number'] }}" 
                                   placeholder="e.g. 225271890" 
                                   class="w-full px-3 py-1.5 rounded-lg border border-slate-200 bg-slate-50 font-mono text-xs text-slate-800 focus:bg-white focus:outline-none focus:border-teal-500 shadow-2xs">
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">Bangla QR Merchant Number</label>
                            <input type="text" 
                                   name="bangla_qr_number" 
                                   value="{{ $bankQrSettings['bangla_qr_number'] }}" 
                                   placeholder="e.g. 01819000000" 
                                   class="w-full px-3 py-1.5 rounded-lg border border-slate-200 bg-slate-50 font-mono text-xs text-slate-800 focus:bg-white focus:outline-none focus:border-teal-500 shadow-2xs">
                        </div>
                    </div>

                    <!-- Form Action Buttons -->
                    <div class="mt-4 pt-3 border-t border-slate-200 flex items-center justify-end">
                        <button type="submit" class="bg-teal-600 hover:bg-teal-700 text-white font-semibold text-xs px-5 py-2 rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                            <i class="fas fa-save text-[11px]"></i>
                            <span>Save Bank &amp; QR Settings</span>
                        </button>
                    </div>

                </form>
            </div>

            {{-- TAB 4: Live Gateway Logs & Webhooks --}}
            <div x-show="activeTab === 'logs'" x-cloak class="space-y-3">
                <div class="overflow-x-auto">
                    <table class="saas-table">
                        <thead>
                            <tr>
                                <th class="w-10 text-center">#</th>
                                <th class="w-36">Transaction #</th>
                                <th class="w-28 text-center">Gateway</th>
                                <th>Customer / Reference</th>
                                <th class="w-32 text-right">Amount</th>
                                <th class="w-28 text-center">Status</th>
                                <th class="w-32 text-center font-mono">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logs as $idx => $log)
                                <tr>
                                    <td class="text-center font-mono text-slate-500">
                                        {{ ($logs->currentPage() - 1) * $logs->perPage() + $idx + 1 }}
                                    </td>
                                    <td class="font-mono font-bold text-cyan-700">
                                        {{ $log->transaction_id ?? $log->gateway_trx_id ?? ('TXN-' . $log->id) }}
                                    </td>
                                    <td class="text-center">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold border {{ $log->gateway_badge['class'] ?? 'bg-slate-100 text-slate-700 border-slate-200' }}">
                                            <span>{{ ucfirst($log->gateway) }}</span>
                                        </span>
                                    </td>
                                    <td class="text-slate-800 text-xs">
                                        {{ $log->customer?->name ?? 'Online Subscriber' }}
                                    </td>
                                    <td class="text-right font-mono font-bold text-emerald-600">
                                        @currency($log->amount)
                                    </td>
                                    <td class="text-center">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold border {{ $log->status_badge['class'] ?? 'bg-emerald-50 text-emerald-700 border-emerald-200' }}">
                                            <span>{{ $log->status }}</span>
                                        </span>
                                    </td>
                                    <td class="text-center font-mono text-slate-600 text-xs">
                                        {{ $log->created_at ? $log->created_at->format('d M Y, h:i A') : '—' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-8 text-center text-slate-400 text-xs">
                                        <i class="fas fa-receipt text-2xl text-slate-300 mb-2 block"></i>
                                        No gateway transactions found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($logs->hasPages())
                    <div class="px-4 py-2 border-t border-slate-200/80 bg-slate-50/50 flex items-center justify-between">
                        <span class="text-[11px] text-slate-500 font-mono">
                            Showing {{ $logs->firstItem() }} to {{ $logs->lastItem() }} of {{ $logs->total() }} entries
                        </span>
                        <div>
                            {{ $logs->links() }}
                        </div>
                    </div>
                @endif
            </div>

        </div>

    </div>

</div>
@endsection

@push('scripts')
<script>
function paymentGatewayManager() {
    return {
        activeTab: '{{ $tab }}',
        showBkashSecret: false,
        showBkashPass: false,
        isTestingBkash: false,

        copyText(text, label) {
            navigator.clipboard.writeText(text).then(() => {
                if (window.Toast) {
                    Toast.fire({
                        icon: 'success',
                        title: `${label} copied to clipboard!`
                    });
                } else {
                    alert(`${label} copied: ${text}`);
                }
            }).catch(err => {
                console.error("Copy failed", err);
            });
        },

        async testBkashConnection() {
            this.isTestingBkash = true;
            try {
                const form = document.querySelector("form[action*='payment-gateways/bkash']");
                const formData = new FormData(form);
                
                const res = await fetch("{{ route('tenant.settings.payment-gateways.test.bkash') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        app_key: formData.get('app_key'),
                        app_secret: formData.get('app_secret'),
                        username: formData.get('username'),
                        password: formData.get('password'),
                        mode: formData.get('mode')
                    })
                });

                const data = await res.json();
                if (data.success) {
                    if (window.Swal) {
                        Swal.fire({
                            icon: 'success',
                            title: 'bKash Handshake Successful!',
                            text: data.message,
                            confirmButtonColor: '#ec4899',
                        });
                    } else {
                        alert(data.message);
                    }
                } else {
                    if (window.Swal) {
                        Swal.fire({
                            icon: 'error',
                            title: 'bKash Connection Failed',
                            text: data.message,
                            confirmButtonColor: '#e11d48',
                        });
                    } else {
                        alert(data.message);
                    }
                }
            } catch (err) {
                console.error("bKash test error", err);
                alert("Network error while connecting to bKash API.");
            } finally {
                this.isTestingBkash = false;
            }
        },

        async testNagadConnection() {
            try {
                const form = document.querySelector("form[action*='payment-gateways/nagad']");
                const formData = new FormData(form);
                
                const res = await fetch("{{ route('tenant.settings.payment-gateways.test.nagad') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        merchant_id: formData.get('merchant_id'),
                        public_key: formData.get('public_key'),
                        private_key: formData.get('private_key'),
                        mode: formData.get('mode')
                    })
                });

                const data = await res.json();
                if (data.success) {
                    if (window.Swal) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Nagad Handshake Valid!',
                            text: data.message,
                            confirmButtonColor: '#ea580c',
                        });
                    } else {
                        alert(data.message);
                    }
                } else {
                    if (window.Swal) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Nagad Verification Failed',
                            text: data.message,
                            confirmButtonColor: '#e11d48',
                        });
                    } else {
                        alert(data.message);
                    }
                }
            } catch (err) {
                console.error("Nagad test error", err);
                alert("Network error while testing Nagad connection.");
            }
        }
    }
}
</script>
@endpush
