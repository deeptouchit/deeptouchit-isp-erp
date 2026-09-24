<!-- Customer Status / Enable-Disable Modal Component -->
<div x-show="statusModal" 
     x-cloak 
     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">

    <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-md overflow-hidden" 
         @click.away="statusModal = false"
         x-transition:enter="transition ease-out duration-200 transform"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150 transform"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">

        <!-- Modal Soft Natural Header -->
        <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-slate-900 text-white flex items-center justify-center text-xs flex-shrink-0">
                    <i class="fas fa-toggle-on"></i>
                </div>
                <div>
                    <h4 class="text-xs font-semibold text-slate-800">{{ __('Connection Status (Enable/Disable)') }}</h4>
                    <p class="text-[10.5px] text-slate-500 font-normal">MikroTik Line Enable/Disable &amp; CoA Kick</p>
                </div>
            </div>
            <button type="button" @click="statusModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center">
                <i class="fas fa-times text-xs"></i>
            </button>
        </div>

        <!-- Form Body -->
        <form @submit.prevent="submitStatusUpdate()" class="p-4 space-y-3.5">
            <!-- Notice Box -->
            <div class="p-2.5 rounded-lg bg-amber-50/70 border border-amber-100 flex items-start gap-2">
                <i class="fas fa-info-circle text-amber-600 text-xs mt-0.5 flex-shrink-0"></i>
                <p class="text-[11px] text-amber-900 leading-tight">
                    <strong>{{ __('Note') }}:</strong> {{ __('Disabling or suspending the customer will immediately terminate their live PPP session on MikroTik and reject RADIUS authentication.') }}
                </p>
            </div>

            <!-- Status Selector -->
            <div class="space-y-1">
                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider">{{ __('Account Status') }}</label>
                <select x-model="statusForm.status" 
                        required
                        class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-2 focus:bg-white focus:border-slate-800 font-semibold text-slate-800 transition">
                    <option value="active">{{ __('Active (Enabled)') }}</option>
                    <option value="suspended">{{ __('Suspended (Disabled)') }}</option>
                    <option value="expired">{{ __('Expired (Expired Validity)') }}</option>
                    <option value="due">{{ __('Due (Pending Arrears)') }}</option>
                    <option value="disconnected">{{ __('Disconnected (Permanent Off)') }}</option>
                </select>
            </div>

            <!-- Quick Toggle Status Switch -->
            <div class="p-3 rounded-lg bg-slate-50 border border-slate-200 flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-slate-800 block">{{ __('Quick Action') }}</span>
                    <span class="text-[10.5px] text-slate-500" x-text="statusForm.status === 'active' ? '{{ __('Line is currently ACTIVE') }}' : '{{ __('Line is currently INACTIVE') }}'"></span>
                </div>
                <button type="button" 
                        @click="statusForm.status = (statusForm.status === 'active' ? 'suspended' : 'active')"
                        class="px-3 py-1 rounded-md text-xs font-bold transition flex items-center gap-1.5 cursor-pointer"
                        :class="statusForm.status === 'active' ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200' : 'bg-rose-100 text-rose-800 hover:bg-rose-200'">
                    <i class="fas" :class="statusForm.status === 'active' ? 'fa-toggle-on text-emerald-600' : 'fa-toggle-off text-rose-600'"></i>
                    <span x-text="statusForm.status === 'active' ? '{{ __('Switch to Disable') }}' : '{{ __('Switch to Enable') }}'"></span>
                </button>
            </div>

            <!-- Modal Footer -->
            <div class="pt-3 border-t border-slate-200 flex items-center justify-end gap-2">
                <button type="button" 
                        @click="statusModal = false" 
                        class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                    {{ __('Cancel') }}
                </button>
                <button type="submit" 
                        :disabled="isStatusLoading" 
                        class="bg-slate-900 hover:bg-slate-800 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                    <i class="fas" :class="isStatusLoading ? 'fa-spinner fa-spin' : 'fa-check-circle'"></i>
                    <span>{{ __('Apply Status') }}</span>
                </button>
            </div>
        </form>

    </div>
</div>
