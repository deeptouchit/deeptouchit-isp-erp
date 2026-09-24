<template>
    <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6 flex items-center justify-between hover:shadow-md transition-shadow">
        <div>
            <p class="text-sm font-medium text-slate-500 mb-1">{{ label }}</p>
            <h3 class="text-2xl font-bold text-slate-800">
                <span v-if="prefix">{{ prefix }}</span>{{ formattedValue }}
            </h3>
        </div>
        <div :class="[colorBgClass, 'p-3.5 rounded-xl flex items-center justify-center']">
            <component :is="resolvedIcon" :class="[colorTextClass, 'w-6 h-6']" />
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { 
    UsersIcon, 
    ServerIcon, 
    GlobeAltIcon, 
    CurrencyDollarIcon,
    CircleStackIcon,
    ShieldCheckIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    icon: { type: String, default: 'users' },
    value: { type: [Number, String], default: 0 },
    label: { type: String, default: '' },
    color: { type: String, default: 'blue' },
    prefix: { type: String, default: '' }
})

const formattedValue = computed(() => {
    if (typeof props.value === 'number') {
        return props.value.toLocaleString()
    }
    return props.value
})

const resolvedIcon = computed(() => {
    switch (props.icon) {
        case 'users': return UsersIcon
        case 'server': return ServerIcon
        case 'globe': return GlobeAltIcon
        case 'dollar': return CurrencyDollarIcon
        case 'database': return CircleStackIcon
        case 'shield': return ShieldCheckIcon
        default: return UsersIcon
    }
})

const colorBgClass = computed(() => {
    switch (props.color) {
        case 'blue': return 'bg-blue-50'
        case 'green': return 'bg-emerald-50'
        case 'purple': return 'bg-purple-50'
        case 'yellow': return 'bg-amber-50'
        case 'red': return 'bg-rose-50'
        default: return 'bg-slate-50'
    }
})

const colorTextClass = computed(() => {
    switch (props.color) {
        case 'blue': return 'text-blue-600'
        case 'green': return 'text-emerald-600'
        case 'purple': return 'text-purple-600'
        case 'yellow': return 'text-amber-600'
        case 'red': return 'text-rose-600'
        default: return 'text-slate-600'
    }
})
</script>
