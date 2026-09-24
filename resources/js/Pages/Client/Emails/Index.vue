<script setup>
import { ref, computed } from 'vue'
import { Head, useForm, router, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import { 
    EnvelopeIcon, 
    PlusIcon, 
    KeyIcon, 
    TrashIcon, 
    ArrowTopRightOnSquareIcon,
    ClipboardDocumentIcon,
    CheckIcon,
    ServerIcon,
    ShieldCheckIcon,
    ArrowPathIcon,
    XMarkIcon,
    ExclamationTriangleIcon,
    InformationCircleIcon,
    AdjustmentsHorizontalIcon,
    EyeIcon,
    EyeSlashIcon,
    MagnifyingGlassIcon,
    InboxIcon,
    ArrowsRightLeftIcon,
    ComputerDesktopIcon,
    DevicePhoneMobileIcon,
    CommandLineIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    emails: {
        type: Array,
        default: () => []
    },
    subscriptions: {
        type: Array,
        default: () => []
    },
    quota: {
        type: Object,
        default: () => ({
            total_allowed: 10,
            total_used: 0,
            remaining: 10,
            can_add: true,
            usage_percentage: 0
        })
    },
    connectionInfo: {
        type: Object,
        default: () => ({
            imap_host: 'mail.yourdomain.com',
            imap_port: 993,
            imap_encryption: 'SSL / TLS',
            smtp_host: 'mail.yourdomain.com',
            smtp_port: 465,
            smtp_encryption: 'SSL / TLS',
            pop3_port: 995,
            webmail_url: '/webmail',
            server_hostname: 'deeptouchit.com'
        })
    }
})

// Active Tab
const activeTab = ref('mailboxes') // 'mailboxes' | 'setup'

// Search & Filter
const searchQuery = ref('')
const selectedDomainFilter = ref('')

const filteredEmails = computed(() => {
    return props.emails.filter(acc => {
        const matchesSearch = !searchQuery.value.trim() || 
            acc.email.toLowerCase().includes(searchQuery.value.toLowerCase().trim()) ||
            acc.domain.toLowerCase().includes(searchQuery.value.toLowerCase().trim())
        const matchesDomain = !selectedDomainFilter.value || acc.domain === selectedDomainFilter.value
        return matchesSearch && matchesDomain
    })
})

const uniqueDomains = computed(() => {
    return [...new Set(props.emails.map(e => e.domain))].filter(Boolean)
})

// Modals State
const showCreateModal = ref(false)
const showPasswordModal = ref(false)
const showQuotaModal = ref(false)
const showForwardModal = ref(false)
const showSetupModal = ref(false)
const showDeleteModal = ref(false)

const selectedAccount = ref(null)
const deleteTargetAccount = ref(null)
const deletingAccount = ref(false)

// Copy Feedback
const copiedField = ref(null)
const copyToClipboard = (text, fieldName) => {
    navigator.clipboard.writeText(text)
    copiedField.value = fieldName
    setTimeout(() => {
        copiedField.value = null
    }, 2000)
}

// Password Generator Helper
const generateStrongPassword = () => {
    const chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789!@#$%^&*'
    let result = ''
    for (let i = 0; i < 16; i++) {
        result += chars.charAt(Math.floor(Math.random() * chars.length))
    }
    return result
}

// Create Form
const createForm = useForm({
    subscription_id: props.subscriptions[0]?.id || '',
    username: '',
    password: '',
    quota_mb: 1024,
})

const selectedSubscription = computed(() => {
    return props.subscriptions.find(s => s.id === createForm.subscription_id) || props.subscriptions[0] || null
})

const activeDomain = computed(() => {
    return selectedSubscription.value ? selectedSubscription.value.domain : 'yourdomain.com'
})

const fullEmailPreview = computed(() => {
    const user = createForm.username.trim() || 'username'
    return `${user}@${activeDomain.value}`
})

const fillRandomPassword = () => {
    createForm.password = generateStrongPassword()
}

const submitCreate = () => {
    createForm.post(route('email-accounts.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false
            createForm.reset({
                subscription_id: props.subscriptions[0]?.id || '',
                username: '',
                password: '',
                quota_mb: 1024,
            })
        }
    })
}

// Password Form
const passwordForm = useForm({
    password: '',
})

const openPasswordModal = (acc) => {
    selectedAccount.value = acc
    passwordForm.password = generateStrongPassword()
    showPasswordModal.value = true
}

const submitChangePassword = () => {
    if (!selectedAccount.value) return
    passwordForm.post(route('email-accounts.change-password', selectedAccount.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showPasswordModal.value = false
            selectedAccount.value = null
            passwordForm.reset()
        }
    })
}

// Quota Form
const quotaForm = useForm({
    quota_mb: 1024,
})

const openQuotaModal = (acc) => {
    selectedAccount.value = acc
    quotaForm.quota_mb = acc.quota_mb || 1024
    showQuotaModal.value = true
}

