<script setup>
import { ref, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import { 
    ClockIcon, 
    ShieldCheckIcon,
    ArrowDownTrayIcon,
    TrashIcon, 
    CheckCircleIcon,
    XCircleIcon,
    InformationCircleIcon,
    MagnifyingGlassIcon,
    GlobeAltIcon,
    ComputerDesktopIcon,
    CodeBracketIcon,
    XMarkIcon,
    EyeIcon,
    SparklesIcon,
    KeyIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    logs: {
        type: Array,
        default: () => []
    },
    loginHistory: {
        type: Array,
        default: () => []
    },
    stats: {
        type: Object,
        default: () => ({
            total_events: 0,
            total_logins: 0,
            client_ip: '127.0.0.1',
            username: 'somitysoft',
            audit_engine: 'Immutable System Event Bus (ISO 27001 compliant)'
        })
    }
})

// Active Tab
const activeTab = ref('events') // 'events' | 'logins' | 'guide'

// Search & Filter
const searchQuery = ref('')
const selectedCategory = ref('ALL') // 'ALL' | 'AUTH' | 'DNS' | 'SECURITY' | 'FILES'

const filteredLogs = computed(() => {
    let list = props.logs
    if (selectedCategory.value !== 'ALL') {
        list = list.filter(l => l.action.toUpperCase().includes(selectedCategory.value))
    }
    if (searchQuery.value.trim()) {
        const q = searchQuery.value.toLowerCase().trim()
        list = list.filter(l => 
            l.action.toLowerCase().includes(q) ||
            l.description.toLowerCase().includes(q) ||
            l.ip_address.toLowerCase().includes(q)
        )
    }
    return list
})

// Modals
const showClearModal = ref(false)
const showPayloadModal = ref(false)
const viewingLog = ref(null)

const openPayloadModal = (log) => {
    viewingLog.value = log
    showPayloadModal.value = true
}

const executeClearLogs = () => {
    router.post(route('advanced.activity-log.clear'), {}, {
        preserveScroll: true,
        onSuccess: () => {
            showClearModal.value = false
        }
    })
}

// Action badge styling
const getActionBadge = (action) => {
    const act = action.toUpperCase()
    if (act.includes('LOGIN') || act.includes('AUTH')) {
        return { label: 'AUTH', class: 'bg-indigo-50 text-indigo-700 border-indigo-200' }
    }
    if (act.includes('SECURITY') || act.includes('FIREWALL') || act.includes('BLOCK')) {
        return { label: 'SECURITY', class: 'bg-rose-50 text-rose-700 border-rose-200' }
    }
    if (act.includes('DNS') || act.includes('DOMAIN')) {
        return { label: 'DNS', class: 'bg-blue-50 text-blue-700 border-blue-200' }
    }
    if (act.includes('FILE') || act.includes('PERMISSION')) {
        return { label: 'FILES', class: 'bg-amber-50 text-amber-700 border-amber-200' }
    }
    return { label: 'SYSTEM', class: 'bg-slate-100 text-slate-700 border-slate-200' }
}
</script>

