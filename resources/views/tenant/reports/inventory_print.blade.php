<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Equipment & Inventory Statement - {{ $tenant->company_name ?? $tenant->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @media print {
            @page {
                size: A4 portrait;
                margin: 12mm 12mm 12mm 12mm;
            }
            body {
                background: white !important;
                color: black !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .no-print {
                display: none !important;
            }
            .page-break {
                page-break-before: always;
            }
        }
        body {
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            border: 1px solid #cbd5e1;
            padding: 5px 8px;
            font-size: 11px;
        }
        th {
            background-color: #f1f5f9;
            font-weight: 700;
        }
        tr:nth-child(even) td {
            background-color: #f8fafc;
        }
    </style>
</head>
<body class="bg-slate-100 p-4 sm:p-6 text-slate-800">

    {{-- Top Action Floating Bar --}}
    <div class="max-w-4xl mx-auto mb-4 no-print flex items-center justify-between bg-white p-3 rounded-xl border border-slate-200 shadow-sm">
        <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
            <span class="text-xs font-bold text-slate-700">Official Equipment &amp; Stock Statement</span>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-4 py-1.5 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-print text-xs"></i>
                <span>Print Document</span>
            </button>
            <button onclick="window.close()" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-medium border border-slate-300 transition cursor-pointer">
                Close
            </button>
        </div>
    </div>

    {{-- Printable Document Canvas --}}
    <div class="max-w-4xl mx-auto bg-white p-6 sm:p-8 rounded-xl border border-slate-200 shadow-sm space-y-5">
        
        {{-- Header Block --}}
        <div class="flex items-start justify-between border-b-2 border-slate-800 pb-4">
            <div>
                <h1 class="text-xl font-black tracking-tight text-slate-900 uppercase">
                    {{ $tenant->company_name ?? $tenant->name }}
                </h1>
                <p class="text-xs text-slate-600 font-medium mt-0.5">
                    {{ $tenant->address ?? 'Central NOC & Core POP Infrastructure, Bangladesh' }}
                </p>
                <p class="text-xs text-slate-600">
                    Phone: {{ $tenant->phone ?? 'N/A' }} | Email: {{ $tenant->email ?? 'noc@isp.local' }}
                </p>
            </div>
            <div class="text-right">
                <span class="inline-block px-2.5 py-1 bg-slate-900 text-white font-mono text-[10px] font-bold rounded uppercase tracking-wider">
                    Equipment Audit Statement
                </span>
                <p class="text-[11px] text-slate-500 font-mono mt-1.5">Date: {{ date('d M Y, h:i A') }}</p>
                <p class="text-[10px] text-slate-400 font-mono">Ref: INV-AUDIT-{{ date('Ymd') }}</p>
            </div>
        </div>

        {{-- KPI Executive Summary Strip --}}
        <div class="grid grid-cols-4 gap-3 bg-slate-50 p-3 rounded-lg border border-slate-200">
            <div>
                <span class="text-[9px] font-bold uppercase text-slate-500 block">Total Asset Valuation</span>
                <span class="text-sm font-black font-mono text-emerald-700 block">@currency($totalAssetValuation)</span>
            </div>
            <div>
                <span class="text-[9px] font-bold uppercase text-slate-500 block">Deployed ONUs</span>
                <span class="text-sm font-black font-mono text-indigo-700 block">{{ number_format($totalOnusInDb) }} Units</span>
            </div>
            <div>
                <span class="text-[9px] font-bold uppercase text-slate-500 block">Core POP Nodes</span>
                <span class="text-sm font-black font-mono text-cyan-700 block">{{ $totalOlts + $totalRouters }} Nodes</span>
            </div>
            <div>
                <span class="text-[9px] font-bold uppercase text-slate-500 block">Audit Status</span>
                <span class="text-sm font-black text-emerald-700 block">Reconciled</span>
            </div>
        </div>

        {{-- Master Inventory Statement Table --}}
        <div>
            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-2">Master Hardware &amp; Materials Ledger</h3>
            <table>
                <thead>
                    <tr>
                        <th class="w-8 text-center">SL</th>
                        <th>Item Code</th>
                        <th>Item Description &amp; Specification</th>
                        <th>Category</th>
                        <th>Brand</th>
                        <th class="text-center">Unit</th>
                        <th class="text-right">Stock</th>
                        <th class="text-right">Deployed</th>
                        <th class="text-right">Total</th>
                        <th class="text-right">Unit Price</th>
                        <th class="text-right">Valuation</th>
                    </tr>
                </thead>
                <tbody>
                    @php $grandTotal = 0; @endphp
                    @foreach($catalog as $idx => $item)
                        @php
                            $tot = $item['in_stock'] + $item['deployed'];
                            $val = $tot * $item['unit_price'];
                            $grandTotal += $val;
                        @endphp
                        <tr>
                            <td class="text-center font-mono text-slate-500">{{ $idx + 1 }}</td>
                            <td class="font-mono font-bold text-cyan-900">{{ $item['sku'] }}</td>
                            <td class="font-medium text-slate-900">{{ $item['item_name'] }}</td>
                            <td class="text-[10.5px] text-slate-600">{{ $item['category'] }}</td>
                            <td class="font-semibold text-slate-800">{{ $item['brand'] }}</td>
                            <td class="text-center text-slate-600">{{ $item['unit'] }}</td>
                            <td class="text-right font-mono font-bold text-slate-800">{{ number_format($item['in_stock']) }}</td>
                            <td class="text-right font-mono font-bold text-indigo-800">{{ number_format($item['deployed']) }}</td>
                            <td class="text-right font-mono font-bold text-slate-900">{{ number_format($tot) }}</td>
                            <td class="text-right font-mono text-slate-700">@currency($item['unit_price'])</td>
                            <td class="text-right font-mono font-bold text-emerald-800">@currency($val)</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-slate-100 font-bold">
                    <tr>
                        <td colspan="6" class="text-right uppercase text-slate-700 py-2">Grand Total Asset Valuation:</td>
                        <td class="text-right font-mono text-slate-800">{{ number_format(collect($catalog)->sum('in_stock')) }}</td>
                        <td class="text-right font-mono text-indigo-800">{{ number_format(collect($catalog)->sum('deployed')) }}</td>
                        <td class="text-right font-mono text-slate-900">{{ number_format(collect($catalog)->sum(fn($i) => $i['in_stock'] + $i['deployed'])) }}</td>
                        <td class="text-right font-mono text-slate-500">-</td>
                        <td class="text-right font-mono font-black text-emerald-900 text-xs">@currency($grandTotal)</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        {{-- Core POP Hardware Infrastructure Table --}}
        <div>
            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-2">Core POP Nodes &amp; Central OLTs</h3>
            <table>
                <thead>
                    <tr>
                        <th class="w-8 text-center">SL</th>
                        <th>Device Name</th>
                        <th>Type / Model</th>
                        <th>IP Address</th>
                        <th class="text-center">Capacity</th>
                        <th class="text-right">Connected ONUs</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @php $sl = 1; @endphp
                    @foreach($coreOlts as $olt)
                        <tr>
                            <td class="text-center font-mono">{{ $sl++ }}</td>
                            <td class="font-bold text-slate-900">{{ $olt->name }}</td>
                            <td class="font-mono text-[10.5px]">OLT: {{ $olt->brand }} {{ $olt->model }}</td>
                            <td class="font-mono text-cyan-900 font-semibold">{{ $olt->ip_address }}</td>
                            <td class="text-center font-mono">{{ $olt->total_pon_ports ?: 8 }} PON Ports</td>
                            <td class="text-right font-mono font-bold text-indigo-800">{{ number_format($olt->total_onus_count) }}</td>
                            <td class="text-center font-bold text-emerald-700 text-[10px]">ONLINE</td>
                        </tr>
                    @endforeach
                    @foreach($coreRouters as $rtr)
                        <tr>
                            <td class="text-center font-mono">{{ $sl++ }}</td>
                            <td class="font-bold text-slate-900">{{ $rtr->name }}</td>
                            <td class="font-mono text-[10.5px]">Router: {{ $rtr->model ?: 'MikroTik CCR' }}</td>
                            <td class="font-mono text-cyan-900 font-semibold">{{ $rtr->ip_address }}</td>
                            <td class="text-center font-mono">BGP / Core Gateway</td>
                            <td class="text-right font-mono font-bold text-indigo-800">-</td>
                            <td class="text-center font-bold text-emerald-700 text-[10px]">ACTIVE</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- 4-Tier Statutory Signature Matrix --}}
        <div class="pt-8 grid grid-cols-4 gap-4 text-center mt-6">
            <div class="border-t border-slate-400 pt-2">
                <span class="text-[11px] font-bold text-slate-800 block">Store Officer</span>
                <span class="text-[9px] text-slate-500 block">Inventory &amp; Warehousing</span>
            </div>
            <div class="border-t border-slate-400 pt-2">
                <span class="text-[11px] font-bold text-slate-800 block">NOC Engineer</span>
                <span class="text-[9px] text-slate-500 block">Hardware &amp; Fiber Splicing</span>
            </div>
            <div class="border-t border-slate-400 pt-2">
                <span class="text-[11px] font-bold text-slate-800 block">Accounts Manager</span>
                <span class="text-[9px] text-slate-500 block">Asset Valuation &amp; Audit</span>
            </div>
            <div class="border-t border-slate-400 pt-2">
                <span class="text-[11px] font-bold text-slate-800 block">Managing Director</span>
                <span class="text-[9px] text-slate-500 block">Executive Approval</span>
            </div>
        </div>

    </div>

</body>
</html>
