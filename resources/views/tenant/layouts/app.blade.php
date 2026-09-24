@extends('layouts.base')

{{-- 1. Forward Title to Base Layout --}}
@section('title')
@hasSection('title')
@yield('title') - {{ $tenant->company_name ?? ($tenant->name ?? 'ISP Portal') }}
@else
{{ $tenant->company_name ?? ($tenant->name ?? config('app.name', 'SomitySoft SaaS Platform')) }}
@endif
@endsection

{{-- 2. Page Specific Style Hooks Passthrough --}}
@section('page_style')
    @yield('tenant_style')
@endsection

{{-- 3. Main Body Structure --}}
@section('body')
@php
    $authUser = auth()->user();
    if (!isset($tenant) || !$tenant) {
        $tenant = $authUser ? ($authUser->tenant ?: \App\Models\Tenant::find($authUser->tenant_id)) : null;
    }
    if (!$tenant) {
        $tenant = \App\Models\Tenant::first();
    }
    $daysRemaining = null;
    if ($tenant && $tenant->subscription_expires_at) {
        $daysRemaining = (int) now()->diffInDays($tenant->subscription_expires_at, false);
    }
@endphp

{{-- Application Container with Alpine.js Reactive State --}}
<div class="min-h-screen flex flex-col bg-slate-100/70 text-slate-800 text-xs antialiased font-sans selection:bg-blue-600 selection:text-white" 
     x-data="{ 
        mobileSidebar: false, 
        filterQuery: '' 
     }">

    {{-- Left Navigation Sidebar Partial --}}
    @include('tenant.layouts.partials.sidebar')

    {{-- Main Workspace Viewport (Offset 60 on Desktop for Fixed Sidebar) --}}
    <div class="lg:pl-60 flex flex-col flex-1 min-h-screen">
        
        {{-- Professional Sticky Topbar --}}
        @include('tenant.layouts.partials.topbar')

        {{-- Main Body Canvas --}}
        <main class="flex-1 p-3 sm:p-5 bg-slate-100/70 pb-16 lg:pb-6">
            <div class="max-w-6xl mx-auto space-y-4">
                @yield('content')
            </div>
        </main>

        {{-- Dynamic SaaS Compact Footer --}}
        <footer class="bg-white border-t border-slate-200 px-4 sm:px-6 py-2.5 flex flex-col sm:flex-row items-center justify-between gap-1.5 text-[11px] text-slate-500">
            <div>
                &copy; {{ date('Y') }} <span class="font-medium text-slate-700">{{ $tenant ? ($tenant->company_name ?: ($tenant->name ?? 'ISP Portal')) : 'ISP Portal' }}</span>. All rights reserved.
            </div>
            <div class="text-[10.5px] font-mono text-slate-400 flex items-center gap-1.5">
                <span>Powered by</span>
                <span class="font-semibold text-slate-600">SomitySoft Cloud</span>
            </div>
        </footer>

    </div>

    {{-- Mobile Bottom Quick Navigation Bar --}}
    <nav class="lg:hidden fixed bottom-0 left-0 right-0 z-40 h-14 bg-white/95 backdrop-blur-md border-t border-slate-200 flex items-center justify-around px-2 safe-bottom">
        <a href="{{ route('tenant.dashboard') }}" 
           class="flex flex-col items-center gap-0.5 py-1 px-2 text-[10px] font-medium transition {{ request()->routeIs('tenant.dashboard*') ? 'text-blue-600 font-bold' : 'text-slate-500 hover:text-slate-800' }}">
            <i class="fas fa-chart-pie text-sm"></i>
            <span>Home</span>
        </a>
        <a href="{{ route('tenant.billing.dashboard') }}" 
           class="flex flex-col items-center gap-0.5 py-1 px-2 text-[10px] font-medium transition {{ request()->routeIs('tenant.billing*') ? 'text-blue-600 font-bold' : 'text-slate-500 hover:text-slate-800' }}">
            <i class="fas fa-credit-card text-sm"></i>
            <span>Billing</span>
        </a>
        <a href="{{ route('tenant.tickets.index') }}" 
           class="flex flex-col items-center gap-0.5 py-1 px-2 text-[10px] font-medium transition {{ request()->routeIs('tenant.tickets*') ? 'text-blue-600 font-bold' : 'text-slate-500 hover:text-slate-800' }}">
            <i class="fas fa-headset text-sm"></i>
            <span>Support</span>
        </a>
        <button type="button" 
                @click="mobileSidebar = true" 
                class="flex flex-col items-center gap-0.5 py-1 px-2 text-[10px] font-medium text-slate-500 hover:text-slate-800 transition cursor-pointer">
            <i class="fas fa-bars text-sm"></i>
            <span>Menu</span>
        </button>
    </nav>

</div>
@endsection