<template>
    <Head title="Account Activity & Security Audit Log - DeepTouch Host" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto space-y-4">
            
            <!-- 1. Page Header with Breadcrumbs & Actions -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Advanced Tools', href: '#' },
                    { label: 'Account Activity & Security Audit Log' }
                ]"
                :show-refresh="true"
                @refreshed="router.reload({ preserveScroll: true })"
            >
                <template #actions>
                    <a 
                        :href="route('advanced.activity-log.export')"
                        target="_blank"
                        class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 rounded-[3px] text-xs font-bold flex items-center gap-1.5 transition cursor-pointer shadow-2xs"
                    >
                        <ArrowDownTrayIcon class="w-3.5 h-3.5 text-blue-600" />
                        <span>Export CSV</span>
                    </a>

                    <button 
                        @click="showClearModal = true"
                        class="px-3 py-1.5 bg-slate-100 hover:bg-rose-50 text-rose-700 border border-slate-200 hover:border-rose-200 rounded-[3px] text-xs font-bold flex items-center gap-1.5 transition cursor-pointer shadow-2xs"
                    >
                        <TrashIcon class="w-3.5 h-3.5" />
                        <span>Clear Log</span>
                    </button>
                </template>
            </PageHeader>

            <!-- 2. Top 3 KPI / Audit Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                
                <!-- Card 1: Total Events -->
                <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-2xs space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <ClockIcon class="w-4 h-4 text-indigo-600" />
                            <span>Audit Events Recorded</span>
                        </span>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-700 border border-indigo-200 font-mono">
                            IMMUTABLE
                        </span>
                    </div>

                    <div>
                        <div class="flex items-baseline gap-2">
                            <span class="text-2xl font-black text-slate-900 font-mono">{{ stats.total_events }}</span>
                            <span class="text-xs text-slate-400 font-bold font-mono">Operations Logged</span>
                        </div>
                        <p class="text-[11px] text-slate-500 mt-1">
                            Every administrative, file, database, and authentication change is recorded chronologically.
                        </p>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1 border-t border-slate-100 font-mono">
                        <span>Audited Account:</span>
                        <strong class="text-slate-800">{{ stats.username }}</strong>
                    </div>
                </div>

                <!-- Card 2: Login History -->
                <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-2xs space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <KeyIcon class="w-4 h-4 text-emerald-600" />
                            <span>Account Logins & Sessions</span>
                        </span>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200 font-mono">
                            MONITORED
                        </span>
                    </div>

                    <div>
                        <div class="flex items-baseline gap-2">
                            <span class="text-2xl font-black text-slate-900 font-mono">{{ stats.total_logins }}</span>
                            <span class="text-xs text-slate-400 font-bold font-mono">Login Sessions</span>
                        </div>
                        <p class="text-[11px] text-slate-500 mt-1">
                            IP addresses, device user agents, and geolocation signatures are tracked for anomaly detection.
                        </p>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1 border-t border-slate-100 font-mono">
                        <span>Security Level:</span>
                        <strong class="text-emerald-700">Strict Audit Active</strong>
                    </div>
                </div>

                <!-- Card 3: Client IP & Compliance Engine -->
                <div class="bg-gradient-to-br from-indigo-50/70 to-blue-50/50 border border-indigo-100 rounded-lg p-4 shadow-2xs flex flex-col justify-between space-y-2.5">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-indigo-900 flex items-center gap-1.5">
                                <GlobeAltIcon class="w-4 h-4 text-indigo-600" />
                                <span>Current Client IP</span>
                            </span>
                            <span class="text-[10px] font-bold px-1.5 py-0.5 bg-indigo-100 text-indigo-800 rounded font-mono">
                                ONLINE
                            </span>
                        </div>

                        <div class="flex items-baseline gap-2 mt-1">
                            <span class="text-lg font-black text-slate-900 font-mono">{{ stats.client_ip }}</span>
                        </div>
                    </div>

                    <div class="pt-2 border-t border-indigo-100 flex items-center justify-between text-[11px] text-indigo-900 font-mono">
                        <span>Engine:</span>
                        <span class="font-bold text-slate-800 truncate max-w-[170px]">ISO 27001 Event Bus</span>
                    </div>
                </div>

            </div>

            <!-- 3. Navigation Tabs -->
            <div class="border-b border-slate-200 flex items-center gap-4 text-xs font-bold">
                <button 
                    @click="activeTab = 'events'"
                    class="pb-2.5 border-b-2 transition cursor-pointer flex items-center gap-1.5"
                    :class="activeTab === 'events' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-800'"
                >
                    <ClockIcon class="w-4 h-4" />
                    <span>Audit Events Trail ({{ logs.length }})</span>
                </button>

                <button 
                    @click="activeTab = 'logins'"
                    class="pb-2.5 border-b-2 transition cursor-pointer flex items-center gap-1.5"
                    :class="activeTab === 'logins' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-800'"
                >
                    <KeyIcon class="w-4 h-4" />
                    <span>Login History & Sessions ({{ loginHistory.length }})</span>
                </button>

                <button 
                    @click="activeTab = 'guide'"
                    class="pb-2.5 border-b-2 transition cursor-pointer flex items-center gap-1.5"
                    :class="activeTab === 'guide' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-800'"
                >
                    <InformationCircleIcon class="w-4 h-4" />
                    <span>Security & Audit Guide</span>
                </button>
            </div>

            <!-- 4. TAB 1: Audit Events Trail Table -->
            <div v-if="activeTab === 'events'" class="bg-white border border-slate-200 rounded-lg shadow-2xs overflow-hidden">
                
                <div class="px-4 py-3 bg-slate-50/80 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    
                    <!-- Category Chips -->
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <button 
                            v-for="cat in ['ALL', 'AUTH', 'DNS', 'SECURITY', 'FILES']" 
                            :key="cat"
                            @click="selectedCategory = cat"
                            class="px-2 py-0.5 rounded-[3px] text-xs font-bold transition cursor-pointer font-mono"
                            :class="selectedCategory === cat ? 'bg-blue-600 text-white shadow-2xs' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-100'"
                        >
                            {{ cat }}
                        </button>
                    </div>

                    <!-- Search -->
                    <div class="relative w-full sm:w-64">
                        <MagnifyingGlassIcon class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2" />
                        <input 
                            v-model="searchQuery"
                            type="text" 
                            placeholder="Filter audit events..." 
                            class="w-full pl-8 pr-2.5 py-1.5 bg-white border border-slate-300 rounded-[3px] text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-blue-500 font-medium shadow-2xs"
                        />
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="filteredLogs.length === 0" class="p-10 text-center text-slate-500 text-xs">
                    <ClockIcon class="w-10 h-10 text-slate-300 mx-auto mb-2" />
                    <h4 class="text-xs font-bold text-slate-900 mb-1">
                        {{ searchQuery ? 'No audit events match your filter' : 'No Activity Logs Recorded Yet' }}
                    </h4>
                </div>

                <!-- Table Content -->
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-700">
                        <thead class="bg-slate-50/90 border-b border-slate-200 text-slate-500 uppercase font-bold tracking-wider text-[10.5px] select-none">
                            <tr>
                                <th class="py-2.5 px-4 w-40">Timestamp</th>
                                <th class="py-2.5 px-4 w-28">Category</th>
                                <th class="py-2.5 px-4">Event Description</th>
                                <th class="py-2.5 px-4 w-36">IP Address</th>
                                <th class="py-2.5 px-4 w-48">Device / Client</th>
                                <th class="py-2.5 px-4 w-20 text-right">Details</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium">
                            <tr v-for="log in filteredLogs" :key="log.id" class="hover:bg-slate-50/80 transition-colors">
                                
                                <td class="py-3 px-4 font-mono text-[11px] text-slate-600">
                                    {{ log.created_at }}
                                </td>

                                <td class="py-3 px-4 font-mono">
                                    <span 
                                        class="px-2 py-0.5 rounded text-[10px] font-bold border uppercase"
                                        :class="getActionBadge(log.action).class"
                                    >
                                        {{ getActionBadge(log.action).label }}
                                    </span>
                                </td>

                                <td class="py-3 px-4 text-slate-900 font-medium text-[11.5px]">
                                    {{ log.description }}
                                </td>

                                <td class="py-3 px-4 font-mono text-[11px] text-slate-700">
                                    {{ log.ip_address }}
                                </td>

                                <td class="py-3 px-4 font-mono text-[10.5px] text-slate-400 truncate max-w-xs" :title="log.user_agent">
                                    {{ log.user_agent || 'Unknown Client' }}
                                </td>

                                <td class="py-3 px-4 text-right">
                                    <button 
                                        @click="openPayloadModal(log)"
                                        title="View Payload / Parameters"
                                        class="p-1.5 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-[3px] shadow-2xs transition cursor-pointer"
                                    >
                                        <EyeIcon class="w-3.5 h-3.5 text-blue-600" />
                                    </button>
                                </td>

                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="px-4 py-2.5 bg-slate-50 border-t border-slate-200 text-[11px] text-slate-500 flex items-center justify-between">
                    <span>Audit logs are preserved for security compliance and session validation.</span>
                    <span class="font-mono">Total Recorded: {{ logs.length }} events</span>
                </div>

            </div>

            <!-- 5. TAB 2: Login History Table -->
            <div v-else-if="activeTab === 'logins'" class="bg-white border border-slate-200 rounded-lg shadow-2xs overflow-hidden">
                
                <div class="px-4 py-3 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <KeyIcon class="w-4 h-4 text-emerald-600" />
                        <span class="text-xs font-bold text-slate-900">Control Panel Login History</span>
                        <span class="text-[11px] text-slate-400 font-mono">({{ loginHistory.length }})</span>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="loginHistory.length === 0" class="p-10 text-center text-slate-500 text-xs">
                    <KeyIcon class="w-10 h-10 text-slate-300 mx-auto mb-2" />
                    <h4 class="text-xs font-bold text-slate-900 mb-1">No Login Sessions Recorded</h4>
                </div>

                <!-- Table Content -->
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-700">
                        <thead class="bg-slate-50/90 border-b border-slate-200 text-slate-500 uppercase font-bold tracking-wider text-[10.5px]">
                            <tr>
                                <th class="py-2.5 px-4 w-44">Login Timestamp</th>
                                <th class="py-2.5 px-4 w-28">Status</th>
                                <th class="py-2.5 px-4 w-36">IP Address</th>
                                <th class="py-2.5 px-4 w-44">Location</th>
                                <th class="py-2.5 px-4">Device & Browser</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium">
                            <tr v-for="lh in loginHistory" :key="lh.id" class="hover:bg-slate-50/80 transition-colors">
                                
                                <td class="py-3 px-4 font-mono text-[11px] text-slate-600">
                                    {{ lh.login_at }}
                                </td>

                                <td class="py-3 px-4">
                                    <span 
                                        class="px-2 py-0.5 rounded text-[10px] font-bold border uppercase font-mono"
                                        :class="lh.status === 'success' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'"
                                    >
                                        {{ lh.status }}
                                    </span>
                                </td>

                                <td class="py-3 px-4 font-mono font-bold text-slate-900 text-[11.5px]">
                                    {{ lh.ip_address }}
                                </td>

                                <td class="py-3 px-4 text-slate-700 text-[11.5px]">
                                    {{ lh.location }}
                                </td>

                                <td class="py-3 px-4 font-mono text-[11px] text-slate-600">
                                    {{ lh.browser }} • {{ lh.os }} ({{ lh.device }})
                                </td>

                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>

            <!-- 6. TAB 3: Security & Audit Guide -->
            <div v-else-if="activeTab === 'guide'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                
                <div class="bg-white border border-slate-200 rounded-lg p-5 shadow-2xs space-y-3">
                    <h4 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                        <ShieldCheckIcon class="w-4 h-4 text-emerald-600" />
                        <span>Security Auditing Best Practices</span>
                    </h4>
                    <ul class="text-xs text-slate-600 space-y-2 leading-relaxed">
                        <li>
                            <strong>Regular Review:</strong> Review your login history weekly to identify unfamiliar IP addresses or unauthorized session logins.
                        </li>
                        <li>
                            <strong>Immediate Password Rotation:</strong> If you spot a login from an unknown country or IP, immediately change your account password and revoke active sessions.
                        </li>
                        <li>
                            <strong>Exporting Compliance Records:</strong> Use the <strong>Export CSV</strong> button anytime for corporate IT audits.
                        </li>
                    </ul>
                </div>

                <div class="bg-white border border-slate-200 rounded-lg p-5 shadow-2xs space-y-3">
                    <h4 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                        <SparklesIcon class="w-4 h-4 text-indigo-600" />
                        <span>Monitored Event Categories</span>
                    </h4>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        The event bus logs authentication events, DNS record updates, Cron job creation, SSL certificate renewal, file permission fixes, and database credential changes.
                    </p>
                </div>

            </div>

        </div>

        <!-- ========================================================= -->
        <!-- MODALS                                                    -->
        <!-- ========================================================= -->
        <Teleport to="body">
            
            <!-- 1. Payload Inspector Modal -->
            <div v-if="showPayloadModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4 z-50">
                <div class="bg-white rounded-lg max-w-xl w-full p-5 shadow-2xl border border-slate-200 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-2">
                            <CodeBracketIcon class="w-5 h-5 text-blue-600" />
                            <h3 class="font-bold text-slate-900 text-sm">
                                Event Payload & Parameter Inspector
                            </h3>
                        </div>
                        <button @click="showPayloadModal = false" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                            <XMarkIcon class="w-4 h-4" />
                        </button>
                    </div>

                    <div class="space-y-3 text-xs">
                        <div class="bg-slate-50 p-2.5 rounded border border-slate-200/70 font-mono">
                            <span class="font-bold text-slate-900">{{ viewingLog?.action }}:</span>
                            <span class="text-slate-600 ml-1.5">{{ viewingLog?.description }}</span>
                        </div>

                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Modified Values / Parameters (JSON)</label>
                            <pre class="bg-slate-900 text-slate-100 p-3 rounded font-mono text-[11px] overflow-x-auto leading-relaxed max-h-56 whitespace-pre-wrap">{{ JSON.stringify(viewingLog?.new_values || viewingLog?.old_values || { ip: viewingLog?.ip_address, timestamp: viewingLog?.created_at }, null, 2) }}</pre>
                        </div>
                    </div>

                    <div class="flex justify-end pt-2 border-t border-slate-100">
                        <button 
                            type="button" 
                            @click="showPayloadModal = false" 
                            class="px-4 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-[3px] text-xs font-bold cursor-pointer"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>

            <!-- 2. Clear Logs Modal -->
            <div v-if="showClearModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4 z-50">
                <div class="bg-white rounded-lg max-w-md w-full p-5 shadow-2xl border border-slate-200 space-y-4">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-lg bg-rose-50 border border-rose-100 text-rose-600 flex items-center justify-center shrink-0">
                            <TrashIcon class="w-5 h-5 stroke-[1.75]" />
                        </div>
                        <div class="flex-1">
                            <h3 class="text-sm font-black text-slate-900">Clear Activity Audit Logs?</h3>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Are you sure you want to clear your recorded activity logs? This action will permanently remove historical audit events from your view.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                        <button 
                            @click="showClearModal = false" 
                            type="button" 
                            class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-[3px] text-xs font-bold transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button 
                            @click="executeClearLogs" 
                            type="button"
                            class="px-4 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-[3px] text-xs font-bold shadow-2xs transition cursor-pointer flex items-center gap-1.5"
                        >
                            <TrashIcon class="w-3.5 h-3.5" />
                            <span>Clear Activity Log</span>
                        </button>
                    </div>
                </div>
            </div>

        </Teleport>

    </AuthenticatedLayout>
</template>
