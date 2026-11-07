@extends('tenant.layouts.app')

@section('title', 'Create Custom Role - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
    {{-- Page-specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" x-data="createRoleManager()">
    
    {{-- 1. TOP HEADER BAR (Strict AGENTS.md Rule 2.A: Title + Icon + Action Buttons ONLY. No subtitles/p tags) --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 bg-white p-3 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-cyan-50 text-cyan-600 border border-cyan-100 flex items-center justify-center text-sm shadow-2xs flex-shrink-0">
                <i class="fas fa-user-plus"></i>
            </div>
            <h1 class="text-sm font-bold text-slate-800 tracking-tight">Create Custom Role</h1>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('tenant.settings.rbac') }}" class="h-[34px] py-0 px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-arrow-left text-slate-500 text-xs"></i>
                <span>Back to Roles List</span>
            </a>
            <button type="submit" form="createRoleForm" class="h-[34px] py-0 px-4 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-check text-xs"></i>
                <span>Save &amp; Create Role</span>
            </button>
        </div>
    </div>

    {{-- Main Form Container --}}
    <form id="createRoleForm" action="{{ route('tenant.settings.rbac.store') }}" method="POST" class="space-y-3">
        @csrf

        {{-- 2. BASIC ROLE SPECIFICATIONS CARD --}}
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs space-y-3">
            <div class="flex items-center gap-2 pb-2 border-b border-slate-100">
                <span class="w-2 h-2 rounded-full bg-cyan-600"></span>
                <h2 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Role Profile &amp; Basic Configuration</h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
                {{-- Role Title --}}
                <div class="sm:col-span-2">
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">
                        Role Title / Display Name <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" 
                           name="display_name" 
                           required 
                           value="{{ old('display_name') }}"
                           placeholder="e.g. Senior Network Engineer, Front Desk Officer..." 
                           class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                    @error('display_name')
                        <span class="text-[10px] text-rose-500 font-medium mt-0.5 block">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Badge Color Theme --}}
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Badge Color Theme</label>
                    <select name="color" class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="purple" {{ old('color') == 'purple' ? 'selected' : '' }}>Purple Theme</option>
                        <option value="cyan" {{ old('color') == 'cyan' || !old('color') ? 'selected' : '' }}>Cyan Theme</option>
                        <option value="blue" {{ old('color') == 'blue' ? 'selected' : '' }}>Blue Theme</option>
                        <option value="indigo" {{ old('color') == 'indigo' ? 'selected' : '' }}>Indigo Theme</option>
                        <option value="emerald" {{ old('color') == 'emerald' ? 'selected' : '' }}>Emerald Theme</option>
                        <option value="amber" {{ old('color') == 'amber' ? 'selected' : '' }}>Amber Theme</option>
                        <option value="rose" {{ old('color') == 'rose' ? 'selected' : '' }}>Rose Theme</option>
                        <option value="sky" {{ old('color') == 'sky' ? 'selected' : '' }}>Sky Theme</option>
                        <option value="slate" {{ old('color') == 'slate' ? 'selected' : '' }}>Slate Neutral</option>
                    </select>
                </div>

                {{-- Initial Status --}}
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Initial Status</label>
                    <select name="status" class="w-full h-[34px] px-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                        <option value="active" {{ old('status') == 'active' || !old('status') ? 'selected' : '' }}>Active (Usable)</option>
                        <option value="disabled" {{ old('status') == 'disabled' ? 'selected' : '' }}>Disabled (Draft)</option>
                    </select>
                </div>

                {{-- Role Description --}}
                <div class="sm:col-span-2 md:col-span-4">
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Role Description &amp; Scope</label>
                    <input type="text" 
                           name="description" 
                           value="{{ old('description') }}"
                           placeholder="Describe the operational responsibilities and authority scope of this position..." 
                           class="w-full h-[34px] px-3 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden transition">
                </div>
            </div>
        </div>

        {{-- 3. GRANULAR CAPABILITIES & PERMISSION MATRIX CARD --}}
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs space-y-3">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-2.5 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-indigo-600"></span>
                    <div>
                        <h2 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Granular Capabilities &amp; Access Rights</h2>
                        <span class="text-[10.5px] text-slate-500 font-medium">Selected: <strong class="text-cyan-700 font-mono" x-text="selectedPermissions.length"></strong> of {{ $totalPermissions }} available capabilities</span>
                    </div>
                </div>

                {{-- Quick Matrix Actions --}}
                <div class="flex items-center gap-1.5">
                    <button type="button" @click="selectAll()" class="h-[28px] py-0 px-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded text-xs font-semibold border border-slate-200/80 transition cursor-pointer">
                        <i class="fas fa-check-double text-[10px]"></i>
                        <span>Select All</span>
                    </button>
                    <button type="button" @click="clearAll()" class="h-[28px] py-0 px-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded text-xs font-semibold border border-slate-200/80 transition cursor-pointer">
                        <i class="fas fa-xmark text-[10px]"></i>
                        <span>Clear All</span>
                    </button>
                </div>
            </div>

            {{-- Permissions Group Grid --}}
            <div class="space-y-3 pt-1">
                @foreach($permissionCatalog as $groupTitle => $group)
                    @php
                        $groupKeys = array_keys($group['permissions']);
                    @endphp
                    <div class="border border-slate-200/90 rounded-lg overflow-hidden bg-slate-50/30">
                        {{-- Group Header --}}
                        <div class="bg-slate-100/80 px-3 py-2 flex items-center justify-between border-b border-slate-200/80">
                            <div class="flex items-center gap-2">
                                <i class="fas {{ $group['icon'] }} text-slate-600 text-xs w-4 text-center"></i>
                                <span class="text-xs font-bold text-slate-800">{{ $groupTitle }}</span>
                                <span class="text-[10px] text-slate-500 font-mono font-medium">({{ count($group['permissions']) }} Capabilities)</span>
                            </div>
                            <button type="button" 
                                    @click="toggleGroup(@js($groupKeys))" 
                                    class="text-[10.5px] text-cyan-700 hover:text-cyan-800 font-semibold cursor-pointer">
                                Toggle Group
                            </button>
                        </div>

                        {{-- Group Permissions Items --}}
                        <div class="p-3 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                            @foreach($group['permissions'] as $permKey => $permLabel)
                                <label class="flex items-start gap-2.5 p-2 rounded-lg bg-white border border-slate-200/70 hover:border-cyan-300 hover:shadow-2xs transition cursor-pointer"
                                       :class="selectedPermissions.includes('{{ $permKey }}') ? 'border-cyan-300 bg-cyan-50/20' : ''">
                                    <input type="checkbox" 
                                           name="permissions[]" 
                                           value="{{ $permKey }}" 
                                           x-model="selectedPermissions"
                                           class="mt-0.5 rounded border-slate-300 text-cyan-600 focus:ring-cyan-500 w-3.5 h-3.5">
                                    <div class="min-w-0">
                                        <span class="font-medium text-slate-800 block text-[11px] leading-tight">{{ $permLabel }}</span>
                                        <span class="font-mono text-[9.5px] text-slate-400 block mt-0.5">{{ $permKey }}</span>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Form Action Footer --}}
        <div class="p-3 bg-white rounded-xl border border-slate-200 shadow-xs flex items-center justify-between">
            <a href="{{ route('tenant.settings.rbac') }}" class="h-[34px] py-0 px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-lg border border-slate-200/80 transition flex items-center justify-center cursor-pointer">
                Cancel
            </a>
            <button type="submit" class="h-[34px] py-0 px-5 bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                <i class="fas fa-check text-xs"></i>
                <span>Save &amp; Create Role</span>
            </button>
        </div>
    </form>

</div>
@endsection

@push('scripts')
<script>
    function createRoleManager() {
        return {
            selectedPermissions: [
                'customers.view',
                'support.tickets'
            ],

            allAvailablePermissions: [
                @foreach($permissionCatalog as $group)
                    @foreach($group['permissions'] as $pKey => $pLabel)
                        '{{ $pKey }}',
                    @endforeach
                @endforeach
            ],

            selectAll() {
                this.selectedPermissions = [...this.allAvailablePermissions];
            },

            clearAll() {
                this.selectedPermissions = [];
            },

            toggleGroup(groupKeys) {
                const allSelected = groupKeys.every(k => this.selectedPermissions.includes(k));
                if (allSelected) {
                    this.selectedPermissions = this.selectedPermissions.filter(k => !groupKeys.includes(k));
                } else {
                    const toAdd = groupKeys.filter(k => !this.selectedPermissions.includes(k));
                    this.selectedPermissions.push(...toAdd);
                }
            }
        };
    }
</script>
@endpush
