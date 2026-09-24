<!-- Modal 12: Note for Customer Modal -->
<div x-show="noteModal" 
     x-transition:enter="transition ease-out duration-150"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-100"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4" 
     style="display: none;">
    
    <div @click.away="noteModal = false" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="transform opacity-0 scale-95"
         x-transition:enter-end="transform opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="transform opacity-100 scale-100"
         x-transition:leave-end="transform opacity-0 scale-95"
         class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden">
        
        <!-- Modal Header -->
        <div class="px-4 py-3 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <div class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 border border-indigo-100 flex items-center justify-center text-xs flex-shrink-0">
                    <i class="fas fa-clipboard"></i>
                </div>
                <div>
                    <h3 class="text-xs font-semibold text-slate-800">{{ __('Customer Note & Remarks') }}</h3>
                    <p class="text-[10.5px] text-slate-500 font-normal">{{ __('Internal operational logs and special instructions') }}</p>
                </div>
            </div>
            <button type="button" @click="noteModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center text-xs cursor-pointer">
                <i class="fas fa-xmark"></i>
            </button>
        </div>

        <!-- Modal Form -->
        <form @submit.prevent="submitCustomerNote()" class="p-4 space-y-3.5 text-xs">
            
            <!-- Quick Info Header Badge -->
            <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80 flex items-center justify-between">
                <div class="flex items-center gap-2 min-w-0">
                    <div class="w-7 h-7 rounded-md bg-indigo-100 text-indigo-700 flex items-center justify-center text-[11px] font-bold font-mono flex-shrink-0">
                        {{ substr($customer->username ?? 'CU', 0, 2) }}
                    </div>
                    <div class="min-w-0">
                        <span class="font-semibold text-slate-800 block text-xs truncate">{{ $customer->name }}</span>
                        <span class="text-[10px] text-slate-500 font-mono block truncate">{{ $customer->username }} &bull; {{ $customer->phone ?? 'N/A' }}</span>
                    </div>
                </div>
                <div class="text-right flex-shrink-0">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium {{ $customer->status === 'active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' }}">
                        {{ ucfirst($customer->status) }}
                    </span>
                </div>
            </div>

            <!-- Quick Preset Chips -->
            <div>
                <label class="block text-[10.5px] font-semibold text-slate-600 mb-1.5 uppercase tracking-wider">
                    {{ __('Quick Preset Tags:') }}
                </label>
                <div class="flex flex-wrap gap-1.5">
                    <button type="button" 
                            @click="appendPresetNote('[VIP Client] ')" 
                            class="px-2 py-1 rounded bg-slate-100 hover:bg-indigo-50 hover:text-indigo-700 hover:border-indigo-200 border border-slate-200 text-[10.5px] font-medium text-slate-600 transition cursor-pointer">
                        + {{ __('VIP Client') }}
                    </button>
                    <button type="button" 
                            @click="appendPresetNote('[Special Discount] ')" 
                            class="px-2 py-1 rounded bg-slate-100 hover:bg-indigo-50 hover:text-indigo-700 hover:border-indigo-200 border border-slate-200 text-[10.5px] font-medium text-slate-600 transition cursor-pointer">
                        + {{ __('Special Discount') }}
                    </button>
                    <button type="button" 
                            @click="appendPresetNote('[Call Before Cut-off] ')" 
                            class="px-2 py-1 rounded bg-slate-100 hover:bg-indigo-50 hover:text-indigo-700 hover:border-indigo-200 border border-slate-200 text-[10.5px] font-medium text-slate-600 transition cursor-pointer">
                        + {{ __('Call Before Cut-off') }}
                    </button>
                    <button type="button" 
                            @click="appendPresetNote('[ONU/Router: ] ')" 
                            class="px-2 py-1 rounded bg-slate-100 hover:bg-indigo-50 hover:text-indigo-700 hover:border-indigo-200 border border-slate-200 text-[10.5px] font-medium text-slate-600 transition cursor-pointer">
                        + {{ __('Router Info') }}
                    </button>
                    <button type="button" 
                            @click="appendPresetNote('[Promise Date: ] ')" 
                            class="px-2 py-1 rounded bg-slate-100 hover:bg-indigo-50 hover:text-indigo-700 hover:border-indigo-200 border border-slate-200 text-[10.5px] font-medium text-slate-600 transition cursor-pointer">
                        + {{ __('Payment Promise') }}
                    </button>
                </div>
            </div>

            <!-- Note Textarea -->
            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-[11px] font-medium text-slate-700">
                        {{ __('Internal Note / Remarks') }}
                    </label>
                    <span class="text-[10px] text-slate-400 font-mono" x-text="`${(noteForm.remarks || '').length}/2000`"></span>
                </div>
                <textarea x-model="noteForm.remarks" 
                          rows="5" 
                          maxlength="2000"
                          placeholder="{{ __('Type customer-specific operational notes, equipment details, or billing instructions...') }}"
                          class="w-full p-2.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-200 focus:outline-hidden transition leading-relaxed"></textarea>
                <span class="text-[10.5px] text-slate-400 mt-1 block">
                    <i class="fas fa-lock text-[9px] mr-1"></i> {{ __('Private internal note (not visible to customer).') }}
                </span>
            </div>

            <!-- Footer Buttons -->
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 -mx-4 -mb-4 flex items-center justify-between">
                <button type="button" 
                        @click="noteForm.remarks = ''" 
                        class="text-[11px] text-rose-500 hover:text-rose-700 hover:underline cursor-pointer">
                    {{ __('Clear Text') }}
                </button>
                <div class="flex items-center gap-2">
                    <button type="button" 
                            @click="noteModal = false" 
                            class="px-3.5 py-1.5 rounded-lg border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs transition cursor-pointer">
                        {{ __('Cancel') }}
                    </button>
                    <button type="submit" 
                            :disabled="isNoteLoading" 
                            class="px-4 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white font-medium text-xs shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                        <i class="fas" :class="isNoteLoading ? 'fa-spinner fa-spin' : 'fa-save'"></i>
                        <span x-text="isNoteLoading ? '{{ __('Saving...') }}' : '{{ __('Save Note') }}'"></span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
