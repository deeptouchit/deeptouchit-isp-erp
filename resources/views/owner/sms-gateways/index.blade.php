@extends('owner.layouts.app')

@section('page-title', 'SMS Gateways & Alert Hub')

@section('content')
<div class="space-y-4" x-data="{ 
    activeTab: 'provider_config',
    selectedProvider: '{{ $settings['sms_provider'] ?? 'bulksmsbd' }}',
    showSecret: false,
    testMsg: 'SomitySoft Cloud Test SMS: Your gateway integration is active and verified.',
    calcChars() {
        let len = this.testMsg.length;
        let isUnicode = /[^\u0000-\u007f]/.test(this.testMsg);
        let perSms = isUnicode ? 70 : 160;
        let parts = Math.max(1, Math.ceil(len / perSms));
        return { len, isUnicode, perSms, parts };
    }
}">

    <!-- Top Header & SMS Engine Status -->
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-600 to-teal-700 flex items-center justify-center text-white text-base font-bold shadow-xs">
                <i class="fas fa-comment-dots text-sm"></i>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-sm font-bold text-slate-800">Enterprise SMS Gateway & Automated Alerts</h2>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1 animate-pulse"></span>
                        API Active
                    </span>
                </div>
                <p class="text-[11px] text-slate-500 mt-0.5">Automated BulkSMS BD API, GreenWeb, Onnorokom, high-speed OTP & transaction receipts</p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <button type="button" @click="activeTab = 'api_docs'" class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-[11px] font-semibold transition border border-slate-200 flex items-center gap-1.5">
                <i class="fas fa-code text-slate-500 text-[10px]"></i>
                <span>API Spec Docs</span>
            </button>
            <button type="button" @click="activeTab = 'test_dispatcher'" class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-semibold transition shadow-xs flex items-center gap-1.5">
                <i class="fas fa-paper-plane text-[10px]"></i>
                <span>Live SMS Dispatcher</span>
            </button>
        </div>
    </div>

    <!-- Real-time SMS Engine Metrics Strip -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="bg-white p-3 rounded-xl border border-slate-200/80 shadow-xs flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-xs">
                <i class="fas fa-tower-broadcast"></i>
            </div>
            <div>
                <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider block">Primary Gateway</span>
                <span class="text-xs font-bold text-slate-800 uppercase font-mono">{{ $settings['sms_provider'] ?? 'BulkSMS BD' }}</span>
            </div>
        </div>

        <div class="bg-white p-3 rounded-xl border border-slate-200/80 shadow-xs flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-xs">
                <i class="fas fa-id-card-clip"></i>
            </div>
            <div>
                <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider block">Approved Sender ID</span>
                <span class="text-xs font-bold text-slate-800">{{ $settings['sms_sender_id'] ?? 'SomitySoft' }}</span>
            </div>
        </div>

        <div class="bg-white p-3 rounded-xl border border-slate-200/80 shadow-xs flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-xs">
                <i class="fas fa-bolt"></i>
            </div>
            <div>
                <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider block">Avg. API Latency</span>
                <span class="text-xs font-bold text-slate-800 font-mono">145 ms</span>
            </div>
        </div>

        <div class="bg-white p-3 rounded-xl border border-slate-200/80 shadow-xs flex items-center justify-between gap-2">
            <div class="flex items-center gap-2.5 overflow-hidden">
                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-xs flex-shrink-0">
                    <i class="fas fa-wallet"></i>
                </div>
                <div class="overflow-hidden leading-tight">
                    <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider block">Live Account Balance</span>
                    <span class="text-xs font-bold text-emerald-600 font-mono truncate block">{{ $balanceInfo['formatted'] ?? '৳ 0.00' }}</span>
                </div>
            </div>
            <a href="{{ route('owner.sms-gateways.balance') }}" title="Check Live Balance API" class="p-1.5 rounded-lg bg-slate-50 hover:bg-emerald-50 text-slate-500 hover:text-emerald-700 transition border border-slate-200 text-xs flex-shrink-0">
                <i class="fas fa-arrows-rotate"></i>
            </a>
        </div>
    </div>


    <!-- Navigation Tabs -->
    <div class="bg-white p-1.5 rounded-xl border border-slate-200/80 shadow-xs flex flex-wrap items-center gap-1">
        <button type="button" @click="activeTab = 'provider_config'" 
            :class="activeTab === 'provider_config' ? 'bg-emerald-600 text-white shadow-xs font-semibold' : 'text-slate-600 hover:bg-slate-100 font-medium'"
            class="px-3 py-1.5 rounded-lg text-xs transition flex items-center gap-1.5">
            <i class="fas fa-sliders-h text-[11px]"></i>
            <span>SMS Providers Matrix</span>
        </button>

        <button type="button" @click="activeTab = 'event_triggers'" 
            :class="activeTab === 'event_triggers' ? 'bg-emerald-600 text-white shadow-xs font-semibold' : 'text-slate-600 hover:bg-slate-100 font-medium'"
            class="px-3 py-1.5 rounded-lg text-xs transition flex items-center gap-1.5">
            <i class="fas fa-bolt text-[11px]"></i>
            <span>Automated Event Triggers</span>
        </button>

        <button type="button" @click="activeTab = 'sms_templates'" 
            :class="activeTab === 'sms_templates' ? 'bg-emerald-600 text-white shadow-xs font-semibold' : 'text-slate-600 hover:bg-slate-100 font-medium'"
            class="px-3 py-1.5 rounded-lg text-xs transition flex items-center gap-1.5">
            <i class="fas fa-file-lines text-[11px]"></i>
            <span>Templates & Tags</span>
        </button>

        <button type="button" @click="activeTab = 'sms_logs'" 
            :class="activeTab === 'sms_logs' ? 'bg-emerald-600 text-white shadow-xs font-semibold' : 'text-slate-600 hover:bg-slate-100 font-medium'"
            class="px-3 py-1.5 rounded-lg text-xs transition flex items-center gap-1.5">
            <i class="fas fa-list-check text-[11px]"></i>
            <span>SMS Audit Logs</span>
        </button>

        <button type="button" @click="activeTab = 'api_docs'" 
            :class="activeTab === 'api_docs' ? 'bg-emerald-600 text-white shadow-xs font-semibold' : 'text-slate-600 hover:bg-slate-100 font-medium'"
            class="px-3 py-1.5 rounded-lg text-xs transition flex items-center gap-1.5">
            <i class="fas fa-code text-[11px]"></i>
            <span>Official API Docs</span>
        </button>

        <button type="button" @click="activeTab = 'test_dispatcher'" 
            :class="activeTab === 'test_dispatcher' ? 'bg-emerald-600 text-white shadow-xs font-semibold' : 'text-slate-600 hover:bg-slate-100 font-medium'"
            class="px-3 py-1.5 rounded-lg text-xs transition flex items-center gap-1.5 ml-auto">
            <i class="fas fa-paper-plane text-[11px]"></i>
            <span>Live Test Dispatcher</span>
        </button>
    </div>

    <!-- MAIN SMS CONFIGURATION FORM -->
    <form action="{{ route('owner.sms-gateways.update') }}" method="POST" class="space-y-4">
        @csrf

        <!-- TAB 1: SMS Gateway Providers Matrix -->
        <div x-show="activeTab === 'provider_config'" class="space-y-4" x-cloak>
            
            <!-- Provider Selector Grid -->
            <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs p-4 space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                        <i class="fas fa-network-wired text-emerald-600"></i>
                        Select Primary Outgoing SMS Gateway
                    </span>
                    <span class="text-[10px] text-slate-400">Integrated with official provider cURL endpoints</span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-2">
                    <!-- BulkSMS BD (Primary) -->
                    <label class="cursor-pointer">
                        <input type="radio" name="sms_provider" value="bulksmsbd" x-model="selectedProvider" class="sr-only">
                        <div :class="selectedProvider === 'bulksmsbd' ? 'border-emerald-500 bg-emerald-50/50 shadow-xs ring-1 ring-emerald-500' : 'border-slate-200 hover:bg-slate-50'" class="p-2.5 rounded-xl border text-center transition">
                            <i class="fas fa-bolt text-emerald-600 text-base mb-1 block"></i>
                            <span class="text-[11px] font-bold text-slate-800 block">BulkSMS BD</span>
                            <span class="text-[9px] text-slate-400 font-mono">bulksmsbd.net</span>
                        </div>
                    </label>

                    <!-- GreenWeb BD -->
                    <label class="cursor-pointer">
                        <input type="radio" name="sms_provider" value="greenweb" x-model="selectedProvider" class="sr-only">
                        <div :class="selectedProvider === 'greenweb' ? 'border-emerald-500 bg-emerald-50/50 shadow-xs ring-1 ring-emerald-500' : 'border-slate-200 hover:bg-slate-50'" class="p-2.5 rounded-xl border text-center transition">
                            <i class="fas fa-leaf text-teal-600 text-base mb-1 block"></i>
                            <span class="text-[11px] font-bold text-slate-800 block">GreenWeb BD</span>
                            <span class="text-[9px] text-slate-400 font-mono">Token API</span>
                        </div>
                    </label>

                    <!-- Onnorokom -->
                    <label class="cursor-pointer">
                        <input type="radio" name="sms_provider" value="onnorokom" x-model="selectedProvider" class="sr-only">
                        <div :class="selectedProvider === 'onnorokom' ? 'border-emerald-500 bg-emerald-50/50 shadow-xs ring-1 ring-emerald-500' : 'border-slate-200 hover:bg-slate-50'" class="p-2.5 rounded-xl border text-center transition">
                            <i class="fas fa-paper-plane text-blue-600 text-base mb-1 block"></i>
                            <span class="text-[11px] font-bold text-slate-800 block">Onnorokom</span>
                            <span class="text-[9px] text-slate-400 font-mono">REST v2</span>
                        </div>
                    </label>

                    <!-- MIM SMS -->
                    <label class="cursor-pointer">
                        <input type="radio" name="sms_provider" value="mim_sms" x-model="selectedProvider" class="sr-only">
                        <div :class="selectedProvider === 'mim_sms' ? 'border-emerald-500 bg-emerald-50/50 shadow-xs ring-1 ring-emerald-500' : 'border-slate-200 hover:bg-slate-50'" class="p-2.5 rounded-xl border text-center transition">
                            <i class="fas fa-message text-indigo-600 text-base mb-1 block"></i>
                            <span class="text-[11px] font-bold text-slate-800 block">MIM SMS</span>
                            <span class="text-[9px] text-slate-400 font-mono">mimsms.com</span>
                        </div>
                    </label>

                    <!-- ElitBuzz -->
                    <label class="cursor-pointer">
                        <input type="radio" name="sms_provider" value="elitbuzz" x-model="selectedProvider" class="sr-only">
                        <div :class="selectedProvider === 'elitbuzz' ? 'border-emerald-500 bg-emerald-50/50 shadow-xs ring-1 ring-emerald-500' : 'border-slate-200 hover:bg-slate-50'" class="p-2.5 rounded-xl border text-center transition">
                            <i class="fas fa-satellite-dish text-amber-500 text-base mb-1 block"></i>
                            <span class="text-[11px] font-bold text-slate-800 block">ElitBuzz BD</span>
                            <span class="text-[9px] text-slate-400 font-mono">msg.elitbuzz</span>
                        </div>
                    </label>

                    <!-- SSL Wireless -->
                    <label class="cursor-pointer">
                        <input type="radio" name="sms_provider" value="ssl_wireless" x-model="selectedProvider" class="sr-only">
                        <div :class="selectedProvider === 'ssl_wireless' ? 'border-emerald-500 bg-emerald-50/50 shadow-xs ring-1 ring-emerald-500' : 'border-slate-200 hover:bg-slate-50'" class="p-2.5 rounded-xl border text-center transition">
                            <i class="fas fa-shield-alt text-rose-500 text-base mb-1 block"></i>
                            <span class="text-[11px] font-bold text-slate-800 block">SSL Wireless</span>
                            <span class="text-[9px] text-slate-400 font-mono">PushSMS</span>
                        </div>
                    </label>

                    <!-- Custom HTTP Webhook -->
                    <label class="cursor-pointer">
                        <input type="radio" name="sms_provider" value="custom_http" x-model="selectedProvider" class="sr-only">
                        <div :class="selectedProvider === 'custom_http' ? 'border-emerald-500 bg-emerald-50/50 shadow-xs ring-1 ring-emerald-500' : 'border-slate-200 hover:bg-slate-50'" class="p-2.5 rounded-xl border text-center transition">
                            <i class="fas fa-code text-slate-600 text-base mb-1 block"></i>
                            <span class="text-[11px] font-bold text-slate-800 block">Custom HTTP</span>
                            <span class="text-[9px] text-slate-400 font-mono">GET / POST</span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Provider Parameters Card -->
            <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs divide-y divide-slate-100">
                <div class="p-3.5 flex items-center justify-between bg-slate-50/50 rounded-t-xl">
                    <span class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                        <i class="fas fa-key text-emerald-600"></i>
                        API Gateway Authentication Parameters
                    </span>
                    <span class="text-[10px] text-slate-400 font-mono">Selected: <strong class="text-emerald-700 uppercase" x-text="selectedProvider"></strong></span>
                </div>

                <div class="p-4 space-y-3.5 text-xs">
                    
                    <!-- Provider Info Alert for BulkSMS BD -->
                    <div x-show="selectedProvider === 'bulksmsbd'" class="p-3 bg-emerald-50/70 border border-emerald-200 rounded-lg text-slate-700 space-y-1">
                        <div class="flex items-center gap-1.5 font-bold text-xs text-emerald-900">
                            <i class="fas fa-circle-check text-emerald-600"></i>
                            <span>Official BulkSMS BD API Integration Active</span>
                        </div>
                        <p class="text-[10.5px] text-slate-600">Endpoint: <code class="bg-white px-1 py-0.5 rounded border border-emerald-200 font-mono text-emerald-800 font-semibold">http://bulksmsbd.net/api/smsapi</code> (Single/Comma) & <code class="bg-white px-1 py-0.5 rounded border border-emerald-200 font-mono text-emerald-800 font-semibold">http://bulksmsbd.net/api/smsapimany</code> (Batch)</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Approved Sender ID (Masking / Non-Masking) *</label>
                            <input type="text" name="sms_sender_id" value="{{ $settings['sms_sender_id'] ?? 'SomitySoft' }}" placeholder="e.g. 8809612XXXXXX or SomitySoft" required class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-emerald-500 font-bold">
                            <span class="text-[10px] text-slate-400 mt-0.5 block">Approved Sender ID allocated by your SMS provider (BTRC registered)</span>
                        </div>

                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">API Key / Token *</label>
                            <input type="text" name="sms_api_key" value="{{ $settings['sms_api_key'] ?? '' }}" placeholder="Enter your provider API Key" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-emerald-500 font-mono">
                            <span class="text-[10px] text-slate-400 mt-0.5 block">API Key obtained from BulkSMS BD / provider dashboard</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 pt-1 border-t border-slate-100">
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-[11px] font-semibold text-slate-700">API Secret / Password (If Required)</label>
                                <button type="button" @click="showSecret = !showSecret" class="text-[10px] text-emerald-600 hover:text-emerald-700 font-medium">
                                    <span x-text="showSecret ? 'Hide Secret' : 'Show Secret'"></span>
                                </button>
                            </div>
                            <input :type="showSecret ? 'text' : 'password'" name="sms_api_secret" value="{{ $settings['sms_api_secret'] ?? '' }}" placeholder="••••••••••••••••" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-emerald-500 font-mono">
                        </div>

                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Client ID / Stakeholder SID (Optional)</label>
                            <input type="text" name="sms_client_id" value="{{ $settings['sms_client_id'] ?? '' }}" placeholder="Optional Client ID" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-emerald-500 font-mono">
                        </div>
                    </div>

                    <!-- Custom HTTP Endpoint (Visible when custom_http is selected) -->
                    <div x-show="selectedProvider === 'custom_http'" class="pt-2 border-t border-slate-100 space-y-3" x-cloak>
                        <div class="p-3 bg-slate-50 rounded-lg border border-slate-200/80">
                            <span class="text-xs font-bold text-slate-800 block mb-1">Custom HTTP REST Webhook Endpoint</span>
                            <input type="text" name="sms_custom_url" value="{{ $settings['sms_custom_url'] ?? 'https://api.sms-provider.com/send?to={number}&msg={message}&key={api_key}&senderid={sender_id}' }}" placeholder="https://api.sms-provider.com/send?to={number}&msg={message}&key={api_key}&senderid={sender_id}" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-emerald-500 font-mono">
                            <span class="text-[10.5px] text-slate-500 mt-1 block">Supported tags: <code class="text-emerald-700">{number}</code>, <code class="text-emerald-700">{message}</code>, <code class="text-emerald-700">{api_key}</code>, <code class="text-emerald-700">{sender_id}</code></span>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- TAB 2: Automated Notification Event Triggers -->
        <div x-show="activeTab === 'event_triggers'" class="space-y-4" x-cloak>
            <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs divide-y divide-slate-100">
                <div class="p-3.5 flex items-center justify-between bg-slate-50/50 rounded-t-xl">
                    <span class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                        <i class="fas fa-bell text-emerald-600"></i>
                        Automated System Event Dispatches
                    </span>
                    <span class="text-[10px] text-slate-400">Trigger automated SMS alerts</span>
                </div>

                <div class="p-4 space-y-3 text-xs">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        
                        <!-- Trigger 1: Tenant Provisioning -->
                        <div class="p-3 rounded-lg border border-slate-200/80 hover:bg-slate-50/50 transition flex items-start justify-between gap-3">
                            <div class="space-y-0.5">
                                <span class="font-bold text-slate-800 text-xs flex items-center gap-1.5">
                                    <i class="fas fa-building text-blue-600"></i>
                                    New ISP Tenant Welcome SMS
                                </span>
                                <p class="text-[10.5px] text-slate-500">Sends admin credentials & portal link when an ISP organization is registered.</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer mt-0.5">
                                <input type="checkbox" name="sms_trigger_new_tenant" value="1" {{ ($settings['sms_trigger_new_tenant'] ?? '1') == '1' ? 'checked' : '' }} class="sr-only peer">
                                <div class="w-8 h-4.5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-3.5 after:w-3.5 after:transition-all peer-checked:bg-emerald-600"></div>
                            </label>
                        </div>

                        <!-- Trigger 2: Monthly Invoice -->
                        <div class="p-3 rounded-lg border border-slate-200/80 hover:bg-slate-50/50 transition flex items-start justify-between gap-3">
                            <div class="space-y-0.5">
                                <span class="font-bold text-slate-800 text-xs flex items-center gap-1.5">
                                    <i class="fas fa-file-invoice-dollar text-emerald-600"></i>
                                    Monthly Subscription Invoice Notice
                                </span>
                                <p class="text-[10.5px] text-slate-500">Alerts ISP owners with invoice amount & bKash/Nagad payment link.</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer mt-0.5">
                                <input type="checkbox" name="sms_trigger_invoice_generated" value="1" {{ ($settings['sms_trigger_invoice_generated'] ?? '1') == '1' ? 'checked' : '' }} class="sr-only peer">
                                <div class="w-8 h-4.5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-3.5 after:w-3.5 after:transition-all peer-checked:bg-emerald-600"></div>
                            </label>
                        </div>

                        <!-- Trigger 3: Payment Receipt -->
                        <div class="p-3 rounded-lg border border-slate-200/80 hover:bg-slate-50/50 transition flex items-start justify-between gap-3">
                            <div class="space-y-0.5">
                                <span class="font-bold text-slate-800 text-xs flex items-center gap-1.5">
                                    <i class="fas fa-receipt text-indigo-600"></i>
                                    Instant Payment Confirmation Receipt
                                </span>
                                <p class="text-[10.5px] text-slate-500">Sends TrxID and remaining SaaS subscription validity confirmation.</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer mt-0.5">
                                <input type="checkbox" name="sms_trigger_payment_received" value="1" {{ ($settings['sms_trigger_payment_received'] ?? '1') == '1' ? 'checked' : '' }} class="sr-only peer">
                                <div class="w-8 h-4.5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-3.5 after:w-3.5 after:transition-all peer-checked:bg-emerald-600"></div>
                            </label>
                        </div>

                        <!-- Trigger 4: Expiry Due Alert -->
                        <div class="p-3 rounded-lg border border-slate-200/80 hover:bg-slate-50/50 transition flex items-start justify-between gap-3">
                            <div class="space-y-0.5">
                                <span class="font-bold text-slate-800 text-xs flex items-center gap-1.5">
                                    <i class="fas fa-triangle-exclamation text-amber-500"></i>
                                    Subscription Expiry & Suspension Warning
                                </span>
                                <p class="text-[10.5px] text-slate-500">Dispatches reminder 3 days prior to tenant account auto-suspension.</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer mt-0.5">
                                <input type="checkbox" name="sms_trigger_expiry_alert" value="1" {{ ($settings['sms_trigger_expiry_alert'] ?? '1') == '1' ? 'checked' : '' }} class="sr-only peer">
                                <div class="w-8 h-4.5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-3.5 after:w-3.5 after:transition-all peer-checked:bg-emerald-600"></div>
                            </label>
                        </div>

                        <!-- Trigger 5: 2FA OTP -->
                        <div class="p-3 rounded-lg border border-slate-200/80 hover:bg-slate-50/50 transition flex items-start justify-between gap-3">
                            <div class="space-y-0.5">
                                <span class="font-bold text-slate-800 text-xs flex items-center gap-1.5">
                                    <i class="fas fa-key text-rose-600"></i>
                                    2FA Security OTP & Password Reset
                                </span>
                                <p class="text-[10.5px] text-slate-500">Delivers high-priority 6-digit authentication verification codes.</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer mt-0.5">
                                <input type="checkbox" name="sms_trigger_otp_2fa" value="1" {{ ($settings['sms_trigger_otp_2fa'] ?? '1') == '1' ? 'checked' : '' }} class="sr-only peer">
                                <div class="w-8 h-4.5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-3.5 after:w-3.5 after:transition-all peer-checked:bg-emerald-600"></div>
                            </label>
                        </div>

                        <!-- Trigger 6: Network Alerts -->
                        <div class="p-3 rounded-lg border border-slate-200/80 hover:bg-slate-50/50 transition flex items-start justify-between gap-3">
                            <div class="space-y-0.5">
                                <span class="font-bold text-slate-800 text-xs flex items-center gap-1.5">
                                    <i class="fas fa-tower-cell text-cyan-600"></i>
                                    Emergency Network & Downtime Broadcast
                                </span>
                                <p class="text-[10.5px] text-slate-500">Broadcasts emergency optical fiber cut or core network alert to NOCs.</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer mt-0.5">
                                <input type="checkbox" name="sms_trigger_network_alert" value="1" {{ ($settings['sms_trigger_network_alert'] ?? '0') == '1' ? 'checked' : '' }} class="sr-only peer">
                                <div class="w-8 h-4.5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-3.5 after:w-3.5 after:transition-all peer-checked:bg-emerald-600"></div>
                            </label>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 3: Message Templates & Tags -->
        <div x-show="activeTab === 'sms_templates'" class="space-y-4" x-cloak>
            <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs divide-y divide-slate-100">
                <div class="p-3.5 flex items-center justify-between bg-slate-50/50 rounded-t-xl">
                    <span class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                        <i class="fas fa-file-code text-emerald-600"></i>
                        Automated SMS Notification Templates
                    </span>
                    <span class="text-[10px] text-slate-400">Dynamic Variable Tags</span>
                </div>

                <div class="p-4 space-y-4 text-xs">
                    
                    <div class="p-3 bg-slate-50 rounded-lg border border-slate-200/80 text-[11px] space-y-1">
                        <span class="font-bold text-slate-800 flex items-center gap-1.5">
                            <i class="fas fa-tags text-blue-500"></i>
                            Supported Dynamic Template Tags:
                        </span>
                        <div class="flex flex-wrap gap-1.5 pt-1">
                            <span class="px-1.5 py-0.5 bg-white rounded border border-slate-200 font-mono text-[10.5px] text-emerald-700">{tenant_name}</span>
                            <span class="px-1.5 py-0.5 bg-white rounded border border-slate-200 font-mono text-[10.5px] text-emerald-700">{invoice_id}</span>
                            <span class="px-1.5 py-0.5 bg-white rounded border border-slate-200 font-mono text-[10.5px] text-emerald-700">{amount}</span>
                            <span class="px-1.5 py-0.5 bg-white rounded border border-slate-200 font-mono text-[10.5px] text-emerald-700">{due_date}</span>
                            <span class="px-1.5 py-0.5 bg-white rounded border border-slate-200 font-mono text-[10.5px] text-emerald-700">{otp_code}</span>
                            <span class="px-1.5 py-0.5 bg-white rounded border border-slate-200 font-mono text-[10.5px] text-emerald-700">{company_name}</span>
                            <span class="px-1.5 py-0.5 bg-white rounded border border-slate-200 font-mono text-[10.5px] text-emerald-700">{portal_url}</span>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Invoice Generated SMS Template</label>
                            <textarea name="sms_template_invoice" rows="2" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-emerald-500 font-mono">{{ $settings['sms_template_invoice'] ?? 'Dear {tenant_name}, your SaaS subscription invoice #{invoice_id} of BDT {amount} is ready. Due date: {due_date}. Pay at {portal_url}' }}</textarea>
                        </div>

                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Payment Receipt SMS Template</label>
                            <textarea name="sms_template_payment" rows="2" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-emerald-500 font-mono">{{ $settings['sms_template_payment'] ?? 'Thank you {tenant_name}! We received payment of BDT {amount} for #{invoice_id}. Your SaaS plan is active until {due_date}.' }}</textarea>
                        </div>

                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">2FA OTP Authentication SMS Template</label>
                            <textarea name="sms_template_otp" rows="2" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-emerald-500 font-mono">{{ $settings['sms_template_otp'] ?? 'Your {company_name} verification security code is: {otp_code}. Valid for 5 minutes. Do not share.' }}</textarea>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- Global Save Button Strip -->
        <div x-show="activeTab !== 'sms_logs' && activeTab !== 'api_docs' && activeTab !== 'test_dispatcher'" class="flex items-center justify-between bg-white p-3.5 rounded-xl border border-slate-200/80 shadow-xs">
            <span class="text-[11px] text-slate-500">
                <i class="fas fa-info-circle text-emerald-600 mr-1"></i>
                Gateway configurations and trigger settings take effect immediately.
            </span>
            <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5">
                <i class="fas fa-save text-[11px]"></i>
                <span>Save SMS Settings</span>
            </button>
        </div>

    </form>

    <!-- TAB 4: Outgoing SMS Delivery Audit Logs -->
    <div x-show="activeTab === 'sms_logs'" class="space-y-4" x-cloak>
        
        <!-- SMS Analytics Bar -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div class="p-3 bg-white rounded-xl border border-slate-200/80 shadow-xs flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Total Dispatched</span>
                    <span class="text-sm font-extrabold text-slate-800 font-mono">{{ number_format($totalSmsCount ?? 0) }} SMS</span>
                </div>
                <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-xs">
                    <i class="fas fa-paper-plane"></i>
                </div>
            </div>

            <div class="p-3 bg-white rounded-xl border border-slate-200/80 shadow-xs flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider block">Total Incurred Cost</span>
                    <span class="text-sm font-extrabold text-emerald-700 font-mono">@currency($totalSmsCost ?? 0)</span>
                </div>
                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-xs">
                    <i class="fas fa-coins"></i>
                </div>
            </div>

            <div class="p-3 bg-white rounded-xl border border-slate-200/80 shadow-xs flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Delivered Rate</span>
                    <span class="text-sm font-extrabold text-emerald-600 font-mono">{{ number_format($deliveredSmsCount ?? 0) }}</span>
                </div>
                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-xs">
                    <i class="fas fa-check-double"></i>
                </div>
            </div>

            <div class="p-3 bg-white rounded-xl border border-slate-200/80 shadow-xs flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Failed / Errors</span>
                    <span class="text-sm font-extrabold text-rose-600 font-mono">{{ number_format($failedSmsCount ?? 0) }}</span>
                </div>
                <div class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center text-xs">
                    <i class="fas fa-circle-exclamation"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs divide-y divide-slate-100">
            <div class="p-3.5 flex items-center justify-between bg-slate-50/50 rounded-t-xl">
                <span class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                    <i class="fas fa-list-check text-emerald-600"></i>
                    Live SMS Consumption & Transmission Audit Ledger
                </span>
                <span class="text-[10px] text-slate-400 font-mono">{{ $realSmsLogs->total() ?? 0 }} Total Entries</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/40 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                            <th class="py-2.5 px-4">Recipient Phone</th>
                            <th class="py-2.5 px-4">ISP Tenant</th>
                            <th class="py-2.5 px-4">Event Type & Content Snippet</th>
                            <th class="py-2.5 px-4">Gateway</th>
                            <th class="py-2.5 px-4">Parts & Cost</th>
                            <th class="py-2.5 px-4">Status</th>
                            <th class="py-2.5 px-4 text-right">Dispatched At</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700 font-mono">
                        @forelse($realSmsLogs as $log)
                        <tr class="hover:bg-slate-50/80 transition text-[11px]">
                            <td class="py-2.5 px-4 font-bold text-slate-900">
                                {{ $log->recipient_phone }}
                            </td>
                            <td class="py-2.5 px-4 font-sans font-semibold text-slate-800">
                                {{ $log->tenant->name ?? 'System / Direct' }}
                            </td>
                            <td class="py-2.5 px-4 font-sans">
                                <span class="font-bold text-slate-800 text-[10.5px] uppercase block">{{ str_replace('_', ' ', $log->sms_type) }}</span>
                                <span class="text-[10.5px] text-slate-500 truncate max-w-xs block font-mono" title="{{ $log->message_body }}">
                                    {{ Str::limit($log->message_body, 45) }}
                                </span>
                            </td>
                            <td class="py-2.5 px-4 text-[10.5px] text-slate-600">
                                {{ $log->gateway_name }}
                            </td>
                            <td class="py-2.5 px-4 text-[10.5px]">
                                <span class="text-slate-800">{{ $log->parts_count }} Part(s)</span>
                                <span class="text-emerald-700 font-bold block">@currency($log->total_cost)</span>
                            </td>
                            <td class="py-2.5 px-4">
                                @if($log->status === 'delivered')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[9.5px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <i class="fas fa-check-circle text-[8px] mr-1"></i> Delivered
                                    </span>
                                @elseif($log->status === 'sent')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[9.5px] font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                                        <i class="fas fa-paper-plane text-[8px] mr-1"></i> Sent
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[9.5px] font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                        <i class="fas fa-times-circle text-[8px] mr-1"></i> Failed
                                    </span>
                                @endif
                            </td>
                            <td class="py-2.5 px-4 text-right text-[10.5px] text-slate-400">
                                {{ $log->created_at ? $log->created_at->format('d M, h:i A') : '—' }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400 font-sans text-xs">
                                No SMS transmission logs found.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($realSmsLogs->hasPages())
                <div class="p-3 bg-slate-50/50">
                    {{ $realSmsLogs->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- TAB 5: Official API Code Snippets & Specification Docs -->
    <div x-show="activeTab === 'api_docs'" class="space-y-4" x-cloak>
        <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs divide-y divide-slate-100">
            <div class="p-3.5 flex items-center justify-between bg-slate-50/50 rounded-t-xl">
                <span class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                    <i class="fas fa-code text-emerald-600"></i>
                    Official Provider cURL & PHP API Specifications
                </span>
                <span class="text-[10px] text-slate-400">BulkSMS BD Native Format</span>
            </div>

            <div class="p-4 space-y-4 text-xs">
                
                <!-- 1. Single / Comma Separated Dispatch API -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-slate-800 text-xs flex items-center gap-1.5">
                            <span class="px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-800 font-mono text-[10px] font-bold">POST</span>
                            1. Single or Comma-Separated Number Dispatch (<code class="text-emerald-700">smsapi</code>)
                        </span>
                        <span class="text-[10px] font-mono text-slate-400">Endpoint: http://bulksmsbd.net/api/smsapi</span>
                    </div>

                    <pre class="bg-slate-900 text-slate-100 p-3.5 rounded-xl font-mono text-[11px] overflow-x-auto leading-relaxed"><code>$url = "http://bulksmsbd.net/api/smsapi";
$data = [
    "api_key"  => "{{ $settings['sms_api_key'] ?? 'YOUR_API_KEY' }}",
    "senderid" => "{{ $settings['sms_sender_id'] ?? 'SomitySoft' }}",
    "number"   => "88017XXXXXXXX,88018XXXXXXXX",
    "message"  => "Your SomitySoft Invoice #INV-001 of BDT 2,500 is ready."
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
curl_close($ch);</code></pre>
                </div>

                <!-- 2. Multiple Custom Content Dispatch API -->
                <div class="space-y-2 pt-2 border-t border-slate-100">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-slate-800 text-xs flex items-center gap-1.5">
                            <span class="px-1.5 py-0.5 rounded bg-blue-100 text-blue-800 font-mono text-[10px] font-bold">POST</span>
                            2. Multiple Custom Content Batch Dispatch (<code class="text-blue-700">smsapimany</code>)
                        </span>
                        <span class="text-[10px] font-mono text-slate-400">Endpoint: http://bulksmsbd.net/api/smsapimany</span>
                    </div>

                    <pre class="bg-slate-900 text-slate-100 p-3.5 rounded-xl font-mono text-[11px] overflow-x-auto leading-relaxed"><code>$url = "http://bulksmsbd.net/api/smsapimany";
$messages = json_encode([
    [ "to" => "88017XXXXXXXX", "message" => "Invoice for SpeedNet ISP" ],
    [ "to" => "88018XXXXXXXX", "message" => "Invoice for FastFiber BD" ]
]);

$data = [
    "api_key"  => "{{ $settings['sms_api_key'] ?? 'YOUR_API_KEY' }}",
    "senderid" => "{{ $settings['sms_sender_id'] ?? 'SomitySoft' }}",
    "messages" => $messages
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
curl_close($ch);</code></pre>
                </div>

                <!-- 3. Credit Balance API PHP Source Code -->
                <div class="space-y-2 pt-2 border-t border-slate-100">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-slate-800 text-xs flex items-center gap-1.5">
                            <span class="px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 font-mono text-[10px] font-bold">GET & POST</span>
                            3. Credit Balance API Source Code (<code class="text-amber-700">getBalanceApi</code>)
                        </span>
                        <span class="text-[10px] font-mono text-slate-400">Endpoint: http://bulksmsbd.net/api/getBalanceApi</span>
                    </div>

                    <pre class="bg-slate-900 text-slate-100 p-3.5 rounded-xl font-mono text-[11px] overflow-x-auto leading-relaxed"><code>$url = "http://bulksmsbd.net/api/getBalanceApi?api_key={{ $settings['sms_api_key'] ?? 'YOUR_API_KEY' }}";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
curl_close($ch);

// Response format: {"response_code": 202, "balance": "1500.00"}
$data = json_decode($response, true);
$balance = $data['balance'] ?? $response;
echo "Available Balance: BDT " . $balance;</code></pre>
                </div>

            </div>
        </div>
    </div>

    <!-- TAB 6: Live Interactive Test SMS Dispatcher -->
    <div x-show="activeTab === 'test_dispatcher'" class="space-y-4" x-cloak>
        <div class="max-w-2xl mx-auto bg-white rounded-xl border border-slate-200/80 shadow-xs divide-y divide-slate-100">
            <div class="p-3.5 flex items-center justify-between bg-slate-50/50 rounded-t-xl">
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded-md bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-xs">
                        <i class="fas fa-paper-plane text-[10px]"></i>
                    </div>
                    <span class="text-xs font-bold text-slate-800">Live SMS Dispatcher & Cost Estimator</span>
                </div>
                <span class="text-[10px] text-slate-400">Real-Time Mobile Carrier Test</span>
            </div>

            <form action="{{ route('owner.sms-gateways.test') }}" method="POST" class="p-4 space-y-3.5 text-xs">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">Destination Mobile Number *</label>
                        <input type="text" name="test_phone" placeholder="01712XXXXXX" required class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-emerald-500 font-mono">
                        <span class="text-[10px] text-slate-400 mt-0.5 block">Accepts 017..., 88017..., or +88017... formats</span>
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">Dispatch Gateway Provider</label>
                        <select name="test_provider" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-emerald-500 font-semibold">
                            <option value="bulksmsbd">BulkSMS BD (bulksmsbd.net)</option>
                            <option value="greenweb">GreenWeb BD API</option>
                            <option value="onnorokom">Onnorokom SMS</option>
                            <option value="mim_sms">MIM SMS</option>
                            <option value="elitbuzz">ElitBuzz SMS</option>
                        </select>
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-[11px] font-semibold text-slate-700">Message Content *</label>
                        <div class="text-[10px] font-mono text-slate-500">
                            <span class="font-bold text-slate-700" x-text="calcChars().len"></span> chars • 
                            <span class="font-bold text-emerald-600" x-text="calcChars().parts + ' SMS Part(s)'"></span>
                            <span class="text-slate-400" x-text="calcChars().isUnicode ? '(Unicode / Bangla 70c)' : '(GSM-7 / English 160c)'"></span>
                        </div>
                    </div>
                    <textarea name="test_message" x-model="testMsg" rows="3" required class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-emerald-500"></textarea>
                </div>

                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/80 space-y-1 text-[11px]">
                    <span class="font-bold text-slate-800 flex items-center gap-1.5">
                        <i class="fas fa-info-circle text-emerald-600"></i>
                        Carrier Transmission Notes:
                    </span>
                    <p class="text-[10.5px] text-slate-600">This test dispatches a real SMS payload through your configured gateway token. Ensure your provider account has sufficient SMS credit balance.</p>
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center justify-center gap-1.5">
                        <i class="fas fa-paper-plane text-[11px]"></i>
                        <span>Dispatch Test SMS Payload</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
