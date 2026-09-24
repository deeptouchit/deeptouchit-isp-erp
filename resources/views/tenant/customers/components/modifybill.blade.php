<!-- Modify Monthly Bill Modal Component (Natural White Production Grade) -->
<div x-show="billModal" 
     x-cloak 
     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">

    <div class="bg-white text-slate-800 rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-md overflow-hidden" 
         @click.away="if (!isBillLoading) billModal = false"
         x-transition:enter="transition ease-out duration-200 transform"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150 transform"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">

        <!-- Natural Soft Header -->
        <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <div class="w-7 h-7 rounded-lg bg-pink-50 text-pink-600 border border-pink-100 flex items-center justify-center text-xs flex-shrink-0 shadow-2xs">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div>
                    <h4 class="text-xs font-semibold text-slate-800">Modify Monthly Bill</h4>
                    <p class="text-[10.5px] text-slate-500 font-normal">
                        User: <span class="font-mono font-bold text-slate-700">{{ $customer->username ?: 'Client' }}</span> &bull; 
                        ID: <span class="font-mono text-slate-600">{{ $customer->customer_id ?: 'CUST-' . $customer->id }}</span>
                    </p>
                </div>
            </div>
            <button type="button" 
                    @click="billModal = false" 
                    :disabled="isBillLoading"
                    class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center">
                <i class="fas fa-times text-xs"></i>
            </button>
        </div>

        <!-- Form Body -->
        <form @submit.prevent="submitModifyBill()">
            <div class="p-4 space-y-3.5 text-xs">

                <!-- 1. Current Plan & Bill Summary Card -->
                <div class="p-3 rounded-xl bg-slate-50/90 border border-slate-200/90 flex items-center justify-between gap-3">
                    <div class="space-y-0.5">
                        <span class="text-[10px] uppercase font-semibold text-slate-400 tracking-wider">Assigned Plan</span>
                        <div class="font-bold text-slate-800 flex items-center gap-1.5 flex-wrap">
                            <span class="w-2 h-2 rounded-full bg-pink-500 flex-shrink-0"></span>
                            <span x-text="packageData.currentPackageName"></span>
                            <template x-if="packageData.currentSpeed">
                                <span class="px-1.5 py-0.2 rounded bg-pink-100 text-pink-800 text-[9.5px] font-mono font-semibold" x-text="packageData.currentSpeed"></span>
                            </template>
                        </div>
                        <div class="text-[10.5px] font-mono text-slate-500">
                            Base Plan Price: <strong class="text-slate-700 inline-flex items-baseline gap-0.5"><span class="text-[10px] opacity-70">{{ $currencySymbol ?? '৳' }}</span><span x-text="Number('{{ (float)($currentPackage?->price ?? $customer->monthly_bill) }}').toFixed(2)"></span></strong>
                        </div>
                    </div>

                    <div class="text-right space-y-0.5 font-mono flex-shrink-0">
                        <span class="text-[10px] uppercase font-sans font-semibold text-slate-400 tracking-wider">Current Bill</span>
                        <div class="text-sm font-bold text-pink-700 inline-flex items-baseline gap-0.5">
                            <span class="text-xs opacity-70">{{ $currencySymbol ?? '৳' }}</span><span x-text="Number(billForm.current_monthly_bill).toFixed(2)"></span>
                        </div>
                        <div class="text-[10px] text-slate-500">
                            Due: <span class="font-bold inline-flex items-baseline gap-0.5" :class="Number('{{ (float)($customer->due_amount ?? 0) }}') > 0 ? 'text-rose-600' : 'text-emerald-600'"><span class="text-[10px] opacity-70">{{ $currencySymbol ?? '৳' }}</span><span>{{ number_format((float)($customer->due_amount ?? 0), 2) }}</span></span>
                        </div>
                    </div>
                </div>

                <!-- 2. New Monthly Bill Input -->
                <div class="space-y-1.5">
                    <label class="block text-slate-700 font-semibold text-[11px]">
                        New Monthly Bill ({{ $currencySymbol ?? '৳' }}) <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative rounded-lg shadow-2xs">
                        <div class="pointer-events-none absolute inset-y-0 left-0 w-8 flex items-center justify-center text-slate-400 font-mono font-bold text-xs select-none">
                            {{ $currencySymbol ?? '৳' }}
                        </div>
                        <input type="number" 
                               step="0.01" 
                               min="0" 
                               required
                               x-model="billForm.monthly_bill" 
                               placeholder="0.00"
                               class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs pl-8 pr-3 py-2 text-slate-900 font-mono font-bold focus:bg-white focus:border-pink-500 focus:ring-1 focus:ring-pink-500 transition">
                    </div>

                    <!-- Quick Preset Pill Buttons -->
                    <div class="flex items-center gap-1.5 flex-wrap pt-1">
                        <span class="text-[10px] text-slate-400">Quick Presets:</span>
                        <button type="button" 
                                @click="billForm.monthly_bill = Number('{{ (float)($currentPackage?->price ?? 500) }}').toFixed(2); billForm.reason = 'Standard Package Rate'"
                                class="px-2 py-0.5 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] font-medium transition border border-slate-200">
                            Reset to Plan ({{ $currencySymbol ?? '৳' }}{{ number_format((float)($currentPackage?->price ?? 500), 0) }})
                        </button>
                        <button type="button" 
                                @click="billForm.monthly_bill = Math.max(0, Number(billForm.current_monthly_bill) - 50).toFixed(2); billForm.reason = 'Special {{ $currencySymbol ?? '৳' }}50 Discount'"
                                class="px-2 py-0.5 rounded bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-[10px] font-medium transition border border-emerald-200">
                            -{{ $currencySymbol ?? '৳' }}50 Discount
                        </button>
                        <button type="button" 
                                @click="billForm.monthly_bill = Math.max(0, Number(billForm.current_monthly_bill) - 100).toFixed(2); billForm.reason = 'VIP {{ $currencySymbol ?? '৳' }}100 Discount'"
                                class="px-2 py-0.5 rounded bg-purple-50 hover:bg-purple-100 text-purple-700 text-[10px] font-medium transition border border-purple-200">
                            -{{ $currencySymbol ?? '৳' }}100 VIP
                        </button>
                        <button type="button" 
                                @click="billForm.monthly_bill = (Number(billForm.current_monthly_bill) + 200).toFixed(2); billForm.reason = 'Static IP Fee Added (+{{ $currencySymbol ?? '৳' }}200)'"
                                class="px-2 py-0.5 rounded bg-blue-50 hover:bg-blue-100 text-blue-700 text-[10px] font-medium transition border border-blue-200">
                            +{{ $currencySymbol ?? '৳' }}200 Static IP
                        </button>
                    </div>
                </div>

                <!-- 3. Difference Alert Card -->
                <template x-if="Number(billForm.monthly_bill) !== Number(billForm.current_monthly_bill)">
                    <div class="p-2.5 rounded-lg border flex items-center justify-between text-[11px] font-mono"
                         :class="Number(billForm.monthly_bill) < Number(billForm.current_monthly_bill) ? 'bg-emerald-50/70 border-emerald-200 text-emerald-800' : 'bg-blue-50/70 border-blue-200 text-blue-800'">
                        <div class="flex items-center gap-1.5">
                            <i class="fas" :class="Number(billForm.monthly_bill) < Number(billForm.current_monthly_bill) ? 'fa-tag text-emerald-600' : 'fa-plus-circle text-blue-600'"></i>
                            <span class="font-sans font-semibold" x-text="Number(billForm.monthly_bill) < Number(billForm.current_monthly_bill) ? 'Discount Applied:' : 'Extra Charge Added:'"></span>
                        </div>
                        <div class="font-bold flex items-baseline gap-0.5">
                            <span x-text="Number(billForm.monthly_bill) < Number(billForm.current_monthly_bill) ? '-{{ $currencySymbol ?? '৳' }}' + (Number(billForm.current_monthly_bill) - Number(billForm.monthly_bill)).toFixed(2) : '+{{ $currencySymbol ?? '৳' }}' + (Number(billForm.monthly_bill) - Number(billForm.current_monthly_bill)).toFixed(2)"></span>
                            <span class="text-[10px] font-normal text-slate-500">/ month</span>
                        </div>
                    </div>
                </template>

                <!-- 4. Modification Reason / Note -->
                <div class="space-y-1.5">
                    <label class="block text-slate-700 font-semibold text-[11px]">
                        Adjustment Reason / Note <span class="text-slate-400 font-normal">(Optional)</span>
                    </label>
                    <input type="text" 
                           x-model="billForm.reason" 
                           placeholder="e.g. VIP Client Discount, Corporate Rate, Static IP..."
                           class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 text-slate-800 focus:bg-white focus:border-pink-500 focus:ring-1 focus:ring-pink-500 transition">
                </div>

                <!-- 5. Options -->
                <div class="space-y-2 pt-1">
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox" 
                               x-model="billForm.adjust_due" 
                               class="rounded border-slate-300 text-pink-600 focus:ring-pink-500">
                        <span class="text-slate-700 text-[11px] font-medium">
                            Adjust current unpaid Due balance by price difference immediately
                        </span>
                    </label>
                </div>

            </div>

            <!-- Footer Buttons -->
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                <button type="button" 
                        @click="billModal = false" 
                        :disabled="isBillLoading"
                        class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition shadow-2xs">
                    Cancel
                </button>

                <button type="submit" 
                        :disabled="isBillLoading || !billForm.monthly_bill"
                        class="bg-pink-600 hover:bg-pink-700 disabled:opacity-50 text-white font-medium text-xs px-4 py-1.5 rounded-lg transition flex items-center gap-1.5 shadow-xs">
                    <i class="fas" :class="isBillLoading ? 'fa-spinner fa-spin' : 'fa-check'"></i>
                    <span x-text="isBillLoading ? 'Updating Bill...' : 'Save Monthly Bill'"></span>
                </button>
            </div>
        </form>

    </div>
</div>
