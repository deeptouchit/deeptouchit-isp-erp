<script setup>
import { ref, computed } from 'vue'
import { Head, useForm, router, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import { 
    ShieldExclamationIcon, 
    ShieldCheckIcon,
    PlusIcon, 
    NoSymbolIcon,
    TrashIcon, 
    CheckCircleIcon,
    XCircleIcon,
    ClipboardDocumentIcon,
    CheckIcon,
    SparklesIcon,
    ArrowPathIcon,
    XMarkIcon,
    ExclamationTriangleIcon,
    InformationCircleIcon,
    MagnifyingGlassIcon,
    GlobeAltIcon,
    ServerIcon,
    CommandLineIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    blockedIps: {
        type: Array,
        default: () => []
    },
    allowlistedIps: {
        type: Array,
        default: () => []
    },
    stats: {
        type: Object,
        default: () => ({
            total_blocked: 0,
            total_allowed: 0,
            client_ip: '103.59.177.100',
            is_my_ip_allowed: false,
            is_my_ip_blocked: false,
            firewall_engine: 'Linux Netfilter / iptables & Nginx Deny'
        })
    }
})

// Active Tab
const activeTab = ref('blocked') // 'blocked' | 'allowed' | 'guide'

// Search & Filter
const searchQuery = ref('')
const filteredBlocked = computed(() => {
    if (!searchQuery.value.trim()) return props.blockedIps
    const q = searchQuery.value.toLowerCase().trim()
    return props.blockedIps.filter(b => 
        b.ip_address.toLowerCase().includes(q) ||
        b.reason.toLowerCase().includes(q) ||
        b.type.toLowerCase().includes(q)
    )
})

const filteredAllowed = computed(() => {
    if (!searchQuery.value.trim()) return props.allowlistedIps
    const q = searchQuery.value.toLowerCase().trim()
    return props.allowlistedIps.filter(a => 
        a.ip_address.toLowerCase().includes(q) ||
        a.label.toLowerCase().includes(q) ||
        a.scope.toLowerCase().includes(q)
    )
})

// Modals
const showBlockModal = ref(false)
const showAllowModal = ref(false)
const showDeleteBlockModal = ref(false)
const showDeleteAllowModal = ref(false)

const deletingBlock = ref(null)
const deletingAllow = ref(null)

// Copy helper
const copiedField = ref(null)
const copyToClipboard = (text, fieldName) => {
    navigator.clipboard.writeText(text)
    copiedField.value = fieldName
    setTimeout(() => {
        copiedField.value = null
    }, 2000)
}

// Forms
const blockForm = useForm({
    ip_address: '',
    reason: 'Malicious traffic / brute force attempt',
    duration_days: 0, // 0 = permanent
})

const allowForm = useForm({
    ip_address: '',
    label: 'Office / Developer IP',
    scope: 'all_services',
    duration_days: 0, // 0 = permanent
})

// Action Handlers
const openBlockModal = (prefillIp = '') => {
    blockForm.reset({
        ip_address: prefillIp || '',
        reason: 'Malicious traffic / brute force attempt',
        duration_days: 0,
    })
    showBlockModal.value = true
}

const submitBlock = () => {
    blockForm.post(route('advanced.ip-manager.block'), {
        preserveScroll: true,
        onSuccess: () => {
            showBlockModal.value = false
            blockForm.reset()
        }
    })
}

const openAllowModal = (prefillIp = '') => {
    allowForm.reset({
        ip_address: prefillIp || props.stats.client_ip || '',
        label: prefillIp === props.stats.client_ip ? 'My Current IP' : 'Office / Developer IP',
        scope: 'all_services',
        duration_days: 0,
    })
    showAllowModal.value = true
}

const submitAllow = () => {
    allowForm.post(route('advanced.ip-manager.allow'), {
        preserveScroll: true,
        onSuccess: () => {
            showAllowModal.value = false
            allowForm.reset()
        }
    })
}

const confirmDeleteBlock = (b) => {
    deletingBlock.value = b
    showDeleteBlockModal.value = true
}

const executeDeleteBlock = () => {
    if (!deletingBlock.value) return
    router.delete(route('advanced.ip-manager.unblock', deletingBlock.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteBlockModal.value = false
            deletingBlock.value = null
        }
    })
}

