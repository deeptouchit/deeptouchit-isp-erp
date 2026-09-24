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
    AdjustmentsHorizontalIcon,
    GlobeAltIcon,
    PlusIcon,
    TrashIcon,
    PencilSquareIcon,
    ArrowPathIcon,
    XMarkIcon,
    CheckIcon,
    DocumentDuplicateIcon,
    ServerIcon,
    EnvelopeIcon,
    ShieldCheckIcon,
    BoltIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    zones: {
        type: Array,
        default: () => [],
    },
    selectedZone: {
        type: Object,
        default: null,
    },
    records: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({
            total_records: 0,
            address_count: 0,
            mx_count: 0,
            txt_count: 0,
        }),
    },
    filters: {
        type: Object,
        default: () => ({ type: 'ALL', search: '', zone_id: null }),
    },
})

// Search & Filter State
const search = ref(props.filters.search || '')
const typeFilter = ref(props.filters.type || 'ALL')
const selectedZoneId = ref(props.selectedZone?.id || '')
const feedbackMsg = ref('')

const onZoneChange = () => {
    router.get(
        route('admin.dns.records'),
        { zone_id: selectedZoneId.value, type: typeFilter.value, search: search.value },
        { preserveState: true }
    )
}

const filteredRecords = computed(() => {
    let list = props.records || []

    if (search.value.trim()) {
        const q = search.value.toLowerCase()
        list = list.filter(r =>
            r.name.toLowerCase().includes(q) ||
            r.content.toLowerCase().includes(q) ||
            r.type.toLowerCase().includes(q)
        )
    }

    if (typeFilter.value !== 'ALL') {
        list = list.filter(r => r.type === typeFilter.value)
    }

    return list
})

const resetFilters = () => {
    search.value = ''
    typeFilter.value = 'ALL'
}

// 1. ADD / EDIT RECORD MODAL
const showRecordModal = ref(false)
const isEditing = ref(false)
const editingRecordId = ref(null)

const recordForm = useForm({
    dns_zone_id: props.selectedZone?.id || '',
    name: '@',
    type: 'A',
    content: '',
    ttl: 3600,
    priority: 10,
    port: null,
    weight: null,
})

const openAddModal = () => {
    isEditing.value = false
    editingRecordId.value = null
    recordForm.reset()
    recordForm.dns_zone_id = selectedZoneId.value || props.zones[0]?.id || ''
    recordForm.name = '@'
    recordForm.type = 'A'
    recordForm.ttl = 3600
    showRecordModal.value = true
}

const openEditModal = (record) => {
    isEditing.value = true
    editingRecordId.value = record.id
    recordForm.dns_zone_id = record.dns_zone_id
    recordForm.name = record.name
    recordForm.type = record.type
    recordForm.content = record.content
    recordForm.ttl = record.ttl || 3600
    recordForm.priority = record.priority || 10
    recordForm.port = record.port || null
    recordForm.weight = record.weight || null
    showRecordModal.value = true
}

const submitRecord = () => {
    if (isEditing.value) {
        recordForm.put(route('admin.dns.records.update', editingRecordId.value), {
            preserveScroll: true,
            onSuccess: () => {
                showRecordModal.value = false
                feedbackMsg.value = `Record '${recordForm.name}' (${recordForm.type}) updated.`
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            }
        })
    } else {
        recordForm.post(route('admin.dns.records.store'), {
            preserveScroll: true,
            onSuccess: () => {
                showRecordModal.value = false
                feedbackMsg.value = `Record '${recordForm.name}' (${recordForm.type}) added to zone.`
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            }
        })
    }
}

