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
    PaperAirplaneIcon,
    ShieldCheckIcon,
    NoSymbolIcon,
    TrashIcon,
    PencilSquareIcon,
    DocumentDuplicateIcon,
    KeyIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    webhooks: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    stats: {
        type: Object,
        default: () => ({
            total_webhooks: 0,
            active_webhooks: 0,
            total_dispatches: 0,
            delivery_rate: 100,
            success_count: 0,
            failing_count: 0,
        }),
    },
    filters: {
        type: Object,
        default: () => ({ search: '', status: 'all' }),
    },
})

// Flash Feedback Toast
const feedbackMsg = ref('')

// Filter State
const search = ref(props.filters?.search || '')
const currentStatus = ref(props.filters?.status || 'all')

const applyFilters = () => {
    router.get(route('admin.api.webhooks'), {
        search: search.value || undefined,
        status: currentStatus.value !== 'all' ? currentStatus.value : undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const selectStatus = (status) => {
    currentStatus.value = status
    applyFilters()
}

const resetFilters = () => {
    search.value = ''
    currentStatus.value = 'all'
    applyFilters()
}

// Available Events
const availableEvents = [
    { key: '*', label: 'All System Events (*)' },
    { key: 'account.created', label: 'Hosting Account Created' },
    { key: 'account.suspended', label: 'Hosting Account Suspended' },
    { key: 'invoice.paid', label: 'Invoice Payment Received' },
    { key: 'ticket.created', label: 'Support Ticket Opened' },
    { key: 'security.ip_banned', label: 'Security Firewall IP Banned' }
]

// 1. CREATE / EDIT WEBHOOK MODAL
const showModal = ref(false)
const isEditing = ref(false)
const editingWebhookId = ref(null)

const webhookForm = useForm({
    name: '',
    url: '',
    events: ['account.created', 'invoice.paid'],
    secret: '',
    is_active: true,
})

const openCreateModal = () => {
    isEditing.value = false
    editingWebhookId.value = null
    webhookForm.reset()
    webhookForm.events = ['account.created', 'invoice.paid']
    webhookForm.is_active = true
    showModal.value = true
}

const openEditModal = (w) => {
    isEditing.value = true
    editingWebhookId.value = w.id
    webhookForm.name = w.name
    webhookForm.url = w.url
    webhookForm.events = w.events || ['account.created']
    webhookForm.secret = w.secret || ''
    webhookForm.is_active = w.is_active !== false
    showModal.value = true
}

const submitWebhook = () => {
    if (isEditing.value) {
        webhookForm.put(route('admin.api.webhooks.update', editingWebhookId.value), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false
                feedbackMsg.value = 'Webhook endpoint updated.'
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            }
        })
    } else {
        webhookForm.post(route('admin.api.webhooks.store'), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false
                feedbackMsg.value = 'New webhook endpoint registered.'
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            }
        })
    }
}

