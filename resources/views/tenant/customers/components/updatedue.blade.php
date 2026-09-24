<!-- Update Due Balance Modal Component (Natural White Production Grade) -->
<div x-show="dueModal" 
     x-cloak 
     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">

    <div class="bg-white text-slate-800 rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-md overflow-hidden" 
         @click.away="if (!isDueLoading) dueModal = false"
         x-transition:enter="transition ease-out duration-200 transform"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150 transform"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">

        <!-- Natural Soft Header -->
        <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <div class="w-7 h-7 rounded-lg bg-lime-50 text-lime-700 border border-lime-200 flex items-center justify-center text-xs flex-shrink-0 shadow-2xs">
                    <i class="fas fa-credit-card"></i>
                </div>
                <div>
                    <h4 class="text-xs font-semibold text-slate-800">Update Due Balance (বকেয়া সমন্বয়)</h4>
                    <p class="text-[10.5px] text-slate-500 font-normal">
                        Subscriber: <span class="font-mono font-bold text-slate-700">{{ $customer->username ?: 'Client' }}</span> &bull; 
                        ID: <span class="font-mono text-slate-600">{{ $customer->customer_id ?: 'CUST-' . $customer->id }}</span>
                    </p>
                </div>
            </div>
            <button type="button" 
                    @click="dueModal = false" 
                    :disabled="isDueLoading"
                    class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center">
                <i class="fas fa-times text-xs"></i>
            </button>
        </div>

        <!-- Form Body -->
        <form @submit.prevent="submitDueBalance()">
            <div class="p-4 space-y-3.5 text-xs">

                <!-- 1. Current Due & Bill Snapshot Card -->
                <div class="p-3 rounded-xl bg-slate-50/90 border border-slate-200/90 flex items-center justify-between gap-3">
                    <div class="space-y-0.5">
                        <span class="text-[10px] uppercase font-semibold text-slate-400 tracking-wider">Current Outstanding Due</span>
                        <div class="text-base font-bold font-mono flex items-baseline gap-1" :class="Number(dueData.currentDue) > 0 ? 'text-rose-600' : 'text-emerald-600'">
                            <span class="text-xs font-sans opacity-70">{{ $currencySymbol ?? '৳' }}</span>
                            <span x-text="Number(dueData.currentDue).toFixed(2)"></span>
                        </div>
                    </div>

                    <div class="text-right space-y-0.5 font-mono flex-shrink-0">
                        <span class="text-[10px] uppercase font-sans font-semibold text-slate-400 tracking-wider">Monthly Bill</span>
                        <div class="text-xs font-bold text-slate-700 inline-flex items-baseline gap-0.5">
                            <span class="text-[10px] opacity-70">{{ $currencySymbol ?? '৳' }}</span><span x-text="Number(packageData.currentMonthlyBill || '{{ (float)($customer->monthly_bill ?? 0) }}').toFixed(2)"></span>
                        </div>
                    </div>
                </div>

                <!-- 2. Adjustment Mode Selector (4 Action Tiles) -->
                <div class="space-y-1.5">
                    <label class="block text-slate-700 font-semibold text-[11px]">
                        Adjustment Mode (সমন্বয়ের ধরন) <span class="text-rose-500">*</span>
                    </label>
                    <div class="grid grid-cols-2 gap-2">
                        <!-- Mode: Deduct / Discount / Waiver -->
                        <label class="relative flex items-center gap-2 p-2 rounded-lg border cursor-pointer transition select-none"
                               :class="dueForm.mode === 'deduct' ? 'bg-emerald-50/70 border-emerald-400 ring-1 ring-emerald-300' : 'bg-slate-50 border-slate-200 hover:bg-slate-100/70'">
                            <input type="radio" 
                                   name="due_mode" 
                                   value="deduct" 
                                   x-model="dueForm.mode" 
                                   class="text-emerald-600 focus:ring-emerald-500">
                            <div>
                                <div class="font-semibold text-slate-800 text-[11.5px] flex items-center gap-1">
                                    <i class="fas fa-tag text-emerald-600 text-[10px]"></i> Discount / Waiver
                                </div>
                                <div class="text-[9.5px] text-slate-500 leading-tight">বকেয়া থেকে ছাড়/মওকুফ</div>
                            </div>
                        </label>

                        <!-- Mode: Direct Set -->
                        <label class="relative flex items-center gap-2 p-2 rounded-lg border cursor-pointer transition select-none"
                               :class="dueForm.mode === 'set' ? 'bg-blue-50/70 border-blue-400 ring-1 ring-blue-300' : 'bg-slate-50 border-slate-200 hover:bg-slate-100/70'">
                            <input type="radio" 
                                   name="due_mode" 
                                   value="set" 
                                   x-model="dueForm.mode" 
                                   class="text-blue-600 focus:ring-blue-500">
                            <div>
                                <div class="font-semibold text-slate-800 text-[11.5px] flex items-center gap-1">
                                    <i class="fas fa-pen text-blue-600 text-[10px]"></i> Set Exact Due
                                </div>
                                <div class="text-[9.5px] text-slate-500 leading-tight">সরাসরি নির্দিষ্ট পরিমাণ</div>
                            </div>
                        </label>

                        <!-- Mode: Add Extra Due -->
                        <label class="relative flex items-center gap-2 p-2 rounded-lg border cursor-pointer transition select-none"
                               :class="dueForm.mode === 'add' ? 'bg-amber-50/70 border-amber-400 ring-1 ring-amber-300' : 'bg-slate-50 border-slate-200 hover:bg-slate-100/70'">
                            <input type="radio" 
                                   name="due_mode" 
                                   value="add" 
                                   x-model="dueForm.mode" 
                                   class="text-amber-600 focus:ring-amber-500">
                            <div>
                                <div class="font-semibold text-slate-800 text-[11.5px] flex items-center gap-1">
                                    <i class="fas fa-plus-circle text-amber-600 text-[10px]"></i> Add Charge
                                </div>
                                <div class="text-[9.5px] text-slate-500 leading-tight">অতিরিক্ত চার্জ/বকেয়া যোগ</div>
                            </div>
                        </label>

                        <!-- Mode: Clear All Due -->
                        <label class="relative flex items-center gap-2 p-2 rounded-lg border cursor-pointer transition select-none"
                               :class="dueForm.mode === 'clear' ? 'bg-purple-50/70 border-purple-400 ring-1 ring-purple-300' : 'bg-slate-50 border-slate-200 hover:bg-slate-100/70'">
                            <input type="radio" 
                                   name="due_mode" 
                                   value="clear" 
                                   x-model="dueForm.mode" 
                                   class="text-purple-600 focus:ring-purple-500">
                            <div>
                                <div class="font-semibold text-slate-800 text-[11.5px] flex items-center gap-1">
                                    <i class="fas fa-check-double text-purple-600 text-[10px]"></i> Clear All ({{ $currencySymbol ?? '৳' }}0)
                                </div>
                                <div class="text-[9.5px] text-slate-500 leading-tight">সম্পূর্ণ বকেয়া ০ করা</div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- 3. Amount Input (Hidden when mode == clear) -->
                <template x-if="dueForm.mode !== 'clear'">
                    <div class="space-y-1.5">
                        <label class="block text-slate-700 font-semibold text-[11px]">
                            <span x-text="dueForm.mode === 'set' ? 'New Due Amount ({{ $currencySymbol ?? '৳' }})' : (dueForm.mode === 'deduct' ? 'Discount / Waiver Amount ({{ $currencySymbol ?? '৳' }})' : 'Additional Charge ({{ $currencySymbol ?? '৳' }})')"></span>
                            <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative rounded-lg shadow-2xs">
                            <div class="pointer-events-none absolute inset-y-0 left-0 w-8 flex items-center justify-center text-slate-400 font-mono font-bold text-xs select-none">
                                {{ $currencySymbol ?? '৳' }}
                            </div>
                            <input type="number" 
                                   step="0.01" 
                                   min="0" 
                                   required
                                   x-model="dueForm.amount" 
                                   placeholder="0.00"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs pl-8 pr-3 py-2 text-slate-900 font-mono font-bold focus:bg-white focus:border-lime-500 focus:ring-1 focus:ring-lime-500 transition">
                        </div>

                        <!-- Quick Preset Pill Buttons -->
                        <div class="flex items-center gap-1.5 flex-wrap pt-1">
                            <span class="text-[10px] text-slate-400">Presets:</span>
                            <template x-if="Number(dueData.currentDue) > 0">
                                <button type="button" 
                                        @click="dueForm.amount = Number(dueData.currentDue).toFixed(2); dueForm.remarks = 'Full Due Balance Amount'"
                                        class="px-2 py-0.5 rounded bg-rose-50 hover:bg-rose-100 text-rose-700 text-[10px] font-medium transition border border-rose-200">
                                    Full Due ({{ $currencySymbol ?? '৳' }}<span x-text="Number(dueData.currentDue).toFixed(0)"></span>)
                                </button>
                            </template>
                            <button type="button" 
                                    @click="dueForm.amount = Number(packageData.currentMonthlyBill || 500).toFixed(2); dueForm.remarks = '1 Month Package Bill Amount'"
                                    class="px-2 py-0.5 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] font-medium transition border border-slate-200">
                                1 Month Bill ({{ $currencySymbol ?? '৳' }}<span x-text="Number(packageData.currentMonthlyBill || 500).toFixed(0)"></span>)
                            </button>
                            <button type="button" 
                                    @click="dueForm.amount = '100.00'; dueForm.remarks = '{{ $currencySymbol ?? '৳' }}100 Adjustment'"
                                    class="px-2 py-0.5 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] font-medium transition border border-slate-200">
                                {{ $currencySymbol ?? '৳' }}100
                            </button>
                            <button type="button" 
                                    @click="dueForm.amount = '200.00'; dueForm.remarks = '{{ $currencySymbol ?? '৳' }}200 Adjustment'"
                                    class="px-2 py-0.5 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] font-medium transition border border-slate-200">
                                {{ $currencySymbol ?? '৳' }}200
                            </button>
                            <button type="button" 
                                    @click="dueForm.amount = '500.00'; dueForm.remarks = '{{ $currencySymbol ?? '৳' }}500 Adjustment'"
                                    class="px-2 py-0.5 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] font-medium transition border border-slate-200">
                                {{ $currencySymbol ?? '৳' }}500
                            </button>
                        </div>
                    </div>
                </template>

                <!-- 4. Live Calculation Summary Alert -->
                <div class="p-3 rounded-lg border bg-slate-50/80 border-slate-200/90 space-y-1.5 font-mono text-[11px]">
                    <div class="flex items-center justify-between text-slate-600">
                        <span>Current Due:</span>
                        <span class="inline-flex items-baseline gap-0.5"><span class="text-[10px] opacity-70">{{ $currencySymbol ?? '৳' }}</span><span x-text="Number(dueData.currentDue).toFixed(2)"></span></span>
                    </div>
                    <div class="flex items-center justify-between font-medium" 
                         :class="{
                             'text-emerald-700': dueForm.mode === 'deduct' || dueForm.mode === 'clear',
                             'text-amber-700': dueForm.mode === 'add',
                             'text-blue-700': dueForm.mode === 'set'
                         }">
                        <span x-text="dueForm.mode === 'deduct' ? 'Discount / Waiver:' : (dueForm.mode === 'add' ? 'Added Charge:' : (dueForm.mode === 'clear' ? 'Waiver (Clear All):' : 'Set Direct Due:'))"></span>
                        <span class="inline-flex items-baseline gap-0.5">
                            <span x-text="dueForm.mode === 'deduct' || dueForm.mode === 'clear' ? '-{{ $currencySymbol ?? '৳' }}' : (dueForm.mode === 'add' ? '+{{ $currencySymbol ?? '৳' }}' : '{{ $currencySymbol ?? '৳' }}')"></span>
                            <span x-text="dueForm.mode === 'clear' ? Number(dueData.currentDue).toFixed(2) : Number(dueForm.amount || 0).toFixed(2)"></span>
                        </span>
                    </div>
                    <div class="pt-1 border-t border-slate-200 flex items-center justify-between font-bold text-xs">
                        <span class="text-slate-800">New Final Due:</span>
                        <span class="inline-flex items-baseline gap-0.5" :class="calculateNewDue() > 0 ? 'text-rose-600' : 'text-emerald-600'">
                            <span class="text-[10px] opacity-70">{{ $currencySymbol ?? '৳' }}</span><span x-text="calculateNewDue().toFixed(2)"></span>
                        </span>
                    </div>
                </div>

                <!-- 5. Remarks / Reason -->
                <div class="space-y-1.5">
                    <label class="block text-slate-700 font-semibold text-[11px]">
                        Reason / Note (কারণ বা বিবরণ) <span class="text-slate-400 font-normal">(Optional)</span>
                    </label>
                    <input type="text" 
                           x-model="dueForm.remarks" 
                           placeholder="e.g. VIP Waiver, Previous month correction, ONU charge..."
                           class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 text-slate-800 focus:bg-white focus:border-lime-500 focus:ring-1 focus:ring-lime-500 transition">
                </div>

            </div>

            <!-- Footer Buttons -->
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                <button type="button" 
                        @click="dueModal = false" 
                        :disabled="isDueLoading"
                        class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition shadow-2xs">
                    Cancel
                </button>

                <button type="submit" 
                        :disabled="isDueLoading || (dueForm.mode !== 'clear' && (dueForm.amount === '' || Number(dueForm.amount) < 0))"
                        class="bg-lime-600 hover:bg-lime-700 disabled:opacity-50 text-white font-medium text-xs px-4 py-1.5 rounded-lg transition flex items-center gap-1.5 shadow-xs">
                    <i class="fas" :class="isDueLoading ? 'fa-spinner fa-spin' : 'fa-check'"></i>
                    <span x-text="isDueLoading ? 'Updating...' : 'Save Due Balance'"></span>
                </button>
            </div>
        </form>

    </div>
</div>
