<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Money Receipt - {{ $payment->invoice_no }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: white !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .receipt-container {
                box-shadow: none !important;
                border: none !important;
                max-width: 100% !important;
                width: 100% !important;
                padding: 10px !important;
            }
        }
        @page {
            size: auto;
            margin: 8mm;
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-800 antialiased min-h-screen py-6 px-4 font-sans text-xs">

    <!-- Top Floating Actions (Hidden in Print) -->
    <div class="no-print max-w-xl mx-auto mb-4 flex items-center justify-between">
        <button onclick="window.close()" class="px-3.5 py-1.5 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-semibold text-xs transition flex items-center gap-1.5 shadow-2xs cursor-pointer">
            <i class="fas fa-arrow-left text-[10px]"></i>
            <span>Close Window</span>
        </button>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-4 py-1.5 rounded-lg bg-teal-600 hover:bg-teal-700 text-white font-semibold text-xs transition flex items-center gap-1.5 shadow-xs cursor-pointer">
                <i class="fas fa-print text-[11px]"></i>
                <span>Print Money Receipt</span>
            </button>
        </div>
    </div>

    <!-- Printable Receipt Container -->
    <div class="receipt-container max-w-xl mx-auto bg-white rounded-xl border border-slate-200 shadow-lg p-6 space-y-4">
        
        <!-- Receipt Header -->
        <div class="border-b border-slate-200 pb-3 flex items-start justify-between gap-4">
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-teal-600 text-white flex items-center justify-center font-bold text-sm shadow-2xs">
                        <i class="fas fa-wifi"></i>
                    </div>
                    <div>
                        <h1 class="text-sm font-bold text-slate-900 tracking-tight leading-none uppercase">
                            {{ $tenant->company_name ?? $tenant->name }}
                        </h1>
                        <p class="text-[10px] text-slate-500 font-medium">Internet Service Provider (ISP)</p>
                    </div>
                </div>
                <div class="text-[10px] text-slate-600 space-y-0.5 pt-1">
                    @if($tenant->address)
                        <p><i class="fas fa-map-marker-alt text-slate-400 w-3 text-center"></i> {{ $tenant->address }}</p>
                    @endif
                    @if($tenant->phone || $tenant->billing_phone)
                        <p><i class="fas fa-phone text-slate-400 w-3 text-center"></i> {{ $tenant->billing_phone ?? $tenant->phone }}</p>
                    @endif
                    @if($tenant->email)
                        <p><i class="fas fa-envelope text-slate-400 w-3 text-center"></i> {{ $tenant->email }}</p>
                    @endif
                </div>
            </div>

            <!-- Receipt Badge & Number -->
            <div class="text-right space-y-1">
                <span class="inline-block px-2.5 py-0.5 rounded bg-teal-50 text-teal-700 border border-teal-200 font-bold uppercase tracking-wider text-[10px]">
                    OFFICIAL MONEY RECEIPT
                </span>
                <div class="text-right font-mono">
                    <span class="text-[10px] text-slate-400 block">Receipt No:</span>
                    <span class="text-xs font-bold text-slate-900 block">{{ $payment->invoice_no }}</span>
                </div>
                <div class="text-[10px] text-slate-500 font-mono">
                    Date: {{ $payment->paid_at ? $payment->paid_at->format('d M Y, h:i A') : $payment->created_at->format('d M Y, h:i A') }}
                </div>
            </div>
        </div>

        <!-- Customer & Billing Details Grid -->
        <div class="grid grid-cols-2 gap-3 p-3 bg-slate-50 rounded-lg border border-slate-200/80">
            <div class="space-y-1">
                <span class="text-[9.5px] uppercase font-bold text-slate-400 tracking-wider block">Subscriber Info</span>
                <div class="font-bold text-slate-800 text-xs">{{ $payment->customer?->name }}</div>
                <div class="font-mono text-[10.5px] text-slate-600">ID: <strong>{{ $payment->customer?->customer_id }}</strong> &bull; User: <strong>{{ $payment->customer?->username }}</strong></div>
                <div class="text-[10.5px] text-slate-600"><i class="fas fa-phone text-slate-400 text-[9px]"></i> {{ $payment->customer?->phone ?: 'N/A' }}</div>
                @if($payment->customer?->address)
                    <div class="text-[10px] text-slate-500 truncate"><i class="fas fa-home text-slate-400 text-[9px]"></i> {{ $payment->customer?->address }}</div>
                @endif
            </div>

            <div class="space-y-1 text-right">
                <span class="text-[9.5px] uppercase font-bold text-slate-400 tracking-wider block">Subscription Details</span>
                <div class="font-bold text-teal-800 text-xs">{{ $payment->customer?->package_name ?: ($payment->customer?->package?->name ?: 'Standard Package') }}</div>
                <div class="font-mono text-[10.5px] text-slate-600">Monthly Bill: <strong>@currency($payment->customer?->monthly_bill ?? 0)</strong></div>
                <div class="text-[10.5px] text-slate-600">
                    Validity Extended: 
                    <strong class="text-emerald-700 font-mono">{{ $payment->customer?->expiry_date ? $payment->customer?->expiry_date->format('d M Y') : 'Active' }}</strong>
                </div>
                <div class="text-[10px] text-slate-500">Collected By: <strong>{{ $payment->collector?->name ?: 'Office Admin' }}</strong></div>
            </div>
        </div>

        <!-- Payment Breakdown Table -->
        <table class="w-full border-collapse border border-slate-200 rounded-lg overflow-hidden text-[11px]">
            <thead>
                <tr class="bg-slate-100 text-slate-700 font-bold border-b border-slate-200">
                    <th class="p-2 text-left">Description</th>
                    <th class="p-2 text-left">Billing Period / Month</th>
                    <th class="p-2 text-center">Payment Method</th>
                    <th class="p-2 text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr class="border-b border-slate-100">
                    <td class="p-2 font-medium text-slate-800">
                        Internet Subscription Fee
                        @if($payment->notes)
                            <span class="block text-[10px] text-slate-500 font-normal italic">{{ $payment->notes }}</span>
                        @endif
                    </td>
                    <td class="p-2 font-mono text-slate-700">{{ $payment->billing_month ?: date('F Y') }}</td>
                    <td class="p-2 text-center">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[9.5px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                            {{ $payment->payment_method_name }}
                        </span>
                        @if($payment->transaction_id)
                            <span class="block font-mono text-[9px] text-slate-500">Trx: {{ $payment->transaction_id }}</span>
                        @endif
                    </td>
                    <td class="p-2 text-right font-mono font-bold text-slate-800">
                        @currency((float)$payment->amount + (float)$payment->discount)
                    </td>
                </tr>
                @if((float)$payment->discount > 0)
                    <tr class="border-b border-slate-100 bg-emerald-50/40">
                        <td colspan="3" class="p-2 text-right font-medium text-emerald-800">Special Discount / Waiver:</td>
                        <td class="p-2 text-right font-mono font-bold text-emerald-700">-@currency($payment->discount)</td>
                    </tr>
                @endif
                <tr class="bg-slate-50 font-bold text-xs border-t-2 border-slate-300">
                    <td colspan="3" class="p-2 text-right uppercase tracking-wider text-slate-800">Total Net Amount Paid:</td>
                    <td class="p-2 text-right font-mono font-bold text-emerald-700 text-sm">
                        @currency($payment->amount)
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Outstanding Balance Summary & Footer Note -->
        <div class="flex items-center justify-between p-2.5 bg-slate-50 rounded-lg border border-slate-200 text-[10.5px]">
            <div class="space-y-0.5">
                <span class="text-slate-500">Current Due Balance:</span>
                <span class="font-bold font-mono text-xs {{ (float)($payment->customer?->due_amount ?? 0) > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                    @currency($payment->customer?->due_amount ?? 0)
                </span>
            </div>
            <div class="text-right text-[10px] text-slate-500 italic">
                Thank you for your business. For support or queries, call {{ $tenant->billing_phone ?? ($tenant->phone ?? 'our helpline') }}.
            </div>
        </div>

        <!-- Authorization Signatures -->
        <div class="pt-8 grid grid-cols-2 gap-8 text-center text-[10.5px] text-slate-600">
            <div>
                <div class="border-t border-slate-300 pt-1.5 font-medium">Customer Signature</div>
            </div>
            <div>
                <div class="border-t border-slate-300 pt-1.5 font-semibold text-slate-800">Authorized Signature &amp; Seal</div>
            </div>
        </div>

    </div>

</body>
</html>
