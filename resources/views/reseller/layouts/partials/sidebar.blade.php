@php
    $authUser = auth()->user();
    $currentReseller = $reseller ?? ($authUser?->reseller ?: \App\Models\TenantReseller::find($authUser?->reseller_id));
    $resellerName = $currentReseller->name ?? __('Reseller Partner');
    $walletBal = (float) ($currentReseller->wallet_balance ?? 0);
    $availBal = (float) ($currentReseller->total_available_balance ?? $walletBal);
    $sidebarMenus = \App\Services\SidebarMenuService::resolveForUser($authUser);
@endphp

@push('styles')
<style>
    @media (min-width: 1024px) {
        html.sidebar-collapsed #reseller-sidebar .reseller-sidebar-label,
        html.sidebar-collapsed #reseller-sidebar .reseller-sidebar-search,
        html.sidebar-collapsed #reseller-sidebar .reseller-sidebar-submenu,
        html.sidebar-collapsed #reseller-sidebar .reseller-sidebar-balance,
        html.sidebar-collapsed #reseller-sidebar .reseller-sidebar-shortcut {
            display: none !important;
        }
        html.sidebar-collapsed #reseller-sidebar .reseller-sidebar-brand,
        html.sidebar-collapsed #reseller-sidebar .reseller-sidebar-item,
        html.sidebar-collapsed #reseller-sidebar .reseller-sidebar-toggle {
            justify-content: center;
        }
    }
</style>
@endpush

<div x-show="mobileSidebar"
     x-transition
     @click="mobileSidebar = false"
     class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-40 lg:hidden"
     style="display:none"
     x-cloak></div>

