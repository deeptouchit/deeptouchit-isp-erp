@extends('tenant.layouts.app')

@section('title', __('Invoice #:no', ['no' => $invoice->invoice_no]) . ' - ' . ($tenant->company_name ?? $tenant->name ?? 'ISP Portal'))

{{-- 1. Page Specific Stylesheets & Print Media Rules --}}
@push('styles')
<style>
    @media print {
        header, nav, footer, .no-print, .lg\:pl-60 {
            display: none !important;
            padding: 0 !important;
            margin: 0 !important;
        }
        body, main {
            background: white !important;
            padding: 0 !important;
            margin: 0 !important;
        }
        #printable-voucher {
            border: none !important;
            box-shadow: none !important;
            width: 100% !important;
            max-width: 100% !important;
            padding: 0 !important;
        }
    }
</style>
@endpush

{{-- 2. Main Workspace Body --}}
@section('content')
<div class="space-y-3"
     x-data="{
         selectedGateway: 'bkash',
         customAmount: '{{ $invoice->calculated_due }}',
         fullAmount: '{{ $invoice->calculated_due }}',
         payType: 'full',

         printReceipt() {
             window.print();
         }
     }">

    <!-- 1. Top Header Bar (Icon + Title + Action Buttons ONLY - Zero Subtitle) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 bg-white p-3 rounded-xl border border-slate-200 shadow-xs no-print">
        <div class="flex items-center gap-2.5">
            <a href="{{ route('tenant.billing.dashboard') }}"
               class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 border border-slate-200 transition inline-flex items-center justify-center cursor-pointer flex-shrink-0"
               title="{{ __('Plan Overview') }}">
                <i class="fas fa-arrow-left text-xs"></i>
            </a>
            <div class="flex items-center gap-2 flex-wrap">
                <h1 class="text-sm font-bold text-slate-900 tracking-tight leading-tight">{{ __('Invoice #:no', ['no' => $invoice->invoice_no]) }}</h1>
                @if($invoice->status === 'paid')
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase bg-emerald-50 text-emerald-700 border border-emerald-200">
                        {{ __('Paid') }}
                    </span>
                @elseif($invoice->status === 'partially_paid')
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase bg-amber-50 text-amber-700 border border-amber-200">
                        {{ __('Partial') }}
                    </span>
                @else
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase bg-rose-50 text-rose-700 border border-rose-200">
                        {{ __('Unpaid') }}
                    </span>
                @endif
            </div>
        </div>

        <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap flex-shrink-0">
            <button type="button"
                    @click="printReceipt()"
                    class="h-[34px] px-3 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs border border-slate-200/80 transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-print text-[11px] text-slate-500"></i>
                <span>{{ __('Print Invoice') }}</span>
            </button>
            <a href="{{ route('tenant.billing.invoices') }}"
               class="h-[34px] px-3 rounded-lg bg-slate-800 hover:bg-slate-900 text-white font-semibold text-xs transition inline-flex items-center gap-1.5 shadow-xs">
                <i class="fas fa-receipt text-[11px]"></i>
                <span>{{ __('Invoices') }}</span>
            </a>
        </div>
    </div>

    <!-- Main Invoice Details Matrix -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        <!-- Left 2 Cols: Official Printable Voucher -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 p-6 shadow-xs space-y-5" id="printable-voucher">

            <!-- Invoice Header -->
            <div class="flex items-start justify-between border-b border-slate-200 pb-4">
                <div>
                    <h2 class="text-base font-extrabold text-blue-600 uppercase tracking-wider">{{ config('app.name', 'SOMITYSOFT SAAS') }}</h2>
                    <p class="text-[10px] text-slate-400 mt-0.5">{{ __('Automated Multi-Tenant ISP Billing Platform') }}</p>
                </div>
                <div class="text-right">
                    <span class="text-xs font-bold font-mono text-slate-900 block">{{ $invoice->invoice_no }}</span>
                    <span class="text-[10px] text-slate-400 block">{{ __('Issue Date') }}: {{ $invoice->created_at ? $invoice->created_at->format('d M, Y') : '--' }}</span>
                    <div class="mt-1">
                        @if($invoice->status === 'paid')
                            <span class="px-2 py-0.5 rounded-full font-bold text-[9.5px] uppercase bg-emerald-100 text-emerald-800">
                                {{ __('PAID / COMPLETED') }}
                            </span>
                        @elseif($invoice->status === 'partially_paid')
                            <span class="px-2 py-0.5 rounded-full font-bold text-[9.5px] uppercase bg-amber-100 text-amber-800">
                                {{ __('Partially Paid') }}
                            </span>
                        @else
                            <span class="px-2 py-0.5 rounded-full font-bold text-[9.5px] uppercase bg-rose-100 text-rose-800">
                                {{ __('Unpaid') }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Billed To -->
            <div class="grid grid-cols-2 gap-4 text-xs">
                <div>
                    <span class="text-[10px] font-bold text-slate-400 uppercase block">{{ __('Billed To') }}:</span>
                    <span class="font-bold text-slate-900 block mt-0.5">{{ $tenant->name }}</span>
                    <span class="text-slate-600 block text-[11px]">{{ $tenant->company_name }}</span>
                    <span class="text-slate-500 block text-[10.5px]">{{ $tenant->email }}</span>
                    <span class="text-slate-500 block text-[10.5px]">{{ $tenant->phone }}</span>
                </div>
                <div class="text-right">
                    <span class="text-[10px] font-bold text-slate-400 uppercase block">{{ __('Payment Schedule') }}:</span>
                    <span class="text-slate-700 block mt-0.5">{{ __('Due Date') }}: <strong>{{ $invoice->due_date ? $invoice->due_date->format('d M, Y') : __('Immediate') }}</strong></span>
                    @if($invoice->period_start && $invoice->period_end)
                        <span class="text-slate-500 font-mono text-[10.5px] block">
                            {{ __('Billing Period') }}: {{ $invoice->period_start->format('d M') }} - {{ $invoice->period_end->format('d M, Y') }}
                        </span>
                    @endif
                    @if($invoice->paid_at)
                        <span class="text-emerald-700 font-semibold block text-[10.5px]">{{ __('Paid Date') }}: {{ $invoice->paid_at->format('d M, Y (h:i A)') }}</span>
                    @endif
                </div>
            </div>

            <!-- Items Table -->
            <div class="rounded-xl border border-slate-200 overflow-hidden">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-slate-600 border-b border-slate-200 uppercase text-[10px] font-bold">
                        <tr>
                            <th class="px-3.5 py-2">{{ __('Description') }}</th>
                            <th class="px-3.5 py-2 text-center">{{ __('Qty') }}</th>
                            <th class="px-3.5 py-2 text-right">{{ __('Amount') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($invoice->items as $item)
                            <tr>
                                <td class="px-3.5 py-2.5">
                                    <span class="font-bold text-slate-800 block">{{ $item->description }}</span>
                                </td>
                                <td class="px-3.5 py-2.5 text-center font-mono">{{ $item->quantity }}</td>
                                <td class="px-3.5 py-2.5 text-right font-bold text-slate-900 font-mono">
                                    @currency($item->total_price)
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-3.5 py-2.5">
                                    <span class="font-bold text-slate-800 block">{{ __('Subscription Plan') }} - {{ $invoice->plan->name ?? __('Standard Plan') }}</span>
                                </td>
                                <td class="px-3.5 py-2.5 text-center font-mono">1</td>
                                <td class="px-3.5 py-2.5 text-right font-bold text-slate-900 font-mono">
                                    @currency($invoice->amount)
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="bg-slate-50/80 border-t border-slate-200 font-semibold text-[11px]">
                        <tr>
                            <td colspan="2" class="px-3.5 py-1.5 text-right text-slate-500">{{ __('Total Amount') }}:</td>
                            <td class="px-3.5 py-1.5 text-right font-bold text-slate-900 font-mono">@currency($invoice->amount)</td>
                        </tr>
                        @if($invoice->paid_amount > 0)
                            <tr>
                                <td colspan="2" class="px-3.5 py-1 text-right text-emerald-600">{{ __('Paid Amount') }} (-):</td>
                                <td class="px-3.5 py-1 text-right font-bold text-emerald-600 font-mono">@currency($invoice->paid_amount)</td>
                            </tr>
                        @endif
                        @if($invoice->credit_amount > 0)
                            <tr>
                                <td colspan="2" class="px-3.5 py-1 text-right text-purple-600">{{ __('Credit Balance') }} (-):</td>
                                <td class="px-3.5 py-1 text-right font-bold text-purple-600 font-mono">@currency($invoice->credit_amount)</td>
                            </tr>
                        @endif
                        <tr class="border-t border-slate-200 text-xs font-extrabold bg-slate-100/70">
                            <td colspan="2" class="px-3.5 py-2 text-right text-slate-900 uppercase">{{ __('Due Amount') }}:</td>
                            <td class="px-3.5 py-2 text-right text-rose-600 font-mono text-sm">
                                @currency($invoice->calculated_due)
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

        </div>

        <!-- Right 1 Col: Checkout / Payment Panel -->
        <div class="space-y-4 no-print">

            @if($invoice->calculated_due > 0)
                <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs space-y-4">

                    <div class="border-b border-slate-100 pb-3">
                        <h3 class="font-extrabold text-slate-900 text-sm flex items-center gap-2">
                            <i class="fas fa-wallet text-blue-600"></i>
                            <span>{{ __('Complete Payment') }}</span>
                        </h3>
                        <p class="text-[10.5px] text-slate-400 mt-0.5">{{ __('Select a payment gateway to settle your dues') }}</p>
                    </div>

                    <form action="{{ route('tenant.billing.pay', $invoice) }}" method="POST" class="space-y-4">
                        @csrf

                        <!-- Payment Gateways Options -->
                        <div>
                            <label class="block text-[10.5px] font-bold text-slate-600 uppercase tracking-wider mb-2">{{ __('Select Gateway') }}</label>
                            <div class="grid grid-cols-2 gap-2">

                                <!-- bKash -->
                                <label class="p-3 rounded-xl border cursor-pointer transition flex flex-col items-center gap-1.5 text-center"
                                       :class="selectedGateway === 'bkash' ? 'border-pink-500 bg-pink-50/50 shadow-xs' : 'border-slate-200 hover:bg-slate-50'">
                                    <input type="radio" name="gateway" value="bkash" x-model="selectedGateway" class="hidden">
                                    <div class="w-8 h-8 rounded-lg bg-pink-600 text-white flex items-center justify-center font-bold text-xs">
                                        bK
                                    </div>
                                    <span class="font-bold text-slate-800 text-[11px]">{{ __('bKash Checkout') }}</span>
                                </label>

                                <!-- Nagad -->
                                <label class="p-3 rounded-xl border cursor-pointer transition flex flex-col items-center gap-1.5 text-center"
                                       :class="selectedGateway === 'nagad' ? 'border-orange-500 bg-orange-50/50 shadow-xs' : 'border-slate-200 hover:bg-slate-50'">
                                    <input type="radio" name="gateway" value="nagad" x-model="selectedGateway" class="hidden">
                                    <div class="w-8 h-8 rounded-lg bg-orange-600 text-white flex items-center justify-center font-bold text-xs">
                                        NG
                                    </div>
                                    <span class="font-bold text-slate-800 text-[11px]">{{ __('Nagad Payment') }}</span>
                                </label>

                            </div>
                        </div>

                        <!-- Partial vs Full Amount Option -->
                        <div>
                            <label class="block text-[10.5px] font-bold text-slate-600 uppercase tracking-wider mb-1.5">{{ __('Payment Amount') }}</label>

                            <div class="flex items-center gap-3 mb-2 text-xs">
                                <label class="flex items-center gap-1.5 cursor-pointer">
                                    <input type="radio" name="pay_option" value="full" x-model="payType" @change="customAmount = fullAmount" class="text-blue-600">
                                    <span class="font-semibold">{{ __('Full Due') }} (@currency($invoice->calculated_due))</span>
                                </label>
                                <label class="flex items-center gap-1.5 cursor-pointer">
                                    <input type="radio" name="pay_option" value="partial" x-model="payType" class="text-blue-600">
                                    <span class="font-semibold">{{ __('Partial') }}</span>
                                </label>
                            </div>

                            <div x-show="payType === 'partial'" x-cloak class="mt-2">
                                <input type="number"
                                       name="amount"
                                       x-model="customAmount"
                                       min="1"
                                       max="{{ $invoice->calculated_due }}"
                                       placeholder="{{ __('Enter partial amount') }}"
                                       class="w-full px-3 py-2 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 font-mono font-bold text-xs focus:ring-1 focus:ring-blue-500">
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit"
                                class="w-full py-2.5 rounded-xl text-white font-bold text-xs shadow-xs transition flex items-center justify-center gap-2 cursor-pointer"
                                :class="selectedGateway === 'bkash' ? 'bg-pink-600 hover:bg-pink-700' : 'bg-orange-600 hover:bg-orange-700'">
                            <i class="fas fa-lock text-[10px]"></i>
                            <span>
                                {{ __('Pay Now') }} {{ $currencySymbol ?? '৳' }}<span x-text="payType === 'full' ? '{{ number_format($invoice->calculated_due, 0) }}' : customAmount"></span>
                                (<span x-text="selectedGateway === 'bkash' ? '{{ __('bKash') }}' : '{{ __('Nagad') }}'"></span>)
                            </span>
                        </button>

                    </form>

                </div>
            @else
                <div class="bg-emerald-50 rounded-2xl border border-emerald-200 p-5 text-center space-y-2">
                    <div class="w-10 h-10 rounded-full bg-emerald-600 text-white flex items-center justify-center mx-auto text-sm shadow-xs">
                        <i class="fas fa-check"></i>
                    </div>
                    <h4 class="font-extrabold text-emerald-900 text-sm">{{ __('Invoice Fully Paid') }}</h4>
                    <p class="text-[11px] text-emerald-700">{{ __('Thank you! This invoice has been completely settled and your subscription is active.') }}</p>
                </div>
            @endif

        </div>

    </div>

</div>
@endsection

{{-- 3. Page Specific Scripts --}}
@push('scripts')
<script>
    // Invoice show script logic
</script>
@endpush
