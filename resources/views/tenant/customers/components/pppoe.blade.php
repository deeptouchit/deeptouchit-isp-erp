<!-- PPPoE Credentials Modal Component -->
<div x-show="pppoeModal" 
     x-cloak 
     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">

    <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-md overflow-hidden" 
         @click.away="pppoeModal = false"
         x-transition:enter="transition ease-out duration-200 transform"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150 transform"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">

        <!-- Modal Soft Natural Header -->
        <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-sky-50 text-sky-600 border border-sky-100 flex items-center justify-center text-xs flex-shrink-0">
                    <i class="fas fa-network-wired"></i>
                </div>
                <div>
                    <h4 class="text-xs font-semibold text-slate-800">{{ __('Update PPPoE Credentials') }}</h4>
                    <p class="text-[10.5px] text-slate-500 font-normal">MikroTik Router &amp; FreeRADIUS AAA Sync</p>
                </div>
            </div>
            <button type="button" @click="pppoeModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center">
                <i class="fas fa-times text-xs"></i>
            </button>
        </div>

        <!-- Form Body -->
        <form @submit.prevent="submitPppoeUpdate()" class="p-4 space-y-3.5">
            <!-- Sync Info Notice Box -->
            <div class="p-2.5 rounded-lg bg-sky-50/60 border border-sky-100/80 flex items-start gap-2">
                <i class="fas fa-info-circle text-sky-600 text-xs mt-0.5 flex-shrink-0"></i>
                <p class="text-[11px] text-sky-900 leading-tight">
                    <strong>{{ __('Note') }}:</strong> {{ __('Modifying credentials here will instantly update the secret on the MikroTik router and FreeRADIUS server.') }}
                </p>
            </div>

            <!-- Username Field -->
            <div class="space-y-1">
                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider">{{ __('PPPoE Username') }}</label>
                <input type="text" 
                       x-model="pppoeForm.username" 
                       required
                       placeholder="{{ __('PPPoE Username') }}"
                       class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-sky-500 font-mono font-bold text-slate-800 transition">
            </div>

            <!-- Password Field with Generator & Toggle -->
            <div class="space-y-1" x-data="{ showPass: false }">
                <div class="flex items-center justify-between">
                    <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider">{{ __('Password') }}</label>
                    <button type="button" 
                            @click="generateRandomPassword()" 
                            class="text-[10.5px] font-semibold text-sky-600 hover:text-sky-700 flex items-center gap-1 cursor-pointer">
                        <i class="fas fa-sync-alt text-[9px]"></i> {{ __('Generate Password') }}
                    </button>
                </div>
                <div class="relative flex items-center">
                    <input :type="showPass ? 'text' : 'password'" 
                       x-model="pppoeForm.password" 
                       required
                       placeholder="{{ __('Password') }}"
                       class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs pl-3 pr-9 py-1.5 focus:bg-white focus:border-sky-500 font-mono font-bold text-slate-800 transition">
                    <button type="button" 
                            @click="showPass = !showPass" 
                            class="absolute right-2 text-slate-400 hover:text-slate-600 p-1 text-xs cursor-pointer">
                        <i class="fas" :class="showPass ? 'fa-eye-slash' : 'fa-eye'"></i>
                    </button>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="pt-3 border-t border-slate-200 flex items-center justify-end gap-2">
                <button type="button" 
                        @click="pppoeModal = false" 
                        class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                    {{ __('Cancel') }}
                </button>
                <button type="submit" 
                        :disabled="isPppoeLoading" 
                        class="bg-sky-600 hover:bg-sky-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                    <i class="fas" :class="isPppoeLoading ? 'fa-spinner fa-spin' : 'fa-save'"></i>
                    <span>{{ __('Update PPPoE') }}</span>
                </button>
            </div>
        </form>

    </div>
</div>
