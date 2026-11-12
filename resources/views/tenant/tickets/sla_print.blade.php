<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Staff Performance & SLA Report - :company', ['company' => $tenant->company_name ?? $tenant->name]) }}</title>
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
            <span class="text-xs font-bold text-slate-700">{{ __('Staff Performance & SLA Analytics Summary') }}</span>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-4 py-1.5 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-print text-xs"></i>
                <span>{{ __('Print Report') }}</span>
            </button>
            <button onclick="window.close()" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-medium border border-slate-300 transition cursor-pointer">
                {{ __('Close') }}
            </button>
        </div>
    </div>

    @php
        $periodLabel = match($period) {
            '7_days' => __('Last 7 Days'),
            '30_days' => __('Last 30 Days'),
            '90_days' => __('Last 90 Days'),
            'this_month' => __('This Month'),
            'all' => __('All Time'),
            default => __(ucwords(str_replace('_', ' ', $period))),
        };
    @endphp

    {{-- Printable Document Canvas --}}
    <div class="max-w-3xl mx-auto bg-white p-6 sm:p-8 rounded-xl border border-slate-200 shadow-sm space-y-5">
        
        {{-- Header Block --}}
        <div class="flex items-start justify-between border-b-2 border-slate-800 pb-4">
            <div>
                <h1 class="text-xl font-black tracking-tight text-slate-900 uppercase">
                    {{ $tenant->company_name ?? $tenant->name }}
                </h1>
                <p class="text-xs text-slate-600 font-medium mt-0.5">
                    {{ __('Customer Support & Technical Staff Performance Report') }}
                </p>
                <p class="text-xs text-slate-600">
                    {{ __('Period: :period | Generated: :date', ['period' => $periodLabel, 'date' => now()->translatedFormat('d M Y, h:i A')]) }}
                </p>
            </div>
            <div class="text-right">
                <span class="inline-block px-3 py-1 bg-slate-900 text-white font-mono text-xs font-bold rounded uppercase tracking-wider">
                    {{ __('SLA REPORT') }}
                </span>
                <p class="text-sm font-bold font-mono text-cyan-700 mt-1.5">SLA-{{ date('Ym') }}</p>
                <p class="text-[10px] text-slate-400 font-mono">{{ __('Status: :status', ['status' => __('Active')]) }}</p>
            </div>
        </div>

        {{-- Executive Summary & Benchmarks --}}
        <div>
            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-2">{{ __('Key Performance Summary') }}</h3>
            <div class="grid grid-cols-4 gap-2 text-center">
                <div class="p-2 bg-slate-50 rounded border border-slate-200">
                    <span class="text-[9px] text-slate-500 uppercase block font-bold">{{ __('On-Time Rate') }}</span>
                    <span class="text-sm font-bold font-mono text-emerald-700">96.8%</span>
                </div>
                <div class="p-2 bg-slate-50 rounded border border-slate-200">
                    <span class="text-[9px] text-slate-500 uppercase block font-bold">{{ __('Avg Response Time') }}</span>
                    <span class="text-sm font-bold font-mono text-cyan-800">{{ __(':mins Mins', ['mins' => 14]) }}</span>
                </div>
                <div class="p-2 bg-slate-50 rounded border border-slate-200">
                    <span class="text-[9px] text-slate-500 uppercase block font-bold">{{ __('Avg Solve Time') }}</span>
                    <span class="text-sm font-bold font-mono text-blue-800">{{ __(':hours Hours', ['hours' => '1.8']) }}</span>
                </div>
                <div class="p-2 bg-slate-50 rounded border border-slate-200">
                    <span class="text-[9px] text-slate-500 uppercase block font-bold">{{ __('First Call Solved') }}</span>
                    <span class="text-sm font-bold font-mono text-purple-800">78.4%</span>
                </div>
            </div>
        </div>

        {{-- Category SLA Attainment Matrix --}}
        <div>
            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-2">{{ __('Problem Categories & Solve Time') }}</h3>
            <table>
                <thead>
                    <tr>
                        <th class="text-left">{{ __('Problem Category') }}</th>
                        <th class="text-center">{{ __('Target Time') }}</th>
                        <th class="text-center">{{ __('Actual Solve Time') }}</th>
                        <th class="text-center">{{ __('On-Time Rate') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($categoryBreakdown as $cat)
                        <tr>
                            <td class="font-medium text-slate-900">{{ $cat['name'] }}</td>
                            <td class="text-center font-mono text-slate-600">{{ $cat['target'] }}</td>
                            <td class="text-center font-mono font-bold text-indigo-700">{{ $cat['actual'] }}</td>
                            <td class="text-center font-mono font-bold text-emerald-700">{{ $cat['sla_pct'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Engineer & Technician Leaderboard Scorecard --}}
        <div>
            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-2">{{ __('Staff & Field Engineer Performance') }}</h3>
            <table>
                <thead>
                    <tr>
                        <th class="w-8 text-center">#</th>
                        <th class="text-left">{{ __('Staff Name') }}</th>
                        <th class="text-left">{{ __('Role / Position') }}</th>
                        <th class="text-center">{{ __('Assigned') }}</th>
                        <th class="text-center">{{ __('Resolved') }}</th>
                        <th class="text-center">{{ __('Avg Solve Time') }}</th>
                        <th class="text-center">{{ __('On-Time Rate') }}</th>
                        <th class="text-center">{{ __('Rating') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($scorecards as $idx => $sc)
                        <tr>
                            <td class="text-center font-mono text-slate-400">{{ $idx + 1 }}</td>
                            <td class="font-bold text-slate-900">{{ $sc->name }}</td>
                            <td class="text-slate-600">{{ $sc->role }}</td>
                            <td class="text-center font-mono text-slate-800">{{ $sc->assigned }}</td>
                            <td class="text-center font-mono font-bold text-indigo-700">{{ $sc->resolved }}</td>
                            <td class="text-center font-mono text-slate-800">{{ $sc->avg_time }}</td>
                            <td class="text-center font-mono font-bold text-emerald-700">{{ $sc->compliance }}</td>
                            <td class="text-center font-mono font-bold text-purple-700">{{ $sc->grade }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Dual Sign-off Blocks --}}
        <div class="pt-10 grid grid-cols-2 gap-8 text-center mt-6">
            <div class="border-t border-slate-400 pt-2">
                <span class="text-xs font-bold text-slate-800 block">{{ __('Support In-Charge') }}</span>
                <span class="text-[10px] text-slate-500 block">{{ __('Customer Service Department') }}</span>
            </div>
            <div class="border-t border-slate-400 pt-2">
                <span class="text-xs font-bold text-slate-800 block">{{ __('Technical Lead') }}</span>
                <span class="text-[10px] text-slate-500 block">{{ __('Field Engineering & Operations') }}</span>
            </div>
        </div>

    </div>

</body>
</html>
