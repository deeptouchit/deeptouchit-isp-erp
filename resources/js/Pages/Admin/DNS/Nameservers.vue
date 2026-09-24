<script setup>
import { ref, computed } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import RefreshButton from '@/Components/UI/RefreshButton.vue'
import InfoCardsGrid from '@/Components/UI/InfoCardsGrid.vue'
import InfoCard from '@/Components/UI/InfoCard.vue'
import DataTableFilter from '@/Components/UI/DataTableFilter.vue'
import RowActionDropdown from '@/Components/UI/RowActionDropdown.vue'

import {
    ServerStackIcon,
    ServerIcon,
    GlobeAltIcon,
    PlusIcon,
    TrashIcon,
    PencilSquareIcon,
    ArrowPathIcon,
    XMarkIcon,
    CheckIcon,
    ShieldCheckIcon,
    BoltIcon,
    SignalIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    nameservers: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({
            total_nameservers: 0,
            primary_ns: 'ns1.deeptouchit.com',
            secondary_ns: 'ns2.deeptouchit.com',
            primary_ip: '103.59.177.138',
            total_zones: 0,
            bind_port53: 'Listening (UDP/TCP 53)',
        }),
    },
})

// Toast Feedback
const feedbackMsg = ref('')
const search = ref('')

const filteredNameservers = computed(() => {
    let list = props.nameservers || []
    if (search.value.trim()) {
        const q = search.value.toLowerCase()
        list = list.filter(ns =>
            ns.hostname.toLowerCase().includes(q) ||
            ns.ip_address.toLowerCase().includes(q) ||
            (ns.ipv6_address || '').toLowerCase().includes(q)
        )
    }
    return list
})

// 1. ADD / EDIT NAMESERVER MODAL
const showNsModal = ref(false)
const isEditing = ref(false)
const editingNsId = ref(null)

const nsForm = useForm({
    hostname: '',
    ip_address: '103.59.177.138',
    ipv6_address: '',
    is_primary: false,
    is_default: true,
})

const openAddModal = () => {
    isEditing.value = false
    editingNsId.value = null
    nsForm.reset()
    nsForm.hostname = ''
    nsForm.ip_address = '103.59.177.138'
    nsForm.ipv6_address = ''
    nsForm.is_primary = false
    nsForm.is_default = true
    showNsModal.value = true
}

const openEditModal = (ns) => {
    isEditing.value = true
    editingNsId.value = ns.id
    nsForm.hostname = ns.hostname
    nsForm.ip_address = ns.ip_address
    nsForm.ipv6_address = ns.ipv6_address || ''
    nsForm.is_primary = ns.is_primary
    nsForm.is_default = ns.is_default
    showNsModal.value = true
}

const submitNs = () => {
    if (isEditing.value) {
        nsForm.put(route('admin.dns.nameservers.update', editingNsId.value), {
            preserveScroll: true,
            onSuccess: () => {
                showNsModal.value = false
                feedbackMsg.value = `Nameserver node updated successfully.`
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            },
        })
    } else {
        nsForm.post(route('admin.dns.nameservers.store'), {
            preserveScroll: true,
            onSuccess: () => {
                showNsModal.value = false
                feedbackMsg.value = `Nameserver node '${nsForm.hostname}' registered.`
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            },
        })
    }
}

// 2. TEST PROBE
const isTestingAll = ref(false)
const testAllProbes = () => {
    isTestingAll.value = true
    router.post(route('admin.dns.nameservers.test-all'), {}, {
        preserveScroll: true,
        onSuccess: () => {
            isTestingAll.value = false
            feedbackMsg.value = 'UDP/TCP Port 53 resolution probed across all cluster nodes.'
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        },
        onError: () => {
            isTestingAll.value = false
        }
    })
}

