<script setup>
import { ref, computed, watch } from 'vue'
import { Head, useForm, Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import { 
    CircleStackIcon, 
    PlusIcon, 
    KeyIcon, 
    TrashIcon, 
    ArrowTopRightOnSquareIcon,
    ArrowDownTrayIcon, 
    ArrowUpTrayIcon,
    ClipboardDocumentIcon,
    CheckIcon,
    ServerIcon,
    ShieldCheckIcon,
    ArrowPathIcon,
    XMarkIcon,
    ExclamationTriangleIcon,
    InformationCircleIcon,
    WrenchScrewdriverIcon,
    SparklesIcon,
    MagnifyingGlassIcon,
    CommandLineIcon,
    DocumentTextIcon,
    CodeBracketIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    databases: {
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
            total_allowed: 2,
            total_used: 0,
            remaining: 2,
            can_add: true,
            usage_percentage: 0
        })
    },
    serverInfo: {
        type: Object,
        default: () => ({
            host: 'localhost',
            ip: '127.0.0.1',
            port: 3306,
            driver: 'MySQL 8.0 / MariaDB',
            socket: '/var/run/mysqld/mysqld.sock',
            charset: 'utf8mb4',
            collation: 'utf8mb4_unicode_ci'
        })
    }
})

// Active Tab
const activeTab = ref('databases') // 'databases' | 'import' | 'snippets'

// Search & Filter
const searchQuery = ref('')
const filteredDatabases = computed(() => {
    if (!searchQuery.value.trim()) return props.databases
    const q = searchQuery.value.toLowerCase().trim()
    return props.databases.filter(d => 
        d.name.toLowerCase().includes(q) || 
        d.db_user.toLowerCase().includes(q) ||
        d.subscription?.domain?.toLowerCase().includes(q)
    )
})

// Modals State
const showCreateModal = ref(false)
const showPasswordModal = ref(false)
const showImportModal = ref(false)
const showDeleteModal = ref(false)
const selectedDb = ref(null)
const deleteTargetDb = ref(null)
const deletingDb = ref(false)

// Copy feedback
const copiedText = ref('')
const copyToClipboard = (text, key) => {
    navigator.clipboard.writeText(text)
    copiedText.value = key
    setTimeout(() => {
        copiedText.value = ''
    }, 2000)
}

// Create Database Form
const createForm = useForm({
    subscription_id: props.subscriptions[0]?.id || '',
    name: '',
    db_user: '',
    db_password: '',
})

// Current selected subscription prefix
const selectedSubscription = computed(() => {
    return props.subscriptions.find(s => s.id === createForm.subscription_id) || props.subscriptions[0] || null
})

const activePrefix = computed(() => {
    return selectedSubscription.value ? selectedSubscription.value.prefix : 'db'
})

// When database name is typed, auto-suggest user if empty
watch(() => createForm.name, (newVal) => {
    if (!createForm.db_user || createForm.db_user === newVal.slice(0, -1)) {
        createForm.db_user = newVal
    }
})

// Password Generator
const generateRandomPassword = () => {
    const chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789!@#$%^&*'
    let pass = ''
    for (let i = 0; i < 16; i++) {
        pass += chars.charAt(Math.floor(Math.random() * chars.length))
    }
    return pass
}

const fillRandomPassword = () => {
    createForm.db_password = generateRandomPassword()
}

const submitCreate = () => {
    createForm.post(route('databases.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false
            createForm.reset()
        }
    })
}

// Password Reset Form
const passwordForm = useForm({
    password: ''
})

const openPasswordModal = (db) => {
    selectedDb.value = db
    passwordForm.password = generateRandomPassword()
    showPasswordModal.value = true
}

const fillRandomPasswordForReset = () => {
    passwordForm.password = generateRandomPassword()
}

const submitPasswordChange = () => {
    if (!selectedDb.value) return
    passwordForm.post(route('databases.change-password', selectedDb.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showPasswordModal.value = false
            passwordForm.reset()
            selectedDb.value = null
        }
    })
}

// SQL File Import Form
const importForm = useForm({
    database_id: props.databases[0]?.id || '',
    sql_file: null
})

const openImportModal = (db = null) => {
    if (db) {
        importForm.database_id = db.id
    } else if (props.databases.length > 0) {
        importForm.database_id = props.databases[0].id
    }
    showImportModal.value = true
}

