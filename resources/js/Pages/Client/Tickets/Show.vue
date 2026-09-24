<script setup>
import { ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import { 
    ArrowLeftIcon, 
    PaperAirplaneIcon, 
    CheckCircleIcon, 
    ShieldCheckIcon,
    XMarkIcon,
    ClipboardDocumentIcon,
    CheckIcon,
    ClockIcon,
    UserCircleIcon,
    ChatBubbleLeftRightIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    ticket: {
        type: Object,
        required: true
    }
})

const replyForm = useForm({
    message: ''
})

const submitReply = () => {
    replyForm.post(route('tickets.reply', props.ticket.id), {
        onSuccess: () => replyForm.reset()
    })
}

const closeTicket = () => {
    if (confirm('Are you sure you want to mark this support ticket as closed?')) {
        useForm({}).post(route('tickets.close', props.ticket.id))
    }
}

// Copy Feedback
const copied = ref(false)
const copyTicketNo = () => {
    navigator.clipboard.writeText(props.ticket.ticket_no)
    copied.value = true
    setTimeout(() => {
        copied.value = false
    }, 2000)
}

const formatDate = (dateStr) => {
    if (!dateStr) return 'N/A'
    if (typeof dateStr === 'string' && dateStr.length <= 12 && dateStr.includes(',')) {
        return dateStr
    }
    try {
        const d = new Date(dateStr)
        if (isNaN(d.getTime())) return dateStr
        return d.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
    } catch {
        return String(dateStr).substring(0, 16)
    }
}
</script>

