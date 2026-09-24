<script setup>
import { ref, computed } from 'vue'
import { Head, useForm, Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import { 
    GlobeAltIcon, 
    PlusIcon, 
    TrashIcon, 
    ServerIcon, 
    ShieldCheckIcon,
    InformationCircleIcon,
    ClipboardDocumentIcon,
    CheckIcon,
    CommandLineIcon,
    XMarkIcon,
    ArrowPathIcon,
    ComputerDesktopIcon,
    SparklesIcon,
    MagnifyingGlassIcon,
    CircleStackIcon,
    KeyIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    databases: {
        type: Array,
        default: () => []
    },
    allowedHosts: {
        type: Array,
        default: () => []
    },
    clientIp: {
        type: String,
        default: '127.0.0.1'
    },
    serverInfo: {
        type: Object,
        default: () => ({
            host: '103.59.177.100',
            port: 3306,
            driver: 'MySQL 8.0 / MariaDB'
        })
    }
})

// Search & Filter
const searchQuery = ref('')
const filteredHosts = computed(() => {
    if (!searchQuery.value.trim()) return props.allowedHosts
    const q = searchQuery.value.toLowerCase().trim()
    return props.allowedHosts.filter(h => 
        h.user.toLowerCase().includes(q) || 
        h.host.toLowerCase().includes(q) ||
        (h.note && h.note.toLowerCase().includes(q))
    )
})

// Copy feedback
const copiedText = ref('')
const copyToClipboard = (text, key) => {
    navigator.clipboard.writeText(text)
    copiedText.value = key
    setTimeout(() => {
        copiedText.value = ''
    }, 2000)
}

// Add Host Form
const addForm = useForm({
    host: '',
    database_id: '',
    note: ''
})

const fillCurrentIp = () => {
    addForm.host = props.clientIp
}

const fillWildcard = () => {
    addForm.host = '%'
}

const submitAdd = () => {
    addForm.post(route('databases.remote.add'), {
        preserveScroll: true,
        onSuccess: () => {
            addForm.reset()
        }
    })
}

// Revoke Access Modal
const showRevokeModal = ref(false)
const targetRevoke = ref(null)
const revoking = ref(false)

const confirmRevoke = (item) => {
    targetRevoke.value = item
    showRevokeModal.value = true
}

const executeRevoke = () => {
    if (!targetRevoke.value) return
    revoking.value = true
    router.post(route('databases.remote.revoke'), {
        user: targetRevoke.value.user,
        host: targetRevoke.value.host
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showRevokeModal.value = false
            targetRevoke.value = null
        },
        onFinish: () => {
            revoking.value = false
        }
    })
}
</script>

