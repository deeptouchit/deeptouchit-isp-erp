<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Automation &amp; Auto-Cut Rules - {{ $tenant->company_name ?? $tenant->name }}</title>
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

    {{-- Top Action Bar --}}
    <div class="max-w-3xl mx-auto mb-4 no-print flex items-center justify-between bg-white p-3 rounded-xl border border-slate-200 shadow-sm">
        <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-cyan-600"></span>
            <span class="text-xs font-bold text-slate-700">Automation &amp; Auto-Cut Rules Summary</span>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-4 py-1.5 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-print text-xs"></i>
                <span>Print Rules</span>
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
                    Automated Billing &amp; Auto-Cut Settings Report
                </p>
                <p class="text-xs text-slate-600">
                    Generated: {{ date('d M Y, h:i A') }}
                </p>
            </div>
            <div class="text-right">
                <span class="inline-block px-3 py-1 bg-slate-900 text-white font-mono text-xs font-bold rounded uppercase tracking-wider">
                    AUTOMATION RULES
                </span>
                <p class="text-sm font-bold font-mono text-cyan-700 mt-1.5">AUTO-RULES-{{ date('Ym') }}</p>
                <p class="text-[10px] text-slate-400 font-mono">Status: Active</p>
            </div>
        </div>

        {{-- Policy Rules Matrix --}}
        <div>
            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-2">Active Automation Rules</h3>
            <table>
                <tr>
                    <td class="w-1/3 bg-slate-50 font-bold text-slate-600">Auto-Cut Status:</td>
                    <td class="w-2/3 font-bold font-mono text-emerald-700">{{ $settings->auto_cut_enabled ? 'ENABLED' : 'DISABLED' }}</td>
                </tr>
                <tr>
                    <td class="bg-slate-50 font-bold text-slate-600">Grace Period:</td>
                    <td class="font-bold text-slate-900 font-mono">{{ $settings->grace_period_days }} Days after Bill Date</td>
                </tr>
                <tr>
                    <td class="bg-slate-50 font-bold text-slate-600">Disconnection Action:</td>
                    <td class="font-mono text-indigo-700 font-semibold">{{ strtoupper(str_replace('_', ' ', $settings->auto_cut_action)) }}</td>
                </tr>
                <tr>
                    <td class="bg-slate-50 font-bold text-slate-600">Daily Auto-Cut Run Time:</td>
                    <td class="font-mono text-slate-800">{{ $settings->auto_cut_time }}</td>
                </tr>
                <tr>
                    <td class="bg-slate-50 font-bold text-slate-600">Minimum Due Threshold:</td>
                    <td class="font-mono font-bold text-slate-900">@currency($settings->min_due_threshold)</td>
                </tr>
                <tr>
                    <td class="bg-slate-50 font-bold text-slate-600">Monthly Bill Generation:</td>
                    <td class="text-slate-800">{{ $settings->billing_generation_day }}th of each month at {{ $settings->billing_generation_time }}</td>
                </tr>
                <tr>
                    <td class="bg-slate-50 font-bold text-slate-600">Auto-Reconnect on Payment:</td>
                    <td class="font-bold text-emerald-700 font-mono">Instant (MikroTik / RADIUS)</td>
                </tr>
                <tr>
                    <td class="bg-slate-50 font-bold text-slate-600">Automatic Backup:</td>
                    <td class="text-slate-800 font-mono">{{ ucfirst($settings->backup_frequency) }} at {{ $settings->backup_time }} (Keep for {{ $settings->backup_retention_days }} Days)</td>
                </tr>
            </table>
        </div>

        {{-- Recent Automation Execution Audits --}}
        <div>
            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-2">Recent Activity Logs</h3>
            <table>
                <thead>
                    <tr>
                        <th class="w-8 text-center">#</th>
                        <th class="text-left">Task Name</th>
                        <th class="text-center">Trigger</th>
                        <th class="text-center">Duration</th>
                        <th class="text-left">Result Summary</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentLogs as $idx => $l)
                        <tr>
                            <td class="text-center font-mono text-slate-400">{{ $idx + 1 }}</td>
                            <td class="font-bold text-slate-900">{{ $l->task_name }}</td>
                            <td class="text-center font-mono text-[10px]">{{ strtoupper($l->triggered_by ?? 'CRON') }}</td>
                            <td class="text-center font-mono text-[10px]">{{ $l->duration_ms }} ms</td>
                            <td class="text-[10.5px] text-slate-700">{{ $l->output_summary }}</td>
                            <td class="text-center font-bold font-mono text-emerald-700">{{ strtoupper($l->status) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-slate-400 py-3">No activity logs found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Dual Sign-off Blocks --}}
        <div class="pt-10 grid grid-cols-2 gap-8 text-center mt-6">
            <div class="border-t border-slate-400 pt-2">
                <span class="text-xs font-bold text-slate-800 block">Billing In-Charge</span>
                <span class="text-[10px] text-slate-500 block">Accounts Department</span>
            </div>
            <div class="border-t border-slate-400 pt-2">
                <span class="text-xs font-bold text-slate-800 block">Network Administrator</span>
                <span class="text-[10px] text-slate-500 block">Technical Department</span>
            </div>
        </div>

    </div>

</body>
</html>
