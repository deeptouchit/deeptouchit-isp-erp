@extends('reseller.layouts.app')

@section('title', __('Customer Details'))

@section('content')
<div class="max-w-4xl mx-auto space-y-4">
    <div class="rounded-xl border border-slate-200 bg-white p-3 flex items-center gap-2 shadow-xs">
        <a href="{{ route('reseller.customers.index') }}" aria-label="{{ __('Assigned Customers') }}" class="w-8 h-8 rounded-lg bg-slate-100 inline-flex items-center justify-center text-slate-700"><i class="fas fa-arrow-left" aria-hidden="true"></i></a>
        <h1 class="text-sm font-bold text-slate-800">{{ $customer->name }}</h1>
    </div>
    <dl class="grid grid-cols-2 sm:grid-cols-4 gap-3 bg-white p-5 rounded-xl border border-slate-200 shadow-xs">
        @foreach([['Customer ID', $customer->customer_id], ['Phone', $customer->phone], ['Package Profile', $customer->package_display_name]] as [$label, $value])
            <div>
                <dt class="text-xs text-slate-500">{{ __($label) }}</dt>
                <dd class="mt-1 text-sm font-semibold text-slate-800 break-words">{{ $value ?: __('N/A') }}</dd>
            </div>
        @endforeach
        <div>
            <dt class="text-xs text-slate-500">{{ __('Due Amount') }}</dt>
            <dd class="mt-1 text-sm font-bold font-mono text-slate-800">@currency($due)</dd>
        </div>
    </dl>
    @if($due > 0)
        <form method="POST" action="{{ route('reseller.customers.pay-bill', $customer->id) }}" x-data="{ sending: false }" @submit="sending = true"
              class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs space-y-4">
            @csrf
            <h2 class="text-sm font-bold text-slate-800">{{ __('Collect Cash') }}</h2>
            <input type="hidden" name="request_key" value="{{ old('request_key', $requestKey) }}">
            <input type="hidden" name="payment_method" value="cash">
            <div>
                <label for="collector-amount" class="block text-xs font-medium text-slate-700 mb-1">{{ __('Amount') }}</label>
                <input id="collector-amount" name="amount" type="number" inputmode="decimal" min="0.01" max="{{ $due }}" step="0.01" required value="{{ old('amount', $due) }}"
                       class="w-full sm:max-w-xs rounded-lg border px-3 py-1.5 text-xs focus:bg-white focus:border-cyan-500 @error('amount') border-rose-400 ring-1 ring-rose-100 bg-rose-50/30 @else bg-slate-50 border-slate-200 @enderror">
                @error('amount') <p role="alert" class="mt-1 text-xs text-rose-700">{{ $message }}</p> @enderror
                @error('request_key') <p role="alert" class="mt-1 text-xs text-rose-700">{{ $message }}</p> @enderror
            </div>
            <div class="flex justify-center">
                <button type="submit" :disabled="sending" class="rounded-lg bg-cyan-600 px-5 py-2 text-xs font-semibold text-white shadow-xs hover:bg-cyan-700 disabled:opacity-50">{{ __('Record Cash Payment') }}</button>
            </div>
        </form>
    @else
        <div class="rounded-xl border border-slate-200 bg-white p-4 text-xs text-slate-600">{{ __('No outstanding balance.') }}</div>
    @endif
</div>
@endsection
