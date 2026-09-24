@extends('owner.layouts.app')

@section('page-title', $tenant->name . ' - Complete Overview')

@section('content')
<div class="space-y-4" x-data="{
    extendModalOpen: false,
    walletModalOpen: false,
    subModalOpen: false,
    extendType: '1_month',
    customDate: '',

    copyToClipboard(text) {
        navigator.clipboard.writeText(text);
        alert('Copied to clipboard: ' + text);
    }
}">


    @php
        $admin = $tenant->users->where('role', 'isp_admin')->first() ?? $tenant->users->first();
        $activeSub = $tenant->subscriptions->first();
        $expiryDate = $tenant->subscription_expires_at ? \Carbon\Carbon::parse($tenant->subscription_expires_at) : ($activeSub?->current_period_end);
        $isExpired = $expiryDate ? $expiryDate->isPast() : false;
        $daysLeft = $expiryDate ? ceil(now()->floatDiffInDays($expiryDate, false)) : null;
        $wallet = $tenant->wallet;
    @endphp

    <!-- Top Organization Header & Actions Banner -->
    <div class="bg-white p-4 sm:p-5 rounded-xl border border-slate-200 shadow-xs flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        
        <div class="flex items-start sm:items-center gap-3.5">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white flex items-center justify-center font-extrabold text-base shadow-xs flex-shrink-0">
                {{ strtoupper(substr($tenant->name, 0, 1)) }}
            </div>
            <div>
                <div class="flex items-center gap-2 flex-wrap">
                    <h2 class="text-base font-bold text-slate-900 tracking-tight">{{ $tenant->name }}</h2>
                    <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 font-mono text-[10px] font-bold border border-slate-200">
                        ID: #{{ $tenant->id }}
                    </span>
                    @if ($tenant->status === 'active' && !$isExpired)
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            Active License
                        </span>
                    @elseif($tenant->status === 'active' && $isExpired)
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                            Expired Subscription
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                            Suspended Account
                        </span>
                    @endif
                </div>
                <p class="text-[11px] text-slate-500 mt-0.5 font-mono">
                    {{ $tenant->company_name ?: 'ISP Broadband Service' }} • Joined {{ $tenant->created_at ? $tenant->created_at->format('d M, Y') : 'N/A' }}
                </p>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center gap-2 flex-wrap">
            
            <!-- 1-Click Login / Impersonate -->
            <a href="{{ route('owner.tenants.impersonate', $tenant) }}" 
               class="px-3.5 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-xs transition flex items-center gap-1.5">
                <i class="fas fa-sign-in-alt text-[11px]"></i>
                <span>Login as ISP Admin</span>
            </a>

            <!-- Quick Extend Validity -->
            <button type="button" 
                    @click="extendModalOpen = true"
                    class="px-3.5 py-2 rounded-lg bg-purple-50 hover:bg-purple-600 hover:text-white text-purple-700 border border-purple-200 font-bold text-xs transition flex items-center gap-1.5 shadow-2xs">
                <i class="fas fa-calendar-plus text-[11px]"></i>
                <span>Extend Validity</span>
            </button>

            <!-- Adjust Wallet -->
            <button type="button" 
                    @click="walletModalOpen = true"
                    class="px-3.5 py-2 rounded-lg bg-emerald-50 hover:bg-emerald-600 hover:text-white text-emerald-700 border border-emerald-200 font-bold text-xs transition flex items-center gap-1.5 shadow-2xs">
                <i class="fas fa-wallet text-[11px]"></i>
                <span>Adjust Wallet</span>
            </button>

            <!-- Edit Settings -->
            <a href="{{ route('owner.tenants.edit', $tenant) }}" 
               class="px-3 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition flex items-center gap-1.5">
                <i class="fas fa-pencil-alt text-[10px]"></i>
                <span>Edit</span>
            </a>

            <!-- Toggle Status -->
            <form action="{{ route('owner.tenants.toggle-status', $tenant) }}" method="POST" onsubmit="return confirm('Change status for {{ addslashes($tenant->name) }}?');" class="inline">
                @csrf
                <button type="submit" 
                        class="px-3 py-2 rounded-lg {{ $tenant->status === 'active' ? 'bg-amber-50 text-amber-600 hover:bg-amber-600 hover:text-white border border-amber-200' : 'bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white border border-emerald-200' }} font-semibold text-xs transition flex items-center gap-1.5 shadow-2xs">
                    <i class="fas {{ $tenant->status === 'active' ? 'fa-ban' : 'fa-check' }} text-[10px]"></i>
                    <span>{{ $tenant->status === 'active' ? 'Suspend' : 'Activate' }}</span>
                </button>
            </form>

            <!-- Delete Button -->
            <button type="button" 
                    onclick="confirmDeleteTenant({{ $tenant->id }}, '{{ addslashes($tenant->name) }}')"
                    class="px-3 py-2 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white border border-rose-200 font-semibold text-xs transition flex items-center gap-1.5 shadow-2xs">
                <i class="fas fa-trash-alt text-[10px]"></i>
                <span>Delete</span>
            </button>
            <form id="delete-tenant-form-{{ $tenant->id }}" 
                  action="{{ route('owner.tenants.destroy', $tenant) }}" 
                  method="POST" 
                  class="hidden">
                @csrf
                @method('DELETE')
            </form>

            <!-- Back to Index -->
            <a href="{{ route('owner.tenants.index') }}" 
               class="px-3 py-2 rounded-lg bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 font-semibold text-xs transition flex items-center gap-1.5">
                <i class="fas fa-arrow-left text-[10px]"></i>
                <span>Back</span>
            </a>

        </div>

    </div>

    <!-- 4 High-Density Key Metric Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        
        <!-- Metric 1: Assigned Plan -->
        <div class="p-3.5 rounded-xl bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5">
                <span class="text-[10px] font-bold text-purple-600 uppercase tracking-wider block">Assigned Plan</span>
                <span class="text-base font-extrabold text-slate-900 tracking-tight block">{{ $tenant->plan->name ?? 'Custom Plan' }}</span>
                <span class="text-[10.5px] text-slate-500 font-mono font-semibold">
                    @currency($tenant->plan->monthly_price ?? 0)<span class="text-[9.5px] font-normal text-slate-400">/mo</span>
                </span>
            </div>
            <div class="w-9 h-9 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center text-sm border border-purple-100 flex-shrink-0">
                <i class="fas fa-layer-group"></i>
            </div>
        </div>

        <!-- Metric 2: Capacity Limit -->
        <div class="p-3.5 rounded-xl bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5">
                <span class="text-[10px] font-bold text-blue-600 uppercase tracking-wider block">Capacity Limit</span>
                <span class="text-base font-extrabold text-slate-900 tracking-tight block font-mono">
                    {{ number_format($tenant->plan->customer_limit ?? 0) }}
                </span>
                <span class="text-[10px] text-slate-400 block">Maximum Active Subscribers</span>
            </div>
            <div class="w-9 h-9 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-sm border border-blue-100 flex-shrink-0">
                <i class="fas fa-users"></i>
            </div>
        </div>

        <!-- Metric 3: Subscription Expiry -->
        <div class="p-3.5 rounded-xl bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5">
                <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block">Subscription Period End</span>
                <span class="text-sm font-extrabold text-slate-900 tracking-tight block font-mono">
                    {{ $expiryDate ? $expiryDate->format('d M, Y') : 'Lifetime' }}
                </span>
                @if($expiryDate)
                    @if($isExpired)
                        <span class="text-[10px] text-rose-600 font-bold block">Expired {{ abs($daysLeft) }} days ago</span>
                    @elseif($daysLeft <= 7)
                        <span class="text-[10px] text-amber-600 font-bold block">{{ $daysLeft }} days left (Expiring soon)</span>
                    @else
                        <span class="text-[10px] text-emerald-600 font-bold block">{{ $daysLeft }} days remaining</span>
                    @endif
                @else
                    <span class="text-[10px] text-slate-400 block">No expiration date set</span>
                @endif
            </div>
            <div class="w-9 h-9 rounded-lg {{ $isExpired ? 'bg-rose-50 text-rose-600 border-rose-100' : 'bg-emerald-50 text-emerald-600 border-emerald-100' }} flex items-center justify-center text-sm border flex-shrink-0">
                <i class="fas {{ $isExpired ? 'fa-hourglass-end' : 'fa-clock' }}"></i>
            </div>
        </div>

        <!-- Metric 4: Wallet Balance -->
        <div class="p-3.5 rounded-xl bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="space-y-0.5">
                <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider block">Prepaid Wallet</span>
                <span class="text-base font-extrabold text-emerald-700 tracking-tight block font-mono">
                    @currency($wallet->balance ?? ($tenant->wallet_balance ?? 0))
                </span>
                <span class="text-[10px] text-slate-400 block">Available Platform Credit</span>
            </div>
            <div class="w-9 h-9 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm border border-emerald-100 flex-shrink-0">
                <i class="fas fa-wallet"></i>
            </div>
        </div>

    </div>

    <!-- Subscription Lifecycle Management Bar (If subscription exists) -->
    @if($activeSub)
        <div class="p-4 rounded-xl bg-purple-50/80 border border-purple-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs text-purple-950">
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <span class="font-extrabold text-sm text-purple-900">Active Subscription: {{ $activeSub->plan->name ?? 'Standard Plan' }}</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-white text-purple-700 border border-purple-300 uppercase">
                        {{ $activeSub->status }}
                    </span>
                    @if($activeSub->auto_renew)
                        <span class="px-2 py-0.5 rounded-full text-[9.5px] font-bold bg-emerald-100 text-emerald-800">Auto-Renew ON</span>
                    @endif
                </div>
                <p class="text-[11px] text-purple-800 font-mono">
                    Period: <strong>{{ $activeSub->current_period_start ? $activeSub->current_period_start->format('d M, Y') : '—' }}</strong> → 
                    <strong>{{ $activeSub->current_period_end ? $activeSub->current_period_end->format('d M, Y') : '—' }}</strong> • 
                    Next Billing: <strong>{{ $activeSub->next_billing_date ? $activeSub->next_billing_date->format('d M, Y') : '—' }}</strong> • 
                    Grace Period Ends: <strong>{{ $activeSub->grace_period_ends_at ? $activeSub->grace_period_ends_at->format('d M, Y') : 'N/A' }}</strong>
                </p>
            </div>
            <div class="flex items-center gap-2">
                <form action="{{ route('owner.billing.subscriptions.renew', $activeSub) }}" method="POST" onsubmit="return confirm('Renew subscription by +1 cycle now?');">
                    @csrf
                    <button type="submit" class="px-3.5 py-1.5 rounded-lg bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs shadow-xs transition flex items-center gap-1.5">
                        <i class="fas fa-sync-alt text-[10px]"></i>
                        <span>Renew (+1 Cycle)</span>
                    </button>
                </form>
            </div>
        </div>
    @endif

    <!-- Complete 360-Degree Overview Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        
        <!-- Left 2 Cols: Identity, Endpoints & Invoices -->
        <div class="lg:col-span-2 space-y-4">
            
            <!-- Card 1: Corporate Profile & Technical Endpoints -->
            <div class="bg-white rounded-xl border border-slate-200 p-4 sm:p-5 shadow-xs space-y-3.5">
                <h3 class="font-bold text-slate-900 text-xs pb-2 border-b border-slate-100 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-building text-blue-600"></i>
                        <span>Corporate Profile & Platform Endpoints</span>
                    </div>
                    <a href="{{ route('owner.tenants.edit', $tenant) }}" class="text-blue-600 hover:underline text-[11px] font-semibold">
                        Edit Info
                    </a>
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 text-xs">
                    <div>
                        <span class="text-slate-400 text-[10.5px] block font-medium">Organization / Brand Name</span>
                        <span class="text-slate-900 font-bold text-xs">{{ $tenant->name }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 text-[10.5px] block font-medium">Company Legal Registered Entity</span>
                        <span class="text-slate-800 font-semibold">{{ $tenant->company_name ?: $tenant->name }}</span>
                    </div>

                    <!-- Subdomain Endpoint with 1-Click Copy -->
                    <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">SaaS Subdomain URL</span>
                        <div class="flex items-center justify-between gap-1.5 mt-1">
                            <span class="font-mono font-bold text-blue-700 text-xs truncate">{{ $tenant->slug }}.somitysoft.com</span>
                            <div class="flex items-center gap-1 flex-shrink-0">
                                <button type="button" 
                                        @click="copyToClipboard('{{ $tenant->slug }}.somitysoft.com')" 
                                        title="Copy URL"
                                        class="p-1 rounded bg-white hover:bg-slate-200 text-slate-600 border border-slate-200 text-[10px]">
                                    <i class="fas fa-copy"></i>
                                </button>
                                <a href="http://{{ $tenant->slug }}.somitysoft.com" target="_blank" title="Visit Portal" class="p-1 rounded bg-white hover:bg-slate-200 text-blue-600 border border-slate-200 text-[10px]">
                                    <i class="fas fa-external-link-alt"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Custom Domain -->
                    <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Custom Domain CNAME</span>
                        <span class="font-mono text-slate-700 text-xs font-semibold mt-1 block">
                            {{ $tenant->domain ?: 'Not Configured' }}
                        </span>
                    </div>

                    <div>
                        <span class="text-slate-400 text-[10.5px] block font-medium">Official Contact Hotline</span>
                        <a href="tel:{{ $tenant->phone }}" class="text-slate-900 font-semibold hover:text-blue-600 transition flex items-center gap-1.5">
                            <i class="fas fa-phone text-slate-400 text-[10px]"></i>
                            <span>{{ $tenant->phone }}</span>
                        </a>
                    </div>
                    <div>
                        <span class="text-slate-400 text-[10.5px] block font-medium">Official Email Address</span>
                        <a href="mailto:{{ $tenant->email }}" class="text-slate-900 font-semibold hover:text-blue-600 transition flex items-center gap-1.5">
                            <i class="fas fa-envelope text-slate-400 text-[10px]"></i>
                            <span>{{ $tenant->email }}</span>
                        </a>
                    </div>

                    <div class="sm:col-span-2">
                        <span class="text-slate-400 text-[10.5px] block font-medium">Physical / Registered Office Address</span>
                        <span class="text-slate-700">{{ $tenant->address ?: 'No official office address specified.' }}</span>
                    </div>
                </div>
            </div>

            <!-- Card 2: SaaS Plan Specifications -->
            <div class="bg-white rounded-xl border border-slate-200 p-4 sm:p-5 shadow-xs space-y-3.5">
                <h3 class="font-bold text-slate-900 text-xs pb-2 border-b border-slate-100 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-layer-group text-purple-600"></i>
                        <span>SaaS Subscription Plan Specifications</span>
                    </div>
                    <span class="px-2 py-0.5 rounded bg-purple-50 text-purple-700 border border-purple-200 font-bold text-[10.5px]">
                        {{ $tenant->plan->name ?? 'Custom Plan' }}
                    </span>
                </h3>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                    <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200">
                        <span class="text-[10px] text-slate-400 uppercase font-bold block">Monthly Fee</span>
                        <span class="font-mono font-bold text-slate-900 text-xs mt-0.5 block">@currency($tenant->plan->monthly_price ?? 0)</span>
                    </div>
                    <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200">
                        <span class="text-[10px] text-slate-400 uppercase font-bold block">OTC Setup Fee</span>
                        <span class="font-mono font-bold text-slate-900 text-xs mt-0.5 block">@currency($tenant->plan->otc_charge ?? 0)</span>
                    </div>
                    <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200">
                        <span class="text-[10px] text-slate-400 uppercase font-bold block">Customer Limit</span>
                        <span class="font-mono font-bold text-blue-700 text-xs mt-0.5 block">{{ number_format($tenant->plan->customer_limit ?? 0) }} Users</span>
                    </div>
                    <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200">
                        <span class="text-[10px] text-slate-400 uppercase font-bold block">Mikrotik / OLT</span>
                        <span class="font-mono font-bold text-purple-700 text-xs mt-0.5 block">{{ $tenant->plan->olt_limit ?? 1 }} OLT Limit</span>
                    </div>
                </div>
            </div>

            <!-- Card 3: Recent Billing History Table -->
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-xs">
                <div class="px-4 py-3 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                    <h3 class="font-bold text-slate-900 text-xs flex items-center gap-1.5">
                        <i class="fas fa-receipt text-slate-600"></i>
                        <span>Recent Invoices & Payment Records</span>
                    </h3>
                    <span class="text-[11px] text-slate-500 font-mono">{{ count($tenant->invoices) }} Records</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-700 whitespace-nowrap">
                        <thead class="bg-slate-50/80 text-slate-500 uppercase tracking-wider text-[10px] border-b border-slate-200">
                            <tr>
                                <th class="px-4 py-2">Invoice #</th>
                                <th class="px-4 py-2">Amount</th>
                                <th class="px-4 py-2">Due</th>
                                <th class="px-4 py-2">Method</th>
                                <th class="px-4 py-2">Transaction ID</th>
                                <th class="px-4 py-2">Due Date</th>
                                <th class="px-4 py-2">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($tenant->invoices as $inv)
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="px-4 py-2 font-mono font-bold text-blue-700">{{ $inv->invoice_no }}</td>
                                    <td class="px-4 py-2 font-mono font-bold">@currency($inv->amount)</td>
                                    <td class="px-4 py-2 font-mono font-bold {{ $inv->calculated_due > 0 ? 'text-amber-700' : 'text-slate-400' }}">
                                        @currency($inv->calculated_due)
                                    </td>
                                    <td class="px-4 py-2">{{ strtoupper($inv->payment_method ?: 'Online') }}</td>
                                    <td class="px-4 py-2 font-mono text-slate-500 text-[11px]">{{ $inv->trx_id ?: '—' }}</td>
                                    <td class="px-4 py-2 text-slate-600">{{ $inv->due_date ? \Carbon\Carbon::parse($inv->due_date)->format('d M, Y') : '—' }}</td>
                                    <td class="px-4 py-2">
                                        @if($inv->status === 'paid')
                                            <span class="px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 font-bold text-[10px] border border-emerald-200">Paid</span>
                                        @else
                                            <span class="px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 font-bold text-[10px] border border-amber-200">Unpaid</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-6 text-center text-slate-400 text-xs">
                                        No subscription billing invoices generated yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Card 4: Tenant SMS Consumption & Cost Ledger -->
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-xs space-y-0">
                <div class="px-4 py-3 bg-slate-50 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-comment-dots text-blue-600"></i>
                        <h3 class="font-bold text-slate-900 text-xs">SMS Consumption & Cost Ledger</h3>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-0.5 rounded-md bg-blue-50 text-blue-700 font-bold text-[10.5px] border border-blue-200 font-mono">
                            Sent: {{ number_format($tenant->total_sms_count) }} SMS
                        </span>
                        <span class="px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 font-bold text-[10.5px] border border-emerald-200 font-mono">
                            Cost: @currency($tenant->total_sms_cost)
                        </span>
                    </div>
                </div>

                <div class="overflow-x-auto max-h-72">
                    <table class="w-full text-left text-xs text-slate-700 whitespace-nowrap">
                        <thead class="bg-slate-50/80 text-slate-500 uppercase tracking-wider text-[10px] border-b border-slate-200 sticky top-0">
                            <tr>
                                <th class="px-4 py-2">Phone</th>
                                <th class="px-4 py-2">Type</th>
                                <th class="px-4 py-2">Message Snippet</th>
                                <th class="px-4 py-2">Parts</th>
                                <th class="px-4 py-2">Total Cost</th>
                                <th class="px-4 py-2">Status</th>
                                <th class="px-4 py-2">Dispatched At</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-mono">
                            @forelse($tenant->smsLogs as $sms)
                                <tr class="hover:bg-slate-50 transition text-[11px]">
                                    <td class="px-4 py-2 font-bold text-slate-800">{{ $sms->recipient_phone }}</td>
                                    <td class="px-4 py-2">
                                        <span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 font-sans text-[10px] uppercase font-bold">
                                            {{ str_replace('_', ' ', $sms->sms_type) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 font-sans text-slate-600 max-w-xs truncate" title="{{ $sms->message_body }}">
                                        {{ Str::limit($sms->message_body, 40) }}
                                    </td>
                                    <td class="px-4 py-2 text-slate-700">{{ $sms->parts_count }} ({{ $sms->character_count }}c)</td>
                                    <td class="px-4 py-2 font-bold text-emerald-700">@currency($sms->total_cost)</td>
                                    <td class="px-4 py-2">
                                        @if($sms->status === 'delivered')
                                            <span class="px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 font-bold text-[9.5px]">Delivered</span>
                                        @elseif($sms->status === 'sent')
                                            <span class="px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 font-bold text-[9.5px]">Sent</span>
                                        @else
                                            <span class="px-2 py-0.5 rounded-full bg-rose-50 text-rose-700 font-bold text-[9.5px]">Failed</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-slate-400 text-[10.5px]">
                                        {{ $sms->created_at ? $sms->created_at->format('d M, h:i A') : '—' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-6 text-center text-slate-400 font-sans text-xs">
                                        No SMS dispatches logged for this tenant yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Card 5: Tenant Activity & Audit Trail Timeline -->
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-xs">
                <div class="px-4 py-3 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-history text-purple-600"></i>
                        <h3 class="font-bold text-slate-900 text-xs">Tenant Activity & Audit Trail</h3>
                    </div>
                    <span class="text-[11px] text-slate-500 font-mono">{{ count($tenant->activityLogs) }} Audit Logs</span>
                </div>

                <div class="p-4 divide-y divide-slate-100 max-h-96 overflow-y-auto space-y-3">
                    @forelse($tenant->activityLogs as $log)
                        <div class="pt-3 first:pt-0 flex items-start gap-3 text-xs">
                            <div class="w-7 h-7 rounded-full bg-purple-50 text-purple-600 flex items-center justify-center flex-shrink-0 text-xs mt-0.5 border border-purple-100">
                                @if(str_contains($log->event_type, 'created'))
                                    <i class="fas fa-plus"></i>
                                @elseif(str_contains($log->event_type, 'wallet'))
                                    <i class="fas fa-wallet"></i>
                                @elseif(str_contains($log->event_type, 'impersonate'))
                                    <i class="fas fa-user-secret"></i>
                                @elseif(str_contains($log->event_type, 'suspend'))
                                    <i class="fas fa-lock text-rose-600"></i>
                                @elseif(str_contains($log->event_type, 'extend') || str_contains($log->event_type, 'renew'))
                                    <i class="fas fa-calendar-check text-emerald-600"></i>
                                @else
                                    <i class="fas fa-bolt"></i>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="font-bold text-slate-900 text-xs truncate">
                                        {{ $log->description }}
                                    </span>
                                    <span class="text-[10px] text-slate-400 font-mono flex-shrink-0">
                                        {{ $log->created_at ? $log->created_at->diffForHumans() : '—' }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-3 text-[10.5px] text-slate-400 mt-1">
                                    <span>Actor: <strong class="text-slate-600">{{ $log->actor_name ?: ucfirst($log->actor_type) }}</strong></span>
                                    <span>•</span>
                                    <span>IP: <code class="font-mono text-slate-600">{{ $log->ip_address ?: '127.0.0.1' }}</code></span>
                                    <span>•</span>
                                    <span>Time: {{ $log->created_at ? $log->created_at->format('d M Y, h:i A') : '' }}</span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-6 text-slate-400 text-xs">
                            No activity audit logs recorded yet.
                        </div>
                    @endforelse
                </div>
            </div>

        </div>

        <!-- Right 1 Col: Primary Admin Account & Subscription Control -->
        <div class="space-y-4">
            
            <!-- Primary Administrator Card -->
            <div class="bg-white rounded-xl border border-slate-200 p-4 sm:p-5 shadow-xs space-y-3.5">
                <h3 class="font-bold text-slate-900 text-xs pb-2 border-b border-slate-100 flex items-center gap-2">
                    <i class="fas fa-user-shield text-emerald-600"></i>
                    <span>Primary ISP Administrator</span>
                </h3>

                <div class="space-y-3 text-xs">
                    <div>
                        <span class="text-slate-400 text-[10.5px] block font-medium">Administrator Full Name</span>
                        <span class="text-slate-900 font-bold text-xs">{{ $admin->name ?? 'N/A' }}</span>
                    </div>

                    <div>
                        <span class="text-slate-400 text-[10.5px] block font-medium">Login Email Address</span>
                        <span class="text-slate-900 font-mono font-semibold">{{ $admin->email ?? 'N/A' }}</span>
                    </div>

                    <div>
                        <span class="text-slate-400 text-[10.5px] block font-medium">Access Role & Permission</span>
                        <span class="px-2 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-200 text-[10.5px] font-bold">
                            {{ strtoupper($admin->role ?? 'ISP_ADMIN') }}
                        </span>
                    </div>

                    <div class="pt-2">
                        <a href="{{ route('owner.tenants.impersonate', $tenant) }}" 
                           class="w-full py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-xs transition flex items-center justify-center gap-1.5">
                            <i class="fas fa-sign-in-alt text-[10px]"></i>
                            <span>Launch ISP Admin Console</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Quick Extend Validity Panel -->
            <div class="bg-white rounded-xl border border-slate-200 p-4 sm:p-5 shadow-xs space-y-3.5">
                <h3 class="font-bold text-slate-900 text-xs pb-2 border-b border-slate-100 flex items-center gap-2">
                    <i class="fas fa-calendar-plus text-purple-600"></i>
                    <span>Quick Subscription Renewal</span>
                </h3>

                <form action="{{ route('owner.tenants.extend-subscription', $tenant) }}" method="POST" class="space-y-3">
                    @csrf

                    <div>
                        <label class="block text-[10.5px] font-bold text-slate-600 uppercase tracking-wider mb-1.5">Choose Duration</label>
                        <div class="grid grid-cols-2 gap-1.5">
                            <label class="p-2 rounded-lg border text-center cursor-pointer transition text-xs"
                                   :class="extendType === '1_month' ? 'border-purple-600 bg-purple-50/50 font-bold text-purple-700' : 'border-slate-200 hover:bg-slate-50 text-slate-700'">
                                <input type="radio" name="extension_type" value="1_month" x-model="extendType" class="hidden">
                                <span>+ 1 Month</span>
                            </label>

                            <label class="p-2 rounded-lg border text-center cursor-pointer transition text-xs"
                                   :class="extendType === '3_months' ? 'border-purple-600 bg-purple-50/50 font-bold text-purple-700' : 'border-slate-200 hover:bg-slate-50 text-slate-700'">
                                <input type="radio" name="extension_type" value="3_months" x-model="extendType" class="hidden">
                                <span>+ 3 Months</span>
                            </label>

                            <label class="p-2 rounded-lg border text-center cursor-pointer transition text-xs"
                                   :class="extendType === '6_months' ? 'border-purple-600 bg-purple-50/50 font-bold text-purple-700' : 'border-slate-200 hover:bg-slate-50 text-slate-700'">
                                <input type="radio" name="extension_type" value="6_months" x-model="extendType" class="hidden">
                                <span>+ 6 Months</span>
                            </label>

                            <label class="p-2 rounded-lg border text-center cursor-pointer transition text-xs"
                                   :class="extendType === '1_year' ? 'border-purple-600 bg-purple-50/50 font-bold text-purple-700' : 'border-slate-200 hover:bg-slate-50 text-slate-700'">
                                <input type="radio" name="extension_type" value="1_year" x-model="extendType" class="hidden">
                                <span>+ 1 Year</span>
                            </label>
                        </div>
                    </div>

                    <div class="p-2 rounded-lg bg-slate-50 border border-slate-200 flex items-center gap-2">
                        <input type="checkbox" name="reactivate_if_suspended" value="1" checked id="auto_reactivate_check" class="rounded text-purple-600 focus:ring-purple-500">
                        <label for="auto_reactivate_check" class="text-slate-700 font-medium text-[10.5px] cursor-pointer">
                            Auto-activate if suspended/expired
                        </label>
                    </div>

                    <button type="submit" class="w-full py-2 rounded-lg bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs shadow-xs transition flex items-center justify-center gap-1.5">
                        <i class="fas fa-check"></i>
                        <span>Apply Extension</span>
                    </button>
                </form>
            </div>

        </div>

    </div>

    <!-- Modal for Top Extend Button -->
    <div x-show="extendModalOpen" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4"
         x-cloak>
        
        <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-md w-full overflow-hidden text-xs"
             @click.outside="extendModalOpen = false">
            
            <form action="{{ route('owner.tenants.extend-subscription', $tenant) }}" method="POST">
                @csrf

                <div class="p-4 bg-purple-50/70 border-b border-purple-100 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-purple-600 text-white flex items-center justify-center font-bold text-xs shadow-xs">
                            <i class="fas fa-calendar-plus"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-slate-900 text-xs">Extend Subscription Validity</h3>
                            <p class="text-[11px] text-purple-700 font-semibold">{{ $tenant->name }}</p>
                        </div>
                    </div>
                    <button type="button" @click="extendModalOpen = false" class="p-1 rounded-lg text-slate-400 hover:text-slate-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="p-5 space-y-4">
                    <div>
                        <span class="text-[10.5px] font-semibold text-slate-600 block mb-1">Current Expiration:</span>
                        <div class="p-2 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 font-mono font-semibold">
                            {{ $expiryDate ? $expiryDate->format('d M, Y') : 'Lifetime / No Expiry' }}
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10.5px] font-bold text-slate-700 uppercase tracking-wider mb-2">Select Extension Period</label>
                        <div class="grid grid-cols-2 gap-2">
                            <label class="p-2.5 rounded-lg border text-center cursor-pointer transition"
                                   :class="extendType === '1_month' ? 'border-purple-600 bg-purple-50/50 font-bold text-purple-700' : 'border-slate-200 hover:bg-slate-50 text-slate-700'">
                                <input type="radio" name="extension_type" value="1_month" x-model="extendType" class="hidden">
                                <span>+ 1 Month</span>
                            </label>

                            <label class="p-2.5 rounded-lg border text-center cursor-pointer transition"
                                   :class="extendType === '3_months' ? 'border-purple-600 bg-purple-50/50 font-bold text-purple-700' : 'border-slate-200 hover:bg-slate-50 text-slate-700'">
                                <input type="radio" name="extension_type" value="3_months" x-model="extendType" class="hidden">
                                <span>+ 3 Months</span>
                            </label>

                            <label class="p-2.5 rounded-lg border text-center cursor-pointer transition"
                                   :class="extendType === '6_months' ? 'border-purple-600 bg-purple-50/50 font-bold text-purple-700' : 'border-slate-200 hover:bg-slate-50 text-slate-700'">
                                <input type="radio" name="extension_type" value="6_months" x-model="extendType" class="hidden">
                                <span>+ 6 Months</span>
                            </label>

                            <label class="p-2.5 rounded-lg border text-center cursor-pointer transition"
                                   :class="extendType === '1_year' ? 'border-purple-600 bg-purple-50/50 font-bold text-purple-700' : 'border-slate-200 hover:bg-slate-50 text-slate-700'">
                                <input type="radio" name="extension_type" value="1_year" x-model="extendType" class="hidden">
                                <span>+ 1 Year</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="p-2.5 rounded-lg border flex items-center gap-2 cursor-pointer transition"
                                :class="extendType === 'custom_date' ? 'border-purple-600 bg-purple-50/50 font-bold text-purple-700' : 'border-slate-200 hover:bg-slate-50 text-slate-700'">
                            <input type="radio" name="extension_type" value="custom_date" x-model="extendType" class="text-purple-600">
                            <span>Specify Exact Expiry Date</span>
                        </label>
                        <div class="mt-2" x-show="extendType === 'custom_date'" x-cloak>
                            <input type="date" name="custom_date" x-model="customDate" class="w-full px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs focus:ring-1 focus:ring-purple-500">
                        </div>
                    </div>

                    <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200 flex items-center gap-2">
                        <input type="checkbox" name="reactivate_if_suspended" value="1" checked id="reactivate_check_modal" class="rounded text-purple-600 focus:ring-purple-500">
                        <label for="reactivate_check_modal" class="text-slate-700 font-medium text-[11px] cursor-pointer">
                            Automatically set status to Active if previously expired/suspended
                        </label>
                    </div>
                </div>

                <div class="p-4 bg-slate-50 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" @click="extendModalOpen = false" class="px-3.5 py-1.5 rounded-lg bg-white border border-slate-200 text-slate-600 hover:bg-slate-100 font-semibold text-xs transition">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-1.5 rounded-lg bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs shadow-xs transition flex items-center gap-1.5">
                        <i class="fas fa-check"></i>
                        <span>Apply Extension</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal for Wallet Adjustment -->
    <div x-show="walletModalOpen" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4"
         x-cloak>
        
        <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-md w-full overflow-hidden text-xs"
             @click.outside="walletModalOpen = false">
            
            <form action="{{ route('owner.billing.wallets.adjust', $tenant) }}" method="POST">
                @csrf

                <div class="p-4 bg-purple-50/70 border-b border-purple-100 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-purple-600 text-white flex items-center justify-center font-bold text-xs shadow-xs">
                            <i class="fas fa-wallet"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-slate-900 text-xs">Adjust Tenant Wallet Credit</h3>
                            <p class="text-[11px] text-purple-700 font-semibold">{{ $tenant->name }}</p>
                        </div>
                    </div>
                    <button type="button" @click="walletModalOpen = false" class="p-1 rounded-lg text-slate-400 hover:text-slate-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="p-5 space-y-3.5">
                    <div>
                        <span class="text-[10.5px] font-semibold text-slate-600 block mb-1">Current Balance:</span>
                        <div class="p-2 rounded-lg bg-slate-50 border border-slate-200 text-emerald-700 font-mono font-extrabold text-sm">
                            @currency($wallet->balance ?? ($tenant->wallet_balance ?? 0))
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">Adjustment Type *</label>
                        <select name="type" required class="w-full px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs font-semibold focus:ring-1 focus:ring-purple-500">
                            <option value="credit">Credit (+) Deposit Advance</option>
                            <option value="debit">Debit (-) Withdraw / Charge</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">Amount ({{ $currencySymbol ?? '৳' }}) *</label>
                        <input type="number" step="0.01" name="amount" required placeholder="e.g. 500" class="w-full px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs font-mono font-bold focus:ring-1 focus:ring-purple-500">
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">Reason / Note *</label>
                        <input type="text" name="description" required placeholder="e.g. Cash payment received" class="w-full px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs focus:ring-1 focus:ring-purple-500">
                    </div>
                </div>

                <div class="p-4 bg-slate-50 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" @click="walletModalOpen = false" class="px-3.5 py-1.5 rounded-lg bg-white border border-slate-200 text-slate-600 hover:bg-slate-100 font-semibold text-xs transition">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-1.5 rounded-lg bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs shadow-xs transition flex items-center gap-1.5">
                        <i class="fas fa-check"></i>
                        <span>Apply Adjustment</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

@endsection

<!-- SweetAlert2 Confirmation Script -->
@push('scripts')
<script>
    function confirmDeleteTenant(tenantId, tenantName) {
        Swal.fire({
            title: 'Delete ISP Organization?',
            html: `
                <div class="text-left text-xs space-y-2 mt-2">
                    <p class="text-slate-700">Are you sure you want to completely remove <strong>"${tenantName}"</strong>?</p>
                    <div class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-[11.5px] leading-relaxed">
                        <i class="fas fa-exclamation-triangle mr-1 text-rose-600"></i>
                        <strong>High Impact Action:</strong> This will permanently erase the ISP organization, associated admin accounts, customer databases, billing records, and all related settings.
                    </div>
                </div>
            `,
            icon: 'warning',
            iconColor: '#e11d48',
            showCancelButton: true,
            confirmButtonColor: '#e11d48',
            cancelButtonColor: '#64748b',
            confirmButtonText: '<i class="fas fa-trash-alt mr-1"></i> Yes, Delete Everything',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
            customClass: {
                popup: 'rounded-2xl shadow-2xl border border-slate-200',
                title: 'text-sm font-bold text-slate-900',
                confirmButton: 'rounded-lg text-xs font-bold px-4 py-2 shadow-xs',
                cancelButton: 'rounded-lg text-xs font-semibold px-4 py-2 bg-slate-100 text-slate-700 hover:bg-slate-200 border-0'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('delete-tenant-form-' + tenantId).submit();
            }
        });
    }
</script>
@endpush

