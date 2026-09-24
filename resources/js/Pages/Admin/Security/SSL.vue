<script setup>
import { ref, computed } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import RefreshButton from '@/Components/UI/RefreshButton.vue'
import InfoCardsGrid from '@/Components/UI/InfoCardsGrid.vue'
import InfoCard from '@/Components/UI/InfoCard.vue'
import DataTableFilter from '@/Components/UI/DataTableFilter.vue'
import FilterSelect from '@/Components/UI/FilterSelect.vue'
import RowActionDropdown from '@/Components/UI/RowActionDropdown.vue'

import {
    ShieldCheckIcon,
    LockClosedIcon,
    PlusIcon,
    TrashIcon,
    ArrowPathIcon,
    XMarkIcon,
    CheckIcon,
    DocumentDuplicateIcon,
    ArrowDownTrayIcon,
    GlobeAltIcon,
    KeyIcon,
    DocumentTextIcon,
    ServerIcon,
    BoltIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    certificates: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    stats: {
        type: Object,
        default: () => ({
            total_certs: 0,
            active_certs: 0,
            autossl_count: 0,
            expiring_soon: 0,
            https_enforced: 0,
        }),
    },
    hostedDomains: {
        type: Array,
        default: () => [],
    },
    subscriptions: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({ search: '', status: 'all' }),
    },
})

// Search & Filter State
const search = ref(props.filters.search || '')
const statusFilter = ref(props.filters.status || 'all')
const feedbackMsg = ref('')

const certList = computed(() => {
    if (Array.isArray(props.certificates)) return props.certificates
    if (props.certificates?.data && Array.isArray(props.certificates.data)) return props.certificates.data
    return []
})

const filteredCerts = computed(() => {
    let list = certList.value

    if (search.value.trim()) {
        const q = search.value.toLowerCase()
        list = list.filter(c =>
            c.domain.toLowerCase().includes(q) ||
            (c.issuer || '').toLowerCase().includes(q) ||
            (c.subscription?.domain || '').toLowerCase().includes(q)
        )
    }

    if (statusFilter.value !== 'all') {
        list = list.filter(c => (c.status || 'active') === statusFilter.value)
    }

    return list
})

const resetFilters = () => {
    search.value = ''
    statusFilter.value = 'all'
}

// 1. ISSUE LET'S ENCRYPT MODAL
const showLetsEncryptModal = ref(false)
const leForm = useForm({
    domain: '',
    san_domains: [],
    force_https: true,
    hsts_enabled: false,
    subscription_id: '',
})

const openLetsEncryptModal = () => {
    leForm.reset()
    leForm.force_https = true
    if (props.hostedDomains.length > 0) {
        leForm.domain = props.hostedDomains[0]
        leForm.san_domains = [`www.${props.hostedDomains[0]}`, `mail.${props.hostedDomains[0]}`]
    }
    showLetsEncryptModal.value = true
}

const onDomainSelect = (dom) => {
    leForm.domain = dom
    leForm.san_domains = [`www.${dom}`, `mail.${dom}`]
}