// 2. TEST PING WEBHOOK
const pingWebhook = (w) => {
    router.post(route('admin.api.webhooks.test', w.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = `Test ping dispatched to '${w.name}'.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 3. TOGGLE WEBHOOK
const toggleWebhook = (w) => {
    router.post(route('admin.api.webhooks.toggle', w.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = 'Webhook status updated.'
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 4. DELETE MODAL
const showDeleteModal = ref(false)
const webhookToDelete = ref(null)
const deleteForm = useForm({})

const openDeleteModal = (w) => {
    webhookToDelete.value = w
    showDeleteModal.value = true
}

const submitDelete = () => {
    if (!webhookToDelete.value) return
    deleteForm.delete(route('admin.api.webhooks.destroy', webhookToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false
            feedbackMsg.value = 'Webhook endpoint removed.'
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

const copySecret = (secret) => {
    navigator.clipboard.writeText(secret)
    feedbackMsg.value = 'Signing secret copied to clipboard.'
    setTimeout(() => { feedbackMsg.value = '' }, 2500)
}
</script>

<template>
    <Head title="Outbound Webhooks - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'API', href: route('admin.api.webhooks') },
                    { label: 'Webhooks' }
                ]"
            >
                <template #actions>
                    <Link
                        :href="route('admin.api.keys')"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <KeyIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>API Keys</span>
                    </Link>

                    <button 
                        type="button" 
                        @click="openCreateModal"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <PlusIcon class="w-3.5 h-3.5" />
                        <span>Register Webhook</span>
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
                    title="Webhook Endpoints"
                    :value="String(stats.total_webhooks || webhooks.data?.length || 0)"
                    badge="Endpoints"
                    badgeType="info"
                    color="blue"
                    :icon="PaperAirplaneIcon"
                />

                <InfoCard
                    title="Active Subscriptions"
                    :value="String(stats.active_webhooks || webhooks.data?.filter(w => w.is_active).length || 0)"
                    badge="Live"
                    badgeType="success"
                    color="emerald"
                    :icon="ShieldCheckIcon"
                />

                <InfoCard
                    title="Total Event Dispatches"
                    :value="String(stats.total_dispatches || 0)"
                    badge="Dispatched"
                    badgeType="info"
                    color="purple"
                    :icon="ArrowPathIcon"
                />

                <InfoCard
                    title="Delivery Success Rate"
                    :value="`${stats.delivery_rate || 100}%`"
                    badge="Healthy"
                    badgeType="success"
                    color="sky"
                    :icon="CheckIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Status Tabs -->
            <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        v-for="st in ['all', 'active', 'paused']"
                        :key="st"
                        type="button"
                        @click="selectStatus(st)"
                        :class="currentStatus === st ? 'bg-blue-600 text-white' : 'bg-white text-slate-700 hover:bg-slate-50 border border-slate-200'"
                        class="px-3 py-1.5 rounded-[3px] font-bold text-xs uppercase font-mono transition cursor-pointer shadow-2xs"
                    >
                        {{ st }}
                    </button>
                </div>
            </div>

            <!-- 4. Standard Filter Toolbar -->
            <DataTableFilter
                v-model="search"
                searchPlaceholder="Search webhook name, target HTTP URL, or event..."
                @search="applyFilters"
                @filter="applyFilters"
                @reset="resetFilters"
            />

            <!-- 5. Standard Data Table -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Endpoint Name</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Target Payload URL</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Subscribed Events</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Last Delivery</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Status</th>
                                <th class="py-2.5 px-3 w-14 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(w, index) in webhooks.data" :key="w.id || index" class="hover:bg-blue-50/30 transition">
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-bold text-slate-900">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs border border-blue-100 shrink-0">
                                            <PaperAirplaneIcon class="w-3.5 h-3.5" />
                                        </div>
                                        <span>{{ w.name }}</span>
                                    </div>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono text-[11px] text-slate-600 max-w-xs truncate" :title="w.url">
                                    {{ w.url }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase bg-slate-100 text-slate-700 border border-slate-200 font-mono">
                                        {{ w.events?.includes('*') ? 'All Events (*)' : `${w.events?.length || 1} Events` }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[10.5px] text-slate-500">
                                    {{ w.last_delivery_at ? new Date(w.last_delivery_at).toLocaleString() : 'Never' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="w.is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-500 border-slate-200'"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono"
                                    >
                                        {{ w.is_active ? 'Active' : 'Paused' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <RowActionDropdown>
                                        <button 
                                            type="button" 
                                            @click="pingWebhook(w)"
                                            class="w-full text-left px-3 py-1.5 text-xs text-blue-600 hover:bg-blue-50 flex items-center gap-2 font-medium cursor-pointer"
                                        >
                                            <PaperAirplaneIcon class="w-3.5 h-3.5 text-blue-500" />
                                            <span>Send Test Ping</span>
                                        </button>

                                        <button
                                            v-if="w.secret"
                                            type="button"
                                            @click="copySecret(w.secret)"
                                            class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                        >
                                            <DocumentDuplicateIcon class="w-3.5 h-3.5 text-slate-500" />
                                            <span>Copy Secret</span>
                                        </button>

                                        <button
                                            type="button"
                                            @click="openEditModal(w)"
                                            class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                        >
                                            <PencilSquareIcon class="w-3.5 h-3.5 text-slate-500" />
                                            <span>Edit Endpoint</span>
                                        </button>

                                        <button
                                            type="button"
                                            @click="toggleWebhook(w)"
                                            class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                        >
                                            <NoSymbolIcon class="w-3.5 h-3.5 text-slate-500" />
                                            <span>{{ w.is_active ? 'Pause Webhook' : 'Activate Webhook' }}</span>
                                        </button>

                                        <button
                                            type="button"
                                            @click="openDeleteModal(w)"
                                            class="w-full text-left px-3 py-1.5 text-xs text-rose-600 hover:bg-rose-50 flex items-center gap-2 font-medium cursor-pointer"
                                        >
                                            <TrashIcon class="w-3.5 h-3.5" />
                                            <span>Delete Webhook</span>
                                        </button>
                                    </RowActionDropdown>
                                </td>
                            </tr>

                            <tr v-if="!webhooks.data || webhooks.data.length === 0">
                                <td colspan="7" class="py-12 text-center text-slate-400 font-sans">
                                    No outbound webhooks configured.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- CREATE / EDIT MODAL -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <PaperAirplaneIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            {{ isEditing ? 'Edit Webhook Endpoint' : 'Register Webhook Endpoint' }}
                        </h3>
                    </div>
                    <button @click="showModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitWebhook" class="p-4 space-y-3 text-xs">
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Endpoint Friendly Name <span class="text-rose-500">*</span></label>
                        <input v-model="webhookForm.name" type="text" required placeholder="Slack Alert Dispatcher" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Target HTTP URL <span class="text-rose-500">*</span></label>
                        <input v-model="webhookForm.url" type="url" required placeholder="https://hooks.slack.com/services/..." class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Signing Secret (Optional)</label>
                        <input v-model="webhookForm.secret" type="password" placeholder="whsec_..." class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    </div>

                    <div class="space-y-1.5 pt-1">
                        <label class="block font-bold text-slate-700">Subscribed Events</label>
                        <div class="space-y-1 bg-slate-50 p-2 rounded-[3px] border border-slate-200 max-h-32 overflow-y-auto">
                            <label v-for="ev in availableEvents" :key="ev.key" class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" :value="ev.key" v-model="webhookForm.events" class="rounded-[2px] text-blue-600 focus:ring-blue-500 cursor-pointer" />
                                <span class="text-xs text-slate-800">{{ ev.label }}</span>
                            </label>
                        </div>
                    </div>

                    <div class="px-4 py-2.5 -mx-4 -mb-4 mt-4 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end gap-2 text-xs">
                        <button
                            type="button"
                            @click="showModal = false"
                            class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="webhookForm.processing"
                            class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                        >
                            {{ webhookForm.processing ? 'Saving...' : (isEditing ? 'Save Changes' : 'Register Webhook') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- DELETE MODAL -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-rose-50 text-rose-600 flex items-center justify-center font-bold border border-rose-100">
                            <TrashIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Delete Webhook Endpoint
                        </h3>
                    </div>
                    <button @click="showDeleteModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-2.5 text-xs">
                    <p class="text-slate-600">
                        Are you sure you want to permanently delete webhook endpoint <strong class="text-slate-900 font-mono">[{{ webhookToDelete?.name }}]</strong>?
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
                        {{ deleteForm.processing ? 'Deleting...' : 'Delete Webhook' }}
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
