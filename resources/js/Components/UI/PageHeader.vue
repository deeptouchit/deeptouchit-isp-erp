<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { ChevronRightIcon, HomeIcon } from '@heroicons/vue/20/solid'
import RefreshButton from '@/Components/UI/RefreshButton.vue'

const props = defineProps({
    breadcrumbs: {
        type: Array,
        default: () => [],
    },
    title: {
        type: String,
        default: '',
    },
    showRefresh: {
        type: Boolean,
        default: false,
    },
    homeUrl: {
        type: String,
        default: null,
    }
})

const emit = defineEmits(['refreshed'])

const homeUrl = computed(() => {
    if (props.homeUrl) return props.homeUrl
    try {
        if (typeof route === 'function') {
            const currentRoute = route().current() || ''
            if (currentRoute && !currentRoute.startsWith('admin.') && route().has('client.dashboard')) {
                return route('client.dashboard')
            }
            if (route().has('admin.dashboard')) {
                return route('admin.dashboard')
            }
            if (route().has('client.dashboard')) {
                return route('client.dashboard')
            }
        }
        return '/client/dashboard'
    } catch {
        return '/client/dashboard'
    }
})

const SECTION_HEADERS = new Set([
    'services', 'system', 'infrastructure', 'hosting', 'business', 
    'security', 'monitoring', 'hosting & cloud infrastructure',
    'services, engines & storage', 'business, billing & support', 
    'system & global configuration', 'security & protection', 'monitoring & automation'
])

const normalizedBreadcrumbs = computed(() => {
    let list = []
    if (props.breadcrumbs && Array.isArray(props.breadcrumbs)) {
        list = props.breadcrumbs.map(item => {
            if (typeof item === 'string') {
                return { label: item, href: null }
            }
            return {
                label: item.label || item.name || item.title || '',
                href: (item.href && item.href !== '#' && item.href.trim() !== '') ? item.href : null
            }
        }).filter(b => Boolean(b.label))

        // If there are 3+ items and the first item is an unlinked generic section header, drop it to enforce clean 2-level hierarchy: Home > Category > Page
        if (list.length >= 3 && !list[0].href && SECTION_HEADERS.has(list[0].label.toLowerCase().trim())) {
            list = list.slice(1)
        }
    } else if (props.title) {
        list = [{ label: props.title, href: null }]
    }
    return list
})
</script>

<template>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 mb-3.5">
        <!-- Left: Minimal Clean Breadcrumbs Navigation -->
        <nav v-if="normalizedBreadcrumbs.length > 0" class="flex flex-wrap items-center gap-1.5 text-xs text-slate-500 min-w-0" aria-label="Breadcrumb">
            <Link :href="homeUrl" class="inline-flex items-center gap-1 text-slate-400 hover:text-blue-600 transition-colors shrink-0">
                <HomeIcon class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                <span class="text-xs font-medium">Home</span>
            </Link>

            <template v-for="(crumb, idx) in normalizedBreadcrumbs" :key="idx">
                <ChevronRightIcon class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                
                <Link 
                    v-if="crumb.href && idx !== normalizedBreadcrumbs.length - 1" 
                    :href="crumb.href" 
                    class="text-xs font-medium text-slate-500 hover:text-blue-600 transition-colors truncate max-w-[200px]"
                >
                    {{ crumb.label }}
                </Link>
                
                <span 
                    v-else 
                    :class="idx === normalizedBreadcrumbs.length - 1 ? 'text-slate-900 font-bold text-xs' : 'text-slate-600 font-medium text-xs'"
                    class="truncate max-w-[280px]"
                >
                    {{ crumb.label }}
                </span>
            </template>
        </nav>

        <!-- Right: Module Action Buttons Slot -->
        <div class="flex items-center gap-2 shrink-0 self-start sm:self-center">
            <RefreshButton v-if="showRefresh" @refreshed="emit('refreshed')" />
            <slot name="actions" />
        </div>
    </div>
</template>
