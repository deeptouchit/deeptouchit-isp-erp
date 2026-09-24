<script setup>
import { ref, computed } from 'vue'
import { Head, useForm, router, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import { 
    LockClosedIcon, 
    PlusIcon, 
    PencilSquareIcon, 
    TrashIcon, 
    UserPlusIcon,
    KeyIcon,
    ShieldCheckIcon,
    ArrowPathIcon,
    XMarkIcon,
    ExclamationTriangleIcon,
    InformationCircleIcon,
    MagnifyingGlassIcon,
    FolderIcon,
    UserIcon,
    UserGroupIcon,
    SparklesIcon,
    CheckCircleIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    directories: {
        type: Array,
        default: () => []
    },
    domains: {
        type: Array,
        default: () => ['somitysoft.com']
    },
    stats: {
        type: Object,
        default: () => ({
            total_protected: 0,
            total_users: 0,
            auth_type: 'HTTP Basic Auth (RFC 7617)',
            encryption: 'BCrypt / APR1-MD5',
            doc_root: '/var/www/vhosts/somitysoft/somitysoft.com/public_html',
            default_domain: 'somitysoft.com'
        })
    }
})

// Active Tab
const activeTab = ref('dirs') // 'dirs' | 'users' | 'guide'

// Search
const searchQuery = ref('')
const filteredDirectories = computed(() => {
    if (!searchQuery.value.trim()) return props.directories
    const q = searchQuery.value.toLowerCase().trim()
    return props.directories.filter(d => 
        d.path.toLowerCase().includes(q) ||
        d.realm.toLowerCase().includes(q) ||
        (d.domain && d.domain.toLowerCase().includes(q))
    )
})

// Modals
const showCreateModal = ref(false)
const showEditModal = ref(false)
const showDeleteModal = ref(false)
const showUsersModal = ref(false)

const editingDir = ref(null)
const deletingDir = ref(null)
const managingUsersDir = ref(null)

// Forms
const createForm = useForm({
    path: '',
    realm: 'Restricted Area - Authorized Personnel Only',
    domain: props.domains[0] || '',
    username: 'admin',
    password: '',
})

const editForm = useForm({
    path: '',
    realm: '',
    domain: '',
    is_active: true,
})

const addUserForm = useForm({
    username: '',
    password: '',
})

// Actions
const openCreateModal = () => {
    createForm.reset({
        path: '/admin',
        realm: 'Restricted Area - Authorized Personnel Only',
        domain: props.domains[0] || '',
        username: 'admin',
        password: '',
    })
    showCreateModal.value = true
}

const submitCreate = () => {
    createForm.post(route('advanced.protected-dirs.create'), {
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false
            createForm.reset()
        }
    })
}

const openEditModal = (dir) => {
    editingDir.value = dir
    editForm.path = dir.path
    editForm.realm = dir.realm
    editForm.domain = dir.domain
    editForm.is_active = dir.is_active
    showEditModal.value = true
}

const submitEdit = () => {
    if (!editingDir.value) return
    editForm.put(route('advanced.protected-dirs.update', editingDir.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showEditModal.value = false
            editingDir.value = null
        }
    })
}

const confirmDelete = (dir) => {
    deletingDir.value = dir
    showDeleteModal.value = true
}

const executeDelete = () => {
    if (!deletingDir.value) return
    router.delete(route('advanced.protected-dirs.delete', deletingDir.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false
            deletingDir.value = null
        }
    })
}

const toggleDirectory = (dir) => {
    router.post(route('advanced.protected-dirs.toggle', dir.id), {}, {
        preserveScroll: true,
    })
}

const openUsersModal = (dir) => {
    managingUsersDir.value = dir
    addUserForm.reset({
        username: '',
        password: '',
    })
    showUsersModal.value = true
}

const submitAddUser = () => {
    if (!managingUsersDir.value) return
    addUserForm.post(route('advanced.protected-dirs.add-user', managingUsersDir.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            addUserForm.reset()
            // Refresh modal state
            const updated = props.directories.find(d => d.id === managingUsersDir.value.id)
            if (updated) managingUsersDir.value = updated
        }
    })
}