<template>
    <Head title="Remote MySQL Access - DeepTouch Host" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto space-y-4">
            
            <!-- 1. Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Databases', href: route('databases.index') },
                    { label: 'Remote MySQL Access' }
                ]"
                :show-refresh="true"
                @refreshed="router.reload({ preserveScroll: true })"
            >
                <template #actions>
                    <Link 
                        :href="route('databases.index')"
                        class="px-3 py-1.5 bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition"
                    >
                        <span>← Back to Databases</span>
                    </Link>
                </template>
            </PageHeader>

            <!-- 2. KPI Cards (3-Column Grid) -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                
                <!-- Card 1: Remote MySQL Endpoint -->
                <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-2xs space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <ServerIcon class="w-4 h-4 text-blue-600" />
                            <span>Public Server Host</span>
                        </span>
                        <span class="text-[10px] font-mono font-bold text-slate-600 bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200">
                            Port {{ serverInfo.port }}
                        </span>
                    </div>

                    <div class="bg-slate-50 p-2 rounded border border-slate-200/70 font-mono text-xs flex items-center justify-between">
                        <span class="font-bold text-slate-900">{{ serverInfo.host }}</span>
                        <button 
                            @click="copyToClipboard(serverInfo.host, 'server_ip')"
                            class="text-slate-400 hover:text-blue-600 cursor-pointer"
                            title="Copy Server Host"
                        >
                            <CheckIcon v-if="copiedText === 'server_ip'" class="w-3.5 h-3.5 text-emerald-600" />
                            <ClipboardDocumentIcon v-else class="w-3.5 h-3.5" />
                        </button>
                    </div>

                    <p class="text-[11px] text-slate-500 pt-1 border-t border-slate-100">
                        Use this public IP address as Host in your desktop database client.
                    </p>
                </div>

                <!-- Card 2: Detected Client IP -->
                <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-2xs space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <GlobeAltIcon class="w-4 h-4 text-emerald-600" />
                            <span>Your Current IP</span>
                        </span>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200 uppercase font-mono">
                            DETECTED
                        </span>
                    </div>

                    <div class="bg-slate-50 p-2 rounded border border-slate-200/70 font-mono text-xs flex items-center justify-between">
                        <span class="font-bold text-slate-900">{{ clientIp }}</span>
                        <button 
                            @click="fillCurrentIp"
                            class="text-[11px] font-bold text-blue-600 hover:underline cursor-pointer flex items-center gap-1 font-sans"
                        >
                            <span>Use in form ↓</span>
                        </button>
                    </div>

                    <p class="text-[11px] text-slate-500 pt-1 border-t border-slate-100">
                        Whitelist this IP to connect directly from your home or office workstation.
                    </p>
                </div>

                <!-- Card 3: Compatible SQL Clients -->
                <div class="bg-gradient-to-br from-blue-50/70 to-indigo-50/50 border border-blue-100 rounded-lg p-4 shadow-2xs flex flex-col justify-between space-y-2">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-blue-900 flex items-center gap-1.5">
                                <ComputerDesktopIcon class="w-4 h-4 text-blue-600" />
                                <span>Desktop Clients</span>
                            </span>
                            <span class="text-[10px] font-bold px-1.5 py-0.5 bg-blue-100 text-blue-800 rounded font-mono">
                                TCP / TLS 1.3
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-600 mt-1">
                            Compatible with <strong>TablePlus, DBeaver, HeidiSQL, Navicat</strong>, and VS Code SQL Tools.
                        </p>
                    </div>

                    <div class="pt-2 border-t border-blue-100 text-[10.5px] text-slate-500">
                        Direct connection on Port 3306 with UTF8MB4 support.
                    </div>
                </div>

            </div>

            <!-- 3. Add Remote Access Host Form -->
            <div class="bg-white border border-slate-200 rounded-lg shadow-2xs p-5">
                <div class="border-b border-slate-100 pb-3 mb-4 flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h3 class="font-bold text-slate-900 text-sm">Add Remote Access Host / IP</h3>
                        <p class="text-[11px] text-slate-500">Authorize an external IP address or wildcard host to access your databases remotely.</p>
                    </div>

                    <div class="flex items-center gap-2">
                        <button 
                            type="button" 
                            @click="fillCurrentIp" 
                            class="px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 rounded-[3px] text-[11px] font-bold transition cursor-pointer"
                        >
                            + Add My Current IP ({{ clientIp }})
                        </button>
                        <button 
                            type="button" 
                            @click="fillWildcard" 
                            class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300 rounded-[3px] text-[11px] font-bold transition cursor-pointer"
                        >
                            + Wildcard Any IP (%)
                        </button>
                    </div>
                </div>

                <form @submit.prevent="submitAdd" class="grid grid-cols-1 md:grid-cols-4 gap-3 text-xs">
                    
                    <!-- Host / IP -->
                    <div class="space-y-1 md:col-span-2">
                        <label class="block font-bold text-slate-700">Host (IP Address or % Wildcard) <span class="text-rose-500">*</span></label>
                        <input 
                            v-model="addForm.host" 
                            type="text" 
                            required 
                            placeholder="e.g. 103.59.177.100 or %" 
                            class="w-full bg-white border border-slate-300 rounded-[3px] px-2.5 py-2 text-xs font-mono font-bold text-slate-900 focus:ring-1 focus:ring-blue-600 focus:border-blue-600" 
                        />
                        <span v-if="addForm.errors.host" class="text-[11px] text-rose-600 font-bold block mt-1">
                            {{ addForm.errors.host }}
                        </span>
                    </div>

                    <!-- Target Database -->
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Target Database Scope</label>
                        <select 
                            v-model="addForm.database_id" 
                            class="w-full bg-white border border-slate-300 rounded-[3px] px-2.5 py-2 text-xs font-bold text-slate-900 focus:ring-1 focus:ring-blue-600 focus:border-blue-600 font-mono"
                        >
                            <option value="">All My Databases</option>
                            <option v-for="db in databases" :key="db.id" :value="db.id">
                                {{ db.name }} ({{ db.db_user }})
                            </option>
                        </select>
                    </div>

                    <!-- Submit Button -->
                    <div class="space-y-1 flex flex-col justify-end">
                        <button 
                            type="submit" 
                            :disabled="addForm.processing || !addForm.host"
                            class="w-full py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold shadow-2xs transition cursor-pointer disabled:opacity-50 flex items-center justify-center gap-1.5"
                        >
                            <PlusIcon class="w-3.5 h-3.5 stroke-[2.5]" />
                            <span>{{ addForm.processing ? 'Granting Access...' : 'Add Access Host' }}</span>
                        </button>
                    </div>

                </form>
            </div>

            <!-- 4. Active Allowed Remote Hosts Table -->
            <div class="bg-white border border-slate-200 rounded-lg shadow-2xs overflow-hidden">
                
                <div class="px-4 py-3 bg-slate-50/80 border-b border-slate-200 flex flex-wrap items-center justify-between gap-2.5">
                    <div class="flex items-center gap-2">
                        <ShieldCheckIcon class="w-4 h-4 text-emerald-600" />
                        <span class="text-xs font-bold text-slate-900">Active Remote Access Rules</span>
                        <span class="text-[11px] text-slate-400 font-mono">({{ filteredHosts.length }})</span>
                    </div>

                    <div class="relative">
                        <MagnifyingGlassIcon class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2" />
                        <input 
                            type="text" 
                            v-model="searchQuery" 
                            placeholder="Filter rules..." 
                            class="pl-8 pr-2.5 py-1.5 text-xs bg-white border border-slate-300 rounded-[3px] focus:ring-blue-500 focus:border-blue-500 w-36 sm:w-48 shadow-2xs"
                        />
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="filteredHosts.length === 0" class="p-10 text-center text-slate-500 text-xs">
                    <GlobeAltIcon class="w-10 h-10 text-slate-300 mx-auto mb-2" />
                    <h4 class="text-sm font-bold text-slate-900 mb-1">No Remote Hosts Configured</h4>
                    <p class="text-[11px] text-slate-400 max-w-sm mx-auto mb-3">
                        By default, your MySQL users only allow local connections (`localhost` and `127.0.0.1`). Add your IP above to allow external connections.
                    </p>
                    <button 
                        @click="fillCurrentIp" 
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold shadow-2xs transition cursor-pointer"
                    >
                        Whitelist Current IP ({{ clientIp }})
                    </button>
                </div>

                <!-- Table Content -->
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-700">
                        <thead class="bg-slate-50/90 border-b border-slate-200 text-slate-500 uppercase font-bold tracking-wider text-[10.5px] select-none">
                            <tr>
                                <th class="py-2.5 px-4">MySQL User</th>
                                <th class="py-2.5 px-4">Allowed Remote Host / IP</th>
                                <th class="py-2.5 px-4">Access Scope</th>
                                <th class="py-2.5 px-4 w-28">Status</th>
                                <th class="py-2.5 px-4 w-28 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium">
                            <tr v-for="(item, idx) in filteredHosts" :key="idx" class="hover:bg-slate-50/80 transition-colors">
                                
                                <!-- User -->
                                <td class="py-3 px-4 font-mono font-bold text-slate-900">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded bg-blue-50 border border-blue-200 text-blue-600 flex items-center justify-center shrink-0">
                                            <KeyIcon class="w-3.5 h-3.5" />
                                        </div>
                                        <div>
                                            <span>{{ item.user }}</span>
                                            <span class="text-[10px] text-slate-400 font-sans block">MySQL Account</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Host / IP -->
                                <td class="py-3 px-4 font-mono text-slate-900 font-bold">
                                    <div class="flex items-center gap-1.5">
                                        <span 
                                            class="px-2 py-0.5 rounded-[3px] border font-bold text-xs"
                                            :class="item.host === '%' ? 'bg-amber-50 text-amber-800 border-amber-200' : 'bg-blue-50 text-blue-800 border-blue-200'"
                                        >
                                            {{ item.host }}
                                        </span>
                                        <button 
                                            @click="copyToClipboard(item.host, 'host_' + idx)" 
                                            class="text-slate-400 hover:text-blue-600 cursor-pointer"
                                            title="Copy Host"
                                        >
                                            <CheckIcon v-if="copiedText === 'host_' + idx" class="w-3.5 h-3.5 text-emerald-600" />
                                            <ClipboardDocumentIcon v-else class="w-3.5 h-3.5" />
                                        </button>
                                        <span v-if="item.host === '%'" class="text-[10px] text-amber-600 font-sans font-bold">(All Public IPs)</span>
                                    </div>
                                </td>

                                <!-- Scope -->
                                <td class="py-3 px-4 text-slate-600">
                                    <div class="flex items-center gap-1 text-[11px]">
                                        <CircleStackIcon class="w-3.5 h-3.5 text-purple-600 shrink-0" />
                                        <span>{{ item.note }}</span>
                                    </div>
                                </td>

                                <!-- Status -->
                                <td class="py-3 px-4">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 uppercase font-mono">
                                        ● ACTIVE
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td class="py-3 px-4 text-right">
                                    <button 
                                        @click="confirmRevoke(item)" 
                                        type="button" 
                                        class="px-2.5 py-1 bg-white hover:bg-rose-50 text-slate-600 hover:text-rose-700 border border-slate-200 hover:border-rose-200 rounded-[3px] text-xs font-bold shadow-2xs transition cursor-pointer flex items-center gap-1 ml-auto"
                                        title="Revoke Remote Access"
                                    >
                                        <TrashIcon class="w-3.5 h-3.5 text-rose-600" />
                                        <span>Revoke</span>
                                    </button>
                                </td>

                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="px-4 py-2.5 bg-slate-50 border-t border-slate-200 text-[11px] text-slate-500 flex items-center justify-between">
                    <span>Direct MySQL connections require Port 3306 to be open in firewall.</span>
                    <span class="font-mono">TCP Protocol • UTF8MB4</span>
                </div>

            </div>

        </div>

        <!-- Revoke Confirmation Modal -->
        <Teleport to="body">
            <div v-if="showRevokeModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4 z-50">
                <div class="bg-white rounded-lg max-w-md w-full p-5 shadow-2xl border border-slate-200 space-y-4">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-lg bg-rose-50 border border-rose-100 text-rose-600 flex items-center justify-center shrink-0">
                            <TrashIcon class="w-5 h-5 stroke-[1.75]" />
                        </div>
                        <div class="flex-1">
                            <h3 class="text-sm font-black text-slate-900">Revoke Remote Access?</h3>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Are you sure you want to revoke remote access for user <strong class="text-slate-900 font-mono">{{ targetRevoke?.user }}</strong> from host <strong class="text-rose-600 font-mono">{{ targetRevoke?.host }}</strong>? External applications from this IP will be disconnected immediately.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                        <button 
                            @click="showRevokeModal = false; targetRevoke = null" 
                            type="button" 
                            :disabled="revoking"
                            class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-[3px] text-xs font-bold transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button 
                            @click="executeRevoke" 
                            type="button"
                            :disabled="revoking"
                            class="px-4 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-[3px] text-xs font-bold shadow-2xs transition cursor-pointer flex items-center gap-1.5 disabled:opacity-50"
                        >
                            <TrashIcon class="w-3.5 h-3.5" />
                            <span>{{ revoking ? 'Revoking...' : 'Revoke Access' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

    </AuthenticatedLayout>
</template>
