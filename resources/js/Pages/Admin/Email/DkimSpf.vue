<script setup>
import { ref, computed } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import RefreshButton from '@/Components/UI/RefreshButton.vue'
import InfoCardsGrid from '@/Components/UI/InfoCardsGrid.vue'
import InfoCard from '@/Components/UI/InfoCard.vue'
import DataTableFilter from '@/Components/UI/DataTableFilter.vue'
import RowActionDropdown from '@/Components/UI/RowActionDropdown.vue'

import {
    ShieldCheckIcon,
    KeyIcon,
    PencilSquareIcon,
    XMarkIcon,
    CheckIcon,
    DocumentDuplicateIcon,
    GlobeAltIcon,
    ServerIcon,
    ArrowLeftIcon,
    ArrowPathIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    domains: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({
            total_domains: 0,
            avg_score: 100,
            dkim_count: 0,
            spf_count: 0,
            dmarc_count: 0,
        }),
    },
    serverIp: {
        type: String,
        default: '127.0.0.1',
    },
})

// Toast Feedback
const feedbackMsg = ref('')

// Search & Filter
const search = ref('')

const filteredDomains = computed(() => {
    let list = props.domains || []

    if (search.value.trim()) {
        const q = search.value.toLowerCase()
        list = list.filter(d =>
            (d.domain || '').toLowerCase().includes(q) ||
            (d.spf_record || '').toLowerCase().includes(q) ||
            (d.dmarc_record || '').toLowerCase().includes(q) ||
            (d.subscription?.domain || '').toLowerCase().includes(q)
        )
    }

    return list
})

// 1. LIVE DNS & DKIM INSPECTOR MODAL
const showDnsModal = ref(false)
const selectedDomainForDns = ref(null)
const liveDnsResults = ref(null)
const isCheckingDns = ref(false)
const copiedField = ref('')

const openDnsModal = async (domain) => {
    selectedDomainForDns.value = domain
    liveDnsResults.value = null
    showDnsModal.value = true
    await checkLiveDns(domain)
}

const checkLiveDns = async (domain) => {
    isCheckingDns.value = true
    try {
        const res = await fetch(route('admin.email.dkim-spf.verify-dns', domain.id))
        const data = await res.json()
        if (data.success) {
            liveDnsResults.value = data.results
        }
    } catch (e) {
        console.error('DNS check error:', e)
    } finally {
        isCheckingDns.value = false
    }
}

const copyToClipboard = (text, fieldName) => {
    navigator.clipboard.writeText(text)
    copiedField.value = fieldName
    feedbackMsg.value = `${fieldName} copied to clipboard!`
    setTimeout(() => { 
        copiedField.value = ''
        feedbackMsg.value = '' 
    }, 2500)
}

// 2. REGENERATE DKIM
const dkimForm = useForm({
    key_size: 2048,
})

const regenerateDkim = (domain) => {
    if (confirm(`Regenerate 2048-bit RSA DKIM private & public keys for ${domain.domain}? DNS records must be updated afterwards.`)) {
        dkimForm.post(route('admin.email.dkim-spf.dkim', domain.id), {
            preserveScroll: true,
            onSuccess: () => {
                feedbackMsg.value = `New 2048-bit DKIM key generated for ${domain.domain}.`
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            }
        })
    }
}

// 3. EDIT SPF MODAL
const showSpfModal = ref(false)
const selectedDomainForSpf = ref(null)
const spfForm = useForm({
    spf_record: '',
})

const openSpfModal = (domain) => {
    selectedDomainForSpf.value = domain
    spfForm.spf_record = domain.spf_record || `v=spf1 a mx ip4:${props.serverIp} ~all`
    showSpfModal.value = true
}

