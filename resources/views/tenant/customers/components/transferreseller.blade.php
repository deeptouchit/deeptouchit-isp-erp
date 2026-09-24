<!-- Transfer to Reseller Modal Component (Natural White Production Grade) -->
<div x-show="transferModal" 
     x-cloak 
     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">

    <div class="bg-white text-slate-800 rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-md overflow-hidden" 
         @click.away="if (!isTransferLoading) transferModal = false"
         x-transition:enter="transition ease-out duration-200 transform"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150 transform"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">

        <!-- Natural Soft Header -->
        <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <div class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 border border-indigo-100 flex items-center justify-center text-xs flex-shrink-0 shadow-2xs">
                    <i class="fas fa-exchange-alt"></i>
                </div>
                <div>
                    <h4 class="text-xs font-semibold text-slate-800">Transfer Subscriber Affiliation</h4>
                    <p class="text-[10.5px] text-slate-500 font-normal">
                        Subscriber: <span class="font-mono font-bold text-slate-700">{{ $customer->username ?: 'Client' }}</span> &bull; 
                        ID: <span class="font-mono text-slate-600">{{ $customer->customer_id ?: 'CUST-' . $customer->id }}</span>
                    </p>
                </div>
            </div>
            <button type="button" 
                    @click="transferModal = false" 
                    :disabled="isTransferLoading"
                    class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center">
                <i class="fas fa-times text-xs"></i>
            </button>
        </div>

        <!-- Form Body -->
        <form @submit.prevent="submitTransfer()">
            <div class="p-4 space-y-3.5 text-xs">

                <!-- 1. Current Affiliation Snapshot Card -->
                <div class="p-3 rounded-xl bg-slate-50/90 border border-slate-200/90 flex items-center justify-between gap-3">
                    <div class="space-y-0.5">
                        <span class="text-[10px] uppercase font-semibold text-slate-400 tracking-wider">Current Affiliation</span>
                        <div class="font-bold text-slate-800 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full" :class="transferData.currentResellerId ? 'bg-purple-500' : 'bg-cyan-500'"></span>
                            <span x-text="transferData.currentResellerName || 'ISP Direct Retail'"></span>
                            <template x-if="transferData.currentResellerCode">
                                <span class="px-1.5 py-0.2 rounded bg-purple-100 text-purple-800 text-[9.5px] font-mono font-semibold" x-text="transferData.currentResellerCode"></span>
                            </template>
                        </div>
                    </div>

                    <div class="text-right space-y-0.5 font-mono flex-shrink-0">
                        <span class="text-[10px] uppercase font-sans font-semibold text-slate-400 tracking-wider">Plan & Bill</span>
                        <div class="text-xs font-bold text-slate-700">
                            <span x-text="packageData.currentPackageName"></span>
                        </div>
                        <div class="text-[10px] text-slate-500">
                            ৳<span x-text="Number(packageData.currentMonthlyBill).toFixed(2)"></span> /mo
                        </div>
                    </div>
                </div>

                <!-- 2. Transfer Destination Target Selection -->
                <div class="space-y-1.5">
                    <label class="block text-slate-700 font-semibold text-[11px]">
                        Transfer Target (কোথায় স্থানান্তর করতে চান?) <span class="text-rose-500">*</span>
                    </label>
                    <div class="grid grid-cols-2 gap-2">
                        <!-- Direct ISP Retail -->
                        <label class="relative flex items-center gap-2 p-2.5 rounded-lg border cursor-pointer transition select-none"
                               :class="transferForm.target_type === 'isp' ? 'bg-cyan-50/70 border-cyan-400 ring-1 ring-cyan-300' : 'bg-slate-50 border-slate-200 hover:bg-slate-100/70'">
                            <input type="radio" 
                                   name="target_type" 
                                   value="isp" 
                                   x-model="transferForm.target_type" 
                                   @change="transferForm.reseller_id = ''"
                                   class="text-cyan-600 focus:ring-cyan-500">
                            <div>
                                <div class="font-semibold text-slate-800 text-xs flex items-center gap-1">
                                    <i class="fas fa-building text-cyan-600 text-[10px]"></i> ISP Direct
                                </div>
                                <div class="text-[9.5px] text-slate-500 leading-tight">সরাসরি মেইন আইএসপি</div>
                            </div>
                        </label>

                        <!-- Reseller Partner -->
                        <label class="relative flex items-center gap-2 p-2.5 rounded-lg border cursor-pointer transition select-none"
                               :class="transferForm.target_type === 'reseller' ? 'bg-purple-50/70 border-purple-400 ring-1 ring-purple-300' : 'bg-slate-50 border-slate-200 hover:bg-slate-100/70'">
                            <input type="radio" 
                                   name="target_type" 
                                   value="reseller" 
                                   x-model="transferForm.target_type" 
                                   class="text-purple-600 focus:ring-purple-500">
                            <div>
                                <div class="font-semibold text-slate-800 text-xs flex items-center gap-1">
                                    <i class="fas fa-handshake text-purple-600 text-[10px]"></i> Reseller Partner
                                </div>
                                <div class="text-[9.5px] text-slate-500 leading-tight">সাব-আইএসপি / পার্টনার</div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- 3. Select Reseller Dropdown (When target == reseller) -->
                <template x-if="transferForm.target_type === 'reseller'">
                    <div class="space-y-1.5 p-3 rounded-xl bg-purple-50/40 border border-purple-100">
                        <label class="block text-purple-900 font-semibold text-[11px]">
                            Select Target Reseller Partner <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <select x-model="transferForm.reseller_id" 
                                    required
                                    class="w-full bg-white border border-purple-200 rounded-lg text-xs px-3 py-2 text-slate-800 font-medium focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition">
                                <option value="">-- Choose Reseller --</option>
                                @foreach($allResellers ?? [] as $res)
                                    <option value="{{ $res->id }}">
                                        {{ $res->name }} ({{ $res->code }}) &bull; Balance: ৳{{ number_format((float)($res->wallet_balance ?? 0), 2) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </template>

                <!-- 4. Transfer Note / Reason -->
                <div class="space-y-1.5">
                    <label class="block text-slate-700 font-semibold text-[11px]">
                        Transfer Reason / Note <span class="text-slate-400 font-normal">(Optional)</span>
                    </label>
                    <input type="text" 
                           x-model="transferForm.notes" 
                           placeholder="e.g. Area franchise handover, Subscriber request..."
                           class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 text-slate-800 focus:bg-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                </div>

                <!-- 5. Options -->
                <div class="space-y-2 pt-1 border-t border-slate-100">
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox" 
                               x-model="transferForm.sync_mikrotik" 
                               class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-slate-700 text-[11px] font-medium">
                            Sync subscriber profile & comments to MikroTik RouterOS & FreeRADIUS
                        </span>
                    </label>
                </div>

            </div>

            <!-- Footer Buttons -->
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                <button type="button" 
                        @click="transferModal = false" 
                        :disabled="isTransferLoading"
                        class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition shadow-2xs">
                    Cancel
                </button>

                <button type="submit" 
                        :disabled="isTransferLoading || (transferForm.target_type === 'reseller' && !transferForm.reseller_id)"
                        class="bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white font-medium text-xs px-4 py-1.5 rounded-lg transition flex items-center gap-1.5 shadow-xs">
                    <i class="fas" :class="isTransferLoading ? 'fa-spinner fa-spin' : 'fa-check'"></i>
                    <span x-text="isTransferLoading ? 'Transferring...' : 'Confirm Transfer'"></span>
                </button>
            </div>
        </form>

    </div>
</div>