// 2. TOGGLE STATUS
const toggleStatus = (record) => {
    router.post(route('admin.dns.records.toggle-status', record.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = `Record ${record.name} status updated.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 3. DELETE RECORD
const showDeleteModal = ref(false)
const recordToDelete = ref(null)
const deleteForm = useForm({})

const openDeleteModal = (record) => {
    recordToDelete.value = record
    showDeleteModal.value = true
}

const submitDelete = () => {
    if (!recordToDelete.value) return
    deleteForm.delete(route('admin.dns.records.destroy', recordToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false
            feedbackMsg.value = `Record ${recordToDelete.value.name} deleted.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

const copyContent = (record) => {
    navigator.clipboard.writeText(record.content)
    feedbackMsg.value = `Copied content of ${record.name} to clipboard!`
    setTimeout(() => { feedbackMsg.value = '' }, 2500)
}
</script>

<template>
    <Head title="DNS Resource Records - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Services', href: '#' },
                    { label: 'DNS Management', href: route('admin.dns.zones') },
                    { label: selectedZone ? `Records: ${selectedZone.domain}` : 'Resource Records Management' }
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

                    <!-- Zone Switcher -->
                    <div class="flex items-center gap-1.5 bg-white px-2.5 py-1.5 rounded-[3px] border border-slate-200 shadow-2xs text-xs">
                        <span class="font-bold text-slate-500">Zone:</span>
                        <select 
                            v-model="selectedZoneId"
                            @change="onZoneChange"
                            class="bg-blue-50 border border-blue-200 text-xs font-bold text-blue-700 rounded-[2px] py-0.5 px-2 focus:ring-1 focus:ring-blue-500 cursor-pointer outline-none"
                        >
                            <option v-for="z in zones" :key="z.id" :value="z.id">
                                {{ z.domain }}
                            </option>
                        </select>
                    </div>

                    <button 
                        type="button" 
                        @click="openAddModal"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <PlusIcon class="w-3.5 h-3.5" />
                        <span>Add DNS Record</span>
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
                    title="Total Resource Records"
                    :value="String(stats.total_records || records.length || 0)"
                    badge="Records"
                    badgeType="info"
                    color="blue"
                    :icon="AdjustmentsHorizontalIcon"
                />

                <InfoCard
                    title="A / AAAA Address"
                    :value="String(stats.address_count || 0)"
                    badge="IP Binding"
                    badgeType="success"
                    color="emerald"
                    :icon="ServerIcon"
                />

                <InfoCard
                    title="MX Mail Exchanger"
                    :value="String(stats.mx_count || 0)"
                    badge="Routing"
                    badgeType="info"
                    color="sky"
                    :icon="EnvelopeIcon"
                />

                <InfoCard
                    title="TXT / SPF / DKIM"
                    :value="String(stats.txt_count || 0)"
                    badge="Verification"
                    badgeType="purple"
                    color="purple"
                    :icon="ShieldCheckIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Standard Filter Toolbar -->
            <DataTableFilter
                v-model="search"
                searchPlaceholder="Search record host, target value, or type..."
                @search="() => {}"
                @filter="() => {}"
                @reset="resetFilters"
            >
                <FilterSelect
                    v-model="typeFilter"
                    label="Record Type"
                    :options="[
                        { label: 'All Record Types', value: 'ALL' },
                        { label: 'A (IPv4 Address)', value: 'A' },
                        { label: 'AAAA (IPv6 Address)', value: 'AAAA' },
                        { label: 'CNAME (Alias)', value: 'CNAME' },
                        { label: 'MX (Mail Exchanger)', value: 'MX' },
                        { label: 'TXT (Text / SPF / DKIM)', value: 'TXT' },
                        { label: 'NS (Nameserver)', value: 'NS' },
                        { label: 'SRV (Service)', value: 'SRV' },
                        { label: 'CAA (Certificate Auth)', value: 'CAA' },
                        { label: 'PTR (Reverse DNS)', value: 'PTR' }
                    ]"
                    placeholder="All Types"
                />
            </DataTableFilter>

            <!-- 4. Standard Data Table -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Record Name / Host</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 w-20">Type</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Target / Content / Routing Value</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 w-20">TTL (s)</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 w-20">Priority</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Status</th>
                                <th class="py-2.5 px-3 w-28">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(record, index) in filteredRecords" :key="record.id || index" class="hover:bg-blue-50/30 transition">
                                <!-- # -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>

                                <!-- Name -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono font-bold text-slate-900">
                                    {{ record.name }}
                                </td>

                                <!-- Type -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="[
                                            record.type === 'A' ? 'bg-blue-50 text-blue-700 border-blue-200' :
                                            record.type === 'CNAME' ? 'bg-indigo-50 text-indigo-700 border-indigo-200' :
                                            record.type === 'MX' ? 'bg-amber-50 text-amber-700 border-amber-200' :
                                            record.type === 'TXT' ? 'bg-purple-50 text-purple-700 border-purple-200' :
                                            record.type === 'NS' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' :
                                            'bg-slate-100 text-slate-700 border-slate-200'
                                        ]"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono"
                                    >
                                        {{ record.type }}
                                    </span>
                                </td>

                                <!-- Content -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono text-[11px] text-slate-800 max-w-md truncate" :title="record.content">
                                    {{ record.content }}
                                </td>

                                <!-- TTL -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[10.5px] text-slate-500">
                                    {{ record.ttl || 3600 }}
                                </td>

                                <!-- Priority -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[10.5px] text-slate-500">
                                    {{ record.priority ?? '-' }}
                                </td>

                                <!-- Status -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="record.status === 'active' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-600 border-slate-200'"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono"
                                    >
                                        {{ record.status || 'active' }}
                                    </span>
                                </td>

                                <!-- Action -->
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1">
                                        <button 
                                            type="button" 
                                            @click="openEditModal(record)"
                                            class="px-2 py-1 bg-white hover:bg-slate-50 text-blue-700 font-bold rounded-[3px] text-xs border border-slate-200 shadow-2xs transition cursor-pointer"
                                            title="Edit Record"
                                        >
                                            Edit ✏️
                                        </button>

                                        <RowActionDropdown>
                                            <button
                                                type="button"
                                                @click="copyContent(record)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <DocumentDuplicateIcon class="w-3.5 h-3.5 text-slate-500" />
                                                <span>Copy Value Content</span>
                                            </button>

                                            <button
                                                type="button"
                                                @click="toggleStatus(record)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <BoltIcon class="w-3.5 h-3.5 text-slate-500" />
                                                <span>{{ record.status === 'active' ? 'Disable Record' : 'Enable Record' }}</span>
                                            </button>

                                            <button
                                                type="button"
                                                @click="openDeleteModal(record)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-rose-600 hover:bg-rose-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <TrashIcon class="w-3.5 h-3.5" />
                                                <span>Delete Record</span>
                                            </button>
                                        </RowActionDropdown>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!filteredRecords || filteredRecords.length === 0">
                                <td colspan="8" class="py-12 text-center text-slate-400">
                                    No DNS resource records found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 1. ADD / EDIT RECORD MODAL -->
        <div v-if="showRecordModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-lg overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <AdjustmentsHorizontalIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            {{ isEditing ? 'Edit DNS Record' : 'Add DNS Resource Record' }}
                        </h3>
                    </div>
                    <button @click="showRecordModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitRecord" class="p-4 space-y-3 text-xs">
                    <div class="grid grid-cols-3 gap-3">
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Type <span class="text-rose-500">*</span></label>
                            <select v-model="recordForm.type" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-bold focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                                <option value="A">A (IPv4)</option>
                                <option value="AAAA">AAAA (IPv6)</option>
                                <option value="CNAME">CNAME (Alias)</option>
                                <option value="MX">MX (Mail)</option>
                                <option value="TXT">TXT (Text/SPF)</option>
                                <option value="NS">NS (Nameserver)</option>
                                <option value="SRV">SRV (Service)</option>
                                <option value="CAA">CAA (Cert Auth)</option>
                                <option value="PTR">PTR (Reverse)</option>
                            </select>
                        </div>

                        <div class="space-y-1 col-span-2">
                            <label class="block font-bold text-slate-700">Name / Host <span class="text-rose-500">*</span></label>
                            <input v-model="recordForm.name" type="text" required placeholder="@ or www or mail" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Value / Target Content <span class="text-rose-500">*</span></label>
                        <textarea v-model="recordForm.content" rows="3" required placeholder="IP address, FQDN hostname, or TXT string..." class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"></textarea>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">TTL (Seconds)</label>
                            <input v-model.number="recordForm.ttl" type="number" min="60" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                        </div>

                        <div v-if="recordForm.type === 'MX' || recordForm.type === 'SRV'" class="space-y-1">
                            <label class="block font-bold text-slate-700">Priority</label>
                            <input v-model.number="recordForm.priority" type="number" min="0" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                        </div>

                        <div v-if="recordForm.type === 'SRV'" class="space-y-1">
                            <label class="block font-bold text-slate-700">Port</label>
                            <input v-model.number="recordForm.port" type="number" min="1" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                        </div>
                    </div>

                    <div class="px-4 py-2.5 -mx-4 -mb-4 mt-4 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end gap-2 text-xs">
                        <button
                            type="button"
                            @click="showRecordModal = false"
                            class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="recordForm.processing"
                            class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                        >
                            {{ recordForm.processing ? 'Saving...' : (isEditing ? 'Save Changes' : 'Add Record') }}
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
                            Delete Resource Record
                        </h3>
                    </div>
                    <button @click="showDeleteModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-2.5 text-xs">
                    <p class="text-slate-600">
                        Are you sure you want to delete record <strong class="text-slate-900 font-mono">[{{ recordToDelete?.name }} ({{ recordToDelete?.type }})]</strong>?
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
                        {{ deleteForm.processing ? 'Deleting...' : 'Delete' }}
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
