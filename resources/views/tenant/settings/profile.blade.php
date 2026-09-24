@extends('tenant.layouts.app')

@section('title', 'Company Profile - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
    {{-- Page-specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" x-data="profileManager()">
    
    {{-- 1. TOP HEADER BAR (AGENTS.md Rule 2.A) --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 bg-white p-3 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center text-sm shadow-2xs flex-shrink-0">
                <i class="fas fa-building"></i>
            </div>
            <h1 class="text-sm font-bold text-slate-800 tracking-tight">Company Profile</h1>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('tenant.settings.profile.print') }}" target="_blank" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-print text-slate-500 text-xs"></i>
                <span>Print Profile</span>
            </a>
            <button type="submit" form="profileForm" class="px-4 py-1.5 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-floppy-disk text-xs"></i>
                <span>Save Changes</span>
            </button>
        </div>
    </div>

    {{-- 2. KPI SUMMARY STRIP (Strictly 6 Cards - AGENTS.md Rule 2.B) --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        {{-- Card 1: License Type --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">License Type</span>
                <span class="text-[13px] font-bold font-mono text-indigo-700 leading-tight block truncate">
                    {{ $stats['license_type'] }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-indigo-200 bg-indigo-50 text-indigo-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-certificate"></i>
            </div>
        </div>

        {{-- Card 2: BTRC Status --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">BTRC Status</span>
                <span class="text-[13px] font-bold font-mono text-emerald-600 leading-tight block">
                    {{ $stats['btrc_status'] }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-emerald-200 bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-shield-halved"></i>
            </div>
        </div>

        {{-- Card 3: Account Status --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Account Status</span>
                <span class="text-[13px] font-bold font-mono text-cyan-700 leading-tight block">
                    {{ $stats['subscription_status'] }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-cyan-200 bg-cyan-50 text-cyan-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-circle-check"></i>
            </div>
        </div>

        {{-- Card 4: Trade License Status --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Trade License</span>
                <span class="text-[13px] font-bold font-mono text-slate-800 leading-tight block">
                    {{ $stats['trade_license'] }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-slate-200 bg-slate-50 text-slate-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-file-lines"></i>
            </div>
        </div>

        {{-- Card 5: Helpline Status --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Helpline</span>
                <span class="text-[13px] font-bold font-mono text-blue-700 leading-tight block">
                    {{ $stats['support_line'] }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-blue-200 bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-headset"></i>
            </div>
        </div>

        {{-- Card 6: Package Plan --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Package Plan</span>
                <span class="text-[13px] font-bold font-mono text-purple-700 leading-tight block truncate">
                    {{ $stats['plan_name'] }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-purple-200 bg-purple-50 text-purple-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-cubes"></i>
            </div>
        </div>
    </div>

    {{-- 3. NAVIGATION TABS --}}
    <div class="flex items-center gap-1.5 border-b border-slate-200 bg-white px-3 pt-2 rounded-t-xl">
        <button type="button" 
                @click="activeTab = 'identity'"
                class="px-3.5 py-2 text-xs font-semibold rounded-t-lg transition border-b-2 cursor-pointer flex items-center gap-1.5"
                :class="activeTab === 'identity' ? 'border-cyan-600 text-cyan-700 bg-cyan-50/50' : 'border-transparent text-slate-600 hover:text-slate-900 hover:bg-slate-50'">
            <i class="fas fa-id-card text-xs"></i>
            <span>1. Company Info &amp; Logo</span>
        </button>
        <button type="button" 
                @click="activeTab = 'regulatory'"
                class="px-3.5 py-2 text-xs font-semibold rounded-t-lg transition border-b-2 cursor-pointer flex items-center gap-1.5"
                :class="activeTab === 'regulatory' ? 'border-cyan-600 text-cyan-700 bg-cyan-50/50' : 'border-transparent text-slate-600 hover:text-slate-900 hover:bg-slate-50'">
            <i class="fas fa-file-contract text-xs"></i>
            <span>2. License &amp; Legal Info</span>
        </button>
        <button type="button" 
                @click="activeTab = 'contact'"
                class="px-3.5 py-2 text-xs font-semibold rounded-t-lg transition border-b-2 cursor-pointer flex items-center gap-1.5"
                :class="activeTab === 'contact' ? 'border-cyan-600 text-cyan-700 bg-cyan-50/50' : 'border-transparent text-slate-600 hover:text-slate-900 hover:bg-slate-50'">
            <i class="fas fa-address-book text-xs"></i>
            <span>3. Contact &amp; Address</span>
        </button>
        <button type="button" 
                @click="activeTab = 'email'"
                class="px-3.5 py-2 text-xs font-semibold rounded-t-lg transition border-b-2 cursor-pointer flex items-center gap-1.5"
                :class="activeTab === 'email' ? 'border-cyan-600 text-cyan-700 bg-cyan-50/50' : 'border-transparent text-slate-600 hover:text-slate-900 hover:bg-slate-50'">
            <i class="fas fa-envelope-circle-check text-xs"></i>
            <span>4. Email Gateway (SMTP)</span>
        </button>
    </div>

    {{-- 4. PROFILE CONFIGURATION FORM --}}
    <form id="profileForm" action="{{ route('tenant.settings.profile.update') }}" method="POST" enctype="multipart/form-data" class="space-y-3">
        @csrf

        {{-- TAB 1: COMPANY INFO & LOGO --}}
        <div x-show="activeTab === 'identity'" class="bg-white p-5 rounded-b-xl border border-t-0 border-slate-200 shadow-xs space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Brand / Display Name --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Company / Brand Name *</label>
                    <input type="text" name="name" value="{{ old('name', $tenant->name) }}" required class="w-full bg-slate-50 border @error('name') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 @else border-slate-200 @enderror rounded-lg text-xs px-3 py-2 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                    <span class="text-[10px] text-slate-400 mt-0.5 block">Shown on top menu, customer portal, and SMS.</span>
                    @error('name') <span class="text-rose-500 text-[11px] mt-0.5 block">{{ $message }}</span> @enderror
                </div>

                {{-- Registered Legal Company Name --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Registered Legal Name *</label>
                    <input type="text" name="company_name" value="{{ old('company_name', $tenant->company_name) }}" required class="w-full bg-slate-50 border @error('company_name') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 @else border-slate-200 @enderror rounded-lg text-xs px-3 py-2 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                    <span class="text-[10px] text-slate-400 mt-0.5 block">Legal name printed on invoices, money receipts, and reports.</span>
                    @error('company_name') <span class="text-rose-500 text-[11px] mt-0.5 block">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Tenant Slug / Subdomain (Readonly) --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">System Subdomain / URL</label>
                    <div class="flex items-center">
                        <span class="bg-slate-100 border border-r-0 border-slate-200 text-slate-500 text-xs px-3 py-2 rounded-l-lg font-mono">https://</span>
                        <input type="text" readonly value="{{ $tenant->domain ?? ($tenant->slug . '.somitysoft.com') }}" class="w-full bg-slate-100 border border-slate-200 rounded-r-lg text-xs px-3 py-2 font-mono text-slate-600 focus:outline-none cursor-not-allowed">
                    </div>
                    <span class="text-[10px] text-slate-400 mt-0.5 block">Login link for your staff and customers.</span>
                </div>

                {{-- Official Website URL --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Website URL</label>
                    <input type="url" name="website" value="{{ old('website', $tenant->website) }}" placeholder="https://yourdomain.com" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-2 focus:bg-white focus:border-cyan-500 focus:outline-none transition font-mono">
                    <span class="text-[10px] text-slate-400 mt-0.5 block">Your company website link (optional).</span>
                </div>
            </div>

            {{-- Logos & Favicon Upload Grid --}}
            <div class="pt-2 border-t border-slate-100 grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Company Logo Upload --}}
                <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200/80 flex items-start gap-4">
                    <div class="w-16 h-16 rounded-lg bg-white border border-slate-200 flex items-center justify-center p-1.5 shadow-2xs overflow-hidden flex-shrink-0">
                        @if($tenant->logo && file_exists(public_path('storage/' . $tenant->logo)))
                            <img src="{{ asset('storage/' . $tenant->logo) }}" alt="Logo" class="max-h-full max-w-full object-contain">
                        @else
                            <div class="text-center text-slate-400">
                                <i class="fas fa-image text-xl block mb-0.5"></i>
                                <span class="text-[8px] uppercase font-bold">No Logo</span>
                            </div>
                        @endif
                    </div>
                    <div class="space-y-1.5 flex-1 min-w-0">
                        <span class="text-xs font-bold text-slate-800 block">Company Logo</span>
                        <span class="text-[10px] text-slate-500 block">Recommended PNG or WebP with transparent background (Max 2MB).</span>
                        <input type="file" name="logo" accept="image/*" class="block w-full text-[11px] text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-md file:border-0 file:text-[11px] file:font-semibold file:bg-cyan-50 file:text-cyan-700 hover:file:bg-cyan-100 cursor-pointer">
                    </div>
                </div>

                {{-- Favicon Upload --}}
                <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200/80 flex items-start gap-4">
                    <div class="w-16 h-16 rounded-lg bg-white border border-slate-200 flex items-center justify-center p-1.5 shadow-2xs overflow-hidden flex-shrink-0">
                        @if($tenant->favicon && file_exists(public_path('storage/' . $tenant->favicon)))
                            <img src="{{ asset('storage/' . $tenant->favicon) }}" alt="Favicon" class="w-8 h-8 object-contain">
                        @else
                            <div class="text-center text-slate-400">
                                <i class="fas fa-globe text-xl block mb-0.5"></i>
                                <span class="text-[8px] uppercase font-bold">Icon</span>
                            </div>
                        @endif
                    </div>
                    <div class="space-y-1.5 flex-1 min-w-0">
                        <span class="text-xs font-bold text-slate-800 block">Website Icon (Favicon)</span>
                        <span class="text-[10px] text-slate-500 block">Small square icon for browser tab (Max 1MB).</span>
                        <input type="file" name="favicon" accept="image/x-icon,image/png" class="block w-full text-[11px] text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-md file:border-0 file:text-[11px] file:font-semibold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200 cursor-pointer">
                    </div>
                </div>
            </div>
        </div>

        {{-- TAB 2: LICENSE & LEGAL INFO --}}
        <div x-show="activeTab === 'regulatory'" class="bg-white p-5 rounded-b-xl border border-t-0 border-slate-200 shadow-xs space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- BTRC License Number --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">BTRC License Number</label>
                    <input type="text" name="btrc_license_no" value="{{ old('btrc_license_no', $tenant->btrc_license_no) }}" placeholder="e.g. BTRC/LL/ISP-NW/2021-042" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-2 font-mono text-indigo-700 font-bold focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                    <span class="text-[10px] text-slate-400 mt-0.5 block">Official ISP license number issued by BTRC.</span>
                </div>

                {{-- BTRC License Category --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">License Category</label>
                    <select name="btrc_license_type" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-2 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                        <option value="Nationwide ISP" {{ old('btrc_license_type', $tenant->btrc_license_type) === 'Nationwide ISP' ? 'selected' : '' }}>Nationwide ISP (All Bangladesh)</option>
                        <option value="Divisional ISP" {{ old('btrc_license_type', $tenant->btrc_license_type) === 'Divisional ISP' ? 'selected' : '' }}>Divisional ISP</option>
                        <option value="District / Zonal ISP" {{ old('btrc_license_type', $tenant->btrc_license_type) === 'District / Zonal ISP' ? 'selected' : '' }}>District / Zonal ISP</option>
                        <option value="Category-A ISP" {{ old('btrc_license_type', $tenant->btrc_license_type) === 'Category-A ISP' ? 'selected' : '' }}>Category-A ISP</option>
                        <option value="Category-B ISP" {{ old('btrc_license_type', $tenant->btrc_license_type) === 'Category-B ISP' ? 'selected' : '' }}>Category-B ISP</option>
                        <option value="Category-C ISP" {{ old('btrc_license_type', $tenant->btrc_license_type) === 'Category-C ISP' ? 'selected' : '' }}>Category-C ISP</option>
                    </select>
                    <span class="text-[10px] text-slate-400 mt-0.5 block">Select your ISP operating license category.</span>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Trade License Number --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Trade License Number</label>
                    <input type="text" name="trade_license_no" value="{{ old('trade_license_no', $tenant->trade_license_no) }}" placeholder="e.g. TRAD/DNCC/019284/2022" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-2 font-mono focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                    <span class="text-[10px] text-slate-400 mt-0.5 block">Trade license number for commercial operations.</span>
                </div>

                {{-- TIN / BIN Tax Identification --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">TIN / BIN / VAT Number</label>
                    <input type="text" name="tin_bin_no" value="{{ old('tin_bin_no', $tenant->tin_bin_no) }}" placeholder="e.g. BIN-003918274-0102" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-2 font-mono focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                    <span class="text-[10px] text-slate-400 mt-0.5 block">Tax &amp; VAT number printed on customer invoices.</span>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2 border-t border-slate-100">
                {{-- Authorized Signatory / MD Name --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Contact Person / Owner Name</label>
                    <input type="text" name="contact_person" value="{{ old('contact_person', $tenant->contact_person) }}" placeholder="e.g. Salzar Rahman" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-2 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                    <span class="text-[10px] text-slate-400 mt-0.5 block">Name of the business owner or managing director.</span>
                </div>

                {{-- Signatory Designation --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Designation / Title</label>
                    <input type="text" name="contact_person_designation" value="{{ old('contact_person_designation', $tenant->contact_person_designation) }}" placeholder="e.g. Managing Director, Proprietor" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-2 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                    <span class="text-[10px] text-slate-400 mt-0.5 block">Official designation printed on documents.</span>
                </div>
            </div>
        </div>

        {{-- TAB 3: CONTACT & ADDRESS --}}
        <div x-show="activeTab === 'contact'" class="bg-white p-5 rounded-b-xl border border-t-0 border-slate-200 shadow-xs space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Primary Contact Phone --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Primary Phone Number *</label>
                    <input type="text" name="phone" value="{{ old('phone', $tenant->phone) }}" required class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-2 font-mono text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                    <span class="text-[10px] text-slate-400 mt-0.5 block">Main contact number for general queries.</span>
                </div>

                {{-- Primary Official Email --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Primary Email Address *</label>
                    <input type="email" name="email" value="{{ old('email', $tenant->email) }}" required class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-2 font-mono text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                    <span class="text-[10px] text-slate-400 mt-0.5 block">Main email for system notifications and alerts.</span>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                {{-- 24/7 Support Hotline --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Helpline / Support Number</label>
                    <input type="text" name="support_hotline" value="{{ old('support_hotline', $tenant->support_hotline) }}" placeholder="e.g. 09611-889900" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-2 font-mono text-cyan-800 font-bold focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                    <span class="text-[10px] text-slate-400 mt-0.5 block">Printed on customer receipts and bills.</span>
                </div>

                {{-- Billing Department Phone --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Billing Phone Number</label>
                    <input type="text" name="billing_phone" value="{{ old('billing_phone', $tenant->billing_phone) }}" placeholder="e.g. 01819-001133" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-2 font-mono focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                    <span class="text-[10px] text-slate-400 mt-0.5 block">Contact line for billing and payment questions.</span>
                </div>

                {{-- Billing Department Email --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Billing Email Address</label>
                    <input type="email" name="billing_email" value="{{ old('billing_email', $tenant->billing_email) }}" placeholder="e.g. billing@yourdomain.com" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-2 font-mono focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                    <span class="text-[10px] text-slate-400 mt-0.5 block">Email address for sending customer invoices.</span>
                </div>
            </div>

            {{-- Full Corporate Physical Address --}}
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Office Address *</label>
                <textarea name="address" required rows="2" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-2 focus:bg-white focus:border-cyan-500 focus:outline-none transition">{{ old('address', $tenant->address) }}</textarea>
                <span class="text-[10px] text-slate-400 mt-0.5 block">Full office address printed on customer invoices and receipts.</span>
            </div>
        </div>

        {{-- TAB 4: EMAIL GATEWAY (SMTP) --}}
        <div x-show="activeTab === 'email'" class="bg-white p-5 rounded-b-xl border border-t-0 border-slate-200 shadow-xs space-y-4">
            {{-- Header & Enable Toggle --}}
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-cyan-600"></span>
                    <div>
                        <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Outbound Email Gateway (SMTP)</h3>
                        <p class="text-[11px] text-slate-500">Configure your email server for automated backups, invoice notifications, and alerts.</p>
                    </div>
                </div>
                <label class="inline-flex items-center gap-2 cursor-pointer bg-slate-50 hover:bg-slate-100 px-3 py-1.5 rounded-lg border border-slate-200 transition">
                    <input type="checkbox" name="mail_enabled" value="1" {{ old('mail_enabled', $tenant->mail_enabled) ? 'checked' : '' }} class="w-4 h-4 rounded text-cyan-600 focus:ring-cyan-500 cursor-pointer">
                    <span class="text-xs font-bold text-slate-700">Enable Email Gateway</span>
                </label>
            </div>

            {{-- SMTP Host & Port --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">SMTP Host Server</label>
                    <input type="text" name="mail_host" x-model="smtpConfig.host" value="{{ old('mail_host', $tenant->mail_host) }}" placeholder="e.g. smtp.gmail.com or mail.yourdomain.com" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-2 font-mono text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                    <span class="text-[10px] text-slate-400 mt-0.5 block">Your mail server hostname.</span>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">SMTP Port</label>
                    <input type="number" name="mail_port" x-model="smtpConfig.port" value="{{ old('mail_port', $tenant->mail_port ?? 587) }}" placeholder="587" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-2 font-mono text-slate-800 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                    <span class="text-[10px] text-slate-400 mt-0.5 block">Common: 587 (TLS), 465 (SSL), 25</span>
                </div>
            </div>

            {{-- Encryption & Auth --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Encryption Protocol</label>
                    <select name="mail_encryption" x-model="smtpConfig.encryption" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-2 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                        <option value="tls" {{ old('mail_encryption', $tenant->mail_encryption) === 'tls' ? 'selected' : '' }}>TLS (Recommended - Port 587)</option>
                        <option value="ssl" {{ old('mail_encryption', $tenant->mail_encryption) === 'ssl' ? 'selected' : '' }}>SSL (Port 465)</option>
                        <option value="none" {{ old('mail_encryption', $tenant->mail_encryption) === 'none' ? 'selected' : '' }}>None / Plain (Port 25)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">SMTP Username / Email</label>
                    <input type="text" name="mail_username" x-model="smtpConfig.username" value="{{ old('mail_username', $tenant->mail_username) }}" placeholder="e.g. billing@yourdomain.com" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-2 font-mono focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">SMTP Password</label>
                    <div class="relative">
                        <input :type="showPassword ? 'text' : 'password'" name="mail_password" x-model="smtpConfig.password" placeholder="{{ $tenant->mail_password ? '••••••••••••' : 'Enter SMTP password' }}" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs pl-3 pr-8 py-2 font-mono focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                        <button type="button" @click="showPassword = !showPassword" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                            <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'" class="text-xs"></i>
                        </button>
                    </div>
                </div>
            </div>

            {{-- From Sender Header --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2 border-t border-slate-100">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">From Sender Email Address</label>
                    <input type="email" name="mail_from_address" x-model="smtpConfig.fromAddress" value="{{ old('mail_from_address', $tenant->mail_from_address ?? $tenant->email) }}" placeholder="e.g. noreply@yourdomain.com" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-2 font-mono focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                    <span class="text-[10px] text-slate-400 mt-0.5 block">Email address visible in the 'From' field of received emails.</span>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">From Sender Name</label>
                    <input type="text" name="mail_from_name" x-model="smtpConfig.fromName" value="{{ old('mail_from_name', $tenant->mail_from_name ?? $tenant->company_name) }}" placeholder="e.g. SpeedNet Billing Department" class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-2 focus:bg-white focus:border-cyan-500 focus:outline-none transition">
                    <span class="text-[10px] text-slate-400 mt-0.5 block">Display name visible to recipients.</span>
                </div>
            </div>

            {{-- SMTP Live Test Box --}}
            <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200/80 flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-md bg-blue-50 text-blue-600 flex items-center justify-center text-xs">
                        <i class="fas fa-paper-plane"></i>
                    </div>
                    <div>
                        <span class="text-xs font-bold text-slate-800 block">Test SMTP Connection</span>
                        <span class="text-[10px] text-slate-500 block">Send a live test email to verify your SMTP server setup.</span>
                    </div>
                </div>

                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <input type="email" x-model="testRecipientEmail" placeholder="Recipient email address" class="w-full sm:w-64 bg-white border border-slate-200 rounded-lg text-xs px-3 py-1.5 font-mono focus:border-cyan-500 focus:outline-none">
                    <button type="button" @click="testSmtpConnection()" :disabled="testingSmtp" class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer flex-shrink-0 disabled:opacity-50">
                        <i :class="testingSmtp ? 'fas fa-spinner fa-spin' : 'fas fa-vial'" class="text-xs"></i>
                        <span x-text="testingSmtp ? 'Testing...' : 'Send Test Email'"></span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Centered Submit Action Bar (AGENTS.md Rule 4) --}}
        <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-xs flex items-center justify-between">
            <span class="text-xs text-slate-500">All updates will immediately apply to your invoices, receipts, automated backups, and customer portal.</span>
            <button type="submit" class="px-5 py-2 bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs rounded-lg shadow-xs transition flex items-center gap-2 cursor-pointer">
                <i class="fas fa-floppy-disk text-xs"></i>
                <span>Save Changes</span>
            </button>
        </div>
    </form>

</div>
@endsection

@push('scripts')
<script>
    function profileManager() {
        return {
            activeTab: 'identity',
            showPassword: false,
            testingSmtp: false,
            testRecipientEmail: '{{ $tenant->billing_email ?: $tenant->email }}',
            smtpConfig: {
                host: '{{ $tenant->mail_host }}',
                port: '{{ $tenant->mail_port ?? 587 }}',
                encryption: '{{ $tenant->mail_encryption ?? 'tls' }}',
                username: '{{ $tenant->mail_username }}',
                password: '',
                fromAddress: '{{ $tenant->mail_from_address ?? $tenant->email }}',
                fromName: '{{ $tenant->mail_from_name ?? $tenant->company_name }}'
            },

            testSmtpConnection() {
                if (!this.testRecipientEmail) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Email Required',
                        text: 'Please enter a recipient email address to receive the test email.',
                        confirmButtonColor: '#0891b2'
                    });
                    return;
                }

                this.testingSmtp = true;

                fetch('{{ route('tenant.settings.profile.test-email') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        test_email: this.testRecipientEmail,
                        mail_host: this.smtpConfig.host,
                        mail_port: this.smtpConfig.port,
                        mail_encryption: this.smtpConfig.encryption,
                        mail_username: this.smtpConfig.username,
                        mail_password: this.smtpConfig.password,
                        mail_from_address: this.smtpConfig.fromAddress,
                        mail_from_name: this.smtpConfig.fromName
                    })
                })
                .then(res => res.json())
                .then(data => {
                    this.testingSmtp = false;
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Test Email Sent!',
                            text: data.message,
                            confirmButtonColor: '#0891b2'
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Connection Failed',
                            text: data.message,
                            confirmButtonColor: '#e11d48'
                        });
                    }
                })
                .catch(err => {
                    this.testingSmtp = false;
                    Swal.fire({
                        icon: 'error',
                        title: 'Request Failed',
                        text: err.message || 'Unable to connect to the server.',
                        confirmButtonColor: '#e11d48'
                    });
                });
            }
        };
    }
</script>
@endpush