const testProbe = (ns) => {
    router.post(route('admin.dns.nameservers.test-probe', ns.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = `Probe response received from ${ns.hostname}.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 3. TOGGLE STATUS
const toggleStatus = (ns) => {
    router.post(route('admin.dns.nameservers.toggle-status', ns.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = `Nameserver ${ns.hostname} status updated.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 4. DELETE NAMESERVER
const showDeleteModal = ref(false)
const nsToDelete = ref(null)
const deleteForm = useForm({})

const openDeleteModal = (ns) => {
    nsToDelete.value = ns
    showDeleteModal.value = true
}

const submitDelete = () => {
    if (!nsToDelete.value) return
    deleteForm.delete(route('admin.dns.nameservers.destroy', nsToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false
            feedbackMsg.value = `Nameserver node removed.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}
</script>

<template>
    <Head title="Custom Nameservers & Glue Records - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Services', href: '#' },
                    { label: 'DNS Management', href: route('admin.dns.zones') },
                    { label: 'Custom Nameservers & Glue Records' }
                ]"
            >
                <template #actions>
                    <Link
                        :href="route('admin.dns.zones')"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <ArrowLeftIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>DNS Zones</span>
                    </Link>

                    <button
                        type="button"
                        @click="testAllProbes"
                        :disabled="isTestingAll"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer disabled:opacity-50"
                    >
                        <SignalIcon class="w-3.5 h-3.5 text-blue-600" :class="{ 'animate-pulse': isTestingAll }" />
                        <span>Test All Probes</span>
                    </button>

                    <button 
                        type="button" 
                        @click="openAddModal"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <PlusIcon class="w-3.5 h-3.5" />
                        <span>Add Nameserver</span>
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
                    title="Cluster Nameservers"
                    :value="String(stats.total_nameservers || nameservers.length || 0)"
                    badge="Nodes"
                    badgeType="info"
                    color="blue"
                    :icon="ServerStackIcon"
                />

                <InfoCard
                    title="Primary Master (NS1)"
                    :value="stats.primary_ns || 'ns1.deeptouchit.com'"
                    badge="Authoritative"
                    badgeType="success"
                    color="emerald"
                    :icon="GlobeAltIcon"
                />

                <InfoCard
                    title="Secondary Slave (NS2)"
                    :value="stats.secondary_ns || 'ns2.deeptouchit.com'"
                    badge="Redundant"
                    badgeType="info"
                    color="purple"
                    :icon="ServerIcon"
                />

                <InfoCard
                    title="Port 53 Listener"
                    :value="stats.bind_port53 || 'Active (53)'"
                    badge="UDP/TCP"
                    badgeType="success"
                    color="sky"
                    :icon="SignalIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Standard Filter Toolbar -->
            <DataTableFilter
                v-model="search"
                searchPlaceholder="Search nameserver FQDN or IP address..."
                @search="() => {}"
                @filter="() => {}"
                @reset="search = ''"
            />

            <!-- 4. Standard Data Table -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Nameserver Hostname (FQDN)</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">IPv4 Glue Address</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">IPv6 Glue Address</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Role / Priority</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Health Probe</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Status</th>
                                <th class="py-2.5 px-3 w-28">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(ns, index) in filteredNameservers" :key="ns.id || index" class="hover:bg-blue-50/30 transition">
                                <!-- # -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>

                                <!-- Hostname -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono font-bold text-slate-900">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs border border-blue-100 shrink-0">
                                            <ServerStackIcon class="w-3.5 h-3.5" />
                                        </div>
                                        <span>{{ ns.hostname }}</span>
                                    </div>
                                </td>

                                <!-- IPv4 -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono text-[11px] text-slate-800">
                                    {{ ns.ip_address }}
                                </td>

                                <!-- IPv6 -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono text-[10.5px] text-slate-500">
                                    {{ ns.ipv6_address || 'None' }}
                                </td>

                                <!-- Role -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="ns.is_primary ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-slate-100 text-slate-700 border-slate-200'"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono"
                                    >
                                        {{ ns.is_primary ? 'Primary (Master)' : 'Secondary (Slave)' }}
                                    </span>
                                </td>

                                <!-- Health Probe -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <button
                                        type="button"
                                        @click="testProbe(ns)"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-[3px] text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100 transition cursor-pointer"
                                    >
                                        <SignalIcon class="w-3 h-3 text-emerald-600" />
                                        <span>{{ ns.last_probe_status || 'Probe Active' }}</span>
                                    </button>
                                </td>

                                <!-- Status -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="ns.status === 'active' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono"
                                    >
                                        {{ ns.status || 'active' }}
                                    </span>
                                </td>

                                <!-- Action -->
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1">
                                        <button 
                                            type="button" 
                                            @click="openEditModal(ns)"
                                            class="px-2 py-1 bg-white hover:bg-slate-50 text-blue-700 font-bold rounded-[3px] text-xs border border-slate-200 shadow-2xs transition cursor-pointer"
                                            title="Edit Nameserver"
                                        >
                                            Edit ✏️
                                        </button>

                                        <RowActionDropdown>
                                            <button
                                                type="button"
                                                @click="testProbe(ns)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <SignalIcon class="w-3.5 h-3.5 text-slate-500" />
                                                <span>Probe Port 53 Health</span>
                                            </button>

                                            <button
                                                type="button"
                                                @click="toggleStatus(ns)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <BoltIcon class="w-3.5 h-3.5 text-slate-500" />
                                                <span>{{ ns.status === 'active' ? 'Suspend Node' : 'Activate Node' }}</span>
                                            </button>

                                            <button
                                                type="button"
                                                @click="openDeleteModal(ns)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-rose-600 hover:bg-rose-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <TrashIcon class="w-3.5 h-3.5" />
                                                <span>Delete Node</span>
                                            </button>
                                        </RowActionDropdown>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!filteredNameservers || filteredNameservers.length === 0">
                                <td colspan="8" class="py-12 text-center text-slate-400">
                                    No nameserver nodes registered.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 1. ADD / EDIT NAMESERVER MODAL -->
        <div v-if="showNsModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <ServerStackIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            {{ isEditing ? 'Edit Nameserver Node' : 'Add Cluster Nameserver' }}
                        </h3>
                    </div>
                    <button @click="showNsModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitNs" class="p-4 space-y-3 text-xs">
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Hostname (FQDN) <span class="text-rose-500">*</span></label>
                        <input v-model="nsForm.hostname" type="text" required placeholder="ns1.example.com" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">IPv4 Glue Address <span class="text-rose-500">*</span></label>
                        <input v-model="nsForm.ip_address" type="text" required placeholder="103.59.177.138" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">IPv6 Glue Address (Optional)</label>
                        <input v-model="nsForm.ipv6_address" type="text" placeholder="2001:db8::1" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    </div>

                    <div class="flex flex-col gap-2 pt-1">
                        <label class="flex items-center gap-2 cursor-pointer font-bold text-slate-700">
                            <input v-model="nsForm.is_primary" type="checkbox" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer" />
                            <span>Designate as Master Primary Nameserver</span>
                        </label>

                        <label class="flex items-center gap-2 cursor-pointer font-bold text-slate-700">
                            <input v-model="nsForm.is_default" type="checkbox" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer" />
                            <span>Auto-assign to all newly provisioned domains</span>
                        </label>
                    </div>

                    <div class="px-4 py-2.5 -mx-4 -mb-4 mt-4 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end gap-2 text-xs">
                        <button
                            type="button"
                            @click="showNsModal = false"
                            class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="nsForm.processing"
                            class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                        >
                            {{ nsForm.processing ? 'Saving...' : (isEditing ? 'Save Changes' : 'Add Nameserver') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 2. DELETE MODAL -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-rose-50 text-rose-600 flex items-center justify-center font-bold border border-rose-100">
                            <TrashIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Delete Nameserver Node
                        </h3>
                    </div>
                    <button @click="showDeleteModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-2.5 text-xs">
                    <p class="text-slate-600">
                        Are you sure you want to remove nameserver <strong class="text-slate-900 font-mono">[{{ nsToDelete?.hostname }}]</strong>?
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
                        {{ deleteForm.processing ? 'Deleting...' : 'Delete Node' }}
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
