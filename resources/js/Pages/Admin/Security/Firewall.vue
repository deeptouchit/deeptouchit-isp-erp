<script setup>
import { ref, computed } from 'vue'
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
    ShieldCheckIcon,
    ShieldExclamationIcon,
    PlusIcon,
    PencilSquareIcon,
    TrashIcon,
    ArrowPathIcon,
    XMarkIcon,
    CheckIcon,
    LockClosedIcon,
    BoltIcon,
    ServerIcon,
    SignalIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    rules: {
        type: Array,
        default: () => [],
    },
    listeningPorts: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({
            is_active: true,
            status_text: 'Active & Protecting',
            default_incoming: 'deny',
            default_outgoing: 'allow',
            total_rules: 0,
            allowed_count: 0,
            blocked_count: 0,
            listening_services: 0,
        }),
    },
    filters: {
        type: Object,
        default: () => ({ search: '', action: 'all' }),
    },
})

// Search & Filter State
const search = ref(props.filters.search || '')
const actionFilter = ref(props.filters.action || 'all')
const feedbackMsg = ref('')

const filteredRules = computed(() => {
    let list = props.rules || []

    if (search.value.trim()) {
        const q = search.value.toLowerCase()
        list = list.filter(r =>
            (r.label || '').toLowerCase().includes(q) ||
            String(r.port).toLowerCase().includes(q) ||
            (r.from_ip || '').toLowerCase().includes(q) ||
            (r.protocol || '').toLowerCase().includes(q)
        )
    }

    if (actionFilter.value !== 'all') {
        list = list.filter(r => r.action === actionFilter.value)
    }

    return list
})

const resetFilters = () => {
    search.value = ''
    actionFilter.value = 'all'
}

// 1. ADD / EDIT CUSTOM RULE MODAL
const showAddModal = ref(false)
const editingRule = ref(null)
const ruleForm = useForm({
    label: '',
    port: '',
    protocol: 'tcp',
    action: 'allow',
    from_ip: 'Anywhere',
})

const openAddModal = () => {
    editingRule.value = null
    ruleForm.reset()
    ruleForm.protocol = 'tcp'
    ruleForm.action = 'allow'
    ruleForm.from_ip = 'Anywhere'
    showAddModal.value = true
}

const openEditModal = (rule) => {
    editingRule.value = rule
    ruleForm.label = rule.label
    ruleForm.port = rule.port
    ruleForm.protocol = rule.protocol
    ruleForm.action = rule.action
    ruleForm.from_ip = rule.from_ip
    showAddModal.value = true
}

const submitRule = () => {
    if (editingRule.value) {
        ruleForm.put(route('admin.security.firewall.update', editingRule.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                showAddModal.value = false
                feedbackMsg.value = `Firewall rule updated.`
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            }
        })
    } else {
        ruleForm.post(route('admin.security.firewall.store'), {
            preserveScroll: true,
            onSuccess: () => {
                showAddModal.value = false
                feedbackMsg.value = `Firewall rule for port ${ruleForm.port} (${ruleForm.action.toUpperCase()}) added.`
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            }
        })
    }
}

