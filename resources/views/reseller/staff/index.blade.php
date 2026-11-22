@extends('reseller.layouts.app')

@section('title', __('Staff Directory') . ' - ' . ($tenant->company_name ?? $tenant->name ?? __('Reseller Portal')))

@push('styles')
    {{-- Page-specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" x-data="staffManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">
    
    {{-- 1. TOP HEADER BAR (AGENTS.md Rule 2.A: Icon + Title + Action buttons ONLY - Strictly No Subtitles) --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 bg-white p-3 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-sm shadow-2xs flex-shrink-0">
                <i class="fas fa-users-gear"></i>
            </div>
            <h1 class="text-sm font-bold text-slate-800 tracking-tight">{{ __('Staff Directory') }}</h1>
        </div>
        <div class="flex items-center gap-1.5 sm:gap-2 flex-wrap sm:flex-nowrap justify-start sm:justify-end">
            <a href="{{ route('reseller.staff.print', request()->query()) }}" target="_blank" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-print text-slate-500 text-xs"></i>
                <span>{{ __('Print Statement') }}</span>
            </a>
            <a href="{{ route('reseller.staff.export', request()->query()) }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-file-csv text-slate-500 text-xs"></i>
                <span>{{ __('Export CSV') }}</span>
            </a>
            <button type="button" @click="openCreateModal = true" class="px-3.5 py-1.5 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-user-plus text-xs"></i>
                <span>{{ __('Add Staff') }}</span>
            </button>
        </div>
    </div>

    {{-- 2. KPI SUMMARY STRIP (Strictly 6 Cards - AGENTS.md Rule 2.B) --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        
        {{-- Card 1: Total Staff --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Staff Directory') }}</span>
                <span class="text-[13px] font-bold font-mono text-cyan-800 leading-tight block truncate">
                    {{ number_format($stats['total_staff']) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-cyan-200 bg-cyan-50 text-cyan-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-users"></i>
            </div>
        </div>

        {{-- Card 2: Active Staff --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Active') }}</span>
                <span class="text-[13px] font-bold font-mono text-emerald-600 leading-tight block truncate">
                    {{ number_format($stats['active_staff']) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-emerald-200 bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-user-check"></i>
            </div>
        </div>

        {{-- Card 3: Bill Collectors --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Collected By') }}</span>
                <span class="text-[13px] font-bold font-mono text-purple-700 leading-tight block truncate">
                    {{ number_format($stats['collectors_count']) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-purple-200 bg-purple-50 text-purple-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-hand-holding-dollar"></i>
            </div>
        </div>

        {{-- Card 4: Field Technicians --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Support Center') }}</span>
                <span class="text-[13px] font-bold font-mono text-indigo-600 leading-tight block truncate">
                    {{ number_format($stats['technicians_count']) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-indigo-200 bg-indigo-50 text-indigo-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-screwdriver-wrench"></i>
            </div>
        </div>

        {{-- Card 5: Managed Clients --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Total Customers') }}</span>
                <span class="text-[13px] font-bold font-mono text-slate-900 leading-tight block truncate">
                    {{ number_format($stats['total_customers']) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-slate-200 bg-slate-50 text-slate-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-address-book"></i>
            </div>
        </div>

        {{-- Card 6: Total Collections Handled --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Total Received') }}</span>
                <span class="text-[13px] font-bold font-mono text-emerald-700 leading-tight block truncate">
                    @currency($stats['total_collected_amount'] ?? ($stats['monthly_collections'] ?? 0))
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-emerald-200 bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-coins"></i>
            </div>
        </div>

    </div>

    {{-- 3. SEARCH & MULTI-FILTER TOOLBAR (AGENTS.md Rule 2.C) --}}
    <div class="bg-white p-2.5 rounded-xl border border-slate-200/90 shadow-2xs space-y-2.5"
         x-data="{ showMoreFilters: @js($roleFilter !== 'all'), searchQuery: @js($search) }">
        <form method="GET" action="{{ route('reseller.staff.index') }}" class="space-y-2.5">
            <div class="flex flex-col md:flex-row items-stretch md:items-center gap-2 flex-wrap">
                <div class="relative flex-1 min-w-[200px]">
                    <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-xs pointer-events-none" :class="searchQuery ? 'text-cyan-600' : 'text-slate-400'"></i>
                    <input type="search" name="search" value="{{ $search }}" x-model="searchQuery" aria-label="{{ __('Search') }}"
                           placeholder="{{ __('Search staff name, ID, phone...') }}"
                           class="h-[34px] w-full pl-8 pr-7 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden">
                    <button type="button" x-show="searchQuery" x-cloak aria-label="{{ __('Clear Search') }}"
                            @click="searchQuery = ''; $nextTick(() => $el.closest('form').requestSubmit())"
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-rose-500">
                        <i class="fas fa-times-circle"></i>
                    </button>
                </div>
                <select name="status" aria-label="{{ __('Account Status') }}" onchange="this.form.requestSubmit()"
                        class="h-[34px] w-full md:w-32 px-2 rounded-lg border border-slate-200 bg-slate-50 text-xs">
                    @foreach(['all' => 'All Status', 'active' => 'Active', 'inactive' => 'Inactive'] as $value => $label)
                        <option value="{{ $value }}" @selected($statusFilter === $value)>{{ __($label) }}</option>
                    @endforeach
                </select>
                <select name="per_page" aria-label="{{ __('Rows per page') }}" onchange="this.form.requestSubmit()"
                        class="h-[34px] w-full md:w-28 px-2 rounded-lg border border-slate-200 bg-slate-50 text-xs">
                    @foreach($pageSizes as $size)
                        <option value="{{ $size }}" @selected($perPage === $size)>{{ __(':count / page', ['count' => $size]) }}</option>
                    @endforeach
                </select>
                <button type="button" @click="showMoreFilters = !showMoreFilters" :aria-expanded="showMoreFilters"
                        class="h-[34px] py-0 px-2.5 rounded-lg border border-slate-200 bg-slate-50 text-xs font-semibold flex items-center justify-center gap-1.5">
                    <i class="fas fa-sliders-h text-[10px]"></i><span>{{ __('More Filters') }}</span>
                    @if($roleFilter !== 'all')
                        <span class="px-1.5 rounded-full bg-cyan-600 text-white text-[9px]">1</span>
                    @endif
                    <i class="fas text-[9px]" :class="showMoreFilters ? 'fa-chevron-up text-cyan-600' : 'fa-chevron-down text-slate-400'"></i>
                </button>
                <div class="flex items-center gap-1.5 md:ml-auto">
                    <button type="submit" class="h-[34px] py-0 px-3 rounded-lg bg-cyan-600 hover:bg-cyan-700 text-white text-xs font-semibold flex items-center gap-1.5">
                        <i class="fas fa-filter text-[10px]"></i><span>{{ __('Filter') }}</span>
                    </button>
                    <a href="{{ route('reseller.staff.index') }}" class="h-[34px] py-0 px-2.5 rounded-lg border border-slate-200 bg-slate-100 hover:bg-slate-200 text-xs font-semibold flex items-center gap-1">
                        <i class="fas fa-rotate-left text-[10px]"></i><span>{{ __('Reset') }}</span>
                    </a>
                </div>
            </div>
            <div x-show="showMoreFilters" x-cloak x-transition class="pt-2 border-t border-slate-100 flex flex-wrap items-center gap-2">
                <select name="role" aria-label="{{ __('Staff Role') }}" onchange="this.form.requestSubmit()"
                        class="h-[34px] w-full sm:w-44 px-2 rounded-lg border border-slate-200 bg-slate-50 text-xs">
                    <option value="all">{{ __('All Roles') }}</option>
                    @foreach($roleLabels as $value => $label)
                        <option value="{{ $value }}" @selected($roleFilter === $value)>{{ __($label) }}{{ str_starts_with($value, 'reseller_') ? '' : ' (' . __('Legacy') . ')' }}</option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>

    <p x-show="resetMessage" x-text="resetMessage" x-cloak role="status"
       class="rounded-lg border border-emerald-200 bg-emerald-50 p-2.5 text-xs text-emerald-800"></p>

    {{-- 4. MASTER COMPACT TABLE (AGENTS.md Rule 2.D: Max 5-7 Minimal Columns) --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th scope="col" class="text-center font-semibold w-10 no-sort">#</th>
                        <th scope="col" class="text-center font-semibold">{{ __('Staff Member') }}</th>
                        <th scope="col" class="text-center font-semibold w-28 font-mono">{{ __('Staff ID') }}</th>
                        <th scope="col" class="text-center font-semibold w-40">{{ __('Role') }}</th>
                        <th scope="col" class="text-center font-semibold w-24">{{ __('Email') }}</th>
                        <th scope="col" class="text-center font-semibold w-32">{{ __('Mobile') }}</th>
                        <th scope="col" class="text-center font-semibold w-20">{{ __('Status') }}</th>
                        <th scope="col" class="text-center font-semibold w-10  no-sort">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($staffMembers as $index => $staff)
                        <tr>
                            <!-- 1. Index -->
                            <td class="text-center font-mono text-slate-500">
                                {{ ($staffMembers->currentPage() - 1) * $staffMembers->perPage() + $index + 1 }}
                            </td>

                            <!-- 2. Staff Member (Single Clean Field) -->
                            <td class="text-left font-semibold text-cyan-800">
                                {{ $staff->name }}
                            </td>

                            <!-- 3. Staff ID -->
                            <td class="text-center font-mono text-slate-700">
                                {{ $staff->staff_id ?: ('STF-' . $staff->id) }}
                            </td>

                            <!-- 4. Role & Designation -->
                            <td class="text-left text-slate-700">
                                {{ __($roleLabels[$staff->role] ?? $staff->role) }}
                            </td>

                            <td class="text-left text-slate-700">
                                <span class="inline-block max-w-[16rem] truncate align-middle" title="{{ $staff->email }}">{{ $staff->email ?: __('N/A') }}</span>
                            </td>

                            <td class="text-center text-slate-700">{{ $staff->phone ?: ($staff->mobile ?: __('N/A')) }}</td>

                            <!-- 7. Status -->
                            <td class="text-center">
                                @if($staff->status === 'active')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <i class="fas fa-circle text-[6px]"></i>
                                        <span>{{ __('Active') }}</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                        <i class="fas fa-pause text-[6px]"></i>
                                        <span>{{ __('Inactive') }}</span>
                                    </span>
                                @endif
                            </td>

                            <!-- 8. Action: Floating 3-Dot Button (AGENTS.md Rule 2.E) -->
                            <td class="text-center">
                                <button type="button" 
                                        @click.stop="toggleMenu({{ Js::from($staff->only([
                                            'id', 'name', 'staff_id', 'designation', 'role', 'phone', 'mobile', 'email',
                                            'assigned_customers_count', 'salary_amount', 'status', 'address', 'collection_commission_rate',
                                        ]) + [
                                            'canManage' => in_array($staff->id, $manageableIds, true),
                                            'roleLabel' => __($roleLabels[$staff->role] ?? $staff->role),
                                            'resetUrl' => in_array($staff->id, $manageableIds, true) ? route('reseller.staff.reset-password', $staff->id) : null,
                                        ]) }}, $event)"
                                        class="w-7 h-7 rounded-lg hover:bg-slate-100 text-slate-500 hover:text-slate-800 transition flex items-center justify-center mx-auto cursor-pointer">
                                    <i class="fas fa-ellipsis-v text-[10px]"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-8 text-slate-400 text-xs">
                                <i class="fas fa-users-gear text-3xl mb-2 block text-slate-300"></i>
                                <span>{{ __('No staff members found matching your search.') }}</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($staffMembers->hasPages())
            <div class="px-4 py-2.5 bg-white border-t border-slate-200 flex items-center justify-between text-xs">
                <div>
                    {{ __('Showing :first to :last of :total staff', ['first' => $staffMembers->firstItem(), 'last' => $staffMembers->lastItem(), 'total' => $staffMembers->total()]) }}
                </div>
                <div>
                    {{ $staffMembers->links() }}
                </div>
            </div>
        @endif
    </div>

    {{-- 5. FLOATING 3-DOT ACTION MENU (AGENTS.md Rule 2.E) --}}
    <div x-show="activeMenu !== null" 
         x-cloak 
         @click.outside="activeMenu = null"
         :style="menuPos"
         class="fixed z-50 w-48 bg-white rounded-xl border border-slate-200 shadow-xl py-1 text-xs text-slate-700 font-medium space-y-0.5"
         style="display: none;">
        
        <button type="button" 
                @click="viewDetails(activeMenu); activeMenu = null" 
                class="w-full px-3 py-1.5 text-left hover:bg-slate-50 hover:text-cyan-700 flex items-center gap-2 cursor-pointer transition">
            <i class="fas fa-id-card text-cyan-600 w-4"></i>
            <span>{{ __('View Profile') }}</span>
        </button>
        <button type="button" 
                x-show="activeMenu?.canManage"
                @click="openEdit(activeMenu); activeMenu = null" 
                class="w-full px-3 py-1.5 text-left hover:bg-slate-50 hover:text-blue-600 flex items-center gap-2 cursor-pointer transition">
            <i class="fas fa-user-pen text-blue-600 w-4"></i>
            <span>{{ __('Edit Details') }}</span>
        </button>
        <button type="button" x-show="activeMenu?.resetUrl" @click="openPasswordReset(activeMenu)"
                class="w-full px-3 py-1.5 text-left hover:bg-slate-50 hover:text-cyan-700 flex items-center gap-2">
            <i class="fas fa-key text-cyan-600 w-4" aria-hidden="true"></i>
            <span>{{ __('Reset Password') }}</span>
        </button>
        <button type="button" 
                x-show="activeMenu?.canManage"
                @click="toggleStatusAction(activeMenu?.id); activeMenu = null" 
                class="w-full px-3 py-1.5 text-left hover:bg-slate-50 hover:text-amber-600 flex items-center gap-2 cursor-pointer transition">
            <i class="fas fa-power-off text-amber-600 w-4"></i>
            <span x-text="activeMenu?.status === 'active' ? @js(__('Disable Account')) : @js(__('Enable Account'))">{{ __('Toggle Status') }}</span>
        </button>
        <button type="button" 
                x-show="activeMenu?.canManage"
                @click="deleteStaffAction(activeMenu?.id, activeMenu?.name); activeMenu = null" 
                class="w-full px-3 py-1.5 text-left text-rose-600 hover:bg-rose-50 flex items-center gap-2 cursor-pointer transition border-t border-slate-100">
            <i class="fas fa-trash-alt text-rose-600 w-4"></i>
            <span>{{ __('Delete Staff') }}</span>
        </button>
    </div>

    <div x-show="resetStaff" x-cloak @keydown.escape.window="if (!resetBusy) resetStaff = null"
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-xs p-4">
        <div role="dialog" aria-modal="true" aria-labelledby="staff-reset-title" @click.outside="if (!resetBusy) resetStaff = null"
             class="w-full max-w-xl overflow-hidden rounded-xl border border-slate-200/90 bg-white shadow-2xl">
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 inline-flex items-center justify-center"><i class="fas fa-key" aria-hidden="true"></i></span>
                    <div>
                        <h3 id="staff-reset-title" class="text-xs font-semibold text-slate-800">{{ __('Reset Password') }}</h3>
                        <p class="text-[10.5px] text-slate-500" x-text="resetStaff?.name"></p>
                    </div>
                </div>
                <button type="button" @click="resetStaff = null" :disabled="resetBusy" aria-label="{{ __('Close') }}" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-500">
                    <i class="fas fa-times" aria-hidden="true"></i>
                </button>
            </div>
            <form x-ref="resetForm" @submit.prevent="submitPasswordReset($event.currentTarget)">
                @csrf
                <div class="p-4 space-y-3">
                    <p x-show="resetError" x-text="resetError" role="alert" class="text-xs text-rose-700 break-words"></p>
                    <label class="block text-xs font-medium text-slate-700">
                        {{ __('Your Current Password') }}
                        <input type="password" name="current_password" autocomplete="current-password" required
                               class="mt-1 block w-full rounded-lg bg-slate-50 border border-slate-200 px-3 py-1.5 text-xs focus:bg-white focus:border-cyan-500">
                    </label>
                    <label class="block text-xs font-medium text-slate-700">
                        {{ __('New Password') }}
                        <input type="password" name="password" autocomplete="new-password" minlength="8" maxlength="72" required
                               class="mt-1 block w-full rounded-lg bg-slate-50 border border-slate-200 px-3 py-1.5 text-xs focus:bg-white focus:border-cyan-500">
                    </label>
                    <label class="block text-xs font-medium text-slate-700">
                        {{ __('Confirm New Password') }}
                        <input type="password" name="password_confirmation" autocomplete="new-password" minlength="8" maxlength="72" required
                               class="mt-1 block w-full rounded-lg bg-slate-50 border border-slate-200 px-3 py-1.5 text-xs focus:bg-white focus:border-cyan-500">
                    </label>
                </div>
                <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" @click="resetStaff = null" :disabled="resetBusy" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg">{{ __('Cancel') }}</button>
                    <button type="submit" :disabled="resetBusy" class="bg-cyan-600 hover:bg-cyan-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs disabled:opacity-50">{{ __('Reset Password') }}</button>
                </div>
            </form>
        </div>
    </div>

    {{-- 6. ADD STAFF MEMBER MODAL (AGENTS.md Rule 3: Natural Soft Modal) --}}
    <div x-show="openCreateModal" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50 overflow-y-auto"
         style="display: none;"
         x-cloak>
        
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden my-6" 
             @click.outside="openCreateModal = false">
            
            <!-- Soft Header -->
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">{{ __('Add Staff Member') }}</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">{{ __('Create staff, field technician or collector login account') }}</p>
                    </div>
                </div>
                <button type="button" @click="openCreateModal = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <!-- Body Form -->
            <form action="{{ route('reseller.staff.store') }}" method="POST">
                @csrf
                <div class="p-4 space-y-3">
                    <!-- Full Name & Phone -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="create_name" class="block text-[11px] font-semibold text-slate-700 mb-1">
                                {{ __('Full Name') }} <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" 
                                   id="create_name" 
                                   name="name" 
                                   required 
                                   placeholder="{{ __('e.g. Md. Shakil Ahmed') }}"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 shadow-2xs">
                        </div>
                        <div>
                            <label for="create_phone" class="block text-[11px] font-semibold text-slate-700 mb-1">
                                {{ __('Mobile / Phone') }} <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" 
                                   id="create_phone" 
                                   name="phone" 
                                   required 
                                   placeholder="01819-XXXXXX" 
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 shadow-2xs">
                        </div>
                    </div>

                    <!-- Email & Password -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="create_email" class="block text-[11px] font-semibold text-slate-700 mb-1">
                                {{ __('Email Address') }} <span class="text-rose-500">*</span>
                            </label>
                            <input type="email" 
                                   id="create_email" 
                                   name="email" 
                                   required 
                                   placeholder="staff@partner.com" 
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 shadow-2xs">
                        </div>
                        <div>
                            <label for="create_password" class="block text-[11px] font-semibold text-slate-700 mb-1">
                                {{ __('Account Password') }} <span class="text-rose-500">*</span>
                            </label>
                            <input type="password" 
                                   id="create_password" 
                                   name="password" 
                                   required 
                                   minlength="6" 
                                   placeholder="{{ __('Min 6 characters') }}"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 shadow-2xs">
                        </div>
                    </div>

                    <!-- Role & Designation -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="create_role" class="block text-[11px] font-semibold text-slate-700 mb-1">
                                {{ __('Staff Role') }} <span class="text-rose-500">*</span>
                            </label>
                            <select id="create_role" name="role" required class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 shadow-2xs">
                                @foreach($assignableRoles as $value => $label)
                                    <option value="{{ $value }}">{{ __($label) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="create_designation" class="block text-[11px] font-semibold text-slate-700 mb-1">
                                {{ __('Designation Title') }}
                            </label>
                            <input type="text" 
                                   id="create_designation" 
                                   name="designation" 
                                   placeholder="{{ __('e.g. Senior Area Collector') }}"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 shadow-2xs">
                        </div>
                    </div>

                    <!-- Salary & Commission Rate -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="create_salary" class="block text-[11px] font-semibold text-slate-700 mb-1">
                                {{ __('Monthly Salary') }} ({{ $currencySymbol ?? '' }})
                            </label>
                            <input type="number" 
                                   id="create_salary" 
                                   name="salary_amount" 
                                   step="100" 
                                   min="0" 
                                   value="0" 
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 shadow-2xs">
                        </div>
                        <div>
                            <label for="create_comm" class="block text-[11px] font-semibold text-slate-700 mb-1">
                                {{ __('Collection Commission (%)') }}
                            </label>
                            <input type="number" 
                                   id="create_comm" 
                                   name="collection_commission_rate" 
                                   step="0.5" 
                                   min="0" 
                                   max="100" 
                                   value="0" 
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 shadow-2xs">
                        </div>
                    </div>

                    <!-- Address -->
                    <div>
                        <label for="create_address" class="block text-[11px] font-semibold text-slate-700 mb-1">
                            {{ __('Address & Territory Assignment') }}
                        </label>
                        <input type="text" 
                               id="create_address" 
                               name="address" 
                               placeholder="{{ __('e.g. Halishahar Area, Block B') }}"
                               class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 shadow-2xs">
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" @click="openCreateModal = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                        {{ __('Cancel') }}
                    </button>
                    <button type="submit" class="bg-cyan-600 hover:bg-cyan-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition cursor-pointer">
                        {{ __('Save Staff Member') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- 7. EDIT STAFF MEMBER MODAL --}}
    <div x-show="editStaffData" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50 overflow-y-auto"
         style="display: none;"
         x-cloak>
        
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden my-6" 
             @click.outside="editStaffData = null">
            
            <!-- Soft Header -->
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-user-pen"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">{{ __('Edit Staff Member') }}</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal" x-text="editStaffData?.name || 'Update details'"></p>
                    </div>
                </div>
                <button type="button" @click="editStaffData = null" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <!-- Body Form -->
            <form :action="`/reseller/staff/${editStaffData?.id}`" method="POST">
                @csrf
                @method('PUT')
                <div class="p-4 space-y-3">
                    <!-- Full Name & Phone -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">{{ __('Full Name') }} <span class="text-rose-500">*</span></label>
                            <input type="text" name="name" :value="editStaffData?.name" required class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 shadow-2xs">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">{{ __('Mobile / Phone') }} <span class="text-rose-500">*</span></label>
                            <input type="text" name="phone" :value="editStaffData?.phone || editStaffData?.mobile" required class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 shadow-2xs">
                        </div>
                    </div>

                    <!-- Email -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">{{ __('Email Address') }} <span class="text-rose-500">*</span></label>
                            <input type="email" name="email" :value="editStaffData?.email" required class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 shadow-2xs">
                        </div>
                    </div>

                    <!-- Role & Designation -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">{{ __('Staff Role') }} <span class="text-rose-500">*</span></label>
                            <select name="role" x-ref="editRole" :value="editStaffData?.role" required class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 shadow-2xs">
                                <template x-for="role in editStaffData?.legacyRoles ?? []" :key="role.value">
                                    <option :value="role.value" x-text="role.label"></option>
                                </template>
                                @foreach($assignableRoles as $value => $label)
                                    <option value="{{ $value }}">{{ __($label) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">{{ __('Designation Title') }}</label>
                            <input type="text" name="designation" :value="editStaffData?.designation" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 shadow-2xs">
                        </div>
                    </div>

                    <!-- Salary & Commission -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">{{ __('Monthly Salary') }} ({{ $currencySymbol ?? '' }})</label>
                            <input type="number" name="salary_amount" :value="editStaffData?.salary_amount || 0" step="100" min="0" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 shadow-2xs">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">{{ __('Collection Commission (%)') }}</label>
                            <input type="number" name="collection_commission_rate" :value="editStaffData?.collection_commission_rate || 0" step="0.5" min="0" max="100" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 shadow-2xs">
                        </div>
                    </div>

                    <!-- Address & Status -->
                    <div class="grid grid-cols-3 gap-3">
                        <div class="col-span-2">
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">{{ __('Address & Territory Assignment') }}</label>
                            <input type="text" name="address" :value="editStaffData?.address" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 shadow-2xs">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">{{ __('Account Status') }}</label>
                            <select name="status" :value="editStaffData?.status" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 shadow-2xs">
                                <option value="active">{{ __('Active') }}</option>
                                <option value="inactive">{{ __('Inactive') }}</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" @click="editStaffData = null" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                        {{ __('Cancel') }}
                    </button>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition cursor-pointer">
                        {{ __('Save Changes') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- 8. STAFF PROFILE DETAILS MODAL --}}
    <div x-show="selectedStaff" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50 overflow-y-auto"
         style="display: none;"
         x-cloak>
        
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden my-6" 
             @click.outside="selectedStaff = null">
            
            <!-- Soft Header -->
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-id-card"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800" x-text="selectedStaff?.name || @js(__('Staff Profile'))"></h3>
                        <p class="text-[10.5px] text-slate-500 font-normal" x-text="'ID: ' + (selectedStaff?.staff_id || ('STF-' + selectedStaff?.id))"></p>
                    </div>
                </div>
                <button type="button" @click="selectedStaff = null" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <!-- Body Details Grid -->
            <div class="p-4 space-y-3">
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[9.5px] font-medium text-slate-500 block uppercase">{{ __('Role / Position') }}</span>
                        <span class="font-bold text-slate-800 block" x-text="selectedStaff?.designation || selectedStaff?.roleLabel"></span>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[9.5px] font-medium text-slate-500 block uppercase">{{ __('Phone Contact') }}</span>
                        <span class="font-mono font-bold text-slate-800 block" x-text="selectedStaff?.phone || selectedStaff?.mobile || '—'"></span>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[9.5px] font-medium text-slate-500 block uppercase">{{ __('Email Address') }}</span>
                        <span class="font-mono font-bold text-slate-800 block truncate" x-text="selectedStaff?.email || '—'"></span>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[9.5px] font-medium text-slate-500 block uppercase">{{ __('Assigned Clients') }}</span>
                        <span class="font-mono font-bold text-cyan-800 block" x-text="selectedStaff?.assigned_customers_count || 0"></span>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[9.5px] font-medium text-slate-500 block uppercase">{{ __('Monthly Salary') }}</span>
                        <span class="font-mono font-bold text-emerald-700 block" x-text="formatSalary(selectedStaff?.salary_amount)"></span>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[9.5px] font-medium text-slate-500 block uppercase">{{ __('Account Status') }}</span>
                        <span class="font-mono font-bold block" :class="selectedStaff?.status === 'active' ? 'text-emerald-600' : 'text-slate-600'" x-text="selectedStaff?.status === 'active' ? @js(__('Active')) : @js(__('Inactive'))"></span>
                    </div>
                </div>

                <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80 text-xs">
                    <span class="text-[9.5px] font-medium text-slate-500 block uppercase">{{ __('Address & Territory') }}</span>
                    <span class="font-semibold text-slate-700 block mt-0.5" x-text="selectedStaff?.address || @js(__('Standard Partner Territory'))"></span>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end">
                <button type="button" @click="selectedStaff = null" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                    {{ __('Close') }}
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function staffManager() {
    return {
        openCreateModal: false,
        editStaffData: null,
        selectedStaff: null,
        activeMenu: null,
        resetStaff: null,
        resetBusy: false,
        resetError: '',
        resetMessage: '',
        menuPos: {},

        toggleMenu(item, event) {
            if (this.activeMenu?.id === item.id) {
                this.activeMenu = null;
                return;
            }
            this.activeMenu = item;
            const rect = event.currentTarget.getBoundingClientRect();
            const dropdownHeight = 200;
            const right = Math.max(10, window.innerWidth - rect.right);
            let top = Math.round(rect.bottom) + 2;
            let bottom = 'auto';

            if (top + dropdownHeight > window.innerHeight) {
                top = 'auto';
                bottom = Math.max(10, window.innerHeight - Math.round(rect.top) + 2) + 'px';
            } else {
                top = `${top}px`;
            }

            this.menuPos = {
                top: top,
                bottom: bottom,
                right: `${right}px`,
                left: 'auto'
            };
        },

        formatSalary(value) {
            const [integer, decimal] = Number(value ?? 0).toFixed(@js($currencyDecimals ?? 2)).split('.');
            const grouped = integer.replace(/\B(?=(\d{3})+(?!\d))/g, () => @js($thousandSeparator ?? ','));
            const amount = decimal === undefined ? grouped : grouped + @js($decimalSeparator ?? '.') + decimal;
            const symbol = @js($currencySymbol ?? '');
            return @js($currencyPosition ?? 'left') === 'right' ? amount + ' ' + symbol : symbol + ' ' + amount;
        },

        viewDetails(staff) {
            this.selectedStaff = staff;
        },

        openEdit(staff) {
            const legacyRoles = @js(array_keys($assignableRoles)).includes(staff.role) ? [] : [{ value: staff.role, label: staff.roleLabel }];
            this.editStaffData = { ...staff, legacyRoles, phone: staff.phone || staff.mobile };
            this.$nextTick(() => { this.$refs.editRole.value = staff.role; });
        },

        openPasswordReset(staff) {
            this.activeMenu = null;
            this.resetStaff = staff;
            this.resetError = '';
            this.resetMessage = '';
            this.$refs.resetForm.reset();
            this.$nextTick(() => this.$refs.resetForm.elements.current_password.focus());
        },

        async submitPasswordReset(form) {
            if (this.resetBusy || !this.resetStaff?.resetUrl) return;
            this.resetBusy = true;
            this.resetError = '';
            try {
                const response = await fetch(this.resetStaff.resetUrl, {
                    method: 'POST',
                    headers: { Accept: 'application/json' },
                    body: new FormData(form),
                });
                const data = await response.json();
                if (!response.ok) {
                    this.resetError = response.status === 422
                        ? Object.values(data.errors ?? {}).flat().join(' ') || @js(__('Unable to reset the staff password.'))
                        : @js(__('Unable to reset the staff password.'));
                    return;
                }

                form.reset();
                this.resetStaff = null;
                this.resetMessage = data.message;
            } catch {
                this.resetError = @js(__('Unable to reset the staff password.'));
            } finally {
                this.resetBusy = false;
            }
        },

        async toggleStatusAction(id) {
            if (!id) return;
            try {
                const response = await fetch(`/reseller/staff/${id}/toggle-status`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();
                if (data.success) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: @js(__('Status Updated')),
                            text: data.message,
                            timer: 1200,
                            showConfirmButton: false,
                            toast: true,
                            position: 'top-end'
                        });
                    }
                    setTimeout(() => location.reload(), 600);
                } else {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({ icon: 'error', title: @js(__('Error')), text: data.message });
                    }
                }
            } catch (e) {
                console.error('Failed to toggle status:', e);
                location.reload();
            }
        },

        async deleteStaffAction(id, name) {
            if (!id) return;

            if (typeof Swal !== 'undefined') {
                const result = await Swal.fire({
                    title: @js(__('Delete Staff Member?')),
                    text: @js(__('Remove :name from the staff directory?')).replace(':name', () => name),
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: @js(__('Yes, Delete Staff')),
                    cancelButtonText: @js(__('Cancel')),
                    customClass: {
                        confirmButton: 'bg-rose-600 hover:bg-rose-700 text-white font-semibold text-xs px-4 py-2 rounded-lg mr-2 cursor-pointer',
                        cancelButton: 'bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs px-4 py-2 rounded-lg border cursor-pointer'
                    },
                    buttonsStyling: false
                });
                if (!result.isConfirmed) return;
            } else {
                if (!confirm(@js(__('Remove :name from the staff directory?')).replace(':name', () => name))) return;
            }

            try {
                const response = await fetch(`/reseller/staff/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();
                if (data.success) {
                    if (typeof Swal !== 'undefined') {
                        await Swal.fire({
                            icon: 'success',
                            title: @js(__('Deleted')),
                            text: data.message,
                            timer: 1200,
                            showConfirmButton: false,
                            toast: true,
                            position: 'top-end'
                        });
                    }
                    location.reload();
                } else {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({ icon: 'error', title: @js(__('Error')), text: data.message });
                    }
                }
            } catch (e) {
                console.error('Delete failed:', e);
                location.reload();
            }
        }
    };
}
</script>
@endpush
