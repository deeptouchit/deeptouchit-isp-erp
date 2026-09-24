@extends('reseller.layouts.app')

@section('title', __('Bangla QR Payment & Commission') . ' - ' . ($customer->name ?? __('Subscriber')))

@push('styles')
<style>
    .qr-scanner-card {
        background: radial-gradient(circle at top left, #f0fdfa 0%, #ffffff 70%, #f8fafc 100%);
    }
</style>
@endpush

@section('content')
<div class="max-w-4xl mx-auto space-y-4" x-data="banglaQrPaymentManager()">

    <!-- Top Header Bar (Strict Master Rule: Back Arrow + Clean Title ONLY) -->
    <div class="bg-white p-3 rounded-xl border border-slate-200 shadow-2xs flex items-center justify-between gap-3">
        <div class="flex items-center gap-2.5">
            <a href="{{ route('reseller.customers.show', $customer->id) }}" 
               class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 flex items-center justify-center transition cursor-pointer shadow-2xs"
               title="{{ __('Back to Customer Profile') }}">
                <i class="fas fa-arrow-left text-xs"></i>
            </a>
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-teal-50 text-teal-600 border border-teal-100 flex items-center justify-center text-sm flex-shrink-0">
                    <i class="fas fa-qrcode"></i>
                </div>
                <h1 class="text-sm font-bold text-slate-900 tracking-tight">{{ __('Bangla QR Payment & Commission') }}</h1>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <span class="px-2.5 py-1 rounded-md text-[11px] font-mono font-bold bg-teal-50 text-teal-800 border border-teal-200">
                ID: {{ $customer->customer_id ?? ('CUST-' . $customer->id) }}
            </span>
        </div>
    </div>

    <!-- Main Grid: Left (QR Scan & Telemetry) | Right (Payment & Commission Form) -->
    <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-start">

        <!-- Left Column: Interoperable Bangla QR & Partner Commission Preview (5 Cols) -->
        <div class="md:col-span-5 space-y-3">
            
            <!-- Bangla QR Interactive Display Card -->
            <div class="bg-white p-4 rounded-xl border border-teal-200/90 shadow-xs space-y-3.5 qr-scanner-card text-center">
                
                <!-- Badge -->
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10.5px] font-bold bg-teal-100 text-teal-900 border border-teal-200">
                    <i class="fas fa-shield-halved text-teal-700"></i>
                    <span>{{ __('Bangladesh Bank Interoperable QR') }}</span>
                </div>

                <!-- High-Res Crisp Bangla QR Frame -->
                <div class="relative w-48 h-48 mx-auto p-3 bg-white rounded-2xl border-2 border-dashed border-teal-300 shadow-md flex items-center justify-center group">
                    <svg class="w-full h-full" viewBox="0 0 100 100" fill="none">
                        <rect width="100" height="100" fill="white" rx="6"/>
                        <!-- Top-Left Target Marker -->
                        <rect x="6" y="6" width="26" height="26" rx="3" fill="#0f766e"/>
                        <rect x="10" y="10" width="18" height="18" rx="2" fill="white"/>
                        <rect x="13" y="13" width="12" height="12" rx="1" fill="#0f766e"/>
                        
                        <!-- Top-Right Target Marker -->
                        <rect x="68" y="6" width="26" height="26" rx="3" fill="#0f766e"/>
                        <rect x="72" y="10" width="18" height="18" rx="2" fill="white"/>
                        <rect x="75" y="13" width="12" height="12" rx="1" fill="#0f766e"/>
                        
                        <!-- Bottom-Left Target Marker -->
                        <rect x="6" y="68" width="26" height="26" rx="3" fill="#0f766e"/>
                        <rect x="10" y="72" width="18" height="18" rx="2" fill="white"/>
                        <rect x="13" y="75" width="12" height="12" rx="1" fill="#0f766e"/>
                        
                        <!-- Center Pattern Data Blocks -->
                        <rect x="36" y="8" width="8" height="8" fill="#115e59"/>
                        <rect x="48" y="8" width="8" height="8" fill="#115e59"/>
                        <rect x="36" y="20" width="8" height="8" fill="#115e59"/>
                        <rect x="48" y="20" width="8" height="8" fill="#115e59"/>
                        
                        <rect x="8" y="36" width="8" height="8" fill="#115e59"/>
                        <rect x="20" y="36" width="8" height="8" fill="#115e59"/>
                        <rect x="8" y="48" width="8" height="8" fill="#115e59"/>
                        <rect x="20" y="48" width="8" height="8" fill="#115e59"/>

                        <rect x="72" y="36" width="8" height="8" fill="#115e59"/>
                        <rect x="84" y="36" width="8" height="8" fill="#115e59"/>
                        <rect x="72" y="48" width="8" height="8" fill="#115e59"/>
                        <rect x="84" y="48" width="8" height="8" fill="#115e59"/>

                        <rect x="36" y="72" width="8" height="8" fill="#115e59"/>
                        <rect x="48" y="72" width="8" height="8" fill="#115e59"/>
                        <rect x="36" y="84" width="8" height="8" fill="#115e59"/>
                        <rect x="48" y="84" width="8" height="8" fill="#115e59"/>
                        
                        <rect x="60" y="60" width="10" height="10" fill="#0d9488"/>
                        <rect x="74" y="74" width="10" height="10" fill="#0d9488"/>

                        <!-- Center Bangla QR Logo -->
                        <circle cx="50" cy="50" r="14" fill="#0d9488" stroke="#ffffff" stroke-width="2"/>
                        <text x="50" y="53" font-size="5.5" font-family="sans-serif" font-weight="bold" fill="white" text-anchor="middle">বাংলা QR</text>
                    </svg>
                </div>

                <!-- QR Merchant / Account Information -->
                <div class="space-y-1">
                    <h3 class="text-xs font-bold text-slate-800">{{ $banglaQrTitle }}</h3>
                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded bg-slate-100 border border-slate-200 text-xs font-mono font-bold text-slate-700">
                        <i class="fas fa-phone text-[10px] text-teal-600"></i>
                        <span>{{ $banglaQrNumber }}</span>
                    </div>
                </div>

                <!-- Supported Payment Apps Pill Strip -->
                <div class="pt-2 border-t border-slate-200/80 space-y-1.5">
                    <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider block">{{ __('Scan & Pay Using Any App') }}:</span>
                    <div class="flex items-center justify-center gap-1.5 flex-wrap text-[10px] font-semibold text-slate-700">
                        <span class="px-2 py-0.5 rounded bg-pink-50 text-pink-700 border border-pink-200">bKash</span>
                        <span class="px-2 py-0.5 rounded bg-orange-50 text-orange-700 border border-orange-200">Nagad</span>
                        <span class="px-2 py-0.5 rounded bg-purple-50 text-purple-700 border border-purple-200">Rocket</span>
                        <span class="px-2 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-200">Cellfin</span>
                        <span class="px-2 py-0.5 rounded bg-amber-50 text-amber-700 border border-amber-200">Upay</span>
                        <span class="px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200">CityTouch</span>
                    </div>
                </div>

            </div>

            <!-- Reseller Commission Live Telemetry Box -->
            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs space-y-3">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-md bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs">
                            <i class="fas fa-wallet"></i>
                        </div>
                        <span class="text-xs font-bold text-slate-800">{{ __('Partner Commission Summary') }}</span>
                    </div>
                    <span class="text-[10.5px] font-mono font-bold px-2 py-0.5 rounded bg-emerald-50 text-emerald-800 border border-emerald-200">
                        {{ __('Rate') }}: {{ $commissionRate }}%
                    </span>
                </div>

                <div class="space-y-2 text-xs font-mono">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500 font-sans">{{ __('Customer Pays (Total)') }}:</span>
                        <strong class="text-slate-800 font-bold">
                            {{ $currencySymbol ?? '৳' }}<span x-text="Number(form.amount || 0).toFixed(2)"></span>
                        </strong>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-slate-500 font-sans">{{ __('Reseller Commission') }} ({{ $commissionRate }}%):</span>
                        <strong class="text-emerald-700 font-bold">
                            +{{ $currencySymbol ?? '৳' }}<span x-text="calculatedCommission()"></span>
                        </strong>
                    </div>

                    <div class="flex items-center justify-between pt-1 border-t border-slate-100">
                        <span class="text-slate-500 font-sans">{{ __('Net ISP Settlement') }}:</span>
                        <strong class="text-slate-700">
                            {{ $currencySymbol ?? '৳' }}<span x-text="calculatedNetIsp()"></span>
                        </strong>
                    </div>
                </div>

                <div class="p-2.5 rounded-lg bg-amber-50/80 border border-amber-200 text-[10.5px] text-amber-900 leading-tight">
                    <i class="fas fa-clock text-amber-600 mr-1"></i>
                    {{ __('The') }} <strong>{{ $commissionRate }}% {{ __('commission') }}</strong> ({{ $currencySymbol ?? '৳' }}<span x-text="calculatedCommission()"></span>) {{ __('will be credited to your Partner Wallet upon Admin verification & approval.') }}
                </div>
            </div>

        </div>

        <!-- Right Column: Subscriber Card & Payment Submission Form (7 Cols) -->
        <div class="md:col-span-7 space-y-3">
            
            <!-- Subscriber Profile Strip -->
            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs space-y-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-full bg-cyan-50 border border-cyan-200 text-cyan-700 flex items-center justify-center font-bold text-xs">
                            {{ substr($customer->username ?: 'CU', 0, 2) }}
                        </div>
                        <div>
                            <h2 class="text-xs font-bold text-slate-900">{{ $customer->name }}</h2>
                            <span class="text-[10.5px] text-slate-500 font-mono">{{ $customer->username }} &bull; {{ $customer->phone ?: 'N/A' }}</span>
                        </div>
                    </div>

                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold font-mono uppercase {{ $customer->status === 'active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' }}">
                        {{ strtoupper($customer->status) }}
                    </span>
                </div>

                <!-- Financial Quick Info -->
                <div class="grid grid-cols-3 gap-2 text-center text-xs font-mono pt-2 border-t border-slate-100">
                    <div class="p-2 rounded-lg bg-slate-50 border border-slate-200">
                        <span class="text-[9.5px] uppercase font-sans text-slate-400 block font-semibold">{{ __('Monthly Plan') }}</span>
                        <strong class="text-slate-800">@currency($customer->monthly_bill ?? 0)</strong>
                    </div>
                    <div class="p-2 rounded-lg bg-slate-50 border border-slate-200">
                        <span class="text-[9.5px] uppercase font-sans text-slate-400 block font-semibold">{{ __('Current Due') }}</span>
                        <strong class="{{ (float)$customer->due_amount > 0 ? 'text-rose-600 font-bold' : 'text-emerald-600' }}">
                            @currency($customer->due_amount ?? 0)
                        </strong>
                    </div>
                    <div class="p-2 rounded-lg bg-slate-50 border border-slate-200">
                        <span class="text-[9.5px] uppercase font-sans text-slate-400 block font-semibold">{{ __('Expiry Date') }}</span>
                        <strong class="text-slate-800">{{ $customer->expiry_date ? $customer->expiry_date->format('d M Y') : __('No Expiry') }}</strong>
                    </div>
                </div>
            </div>

            <!-- Bangla QR Payment Form Card -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs space-y-4">
                
                <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
                    <div class="w-6 h-6 rounded-md bg-teal-100 text-teal-700 flex items-center justify-center text-xs">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-bold text-slate-900">{{ __('Confirm Payment Details') }}</h3>
                        <p class="text-[10px] text-slate-500">{{ __('Enter payment amount and Transaction ID to record payment') }}</p>
                    </div>
                </div>

                <form @submit.prevent="submitBanglaQrPayment()" class="space-y-3.5 text-xs">
                    
                    <!-- Payment Amount -->
                    <div class="space-y-1">
                        <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider">
                            {{ __('Payment Amount') }} <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center font-bold text-slate-400 font-mono">
                                {{ $currencySymbol ?? '৳' }}
                            </span>
                            <input type="number" 
                                   step="0.01" 
                                   min="1" 
                                   x-model="form.amount" 
                                   required 
                                   placeholder="0.00"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs pl-8 pr-3 py-2 text-slate-900 font-mono font-bold focus:bg-white focus:border-teal-500 focus:ring-1 focus:ring-teal-500 transition shadow-2xs">
                        </div>
                    </div>

                    <!-- Transaction ID (TrxID) -->
                    <div class="space-y-1">
                        <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider">
                            {{ __('Transaction ID (TrxID / Reference)') }} <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" 
                               x-model="form.transaction_id" 
                               required 
                               placeholder="e.g. BKT987654321 or FT2609081234" 
                               class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-2 text-slate-900 font-mono font-bold uppercase focus:bg-white focus:border-teal-500 focus:ring-1 focus:ring-teal-500 transition shadow-2xs">
                        <span class="text-[10px] text-slate-400 block">
                            {{ __('Enter the Trx ID received after scanning Bangla QR from customer or bank app.') }}
                        </span>
                    </div>

                    <!-- Billing Month -->
                    <div class="space-y-1">
                        <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider">
                            {{ __('Billing Month') }}
                        </label>
                        <input type="text" 
                               x-model="form.billing_month" 
                               placeholder="e.g. September 2026" 
                               class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-2 text-slate-900 focus:bg-white focus:border-teal-500 transition shadow-2xs">
                    </div>

                    <!-- Payment Note / Remarks -->
                    <div class="space-y-1">
                        <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider">
                            {{ __('Payment Note') }}
                        </label>
                        <input type="text" 
                               x-model="form.notes" 
                               placeholder="e.g. Paid via Bangla QR at customer counter..." 
                               class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-2 text-slate-900 focus:bg-white focus:border-teal-500 transition shadow-2xs">
                    </div>

                    <!-- Switches: Extend Expiry & Reactivate -->
                    <div class="space-y-2 pt-2 border-t border-slate-100">
                        <label class="flex items-center gap-2 cursor-pointer select-none">
                            <input type="checkbox" 
                                   x-model="form.extend_validity" 
                                   class="rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                            <span class="text-slate-700 text-[11px] font-medium">
                                {{ __('Extend package validity / expiry date automatically') }}
                            </span>
                        </label>

                        <label class="flex items-center gap-2 cursor-pointer select-none">
                            <input type="checkbox" 
                                   x-model="form.reactivate_line" 
                                   class="rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                            <span class="text-slate-700 text-[11px] font-medium">
                                {{ __('Auto-reactivate line in MikroTik & FreeRADIUS if currently expired/suspended') }}
                            </span>
                        </label>
                    </div>

                    <!-- Submit Button Area (Strict Master Rule 4) -->
                    <div class="pt-3 border-t border-slate-200 flex items-center justify-between">
                        <a href="{{ route('reseller.customers.show', $customer->id) }}" 
                           class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-4 py-2 rounded-lg transition shadow-2xs cursor-pointer">
                            {{ __('Cancel') }}
                        </a>

                        <button type="submit" 
                                :disabled="isLoading || !form.amount || !form.transaction_id" 
                                class="bg-teal-600 hover:bg-teal-700 disabled:opacity-50 text-white font-semibold text-xs px-6 py-2 rounded-lg shadow-xs transition flex items-center gap-2 cursor-pointer">
                            <i class="fas" :class="isLoading ? 'fa-spinner fa-spin' : 'fa-paper-plane'"></i>
                            <span x-text="isLoading ? '{{ __('Submitting Payment...') }}' : '{{ __('Submit for Admin Approval') }}'"></span>
                        </button>
                    </div>

                </form>

            </div>

        </div>

    </div>

