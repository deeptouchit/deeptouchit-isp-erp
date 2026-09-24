<!-- Change Billing Cycle Modal Component (Natural White Production Grade) -->
<div x-show="cycleModal" 
     x-cloak 
     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">

    <div class="bg-white text-slate-800 rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden" 
         @click.away="if (!isCycleLoading) cycleModal = false"
         x-transition:enter="transition ease-out duration-200 transform"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150 transform"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">

        <!-- Natural Soft Header -->
        <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <div class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center text-xs flex-shrink-0 shadow-2xs">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div>
                    <h4 class="text-xs font-semibold text-slate-800">Change Billing Cycle & Validity</h4>
                    <p class="text-[10.5px] text-slate-500 font-normal">
                        Subscriber: <span class="font-mono font-bold text-slate-700">{{ $customer->username ?: 'Client' }}</span> &bull; 
                        ID: <span class="font-mono text-slate-600">{{ $customer->customer_id ?: 'CUST-' . $customer->id }}</span>
                    </p>
                </div>
            </div>
            <button type="button" 
                    @click="cycleModal = false" 
                    :disabled="isCycleLoading"
                    class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center">
                <i class="fas fa-times text-xs"></i>
            </button>
        </div>

        <!-- Form Body -->
        <form @submit.prevent="submitBillingCycle()">
            <div class="p-4 space-y-3.5 text-xs max-h-[75vh] overflow-y-auto">

                <!-- 1. Current Expiry & Billing Snapshot Card -->
                <div class="p-3 rounded-xl bg-slate-50/90 border border-slate-200/90 flex items-center justify-between gap-3">
                    <div class="space-y-1">
                        <span class="text-[10px] uppercase font-semibold text-slate-400 tracking-wider">Current Expiry Status</span>
                        <div class="font-bold text-slate-800 flex items-center gap-1.5 flex-wrap">
                            <span class="font-mono" x-text="cycleData.currentExpiryDateFormatted || 'No Expiry Set'"></span>
                            <template x-if="cycleData.remainingDays > 0">
                                <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-mono font-semibold">
                                    <span x-text="cycleData.remainingDays"></span> days left
                                </span>
                            </template>
                            <template x-if="cycleData.remainingDays <= 0 && cycleData.currentExpiryDate">
                                <span class="px-2 py-0.5 rounded-full bg-rose-100 text-rose-800 text-[10px] font-mono font-semibold">
                                    Expired
                                </span>
                            </template>
                        </div>
                    </div>

                    <div class="text-right space-y-0.5 font-mono flex-shrink-0">
                        <span class="text-[10px] uppercase font-sans font-semibold text-slate-400 tracking-wider">Billing Type</span>
                        <div class="text-xs font-bold text-blue-700 capitalize">
                            <span x-text="cycleForm.billing_type"></span>
                        </div>
                        <div class="text-[10px] text-slate-500">
                            Grace: <strong class="text-slate-700" x-text="cycleForm.grace_period_days + ' Days'"></strong>
                        </div>
                    </div>
                </div>

                <!-- 2. Billing Type Selector (Prepaid vs Postpaid) -->
                <div class="space-y-1.5">
                    <label class="block text-slate-700 font-semibold text-[11px]">
                        Billing Method / Cycle Type <span class="text-rose-500">*</span>
                    </label>
                    <div class="grid grid-cols-2 gap-2">
                        <!-- Prepaid -->
                        <label class="relative flex items-center gap-2.5 p-2.5 rounded-lg border cursor-pointer transition select-none"
                               :class="cycleForm.billing_type === 'prepaid' ? 'bg-blue-50/60 border-blue-400 ring-1 ring-blue-300' : 'bg-slate-50 border-slate-200 hover:bg-slate-100/70'">
                            <input type="radio" 
                                   name="billing_type" 
                                   value="prepaid" 
                                   x-model="cycleForm.billing_type" 
                                   class="text-blue-600 focus:ring-blue-500">
                            <div>
                                <div class="font-semibold text-slate-800 text-xs">Prepaid Billing</div>
                                <div class="text-[10px] text-slate-500 leading-tight">Pay before use &bull; Fixed validity</div>
                            </div>
                        </label>

                        <!-- Postpaid -->
                        <label class="relative flex items-center gap-2.5 p-2.5 rounded-lg border cursor-pointer transition select-none"
                               :class="cycleForm.billing_type === 'postpaid' ? 'bg-blue-50/60 border-blue-400 ring-1 ring-blue-300' : 'bg-slate-50 border-slate-200 hover:bg-slate-100/70'">
                            <input type="radio" 
                                   name="billing_type" 
                                   value="postpaid" 
                                   x-model="cycleForm.billing_type" 
                                   class="text-blue-600 focus:ring-blue-500">
                            <div>
                                <div class="font-semibold text-slate-800 text-xs">Postpaid Billing</div>
                                <div class="text-[10px] text-slate-500 leading-tight">Monthly bill cycle &bull; Pay on invoice</div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- 3. Expiry Date & Quick Validity Extensions -->
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <label class="block text-slate-700 font-semibold text-[11px]">
                            New Expiry Date <span class="text-rose-500">*</span>
                        </label>
                        <template x-if="cycleForm.expiry_date">
                            <span class="text-[10.5px] font-mono font-medium" 
                                  :class="getCalculatedDays() > 0 ? 'text-emerald-700' : 'text-rose-600'">
                                <i class="fas fa-clock mr-0.5"></i>
                                <span x-text="getCalculatedDays() > 0 ? getCalculatedDays() + ' days validity' : 'Expired on chosen date'"></span>
                            </span>
                        </template>
                    </div>

                    <div class="relative rounded-lg shadow-2xs">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 text-xs">
                            <i class="fas fa-calendar"></i>
                        </div>
                        <input type="date" 
                               required
                               x-model="cycleForm.expiry_date" 
                               class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs pl-8 pr-3 py-2 text-slate-900 font-mono font-bold focus:bg-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition">
                    </div>

                    <!-- Quick Extend Preset Pill Buttons -->
                    <div class="flex items-center gap-1.5 flex-wrap pt-1">
                        <span class="text-[10px] text-slate-400">Quick Extend:</span>
                        <button type="button" 
                                @click="extendExpiryDays(30)" 
                                class="px-2 py-0.5 rounded bg-blue-50 hover:bg-blue-100 text-blue-700 text-[10px] font-medium transition border border-blue-200">
                            +1 Month (30d)
                        </button>
                        <button type="button" 
                                @click="extendExpiryDays(90)" 
                                class="px-2 py-0.5 rounded bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-[10px] font-medium transition border border-indigo-200">
                            +3 Months
                        </button>
                        <button type="button" 
                                @click="extendExpiryDays(180)" 
                                class="px-2 py-0.5 rounded bg-purple-50 hover:bg-purple-100 text-purple-700 text-[10px] font-medium transition border border-purple-200">
                            +6 Months
                        </button>
                        <button type="button" 
                                @click="extendExpiryDays(365)" 
                                class="px-2 py-0.5 rounded bg-amber-50 hover:bg-amber-100 text-amber-800 text-[10px] font-medium transition border border-amber-200">
                            +1 Year
                        </button>
                        <button type="button" 
                                @click="setToNextMonthFirst()" 
                                class="px-2 py-0.5 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] font-medium transition border border-slate-200">
                            1st of Next Month
                        </button>
                    </div>
                </div>

                <!-- 4. Grace Period (Days) -->
                <div class="space-y-1.5">
                    <label class="block text-slate-700 font-semibold text-[11px]">
                        Grace Period Days (গ্রেস পিরিয়ড)
                    </label>
                    <div class="flex items-center gap-2">
                        <div class="relative w-32 rounded-lg shadow-2xs">
                            <input type="number" 
                                   min="0" 
                                   max="90" 
                                   x-model="cycleForm.grace_period_days" 
                                   placeholder="0"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 text-slate-900 font-mono font-bold focus:bg-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition">
                        </div>
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <button type="button" 
                                    @click="cycleForm.grace_period_days = 0"
                                    class="px-2 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] font-medium transition border border-slate-200">
                                0 Days
                            </button>
                            <button type="button" 
                                    @click="cycleForm.grace_period_days = 3"
                                    class="px-2 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] font-medium transition border border-slate-200">
                                3 Days
                            </button>
                            <button type="button" 
                                    @click="cycleForm.grace_period_days = 5"
                                    class="px-2 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] font-medium transition border border-slate-200">
                                5 Days
                            </button>
                            <button type="button" 
                                    @click="cycleForm.grace_period_days = 7"
                                    class="px-2 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] font-medium transition border border-slate-200">
                                7 Days
                            </button>
                        </div>
                    </div>
                    <p class="text-[10px] text-slate-500">
                        গ্রাহকের মেয়াদ শেষ হওয়ার পর এই কয়দিন লাইন চালু থাকবে এবং এরপর অটো-কাট কার্যকর হবে।
                    </p>
                </div>

                <!-- 5. Advanced Options & Switches -->
                <div class="space-y-2 pt-2 border-t border-slate-100">
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox" 
                               x-model="cycleForm.auto_cut_enabled" 
                               class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-slate-700 text-[11px] font-medium">
                            Auto-Cut PPPoE connection upon Expiry + Grace Period
                        </span>
                    </label>

                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox" 
                               x-model="cycleForm.reactivate_line" 
                               class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-slate-700 text-[11px] font-medium">
                            Auto-reactivate line & sync to MikroTik RouterOS & FreeRADIUS if extended
                        </span>
                    </label>
                </div>

            </div>

            <!-- Footer Buttons -->
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                <button type="button" 
                        @click="cycleModal = false" 
                        :disabled="isCycleLoading"
                        class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition shadow-2xs">
                    Cancel
                </button>

                <button type="submit" 
                        :disabled="isCycleLoading || !cycleForm.expiry_date"
                        class="bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white font-medium text-xs px-4 py-1.5 rounded-lg transition flex items-center gap-1.5 shadow-xs">
                    <i class="fas" :class="isCycleLoading ? 'fa-spinner fa-spin' : 'fa-check'"></i>
                    <span x-text="isCycleLoading ? 'Updating Cycle...' : 'Save Billing Cycle'"></span>
                </button>
            </div>
        </form>

    </div>
</div>