<template>
    <Head :title="`Ticket #${ticket.ticket_no} - DeepTouch Host`" />

    <AuthenticatedLayout>
        <div class="max-w-6xl mx-auto space-y-4">
            <!-- 1. Smart Minimal Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Support & Helpdesk', href: '#' },
                    { label: 'Support Tickets', href: route('tickets.index') },
                    { label: 'Ticket #' + ticket.ticket_no }
                ]"
            >
                <template #actions>
                    <Link 
                        :href="route('tickets.index')" 
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <ArrowLeftIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>Tickets</span>
                    </Link>

                    <button 
                        v-if="ticket.status !== 'closed'"
                        @click="closeTicket" 
                        class="px-2.5 py-1.5 bg-white hover:bg-rose-50 text-slate-700 hover:text-rose-700 border border-slate-200 hover:border-rose-200 rounded-[3px] text-xs font-bold transition shadow-2xs cursor-pointer flex items-center gap-1.5"
                    >
                        <XMarkIcon class="w-3.5 h-3.5" />
                        <span>Close Ticket</span>
                    </button>
                </template>
            </PageHeader>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Left 2-Cols: Conversation Stream -->
            <div class="lg:col-span-2 space-y-4">
                
                <!-- Initial User Message -->
                <div class="bg-white rounded-lg border border-slate-200 p-5 sm:p-6 shadow-xs space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded bg-blue-600 text-white font-black text-xs flex items-center justify-center">
                                {{ (ticket.user?.username || 'U').charAt(0).toUpperCase() }}
                            </div>
                            <div>
                                <p class="font-bold text-slate-900 text-xs">
                                    {{ ticket.user?.first_name ? `${ticket.user.first_name} ${ticket.user.last_name || ''}` : ticket.user?.username || 'Client' }}
                                </p>
                                <span class="text-[10px] text-slate-400 font-mono">Original Inquiry</span>
                            </div>
                        </div>
                        <span class="text-[11px] font-mono text-slate-400">
                            {{ ticket.formatted_created_at || formatDate(ticket.created_at) }}
                        </span>
                    </div>
                    <div class="text-xs text-slate-800 leading-relaxed whitespace-pre-wrap font-sans">
                        {{ ticket.message }}
                    </div>
                </div>

                <!-- Replies Thread -->
                <div v-for="reply in ticket.replies" :key="reply.id" class="space-y-4">
                    <div 
                        :class="[
                            reply.is_staff 
                                ? 'bg-gradient-to-br from-blue-50/60 to-indigo-50/40 border-blue-200' 
                                : 'bg-white border-slate-200'
                        ]"
                        class="rounded-lg border p-5 sm:p-6 shadow-xs space-y-4"
                    >
                        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                            <div class="flex items-center gap-2.5">
                                <div 
                                    :class="[
                                        reply.is_staff 
                                            ? 'bg-blue-600 text-white' 
                                            : 'bg-slate-200 text-slate-700'
                                    ]"
                                    class="w-8 h-8 rounded font-black text-xs flex items-center justify-center shadow-2xs"
                                >
                                    {{ reply.is_staff ? '⚡' : (reply.user?.username || 'U').charAt(0).toUpperCase() }}
                                </div>
                                <div>
                                    <div class="flex items-center gap-1.5">
                                        <p class="font-bold text-slate-900 text-xs">
                                            {{ reply.is_staff ? 'DeepTouch Cloud Engineer' : (reply.user?.username || 'Client') }}
                                        </p>
                                        <span 
                                            v-if="reply.is_staff" 
                                            class="text-[9px] font-black uppercase tracking-wider bg-blue-600 text-white px-1.5 py-0.2 rounded"
                                        >
                                            Staff
                                        </span>
                                    </div>
                                    <span class="text-[10px] text-slate-400 font-mono">
                                        {{ reply.is_staff ? 'Support Operations Center' : 'Customer Reply' }}
                                    </span>
                                </div>
                            </div>
                            <span class="text-[11px] font-mono text-slate-400">
                                {{ reply.formatted_created_at || formatDate(reply.created_at) }}
                            </span>
                        </div>
                        <div class="text-xs text-slate-800 leading-relaxed whitespace-pre-wrap font-sans">
                            {{ reply.message }}
                        </div>
                    </div>
                </div>

                <!-- Reply Input Card (If Open) -->
                <div v-if="ticket.status !== 'closed'" class="bg-white rounded-lg border border-slate-200 p-5 sm:p-6 shadow-xs space-y-3">
                    <div class="flex items-center gap-2 text-slate-900 font-bold text-xs uppercase tracking-wider">
                        <ChatBubbleLeftRightIcon class="w-4 h-4 text-blue-600" />
                        <span>Post a Reply to Engineers</span>
                    </div>
                    <form @submit.prevent="submitReply" class="space-y-3">
                        <textarea 
                            v-model="replyForm.message" 
                            rows="4" 
                            required 
                            placeholder="Write your follow-up message, extra details, or upload outputs..." 
                            class="w-full bg-slate-50/50 border border-slate-200 rounded text-xs p-3.5 text-slate-900 focus:ring-1 focus:ring-blue-600 focus:bg-white focus:outline-hidden leading-relaxed"
                        ></textarea>
                        <div class="flex justify-end">
                            <button 
                                type="submit" 
                                :disabled="replyForm.processing"
                                class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded text-xs font-bold shadow-xs transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
                            >
                                <span>{{ replyForm.processing ? 'Sending...' : 'Send Reply' }}</span>
                                <PaperAirplaneIcon class="w-3.5 h-3.5" />
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Ticket Closed Notification -->
                <div v-else class="bg-slate-50 border border-slate-200 rounded-lg p-5 text-center space-y-1">
                    <CheckCircleIcon class="w-7 h-7 text-emerald-600 mx-auto" />
                    <p class="text-xs font-bold text-slate-900">This support ticket is closed and resolved.</p>
                    <p class="text-[11px] text-slate-500">Need further help? Please open a new support ticket.</p>
                </div>

            </div>

            <!-- Right 1-Col: Ticket Details & Metadata Sidebar -->
            <div class="space-y-4">
                
                <!-- Ticket Metadata Card -->
                <div class="bg-white rounded-lg border border-slate-200 p-5 shadow-xs space-y-4">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900 pb-2 border-b border-slate-100">
                        Ticket Information
                    </h3>

                    <div class="space-y-3 text-xs">
                        <div class="flex justify-between items-center">
                            <span class="text-slate-500">Ticket Ref:</span>
                            <span class="font-mono font-bold text-slate-900">{{ ticket.ticket_no }}</span>
                        </div>

                        <div class="flex justify-between items-center">
                            <span class="text-slate-500">Department:</span>
                            <span class="font-bold text-slate-900">{{ ticket.department_label || 'Technical' }}</span>
                        </div>

                        <div class="flex justify-between items-center">
                            <span class="text-slate-500">Priority:</span>
                            <span 
                                class="px-2 py-0.5 rounded text-[10px] font-bold uppercase border"
                                :class="[
                                    ticket.priority === 'critical' ? 'bg-rose-50 text-rose-700 border-rose-200' :
                                    ticket.priority === 'high' ? 'bg-amber-50 text-amber-700 border-amber-200' :
                                    ticket.priority === 'medium' ? 'bg-blue-50 text-blue-700 border-blue-200' :
                                    'bg-slate-50 text-slate-600 border-slate-200'
                                ]"
                            >
                                {{ ticket.priority }}
                            </span>
                        </div>

                        <div class="flex justify-between items-center">
                            <span class="text-slate-500">Status:</span>
                            <span 
                                class="px-2 py-0.5 rounded text-[10px] font-bold border capitalize"
                                :class="[
                                    ticket.status === 'open' || ticket.status === 'waiting' ? 'bg-amber-50 text-amber-700 border-amber-200' :
                                    ticket.status === 'answered' ? 'bg-violet-50 text-violet-700 border-violet-200' :
                                    'bg-slate-50 text-slate-600 border-slate-200'
                                ]"
                            >
                                {{ ticket.status === 'waiting' ? 'Waiting on Staff' : ticket.status === 'answered' ? 'Staff Replied' : ticket.status }}
                            </span>
                        </div>

                        <div class="flex justify-between items-center pt-2 border-t border-slate-100">
                            <span class="text-slate-500">Created:</span>
                            <span class="font-mono text-slate-700 text-[11px]">{{ ticket.formatted_created_at || formatDate(ticket.created_at) }}</span>
                        </div>

                        <div class="flex justify-between items-center">
                            <span class="text-slate-500">Last Activity:</span>
                            <span class="font-mono text-slate-700 text-[11px]">{{ ticket.formatted_updated_at || formatDate(ticket.updated_at) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Support SLA Card -->
                <div class="bg-gradient-to-br from-blue-50/70 to-indigo-50/50 rounded-lg border border-blue-100 p-5 shadow-xs space-y-2.5">
                    <span class="text-xs font-bold uppercase tracking-wider text-blue-700 block">24/7 Priority Support</span>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        DeepTouch operations engineers handle all incoming inquiries with prompt troubleshooting and SLA guarantees.
                    </p>
                    <div class="pt-2 border-t border-blue-100 text-[11px] text-slate-500 flex items-center gap-1.5">
                        <ShieldCheckIcon class="w-4 h-4 text-emerald-600 shrink-0" />
                        <span>Email: help@deeptouchit.com</span>
                    </div>
                </div>

            </div>

        </div>
        </div>
    </AuthenticatedLayout>
</template>

