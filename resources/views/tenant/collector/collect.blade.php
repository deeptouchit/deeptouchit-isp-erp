@extends('tenant.layouts.app')

@section('title', 'Collection Terminal • ' . ($customer->customer_id ?: $customer->username) . ' - ' . ($tenant->company_name ?? $tenant->name ?? 'ISP Management'))

@push('styles')
<style>
    @media print {
        .no-print { display: none !important; }
    }
</style>
@endpush

@section('content')
<div class="space-y-3" x-data="billingTerminal()">
    
    {{-- 1. TOP HEADER BAR --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 bg-white px-4 py-2.5 rounded-xl border border-slate-200/90 shadow-2xs">
        <div class="flex items-center gap-2.5">
            <a href="{{ route('tenant.collector.due-customers') }}" 
               class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center text-xs transition shadow-2xs cursor-pointer"
               title="Back to Due List">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-sm shadow-2xs flex-shrink-0">
                <i class="fas fa-cash-register"></i>
            </div>
            <div>
                <h1 class="text-sm font-bold text-slate-800 tracking-tight flex items-center gap-2">
                    <span>POS Collection Terminal</span>
                    <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-slate-100 text-slate-700 border border-slate-200">
                        {{ $customer->customer_id ?: $customer->username }}
                    </span>
                </h1>
            </div>
        </div>
        
        <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
            @if(!empty($customer->phone))
                <a href="tel:{{ $customer->phone }}" 
                   class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition inline-flex items-center gap-1.5 shadow-2xs cursor-pointer"
                   title="Call Customer">
                    <i class="fas fa-phone text-emerald-600 text-[10px]"></i>
                    <span>{{ $customer->phone }}</span>
                </a>

                @php
                    $cleanPhone = preg_replace('/[^0-9]/', '', $customer->phone);
                    $intlPhone = str_starts_with($cleanPhone, '0') ? '88' . $cleanPhone : $cleanPhone;
                    $waMsg = rawurlencode("Dear Customer ({$customer->username}), your internet bill due is Tk " . number_format((float)$customer->due_amount, 2) . ". Please pay your bill to keep your internet active. Thank you - " . ($tenant->company_name ?? 'ISP'));
                @endphp
                <a href="https://wa.me/{{ $intlPhone }}?text={{ $waMsg }}" 
                   target="_blank"
                   class="px-2.5 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 rounded-lg text-xs font-semibold border border-emerald-200 transition inline-flex items-center gap-1.5 shadow-2xs cursor-pointer"
                   title="Send WhatsApp Reminder">
                    <i class="fab fa-whatsapp text-emerald-600 text-xs"></i>
                    <span>WhatsApp</span>
                </a>
            @endif

            <a href="{{ route('tenant.collector.due-customers') }}" 
               class="px-3 py-1.5 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold transition inline-flex items-center gap-1.5 shadow-xs cursor-pointer">
                <i class="fas fa-list text-[10px]"></i>
                <span>Due Customers List</span>
            </a>
        </div>
    </div>

    {{-- SUCCESS ALERT BANNER --}}
    @if(session('success'))
        <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-xs text-emerald-800 flex items-center justify-between shadow-2xs">
            <div class="flex items-center gap-2">
                <i class="fas fa-circle-check text-emerald-600 text-sm"></i>
                <span class="font-medium">{{ session('success') }}</span>
            </div>
            @if(session('payment_success_id'))
                <a href="{{ route('tenant.collector.receipt', session('payment_success_id')) }}" 
                   target="_blank"
                   class="px-3 py-1 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs rounded-lg shadow-xs transition flex items-center gap-1.5">
                    <i class="fas fa-print text-[10px]"></i>
                    <span>Print 58mm/80mm Slip</span>
                </a>
            @endif
        </div>
    @endif

    @if(session('error'))
        <div class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-800 flex items-center gap-2 shadow-2xs">
            <i class="fas fa-circle-exclamation text-rose-600 text-sm"></i>
            <span class="font-medium">{{ session('error') }}</span>
        </div>
    @endif

    {{-- 2. MAIN TWO-COLUMN TERMINAL LAYOUT --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-3 items-start">
        
        {{-- LEFT PANEL (4 Columns on desktop): Subscriber 360° Profile & Network Intel --}}
        <div class="lg:col-span-5 space-y-3">
            
            {{-- Card 1: Identity & Subscription Card --}}
            <div class="bg-white p-3.5 rounded-xl border border-slate-200/90 shadow-2xs space-y-3">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-md bg-cyan-50 text-cyan-700 border border-cyan-200 flex items-center justify-center text-xs">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <h2 class="text-xs font-bold text-slate-800 uppercase tracking-wide">Subscriber Profile</h2>
                    </div>
                    @if($customer->status === 'expired')
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                            Cut-Off / Expired
                        </span>
                    @else
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            Active Subscriber
                        </span>
                    @endif
                </div>

                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div>
                        <span class="text-[10px] text-slate-400 font-medium uppercase block">Subscriber Name</span>
                        <span class="font-bold text-slate-800 truncate block">{{ $customer->name ?: $customer->username }}</span>
                    </div>
                    <div>
                        <span class="text-[10px] text-slate-400 font-medium uppercase block">PPPoE Username</span>
                        <span class="font-mono font-semibold text-slate-900 truncate block">{{ $customer->username }}</span>
                    </div>
                    <div>
                        <span class="text-[10px] text-slate-400 font-medium uppercase block">Guardian / Father Name</span>
                        <span class="font-medium text-slate-700 truncate block">{{ $customer->father_name ?: '—' }}</span>
                    </div>
                    <div>
                        <span class="text-[10px] text-slate-400 font-medium uppercase block">Mobile Phone</span>
                        <span class="font-mono font-semibold text-slate-800 truncate block">{{ $customer->phone ?: '—' }}</span>
                    </div>
                    <div>
                        <span class="text-[10px] text-slate-400 font-medium uppercase block">Coverage Area</span>
                        <span class="font-medium text-slate-700 truncate block">
                            {{ (is_object($customer->zone) ? ($customer->zone->name ?? '') : $customer->zone) ?: ($customer->coverageZone?->name ?: ($customer->address ?: '—')) }}
                        </span>
                    </div>
                    <div>
                        <span class="text-[10px] text-slate-400 font-medium uppercase block">Current Expiry</span>
                        <span class="font-mono font-bold {{ $customer->status === 'expired' ? 'text-rose-600' : 'text-slate-800' }} truncate block">
                            {{ $customer->expiry_date ? \Carbon\Carbon::parse($customer->expiry_date)->format('d M Y') : 'Not Set' }}
                        </span>
                    </div>
                </div>

                @if(!empty($customer->address))
                    <div class="pt-2 border-t border-slate-100 text-xs">
                        <span class="text-[10px] text-slate-400 font-medium uppercase block">Customer Address</span>
                        <span class="text-slate-600 block">{{ $customer->address }}</span>
                    </div>
                @endif
            </div>

            {{-- Card 2: Package & Network Telemetry --}}
            <div class="bg-white p-3.5 rounded-xl border border-slate-200/90 shadow-2xs space-y-3">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-md bg-indigo-50 text-indigo-700 border border-indigo-200 flex items-center justify-center text-xs">
                            <i class="fas fa-network-wired"></i>
                        </div>
                        <h2 class="text-xs font-bold text-slate-800 uppercase tracking-wide">Package & Router</h2>
                    </div>
                    <span class="font-mono font-bold text-xs text-indigo-700">
                        @currency($customer->monthly_bill ?? ($customer->package?->price ?? 0)) / mo
                    </span>
                </div>

                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div>
                        <span class="text-[10px] text-slate-400 font-medium uppercase block">Assigned Package</span>
                        <span class="font-bold text-slate-800 truncate block">{{ $customer->package_display_name }}</span>
                    </div>
                    <div>
                        <span class="text-[10px] text-slate-400 font-medium uppercase block">Assigned MikroTik</span>
                        <span class="font-medium text-slate-700 truncate block">{{ $customer->router?->name ?? 'Default Router' }}</span>
                    </div>
                    <div>
                        <span class="text-[10px] text-slate-400 font-medium uppercase block">IP Address</span>
                        <span class="font-mono text-slate-700 truncate block">{{ $customer->ip_address ?: 'Dynamic / Auto' }}</span>
                    </div>
                    <div>
                        <span class="text-[10px] text-slate-400 font-medium uppercase block">Connection Type</span>
                        <span class="font-mono uppercase font-semibold text-slate-700 truncate block">{{ $customer->connection_type ?: 'PPPoE' }}</span>
                    </div>
                </div>
            </div>

            {{-- Card 3: Recent Payment Receipts (Last 5) --}}
            <div class="bg-white p-3.5 rounded-xl border border-slate-200/90 shadow-2xs space-y-2.5">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-md bg-slate-100 text-slate-700 border border-slate-200 flex items-center justify-center text-xs">
                            <i class="fas fa-receipt"></i>
                        </div>
                        <h2 class="text-xs font-bold text-slate-800 uppercase tracking-wide">Recent Receipts</h2>
                    </div>
                    <span class="text-[10px] text-slate-400 font-medium">Last 5 Payments</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="saas-table w-full text-xs">
                        <thead>
                            <tr>
                                <th class="text-left py-1">Date</th>
                                <th class="text-right py-1">Amount</th>
                                <th class="text-center py-1">Method</th>
                                <th class="text-center py-1 no-sort">Slip</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentPayments as $p)
                                <tr>
                                    <td class="font-mono text-slate-600 text-[11px] whitespace-nowrap">
                                        {{ \Carbon\Carbon::parse($p->paid_at ?: $p->created_at)->format('d M Y') }}
                                    </td>
                                    <td class="text-right font-mono font-bold text-emerald-600 whitespace-nowrap">
                                        @currency($p->amount)
                                    </td>
                                    <td class="text-center whitespace-nowrap">
                                        <span class="px-1.5 py-0.2 rounded text-[9px] font-mono font-bold bg-slate-100 text-slate-700 uppercase">
                                            {{ $p->payment_method }}
                                        </span>
                                    </td>
                                    <td class="text-center whitespace-nowrap">
                                        <a href="{{ route('tenant.collector.receipt', $p->id) }}" 
                                           target="_blank"
                                           class="w-6 h-6 rounded bg-slate-50 hover:bg-slate-100 text-slate-600 hover:text-cyan-600 border border-slate-200 inline-flex items-center justify-center text-[10px] transition"
                                           title="Print POS Thermal Slip">
                                            <i class="fas fa-print"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-3 text-slate-400 text-xs">
                                        No recent payments on record.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        {{-- RIGHT PANEL (7 Columns on desktop): POS Payment & Expiry Extension Engine --}}
        <div class="lg:col-span-7 space-y-3">
            
            <form action="{{ route('tenant.collector.collect-bill') }}" method="POST" class="space-y-3">
                @csrf
                <input type="hidden" name="customer_id" value="{{ $customer->id }}">

                {{-- Outstanding Due Highlight Hero Strip --}}
                <div class="bg-gradient-to-r from-rose-50 via-rose-50/70 to-amber-50 p-3.5 rounded-xl border border-rose-200/90 shadow-2xs flex items-center justify-between">
                    <div>
                        <span class="text-[10px] uppercase font-bold text-rose-700 tracking-wider block">Total Outstanding Balance</span>
                        <div class="text-xl font-extrabold font-mono text-rose-700 leading-tight">
                            @currency($customer->due_amount ?? 0)
                        </div>
                        <span class="text-[11px] text-slate-500 font-normal">
                            Package Rate: <strong class="font-mono text-slate-700">@currency($customer->monthly_bill ?? ($customer->package?->price ?? 0))</strong> / month
                        </span>
                    </div>

                    <div class="text-right">
                        <span class="text-[10px] uppercase font-bold text-slate-500 tracking-wider block">Live Renewal Preview</span>
                        <div class="text-sm font-bold font-mono text-emerald-700" x-text="calcNewExpiry()">
                            {{ $customer->expiry_date ? \Carbon\Carbon::parse($customer->expiry_date)->format('d M Y') : 'Auto from Today' }}
                        </div>
                        <span class="text-[10px] text-slate-400" x-text="'Extension: +' + months + ' month(s)'"></span>
                    </div>
                </div>

                {{-- Checkout Card --}}
                <div class="bg-white p-4 rounded-xl border border-slate-200/90 shadow-2xs space-y-4">
                    
                    {{-- 1. Preset Selector Buttons --}}
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Quick Amount Presets
                        </label>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                            <button type="button" 
                                    @click="selectPreset('full_due')" 
                                    :class="presetType === 'full_due' ? 'bg-rose-600 text-white border-rose-600 shadow-xs ring-2 ring-rose-200' : 'bg-slate-50 hover:bg-slate-100 text-slate-800 border-slate-200'"
                                    class="py-2 px-2.5 rounded-lg border text-xs font-semibold transition text-center cursor-pointer flex flex-col items-center">
                                <span class="text-[10px] uppercase font-bold opacity-80">Full Due</span>
                                <span class="font-mono font-bold">@currency($customer->due_amount ?? 0)</span>
                            </button>

                            <button type="button" 
                                    @click="selectPreset('1_month')" 
                                    :class="presetType === '1_month' ? 'bg-cyan-600 text-white border-cyan-600 shadow-xs ring-2 ring-cyan-200' : 'bg-slate-50 hover:bg-slate-100 text-slate-800 border-slate-200'"
                                    class="py-2 px-2.5 rounded-lg border text-xs font-semibold transition text-center cursor-pointer flex flex-col items-center">
                                <span class="text-[10px] uppercase font-bold opacity-80">1 Month</span>
                                <span class="font-mono font-bold">@currency($customer->monthly_bill ?? ($customer->package?->price ?? 0))</span>
                            </button>

                            <button type="button" 
                                    @click="selectPreset('2_months')" 
                                    :class="presetType === '2_months' ? 'bg-cyan-600 text-white border-cyan-600 shadow-xs ring-2 ring-cyan-200' : 'bg-slate-50 hover:bg-slate-100 text-slate-800 border-slate-200'"
                                    class="py-2 px-2.5 rounded-lg border text-xs font-semibold transition text-center cursor-pointer flex flex-col items-center">
                                <span class="text-[10px] uppercase font-bold opacity-80">2 Months</span>
                                <span class="font-mono font-bold">@currency(($customer->monthly_bill ?? ($customer->package?->price ?? 0)) * 2)</span>
                            </button>

                            <button type="button" 
                                    @click="selectPreset('3_months')" 
                                    :class="presetType === '3_months' ? 'bg-cyan-600 text-white border-cyan-600 shadow-xs ring-2 ring-cyan-200' : 'bg-slate-50 hover:bg-slate-100 text-slate-800 border-slate-200'"
                                    class="py-2 px-2.5 rounded-lg border text-xs font-semibold transition text-center cursor-pointer flex flex-col items-center">
                                <span class="text-[10px] uppercase font-bold opacity-80">3 Months</span>
                                <span class="font-mono font-bold">@currency(($customer->monthly_bill ?? ($customer->package?->price ?? 0)) * 3)</span>
                            </button>
                        </div>
                    </div>

                    {{-- 2. Input Fields Grid --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        
                        {{-- Collection Amount --}}
                        <div class="sm:col-span-2">
                            <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">
                                Collection Amount (Tk) <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 font-bold font-mono text-slate-400 text-sm">Tk</span>
                                <input type="number" 
                                       name="amount" 
                                       x-model="amount" 
                                       @input="presetType = 'custom'"
                                       step="0.01" 
                                       min="0.01" 
                                       required 
                                       class="w-full h-[40px] pl-9 pr-3 bg-slate-50 border border-slate-200 rounded-lg text-base font-bold font-mono text-slate-900 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition shadow-2xs">
                            </div>
                        </div>

                        {{-- Validity Months Added --}}
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">
                                Extend Validity
                            </label>
                            <select name="months" 
                                    x-model="months" 
                                    class="w-full h-[40px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs font-bold text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition shadow-2xs">
                                <option value="0">0 Months (Due Only)</option>
                                <option value="1">1 Month (+30 Days)</option>
                                <option value="2">2 Months (+60 Days)</option>
                                <option value="3">3 Months (+90 Days)</option>
                                <option value="6">6 Months (+180 Days)</option>
                                <option value="12">12 Months (1 Year)</option>
                            </select>
                        </div>

                        {{-- Payment Method --}}
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">
                                Payment Method <span class="text-rose-500">*</span>
                            </label>
                            <select name="payment_method" 
                                    x-model="paymentMethod" 
                                    required 
                                    class="w-full h-[38px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs font-semibold text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition shadow-2xs">
                                <option value="cash">Cash in Hand</option>
                                <option value="bkash">bKash</option>
                                <option value="nagad">Nagad</option>
                                <option value="rocket">Rocket</option>
                                <option value="bank">Bank Deposit</option>
                                <option value="pos">POS Terminal</option>
                            </select>
                        </div>

                        {{-- Discount / Waiver --}}
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">
                                Discount / Waiver (Tk)
                            </label>
                            <input type="number" 
                                   name="discount" 
                                   x-model="discount" 
                                   step="0.01" 
                                   min="0" 
                                   placeholder="0.00" 
                                   class="w-full h-[38px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition shadow-2xs">
                        </div>

                        {{-- TrxID (for digital payments) --}}
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">
                                TrxID / Ref No
                            </label>
                            <input type="text" 
                                   name="trx_id" 
                                   x-model="trxId" 
                                   placeholder="e.g. 9J83KL41N" 
                                   class="w-full h-[38px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition shadow-2xs">
                        </div>
                    </div>

                    {{-- Collection Notes --}}
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">
                            Collection Note (Optional)
                        </label>
                        <input type="text" 
                               name="notes" 
                               placeholder="e.g. Doorstep payment collected by {{ $authUser->name }}" 
                               class="w-full h-[36px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-hidden transition shadow-2xs">
                    </div>

                    {{-- 3. Enterprise Network Automation Switches --}}
                    <div class="p-3 bg-slate-50/80 rounded-xl border border-slate-200/90 space-y-2.5">
                        <span class="text-[10px] uppercase font-bold text-slate-500 tracking-wider block">
                            Network & Billing Automations
                        </span>

                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" 
                                       name="reactivate_line" 
                                       value="1" 
                                       checked 
                                       class="w-4 h-4 rounded text-cyan-600 focus:ring-cyan-500 border-slate-300">
                                <div>
                                    <span class="text-xs font-semibold text-slate-800 block">Auto-Reactivate MikroTik Line</span>
                                    <span class="text-[10.5px] text-slate-500 block">Enables PPPoE secret & disconnects stale session</span>
                                </div>
                            </label>

                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" 
                                       name="send_sms" 
                                       value="1" 
                                       checked 
                                       class="w-4 h-4 rounded text-cyan-600 focus:ring-cyan-500 border-slate-300">
                                <div>
                                    <span class="text-xs font-semibold text-slate-800 block">Send Instant SMS Receipt</span>
                                    <span class="text-[10.5px] text-slate-500 block">Dispatches SMS confirmation with new expiry date</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    {{-- 4. Submit Area --}}
                    <div class="pt-2 flex items-center justify-between">
                        <div>
                            <span class="text-[10px] text-slate-400 font-medium uppercase block">Total Payable Relief</span>
                            <span class="font-mono font-bold text-sm text-slate-800" x-text="'Tk ' + (Number(amount || 0) + Number(discount || 0)).toFixed(2)"></span>
                        </div>

                        <div class="flex items-center gap-2">
                            <a href="{{ route('tenant.collector.due-customers') }}" 
                               class="h-[38px] px-3.5 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-medium text-xs inline-flex items-center justify-center transition cursor-pointer">
                                Cancel
                            </a>

                            <button type="submit" 
                                    class="h-[38px] px-6 rounded-lg bg-cyan-600 hover:bg-cyan-700 text-white font-bold text-xs shadow-xs transition inline-flex items-center justify-center gap-2 cursor-pointer">
                                <i class="fas fa-check-circle text-sm"></i>
                                <span>Confirm & Collect Bill</span>
                            </button>
                        </div>
                    </div>

                </div>
            </form>

        </div>

    </div>

</div>
@endsection

@push('scripts')
<script>
function billingTerminal() {
    return {
        amount: {{ (float)($customer->due_amount > 0 ? $customer->due_amount : ($customer->monthly_bill ?? ($customer->package?->price ?? 0))) }},
        discount: 0,
        months: 1,
        paymentMethod: 'cash',
        trxId: '',
        presetType: '{{ $customer->due_amount > 0 ? "full_due" : "1_month" }}',
        monthlyRate: {{ (float)($customer->monthly_bill ?? ($customer->package?->price ?? 0)) }},
        totalDue: {{ (float)($customer->due_amount ?? 0) }},
        currentExpiryDate: '{{ $customer->expiry_date ? \Carbon\Carbon::parse($customer->expiry_date)->format("Y-m-d") : "" }}',

        selectPreset(type) {
            this.presetType = type;
            if (type === 'full_due') {
                this.amount = this.totalDue > 0 ? this.totalDue : this.monthlyRate;
                this.months = Math.max(1, Math.round(this.amount / (this.monthlyRate || 1)));
            } else if (type === '1_month') {
                this.amount = this.monthlyRate;
                this.months = 1;
            } else if (type === '2_months') {
                this.amount = this.monthlyRate * 2;
                this.months = 2;
            } else if (type === '3_months') {
                this.amount = this.monthlyRate * 3;
                this.months = 3;
            }
        },

        calcNewExpiry() {
            const monthsToAdd = parseInt(this.months, 10) || 0;
            if (monthsToAdd <= 0) {
                return this.currentExpiryDate || 'Unchanged';
            }

            let base = new Date();
            if (this.currentExpiryDate) {
                const cur = new Date(this.currentExpiryDate);
                if (cur > base) {
                    base = cur;
                }
            }

            const newDate = new Date(base.getFullYear(), base.getMonth() + monthsToAdd, base.getDate());
            const options = { day: '2-digit', month: 'short', year: 'numeric' };
            return newDate.toLocaleDateString('en-GB', options);
        }
    };
}
</script>
@endpush