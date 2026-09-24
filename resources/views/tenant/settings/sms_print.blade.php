<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMS &amp; WhatsApp Notification Gateway Configuration Audit</title>
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
            padding: 6px 10px;
            font-size: 11px;
        }
        th {
            background-color: #f1f5f9;
            font-weight: 700;
        }
    </style>
</head>
<body class="bg-slate-100 p-4 sm:p-6 text-slate-800">

    {{-- Top Action Floating Bar --}}
    <div class="max-w-3xl mx-auto mb-4 no-print flex items-center justify-between bg-white p-3 rounded-xl border border-slate-200 shadow-sm">
        <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-cyan-600"></span>
            <span class="text-xs font-bold text-slate-700">Official SMS &amp; WhatsApp Gateway Configuration Audit</span>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-4 py-1.5 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-print text-xs"></i>
                <span>Print Configuration Sheet</span>
            </button>
            <button onclick="window.close()" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-medium border border-slate-300 transition cursor-pointer">
                Close
            </button>
        </div>
    </div>

    {{-- Printable Document Canvas --}}
    <div class="max-w-3xl mx-auto bg-white p-6 sm:p-8 rounded-xl border border-slate-200 shadow-sm space-y-5">
        
        {{-- Header Block --}}
        <div class="flex items-start justify-between border-b-2 border-slate-800 pb-4">
            <div>
                <h1 class="text-xl font-black tracking-tight text-slate-900 uppercase">
                    {{ $tenant->company_name ?? $tenant->name }}
                </h1>
                <p class="text-xs text-slate-600 font-medium mt-0.5">
                    Telecommunications Messaging &amp; Automated Customer Notification Infrastructure
                </p>
                <p class="text-xs text-slate-600">
                    BTRC Reg: {{ $tenant->btrc_license_no ?? 'BTRC-ISP-NW' }} | Generated: {{ date('d M Y, h:i A') }}
                </p>
            </div>
            <div class="text-right">
                <span class="inline-block px-3 py-1 bg-slate-900 text-white font-mono text-xs font-bold rounded uppercase tracking-wider">
                    GATEWAY AUDIT
                </span>
                <p class="text-sm font-bold font-mono text-cyan-800 mt-1.5">GW-CONFIG-{{ date('Ym') }}</p>
                <p class="text-[10px] text-slate-400 font-mono">Status: Active &amp; Verified</p>
            </div>
        </div>

        {{-- Gateway Routes Table --}}
        <div>
            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-2">Configured Messaging Gateways &amp; Routes</h3>
            <table>
                <thead>
                    <tr>
                        <th class="w-8 text-center">#</th>
                        <th class="text-left">Gateway Connection Name</th>
                        <th class="text-center">Channel</th>
                        <th class="text-center">Provider</th>
                        <th class="text-center">Sender ID / Masking</th>
                        <th class="text-right">Unit Rate</th>
                        <th class="text-right">Credit Balance</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($gateways as $idx => $gw)
                        <tr>
                            <td class="text-center font-mono text-slate-400">{{ $idx + 1 }}</td>
                            <td class="font-bold text-slate-900">{{ $gw->name }} {{ $gw->is_default ? '(Default)' : '' }}</td>
                            <td class="text-center font-mono uppercase text-xs font-semibold text-blue-700">{{ $gw->channel_type }}</td>
                            <td class="text-center font-mono text-xs">{{ strtoupper($gw->provider) }}</td>
                            <td class="text-center font-mono font-bold text-indigo-700">{{ $gw->sender_id ?: 'Non-Masking' }}</td>
                            <td class="text-right font-mono">@currency($gw->cost_per_sms)</td>
                            <td class="text-right font-mono font-bold text-emerald-700">@currency($gw->balance)</td>
                            <td class="text-center font-bold font-mono text-emerald-700">{{ $gw->is_active ? 'ACTIVE' : 'DISABLED' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Active Notification Event Templates --}}
        <div>
            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-2">Automated Event Triggers &amp; Notification Templates</h3>
            <table>
                <thead>
                    <tr>
                        <th class="w-8 text-center">#</th>
                        <th class="text-left">Lifecycle Event Trigger</th>
                        <th class="text-center">SMS</th>
                        <th class="text-center">WhatsApp</th>
                        <th class="text-left">Template Text Content</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($templates as $idx => $tpl)
                        <tr>
                            <td class="text-center font-mono text-slate-400">{{ $idx + 1 }}</td>
                            <td class="font-bold text-slate-900 whitespace-nowrap">{{ $tpl->title }}</td>
                            <td class="text-center font-bold {{ $tpl->send_sms ? 'text-emerald-700' : 'text-slate-400' }}">{{ $tpl->send_sms ? 'YES' : 'NO' }}</td>
                            <td class="text-center font-bold {{ $tpl->send_whatsapp ? 'text-emerald-700' : 'text-slate-400' }}">{{ $tpl->send_whatsapp ? 'YES' : 'NO' }}</td>
                            <td class="font-mono text-[10px] text-slate-700">{{ $tpl->sms_body }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Dual Sign-off Blocks --}}
        <div class="pt-10 grid grid-cols-2 gap-8 text-center mt-6">
            <div class="border-t border-slate-400 pt-2">
                <span class="text-xs font-bold text-slate-800 block">IT Infrastructure &amp; Automation Lead</span>
                <span class="text-[10px] text-slate-500 block">System Engineering Operations</span>
            </div>
            <div class="border-t border-slate-400 pt-2">
                <span class="text-xs font-bold text-slate-800 block">Chief Operating Officer (COO)</span>
                <span class="text-[10px] text-slate-500 block">Commercial Communications &amp; Customer Success</span>
            </div>
        </div>

    </div>

</body>
</html>