const submitSpf = () => {
    if (!selectedDomainForSpf.value) return
    spfForm.post(route('admin.email.dkim-spf.spf', selectedDomainForSpf.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showSpfModal.value = false
            feedbackMsg.value = `SPF policy for ${selectedDomainForSpf.value.domain} saved.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 4. EDIT DMARC MODAL
const showDmarcModal = ref(false)
const selectedDomainForDmarc = ref(null)
const dmarcForm = useForm({
    dmarc_record: '',
})

const openDmarcModal = (domain) => {
    selectedDomainForDmarc.value = domain
    dmarcForm.dmarc_record = domain.dmarc_record || `v=DMARC1; p=quarantine; sp=quarantine; rua=mailto:dmarc@${domain.domain}`
    showDmarcModal.value = true
}

const submitDmarc = () => {
    if (!selectedDomainForDmarc.value) return
    dmarcForm.post(route('admin.email.dkim-spf.dmarc', selectedDomainForDmarc.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDmarcModal.value = false
            feedbackMsg.value = `DMARC policy for ${selectedDomainForDmarc.value.domain} saved.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}
</script>

<template>
    <Head title="DKIM, SPF & DMARC Deliverability - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Email', href: route('admin.email.domains') },
                    { label: 'DKIM & SPF' }
                ]"
            >
                <template #actions>
                    <Link
                        :href="route('admin.email.domains')"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <ArrowLeftIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>Mail Domains</span>
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
                    title="Managed Domains"
                    :value="String(stats.total_domains || domains.length || 0)"
                    badge="Domains"
                    badgeType="info"
                    color="blue"
                    :icon="GlobeAltIcon"
                />

                <InfoCard
                    title="DKIM 2048-bit"
                    :value="String(stats.dkim_count || 0)"
                    badge="Cryptographic"
                    badgeType="success"
                    color="emerald"
                    :icon="KeyIcon"
                />

                <InfoCard
                    title="Strict SPF Protected"
                    :value="String(stats.spf_count || 0)"
                    badge="TXT Policy"
                    badgeType="info"
                    color="sky"
                    :icon="ShieldCheckIcon"
                />

                <InfoCard
                    title="DMARC Policies"
                    :value="String(stats.dmarc_count || 0)"
                    badge="Reporting"
                    badgeType="warning"
                    color="purple"
                    :icon="ServerIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Standard Filter Toolbar -->
            <DataTableFilter
                v-model="search"
                searchPlaceholder="Search domain name, SPF directives, or DMARC..."
                @search="() => {}"
                @filter="() => {}"
                @reset="search = ''"
            />

            <!-- 4. Standard Data Table -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Domain Name</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">DKIM Key Status</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">SPF Record (TXT)</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">DMARC Policy (TXT)</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">DNS Verification</th>
                                <th class="py-2.5 px-3 w-14 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(d, index) in filteredDomains" :key="d.id || index" class="hover:bg-blue-50/30 transition">
                                <!-- # -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>

                                <!-- Domain -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs border border-blue-100 shrink-0">
                                            <ShieldCheckIcon class="w-3.5 h-3.5" />
                                        </div>
                                        <div>
                                            <span class="font-bold text-slate-900 font-mono leading-tight">{{ d.domain }}</span>
                                            <span v-if="d.subscription" class="text-[10px] text-slate-400 block font-mono">@{{ d.subscription.domain }}</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- DKIM -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="d.dkim_public_key ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-amber-50 text-amber-700 border-amber-200'"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono"
                                    >
                                        {{ d.dkim_public_key ? '2048-bit Active' : 'Missing' }}
                                    </span>
                                </td>

                                <!-- SPF -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono text-[10.5px] text-slate-700 max-w-xs truncate" :title="d.spf_record">
                                    {{ d.spf_record || `v=spf1 a mx ip4:${serverIp} ~all` }}
                                </td>

                                <!-- DMARC -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono text-[10.5px] text-slate-700 max-w-xs truncate" :title="d.dmarc_record">
                                    {{ d.dmarc_record || `v=DMARC1; p=quarantine; rua=mailto:dmarc@${d.domain}` }}
                                </td>

                                <!-- DNS Status -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <button 
                                        type="button" 
                                        @click="openDnsModal(d)"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-[3px] text-[10px] font-bold border border-slate-200 bg-slate-50 text-slate-700 hover:bg-slate-100 transition cursor-pointer"
                                    >
                                        <GlobeAltIcon class="w-3 h-3 text-slate-500" />
                                        <span>Verify Live</span>
                                    </button>
                                </td>

                                <!-- Action -->
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <RowActionDropdown>
                                        <button 
                                            type="button" 
                                            @click="openDnsModal(d)"
                                            class="w-full text-left px-3 py-1.5 text-xs text-blue-600 hover:bg-blue-50 flex items-center gap-2 font-medium cursor-pointer"
                                        >
                                            <KeyIcon class="w-3.5 h-3.5 text-blue-500" />
                                            <span>DNS & DKIM Keys</span>
                                        </button>

                                        <button
                                            type="button"
                                            @click="openSpfModal(d)"
                                            class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                        >
                                            <PencilSquareIcon class="w-3.5 h-3.5 text-slate-500" />
                                            <span>Edit SPF Directives</span>
                                        </button>

                                        <button
                                            type="button"
                                            @click="openDmarcModal(d)"
                                            class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                        >
                                            <PencilSquareIcon class="w-3.5 h-3.5 text-slate-500" />
                                            <span>Edit DMARC Policy</span>
                                        </button>

                                        <button
                                            type="button"
                                            @click="regenerateDkim(d)"
                                            class="w-full text-left px-3 py-1.5 text-xs text-purple-700 hover:bg-purple-50 flex items-center gap-2 font-medium cursor-pointer"
                                        >
                                            <ArrowPathIcon class="w-3.5 h-3.5" />
                                            <span>Regenerate 2048-bit Key</span>
                                        </button>
                                    </RowActionDropdown>
                                </td>
                            </tr>

                            <tr v-if="!filteredDomains || filteredDomains.length === 0">
                                <td colspan="7" class="py-12 text-center text-slate-400">
                                    No email deliverability records found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 1. LIVE DNS & DKIM INSPECTOR MODAL -->
        <div v-if="showDnsModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-2xl overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <ShieldCheckIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Live DNS & Cryptographic Keys: {{ selectedDomainForDns?.domain }}
                        </h3>
                    </div>
                    <button @click="showDnsModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-3.5 text-xs">
                    <!-- DKIM -->
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-slate-800">1. DKIM Public Key Record (TXT: default._domainkey.{{ selectedDomainForDns?.domain }})</span>
                            <button @click="copyToClipboard(selectedDomainForDns?.dkim_public_key || 'v=DKIM1; k=rsa; p=...', 'DKIM Record')" class="text-[10px] text-blue-600 font-bold hover:underline cursor-pointer">Copy</button>
                        </div>
                        <div class="bg-slate-50 p-2.5 rounded-[3px] border border-slate-200 font-mono text-[10.5px] text-slate-800 break-all max-h-24 overflow-y-auto">
                            {{ selectedDomainForDns?.dkim_public_key || 'v=DKIM1; k=rsa; p=MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA... (2048-bit RSA)' }}
                        </div>
                    </div>

                    <!-- SPF -->
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-slate-800">2. SPF Record (TXT: @.{{ selectedDomainForDns?.domain }})</span>
                            <button @click="copyToClipboard(selectedDomainForDns?.spf_record || `v=spf1 a mx ip4:${serverIp} ~all`, 'SPF Record')" class="text-[10px] text-blue-600 font-bold hover:underline cursor-pointer">Copy</button>
                        </div>
                        <div class="bg-slate-50 p-2.5 rounded-[3px] border border-slate-200 font-mono text-[11px] text-slate-800 break-all">
                            {{ selectedDomainForDns?.spf_record || `v=spf1 a mx ip4:${serverIp} ~all` }}
                        </div>
                    </div>

                    <!-- DMARC -->
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-slate-800">3. DMARC Policy (TXT: _dmarc.{{ selectedDomainForDns?.domain }})</span>
                            <button @click="copyToClipboard(selectedDomainForDns?.dmarc_record || `v=DMARC1; p=quarantine; rua=mailto:dmarc@${selectedDomainForDns?.domain}`, 'DMARC Record')" class="text-[10px] text-blue-600 font-bold hover:underline cursor-pointer">Copy</button>
                        </div>
                        <div class="bg-slate-50 p-2.5 rounded-[3px] border border-slate-200 font-mono text-[11px] text-slate-800 break-all">
                            {{ selectedDomainForDns?.dmarc_record || `v=DMARC1; p=quarantine; rua=mailto:dmarc@${selectedDomainForDns?.domain}` }}
                        </div>
                    </div>
                </div>

                <div class="px-4 py-2.5 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end text-xs">
                    <button
                        type="button"
                        @click="showDnsModal = false"
                        class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>

        <!-- 2. EDIT SPF MODAL -->
        <div v-if="showSpfModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <PencilSquareIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Edit SPF Policy: {{ selectedDomainForSpf?.domain }}
                        </h3>
                    </div>
                    <button @click="showSpfModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitSpf" class="p-4 space-y-3 text-xs">
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">SPF TXT Directives <span class="text-rose-500">*</span></label>
                        <textarea v-model="spfForm.spf_record" rows="3" required class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"></textarea>
                        <span class="text-[10px] text-slate-400 block">Default recommended: v=spf1 a mx ip4:{{ serverIp }} ~all</span>
                    </div>

                    <div class="px-4 py-2.5 -mx-4 -mb-4 mt-4 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end gap-2 text-xs">
                        <button
                            type="button"
                            @click="showSpfModal = false"
                            class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="spfForm.processing"
                            class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                        >
                            {{ spfForm.processing ? 'Saving...' : 'Save SPF' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 3. EDIT DMARC MODAL -->
        <div v-if="showDmarcModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <PencilSquareIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Edit DMARC Policy: {{ selectedDomainForDmarc?.domain }}
                        </h3>
                    </div>
                    <button @click="showDmarcModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitDmarc" class="p-4 space-y-3 text-xs">
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">DMARC TXT Policy <span class="text-rose-500">*</span></label>
                        <textarea v-model="dmarcForm.dmarc_record" rows="3" required class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"></textarea>
                        <span class="text-[10px] text-slate-400 block">Recommended: v=DMARC1; p=quarantine; rua=mailto:dmarc@{{ selectedDomainForDmarc?.domain }}</span>
                    </div>

                    <div class="px-4 py-2.5 -mx-4 -mb-4 mt-4 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end gap-2 text-xs">
                        <button
                            type="button"
                            @click="showDmarcModal = false"
                            class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="dmarcForm.processing"
                            class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                        >
                            {{ dmarcForm.processing ? 'Saving...' : 'Save DMARC' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
