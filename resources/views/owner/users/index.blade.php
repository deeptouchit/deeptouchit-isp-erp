@extends('owner.layouts.app')

@section('page-title', 'User Management & Access Control')

@push('styles')
<style>
    /* Table column alignment */
    .saas-table td.actions-col, .saas-table th.actions-col {
        width: 48px;
        text-align: center;
    }
</style>
@endpush

@section('content')
<div class="space-y-3" x-data="userManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">

    <!-- 1. Top Header Bar (Strictly Icon + Title + Action Button ONLY) -->
    <div class="flex items-center justify-between bg-white px-3.5 py-2.5 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2">
            <div class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center text-xs flex-shrink-0">
                <i class="fas fa-users-gear"></i>
            </div>
            <h1 class="text-xs sm:text-sm font-bold text-slate-800 tracking-tight">Global User Management</h1>
            <span class="px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 border border-blue-200 text-[10px] font-bold font-mono">
                {{ $totalUsers }} Users
            </span>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" @click="openCreate()" class="px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs shadow-xs transition flex items-center gap-1.5">
                <i class="fas fa-plus text-[10px]"></i>
                <span>Add User</span>
            </button>
        </div>
    </div>

    <!-- 2. KPI Summary Strip (Strictly 6 Cards) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        
        <!-- Card 1: Total Users -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="overflow-hidden">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-slate-500">Total Users</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-slate-800">{{ number_format($totalUsers) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 flex items-center justify-center bg-blue-50 text-blue-600 border-blue-100">
                <i class="fas fa-users"></i>
            </div>
        </div>

        <!-- Card 2: Super Admins -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="overflow-hidden">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-purple-700">Platform Owners</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-purple-700">{{ number_format($superAdmins) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 flex items-center justify-center bg-purple-50 text-purple-600 border-purple-100">
                <i class="fas fa-crown"></i>
            </div>
        </div>

        <!-- Card 3: ISP Admins -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="overflow-hidden">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-indigo-700">ISP Admins</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-indigo-700">{{ number_format($ispAdmins) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 flex items-center justify-center bg-indigo-50 text-indigo-600 border-indigo-100">
                <i class="fas fa-shield-halved"></i>
            </div>
        </div>

        <!-- Card 4: ISP Staff & NOC -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="overflow-hidden">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-emerald-700">Staff & Techs</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-emerald-700">{{ number_format($ispStaff) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 flex items-center justify-center bg-emerald-50 text-emerald-600 border-emerald-100">
                <i class="fas fa-user-gear"></i>
            </div>
        </div>

        <!-- Card 5: Sub-ISP Resellers -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="overflow-hidden">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-amber-700">Reseller Users</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-amber-700">{{ number_format($resellers) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 flex items-center justify-center bg-amber-50 text-amber-600 border-amber-100">
                <i class="fas fa-handshake"></i>
            </div>
        </div>

        <!-- Card 6: Active Ratio -->
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="overflow-hidden">
                <span class="text-[9px] font-medium uppercase tracking-wider block truncate text-teal-700">Active Accounts</span>
                <span class="text-[13px] font-bold font-mono leading-tight block text-teal-700">{{ number_format($activeUsers) }}</span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border flex-shrink-0 flex items-center justify-center bg-teal-50 text-teal-600 border-teal-100">
                <i class="fas fa-circle-check"></i>
            </div>
        </div>

    </div>

    <!-- 3. Search & Multi-Filter Toolbar -->
    <div class="bg-white p-2 rounded-lg border border-slate-200 shadow-xs">
        <form action="{{ route('owner.users.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-2">
            
            <!-- Search Keyword -->
            <div class="relative lg:col-span-2">
                <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name, email, phone..." 
                       class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs pl-8 pr-3 py-1.5 focus:bg-white focus:border-blue-500 focus:outline-none transition">
            </div>

            <!-- Organization / Tenant Filter -->
            <div>
                <select name="tenant_id" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 focus:bg-white focus:border-blue-500 focus:outline-none transition">
                    <option value="">All Organizations</option>
                    <option value="none" {{ request('tenant_id') === 'none' ? 'selected' : '' }}>Platform Global (No Tenant)</option>
                    @foreach($tenants as $tenant)
                        <option value="{{ $tenant->id }}" {{ request('tenant_id') == $tenant->id ? 'selected' : '' }}>
                            {{ $tenant->company_name ?: $tenant->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Role Filter -->
            <div>
                <select name="role" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 focus:bg-white focus:border-blue-500 focus:outline-none transition">
                    <option value="">All Roles</option>
                    @foreach($rolesList as $key => $label)
                        <option value="{{ $key }}" {{ request('role') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Status Filter -->
            <div>
                <select name="status" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 focus:bg-white focus:border-blue-500 focus:outline-none transition">
                    <option value="">All Statuses</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                </select>
            </div>

            <!-- Action & Per Page Buttons -->
            <div class="flex items-center gap-1.5">
                <select name="per_page" class="w-20 bg-slate-50 border border-slate-200 rounded-lg text-xs px-2 py-1.5 focus:bg-white focus:border-blue-500 focus:outline-none transition">
                    <option value="10" {{ request('per_page') == 10 ? 'selected' : '' }}>10</option>
                    <option value="20" {{ request('per_page', 20) == 20 ? 'selected' : '' }}>20</option>
                    <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                    <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                </select>

                <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs py-1.5 px-3 rounded-lg shadow-xs transition flex items-center justify-center gap-1">
                    <i class="fas fa-filter text-[10px]"></i>
                    <span>Filter</span>
                </button>

                @if(request()->hasAny(['search', 'tenant_id', 'role', 'status', 'per_page']))
                    <a href="{{ route('owner.users.index') }}" title="Reset Filters" class="bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs py-1.5 px-2.5 rounded-lg transition flex items-center justify-center">
                        <i class="fas fa-rotate-left text-[11px]"></i>
                    </a>
                @endif
            </div>

        </form>
    </div>

    <!-- 4. Master Compact Table (<table class="saas-table">) -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th class="w-10 text-center">#</th>
                        <th>Name</th>
                        <th>Email Address</th>
                        <th>Phone</th>
                        <th>Organization / Tenant</th>
                        <th>Role</th>
                        <th class="text-center">Status</th>
                        <th>Created Date</th>
                        <th class="actions-col no-sort">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $index => $u)
                        @php
                            $userJson = [
                                'id' => $u->id,
                                'name' => $u->name,
                                'email' => $u->email,
                                'phone' => $u->phone ?? '',
                                'tenant_id' => $u->tenant_id,
                                'tenant_name' => $u->tenant ? ($u->tenant->company_name ?: $u->tenant->name) : 'Platform Global',
                                'role' => $u->role,
                                'role_label' => $u->role_badge['label'] ?? ucfirst(str_replace('_', ' ', $u->role)),
                                'status' => $u->status ?? 'active',
                                'status_label' => $u->status_badge['label'] ?? ucfirst($u->status),
                                'status_class' => $u->status_badge['class'] ?? 'bg-slate-100 text-slate-700',
                                'created_at' => $u->created_at ? $u->created_at->format('d M, Y h:i A') : 'N/A',
                                'updated_at' => $u->updated_at ? $u->updated_at->format('d M, Y h:i A') : 'N/A',
                                'is_self' => $u->id === auth()->id(),
                            ];
                        @endphp
                        <tr>
                            <!-- 1. Serial -->
                            <td class="text-center font-mono text-slate-500 text-[11px]">
                                {{ $users->firstItem() + $index }}
                            </td>

                            <!-- 2. Name (Strictly single data, no concat per AGENTS.md rule) -->
                            <td class="font-semibold text-slate-900">
                                {{ $u->name }}
                            </td>

                            <!-- 3. Email Address -->
                            <td class="font-mono text-slate-700">
                                {{ $u->email }}
                            </td>

                            <!-- 4. Phone -->
                            <td class="font-mono text-slate-600">
                                {{ $u->phone ?: '—' }}
                            </td>

                            <!-- 5. Organization / Tenant -->
                            <td>
                                @if($u->tenant)
                                    <span class="text-slate-800 font-medium">{{ $u->tenant->company_name ?: $u->tenant->name }}</span>
                                @else
                                    <span class="text-slate-400 italic">Platform Global</span>
                                @endif
                            </td>

                            <!-- 6. Role -->
                            <td>
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10.5px] font-semibold border {{ $u->role_badge['class'] ?? 'bg-slate-100 text-slate-700 border-slate-200' }}">
                                    <i class="fas {{ $u->role_badge['icon'] ?? 'fa-user' }} text-[9.5px]"></i>
                                    <span>{{ $u->role_badge['label'] ?? ucfirst(str_replace('_', ' ', $u->role)) }}</span>
                                </span>
                            </td>

                            <!-- 7. Status -->
                            <td class="text-center">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $u->status_badge['class'] ?? 'bg-slate-100 text-slate-700 border-slate-200' }}">
                                    <i class="fas {{ $u->status_badge['icon'] ?? 'fa-circle' }} text-[8px]"></i>
                                    <span>{{ $u->status_badge['label'] ?? ucfirst($u->status) }}</span>
                                </span>
                            </td>

                            <!-- 8. Created Date -->
                            <td class="font-mono text-slate-500 text-[11px]">
                                {{ $u->created_at ? $u->created_at->format('d M, Y') : '—' }}
                            </td>

                            <!-- 9. Actions (Global 3-Dot Floating Action Menu ONLY) -->
                            <td class="actions-col text-center">
                                <button type="button" 
                                        @click="toggleMenu({{ json_encode($userJson) }}, $event)" 
                                        class="w-7 h-7 rounded-lg border border-slate-200 hover:border-slate-300 hover:bg-slate-100 text-slate-600 flex items-center justify-center transition"
                                        title="User Actions">
                                    <i class="fas fa-ellipsis-v text-[10px]"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-8 text-center text-slate-400">
                                <i class="fas fa-user-slash text-2xl mb-1.5 block text-slate-300"></i>
                                <span class="text-xs font-medium">No users found matching the selected filters.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Compact Pagination Footer -->
        @if($users->hasPages())
            <div class="px-3.5 py-2.5 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
                <span class="text-[11px] text-slate-500 font-mono">
                    Showing {{ $users->firstItem() }} - {{ $users->lastItem() }} of {{ $users->total() }} users
                </span>
                <div>
                    {{ $users->links() }}
                </div>
            </div>
        @endif
    </div>

    <!-- 5. Floating Action Dropdown Menu (Fixed z-50 with Positioning Algorithm) -->
    <div x-show="activeMenu" 
         x-cloak
         @click.away="activeMenu = null"
         class="fixed z-50 w-52 bg-white rounded-xl border border-slate-200/90 shadow-2xl py-1 text-xs overflow-hidden animate-in fade-in zoom-in-95 duration-100"
         :style="menuPos">

        <!-- Header: User Context -->
        <div class="px-3 py-1.5 bg-slate-50 border-b border-slate-100">
            <span class="font-bold text-slate-800 block truncate" x-text="activeMenu?.name"></span>
            <span class="text-[10px] text-slate-400 font-mono block truncate" x-text="activeMenu?.email"></span>
        </div>

        <!-- Group 1: Profile & Access -->
        <div class="py-1 border-b border-slate-100">
            <button type="button" 
                    @click="openDetails(activeMenu)" 
                    class="w-full px-3 py-1.5 text-left text-slate-700 hover:bg-slate-50 hover:text-blue-600 flex items-center gap-2 transition">
                <i class="fas fa-id-card w-4 text-slate-400 text-[11px]"></i>
                <span>View User Details</span>
            </button>

            <template x-if="!activeMenu?.is_self">
                <a :href="'{{ url('owner/users') }}/' + activeMenu?.id + '/impersonate'" 
                   class="w-full px-3 py-1.5 text-left text-slate-700 hover:bg-slate-50 hover:text-indigo-600 flex items-center gap-2 transition">
                    <i class="fas fa-right-to-bracket w-4 text-indigo-500 text-[11px]"></i>
                    <span>Login As User</span>
                </a>
            </template>
        </div>

        <!-- Group 2: Security & Configuration -->
        <div class="py-1">
            <!-- Reset Password Modal Trigger -->
            <button type="button" 
                    @click="openResetPassword(activeMenu)" 
                    class="w-full px-3 py-1.5 text-left text-amber-700 hover:bg-amber-50 flex items-center gap-2 transition">
                <i class="fas fa-key w-4 text-amber-500 text-[11px]"></i>
                <span>Reset Password</span>
            </button>

            <!-- Edit User Trigger -->
            <button type="button" 
                    @click="openEdit(activeMenu)" 
                    class="w-full px-3 py-1.5 text-left text-slate-700 hover:bg-slate-50 hover:text-blue-600 flex items-center gap-2 transition">
                <i class="fas fa-user-pen w-4 text-slate-400 text-[11px]"></i>
                <span>Edit Account</span>
            </button>

            <!-- Toggle Active/Inactive Status -->
            <template x-if="!activeMenu?.is_self">
                <form :action="'{{ url('owner/users') }}/' + activeMenu?.id + '/toggle-status'" method="POST">
                    @csrf
                    <button type="submit" class="w-full px-3 py-1.5 text-left text-slate-700 hover:bg-slate-50 flex items-center gap-2 transition"
                            :class="activeMenu?.status === 'active' ? 'text-amber-600 hover:text-amber-700' : 'text-emerald-600 hover:text-emerald-700'">
                        <i class="fas w-4 text-[11px]" :class="activeMenu?.status === 'active' ? 'fa-user-slash text-amber-500' : 'fa-user-check text-emerald-500'"></i>
                        <span x-text="activeMenu?.status === 'active' ? 'Disable Account' : 'Activate Account'"></span>
                    </button>
                </form>
            </template>

            <!-- Delete User -->
            <template x-if="!activeMenu?.is_self">
                <button type="button" 
                        @click="confirmDelete(activeMenu)" 
                        class="w-full px-3 py-1.5 text-left text-rose-600 hover:bg-rose-50 flex items-center gap-2 transition cursor-pointer">
                    <i class="fas fa-trash-can w-4 text-rose-500 text-[11px]"></i>
                    <span>Delete User</span>
                </button>
            </template>
        </div>

    </div>

    <!-- 6. Production-Grade Natural Modals -->

    <!-- Modal A: Password Reset Modal -->
    <div x-show="passwordModalOpen" 
         x-cloak 
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50">
        <div @click.away="passwordModalOpen = false" 
             class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-md overflow-hidden animate-in fade-in zoom-in-95 duration-150">
            
            <!-- Soft Natural Header -->
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-amber-50 text-amber-600 border border-amber-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-key"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">Reset User Password</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">Directly update user credential</p>
                    </div>
                </div>
                <button type="button" @click="passwordModalOpen = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <!-- Form Body -->
            <form :action="'{{ url('owner/users') }}/' + selectedUser?.id + '/reset-password'" method="POST">
                @csrf
                <div class="p-4 space-y-3">
                    
                    <!-- Selected User Info Card -->
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80 flex items-center justify-between">
                        <div>
                            <span class="text-[11px] font-bold text-slate-800 block" x-text="selectedUser?.name"></span>
                            <span class="text-[10.5px] text-slate-500 font-mono block" x-text="selectedUser?.email"></span>
                        </div>
                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold border" :class="selectedUser?.role_badge?.class ?? 'bg-slate-100 text-slate-700'" x-text="selectedUser?.role_label"></span>
                    </div>

                    <!-- Password Field -->
                    <div class="space-y-1">
                        <div class="flex items-center justify-between">
                            <label class="text-[11px] font-semibold text-slate-700">New Password</label>
                            <button type="button" @click="generatePassword()" class="text-[10.5px] text-blue-600 hover:text-blue-700 font-medium transition flex items-center gap-1">
                                <i class="fas fa-wand-magic-sparkles text-[9px]"></i>
                                <span>Generate Strong</span>
                            </button>
                        </div>
                        <div class="relative">
                            <input :type="showPass ? 'text' : 'password'" 
                                   name="password" 
                                   x-model="newPassword" 
                                   required 
                                   placeholder="Enter new password (min 6 characters)" 
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 pr-16 font-mono focus:bg-white focus:border-blue-500 focus:outline-none transition">
                            <div class="absolute right-1.5 top-1/2 -translate-y-1/2 flex items-center gap-1">
                                <button type="button" @click="showPass = !showPass" class="px-1.5 py-0.5 text-slate-400 hover:text-slate-600 transition" title="Toggle visibility">
                                    <i class="fas" :class="showPass ? 'fa-eye-slash' : 'fa-eye'"></i>
                                </button>
                                <button type="button" @click="copyPassword()" class="px-1.5 py-0.5 text-slate-400 hover:text-blue-600 transition" title="Copy to clipboard">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </div>
                        <span x-show="copied" x-cloak class="text-[10px] text-emerald-600 font-semibold flex items-center gap-1">
                            <i class="fas fa-check text-[9px]"></i> Copied password to clipboard!
                        </span>
                    </div>

                </div>

                <!-- Footer -->
                <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" @click="passwordModalOpen = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition">
                        Cancel
                    </button>
                    <button type="submit" class="bg-amber-600 hover:bg-amber-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition flex items-center gap-1.5">
                        <i class="fas fa-check text-[10px]"></i>
                        <span>Save New Password</span>
                    </button>
                </div>
            </form>

        </div>
    </div>

    <!-- Modal B: Create User Modal -->
    <div x-show="createModalOpen" 
         x-cloak 
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50">
        <div @click.away="createModalOpen = false" 
             class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden animate-in fade-in zoom-in-95 duration-150">
            
            <!-- Header -->
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">Add New System User</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">Provision account for ISP Admin, Staff, or Super Admin</p>
                    </div>
                </div>
                <button type="button" @click="createModalOpen = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <!-- Body -->
            <form action="{{ route('owner.users.store') }}" method="POST">
                @csrf
                <div class="p-4 space-y-3">
                    
                    <!-- 2 Column: Name & Email -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <div class="space-y-1">
                            <label class="text-[11px] font-semibold text-slate-700">Full Name <span class="text-rose-500">*</span></label>
                            <input type="text" name="name" required placeholder="e.g. SpeedNet Admin" 
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-blue-500 focus:outline-none transition">
                        </div>

                        <div class="space-y-1">
                            <label class="text-[11px] font-semibold text-slate-700">Email Address <span class="text-rose-500">*</span></label>
                            <input type="email" name="email" required placeholder="e.g. admin@isp.com" 
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-blue-500 focus:outline-none transition">
                        </div>
                    </div>

                    <!-- 2 Column: Phone & Organization -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <div class="space-y-1">
                            <label class="text-[11px] font-semibold text-slate-700">Phone Number</label>
                            <input type="text" name="phone" placeholder="e.g. 01800000000" 
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-blue-500 focus:outline-none transition">
                        </div>

                        <div class="space-y-1">
                            <label class="text-[11px] font-semibold text-slate-700">Assigned Organization / Tenant</label>
                            <select name="tenant_id" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 focus:bg-white focus:border-blue-500 focus:outline-none transition">
                                <option value="">Platform Global (No Specific Tenant)</option>
                                @foreach($tenants as $tenant)
                                    <option value="{{ $tenant->id }}">{{ $tenant->company_name ?: $tenant->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- 2 Column: Role & Status -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <div class="space-y-1">
                            <label class="text-[11px] font-semibold text-slate-700">System Role <span class="text-rose-500">*</span></label>
                            <select name="role" required class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 focus:bg-white focus:border-blue-500 focus:outline-none transition">
                                @foreach($rolesList as $key => $label)
                                    <option value="{{ $key }}" {{ $key === 'isp_admin' ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="space-y-1">
                            <label class="text-[11px] font-semibold text-slate-700">Account Status <span class="text-rose-500">*</span></label>
                            <select name="status" required class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 focus:bg-white focus:border-blue-500 focus:outline-none transition">
                                <option value="active" selected>Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="suspended">Suspended</option>
                            </select>
                        </div>
                    </div>

                    <!-- Password Field -->
                    <div class="space-y-1">
                        <div class="flex items-center justify-between">
                            <label class="text-[11px] font-semibold text-slate-700">Initial Password <span class="text-rose-500">*</span></label>
                            <button type="button" @click="generateCreatePassword()" class="text-[10.5px] text-blue-600 hover:text-blue-700 font-medium transition flex items-center gap-1">
                                <i class="fas fa-wand-magic-sparkles text-[9px]"></i>
                                <span>Generate Password</span>
                            </button>
                        </div>
                        <div class="relative">
                            <input :type="showCreatePass ? 'text' : 'password'" 
                                   name="password" 
                                   x-model="createPassword" 
                                   required 
                                   placeholder="Set login password (min 6 chars)" 
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 pr-10 font-mono focus:bg-white focus:border-blue-500 focus:outline-none transition">
                            <button type="button" @click="showCreatePass = !showCreatePass" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition">
                                <i class="fas" :class="showCreatePass ? 'fa-eye-slash' : 'fa-eye'"></i>
                            </button>
                        </div>
                    </div>

                </div>

                <!-- Footer -->
                <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" @click="createModalOpen = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition">
                        Cancel
                    </button>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition flex items-center gap-1.5">
                        <i class="fas fa-user-plus text-[10px]"></i>
                        <span>Create User Account</span>
                    </button>
                </div>
            </form>

        </div>
    </div>

    <!-- Modal C: Edit User Modal -->
    <div x-show="editModalOpen" 
         x-cloak 
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50">
        <div @click.away="editModalOpen = false" 
             class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden animate-in fade-in zoom-in-95 duration-150">
            
            <!-- Header -->
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-user-pen"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">Edit User Account</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal" x-text="'Updating ' + (selectedUser?.name ?? 'User')"></p>
                    </div>
                </div>
                <button type="button" @click="editModalOpen = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <!-- Body -->
            <form :action="'{{ url('owner/users') }}/' + selectedUser?.id" method="POST">
                @csrf
                @method('PUT')
                <div class="p-4 space-y-3">
                    
                    <!-- 2 Column: Name & Email -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <div class="space-y-1">
                            <label class="text-[11px] font-semibold text-slate-700">Full Name <span class="text-rose-500">*</span></label>
                            <input type="text" name="name" x-model="selectedUser.name" required 
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-blue-500 focus:outline-none transition">
                        </div>

                        <div class="space-y-1">
                            <label class="text-[11px] font-semibold text-slate-700">Email Address <span class="text-rose-500">*</span></label>
                            <input type="email" name="email" x-model="selectedUser.email" required 
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-blue-500 focus:outline-none transition">
                        </div>
                    </div>

                    <!-- 2 Column: Phone & Organization -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <div class="space-y-1">
                            <label class="text-[11px] font-semibold text-slate-700">Phone Number</label>
                            <input type="text" name="phone" x-model="selectedUser.phone" placeholder="Optional" 
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-blue-500 focus:outline-none transition">
                        </div>

                        <div class="space-y-1">
                            <label class="text-[11px] font-semibold text-slate-700">Organization / Tenant</label>
                            <select name="tenant_id" x-model="selectedUser.tenant_id" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 focus:bg-white focus:border-blue-500 focus:outline-none transition">
                                <option :value="null">Platform Global (No Specific Tenant)</option>
                                @foreach($tenants as $tenant)
                                    <option value="{{ $tenant->id }}">{{ $tenant->company_name ?: $tenant->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- 2 Column: Role & Status -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <div class="space-y-1">
                            <label class="text-[11px] font-semibold text-slate-700">System Role <span class="text-rose-500">*</span></label>
                            <select name="role" x-model="selectedUser.role" required class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 focus:bg-white focus:border-blue-500 focus:outline-none transition">
                                @foreach($rolesList as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="space-y-1">
                            <label class="text-[11px] font-semibold text-slate-700">Account Status <span class="text-rose-500">*</span></label>
                            <select name="status" x-model="selectedUser.status" required class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-2.5 py-1.5 focus:bg-white focus:border-blue-500 focus:outline-none transition">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="suspended">Suspended</option>
                            </select>
                        </div>
                    </div>

                    <!-- Optional Password Change -->
                    <div class="space-y-1 pt-1 border-t border-slate-100">
                        <label class="text-[11px] font-semibold text-slate-700">New Password <span class="text-slate-400 font-normal text-[10px]">(Leave blank to keep unchanged)</span></label>
                        <input type="password" name="password" placeholder="Enter new password to override" 
                               class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-blue-500 focus:outline-none transition">
                    </div>

                </div>

                <!-- Footer -->
                <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" @click="editModalOpen = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition">
                        Cancel
                    </button>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition flex items-center gap-1.5">
                        <i class="fas fa-check text-[10px]"></i>
                        <span>Save Changes</span>
                    </button>
                </div>
            </form>

        </div>
    </div>

    <!-- Modal D: View User Details Modal -->
    <div x-show="detailsModalOpen" 
         x-cloak 
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50">
        <div @click.away="detailsModalOpen = false" 
             class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-lg overflow-hidden animate-in fade-in zoom-in-95 duration-150">
            
            <!-- Header -->
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-id-card"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-semibold text-slate-800">User Profile Telemetry</h3>
                        <p class="text-[10.5px] text-slate-500 font-normal">Full identity and affiliation breakdown</p>
                    </div>
                </div>
                <button type="button" @click="detailsModalOpen = false" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <!-- Body Details Grid -->
            <div class="p-4 space-y-3">
                
                <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-xl border border-slate-200/80">
                    <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-sm flex-shrink-0">
                        <span x-text="selectedUser?.name ? selectedUser.name.charAt(0).toUpperCase() : 'U'"></span>
                    </div>
                    <div class="overflow-hidden">
                        <h4 class="text-xs font-bold text-slate-900 truncate" x-text="selectedUser?.name"></h4>
                        <span class="text-[11px] font-mono text-slate-500 block truncate" x-text="selectedUser?.email"></span>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80 space-y-0.5">
                        <span class="text-[9.5px] font-medium text-slate-400 uppercase tracking-wider block">Organization</span>
                        <span class="text-xs font-semibold text-slate-800 block truncate" x-text="selectedUser?.tenant_name"></span>
                    </div>

                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80 space-y-0.5">
                        <span class="text-[9.5px] font-medium text-slate-400 uppercase tracking-wider block">Role</span>
                        <span class="text-xs font-semibold text-slate-800 block truncate" x-text="selectedUser?.role_label"></span>
                    </div>

                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80 space-y-0.5">
                        <span class="text-[9.5px] font-medium text-slate-400 uppercase tracking-wider block">Phone</span>
                        <span class="text-xs font-mono text-slate-800 block truncate" x-text="selectedUser?.phone || 'Not provided'"></span>
                    </div>

                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80 space-y-0.5">
                        <span class="text-[9.5px] font-medium text-slate-400 uppercase tracking-wider block">Account Status</span>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold border" :class="selectedUser?.status_class">
                            <span x-text="selectedUser?.status_label"></span>
                        </span>
                    </div>

                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80 space-y-0.5">
                        <span class="text-[9.5px] font-medium text-slate-400 uppercase tracking-wider block">Created At</span>
                        <span class="text-[11px] font-mono text-slate-700 block" x-text="selectedUser?.created_at"></span>
                    </div>

                    <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200/80 space-y-0.5">
                        <span class="text-[9.5px] font-medium text-slate-400 uppercase tracking-wider block">Last Updated</span>
                        <span class="text-[11px] font-mono text-slate-700 block" x-text="selectedUser?.updated_at"></span>
                    </div>
                </div>

            </div>

            <!-- Footer Quick Actions -->
            <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-between">
                <button type="button" @click="detailsModalOpen = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition">
                    Close
                </button>
                <div class="flex items-center gap-2">
                    <button type="button" @click="detailsModalOpen = false; openResetPassword(selectedUser);" class="bg-amber-600 hover:bg-amber-700 text-white font-medium text-xs px-3.5 py-1.5 rounded-lg shadow-xs transition flex items-center gap-1.5">
                        <i class="fas fa-key text-[10px]"></i>
                        <span>Reset Password</span>
                    </button>
                </div>
            </div>

        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    function userManager() {
        return {
            activeMenu: null,
            menuPos: {},
            
            // Password Reset Modal State
            passwordModalOpen: false,
            newPassword: '',
            showPass: true,
            copied: false,

            // Create Modal State
            createModalOpen: false,
            createPassword: '',
            showCreatePass: false,

            // Edit Modal State
            editModalOpen: false,

            // Details Modal State
            detailsModalOpen: false,

            // Currently Selected User
            selectedUser: null,

            // 3-Dot Floating Action Menu Positioning Algorithm (Strictly per AGENTS.md rule 2.E)
            toggleMenu(item, event) {
                if (this.activeMenu?.id === item.id) {
                    this.activeMenu = null;
                    return;
                }
                this.activeMenu = item;
                const rect = event.currentTarget.getBoundingClientRect();
                const dropdownHeight = 220;
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

            // Open Password Reset Modal
            openResetPassword(user) {
                this.activeMenu = null;
                this.selectedUser = user;
                this.newPassword = this.generateRandomPassword();
                this.showPass = true;
                this.copied = false;
                this.passwordModalOpen = true;
            },

            generatePassword() {
                this.newPassword = this.generateRandomPassword();
                this.copied = false;
            },

            generateRandomPassword() {
                const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789!@#$%';
                let pass = '';
                for (let i = 0; i < 10; i++) {
                    pass += chars.charAt(Math.floor(Math.random() * chars.length));
                }
                return pass;
            },

            copyPassword() {
                if (navigator.clipboard && this.newPassword) {
                    navigator.clipboard.writeText(this.newPassword);
                    this.copied = true;
                    setTimeout(() => { this.copied = false; }, 3000);
                }
            },

            // Open Create User Modal
            openCreate() {
                this.createPassword = this.generateRandomPassword();
                this.showCreatePass = false;
                this.createModalOpen = true;
            },

            generateCreatePassword() {
                this.createPassword = this.generateRandomPassword();
                this.showCreatePass = true;
            },

            // Open Edit User Modal
            openEdit(user) {
                this.activeMenu = null;
                this.selectedUser = JSON.parse(JSON.stringify(user));
                this.editModalOpen = true;
            },

            // Open Details Modal
            openDetails(user) {
                this.activeMenu = null;
                this.selectedUser = user;
                this.detailsModalOpen = true;
            },

            // SweetAlert2 Delete Confirmation
            confirmDelete(user) {
                this.activeMenu = null;
                if (!user || user.is_self) return;

                Swal.fire({
                    title: 'Delete User Account?',
                    html: `Are you sure you want to permanently delete <strong>${user.name}</strong> (<span class="font-mono text-slate-500">${user.email}</span>)?<br><span class="text-[11px] text-rose-500 mt-1 block">This action is permanent and cannot be undone.</span>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e11d48',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: '<i class="fas fa-trash-can mr-1"></i> Yes, Delete User',
                    cancelButtonText: 'Cancel',
                    reverseButtons: true,
                    focusCancel: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = `{{ url('owner/users') }}/${user.id}`;
                        
                        const csrf = document.createElement('input');
                        csrf.type = 'hidden';
                        csrf.name = '_token';
                        csrf.value = '{{ csrf_token() }}';
                        form.appendChild(csrf);

                        const method = document.createElement('input');
                        method.type = 'hidden';
                        method.name = '_method';
                        method.value = 'DELETE';
                        form.appendChild(method);

                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            }
        };
    }
</script>
@endpush
