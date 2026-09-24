<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scheduled Maintenance | {{ $companyName ?? 'SomitySoft SaaS' }}</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="h-full antialiased text-slate-800 flex flex-col justify-between bg-slate-100/70 selection:bg-blue-500 selection:text-white">

    <!-- Subtle Top Ambient Bar -->
    <div class="h-1.5 bg-gradient-to-r from-amber-500 via-blue-600 to-indigo-600 w-full"></div>

    <!-- Main Container -->
    <main class="flex-1 flex items-center justify-center p-4 sm:p-6 lg:p-8">
        <div class="max-w-lg w-full bg-white rounded-2xl shadow-xl shadow-slate-200/50 border border-slate-200/80 p-6 sm:p-8 text-center space-y-6">
            
            <!-- Brand Logo / Name -->
            <div class="flex items-center justify-center gap-2.5">
                @if(!empty($appLogo))
                    <img src="{{ $appLogo }}" alt="Logo" class="h-9 w-auto object-contain">
                @else
                    <div class="h-10 w-10 rounded-xl bg-gradient-to-br from-blue-600 to-indigo-700 flex items-center justify-center text-white shadow-sm font-bold text-lg">
                        S
                    </div>
                @endif
                <span class="font-extrabold text-lg text-slate-900 tracking-tight">{{ $companyName ?? 'SomitySoft SaaS' }}</span>
            </div>

            <!-- Maintenance Icon Graphic -->
            <div class="relative inline-flex items-center justify-center mx-auto">
                <div class="w-20 h-20 rounded-2xl bg-amber-50 border border-amber-200/80 flex items-center justify-center text-amber-600 text-3xl shadow-inner">
                    <i class="fas fa-tools animate-pulse"></i>
                </div>
                <span class="absolute -top-1 -right-1 flex h-4 w-4">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-4 w-4 bg-amber-500"></span>
                </span>
            </div>

            <!-- Status & Heading -->
            <div class="space-y-2">
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-100 text-amber-800 text-xs font-semibold">
                    <i class="fas fa-wrench text-[10px]"></i>
                    <span>Scheduled Platform Maintenance</span>
                </div>
                <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight">
                    We'll Be Right Back!
                </h1>
            </div>

            <!-- Description / Message -->
            <div class="bg-slate-50 border border-slate-200/70 rounded-xl p-4 text-xs sm:text-sm text-slate-600 leading-relaxed text-left">
                <p class="font-medium text-slate-700 mb-1 flex items-center gap-1.5">
                    <i class="fas fa-info-circle text-blue-500"></i>
                    <span>Maintenance Notice:</span>
                </p>
                <p>{{ $maintenanceMessage ?? 'We are currently performing scheduled platform upgrades and database optimizations to serve you better. Services will resume shortly.' }}</p>
            </div>

            <!-- Action Buttons -->
            <div class="pt-2 flex flex-col sm:flex-row items-center justify-center gap-3">
                <button onclick="window.location.reload()" class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs transition shadow-sm flex items-center justify-center gap-2">
                    <i class="fas fa-rotate-right"></i>
                    <span>Check Status / Refresh</span>
                </button>

                @if(auth()->check() && auth()->user()->isOwner())
                    <a href="/owner/dashboard" class="w-full sm:w-auto px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition flex items-center justify-center gap-1.5 border border-slate-200">
                        <i class="fas fa-gauge-high text-blue-600"></i>
                        <span>Owner Console</span>
                    </a>
                @else
                    <a href="/owner/login" class="w-full sm:w-auto px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition flex items-center justify-center gap-1.5 border border-slate-200">
                        <i class="fas fa-user-shield text-slate-500"></i>
                        <span>Owner Login</span>
                    </a>
                @endif
            </div>

            <!-- Contact & Helpdesk -->
            <div class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-center gap-4 text-xs text-slate-500">
                @if(!empty($supportEmail))
                    <a href="mailto:{{ $supportEmail }}" class="hover:text-blue-600 transition flex items-center gap-1.5">
                        <i class="fas fa-envelope text-slate-400"></i>
                        <span>{{ $supportEmail }}</span>
                    </a>
                @endif
                @if(!empty($supportPhone))
                    <span class="hidden sm:inline text-slate-300">•</span>
                    <a href="tel:{{ $supportPhone }}" class="hover:text-blue-600 transition flex items-center gap-1.5">
                        <i class="fas fa-phone text-slate-400"></i>
                        <span>{{ $supportPhone }}</span>
                    </a>
                @endif
            </div>

        </div>
    </main>

    <!-- Clean Footer -->
    <footer class="py-4 text-center text-xs text-slate-400">
        &copy; {{ date('Y') }} {{ $companyName ?? 'SomitySoft SaaS' }}. All rights reserved.
    </footer>

</body>
</html>
