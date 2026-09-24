<script setup>
import { ref, watch, computed } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import RefreshButton from '@/Components/UI/RefreshButton.vue'
import InfoCardsGrid from '@/Components/UI/InfoCardsGrid.vue'
import InfoCard from '@/Components/UI/InfoCard.vue'
import DataTableFilter from '@/Components/UI/DataTableFilter.vue'
import FilterSelect from '@/Components/UI/FilterSelect.vue'
import RowActionDropdown from '@/Components/UI/RowActionDropdown.vue'

import {
    GlobeAltIcon,
    ServerIcon,
    PlusIcon,
    TrashIcon,
    ArrowPathIcon,
    XMarkIcon,
    ShieldCheckIcon,
    CheckIcon,
    DocumentTextIcon,
    CodeBracketIcon,
    BoltIcon,
    DocumentDuplicateIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    zones: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    stats: {
        type: Object,
        default: () => ({
            total_zones: 0,
            active_zones: 0,
            total_records: 0,
            bind_status: 'Active & Running (v9.20)',
        }),
    },
    subscriptions: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({ search: '', status: 'all' }),
    },
})

// Search & Filter State
const search = ref(props.filters.search || '')
const statusFilter = ref(props.filters.status || 'all')
const feedbackMsg = ref('')

const zoneList = computed(() => {
    if (Array.isArray(props.zones)) return props.zones
    if (props.zones?.data && Array.isArray(props.zones.data)) return props.zones.data
    return []
})

const filteredZones = computed(() => {
    let list = zoneList.value

    if (search.value.trim()) {
        const q = search.value.toLowerCase()
        list = list.filter(z =>
            z.domain.toLowerCase().includes(q) ||
            (z.primary_ns || '').toLowerCase().includes(q) ||
            (z.subscription?.domain || '').toLowerCase().includes(q) ||
            (z.subscription?.user?.name || '').toLowerCase().includes(q)
        )
    }

    if (statusFilter.value !== 'all') {
        list = list.filter(z => (z.status || 'active') === statusFilter.value)
    }

    return list
})

const resetFilters = () => {
    search.value = ''
    statusFilter.value = 'all'
}

// 1. CREATE ZONE MODAL
const showCreateModal = ref(false)
const createForm = useForm({
    domain: '',
    server_ip: '103.59.177.138',
    primary_ns: 'ns1.deeptouchit.com',
    secondary_ns: 'ns2.deeptouchit.com',
    admin_email: 'hostmaster.deeptouchit.com',
    subscription_id: '',
    auto_populate: true,
})

const openCreateModal = () => {
    createForm.reset()
    createForm.server_ip = '103.59.177.138'
    createForm.primary_ns = 'ns1.deeptouchit.com'
    createForm.secondary_ns = 'ns2.deeptouchit.com'
    createForm.admin_email = 'hostmaster.deeptouchit.com'
    createForm.auto_populate = true
    createForm.subscription_id = props.subscriptions[0]?.id || ''
    showCreateModal.value = true
}

