<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"><meta name="theme-color" content="#0891b2">
    <meta name="csrf-token" content="{{ csrf_token() }}"><meta name="mobile-web-app-capable" content="yes"><link rel="manifest" href="{{ route('customer.portal.manifest', ['tenant' => $tenant->slug]) }}"><title>@yield('title', 'Customer Portal') · {{ $tenant->company_name ?? $tenant->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>body{padding-bottom:env(safe-area-inset-bottom)}.safe-bottom{padding-bottom:max(.75rem,env(safe-area-inset-bottom))}</style>
    @stack('styles')
</head>
<body class="min-h-screen bg-slate-100 text-slate-800 antialiased">
    @php($unreadCustomerNotifications = auth('customer')->user()?->unreadNotifications()->count() ?? 0)
    <div class="min-h-screen pb-20 sm:pb-6">
        <header class="sticky top-0 z-30 border-b border-slate-200/80 bg-white/95 backdrop-blur"><div class="max-w-2xl mx-auto px-4 py-3 flex items-center justify-between gap-3">
            <div class="min-w-0 flex items-center gap-2.5"><div class="w-9 h-9 rounded-xl bg-cyan-600 text-white flex items-center justify-center font-bold flex-shrink-0">{{ strtoupper(substr($tenant->company_name ?? $tenant->name, 0, 1)) }}</div><div class="min-w-0"><p class="text-[10px] text-cyan-600 font-semibold uppercase tracking-wide">Customer Portal</p><h1 class="text-sm font-bold truncate">{{ $tenant->company_name ?? $tenant->name }}</h1></div></div>
            <div class="flex items-center gap-2"><span class="relative w-9 h-9 rounded-xl bg-slate-100 text-slate-500 flex items-center justify-center" title="Notifications"><i class="fas fa-bell text-xs"></i>@if($unreadCustomerNotifications > 0)<span class="absolute -top-1 -right-1 min-w-4 h-4 px-1 rounded-full bg-rose-600 text-white text-[9px] flex items-center justify-center">{{ min($unreadCustomerNotifications, 99) }}</span>@endif</span><form method="POST" action="{{ route('customer.portal.logout', ['tenant' => $tenant->slug]) }}">@csrf<button aria-label="Sign out" class="w-9 h-9 rounded-xl bg-slate-100 text-slate-500 hover:bg-slate-200 flex items-center justify-center"><i class="fas fa-arrow-right-from-bracket text-xs"></i></button></form></div>
        </div></header>
        <main class="max-w-2xl mx-auto px-4 py-4">@yield('content')</main>
    </div>
    <nav class="fixed bottom-0 inset-x-0 z-40 bg-white/95 backdrop-blur border-t border-slate-200 safe-bottom sm:hidden"><div class="max-w-2xl mx-auto grid grid-cols-5 h-16">
        @foreach([['Home','fa-house','customer.portal.dashboard'],['Bills','fa-file-invoice-dollar','customer.portal.invoices'],['Payments','fa-credit-card','customer.portal.payments'],['Support','fa-headset','customer.portal.tickets'],['Profile','fa-user',null]] as [$label, $icon, $route])
            @if($route)<a href="{{ route($route, ['tenant' => $tenant->slug]) }}" class="flex flex-col items-center justify-center gap-1 text-cyan-600 text-[10px] font-semibold"><i class="fas {{ $icon }} text-sm"></i><span>{{ $label }}</span></a>@else<span class="flex flex-col items-center justify-center gap-1 text-slate-300 text-[10px] font-semibold" title="Available in the next phase"><i class="fas {{ $icon }} text-sm"></i><span>{{ $label }}</span></span>@endif
        @endforeach
    </div></nav>
    @stack('scripts')
    <script>if ('serviceWorker' in navigator) { window.addEventListener('load', () => navigator.serviceWorker.register('/customer-portal-sw.js', { scope: '/portal/' }).catch(() => {})); }</script>
</body>
</html>
