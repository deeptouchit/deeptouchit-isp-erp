@extends('owner.layouts.app')

@section('page-title', 'Payment Gateways & Merchant Settlements')

@section('content')
<div class="space-y-4" x-data="{ 
    activeTab: 'gateways',
    showBkashSecret: false,
    showBkashPass: false,
    showNagadPriv: false,
    showSslPass: false,
    showAamarPass: false,
    showShurjoPass: false,
    showStripeSecret: false,
    showStripeWh: false,
    copiedKey: null,
    copyToClipboard(text, key) {
        navigator.clipboard.writeText(text);
        this.copiedKey = key;
        setTimeout(() => { this.copiedKey = null; }, 2000);
    },
    testSim: {
        gateway: 'bkash',
        amount: 4500,
        calcSurcharge(rate) {
            return (this.amount * (rate / 100)).toFixed(2);
        }
    }
}">

    <!-- Top Header & PGW Status -->
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-600 to-indigo-700 flex items-center justify-center text-white text-base font-bold shadow-xs">
                <i class="fas fa-credit-card text-sm"></i>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-sm font-bold text-slate-800">Enterprise Payment Gateways & Merchant Settlements</h2>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1 animate-pulse"></span>
                        Auto-Settlement Ready
                    </span>
                </div>
                <p class="text-[11px] text-slate-500 mt-0.5">Automated online checkout APIs (bKash Tokenized, Nagad PGW, SSLCommerz, AamarPay, Stripe & Bank Wire)</p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <button type="button" @click="activeTab = 'dev_docs'" class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-[11px] font-semibold transition border border-slate-200 flex items-center gap-1.5">
                <i class="fas fa-code text-slate-500 text-[10px]"></i>
                <span>PGW API Specs</span>
            </button>
            <button type="button" @click="activeTab = 'test_simulator'" class="px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-[11px] font-semibold transition shadow-xs flex items-center gap-1.5">
                <i class="fas fa-flask text-[10px]"></i>
                <span>Test Gateway Handshake</span>
            </button>
        </div>
    </div>

    <!-- Real-time PGW Metrics Strip -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="bg-white p-3 rounded-xl border border-slate-200/80 shadow-xs flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-xs">
                <i class="fas fa-toggle-on"></i>
            </div>
            <div>
                <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider block">Active Gateways</span>
                <span class="text-xs font-bold text-slate-800 font-mono">{{ $activeGatewaysCount }} Active Channels</span>
            </div>
        </div>

        <div class="bg-white p-3 rounded-xl border border-slate-200/80 shadow-xs flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-xs">
                <i class="fas fa-coins"></i>
            </div>
            <div>
                <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider block">Base Settlement Currency</span>
                <span class="text-xs font-bold text-slate-800 font-mono">{{ $settings['currency_code'] ?? 'BDT' }} ({{ $settings['currency_symbol'] ?? '৳' }})</span>
            </div>
        </div>

        <div class="bg-white p-3 rounded-xl border border-slate-200/80 shadow-xs flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-xs">
                <i class="fas fa-receipt"></i>
            </div>
            <div>
                <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider block">Paid Online Invoices</span>
                <span class="text-xs font-bold text-emerald-600 font-mono">৳ {{ number_format($paidInvoicesTotal, 2) }}</span>
            </div>
        </div>

        <div class="bg-white p-3 rounded-xl border border-slate-200/80 shadow-xs flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center text-xs">
                <i class="fas fa-shield-halved"></i>
            </div>
            <div>
                <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider block">Environment Health</span>
                <span class="text-xs font-bold text-slate-800">{{ $liveCount }} Live &bull; {{ $sandboxCount }} Sandbox</span>
            </div>
        </div>
    </div>


    <!-- Test Result Notification if present -->
    @if(session('test_result'))
        @php $tr = session('test_result'); @endphp
        <div class="p-4 bg-slate-900 border border-slate-800 text-slate-100 rounded-xl text-xs space-y-2 shadow-lg">
            <div class="flex items-center justify-between border-b border-slate-800 pb-2">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full {{ $tr['success'] ? 'bg-emerald-400 animate-ping' : 'bg-rose-500' }}"></span>
                    <span class="font-bold text-white text-xs uppercase tracking-wider">Gateway Handshake Test Result: {{ strtoupper($tr['gateway']) }}</span>
                    <span class="px-2 py-0.5 rounded text-[10px] font-mono {{ $tr['success'] ? 'bg-emerald-950 text-emerald-400 border border-emerald-800' : 'bg-rose-950 text-rose-400' }}">
                        HTTP {{ $tr['status_code'] }} &bull; {{ $tr['latency_ms'] }} ms
                    </span>
                </div>
                <span class="text-[10px] text-slate-400 font-mono">Mode: {{ strtoupper($tr['mode']) }}</span>
            </div>
            <div class="text-[11px] text-slate-300 font-mono">
                <span class="text-slate-500">Endpoint:</span> {{ $tr['endpoint'] ?: 'N/A' }}
            </div>
            <div class="text-[11px] text-slate-300 font-mono">
                <span class="text-slate-500">Message:</span> {{ $tr['message'] }}
            </div>
            <div class="bg-black/60 p-2.5 rounded-lg border border-slate-800 overflow-x-auto text-[10.5px] font-mono text-emerald-400">
                <pre>{{ is_array($tr['details']) ? json_encode($tr['details'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : $tr['details'] }}</pre>
            </div>
        </div>
    @endif

    <!-- Main Navigation Tabs -->
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs p-1.5 flex items-center gap-1 overflow-x-auto">
        <button type="button" @click="activeTab = 'gateways'" :class="activeTab === 'gateways' ? 'bg-blue-600 text-white shadow-xs font-bold' : 'text-slate-600 hover:bg-slate-100 font-medium'" class="px-3 py-1.5 rounded-lg text-xs transition flex items-center gap-1.5 whitespace-nowrap">
            <i class="fas fa-credit-card text-[11px]"></i>
            <span>Gateway Providers (8)</span>
        </button>

        <button type="button" @click="activeTab = 'policies'" :class="activeTab === 'policies' ? 'bg-blue-600 text-white shadow-xs font-bold' : 'text-slate-600 hover:bg-slate-100 font-medium'" class="px-3 py-1.5 rounded-lg text-xs transition flex items-center gap-1.5 whitespace-nowrap">
            <i class="fas fa-sliders text-[11px]"></i>
            <span>Policies & Surcharges</span>
        </button>

        <button type="button" @click="activeTab = 'webhooks'" :class="activeTab === 'webhooks' ? 'bg-blue-600 text-white shadow-xs font-bold' : 'text-slate-600 hover:bg-slate-100 font-medium'" class="px-3 py-1.5 rounded-lg text-xs transition flex items-center gap-1.5 whitespace-nowrap">
            <i class="fas fa-webhook text-[11px]"></i>
            <span>Webhooks & IPN Listeners</span>
        </button>

        <button type="button" @click="activeTab = 'test_simulator'" :class="activeTab === 'test_simulator' ? 'bg-blue-600 text-white shadow-xs font-bold' : 'text-slate-600 hover:bg-slate-100 font-medium'" class="px-3 py-1.5 rounded-lg text-xs transition flex items-center gap-1.5 whitespace-nowrap">
            <i class="fas fa-flask text-[11px]"></i>
            <span>Test Handshake Simulator</span>
        </button>

        <button type="button" @click="activeTab = 'audit_logs'" :class="activeTab === 'audit_logs' ? 'bg-blue-600 text-white shadow-xs font-bold' : 'text-slate-600 hover:bg-slate-100 font-medium'" class="px-3 py-1.5 rounded-lg text-xs transition flex items-center gap-1.5 whitespace-nowrap">
            <i class="fas fa-list-check text-[11px]"></i>
            <span>Online Billing Audit Logs</span>
        </button>

        <button type="button" @click="activeTab = 'dev_docs'" :class="activeTab === 'dev_docs' ? 'bg-blue-600 text-white shadow-xs font-bold' : 'text-slate-600 hover:bg-slate-100 font-medium'" class="px-3 py-1.5 rounded-lg text-xs transition flex items-center gap-1.5 whitespace-nowrap">
            <i class="fas fa-book-bookmark text-[11px]"></i>
            <span>API Docs & SDKs</span>
        </button>
    </div>

    <!-- TAB 1: GATEWAY PROVIDERS CONFIGURATION FORM -->
    <div x-show="activeTab === 'gateways'" class="space-y-4">
        <form action="{{ route('owner.payment-gateways.update') }}" method="POST" class="space-y-4">
            @csrf

            <!-- 1. bKash Tokenized Direct Checkout -->
            <div class="bg-white rounded-xl border border-slate-200/80 p-4 space-y-3 shadow-xs">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-2 border-b border-pink-100 gap-2">
                    <div class="flex items-center gap-2.5">
                        <span class="px-2 py-0.5 rounded bg-pink-600 text-white font-bold text-[10px] tracking-wide shadow-xs">bKash</span>
                        <div>
                            <div class="flex items-center gap-2">
                                <h4 class="font-bold text-xs text-slate-900 leading-tight">bKash Tokenized Checkout (v1.2.0-beta API)</h4>
                                <span class="px-1.5 py-0.5 rounded text-[9.5px] font-semibold bg-pink-50 text-pink-700 border border-pink-200">Recommended for BD SaaS</span>
                            </div>
                            <span class="text-[10.5px] text-slate-500">Direct In-App Webhook Token Grant with Agreement & Payment Execution</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <select name="bkash_mode" class="px-2 py-1 text-[11px] rounded-lg border border-slate-200 bg-slate-50 font-semibold text-slate-700">
                            <option value="sandbox" {{ ($settings['bkash_mode'] ?? 'sandbox') === 'sandbox' ? 'selected' : '' }}>Sandbox Mode</option>
                            <option value="live" {{ ($settings['bkash_mode'] ?? '') === 'live' ? 'selected' : '' }}>Live Production</option>
                        </select>
                        <label class="flex items-center gap-1.5 cursor-pointer text-[11px] font-semibold text-slate-700">
                            <input type="checkbox" name="bkash_status" value="1" {{ ($settings['bkash_status'] ?? '1') == '1' ? 'checked' : '' }} class="w-3.5 h-3.5 rounded text-pink-600 focus:ring-0">
                            <span>Enable bKash</span>
                        </label>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-[10.5px] font-semibold text-slate-600 mb-1">bKash App Key *</label>
                        <input type="text" name="bkash_app_key" value="{{ $settings['bkash_app_key'] ?? '' }}" placeholder="e.g. 4f6o9i21..." class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50 font-mono">
                    </div>
                    <div>
                        <label class="block text-[10.5px] font-semibold text-slate-600 mb-1">bKash App Secret *</label>
                        <div class="relative">
                            <input :type="showBkashSecret ? 'text' : 'password'" name="bkash_app_secret" value="{{ $settings['bkash_app_secret'] ?? '' }}" placeholder="Secret Key" class="w-full pl-2.5 pr-8 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50 font-mono">
                            <button type="button" @click="showBkashSecret = !showBkashSecret" class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 text-[10px] hover:text-slate-600">
                                <i class="fas" :class="showBkashSecret ? 'fa-eye-slash' : 'fa-eye'"></i>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10.5px] font-semibold text-slate-600 mb-1">Merchant Username *</label>
                        <input type="text" name="bkash_username" value="{{ $settings['bkash_username'] ?? '' }}" placeholder="sandboxTestUser" class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50">
                    </div>
                    <div>
                        <label class="block text-[10.5px] font-semibold text-slate-600 mb-1">Merchant Password *</label>
                        <div class="relative">
                            <input :type="showBkashPass ? 'text' : 'password'" name="bkash_password" value="{{ $settings['bkash_password'] ?? '' }}" placeholder="••••••••" class="w-full pl-2.5 pr-8 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50">
                            <button type="button" @click="showBkashPass = !showBkashPass" class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 text-[10px] hover:text-slate-600">
                                <i class="fas" :class="showBkashPass ? 'fa-eye-slash' : 'fa-eye'"></i>
                            </button>
                        </div>
                    </div>

                    <div class="sm:col-span-2 lg:col-span-2">
                        <label class="block text-[10.5px] font-semibold text-slate-600 mb-1">bKash Transaction Surcharge / Fee (%)</label>
                        <div class="relative">
                            <input type="number" step="0.01" name="bkash_fee_percent" value="{{ $settings['bkash_fee_percent'] ?? '1.50' }}" placeholder="1.50" class="w-full pl-2.5 pr-8 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50 font-mono">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs font-bold">%</span>
                        </div>
                        <span class="text-[10px] text-slate-400">Standard bKash merchant gateway fee is 1.20% to 1.50%</span>
                    </div>

                    <div class="sm:col-span-2 lg:col-span-2">
                        <label class="block text-[10.5px] font-semibold text-slate-600 mb-1">bKash Callback Webhook Endpoint</label>
                        <div class="flex items-center gap-1.5">
                            <input type="text" readonly value="{{ url('/api/payment/bkash/callback') }}" class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-100 font-mono text-slate-600">
                            <button type="button" @click="copyToClipboard('{{ url('/api/payment/bkash/callback') }}', 'bkash_cb')" class="px-2.5 py-1.5 rounded-lg border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-600 text-xs flex-shrink-0" title="Copy URL">
                                <i class="fas" :class="copiedKey === 'bkash_cb' ? 'fa-check text-emerald-600' : 'fa-copy'"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Nagad Direct Gateway -->
            <div class="bg-white rounded-xl border border-slate-200/80 p-4 space-y-3 shadow-xs">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-2 border-b border-orange-100 gap-2">
                    <div class="flex items-center gap-2.5">
                        <span class="px-2 py-0.5 rounded bg-orange-600 text-white font-bold text-[10px] tracking-wide shadow-xs">Nagad</span>
                        <div>
                            <h4 class="font-bold text-xs text-slate-900 leading-tight">Nagad Merchant Direct Gateway (DFS API)</h4>
                            <span class="text-[10.5px] text-slate-500">Official Nagad RSA 2048-bit Public/Private Key Secured PGW</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <select name="nagad_mode" class="px-2 py-1 text-[11px] rounded-lg border border-slate-200 bg-slate-50 font-semibold text-slate-700">
                            <option value="sandbox" {{ ($settings['nagad_mode'] ?? 'sandbox') === 'sandbox' ? 'selected' : '' }}>Sandbox Mode</option>
                            <option value="live" {{ ($settings['nagad_mode'] ?? '') === 'live' ? 'selected' : '' }}>Live Production</option>
                        </select>
                        <label class="flex items-center gap-1.5 cursor-pointer text-[11px] font-semibold text-slate-700">
                            <input type="checkbox" name="nagad_status" value="1" {{ ($settings['nagad_status'] ?? '1') == '1' ? 'checked' : '' }} class="w-3.5 h-3.5 rounded text-orange-600 focus:ring-0">
                            <span>Enable Nagad</span>
                        </label>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-[10.5px] font-semibold text-slate-600 mb-1">Nagad Merchant ID *</label>
                        <input type="text" name="nagad_merchant_id" value="{{ $settings['nagad_merchant_id'] ?? '' }}" placeholder="6830XXXXXXXXXXX" class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50 font-mono">
                    </div>
                    <div>
                        <label class="block text-[10.5px] font-semibold text-slate-600 mb-1">Nagad Public Key *</label>
                        <input type="text" name="nagad_public_key" value="{{ $settings['nagad_public_key'] ?? '' }}" placeholder="MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8A..." class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50 font-mono">
                    </div>
                    <div>
                        <label class="block text-[10.5px] font-semibold text-slate-600 mb-1">Transaction Surcharge (%)</label>
                        <div class="relative">
                            <input type="number" step="0.01" name="nagad_fee_percent" value="{{ $settings['nagad_fee_percent'] ?? '1.45' }}" placeholder="1.45" class="w-full pl-2.5 pr-8 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50 font-mono">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs font-bold">%</span>
                        </div>
                    </div>

                    <div class="sm:col-span-2 lg:col-span-3">
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-[10.5px] font-semibold text-slate-600">Nagad Private Key (RSA Private PEM) *</label>
                            <button type="button" @click="showNagadPriv = !showNagadPriv" class="text-[10.5px] text-blue-600 font-semibold hover:underline">
                                <span x-text="showNagadPriv ? 'Hide Key' : 'Show / Edit Key'"></span>
                            </button>
                        </div>
                        <div x-show="!showNagadPriv" class="p-2 rounded-lg bg-slate-100 border border-slate-200 text-[11px] font-mono text-slate-500">
                            {{ !empty($settings['nagad_private_key']) ? '•••••••••••••••• (RSA Private Key configured securely)' : 'No private key configured yet' }}
                        </div>
                        <textarea x-show="showNagadPriv" name="nagad_private_key" rows="3" placeholder="-----BEGIN RSA PRIVATE KEY-----&#10;MIIEowIBAAKCAQEA0...&#10;-----END RSA PRIVATE KEY-----" class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50 font-mono text-slate-700">{{ $settings['nagad_private_key'] ?? '' }}</textarea>
                    </div>
                </div>
            </div>

            <!-- 3. SSLCommerz V4 Hosted & EasyCheckout -->
            <div class="bg-white rounded-xl border border-slate-200/80 p-4 space-y-3 shadow-xs">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-2 border-b border-indigo-100 gap-2">
                    <div class="flex items-center gap-2.5">
                        <span class="px-2 py-0.5 rounded bg-indigo-700 text-white font-bold text-[10px] tracking-wide shadow-xs">SSLCommerz</span>
                        <div>
                            <h4 class="font-bold text-xs text-slate-900 leading-tight">SSLCommerz (V4 Hosted & EasyCheckout Gateway)</h4>
                            <span class="text-[10.5px] text-slate-500">Visa, Mastercard, Amex, NexusPay, City Bank, bKash, Nagad, Rocket Unified</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <select name="ssl_mode" class="px-2 py-1 text-[11px] rounded-lg border border-slate-200 bg-slate-50 font-semibold text-slate-700">
                            <option value="sandbox" {{ ($settings['ssl_mode'] ?? 'sandbox') === 'sandbox' ? 'selected' : '' }}>Sandbox Mode</option>
                            <option value="live" {{ ($settings['ssl_mode'] ?? '') === 'live' ? 'selected' : '' }}>Live Production</option>
                        </select>
                        <label class="flex items-center gap-1.5 cursor-pointer text-[11px] font-semibold text-slate-700">
                            <input type="checkbox" name="ssl_status" value="1" {{ ($settings['ssl_status'] ?? '1') == '1' ? 'checked' : '' }} class="w-3.5 h-3.5 rounded text-indigo-700 focus:ring-0">
                            <span>Enable SSLCommerz</span>
                        </label>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-[10.5px] font-semibold text-slate-600 mb-1">Store ID *</label>
                        <input type="text" name="ssl_store_id" value="{{ $settings['ssl_store_id'] ?? '' }}" placeholder="somitysoftlive001" class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50 font-mono">
                    </div>
                    <div>
                        <label class="block text-[10.5px] font-semibold text-slate-600 mb-1">Store Password / Secret Key *</label>
                        <div class="relative">
                            <input :type="showSslPass ? 'text' : 'password'" name="ssl_store_password" value="{{ $settings['ssl_store_password'] ?? '' }}" placeholder="Store Password" class="w-full pl-2.5 pr-8 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50 font-mono">
                            <button type="button" @click="showSslPass = !showSslPass" class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 text-[10px] hover:text-slate-600">
                                <i class="fas" :class="showSslPass ? 'fa-eye-slash' : 'fa-eye'"></i>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10.5px] font-semibold text-slate-600 mb-1">Gateway Surcharge (%)</label>
                        <div class="relative">
                            <input type="number" step="0.01" name="ssl_fee_percent" value="{{ $settings['ssl_fee_percent'] ?? '2.50' }}" placeholder="2.50" class="w-full pl-2.5 pr-8 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50 font-mono">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs font-bold">%</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. AamarPay PGW -->
            <div class="bg-white rounded-xl border border-slate-200/80 p-4 space-y-3 shadow-xs">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-2 border-b border-emerald-100 gap-2">
                    <div class="flex items-center gap-2.5">
                        <span class="px-2 py-0.5 rounded bg-emerald-600 text-white font-bold text-[10px] tracking-wide shadow-xs">AamarPay</span>
                        <div>
                            <h4 class="font-bold text-xs text-slate-900 leading-tight">AamarPay Fast Checkout (JSON Post Engine)</h4>
                            <span class="text-[10.5px] text-slate-500">Popular low-cost payment aggregator for Bangladeshi Internet & SaaS providers</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <select name="aamarpay_mode" class="px-2 py-1 text-[11px] rounded-lg border border-slate-200 bg-slate-50 font-semibold text-slate-700">
                            <option value="sandbox" {{ ($settings['aamarpay_mode'] ?? 'sandbox') === 'sandbox' ? 'selected' : '' }}>Sandbox Mode</option>
                            <option value="live" {{ ($settings['aamarpay_mode'] ?? '') === 'live' ? 'selected' : '' }}>Live Production</option>
                        </select>
                        <label class="flex items-center gap-1.5 cursor-pointer text-[11px] font-semibold text-slate-700">
                            <input type="checkbox" name="aamarpay_status" value="1" {{ ($settings['aamarpay_status'] ?? '0') == '1' ? 'checked' : '' }} class="w-3.5 h-3.5 rounded text-emerald-600 focus:ring-0">
                            <span>Enable AamarPay</span>
                        </label>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-[10.5px] font-semibold text-slate-600 mb-1">AamarPay Store ID *</label>
                        <input type="text" name="aamarpay_store_id" value="{{ $settings['aamarpay_store_id'] ?? '' }}" placeholder="aamarpaytest" class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50 font-mono">
                    </div>
                    <div>
                        <label class="block text-[10.5px] font-semibold text-slate-600 mb-1">Signature Key *</label>
                        <div class="relative">
                            <input :type="showAamarPass ? 'text' : 'password'" name="aamarpay_signature_key" value="{{ $settings['aamarpay_signature_key'] ?? '' }}" placeholder="28cbf071425d8c..." class="w-full pl-2.5 pr-8 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50 font-mono">
                            <button type="button" @click="showAamarPass = !showAamarPass" class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 text-[10px] hover:text-slate-600">
                                <i class="fas" :class="showAamarPass ? 'fa-eye-slash' : 'fa-eye'"></i>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10.5px] font-semibold text-slate-600 mb-1">Gateway Surcharge (%)</label>
                        <div class="relative">
                            <input type="number" step="0.01" name="aamarpay_fee_percent" value="{{ $settings['aamarpay_fee_percent'] ?? '2.00' }}" placeholder="2.00" class="w-full pl-2.5 pr-8 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50 font-mono">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs font-bold">%</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 5. Shurjopay PGW -->
            <div class="bg-white rounded-xl border border-slate-200/80 p-4 space-y-3 shadow-xs">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-2 border-b border-teal-100 gap-2">
                    <div class="flex items-center gap-2.5">
                        <span class="px-2 py-0.5 rounded bg-teal-600 text-white font-bold text-[10px] tracking-wide shadow-xs">ShurjoPay</span>
                        <div>
                            <h4 class="font-bold text-xs text-slate-900 leading-tight">ShurjoPay (v2 Engine API)</h4>
                            <span class="text-[10.5px] text-slate-500">Central Bank authorized payment aggregator with instant settlement</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <select name="shurjopay_mode" class="px-2 py-1 text-[11px] rounded-lg border border-slate-200 bg-slate-50 font-semibold text-slate-700">
                            <option value="sandbox" {{ ($settings['shurjopay_mode'] ?? 'sandbox') === 'sandbox' ? 'selected' : '' }}>Sandbox Mode</option>
                            <option value="live" {{ ($settings['shurjopay_mode'] ?? '') === 'live' ? 'selected' : '' }}>Live Production</option>
                        </select>
                        <label class="flex items-center gap-1.5 cursor-pointer text-[11px] font-semibold text-slate-700">
                            <input type="checkbox" name="shurjopay_status" value="1" {{ ($settings['shurjopay_status'] ?? '0') == '1' ? 'checked' : '' }} class="w-3.5 h-3.5 rounded text-teal-600 focus:ring-0">
                            <span>Enable ShurjoPay</span>
                        </label>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-[10.5px] font-semibold text-slate-600 mb-1">Merchant Username *</label>
                        <input type="text" name="shurjopay_username" value="{{ $settings['shurjopay_username'] ?? '' }}" placeholder="sp_user" class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50">
                    </div>
                    <div>
                        <label class="block text-[10.5px] font-semibold text-slate-600 mb-1">Merchant Password *</label>
                        <div class="relative">
                            <input :type="showShurjoPass ? 'text' : 'password'" name="shurjopay_password" value="{{ $settings['shurjopay_password'] ?? '' }}" placeholder="Password" class="w-full pl-2.5 pr-8 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50">
                            <button type="button" @click="showShurjoPass = !showShurjoPass" class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 text-[10px] hover:text-slate-600">
                                <i class="fas" :class="showShurjoPass ? 'fa-eye-slash' : 'fa-eye'"></i>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10.5px] font-semibold text-slate-600 mb-1">Store Prefix</label>
                        <input type="text" name="shurjopay_prefix" value="{{ $settings['shurjopay_prefix'] ?? 'NOK' }}" placeholder="NOK" class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50 font-mono">
                    </div>
                    <div>
                        <label class="block text-[10.5px] font-semibold text-slate-600 mb-1">Gateway Surcharge (%)</label>
                        <div class="relative">
                            <input type="number" step="0.01" name="shurjopay_fee_percent" value="{{ $settings['shurjopay_fee_percent'] ?? '2.00' }}" placeholder="2.00" class="w-full pl-2.5 pr-8 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50 font-mono">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs font-bold">%</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 6. Stripe Global Cards Gateway -->
            <div class="bg-white rounded-xl border border-slate-200/80 p-4 space-y-3 shadow-xs">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-2 border-b border-purple-100 gap-2">
                    <div class="flex items-center gap-2.5">
                        <span class="px-2 py-0.5 rounded bg-purple-700 text-white font-bold text-[10px] tracking-wide shadow-xs">Stripe</span>
                        <div>
                            <h4 class="font-bold text-xs text-slate-900 leading-tight">Stripe International (Credit / Debit Cards & Apple Pay)</h4>
                            <span class="text-[10.5px] text-slate-500">Global billing engine for international ISP tenants with 3D Secure 2.0</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <select name="stripe_mode" class="px-2 py-1 text-[11px] rounded-lg border border-slate-200 bg-slate-50 font-semibold text-slate-700">
                            <option value="test" {{ ($settings['stripe_mode'] ?? 'test') === 'test' ? 'selected' : '' }}>Test Sandbox</option>
                            <option value="live" {{ ($settings['stripe_mode'] ?? '') === 'live' ? 'selected' : '' }}>Live Production</option>
                        </select>
                        <label class="flex items-center gap-1.5 cursor-pointer text-[11px] font-semibold text-slate-700">
                            <input type="checkbox" name="stripe_status" value="1" {{ ($settings['stripe_status'] ?? '0') == '1' ? 'checked' : '' }} class="w-3.5 h-3.5 rounded text-purple-700 focus:ring-0">
                            <span>Enable Stripe</span>
                        </label>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <div class="sm:col-span-2">
                        <label class="block text-[10.5px] font-semibold text-slate-600 mb-1">Stripe Publishable Key (pk_...) *</label>
                        <input type="text" name="stripe_publishable_key" value="{{ $settings['stripe_publishable_key'] ?? '' }}" placeholder="pk_test_51HXXXXXXXXXXXXX..." class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50 font-mono">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-[10.5px] font-semibold text-slate-600 mb-1">Stripe Secret Key (sk_...) *</label>
                        <div class="relative">
                            <input :type="showStripeSecret ? 'text' : 'password'" name="stripe_secret_key" value="{{ $settings['stripe_secret_key'] ?? '' }}" placeholder="sk_test_51HXXXXXXXXXXXXX..." class="w-full pl-2.5 pr-8 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50 font-mono">
                            <button type="button" @click="showStripeSecret = !showStripeSecret" class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 text-[10px] hover:text-slate-600">
                                <i class="fas" :class="showStripeSecret ? 'fa-eye-slash' : 'fa-eye'"></i>
                            </button>
                        </div>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-[10.5px] font-semibold text-slate-600 mb-1">Webhook Signing Secret (whsec_...)</label>
                        <div class="relative">
                            <input :type="showStripeWh ? 'text' : 'password'" name="stripe_webhook_secret" value="{{ $settings['stripe_webhook_secret'] ?? '' }}" placeholder="whsec_XXXXXXXX..." class="w-full pl-2.5 pr-8 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50 font-mono">
                            <button type="button" @click="showStripeWh = !showStripeWh" class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 text-[10px] hover:text-slate-600">
                                <i class="fas" :class="showStripeWh ? 'fa-eye-slash' : 'fa-eye'"></i>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10.5px] font-semibold text-slate-600 mb-1">Stripe Currency</label>
                        <select name="stripe_currency" class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50 font-semibold">
                            <option value="USD" {{ ($settings['stripe_currency'] ?? 'USD') === 'USD' ? 'selected' : '' }}>USD ($)</option>
                            <option value="BDT" {{ ($settings['stripe_currency'] ?? '') === 'BDT' ? 'selected' : '' }}>BDT (৳)</option>
                            <option value="EUR" {{ ($settings['stripe_currency'] ?? '') === 'EUR' ? 'selected' : '' }}>EUR (€)</option>
                            <option value="GBP" {{ ($settings['stripe_currency'] ?? '') === 'GBP' ? 'selected' : '' }}>GBP (£)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10.5px] font-semibold text-slate-600 mb-1">Gateway Surcharge (%)</label>
                        <div class="relative">
                            <input type="number" step="0.01" name="stripe_fee_percent" value="{{ $settings['stripe_fee_percent'] ?? '2.90' }}" placeholder="2.90" class="w-full pl-2.5 pr-8 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50 font-mono">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs font-bold">%</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 7. Offline Bank Wire Transfer -->
            <div class="bg-white rounded-xl border border-slate-200/80 p-4 space-y-3 shadow-xs">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-2 border-b border-slate-100 gap-2">
                    <div class="flex items-center gap-2.5">
                        <span class="px-2 py-0.5 rounded bg-slate-800 text-white font-bold text-[10px] tracking-wide shadow-xs">Bank Wire</span>
                        <div>
                            <h4 class="font-bold text-xs text-slate-900 leading-tight">Direct Corporate Bank Account & Wire Transfer</h4>
                            <span class="text-[10.5px] text-slate-500">Displays official bank credentials on unpaid SaaS invoices for manual wire deposits</span>
                        </div>
                    </div>
                    <label class="flex items-center gap-1.5 cursor-pointer text-[11px] font-semibold text-slate-700">
                        <input type="checkbox" name="bank_status" value="1" {{ ($settings['bank_status'] ?? '1') == '1' ? 'checked' : '' }} class="w-3.5 h-3.5 rounded text-slate-800 focus:ring-0">
                        <span>Enable Bank Deposit</span>
                    </label>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-[10.5px] font-semibold text-slate-600 mb-1">Bank Name *</label>
                        <input type="text" name="bank_name" value="{{ $settings['bank_name'] ?? 'Islami Bank Bangladesh Ltd' }}" class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50">
                    </div>
                    <div>
                        <label class="block text-[10.5px] font-semibold text-slate-600 mb-1">Account Title / Beneficiary *</label>
                        <input type="text" name="bank_account_name" value="{{ $settings['bank_account_name'] ?? 'SomitySoft Technologies Ltd' }}" class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50">
                    </div>
                    <div>
                        <label class="block text-[10.5px] font-semibold text-slate-600 mb-1">Account Number *</label>
                        <input type="text" name="bank_account_number" value="{{ $settings['bank_account_number'] ?? '2050394819283740' }}" class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50 font-mono font-bold">
                    </div>
                    <div>
                        <label class="block text-[10.5px] font-semibold text-slate-600 mb-1">Branch & Routing No</label>
                        <input type="text" name="bank_routing_number" value="{{ $settings['bank_routing_number'] ?? 'Dhanmondi Branch, Routing: 125271890' }}" class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50">
                    </div>
                    <div class="sm:col-span-2 lg:col-span-4">
                        <label class="block text-[10.5px] font-semibold text-slate-600 mb-1">Tenant Bank Payment Instructions / Notice</label>
                        <input type="text" name="bank_instructions" value="{{ $settings['bank_instructions'] ?? 'After deposit, please upload the deposit slip or provide the Cheque / Transaction reference ID in your tenant billing portal.' }}" class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50">
                    </div>
                </div>
            </div>

            <!-- 8. Manual Personal MFS Deposit (bKash/Nagad/Rocket Send Money) -->
            <div class="bg-white rounded-xl border border-slate-200/80 p-4 space-y-3 shadow-xs">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-2 border-b border-slate-100 gap-2">
                    <div class="flex items-center gap-2.5">
                        <span class="px-2 py-0.5 rounded bg-rose-600 text-white font-bold text-[10px] tracking-wide shadow-xs">Manual MFS</span>
                        <div>
                            <h4 class="font-bold text-xs text-slate-900 leading-tight">Manual MFS Personal / Agent Numbers (Send Money)</h4>
                            <span class="text-[10.5px] text-slate-500">Provide direct personal numbers for tenants who wish to send money manually</span>
                        </div>
                    </div>
                    <label class="flex items-center gap-1.5 cursor-pointer text-[11px] font-semibold text-slate-700">
                        <input type="checkbox" name="mfs_manual_status" value="1" {{ ($settings['mfs_manual_status'] ?? '1') == '1' ? 'checked' : '' }} class="w-3.5 h-3.5 rounded text-rose-600 focus:ring-0">
                        <span>Enable Manual MFS</span>
                    </label>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-[10.5px] font-semibold text-slate-600 mb-1">bKash Personal / Agent No</label>
                        <input type="text" name="mfs_bkash_personal" value="{{ $settings['mfs_bkash_personal'] ?? '01700-000000 (Personal)' }}" class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50 font-mono">
                    </div>
                    <div>
                        <label class="block text-[10.5px] font-semibold text-slate-600 mb-1">Nagad Personal / Agent No</label>
                        <input type="text" name="mfs_nagad_personal" value="{{ $settings['mfs_nagad_personal'] ?? '01800-000000 (Personal)' }}" class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50 font-mono">
                    </div>
                    <div>
                        <label class="block text-[10.5px] font-semibold text-slate-600 mb-1">Rocket Personal No</label>
                        <input type="text" name="mfs_rocket_personal" value="{{ $settings['mfs_rocket_personal'] ?? '01900-000000-8' }}" class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50 font-mono">
                    </div>
                    <div>
                        <label class="block text-[10.5px] font-semibold text-slate-600 mb-1">Upay Personal No</label>
                        <input type="text" name="mfs_upay_personal" value="{{ $settings['mfs_upay_personal'] ?? '01700-000000' }}" class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50 font-mono">
                    </div>
                </div>
            </div>

            <!-- Sticky Save Toolbar -->
            <div class="sticky bottom-3 z-10 bg-white/95 backdrop-blur-md p-3 rounded-xl border border-slate-200/80 shadow-md flex items-center justify-between">
                <div class="flex items-center gap-2 text-xs text-slate-500">
                    <i class="fas fa-shield-halved text-blue-600"></i>
                    <span>All merchant credentials & API secrets are encrypted and stored securely</span>
                </div>
                <button type="submit" class="px-5 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs shadow-xs transition flex items-center gap-1.5">
                    <i class="fas fa-save text-[10px]"></i>
                    <span>Save All Payment Gateways</span>
                </button>
            </div>

        </form>
    </div>

    <!-- TAB 2: POLICIES & SURCHARGES -->
    <div x-show="activeTab === 'policies'" class="space-y-4">
        <form action="{{ route('owner.payment-gateways.update') }}" method="POST" class="space-y-4">
            @csrf

            <div class="bg-white rounded-xl border border-slate-200/80 p-4 space-y-4 shadow-xs">
                <div class="pb-2 border-b border-slate-100">
                    <h3 class="font-bold text-xs text-slate-900">Global SaaS Billing Policies & Surcharge Rules</h3>
                    <p class="text-[11px] text-slate-500">Define settlement currency, automated license activation, and transaction fee passing</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-[10.5px] font-semibold text-slate-600 mb-1">Default Settlement Currency *</label>
                        <select name="currency_code" class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50 font-semibold">
                            <option value="BDT" {{ ($settings['currency_code'] ?? 'BDT') === 'BDT' ? 'selected' : '' }}>BDT - Bangladeshi Taka (৳)</option>
                            <option value="USD" {{ ($settings['currency_code'] ?? '') === 'USD' ? 'selected' : '' }}>USD - United States Dollar ($)</option>
                            <option value="EUR" {{ ($settings['currency_code'] ?? '') === 'EUR' ? 'selected' : '' }}>EUR - Euro (€)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10.5px] font-semibold text-slate-600 mb-1">Currency Symbol *</label>
                        <input type="text" name="currency_symbol" value="{{ $settings['currency_symbol'] ?? '৳' }}" class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50 font-mono font-bold">
                    </div>

                    <div>
                        <label class="block text-[10.5px] font-semibold text-slate-600 mb-1">Billing Grace Period (Days)</label>
                        <input type="number" name="pgw_grace_period_days" value="{{ $settings['pgw_grace_period_days'] ?? '3' }}" class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50 font-mono">
                    </div>
                </div>

                <div class="space-y-3 pt-2 border-t border-slate-100">
                    <label class="flex items-start gap-2.5 cursor-pointer p-3 rounded-lg border border-slate-200/70 hover:bg-slate-50 transition">
                        <input type="checkbox" name="pgw_auto_activate_license" value="1" {{ ($settings['pgw_auto_activate_license'] ?? '1') == '1' ? 'checked' : '' }} class="w-4 h-4 rounded text-blue-600 focus:ring-0 mt-0.5">
                        <div>
                            <span class="font-bold text-xs text-slate-800 block">Instant License Activation upon Successful Payment</span>
                            <span class="text-[10.5px] text-slate-500 block">Automatically mark tenant invoice as PAID and extend tenant SaaS validity immediately upon PGW webhook confirmation</span>
                        </div>
                    </label>

                    <label class="flex items-start gap-2.5 cursor-pointer p-3 rounded-lg border border-slate-200/70 hover:bg-slate-50 transition">
                        <input type="checkbox" name="pgw_pass_fee_to_tenant" value="1" {{ ($settings['pgw_pass_fee_to_tenant'] ?? '0') == '1' ? 'checked' : '' }} class="w-4 h-4 rounded text-blue-600 focus:ring-0 mt-0.5">
                        <div>
                            <span class="font-bold text-xs text-slate-800 block">Pass PGW Surcharge Fee to Tenant (Convenience Fee)</span>
                            <span class="text-[10.5px] text-slate-500 block">When checked, the 1.5% - 2.5% gateway fee is added to the tenant's checkout amount instead of being absorbed by the platform</span>
                        </div>
                    </label>

                    <label class="flex items-start gap-2.5 cursor-pointer p-3 rounded-lg border border-slate-200/70 hover:bg-slate-50 transition">
                        <input type="checkbox" name="pgw_email_receipt_tenant" value="1" {{ ($settings['pgw_email_receipt_tenant'] ?? '1') == '1' ? 'checked' : '' }} class="w-4 h-4 rounded text-blue-600 focus:ring-0 mt-0.5">
                        <div>
                            <span class="font-bold text-xs text-slate-800 block">Send Automated Payment Confirmation Email</span>
                            <span class="text-[10.5px] text-slate-500 block">Automatically dispatch PDF payment receipt and transaction summary to tenant admin email</span>
                        </div>
                    </label>

                    <label class="flex items-start gap-2.5 cursor-pointer p-3 rounded-lg border border-slate-200/70 hover:bg-slate-50 transition">
                        <input type="checkbox" name="pgw_sms_receipt_tenant" value="1" {{ ($settings['pgw_sms_receipt_tenant'] ?? '1') == '1' ? 'checked' : '' }} class="w-4 h-4 rounded text-blue-600 focus:ring-0 mt-0.5">
                        <div>
                            <span class="font-bold text-xs text-slate-800 block">Send Instant SMS Payment Notification</span>
                            <span class="text-[10.5px] text-slate-500 block">Send instant SMS receipt with Transaction ID to tenant registered mobile phone</span>
                        </div>
                    </label>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="px-5 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs shadow-xs transition flex items-center gap-1.5">
                    <i class="fas fa-save text-[10px]"></i>
                    <span>Save Policy Configurations</span>
                </button>
            </div>
        </form>
    </div>

    <!-- TAB 3: WEBHOOKS & IPN LISTENERS -->
    <div x-show="activeTab === 'webhooks'" class="space-y-4">
        <div class="bg-white rounded-xl border border-slate-200/80 p-4 space-y-4 shadow-xs">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                <div>
                    <h3 class="font-bold text-xs text-slate-900">Live Merchant Webhooks & IPN Listeners</h3>
                    <p class="text-[11px] text-slate-500">Provide these URLs in your merchant gateway merchant portal / developer console</p>
                </div>
                <span class="px-2 py-0.5 rounded text-[10.5px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                    <i class="fas fa-link mr-1"></i> HTTPS Enforced
                </span>
            </div>

            <div class="space-y-3">
                <!-- bKash Callback -->
                <div class="p-3 rounded-xl border border-slate-200 bg-slate-50 space-y-1.5">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-xs text-pink-700 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-pink-600"></span>
                            bKash Tokenized Callback & Webhook URL
                        </span>
                        <span class="text-[10px] font-mono text-slate-500">HTTP POST / GET</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="text" readonly value="{{ url('/api/payment/bkash/callback') }}" class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-white font-mono text-slate-700">
                        <button type="button" @click="copyToClipboard('{{ url('/api/payment/bkash/callback') }}', 'wh_bkash')" class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-100 text-slate-700 text-xs font-semibold flex-shrink-0">
                            <i class="fas" :class="copiedKey === 'wh_bkash' ? 'fa-check text-emerald-600' : 'fa-copy'"></i>
                            <span x-text="copiedKey === 'wh_bkash' ? 'Copied' : 'Copy'"></span>
                        </button>
                    </div>
                </div>

                <!-- Nagad Callback -->
                <div class="p-3 rounded-xl border border-slate-200 bg-slate-50 space-y-1.5">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-xs text-orange-700 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-orange-600"></span>
                            Nagad DFS Callback & Verification URL
                        </span>
                        <span class="text-[10px] font-mono text-slate-500">HTTP GET / POST</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="text" readonly value="{{ url('/api/payment/nagad/callback') }}" class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-white font-mono text-slate-700">
                        <button type="button" @click="copyToClipboard('{{ url('/api/payment/nagad/callback') }}', 'wh_nagad')" class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-100 text-slate-700 text-xs font-semibold flex-shrink-0">
                            <i class="fas" :class="copiedKey === 'wh_nagad' ? 'fa-check text-emerald-600' : 'fa-copy'"></i>
                            <span x-text="copiedKey === 'wh_nagad' ? 'Copied' : 'Copy'"></span>
                        </button>
                    </div>
                </div>

                <!-- SSLCommerz IPN -->
                <div class="p-3 rounded-xl border border-slate-200 bg-slate-50 space-y-1.5">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-xs text-indigo-700 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-indigo-600"></span>
                            SSLCommerz IPN (Instant Payment Notification) URL
                        </span>
                        <span class="text-[10px] font-mono text-slate-500">HTTP POST</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="text" readonly value="{{ url('/api/payment/sslcommerz/ipn') }}" class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-white font-mono text-slate-700">
                        <button type="button" @click="copyToClipboard('{{ url('/api/payment/sslcommerz/ipn') }}', 'wh_ssl')" class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-100 text-slate-700 text-xs font-semibold flex-shrink-0">
                            <i class="fas" :class="copiedKey === 'wh_ssl' ? 'fa-check text-emerald-600' : 'fa-copy'"></i>
                            <span x-text="copiedKey === 'wh_ssl' ? 'Copied' : 'Copy'"></span>
                        </button>
                    </div>
                </div>

                <!-- Stripe Webhook -->
                <div class="p-3 rounded-xl border border-slate-200 bg-slate-50 space-y-1.5">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-xs text-purple-700 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-purple-600"></span>
                            Stripe Webhook Listener URL
                        </span>
                        <span class="text-[10px] font-mono text-slate-500">Events: payment_intent.succeeded, charge.refunded</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="text" readonly value="{{ url('/api/payment/stripe/webhook') }}" class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-200 bg-white font-mono text-slate-700">
                        <button type="button" @click="copyToClipboard('{{ url('/api/payment/stripe/webhook') }}', 'wh_stripe')" class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-100 text-slate-700 text-xs font-semibold flex-shrink-0">
                            <i class="fas" :class="copiedKey === 'wh_stripe' ? 'fa-check text-emerald-600' : 'fa-copy'"></i>
                            <span x-text="copiedKey === 'wh_stripe' ? 'Copied' : 'Copy'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 4: TEST HANDSHAKE SIMULATOR -->
    <div x-show="activeTab === 'test_simulator'" class="space-y-4">
        <div class="bg-white rounded-xl border border-slate-200/80 p-4 space-y-4 shadow-xs">
            <div class="pb-2 border-b border-slate-100">
                <h3 class="font-bold text-xs text-slate-900">Interactive Gateway Handshake & Checkout Simulator</h3>
                <p class="text-[11px] text-slate-500">Simulate online payment checkout initialization and verify your API keys and endpoints</p>
            </div>

            <form action="{{ route('owner.payment-gateways.test') }}" method="POST" class="space-y-4 max-w-2xl">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[10.5px] font-semibold text-slate-600 mb-1">Select Gateway Channel *</label>
                        <select name="gateway" x-model="testSim.gateway" class="w-full px-2.5 py-2 text-xs rounded-lg border border-slate-200 bg-slate-50 font-semibold">
                            <option value="bkash">bKash Tokenized Checkout</option>
                            <option value="nagad">Nagad Direct PGW</option>
                            <option value="sslcommerz">SSLCommerz EasyCheckout</option>
                            <option value="aamarpay">AamarPay FastPay</option>
                            <option value="stripe">Stripe Card PaymentIntent</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10.5px] font-semibold text-slate-600 mb-1">Simulated Invoice Amount (BDT) *</label>
                        <input type="number" step="10" name="amount" x-model="testSim.amount" class="w-full px-2.5 py-2 text-xs rounded-lg border border-slate-200 bg-slate-50 font-mono font-bold">
                    </div>
                </div>

                <div class="p-3 rounded-lg bg-blue-50 border border-blue-200 text-xs text-blue-900 space-y-1">
                    <div class="font-bold flex items-center gap-1.5">
                        <i class="fas fa-info-circle text-blue-600"></i>
                        <span>Simulation Information</span>
                    </div>
                    <p class="text-[11px] text-blue-800">
                        This test will perform an authenticated handshake with the selected provider API endpoint, create a session / token, and verify SSL certificates, headers, and authentication tokens without debiting any actual money.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <button type="submit" class="px-5 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs shadow-xs transition flex items-center gap-1.5">
                        <i class="fas fa-play text-[10px]"></i>
                        <span>Execute API Handshake Test</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- TAB 5: AUDIT & TRANSACTION LOGS -->
    <div x-show="activeTab === 'audit_logs'" class="space-y-4">
        <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="p-3.5 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-xs text-slate-900">SaaS Subscription Online Transactions</h3>
                    <p class="text-[11px] text-slate-500">Real-time audit log of all online checkout attempts, merchant fees, and settlement status</p>
                </div>
                <span class="px-2 py-0.5 rounded text-[10.5px] font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                    Showing Last 5 Transactions
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="bg-slate-50/80 text-slate-500 text-[10.5px] font-semibold uppercase tracking-wider border-b border-slate-200">
                            <th class="py-2.5 px-3">Transaction ID</th>
                            <th class="py-2.5 px-3">Tenant & Plan</th>
                            <th class="py-2.5 px-3">Channel / Gateway</th>
                            <th class="py-2.5 px-3 text-right">Gross Amount</th>
                            <th class="py-2.5 px-3 text-right">Fee (Surcharge)</th>
                            <th class="py-2.5 px-3 text-right">Net Settlement</th>
                            <th class="py-2.5 px-3 text-center">Status</th>
                            <th class="py-2.5 px-3 text-right">Date & Time</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($auditLogs as $log)
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="py-2.5 px-3 font-mono font-bold text-slate-800 text-[11px]">
                                    {{ $log['trx_id'] }}
                                </td>
                                <td class="py-2.5 px-3">
                                    <span class="font-bold text-slate-800 block text-xs">{{ $log['tenant'] }}</span>
                                    <span class="text-[10px] text-slate-400 block">{{ $log['plan'] }}</span>
                                </td>
                                <td class="py-2.5 px-3">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold 
                                        {{ $log['gateway_code'] === 'bkash' ? 'bg-pink-50 text-pink-700 border border-pink-200' : '' }}
                                        {{ $log['gateway_code'] === 'nagad' ? 'bg-orange-50 text-orange-700 border border-orange-200' : '' }}
                                        {{ $log['gateway_code'] === 'sslcommerz' ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : '' }}
                                        {{ $log['gateway_code'] === 'stripe' ? 'bg-purple-50 text-purple-700 border border-purple-200' : '' }}
                                        {{ $log['gateway_code'] === 'bank' ? 'bg-slate-100 text-slate-700 border border-slate-200' : '' }}">
                                        {{ $log['gateway'] }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 text-right font-mono font-bold text-slate-800">
                                    ৳ {{ number_format($log['amount'], 2) }}
                                </td>
                                <td class="py-2.5 px-3 text-right font-mono text-slate-500 text-[11px]">
                                    -৳ {{ number_format($log['fee'], 2) }}
                                </td>
                                <td class="py-2.5 px-3 text-right font-mono font-bold text-emerald-600">
                                    ৳ {{ number_format($log['net'], 2) }}
                                </td>
                                <td class="py-2.5 px-3 text-center">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Success
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 text-right text-[10.5px] text-slate-500 whitespace-nowrap font-mono">
                                    {{ $log['timestamp'] }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 6: DEVELOPER SPECS & SDKs -->
    <div x-show="activeTab === 'dev_docs'" class="space-y-4">
        <div class="bg-white rounded-xl border border-slate-200/80 p-4 space-y-4 shadow-xs">
            <div class="pb-2 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-xs text-slate-900">Developer Specs & Native PHP Gateway SDKs</h3>
                    <p class="text-[11px] text-slate-500">Production ready PHP cURL code snippets for bKash, Nagad, SSLCommerz, and Stripe</p>
                </div>
            </div>

            <!-- bKash Token Grant -->
            <div class="space-y-1.5">
                <div class="flex items-center justify-between">
                    <span class="font-bold text-xs text-slate-800">1. bKash Tokenized Checkout (Grant Token & Create Payment)</span>
                    <span class="text-[10px] text-slate-400 font-mono">PHP cURL</span>
                </div>
                <div class="bg-slate-900 text-slate-100 p-3 rounded-lg font-mono text-[11px] overflow-x-auto">
<pre class="text-emerald-400">&lt;?php
// bKash Tokenized Grant Token API
$url = "{{ ($settings['bkash_mode'] ?? 'sandbox') === 'live' ? 'https://tokenized.pay.bka.sh/v1.2.0-beta' : 'https://tokenized.sandbox.bka.sh/v1.2.0-beta' }}/tokenized/checkout/token/grant";
$headers = [
    "Content-Type: application/json",
    "username: " . "{{ $settings['bkash_username'] ?? 'YOUR_USERNAME' }}",
    "password: " . "YOUR_PASSWORD"
];
$data = [
    "app_key" => "{{ $settings['bkash_app_key'] ?? 'YOUR_APP_KEY' }}",
    "app_secret" => "YOUR_APP_SECRET"
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = json_decode(curl_exec($ch), true);
curl_close($ch);

$idToken = $response['id_token'] ?? null;
// Use $idToken in Authorization header to create and execute payment</pre>
                </div>
            </div>

            <!-- SSLCommerz Session Init -->
            <div class="space-y-1.5 pt-2">
                <div class="flex items-center justify-between">
                    <span class="font-bold text-xs text-slate-800">2. SSLCommerz V4 Session Initialization</span>
                    <span class="text-[10px] text-slate-400 font-mono">PHP cURL POST</span>
                </div>
                <div class="bg-slate-900 text-slate-100 p-3 rounded-lg font-mono text-[11px] overflow-x-auto">
<pre class="text-sky-400">&lt;?php
$postData = [
    'store_id' => "{{ $settings['ssl_store_id'] ?? 'YOUR_STORE_ID' }}",
    'store_passwd' => "YOUR_STORE_PASSWORD",
    'total_amount' => 4500.00,
    'currency' => "BDT",
    'tran_id' => "TRX_" . uniqid(),
    'success_url' => "{{ url('/api/payment/sslcommerz/success') }}",
    'fail_url' => "{{ url('/api/payment/sslcommerz/fail') }}",
    'cancel_url' => "{{ url('/api/payment/sslcommerz/cancel') }}",
    'cus_name' => "Tenant Admin",
    'cus_email' => "tenant@domain.com",
    'cus_phone' => "017XXXXXXXX"
];

$url = "{{ ($settings['ssl_mode'] ?? 'sandbox') === 'live' ? 'https://securepay.sslcommerz.com' : 'https://sandbox.sslcommerz.com' }}/gwprocess/v4/api.php";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = json_decode(curl_exec($ch), true);
curl_close($ch);

if (isset($response['GatewayPageURL'])) {
    header("Location: " . $response['GatewayPageURL']);
    exit;
}</pre>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