const deleteUser = (user) => {
    if (!confirm(`Delete user '${user.username}' from this protected directory?`)) return
    router.delete(route('advanced.protected-dirs.delete-user', user.id), {
        preserveScroll: true,
        onSuccess: () => {
            if (managingUsersDir.value) {
                const updated = props.directories.find(d => d.id === managingUsersDir.value.id)
                if (updated) managingUsersDir.value = updated
            }
        }
    })
}

const setSuggestedPath = (p) => {
    createForm.path = p
}
</script>

<template>
    <Head title="Password Protected Directories - DeepTouch Host" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto space-y-4">
            
            <!-- 1. Page Header with Breadcrumbs & Actions -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Advanced Tools', href: '#' },
                    { label: 'Password Protected Directories' }
                ]"
                :show-refresh="true"
                @refreshed="router.reload({ preserveScroll: true })"
            >
                <template #actions>
                    <button 
                        @click="openCreateModal"
                        class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <PlusIcon class="w-3.5 h-3.5 stroke-[2.5]" />
                        <span>Protect Directory</span>
                    </button>
                </template>
            </PageHeader>

            <!-- 2. Top 3 KPI / Security Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                
                <!-- Card 1: Protected Directories Status -->
                <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-2xs space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <LockClosedIcon class="w-4 h-4 text-rose-600" />
                            <span>Protected Directories</span>
                        </span>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200 font-mono">
                            ACTIVE
                        </span>
                    </div>

                    <div>
                        <div class="flex items-baseline gap-2">
                            <span class="text-2xl font-black text-slate-900 font-mono">{{ stats.total_protected }}</span>
                            <span class="text-xs text-slate-400 font-bold font-mono">/ {{ directories.length }} Folders Locked</span>
                        </div>
                        <p class="text-[11px] text-slate-500 mt-1 font-mono truncate" :title="stats.doc_root">
                            Root: <strong class="text-slate-800">{{ stats.doc_root }}</strong>
                        </p>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1 border-t border-slate-100 font-mono">
                        <span>Scope Domain:</span>
                        <strong class="text-slate-700">{{ stats.default_domain }}</strong>
                    </div>
                </div>

                <!-- Card 2: Authorized Logins & Credentials -->
                <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-2xs space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <UserGroupIcon class="w-4 h-4 text-indigo-600" />
                            <span>Authorized Web Users</span>
                        </span>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-700 border border-indigo-200 font-mono">
                            HTTP AUTH
                        </span>
                    </div>

                    <div>
                        <div class="flex items-baseline gap-2">
                            <span class="text-2xl font-black text-slate-900 font-mono">{{ stats.total_users }}</span>
                            <span class="text-xs text-slate-400 font-bold font-mono">Total Credentials</span>
                        </div>
                        <p class="text-[11px] text-slate-500 mt-1">
                            Password Hash: <strong class="text-slate-800 font-mono">{{ stats.encryption }}</strong>
                        </p>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1 border-t border-slate-100 font-mono">
                        <span>Protocol:</span>
                        <strong class="text-slate-700">RFC 7617</strong>
                    </div>
                </div>

                <!-- Card 3: Security & Brute-Force Shield -->
                <div class="bg-gradient-to-br from-indigo-50/70 to-blue-50/50 border border-indigo-100 rounded-lg p-4 shadow-2xs flex flex-col justify-between space-y-2.5">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-indigo-900 flex items-center gap-1.5">
                                <ShieldCheckIcon class="w-4 h-4 text-indigo-600" />
                                <span>Zero-PHP Overhead Shield</span>
                            </span>
                            <span class="text-[10px] font-bold px-1.5 py-0.5 bg-indigo-100 text-indigo-800 rounded font-mono">
                                NGINX FAST
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-600 mt-1 line-clamp-2">
                            Unauthenticated requests are blocked at web server layer before executing any backend code.
                        </p>
                    </div>

                    <div class="pt-2 border-t border-indigo-100 flex items-center justify-between text-[11px] text-indigo-900 font-mono">
                        <span>Protection Level:</span>
                        <span class="font-bold text-emerald-700">Hardware Fast Return</span>
                    </div>
                </div>

            </div>

            <!-- 3. Navigation Tabs -->
            <div class="border-b border-slate-200 flex items-center gap-4 text-xs font-bold">
                <button 
                    @click="activeTab = 'dirs'"
                    class="pb-2.5 border-b-2 transition cursor-pointer flex items-center gap-1.5"
                    :class="activeTab === 'dirs' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-800'"
                >
                    <FolderIcon class="w-4 h-4" />
                    <span>Protected Folders ({{ directories.length }})</span>
                </button>

                <button 
                    @click="activeTab = 'users'"
                    class="pb-2.5 border-b-2 transition cursor-pointer flex items-center gap-1.5"
                    :class="activeTab === 'users' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-800'"
                >
                    <UserGroupIcon class="w-4 h-4" />
                    <span>Authorized Users Inventory ({{ stats.total_users }})</span>
                </button>

                <button 
                    @click="activeTab = 'guide'"
                    class="pb-2.5 border-b-2 transition cursor-pointer flex items-center gap-1.5"
                    :class="activeTab === 'guide' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-800'"
                >
                    <InformationCircleIcon class="w-4 h-4" />
                    <span>Security Guide & Presets</span>
                </button>
            </div>

            <!-- 4. TAB 1: Protected Directories Table -->
            <div v-if="activeTab === 'dirs'" class="bg-white border border-slate-200 rounded-lg shadow-2xs overflow-hidden">
                
                <div class="px-4 py-3 bg-slate-50/80 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <LockClosedIcon class="w-4 h-4 text-rose-600" />
                        <span class="text-xs font-bold text-slate-900">Configured Directory Protections</span>
                        <span class="text-[11px] text-slate-400 font-mono">({{ filteredDirectories.length }})</span>
                    </div>

                    <div class="relative w-full sm:w-64">
                        <MagnifyingGlassIcon class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2" />
                        <input 
                            v-model="searchQuery"
                            type="text" 
                            placeholder="Filter by path or domain..." 
                            class="w-full pl-8 pr-2.5 py-1.5 bg-white border border-slate-300 rounded-[3px] text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-blue-500 font-medium shadow-2xs"
                        />
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="filteredDirectories.length === 0" class="p-12 text-center text-slate-500 text-xs">
                    <LockClosedIcon class="w-12 h-12 text-slate-300 mx-auto mb-2" />
                    <h4 class="text-sm font-bold text-slate-900 mb-1">
                        {{ searchQuery ? 'No protected folders match your filter' : 'No Password Protected Directories' }}
                    </h4>
                    <p class="text-[11px] text-slate-400 max-w-sm mx-auto mb-4">
                        {{ searchQuery ? 'Try clearing your search query.' : 'Lock administrative folders, staging subdirectories, or private assets with HTTP Basic Auth.' }}
                    </p>
                    <button 
                        @click="openCreateModal"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold shadow-2xs transition cursor-pointer"
                    >
                        Protect First Directory
                    </button>
                </div>

                <!-- Table Content -->
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-700">
                        <thead class="bg-slate-50/90 border-b border-slate-200 text-slate-500 uppercase font-bold tracking-wider text-[10.5px] select-none">
                            <tr>
                                <th class="py-2.5 px-4">Directory Path</th>
                                <th class="py-2.5 px-4 w-44">Domain Scope</th>
                                <th class="py-2.5 px-4">Auth Prompt Realm</th>
                                <th class="py-2.5 px-4 w-32">Users</th>
                                <th class="py-2.5 px-4 w-28">Status</th>
                                <th class="py-2.5 px-4 w-36 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium">
                            <tr v-for="dir in filteredDirectories" :key="dir.id" class="hover:bg-slate-50/80 transition-colors">
                                
                                <!-- Directory Path -->
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded bg-rose-50 border border-rose-200 text-rose-600 flex items-center justify-center shrink-0">
                                            <FolderIcon class="w-3.5 h-3.5" />
                                        </div>
                                        <div>
                                            <span class="font-bold text-slate-900 font-mono text-[11.5px]">{{ dir.path }}</span>
                                            <span class="block text-[10px] text-slate-400">Created {{ dir.created_at }}</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Domain -->
                                <td class="py-3 px-4 font-mono text-[11px] text-slate-600">
                                    {{ dir.domain || 'All Domains' }}
                                </td>

                                <!-- Realm -->
                                <td class="py-3 px-4 text-slate-700 text-[11px]">
                                    <span class="truncate block max-w-xs" :title="dir.realm">{{ dir.realm }}</span>
                                </td>

                                <!-- Users -->
                                <td class="py-3 px-4">
                                    <button 
                                        @click="openUsersModal(dir)"
                                        class="px-2 py-0.5 rounded-[3px] bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 font-bold text-[10.5px] flex items-center gap-1 cursor-pointer"
                                    >
                                        <UserIcon class="w-3 h-3 text-indigo-600" />
                                        <span>{{ dir.users_count }} Users</span>
                                    </button>
                                </td>

                                <!-- Status -->
                                <td class="py-3 px-4">
                                    <button 
                                        @click="toggleDirectory(dir)"
                                        class="px-2 py-0.5 rounded text-[10px] font-bold border uppercase font-mono cursor-pointer transition"
                                        :class="dir.is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100' : 'bg-slate-100 text-slate-600 border-slate-200 hover:bg-slate-200'"
                                    >
                                        ● {{ dir.is_active ? 'PROTECTED' : 'DISABLED' }}
                                    </button>
                                </td>

                                <!-- Actions -->
                                <td class="py-3 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        
                                        <!-- Manage Users -->
                                        <button 
                                            @click="openUsersModal(dir)"
                                            title="Manage Authorized Users"
                                            class="p-1.5 bg-white hover:bg-indigo-50 text-indigo-700 border border-indigo-200 rounded-[3px] shadow-2xs transition cursor-pointer"
                                        >
                                            <UserPlusIcon class="w-3.5 h-3.5" />
                                        </button>

                                        <!-- Edit Directory -->
                                        <button 
                                            @click="openEditModal(dir)"
                                            title="Edit Directory"
                                            class="p-1.5 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-[3px] shadow-2xs transition cursor-pointer"
                                        >
                                            <PencilSquareIcon class="w-3.5 h-3.5 text-blue-600" />
                                        </button>

                                        <!-- Delete Protection -->
                                        <button 
                                            @click="confirmDelete(dir)"
                                            title="Remove Protection"
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
                    <span>Nginx auth_basic & Apache .htaccess standard compliant.</span>
                    <span class="font-mono">Security: Zero PHP Execution on 401</span>
                </div>

            </div>

            <!-- 5. TAB 2: Authorized Users Inventory -->
            <div v-else-if="activeTab === 'users'" class="bg-white border border-slate-200 rounded-lg p-5 shadow-2xs space-y-4">
                
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2">
                        <UserGroupIcon class="w-4 h-4 text-indigo-600" />
                        <h3 class="text-xs font-bold text-slate-900">Authorized Directory Logins</h3>
                    </div>
                </div>

                <div v-if="directories.length === 0" class="text-center py-8 text-slate-400 text-xs">
                    No protected directories created yet.
                </div>

                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                    <div 
                        v-for="dir in directories" 
                        :key="dir.id"
                        class="p-3.5 bg-slate-50/80 rounded border border-slate-200 space-y-2.5"
                    >
                        <div class="flex items-center justify-between border-b border-slate-200/70 pb-2">
                            <span class="font-mono font-bold text-slate-900 text-xs flex items-center gap-1.5">
                                <FolderIcon class="w-3.5 h-3.5 text-rose-600" />
                                <span>{{ dir.path }}</span>
                            </span>
                            <button 
                                @click="openUsersModal(dir)"
                                class="text-[10.5px] text-blue-600 font-bold hover:underline cursor-pointer"
                            >
                                + Add User
                            </button>
                        </div>

                        <div class="space-y-1.5">
                            <div 
                                v-for="u in dir.users" 
                                :key="u.id"
                                class="flex items-center justify-between bg-white p-1.5 rounded border border-slate-200 text-xs font-mono"
                            >
                                <span class="font-bold text-slate-800">{{ u.username }}</span>
                                <button 
                                    @click="deleteUser(u)"
                                    class="text-slate-400 hover:text-rose-600 cursor-pointer"
                                    title="Delete user"
                                >
                                    <TrashIcon class="w-3.5 h-3.5" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- 6. TAB 3: Security Guide & Presets -->
            <div v-else-if="activeTab === 'guide'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                
                <div class="bg-white border border-slate-200 rounded-lg p-5 shadow-2xs space-y-3">
                    <h3 class="font-bold text-slate-900 text-sm flex items-center gap-2">
                        <ShieldCheckIcon class="w-4 h-4 text-emerald-600" />
                        <span>Recommended Security Hardening Paths</span>
                    </h3>
                    <ul class="text-xs text-slate-600 space-y-2 leading-relaxed">
                        <li class="flex items-start gap-2">
                            <span class="font-mono text-blue-600 font-bold">/admin</span>
                            <span>Custom administrative control panels or internal tools.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="font-mono text-blue-600 font-bold">/wp-admin</span>
                            <span>Double-authentication shield against WordPress brute-force bot attacks.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="font-mono text-blue-600 font-bold">/staging</span>
                            <span>Prevent search engines (Googlebot) and unauthorized visitors from seeing test environments.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="font-mono text-blue-600 font-bold">/phpmyadmin</span>
                            <span>Block database management interface access.</span>
                        </li>
                    </ul>
                </div>

                <div class="bg-white border border-slate-200 rounded-lg p-5 shadow-2xs space-y-3">
                    <h3 class="font-bold text-slate-900 text-sm flex items-center gap-2">
                        <SparklesIcon class="w-4 h-4 text-indigo-600" />
                        <span>How HTTP Basic Auth Works</span>
                    </h3>
                    <p class="text-xs text-slate-500 leading-relaxed">
                        When a visitor attempts to access a protected directory, their web browser prompts for a Username and Password dialog. If credentials match the encrypted hash store, access is granted for the browser session.
                    </p>
                    <div class="bg-slate-900 text-slate-100 p-2.5 rounded font-mono text-[11px]">
                        HTTP/1.1 401 Unauthorized<br>
                        WWW-Authenticate: Basic realm="Restricted Area"
                    </div>
                </div>

            </div>

        </div>

        <!-- ========================================================= -->
        <!-- MODALS                                                    -->
        <!-- ========================================================= -->
        <Teleport to="body">
            
            <!-- 1. Protect Directory Modal -->
            <div v-if="showCreateModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4 z-50">
                <div class="bg-white rounded-lg max-w-lg w-full p-5 shadow-2xl space-y-4 border border-slate-200">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-2">
                            <LockClosedIcon class="w-5 h-5 text-blue-600" />
                            <h3 class="font-bold text-slate-900 text-sm">
                                Password Protect Directory
                            </h3>
                        </div>
                        <button @click="showCreateModal = false" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                            <XMarkIcon class="w-4 h-4" />
                        </button>
                    </div>

                    <form @submit.prevent="submitCreate" class="space-y-3.5 text-xs">
                        
                        <!-- Domain & Path -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="space-y-1">
                                <label class="block font-bold text-slate-700">Domain Scope</label>
                                <select 
                                    v-model="createForm.domain"
                                    class="w-full bg-white border border-slate-300 rounded-[3px] px-2.5 py-2 text-xs font-bold text-slate-900 focus:ring-1 focus:ring-blue-600 cursor-pointer"
                                >
                                    <option v-for="d in domains" :key="d" :value="d">{{ d }}</option>
                                </select>
                            </div>

                            <div class="space-y-1">
                                <label class="block font-bold text-slate-700">Directory Path</label>
                                <input 
                                    v-model="createForm.path" 
                                    type="text" 
                                    required 
                                    placeholder="/admin or /wp-admin" 
                                    class="w-full bg-white border border-slate-300 rounded-[3px] px-2.5 py-2 text-xs font-mono font-bold text-slate-900 focus:ring-1 focus:ring-blue-600" 
                                />
                            </div>
                        </div>

                        <!-- Quick Path Suggestions -->
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <span class="text-[10.5px] text-slate-400 font-bold">Suggestions:</span>
                            <button 
                                v-for="p in ['/admin', '/wp-admin', '/staging', '/portal', '/docs', '/private']" 
                                :key="p"
                                type="button"
                                @click="setSuggestedPath(p)"
                                class="px-1.5 py-0.5 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 font-mono text-[10.5px] cursor-pointer"
                            >
                                {{ p }}
                            </button>
                        </div>

                        <!-- Realm / Prompt Message -->
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Auth Prompt Message (Realm)</label>
                            <input 
                                v-model="createForm.realm" 
                                type="text" 
                                required 
                                placeholder="Restricted Area - Authorized Personnel Only" 
                                class="w-full bg-white border border-slate-300 rounded-[3px] px-2.5 py-2 text-xs font-bold text-slate-900 focus:ring-1 focus:ring-blue-600" 
                            />
                        </div>

                        <!-- Initial User Credentials -->
                        <div class="p-3 bg-slate-50 rounded border border-slate-200 space-y-3">
                            <h4 class="font-bold text-slate-900 text-[11.5px] flex items-center gap-1.5">
                                <KeyIcon class="w-3.5 h-3.5 text-indigo-600" />
                                <span>Initial Authorized Login Credentials</span>
                            </h4>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div class="space-y-1">
                                    <label class="block font-bold text-slate-700">Username</label>
                                    <input 
                                        v-model="createForm.username" 
                                        type="text" 
                                        required 
                                        placeholder="admin" 
                                        class="w-full bg-white border border-slate-300 rounded-[3px] px-2.5 py-1.5 text-xs font-mono font-bold text-slate-900 focus:ring-1 focus:ring-blue-600" 
                                    />
                                </div>

                                <div class="space-y-1">
                                    <label class="block font-bold text-slate-700">Password</label>
                                    <input 
                                        v-model="createForm.password" 
                                        type="password" 
                                        required 
                                        placeholder="Enter password..." 
                                        class="w-full bg-white border border-slate-300 rounded-[3px] px-2.5 py-1.5 text-xs font-mono text-slate-900 focus:ring-1 focus:ring-blue-600" 
                                    />
                                </div>
                            </div>
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
                                <span>{{ createForm.processing ? 'Protecting...' : 'Lock Directory' }}</span>
                            </button>
                        </div>

                    </form>
                </div>
            </div>

            <!-- 2. Edit Directory Modal -->
            <div v-if="showEditModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4 z-50">
                <div class="bg-white rounded-lg max-w-lg w-full p-5 shadow-2xl space-y-4 border border-slate-200">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-2">
                            <PencilSquareIcon class="w-5 h-5 text-blue-600" />
                            <h3 class="font-bold text-slate-900 text-sm">Edit Directory Protection</h3>
                        </div>
                        <button @click="showEditModal = false" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                            <XMarkIcon class="w-4 h-4" />
                        </button>
                    </div>

                    <form @submit.prevent="submitEdit" class="space-y-3.5 text-xs">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="space-y-1">
                                <label class="block font-bold text-slate-700">Domain Scope</label>
                                <select 
                                    v-model="editForm.domain"
                                    class="w-full bg-white border border-slate-300 rounded-[3px] px-2.5 py-2 text-xs font-bold text-slate-900 focus:ring-1 focus:ring-blue-600 cursor-pointer"
                                >
                                    <option v-for="d in domains" :key="d" :value="d">{{ d }}</option>
                                </select>
                            </div>

                            <div class="space-y-1">
                                <label class="block font-bold text-slate-700">Directory Path</label>
                                <input 
                                    v-model="editForm.path" 
                                    type="text" 
                                    required 
                                    class="w-full bg-white border border-slate-300 rounded-[3px] px-2.5 py-2 text-xs font-mono font-bold text-slate-900 focus:ring-1 focus:ring-blue-600" 
                                />
                            </div>
                        </div>

                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Auth Prompt Message (Realm)</label>
                            <input 
                                v-model="editForm.realm" 
                                type="text" 
                                required 
                                class="w-full bg-white border border-slate-300 rounded-[3px] px-2.5 py-2 text-xs font-bold text-slate-900 focus:ring-1 focus:ring-blue-600" 
                            />
                        </div>

                        <div class="pt-1">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input 
                                    v-model="editForm.is_active" 
                                    type="checkbox" 
                                    class="w-4 h-4 text-blue-600 rounded-[2px] border-slate-300"
                                />
                                <span class="font-bold text-slate-700">Keep protection actively enforced</span>
                            </label>
                        </div>

                        <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                            <button 
                                type="button" 
                                @click="showEditModal = false" 
                                class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-[3px] text-xs font-bold cursor-pointer"
                            >
                                Cancel
                            </button>
                            <button 
                                type="submit" 
                                :disabled="editForm.processing"
                                class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold shadow-2xs transition cursor-pointer disabled:opacity-50"
                            >
                                <span>{{ editForm.processing ? 'Saving...' : 'Update Settings' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 3. Manage Users Modal -->
            <div v-if="showUsersModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4 z-50">
                <div class="bg-white rounded-lg max-w-lg w-full p-5 shadow-2xl space-y-4 border border-slate-200">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-2">
                            <UserGroupIcon class="w-5 h-5 text-indigo-600" />
                            <h3 class="font-bold text-slate-900 text-sm">
                                Authorized Users: <span class="font-mono text-rose-600">{{ managingUsersDir?.path }}</span>
                            </h3>
                        </div>
                        <button @click="showUsersModal = false" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                            <XMarkIcon class="w-4 h-4" />
                        </button>
                    </div>

                    <!-- Current Users List -->
                    <div class="space-y-2">
                        <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider text-[10.5px]">Active Accounts ({{ managingUsersDir?.users?.length || 0 }})</h4>
                        
                        <div v-if="!managingUsersDir?.users?.length" class="text-slate-400 text-xs py-2">
                            No authorized users configured.
                        </div>

                        <div v-else class="space-y-1.5 max-h-40 overflow-y-auto pr-1">
                            <div 
                                v-for="u in managingUsersDir.users" 
                                :key="u.id"
                                class="flex items-center justify-between bg-slate-50 p-2 rounded border border-slate-200 text-xs font-mono"
                            >
                                <span class="font-bold text-slate-900">{{ u.username }}</span>
                                <button 
                                    @click="deleteUser(u)"
                                    class="text-slate-400 hover:text-rose-600 cursor-pointer"
                                    title="Delete user"
                                >
                                    <TrashIcon class="w-3.5 h-3.5" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Add New User Form -->
                    <form @submit.prevent="submitAddUser" class="p-3 bg-slate-50 rounded border border-slate-200 space-y-2.5 text-xs">
                        <h4 class="font-bold text-slate-900 text-[11.5px] flex items-center gap-1.5">
                            <UserPlusIcon class="w-3.5 h-3.5 text-blue-600" />
                            <span>Add New Authorized User</span>
                        </h4>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <input 
                                v-model="addUserForm.username"
                                type="text" 
                                required 
                                placeholder="Username" 
                                class="w-full bg-white border border-slate-300 rounded-[3px] px-2.5 py-1.5 text-xs font-mono font-bold text-slate-900 focus:ring-1 focus:ring-blue-600" 
                            />
                            <input 
                                v-model="addUserForm.password"
                                type="password" 
                                required 
                                placeholder="Password" 
                                class="w-full bg-white border border-slate-300 rounded-[3px] px-2.5 py-1.5 text-xs font-mono text-slate-900 focus:ring-1 focus:ring-blue-600" 
                            />
                        </div>

                        <div class="flex justify-end pt-1">
                            <button 
                                type="submit" 
                                :disabled="addUserForm.processing"
                                class="px-3.5 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold shadow-2xs transition cursor-pointer disabled:opacity-50"
                            >
                                <span>{{ addUserForm.processing ? 'Adding...' : 'Add User' }}</span>
                            </button>
                        </div>
                    </form>

                    <div class="flex justify-end pt-2 border-t border-slate-100">
                        <button 
                            type="button" 
                            @click="showUsersModal = false" 
                            class="px-4 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-[3px] text-xs font-bold cursor-pointer"
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
                            <h3 class="text-sm font-black text-slate-900">Remove Directory Protection?</h3>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Are you sure you want to remove password protection from <strong class="text-slate-900 font-mono">{{ deletingDir?.path }}</strong>? Anyone on the internet will be able to access this directory freely.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                        <button 
                            @click="showDeleteModal = false; deletingDir = null" 
                            type="button" 
                            class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-[3px] text-xs font-bold transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button 
                            @click="executeDelete" 
                            type="button"
                            class="px-4 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-[3px] text-xs font-bold shadow-2xs transition cursor-pointer flex items-center gap-1.5"
                        >
                            <TrashIcon class="w-3.5 h-3.5" />
                            <span>Remove Protection</span>
                        </button>
                    </div>
                </div>
            </div>

        </Teleport>

    </AuthenticatedLayout>
</template>
