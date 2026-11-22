@extends('reseller.layouts.app')

@section('title', $isResellerAdmin ? __('Partner Profile') : __('My Profile'))

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
                <a href="{{ route('reseller.profile.certificate') }}" target="_blank" rel="noopener" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition flex items-center gap-1.5 cursor-pointer">
                    <i class="fas fa-certificate text-purple-600 text-xs"></i>
                    <span>{{ __('Authorization Certificate') }}</span>
                </a>
                <a href="{{ route('reseller.profile.export') }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition flex items-center gap-1.5 cursor-pointer">
                    <i class="fas fa-file-csv text-slate-500 text-xs"></i>
                    <span>{{ __('Export CSV') }}</span>
                </a>
            @endif
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
                    <span class="text-[13px] font-bold text-purple-700 leading-tight block">
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
                    <span class="text-[13px] font-bold text-slate-800 leading-tight block truncate">
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
                    <span class="text-[13px] font-bold text-emerald-600 leading-tight block">
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
                    <span class="text-[13px] font-bold text-emerald-600 leading-tight block">
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
                    <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Partner Code') }}</span>
                    <span class="text-[13px] font-bold text-indigo-700 leading-tight block truncate">
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
                    <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Status') }}</span>
                    <span class="text-[13px] font-bold text-teal-600 leading-tight block">
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
                    <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Staff ID') }}</span>
                    <span class="text-[13px] font-bold text-purple-700 leading-tight block truncate">
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
                    <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Designation') }}</span>
                    <span class="text-[13px] font-bold text-slate-800 leading-tight block truncate">
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
                    <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Mobile') }}</span>
                    <span class="text-[13px] font-bold text-emerald-600 leading-tight block truncate">
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
                    <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Email') }}</span>
                    <span class="text-[13px] font-bold text-slate-700 leading-tight block truncate">
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
                    <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Joined') }}</span>
                    <span class="text-[13px] font-bold text-indigo-700 leading-tight block truncate">
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
                    <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Status') }}</span>
                    <span class="text-[13px] font-bold text-teal-600 leading-tight block">
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
                    @click="activeTab = 'business'" :aria-pressed="activeTab === 'business'"
                    :class="activeTab === 'business' ? 'bg-cyan-600 text-white font-bold shadow-2xs' : 'bg-slate-50 hover:bg-slate-100 text-slate-600 font-medium'"
                    class="px-3.5 py-1.5 rounded-lg text-xs transition cursor-pointer flex items-center gap-1.5">
                <i class="fas fa-building text-[11px]"></i>
                <span>{{ __('Business Details') }}</span>
            </button>

            <button type="button"
                    @click="activeTab = 'financial'" :aria-pressed="activeTab === 'financial'"
                    :class="activeTab === 'financial' ? 'bg-cyan-600 text-white font-bold shadow-2xs' : 'bg-slate-50 hover:bg-slate-100 text-slate-600 font-medium'"
                    class="px-3.5 py-1.5 rounded-lg text-xs transition cursor-pointer flex items-center gap-1.5">
                <i class="fas fa-file-invoice text-[11px]"></i>
                <span>{{ __('Billing & Rates') }}</span>
            </button>

            <button type="button"
                    @click="activeTab = 'security'" :aria-pressed="activeTab === 'security'"
                    :class="activeTab === 'security' ? 'bg-cyan-600 text-white font-bold shadow-2xs' : 'bg-slate-50 hover:bg-slate-100 text-slate-600 font-medium'"
                    class="px-3.5 py-1.5 rounded-lg text-xs transition cursor-pointer flex items-center gap-1.5">
                <i class="fas fa-lock text-[11px]"></i>
                <span>{{ __('Security') }}</span>
            </button>

            <button type="button"
                    @click="activeTab = 'upstream'" :aria-pressed="activeTab === 'upstream'"
                    :class="activeTab === 'upstream' ? 'bg-cyan-600 text-white font-bold shadow-2xs' : 'bg-slate-50 hover:bg-slate-100 text-slate-600 font-medium'"
                    class="px-3.5 py-1.5 rounded-lg text-xs transition cursor-pointer flex items-center gap-1.5">
                <i class="fas fa-network-wired text-[11px]"></i>
                <span>{{ __('ISP Info') }}</span>
            </button>
        @else
            <button type="button"
                    @click="activeTab = 'personal'" :aria-pressed="activeTab === 'personal'"
                    :class="activeTab === 'personal' ? 'bg-cyan-600 text-white font-bold shadow-2xs' : 'bg-slate-50 hover:bg-slate-100 text-slate-600 font-medium'"
                    class="px-3.5 py-1.5 rounded-lg text-xs transition cursor-pointer flex items-center gap-1.5">
                <i class="fas fa-user-circle text-[11px]"></i>
                <span>{{ __('Personal Profile') }}</span>
            </button>

            <button type="button"
                    @click="activeTab = 'security'" :aria-pressed="activeTab === 'security'"
                    :class="activeTab === 'security' ? 'bg-cyan-600 text-white font-bold shadow-2xs' : 'bg-slate-50 hover:bg-slate-100 text-slate-600 font-medium'"
                    class="px-3.5 py-1.5 rounded-lg text-xs transition cursor-pointer flex items-center gap-1.5">
                <i class="fas fa-lock text-[11px]"></i>
                <span>{{ __('Security') }}</span>
            </button>
        @endif
    </div>

    {{-- TAB 1: BUSINESS / PERSONAL PROFILE & CONTACT INFO --}}
    <div x-show="activeTab === 'business' || activeTab === 'personal'" class="w-full space-y-4">
        <form id="profileForm" action="{{ route('reseller.profile.update') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            @method('PUT')

            <!-- User Profile & Avatar Card -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs space-y-4">
                <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                    <h3 class="text-xs font-bold text-slate-800">{{ __('Personal Profile') }}</h3>
                    <span class="text-[10.5px] text-slate-400 font-medium">{{ __('Avatar & Login Information') }}</span>
                </div>

                <!-- Avatar Upload Section -->
                <div class="flex flex-col sm:flex-row items-center gap-4 p-3.5 bg-slate-50/80 rounded-xl border border-slate-200/80">
                    <div class="relative group flex-shrink-0">
                        <!-- Image Preview or Initials -->
                        <template x-if="avatarPreview">
                            <img :src="avatarPreview" alt="{{ __('Avatar Preview') }}" class="w-16 h-16 rounded-full object-cover ring-2 ring-purple-400 shadow-xs">
                        </template>
                        <template x-if="!avatarPreview && hasExistingAvatar && !removeAvatarFlag">
                            <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="w-16 h-16 rounded-full object-cover ring-2 ring-purple-300 shadow-xs">
                        </template>
                        <template x-if="!avatarPreview && (!hasExistingAvatar || removeAvatarFlag)">
                            <div class="w-16 h-16 rounded-full bg-gradient-to-tr from-purple-600 to-indigo-600 text-white flex items-center justify-center font-bold text-xl shadow-xs">
                                {{ mb_strtoupper(mb_substr(trim($user->name ?? ''), 0, 1, 'UTF-8'), 'UTF-8') ?: 'U' }}
                            </div>
                        </template>

                        <!-- Camera overlay badge -->
                        <label for="avatar_input" class="absolute -bottom-0.5 -right-0.5 w-6 h-6 rounded-full bg-cyan-600 hover:bg-cyan-700 text-white flex items-center justify-center text-[10px] ring-2 ring-white shadow cursor-pointer transition">
                            <i class="fas fa-camera"></i>
                        </label>
                    </div>

                    <div class="space-y-1.5 text-center sm:text-left flex-1 min-w-0">
                        <div class="flex items-center gap-2 justify-center sm:justify-start">
                            <label for="avatar_input" class="px-3 py-1.5 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                                <i class="fas fa-upload text-[10px]"></i>
                                <span>{{ __('Upload Photo') }}</span>
                            </label>

                            <button type="button"
                                    x-show="avatarPreview || (hasExistingAvatar && !removeAvatarFlag)"
                                    @click="removeAvatar()"
                                    class="px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-lg text-xs font-semibold transition flex items-center gap-1.5 cursor-pointer">
                                <i class="fas fa-trash-can text-[10px]"></i>
                                <span>{{ __('Remove') }}</span>
                            </button>
                        </div>
                        <input type="file"
                               id="avatar_input" x-ref="avatarInput"
                               name="avatar"
                               accept="image/jpeg,image/png,image/jpg,image/webp"
                               @change="handleAvatarChange($event)"
                               class="hidden">
                        <p x-show="avatarError" x-text="avatarError" x-cloak role="alert" class="mt-1 text-xs text-rose-700"></p>
                        @error('avatar', 'profile') <p role="alert" class="mt-1 text-xs text-rose-700">{{ $message }}</p> @enderror
                        <input type="hidden" name="remove_avatar" :value="removeAvatarFlag ? '1' : '0'">
                        <p class="text-[10.5px] text-slate-500">
                            {{ __('JPG, PNG or WebP. Maximum 2 MB and 4096 x 4096 pixels.') }}
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
                    <div>
                        <label for="user_name" class="block text-[11px] font-semibold text-slate-700 mb-1">
                            {{ __($isResellerAdmin ? 'Admin Name' : 'Full Name') }} <span class="text-rose-500">*</span>
                        </label>
                        <input type="text"
                               id="user_name"
                               name="user_name" autocomplete="name" maxlength="100"
                               value="{{ old('user_name', $user->name) }}"
                               required
                               class="w-full border @error('user_name', 'profile') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border-slate-200 text-slate-800 @enderror rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 shadow-2xs">
                        @error('user_name', 'profile') <p class="mt-1 text-xs text-rose-700" role="alert">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="user_email" class="block text-[11px] font-semibold text-slate-700 mb-1">
                            {{ __('Login Email') }} <span class="text-rose-500">*</span>
                        </label>
                        <input type="email"
                               id="user_email"
                               name="user_email" autocomplete="email" maxlength="100"
                               value="{{ old('user_email', $user->email) }}"
                               required
                               class="w-full border @error('user_email', 'profile') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border-slate-200 text-slate-800 @enderror rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 shadow-2xs">
                        @error('user_email', 'profile') <p class="mt-1 text-xs text-rose-700" role="alert">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="user_mobile" class="block text-[11px] font-semibold text-slate-700 mb-1">
                            {{ __('User Mobile') }}
                        </label>
                        <input type="text"
                                id="user_mobile"
                                name="user_mobile" autocomplete="tel" maxlength="30" inputmode="tel"
                                value="{{ old('user_mobile', $user->mobile ?? $user->phone) }}"
                                placeholder="017xxxxxxxx"
                                class="w-full border @error('user_mobile', 'profile') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border-slate-200 text-slate-800 @enderror rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 shadow-2xs">
                        @error('user_mobile', 'profile') <p class="mt-1 text-xs text-rose-700" role="alert">{{ $message }}</p> @enderror
                    </div>
                </div>

                @if(!$isResellerAdmin)
                    <!-- Save Button for Staff / Collector -->
                    <div class="pt-2 flex justify-center">
                        <button type="submit" class="px-5 py-2 bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                            <i class="fas fa-check text-xs"></i>
                            <span>{{ __('Save Profile') }}</span>
                        </button>
                    </div>
                @endif
            </div>

            @if($isResellerAdmin)
                <!-- Business Details Card (Admin Only) -->
                <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <h3 class="text-xs font-bold text-slate-800">{{ __('Business Information') }}</h3>
                        <span class="px-2.5 py-0.5 rounded text-[10.5px] font-bold bg-purple-50 text-purple-700 border border-purple-200">
                            {{ $reseller->code ?? __('N/A') }}
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3.5">
                        <!-- Partner Name (Read-Only) -->
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                                {{ __('Partner Name') }}
                            </label>
                            <input type="text"
                                   value="{{ $reseller->name ?? '' }}"
                                   readonly
                                   class="w-full bg-slate-100 border border-slate-200 text-slate-600 rounded-lg text-xs px-3 py-1.5 cursor-not-allowed font-medium shadow-2xs">
                        </div>

                        <!-- Partner Prefix (Read-Only) -->
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">
                                {{ __('Username Prefix') }}
                            </label>
                            <input type="text"
                                   value="{{ $reseller->prefix ?? ($reseller->code ?? 'RES') }}"
                                   readonly
                                   class="w-full bg-slate-100 border border-slate-200 text-slate-600 rounded-lg text-xs px-3 py-1.5 cursor-not-allowed font-bold shadow-2xs">
                        </div>

                        <!-- Contact Person -->
                        <div>
                            <label for="contact_person" class="block text-[11px] font-semibold text-slate-700 mb-1">
                                {{ __('Contact Person') }} <span class="text-rose-500">*</span>
                            </label>
                            <input type="text"
                                   id="contact_person"
                                   name="contact_person" maxlength="150"
                                   value="{{ old('contact_person', $reseller->contact_person) }}"
                                   required
                                   placeholder="{{ __('Contact Person Name') }}"
                                   class="w-full border @error('contact_person', 'profile') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border-slate-200 text-slate-800 @enderror rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 shadow-2xs">
                        @error('contact_person', 'profile') <p class="mt-1 text-xs text-rose-700" role="alert">{{ $message }}</p> @enderror
                        </div>

                        <!-- Mobile -->
                        <div>
                            <label for="mobile" class="block text-[11px] font-semibold text-slate-700 mb-1">
                                {{ __('Mobile Number') }} <span class="text-rose-500">*</span>
                            </label>
                            <input type="text"
                                   id="mobile"
                                   name="mobile" maxlength="30" inputmode="tel"
                                   value="{{ old('mobile', $reseller->mobile) }}"
                                   required
                                   placeholder="017xxxxxxxx"
                                   class="w-full border @error('mobile', 'profile') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border-slate-200 text-slate-800 @enderror rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 shadow-2xs">
                        @error('mobile', 'profile') <p class="mt-1 text-xs text-rose-700" role="alert">{{ $message }}</p> @enderror
                        </div>

                        <!-- Email -->
                        <div>
                            <label for="email" class="block text-[11px] font-semibold text-slate-700 mb-1">
                                {{ __('Email Address') }}
                            </label>
                            <input type="email"
                                   id="email"
                                   name="email" maxlength="100"
                                   value="{{ old('email', $reseller->email) }}"
                                   placeholder="partner@isp.com"
                                   class="w-full border @error('email', 'profile') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border-slate-200 text-slate-800 @enderror rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 shadow-2xs">
                        @error('email', 'profile') <p class="mt-1 text-xs text-rose-700" role="alert">{{ $message }}</p> @enderror
                        </div>

                        <!-- Location / Address -->
                        <div class="sm:col-span-2 md:col-span-3">
                            <label for="address" class="block text-[11px] font-semibold text-slate-700 mb-1">
                                {{ __('Office Address') }}
                            </label>
                            <textarea id="address"
                                      name="address" maxlength="500"
                                      rows="2"
                                      placeholder="{{ __('Full office address...') }}"
                                      class="w-full border @error('address', 'profile') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border-slate-200 text-slate-800 @enderror rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 shadow-2xs">{{ old('address', $reseller->address) }}</textarea>
                        @error('address', 'profile') <p class="mt-1 text-xs text-rose-700" role="alert">{{ $message }}</p> @enderror
                        </div>

                        <!-- Notes -->
                        <div class="sm:col-span-2 md:col-span-3">
                            <label for="notes" class="block text-[11px] font-semibold text-slate-700 mb-1">
                                {{ __('Notes') }}
                            </label>
                            <textarea id="notes"
                                      name="notes" maxlength="1000"
                                      rows="2"
                                      placeholder="{{ __('Coverage areas or special notes...') }}"
                                      class="w-full border @error('notes', 'profile') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border-slate-200 text-slate-800 @enderror rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 shadow-2xs">{{ old('notes', $reseller->notes) }}</textarea>
                        @error('notes', 'profile') <p class="mt-1 text-xs text-rose-700" role="alert">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <!-- Submit Button Inside Business Card -->
                    <div class="pt-2 flex justify-center">
                        <button type="submit" class="px-5 py-2 bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                            <i class="fas fa-check text-xs"></i>
                            <span>{{ __('Save Profile') }}</span>
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
                    <h3 class="text-xs font-bold text-slate-800">{{ __('Billing & Rate Details') }}</h3>
                    <span class="px-2.5 py-0.5 rounded text-[10.5px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        {{ $stats['status'] }}
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3.5">
                    <!-- Commission Rate -->
                    <div class="p-3 bg-purple-50/50 rounded-lg border border-purple-100 space-y-1">
                        <span class="text-[10px] uppercase font-bold text-purple-700 block tracking-wider">{{ __('Commission Rate') }}</span>
                        <span class="text-lg font-bold text-purple-900 block leading-tight">
                            {{ $stats['commission_rate'] }}%
                        </span>
                    </div>

                    <!-- Billing Type -->
                    <div class="p-3 bg-slate-50 rounded-lg border border-slate-200 space-y-1">
                        <span class="text-[10px] uppercase font-bold text-slate-600 block tracking-wider">{{ __('Billing Type') }}</span>
                        <span class="text-base font-bold text-slate-800 block leading-tight">
                            {{ $stats['billing_type'] }}
                        </span>
                    </div>

                    <!-- Credit Limit -->
                    <div class="p-3 bg-blue-50/50 rounded-lg border border-blue-100 space-y-1">
                        <span class="text-[10px] uppercase font-bold text-blue-700 block tracking-wider">{{ __('Credit Limit') }}</span>
                        <span class="text-lg font-bold text-blue-900 block leading-tight">
                            @currency($reseller->credit_limit ?? 0)
                        </span>
                    </div>

                    <!-- Monthly Panel Subscription Charge -->
                    <div class="p-3 bg-slate-50 rounded-lg border border-slate-200 space-y-1">
                        <span class="text-[10px] uppercase font-bold text-slate-600 block tracking-wider">{{ __('Monthly Fee') }}</span>
                        <span class="text-base font-bold text-slate-800 block leading-tight">
                            @currency($reseller->monthly_panel_charge ?? 0)
                        </span>
                    </div>

                    <!-- Panel Expiry Date -->
                    <div class="p-3 bg-slate-50 rounded-lg border border-slate-200 space-y-1">
                        <span class="text-[10px] uppercase font-bold text-slate-600 block tracking-wider">{{ __('Expiry Date') }}</span>
                        <span class="text-base font-bold text-slate-800 block leading-tight">
                            {{ $reseller->panel_expiry_date ? $reseller->panel_expiry_date->format('d M, Y') : __('N/A') }}
                        </span>
                    </div>

                    <!-- Available Balance -->
                    <div class="p-3 bg-emerald-50/50 rounded-lg border border-emerald-100 space-y-1">
                        <span class="text-[10px] uppercase font-bold text-emerald-700 block tracking-wider">{{ __('Total Available Balance') }}</span>
                        <span class="text-lg font-bold text-emerald-900 block leading-tight">
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
                <h3 class="text-xs font-bold text-slate-800">{{ __('Change Password') }}</h3>

            </div>

            <form action="{{ route('reseller.profile.password') }}" method="POST" class="max-w-xl space-y-4">
                @csrf

                <!-- Current Password -->
                <div>
                    <label for="current_password" class="block text-[11px] font-semibold text-slate-700 mb-1">
                        {{ __('Current Password') }} <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <input :type="showCurrentPass ? 'text' : 'password'"
                               id="current_password"
                               name="current_password" autocomplete="current-password"
                               required
                               placeholder="••••••••••••"
                               class="w-full border @error('current_password', 'password') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border-slate-200 text-slate-800 @enderror rounded-lg text-xs px-3 py-1.5 pr-8 focus:bg-white focus:border-cyan-500 shadow-2xs">
                        <button type="button"
                                @click="showCurrentPass = !showCurrentPass"
                                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-xs">
                            <i :class="showCurrentPass ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                        </button>
                    </div>
                        @error('current_password', 'password') <p class="mt-1 text-xs text-rose-700" role="alert">{{ $message }}</p> @enderror
                </div>

                <!-- New Password -->
                <div>
                    <label for="password" class="block text-[11px] font-semibold text-slate-700 mb-1">
                        {{ __('New Password') }} <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <input :type="showNewPass ? 'text' : 'password'"
                               id="password"
                               name="password" autocomplete="new-password" minlength="8"
                               required
                               placeholder="{{ __('Minimum 8 characters') }}"
                               class="w-full border @error('password', 'password') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border-slate-200 text-slate-800 @enderror rounded-lg text-xs px-3 py-1.5 pr-8 focus:bg-white focus:border-cyan-500 shadow-2xs">
                        <button type="button"
                                @click="showNewPass = !showNewPass"
                                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-xs">
                            <i :class="showNewPass ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                        </button>
                    </div>
                        @error('password', 'password') <p class="mt-1 text-xs text-rose-700" role="alert">{{ $message }}</p> @enderror
                </div>

                <!-- Confirm New Password -->
                <div>
                    <label for="password_confirmation" class="block text-[11px] font-semibold text-slate-700 mb-1">
                        {{ __('Confirm New Password') }} <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <input :type="showConfPass ? 'text' : 'password'"
                               id="password_confirmation"
                               name="password_confirmation" autocomplete="new-password" minlength="8"
                               required
                               placeholder="{{ __('Confirm New Password') }}"
                               class="w-full border @error('password_confirmation', 'password') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 text-rose-900 @else bg-slate-50 border-slate-200 text-slate-800 @enderror rounded-lg text-xs px-3 py-1.5 pr-8 focus:bg-white focus:border-cyan-500 shadow-2xs">
                        <button type="button"
                                @click="showConfPass = !showConfPass"
                                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-xs">
                            <i :class="showConfPass ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                        </button>
                    </div>
                        @error('password_confirmation', 'password') <p class="mt-1 text-xs text-rose-700" role="alert">{{ $message }}</p> @enderror
                </div>

                <div class="pt-2">
                    <button type="submit" class="px-5 py-2 bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                        <i class="fas fa-key text-xs"></i>
                        <span>{{ __('Update Password') }}</span>
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
                    <h3 class="text-xs font-bold text-slate-800">{{ __('ISP Information') }}</h3>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3.5">
                    <div>
                        <span class="text-[10px] text-slate-400 font-medium block uppercase tracking-wider">{{ __('ISP Company') }}</span>
                        <span class="text-xs font-bold text-slate-800 block mt-0.5">{{ $tenant->company_name ?? ($tenant->name ?? 'ISP Provider') }}</span>
                    </div>

                    <div>
                        <span class="text-[10px] text-slate-400 font-medium block uppercase tracking-wider">{{ __('Support Phone') }}</span>
                        <span class="text-xs font-bold text-purple-700 block mt-0.5">{{ $tenant->support_hotline ?: ($tenant->phone ?? __('N/A')) }}</span>
                    </div>

                    <div>
                        <span class="text-[10px] text-slate-400 font-medium block uppercase tracking-wider">{{ __('Billing Phone') }}</span>
                        <span class="text-xs font-bold text-slate-800 block mt-0.5">{{ $tenant->billing_phone ?: ($tenant->phone ?? __('N/A')) }}</span>
                    </div>

                    <div>
                        <span class="text-[10px] text-slate-400 font-medium block uppercase tracking-wider">{{ __('Support Email') }}</span>
                        <span class="text-xs text-slate-800 block mt-0.5">{{ $tenant->email ?? __('N/A') }}</span>
                    </div>

                    <div>
                        <span class="text-[10px] text-slate-400 font-medium block uppercase tracking-wider">{{ __('BTRC License') }}</span>
                        <span class="text-xs font-bold text-slate-800 block mt-0.5">{{ $tenant->btrc_license_no ?: __('N/A') }}</span>
                    </div>

                    <div>
                        <span class="text-[10px] text-slate-400 font-medium block uppercase tracking-wider">{{ __('Office Address') }}</span>
                        <span class="text-xs text-slate-700 block mt-0.5">{{ $tenant->address ?? __('N/A') }}</span>
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
        activeTab: @js($errors->password->any() ? 'security' : ($isResellerAdmin ? 'business' : 'personal')),
        avatarPreview: null,
        hasExistingAvatar: @json(!empty($user->avatar)),
        removeAvatarFlag: @js((bool) old('remove_avatar', false)),
        avatarError: '',
        showCurrentPass: false,
        showNewPass: false,
        showConfPass: false,
        handleAvatarChange(event) {
            this.clearPreview();
            this.avatarError = '';
            const file = event.target.files[0];
            if (!file) return;

            if (file.size > 2 * 1024 * 1024) {
                this.avatarError = @js(__('Profile photo must be 2 MB or smaller.'));
                event.target.value = '';
                return;
            }

            this.avatarPreview = URL.createObjectURL(file);
            this.removeAvatarFlag = false;
        },
        removeAvatar() {
            this.clearPreview();
            this.avatarError = '';
            this.removeAvatarFlag = true;
            this.$refs.avatarInput.value = '';
        },
        clearPreview() {
            if (this.avatarPreview) URL.revokeObjectURL(this.avatarPreview);
            this.avatarPreview = null;
        },
        destroy() {
            this.clearPreview();
        }
    };
}
</script>
@endpush
