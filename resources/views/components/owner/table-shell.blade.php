@props([
    'hasRows' => true,
    'emptyTitle' => 'No records found',
    'emptyMessage' => 'There are no records matching the current filters.',
    'emptyIcon' => 'fa-inbox',
])

<div class="owner-table-shell bg-white rounded-xl border border-slate-200/90 shadow-2xs overflow-hidden">
    <div class="overflow-x-auto">
        <table class="saas-table">
            @isset($head)
                <thead>
                    {{ $head }}
                </thead>
            @endisset

            @if($hasRows)
                <tbody>
                    {{ $body ?? $slot }}
                </tbody>
            @else
                <tbody>
                    <tr>
                        <td colspan="100" class="owner-table-empty">
                            <div class="flex flex-col items-center justify-center gap-2 py-8 px-4 text-center">
                                <span class="w-9 h-9 rounded-lg bg-slate-100 text-slate-400 flex items-center justify-center">
                                    <i class="fas {{ $emptyIcon }} text-sm" aria-hidden="true"></i>
                                </span>
                                <strong class="text-xs font-semibold text-slate-700">{{ $emptyTitle }}</strong>
                                <span class="text-[11px] text-slate-400">{{ $emptyMessage }}</span>
                                @isset($emptyAction)
                                    <div class="mt-1">{{ $emptyAction }}</div>
                                @endisset
                            </div>
                        </td>
                    </tr>
                </tbody>
            @endif
        </table>
    </div>

    @isset($pagination)
        <div class="owner-table-pagination px-3 py-2.5 border-t border-slate-200 bg-slate-50/70">
            {{ $pagination }}
        </div>
    @endisset
</div>
