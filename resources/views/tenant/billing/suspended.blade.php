@extends('tenant.layouts.app')

@section('title', 'Service Suspended - ' . ($tenant->company_name ?? $tenant->name ?? 'ISP Portal'))

{{-- 1. Page Specific Stylesheets --}}
@push('styles')
<style>
    .lockdown-pulse {
        animation: pulse-ring 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }
    @keyframes pulse-ring {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: .75; transform: scale(1.05); }
    }
</style>
@endpush

{{-- 2. Main Workspace Body --}}
@section('content')
<div class="max-w-2xl mx-auto space-y-4 py-4 sm:py-8">
    
    <!-- Main Suspension Lockdown Card -->
    <div class="bg-white rounded-2xl border border-rose-200 shadow-lg p-6 sm:p-8 text-center space-y-6 overflow-hidden relative">
        
        <!-- Background Ambient Accent -->
        <div class="absolute -top-12 -right-12 w-36 h-36 bg-rose-50 rounded-full blur-2xl pointer-events-none"></div>
        <div class="absolute -bottom-12 -left-12 w-36 h-36 bg-amber-50 rounded-full blur-2xl pointer-events-none"></div>

        <!-- Warning Lock Icon -->
        <div class="w-16 h-16 rounded-2xl bg-rose-50 text-rose-600 border border-rose-200 flex items-center justify-center mx-auto text-2xl shadow-xs lockdown-pulse">
            <i class="fas fa-lock"></i>
        </div>

        <!-- Title & Subtitle -->
        <div class="space-y-1.5">
            <h1 class="text-lg sm:text-xl font-bold text-slate-900 tracking-tight">
                Subscription Access Suspended
            </h1>
            <p class="text-xs text-slate-500 max-w-md mx-auto leading-relaxed">
                Your ISP platform operations have been temporarily paused due to pending SaaS subscription renewal dues.
            </p>
        </div>

        <!-- Data Safety Reassurance Box -->
        <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 text-left space-y-1">
            <div class="flex items-center gap-1.5 font-bold text-emerald-700 text-xs">
                <i class="fas fa-shield-alt text-emerald-600"></i>
                <span>Your Data is 100% Safe &amp; Preserved</span>
            </div>
            <p class="text-slate-500 leading-relaxed text-[11px] font-normal">
                Your subscribers database, MikroTik sync configs, billing records, and radius logs remain fully intact. Clear the outstanding balance below to immediately restore automated ISP operations.
            </p>
        </div>

        <!-- Outstanding Amount Card -->
        <div class="p-4 rounded-xl bg-gradient-to-br from-rose-50 to-amber-50 border border-rose-200/80">
            <span class="text-[10px] font-bold text-rose-700 uppercase tracking-wider block">
                Total Outstanding Renewal Due
            </span>
            <span class="text-2xl sm:text-3xl font-extrabold text-slate-900 font-mono mt-1 block">
                ৳{{ number_format($totalDue, 0) }}
            </span>
            @if($outstandingInvoice)
                <span class="text-[11px] text-slate-500 mt-0.5 block font-mono">
                    Invoice #{{ $outstandingInvoice->invoice_no }} (Due: {{ $outstandingInvoice->due_date ? \Carbon\Carbon::parse($outstandingInvoice->due_date)->format('d M Y') : 'Immediate' }})
                </span>
            @endif
        </div>

        <!-- Action Buttons -->
        <div class="space-y-2.5 pt-1">
            @if($outstandingInvoice)
                <a href="{{ route('tenant.billing.invoice.show', $outstandingInvoice) }}" 
                   class="w-full py-2.5 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs shadow-xs transition flex items-center justify-center gap-2 cursor-pointer">
                    <i class="fas fa-credit-card text-[11px]"></i>
                    <span>Pay Now to Reactivate Service</span>
                </a>
            @else
                <a href="{{ route('tenant.billing.dashboard') }}" 
                   class="w-full py-2.5 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs shadow-xs transition flex items-center justify-center gap-2 cursor-pointer">
                    <i class="fas fa-file-invoice-dollar text-[11px]"></i>
                    <span>View Billing Portal</span>
                </a>
            @endif

            <div class="flex items-center justify-center gap-3 pt-2">
                <a href="{{ route('tenant.tickets.create') }}" 
                   class="text-xs font-semibold text-slate-600 hover:text-blue-600 transition inline-flex items-center gap-1">
                    <i class="fas fa-headset text-[10px]"></i>
                    <span>Contact Support</span>
                </a>
                <span class="text-slate-300">•</span>
                <a href="{{ route('tenant.billing.invoices') }}" 
                   class="text-xs font-semibold text-slate-600 hover:text-blue-600 transition inline-flex items-center gap-1">
                    <i class="fas fa-receipt text-[10px]"></i>
                    <span>Invoices History</span>
                </a>
            </div>
        </div>

    </div>

</div>
@endsection

{{-- 3. Page Specific Scripts --}}
@push('scripts')
<script>
    // Suspended state scripts
</script>
@endpush
