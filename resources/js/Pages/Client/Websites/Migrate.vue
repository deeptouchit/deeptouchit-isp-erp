<script setup>
import { ref, computed } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import { 
    ArrowPathRoundedSquareIcon,
    ServerStackIcon, 
    ShieldCheckIcon,
    BoltIcon,
    CloudArrowUpIcon,
    ArrowTopRightOnSquareIcon,
    CheckCircleIcon,
    DocumentDuplicateIcon,
    CommandLineIcon,
    TrashIcon,
    XMarkIcon,
    PlusIcon,
    ArrowPathIcon,
    ExclamationTriangleIcon,
    GlobeAltIcon,
    FolderArrowDownIcon,
    KeyIcon,
    InformationCircleIcon
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
    migrations: {
        type: Array,
        default: () => []
    },
    server_ip: {
        type: String,
        default: '103.59.177.138'
    },
    nameservers: {
        type: Array,
        default: () => ['ns1.deeptouchit.com', 'ns2.deeptouchit.com']
    }
})

// Migration Mode Tabs
const activeTab = ref('cpanel_server')

const migrationTabs = [
    { id: 'cpanel_server', name: 'cPanel Server-to-Server', badge: 'Recommended', icon: ServerStackIcon },
    { id: 'cpanel_archive', name: 'Upload Backup Archive', badge: '.tar.gz / .zip', icon: CloudArrowUpIcon },
    { id: 'wordpress_direct', name: 'WordPress Direct Importer', badge: '1-Click', icon: GlobeAltIcon },
    { id: 'ftp_transfer', name: 'Direct FTP / SFTP', badge: 'Manual Sync', icon: FolderArrowDownIcon },
]

// Main Migration Form
const form = useForm({
    migration_type: 'cpanel_server',
    target_domain: props.websites[0]?.domain || props.subscription?.domain || '',
    source_host: '',
    source_port: 2083,
    source_username: '',
    source_password: '',
    source_url: '',
    scope_files: true,
    scope_databases: true,
    scope_emails: true,
    scope_ssl: true,
})

const handleTabChange = (tabId) => {
    activeTab.value = tabId
    form.migration_type = tabId
    if (tabId === 'cpanel_server') {
        form.source_port = 2083
    } else if (tabId === 'ftp_transfer') {
        form.source_port = 21
    }
}

const isSubmitting = ref(false)
const submitMigration = () => {
    isSubmitting.value = true
    form.post(route('website.migrate.start'), {
        preserveScroll: true,
        onFinish: () => {
            isSubmitting.value = false
        }
    })
}

const deleteMigration = (id) => {
    if (confirm('Clear this migration log entry?')) {
        router.post(route('website.migrate.cancel', id), {}, { preserveScroll: true })
    }
}

// Logs Modal
const showLogsModal = ref(false)
const selectedMigration = ref(null)

const openLogsModal = (mig) => {
    selectedMigration.value = mig
    showLogsModal.value = true
}

// Copy to Clipboard
const copiedText = ref(null)
const copyToClipboard = (text, id) => {
    navigator.clipboard.writeText(text)
    copiedText.value = id
    setTimeout(() => {
        copiedText.value = null
    }, 2000)
}
</script>

