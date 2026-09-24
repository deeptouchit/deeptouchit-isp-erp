<script setup>
import { ref, computed } from 'vue'
import { Head, router, Link, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import RefreshButton from '@/Components/UI/RefreshButton.vue'
import InfoCardsGrid from '@/Components/UI/InfoCardsGrid.vue'
import InfoCard from '@/Components/UI/InfoCard.vue'
import DataTableFilter from '@/Components/UI/DataTableFilter.vue'
import RowActionDropdown from '@/Components/UI/RowActionDropdown.vue'

import {
    PlusIcon,
    ArrowPathIcon,
    XMarkIcon,
    UserGroupIcon,
    BriefcaseIcon,
    ClockIcon,
    EnvelopeIcon,
    PencilSquareIcon,
    TrashIcon,
    ShieldCheckIcon,
    ChatBubbleLeftRightIcon,
    BoltIcon,
    CheckIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    agents: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({
            total_agents: 0,
            active_roster_count: 0,
            total_assigned_open: 0,
            total_staff_replies: 0,
            auto_assign_count: 0,
        }),
    },
    departments: {
        type: Array,
        default: () => [],
    },
    availableUsers: {
        type: Array,
        default: () => [],
    },
})

// Flash Feedback Toast
const feedbackMsg = ref('')
const search = ref('')

const filteredAgents = computed(() => {
    let list = props.agents || []
    if (search.value.trim()) {
        const q = search.value.toLowerCase()
        list = list.filter(a =>
            (a.name || '').toLowerCase().includes(q) ||
            (a.email || '').toLowerCase().includes(q) ||
            (a.job_title || '').toLowerCase().includes(q)
        )
    }
    return list
})

// 1. CREATE / EDIT MODAL
const showModal = ref(false)
const isEditing = ref(false)
const editingUserId = ref(null)

const agentForm = useForm({
    user_id: '',
    job_title: 'Support Engineer',
    signature: '',
    max_active_tickets: 20,
    is_auto_assignable: true,
})

const openCreateModal = () => {
    isEditing.value = false
    editingUserId.value = null
    agentForm.reset()
    agentForm.user_id = props.availableUsers[0]?.id || ''
    agentForm.job_title = 'Tier-2 Linux Systems Engineer'
    agentForm.max_active_tickets = 20
    agentForm.is_auto_assignable = true
    showModal.value = true
}

const openEditModal = (agent) => {
    isEditing.value = true
    editingUserId.value = agent.id
    agentForm.user_id = agent.id
    agentForm.job_title = agent.job_title || 'Support Engineer'
    agentForm.signature = agent.signature || ''
    agentForm.max_active_tickets = agent.max_active_tickets || 20
    agentForm.is_auto_assignable = Boolean(agent.is_auto_assignable)
    showModal.value = true
}

const submitAgentForm = () => {
    if (isEditing.value) {
        agentForm.put(route('admin.support.agents.update', editingUserId.value), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false
                feedbackMsg.value = 'Agent profile updated.'
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            }
        })
    } else {
        agentForm.post(route('admin.support.agents.store'), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false
                feedbackMsg.value = 'Staff agent enrolled in support roster.'
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            }
        })
    }
}

