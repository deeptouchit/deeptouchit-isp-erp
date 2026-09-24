@extends('reseller.layouts.app')

@section('title', 'Customer Profile - ' . ($tenant->company_name ?? $tenant->name))

@push('styles')
<style>
    /* Scroll container with Center Alignment & Tooltip Clearance */
    .card-header-scroll {
        width: 100%;
        display: flex;
        justify-content: center;
        align-items: center;
        overflow: visible !important;
        padding: 0;
        margin: 0;
    }

    /* Centered Button Group */
    .card-header-scroll .btn-group {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
        padding: 0;
        margin: 0 auto;
        white-space: nowrap;
        overflow: visible !important;
    }

    /* Professional Fixed Compact Icon Button */
    .btn-hdr {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 30px;
        height: 30px;
        border-radius: 6px;
        font-size: 11.5px;
        color: #ffffff !important;
        text-decoration: none;
        cursor: pointer !important;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        border: 1px solid transparent;
        transition: transform 0.15s ease, box-shadow 0.15s ease, filter 0.15s ease;
        flex-shrink: 0;
    }

    .btn-hdr:hover {
        transform: translateY(-1.5px);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.15), 0 2px 4px -1px rgba(0, 0, 0, 0.08);
        filter: brightness(1.12);
        z-index: 50;
    }

    .btn-hdr i {
        font-size: 12px;
        flex-shrink: 0;
    }

    /* Bottom Floating Tooltip Popup (Clean Light Theme) */
    .btn-hdr[data-title]::before {
        content: attr(data-title);
        position: absolute;
        top: calc(100% + 8px);
        left: 50%;
        transform: translateX(-50%) translateY(-4px);
        background: #ffffff;
        color: #0f172a;
        font-size: 11px;
        font-weight: 600;
        letter-spacing: -0.01em;
        white-space: nowrap;
        padding: 4px 10px;
        border-radius: 6px;
        box-shadow: 0 10px 25px -3px rgba(15, 23, 42, 0.15), 0 4px 6px -4px rgba(15, 23, 42, 0.08);
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transition: opacity 0.15s ease, transform 0.15s ease, visibility 0.15s ease;
        z-index: 99999;
        border: 1px solid #cbd5e1;
        line-height: 1.3;
    }

    /* Bottom Floating Tooltip Arrow */
    .btn-hdr[data-title]::after {
        content: '';
        position: absolute;
        top: calc(100% + 3px);
        left: 50%;
        transform: translateX(-50%) translateY(-4px);
        border-width: 0 5px 5px 5px;
        border-style: solid;
        border-color: transparent transparent #cbd5e1 transparent;
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transition: opacity 0.15s ease, transform 0.15s ease, visibility 0.15s ease;
        z-index: 99999;
    }

    .btn-hdr:hover[data-title]::before,
    .btn-hdr:hover[data-title]::after {
        opacity: 1;
        visibility: visible;
        transform: translateX(-50%) translateY(0);
    }

    /* Color Palette */
    .bg-lightblue { background-color: #0284c7; }
    .bg-navy { background-color: #1e293b; }
    .bg-purple { background-color: #7c3aed; }
    .bg-fuchsia { background-color: #c026d3; }
    .bg-pink { background-color: #db2777; }
    .bg-orange { background-color: #ea580c; }
    .bg-lime { background-color: #65a30d; }
    .bg-teal { background-color: #0d9488; }
    .bg-olive { background-color: #4d7c0f; }
    .bg-indigo { background-color: #4f46e5; }
    .bg-amber { background-color: #d97706; }
</style>
@endpush

@php
    $currentPackage = $customer->package ?: (
        $customer->package_id ? $allPackages->firstWhere('id', $customer->package_id) : (
            $customer->package_name ? ($allPackages->firstWhere('name', $customer->package_name) ?? $allPackages->firstWhere('package_name', $customer->package_name)) : null
        )
    );

    $actionConfig = [
        'customerId' => (int) $customer->id,
        'customerStatus' => (string) $customer->status,
        'pppoeUsername' => (string) ($customer->username ?? ''),
        'pppoePassword' => (string) ($customer->password ?? ''),
        'ipAddress' => (string) ($customer->ip_address ?? '127.0.0.1'),
        'packageId' => (string) ($currentPackage?->id ?? ($customer->package_id ?? '')),
        'packageName' => (string) ($currentPackage ? ($currentPackage->package_name ?: $currentPackage->name) : ($customer->package_name ?: 'Custom Plan')),
        'packageCode' => (string) ($currentPackage?->name ?? ''),
        'packageSpeed' => (string) ($currentPackage ? ($currentPackage->download_speed . '/' . $currentPackage->upload_speed . ' Mbps') : ''),
        'monthlyBill' => (string) number_format((float)($customer->monthly_bill ?? ($currentPackage?->price ?? 0)), 2, '.', ''),
        'expiryDate' => (string) ($customer->expiry_date ? $customer->expiry_date->format('Y-m-d') : ''),
        'expiryDateFormatted' => (string) ($customer->expiry_date ? $customer->expiry_date->format('d M Y') : ''),
        'billingType' => (string) strtolower($customer->billing_type ?: 'prepaid'),
        'gracePeriodDays' => (int) ($customer->grace_period_days ?? 0),
        'autoCutEnabled' => (bool) $customer->auto_cut_enabled,
        'dueAmount' => (string) number_format((float)($customer->due_amount ?? 0), 2, '.', ''),
        'isReseller' => (bool) $customer->reseller_id,
        'resellerId' => (string) ($customer->reseller_id ?? ''),
        'resellerName' => (string) ($customer->reseller?->name ?? ''),
        'resellerCode' => (string) ($customer->reseller?->code ?? ''),
        'resellerBalance' => (float) ($customer->reseller?->wallet_balance ?? 0),
        'resellerCreditLimit' => (float) ($customer->reseller?->credit_limit ?? 0),
        'resellerCommissionRate' => (float) ($customer->reseller?->commission_rate ?? 0),
        'isOnline' => (bool) ($customer->is_online ?? false),
        'remarks' => (string) ($customer->remarks ?? ''),
    ];
@endphp

@section('content')
<div class="space-y-3" 
     x-data="customerActionBarManager({{ json_encode($actionConfig) }})"
     @keydown.escape.window="closeAllModals()">

    <!-- Toast Notification Banner -->
    <div x-show="toast.show" 
         x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200 transform"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2"
         class="fixed top-4 right-4 z-50 flex items-center gap-2 px-4 py-2.5 rounded-xl shadow-lg border text-xs font-medium"
         :class="{
             'bg-emerald-50 text-emerald-800 border-emerald-200': toast.type === 'success',
             'bg-rose-50 text-rose-800 border-rose-200': toast.type === 'error',
             'bg-blue-50 text-blue-800 border-blue-200': toast.type === 'info'
         }"
         style="display: none;">
        <i class="fas text-sm" :class="{
            'fa-check-circle text-emerald-600': toast.type === 'success',
            'fa-exclamation-circle text-rose-600': toast.type === 'error',
            'fa-info-circle text-blue-600': toast.type === 'info'
        }"></i>
        <span x-text="toast.message"></span>
    </div>

    <!-- Header Action Buttons Strip Centered -->
    <div class="bg-white px-3 py-1 rounded-lg border border-slate-200 shadow-2xs flex items-center justify-center relative overflow-visible z-10">
        <div class="card-header-scroll">
            <div class="btn-group">

                @if(!auth()->user()?->isResellerCollector())
                <!-- 1. Update PPPoE credentials -->
                <button type="button" 
                        @click="openPppoeModal()" 
                        class="btn-hdr bg-lightblue cursor-pointer" 
                        data-title="{{ __('Update PPPoE Credentials') }}">
                    <i class="fas fa-user"></i>
                </button>

                <!-- 2. Enable/Disable -->
                <button type="button" 
                        @click="openStatusModal()" 
                        class="btn-hdr bg-navy cursor-pointer" 
                        data-title="{{ __('Enable / Disable Line') }}">
                    <i class="fas" :class="statusForm.status === 'active' ? 'fa-toggle-on text-emerald-400' : 'fa-toggle-off text-rose-400'"></i>
                </button>
                @endif

                <!-- 3. RX/TX (Real-time Graphical Bandwidth) -->
                <button type="button" 
                        @click="openRxtxModal()" 
                        class="btn-hdr bg-purple cursor-pointer" 
                        data-title="{{ __('Live RX/TX Bandwidth') }}">
                    <i class="fas fa-chart-bar"></i>
                </button>

                @if(!auth()->user()?->isResellerCollector())
                <!-- 4. Change Package -->
                <button type="button" 
                        @click="openPackageModal()" 
                        class="btn-hdr bg-fuchsia cursor-pointer" 
                        data-title="{{ __('Change Package') }}">
                    <i class="fas fa-box"></i>
                </button>
                @endif

                <!-- 5. Pay Now -->
                <button type="button" 
                        @click="openPayModal()" 
                        class="btn-hdr bg-teal cursor-pointer" 
                        data-title="{{ __('Pay Bill (Collect Payment)') }}">
                    <i class="fas fa-money-bill-wave"></i>
                </button>

                <!-- 6. Note for Customer -->
                <button type="button" 
                        @click="openNoteModal()" 
                        class="btn-hdr bg-amber cursor-pointer" 
                        data-title="{{ __('Note for Customer') }}">
                    <i class="fas fa-clipboard"></i>
                </button>

                <!-- 7. Back to Customer List -->
                <a href="{{ route('reseller.customers.index') }}" 
                   class="btn-hdr bg-purple cursor-pointer" 
                   data-title="{{ __('Back to Customer List') }}">
                    <i class="fas fa-arrow-left"></i>
                </a>

            </div>
        </div>
    </div>

    <!-- Main 2-Section Grid Layout (Left: 3 Cards in 12 Cols | Right: 1 Card in 12 Cols) -->
    <div class="grid grid-cols-12 gap-3.5 items-start">
        
        <!-- Left Section (9 of 12 Columns on Large Screens) -->
        <div class="col-span-12 lg:col-span-9 space-y-3.5">
            <div class="grid grid-cols-12 gap-3.5 items-stretch">
                
                <!-- 1. Customer Profile & Contact Information Card (4 of 12 Columns in Left Section) -->
                <div class="col-span-12 sm:col-span-6 md:col-span-4 bg-white rounded-md border border-slate-200 shadow-2xs overflow-hidden flex flex-col justify-between border-t-2 border-t-cyan-600 h-full">
                    <div class="p-3 flex-1 flex flex-col justify-between space-y-2.5">
                        <div class="space-y-2.5">
                            <!-- Profile Header -->
                            <div class="flex items-center gap-2.5">
                                @if(!empty($customer->photo) && file_exists(public_path($customer->photo)))
                                    <img src="{{ asset($customer->photo) }}" 
                                         alt="{{ $customer->name }}" 
                                         class="w-10 h-10 rounded-full object-cover border border-cyan-200 shadow-2xs flex-shrink-0">
                                @else
                                    <div class="w-10 h-10 rounded-full bg-cyan-50 border border-cyan-200 text-cyan-600 flex flex-col items-center justify-center shadow-2xs flex-shrink-0">
                                        <i class="fas fa-image text-xs text-cyan-500 mb-0.5"></i>
                                        <span class="text-[6px] uppercase tracking-tighter text-slate-400 font-bold">{{ __('NO IMAGE') }}</span>
                                    </div>
                                @endif
                                <div class="min-w-0 flex-1">
                                    <h3 class="text-xs font-bold text-slate-900 tracking-tight leading-snug truncate" title="{{ $customer->name }}">
                                        {{ $customer->name }}
                                    </h3>
                                    <span class="inline-flex items-center px-1.5 py-0.5 mt-0.5 rounded text-[9.5px] font-semibold font-mono bg-slate-100 text-slate-700 border border-slate-200">
                                        ID: {{ $customer->customer_id ?? ('CUS-' . $customer->id) }}
                                    </span>
                                </div>
                            </div>

                            <div class="border-t border-slate-100"></div>

                            <!-- Attributes List with Perfectly Aligned Colons (Straight Vertical Line) -->
                            <div class="space-y-1.5 text-[11px]">
                                <!-- Guardian -->
                                <div class="flex items-center leading-tight whitespace-nowrap">
                                    <div class="w-[100px] flex items-center gap-1.5 text-slate-500 flex-shrink-0">
                                        <i class="fas fa-user-shield text-slate-400 w-3.5 text-center text-[10px]"></i>
                                        <span class="text-[10px] font-medium text-slate-600">{{ __('Guardian') }}</span>
                                    </div>
                                    <span class="text-slate-400 font-bold px-1 flex-shrink-0">:</span>
                                    <span class="font-semibold text-slate-800 text-right flex-1 truncate text-[11px] pl-1">
                                        {{ $customer->father_name ?: 'N/A' }}
                                    </span>
                                </div>

                                <!-- Mobile -->
                                <div class="flex items-center leading-tight whitespace-nowrap">
                                    <div class="w-[100px] flex items-center gap-1.5 text-slate-500 flex-shrink-0">
                                        <i class="fas fa-mobile-alt text-slate-400 w-3.5 text-center text-[10px]"></i>
                                        <span class="text-[10px] font-medium text-slate-600">{{ __('Mobile') }}</span>
                                    </div>
                                    <span class="text-slate-400 font-bold px-1 flex-shrink-0">:</span>
                                    @if($customer->phone)
                                        <a href="tel:{{ $customer->phone }}" class="font-bold text-blue-600 hover:underline font-mono text-right flex-1 truncate text-[11px] pl-1">
                                            {{ $customer->phone }}
                                        </a>
                                    @else
                                        <span class="font-medium text-slate-400 text-right flex-1 truncate text-[11px] pl-1">N/A</span>
                                    @endif
                                </div>

                                <!-- Reseller -->
                                <div class="flex items-center leading-tight whitespace-nowrap">
                                    <div class="w-[100px] flex items-center gap-1.5 text-slate-500 flex-shrink-0">
                                        <i class="fas fa-store text-rose-500 w-3.5 text-center text-[10px]"></i>
                                        <span class="text-[10px] font-medium text-slate-600">{{ __('Reseller') }}</span>
                                    </div>
                                    <span class="text-slate-400 font-bold px-1 flex-shrink-0">:</span>
                                    <span class="font-semibold text-slate-800 text-right flex-1 truncate text-[11px] pl-1" x-text="transferData.currentResellerName || '{{ $customer->reseller?->name ?? 'Direct ISP' }}'">
                                        {{ $customer->reseller?->name ?? 'Direct ISP' }}
                                    </span>
                                </div>

                                <!-- Area / Zone -->
                                <div class="flex items-center leading-tight whitespace-nowrap">
                                    <div class="w-[100px] flex items-center gap-1.5 text-slate-500 flex-shrink-0">
                                        <i class="fas fa-map-marked-alt text-blue-500 w-3.5 text-center text-[10px]"></i>
                                        <span class="text-[10px] font-medium text-slate-600">{{ __('Area / Zone') }}</span>
                                    </div>
                                    <span class="text-slate-400 font-bold px-1 flex-shrink-0">:</span>
                                    <span class="font-semibold text-slate-800 text-right flex-1 truncate text-[11px] pl-1">
                                        {{ $customer->zone ?: 'N/A' }}
                                    </span>
                                </div>

                                <!-- Sub Area -->
                                <div class="flex items-center leading-tight whitespace-nowrap">
                                    <div class="w-[100px] flex items-center gap-1.5 text-slate-500 flex-shrink-0">
                                        <i class="fas fa-map-signs text-amber-500 w-3.5 text-center text-[10px]"></i>
                                        <span class="text-[10px] font-medium text-slate-600">{{ __('Sub Area') }}</span>
                                    </div>
                                    <span class="text-slate-400 font-bold px-1 flex-shrink-0">:</span>
                                    <span class="font-semibold text-slate-800 text-right flex-1 truncate text-[11px] pl-1">
                                        {{ $customer->thana ?: ($customer->sub_zone ?: 'N/A') }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Address Section aligned at bottom -->
                        <div class="space-y-1 mt-auto pt-1">
                            <div class="border-t border-slate-100"></div>
                            <div class="space-y-0.5">
                                <span class="text-[10px] font-medium text-slate-500 block">{{ __('Address') }}:</span>
                                <p class="text-[10.5px] font-normal text-slate-700 leading-relaxed bg-slate-50/80 p-1.5 rounded border border-slate-200/70 truncate" title="{{ $customer->address ?: __('No address specified.') }}">
                                    {{ $customer->address ?: __('No address specified.') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Card Footer Button -->
                    <a href="{{ route('reseller.customers.edit', $customer->id) }}" 
                       class="w-full py-1.5 bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-[11px] flex items-center justify-center gap-1.5 transition shadow-2xs">
                        <i class="fas fa-edit text-[10px]"></i>
                        <span>{{ __('Update Profile') }}</span>
                    </a>
                </div>

                <!-- 2. Subscription & Package Information Card (4 of 12 Columns in Left Section) -->
                <div class="col-span-12 sm:col-span-6 md:col-span-4 bg-white rounded-md border border-slate-200 shadow-2xs overflow-hidden flex flex-col justify-between border-t-2 border-t-blue-600 h-full">
                    <div class="p-3 flex-1 flex flex-col justify-between space-y-2.5">
                        <div class="space-y-2.5">
                            <!-- Subscription Header -->
                            <div class="flex items-center gap-2.5">
                                <div class="w-10 h-10 rounded-full bg-blue-50 border border-blue-200 text-blue-600 flex items-center justify-center shadow-2xs flex-shrink-0">
                                    <i class="fas fa-cube text-base text-blue-600"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <h3 class="text-xs font-bold text-slate-900 tracking-tight leading-snug truncate" 
                                        x-text="packageData.currentPackageName || '{{ addslashes($customer->packageDisplayName) }}'"
                                        title="{{ $customer->packageDisplayName }}">
                                        {{ $customer->packageDisplayName }}
                                    </h3>
                                    <span class="inline-flex items-center px-1.5 py-0.5 mt-0.5 rounded text-[9.5px] font-semibold font-mono uppercase border {{ $customer->status === 'active' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : ($customer->status === 'expired' ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-rose-50 text-rose-700 border border-rose-200') }}"
                                          :class="statusForm.status === 'active' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : (statusForm.status === 'expired' ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-rose-50 text-rose-700 border-rose-200')"
                                          x-text="statusForm.status.toUpperCase()">
                                        {{ strtoupper($customer->status) }}
                                    </span>
                                </div>
                            </div>

                            <div class="border-t border-slate-100"></div>

                            <!-- Attributes List with Perfectly Aligned Colons (Straight Vertical Line) -->
                            <div class="space-y-1.5 text-[11px]">
                                <!-- Bandwidth / Speed -->
                                <div class="flex items-center leading-tight whitespace-nowrap">
                                    <div class="w-[100px] flex items-center gap-1.5 text-slate-500 flex-shrink-0">
                                        <i class="fas fa-tachometer-alt text-cyan-500 w-3.5 text-center text-[10px]"></i>
                                        <span class="text-[10px] font-medium text-slate-600">{{ __('Bandwidth') }}</span>
                                    </div>
                                    <span class="text-slate-400 font-bold px-1 flex-shrink-0">:</span>
                                    <span class="font-semibold text-slate-800 text-right flex-1 truncate text-[11px] pl-1 font-mono" 
                                          x-text="packageData.currentSpeed || '{{ $currentPackage ? ($currentPackage->download_speed . '/' . $currentPackage->upload_speed . ' Mbps') : ($customer->bandwidth ?: 'N/A') }}'">
                                        {{ $currentPackage ? ($currentPackage->download_speed . '/' . $currentPackage->upload_speed . ' Mbps') : ($customer->bandwidth ?: 'N/A') }}
                                    </span>
                                </div>

                                <!-- Monthly Fee -->
                                <div class="flex items-center leading-tight whitespace-nowrap">
                                    <div class="w-[100px] flex items-center gap-1.5 text-slate-500 flex-shrink-0">
                                        <i class="fas fa-money-bill-wave text-emerald-500 w-3.5 text-center text-[10px]"></i>
                                        <span class="text-[10px] font-medium text-slate-600">{{ __('Monthly Fee') }}</span>
                                    </div>
                                    <span class="text-slate-400 font-bold px-1 flex-shrink-0">:</span>
                                    <span class="font-bold text-emerald-700 text-right flex-1 truncate text-[11px] pl-1 font-mono">
                                        {{ $currencySymbol ?? '৳' }} <span x-text="Number(packageData.currentMonthlyBill || {{ (float)($customer->monthly_bill ?? ($currentPackage?->price ?? 0)) }}).toFixed(2)">{{ number_format((float)($customer->monthly_bill ?? ($currentPackage?->price ?? 0)), 2) }}</span>
                                    </span>
                                </div>

                                <!-- Billing Type -->
                                <div class="flex items-center leading-tight whitespace-nowrap">
                                    <div class="w-[100px] flex items-center gap-1.5 text-slate-500 flex-shrink-0">
                                        <i class="fas fa-credit-card text-blue-500 w-3.5 text-center text-[10px]"></i>
                                        <span class="text-[10px] font-medium text-slate-600">{{ __('Billing Type') }}</span>
                                    </div>
                                    <span class="text-slate-400 font-bold px-1 flex-shrink-0">:</span>
                                    <span class="font-semibold text-slate-800 text-right flex-1 truncate text-[11px] pl-1 capitalize font-mono"
                                          x-text="(cycleData.currentBillingType || '{{ $customer->billing_type }}').toUpperCase()">
                                        {{ strtoupper($customer->billing_type) }}
                                    </span>
                                </div>

                                <!-- Expire Date -->
                                <div class="flex items-center leading-tight whitespace-nowrap">
                                    <div class="w-[100px] flex items-center gap-1.5 text-slate-500 flex-shrink-0">
                                        <i class="fas fa-calendar-times text-rose-500 w-3.5 text-center text-[10px]"></i>
                                        <span class="text-[10px] font-medium text-slate-600">{{ __('Expire Date') }}</span>
                                    </div>
                                    <span class="text-slate-400 font-bold px-1 flex-shrink-0">:</span>
                                    <span class="font-bold text-slate-800 text-right flex-1 truncate text-[11px] pl-1 font-mono"
                                          x-text="formatDate(cycleData.currentExpiryDate) || '{{ $customer->expiry_date?->format('d M Y') ?? 'N/A' }}'">
                                        {{ $customer->expiry_date?->format('d M Y') ?? 'N/A' }}
                                    </span>
                                </div>

                                <!-- Grace Period (Ensured single line without wrapping) -->
                                <div class="flex items-center leading-tight whitespace-nowrap">
                                    <div class="w-[100px] flex items-center gap-1.5 text-slate-500 flex-shrink-0">
                                        <i class="fas fa-hourglass-half text-amber-500 w-3.5 text-center text-[10px]"></i>
                                        <span class="text-[10px] font-medium text-slate-600">{{ __('Grace Period') }}</span>
                                    </div>
                                    <span class="text-slate-400 font-bold px-1 flex-shrink-0">:</span>
                                    <span class="font-semibold text-slate-800 text-right flex-1 truncate text-[11px] pl-1 font-mono">
                                        <span x-text="cycleData.grace_period_days ?? '{{ $customer->grace_period_days ?? 0 }}'">{{ $customer->grace_period_days ?? 0 }}</span> {{ __('Days') }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Auto-Cut Status Box aligned at bottom -->
                        <div class="space-y-1 mt-auto pt-1">
                            <div class="border-t border-slate-100"></div>
                            <div class="flex items-center justify-between p-1.5 rounded bg-slate-50/80 border border-slate-200/70">
                                <span class="text-[10px] font-medium text-slate-600">{{ __('Auto-Cut System') }}:</span>
                                <span class="px-1.5 py-0.5 rounded text-[9.5px] font-bold font-mono uppercase border"
                                      :class="(cycleData.auto_cut_enabled ?? {{ $customer->auto_cut_enabled ? 'true' : 'false' }}) ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'"
                                      x-text="(cycleData.auto_cut_enabled ?? {{ $customer->auto_cut_enabled ? 'true' : 'false' }}) ? 'ENABLED' : 'DISABLED'">
                                    {{ $customer->auto_cut_enabled ? 'ENABLED' : 'DISABLED' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Card Footer Button -->
                    <button type="button" 
                            @click="openPackageModal()" 
                            class="w-full py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-[11px] flex items-center justify-center gap-1.5 transition shadow-2xs cursor-pointer">
                        <i class="fas fa-exchange-alt text-[10px]"></i>
                        <span>{{ __('Change Package') }}</span>
                    </button>
                </div>

                <!-- 3. Online/Offline & Realtime Telemetry Card (4 of 12 Columns in Left Section) -->
                <div class="col-span-12 sm:col-span-6 md:col-span-4 bg-white rounded-md border border-slate-200 shadow-2xs overflow-hidden flex flex-col justify-between border-t-2 border-t-emerald-600 h-full">
                    <div class="p-3 flex-1 flex flex-col justify-between space-y-2.5">
                        <div class="space-y-2.5">
                            <!-- Telemetry Header -->
                            <div class="flex items-center gap-2.5">
                                <div class="w-10 h-10 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-600 flex items-center justify-center shadow-2xs flex-shrink-0">
                                    <i class="fas fa-network-wired text-base transition-colors" :class="isLivePolling ? 'text-emerald-500 animate-pulse' : 'text-emerald-600'"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <h3 class="text-xs font-bold text-slate-900 tracking-tight leading-snug truncate" title="{{ __('Network & Telemetry') }}">
                                        {{ __('Network & Telemetry') }}
                                    </h3>
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 mt-0.5 rounded text-[9.5px] font-semibold font-mono uppercase border" 
                                          :class="traffic.is_online ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-600 border-slate-200'">
                                        <span class="w-1.5 h-1.5 rounded-full" :class="traffic.is_online ? 'bg-emerald-500 animate-ping' : 'bg-slate-400'"></span>
                                        <span x-text="traffic.is_online ? 'ONLINE' : 'OFFLINE'">{{ ($customer->is_online ?? false) ? 'ONLINE' : 'OFFLINE' }}</span>
                                    </span>
                                </div>
                            </div>

                            <div class="border-t border-slate-100"></div>

                            <!-- Attributes List with Perfectly Aligned Colons (Straight Vertical Line) -->
                            <div class="space-y-1.5 text-[11px]">
                                <!-- PPPoE Username -->
                                <div class="flex items-center leading-tight whitespace-nowrap">
                                    <div class="w-[100px] flex items-center gap-1.5 text-slate-500 flex-shrink-0">
                                        <i class="fas fa-user-tag text-purple-500 w-3.5 text-center text-[10px]"></i>
                                        <span class="text-[10px] font-medium text-slate-600">{{ __('PPPoE') }}</span>
                                    </div>
                                    <span class="text-slate-400 font-bold px-1 flex-shrink-0">:</span>
                                    <span class="font-bold text-slate-800 text-right flex-1 truncate text-[11px] pl-1 font-mono">
                                        {{ $customer->username ?: 'N/A' }}
                                    </span>
                                </div>

                                <!-- Password -->
                                <div class="flex items-center leading-tight whitespace-nowrap">
                                    <div class="w-[100px] flex items-center gap-1.5 text-slate-500 flex-shrink-0">
                                        <i class="fas fa-key text-amber-500 w-3.5 text-center text-[10px]"></i>
                                        <span class="text-[10px] font-medium text-slate-600">{{ __('Password') }}</span>
                                    </div>
                                    <span class="text-slate-400 font-bold px-1 flex-shrink-0">:</span>
                                    <div class="flex items-center justify-end gap-1.5 flex-1 truncate pl-1">
                                        <span class="font-mono text-[11px] font-semibold text-slate-800 truncate" 
                                              x-text="showPassword ? '{{ addslashes($customer->password ?? '') }}' : '••••••••'">
                                            ••••••••
                                        </span>
                                        <button type="button" 
                                                @click="showPassword = !showPassword" 
                                                class="text-slate-400 hover:text-slate-700 text-[10px] focus:outline-none cursor-pointer"
                                                :title="showPassword ? 'Hide Password' : 'Show Password'">
                                            <i class="fas" :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- IP Address (Click to open Router Remote Access in new tab) -->
                                <div class="flex items-center leading-tight whitespace-nowrap">
                                    <div class="w-[100px] flex items-center gap-1.5 text-slate-500 flex-shrink-0">
                                        <i class="fas fa-laptop-code text-blue-500 w-3.5 text-center text-[10px]"></i>
                                        <span class="text-[10px] font-medium text-slate-600">{{ __('IP Address') }}</span>
                                    </div>
                                    <span class="text-slate-400 font-bold px-1 flex-shrink-0">:</span>
                                    <div class="flex items-center justify-end gap-1 flex-1 truncate pl-1">
                                        <a :href="getRouterRemoteUrl(traffic.ip || '{{ $customer->ip_address }}')" 
                                           :target="(traffic.ip && traffic.ip !== 'Dynamic / Auto' && traffic.ip !== '127.0.0.1') ? '_blank' : '_self'" 
                                           rel="noopener noreferrer" 
                                           class="inline-flex items-center gap-1 font-bold text-blue-600 hover:text-blue-800 hover:underline font-mono text-[11px] truncate cursor-pointer group"
                                           :title="'Open Router Remote Access (' + getRouterRemoteUrl(traffic.ip || '{{ $customer->ip_address }}') + ') in new tab'">
                                            <span x-text="traffic.ip || '{{ $customer->ip_address ?: 'Dynamic / Auto' }}'">
                                                {{ $customer->ip_address ?: 'Dynamic / Auto' }}
                                            </span>
                                            <i class="fas fa-external-link-alt text-[8px] text-blue-400 group-hover:text-blue-600"></i>
                                        </a>
                                    </div>
                                </div>

                                <!-- Uptime (Professional Human-Readable Format) -->
                                <div class="flex items-center leading-tight whitespace-nowrap">
                                    <div class="w-[100px] flex items-center gap-1.5 text-slate-500 flex-shrink-0">
                                        <i class="fas fa-stopwatch text-emerald-500 w-3.5 text-center text-[10px]"></i>
                                        <span class="text-[10px] font-medium text-slate-600">{{ __('Uptime') }}</span>
                                    </div>
                                    <span class="text-slate-400 font-bold px-1 flex-shrink-0">:</span>
                                    <div class="flex items-center justify-end flex-1 truncate pl-1">
                                        <span class="font-bold font-mono text-[11px] truncate"
                                              :class="traffic.is_online ? 'text-emerald-700' : 'text-slate-400'"
                                              x-text="formatUptime(traffic.uptime)">
                                            Offline
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Router / NAS Box aligned at bottom -->
                        <div class="space-y-1 mt-auto pt-1">
                            <div class="border-t border-slate-100"></div>
                            <div class="flex items-center justify-between p-1.5 rounded bg-slate-50/80 border border-slate-200/70">
                                <span class="text-[10px] font-medium text-slate-600">{{ __('Router / NAS') }}:</span>
                                <span class="px-1.5 py-0.5 rounded text-[9.5px] font-bold font-mono text-emerald-700 bg-emerald-50 border border-emerald-200 truncate max-w-[150px]"
                                      title="{{ $customer->router?->name ?? 'Default Gateway' }}">
                                    {{ $customer->router?->name ?? 'Default Gateway' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Card Footer Button -->
                    <button type="button" 
                            @click="openPppoeModal()" 
                            class="w-full py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-[11px] flex items-center justify-center gap-1.5 transition shadow-2xs cursor-pointer">
                        <i class="fas fa-key text-[10px]"></i>
                        <span>{{ __('Change Credentials') }}</span>
                    </button>
                </div>
            </div>

            <!-- 5. User Financial Ledger, Invoices & Session History Section (Below the 3 Info Cards in Left Section) -->
            @php
                $formatSessionBytes = function($bytes) {
                    if (!$bytes || $bytes <= 0) return '0 B';
                    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
                    $i = min(4, (int)floor(log((float)$bytes, 1024)));
                    return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
                };
                $formatDurationSecs = function($seconds) {
                    if (!$seconds || $seconds <= 0) return '0s';
                    $h = floor($seconds / 3600);
                    $m = floor(($seconds % 3600) / 60);
                    $s = $seconds % 60;
                    $res = [];
                    if ($h > 0) $res[] = "{$h}h";
                    if ($m > 0) $res[] = "{$m}m";
                    if ($s > 0 || empty($res)) $res[] = "{$s}s";
                    return implode(' ', $res);
                };
            @endphp

            <!-- 5. User Billing Invoices & Money Receipts Card -->
            <div class="bg-white rounded-md border border-slate-200 shadow-2xs overflow-hidden border-t-2 border-t-teal-600">
                <!-- Header with Quick Action -->
                <div class="p-2.5 border-b border-slate-200 flex items-center justify-between gap-2 bg-slate-50/70">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded bg-teal-50 border border-teal-200 text-teal-600 flex items-center justify-center shadow-2xs flex-shrink-0">
                            <i class="fas fa-file-invoice-dollar text-xs"></i>
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 tracking-tight leading-snug">
                             {{ __('Invoices') }}
                        </h3>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="px-2 py-0.5 rounded text-[9.5px] font-mono bg-teal-100 text-teal-800 font-bold border border-teal-200">
                            {{ __('Latest') }} {{ $recentPayments->count() }} {{ __('Invoices') }}
                        </span>
                        <button type="button" 
                                @click="openPayModal()" 
                                class="px-2.5 py-1 bg-teal-600 hover:bg-teal-700 text-white font-semibold text-[11px] rounded transition flex items-center gap-1.5 shadow-2xs cursor-pointer">
                            <i class="fas fa-money-bill-wave text-[10px]"></i>
                            <span>{{ __('Collect Payment') }}</span>
                        </button>
                    </div>
                </div>

                <!-- Invoices & Payment Money Receipts Table -->
                @if($recentPayments->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="saas-table w-full">
                            <thead>
                                <tr>
                                    <th class="w-8 text-center">#</th>
                                    <th>{{ __('Invoice') }}</th>
                                    <th>{{ __('Month') }}</th>
                                    <th class="text-right">{{ __('Paid Amount') }}</th>
                                    <th>{{ __('Payment Method') }}</th>
                                    <th>{{ __('Paid Date') }}</th>
                                    <th class="text-center">{{ __('Status') }}</th>
                                    <th class="text-center w-20 no-sort">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentPayments->take(12) as $idx => $payment)
                                    <tr>
                                        <td class="text-center text-slate-400 font-mono">{{ $idx + 1 }}</td>
                                        <td class="font-mono font-bold text-slate-800">
                                            {{ $payment->invoice_no }}
                                        </td>
                                        <td class="font-medium text-slate-700">
                                            {{ $payment->billing_month ?: 'Regular' }}
                                        </td>
                                        <td class="text-right font-mono font-bold text-emerald-700">
                                            @currency($payment->amount)
                                        </td>
                                        <td class="capitalize">
                                            <span class="inline-flex items-center gap-1 text-[10.5px] font-medium text-slate-700">
                                                @if(in_array(strtolower($payment->payment_method), ['bkash', 'nagad', 'rocket']))
                                                    <i class="fas fa-mobile-alt text-pink-500 text-[10px]"></i>
                                                @elseif(strtolower($payment->payment_method) === 'cash')
                                                    <i class="fas fa-money-bill-wave text-emerald-500 text-[10px]"></i>
                                                @elseif(strtolower($payment->payment_method) === 'bank_transfer')
                                                    <i class="fas fa-university text-blue-500 text-[10px]"></i>
                                                @else
                                                    <i class="fas fa-credit-card text-indigo-500 text-[10px]"></i>
                                                @endif
                                                <span>{{ $payment->payment_method_name }}</span>
                                            </span>
                                        </td>
                                        <td class="font-mono text-slate-600 text-[10.5px]">
                                            {{ $payment->paid_at ? $payment->paid_at->format('d M Y, h:i A') : $payment->created_at->format('d M Y, h:i A') }}
                                        </td>
                                        <td class="text-center">
                                            @php
                                                $badge = $payment->status_badge;
                                            @endphp
                                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[9.5px] font-bold font-mono uppercase {{ $badge['class'] ?? 'bg-emerald-50 text-emerald-700 border border-emerald-200' }}">
                                                <span class="w-1.5 h-1.5 rounded-full {{ $badge['dot'] ?? 'bg-emerald-500' }}"></span>
                                                <span>{{ $badge['label'] ?? strtoupper($payment->status) }}</span>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="flex items-center justify-center gap-1">
                                                @if(strtolower($payment->status) === 'pending')
                                                    <button type="button" 
                                                            @click="approvePayment({{ $payment->id }}, '{{ $payment->invoice_no }}')"
                                                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-[10px] transition shadow-2xs cursor-pointer"
                                                            title="{{ __('Approve Payment & Activate Line') }}">
                                                        <i class="fas fa-check-circle text-[9px]"></i>
                                                        <span>{{ __('Approve') }}</span>
                                                    </button>
                                                @endif
                                                <button type="button" 
                                                        @click="openPaymentDetails({{ json_encode([
                                                            'id' => $payment->id,
                                                            'invoice_no' => $payment->invoice_no,
                                                            'billing_month' => $payment->billing_month ?: 'Regular Month',
                                                            'amount' => (float)$payment->amount,
                                                            'amount_formatted' => ($currencySymbol ?? '৳') . ' ' . number_format((float)$payment->amount, 2),
                                                            'discount' => (float)$payment->discount,
                                                            'discount_formatted' => ($currencySymbol ?? '৳') . ' ' . number_format((float)$payment->discount, 2),
                                                            'total_formatted' => ($currencySymbol ?? '৳') . ' ' . number_format((float)$payment->amount + (float)$payment->discount, 2),
                                                            'payment_method' => $payment->payment_method_name,
                                                            'transaction_id' => $payment->transaction_id ?: 'N/A',
                                                            'collector_name' => $payment->collector?->name ?: 'Admin',
                                                            'paid_at' => $payment->paid_at ? $payment->paid_at->format('d M Y, h:i A') : $payment->created_at->format('d M Y, h:i A'),
                                                            'notes' => $payment->notes,
                                                            'receipt_url' => Route::has('reseller.collections.receipt') ? route('reseller.collections.receipt', $payment->id) : '#',
                                                        ]) }})" 
                                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-slate-100 hover:bg-teal-50 hover:text-teal-700 border border-slate-200 text-slate-700 font-semibold text-[10px] transition shadow-2xs cursor-pointer"
                                                        title="{{ __('View Invoice & Payment Details') }}">
                                                    <i class="fas fa-eye text-[9px]"></i>
                                                    <span>{{ __('Details') }}</span>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="p-8 text-center space-y-2.5">
                        <div class="w-12 h-12 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center mx-auto text-slate-400">
                            <i class="fas fa-file-invoice-dollar text-xl text-teal-600"></i>
                        </div>
                        <div class="space-y-0.5">
                            <h4 class="text-xs font-bold text-slate-700">{{ __('No Billing Transactions Recorded') }}</h4>
                            <p class="text-[11px] text-slate-500">{{ __('Collect bill payment to generate official money receipts and record customer ledger transactions.') }}</p>
                        </div>
                        <button type="button" 
                                @click="openPayModal()" 
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-teal-600 hover:bg-teal-700 text-white font-semibold text-xs rounded-lg transition shadow-2xs cursor-pointer">
                            <i class="fas fa-receipt text-[10px]"></i>
                            <span>{{ __('Collect First Payment') }}</span>
                        </button>
                    </div>
                @endif
            </div>

            <!-- 6. Dedicated RADIUS PPPoE Sessions & Connection History Card -->
            <div class="bg-white rounded-md border border-slate-200 shadow-2xs overflow-hidden border-t-2 border-t-blue-600">
                <!-- Header -->
                <div class="p-2.5 border-b border-slate-200 flex items-center justify-between gap-2 bg-slate-50/70">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded bg-blue-50 border border-blue-200 text-blue-600 flex items-center justify-center shadow-2xs flex-shrink-0">
                            <i class="fas fa-network-wired text-xs"></i>
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 tracking-tight leading-snug">
                            {{ __('RADIUS PPPoE Sessions & Connection History') }}
                        </h3>
                    </div>

                    <span class="px-2 py-0.5 rounded text-[9.5px] font-mono bg-blue-100 text-blue-800 font-bold border border-blue-200">
                        {{ __('Latest') }} {{ $recentSessions->count() }} {{ __('Sessions') }}
                    </span>
                </div>

                <!-- RADIUS Online Sessions Table -->
                @if($recentSessions->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="saas-table w-full">
                            <thead>
                                <tr>
                                    <th class="w-8 text-center">#</th>
                                    <th>{{ __('Framed IP') }}</th>
                                    <th>{{ __('Caller MAC (Device)') }}</th>
                                    <th>{{ __('NAS / Router IP') }}</th>
                                    <th>{{ __('Connected (Start)') }}</th>
                                    <th>{{ __('Disconnected (Stop)') }}</th>
                                    <th>{{ __('Duration') }}</th>
                                    <th class="text-right">{{ __('Download (Rx)') }}</th>
                                    <th class="text-right">{{ __('Upload (Tx)') }}</th>
                                    <th class="text-right">{{ __('Total Usage') }}</th>
                                    <th class="text-center">{{ __('Termination Cause') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentSessions->take(12) as $sIdx => $session)
                                    @php
                                        $rx = (int)($session->acctoutputoctets ?? 0);
                                        $tx = (int)($session->acctinputoctets ?? 0);
                                        $tot = $rx + $tx;
                                        $isLiveSession = empty($session->acctstoptime);
                                    @endphp
                                    <tr>
                                        <td class="text-center text-slate-400 font-mono">{{ $sIdx + 1 }}</td>
                                        <td class="font-mono font-bold text-slate-800">
                                            {{ $session->framedipaddress ?: 'Dynamic' }}
                                        </td>
                                        <td class="font-mono text-slate-600 text-[10.5px]">
                                            {{ $session->callingstationid ?: 'N/A' }}
                                        </td>
                                        <td class="font-mono text-slate-600 text-[10.5px]">
                                            {{ $session->nasipaddress ?: '127.0.0.1' }}
                                        </td>
                                        <td class="font-mono text-slate-600 text-[10.5px]">
                                            {{ $session->acctstarttime ? Carbon\Carbon::parse($session->acctstarttime)->format('d M Y, h:i A') : 'N/A' }}
                                        </td>
                                        <td class="font-mono text-slate-600 text-[10.5px]">
                                            @if($isLiveSession)
                                                <span class="inline-flex items-center gap-1 text-emerald-600 font-bold font-sans text-[10px]">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-ping"></span>
                                                    {{ __('Active Session') }}
                                                </span>
                                            @else
                                                {{ Carbon\Carbon::parse($session->acctstoptime)->format('d M Y, h:i A') }}
                                            @endif
                                        </td>
                                        <td class="font-mono text-slate-800 font-semibold text-[10.5px]">
                                            {{ $formatDurationSecs((int)($session->acctsessiontime ?? 0)) }}
                                        </td>
                                        <td class="text-right font-mono font-bold text-emerald-700">
                                            {{ $formatSessionBytes($rx) }}
                                        </td>
                                        <td class="text-right font-mono font-bold text-blue-700">
                                            {{ $formatSessionBytes($tx) }}
                                        </td>
                                        <td class="text-right font-mono font-bold text-slate-900">
                                            {{ $formatSessionBytes($tot) }}
                                        </td>
                                        <td class="text-center">
                                            @if($isLiveSession)
                                                <span class="px-1.5 py-0.5 rounded text-[9px] font-bold font-mono uppercase bg-emerald-50 text-emerald-700 border border-emerald-200">ONLINE</span>
                                            @else
                                                <span class="px-1.5 py-0.5 rounded text-[9px] font-medium font-mono bg-slate-100 text-slate-600 border border-slate-200">{{ $session->acctterminatecause ?: 'User-Request' }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="p-8 text-center space-y-2">
                        <div class="w-12 h-12 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center mx-auto text-slate-400">
                            <i class="fas fa-network-wired text-xl text-blue-600"></i>
                        </div>
                        <h4 class="text-xs font-bold text-slate-700">{{ __('No RADIUS Session Records Found') }}</h4>
                        <p class="text-[11px] text-slate-500">{{ __('Historical PPPoE login and accounting sessions will appear here.') }}</p>
                    </div>
                @endif
            </div>

            <!-- 7. Dedicated Activity & Audit Trail Table Card -->
            <div class="bg-white rounded-md border border-slate-200 shadow-2xs overflow-hidden border-t-2 border-t-purple-600">
                <!-- Header -->
                <div class="p-2.5 border-b border-slate-200 flex items-center justify-between gap-2 bg-slate-50/70">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded bg-purple-50 border border-purple-200 text-purple-600 flex items-center justify-center shadow-2xs flex-shrink-0">
                            <i class="fas fa-history text-xs"></i>
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 tracking-tight leading-snug">
                            {{ __('Customer Activity & Audit Trail') }}
                        </h3>
                    </div>

                    <span class="px-2 py-0.5 rounded text-[9.5px] font-mono bg-purple-100 text-purple-800 font-bold border border-purple-200">
                        {{ __('Latest') }} {{ $activityLogs->count() }} {{ __('Log Entries') }}
                    </span>
                </div>

                <!-- Audit Trail Table -->
                @if($activityLogs->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="saas-table w-full">
                            <thead>
                                <tr>
                                    <th class="w-8 text-center">#</th>
                                    <th>{{ __('Event Type') }}</th>
                                    <th>{{ __('Activity Description') }}</th>
                                    <th>{{ __('IP Address') }}</th>
                                    <th>{{ __('Timestamp') }}</th>
                                    <th class="text-center w-20 no-sort">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($activityLogs->take(12) as $lIdx => $log)
                                    <tr>
                                        <td class="text-center text-slate-400 font-mono">{{ $lIdx + 1 }}</td>
                                        <td class="font-mono font-bold text-cyan-800 text-[10.5px]">
                                            {{ $log->event_type ?: 'ACTIVITY' }}
                                        </td>
                                        <td class="text-slate-800 text-[11px] truncate max-w-[320px]" title="{{ $log->description }}">
                                            {{ Str::limit($log->description, 30) }}
                                        </td>
                                        <td class="font-mono text-slate-500 text-[10.5px]">
                                            {{ $log->ip_address ?: '127.0.0.1' }}
                                        </td>
                                        <td class="font-mono text-slate-600 text-[10.5px]">
                                            {{ $log->created_at->format('d M Y, h:i A') }}
                                        </td>
                                        <td class="text-center">
                                            <button type="button" 
                                                    @click="openLogDetails({{ json_encode([
                                                        'id' => $log->id,
                                                        'event_type' => $log->event_type ?: 'ACTIVITY',
                                                        'description' => $log->description,
                                                        'actor_name' => $log->actor_name ?: 'System Admin',
                                                        'actor_type' => $log->actor_type ? class_basename($log->actor_type) : 'User',
                                                        'ip_address' => $log->ip_address ?: '127.0.0.1',
                                                        'user_agent' => $log->user_agent ?: 'N/A',
                                                        'metadata' => $log->metadata,
                                                        'created_at' => $log->created_at->format('d M Y, h:i:s A'),
                                                    ]) }})"
                                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-slate-100 hover:bg-purple-50 hover:text-purple-700 border border-slate-200 text-slate-700 font-semibold text-[10px] transition shadow-2xs cursor-pointer"
                                                    title="{{ __('View Activity Details') }}">
                                                <i class="fas fa-eye text-[9px]"></i>
                                                <span>{{ __('Details') }}</span>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="p-8 text-center space-y-2">
                        <div class="w-12 h-12 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center mx-auto text-slate-400">
                            <i class="fas fa-history text-xl text-purple-600"></i>
                        </div>
                        <h4 class="text-xs font-bold text-slate-700">{{ __('No Activity Logs Found') }}</h4>
                        <p class="text-[11px] text-slate-500">{{ __('System actions and billing transactions related to this subscriber will be recorded here.') }}</p>
                    </div>
                @endif
            </div>
        </div>

        @php
            $onu = $customer->onu_device;
        @endphp

        <!-- Right Section (3 of 12 Columns on Large Screens) -->
        <div class="col-span-12 lg:col-span-3 space-y-3.5">
            
            <!-- MikroTik Traffic Graph Card (Placed directly above Device & Optical Details) -->
            <div class="w-full bg-white rounded-md border border-slate-200 shadow-2xs overflow-hidden flex flex-col justify-between border-t-2 border-t-indigo-600">
                <div class="p-3 space-y-2.5">
                    <!-- Clean Header: Icon + Title ONLY (Master Rule compliant: No Cross, No Subtitle) -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded bg-indigo-50 border border-indigo-200 text-indigo-600 flex items-center justify-center shadow-2xs flex-shrink-0">
                                <i class="fas fa-chart-area text-xs text-indigo-600"></i>
                            </div>
                            <h3 class="text-xs font-bold text-slate-900 tracking-tight leading-snug truncate" title="{{ __('Traffic Graph') }}">
                                {{ __('Traffic Graph') }}
                            </h3>
                        </div>

                        <!-- Live Pulse / Online Badge -->
                        <div class="flex items-center gap-1.5">
                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[9px] font-semibold font-mono uppercase border"
                                  :class="traffic.is_online ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-500 border-slate-200'">
                                <span class="w-1.5 h-1.5 rounded-full" :class="traffic.is_online ? 'bg-emerald-500 animate-ping' : 'bg-slate-400'"></span>
                                <span x-text="traffic.is_online ? 'ONLINE' : 'OFFLINE'">{{ ($customer->is_online ?? false) ? 'ONLINE' : 'OFFLINE' }}</span>
                            </span>
                        </div>
                    </div>

                    <!-- Tabs Below Header: 5 Min | 1 Hour | 1 Day | 7 Days -->
                    <div class="grid grid-cols-4 gap-1 p-0.5 rounded bg-slate-100 border border-slate-200 text-[9.5px] font-semibold">
                        <button type="button" 
                                @click="setTrafficPeriod('5m')" 
                                class="py-0.5 text-center rounded transition cursor-pointer"
                                :class="trafficPeriod === '5m' ? 'bg-white text-indigo-700 shadow-2xs font-bold' : 'text-slate-500 hover:text-slate-800'">
                            {{ __('5 Min') }}
                        </button>
                        <button type="button" 
                                @click="setTrafficPeriod('1h')" 
                                class="py-0.5 text-center rounded transition cursor-pointer"
                                :class="trafficPeriod === '1h' ? 'bg-white text-indigo-700 shadow-2xs font-bold' : 'text-slate-500 hover:text-slate-800'">
                            {{ __('1 Hour') }}
                        </button>
                        <button type="button" 
                                @click="setTrafficPeriod('1d')" 
                                class="py-0.5 text-center rounded transition cursor-pointer"
                                :class="trafficPeriod === '1d' ? 'bg-white text-indigo-700 shadow-2xs font-bold' : 'text-slate-500 hover:text-slate-800'">
                            {{ __('1 Day') }}
                        </button>
                        <button type="button" 
                                @click="setTrafficPeriod('7d')" 
                                class="py-0.5 text-center rounded transition cursor-pointer"
                                :class="trafficPeriod === '7d' ? 'bg-white text-indigo-700 shadow-2xs font-bold' : 'text-slate-500 hover:text-slate-800'">
                            {{ __('7 Days') }}
                        </button>
                    </div>

                    <!-- MikroTik-Style Traffic Canvas Box (Light Theme) -->
                    <div class="relative bg-white rounded border border-slate-200 overflow-hidden shadow-2xs font-mono">
                        <!-- Canvas Container -->
                        <div class="relative w-full h-32 bg-white cursor-crosshair">
                            <canvas id="mikrotikTrafficCanvas" class="w-full h-full block bg-white"></canvas>
                            
                            <!-- Offline Overlay if client is offline -->
                            <div x-show="!traffic.is_online" class="absolute inset-0 bg-slate-100/90 backdrop-blur-2xs flex flex-col items-center justify-center text-slate-500 gap-1">
                                <i class="fas fa-signal-slash text-sm text-slate-400"></i>
                                <span class="text-[9.5px] font-mono font-medium text-slate-600">{{ __('Client is Offline') }}</span>
                            </div>
                        </div>

                        <!-- MikroTik MRTG Telemetry Legend Table -->
                        <div class="border-t border-slate-200 bg-slate-50/70 text-[9.5px] font-mono divide-y divide-slate-100">
                            <!-- Table Header -->
                            <div class="grid grid-cols-3 px-2 py-1 bg-slate-100/80 font-bold text-slate-600 text-[9px] uppercase tracking-wider">
                                <div>{{ __('Metric') }}</div>
                                <div class="text-right text-emerald-700 flex items-center justify-end gap-1">
                                    <span class="w-1.5 h-1.5 bg-emerald-500 rounded-xs inline-block"></span>
                                    <span>Rx (In)</span>
                                </div>
                                <div class="text-right text-blue-700 flex items-center justify-end gap-1">
                                    <span class="w-1.5 h-1.5 bg-blue-500 rounded-xs inline-block"></span>
                                    <span>Tx (Out)</span>
                                </div>
                            </div>
                            <!-- Current Row -->
                            <div class="grid grid-cols-3 px-2 py-0.5 items-center hover:bg-white transition">
                                <span class="text-slate-500 font-medium">{{ __('Current') }}:</span>
                                <span class="text-right font-bold text-emerald-700 font-mono" x-text="currentPeriodStats.rx_curr">0.00 Mbps</span>
                                <span class="text-right font-bold text-blue-700 font-mono" x-text="currentPeriodStats.tx_curr">0.00 Mbps</span>
                            </div>
                            <!-- Average Row -->
                            <div class="grid grid-cols-3 px-2 py-0.5 items-center hover:bg-white transition">
                                <span class="text-slate-500 font-medium">{{ __('Average') }}:</span>
                                <span class="text-right font-semibold text-emerald-700 font-mono" x-text="currentPeriodStats.rx_avg || '0.00 Mbps'">0.00 Mbps</span>
                                <span class="text-right font-semibold text-blue-700 font-mono" x-text="currentPeriodStats.tx_avg || '0.00 Mbps'">0.00 Mbps</span>
                            </div>
                            <!-- Max (Peak) Row -->
                            <div class="grid grid-cols-3 px-2 py-0.5 items-center hover:bg-white transition">
                                <span class="text-slate-500 font-medium">{{ __('Peak (Max)') }}:</span>
                                <span class="text-right font-bold text-emerald-800 font-mono" x-text="currentPeriodStats.rx_max">0.00 Mbps</span>
                                <span class="text-right font-bold text-blue-800 font-mono" x-text="currentPeriodStats.tx_max">0.00 Mbps</span>
                            </div>
                            <!-- Total Volume Row -->
                            <div class="grid grid-cols-3 px-2 py-1 items-center bg-slate-100/50 font-semibold border-t border-slate-200">
                                <span class="text-slate-600">{{ __('Total Volume') }}:</span>
                                <span class="text-right font-bold text-emerald-800 font-mono" x-text="currentPeriodStats.rx_total">0 B</span>
                                <span class="text-right font-bold text-blue-800 font-mono" x-text="currentPeriodStats.tx_total">0 B</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Action -->
                <button type="button" 
                        @click="openRxtxModal()" 
                        class="w-full py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-[11px] flex items-center justify-center gap-1.5 transition shadow-2xs cursor-pointer">
                    <i class="fas fa-layer-group text-[10px]"></i>
                    <span>{{ __('Detailed Live Telemetry & Logs') }}</span>
                </button>
            </div>

            <!-- 4. Device & Optical Network Information Card (Full Width of Right Column) -->
            <div class="w-full bg-white rounded-md border border-slate-200 shadow-2xs overflow-hidden flex flex-col justify-between border-t-2 border-t-purple-600">
                <div class="p-3 space-y-2.5">
                    <!-- Device Header -->
                    <div class="flex items-center gap-2.5">
                        <div class="w-10 h-10 rounded-full bg-purple-50 border border-purple-200 text-purple-600 flex items-center justify-center shadow-2xs flex-shrink-0">
                            <i class="fas fa-server text-base text-purple-600"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="text-xs font-bold text-slate-900 tracking-tight leading-snug truncate" title="{{ __('Device & Optical Details') }}">
                                {{ __('Device & Optical Details') }}
                            </h3>
                            <span class="inline-flex items-center px-1.5 py-0.5 mt-0.5 rounded text-[9.5px] font-semibold font-mono uppercase bg-purple-50 text-purple-700 border border-purple-200">
                                FTTH / CPE
                            </span>
                        </div>
                    </div>

                    <div class="border-t border-slate-100"></div>

                    <!-- Attributes List with Perfectly Aligned Colons (Straight Vertical Line) -->
                    <div class="space-y-1.5 text-[11px]">
                        <!-- Interface -->
                        <div class="flex items-center leading-tight whitespace-nowrap">
                            <div class="w-[100px] flex items-center gap-1.5 text-slate-500 flex-shrink-0">
                                <i class="fas fa-ethernet text-cyan-500 w-3.5 text-center text-[10px]"></i>
                                <span class="text-[10px] font-medium text-slate-600">{{ __('Interface') }}</span>
                            </div>
                            <span class="text-slate-400 font-bold px-1 flex-shrink-0">:</span>
                            <span class="font-semibold text-slate-800 text-right flex-1 truncate text-[11px] pl-1 font-mono">
                                {{ $onu ? ($onu->pon_port . ($onu->onu_id ? ':' . $onu->onu_id : '')) : ($customer->fiber_route_info ?: 'N/A') }}
                            </span>
                        </div>

                        <!-- ONU Name -->
                        <div class="flex items-center leading-tight whitespace-nowrap">
                            <div class="w-[100px] flex items-center gap-1.5 text-slate-500 flex-shrink-0">
                                <i class="fas fa-microchip text-indigo-500 w-3.5 text-center text-[10px]"></i>
                                <span class="text-[10px] font-medium text-slate-600">{{ __('ONU Name') }}</span>
                            </div>
                            <span class="text-slate-400 font-bold px-1 flex-shrink-0">:</span>
                            <span class="font-semibold text-slate-800 text-right flex-1 truncate text-[11px] pl-1 font-mono">
                                {{ $onu?->name ?: ($onu?->model ?: 'N/A') }}
                            </span>
                        </div>

                        <!-- ONU Type -->
                        <div class="flex items-center leading-tight whitespace-nowrap">
                            <div class="w-[100px] flex items-center gap-1.5 text-slate-500 flex-shrink-0">
                                <i class="fas fa-tag text-amber-500 w-3.5 text-center text-[10px]"></i>
                                <span class="text-[10px] font-medium text-slate-600">{{ __('ONU Type') }}</span>
                            </div>
                            <span class="text-slate-400 font-bold px-1 flex-shrink-0">:</span>
                            <span class="font-semibold text-slate-800 text-right flex-1 truncate text-[11px] pl-1 uppercase font-mono">
                                {{ $onu?->onu_type ?: ($customer->olt?->brand ?: 'N/A') }}
                            </span>
                        </div>

                        <!-- ONU MAC -->
                        <div class="flex items-center leading-tight whitespace-nowrap">
                            <div class="w-[100px] flex items-center gap-1.5 text-slate-500 flex-shrink-0">
                                <i class="fas fa-fingerprint text-purple-500 w-3.5 text-center text-[10px]"></i>
                                <span class="text-[10px] font-medium text-slate-600">{{ __('ONU MAC') }}</span>
                            </div>
                            <span class="text-slate-400 font-bold px-1 flex-shrink-0">:</span>
                            <span class="font-bold text-slate-800 text-right flex-1 truncate text-[11px] pl-1 font-mono">
                                {{ $customer->onu_mac_sn ?: ($onu?->mac_address ?: 'N/A') }}
                            </span>
                        </div>

                        <!-- Power (Rx Power) -->
                        <div class="flex items-center leading-tight whitespace-nowrap">
                            <div class="w-[100px] flex items-center gap-1.5 text-slate-500 flex-shrink-0">
                                <i class="fas fa-bolt text-amber-500 w-3.5 text-center text-[10px]"></i>
                                <span class="text-[10px] font-medium text-slate-600">{{ __('Power') }}</span>
                            </div>
                            <span class="text-slate-400 font-bold px-1 flex-shrink-0">:</span>
                            @if($onu && $onu->rx_power_dbm !== null)
                                <span class="font-bold text-right flex-1 truncate text-[11px] pl-1 font-mono {{ $onu->rx_power_dbm >= -25 && $onu->rx_power_dbm <= -12 ? 'text-emerald-600' : ($onu->rx_power_dbm >= -28 ? 'text-amber-600' : 'text-rose-600') }}">
                                    {{ number_format($onu->rx_power_dbm, 2) }} dBm
                                </span>
                            @else
                                <span class="font-medium text-slate-400 text-right flex-1 truncate text-[11px] pl-1">N/A</span>
                            @endif
                        </div>

                        <!-- Distance -->
                        <div class="flex items-center leading-tight whitespace-nowrap">
                            <div class="w-[100px] flex items-center gap-1.5 text-slate-500 flex-shrink-0">
                                <i class="fas fa-route text-blue-500 w-3.5 text-center text-[10px]"></i>
                                <span class="text-[10px] font-medium text-slate-600">{{ __('Distance') }}</span>
                            </div>
                            <span class="text-slate-400 font-bold px-1 flex-shrink-0">:</span>
                            @if($onu && $onu->distance_m !== null)
                                <span class="font-semibold text-slate-800 text-right flex-1 truncate text-[11px] pl-1 font-mono">
                                    {{ $onu->distance_m >= 1000 ? number_format($onu->distance_m / 1000, 2) . ' km' : ($onu->distance_m . ' m') }}
                                </span>
                            @else
                                <span class="font-medium text-slate-400 text-right flex-1 truncate text-[11px] pl-1">N/A</span>
                            @endif
                        </div>

                        <!-- VLAN -->
                        <div class="flex items-center leading-tight whitespace-nowrap">
                            <div class="w-[100px] flex items-center gap-1.5 text-slate-500 flex-shrink-0">
                                <i class="fas fa-layer-group text-teal-500 w-3.5 text-center text-[10px]"></i>
                                <span class="text-[10px] font-medium text-slate-600">{{ __('VLAN') }}</span>
                            </div>
                            <span class="text-slate-400 font-bold px-1 flex-shrink-0">:</span>
                            <span class="font-semibold text-slate-800 text-right flex-1 truncate text-[11px] pl-1 font-mono">
                                {{ $onu?->vlan_id ? ('VLAN ' . $onu->vlan_id) : 'N/A' }}
                            </span>
                        </div>

                        <!-- Router MAC (Caller-ID) -->
                        <div class="flex items-center leading-tight whitespace-nowrap">
                            <div class="w-[100px] flex items-center gap-1.5 text-slate-500 flex-shrink-0">
                                <i class="fas fa-id-badge text-rose-500 w-3.5 text-center text-[10px]"></i>
                                <span class="text-[10px] font-medium text-slate-600">{{ __('Router MAC') }}</span>
                            </div>
                            <span class="text-slate-400 font-bold px-1 flex-shrink-0">:</span>
                            <span class="font-bold text-slate-800 text-right flex-1 truncate text-[11px] pl-1 font-mono"
                                  x-text="traffic.mac || '{{ $customer->mac_address ?: 'N/A' }}'">
                                {{ $customer->mac_address ?: 'N/A' }}
                            </span>
                        </div>

                        <!-- Router Brand -->
                        <div class="flex items-center leading-tight whitespace-nowrap">
                            <div class="w-[100px] flex items-center gap-1.5 text-slate-500 flex-shrink-0">
                                <i class="fas fa-wifi text-blue-500 w-3.5 text-center text-[10px]"></i>
                                <span class="text-[10px] font-medium text-slate-600">{{ __('Router Brand') }}</span>
                            </div>
                            <span class="text-slate-400 font-bold px-1 flex-shrink-0">:</span>
                            <span class="font-semibold text-slate-800 text-right flex-1 truncate text-[11px] pl-1 font-mono">
                                {{ $customer->router?->name ?: ($customer->router?->model ?: 'N/A') }}
                            </span>
                        </div>

                        <!-- OLT -->
                        <div class="flex items-center leading-tight whitespace-nowrap">
                            <div class="w-[100px] flex items-center gap-1.5 text-slate-500 flex-shrink-0">
                                <i class="fas fa-network-wired text-indigo-500 w-3.5 text-center text-[10px]"></i>
                                <span class="text-[10px] font-medium text-slate-600">{{ __('OLT') }}</span>
                            </div>
                            <span class="text-slate-400 font-bold px-1 flex-shrink-0">:</span>
                            <span class="font-semibold text-slate-800 text-right flex-1 truncate text-[11px] pl-1 font-mono">
                                {{ $customer->olt?->name ?: ($customer->olt?->brand ?: 'N/A') }}
                            </span>
                        </div>
                    </div>

                    <div class="border-t border-slate-100"></div>

                    <!-- Location / Distribution Route Box -->
                    <div class="space-y-0.5">
                        <span class="text-[10px] font-medium text-slate-500 block">{{ __('Location') }}:</span>
                        <p class="text-[10.5px] font-normal text-slate-700 leading-relaxed bg-slate-50/80 p-1.5 rounded border border-slate-200/70 truncate" 
                           title="{{ $customer->fiber_route_info ?: ($customer->zone ?: ($customer->address ?: __('No location specified.'))) }}">
                            <i class="fas fa-map-marker-alt text-rose-500 text-[9px] mr-1"></i>
                            {{ $customer->fiber_route_info ?: ($customer->zone ?: ($customer->address ?: __('No location specified.'))) }}
                        </p>
                    </div>

                    <div class="border-t border-slate-100"></div>

                    <!-- Visual Geographic & Optical Route Map (Above Footer) -->
                    <div class="space-y-1">
                        <div class="flex items-center justify-between text-[10px] font-medium text-slate-600">
                            <span class="flex items-center gap-1 font-semibold text-slate-700">
                                <i class="fas fa-map-marked-alt text-purple-600 text-[10px]"></i>
                                <span>{{ __('Visual Route & GPS Map') }}</span>
                            </span>
                            @php
                                $lat = $customer->gps_lat ?: '23.8759';
                                $lng = $customer->gps_lng ?: '90.3807';
                                $bbox_min_lng = (float)$lng - 0.006;
                                $bbox_min_lat = (float)$lat - 0.004;
                                $bbox_max_lng = (float)$lng + 0.006;
                                $bbox_max_lat = (float)$lat + 0.004;
                            @endphp
                            <a href="https://www.google.com/maps?q={{ $lat }},{{ $lng }}" 
                               target="_blank" 
                               rel="noopener noreferrer" 
                               class="text-[9.5px] font-bold text-blue-600 hover:text-blue-800 hover:underline flex items-center gap-0.5 group"
                               title="Open in Google Maps">
                                <span>Google Maps</span>
                                <i class="fas fa-external-link-alt text-[7.5px] text-blue-400 group-hover:text-blue-600"></i>
                            </a>
                        </div>

                        <!-- Embedded Interactive Map Container -->
                        <div class="relative w-full h-[135px] rounded-lg overflow-hidden border border-slate-200 bg-slate-100 shadow-2xs">
                            <iframe 
                                class="w-full h-full border-0"
                                loading="lazy"
                                src="https://www.openstreetmap.org/export/embed.html?bbox={{ $bbox_min_lng }}%2C{{ $bbox_min_lat }}%2C{{ $bbox_max_lng }}%2C{{ $bbox_max_lat }}&amp;layer=mapnik&amp;marker={{ $lat }}%2C{{ $lng }}">
                            </iframe>

                            <!-- Floating GPS & Distance Pill Badge -->
                            <div class="absolute bottom-1.5 left-1.5 right-1.5 flex items-center justify-between pointer-events-none">
                                <span class="px-1.5 py-0.5 rounded bg-slate-900/85 backdrop-blur-xs text-white font-mono text-[9px] shadow-xs flex items-center gap-1">
                                    <i class="fas fa-crosshairs text-cyan-400 text-[8px]"></i>
                                    <span>{{ $lat }}, {{ $lng }}</span>
                                </span>
                                @if($onu && $onu->distance_m !== null)
                                    <span class="px-1.5 py-0.5 rounded bg-purple-900/85 backdrop-blur-xs text-purple-200 font-mono text-[9px] shadow-xs flex items-center gap-1">
                                        <i class="fas fa-route text-amber-400 text-[8px]"></i>
                                        <span>{{ $onu->distance_m >= 1000 ? number_format($onu->distance_m / 1000, 2) . ' km' : ($onu->distance_m . ' m') }}</span>
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card Footer Button -->
                <a href="{{ Route::has('tenant.network.onu') ? route('tenant.network.onu') : (Route::has('tenant.network.olt') ? route('tenant.network.olt') : '#') }}" 
                   class="w-full py-1.5 bg-purple-600 hover:bg-purple-700 text-white font-semibold text-[11px] flex items-center justify-center gap-1.5 transition shadow-2xs">
                    <i class="fas fa-search-location text-[10px]"></i>
                    <span>{{ __('Diagnose Optical Line') }}</span>
                </a>
            </div>

            <!-- 5. Customer Note & Remarks Card (Placed Directly Under Device & Optical Details) -->
            <div class="w-full bg-white rounded-md border border-slate-200 shadow-2xs overflow-hidden flex flex-col justify-between border-t-2 border-t-amber-500">
                <div class="p-3 space-y-2.5">
                    <!-- Header -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded bg-amber-50 border border-amber-200 text-amber-600 flex items-center justify-center shadow-2xs flex-shrink-0">
                                <i class="fas fa-sticky-note text-xs"></i>
                            </div>
                            <h3 class="text-xs font-bold text-slate-900 tracking-tight leading-snug truncate" title="{{ __('Note & Remarks') }}">
                                {{ __('Note & Remarks') }}
                            </h3>
                        </div>
                        <button type="button" 
                                @click="openNoteModal()" 
                                class="px-2 py-0.5 rounded bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 font-semibold text-[10px] transition flex items-center gap-1 cursor-pointer">
                            <i class="fas fa-edit text-[9px]"></i>
                            <span>{{ __('Edit') }}</span>
                        </button>
                    </div>

                    <div class="border-t border-slate-100"></div>

                    <!-- Note Content Display -->
                    <div class="p-2.5 rounded-lg bg-amber-50/40 border border-amber-200/60 text-slate-700 text-xs leading-relaxed min-h-[65px] whitespace-pre-line break-words">
                        <template x-if="noteForm.remarks && noteForm.remarks.trim() !== ''">
                            <span class="text-slate-800 text-[11px] font-sans" x-text="noteForm.remarks"></span>
                        </template>
                        <template x-if="!noteForm.remarks || noteForm.remarks.trim() === ''">
                            <span class="text-slate-400 italic text-[11px] flex items-center gap-1">
                                <i class="fas fa-info-circle text-[10px] text-slate-400"></i>
                                {{ __('No internal notes or operational remarks recorded.') }}
                            </span>
                        </template>
                    </div>
                </div>

                <!-- Footer Quick Action -->
                <button type="button" 
                        @click="openNoteModal()" 
                        class="w-full py-1.5 bg-amber-600 hover:bg-amber-700 text-white font-semibold text-[11px] flex items-center justify-center gap-1.5 transition shadow-2xs cursor-pointer">
                    <i class="fas fa-pen-to-square text-[10px]"></i>
                    <span>{{ __('Manage Notes & Remarks') }}</span>
                </button>
            </div>

        </div>

    </div>

    <!-- Modal Component Includes -->
    @include('tenant.customers.components.pppoe')
    @include('tenant.customers.components.editstatus')
    @include('tenant.customers.components.rxtx')
    @include('tenant.customers.components.changepackage')
    @include('tenant.customers.components.paybill')
    @include('tenant.customers.components.customernote')
    @include('tenant.customers.components.logdetails')
    @include('tenant.customers.components.paymentdetails')

</div>
@endsection

@push('scripts')
<script>
window.customerActionBarManager = function(config) {
    return {
        customerId: config.customerId,
        activeBillingTab: 'invoices',
        paymentDetailsModal: false,
        selectedPayment: {},
        logDetailsModal: false,
        selectedLog: {},
        pppoeModal: false,
        statusModal: false,
        rxtxModal: false,
        packageModal: false,
        payModal: false,
        transferModal: false,
        noteModal: false,
        isPppoeLoading: false,
        isStatusLoading: false,
        isPackageLoading: false,
        isPayLoading: false,
        isTransferLoading: false,
        isNoteLoading: false,
        isLivePolling: false,
        pollTimer: null,
        showPassword: false,
        toast: { show: false, message: '', type: 'success' },
        noteForm: {
            remarks: config.remarks || ''
        },
        payForm: {
            payment_mode: 'due',
            amount: '0.00',
            discount: '0.00',
            months: 1,
            payment_method: 'cash',
            transaction_id: '',
            billing_month: '{{ Carbon\Carbon::now()->format('F Y') }}',
            notes: '',
            extend_validity: true,
            reactivate_line: true
        },
        pppoeForm: {
            username: config.pppoeUsername,
            password: config.pppoePassword
        },
        statusForm: {
            status: config.customerStatus
        },
        dueData: {
            currentDue: parseFloat(config.dueAmount || 0) || 0
        },
        cycleData: {
            currentBillingType: config.billingType || 'prepaid',
            currentExpiryDate: config.expiryDate || '',
            gracePeriodDays: parseInt(config.gracePeriodDays) || 0,
            autoCutEnabled: !!config.autoCutEnabled
        },
        transferData: {
            currentResellerId: config.resellerId || '',
            currentResellerName: config.resellerName || '',
            currentResellerCode: config.resellerCode || ''
        },
        transferForm: {
            target_type: config.resellerId ? 'reseller' : 'isp',
            reseller_id: config.resellerId || '',
            notes: '',
            sync_mikrotik: true
        },
        packageForm: {
            package_id: config.packageId || '',
            update_bill: true,
            disconnect_session: true,
            direct_proration_mode: 'adjust_expiry'
        },
        packageData: {
            currentPackageId: config.packageId || '',
            currentPackageName: config.packageName || 'Custom Plan',
            currentCode: config.packageCode || '',
            currentSpeed: config.packageSpeed || '',
            currentMonthlyBill: config.monthlyBill || '0.00',
            currentExpiryDate: config.expiryDate || '',
            remainingDays: (() => {
                if (!config.expiryDate) return 0;
                const today = new Date();
                today.setHours(0,0,0,0);
                const exp = new Date(config.expiryDate);
                exp.setHours(0,0,0,0);
                const diffTime = exp - today;
                return Math.max(0, Math.ceil(diffTime / (1000 * 60 * 60 * 24)));
            })(),
            isReseller: !!config.isReseller,
            resellerId: config.resellerId || '',
            resellerName: config.resellerName || '',
            resellerCode: config.resellerCode || '',
            resellerBalance: parseFloat(config.resellerBalance) || 0,
            resellerCreditLimit: parseFloat(config.resellerCreditLimit) || 0,
            resellerCommissionRate: parseFloat(config.resellerCommissionRate) || 0
        },
        selectedPackageInfo: null,
        upgradeImpact: {
            isUpgrade: false,
            isDowngrade: false,
            typeLabel: '',
            proratedDiff: '0.00',
            proratedRefund: '0.00',
            hasSufficientBalance: true,
            adjustedDays: 0,
            adjustedExpiryDate: ''
        },
        traffic: {
            is_online: false,
            rx_mbps: 0.00,
            tx_mbps: 0.00,
            rx_human: '0.00 Mbps',
            tx_human: '0.00 Mbps',
            uptime: '--',
            ip: config.ipAddress || '',
            mac: '',
            total_rx_human: '0 B',
            total_tx_human: '0 B'
        },
        trafficPeriod: '5m',
        isUsageLoading: false,
        periodData: {
            '5m': {
                unit: 'Mbps',
                labels: Array(30).fill(''),
                rx: Array(30).fill(0),
                tx: Array(30).fill(0),
                rx_curr: '0.00 Mbps',
                tx_curr: '0.00 Mbps',
                rx_avg: '0.00 Mbps',
                tx_avg: '0.00 Mbps',
                rx_max: '0.00 Mbps',
                tx_max: '0.00 Mbps',
                rx_total: '0 B',
                tx_total: '0 B',
                scale_label: '10 Mbps'
            },
            '1h': {
                unit: 'MB',
                labels: Array(30).fill(''),
                rx: Array(30).fill(0),
                tx: Array(30).fill(0),
                rx_curr: '0.00 Mbps',
                tx_curr: '0.00 Mbps',
                rx_avg: '0.00 MB',
                tx_avg: '0.00 MB',
                rx_max: '0.00 MB',
                tx_max: '0.00 MB',
                rx_total: '0 B',
                tx_total: '0 B',
                scale_label: '10 MB'
            },
            '1d': {
                unit: 'MB',
                labels: Array(24).fill(''),
                rx: Array(24).fill(0),
                tx: Array(24).fill(0),
                rx_curr: '0.00 Mbps',
                tx_curr: '0.00 Mbps',
                rx_avg: '0.00 MB/h',
                tx_avg: '0.00 MB/h',
                rx_max: '0.00 MB',
                tx_max: '0.00 MB',
                rx_total: '0 B',
                tx_total: '0 B',
                scale_label: '10 MB/h'
            },
            '7d': {
                unit: 'GB',
                labels: Array(7).fill(''),
                rx: Array(7).fill(0),
                tx: Array(7).fill(0),
                rx_curr: '0.00 Mbps',
                tx_curr: '0.00 Mbps',
                rx_avg: '0.00 GB/d',
                tx_avg: '0.00 GB/d',
                rx_max: '0.00 GB',
                tx_max: '0.00 GB',
                rx_total: '0 B',
                tx_total: '0 B',
                scale_label: '10 GB/d'
            }
        },

        get currentPeriodStats() {
            return this.periodData[this.trafficPeriod] || this.periodData['5m'];
        },

        setTrafficPeriod(period) {
            this.trafficPeriod = period;
            this.$nextTick(() => {
                this.drawMikrotikGraph();
            });
        },

        async fetchUsageHistory() {
            this.isUsageLoading = true;
            try {
                const response = await fetch(`{{ url('reseller/customers') }}/${this.customerId}/usage-history`, {
                    headers: { 'Accept': 'application/json' }
                });
                const res = await response.json();
                if (res.success) {
                    if (res.period_5m) this.periodData['5m'] = res.period_5m;
                    if (res.period_1h) this.periodData['1h'] = res.period_1h;
                    if (res.period_1d) this.periodData['1d'] = res.period_1d;
                    if (res.period_7d) this.periodData['7d'] = res.period_7d;

                    this.$nextTick(() => {
                        this.drawMikrotikGraph();
                    });
                }
            } catch (err) {
            } finally {
                this.isUsageLoading = false;
            }
        },

        chartData: {
            maxPoints: 24,
            rx: Array(24).fill(0),
            tx: Array(24).fill(0)
        },

        showToast(message, type = 'success') {
            this.toast.message = message;
            this.toast.type = type;
            this.toast.show = true;
            setTimeout(() => { this.toast.show = false; }, 4000);
        },

        openPppoeModal() {
            this.closeAllModals();
            this.pppoeModal = true;
        },

        openStatusModal() {
            if (this.statusForm.status === 'expired') {
                this.showToast('Expired subscriber cannot be enabled or disabled directly. Please renew the line or collect bill payment first.', 'error');
                return;
            }
            this.closeAllModals();
            this.statusModal = true;
        },

        openPackageModal() {
            this.closeAllModals();
            this.packageForm.package_id = '';
            this.selectedPackageInfo = null;
            this.packageModal = true;
        },

        openPayModal() {
            this.closeAllModals();
            const curDue = parseFloat(this.dueData.currentDue) || 0;
            this.payForm.payment_mode = 'due';
            this.payForm.amount = curDue > 0 ? curDue.toFixed(2) : Number(this.packageData.currentMonthlyBill || 0).toFixed(2);
            this.payForm.discount = '0.00';
            this.payForm.months = 1;
            this.payForm.payment_method = 'cash';
            this.payForm.transaction_id = '';
            this.payForm.notes = '';
            this.payForm.extend_validity = true;
            this.payForm.reactivate_line = true;
            this.payModal = true;
        },

        onPaymentModeChange() {
            const curDue = parseFloat(this.dueData.currentDue) || 0;
            const monthly = parseFloat(this.packageData.currentMonthlyBill) || 500;
            if (this.payForm.payment_mode === 'due') {
                this.payForm.amount = curDue > 0 ? curDue.toFixed(2) : monthly.toFixed(2);
            } else if (this.payForm.payment_mode === 'advance') {
                this.payForm.amount = (monthly * this.payForm.months).toFixed(2);
            }
        },

        setAdvanceMonths(m) {
            this.payForm.months = m;
            const monthly = parseFloat(this.packageData.currentMonthlyBill) || 500;
            this.payForm.amount = (monthly * m).toFixed(2);
        },

        openTransferModal() {
            this.closeAllModals();
            this.transferForm.target_type = this.packageData.isReseller ? 'reseller' : 'isp';
            this.transferForm.reseller_id = this.packageData.resellerId || '';
            this.transferForm.package_id = this.packageData.currentPackageId || '';
            this.transferForm.notes = '';
            this.transferForm.sync_mikrotik = true;
            this.transferModal = true;
        },

        openNoteModal() {
            this.closeAllModals();
            this.noteModal = true;
        },

        openRxtxModal() {
            this.closeAllModals();
            this.rxtxModal = true;
            this.isLivePolling = true;
            this.$nextTick(() => {
                this.initGraph();
                this.fetchTrafficPoint();
                this.startPolling();
            });
        },

        closeRxtxModal() {
            this.rxtxModal = false;
            this.stopPolling();
        },

        init() {
            this.fetchTrafficPoint();
            this.fetchUsageHistory();
            this.$nextTick(() => {
                this.initMikrotikGraph();
                if (this.traffic.is_online || {{ ($customer->is_online ?? false) ? 'true' : 'false' }}) {
                    this.startPolling();
                }
            });
            window.addEventListener('resize', () => {
                if (typeof this.initMikrotikGraph === 'function') {
                    this.initMikrotikGraph();
                }
            });

            // Check for payment_success in URL params
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('payment_success') === '1') {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Payment Submitted Successfully!',
                        html: '<div class="text-xs text-slate-600 mt-1">Your payment was recorded successfully. Subscriber line verification and activation will update automatically.</div>',
                        confirmButtonText: 'OK, Got It',
                        customClass: {
                            confirmButton: 'bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs px-4 py-2 rounded-lg cursor-pointer'
                        },
                        buttonsStyling: false
                    });
                }
                const cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
                window.history.replaceState({path: cleanUrl}, '', cleanUrl);
            }
        },

        closeAllModals() {
            this.pppoeModal = false;
            this.statusModal = false;
            this.packageModal = false;
            this.payModal = false;
            this.transferModal = false;
            this.noteModal = false;
            this.paymentDetailsModal = false;
            this.logDetailsModal = false;
            this.closeRxtxModal();
        },

        openPaymentDetails(payment) {
            this.closeAllModals();
            this.selectedPayment = payment || {};
            this.paymentDetailsModal = true;
        },

        async approvePayment(id, invoiceNo) {
            if (typeof Swal !== 'undefined') {
                const result = await Swal.fire({
                    title: 'Approve Payment & Activate Line?',
                    html: `<div class="text-xs text-slate-600">Are you sure you want to approve receipt <b>${invoiceNo}</b>?<br><span class="text-slate-400 mt-1 block">This will extend subscriber validity and activate PPPoE line on MikroTik.</span></div>`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Approve & Activate',
                    cancelButtonText: 'Cancel',
                    customClass: {
                        confirmButton: 'bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs px-4 py-2 rounded-lg cursor-pointer mr-2',
                        cancelButton: 'bg-slate-200 hover:bg-slate-300 text-slate-700 font-semibold text-xs px-4 py-2 rounded-lg cursor-pointer'
                    },
                    buttonsStyling: false
                });
                if (!result.isConfirmed) return;
            } else {
                if (!confirm(`Are you sure you want to approve payment '${invoiceNo}' and activate subscriber line in MikroTik & FreeRADIUS?`)) {
                    return;
                }
            }

            try {
                const res = await fetch(`/reseller/collections/${id}/approve`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();
                if (data.success) {
                    if (typeof Swal !== 'undefined') {
                        await Swal.fire({
                            icon: 'success',
                            title: 'Payment Approved!',
                            text: data.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                    window.location.reload();
                } else {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Approval Failed',
                            text: data.message || 'Failed to approve payment.',
                            customClass: {
                                confirmButton: 'bg-rose-600 hover:bg-rose-700 text-white font-semibold text-xs px-4 py-2 rounded-lg cursor-pointer'
                            },
                            buttonsStyling: false
                        });
                    } else {
                        alert(data.message || 'Failed to approve payment.');
                    }
                }
            } catch (e) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Network Error',
                        text: 'Could not communicate with the server to approve payment.',
                        customClass: {
                            confirmButton: 'bg-rose-600 hover:bg-rose-700 text-white font-semibold text-xs px-4 py-2 rounded-lg cursor-pointer'
                        },
                        buttonsStyling: false
                    });
                } else {
                    alert('Network error approving payment.');
                }
            }
        },

        openLogDetails(log) {
            this.selectedLog = log || {};
            this.logDetailsModal = true;
        },

        getRouterRemoteUrl(ip) {
            if (!ip || ip === 'Dynamic / Auto' || ip === '127.0.0.1') return 'javascript:void(0)';
            let raw = String(ip).trim();
            if (!raw.startsWith('http://') && !raw.startsWith('https://')) {
                raw = 'http://' + raw;
            }
            const hostPart = raw.replace(/^https?:\/\//, '');
            if (!hostPart.includes(':')) {
                raw += ':8080';
            }
            return raw;
        },

        formatDate(val) {
            if (!val) return '';
            try {
                const d = new Date(val);
                if (isNaN(d.getTime())) return String(val);
                return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
            } catch (e) {
                return String(val);
            }
        },

        formatUptime(val) {
            if (!val || val === '--' || val === 'Offline' || val === '0s') return 'Offline';
            let str = String(val).trim();
            if (str.toLowerCase() === 'offline') return 'Offline';

            let weeks = 0, days = 0, hours = 0, mins = 0, secs = 0;

            const wMatch = str.match(/(\d+)w/i);
            if (wMatch) { weeks = parseInt(wMatch[1]); str = str.replace(wMatch[0], ''); }

            const dMatch = str.match(/(\d+)d/i);
            if (dMatch) { days = parseInt(dMatch[1]); str = str.replace(dMatch[0], ''); }

            const timeMatch = str.match(/(\d+):(\d+)(?::(\d+))?/);
            if (timeMatch) {
                hours = parseInt(timeMatch[1]);
                mins = parseInt(timeMatch[2]);
                secs = timeMatch[3] ? parseInt(timeMatch[3]) : 0;
            } else {
                const hMatch = str.match(/(\d+)h/i);
                if (hMatch) hours = parseInt(hMatch[1]);
                const mMatch = str.match(/(\d+)m/i);
                if (mMatch) mins = parseInt(mMatch[1]);
                const sMatch = str.match(/(\d+)s/i);
                if (sMatch) secs = parseInt(sMatch[1]);
            }

            const totalDays = (weeks * 7) + days;
            let parts = [];
            if (totalDays > 0) parts.push(`${totalDays}d`);
            if (hours > 0 || totalDays > 0) parts.push(`${hours}h`);
            if (mins > 0 || hours > 0 || totalDays > 0) parts.push(`${mins}m`);
            if (totalDays === 0 && hours === 0 && mins === 0) parts.push(`${secs}s`);

            return parts.length > 0 ? parts.join(' ') : 'Offline';
        },

        togglePolling() {
            if (this.isLivePolling) {
                this.stopPolling();
            } else {
                if (!this.traffic.is_online) {
                    this.showToast('Client is offline. Cannot start live traffic monitor.', 'warning');
                    return;
                }
                this.startPolling();
            }
        },

        startPolling() {
            if (!this.traffic.is_online) {
                this.stopPolling();
                return;
            }
            this.isLivePolling = true;
            if (this.pollTimer) clearInterval(this.pollTimer);
            this.pollTimer = setInterval(() => {
                if (this.isLivePolling && this.traffic.is_online) {
                    this.fetchTrafficPoint();
                } else {
                    this.stopPolling();
                }
            }, 1000);
        },

        stopPolling() {
            this.isLivePolling = false;
            if (this.pollTimer) {
                clearInterval(this.pollTimer);
                this.pollTimer = null;
            }
        },

        async fetchTrafficPoint() {
            try {
                const response = await fetch(`{{ url('reseller/customers') }}/${this.customerId}/traffic`, {
                    headers: { 'Accept': 'application/json' }
                });
                const res = await response.json();
                if (res.success) {
                    this.traffic.is_online = !!res.is_online;
                    if (!this.traffic.is_online && this.isLivePolling) {
                        this.stopPolling();
                    }
                    this.traffic.rx_mbps = parseFloat(res.rx_mbps) || 0;
                    this.traffic.tx_mbps = parseFloat(res.tx_mbps) || 0;
                    this.traffic.rx_human = res.rx_human || '0.00 Mbps';
                    this.traffic.tx_human = res.tx_human || '0.00 Mbps';
                    this.traffic.uptime = res.uptime || 'Offline';
                    this.traffic.ip = res.ip || config.ipAddress;
                    this.traffic.mac = res.mac || '';
                    this.traffic.total_rx_human = res.total_rx_human || '0 B';
                    this.traffic.total_tx_human = res.total_tx_human || '0 B';

                    // Update 5m period data live buffer
                    if (this.periodData && this.periodData['5m']) {
                        const cur5m = this.periodData['5m'];
                        if (cur5m.rx) {
                            cur5m.rx.shift();
                            cur5m.rx.push(this.traffic.rx_mbps);
                        }
                        if (cur5m.tx) {
                            cur5m.tx.shift();
                            cur5m.tx.push(this.traffic.tx_mbps);
                        }
                        if (cur5m.labels) {
                            cur5m.labels.shift();
                            const d = new Date();
                            const ts = String(d.getHours()).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2, '0') + ':' + String(d.getSeconds()).padStart(2, '0');
                            cur5m.labels.push(ts);
                        }
                        cur5m.rx_curr = this.traffic.rx_human;
                        cur5m.tx_curr = this.traffic.tx_human;
                        const avgRx = (cur5m.rx.reduce((a, b) => a + b, 0) / (cur5m.rx.length || 1)).toFixed(2);
                        const avgTx = (cur5m.tx.reduce((a, b) => a + b, 0) / (cur5m.tx.length || 1)).toFixed(2);
                        cur5m.rx_avg = `${avgRx} Mbps`;
                        cur5m.tx_avg = `${avgTx} Mbps`;
                        cur5m.rx_total = this.traffic.total_rx_human;
                        cur5m.tx_total = this.traffic.total_tx_human;
                        const maxVal = Math.max(0.5, ...cur5m.rx, ...cur5m.tx);
                        cur5m.rx_max = Math.max(...cur5m.rx).toFixed(2) + ' Mbps';
                        cur5m.tx_max = Math.max(...cur5m.tx).toFixed(2) + ' Mbps';
                        cur5m.scale_label = Math.ceil(maxVal * 1.25) + ' Mbps';
                    }

                    // Push into waveform buffer
                    this.chartData.rx.shift();
                    this.chartData.rx.push(this.traffic.rx_mbps);

                    this.chartData.tx.shift();
                    this.chartData.tx.push(this.traffic.tx_mbps);

                    this.drawMikrotikGraph();

                    if (this.rxtxModal && typeof this.drawCanvasGraph === 'function') {
                        this.drawCanvasGraph();
                    }
                }
            } catch (err) {}
        },

        graphHoverIdx: null,
        graphHoverPos: null,

        initMikrotikGraph() {
            const canvas = document.getElementById('mikrotikTrafficCanvas');
            if (!canvas) return;
            const dpr = window.devicePixelRatio || 2;
            const rect = canvas.getBoundingClientRect();
            canvas.width = (rect.width || 280) * dpr;
            canvas.height = (rect.height || 128) * dpr;

            if (!canvas._hasHoverListener) {
                canvas._hasHoverListener = true;
                canvas.addEventListener('mousemove', (e) => {
                    const r = canvas.getBoundingClientRect();
                    const scaleX = canvas.width / r.width;
                    const scaleY = canvas.height / r.height;
                    const clientX = (e.clientX - r.left) * scaleX;
                    const clientY = (e.clientY - r.top) * scaleY;
                    
                    const dprVal = window.devicePixelRatio || 2;
                    const padL = Math.round(36 * dprVal);
                    const padR = Math.round(10 * dprVal);
                    const plotW = canvas.width - padL - padR;

                    const pData = this.currentPeriodStats;
                    const rxVals = pData.rx || [];
                    const ptCount = rxVals.length || 30;

                    if (clientX >= padL && clientX <= (padL + plotW)) {
                        const stepX = plotW / Math.max(1, ptCount - 1);
                        const idx = Math.min(ptCount - 1, Math.max(0, Math.round((clientX - padL) / stepX)));
                        this.graphHoverIdx = idx;
                        this.graphHoverPos = { x: clientX, y: clientY };
                    } else {
                        this.graphHoverIdx = null;
                        this.graphHoverPos = null;
                    }
                    this.drawMikrotikGraph();
                });

                canvas.addEventListener('mouseleave', () => {
                    this.graphHoverIdx = null;
                    this.graphHoverPos = null;
                    this.drawMikrotikGraph();
                });
            }

            this.drawMikrotikGraph();
        },

        drawMikrotikGraph() {
            const canvas = document.getElementById('mikrotikTrafficCanvas');
            if (!canvas) return;
            const ctx = canvas.getContext('2d');
            const w = canvas.width;
            const h = canvas.height;
            const dpr = window.devicePixelRatio || 2;

            const pData = this.currentPeriodStats;
            const rxVals = pData.rx || [];
            const txVals = pData.tx || [];
            const labels = pData.labels || [];
            const ptCount = rxVals.length || 30;
            const unit = pData.unit || 'Mbps';

            ctx.clearRect(0, 0, w, h);

            // 1. Clean Crisp Light Background (Pure White)
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, w, h);

            // Layout Margins
            const padL = Math.round(36 * dpr);
            const padR = Math.round(10 * dpr);
            const padT = Math.round(8 * dpr);
            const padB = Math.round(18 * dpr);
            const plotW = w - padL - padR;
            const plotH = h - padT - padB;

            // Dynamic Scale Calculation
            const rawMax = Math.max(0.5, ...rxVals, ...txVals);
            const maxVal = rawMax * 1.15;
            const scaleY = plotH / maxVal;
            const stepX = plotW / Math.max(1, ptCount - 1);
            const baselineY = padT + plotH;

            // 2. MikroTik Grid Lines & Left Y-Axis Scale Ticks
            ctx.lineWidth = 1 * dpr;
            ctx.strokeStyle = '#e2e8f0';
            ctx.fillStyle = '#64748b';
            ctx.font = `${Math.round(8.5 * dpr)}px monospace`;
            ctx.textAlign = 'right';
            ctx.textBaseline = 'middle';

            const gridLines = 3;
            for (let i = 0; i <= gridLines; i++) {
                const y = padT + (plotH / gridLines) * i;
                
                // Dotted horizontal grid line
                ctx.beginPath();
                ctx.setLineDash([3 * dpr, 3 * dpr]);
                ctx.moveTo(padL, y);
                ctx.lineTo(padL + plotW, y);
                ctx.stroke();

                // Y-Axis Tick Value
                const tickVal = ((maxVal / gridLines) * (gridLines - i)).toFixed(1);
                ctx.fillText(`${tickVal}`, padL - (4 * dpr), y);
            }
            ctx.setLineDash([]); // Reset dash

            // 3. X-Axis Baseline & Timeline Labels
            ctx.beginPath();
            ctx.strokeStyle = '#cbd5e1';
            ctx.lineWidth = 1 * dpr;
            ctx.moveTo(padL, baselineY);
            ctx.lineTo(padL + plotW, baselineY);
            ctx.stroke();

            // X-Ticks along bottom
            if (labels.length > 0) {
                ctx.fillStyle = '#94a3b8';
                ctx.font = `${Math.round(7.5 * dpr)}px monospace`;
                ctx.textBaseline = 'top';

                const tickIndices = [
                    0,
                    Math.floor(ptCount * 0.33),
                    Math.floor(ptCount * 0.66),
                    ptCount - 1
                ];

                tickIndices.forEach((idx, tNum) => {
                    if (labels[idx]) {
                        const x = padL + (idx * stepX);
                        if (tNum === 0) ctx.textAlign = 'left';
                        else if (tNum === tickIndices.length - 1) ctx.textAlign = 'right';
                        else ctx.textAlign = 'center';
                        ctx.fillText(labels[idx], x, baselineY + (4 * dpr));
                    }
                });
            }

            // 4. Draw RX (Download - Emerald Green Area)
            if (rxVals.length > 0) {
                ctx.beginPath();
                ctx.moveTo(padL, baselineY);
                rxVals.forEach((val, idx) => {
                    const x = padL + (idx * stepX);
                    const y = baselineY - (val * scaleY);
                    ctx.lineTo(x, y);
                });
                ctx.lineTo(padL + ((rxVals.length - 1) * stepX), baselineY);
                ctx.closePath();

                const gradRx = ctx.createLinearGradient(0, padT, 0, baselineY);
                gradRx.addColorStop(0, 'rgba(16, 185, 129, 0.45)');
                gradRx.addColorStop(1, 'rgba(16, 185, 129, 0.04)');
                ctx.fillStyle = gradRx;
                ctx.fill();

                // Rx Stroke Outline
                ctx.beginPath();
                rxVals.forEach((val, idx) => {
                    const x = padL + (idx * stepX);
                    const y = baselineY - (val * scaleY);
                    if (idx === 0) ctx.moveTo(x, y);
                    else ctx.lineTo(x, y);
                });
                ctx.strokeStyle = '#059669';
                ctx.lineWidth = 1.8 * dpr;
                ctx.stroke();
            }

            // 5. Draw TX (Upload - Blue Area)
            if (txVals.length > 0) {
                ctx.beginPath();
                ctx.moveTo(padL, baselineY);
                txVals.forEach((val, idx) => {
                    const x = padL + (idx * stepX);
                    const y = baselineY - (val * scaleY);
                    ctx.lineTo(x, y);
                });
                ctx.lineTo(padL + ((txVals.length - 1) * stepX), baselineY);
                ctx.closePath();

                const gradTx = ctx.createLinearGradient(0, padT, 0, baselineY);
                gradTx.addColorStop(0, 'rgba(37, 99, 235, 0.35)');
                gradTx.addColorStop(1, 'rgba(37, 99, 235, 0.02)');
                ctx.fillStyle = gradTx;
                ctx.fill();

                // Tx Stroke Outline
                ctx.beginPath();
                txVals.forEach((val, idx) => {
                    const x = padL + (idx * stepX);
                    const y = baselineY - (val * scaleY);
                    if (idx === 0) ctx.moveTo(x, y);
                    else ctx.lineTo(x, y);
                });
                ctx.strokeStyle = '#2563eb';
                ctx.lineWidth = 1.6 * dpr;
                ctx.stroke();
            }

            // 6. Interactive Hover Crosshair & Tooltip Box
            if (this.graphHoverIdx !== null && rxVals[this.graphHoverIdx] !== undefined) {
                const hIdx = this.graphHoverIdx;
                const hX = padL + (hIdx * stepX);
                const rxVal = rxVals[hIdx] || 0;
                const txVal = txVals[hIdx] || 0;
                const timeLabel = labels[hIdx] || '';

                // Vertical Crosshair Line
                ctx.beginPath();
                ctx.setLineDash([2 * dpr, 2 * dpr]);
                ctx.strokeStyle = '#64748b';
                ctx.lineWidth = 1 * dpr;
                ctx.moveTo(hX, padT);
                ctx.lineTo(hX, baselineY);
                ctx.stroke();
                ctx.setLineDash([]);

                // Rx & Tx Marker Dots
                const rxY = baselineY - (rxVal * scaleY);
                const txY = baselineY - (txVal * scaleY);

                ctx.beginPath();
                ctx.arc(hX, rxY, 3 * dpr, 0, Math.PI * 2);
                ctx.fillStyle = '#059669';
                ctx.fill();
                ctx.strokeStyle = '#ffffff';
                ctx.lineWidth = 1.5 * dpr;
                ctx.stroke();

                ctx.beginPath();
                ctx.arc(hX, txY, 3 * dpr, 0, Math.PI * 2);
                ctx.fillStyle = '#2563eb';
                ctx.fill();
                ctx.strokeStyle = '#ffffff';
                ctx.lineWidth = 1.5 * dpr;
                ctx.stroke();

                // Floating Tooltip Badge Header
                const text = `${timeLabel ? timeLabel + ' | ' : ''}Rx: ${rxVal.toFixed(2)} ${unit} | Tx: ${txVal.toFixed(2)} ${unit}`;
                ctx.font = `bold ${Math.round(8 * dpr)}px monospace`;
                const tw = ctx.measureText(text).width + (12 * dpr);
                const th = 16 * dpr;
                const txLeft = Math.min(w - tw - (6 * dpr), Math.max(padL + (4 * dpr), hX - (tw / 2)));
                const txTop = padT + (2 * dpr);

                ctx.fillStyle = 'rgba(15, 23, 42, 0.88)';
                ctx.beginPath();
                ctx.roundRect(txLeft, txTop, tw, th, 3 * dpr);
                ctx.fill();

                ctx.fillStyle = '#f8fafc';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText(text, txLeft + (tw / 2), txTop + (th / 2));
            }
        },

        initGraph() {
            const canvas = document.getElementById('rxtxLiveCanvas') || document.getElementById('rxtxCanvas');
            if (!canvas) return;
            const dpr = window.devicePixelRatio || 1;
            const rect = canvas.getBoundingClientRect();
            canvas.width = (rect.width || 440) * dpr;
            canvas.height = (rect.height || 176) * dpr;
            this.drawCanvasGraph();
        },

        drawCanvasGraph() {
            const canvas = document.getElementById('rxtxLiveCanvas') || document.getElementById('rxtxCanvas');
            if (!canvas) return;
            const ctx = canvas.getContext('2d');
            const w = canvas.width;
            const h = canvas.height;

            ctx.clearRect(0, 0, w, h);

            // Calculate Scale
            const maxVal = Math.max(5, ...this.chartData.rx, ...this.chartData.tx);
            const scaleY = (h - 20) / maxVal;
            const stepX = w / (this.chartData.maxPoints - 1);

            // 1. Draw Grid Lines
            ctx.lineWidth = 1;
            ctx.strokeStyle = 'rgba(226, 232, 240, 0.9)';
            for (let i = 1; i <= 4; i++) {
                const y = h - (h / 4) * i;
                ctx.beginPath();
                ctx.moveTo(0, y);
                ctx.lineTo(w, y);
                ctx.stroke();
            }

            // 2. Draw RX Area & Line (Emerald / Green)
            ctx.beginPath();
            ctx.moveTo(0, h);
            this.chartData.rx.forEach((val, idx) => {
                const x = idx * stepX;
                const y = h - (val * scaleY);
                if (idx === 0) ctx.lineTo(x, y);
                else ctx.lineTo(x, y);
            });
            ctx.lineTo(w, h);
            ctx.closePath();
            const gradRx = ctx.createLinearGradient(0, 0, 0, h);
            gradRx.addColorStop(0, 'rgba(16, 185, 129, 0.35)');
            gradRx.addColorStop(1, 'rgba(16, 185, 129, 0.02)');
            ctx.fillStyle = gradRx;
            ctx.fill();

            // RX Stroke
            ctx.beginPath();
            this.chartData.rx.forEach((val, idx) => {
                const x = idx * stepX;
                const y = h - (val * scaleY);
                if (idx === 0) ctx.moveTo(x, y);
                else ctx.lineTo(x, y);
            });
            ctx.strokeStyle = '#10b981';
            ctx.lineWidth = 2.5;
            ctx.stroke();

            // 3. Draw TX Area & Line (Purple)
            ctx.beginPath();
            ctx.moveTo(0, h);
            this.chartData.tx.forEach((val, idx) => {
                const x = idx * stepX;
                const y = h - (val * scaleY);
                if (idx === 0) ctx.lineTo(x, y);
                else ctx.lineTo(x, y);
            });
            ctx.lineTo(w, h);
            ctx.closePath();
            const gradTx = ctx.createLinearGradient(0, 0, 0, h);
            gradTx.addColorStop(0, 'rgba(139, 92, 246, 0.30)');
            gradTx.addColorStop(1, 'rgba(139, 92, 246, 0.02)');
            ctx.fillStyle = gradTx;
            ctx.fill();

            // TX Stroke
            ctx.beginPath();
            this.chartData.tx.forEach((val, idx) => {
                const x = idx * stepX;
                const y = h - (val * scaleY);
                if (idx === 0) ctx.moveTo(x, y);
                else ctx.lineTo(x, y);
            });
            ctx.strokeStyle = '#8b5cf6';
            ctx.lineWidth = 2;
            ctx.stroke();
        },

        generateRandomPassword() {
            const chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789@#';
            let pass = '';
            for (let i = 0; i < 8; i++) {
                pass += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            this.pppoeForm.password = pass;
        },

        async submitPppoeUpdate() {
            this.isPppoeLoading = true;
            try {
                const response = await fetch(`{{ url('reseller/customers') }}/${this.customerId}/update-pppoe`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(this.pppoeForm)
                });
                const res = await response.json();
                if (res.success) {
                    this.pppoeForm.username = res.username;
                    this.pppoeForm.password = res.password;
                    this.showToast(res.message || 'PPPoE credentials updated and synced to MikroTik & FreeRADIUS!');
                    this.pppoeModal = false;
                } else {
                    this.showToast(res.message || 'Failed to update PPPoE.', 'error');
                }
            } catch (err) {
                this.showToast('Validation or network error occurred.', 'error');
            } finally {
                this.isPppoeLoading = false;
            }
        },

        async submitStatusUpdate() {
            this.isStatusLoading = true;
            try {
                const response = await fetch(`{{ url('reseller/customers') }}/${this.customerId}/update-status`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(this.statusForm)
                });
                const res = await response.json();
                if (res.success) {
                    this.statusForm.status = res.status;
                    this.showToast(res.message || 'Status updated and synced to MikroTik & FreeRADIUS!');
                    this.statusModal = false;
                } else {
                    this.showToast(res.message || 'Failed to update status.', 'error');
                }
            } catch (err) {
                this.showToast('Network error while updating status.', 'error');
            } finally {
                this.isStatusLoading = false;
            }
        },

        onPackageSelect() {
            const select = document.querySelector('select[x-model="packageForm.package_id"]');
            if (!select || !this.packageForm.package_id) {
                this.selectedPackageInfo = null;
                return;
            }
            const option = select.selectedOptions[0];
            if (!option) return;

            const newPrice = parseFloat(option.getAttribute('data-price')) || 0;
            const newName = option.getAttribute('data-name') || '';
            const newSpeed = option.getAttribute('data-speed') || '';

            this.selectedPackageInfo = {
                id: this.packageForm.package_id,
                name: newName,
                price: newPrice.toFixed(2),
                speed: newSpeed
            };

            const currentBill = parseFloat(this.packageData.currentMonthlyBill) || 0;
            const commRate = parseFloat(this.packageData.resellerCommissionRate) || 0;

            const calcCost = (price) => {
                if (!this.packageData.isReseller) return price;
                const discount = (price * commRate) / 100;
                return Math.max(0, price - discount);
            };

            const oldCost = calcCost(currentBill);
            const newCost = calcCost(newPrice);
            const isUpgrade = newCost > oldCost;
            const isDowngrade = newCost < oldCost;

            const oldDaily = oldCost / 30;
            const newDaily = newCost / 30;
            const remainingDays = this.packageData.remainingDays || 0;

            const proratedDiff = Math.max(0, (newDaily - oldDaily) * remainingDays);
            const proratedRefund = Math.max(0, (oldDaily - newDaily) * remainingDays);

            const availableBal = (parseFloat(this.packageData.resellerBalance) || 0) + (parseFloat(this.packageData.resellerCreditLimit) || 0);
            const hasSufficient = availableBal >= proratedDiff;

            let adjustedDays = remainingDays;
            let adjustedExpiryDate = this.packageData.currentExpiryDate;

            if (remainingDays > 0 && newDaily > 0) {
                const remainingValue = remainingDays * oldDaily;
                adjustedDays = Math.max(1, Math.floor(remainingValue / newDaily));
                const newExp = new Date();
                newExp.setDate(newExp.getDate() + adjustedDays);
                adjustedExpiryDate = newExp.toISOString().split('T')[0];
            }

            this.upgradeImpact = {
                isUpgrade: isUpgrade,
                isDowngrade: isDowngrade,
                typeLabel: isUpgrade ? 'Plan Upgrade' : (isDowngrade ? 'Plan Downgrade' : 'Same Tier Plan'),
                proratedDiff: proratedDiff.toFixed(2),
                proratedRefund: proratedRefund.toFixed(2),
                hasSufficientBalance: hasSufficient,
                adjustedDays: adjustedDays,
                adjustedExpiryDate: adjustedExpiryDate,
                oldCost: oldCost.toFixed(2),
                newCost: newCost.toFixed(2)
            };
        },

        async submitPackageChange() {
            if (!this.packageForm.package_id) {
                this.showToast('Please select a new package.', 'error');
                return;
            }
            this.isPackageLoading = true;
            try {
                const response = await fetch(`{{ url('reseller/customers') }}/${this.customerId}/update-package`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(this.packageForm)
                });
                const res = await response.json();
                if (res.success) {
                    this.packageData.currentPackageId = res.package_id;
                    this.packageData.currentPackageName = res.package_name;
                    this.packageData.currentMonthlyBill = res.monthly_bill;
                    if (res.expiry_date) {
                        this.packageData.currentExpiryDate = res.expiry_date;
                    }
                    if (res.reseller_balance !== null && res.reseller_balance !== undefined) {
                        this.packageData.resellerBalance = parseFloat(res.reseller_balance);
                    }
                    this.showToast(res.message || 'Package changed and synced successfully!');
                    this.packageModal = false;
                } else {
                    this.showToast(res.message || 'Failed to change package.', 'error');
                }
            } catch (err) {
                this.showToast('Network error while changing package.', 'error');
            } finally {
                this.isPackageLoading = false;
            }
        },

        async submitBillPayment() {
            if (!this.payForm.amount || parseFloat(this.payForm.amount) <= 0) {
                this.showToast('Please enter a valid payment amount.', 'error');
                return;
            }
            this.isPayLoading = true;
            try {
                const response = await fetch(`{{ url('reseller/customers') }}/${this.customerId}/pay-bill`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(this.payForm)
                });
                const res = await response.json();
                if (res.success) {
                    if (res.redirect_url) {
                        this.showToast(res.message || 'Redirecting to bKash Payment Gateway...');
                        window.location.href = res.redirect_url;
                        return;
                    }
                    if (res.customer) {
                        this.dueData.currentDue = parseFloat(res.customer.due_amount_raw !== undefined ? res.customer.due_amount_raw : 0);
                        if (res.customer.expiry_date) {
                            this.cycleData.currentExpiryDate = res.customer.expiry_raw;
                            this.cycleData.currentExpiryDateFormatted = res.customer.expiry_date;
                            this.cycleData.remainingDays = res.customer.remaining_days;
                            this.packageData.currentExpiryDate = res.customer.expiry_raw;
                            this.packageData.remainingDays = res.customer.remaining_days;
                        }
                    }
                    this.showToast(res.message || 'Payment recorded successfully!');
                    this.payModal = false;

                    // Refresh page after short delay to update ledger & tables
                    setTimeout(() => {
                        window.location.reload();
                    }, 1200);
                } else {
                    let errMsg = res.message || 'Failed to record payment.';
                    if (res.errors) {
                        errMsg = Object.values(res.errors).flat().join('\n');
                    }
                    this.showToast(errMsg, 'error');
                }
            } catch (err) {
                this.showToast('Network error while processing payment.', 'error');
            } finally {
                this.isPayLoading = false;
            }
        },

        openNoteModal() {
            this.closeAllModals();
            this.noteModal = true;
        },

        appendPresetNote(text) {
            if (!this.noteForm.remarks || this.noteForm.remarks.trim() === '') {
                this.noteForm.remarks = text;
            } else {
                this.noteForm.remarks = this.noteForm.remarks.trim() + '\n' + text;
            }
        },

        async submitCustomerNote() {
            this.isNoteLoading = true;
            try {
                const response = await fetch(`{{ url('reseller/customers') }}/${this.customerId}/update-note`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ remarks: this.noteForm.remarks })
                });
                const res = await response.json();
                if (res.success) {
                    this.showToast(res.message || 'Customer notes saved successfully!');
                    this.noteModal = false;
                } else {
                    this.showToast(res.message || 'Failed to save note.', 'error');
                }
            } catch (err) {
                this.showToast('Network error while saving customer note.', 'error');
            } finally {
                this.isNoteLoading = false;
            }
        }
    };
};

if (window.Alpine) {
    window.Alpine.data('customerActionBarManager', (config) => window.customerActionBarManager(config));
} else {
    document.addEventListener('alpine:init', () => {
        Alpine.data('customerActionBarManager', (config) => window.customerActionBarManager(config));
    });
}
</script>
@endpush
