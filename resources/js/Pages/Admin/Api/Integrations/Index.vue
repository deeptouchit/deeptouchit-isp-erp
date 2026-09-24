<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import RefreshButton from '@/Components/UI/RefreshButton.vue'
import InfoCardsGrid from '@/Components/UI/InfoCardsGrid.vue'
import InfoCard from '@/Components/UI/InfoCard.vue'
import DataTableFilter from '@/Components/UI/DataTableFilter.vue'
import FilterSelect from '@/Components/UI/FilterSelect.vue'
import RowActionDropdown from '@/Components/UI/RowActionDropdown.vue'

import {
    PlusIcon,
    ArrowPathIcon,
    CheckIcon,
    XMarkIcon,
    PuzzlePieceIcon,
    CloudArrowUpIcon,
    CircleStackIcon,
    PaperAirplaneIcon,
    ShieldCheckIcon,
    PencilSquareIcon,
    BoltIcon,
    CommandLineIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    integrations: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({
            total_integrations: 0,
            connected_count: 0,
            total_events: 0,
            storage_targets: 0,
            disconnected_count: 0,
        }),
    },
    filters: {
        type: Object,
        default: () => ({ search: '', category: 'all', status: 'all' }),
    },
})

// Flash Feedback Toast
const feedbackMsg = ref('')

// Filter State
const search = ref(props.filters?.search || '')
const currentCategory = ref(props.filters?.category || 'all')
const currentStatus = ref(props.filters?.status || 'all')

