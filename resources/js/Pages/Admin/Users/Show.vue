<script setup>
import { ref } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import InfoCardsGrid from '@/Components/UI/InfoCardsGrid.vue'
import InfoCard from '@/Components/UI/InfoCard.vue'
import {
    ArrowLeftIcon,
    PencilSquareIcon,
    KeyIcon,
    PauseIcon,
    PlayIcon,
    GlobeAltIcon,
    CreditCardIcon,
    LifebuoyIcon,
    ShieldCheckIcon,
    BriefcaseIcon,
    ClockIcon,
    CheckCircleIcon,
    XMarkIcon,
    ArrowRightEndOnRectangleIcon,
    ArrowTopRightOnSquareIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
})

const activeTab = ref('subscriptions')

// Flash Feedback
const feedbackMsg = ref('')

// Toggle Status
const toggleStatus = () => {
    router.post(route('admin.users.toggle-status', props.user.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = `User status updated to ${props.user.status === 'active' ? 'suspended' : 'active'}.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// Impersonate User
const impersonateUser = () => {
    if (confirm(`Login to client dashboard as '${props.user.name}' (${props.user.email})?`)) {
        router.post(route('admin.users.impersonate', props.user.id))
    }
}

// Password Reset Modal State
const showPasswordModal = ref(false)
const passwordForm = useForm({ password: '' })

const submitPasswordReset = () => {
    passwordForm.post(route('admin.users.reset-password', props.user.id), {
        preserveScroll: true,
        onSuccess: () => {
            showPasswordModal.value = false
            feedbackMsg.value = `Password updated successfully.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        },
    })
}

// Helpers
const formatDate = (dateStr) => {
    if (!dateStr) return 'Never'
    try {
        const d = new Date(dateStr)
        if (isNaN(d.getTime())) return dateStr
        return new Intl.DateTimeFormat('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
        }).format(d)
    } catch {
        return dateStr
    }
}
</script>

