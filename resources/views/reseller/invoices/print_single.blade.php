<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tax Invoice - {{ $invoice->invoice_no }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @media print {
            @page {
                size: A4 portrait;
                margin: 12mm 12mm 12mm 12mm;
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
            <span class="font-bold text-slate-800 text-sm">Tax Invoice: {{ $invoice->invoice_no }}</span>
            <span class="text-xs text-slate-500 font-mono">Status: {{ $invoice->payment_status }}</span>
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
        
        <!-- 1. Header: Host ISP Info & Invoice Title -->
        <div class="flex items-start justify-between border-b border-slate-200 pb-5">
            <div>
                <h1 class="text-xl font-bold text-slate-900 tracking-tight">{{ $tenant->company_name ?? $tenant->name ?? 'ISP Management System' }}</h1>
                <p class="text-[11px] text-slate-500">{{ $tenant->address ?? 'Internet & Telecommunication Services Provider' }}</p>
                <div class="mt-1 text-[10.5px] text-slate-600 font-mono">
                    @if($tenant->phone || $tenant->mobile)
                        <div>Phone: {{ $tenant->phone ?? $tenant->mobile }}</div>
                    @endif
                    @if($tenant->email)
                        <div>Email: {{ $tenant->email }}</div>
                    @endif
                </div>
            </div>
            <div class="text-right">
                <div class="text-lg font-black tracking-widest text-indigo-700 uppercase">TAX INVOICE</div>
                <div class="text-xs font-mono font-bold text-slate-800 mt-1">{{ $invoice->invoice_no }}</div>
                <div class="inline-block mt-2 px-2.5 py-0.5 rounded text-[10.5px] font-bold border font-mono {{ $invoice->status_badge['class'] }}">
                    {{ $invoice->status_badge['label'] }}
                </div>
            </div>
        </div>

        <!-- 2. Bill To & Invoice Meta -->
        <div class="grid grid-cols-2 gap-6 p-4 rounded-xl bg-slate-50 border border-slate-200 text-xs">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-1">INVOICE TO (CLIENT / PARTNER)</span>
                <div class="text-sm font-bold text-slate-900">{{ $reseller->name }}</div>
                <div class="text-[11px] text-slate-600 font-mono mt-0.5">Partner Code: {{ $reseller->code ?? 'RES-001' }}</div>
                @if($reseller->contact_person)
                    <div class="text-[11px] text-slate-600">Attn: {{ $reseller->contact_person }}</div>
                @endif
                @if($reseller->mobile)
                    <div class="text-[11px] text-slate-600 font-mono">Mobile: {{ $reseller->mobile }}</div>
                @endif
                @if($reseller->address)
                    <div class="text-[11px] text-slate-600 mt-0.5">{{ $reseller->address }}</div>
                @endif
            </div>
            <div class="text-right space-y-1">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-1">BILLING INFORMATION</span>
                <div><span class="text-slate-500">Billing Month:</span> <span class="font-bold font-mono text-slate-800">{{ $invoice->billing_month ? $invoice->billing_month->format('F Y') : 'Current' }}</span></div>
                <div><span class="text-slate-500">Invoice Date:</span> <span class="font-mono text-slate-800">{{ $invoice->created_at ? $invoice->created_at->format('d M Y') : date('d M Y') }}</span></div>
                <div><span class="text-slate-500">Due Date:</span> <span class="font-mono text-slate-800">{{ $invoice->due_date ? $invoice->due_date->format('d M Y') : 'Immediate' }}</span></div>
                @if($invoice->paid_at)
                    <div><span class="text-slate-500">Paid On:</span> <span class="font-mono text-emerald-700 font-bold">{{ $invoice->paid_at->format('d M Y, h:i A') }}</span></div>
                @endif
            </div>
        </div>

        <!-- 3. Line Items Table -->
        <div class="space-y-2">
            <table class="w-full border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-100 border-b border-slate-300 text-slate-700 font-semibold">
                        <th class="py-2 px-3 text-left w-10">#</th>
                        <th class="py-2 px-3 text-left">Service Item / Description</th>
                        <th class="py-2 px-3 text-center w-20">Qty</th>
                        <th class="py-2 px-3 text-right w-28">Unit Rate</th>
                        <th class="py-2 px-3 text-right w-32">Total Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($items as $idx => $item)
                        <tr>
                            <td class="py-2.5 px-3 font-mono text-slate-500">{{ $idx + 1 }}</td>
                            <td class="py-2.5 px-3">
                                <div class="font-bold text-slate-900">{{ $item['item'] ?? 'Wholesale Service' }}</div>
                                @if(!empty($item['description']))
                                    <div class="text-[11px] text-slate-500">{{ $item['description'] }}</div>
                                @endif
                            </td>
                            <td class="py-2.5 px-3 text-center font-mono text-slate-700">{{ $item['qty'] ?? 1 }} {{ $item['unit'] ?? '' }}</td>
                            <td class="py-2.5 px-3 text-right font-mono text-slate-700">@currency($item['rate'] ?? 0)</td>
                            <td class="py-2.5 px-3 text-right font-mono font-bold text-slate-900">@currency($item['total'] ?? 0)</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="border-t-2 border-slate-300 text-xs">
                    <tr>
                        <td colspan="4" class="py-1.5 px-3 text-right text-slate-600 font-medium">Subtotal Amount:</td>
                        <td class="py-1.5 px-3 text-right font-mono font-bold text-slate-900">@currency($invoice->subtotal)</td>
                    </tr>
                    @if($invoice->discount > 0)
                        <tr>
                            <td colspan="4" class="py-1 px-3 text-right text-amber-700 font-medium">Discount:</td>
                            <td class="py-1 px-3 text-right font-mono text-amber-700">-@currency($invoice->discount)</td>
                        </tr>
                    @endif
                    @if($invoice->vat_tax > 0)
                        <tr>
                            <td colspan="4" class="py-1 px-3 text-right text-slate-600 font-medium">VAT / Tax:</td>
                            <td class="py-1 px-3 text-right font-mono text-slate-800">+@currency($invoice->vat_tax)</td>
                        </tr>
                    @endif
                    <tr class="bg-slate-50 text-sm font-bold border-t border-b border-slate-300">
                        <td colspan="4" class="py-2 px-3 text-right text-slate-900">Grand Total Billed:</td>
                        <td class="py-2 px-3 text-right font-mono text-indigo-900">@currency($invoice->amount)</td>
                    </tr>
                    <tr>
                        <td colspan="4" class="py-1.5 px-3 text-right text-emerald-700 font-bold">Total Paid:</td>
                        <td class="py-1.5 px-3 text-right font-mono font-bold text-emerald-700">@currency($invoice->paid_amount)</td>
                    </tr>
                    <tr class="font-bold">
                        <td colspan="4" class="py-1.5 px-3 text-right {{ $invoice->due_amount > 0 ? 'text-rose-700' : 'text-slate-500' }}">Total Due Balance:</td>
                        <td class="py-1.5 px-3 text-right font-mono {{ $invoice->due_amount > 0 ? 'text-rose-700' : 'text-slate-500' }}">@currency($invoice->due_amount)</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        @if($invoice->notes)
            <div class="p-3 rounded-lg bg-slate-50 border border-slate-200 text-xs">
                <span class="font-bold text-slate-700 block mb-0.5">Notes &amp; Terms:</span>
                <p class="text-slate-600">{{ $invoice->notes }}</p>
            </div>
        @endif

        <!-- 4. Signatures -->
        <div class="pt-12 grid grid-cols-2 gap-8 text-[11px] text-slate-600">
            <div>
                <div class="border-t border-slate-300 pt-1.5 w-48 text-center">
                    Authorized Reseller Signature
                </div>
            </div>
            <div class="text-right flex justify-end">
                <div class="border-t border-slate-300 pt-1.5 w-48 text-center">
                    Host ISP Billing &amp; Finance Seal
                </div>
            </div>
        </div>

    </div>

</body>
</html>
