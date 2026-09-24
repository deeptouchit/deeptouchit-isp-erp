<!-- Activity Log Details Modal Component (Production-Grade Natural Modal) -->
<div x-show="logDetailsModal" 
     x-cloak 
     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">

    <div class="bg-white text-slate-800 rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden" 
         @click.away="logDetailsModal = false"
         x-transition:enter="transition ease-out duration-200 transform"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150 transform"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">

        <!-- Natural Soft Header -->
        <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <div class="w-7 h-7 rounded-lg bg-purple-50 text-purple-600 border border-purple-100 flex items-center justify-center text-xs flex-shrink-0 shadow-2xs">
                    <i class="fas fa-history"></i>
                </div>
                <div>
                    <h4 class="text-xs font-semibold text-slate-800">{{ __('Activity & Audit Log Details') }}</h4>
                    <p class="text-[10.5px] text-slate-500 font-normal">
                        {{ __('ID') }}: <span class="font-mono font-bold text-slate-700" x-text="'#' + (selectedLog.id || '0')"></span> &bull; 
                        {{ __('Timestamp') }}: <span class="font-mono text-slate-600" x-text="selectedLog.created_at || '--'"></span>
                    </p>
                </div>
            </div>
            <button type="button" 
                    @click="logDetailsModal = false" 
                    class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center">
                <i class="fas fa-times text-xs"></i>
            </button>
        </div>

        <!-- Body -->
        <div class="p-4 space-y-3.5 text-xs max-h-[75vh] overflow-y-auto">
            
            <!-- Metadata Grid -->
            <div class="grid grid-cols-2 gap-2.5 p-3 rounded-lg bg-slate-50 border border-slate-200/80">
                <div>
                    <span class="text-[10px] uppercase font-semibold text-slate-400 block tracking-wider">{{ __('Event Type') }}</span>
                    <span class="inline-flex items-center px-2 py-0.5 mt-0.5 rounded text-[10px] font-mono font-bold uppercase bg-purple-50 text-purple-700 border border-purple-200"
                          x-text="selectedLog.event_type || 'ACTIVITY'">
                    </span>
                </div>
                <div>
                    <span class="text-[10px] uppercase font-semibold text-slate-400 block tracking-wider">{{ __('Performed By') }}</span>
                    <strong class="text-slate-800 text-[11px]" x-text="selectedLog.actor_name || 'System Admin'"></strong>
                    <span class="text-[10px] text-slate-500 block font-mono" x-text="selectedLog.actor_type ? '(' + selectedLog.actor_type + ')' : ''"></span>
                </div>
                <div>
                    <span class="text-[10px] uppercase font-semibold text-slate-400 block tracking-wider">{{ __('IP Address') }}</span>
                    <span class="font-mono text-slate-700 text-[11px]" x-text="selectedLog.ip_address || '127.0.0.1'"></span>
                </div>
                <div>
                    <span class="text-[10px] uppercase font-semibold text-slate-400 block tracking-wider">{{ __('Logged At') }}</span>
                    <span class="font-mono text-slate-700 text-[11px]" x-text="selectedLog.created_at || 'N/A'"></span>
                </div>
            </div>

            <!-- Full Activity Description -->
            <div class="space-y-1">
                <span class="text-[10.5px] font-semibold text-slate-700 block">{{ __('Activity Description') }}:</span>
                <div class="p-3 bg-white rounded-lg border border-slate-200 text-xs text-slate-800 leading-relaxed font-normal select-text whitespace-pre-wrap shadow-2xs"
                     x-text="selectedLog.description">
                </div>
            </div>

            <!-- User Agent -->
            <template x-if="selectedLog.user_agent && selectedLog.user_agent !== 'N/A'">
                <div class="space-y-1">
                    <span class="text-[10px] font-medium text-slate-500 block">{{ __('User Agent') }}:</span>
                    <p class="text-[10px] font-mono text-slate-600 bg-slate-50 p-2 rounded border border-slate-200 break-all select-text leading-tight" 
                       x-text="selectedLog.user_agent"></p>
                </div>
            </template>

            <!-- Metadata JSON (if any) -->
            <template x-if="selectedLog.metadata && typeof selectedLog.metadata === 'object' && Object.keys(selectedLog.metadata).length > 0">
                <div class="space-y-1">
                    <span class="text-[10px] font-medium text-slate-500 block">{{ __('Payload / Metadata') }}:</span>
                    <pre class="bg-slate-900 text-cyan-300 font-mono text-[10.5px] p-3 rounded-lg border border-slate-800 overflow-x-auto select-text max-h-48" 
                         x-text="JSON.stringify(selectedLog.metadata, null, 2)"></pre>
                </div>
            </template>

        </div>

        <!-- Modal Footer -->
        <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end">
            <button type="button" 
                    @click="logDetailsModal = false" 
                    class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-4 py-1.5 rounded-lg transition shadow-2xs cursor-pointer">
                {{ __('Close') }}
            </button>
        </div>

    </div>
</div>
