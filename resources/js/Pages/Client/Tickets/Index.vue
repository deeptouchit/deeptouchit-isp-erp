<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import { 
    LifebuoyIcon, 
    PlusIcon, 
    ArrowRightIcon,
    MagnifyingGlassIcon,
    ChatBubbleLeftRightIcon,
    CheckCircleIcon,
    ClockIcon,
    ClipboardDocumentIcon,
    CheckIcon,
    ShieldCheckIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    tickets: {
        type: Object,
        default: () => ({ data: [], links: [] })
    },
    stats: {
        type: Object,
        default: () => ({
            total_count: 0,
            open_count: 0,
            answered_count: 0,
            closed_count: 0
        })
    },
    filters: {
        type: Object,
        default: () => ({
            status: 'all',
            search: ''
        })
    }
})

// Filter state
const currentStatus = ref(props.filters.status || 'all')
const searchQuery = ref(props.filters.search || '')

const applyFilter = (status) => {
    currentStatus.value = status
    router.get(route('tickets.index'), {
        status: status === 'all' ? null : status,
        search: searchQuery.value || null
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    })
}

let searchTimer = null
const onSearchInput = () => {
    clearTimeout(searchTimer)
    searchTimer = setTimeout(() => {
        router.get(route('tickets.index'), {
            status: currentStatus.value === 'all' ? null : currentStatus.value,
            search: searchQuery.value || null
        }, {
            preserveState: true,
            preserveScroll: true,
            replace: true
        })
    }, 350)
}

// Copy Feedback
const copiedField = ref(null)
const copyToClipboard = (text, fieldName) => {
    navigator.clipboard.writeText(text)
    copiedField.value = fieldName
    setTimeout(() => {
        copiedField.value = null
    }, 2000)
}
</script>

