@extends('reseller.layouts.app')

@section('title', ($isResellerAdmin ? 'Partner Profile - ' . ($reseller->name ?? 'Reseller Portal') : 'My Profile - ' . $user->name))

@push('styles')
    {{-- Page-specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" x-data="profileManager()">
    
    {{-- 1. TOP HEADER BAR (AGENTS.md Rule 2.A: Icon + Title + Action buttons ONLY) --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 bg-white p-3 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 border border-purple-100 flex items-center justify-center text-sm shadow-2xs flex-shrink-0">
                <i class="fas fa-id-card"></i>
            </div>
            <h1 class="text-sm font-bold text-slate-800 tracking-tight">{{ $isResellerAdmin ? __('Partner Profile') : __('My Profile') }}</h1>
        </div>
        <div class="flex items-center gap-2">
            @if($isResellerAdmin)
                <a href="{{ route('reseller.profile.certificate') }}" target="_blank" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition flex items-center gap-1.5 cursor-pointer">
                    <i class="fas fa-certificate text-purple-600 text-xs"></i>
                    <span>{{ __('Authorization Certificate') }}</span>
                </a>
                <a href="{{ route('reseller.profile.export') }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition flex items-center gap-1.5 cursor-pointer">
                    <i class="fas fa-file-csv text-slate-500 text-xs"></i>
                    <span>{{ __('Export CSV') }}</span>
                </a>
            @endif
            <button type="button" @click="submitProfileForm()" class="px-3.5 py-1.5 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-save text-xs"></i>
                <span>{{ __('Save Changes') }}</span>
            </button>
        </div>
    </div>

    {{-- Flash Notifications --}}
    @if(session('success'))
        <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 text-xs flex items-center gap-2 shadow-2xs">
            <i class="fas fa-circle-check text-emerald-600 text-sm flex-shrink-0"></i>
            <span class="font-medium">{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-rose-800 text-xs flex items-center gap-2 shadow-2xs">
            <i class="fas fa-circle-exclamation text-rose-600 text-sm flex-shrink-0"></i>
            <span class="font-medium">{{ session('error') }}</span>
        </div>
    @endif

    @if(isset($errors) && $errors->any())
        <div class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-rose-800 text-xs space-y-1 shadow-2xs">
            <div class="font-bold flex items-center gap-1.5">
                <i class="fas fa-triangle-exclamation text-rose-600 text-xs"></i>
                <span>{{ __('Warning') }}:</span>
            </div>
            <ul class="list-disc list-inside text-[11px] text-rose-700 pl-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- 2. KPI SUMMARY STRIP (Strictly 6 Cards - AGENTS.md Rule 2.B) --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
        @if($isResellerAdmin)
            {{-- Card 1: Commission Rate --}}
            <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
                <div class="min-w-0 pr-1">
                    <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Commission Rate') }}</span>
                    <span class="text-[13px] font-bold font-mono text-purple-700 leading-tight block">
                        {{ $stats['commission_rate'] }}%
                    </span>
                </div>
                <div class="w-6 h-6 rounded-md text-[10px] border border-purple-200 bg-purple-50 text-purple-600 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-percent"></i>
                </div>
            </div>

            {{-- Card 2: Billing Model --}}
            <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
                <div class="min-w-0 pr-1">
                    <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Billing Type') }}</span>
                    <span class="text-[13px] font-bold font-mono text-slate-800 leading-tight block truncate">
                        {{ $stats['billing_type'] }}
                    </span>
                </div>
                <div class="w-6 h-6 rounded-md text-[10px] border border-slate-200 bg-slate-50 text-slate-600 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-receipt"></i>
                </div>
            </div>

            {{-- Card 3: Active Subscribers --}}
            <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
                <div class="min-w-0 pr-1">
                    <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Total Customers') }}</span>
                    <span class="text-[13px] font-bold font-mono text-emerald-600 leading-tight block">
                        {{ number_format($stats['active_subscribers']) }}
                    </span>
                </div>
                <div class="w-6 h-6 rounded-md text-[10px] border border-emerald-200 bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-users"></i>
                </div>
            </div>

            {{-- Card 4: Wallet Balance --}}
            <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
                <div class="min-w-0 pr-1">
                    <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Wallet Balance') }}</span>
                    <span class="text-[13px] font-bold font-mono text-emerald-600 leading-tight block">
                        @currency($stats['wallet_balance'] ?? ($stats['available_balance'] ?? 0))
                    </span>
                </div>
                <div class="w-6 h-6 rounded-md text-[10px] border border-emerald-200 bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-wallet"></i>
                </div>
            </div>

            {{-- Card 5: Partner Code / Prefix --}}
            <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
                <div class="min-w-0 pr-1">
                    <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Partner Code</span>
                    <span class="text-[13px] font-bold font-mono text-indigo-700 leading-tight block truncate">
                        {{ $stats['partner_code'] }}
                    </span>
                </div>
                <div class="w-6 h-6 rounded-md text-[10px] border border-indigo-200 bg-indigo-50 text-indigo-600 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-hashtag"></i>
                </div>
            </div>

            {{-- Card 6: Account Status --}}
            <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
                <div class="min-w-0 pr-1">
                    <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Status</span>
                    <span class="text-[13px] font-bold font-mono text-teal-600 leading-tight block">
                        {{ $stats['status'] }}
                    </span>
                </div>
                <div class="w-6 h-6 rounded-md text-[10px] border border-teal-200 bg-teal-50 text-teal-600 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-check-double"></i>
                </div>
            </div>
        @else
            {{-- Non-Admin / Staff / Collector 6 Cards --}}
            {{-- Card 1: Staff ID --}}
            <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
                <div class="min-w-0 pr-1">
                    <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Staff ID</span>
                    <span class="text-[13px] font-bold font-mono text-purple-700 leading-tight block truncate">
                        {{ $stats['staff_id'] }}
                    </span>
                </div>
                <div class="w-6 h-6 rounded-md text-[10px] border border-purple-200 bg-purple-50 text-purple-600 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-id-badge"></i>
                </div>
            </div>

            {{-- Card 2: Role --}}
            <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
                <div class="min-w-0 pr-1">
                    <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Designation</span>
                    <span class="text-[13px] font-bold font-mono text-slate-800 leading-tight block truncate">
                        {{ $stats['role_title'] }}
                    </span>
                </div>
                <div class="w-6 h-6 rounded-md text-[10px] border border-slate-200 bg-slate-50 text-slate-600 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-user-shield"></i>
                </div>
            </div>

            {{-- Card 3: Mobile --}}
            <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
                <div class="min-w-0 pr-1">
                    <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Mobile</span>
                    <span class="text-[13px] font-bold font-mono text-emerald-600 leading-tight block truncate">
                        {{ $stats['mobile'] }}
                    </span>
                </div>
                <div class="w-6 h-6 rounded-md text-[10px] border border-emerald-200 bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-phone"></i>
                </div>
            </div>

            {{-- Card 4: Email --}}
            <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
                <div class="min-w-0 pr-1">
                    <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Email</span>
                    <span class="text-[13px] font-bold font-mono text-slate-700 leading-tight block truncate">
                        {{ $stats['email'] }}
                    </span>
                </div>
                <div class="w-6 h-6 rounded-md text-[10px] border border-slate-200 bg-slate-50 text-slate-600 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-envelope"></i>
                </div>
            </div>

            {{-- Card 5: Joined Date --}}
            <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
                <div class="min-w-0 pr-1">
                    <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Joined</span>
                    <span class="text-[13px] font-bold font-mono text-indigo-700 leading-tight block truncate">
                        {{ $stats['joined_date'] }}
                    </span>
                </div>
                <div class="w-6 h-6 rounded-md text-[10px] border border-indigo-200 bg-indigo-50 text-indigo-600 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-calendar-alt"></i>
                </div>
            </div>

            {{-- Card 6: Status --}}
            <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
                <div class="min-w-0 pr-1">
                    <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">Status</span>
                    <span class="text-[13px] font-bold font-mono text-teal-600 leading-tight block">
                        {{ $stats['status'] }}
                    </span>
                </div>
                <div class="w-6 h-6 rounded-md text-[10px] border border-teal-200 bg-teal-50 text-teal-600 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-check-double"></i>
                </div>
            </div>
        @endif
    </div>

    {{-- 3. TAB NAVIGATION STRIP --}}
    <div class="flex items-center gap-1.5 border-b border-slate-200 pb-1 bg-white p-2 rounded-xl border shadow-2xs">
        @if($isResellerAdmin)
            <button type="button" 
                    @click="activeTab = 'business'" 
                    :class="activeTab === 'business' ? 'bg-purple-600 text-white font-bold shadow-2xs' : 'bg-slate-50 hover:bg-slate-100 text-slate-600 font-medium'"
                    class="px-3.5 py-1.5 rounded-lg text-xs transition cursor-pointer flex items-center gap-1.5">
                <i class="fas fa-building text-[11px]"></i>
                <span>1. Business Details</span>
            </button>

            <button type="button" 
                    @click="activeTab = 'financial'" 
                    :class="activeTab === 'financial' ? 'bg-purple-600 text-white font-bold shadow-2xs' : 'bg-slate-50 hover:bg-slate-100 text-slate-600 font-medium'"
                    class="px-3.5 py-1.5 rounded-lg text-xs transition cursor-pointer flex items-center gap-1.5">
                <i class="fas fa-file-invoice text-[11px]"></i>
                <span>2. Billing &amp; Rates</span>
            </button>

            <button type="button" 
                    @click="activeTab = 'security'" 
                    :class="activeTab === 'security' ? 'bg-purple-600 text-white font-bold shadow-2xs' : 'bg-slate-50 hover:bg-slate-100 text-slate-600 font-medium'"
                    class="px-3.5 py-1.5 rounded-lg text-xs transition cursor-pointer flex items-center gap-1.5">
                <i class="fas fa-lock text-[11px]"></i>
                <span>3. Security</span>
            </button>

            <button type="button" 
                    @click="activeTab = 'upstream'" 
                    :class="activeTab === 'upstream' ? 'bg-purple-600 text-white font-bold shadow-2xs' : 'bg-slate-50 hover:bg-slate-100 text-slate-600 font-medium'"
                    class="px-3.5 py-1.5 rounded-lg text-xs transition cursor-pointer flex items-center gap-1.5">
                <i class="fas fa-network-wired text-[11px]"></i>
                <span>4. ISP Info</span>
            </button>
        @else
            <button type="button" 
                    @click="activeTab = 'personal'" 
                    :class="activeTab === 'personal' ? 'bg-purple-600 text-white font-bold shadow-2xs' : 'bg-slate-50 hover:bg-slate-100 text-slate-600 font-medium'"
                    class="px-3.5 py-1.5 rounded-lg text-xs transition cursor-pointer flex items-center gap-1.5">
                <i class="fas fa-user-circle text-[11px]"></i>
                <span>1. Personal Profile</span>
            </button>

            <button type="button" 
                    @click="activeTab = 'security'" 
                    :class="activeTab === 'security' ? 'bg-purple-600 text-white font-bold shadow-2xs' : 'bg-slate-50 hover:bg-slate-100 text-slate-600 font-medium'"
                    class="px-3.5 py-1.5 rounded-lg text-xs transition cursor-pointer flex items-center gap-1.5">
                <i class="fas fa-lock text-[11px]"></i>
                <span>2. Security</span>
            </button>
        @endif
    </div>

    {{-- TAB 1: BUSINESS / PERSONAL PROFILE & CONTACT INFO --}}
    <div x-show="activeTab === 'business' || activeTab === 'personal'" class="space-y-4">
        <form id="profileForm" action="{{ route('reseller.profile.update') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            @method('PUT')

            <!-- User Profile & Avatar Card -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs space-y-4">
                <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                    <h3 class="text-xs font-bold text-slate-800">{{ $isResellerAdmin ? 'User Profile & Picture' : 'Personal Profile & Avatar' }}</h3>
                    <span class="text-[10.5px] text-slate-400 font-medium">Avatar &amp; Login Information</span>
                </div>

                <!-- Avatar Upload Section -->
                <div class="flex flex-col sm:flex-row items-center gap-4 p-3.5 bg-slate-50/80 rounded-xl border border-slate-200/80">
                    <div class="relative group flex-shrink-0">
                        <!-- Image Preview or Initials -->
                        <template x-if="avatarPreview">
                            <img :src="avatarPreview" alt="Avatar Preview" class="w-16 h-16 rounded-full object-cover ring-2 ring-purple-400 shadow-xs">
                        </template>
                        <template x-if="!avatarPreview && hasExistingAvatar && !removeAvatarFlag">
                            <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="w-16 h-16 rounded-full object-cover ring-2 ring-purple-300 shadow-xs">
                        </template>
                        <template x-if="!avatarPreview && (!hasExistingAvatar || removeAvatarFlag)">
                            <div class="w-16 h-16 rounded-full bg-gradient-to-tr from-purple-600 to-indigo-600 text-white flex items-center justify-center font-bold text-xl shadow-xs">
                                {{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}
                            </div>
                        </template>

                        <!-- Camera overlay badge -->
                        <label for="avatar_input" class="absolute -bottom-0.5 -right-0.5 w-6 h-6 rounded-full bg-purple-600 hover:bg-purple-700 text-white flex items-center justify-center text-[10px] ring-2 ring-white shadow cursor-pointer transition">
                            <i class="fas fa-camera"></i>
                        </label>
                    </div>

                    <div class="space-y-1.5 text-center sm:text-left flex-1 min-w-0">
                        <div class="flex items-center gap-2 justify-center sm:justify-start">
                            <label for="avatar_input" class="px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                                <i class="fas fa-upload text-[10px]"></i>
                                <span>Upload Photo</span>
                            </label>

                            <button type="button" 
                                    x-show="avatarPreview || (hasExistingAvatar && !removeAvatarFlag)" 
                                    @click="removeAvatar()" 
                                    class="px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-lg text-xs font-semibold transition flex items-center gap-1.5 cursor-pointer">
                                <i class="fas fa-trash-can text-[10px]"></i>
                                <span>Remove</span>
                            </button>
                        </div>
                        <input type="file" 
                               id="avatar_input" 
                               name="avatar" 
                               accept="image/jpeg,image/png,image/jpg,image/webp" 
                               @change="handleAvatarChange($event)" 
                               class="hidden">
                        <input type="hidden" name="remove_avatar" :value="removeAvatarFlag ? '1' : '0'">
                        <p class="text-[10.5px] text-slate-500">
                            Allowed formats: JPG, PNG, WEBP. Maximum file size: 2MB.
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
                    <div>
                        <label for="user_name" class="block text-[11px] font-semibold text-slate-700 mb-1">
                            {{ $isResellerAdmin ? 'Admin Name' : 'Full Name' }} <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" 
                               id="user_name" 
                               name="user_name" 
                               value="{{ old('user_name', $user->name) }}" 
                               required 
                               class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-purple-500 shadow-2xs">
                    </div>

                    <div>
                        <label for="user_email" class="block text-[11px] font-semibold text-slate-700 mb-1">
                            Login Email <span class="text-rose-500">*</span>
                        </label>
                        <input type="email" 
                               id="user_email" 
                               name="user_email" 
                               value="{{ old('user_email', $user->email) }}" 
                               required 
                               class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-purple-500 shadow-2xs font-mono">
                    </div>

                    <div>
                        <label for="user_mobile" class="block text-[11px] font-semibold text-slate-700 mb-1">
                            User Mobile
                        </label>
                        <input type="text" 
                                id="user_mobile" 
                                name="user_mobile" 
                                value="{{ old('user_mobile', $user->mobile ?? $user->phone) }}" 
                                placeholder="017xxxxxxxx" 
                                class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-purple-500 font-mono shadow-2xs">
                    </div>
                </div>

                @if(!$isResellerAdmin)
                    <!-- Save Button for Staff / Collector -->
                    <div class="pt-2 flex justify-end">
                        <button type="submit" class="px-5 py-2 bg-purple-600 hover:bg-purple-700 text-white font-semibold text-xs rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                            <i class="fas fa-check text-xs"></i>
                            <span>Save Profile</span>
                        </button>
                    </div>
                @endif
            </div>

            @if($isResellerAdmin)
                <!-- Business Details Card (Admin Only) -->
                <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <h3 class="text-xs font-bold text-slate-800">Business Information</h3>
                        <span class="px-2.5 py-0.5 rounded text-[10.5px] font-mono font-bold bg-purple-50 text-purple-700 border border-purple-200">
                            {{ $reseller->code ?? 'PARTNER' }}
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3.5">
                        <!-- Partner Name (Read-Only) -->
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                                Partner Name
                            </label>
                            <input type="text" 
                                   value="{{ $reseller->name ?? '' }}" 
                                   readonly 
                                   class="w-full bg-slate-100 border border-slate-200 text-slate-600 rounded-lg text-xs px-3 py-1.5 cursor-not-allowed font-medium shadow-2xs">
                        </div>

                        <!-- Partner Prefix (Read-Only) -->
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                                Username Prefix
                            </label>
                            <input type="text" 
                                   value="{{ $reseller->prefix ?? ($reseller->code ?? 'RES') }}" 
                                   readonly 
                                   class="w-full bg-slate-100 border border-slate-200 text-slate-600 rounded-lg text-xs px-3 py-1.5 cursor-not-allowed font-mono font-bold shadow-2xs">
                        </div>

                        <!-- Contact Person -->
                        <div>
                            <label for="contact_person" class="block text-[11px] font-semibold text-slate-700 mb-1">
                                Contact Person <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" 
                                   id="contact_person" 
                                   name="contact_person" 
                                   value="{{ old('contact_person', $reseller->contact_person) }}" 
                                   required 
                                   placeholder="Contact Person Name" 
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-purple-500 shadow-2xs">
                        </div>

                        <!-- Mobile -->
                        <div>
                            <label for="mobile" class="block text-[11px] font-semibold text-slate-700 mb-1">
                                Mobile Number <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" 
                                   id="mobile" 
                                   name="mobile" 
                                   value="{{ old('mobile', $reseller->mobile) }}" 
                                   required 
                                   placeholder="017xxxxxxxx" 
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-purple-500 font-mono shadow-2xs">
                        </div>

                        <!-- Email -->
                        <div>
                            <label for="email" class="block text-[11px] font-semibold text-slate-700 mb-1">
                                Email Address
                            </label>
                            <input type="email" 
                                   id="email" 
                                   name="email" 
                                   value="{{ old('email', $reseller->email) }}" 
                                   placeholder="partner@isp.com" 
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-purple-500 shadow-2xs">
                        </div>

                        <!-- Location / Address -->
                        <div class="sm:col-span-2 md:col-span-3">
                            <label for="address" class="block text-[11px] font-semibold text-slate-700 mb-1">
                                Office Address
                            </label>
                            <textarea id="address" 
                                      name="address" 
                                      rows="2" 
                                      placeholder="Full office address..." 
                                      class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-purple-500 shadow-2xs">{{ old('address', $reseller->address) }}</textarea>
                        </div>

                        <!-- Notes -->
                        <div class="sm:col-span-2 md:col-span-3">
                            <label for="notes" class="block text-[11px] font-semibold text-slate-700 mb-1">
                                Notes
                            </label>
                            <textarea id="notes" 
                                      name="notes" 
                                      rows="2" 
                                      placeholder="Coverage areas or special notes..." 
                                      class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-purple-500 shadow-2xs">{{ old('notes', $reseller->notes) }}</textarea>
                        </div>
                    </div>

                    <!-- Submit Button Inside Business Card -->
                    <div class="pt-2 flex justify-end">
                        <button type="submit" class="px-5 py-2 bg-purple-600 hover:bg-purple-700 text-white font-semibold text-xs rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                            <i class="fas fa-check text-xs"></i>
                            <span>Save Profile</span>
                        </button>
                    </div>
                </div>
            @endif
        </form>
    </div>

    @if($isResellerAdmin)
        {{-- TAB 2: FINANCIAL & TARIFF TERMS (Admin Only) --}}
        <div x-show="activeTab === 'financial'" class="space-y-4" style="display: none;">
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-xs font-bold text-slate-800">Billing &amp; Rate Details</h3>
                    <span class="px-2.5 py-0.5 rounded text-[10.5px] font-mono font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        Active
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3.5">
                    <!-- Commission Rate -->
                    <div class="p-3 bg-purple-50/50 rounded-lg border border-purple-100 space-y-1">
                        <span class="text-[10px] uppercase font-bold text-purple-700 block tracking-wider">Commission Rate</span>
                        <span class="text-lg font-bold font-mono text-purple-900 block leading-tight">
                            {{ $stats['commission_rate'] }}%
                        </span>
                    </div>

                    <!-- Billing Type -->
                    <div class="p-3 bg-slate-50 rounded-lg border border-slate-200 space-y-1">
                        <span class="text-[10px] uppercase font-bold text-slate-600 block tracking-wider">Billing Type</span>
                        <span class="text-base font-bold font-mono text-slate-800 block leading-tight">
                            {{ $stats['billing_type'] }}
                        </span>
                    </div>

                    <!-- Credit Limit -->
                    <div class="p-3 bg-blue-50/50 rounded-lg border border-blue-100 space-y-1">
                        <span class="text-[10px] uppercase font-bold text-blue-700 block tracking-wider">Credit Limit</span>
                        <span class="text-lg font-bold font-mono text-blue-900 block leading-tight">
                            @currency($reseller->credit_limit ?? 0)
                        </span>
                    </div>

                    <!-- Monthly Panel Subscription Charge -->
                    <div class="p-3 bg-slate-50 rounded-lg border border-slate-200 space-y-1">
                        <span class="text-[10px] uppercase font-bold text-slate-600 block tracking-wider">Monthly Fee</span>
                        <span class="text-base font-bold font-mono text-slate-800 block leading-tight">
                            @currency($reseller->monthly_panel_charge ?? 0)
                        </span>
                    </div>

                    <!-- Panel Expiry Date -->
                    <div class="p-3 bg-slate-50 rounded-lg border border-slate-200 space-y-1">
                        <span class="text-[10px] uppercase font-bold text-slate-600 block tracking-wider">Expiry Date</span>
                        <span class="text-base font-bold font-mono text-slate-800 block leading-tight">
                            {{ $reseller->panel_expiry_date ? $reseller->panel_expiry_date->format('d M, Y') : 'Lifetime' }}
                        </span>
                    </div>

                    <!-- Available Balance -->
                    <div class="p-3 bg-emerald-50/50 rounded-lg border border-emerald-100 space-y-1">
                        <span class="text-[10px] uppercase font-bold text-emerald-700 block tracking-wider">Total Available Balance</span>
                        <span class="text-lg font-bold font-mono text-emerald-900 block leading-tight">
                            @currency($stats['available_balance'])
                        </span>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- TAB: ACCOUNT SECURITY & PASSWORD (All Roles) --}}
    <div x-show="activeTab === 'security'" class="space-y-4" style="display: none;">
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs space-y-4">
            <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                <h3 class="text-xs font-bold text-slate-800">Change Password</h3>
                <span class="text-[10.5px] text-slate-400 font-medium">Update account login credentials</span>
            </div>

            <form action="{{ route('reseller.profile.password') }}" method="POST" class="max-w-xl space-y-4">
                @csrf

                <!-- Current Password -->
                <div>
                    <label for="current_password" class="block text-[11px] font-semibold text-slate-700 mb-1">
                        Current Password <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <input :type="showCurrentPass ? 'text' : 'password'" 
                               id="current_password" 
                               name="current_password" 
                               required 
                               placeholder="••••••••••••" 
                               class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 pr-8 focus:bg-white focus:border-purple-500 shadow-2xs font-mono">
                        <button type="button" 
                                @click="showCurrentPass = !showCurrentPass" 
                                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-xs">
                            <i :class="showCurrentPass ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                        </button>
                    </div>
                </div>

                <!-- New Password -->
                <div>
                    <label for="password" class="block text-[11px] font-semibold text-slate-700 mb-1">
                        New Password <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <input :type="showNewPass ? 'text' : 'password'" 
                               id="password" 
                               name="password" 
                               required 
                               placeholder="Minimum 6 characters" 
                               class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 pr-8 focus:bg-white focus:border-purple-500 shadow-2xs font-mono">
                        <button type="button" 
                                @click="showNewPass = !showNewPass" 
                                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-xs">
                            <i :class="showNewPass ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                        </button>
                    </div>
                </div>

                <!-- Confirm New Password -->
                <div>
                    <label for="password_confirmation" class="block text-[11px] font-semibold text-slate-700 mb-1">
                        Confirm New Password <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <input :type="showConfPass ? 'text' : 'password'" 
                               id="password_confirmation" 
                               name="password_confirmation" 
                               required 
                               placeholder="Re-type new password" 
                               class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 pr-8 focus:bg-white focus:border-purple-500 shadow-2xs font-mono">
                        <button type="button" 
                                @click="showConfPass = !showConfPass" 
                                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-xs">
                            <i :class="showConfPass ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                        </button>
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" class="px-5 py-2 bg-purple-600 hover:bg-purple-700 text-white font-semibold text-xs rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                        <i class="fas fa-key text-xs"></i>
                        <span>Update Password</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if($isResellerAdmin)
        {{-- TAB 4: HOST ISP NETWORK & UPSTREAM INFO (Admin Only) --}}
        <div x-show="activeTab === 'upstream'" class="space-y-4" style="display: none;">
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs space-y-4">
                <div class="border-b border-slate-100 pb-3">
                    <h3 class="text-xs font-bold text-slate-800">ISP Information</h3>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3.5">
                    <div>
                        <span class="text-[10px] text-slate-400 font-medium block uppercase tracking-wider">ISP Company</span>
                        <span class="text-xs font-bold text-slate-800 block mt-0.5">{{ $tenant->company_name ?? ($tenant->name ?? 'ISP Provider') }}</span>
                    </div>

                    <div>
                        <span class="text-[10px] text-slate-400 font-medium block uppercase tracking-wider">Support Phone</span>
                        <span class="text-xs font-mono font-bold text-purple-700 block mt-0.5">{{ $tenant->support_hotline ?: ($tenant->phone ?? 'N/A') }}</span>
                    </div>

                    <div>
                        <span class="text-[10px] text-slate-400 font-medium block uppercase tracking-wider">Billing Phone</span>
                        <span class="text-xs font-mono font-bold text-slate-800 block mt-0.5">{{ $tenant->billing_phone ?: ($tenant->phone ?? 'N/A') }}</span>
                    </div>

                    <div>
                        <span class="text-[10px] text-slate-400 font-medium block uppercase tracking-wider">Support Email</span>
                        <span class="text-xs font-mono text-slate-800 block mt-0.5">{{ $tenant->email ?? 'noc@isp.com' }}</span>
                    </div>

                    <div>
                        <span class="text-[10px] text-slate-400 font-medium block uppercase tracking-wider">BTRC License</span>
                        <span class="text-xs font-bold text-slate-800 block mt-0.5">{{ $tenant->btrc_license_no ?: 'Verified License' }}</span>
                    </div>

                    <div>
                        <span class="text-[10px] text-slate-400 font-medium block uppercase tracking-wider">Office Address</span>
                        <span class="text-xs text-slate-700 block mt-0.5">{{ $tenant->address ?? 'N/A' }}</span>
                    </div>
                </div>
            </div>
        </div>
    @endif

</div>
@endsection

@push('scripts')
<script>
function profileManager() {
    return {
        activeTab: '{{ $errors->has("current_password") || $errors->has("password") ? "security" : ($isResellerAdmin ? "business" : "personal") }}',
        avatarPreview: null,
        hasExistingAvatar: @json(!empty($user->avatar)),
        removeAvatarFlag: false,
        showCurrentPass: false,
        showNewPass: false,
        showConfPass: false,
        handleAvatarChange(e) {
            const file = e.target.files[0];
            if (file) {
                if (file.size > 2 * 1024 * 1024) {
                    alert('ছবি সর্বোচ্চ ২ মেগাবাইট (2MB) হতে পারবে।');
                    e.target.value = '';
                    return;
                }
                const reader = new FileReader();
                reader.onload = (event) => {
                    this.avatarPreview = event.target.result;
                    this.removeAvatarFlag = false;
                };
                reader.readAsDataURL(file);
            }
        },
        removeAvatar() {
            this.avatarPreview = null;
            this.removeAvatarFlag = true;
            const input = document.getElementById('avatar_input');
            if (input) input.value = '';
        },
        submitProfileForm() {
            const form = document.getElementById('profileForm');
            if (form) {
                form.submit();
            }
        }
    };
}
</script>
@endpush
