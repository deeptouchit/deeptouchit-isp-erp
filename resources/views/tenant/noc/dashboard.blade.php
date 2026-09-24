@extends('tenant.layouts.app')

@section('title', 'NOC & Field Hub - ' . ($tenant->company_name ?? 'ISP Management'))

@push('styles')
    {{-- Page Specific Styles --}}
@endpush

@section('content')
<div class="space-y-3" x-data="nocManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">
    
    {{-- 1. TOP HEADER BAR (AGENTS.md Rule 2.A: Icon + Title + Action buttons ONLY) --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 bg-white px-4 py-2.5 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-sm shadow-2xs flex-shrink-0">
                <i class="fas fa-screwdriver-wrench"></i>
            </div>
            <h1 class="text-sm font-bold text-slate-800 tracking-tight">{{ __('NOC & Field Engineering Hub') }}</h1>
        </div>
        <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
            <a href="{{ route('tenant.noc.installations') }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition inline-flex items-center gap-1.5 shadow-2xs cursor-pointer">
                <i class="fas fa-network-wired text-cyan-600 text-xs"></i>
                <span>{{ __('New Installations') }}</span>
            </a>
            <a href="{{ route('tenant.noc.tickets') }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition inline-flex items-center gap-1.5 shadow-2xs cursor-pointer">
                <i class="fas fa-headset text-amber-600 text-xs"></i>
                <span>{{ __('Support Tickets') }}</span>
            </a>
            <button type="button" @click="openSignalModal()" class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-wave-square text-xs"></i>
                <span>{{ __('Optical Signal Test') }}</span>
            </button>
        </div>
    </div>

    {{-- 2. KPI SUMMARY STRIP (Strictly 6 Cards - AGENTS.md Rule 2.B) --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Pending Install') }}</span>
                <span class="text-[13px] font-bold font-mono text-cyan-600 leading-tight block truncate">{{ number_format($stats['pending_installations']) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-cyan-200 bg-cyan-50 text-cyan-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-satellite-dish"></i>
            </div>
        </div>

        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Assigned Tickets') }}</span>
                <span class="text-[13px] font-bold font-mono text-amber-600 leading-tight block truncate">{{ number_format($stats['assigned_tickets']) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-amber-200 bg-amber-50 text-amber-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-ticket"></i>
            </div>
        </div>

        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Resolved Today') }}</span>
                <span class="text-[13px] font-bold font-mono text-emerald-600 leading-tight block truncate">{{ number_format($stats['resolved_today']) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-emerald-200 bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-circle-check"></i>
            </div>
        </div>

        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Live Active') }}</span>
                <span class="text-[13px] font-bold font-mono text-blue-700 leading-tight block truncate">{{ number_format($stats['active_sessions']) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-blue-200 bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-signal"></i>
            </div>
        </div>

        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Offline Lines') }}</span>
                <span class="text-[13px] font-bold font-mono text-rose-600 leading-tight block truncate">{{ number_format($stats['offline_customers']) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-rose-200 bg-rose-50 text-rose-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-triangle-exclamation"></i>
            </div>
        </div>

        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Total ONU/CPE') }}</span>
                <span class="text-[13px] font-bold font-mono text-purple-700 leading-tight block truncate">{{ number_format($stats['total_onus']) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-purple-200 bg-purple-50 text-purple-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-server"></i>
            </div>
        </div>
    </div>

    {{-- 3. MODULE OVERVIEW & QUICK ACTIONS --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
        
        <!-- Quick Installation Queue -->
        <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-xs space-y-2.5">
            <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                <div class="flex items-center gap-2">
                    <i class="fas fa-network-wired text-cyan-600 text-xs"></i>
                    <h3 class="text-xs font-bold text-slate-800">{{ __('Recent Installation Queue') }}</h3>
                </div>
                <a href="{{ route('tenant.noc.installations') }}" class="text-[10px] font-semibold text-cyan-600 hover:underline">
                    {{ __('View All') }} &rarr;
                </a>
            </div>

            <div class="space-y-1.5">
                @forelse($recentCustomers as $customer)
                    <div class="p-2 rounded-lg bg-slate-50 border border-slate-200/80 flex items-center justify-between text-xs">
                        <div class="min-w-0 pr-2">
                            <span class="font-bold text-slate-800 block truncate">{{ $customer->username ?? $customer->name }}</span>
                            <span class="text-[10px] text-slate-500 font-mono">{{ $customer->phone ?? 'No Phone' }} • {{ $customer->package_display_name }}</span>
                        </div>
                        <a href="{{ route('tenant.noc.installations') }}" class="px-2 py-1 rounded bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-[10.5px] transition shadow-2xs">
                            {{ __('Setup') }}
                        </a>
                    </div>
                @empty
                    <div class="py-4 text-center text-slate-400 text-xs">
                        {{ __('No pending installations in queue.') }}
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Assigned Support Tickets -->
        <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-xs space-y-2.5">
            <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                <div class="flex items-center gap-2">
                    <i class="fas fa-ticket text-amber-600 text-xs"></i>
                    <h3 class="text-xs font-bold text-slate-800">{{ __('Open Support Tickets') }}</h3>
                </div>
                <a href="{{ route('tenant.noc.tickets') }}" class="text-[10px] font-semibold text-amber-600 hover:underline">
                    {{ __('View All') }} &rarr;
                </a>
            </div>

            <div class="space-y-1.5">
                @forelse($recentTickets as $ticket)
                    <div class="p-2 rounded-lg bg-slate-50 border border-slate-200/80 flex items-center justify-between text-xs">
                        <div class="min-w-0 pr-2">
                            <span class="font-bold text-slate-800 block truncate">#{{ $ticket->ticket_number }} • {{ $ticket->subject }}</span>
                            <span class="text-[10px] text-slate-500 capitalize">{{ $ticket->priority }} priority • {{ $ticket->status }}</span>
                        </div>
                        <a href="{{ route('tenant.noc.tickets') }}" class="px-2 py-1 rounded bg-amber-600 hover:bg-amber-700 text-white font-semibold text-[10.5px] transition shadow-2xs">
                            {{ __('Handle') }}
                        </a>
                    </div>
                @empty
                    <div class="py-4 text-center text-slate-400 text-xs">
                        {{ __('No open support tickets at the moment.') }}
                    </div>
                @endforelse
            </div>
        </div>

    </div>

    {{-- 4. OPTICAL SIGNAL TEST SOFT NATURAL MODAL (AGENTS.md Rule 3) --}}
    <div x-show="showSignalModal" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50"
         style="display: none;"
         x-cloak>
        
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-md overflow-hidden" 
             @click.outside="showSignalModal = false">
            
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-wave-square"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">{{ __('Optical Signal Diagnostic') }}</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">{{ __('Test live OLT / ONU optical power (dBm)') }}</p>
                    </div>
                </div>
                <button type="button" @click="showSignalModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <div class="p-4 space-y-3 text-xs">
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                        {{ __('Select Customer / ID') }} <span class="text-rose-500">*</span>
                    </label>
                    <input type="number" x-model="signalCustomerId" placeholder="e.g. 1024" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-emerald-500 shadow-2xs font-mono">
                </div>

                <div x-show="signalResult" class="p-3 bg-slate-50 rounded-lg border border-slate-200/80 space-y-2 font-mono text-xs" style="display: none;">
                    <div class="flex justify-between border-b border-slate-200/60 pb-1">
                        <span class="text-slate-500">{{ __('Customer') }}:</span>
                        <span class="font-bold text-slate-800" x-text="signalResult?.customer_name"></span>
                    </div>
                    <div class="flex justify-between border-b border-slate-200/60 pb-1">
                        <span class="text-slate-500">{{ __('Optical RX Power') }}:</span>
                        <span class="font-bold text-emerald-600" x-text="signalResult?.rx_power"></span>
                    </div>
                    <div class="flex justify-between border-b border-slate-200/60 pb-1">
                        <span class="text-slate-500">{{ __('Optical TX Power') }}:</span>
                        <span class="font-bold text-cyan-600" x-text="signalResult?.tx_power"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">{{ __('Signal Quality') }}:</span>
                        <span class="font-bold uppercase text-slate-800" x-text="signalResult?.signal_status"></span>
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-200 flex items-center justify-end gap-2 -mx-4 -mb-4 p-3 bg-slate-50/80">
                    <button type="button" @click="showSignalModal = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                        {{ __('Close') }}
                    </button>
                    <button type="button" @click="runSignalTest()" :disabled="testingSignal" class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition cursor-pointer flex items-center gap-1.5">
                        <i class="fas fa-spinner fa-spin text-xs" x-show="testingSignal" style="display: none;"></i>
                        <span>{{ __('Run Live Test') }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function nocManager() {
    return {
        showSignalModal: false,
        signalCustomerId: '1',
        signalResult: null,
        testingSignal: false,

        openSignalModal() {
            this.showSignalModal = true;
        },

        runSignalTest() {
            this.testingSignal = true;
            fetch('{{ route("tenant.noc.signal-test") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ customer_id: this.signalCustomerId })
            })
            .then(res => res.json())
            .then(data => {
                this.testingSignal = false;
                if (data.success) {
                    this.signalResult = data;
                } else {
                    alert(data.message || 'Customer not found.');
                }
            })
            .catch(() => {
                this.testingSignal = false;
                alert('Test failed. Please check connection.');
            });
        }
    };
}
</script>
@endpush
