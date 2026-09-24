<script setup>
import { MagnifyingGlassIcon, ArrowPathIcon, FunnelIcon } from '@heroicons/vue/20/solid'

const model = defineModel('search', { type: String, default: '' })

defineProps({
    placeholder: {
        type: String,
        default: 'Search records...',
    },
    hasActiveFilters: {
        type: Boolean,
        default: false,
    }
})

const emit = defineEmits(['apply', 'clear'])
</script>

<template>
    <div class="bg-white p-2.5 rounded-[4px] border border-[#E2E8F0] shadow-2xs mb-3.5 flex flex-col lg:flex-row lg:items-center justify-between gap-2.5">
        <!-- Left: Search Input & Filter Dropdowns -->
        <div class="flex flex-1 flex-wrap items-center gap-2">
            <!-- Search Bar -->
            <div class="relative flex-1 min-w-[200px] max-w-xs">
                <MagnifyingGlassIcon class="w-4 h-4 text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2 pointer-events-none" />
                <input
                    v-model="model"
                    type="text"
                    :placeholder="placeholder"
                    @keyup.enter="emit('apply')"
                    class="w-full pl-8 pr-3 py-1.5 text-xs font-medium rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition-all shadow-2xs"
                />
            </div>

            <!-- Dynamic Dropdown Filter Slots -->
            <div class="flex flex-wrap items-center gap-2">
                <slot name="filters" />
            </div>

            <!-- Filter (Apply) & Reset Buttons -->
            <div class="flex items-center gap-1.5 shrink-0">
                <button
                    type="button"
                    @click="emit('apply')"
                    class="px-2.5 py-1.5 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-[3px] shadow-2xs flex items-center gap-1 transition-colors cursor-pointer"
                >
                    <FunnelIcon class="w-3.5 h-3.5" />
                    <span>Filter</span>
                </button>

                <button
                    v-if="hasActiveFilters"
                    type="button"
                    @click="emit('clear')"
                    class="px-2.5 py-1.5 text-xs font-bold text-slate-600 hover:text-rose-600 hover:bg-rose-50 rounded-[3px] border border-slate-200 hover:border-rose-200 flex items-center gap-1 transition-colors cursor-pointer"
                    title="Reset All Filters"
                >
                    <ArrowPathIcon class="w-3.5 h-3.5 text-slate-400" />
                    <span>Reset</span>
                </button>
            </div>
        </div>

        <!-- Right: Actions Slot (Bulk Actions, Export, View Toggle) -->
        <div class="flex items-center gap-2 shrink-0">
            <slot name="actions" />
        </div>
    </div>
</template>