const onFileSelected = (e) => {
    importForm.sql_file = e.target.files[0] || null
}

const submitImport = () => {
    if (!importForm.database_id || !importForm.sql_file) return
    importForm.post(route('databases.import', importForm.database_id), {
        preserveScroll: true,
        onSuccess: () => {
            showImportModal.value = false
            importForm.reset()
        }
    })
}

// Check & Repair Tables
const repairDatabase = (db) => {
    router.post(route('databases.repair', db.id), {}, {
        preserveScroll: true
    })
}

// Optimize Tables
const optimizeDatabase = (db) => {
    router.post(route('databases.optimize', db.id), {}, {
        preserveScroll: true
    })
}

// Delete Confirmation
const confirmDeleteDatabase = (db) => {
    deleteTargetDb.value = db
    showDeleteModal.value = true
}

const executeDeleteDatabase = () => {
    if (!deleteTargetDb.value) return
    deletingDb.value = true
    router.delete(route('databases.destroy', deleteTargetDb.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false
            deleteTargetDb.value = null
        },
        onFinish: () => {
            deletingDb.value = false
        }
    })
}

// Sample snippets
const getLaravelEnvSnippet = (db) => {
    return `DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=${db ? db.name : 'your_database'}
DB_USERNAME=${db ? db.db_user : 'your_user'}
DB_PASSWORD=your_password`
}

const getWordpressSnippet = (db) => {
    return `define( 'DB_NAME', '${db ? db.name : 'your_database'}' );
define( 'DB_USER', '${db ? db.db_user : 'your_user'}' );
define( 'DB_PASSWORD', 'your_password' );
define( 'DB_HOST', 'localhost' );
define( 'DB_CHARSET', 'utf8mb4' );`
}
</script>

