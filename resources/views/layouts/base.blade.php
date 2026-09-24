<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">
<head>
    {{-- 1. Document Metadata & SEO --}}
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0, viewport-fit=cover">
    <meta name="theme-color" content="#2563eb">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>@yield('title', $globalSettings['app_name'] ?? config('app.name', 'SomitySoft SaaS Platform'))</title>

    {{-- Favicon --}}
    @if(!empty($globalSettings['app_favicon']))
        <link rel="icon" type="image/x-icon" href="{{ $globalSettings['app_favicon'] }}">
    @else
        <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    @endif

    {{-- 2. Typography & Web Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Baloo+Da+2:wght@400..800&display=swap" rel="stylesheet">

    {{-- 3. Core CSS Stylesheets --}}
    <!-- FontAwesome 6 Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    {{-- 4. Tailwind CSS Play CDN & Enterprise Theme Config --}}
    <script>
        // Suppress Tailwind Play CDN production warning in console
        (function() {
            window.__isPageUnloading = false;
            window.addEventListener('beforeunload', function() {
                window.__isPageUnloading = true;
            });
            window.addEventListener('pagehide', function() {
                window.__isPageUnloading = true;
            });

            // Unlock AudioContext on first user interaction to prevent security warnings
            function unlockAudio() {
                try {
                    const AudioCtx = window.AudioContext || window.webkitAudioContext;
                    if (AudioCtx && !window.__resellerAudioCtx) {
                        window.__resellerAudioCtx = new AudioCtx();
                    }
                    if (window.__resellerAudioCtx && window.__resellerAudioCtx.state === 'suspended') {
                        window.__resellerAudioCtx.resume();
                    }
                } catch(e) {}
                document.removeEventListener('click', unlockAudio);
                document.removeEventListener('keydown', unlockAudio);
                document.removeEventListener('touchstart', unlockAudio);
            }
            document.addEventListener('click', unlockAudio, { passive: true });
            document.addEventListener('keydown', unlockAudio, { passive: true });
            document.addEventListener('touchstart', unlockAudio, { passive: true });

            const origWarn = console.warn;
            console.warn = function(...args) {
                if (args[0] && typeof args[0] === 'string' && args[0].includes('cdn.tailwindcss.com')) {
                    return;
                }
                origWarn.apply(console, args);
            };
        })();
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Baloo Da 2"', 'sans-serif'],
                        bangla: ['"Baloo Da 2"', 'sans-serif'],
                    },
                    colors: {
                        primary: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            200: '#bfdbfe',
                            300: '#93c5fd',
                            400: '#60a5fa',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a',
                            950: '#172554',
                        }
                    },
                    boxShadow: {
                        'app': '0 4px 20px -2px rgba(0, 0, 0, 0.05), 0 2px 6px -1px rgba(0, 0, 0, 0.02)',
                        'app-lg': '0 10px 30px -3px rgba(0, 0, 0, 0.08), 0 4px 10px -2px rgba(0, 0, 0, 0.03)',
                        'bottom-bar': '0 -4px 20px -2px rgba(0, 0, 0, 0.06)',
                    }
                }
            }
        }
    </script>

    {{-- 5. Global Custom Styles & Overrides --}}
    <style>
        [x-cloak] { display: none !important; }

        body {
            font-family: "Baloo Da 2", sans-serif;
            -webkit-tap-highlight-color: transparent;
            touch-action: manipulation;
        }

        /* Clean Scrollbars */
        ::-webkit-scrollbar {
            width: 5px;
            height: 5px;
        }
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 9999px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        .safe-bottom {
            padding-bottom: env(safe-area-inset-bottom, 16px);
        }

        /* ========================================================================= */
        /* Global SaaS Enterprise Compact Table (DataTables-Like Pure CSS System)   */
        /* ========================================================================= */
        .saas-table,
        table.saas-table,
        .table-sm {
            width: 100%;
            border-collapse: collapse !important;
            border-spacing: 0;
            white-space: nowrap !important;
            font-size: 11px;
            font-weight: 400;
            border: 1px solid #cbd5e1 !important;
            color: #334155;
            background-color: #ffffff;
        }

        .saas-table th,
        .saas-table td,
        table.saas-table th,
        table.saas-table td,
        .table-sm th,
        .table-sm td {
            white-space: nowrap !important;
            vertical-align: middle;
        }

        .saas-table thead,
        table.saas-table thead,
        .table-sm thead {
            background-color: #f1f5f9;
            color: #1e293b;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            font-size: 10.5px;
            font-weight: 600;
        }

        .saas-table thead th,
        table.saas-table thead th,
        .table-sm thead th {
            padding: 4px 6px;
            font-weight: 600;
            color: #334155;
            border: 1px solid #cbd5e1 !important;
            text-align: center !important;
            background-color: #f1f5f9;
        }

        .saas-table tbody tr,
        table.saas-table tbody tr,
        .table-sm tbody tr {
            border-bottom: 1px solid #e2e8f0;
            transition: background-color 75ms ease-in-out;
        }

        /* DataTables-style alternate zebra striping */
        .saas-table tbody tr:nth-child(even) > td,
        table.saas-table tbody tr:nth-child(even) > td,
        .table-sm tbody tr:nth-child(even) > td {
            background-color: #f8fafc;
        }

        .saas-table tbody tr:nth-child(odd) > td,
        table.saas-table tbody tr:nth-child(odd) > td,
        .table-sm tbody tr:nth-child(odd) > td {
            background-color: #ffffff;
        }

        /* Hover row effect - styled on TD so cell borders remain 100% visible */
        .saas-table tbody tr:hover > td,
        table.saas-table tbody tr:hover > td,
        .table-sm tbody tr:hover > td {
            background-color: #f1f5f9 !important;
            border-color: #cbd5e1 !important;
        }

        .saas-table tbody td,
        table.saas-table tbody td,
        .table-sm tbody td {
            padding: 3px 6px;
            border: 1px solid #e2e8f0 !important;
            font-size: 11px;
            line-height: 1.25;
            text-align: left;
        }

        /* Column/Cell text alignment helpers */
        .saas-table th.text-left, .saas-table td.text-left { text-align: left !important; }
        .saas-table th.text-center, .saas-table td.text-center { text-align: center !important; }
        .saas-table th.text-right, .saas-table td.text-right { text-align: right !important; }
    </style>

    {{-- Page Specific Style Hooks --}}
    @yield('page_style')
    @stack('styles')
