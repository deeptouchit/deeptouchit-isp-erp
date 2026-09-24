@extends('tenant.layouts.app')

@section('title', 'Activity Audit Trail - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
    {{-- Page specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" x-data="logsManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">

    <!-- Toast Notification Banner -->
    <div x-show="toast.show" 
         x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200 transform"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2"
         class="fixed top-4 right-4 z-50 flex items-center gap-2 px-4 py-2.5 rounded-xl shadow-lg border text-xs font-medium bg-emerald-50 text-emerald-800 border-emerald-200"
         style="display: none;">
        <i class="fas fa-check-circle text-emerald-600 text-sm"></i>
        <span x-text="toast.message"></span>
    </div>

    <!-- 1. Top Header Bar (Strictly Icon + Title + Action Button ONLY - No Subtitle) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-3.5 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-3">
            <div class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 border border-indigo-100 flex items-center justify-center text-xs flex-shrink-0">
                <i class="fas fa-shield-halved"></i>
            </div>
            <h1 class="text-sm font-bold text-slate-900 tracking-tight">Activity Audit Trail</h1>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center gap-2">
            <a href="{{ route('tenant.staff.logs') }}" 
               class="px-2.5 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 text-slate-700 font-semibold text-xs transition inline-flex items-center gap-1.5">
                <i class="fas fa-rotate text-slate-400 text-[11px]"></i>
                <span>Refresh Stream</span>
            </a>
        </div>
    </div>

    <!-- 2. KPI Summary Strip (Strictly 6 Cards) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        <!-- Metric 1: Logs Today -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-slate-500 uppercase tracking-wider block truncate">Logs Today</span>
                <span class="text-[13px] font-bold text-slate-900 font-mono leading-tight block">{{ number_format($todayLogsCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-slate-100 text-slate-600 flex items-center justify-center text-[10px] border border-slate-200 flex-shrink-0">
                <i class="fas fa-list-check"></i>
            </div>
        </div>

        <!-- Metric 2: Authentication -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-emerald-700 uppercase tracking-wider block truncate">Auth Events</span>
                <span class="text-[13px] font-bold text-emerald-600 font-mono leading-tight block">{{ number_format($authEventsCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-emerald-50 text-emerald-600 flex items-center justify-center text-[10px] border border-emerald-100 flex-shrink-0">
                <i class="fas fa-key"></i>
            </div>
        </div>

        <!-- Metric 3: Network & ONUs -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-blue-700 uppercase tracking-wider block truncate">Network &amp; ONU</span>
                <span class="text-[13px] font-bold text-blue-600 font-mono leading-tight block">{{ number_format($networkEventsCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-blue-50 text-blue-600 flex items-center justify-center text-[10px] border border-blue-100 flex-shrink-0">
                <i class="fas fa-network-wired"></i>
            </div>
        </div>

        <!-- Metric 4: Billing & Recharge -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-amber-700 uppercase tracking-wider block truncate">Billing / Wallet</span>
                <span class="text-[13px] font-bold text-amber-600 font-mono leading-tight block">{{ number_format($billingEventsCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-amber-50 text-amber-600 flex items-center justify-center text-[10px] border border-amber-100 flex-shrink-0">
                <i class="fas fa-wallet"></i>
            </div>
        </div>

        <!-- Metric 5: Support Tickets -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-purple-700 uppercase tracking-wider block truncate">Support Tickets</span>
                <span class="text-[13px] font-bold text-purple-600 font-mono leading-tight block">{{ number_format($supportEventsCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-purple-50 text-purple-600 flex items-center justify-center text-[10px] border border-purple-100 flex-shrink-0">
                <i class="fas fa-headset"></i>
            </div>
        </div>

        <!-- Metric 6: HRM & Attendance -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-teal-700 uppercase tracking-wider block truncate">HR &amp; Attendance</span>
                <span class="text-[13px] font-bold text-teal-600 font-mono leading-tight block">{{ number_format($attendanceEventsCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-teal-50 text-teal-600 flex items-center justify-center text-[10px] border border-teal-100 flex-shrink-0">
                <i class="fas fa-clipboard-check"></i>
            </div>
        </div>
    </div>

    <!-- 3. Search & Multi-Filter Toolbar -->
    <div class="bg-white p-2.5 rounded-xl border border-slate-200 shadow-xs">
        <form method="GET" action="{{ route('tenant.staff.logs') }}" class="flex flex-wrap items-center gap-2">
            <!-- Date Picker -->
            <div class="w-36">
                <input type="date" 
                       name="date" 
                       value="{{ $selectedDate }}" 
                       onchange="this.form.submit()"
                       class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-medium focus:bg-white focus:border-indigo-500 focus:outline-hidden transition">
            </div>

            <!-- Search Input -->
            <div class="relative flex-1 min-w-[180px]">
                <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                <input type="text" 
                       name="search" 
                       value="{{ $search }}" 
                       placeholder="Search Description, Actor, IP..." 
                       class="w-full pl-8 pr-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-indigo-500 focus:outline-hidden transition">
            </div>

            <!-- Operator / Actor Filter -->
            <div class="w-40">
                <select name="actor_id" 
                        class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-indigo-500 focus:outline-hidden transition">
                    <option value="">All Staff</option>
                    @foreach($allStaff as $stf)
                        <option value="{{ $stf->id }}" {{ (string)$actorFilter === (string)$stf->id ? 'selected' : '' }}>
                            {{ $stf->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Event Type / Category Filter -->
            <div class="w-36">
                <select name="event_type" 
                        class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-indigo-500 focus:outline-hidden transition">
                    <option value="">All Categories</option>
                    @foreach($distinctEvents as $evt)
                        <option value="{{ $evt }}" {{ (string)$eventTypeFilter === (string)$evt ? 'selected' : '' }}>
                            {{ $evt }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Per Page Select -->
            <div class="w-24">
                <select name="per_page" 
                        class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-indigo-500 focus:outline-hidden transition">
                    <option value="15" {{ request('per_page', 20) == 15 ? 'selected' : '' }}>15 / Page</option>
                    <option value="20" {{ request('per_page', 20) == 20 ? 'selected' : '' }}>20 / Page</option>
                    <option value="30" {{ request('per_page', 20) == 30 ? 'selected' : '' }}>30 / Page</option>
                    <option value="50" {{ request('per_page', 20) == 50 ? 'selected' : '' }}>50 / Page</option>
                </select>
            </div>

            <!-- Action Buttons (Strict Universal Standard) -->
            <div class="flex items-center gap-1.5 ml-auto flex-shrink-0">
                <button type="submit" 
                        class="bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs py-1.5 px-3 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer" 
                        title="Apply Filters">
                    <i class="fas fa-filter text-[10px]"></i>
                    <span>Filter</span>
                </button>
                <a href="{{ route('tenant.staff.logs') }}" 
                   class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs py-1.5 px-2.5 rounded-lg border border-slate-200/80 transition flex items-center justify-center gap-1 cursor-pointer" 
                   title="Reset Filters">
                    <i class="fas fa-rotate-left text-[10px]"></i>
                    <span>Reset</span>
                </a>
            </div>
        </form>
    </div>

    <!-- 4. Master Table (<table class="saas-table">) -->
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-12 text-center">#</th>
                        <th>Timestamp</th>
                        <th>Actor / Staff</th>
                        <th>Category</th>
                        <th>Action Description</th>
                        <th>IP Address</th>
                        <th class="w-14 text-center no-sort">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($activityLogs as $index => $log)
                        @php
                            $rawLogData = [
                                'id' => $log->id,
                                'actor_name' => $log->actor_name ?? 'System',
                                'event_type' => $log->event_type ?? 'GENERAL',
                                'description' => $log->description,
                                'ip_address' => $log->ip_address ?: '127.0.0.1',
                                'user_agent' => $log->user_agent ?: '--',
                                'created_at_formatted' => $log->created_at->format('d M Y, h:i:s A'),
                                'created_at_human' => $log->created_at->diffForHumans(),
                                'metadata' => $log->metadata ?? []
                            ];
                        @endphp
                        <tr>
                            <!-- 1. Index -->
                            <td class="text-center font-mono text-slate-400">
                                {{ $activityLogs->firstItem() ? ($activityLogs->firstItem() + $index) : ($index + 1) }}
                            </td>

                            <!-- 2. Timestamp -->
                            <td class="font-mono text-slate-700" title="{{ $log->created_at->format('Y-m-d H:i:s') }}">
                                {{ $log->created_at->format('d M Y, h:i A') }}
                            </td>

                            <!-- 3. Actor / Staff -->
                            <td class="font-semibold text-slate-900">
                                {{ $log->actor_name ?: 'System Engine' }}
                            </td>

                            <!-- 4. Category Badge -->
                            <td>
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-mono font-semibold border
                                    @if(str_contains($log->event_type, 'AUTH')) bg-emerald-50 text-emerald-700 border-emerald-200
                                    @elseif(str_contains($log->event_type, 'ONU') || str_contains($log->event_type, 'NETWORK')) bg-blue-50 text-blue-700 border-blue-200
                                    @elseif(str_contains($log->event_type, 'BILL') || str_contains($log->event_type, 'RECHARGE') || str_contains($log->event_type, 'WALLET')) bg-amber-50 text-amber-700 border-amber-200
                                    @elseif(str_contains($log->event_type, 'TICKET') || str_contains($log->event_type, 'SUPPORT')) bg-purple-50 text-purple-700 border-purple-200
                                    @elseif(str_contains($log->event_type, 'ATTENDANCE') || str_contains($log->event_type, 'PUNCH')) bg-teal-50 text-teal-700 border-teal-200
                                    @else bg-slate-100 text-slate-700 border-slate-200 @endif">
                                    {{ $log->event_type }}
                                </span>
                            </td>

                            <!-- 5. Action Description -->
                            <td class="text-slate-800 max-w-md truncate" title="{{ $log->description }}">
                                {{ $log->description }}
                            </td>

                            <!-- 6. IP Address -->
                            <td class="font-mono text-slate-600">
                                {{ $log->ip_address ?: '127.0.0.1' }}
                            </td>

                            <!-- 7. Action (Floating 3-Dot Dropdown) -->
                            <td class="text-center">
                                <button type="button" 
                                        @click="toggleMenu({{ Js::from($rawLogData) }}, $event)" 
                                        class="w-6 h-6 rounded-md hover:bg-slate-100 text-slate-400 hover:text-slate-700 transition cursor-pointer inline-flex items-center justify-center">
                                    <i class="fas fa-ellipsis-v text-[10px]"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-slate-400 text-xs italic bg-slate-50/50">
                                <i class="fas fa-shield-halved text-2xl text-slate-300 mb-2 block"></i>
                                No activity audit records found matching your filter criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination Bar -->
        @if($activityLogs->hasPages())
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200">
                {{ $activityLogs->links() }}
            </div>
        @endif
    </div>

    <!-- 5. Floating Action Dropdown Menu (Fixed z-50 with BoundingClientRect Positioning) -->
    <div x-show="activeMenu !== null" 
         @click.outside="activeMenu = null"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         :style="{ top: menuPos.top, bottom: menuPos.bottom, right: menuPos.right, left: menuPos.left }"
         class="fixed z-50 w-44 bg-white rounded-xl shadow-xl border border-slate-200 py-1 text-left text-xs divide-y divide-slate-100"
         style="display: none;">
        
        <div class="py-1">
            <!-- View Full Audit Details -->
            <button type="button" 
                    @click="const item = activeMenu; activeMenu = null; openDetailsModal(item)" 
                    class="w-full px-3 py-1.5 hover:bg-slate-50 text-slate-700 flex items-center gap-2 transition text-left cursor-pointer">
                <i class="fas fa-file-lines text-indigo-600 w-3.5 text-center text-[11px]"></i>
                <span>View Full Payload</span>
            </button>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- NATURAL SMART MODALS                                                      -->
    <!-- ========================================================================= -->

    <!-- MODAL 1: VIEW FULL AUDIT LOG PAYLOAD & DETAILS -->
    <div x-show="detailsModal.open" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="display: none;">
        
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-xl overflow-hidden"
             @click.away="detailsModal.open = false">
            
            <!-- Soft Natural Header -->
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 border border-indigo-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-shield-halved"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">Activity Audit Event Details</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal" x-text="detailsModal.data?.event_type + ' • ID #' + detailsModal.data?.id"></p>
                    </div>
                </div>
                <button type="button" @click="detailsModal.open = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="p-4 space-y-3 text-xs text-slate-800 max-h-[75vh] overflow-y-auto" x-if="detailsModal.data">
                
                <!-- Key Summary Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                    <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200/80">
                        <span class="text-[10px] font-medium text-slate-400 uppercase block">Actor / Staff</span>
                        <span class="font-semibold text-slate-900 block text-xs" x-text="detailsModal.data?.actor_name"></span>
                    </div>

                    <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200/80">
                        <span class="text-[10px] font-medium text-slate-400 uppercase block">Category</span>
                        <span class="font-mono font-semibold text-indigo-700 block text-xs truncate" x-text="detailsModal.data?.event_type"></span>
                    </div>

                    <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200/80">
                        <span class="text-[10px] font-medium text-slate-400 uppercase block">Timestamp</span>
                        <span class="font-mono text-slate-700 block text-xs" x-text="detailsModal.data?.created_at_formatted"></span>
                    </div>

                    <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200/80">
                        <span class="text-[10px] font-medium text-slate-400 uppercase block">Client IP</span>
                        <span class="font-mono font-semibold text-slate-800 block text-xs" x-text="detailsModal.data?.ip_address"></span>
                    </div>

                    <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200/80 sm:col-span-2">
                        <span class="text-[10px] font-medium text-slate-400 uppercase block">User Agent</span>
                        <span class="font-mono text-[11px] text-slate-600 block truncate" x-text="detailsModal.data?.user_agent"></span>
                    </div>
                </div>

                <!-- Event Description -->
                <div class="p-3 rounded-lg bg-slate-50 border border-slate-200/80 space-y-1">
                    <span class="text-[10px] font-medium text-slate-400 uppercase block">Action Summary</span>
                    <p class="text-xs text-slate-900 font-medium leading-relaxed" x-text="detailsModal.data?.description"></p>
                </div>

                <!-- Payload / Metadata Viewer -->
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-semibold text-slate-700">Metadata Payload</span>
                        <button type="button" 
                                @click="copyPayload()" 
                                class="text-[10.5px] font-medium text-indigo-600 hover:text-indigo-800 flex items-center gap-1 cursor-pointer">
                            <i class="fas fa-copy"></i>
                            <span>Copy JSON</span>
                        </button>
                    </div>
                    <pre class="p-3 bg-slate-900 text-cyan-300 font-mono text-[11px] rounded-lg border border-slate-800 overflow-x-auto max-h-48"
                         x-text="JSON.stringify(detailsModal.data?.metadata || {}, null, 2)"></pre>
                </div>

            </div>

            <!-- Modal Footer -->
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end">
                <button type="button" @click="detailsModal.open = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg cursor-pointer transition">
                    Close Details
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function logsManager() {
    return {
        activeMenu: null,
        menuPos: { top: '0px', bottom: 'auto', right: '0px', left: 'auto' },

        toast: {
            show: false,
            message: '',
            timeout: null
        },

        detailsModal: {
            open: false,
            data: null
        },

        showToast(message) {
            this.toast.message = message;
            this.toast.show = true;
            if (this.toast.timeout) clearTimeout(this.toast.timeout);
            this.toast.timeout = setTimeout(() => {
                this.toast.show = false;
            }, 3000);
        },

        toggleMenu(item, event) {
            if (this.activeMenu?.id === item.id) {
                this.activeMenu = null;
                return;
            }
            this.activeMenu = item;
            const rect = event.currentTarget.getBoundingClientRect();
            const dropdownHeight = 60;
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

        openDetailsModal(data) {
            this.detailsModal.data = data;
            this.detailsModal.open = true;
        },

        copyPayload() {
            const payload = JSON.stringify(this.detailsModal.data?.metadata || {}, null, 2);
            navigator.clipboard.writeText(payload).then(() => {
                this.showToast('Metadata payload copied to clipboard!');
            });
        }
    };
}
</script>
@endpush
