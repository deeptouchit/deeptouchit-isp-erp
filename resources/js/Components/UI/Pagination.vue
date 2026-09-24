<script setup>
import { Link } from '@inertiajs/vue3'
import { ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/20/solid'

defineProps({
    links: {
        type: Array,
        default: () => [],
    },
    meta: {
        type: Object,
        default: () => ({}),
    }
})
</script>

<template>
    <div v-if="(links && links.length > 3) || (meta && meta.total > 0)" class="bg-white p-3 border-t border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 select-none text-xs">
        <!-- Left: Summary Info -->
        <div class="text-[11.5px] text-slate-500 font-medium">
            <template v-if="meta?.from && meta?.to && meta?.total">
                Showing <span class="font-bold text-slate-900 font-mono">{{ meta.from }}</span> to <span class="font-bold text-slate-900 font-mono">{{ meta.to }}</span> of <span class="font-bold text-slate-900 font-mono">{{ meta.total }}</span> results
            </template>
            <template v-else-if="meta?.total">
                Total <span class="font-bold text-slate-900 font-mono">{{ meta.total }}</span> results
            </template>
        </div>

        <!-- Right: Pagination Buttons -->
        <div v-if="links && links.length > 3" class="flex items-center gap-1">
            <template v-for="(link, i) in links" :key="i">
                <!-- Disabled Link -->
                <span
                    v-if="!link.url"
                    class="px-2.5 py-1 text-slate-300 border border-slate-100 rounded-[3px] text-[11px] font-semibold cursor-not-allowed"
                    v-html="link.label"
                />

                <!-- Active / Clickable Link -->
                <Link
                    v-else
                    :href="link.url"
                    preserve-scroll
                    preserve-state
                    :class="link.active ? 'bg-blue-600 text-white font-bold border-blue-600 shadow-2xs' : 'bg-white hover:bg-slate-50 text-slate-700 font-semibold border-slate-200'"
                    class="px-2.5 py-1 border rounded-[3px] text-[11px] transition-all cursor-pointer"
                    v-html="link.label"
                />
            </template>
        </div>
    </div>
</template>
