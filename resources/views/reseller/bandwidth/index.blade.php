@extends('reseller.layouts.app')

@section('title', __('Bandwidth Usage') . ' - ' . $reseller->name)

@section('content')
<div class="space-y-3" x-data="bandwidthMonitor()" @resize.window.debounce.150ms="draw()" @visibilitychange.document="resume()">
    <div class="flex flex-wrap items-center justify-between gap-2 bg-white p-3 rounded-xl border border-slate-200 shadow-xs">
        <h1 class="flex items-center gap-2.5 text-sm font-bold text-slate-800">
            <span class="w-8 h-8 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 inline-flex items-center justify-center"><i class="fas fa-chart-line" aria-hidden="true"></i></span>
            {{ __('Bandwidth Usage') }}
        </h1>
        <div class="flex flex-wrap gap-2 text-xs font-semibold">
            <button type="button" @click="refresh()" :disabled="loading" class="h-[34px] px-3 rounded-lg border border-slate-200 bg-slate-50 disabled:opacity-50">
                <i class="fas fa-rotate" :class="{ 'fa-spin': loading }" aria-hidden="true"></i> {{ __('Refresh') }}
            </button>
            <a href="{{ route('reseller.bandwidth.export') }}" class="h-[34px] px-3 rounded-lg border border-slate-200 bg-slate-50 inline-flex items-center gap-1.5"><i class="fas fa-file-csv" aria-hidden="true"></i>{{ __('Export CSV') }}</a>
            <a href="{{ route('reseller.bandwidth.print') }}" target="_blank" rel="noopener" class="h-[34px] px-3 rounded-lg border border-slate-200 bg-slate-50 inline-flex items-center gap-1.5"><i class="fas fa-print" aria-hidden="true"></i>{{ __('Print') }}</a>
            <button type="button" @click="openUpgradeModal = true" class="h-[34px] px-3 rounded-lg bg-cyan-600 hover:bg-cyan-700 text-white"><i class="fas fa-bolt" aria-hidden="true"></i> {{ __('Request Bandwidth Upgrade') }}</button>
        </div>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        @foreach([
            ['Allocated Bandwidth', 'fa-layer-group', $bandwidth?->formatted_total_bandwidth, null],
            ['Download Speed', 'fa-arrow-down', null, "formatSpeed(reading.current_download)"],
            ['Upload Speed', 'fa-arrow-up', null, "formatSpeed(reading.current_upload)"],
            ['Utilization', 'fa-gauge-high', null, "reading.utilization_percent === null ? '—' : readingsFormat.format(reading.utilization_percent) + '%'"],
            ['Peak Download', 'fa-chart-line', null, "formatSpeed(peakDownload())"],
            ['Line Status', 'fa-network-wired', $bandwidth?->status_badge['label'], null],
        ] as [$label, $icon, $value, $binding])
            <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between gap-2">
                <div class="min-w-0">
                    <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">{{ __($label) }}</span>
                    <span class="text-[13px] font-bold font-mono leading-tight block truncate text-slate-800" @if($binding) x-text="{{ $binding }}" @endif>{{ $value ?? '—' }}</span>
                </div>
                <span class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 border-cyan-100 bg-cyan-50 text-cyan-700 inline-flex items-center justify-center"><i class="fas {{ $icon }}" aria-hidden="true"></i></span>
            </div>
        @endforeach
    </div>

    <section class="bg-white border border-slate-200 rounded-xl shadow-xs p-3 space-y-3" aria-labelledby="bandwidth-chart-heading">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <h2 id="bandwidth-chart-heading" class="text-xs font-bold text-slate-800">{{ __('Live Bandwidth Traffic') }}</h2>
                <p class="text-[11px] text-slate-500">{{ __('Last 60 measurements from this page') }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2 text-[11px]">
                <span role="status" class="rounded-md px-2 py-1 font-semibold" :class="state === 'live' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-800'" x-text="states[state]"></span>
                <time x-show="reading.timestamp" :datetime="reading.timestamp" x-text="formatTime(reading.timestamp)" class="font-mono text-slate-500"></time>
            </div>
        </div>
        <div class="flex items-center gap-4 text-[11px] text-slate-600">
            <span><i class="fas fa-circle text-cyan-600" aria-hidden="true"></i> {{ __('Download') }}</span>
            <span><i class="fas fa-circle text-violet-600" aria-hidden="true"></i> {{ __('Upload') }}</span>
        </div>
        <canvas x-ref="chart" class="block w-full h-52" role="img" aria-label="{{ __('Live Bandwidth Traffic') }}"></canvas>
    </section>

    <section class="bg-white border border-slate-200 rounded-xl shadow-xs overflow-hidden" aria-labelledby="bandwidth-allocation-heading">
        <div class="flex flex-wrap items-center justify-between gap-2 p-3 border-b border-slate-100">
            <h2 id="bandwidth-allocation-heading" class="text-xs font-bold text-slate-800">{{ __('Bandwidth Allocation') }}</h2>
            @if($bandwidth)
                <span class="text-xs text-slate-600">{{ $bandwidth->allocation_type_badge['label'] }} · <strong>@currency($bandwidth->monthly_bill_amount)</strong> / {{ __('Month') }}</span>
            @endif
        </div>
        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th scope="col" class="w-10 text-center font-semibold no-sort">#</th>
                        <th scope="col" class="text-center font-semibold">{{ __('Pipeline') }}</th>
                        <th scope="col" class="text-center font-semibold">{{ __('Allocated Bandwidth') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pipelines as $pipeline => $mbps)
                        <tr>
                            <td class="text-center font-mono">{{ $loop->iteration }}</td>
                            <td class="text-left">{{ __($pipeline) }}</td>
                            <td class="text-center font-mono" data-sort-value="{{ $mbps }}">{{ __(':speed Mbps', ['speed' => number_format($mbps, 2)]) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center py-8">
                                <i class="fas fa-network-wired text-cyan-600 mb-2" aria-hidden="true"></i>
                                <p class="font-semibold text-slate-700">{{ __('No bandwidth allocation configured.') }}</p>
                                <button type="button" @click="openUpgradeModal = true" class="mt-2 text-xs font-semibold text-cyan-700 hover:underline">{{ __('Request Bandwidth Upgrade') }}</button>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- 7. REQUEST BANDWIDTH UPGRADE MODAL (AGENTS.md Rule 3: Natural Soft Modal) --}}
    <div x-show="openUpgradeModal"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50"
         style="display: none;"
         x-cloak>

        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden"
             @click.outside="openUpgradeModal = false">

            <!-- Soft Header -->
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-purple-50 text-purple-600 border border-purple-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">{{ __('Request Bandwidth Upgrade') }}</h3>
                    </div>
                </div>
                <button type="button" @click="openUpgradeModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <!-- Body Form -->
            <form action="{{ route('reseller.bandwidth.request-upgrade') }}" method="POST">
                @csrf
                <div class="p-4 space-y-3">
                    @if($errors->any())
                        <ul class="text-xs text-rose-700 list-disc pl-4" role="alert">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    @endif
                    <!-- Upgrade Type & Requested Mbps -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="upgrade_type" class="block text-[11px] font-semibold text-slate-700 mb-1">
                                {{ __('Upgrade Type') }} <span class="text-rose-500">*</span>
                            </label>
                            <select id="upgrade_type" name="upgrade_type" required class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-purple-500 shadow-2xs">
                                <option value="additional_capacity">{{ __('Permanent Capacity (+Mbps)') }}</option>
                                <option value="emergency_boost">{{ __('Emergency 24H Boost') }}</option>
                                <option value="change_cir_ratio">{{ __('Increase CIR 1:1 Ratio') }}</option>
                                <option value="temporary_addon">{{ __('Temporary Festive Add-On') }}</option>
                            </select>
                        </div>
                        <div>
                            <label for="requested_mbps" class="block text-[11px] font-semibold text-slate-700 mb-1">
                                {{ __('Additional Capacity (Mbps)') }} <span class="text-rose-500">*</span>
                            </label>
                            <input type="number"
                                   id="requested_mbps"
                                   name="requested_mbps"
                                   min="5"
                                   step="5"
                                   value="50"
                                   required
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-purple-500 shadow-2xs">
                        </div>
                    </div>

                    <!-- Target Pipeline & Effective Date -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="target_pipeline" class="block text-[11px] font-semibold text-slate-700 mb-1">
                                {{ __('Target Pipeline') }} <span class="text-rose-500">*</span>
                            </label>
                            <select id="target_pipeline" name="target_pipeline" required class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-purple-500 shadow-2xs">
                                <option value="total_pool">{{ __('Total Combined Pool') }}</option>
                                <option value="global_internet">{{ __('Global Internet CIR') }}</option>
                                <option value="bdix_local">{{ __('BDIX National Exchange') }}</option>
                                <option value="cdn_cache">{{ __('CDN & Video Cache') }}</option>
                            </select>
                        </div>
                        <div>
                            <label for="effective_date" class="block text-[11px] font-semibold text-slate-700 mb-1">
                                {{ __('Preferred Effective Date') }}
                            </label>
                            <input type="date"
                                   id="effective_date"
                                   name="effective_date"
                                   value="{{ date('Y-m-d') }}"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-purple-500 shadow-2xs">
                        </div>
                    </div>

                    <!-- Notes -->
                    <div>
                        <label for="upgrade_notes" class="block text-[11px] font-semibold text-slate-700 mb-1">
                            {{ __('Operational Notes / Requirements') }}
                        </label>
                        <textarea id="upgrade_notes"
                                  name="notes"
                                  rows="3"
                                  class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-purple-500 shadow-2xs"></textarea>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" @click="openUpgradeModal = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                        {{ __('Cancel') }}
                    </button>
                    <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition cursor-pointer">
                        {{ __('Submit Upgrade Request') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function bandwidthMonitor() {
    const endpoint = @js(route('reseller.bandwidth.traffic'));
    const locale = @js(app()->getLocale());
    const timeFormat = new Intl.DateTimeFormat(locale, { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    const emptyReading = { current_download: null, current_upload: null, utilization_percent: null, timestamp: null };
    let timer;
    let request;

    return {
        openUpgradeModal: @js($errors->any()),
        loading: false,
        state: 'sampling',
        states: @js([
            'sampling' => __('Collecting router measurements'),
            'live' => __('Live'),
            'unavailable' => __('Router telemetry unavailable'),
            'unconfigured' => __('Live telemetry is not configured'),
        ]),
        reading: emptyReading,
        readingsFormat: new Intl.NumberFormat(locale, { maximumFractionDigits: 2 }),
        points: [],

        init() {
            this.$nextTick(() => this.draw());
            this.refresh();
        },

        destroy() {
            clearTimeout(timer);
            request?.abort();
        },

        resume() {
            if (!document.hidden) this.refresh();
        },

        formatTime(timestamp) {
            return timestamp ? timeFormat.format(new Date(timestamp)) : '';
        },

        formatSpeed(value) {
            return value === null ? '—' : @js(__(':speed Mbps')).replace(':speed', this.readingsFormat.format(value));
        },

        peakDownload() {
            return this.points.length ? Math.max(...this.points.map(point => point.current_download)) : null;
        },

        async refresh() {
            if (this.loading || document.hidden) return;
            clearTimeout(timer);
            this.loading = true;
            request = new AbortController();
            try {
                const response = await fetch(endpoint, {
                    headers: { Accept: 'application/json' },
                    cache: 'no-store',
                    signal: request.signal,
                });
                if (!response.ok) throw new Error(response.status);
                const reading = await response.json();
                this.state = reading.state;
                this.reading = reading;
                if (reading.timestamp && this.points.at(-1)?.timestamp !== reading.timestamp) {
                    this.points = [...this.points, reading].slice(-60);
                    this.draw();
                }
            } catch (error) {
                if (error.name === 'AbortError') return;
                this.state = 'unavailable';
                this.reading = emptyReading;
            } finally {
                this.loading = false;
                if (!request.signal.aborted) timer = setTimeout(() => this.refresh(), this.state === 'live' || this.state === 'sampling' ? 6000 : 15000);
            }
        },

        draw() {
            const canvas = this.$refs.chart;
            const { width, height } = canvas.getBoundingClientRect();
            if (!width || !height) return;
            const scale = window.devicePixelRatio || 1;
            canvas.width = width * scale;
            canvas.height = height * scale;
            const context = canvas.getContext('2d');
            context.scale(scale, scale);
            context.font = '11px system-ui';
            const left = 56;
            const right = width - 14;
            const bottom = height - 24;
            const top = 10;
            const max = Math.max(1, ...this.points.flatMap(point => [point.current_download, point.current_upload]));

            for (let i = 0; i <= 4; i++) {
                const fraction = i / 4;
                const y = bottom - fraction * (bottom - top);
                context.strokeStyle = '#e2e8f0';
                context.beginPath();
                context.moveTo(left, y);
                context.lineTo(right, y);
                context.stroke();
                context.fillStyle = '#475569';
                context.textAlign = 'right';
                context.fillText(this.readingsFormat.format(max * fraction), left - 6, y + 4);
            }
            if (!this.points.length) return;
            const start = Date.parse(this.points[0].timestamp);
            const end = Date.parse(this.points.at(-1).timestamp);

            for (const [field, color] of [['current_download', '#0891b2'], ['current_upload', '#7c3aed']]) {
                context.strokeStyle = color;
                context.fillStyle = color;
                context.lineWidth = 2;
                context.beginPath();
                let previous = null;
                for (const point of this.points) {
                    const time = Date.parse(point.timestamp);
                    const x = left + (time - start) / Math.max(1, end - start) * (right - left);
                    const y = bottom - point[field] / max * (bottom - top);
                    if (previous === null || time - previous > 15000) context.moveTo(x, y);
                    else context.lineTo(x, y);
                    if (this.points.length === 1) context.fillRect(x - 2, y - 2, 4, 4);
                    previous = time;
                }
                context.stroke();
            }

            context.fillStyle = '#475569';
            context.textAlign = 'left';
            context.fillText(this.formatTime(this.points[0].timestamp), left, height - 6);
            if (end !== start) {
                context.textAlign = 'right';
                context.fillText(this.formatTime(this.points.at(-1).timestamp), right, height - 6);
            }
        }
    };
}
</script>
@endpush
