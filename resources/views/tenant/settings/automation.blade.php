@extends('tenant.layouts.app')

@section('title', 'System Automation & Auto-Cut - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
    {{-- Page-specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" x-data="automationManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">
    
    {{-- 1. TOP HEADER BAR (AGENTS.md Rule 2.A: Icon + Title + Action Buttons ONLY. No subtitle/p tag) --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 bg-white p-3 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center text-sm shadow-2xs flex-shrink-0">
                <i class="fas fa-robot"></i>
            </div>
            <h1 class="text-sm font-bold text-slate-800 tracking-tight">System Automation &amp; Auto-Cut</h1>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" @click="runAutomationJob('autocut')" class="h-[34px] px-3 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-lg text-xs font-semibold border border-rose-200 transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-scissors text-rose-600 text-xs"></i>
                <span>Run Auto-Cut</span>
            </button>
            <a href="{{ route('tenant.settings.automation.print') }}" target="_blank" class="h-[34px] px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-print text-slate-500 text-xs"></i>
                <span>Print Policy</span>
            </a>
            <button type="submit" form="automationForm" class="h-[34px] px-4 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-floppy-disk text-xs"></i>
                <span>Save Rules</span>
            </button>
        </div>
    </div>

    {{-- 2. KPI SUMMARY STRIP (Strictly 6 Cards - AGENTS.md Rule 2.B) --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        {{-- Card 1: Auto-Cut Status --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Auto-Cut Status</span>
                <span class="text-[13px] font-bold font-mono text-emerald-600 leading-tight block truncate">
                    {{ $stats['autocut_status'] }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-emerald-200 bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-scissors"></i>
            </div>
        </div>

        {{-- Card 2: Due Customers --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Due Customers</span>
                <span class="text-[13px] font-bold font-mono text-amber-600 leading-tight block">
                    {{ number_format($stats['overdue_at_risk']) }} Users
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-amber-200 bg-amber-50 text-amber-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-user-clock"></i>
            </div>
        </div>

        {{-- Card 3: Disconnected Lines --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Disconnected Lines</span>
                <span class="text-[13px] font-bold font-mono text-rose-600 leading-tight block">
                    {{ number_format($stats['currently_suspended']) }} Lines
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-rose-200 bg-rose-50 text-rose-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-ban"></i>
            </div>
        </div>

        {{-- Card 4: Auto-Billing --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Auto-Billing</span>
                <span class="text-[13px] font-bold font-mono text-cyan-700 leading-tight block truncate">
                    {{ $stats['auto_billing'] }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-cyan-200 bg-cyan-50 text-cyan-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-file-invoice-dollar"></i>
            </div>
        </div>

        {{-- Card 5: Auto-Reconnect --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Auto-Reconnect</span>
                <span class="text-[13px] font-bold font-mono text-indigo-700 leading-tight block">
                    {{ $stats['auto_reconnect'] }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-indigo-200 bg-indigo-50 text-indigo-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-bolt"></i>
            </div>
        </div>

        {{-- Card 6: System Status --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">System Status</span>
                <span class="text-[13px] font-bold font-mono text-purple-700 leading-tight block">
                    {{ $stats['cron_health'] }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-purple-200 bg-purple-50 text-purple-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-heart-pulse"></i>
            </div>
        </div>
    </div>

    {{-- 3. NAVIGATION TABS --}}
    <div class="flex items-center gap-1.5 border-b border-slate-200 bg-white px-3 pt-2 rounded-t-xl">
        <button type="button" 
                @click="activeTab = 'rules'"
                class="px-3.5 py-2 text-xs font-semibold rounded-t-lg transition border-b-2 cursor-pointer flex items-center gap-1.5"
                :class="activeTab === 'rules' ? 'border-cyan-600 text-cyan-700 bg-cyan-50/50' : 'border-transparent text-slate-600 hover:text-slate-900 hover:bg-slate-50'">
            <i class="fas fa-sliders text-xs"></i>
            <span>1. Automation Rules</span>
        </button>
        <button type="button" 
                @click="activeTab = 'logs'"
                class="px-3.5 py-2 text-xs font-semibold rounded-t-lg transition border-b-2 cursor-pointer flex items-center gap-1.5"
                :class="activeTab === 'logs' ? 'border-cyan-600 text-cyan-700 bg-cyan-50/50' : 'border-transparent text-slate-600 hover:text-slate-900 hover:bg-slate-50'">
            <i class="fas fa-clock-rotate-left text-xs"></i>
            <span>2. Activity &amp; Cron Logs</span>
            <span class="ml-1 px-1.5 py-0.2 rounded-full text-[10px] font-mono font-bold bg-slate-100 text-slate-600">{{ $logs->total() }}</span>
        </button>
    </div>

    {{-- TAB 1: AUTOMATION RULES FORM --}}
    <div x-show="activeTab === 'rules'" class="space-y-3">
        <form id="automationForm" action="{{ route('tenant.settings.automation.update') }}" method="POST" class="space-y-3">
            @csrf

            {{-- Card 1: Auto-Cut & Subscriber Line Suspension Rules --}}
            <div class="bg-white p-5 rounded-b-xl border border-t-0 border-slate-200 shadow-xs space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="w-7 h-7 rounded-lg bg-rose-50 text-rose-600 border border-rose-100 flex items-center justify-center text-xs flex-shrink-0">
                            <i class="fas fa-scissors"></i>
                        </div>
                        <div>
                            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Auto-Cut &amp; Subscriber Line Suspension Rules</h3>
                            <span class="text-[10.5px] text-slate-500 font-normal">Automatically suspend overdue customer sessions and update MikroTik router state</span>
                        </div>
                    </div>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="auto_cut_enabled" value="1" {{ old('auto_cut_enabled', $settings->auto_cut_enabled) ? 'checked' : '' }} class="rounded border-slate-300 text-rose-600 focus:ring-rose-500 w-4 h-4 cursor-pointer">
                        <span class="text-xs font-bold text-slate-800">Enable Auto-Cut</span>
                    </label>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    {{-- Grace Period Days --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Grace Period (Days) *</label>
                        <input type="number" min="0" max="30" name="grace_period_days" value="{{ old('grace_period_days', $settings->grace_period_days) }}" required class="h-[34px] w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 font-mono font-bold text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <span class="text-[10px] text-slate-400 mt-1 block">0 = Cut on expiry date. 2 = Cut 2 days after expiry.</span>
                    </div>

                    {{-- Minimum Due Threshold --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Minimum Due for Cut ({{ $currencySymbol ?? '৳' }}) *</label>
                        <input type="number" min="0" step="1" name="min_due_threshold" value="{{ old('min_due_threshold', $settings->min_due_threshold) }}" required class="h-[34px] w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 font-mono font-bold text-rose-700 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <span class="text-[10px] text-slate-400 mt-1 block">Do not cut if due amount is less than this threshold.</span>
                    </div>

                    {{-- Action Method --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Cut Action Method *</label>
                        <select name="auto_cut_action" required class="h-[34px] w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 focus:bg-white focus:border-cyan-500 focus:outline-hidden font-medium cursor-pointer transition">
                            <option value="disable_secret" {{ old('auto_cut_action', $settings->auto_cut_action) === 'disable_secret' ? 'selected' : '' }}>Disable MikroTik PPP Secret</option>
                            <option value="radius_pool" {{ old('auto_cut_action', $settings->auto_cut_action) === 'radius_pool' ? 'selected' : '' }}>Move to Expired IP Pool</option>
                            <option value="change_profile" {{ old('auto_cut_action', $settings->auto_cut_action) === 'change_profile' ? 'selected' : '' }}>Switch to Blocked Profile</option>
                        </select>
                        <span class="text-[10px] text-slate-400 mt-1 block">Action executed on router when customer is overdue.</span>
                    </div>

                    {{-- Daily Execution Time --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Auto-Cut Run Time *</label>
                        <input type="time" name="auto_cut_time" value="{{ old('auto_cut_time', $settings->auto_cut_time) }}" required class="h-[34px] w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 font-mono focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <span class="text-[10px] text-slate-400 mt-1 block">Nightly batch execution time (e.g. 02:00 AM).</span>
                    </div>
                </div>

                <div class="flex items-center gap-4 pt-2 border-t border-slate-100">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="auto_send_cut_sms" value="1" {{ old('auto_send_cut_sms', $settings->auto_send_cut_sms) ? 'checked' : '' }} class="rounded border-slate-300 text-cyan-600 focus:ring-cyan-500 cursor-pointer">
                        <span class="text-xs text-slate-700 font-medium">Send SMS notification when line is disconnected</span>
                    </label>
                </div>
            </div>

            {{-- Card 2: Recurring Invoicing & Reconnection --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                {{-- Auto Generate Invoices --}}
                <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-md bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-[11px] flex-shrink-0">
                                <i class="fas fa-file-invoice-dollar"></i>
                            </div>
                            <div>
                                <h4 class="text-xs font-bold text-slate-800">Auto Generate Invoices</h4>
                                <span class="text-[10px] text-slate-400">Generates bills before customer's expiry date</span>
                            </div>
                        </div>
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="checkbox" name="auto_billing_enabled" value="1" {{ old('auto_billing_enabled', $settings->auto_billing_enabled) ? 'checked' : '' }} class="rounded border-slate-300 text-cyan-600 cursor-pointer">
                            <span class="text-xs font-bold text-slate-700">Enable</span>
                        </label>
                    </div>

                    <div class="grid grid-cols-2 gap-3 text-xs">
                        <div>
                            <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Generate (Days Before Expiry) *</label>
                            <input type="number" min="1" max="15" name="billing_generation_day" value="{{ old('billing_generation_day', $settings->billing_generation_day ?: 3) }}" required class="h-[34px] w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 focus:bg-white focus:border-cyan-500 focus:outline-hidden font-mono font-bold text-cyan-800 transition">
                            <span class="text-[9.5px] text-slate-400 mt-1 block">e.g. 3 Days before customer's expiry.</span>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Run Time *</label>
                            <input type="time" name="billing_generation_time" value="{{ old('billing_generation_time', $settings->billing_generation_time ?: '00:05') }}" required class="h-[34px] w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 focus:bg-white focus:border-cyan-500 focus:outline-hidden font-mono transition">
                            <span class="text-[9.5px] text-slate-400 mt-1 block">Recommended at midnight (12:05 AM).</span>
                        </div>
                    </div>

                    <div class="pt-1">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="auto_send_bill_sms" value="1" {{ old('auto_send_bill_sms', $settings->auto_send_bill_sms) ? 'checked' : '' }} class="rounded border-slate-300 text-cyan-600 cursor-pointer">
                            <span class="text-xs text-slate-600">Send SMS when new bill is generated</span>
                        </label>
                    </div>
                </div>

                {{-- Auto-Reconnect on Payment --}}
                <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-md bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-[11px] flex-shrink-0">
                                <i class="fas fa-plug-circle-check"></i>
                            </div>
                            <h4 class="text-xs font-bold text-slate-800">Auto-Reconnect on Payment</h4>
                        </div>
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="checkbox" name="auto_reconnect_enabled" value="1" {{ old('auto_reconnect_enabled', $settings->auto_reconnect_enabled) ? 'checked' : '' }} class="rounded border-slate-300 text-emerald-600 cursor-pointer">
                            <span class="text-xs font-bold text-slate-700">Enable</span>
                        </label>
                    </div>

                    <p class="text-xs text-slate-600 leading-relaxed">
                        When a disconnected customer pays their bill (via bKash, Nagad, or Counter Cash), their line is immediately re-enabled on the router and active sessions are updated.
                    </p>

                    <div class="pt-2 border-t border-slate-100">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="auto_send_restore_sms" value="1" {{ old('auto_send_restore_sms', $settings->auto_send_restore_sms) ? 'checked' : '' }} class="rounded border-slate-300 text-emerald-600 cursor-pointer">
                            <span class="text-xs text-slate-700 font-medium">Send "Payment Received &amp; Line Reconnected" SMS</span>
                        </label>
                    </div>
                </div>
            </div>

            {{-- Card 3: Reminders & Automatic Backup --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                {{-- Bill Payment Reminders --}}
                <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-md bg-amber-50 text-amber-600 border border-amber-100 flex items-center justify-center text-[11px] flex-shrink-0">
                                <i class="fas fa-bell"></i>
                            </div>
                            <h4 class="text-xs font-bold text-slate-800">Bill Payment Reminders</h4>
                        </div>
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="checkbox" name="expiry_reminders_enabled" value="1" {{ old('expiry_reminders_enabled', $settings->expiry_reminders_enabled) ? 'checked' : '' }} class="rounded border-slate-300 text-amber-600 cursor-pointer">
                            <span class="text-xs font-bold text-slate-700">Enable</span>
                        </label>
                    </div>

                    <div class="grid grid-cols-2 gap-3 text-xs">
                        <div>
                            <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Reminder (Days Before Expiry) *</label>
                            <input type="number" min="1" max="10" name="reminder_1_days_before" value="{{ old('reminder_1_days_before', $settings->reminder_1_days_before ?: 2) }}" required class="h-[34px] w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 focus:bg-white focus:border-cyan-500 focus:outline-hidden font-mono font-bold text-amber-800 transition">
                            <span class="text-[9.5px] text-slate-400 mt-1 block">e.g. 2 Days before customer's expiry.</span>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Send Time *</label>
                            <input type="time" name="reminder_dispatch_time" value="{{ old('reminder_dispatch_time', $settings->reminder_dispatch_time ?: '09:00') }}" required class="h-[34px] w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 focus:bg-white focus:border-cyan-500 focus:outline-hidden font-mono transition">
                            <span class="text-[9.5px] text-slate-400 mt-1 block">Recommended in morning (09:00 AM).</span>
                        </div>
                        <input type="hidden" name="reminder_2_days_before" value="{{ $settings->reminder_2_days_before ?: 0 }}">
                    </div>
                </div>

                {{-- Automatic Backup --}}
                <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-md bg-purple-50 text-purple-600 border border-purple-100 flex items-center justify-center text-[11px] flex-shrink-0">
                                <i class="fas fa-database"></i>
                            </div>
                            <h4 class="text-xs font-bold text-slate-800">Automatic Database Backup</h4>
                        </div>
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="checkbox" name="auto_backup_enabled" value="1" {{ old('auto_backup_enabled', $settings->auto_backup_enabled) ? 'checked' : '' }} class="rounded border-slate-300 text-purple-600 cursor-pointer">
                            <span class="text-xs font-bold text-slate-700">Enable</span>
                        </label>
                    </div>

                    <div class="grid grid-cols-3 gap-2.5 text-xs">
                        <div>
                            <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Frequency</label>
                            <select name="backup_frequency" required class="h-[34px] w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 focus:bg-white focus:border-cyan-500 focus:outline-hidden cursor-pointer transition">
                                <option value="daily" {{ old('backup_frequency', $settings->backup_frequency) === 'daily' ? 'selected' : '' }}>Daily</option>
                                <option value="weekly" {{ old('backup_frequency', $settings->backup_frequency) === 'weekly' ? 'selected' : '' }}>Weekly</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Backup Time</label>
                            <input type="time" name="backup_time" value="{{ old('backup_time', $settings->backup_time) }}" required class="h-[34px] w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 focus:bg-white focus:border-cyan-500 focus:outline-hidden font-mono transition">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Keep (Days)</label>
                            <input type="number" min="7" max="365" name="backup_retention_days" value="{{ old('backup_retention_days', $settings->backup_retention_days) }}" required class="h-[34px] w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 focus:bg-white focus:border-cyan-500 focus:outline-hidden font-mono transition">
                        </div>
                    </div>

                    {{-- Auto Email Backup Dispatch --}}
                    <div class="pt-3 border-t border-slate-100 space-y-2">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-1.5">
                                <i class="fas fa-envelope text-cyan-600 text-[11px]"></i>
                                <span class="text-xs font-bold text-slate-800">Send Backup to Email</span>
                            </div>
                            <label class="flex items-center gap-1.5 cursor-pointer">
                                <input type="checkbox" name="auto_backup_email_enabled" value="1" {{ old('auto_backup_email_enabled', $settings->auto_backup_email_enabled) ? 'checked' : '' }} class="rounded border-slate-300 text-cyan-600 cursor-pointer">
                                <span class="text-xs font-bold text-slate-700">Enable</span>
                            </label>
                        </div>
                        <div>
                            <input type="email" name="backup_destination_email" value="{{ old('backup_destination_email', $settings->backup_destination_email ?: ($tenant->billing_email ?: $tenant->email)) }}" placeholder="e.g. backup@yourdomain.com" class="h-[34px] w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 font-mono text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                            <span class="text-[9.5px] text-slate-400 mt-1 block">Delivered via SMTP Gateway configured on <a href="{{ route('tenant.settings.profile') }}" class="text-cyan-600 underline font-medium">Company Profile</a>.</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Centered Submit Action Bar --}}
            <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-xs flex items-center justify-between">
                <span class="text-xs text-slate-500">Scheduled automation rules run automatically in the background via system cron.</span>
                <button type="submit" class="h-[34px] px-5 bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs rounded-lg shadow-xs transition flex items-center gap-2 cursor-pointer">
                    <i class="fas fa-floppy-disk text-xs"></i>
                    <span>Save Rules</span>
                </button>
            </div>
        </form>
    </div>

    {{-- TAB 2: ACTIVITY & CRON EXECUTION LOGS --}}
    <div x-show="activeTab === 'logs'" class="space-y-3" x-data="{ searchQuery: '{{ addslashes($search) }}' }">
        
        {{-- Search & Multi-Filter Toolbar & Action Buttons (Customer-Standard 2-Tier Filter System - AGENTS.md Rule 2.C) --}}
        <div class="bg-white p-2.5 rounded-xl border border-slate-200/90 shadow-2xs space-y-2.5">
            <form method="GET" action="{{ route('tenant.settings.automation') }}" class="flex flex-col md:flex-row items-stretch md:items-center gap-2">
                <input type="hidden" name="tab" value="logs">
                
                {{-- Intelligent Live Search Box with 1-Click Clear --}}
                <div class="relative flex-1 min-w-[200px]">
                    <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-xs transition-colors"
                       :class="searchQuery ? 'text-cyan-600 font-bold' : 'text-slate-400'"></i>
                    <input type="text" 
                           name="search" 
                           x-model="searchQuery" 
                           value="{{ $search }}" 
                           placeholder="Search by task, trigger, or summary..." 
                           class="h-[34px] w-full pl-8 pr-7 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    <button type="button" 
                            x-show="searchQuery && searchQuery.length > 0" 
                            @click="searchQuery = ''; $nextTick(() => $el.closest('form').submit())" 
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-0.5 rounded cursor-pointer">
                        <i class="fas fa-xmark text-xs"></i>
                    </button>
                </div>

                {{-- Status Filter --}}
                <select name="status" class="h-[34px] w-full md:w-36 bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 focus:bg-white focus:border-cyan-500 focus:outline-hidden font-medium cursor-pointer transition">
                    <option value="all">All Statuses</option>
                    <option value="success" {{ $status === 'success' ? 'selected' : '' }}>Success</option>
                    <option value="failed" {{ $status === 'failed' ? 'selected' : '' }}>Failed</option>
                </select>

                {{-- Trigger Filter --}}
                <select name="trigger" class="h-[34px] w-full md:w-36 bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 focus:bg-white focus:border-cyan-500 focus:outline-hidden font-medium cursor-pointer transition">
                    <option value="all">All Triggers</option>
                    <option value="cron_scheduler" {{ ($trigger ?? '') === 'cron_scheduler' ? 'selected' : '' }}>System Cron</option>
                    <option value="manual_admin" {{ ($trigger ?? '') === 'manual_admin' ? 'selected' : '' }}>Manual Admin</option>
                </select>

                {{-- Pagination Selector (per_page) --}}
                <select name="per_page" class="h-[34px] w-full md:w-28 flex-shrink-0 bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 focus:bg-white focus:border-cyan-500 focus:outline-hidden font-mono cursor-pointer transition">
                    <option value="15" {{ $perPage == 15 ? 'selected' : '' }}>15 / page</option>
                    <option value="20" {{ $perPage == 20 ? 'selected' : '' }}>20 / page</option>
                    <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50 / page</option>
                    <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100 / page</option>
                </select>

                {{-- Filter & Reset Buttons Sequence (AGENTS.md Rule 2.C) --}}
                <div class="flex items-center gap-1.5 flex-shrink-0 md:ml-auto">
                    <button type="submit" class="h-[34px] bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs py-0 px-3 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                        <i class="fas fa-filter text-[10px]"></i>
                        <span>Filter</span>
                    </button>
                    <a href="{{ route('tenant.settings.automation', ['tab' => 'logs']) }}" class="h-[34px] bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs py-0 px-2.5 rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer" title="Reset Filters">
                        <i class="fas fa-rotate-left text-[10px]"></i>
                        <span>Reset</span>
                    </a>
                    <a href="{{ route('tenant.settings.automation.export') }}" class="h-[34px] bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs py-0 px-3 rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1.5 cursor-pointer" title="Export CSV">
                        <i class="fas fa-file-csv text-emerald-600 text-xs"></i>
                        <span>CSV</span>
                    </a>
                    <button type="button" @click="clearAllLogs()" class="h-[34px] bg-rose-50 hover:bg-rose-100 text-rose-700 font-semibold text-xs py-0 px-2.5 rounded-lg border border-rose-200 transition flex items-center justify-center gap-1.5 cursor-pointer" title="Clear All Logs">
                        <i class="fas fa-trash-can text-rose-600 text-xs"></i>
                        <span>Clear</span>
                    </button>
                </div>
            </form>
        </div>

        {{-- Master Compact Table (<table class="saas-table"> - AGENTS.md Rule 2.D) --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="saas-table">
                    <thead>
                        <tr>
                            <th class="w-10 text-center font-semibold">#</th>
                            <th class="text-center font-semibold">Task Name</th>
                            <th class="text-center font-semibold">Trigger</th>
                            <th class="text-center font-semibold">Duration</th>
                            <th class="text-center font-semibold">Result Summary</th>
                            <th class="text-center font-semibold">Status</th>
                            <th class="text-center font-semibold">Date &amp; Time</th>
                            <th class="w-16 text-center font-semibold no-sort">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $idx => $l)
                            <tr>
                                {{-- 1. Index --}}
                                <td class="text-center text-slate-400 font-mono text-xs">{{ $logs->firstItem() + $idx }}</td>
                                
                                {{-- 2. Task Name (text-left) --}}
                                <td class="text-left font-semibold text-slate-800">
                                    {{ $l->task_name }}
                                </td>

                                {{-- 3. Trigger Type (text-center badge) --}}
                                <td class="text-center">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold font-mono border {{ $l->triggered_by === 'manual_admin' ? 'border-purple-200 bg-purple-50 text-purple-700' : 'border-slate-200 bg-slate-50 text-slate-600' }}">
                                        {{ $l->triggered_by === 'manual_admin' ? 'Manual' : 'System Cron' }}
                                    </span>
                                </td>

                                {{-- 4. Duration (text-right font-mono) --}}
                                <td class="text-right font-mono text-xs text-slate-700">
                                    {{ number_format($l->duration_ms) }} ms
                                </td>

                                {{-- 5. Result Summary (text-left truncate) --}}
                                <td class="text-left text-slate-700 text-xs truncate max-w-xs" title="{{ $l->output_summary }}">
                                    {{ $l->output_summary }}
                                </td>

                                {{-- 6. Status (text-center badge) --}}
                                <td class="text-center">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold border {{ $l->status === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-rose-200 bg-rose-50 text-rose-700' }}">
                                        <i class="fas {{ $l->status === 'success' ? 'fa-check text-[9px]' : 'fa-xmark text-[9px]' }}"></i>
                                        <span>{{ ucfirst($l->status) }}</span>
                                    </span>
                                </td>

                                {{-- 7. Executed At (text-center font-mono) --}}
                                <td class="text-center font-mono text-slate-500 text-[11px]">
                                    {{ $l->created_at ? $l->created_at->format('d-M-Y h:i A') : 'N/A' }}
                                </td>

                                {{-- 8. Action (text-center) --}}
                                <td class="text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <button type="button" 
                                                @click="viewLogDetails({{ json_encode($l) }})" 
                                                class="w-6 h-6 rounded-md hover:bg-cyan-50 text-slate-400 hover:text-cyan-600 transition inline-flex items-center justify-center cursor-pointer" 
                                                title="View Log Details">
                                            <i class="fas fa-eye text-xs"></i>
                                        </button>
                                        <button type="button" 
                                                @click="deleteSingleLog({{ $l->id }}, '{{ addslashes($l->task_name) }}')" 
                                                class="w-6 h-6 rounded-md hover:bg-rose-50 text-slate-400 hover:text-rose-600 transition inline-flex items-center justify-center cursor-pointer" 
                                                title="Delete Log">
                                            <i class="fas fa-trash-can text-xs"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-8 text-slate-400">
                                    <div class="max-w-xs mx-auto space-y-2">
                                        <div class="w-10 h-10 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto text-sm">
                                            <i class="fas fa-clock-rotate-left"></i>
                                        </div>
                                        <p class="text-xs font-semibold text-slate-700">No Automation Logs Recorded</p>
                                        <p class="text-[11px] text-slate-400 leading-tight">Automation activity and scheduled cron telemetry will appear here once executed.</p>
                                        <button type="button" @click="runAutomationJob('autocut')" class="mt-2 h-[30px] px-3 bg-cyan-50 hover:bg-cyan-100 text-cyan-700 rounded-lg text-xs font-semibold border border-cyan-200 transition inline-flex items-center gap-1.5 cursor-pointer">
                                            <i class="fas fa-play text-[10px]"></i>
                                            <span>Run Auto-Cut Now</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($logs->hasPages())
                <div class="p-2.5 border-t border-slate-200 bg-slate-50/50 flex flex-col sm:flex-row items-center justify-between gap-2">
                    <span class="text-xs text-slate-500 font-medium">
                        Showing {{ $logs->firstItem() }} to {{ $logs->lastItem() }} of {{ $logs->total() }} execution logs
                    </span>
                    <div>
                        {{ $logs->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- 4. VIEW LOG DETAILS NATURAL MODAL (AGENTS.md Rule 3) --}}
    <template x-if="selectedLog !== null">
        <div class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50"
             @keydown.escape.window="selectedLog = null"
             x-cloak>
            <div class="bg-white rounded-xl shadow-2xl border border-slate-200 w-full max-w-lg overflow-hidden transform transition-all"
                 @click.away="selectedLog = null">
                
                {{-- Modal Header --}}
                <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100 bg-slate-50/60">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0">
                            <i class="fas fa-file-waveform"></i>
                        </div>
                        <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider" x-text="selectedLog.task_name"></h3>
                    </div>
                    <button type="button" @click="selectedLog = null" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 flex items-center justify-center transition cursor-pointer">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>

                {{-- Modal Body --}}
                <div class="p-4 space-y-3 text-xs">
                    <div class="grid grid-cols-2 gap-2 bg-slate-50 p-2.5 rounded-lg border border-slate-200/80">
                        <div>
                            <span class="text-[10px] uppercase font-bold text-slate-400 block">Trigger Source</span>
                            <span class="font-semibold text-slate-800" x-text="selectedLog.triggered_by === 'manual_admin' ? 'Manual (Admin User)' : 'Automated System Cron'"></span>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase font-bold text-slate-400 block">Execution Status</span>
                            <span class="font-bold uppercase font-mono" :class="selectedLog.status === 'success' ? 'text-emerald-600' : 'text-rose-600'" x-text="selectedLog.status"></span>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase font-bold text-slate-400 block">Duration</span>
                            <span class="font-mono text-slate-800" x-text="selectedLog.duration_ms + ' ms'"></span>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase font-bold text-slate-400 block">Executed At</span>
                            <span class="font-mono text-slate-800" x-text="selectedLog.created_at"></span>
                        </div>
                    </div>

                    <div>
                        <span class="text-[10px] uppercase font-bold text-slate-400 block mb-1">Output Summary &amp; Result</span>
                        <div class="p-2.5 bg-slate-900 text-slate-100 rounded-lg font-mono text-[11px] leading-relaxed break-words" x-text="selectedLog.output_summary"></div>
                    </div>

                    <template x-if="selectedLog.metrics && Object.keys(selectedLog.metrics).length > 0">
                        <div>
                            <span class="text-[10px] uppercase font-bold text-slate-400 block mb-1">Telemetry Metrics</span>
                            <pre class="p-2.5 bg-slate-50 border border-slate-200 text-slate-800 rounded-lg font-mono text-[10.5px] max-h-36 overflow-y-auto" x-text="JSON.stringify(selectedLog.metrics, null, 2)"></pre>
                        </div>
                    </template>
                </div>

                {{-- Modal Footer --}}
                <div class="px-4 py-2.5 border-t border-slate-100 bg-slate-50/50 flex items-center justify-end">
                    <button type="button" @click="selectedLog = null" class="h-[34px] px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-lg border border-slate-200/80 transition cursor-pointer">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </template>

</div>
@endsection

@push('scripts')
<script>
    function automationManager() {
        return {
            activeTab: '{{ $tab }}',
            activeMenu: null,
            menuPos: { top: 'auto', bottom: 'auto', right: 'auto', left: 'auto' },
            selectedLog: null,

            viewLogDetails(log) {
                this.selectedLog = log;
            },

            runAutomationJob(task) {
                Swal.fire({
                    title: 'Run Auto-Cut Execution?',
                    text: 'This will evaluate overdue customer subscriptions and enforce router disconnection rules immediately.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#0891b2',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Yes, Execute Now'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Executing Auto-Cut...',
                            text: 'Please wait while subscriptions are being checked.',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        fetch(`/admin/settings/automation/run/${task}`, {
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
                                title: 'Execution Completed',
                                text: data.message,
                                confirmButtonColor: '#0891b2'
                            }).then(() => {
                                window.location.reload();
                            });
                        })
                        .catch(() => {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Failed to run automation task.',
                                confirmButtonColor: '#0891b2'
                            });
                        });
                    }
                });
            },

            clearAllLogs() {
                Swal.fire({
                    title: 'Clear All Activity Logs?',
                    text: 'Are you sure you want to delete all automation logs? This audit trail cannot be undone.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e11d48',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Yes, Clear All'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch('{{ route("tenant.settings.automation.logs.clear") }}', {
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

            deleteSingleLog(id, taskName) {
                Swal.fire({
                    title: 'Delete Log Entry?',
                    text: `Are you sure you want to delete the log for '${taskName}'?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e11d48',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Yes, Delete'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch(`/admin/settings/automation/logs/${id}`, {
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