const submitUpdateQuota = () => {
    if (!selectedAccount.value) return
    quotaForm.post(route('email-accounts.update-quota', selectedAccount.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showQuotaModal.value = false
            selectedAccount.value = null
        }
    })
}

// Forwarding Form
const forwardForm = useForm({
    forward_to: '',
})

const openForwardModal = (acc) => {
    selectedAccount.value = acc
    forwardForm.forward_to = acc.forward_to || ''
    showForwardModal.value = true
}

const submitForwarding = () => {
    if (!selectedAccount.value) return
    forwardForm.post(route('email-accounts.forwarding', selectedAccount.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showForwardModal.value = false
            selectedAccount.value = null
        }
    })
}

// Status Toggle
const toggleStatus = (acc) => {
    router.post(route('email-accounts.toggle-status', acc.id), {}, {
        preserveScroll: true
    })
}

// Quick Setup Modal
const openSetupModal = (acc) => {
    selectedAccount.value = acc
    showSetupModal.value = true
}

// Delete Action
const confirmDelete = (acc) => {
    deleteTargetAccount.value = acc
    showDeleteModal.value = true
}

const executeDelete = () => {
    if (!deleteTargetAccount.value) return
    deletingAccount.value = true
    router.delete(route('email-accounts.destroy', deleteTargetAccount.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false
            deleteTargetAccount.value = null
        },
        onFinish: () => {
            deletingAccount.value = false
        }
    })
}
</script>

