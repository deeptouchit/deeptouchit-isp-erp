<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Voucher - {{ $payment->voucher_no }} - {{ $tenant->company_name ?? $tenant->name }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: #ffffff !important;
                padding: 0 !important;
            }
            .voucher-card {
                box-shadow: none !important;
                border: 1px solid #cbd5e1 !important;
            }
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-800 p-4 md:p-8 font-sans antialiased">
    <div class="max-w-3xl mx-auto space-y-4">
        
        <!-- Action Buttons (No Print) -->
        <div class="no-print flex items-center justify-between bg-white px-4 py-3 rounded-xl border border-slate-200 shadow-xs">
            <a href="{{ route('tenant.upstream.index') }}" class="inline-flex items-center gap-2 text-xs font-semibold text-slate-600 hover:text-slate-900 transition">
                <i class="fas fa-arrow-left"></i>
                <span>Back to Upstream Dashboard</span>
            </a>
            <button onclick="window.print()" class="inline-flex items-center gap-2 px-4 py-1.5 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold shadow-xs transition cursor-pointer">
                <i class="fas fa-print"></i>
                <span>Print Payment Voucher</span>
            </button>
        </div>

        <!-- Voucher Printable Container -->
        <div class="voucher-card bg-white rounded-2xl border border-slate-200 shadow-xl p-6 md:p-8 space-y-6">
            
            <!-- 1. Voucher Header -->
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 border-b border-slate-200 pb-5">
                <div>
                    <h1 class="text-xl font-black tracking-tight text-slate-900 uppercase">{{ $tenant->company_name ?? $tenant->name }}</h1>
                    <p class="text-xs text-slate-500 mt-0.5">{{ $tenant->address ?? 'ISP Operations & Telecommunication Gateway' }}</p>
                    <div class="flex items-center gap-3 text-[11px] text-slate-500 mt-1">
                        @if($tenant->phone)<span><i class="fas fa-phone text-slate-400 mr-1"></i>{{ $tenant->phone }}</span>@endif
                        @if($tenant->email)<span><i class="fas fa-envelope text-slate-400 mr-1"></i>{{ $tenant->email }}</span>@endif
                    </div>
                </div>

                <div class="text-right">
                    <span class="inline-block px-3 py-1 bg-cyan-50 border border-cyan-200 text-cyan-800 font-bold text-xs uppercase tracking-wider rounded-md">
                        Payment Disbursement Voucher
                    </span>
                    <div class="mt-2 text-xs font-mono font-bold text-slate-800">
                        Voucher No: <span class="text-cyan-700">{{ $payment->voucher_no }}</span>
                    </div>
                    <div class="text-[11px] text-slate-500 mt-0.5">
                        Date: <span class="font-medium text-slate-700">{{ $payment->paid_at ? $payment->paid_at->format('d M, Y h:i A') : $payment->created_at->format('d M, Y') }}</span>
                    </div>
                </div>
            </div>

            <!-- 2. Payee & Carrier Details Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-4 rounded-xl bg-slate-50 border border-slate-200/80">
                <div class="space-y-1.5">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Paid To (Carrier / Payee)</span>
                    <h2 class="text-sm font-bold text-slate-800">{{ $payment->provider->name ?? 'N/A' }}</h2>
                    <div class="text-xs text-slate-600">
                        Carrier Type: <span class="font-semibold text-indigo-700">{{ $payment->provider->carrier_type ?? 'IIG' }}</span>
                    </div>
                    @if($payment->provider->contact_person)
                    <div class="text-xs text-slate-600">Contact: {{ $payment->provider->contact_person }} ({{ $payment->provider->phone }})</div>
                    @endif
                </div>

                <div class="space-y-1.5 md:border-l md:border-slate-200 md:pl-4">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Bank & Disbursement Method</span>
                    <div class="text-xs font-semibold text-slate-800">
                        Method: <span class="text-blue-700">{{ str_replace('_', ' ', $payment->payment_method) }}</span>
                    </div>
                    @if($payment->bank_name)
                    <div class="text-xs text-slate-600">Bank: <span class="font-medium text-slate-800">{{ $payment->bank_name }}</span></div>
                    @endif
                    @if($payment->cheque_no)
                    <div class="text-xs text-slate-600 font-mono">Cheque / Trx No: <span class="font-bold text-slate-800">{{ $payment->cheque_no }}</span></div>
                    @elseif($payment->transaction_ref)
                    <div class="text-xs text-slate-600 font-mono">Ref: <span class="font-bold text-slate-800">{{ $payment->transaction_ref }}</span></div>
                    @endif
                </div>
            </div>

            <!-- 3. Payment Line Details -->
            <div>
                <table class="w-full border-collapse border border-slate-200 rounded-lg overflow-hidden text-xs">
                    <thead>
                        <tr class="bg-slate-100 text-slate-700 text-left font-semibold border-b border-slate-200">
                            <th class="p-2.5">#</th>
                            <th class="p-2.5">Description / Bill Reference</th>
                            <th class="p-2.5">Billing Month</th>
                            <th class="p-2.5 text-right">Invoice Amount</th>
                            <th class="p-2.5 text-right">Disbursed Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 text-slate-700">
                        <tr>
                            <td class="p-2.5 font-mono">1</td>
                            <td class="p-2.5">
                                <span class="font-semibold text-slate-800">Carrier Monthly Bandwidth & Transmission Settlement</span>
                                @if($payment->invoice)
                                <span class="block text-[11px] text-slate-500 font-mono mt-0.5">Invoice #{{ $payment->invoice->invoice_no }}</span>
                                @endif
                            </td>
                            <td class="p-2.5 font-medium">
                                {{ $payment->invoice && $payment->invoice->billing_month ? $payment->invoice->billing_month->format('F Y') : date('F Y') }}
                            </td>
                            <td class="p-2.5 text-right font-mono text-slate-600">
                                @if($payment->invoice)
                                @currency($payment->invoice->total_amount)
                                @else
                                -
                                @endif
                            </td>
                            <td class="p-2.5 text-right font-mono font-bold text-emerald-700 text-sm">
                                @currency($payment->amount)
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="bg-slate-50 font-bold border-t border-slate-200">
                            <td colspan="4" class="p-3 text-right uppercase tracking-wider text-slate-600">Total Disbursed:</td>
                            <td class="p-3 text-right font-mono text-base text-cyan-800">@currency($payment->amount)</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- 4. Notes & Audit Info -->
            @if($payment->notes)
            <div class="p-3 rounded-lg bg-amber-50/50 border border-amber-200/60 text-xs text-slate-700">
                <span class="font-bold text-amber-800 mr-1"><i class="fas fa-note-sticky mr-1"></i>Notes:</span>
                {{ $payment->notes }}
            </div>
            @endif

            <!-- 5. Signatures Block -->
            <div class="pt-12 grid grid-cols-4 gap-4 text-center">
                <div class="border-t border-slate-300 pt-1.5">
                    <p class="text-[11px] font-semibold text-slate-800">{{ $payment->creator->name ?? 'Accounts Officer' }}</p>
                    <p class="text-[9px] uppercase tracking-wider text-slate-400">Prepared By</p>
                </div>
                <div class="border-t border-slate-300 pt-1.5">
                    <p class="text-[11px] font-semibold text-slate-800">Accountant</p>
                    <p class="text-[9px] uppercase tracking-wider text-slate-400">Checked By</p>
                </div>
                <div class="border-t border-slate-300 pt-1.5">
                    <p class="text-[11px] font-semibold text-slate-800">Managing Director</p>
                    <p class="text-[9px] uppercase tracking-wider text-slate-400">Approved By</p>
                </div>
                <div class="border-t border-slate-300 pt-1.5">
                    <p class="text-[11px] font-semibold text-slate-800">Carrier Representative</p>
                    <p class="text-[9px] uppercase tracking-wider text-slate-400">Received By</p>
                </div>
            </div>

            <!-- Footer Stamp & Timestamp -->
            <div class="border-t border-slate-100 pt-4 flex items-center justify-between text-[10px] text-slate-400 font-mono">
                <span>System Generated Electronic Voucher • SomitySoft Carrier Accounting Engine</span>
                <span>Generated at: {{ now()->format('Y-m-d H:i:s') }}</span>
            </div>
        </div>
    </div>
</body>
</html>