<template>
    <Head title="Automated Website & cPanel Migration - DeepTouch Host" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto space-y-4">
            
            <!-- 1. Page Header with Breadcrumbs & Actions -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Website Tools', href: '#' },
                    { label: 'Website Migration' }
                ]"
                :show-refresh="true"
                @refreshed="router.reload({ preserveScroll: true })"
            >
                <template #actions>
                    <Link
                        :href="route('website.installer')"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition"
                    >
                        <ArrowPathRoundedSquareIcon class="w-3.5 h-3.5 text-blue-600" />
                        <span>App Auto Installer</span>
                    </Link>
                </template>
            </PageHeader>

            <!-- 2. Top 3 Assurance & Guarantee Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                
                <!-- Card 1: Zero-Downtime Guarantee -->
                <div class="bg-white rounded-lg border border-slate-200 p-4 shadow-2xs space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <ShieldCheckIcon class="w-4 h-4 text-emerald-600" />
                            <span>Zero-Downtime Migration</span>
                        </span>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200 uppercase font-mono">
                            ACTIVE
                        </span>
                    </div>

                    <div>
                        <div class="flex items-baseline justify-between">
                            <span class="text-2xl font-black text-emerald-600 font-mono">100% Uptime</span>
                            <span class="text-xs text-slate-500 font-mono">Seamless Sync</span>
                        </div>
                        <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden mt-2">
                            <div class="bg-emerald-500 h-full rounded-full w-full"></div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1 border-t border-slate-100">
                        <span>DNS Cutover:</span>
                        <strong class="text-slate-700">Test via Local Preview First</strong>
                    </div>
                </div>

                <!-- Card 2: High-Speed Encrypted Tunnel -->
                <div class="bg-white rounded-lg border border-slate-200 p-4 shadow-2xs space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <BoltIcon class="w-4 h-4 text-blue-600" />
                            <span>High-Speed Tunnel</span>
                        </span>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-200 uppercase font-mono">
                            MULTI-THREAD
                        </span>
                    </div>

                    <div>
                        <div class="flex items-baseline justify-between">
                            <span class="text-2xl font-black text-slate-900 font-mono">Up to 100 MB/s</span>
                            <span class="text-xs text-slate-500 font-mono">SSH / FastCGI</span>
                        </div>
                        <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden mt-2">
                            <div class="bg-blue-600 h-full rounded-full w-full"></div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1 border-t border-slate-100">
                        <span>Database Transfer:</span>
                        <strong class="text-blue-700 font-bold">Auto MySQL Schema Matching</strong>
                    </div>
                </div>

                <!-- Card 3: Free AutoSSL & Email Restore -->
                <div class="bg-white rounded-lg border border-slate-200 p-4 shadow-2xs space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <KeyIcon class="w-4 h-4 text-purple-600" />
                            <span>Full Stack Restoration</span>
                        </span>
                        <span class="text-[10px] font-bold text-slate-500 uppercase font-mono">
                            AUTO-PROVISION
                        </span>
                    </div>

                    <div class="space-y-1 text-xs font-mono">
                        <div class="flex items-center justify-between p-1.5 bg-slate-50 rounded border border-slate-200/70">
                            <span class="text-slate-500 text-[11px]">Mailboxes:</span>
                            <span class="font-bold text-slate-800">IMAP / Forwarders Restored</span>
                        </div>

                        <div class="flex items-center justify-between p-1.5 bg-slate-50 rounded border border-slate-200/70">
                            <span class="text-slate-500 text-[11px]">SSL Status:</span>
                            <span class="font-bold text-purple-700">Auto-Renewing Let's Encrypt</span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- 3. Migration Wizard & Configuration Form -->
            <div class="bg-white rounded-lg border border-slate-200 shadow-2xs overflow-hidden">
                
                <!-- Wizard Tabs Header -->
                <div class="border-b border-slate-200 bg-slate-50/70 px-4 py-2.5 flex items-center justify-between overflow-x-auto gap-2">
                    <div class="flex items-center gap-1.5">
                        <button
                            v-for="tab in migrationTabs"
                            :key="tab.id"
                            @click="handleTabChange(tab.id)"
                            class="px-3 py-1.5 rounded-[3px] text-xs font-bold flex items-center gap-1.5 transition cursor-pointer whitespace-nowrap"
                            :class="activeTab === tab.id ? 'bg-blue-600 text-white shadow-2xs' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200'"
                        >
                            <component :is="tab.icon" class="w-3.5 h-3.5" />
                            <span>{{ tab.name }}</span>
                            <span 
                                class="text-[9.5px] px-1 py-0.2 rounded font-mono uppercase"
                                :class="activeTab === tab.id ? 'bg-blue-700 text-blue-100' : 'bg-slate-100 text-slate-600'"
                            >
                                {{ tab.badge }}
                            </span>
                        </button>
                    </div>
                </div>

                <!-- Form Content Area -->
                <form @submit.prevent="submitMigration" class="p-5 space-y-4">
                    
                    <!-- Destination Domain Selection -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700">Destination Domain (This Server) <span class="text-rose-500">*</span></label>
                            <select 
                                v-model="form.target_domain"
                                required
                                class="w-full text-xs font-bold rounded-[3px] border-slate-300 bg-white text-slate-900 py-2 px-2.5 focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option v-for="site in websites" :key="site.id" :value="site.domain">
                                    https://{{ site.domain }}
                                </option>
                            </select>
                            <span class="text-[11px] text-slate-500">The website where incoming files & databases will be populated.</span>
                        </div>

                        <!-- Mode 1 & 4: Remote Server Host -->
                        <div v-if="activeTab === 'cpanel_server' || activeTab === 'ftp_transfer'" class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700">Source Server IP / Hostname <span class="text-rose-500">*</span></label>
                            <div class="grid grid-cols-3 gap-2">
                                <input 
                                    type="text" 
                                    v-model="form.source_host"
                                    required
                                    placeholder="e.g. cpanel.oldhost.com or IP"
                                    class="col-span-2 text-xs font-mono rounded-[3px] border-slate-300 bg-white text-slate-900 py-2 px-2.5 focus:ring-blue-500 focus:border-blue-500"
                                />
                                <input 
                                    type="number" 
                                    v-model="form.source_port"
                                    required
                                    placeholder="Port"
                                    class="text-xs font-mono rounded-[3px] border-slate-300 bg-white text-slate-900 py-2 px-2.5 focus:ring-blue-500 focus:border-blue-500"
                                />
                            </div>
                            <span class="text-[11px] text-slate-500">cPanel standard port is 2083 (SSL) or 22 (SSH). FTP port is 21.</span>
                        </div>

                        <!-- Mode 3: WordPress URL -->
                        <div v-else-if="activeTab === 'wordpress_direct'" class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700">Existing WordPress Website URL <span class="text-rose-500">*</span></label>
                            <input 
                                type="url" 
                                v-model="form.source_url"
                                required
                                placeholder="https://example.com"
                                class="w-full text-xs font-mono rounded-[3px] border-slate-300 bg-white text-slate-900 py-2 px-2.5 focus:ring-blue-500 focus:border-blue-500"
                            />
                            <span class="text-[11px] text-slate-500">Directly imports posts, media library, plugins, themes, and database.</span>
                        </div>
                    </div>

                    <!-- Remote Credentials (For cPanel, FTP, WordPress) -->
                    <div v-if="activeTab !== 'cpanel_archive'" class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2 border-t border-slate-100">
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700">Source Account Username <span class="text-rose-500">*</span></label>
                            <input 
                                type="text" 
                                v-model="form.source_username"
                                required
                                placeholder="e.g. cpanel_user or wp_admin"
                                class="w-full text-xs font-mono rounded-[3px] border-slate-300 bg-white text-slate-900 py-2 px-2.5 focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>

                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700">Source Account Password / Token <span class="text-rose-500">*</span></label>
                            <input 
                                type="password" 
                                v-model="form.source_password"
                                required
                                placeholder="Enter remote login password"
                                class="w-full text-xs font-mono rounded-[3px] border-slate-300 bg-white text-slate-900 py-2 px-2.5 focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>
                    </div>

                    <!-- Mode 2: Backup Archive Upload Area -->
                    <div v-if="activeTab === 'cpanel_archive'" class="pt-2 border-t border-slate-100 space-y-2">
                        <label class="block text-xs font-bold text-slate-700">Upload cPanel Backup Archive (.tar.gz / .zip)</label>
                        <div class="border-2 border-dashed border-slate-300 hover:border-blue-500 rounded-lg p-6 text-center bg-slate-50/50 transition cursor-pointer">
                            <CloudArrowUpIcon class="w-8 h-8 text-slate-400 mx-auto mb-2" />
                            <p class="text-xs font-bold text-slate-700">Drag & Drop backup archive here, or <span class="text-blue-600 underline">browse files</span></p>
                            <p class="text-[11px] text-slate-500 mt-1">Supports full cPanel backup archives (e.g. <code>backup-9.3.2026_user.tar.gz</code>)</p>
                        </div>
                    </div>

                    <!-- Migration Scope Checkboxes -->
                    <div class="pt-2 border-t border-slate-100 space-y-2">
                        <span class="block text-xs font-bold text-slate-700">Components to Migrate</span>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <label class="flex items-center gap-2 p-2.5 bg-slate-50 border border-slate-200 rounded cursor-pointer">
                                <input type="checkbox" v-model="form.scope_files" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
                                <span class="text-xs font-bold text-slate-700">Web Files (public_html)</span>
                            </label>

                            <label class="flex items-center gap-2 p-2.5 bg-slate-50 border border-slate-200 rounded cursor-pointer">
                                <input type="checkbox" v-model="form.scope_databases" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
                                <span class="text-xs font-bold text-slate-700">MySQL Databases</span>
                            </label>

                            <label class="flex items-center gap-2 p-2.5 bg-slate-50 border border-slate-200 rounded cursor-pointer">
                                <input type="checkbox" v-model="form.scope_emails" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
                                <span class="text-xs font-bold text-slate-700">Email Accounts</span>
                            </label>

                            <label class="flex items-center gap-2 p-2.5 bg-slate-50 border border-slate-200 rounded cursor-pointer">
                                <input type="checkbox" v-model="form.scope_ssl" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
                                <span class="text-xs font-bold text-slate-700">SSL & DNS Records</span>
                            </label>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex items-center justify-between pt-3 border-t border-slate-100">
                        <div class="flex items-center gap-1.5 text-[11px] text-slate-500">
                            <InformationCircleIcon class="w-4 h-4 text-blue-600" />
                            <span>Your current website stays 100% online during the transfer.</span>
                        </div>

                        <button 
                            type="submit" 
                            :disabled="form.processing"
                            class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold shadow-2xs transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
                        >
                            <ArrowPathRoundedSquareIcon class="w-4 h-4" :class="{ 'animate-spin': form.processing }" />
                            <span>{{ form.processing ? 'Transferring Website...' : 'Start Automated Migration' }}</span>
                        </button>
                    </div>

                </form>

            </div>

            <!-- 4. Active Migrations & History Table -->
            <div class="bg-white rounded-lg border border-slate-200 shadow-2xs overflow-hidden">
                <div class="px-4 py-3 bg-slate-50/70 border-b border-slate-200 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <ServerStackIcon class="w-4 h-4 text-blue-600" />
                        <span class="text-xs font-bold text-slate-800">Migration Jobs & Transfer Logs</span>
                    </div>
                    <span class="text-[11px] text-slate-500 font-mono">{{ migrations.length }} Total Records</span>
                </div>

                <div v-if="migrations.length === 0" class="p-8 text-center text-slate-500 text-xs">
                    No migration jobs have been performed yet. Start a new migration using the wizard above.
                </div>

                <div v-else class="divide-y divide-slate-100">
                    <div 
                        v-for="mig in migrations" 
                        :key="mig.id"
                        class="p-4 flex flex-col md:flex-row md:items-center justify-between gap-4 hover:bg-slate-50/50 transition"
                    >
                        <div class="flex items-center gap-3.5">
                            <div class="w-9 h-9 rounded-lg bg-emerald-50 border border-emerald-200 flex items-center justify-center text-emerald-600 font-bold shrink-0">
                                <CheckCircleIcon class="w-5 h-5" />
                            </div>

                            <div>
                                <div class="flex items-center gap-2">
                                    <h4 class="font-bold text-slate-900 text-sm font-mono">{{ mig.id }}</h4>
                                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200 uppercase font-mono">
                                        {{ mig.status }}
                                    </span>
                                    <span class="text-[11px] text-slate-500 font-mono">({{ mig.data_transferred }})</span>
                                </div>
                                <div class="flex items-center gap-2 text-xs text-slate-500 font-mono mt-0.5">
                                    <span>From: <strong>{{ mig.source_name }}</strong></span>
                                    <span>→</span>
                                    <span class="text-blue-600 font-bold">https://{{ mig.target_domain }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Items tags & Actions -->
                        <div class="flex items-center gap-2">
                            <div class="hidden sm:flex items-center gap-1 text-[10.5px] font-mono text-slate-500">
                                <span v-for="item in mig.items" :key="item" class="px-1.5 py-0.5 rounded bg-slate-100 border border-slate-200">
                                    {{ item }}
                                </span>
                            </div>

                            <button 
                                @click="openLogsModal(mig)"
                                class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-[3px] text-xs font-bold shadow-2xs flex items-center gap-1 transition cursor-pointer"
                            >
                                <CommandLineIcon class="w-3.5 h-3.5 text-blue-600" />
                                <span>Logs</span>
                            </button>

                            <button 
                                @click="deleteMigration(mig.id)"
                                class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded transition cursor-pointer"
                                title="Clear record"
                            >
                                <TrashIcon class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 5. DNS Cutover & Zero-Downtime Preview Box -->
            <div class="bg-slate-900 text-slate-100 rounded-lg p-5 shadow-2xs space-y-3.5 border border-slate-800">
                <div class="flex items-center justify-between border-b border-slate-800 pb-2.5">
                    <span class="text-xs font-bold text-slate-200 flex items-center gap-1.5">
                        <GlobeAltIcon class="w-4 h-4 text-blue-400" />
                        <span>Step 2: DNS Switch & Zero-Risk Live Preview</span>
                    </span>
                    <span class="text-[10.5px] font-mono text-emerald-400 font-bold">READY TO POINT</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs font-mono">
                    
                    <!-- Nameservers Box -->
                    <div class="p-3 bg-slate-800/80 rounded border border-slate-700 space-y-2">
                        <span class="text-slate-400 text-[11px] uppercase tracking-wider block font-bold">Official DeepTouch Nameservers</span>
                        <div class="space-y-1">
                            <div v-for="ns in nameservers" :key="ns" class="flex items-center justify-between p-1.5 bg-slate-900 rounded border border-slate-700/60">
                                <span class="text-blue-300 font-bold">{{ ns }}</span>
                                <button 
                                    @click="copyToClipboard(ns, ns)"
                                    class="text-[11px] text-slate-400 hover:text-white transition cursor-pointer flex items-center gap-1"
                                >
                                    <DocumentDuplicateIcon class="w-3.5 h-3.5" />
                                    <span>{{ copiedText === ns ? 'Copied!' : 'Copy' }}</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Host File Preview -->
                    <div class="p-3 bg-slate-800/80 rounded border border-slate-700 space-y-2">
                        <span class="text-slate-400 text-[11px] uppercase tracking-wider block font-bold">Local hosts File Preview String</span>
                        <p class="text-[10.5px] text-slate-400 font-sans leading-relaxed">
                            Test your migrated site locally before updating DNS by adding this line to your computer's <code>hosts</code> file:
                        </p>
                        <div class="flex items-center justify-between p-1.5 bg-slate-900 rounded border border-slate-700/60 text-[11px]">
                            <span class="text-emerald-400 font-bold">{{ server_ip }} {{ subscription?.domain || 'yourdomain.com' }}</span>
                            <button 
                                @click="copyToClipboard(`${server_ip} ${subscription?.domain || 'yourdomain.com'}`, 'hosts')"
                                class="text-[11px] text-slate-400 hover:text-white transition cursor-pointer flex items-center gap-1"
                            >
                                <DocumentDuplicateIcon class="w-3.5 h-3.5" />
                                <span>{{ copiedText === 'hosts' ? 'Copied!' : 'Copy' }}</span>
                            </button>
                        </div>
                    </div>

                </div>
            </div>

        </div>

        <!-- Terminal Logs Modal -->
        <div v-if="showLogsModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4 z-50">
            <div class="bg-slate-950 text-slate-100 rounded-lg max-w-xl w-full p-5 shadow-2xl border border-slate-800 space-y-3 font-mono">
                <div class="flex items-center justify-between border-b border-slate-800 pb-2.5">
                    <div class="flex items-center gap-2 text-xs">
                        <CommandLineIcon class="w-4 h-4 text-emerald-400" />
                        <span class="font-bold text-slate-200">Migration Terminal [{{ selectedMigration?.id }}]</span>
                    </div>
                    <button @click="showLogsModal = false" class="text-slate-400 hover:text-white cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <!-- Log Lines -->
                <div class="bg-slate-900/90 rounded p-3 text-xs space-y-1.5 max-h-72 overflow-y-auto font-mono text-slate-300 border border-slate-800">
                    <div v-for="(log, idx) in selectedMigration?.logs" :key="idx" class="flex items-start gap-2">
                        <span class="text-emerald-500 font-bold select-none">✓</span>
                        <span>{{ log }}</span>
                    </div>
                </div>

                <div class="flex justify-end pt-2 border-t border-slate-800">
                    <button 
                        @click="showLogsModal = false" 
                        class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-white rounded text-xs font-bold cursor-pointer transition"
                    >
                        Close Terminal
                    </button>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>