<template>
    <Head :title="`Customer 360: ${user.name} - DeepTouchHost`" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Customers', href: '#' },
                    { label: 'All Customers', href: route('admin.users.index') },
                    { label: user.name || 'Customer 360' }
                ]"
            >
                <template #actions>
                    <Link 
                        :href="route('admin.users.index')" 
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <ArrowLeftIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>Back</span>
                    </Link>

                    <button
                        v-if="user.id !== $page.props.auth?.user?.id && user.role === 'client'"
                        type="button"
                        @click="impersonateUser"
                        class="px-2.5 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-emerald-200 shadow-2xs transition cursor-pointer"
                        title="Login as client"
                    >
                        <ArrowRightEndOnRectangleIcon class="w-3.5 h-3.5" />
                        <span>Login as Client</span>
                    </button>

                    <button
                        type="button"
                        @click="toggleStatus"
                        :class="user.status === 'active' ? 'bg-amber-50 text-amber-700 border-amber-200 hover:bg-amber-100' : 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100'"
                        class="px-2.5 py-1.5 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border shadow-2xs transition cursor-pointer"
                    >
                        <PauseIcon v-if="user.status === 'active'" class="w-3.5 h-3.5" />
                        <PlayIcon v-else class="w-3.5 h-3.5" />
                        <span>{{ user.status === 'active' ? 'Suspend' : 'Activate' }}</span>
                    </button>

                    <button
                        type="button"
                        @click="showPasswordModal = true"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <KeyIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>Reset Password</span>
                    </button>

                    <Link
                        :href="route('admin.users.edit', user.id)"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] text-xs flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <PencilSquareIcon class="w-3.5 h-3.5" />
                        <span>Edit Account</span>
                    </Link>
                </template>
            </PageHeader>

            <!-- Feedback Banner -->
            <div v-if="feedbackMsg" class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-3 py-2 rounded-[3px] text-xs font-bold flex items-center justify-between shadow-2xs">
                <div class="flex items-center gap-2">
                    <CheckCircleIcon class="w-4 h-4 text-emerald-600" />
                    <span>{{ feedbackMsg }}</span>
                </div>
                <button @click="feedbackMsg = ''" class="text-emerald-600 hover:text-emerald-800 text-xs cursor-pointer">✕</button>
            </div>

            <!-- 2. Ultra-Compact KPI Info Cards (56px) -->
            <InfoCardsGrid :cols="4">
                <InfoCard
                    title="Hosting Accounts"
                    :value="String(user.subscriptions?.length || 0)"
                    badge="VHosts"
                    badgeType="info"
                    color="blue"
                    :icon="GlobeAltIcon"
                />

                <InfoCard
                    title="Billing Invoices"
                    :value="String(user.invoices?.length || 0)"
                    badge="Invoices"
                    badgeType="success"
                    color="emerald"
                    :icon="CreditCardIcon"
                />

                <InfoCard
                    title="Support Tickets"
                    :value="String(user.tickets?.length || 0)"
                    badge="Tickets"
                    badgeType="info"
                    color="sky"
                    :icon="LifebuoyIcon"
                />

                <InfoCard
                    title="Security Vault"
                    :value="user.two_factor_confirmed_at ? '2FA Active' : 'Standard'"
                    :badge="user.two_factor_confirmed_at ? 'Protected' : 'Standard'"
                    :badgeType="user.two_factor_confirmed_at ? 'success' : 'neutral'"
                    color="purple"
                    :icon="ShieldCheckIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Profile Summary Card -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] p-4 shadow-2xs text-xs">
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div>
                        <span class="text-slate-400 font-bold uppercase text-[10px]">Access Role</span>
                        <div class="mt-0.5">
                            <span 
                                :class="[
                                    user.role === 'admin' ? 'bg-purple-50 text-purple-700 border-purple-200' :
                                    user.role === 'reseller' ? 'bg-sky-50 text-sky-700 border-sky-200' :
                                    'bg-slate-100 text-slate-700 border-slate-200'
                                ]"
                                class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border inline-block font-mono"
                            >
                                {{ user.role }}
                            </span>
                        </div>
                    </div>

                    <div>
                        <span class="text-slate-400 font-bold uppercase text-[10px]">Account Status</span>
                        <div class="mt-0.5">
                            <span 
                                :class="user.status === 'active' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'"
                                class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold capitalize border inline-block"
                            >
                                ● {{ user.status }}
                            </span>
                        </div>
                    </div>

                    <div>
                        <span class="text-slate-400 font-bold uppercase text-[10px]">Contact Email</span>
                        <p class="font-mono text-slate-800 font-bold mt-0.5 text-[11px] truncate">{{ user.email }}</p>
                    </div>

                    <div>
                        <span class="text-slate-400 font-bold uppercase text-[10px]">Registered On</span>
                        <p class="font-mono text-slate-800 font-bold mt-0.5 text-[11px]">{{ formatDate(user.created_at) }}</p>
                    </div>
                </div>
            </div>

            <!-- 4. Tabbed Workloads View -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <!-- Tab Headers -->
                <div class="flex items-center border-b border-slate-200 bg-slate-50/70 px-3">
                    <button
                        type="button"
                        @click="activeTab = 'subscriptions'"
                        :class="activeTab === 'subscriptions' ? 'border-blue-600 text-blue-600 font-bold bg-white' : 'border-transparent text-slate-600 hover:text-slate-900'"
                        class="py-2.5 px-3 text-xs border-b-2 transition cursor-pointer flex items-center gap-1.5"
                    >
                        <GlobeAltIcon class="w-3.5 h-3.5" />
                        <span>Hosting Subscriptions ({{ user.subscriptions?.length || 0 }})</span>
                    </button>

                    <button
                        v-if="user.role === 'reseller'"
                        type="button"
                        @click="activeTab = 'clients'"
                        :class="activeTab === 'clients' ? 'border-blue-600 text-blue-600 font-bold bg-white' : 'border-transparent text-slate-600 hover:text-slate-900'"
                        class="py-2.5 px-3 text-xs border-b-2 transition cursor-pointer flex items-center gap-1.5"
                    >
                        <BriefcaseIcon class="w-3.5 h-3.5" />
                        <span>Reseller Clients ({{ user.clients?.length || 0 }})</span>
                    </button>

                    <button
                        type="button"
                        @click="activeTab = 'invoices'"
                        :class="activeTab === 'invoices' ? 'border-blue-600 text-blue-600 font-bold bg-white' : 'border-transparent text-slate-600 hover:text-slate-900'"
                        class="py-2.5 px-3 text-xs border-b-2 transition cursor-pointer flex items-center gap-1.5"
                    >
                        <CreditCardIcon class="w-3.5 h-3.5" />
                        <span>Invoices ({{ user.invoices?.length || 0 }})</span>
                    </button>

                    <button
                        type="button"
                        @click="activeTab = 'tickets'"
                        :class="activeTab === 'tickets' ? 'border-blue-600 text-blue-600 font-bold bg-white' : 'border-transparent text-slate-600 hover:text-slate-900'"
                        class="py-2.5 px-3 text-xs border-b-2 transition cursor-pointer flex items-center gap-1.5"
                    >
                        <LifebuoyIcon class="w-3.5 h-3.5" />
                        <span>Support Tickets ({{ user.tickets?.length || 0 }})</span>
                    </button>

                    <button
                        type="button"
                        @click="activeTab = 'activity'"
                        :class="activeTab === 'activity' ? 'border-blue-600 text-blue-600 font-bold bg-white' : 'border-transparent text-slate-600 hover:text-slate-900'"
                        class="py-2.5 px-3 text-xs border-b-2 transition cursor-pointer flex items-center gap-1.5"
                    >
                        <ClockIcon class="w-3.5 h-3.5" />
                        <span>Activity Audit</span>
                    </button>
                </div>

                <!-- Tab 1: Subscriptions -->
                <div v-if="activeTab === 'subscriptions'" class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Domain Name</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">System Username</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Plan / Package</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Server Node</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Status</th>
                                <th class="py-2.5 px-3 w-16">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(sub, i) in user.subscriptions" :key="sub.id" class="hover:bg-blue-50/30 transition">
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">{{ i + 1 }}</td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left font-bold text-slate-900 whitespace-nowrap">
                                    <a :href="`http://${sub.domain}`" target="_blank" class="hover:text-blue-600 inline-flex items-center gap-1">
                                        <span>{{ sub.domain }}</span>
                                        <ArrowTopRightOnSquareIcon class="w-3 h-3 text-slate-400" />
                                    </a>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left font-mono text-slate-600 whitespace-nowrap">@{{ sub.username }}</td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap">{{ sub.plan?.name || 'Default Plan' }}</td>
                                <td class="py-2.5 px-3 border-r border-slate-100 font-mono text-slate-500 whitespace-nowrap">{{ sub.server?.name || 'Main Node' }}</td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span :class="sub.status === 'active' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-amber-50 text-amber-700 border-amber-200'" class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold capitalize border">
                                        {{ sub.status }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <Link :href="route('admin.hosting.accounts')" class="text-blue-600 hover:text-blue-800 font-bold text-xs">
                                        Manage
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="!user.subscriptions || user.subscriptions.length === 0">
                                <td colspan="7" class="py-8 text-center text-slate-400">No active subscriptions provisioned.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Tab 2: Reseller Clients -->
                <div v-if="activeTab === 'clients'" class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Client Name</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Email Address</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Status</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Registered</th>
                                <th class="py-2.5 px-3 w-16">Profile</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(client, i) in user.clients" :key="client.id" class="hover:bg-blue-50/30 transition">
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">{{ i + 1 }}</td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left font-bold text-slate-900 whitespace-nowrap">{{ client.name }}</td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left font-mono text-slate-600 whitespace-nowrap">{{ client.email }}</td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span :class="client.status === 'active' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-amber-50 text-amber-700 border-amber-200'" class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold capitalize border">
                                        {{ client.status }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 font-mono text-slate-400 whitespace-nowrap">{{ formatDate(client.created_at) }}</td>
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <Link :href="route('admin.users.show', client.id)" class="text-blue-600 hover:text-blue-800 font-bold text-xs">
                                        View 360
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="!user.clients || user.clients.length === 0">
                                <td colspan="6" class="py-8 text-center text-slate-400">No client sub-accounts managed under this reseller.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Tab 3: Invoices -->
                <div v-if="activeTab === 'invoices'" class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Invoice No</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Total Amount</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Status</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Due Date</th>
                                <th class="py-2.5 px-3 w-16">Invoice</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(inv, i) in user.invoices" :key="inv.id" class="hover:bg-blue-50/30 transition">
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">{{ i + 1 }}</td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left font-mono font-bold text-slate-900 whitespace-nowrap">#INV-{{ inv.id }}</td>
                                <td class="py-2.5 px-3 border-r border-slate-100 font-bold text-slate-900 whitespace-nowrap">${{ Number(inv.total || 0).toFixed(2) }}</td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span :class="inv.status === 'paid' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-amber-50 text-amber-700 border-amber-200'" class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border">
                                        {{ inv.status }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 font-mono text-slate-500 whitespace-nowrap">{{ formatDate(inv.due_date) }}</td>
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <Link :href="route('admin.billing.invoices')" class="text-blue-600 hover:text-blue-800 font-bold text-xs">
                                        Open
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="!user.invoices || user.invoices.length === 0">
                                <td colspan="6" class="py-8 text-center text-slate-400">No invoices generated for this user account.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Tab 4: Support Tickets -->
                <div v-if="activeTab === 'tickets'" class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Subject</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Department</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Priority</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Status</th>
                                <th class="py-2.5 px-3 w-16">Ticket</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(ticket, i) in user.tickets" :key="ticket.id" class="hover:bg-blue-50/30 transition">
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">{{ i + 1 }}</td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left font-bold text-slate-900 whitespace-nowrap">{{ ticket.subject }}</td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-600 whitespace-nowrap">{{ ticket.department || 'Technical' }}</td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span :class="ticket.priority === 'urgent' ? 'bg-rose-50 text-rose-700' : 'bg-slate-100 text-slate-700'" class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase">
                                        {{ ticket.priority || 'Normal' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span :class="ticket.status === 'closed' ? 'bg-slate-100 text-slate-600' : 'bg-emerald-50 text-emerald-700 border-emerald-200'" class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border">
                                        {{ ticket.status }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <Link :href="route('admin.support.tickets')" class="text-blue-600 hover:text-blue-800 font-bold text-xs">
                                        View
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="!user.tickets || user.tickets.length === 0">
                                <td colspan="6" class="py-8 text-center text-slate-400">No support tickets recorded for this customer.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Tab 5: Activity Logs -->
                <div v-if="activeTab === 'activity'" class="p-4 text-slate-500 text-xs text-center">
                    <p>Live session traces and audit records are synchronized with the central audit ledger.</p>
                    <Link :href="route('admin.customers.activity')" class="mt-2 inline-flex items-center gap-1 font-bold text-blue-600 hover:text-blue-800">
                        <span>Open Customer Activity Log</span>
                        <ArrowTopRightOnSquareIcon class="w-3.5 h-3.5" />
                    </Link>
                </div>
            </div>
        </div>

        <!-- PASSWORD RESET MODAL -->
        <div v-if="showPasswordModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <KeyIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Reset Password: {{ user.name }}
                        </h3>
                    </div>
                    <button @click="showPasswordModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitPasswordReset" class="p-4 space-y-3.5 text-xs">
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">New Password <span class="text-rose-500">*</span></label>
                        <input
                            v-model="passwordForm.password"
                            type="password"
                            required
                            minlength="8"
                            placeholder="Enter secure new password"
                            class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 transition-all shadow-2xs"
                        />
                        <span v-if="passwordForm.errors.password" class="text-rose-600 text-[10.5px] font-semibold block">{{ passwordForm.errors.password }}</span>
                    </div>

                    <div class="pt-2 flex items-center justify-end gap-2 border-t border-slate-100">
                        <button
                            type="button"
                            @click="showPasswordModal = false"
                            class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="passwordForm.processing"
                            class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                        >
                            {{ passwordForm.processing ? 'Saving...' : 'Save Password' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