<template>
    <Head title="Support Tickets - DeepTouch Host" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto space-y-4">
            <!-- 1. Smart Minimal Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Support & Helpdesk', href: '#' },
                    { label: 'Support Tickets' }
                ]"
            >
                <template #actions>
                    <Link 
                        :href="route('tickets.create')" 
                        class="px-2.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <PlusIcon class="w-3.5 h-3.5 stroke-[2.5]" />
                        <span>Open Support Ticket</span>
                    </Link>
                </template>
            </PageHeader>

            <!-- ========================================================= -->
            <!-- 1. TOP SUPPORT METRICS CARDS (3-COLUMN GRID)              -->
            <!-- ========================================================= -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                
                <!-- Card 1: Active Inquiries -->
                <div class="bg-white border border-slate-200 rounded-lg p-5 shadow-xs flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Active Inquiries</span>
                            <span 
                                class="text-[11px] font-bold px-2 py-0.5 rounded border"
                                :class="stats.open_count > 0 ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-slate-50 text-slate-600 border-slate-200'"
                            >
                                {{ stats.open_count }} In Progress
                            </span>
                        </div>
                        <div class="mt-2.5 flex items-baseline gap-2">
                            <span class="text-2xl font-black text-slate-900">{{ stats.open_count }}</span>
                            <span class="text-xs font-bold text-slate-400">Open Tickets</span>
                        </div>
                    </div>
                    <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                        <span class="text-slate-500 flex items-center gap-1 text-[11px]">
                            <ClockIcon class="w-3.5 h-3.5 text-blue-600" />
                            <span>Response SLA: &lt; 15 Mins</span>
                        </span>
                        <span class="font-bold text-blue-600">Active Priority</span>
                    </div>
                </div>

                <!-- Card 2: Resolved Tickets -->
                <div class="bg-white border border-slate-200 rounded-lg p-5 shadow-xs flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Resolved Inquiries</span>
                            <span class="text-[11px] font-bold px-2 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded">
                                {{ stats.closed_count }} Closed
                            </span>
                        </div>
                        <div class="mt-2.5 flex items-baseline gap-2">
                            <span class="text-2xl font-black text-emerald-600">{{ stats.closed_count }}</span>
                            <span class="text-xs font-bold text-slate-400">Successfully Solved</span>
                        </div>
                    </div>
                    <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                        <span class="flex items-center gap-1 text-[11px]">
                            <CheckCircleIcon class="w-3.5 h-3.5 text-emerald-600" />
                            <span>100% Satisfaction SLA</span>
                        </span>
                        <span class="font-bold text-slate-700">Reconciled</span>
                    </div>
                </div>

                <!-- Card 3: 24/7 Dedicated Support -->
                <div class="bg-gradient-to-br from-blue-50/70 to-indigo-50/50 border border-blue-100 rounded-lg p-5 shadow-xs flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-blue-700">Dedicated Support</span>
                            <span class="text-[10px] font-bold px-2 py-0.5 bg-blue-100 text-blue-800 rounded">
                                24/7 Available
                            </span>
                        </div>
                        <p class="text-xs text-slate-600 mt-2 font-medium">
                            Cloud infrastructure engineers ready to assist with PHP, MySQL, DNS, SSL & Email configurations.
                        </p>
                    </div>
                    <div class="mt-3 pt-3 border-t border-blue-100 flex items-center justify-between text-xs text-slate-500">
                        <span class="flex items-center gap-1 text-[11px]">
                            <ShieldCheckIcon class="w-3.5 h-3.5 text-emerald-600" />
                            <span>help@deeptouchit.com</span>
                        </span>
                        <span class="font-bold text-slate-700">Direct Desk</span>
                    </div>
                </div>

            </div>

            <!-- ========================================================= -->
            <!-- 2. TICKETS TABLE & FILTERS                                -->
            <!-- ========================================================= -->
            <div class="bg-white border border-slate-200 rounded-lg shadow-xs overflow-hidden">
                <div class="p-4 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/50">
                    
                    <!-- Status Tabs -->
                    <div class="flex items-center gap-1.5 overflow-x-auto">
                        <button 
                            @click="applyFilter('all')"
                            class="px-3 py-1.5 text-xs font-bold rounded-[3px] transition cursor-pointer font-mono"
                            :class="currentStatus === 'all' ? 'bg-blue-600 text-white shadow-2xs' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50'"
                        >
                            ALL ({{ stats.total_count }})
                        </button>
                        <button 
                            @click="applyFilter('open')"
                            class="px-3 py-1.5 text-xs font-bold rounded-[3px] transition cursor-pointer font-mono"
                            :class="currentStatus === 'open' ? 'bg-blue-600 text-white shadow-2xs' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50'"
                        >
                            OPEN ({{ stats.open_count }})
                        </button>
                        <button 
                            @click="applyFilter('in_progress')"
                            class="px-3 py-1.5 text-xs font-bold rounded-[3px] transition cursor-pointer font-mono"
                            :class="currentStatus === 'in_progress' ? 'bg-blue-600 text-white shadow-2xs' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50'"
                        >
                            IN PROGRESS ({{ stats.in_progress_count || 0 }})
                        </button>
                        <button 
                            @click="applyFilter('answered')"
                            class="px-3 py-1.5 text-xs font-bold rounded-[3px] transition cursor-pointer font-mono"
                            :class="currentStatus === 'answered' ? 'bg-blue-600 text-white shadow-2xs' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50'"
                        >
                            ANSWERED ({{ stats.answered_count }})
                        </button>
                        <button 
                            @click="applyFilter('closed')"
                            class="px-3 py-1.5 text-xs font-bold rounded-[3px] transition cursor-pointer font-mono"
                            :class="currentStatus === 'closed' ? 'bg-blue-600 text-white shadow-2xs' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50'"
                        >
                            CLOSED ({{ stats.closed_count }})
                        </button>
                    </div>

                    <!-- Search Input -->
                    <div class="relative w-full sm:w-64">
                        <MagnifyingGlassIcon class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2" />
                        <input 
                            v-model="searchQuery"
                            @input="onSearchInput"
                            type="text"
                            placeholder="Search ticket # or subject..."
                            class="w-full bg-white border border-slate-200 rounded text-xs pl-8 pr-2.5 py-1.5 focus:ring-1 focus:ring-blue-600 focus:outline-hidden"
                        />
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="tickets.data.length === 0" class="p-12 text-center">
                    <div class="w-12 h-12 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center mx-auto mb-3 border border-blue-100">
                        <LifebuoyIcon class="w-6 h-6" />
                    </div>
                    <h3 class="text-sm font-bold text-slate-900 mb-1">
                        {{ searchQuery ? 'No tickets match your search' : 'No Support Tickets Found' }}
                    </h3>
                    <p class="text-xs text-slate-500 max-w-sm mx-auto mb-5">
                        {{ searchQuery ? 'Try adjusting your search criteria.' : 'Have any questions regarding domain delegation, databases, or PHP extensions? Open a ticket to speak directly with our engineers.' }}
                    </p>
                    <Link 
                        v-if="!searchQuery"
                        :href="route('tickets.create')" 
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded text-xs font-bold shadow-xs inline-flex items-center gap-1.5"
                    >
                        <PlusIcon class="w-4 h-4 stroke-[2.5]" />
                        <span>Open Your First Ticket</span>
                    </Link>
                </div>

                <!-- Tickets Table -->
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase font-bold tracking-wider text-[10px]">
                            <tr>
                                <th class="py-3 px-4">Ticket #</th>
                                <th class="py-3 px-4">Department</th>
                                <th class="py-3 px-4">Subject</th>
                                <th class="py-3 px-4">Priority</th>
                                <th class="py-3 px-4">Status</th>
                                <th class="py-3 px-4">Last Activity</th>
                                <th class="py-3 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium">
                            <tr v-for="ticket in tickets.data" :key="ticket.id" class="hover:bg-slate-50/70 transition">
                                <!-- Ticket # -->
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-1.5">
                                        <Link 
                                            :href="route('tickets.show', ticket.id)"
                                            class="font-mono font-bold text-blue-600 hover:text-blue-800 hover:underline"
                                        >
                                            {{ ticket.ticket_no }}
                                        </Link>
                                        <button 
                                            @click="copyToClipboard(ticket.ticket_no, 'tkt_' + ticket.id)"
                                            class="text-slate-400 hover:text-slate-600 cursor-pointer"
                                            title="Copy Ticket #"
                                        >
                                            <CheckIcon v-if="copiedField === 'tkt_' + ticket.id" class="w-3 h-3 text-emerald-600" />
                                            <ClipboardDocumentIcon v-else class="w-3 h-3" />
                                        </button>
                                    </div>
                                </td>

                                <!-- Department -->
                                <td class="py-3 px-4">
                                    <span class="px-2 py-0.5 bg-slate-100 text-slate-700 rounded text-[10px] font-bold border border-slate-200">
                                        {{ ticket.department_label || 'Technical' }}
                                    </span>
                                </td>

                                <!-- Subject -->
                                <td class="py-3 px-4">
                                    <Link 
                                        :href="route('tickets.show', ticket.id)"
                                        class="font-bold text-slate-900 hover:text-blue-600 transition"
                                    >
                                        {{ ticket.subject }}
                                    </Link>
                                </td>

                                <!-- Priority -->
                                <td class="py-3 px-4">
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
                                </td>

                                <!-- Status -->
                                <td class="py-3 px-4">
                                    <span 
                                        class="px-2 py-0.5 rounded text-[10px] font-bold border capitalize inline-flex items-center gap-1"
                                        :class="[
                                            ticket.status === 'open' ? 'bg-amber-50 text-amber-700 border-amber-200' :
                                            ticket.status === 'waiting' ? 'bg-amber-50 text-amber-700 border-amber-200' :
                                            ticket.status === 'answered' ? 'bg-violet-50 text-violet-700 border-violet-200' :
                                            'bg-slate-50 text-slate-600 border-slate-200'
                                        ]"
                                    >
                                        <span 
                                            class="w-1.5 h-1.5 rounded-full" 
                                            :class="[
                                                ticket.status === 'open' || ticket.status === 'waiting' ? 'bg-amber-600' :
                                                ticket.status === 'answered' ? 'bg-violet-600' :
                                                'bg-slate-400'
                                            ]"
                                        ></span>
                                        <span>{{ ticket.status === 'waiting' ? 'Waiting on Staff' : ticket.status === 'answered' ? 'Staff Replied' : ticket.status }}</span>
                                    </span>
                                </td>

                                <!-- Last Activity -->
                                <td class="py-3 px-4 text-slate-500 font-mono text-[11px] whitespace-nowrap">
                                    {{ ticket.formatted_updated_at || ticket.updated_at }}
                                </td>

                                <!-- Actions -->
                                <td class="py-3 px-4 text-right">
                                    <Link 
                                        :href="route('tickets.show', ticket.id)"
                                        class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded text-[11px] font-bold transition inline-flex items-center gap-1"
                                    >
                                        <span>View Thread</span>
                                        <ArrowRightIcon class="w-3 h-3 stroke-[2.5]" />
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="tickets.links && tickets.links.length > 3" class="p-4 border-t border-slate-100 flex items-center justify-between">
                    <span class="text-xs text-slate-500">
                        Showing {{ tickets.from }} to {{ tickets.to }} of {{ tickets.total }} tickets
                    </span>
                    <div class="flex items-center gap-1">
                        <Link 
                            v-for="(link, i) in tickets.links" 
                            :key="i"
                            :href="link.url || '#'"
                            class="px-2.5 py-1 text-xs rounded font-bold transition"
                            :class="[
                                link.active ? 'bg-blue-600 text-white' : 'bg-slate-50 text-slate-600 hover:bg-slate-100',
                                !link.url ? 'opacity-40 cursor-not-allowed pointer-events-none' : ''
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>

