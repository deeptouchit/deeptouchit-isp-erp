<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice_{{ $invoice->invoice_no }} — {{ $invoice->tenant->name ?? 'Client' }} — {{ $globalSettings['app_name'] ?? 'SomitySoft SaaS' }}</title>
    
    @if(!empty($globalSettings['app_favicon']))
        <link rel="icon" type="image/x-icon" href="{{ $globalSettings['app_favicon'] }}">
    @endif

    <!-- Fonts: Plus Jakarta Sans & JetBrains Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Tailwind CSS (Play CDN) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'system-ui', 'sans-serif'],
                        mono: ['"JetBrains Mono"', 'monospace'],
                    },
                }
            }
        }
    </script>

    <style>
        body {
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
            background-color: #f1f5f9;
            -webkit-font-smoothing: antialiased;
        }

        /* Screen A4 sheet simulator */
        .invoice-sheet {
            width: 210mm;
            min-height: 297mm;
            margin: 20px auto;
            background: #ffffff;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            padding: 12mm 15mm;
            position: relative;
            box-sizing: border-box;
        }

        /* High-Grade Print Rules */
        @media print {
            @page {
                size: A4 portrait;
                margin: 8mm 10mm;
            }

            body, html {
                margin: 0 !important;
                padding: 0 !important;
                background: #ffffff !important;
                color: #0f172a !important;
                font-size: 11pt !important;
            }

            .no-print {
                display: none !important;
            }

            .invoice-sheet {
                width: 100% !important;
                min-height: auto !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
                border: none !important;
            }

            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }

        /* Official Grunge Stamp Styles */
        .stamp-badge {
            transform: rotate(-10deg);
            display: inline-block;
            padding: 6px 14px;
            text-transform: uppercase;
            font-weight: 800;
            letter-spacing: 2px;
            font-size: 13px;
            border-radius: 6px;
            mask-image: linear-gradient(135deg, rgba(0,0,0,0.85) 0%, rgba(0,0,0,1) 50%, rgba(0,0,0,0.8) 100%);
        }
        .stamp-paid {
            color: #047857;
            border: 2.5px dashed #059669;
            background-color: rgba(209, 250, 229, 0.45);
        }
        .stamp-unpaid {
            color: #b91c1c;
            border: 2.5px dashed #dc2626;
            background-color: rgba(254, 226, 226, 0.45);
        }
    </style>
