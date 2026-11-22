@extends('layouts.base')

{{-- 1. Forward Title to Base Layout --}}
@section('title')
@hasSection('title')
@yield('title') - {{ $reseller->name ?? ($tenant->company_name ?? 'Reseller Portal') }}
@else
{{ $reseller->name ?? ($tenant->company_name ?? 'ISP Reseller Partner Portal') }}
@endif
@endsection

{{-- 2. Page Specific Style Hooks Passthrough --}}
@section('page_style')
    @yield('reseller_style')
@endsection

{{-- 3. Main Body Structure --}}
@section('body')
@php
    $authUser = auth()->user();
    if (!isset($reseller) || !$reseller) {
        $reseller = $authUser ? ($authUser->reseller ?: \App\Models\TenantReseller::find($authUser->reseller_id)) : null;
    }
    if (!isset($tenant) || !$tenant) {
        $tenant = $reseller ? $reseller->tenant : ($authUser ? ($authUser->tenant ?: \App\Models\Tenant::find($authUser->tenant_id)) : null);
    }
    if (!$tenant) {
        $tenant = \App\Models\Tenant::first();
    }
@endphp

{{-- Application Container with Alpine.js Reactive State --}}
<div class="min-h-screen flex flex-col bg-slate-100/70 text-slate-800 text-xs antialiased font-sans selection:bg-purple-600 selection:text-white" 
     x-data="{
        mobileSidebar: false,
        sidebarCollapsed: document.documentElement.classList.contains('sidebar-collapsed'),
        filterQuery: '',
        isDesktop() {
            return window.matchMedia('(min-width: 1024px)').matches;
        },
        toggleSidebar() {
            if (!this.isDesktop()) return;
            this.sidebarCollapsed = !this.sidebarCollapsed;
            document.documentElement.classList.toggle('sidebar-collapsed', this.sidebarCollapsed);
            if (this.sidebarCollapsed) this.filterQuery = '';
            try {
                localStorage.setItem('isp_sidebar_collapsed', this.sidebarCollapsed ? '1' : '0');
            } catch {}
        }
     }"
     @keydown.window.ctrl.b="if (isDesktop()) { $event.preventDefault(); toggleSidebar(); }"
     @keydown.escape.window="mobileSidebar = false">

    {{-- Left Navigation Sidebar Partial --}}
    @include('reseller.layouts.partials.sidebar')

    {{-- Main Workspace Viewport (Offset 60 on Desktop for Fixed Sidebar) --}}
    <div class="main-viewport flex flex-col flex-1 min-h-screen">
        
        {{-- Professional Sticky Topbar --}}
        @include('reseller.layouts.partials.topbar')

        {{-- Main Body Canvas --}}
        <main class="flex-1 p-2.5 sm:p-4 md:p-5 bg-slate-100/70 pb-20 lg:pb-6">
            <div class="max-w-7xl mx-auto space-y-3 sm:space-y-4">
                @yield('content')
            </div>
        </main>

        {{-- Dynamic SaaS Compact Footer --}}
        <footer class="bg-white border-t border-slate-200 px-4 sm:px-6 py-2.5 flex flex-col sm:flex-row items-center justify-between gap-1.5 text-[11px] text-slate-500">
            <div>
                &copy; {{ date('Y') }} <span class="font-medium text-slate-700">{{ $reseller ? $reseller->name : ($tenant->company_name ?? 'Reseller Portal') }}</span>. All rights reserved.
            </div>
            <div class="text-[10.5px] font-mono text-slate-400 flex items-center gap-1.5">
                <span>Partner with</span>
                <span class="font-semibold text-slate-600">{{ $tenant->company_name ?? 'SomitySoft Network' }}</span>
            </div>
        </footer>

    </div>

    {{-- Mobile Bottom Quick Navigation Bar --}}
    <nav class="lg:hidden fixed bottom-0 left-0 right-0 z-40 h-14 bg-white/95 backdrop-blur-md border-t border-slate-200 flex items-center justify-around px-2 safe-bottom shadow-lg">
        <a href="{{ route('reseller.dashboard') }}" 
           class="flex flex-col items-center gap-0.5 py-1 px-2 text-[10px] font-medium transition {{ request()->routeIs('reseller.dashboard') ? 'text-purple-600 font-bold' : 'text-slate-500 hover:text-slate-800' }}">
            <i class="fas fa-chart-pie text-sm"></i>
            <span>{{ __('Home') }}</span>
        </a>
        @if($authUser?->isResellerCollector())
            <a href="{{ route('reseller.customers.index', ['status' => 'due']) }}"
               class="flex flex-col items-center gap-0.5 py-1 px-2 text-[10px] font-medium {{ request()->routeIs('reseller.customers*') ? 'text-cyan-700 font-bold' : 'text-slate-500' }}">
                <i class="fas fa-file-invoice-dollar text-sm" aria-hidden="true"></i>
                <span>{{ __('Due') }}</span>
            </a>
            <a href="{{ route('reseller.collections.index') }}"
               class="flex flex-col items-center gap-0.5 py-1 px-2 text-[10px] font-medium {{ request()->routeIs('reseller.collections*') ? 'text-cyan-700 font-bold' : 'text-slate-500' }}">
                <i class="fas fa-receipt text-sm" aria-hidden="true"></i>
                <span>{{ __('Collections') }}</span>
            </a>
        @else
        <a href="{{ route('reseller.customers.index') }}" 
           class="flex flex-col items-center gap-0.5 py-1 px-2 text-[10px] font-medium transition {{ request()->routeIs('reseller.customers*') ? 'text-purple-600 font-bold' : 'text-slate-500 hover:text-slate-800' }}">
            <i class="fas fa-users text-sm"></i>
            <span>{{ __('Customers') }}</span>
        </a>
        <a href="{{ route('reseller.recharge.index') }}" 
           class="flex flex-col items-center gap-0.5 py-1 px-2 text-[10px] font-medium transition {{ request()->routeIs('reseller.recharge*') || request()->routeIs('reseller.ledger*') ? 'text-purple-600 font-bold' : 'text-slate-500 hover:text-slate-800' }}">
            <i class="fas fa-wallet text-sm"></i>
            <span>{{ __('Wallet') }}</span>
        </a>
        @endif
        <button type="button" 
                @click="mobileSidebar = true" aria-controls="reseller-sidebar" :aria-expanded="mobileSidebar"
                class="flex flex-col items-center gap-0.5 py-1 px-2 text-[10px] font-medium text-slate-500 hover:text-slate-800 transition cursor-pointer">
            <i class="fas fa-bars text-sm"></i>
            <span>{{ __('Menu') }}</span>
        </button>
    </nav>

</div>
@endsection
