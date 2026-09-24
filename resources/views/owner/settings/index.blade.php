@extends('owner.layouts.app')

@section('page-title', 'General Settings')

@section('content')
<div class="space-y-4" x-data="{ activeTab: 'branding' }">
    <!-- Top Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-4 rounded-xl border border-slate-200/80 shadow-xs">
        <div>
            <h2 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                <i class="fas fa-sliders-h text-blue-600 text-xs"></i>
                General Platform Configuration & Branding
            </h2>
            <p class="text-[11px] text-slate-500 mt-0.5">Application logo, date-time localization, footer credits, trial rules, and general policies</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[10.5px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                <i class="fas fa-check-circle text-[9px]"></i>
                Version {{ $settings['app_version'] ?? 'v3.2 Enterprise' }}
            </span>
        </div>
    </div>


    <!-- Navigation Tabs (Sleek Compact) -->
    <div class="flex items-center gap-1 bg-white p-1 rounded-xl border border-slate-200/80 shadow-xs overflow-x-auto">
        <button type="button" @click="activeTab = 'branding'" 
                :class="activeTab === 'branding' ? 'bg-blue-50 text-blue-700 font-bold border-blue-200' : 'text-slate-600 hover:bg-slate-50 border-transparent'"
                class="px-3 py-1.5 rounded-lg text-xs transition border flex items-center gap-1.5 whitespace-nowrap">
            <i class="fas fa-palette text-[11px]"></i>
            <span>Logo & Branding</span>
        </button>

        <button type="button" @click="activeTab = 'localization'" 
                :class="activeTab === 'localization' ? 'bg-blue-50 text-blue-700 font-bold border-blue-200' : 'text-slate-600 hover:bg-slate-50 border-transparent'"
                class="px-3 py-1.5 rounded-lg text-xs transition border flex items-center gap-1.5 whitespace-nowrap">
            <i class="fas fa-calendar-alt text-[11px]"></i>
            <span>Date, Time & Currency</span>
        </button>

        <button type="button" @click="activeTab = 'footer'" 
                :class="activeTab === 'footer' ? 'bg-blue-50 text-blue-700 font-bold border-blue-200' : 'text-slate-600 hover:bg-slate-50 border-transparent'"
                class="px-3 py-1.5 rounded-lg text-xs transition border flex items-center gap-1.5 whitespace-nowrap">
            <i class="fas fa-code text-[11px]"></i>
            <span>Footer & Credits</span>
        </button>

        <button type="button" @click="activeTab = 'saas_policy'" 
                :class="activeTab === 'saas_policy' ? 'bg-blue-50 text-blue-700 font-bold border-blue-200' : 'text-slate-600 hover:bg-slate-50 border-transparent'"
                class="px-3 py-1.5 rounded-lg text-xs transition border flex items-center gap-1.5 whitespace-nowrap">
            <i class="fas fa-hand-holding-usd text-[11px]"></i>
            <span>SaaS Trial & Policies</span>
        </button>

        <button type="button" @click="activeTab = 'support'" 
                :class="activeTab === 'support' ? 'bg-blue-50 text-blue-700 font-bold border-blue-200' : 'text-slate-600 hover:bg-slate-50 border-transparent'"
                class="px-3 py-1.5 rounded-lg text-xs transition border flex items-center gap-1.5 whitespace-nowrap">
            <i class="fas fa-headset text-[11px]"></i>
            <span>Helpdesk & Support</span>
        </button>

        <button type="button" @click="activeTab = 'system_notice'" 
                :class="activeTab === 'system_notice' ? 'bg-blue-50 text-blue-700 font-bold border-blue-200' : 'text-slate-600 hover:bg-slate-50 border-transparent'"
                class="px-3 py-1.5 rounded-lg text-xs transition border flex items-center gap-1.5 whitespace-nowrap">
            <i class="fas fa-bullhorn text-[11px]"></i>
            <span>Maintenance & Notice</span>
        </button>
    </div>

    <!-- Main Settings Form -->
    <form action="{{ route('owner.settings.update') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
        @csrf
        <input type="hidden" name="group" value="general">

        <!-- TAB 1: Logo & Branding -->
        <div x-show="activeTab === 'branding'" class="space-y-4">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                
                <!-- Logo & Assets Upload Card -->
                <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs divide-y divide-slate-100 lg:col-span-1">
                    <div class="p-3.5 flex items-center justify-between bg-slate-50/50 rounded-t-xl">
                        <span class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                            <i class="fas fa-image text-blue-600"></i>
                            Brand Assets & Logo
                        </span>
                        <span class="text-[10px] text-slate-400">PNG / SVG / ICO</span>
                    </div>

                    <div class="p-4 space-y-4 text-xs">
                        <!-- Main Logo -->
                        <div class="space-y-2">
                            <label class="block text-[11px] font-semibold text-slate-700">Platform Main Logo</label>
                            <div class="flex items-center gap-3 p-3 bg-slate-50 border border-slate-200 rounded-lg">
                                <div class="w-12 h-12 bg-white rounded-md border border-slate-200 flex items-center justify-center overflow-hidden flex-shrink-0 p-1">
                                    @if(!empty($settings['app_logo']))
                                        <img src="{{ $settings['app_logo'] }}" alt="Logo" class="max-h-full max-w-full object-contain">
                                    @else
                                        <i class="fas fa-crown text-blue-600 text-xl"></i>
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <input type="file" name="app_logo" accept="image/*" class="w-full text-[11px] text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-md file:border-0 file:text-[11px] file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer">
                                    <p class="text-[9.5px] text-slate-400 mt-1">Recommended: 200 x 50 px (PNG/SVG)</p>
                                </div>
                            </div>
                        </div>

                        <!-- Favicon -->
                        <div class="space-y-2">
                            <label class="block text-[11px] font-semibold text-slate-700">Browser Favicon</label>
                            <div class="flex items-center gap-3 p-3 bg-slate-50 border border-slate-200 rounded-lg">
                                <div class="w-8 h-8 bg-white rounded-md border border-slate-200 flex items-center justify-center overflow-hidden flex-shrink-0 p-1">
                                    @if(!empty($settings['app_favicon']))
                                        <img src="{{ $settings['app_favicon'] }}" alt="Favicon" class="max-h-full max-w-full object-contain">
                                    @else
                                        <i class="fas fa-globe text-emerald-600 text-base"></i>
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <input type="file" name="app_favicon" accept="image/x-icon,image/png" class="w-full text-[11px] text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-md file:border-0 file:text-[11px] file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer">
                                    <p class="text-[9.5px] text-slate-400 mt-1">Recommended: 32 x 32 or 64 x 64 px (.ico/.png)</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Brand Details & Typography -->
                <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs divide-y divide-slate-100 lg:col-span-2">
                    <div class="p-3.5 flex items-center justify-between bg-slate-50/50 rounded-t-xl">
                        <span class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                            <i class="fas fa-id-card text-indigo-600"></i>
                            Platform Identity & App Names
                        </span>
                        <span class="text-[10px] text-slate-400">Core Identity</span>
                    </div>

                    <div class="p-4 space-y-3.5 text-xs">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-700 mb-1">Application Full Name</label>
                                <input type="text" name="app_name" value="{{ $settings['app_name'] ?? 'SomitySoft ISP Billing SaaS' }}" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-700 mb-1">Short / Navigation Name</label>
                                <input type="text" name="app_short_name" value="{{ $settings['app_short_name'] ?? 'SomitySoft' }}" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-blue-500">
                            </div>
                        </div>

                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Tagline</label>
                            <input type="text" name="app_tagline" value="{{ $settings['app_tagline'] ?? 'Next-Gen Multi-Tenant ISP Billing & Network Automation Platform' }}" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-blue-500">
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-700 mb-1">SaaS Domain URL</label>
                                <input type="url" name="app_website_url" value="{{ $settings['app_website_url'] ?? 'https://somitysoft.com' }}" placeholder="https://somitysoft.com" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-blue-500 font-mono">
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-700 mb-1">Login Screen Title</label>
                                <input type="text" name="login_screen_title" value="{{ $settings['login_screen_title'] ?? 'ISP Billing & Network Automation Cloud' }}" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-blue-500">
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- TAB 2: Date, Time & Currency -->
        <div x-show="activeTab === 'localization'" class="space-y-4" x-cloak>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                
                <!-- Date & Time Formats -->
                <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs divide-y divide-slate-100">
                    <div class="p-3.5 flex items-center justify-between bg-slate-50/50 rounded-t-xl">
                        <span class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                            <i class="fas fa-clock text-blue-600"></i>
                            Date & Time Localization
                        </span>
                        <span class="text-[10px] text-slate-400">Timezone & Formats</span>
                    </div>

                    <div class="p-4 space-y-3.5 text-xs">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">System Timezone</label>
                            <select name="app_timezone" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-blue-500">
                                <option value="Asia/Dhaka" {{ ($settings['app_timezone'] ?? 'Asia/Dhaka') === 'Asia/Dhaka' ? 'selected' : '' }}>Asia/Dhaka (GMT+6 - Bangladesh Standard Time)</option>
                                <option value="UTC" {{ ($settings['app_timezone'] ?? '') === 'UTC' ? 'selected' : '' }}>UTC (GMT+0 - Coordinated Universal Time)</option>
                                <option value="Asia/Kolkata" {{ ($settings['app_timezone'] ?? '') === 'Asia/Kolkata' ? 'selected' : '' }}>Asia/Kolkata (GMT+5:30 - Indian Standard Time)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Date Format</label>
                            <select name="date_format" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-blue-500">
                                <option value="d M Y" {{ ($settings['date_format'] ?? 'd M Y') === 'd M Y' ? 'selected' : '' }}>{{ date('d M Y') }} (d M Y - e.g. 04 Sep 2026)</option>
                                <option value="d/m/Y" {{ ($settings['date_format'] ?? '') === 'd/m/Y' ? 'selected' : '' }}>{{ date('d/m/Y') }} (d/m/Y - e.g. 04/09/2026)</option>
                                <option value="Y-m-d" {{ ($settings['date_format'] ?? '') === 'Y-m-d' ? 'selected' : '' }}>{{ date('Y-m-d') }} (Y-m-d - ISO Standard)</option>
                                <option value="M d, Y" {{ ($settings['date_format'] ?? '') === 'M d, Y' ? 'selected' : '' }}>{{ date('M d, Y') }} (M d, Y - e.g. Sep 04, 2026)</option>
                                <option value="d-m-Y" {{ ($settings['date_format'] ?? '') === 'd-m-Y' ? 'selected' : '' }}>{{ date('d-m-Y') }} (d-m-Y - e.g. 04-09-2026)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Time Format</label>
                            <select name="time_format" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-blue-500">
                                <option value="h:i A" {{ ($settings['time_format'] ?? 'h:i A') === 'h:i A' ? 'selected' : '' }}>12-Hour Format — {{ date('h:i A') }} (AM/PM)</option>
                                <option value="H:i" {{ ($settings['time_format'] ?? '') === 'H:i' ? 'selected' : '' }}>24-Hour Format — {{ date('H:i') }} (Military)</option>
                                <option value="h:i:s A" {{ ($settings['time_format'] ?? '') === 'h:i:s A' ? 'selected' : '' }}>12-Hour with Seconds — {{ date('h:i:s A') }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Default System Language</label>
                            <select name="app_locale" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-blue-500">
                                <option value="en" {{ ($settings['app_locale'] ?? 'en') === 'en' ? 'selected' : '' }}>English (US - Default)</option>
                                <option value="bn" {{ ($settings['app_locale'] ?? '') === 'bn' ? 'selected' : '' }}>বাংলা (Bangla)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Currency & Financial Formatting -->
                <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs divide-y divide-slate-100">
                    <div class="p-3.5 flex items-center justify-between bg-slate-50/50 rounded-t-xl">
                        <span class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                            <i class="fas fa-coins text-amber-500"></i>
                            Currency & Monetary Display
                        </span>
                        <span class="text-[10px] text-slate-400">Financial Symbols</span>
                    </div>

                    <div class="p-4 space-y-3.5 text-xs">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-700 mb-1">Currency Code</label>
                                <input type="text" name="currency_code" value="{{ $settings['currency_code'] ?? 'BDT' }}" placeholder="BDT" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-blue-500 font-mono">
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-700 mb-1">Currency Symbol</label>
                                <input type="text" name="currency_symbol" value="{{ $settings['currency_symbol'] ?? '৳' }}" placeholder="৳" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-blue-500 font-bold">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-700 mb-1">Symbol Placement</label>
                                <select name="currency_position" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-blue-500">
                                    <option value="left" {{ ($settings['currency_position'] ?? 'left') === 'left' ? 'selected' : '' }}>Left (e.g. ৳ 1,500)</option>
                                    <option value="right" {{ ($settings['currency_position'] ?? '') === 'right' ? 'selected' : '' }}>Right (e.g. 1,500 ৳)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-700 mb-1">Decimal Places</label>
                                <select name="currency_decimals" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-blue-500">
                                    <option value="2" {{ ($settings['currency_decimals'] ?? '2') === '2' ? 'selected' : '' }}>2 Decimals (e.g. 1,500.00)</option>
                                    <option value="0" {{ ($settings['currency_decimals'] ?? '') === '0' ? 'selected' : '' }}>Zero Decimals (e.g. 1,500)</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-700 mb-1">Thousand Separator</label>
                                <select name="thousand_separator" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-blue-500 font-mono">
                                    <option value="," {{ ($settings['thousand_separator'] ?? ',') === ',' ? 'selected' : '' }}>Comma ( , ) — 1,000,000</option>
                                    <option value="." {{ ($settings['thousand_separator'] ?? '') === '.' ? 'selected' : '' }}>Dot ( . ) — 1.000.000</option>
                                    <option value="space" {{ ($settings['thousand_separator'] ?? '') === 'space' ? 'selected' : '' }}>Space ( ) — 1 000 000</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-700 mb-1">Decimal Separator</label>
                                <select name="decimal_separator" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-blue-500 font-mono">
                                    <option value="." {{ ($settings['decimal_separator'] ?? '.') === '.' ? 'selected' : '' }}>Dot ( . ) — .00</option>
                                    <option value="," {{ ($settings['decimal_separator'] ?? '') === ',' ? 'selected' : '' }}>Comma ( , ) — ,00</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- TAB 3: Footer & Development Credits -->
        <div x-show="activeTab === 'footer'" class="space-y-4" x-cloak>
            <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs divide-y divide-slate-100">
                <div class="p-3.5 flex items-center justify-between bg-slate-50/50 rounded-t-xl">
                    <span class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                        <i class="fas fa-copyright text-blue-600"></i>
                        Admin & Tenant Console Footer Copyright & Credits
                    </span>
                    <span class="text-[10px] text-slate-400">White-label & Credits</span>
                </div>

                <div class="p-4 space-y-3.5 text-xs">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Footer Copyright Notice</label>
                            <input type="text" name="footer_copyright_text" value="{{ $settings['footer_copyright_text'] ?? '© 2026 SomitySoft SaaS. All rights reserved.' }}" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Software Version Tag</label>
                            <input type="text" name="app_version" value="{{ $settings['app_version'] ?? 'v3.2 Enterprise' }}" placeholder="v3.2 Enterprise" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-blue-500 font-mono">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Developer Credit Text</label>
                            <input type="text" name="footer_developed_by" value="{{ $settings['footer_developed_by'] ?? 'Developed with ❤️ by SomitySoft Technologies' }}" placeholder="Developed with ❤️ by SomitySoft" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Developer Official Website Link</label>
                            <input type="url" name="footer_developer_url" value="{{ $settings['footer_developer_url'] ?? 'https://somitysoft.com' }}" placeholder="https://somitysoft.com" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-blue-500 font-mono">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">Customer Portal & Payment Receipt Footer Notice</label>
                        <input type="text" name="customer_portal_footer" value="{{ $settings['customer_portal_footer'] ?? 'Powered by SomitySoft Cloud ISP Automation Platform' }}" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-blue-500">
                    </div>

                    <div class="flex items-center gap-2 pt-1 border-t border-slate-100">
                        <input type="checkbox" name="show_developer_credit" id="show_developer_credit" value="1" {{ ($settings['show_developer_credit'] ?? '1') == '1' ? 'checked' : '' }} class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        <label for="show_developer_credit" class="text-[11px] font-semibold text-slate-700 cursor-pointer">
                            Display developer credit link across all owner and tenant console footers
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 4: SaaS Trial & Subscription Policies -->
        <div x-show="activeTab === 'saas_policy'" class="space-y-4" x-cloak>
            <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs divide-y divide-slate-100">
                <div class="p-3.5 flex items-center justify-between bg-slate-50/50 rounded-t-xl">
                    <span class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                        <i class="fas fa-hand-holding-usd text-emerald-600"></i>
                        SaaS Subscription, Trial & Sign-Up Rules
                    </span>
                    <span class="text-[10px] text-slate-400">Tenancy Lifecycle</span>
                </div>

                <div class="p-4 space-y-3.5 text-xs">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">New Tenant Free Trial (Days)</label>
                            <input type="number" name="default_trial_days" value="{{ $settings['default_trial_days'] ?? '14' }}" placeholder="14" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-blue-500 font-mono">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Billing Grace Period (Days)</label>
                            <input type="number" name="billing_grace_days" value="{{ $settings['billing_grace_days'] ?? '3' }}" placeholder="3" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-blue-500 font-mono">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Advance Invoice Generation (Days)</label>
                            <input type="number" name="invoice_generate_days_before" value="{{ $settings['invoice_generate_days_before'] ?? '7' }}" placeholder="7" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-blue-500 font-mono">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Invoice Number Prefix</label>
                            <input type="text" name="invoice_prefix" value="{{ $settings['invoice_prefix'] ?? 'INV-' }}" placeholder="INV-" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-blue-500 font-mono">
                            <p class="text-[10px] text-slate-500 mt-1">
                                Invoices emailed from: <span class="font-mono text-blue-600 font-semibold">{{ $settings['billing_email'] ?? 'billing@somitysoft.com' }}</span>
                            </p>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Auto-Suspension Policy</label>
                            <select name="auto_suspend_tenant" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-blue-500">
                                <option value="1" {{ ($settings['auto_suspend_tenant'] ?? '1') == '1' ? 'selected' : '' }}>Automatic Lock after Grace Period</option>
                                <option value="0" {{ ($settings['auto_suspend_tenant'] ?? '') == '0' ? 'selected' : '' }}>Manual Suspension Only</option>
                            </select>
                        </div>
                    </div>

                    <div class="space-y-2 pt-1 border-t border-slate-100">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="allow_self_registration" value="1" {{ ($settings['allow_self_registration'] ?? '1') == '1' ? 'checked' : '' }} class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                            <span class="text-[11px] font-semibold text-slate-700">Allow self registration for new ISP tenants on website landing page</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="require_email_verification" value="1" {{ ($settings['require_email_verification'] ?? '0') == '1' ? 'checked' : '' }} class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                            <span class="text-[11px] font-semibold text-slate-700">Enforce email verification upon new tenant sign-up</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 5: Helpdesk & Support Contacts -->
        <div x-show="activeTab === 'support'" class="space-y-4" x-cloak>
            <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs divide-y divide-slate-100">
                <div class="p-3.5 flex items-center justify-between bg-slate-50/50 rounded-t-xl">
                    <span class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                        <i class="fas fa-headset text-indigo-600"></i>
                        Helpdesk, Customer Support & Headquarters
                    </span>
                    <span class="text-[10px] text-slate-400">Owner Contact Info</span>
                </div>

                <div class="p-4 space-y-3.5 text-xs">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1 flex items-center justify-between">
                                <span>Official Billing Email</span>
                                <span class="text-[9px] font-semibold text-blue-600 bg-blue-50 border border-blue-200 px-1.5 py-0.5 rounded">Invoices</span>
                            </label>
                            <input type="email" name="billing_email" value="{{ $settings['billing_email'] ?? 'billing@somitysoft.com' }}" placeholder="billing@somitysoft.com" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-blue-500 font-medium">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Official Support Email</label>
                            <input type="email" name="support_email" value="{{ $settings['support_email'] ?? 'support@somitysoft.com' }}" placeholder="support@somitysoft.com" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Support Hotline / Phone</label>
                            <input type="text" name="support_phone" value="{{ $settings['support_phone'] ?? '+880 1700-000000' }}" placeholder="+880 1700-000000" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-blue-500 font-mono">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">WhatsApp Support Number</label>
                            <input type="text" name="support_whatsapp" value="{{ $settings['support_whatsapp'] ?? '+880 1700-000000' }}" placeholder="+880 1700-000000" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-blue-500 font-mono">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">Headquarters Office Address</label>
                        <input type="text" name="company_address" value="{{ $settings['company_address'] ?? 'Dhaka, Bangladesh' }}" placeholder="Road #1, Block #A, Dhaka" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">Knowledgebase / Documentation Portal URL</label>
                        <input type="url" name="support_docs_url" value="{{ $settings['support_docs_url'] ?? 'https://somitysoft.com/docs' }}" placeholder="https://somitysoft.com/docs" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-blue-500 font-mono">
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 6: Maintenance & System Broadcast -->
        <div x-show="activeTab === 'system_notice'" class="space-y-4" x-cloak>
            <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs divide-y divide-slate-100">
                <div class="p-3.5 flex items-center justify-between bg-slate-50/50 rounded-t-xl">
                    <span class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                        <i class="fas fa-bullhorn text-rose-600"></i>
                        Platform Maintenance Mode & Global Tenant Announcements
                    </span>
                    <span class="text-[10px] text-slate-400">Emergency & Broadcasts</span>
                </div>

                <div class="p-4 space-y-3.5 text-xs">
                    <div class="p-3 bg-amber-50/70 border border-amber-200 rounded-lg space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-slate-800 text-xs flex items-center gap-1.5">
                                <i class="fas fa-tools text-amber-600"></i>
                                Maintenance Mode
                            </span>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="maintenance_mode" value="1" {{ ($settings['maintenance_mode'] ?? '0') == '1' ? 'checked' : '' }} class="sr-only peer">
                                <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-amber-600"></div>
                            </label>
                        </div>
                        <p class="text-[10.5px] text-slate-600">When enabled, tenants and end-users will see a maintenance screen while owners maintain full access.</p>
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">Maintenance Notice Message</label>
                        <textarea name="maintenance_message" rows="2" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-blue-500">{{ $settings['maintenance_message'] ?? 'We are currently performing scheduled platform upgrades. We will be back online shortly.' }}</textarea>
                    </div>

                    <div class="pt-2 border-t border-slate-100 space-y-2">
                        <div class="flex items-center gap-2">
                            <input type="checkbox" name="announcement_enabled" id="announcement_enabled" value="1" {{ ($settings['announcement_enabled'] ?? '0') == '1' ? 'checked' : '' }} class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                            <label for="announcement_enabled" class="text-[11px] font-semibold text-slate-700 cursor-pointer">
                                Broadcast a global emergency announcement banner on all tenant dashboards
                            </label>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Global Broadcast Announcement Text</label>
                            <input type="text" name="announcement_text" value="{{ $settings['announcement_text'] ?? '' }}" placeholder="Notice: Core network upgrades scheduled for Saturday midnight." class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs focus:ring-1 focus:ring-blue-500">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Global Submit Button -->
        <div class="flex items-center justify-between bg-white p-3.5 rounded-xl border border-slate-200/80 shadow-xs">
            <span class="text-[11px] text-slate-500">
                <i class="fas fa-info-circle text-blue-500 mr-1"></i>
                All settings updates take effect immediately platform-wide.
            </span>
            <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5">
                <i class="fas fa-save text-[11px]"></i>
                <span>Save Platform Settings</span>
            </button>
        </div>

    </form>
</div>
@endsection
