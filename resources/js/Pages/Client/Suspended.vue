<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { 
    ExclamationTriangleIcon,
    ShieldExclamationIcon,
    BanknotesIcon,
    LifebuoyIcon,
    PhoneIcon,
    EnvelopeIcon,
    ChatBubbleBottomCenterTextIcon,
    ArrowLeftOnRectangleIcon,
    ArrowTopRightOnSquareIcon,
    ServerIcon,
    CheckCircleIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    user: {
        type: Object,
        default: () => ({})
    },
    subscription: {
        type: Object,
        default: () => ({})
    },
    unpaidInvoices: {
        type: Array,
        default: () => []
    },
    totalDue: {
        type: Number,
        default: 0
    },
    tickets: {
        type: Array,
        default: () => []
    },
    supportInfo: {
        type: Object,
        default: () => ({
            phone: '+880 1977-799300',
            email: 'support@deeptouchit.com',
            whatsapp: '8801977799300'
        })
    }
})
</script>

<template>
    <Head title="Account Suspended - DeepTouchHost" />

    <div class="min-h-screen bg-slate-100 flex flex-col font-sans text-slate-800 antialiased selection:bg-rose-500 selection:text-white">
        <!-- Top Navigation Bar -->
        <header class="h-14 bg-white border-b border-[#E2E8F0] px-4 sm:px-8 flex items-center justify-between shadow-2xs">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-[4px] bg-rose-600 text-white flex items-center justify-center font-bold text-sm shadow-xs">
                    <ServerIcon class="w-4 h-4" />
                </div>
                <div>
                    <h1 class="text-sm font-black text-slate-900 tracking-tight">DeepTouchHost</h1>
                    <p class="text-[10px] text-slate-400 font-mono">Client Account Portal</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <div class="hidden sm:flex items-center gap-2 text-xs">
                    <span class="text-slate-500">Logged in as:</span>
                    <span class="font-bold text-slate-900">{{ user.name }}</span>
                </div>

                <Link 
                    :href="route('logout')" 
                    method="post" 
                    as="button" 
                    class="px-3 py-1.5 bg-slate-100 hover:bg-rose-50 hover:text-rose-700 text-slate-700 rounded-[3px] text-xs font-bold transition flex items-center gap-1.5 cursor-pointer border border-slate-200"
                >
                    <ArrowLeftOnRectangleIcon class="w-4 h-4" />
                    <span>Sign Out</span>
                </Link>
            </div>
        </header>

        <!-- Main Container -->
        <main class="flex-1 max-w-4xl w-full mx-auto p-4 sm:p-6 space-y-5">
            <!-- 1. Hero Suspension Banner -->
            <div class="bg-white rounded-[4px] border border-rose-200 shadow-2xs overflow-hidden">
                <div class="bg-rose-600 px-6 py-4 text-white flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-[4px] bg-white/10 flex items-center justify-center">
                            <ShieldExclamationIcon class="w-6 h-6 text-white" />
                        </div>
                        <div>
                            <h2 class="text-base font-bold">Hosting Account Suspended</h2>
                            <p class="text-xs text-rose-100 mt-0.5">Your hosting services and access permissions are temporarily restricted.</p>
                        </div>
                    </div>
                    <span class="px-2.5 py-1 bg-white text-rose-700 font-mono font-bold text-xs uppercase rounded-[3px] shadow-xs">
                        SUSPENDED
                    </span>
                </div>

                <!-- Account Snapshot -->
                <div class="p-6 bg-rose-50/50 border-b border-rose-100 grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                    <div class="space-y-1">
                        <span class="text-slate-500 block">Account Details:</span>
                        <p class="font-bold text-slate-900 text-sm">{{ user.name }}</p>
                        <p class="font-mono text-slate-600 text-[11px]">{{ user.email }}</p>
                        <span class="inline-block font-mono text-[10px] text-slate-500">ID #{{ user.id }}</span>
                    </div>

                    <div class="space-y-1">
                        <span class="text-slate-500 block">Hosting Domain:</span>
                        <p class="font-bold text-slate-900 text-sm">{{ subscription?.domain || 'somitysoft.com' }}</p>
                        <p class="text-slate-600 text-[11px]">Plan: {{ subscription?.plan?.name || 'Pro Hosting' }}</p>
                    </div>

                    <div class="space-y-1 sm:text-right">
                        <span class="text-slate-500 block">Total Outstanding Due:</span>
                        <p class="font-bold text-rose-700 text-lg font-mono">৳{{ Number(totalDue || 0).toFixed(2) }}</p>
                        <p class="text-[11px] text-slate-500">{{ unpaidInvoices.length }} Unpaid Invoice(s)</p>
                    </div>
                </div>

                <!-- Notice Explanation -->
                <div class="p-6 space-y-3">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900 font-mono">How to Reactivate Your Hosting Account:</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                        <div class="p-3 bg-white rounded-[3px] border border-slate-200 flex items-start gap-2.5">
                            <span class="w-5 h-5 rounded-[2px] bg-blue-50 text-blue-700 font-bold flex items-center justify-center shrink-0 text-[11px]">1</span>
                            <div>
                                <p class="font-bold text-slate-900">Clear Outstanding Invoices</p>
                                <p class="text-slate-500 text-[11px] mt-0.5">Pay any overdue invoices below using bKash, Nagad, or Credit Card for instant automated reactivation.</p>
                            </div>
                        </div>

                        <div class="p-3 bg-white rounded-[3px] border border-slate-200 flex items-start gap-2.5">
                            <span class="w-5 h-5 rounded-[2px] bg-blue-50 text-blue-700 font-bold flex items-center justify-center shrink-0 text-[11px]">2</span>
                            <div>
                                <p class="font-bold text-slate-900">Contact Support Desk</p>
                                <p class="text-slate-500 text-[11px] mt-0.5">If you have already made payment or believe this suspension is in error, reach our support team immediately.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Unpaid Invoices Section -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="p-4 border-b border-slate-100 flex items-center justify-between bg-[#F8FAFC]">
                    <div class="flex items-center gap-2">
                        <BanknotesIcon class="w-4 h-4 text-slate-700" />
                        <h3 class="text-xs font-bold uppercase font-mono text-slate-900">Outstanding Invoices</h3>
                    </div>
                    <Link 
                        :href="route('billing.invoices')" 
                        class="text-xs font-bold text-blue-600 hover:text-blue-800 flex items-center gap-1"
                    >
                        <span>View All Invoices</span>
                        <ArrowTopRightOnSquareIcon class="w-3.5 h-3.5" />
                    </Link>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-700">
                        <thead class="bg-[#F8FAFC] border-b border-[#E2E8F0] text-[11px] uppercase font-mono text-slate-500">
                            <tr>
                                <th class="px-4 py-2.5">Invoice #</th>
                                <th class="px-4 py-2.5">Issue Date</th>
                                <th class="px-4 py-2.5">Due Date</th>
                                <th class="px-4 py-2.5">Amount Due</th>
                                <th class="px-4 py-2.5 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="unpaidInvoices.length === 0">
                                <td colspan="5" class="px-4 py-6 text-center text-slate-400">
                                    No pending invoices found. Please contact support to review your suspension reason.
                                </td>
                            </tr>
                            <tr v-for="inv in unpaidInvoices" :key="inv.id" class="hover:bg-slate-50 transition">
                                <td class="px-4 py-2.5 font-mono font-bold text-slate-900">#{{ inv.invoice_no }}</td>
                                <td class="px-4 py-2.5 text-slate-600">{{ inv.issue_date ? inv.issue_date.split('T')[0] : 'N/A' }}</td>
                                <td class="px-4 py-2.5 font-bold text-rose-600">{{ inv.due_date ? inv.due_date.split('T')[0] : 'Overdue' }}</td>
                                <td class="px-4 py-2.5 font-mono font-bold text-slate-900">৳{{ Number(inv.due_amount || inv.total_amount).toFixed(2) }}</td>
                                <td class="px-4 py-2.5 text-right">
                                    <Link 
                                        :href="route('billing.invoice.show', inv.id)" 
                                        class="px-3 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded-[3px] text-xs font-bold transition shadow-2xs inline-flex items-center gap-1 cursor-pointer"
                                    >
                                        <span>Pay Now ➔</span>
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 3. Support & Assistance Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Open Ticket Card -->
                <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs p-5 space-y-3">
                    <div class="flex items-center gap-2">
                        <LifebuoyIcon class="w-5 h-5 text-blue-600" />
                        <h4 class="text-xs font-bold uppercase font-mono text-slate-900">Submit Support Ticket</h4>
                    </div>
                    <p class="text-xs text-slate-500">
                        Create an urgent ticket with our billing and engineering department to expedite account unsuspension.
                    </p>
                    <div class="pt-2">
                        <Link 
                            :href="route('tickets.create')" 
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold transition shadow-2xs inline-flex items-center gap-1.5 cursor-pointer"
                        >
                            <span>Open Support Ticket</span>
                            <ArrowTopRightOnSquareIcon class="w-3.5 h-3.5" />
                        </Link>
                    </div>
                </div>

                <!-- Emergency Contact Desk -->
                <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs p-5 space-y-3">
                    <h4 class="text-xs font-bold uppercase font-mono text-slate-900">Emergency Support Contacts</h4>
                    <div class="space-y-2 text-xs">
                        <a 
                            :href="'https://wa.me/' + supportInfo.whatsapp" 
                            target="_blank" 
                            class="flex items-center justify-between p-2 rounded-[3px] bg-emerald-50 text-emerald-800 border border-emerald-200 hover:bg-emerald-100 transition"
                        >
                            <span class="flex items-center gap-2 font-bold">
                                <ChatBubbleBottomCenterTextIcon class="w-4 h-4 text-emerald-600" />
                                <span>WhatsApp Emergency Desk</span>
                            </span>
                            <span class="font-mono text-[11px]">{{ supportInfo.phone }}</span>
                        </a>

                        <a 
                            :href="'mailto:' + supportInfo.email" 
                            class="flex items-center justify-between p-2 rounded-[3px] bg-slate-50 text-slate-700 border border-slate-200 hover:bg-slate-100 transition"
                        >
                            <span class="flex items-center gap-2 font-bold">
                                <EnvelopeIcon class="w-4 h-4 text-slate-500" />
                                <span>Email Support</span>
                            </span>
                            <span class="font-mono text-[11px]">{{ supportInfo.email }}</span>
                        </a>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="py-4 text-center text-xs text-slate-400 border-t border-slate-200 bg-white">
            <p>© {{ new Date().getFullYear() }} DeepTouchHost Enterprise Cloud Engine. All rights reserved.</p>
        </footer>
    </div>
</template>
