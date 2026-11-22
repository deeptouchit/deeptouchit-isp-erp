@extends('reseller.layouts.app')

@section('title', __('Support Tickets'))

@push('styles')
    {{-- Page-specific CSS --}}
@endpush

@section('content')
<div class="space-y-3" x-data="ticketManager()" @scroll.window="activeMenu = null" @resize.window="activeMenu = null">

    {{-- 1. TOP HEADER BAR (AGENTS.md Rule 2.A: Icon + Title + Action buttons ONLY) --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 bg-white p-3 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 border border-purple-100 flex items-center justify-center text-sm shadow-2xs flex-shrink-0">
                <i class="fas fa-headset"></i>
            </div>
            <h1 class="text-sm font-bold text-slate-800 tracking-tight">{{ __('Support Tickets') }}</h1>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('reseller.tickets.print', $reportQuery) }}" target="_blank" rel="noopener" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-print text-slate-500 text-xs"></i>
                <span>{{ __('Print Statement') }}</span>
            </a>
            <a href="{{ route('reseller.tickets.export', $reportQuery) }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200/80 transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-file-csv text-slate-500 text-xs"></i>
                <span>{{ __('Export CSV') }}</span>
            </a>
            <button type="button" @click="openCreateModal = true" class="px-3.5 py-1.5 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fas fa-plus text-xs"></i>
                <span>{{ __('Create Ticket') }}</span>
            </button>
        </div>
    </div>

    {{-- 2. KPI SUMMARY STRIP (Strictly 6 Cards - AGENTS.md Rule 2.B) --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">

        {{-- Card 1: Total Tickets --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('All Tickets') }}</span>
                <span class="text-[13px] font-bold font-mono text-slate-900 leading-tight block">
                    {{ number_format($stats['total']) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-slate-200 bg-slate-50 text-slate-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-ticket"></i>
            </div>
        </div>

        {{-- Card 2: Open Tickets --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Open') }}</span>
                <span class="text-[13px] font-bold font-mono text-emerald-600 leading-tight block">
                    {{ number_format($stats['open']) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-emerald-200 bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-folder-open"></i>
            </div>
        </div>

        {{-- Card 3: In Progress --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('In Progress') }}</span>
                <span class="text-[13px] font-bold font-mono text-indigo-600 leading-tight block">
                    {{ number_format($stats['in_progress']) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-indigo-200 bg-indigo-50 text-indigo-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-spinner"></i>
            </div>
        </div>

        {{-- Card 4: Answered --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Answered') }}</span>
                <span class="text-[13px] font-bold font-mono text-purple-700 leading-tight block">
                    {{ number_format($stats['answered']) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-purple-200 bg-purple-50 text-purple-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-reply-all"></i>
            </div>
        </div>

        {{-- Card 5: Urgent Issues --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Urgent') }}</span>
                <span class="text-[13px] font-bold font-mono text-rose-600 leading-tight block">
                    {{ number_format($stats['urgent']) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-rose-200 bg-rose-50 text-rose-600 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-bolt"></i>
            </div>
        </div>

        {{-- Card 6: Closed Tickets --}}
        <div class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-1">
                <span class="text-[9px] font-medium uppercase tracking-wider text-slate-500 block truncate">{{ __('Closed') }}</span>
                <span class="text-[13px] font-bold font-mono text-slate-500 leading-tight block">
                    {{ number_format($stats['closed']) }}
                </span>
            </div>
            <div class="w-6 h-6 rounded-md text-[10px] border border-slate-200 bg-slate-50 text-slate-500 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-circle-check"></i>
            </div>
        </div>

    </div>

    @php($moreCount = (int) ($priority !== 'all'))
    <form method="GET" action="{{ route('reseller.tickets.index') }}"
          x-data="{ showMoreFilters: @js($moreCount > 0), searchQuery: @js($search) }"
          class="bg-white p-2.5 rounded-xl border border-slate-200/90 shadow-2xs space-y-2.5">
        <div class="flex flex-col md:flex-row items-stretch md:items-center gap-2">
            <div class="relative flex-1 min-w-[200px]">
                <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-xs pointer-events-none"
                   :class="searchQuery ? 'text-cyan-600 font-bold' : 'text-slate-400'" aria-hidden="true"></i>
                <input type="search" name="search" value="{{ $search }}" x-model="searchQuery" aria-label="{{ __('Search') }}"
                       placeholder="{{ __('Search ticket number or subject') }}"
                       class="h-[34px] w-full pl-8 pr-7 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-cyan-500 focus:outline-hidden">
                <button type="button" x-show="searchQuery" x-cloak aria-label="{{ __('Clear Search') }}"
                        @click="searchQuery = ''; $nextTick(() => $el.closest('form').requestSubmit())"
                        class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700">
                    <i class="fas fa-times" aria-hidden="true"></i>
                </button>
            </div>
            <select name="department" aria-label="{{ __('Department') }}" class="h-[34px] w-full md:w-36 px-2 rounded-lg border border-slate-200 bg-slate-50 text-xs">
                <option value="all">{{ __('All Departments') }}</option>
                @foreach($departments as $value => $label)
                    <option value="{{ $value }}" @selected($department === $value)>{{ __($label) }}</option>
                @endforeach
            </select>
            <select name="status" aria-label="{{ __('Status') }}" class="h-[34px] w-full md:w-32 px-2 rounded-lg border border-slate-200 bg-slate-50 text-xs">
                @foreach(['all' => 'All Status', 'open' => 'Open Tickets', 'in_progress' => 'In Progress', 'answered' => 'Answered', 'resolved' => 'Resolved', 'closed' => 'Closed'] as $value => $label)
                    <option value="{{ $value }}" @selected($status === $value)>{{ __($label) }}</option>
                @endforeach
            </select>
            <select name="per_page" aria-label="{{ __('Rows per page') }}" onchange="this.form.requestSubmit()" class="h-[34px] w-full md:w-28 px-2 rounded-lg border border-slate-200 bg-slate-50 text-xs">
                @foreach($pageSizes as $size)
                    <option value="{{ $size }}" @selected($perPage === $size)>{{ __(':count / page', ['count' => $size]) }}</option>
                @endforeach
            </select>
            <button type="button" @click="showMoreFilters = !showMoreFilters" :aria-expanded="showMoreFilters" aria-controls="ticket-more-filters"
                    class="h-[34px] px-2.5 py-0 rounded-lg border border-slate-200 bg-slate-50 text-xs font-semibold flex items-center justify-center gap-1.5 whitespace-nowrap">
                <i class="fas fa-sliders-h text-[10px]" aria-hidden="true"></i>{{ __('More Filters') }}
                @if($moreCount)
                    <span class="px-1.5 rounded-full bg-cyan-600 text-white text-[9px]">{{ $moreCount }}</span>
                @endif
                <i class="fas text-[9px]" :class="showMoreFilters ? 'fa-chevron-up text-cyan-600' : 'fa-chevron-down text-slate-400'" aria-hidden="true"></i>
            </button>
            <div class="flex items-center gap-1.5 flex-shrink-0 md:ml-auto">
                <button type="submit" class="h-[34px] px-3 py-0 rounded-lg bg-cyan-600 hover:bg-cyan-700 text-white text-xs font-semibold flex items-center justify-center gap-1.5">
                    <i class="fas fa-filter text-[10px]" aria-hidden="true"></i>{{ __('Filter') }}
                </button>
                <a href="{{ route('reseller.tickets.index') }}" class="h-[34px] px-2.5 py-0 rounded-lg border border-slate-200/80 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold flex items-center justify-center gap-1">
                    <i class="fas fa-rotate-left text-[10px]" aria-hidden="true"></i>{{ __('Reset') }}
                </a>
            </div>
        </div>
        <div id="ticket-more-filters" x-show="showMoreFilters" x-cloak x-transition class="pt-2 border-t border-slate-100 flex flex-wrap items-center gap-2">
            <select name="priority" aria-label="{{ __('Priority') }}" class="h-[34px] w-full sm:w-40 px-2 rounded-lg border border-slate-200 bg-slate-50 text-xs">
                @foreach(['all' => 'All Priorities', 'urgent' => 'Urgent', 'high' => 'High', 'medium' => 'Medium', 'low' => 'Low'] as $value => $label)
                    <option value="{{ $value }}" @selected($priority === $value)>{{ __($label) }}</option>
                @endforeach
            </select>
        </div>
    </form>

    {{-- 4. MASTER COMPACT TABLE (AGENTS.md Rule 2.D: Max 5-7 Minimal Columns) --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="saas-table">
                <thead>
                    <tr>
                        <th scope="col" class="text-center font-semibold w-10 no-sort">#</th>
                        <th scope="col" class="text-center font-semibold w-28">{{ __('Ticket ID') }}</th>
                        <th scope="col" class="text-center font-semibold">{{ __('Subject') }}</th>
                        <th scope="col" class="text-center font-semibold w-32">{{ __('Department') }}</th>
                        <th scope="col" class="text-center font-semibold w-24">{{ __('Priority') }}</th>
                        <th scope="col" class="text-center font-semibold w-24">{{ __('Status') }}</th>
                        <th scope="col" class="text-center font-semibold w-16 no-sort">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tickets as $ticket)
                        <tr>
                            <!-- 1. Index -->
                            <td class="text-center font-mono text-slate-500">
                                {{ $tickets->firstItem() + $loop->index }}
                            </td>

                            <!-- 2. Ticket ID -->
                            <td class="text-center font-mono font-bold text-cyan-700">
                                <a href="{{ route('reseller.tickets.show', $ticket->id) }}" class="hover:underline">
                                    {{ $ticket->ticket_number }}
                                </a>
                            </td>

                            <!-- 3. Subject (Single data) -->
                            <td class="font-semibold text-slate-800">
                                <a href="{{ route('reseller.tickets.show', $ticket->id) }}" class="hover:text-purple-700 transition">
                                    {{ $ticket->subject }}
                                </a>
                            </td>

                            <!-- 4. Department -->
                            <td class="text-left text-slate-600">
                                {{ __($departments[$ticket->department] ?? ucfirst($ticket->department ?? 'general')) }}
                            </td>

                            <!-- 5. Priority -->
                            <td class="text-center">
                                <span class="px-2 py-0.5 rounded border text-[10px] font-semibold {{ $ticket->priority_badge_color }}">
                                    {{ __(ucwords(str_replace('_', ' ', $ticket->priority))) }}
                                </span>
                            </td>

                            <!-- 6. Status -->
                            <td class="text-center">
                                <span class="px-2 py-0.5 rounded border text-[10px] font-semibold {{ $ticket->status_badge_color }}">
                                    {{ __(ucwords(str_replace('_', ' ', $ticket->status))) }}
                                </span>
                            </td>

                            <!-- 7. Action: Global Floating 3-Dot Menu (AGENTS.md Rule 2.E) -->
                            <td class="text-center">
                                <button type="button"
                                        @click.stop="toggleMenu(@js([
                                            'id' => $ticket->id,
                                            'showUrl' => route('reseller.tickets.show', $ticket->id),
                                            'closeUrl' => $ticket->isOpen() ? route('reseller.tickets.close', $ticket->id) : null,
                                        ]), $event)"
                                        aria-label="{{ __('Ticket actions') }}"
                                        class="w-7 h-7 rounded-lg hover:bg-slate-100 text-slate-500 hover:text-slate-800 transition flex items-center justify-center mx-auto cursor-pointer">
                                    <i class="fas fa-ellipsis-v text-[10px]"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-8 text-slate-400">
                                <i class="fas fa-headset text-3xl mb-2 block text-slate-300"></i>
                                <p class="font-semibold text-slate-700">{{ __('No support tickets match your filters.') }}</p>
                                <button type="button" @click="openCreateModal = true" class="mt-2 text-xs font-semibold text-cyan-700 hover:underline">{{ __('Create Ticket') }}</button>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($tickets->hasPages())
            <div class="px-4 py-2.5 bg-white border-t border-slate-200 flex items-center justify-between text-xs">
                <div>
                    {{ __('Showing :first to :last of :total tickets', ['first' => $tickets->firstItem(), 'last' => $tickets->lastItem(), 'total' => $tickets->total()]) }}
                </div>
                <div>
                    {{ $tickets->links() }}
                </div>
            </div>
        @endif
    </div>

    {{-- 5. FLOATING 3-DOT ACTION MENU (AGENTS.md Rule 2.E) --}}
    <div x-show="activeMenu"
         @click.outside="activeMenu = null"
         class="fixed z-50 bg-white rounded-xl border border-slate-200 shadow-xl py-1 w-44 text-xs space-y-0.5"
         :style="menuPos"
         style="display: none;"
         x-cloak>
        <a :href="activeMenu?.showUrl" class="flex items-center gap-2 px-3 py-1.5 text-slate-700 hover:bg-slate-50 hover:text-cyan-600">
            <i class="fas fa-comments w-4 text-cyan-600" aria-hidden="true"></i>{{ __('View Thread') }}
        </a>
        <form x-show="activeMenu?.closeUrl" :action="activeMenu?.closeUrl" method="POST"
              @submit="if (!confirm(@js(__('Mark this support ticket as closed?')))) $event.preventDefault()">
            @csrf
            <button type="submit" class="w-full flex items-center gap-2 px-3 py-1.5 text-rose-600 hover:bg-rose-50 text-left">
                <i class="fas fa-check-circle w-4" aria-hidden="true"></i>{{ __('Close Ticket') }}
            </button>
        </form>
    </div>

    {{-- 6. CREATE NEW TICKET MODAL (AGENTS.md Rule 3: Natural Soft Modal) --}}
    <div x-show="openCreateModal"
         @keydown.escape.window="openCreateModal = false; activeMenu = null"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 fixed inset-0 z-50"
         style="display: none;"
         x-cloak>

        <div role="dialog" aria-modal="true" aria-labelledby="create-ticket-title"
             class="bg-white rounded-xl border border-slate-200/90 shadow-2xl w-full max-w-xl max-h-[calc(100dvh-2rem)] overflow-y-auto"
             @click.outside="openCreateModal = false">

            <!-- Soft Header -->
            <div class="bg-slate-50/80 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-purple-50 text-purple-600 border border-purple-100 flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-headset"></i>
                    </div>
                    <div>
                        <h3 id="create-ticket-title" class="text-xs font-semibold text-slate-800">{{ __('Open Support Ticket') }}</h3>
                    </div>
                </div>
                <button type="button" @click="openCreateModal = false" aria-label="{{ __('Close') }}" class="w-6 h-6 rounded-md hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition flex items-center justify-center cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <!-- Body Form -->
            <form action="{{ route('reseller.tickets.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="p-4 space-y-3">
                    <!-- Subject -->
                    <div>
                        <label for="subject" class="block text-[11px] font-semibold text-slate-700 mb-1">
                            {{ __('Subject') }} <span class="text-rose-500">*</span>
                        </label>
                        <input type="text"
                               id="subject"
                               name="subject"
                               required
                               maxlength="255" value="{{ old('subject') }}"
                               class="w-full border @error('subject') border-rose-400 bg-rose-50/30 @else bg-slate-50 border-slate-200 @enderror rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 shadow-2xs">
                            @error('subject') <p class="mt-1 text-xs text-rose-700" role="alert">{{ $message }}</p> @enderror
                    </div>

                    <!-- Department & Priority -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label for="modal_department" class="block text-[11px] font-semibold text-slate-700 mb-1">
                                {{ __('Department') }} <span class="text-rose-500">*</span>
                            </label>
                            <select id="modal_department" name="department" required class="w-full border @error('department') border-rose-400 bg-rose-50/30 @else bg-slate-50 border-slate-200 @enderror rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 shadow-2xs">
                                @foreach(collect($departments)->only(['technical', 'billing', 'network', 'package', 'general']) as $value => $label)
                                    <option value="{{ $value }}" @selected(old('department') === $value)>{{ __($label) }}</option>
                                @endforeach
                            </select>
                            @error('department') <p class="mt-1 text-xs text-rose-700" role="alert">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="modal_priority" class="block text-[11px] font-semibold text-slate-700 mb-1">
                                {{ __('Priority') }} <span class="text-rose-500">*</span>
                            </label>
                            <select id="modal_priority" name="priority" required class="w-full border @error('priority') border-rose-400 bg-rose-50/30 @else bg-slate-50 border-slate-200 @enderror rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 shadow-2xs">
                                <option value="medium" @selected(old('priority', 'medium') === 'medium')>{{ __('Medium') }}</option>
                                <option value="high" @selected(old('priority', 'medium') === 'high')>{{ __('High') }}</option>
                                <option value="urgent" @selected(old('priority', 'medium') === 'urgent')>{{ __('Urgent') }}</option>
                                <option value="low" @selected(old('priority', 'medium') === 'low')>{{ __('Low') }}</option>
                            </select>
                            @error('priority') <p class="mt-1 text-xs text-rose-700" role="alert">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <!-- Detailed Message -->
                    <div>
                        <label for="modal_message" class="block text-[11px] font-semibold text-slate-700 mb-1">
                            {{ __('Issue Description') }} <span class="text-rose-500">*</span>
                        </label>
                        <textarea id="modal_message"
                                  name="message"
                                  rows="4"
                                  required
                                  maxlength="5000"
                                  class="w-full border @error('message') border-rose-400 bg-rose-50/30 @else bg-slate-50 border-slate-200 @enderror rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 shadow-2xs">{{ old('message') }}</textarea>
                            @error('message') <p class="mt-1 text-xs text-rose-700" role="alert">{{ $message }}</p> @enderror
                    </div>

                    <!-- File Attachment -->
                    <div>
                        <label for="modal_attachment" class="block text-[11px] font-semibold text-slate-700 mb-1">
                            {{ __('Attachment (Screenshot / Log)') }}
                        </label>
                        <input type="file"
                               id="modal_attachment"
                               name="attachment" accept=".jpg,.jpeg,.png,.pdf,.txt,.log,.zip"
                               class="w-full border @error('attachment') border-rose-400 bg-rose-50/30 @else bg-slate-50 border-slate-200 @enderror rounded-lg text-xs px-3 py-1.5 focus:bg-white focus:border-cyan-500 shadow-2xs file:mr-2 file:py-0.5 file:px-2 file:rounded file:border-0 file:text-[10px] file:font-semibold file:bg-purple-100 file:text-purple-700">
                            @error('attachment') <p class="mt-1 text-xs text-rose-700" role="alert">{{ $message }}</p> @enderror
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button" @click="openCreateModal = false" class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-medium text-xs px-3.5 py-1.5 rounded-lg transition cursor-pointer">
                        {{ __('Cancel') }}
                    </button>
                    <button type="submit" class="bg-cyan-600 hover:bg-cyan-700 text-white font-medium text-xs px-4 py-1.5 rounded-lg shadow-xs transition cursor-pointer">
                        {{ __('Submit Ticket') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function ticketManager() {
    return {
        openCreateModal: @js($errors->any() || (session()->has('error') && old('subject') !== null)),
        activeMenu: null,
        menuPos: {},

        toggleMenu(item, event) {
            if (this.activeMenu?.id === item.id) {
                this.activeMenu = null;
                return;
            }
            this.activeMenu = item;
            const rect = event.currentTarget.getBoundingClientRect();
            const dropdownHeight = 120;
            const right = Math.max(10, window.innerWidth - rect.right);
            let top = Math.round(rect.bottom) + 2;
            let bottom = 'auto';

            if (top + dropdownHeight > window.innerHeight) {
                top = 'auto';
                bottom = Math.max(10, window.innerHeight - Math.round(rect.top) + 2) + 'px';
            } else {
                top = `${top}px`;
            }

            this.menuPos = {
                top: top,
                bottom: bottom,
                right: `${right}px`,
                left: 'auto'
            };
        }
    };
}
</script>
@endpush