// 2. TOGGLE MASTER FIREWALL
const isTogglingMaster = ref(false)
const toggleMasterFirewall = () => {
    isTogglingMaster.value = true
    router.post(route('admin.security.firewall.toggle-master'), {}, {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = `UFW Master Firewall state toggled.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        },
        onFinish: () => {
            isTogglingMaster.value = false
        }
    })
}

// 3. APPLY QUICK PRESET
const applyPreset = (presetName) => {
    router.post(route('admin.security.firewall.apply-preset'), { preset: presetName }, {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = `Preset rule for ${presetName} configured.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 4. SYNC SYSTEM UFW RULES
const isSyncing = ref(false)
const syncRules = () => {
    isSyncing.value = true
    router.post(route('admin.security.firewall.sync'), {}, {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = `iptables and UFW rules synchronized with database.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        },
        onFinish: () => {
            isSyncing.value = false
        }
    })
}

// 5. DELETE RULE
const showDeleteModal = ref(false)
const ruleToDelete = ref(null)
const deleteForm = useForm({})

const openDeleteModal = (rule) => {
    ruleToDelete.value = rule
    showDeleteModal.value = true
}

const submitDelete = () => {
    if (!ruleToDelete.value) return
    deleteForm.delete(route('admin.security.firewall.destroy', ruleToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false
            feedbackMsg.value = `Firewall rule removed.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}
</script>

<template>
    <Head title="Firewall & WAF Rules - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Security', href: '#' },
                    { label: 'Security & Data Protection', href: route('admin.security.ssl') },
                    { label: 'Firewall & WAF Rules' }
                ]"
            >
                <template #actions>
                    <button
                        type="button"
                        @click="syncRules"
                        :disabled="isSyncing"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer disabled:opacity-50"
                    >
                        <ArrowPathIcon class="w-3.5 h-3.5 text-slate-500" :class="{ 'animate-spin': isSyncing }" />
                        <span>Sync UFW</span>
                    </button>

                    <button
                        type="button"
                        @click="toggleMasterFirewall"
                        :disabled="isTogglingMaster"
                        :class="stats.is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-300' : 'bg-rose-50 text-rose-700 border-rose-300'"
                        class="px-2.5 py-1.5 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border shadow-2xs transition cursor-pointer disabled:opacity-50"
                    >
                        <ShieldCheckIcon class="w-3.5 h-3.5" />
                        <span>{{ stats.is_active ? 'Firewall: Active' : 'Firewall: Disabled' }}</span>
                    </button>

                    <button 
                        type="button" 
                        @click="openAddModal"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <PlusIcon class="w-3.5 h-3.5" />
                        <span>Add Custom Rule</span>
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
                    title="UFW Master Status"
                    :value="stats.status_text || 'Active & Protecting'"
                    :badge="stats.is_active ? 'Online' : 'Disabled'"
                    :badgeType="stats.is_active ? 'success' : 'danger'"
                    color="emerald"
                    :icon="ShieldCheckIcon"
                />

                <InfoCard
                    title="Allowed Inbound"
                    :value="String(stats.allowed_count || 0)"
                    badge="Open Ports"
                    badgeType="info"
                    color="blue"
                    :icon="BoltIcon"
                />

                <InfoCard
                    title="Blocked Filter Rules"
                    :value="String(stats.blocked_count || 0)"
                    badge="Deny / Drop"
                    badgeType="warning"
                    color="purple"
                    :icon="LockClosedIcon"
                />

                <InfoCard
                    title="Listening Daemons"
                    :value="String(stats.listening_services || listeningPorts.length || 0)"
                    badge="Services"
                    badgeType="info"
                    color="sky"
                    :icon="SignalIcon"
                />
            </InfoCardsGrid>

            <!-- 3. 1-Click Quick Service Presets Toolbar -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] p-2.5 shadow-2xs flex flex-wrap items-center justify-between gap-2 text-xs">
                <span class="font-bold text-slate-700">1-Click Presets:</span>
                <div class="flex flex-wrap items-center gap-1.5">
                    <button type="button" @click="applyPreset('HTTP')" class="px-2 py-0.8 bg-slate-50 hover:bg-slate-100 text-slate-700 font-bold border border-slate-200 rounded-[2px] cursor-pointer">HTTP (80)</button>
                    <button type="button" @click="applyPreset('HTTPS')" class="px-2 py-0.8 bg-slate-50 hover:bg-slate-100 text-slate-700 font-bold border border-slate-200 rounded-[2px] cursor-pointer">HTTPS (443)</button>
                    <button type="button" @click="applyPreset('SSH')" class="px-2 py-0.8 bg-slate-50 hover:bg-slate-100 text-slate-700 font-bold border border-slate-200 rounded-[2px] cursor-pointer">SSH (22)</button>
                    <button type="button" @click="applyPreset('MySQL')" class="px-2 py-0.8 bg-slate-50 hover:bg-slate-100 text-slate-700 font-bold border border-slate-200 rounded-[2px] cursor-pointer">MySQL (3306)</button>
                    <button type="button" @click="applyPreset('Postgres')" class="px-2 py-0.8 bg-slate-50 hover:bg-slate-100 text-slate-700 font-bold border border-slate-200 rounded-[2px] cursor-pointer">Postgres (5432)</button>
                    <button type="button" @click="applyPreset('FTP')" class="px-2 py-0.8 bg-slate-50 hover:bg-slate-100 text-slate-700 font-bold border border-slate-200 rounded-[2px] cursor-pointer">FTP (21)</button>
                    <button type="button" @click="applyPreset('DNS')" class="px-2 py-0.8 bg-slate-50 hover:bg-slate-100 text-slate-700 font-bold border border-slate-200 rounded-[2px] cursor-pointer">DNS (53)</button>
                    <button type="button" @click="applyPreset('SMTP')" class="px-2 py-0.8 bg-slate-50 hover:bg-slate-100 text-slate-700 font-bold border border-slate-200 rounded-[2px] cursor-pointer">SMTP (25/587)</button>
                </div>
            </div>

            <!-- 4. Standard Filter Toolbar -->
            <DataTableFilter
                v-model="search"
                searchPlaceholder="Search port, protocol, service label, or source IP..."
                @search="() => {}"
                @filter="() => {}"
                @reset="resetFilters"
            >
                <FilterSelect
                    v-model="actionFilter"
                    label="Filter Action"
                    :options="[
                        { label: 'All Actions', value: 'all' },
                        { label: 'Allow Traffic', value: 'allow' },
                        { label: 'Deny / Drop', value: 'deny' },
                        { label: 'Rate Limited', value: 'limit' }
                    ]"
                    placeholder="All Actions"
                />
            </DataTableFilter>

            <!-- 5. Standard Data Table -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Service / Rule Label</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 w-24">Port / Range</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 w-20">Protocol</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Source IP / Subnet</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Rule Action</th>
                                <th class="py-2.5 px-3 w-28">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(rule, index) in filteredRules" :key="rule.id || index" class="hover:bg-blue-50/30 transition">
                                <!-- # -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>

                                <!-- Label -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-bold text-slate-900">
                                    <div class="flex items-center gap-2">
                                        <div 
                                            :class="rule.action === 'allow' ? 'bg-emerald-50 text-emerald-600 border-emerald-100' : 'bg-rose-50 text-rose-600 border-rose-100'"
                                            class="w-6 h-6 rounded-[3px] flex items-center justify-center font-bold text-xs border shrink-0"
                                        >
                                            <BoltIcon v-if="rule.action === 'allow'" class="w-3.5 h-3.5" />
                                            <LockClosedIcon v-else class="w-3.5 h-3.5" />
                                        </div>
                                        <span>{{ rule.label }}</span>
                                    </div>
                                </td>

                                <!-- Port -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono font-bold text-blue-700 text-[11px]">
                                    {{ rule.port }}
                                </td>

                                <!-- Protocol -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono uppercase text-[10.5px] text-slate-600">
                                    {{ rule.protocol }}
                                </td>

                                <!-- From IP -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono text-[11px] text-slate-700">
                                    {{ rule.from_ip || 'Anywhere' }}
                                </td>

                                <!-- Action -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="[
                                            rule.action === 'allow' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' :
                                            rule.action === 'limit' ? 'bg-amber-50 text-amber-700 border-amber-200' :
                                            'bg-rose-50 text-rose-700 border-rose-200'
                                        ]"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono"
                                    >
                                        {{ rule.action }}
                                    </span>
                                </td>

                                <!-- Action -->
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1">
                                        <button 
                                            type="button" 
                                            @click="openEditModal(rule)"
                                            class="px-2 py-1 bg-white hover:bg-slate-50 text-blue-700 font-bold rounded-[3px] text-xs border border-slate-200 shadow-2xs transition cursor-pointer"
                                            title="Edit Rule"
                                        >
                                            Edit ✏️
                                        </button>

                                        <RowActionDropdown>
                                            <button
                                                type="button"
                                                @click="openDeleteModal(rule)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-rose-600 hover:bg-rose-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <TrashIcon class="w-3.5 h-3.5" />
                                                <span>Delete Rule</span>
                                            </button>
                                        </RowActionDropdown>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!filteredRules || filteredRules.length === 0">
                                <td colspan="7" class="py-12 text-center text-slate-400">
                                    No firewall rules found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 1. ADD / EDIT RULE MODAL -->
        <div v-if="showAddModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <PencilSquareIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            {{ editingRule ? 'Edit Firewall Rule' : 'Add Firewall Rule' }}
                        </h3>
                    </div>
                    <button @click="showAddModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitRule" class="p-4 space-y-3 text-xs">
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Service Label <span class="text-rose-500">*</span></label>
                        <input v-model="ruleForm.label" type="text" required placeholder="e.g. Custom Node.js API" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Port / Range <span class="text-rose-500">*</span></label>
                            <input v-model="ruleForm.port" type="text" required placeholder="8080 or 8000:8010" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                        </div>

                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Protocol</label>
                            <select v-model="ruleForm.protocol" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                                <option value="tcp">TCP</option>
                                <option value="udp">UDP</option>
                                <option value="both">Both (TCP & UDP)</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Action Policy</label>
                            <select v-model="ruleForm.action" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                                <option value="allow">ALLOW</option>
                                <option value="deny">DENY (Drop)</option>
                                <option value="limit">LIMIT (Rate-Limit)</option>
                            </select>
                        </div>

                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Source IP</label>
                            <input v-model="ruleForm.from_ip" type="text" placeholder="Anywhere or IP/CIDR" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                        </div>
                    </div>

                    <div class="px-4 py-2.5 -mx-4 -mb-4 mt-4 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end gap-2 text-xs">
                        <button
                            type="button"
                            @click="showAddModal = false"
                            class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="ruleForm.processing"
                            class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                        >
                            {{ ruleForm.processing ? 'Saving...' : (editingRule ? 'Save Changes' : 'Add Rule') }}
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
                            Delete Firewall Rule
                        </h3>
                    </div>
                    <button @click="showDeleteModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-2.5 text-xs">
                    <p class="text-slate-600">
                        Are you sure you want to remove rule <strong class="text-slate-900 font-mono">[{{ ruleToDelete?.label }} (Port {{ ruleToDelete?.port }})]</strong>?
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
                        {{ deleteForm.processing ? 'Deleting...' : 'Delete Rule' }}
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
