@extends('tenant.layouts.app')

@section('title', 'Daily Cash Handover - ' . ($tenant->company_name ?? 'ISP Management'))

@push('styles')
    {{-- Page Specific Styles --}}
@endpush

@section('content')
<div class="space-y-3" x-data="{ showHandoverModal: false }">
    
    {{-- 1. TOP HEADER BAR (AGENTS.md Rule 2.A: Icon + Title + Action buttons ONLY) --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 bg-white px-4 py-2.5 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <a href="{{ route('tenant.dashboard') }}" class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center text-xs transition shadow-2xs">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 border border-purple-100 flex items-center justify-center text-sm shadow-2xs flex-shrink-0">
                <i class="fas fa-vault"></i>
            </div>
            <h1 class="text-sm font-bold text-slate-800 tracking-tight">{{ __('Daily Cash Handover') }}</h1>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" @click="showHandoverModal = true" class="px-3.5 py-1.5 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-xs font-semibold shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-paper-plane text-xs"></i>
                <span>{{ __('Submit Cash Handover') }}</span>
            </button>
        </div>
    </div>

    {{-- 2. KPI SUMMARY STRIP (Strictly 6 Cards - AGENTS.md Rule 2.B) --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Cash in Hand') }}</span>
                <span class="text-[13px] font-bold font-mono text-amber-700 leading-tight block truncate">@currency($stats['available_cash_in_hand'])</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-amber-200 bg-amber-50 text-amber-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-wallet"></i>
            </div>
        </div>

        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Pending Approval') }}</span>
                <span class="text-[13px] font-bold font-mono text-cyan-700 leading-tight block truncate">@currency($stats['pending_approval'])</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-cyan-200 bg-cyan-50 text-cyan-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-hourglass-half"></i>
            </div>
        </div>

        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Approved Handed') }}</span>
                <span class="text-[13px] font-bold font-mono text-emerald-600 leading-tight block truncate">@currency($stats['total_approved_handed'])</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-emerald-200 bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-circle-check"></i>
            </div>
        </div>

        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Total Cash') }}</span>
                <span class="text-[13px] font-bold font-mono text-slate-800 leading-tight block truncate">@currency($stats['total_cash_collected'])</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-slate-200 bg-slate-50 text-slate-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-money-bill-transfer"></i>
            </div>
        </div>

        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Handover Count') }}</span>
                <span class="text-[13px] font-bold font-mono text-purple-700 leading-tight block truncate">{{ number_format($stats['total_handovers_count']) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-purple-200 bg-purple-50 text-purple-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-receipt"></i>
            </div>
        </div>

        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Today Collected') }}</span>
                <span class="text-[13px] font-bold font-mono text-emerald-700 leading-tight block truncate">@currency($stats['today_collected'])</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-emerald-200 bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-calendar-day"></i>
            </div>
        </div>
    </div>

    {{-- 3. MASTER COMPACT TABLE (AGENTS.md Rule 2.D: Max 5-7 Columns) --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-3 border-b border-slate-200 flex items-center justify-between bg-slate-50/50">
            <div class="flex items-center gap-2">
                <i class="fas fa-clock-rotate-left text-purple-600 text-xs"></i>
                <h3 class="text-xs font-bold text-slate-800">{{ __('My Cash Handover History') }}</h3>
                <span class="px-2 py-0.5 rounded-full bg-slate-200 text-slate-700 text-[10px] font-bold font-mono">
                    {{ $handovers->total() }}
                </span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-10 text-center">#</th>
                        <th>{{ __('Date') }}</th>
                        <th class="text-right">{{ __('Handover Amount') }}</th>
                        <th>{{ __('Notes') }}</th>
                        <th>{{ __('Verified By') }}</th>
                        <th class="text-center">{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($handovers as $index => $item)
                        <tr>
                            <td class="text-center font-mono text-slate-500">{{ $handovers->firstItem() + $index }}</td>
                            <td class="font-mono text-slate-800">
                                {{ $item->handover_date ? \Carbon\Carbon::parse($item->handover_date)->format('d M Y') : '—' }}
                            </td>
                            <td class="text-right font-mono font-bold text-slate-900">
                                @currency($item->amount)
                            </td>
                            <td class="text-slate-600 text-xs">{{ $item->notes ?: '—' }}</td>
                            <td class="font-medium text-slate-800">{{ $item->verifier?->name ?? '—' }}</td>
                            <td class="text-center">
                                @if($item->status === 'approved' || $item->status === 'verified')
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold font-mono bg-emerald-50 text-emerald-700 border border-emerald-200">{{ __('Verified') }}</span>
                                @elseif($item->status === 'pending')
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold font-mono bg-amber-50 text-amber-700 border border-amber-200">{{ __('Pending') }}</span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold font-mono bg-rose-50 text-rose-700 border border-rose-200">{{ __('Rejected') }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-6 text-slate-400">
                                <i class="fas fa-vault text-2xl mb-1.5 block text-slate-300"></i>
                                <span>{{ __('No cash handovers submitted yet.') }}</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($handovers->hasPages())
            <div class="p-3 border-t border-slate-200 bg-slate-50/50">
                {{ $handovers->links() }}
            </div>
        @endif
    </div>

    {{-- 4. SUBMIT CASH HANDOVER SOFT NATURAL MODAL (AGENTS.md Rule 3) --}}
    <div x-show="showHandoverModal" 
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
             @click.outside="showHandoverModal = false">
            
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-purple-50 text-purple-600 border border-purple-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-vault"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">{{ __('Submit Daily Cash Handover') }}</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">{{ __('Submit collected cash to Manager / Accounts') }}</p>
                    </div>
                </div>
                <button type="button" @click="showHandoverModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <form action="{{ route('tenant.collector.handover.store') }}" method="POST" class="p-4 space-y-3">
                @csrf
                <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200/80 flex items-center justify-between text-xs">
                    <span class="text-slate-600">{{ __('Current Cash in Hand') }}:</span>
                    <span class="font-bold font-mono text-amber-700">@currency($availableCashInHand)</span>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                        {{ __('Handover Amount') }} ({{ $currencySymbol ?? '৳' }}) <span class="text-rose-500">*</span>
                    </label>
                    <input type="number" name="amount" value="{{ $availableCashInHand }}" step="0.01" min="1" required class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-purple-500 shadow-2xs font-mono">
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                        {{ __('Notes / Remarks') }}
                    </label>
                    <input type="text" name="notes" placeholder="e.g. Evening shift cash handover" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-purple-500 shadow-2xs">
                </div>

                <div class="pt-3 border-t border-slate-200 flex items-center justify-end gap-2 -mx-4 -mb-4 p-3 bg-slate-50/80">
                    <button type="button" @click="showHandoverModal = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                        {{ __('Cancel') }}
                    </button>
                    <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition cursor-pointer">
                        {{ __('Submit to Manager') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
