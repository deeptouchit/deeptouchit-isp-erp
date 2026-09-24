<script setup>
import { ref } from 'vue'
import { Head, router, Link, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import RefreshButton from '@/Components/UI/RefreshButton.vue'

import {
    ArrowLeftIcon,
    PaperAirplaneIcon,
    CheckIcon,
    ArrowPathIcon,
    XMarkIcon,
    UserCircleIcon,
    ChatBubbleLeftRightIcon,
    ClockIcon,
    TagIcon,
    ShieldCheckIcon,
    DocumentTextIcon,
    UserIcon,
    EnvelopeIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    ticket: {
        type: Object,
        required: true,
    },
    staff: {
        type: Array,
        default: () => [],
    },
    cannedResponses: {
        type: Array,
        default: () => [],
    },
})

// Flash Feedback Toast
const feedbackMsg = ref('')

// Reply Form
const replyForm = useForm({
    message: '',
    status: 'answered',
})

const selectedCanned = ref('')

const insertCannedResponse = () => {
    if (!selectedCanned.value) return
    const template = props.cannedResponses.find(c => c.title === selectedCanned.value)
    if (template) {
        replyForm.message = template.message
    }
}

const submitReply = () => {
    replyForm.post(route('admin.tickets.reply', props.ticket.id), {
        preserveScroll: true,
        onSuccess: () => {
            replyForm.reset('message')
            replyForm.status = 'answered'
            selectedCanned.value = ''
            feedbackMsg.value = 'Staff reply submitted.'
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// Ticket Metadata Update Form
const metaForm = useForm({
    status: props.ticket.status,
    priority: props.ticket.priority,
    department: props.ticket.department,
    assigned_to: props.ticket.assigned_to || '',
})

const updateMeta = () => {
    metaForm.put(route('admin.tickets.update', props.ticket.id), {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = 'Ticket metadata updated.'
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 1-Click Close or Reopen Ticket
const toggleCloseTicket = () => {
    if (props.ticket.status === 'closed') {
        metaForm.status = 'open'
        updateMeta()
    } else {
        router.post(route('admin.tickets.close', props.ticket.id), {}, {
            preserveScroll: true,
            onSuccess: () => {
                feedbackMsg.value = `Ticket #${props.ticket.ticket_number || props.ticket.id} closed.`
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            }
        })
    }
}
</script>

<template>
    <Head :title="`Ticket #${ticket.ticket_number || ticket.id} - DeepTouchHost`" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Support', href: '#' },
                    { label: 'Customer Support', href: route('admin.tickets.index') },
                    { label: `Ticket #${ticket.ticket_number || ticket.id}` }
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
                        @click="toggleCloseTicket"
                        :class="ticket.status === 'closed' ? 'bg-emerald-600 hover:bg-emerald-700 text-white' : 'bg-white hover:bg-slate-50 text-slate-700 border border-slate-200'"
                        class="px-2.5 py-1.5 font-bold rounded-[3px] text-xs flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <CheckIcon class="w-3.5 h-3.5" />
                        <span>{{ ticket.status === 'closed' ? 'Reopen Ticket' : 'Close Ticket' }}</span>
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

            <!-- Main Layout: 2 Columns (Conversation Stream + Ticket Info Sidebar) -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-3.5 items-start">
                <!-- Left: Ticket Thread & Reply Composer -->
                <div class="lg:col-span-2 space-y-3.5">
                    <!-- Ticket Header Box -->
                    <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs p-4 space-y-2">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="font-mono font-bold text-xs text-blue-700">#{{ ticket.ticket_number || ticket.id }}</span>
                                <span class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase bg-slate-100 text-slate-700 border border-slate-200 font-mono">
                                    {{ ticket.department || 'Technical' }}
                                </span>
                                <span 
                                    :class="[
                                        ticket.priority === 'critical' ? 'bg-rose-50 text-rose-700 border-rose-200' :
                                        ticket.priority === 'high' ? 'bg-amber-50 text-amber-700 border-amber-200' :
                                        'bg-blue-50 text-blue-700 border-blue-200'
                                    ]"
                                    class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono"
                                >
                                    {{ ticket.priority || 'medium' }}
                                </span>
                            </div>
                            <span class="text-[11px] font-mono text-slate-400">{{ ticket.created_at ? new Date(ticket.created_at).toLocaleString() : 'Recent' }}</span>
                        </div>
                        <h2 class="text-sm font-bold text-slate-900 leading-snug">{{ ticket.subject }}</h2>
                    </div>

                    <!-- Conversation Stream -->
                    <div class="space-y-3">
                        <!-- Initial Message -->
                        <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs p-4 space-y-2.5">
                            <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-xs">
                                        {{ (ticket.user?.name || ticket.client_name || 'C').charAt(0) }}
                                    </div>
                                    <div>
                                        <span class="font-bold text-xs text-slate-900 block leading-tight">{{ ticket.user?.name || ticket.client_name || 'Customer' }}</span>
                                        <span class="text-[10px] text-slate-400 font-mono">Client Inquirer</span>
                                    </div>
                                </div>
                                <span class="text-[11px] font-mono text-slate-400">{{ ticket.created_at ? new Date(ticket.created_at).toLocaleString() : 'Original Post' }}</span>
                            </div>
                            <div class="text-xs text-slate-800 whitespace-pre-wrap leading-relaxed">
                                {{ ticket.message || 'Customer opened inquiry.' }}
                            </div>
                        </div>

                        <!-- Replies Loop -->
                        <div
                            v-for="r in ticket.replies || []"
                            :key="r.id"
                            :class="r.is_staff ? 'bg-blue-50/40 border-blue-200/70' : 'bg-white border-[#E2E8F0]'"
                            class="rounded-[4px] border shadow-2xs p-4 space-y-2.5"
                        >
                            <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                                <div class="flex items-center gap-2">
                                    <div 
                                        :class="r.is_staff ? 'bg-blue-600 text-white' : 'bg-slate-200 text-slate-700'"
                                        class="w-6 h-6 rounded-full flex items-center justify-center font-bold text-xs"
                                    >
                                        {{ (r.user?.name || r.author_name || (r.is_staff ? 'S' : 'C')).charAt(0) }}
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-1.5">
                                            <span class="font-bold text-xs text-slate-900">{{ r.user?.name || r.author_name || (r.is_staff ? 'Support Staff' : 'Customer') }}</span>
                                            <span v-if="r.is_staff" class="px-1.5 py-0.2 rounded-[2px] bg-blue-100 text-blue-700 font-bold text-[9px] uppercase font-mono">Staff</span>
                                        </div>
                                    </div>
                                </div>
                                <span class="text-[11px] font-mono text-slate-400">{{ r.created_at ? new Date(r.created_at).toLocaleString() : 'Recent' }}</span>
                            </div>
                            <div class="text-xs text-slate-800 whitespace-pre-wrap leading-relaxed">
                                {{ r.message }}
                            </div>
                        </div>
                    </div>

                    <!-- Staff Reply Composer -->
                    <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs p-4 space-y-3">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                            <span class="font-bold text-xs text-slate-900 uppercase tracking-wide flex items-center gap-1.5">
                                <PaperAirplaneIcon class="w-3.5 h-3.5 text-blue-600" />
                                <span>Post Staff Response</span>
                            </span>

                            <div v-if="cannedResponses.length" class="flex items-center gap-1.5">
                                <span class="text-[11px] text-slate-500 font-medium">Insert Macro:</span>
                                <select 
                                    v-model="selectedCanned" 
                                    @change="insertCannedResponse"
                                    class="px-2 py-1 text-xs rounded-[3px] border border-slate-200 bg-slate-50 text-slate-800 focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer"
                                >
                                    <option value="" disabled>Choose macro...</option>
                                    <option v-for="c in cannedResponses" :key="c.id" :value="c.title">{{ c.title }}</option>
                                </select>
                            </div>
                        </div>

                        <form @submit.prevent="submitReply" class="space-y-3 text-xs">
                            <textarea
                                v-model="replyForm.message"
                                rows="4"
                                required
                                placeholder="Type your response to the client here..."
                                class="w-full px-3 py-2 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"
                            ></textarea>

                            <div class="flex items-center justify-between pt-1">
                                <div class="flex items-center gap-2">
                                    <label class="font-bold text-slate-700">Set Ticket Status:</label>
                                    <select v-model="replyForm.status" class="px-2 py-1 text-xs rounded-[3px] border border-slate-200 bg-slate-50 text-slate-900 focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                                        <option value="answered">Answered (Pending Client)</option>
                                        <option value="in_progress">In Progress</option>
                                        <option value="closed">Closed & Resolved</option>
                                    </select>
                                </div>

                                <button
                                    type="submit"
                                    :disabled="replyForm.processing"
                                    class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] transition shadow-2xs flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
                                >
                                    <PaperAirplaneIcon class="w-3.5 h-3.5" />
                                    <span>{{ replyForm.processing ? 'Posting...' : 'Send Reply' }}</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Right: Ticket Metadata & Customer Profile -->
                <div class="space-y-3.5">
                    <!-- Ticket Meta Box -->
                    <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs p-4 space-y-3">
                        <span class="font-bold text-xs text-slate-900 uppercase tracking-wide block border-b border-slate-100 pb-2">
                            Ticket Settings & Routing
                        </span>

                        <form @submit.prevent="updateMeta" class="space-y-2.5 text-xs">
                            <div class="space-y-1">
                                <label class="block font-bold text-slate-700">Ticket Status</label>
                                <select v-model="metaForm.status" class="w-full px-2.5 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                                    <option value="open">Open</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="answered">Answered</option>
                                    <option value="closed">Closed</option>
                                </select>
                            </div>

                            <div class="space-y-1">
                                <label class="block font-bold text-slate-700">Priority</label>
                                <select v-model="metaForm.priority" class="w-full px-2.5 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                    <option value="critical">Critical</option>
                                </select>
                            </div>

                            <div class="space-y-1">
                                <label class="block font-bold text-slate-700">Department</label>
                                <select v-model="metaForm.department" class="w-full px-2.5 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                                    <option value="technical">Technical Support</option>
                                    <option value="billing">Billing & Accounts</option>
                                    <option value="sales">Sales & Upgrades</option>
                                </select>
                            </div>

                            <div class="space-y-1">
                                <label class="block font-bold text-slate-700">Assigned Agent</label>
                                <select v-model="metaForm.assigned_to" class="w-full px-2.5 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                                    <option value="">Unassigned</option>
                                    <option v-for="s in staff" :key="s.id" :value="s.id">{{ s.name }}</option>
                                </select>
                            </div>

                            <button
                                type="submit"
                                :disabled="metaForm.processing"
                                class="w-full mt-2 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer text-xs"
                            >
                                {{ metaForm.processing ? 'Saving...' : 'Update Settings' }}
                            </button>
                        </form>
                    </div>

                    <!-- Client Profile Box -->
                    <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs p-4 space-y-2.5 text-xs">
                        <span class="font-bold text-slate-900 uppercase tracking-wide block border-b border-slate-100 pb-2">
                            Customer Profile
                        </span>

                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded-full bg-slate-100 text-slate-700 flex items-center justify-center font-bold text-xs">
                                {{ (ticket.user?.name || ticket.client_name || 'C').charAt(0) }}
                            </div>
                            <div>
                                <span class="block font-bold text-slate-900 leading-tight">{{ ticket.user?.name || ticket.client_name || 'Customer' }}</span>
                                <span class="text-[11px] text-slate-400 font-mono">{{ ticket.user?.email || ticket.client_email }}</span>
                            </div>
                        </div>

                        <div class="border-t border-slate-100 pt-2 space-y-1.5 text-[11px] text-slate-600">
                            <div class="flex justify-between">
                                <span class="text-slate-400">Total Inquiries:</span>
                                <span class="font-mono font-bold text-slate-800">{{ ticket.user?.tickets_count || 1 }} Tickets</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-400">Account Domain:</span>
                                <span class="font-mono text-blue-600">{{ ticket.domain || 'N/A' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
