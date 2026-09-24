@extends('owner.layouts.app')

@section('page-title', 'Provision New ISP Tenant')

@section('content')
<div class="max-w-4xl mx-auto space-y-4">

    <!-- Top Navigation Header -->
    <div class="flex items-center justify-between bg-white p-4 rounded-xl border border-slate-200 shadow-xs">
        <div>
            <h2 class="text-sm font-bold text-slate-900 tracking-tight">Add New ISP Organization</h2>
            <p class="text-[11px] text-slate-500">Provision a multi-tenant workspace, assign SaaS subscription plan, and create root ISP admin credentials</p>
        </div>
        <a href="{{ route('owner.tenants.index') }}" class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition flex items-center gap-1.5">
            <i class="fas fa-arrow-left text-[10px]"></i>
            <span>Back to List</span>
        </a>
    </div>

    <!-- Create Tenant Form Card -->
    <div class="rounded-xl bg-white border border-slate-200 p-5 shadow-xs">
        <form action="{{ route('owner.tenants.store') }}" method="POST" class="space-y-5">
            @csrf

            <!-- Section 1: ISP Company Details -->
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-blue-600 mb-3 pb-1.5 border-b border-slate-100 flex items-center gap-1.5">
                    <i class="fas fa-building text-[11px]"></i>
                    <span>ISP Organization Profile</span>
                </span>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Organization / Brand Name *</label>
                        <input type="text" name="name" value="{{ old('name') }}" required placeholder="e.g. SpeedNet ISP" class="w-full px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs focus:ring-1 focus:ring-blue-500 transition">
                        @error('name')<p class="text-rose-500 text-[10px] mt-0.5">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Full Company Legal Name *</label>
                        <input type="text" name="company_name" value="{{ old('company_name') }}" required placeholder="e.g. SpeedNet Broadband Ltd." class="w-full px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs focus:ring-1 focus:ring-blue-500 transition">
                        @error('company_name')<p class="text-rose-500 text-[10px] mt-0.5">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Sub-Domain Slug *</label>
                        <div class="flex items-center">
                            <input type="text" name="slug" value="{{ old('slug') }}" required placeholder="speednet" class="w-full px-3 py-1.5 rounded-l-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs font-mono">
                            <span class="px-2.5 py-1.5 bg-slate-100 border border-l-0 border-slate-200 text-slate-500 text-xs rounded-r-lg font-mono">.somitysoft.com</span>
                        </div>
                        @error('slug')<p class="text-rose-500 text-[10px] mt-0.5">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Custom Domain (Optional)</label>
                        <input type="text" name="domain" value="{{ old('domain') }}" placeholder="billing.speednet.com" class="w-full px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs font-mono">
                        @error('domain')<p class="text-rose-500 text-[10px] mt-0.5">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Hotline / Phone Number *</label>
                        <input type="text" name="phone" value="{{ old('phone') }}" required placeholder="017XXXXXXXX" class="w-full px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs">
                        @error('phone')<p class="text-rose-500 text-[10px] mt-0.5">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Official Email Address *</label>
                        <input type="email" name="email" value="{{ old('email') }}" required placeholder="billing@speednet.com" class="w-full px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs">
                        @error('email')<p class="text-rose-500 text-[10px] mt-0.5">{{ $message }}</p>@enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Office / Billing Address</label>
                        <input type="text" name="address" value="{{ old('address') }}" placeholder="House #, Road #, City..." class="w-full px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs">
                    </div>
                </div>
            </div>

            <!-- Section 2: Subscription Plan & Validity -->
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-purple-600 mb-3 pb-1.5 border-b border-slate-100 flex items-center gap-1.5">
                    <i class="fas fa-layer-group text-[11px]"></i>
                    <span>SaaS Subscription Plan & Initial Validity</span>
                </span>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
                    <div class="sm:col-span-2">
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Select SaaS Subscription Plan *</label>
                        <select name="saas_plan_id" required class="w-full px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs font-semibold">
                            <option value="">Choose a plan...</option>
                            @foreach ($plans as $plan)
                                <option value="{{ $plan->id }}" {{ old('saas_plan_id') == $plan->id ? 'selected' : '' }}>
                                    {{ $plan->name }} ({{ $currencySymbol ?? '৳' }}{{ number_format($plan->monthly_price) }}/mo • Max {{ number_format($plan->customer_limit) }} Customers)
                                </option>
                            @endforeach
                        </select>
                        @error('saas_plan_id')<p class="text-rose-500 text-[10px] mt-0.5">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Initial Validity Period</label>
                        <select name="duration_months" class="w-full px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs font-semibold">
                            <option value="trial" {{ old('duration_months') === 'trial' ? 'selected' : '' }}>Free Trial ({{ $globalSettings['default_trial_days'] ?? 14 }} Days)</option>
                            <option value="1" {{ old('duration_months', '1') === '1' ? 'selected' : '' }}>1 Month (Standard)</option>
                            <option value="3" {{ old('duration_months') === '3' ? 'selected' : '' }}>3 Months</option>
                            <option value="6" {{ old('duration_months') === '6' ? 'selected' : '' }}>6 Months</option>
                            <option value="12" {{ old('duration_months') === '12' ? 'selected' : '' }}>1 Year (12 Months)</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Section 3: Primary ISP Admin Credentials -->
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-600 mb-3 pb-1.5 border-b border-slate-100 flex items-center gap-1.5">
                    <i class="fas fa-user-shield text-[11px]"></i>
                    <span>Primary ISP Administrator Account</span>
                </span>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Admin Full Name *</label>
                        <input type="text" name="admin_name" value="{{ old('admin_name') }}" required placeholder="e.g. Tanvir Ahmed" class="w-full px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs">
                        @error('admin_name')<p class="text-rose-500 text-[10px] mt-0.5">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Login Email Address *</label>
                        <input type="email" name="admin_email" value="{{ old('admin_email') }}" required placeholder="admin@speednet.com" class="w-full px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs">
                        @error('admin_email')<p class="text-rose-500 text-[10px] mt-0.5">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Secure Password *</label>
                        <input type="password" name="admin_password" required placeholder="Minimum 6 characters" class="w-full px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs">
                        @error('admin_password')<p class="text-rose-500 text-[10px] mt-0.5">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            <!-- Form Submit Actions -->
            <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                <a href="{{ route('owner.tenants.index') }}" class="px-4 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-semibold transition">
                    Cancel
                </a>
                <button type="submit" class="px-4 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold shadow-xs transition flex items-center gap-1.5">
                    <i class="fas fa-check-circle text-[10px]"></i>
                    <span>Provision Tenant Account</span>
                </button>
            </div>

        </form>
    </div>

</div>
@endsection
