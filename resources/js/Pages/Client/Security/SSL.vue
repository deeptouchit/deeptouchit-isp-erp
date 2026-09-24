<script setup>
import { ref, computed } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import { 
    ShieldCheckIcon, 
    CheckCircleIcon, 
    ExclamationTriangleIcon, 
    XCircleIcon,
    ArrowPathIcon,
    LockClosedIcon,
    LockOpenIcon,
    PlusIcon,
    DocumentDuplicateIcon,
    ArrowTopRightOnSquareIcon,
    TrashIcon,
    SparklesIcon,
    BoltIcon,
    KeyIcon,
    DocumentTextIcon,
    XMarkIcon,
    GlobeAltIcon,
    EyeIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    subscription: {
        type: Object,
        default: null
    },
    subscriptions: {
        type: Array,
        default: () => []
    },
    websites: {
        type: Array,
        default: () => []
    },
    certificates: {
        type: Array,
        default: () => []
    },
    stats: {
        type: Object,
        default: () => ({
            total_domains: 1,
            secured_domains: 1,
            unprotected_domains: 0,
            auto_ssl_enabled: 1,
            grade: 'A+ SSL Labs Certified',
            tls_version: 'TLS 1.3 / HSTS'
        })
    }
})

// 1. Issue Free SSL Modal State
const showIssueModal = ref(false)
const issueForm = useForm({
    website_id: props.websites[0]?.id || '',
    provider: 'letsencrypt',
    include_www: true,
    force_https: true,
})

const submitIssue = () => {
    issueForm.post(route('security.ssl.issue'), {
        preserveScroll: true,
        onSuccess: () => {
            showIssueModal.value = false
        }
    })
}

// 2. Custom SSL Modal State
const showCustomModal = ref(false)
const customForm = useForm({
    website_id: props.websites[0]?.id || '',
    certificate: '',
    private_key: '',
    ca_bundle: '',
})

const submitCustom = () => {
    customForm.post(route('security.ssl.install-custom'), {
        preserveScroll: true,
        onSuccess: () => {
            showCustomModal.value = false
            customForm.reset()
        }
    })
}

// 3. Certificate Details Modal
const showDetailsModal = ref(false)
const selectedCert = ref(null)

const openCertDetails = (cert) => {
    selectedCert.value = cert
    showDetailsModal.value = true
}

// 4. Renew Certificate
const renewForm = useForm({})
const activeRenewingId = ref(null)

const handleRenew = (cert) => {
    activeRenewingId.value = cert.id
    renewForm.post(route('security.ssl.renew', cert.id), {
        preserveScroll: true,
        onFinish: () => {
            activeRenewingId.value = null
        }
    })
}

// 5. Revoke Certificate Modal
const showRevokeModal = ref(false)
const certToRevoke = ref(null)
const revokeForm = useForm({})

const openRevokeModal = (cert) => {
    certToRevoke.value = cert
    showRevokeModal.value = true
}

const submitRevoke = () => {
    if (!certToRevoke.value) return
    revokeForm.post(route('security.ssl.revoke', certToRevoke.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showRevokeModal.value = false
            certToRevoke.value = null
        }
    })
}

// 6. Toggle Force HTTPS
const forceHttpsForm = useForm({})
const toggleForceHttps = (cert) => {
    forceHttpsForm.post(route('security.ssl.force-https', cert.id), {
        preserveScroll: true
    })
}
</script>

