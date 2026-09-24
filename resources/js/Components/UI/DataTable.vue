<script setup>
defineProps({
    columns: {
        type: Array,
        default: () => [],
        // Example: [{ key: 'name', label: 'Server Name', align: 'center' }]
    },
    items: {
        type: Array,
        default: () => [],
    },
    loading: {
        type: Boolean,
        default: false,
    },
    emptyMessage: {
        type: String,
        default: 'No records found matching your criteria.',
    }
})
</script>

<template>
    <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden mb-4">
        <div class="overflow-x-auto">
            <table class="w-full text-xs border-collapse whitespace-nowrap">
                <!-- Centered Table Header Row with Crisp Column Borders -->
                <thead class="bg-slate-50/90 border-b border-slate-200 text-slate-600 font-bold uppercase tracking-wider text-[10px] select-none whitespace-nowrap">
                    <tr class="whitespace-nowrap">
                        <slot name="header" />
                    </tr>
                </thead>

                <!-- Table Body with Crisp Row & Column Grid Borders -->
                <tbody class="divide-y divide-slate-200 whitespace-nowrap">
                    <!-- Loading State Skeleton -->
                    <tr v-if="loading" v-for="n in 3" :key="'skeleton-' + n" class="animate-pulse">
                        <td colspan="100" class="py-3 px-4 text-center">
                            <div class="h-4 bg-slate-100 rounded-[2px] w-full"></div>
                        </td>
                    </tr>

                    <!-- Empty State -->
                    <tr v-else-if="items.length === 0">
                        <td colspan="100" class="py-12 px-4 text-center text-slate-400">
                            <slot name="empty">
                                <div class="max-w-xs mx-auto space-y-1.5">
                                    <p class="text-xs font-semibold text-slate-600">{{ emptyMessage }}</p>
                                    <p class="text-[11px] text-slate-400">Try adjusting your filters or search query.</p>
                                </div>
                            </slot>
                        </td>
                    </tr>

                    <!-- Data Rows Slot -->
                    <slot v-else />
                </tbody>
            </table>
        </div>

        <!-- Optional Footer Slot (Pagination) -->
        <slot name="footer" />
    </div>
</template>
