<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Partner Authorization Certificate') }} - {{ $reseller->name ?? __('Reseller Partner') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Baloo+Da+2:wght@400..800&family=Cinzel:wght@600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: "Baloo Da 2", 'Plus Jakarta Sans', sans-serif;
            background-color: #f1f5f9;
        }
        .cert-title {
            font-family: 'Cinzel', "Baloo Da 2", serif;
        }
        @media print {
            body {
                background: white !important;
                padding: 0 !important;
            }
            .no-print {
                display: none !important;
            }
            .cert-page {
                box-shadow: none !important;
                border: 2px solid #6b21a8 !important;
                margin: 0 !important;
                width: 100% !important;
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body class="p-4 sm:p-8 flex flex-col items-center min-h-screen">

    <!-- Action Bar -->
    <div class="max-w-4xl w-full mb-4 flex items-center justify-between no-print">
        <a href="{{ route('reseller.profile') }}" class="px-3.5 py-1.5 rounded-lg bg-white border border-slate-300 text-slate-700 text-xs font-semibold hover:bg-slate-50 transition shadow-xs flex items-center gap-1.5">
            ← {{ __('Back to Profile') }}
        </a>
        <button onclick="window.print()" class="px-4 py-1.5 rounded-lg bg-purple-600 hover:bg-purple-700 text-white text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            {{ __('Print Official Certificate') }}
        </button>
    </div>

    <!-- Certificate Sheet Canvas -->
    <div class="cert-page max-w-4xl w-full bg-white border-8 border-double border-purple-900 rounded-2xl p-8 sm:p-12 shadow-2xl relative overflow-hidden text-slate-800">
        
        <!-- Corner Watermark Accents -->
        <div class="absolute -top-12 -right-12 w-40 h-40 bg-purple-100 rounded-full blur-2xl pointer-events-none"></div>
        <div class="absolute -bottom-12 -left-12 w-40 h-40 bg-indigo-100 rounded-full blur-2xl pointer-events-none"></div>

        <!-- Issuer Header -->
        <div class="text-center pb-6 border-b-2 border-purple-100 relative z-10">
            <h2 class="text-lg sm:text-xl font-extrabold text-purple-900 tracking-wide uppercase">
                {{ $tenant->company_name ?? ($tenant->name ?? 'ISP Management Network') }}
            </h2>
            <p class="text-xs text-slate-500 font-mono mt-0.5">
                {{ __('License / Registration') }}: {{ $tenant->btrc_license_no ?: __('Nationwide Internet Service Provider') }}
            </p>
        </div>

        <!-- Certificate Body -->
        <div class="text-center py-8 space-y-4 relative z-10">
            <div class="inline-block px-4 py-1 rounded-full bg-purple-50 border border-purple-200 text-purple-800 text-xs font-bold uppercase tracking-widest">
                {{ __('Certificate of Reseller Partnership') }}
            </div>

            <h1 class="cert-title text-2xl sm:text-3xl font-bold text-slate-900 pt-2">
                {{ __('AUTHORIZATION CERTIFICATE') }}
            </h1>

            <p class="text-xs text-slate-600 max-w-xl mx-auto leading-relaxed">
                {{ __('This is to officially certify that the designated organization below is recognized and authorized as an active Sub-ISP / Franchise Reseller Partner under our carrier network infrastructure.') }}
            </p>

            <div class="my-6 p-6 rounded-xl bg-purple-50/50 border border-purple-200/80 max-w-2xl mx-auto space-y-2 text-left">
                <div class="flex justify-between border-b border-purple-100/80 pb-2 text-xs">
                    <span class="text-slate-500 font-medium">{{ __('Partner Name') }}:</span>
                    <span class="font-bold text-slate-900 text-sm">{{ $reseller->name ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between border-b border-purple-100/80 pb-2 text-xs">
                    <span class="text-slate-500 font-medium">{{ __('Partner Code') }}:</span>
                    <span class="font-bold font-mono text-purple-800">{{ $reseller->code ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between border-b border-purple-100/80 pb-2 text-xs">
                    <span class="text-slate-500 font-medium">{{ __('Client Prefix') }}:</span>
                    <span class="font-bold font-mono text-slate-800">{{ $reseller->prefix ?? ($reseller->code ?? 'RES') }}</span>
                </div>
                <div class="flex justify-between border-b border-purple-100/80 pb-2 text-xs">
                    <span class="text-slate-500 font-medium">{{ __('Authorized Contact Person') }}:</span>
                    <span class="font-bold text-slate-800">{{ $reseller->contact_person ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between border-b border-purple-100/80 pb-2 text-xs">
                    <span class="text-slate-500 font-medium">{{ __('Registered Contact Number') }}:</span>
                    <span class="font-mono text-slate-800">{{ $reseller->mobile ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between text-xs pt-1">
                    <span class="text-slate-500 font-medium">{{ __('Current Status') }}:</span>
                    <span class="font-bold font-mono text-emerald-700 bg-emerald-100/80 px-2 py-0.5 rounded text-[11px]">{{ strtoupper($reseller->status ?? 'ACTIVE') }}</span>
                </div>
            </div>

            <p class="text-[11px] text-slate-500 max-w-lg mx-auto">
                {{ __('Authorized to provision, maintain, and manage retail subscriber connections within designated coverage zones subject to standard SLA agreements.') }}
            </p>
        </div>

        <!-- Signatures & Verification -->
        <div class="pt-8 border-t-2 border-purple-100 grid grid-cols-2 gap-8 text-center text-xs relative z-10">
            <div>
                <div class="w-40 border-b border-slate-400 mx-auto mb-1.5 h-10"></div>
                <span class="font-bold text-slate-800 block">{{ __('Authorized Signature') }}</span>
                <span class="text-[10px] text-slate-500">{{ $reseller->name }}</span>
            </div>
            <div>
                <div class="w-40 border-b border-slate-400 mx-auto mb-1.5 h-10"></div>
                <span class="font-bold text-purple-900 block">{{ __('Managing Director / Carrier ISP') }}</span>
                <span class="text-[10px] text-slate-500">{{ $tenant->company_name ?? 'Host ISP HQ' }}</span>
            </div>
        </div>

        <!-- Footer Verification Code -->
        <div class="mt-8 pt-3 border-t border-slate-100 flex items-center justify-between text-[10px] font-mono text-slate-400">
            <span>{{ __('Doc Ref') }}: CERT-{{ $reseller->code ?? '001' }}-{{ date('Y') }}</span>
            <span>{{ __('Issue Date') }}: {{ date('F d, Y') }}</span>
        </div>

    </div>

</body>
</html>