</div>
@endsection

@push('scripts')
<script>
function banglaQrPaymentManager() {
    return {
        isLoading: false,
        commissionRate: {{ (float)$commissionRate }},
        form: {
            amount: '{{ number_format((float)$defaultAmount, 2, '.', '') }}',
            discount: '0.00',
            payment_method: 'bangla_qr',
            transaction_id: '',
            billing_month: '{{ Carbon\Carbon::now()->format('F Y') }}',
            notes: '',
            extend_validity: true,
            reactivate_line: true
        },
        calculatedCommission() {
            const amt = parseFloat(this.form.amount) || 0;
            return ((amt * this.commissionRate) / 100).toFixed(2);
        },
        calculatedNetIsp() {
            const amt = parseFloat(this.form.amount) || 0;
            const comm = (amt * this.commissionRate) / 100;
            return Math.max(0, amt - comm).toFixed(2);
        },
        async submitBanglaQrPayment() {
            if (!this.form.amount || parseFloat(this.form.amount) <= 0) {
                alert('Please enter a valid payment amount.');
                return;
            }
            if (!this.form.transaction_id || this.form.transaction_id.trim() === '') {
                alert('Please enter Transaction ID (TrxID).');
                return;
            }

            this.isLoading = true;
            try {
                const response = await fetch(`{{ route('reseller.customers.pay-bill', $customer->id) }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(this.form)
                });

                const res = await response.json();
                if (res.success) {
                    window.location.href = `{{ route('reseller.customers.show', $customer->id) }}?payment_success=1`;
                } else {
                    let errMsg = res.message || 'Failed to process Bangla QR payment.';
                    if (res.errors) {
                        errMsg = Object.values(res.errors).flat().join('\n');
                    }
                    alert(errMsg);
                }
            } catch (err) {
                alert('Network error while processing Bangla QR payment.');
            } finally {
                this.isLoading = false;
            }
        }
    };
}
</script>
@endpush