<template>
    <Head title="MySQL Databases & SQL Studio - DeepTouch Host" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto space-y-4">
            
            <!-- 1. Page Header with Breadcrumbs & Actions -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Databases', href: '#' },
                    { label: 'MySQL Databases & Users' }
                ]"
                :show-refresh="true"
                @refreshed="router.reload({ preserveScroll: true })"
            >
                <template #actions>
                    <a 
                        v-if="databases.length > 0"
                        :href="route('databases.sso')" 
                        target="_blank"
                        class="px-3 py-1.5 bg-purple-50 hover:bg-purple-100 text-purple-700 border border-purple-200 rounded-[3px] text-xs font-bold flex items-center gap-1.5 transition cursor-pointer shadow-2xs"
                    >
                        <CircleStackIcon class="w-3.5 h-3.5 text-purple-600" />
                        <span>Open phpMyAdmin ↗</span>
                    </a>

                    <button 
                        @click="openImportModal()"
                        v-if="databases.length > 0"
                        class="px-3 py-1.5 bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <ArrowUpTrayIcon class="w-3.5 h-3.5 text-blue-600" />
                        <span>Import .SQL</span>
                    </button>

                    <button 
                        @click="showCreateModal = true; if (!createForm.db_password) fillRandomPassword()"
                        :disabled="!quota.can_add"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer disabled:opacity-50"
                    >
                        <PlusIcon class="w-3.5 h-3.5 stroke-[2.5]" />
                        <span>Create Database</span>
                    </button>
                </template>
            </PageHeader>

            <!-- 2. Top Metrics & Quick Info Cards (3-Column Grid) -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                
                <!-- Card 1: Database Quota Tracker -->
                <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-2xs space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <CircleStackIcon class="w-4 h-4 text-blue-600" />
                            <span>Database Quota</span>
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
                            <span class="text-xs text-slate-400 font-bold font-mono">/ {{ quota.total_allowed }} Allowed</span>
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
                        <span>Allocation:</span>
                        <strong class="text-slate-700 font-mono">{{ quota.usage_percentage }}% Used</strong>
                    </div>
                </div>

                <!-- Card 2: Quick Local Connection Parameters -->
                <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-2xs space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <CommandLineIcon class="w-4 h-4 text-emerald-600" />
                            <span>Local Connection</span>
                        </span>
                        <span class="text-[10px] font-mono font-bold text-slate-600 bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200">
                            {{ serverInfo.driver }}
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-2 text-xs font-mono">
                        <div class="bg-slate-50 p-1.5 rounded border border-slate-200/70">
                            <span class="text-[10px] text-slate-400 block font-bold uppercase">DB Host</span>
                            <div class="flex items-center justify-between font-bold text-slate-900 mt-0.5">
                                <span>localhost</span>
                                <button 
                                    @click="copyToClipboard('localhost', 'host')" 
                                    class="text-slate-400 hover:text-blue-600 cursor-pointer"
                                    title="Copy Host"
                                >
                                    <CheckIcon v-if="copiedText === 'host'" class="w-3.5 h-3.5 text-emerald-600" />
                                    <ClipboardDocumentIcon v-else class="w-3.5 h-3.5" />
                                </button>
                            </div>
                        </div>

                        <div class="bg-slate-50 p-1.5 rounded border border-slate-200/70">
                            <span class="text-[10px] text-slate-400 block font-bold uppercase">Port</span>
                            <div class="flex items-center justify-between font-bold text-slate-900 mt-0.5">
                                <span>3306</span>
                                <button 
                                    @click="copyToClipboard('3306', 'port')" 
                                    class="text-slate-400 hover:text-blue-600 cursor-pointer"
                                    title="Copy Port"
                                >
                                    <CheckIcon v-if="copiedText === 'port'" class="w-3.5 h-3.5 text-emerald-600" />
                                    <ClipboardDocumentIcon v-else class="w-3.5 h-3.5" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="text-[10.5px] text-slate-500 pt-1 border-t border-slate-100 flex items-center gap-1">
                        <InformationCircleIcon class="w-3.5 h-3.5 text-blue-600 shrink-0" />
                        <span>Use in wp-config.php, .env, or database configs</span>
                    </div>
                </div>

                <!-- Card 3: phpMyAdmin Studio Launch Card -->
                <div class="bg-gradient-to-br from-purple-50/70 to-indigo-50/50 border border-purple-100 rounded-lg p-4 shadow-2xs flex flex-col justify-between space-y-2.5">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-purple-900 flex items-center gap-1.5">
                                <SparklesIcon class="w-4 h-4 text-purple-600" />
                                <span>Web SQL Studio</span>
                            </span>
                            <span class="text-[10px] font-bold px-1.5 py-0.5 bg-purple-100 text-purple-800 rounded font-mono">
                                SSO AUTO-LOGIN
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-600 mt-1 line-clamp-2">
                            Visual SQL GUI to explore tables, execute queries, and import .sql dumps.
                        </p>
                    </div>

                    <div class="pt-2 border-t border-purple-100">
                        <a 
                            v-if="databases.length > 0"
                            :href="route('databases.sso')" 
                            target="_blank"
                            class="w-full px-3 py-1.5 bg-white hover:bg-purple-600 hover:text-white text-purple-700 border border-purple-200 rounded-[3px] text-xs font-bold shadow-2xs transition flex items-center justify-center gap-1.5 cursor-pointer"
                        >
                            <span>Open phpMyAdmin</span>
                            <ArrowTopRightOnSquareIcon class="w-3.5 h-3.5" />
                        </a>
                        <button 
                            v-else
                            @click="showCreateModal = true; fillRandomPassword()"
                            class="w-full px-3 py-1.5 bg-white hover:bg-purple-600 hover:text-white text-purple-700 border border-purple-200 rounded-[3px] text-xs font-bold shadow-2xs transition flex items-center justify-center gap-1.5 cursor-pointer"
                        >
                            <span>Create DB to Launch</span>
                            <PlusIcon class="w-3.5 h-3.5" />
                        </button>
                    </div>
                </div>

            </div>

            <!-- 3. Navigation Tabs -->
            <div class="border-b border-slate-200 flex items-center gap-4 text-xs font-bold">
                <button 
                    @click="activeTab = 'databases'"
                    class="pb-2.5 border-b-2 transition cursor-pointer flex items-center gap-1.5"
                    :class="activeTab === 'databases' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-800'"
                >
                    <CircleStackIcon class="w-4 h-4" />
                    <span>Databases ({{ databases.length }})</span>
                </button>

                <button 
                    @click="activeTab = 'snippets'"
                    class="pb-2.5 border-b-2 transition cursor-pointer flex items-center gap-1.5"
                    :class="activeTab === 'snippets' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-800'"
                >
                    <CodeBracketIcon class="w-4 h-4" />
                    <span>Connection Snippets (.env / wp-config)</span>
                </button>
            </div>

            <!-- 4. TAB 1: Databases List Table -->
            <div v-if="activeTab === 'databases'" class="bg-white border border-slate-200 rounded-lg shadow-2xs overflow-hidden">
                
                <!-- Table Header & Live Search Bar -->
                <div class="px-4 py-3 bg-slate-50/80 border-b border-slate-200 flex flex-wrap items-center justify-between gap-2.5">
                    <div class="flex items-center gap-2">
                        <CircleStackIcon class="w-4 h-4 text-purple-600" />
                        <span class="text-xs font-bold text-slate-900">Provisioned Databases</span>
                        <span class="text-[11px] text-slate-400 font-mono">({{ filteredDatabases.length }})</span>
                    </div>

                    <div class="relative">
                        <MagnifyingGlassIcon class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2" />
                        <input 
                            type="text" 
                            v-model="searchQuery" 
                            placeholder="Filter databases..." 
                            class="pl-8 pr-2.5 py-1.5 text-xs bg-white border border-slate-300 rounded-[3px] focus:ring-blue-500 focus:border-blue-500 w-36 sm:w-48 shadow-2xs"
                        />
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="filteredDatabases.length === 0" class="p-12 text-center text-slate-500 text-xs">
                    <CircleStackIcon class="w-12 h-12 text-slate-300 mx-auto mb-2" />
                    <h4 class="text-sm font-bold text-slate-900 mb-1">No Databases Found</h4>
                    <p class="text-[11px] text-slate-400 max-w-sm mx-auto mb-4">
                        Create a MySQL database to connect with WordPress, Laravel, or custom apps.
                    </p>
                    <button 
                        @click="showCreateModal = true; if (!createForm.db_password) fillRandomPassword()"
                        :disabled="!quota.can_add"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold shadow-2xs transition cursor-pointer disabled:opacity-50"
                    >
                        Create Database Now
                    </button>
                </div>

                <!-- Table Content -->
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-700">
                        <thead class="bg-slate-50/90 border-b border-slate-200 text-slate-500 uppercase font-bold tracking-wider text-[10.5px] select-none">
                            <tr>
                                <th class="py-2.5 px-4">Database Name</th>
                                <th class="py-2.5 px-4">Database User</th>
                                <th class="py-2.5 px-4 w-36">Size & Tables</th>
                                <th class="py-2.5 px-4 w-40">Associated Domain</th>
                                <th class="py-2.5 px-4 w-28">Host / Port</th>
                                <th class="py-2.5 px-4 w-24">Status</th>
                                <th class="py-2.5 px-4 w-52 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium">
                            <tr v-for="db in filteredDatabases" :key="db.id" class="hover:bg-slate-50/80 transition-colors">
                                
                                <!-- Database Name -->
                                <td class="py-3 px-4 font-bold text-slate-900 font-mono">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded bg-purple-50 border border-purple-200 text-purple-600 flex items-center justify-center shrink-0">
                                            <CircleStackIcon class="w-4 h-4" />
                                        </div>
                                        <div class="truncate">
                                            <span>{{ db.name }}</span>
                                            <span class="text-[10px] text-slate-400 font-sans block">Created: {{ db.created_at }}</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Database User -->
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-1.5 font-mono text-slate-800">
                                        <span>{{ db.db_user }}</span>
                                        <button 
                                            @click="copyToClipboard(db.db_user, 'user_' + db.id)" 
                                            class="text-slate-400 hover:text-blue-600 cursor-pointer"
                                            title="Copy Username"
                                        >
                                            <CheckIcon v-if="copiedText === 'user_' + db.id" class="w-3.5 h-3.5 text-emerald-600" />
                                            <ClipboardDocumentIcon v-else class="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                </td>

                                <!-- Size & Tables -->
                                <td class="py-3 px-4 font-mono text-[11px] text-slate-600">
                                    <span class="font-bold text-slate-800">{{ db.size_formatted }}</span>
                                    <span class="text-slate-300 mx-1">•</span>
                                    <span>{{ db.table_count }} tables</span>
                                </td>

                                <!-- Associated Domain -->
                                <td class="py-3 px-4">
                                    <div class="font-bold text-slate-900 font-mono text-[11px]">
                                        {{ db.subscription?.domain }}
                                    </div>
                                    <div class="text-[10px] text-slate-400">
                                        {{ db.subscription?.plan_name }}
                                    </div>
                                </td>

                                <!-- Host / Port -->
                                <td class="py-3 px-4 font-mono text-slate-500 text-[11px]">
                                    {{ db.host }}:{{ db.port }}
                                </td>

                                <!-- Status -->
                                <td class="py-3 px-4">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 uppercase font-mono">
                                        ● {{ db.status }}
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td class="py-3 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        
                                        <!-- phpMyAdmin SSO -->
                                        <a 
                                            :href="route('databases.sso', db.id)" 
                                            target="_blank"
                                            title="Open in phpMyAdmin GUI"
                                            class="p-1.5 bg-white hover:bg-purple-50 text-purple-700 border border-purple-200 rounded-[3px] shadow-2xs transition cursor-pointer"
                                        >
                                            <ArrowTopRightOnSquareIcon class="w-3.5 h-3.5" />
                                        </a>

                                        <!-- Import .SQL -->
                                        <button 
                                            @click="openImportModal(db)"
                                            title="Import .SQL File into Database"
                                            class="p-1.5 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-[3px] shadow-2xs transition cursor-pointer"
                                        >
                                            <ArrowUpTrayIcon class="w-3.5 h-3.5 text-blue-600" />
                                        </button>

                                        <!-- Export .SQL -->
                                        <a 
                                            :href="route('databases.export', db.id)" 
                                            title="Export .SQL Database Dump"
                                            class="p-1.5 bg-white hover:bg-blue-50 text-blue-600 border border-blue-200 rounded-[3px] shadow-2xs transition cursor-pointer"
                                        >
                                            <ArrowDownTrayIcon class="w-3.5 h-3.5" />
                                        </a>

                                        <!-- Repair Tables -->
                                        <button 
                                            @click="repairDatabase(db)"
                                            title="Check & Repair All Tables"
                                            class="p-1.5 bg-white hover:bg-emerald-50 text-emerald-600 border border-emerald-200 rounded-[3px] shadow-2xs transition cursor-pointer"
                                        >
                                            <WrenchScrewdriverIcon class="w-3.5 h-3.5" />
                                        </button>

                                        <!-- Optimize Tables -->
                                        <button 
                                            @click="optimizeDatabase(db)"
                                            title="Defragment & Optimize Tables"
                                            class="p-1.5 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-[3px] shadow-2xs transition cursor-pointer"
                                        >
                                            <SparklesIcon class="w-3.5 h-3.5 text-amber-600" />
                                        </button>

                                        <!-- Reset Password -->
                                        <button 
                                            @click="openPasswordModal(db)"
                                            title="Reset Database User Password"
                                            class="p-1.5 bg-white hover:bg-amber-50 text-amber-600 border border-amber-200 rounded-[3px] shadow-2xs transition cursor-pointer"
                                        >
                                            <KeyIcon class="w-3.5 h-3.5" />
                                        </button>

                                        <!-- Delete Database -->
                                        <button 
                                            @click="confirmDeleteDatabase(db)"
                                            type="button"
                                            class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded transition cursor-pointer"
                                            title="Drop Database & User"
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
                    <span>MySQL 8.0 InnoDB engine with UTF8MB4 international collation.</span>
                    <span class="font-mono">Port 3306 • Multi-tenant Isolation</span>
                </div>

            </div>

            <!-- 5. TAB 2: Connection Snippets -->
            <div v-else-if="activeTab === 'snippets'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                
                <!-- Laravel .env snippet -->
                <div class="bg-slate-900 text-slate-100 rounded-lg p-4 shadow-2xs border border-slate-800 space-y-3">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-2">
                        <span class="text-xs font-bold text-slate-200 font-mono">Laravel .env Config</span>
                        <button 
                            @click="copyToClipboard(getLaravelEnvSnippet(databases[0]), 'laravel_env')"
                            class="text-[11px] font-bold text-blue-400 hover:text-blue-300 flex items-center gap-1 cursor-pointer"
                        >
                            <ClipboardDocumentIcon class="w-3.5 h-3.5" />
                            <span>{{ copiedText === 'laravel_env' ? 'Copied!' : 'Copy Snippet' }}</span>
                        </button>
                    </div>

                    <pre class="bg-slate-950 p-3 rounded text-xs font-mono text-emerald-400 overflow-x-auto select-all leading-relaxed">{{ getLaravelEnvSnippet(databases[0]) }}</pre>
                </div>

                <!-- WordPress wp-config.php snippet -->
                <div class="bg-slate-900 text-slate-100 rounded-lg p-4 shadow-2xs border border-slate-800 space-y-3">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-2">
                        <span class="text-xs font-bold text-slate-200 font-mono">WordPress wp-config.php Config</span>
                        <button 
                            @click="copyToClipboard(getWordpressSnippet(databases[0]), 'wp_env')"
                            class="text-[11px] font-bold text-blue-400 hover:text-blue-300 flex items-center gap-1 cursor-pointer"
                        >
                            <ClipboardDocumentIcon class="w-3.5 h-3.5" />
                            <span>{{ copiedText === 'wp_env' ? 'Copied!' : 'Copy Snippet' }}</span>
                        </button>
                    </div>

                    <pre class="bg-slate-950 p-3 rounded text-xs font-mono text-purple-300 overflow-x-auto select-all leading-relaxed">{{ getWordpressSnippet(databases[0]) }}</pre>
                </div>

            </div>

        </div>

        <!-- ========================================================= -->
        <!-- MODALS                                                    -->
        <!-- ========================================================= -->
        <Teleport to="body">
            
            <!-- 1. Create Database Modal -->
            <div v-if="showCreateModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4 z-50">
                <div class="bg-white rounded-lg max-w-md w-full p-5 shadow-2xl space-y-4 border border-slate-200">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-2">
                            <CircleStackIcon class="w-5 h-5 text-purple-600" />
                            <h3 class="font-bold text-slate-900 text-sm">Create MySQL Database</h3>
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
                                    {{ sub.domain }} • {{ sub.plan_name }} (Prefix: {{ sub.prefix }}_)
                                </option>
                            </select>
                        </div>

                        <!-- Database Name with Prefix -->
                        <div class="space-y-1.5">
                            <label class="block font-bold text-slate-700">Database Name</label>
                            <div class="flex rounded-[3px] shadow-2xs">
                                <span class="inline-flex items-center px-3 rounded-l-[3px] border border-r-0 border-slate-300 bg-slate-100 text-slate-600 font-mono font-bold text-xs">
                                    {{ activePrefix }}_
                                </span>
                                <input 
                                    v-model="createForm.name" 
                                    type="text" 
                                    required 
                                    placeholder="e.g. app_db" 
                                    class="flex-1 min-w-0 bg-white border border-slate-300 rounded-r-[3px] px-2.5 py-2 text-xs font-mono font-bold text-slate-900 focus:ring-1 focus:ring-blue-600 focus:border-blue-600" 
                                />
                            </div>
                            <span v-if="createForm.errors.name" class="text-[11px] text-rose-600 font-bold block mt-1">
                                {{ createForm.errors.name }}
                            </span>
                        </div>

                        <!-- Database User with Prefix -->
                        <div class="space-y-1.5">
                            <label class="block font-bold text-slate-700">Database User</label>
                            <div class="flex rounded-[3px] shadow-2xs">
                                <span class="inline-flex items-center px-3 rounded-l-[3px] border border-r-0 border-slate-300 bg-slate-100 text-slate-600 font-mono font-bold text-xs">
                                    {{ activePrefix }}_
                                </span>
                                <input 
                                    v-model="createForm.db_user" 
                                    type="text" 
                                    required 
                                    placeholder="e.g. app_user" 
                                    class="flex-1 min-w-0 bg-white border border-slate-300 rounded-r-[3px] px-2.5 py-2 text-xs font-mono font-bold text-slate-900 focus:ring-1 focus:ring-blue-600 focus:border-blue-600" 
                                />
                            </div>
                            <span v-if="createForm.errors.db_user" class="text-[11px] text-rose-600 font-bold block mt-1">
                                {{ createForm.errors.db_user }}
                            </span>
                        </div>

                        <!-- Database Password with Generator -->
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between">
                                <label class="font-bold text-slate-700">Database Password</label>
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
                                v-model="createForm.db_password" 
                                type="text" 
                                required 
                                placeholder="Enter secure password" 
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
                                <span>{{ createForm.processing ? 'Provisioning...' : 'Create Database' }}</span>
                            </button>
                        </div>

                    </form>
                </div>
            </div>

            <!-- 2. Import .SQL Modal -->
            <div v-if="showImportModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4 z-50">
                <div class="bg-white rounded-lg max-w-md w-full p-5 shadow-2xl space-y-4 border border-slate-200">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-2">
                            <ArrowUpTrayIcon class="w-5 h-5 text-blue-600" />
                            <h3 class="font-bold text-slate-900 text-sm">Import .SQL Dump into Database</h3>
                        </div>
                        <button @click="showImportModal = false" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                            <XMarkIcon class="w-4 h-4" />
                        </button>
                    </div>

                    <form @submit.prevent="submitImport" class="space-y-4 text-xs">
                        
                        <div class="space-y-1.5">
                            <label class="block font-bold text-slate-700">Target Database</label>
                            <select 
                                v-model="importForm.database_id" 
                                required 
                                class="w-full bg-white border border-slate-300 rounded-[3px] px-2.5 py-2 text-xs font-bold text-slate-900 focus:ring-1 focus:ring-blue-600 focus:border-blue-600 font-mono"
                            >
                                <option v-for="db in databases" :key="db.id" :value="db.id">
                                    {{ db.name }} (User: {{ db.db_user }})
                                </option>
                            </select>
                        </div>

                        <div class="space-y-1.5">
                            <label class="block font-bold text-slate-700">Choose .SQL or .SQL.GZ File</label>
                            <input 
                                type="file" 
                                accept=".sql,.gz" 
                                required
                                @change="onFileSelected"
                                class="w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-[3px] file:border-0 file:text-xs file:font-bold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer"
                            />
                            <span v-if="importForm.errors.sql_file" class="text-[11px] text-rose-600 font-bold block mt-1">
                                {{ importForm.errors.sql_file }}
                            </span>
                        </div>

                        <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                            <button 
                                type="button" 
                                @click="showImportModal = false" 
                                class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-[3px] text-xs font-bold cursor-pointer"
                            >
                                Cancel
                            </button>
                            <button 
                                type="submit" 
                                :disabled="importForm.processing || !importForm.sql_file"
                                class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold shadow-2xs transition cursor-pointer disabled:opacity-50 flex items-center gap-1.5"
                            >
                                <ArrowUpTrayIcon class="w-3.5 h-3.5" :class="{ 'animate-spin': importForm.processing }" />
                                <span>{{ importForm.processing ? 'Importing SQL...' : 'Start Import' }}</span>
                            </button>
                        </div>

                    </form>
                </div>
            </div>

            <!-- 3. Reset Password Modal -->
            <div v-if="showPasswordModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4 z-50">
                <div class="bg-white rounded-lg max-w-sm w-full p-5 shadow-2xl space-y-4 border border-slate-200">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                        <h3 class="font-bold text-slate-900 text-sm">Reset Password for {{ selectedDb?.db_user }}</h3>
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

            <!-- 4. Delete Confirmation Modal -->
            <div v-if="showDeleteModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4 z-50">
                <div class="bg-white rounded-lg max-w-md w-full p-5 shadow-2xl border border-slate-200 space-y-4">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-lg bg-rose-50 border border-rose-100 text-rose-600 flex items-center justify-center shrink-0">
                            <TrashIcon class="w-5 h-5 stroke-[1.75]" />
                        </div>
                        <div class="flex-1">
                            <h3 class="text-sm font-black text-slate-900">Drop Database & User?</h3>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Are you sure you want to drop database <strong class="text-rose-600 font-mono">{{ deleteTargetDb?.name }}</strong>? All stored tables and relational data will be deleted permanently.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                        <button 
                            @click="showDeleteModal = false; deleteTargetDb = null" 
                            type="button" 
                            :disabled="deletingDb"
                            class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-[3px] text-xs font-bold transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button 
                            @click="executeDeleteDatabase" 
                            type="button"
                            :disabled="deletingDb"
                            class="px-4 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-[3px] text-xs font-bold shadow-2xs transition cursor-pointer flex items-center gap-1.5 disabled:opacity-50"
                        >
                            <TrashIcon class="w-3.5 h-3.5" />
                            <span>{{ deletingDb ? 'Dropping...' : 'Drop Database' }}</span>
                        </button>
                    </div>
                </div>
            </div>

        </Teleport>

    </AuthenticatedLayout>
</template>