<template>
    <Head title="Business Email Mailboxes - DeepTouch Host" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto space-y-4">
            
            <!-- 1. Page Header with Breadcrumbs & Actions -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Email Services', href: '#' },
                    { label: 'Business Email Mailboxes' }
                ]"
                :show-refresh="true"
                @refreshed="router.reload({ preserveScroll: true })"
            >
                <template #actions>
                    <a 
                        :href="connectionInfo.webmail_url" 
                        target="_blank"
                        class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-[3px] text-xs font-bold flex items-center gap-1.5 transition cursor-pointer shadow-2xs"
                    >
                        <InboxIcon class="w-3.5 h-3.5 text-indigo-600" />
                        <span>Webmail Portal ↗</span>
                    </a>

                    <button 
                        @click="showCreateModal = true; if (!createForm.password) fillRandomPassword()"
                        :disabled="!quota.can_add"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer disabled:opacity-50"
                    >
                        <PlusIcon class="w-3.5 h-3.5 stroke-[2.5]" />
                        <span>Create Email Account</span>
                    </button>
                </template>
            </PageHeader>

            <!-- 2. Top Metrics & Quick Info Cards (3-Column Grid) -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                
                <!-- Card 1: Mailbox Quota Tracker -->
                <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-2xs space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <EnvelopeIcon class="w-4 h-4 text-blue-600" />
                            <span>Mailbox Quota</span>
                        </span>
                        <span 
                            class="text-[10px] font-bold px-1.5 py-0.5 rounded border uppercase font-mono"
                            :class="quota.can_add ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-amber-50 text-amber-700 border-amber-200'"
                        >
                            {{ quota.remaining }} Available
                        </span>
                    </div>

                    <div>
                        <div class="flex items-baseline gap-2">
                            <span class="text-2xl font-black text-slate-900 font-mono">{{ quota.total_used }}</span>
                            <span class="text-xs text-slate-400 font-bold font-mono">/ {{ quota.total_allowed === 0 ? 'Unlimited' : quota.total_allowed }} Mailboxes</span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-1.5 mt-2 overflow-hidden">
                            <div 
                                class="h-full rounded-full transition-all duration-300"
                                :class="quota.usage_percentage >= 90 ? 'bg-rose-500' : (quota.usage_percentage >= 70 ? 'bg-amber-500' : 'bg-blue-600')"
                                :style="{ width: `${quota.usage_percentage}%` }"
                            ></div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1 border-t border-slate-100">
                        <span>Plan Allocation:</span>
                        <strong class="text-slate-700 font-mono">{{ quota.usage_percentage }}% Used</strong>
                    </div>
                </div>

                <!-- Card 2: Client Connection Parameters -->
                <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-2xs space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <CommandLineIcon class="w-4 h-4 text-emerald-600" />
                            <span>Client Connection (SSL/TLS)</span>
                        </span>
                        <span class="text-[10px] font-mono font-bold text-slate-600 bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200">
                            Port 993 / 465
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-2 text-xs font-mono">
                        <div class="bg-slate-50 p-1.5 rounded border border-slate-200/70">
                            <span class="text-[10px] text-slate-400 block font-bold uppercase">IMAP Host</span>
                            <div class="flex items-center justify-between font-bold text-slate-900 mt-0.5">
                                <span class="truncate">{{ connectionInfo.imap_host }}</span>
                                <button 
                                    @click="copyToClipboard(connectionInfo.imap_host, 'imap_host')" 
                                    class="text-slate-400 hover:text-blue-600 cursor-pointer shrink-0 ml-1"
                                    title="Copy IMAP Host"
                                >
                                    <CheckIcon v-if="copiedField === 'imap_host'" class="w-3.5 h-3.5 text-emerald-600" />
                                    <ClipboardDocumentIcon v-else class="w-3.5 h-3.5" />
                                </button>
                            </div>
                        </div>

                        <div class="bg-slate-50 p-1.5 rounded border border-slate-200/70">
                            <span class="text-[10px] text-slate-400 block font-bold uppercase">SMTP Host</span>
                            <div class="flex items-center justify-between font-bold text-slate-900 mt-0.5">
                                <span class="truncate">{{ connectionInfo.smtp_host }}</span>
                                <button 
                                    @click="copyToClipboard(connectionInfo.smtp_host, 'smtp_host')" 
                                    class="text-slate-400 hover:text-blue-600 cursor-pointer shrink-0 ml-1"
                                    title="Copy SMTP Host"
                                >
                                    <CheckIcon v-if="copiedField === 'smtp_host'" class="w-3.5 h-3.5 text-emerald-600" />
                                    <ClipboardDocumentIcon v-else class="w-3.5 h-3.5" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="text-[10.5px] text-slate-500 pt-1 border-t border-slate-100 flex items-center gap-1">
                        <InformationCircleIcon class="w-3.5 h-3.5 text-blue-600 shrink-0" />
                        <span>Compatible with Outlook, Apple Mail, Thunderbird & Gmail</span>
                    </div>
                </div>

                <!-- Card 3: Webmail Client Studio Launch Card -->
                <div class="bg-gradient-to-br from-indigo-50/70 to-blue-50/50 border border-indigo-100 rounded-lg p-4 shadow-2xs flex flex-col justify-between space-y-2.5">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-indigo-900 flex items-center gap-1.5">
                                <InboxIcon class="w-4 h-4 text-indigo-600" />
                                <span>Webmail Client Portal</span>
                            </span>
                            <span class="text-[10px] font-bold px-1.5 py-0.5 bg-indigo-100 text-indigo-800 rounded font-mono">
                                1-CLICK SSO
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-600 mt-1 line-clamp-2">
                            Check incoming emails, send business letters, and manage address book directly.
                        </p>
                    </div>

                    <div class="pt-2 border-t border-indigo-100">
                        <a 
                            :href="connectionInfo.webmail_url" 
                            target="_blank"
                            class="w-full px-3 py-1.5 bg-white hover:bg-indigo-600 hover:text-white text-indigo-700 border border-indigo-200 rounded-[3px] text-xs font-bold shadow-2xs transition flex items-center justify-center gap-1.5 cursor-pointer"
                        >
                            <span>Launch Webmail Portal</span>
                            <ArrowTopRightOnSquareIcon class="w-3.5 h-3.5" />
                        </a>
                    </div>
                </div>

            </div>

            <!-- 3. Navigation Tabs -->
            <div class="border-b border-slate-200 flex items-center gap-4 text-xs font-bold">
                <button 
                    @click="activeTab = 'mailboxes'"
                    class="pb-2.5 border-b-2 transition cursor-pointer flex items-center gap-1.5"
                    :class="activeTab === 'mailboxes' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-800'"
                >
                    <EnvelopeIcon class="w-4 h-4" />
                    <span>Mailboxes ({{ emails.length }})</span>
                </button>

                <button 
                    @click="activeTab = 'setup'"
                    class="pb-2.5 border-b-2 transition cursor-pointer flex items-center gap-1.5"
                    :class="activeTab === 'setup' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-800'"
                >
                    <ComputerDesktopIcon class="w-4 h-4" />
                    <span>Manual Setup & App Configuration</span>
                </button>
            </div>

            <!-- 4. TAB 1: Mailboxes Table -->
            <div v-if="activeTab === 'mailboxes'" class="bg-white border border-slate-200 rounded-lg shadow-2xs overflow-hidden">
                
                <!-- Table Header & Live Search Bar -->
                <div class="px-4 py-3 bg-slate-50/80 border-b border-slate-200 flex flex-wrap items-center justify-between gap-2.5">
                    <div class="flex items-center gap-2">
                        <EnvelopeIcon class="w-4 h-4 text-indigo-600" />
                        <span class="text-xs font-bold text-slate-900">Provisioned Mailboxes</span>
                        <span class="text-[11px] text-slate-400 font-mono">({{ filteredEmails.length }})</span>
                    </div>

                    <div class="flex items-center gap-2">
                        <div v-if="uniqueDomains.length > 1" class="relative">
                            <select 
                                v-model="selectedDomainFilter"
                                class="bg-white border border-slate-300 rounded-[3px] text-xs font-bold text-slate-700 py-1.5 pl-2.5 pr-7 focus:ring-1 focus:ring-blue-500 cursor-pointer shadow-2xs"
                            >
                                <option value="">All Domains</option>
                                <option v-for="d in uniqueDomains" :key="d" :value="d">{{ d }}</option>
                            </select>
                        </div>

                        <div class="relative">
                            <MagnifyingGlassIcon class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2" />
                            <input 
                                type="text" 
                                v-model="searchQuery" 
                                placeholder="Search mailbox..." 
                                class="pl-8 pr-2.5 py-1.5 text-xs bg-white border border-slate-300 rounded-[3px] focus:ring-blue-500 focus:border-blue-500 w-36 sm:w-48 shadow-2xs"
                            />
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="filteredEmails.length === 0" class="p-12 text-center text-slate-500 text-xs">
                    <EnvelopeIcon class="w-12 h-12 text-slate-300 mx-auto mb-2" />
                    <h4 class="text-sm font-bold text-slate-900 mb-1">No Mailboxes Found</h4>
                    <p class="text-[11px] text-slate-400 max-w-sm mx-auto mb-4">
                        Create branded email accounts with IMAP/SMTP encryption and Roundcube Webmail support.
                    </p>
                    <button 
                        @click="showCreateModal = true; if (!createForm.password) fillRandomPassword()"
                        :disabled="!quota.can_add"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold shadow-2xs transition cursor-pointer disabled:opacity-50"
                    >
                        Create Email Account Now
                    </button>
                </div>

                <!-- Table Content -->
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-700">
                        <thead class="bg-slate-50/90 border-b border-slate-200 text-slate-500 uppercase font-bold tracking-wider text-[10.5px] select-none">
                            <tr>
                                <th class="py-2.5 px-4">Mailbox Address</th>
                                <th class="py-2.5 px-4 w-36">Domain</th>
                                <th class="py-2.5 px-4 w-44">Storage Quota</th>
                                <th class="py-2.5 px-4 w-28">Status</th>
                                <th class="py-2.5 px-4 w-56 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium">
                            <tr v-for="acc in filteredEmails" :key="acc.id" class="hover:bg-slate-50/80 transition-colors">
                                
                                <!-- Mailbox Address -->
                                <td class="py-3 px-4 font-bold text-slate-900 font-mono">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded bg-indigo-50 border border-indigo-200 text-indigo-600 flex items-center justify-center shrink-0">
                                            <EnvelopeIcon class="w-4 h-4" />
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-1.5">
                                                <span>{{ acc.email }}</span>
                                                <button 
                                                    @click="copyToClipboard(acc.email, 'acc_' + acc.id)"
                                                    class="text-slate-400 hover:text-blue-600 cursor-pointer"
                                                    title="Copy email address"
                                                >
                                                    <CheckIcon v-if="copiedField === 'acc_' + acc.id" class="w-3.5 h-3.5 text-emerald-600" />
                                                    <ClipboardDocumentIcon v-else class="w-3.5 h-3.5" />
                                                </button>
                                            </div>
                                            <div v-if="acc.forward_to" class="text-[10.5px] text-amber-700 font-sans flex items-center gap-1 mt-0.5">
                                                <ArrowsRightLeftIcon class="w-3 h-3" />
                                                <span>Forwards to: {{ acc.forward_to }}</span>
                                            </div>
                                            <div v-else class="text-[10px] text-slate-400 font-sans">
                                                Created: {{ acc.created_at || 'Recently' }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Domain -->
                                <td class="py-3 px-4 font-mono text-[11px] text-slate-700 font-bold">
                                    <span class="px-2 py-0.5 bg-slate-100 rounded-[3px] border border-slate-200">
                                        {{ acc.domain }}
                                    </span>
                                </td>

                                <!-- Storage Quota Progress -->
                                <td class="py-3 px-4">
                                    <div class="space-y-1">
                                        <div class="flex items-center justify-between text-[10.5px] font-mono">
                                            <span class="font-bold text-slate-800">{{ acc.used_quota_mb }} MB</span>
                                            <span class="text-slate-400">/ {{ acc.quota_mb }} MB</span>
                                        </div>
                                        <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                            <div 
                                                class="h-full rounded-full transition-all duration-300"
                                                :class="acc.usage_percent > 85 ? 'bg-rose-500' : 'bg-emerald-500'"
                                                :style="{ width: `${acc.usage_percent}%` }"
                                            ></div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Status -->
                                <td class="py-3 px-4">
                                    <button 
                                        @click="toggleStatus(acc)"
                                        type="button"
                                        title="Click to toggle status"
                                        class="px-2 py-0.5 rounded text-[10px] font-bold border uppercase font-mono cursor-pointer transition"
                                        :class="acc.status === 'active' ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100' : 'bg-amber-50 text-amber-700 border-amber-200 hover:bg-amber-100'"
                                    >
                                        ● {{ acc.status }}
                                    </button>
                                </td>

                                <!-- Actions -->
                                <td class="py-3 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        
                                        <!-- 1-Click Webmail SSO -->
                                        <a 
                                            :href="route('email-accounts.webmail', acc.id)" 
                                            target="_blank"
                                            title="1-Click Webmail Login"
                                            class="px-2.5 py-1 bg-white hover:bg-indigo-50 text-indigo-700 border border-indigo-200 rounded-[3px] text-xs font-bold shadow-2xs transition flex items-center gap-1 cursor-pointer"
                                        >
                                            <InboxIcon class="w-3.5 h-3.5" />
                                            <span>Webmail</span>
                                            <ArrowTopRightOnSquareIcon class="w-3 h-3 text-indigo-400" />
                                        </a>

                                        <!-- Quick Setup Details Modal -->
                                        <button 
                                            @click="openSetupModal(acc)"
                                            title="View Mail Client Setup Credentials"
                                            class="p-1.5 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-[3px] shadow-2xs transition cursor-pointer"
                                        >
                                            <CommandLineIcon class="w-3.5 h-3.5 text-blue-600" />
                                        </button>

                                        <!-- Forwarding Setup -->
                                        <button 
                                            @click="openForwardModal(acc)"
                                            title="Set Email Forwarding"
                                            class="p-1.5 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-[3px] shadow-2xs transition cursor-pointer"
                                        >
                                            <ArrowsRightLeftIcon class="w-3.5 h-3.5 text-amber-600" />
                                        </button>

                                        <!-- Change Password -->
                                        <button 
                                            @click="openPasswordModal(acc)"
                                            title="Change Mailbox Password"
                                            class="p-1.5 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-[3px] shadow-2xs transition cursor-pointer"
                                        >
                                            <KeyIcon class="w-3.5 h-3.5 text-slate-600" />
                                        </button>

                                        <!-- Edit Quota -->
                                        <button 
                                            @click="openQuotaModal(acc)"
                                            title="Update Storage Quota"
                                            class="p-1.5 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-[3px] shadow-2xs transition cursor-pointer"
                                        >
                                            <AdjustmentsHorizontalIcon class="w-3.5 h-3.5 text-slate-600" />
                                        </button>

                                        <!-- Delete Account -->
                                        <button 
                                            @click="confirmDelete(acc)"
                                            title="Delete Mailbox"
                                            class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded transition cursor-pointer"
                                        >
                                            <TrashIcon class="w-4 h-4" />
                                        </button>

                                    </div>
                                </td>

                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="px-4 py-2.5 bg-slate-50 border-t border-slate-200 text-[11px] text-slate-500 flex items-center justify-between">
                    <span>Postfix MTA & Dovecot IMAP/POP3 Mail Server.</span>
                    <span class="font-mono">TLS 1.3 • Anti-Spam Filtering Active</span>
                </div>

            </div>

            <!-- 5. TAB 2: Manual Setup & App Configuration -->
            <div v-else-if="activeTab === 'setup'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                
                <!-- Secure SSL/TLS Settings (Recommended) -->
                <div class="bg-white border border-slate-200 rounded-lg p-5 shadow-2xs space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-2">
                            <ShieldCheckIcon class="w-5 h-5 text-emerald-600" />
                            <h3 class="font-bold text-slate-900 text-sm">Secure SSL / TLS Settings (Recommended)</h3>
                        </div>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200 font-mono">
                            ENCRYPTED
                        </span>
                    </div>

                    <div class="space-y-3 text-xs">
                        <div class="bg-slate-50 p-3 rounded border border-slate-200 space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-slate-600 uppercase text-[10px]">Incoming Server (IMAP)</span>
                                <span class="font-mono font-bold text-slate-800">Port 993</span>
                            </div>
                            <div class="font-mono font-bold text-blue-700 text-sm flex items-center justify-between">
                                <span>{{ connectionInfo.imap_host }}</span>
                                <button 
                                    @click="copyToClipboard(connectionInfo.imap_host, 'imap_host_tab')"
                                    class="text-slate-400 hover:text-blue-600 cursor-pointer"
                                >
                                    <CheckIcon v-if="copiedField === 'imap_host_tab'" class="w-3.5 h-3.5 text-emerald-600" />
                                    <ClipboardDocumentIcon v-else class="w-3.5 h-3.5" />
                                </button>
                            </div>
                        </div>

                        <div class="bg-slate-50 p-3 rounded border border-slate-200 space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-slate-600 uppercase text-[10px]">Outgoing Server (SMTP)</span>
                                <span class="font-mono font-bold text-slate-800">Port 465 (SSL) / 587 (STARTTLS)</span>
                            </div>
                            <div class="font-mono font-bold text-blue-700 text-sm flex items-center justify-between">
                                <span>{{ connectionInfo.smtp_host }}</span>
                                <button 
                                    @click="copyToClipboard(connectionInfo.smtp_host, 'smtp_host_tab')"
                                    class="text-slate-400 hover:text-blue-600 cursor-pointer"
                                >
                                    <CheckIcon v-if="copiedField === 'smtp_host_tab'" class="w-3.5 h-3.5 text-emerald-600" />
                                    <ClipboardDocumentIcon v-else class="w-3.5 h-3.5" />
                                </button>
                            </div>
                        </div>

                        <div class="p-3 bg-blue-50 border border-blue-200 rounded text-[11px] text-blue-800 space-y-1">
                            <div class="font-bold">Authentication Requirement:</div>
                            <p>Use your full email address (e.g. <code>info@yourdomain.com</code>) as Username and mailbox password.</p>
                        </div>
                    </div>
                </div>

                <!-- Client App Compatibility -->
                <div class="bg-white border border-slate-200 rounded-lg p-5 shadow-2xs space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-2">
                            <ComputerDesktopIcon class="w-5 h-5 text-blue-600" />
                            <h3 class="font-bold text-slate-900 text-sm">Supported Mail Clients</h3>
                        </div>
                    </div>

                    <div class="space-y-2.5 text-xs text-slate-600">
                        <div class="p-2.5 bg-slate-50 rounded border border-slate-100 flex items-start gap-2.5">
                            <DevicePhoneMobileIcon class="w-5 h-5 text-slate-500 shrink-0 mt-0.5" />
                            <div>
                                <h4 class="font-bold text-slate-800">iOS & Android Mail</h4>
                                <p class="text-[11px] text-slate-500">Go to Settings > Mail > Accounts > Add Account > Other, then enter the IMAP and SMTP parameters.</p>
                            </div>
                        </div>

                        <div class="p-2.5 bg-slate-50 rounded border border-slate-100 flex items-start gap-2.5">
                            <ComputerDesktopIcon class="w-5 h-5 text-slate-500 shrink-0 mt-0.5" />
                            <div>
                                <h4 class="font-bold text-slate-800">Microsoft Outlook & Thunderbird</h4>
                                <p class="text-[11px] text-slate-500">Enter your email and password, choose IMAP account type, and enable SSL/TLS encryption.</p>
                            </div>
                        </div>

                        <div class="p-2.5 bg-slate-50 rounded border border-slate-100 flex items-start gap-2.5">
                            <InboxIcon class="w-5 h-5 text-slate-500 shrink-0 mt-0.5" />
                            <div>
                                <h4 class="font-bold text-slate-800">Gmail Send Mail As</h4>
                                <p class="text-[11px] text-slate-500">Add SMTP server in Gmail Settings > Accounts and Import > Send mail as.</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>

        <!-- ========================================================= -->
        <!-- MODALS                                                    -->
        <!-- ========================================================= -->
        <Teleport to="body">
            
            <!-- 1. Create Mailbox Modal -->
            <div v-if="showCreateModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4 z-50">
                <div class="bg-white rounded-lg max-w-md w-full p-5 shadow-2xl space-y-4 border border-slate-200">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-2">
                            <EnvelopeIcon class="w-5 h-5 text-indigo-600" />
                            <h3 class="font-bold text-slate-900 text-sm">Create Business Email Account</h3>
                        </div>
                        <button @click="showCreateModal = false" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                            <XMarkIcon class="w-4 h-4" />
                        </button>
                    </div>

                    <form @submit.prevent="submitCreate" class="space-y-4 text-xs">
                        
                        <!-- Subscription Selector -->
                        <div class="space-y-1.5">
                            <label class="block font-bold text-slate-700">Target Domain</label>
                            <select 
                                v-model="createForm.subscription_id" 
                                required 
                                class="w-full bg-white border border-slate-300 rounded-[3px] px-2.5 py-2 text-xs font-bold text-slate-900 focus:ring-1 focus:ring-blue-600 focus:border-blue-600"
                            >
                                <option v-for="sub in subscriptions" :key="sub.id" :value="sub.id">
                                    {{ sub.domain }} • {{ sub.plan_name }}
                                </option>
                            </select>
                        </div>

                        <!-- Username with Domain Extension -->
                        <div class="space-y-1.5">
                            <label class="block font-bold text-slate-700">Mailbox Username</label>
                            <div class="flex rounded-[3px] shadow-2xs">
                                <input 
                                    v-model="createForm.username" 
                                    type="text" 
                                    required 
                                    placeholder="e.g. info, support, contact" 
                                    class="flex-1 min-w-0 bg-white border border-slate-300 rounded-l-[3px] px-2.5 py-2 text-xs font-mono font-bold text-slate-900 focus:ring-1 focus:ring-blue-600 focus:border-blue-600 text-right" 
                                />
                                <span class="inline-flex items-center px-3 rounded-r-[3px] border border-l-0 border-slate-300 bg-slate-100 text-slate-600 font-mono font-bold text-xs">
                                    @{{ activeDomain }}
                                </span>
                            </div>
                            <span v-if="createForm.errors.username" class="text-[11px] text-rose-600 font-bold block mt-1">
                                {{ createForm.errors.username }}
                            </span>
                        </div>

                        <!-- Password with Generator -->
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between">
                                <label class="font-bold text-slate-700">Mailbox Password</label>
                                <button 
                                    type="button" 
                                    @click="fillRandomPassword" 
                                    class="text-[10px] font-bold text-blue-600 hover:underline cursor-pointer flex items-center gap-1"
                                >
                                    <ArrowPathIcon class="w-3 h-3" />
                                    <span>Generate Strong</span>
                                </button>
                            </div>
                            <input 
                                v-model="createForm.password" 
                                type="text" 
                                required 
                                placeholder="Enter secure password" 
                                class="w-full bg-white border border-slate-300 rounded-[3px] px-2.5 py-2 text-xs font-mono font-bold text-slate-900 focus:ring-1 focus:ring-blue-600 focus:border-blue-600" 
                            />
                            <span v-if="createForm.errors.password" class="text-[11px] text-rose-600 font-bold block mt-1">
                                {{ createForm.errors.password }}
                            </span>
                        </div>

                        <!-- Storage Quota -->
                        <div class="space-y-1.5">
                            <label class="block font-bold text-slate-700">Storage Quota (MB)</label>
                            <input 
                                v-model.number="createForm.quota_mb" 
                                type="number" 
                                min="100" 
                                max="10240" 
                                required 
                                class="w-full bg-white border border-slate-300 rounded-[3px] px-2.5 py-2 text-xs font-mono font-bold text-slate-900 focus:ring-1 focus:ring-blue-600 focus:border-blue-600" 
                            />
                        </div>

                        <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                            <button 
                                type="button" 
                                @click="showCreateModal = false" 
                                class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-[3px] text-xs font-bold cursor-pointer"
                            >
                                Cancel
                            </button>
                            <button 
                                type="submit" 
                                :disabled="createForm.processing"
                                class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold shadow-2xs transition cursor-pointer disabled:opacity-50 flex items-center gap-1"
                            >
                                <span>{{ createForm.processing ? 'Provisioning...' : 'Create Mailbox' }}</span>
                            </button>
                        </div>

                    </form>
                </div>
            </div>

            <!-- 2. Change Password Modal -->
            <div v-if="showPasswordModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4 z-50">
                <div class="bg-white rounded-lg max-w-sm w-full p-5 shadow-2xl space-y-4 border border-slate-200">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                        <h3 class="font-bold text-slate-900 text-sm">Reset Password for {{ selectedAccount?.email }}</h3>
                        <button @click="showPasswordModal = false" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                            <XMarkIcon class="w-4 h-4" />
                        </button>
                    </div>

                    <form @submit.prevent="submitChangePassword" class="space-y-4 text-xs">
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between">
                                <label class="font-bold text-slate-700">New Password</label>
                                <button 
                                    type="button" 
                                    @click="passwordForm.password = generateStrongPassword()" 
                                    class="text-[10px] font-bold text-blue-600 hover:underline cursor-pointer flex items-center gap-1"
                                >
                                    <ArrowPathIcon class="w-3 h-3" />
                                    <span>Generate Strong</span>
                                </button>
                            </div>
                            <input 
                                v-model="passwordForm.password" 
                                type="text" 
                                required 
                                placeholder="Enter new password" 
                                class="w-full bg-white border border-slate-300 rounded-[3px] px-2.5 py-2 text-xs font-mono font-bold text-slate-900 focus:ring-1 focus:ring-blue-600 focus:border-blue-600" 
                            />
                        </div>

                        <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                            <button 
                                type="button" 
                                @click="showPasswordModal = false" 
                                class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-[3px] text-xs font-bold cursor-pointer"
                            >
                                Cancel
                            </button>
                            <button 
                                type="submit" 
                                :disabled="passwordForm.processing"
                                class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold shadow-2xs cursor-pointer"
                            >
                                Update Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 3. Edit Quota Modal -->
            <div v-if="showQuotaModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4 z-50">
                <div class="bg-white rounded-lg max-w-sm w-full p-5 shadow-2xl space-y-4 border border-slate-200">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                        <h3 class="font-bold text-slate-900 text-sm">Update Quota for {{ selectedAccount?.email }}</h3>
                        <button @click="showQuotaModal = false" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                            <XMarkIcon class="w-4 h-4" />
                        </button>
                    </div>

                    <form @submit.prevent="submitUpdateQuota" class="space-y-4 text-xs">
                        <div class="space-y-1.5">
                            <label class="font-bold text-slate-700">Storage Limit (MB)</label>
                            <input 
                                v-model.number="quotaForm.quota_mb" 
                                type="number" 
                                min="100" 
                                max="10240" 
                                required 
                                class="w-full bg-white border border-slate-300 rounded-[3px] px-2.5 py-2 text-xs font-mono font-bold text-slate-900 focus:ring-1 focus:ring-blue-600 focus:border-blue-600" 
                            />
                        </div>

                        <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                            <button 
                                type="button" 
                                @click="showQuotaModal = false" 
                                class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-[3px] text-xs font-bold cursor-pointer"
                            >
                                Cancel
                            </button>
                            <button 
                                type="submit" 
                                :disabled="quotaForm.processing"
                                class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold shadow-2xs cursor-pointer"
                            >
                                Save Quota
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 4. Email Forwarding Modal -->
            <div v-if="showForwardModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4 z-50">
                <div class="bg-white rounded-lg max-w-sm w-full p-5 shadow-2xl space-y-4 border border-slate-200">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                        <h3 class="font-bold text-slate-900 text-sm">Forwarding for {{ selectedAccount?.email }}</h3>
                        <button @click="showForwardModal = false" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                            <XMarkIcon class="w-4 h-4" />
                        </button>
                    </div>

                    <form @submit.prevent="submitForwarding" class="space-y-4 text-xs">
                        <div class="space-y-1.5">
                            <label class="font-bold text-slate-700">Forward All Incoming Emails To</label>
                            <input 
                                v-model="forwardForm.forward_to" 
                                type="email" 
                                placeholder="e.g. personal@gmail.com (leave blank to disable)" 
                                class="w-full bg-white border border-slate-300 rounded-[3px] px-2.5 py-2 text-xs font-mono text-slate-900 focus:ring-1 focus:ring-blue-600 focus:border-blue-600" 
                            />
                            <p class="text-[10px] text-slate-400">Incoming emails will be delivered locally and a copy will be forwarded.</p>
                        </div>

                        <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                            <button 
                                type="button" 
                                @click="showForwardModal = false" 
                                class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-[3px] text-xs font-bold cursor-pointer"
                            >
                                Cancel
                            </button>
                            <button 
                                type="submit" 
                                :disabled="forwardForm.processing"
                                class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold shadow-2xs cursor-pointer"
                            >
                                Save Forwarding
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 5. Quick Setup Details Modal -->
            <div v-if="showSetupModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4 z-50">
                <div class="bg-white rounded-lg max-w-md w-full p-5 shadow-2xl space-y-4 border border-slate-200">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                        <h3 class="font-bold text-slate-900 text-sm">Connection Credentials: {{ selectedAccount?.email }}</h3>
                        <button @click="showSetupModal = false" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                            <XMarkIcon class="w-4 h-4" />
                        </button>
                    </div>

                    <div class="space-y-3 text-xs">
                        <div class="bg-slate-50 p-2.5 rounded border border-slate-200/70 font-mono space-y-1">
                            <span class="text-[10px] text-slate-400 block font-bold uppercase font-sans">Username / Login</span>
                            <div class="flex items-center justify-between font-bold text-slate-900">
                                <span>{{ selectedAccount?.email }}</span>
                                <button 
                                    @click="copyToClipboard(selectedAccount?.email, 'setup_user')"
                                    class="text-slate-400 hover:text-blue-600 cursor-pointer"
                                >
                                    <CheckIcon v-if="copiedField === 'setup_user'" class="w-3.5 h-3.5 text-emerald-600" />
                                    <ClipboardDocumentIcon v-else class="w-3.5 h-3.5" />
                                </button>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2 text-xs font-mono">
                            <div class="bg-slate-50 p-2 rounded border border-slate-200/70">
                                <span class="text-[10px] text-slate-400 block font-bold uppercase font-sans">IMAP Server</span>
                                <div class="font-bold text-slate-900 mt-0.5 truncate">{{ connectionInfo.imap_host }}</div>
                                <span class="text-[10px] text-slate-500 font-sans">Port 993 (SSL)</span>
                            </div>

                            <div class="bg-slate-50 p-2 rounded border border-slate-200/70">
                                <span class="text-[10px] text-slate-400 block font-bold uppercase font-sans">SMTP Server</span>
                                <div class="font-bold text-slate-900 mt-0.5 truncate">{{ connectionInfo.smtp_host }}</div>
                                <span class="text-[10px] text-slate-500 font-sans">Port 465 (SSL)</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end pt-2 border-t border-slate-100">
                        <button 
                            type="button" 
                            @click="showSetupModal = false" 
                            class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold cursor-pointer"
                        >
                            Done
                        </button>
                    </div>
                </div>
            </div>

            <!-- 6. Delete Confirmation Modal -->
            <div v-if="showDeleteModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4 z-50">
                <div class="bg-white rounded-lg max-w-md w-full p-5 shadow-2xl border border-slate-200 space-y-4">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-lg bg-rose-50 border border-rose-100 text-rose-600 flex items-center justify-center shrink-0">
                            <TrashIcon class="w-5 h-5 stroke-[1.75]" />
                        </div>
                        <div class="flex-1">
                            <h3 class="text-sm font-black text-slate-900">Delete Mailbox?</h3>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Are you sure you want to delete mailbox <strong class="text-rose-600 font-mono">{{ deleteTargetAccount?.email }}</strong>? All stored emails, sent messages, and attachments will be deleted permanently from the mail server.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                        <button 
                            @click="showDeleteModal = false; deleteTargetAccount = null" 
                            type="button" 
                            :disabled="deletingAccount"
                            class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-[3px] text-xs font-bold transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button 
                            @click="executeDelete" 
                            type="button"
                            :disabled="deletingAccount"
                            class="px-4 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-[3px] text-xs font-bold shadow-2xs transition cursor-pointer flex items-center gap-1.5 disabled:opacity-50"
                        >
                            <TrashIcon class="w-3.5 h-3.5" />
                            <span>{{ deletingAccount ? 'Deleting...' : 'Delete Mailbox' }}</span>
                        </button>
                    </div>
                </div>
            </div>

        </Teleport>

    </AuthenticatedLayout>
</template>
