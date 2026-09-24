<script setup>
import { ref, computed } from 'vue'
import { Head, useForm, router, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import { 
    GlobeAltIcon, 
    PlusIcon, 
    PencilSquareIcon, 
    TrashIcon, 
    ClipboardDocumentIcon,
    CheckIcon,
    ServerIcon,
    ShieldCheckIcon,
    ArrowPathIcon,
    XMarkIcon,
    ExclamationTriangleIcon,
    InformationCircleIcon,
    MagnifyingGlassIcon,
    ArrowUturnLeftIcon,
    ArrowsRightLeftIcon,
    CheckCircleIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    zones: {
        type: Array,
        default: () => []
    },
    activeZone: {
        type: Object,
        default: () => ({
            id: 1,
            domain: 'somitysoft.com',
            primary_ns: 'ns1.deeptouchit.com',
            secondary_ns: 'ns2.deeptouchit.com',
            dnssec_enabled: false,
            serial: 2026090301,
            ttl: 14400,
            status: 'active'
        })
    },
    records: {
        type: Array,
        default: () => []
    },
    serverIp: {
        type: String,
        default: '103.59.177.100'
    },
    defaultNameservers: {
        type: Array,
        default: () => ['ns1.deeptouchit.com', 'ns2.deeptouchit.com']
    }
})

// Search & Type Filter
const searchQuery = ref('')
const selectedTypeFilter = ref('ALL')

const typeFilters = ['ALL', 'A', 'AAAA', 'CNAME', 'MX', 'TXT', 'NS', 'SRV', 'CAA']

const filteredRecords = computed(() => {
    return props.records.filter(r => {
        const matchesType = selectedTypeFilter.value === 'ALL' || r.type === selectedTypeFilter.value
        const q = searchQuery.value.trim().toLowerCase()
        const matchesSearch = !q || 
            r.name.toLowerCase().includes(q) || 
            r.content.toLowerCase().includes(q) ||
            r.type.toLowerCase().includes(q)
        return matchesType && matchesSearch
    })
})

// Modals
const showAddModal = ref(false)
const showEditModal = ref(false)
const showDeleteModal = ref(false)
const showResetModal = ref(false)

const editingRecord = ref(null)
const deletingRecord = ref(null)

// Copy helper
const copiedField = ref(null)
const copyToClipboard = (text, fieldName) => {
    navigator.clipboard.writeText(text)
    copiedField.value = fieldName
    setTimeout(() => {
        copiedField.value = null
    }, 2000)
}

// Domain / Zone Switcher
const onZoneChange = (event) => {
    const zoneId = event.target.value
    router.get(route('advanced.dns'), { zone_id: zoneId }, {
        preserveScroll: true,
        preserveState: true,
    })
}

// Record Form (Add / Edit)
const recordForm = useForm({
    dns_zone_id: props.activeZone?.id || '',
    type: 'A',
    name: '@',
    content: '',
    ttl: 14400,
    priority: null,
    weight: null,
    port: null,
})

const openAddModal = () => {
    recordForm.reset({
        dns_zone_id: props.activeZone?.id || '',
        type: 'A',
        name: '@',
        content: props.serverIp || '',
        ttl: 14400,
        priority: null,
        weight: null,
        port: null,
    })
    showAddModal.value = true
}

const submitAddRecord = () => {
    recordForm.dns_zone_id = props.activeZone?.id
    recordForm.post(route('advanced.dns.add-record'), {
        preserveScroll: true,
        onSuccess: () => {
            showAddModal.value = false
            recordForm.reset()
        }
    })
}

const openEditModal = (r) => {
    editingRecord.value = r
    recordForm.type = r.type
    recordForm.name = r.name
    recordForm.content = r.content
    recordForm.ttl = r.ttl
    recordForm.priority = r.priority
    recordForm.weight = r.weight
    recordForm.port = r.port
    showEditModal.value = true
}

const submitEditRecord = () => {
    if (!editingRecord.value) return
    recordForm.put(route('advanced.dns.update-record', editingRecord.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showEditModal.value = false
            editingRecord.value = null
        }
    })
}

const confirmDelete = (r) => {
    deletingRecord.value = r
    showDeleteModal.value = true
}

const executeDelete = () => {
    if (!deletingRecord.value) return
    router.delete(route('advanced.dns.delete-record', deletingRecord.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false
            deletingRecord.value = null
        }
    })
}

