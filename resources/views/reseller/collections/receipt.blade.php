<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt #{{ $payment->invoice_no }} - {{ $reseller->name ?? 'Partner' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @media print {
            @page {
                size: A4 portrait;
                margin: 12mm;
            }
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 text-xs antialiased font-sans p-4 sm:p-6 print:p-0 print:bg-white">

    <!-- Print Control Bar -->
    <div class="max-w-2xl mx-auto mb-4 flex items-center justify-between no-print bg-white p-3 rounded-xl border border-slate-200 shadow-sm">
        <div class="flex items-center gap-2">
            <span class="font-bold text-slate-800 text-sm">Money Receipt #{{ $payment->invoice_no }}</span>
            <span class="text-xs text-slate-500 font-mono">{{ $payment->customer?->name }}</span>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-print"></i>
                <span>Print Receipt</span>
            </button>
            <button onclick="window.close()" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200 transition cursor-pointer">
                Close
            </button>
        </div>
    </div>

    <!-- Printable Paper Sheet -->
    <div class="max-w-2xl mx-auto bg-white border border-slate-200 rounded-xl p-8 print:border-none print:p-0 print:shadow-none space-y-6">
        
        <!-- Header -->
        <div class="flex items-start justify-between border-b border-slate-200 pb-4">
            <div>
                <h1 class="text-xl font-black text-slate-900 tracking-tight">{{ $tenant->company_name ?? $tenant->name ?? 'ISP Management Network' }}</h1>
                <p class="text-xs text-slate-600 mt-0.5">High Speed Broadband Internet Services</p>
                <div class="mt-2 text-[11px] text-slate-600 font-sans space-y-0.5">
                    <div><strong>Partner:</strong> {{ $reseller->name }} ({{ $reseller->code ?? 'RES-001' }})</div>
                    <div><strong>Contact Phone:</strong> {{ $reseller->mobile ?? 'N/A' }}</div>
                </div>
            </div>
            <div class="text-right">
                <div class="inline-block px-3 py-1 rounded-lg bg-emerald-50 text-emerald-800 border border-emerald-200 font-mono font-bold text-xs uppercase mb-1.5">
                    MONEY RECEIPT
                </div>
                <div class="text-sm font-mono font-bold text-slate-800">NO #{{ $payment->invoice_no }}</div>
                <div class="text-[11px] text-slate-500 font-mono">Date: {{ $payment->paid_at ? $payment->paid_at->format('d M Y, h:i A') : $payment->created_at->format('d M Y') }}</div>
            </div>
        </div>

        <!-- Receipt Details Box -->
        <div class="p-4 bg-slate-50 rounded-xl border border-slate-200/80 space-y-3">
            <div class="flex justify-between border-b border-slate-200/60 pb-2">
                <div>
                    <span class="text-[10px] uppercase font-bold text-slate-500 block">Received With Thanks From</span>
                    <div class="text-sm font-bold text-slate-900 mt-0.5">{{ $payment->customer?->name ?? 'Valued Subscriber' }}</div>
                    <div class="text-xs text-slate-600 font-mono">Customer ID: <strong>{{ $payment->customer?->customer_id }}</strong> • User: <strong>{{ $payment->customer?->username }}</strong></div>
                    <div class="text-xs text-slate-600 font-mono">Phone: {{ $payment->customer?->phone }}</div>
                </div>
                <div class="text-right">
                    <span class="text-[10px] uppercase font-bold text-slate-500 block">Billing Details</span>
                    <div class="text-xs text-slate-700 mt-0.5">Package: <strong class="text-cyan-800">{{ $payment->customer?->package_display_name ?? 'N/A' }}</strong></div>
                    <div class="text-xs text-slate-700">Billing Month: <strong>{{ $payment->billing_month ? \Carbon\Carbon::parse($payment->billing_month . '-01')->format('F Y') : 'N/A' }}</strong></div>
                    <div class="text-xs text-slate-700">Collector: <strong>{{ $payment->collector?->name ?? 'Counter' }}</strong></div>
                </div>
            </div>

            <!-- Amount Breakdown Table -->
            <div class="pt-1">
                <table class="w-full text-xs font-mono">
                    <tr class="border-b border-slate-200">
                        <td class="py-2 text-slate-600 font-sans">Payment Method:</td>
                        <td class="py-2 text-right font-bold uppercase text-slate-800">{{ $payment->payment_method }}</td>
                    </tr>
                    @if($payment->discount > 0)
                    <tr class="border-b border-slate-200">
                        <td class="py-2 text-emerald-700 font-sans">Discount:</td>
                        <td class="py-2 text-right font-bold text-emerald-700">-@currency($payment->discount)</td>
                    </tr>
                    @endif
                    <tr class="border-b-2 border-slate-300 font-bold text-sm bg-white">
                        <td class="py-2.5 px-2 text-slate-900 font-sans">Total Amount Paid:</td>
                        <td class="py-2.5 px-2 text-right text-emerald-700">@currency($payment->amount)</td>
                    </tr>
                    <tr>
                        <td class="py-2 text-slate-600 font-sans">Remaining Due:</td>
                        <td class="py-2 text-right font-bold {{ ($payment->customer?->due_amount ?? 0) > 0 ? 'text-rose-600' : 'text-slate-700' }}">@currency($payment->customer?->due_amount ?? 0)</td>
                    </tr>
                </table>
            </div>
        </div>

        @if($payment->notes)
        <div class="p-3 bg-slate-50 rounded-lg text-slate-600 text-xs border border-slate-200">
            <strong>Notes:</strong> {{ $payment->notes }}
        </div>
        @endif

        <!-- Footer Signatures -->
        <div class="pt-8 border-t border-slate-200 flex justify-between items-end text-slate-500 text-[11px]">
            <div>
                <p>Thank you for your payment!</p>
                <p class="font-mono text-[10px] mt-0.5">Automated System Receipt • {{ now()->format('d M Y, h:i A') }}</p>
            </div>
            <div class="text-center">
                <div class="border-t border-slate-400 w-44 mb-1"></div>
                <p class="font-semibold text-slate-700 text-xs">Collector Signature</p>
                <p class="text-[10px] text-slate-500">{{ $payment->collector?->name ?? $reseller->name }}</p>
            </div>
        </div>

    </div>

</body>
</html>
