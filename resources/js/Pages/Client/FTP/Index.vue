<script setup>
import { ref, computed } from 'vue'
import { Head, useForm, Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import { 
    FolderIcon, 
    PlusIcon, 
    TrashIcon, 
    KeyIcon,
    ArrowDownTrayIcon,
    ClipboardDocumentIcon,
    CheckIcon,
    ServerIcon,
    ShieldCheckIcon,
    ArrowPathIcon,
    XMarkIcon,
    ExclamationTriangleIcon,
    ArrowUpCircleIcon,
    InformationCircleIcon,
    LockClosedIcon,
    MagnifyingGlassIcon,
    CommandLineIcon,
    ArrowsRightLeftIcon,
    GlobeAltIcon,
    PauseCircleIcon,
    PlayCircleIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    ftpAccounts: {
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
            total_allowed: 1,
            total_used: 0,
            remaining: 1,
            can_add: true,
            usage_percentage: 0
        })
    },
    serverInfo: {
        type: Object,
        default: () => ({
            public_ip: '103.59.177.138',
            ftp_host: '103.59.177.138',
            primary_domain: 'somitysoft.com',
            ftp_port: 21,
            passive_ports: '30000 - 31000',
            protocols: 'FTP / FTPS (Explicit TLS/SSL)'
        })
    }
})

// Search & Filter
const searchQuery = ref('')
const filteredAccounts = computed(() => {
    if (!searchQuery.value.trim()) return props.ftpAccounts
    const q = searchQuery.value.toLowerCase().trim()
    return props.ftpAccounts.filter(a => 
        a.username.toLowerCase().includes(q) || 
        a.display_path.toLowerCase().includes(q) ||
        a.subscription?.domain?.toLowerCase().includes(q)
    )
})

// Modals
const showCreateModal = ref(false)
const showPasswordModal = ref(false)
const selectedAccount = ref(null)
const showDeleteModal = ref(false)
const deleteTargetAccount = ref(null)
const deletingAccount = ref(false)
const showQuickConnectModal = ref(false)
const quickConnectAccount = ref(null)

// Copy feedback
const copiedText = ref('')
const copyToClipboard = (text, label) => {
    navigator.clipboard.writeText(text)
    copiedText.value = label
    setTimeout(() => {
        copiedText.value = ''
    }, 2000)
}

// Create Form
const createForm = useForm({
    subscription_id: props.subscriptions[0]?.id || '',
    username: '',
    password: '',
    path: 'public_html',
    permissions: 'readwrite'
})

// Password Form
const passwordForm = useForm({
    password: ''
})

// Password Generator helper
const generateRandomPassword = () => {
    const chars = 'abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789!@#$%&*'
    let pwd = ''
    for (let i = 0; i < 14; i++) {
        pwd += chars.charAt(Math.floor(Math.random() * chars.length))
    }
    return pwd
}

const fillRandomPassword = () => {
    createForm.password = generateRandomPassword()
}

const fillRandomPasswordForReset = () => {
    passwordForm.password = generateRandomPassword()
}

// Actions
const submitCreate = () => {
    createForm.post(route('ftp-accounts.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false
            createForm.reset()
        }
    })
}

const openPasswordModal = (acc) => {
    selectedAccount.value = acc
    passwordForm.password = ''
    showPasswordModal.value = true
}

const submitPasswordChange = () => {
    if (!selectedAccount.value) return
    passwordForm.post(route('ftp-accounts.change-password', selectedAccount.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showPasswordModal.value = false
            passwordForm.reset()
        }
    })
}

const toggleAccountStatus = (acc) => {
    router.post(route('ftp-accounts.toggle-status', acc.id), {}, {
        preserveScroll: true
    })
}

const downloadFileZillaXml = (acc) => {
    window.open(route('ftp-accounts.filezilla', acc.id), '_blank')
}

const openQuickConnect = (acc) => {
    quickConnectAccount.value = acc
    showQuickConnectModal.value = true
}

const confirmDeleteAccount = (acc) => {
    deleteTargetAccount.value = acc
    showDeleteModal.value = true
}

