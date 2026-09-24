@extends('layouts.base')

@section('title', ($tenant ? $tenant->company_name : 'ISP Management System') . ' | Portal Login')

@section('body')
<div class="min-h-screen flex items-center justify-center bg-slate-100/70 p-4 text-slate-800 antialiased selection:bg-blue-600 selection:text-white">
    
    <div class="w-full max-w-md" x-data="{
        email: '{{ old('email', 'admin@speednet.com') }}',
        password: 'password',
        showPass: false,
        activeTab: 'isp',
        fillDemo(uEmail, uPass = 'password') {
            this.email = uEmail;
            this.password = uPass;
        }
    }">
        
        <!-- Brand Header Logo & Title -->
        <div class="text-center mb-5">
            @if($tenant && !empty($tenant->logo))
                <div class="mb-2 flex justify-center">
                    <img src="{{ $tenant->logo }}" alt="{{ $tenant->company_name }}" class="h-10 max-w-[180px] object-contain">
                </div>
            @else
                <div class="inline-flex items-center justify-center w-11 h-11 rounded-xl bg-cyan-600 shadow-xs mb-2 text-white">
                    <i class="fas fa-network-wired text-white text-base"></i>
                </div>
            @endif

            <h1 class="text-lg font-bold text-slate-900 tracking-tight">
                {{ $tenant ? $tenant->company_name : 'ISP Management System' }}
            </h1>
            <p class="text-[11px] text-slate-500 mt-0.5">
                Single Unified Portal Login (ISP Admin, Reseller & Staff)
            </p>
        </div>

        <!-- Login Card -->
        <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-5 sm:p-6 space-y-4">
            
            <!-- Success / Flash Messages -->
            @if(session('success'))
                <div class="p-2.5 bg-emerald-50 border border-emerald-200 rounded-lg text-emerald-800 text-[11px] flex items-center gap-1.5 shadow-2xs">
                    <i class="fas fa-check-circle text-emerald-600 text-xs"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if(session('warning'))
                <div class="p-2.5 bg-amber-50 border border-amber-200 rounded-lg text-amber-800 text-[11px] flex items-center gap-1.5 shadow-2xs">
                    <i class="fas fa-triangle-exclamation text-amber-600 text-xs"></i>
                    <span>{{ session('warning') }}</span>
                </div>
            @endif

            @if($errors->any())
                <div class="p-2.5 bg-rose-50 border border-rose-200 rounded-lg text-rose-800 text-[11px] space-y-0.5 shadow-2xs">
                    <div class="font-bold flex items-center gap-1">
                        <i class="fas fa-circle-exclamation text-rose-600 text-xs"></i>
                        <span>Authentication Failed</span>
                    </div>
                    <p class="text-[10.5px] text-rose-700">{{ $errors->first() }}</p>
                </div>
            @endif

            <form action="{{ route('tenant.login.submit') }}" method="POST" class="space-y-3.5">
                @csrf

                <!-- Email, Phone, Mobile or Username -->
                <div>
                    <label for="email" class="block text-[11px] font-semibold text-slate-700 mb-1">
                        Email, Mobile or Username
                    </label>
                    <div class="relative">
                        <i class="fas fa-user absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input type="text" 
                               id="email" 
                               name="email" 
                               x-model="email"
                               required 
                               autofocus
                               placeholder="user@example.com or 017xxxxxxxx" 
                               class="w-full pl-8 pr-3 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-800 text-xs focus:outline-none focus:ring-1 focus:ring-cyan-500 focus:border-cyan-500 transition placeholder:text-slate-400 shadow-2xs">
                    </div>
                </div>

                <!-- Password with Show/Hide Eye Toggle -->
                <div>
                    <label for="password" class="block text-[11px] font-semibold text-slate-700 mb-1">
                        Password
                    </label>
                    <div class="relative">
                        <i class="fas fa-lock absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input :type="showPass ? 'text' : 'password'" 
                               id="password" 
                               name="password" 
                               x-model="password"
                               required 
                               placeholder="••••••••" 
                               class="w-full pl-8 pr-8 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-800 text-xs focus:outline-none focus:ring-1 focus:ring-cyan-500 focus:border-cyan-500 transition placeholder:text-slate-400 shadow-2xs">
                        <button type="button" @click="showPass = !showPass" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition cursor-pointer">
                            <i class="fas text-[11px]" :class="showPass ? 'fa-eye-slash' : 'fa-eye'"></i>
                        </button>
                    </div>
                </div>

                <!-- Remember Me -->
                <div class="flex items-center justify-between pt-0.5 text-[11px]">
                    <label class="flex items-center gap-1.5 cursor-pointer text-slate-600">
                        <input type="checkbox" name="remember" class="w-3.5 h-3.5 rounded border-slate-300 bg-slate-50 text-cyan-600 focus:ring-0">
                        <span>Remember Me</span>
                    </label>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="w-full py-2 px-3 rounded-lg bg-cyan-600 hover:bg-cyan-700 text-white font-bold text-xs shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                    <i class="fas fa-sign-in-alt text-[11px]"></i>
                    <span>Sign In to Portal</span>
                </button>

            </form>

            <!-- Quick Demo Accounts Selector (1-Click Fill) -->
            <div class="pt-3 border-t border-slate-100 space-y-2.5">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 flex items-center gap-1">
                        <i class="fas fa-bolt text-amber-500 text-[10px]"></i> Quick Role Accounts
                    </span>
                    <div class="flex items-center gap-1 bg-slate-100 p-0.5 rounded-md text-[10px] font-semibold">
                        <button type="button" 
                                @click="activeTab = 'isp'" 
                                :class="activeTab === 'isp' ? 'bg-white text-cyan-700 shadow-2xs' : 'text-slate-500 hover:text-slate-700'" 
                                class="px-2 py-0.5 rounded transition cursor-pointer">
                            ISP HQ
                        </button>
                        <button type="button" 
                                @click="activeTab = 'reseller'" 
                                :class="activeTab === 'reseller' ? 'bg-white text-purple-700 shadow-2xs' : 'text-slate-500 hover:text-slate-700'" 
                                class="px-2 py-0.5 rounded transition cursor-pointer">
                            Reseller
                        </button>
                    </div>
                </div>

                <!-- ISP Role Accounts -->
                <div x-show="activeTab === 'isp'" class="grid grid-cols-2 gap-1.5 text-[10.5px]">
                    <button type="button" 
                            @click="fillDemo('admin@speednet.com')" 
                            :class="email === 'admin@speednet.com' ? 'border-cyan-500 bg-cyan-50/70 text-cyan-800' : 'border-slate-200 bg-slate-50/60 hover:bg-slate-100 text-slate-700'"
                            class="p-1.5 rounded-lg border text-left transition flex items-center gap-1.5 cursor-pointer">
                        <span class="w-5 h-5 rounded bg-cyan-600 text-white flex items-center justify-center text-[9px] font-bold shrink-0">AD</span>
                        <div class="truncate leading-tight">
                            <span class="font-bold block truncate">ISP Admin</span>
                            <span class="text-[9px] text-slate-400 truncate block">admin@speednet.com</span>
                        </div>
                    </button>

                    <button type="button" 
                            @click="fillDemo('mahmud.manager@somitysoft.com')" 
                            :class="email === 'mahmud.manager@somitysoft.com' ? 'border-blue-500 bg-blue-50/70 text-blue-800' : 'border-slate-200 bg-slate-50/60 hover:bg-slate-100 text-slate-700'"
                            class="p-1.5 rounded-lg border text-left transition flex items-center gap-1.5 cursor-pointer">
                        <span class="w-5 h-5 rounded bg-blue-600 text-white flex items-center justify-center text-[9px] font-bold shrink-0">MG</span>
                        <div class="truncate leading-tight">
                            <span class="font-bold block truncate">ISP Manager</span>
                            <span class="text-[9px] text-slate-400 truncate block">mahmud.manager</span>
                        </div>
                    </button>

                    <button type="button" 
                            @click="fillDemo('saiful.collector@somitysoft.com')" 
                            :class="email === 'saiful.collector@somitysoft.com' ? 'border-emerald-500 bg-emerald-50/70 text-emerald-800' : 'border-slate-200 bg-slate-50/60 hover:bg-slate-100 text-slate-700'"
                            class="p-1.5 rounded-lg border text-left transition flex items-center gap-1.5 cursor-pointer">
                        <span class="w-5 h-5 rounded bg-emerald-600 text-white flex items-center justify-center text-[9px] font-bold shrink-0">CL</span>
                        <div class="truncate leading-tight">
                            <span class="font-bold block truncate">ISP Collector</span>
                            <span class="text-[9px] text-slate-400 truncate block">saiful.collector</span>
                        </div>
                    </button>

                    <button type="button" 
                            @click="fillDemo('kamrul.tech@somitysoft.com')" 
                            :class="email === 'kamrul.tech@somitysoft.com' ? 'border-indigo-500 bg-indigo-50/70 text-indigo-800' : 'border-slate-200 bg-slate-50/60 hover:bg-slate-100 text-slate-700'"
                            class="p-1.5 rounded-lg border text-left transition flex items-center gap-1.5 cursor-pointer">
                        <span class="w-5 h-5 rounded bg-indigo-600 text-white flex items-center justify-center text-[9px] font-bold shrink-0">NC</span>
                        <div class="truncate leading-tight">
                            <span class="font-bold block truncate">ISP NOC/Tech</span>
                            <span class="text-[9px] text-slate-400 truncate block">kamrul.tech</span>
                        </div>
                    </button>
                </div>

                <!-- Reseller Role Accounts -->
                <div x-show="activeTab === 'reseller'" class="grid grid-cols-2 gap-1.5 text-[10.5px]" style="display: none;">
                    <button type="button" 
                            @click="fillDemo('partner@reseller.com')" 
                            :class="email === 'partner@reseller.com' ? 'border-purple-500 bg-purple-50/70 text-purple-800' : 'border-slate-200 bg-slate-50/60 hover:bg-slate-100 text-slate-700'"
                            class="p-1.5 rounded-lg border text-left transition flex items-center gap-1.5 cursor-pointer col-span-2">
                        <span class="w-5 h-5 rounded bg-purple-600 text-white flex items-center justify-center text-[9px] font-bold shrink-0">RS</span>
                        <div class="truncate leading-tight">
                            <span class="font-bold block truncate">Reseller Admin (Chittagong Cyber)</span>
                            <span class="text-[9px] text-slate-400 truncate block">partner@reseller.com</span>
                        </div>
                    </button>

                    <button type="button" 
                            @click="fillDemo('alamin.ctg@somitysoft.com')" 
                            :class="email === 'alamin.ctg@somitysoft.com' ? 'border-amber-500 bg-amber-50/70 text-amber-800' : 'border-slate-200 bg-slate-50/60 hover:bg-slate-100 text-slate-700'"
                            class="p-1.5 rounded-lg border text-left transition flex items-center gap-1.5 cursor-pointer">
                        <span class="w-5 h-5 rounded bg-amber-600 text-white flex items-center justify-center text-[9px] font-bold shrink-0">RC</span>
                        <div class="truncate leading-tight">
                            <span class="font-bold block truncate">Reseller Collector</span>
                            <span class="text-[9px] text-slate-400 truncate block">alamin.ctg</span>
                        </div>
                    </button>

                    <button type="button" 
                            @click="fillDemo('monir.ctg@somitysoft.com')" 
                            :class="email === 'monir.ctg@somitysoft.com' ? 'border-teal-500 bg-teal-50/70 text-teal-800' : 'border-slate-200 bg-slate-50/60 hover:bg-slate-100 text-slate-700'"
                            class="p-1.5 rounded-lg border text-left transition flex items-center gap-1.5 cursor-pointer">
                        <span class="w-5 h-5 rounded bg-teal-600 text-white flex items-center justify-center text-[9px] font-bold shrink-0">RT</span>
                        <div class="truncate leading-tight">
                            <span class="font-bold block truncate">Reseller Tech</span>
                            <span class="text-[9px] text-slate-400 truncate block">monir.ctg</span>
                        </div>
                    </button>
                </div>
            </div>

            <!-- Footer Links -->
            <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-[10.5px] text-slate-400">
                <a href="{{ route('owner.login') }}" class="hover:text-slate-600 transition flex items-center gap-1 font-medium">
                    <i class="fas fa-crown text-[9px] text-amber-500"></i>
                    <span>SaaS Admin Console</span>
                </a>
                <span class="text-slate-400 font-mono text-[10px]">Pass: password</span>
            </div>

        </div>

        <div class="mt-4 text-center text-[10.5px] text-slate-400">
            Powered by SomitySoft Enterprise ISP Management Engine
        </div>

    </div>

</div>
@endsection