const submitCreate = () => {
    createForm.post(route('admin.dns.zones.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false
            feedbackMsg.value = `DNS Zone '${createForm.domain}' compiled into BIND9 successfully.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        },
    })
}

// 2. RAW ZONE VIEWER MODAL
const showRawModal = ref(false)
const selectedZoneDomain = ref('')
const selectedZoneSerial = ref('')
const rawZoneContent = ref('')
const isLoadingRaw = ref(false)

const openRawModal = async (zone) => {
    selectedZoneDomain.value = zone.domain
    selectedZoneSerial.value = zone.serial
    rawZoneContent.value = 'Compiling and fetching live BIND9 zone file...'
    showRawModal.value = true
    isLoadingRaw.value = true

    try {
        const res = await fetch(route('admin.dns.zones.raw', zone.id))
        const data = await res.json()
        rawZoneContent.value = data.raw_content || '; No raw content returned.'
    } catch (e) {
        rawZoneContent.value = '; Failed to load zone file from BIND9 directory.'
    } finally {
        isLoadingRaw.value = false
    }
}

// 3. RELOAD BIND DAEMON
const isReloadingDaemon = ref(false)
const reloadDaemon = () => {
    isReloadingDaemon.value = true
    router.post(route('admin.dns.zones.reload-daemon'), {}, {
        preserveScroll: true,
        onSuccess: () => {
            isReloadingDaemon.value = false
            feedbackMsg.value = 'BIND9 daemon reloaded successfully.'
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        },
        onError: () => {
            isReloadingDaemon.value = false
        }
    })
}

// 4. RELOAD ZONE
const reloadZone = (zone) => {
    router.post(route('admin.dns.zones.reload', zone.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = `Zone ${zone.domain} SOA serial incremented & reloaded.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 5. TOGGLE DNSSEC
const toggleDnssec = (zone) => {
    router.post(route('admin.dns.zones.toggle-dnssec', zone.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = `DNSSEC state updated for ${zone.domain}.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 6. TOGGLE STATUS
const toggleStatus = (zone) => {
    const action = zone.status === 'active' ? 'Suspend' : 'Activate'
    if (confirm(`${action} authoritative resolution for ${zone.domain}?`)) {
        useForm({}).post(route('admin.dns.zones.toggle-status', zone.id), {
            preserveScroll: true,
            onSuccess: () => {
                feedbackMsg.value = `Zone ${zone.domain} status updated.`
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            }
        })
    }
}

// 7. DELETE ZONE
const showDeleteModal = ref(false)
const zoneToDelete = ref(null)
const deleteForm = useForm({})

const openDeleteModal = (zone) => {
    zoneToDelete.value = zone
    showDeleteModal.value = true
}

const submitDelete = () => {
    if (!zoneToDelete.value) return
    deleteForm.delete(route('admin.dns.zones.destroy', zoneToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false
            feedbackMsg.value = `DNS Zone ${zoneToDelete.value.domain} deleted.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

const copyToClipboard = (text) => {
    navigator.clipboard.writeText(text)
    feedbackMsg.value = 'Copied zone file to clipboard!'
    setTimeout(() => { feedbackMsg.value = '' }, 2500)
}
</script>

<template>
    <Head title="DNS Zones - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'DNS', href: route('admin.dns.zones') },
                    { label: 'Zones' }
                ]"
            >
                <template #actions>
                    <button
                        type="button"
                        @click="reloadDaemon"
                        :disabled="isReloadingDaemon"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer disabled:opacity-50"
                    >
                        <ArrowPathIcon class="w-3.5 h-3.5 text-slate-500" :class="{ 'animate-spin': isReloadingDaemon }" />
                        <span>Reload BIND9</span>
                    </button>

                    <button 
                        type="button" 
                        @click="openCreateModal"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <PlusIcon class="w-3.5 h-3.5" />
                        <span>Create Zone</span>
                    </button>

                    <RefreshButton />
                </template>
            </PageHeader>

            <!-- Feedback Banner -->
            <div v-if="feedbackMsg" class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-3 py-2 rounded-[3px] text-xs font-bold flex items-center justify-between shadow-2xs">
                <div class="flex items-center gap-2">
                    <CheckIcon class="w-4 h-4 text-emerald-600" />
                    <span>{{ feedbackMsg }}</span>
                </div>
                <button @click="feedbackMsg = ''" class="text-emerald-600 hover:text-emerald-800 text-xs cursor-pointer">✕</button>
            </div>

            <!-- 2. Ultra-Compact KPI Info Cards (56px) -->
            <InfoCardsGrid :cols="4">
                <InfoCard
                    title="Authoritative Zones"
                    :value="String(stats.total_zones || zoneList.length || 0)"
                    badge="Zones"
                    badgeType="info"
                    color="blue"
                    :icon="GlobeAltIcon"
                />

                <InfoCard
                    title="Active Resolving"
                    :value="String(stats.active_zones || 0)"
                    badge="Active"
                    badgeType="success"
                    color="emerald"
                    :icon="ShieldCheckIcon"
                />

                <InfoCard
                    title="Resource Records"
                    :value="String(stats.total_records || 0)"
                    badge="RR Total"
                    badgeType="info"
                    color="purple"
                    :icon="DocumentTextIcon"
                />

                <InfoCard
                    title="DNS Daemon Engine"
                    :value="stats.bind_status || 'BIND 9.20'"
                    badge="Running"
                    badgeType="success"
                    color="sky"
                    :icon="ServerIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Standard Filter Toolbar -->
            <DataTableFilter
                v-model="search"
                searchPlaceholder="Search zone domain, primary nameserver, or owner..."
                @search="() => {}"
                @filter="() => {}"
                @reset="resetFilters"
            >
                <FilterSelect
                    v-model="statusFilter"
                    label="Resolution Status"
                    :options="[
                        { label: 'All Statuses', value: 'all' },
                        { label: 'Active Zones', value: 'active' },
                        { label: 'Suspended Zones', value: 'suspended' }
                    ]"
                    placeholder="All Statuses"
                />
            </DataTableFilter>

            <!-- 4. Standard Data Table -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Zone Domain</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Associated Account / Owner</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">SOA Serial</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Primary Nameserver</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">DNSSEC</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Records</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Status</th>
                                <th class="py-2.5 px-3 w-14 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(zone, index) in filteredZones" :key="zone.id || index" class="hover:bg-blue-50/30 transition">
                                <!-- # -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>

                                <!-- Domain -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs border border-blue-100 shrink-0">
                                            <GlobeAltIcon class="w-3.5 h-3.5" />
                                        </div>
                                        <Link 
                                            :href="route('admin.dns.records', { zone_id: zone.id })"
                                            class="font-bold text-blue-600 hover:text-blue-800 font-mono leading-tight hover:underline cursor-pointer"
                                        >
                                            {{ zone.domain }}
                                        </Link>
                                    </div>
                                </td>

                                <!-- Owner -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap">
                                    <div v-if="zone.subscription" class="space-y-0.5">
                                        <span class="font-bold text-slate-900 block leading-tight">{{ zone.subscription.domain }}</span>
                                        <span class="text-[10px] font-mono text-slate-400 block">@{{ zone.subscription.username }}</span>
                                    </div>
                                    <span v-else class="text-slate-400 italic">System Scope</span>
                                </td>

                                <!-- SOA Serial -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[10.5px] text-slate-700">
                                    {{ zone.serial || '2026090201' }}
                                </td>

                                <!-- Primary NS -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono text-[10.5px] text-slate-600">
                                    {{ zone.primary_ns || 'ns1.deeptouchit.com' }}
                                </td>

                                <!-- DNSSEC -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="zone.has_dnssec ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-500 border-slate-200'"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold border font-mono"
                                    >
                                        {{ zone.has_dnssec ? 'DNSSEC Signed' : 'Disabled' }}
                                    </span>
                                </td>

                                <!-- Records Count -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono font-bold text-slate-900">
                                    {{ zone.records_count || zone.records?.length || 0 }}
                                </td>

                                <!-- Status -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="zone.status === 'active' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono"
                                    >
                                        {{ zone.status || 'active' }}
                                    </span>
                                </td>

                                <!-- Action -->
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <RowActionDropdown>
                                        <Link 
                                            :href="route('admin.dns.records', { zone_id: zone.id })"
                                            class="w-full text-left px-3 py-1.5 text-xs text-blue-600 hover:bg-blue-50 flex items-center gap-2 font-medium cursor-pointer"
                                        >
                                            <ListBulletIcon class="w-3.5 h-3.5 text-blue-500" />
                                            <span>Manage Records</span>
                                        </Link>

                                        <button
                                            type="button"
                                            @click="openRawModal(zone)"
                                            class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                        >
                                            <CodeBracketIcon class="w-3.5 h-3.5 text-slate-500" />
                                            <span>View Raw Zone File</span>
                                        </button>

                                        <button
                                            type="button"
                                            @click="reloadZone(zone)"
                                            class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                        >
                                            <ArrowPathIcon class="w-3.5 h-3.5 text-slate-500" />
                                            <span>Increment Serial & Reload</span>
                                        </button>

                                        <button
                                            type="button"
                                            @click="toggleDnssec(zone)"
                                            class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                        >
                                            <ShieldCheckIcon class="w-3.5 h-3.5 text-slate-500" />
                                            <span>{{ zone.has_dnssec ? 'Disable DNSSEC' : 'Enable DNSSEC Signing' }}</span>
                                        </button>

                                        <button
                                            type="button"
                                            @click="toggleStatus(zone)"
                                            class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                        >
                                            <BoltIcon class="w-3.5 h-3.5 text-slate-500" />
                                            <span>{{ zone.status === 'active' ? 'Suspend Resolution' : 'Activate Resolution' }}</span>
                                        </button>

                                        <button
                                            type="button"
                                            @click="openDeleteModal(zone)"
                                            class="w-full text-left px-3 py-1.5 text-xs text-rose-600 hover:bg-rose-50 flex items-center gap-2 font-medium cursor-pointer"
                                        >
                                            <TrashIcon class="w-3.5 h-3.5" />
                                            <span>Delete DNS Zone</span>
                                        </button>
                                    </RowActionDropdown>
                                </td>
                            </tr>

                            <tr v-if="!filteredZones || filteredZones.length === 0">
                                <td colspan="9" class="py-12 text-center text-slate-400">
                                    No DNS zones found matching search.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 1. CREATE DNS ZONE MODAL -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-lg overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <PlusIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Create Authoritative DNS Zone
                        </h3>
                    </div>
                    <button @click="showCreateModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitCreate" class="p-4 space-y-3 text-xs">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Account Subscription <span class="text-rose-500">*</span></label>
                            <select v-model="createForm.subscription_id" required class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                                <option v-for="s in subscriptions" :key="s.id" :value="s.id">
                                    {{ s.domain }} (@{{ s.username }})
                                </option>
                            </select>
                        </div>

                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Zone Domain (FQDN) <span class="text-rose-500">*</span></label>
                            <input v-model="createForm.domain" type="text" required placeholder="example.com" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Primary IP Address (@ Record) <span class="text-rose-500">*</span></label>
                        <input v-model="createForm.server_ip" type="text" required placeholder="103.59.177.138" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Primary Nameserver (NS1)</label>
                            <input v-model="createForm.primary_ns" type="text" required class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                        </div>

                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Secondary Nameserver (NS2)</label>
                            <input v-model="createForm.secondary_ns" type="text" required class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                        </div>
                    </div>

                    <div class="flex items-center gap-2 pt-1">
                        <input v-model="createForm.auto_populate" id="auto_populate" type="checkbox" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer" />
                        <label for="auto_populate" class="text-slate-700 font-medium cursor-pointer">Auto-generate standard web & mail records (A, CNAME, MX, SPF, DKIM)</label>
                    </div>

                    <div class="px-4 py-2.5 -mx-4 -mb-4 mt-4 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end gap-2 text-xs">
                        <button
                            type="button"
                            @click="showCreateModal = false"
                            class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="createForm.processing"
                            class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                        >
                            {{ createForm.processing ? 'Compiling Zone...' : 'Create DNS Zone' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 2. RAW ZONE VIEWER MODAL -->
        <div v-if="showRawModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-2xl overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <CodeBracketIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            BIND9 Zone File: {{ selectedZoneDomain }} (Serial: {{ selectedZoneSerial }})
                        </h3>
                    </div>
                    <button @click="showRawModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-3 text-xs">
                    <div class="bg-slate-900 text-emerald-400 p-3.5 rounded-[3px] font-mono text-[11px] max-h-80 overflow-y-auto border border-slate-800 leading-relaxed whitespace-pre-wrap">
                        {{ rawZoneContent }}
                    </div>
                </div>

                <div class="px-4 py-2.5 bg-slate-50/70 border-t border-slate-100 flex items-center justify-between text-xs">
                    <button
                        type="button"
                        @click="copyToClipboard(rawZoneContent)"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-100 text-slate-700 font-bold rounded-[3px] border border-slate-200 transition flex items-center gap-1.5 cursor-pointer"
                    >
                        <DocumentDuplicateIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>Copy Zone File</span>
                    </button>

                    <button
                        type="button"
                        @click="showRawModal = false"
                        class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>

        <!-- 3. DELETE MODAL -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-rose-50 text-rose-600 flex items-center justify-center font-bold border border-rose-100">
                            <TrashIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Delete DNS Zone
                        </h3>
                    </div>
                    <button @click="showDeleteModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-2.5 text-xs">
                    <p class="text-slate-600">
                        Are you sure you want to delete DNS zone <strong class="text-slate-900 font-mono">[{{ zoneToDelete?.domain }}]</strong>? All records will be removed and BIND9 configuration will be updated immediately.
                    </p>
                </div>

                <div class="px-4 py-2.5 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end gap-2 text-xs">
                    <button
                        type="button"
                        @click="showDeleteModal = false"
                        class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="submitDelete"
                        :disabled="deleteForm.processing"
                        class="px-3.5 py-1.5 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                    >
                        {{ deleteForm.processing ? 'Deleting...' : 'Delete Zone' }}
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
