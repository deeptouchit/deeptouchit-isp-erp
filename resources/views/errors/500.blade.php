<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>500 - Server Error | {{ config('app.name', 'SomitySoft ISP Platform') }}</title>
    
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                        mono: ['JetBrains Mono', 'monospace'],
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Inter', system-ui, sans-serif; }
        .bg-grid {
            background-size: 32px 32px;
            background-image: 
                linear-gradient(to right, rgba(226, 232, 240, 0.6) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(226, 232, 240, 0.6) 1px, transparent 1px);
        }
    </style>
</head>
<body class="h-full bg-slate-50 text-slate-800 antialiased flex items-center justify-center p-4 bg-grid relative overflow-x-hidden">

    <!-- Ambient Gradient Blobs -->
    <div class="fixed top-1/4 -left-32 w-96 h-96 bg-cyan-200/40 rounded-full blur-3xl pointer-events-none -z-10"></div>
    <div class="fixed bottom-1/4 -right-32 w-96 h-96 bg-rose-200/40 rounded-full blur-3xl pointer-events-none -z-10"></div>

    <main class="w-full max-w-xl mx-auto">
        <!-- Main Card -->
        <div class="bg-white/90 backdrop-blur-md rounded-2xl border border-slate-200 shadow-xl overflow-hidden">
            
            <!-- Top Status Indicator Strip -->
            <div class="h-1.5 bg-gradient-to-r from-rose-500 via-amber-500 to-cyan-500"></div>

            <div class="p-6 sm:p-8 space-y-6">
                
                <!-- Header Badge & Icon -->
                <div class="flex items-start justify-between gap-4">
                    <div class="space-y-1">
                        <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-rose-50 text-rose-700 border border-rose-200/80">
                            <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
                            <span>HTTP 500 &bull; Internal Server Exception</span>
                        </div>
                        <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 pt-1">
                            System Encountered an Unexpected Issue
                        </h1>
                        <p class="text-xs text-slate-500 leading-relaxed font-normal">
                            An internal execution error was caught by the framework. Automated telemetry has logged this event for the technical team to investigate.
                        </p>
                    </div>

                    <!-- Icon Badge -->
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-rose-50 to-rose-100 text-rose-600 border border-rose-200/80 flex items-center justify-center text-xl flex-shrink-0 shadow-xs">
                        <i class="fas fa-server"></i>
                    </div>
                </div>

                <!-- Diagnostics Box -->
                <div class="p-3.5 bg-slate-50/80 rounded-xl border border-slate-200/90 text-xs space-y-2">
                    <div class="flex items-center justify-between text-[11px] text-slate-500">
                        <span class="font-medium text-slate-700 flex items-center gap-1.5">
                            <i class="fas fa-fingerprint text-slate-400"></i>
                            <span>Incident Reference</span>
                        </span>
                        <span class="font-mono text-slate-600 bg-white px-2 py-0.5 rounded border border-slate-200">
                            REQ-{{ strtoupper(substr(md5(microtime()), 0, 8)) }}
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-2 pt-1 border-t border-slate-200/60 text-[11px]">
                        <div>
                            <span class="text-slate-400 block">Occurred At</span>
                            <span class="font-mono text-slate-700 font-medium">{{ now()->format('d M, Y H:i:s T') }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block">Telemetry Status</span>
                            <span class="text-emerald-700 font-medium inline-flex items-center gap-1">
                                <i class="fas fa-circle-check text-[10px]"></i> Recorded in Server Log
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Action Toolbar -->
                <div class="pt-2 flex flex-col sm:flex-row items-center gap-2.5">
                    <button type="button" 
                            onclick="window.location.reload()" 
                            class="w-full sm:w-auto flex-1 bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs py-2.5 px-4 rounded-xl shadow-xs transition flex items-center justify-center gap-2 cursor-pointer active:scale-98">
                        <i class="fas fa-rotate-right text-[11px]"></i>
                        <span>Reload & Try Again</span>
                    </button>

                    <button type="button" 
                            onclick="window.history.length > 1 ? window.history.back() : window.location.href='/admin/dashboard'" 
                            class="w-full sm:w-auto bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs py-2.5 px-4 rounded-xl border border-slate-200 transition flex items-center justify-center gap-2 cursor-pointer">
                        <i class="fas fa-arrow-left text-[11px]"></i>
                        <span>Previous Screen</span>
                    </button>

                    <a href="/admin/dashboard" 
                       class="w-full sm:w-auto bg-slate-50 hover:bg-white text-slate-600 hover:text-cyan-700 font-semibold text-xs py-2.5 px-4 rounded-xl border border-slate-200 transition flex items-center justify-center gap-1.5">
                        <i class="fas fa-gauge-high text-[11px]"></i>
                        <span>Dashboard</span>
                    </a>
                </div>

            </div>

            <!-- Footer Note -->
            <div class="px-6 py-3 bg-slate-50/70 border-t border-slate-200/80 flex items-center justify-between text-[11px] text-slate-500">
                <span class="flex items-center gap-1.5">
                    <i class="fas fa-shield-halved text-cyan-600 text-[10px]"></i>
                    <span>{{ config('app.name', 'DeepTouch ISP Platform') }}</span>
                </span>
                <span>Self-Healing Engine Active</span>
            </div>

        </div>

        <p class="text-center text-[11px] text-slate-400 mt-4">
            If the issue persists, please report the Incident Reference to your system administrator.
        </p>
    </main>

</body>
</html>
