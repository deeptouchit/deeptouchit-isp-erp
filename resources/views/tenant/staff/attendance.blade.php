@extends('tenant.layouts.app')

@section('title', 'Daily Attendance Register - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
    {{-- Page specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" x-data="attendanceManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">

    <!-- Toast Notification Banner -->
    <div x-show="toast.show" 
         x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200 transform"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2"
         class="fixed top-4 right-4 z-50 flex items-center gap-2 px-4 py-2.5 rounded-xl shadow-lg border text-xs font-medium"
         :class="{
             'bg-emerald-50 text-emerald-800 border-emerald-200': toast.type === 'success',
             'bg-rose-50 text-rose-800 border-rose-200': toast.type === 'error',
             'bg-blue-50 text-blue-800 border-blue-200': toast.type === 'info'
         }"
         style="display: none;">
        <i class="fas text-sm" :class="{
            'fa-check-circle text-emerald-600': toast.type === 'success',
            'fa-exclamation-circle text-rose-600': toast.type === 'error',
            'fa-info-circle text-blue-600': toast.type === 'info'
         }"></i>
        <span x-text="toast.message"></span>
    </div>

    <!-- 1. Top Header Bar (Strictly Icon + Title + Action Button ONLY - No Subtitle) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-3.5 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-3">
            <div class="w-7 h-7 rounded-lg bg-teal-50 text-teal-600 border border-teal-100 flex items-center justify-center text-xs flex-shrink-0">
                <i class="fas fa-clipboard-user"></i>
            </div>
            <h1 class="text-sm font-bold text-slate-900 tracking-tight">Daily Attendance Register</h1>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center gap-2">
            <!-- Export CSV -->
            <a href="{{ route('tenant.staff.attendance.export', ['date' => $selectedDate]) }}" 
               class="px-2.5 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 text-slate-700 font-semibold text-xs transition inline-flex items-center gap-1.5">
                <i class="fas fa-file-csv text-emerald-600 text-[11px]"></i>
                <span>Export CSV</span>
            </a>

            <!-- Quick Punch In/Out Modal Trigger -->
            <button type="button" 
                    @click="openPunchModal()"
                    class="px-2.5 py-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 font-semibold text-xs shadow-2xs transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-fingerprint text-emerald-600 text-[11px]"></i>
                <span>Quick Punch</span>
            </button>

            <!-- Manual Mark Attendance Modal Trigger -->
            <button type="button" 
                    @click="openMarkModal()"
                    class="px-3.5 py-1.5 rounded-lg bg-teal-600 hover:bg-teal-700 text-white font-semibold text-xs shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-calendar-check text-[11px]"></i>
                <span>+ Mark Attendance</span>
            </button>
        </div>
    </div>

    <!-- 2. KPI Summary Strip (Strictly 6 Cards) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        <!-- Metric 1: Total Active Staff -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-slate-500 uppercase tracking-wider block truncate">Active Staff</span>
                <span class="text-[13px] font-bold text-slate-900 font-mono leading-tight block">{{ number_format($totalActiveStaff) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-slate-100 text-slate-600 flex items-center justify-center text-[10px] border border-slate-200 flex-shrink-0">
                <i class="fas fa-users"></i>
            </div>
        </div>

        <!-- Metric 2: Present Today -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-emerald-700 uppercase tracking-wider block truncate">Present Today</span>
                <span class="text-[13px] font-bold text-emerald-600 font-mono leading-tight block">{{ number_format($presentTodayCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-emerald-50 text-emerald-600 flex items-center justify-center text-[10px] border border-emerald-100 flex-shrink-0">
                <i class="fas fa-user-check"></i>
            </div>
        </div>

        <!-- Metric 3: Field Duty -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-blue-700 uppercase tracking-wider block truncate">Field Duty</span>
                <span class="text-[13px] font-bold text-blue-600 font-mono leading-tight block">{{ number_format($fieldDutyCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-blue-50 text-blue-600 flex items-center justify-center text-[10px] border border-blue-100 flex-shrink-0">
                <i class="fas fa-person-walking-luggage"></i>
            </div>
        </div>

        <!-- Metric 4: Late Arrivals -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-amber-700 uppercase tracking-wider block truncate">Late Arrivals</span>
                <span class="text-[13px] font-bold text-amber-600 font-mono leading-tight block">{{ number_format($lateArrivalsCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-amber-50 text-amber-600 flex items-center justify-center text-[10px] border border-amber-100 flex-shrink-0">
                <i class="fas fa-clock"></i>
            </div>
        </div>

        <!-- Metric 5: Half Day -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-indigo-700 uppercase tracking-wider block truncate">Half Day</span>
                <span class="text-[13px] font-bold text-indigo-600 font-mono leading-tight block">{{ number_format($halfDayCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-indigo-50 text-indigo-600 flex items-center justify-center text-[10px] border border-indigo-100 flex-shrink-0">
                <i class="fas fa-business-time"></i>
            </div>
        </div>

        <!-- Metric 6: Absent / Leave -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-rose-700 uppercase tracking-wider block truncate">Absent / Leave</span>
                <span class="text-[13px] font-bold text-rose-600 font-mono leading-tight block">{{ number_format($absentLeaveCount) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-rose-50 text-rose-600 flex items-center justify-center text-[10px] border border-rose-100 flex-shrink-0">
                <i class="fas fa-user-xmark"></i>
            </div>
        </div>
    </div>

    <!-- 3. Search & Multi-Filter Toolbar -->
    <div class="bg-white p-2.5 rounded-xl border border-slate-200 shadow-xs">
        <form method="GET" action="{{ route('tenant.staff.attendance') }}" class="flex flex-wrap items-center gap-2">
            <!-- Date Picker -->
            <div class="w-36">
                <input type="date" 
                       name="date" 
                       value="{{ $selectedDate }}" 
                       onchange="this.form.submit()"
                       class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-medium focus:bg-white focus:border-teal-500 focus:outline-hidden transition">
            </div>

            <!-- Search Input -->
            <div class="relative flex-1 min-w-[180px]">
                <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                <input type="text" 
                       name="search" 
                       value="{{ $search }}" 
                       placeholder="Search Name, ID, Mobile..." 
                       class="w-full pl-8 pr-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-teal-500 focus:outline-hidden transition">
            </div>

            <!-- Scope Filter -->
            <div class="w-32">
                <select name="scope" 
                        class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-teal-500 focus:outline-hidden transition">
                    <option value="">All Scopes</option>
                    <option value="isp" {{ $scopeFilter === 'isp' ? 'selected' : '' }}>ISP HQ</option>
                    <option value="reseller" {{ $scopeFilter === 'reseller' ? 'selected' : '' }}>Sub-ISP Partners</option>
                </select>
            </div>

            <!-- Reseller Partner Filter -->
            <div class="w-40">
                <select name="reseller_id" 
                        class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-teal-500 focus:outline-hidden transition">
                    <option value="">All Partners</option>
                    @foreach($allResellers as $reseller)
                        <option value="{{ $reseller->id }}" {{ (string)$selectedResellerId === (string)$reseller->id ? 'selected' : '' }}>
                            {{ $reseller->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Status Filter -->
            <div class="w-32">
                <select name="status" 
                        class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-teal-500 focus:outline-hidden transition">
                    <option value="">All Status</option>
                    <option value="present" {{ $statusFilter === 'present' ? 'selected' : '' }}>Present</option>
                    <option value="late" {{ $statusFilter === 'late' ? 'selected' : '' }}>Late Check-in</option>
                    <option value="field_duty" {{ $statusFilter === 'field_duty' ? 'selected' : '' }}>Field Duty</option>
                    <option value="on_leave" {{ $statusFilter === 'on_leave' ? 'selected' : '' }}>On Leave</option>
                    <option value="half_day" {{ $statusFilter === 'half_day' ? 'selected' : '' }}>Half Day</option>
                    <option value="absent" {{ $statusFilter === 'absent' ? 'selected' : '' }}>Absent</option>
                </select>
            </div>

            <!-- Shift Filter -->
            <div class="w-36">
                <select name="shift" 
                        class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-teal-500 focus:outline-hidden transition">
                    <option value="">All Shifts</option>
                    <option value="Morning Shift (09:00 - 18:00)" {{ $shiftFilter === 'Morning Shift (09:00 - 18:00)' ? 'selected' : '' }}>Morning Shift</option>
                    <option value="Evening Shift (14:00 - 22:00)" {{ $shiftFilter === 'Evening Shift (14:00 - 22:00)' ? 'selected' : '' }}>Evening Shift</option>
                    <option value="Night NOC Shift (22:00 - 08:00)" {{ $shiftFilter === 'Night NOC Shift (22:00 - 08:00)' ? 'selected' : '' }}>Night NOC Shift</option>
                    <option value="Full Day Field Duty" {{ $shiftFilter === 'Full Day Field Duty' ? 'selected' : '' }}>Full Day Duty</option>
                </select>
            </div>

            <!-- Per Page Select -->
            <div class="w-24">
                <select name="per_page" 
                        class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-teal-500 focus:outline-hidden transition">
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
                <a href="{{ route('tenant.staff.attendance') }}" 
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
                        <th>Staff ID</th>
                        <th>Name</th>
                        <th>Scope / Partner</th>
                        <th>Punch In</th>
                        <th>Punch Out</th>
                        <th>Duration</th>
                        <th class="text-center">Status</th>
                        <th class="w-14 text-center no-sort">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($attendances as $index => $att)
                        @php
                            $staff = $att->user;
                            $statusBadge = $att->status_badge;
                            $staffCode = $staff?->staff_id ?? ('STF-' . str_pad($att->user_id, 4, '0', STR_PAD_LEFT));
                            $rawAttData = [
                                'id' => $att->id,
                                'user_id' => $att->user_id,
                                'staff_name' => $staff?->name ?? 'Unknown Staff',
                                'staff_code' => $staffCode,
                                'designation' => $staff?->designation ?: 'Staff Officer',
                                'phone' => $staff?->phone ?: ($staff?->mobile ?: '--'),
                                'email' => $staff?->email ?: '--',
                                'scope' => $att->reseller ? $att->reseller->name : 'ISP HQ',
                                'shift' => $att->shift ?: 'Morning Shift',
                                'date' => $att->date?->format('d M Y') ?? $selectedDate,
                                'date_raw' => $att->date?->format('Y-m-d') ?? $selectedDate,
                                'status' => $att->status,
                                'status_label' => $statusBadge['label'],
                                'punch_in' => $att->punch_in_at ? $att->punch_in_at->format('h:i A') : '--:--',
                                'punch_in_raw' => $att->punch_in_at ? $att->punch_in_at->format('H:i') : '',
                                'punch_in_ip' => $att->punch_in_ip ?: '--',
                                'punch_out' => $att->punch_out_at ? $att->punch_out_at->format('h:i A') : ($att->punch_in_at ? 'Running' : '--:--'),
                                'punch_out_raw' => $att->punch_out_at ? $att->punch_out_at->format('H:i') : '',
                                'punch_out_ip' => $att->punch_out_ip ?: '--',
                                'duration' => $att->duration_formatted,
                                'late_reason' => $att->late_reason ?: '--',
                                'remarks' => $att->remarks ?: '--',
                            ];
                        @endphp
                        <tr>
                            <!-- 1. Index -->
                            <td class="text-center font-mono text-slate-400">
                                {{ $attendances->firstItem() ? ($attendances->firstItem() + $index) : ($index + 1) }}
                            </td>

                            <!-- 2. Staff ID -->
                            <td class="font-mono text-slate-700 font-medium">
                                {{ $staffCode }}
                            </td>

                            <!-- 3. Name -->
                            <td class="font-medium text-slate-900">
                                {{ $staff?->name ?? 'Unknown Staff' }}
                            </td>

                            <!-- 4. Scope / Partner -->
                            <td>
                                @if($att->reseller)
                                    <span class="inline-flex items-center gap-1 text-slate-800">
                                        <i class="fas fa-handshake text-blue-500 text-[10px]"></i>
                                        <span>{{ $att->reseller->name }}</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-slate-800">
                                        <i class="fas fa-building text-purple-500 text-[10px]"></i>
                                        <span>ISP Core HQ</span>
                                    </span>
                                @endif
                            </td>

                            <!-- 5. Punch In -->
                            <td class="font-mono text-slate-700">
                                {{ $att->punch_in_at ? $att->punch_in_at->format('h:i A') : '--:--' }}
                            </td>

                            <!-- 6. Punch Out -->
                            <td class="font-mono">
                                @if($att->punch_out_at)
                                    <span class="text-slate-700">{{ $att->punch_out_at->format('h:i A') }}</span>
                                @elseif($att->punch_in_at)
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.2 rounded text-[10px] bg-amber-50 text-amber-700 border border-amber-200">
                                        <i class="fas fa-spinner fa-spin text-[8px]"></i>
                                        <span>Running</span>
                                    </span>
                                @else
                                    <span class="text-slate-400">--:--</span>
                                @endif
                            </td>

                            <!-- 7. Duration -->
                            <td class="font-mono text-slate-700">
                                {{ $att->duration_formatted }}
                            </td>

                            <!-- 8. Status -->
                            <td class="text-center">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold border {{ $statusBadge['bg'] }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $statusBadge['dot'] }}"></span>
                                <span>{{ $statusBadge['label'] }}</span>
                            </span>
                            </td>

                            <!-- 9. Action (Floating 3-Dot Dropdown) -->
                            <td class="text-center">
                                <button type="button" 
                                        @click="toggleMenu({{ Js::from($rawAttData) }}, $event)" 
                                        class="w-6 h-6 rounded-md hover:bg-slate-100 text-slate-400 hover:text-slate-700 transition cursor-pointer inline-flex items-center justify-center">
                                    <i class="fas fa-ellipsis-v text-[10px]"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-8 text-center text-slate-400 text-xs italic bg-slate-50/50">
                                <i class="fas fa-clipboard-list text-2xl text-slate-300 mb-2 block"></i>
                                No attendance records found for {{ $selectedDate }} matching your filter criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination Bar -->
        @if($attendances->hasPages())
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200">
                {{ $attendances->links() }}
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
            <!-- View Full Attendance Details -->
            <button type="button" 
                    @click="const item = activeMenu; activeMenu = null; openDetailsModal(item)" 
                    class="w-full px-3 py-1.5 hover:bg-slate-50 text-slate-700 flex items-center gap-2 transition text-left cursor-pointer">
                <i class="fas fa-id-card text-teal-600 w-3.5 text-center text-[11px]"></i>
                <span>View Full Details</span>
            </button>

            <!-- Edit Attendance Record -->
            <button type="button" 
                    @click="const item = activeMenu; activeMenu = null; editAttendanceFromMenu(item)" 
                    class="w-full px-3 py-1.5 hover:bg-slate-50 text-slate-700 flex items-center gap-2 transition text-left cursor-pointer">
                <i class="fas fa-pen-to-square text-indigo-600 w-3.5 text-center text-[11px]"></i>
                <span>Edit Record</span>
            </button>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- NATURAL SMART MODALS                                                      -->
    <!-- ========================================================================= -->

    <!-- MODAL 1: VIEW FULL ATTENDANCE DETAILS -->
    <div x-show="detailsModal.open" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="display: none;">
        
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden"
             @click.away="detailsModal.open = false">
            
            <!-- Soft Natural Header -->
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-teal-50 text-teal-600 border border-teal-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-clipboard-user"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800" x-text="detailsModal.data?.staff_name || 'Attendance Details'"></h3>
                        <p class="text-[10.5px] text-slate-500 font-normal" x-text="(detailsModal.data?.staff_code || '') + ' • ' + (detailsModal.data?.designation || '')"></p>
                    </div>
                </div>
                <button type="button" @click="detailsModal.open = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <!-- Modal Content Grid -->
            <div class="p-4 space-y-3.5 text-xs text-slate-800 max-h-[75vh] overflow-y-auto" x-if="detailsModal.data">
                <div class="grid grid-cols-2 gap-2">
                    <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200/80">
                        <span class="text-[10px] font-medium text-slate-400 uppercase block">Date</span>
                        <span class="font-semibold text-slate-800 block text-xs" x-text="detailsModal.data?.date"></span>
                    </div>

                    <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200/80">
                        <span class="text-[10px] font-medium text-slate-400 uppercase block">Scope</span>
                        <span class="font-semibold text-slate-800 block text-xs truncate" x-text="detailsModal.data?.scope"></span>
                    </div>

                    <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200/80">
                        <span class="text-[10px] font-medium text-slate-400 uppercase block">Status</span>
                        <span class="font-semibold text-teal-700 uppercase block text-xs" x-text="detailsModal.data?.status_label"></span>
                    </div>

                    <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200/80">
                        <span class="text-[10px] font-medium text-slate-400 uppercase block">Work Duration</span>
                        <span class="font-mono font-semibold text-slate-800 block text-xs" x-text="detailsModal.data?.duration"></span>
                    </div>

                    <div class="p-2.5 rounded-lg bg-emerald-50/50 border border-emerald-100">
                        <span class="text-emerald-600 text-[10px] font-medium uppercase block">Punch In</span>
                        <span class="font-mono font-bold text-emerald-800 block text-xs" x-text="detailsModal.data?.punch_in"></span>
                        <span class="text-[10px] text-slate-400 font-mono block mt-0.5" x-text="'IP: ' + detailsModal.data?.punch_in_ip"></span>
                    </div>

                    <div class="p-2.5 rounded-lg bg-blue-50/50 border border-blue-100">
                        <span class="text-blue-600 text-[10px] font-medium uppercase block">Punch Out</span>
                        <span class="font-mono font-bold text-blue-800 block text-xs" x-text="detailsModal.data?.punch_out"></span>
                        <span class="text-[10px] text-slate-400 font-mono block mt-0.5" x-text="'IP: ' + detailsModal.data?.punch_out_ip"></span>
                    </div>
                </div>

                <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200/80">
                    <span class="text-[10px] font-medium text-slate-400 uppercase block">Shift &amp; Schedule</span>
                    <span class="text-xs text-slate-700 block mt-0.5" x-text="detailsModal.data?.shift"></span>
                </div>

                <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200/80" x-show="detailsModal.data?.late_reason !== '--'">
                    <span class="text-[10px] font-medium text-amber-700 uppercase block">Late Arrival Reason</span>
                    <span class="text-xs text-slate-700 block mt-0.5" x-text="detailsModal.data?.late_reason"></span>
                </div>

                <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200/80" x-show="detailsModal.data?.remarks !== '--'">
                    <span class="text-[10px] font-medium text-slate-400 uppercase block">Remarks &amp; Notes</span>
                    <span class="text-xs text-slate-700 block mt-0.5" x-text="detailsModal.data?.remarks"></span>
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

    <!-- MODAL 2: MANUAL MARK / EDIT ATTENDANCE -->
    <div x-show="markModal.open" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="display: none;">
        
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden"
             @click.away="markModal.open = false">
            
            <!-- Soft Natural Header -->
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-teal-50 text-teal-600 border border-teal-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800" x-text="markModal.isEdit ? 'Edit Attendance Record' : 'Record Staff Attendance'"></h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">Daily duty log, punch timestamps &amp; remarks</p>
                    </div>
                </div>
                <button type="button" @click="markModal.open = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <!-- Form Body -->
            <form @submit.prevent="submitMarkAttendance()">
                <div class="p-4 space-y-3.5 text-xs max-h-[75vh] overflow-y-auto">
                    
                    <!-- Select Staff -->
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            Staff Member <span class="text-rose-500">*</span>
                        </label>
                        <select x-model="markModal.form.user_id" 
                                required
                                :disabled="markModal.isEdit"
                                class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-semibold text-slate-800 focus:bg-white focus:border-teal-500 focus:outline-hidden transition">
                            <option value="">-- Choose Employee --</option>
                            @foreach($allStaff as $stf)
                                <option value="{{ $stf->id }}">
                                    {{ $stf->name }} ({{ $stf->staff_id ?? ('STF-' . str_pad($stf->id, 4, '0', STR_PAD_LEFT)) }}) - {{ $stf->role_badge['label'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Date & Status Row -->
                    <div class="grid grid-cols-2 gap-2.5">
                        <div>
                            <label class="block text-[11px] font-medium text-slate-700 mb-1">
                                Date <span class="text-rose-500">*</span>
                            </label>
                            <input type="date" 
                                   x-model="markModal.form.date" 
                                   required
                                   class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-900 focus:bg-white focus:border-teal-500 focus:outline-hidden transition font-medium">
                        </div>

                        <div>
                            <label class="block text-[11px] font-medium text-slate-700 mb-1">
                                Status <span class="text-rose-500">*</span>
                            </label>
                            <select x-model="markModal.form.status" 
                                    required
                                    class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-teal-500 focus:outline-hidden transition font-medium">
                                <option value="present">Present</option>
                                <option value="late">Late Check-in</option>
                                <option value="field_duty">Field Duty</option>
                                <option value="on_leave">On Leave</option>
                                <option value="half_day">Half Day</option>
                                <option value="absent">Absent</option>
                            </select>
                        </div>
                    </div>

                    <!-- Punch In & Punch Out Time Row -->
                    <div class="grid grid-cols-2 gap-2.5" x-show="markModal.form.status !== 'absent' && markModal.form.status !== 'on_leave'">
                        <div>
                            <label class="block text-[11px] font-medium text-slate-700 mb-1">
                                Punch In Time (24h)
                            </label>
                            <input type="time" 
                                   x-model="markModal.form.punch_in_time" 
                                   class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono text-slate-900 focus:bg-white focus:border-teal-500 focus:outline-hidden transition">
                        </div>

                        <div>
                            <label class="block text-[11px] font-medium text-slate-700 mb-1">
                                Punch Out Time (24h)
                            </label>
                            <input type="time" 
                                   x-model="markModal.form.punch_out_time" 
                                   class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono text-slate-900 focus:bg-white focus:border-teal-500 focus:outline-hidden transition">
                        </div>
                    </div>

                    <!-- Late Reason (conditional if Late selected) -->
                    <div x-show="markModal.form.status === 'late'">
                        <label class="block text-[11px] font-medium text-amber-800 mb-1">
                            Late Arrival Reason
                        </label>
                        <input type="text" 
                               x-model="markModal.form.late_reason" 
                               placeholder="Traffic delay, weather, client emergency..." 
                               class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-900 focus:bg-white focus:border-amber-500 focus:outline-hidden transition font-medium">
                    </div>

                    <!-- Remarks / Notes -->
                    <div>
                        <label class="block text-[11px] font-medium text-slate-700 mb-1">
                            Duty Remarks / Site Location Note
                        </label>
                        <textarea x-model="markModal.form.remarks" 
                                  rows="2" 
                                  placeholder="Notes regarding shift, field work location, ticket reference..."
                                  class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:bg-white focus:border-teal-500 focus:outline-hidden transition font-normal"></textarea>
                    </div>

                </div>

                <!-- Modal Footer -->
                <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" 
                            @click="markModal.open = false" 
                            class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg cursor-pointer transition">
                        Cancel
                    </button>
                    <button type="submit" 
                            :disabled="markModal.loading"
                            class="bg-teal-600 hover:bg-teal-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer disabled:opacity-50">
                        <i class="fas fa-spinner fa-spin text-xs" x-show="markModal.loading" style="display: none;"></i>
                        <span x-text="markModal.isEdit ? 'Update Attendance' : 'Save Attendance'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 3: QUICK PUNCH IN / OUT MODAL -->
    <div x-show="punchModal.open" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="display: none;">
        
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-md overflow-hidden"
             @click.away="punchModal.open = false">
            
            <!-- Soft Natural Header -->
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-fingerprint"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">Instant Punch Clock</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">Real-time attendance punch with telemetry</p>
                    </div>
                </div>
                <button type="button" @click="punchModal.open = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <div class="p-4 space-y-3.5 text-xs text-center">
                <!-- Select Employee for Quick Punch -->
                <div class="text-left">
                    <label class="block text-[11px] font-medium text-slate-700 mb-1">Select Employee <span class="text-rose-500">*</span></label>
                    <select x-model="punchModal.user_id" 
                            class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-semibold text-slate-800 focus:bg-white focus:border-emerald-500 focus:outline-hidden transition">
                        @foreach($allStaff as $stf)
                            <option value="{{ $stf->id }}">
                                {{ $stf->name }} ({{ $stf->staff_id ?? ('STF-' . str_pad($stf->id, 4, '0', STR_PAD_LEFT)) }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Digital Clock Display -->
                <div class="p-3 bg-slate-900 rounded-xl text-white font-mono">
                    <span class="text-2xl font-bold tracking-wider text-emerald-400" x-text="currentTime"></span>
                    <span class="block text-[10px] text-slate-400 mt-0.5">{{ date('l, d F Y') }}</span>
                </div>

                <!-- Action Button -->
                <button type="button" 
                        @click="executeQuickPunch()"
                        :disabled="punchModal.loading"
                        class="w-full py-2.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs shadow-xs transition flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50">
                    <i class="fas fa-spinner fa-spin text-xs" x-show="punchModal.loading" style="display: none;"></i>
                    <i class="fas fa-arrow-right-to-bracket text-xs" x-show="!punchModal.loading"></i>
                    <span>Execute Punch In / Out Now</span>
                </button>
            </div>

            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end">
                <button type="button" @click="punchModal.open = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg cursor-pointer transition">
                    Close Window
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function attendanceManager() {
    return {
        currentTime: '{{ now()->format("h:i:s A") }}',
        activeMenu: null,
        menuPos: { top: '0px', bottom: 'auto', right: '0px', left: 'auto' },

        toast: {
            show: false,
            message: '',
            type: 'success',
            timeout: null
        },

        detailsModal: {
            open: false,
            data: null
        },

        markModal: {
            open: false,
            loading: false,
            isEdit: false,
            form: {
                id: null,
                user_id: '',
                date: '{{ $selectedDate }}',
                status: 'present',
                punch_in_time: '09:00',
                punch_out_time: '18:00',
                late_reason: '',
                remarks: ''
            }
        },

        punchModal: {
            open: false,
            loading: false,
            user_id: '{{ $allStaff->first()?->id ?? "" }}'
        },

        init() {
            setInterval(() => {
                const now = new Date();
                this.currentTime = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
            }, 1000);
        },

        showToast(message, type = 'success') {
            this.toast.message = message;
            this.toast.type = type;
            this.toast.show = true;
            if (this.toast.timeout) clearTimeout(this.toast.timeout);
            this.toast.timeout = setTimeout(() => {
                this.toast.show = false;
            }, 4000);
        },

        toggleMenu(item, event) {
            if (this.activeMenu?.id === item.id) {
                this.activeMenu = null;
                return;
            }
            this.activeMenu = item;
            const rect = event.currentTarget.getBoundingClientRect();
            const dropdownHeight = 110;
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

        openMarkModal() {
            this.markModal.isEdit = false;
            this.markModal.form = {
                id: null,
                user_id: '{{ $allStaff->first()?->id ?? "" }}',
                date: '{{ $selectedDate }}',
                status: 'present',
                punch_in_time: '09:00',
                punch_out_time: '18:00',
                late_reason: '',
                remarks: ''
            };
            this.markModal.open = true;
        },

        editAttendanceFromMenu(data) {
            this.markModal.isEdit = true;
            this.markModal.form = {
                id: data.id,
                user_id: data.user_id,
                date: data.date_raw,
                status: data.status,
                punch_in_time: data.punch_in_raw,
                punch_out_time: data.punch_out_raw,
                late_reason: data.late_reason === '--' ? '' : data.late_reason,
                remarks: data.remarks === '--' ? '' : data.remarks
            };
            this.markModal.open = true;
        },

        openPunchModal() {
            this.punchModal.open = true;
        },

        async submitMarkAttendance() {
            this.markModal.loading = true;
            try {
                const res = await fetch('{{ route("tenant.staff.attendance.mark") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.markModal.form)
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    this.showToast(data.message, 'success');
                    this.markModal.open = false;
                    setTimeout(() => window.location.reload(), 800);
                } else {
                    this.showToast(data.message || 'Failed to record attendance', 'error');
                }
            } catch (err) {
                this.showToast('Network error while saving attendance record.', 'error');
            } finally {
                this.markModal.loading = false;
            }
        },

        async executeQuickPunch() {
            this.punchModal.loading = true;
            try {
                const res = await fetch('{{ route("tenant.staff.attendance.punch") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ user_id: this.punchModal.user_id })
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    this.showToast(data.message, 'success');
                    this.punchModal.open = false;
                    setTimeout(() => window.location.reload(), 800);
                } else {
                    this.showToast(data.message || 'Punch operation failed', 'error');
                }
            } catch (err) {
                this.showToast('Network error while processing punch action.', 'error');
            } finally {
                this.punchModal.loading = false;
            }
        }
    };
}
</script>
@endpush