const executeReset = () => {
    router.post(route('advanced.dns.reset-defaults'), {
        dns_zone_id: props.activeZone?.id
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showResetModal.value = false
        }
    })
}

const toggleDnssec = () => {
    router.post(route('advanced.dns.dnssec-toggle'), {
        dns_zone_id: props.activeZone?.id
    }, {
        preserveScroll: true,
    })
}

// Color badges helper
const getTypeBadgeClass = (type) => {
    switch(type) {
        case 'A': return 'bg-blue-50 text-blue-700 border-blue-200'
        case 'AAAA': return 'bg-indigo-50 text-indigo-700 border-indigo-200'
        case 'CNAME': return 'bg-purple-50 text-purple-700 border-purple-200'
        case 'MX': return 'bg-amber-50 text-amber-700 border-amber-200'
        case 'TXT': return 'bg-emerald-50 text-emerald-700 border-emerald-200'
        case 'NS': return 'bg-slate-100 text-slate-700 border-slate-300'
        default: return 'bg-slate-50 text-slate-600 border-slate-200'
    }
}
</script>

<template>
    <Head title="DNS Zone Editor - DeepTouch Host" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto space-y-4">
            
            <!-- 1. Page Header with Breadcrumbs & Actions -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Advanced Tools', href: '#' },
                    { label: 'DNS Zone Editor' }
                ]"
                :show-refresh="true"
                @refreshed="router.reload({ preserveScroll: true })"
            >
                <template #actions>
                    <div v-if="zones.length > 0" class="flex items-center gap-2">
                        <span class="text-xs font-bold text-slate-500">Zone:</span>
                        <select 
                            :value="activeZone?.id"
                            @change="onZoneChange"
                            class="bg-white border border-slate-300 rounded-[3px] text-xs font-bold text-slate-800 py-1.5 pl-2.5 pr-8 focus:ring-1 focus:ring-blue-500 cursor-pointer shadow-2xs"
                        >
                            <option v-for="z in zones" :key="z.id" :value="z.id">
                                {{ z.domain }}
                            </option>
                        </select>
                    </div>

                    <button 
                        @click="showResetModal = true"
                        class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 rounded-[3px] text-xs font-bold flex items-center gap-1.5 transition cursor-pointer shadow-2xs"
                    >
                        <ArrowUturnLeftIcon class="w-3.5 h-3.5" />
                        <span>Reset Defaults</span>
                    </button>

                    <button 
                        @click="openAddModal"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <PlusIcon class="w-3.5 h-3.5 stroke-[2.5]" />
                        <span>Add Record</span>
                    </button>
                </template>
            </PageHeader>

            <!-- 2. Top 3 KPI / DNS Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                
                <!-- Card 1: Authoritative Nameservers -->
                <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-2xs space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <GlobeAltIcon class="w-4 h-4 text-blue-600" />
                            <span>Authoritative Nameservers</span>
                        </span>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200 font-mono">
                            ACTIVE
                        </span>
                    </div>

                    <div class="space-y-1.5 text-xs font-mono">
                        <div v-for="(ns, idx) in defaultNameservers" :key="ns" class="bg-slate-50 p-1.5 rounded border border-slate-200/70 flex items-center justify-between">
                            <span class="font-bold text-slate-900 truncate">{{ ns }}</span>
                            <button 
                                @click="copyToClipboard(ns, 'ns_' + idx)"
                                class="text-slate-400 hover:text-blue-600 cursor-pointer ml-1"
                                title="Copy Nameserver"
                            >
                                <CheckIcon v-if="copiedField === 'ns_' + idx" class="w-3.5 h-3.5 text-emerald-600" />
                                <ClipboardDocumentIcon v-else class="w-3.5 h-3.5" />
                            </button>
                        </div>
                    </div>

                    <div class="text-[10.5px] text-slate-500 pt-1 border-t border-slate-100 flex items-center gap-1">
                        <InformationCircleIcon class="w-3.5 h-3.5 text-blue-600 shrink-0" />
                        <span>Point your domain at your registrar to these NS</span>
                    </div>
                </div>

                <!-- Card 2: Zone Records & SOA Details -->
                <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-2xs space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <ServerIcon class="w-4 h-4 text-emerald-600" />
                            <span>Zone Configuration</span>
                        </span>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-200 font-mono">
                            TTL {{ activeZone?.ttl || 14400 }}s
                        </span>
                    </div>

                    <div>
                        <div class="flex items-baseline gap-2">
                            <span class="text-2xl font-black text-slate-900 font-mono">{{ records.length }}</span>
                            <span class="text-xs text-slate-400 font-bold font-mono">DNS Records Active</span>
                        </div>
                        <p class="text-[11px] text-slate-500 mt-1 font-mono">
                            SOA Serial: <strong class="text-slate-800">{{ activeZone?.serial || 2026090301 }}</strong>
                        </p>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1 border-t border-slate-100 font-mono">
                        <span>Target IP:</span>
                        <strong class="text-slate-700">{{ serverIp }}</strong>
                    </div>
                </div>

                <!-- Card 3: DNSSEC Security & Propagation -->
                <div class="bg-gradient-to-br from-indigo-50/70 to-blue-50/50 border border-indigo-100 rounded-lg p-4 shadow-2xs flex flex-col justify-between space-y-2.5">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-indigo-900 flex items-center gap-1.5">
                                <ShieldCheckIcon class="w-4 h-4 text-indigo-600" />
                                <span>DNSSEC Protection</span>
                            </span>
                            <button 
                                @click="toggleDnssec"
                                class="text-[10px] font-bold px-2 py-0.5 rounded font-mono uppercase transition cursor-pointer border"
                                :class="activeZone?.dnssec_enabled ? 'bg-emerald-100 text-emerald-800 border-emerald-300' : 'bg-slate-200 text-slate-600 border-slate-300'"
                            >
                                {{ activeZone?.dnssec_enabled ? 'ENABLED' : 'DISABLED' }}
                            </button>
                        </div>
                        <p class="text-[11px] text-slate-600 mt-1 line-clamp-2">
                            Cryptographic signature authentication prevents DNS spoofing and cache poisoning attacks.
                        </p>
                    </div>

                    <div class="pt-2 border-t border-indigo-100 flex items-center justify-between text-[11px] text-indigo-900">
                        <span>Propagation Speed:</span>
                        <span class="font-bold font-mono">Anycast Cloud Network</span>
                    </div>
                </div>

            </div>

            <!-- 3. DNS Records Management Table -->
            <div class="bg-white border border-slate-200 rounded-lg shadow-2xs overflow-hidden">
                
                <!-- Table Header & Controls -->
                <div class="p-3 bg-slate-50/80 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    
                    <!-- Filter Chips -->
                    <div class="flex items-center gap-1 overflow-x-auto pb-1 sm:pb-0">
                        <button 
                            v-for="t in typeFilters" 
                            :key="t"
                            @click="selectedTypeFilter = t"
                            class="px-2.5 py-1 rounded-[3px] text-xs font-bold transition cursor-pointer font-mono"
                            :class="selectedTypeFilter === t ? 'bg-blue-600 text-white shadow-2xs' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-100'"
                        >
                            {{ t }}
                        </button>
                    </div>

                    <!-- Search Input -->
                    <div class="relative w-full sm:w-64">
                        <MagnifyingGlassIcon class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2" />
                        <input 
                            v-model="searchQuery"
                            type="text" 
                            placeholder="Filter records (name, value)..." 
                            class="w-full pl-8 pr-2.5 py-1.5 bg-white border border-slate-300 rounded-[3px] text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-blue-500 font-medium shadow-2xs"
                        />
                    </div>

                </div>

                <!-- Empty State -->
                <div v-if="filteredRecords.length === 0" class="p-12 text-center text-slate-500 text-xs">
                    <GlobeAltIcon class="w-12 h-12 text-slate-300 mx-auto mb-2" />
                    <h4 class="text-sm font-bold text-slate-900 mb-1">
                        {{ searchQuery || selectedTypeFilter !== 'ALL' ? 'No records match your filter' : 'No DNS Records Configured' }}
                    </h4>
                    <p class="text-[11px] text-slate-400 max-w-sm mx-auto mb-4">
                        {{ searchQuery || selectedTypeFilter !== 'ALL' ? 'Try clearing your search or filter to see all DNS entries.' : 'Add A, CNAME, MX, or TXT records to route web traffic and mail.' }}
                    </p>
                    <button 
                        v-if="!searchQuery && selectedTypeFilter === 'ALL'"
                        @click="openAddModal"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold shadow-2xs transition cursor-pointer"
                    >
                        Add First Record
                    </button>
                    <button 
                        v-else
                        @click="searchQuery = ''; selectedTypeFilter = 'ALL'"
                        class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-[3px] text-xs font-bold transition cursor-pointer"
                    >
                        Clear Filters
                    </button>
                </div>

                <!-- Table Content -->
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-700">
                        <thead class="bg-slate-50/90 border-b border-slate-200 text-slate-500 uppercase font-bold tracking-wider text-[10.5px] select-none">
                            <tr>
                                <th class="py-2.5 px-4 w-24">Type</th>
                                <th class="py-2.5 px-4 w-52">Name / Host</th>
                                <th class="py-2.5 px-4">Value / Points To</th>
                                <th class="py-2.5 px-4 w-24">TTL</th>
                                <th class="py-2.5 px-4 w-24">Priority</th>
                                <th class="py-2.5 px-4 w-28 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium">
                            <tr v-for="rec in filteredRecords" :key="rec.id" class="hover:bg-slate-50/80 transition-colors">
                                
                                <!-- Record Type -->
                                <td class="py-3 px-4 font-mono">
                                    <span 
                                        class="px-2 py-0.5 rounded-[3px] font-bold border text-[11px]"
                                        :class="getTypeBadgeClass(rec.type)"
                                    >
                                        {{ rec.type }}
                                    </span>
                                </td>

                                <!-- Name / Host -->
                                <td class="py-3 px-4 font-bold text-slate-900 font-mono text-[11.5px]">
                                    {{ rec.name }}
                                </td>

                                <!-- Value / Content -->
                                <td class="py-3 px-4 font-mono text-[11px] text-slate-700">
                                    <div class="flex items-center gap-1.5 max-w-xl truncate">
                                        <span class="truncate">{{ rec.content }}</span>
                                        <button 
                                            @click="copyToClipboard(rec.content, 'rec_' + rec.id)"
                                            class="text-slate-400 hover:text-blue-600 cursor-pointer shrink-0"
                                            title="Copy Value"
                                        >
                                            <CheckIcon v-if="copiedField === 'rec_' + rec.id" class="w-3.5 h-3.5 text-emerald-600" />
                                            <ClipboardDocumentIcon v-else class="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                </td>

                                <!-- TTL -->
                                <td class="py-3 px-4 font-mono text-slate-500 text-[11px]">
                                    {{ rec.ttl }}s
                                </td>

                                <!-- Priority -->
                                <td class="py-3 px-4 font-mono text-[11px]">
                                    <span v-if="rec.priority !== null" class="font-bold text-amber-700 bg-amber-50 px-1.5 py-0.2 rounded border border-amber-200">
                                        {{ rec.priority }}
                                    </span>
                                    <span v-else class="text-slate-300">-</span>
                                </td>

                                <!-- Actions -->
                                <td class="py-3 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        
                                        <!-- Edit Record -->
                                        <button 
                                            @click="openEditModal(rec)"
                                            title="Edit Record"
                                            class="p-1.5 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-[3px] shadow-2xs transition cursor-pointer"
                                        >
                                            <PencilSquareIcon class="w-3.5 h-3.5 text-blue-600" />
                                        </button>

                                        <!-- Delete Record -->
                                        <button 
                                            @click="confirmDelete(rec)"
                                            title="Delete Record"
                                            class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded transition cursor-pointer"
                                        >
                                            <TrashIcon class="w-4 h-4" />
                                        </button>

                                    </div>
                                </td>

                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="px-4 py-2.5 bg-slate-50 border-t border-slate-200 text-[11px] text-slate-500 flex items-center justify-between">
                    <span>PowerDNS & BIND9 Authoritative Nameserver.</span>
                    <span class="font-mono">Zone: {{ activeZone?.domain }}</span>
                </div>

            </div>

        </div>

        <!-- ========================================================= -->
        <!-- MODALS                                                    -->
        <!-- ========================================================= -->
        <Teleport to="body">
            
            <!-- 1. Add / Edit DNS Record Modal -->
            <div v-if="showAddModal || showEditModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4 z-50">
                <div class="bg-white rounded-lg max-w-lg w-full p-5 shadow-2xl space-y-4 border border-slate-200">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-2">
                            <GlobeAltIcon class="w-5 h-5 text-blue-600" />
                            <h3 class="font-bold text-slate-900 text-sm">
                                {{ showEditModal ? 'Edit DNS Record' : 'Add New DNS Record' }}
                            </h3>
                        </div>
                        <button @click="showAddModal = false; showEditModal = false" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                            <XMarkIcon class="w-4 h-4" />
                        </button>
                    </div>

                    <form @submit.prevent="showEditModal ? submitEditRecord() : submitAddRecord()" class="space-y-3.5 text-xs">
                        
                        <!-- Record Type & Name Grid -->
                        <div class="grid grid-cols-3 gap-3">
                            <div class="space-y-1">
                                <label class="block font-bold text-slate-700">Type</label>
                                <select 
                                    v-model="recordForm.type" 
                                    required 
                                    class="w-full bg-white border border-slate-300 rounded-[3px] px-2.5 py-2 text-xs font-mono font-bold text-slate-900 focus:ring-1 focus:ring-blue-600 cursor-pointer"
                                >
                                    <option value="A">A (IPv4)</option>
                                    <option value="AAAA">AAAA (IPv6)</option>
                                    <option value="CNAME">CNAME (Alias)</option>
                                    <option value="MX">MX (Mail Exchanger)</option>
                                    <option value="TXT">TXT (Text / SPF / DKIM)</option>
                                    <option value="NS">NS (Nameserver)</option>
                                    <option value="SRV">SRV (Service)</option>
                                    <option value="CAA">CAA (Cert Authority)</option>
                                </select>
                            </div>

                            <div class="col-span-2 space-y-1">
                                <label class="block font-bold text-slate-700">Name / Host</label>
                                <input 
                                    v-model="recordForm.name" 
                                    type="text" 
                                    required 
                                    placeholder="@ (root) or subdomain" 
                                    class="w-full bg-white border border-slate-300 rounded-[3px] px-2.5 py-2 text-xs font-mono font-bold text-slate-900 focus:ring-1 focus:ring-blue-600" 
                                />
                            </div>
                        </div>

                        <!-- Content / Value -->
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Value / Target Content</label>
                            <input 
                                v-if="recordForm.type !== 'TXT'"
                                v-model="recordForm.content" 
                                type="text" 
                                required 
                                placeholder="IP address, hostname, or target value" 
                                class="w-full bg-white border border-slate-300 rounded-[3px] px-2.5 py-2 text-xs font-mono font-bold text-slate-900 focus:ring-1 focus:ring-blue-600" 
                            />
                            <textarea 
                                v-else
                                v-model="recordForm.content"
                                rows="3"
                                required
                                placeholder="v=spf1 a mx ip4:... or public key content"
                                class="w-full bg-slate-50 border border-slate-300 rounded-[3px] p-2.5 text-xs font-mono text-slate-900 focus:ring-1 focus:ring-blue-600 outline-none leading-relaxed"
                            ></textarea>
                        </div>

                        <!-- Additional Parameters: TTL, Priority, Weight, Port -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                            <div class="space-y-1">
                                <label class="block font-bold text-slate-700 font-mono text-[11px]">TTL (Seconds)</label>
                                <select 
                                    v-model.number="recordForm.ttl"
                                    class="w-full bg-white border border-slate-300 rounded-[3px] px-2 py-1.5 text-xs font-mono text-slate-900 focus:ring-1 focus:ring-blue-600"
                                >
                                    <option :value="300">300 (5 min)</option>
                                    <option :value="3600">3600 (1 hour)</option>
                                    <option :value="14400">14400 (4 hours)</option>
                                    <option :value="86400">86400 (1 day)</option>
                                </select>
                            </div>

                            <div v-if="recordForm.type === 'MX' || recordForm.type === 'SRV'" class="space-y-1">
                                <label class="block font-bold text-slate-700 font-mono text-[11px]">Priority</label>
                                <input 
                                    v-model.number="recordForm.priority" 
                                    type="number" 
                                    min="0"
                                    max="65535"
                                    placeholder="e.g. 10" 
                                    class="w-full bg-white border border-slate-300 rounded-[3px] px-2 py-1.5 text-xs font-mono text-slate-900 focus:ring-1 focus:ring-blue-600" 
                                />
                            </div>

                            <div v-if="recordForm.type === 'SRV'" class="space-y-1">
                                <label class="block font-bold text-slate-700 font-mono text-[11px]">Weight</label>
                                <input 
                                    v-model.number="recordForm.weight" 
                                    type="number" 
                                    placeholder="e.g. 5" 
                                    class="w-full bg-white border border-slate-300 rounded-[3px] px-2 py-1.5 text-xs font-mono text-slate-900 focus:ring-1 focus:ring-blue-600" 
                                />
                            </div>

                            <div v-if="recordForm.type === 'SRV'" class="space-y-1">
                                <label class="block font-bold text-slate-700 font-mono text-[11px]">Port</label>
                                <input 
                                    v-model.number="recordForm.port" 
                                    type="number" 
                                    placeholder="e.g. 5060" 
                                    class="w-full bg-white border border-slate-300 rounded-[3px] px-2 py-1.5 text-xs font-mono text-slate-900 focus:ring-1 focus:ring-blue-600" 
                                />
                            </div>
                        </div>

                        <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                            <button 
                                type="button" 
                                @click="showAddModal = false; showEditModal = false" 
                                class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-[3px] text-xs font-bold cursor-pointer"
                            >
                                Cancel
                            </button>
                            <button 
                                type="submit" 
                                :disabled="recordForm.processing"
                                class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold shadow-2xs transition cursor-pointer disabled:opacity-50 flex items-center gap-1"
                            >
                                <span>{{ recordForm.processing ? 'Saving...' : (showEditModal ? 'Update Record' : 'Create Record') }}</span>
                            </button>
                        </div>

                    </form>
                </div>
            </div>

            <!-- 2. Delete Confirmation Modal -->
            <div v-if="showDeleteModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4 z-50">
                <div class="bg-white rounded-lg max-w-md w-full p-5 shadow-2xl border border-slate-200 space-y-4">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-lg bg-rose-50 border border-rose-100 text-rose-600 flex items-center justify-center shrink-0">
                            <TrashIcon class="w-5 h-5 stroke-[1.75]" />
                        </div>
                        <div class="flex-1">
                            <h3 class="text-sm font-black text-slate-900">Delete DNS Record?</h3>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Are you sure you want to delete <strong class="text-slate-900 font-mono">{{ deletingRecord?.type }}</strong> record for <strong class="text-rose-600 font-mono">{{ deletingRecord?.name }}</strong>? Traffic routing using this record will stop immediately.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                        <button 
                            @click="showDeleteModal = false; deletingRecord = null" 
                            type="button" 
                            class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-[3px] text-xs font-bold transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button 
                            @click="executeDelete" 
                            type="button"
                            class="px-4 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-[3px] text-xs font-bold shadow-2xs transition cursor-pointer flex items-center gap-1.5"
                        >
                            <TrashIcon class="w-3.5 h-3.5" />
                            <span>Delete Record</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- 3. Reset Defaults Modal -->
            <div v-if="showResetModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4 z-50">
                <div class="bg-white rounded-lg max-w-md w-full p-5 shadow-2xl border border-slate-200 space-y-4">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-lg bg-amber-50 border border-amber-100 text-amber-600 flex items-center justify-center shrink-0">
                            <ExclamationTriangleIcon class="w-5 h-5 stroke-[1.75]" />
                        </div>
                        <div class="flex-1">
                            <h3 class="text-sm font-black text-slate-900">Reset DNS Zone to Defaults?</h3>
                            <p class="text-xs text-slate-500 mt-0.5">
                                This will replace all existing custom DNS records for <strong class="text-slate-900 font-mono">{{ activeZone?.domain }}</strong> with standard Web and Email hosting default records.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                        <button 
                            @click="showResetModal = false" 
                            type="button" 
                            class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-[3px] text-xs font-bold transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button 
                            @click="executeReset" 
                            type="button"
                            class="px-4 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-[3px] text-xs font-bold shadow-2xs transition cursor-pointer flex items-center gap-1.5"
                        >
                            <ArrowUturnLeftIcon class="w-3.5 h-3.5" />
                            <span>Reset to Defaults</span>
                        </button>
                    </div>
                </div>
            </div>

        </Teleport>

    </AuthenticatedLayout>
</template>
