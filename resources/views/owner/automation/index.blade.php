@extends('owner.layouts.app')

@section('page-title', 'Automation & Queue Supervisor')

@section('content')
<div class="space-y-4" x-data="{
    copied: false,
    copyCode(text) {
        navigator.clipboard.writeText(text);
        this.copied = true;
        setTimeout(() => this.copied = false, 2500);
    }
}">


    <!-- Top Header Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-3.5 rounded-xl border border-slate-200 shadow-xs">
        <div>
            <div class="flex items-center gap-2">
                <h2 class="text-sm font-bold text-slate-900 tracking-tight">SaaS Automation & Cron Engine (Queue & Supervisor Control)</h2>
                <span class="px-2 py-0.5 rounded-full {{ $cronStatus === 'healthy' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' }} text-[10px] font-bold font-mono">
                    Cron: {{ $cronStatusText }}
                </span>
            </div>
            <p class="text-[11px] text-slate-500 mt-0.5">Scheduler → Dispatcher → Queue Worker → Supervisor → Idempotent Service → Audit Ledger</p>
        </div>

        <div class="flex items-center gap-2">
            <form action="{{ route('owner.automation.dispatch', 'heartbeat_now') }}" method="POST">
                @csrf
                <button type="submit" class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition flex items-center gap-1.5 shadow-2xs">
                    <i class="fas fa-heartbeat text-rose-500 text-[11px]"></i>
                    <span>Ping Heartbeat</span>
                </button>
            </form>
            <form action="{{ route('owner.automation.dispatch', 'full_engine') }}" method="POST" onsubmit="return confirm('Run Master Billing Engine right now?');">
                @csrf
                <button type="submit" class="px-3.5 py-1.5 rounded-lg bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs shadow-xs transition flex items-center gap-1.5">
                    <i class="fas fa-bolt text-[11px]"></i>
                    <span>Run Master Engine</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Queue & Cron Health 4-Card Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <!-- Card 1: Queue Pending Jobs -->
        <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-xs flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-base">
                <i class="fas fa-layer-group"></i>
            </div>
            <div>
                <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Pending Jobs</p>
                <p class="text-lg font-black text-slate-900 font-mono">{{ number_format($queueHealth['pending_jobs']) }}</p>
                <span class="text-[10px] text-slate-500">{{ $queueHealth['worker_status'] }}</span>
            </div>
        </div>

        <!-- Card 2: Failed Jobs -->
        <div class="bg-white p-3.5 rounded-xl border {{ $queueHealth['failed_jobs'] > 0 ? 'border-rose-300 bg-rose-50/20' : 'border-slate-200' }} shadow-xs flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl {{ $queueHealth['failed_jobs'] > 0 ? 'bg-rose-100 text-rose-600' : 'bg-slate-100 text-slate-500' }} flex items-center justify-center text-base">
                <i class="fas fa-triangle-exclamation"></i>
            </div>
            <div>
                <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Failed Jobs</p>
                <p class="text-lg font-black {{ $queueHealth['failed_jobs'] > 0 ? 'text-rose-600' : 'text-slate-900' }} font-mono">{{ number_format($queueHealth['failed_jobs']) }}</p>
                <span class="text-[10px] text-slate-500">{{ $queueHealth['failed_jobs'] > 0 ? 'Action required' : 'All clear' }}</span>
            </div>
        </div>

        <!-- Card 3: Scheduler Heartbeat -->
        <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-xs flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl {{ $cronStatus === 'healthy' ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600' }} flex items-center justify-center text-base">
                <i class="fas fa-heart-pulse"></i>
            </div>
            <div>
                <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Host Scheduler</p>
                <p class="text-xs font-bold {{ $cronStatus === 'healthy' ? 'text-emerald-700' : 'text-amber-700' }}">{{ $cronStatusText }}</p>
                <span class="text-[10px] text-slate-500 font-mono">{{ $heartbeatCarbon ? $heartbeatCarbon->diffForHumans() : 'Never' }}</span>
            </div>
        </div>

        <!-- Card 4: Total Executions -->
        <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-xs flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-base">
                <i class="fas fa-chart-line"></i>
            </div>
            <div>
                <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Automation Runs</p>
                <p class="text-lg font-black text-slate-900 font-mono">{{ number_format($totalRuns) }}</p>
                <span class="text-[10px] text-emerald-600 font-semibold">{{ $successRuns }} Passed</span>
            </div>
        </div>
    </div>

    <!-- 1. Live Cron Heartbeat & Server Setup Card -->
    <div class="bg-slate-900 text-white rounded-2xl p-4 sm:p-5 border border-slate-800 shadow-lg space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-800 pb-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl {{ $cronStatus === 'healthy' ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-rose-500/20 text-rose-400 border border-rose-500/30' }} flex items-center justify-center text-lg flex-shrink-0">
                    <i class="fas fa-clock"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-white flex items-center gap-2">
                        <span>Server Host Cron Scheduler:</span>
                        @if($cronStatus === 'healthy')
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 font-mono">
                                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                ACTIVE & HEALTHY
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-500/20 text-rose-400 border border-rose-500/30 font-mono">
                                <span class="w-2 h-2 rounded-full bg-rose-400"></span>
                                {{ strtoupper($cronStatusText) }}
                            </span>
                        @endif
                    </h3>
                    <p class="text-[11px] text-slate-400 mt-0.5 font-mono">
                        Last Heartbeat: {{ $heartbeatCarbon ? $heartbeatCarbon->format('d M, Y h:i:s A') . ' (' . $heartbeatCarbon->diffForHumans() . ')' : 'No heartbeats recorded yet' }}
                    </p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Host Crontab Command -->
            <div class="space-y-1.5">
                <label class="text-[11px] font-semibold text-slate-300 block">1. Server Crontab Setup Command (Runs once per minute):</label>
                <div class="flex items-center gap-2 bg-slate-950 p-2.5 rounded-xl border border-slate-800 font-mono text-[11px] text-emerald-400 overflow-x-auto">
                    <span class="flex-1 select-all whitespace-nowrap">{{ $cronSnippet }}</span>
                    <button type="button" 
                            @click="copyCode('{{ addslashes($cronSnippet) }}')"
                            class="px-3 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 font-sans text-xs font-semibold flex items-center gap-1 transition flex-shrink-0">
                        <i class="fas" :class="copied ? 'fa-check text-emerald-400' : 'fa-copy'"></i>
                        <span x-text="copied ? 'Copied!' : 'Copy'"></span>
                    </button>
                </div>
            </div>

            <!-- Supervisor Worker Blueprint -->
            <div class="space-y-1.5">
                <label class="text-[11px] font-semibold text-slate-300 block">2. Supervisor Queue Worker Command:</label>
                <div class="flex items-center gap-2 bg-slate-950 p-2.5 rounded-xl border border-slate-800 font-mono text-[11px] text-blue-400 overflow-x-auto">
                    <span class="flex-1 select-all whitespace-nowrap">{{ $supervisorSnippet }}</span>
                    <button type="button" 
                            @click="copyCode('{{ addslashes($supervisorSnippet) }}')"
                            class="px-3 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 font-sans text-xs font-semibold flex items-center gap-1 transition flex-shrink-0">
                        <i class="fas" :class="copied ? 'fa-check text-blue-400' : 'fa-copy'"></i>
                        <span x-text="copied ? 'Copied!' : 'Copy'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Webhook Ingestion Endpoints (Item 20 & Item 15) -->
    <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-xs space-y-3">
        <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-xs">
                    <i class="fas fa-network-wired"></i>
                </div>
                <div>
                    <h3 class="font-bold text-slate-900 text-xs">Payment Gateway Webhook / IPN Endpoints (Tenant-Isolated & HMAC Verified)</h3>
                    <p class="text-[10.5px] text-slate-500">Provide these URLs in payment gateway developer dashboards</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
            <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200 space-y-1">
                <div class="flex items-center justify-between">
                    <span class="font-bold text-slate-700 text-[11px] flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-pink-500"></span>
                        bKash Webhook / IPN URL
                    </span>
                    <button type="button" @click="copyCode('{{ route('api.webhook.bkash') }}')" class="text-[10px] text-blue-600 hover:underline">Copy URL</button>
                </div>
                <code class="text-[10.5px] text-slate-600 block font-mono break-all select-all">{{ route('api.webhook.bkash') }}</code>
            </div>

            <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200 space-y-1">
                <div class="flex items-center justify-between">
                    <span class="font-bold text-slate-700 text-[11px] flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                        Nagad Webhook / Callback URL
                    </span>
                    <button type="button" @click="copyCode('{{ route('api.webhook.nagad') }}')" class="text-[10px] text-blue-600 hover:underline">Copy URL</button>
                </div>
                <code class="text-[10.5px] text-slate-600 block font-mono break-all select-all">{{ route('api.webhook.nagad') }}</code>
            </div>
        </div>
    </div>

    <!-- Failed Jobs Section (Item 19) -->
    @if(count($failedJobsList) > 0)
        <div class="bg-white rounded-xl border border-rose-200 shadow-xs p-4 space-y-3">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-rose-100 pb-3">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center text-xs">
                        <i class="fas fa-circle-exclamation"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-rose-900 text-xs">Failed Queue Jobs ({{ count($failedJobsList) }})</h3>
                        <p class="text-[10.5px] text-slate-500">Jobs that encountered unhandled exceptions and were captured for retry</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <form action="{{ route('owner.automation.failed-jobs.retry-all') }}" method="POST">
                        @csrf
                        <button type="submit" class="px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-bold text-[11px] transition flex items-center gap-1">
                            <i class="fas fa-rotate-right"></i>
                            <span>Retry All Failed</span>
                        </button>
                    </form>
                    <form action="{{ route('owner.automation.failed-jobs.flush') }}" method="POST" onsubmit="return confirm('Flush all failed jobs permanently?');">
                        @csrf
                        <button type="submit" class="px-3 py-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-bold text-[11px] transition flex items-center gap-1">
                            <i class="fas fa-trash-can"></i>
                            <span>Flush All</span>
                        </button>
                    </form>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 text-[10.5px] uppercase font-bold tracking-wider border-b border-slate-200">
                            <th class="p-2.5">ID</th>
                            <th class="p-2.5">Job Name</th>
                            <th class="p-2.5">Queue</th>
                            <th class="p-2.5">Failed At</th>
                            <th class="p-2.5">Error Summary</th>
                            <th class="p-2.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-mono text-[11px]">
                        @foreach($failedJobsList as $job)
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="p-2.5 font-bold text-slate-800">#{{ $job['id'] }}</td>
                                <td class="p-2.5 font-bold text-slate-900 font-sans">{{ $job['name'] }}</td>
                                <td class="p-2.5 text-slate-600">{{ $job['queue'] }}</td>
                                <td class="p-2.5 text-slate-500 font-sans">{{ $job['failed_at'] }}</td>
                                <td class="p-2.5 text-rose-600 max-w-xs truncate font-sans" title="{{ $job['full_exception'] }}">{{ $job['exception_summary'] }}</td>
                                <td class="p-2.5 text-right font-sans">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <form action="{{ route('owner.automation.failed-jobs.retry', $job['id']) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="px-2.5 py-1 rounded-md bg-blue-50 text-blue-700 hover:bg-blue-100 font-bold text-[10.5px]">Retry</button>
                                        </form>
                                        <form action="{{ route('owner.automation.failed-jobs.delete', $job['id']) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-2.5 py-1 rounded-md bg-rose-50 text-rose-700 hover:bg-rose-100 font-bold text-[10.5px]">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- 2-Column Grid: Policy Settings & Manual Dispatchers -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        <!-- Column 1 & 2: Dynamic Policy Settings Form -->
        <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 p-4 sm:p-5 shadow-xs space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-xs">
                        <i class="fas fa-sliders"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-900 text-xs">Dynamic Automation Policies</h3>
                        <p class="text-[11px] text-slate-500">Configure business rules without changing codebase</p>
                    </div>
                </div>
            </div>

            <form action="{{ route('owner.automation.policy.update') }}" method="POST" class="space-y-4 text-xs">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Invoice Generation Lead Days -->
                    <div class="space-y-1">
                        <label class="font-semibold text-slate-700 block text-[11px]">
                            Invoice Generation Lead Time *
                        </label>
                        <div class="flex items-center gap-2">
                            <input type="number" name="automation_invoice_lead_days" value="{{ $policy['invoice_lead_days'] }}" min="1" max="30" required class="w-24 px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs font-mono font-bold focus:ring-1 focus:ring-blue-500">
                            <span class="text-slate-500 text-[11px]">Days before expiry</span>
                        </div>
                        <span class="text-[10px] text-slate-400 block">Upcoming subscription invoices will be generated in advance.</span>
                    </div>

                    <!-- Grace Period Days -->
                    <div class="space-y-1">
                        <label class="font-semibold text-slate-700 block text-[11px]">
                            Grace Period Duration *
                        </label>
                        <div class="flex items-center gap-2">
                            <input type="number" name="automation_grace_period_days" value="{{ $policy['grace_period_days'] }}" min="0" max="30" required class="w-24 px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs font-mono font-bold focus:ring-1 focus:ring-blue-500">
                            <span class="text-slate-500 text-[11px]">Days after due date</span>
                        </div>
                        <span class="text-[10px] text-slate-400 block">Tenants get grace period before non-destructive suspension.</span>
                    </div>

                    <!-- Daily Run Hour -->
                    <div class="space-y-1">
                        <label class="font-semibold text-slate-700 block text-[11px]">
                            Preferred Master Run Hour *
                        </label>
                        <input type="time" name="automation_cron_run_hour" value="{{ $policy['cron_run_hour'] }}" required class="w-36 px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs font-mono font-bold focus:ring-1 focus:ring-blue-500">
                        <span class="text-[10px] text-slate-400 block">Daily time for executing master invoice & renewal jobs.</span>
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-100 space-y-2.5">
                    <!-- Auto Suspend Policy Switch -->
                    <label class="flex items-start gap-2.5 p-2.5 rounded-xl bg-slate-50 border border-slate-200 cursor-pointer hover:bg-slate-100/70 transition">
                        <input type="checkbox" name="automation_auto_suspend_enabled" value="1" {{ $policy['auto_suspend_enabled'] ? 'checked' : '' }} class="mt-0.5 rounded text-blue-600 focus:ring-blue-500">
                        <div class="leading-tight">
                            <span class="font-bold text-slate-800 block text-[11.5px]">Enforce Automated Non-Destructive Suspensions</span>
                            <span class="text-[10.5px] text-slate-500">Automatically pause service access after grace period expires, keeping billing portal and data 100% safe.</span>
                        </div>
                    </label>

                    <!-- Auto Wallet Renew Switch -->
                    <label class="flex items-start gap-2.5 p-2.5 rounded-xl bg-slate-50 border border-slate-200 cursor-pointer hover:bg-slate-100/70 transition">
                        <input type="checkbox" name="automation_auto_wallet_renew_enabled" value="1" {{ $policy['auto_wallet_renew_enabled'] ? 'checked' : '' }} class="mt-0.5 rounded text-blue-600 focus:ring-blue-500">
                        <div class="leading-tight">
                            <span class="font-bold text-slate-800 block text-[11.5px]">Auto-Settle Renewals from Tenant Prepaid Wallets</span>
                            <span class="text-[10.5px] text-slate-500">If a tenant has sufficient prepaid advance balance, automatically debit wallet and renew subscription.</span>
                        </div>
                    </label>
                </div>

                <div class="pt-2 flex justify-end">
                    <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-xs transition flex items-center gap-1.5">
                        <i class="fas fa-save"></i>
                        <span>Save Automation Policies</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Column 3: 1-Click Manual Command Dispatchers -->
        <div class="bg-white rounded-xl border border-slate-200 p-4 sm:p-5 shadow-xs space-y-3.5">
            <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
                <div class="w-7 h-7 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center text-xs">
                    <i class="fas fa-terminal"></i>
                </div>
                <div>
                    <h3 class="font-bold text-slate-900 text-xs">Manual Dispatchers</h3>
                    <p class="text-[11px] text-slate-500">Trigger individual engine tasks</p>
                </div>
            </div>

            <div class="space-y-2 text-xs">
                <!-- Task 1: Invoices Only -->
                <form action="{{ route('owner.automation.dispatch', 'invoices_only') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full p-2.5 rounded-xl bg-slate-50 hover:bg-blue-50 hover:border-blue-200 border border-slate-200 text-left transition flex items-center justify-between group">
                        <div class="flex items-center gap-2.5">
                            <div class="w-7 h-7 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center text-xs">
                                <i class="fas fa-file-invoice"></i>
                            </div>
                            <div>
                                <span class="font-bold text-slate-800 group-hover:text-blue-700 block text-[11px]">Generate Invoices</span>
                                <span class="text-[10px] text-slate-400">Generate upcoming period vouchers</span>
                            </div>
                        </div>
                        <i class="fas fa-play text-[10px] text-slate-400 group-hover:text-blue-600"></i>
                    </button>
                </form>

                <!-- Task 2: Suspensions Only -->
                <form action="{{ route('owner.automation.dispatch', 'suspensions_only') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full p-2.5 rounded-xl bg-slate-50 hover:bg-rose-50 hover:border-rose-200 border border-slate-200 text-left transition flex items-center justify-between group">
                        <div class="flex items-center gap-2.5">
                            <div class="w-7 h-7 rounded-lg bg-rose-100 text-rose-700 flex items-center justify-center text-xs">
                                <i class="fas fa-lock"></i>
                            </div>
                            <div>
                                <span class="font-bold text-slate-800 group-hover:text-rose-700 block text-[11px]">Enforce Suspensions</span>
                                <span class="text-[10px] text-slate-400">Check overdue & grace status</span>
                            </div>
                        </div>
                        <i class="fas fa-play text-[10px] text-slate-400 group-hover:text-rose-600"></i>
                    </button>
                </form>

                <!-- Task 3: Reminders Only -->
                <form action="{{ route('owner.automation.dispatch', 'reminders_only') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full p-2.5 rounded-xl bg-slate-50 hover:bg-amber-50 hover:border-amber-200 border border-slate-200 text-left transition flex items-center justify-between group">
                        <div class="flex items-center gap-2.5">
                            <div class="w-7 h-7 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center text-xs">
                                <i class="fas fa-bell"></i>
                            </div>
                            <div>
                                <span class="font-bold text-slate-800 group-hover:text-amber-700 block text-[11px]">Dispatch Reminders</span>
                                <span class="text-[10px] text-slate-400">Send multi-stage notifications</span>
                            </div>
                        </div>
                        <i class="fas fa-play text-[10px] text-slate-400 group-hover:text-amber-600"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Execution Logs Table -->
    <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-xs space-y-3">
        <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center text-xs">
                    <i class="fas fa-list-check"></i>
                </div>
                <h3 class="font-bold text-slate-900 text-xs">Automation Execution Ledger</h3>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 text-[10.5px] uppercase font-bold tracking-wider border-b border-slate-200">
                        <th class="p-2.5">Timestamp</th>
                        <th class="p-2.5">Task Name</th>
                        <th class="p-2.5">Trigger</th>
                        <th class="p-2.5">Status</th>
                        <th class="p-2.5">Duration</th>
                        <th class="p-2.5">Summary</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-mono text-[11px]">
                    @forelse($logs as $log)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="p-2.5 text-slate-500 font-sans">{{ $log->created_at->format('d M, Y h:i A') }}</td>
                            <td class="p-2.5 font-bold text-slate-800">{{ $log->task_name }}</td>
                            <td class="p-2.5 text-slate-600">{{ $log->triggered_by }}</td>
                            <td class="p-2.5">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold font-sans {{ $log->status === 'success' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' }}">
                                    {{ strtoupper($log->status) }}
                                </span>
                            </td>
                            <td class="p-2.5 text-slate-500">{{ $log->duration_ms }} ms</td>
                            <td class="p-2.5 text-slate-700 font-sans max-w-sm truncate" title="{{ $log->output_summary }}">{{ $log->output_summary }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-4 text-center text-slate-400 font-sans">No automation logs recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="pt-2 border-t border-slate-100">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
