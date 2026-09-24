<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation & Activation Slip - {{ $task->task_number }}</title>
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
            <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
            <span class="text-xs font-bold text-slate-700">Official Connection &amp; Activation Agreement</span>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-print text-xs"></i>
                <span>Print Activation Slip</span>
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
                    Broadband Internet Provisioning &amp; Customer Activation Division
                </p>
                <p class="text-xs text-slate-600">
                    Helpdesk: {{ $tenant->phone ?? 'N/A' }} | Web: www.{{ strtolower(str_replace(' ', '', $tenant->name ?? 'isp')) }}.com
                </p>
            </div>
            <div class="text-right">
                <span class="inline-block px-3 py-1 bg-slate-900 text-white font-mono text-xs font-bold rounded uppercase tracking-wider">
                    ACTIVATION SLIP
                </span>
                <p class="text-sm font-bold font-mono text-blue-700 mt-1.5">{{ $task->task_number }}</p>
                <p class="text-[10px] text-slate-400 font-mono">Date: {{ date('d M Y') }}</p>
            </div>
        </div>

        {{-- Subscriber & Account Matrix --}}
        <div>
            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-2">Subscriber &amp; Location Information</h3>
            <table>
                <tr>
                    <td class="w-1/3 bg-slate-50 font-bold text-slate-600">Subscriber Name:</td>
                    <td class="w-2/3 font-bold text-slate-900">{{ $task->applicant_name }}</td>
                </tr>
                <tr>
                    <td class="bg-slate-50 font-bold text-slate-600">Contact Mobile:</td>
                    <td class="font-mono font-bold text-blue-700">{{ $task->applicant_phone }}</td>
                </tr>
                <tr>
                    <td class="bg-slate-50 font-bold text-slate-600">Installation Address:</td>
                    <td class="text-slate-800">{{ $task->installation_address }}</td>
                </tr>
                <tr>
                    <td class="bg-slate-50 font-bold text-slate-600">Coverage Zone:</td>
                    <td class="font-semibold text-slate-800">{{ $task->coverageZone?->name ?? 'Primary Coverage Area' }}</td>
                </tr>
            </table>
        </div>

        {{-- Package Plan & Technical Provisioning Details --}}
        <div>
            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-2">Service Plan &amp; Hardware Telemetry</h3>
            <table>
                <tr>
                    <td class="w-1/3 bg-slate-50 font-bold text-slate-600">Subscribed Internet Plan:</td>
                    <td class="w-2/3 font-bold text-indigo-800">{{ $task->package?->name ?? 'High Speed Broadband' }} ({{ $task->package?->download_speed ?? 20 }} Mbps)</td>
                </tr>
                <tr>
                    <td class="bg-slate-50 font-bold text-slate-600">Optical Splitter &amp; Pole:</td>
                    <td class="text-slate-800 font-mono">{{ $task->splitter_location ?: 'Nearest Optical POP Distribution' }}</td>
                </tr>
                <tr>
                    <td class="bg-slate-50 font-bold text-slate-600">Drop Cable Length:</td>
                    <td class="font-mono font-bold text-slate-800">{{ $task->cable_length_meters }} Meters</td>
                </tr>
                <tr>
                    <td class="bg-slate-50 font-bold text-slate-600">ONU Model &amp; MAC Serial:</td>
                    <td class="font-mono text-cyan-900 font-bold">{{ $task->onu_model ?: 'VSOL Gigabit' }} ({{ $task->onu_mac_serial ?: 'Auto-Provisioned' }})</td>
                </tr>
                <tr>
                    <td class="bg-slate-50 font-bold text-slate-600">Tested Optical RX Power:</td>
                    <td class="font-mono font-bold text-emerald-700">{{ $task->optical_rx_power ?: '-18.5 dBm (Optimal)' }}</td>
                </tr>
            </table>
        </div>

        {{-- Billing & Financial Settlement Matrix --}}
        <div>
            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-2">Financial Account Settlement</h3>
            <table>
                <thead>
                    <tr>
                        <th class="w-1/2 text-left">Fee Item Description</th>
                        <th class="w-1/2 text-right">Settled Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="font-medium text-slate-700">Initial Connection &amp; Fiber Setup Fee:</td>
                        <td class="text-right font-mono font-bold text-slate-900">@currency($task->connection_fee)</td>
                    </tr>
                    <tr>
                        <td class="font-medium text-slate-700">Advance Monthly Subscription Paid:</td>
                        <td class="text-right font-mono font-bold text-emerald-800">@currency($task->advance_payment)</td>
                    </tr>
                    <tr>
                        <td class="font-bold text-slate-800 bg-slate-50">Total Amount Paid at Installation:</td>
                        <td class="text-right font-mono font-bold text-indigo-900 bg-slate-50 text-xs">@currency($task->connection_fee + $task->advance_payment)</td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Dual Sign-off Blocks --}}
        <div class="pt-10 grid grid-cols-2 gap-8 text-center mt-6">
            <div class="border-t border-slate-400 pt-2">
                <span class="text-xs font-bold text-slate-800 block">NOC Installation Engineer</span>
                <span class="text-[10px] text-slate-500 block">{{ $task->assigned_technician_name ?: 'Authorized Technician' }}</span>
            </div>
            <div class="border-t border-slate-400 pt-2">
                <span class="text-xs font-bold text-slate-800 block">Subscriber Acceptance &amp; Signature</span>
                <span class="text-[10px] text-slate-500 block">Acknowledging physical setup and speed test</span>
            </div>
        </div>

    </div>

</body>
</html>