<template>
    <Head title="SSL/TLS Certificates & AutoSSL - DeepTouch Host" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto space-y-4">
            
            <!-- 1. Page Header with Breadcrumbs & Actions -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Security', href: '#' },
                    { label: 'SSL/TLS Certificates' }
                ]"
                :show-refresh="true"
                @refreshed="router.reload({ preserveScroll: true })"
            >
                <template #actions>
                    <button 
                        @click="showCustomModal = true"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <KeyIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>Install Custom SSL</span>
                    </button>

                    <button 
                        @click="showIssueModal = true"
                        class="px-2.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <PlusIcon class="w-3.5 h-3.5 stroke-[2.5]" />
                        <span>Issue Free Let's Encrypt SSL</span>
                    </button>
                </template>
            </PageHeader>

            <!-- 2. Top Analytics & Security Status Grid (3 Cards) -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                
                <!-- Card 1: Active Certificates -->
                <div class="bg-white rounded-lg border border-slate-200 p-4 shadow-2xs space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <LockClosedIcon class="w-4 h-4 text-emerald-600" />
                            <span>HTTPS Coverage</span>
                        </span>
                        <span 
                            class="text-[10px] font-bold px-1.5 py-0.5 rounded border uppercase font-mono"
                            :class="stats.unprotected_domains === 0 ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-amber-50 text-amber-700 border-amber-200'"
                        >
                            {{ stats.unprotected_domains === 0 ? '100% ENCRYPTED' : 'UNSECURED DOMAINS' }}
                        </span>
                    </div>

                    <div>
                        <div class="flex items-baseline justify-between">
                            <span class="text-2xl font-black text-emerald-600 font-mono">
                                {{ stats.secured_domains }} of {{ stats.total_domains }} Secured
                            </span>
                            <span class="text-xs text-slate-500 font-mono">Auto-Renewing</span>
                        </div>
                        <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden mt-2">
                            <div 
                                class="h-full rounded-full bg-emerald-500"
                                :style="{ width: `${stats.total_domains > 0 ? (stats.secured_domains / stats.total_domains) * 100 : 100}%` }"
                            ></div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1 border-t border-slate-100">
                        <span>SSL Standard:</span>
                        <strong class="text-slate-700 font-mono text-[10.5px]">DV Wildcard Supported</strong>
                    </div>
                </div>

                <!-- Card 2: AutoSSL Renewal Engine -->
                <div class="bg-white rounded-lg border border-slate-200 p-4 shadow-2xs space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <SparklesIcon class="w-4 h-4 text-blue-600" />
                            <span>AutoSSL Automation</span>
                        </span>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-200 uppercase font-mono">
                            ACTIVE
                        </span>
                    </div>

                    <div>
                        <div class="flex items-baseline justify-between">
                            <span class="text-2xl font-black text-slate-900 font-mono">90-Day Renewal</span>
                            <span class="text-xs text-slate-500 font-mono">Zero Downtime</span>
                        </div>
                        <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden mt-2">
                            <div class="bg-blue-600 h-full rounded-full w-full"></div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1 border-t border-slate-100">
                        <span>Automated Daemon:</span>
                        <strong class="text-emerald-700 font-bold">Certbot ACME Client</strong>
                    </div>
                </div>

                <!-- Card 3: TLS 1.3 & HSTS Grade -->
                <div class="bg-white rounded-lg border border-slate-200 p-4 shadow-2xs space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <ShieldCheckIcon class="w-4 h-4 text-purple-600" />
                            <span>Encryption & Security</span>
                        </span>
                        <span class="text-[10px] font-bold text-slate-500 uppercase font-mono">
                            A+ LABS
                        </span>
                    </div>

                    <div class="space-y-1 text-xs font-mono">
                        <div class="flex items-center justify-between p-1.5 bg-slate-50 rounded border border-slate-200/70">
                            <span class="text-slate-500 text-[11px]">Cipher Suite:</span>
                            <span class="font-bold text-purple-700">ECDSA 384 + RSA</span>
                        </div>

                        <div class="flex items-center justify-between p-1.5 bg-slate-50 rounded border border-slate-200/70">
                            <span class="text-slate-500 text-[11px]">Strict Transport:</span>
                            <span class="font-bold text-emerald-700">HSTS Preload Ready</span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- 3. SSL Certificates Management Table -->
            <div class="bg-white rounded-lg border border-slate-200 shadow-2xs overflow-hidden">
                <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between bg-slate-50/60">
                    <div class="flex items-center gap-2">
                        <ShieldCheckIcon class="w-4 h-4 text-blue-600" />
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800">
                            Managed SSL/TLS Certificates ({{ certificates.length }})
                        </h3>
                    </div>
                    <span class="text-xs text-slate-500 font-mono">
                        Nginx SSL SNI Engine
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs whitespace-nowrap">
                        <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200">
                            <tr>
                                <th class="py-2.5 px-4">Domain Name</th>
                                <th class="py-2.5 px-4">Issuer & Authority</th>
                                <th class="py-2.5 px-4">Status</th>
                                <th class="py-2.5 px-4">Expires On</th>
                                <th class="py-2.5 px-4">Auto-Renew</th>
                                <th class="py-2.5 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-mono">
                            <tr v-for="cert in certificates" :key="cert.id" class="hover:bg-slate-50/70 transition">
                                <!-- Domain Name -->
                                <td class="py-3 px-4 font-bold text-slate-900">
                                    <div class="flex items-center gap-2">
                                        <LockClosedIcon v-if="cert.ssl_status === 'active'" class="w-4 h-4 text-emerald-600 shrink-0" />
                                        <LockOpenIcon v-else class="w-4 h-4 text-rose-500 shrink-0" />
                                        <span class="text-blue-700">{{ cert.domain }}</span>
                                        <span v-if="cert.is_subdomain" class="text-[9.5px] px-1.5 py-0.2 rounded bg-slate-100 text-slate-600 border border-slate-200 uppercase font-sans font-bold">
                                            Subdomain
                                        </span>
                                    </div>
                                </td>

                                <!-- Issuer -->
                                <td class="py-3 px-4 font-sans text-slate-600">
                                    <span class="font-bold text-slate-800">{{ cert.issuer }}</span>
                                    <span class="text-[10.5px] text-slate-400 block font-mono">{{ cert.encryption }}</span>
                                </td>

                                <!-- Status Badge -->
                                <td class="py-3 px-4">
                                    <span 
                                        class="px-2 py-0.5 rounded-[3px] text-[10.5px] font-bold uppercase font-sans border inline-flex items-center gap-1"
                                        :class="cert.ssl_status === 'active' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full" :class="cert.ssl_status === 'active' ? 'bg-emerald-500' : 'bg-rose-500'"></span>
                                        <span>{{ cert.ssl_status === 'active' ? 'ACTIVE - VALID' : 'UNPROTECTED' }}</span>
                                    </span>
                                </td>

                                <!-- Expiry Date -->
                                <td class="py-3 px-4">
                                    <div class="space-y-0.5">
                                        <span class="font-bold text-slate-800">{{ cert.expires_at }}</span>
                                        <span class="text-[10px] text-slate-500 block font-sans">
                                            {{ cert.expires_in_days }} days remaining
                                        </span>
                                    </div>
                                </td>

                                <!-- Auto-Renew -->
                                <td class="py-3 px-4 font-sans">
                                    <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700">
                                        <CheckCircleIcon class="w-3.5 h-3.5" />
                                        <span>Enabled (90-day)</span>
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td class="py-3 px-4 text-right font-sans">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button 
                                            @click="openCertDetails(cert)"
                                            class="p-1.5 rounded hover:bg-slate-100 text-slate-600 transition cursor-pointer"
                                            title="View Certificate Details"
                                        >
                                            <EyeIcon class="w-4 h-4" />
                                        </button>

                                        <button 
                                            @click="handleRenew(cert)"
                                            :disabled="activeRenewingId === cert.id"
                                            class="px-2.5 py-1 bg-white hover:bg-blue-50 text-blue-700 font-bold rounded-[3px] text-xs border border-blue-200 shadow-2xs transition cursor-pointer disabled:opacity-50 flex items-center gap-1"
                                            title="Force Certificate Renewal"
                                        >
                                            <ArrowPathIcon class="w-3 h-3" :class="{ 'animate-spin': activeRenewingId === cert.id }" />
                                            <span>{{ activeRenewingId === cert.id ? 'Renewing...' : 'Renew' }}</span>
                                        </button>

                                        <button 
                                            @click="openRevokeModal(cert)"
                                            class="p-1.5 rounded hover:bg-rose-50 text-rose-500 hover:text-rose-700 transition cursor-pointer"
                                            title="Revoke Certificate"
                                        >
                                            <TrashIcon class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- 1. Issue Free SSL Modal -->
        <div v-if="showIssueModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-lg max-w-lg w-full p-5 shadow-xl space-y-4 border border-slate-200">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2">
                        <LockClosedIcon class="w-5 h-5 text-blue-600" />
                        <h3 class="font-bold text-slate-900 text-sm">Issue Free Let's Encrypt SSL</h3>
                    </div>
                    <button @click="showIssueModal = false" class="text-slate-400 hover:text-slate-600 p-1 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitIssue" class="space-y-3.5 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Select Domain</label>
                        <select 
                            v-model="issueForm.website_id"
                            required
                            class="w-full text-xs font-bold rounded-[3px] border-slate-300 bg-white text-slate-900 py-2 px-3 focus:ring-blue-500 focus:border-blue-500"
                        >
                            <option v-for="site in websites" :key="site.id" :value="site.id">
                                {{ site.domain }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">SSL Certificate Authority</label>
                        <div class="p-2.5 bg-blue-50/70 border border-blue-200 rounded flex items-center justify-between">
                            <span class="font-bold text-blue-900">Let's Encrypt Authority (Free Automated DV SSL)</span>
                            <span class="text-[10.5px] bg-blue-600 text-white font-bold px-1.5 py-0.5 rounded uppercase">Recommended</span>
                        </div>
                    </div>

                    <div class="space-y-2 p-3 bg-slate-50 rounded border border-slate-200">
                        <label class="flex items-center gap-2 cursor-pointer font-bold text-slate-700">
                            <input type="checkbox" v-model="issueForm.include_www" class="rounded text-blue-600 focus:ring-blue-500 border-slate-300" />
                            <span>Include www. wildcard alias (e.g. www.domain.com)</span>
                        </label>

                        <label class="flex items-center gap-2 cursor-pointer font-bold text-slate-700">
                            <input type="checkbox" v-model="issueForm.force_https" class="rounded text-blue-600 focus:ring-blue-500 border-slate-300" />
                            <span>Enforce Automatic HTTP to HTTPS 301 Redirection</span>
                        </label>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                        <button 
                            type="button" 
                            @click="showIssueModal = false" 
                            class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-[3px] text-xs font-bold cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button 
                            type="submit" 
                            :disabled="issueForm.processing"
                            class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold shadow-2xs transition cursor-pointer disabled:opacity-50"
                        >
                            {{ issueForm.processing ? 'Issuing Certificate...' : 'Issue Free Certificate' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 2. Install Custom SSL Modal -->
        <div v-if="showCustomModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-lg max-w-lg w-full p-5 shadow-xl space-y-4 border border-slate-200">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2">
                        <KeyIcon class="w-5 h-5 text-purple-600" />
                        <h3 class="font-bold text-slate-900 text-sm">Install Custom SSL (CRT / KEY)</h3>
                    </div>
                    <button @click="showCustomModal = false" class="text-slate-400 hover:text-slate-600 p-1 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitCustom" class="space-y-3 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Domain</label>
                        <select 
                            v-model="customForm.website_id"
                            required
                            class="w-full text-xs font-bold rounded-[3px] border-slate-300 bg-white text-slate-900 py-1.5 px-2.5 focus:ring-blue-500 focus:border-blue-500"
                        >
                            <option v-for="site in websites" :key="site.id" :value="site.id">
                                {{ site.domain }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Certificate (CRT / PEM)</label>
                        <textarea 
                            v-model="customForm.certificate"
                            rows="4"
                            placeholder="-----BEGIN CERTIFICATE-----..."
                            required
                            class="w-full font-mono text-[11px] rounded-[3px] border-slate-300 bg-slate-50 text-slate-900 py-1.5 px-2.5 focus:bg-white focus:ring-blue-500"
                        ></textarea>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Private Key (KEY)</label>
                        <textarea 
                            v-model="customForm.private_key"
                            rows="4"
                            placeholder="-----BEGIN RSA PRIVATE KEY-----..."
                            required
                            class="w-full font-mono text-[11px] rounded-[3px] border-slate-300 bg-slate-50 text-slate-900 py-1.5 px-2.5 focus:bg-white focus:ring-blue-500"
                        ></textarea>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">CA Bundle (Optional)</label>
                        <textarea 
                            v-model="customForm.ca_bundle"
                            rows="2"
                            placeholder="-----BEGIN CERTIFICATE----- (Intermediate CA)..."
                            class="w-full font-mono text-[11px] rounded-[3px] border-slate-300 bg-slate-50 text-slate-900 py-1.5 px-2.5 focus:bg-white focus:ring-blue-500"
                        ></textarea>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                        <button 
                            type="button" 
                            @click="showCustomModal = false" 
                            class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-[3px] text-xs font-bold cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button 
                            type="submit" 
                            :disabled="customForm.processing"
                            class="px-4 py-1.5 bg-purple-600 hover:bg-purple-700 text-white rounded-[3px] text-xs font-bold shadow-2xs transition cursor-pointer disabled:opacity-50"
                        >
                            {{ customForm.processing ? 'Installing...' : 'Install Certificate' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 3. Certificate Details Modal -->
        <div v-if="showDetailsModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-lg max-w-lg w-full p-5 shadow-xl space-y-4 border border-slate-200">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2">
                        <LockClosedIcon class="w-5 h-5 text-emerald-600" />
                        <h3 class="font-bold text-slate-900 text-sm">Certificate Details: {{ selectedCert?.domain }}</h3>
                    </div>
                    <button @click="showDetailsModal = false" class="text-slate-400 hover:text-slate-600 p-1 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="space-y-2.5 text-xs font-mono">
                    <div class="p-2.5 bg-slate-50 rounded border border-slate-200 space-y-1">
                        <span class="text-slate-400 text-[10px] font-sans font-bold uppercase">Common Name</span>
                        <p class="font-bold text-slate-900">{{ selectedCert?.domain }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div class="p-2.5 bg-slate-50 rounded border border-slate-200 space-y-1">
                            <span class="text-slate-400 text-[10px] font-sans font-bold uppercase">Issuer</span>
                            <p class="font-bold text-slate-900 font-sans">{{ selectedCert?.issuer }}</p>
                        </div>
                        <div class="p-2.5 bg-slate-50 rounded border border-slate-200 space-y-1">
                            <span class="text-slate-400 text-[10px] font-sans font-bold uppercase">Cipher & Key</span>
                            <p class="font-bold text-slate-900">{{ selectedCert?.encryption }}</p>
                        </div>
                    </div>

                    <div class="p-2.5 bg-slate-50 rounded border border-slate-200 space-y-1">
                        <span class="text-slate-400 text-[10px] font-sans font-bold uppercase">Subject Alternative Names (SANs)</span>
                        <div class="flex items-center gap-1.5 flex-wrap pt-0.5">
                            <span v-for="san in selectedCert?.san_domains" :key="san" class="px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-200 text-[10.5px]">
                                {{ san }}
                            </span>
                        </div>
                    </div>

                    <div class="p-2.5 bg-slate-50 rounded border border-slate-200 space-y-1">
                        <span class="text-slate-400 text-[10px] font-sans font-bold uppercase">SHA-256 Fingerprint</span>
                        <p class="font-bold text-slate-700 text-[11px] truncate">{{ selectedCert?.fingerprint }}</p>
                    </div>
                </div>

                <div class="flex justify-end pt-3 border-t border-slate-100">
                    <button 
                        type="button" 
                        @click="showDetailsModal = false" 
                        class="px-4 py-1.5 bg-slate-900 text-white rounded-[3px] text-xs font-bold cursor-pointer"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>

        <!-- 4. Revoke Modal -->
        <div v-if="showRevokeModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-lg max-w-md w-full p-5 shadow-xl space-y-4 border border-slate-200">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2">
                        <TrashIcon class="w-5 h-5 text-rose-600" />
                        <h3 class="font-bold text-slate-900 text-sm">Revoke SSL Certificate</h3>
                    </div>
                    <button @click="showRevokeModal = false" class="text-slate-400 hover:text-slate-600 p-1 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="text-xs text-slate-600 space-y-2">
                    <p>
                        Are you sure you want to revoke the SSL certificate for <strong class="text-slate-900">{{ certToRevoke?.domain }}</strong>?
                    </p>
                    <p class="text-[11px] text-rose-600 font-medium">
                        Visitors may encounter browser "Not Secure" warnings until a new certificate is issued.
                    </p>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button 
                        type="button" 
                        @click="showRevokeModal = false" 
                        class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-[3px] text-xs font-bold cursor-pointer"
                    >
                        Cancel
                    </button>
                    <button 
                        type="button"
                        @click="submitRevoke"
                        :disabled="revokeForm.processing"
                        class="px-4 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-[3px] text-xs font-bold shadow-2xs transition cursor-pointer disabled:opacity-50"
                    >
                        {{ revokeForm.processing ? 'Revoking...' : 'Confirm Revoke' }}
                    </button>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>
