<!-- Invoice & Payment Details Modal Component (Natural Production Grade) -->
<div x-show="paymentDetailsModal" 
     x-cloak 
     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">

    <div class="bg-white text-slate-800 rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden" 
         @click.away="paymentDetailsModal = false"
         x-transition:enter="transition ease-out duration-200 transform"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150 transform"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">

        <!-- Natural Soft Header -->
        <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <div class="w-7 h-7 rounded-lg bg-teal-50 text-teal-600 border border-teal-100 flex items-center justify-center text-xs flex-shrink-0 shadow-2xs">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
                <div>
                    <h4 class="text-xs font-semibold text-slate-800">{{ __('Invoice & Payment Details') }}</h4>
                    <p class="text-[10.5px] text-slate-500 font-normal">
                        {{ __('Invoice') }}: <span class="font-mono font-bold text-slate-700" x-text="selectedPayment.invoice_no || 'INV-000'"></span> &bull; 
                        {{ __('Date') }}: <span class="font-mono text-slate-600" x-text="selectedPayment.paid_at || '--'"></span>
                    </p>
                </div>
            </div>
            <button type="button" 
                    @click="paymentDetailsModal = false" 
                    class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center">
                <i class="fas fa-times text-xs"></i>
            </button>
        </div>

        <!-- Body -->
        <div class="p-4 space-y-3 text-xs max-h-[75vh] overflow-y-auto">
            
            <!-- Financial Snapshot (3 Cards) -->
            <div class="grid grid-cols-3 gap-2 text-center">
                <div class="p-2.5 rounded-lg bg-emerald-50/70 border border-emerald-200/80">
                    <span class="text-[9.5px] uppercase font-bold text-emerald-800 tracking-wider block">{{ __('Paid Amount') }}</span>
                    <span class="font-mono font-bold text-sm text-emerald-700 leading-tight block mt-0.5" x-text="selectedPayment.amount_formatted"></span>
                </div>
                <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200">
                    <span class="text-[9.5px] uppercase font-bold text-slate-500 tracking-wider block">{{ __('Discount') }}</span>
                    <span class="font-mono font-bold text-sm text-slate-700 leading-tight block mt-0.5" x-text="selectedPayment.discount_formatted"></span>
                </div>
                <div class="p-2.5 rounded-lg bg-teal-50/70 border border-teal-200/80">
                    <span class="text-[9.5px] uppercase font-bold text-teal-800 tracking-wider block">{{ __('Total Billed') }}</span>
                    <span class="font-mono font-bold text-sm text-teal-900 leading-tight block mt-0.5" x-text="selectedPayment.total_formatted"></span>
                </div>
            </div>

            <!-- Transaction Details Table / List -->
            <div class="space-y-1.5 p-3 rounded-lg bg-slate-50 border border-slate-200/80 text-[11px]">
                <div class="flex items-center justify-between py-1 border-b border-slate-200/60">
                    <span class="text-slate-500">{{ __('Invoice Number') }}:</span>
                    <span class="font-mono font-bold text-slate-800" x-text="selectedPayment.invoice_no"></span>
                </div>
                <div class="flex items-center justify-between py-1 border-b border-slate-200/60">
                    <span class="text-slate-500">{{ __('Billing Month') }}:</span>
                    <span class="font-medium text-slate-800" x-text="selectedPayment.billing_month || '{{ __('Regular Month') }}'"></span>
                </div>
                <div class="flex items-center justify-between py-1 border-b border-slate-200/60">
                    <span class="text-slate-500">{{ __('Payment Method') }}:</span>
                    <span class="font-semibold text-slate-800" x-text="selectedPayment.payment_method"></span>
                </div>
                <div class="flex items-center justify-between py-1 border-b border-slate-200/60">
                    <span class="text-slate-500">{{ __('Transaction ID') }}:</span>
                    <span class="font-mono text-slate-700" x-text="selectedPayment.transaction_id || 'N/A'"></span>
                </div>
                <div class="flex items-center justify-between py-1 border-b border-slate-200/60">
                    <span class="text-slate-500">{{ __('Collected By') }}:</span>
                    <span class="font-medium text-slate-800" x-text="selectedPayment.collector_name || 'System Admin'"></span>
                </div>
                <div class="flex items-center justify-between py-1 border-b border-slate-200/60">
                    <span class="text-slate-500">{{ __('Payment Date') }}:</span>
                    <span class="font-mono text-slate-700" x-text="selectedPayment.paid_at"></span>
                </div>
                <div class="flex items-center justify-between py-1">
                    <span class="text-slate-500">{{ __('Account Status') }}:</span>
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9.5px] font-bold font-mono uppercase bg-emerald-50 text-emerald-700 border border-emerald-200">
                        {{ __('PAID / COMPLETED') }}
                    </span>
                </div>
            </div>

            <!-- Notes / Remarks (if present) -->
            <template x-if="selectedPayment.notes">
                <div class="space-y-1">
                    <span class="text-[10px] font-semibold text-slate-500 block uppercase tracking-wider">{{ __('Remarks') }}:</span>
                    <div class="p-2.5 bg-white rounded-lg border border-slate-200 text-xs text-slate-700 leading-relaxed" 
                         x-text="selectedPayment.notes"></div>
                </div>
            </template>

        </div>

        <!-- Modal Footer with 1-Click Print Receipt -->
        <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
            <a :href="selectedPayment.receipt_url" 
               target="_blank" 
               class="bg-teal-600 hover:bg-teal-700 text-white font-medium text-xs px-3.5 py-1.5 rounded-lg transition inline-flex items-center gap-1.5 shadow-xs cursor-pointer">
                <i class="fas fa-print text-[10px]"></i>
                <span>{{ __('Print Receipt') }}</span>
            </a>

            <button type="button" 
                    @click="paymentDetailsModal = false" 
                    class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-4 py-1.5 rounded-lg transition shadow-2xs cursor-pointer">
                {{ __('Close') }}
            </button>
        </div>

    </div>
</div>