const applyFilters = () => {
    router.get(route('admin.api.integrations'), {
        search: search.value || undefined,
        category: currentCategory.value !== 'all' ? currentCategory.value : undefined,
        status: currentStatus.value !== 'all' ? currentStatus.value : undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const selectCategory = (cat) => {
    currentCategory.value = cat
    applyFilters()
}

const resetFilters = () => {
    search.value = ''
    currentCategory.value = 'all'
    currentStatus.value = 'all'
    applyFilters()
}

// 1. CONFIGURE INTEGRATION MODAL
const showConfigModal = ref(false)
const selectedIntegration = ref(null)

const configForm = useForm({
    api_key: '',
    api_secret: '',
    endpoint_url: '',
    account_id: '',
    is_connected: true,
})

const openConfigModal = (item) => {
    selectedIntegration.value = item
    configForm.api_key = item.credentials?.api_key || ''
    configForm.api_secret = item.credentials?.api_secret || ''
    configForm.endpoint_url = item.credentials?.endpoint_url || ''
    configForm.account_id = item.credentials?.account_id || ''
    configForm.is_connected = item.is_connected !== false
    showConfigModal.value = true
}

const submitConfig = () => {
    if (!selectedIntegration.value) return
    configForm.put(route('admin.api.integrations.update', selectedIntegration.value.id || selectedIntegration.value.slug), {
        preserveScroll: true,
        onSuccess: () => {
            showConfigModal.value = false
            feedbackMsg.value = `${selectedIntegration.value.name} integration configured.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 2. TEST CONNECTION
const testConnection = (item) => {
    router.post(route('admin.api.integrations.test', item.id || item.slug), {}, {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = `Connection test to '${item.name}' verified successfully.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 3. TOGGLE INTEGRATION
const toggleIntegration = (item) => {
    router.post(route('admin.api.integrations.toggle', item.id || item.slug), {}, {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = `${item.name} connection toggled.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}
</script>

<template>
    <Head title="3rd-Party Integrations - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'API', href: route('admin.api.integrations') },
                    { label: 'Integrations' }
                ]"
            >
                <template #actions>
                    <Link
                        :href="route('admin.api.webhooks')"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <PaperAirplaneIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>Webhooks</span>
                    </Link>

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
                    title="Available Connectors"
                    :value="String(stats.total_integrations || integrations.length || 0)"
                    badge="Integrations"
                    badgeType="info"
                    color="blue"
                    :icon="PuzzlePieceIcon"
                />

                <InfoCard
                    title="Connected Live"
                    :value="String(stats.connected_count || integrations.filter(i => i.is_connected).length || 0)"
                    badge="Active"
                    badgeType="success"
                    color="emerald"
                    :icon="ShieldCheckIcon"
                />

                <InfoCard
                    title="Storage Targets"
                    :value="String(stats.storage_targets || 0)"
                    badge="Cloud"
                    badgeType="info"
                    color="purple"
                    :icon="CloudArrowUpIcon"
                />

                <InfoCard
                    title="Integrated Events"
                    :value="String(stats.total_events || 0)"
                    badge="Processed"
                    badgeType="info"
                    color="sky"
                    :icon="BoltIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Category Tabs -->
            <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        v-for="cat in ['all', 'cloud_storage', 'billing', 'monitoring', 'dns_provider', 'notification']"
                        :key="cat"
                        type="button"
                        @click="selectCategory(cat)"
                        :class="currentCategory === cat ? 'bg-blue-600 text-white' : 'bg-white text-slate-700 hover:bg-slate-50 border border-slate-200'"
                        class="px-3 py-1.5 rounded-[3px] font-bold text-xs uppercase font-mono transition cursor-pointer shadow-2xs"
                    >
                        {{ cat.replace(/_/g, ' ') }}
                    </button>
                </div>
            </div>

            <!-- 4. Standard Filter Toolbar -->
            <DataTableFilter
                v-model="search"
                searchPlaceholder="Search integration provider, name, or description..."
                @search="applyFilters"
                @filter="applyFilters"
                @reset="resetFilters"
            >
                <FilterSelect
                    v-model="currentStatus"
                    label="Connection Status"
                    :options="[
                        { label: 'All Statuses', value: 'all' },
                        { label: 'Connected', value: 'connected' },
                        { label: 'Disconnected', value: 'disconnected' }
                    ]"
                    placeholder="All Statuses"
                />
            </DataTableFilter>

            <!-- 5. Standard Data Table -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Integration Provider</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Category</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Description</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Status</th>
                                <th class="py-2.5 px-3 w-14 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(item, index) in integrations" :key="item.id || index" class="hover:bg-blue-50/30 transition">
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-bold text-slate-900">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs border border-blue-100 shrink-0">
                                            <PuzzlePieceIcon class="w-3.5 h-3.5" />
                                        </div>
                                        <div>
                                            <span class="block leading-tight font-bold">{{ item.name }}</span>
                                            <span class="text-[10px] text-slate-400 font-mono block">{{ item.slug || item.provider }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase bg-slate-100 text-slate-700 border border-slate-200 font-mono">
                                        {{ item.category || 'Storage' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-medium text-slate-800 max-w-sm truncate" :title="item.description">
                                    {{ item.description || 'Cloud connector integration' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="item.is_connected ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-500 border-slate-200'"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono"
                                    >
                                        {{ item.is_connected ? 'Connected' : 'Not Configured' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <RowActionDropdown>
                                        <button 
                                            type="button" 
                                            @click="openConfigModal(item)"
                                            class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                        >
                                            <PencilSquareIcon class="w-3.5 h-3.5 text-slate-500" />
                                            <span>Configure Connector</span>
                                        </button>

                                        <button 
                                            v-if="item.is_connected"
                                            type="button" 
                                            @click="testConnection(item)"
                                            class="w-full text-left px-3 py-1.5 text-xs text-blue-600 hover:bg-blue-50 flex items-center gap-2 font-medium cursor-pointer"
                                        >
                                            <BoltIcon class="w-3.5 h-3.5 text-blue-500" />
                                            <span>Test Connection</span>
                                        </button>

                                        <button 
                                            v-if="item.is_connected"
                                            type="button" 
                                            @click="toggleIntegration(item)"
                                            class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                        >
                                            <ShieldCheckIcon class="w-3.5 h-3.5 text-slate-500" />
                                            <span>Toggle Status</span>
                                        </button>
                                    </RowActionDropdown>
                                </td>
                            </tr>

                            <tr v-if="!integrations || integrations.length === 0">
                                <td colspan="6" class="py-12 text-center text-slate-400 font-sans">
                                    No cloud integrations configured.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- CONFIGURE INTEGRATION MODAL -->
        <div v-if="showConfigModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <PuzzlePieceIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Configure {{ selectedIntegration?.name }}
                        </h3>
                    </div>
                    <button @click="showConfigModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitConfig" class="p-4 space-y-3 text-xs">
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">API Key / Access Key ID <span class="text-rose-500">*</span></label>
                        <input v-model="configForm.api_key" type="text" required class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Secret Key / Token <span class="text-rose-500">*</span></label>
                        <input v-model="configForm.api_secret" type="password" required class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Endpoint / Region</label>
                        <input v-model="configForm.endpoint_url" type="text" placeholder="https://s3.us-east-1.amazonaws.com" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    </div>

                    <div class="flex items-center gap-2 pt-1">
                        <input v-model="configForm.is_connected" id="int_conn" type="checkbox" class="rounded-[2px] text-blue-600 focus:ring-blue-500 cursor-pointer" />
                        <label for="int_conn" class="text-xs text-slate-700 font-medium cursor-pointer">Enable active integration connection</label>
                    </div>

                    <div class="px-4 py-2.5 -mx-4 -mb-4 mt-4 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end gap-2 text-xs">
                        <button
                            type="button"
                            @click="showConfigModal = false"
                            class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="configForm.processing"
                            class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                        >
                            {{ configForm.processing ? 'Saving...' : 'Save Configuration' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
