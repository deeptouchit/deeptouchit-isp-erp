<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Company Profile') }} - {{ $tenant->company_name ?? $tenant->name }}</title>
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
            <span class="text-xs font-bold text-slate-700">{{ __('Official Company Profile & Certification') }}</span>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="h-[34px] py-0 px-4 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-print text-xs"></i>
                <span>{{ __('Print Document') }}</span>
            </button>
            <button onclick="window.close()" class="h-[34px] py-0 px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-medium border border-slate-300 transition cursor-pointer">
                {{ __('Close') }}
            </button>
        </div>
    </div>

    {{-- Printable Document Canvas --}}
    <div class="max-w-3xl mx-auto bg-white p-6 sm:p-8 rounded-xl border border-slate-200 shadow-sm space-y-5">
        
        {{-- Header Block --}}
        <div class="flex items-start justify-between border-b-2 border-slate-800 pb-4">
            <div class="flex items-center gap-4">
                @if($tenant->logo && (\Illuminate\Support\Facades\Storage::disk('public')->exists($tenant->logo) || file_exists(public_path('storage/' . $tenant->logo))))
                    <div class="w-16 h-16 rounded-lg border border-slate-200 p-1 flex items-center justify-center overflow-hidden flex-shrink-0">
                        <img src="{{ asset('storage/' . $tenant->logo) }}" alt="{{ __('Logo') }}" class="max-h-full max-w-full object-contain">
                    </div>
                @endif
                <div>
                    <h1 class="text-xl font-black tracking-tight text-slate-900 uppercase">
                        {{ $tenant->company_name ?? $tenant->name }}
                    </h1>
                    <p class="text-xs text-slate-600 font-medium mt-0.5">
                        {{ __('Licensed Internet Service Provider (ISP) Operations') }}
                    </p>
                    <p class="text-xs text-slate-600">
                        {{ __('Web') }}: {{ $tenant->website ?? __('N/A') }} | {{ __('Email') }}: {{ $tenant->email }}
                    </p>
                </div>
            </div>
            <div class="text-right">
                <span class="inline-block px-3 py-1 bg-slate-900 text-white font-mono text-xs font-bold rounded uppercase tracking-wider">
                    {{ __('COMPANY PROFILE') }}
                </span>
                <p class="text-sm font-bold font-mono text-cyan-800 mt-1.5">{{ $tenant->btrc_license_no ?? 'BTRC-ISP-LIC' }}</p>
                <p class="text-[10px] text-slate-400 font-mono">{{ __('Status') }}: {{ __(ucfirst(strtolower($tenant->status ?? 'active'))) }}</p>
            </div>
        </div>

        {{-- Legal Identity & Government Registry --}}
        <div>
            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-2">{{ __('Company & License Information') }}</h3>
            <table>
                <tr>
                    <td class="w-1/3 bg-slate-50 font-bold text-slate-600">{{ __('Registered Corporate Name:') }}</td>
                    <td class="w-2/3 font-bold text-slate-900">{{ $tenant->company_name }}</td>
                </tr>
                <tr>
                    <td class="bg-slate-50 font-bold text-slate-600">{{ __('Brand / Trading Name:') }}</td>
                    <td class="font-bold text-cyan-800">{{ $tenant->name }}</td>
                </tr>
                <tr>
                    <td class="bg-slate-50 font-bold text-slate-600">{{ __('BTRC License Number:') }}</td>
                    <td class="font-mono font-bold text-indigo-700">{{ $tenant->btrc_license_no ?: __('Pending Registration') }}</td>
                </tr>
                <tr>
                    <td class="bg-slate-50 font-bold text-slate-600">{{ __('BTRC License Category:') }}</td>
                    <td class="font-semibold text-slate-800">{{ $tenant->btrc_license_type ? __($tenant->btrc_license_type) : __('Nationwide ISP') }}</td>
                </tr>
                <tr>
                    <td class="bg-slate-50 font-bold text-slate-600">{{ __('Trade License Number:') }}</td>
                    <td class="font-mono text-slate-800">{{ $tenant->trade_license_no ?: __('N/A') }}</td>
                </tr>
                <tr>
                    <td class="bg-slate-50 font-bold text-slate-600">{{ __('TIN / BIN / Tax ID:') }}</td>
                    <td class="font-mono font-bold text-slate-800">{{ $tenant->tin_bin_no ?: __('N/A') }}</td>
                </tr>
            </table>
        </div>

        {{-- Contact & Department Directory --}}
        <div>
            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-2">{{ __('Contact & Head Office Information') }}</h3>
            <table>
                <tr>
                    <td class="w-1/3 bg-slate-50 font-bold text-slate-600">{{ __('Corporate Head Office:') }}</td>
                    <td class="w-2/3 text-slate-900 font-medium">{{ $tenant->address }}</td>
                </tr>
                <tr>
                    <td class="bg-slate-50 font-bold text-slate-600">{{ __('24/7 Support Helpline:') }}</td>
                    <td class="font-mono font-bold text-cyan-800">{{ $tenant->support_hotline ?: __('N/A') }}</td>
                </tr>
                <tr>
                    <td class="bg-slate-50 font-bold text-slate-600">{{ __('Primary Phone & Email:') }}</td>
                    <td class="font-mono text-slate-800">{{ $tenant->phone }} | {{ $tenant->email }}</td>
                </tr>
                <tr>
                    <td class="bg-slate-50 font-bold text-slate-600">{{ __('Billing Department:') }}</td>
                    <td class="font-mono text-slate-800">{{ $tenant->billing_phone ?: $tenant->phone }} ({{ $tenant->billing_email ?: $tenant->email }})</td>
                </tr>
                <tr>
                    <td class="bg-slate-50 font-bold text-slate-600">{{ __('System Workspace Portal:') }}</td>
                    <td class="font-mono text-indigo-700">https://{{ $tenant->domain ?? ($tenant->slug . '.somitysoft.com') }}</td>
                </tr>
            </table>
        </div>

        {{-- Billing, Currency & Policies --}}
        <div>
            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-2">{{ __('Billing & Invoice Policy') }}</h3>
            <table>
                <tr>
                    <td class="w-1/3 bg-slate-50 font-bold text-slate-600">{{ __('Operating Currency:') }}</td>
                    <td class="w-2/3 font-mono font-bold text-slate-800">{{ $tenant->currency_code ?? 'BDT' }} ({{ $currencySymbol ?? ($tenant->currency_symbol ?? '৳') }})</td>
                </tr>
                <tr>
                    <td class="bg-slate-50 font-bold text-slate-600">{{ __('System Timezone:') }}</td>
                    <td class="font-mono text-slate-800">{{ $tenant->timezone ?? 'Asia/Dhaka' }}</td>
                </tr>
                @if(!empty($tenant->invoice_footer_notes))
                <tr>
                    <td class="bg-slate-50 font-bold text-slate-600">{{ __('Invoice Terms & Notes:') }}</td>
                    <td class="text-slate-700 text-xs">{{ $tenant->invoice_footer_notes }}</td>
                </tr>
                @endif
            </table>
        </div>

        {{-- Dual Sign-off Blocks --}}
        <div class="pt-8 grid grid-cols-2 gap-8 text-center mt-6">
            <div class="border-t border-slate-400 pt-2">
                <span class="text-xs font-bold text-slate-800 block">{{ $tenant->contact_person ?? __('Managing Director / Proprietor') }}</span>
                <span class="text-[10px] text-slate-500 block">{{ $tenant->contact_person_designation ?? __('Authorized Signatory') }}</span>
            </div>
            <div class="border-t border-slate-400 pt-2">
                <span class="text-xs font-bold text-slate-800 block">{{ __('System Verification') }}</span>
                <span class="text-[10px] text-slate-500 block">{{ __('ISP Automation System') }}</span>
            </div>
        </div>

    </div>

</body>
</html>
