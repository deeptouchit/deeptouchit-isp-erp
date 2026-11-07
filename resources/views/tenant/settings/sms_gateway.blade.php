@extends('tenant.layouts.app')

@section('title', 'SMS & Notification Settings - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
    {{-- Page-specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" x-data="notificationManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">
    
    {{-- 1. TOP HEADER BAR (AGENTS.md Rule 2.A - Title + Icon + Action Buttons ONLY) --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 bg-white p-3 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-sm shadow-2xs flex-shrink-0">
                <i class="fas fa-comment-sms"></i>
            </div>
            <h1 class="text-sm font-bold text-slate-800 tracking-tight">SMS &amp; Notification Settings</h1>
        </div>
        <div class="flex items-center gap-1.5 flex-wrap">
            <button type="button" @click="openAddGatewayModal()" class="h-[34px] py-0 px-3 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-plus text-xs"></i>
                <span>Add Gateway</span>
            </button>
            <button type="button" @click="testModalOpen = true" class="h-[34px] py-0 px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-paper-plane text-slate-500 text-xs"></i>
                <span>Test Dispatch</span>
            </button>
            <a href="{{ route('tenant.settings.sms-gateway.print') }}" target="_blank" class="h-[34px] py-0 px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-print text-slate-500 text-xs"></i>
                <span>Print Policy</span>
            </a>
        </div>
    </div>

    {{-- 2. KPI SUMMARY STRIP (Strictly 6 Cards - AGENTS.md Rule 2.B) --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        {{-- Card 1: SMS Credits Balance --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">SMS Balance</span>
                <span class="text-[13px] font-bold font-mono text-emerald-600 leading-tight block">
                    @currency($stats['sms_balance'])
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-emerald-200 bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-wallet"></i>
            </div>
        </div>

        {{-- Card 2: Total Dispatched --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Total Sent</span>
                <span class="text-[13px] font-bold font-mono text-slate-800 leading-tight block">
                    {{ number_format($stats['total_sent']) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-slate-200 bg-slate-50 text-slate-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-paper-plane"></i>
            </div>
        </div>

        {{-- Card 3: Delivery Success Rate --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Delivery Rate</span>
                <span class="text-[13px] font-bold font-mono text-cyan-700 leading-tight block">
                    {{ $stats['delivery_rate'] }}%
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-cyan-200 bg-cyan-50 text-cyan-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-circle-check"></i>
            </div>
        </div>

        {{-- Card 4: Approved Sender Masking --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Sender Masking</span>
                <span class="text-[13px] font-bold font-mono text-indigo-700 leading-tight block truncate">
                    {{ $stats['sender_masking'] }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-indigo-200 bg-indigo-50 text-indigo-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-id-badge"></i>
            </div>
        </div>

        {{-- Card 5: Active Templates --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Active Triggers</span>
                <span class="text-[13px] font-bold font-mono text-purple-700 leading-tight block truncate">
                    {{ $stats['active_templates'] }} Ready
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-purple-200 bg-purple-50 text-purple-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-bolt"></i>
            </div>
        </div>

        {{-- Card 6: Connected Gateways --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Gateways</span>
                <span class="text-[13px] font-bold font-mono text-blue-700 leading-tight block truncate">
                    {{ $stats['active_gateways'] }} Active
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-blue-200 bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-tower-broadcast"></i>
            </div>
        </div>
    </div>

    {{-- 3. NAVIGATION TABS (3 Production Modules) --}}
    <div class="flex items-center gap-1 border-b border-slate-200 bg-white px-3 pt-2 rounded-t-xl overflow-x-auto">
        <button type="button" 
                @click="activeTab = 'templates'"
                class="h-[36px] px-3.5 text-xs font-semibold rounded-t-lg transition border-b-2 cursor-pointer flex items-center gap-1.5 whitespace-nowrap"
                :class="activeTab === 'templates' ? 'border-cyan-600 text-cyan-700 bg-cyan-50/50' : 'border-transparent text-slate-600 hover:text-slate-900 hover:bg-slate-50'">
            <i class="fas fa-envelope-open-text text-xs"></i>
            <span>1. Notification Templates &amp; Alerts ({{ $templates->count() }})</span>
        </button>
        <button type="button" 
                @click="activeTab = 'gateways'"
                class="h-[36px] px-3.5 text-xs font-semibold rounded-t-lg transition border-b-2 cursor-pointer flex items-center gap-1.5 whitespace-nowrap"
                :class="activeTab === 'gateways' ? 'border-cyan-600 text-cyan-700 bg-cyan-50/50' : 'border-transparent text-slate-600 hover:text-slate-900 hover:bg-slate-50'">
            <i class="fas fa-tower-broadcast text-xs"></i>
            <span>2. Gateway Routes &amp; Providers ({{ $gateways->count() }})</span>
        </button>
        <button type="button" 
                @click="activeTab = 'logs'"
                class="h-[36px] px-3.5 text-xs font-semibold rounded-t-lg transition border-b-2 cursor-pointer flex items-center gap-1.5 whitespace-nowrap"
                :class="activeTab === 'logs' ? 'border-cyan-600 text-cyan-700 bg-cyan-50/50' : 'border-transparent text-slate-600 hover:text-slate-900 hover:bg-slate-50'">
            <i class="fas fa-clock-rotate-left text-xs"></i>
            <span>3. Outbound Delivery Logs ({{ $logs->total() }})</span>
        </button>
    </div>

    {{-- TAB 1: NOTIFICATION TEMPLATES TABLE --}}
    <div x-show="activeTab === 'templates'" class="space-y-3">
        <div class="bg-white rounded-b-xl border border-t-0 border-slate-200 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="saas-table">
                    <thead>
                        <tr>
                            <th class="w-10 text-center font-semibold">#</th>
                            <th class="text-center font-semibold">Event Trigger</th>
                            <th class="text-center font-semibold">SMS Channel</th>
                            <th class="text-center font-semibold">WhatsApp Channel</th>
                            <th class="text-center font-semibold">Message Preview</th>
                            <th class="text-center font-semibold">Status</th>
                            <th class="w-20 text-center no-sort font-semibold">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($templates as $idx => $t)
                            <tr>
                                <td class="text-center text-slate-400 font-mono">{{ $idx + 1 }}</td>
                                
                                {{-- Trigger Name --}}
                                <td class="text-left font-bold text-slate-800">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-md bg-cyan-50 text-cyan-700 border border-cyan-100 flex items-center justify-center text-[10px] flex-shrink-0">
                                            <i class="fas fa-message"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <span class="block truncate">{{ $t->title }}</span>
                                            <span class="text-[10px] text-slate-400 font-mono block">{{ $t->event_trigger }}</span>
                                        </div>
                                    </div>
                                </td>

                                {{-- SMS Channel Status --}}
                                <td class="text-center">
                                    @if($t->send_sms)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold border border-emerald-200 bg-emerald-50 text-emerald-700 whitespace-nowrap">
                                            <i class="fas fa-check text-[9px]"></i> SMS
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium text-slate-400 bg-slate-100 whitespace-nowrap">
                                            OFF
                                        </span>
                                    @endif
                                </td>

                                {{-- WhatsApp Channel Status --}}
                                <td class="text-center">
                                    @if($t->send_whatsapp)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold border border-green-200 bg-green-50 text-green-700 whitespace-nowrap">
                                            <i class="fab fa-whatsapp text-[10px]"></i> WA
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium text-slate-400 bg-slate-100 whitespace-nowrap">
                                            OFF
                                        </span>
                                    @endif
                                </td>

                                {{-- Message Body Preview --}}
                                <td class="text-left text-slate-600 text-xs truncate max-w-md font-sans" title="{{ $t->sms_body }}">
                                    {{ $t->sms_body }}
                                </td>

                                {{-- Status Badge --}}
                                <td class="text-center">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $t->is_active ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-slate-50 text-slate-500' }} whitespace-nowrap">
                                        <span>{{ $t->is_active ? 'ACTIVE' : 'DISABLED' }}</span>
                                    </span>
                                </td>

                                {{-- Action --}}
                                <td class="text-center">
                                    <button type="button" 
                                            @click="openEditTemplateModal({{ json_encode($t) }})"
                                            class="h-[28px] px-2.5 bg-cyan-50 hover:bg-cyan-100 text-cyan-700 rounded-md text-xs font-semibold border border-cyan-200 transition inline-flex items-center justify-center gap-1 mx-auto cursor-pointer">
                                        <i class="fas fa-pen-to-square text-[10px]"></i>
                                        <span>Edit</span>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-10 text-slate-400">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 mb-2">
                                            <i class="fas fa-envelope-open-text text-base"></i>
                                        </div>
                                        <span class="text-xs font-semibold text-slate-600">No Notification Templates Configured</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- TAB 2: CONFIGURABLE GATEWAY ROUTES TABLE --}}
    <div x-show="activeTab === 'gateways'" class="space-y-3">
        <div class="bg-white rounded-b-xl border border-t-0 border-slate-200 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="saas-table">
                    <thead>
                        <tr>
                            <th class="w-10 text-center font-semibold">#</th>
                            <th class="text-center font-semibold">Gateway Name</th>
                            <th class="text-center font-semibold">Channel</th>
                            <th class="text-center font-semibold">Provider Engine</th>
                            <th class="text-center font-semibold">Sender ID / Masking</th>
                            <th class="text-center font-semibold">Unit Rate</th>
                            <th class="text-center font-semibold">Credit Balance</th>
                            <th class="text-center font-semibold">Default</th>
                            <th class="text-center font-semibold">Status</th>
                            <th class="w-16 text-center no-sort font-semibold">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($gateways as $idx => $g)
                            <tr>
                                <td class="text-center text-slate-400 font-mono">{{ $idx + 1 }}</td>
                                
                                {{-- Name --}}
                                <td class="text-left font-bold text-slate-800">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-md bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center text-[10px] flex-shrink-0">
                                            <i class="fas {{ $g->channel_type === 'whatsapp' ? 'fa-brands fa-whatsapp' : 'fa-tower-broadcast' }}"></i>
                                        </div>
                                        <span>{{ $g->name }}</span>
                                    </div>
                                </td>

                                {{-- Channel --}}
                                <td class="text-center">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold font-mono uppercase {{ $g->channel_type === 'whatsapp' ? 'border border-green-200 bg-green-50 text-green-700' : 'border border-cyan-200 bg-cyan-50 text-cyan-700' }} whitespace-nowrap">
                                        {{ $g->channel_type }}
                                    </span>
                                </td>

                                {{-- Provider Engine --}}
                                <td class="text-center font-mono text-xs text-slate-700">
                                    {{ strtoupper($g->provider) }}
                                </td>

                                {{-- Sender ID --}}
                                <td class="text-center font-mono font-bold text-indigo-700 text-xs">
                                    {{ $g->sender_id ?: 'Non-Masking' }}
                                </td>

                                {{-- Unit Rate --}}
                                <td class="text-right font-mono text-xs font-semibold text-slate-800">
                                    @currency($g->cost_per_sms ?? 0.35)
                                </td>

                                {{-- Balance --}}
                                <td class="text-right font-mono text-xs font-bold text-emerald-600">
                                    @currency($g->balance ?? 0.00)
                                </td>

                                {{-- Is Default --}}
                                <td class="text-center">
                                    @if($g->is_default)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200 whitespace-nowrap">
                                            <i class="fas fa-star text-[9px]"></i> Primary
                                        </span>
                                    @else
                                        <span class="text-[10px] text-slate-400">Secondary</span>
                                    @endif
                                </td>

                                {{-- Status --}}
                                <td class="text-center">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $g->is_active ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-slate-50 text-slate-500' }} whitespace-nowrap">
                                        {{ $g->is_active ? 'ACTIVE' : 'DISABLED' }}
                                    </span>
                                </td>

                                {{-- Action Dropdown (AGENTS.md Rule 2.E) --}}
                                <td class="text-center">
                                    <div class="relative inline-block text-left">
                                        <button type="button" 
                                                @click="toggleGatewayMenu({{ json_encode($g) }}, $event)"
                                                class="w-7 h-7 rounded-lg hover:bg-slate-100 text-slate-500 hover:text-slate-800 transition flex items-center justify-center mx-auto cursor-pointer"
                                                title="Actions">
                                            <i class="fas fa-ellipsis-v text-[10px]"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-10 text-slate-400">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 mb-2">
                                            <i class="fas fa-tower-broadcast text-base"></i>
                                        </div>
                                        <span class="text-xs font-semibold text-slate-600">No Gateway Routes Configured</span>
                                        <p class="text-[11px] text-slate-400 mt-1">Connect Greenweb, BulkSMS BD, or Twilio to automate notifications.</p>
                                        <button type="button" @click="openAddGatewayModal()" class="mt-3 h-[30px] px-3 bg-cyan-600 hover:bg-cyan-700 text-white font-medium text-xs rounded-lg transition inline-flex items-center gap-1.5 cursor-pointer">
                                            <i class="fas fa-plus text-xs"></i>
                                            <span>Add Gateway</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- TAB 3: OUTBOUND DELIVERY LOGS --}}
    <div x-show="activeTab === 'logs'" class="space-y-3">
        {{-- Search & 2-Tier Multi-Filter Toolbar (AGENTS.md Rule 2.C) --}}
        <div class="bg-white p-2.5 rounded-xl border border-slate-200/90 shadow-2xs space-y-2.5">
            <form method="GET" action="{{ route('tenant.settings.sms-gateway') }}" class="flex flex-col md:flex-row items-stretch md:items-center gap-2">
                <input type="hidden" name="tab" value="logs">
                
                {{-- Intelligent Live Search Box --}}
                <div class="relative flex-1 min-w-[200px]">
                    <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-xs"
                       :class="searchQuery ? 'text-cyan-600 font-bold' : 'text-slate-400'"></i>
                    <input type="text" 
                           name="search" 
                           x-model="searchQuery" 
                           placeholder="Search by recipient mobile, message text..." 
                           class="h-[34px] w-full pl-8 pr-7 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    <button type="button" 
                            x-show="searchQuery && searchQuery.length > 0" 
                            @click="searchQuery = ''; $nextTick(() => $el.closest('form').submit())" 
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-xs cursor-pointer">
                        <i class="fas fa-times-circle"></i>
                    </button>
                </div>

                {{-- Status Dropdown --}}
                <div class="w-full md:w-44 flex-shrink-0">
                    <select name="status" class="h-[34px] w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition cursor-pointer">
                        <option value="all">All Delivery Statuses</option>
                        <option value="delivered" {{ $status === 'delivered' ? 'selected' : '' }}>Delivered / Success</option>
                        <option value="failed" {{ $status === 'failed' ? 'selected' : '' }}>Failed / Error</option>
                    </select>
                </div>

                {{-- Notification Type Dropdown --}}
                <div class="w-full md:w-44 flex-shrink-0">
                    <select name="type" class="h-[34px] w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition cursor-pointer">
                        <option value="all">All Channels / Types</option>
                        <option value="payment_sms" {{ ($type ?? '') === 'payment_sms' ? 'selected' : '' }}>Payment Receipts</option>
                        <option value="due_reminder" {{ ($type ?? '') === 'due_reminder' ? 'selected' : '' }}>Due Bill Reminders</option>
                        <option value="welcome" {{ ($type ?? '') === 'welcome' ? 'selected' : '' }}>Welcome SMS</option>
                        <option value="suspension" {{ ($type ?? '') === 'suspension' ? 'selected' : '' }}>Suspension Warnings</option>
                        <option value="test_sms" {{ ($type ?? '') === 'test_sms' ? 'selected' : '' }}>Test Dispatch</option>
                        <option value="whatsapp" {{ ($type ?? '') === 'whatsapp' ? 'selected' : '' }}>WhatsApp</option>
                    </select>
                </div>

                {{-- Pagination Selector --}}
                <div class="w-full md:w-28 flex-shrink-0">
                    <select name="per_page" class="h-[34px] w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition cursor-pointer">
                        <option value="15" {{ $perPage == 15 ? 'selected' : '' }}>15 / page</option>
                        <option value="20" {{ $perPage == 20 ? 'selected' : '' }}>20 / page</option>
                        <option value="30" {{ $perPage == 30 ? 'selected' : '' }}>30 / page</option>
                        <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50 / page</option>
                        <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100 / page</option>
                    </select>
                </div>

                {{-- Filter & Reset Sequence (AGENTS.md Rule 2.C) --}}
                <div class="flex items-center gap-1.5 flex-shrink-0 md:ml-auto">
                    <button type="submit" class="h-[34px] py-0 px-3 bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                        <i class="fas fa-filter text-[10px]"></i>
                        <span>Filter</span>
                    </button>
                    <a href="{{ route('tenant.settings.sms-gateway', ['tab' => 'logs']) }}" class="h-[34px] py-0 px-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer">
                        <i class="fas fa-rotate-left text-[10px]"></i>
                        <span>Reset</span>
                    </a>
                </div>
            </form>

            {{-- Bulk Export & Clear Actions Sub-Bar --}}
            <div class="pt-2 border-t border-slate-100 flex items-center justify-between">
                <span class="text-xs text-slate-500">
                    Showing {{ $logs->firstItem() ?? 0 }} to {{ $logs->lastItem() ?? 0 }} of {{ $logs->total() }} recorded outbound events
                </span>
                <div class="flex items-center gap-2">
                    <a href="{{ route('tenant.settings.sms-gateway.export') }}" class="h-[30px] px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition inline-flex items-center gap-1.5 cursor-pointer">
                        <i class="fas fa-file-csv text-emerald-600 text-xs"></i>
                        <span>Export CSV</span>
                    </a>
                    <button type="button" @click="clearAllLogs()" class="h-[30px] px-3 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-lg text-xs font-semibold border border-rose-200 transition inline-flex items-center gap-1.5 cursor-pointer">
                        <i class="fas fa-trash-can text-rose-600 text-xs"></i>
                        <span>Clear All Logs</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Master Logs Table (<table class="saas-table">) --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="saas-table">
                    <thead>
                        <tr>
                            <th class="w-10 text-center font-semibold">#</th>
                            <th class="text-center font-semibold">Recipient Mobile</th>
                            <th class="text-center font-semibold">Notification Type</th>
                            <th class="text-center font-semibold">Message Text Preview</th>
                            <th class="text-center font-semibold">Parts / Cost</th>
                            <th class="text-center font-semibold">Delivery Status</th>
                            <th class="text-center font-semibold">Dispatched At</th>
                            <th class="w-16 text-center no-sort font-semibold">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $idx => $l)
                            <tr>
                                <td class="text-center text-slate-400 font-mono">{{ $logs->firstItem() + $idx }}</td>
                                
                                {{-- Recipient Phone --}}
                                <td class="text-center font-bold text-slate-800 font-mono">
                                    {{ $l->recipient_phone }}
                                </td>

                                {{-- Type --}}
                                <td class="text-center">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold font-mono border border-slate-200 bg-slate-50 text-slate-600 whitespace-nowrap">
                                        {{ strtoupper(str_replace('_', ' ', $l->sms_type ?? 'SMS')) }}
                                    </span>
                                </td>

                                {{-- Message Body --}}
                                <td class="text-left text-slate-700 text-xs truncate max-w-sm" title="{{ $l->message_body }}">
                                    {{ $l->message_body }}
                                </td>

                                {{-- Parts / Cost --}}
                                <td class="text-right font-mono text-xs text-slate-700">
                                    <span class="text-slate-500">{{ $l->parts_count ?: 1 }}P</span>
                                    <span class="text-slate-400">/</span>
                                    <span class="font-bold text-slate-800">@currency($l->total_cost)</span>
                                </td>

                                {{-- Status --}}
                                <td class="text-center">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold border {{ in_array($l->status, ['delivered', 'success', 'sent']) ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-rose-200 bg-rose-50 text-rose-700' }} whitespace-nowrap">
                                        <span>{{ strtoupper($l->status) }}</span>
                                    </span>
                                </td>

                                {{-- Dispatched At --}}
                                <td class="text-center font-mono text-slate-500 text-[11px]">
                                    {{ $l->created_at ? $l->created_at->format('d-M-Y h:i A') : 'N/A' }}
                                </td>

                                {{-- Action Dropdown (AGENTS.md Rule 2.E) --}}
                                <td class="text-center">
                                    <div class="relative inline-block text-left">
                                        <button type="button" 
                                                @click="toggleLogMenu({{ json_encode($l) }}, $event)"
                                                class="w-7 h-7 rounded-lg hover:bg-slate-100 text-slate-500 hover:text-slate-800 transition flex items-center justify-center mx-auto cursor-pointer"
                                                title="Actions">
                                            <i class="fas fa-ellipsis-v text-[10px]"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-10 text-slate-400">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 mb-2">
                                            <i class="fas fa-paper-plane text-base"></i>
                                        </div>
                                        <span class="text-xs font-semibold text-slate-600">No Outbound Delivery Logs Found</span>
                                        <p class="text-[11px] text-slate-400 mt-1">Dispatched customer SMS notifications and automated alerts will appear here.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Compact Pagination Footer --}}
            @if($logs->hasPages())
                <div class="p-3 border-t border-slate-200 bg-slate-50/50 flex items-center justify-between">
                    <span class="text-xs text-slate-500">
                        Page {{ $logs->currentPage() }} of {{ $logs->lastPage() }}
                    </span>
                    <div>
                        {{ $logs->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- 4. FLOATING 3-DOT ACTION MENUS (AGENTS.md Rule 2.E) --}}
    
    {{-- Gateway Floating Action Menu --}}
    <div x-show="activeGatewayMenu" 
         x-cloak
         @click.away="activeGatewayMenu = null"
         :style="`position: fixed; top: ${menuPos.top}; bottom: ${menuPos.bottom}; right: ${menuPos.right}; left: ${menuPos.left};`"
         class="z-50 w-44 bg-white rounded-xl shadow-xl border border-slate-200/90 py-1 text-xs">
        
        <button type="button" 
                @click="openEditGatewayModal(activeGatewayMenu); activeGatewayMenu = null" 
                class="w-full px-3 py-2 text-left text-slate-700 hover:bg-slate-50 hover:text-slate-900 flex items-center gap-2 cursor-pointer">
            <i class="fas fa-pen-to-square text-cyan-600 w-3.5 text-center"></i>
            <span>Edit Configuration</span>
        </button>

        <button type="button" 
                @click="toggleGatewayStatus(activeGatewayMenu); activeGatewayMenu = null" 
                class="w-full px-3 py-2 text-left text-slate-700 hover:bg-slate-50 hover:text-slate-900 flex items-center gap-2 cursor-pointer">
            <i class="fas fa-power-off text-indigo-600 w-3.5 text-center"></i>
            <span x-text="activeGatewayMenu?.is_active ? 'Disable Route' : 'Enable Route'"></span>
        </button>

        <div class="h-px bg-slate-100 my-1"></div>

        <button type="button" 
                @click="deleteGateway(activeGatewayMenu); activeGatewayMenu = null" 
                class="w-full px-3 py-2 text-left text-rose-600 hover:bg-rose-50 flex items-center gap-2 cursor-pointer">
            <i class="fas fa-trash-can text-rose-500 w-3.5 text-center"></i>
            <span>Delete Gateway</span>
        </button>
    </div>

    {{-- Log Floating Action Menu --}}
    <div x-show="activeLogMenu" 
         x-cloak
         @click.away="activeLogMenu = null"
         :style="`position: fixed; top: ${menuPos.top}; bottom: ${menuPos.bottom}; right: ${menuPos.right}; left: ${menuPos.left};`"
         class="z-50 w-44 bg-white rounded-xl shadow-xl border border-slate-200/90 py-1 text-xs">
        
        <button type="button" 
                @click="viewLogDetails(activeLogMenu); activeLogMenu = null" 
                class="w-full px-3 py-2 text-left text-slate-700 hover:bg-slate-50 hover:text-slate-900 flex items-center gap-2 cursor-pointer">
            <i class="fas fa-eye text-cyan-600 w-3.5 text-center"></i>
            <span>View Full Details</span>
        </button>

        <div class="h-px bg-slate-100 my-1"></div>

        <button type="button" 
                @click="deleteSingleLog(activeLogMenu?.id, activeLogMenu?.recipient_phone); activeLogMenu = null" 
                class="w-full px-3 py-2 text-left text-rose-600 hover:bg-rose-50 flex items-center gap-2 cursor-pointer">
            <i class="fas fa-trash-can text-rose-500 w-3.5 text-center"></i>
            <span>Delete Record</span>
        </button>
    </div>

    {{-- 5. PRODUCTION-GRADE NATURAL MODALS (AGENTS.md Rule 3) --}}

    {{-- MODAL 1: EDIT NOTIFICATION TEMPLATE --}}
    <div x-show="editTemplateModalOpen" 
         x-cloak
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50">
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-xl overflow-hidden" @click.away="editTemplateModalOpen = false">
            <form :action="`/admin/settings/sms-gateway/templates/${activeTemplate?.id}`" method="POST">
                @csrf
                @method('PUT')

                {{-- Modal Header --}}
                <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0">
                            <i class="fas fa-pen-to-square"></i>
                        </div>
                        <div>
                            <h3 class="text-xs font-semibold text-slate-800" x-text="activeTemplate?.title || 'Edit Template'"></h3>
                            <span class="text-[10.5px] text-slate-500 font-normal">Custom text &amp; dynamic placeholders for this notification event</span>
                        </div>
                    </div>
                    <button type="button" @click="editTemplateModalOpen = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                        <i class="fas fa-xmark text-xs"></i>
                    </button>
                </div>

                {{-- Modal Body --}}
                <div class="p-4 space-y-3.5 text-xs">
                    {{-- Dynamic Tags Helper Pills --}}
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80 space-y-1.5">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 block">Click Tag to Insert in Message:</span>
                        <div class="flex flex-wrap gap-1">
                            <template x-for="tag in ['{customer_name}', '{customer_id}', '{amount}', '{due_date}', '{billing_month}', '{receipt_no}', '{company_name}', '{portal_url}', '{helpline}']">
                                <button type="button" 
                                        @click="insertTag(tag)" 
                                        class="px-2 py-0.5 bg-white border border-slate-200 hover:border-cyan-400 text-cyan-700 font-mono text-[10px] rounded transition cursor-pointer" 
                                        x-text="tag">
                                </button>
                            </template>
                        </div>
                    </div>

                    {{-- SMS Body --}}
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider">SMS Message Text (Plain Text)</label>
                            <span class="text-[10.5px] font-mono text-slate-500">
                                <span x-text="charCount">0</span> chars (<span x-text="smsParts">1</span> SMS Part)
                            </span>
                        </div>
                        <textarea id="templateSmsBody" 
                                  name="sms_body" 
                                  rows="3" 
                                  x-model="activeTemplate.sms_body" 
                                  @input="calculateSmsParts()"
                                  required 
                                  class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-2 font-mono text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition leading-relaxed">
                        </textarea>
                    </div>

                    {{-- WhatsApp Body --}}
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">WhatsApp Message Text (Markdown Supported)</label>
                        <textarea name="whatsapp_body" 
                                  rows="3" 
                                  x-model="activeTemplate.whatsapp_body" 
                                  class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-2 font-mono text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition leading-relaxed">
                        </textarea>
                    </div>

                    {{-- Channel Switches --}}
                    <div class="grid grid-cols-3 gap-2 pt-2 border-t border-slate-100">
                        <label class="flex items-center gap-2 cursor-pointer p-2 bg-slate-50 rounded-lg border border-slate-200/80">
                            <input type="checkbox" name="send_sms" value="1" :checked="activeTemplate?.send_sms" class="rounded border-slate-300 text-cyan-600 focus:ring-cyan-500 cursor-pointer">
                            <span class="text-xs font-semibold text-slate-700">Send via SMS</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer p-2 bg-slate-50 rounded-lg border border-slate-200/80">
                            <input type="checkbox" name="send_whatsapp" value="1" :checked="activeTemplate?.send_whatsapp" class="rounded border-slate-300 text-green-600 focus:ring-green-500 cursor-pointer">
                            <span class="text-xs font-semibold text-slate-700">Send WhatsApp</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer p-2 bg-slate-50 rounded-lg border border-slate-200/80">
                            <input type="checkbox" name="is_active" value="1" :checked="activeTemplate?.is_active" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer">
                            <span class="text-xs font-semibold text-slate-700">Active Trigger</span>
                        </label>
                    </div>
                </div>

                {{-- Modal Footer --}}
                <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" @click="editTemplateModalOpen = false" class="h-[34px] py-0 px-3.5 border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs rounded-lg transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" class="h-[34px] py-0 px-4 bg-cyan-600 hover:bg-cyan-700 text-white font-medium text-xs rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                        <i class="fas fa-floppy-disk text-xs"></i>
                        <span>Save Template</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL 2: SEND LIVE TEST MESSAGE --}}
    <div x-show="testModalOpen" 
         x-cloak
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50">
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-md overflow-hidden" @click.away="testModalOpen = false">
            <form action="{{ route('tenant.settings.sms-gateway.test') }}" method="POST">
                @csrf

                {{-- Modal Header --}}
                <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center text-xs flex-shrink-0">
                            <i class="fas fa-paper-plane"></i>
                        </div>
                        <div>
                            <h3 class="text-xs font-semibold text-slate-800">Send Live Test Message</h3>
                            <span class="text-[10.5px] text-slate-500 font-normal">Test instant outbound delivery to any mobile number</span>
                        </div>
                    </div>
                    <button type="button" @click="testModalOpen = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                        <i class="fas fa-xmark text-xs"></i>
                    </button>
                </div>

                {{-- Modal Body --}}
                <div class="p-4 space-y-3.5 text-xs">
                    {{-- Channel --}}
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Dispatch Channel *</label>
                        <div class="grid grid-cols-2 gap-2">
                            <label class="flex items-center gap-2 p-2 bg-slate-50 rounded-lg border border-slate-200 cursor-pointer">
                                <input type="radio" name="channel" value="sms" checked class="text-cyan-600">
                                <span class="font-semibold text-slate-700">SMS Gateway</span>
                            </label>
                            <label class="flex items-center gap-2 p-2 bg-slate-50 rounded-lg border border-slate-200 cursor-pointer">
                                <input type="radio" name="channel" value="whatsapp" class="text-green-600">
                                <span class="font-semibold text-slate-700">WhatsApp Gateway</span>
                            </label>
                        </div>
                    </div>

                    {{-- Recipient Mobile --}}
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Recipient Mobile Number *</label>
                        <input type="text" name="recipient_phone" required placeholder="018XXXXXXXX" class="h-[34px] w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 font-mono text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    </div>

                    {{-- Message Text --}}
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Test Message Text *</label>
                        <textarea name="test_message" rows="3" required class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-2 text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition leading-relaxed">Test notification from {{ $tenant->name ?? 'ISP System' }}. Your connection is healthy.</textarea>
                    </div>
                </div>

                {{-- Modal Footer --}}
                <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" @click="testModalOpen = false" class="h-[34px] py-0 px-3.5 border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs rounded-lg transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" class="h-[34px] py-0 px-4 bg-cyan-600 hover:bg-cyan-700 text-white font-medium text-xs rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                        <i class="fas fa-paper-plane text-xs"></i>
                        <span>Send Test Now</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL 3: ADD / EDIT GATEWAY MODAL --}}
    <div x-show="gatewayModalOpen" 
         x-cloak
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50">
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden" @click.away="gatewayModalOpen = false">
            <form :action="isEditingGateway ? `/admin/settings/sms-gateway/gateways/${activeGateway?.id}` : '{{ route('tenant.settings.sms-gateway.gateways.store') }}'" method="POST">
                @csrf
                <template x-if="isEditingGateway">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                {{-- Modal Header --}}
                <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center text-xs flex-shrink-0">
                            <i class="fas fa-tower-broadcast"></i>
                        </div>
                        <div>
                            <h3 class="text-xs font-semibold text-slate-800" x-text="isEditingGateway ? 'Edit Gateway Configuration' : 'Connect New Messaging Gateway'"></h3>
                            <span class="text-[10.5px] text-slate-500 font-normal">Configure API credentials, sender ID and rates</span>
                        </div>
                    </div>
                    <button type="button" @click="gatewayModalOpen = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                        <i class="fas fa-xmark text-xs"></i>
                    </button>
                </div>

                {{-- Modal Body --}}
                <div class="p-4 space-y-3 text-xs">
                    <div class="grid grid-cols-2 gap-3" x-show="!isEditingGateway">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Channel Type *</label>
                            <select name="channel_type" x-model="gatewayForm.channel_type" class="h-[34px] w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                                <option value="sms">SMS Gateway</option>
                                <option value="whatsapp">WhatsApp Gateway</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Provider Engine *</label>
                            <select name="provider" x-model="gatewayForm.provider" class="h-[34px] w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                                <option value="greenweb">Greenweb BD</option>
                                <option value="bulksmsbd">BulkSMS BD</option>
                                <option value="onnorokom">Onnorokom SMS</option>
                                <option value="alphasms">Alpha SMS</option>
                                <option value="infobip">Infobip</option>
                                <option value="twilio">Twilio SMS</option>
                                <option value="whatsapp_cloud">Meta WhatsApp Cloud API</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Gateway Connection Name *</label>
                        <input type="text" name="name" x-model="gatewayForm.name" required placeholder="e.g. Greenweb Primary Masking" class="h-[34px] w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Sender ID / Masking</label>
                            <input type="text" name="sender_id" x-model="gatewayForm.sender_id" placeholder="e.g. 8809611XXXXX or Brand Name" class="h-[34px] w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 font-mono focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">API Endpoint URL</label>
                            <input type="text" name="api_endpoint" x-model="gatewayForm.api_endpoint" placeholder="https://api.sms-provider.com/send" class="h-[34px] w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 font-mono focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">API Token / Secret Key *</label>
                        <input type="text" name="api_key" x-model="gatewayForm.api_key" required placeholder="Enter Gateway API Key" class="h-[34px] w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 font-mono focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Cost Per SMS (BDT)</label>
                            <input type="number" step="0.01" name="cost_per_sms" x-model="gatewayForm.cost_per_sms" required placeholder="0.35" class="h-[34px] w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 font-mono focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Current Balance (BDT)</label>
                            <input type="number" step="0.01" name="balance" x-model="gatewayForm.balance" required placeholder="1000.00" class="h-[34px] w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 font-mono focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        </div>
                    </div>

                    <div class="pt-2 border-t border-slate-100">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_default" value="1" x-model="gatewayForm.is_default" class="rounded border-slate-300 text-cyan-600 focus:ring-cyan-500 cursor-pointer">
                            <span class="text-xs font-semibold text-slate-700">Set as Primary Default Gateway</span>
                        </label>
                    </div>
                </div>

                {{-- Modal Footer --}}
                <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" @click="gatewayModalOpen = false" class="h-[34px] py-0 px-3.5 border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs rounded-lg transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" class="h-[34px] py-0 px-4 bg-cyan-600 hover:bg-cyan-700 text-white font-medium text-xs rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                        <i class="fas fa-floppy-disk text-xs"></i>
                        <span x-text="isEditingGateway ? 'Update Gateway' : 'Save Gateway'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL 4: VIEW LOG DETAILS MODAL --}}
    <div x-show="logDetailsModalOpen" 
         x-cloak
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50">
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden" @click.away="logDetailsModalOpen = false">
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-file-lines"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">SMS Outbound Delivery Details</h3>
                        <span class="text-[10.5px] text-slate-500 font-normal" x-text="activeLog?.recipient_phone"></span>
                    </div>
                </div>
                <button type="button" @click="logDetailsModalOpen = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-xmark text-xs"></i>
                </button>
            </div>

            <div class="p-4 space-y-3 text-xs">
                <div class="grid grid-cols-2 gap-2 bg-slate-50 p-3 rounded-lg border border-slate-200/80">
                    <div>
                        <span class="text-[10px] uppercase font-bold text-slate-400 block">Recipient Mobile</span>
                        <span class="font-mono font-bold text-slate-800 text-xs" x-text="activeLog?.recipient_phone"></span>
                    </div>
                    <div>
                        <span class="text-[10px] uppercase font-bold text-slate-400 block">Delivery Status</span>
                        <span class="font-bold uppercase text-xs" :class="activeLog?.status === 'delivered' ? 'text-emerald-600' : 'text-rose-600'" x-text="activeLog?.status"></span>
                    </div>
                    <div>
                        <span class="text-[10px] uppercase font-bold text-slate-400 block">Parts &amp; Length</span>
                        <span class="font-mono text-slate-700" x-text="`${activeLog?.parts_count || 1} Parts (${activeLog?.character_count || 0} Chars)`"></span>
                    </div>
                    <div>
                        <span class="text-[10px] uppercase font-bold text-slate-400 block">Total Cost</span>
                        <span class="font-mono font-bold text-slate-800" x-text="`৳${activeLog?.total_cost || '0.00'}`"></span>
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Message Body</label>
                    <div class="p-3 bg-slate-50 rounded-lg border border-slate-200 font-mono text-slate-800 text-[11px] leading-relaxed select-all" x-text="activeLog?.message_body"></div>
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Gateway API Response Payload</label>
                    <pre class="p-3 bg-slate-900 text-cyan-300 rounded-lg font-mono text-[10.5px] overflow-x-auto max-h-40 leading-relaxed" x-text="formatJson(activeLog?.api_response)"></pre>
                </div>
            </div>

            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end">
                <button type="button" @click="logDetailsModalOpen = false" class="h-[34px] py-0 px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium text-xs rounded-lg transition cursor-pointer">
                    Close
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    function notificationManager() {
        return {
            activeTab: '{{ $tab }}',
            searchQuery: '{{ addslashes($search) }}',
            editTemplateModalOpen: false,
            testModalOpen: false,
            gatewayModalOpen: false,
            logDetailsModalOpen: false,
            isEditingGateway: false,
            
            activeTemplate: { id: null, title: '', sms_body: '', whatsapp_body: '', send_sms: true, send_whatsapp: true, is_active: true },
            activeGateway: null,
            activeLog: null,

            // Floating 3-Dot Menus
            activeGatewayMenu: null,
            activeLogMenu: null,
            menuPos: { top: '0px', bottom: 'auto', right: '0px', left: 'auto' },

            charCount: 0,
            smsParts: 1,

            gatewayForm: {
                id: null,
                channel_type: 'sms',
                provider: 'greenweb',
                name: '',
                sender_id: '',
                api_endpoint: '',
                api_key: '',
                cost_per_sms: 0.35,
                balance: 1000.00,
                is_default: false
            },

            // Position calculation for 3-dot menus (AGENTS.md Rule 2.E)
            toggleGatewayMenu(item, event) {
                if (this.activeGatewayMenu?.id === item.id) {
                    this.activeGatewayMenu = null;
                    return;
                }
                this.activeLogMenu = null;
                this.activeGatewayMenu = item;
                this.calculateMenuPosition(event);
            },

            toggleLogMenu(item, event) {
                if (this.activeLogMenu?.id === item.id) {
                    this.activeLogMenu = null;
                    return;
                }
                this.activeGatewayMenu = null;
                this.activeLogMenu = item;
                this.calculateMenuPosition(event);
            },

            calculateMenuPosition(event) {
                const rect = event.currentTarget.getBoundingClientRect();
                const dropdownHeight = 150;
                const right = Math.max(10, window.innerWidth - rect.right);
                let top = Math.round(rect.bottom) + 2;
                let bottom = 'auto';

                if (top + dropdownHeight > window.innerHeight) {
                    top = 'auto';
                    bottom = Math.max(10, window.innerHeight - Math.round(rect.top) + 2) + 'px';
                } else {
                    top = `${top}px`;
                }

                this.menuPos = {
                    top: top,
                    bottom: bottom,
                    right: `${right}px`,
                    left: 'auto'
                };
            },

            openEditTemplateModal(template) {
                this.activeTemplate = JSON.parse(JSON.stringify(template));
                this.calculateSmsParts();
                this.editTemplateModalOpen = true;
            },

            calculateSmsParts() {
                const text = this.activeTemplate?.sms_body || '';
                this.charCount = text.length;
                this.smsParts = Math.max(1, Math.ceil(this.charCount / 160));
            },

            insertTag(tag) {
                const textarea = document.getElementById('templateSmsBody');
                if (textarea) {
                    const start = textarea.selectionStart;
                    const end = textarea.selectionEnd;
                    const text = this.activeTemplate.sms_body || '';
                    this.activeTemplate.sms_body = text.substring(0, start) + tag + text.substring(end);
                    this.calculateSmsParts();
                }
            },

            openAddGatewayModal() {
                this.isEditingGateway = false;
                this.gatewayForm = {
                    id: null,
                    channel_type: 'sms',
                    provider: 'greenweb',
                    name: '',
                    sender_id: '{{ $tenant->sms_sender_id ?: 'TITHI' }}',
                    api_endpoint: '',
                    api_key: '',
                    cost_per_sms: 0.35,
                    balance: 1000.00,
                    is_default: false
                };
                this.gatewayModalOpen = true;
            },

            openEditGatewayModal(gateway) {
                this.isEditingGateway = true;
                this.activeGateway = gateway;
                this.gatewayForm = {
                    id: gateway.id,
                    channel_type: gateway.channel_type || 'sms',
                    provider: gateway.provider,
                    name: gateway.name,
                    sender_id: gateway.sender_id || '',
                    api_endpoint: gateway.api_endpoint || '',
                    api_key: gateway.api_key || '',
                    cost_per_sms: gateway.cost_per_sms || 0.35,
                    balance: gateway.balance || 0.00,
                    is_default: gateway.is_default
                };
                this.gatewayModalOpen = true;
            },

            toggleGatewayStatus(gateway) {
                fetch(`/admin/settings/sms-gateway/gateways/${gateway.id}/toggle-status`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                })
                .then(r => r.json())
                .then(data => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Status Updated',
                        text: data.message,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.reload();
                    });
                });
            },

            deleteGateway(gateway) {
                Swal.fire({
                    title: 'Delete Gateway Route?',
                    text: `Are you sure you want to remove '${gateway.name}'?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e11d48',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Yes, Delete'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch(`/admin/settings/sms-gateway/gateways/${gateway.id}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            }
                        })
                        .then(() => {
                            window.location.reload();
                        });
                    }
                });
            },

            viewLogDetails(log) {
                this.activeLog = log;
                this.logDetailsModalOpen = true;
            },

            formatJson(str) {
                if (!str) return 'No API Response Payload';
                try {
                    const parsed = typeof str === 'string' ? JSON.parse(str) : str;
                    return JSON.stringify(parsed, null, 2);
                } catch(e) {
                    return str;
                }
            },

            clearAllLogs() {
                Swal.fire({
                    title: 'Clear All Delivery Logs?',
                    text: 'Are you sure you want to delete all SMS logs? This action cannot be undone.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e11d48',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Yes, Clear All'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch('{{ route("tenant.settings.sms-gateway.logs.clear") }}', {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            }
                        })
                        .then(r => r.json())
                        .then(data => {
                            Swal.fire({
                                icon: 'success',
                                title: 'Logs Cleared',
                                text: data.message,
                                confirmButtonColor: '#0891b2'
                            }).then(() => {
                                window.location.reload();
                            });
                        });
                    }
                });
            },

            deleteSingleLog(id, phone) {
                Swal.fire({
                    title: 'Delete Log?',
                    text: `Delete outbound SMS log for ${phone}?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e11d48',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Yes, Delete'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch(`/admin/settings/sms-gateway/logs/${id}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            }
                        })
                        .then(r => r.json())
                        .then(data => {
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted',
                                text: data.message,
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.reload();
                            });
                        });
                    }
                });
            }
        };
    }
</script>
@endpush
