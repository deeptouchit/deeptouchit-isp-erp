<div x-data="{ target: null, menuPos: {} }" @scroll.window="target = null" @resize.window="target = null" @keydown.escape.window="target = null"
     class="bg-white border border-slate-200 rounded-xl shadow-xs">
    <div class="overflow-x-auto" @scroll="target = null">
        <table class="saas-table">
            <thead>
                <tr>
                    <th scope="col" class="w-10 text-center font-semibold no-sort">#</th>
                    <th scope="col" class="text-center font-semibold">{{ __('Customer ID') }}</th>
                    <th scope="col" class="text-center font-semibold">{{ __('Customer Name') }}</th>
                    <th scope="col" class="text-center font-semibold">{{ __('Package Profile') }}</th>
                    <th scope="col" class="text-center font-semibold">{{ __('Due Amount') }}</th>
                    <th scope="col" class="text-center font-semibold">{{ __('Status') }}</th>
                    <th scope="col" class="w-10 text-center font-semibold no-sort">{{ __('Action') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers as $customer)
                    <tr>
                        <td class="text-center font-mono">{{ ($offset ?? 0) + $loop->iteration }}</td>
                        <td class="text-center font-mono">{{ $customer->customer_id }}</td>
                        <td class="text-left">{{ $customer->name }}</td>
                        <td class="text-left">{{ $customer->package_display_name }}</td>
                        <td class="text-right font-mono" data-sort-value="{{ $customer->due_amount }}">@currency($customer->due_amount)</td>
                        <td class="text-center">{{ __(ucfirst($customer->status)) }}</td>
                        <td class="text-center">
                            <button type="button" aria-label="{{ __('Action') }}" :aria-expanded="target === @js(route('reseller.customers.show', $customer->id))"
                                    @click.stop="const url = @js(route('reseller.customers.show', $customer->id));
                                        if (target === url) { target = null; return; }
                                        const rect = $el.getBoundingClientRect();
                                        menuPos = { right: Math.max(10, innerWidth - rect.right) + 'px',
                                            top: rect.bottom + 44 <= innerHeight ? (Math.round(rect.bottom) + 2) + 'px' : 'auto',
                                            bottom: rect.bottom + 44 > innerHeight ? (Math.round(innerHeight - rect.top) + 2) + 'px' : 'auto' };
                                        target = url;"
                                    class="w-7 h-7 mx-auto rounded-lg text-slate-600 hover:bg-slate-100">
                                <i class="fas fa-ellipsis-v text-[10px]" aria-hidden="true"></i>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center">
                            <i class="fas fa-users text-cyan-600 mb-2" aria-hidden="true"></i>
                            <p class="font-semibold text-slate-700">{{ __('No assigned customers match your filters.') }}</p>
                            <a href="{{ route('reseller.customers.index') }}" class="mt-2 inline-block font-semibold text-cyan-700">{{ __('Assigned Customers') }}</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div x-show="target" x-cloak :style="menuPos" @click.outside="target = null"
         class="fixed z-50 rounded-xl border border-slate-200 bg-white p-1 shadow-xl">
        <a :href="target" class="block rounded-lg px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-cyan-50 hover:text-cyan-700">
            {{ __('View Details') }}
        </a>
    </div>
</div>
