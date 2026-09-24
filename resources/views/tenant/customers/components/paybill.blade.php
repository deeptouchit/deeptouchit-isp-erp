<!-- Receive Customer Bill Payment Modal Component (Natural White Modern Design) -->
<div x-show="payModal" 
     x-cloak 
     class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-900/40 backdrop-blur-xs overflow-y-auto"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">

    <div class="bg-white text-slate-800 rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden my-auto" 
         @click.away="if (!isPayLoading) payModal = false"
         x-transition:enter="transition ease-out duration-200 transform"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150 transform"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">

        <!-- Natural Soft Header -->
        <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <div class="w-7 h-7 rounded-lg bg-teal-50 text-teal-600 border border-teal-100 flex items-center justify-center text-xs flex-shrink-0 shadow-2xs">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div>
                    <h4 class="text-xs font-semibold text-slate-800">{{ __('Receive Customer Bill Payment') }}</h4>
                    <p class="text-[10.5px] text-slate-500 font-normal">
                        {{ __('Subscriber') }}: <span class="font-mono font-bold text-slate-700">{{ $customer->username ?: 'Client' }}</span> &bull; 
                        {{ __('ID') }}: <span class="font-mono text-slate-600">{{ $customer->customer_id ?: 'CUST-' . $customer->id }}</span>
                    </p>
                </div>
            </div>
            <button type="button" 
                    @click="payModal = false" 
                    :disabled="isPayLoading"
                    class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                <i class="fas fa-times text-xs"></i>
            </button>
        </div>

        <!-- Form Body -->
        <form @submit.prevent="submitBillPayment()">
            <div class="p-4 space-y-3 text-xs max-h-[72vh] overflow-y-auto">

                <!-- 1. Customer Financial Snapshot Card -->
                <div class="p-2.5 sm:p-3 rounded-xl bg-slate-50/90 border border-slate-200/90 flex items-center justify-between gap-3">
                    <div class="space-y-0.5">
                        <span class="text-[10px] uppercase font-semibold text-slate-400 tracking-wider">{{ __('Current Due Amount') }}</span>
                        <div class="text-base font-bold font-mono flex items-baseline gap-1" :class="Number(dueData.currentDue) > 0 ? 'text-rose-600' : 'text-emerald-600'">
                            <span class="text-xs font-sans opacity-70">{{ $currencySymbol ?? '৳' }}</span>
                            <span x-text="Number(dueData.currentDue).toFixed(2)"></span>
                        </div>
                        <div class="text-[10px] text-slate-500">
                            {{ __('Monthly Plan') }}: <strong class="text-slate-700 font-mono inline-flex items-baseline gap-0.5"><span class="text-[10px] opacity-70">{{ $currencySymbol ?? '৳' }}</span><span x-text="Number(packageData.currentMonthlyBill).toFixed(2)"></span></strong>
                        </div>
                    </div>

                    <div class="text-right space-y-0.5 font-mono flex-shrink-0">
                        <span class="text-[10px] uppercase font-sans font-semibold text-slate-400 tracking-wider">{{ __('Expiry Status') }}</span>
                        <div class="text-xs font-bold text-slate-800">
                            <span x-text="cycleData.currentExpiryDateFormatted || '{{ __('No Expiry Set') }}'"></span>
                        </div>
                        <div class="text-[10px]">
                            <template x-if="cycleData.remainingDays > 0">
                                <span class="text-emerald-600 font-semibold" x-text="cycleData.remainingDays + ' {{ __('days left') }}'"></span>
                            </template>
                            <template x-if="cycleData.remainingDays <= 0 && cycleData.currentExpiryDate">
                                <span class="text-rose-600 font-semibold">{{ __('Expired') }}</span>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- 2. Payment Type / Mode Selector -->
                <div class="space-y-1">
                    <label class="block text-slate-700 font-semibold text-[11px]">
                        {{ __('Payment Mode') }} <span class="text-rose-500">*</span>
                    </label>
                    <div class="grid grid-cols-3 gap-2">
                        <!-- Mode 1: Due / Regular Bill -->
                        <label class="relative flex flex-col p-2 rounded-lg border cursor-pointer transition select-none text-center"
                                :class="payForm.payment_mode === 'due' ? 'bg-teal-50/70 border-teal-500 ring-1 ring-teal-400 text-teal-900' : 'bg-slate-50 border-slate-200 hover:bg-slate-100/70 text-slate-700'">
                            <input type="radio" 
                                   name="pay_mode" 
                                   value="due" 
                                   x-model="payForm.payment_mode" 
                                   @change="onPaymentModeChange()"
                                   class="sr-only">
                            <i class="fas fa-file-invoice text-teal-600 text-xs mb-0.5"></i>
                            <span class="font-bold text-[11px]">{{ __('Due / Regular') }}</span>
                            <span class="text-[9.5px] text-slate-500">{{ __('Due / Monthly Bill') }}</span>
                        </label>

                        <!-- Mode 2: Advance Multi-Month Recharge -->
                        <label class="relative flex flex-col p-2 rounded-lg border cursor-pointer transition select-none text-center"
                                :class="payForm.payment_mode === 'advance' ? 'bg-teal-50/70 border-teal-500 ring-1 ring-teal-400 text-teal-900' : 'bg-slate-50 border-slate-200 hover:bg-slate-100/70 text-slate-700'">
                            <input type="radio" 
                                   name="pay_mode" 
                                   value="advance" 
                                   x-model="payForm.payment_mode" 
                                   @change="onPaymentModeChange()"
                                   class="sr-only">
                            <i class="fas fa-calendar-plus text-teal-600 text-xs mb-0.5"></i>
                            <span class="font-bold text-[11px]">{{ __('Advance Pay') }}</span>
                            <span class="text-[9.5px] text-slate-500">{{ __('Advance Recharge') }}</span>
                        </label>

                        <!-- Mode 3: Custom Amount -->
                        <label class="relative flex flex-col p-2 rounded-lg border cursor-pointer transition select-none text-center"
                                :class="payForm.payment_mode === 'custom' ? 'bg-teal-50/70 border-teal-500 ring-1 ring-teal-400 text-teal-900' : 'bg-slate-50 border-slate-200 hover:bg-slate-100/70 text-slate-700'">
                            <input type="radio" 
                                   name="pay_mode" 
                                   value="custom" 
                                   x-model="payForm.payment_mode" 
                                   @change="onPaymentModeChange()"
                                   class="sr-only">
                            <i class="fas fa-coins text-teal-600 text-xs mb-0.5"></i>
                            <span class="font-bold text-[11px]">{{ __('Custom Amount') }}</span>
                            <span class="text-[9.5px] text-slate-500">{{ __('Custom Amount') }}</span>
                        </label>
                    </div>
                </div>

                <!-- 3. Advance Month Selector Strip -->
                <div x-show="payForm.payment_mode === 'advance'" x-cloak class="p-2.5 rounded-lg bg-teal-50/60 border border-teal-200 space-y-1.5">
                    <label class="block text-teal-900 font-semibold text-[11px]">
                        {{ __('Select Months to Recharge') }}:
                    </label>
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <template x-for="m in [1, 2, 3, 6, 12]" :key="m">
                            <button type="button" 
                                    @click="setAdvanceMonths(m)" 
                                    class="px-2.5 py-1 rounded-md text-xs font-mono font-bold transition cursor-pointer"
                                    :class="payForm.months === m ? 'bg-teal-600 text-white shadow-xs' : 'bg-white text-slate-700 hover:bg-teal-100 border border-slate-200'"
                                    x-text="m + (m === 1 ? ' {{ __('Month') }}' : ' {{ __('Months') }}')">
                            </button>
                        </template>
                    </div>
                </div>

                <!-- 4. Amount and Discount Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                    <!-- Collected Amount -->
                    <div class="space-y-1">
                        <label class="block text-slate-700 font-semibold text-[11px]">
                            {{ __('Collected Amount') }} <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center font-bold text-slate-400 font-mono">
                                {{ $currencySymbol ?? '৳' }}
                            </span>
                            <input type="number" 
                                   step="0.01" 
                                   min="1" 
                                   x-model="payForm.amount" 
                                   required 
                                   placeholder="0.00"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs pl-8 pr-3 py-1.5 text-slate-900 font-mono font-bold focus:bg-white focus:border-teal-500 focus:ring-1 focus:ring-teal-500 transition">
                        </div>
                    </div>

                    <!-- Discount (Optional) -->
                    <div class="space-y-1">
                        <label class="block text-slate-700 font-semibold text-[11px]">
                            {{ __('Discount') }} <span class="text-slate-400 font-normal">({{ __('Optional') }})</span>
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center font-bold text-slate-400 font-mono">
                                {{ $currencySymbol ?? '৳' }}
                            </span>
                            <input type="number" 
                                   step="0.01" 
                                   min="0" 
                                   x-model="payForm.discount" 
                                   placeholder="0.00"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs pl-8 pr-3 py-1.5 text-slate-900 font-mono focus:bg-white focus:border-teal-500 transition">
                        </div>
                    </div>
                </div>

                <!-- 5. Payment Channel Selection -->
                <div class="space-y-1.5">
                    <label class="block text-slate-700 font-semibold text-[11px]">
                        {{ __('Payment Method') }} <span class="text-rose-500">*</span>
                    </label>

                    <!-- Reseller Channel Grid (Cash, bKash, Bangla QR Dedicated Page) -->
                    <template x-if="packageData.isReseller">
                        <div class="grid grid-cols-3 gap-2">
                            <!-- 1. Cash -->
                            <button type="button" 
                                    @click="payForm.payment_method = 'cash'"
                                    class="p-2.5 rounded-xl border text-center transition flex flex-col items-center gap-1 cursor-pointer"
                                    :class="payForm.payment_method === 'cash' ? 'bg-emerald-50/80 border-emerald-500 ring-1 ring-emerald-400 text-emerald-950 shadow-2xs' : 'bg-slate-50 border-slate-200 hover:bg-slate-100 text-slate-700'">
                                <div class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs">
                                    <i class="fas fa-money-bill-wave"></i>
                                </div>
                                <span class="text-xs font-bold">{{ __('Cash') }}</span>
                                <span class="text-[9px] px-1.5 py-0.5 rounded font-mono font-semibold"
                                      :class="payForm.payment_method === 'cash' ? 'bg-emerald-200/70 text-emerald-800' : 'bg-slate-200/70 text-slate-600'">
                                    {{ __('Wallet Debit') }}
                                </span>
                            </button>

                            <!-- 2. bKash -->
                            <button type="button" 
                                    @click="payForm.payment_method = 'bkash'"
                                    class="p-2.5 rounded-xl border text-center transition flex flex-col items-center gap-1 cursor-pointer"
                                    :class="payForm.payment_method === 'bkash' ? 'bg-pink-50/80 border-pink-500 ring-1 ring-pink-400 text-pink-950 shadow-2xs' : 'bg-slate-50 border-slate-200 hover:bg-slate-100 text-slate-700'">
                                <div class="w-7 h-7 rounded-lg bg-pink-100 text-pink-700 flex items-center justify-center text-xs">
                                    <i class="fas fa-mobile-screen"></i>
                                </div>
                                <span class="text-xs font-bold">{{ __('bKash') }}</span>
                                <span class="text-[9px] px-1.5 py-0.5 rounded font-mono font-semibold"
                                      :class="payForm.payment_method === 'bkash' ? 'bg-pink-200/70 text-pink-800' : 'bg-slate-200/70 text-slate-600'">
                                    {{ __('Direct API') }}
                                </span>
                            </button>

                            <!-- 3. Bangla QR -> Opens Dedicated Page -->
                            <a :href="'{{ url('reseller/customers') }}/' + customerId + '/bangla-qr?amount=' + (payForm.amount || 0)"
                               class="p-2.5 rounded-xl border text-center transition flex flex-col items-center gap-1 cursor-pointer bg-slate-50 border-slate-200 hover:bg-teal-50/80 hover:border-teal-400 text-slate-700 hover:text-teal-950 group shadow-2xs">
                                <div class="w-7 h-7 rounded-lg bg-teal-100 text-teal-700 group-hover:bg-teal-200 flex items-center justify-center text-xs">
                                    <i class="fas fa-qrcode"></i>
                                </div>
                                <span class="text-xs font-bold flex items-center gap-1">
                                    <span>{{ __('Bangla QR') }}</span>
                                    <i class="fas fa-arrow-up-right-from-square text-[8.5px] text-teal-600"></i>
                                </span>
                                <span class="text-[9px] px-1.5 py-0.5 rounded font-mono font-semibold bg-teal-100/80 text-teal-800">
                                    {{ __('Full Page') }}
                                </span>
                            </a>
                        </div>
                    </template>

                    <!-- Direct ISP Retail Channels (For Core Admin) -->
                    <template x-if="!packageData.isReseller">
                        <div class="grid grid-cols-3 sm:grid-cols-6 gap-1.5">
                            <!-- Cash -->
                            <button type="button" 
                                    @click="payForm.payment_method = 'cash'"
                                    class="p-2 rounded-lg border text-center transition flex flex-col items-center gap-0.5 cursor-pointer"
                                    :class="payForm.payment_method === 'cash' ? 'bg-teal-50 border-teal-500 ring-1 ring-teal-400 text-teal-800' : 'bg-slate-50 border-slate-200 hover:bg-slate-100 text-slate-700'">
                                <i class="fas fa-money-bill text-emerald-600 text-xs"></i>
                                <span class="text-[10px] font-semibold">{{ __('Cash') }}</span>
                            </button>

                            <!-- bKash -->
                            <button type="button" 
                                    @click="payForm.payment_method = 'bkash'"
                                    class="p-2 rounded-lg border text-center transition flex flex-col items-center gap-0.5 cursor-pointer"
                                    :class="payForm.payment_method === 'bkash' ? 'bg-pink-50 border-pink-500 ring-1 ring-pink-400 text-pink-800' : 'bg-slate-50 border-slate-200 hover:bg-slate-100 text-slate-700'">
                                <i class="fas fa-mobile-alt text-pink-600 text-xs"></i>
                                <span class="text-[10px] font-semibold">{{ __('bKash') }}</span>
                            </button>

                            <!-- Bangla QR -->
                            <button type="button" 
                                    @click="payForm.payment_method = 'bangla_qr'"
                                    class="p-2 rounded-lg border text-center transition flex flex-col items-center gap-0.5 cursor-pointer"
                                    :class="payForm.payment_method === 'bangla_qr' ? 'bg-teal-50 border-teal-500 ring-1 ring-teal-400 text-teal-800' : 'bg-slate-50 border-slate-200 hover:bg-slate-100 text-slate-700'">
                                <i class="fas fa-qrcode text-emerald-700 text-xs"></i>
                                <span class="text-[10px] font-semibold">{{ __('Bangla QR') }}</span>
                            </button>

                            <!-- Nagad -->
                            <button type="button" 
                                    @click="payForm.payment_method = 'nagad'"
                                    class="p-2 rounded-lg border text-center transition flex flex-col items-center gap-0.5 cursor-pointer"
                                    :class="payForm.payment_method === 'nagad' ? 'bg-orange-50 border-orange-500 ring-1 ring-orange-400 text-orange-800' : 'bg-slate-50 border-slate-200 hover:bg-slate-100 text-slate-700'">
                                <i class="fas fa-wallet text-orange-600 text-xs"></i>
                                <span class="text-[10px] font-semibold">{{ __('Nagad') }}</span>
                            </button>

                            <!-- Rocket -->
                            <button type="button" 
                                    @click="payForm.payment_method = 'rocket'"
                                    class="p-2 rounded-lg border text-center transition flex flex-col items-center gap-0.5 cursor-pointer"
                                    :class="payForm.payment_method === 'rocket' ? 'bg-purple-50 border-purple-500 ring-1 ring-purple-400 text-purple-800' : 'bg-slate-50 border-slate-200 hover:bg-slate-100 text-slate-700'">
                                <i class="fas fa-rocket text-purple-600 text-xs"></i>
                                <span class="text-[10px] font-semibold">{{ __('Rocket') }}</span>
                            </button>

                            <!-- Bank -->
                            <button type="button" 
                                    @click="payForm.payment_method = 'bank_transfer'"
                                    class="p-2 rounded-lg border text-center transition flex flex-col items-center gap-0.5 cursor-pointer"
                                    :class="payForm.payment_method === 'bank_transfer' ? 'bg-blue-50 border-blue-500 ring-1 ring-blue-400 text-blue-800' : 'bg-slate-50 border-slate-200 hover:bg-slate-100 text-slate-700'">
                                <i class="fas fa-university text-blue-600 text-xs"></i>
                                <span class="text-[10px] font-semibold">{{ __('Bank') }}</span>
                            </button>
                        </div>
                    </template>

                    <!-- Reseller Commission & Ledger Calculation Box -->
                    <template x-if="packageData.isReseller">
                        <div class="mt-2 p-2.5 rounded-xl border transition text-[11px]"
                             :class="payForm.payment_method === 'cash' ? 'bg-emerald-50/60 border-emerald-200 text-emerald-950' : 'bg-pink-50/60 border-pink-200 text-pink-950'">
                            
                            <!-- Scenario A: CASH PAYMENT -->
                            <template x-if="payForm.payment_method === 'cash'">
                                <div class="space-y-1.5">
                                    <div class="flex items-center justify-between font-semibold border-b border-emerald-200/70 pb-1">
                                        <span class="flex items-center gap-1.5 text-emerald-900">
                                            <i class="fas fa-hand-holding-dollar text-emerald-700"></i>
                                            <span>{{ __('Cash Collection & Wallet Debit:') }}</span>
                                        </span>
                                        <span class="font-mono text-emerald-800 text-[10px]" x-text="'{{ __('Commission') }}: ' + packageData.resellerCommissionRate + '%'"></span>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2 font-mono text-[10.5px]">
                                        <div>
                                            <span class="text-slate-600 font-sans">{{ __('Cash in Hand:') }}</span>
                                            <strong class="text-slate-900" x-text="'{{ $currencySymbol ?? '৳' }}' + Number(payForm.amount || 0).toFixed(2)"></strong>
                                        </div>
                                        <div class="text-right">
                                            <span class="text-slate-600 font-sans">{{ __('Net Wallet Debit:') }}</span>
                                            <strong class="text-rose-700 font-bold" x-text="'{{ $currencySymbol ?? '৳' }}' + (Number(payForm.amount || 0) * (1 - (packageData.resellerCommissionRate / 100))).toFixed(2)"></strong>
                                        </div>
                                    </div>
                                    <div class="text-[10px] pt-1 flex items-center justify-between"
                                         :class="(packageData.resellerBalance + packageData.resellerCreditLimit) >= (Number(payForm.amount || 0) * (1 - (packageData.resellerCommissionRate / 100))) ? 'text-emerald-700' : 'text-rose-700 font-bold'">
                                        <span>{{ __('Available Wallet:') }} {{ $currencySymbol ?? '৳' }}<span x-text="Number(packageData.resellerBalance || 0).toFixed(2)"></span></span>
                                        <span x-show="(packageData.resellerBalance + packageData.resellerCreditLimit) < (Number(payForm.amount || 0) * (1 - (packageData.resellerCommissionRate / 100)))">
                                            ⚠️ {{ __('Insufficient Wallet Balance!') }}
                                        </span>
                                    </div>
                                </div>
                            </template>

                            <!-- Scenario B: bKash PAYMENT -->
                            <template x-if="payForm.payment_method === 'bkash'">
                                <div class="space-y-1.5">
                                    <div class="flex items-center justify-between font-semibold border-b border-pink-200/70 pb-1">
                                        <span class="flex items-center gap-1.5 text-pink-900">
                                            <i class="fas fa-gift text-pink-700"></i>
                                            <span>{{ __('bKash API Checkout & Commission:') }}</span>
                                        </span>
                                        <span class="font-mono text-pink-800 text-[10px]" x-text="'{{ __('Rate') }}: ' + packageData.resellerCommissionRate + '%'"></span>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2 font-mono text-[10.5px]">
                                        <div>
                                            <span class="text-slate-600 font-sans">{{ __('Customer Pays (Full):') }}</span>
                                            <strong class="text-slate-900" x-text="'{{ $currencySymbol ?? '৳' }}' + Number(payForm.amount || 0).toFixed(2)"></strong>
                                        </div>
                                        <div class="text-right">
                                            <span class="text-slate-600 font-sans">{{ __('Commission Earned:') }}</span>
                                            <strong class="text-emerald-700 font-bold" x-text="'+{{ $currencySymbol ?? '৳' }}' + (Number(payForm.amount || 0) * (packageData.resellerCommissionRate / 100)).toFixed(2)"></strong>
                                        </div>
                                    </div>
                                    <div class="text-[10px] text-pink-700 pt-0.5">
                                        {{ __('✨ Commission will be credited directly to Reseller Wallet upon successful bKash payment.') }}
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>

                <!-- 6. Billing Month & Trx ID Grid -->
                <div :class="(!packageData.isReseller && payForm.payment_method !== 'cash') || (packageData.isReseller && payForm.payment_method === 'bangla_qr') ? 'grid grid-cols-1 sm:grid-cols-2 gap-2.5' : 'space-y-1.5'">
                    <div class="space-y-1">
                        <label class="block text-slate-700 font-semibold text-[11px]">
                            {{ __('Billing Month') }}
                        </label>
                        <input type="text" 
                               x-model="payForm.billing_month" 
                               placeholder="e.g. September 2026"
                               class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 text-slate-800 focus:bg-white focus:border-teal-500 transition">
                    </div>

                    <div class="space-y-1" x-show="(!packageData.isReseller && payForm.payment_method !== 'cash') || (packageData.isReseller && payForm.payment_method === 'bangla_qr')" x-cloak>
                        <label class="block text-slate-700 font-semibold text-[11px]">
                            {{ __('Transaction ID') }} <span class="text-slate-400 font-normal">({{ __('Optional') }})</span>
                        </label>
                        <input type="text" 
                               x-model="payForm.transaction_id" 
                               placeholder="e.g. 9J28KLM..."
                               class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 text-slate-800 font-mono focus:bg-white focus:border-teal-500 transition">
                    </div>
                </div>

                <!-- 7. Remarks / Note (Shown only when needed) -->
                <div class="space-y-1" x-show="!packageData.isReseller && payForm.payment_method !== 'cash'" x-cloak>
                    <label class="block text-slate-700 font-semibold text-[11px]">
                        {{ __('Payment Note / Remarks') }} <span class="text-slate-400 font-normal">({{ __('Optional') }})</span>
                    </label>
                    <input type="text" 
                           x-model="payForm.notes" 
                           placeholder="e.g. Collected by Area Agent / Shop Counter..."
                           class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 text-slate-800 focus:bg-white focus:border-teal-500 transition">
                </div>

                <!-- 8. Options & Switches -->
                <div class="space-y-2 pt-1 border-t border-slate-100">
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox" 
                               x-model="payForm.extend_validity" 
                               class="rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                        <span class="text-slate-700 text-[11px] font-medium">
                            {{ __('Extend package validity / expiry date automatically') }}
                        </span>
                    </label>

                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox" 
                               x-model="payForm.reactivate_line" 
                               class="rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                        <span class="text-slate-700 text-[11px] font-medium">
                            {{ __('Auto-reactivate line in MikroTik & FreeRADIUS if currently expired/suspended') }}
                        </span>
                    </label>
                </div>

            </div>

            <!-- Footer Buttons -->
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                <button type="button" 
                        @click="payModal = false" 
                        :disabled="isPayLoading"
                        class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition shadow-2xs cursor-pointer">
                    {{ __('Cancel') }}
                </button>

                <button type="submit" 
                        :disabled="isPayLoading || !payForm.amount || Number(payForm.amount) <= 0"
                        class="font-medium text-xs px-4 py-1.5 rounded-lg transition flex items-center gap-1.5 shadow-xs disabled:opacity-50 text-white cursor-pointer"
                        :class="payForm.payment_method === 'bkash' ? 'bg-pink-600 hover:bg-pink-700' : 'bg-teal-600 hover:bg-teal-700'">
                    <i class="fas" :class="isPayLoading ? 'fa-spinner fa-spin' : (payForm.payment_method === 'bkash' ? 'fa-external-link-alt' : 'fa-receipt')"></i>
                    <span x-text="isPayLoading ? '{{ __('Processing...') }}' : (payForm.payment_method === 'bkash' ? '{{ __('Pay with bKash Checkout') }}' : '{{ __('Confirm & Collect Payment') }}')"></span>
                </button>
            </div>
        </form>

    </div>
</div>
