<template>
    <div class="space-y-3">
        <div v-if="activities.length === 0" class="text-center py-6 text-slate-400 text-sm">
            No recent activity found.
        </div>
        <div 
            v-for="(act, idx) in activities" 
            :key="idx" 
            class="flex items-start gap-3 p-2.5 rounded-lg hover:bg-slate-50 transition-colors text-sm"
        >
            <div class="w-2 h-2 rounded-full bg-blue-500 mt-1.5 flex-shrink-0"></div>
            <div class="flex-1 min-w-0">
                <p class="font-medium text-slate-700 truncate">{{ act.action || act.description }}</p>
                <p class="text-xs text-slate-400">{{ formatDate(act.created_at) }}</p>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    activities: {
        type: Array,
        default: () => []
    }
})

const formatDate = (dateStr) => {
    if (!dateStr) return 'Just now'
    const d = new Date(dateStr)
    return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
}
</script>