const executeDeleteAccount = () => {
    if (!deleteTargetAccount.value) return
    deletingAccount.value = true
    router.delete(route('ftp-accounts.destroy', deleteTargetAccount.value.id), {
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
    <Head title="FTP Accounts & Access - DeepTouch Cloud" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto space-y-4">
            
            <!-- 1. Page Header with Breadcrumbs & Actions -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Files', href: '#' },
                    { label: 'FTP Accounts & Access' }
                ]"
                :show-refresh="true"
                @refreshed="router.reload({ preserveScroll: true })"
            >
                <template #actions>
                    <button 
                        v-if="quota.can_add"
                        @click="showCreateModal = true"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <PlusIcon class="w-3.5 h-3.5 stroke-[2.5]" />
                        <span>Create FTP Account</span>
                    </button>
                    <!-- Quota Full Notice -->
                    <div v-else class="flex items-center gap-2">
                        <span class="px-2.5 py-1 rounded bg-amber-50 text-amber-800 border border-amber-200 text-xs font-bold">
                            FTP Quota Full ({{ quota.total_used }}/{{ quota.total_allowed }})
                        </span>
                        <Link 
                            :href="route('hosting.upgrade')" 
                            class="px-3 py-1.5 bg-amber-500 hover:bg-amber-600 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition"
                        >
                            <ArrowUpCircleIcon class="w-3.5 h-3.5" />
                            <span>Upgrade Plan</span>
                        </Link>
                    </div>
                </template>
            </PageHeader>

            <!-- 2. Top Analytics & Connection Reference Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                
                <!-- Card 1: Active Accounts & Daemon Status -->
                <div class="bg-white rounded-lg border border-slate-200 p-4 shadow-2xs space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <ServerIcon class="w-4 h-4 text-blue-600" />
                            <span>Active FTP Accounts</span>
                        </span>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200 uppercase font-mono flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            DAEMON ONLINE
                        </span>
                    </div>

                    <div>
                        <span class="text-2xl font-black text-slate-900 font-mono">{{ ftpAccounts.length }}</span>
                        <span class="text-xs text-slate-500 font-mono ml-1.5">virtual user(s)</span>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1 border-t border-slate-100">
                        <span>Engine:</span>
                        <strong class="text-slate-700">vsftpd (Port 21 / TLS 1.3)</strong>
                    </div>
                </div>

                <!-- Card 2: Package Quota Allocation -->
                <div class="bg-white rounded-lg border border-slate-200 p-4 shadow-2xs space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <LockClosedIcon class="w-4 h-4 text-purple-600" />
                            <span>FTP Quota Allocation</span>
                        </span>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-purple-50 text-purple-700 border border-purple-200 font-mono">
                            {{ quota.total_used }} / {{ quota.total_allowed }} USED
                        </span>
                    </div>

                    <div>
                        <div class="flex items-baseline justify-between">
                            <span class="text-2xl font-black text-purple-600 font-mono">{{ quota.remaining }}</span>
                            <span class="text-xs text-slate-500 font-mono">Remaining Slots</span>
                        </div>
                        <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden mt-2">
                            <div 
                                class="h-full rounded-full transition-all duration-300"
                                :class="quota.usage_percentage >= 100 ? 'bg-amber-500' : 'bg-purple-600'"
                                :style="{ width: `${quota.usage_percentage}%` }"
                            ></div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1 border-t border-slate-100">
                        <span>Status:</span>
                        <strong :class="quota.can_add ? 'text-emerald-700' : 'text-amber-700'">
                            {{ quota.can_add ? 'Ready to Provision' : 'Limit Reached' }}
                        </strong>
                    </div>
                </div>

                <!-- Card 3: Direct Connection Credentials -->
                <div class="bg-white rounded-lg border border-slate-200 p-4 shadow-2xs space-y-2">
                    <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                        <CommandLineIcon class="w-4 h-4 text-emerald-600" />
                        <span>FTP Connection Host & Port</span>
                    </span>
                    
                    <div class="space-y-1.5 text-xs font-mono">
                        <div class="flex items-center justify-between bg-slate-50 px-2.5 py-1 rounded border border-slate-200/70">
                            <span class="text-slate-500 text-[11px]">FTP Host:</span>
                            <button 
                                @click="copyToClipboard(serverInfo.public_ip, 'host')"
                                class="font-bold text-slate-800 hover:text-blue-600 flex items-center gap-1 transition cursor-pointer"
                            >
                                <span>{{ serverInfo.public_ip }}</span>
                                <ClipboardDocumentIcon class="w-3.5 h-3.5 text-slate-400" />
                            </button>
                        </div>

                        <div class="flex items-center justify-between bg-slate-50 px-2.5 py-1 rounded border border-slate-200/70">
                            <span class="text-slate-500 text-[11px]">FTP Port:</span>
                            <span class="font-bold text-slate-800">21 (Explicit TLS)</span>
                        </div>
                    </div>

                    <div v-if="copiedText" class="text-[10px] text-emerald-600 font-bold text-center">
                        ✓ Copied to clipboard!
                    </div>
                </div>

            </div>

            <!-- 3. Provisioned Accounts Table & Management -->
            <div class="bg-white rounded-lg border border-slate-200 shadow-2xs overflow-hidden">
                
                <!-- Table Header & Live Search Bar -->
                <div class="px-4 py-3 bg-slate-50/80 border-b border-slate-200 flex flex-wrap items-center justify-between gap-2.5">
                    <div class="flex items-center gap-2">
                        <ServerIcon class="w-4 h-4 text-blue-600" />
                        <span class="text-xs font-bold text-slate-900">Provisioned FTP Accounts</span>
                        <span class="text-[11px] text-slate-400 font-mono">({{ filteredAccounts.length }})</span>
                    </div>

                    <div class="relative">
                        <MagnifyingGlassIcon class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2" />
                        <input 
                            type="text" 
                            v-model="searchQuery" 
                            placeholder="Filter accounts..." 
                            class="pl-8 pr-2.5 py-1.5 text-xs bg-white border border-slate-300 rounded-[3px] focus:ring-blue-500 focus:border-blue-500 w-36 sm:w-48 shadow-2xs"
                        />
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="filteredAccounts.length === 0" class="p-12 text-center text-slate-500 text-xs">
                    <ServerIcon class="w-12 h-12 text-slate-300 mx-auto mb-2" />
                    <p class="font-bold text-slate-700">No FTP accounts found</p>
                    <p class="text-[11px] text-slate-400 mt-0.5">Click "Create FTP Account" above to create credentials for FileZilla or VS Code.</p>
                </div>

                <!-- Accounts Table -->
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-700">
                        <thead class="bg-slate-50/90 text-slate-500 font-bold uppercase tracking-wider text-[10.5px] border-b border-slate-200 select-none">
                            <tr>
                                <th class="py-2.5 px-4">FTP Username</th>
                                <th class="py-2.5 px-4">Locked Root Directory</th>
                                <th class="py-2.5 px-4 w-40">Domain / Plan</th>
                                <th class="py-2.5 px-4 w-28">Permissions</th>
                                <th class="py-2.5 px-4 w-24">Status</th>
                                <th class="py-2.5 px-4 w-48 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium">
                            <tr 
                                v-for="acc in filteredAccounts" 
                                :key="acc.id"
                                class="hover:bg-slate-50/80 transition-colors"
                            >
                                <!-- Username -->
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded bg-blue-50 border border-blue-200 text-blue-600 flex items-center justify-center font-bold shrink-0">
                                            <ServerIcon class="w-4 h-4" />
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-1.5 font-bold text-slate-900 font-mono">
                                                <span>{{ acc.username }}</span>
                                                <button 
                                                    @click="copyToClipboard(acc.username, 'user-' + acc.id)"
                                                    title="Copy Username"
                                                    class="text-slate-400 hover:text-blue-600 transition cursor-pointer"
                                                >
                                                    <ClipboardDocumentIcon class="w-3.5 h-3.5" />
                                                </button>
                                            </div>
                                            <span class="text-[10px] text-slate-400">Created: {{ acc.created_at }}</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Locked Directory -->
                                <td class="py-3 px-4 font-mono text-xs">
                                    <div class="flex items-center gap-1.5">
                                        <FolderIcon class="w-4 h-4 text-amber-500 shrink-0" />
                                        <span class="bg-slate-100 px-2 py-0.5 rounded text-[11px] font-bold text-slate-800">{{ acc.display_path }}</span>
                                    </div>
                                </td>

                                <!-- Domain / Plan -->
                                <td class="py-3 px-4 text-slate-600">
                                    <span class="font-bold text-slate-900 block font-mono text-[11px]">{{ acc.subscription.domain }}</span>
                                    <span class="text-[10px] text-slate-400">{{ acc.subscription.plan_name }}</span>
                                </td>

                                <!-- Permissions -->
                                <td class="py-3 px-4">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200 uppercase font-mono">
                                        {{ acc.permissions === 'readwrite' ? 'Read / Write' : 'Read Only' }}
                                    </span>
                                </td>

                                <!-- Status Toggle -->
                                <td class="py-3 px-4">
                                    <button 
                                        @click="toggleAccountStatus(acc)"
                                        class="px-2 py-0.5 rounded text-[10px] font-bold border uppercase font-mono transition cursor-pointer flex items-center gap-1"
                                        :class="acc.status === 'active' ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100' : 'bg-rose-50 text-rose-700 border-rose-200 hover:bg-rose-100'"
                                        title="Click to toggle status"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full" :class="acc.status === 'active' ? 'bg-emerald-600' : 'bg-rose-600'"></span>
                                        <span>{{ acc.status }}</span>
                                    </button>
                                </td>

                                <!-- Actions -->
                                <td class="py-3 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        
                                        <!-- Quick Connect Details -->
                                        <button 
                                            @click="openQuickConnect(acc)"
                                            class="p-1.5 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-[3px] shadow-2xs transition cursor-pointer flex items-center gap-1 font-bold text-[11px]"
                                            title="Quick Connect Credentials"
                                        >
                                            <CommandLineIcon class="w-3.5 h-3.5 text-slate-600" />
                                            <span class="hidden sm:inline">Connect</span>
                                        </button>

                                        <!-- FileZilla XML Config Download -->
                                        <button 
                                            @click="downloadFileZillaXml(acc)"
                                            title="Download FileZilla Site XML Configuration"
                                            class="p-1.5 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-[3px] shadow-2xs transition cursor-pointer flex items-center gap-1 font-bold text-[11px]"
                                        >
                                            <ArrowDownTrayIcon class="w-3.5 h-3.5 text-blue-600" />
                                            <span class="hidden sm:inline">FileZilla</span>
                                        </button>

                                        <!-- Change Password -->
                                        <button 
                                            @click="openPasswordModal(acc)"
                                            title="Change Password"
                                            class="p-1.5 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-[3px] shadow-2xs transition cursor-pointer"
                                        >
                                            <KeyIcon class="w-3.5 h-3.5 text-amber-600" />
                                        </button>

                                        <!-- Delete Account -->
                                        <button 
                                            @click="confirmDeleteAccount(acc)"
                                            type="button"
                                            class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded transition cursor-pointer"
                                            title="Delete FTP Account"
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
                    <span>FileZilla, WinSCP, Cyberduck, and VS Code SFTP supported.</span>
                    <span class="font-mono">Linux vsftpd • TLS 1.3 Encryption</span>
                </div>
            </div>

        </div>

        <!-- ========================================================= -->
        <!-- MODALS                                                    -->
        <!-- ========================================================= -->

        <Teleport to="body">
            
            <!-- 1. Create FTP Account Modal -->
            <div v-if="showCreateModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4 z-50">
                <div class="bg-white rounded-lg max-w-md w-full p-5 shadow-2xl space-y-4 border border-slate-200">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-2">
                            <ServerIcon class="w-5 h-5 text-blue-600" />
                            <h3 class="font-bold text-slate-900 text-sm">Create New FTP Account</h3>
                        </div>
                        <button @click="showCreateModal = false" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                            <XMarkIcon class="w-4 h-4" />
                        </button>
                    </div>

                    <form @submit.prevent="submitCreate" class="space-y-4 text-xs">
                        
                        <!-- Subscription Selector -->
                        <div class="space-y-1.5">
                            <label class="block font-bold text-slate-700">Target Subscription</label>
                            <select 
                                v-model="createForm.subscription_id" 
                                required 
                                class="w-full bg-white border border-slate-300 rounded-[3px] px-2.5 py-2 text-xs font-bold text-slate-900 focus:ring-1 focus:ring-blue-600 focus:border-blue-600"
                            >
                                <option v-for="sub in subscriptions" :key="sub.id" :value="sub.id">
                                    {{ sub.domain }} • {{ sub.plan_name }}
                                </option>
                            </select>
                            <span v-if="createForm.errors.subscription_id" class="text-[11px] text-rose-600 font-bold block mt-1">
                                {{ createForm.errors.subscription_id }}
                            </span>
                        </div>

                        <!-- Username -->
                        <div class="space-y-1.5">
                            <label class="block font-bold text-slate-700">FTP Username</label>
                            <input 
                                v-model="createForm.username" 
                                type="text" 
                                required 
                                placeholder="e.g. dev_deploy" 
                                class="w-full bg-white border border-slate-300 rounded-[3px] px-2.5 py-2 text-xs font-mono font-bold text-slate-900 focus:ring-1 focus:ring-blue-600 focus:border-blue-600" 
                            />
                            <p class="text-[10px] text-slate-500">Only letters, numbers, dashes and underscores allowed.</p>
                            <span v-if="createForm.errors.username" class="text-[11px] text-rose-600 font-bold block mt-1">
                                {{ createForm.errors.username }}
                            </span>
                        </div>

                        <!-- Password with Generator -->
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between">
                                <label class="font-bold text-slate-700">Password</label>
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
                                placeholder="Min 8 characters" 
                                class="w-full bg-white border border-slate-300 rounded-[3px] px-2.5 py-2 text-xs font-mono font-bold text-slate-900 focus:ring-1 focus:ring-blue-600 focus:border-blue-600" 
                            />
                            <span v-if="createForm.errors.password" class="text-[11px] text-rose-600 font-bold block mt-1">
                                {{ createForm.errors.password }}
                            </span>
                        </div>

                        <!-- Restricted Directory Path -->
                        <div class="space-y-1.5">
                            <label class="block font-bold text-slate-700">Locked Root Directory</label>
                            <input 
                                v-model="createForm.path" 
                                type="text" 
                                placeholder="public_html or leave empty for root" 
                                class="w-full bg-white border border-slate-300 rounded-[3px] px-2.5 py-2 text-xs font-mono text-slate-900 focus:ring-1 focus:ring-blue-600 focus:border-blue-600" 
                            />
                            <div class="flex gap-1.5 text-[10.5px]">
                                <button 
                                    type="button" 
                                    @click="createForm.path = 'public_html'" 
                                    class="px-2 py-0.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-[3px] font-bold cursor-pointer"
                                >
                                    public_html
                                </button>
                                <button 
                                    type="button" 
                                    @click="createForm.path = ''" 
                                    class="px-2 py-0.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-[3px] font-bold cursor-pointer"
                                >
                                    Account Root
                                </button>
                            </div>
                            <span v-if="createForm.errors.path" class="text-[11px] text-rose-600 font-bold block mt-1">
                                {{ createForm.errors.path }}
                            </span>
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
                                <span>{{ createForm.processing ? 'Provisioning...' : 'Create Account' }}</span>
                            </button>
                        </div>

                    </form>
                </div>
            </div>

            <!-- 2. Reset Password Modal -->
            <div v-if="showPasswordModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4 z-50">
                <div class="bg-white rounded-lg max-w-sm w-full p-5 shadow-2xl space-y-4 border border-slate-200">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                        <h3 class="font-bold text-slate-900 text-sm">Reset Password for {{ selectedAccount?.username }}</h3>
                        <button @click="showPasswordModal = false" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                            <XMarkIcon class="w-4 h-4" />
                        </button>
                    </div>

                    <form @submit.prevent="submitPasswordChange" class="space-y-4 text-xs">
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between">
                                <label class="font-bold text-slate-700">New Password</label>
                                <button 
                                    type="button" 
                                    @click="fillRandomPasswordForReset" 
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
                                placeholder="Min 8 characters" 
                                class="w-full bg-white border border-slate-300 rounded-[3px] px-2.5 py-2 text-xs font-mono font-bold text-slate-900 focus:ring-1 focus:ring-blue-600 focus:border-blue-600" 
                            />
                            <span v-if="passwordForm.errors.password" class="text-[11px] text-rose-600 font-bold block mt-1">
                                {{ passwordForm.errors.password }}
                            </span>
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

            <!-- 3. Quick Connect Details Modal -->
            <div v-if="showQuickConnectModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4 z-50">
                <div class="bg-white rounded-lg max-w-md w-full p-5 shadow-2xl space-y-4 border border-slate-200">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-2">
                            <CommandLineIcon class="w-5 h-5 text-blue-600" />
                            <h3 class="font-bold text-slate-900 text-sm">FTP Client Connection Guide</h3>
                        </div>
                        <button @click="showQuickConnectModal = false" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                            <XMarkIcon class="w-4 h-4" />
                        </button>
                    </div>

                    <div class="space-y-2 text-xs font-mono">
                        <div class="p-2.5 bg-slate-50 rounded border border-slate-200/70 flex items-center justify-between">
                            <span class="text-slate-500">Host:</span>
                            <span class="font-bold text-slate-900">{{ serverInfo.public_ip }}</span>
                        </div>

                        <div class="p-2.5 bg-slate-50 rounded border border-slate-200/70 flex items-center justify-between">
                            <span class="text-slate-500">Username:</span>
                            <span class="font-bold text-blue-700">{{ quickConnectAccount?.username }}</span>
                        </div>

                        <div class="p-2.5 bg-slate-50 rounded border border-slate-200/70 flex items-center justify-between">
                            <span class="text-slate-500">Port:</span>
                            <span class="font-bold text-slate-900">21</span>
                        </div>

                        <div class="p-2.5 bg-slate-50 rounded border border-slate-200/70 flex items-center justify-between">
                            <span class="text-slate-500">Protocol:</span>
                            <span class="font-bold text-slate-900">FTP / FTPS (Explicit TLS)</span>
                        </div>

                        <div class="p-2.5 bg-slate-50 rounded border border-slate-200/70 flex items-center justify-between">
                            <span class="text-slate-500">Locked Path:</span>
                            <span class="font-bold text-slate-900">{{ quickConnectAccount?.display_path }}</span>
                        </div>
                    </div>

                    <div class="flex justify-end pt-2 border-t border-slate-100">
                        <button 
                            @click="showQuickConnectModal = false"
                            class="px-4 py-1.5 bg-slate-900 text-white rounded-[3px] text-xs font-bold cursor-pointer"
                        >
                            Done
                        </button>
                    </div>
                </div>
            </div>

            <!-- 4. Delete Confirmation Modal -->
            <div v-if="showDeleteModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4 z-50">
                <div class="bg-white rounded-lg max-w-md w-full p-5 shadow-2xl border border-slate-200 space-y-4">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-lg bg-rose-50 border border-rose-100 text-rose-600 flex items-center justify-center shrink-0">
                            <TrashIcon class="w-5 h-5 stroke-[1.75]" />
                        </div>
                        <div class="flex-1">
                            <h3 class="text-sm font-black text-slate-900">Delete FTP Account?</h3>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Are you sure you want to delete FTP user <strong class="text-rose-600 font-mono">{{ deleteTargetAccount?.username }}</strong>?
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
                            @click="executeDeleteAccount" 
                            type="button"
                            :disabled="deletingAccount"
                            class="px-4 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-[3px] text-xs font-bold shadow-2xs transition cursor-pointer flex items-center gap-1.5 disabled:opacity-50"
                        >
                            <TrashIcon class="w-3.5 h-3.5" />
                            <span>{{ deletingAccount ? 'Deleting...' : 'Delete Permanently' }}</span>
                        </button>
                    </div>
                </div>
            </div>

        </Teleport>

    </AuthenticatedLayout>
</template>
