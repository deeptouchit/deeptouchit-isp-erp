<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Field Task Sheet - {{ $job->job_number }}</title>
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
            <span class="text-xs font-bold text-slate-700">Official Field Dispatch Job Sheet</span>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-print text-xs"></i>
                <span>Print Task Sheet</span>
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
                    Field Operations, Splicing &amp; Customer Support Department
                </p>
                <p class="text-xs text-slate-600">
                    Hotline: {{ $tenant->phone ?? 'N/A' }} | Email: {{ $tenant->email ?? 'support@isp.local' }}
                </p>
            </div>
            <div class="text-right">
                <span class="inline-block px-3 py-1 bg-slate-900 text-white font-mono text-xs font-bold rounded uppercase tracking-wider">
                    FIELD TASK SHEET
                </span>
                <p class="text-sm font-bold font-mono text-blue-700 mt-1.5">{{ $job->job_number }}</p>
                <p class="text-[10px] text-slate-400 font-mono">Date: {{ date('d M Y, h:i A') }}</p>
            </div>
        </div>

        {{-- Task Classification & Priority Bar --}}
        <div class="grid grid-cols-3 gap-3 bg-slate-50 p-3 rounded-lg border border-slate-200 text-xs">
            <div>
                <span class="text-[9px] font-bold uppercase text-slate-500 block">Job Classification</span>
                <span class="font-bold text-slate-800">{{ $job->job_type_name }}</span>
            </div>
            <div>
                <span class="text-[9px] font-bold uppercase text-slate-500 block">Priority Level</span>
                <span class="font-bold uppercase {{ $job->priority === 'urgent' ? 'text-rose-600' : 'text-slate-800' }}">{{ $job->priority }}</span>
            </div>
            <div>
                <span class="text-[9px] font-bold uppercase text-slate-500 block">Assigned Technician</span>
                <span class="font-bold text-indigo-700">{{ $job->assigned_technician_name ?: 'Unassigned' }}</span>
            </div>
        </div>

        {{-- Customer & Site Information --}}
        <div>
            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-2">Subscriber &amp; Location Details</h3>
            <table>
                <tr>
                    <td class="w-1/3 bg-slate-50 font-bold text-slate-600">Subscriber / Contact Name:</td>
                    <td class="w-2/3 font-semibold text-slate-900">{{ $job->customer_name }}</td>
                </tr>
                <tr>
                    <td class="bg-slate-50 font-bold text-slate-600">Contact Mobile Number:</td>
                    <td class="font-mono font-bold text-blue-700">{{ $job->customer_phone ?: 'N/A' }}</td>
                </tr>
                <tr>
                    <td class="bg-slate-50 font-bold text-slate-600">Installation / Fault Address:</td>
                    <td class="text-slate-800">{{ $job->address }}</td>
                </tr>
                <tr>
                    <td class="bg-slate-50 font-bold text-slate-600">Scheduled Time Window:</td>
                    <td class="font-mono text-slate-700">{{ $job->scheduled_at ? $job->scheduled_at->format('d M Y, h:i A') : 'Immediate Dispatch' }}</td>
                </tr>
            </table>
        </div>

        {{-- Fault Description & Technical Scope --}}
        <div>
            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-2">Reported Issue &amp; Technical Scope</h3>
            <div class="p-3 bg-slate-50 rounded-lg border border-slate-200 text-xs text-slate-800 leading-relaxed min-h-[60px]">
                {{ $job->issue_description }}
            </div>
        </div>

        {{-- Optical Readings & Materials Checklist Table --}}
        <div>
            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-2">Optical Power &amp; Materials Verification</h3>
            <table>
                <thead>
                    <tr>
                        <th class="w-1/2">Parameter / Checklist Item</th>
                        <th class="w-1/2">Field Measurement / Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="font-semibold text-slate-700">Optical Signal (RX Power) Before Repair:</td>
                        <td class="font-mono font-bold text-rose-700">{{ $job->optical_rx_before ?: '__________ dBm' }}</td>
                    </tr>
                    <tr>
                        <td class="font-semibold text-slate-700">Optical Signal (RX Power) After Repair:</td>
                        <td class="font-mono font-bold text-emerald-700">{{ $job->optical_rx_after ?: '__________ dBm' }}</td>
                    </tr>
                    <tr>
                        <td class="font-semibold text-slate-700">Materials &amp; Equipment Used:</td>
                        <td class="text-slate-800 font-mono text-[10.5px]">
                            @if($job->materials_used && is_array($job->materials_used))
                                {{ implode(', ', $job->materials_used) }}
                            @else
                                Drop Cable: _____ m | Fast Conn: _____ pcs | ONU Model: ____________
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="font-semibold text-slate-700">Resolution &amp; Splicing Notes:</td>
                        <td class="text-slate-800 min-h-[40px]">{{ $job->resolution_notes ?: '________________________________________________' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Dual Sign-off Blocks --}}
        <div class="pt-10 grid grid-cols-2 gap-8 text-center mt-6">
            <div class="border-t border-slate-400 pt-2">
                <span class="text-xs font-bold text-slate-800 block">Field Technician Signature</span>
                <span class="text-[10px] text-slate-500 block">{{ $job->assigned_technician_name ?: 'NOC Field Engineer' }}</span>
            </div>
            <div class="border-t border-slate-400 pt-2">
                <span class="text-xs font-bold text-slate-800 block">Customer / Subscriber Acceptance</span>
                <span class="text-[10px] text-slate-500 block">Satisfied with link restoration</span>
            </div>
        </div>

    </div>

</body>
</html>
