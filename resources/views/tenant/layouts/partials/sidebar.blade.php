@php
    $authUser = auth()->user();
    $sidebarMenus = \App\Services\SidebarMenuService::resolveForUser($authUser);
    $consoleRoleLabel = match (true) {
        $authUser && $authUser->isCollector() => 'Bill Collector',
        $authUser && $authUser->isTechnician() => 'NOC & Field Tech',
        $authUser && $authUser->role === 'isp_manager' => 'ISP Manager',
        default => 'ISP Admin Console',
    };
    $roleAccent = match (true) {
        $authUser && $authUser->isCollector() => ['dot' => 'bg-amber-500', 'text' => 'text-amber-700'],
        $authUser && $authUser->isTechnician() => ['dot' => 'bg-cyan-500', 'text' => 'text-cyan-700'],
        $authUser && $authUser->role === 'isp_manager' => ['dot' => 'bg-indigo-500', 'text' => 'text-indigo-700'],
        default => ['dot' => 'bg-blue-600', 'text' => 'text-blue-600'],
    };

    $brandName = (isset($tenant) && $tenant)
        ? ($tenant->company_name ?: ($tenant->name ?? 'ISP Portal'))
        : 'ISP Portal';

    $resolveBrandAsset = static function (?string $path): ?string {
        if (empty($path)) {
            return null;
        }
        return (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/'))
            ? $path
            : asset('storage/' . ltrim($path, '/'));
    };

    $brandFaviconUrl = (isset($tenant) && $tenant) ? $resolveBrandAsset($tenant->favicon ?? null) : null;
    $brandLogoUrl = (isset($tenant) && $tenant) ? $resolveBrandAsset($tenant->logo ?? null) : null;

    // Detect wide horizontal wordmark logos so they don't duplicate or crush the sidebar title text
    $isWideLogo = false;
    if (!$brandFaviconUrl && isset($tenant) && $tenant && !empty($tenant->logo) && !str_starts_with($tenant->logo, 'http')) {
        $localLogoPath = public_path('storage/' . ltrim($tenant->logo, '/'));
        if (is_file($localLogoPath)) {
            $logoDim = @getimagesize($localLogoPath);
            if ($logoDim && !empty($logoDim[0]) && !empty($logoDim[1]) && ($logoDim[0] / $logoDim[1]) > 1.6) {
                $isWideLogo = true;
            }
        }
    }

    $brandSquareIconUrl = $brandFaviconUrl ?: (!$isWideLogo ? $brandLogoUrl : null);
    $brandInitial = mb_strtoupper(mb_substr(trim($brandName), 0, 1, 'UTF-8'), 'UTF-8') ?: 'I';
@endphp

<!-- Mobile Off-Canvas Sidebar Backdrop -->
<div x-show="mobileSidebar" 
     x-transition:enter="transition-opacity ease-linear duration-150"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition-opacity ease-linear duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     @click="mobileSidebar = false"
     class="fixed inset-0 z-40 bg-slate-900/40 backdrop-blur-xs lg:hidden" 
     x-cloak></div>

@push('styles')
<style>
    .sidebar-collapsed .sidebar-brand-text,
    .sidebar-collapsed .sidebar-brand-wide,
    .sidebar-collapsed .sidebar-search,
    .sidebar-collapsed .sidebar-user-text {
        display: none !important;
    }
    .sidebar-collapsed .sidebar-brand-collapsed-icon {
        display: flex !important;
    }
    .sidebar-collapsed .sidebar-nav-container a > span,
    .sidebar-collapsed .sidebar-nav-container button span,
    .sidebar-collapsed .sidebar-nav-container button > .fa-chevron-right,
    .sidebar-collapsed .sidebar-nav-container [x-collapse] {
        display: none !important;
    }
    .sidebar-collapsed .sidebar-nav-container a,
    .sidebar-collapsed .sidebar-nav-container button {
        justify-content: center !important;
        padding-left: 0.5rem !important;
        padding-right: 0.5rem !important;
    }
    .sidebar-collapsed .sidebar-nav-container button > div {
        justify-content: center !important;
        margin: 0 !important;
        gap: 0 !important;
    }
    .sidebar-collapsed .sidebar-nav-container a i,
    .sidebar-collapsed .sidebar-nav-container button i {
        margin: 0 !important;
        font-size: 13px !important;
    }
    .sidebar-collapsed .sidebar-user-footer {
        justify-content: center !important;
    }
    .sidebar-collapsed .sidebar-user-footer form {
        display: none !important;
    }
</style>
@endpush

<!-- Sleek Compact Left Sidebar (60 Width or 68px Collapsed on Desktop) -->
<aside class="sidebar-fixed fixed inset-y-0 left-0 z-50 bg-white border-r border-slate-200 flex flex-col lg:w-60"
       :class="[
           mobileSidebar ? 'translate-x-0 shadow-xl w-60' : '-translate-x-full lg:translate-x-0',
           sidebarCollapsed ? 'sidebar-collapsed' : ''
       ]">
    
    <!-- Compact Brand Header (Aligned 56px Height with Topbar) -->
    <div class="h-14 flex-shrink-0 flex items-center border-b border-slate-200 bg-white transition-all"
         :class="sidebarCollapsed ? 'justify-center px-2' : 'justify-between px-2.5 gap-1.5'">
        <a href="{{ route('tenant.dashboard') }}" 
           class="flex items-center gap-2 min-w-0 group" 
           :class="sidebarCollapsed ? 'justify-center' : 'flex-1'"
           :title='sidebarCollapsed ? {!! json_encode($brandName . " — " . __($consoleRoleLabel), JSON_HEX_APOS) !!} : {!! json_encode($brandName, JSON_HEX_APOS) !!}'>
            @if(!$brandSquareIconUrl && $brandLogoUrl)
                {{-- Wide horizontal logo fallback when no square icon/favicon is configured --}}
                <div class="sidebar-brand-wide flex-1 min-w-0 flex flex-col justify-center">
                    <img src="{{ $brandLogoUrl }}" alt="{{ $brandName }}" class="h-7 max-w-[168px] w-auto object-contain object-left">
                    <span class="flex items-center gap-1.5 mt-0.5 min-w-0">
                        <span class="w-1.5 h-1.5 rounded-full {{ $roleAccent['dot'] }} flex-shrink-0"></span>
                        <span class="text-[9.5px] font-semibold {{ $roleAccent['text'] }} whitespace-nowrap truncate leading-none">{{ __($consoleRoleLabel) }}</span>
                    </span>
                </div>
                <div class="sidebar-brand-collapsed-icon hidden w-9 h-9 rounded-lg bg-gradient-to-br from-blue-600 to-cyan-600 flex-shrink-0 items-center justify-center text-white font-bold text-sm shadow-xs">
                    {{ $brandInitial }}
                </div>
            @else
                @if($brandSquareIconUrl)
                    <div class="w-9 h-9 rounded-lg bg-white border border-slate-200/90 p-0.5 flex-shrink-0 flex items-center justify-center shadow-2xs overflow-hidden group-hover:border-blue-300 transition">
                        <img src="{{ $brandSquareIconUrl }}" alt="{{ $brandName }}" class="w-full h-full object-contain rounded-[5px]">
                    </div>
                @else
                    <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-blue-600 to-cyan-600 flex-shrink-0 flex items-center justify-center text-white font-bold text-sm shadow-xs">
                        {{ $brandInitial }}
                    </div>
                @endif
                <div class="sidebar-brand-text flex-1 min-w-0 flex flex-col justify-center leading-tight">
                    <span class="font-bold text-slate-900 text-[12px] tracking-[-0.01em] truncate block">{{ $brandName }}</span>
                    <span class="flex items-center gap-1.5 mt-0.5 min-w-0">
                        <span class="w-1.5 h-1.5 rounded-full {{ $roleAccent['dot'] }} flex-shrink-0"></span>
                        <span class="text-[10px] font-semibold {{ $roleAccent['text'] }} whitespace-nowrap truncate leading-none">{{ __($consoleRoleLabel) }}</span>
                    </span>
                </div>
            @endif
        </a>

        <!-- Mobile Close Button -->
        <button type="button" @click="mobileSidebar = false" class="lg:hidden flex-shrink-0 p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition cursor-pointer">
            <i class="fas fa-times text-xs"></i>
        </button>
    </div>

    <!-- Sleek Search Filter -->
    <div class="sidebar-search p-2.5 border-b border-slate-100 bg-slate-50/50">
        <div class="relative">
            <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-[10px]"></i>
            <input type="search" 
                   name="menu_quick_search_no_autofill"
                   autocomplete="off"
                   autocorrect="off"
                   autocapitalize="off"
                   spellcheck="false"
                   data-lpignore="true"
                   data-form-type="other"
                   readonly
                   onfocus="this.removeAttribute('readonly');"
                   x-model="filterQuery" 
                   placeholder="{{ __('Filter menu...') }}" 
                   class="w-full pl-7 pr-6 py-1.5 text-[11px] rounded-lg border border-slate-200 bg-white text-slate-700 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition placeholder:text-slate-400 shadow-2xs">
            <button type="button" 
                    x-show="filterQuery" 
                    @click="filterQuery = ''" 
                    class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 cursor-pointer"
                    style="display: none;">
                <i class="fas fa-times-circle text-[10px]"></i>
            </button>
        </div>
    </div>

    <!-- Config-Driven Navigation Menu (Loaded from config/menus/*.php via SidebarMenuService) -->
    <div class="sidebar-nav-container flex-1 overflow-y-auto p-2 space-y-1"
         @click="if (sidebarCollapsed) { toggleSidebar(); }">
        @foreach($sidebarMenus as $item)
            @if($item['type'] === 'link')
                <a href="{{ $item['url'] }}" 
                   x-show='!filterQuery || {!! json_encode($item["keywords"], JSON_HEX_APOS) !!}.includes(filterQuery.toLowerCase())'
                   class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg border transition text-[11.5px] font-semibold shadow-2xs cursor-pointer {{ $item['is_active'] ? $item['active_cls'] : 'border-slate-200/90 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300' }}">
                    <i class="{{ $item['icon'] }} w-4 text-center text-xs {{ $item['is_active'] ? $item['icon_active'] : 'text-slate-500' }}"></i>
                    <span>{{ __($item['title']) }}</span>
                </a>
            @else
                <div x-data="{ open: {{ $item['is_active'] ? 'true' : 'false' }} }" 
                     x-show='!filterQuery || {!! json_encode($item["keywords"], JSON_HEX_APOS) !!}.includes(filterQuery.toLowerCase())'
                     class="space-y-1">
                    <button type="button" 
                            @click="open = !open" 
                            class="w-full flex items-center justify-between px-2.5 py-1.5 rounded-lg border transition text-[11.5px] font-semibold shadow-2xs cursor-pointer {{ $item['is_active'] ? $item['active_cls'] : 'border-slate-200/90 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300' }}">
                        <div class="flex items-center gap-2">
                            <i class="{{ $item['icon'] }} w-4 text-center text-xs {{ $item['is_active'] ? $item['icon_active'] : 'text-slate-500' }}"></i>
                            <span>{{ __($item['title']) }}</span>
                        </div>
                        <i class="fas fa-chevron-right text-[8.5px] text-slate-400 transition-transform duration-150" :class="open ? 'rotate-90 text-slate-600' : ''"></i>
                    </button>

                    <!-- Sub-menu Items -->
                    <div x-show="open || filterQuery" x-collapse class="pl-2.5 pr-0.5 space-y-1" style="{{ $item['is_active'] ? '' : 'display: none;' }}">
                        @foreach($item['children'] as $child)
                            <a href="{{ $child['url'] }}" 
                               x-show='!filterQuery || {!! json_encode($child["keywords"] . " " . mb_strtolower($item["title"] . " " . __($item["title"]), "UTF-8"), JSON_HEX_APOS) !!}.includes(filterQuery.toLowerCase())'
                               class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ $child['is_active'] ? $child['active_cls'] : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 font-medium' }}">
                                @if(!empty($child['badge']))
                                    <span class="flex items-center justify-between">
                                        <span>{{ __($child['title']) }}</span>
                                        <span class="px-1.5 py-0.2 rounded text-[9px] bg-amber-100 text-amber-800 font-bold">{{ __($child['badge']) }}</span>
                                    </span>
                                @else
                                    {{ __($child['title']) }}
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        @endforeach
    </div>

    <!-- Sidebar User Footer -->
    <div class="sidebar-user-footer p-2.5 border-t border-slate-100 bg-slate-50/70 flex items-center justify-between">
        <div class="flex items-center gap-2 overflow-hidden">
            <div class="w-6 h-6 rounded-md bg-blue-100 text-blue-700 flex-shrink-0 flex items-center justify-center font-bold text-[10px]">
                {{ strtoupper(substr($authUser->name ?? 'A', 0, 1)) }}
            </div>
            <div class="sidebar-user-text flex flex-col overflow-hidden leading-none">
                <span class="text-[11px] font-bold text-slate-800 truncate">{{ $authUser?->name ?? 'Admin' }}</span>
                <span class="text-[9px] text-slate-400">{{ __($authUser?->role_badge['label'] ?? ($authUser && $authUser->isCollector() ? 'Bill Collector' : 'ISP Staff')) }}</span>
            </div>
        </div>
        <form action="{{ route('tenant.logout') }}" method="POST">
            @csrf
            <button type="submit" title="{{ __('Sign Out') }}" class="p-1 text-slate-400 hover:text-rose-600 transition cursor-pointer">
                <i class="fas fa-sign-out-alt text-xs"></i>
            </button>
        </form>
    </div>

    <!-- Sleek Desktop Collapse Toggle Footer Bar -->
    <div class="hidden lg:flex items-center justify-between p-2 border-t border-slate-200/60 bg-white text-slate-500">
        <button type="button" 
                @click="toggleSidebar()" 
                class="w-full flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-slate-100 hover:text-slate-800 transition cursor-pointer"
                :class="sidebarCollapsed ? 'justify-center' : 'justify-between'"
                :title="sidebarCollapsed ? '{{ __('Expand Sidebar (Ctrl+B)') }}' : '{{ __('Collapse Sidebar (Ctrl+B)') }}'">
            <div class="flex items-center gap-2 overflow-hidden">
                <i class="fas text-xs transition-transform duration-200" 
                   :class="sidebarCollapsed ? 'fa-angles-right text-cyan-600' : 'fa-angles-left text-slate-400'"></i>
                <span x-show="!sidebarCollapsed" class="text-[11px] font-medium tracking-tight">{{ __('Collapse') }}</span>
            </div>
            <kbd x-show="!sidebarCollapsed" class="text-[9px] font-mono bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200 text-slate-400">Ctrl+B</kbd>
        </button>
    </div>
</aside>
