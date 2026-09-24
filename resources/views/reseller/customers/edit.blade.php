@extends('reseller.layouts.app')

@section('title', 'Edit Customer - ' . $customer->name)

@section('content')
<div class="max-w-4xl mx-auto space-y-4">
    
    {{-- 1. Top Header Bar (Rule 4: Back button + Title ONLY) --}}
    <div class="flex items-center justify-between bg-white p-3 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <a href="{{ route('reseller.customers.index') }}" class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center transition border border-slate-200/80 cursor-pointer">
                <i class="fas fa-arrow-left text-xs"></i>
            </a>
            <h1 class="text-sm font-bold text-slate-800 tracking-tight">{{ __('Edit Customer') }}</h1>
        </div>
        <div class="px-2.5 py-1 bg-cyan-50 border border-cyan-200 rounded-lg text-xs font-mono font-bold text-cyan-800">
            ID: {{ $customer->customer_id }}
        </div>
    </div>

    {{-- Form Container --}}
    <form action="{{ route('reseller.customers.update', $customer->id) }}" method="POST" class="space-y-4">
        @csrf
        @method('PUT')

        {{-- Section 1: Basic & Contact Information --}}
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs space-y-4">
            <div class="flex items-center gap-2 pb-2 border-b border-slate-100">
                <i class="fas fa-user text-cyan-600 text-xs"></i>
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700">{{ __('Company Information') }}</h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                
                <!-- Full Name -->
                <div class="sm:col-span-2">
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">{{ __('Customer Name') }} *</label>
                    <input type="text" 
                           name="name" 
                           value="{{ old('name', $customer->name) }}" 
                           required 
                           class="w-full px-3 py-1.5 rounded-lg text-xs @error('name') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border border-slate-200 text-slate-800 @enderror focus:bg-white focus:outline-none focus:border-cyan-500 shadow-2xs">
                    @error('name') <span class="text-[10px] text-rose-600 mt-0.5 block">{{ $message }}</span> @enderror
                </div>

                <!-- Primary Phone -->
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">{{ __('Phone Number') }} *</label>
                    <input type="text" 
                           name="phone" 
                           value="{{ old('phone', $customer->phone) }}" 
                           required 
                           class="w-full px-3 py-1.5 rounded-lg text-xs font-mono @error('phone') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border border-slate-200 text-slate-800 @enderror focus:bg-white focus:outline-none focus:border-cyan-500 shadow-2xs">
                    @error('phone') <span class="text-[10px] text-rose-600 mt-0.5 block">{{ $message }}</span> @enderror
                </div>

                <!-- Alt Phone -->
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">{{ __('Alternative Phone') }}</label>
                    <input type="text" 
                           name="alt_phone" 
                           value="{{ old('alt_phone', $customer->alt_phone) }}" 
                           class="w-full px-3 py-1.5 rounded-lg text-xs font-mono bg-slate-50 border border-slate-200 text-slate-800 focus:bg-white focus:outline-none focus:border-cyan-500 shadow-2xs">
                </div>

                <!-- Email Address -->
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">{{ __('Email') }}</label>
                    <input type="email" 
                           name="email" 
                           value="{{ old('email', $customer->email) }}" 
                           class="w-full px-3 py-1.5 rounded-lg text-xs bg-slate-50 border border-slate-200 text-slate-800 focus:bg-white focus:outline-none focus:border-cyan-500 shadow-2xs">
                </div>

                <!-- National ID (NID) -->
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">{{ __('NID Number') }}</label>
                    <input type="text" 
                           name="national_id" 
                           value="{{ old('national_id', $customer->national_id) }}" 
                           class="w-full px-3 py-1.5 rounded-lg text-xs font-mono bg-slate-50 border border-slate-200 text-slate-800 focus:bg-white focus:outline-none focus:border-cyan-500 shadow-2xs">
                </div>

                <!-- Zone / Area -->
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">{{ __('Zone') }}</label>
                    <input type="text" 
                           name="zone" 
                           list="zoneSuggestions"
                           value="{{ old('zone', $customer->zone) }}" 
                           class="w-full px-3 py-1.5 rounded-lg text-xs bg-slate-50 border border-slate-200 text-slate-800 focus:bg-white focus:outline-none focus:border-cyan-500 shadow-2xs">
                    <datalist id="zoneSuggestions">
                        @foreach($zones as $z)
                            <option value="{{ $z }}">
                        @endforeach
                    </datalist>
                </div>

                <!-- Complete Address -->
                <div class="sm:col-span-2">
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">{{ __('Installation Address') }}</label>
                    <input type="text" 
                           name="address" 
                           value="{{ old('address', $customer->address) }}" 
                           class="w-full px-3 py-1.5 rounded-lg text-xs bg-slate-50 border border-slate-200 text-slate-800 focus:bg-white focus:outline-none focus:border-cyan-500 shadow-2xs">
                </div>

            </div>
        </div>

        {{-- Section 2: PPPoE & Internet Package --}}
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs space-y-4">
            <div class="flex items-center gap-2 pb-2 border-b border-slate-100">
                <i class="fas fa-network-wired text-cyan-600 text-xs"></i>
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700">{{ __('Packages & Rates') }}</h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                
                <!-- PPPoE Username -->
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">{{ __('PPPoE Username') }} *</label>
                    <input type="text" 
                           name="username" 
                           value="{{ old('username', $customer->username) }}" 
                           required 
                           class="w-full px-3 py-1.5 rounded-lg text-xs font-mono font-semibold @error('username') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border border-slate-200 text-slate-800 @enderror focus:bg-white focus:outline-none focus:border-cyan-500 shadow-2xs">
                    @error('username') <span class="text-[10px] text-rose-600 mt-0.5 block">{{ $message }}</span> @enderror
                </div>

                <!-- PPPoE Password -->
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">{{ __('PPPoE Password') }} *</label>
                    <input type="text" 
                           name="password" 
                           value="{{ old('password', $customer->password) }}" 
                           required 
                           class="w-full px-3 py-1.5 rounded-lg text-xs font-mono font-semibold @error('password') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border border-slate-200 text-slate-800 @enderror focus:bg-white focus:outline-none focus:border-cyan-500 shadow-2xs">
                    @error('password') <span class="text-[10px] text-rose-600 mt-0.5 block">{{ $message }}</span> @enderror
                </div>

                <!-- Internet Package -->
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">{{ __('Select Package') }} *</label>
                    <select name="package_id" 
                            id="packageSelect" 
                            required 
                            class="w-full px-2.5 py-1.5 rounded-lg text-xs @error('package_id') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border border-slate-200 text-slate-800 @enderror focus:bg-white focus:outline-none focus:border-cyan-500 shadow-2xs">
                        @foreach($packages as $p)
                            <option value="{{ $p->id }}" data-price="{{ $p->price }}" {{ old('package_id', $customer->package_id) == $p->id ? 'selected' : '' }}>
                                {{ $p->mikrotik_profile ?? $p->name }} (@currency($p->price))
                            </option>
                        @endforeach
                    </select>
                    @error('package_id') <span class="text-[10px] text-rose-600 mt-0.5 block">{{ $message }}</span> @enderror
                </div>

                <!-- Monthly Bill -->
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">{{ __('Monthly Fee') }} ({{ $currencySymbol ?? '৳' }}) *</label>
                    <input type="number" 
                           step="0.01" 
                           name="monthly_bill" 
                           id="monthlyBillInput"
                           value="{{ old('monthly_bill', $customer->monthly_bill) }}" 
                           required
                           class="w-full px-3 py-1.5 rounded-lg text-xs font-mono font-bold bg-slate-50 border border-slate-200 text-slate-800 focus:bg-white focus:outline-none focus:border-cyan-500 shadow-2xs">
                </div>

                <!-- Outstanding Due -->
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">{{ __('Due Amount') }} ({{ $currencySymbol ?? '৳' }})</label>
                    <input type="number" 
                           step="0.01" 
                           name="due_amount" 
                           value="{{ old('due_amount', $customer->due_amount) }}" 
                           class="w-full px-3 py-1.5 rounded-lg text-xs font-mono font-bold bg-slate-50 border border-slate-200 text-slate-800 focus:bg-white focus:outline-none focus:border-cyan-500 shadow-2xs">
                </div>

                <!-- Account Status -->
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">{{ __('Select Status') }} *</label>
                    <select name="status" required class="w-full px-2.5 py-1.5 rounded-lg text-xs bg-slate-50 border border-slate-200 text-slate-800 focus:bg-white focus:outline-none focus:border-cyan-500 shadow-2xs">
                        <option value="active" {{ old('status', $customer->status) === 'active' ? 'selected' : '' }}>{{ __('Active') }}</option>
                        <option value="due" {{ old('status', $customer->status) === 'due' ? 'selected' : '' }}>{{ __('Due / Unpaid') }}</option>
                        <option value="expired" {{ old('status', $customer->status) === 'expired' ? 'selected' : '' }}>{{ __('Expired') }}</option>
                        <option value="disabled" {{ in_array(old('status', $customer->status), ['disabled', 'disconnected']) ? 'selected' : '' }}>{{ __('Disabled') }}</option>
                    </select>
                </div>

                <!-- Router -->
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">MikroTik Router</label>
                    <select name="router_id" class="w-full px-2.5 py-1.5 rounded-lg text-xs bg-slate-50 border border-slate-200 text-slate-800 focus:bg-white focus:outline-none focus:border-cyan-500 shadow-2xs">
                        <option value="">Default / Auto Assign</option>
                        @foreach($routers as $r)
                            <option value="{{ $r->id }}" {{ old('router_id', $customer->router_id) == $r->id ? 'selected' : '' }}>{{ $r->name }} ({{ $r->ip_address }})</option>
                        @endforeach
                    </select>
                </div>

                <!-- ONU MAC / Serial -->
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">ONU Serial / MAC</label>
                    <input type="text" 
                           name="onu_mac_sn" 
                           value="{{ old('onu_mac_sn', $customer->onu_mac_sn) }}" 
                           class="w-full px-3 py-1.5 rounded-lg text-xs font-mono bg-slate-50 border border-slate-200 text-slate-800 focus:bg-white focus:outline-none focus:border-cyan-500 shadow-2xs">
                </div>

                <!-- Expiry Date -->
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">{{ __('Expiry Date') }}</label>
                    <input type="date" 
                           name="expiry_date" 
                           value="{{ old('expiry_date', $customer->expiry_date ? \Carbon\Carbon::parse($customer->expiry_date)->format('Y-m-d') : '') }}" 
                           class="w-full px-3 py-1.5 rounded-lg text-xs font-mono bg-slate-50 border border-slate-200 text-slate-800 focus:bg-white focus:outline-none focus:border-cyan-500 shadow-2xs">
                </div>

                <!-- Remarks -->
                <div class="sm:col-span-2 md:col-span-3">
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">{{ __('Remarks') }}</label>
                    <input type="text" 
                           name="remarks" 
                           value="{{ old('remarks', $customer->remarks) }}" 
                           class="w-full px-3 py-1.5 rounded-lg text-xs bg-slate-50 border border-slate-200 text-slate-800 focus:bg-white focus:outline-none focus:border-cyan-500 shadow-2xs">
                </div>

            </div>
        </div>

        {{-- Submit Button Area (Rule 4: Centralized submit) --}}
        <div class="flex items-center justify-end gap-2 bg-white p-3.5 rounded-xl border border-slate-200 shadow-xs">
            <a href="{{ route('reseller.customers.index') }}" class="px-4 py-2 border border-slate-300 hover:bg-slate-100 text-slate-700 font-semibold text-xs rounded-lg transition cursor-pointer">
                {{ __('Cancel') }}
            </a>
            <button type="submit" class="bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs px-5 py-2 rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-check text-[11px]"></i>
                <span>{{ __('Save Changes') }}</span>
            </button>
        </div>

    </form>
</div>
@endsection
