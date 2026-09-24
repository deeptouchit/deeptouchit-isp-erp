<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Profile - {{ $tenant->name }}</title>
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
            <span class="text-xs font-bold text-slate-700">Company Profile Information</span>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-4 py-1.5 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-print text-xs"></i>
                <span>Print</span>
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
                    Internet Service Provider Operations
                </p>
                <p class="text-xs text-slate-600">
                    Web: {{ $tenant->website ?? 'N/A' }} | Email: {{ $tenant->email }}
                </p>
            </div>
            <div class="text-right">
                <span class="inline-block px-3 py-1 bg-slate-900 text-white font-mono text-xs font-bold rounded uppercase tracking-wider">
                    COMPANY PROFILE
                </span>
                <p class="text-sm font-bold font-mono text-cyan-800 mt-1.5">{{ $tenant->btrc_license_no ?? 'BTRC-ISP-LIC' }}</p>
                <p class="text-[10px] text-slate-400 font-mono">Status: Active</p>
            </div>
        </div>

        {{-- Legal Identity & Government Registry --}}
        <div>
            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-2">Company &amp; License Information</h3>
            <table>
                <tr>
                    <td class="w-1/3 bg-slate-50 font-bold text-slate-600">Registered Legal Name:</td>
                    <td class="w-2/3 font-bold text-slate-900">{{ $tenant->company_name }}</td>
                </tr>
                <tr>
                    <td class="bg-slate-50 font-bold text-slate-600">Brand / Trading Name:</td>
                    <td class="font-bold text-cyan-800">{{ $tenant->name }}</td>
                </tr>
                <tr>
                    <td class="bg-slate-50 font-bold text-slate-600">BTRC License Number:</td>
                    <td class="font-mono font-bold text-indigo-700">{{ $tenant->btrc_license_no ?: 'N/A' }}</td>
                </tr>
                <tr>
                    <td class="bg-slate-50 font-bold text-slate-600">License Category:</td>
                    <td class="font-semibold text-slate-800">{{ $tenant->btrc_license_type ?: 'Nationwide ISP' }}</td>
                </tr>
                <tr>
                    <td class="bg-slate-50 font-bold text-slate-600">Trade License Number:</td>
                    <td class="font-mono text-slate-800">{{ $tenant->trade_license_no ?: 'N/A' }}</td>
                </tr>
                <tr>
                    <td class="bg-slate-50 font-bold text-slate-600">TIN / BIN / Tax ID:</td>
                    <td class="font-mono font-bold text-slate-800">{{ $tenant->tin_bin_no ?: 'N/A' }}</td>
                </tr>
            </table>
        </div>

        {{-- Contact & Department Directory --}}
        <div>
            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-2">Contact &amp; Address Information</h3>
            <table>
                <tr>
                    <td class="w-1/3 bg-slate-50 font-bold text-slate-600">Office Address:</td>
                    <td class="w-2/3 text-slate-900 font-medium">{{ $tenant->address }}</td>
                </tr>
                <tr>
                    <td class="bg-slate-50 font-bold text-slate-600">Helpline / Support Phone:</td>
                    <td class="font-mono font-bold text-cyan-800">{{ $tenant->support_hotline ?: 'N/A' }}</td>
                </tr>
                <tr>
                    <td class="bg-slate-50 font-bold text-slate-600">Billing Department:</td>
                    <td class="font-mono text-slate-800">{{ $tenant->billing_phone ?: $tenant->phone }} ({{ $tenant->billing_email ?: $tenant->email }})</td>
                </tr>
                <tr>
                    <td class="bg-slate-50 font-bold text-slate-600">System Website URL:</td>
                    <td class="font-mono text-indigo-700">https://{{ $tenant->domain ?? ($tenant->slug . '.somitysoft.com') }}</td>
                </tr>
            </table>
        </div>

        {{-- Dual Sign-off Blocks --}}
        <div class="pt-10 grid grid-cols-2 gap-8 text-center mt-6">
            <div class="border-t border-slate-400 pt-2">
                <span class="text-xs font-bold text-slate-800 block">{{ $tenant->contact_person ?? 'Owner / Manager' }}</span>
                <span class="text-[10px] text-slate-500 block">{{ $tenant->contact_person_designation ?? 'Authorized Person' }}</span>
            </div>
            <div class="border-t border-slate-400 pt-2">
                <span class="text-xs font-bold text-slate-800 block">System Verification</span>
                <span class="text-[10px] text-slate-500 block">ISP Operations</span>
            </div>
        </div>

    </div>

</body>
</html>