</head>
<body class="h-full antialiased text-slate-800 bg-slate-50 selection:bg-blue-600 selection:text-white">

    {{-- Global Announcement Banner --}}
    @if(($globalSettings['announcement_enabled'] ?? '0') == '1' && !empty($globalSettings['announcement_text']))
        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white px-4 py-1.5 text-center text-xs font-semibold flex items-center justify-center gap-2 shadow-xs z-50 relative">
            <i class="fas fa-bullhorn text-amber-300 animate-pulse text-[11px]"></i>
            <span>{{ $globalSettings['announcement_text'] }}</span>
        </div>
    @endif

    {{-- Main Body Content --}}
    @yield('body')

    {{-- Flash Message Toast Component --}}
    @php
        $hasErrorBag = isset($errors) && $errors->any();
        $toastMsg = session('error') ?? ($hasErrorBag ? $errors->first() : (session('success') ?? session('message') ?? null));
        $toastType = (session('error') || $hasErrorBag) ? 'error' : (session('success') ? 'success' : (session('message') ? 'info' : 'info'));
    @endphp

    @if(!empty($toastMsg))
    <div x-data="{ show: true }" 
         x-show="show" 
         x-transition:enter="transform ease-out duration-300 transition"
         x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
         x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         x-init="setTimeout(() => show = false, 4000)"
         x-cloak
         class="fixed z-50 top-4 right-4 max-w-sm w-full rounded-2xl shadow-app-lg border p-4 pointer-events-auto flex items-start gap-3 {{ $toastType === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-900' : ($toastType === 'error' ? 'border-rose-200 bg-rose-50 text-rose-900' : 'border-blue-200 bg-blue-50 text-blue-900') }}">
        <div class="flex-shrink-0 mt-0.5">
            @if($toastType === 'success')
                <i class="fas fa-check-circle text-emerald-600 text-lg"></i>
            @elseif($toastType === 'error')
                <i class="fas fa-exclamation-circle text-rose-600 text-lg"></i>
            @else
                <i class="fas fa-info-circle text-blue-600 text-lg"></i>
            @endif
        </div>
        <div class="flex-1 text-xs font-semibold leading-snug break-words">
            {{ $toastMsg }}
        </div>
        <button type="button" @click="show = false" class="text-slate-400 hover:text-slate-600 transition -mr-1 -mt-1 p-1">
            <i class="fas fa-times text-xs"></i>
        </button>
    </div>
    @endif

    {{-- ========================================================================= --}}
    {{-- Vendor JavaScript Libraries (Loaded before child page scripts)            --}}
    {{-- ========================================================================= --}}
    
    <!-- 1. jQuery Core -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

    <!-- 2. SweetAlert2 Notifications -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- 4. Alpine.js Framework & Plugins -->
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.5/dist/cdn.min.js"></script>

    {{-- ========================================================================= --}}
    {{-- Global SaaS Pure Client-Side Table Sorting Engine                         --}}
    {{-- ========================================================================= --}}
    <script>
    (function() {
        function initSaasTableSort() {
            document.querySelectorAll('table.saas-table').forEach(function(table) {
                if (table.dataset.sortInitialized) return;
                table.dataset.sortInitialized = 'true';

                const headers = table.querySelectorAll('thead th');
                headers.forEach(function(th, colIndex) {
                    const headerText = th.innerText.trim();
                    const isNoSort = th.classList.contains('no-sort') || 
                                     th.dataset.sort === 'none' || 
                                     headerText.toLowerCase() === 'actions' || 
                                     headerText.toLowerCase() === 'action' || 
                                     th.querySelector('input[type="checkbox"]');

                    if (isNoSort) return;

                    th.style.cursor = 'pointer';
                    th.style.userSelect = 'none';
                    th.classList.add('select-none');

                    if (!th.querySelector('.saas-sort-icon')) {
                        const iconSpan = document.createElement('span');
                        iconSpan.className = 'saas-sort-icon inline-block ml-1 text-slate-300 text-[9px] align-middle transition-colors';
                        iconSpan.innerHTML = '<i class="fas fa-sort"></i>';
                        th.appendChild(iconSpan);
                    }

                    th.addEventListener('click', function(e) {
                        if (e.target.closest('button') || e.target.closest('input') || e.target.closest('select') || e.target.closest('a')) {
                            return;
                        }

                        const currentDir = th.getAttribute('data-sort-dir');
                        const newDir = currentDir === 'asc' ? 'desc' : 'asc';

                        // Reset all other headers in this table
                        headers.forEach(function(otherTh) {
                            otherTh.removeAttribute('data-sort-dir');
                            const otherIcon = otherTh.querySelector('.saas-sort-icon');
                            if (otherIcon) {
                                otherIcon.innerHTML = '<i class="fas fa-sort"></i>';
                                otherIcon.className = 'saas-sort-icon inline-block ml-1 text-slate-300 text-[9px] align-middle transition-colors';
                            }
                        });

                        // Set direction on clicked header
                        th.setAttribute('data-sort-dir', newDir);
                        const icon = th.querySelector('.saas-sort-icon');
                        if (icon) {
                            if (newDir === 'asc') {
                                icon.innerHTML = '<i class="fas fa-sort-up"></i>';
                                icon.className = 'saas-sort-icon inline-block ml-1 text-cyan-600 font-bold text-[9px] align-middle transition-colors';
                            } else {
                                icon.innerHTML = '<i class="fas fa-sort-down"></i>';
                                icon.className = 'saas-sort-icon inline-block ml-1 text-cyan-600 font-bold text-[9px] align-middle transition-colors';
                            }
                        }

                        sortTable(table, colIndex, newDir);
                    });
                });
            });
        }

        function sortTable(table, colIndex, direction) {
            const tbody = table.querySelector('tbody');
            if (!tbody) return;

            const rows = Array.from(tbody.querySelectorAll('tr'));
            if (rows.length <= 1) return;

            function getVal(row) {
                const cell = row.children[colIndex];
                if (!cell) return '';
                if (cell.dataset.sortValue !== undefined) {
                    return cell.dataset.sortValue;
                }
                return cell.innerText.trim();
            }

            function parseValue(val) {
                const clean = val.replace(/^[৳$€£\s]+/, '').replace(/,/g, '').trim();
                const num = parseFloat(clean);
                if (!isNaN(num) && /^-?\d+(\.\d+)?(\s*(KB|MB|GB|TB|Kbps|Mbps|Gbps|ms|\/UDP|\/TCP|%))?$/i.test(clean)) {
                    return { type: 'num', value: num };
                }
                return { type: 'str', value: val.toLowerCase() };
            }

            const isAsc = direction === 'asc';

            rows.sort(function(rowA, rowB) {
                const aRaw = getVal(rowA);
                const bRaw = getVal(rowB);

                const a = parseValue(aRaw);
                const b = parseValue(bRaw);

                if (a.type === 'num' && b.type === 'num') {
                    return isAsc ? a.value - b.value : b.value - a.value;
                }

                return isAsc 
                    ? a.value.localeCompare(b.value, undefined, { numeric: true, sensitivity: 'base' })
                    : b.value.localeCompare(a.value, undefined, { numeric: true, sensitivity: 'base' });
            });

            const fragment = document.createDocumentFragment();
            rows.forEach(function(row) {
                fragment.appendChild(row);
            });
            tbody.appendChild(fragment);

            table.dispatchEvent(new CustomEvent('saas-table:sorted', {
                bubbles: true,
                detail: { colIndex: colIndex, direction: direction }
            }));
        }

        window.sortSaasTable = sortTable;
        window.initSaasTableSort = initSaasTableSort;

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initSaasTableSort);
        } else {
            initSaasTableSort();
        }

        document.addEventListener('alpine:initialized', function() {
            setTimeout(initSaasTableSort, 50);
        });
    })();
    </script>

    {{-- Page Specific Script Hooks --}}
    @yield('scripts')
    @stack('scripts')
</body>
</html>