</head>
<body class="text-slate-800">

    <!-- Screen Navigation & Action Bar (Hidden on Print) -->
    <div class="no-print sticky top-0 z-50 bg-slate-900/90 backdrop-blur-md border-b border-slate-700/80 px-4 py-3 shadow-md">
        <div class="max-w-5xl mx-auto flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <a href="{{ route('owner.billing.index') }}" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white text-xs font-semibold flex items-center gap-1.5 transition">
                    <i class="fas fa-arrow-left text-[10px]"></i>
                    <span>Back to Billing</span>
                </a>
                <span class="text-slate-400 text-xs hidden sm:inline">|</span>
                <span class="text-white text-xs font-semibold flex items-center gap-2">
                    <i class="fas fa-file-invoice text-blue-400"></i>
                    <span>Invoice #{{ $invoice->invoice_no }}</span>
                    <span class="px-2 py-0.5 rounded text-[10px] uppercase font-bold {{ $invoice->status === 'paid' ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-amber-500/20 text-amber-400 border border-amber-500/30' }}">
                        {{ $invoice->status }}
                    </span>
                </span>
            </div>

            <div class="flex items-center gap-2">
                <span class="text-[11px] text-slate-400 mr-2 hidden md:inline">
                    <i class="fas fa-info-circle text-blue-400 mr-1"></i> Press <strong>Ctrl + P</strong> or click:
                </span>
                <button type="button" onclick="window.print()" class="px-4 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold shadow-md hover:shadow-blue-500/30 transition flex items-center gap-2 cursor-pointer">
                    <i class="fas fa-print"></i>
                    <span>Print Voucher</span>
                </button>
                <button type="button" onclick="window.print()" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold flex items-center gap-1.5 transition cursor-pointer">
                    <i class="fas fa-file-pdf text-red-400"></i>
                    <span>Save PDF</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Main Enterprise A4 Printable Voucher -->
    <div class="invoice-sheet flex flex-col justify-between" id="invoiceSheet">
        
        <div class="space-y-6">
            
            <!-- 1. Header Section: Platform Branding & Voucher Meta -->
            <div class="flex items-start justify-between border-b-2 border-slate-800 pb-5">
                <!-- Platform Brand Info -->
                <div class="space-y-1.5 max-w-sm">
                    <div class="flex items-center gap-2.5">
                        @if(!empty($globalSettings['app_logo']))
                            <img src="{{ $globalSettings['app_logo'] }}" alt="Logo" class="h-10 max-w-[180px] object-contain">
                        @else
                            <div class="h-9 w-9 rounded-lg bg-blue-600 text-white flex items-center justify-center font-black text-base shadow-sm">
                                S
                            </div>
                        @endif
                        <div>
                            <h1 class="text-base font-extrabold text-slate-900 tracking-tight leading-none uppercase">
                                {{ $globalSettings['app_name'] ?? 'SomitySoft SaaS' }}
                            </h1>
                            <p class="text-[10px] font-semibold text-blue-600 tracking-wide mt-0.5">
                                {{ $globalSettings['app_tagline'] ?? 'Automated Multi-Tenant ISP Cloud Billing' }}
                            </p>
                        </div>
                    </div>

                    <div class="text-[10.5px] text-slate-500 space-y-0.5 pt-1 leading-relaxed">
                        <p class="flex items-center gap-1.5">
                            <i class="fas fa-map-marker-alt text-slate-400 text-[9px]"></i>
                            <span>{{ $globalSettings['company_address'] ?? 'Dhaka, Bangladesh' }}</span>
                        </p>
                        <p class="flex items-center gap-1.5">
                            <i class="fas fa-phone-alt text-slate-400 text-[9px]"></i>
                            <span>{{ $globalSettings['support_phone'] ?? '+880 1700-000000' }}</span>
                            <span class="text-slate-300">•</span>
                            <i class="fas fa-envelope text-slate-400 text-[9px]"></i>
                            <span>{{ $globalSettings['support_email'] ?? 'support@somitysoft.com' }}</span>
                        </p>
                        <p class="flex items-center gap-1.5 font-mono text-[9.5px] text-slate-400">
                            <i class="fas fa-globe text-slate-400 text-[9px]"></i>
                            <span>{{ $globalSettings['app_website_url'] ?? 'https://somitysoft.com' }}</span>
                        </p>
                    </div>
                </div>

                <!-- Voucher Title & Numbers -->
                <div class="text-right space-y-2">
                    <div>
                        <span class="text-[10px] font-extrabold text-blue-600 uppercase tracking-widest block">Official Bill & Money Receipt</span>
                        <h2 class="text-2xl font-black text-slate-900 tracking-tight font-mono uppercase">INVOICE</h2>
                    </div>

                    <div class="bg-slate-50 border border-slate-200 rounded-lg p-2.5 text-[11px] space-y-1 font-mono text-left inline-block min-w-[200px]">
                        <div class="flex justify-between gap-3">
                            <span class="text-slate-500">Invoice No:</span>
                            <strong class="text-slate-900">{{ $invoice->invoice_no }}</strong>
                        </div>
                        <div class="flex justify-between gap-3">
                            <span class="text-slate-500">Issue Date:</span>
                            <span class="text-slate-800">{{ $invoice->created_at ? $invoice->created_at->format('d M Y') : date('d M Y') }}</span>
                        </div>
                        <div class="flex justify-between gap-3">
                            <span class="text-slate-500">Due Date:</span>
                            <span class="text-rose-600 font-bold">{{ $invoice->due_date ? $invoice->due_date->format('d M Y') : date('d M Y') }}</span>
                        </div>
                        <div class="flex justify-between gap-3 pt-1 border-t border-slate-200">
                            <span class="text-slate-500">Billing Cycle:</span>
                            <span class="text-slate-800 font-semibold">{{ $invoice->subscription->billing_cycle ?? 'Monthly' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Client ISP Info (Bill To) & Payment Overview Card -->
            <div class="grid grid-cols-12 gap-4">
                
                <!-- Billed To -->
                <div class="col-span-7 bg-slate-50/70 rounded-xl border border-slate-200 p-3.5 space-y-1.5">
                    <div class="flex items-center justify-between border-b border-slate-200/80 pb-1.5">
                        <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-500 flex items-center gap-1.5">
                            <i class="fas fa-building text-blue-600"></i>
                            <span>Billed To / Subscriber ISP</span>
                        </span>
                        <span class="text-[9.5px] font-mono text-slate-400">ID: #TEN-{{ str_pad($invoice->tenant_id, 4, '0', STR_PAD_LEFT) }}</span>
                    </div>

                    <div>
                        <h3 class="text-sm font-bold text-slate-900 leading-snug">
                            {{ $invoice->tenant->company_name ?? $invoice->tenant->name ?? 'ISP Client' }}
                        </h3>
                    </div>

                    <div class="text-[10.5px] text-slate-600 space-y-0.5 pt-1">
                        <p class="flex items-center gap-1.5">
                            <i class="fas fa-phone text-slate-400 text-[9px]"></i>
                            <span>{{ $invoice->tenant->phone ?? 'N/A' }}</span>
                            <span class="text-slate-300">•</span>
                            <i class="fas fa-envelope text-slate-400 text-[9px]"></i>
                            <span>{{ $invoice->tenant->email ?? 'N/A' }}</span>
                        </p>
                        <p class="flex items-center gap-1.5">
                            <i class="fas fa-map-pin text-slate-400 text-[9px]"></i>
                            <span>{{ $invoice->tenant->address ?? 'Bangladesh' }}</span>
                        </p>
                        @if(!empty($invoice->tenant->slug) || !empty($invoice->tenant->domain))
                            <p class="flex items-center gap-1.5 font-mono text-[9.5px] text-blue-700">
                                <i class="fas fa-link text-blue-500 text-[9px]"></i>
                                <span>{{ $invoice->tenant->domain ?: ($invoice->tenant->slug . '.somitysoft.com') }}</span>
                            </p>
                        @endif
                    </div>
                </div>

                <!-- Payment Status & Real Official Stamp -->
                <div class="col-span-5 bg-slate-50/70 rounded-xl border border-slate-200 p-3.5 flex flex-col justify-between relative overflow-hidden">
                    <div>
                        <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-500 block border-b border-slate-200/80 pb-1.5 mb-2">
                            Settlement & Verification
                        </span>

                        <div class="space-y-1 text-[10.5px] font-mono">
                            <div class="flex justify-between">
                                <span class="text-slate-500">Method:</span>
                                <span class="font-semibold text-slate-800">{{ $invoice->payment_method ?? ($invoice->status === 'paid' ? 'Gateway / Bank' : 'Unsettled') }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Transaction ID:</span>
                                <span class="font-bold text-slate-900">{{ $invoice->trx_id ?? 'PENDING' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Paid At:</span>
                                <span class="text-slate-800">{{ $invoice->paid_at ? \Carbon\Carbon::parse($invoice->paid_at)->format('d M Y, h:i A') : 'N/A' }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Stamp Badge -->
                    <div class="pt-2 text-center">
                        @if($invoice->status === 'paid')
                            <div class="stamp-badge stamp-paid">
                                <i class="fas fa-check-circle mr-1"></i> PAID / SETTLED
                            </div>
                        @else
                            <div class="stamp-badge stamp-unpaid">
                                <i class="fas fa-clock mr-1"></i> PAYMENT DUE
                            </div>
                        @endif
                    </div>
                </div>

            </div>

            <!-- 3. Line Items Table -->
            <div class="border border-slate-200 rounded-xl overflow-hidden shadow-2xs">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-800 text-white uppercase text-[10px] tracking-wider">
                            <th class="py-2.5 px-3.5 text-center w-10">#</th>
                            <th class="py-2.5 px-3.5">Service & Package Details</th>
                            <th class="py-2.5 px-3.5 text-center w-36">Validity Period</th>
                            <th class="py-2.5 px-3.5 text-right w-28">Rate</th>
                            <th class="py-2.5 px-3.5 text-right w-32">Total ({{ $currencyCode ?? 'BDT' }})</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 text-slate-700">
                        @if($invoice->items->isNotEmpty())
                            @foreach($invoice->items as $idx => $item)
                                <tr class="hover:bg-slate-50/50">
                                    <td class="py-3 px-3.5 text-center text-slate-400 font-mono">{{ $idx + 1 }}</td>
                                    <td class="py-3 px-3.5">
                                        <span class="font-bold text-slate-900 block text-xs">{{ $item->description }}</span>
                                        <span class="text-[10px] text-slate-500">Tier: {{ $invoice->plan->name ?? 'SaaS Standard' }} • Limit: {{ number_format($invoice->plan->customer_limit ?? 0) }} Subscribers</span>
                                    </td>
                                    <td class="py-3 px-3.5 text-center font-mono text-[10px] text-slate-600">
                                        {{ $invoice->period_start ? $invoice->period_start->format('d M Y') : date('d M Y') }}<br>
                                        to {{ $invoice->period_end ? $invoice->period_end->format('d M Y') : date('d M Y', strtotime('+1 month')) }}
                                    </td>
                                    <td class="py-3 px-3.5 text-right font-mono">
                                        @currency($item->unit_price)
                                    </td>
                                    <td class="py-3 px-3.5 text-right font-mono font-bold text-slate-900">
                                        @currency($item->total_price)
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr class="hover:bg-slate-50/50">
                                <td class="py-3 px-3.5 text-center text-slate-400 font-mono">1</td>
                                <td class="py-3 px-3.5">
                                    <span class="font-bold text-slate-900 block text-xs">
                                        SaaS Platform License — {{ $invoice->plan->name ?? 'Enterprise ISP Tier' }}
                                    </span>
                                    <span class="text-[10.5px] text-slate-500 leading-tight block mt-0.5">
                                        Full multi-tenant automation, RADIUS/MikroTik sync, billing gateway and automated SMS reminders.
                                        Max Capacity: <strong>{{ number_format($invoice->plan->customer_limit ?? 0) }}</strong> Subscribers.
                                    </span>
                                </td>
                                <td class="py-3 px-3.5 text-center font-mono text-[10.5px] text-slate-600">
                                    {{ $invoice->period_start ? $invoice->period_start->format('d M Y') : date('d M Y') }}<br>
                                    <span class="text-slate-400 text-[9.5px]">to</span> {{ $invoice->period_end ? $invoice->period_end->format('d M Y') : date('d M Y', strtotime('+1 month')) }}
                                </td>
                                <td class="py-3 px-3.5 text-right font-mono text-slate-700">
                                    @currency($invoice->amount)
                                </td>
                                <td class="py-3 px-3.5 text-right font-mono font-bold text-slate-900">
                                    @currency($invoice->amount)
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            <!-- 4. Financial Calculations & In-Words -->
            <div class="grid grid-cols-12 gap-4 items-start">
                
                <!-- Left: Amount in Words & Notes -->
                <div class="col-span-7 space-y-3">
                    <div class="p-3 bg-blue-50/50 border border-blue-200/70 rounded-xl space-y-1">
                        <span class="text-[9.5px] font-bold uppercase tracking-wider text-blue-700 block">Total Amount in Words:</span>
                        <p class="text-xs font-extrabold text-slate-900 italic">
                            "{{ $amountInWords }}"
                        </p>
                    </div>

                    <div class="text-[10px] text-slate-500 space-y-1 p-2 rounded-lg bg-slate-50 border border-slate-200">
                        <p class="font-semibold text-slate-700 flex items-center gap-1">
                            <i class="fas fa-shield-alt text-blue-500"></i> Terms & Important Instructions:
                        </p>
                        <p>1. All SaaS subscription fees are billed in advance per billing period.</p>
                        <p>2. Subscription renewal ensures uninterrupted cloud syncing, RADIUS authentication, and tenant services.</p>
                        <p>3. Payments settled through bKash, Nagad, or Bank Transfer are reconciled automatically in real-time.</p>
                    </div>
                </div>

                <!-- Right: Financial Summary Totals -->
                <div class="col-span-5 bg-slate-50 border border-slate-200 rounded-xl p-3.5 space-y-2 text-xs font-mono">
                    <div class="flex justify-between text-slate-600">
                        <span>Subtotal:</span>
                        <span class="font-semibold">@currency($invoice->subtotal ?: $invoice->amount)</span>
                    </div>

                    @if(!empty($invoice->discount) && $invoice->discount > 0)
                        <div class="flex justify-between text-emerald-600">
                            <span>Promotional Discount:</span>
                            <span>- @currency($invoice->discount)</span>
                        </div>
                    @endif

                    @if(!empty($invoice->tax) && $invoice->tax > 0)
                        <div class="flex justify-between text-slate-600">
                            <span>Tax / VAT:</span>
                            <span>+ @currency($invoice->tax)</span>
                        </div>
                    @endif

                    <div class="border-t border-slate-300 pt-2 flex justify-between text-sm font-bold text-slate-900">
                        <span>Grand Total:</span>
                        <span class="text-blue-700 font-extrabold">@currency($invoice->amount)</span>
                    </div>

                    <div class="border-t border-dashed border-slate-200 pt-1.5 flex justify-between text-emerald-700 font-semibold text-[11px]">
                        <span>Paid Amount:</span>
                        <span>@currency($invoice->paid_amount)</span>
                    </div>

                    <div class="border-t-2 border-slate-800 pt-1.5 flex justify-between font-extrabold {{ $invoice->calculated_due > 0 ? 'text-rose-600' : 'text-slate-800' }}">
                        <span>Due Balance:</span>
                        <span class="text-sm">@currency($invoice->calculated_due)</span>
                    </div>
                </div>

            </div>

        </div>

        <!-- 5. Bottom Section: QR Verification, Signatures & Digital Seal -->
        <div class="mt-8 pt-6 border-t-2 border-slate-200 space-y-6">
            
            <div class="grid grid-cols-12 gap-4 items-end">
                
                <!-- QR Code Verification -->
                <div class="col-span-4 flex items-center gap-3">
                    <div class="p-1.5 bg-white border border-slate-300 rounded-lg shadow-2xs flex-shrink-0">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=95x95&data={{ urlencode(route('owner.invoices.print', $invoice)) }}" 
                             alt="QR Code" class="w-20 h-20">
                    </div>
                    <div class="text-[9.5px] text-slate-500 leading-tight">
                        <span class="font-bold text-slate-800 block text-[10px] uppercase">Official QR Verify</span>
                        Scan with your camera to confirm digital validity on {{ parse_url($globalSettings['app_website_url'] ?? url('/'), PHP_URL_HOST) }}.
                    </div>
                </div>

                <!-- Client Receiver Sign -->
                <div class="col-span-4 text-center">
                    <div class="h-10"></div>
                    <div class="border-t border-slate-300 pt-1">
                        <span class="text-[10px] font-bold text-slate-600 uppercase block">Subscriber / Client Signature</span>
                        <span class="text-[9px] text-slate-400">Authorized ISP Officer</span>
                    </div>
                </div>

                <!-- Platform Authorized Seal -->
                <div class="col-span-4 text-center">
                    <div class="h-10 flex items-center justify-center">
                        <span class="font-mono text-[9.5px] text-emerald-700 uppercase tracking-widest font-extrabold border border-emerald-500/40 bg-emerald-50 px-2 py-0.5 rounded">
                            ✓ Digitally Certified
                        </span>
                    </div>
                    <div class="border-t border-slate-800 pt-1">
                        <span class="text-[10px] font-bold text-slate-900 uppercase block">Authorized Signatory</span>
                        <span class="text-[9px] text-slate-500 font-semibold">{{ $globalSettings['app_name'] ?? 'SomitySoft SaaS' }} Accounts Dept.</span>
                    </div>
                </div>

            </div>

            <!-- Footer Meta & Timestamp -->
            <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-[9px] text-slate-400 font-mono">
                <div>
                    {{ $globalSettings['customer_portal_footer'] ?? 'Powered by SomitySoft Cloud ISP Automation Platform' }}
                </div>
                <div class="text-right">
                    <span>Generated: {{ now()->format('d M Y, h:i A') }} • System Hash: {{ strtoupper(substr(md5($invoice->id . $invoice->invoice_no), 0, 10)) }}</span>
                </div>
            </div>

        </div>

    </div>

</body>
</html>
