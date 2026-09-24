<script setup>
import { ref, onMounted } from 'vue'
import { ShieldExclamationIcon, CheckCircleIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    serverId: {
        type: Number,
        required: true,
    }
})

const alerts = ref([])
const isLoading = ref(false)

const fetchAlerts = async () => {
    isLoading.value = true
    try {
        const response = await fetch(`/api/v1/servers/${props.serverId}/monitoring/alerts`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        if (response.ok) {
            const data = await response.json()
            alerts.value = data.data || []
        }
    } catch (e) {
        alerts.value = []
    } finally {
        isLoading.value = false
    }
}

onMounted(() => {
    fetchAlerts()
})

const formatDate = (dateStr) => {
    if (!dateStr) return '—'
    try {
        const d = new Date(dateStr)
        if (isNaN(d.getTime())) return dateStr
        return new Intl.DateTimeFormat('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
            second: '2-digit',
            hour12: true,
        }).format(d)
    } catch {
        return dateStr
    }
}
</script>

<template>
    <div class="space-y-3 bg-white p-4 rounded-lg border border-slate-200 shadow-xs">
        <div class="flex items-center justify-between">
            <div>
                <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                    Active & Recent Monitoring Alerts
                </h4>
                <p class="text-[11px] text-slate-400">
                    Threshold violations and health events detected on this node.
                </p>
            </div>
        </div>

        <div v-if="isLoading" class="py-6 text-center text-xs text-slate-400">
            Loading alert states...
        </div>

        <div v-else-if="alerts.length === 0" class="py-6 text-center text-xs text-slate-500 flex items-center justify-center gap-2 font-medium">
            <CheckCircleIcon class="w-4 h-4 text-emerald-600" />
            <span>No active threshold alerts or anomalies detected on this server.</span>
        </div>

        <div v-else class="divide-y divide-slate-100 text-xs">
            <div 
                v-for="alert in alerts" 
                :key="alert.id"
                class="py-2.5 flex items-start justify-between gap-3"
            >
                <div class="flex items-start gap-2.5">
                    <div 
                        :class="[
                            'w-7 h-7 rounded-md flex items-center justify-center flex-shrink-0 mt-0.5 border',
                            alert.severity === 'critical' ? 'bg-rose-50 text-rose-600 border-rose-100' : 'bg-amber-50 text-amber-600 border-amber-100'
                        ]"
                    >
                        <ExclamationTriangleIcon class="w-3.5 h-3.5" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-slate-900 font-mono">{{ alert.alert_type }}</span>
                            <span 
                                :class="[
                                    'px-1.5 py-0.2 rounded text-[10px] font-bold uppercase border',
                                    alert.state === 'recovered' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : alert.severity === 'critical' ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-amber-50 text-amber-700 border-amber-200'
                                ]"
                            >
                                {{ alert.state }}
                            </span>
                        </div>
                        <p class="text-slate-500 text-[11px] mt-0.5">
                            Target: <span class="font-mono text-slate-700">{{ alert.resource_identity }}</span> • Current: <strong class="text-slate-900">{{ alert.current_value }}</strong> (Threshold: {{ alert.threshold_value }})
                        </p>
                    </div>
                </div>
                <div class="text-[11px] text-slate-400 font-mono text-right">
                    {{ formatDate(alert.started_at) }}
                </div>
            </div>
        </div>
    </div>
</template>
