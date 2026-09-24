@extends('tenant.layouts.app')

@section('title', 'System Automation & Auto-Cut - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
    {{-- Page-specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" x-data="automationManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">
    
    {{-- 1. TOP HEADER BAR (AGENTS.md Rule 2.A) --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 bg-white p-3 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center text-sm shadow-2xs flex-shrink-0">
                <i class="fas fa-robot"></i>
            </div>
            <h1 class="text-sm font-bold text-slate-800 tracking-tight">System Automation &amp; Auto-Cut</h1>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" @click="runAutomationJob('autocut')" class="px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-lg text-xs font-semibold border border-rose-200 transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-scissors text-rose-600 text-xs"></i>
                <span>Run Auto-Cut</span>
            </button>
            <a href="{{ route('tenant.settings.automation.print') }}" target="_blank" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-print text-slate-500 text-xs"></i>
                <span>Print Rules</span>
            </a>
            <button type="submit" form="automationForm" class="px-4 py-1.5 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
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
            <span>2. Activity Logs</span>
        </button>
    </div>

    {{-- TAB 1: AUTOMATION RULES --}}
    <div x-show="activeTab === 'rules'" class="space-y-3">
        <form id="automationForm" action="{{ route('tenant.settings.automation.update') }}" method="POST" class="space-y-3">
            @csrf

            {{-- Card 1: Auto-Cut Rules --}}
            <div class="bg-white p-5 rounded-b-xl border border-t-0 border-slate-200 shadow-xs space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="w-7 h-7 rounded-lg bg-rose-50 text-rose-600 border border-rose-100 flex items-center justify-center text-xs flex-shrink-0">
                            <i class="fas fa-scissors"></i>
                        </div>
                        <div>
                            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Auto-Cut Rules</h3>
                            <span class="text-[10.5px] text-slate-500 font-normal">Automatically disconnect customers when bill is unpaid</span>
                        </div>
                    </div>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="auto_cut_enabled" value="1" {{ old('auto_cut_enabled', $settings->auto_cut_enabled) ? 'checked' : '' }} class="rounded border-slate-300 text-rose-600 focus:ring-rose-500 w-4 h-4">
                        <span class="text-xs font-bold text-slate-800">Enable Auto-Cut</span>
                    </label>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    {{-- Grace Period Days --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Grace Period (Days) *</label>
                        <input type="number" min="0" max="30" name="grace_period_days" value="{{ old('grace_period_days', $settings->grace_period_days) }}" required class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-2 font-mono font-bold focus:bg-white focus:border-cyan-500 focus:outline-none">
                        <span class="text-[10px] text-slate-400 mt-0.5 block">0 = Cut on expiry date. 2 = Cut 2 days after expiry.</span>
                    </div>

                    {{-- Minimum Due Threshold --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Minimum Due for Cut ({{ $currencySymbol ?? '৳' }}) *</label>
                        <input type="number" min="0" step="1" name="min_due_threshold" value="{{ old('min_due_threshold', $settings->min_due_threshold) }}" required class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-2 font-mono font-bold text-rose-700 focus:bg-white focus:border-cyan-500 focus:outline-none">
                        <span class="text-[10px] text-slate-400 mt-0.5 block">Do not cut if due amount is less than this.</span>
                    </div>

                    {{-- Action Method --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Cut Method *</label>
                        <select name="auto_cut_action" required class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-2 focus:bg-white focus:border-cyan-500 focus:outline-none font-medium">
                            <option value="disable_secret" {{ old('auto_cut_action', $settings->auto_cut_action) === 'disable_secret' ? 'selected' : '' }}>Disable MikroTik Secret</option>
                            <option value="radius_pool" {{ old('auto_cut_action', $settings->auto_cut_action) === 'radius_pool' ? 'selected' : '' }}>Move to Expired IP Pool</option>
                            <option value="change_profile" {{ old('auto_cut_action', $settings->auto_cut_action) === 'change_profile' ? 'selected' : '' }}>Switch to Blocked Profile</option>
                        </select>
                        <span class="text-[10px] text-slate-400 mt-0.5 block">Action performed on router when customer expires.</span>
                    </div>

                    {{-- Daily Execution Time --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Auto-Cut Run Time *</label>
                        <input type="time" name="auto_cut_time" value="{{ old('auto_cut_time', $settings->auto_cut_time) }}" required class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-2 font-mono focus:bg-white focus:border-cyan-500 focus:outline-none">
                        <span class="text-[10px] text-slate-400 mt-0.5 block">Time of day when auto-cut runs (e.g. 02:00 AM).</span>
                    </div>
                </div>

                <div class="flex items-center gap-4 pt-2 border-t border-slate-100">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="auto_send_cut_sms" value="1" {{ old('auto_send_cut_sms', $settings->auto_send_cut_sms) ? 'checked' : '' }} class="rounded border-slate-300 text-cyan-600 focus:ring-cyan-500">
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
                            <input type="checkbox" name="auto_billing_enabled" value="1" {{ old('auto_billing_enabled', $settings->auto_billing_enabled) ? 'checked' : '' }} class="rounded border-slate-300 text-cyan-600">
                            <span class="text-xs font-bold text-slate-700">Enable</span>
                        </label>
                    </div>

                    <div class="grid grid-cols-2 gap-3 text-xs">
                        <div>
                            <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Generate (Days Before Expiry) *</label>
                            <input type="number" min="1" max="15" name="billing_generation_day" value="{{ old('billing_generation_day', $settings->billing_generation_day ?: 3) }}" required class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none font-mono font-bold text-cyan-800">
                            <span class="text-[9.5px] text-slate-400 mt-0.5 block">e.g. 3 Days before customer's expiry.</span>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Run Time *</label>
                            <input type="time" name="billing_generation_time" value="{{ old('billing_generation_time', $settings->billing_generation_time ?: '00:05') }}" required class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none font-mono">
                            <span class="text-[9.5px] text-slate-400 mt-0.5 block">Recommended at midnight (12:05 AM).</span>
                        </div>
                    </div>

                    <div class="pt-1">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="auto_send_bill_sms" value="1" {{ old('auto_send_bill_sms', $settings->auto_send_bill_sms) ? 'checked' : '' }} class="rounded border-slate-300 text-cyan-600">
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
                            <input type="checkbox" name="auto_reconnect_enabled" value="1" {{ old('auto_reconnect_enabled', $settings->auto_reconnect_enabled) ? 'checked' : '' }} class="rounded border-slate-300 text-emerald-600">
                            <span class="text-xs font-bold text-slate-700">Enable</span>
                        </label>
                    </div>

                    <p class="text-xs text-slate-600 leading-relaxed">
                        When a disconnected customer pays their bill (via bKash, Nagad, or Cash), their line is immediately re-enabled on the router.
                    </p>

                    <div class="pt-2 border-t border-slate-100">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="auto_send_restore_sms" value="1" {{ old('auto_send_restore_sms', $settings->auto_send_restore_sms) ? 'checked' : '' }} class="rounded border-slate-300 text-emerald-600">
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
                            <input type="checkbox" name="expiry_reminders_enabled" value="1" {{ old('expiry_reminders_enabled', $settings->expiry_reminders_enabled) ? 'checked' : '' }} class="rounded border-slate-300 text-amber-600">
                            <span class="text-xs font-bold text-slate-700">Enable</span>
                        </label>
                    </div>

                    <div class="grid grid-cols-2 gap-3 text-xs">
                        <div>
                            <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Reminder (Days Before Expiry) *</label>
                            <input type="number" min="1" max="10" name="reminder_1_days_before" value="{{ old('reminder_1_days_before', $settings->reminder_1_days_before ?: 2) }}" required class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none font-mono font-bold text-amber-800">
                            <span class="text-[9.5px] text-slate-400 mt-0.5 block">e.g. 2 Days before customer's expiry.</span>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Send Time *</label>
                            <input type="time" name="reminder_dispatch_time" value="{{ old('reminder_dispatch_time', $settings->reminder_dispatch_time ?: '09:00') }}" required class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none font-mono">
                            <span class="text-[9.5px] text-slate-400 mt-0.5 block">Recommended in morning (09:00 AM).</span>
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
                            <h4 class="text-xs font-bold text-slate-800">Automatic Backup</h4>
                        </div>
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="checkbox" name="auto_backup_enabled" value="1" {{ old('auto_backup_enabled', $settings->auto_backup_enabled) ? 'checked' : '' }} class="rounded border-slate-300 text-purple-600">
                            <span class="text-xs font-bold text-slate-700">Enable</span>
                        </label>
                    </div>

                    <div class="grid grid-cols-3 gap-2.5 text-xs">
                        <div>
                            <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Frequency</label>
                            <select name="backup_frequency" required class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none">
                                <option value="daily" {{ old('backup_frequency', $settings->backup_frequency) === 'daily' ? 'selected' : '' }}>Daily</option>
                                <option value="weekly" {{ old('backup_frequency', $settings->backup_frequency) === 'weekly' ? 'selected' : '' }}>Weekly</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Backup Time</label>
                            <input type="time" name="backup_time" value="{{ old('backup_time', $settings->backup_time) }}" required class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none font-mono">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Keep (Days)</label>
                            <input type="number" min="7" max="365" name="backup_retention_days" value="{{ old('backup_retention_days', $settings->backup_retention_days) }}" required class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none font-mono">
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
                                <input type="checkbox" name="auto_backup_email_enabled" value="1" {{ old('auto_backup_email_enabled', $settings->auto_backup_email_enabled) ? 'checked' : '' }} class="rounded border-slate-300 text-cyan-600">
                                <span class="text-xs font-bold text-slate-700">Enable</span>
                            </label>
                        </div>
                        <div>
                            <input type="email" name="backup_destination_email" value="{{ old('backup_destination_email', $settings->backup_destination_email ?: ($tenant->billing_email ?: $tenant->email)) }}" placeholder="e.g. backup@yourdomain.com" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 font-mono text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-none">
                            <span class="text-[9.5px] text-slate-400 mt-0.5 block">Delivered via SMTP Gateway configured on <a href="{{ route('tenant.settings.profile') }}" class="text-cyan-600 underline font-medium">Company Profile</a>.</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Centered Submit Action Bar --}}
            <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-xs flex items-center justify-between">
                <span class="text-xs text-slate-500">Automation rules run automatically in the background.</span>
                <button type="submit" class="px-5 py-2 bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs rounded-lg shadow-xs transition flex items-center gap-2 cursor-pointer">
                    <i class="fas fa-floppy-disk text-xs"></i>
                    <span>Save Rules</span>
                </button>
            </div>
        </form>
    </div>

    {{-- TAB 2: ACTIVITY LOGS --}}
    <div x-show="activeTab === 'logs'" class="space-y-3">
        {{-- Search & Multi-Filter Bar with Actions (AGENTS.md Rule 2.C) --}}
        <div class="bg-white p-3 rounded-xl border border-slate-200 shadow-xs flex flex-col lg:flex-row lg:items-center justify-between gap-3">
            <form method="GET" action="{{ route('tenant.settings.automation') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-2 flex-1">
                <input type="hidden" name="tab" value="logs">
                
                {{-- Search --}}
                <div class="sm:col-span-1">
                    <div class="relative">
                        <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input type="text" name="search" value="{{ $search }}" placeholder="Search logs..." class="w-full pl-8 pr-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                    </div>
                </div>

                {{-- Status --}}
                <div>
                    <select name="status" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                        <option value="all">All Statuses</option>
                        <option value="success" {{ $status === 'success' ? 'selected' : '' }}>Success</option>
                        <option value="failed" {{ $status === 'failed' ? 'selected' : '' }}>Failed</option>
                    </select>
                </div>

                {{-- Filter & Reset Buttons (AGENTS.md Rule 2.C) --}}
                <div class="flex items-center gap-1.5">
                    <button type="submit" class="flex-1 py-1.5 px-3 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                        <i class="fas fa-filter text-[10px]"></i>
                        <span>Filter</span>
                    </button>
                    <a href="{{ route('tenant.settings.automation', ['tab' => 'logs']) }}" class="py-1.5 px-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer" title="Reset Filters">
                        <i class="fas fa-rotate-left text-[10px]"></i>
                        <span>Reset</span>
                    </a>
                </div>
            </form>

            {{-- Log Management Actions --}}
            <div class="flex items-center gap-2 border-t lg:border-t-0 pt-2 lg:pt-0 border-slate-100 flex-shrink-0">
                <a href="{{ route('tenant.settings.automation.export') }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition flex items-center gap-1.5 cursor-pointer">
                    <i class="fas fa-file-csv text-emerald-600 text-xs"></i>
                    <span>Export CSV</span>
                </a>
                <button type="button" @click="clearAllLogs()" class="px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-lg text-xs font-semibold border border-rose-200 transition flex items-center gap-1.5 cursor-pointer">
                    <i class="fas fa-trash-can text-rose-600 text-xs"></i>
                    <span>Clear Logs</span>
                </button>
            </div>
        </div>

        {{-- Master Logs Table (<table class="saas-table"> - AGENTS.md Rule 2.D) --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="saas-table">
                    <thead>
                        <tr>
                            <th class="w-10 text-center">#</th>
                            <th>Task Name</th>
                            <th>Trigger</th>
                            <th class="text-center">Duration</th>
                            <th>Result Summary</th>
                            <th class="text-center">Status</th>
                            <th>Date &amp; Time</th>
                            <th class="w-12 text-center no-sort">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $idx => $l)
                            <tr>
                                <td class="text-center text-slate-400 font-mono text-xs">{{ $logs->firstItem() + $idx }}</td>
                                
                                {{-- Task Name --}}
                                <td class="font-semibold text-slate-800">
                                    {{ $l->task_name }}
                                </td>

                                {{-- Trigger Type --}}
                                <td>
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold font-mono border {{ $l->triggered_by === 'manual_admin' ? 'border-purple-200 bg-purple-50 text-purple-700' : 'border-slate-200 bg-slate-50 text-slate-600' }}">
                                        {{ $l->triggered_by === 'manual_admin' ? 'Manual' : 'Auto' }}
                                    </span>
                                </td>

                                {{-- Duration --}}
                                <td class="text-center font-mono text-xs text-slate-700">
                                    {{ $l->duration_ms }} ms
                                </td>

                                {{-- Summary --}}
                                <td class="text-slate-700 text-xs truncate max-w-sm" title="{{ $l->output_summary }}">
                                    {{ $l->output_summary }}
                                </td>

                                {{-- Status --}}
                                <td class="text-center">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold border {{ $l->status === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-rose-200 bg-rose-50 text-rose-700' }}">
                                        <span>{{ ucfirst($l->status) }}</span>
                                    </span>
                                </td>

                                {{-- Executed At --}}
                                <td class="font-mono text-slate-500 text-[11px]">
                                    {{ $l->created_at ? $l->created_at->format('d-M-Y h:i A') : 'N/A' }}
                                </td>

                                {{-- Action --}}
                                <td class="text-center">
                                    <button type="button" 
                                            @click="deleteSingleLog({{ $l->id }}, '{{ addslashes($l->task_name) }}')" 
                                            class="w-6 h-6 rounded-md hover:bg-rose-50 text-slate-400 hover:text-rose-600 transition inline-flex items-center justify-center cursor-pointer" 
                                            title="Delete Log">
                                        <i class="fas fa-trash-can text-xs"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-8 text-slate-400">
                                    <span class="text-xs font-medium text-slate-500">No automation logs found.</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($logs->hasPages())
                <div class="p-3 border-t border-slate-200 bg-slate-50/50 flex items-center justify-between">
                    <span class="text-xs text-slate-500">
                        Showing {{ $logs->firstItem() }} to {{ $logs->lastItem() }} of {{ $logs->total() }} logs
                    </span>
                    <div>
                        {{ $logs->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    function automationManager() {
        return {
            activeTab: '{{ $tab }}',
            activeMenu: null,
            menuPos: { top: 'auto', bottom: 'auto', right: 'auto', left: 'auto' },

            runAutomationJob(task) {
                Swal.fire({
                    title: 'Run Auto-Cut?',
                    text: 'Are you sure you want to run the auto-cut task now?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#0891b2',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Yes, Run Now'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Running Auto-Cut...',
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
                                title: 'Completed',
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
                                text: 'Failed to run task.',
                                confirmButtonColor: '#0891b2'
                            });
                        });
                    }
                });
            },

            clearAllLogs() {
                Swal.fire({
                    title: 'Clear All Logs?',
                    text: 'Are you sure you want to delete all activity logs? This cannot be undone.',
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
                    title: 'Delete Log?',
                    text: `Delete log for '${taskName}'?`,
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

