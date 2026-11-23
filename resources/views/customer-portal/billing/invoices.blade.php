@extends('customer-portal.layouts.app')
@section('title', 'Bills')
@section('content')
<section class="space-y-3"><div><p class="text-xs text-cyan-600 font-semibold">Billing</p><h2 class="text-xl font-bold">My bills</h2></div>
@forelse($invoices as $invoice)<a href="{{ route('customer.portal.invoice', ['tenant' => $tenant->slug, 'invoice' => $invoice->id]) }}" class="block rounded-2xl bg-white border border-slate-200 p-4 shadow-sm"><div class="flex items-center justify-between gap-3"><div><p class="text-xs font-bold text-slate-900">{{ $invoice->invoice_no }}</p><p class="mt-1 text-[11px] text-slate-500">Due {{ $invoice->due_date?->format('d M Y') ?? '—' }}</p></div><span class="px-2 py-1 rounded-lg text-[10px] font-semibold {{ $invoice->status_badge['class'] }}">{{ $invoice->status_badge['label'] }}</span></div><div class="mt-3 flex items-end justify-between"><span class="text-xs text-slate-500">Amount due</span><span class="text-lg font-bold font-mono">@currency($invoice->due_amount)</span></div></a>@empty<div class="rounded-2xl border border-dashed border-slate-300 p-8 text-center"><i class="fas fa-receipt text-cyan-500"></i><p class="mt-2 text-sm font-semibold">No bills found</p><p class="mt-1 text-xs text-slate-500">Your invoices will appear here.</p></div>@endforelse
@if($invoices->hasPages())<div>{{ $invoices->links() }}</div>@endif</section>
@endsection
