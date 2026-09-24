@extends('tenant.layouts.app')

@section('title', ($employee->name ?? 'Staff Profile') . ' - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
    {{-- Page-specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" x-data="{ activeTab: 'profile' }">

    <!-- 1. Top Header Bar (Strictly Icon + Title + Action Buttons ONLY - No Subtitles) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-3.5 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-3">
            <div class="w-7 h-7 rounded-lg bg-purple-50 text-purple-600 border border-purple-100 flex items-center justify-center text-xs flex-shrink-0">
                <i class="fas fa-user-tie"></i>
            </div>
            <h1 class="text-sm font-bold text-slate-900 tracking-tight">{{ $employee->name }}</h1>
        </div>

        <div class="flex items-center gap-2">
            @if(auth()->user()?->isCollector() || auth()->user()?->isTechnician())
                <a href="{{ route('tenant.customers.index') }}" 
                   class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs border border-slate-200/80 transition inline-flex items-center gap-1.5 cursor-pointer">
                    <i class="fas fa-arrow-left text-[10px]"></i>
                    <span>{{ __('Back to Customers') }}</span>
                </a>
            @else
                <a href="{{ route('tenant.staff.index') }}" 
                   class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs border border-slate-200/80 transition inline-flex items-center gap-1.5 cursor-pointer">
                    <i class="fas fa-arrow-left text-[10px]"></i>
                    <span>{{ __('Back to Staff') }}</span>
                </a>
            @endif
        </div>
    </div>

    <!-- 2. KPI Summary Strip (Strictly 6 Cards) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        <!-- Metric 1: Staff ID -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-purple-700 uppercase tracking-wider block truncate">{{ __('Staff ID') }}</span>
                <span class="text-[13px] font-bold font-mono text-slate-800 leading-tight block truncate">{{ $stats['staff_id'] }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-purple-50 text-purple-600 border border-purple-100 flex items-center justify-center text-[10px] flex-shrink-0">
                <i class="fas fa-id-badge"></i>
            </div>
        </div>

        <!-- Metric 2: Operational Role -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-blue-700 uppercase tracking-wider block truncate">{{ __('Role') }}</span>
                <span class="text-[13px] font-bold text-slate-800 leading-tight block truncate">{{ $stats['role'] }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center text-[10px] flex-shrink-0">
                <i class="fas fa-user-shield"></i>
            </div>
        </div>

        <!-- Metric 3: Scope / Branch -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-emerald-700 uppercase tracking-wider block truncate">{{ __('Assignment') }}</span>
                <span class="text-[13px] font-bold text-slate-800 leading-tight block truncate">{{ $stats['scope'] }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center text-[10px] flex-shrink-0">
                <i class="fas fa-building"></i>
            </div>
        </div>

        <!-- Metric 4: Duty Shift -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-amber-700 uppercase tracking-wider block truncate">{{ __('Shift') }}</span>
                <span class="text-[13px] font-bold text-slate-800 leading-tight block truncate">{{ $stats['shift'] }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-amber-50 text-amber-600 border border-amber-100 flex items-center justify-center text-[10px] flex-shrink-0">
                <i class="fas fa-clock"></i>
            </div>
        </div>

        <!-- Metric 5: Status -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-teal-700 uppercase tracking-wider block truncate">{{ __('Status') }}</span>
                <span class="text-[13px] font-bold text-slate-800 leading-tight block truncate">{{ $stats['status'] }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-teal-50 text-teal-600 border border-teal-100 flex items-center justify-center text-[10px] flex-shrink-0">
                <i class="fas fa-circle-check"></i>
            </div>
        </div>

        <!-- Metric 6: Joined Date -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0">
                <span class="text-[9px] font-medium text-indigo-700 uppercase tracking-wider block truncate">{{ __('Joined') }}</span>
                <span class="text-[13px] font-bold font-mono text-slate-800 leading-tight block truncate">{{ $stats['joined_at'] }}</span>
            </div>
            <div class="w-6 h-6 rounded-md bg-indigo-50 text-indigo-600 border border-indigo-100 flex items-center justify-center text-[10px] flex-shrink-0">
                <i class="fas fa-calendar-check"></i>
            </div>
        </div>
    </div>

    <!-- Main Content Details Grid (2 Columns) -->
    <div class="grid grid-cols-12 gap-3.5 items-start">
        
        <!-- Left Column: Personal Profile & Contact Card (5 of 12) -->
        <div class="col-span-12 lg:col-span-5 space-y-3.5">
            <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-4 space-y-4">
                
                <!-- Avatar & Identity Header -->
                <div class="flex items-center gap-3 pb-3 border-b border-slate-100">
                    @if(!empty($employee->avatar_url))
                        <img src="{{ $employee->avatar_url }}" alt="{{ $employee->name }}" class="w-12 h-12 rounded-xl object-cover ring-2 ring-purple-200 shadow-xs">
                    @else
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-tr from-purple-600 to-indigo-600 text-white flex items-center justify-center font-bold text-base shadow-xs">
                            {{ strtoupper(substr($employee->name ?? 'E', 0, 1)) }}
                        </div>
                    @endif
                    <div class="min-w-0 flex-1">
                        <h2 class="text-sm font-bold text-slate-900 truncate">{{ $employee->name }}</h2>
                        <div class="text-[11px] text-slate-500 truncate">{{ $employee->designation ?: 'Staff Member' }}</div>
                        <div class="mt-1 flex items-center gap-1.5 flex-wrap">
                            <span class="px-1.5 py-0.5 rounded text-[9.5px] font-mono font-bold bg-purple-50 text-purple-700 border border-purple-200">
                                {{ strtoupper($employee->role ?? 'STAFF') }}
                            </span>
                            <span class="px-1.5 py-0.5 rounded text-[9.5px] font-bold {{ $employee->status === 'active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600' }}">
                                {{ ucfirst($employee->status ?? 'active') }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Contact & Bio Information List -->
                <div class="space-y-2.5 text-xs">
                    <div class="flex items-center justify-between py-1 border-b border-slate-50">
                        <span class="text-slate-500 flex items-center gap-2">
                            <i class="fas fa-envelope text-slate-400 w-3.5"></i>
                            <span>{{ __('Email Address') }}</span>
                        </span>
                        <span class="font-semibold text-slate-800 font-mono">{{ $employee->email }}</span>
                    </div>

                    <div class="flex items-center justify-between py-1 border-b border-slate-50">
                        <span class="text-slate-500 flex items-center gap-2">
                            <i class="fas fa-phone text-slate-400 w-3.5"></i>
                            <span>{{ __('Phone Number') }}</span>
                        </span>
                        <span class="font-semibold text-slate-800 font-mono">{{ $employee->phone ?: ($employee->mobile ?: 'N/A') }}</span>
                    </div>

                    <div class="flex items-center justify-between py-1 border-b border-slate-50">
                        <span class="text-slate-500 flex items-center gap-2">
                            <i class="fas fa-id-card text-slate-400 w-3.5"></i>
                            <span>{{ __('Employee Code') }}</span>
                        </span>
                        <span class="font-semibold text-purple-700 font-mono">{{ $stats['staff_id'] }}</span>
                    </div>

                    <div class="flex items-center justify-between py-1 border-b border-slate-50">
                        <span class="text-slate-500 flex items-center gap-2">
                            <i class="fas fa-map-marker-alt text-slate-400 w-3.5"></i>
                            <span>{{ __('Assigned Location') }}</span>
                        </span>
                        <span class="font-medium text-slate-700 truncate max-w-[200px] text-right">{{ $employee->address ?: 'Dhaka, Bangladesh' }}</span>
                    </div>
                </div>

            </div>
        </div>

        <!-- Right Column: Operational Assignments & Recent Attendance Logs (7 of 12) -->
        <div class="col-span-12 lg:col-span-7 space-y-3.5">
            
            <!-- Operational Details Card -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-4 space-y-3.5">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                    <h3 class="text-xs font-bold text-slate-900 flex items-center gap-2">
                        <i class="fas fa-briefcase text-purple-600 text-xs"></i>
                        <span>{{ __('Operational Scope & Duties') }}</span>
                    </h3>
                    <span class="text-[10px] font-mono text-slate-400">Tenant #{{ $tenant->id }}</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/70">
                        <span class="text-[10px] font-medium text-slate-500 block uppercase tracking-wider">{{ __('Primary Scope') }}</span>
                        <span class="text-xs font-bold text-slate-800 block mt-0.5">{{ $stats['scope'] }}</span>
                        <span class="text-[10.5px] text-slate-400 block">{{ $employee->reseller_id ? 'Partner Operations' : 'Core Direct ISP' }}</span>
                    </div>

                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/70">
                        <span class="text-[10px] font-medium text-slate-500 block uppercase tracking-wider">{{ __('Duty Shift') }}</span>
                        <span class="text-xs font-bold text-slate-800 block mt-0.5">{{ $employee->shift ?: 'Regular 8-Hour Duty' }}</span>
                        <span class="text-[10.5px] text-slate-400 block">{{ __('Operational Field / Desk') }}</span>
                    </div>

                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/70">
                        <span class="text-[10px] font-medium text-slate-500 block uppercase tracking-wider">{{ __('Official Designation') }}</span>
                        <span class="text-xs font-bold text-slate-800 block mt-0.5">{{ $employee->designation ?: 'Field Officer' }}</span>
                        <span class="text-[10.5px] text-slate-400 block">{{ __('Assigned Designation') }}</span>
                    </div>

                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/70">
                        <span class="text-[10px] font-medium text-slate-500 block uppercase tracking-wider">{{ __('Registered On') }}</span>
                        <span class="text-xs font-bold font-mono text-slate-800 block mt-0.5">{{ $employee->created_at ? $employee->created_at->format('d M Y, h:i A') : 'N/A' }}</span>
                        <span class="text-[10.5px] text-slate-400 block">{{ __('System Profile Creation') }}</span>
                    </div>
                </div>
            </div>

            <!-- Recent Attendance & Duty Register -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-200 bg-slate-50/70 flex items-center justify-between">
                    <h3 class="text-xs font-bold text-slate-900 flex items-center gap-2">
                        <i class="fas fa-clipboard-check text-purple-600 text-xs"></i>
                        <span>{{ __('Recent Daily Attendance History') }}</span>
                    </h3>
                    <span class="text-[10px] font-semibold text-slate-500">{{ __('Last 7 Records') }}</span>
                </div>

                @if($recentAttendances->isNotEmpty())
                    <table class="saas-table">
                        <thead>
                            <tr>
                                <th class="w-12 text-center">#</th>
                                <th>{{ __('Date') }}</th>
                                <th>{{ __('Punch In') }}</th>
                                <th>{{ __('Punch Out') }}</th>
                                <th class="text-center">{{ __('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentAttendances as $index => $att)
                                <tr>
                                    <td class="text-center font-mono text-slate-500">{{ $index + 1 }}</td>
                                    <td class="font-semibold text-slate-800 font-mono">{{ \Carbon\Carbon::parse($att->date)->format('d M Y') }}</td>
                                    <td class="font-mono text-slate-700">{{ $att->punch_in_at ? \Carbon\Carbon::parse($att->punch_in_at)->format('h:i A') : '—' }}</td>
                                    <td class="font-mono text-slate-700">{{ $att->punch_out_at ? \Carbon\Carbon::parse($att->punch_out_at)->format('h:i A') : '—' }}</td>
                                    <td class="text-center">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ in_array($att->status, ['present', 'field_duty']) ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : ($att->status === 'late' ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-rose-50 text-rose-700 border border-rose-200') }}">
                                            {{ ucfirst(str_replace('_', ' ', $att->status)) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="p-6 text-center text-slate-400 text-xs">
                        <i class="fas fa-calendar-times text-2xl mb-1.5 text-slate-300 block"></i>
                        <span>{{ __('No attendance records found for this employee yet.') }}</span>
                    </div>
                @endif
            </div>

        </div>

    </div>

</div>
@endsection
