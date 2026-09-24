<!-- RX/TX Live Real-time Traffic Graph Modal Component (Natural White Modern Design) -->
<div x-show="rxtxModal" 
     x-cloak 
     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">

    <div class="bg-white text-slate-800 rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-xl overflow-hidden" 
         @click.away="closeRxtxModal()"
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
                    <i class="fas fa-chart-line"></i>
                </div>
                <div>
                    <h4 class="text-xs font-semibold text-slate-800">{{ __('Real-time Bandwidth Monitor') }}</h4>
                    <p class="text-[10.5px] text-slate-500 font-normal">
                        {{ __('User') }}: <span class="font-mono font-bold text-slate-700" x-text="($data.rxtxCustomer && $data.rxtxCustomer.username) ? $data.rxtxCustomer.username : '{{ $customer->username ?? 'PPPoE Client' }}'">{{ $customer->username ?? 'PPPoE Client' }}</span> &bull; 
                        {{ __('ID') }}: <span class="font-mono text-slate-600" x-text="($data.rxtxCustomer && ($data.rxtxCustomer.customer_id || $data.rxtxCustomer.id)) ? ($data.rxtxCustomer.customer_id || ('CUST-' + $data.rxtxCustomer.id)) : '{{ $customer->customer_id ?? ('CUST-' . ($customer->id ?? '')) }}'">{{ $customer->customer_id ?? ('CUST-' . ($customer->id ?? '')) }}</span>
                    </p>
                </div>
            </div>
            
            <div class="flex items-center gap-2">
                <!-- Live Pulse Pill -->
                <div class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[9.5px] font-bold tracking-wider uppercase border"
                     :class="traffic.is_online ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'">
                    <span class="w-1.5 h-1.5 rounded-full" :class="traffic.is_online ? 'bg-emerald-500 animate-pulse' : 'bg-rose-500'"></span>
                    <span x-text="traffic.is_online ? '{{ __('Online') }}' : '{{ __('Offline') }}'"></span>
                </div>

                <button type="button" 
                        @click="closeRxtxModal()" 
                        class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>
        </div>

        <!-- Body & Live Canvas Graph -->
        <div class="p-4 space-y-3.5">

            <!-- Chart Surface Container -->
            <div class="relative bg-slate-50/70 rounded-xl border border-slate-200/90 p-3 overflow-hidden">
                <!-- Watermark Legend -->
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[10.5px] font-bold text-slate-600 uppercase tracking-wider">{{ __('MikroTik RouterOS Live Waveform') }}</span>
                    <div class="flex items-center gap-3 text-[10px] font-mono font-bold">
                        <div class="flex items-center gap-1 text-purple-700">
                            <span class="w-2.5 h-2.5 rounded-xs bg-purple-600"></span>
                            <span>{{ __('Download (RX)') }}: <strong x-text="traffic.rx_human">0.00 Mbps</strong></span>
                        </div>
                        <div class="flex items-center gap-1 text-emerald-700">
                            <span class="w-2.5 h-2.5 rounded-xs bg-emerald-600"></span>
                            <span>{{ __('Upload (TX)') }}: <strong x-text="traffic.tx_human">0.00 Mbps</strong></span>
                        </div>
                    </div>
                </div>

                <!-- Canvas -->
                <canvas id="rxtxLiveCanvas" class="w-full h-36 rounded-lg bg-slate-900 border border-slate-800"></canvas>
            </div>

            <!-- Interface Telemetry 4-Column Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs font-mono">
                <div class="p-2 rounded-lg bg-slate-50 border border-slate-200/80">
                    <div class="text-[9.5px] text-slate-400 uppercase font-sans font-medium">{{ __('Interface') }}</div>
                    <div class="font-bold text-slate-800 truncate" :title="'<pppoe-' + (($data.rxtxCustomer && $data.rxtxCustomer.username) ? $data.rxtxCustomer.username : '{{ $customer->username ?? '' }}') + '>'">
                        &lt;pppoe-<span x-text="($data.rxtxCustomer && $data.rxtxCustomer.username) ? $data.rxtxCustomer.username : '{{ $customer->username ?? '' }}'">{{ $customer->username ?? '' }}</span>&gt;
                    </div>
                </div>
                <div class="p-2 rounded-lg bg-slate-50 border border-slate-200/80">
                    <div class="text-[9.5px] text-slate-400 uppercase font-sans font-medium">{{ __('Assigned IP') }}</div>
                    <div class="font-bold text-cyan-700 truncate" x-text="traffic.ip || '--'">--</div>
                </div>
                <div class="p-2 rounded-lg bg-slate-50 border border-slate-200/80">
                    <div class="text-[9.5px] text-slate-400 uppercase font-sans font-medium">{{ __('Session Uptime') }}</div>
                    <div class="font-bold text-emerald-700 truncate" x-text="traffic.uptime || '{{ __('Offline') }}'">--</div>
                </div>
                <div class="p-2 rounded-lg bg-slate-50 border border-slate-200/80">
                    <div class="text-[9.5px] text-slate-400 uppercase font-sans font-medium">{{ __('Caller MAC') }}</div>
                    <div class="font-bold text-slate-700 truncate" x-text="traffic.mac || '--'">--</div>
                </div>
            </div>

            <!-- Digital Speed Cards (RX & TX) -->
            <div class="grid grid-cols-2 gap-2.5">
                <!-- RX Card -->
                <div class="p-3 rounded-xl bg-emerald-50/70 border border-emerald-200/80 text-center">
                    <div class="flex items-center justify-center gap-1 text-[10.5px] font-bold uppercase tracking-wider text-emerald-800">
                        <i class="fas fa-arrow-down text-[9.5px]"></i> {{ __('Download Speed (RX)') }}
                    </div>
                    <div class="text-xl font-bold font-mono text-emerald-900 leading-tight mt-1" x-text="traffic.rx_human">
                        0.00 Mbps
                    </div>
                    <div class="text-[10px] font-mono text-emerald-700 mt-0.5">
                        {{ __('Total') }}: <span class="font-bold" x-text="traffic.total_rx_human || '0 B'"></span>
                    </div>
                </div>

                <!-- TX Card -->
                <div class="p-3 rounded-xl bg-purple-50/70 border border-purple-200/80 text-center">
                    <div class="flex items-center justify-center gap-1 text-[10.5px] font-bold uppercase tracking-wider text-purple-800">
                        <i class="fas fa-arrow-up text-[9.5px]"></i> {{ __('Upload Speed (TX)') }}
                    </div>
                    <div class="text-xl font-bold font-mono text-purple-900 leading-tight mt-1" x-text="traffic.tx_human">
                        0.00 Mbps
                    </div>
                    <div class="text-[10px] font-mono text-purple-700 mt-0.5">
                        {{ __('Total') }}: <span class="font-bold" x-text="traffic.total_tx_human || '0 B'"></span>
                    </div>
                </div>
            </div>

            <!-- Footer Controls -->
            <div class="pt-3 border-t border-slate-200 flex items-center justify-between">
                <button type="button" 
                        @click="togglePolling()" 
                        class="px-3 py-1.5 rounded-lg border text-xs font-semibold transition flex items-center gap-1.5 shadow-2xs"
                        :class="isLivePolling ? 'border-amber-200 bg-amber-50 text-amber-800 hover:bg-amber-100' : 'border-emerald-200 bg-emerald-50 text-emerald-800 hover:bg-emerald-100'">
                    <i class="fas" :class="isLivePolling ? 'fa-pause' : 'fa-play'"></i>
                    <span x-text="isLivePolling ? '{{ __('Pause Monitor') }}' : '{{ __('Resume Live') }}'"></span>
                </button>

                <div class="flex items-center gap-2">
                    <button type="button" 
                            @click="fetchTrafficPoint()" 
                            class="px-3 py-1.5 rounded-lg border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-700 text-xs font-semibold transition flex items-center gap-1.5 shadow-2xs">
                        <i class="fas fa-arrows-rotate text-[10px]"></i> {{ __('Refresh') }}
                    </button>
                    <button type="button" 
                            @click="closeRxtxModal()" 
                            class="px-3.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium text-xs transition">
                        {{ __('Close') }}
                    </button>
                </div>
            </div>

        </div>

    </div>
</div>
