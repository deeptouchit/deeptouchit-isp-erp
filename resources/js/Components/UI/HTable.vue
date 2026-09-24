<script setup>
defineProps({
    headers: {
        type: Array,
        default: () => [] // Array of { key, label, align? }
    },
    items: {
        type: Array,
        default: () => []
    },
    emptyTitle: {
        type: String,
        default: 'No records found'
    },
    emptySubtitle: {
        type: String,
        default: 'There are no items to display in this table.'
    }
})
</script>

<template>
    <div class="bg-white rounded-2xl border border-hslate-200 shadow-hcard overflow-hidden">
        <!-- Optional Top Bar (Search, Filters, Actions) -->
        <div v-if="$slots.header || $slots.actions || $slots.search" class="p-5 border-b border-hslate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex-1">
                <slot name="header" />
                <slot name="search" />
            </div>
            <div v-if="$slots.actions" class="flex items-center gap-2">
                <slot name="actions" />
            </div>
        </div>

        <!-- Table Container -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-hslate-50/80 border-b border-hslate-200/80 text-hslate-400 uppercase font-extrabold tracking-wider text-[10px]">
                    <tr>
                        <slot name="thead">
                            <th 
                                v-for="h in headers" 
                                :key="h.key || h" 
                                :class="[
                                    'py-3.5 px-6 font-sans',
                                    h.align === 'right' ? 'text-right' : (h.align === 'center' ? 'text-center' : 'text-left')
                                ]"
                            >
                                {{ typeof h === 'object' ? h.label : h }}
                            </th>
                        </slot>
                    </tr>
                </thead>
                <tbody class="divide-y divide-hslate-100 font-medium text-hslate-700">
                    <tr v-if="items.length === 0">
                        <td :colspan="headers.length || 10" class="py-14 text-center">
                            <slot name="empty">
                                <div class="max-w-xs mx-auto space-y-2">
                                    <div class="w-12 h-12 rounded-2xl bg-brand-50 text-brand-600 flex items-center justify-center mx-auto border border-brand-100">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                        </svg>
                                    </div>
                                    <p class="text-sm font-bold text-hslate-800">{{ emptyTitle }}</p>
                                    <p class="text-xs text-hslate-400 font-medium">{{ emptySubtitle }}</p>
                                </div>
                            </slot>
                        </td>
                    </tr>

                    <slot name="tbody" />
                </tbody>
            </table>
        </div>

        <!-- Table Footer / Pagination -->
        <div v-if="$slots.footer" class="px-6 py-4 bg-hslate-50/50 border-t border-hslate-100 flex items-center justify-between">
            <slot name="footer" />
        </div>
    </div>
</template>
