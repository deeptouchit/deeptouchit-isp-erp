@php
    $authUser = auth()->user();
    $currentReseller = $reseller ?? ($authUser?->reseller ?: \App\Models\TenantReseller::find($authUser?->reseller_id));
    $resellerName = $currentReseller->name ?? __('Reseller Partner');
    $walletBal = (float) ($currentReseller->wallet_balance ?? 0);
    $availBal = (float) ($currentReseller->total_available_balance ?? $walletBal);
    $sidebarMenus = \App\Services\SidebarMenuService::resolveForUser($authUser);
@endphp

<div x-show="mobileSidebar"
     x-transition
     @click="mobileSidebar = false"
     class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-40 lg:hidden"
     style="display:none"
     x-cloak></div>

<aside :class="mobileSidebar ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
       class="fixed inset-y-0 left-0 z-50 w-60 bg-white border-r border-slate-200 flex flex-col transition-transform duration-200 ease-in-out shadow-lg lg:shadow-none select-none">
    <div class="h-14 px-3.5 border-b border-slate-200 flex items-center justify-between bg-slate-50/80">
        <a href="{{ route('reseller.dashboard') }}" class="flex items-center gap-2.5 min-w-0">
            <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-purple-600 to-indigo-600 text-white flex items-center justify-center font-bold text-sm shadow-xs flex-shrink-0">
                <i class="fas fa-handshake-angle text-xs"></i>
            </div>
            <span class="font-bold text-slate-900 text-xs truncate">{{ $resellerName }}</span>
        </a>
        <button type="button" @click="mobileSidebar = false" class="lg:hidden p-1 text-slate-400 hover:text-slate-600 rounded-md">
            <i class="fas fa-times text-xs"></i>
        </button>
    </div>

    <div class="px-3 pt-2.5 pb-1">
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
                <a href="{{ $item['url'] }}"
                   x-show='!filterQuery || {!! json_encode($item["keywords"], JSON_HEX_APOS) !!}.includes(filterQuery.toLowerCase())'
                   class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg border transition text-[11.5px] font-semibold shadow-2xs {{ $item['is_active'] ? $item['active_cls'] : 'border-slate-200/90 bg-white text-slate-700 hover:bg-slate-50' }}">
                    <i class="{{ $item['icon'] }} w-4 text-center text-xs {{ $item['is_active'] ? $item['icon_active'] : 'text-slate-500' }}"></i>
                    <span>{{ __($item['title']) }}</span>
                </a>
            @else
                <div x-data="{ open: {{ $item['is_active'] ? 'true' : 'false' }} }"
                     x-show='!filterQuery || {!! json_encode($item["keywords"], JSON_HEX_APOS) !!}.includes(filterQuery.toLowerCase())'
                     class="space-y-1">
                    <button type="button"
                            @click="open = !open"
                            class="w-full flex items-center justify-between px-2.5 py-1.5 rounded-lg border transition text-[11.5px] font-semibold shadow-2xs {{ $item['is_active'] ? $item['active_cls'] : 'border-slate-200/90 bg-white text-slate-700 hover:bg-slate-50' }}">
                        <div class="flex items-center gap-2">
                            <i class="{{ $item['icon'] }} w-4 text-center text-xs {{ $item['is_active'] ? $item['icon_active'] : 'text-slate-500' }}"></i>
                            <span>{{ __($item['title']) }}</span>
                        </div>
                        <i class="fas fa-chevron-right text-[8.5px] text-slate-400 transition-transform" :class="open ? 'rotate-90' : ''"></i>
                    </button>
                    <div x-show="open || filterQuery" x-collapse class="pl-2.5 pr-0.5 space-y-1">
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
        <div class="p-2.5 border-t border-slate-100 bg-slate-50/70">
            <a href="{{ route('reseller.recharge.index') }}" class="block px-2.5 py-2 rounded-lg bg-emerald-50 border border-emerald-100">
                <div class="flex items-center justify-between">
                    <span class="text-[9.5px] font-medium text-slate-500">{{ __('Available Balance') }}</span>
                    <i class="fas fa-wallet text-emerald-600 text-[10px]"></i>
                </div>
                <span class="text-[13px] font-bold font-mono text-emerald-700">@currency($availBal)</span>
            </a>
        </div>
    @endif
</aside>