// 2. TOGGLE AUTO-ASSIGN
const toggleAutoAssign = (agent) => {
    router.post(route('admin.support.agents.toggle', agent.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = 'Auto-assignment status updated.'
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 3. DELETE MODAL
const showDeleteModal = ref(false)
const agentToDelete = ref(null)
const deleteForm = useForm({})

const openDeleteModal = (agent) => {
    agentToDelete.value = agent
    showDeleteModal.value = true
}

const submitDelete = () => {
    if (!agentToDelete.value) return
    deleteForm.delete(route('admin.support.agents.destroy', agentToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false
            feedbackMsg.value = 'Staff agent removed from roster.'
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}
</script>

<template>
    <Head title="Support Agents - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Support', href: '#' },
                    { label: 'Customer Support', href: route('admin.tickets.index') },
                    { label: 'Staff Support Agents & Duty Roster' }
                ]"
            >
                <template #actions>
                    <Link
                        :href="route('admin.tickets.index')"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <ArrowLeftIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>All Tickets</span>
                    </Link>

                    <button 
                        type="button" 
                        @click="openCreateModal"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <PlusIcon class="w-3.5 h-3.5" />
                        <span>Enroll Staff Agent</span>
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
                    title="Enrolled Staff Agents"
                    :value="String(stats.total_agents || agents.length || 0)"
                    badge="Agents"
                    badgeType="info"
                    color="blue"
                    :icon="UserGroupIcon"
                />

                <InfoCard
                    title="Active On Duty"
                    :value="String(stats.active_roster_count || agents.length || 0)"
                    badge="Online"
                    badgeType="success"
                    color="emerald"
                    :icon="ShieldCheckIcon"
                />

                <InfoCard
                    title="Assigned Inquiries"
                    :value="String(stats.total_assigned_open || 0)"
                    badge="Active Tickets"
                    badgeType="warning"
                    color="purple"
                    :icon="ChatBubbleLeftRightIcon"
                />

                <InfoCard
                    title="Responses Posted"
                    :value="String(stats.total_staff_replies || 0)"
                    badge="Replies"
                    badgeType="info"
                    color="sky"
                    :icon="ClockIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Standard Filter Toolbar -->
            <DataTableFilter
                v-model="search"
                searchPlaceholder="Search agent name, email address, or job title..."
                @search="() => {}"
                @filter="() => {}"
                @reset="() => { search = ''; }"
            />

            <!-- 4. Standard Data Table -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Staff Agent Name</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Job Designation</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Ticket Load Limit</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Auto Assignable</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Status</th>
                                <th class="py-2.5 px-3 w-28">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(agent, index) in filteredAgents" :key="agent.id || index" class="hover:bg-blue-50/30 transition">
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-bold text-slate-900">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs border border-blue-100 shrink-0">
                                            <UserGroupIcon class="w-3.5 h-3.5" />
                                        </div>
                                        <div>
                                            <span class="block leading-tight font-bold">{{ agent.name }}</span>
                                            <span class="text-[10px] text-slate-400 font-mono block">{{ agent.email }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap text-slate-800 font-medium">
                                    {{ agent.job_title || 'Support Engineer' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[11px] text-slate-700">
                                    Max {{ agent.max_active_tickets || 20 }} Tickets
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="agent.is_auto_assignable ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-500 border-slate-200'"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono"
                                    >
                                        {{ agent.is_auto_assignable ? 'Yes' : 'No' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase bg-emerald-50 text-emerald-700 border border-emerald-200 font-mono">
                                        Active
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1">
                                        <RowActionDropdown>
                                            <button
                                                type="button"
                                                @click="openEditModal(agent)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <PencilSquareIcon class="w-3.5 h-3.5 text-slate-500" />
                                                <span>Edit Profile</span>
                                            </button>

                                            <button
                                                type="button"
                                                @click="toggleAutoAssign(agent)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <BoltIcon class="w-3.5 h-3.5 text-slate-500" />
                                                <span>{{ agent.is_auto_assignable ? 'Disable Auto-Assign' : 'Enable Auto-Assign' }}</span>
                                            </button>

                                            <button
                                                type="button"
                                                @click="openDeleteModal(agent)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-rose-600 hover:bg-rose-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <TrashIcon class="w-3.5 h-3.5" />
                                                <span>Remove Agent</span>
                                            </button>
                                        </RowActionDropdown>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!filteredAgents || filteredAgents.length === 0">
                                <td colspan="7" class="py-12 text-center text-slate-400 font-sans">
                                    No support agents enrolled.
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
                            <UserGroupIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            {{ isEditing ? 'Edit Staff Agent Profile' : 'Enroll Support Staff Agent' }}
                        </h3>
                    </div>
                    <button @click="showModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitAgentForm" class="p-4 space-y-3 text-xs">
                    <div v-if="!isEditing" class="space-y-1">
                        <label class="block font-bold text-slate-700">Select User Account <span class="text-rose-500">*</span></label>
                        <select v-model="agentForm.user_id" required class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                            <option value="" disabled>Select User...</option>
                            <option v-for="u in availableUsers" :key="u.id" :value="u.id">{{ u.name }} ({{ u.email }})</option>
                        </select>
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Job Title / Designation <span class="text-rose-500">*</span></label>
                        <input v-model="agentForm.job_title" type="text" required placeholder="Senior Linux Support Engineer" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Max Active Ticket Load</label>
                        <input v-model.number="agentForm.max_active_tickets" type="number" min="1" max="100" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Agent Email Signature</label>
                        <textarea v-model="agentForm.signature" rows="3" placeholder="Best regards, Agent Name..." class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"></textarea>
                    </div>

                    <div class="flex items-center gap-2 pt-1">
                        <input v-model="agentForm.is_auto_assignable" id="auto_assign" type="checkbox" class="rounded-[2px] text-blue-600 focus:ring-blue-500 cursor-pointer" />
                        <label for="auto_assign" class="text-xs text-slate-700 font-medium cursor-pointer">Include agent in round-robin auto-ticket assignment</label>
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
                            :disabled="agentForm.processing"
                            class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                        >
                            {{ agentForm.processing ? 'Saving...' : (isEditing ? 'Save Changes' : 'Enroll Agent') }}
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
                            Remove Support Agent
                        </h3>
                    </div>
                    <button @click="showDeleteModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-2.5 text-xs">
                    <p class="text-slate-600">
                        Are you sure you want to remove staff member <strong class="text-slate-900 font-mono">[{{ agentToDelete?.name }}]</strong> from the support roster?
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
                        {{ deleteForm.processing ? 'Removing...' : 'Remove Agent' }}
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