const confirmDeleteAllow = (a) => {
    deletingAllow.value = a
    showDeleteAllowModal.value = true
}

const executeDeleteAllow = () => {
    if (!deletingAllow.value) return
    router.delete(route('advanced.ip-manager.unallow', deletingAllow.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteAllowModal.value = false
            deletingAllow.value = null
        }
    })
}

const allowMyIpNow = () => {
    router.post(route('advanced.ip-manager.allow-my-ip'), {}, {
        preserveScroll: true,
    })
}
</script>

<template>
    <Head title="IP Access & Block Manager - DeepTouch Host" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto space-y-4">
            
            <!-- 1. Page Header with Breadcrumbs & Actions -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Advanced Tools', href: '#' },
                    { label: 'IP Access & Block Manager' }
                ]"
                :show-refresh="true"
                @refreshed="router.reload({ preserveScroll: true })"
            >
                <template #actions>
                    <button 
                        v-if="!stats.is_my_ip_allowed"
                        @click="allowMyIpNow"
                        class="px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 rounded-[3px] text-xs font-bold flex items-center gap-1.5 transition cursor-pointer shadow-2xs"
                    >
                        <CheckCircleIcon class="w-3.5 h-3.5 text-emerald-600" />
                        <span>Allowlist My IP ({{ stats.client_ip }})</span>
                    </button>

                    <button 
                        @click="openAllowModal()"
                        class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <ShieldCheckIcon class="w-3.5 h-3.5 stroke-[2.5]" />
                        <span>Allow an IP Address</span>
                    </button>

                    <button 
                        @click="openBlockModal()"
                        class="px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <NoSymbolIcon class="w-3.5 h-3.5 stroke-[2.5]" />
                        <span>Block an IP Address</span>
                    </button>
                </template>
            </PageHeader>

            <!-- 2. Top 3 KPI / Security Threat Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                
                <!-- Card 1: Blocked IP Addresses -->
                <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-2xs space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <NoSymbolIcon class="w-4 h-4 text-rose-600" />
                            <span>Blocked IP Addresses</span>
                        </span>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-rose-50 text-rose-700 border border-rose-200 font-mono">
                            BLACKLIST
                        </span>
                    </div>

                    <div>
                        <div class="flex items-baseline gap-2">
                            <span class="text-2xl font-black text-slate-900 font-mono">{{ stats.total_blocked }}</span>
                            <span class="text-xs text-slate-400 font-bold font-mono">Blocked Targets</span>
                        </div>
                        <p class="text-[11px] text-slate-500 mt-1">
                            Traffic from blocked IPs is dropped at web server layer with <strong>403 Forbidden</strong>.
                        </p>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1 border-t border-slate-100 font-mono">
                        <span>Status:</span>
                        <strong class="text-rose-600">Active Shielding</strong>
                    </div>
                </div>

                <!-- Card 2: Allowlisted Trusted IPs -->
                <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-2xs space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <ShieldCheckIcon class="w-4 h-4 text-emerald-600" />
                            <span>Allowlisted Trusted IPs</span>
                        </span>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200 font-mono">
                            WHITELIST
                        </span>
                    </div>

                    <div>
                        <div class="flex items-baseline gap-2">
                            <span class="text-2xl font-black text-slate-900 font-mono">{{ stats.total_allowed }}</span>
                            <span class="text-xs text-slate-400 font-bold font-mono">Trusted Addresses</span>
                        </div>
                        <p class="text-[11px] text-slate-500 mt-1">
                            Allowlisted IPs bypass rate limiting, captcha verification, and security challenges.
                        </p>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1 border-t border-slate-100 font-mono">
                        <span>Privilege:</span>
                        <strong class="text-emerald-700">Unrestricted Access</strong>
                    </div>
                </div>

                <!-- Card 3: Client Current IP & Firewall Coordinates -->
                <div class="bg-gradient-to-br from-indigo-50/70 to-blue-50/50 border border-indigo-100 rounded-lg p-4 shadow-2xs flex flex-col justify-between space-y-2.5">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-indigo-900 flex items-center gap-1.5">
                                <GlobeAltIcon class="w-4 h-4 text-indigo-600" />
                                <span>Your Current Public IP</span>
                            </span>
                            <span 
                                class="text-[10px] font-bold px-1.5 py-0.5 rounded font-mono uppercase"
                                :class="stats.is_my_ip_allowed ? 'bg-emerald-100 text-emerald-800' : (stats.is_my_ip_blocked ? 'bg-rose-100 text-rose-800' : 'bg-slate-200 text-slate-700')"
                            >
                                {{ stats.is_my_ip_allowed ? 'TRUSTED' : (stats.is_my_ip_blocked ? 'BLOCKED' : 'STANDARD') }}
                            </span>
                        </div>

                        <div class="flex items-center gap-2 mt-2">
                            <span class="text-lg font-black text-slate-900 font-mono">{{ stats.client_ip }}</span>
                            <button 
                                @click="copyToClipboard(stats.client_ip, 'my_ip')"
                                class="text-slate-400 hover:text-blue-600 cursor-pointer"
                                title="Copy My IP"
                            >
                                <CheckIcon v-if="copiedField === 'my_ip'" class="w-4 h-4 text-emerald-600" />
                                <ClipboardDocumentIcon v-else class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    <div class="pt-2 border-t border-indigo-100 flex items-center justify-between text-[11px] text-indigo-900 font-mono">
                        <span>Engine:</span>
                        <span class="font-bold truncate max-w-xs">{{ stats.firewall_engine }}</span>
                    </div>
                </div>

            </div>

            <!-- 3. Navigation Tabs -->
            <div class="border-b border-slate-200 flex items-center gap-4 text-xs font-bold">
                <button 
                    @click="activeTab = 'blocked'"
                    class="pb-2.5 border-b-2 transition cursor-pointer flex items-center gap-1.5"
                    :class="activeTab === 'blocked' ? 'border-rose-600 text-rose-600' : 'border-transparent text-slate-500 hover:text-slate-800'"
                >
                    <NoSymbolIcon class="w-4 h-4" />
                    <span>Blocked IP Addresses ({{ blockedIps.length }})</span>
                </button>

                <button 
                    @click="activeTab = 'allowed'"
                    class="pb-2.5 border-b-2 transition cursor-pointer flex items-center gap-1.5"
                    :class="activeTab === 'allowed' ? 'border-emerald-600 text-emerald-600' : 'border-transparent text-slate-500 hover:text-slate-800'"
                >
                    <ShieldCheckIcon class="w-4 h-4" />
                    <span>Allowlisted Trusted IPs ({{ allowlistedIps.length }})</span>
                </button>

                <button 
                    @click="activeTab = 'guide'"
                    class="pb-2.5 border-b-2 transition cursor-pointer flex items-center gap-1.5"
                    :class="activeTab === 'guide' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-800'"
                >
                    <InformationCircleIcon class="w-4 h-4" />
                    <span>CIDR & Security Best Practices</span>
                </button>
            </div>

            <!-- 4. TAB 1: Blocked IP Addresses Table -->
            <div v-if="activeTab === 'blocked'" class="bg-white border border-slate-200 rounded-lg shadow-2xs overflow-hidden">
                
                <div class="px-4 py-3 bg-slate-50/80 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <NoSymbolIcon class="w-4 h-4 text-rose-600" />
                        <span class="text-xs font-bold text-slate-900">Active IP Block Rules (Blacklist)</span>
                        <span class="text-[11px] text-slate-400 font-mono">({{ filteredBlocked.length }})</span>
                    </div>

                    <div class="relative w-full sm:w-64">
                        <MagnifyingGlassIcon class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2" />
                        <input 
                            v-model="searchQuery"
                            type="text" 
                            placeholder="Filter blocked IPs or reason..." 
                            class="w-full pl-8 pr-2.5 py-1.5 bg-white border border-slate-300 rounded-[3px] text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-blue-500 font-medium shadow-2xs"
                        />
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="filteredBlocked.length === 0" class="p-12 text-center text-slate-500 text-xs">
                    <ShieldCheckIcon class="w-12 h-12 text-emerald-400 mx-auto mb-2" />
                    <h4 class="text-sm font-bold text-slate-900 mb-1">
                        {{ searchQuery ? 'No blocked IPs match your filter' : 'No IP Addresses Currently Blocked' }}
                    </h4>
                    <p class="text-[11px] text-slate-400 max-w-sm mx-auto mb-4">
                        {{ searchQuery ? 'Try clearing your search query.' : 'Your firewall has no manual IP blacklists active. Block malicious scrapers or attack sources anytime.' }}
                    </p>
                    <button 
                        @click="openBlockModal()"
                        class="px-3.5 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-[3px] text-xs font-bold shadow-2xs transition cursor-pointer"
                    >
                        Block an IP Address
                    </button>
                </div>

                <!-- Table Content -->
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-700">
                        <thead class="bg-slate-50/90 border-b border-slate-200 text-slate-500 uppercase font-bold tracking-wider text-[10.5px] select-none">
                            <tr>
                                <th class="py-2.5 px-4 w-48">Blocked IP / CIDR</th>
                                <th class="py-2.5 px-4">Reason / Notes</th>
                                <th class="py-2.5 px-4 w-28">Type</th>
                                <th class="py-2.5 px-4 w-36">Duration</th>
                                <th class="py-2.5 px-4 w-36">Blocked Date</th>
                                <th class="py-2.5 px-4 w-24 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium">
                            <tr v-for="b in filteredBlocked" :key="b.id" class="hover:bg-slate-50/80 transition-colors">
                                
                                <!-- IP Address -->
                                <td class="py-3 px-4 font-mono font-bold text-slate-900 text-[11.5px]">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-rose-700 bg-rose-50 px-1.5 py-0.5 rounded border border-rose-200">{{ b.ip_address }}</span>
                                        <button 
                                            @click="copyToClipboard(b.ip_address, 'blk_' + b.id)"
                                            class="text-slate-400 hover:text-blue-600 cursor-pointer"
                                            title="Copy IP"
                                        >
                                            <CheckIcon v-if="copiedField === 'blk_' + b.id" class="w-3.5 h-3.5 text-emerald-600" />
                                            <ClipboardDocumentIcon v-else class="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                </td>

                                <!-- Reason -->
                                <td class="py-3 px-4 text-slate-700 text-[11.5px]">
                                    {{ b.reason }}
                                </td>

                                <!-- Type -->
                                <td class="py-3 px-4 font-mono">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold border uppercase bg-slate-100 text-slate-700 border-slate-200">
                                        {{ b.type }}
                                    </span>
                                </td>

                                <!-- Duration / Remaining -->
                                <td class="py-3 px-4 font-mono text-[11px] text-slate-600">
                                    <span v-if="b.time_remaining === 'Permanent'" class="font-bold text-slate-800">Permanent</span>
                                    <span v-else class="text-amber-700">{{ b.time_remaining }}</span>
                                </td>

                                <!-- Blocked Date -->
                                <td class="py-3 px-4 text-slate-500 font-mono text-[11px]">
                                    {{ b.created_at }}
                                </td>

                                <!-- Action -->
                                <td class="py-3 px-4 text-right">
                                    <button 
                                        @click="confirmDeleteBlock(b)"
                                        title="Unblock IP"
                                        class="px-2 py-1 bg-white hover:bg-rose-50 text-rose-700 border border-slate-200 rounded-[3px] text-[11px] font-bold shadow-2xs transition cursor-pointer"
                                    >
                                        Unblock
                                    </button>
                                </td>

                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="px-4 py-2.5 bg-slate-50 border-t border-slate-200 text-[11px] text-slate-500 flex items-center justify-between">
                    <span>Synchronized with Nginx deny rules & iptables netfilter.</span>
                    <span class="font-mono">Total Blocked: {{ blockedIps.length }} rules</span>
                </div>

            </div>

            <!-- 5. TAB 2: Allowlisted IP Addresses Table -->
            <div v-else-if="activeTab === 'allowed'" class="bg-white border border-slate-200 rounded-lg shadow-2xs overflow-hidden">
                
                <div class="px-4 py-3 bg-slate-50/80 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <ShieldCheckIcon class="w-4 h-4 text-emerald-600" />
                        <span class="text-xs font-bold text-slate-900">Allowlisted Trusted IPs (Whitelist)</span>
                        <span class="text-[11px] text-slate-400 font-mono">({{ filteredAllowed.length }})</span>
                    </div>

                    <div class="relative w-full sm:w-64">
                        <MagnifyingGlassIcon class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2" />
                        <input 
                            v-model="searchQuery"
                            type="text" 
                            placeholder="Filter allowlisted IPs or label..." 
                            class="w-full pl-8 pr-2.5 py-1.5 bg-white border border-slate-300 rounded-[3px] text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-blue-500 font-medium shadow-2xs"
                        />
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="filteredAllowed.length === 0" class="p-12 text-center text-slate-500 text-xs">
                    <ShieldExclamationIcon class="w-12 h-12 text-slate-300 mx-auto mb-2" />
                    <h4 class="text-sm font-bold text-slate-900 mb-1">
                        {{ searchQuery ? 'No allowlisted IPs match your filter' : 'No Allowlisted IPs Configured' }}
                    </h4>
                    <p class="text-[11px] text-slate-400 max-w-sm mx-auto mb-4">
                        {{ searchQuery ? 'Try clearing your search query.' : 'Allowlist your home office, developer machines, or corporate VPN to bypass rate limits.' }}
                    </p>
                    <div class="flex items-center justify-center gap-2">
                        <button 
                            @click="allowMyIpNow"
                            class="px-3.5 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 rounded-[3px] text-xs font-bold shadow-2xs transition cursor-pointer"
                        >
                            Allowlist My Current IP
                        </button>
                        <button 
                            @click="openAllowModal()"
                            class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-[3px] text-xs font-bold shadow-2xs transition cursor-pointer"
                        >
                            Allow an IP Address
                        </button>
                    </div>
                </div>

                <!-- Table Content -->
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-700">
                        <thead class="bg-slate-50/90 border-b border-slate-200 text-slate-500 uppercase font-bold tracking-wider text-[10.5px] select-none">
                            <tr>
                                <th class="py-2.5 px-4 w-48">Trusted IP / CIDR</th>
                                <th class="py-2.5 px-4">Label / Identifier</th>
                                <th class="py-2.5 px-4 w-32">Scope</th>
                                <th class="py-2.5 px-4 w-36">Duration</th>
                                <th class="py-2.5 px-4 w-36">Added Date</th>
                                <th class="py-2.5 px-4 w-24 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium">
                            <tr v-for="a in filteredAllowed" :key="a.id" class="hover:bg-slate-50/80 transition-colors">
                                
                                <!-- IP Address -->
                                <td class="py-3 px-4 font-mono font-bold text-slate-900 text-[11.5px]">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200">{{ a.ip_address }}</span>
                                        <button 
                                            @click="copyToClipboard(a.ip_address, 'alw_' + a.id)"
                                            class="text-slate-400 hover:text-blue-600 cursor-pointer"
                                            title="Copy IP"
                                        >
                                            <CheckIcon v-if="copiedField === 'alw_' + a.id" class="w-3.5 h-3.5 text-emerald-600" />
                                            <ClipboardDocumentIcon v-else class="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                </td>

                                <!-- Label -->
                                <td class="py-3 px-4 text-slate-800 font-bold text-[11.5px]">
                                    {{ a.label }}
                                </td>

                                <!-- Scope -->
                                <td class="py-3 px-4 font-mono">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold border uppercase bg-indigo-50 text-indigo-700 border-indigo-200">
                                        {{ a.scope }}
                                    </span>
                                </td>

                                <!-- Duration / Remaining -->
                                <td class="py-3 px-4 font-mono text-[11px] text-slate-600">
                                    <span v-if="a.time_remaining.includes('Permanent')" class="font-bold text-slate-800">Permanent</span>
                                    <span v-else class="text-amber-700">{{ a.time_remaining }}</span>
                                </td>

                                <!-- Added Date -->
                                <td class="py-3 px-4 text-slate-500 font-mono text-[11px]">
                                    {{ a.created_at }}
                                </td>

                                <!-- Action -->
                                <td class="py-3 px-4 text-right">
                                    <button 
                                        @click="confirmDeleteAllow(a)"
                                        title="Remove from Allowlist"
                                        class="px-2 py-1 bg-white hover:bg-rose-50 text-rose-700 border border-slate-200 rounded-[3px] text-[11px] font-bold shadow-2xs transition cursor-pointer"
                                    >
                                        Remove
                                    </button>
                                </td>

                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="px-4 py-2.5 bg-slate-50 border-t border-slate-200 text-[11px] text-slate-500 flex items-center justify-between">
                    <span>Privileged whitelist bypasses security challenge captchas.</span>
                    <span class="font-mono">Total Allowed: {{ allowlistedIps.length }} IPs</span>
                </div>

            </div>

            <!-- 6. TAB 3: CIDR Subnets & Security Guide -->
            <div v-else-if="activeTab === 'guide'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                
                <!-- CIDR Notation Reference -->
                <div class="bg-white border border-slate-200 rounded-lg p-5 shadow-2xs space-y-3">
                    <h3 class="font-bold text-slate-900 text-sm flex items-center gap-2">
                        <CommandLineIcon class="w-4 h-4 text-indigo-600" />
                        <span>CIDR Subnet Formats & Examples</span>
                    </h3>
                    <div class="space-y-2 text-xs">
                        <div class="p-2.5 bg-slate-50 rounded border border-slate-200/70 font-mono">
                            <span class="font-bold text-slate-900">192.168.1.50</span>
                            <p class="text-slate-500 text-[11px] font-sans mt-0.5">Single Host IP (/32 mask) - exact single address match.</p>
                        </div>
                        <div class="p-2.5 bg-slate-50 rounded border border-slate-200/70 font-mono">
                            <span class="font-bold text-slate-900">192.168.1.0/24</span>
                            <p class="text-slate-500 text-[11px] font-sans mt-0.5">Subnet Range - covers 256 addresses (192.168.1.0 through 192.168.1.255).</p>
                        </div>
                        <div class="p-2.5 bg-slate-50 rounded border border-slate-200/70 font-mono">
                            <span class="font-bold text-slate-900">10.0.0.0/16</span>
                            <p class="text-slate-500 text-[11px] font-sans mt-0.5">Class B Network - covers 65,536 addresses.</p>
                        </div>
                    </div>
                </div>

                <!-- Best Practices -->
                <div class="bg-white border border-slate-200 rounded-lg p-5 shadow-2xs space-y-3">
                    <h3 class="font-bold text-slate-900 text-sm flex items-center gap-2">
                        <ShieldCheckIcon class="w-4 h-4 text-emerald-600" />
                        <span>Firewall & Allowlist Best Practices</span>
                    </h3>
                    <ul class="text-xs text-slate-600 space-y-2 leading-relaxed">
                        <li class="flex items-start gap-2">
                            <CheckCircleIcon class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" />
                            <span><strong>Allowlist Developer Offices:</strong> Always allowlist your office and VPN IPs so you never get locked out during debugging or intense testing.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <CheckCircleIcon class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" />
                            <span><strong>Temporary Blocks for Scrapers:</strong> Block aggressive scraping bots for 7 or 30 days to re-evaluate if their traffic ceases.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <CheckCircleIcon class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" />
                            <span><strong>Cloudflare IP Pass-Through:</strong> The server automatically extracts original visitor IPs from <code>CF-Connecting-IP</code> and <code>X-Forwarded-For</code> headers.</span>
                        </li>
                    </ul>
                </div>

            </div>

        </div>

        <!-- ========================================================= -->
        <!-- MODALS                                                    -->
        <!-- ========================================================= -->
        <Teleport to="body">
            
            <!-- 1. Block IP Modal -->
            <div v-if="showBlockModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4 z-50">
                <div class="bg-white rounded-lg max-w-lg w-full p-5 shadow-2xl space-y-4 border border-slate-200">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-2">
                            <NoSymbolIcon class="w-5 h-5 text-rose-600" />
                            <h3 class="font-bold text-slate-900 text-sm">
                                Block an IP Address
                            </h3>
                        </div>
                        <button @click="showBlockModal = false" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                            <XMarkIcon class="w-4 h-4" />
                        </button>
                    </div>

                    <form @submit.prevent="submitBlock" class="space-y-3.5 text-xs">
                        
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">IP Address or CIDR Subnet Range</label>
                            <input 
                                v-model="blockForm.ip_address" 
                                type="text" 
                                required 
                                placeholder="e.g. 192.168.1.100 or 10.0.0.0/24" 
                                class="w-full bg-white border border-slate-300 rounded-[3px] px-2.5 py-2 text-xs font-mono font-bold text-slate-900 focus:ring-1 focus:ring-rose-600" 
                            />
                        </div>

                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Reason / Incident Description</label>
                            <input 
                                v-model="blockForm.reason" 
                                type="text" 
                                required 
                                placeholder="e.g. Malicious scraper, DDoS flood source, wp-login brute force" 
                                class="w-full bg-white border border-slate-300 rounded-[3px] px-2.5 py-2 text-xs font-bold text-slate-900 focus:ring-1 focus:ring-rose-600" 
                            />
                        </div>

                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Block Duration / Expiry</label>
                            <select 
                                v-model.number="blockForm.duration_days"
                                class="w-full bg-white border border-slate-300 rounded-[3px] px-2.5 py-2 text-xs font-bold text-slate-900 focus:ring-1 focus:ring-rose-600 cursor-pointer"
                            >
                                <option :value="0">Permanent (Until manually unblocked)</option>
                                <option :value="1">24 Hours (1 Day)</option>
                                <option :value="7">7 Days (1 Week)</option>
                                <option :value="30">30 Days (1 Month)</option>
                                <option :value="90">90 Days (3 Months)</option>
                            </select>
                        </div>

                        <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                            <button 
                                type="button" 
                                @click="showBlockModal = false" 
                                class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-[3px] text-xs font-bold cursor-pointer"
                            >
                                Cancel
                            </button>
                            <button 
                                type="submit" 
                                :disabled="blockForm.processing"
                                class="px-4 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-[3px] text-xs font-bold shadow-2xs transition cursor-pointer disabled:opacity-50 flex items-center gap-1"
                            >
                                <span>{{ blockForm.processing ? 'Blocking...' : 'Enforce Block Rule' }}</span>
                            </button>
                        </div>

                    </form>
                </div>
            </div>

            <!-- 2. Allow IP Modal -->
            <div v-if="showAllowModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4 z-50">
                <div class="bg-white rounded-lg max-w-lg w-full p-5 shadow-2xl space-y-4 border border-slate-200">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-2">
                            <ShieldCheckIcon class="w-5 h-5 text-emerald-600" />
                            <h3 class="font-bold text-slate-900 text-sm">
                                Allow an IP Address
                            </h3>
                        </div>
                        <button @click="showAllowModal = false" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                            <XMarkIcon class="w-4 h-4" />
                        </button>
                    </div>

                    <form @submit.prevent="submitAllow" class="space-y-3.5 text-xs">
                        
                        <div class="space-y-1">
                            <div class="flex items-center justify-between">
                                <label class="block font-bold text-slate-700">IP Address or CIDR Subnet Range</label>
                                <button 
                                    type="button"
                                    @click="allowForm.ip_address = stats.client_ip; allowForm.label = 'My Current IP'"
                                    class="text-[10.5px] text-emerald-600 font-bold hover:underline cursor-pointer"
                                >
                                    Use My IP ({{ stats.client_ip }})
                                </button>
                            </div>
                            <input 
                                v-model="allowForm.ip_address" 
                                type="text" 
                                required 
                                placeholder="e.g. 103.59.177.100" 
                                class="w-full bg-white border border-slate-300 rounded-[3px] px-2.5 py-2 text-xs font-mono font-bold text-slate-900 focus:ring-1 focus:ring-emerald-600" 
                            />
                        </div>

                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Friendly Label / Identifier</label>
                            <input 
                                v-model="allowForm.label" 
                                type="text" 
                                required 
                                placeholder="e.g. Developer Home, Head Office, Monitoring Service" 
                                class="w-full bg-white border border-slate-300 rounded-[3px] px-2.5 py-2 text-xs font-bold text-slate-900 focus:ring-1 focus:ring-emerald-600" 
                            />
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="space-y-1">
                                <label class="block font-bold text-slate-700">Access Scope</label>
                                <select 
                                    v-model="allowForm.scope"
                                    class="w-full bg-white border border-slate-300 rounded-[3px] px-2.5 py-2 text-xs font-bold text-slate-900 focus:ring-1 focus:ring-emerald-600 cursor-pointer"
                                >
                                    <option value="all_services">All Services (Full Bypass)</option>
                                    <option value="web_only">Web / HTTP(S) Only</option>
                                    <option value="ssh_only">SSH / SFTP Only</option>
                                    <option value="cpanel_only">Control Panel Only</option>
                                </select>
                            </div>

                            <div class="space-y-1">
                                <label class="block font-bold text-slate-700">Allowlist Duration</label>
                                <select 
                                    v-model.number="allowForm.duration_days"
                                    class="w-full bg-white border border-slate-300 rounded-[3px] px-2.5 py-2 text-xs font-bold text-slate-900 focus:ring-1 focus:ring-emerald-600 cursor-pointer"
                                >
                                    <option :value="0">Permanent (Indefinite)</option>
                                    <option :value="30">30 Days</option>
                                    <option :value="90">90 Days</option>
                                    <option :value="365">1 Year</option>
                                </select>
                            </div>
                        </div>

                        <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                            <button 
                                type="button" 
                                @click="showAllowModal = false" 
                                class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-[3px] text-xs font-bold cursor-pointer"
                            >
                                Cancel
                            </button>
                            <button 
                                type="submit" 
                                :disabled="allowForm.processing"
                                class="px-4 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-[3px] text-xs font-bold shadow-2xs transition cursor-pointer disabled:opacity-50 flex items-center gap-1"
                            >
                                <span>{{ allowForm.processing ? 'Saving...' : 'Add to Allowlist' }}</span>
                            </button>
                        </div>

                    </form>
                </div>
            </div>

            <!-- 3. Unblock Confirmation Modal -->
            <div v-if="showDeleteBlockModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4 z-50">
                <div class="bg-white rounded-lg max-w-md w-full p-5 shadow-2xl border border-slate-200 space-y-4">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-lg bg-rose-50 border border-rose-100 text-rose-600 flex items-center justify-center shrink-0">
                            <TrashIcon class="w-5 h-5 stroke-[1.75]" />
                        </div>
                        <div class="flex-1">
                            <h3 class="text-sm font-black text-slate-900">Unblock IP Address?</h3>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Are you sure you want to unblock <strong class="text-slate-900 font-mono">{{ deletingBlock?.ip_address }}</strong>? Traffic from this IP will be allowed to connect again.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                        <button 
                            @click="showDeleteBlockModal = false; deletingBlock = null" 
                            type="button" 
                            class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-[3px] text-xs font-bold transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button 
                            @click="executeDeleteBlock" 
                            type="button"
                            class="px-4 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-[3px] text-xs font-bold shadow-2xs transition cursor-pointer flex items-center gap-1.5"
                        >
                            <TrashIcon class="w-3.5 h-3.5" />
                            <span>Unblock IP</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- 4. Remove Allowlist Confirmation Modal -->
            <div v-if="showDeleteAllowModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4 z-50">
                <div class="bg-white rounded-lg max-w-md w-full p-5 shadow-2xl border border-slate-200 space-y-4">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-lg bg-amber-50 border border-amber-100 text-amber-600 flex items-center justify-center shrink-0">
                            <ExclamationTriangleIcon class="w-5 h-5 stroke-[1.75]" />
                        </div>
                        <div class="flex-1">
                            <h3 class="text-sm font-black text-slate-900">Remove from Allowlist?</h3>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Are you sure you want to remove <strong class="text-slate-900 font-mono">{{ deletingAllow?.ip_address }}</strong> ({{ deletingAllow?.label }}) from your trusted allowlist?
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                        <button 
                            @click="showDeleteAllowModal = false; deletingAllow = null" 
                            type="button" 
                            class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-[3px] text-xs font-bold transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button 
                            @click="executeDeleteAllow" 
                            type="button"
                            class="px-4 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-[3px] text-xs font-bold shadow-2xs transition cursor-pointer flex items-center gap-1.5"
                        >
                            <TrashIcon class="w-3.5 h-3.5" />
                            <span>Remove Allowlist</span>
                        </button>
                    </div>
                </div>
            </div>

        </Teleport>

    </AuthenticatedLayout>
</template>
