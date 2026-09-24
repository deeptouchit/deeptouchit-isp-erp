@extends('owner.layouts.app')

@section('page-title', 'Edit Tenant: ' . $tenant->name)

@section('content')
<div class="max-w-4xl mx-auto space-y-4">

    <!-- Top Bar -->
    <div class="flex items-center justify-between bg-white p-4 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-sm border border-blue-100 flex-shrink-0">
                {{ strtoupper(substr($tenant->name, 0, 1)) }}
            </div>
            <div>
                <h2 class="text-sm font-bold text-slate-900 tracking-tight">Edit ISP Tenant Settings</h2>
                <p class="text-[11px] text-slate-500 font-mono">{{ $tenant->slug }}.somitysoft.com • ID: #{{ $tenant->id }}</p>
            </div>
        </div>
        
        <div class="flex items-center gap-2">
            <a href="{{ route('owner.tenants.impersonate', $tenant) }}" class="px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold shadow-xs transition flex items-center gap-1.5">
                <i class="fas fa-sign-in-alt text-[10px]"></i>
                <span>Login as Admin</span>
            </a>
            <a href="{{ route('owner.tenants.index') }}" class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition flex items-center gap-1.5">
                <i class="fas fa-arrow-left text-[10px]"></i>
                <span>Back</span>
            </a>
        </div>
    </div>

    <!-- Edit Form Card -->
    <div class="rounded-xl bg-white border border-slate-200 p-5 shadow-xs">
        <form action="{{ route('owner.tenants.update', $tenant) }}" method="POST" class="space-y-5">
            @csrf
            @method('PUT')

            <!-- Section 1: ISP Organization Details -->
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-blue-600 mb-3 pb-1.5 border-b border-slate-100 flex items-center gap-1.5">
                    <i class="fas fa-building text-[11px]"></i>
                    <span>ISP Organization Profile</span>
                </span>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Organization / Brand Name *</label>
                        <input type="text" name="name" value="{{ old('name', $tenant->name) }}" required class="w-full px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs focus:ring-1 focus:ring-blue-500">
                        @error('name')<p class="text-rose-500 text-[10px] mt-0.5">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Company Legal Name *</label>
                        <input type="text" name="company_name" value="{{ old('company_name', $tenant->company_name) }}" required class="w-full px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs focus:ring-1 focus:ring-blue-500">
                        @error('company_name')<p class="text-rose-500 text-[10px] mt-0.5">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Sub-Domain Slug *</label>
                        <div class="flex items-center">
                            <input type="text" name="slug" value="{{ old('slug', $tenant->slug) }}" required class="w-full px-3 py-1.5 rounded-l-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs font-mono">
                            <span class="px-2.5 py-1.5 bg-slate-100 border border-l-0 border-slate-200 text-slate-500 text-xs rounded-r-lg font-mono">.somitysoft.com</span>
                        </div>
                        @error('slug')<p class="text-rose-500 text-[10px] mt-0.5">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Custom Domain (Optional)</label>
                        <input type="text" name="domain" value="{{ old('domain', $tenant->domain) }}" placeholder="billing.speednet.com" class="w-full px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs font-mono">
                        @error('domain')<p class="text-rose-500 text-[10px] mt-0.5">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Hotline / Phone *</label>
                        <input type="text" name="phone" value="{{ old('phone', $tenant->phone) }}" required class="w-full px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs">
                        @error('phone')<p class="text-rose-500 text-[10px] mt-0.5">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Official Email *</label>
                        <input type="email" name="email" value="{{ old('email', $tenant->email) }}" required class="w-full px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs">
                        @error('email')<p class="text-rose-500 text-[10px] mt-0.5">{{ $message }}</p>@enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Office / Billing Address</label>
                        <input type="text" name="address" value="{{ old('address', $tenant->address) }}" class="w-full px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs">
                    </div>
                </div>
            </div>

            <!-- Section 2: Subscription & Account Status -->
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-purple-600 mb-3 pb-1.5 border-b border-slate-100 flex items-center gap-1.5">
                    <i class="fas fa-layer-group text-[11px]"></i>
                    <span>SaaS Subscription & Account Status</span>
                </span>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Assigned Plan *</label>
                        <select name="saas_plan_id" required class="w-full px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs font-semibold">
                            @foreach ($plans as $plan)
                                <option value="{{ $plan->id }}" {{ old('saas_plan_id', $tenant->saas_plan_id) == $plan->id ? 'selected' : '' }}>
                                    {{ $plan->name }} ({{ $currencySymbol ?? '৳' }}{{ number_format($plan->monthly_price) }}/mo • Max {{ number_format($plan->customer_limit) }} Users)
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Account Status *</label>
                        <select name="status" required class="w-full px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs font-semibold">
                            <option value="active" {{ old('status', $tenant->status) === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="suspended" {{ old('status', $tenant->status) === 'suspended' ? 'selected' : '' }}>Suspended</option>
                            <option value="pending" {{ old('status', $tenant->status) === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="cancelled" {{ old('status', $tenant->status) === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Subscription Expiry Date</label>
                        <input type="date" name="subscription_expires_at" value="{{ old('subscription_expires_at', $tenant->subscription_expires_at ? \Carbon\Carbon::parse($tenant->subscription_expires_at)->format('Y-m-d') : '') }}" class="w-full px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs">
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Prepaid Wallet Balance ({{ $currencySymbol ?? '৳' }})</label>
                        <input type="number" step="0.01" name="wallet_balance" value="{{ old('wallet_balance', $tenant->wallet_balance) }}" class="w-full px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 text-xs font-mono">
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                <a href="{{ route('owner.tenants.index') }}" class="px-4 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-semibold transition">
                    Cancel
                </a>
                <button type="submit" class="px-4 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold shadow-xs transition flex items-center gap-1.5">
                    <i class="fas fa-save text-[10px]"></i>
                    <span>Save Changes</span>
                </button>
            </div>

        </form>
    </div>

</div>
@endsection
