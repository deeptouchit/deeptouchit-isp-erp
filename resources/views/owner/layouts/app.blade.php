@extends('layouts.base')

@section('body')
@php
    $ownerUser = auth()->user();
    $globalSettings = \App\Models\Setting::whereNull('tenant_id')->pluck('value', 'key')->toArray();
    $appLogo = $globalSettings['app_logo'] ?? null;
    $appName = $globalSettings['app_name'] ?? 'SomitySoft SaaS';
    $appShortName = $globalSettings['app_short_name'] ?? 'SomitySoft';
    $unreadNotifsCount = $ownerUser ? $ownerUser->unreadNotifications()->count() : 0;
@endphp

<div class="min-h-screen flex flex-col bg-slate-100/70 text-slate-800 text-xs antialiased font-sans" 
     x-data="{ 
        mobileSidebar: false, 
        filterQuery: '' 
     }">

    <!-- Left Sidebar Partial -->
    @include('owner.layouts.partials.sidebar')

    <!-- Main Content Wrapper (Offset with 60 on Desktop) -->
    <div class="lg:pl-60 flex flex-col flex-1 min-h-screen">
        
        <!-- Professional Sticky Topbar Partial -->
        @include('owner.layouts.partials.topbar')

        <!-- Main Body Canvas -->
        <main class="flex-1 p-3 sm:p-5 bg-slate-100/70 pb-16 lg:pb-6">
            <div class="max-w-6xl mx-auto space-y-4">
                @yield('content')
            </div>
        </main>

        <!-- Compact Clean Dynamic Footer -->
        <footer class="bg-white border-t border-slate-200 px-4 sm:px-6 py-2.5 flex flex-col sm:flex-row items-center justify-between gap-1.5 text-[11px] text-slate-500">
            <div>
                {{ $globalSettings['footer_copyright_text'] ?? ('© ' . date('Y') . ' SomitySoft SaaS. All rights reserved.') }}
                @if(($globalSettings['show_developer_credit'] ?? '1') == '1' && !empty($globalSettings['footer_developed_by']))
                    <span class="mx-1 text-slate-300">|</span>
                    <a href="{{ $globalSettings['footer_developer_url'] ?? 'https://somitysoft.com' }}" target="_blank" class="hover:text-blue-600 transition font-medium">
                        {{ $globalSettings['footer_developed_by'] }}
                    </a>
                @endif
            </div>
            <div class="text-[10.5px] font-mono text-slate-400">
                {{ $globalSettings['app_version'] ?? 'v3.2 Enterprise' }}
            </div>
        </footer>

    </div>

    <!-- Mobile Bottom App Bar (Compact) -->
    <nav class="lg:hidden fixed bottom-0 left-0 right-0 z-40 h-14 bg-white/95 backdrop-blur-md border-t border-slate-200 flex items-center justify-around px-2 safe-bottom">
        <a href="{{ route('owner.dashboard') }}" class="flex flex-col items-center gap-0.5 py-1 px-2 text-[10px] font-medium {{ request()->routeIs('owner.dashboard') ? 'text-blue-600 font-bold' : 'text-slate-500' }}">
            <i class="fas fa-chart-pie text-sm"></i>
            <span>Home</span>
        </a>
        <a href="{{ route('owner.tenants.index') }}" class="flex flex-col items-center gap-0.5 py-1 px-2 text-[10px] font-medium {{ request()->routeIs('owner.tenants*') ? 'text-blue-600 font-bold' : 'text-slate-500' }}">
            <i class="fas fa-building text-sm"></i>
            <span>Tenants</span>
        </a>
        <a href="{{ route('owner.payment-gateways.index') }}" class="flex flex-col items-center gap-0.5 py-1 px-2 text-[10px] font-medium {{ request()->routeIs('owner.payment-gateways*') ? 'text-blue-600 font-bold' : 'text-slate-500' }}">
            <i class="fas fa-credit-card text-sm"></i>
            <span>PGW</span>
        </a>
        <a href="{{ route('owner.sms-gateways.index') }}" class="flex flex-col items-center gap-0.5 py-1 px-2 text-[10px] font-medium {{ request()->routeIs('owner.sms-gateways*') ? 'text-blue-600 font-bold' : 'text-slate-500' }}">
            <i class="fas fa-comment-dots text-sm"></i>
            <span>SMS</span>
        </a>
        <button type="button" @click="mobileSidebar = true" class="flex flex-col items-center gap-0.5 py-1 px-2 text-[10px] font-medium text-slate-500">
            <i class="fas fa-bars text-sm"></i>
            <span>Menu</span>
        </button>
    </nav>

</div>
@endsection
