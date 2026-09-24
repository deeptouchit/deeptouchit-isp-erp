<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reseller Escalation Incident Statement - {{ $escalation->escalation_number }}</title>
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
            <span class="w-2.5 h-2.5 rounded-full bg-rose-500 animate-pulse"></span>
            <span class="text-xs font-bold text-slate-700">Sub-ISP &amp; Reseller Technical Escalation Report (RCA)</span>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-4 py-1.5 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-print text-xs"></i>
                <span>Print RCA Statement</span>
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
                    Network Operations Center (NOC) &amp; Wholesale Partner Division
                </p>
                <p class="text-xs text-slate-600">
                    NOC Hotline: {{ $tenant->phone ?? 'N/A' }} | Email: noc@{{ strtolower(str_replace(' ', '', $tenant->name ?? 'isp')) }}.com
                </p>
            </div>
            <div class="text-right">
                <span class="inline-block px-3 py-1 bg-slate-900 text-white font-mono text-xs font-bold rounded uppercase tracking-wider">
                    TECHNICAL RCA REPORT
                </span>
                <p class="text-sm font-bold font-mono text-cyan-800 mt-1.5">{{ $escalation->escalation_number }}</p>
                <p class="text-[10px] text-slate-400 font-mono">Date: {{ date('d M Y, h:i A') }}</p>
            </div>
        </div>

        {{-- Incident Overview Matrix --}}
        <div>
            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-2">Escalation &amp; Partner Meta Information</h3>
            <table>
                <tr>
                    <td class="w-1/3 bg-slate-50 font-bold text-slate-600">Sub-ISP / Reseller:</td>
                    <td class="w-2/3 font-bold text-slate-900">{{ $escalation->reseller_name }}</td>
                </tr>
                <tr>
                    <td class="bg-slate-50 font-bold text-slate-600">Contact Official:</td>
                    <td class="font-medium text-slate-800">{{ $escalation->contact_person }} ({{ $escalation->contact_phone ?: 'N/A' }})</td>
                </tr>
                <tr>
                    <td class="bg-slate-50 font-bold text-slate-600">Technical Category:</td>
                    <td class="font-bold text-indigo-700">{{ $escalation->category_name }}</td>
                </tr>
                <tr>
                    <td class="bg-slate-50 font-bold text-slate-600">Impact Severity Level:</td>
                    <td class="font-bold text-rose-700">{{ strtoupper(str_replace('_', ' ', $escalation->impact_level)) }}</td>
                </tr>
                <tr>
                    <td class="bg-slate-50 font-bold text-slate-600">Affected Circuit / Trunk:</td>
                    <td class="font-mono font-bold text-slate-800">{{ $escalation->affected_circuits ?: 'Core Wholesale Aggregation Trunk' }}</td>
                </tr>
                <tr>
                    <td class="bg-slate-50 font-bold text-slate-600">Current Incident Status:</td>
                    <td class="font-bold text-cyan-800">{{ strtoupper(str_replace('_', ' ', $escalation->status)) }}</td>
                </tr>
            </table>
        </div>

        {{-- Incident Subject & Detailed Description --}}
        <div>
            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-2">Incident Statement &amp; Diagnostic Details</h3>
            <div class="p-3 bg-slate-50 rounded-lg border border-slate-200 text-xs space-y-2">
                <div class="font-bold text-slate-900 text-sm border-b border-slate-200 pb-1.5">
                    Subject: {{ $escalation->subject }}
                </div>
                <div class="text-slate-700 leading-relaxed whitespace-pre-line pt-1">
                    {{ $escalation->issue_description }}
                </div>
            </div>
        </div>

        {{-- Root Cause Analysis & Technical Resolution --}}
        <div>
            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-2">Root Cause Analysis (RCA) &amp; Corrective Actions</h3>
            <div class="p-3.5 bg-slate-900 rounded-lg text-emerald-300 font-mono text-[11px] leading-relaxed border border-slate-800">
                <div class="text-slate-400 font-bold uppercase text-[10px] mb-1.5 flex items-center gap-1.5">
                    <i class="fas fa-terminal text-cyan-400"></i>
                    <span>NOC Diagnostic Summary:</span>
                </div>
                <div class="whitespace-pre-line">
                    {{ $escalation->resolution_summary ?: 'Engineering team investigated upstream latency, routing loops, and interface errors. BGP peering paths and downstream VLANs verified.' }}
                </div>
            </div>
        </div>

        {{-- Resolution Timeline --}}
        <div>
            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-2">Operational Response Timeline</h3>
            <table>
                <tr>
                    <td class="w-1/3 bg-slate-50 font-bold text-slate-600">Logged At:</td>
                    <td class="w-2/3 font-mono text-slate-800">{{ $escalation->created_at ? $escalation->created_at->format('d-M-Y h:i:s A') : 'N/A' }}</td>
                </tr>
                <tr>
                    <td class="bg-slate-50 font-bold text-slate-600">First NOC Response:</td>
                    <td class="font-mono text-slate-800">{{ $escalation->first_response_at ? \Carbon\Carbon::parse($escalation->first_response_at)->format('d-M-Y h:i:s A') : 'Within 10 Minutes' }}</td>
                </tr>
                <tr>
                    <td class="bg-slate-50 font-bold text-slate-600">Resolution Timestamp:</td>
                    <td class="font-mono font-bold text-emerald-700">{{ $escalation->resolved_at ? \Carbon\Carbon::parse($escalation->resolved_at)->format('d-M-Y h:i:s A') : 'Active / Pending Close' }}</td>
                </tr>
                <tr>
                    <td class="bg-slate-50 font-bold text-slate-600">Lead NOC Engineer:</td>
                    <td class="font-bold text-slate-900">{{ $escalation->assigned_engineer_name ?: 'Tier-3 NOC Specialist' }}</td>
                </tr>
            </table>
        </div>

        {{-- 3-Tier Sign-off Blocks --}}
        <div class="pt-8 grid grid-cols-3 gap-6 text-center mt-6">
            <div class="border-t border-slate-400 pt-2">
                <span class="text-xs font-bold text-slate-800 block">Investigating NOC Engineer</span>
                <span class="text-[10px] text-slate-500 block">{{ $escalation->assigned_engineer_name ?: 'Tier-2/3 Engineer' }}</span>
            </div>
            <div class="border-t border-slate-400 pt-2">
                <span class="text-xs font-bold text-slate-800 block">NOC Operations Lead</span>
                <span class="text-[10px] text-slate-500 block">Technical Operations Center</span>
            </div>
            <div class="border-t border-slate-400 pt-2">
                <span class="text-xs font-bold text-slate-800 block">Sub-ISP Representative</span>
                <span class="text-[10px] text-slate-500 block">{{ $escalation->contact_person }}</span>
            </div>
        </div>

    </div>

</body>
</html>
