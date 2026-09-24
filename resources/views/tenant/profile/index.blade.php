@extends('tenant.layouts.app')

@section('title', __('My Profile') . ' - ' . $user->name)

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
            <h1 class="text-sm font-bold text-slate-800 tracking-tight">{{ __('My Profile') }}</h1>
        </div>
        <div class="flex items-center gap-2">
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
        {{-- Card 1: Staff ID --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Staff ID') }}</span>
                <span class="text-[13px] font-bold font-mono text-purple-700 leading-tight block truncate">
                    {{ $stats['staff_id'] }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-purple-200 bg-purple-50 text-purple-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-id-badge"></i>
            </div>
        </div>

        {{-- Card 2: Role / Designation --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Designation') }}</span>
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
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Mobile') }}</span>
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
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Email') }}</span>
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
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Joined') }}</span>
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
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Status') }}</span>
                <span class="text-[13px] font-bold font-mono text-teal-600 leading-tight block">
                    {{ $stats['status'] }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-teal-200 bg-teal-50 text-teal-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-check-double"></i>
            </div>
        </div>
    </div>

    {{-- 3. TAB NAVIGATION STRIP --}}
    <div class="flex items-center gap-1.5 border-b border-slate-200 pb-1 bg-white p-2 rounded-xl border shadow-2xs">
        <button type="button" 
                @click="activeTab = 'personal'" 
                :class="activeTab === 'personal' ? 'bg-purple-600 text-white font-bold shadow-2xs' : 'bg-slate-50 hover:bg-slate-100 text-slate-600 font-medium'"
                class="px-3.5 py-1.5 rounded-lg text-xs transition cursor-pointer flex items-center gap-1.5">
            <i class="fas fa-user-circle text-[11px]"></i>
            <span>{{ __('1. Personal Profile') }}</span>
        </button>

        <button type="button" 
                @click="activeTab = 'security'" 
                :class="activeTab === 'security' ? 'bg-purple-600 text-white font-bold shadow-2xs' : 'bg-slate-50 hover:bg-slate-100 text-slate-600 font-medium'"
                class="px-3.5 py-1.5 rounded-lg text-xs transition cursor-pointer flex items-center gap-1.5">
            <i class="fas fa-lock text-[11px]"></i>
            <span>{{ __('2. Security') }}</span>
        </button>
    </div>

    {{-- TAB 1: PERSONAL PROFILE & CONTACT INFO --}}
    <div x-show="activeTab === 'personal'" class="space-y-4">
        <form id="profileForm" action="{{ route('tenant.profile.update') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            @method('PUT')

            <!-- User Profile & Avatar Card -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs space-y-4">
                <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                    <h3 class="text-xs font-bold text-slate-800">{{ __('Personal Profile & Avatar') }}</h3>
                    <span class="text-[10.5px] text-slate-400 font-medium">{{ __('Avatar & Login Information') }}</span>
                </div>

                <!-- Avatar Upload Section -->
                <div class="flex flex-col sm:flex-row items-center gap-4 p-3.5 bg-slate-50/80 rounded-xl border border-slate-200/80">
                    <div class="relative group flex-shrink-0">
                        <!-- Image Preview or Initials -->
                        <template x-if="avatarPreview">
                            <img :src="avatarPreview" alt="Avatar Preview" class="w-16 h-16 rounded-full object-cover ring-2 ring-purple-400 shadow-xs">
                        </template>
                        <template x-if="!avatarPreview">
                            @if(!empty($user->avatar_url))
                                <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="w-16 h-16 rounded-full object-cover ring-2 ring-purple-300 shadow-xs">
                            @else
                                <div class="w-16 h-16 rounded-full bg-gradient-to-tr from-purple-600 to-indigo-600 text-white flex items-center justify-center font-bold text-lg shadow-xs">
                                    {{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}
                                </div>
                            @endif
                        </template>

                        <!-- Hover Overlay Change Button -->
                        <label for="avatarInput" class="absolute inset-0 rounded-full bg-black/40 flex items-center justify-center text-white text-xs opacity-0 group-hover:opacity-100 transition cursor-pointer">
                            <i class="fas fa-camera"></i>
                        </label>
                    </div>

                    <div class="flex-1 text-center sm:text-left space-y-1">
                        <div class="flex items-center justify-center sm:justify-start gap-2">
                            <label for="avatarInput" class="px-3 py-1 bg-white hover:bg-slate-100 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200 shadow-2xs transition cursor-pointer inline-flex items-center gap-1.5">
                                <i class="fas fa-upload text-[10px]"></i>
                                <span>{{ __('Upload Photo') }}</span>
                            </label>
                            <input type="file" id="avatarInput" name="avatar" accept="image/*" class="hidden" @change="handleAvatarSelect($event)">

                            <template x-if="avatarPreview || '{{ $user->avatar ? 'true' : '' }}'">
                                <button type="button" @click="removeAvatar()" class="px-2.5 py-1 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-lg text-xs font-semibold border border-rose-200 transition cursor-pointer inline-flex items-center gap-1">
                                    <i class="fas fa-trash-alt text-[10px]"></i>
                                    <span>{{ __('Remove') }}</span>
                                </button>
                            </template>
                        </div>
                        <input type="hidden" name="remove_avatar" :value="removeAvatarFlag ? '1' : '0'">
                        <p class="text-[10.5px] text-slate-500">{{ __('Supports JPG, PNG or WEBP (Max 2MB). Recommended square aspect ratio.') }}</p>
                    </div>
                </div>

                <!-- Personal Information Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">{{ __('Full Name') }} <span class="text-rose-500">*</span></label>
                        <input type="text" name="user_name" value="{{ old('user_name', $user->name) }}" required
                               class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-purple-500 font-medium">
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">{{ __('Email Address (Login)') }} <span class="text-rose-500">*</span></label>
                        <input type="email" name="user_email" value="{{ old('user_email', $user->email) }}" required
                               class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-purple-500 font-medium font-mono">
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">{{ __('Mobile Number') }}</label>
                        <input type="text" name="user_mobile" value="{{ old('user_mobile', $user->mobile ?: $user->phone) }}"
                               class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-purple-500 font-medium font-mono" placeholder="017xxxxxxxx">
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">{{ __('Residential Address') }}</label>
                        <input type="text" name="address" value="{{ old('address', $user->address) }}"
                               class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-purple-500 font-medium" placeholder="House / Street, Area, City">
                    </div>
                </div>
            </div>

            <!-- Save Action Button -->
            <div class="flex items-center justify-end">
                <button type="submit" class="px-5 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                    <i class="fas fa-save"></i>
                    <span>{{ __('Save Profile Details') }}</span>
                </button>
            </div>
        </form>
    </div>

    {{-- TAB 2: SECURITY & PASSWORD UPDATE --}}
    <div x-show="activeTab === 'security'" class="space-y-4" style="display: none;">
        <form action="{{ route('tenant.profile.password') }}" method="POST" class="space-y-4">
            @csrf

            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs space-y-4">
                <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                    <h3 class="text-xs font-bold text-slate-800">{{ __('Change Account Password') }}</h3>
                    <span class="text-[10.5px] text-slate-400 font-medium">{{ __('Update your login credentials securely') }}</span>
                </div>

                <div class="max-w-md space-y-3.5">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">{{ __('Current Password') }} <span class="text-rose-500">*</span></label>
                        <input type="password" name="current_password" required
                               class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-purple-500 font-medium"
                               placeholder="••••••••">
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">{{ __('New Password') }} <span class="text-rose-500">*</span></label>
                        <input type="password" name="password" required minlength="6"
                               class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-purple-500 font-medium"
                               placeholder="{{ __('Minimum 6 characters') }}">
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">{{ __('Confirm New Password') }} <span class="text-rose-500">*</span></label>
                        <input type="password" name="password_confirmation" required minlength="6"
                               class="w-full bg-slate-50 border border-slate-200 rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-purple-500 font-medium"
                               placeholder="••••••••">
                    </div>
                </div>
            </div>

            <!-- Update Password Action Button -->
            <div class="flex items-center justify-end">
                <button type="submit" class="px-5 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                    <i class="fas fa-key"></i>
                    <span>{{ __('Update Password') }}</span>
                </button>
            </div>
        </form>
    </div>

</div>
@endsection

@push('scripts')
<script>
function profileManager() {
    return {
        activeTab: 'personal',
        avatarPreview: null,
        removeAvatarFlag: false,

        handleAvatarSelect(event) {
            const file = event.target.files[0];
            if (file) {
                this.avatarPreview = URL.createObjectURL(file);
                this.removeAvatarFlag = false;
            }
        },

        removeAvatar() {
            this.avatarPreview = null;
            this.removeAvatarFlag = true;
            const input = document.getElementById('avatarInput');
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
