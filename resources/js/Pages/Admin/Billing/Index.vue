<script setup>
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { CreditCardIcon, BanknotesIcon, ArrowTrendingUpIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    invoices: {
        type: [Object, Array],
        default: () => ({ data: [] })
    },
    payments: {
        type: Array,
        default: () => []
    },
    totalRevenue: {
        type: Number,
        default: 0
    }
})

const invoiceList = computed(() => {
    if (Array.isArray(props.invoices)) return props.invoices
    if (props.invoices?.data && Array.isArray(props.invoices.data)) return props.invoices.data
    return []
})
</script>

<template>
    <Head title="Billing & Invoices - DeepTouch Host hPanel" />

    <AdminLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">Billing, Invoices & Gateways</h1>
                    <p class="text-xs text-slate-500 font-medium mt-0.5">Overview of bKash, Nagad, SSLCommerz, Stripe payments and automated invoices</p>
                </div>
            </div>
        </template>

        <div class="max-w-7xl mx-auto space-y-6">
            <!-- Revenue Stats -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                <div class="bg-white border border-slate-200 p-6 rounded-3xl shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase text-slate-400">Total Cleared Revenue</p>
                        <p class="text-3xl font-black text-slate-900 mt-1">৳{{ totalRevenue }}</p>
                        <span class="text-[11px] text-emerald-600 font-semibold mt-1 block">● Real-time BDT Gateway</span>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center">
                        <BanknotesIcon class="w-6 h-6" />
                    </div>
                </div>

                <div class="bg-white border border-slate-200 p-6 rounded-3xl shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase text-slate-400">Supported Gateways</p>
                        <p class="text-xl font-black text-slate-900 mt-1">bKash, SSL, Stripe</p>
                        <span class="text-[11px] text-violet-600 font-semibold mt-1 block">Tokenized API Mode</span>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-violet-50 text-violet-600 flex items-center justify-center">
                        <CreditCardIcon class="w-6 h-6" />
                    </div>
                </div>

                <div class="bg-white border border-slate-200 p-6 rounded-3xl shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase text-slate-400">Auto Invoice Scheduler</p>
                        <p class="text-xl font-black text-emerald-600 mt-1">Daily 04:00 AM</p>
                        <span class="text-[11px] text-slate-400 mt-1 block">7-Day Advance Notice</span>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <ArrowTrendingUpIcon class="w-6 h-6" />
                    </div>
                </div>
            </div>

            <!-- Invoices Table -->
            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 font-bold text-slate-900">
                    Client Invoice Ledger
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase font-black tracking-wider">
                            <tr>
                                <th class="py-3.5 px-6">Invoice #</th>
                                <th class="py-3.5 px-6">Client</th>
                                <th class="py-3.5 px-6">Amount</th>
                                <th class="py-3.5 px-6">Due Date</th>
                                <th class="py-3.5 px-6">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium">
                            <tr v-if="invoiceList.length === 0">
                                <td colspan="5" class="py-8 text-center text-slate-400">
                                    No invoices generated yet.
                                </td>
                            </tr>
                            <tr v-for="inv in invoiceList" :key="inv.id" class="hover:bg-slate-50/80 transition">
                                <td class="py-4 px-6 font-mono font-bold text-violet-600">
                                    {{ inv.invoice_no }}
                                </td>
                                <td class="py-4 px-6 font-bold text-slate-800">
                                    {{ inv.user?.username || 'Client' }}
                                </td>
                                <td class="py-4 px-6 font-black text-slate-900">
                                    ৳{{ inv.total_amount }}
                                </td>
                                <td class="py-4 px-6 font-mono text-slate-500">
                                    {{ inv.due_date }}
                                </td>
                                <td class="py-4 px-6">
                                    <span 
                                        :class="[inv.status === 'paid' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-amber-50 text-amber-700 border-amber-200']"
                                        class="px-2.5 py-1 rounded-full text-[11px] font-bold capitalize border"
                                    >
                                        {{ inv.status }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