<aside id="reseller-sidebar" aria-label="{{ __('Menu') }}" :class="mobileSidebar ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
       class="sidebar-fixed fixed inset-y-0 left-0 z-50 w-60 bg-white border-r border-slate-200 flex flex-col transition-transform duration-200 ease-in-out shadow-lg lg:shadow-none select-none">
    <div class="reseller-sidebar-brand h-14 px-3.5 border-b border-slate-200 flex items-center justify-between bg-slate-50/80">
        <a href="{{ route('reseller.dashboard') }}" title="{{ $resellerName }}" class="flex items-center gap-2.5 min-w-0">
            <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-purple-600 to-indigo-600 text-white flex items-center justify-center font-bold text-sm shadow-xs flex-shrink-0">
                <i class="fas fa-handshake-angle text-xs"></i>
            </div>
            <span class="reseller-sidebar-label font-bold text-slate-900 text-xs truncate">{{ $resellerName }}</span>
        </a>
        <button type="button" @click="mobileSidebar = false" aria-label="{{ __('Close') }}" class="lg:hidden p-1 text-slate-400 hover:text-slate-600 rounded-md">
            <i class="fas fa-times text-xs"></i>
        </button>
    </div>

    <div class="reseller-sidebar-search px-3 pt-2.5 pb-1">
        <div class="relative">
            <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-[10px]"></i>
            <input type="search"
                   autocomplete="off"
                   x-model="filterQuery"
                   placeholder="{{ __('Quick find menu...') }}"
                   class="w-full pl-7 pr-6 py-1 rounded-lg border border-slate-200 bg-slate-50/80 text-[11px] text-slate-700 focus:bg-white focus:outline-none focus:ring-1 focus:ring-purple-500 transition">
            <button type="button" x-show="filterQuery" @click="filterQuery = ''" class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400">
                <i class="fas fa-times-circle text-[10px]"></i>
            </button>
        </div>
    </div>

    <div class="flex-1 overflow-y-auto px-2.5 py-2 space-y-1 text-xs">
        @foreach($sidebarMenus as $item)
            @if($item['type'] === 'link')
                <a href="{{ $item['url'] }}" title="{{ __($item['title']) }}"
                   x-show='!filterQuery || {!! json_encode($item["keywords"], JSON_HEX_APOS) !!}.includes(filterQuery.toLowerCase())'
                   class="reseller-sidebar-item flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg border transition text-[11.5px] font-semibold shadow-2xs {{ $item['is_active'] ? $item['active_cls'] : 'border-slate-200/90 bg-white text-slate-700 hover:bg-slate-50' }}">
                    <i class="{{ $item['icon'] }} w-4 text-center text-xs {{ $item['is_active'] ? $item['icon_active'] : 'text-slate-500' }}"></i>
                    <span class="reseller-sidebar-label">{{ __($item['title']) }}</span>
                </a>
            @else
                <div x-data="{ open: {{ $item['is_active'] ? 'true' : 'false' }} }"
                     x-show='!filterQuery || {!! json_encode($item["keywords"], JSON_HEX_APOS) !!}.includes(filterQuery.toLowerCase())'
                     class="space-y-1">
                    <button type="button"
                            @click="if (sidebarCollapsed && isDesktop()) { toggleSidebar(); open = true; } else { open = !open; }"
                            :aria-expanded="open || Boolean(filterQuery)"
                            title="{{ __($item['title']) }}"
                            class="reseller-sidebar-item w-full flex items-center justify-between px-2.5 py-1.5 rounded-lg border transition text-[11.5px] font-semibold shadow-2xs {{ $item['is_active'] ? $item['active_cls'] : 'border-slate-200/90 bg-white text-slate-700 hover:bg-slate-50' }}">
                        <div class="flex items-center gap-2">
                            <i class="{{ $item['icon'] }} w-4 text-center text-xs {{ $item['is_active'] ? $item['icon_active'] : 'text-slate-500' }}"></i>
                            <span class="reseller-sidebar-label">{{ __($item['title']) }}</span>
                        </div>
                        <i class="reseller-sidebar-label fas fa-chevron-right text-[8.5px] text-slate-400 transition-transform" :class="open ? 'rotate-90' : ''"></i>
                    </button>
                    <div x-show="open || filterQuery" x-collapse class="reseller-sidebar-submenu pl-2.5 pr-0.5 space-y-1">
                        @foreach($item['children'] as $child)
                            <a href="{{ $child['url'] }}"
                               x-show='!filterQuery || {!! json_encode($child["keywords"], JSON_HEX_APOS) !!}.includes(filterQuery.toLowerCase())'
                               class="block px-2.5 py-1.5 rounded-lg border transition text-[11px] shadow-2xs {{ $child['is_active'] ? $child['active_cls'] : 'border-slate-200/80 bg-white text-slate-700 hover:bg-slate-50 font-medium' }}">
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

    @if($authUser && $authUser->isResellerAdmin())
        <div class="reseller-sidebar-balance p-2.5 border-t border-slate-100 bg-slate-50/70">
            <a href="{{ route('reseller.recharge.index') }}" class="block px-2.5 py-2 rounded-lg bg-emerald-50 border border-emerald-100">
                <div class="flex items-center justify-between">
                    <span class="text-[9.5px] font-medium text-slate-500">{{ __('Available Balance') }}</span>
                    <i class="fas fa-wallet text-emerald-600 text-[10px]"></i>
                </div>
                <span class="text-[13px] font-bold font-mono text-emerald-700">@currency($availBal)</span>
            </a>
        </div>
    @endif
    <div class="hidden lg:block p-2.5 border-t border-slate-200">
        <button type="button" @click="toggleSidebar()" aria-controls="reseller-sidebar" :aria-expanded="!sidebarCollapsed"
                :title="sidebarCollapsed ? @js(__('Expand Sidebar (Ctrl+B)')) : @js(__('Collapse Sidebar (Ctrl+B)'))"
                :aria-label="sidebarCollapsed ? @js(__('Expand Sidebar (Ctrl+B)')) : @js(__('Collapse Sidebar (Ctrl+B)'))"
                class="reseller-sidebar-toggle w-full flex items-center justify-between gap-2 px-2.5 py-1.5 rounded-lg border border-slate-200 text-slate-600 hover:bg-cyan-50 hover:text-cyan-700">
            <span class="flex items-center gap-2">
                <i class="fas text-xs" :class="sidebarCollapsed ? 'fa-angles-right text-cyan-600' : 'fa-angles-left'" aria-hidden="true"></i>
                <span class="reseller-sidebar-label text-[11px] font-medium">{{ __('Collapse') }}</span>
            </span>
            <kbd class="reseller-sidebar-shortcut text-[9px] font-mono bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200">Ctrl+B</kbd>
        </button>
    </div>
</aside>