const submitLetsEncrypt = () => {
    leForm.post(route('admin.security.ssl.issue-letsencrypt'), {
        preserveScroll: true,
        onSuccess: () => {
            showLetsEncryptModal.value = false
            feedbackMsg.value = `Let's Encrypt AutoSSL issued for '${leForm.domain}'.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 2. INSTALL CUSTOM CERTIFICATE MODAL
const showCustomModal = ref(false)
const customForm = useForm({
    domain: '',
    certificate: '',
    private_key: '',
    ca_bundle: '',
    force_https: true,
})

const openCustomModal = () => {
    customForm.reset()
    customForm.force_https = true
    showCustomModal.value = true
}

const submitCustom = () => {
    customForm.post(route('admin.security.ssl.install-custom'), {
        preserveScroll: true,
        onSuccess: () => {
            showCustomModal.value = false
            feedbackMsg.value = `Custom SSL Certificate installed for '${customForm.domain}'.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 3. VIEW CERTIFICATE MODAL
const showViewModal = ref(false)
const selectedCert = ref(null)

const openViewModal = (cert) => {
    selectedCert.value = cert
    showViewModal.value = true
}

// 4. RENEW CERTIFICATE
const renewCert = (cert) => {
    router.post(route('admin.security.ssl.renew', cert.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = `Certificate for '${cert.domain}' renewed successfully.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 5. TOGGLE FORCE HTTPS
const toggleForceHttps = (cert) => {
    router.post(route('admin.security.ssl.toggle-force-https', cert.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = `Force HTTPS setting updated for '${cert.domain}'.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 6. TOGGLE AUTO RENEW
const toggleAutoRenew = (cert) => {
    router.post(route('admin.security.ssl.toggle-auto-renew', cert.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = `Auto-Renew setting updated for '${cert.domain}'.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 7. DELETE CERTIFICATE
const showDeleteModal = ref(false)
const certToDelete = ref(null)
const deleteForm = useForm({})

const openDeleteModal = (cert) => {
    certToDelete.value = cert
    showDeleteModal.value = true
}

const submitDelete = () => {
    if (!certToDelete.value) return
    deleteForm.delete(route('admin.security.ssl.destroy', certToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false
            feedbackMsg.value = `Certificate for '${certToDelete.value.domain}' deleted.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

const copyToClipboard = (text, label) => {
    navigator.clipboard.writeText(text)
    feedbackMsg.value = `${label} copied to clipboard!`
    setTimeout(() => { feedbackMsg.value = '' }, 2500)
}
</script>

<template>
    <Head title="SSL / TLS Certificates - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Security', href: '#' },
                    { label: 'Security & Data Protection', href: route('admin.security.ssl') },
                    { label: 'SSL / TLS Certificates' }
                ]"
            >
                <template #actions>
                    <button 
                        type="button" 
                        @click="openCustomModal"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <DocumentTextIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>Install Custom SSL</span>
                    </button>

                    <button 
                        type="button" 
                        @click="openLetsEncryptModal"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <ShieldCheckIcon class="w-3.5 h-3.5" />
                        <span>Issue Let's Encrypt SSL</span>
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
                    title="Active Certificates"
                    :value="String(stats.total_certs || certList.length || 0)"
                    badge="SSL/TLS"
                    badgeType="info"
                    color="blue"
                    :icon="ShieldCheckIcon"
                />

                <InfoCard
                    title="Let's Encrypt AutoSSL"
                    :value="String(stats.autossl_count || 0)"
                    badge="ACME Auto"
                    badgeType="success"
                    color="emerald"
                    :icon="LockClosedIcon"
                />

                <InfoCard
                    title="HTTPS Enforced"
                    :value="String(stats.https_enforced || 0)"
                    badge="Strict HSTS"
                    badgeType="info"
                    color="purple"
                    :icon="GlobeAltIcon"
                />

                <InfoCard
                    title="Expiring Soon (<30d)"
                    :value="String(stats.expiring_soon || 0)"
                    :badge="stats.expiring_soon > 0 ? 'Warning' : 'All Valid'"
                    :badgeType="stats.expiring_soon > 0 ? 'warning' : 'success'"
                    color="sky"
                    :icon="ServerIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Standard Filter Toolbar -->
            <DataTableFilter
                v-model="search"
                searchPlaceholder="Search certificate domain, issuer CA, or tenant..."
                @search="() => {}"
                @filter="() => {}"
                @reset="resetFilters"
            >
                <FilterSelect
                    v-model="statusFilter"
                    label="Certificate Validity"
                    :options="[
                        { label: 'All Certificates', value: 'all' },
                        { label: 'Active & Valid', value: 'active' },
                        { label: 'Expiring Soon', value: 'expiring' },
                        { label: 'Expired / Revoked', value: 'expired' }
                    ]"
                    placeholder="All Certificates"
                />
            </DataTableFilter>

            <!-- 4. Standard Data Table -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Primary Domain (FQDN)</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Issuer CA</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Force HTTPS</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Auto-Renew</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Valid Until / Expiry</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Status</th>
                                <th class="py-2.5 px-3 w-28">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(cert, index) in filteredCerts" :key="cert.id || index" class="hover:bg-blue-50/30 transition">
                                <!-- # -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>

                                <!-- Domain -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono font-bold text-slate-900">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-[3px] bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-xs border border-emerald-100 shrink-0">
                                            <LockClosedIcon class="w-3.5 h-3.5" />
                                        </div>
                                        <div>
                                            <span class="block leading-tight">{{ cert.domain }}</span>
                                            <span v-if="cert.san_domains && cert.san_domains.length" class="text-[10px] text-slate-400 block font-normal truncate max-w-xs">
                                                SAN: {{ cert.san_domains.join(', ') }}
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Issuer -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono text-[11px] text-slate-700">
                                    {{ cert.issuer || "Let's Encrypt Authority X3" }}
                                </td>

                                <!-- Force HTTPS -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <button 
                                        type="button" 
                                        @click="toggleForceHttps(cert)"
                                        class="cursor-pointer"
                                        :title="cert.force_https ? 'Click to disable HTTPS redirect' : 'Click to force HTTPS'"
                                    >
                                        <span 
                                            :class="cert.force_https ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-slate-100 text-slate-500 border-slate-200'"
                                            class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold border font-mono"
                                        >
                                            {{ cert.force_https ? 'Enforced' : 'Optional' }}
                                        </span>
                                    </button>
                                </td>

                                <!-- Auto-Renew -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <button 
                                        type="button" 
                                        @click="toggleAutoRenew(cert)"
                                        class="cursor-pointer"
                                        :title="cert.auto_renew ? 'Auto-renews 30 days before expiry' : 'Manual renewal'"
                                    >
                                        <span 
                                            :class="cert.auto_renew ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-500 border-slate-200'"
                                            class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold border font-mono"
                                        >
                                            {{ cert.auto_renew ? 'Enabled' : 'Disabled' }}
                                        </span>
                                    </button>
                                </td>

                                <!-- Expiry -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[10.5px] text-slate-700">
                                    {{ cert.valid_to ? new Date(cert.valid_to).toLocaleDateString() : '90 Days' }}
                                </td>

                                <!-- Status -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="cert.status === 'active' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-amber-50 text-amber-700 border-amber-200'"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono"
                                    >
                                        {{ cert.status || 'active' }}
                                    </span>
                                </td>

                                <!-- Action -->
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1">
                                        <button 
                                            type="button" 
                                            @click="openViewModal(cert)"
                                            class="px-2 py-1 bg-white hover:bg-slate-50 text-blue-700 font-bold rounded-[3px] text-xs border border-slate-200 shadow-2xs transition cursor-pointer"
                                            title="View Certificate Details"
                                        >
                                            View 🔍
                                        </button>

                                        <RowActionDropdown>
                                            <button
                                                type="button"
                                                @click="renewCert(cert)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-blue-700 hover:bg-blue-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <ArrowPathIcon class="w-3.5 h-3.5 text-blue-500" />
                                                <span>Renew Certificate Now</span>
                                            </button>

                                            <button
                                                type="button"
                                                @click="toggleForceHttps(cert)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <GlobeAltIcon class="w-3.5 h-3.5 text-slate-500" />
                                                <span>Toggle Force HTTPS</span>
                                            </button>

                                            <button
                                                type="button"
                                                @click="toggleAutoRenew(cert)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <BoltIcon class="w-3.5 h-3.5 text-slate-500" />
                                                <span>Toggle Auto-Renew</span>
                                            </button>

                                            <button
                                                type="button"
                                                @click="openDeleteModal(cert)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-rose-600 hover:bg-rose-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <TrashIcon class="w-3.5 h-3.5" />
                                                <span>Revoke & Delete</span>
                                            </button>
                                        </RowActionDropdown>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!filteredCerts || filteredCerts.length === 0">
                                <td colspan="8" class="py-12 text-center text-slate-400">
                                    No SSL certificates found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 1. ISSUE LET'S ENCRYPT MODAL -->
        <div v-if="showLetsEncryptModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-lg overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <ShieldCheckIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Issue Let's Encrypt AutoSSL
                        </h3>
                    </div>
                    <button @click="showLetsEncryptModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitLetsEncrypt" class="p-4 space-y-3 text-xs">
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Domain Name <span class="text-rose-500">*</span></label>
                        <select 
                            v-if="hostedDomains.length > 0"
                            v-model="leForm.domain"
                            @change="onDomainSelect(leForm.domain)"
                            required
                            class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer font-mono font-bold"
                        >
                            <option v-for="d in hostedDomains" :key="d" :value="d">
                                {{ d }}
                            </option>
                        </select>
                        <input v-else v-model="leForm.domain" type="text" required placeholder="example.com" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Subject Alternative Names (SANs)</label>
                        <div class="bg-slate-50 p-2 rounded-[3px] border border-slate-200 font-mono text-[11px] text-slate-700">
                            {{ leForm.san_domains.join(', ') || 'www.' + leForm.domain }}
                        </div>
                    </div>

                    <div class="flex flex-col gap-2 pt-1">
                        <label class="flex items-center gap-2 cursor-pointer font-bold text-slate-700">
                            <input v-model="leForm.force_https" type="checkbox" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer" />
                            <span>Enforce Automatic HTTP to HTTPS 301 Redirect</span>
                        </label>
                    </div>

                    <div class="px-4 py-2.5 -mx-4 -mb-4 mt-4 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end gap-2 text-xs">
                        <button
                            type="button"
                            @click="showLetsEncryptModal = false"
                            class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="leForm.processing"
                            class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                        >
                            {{ leForm.processing ? 'Validating ACME Challenge...' : 'Issue Certificate' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 2. INSTALL CUSTOM CERTIFICATE MODAL -->
        <div v-if="showCustomModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-2xl overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <DocumentTextIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Install Custom SSL Certificate
                        </h3>
                    </div>
                    <button @click="showCustomModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitCustom" class="p-4 space-y-3 text-xs">
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Target Domain (FQDN) <span class="text-rose-500">*</span></label>
                        <input v-model="customForm.domain" type="text" required placeholder="example.com" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Certificate (CRT / PEM) <span class="text-rose-500">*</span></label>
                            <textarea v-model="customForm.certificate" rows="4" required placeholder="-----BEGIN CERTIFICATE-----" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"></textarea>
                        </div>

                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Private Key (KEY) <span class="text-rose-500">*</span></label>
                            <textarea v-model="customForm.private_key" rows="4" required placeholder="-----BEGIN RSA PRIVATE KEY-----" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"></textarea>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">CA Intermediate Bundle (Optional)</label>
                        <textarea v-model="customForm.ca_bundle" rows="3" placeholder="-----BEGIN CERTIFICATE-----" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"></textarea>
                    </div>

                    <div class="px-4 py-2.5 -mx-4 -mb-4 mt-4 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end gap-2 text-xs">
                        <button
                            type="button"
                            @click="showCustomModal = false"
                            class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="customForm.processing"
                            class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                        >
                            {{ customForm.processing ? 'Installing...' : 'Install Certificate' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 3. VIEW CERTIFICATE MODAL -->
        <div v-if="showViewModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-lg overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <ShieldCheckIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Certificate Details: {{ selectedCert?.domain }}
                        </h3>
                    </div>
                    <button @click="showViewModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-3 text-xs">
                    <div class="p-3 bg-slate-50 rounded-[3px] border border-slate-200 space-y-1.5 font-mono text-[11px]">
                        <p><strong class="text-slate-900">Domain:</strong> {{ selectedCert?.domain }}</p>
                        <p><strong class="text-slate-900">Issuer:</strong> {{ selectedCert?.issuer || "Let's Encrypt Authority" }}</p>
                        <p><strong class="text-slate-900">Valid From:</strong> {{ selectedCert?.valid_from || 'Active' }}</p>
                        <p><strong class="text-slate-900">Valid Until:</strong> {{ selectedCert?.valid_to || '90 Days' }}</p>
                        <p><strong class="text-slate-900">SANs:</strong> {{ (selectedCert?.san_domains || []).join(', ') || 'None' }}</p>
                    </div>
                </div>

                <div class="px-4 py-2.5 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end text-xs">
                    <button
                        type="button"
                        @click="showViewModal = false"
                        class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>

        <!-- 4. DELETE MODAL -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-rose-50 text-rose-600 flex items-center justify-center font-bold border border-rose-100">
                            <TrashIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Revoke & Delete SSL Certificate
                        </h3>
                    </div>
                    <button @click="showDeleteModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-2.5 text-xs">
                    <p class="text-slate-600">
                        Are you sure you want to revoke and delete the SSL certificate for <strong class="text-slate-900 font-mono">[{{ certToDelete?.domain }}]</strong>? HTTPS traffic may be disrupted.
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
                        {{ deleteForm.processing ? 'Deleting...' : 'Delete Certificate' }}
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
