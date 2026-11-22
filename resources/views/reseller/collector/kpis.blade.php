<div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
    @foreach($cards as [$label, $key, $icon, $money])
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between gap-2">
            <div class="min-w-0">
                <span class="saas-kpi-label block truncate text-slate-500">{{ __($label) }}</span>
                <span class="saas-kpi-value block text-slate-800">
                    @if($money)
                        @currency($stats[$key])
                    @else
                        {{ number_format($stats[$key]) }}
                    @endif
                </span>
            </div>
            <span class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 border-cyan-100 bg-cyan-50 text-cyan-700 flex items-center justify-center">
                <i class="fas {{ $icon }}" aria-hidden="true"></i>
            </span>
        </div>
    @endforeach
</div>
