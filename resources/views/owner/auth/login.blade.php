@extends('layouts.base')

@section('title', 'Platform Owner Login | SomitySoft SaaS')

@section('body')
<div class="min-h-screen flex items-center justify-center bg-slate-100/70 p-4 text-slate-800 antialiased selection:bg-blue-600 selection:text-white">
    
    <div class="w-full max-w-sm">
        
        <!-- Brand Header Logo -->
        <div class="text-center mb-6">
            @if(!empty($globalSettings['app_logo']))
                <div class="mb-2.5 flex justify-center">
                    <img src="{{ $globalSettings['app_logo'] }}" alt="{{ $globalSettings['app_name'] ?? 'SomitySoft' }}" class="h-10 max-w-[180px] object-contain">
                </div>
            @else
                <div class="inline-flex items-center justify-center w-11 h-11 rounded-xl bg-blue-600 shadow-xs mb-2.5 text-white">
                    <i class="fas fa-crown text-amber-300 text-lg"></i>
                </div>
            @endif
            <h1 class="text-lg font-bold text-slate-900 tracking-tight">{{ $globalSettings['login_screen_title'] ?? 'Platform Owner Login' }}</h1>
            <p class="text-[11px] text-slate-500">{{ $globalSettings['app_tagline'] ?? 'Sign in to the SaaS Super Admin Console' }}</p>
        </div>

        <!-- Login Form Card (Sleek Compact White Card) -->
        <div class="rounded-2xl bg-white border border-slate-200/80 p-5 sm:p-6 shadow-xs">
            
            <form action="{{ route('owner.login.submit') }}" method="POST" class="space-y-3.5">
                @csrf

                <!-- Email Input -->
                <div>
                    <label for="email" class="block text-[11px] font-semibold text-slate-600 mb-1">Email Address</label>
                    <div class="relative">
                        <i class="fas fa-envelope absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input type="email" 
                                id="email" 
                                name="email" 
                                value="{{ old('email', 'owner@somitysoft.com') }}" 
                                required 
                                autofocus
                                placeholder="owner@somitysoft.com" 
                                class="w-full pl-8 pr-3 py-1.5 rounded-lg border border-slate-200 bg-slate-50/50 text-slate-800 text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition placeholder:text-slate-400">
                    </div>
                    @error('email')
                        <p class="mt-1 text-[10px] text-rose-500 font-semibold">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password Input -->
                <div x-data="{ showPass: false }">
                    <label for="password" class="block text-[11px] font-semibold text-slate-600 mb-1">Password</label>
                    <div class="relative">
                        <i class="fas fa-lock absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input :type="showPass ? 'text' : 'password'" 
                                id="password" 
                                name="password" 
                                value="password"
                                required 
                                placeholder="••••••••" 
                                class="w-full pl-8 pr-8 py-1.5 rounded-lg border border-slate-200 bg-slate-50/50 text-slate-800 text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition placeholder:text-slate-400">
                        <button type="button" @click="showPass = !showPass" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition">
                            <i class="fas text-xs" :class="showPass ? 'fa-eye-slash' : 'fa-eye'"></i>
                        </button>
                    </div>
                    @error('password')
                        <p class="mt-1 text-[10px] text-rose-500 font-semibold">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Remember Me -->
                <div class="flex items-center justify-between pt-0.5">
                    <label class="flex items-center gap-1.5 cursor-pointer text-[11px] text-slate-500">
                        <input type="checkbox" name="remember" class="w-3.5 h-3.5 rounded border-slate-300 bg-slate-50 text-blue-600 focus:ring-0">
                        <span>Remember Me</span>
                    </label>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="w-full py-2 px-3 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs shadow-xs transition flex items-center justify-center gap-1.5">
                    <i class="fas fa-sign-in-alt text-[11px]"></i>
                    <span>Sign In to Owner Portal</span>
                </button>

            </form>

            <div class="mt-4 pt-3 border-t border-slate-100 text-center">
                <a href="{{ url('/') }}" class="text-[11px] text-slate-400 hover:text-slate-700 transition flex items-center justify-center gap-1">
                    <i class="fas fa-arrow-left text-[9px]"></i>
                    <span>Back to Main Website</span>
                </a>
            </div>

        </div>

    </div>

</div>
@endsection
