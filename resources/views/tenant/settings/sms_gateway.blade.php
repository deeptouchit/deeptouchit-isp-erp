@extends('tenant.layouts.app')

@section('title', 'SMS & Notification Settings - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
    {{-- Page-specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" x-data="notificationManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">
    
    {{-- 1. TOP HEADER BAR (AGENTS.md Rule 2.A) --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 bg-white p-3 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center text-sm shadow-2xs flex-shrink-0">
                <i class="fas fa-comment-sms"></i>
            </div>
            <h1 class="text-sm font-bold text-slate-800 tracking-tight">SMS &amp; Notification Settings</h1>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" @click="testModalOpen = true" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-paper-plane text-slate-500 text-xs"></i>
                <span>Test Dispatch</span>
            </button>
            <a href="{{ route('tenant.settings.sms-gateway.print') }}" target="_blank" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition flex items-center gap-1.5 cursor-pointer">
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
                <span class="text-[13px] font-bold font-mono text-purple-700 leading-tight block">
                    {{ $stats['active_templates'] }} Ready
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-purple-200 bg-purple-50 text-purple-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-bolt"></i>
            </div>
        </div>

        {{-- Card 6: Failed / Error --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Failed / Bounced</span>
                <span class="text-[13px] font-bold font-mono {{ $stats['failed_count'] > 0 ? 'text-amber-600' : 'text-slate-700' }} leading-tight block">
                    {{ number_format($stats['failed_count']) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border {{ $stats['failed_count'] > 0 ? 'border-amber-200 bg-amber-50 text-amber-600' : 'border-slate-200 bg-slate-50 text-slate-600' }} flex items-center justify-center flex-shrink-0">
                <i class="fas fa-triangle-exclamation"></i>
            </div>
        </div>
    </div>

    {{-- 3. NAVIGATION TABS --}}
    <div class="flex items-center gap-1.5 border-b border-slate-200 bg-white px-3 pt-2 rounded-t-xl">
        <button type="button" 
                @click="activeTab = 'templates'"
                class="px-3.5 py-2 text-xs font-semibold rounded-t-lg transition border-b-2 cursor-pointer flex items-center gap-1.5"
                :class="activeTab === 'templates' ? 'border-cyan-600 text-cyan-700 bg-cyan-50/50' : 'border-transparent text-slate-600 hover:text-slate-900 hover:bg-slate-50'">
            <i class="fas fa-envelope-open-text text-xs"></i>
            <span>1. Notification Templates &amp; Alerts ({{ $templates->count() }})</span>
        </button>
        <button type="button" 
                @click="activeTab = 'logs'"
                class="px-3.5 py-2 text-xs font-semibold rounded-t-lg transition border-b-2 cursor-pointer flex items-center gap-1.5"
                :class="activeTab === 'logs' ? 'border-cyan-600 text-cyan-700 bg-cyan-50/50' : 'border-transparent text-slate-600 hover:text-slate-900 hover:bg-slate-50'">
            <i class="fas fa-clock-rotate-left text-xs"></i>
            <span>2. Outbound Delivery Logs</span>
        </button>
    </div>

    {{-- TAB 1: NOTIFICATION TEMPLATES TABLE --}}
    <div x-show="activeTab === 'templates'" class="space-y-3">
        <div class="bg-white rounded-b-xl border border-t-0 border-slate-200 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="saas-table">
                    <thead>
                        <tr>
                            <th class="w-10 text-center">SL</th>
                            <th>Notification Event Trigger</th>
                            <th class="text-center">SMS</th>
                            <th class="text-center">WhatsApp</th>
                            <th>Message Text Body Preview</th>
                            <th class="text-center">Status</th>
                            <th class="w-16 text-center no-sort">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($templates as $idx => $t)
                            <tr>
                                <td class="text-center text-slate-400 font-mono">{{ $idx + 1 }}</td>
                                
                                {{-- Trigger Name --}}
                                <td class="font-bold text-slate-800">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-md bg-cyan-50 text-cyan-700 border border-cyan-100 flex items-center justify-center text-[10px] flex-shrink-0">
                                            <i class="fas fa-message"></i>
                                        </div>
                                        <span>{{ $t->title }}</span>
                                    </div>
                                </td>

                                {{-- SMS Channel Status --}}
                                <td class="text-center">
                                    @if($t->send_sms)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold border border-emerald-200 bg-emerald-50 text-emerald-700">
                                            <i class="fas fa-check text-[9px]"></i> SMS
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium text-slate-400 bg-slate-100">
                                            OFF
                                        </span>
                                    @endif
                                </td>

                                {{-- WhatsApp Channel Status --}}
                                <td class="text-center">
                                    @if($t->send_whatsapp)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold border border-green-200 bg-green-50 text-green-700">
                                            <i class="fab fa-whatsapp text-[10px]"></i> WA
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium text-slate-400 bg-slate-100">
                                            OFF
                                        </span>
                                    @endif
                                </td>

                                {{-- Message Body Preview --}}
                                <td class="text-slate-600 text-xs truncate max-w-md font-sans" title="{{ $t->sms_body }}">
                                    {{ $t->sms_body }}
                                </td>

                                {{-- Status Badge --}}
                                <td class="text-center">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $t->is_active ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-slate-50 text-slate-500' }}">
                                        <span>{{ $t->is_active ? 'ACTIVE' : 'DISABLED' }}</span>
                                    </span>
                                </td>

                                {{-- Action --}}
                                <td class="text-center">
                                    <button type="button" 
                                            @click="openEditTemplateModal({{ json_encode($t) }})"
                                            class="px-2.5 py-1 bg-cyan-50 hover:bg-cyan-100 text-cyan-700 rounded-md text-xs font-semibold border border-cyan-200 transition flex items-center justify-center gap-1 mx-auto cursor-pointer">
                                        <i class="fas fa-pen-to-square text-[10px]"></i>
                                        <span>Edit</span>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-8 text-slate-400">
                                    <span class="text-xs font-medium text-slate-500">No notification templates found.</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- TAB 2: OUTBOUND DELIVERY LOGS --}}
    <div x-show="activeTab === 'logs'" class="space-y-3">
        {{-- Search & Multi-Filter Bar with Actions --}}
        <div class="bg-white p-3 rounded-xl border border-slate-200 shadow-xs flex flex-col lg:flex-row lg:items-center justify-between gap-3">
            <form method="GET" action="{{ route('tenant.settings.sms-gateway') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-2 flex-1">
                <input type="hidden" name="tab" value="logs">
                
                {{-- Search --}}
                <div class="sm:col-span-1">
                    <div class="relative">
                        <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input type="text" name="search" value="{{ $search }}" placeholder="Search by recipient, text..." class="w-full pl-8 pr-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                    </div>
                </div>

                {{-- Status --}}
                <div>
                    <select name="status" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                        <option value="all">All Delivery Statuses</option>
                        <option value="delivered" {{ $status === 'delivered' ? 'selected' : '' }}>Delivered / Success</option>
                        <option value="failed" {{ $status === 'failed' ? 'selected' : '' }}>Failed / Error</option>
                    </select>
                </div>

                {{-- Filter & Reset Buttons (AGENTS.md Rule 2.C) --}}
                <div class="flex items-center gap-1.5">
                    <button type="submit" class="flex-1 py-1.5 px-3 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                        <i class="fas fa-filter text-[10px]"></i>
                        <span>Filter</span>
                    </button>
                    <a href="{{ route('tenant.settings.sms-gateway', ['tab' => 'logs']) }}" class="py-1.5 px-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer">
                        <i class="fas fa-rotate-left text-[10px]"></i>
                        <span>Reset</span>
                    </a>
                </div>
            </form>

            {{-- Log Actions --}}
            <div class="flex items-center gap-2 border-t lg:border-t-0 pt-2 lg:pt-0 border-slate-100 flex-shrink-0">
                <a href="{{ route('tenant.settings.sms-gateway.export') }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition flex items-center gap-1.5 cursor-pointer">
                    <i class="fas fa-file-csv text-emerald-600 text-xs"></i>
                    <span>Export CSV</span>
                </a>
                <button type="button" @click="clearAllLogs()" class="px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-lg text-xs font-semibold border border-rose-200 transition flex items-center gap-1.5 cursor-pointer">
                    <i class="fas fa-trash-can text-rose-600 text-xs"></i>
                    <span>Clear All Logs</span>
                </button>
            </div>
        </div>

        {{-- Master Logs Table --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="saas-table">
                    <thead>
                        <tr>
                            <th class="w-10 text-center">SL</th>
                            <th>Recipient Mobile</th>
                            <th>Notification Type</th>
                            <th>Message Text Preview</th>
                            <th class="text-center">Parts / Cost</th>
                            <th class="text-center">Delivery Status</th>
                            <th>Dispatched At</th>
                            <th class="w-12 text-center no-sort">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $idx => $l)
                            <tr>
                                <td class="text-center text-slate-400 font-mono">{{ $logs->firstItem() + $idx }}</td>
                                
                                {{-- Recipient Phone --}}
                                <td class="font-bold text-slate-800 font-mono">
                                    {{ $l->recipient_phone }}
                                </td>

                                {{-- Type --}}
                                <td>
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold font-mono border border-slate-200 bg-slate-50 text-slate-600">
                                        {{ strtoupper(str_replace('_', ' ', $l->sms_type ?? 'SMS')) }}
                                    </span>
                                </td>

                                {{-- Message Body --}}
                                <td class="text-slate-700 text-xs truncate max-w-sm" title="{{ $l->message_body }}">
                                    {{ $l->message_body }}
                                </td>

                                {{-- Parts / Cost --}}
                                <td class="text-center font-mono text-xs text-slate-700">
                                    <span class="text-slate-500">{{ $l->parts_count ?: 1 }} SMS</span>
                                    <span class="text-slate-400">/</span>
                                    <span class="font-bold text-slate-800">@currency($l->total_cost)</span>
                                </td>

                                {{-- Status --}}
                                <td class="text-center">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold border {{ in_array($l->status, ['delivered', 'success', 'sent']) ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-rose-200 bg-rose-50 text-rose-700' }}">
                                        <span>{{ strtoupper($l->status) }}</span>
                                    </span>
                                </td>

                                {{-- Dispatched At --}}
                                <td class="font-mono text-slate-500 text-[11px]">
                                    {{ $l->created_at ? $l->created_at->format('d-M-Y h:i A') : 'N/A' }}
                                </td>

                                {{-- Action --}}
                                <td class="text-center">
                                    <button type="button" 
                                            @click="deleteSingleLog({{ $l->id }}, '{{ addslashes($l->recipient_phone) }}')" 
                                            class="w-6 h-6 rounded-md hover:bg-rose-50 text-slate-400 hover:text-rose-600 transition inline-flex items-center justify-center cursor-pointer" 
                                            title="Delete Log">
                                        <i class="fas fa-trash-can text-xs"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-8 text-slate-400">
                                    <span class="text-xs font-medium text-slate-500">No outbound delivery logs found.</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($logs->hasPages())
                <div class="p-3 border-t border-slate-200 bg-slate-50/50 flex items-center justify-between">
                    <span class="text-xs text-slate-500">
                        Showing {{ $logs->firstItem() }} to {{ $logs->lastItem() }} of {{ $logs->total() }} delivery logs
                    </span>
                    <div>
                        {{ $logs->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- MODAL 1: EDIT NOTIFICATION TEMPLATE (AGENTS.md Rule 3) --}}
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
                            <span class="text-[10.5px] text-slate-500 font-normal">Custom text &amp; placeholders for this notification event</span>
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
                                  class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-2 font-mono text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-none transition leading-relaxed">
                        </textarea>
                    </div>

                    {{-- WhatsApp Body --}}
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">WhatsApp Message Text (Markdown Supported)</label>
                        <textarea name="whatsapp_body" 
                                  rows="3" 
                                  x-model="activeTemplate.whatsapp_body" 
                                  class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-2 font-mono text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-none transition leading-relaxed">
                        </textarea>
                    </div>

                    {{-- Channel Switches --}}
                    <div class="grid grid-cols-3 gap-2 pt-2 border-t border-slate-100">
                        <label class="flex items-center gap-2 cursor-pointer p-2 bg-slate-50 rounded-lg border border-slate-200/80">
                            <input type="checkbox" name="send_sms" value="1" :checked="activeTemplate?.send_sms" class="rounded border-slate-300 text-cyan-600 focus:ring-cyan-500">
                            <span class="text-xs font-semibold text-slate-700">Send via SMS</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer p-2 bg-slate-50 rounded-lg border border-slate-200/80">
                            <input type="checkbox" name="send_whatsapp" value="1" :checked="activeTemplate?.send_whatsapp" class="rounded border-slate-300 text-green-600 focus:ring-green-500">
                            <span class="text-xs font-semibold text-slate-700">Send WhatsApp</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer p-2 bg-slate-50 rounded-lg border border-slate-200/80">
                            <input type="checkbox" name="is_active" value="1" :checked="activeTemplate?.is_active" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                            <span class="text-xs font-semibold text-slate-700">Active Trigger</span>
                        </label>
                    </div>
                </div>

                {{-- Modal Footer --}}
                <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" @click="editTemplateModalOpen = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" class="bg-cyan-600 hover:bg-cyan-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                        <i class="fas fa-floppy-disk text-xs"></i>
                        <span>Save Template</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL 2: TEST SMS DISPATCH MODAL --}}
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
                        <input type="text" name="recipient_phone" required placeholder="018XXXXXXXX" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-2 font-mono text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                    </div>

                    {{-- Message Text --}}
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Test Message Text *</label>
                        <textarea name="test_message" rows="3" required class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-2 text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-none transition leading-relaxed">Test notification from {{ $tenant->name ?? 'ISP System' }}. Your connection is healthy.</textarea>
                    </div>
                </div>

                {{-- Modal Footer --}}
                <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" @click="testModalOpen = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" class="bg-cyan-600 hover:bg-cyan-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                        <i class="fas fa-paper-plane text-xs"></i>
                        <span>Send Test Now</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    function notificationManager() {
        return {
            activeTab: '{{ $tab }}',
            editTemplateModalOpen: false,
            testModalOpen: false,
            activeTemplate: { id: null, title: '', sms_body: '', whatsapp_body: '', send_sms: true, send_whatsapp: true, is_active: true },
            charCount: 0,
            smsParts: 1,

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
