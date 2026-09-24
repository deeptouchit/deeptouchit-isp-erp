<!-- Change Internet Package Modal Component (Natural White Production Grade) -->
<div x-show="packageModal" 
     x-cloak 
     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">

    <div class="bg-white text-slate-800 rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden" 
         @click.away="if (!isPackageLoading) packageModal = false"
         x-transition:enter="transition ease-out duration-200 transform"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150 transform"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">

        <!-- Natural Soft Header -->
        <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <div class="w-7 h-7 rounded-lg bg-fuchsia-50 text-fuchsia-600 border border-fuchsia-100 flex items-center justify-center text-xs flex-shrink-0 shadow-2xs">
                    <i class="fas fa-box"></i>
                </div>
                <div>
                    <h4 class="text-xs font-semibold text-slate-800">{{ __('Change Package') }}</h4>
                    <p class="text-[10.5px] text-slate-500 font-normal">
                        {{ __('User') }}: <span class="font-mono font-bold text-slate-700">{{ $customer->username ?: 'PPPoE Client' }}</span> &bull; 
                        {{ __('ID') }}: <span class="font-mono text-slate-600">{{ $customer->customer_id ?: 'CUST-' . $customer->id }}</span>
                    </p>
                </div>
            </div>
            <button type="button" 
                    @click="packageModal = false" 
                    :disabled="isPackageLoading"
                    class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center">
                <i class="fas fa-times text-xs"></i>
            </button>
        </div>

        <!-- Form Body -->
        <form @submit.prevent="submitPackageChange()">
            <div class="p-4 space-y-3.5 text-xs">

                <!-- 1. Current Plan & Expiry Snapshot -->
                <div class="p-3 rounded-xl bg-slate-50/90 border border-slate-200/90 flex items-center justify-between gap-3">
                    <div class="space-y-0.5">
                        <span class="text-[10px] uppercase font-semibold text-slate-400 tracking-wider">{{ __('Current Package') }}</span>
                        <div class="font-bold text-slate-800 flex items-center gap-1.5 flex-wrap">
                            <span class="w-2 h-2 rounded-full bg-fuchsia-500 flex-shrink-0"></span>
                            <span x-text="packageData.currentPackageName || '{{ $customer->package_name ?: __('Custom Plan') }}'"></span>
                            <template x-if="packageData.currentSpeed">
                                <span class="px-1.5 py-0.5 rounded bg-fuchsia-100 text-fuchsia-800 text-[10px] font-mono font-bold" x-text="packageData.currentSpeed"></span>
                            </template>
                        </div>
                        <div class="text-[11px] font-mono text-slate-500">
                            {{ __('Monthly Rate') }}: <strong class="text-slate-800">{{ $currencySymbol ?? '৳' }}<span x-text="Number(packageData.currentMonthlyBill).toFixed(2)"></span></strong>
                        </div>
                    </div>

                    <div class="text-right space-y-0.5 font-mono flex-shrink-0">
                        <span class="text-[10px] uppercase font-sans font-semibold text-slate-400 tracking-wider">{{ __('Current Expiry') }}</span>
                        <div class="font-bold text-slate-800" x-text="packageData.currentExpiryDate || '{{ __('No Expiry Set') }}'"></div>
                        <div class="text-[10.5px]">
                            <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-medium"
                                  :class="packageData.remainingDays > 0 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200'">
                                <span x-text="packageData.remainingDays > 0 ? packageData.remainingDays + ' {{ __('days left') }}' : '{{ __('Expired / Due') }}'"></span>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- 2. Direct ISP or Reseller Status Banner -->
                <template x-if="packageData.isReseller">
                    <div class="p-2.5 rounded-lg bg-purple-50/70 border border-purple-200/80 flex items-center justify-between text-purple-900">
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-md bg-purple-100 text-purple-700 flex items-center justify-center text-[10px]">
                                <i class="fas fa-handshake"></i>
                            </div>
                            <div>
                                <div class="font-semibold text-[11px]" x-text="'{{ __('Partner') }}: ' + packageData.resellerName + ' (' + packageData.resellerCode + ')'"></div>
                                <div class="text-[10px] text-purple-700">{{ __('Commission') }}: <span class="font-bold" x-text="packageData.resellerCommissionRate + '%'"></span></div>
                            </div>
                        </div>
                        <div class="text-right font-mono">
                            <div class="text-[9.5px] uppercase font-sans text-purple-600 font-semibold">{{ __('Wallet Balance') }}</div>
                            <div class="font-bold text-xs" :class="packageData.resellerBalance > 0 ? 'text-emerald-700' : 'text-rose-700'" x-text="'{{ $currencySymbol ?? '৳' }}' + Number(packageData.resellerBalance).toFixed(2)"></div>
                        </div>
                    </div>
                </template>

                <template x-if="!packageData.isReseller">
                    <div class="p-2.5 rounded-lg bg-cyan-50/70 border border-cyan-200/80 flex items-center justify-between text-cyan-900">
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-md bg-cyan-100 text-cyan-700 flex items-center justify-center text-[10px]">
                                <i class="fas fa-building"></i>
                            </div>
                            <div>
                                <div class="font-semibold text-[11px]">{{ __('Direct ISP Subscriber') }}</div>
                                <div class="text-[10px] text-cyan-700">{{ __('Account') }}: <span class="font-bold">{{ __('Main ISP Direct Client') }}</span></div>
                            </div>
                        </div>
                        <div class="text-right font-mono">
                            <div class="text-[9.5px] uppercase font-sans text-cyan-600 font-semibold">{{ __('Due Balance') }}</div>
                            <div class="font-bold text-xs" :class="Number('{{ (float)($customer->due_amount ?? 0) }}') > 0 ? 'text-rose-700' : 'text-emerald-700'">
                                @currency((float)($customer->due_amount ?? 0))
                            </div>
                        </div>
                    </div>
                </template>

                <!-- 3. Target Package Selection -->
                <div class="space-y-1.5">
                    <label class="block text-slate-700 font-semibold text-[11px]">
                        {{ __('Select New Package') }} <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <select x-model="packageForm.package_id" 
                                @change="onPackageSelect()"
                                required
                                class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-2 text-slate-800 font-medium focus:bg-white focus:border-fuchsia-500 focus:ring-1 focus:ring-fuchsia-500 transition appearance-none">
                            <option value="">{{ __('-- Choose Target Package to Switch --') }}</option>
                            @foreach($allPackages as $pkg)
                                @php
                                    $isCurrent = ($customer->package_id == $pkg->id) || ($customer->package_name == $pkg->name) || ($customer->package_name == $pkg->package_name);
                                @endphp
                                <option value="{{ $pkg->id }}" 
                                        data-name="{{ $pkg->package_name ?: $pkg->name }}" 
                                        data-price="{{ (float)$pkg->price }}"
                                        data-speed="{{ $pkg->download_speed ?: 'Auto' }}/{{ $pkg->upload_speed ?: 'Auto' }} Mbps">
                                    {{ $pkg->package_name ?: $pkg->name }} &mdash; @currency($pkg->price) ({{ $pkg->download_speed }}/{{ $pkg->upload_speed }} Mbps) {{ $isCurrent ? '★ [' . __('Current Package') . ']' : '' }}
                                </option>
                            @endforeach
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2.5 text-slate-400">
                            <i class="fas fa-chevron-down text-[10px]"></i>
                        </div>
                    </div>
                </div>

                <!-- 4. Dynamic Live Upgrade / Downgrade Preview Card -->
                <template x-if="selectedPackageInfo">
                    <div class="p-3 rounded-xl border transition space-y-2.5"
                         :class="upgradeImpact.isUpgrade ? 'bg-amber-50/50 border-amber-200/90' : (upgradeImpact.isDowngrade ? 'bg-blue-50/50 border-blue-200/90' : 'bg-slate-50 border-slate-200')">
                        
                        <!-- Calculation Breakdown Header -->
                        <div class="flex items-center justify-between border-b pb-2"
                             :class="upgradeImpact.isUpgrade ? 'border-amber-200/70' : 'border-slate-200'">
                            <span class="font-bold text-[11px]" 
                                  :class="upgradeImpact.isUpgrade ? 'text-amber-900' : (upgradeImpact.isDowngrade ? 'text-blue-900' : 'text-slate-800')">
                                <i class="fas" :class="upgradeImpact.isUpgrade ? 'fa-arrow-trend-up' : (upgradeImpact.isDowngrade ? 'fa-arrow-trend-down' : 'fa-check')"></i>
                                <span x-text="upgradeImpact.typeLabel"></span>
                            </span>
                            <span class="font-mono text-[11px] font-bold text-slate-800" x-text="'{{ __('New Rate') }}: {{ $currencySymbol ?? '৳' }}' + selectedPackageInfo.price + '/mo'"></span>
                        </div>

                        <!-- Reseller Proration & Validity Impact Alert -->
                        <template x-if="packageData.isReseller && packageData.remainingDays > 0">
                            <div class="space-y-2">
                                <!-- Scenario A: Reseller has sufficient wallet balance -->
                                <template x-if="upgradeImpact.isUpgrade && upgradeImpact.hasSufficientBalance">
                                    <div class="p-2 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-start gap-2">
                                        <i class="fas fa-check-circle text-emerald-600 mt-0.5 text-xs flex-shrink-0"></i>
                                        <div class="text-[11px]">
                                            <span class="font-semibold">{{ __('Reseller Balance Sufficient:') }}</span>
                                            {{ __('Prorated amount') }} <strong class="font-mono font-bold text-emerald-900">{{ $currencySymbol ?? '৳' }}<span x-text="upgradeImpact.proratedDiff"></span></strong> {{ __('will be deducted from Reseller wallet. Expiry remains') }} <span class="font-mono font-bold" x-text="packageData.currentExpiryDate"></span>.
                                        </div>
                                    </div>
                                </template>

                                <!-- Scenario B: Reseller has INSUFFICIENT balance (মেয়াদ কমবে) -->
                                <template x-if="upgradeImpact.isUpgrade && !upgradeImpact.hasSufficientBalance">
                                    <div class="p-2.5 rounded-lg bg-amber-100/70 border border-amber-300 text-amber-900 flex items-start gap-2">
                                        <i class="fas fa-triangle-exclamation text-amber-700 mt-0.5 text-xs flex-shrink-0"></i>
                                        <div class="text-[11px] space-y-1">
                                            <div>
                                                <strong class="text-amber-950 font-bold">⚠️ {{ __('Insufficient Partner Balance:') }}</strong>
                                                {{ __('Needed') }} <strong>{{ $currencySymbol ?? '৳' }}<span x-text="upgradeImpact.proratedDiff"></span></strong>, {{ __('available balance is') }} <strong>{{ $currencySymbol ?? '৳' }}<span x-text="Number(packageData.resellerBalance).toFixed(2)"></span></strong>.
                                            </div>
                                            <div class="p-1.5 rounded bg-white/80 border border-amber-200 font-mono text-[10.5px] text-amber-900">
                                                {{ __('Validity adjusted to') }} <strong class="text-rose-700" x-text="upgradeImpact.adjustedDays + ' {{ __('days') }}'"></strong> 
                                                ({{ __('New Expiry') }}: <strong class="text-slate-800" x-text="upgradeImpact.adjustedExpiryDate"></strong>).
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <!-- Scenario C: Downgrade (Prorated Refund to Wallet) -->
                                <template x-if="upgradeImpact.isDowngrade">
                                    <div class="p-2 rounded-lg bg-blue-50 border border-blue-200 text-blue-800 flex items-start gap-2">
                                        <i class="fas fa-info-circle text-blue-600 mt-0.5 text-xs flex-shrink-0"></i>
                                        <div class="text-[11px]">
                                            <span class="font-semibold">{{ __('Downgrade Proration:') }}</span>
                                            {{ __('Prorated credit') }} <strong class="font-mono font-bold text-blue-900">{{ $currencySymbol ?? '৳' }}<span x-text="upgradeImpact.proratedRefund"></span></strong> {{ __('will be refunded to Partner wallet.') }}
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>

                        <!-- Direct Customer Proration Handling -->
                        <template x-if="!packageData.isReseller && packageData.remainingDays > 0 && (upgradeImpact.isUpgrade || upgradeImpact.isDowngrade)">
                            <div class="space-y-2">
                                <div class="p-2 rounded-lg bg-white border border-slate-200 text-slate-700 text-[11px] space-y-1">
                                    <div class="flex items-center justify-between">
                                        <span class="font-semibold">{{ __('Remaining Active Cycle:') }}</span>
                                        <span class="font-mono font-bold text-slate-800" x-text="packageData.remainingDays + ' {{ __('days left') }}'"></span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="font-semibold" x-text="upgradeImpact.isUpgrade ? '{{ __('Prorated Upgrade Difference:') }}' : '{{ __('Prorated Downgrade Difference:') }}'"></span>
                                        <span class="font-mono font-bold" 
                                              :class="upgradeImpact.isUpgrade ? 'text-amber-800' : 'text-blue-800'"
                                              x-text="'{{ $currencySymbol ?? '৳' }}' + (upgradeImpact.isUpgrade ? upgradeImpact.proratedDiff : upgradeImpact.proratedRefund)"></span>
                                    </div>
                                </div>

                                <!-- Policy Selector -->
                                <div class="space-y-1 pt-1">
                                    <div class="text-[10px] font-semibold text-slate-500 uppercase tracking-wider">{{ __('Proration & Expiry Policy:') }}</div>
                                    
                                    <!-- Option 1: Adjust Expiry Automatically -->
                                    <label class="p-2 rounded-lg border flex items-start gap-2 cursor-pointer transition"
                                           :class="packageForm.direct_proration_mode === 'adjust_expiry' ? 'bg-fuchsia-50/80 border-fuchsia-300 text-fuchsia-950' : 'bg-white border-slate-200 hover:bg-slate-50'">
                                        <input type="radio" 
                                               name="direct_proration_mode" 
                                               value="adjust_expiry" 
                                               x-model="packageForm.direct_proration_mode" 
                                               class="mt-0.5 text-fuchsia-600 focus:ring-fuchsia-500">
                                        <div class="text-[11px] leading-tight">
                                            <div class="font-bold flex items-center gap-1.5">
                                                <span>{{ __('Adjust Expiry Date Automatically') }}</span>
                                                <span class="px-1.5 py-0.2 rounded bg-fuchsia-100 text-fuchsia-700 text-[9px] font-bold">{{ __('Recommended') }}</span>
                                            </div>
                                            <div class="text-[10px] text-slate-600 mt-0.5">
                                                <template x-if="upgradeImpact.isUpgrade">
                                                    <span>{{ __('Recalculates expiry from') }} <strong x-text="packageData.remainingDays + ' {{ __('days') }}'"></strong> {{ __('to') }} <strong class="text-rose-700" x-text="upgradeImpact.adjustedDays + ' {{ __('days') }}'"></strong> ({{ __('New Expiry') }}: <strong x-text="upgradeImpact.adjustedExpiryDate"></strong>).</span>
                                                </template>
                                                <template x-if="upgradeImpact.isDowngrade">
                                                    <span>{{ __('Extends expiry from') }} <strong x-text="packageData.remainingDays + ' {{ __('days') }}'"></strong> {{ __('to') }} <strong class="text-emerald-700" x-text="upgradeImpact.adjustedDays + ' {{ __('days') }}'"></strong> ({{ __('New Expiry') }}: <strong x-text="upgradeImpact.adjustedExpiryDate"></strong>).</span>
                                                </template>
                                            </div>
                                        </div>
                                    </label>

                                    <!-- Option 2: Add to Due / Bill -->
                                    <label class="p-2 rounded-lg border flex items-start gap-2 cursor-pointer transition"
                                           :class="packageForm.direct_proration_mode === 'add_to_due' ? 'bg-fuchsia-50/80 border-fuchsia-300 text-fuchsia-950' : 'bg-white border-slate-200 hover:bg-slate-50'">
                                        <input type="radio" 
                                               name="direct_proration_mode" 
                                               value="add_to_due" 
                                               x-model="packageForm.direct_proration_mode" 
                                               class="mt-0.5 text-fuchsia-600 focus:ring-fuchsia-500">
                                        <div class="text-[11px] leading-tight">
                                            <div class="font-bold">{{ __('Add Difference to Customer Due / Balance') }}</div>
                                            <div class="text-[10px] text-slate-600 mt-0.5">
                                                {{ __('Expiry remains') }} <strong x-text="packageData.currentExpiryDate"></strong>. {{ __('Difference of') }} <strong x-text="'{{ $currencySymbol ?? '৳' }}' + (upgradeImpact.isUpgrade ? upgradeImpact.proratedDiff : upgradeImpact.proratedRefund)"></strong> {{ __('will be adjusted in due.') }}
                                            </div>
                                        </div>
                                    </label>

                                    <!-- Option 3: Apply from Next Billing Cycle -->
                                    <label class="p-2 rounded-lg border flex items-start gap-2 cursor-pointer transition"
                                           :class="packageForm.direct_proration_mode === 'next_cycle' ? 'bg-fuchsia-50/80 border-fuchsia-300 text-fuchsia-950' : 'bg-white border-slate-200 hover:bg-slate-50'">
                                        <input type="radio" 
                                               name="direct_proration_mode" 
                                               value="next_cycle" 
                                               x-model="packageForm.direct_proration_mode" 
                                               class="mt-0.5 text-fuchsia-600 focus:ring-fuchsia-500">
                                        <div class="text-[11px] leading-tight">
                                            <div class="font-bold">{{ __('Apply from Next Billing Cycle') }}</div>
                                            <div class="text-[10px] text-slate-600 mt-0.5">{{ __('No prorated charge now. New rate takes effect on next invoice cycle.') }}</div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </template>

                    </div>
                </template>

                <!-- 5. Advanced Sync & Disconnect Options -->
                <div class="space-y-2 pt-1">
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox" 
                               x-model="packageForm.update_bill" 
                               class="rounded border-slate-300 text-fuchsia-600 focus:ring-fuchsia-500">
                        <span class="text-slate-700 text-[11px] font-medium">{{ __("Update customer's recurring monthly bill to new rate") }}</span>
                    </label>

                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox" 
                               x-model="packageForm.disconnect_session" 
                               class="rounded border-slate-300 text-fuchsia-600 focus:ring-fuchsia-500">
                        <span class="text-slate-700 text-[11px] font-medium">
                            {{ __('Terminate active MikroTik PPPoE session (Apply new speed immediately)') }}
                        </span>
                    </label>
                </div>

            </div>

            <!-- Footer Buttons -->
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                <button type="button" 
                        @click="packageModal = false" 
                        :disabled="isPackageLoading"
                        class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition shadow-2xs">
                    {{ __('Cancel') }}
                </button>

                <button type="submit" 
                        :disabled="isPackageLoading || !packageForm.package_id"
                        class="bg-fuchsia-600 hover:bg-fuchsia-700 disabled:opacity-50 text-white font-medium text-xs px-4 py-1.5 rounded-lg transition flex items-center gap-1.5 shadow-xs">
                    <i class="fas" :class="isPackageLoading ? 'fa-spinner fa-spin' : 'fa-check'"></i>
                    <span x-text="isPackageLoading ? '{{ __('Applying Changes...') }}' : '{{ __('Confirm Package Change') }}'"></span>
                </button>
            </div>
        </form>

    </div>
</div>
