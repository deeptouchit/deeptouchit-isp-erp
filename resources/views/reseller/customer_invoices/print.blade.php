<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Bill Invoice #{{ $invoice->invoice_no }} - {{ $reseller->name ?? 'Partner' }}</title>
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
    <div class="max-w-3xl mx-auto mb-4 flex items-center justify-between no-print bg-white p-3 rounded-xl border border-slate-200 shadow-sm">
        <div class="flex items-center gap-2">
            <span class="font-bold text-slate-800 text-sm">Customer Bill Invoice #{{ $invoice->invoice_no }}</span>
            <span class="text-xs text-slate-500 font-mono">{{ $invoice->customer?->name }}</span>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-3.5 py-1.5 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-print"></i>
                <span>Print Invoice</span>
            </button>
            <button onclick="window.close()" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200 transition cursor-pointer">
                Close
            </button>
        </div>
    </div>

    <!-- Printable Paper Sheet -->
    <div class="max-w-3xl mx-auto bg-white border border-slate-200 rounded-xl p-8 print:border-none print:p-0 print:shadow-none space-y-6">
        
        <!-- Header -->
        <div class="flex items-start justify-between border-b border-slate-200 pb-5">
            <div>
                <h1 class="text-xl font-black text-slate-900 tracking-tight">{{ $tenant->company_name ?? $tenant->name ?? 'ISP Management Network' }}</h1>
                <p class="text-xs text-slate-600 mt-0.5">High Speed Fiber Broadband Network</p>
                <div class="mt-2 text-[11px] text-slate-600 font-sans space-y-0.5">
                    <div><strong>Franchise Partner:</strong> {{ $reseller->name }} ({{ $reseller->code ?? 'RES-001' }})</div>
                    <div><strong>Contact Phone:</strong> {{ $reseller->mobile ?? 'N/A' }}</div>
                    <div><strong>Address:</strong> {{ $reseller->address ?? 'N/A' }}</div>
                </div>
            </div>
            <div class="text-right">
                <div class="inline-block px-3 py-1 rounded-lg {{ $invoice->status === 'paid' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-rose-50 text-rose-800 border-rose-200' }} font-mono font-bold text-xs border uppercase mb-2">
                    STATUS: {{ $invoice->status }}
                </div>
                <div class="text-sm font-mono font-bold text-slate-800">INVOICE #{{ $invoice->invoice_no }}</div>
                <div class="text-[11px] text-slate-500 font-mono">Date: {{ $invoice->issue_date ? \Carbon\Carbon::parse($invoice->issue_date)->format('d M Y') : now()->format('d M Y') }}</div>
                <div class="text-[11px] text-slate-500 font-mono">Month: {{ $invoice->billing_month ? \Carbon\Carbon::parse($invoice->billing_month . '-01')->format('F Y') : 'N/A' }}</div>
            </div>
        </div>

        <!-- Bill To -->
        <div class="p-4 bg-slate-50 rounded-xl border border-slate-200/80 grid grid-cols-2 gap-4">
            <div>
                <span class="text-[10px] uppercase font-bold text-slate-500 block tracking-wider">Bill To Customer</span>
                <div class="text-sm font-bold text-slate-900 mt-1">{{ $invoice->customer?->name ?? 'Valued Customer' }}</div>
                <div class="text-xs text-slate-600 font-mono mt-0.5">ID: <strong>{{ $invoice->customer?->customer_id }}</strong> • User: <strong>{{ $invoice->customer?->username }}</strong></div>
                <div class="text-xs text-slate-600 mt-0.5">Phone: {{ $invoice->customer?->phone }}</div>
                <div class="text-xs text-slate-500 mt-0.5">{{ $invoice->customer?->address }}</div>
            </div>
            <div class="text-right space-y-1">
                <span class="text-[10px] uppercase font-bold text-slate-500 block tracking-wider">Subscription Info</span>
                <div class="text-xs text-slate-700">Package: <strong class="text-cyan-800">{{ $invoice->customer?->package_display_name ?? $invoice->package?->name ?? ($invoice->package_name ?? 'Internet Package') }}</strong></div>
                <div class="text-xs text-slate-700">Zone / Area: <strong>{{ $invoice->customer?->zone ?? 'Default Zone' }}</strong></div>
                <div class="text-xs text-slate-700 font-mono">Payment Method: <strong>{{ strtoupper($invoice->payment_method ?? 'CASH') }}</strong></div>
            </div>
        </div>

        <!-- Items Table -->
        <table class="w-full border-collapse border border-slate-200 text-left text-xs">
            <thead>
                <tr class="bg-slate-100 text-slate-700 border-b border-slate-200 font-semibold">
                    <th class="p-3 border-r border-slate-200 w-10 text-center">#</th>
                    <th class="p-3 border-r border-slate-200">Description / Service Item</th>
                    <th class="p-3 border-r border-slate-200 w-32 text-center">Billing Month</th>
                    <th class="p-3 w-32 text-right">Amount ({{ $currencySymbol }})</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 font-mono">
                <tr>
                    <td class="p-3 border-r border-slate-200 text-center text-slate-500">1</td>
                    <td class="p-3 border-r border-slate-200 font-sans">
                        <strong class="text-slate-800 text-xs">{{ $invoice->package?->name ?? ($invoice->package_name ?? 'Broadband Internet') }}</strong>
                        <span class="text-slate-500 text-[11px] block">High-speed unlimited broadband service for {{ $invoice->billing_month ? \Carbon\Carbon::parse($invoice->billing_month . '-01')->format('F Y') : '' }}</span>
                    </td>
                    <td class="p-3 border-r border-slate-200 text-center text-slate-700">
                        {{ $invoice->billing_month ? \Carbon\Carbon::parse($invoice->billing_month . '-01')->format('M Y') : 'N/A' }}
                    </td>
                    <td class="p-3 text-right font-bold text-slate-900">
                        {{ number_format($invoice->amount, 2) }}
                    </td>
                </tr>
            </tbody>
            <tfoot class="font-mono bg-slate-50 divide-y divide-slate-200 text-xs">
                <tr>
                    <td colspan="3" class="p-2.5 text-right font-sans text-slate-600">Subtotal:</td>
                    <td class="p-2.5 text-right font-bold text-slate-800">{{ number_format($invoice->amount, 2) }}</td>
                </tr>
                @if($invoice->discount > 0)
                <tr>
                    <td colspan="3" class="p-2.5 text-right font-sans text-emerald-700">Discount:</td>
                    <td class="p-2.5 text-right font-bold text-emerald-700">-{{ number_format($invoice->discount, 2) }}</td>
                </tr>
                @endif
                <tr class="bg-slate-100 font-bold text-sm">
                    <td colspan="3" class="p-3 text-right font-sans text-slate-900">Total Payable:</td>
                    <td class="p-3 text-right text-slate-900">{{ number_format($invoice->total_payable, 2) }}</td>
                </tr>
                <tr>
                    <td colspan="3" class="p-2.5 text-right font-sans text-emerald-700 font-semibold">Paid Amount:</td>
                    <td class="p-2.5 text-right font-bold text-emerald-700">{{ number_format($invoice->paid_amount, 2) }}</td>
                </tr>
                <tr>
                    <td colspan="3" class="p-2.5 text-right font-sans text-rose-700 font-semibold">Due Amount:</td>
                    <td class="p-2.5 text-right font-bold text-rose-700">{{ number_format($invoice->due_amount, 2) }}</td>
                </tr>
            </tfoot>
        </table>

        <!-- Signatures & Notice -->
        <div class="pt-6 border-t border-slate-200 text-slate-500 text-[11px] flex justify-between items-end">
            <div>
                <p>Thank you for choosing our fiber broadband service!</p>
                <p class="font-mono text-[10px] mt-0.5">System Generated Invoice • Date: {{ now()->format('d M Y, h:i A') }}</p>
            </div>
            <div class="text-center">
                <div class="border-t border-slate-400 w-44 mb-1"></div>
                <p class="font-semibold text-slate-700 text-xs">Authorized Signature</p>
                <p class="text-[10px] text-slate-500">{{ $reseller->name }}</p>
            </div>
        </div>

    </div>

</body>
</html>
