@extends('tenant.layouts.app')

@section('title', 'New Installation Tasks & Provisioning - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
{{-- Page specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" x-data="installationPageManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">
    
    {{-- 1. TOP HEADER BAR (Strictly Title + Icon + Action Buttons ONLY, No subtitle) --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 bg-white px-3.5 py-2.5 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <div class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center text-xs flex-shrink-0">
                <i class="fas fa-tower-broadcast"></i>
            </div>
            <h1 class="text-sm font-bold text-slate-800 tracking-tight">New Installation Tasks &amp; Provisioning Pipeline</h1>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            {{-- Export CSV Stream Button --}}
            <a href="{{ route('tenant.tickets.installations.export', request()->all()) }}" 
               class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-xs px-3.5 py-1.5 rounded-lg shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-file-csv text-[11px]"></i>
                <span>Export CSV</span>
            </a>

            {{-- New Order Primary Button --}}
            <button type="button" 
                    @click="newOrderModalOpen = true" 
                    class="bg-blue-600 hover:bg-blue-700 text-white font-medium text-xs px-3.5 py-1.5 rounded-lg shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-plus text-[11px]"></i>
                <span>New Installation Order</span>
            </button>
        </div>
    </div>

    {{-- 2. KPI SUMMARY STRIP (Strictly 6 Cards) --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        {{-- Card 1: Total Installation Orders --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Total Orders</span>
                <span class="text-[13px] font-bold font-mono text-slate-800 leading-tight block">
                    {{ number_format($stats['total']) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-slate-200 bg-slate-50 text-slate-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-list-check"></i>
            </div>
        </div>

        {{-- Card 2: Feasibility & Port Check --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">1. Feasibility</span>
                <span class="text-[13px] font-bold font-mono text-amber-700 leading-tight block">
                    {{ number_format($stats['feasibility']) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-amber-200 bg-amber-50 text-amber-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-magnifying-glass-location"></i>
            </div>
        </div>

        {{-- Card 3: Drop Cable Pulling --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">2. Cable Pulling</span>
                <span class="text-[13px] font-bold font-mono text-cyan-700 leading-tight block">
                    {{ number_format($stats['cable_pulling']) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-cyan-200 bg-cyan-50 text-cyan-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-route"></i>
            </div>
        </div>

        {{-- Card 4: Splicing & Power Test --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">3. Splicing Test</span>
                <span class="text-[13px] font-bold font-mono text-indigo-700 leading-tight block">
                    {{ number_format($stats['splicing']) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-indigo-200 bg-indigo-50 text-indigo-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-bolt"></i>
            </div>
        </div>

        {{-- Card 5: PPPoE / Router Setup --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">4. Router Setup</span>
                <span class="text-[13px] font-bold font-mono text-purple-700 leading-tight block">
                    {{ number_format($stats['binding']) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-purple-200 bg-purple-50 text-purple-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-network-wired"></i>
            </div>
        </div>

        {{-- Card 6: Activated & Live --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">5. Activated Live</span>
                <span class="text-[13px] font-bold font-mono text-emerald-700 leading-tight block">
                    {{ number_format($stats['completed']) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-emerald-200 bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-circle-check"></i>
            </div>
        </div>
    </div>

    {{-- 3. SEARCH & MULTI-FILTER TOOLBAR --}}
    <div class="bg-white p-3 rounded-xl border border-slate-200 shadow-xs">
        <form method="GET" action="{{ route('tenant.tickets.installations') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-2.5">
            {{-- Filter 1: Universal Search --}}
            <div class="lg:col-span-2">
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Search Order / Applicant / Address</label>
                <div class="relative">
                    <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search by task #, applicant, phone, address..." class="w-full pl-8 pr-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                </div>
            </div>

            {{-- Filter 2: Pipeline Stage --}}
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Pipeline Stage</label>
                <select name="stage" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                    <option value="all">All Pipeline Stages</option>
                    <option value="feasibility_check" {{ $stage === 'feasibility_check' ? 'selected' : '' }}>1. Feasibility Check</option>
                    <option value="cable_pulling" {{ $stage === 'cable_pulling' ? 'selected' : '' }}>2. Drop Cable Pulling</option>
                    <option value="splicing_power_test" {{ $stage === 'splicing_power_test' ? 'selected' : '' }}>3. Splicing &amp; Power</option>
                    <option value="mikrotik_binding" {{ $stage === 'mikrotik_binding' ? 'selected' : '' }}>4. PPPoE / Router Setup</option>
                    <option value="active_completed" {{ $stage === 'active_completed' ? 'selected' : '' }}>5. Activated &amp; Live</option>
                </select>
            </div>

            {{-- Filter 3: Coverage Zone --}}
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Coverage Area</label>
                <select name="zone_id" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                    <option value="all">All Coverage Areas</option>
                    @foreach($zones as $z)
                        <option value="{{ $z->id }}" {{ (string)$zoneId === (string)$z->id ? 'selected' : '' }}>{{ $z->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Filter 4: Assigned Technician --}}
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Field Technician</label>
                <select name="technician_id" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                    <option value="all">All Technicians</option>
                    @foreach($technicians as $tech)
                        <option value="{{ $tech->id }}" {{ (string)$technicianId === (string)$tech->id ? 'selected' : '' }}>{{ $tech->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Filter & Reset Buttons (Strict Universal Standard - AGENTS.md Rule 2.C) --}}
            <div class="flex items-end gap-1.5">
                <button type="submit" class="flex-1 py-1.5 px-3 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer" title="Apply Filters">
                    <i class="fas fa-filter text-[10px]"></i>
                    <span>Filter</span>
                </button>
                <a href="{{ route('tenant.tickets.installations') }}" class="py-1.5 px-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer" title="Reset Filters">
                    <i class="fas fa-rotate-left text-[10px]"></i>
                    <span>Reset</span>
                </a>
            </div>
        </form>
    </div>

    {{-- 4. MASTER COMPACT TABLE (<table class="saas-table"> Pure CSS System) --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-10 text-center">SL</th>
                        <th>Order #</th>
                        <th>Subscriber Name</th>
                        <th>Package Plan</th>
                        <th>Coverage Area</th>
                        <th>Assigned Technician</th>
                        <th class="text-center">Pipeline Stage</th>
                        <th class="text-right">Optical Signal</th>
                        <th class="text-right">Fee / Advance</th>
                        <th class="w-14 text-center no-sort">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tasks as $idx => $t)
                        <tr>
                            <td class="text-center text-slate-400 font-mono">{{ $tasks->firstItem() + $idx }}</td>
                            
                            {{-- Task Number (Single data) --}}
                            <td class="font-mono font-bold text-cyan-800">
                                <a href="javascript:void(0)" @click="showTaskDetails(@js($t))" class="hover:underline">
                                    {{ $t->task_number }}
                                </a>
                            </td>

                            {{-- Applicant Name (Single data) --}}
                            <td class="font-semibold text-slate-800">
                                {{ $t->applicant_name }}
                            </td>

                            {{-- Package Plan (Single data) --}}
                            <td class="text-slate-700 font-medium">
                                {{ $t->package?->name ?? 'Standard Package' }}
                            </td>

                            {{-- Coverage Area (Single data) --}}
                            <td class="text-slate-600 text-[11px]">
                                {{ $t->coverageZone?->name ?? 'Primary Zone' }}
                            </td>

                            {{-- Assigned Technician (Single data) --}}
                            <td class="text-slate-700">
                                @if($t->assigned_technician_name)
                                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-700">
                                        <i class="fas fa-user-gear text-indigo-500 text-[10px]"></i>
                                        <span>{{ $t->assigned_technician_name }}</span>
                                    </span>
                                @else
                                    <span class="text-slate-400 text-xs font-mono">Unassigned</span>
                                @endif
                            </td>

                            {{-- Pipeline Stage Badge --}}
                            <td class="text-center">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $t->stage_badge_color }}">
                                    <span>{{ $t->stage_name }}</span>
                                </span>
                            </td>

                            {{-- Optical Signal RX --}}
                            <td class="text-right font-mono font-bold {{ $t->optical_rx_power ? 'text-emerald-700' : 'text-slate-400' }}">
                                {{ $t->optical_rx_power ?: 'Pending' }}
                            </td>

                            {{-- Fee / Advance (@currency) --}}
                            <td class="text-right font-mono text-xs">
                                <span class="text-slate-800 font-bold">@currency($t->connection_fee)</span>
                                @if($t->advance_payment > 0)
                                    <span class="text-[10px] text-emerald-600 block">(Adv: @currency($t->advance_payment))</span>
                                @endif
                            </td>

                            {{-- 3-Dot Floating Action Menu Trigger --}}
                            <td class="text-center">
                                <button type="button" 
                                        @click="toggleMenu(@js($t), $event)"
                                        class="p-1 rounded-md text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition cursor-pointer"
                                        title="Actions">
                                    <i class="fas fa-ellipsis-v text-[10px]"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-8 text-slate-400">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 text-sm">
                                        <i class="fas fa-tower-broadcast"></i>
                                    </div>
                                    <span class="text-xs font-medium text-slate-500">No installation tasks found matching your filters.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($tasks->hasPages())
        <div class="px-3.5 py-2 border-t border-slate-200 bg-slate-50/50 flex items-center justify-between">
            <span class="text-[11px] text-slate-500">
                Showing {{ $tasks->firstItem() }} to {{ $tasks->lastItem() }} of {{ $tasks->total() }} Installation Tasks
            </span>
            <div>
                {{ $tasks->links() }}
            </div>
        </div>
        @endif
    </div>

    {{-- 5. FLOATING 3-DOT ACTION MENU (AGENTS.md Rule 2.E) --}}
    <div x-show="activeMenu" 
         x-cloak 
         @click.away="activeMenu = null"
         class="fixed z-50 w-56 bg-white rounded-xl shadow-xl border border-slate-200 py-1.5 text-xs divide-y divide-slate-100"
         :style="`top: ${menuPos.top}; bottom: ${menuPos.bottom}; right: ${menuPos.right}; left: ${menuPos.left};`">
        
        {{-- Group 1: Pipeline Progression --}}
        <div class="py-1">
            <button type="button" @click="openAdvanceModal(activeMenu); activeMenu = null" class="w-full flex items-center gap-2 px-3 py-1.5 text-slate-700 hover:bg-slate-50 hover:text-blue-600 font-medium transition cursor-pointer">
                <i class="fas fa-forward text-blue-600 w-4 text-center text-xs"></i>
                <span>Advance Pipeline Stage</span>
            </button>
            <button type="button" @click="showTaskDetails(activeMenu); activeMenu = null" class="w-full flex items-center gap-2 px-3 py-1.5 text-slate-700 hover:bg-slate-50 hover:text-cyan-600 font-medium transition cursor-pointer">
                <i class="fas fa-eye text-cyan-600 w-4 text-center text-xs"></i>
                <span>View Order Details</span>
            </button>
            <a :href="`/admin/support/installations/${activeMenu?.id}/print`" target="_blank" class="flex items-center gap-2 px-3 py-1.5 text-slate-700 hover:bg-slate-50 hover:text-indigo-600 font-medium transition">
                <i class="fas fa-print text-indigo-600 w-4 text-center text-xs"></i>
                <span>Print Activation Slip</span>
            </a>
            <button type="button" @click="copyTaskNumber(activeMenu?.task_number); activeMenu = null" class="w-full flex items-center gap-2 px-3 py-1.5 text-slate-700 hover:bg-slate-50 font-medium transition cursor-pointer">
                <i class="fas fa-copy text-slate-500 w-4 text-center text-xs"></i>
                <span>Copy Order #</span>
            </button>
        </div>
    </div>

    {{-- MODAL 1: NEW INSTALLATION ORDER MODAL --}}
    <div x-show="newOrderModalOpen" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
         @keydown.escape.window="newOrderModalOpen = false">
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden" 
             @click.away="newOrderModalOpen = false">
            <form method="POST" action="{{ route('tenant.tickets.installations.store') }}">
                @csrf
                {{-- Modal Header --}}
                <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center text-xs flex-shrink-0">
                            <i class="fas fa-tower-broadcast"></i>
                        </div>
                        <div>
                            <h4 class="text-xs font-semibold text-slate-800">New Physical Line Installation Order</h4>
                            <span class="text-[10.5px] text-slate-500 font-normal">Schedule drop cable pulling &amp; ONU setup</span>
                        </div>
                    </div>
                    <button type="button" @click="newOrderModalOpen = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>

                {{-- Modal Body --}}
                <div class="p-4 space-y-3 text-xs">
                    <div class="grid grid-cols-2 gap-2.5">
                        <div>
                            <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Subscriber Full Name *</label>
                            <input type="text" name="applicant_name" required placeholder="Dr. Shamsul Huda" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Contact Phone *</label>
                            <input type="text" name="applicant_phone" required placeholder="01715889900" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Installation Address *</label>
                        <input type="text" name="installation_address" required placeholder="House 14, Road 7, Sector 3, Uttara, Dhaka" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none">
                    </div>

                    <div class="grid grid-cols-2 gap-2.5">
                        <div>
                            <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Internet Package *</label>
                            <select name="package_id" required class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none">
                                @foreach($packages as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->download_speed }} Mbps - {{ $currencySymbol ?? '৳' }}{{ number_format($p->price) }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Coverage Area</label>
                            <select name="zone_id" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none">
                                @foreach($zones as $z)
                                    <option value="{{ $z->id }}">{{ $z->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2.5">
                        <div>
                            <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Assign Technician</label>
                            <select name="assigned_technician_id" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none">
                                <option value="">Select Technician</option>
                                @foreach($technicians as $tech)
                                    <option value="{{ $tech->id }}">{{ $tech->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Connection Fee ({{ $currencySymbol ?? '৳' }})</label>
                            <input type="number" step="0.01" name="connection_fee" value="1000.00" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Installation Notes &amp; Routing Remarks</label>
                        <textarea name="remarks" rows="2" placeholder="Pole location or optical port requirements..." class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none"></textarea>
                    </div>
                </div>

                {{-- Modal Footer --}}
                <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" @click="newOrderModalOpen = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs cursor-pointer">
                        Create Order
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL 2: ADVANCE PIPELINE STAGE MODAL --}}
    <div x-show="advanceModalOpen" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
         @keydown.escape.window="advanceModalOpen = false">
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden" 
             @click.away="advanceModalOpen = false">
            <form :action="`/admin/support/installations/${selectedTask?.id}/step`" method="POST">
                @csrf
                {{-- Modal Header --}}
                <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-xs flex-shrink-0">
                            <i class="fas fa-forward"></i>
                        </div>
                        <div>
                            <h4 class="text-xs font-semibold text-slate-800">Progress Installation Pipeline</h4>
                            <span class="text-[10.5px] text-slate-500 font-mono" x-text="selectedTask?.task_number"></span>
                        </div>
                    </div>
                    <button type="button" @click="advanceModalOpen = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>

                {{-- Modal Body --}}
                <div class="p-4 space-y-3 text-xs">
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Set Pipeline Stage *</label>
                        <select name="installation_stage" required class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none" :value="selectedTask?.installation_stage">
                            <option value="feasibility_check">1. Feasibility &amp; Port Check</option>
                            <option value="cable_pulling">2. Drop Cable Pulling</option>
                            <option value="splicing_power_test">3. Optical Splicing &amp; Power Test</option>
                            <option value="mikrotik_binding">4. PPPoE Binding &amp; Router Setup</option>
                            <option value="active_completed">5. Activated &amp; Live Service</option>
                            <option value="cancelled">Cancelled Order</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-2.5">
                        <div>
                            <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Drop Cable Used (Meters)</label>
                            <input type="number" name="cable_length_meters" :value="selectedTask?.cable_length_meters" placeholder="e.g. 85" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Optical RX Power (dBm)</label>
                            <input type="text" name="optical_rx_power" :value="selectedTask?.optical_rx_power" placeholder="e.g. -18.5 dBm" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2.5">
                        <div>
                            <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">ONU Model Deployed</label>
                            <input type="text" name="onu_model" :value="selectedTask?.onu_model" placeholder="VSOL V2801SG Gigabit" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">ONU MAC / PON Serial</label>
                            <input type="text" name="onu_mac_serial" :value="selectedTask?.onu_mac_serial" placeholder="VSOL:88:99:AA:BB:CC" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 focus:outline-none">
                        </div>
                    </div>
                </div>

                {{-- Modal Footer --}}
                <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" @click="advanceModalOpen = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" class="bg-cyan-600 hover:bg-cyan-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs cursor-pointer">
                        Update Pipeline Stage
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL 3: VIEW ORDER DETAILS MODAL --}}
    <div x-show="detailsModalOpen" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
         @keydown.escape.window="detailsModalOpen = false">
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden" 
             @click.away="detailsModalOpen = false">
            {{-- Modal Header --}}
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-tower-broadcast"></i>
                    </div>
                    <div>
                        <h4 class="text-xs font-semibold text-slate-800" x-text="selectedTask?.task_number"></h4>
                        <span class="text-[10.5px] text-slate-500 font-normal">Physical Connection Summary</span>
                    </div>
                </div>
                <button type="button" @click="detailsModalOpen = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            {{-- Modal Body --}}
            <div class="p-4 space-y-3 text-xs">
                <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                    <span class="text-[10px] text-slate-500 font-semibold uppercase block mb-0.5">Subscriber &amp; Location</span>
                    <p class="font-bold text-slate-900" x-text="selectedTask?.applicant_name"></p>
                    <p class="text-slate-600 text-[11px]" x-text="selectedTask?.installation_address"></p>
                    <p class="text-indigo-700 font-mono text-[11px]" x-text="'Phone: ' + selectedTask?.applicant_phone"></p>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[10px] text-slate-500 font-semibold uppercase block">Package Plan</span>
                        <span class="font-bold text-slate-800" x-text="selectedTask?.package?.name || 'Standard'"></span>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[10px] text-slate-500 font-semibold uppercase block">Assigned Tech</span>
                        <span class="font-bold text-slate-800" x-text="selectedTask?.assigned_technician_name || 'Unassigned'"></span>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[10px] text-slate-500 font-semibold uppercase block">Optical RX Signal</span>
                        <span class="font-mono font-bold text-emerald-700" x-text="selectedTask?.optical_rx_power || 'Pending'"></span>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80">
                        <span class="text-[10px] text-slate-500 font-semibold uppercase block">Cable Deployed</span>
                        <span class="font-mono font-bold text-slate-800" x-text="(selectedTask?.cable_length_meters || 0) + ' Meters'"></span>
                    </div>
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                <button type="button" @click="detailsModalOpen = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg cursor-pointer">
                    Close
                </button>
                <a :href="`/admin/support/installations/${selectedTask?.id}/print`" target="_blank" class="bg-cyan-600 hover:bg-cyan-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs inline-flex items-center gap-1.5">
                    <i class="fas fa-print text-xs"></i>
                    <span>Print Activation Slip</span>
                </a>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    function installationPageManager() {
        return {
            activeMenu: null,
            menuPos: { top: '0px', bottom: 'auto', right: '0px', left: 'auto' },
            newOrderModalOpen: false,
            advanceModalOpen: false,
            detailsModalOpen: false,
            selectedTask: null,

            toggleMenu(item, event) {
                if (this.activeMenu?.id === item.id) {
                    this.activeMenu = null;
                    return;
                }
                this.activeMenu = item;
                const rect = event.currentTarget.getBoundingClientRect();
                const dropdownHeight = 160;
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

            showTaskDetails(task) {
                this.selectedTask = task;
                this.detailsModalOpen = true;
            },

            openAdvanceModal(task) {
                this.selectedTask = task;
                this.advanceModalOpen = true;
            },

            copyTaskNumber(taskNo) {
                if (navigator.clipboard && taskNo) {
                    navigator.clipboard.writeText(taskNo);
                    if (window.Swal) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Order # Copied',
                            text: taskNo + ' copied to clipboard',
                            toast: true,
                            position: 'top-end',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                }
            }
        };
    }
</script>
@endpush
