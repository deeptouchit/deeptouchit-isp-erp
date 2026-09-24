<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import { 
    CommandLineIcon, 
    PlusIcon, 
    KeyIcon, 
    TrashIcon, 
    ClipboardDocumentIcon,
    CheckIcon,
    ServerIcon,
    ShieldCheckIcon,
    ArrowPathIcon,
    XMarkIcon,
    ExclamationTriangleIcon,
    InformationCircleIcon,
    EyeIcon,
    ArrowDownTrayIcon,
    ComputerDesktopIcon,
    SparklesIcon,
    LockClosedIcon,
    CodeBracketIcon,
    PlayIcon,
    ChevronRightIcon,
    StopIcon,
    ArrowTrendingUpIcon,
    GlobeAltIcon,
    DocumentDuplicateIcon
} from '@heroicons/vue/24/outline'
import axios from 'axios'

const props = defineProps({
    username: {
        type: String,
        default: 'client'
    },
    domain: {
        type: String,
        default: 'deeptouchit.com'
    },
    document_root: {
        type: String,
        default: '/var/www/vhosts/client'
    },
    subscriptions: {
        type: Array,
        default: () => []
    },
    currentSubscriptionId: {
        type: Number,
        default: null
    },
    keys: {
        type: Array,
        default: () => []
    },
    sshEnabled: {
        type: Boolean,
        default: true
    },
    serverInfo: {
        type: Object,
        default: () => ({
            host: '103.59.177.138',
            port: 22,
            username: 'client',
            connection_command: 'ssh client@103.59.177.138 -p 22',
            status: 'active',
            shell: '/bin/bash (Jailed Shell Environment)',
            openssh_config: '',
            vscode_config: ''
        })
    },
    sessionFlash: {
        type: Object,
        default: () => ({})
    }
})

// Active Subscription state
const selectedSubId = ref(props.currentSubscriptionId || props.subscriptions[0]?.id || null)
const onSubscriptionChange = () => {
    router.get(route('advanced.ssh'), { subscription_id: selectedSubId.value }, { preserveState: false })
}

// Active Tab
const activeTab = ref('keys') // 'keys' | 'terminal' | 'snippets' | 'security'

// Modals
const showAddModal = ref(false)
const showGenerateModal = ref(false)
const showViewKeyModal = ref(false)
const showDeleteModal = ref(false)
const showPrivateKeyModal = ref(false)
const showPasswordModal = ref(false)

const viewingKey = ref(null)
const deletingKey = ref(null)
const privateKeyContent = ref(props.sessionFlash?.generated_private_key || '')
const privateKeyFilename = ref(props.sessionFlash?.generated_key_name || 'id_ed25519.pem')

if (props.sessionFlash?.generated_private_key) {
    showPrivateKeyModal.value = true
}

// Password Form
const passwordForm = useForm({
    subscription_id: selectedSubId.value,
    password: ''
})

const generateRandomPassword = () => {
    const chars = 'abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789!@#$%&*'
    let pwd = ''
    for (let i = 0; i < 16; i++) {
        pwd += chars.charAt(Math.floor(Math.random() * chars.length))
    }
    return pwd
}

const fillRandomPassword = () => {
    passwordForm.password = generateRandomPassword()
}

const submitPasswordChange = () => {
    passwordForm.subscription_id = selectedSubId.value
    passwordForm.post(route('advanced.ssh.change-password'), {
        preserveScroll: true,
        onSuccess: () => {
            showPasswordModal.value = false
            passwordForm.reset()
        }
    })
}

// Copy feedback
const copiedField = ref(null)
const copyToClipboard = (text, fieldName) => {
    navigator.clipboard.writeText(text)
    copiedField.value = fieldName
    setTimeout(() => {
        copiedField.value = null
    }, 2000)
}

// Download Generated Private Key helper
const downloadPrivateKey = (content, filename) => {
    const blob = new Blob([content], { type: 'application/x-pem-file' })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = filename || 'id_ed25519.pem'
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    URL.revokeObjectURL(url)
}

// Add Key Form
const addForm = useForm({
    subscription_id: selectedSubId.value,
    label: '',
    public_key: '',
})

const submitAddKey = () => {
    addForm.subscription_id = selectedSubId.value
    addForm.post(route('advanced.ssh.add-key'), {
        preserveScroll: true,
        onSuccess: () => {
            showAddModal.value = false
            addForm.reset()
        }
    })
}

// Generate Key Form
const generateForm = useForm({
    subscription_id: selectedSubId.value,
    label: 'key_' + props.username + '_' + (props.keys.length + 1),
    key_type: 'ed25519',
    passphrase: '',
})

const submitGenerateKey = () => {
    generateForm.subscription_id = selectedSubId.value
    generateForm.post(route('advanced.ssh.generate-key'), {
        preserveScroll: true,
        onSuccess: () => {
            showGenerateModal.value = false
            generateForm.reset({
                subscription_id: selectedSubId.value,
                label: 'key_' + props.username + '_' + (props.keys.length + 2),
                key_type: 'ed25519',
                passphrase: '',
            })
        }
    })
}

// Delete / Revoke Key
const confirmDelete = (k) => {
    deletingKey.value = k
    showDeleteModal.value = true
}

const executeDelete = () => {
    if (!deletingKey.value) return
    router.delete(route('advanced.ssh.delete-key'), {
        data: { 
            subscription_id: selectedSubId.value,
            full_key: deletingKey.value.full_key 
        },
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false
            deletingKey.value = null
        }
    })
}

// Toggle Access
const toggleSshLoading = ref(false)
const toggleAccess = () => {
    toggleSshLoading.value = true
    router.post(route('advanced.ssh.toggle-access'), {
        subscription_id: selectedSubId.value
    }, {
        preserveScroll: true,
        onFinish: () => {
            toggleSshLoading.value = false
        }
    })
}

// ==========================================
// INTERACTIVE WEB TERMINAL LOGIC
// ==========================================
const terminalInput = ref('')
const terminalHistory = ref([
    {
        type: 'system',
        text: `DeepTouch Cloud Jailed Shell v2.4 (x86_64-linux)\nLogged in as ${props.username}@${props.domain}\nWorking Directory: ${props.document_root}\nType 'help' or click presets below to execute commands.`
    }
])
const terminalRunning = ref(false)
const commandHistoryIndex = ref(-1)
const commandHistoryList = ref([])
const terminalOutputRef = ref(null)

const scrollToBottom = () => {
    nextTick(() => {
        if (terminalOutputRef.value) {
            terminalOutputRef.value.scrollTop = terminalOutputRef.value.scrollHeight
        }
    })
}

const runCommand = async (cmdToRun = null) => {
    const cmd = (cmdToRun || terminalInput.value).trim()
    if (!cmd) return

    terminalHistory.value.push({
        type: 'command',
        text: cmd,
        timestamp: new Date().toLocaleTimeString()
    })

    if (!cmdToRun) {
        commandHistoryList.value.unshift(cmd)
        commandHistoryIndex.value = -1
        terminalInput.value = ''
    }

    scrollToBottom()

    if (cmd.toLowerCase() === 'clear') {
        terminalHistory.value = []
        return
    }

    if (cmd.toLowerCase() === 'help') {
        terminalHistory.value.push({
            type: 'output',
            text: `Common commands available in your jailed environment:\n  - php artisan <cmd>     (Run Laravel artisan CLI)\n  - composer <cmd>        (Dependency management)\n  - git status / log      (Version control status)\n  - npm run / node -v     (Node.js tools)\n  - ls -lah               (List directory contents)\n  - pwd                   (Print working directory)\n  - clear                 (Clear terminal screen)`,
            exit_code: 0
        })
        scrollToBottom()
        return
    }

    terminalRunning.value = true
    try {
        const res = await axios.post(route('advanced.ssh.run-command'), {
            command: cmd,
            subscription_id: selectedSubId.value
        })

        terminalHistory.value.push({
            type: 'output',
            text: res.data.output || '(No output)',
            exit_code: res.data.exit_code,
            duration_ms: res.data.duration_ms,
            working_dir: res.data.working_dir
        })
    } catch (err) {
        terminalHistory.value.push({
            type: 'output',
            text: err.response?.data?.output || err.response?.data?.error || err.message,
            exit_code: err.response?.data?.exit_code || 1,
            is_error: true
        })
    } finally {
        terminalRunning.value = false
        scrollToBottom()
    }
}

const handleKeyDown = (e) => {
    if (e.key === 'ArrowUp') {
        e.preventDefault()
        if (commandHistoryList.value.length > commandHistoryIndex.value + 1) {
            commandHistoryIndex.value++
            terminalInput.value = commandHistoryList.value[commandHistoryIndex.value]
        }
    } else if (e.key === 'ArrowDown') {
        e.preventDefault()
        if (commandHistoryIndex.value > 0) {
            commandHistoryIndex.value--
            terminalInput.value = commandHistoryList.value[commandHistoryIndex.value]
        } else if (commandHistoryIndex.value === 0) {
            commandHistoryIndex.value = -1
            terminalInput.value = ''
        }
    }
}

// Preset Quick Commands
const quickPresets = [
    { label: 'PHP Version', cmd: 'php -v' },
    { label: 'Artisan Status', cmd: 'php artisan --version' },
    { label: 'Composer Info', cmd: 'composer -v' },
    { label: 'Git Status', cmd: 'git status' },
    { label: 'Directory List', cmd: 'ls -lah' },
    { label: 'Current Dir', cmd: 'pwd' },
    { label: 'Disk Usage', cmd: 'du -sh *' },
]

// Snippet Language Selector
const snippetTab = ref('openssh') // 'openssh' | 'vscode' | 'powershell' | 'putty'

const powershellCmd = computed(() => {
    return `ssh -i ~/.ssh/id_ed25519 ${props.username}@${props.serverInfo.host} -p ${props.serverInfo.port}`
})

const macLinuxCmd = computed(() => {
    return `chmod 600 ~/.ssh/id_ed25519 && ssh -i ~/.ssh/id_ed25519 ${props.username}@${props.serverInfo.host} -p ${props.serverInfo.port}`
})
</script>

<template>
    <Head title="SSH & Web Terminal Access Hub" />

    <AuthenticatedLayout>
        <div class="space-y-6 max-w-7xl mx-auto pb-12">
            
            <!-- 1. Header Bar with Subscription Switcher & Quick Actions -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-5 rounded-xl border border-slate-200 shadow-xs">
                <div>
                    <div class="flex items-center gap-2">
                        <div class="w-9 h-9 rounded-lg bg-slate-900 flex items-center justify-center text-white shadow-xs">
                            <CommandLineIcon class="w-5 h-5 text-emerald-400" />
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h1 class="text-lg font-bold text-slate-900 tracking-tight">SSH & Web Terminal Hub</h1>
                                <span 
                                    v-if="sshEnabled" 
                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                    <span>Active (Port {{ serverInfo.port }})</span>
                                </span>
                                <span 
                                    v-else 
                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-rose-50 text-rose-700 border border-rose-200"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                    <span>Locked / Disabled</span>
                                </span>
                            </div>
                            <p class="text-xs text-slate-500">
                                Enterprise public-key authentication, jailed environment access & interactive web console.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
                    <!-- Subscription Selector -->
                    <div v-if="subscriptions.length > 1" class="flex items-center gap-1.5 bg-slate-50 px-2.5 py-1.5 rounded-lg border border-slate-200">
                        <GlobeAltIcon class="w-4 h-4 text-slate-400" />
                        <select 
                            v-model="selectedSubId" 
                            @change="onSubscriptionChange"
                            class="bg-transparent border-0 text-xs font-bold text-slate-800 focus:ring-0 p-0 cursor-pointer"
                        >
                            <option v-for="sub in subscriptions" :key="sub.id" :value="sub.id">
                                {{ sub.domain }} ({{ sub.username }})
                            </option>
                        </select>
                    </div>

                    <!-- Toggle Access Button -->
                    <button 
                        @click="toggleAccess"
                        :disabled="toggleSshLoading"
                        class="px-3 py-1.5 rounded-lg text-xs font-bold border transition cursor-pointer flex items-center gap-1.5 disabled:opacity-50"
                        :class="sshEnabled 
                            ? 'bg-amber-50 hover:bg-amber-100 text-amber-800 border-amber-200' 
                            : 'bg-emerald-600 hover:bg-emerald-700 text-white border-emerald-600 shadow-xs'"
                    >
                        <LockClosedIcon v-if="sshEnabled" class="w-3.5 h-3.5" />
                        <ShieldCheckIcon v-else class="w-3.5 h-3.5" />
                        <span>{{ toggleSshLoading ? 'Updating...' : (sshEnabled ? 'Disable Access' : 'Enable Access') }}</span>
                    </button>

                    <!-- Update Password Button -->
                    <button 
                        @click="showPasswordModal = true"
                        class="px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-800 rounded-lg text-xs font-bold border border-slate-200 shadow-2xs transition cursor-pointer flex items-center gap-1.5"
                    >
                        <KeyIcon class="w-3.5 h-3.5 text-slate-600" />
                        <span>SSH Password</span>
                    </button>

                    <!-- Add Key Button -->
                    <button 
                        @click="showAddModal = true"
                        class="px-3.5 py-1.5 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-xs font-bold shadow-xs transition cursor-pointer flex items-center gap-1.5"
                    >
                        <PlusIcon class="w-3.5 h-3.5" />
                        <span>Import Public Key</span>
                    </button>

                    <!-- Generate Key Button -->
                    <button 
                        @click="showGenerateModal = true"
                        class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-bold shadow-xs transition cursor-pointer flex items-center gap-1.5"
                    >
                        <SparklesIcon class="w-3.5 h-3.5 text-blue-200" />
                        <span>Generate Key Pair</span>
                    </button>
                </div>
            </div>

            <!-- 2. Hero Telemetry Dashboard -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                
                <!-- Server IP & Host -->
                <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">Server Host / IPv4</span>
                        <div class="flex items-center gap-1.5 mt-1 font-mono font-bold text-slate-900 text-sm">
                            <span>{{ serverInfo.host }}</span>
                            <button 
                                @click="copyToClipboard(serverInfo.host, 'host')" 
                                class="text-slate-400 hover:text-blue-600 cursor-pointer"
                                title="Copy Host"
                            >
                                <CheckIcon v-if="copiedField === 'host'" class="w-3.5 h-3.5 text-emerald-600" />
                                <ClipboardDocumentIcon v-else class="w-3.5 h-3.5" />
                            </button>
                        </div>
                        <span class="text-[10px] text-slate-400 mt-0.5 block">Default SSH Port: {{ serverInfo.port }}</span>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                        <ServerIcon class="w-5 h-5" />
                    </div>
                </div>

                <!-- Username & Jailed Env -->
                <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">SSH Username</span>
                        <div class="flex items-center gap-1.5 mt-1 font-mono font-bold text-slate-900 text-sm">
                            <span>{{ username }}</span>
                            <button 
                                @click="copyToClipboard(username, 'user')" 
                                class="text-slate-400 hover:text-blue-600 cursor-pointer"
                                title="Copy Username"
                            >
                                <CheckIcon v-if="copiedField === 'user'" class="w-3.5 h-3.5 text-emerald-600" />
                                <ClipboardDocumentIcon v-else class="w-3.5 h-3.5" />
                            </button>
                        </div>
                        <span class="text-[10px] text-emerald-600 font-bold mt-0.5 block">Jailed Chroot Protection</span>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                        <ShieldCheckIcon class="w-5 h-5" />
                    </div>
                </div>

                <!-- Authorized Keys -->
                <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">Authorized Keys</span>
                        <div class="mt-1 font-mono font-bold text-slate-900 text-base">
                            {{ keys.length }} <span class="text-xs font-normal text-slate-500">Active</span>
                        </div>
                        <span class="text-[10px] text-slate-400 mt-0.5 block">ED25519 & RSA 4096-bit</span>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center shrink-0">
                        <KeyIcon class="w-5 h-5" />
                    </div>
                </div>

                <!-- Quick Connection One-Liner -->
                <div class="bg-slate-900 text-white p-4 rounded-xl shadow-xs flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-[10.5px] font-bold text-slate-400 uppercase tracking-wider">Quick Command</span>
                        <button 
                            @click="copyToClipboard(serverInfo.connection_command, 'cmd')" 
                            class="text-xs font-bold text-emerald-400 hover:text-emerald-300 flex items-center gap-1 cursor-pointer"
                        >
                            <CheckIcon v-if="copiedField === 'cmd'" class="w-3 h-3" />
                            <ClipboardDocumentIcon v-else class="w-3 h-3" />
                            <span>{{ copiedField === 'cmd' ? 'Copied' : 'Copy' }}</span>
                        </button>
                    </div>
                    <div class="font-mono text-xs text-emerald-300 truncate bg-slate-800/80 px-2 py-1.5 rounded-md mt-2 border border-slate-700">
                        {{ serverInfo.connection_command }}
                    </div>
                </div>

            </div>

            <!-- 3. Navigation Tabs -->
            <div class="flex border-b border-slate-200 gap-1 bg-white px-4 pt-2 rounded-t-xl">
                <button 
                    @click="activeTab = 'keys'"
                    class="px-4 py-2.5 text-xs font-bold border-b-2 flex items-center gap-2 transition cursor-pointer"
                    :class="activeTab === 'keys' 
                        ? 'border-blue-600 text-blue-600' 
                        : 'border-transparent text-slate-500 hover:text-slate-900 hover:border-slate-300'"
                >
                    <KeyIcon class="w-4 h-4" />
                    <span>Authorized Keys ({{ keys.length }})</span>
                </button>

                <button 
                    @click="activeTab = 'terminal'"
                    class="px-4 py-2.5 text-xs font-bold border-b-2 flex items-center gap-2 transition cursor-pointer"
                    :class="activeTab === 'terminal' 
                        ? 'border-blue-600 text-blue-600' 
                        : 'border-transparent text-slate-500 hover:text-slate-900 hover:border-slate-300'"
                >
                    <CommandLineIcon class="w-4 h-4 text-emerald-600" />
                    <span>Web Console & CLI Runner</span>
                    <span class="px-1.5 py-0.2 bg-emerald-100 text-emerald-800 text-[10px] rounded-full font-bold">Interactive</span>
                </button>

                <button 
                    @click="activeTab = 'snippets'"
                    class="px-4 py-2.5 text-xs font-bold border-b-2 flex items-center gap-2 transition cursor-pointer"
                    :class="activeTab === 'snippets' 
                        ? 'border-blue-600 text-blue-600' 
                        : 'border-transparent text-slate-500 hover:text-slate-900 hover:border-slate-300'"
                >
                    <CodeBracketIcon class="w-4 h-4" />
                    <span>IDE & Client Snippets</span>
                </button>

                <button 
                    @click="activeTab = 'security'"
                    class="px-4 py-2.5 text-xs font-bold border-b-2 flex items-center gap-2 transition cursor-pointer"
                    :class="activeTab === 'security' 
                        ? 'border-blue-600 text-blue-600' 
                        : 'border-transparent text-slate-500 hover:text-slate-900 hover:border-slate-300'"
                >
                    <ShieldCheckIcon class="w-4 h-4" />
                    <span>Security & Hardening</span>
                </button>
            </div>

            <!-- ==========================================
                 TAB 1: AUTHORIZED KEYS
            ========================================== -->
            <div v-if="activeTab === 'keys'" class="space-y-4">
                
                <div class="bg-white rounded-b-xl rounded-t-none border border-t-0 border-slate-200 overflow-hidden shadow-xs">
                    
                    <div v-if="keys.length === 0" class="p-16 text-center space-y-3">
                        <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto">
                            <KeyIcon class="w-6 h-6" />
                        </div>
                        <h3 class="text-sm font-bold text-slate-800">No SSH Public Keys Authorized</h3>
                        <p class="text-xs text-slate-500 max-w-sm mx-auto">
                            To connect securely via SSH or SFTP without passwords, import your public key or generate a new key pair.
                        </p>
                        <div class="flex items-center justify-center gap-2 pt-2">
                            <button 
                                @click="showAddModal = true" 
                                class="px-3.5 py-1.5 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-xs font-bold shadow-xs cursor-pointer flex items-center gap-1.5"
                            >
                                <PlusIcon class="w-3.5 h-3.5" />
                                <span>Import Key</span>
                            </button>
                            <button 
                                @click="showGenerateModal = true" 
                                class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-bold shadow-xs cursor-pointer flex items-center gap-1.5"
                            >
                                <SparklesIcon class="w-3.5 h-3.5" />
                                <span>Generate Pair</span>
                            </button>
                        </div>
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-slate-50/80 border-b border-slate-200 text-slate-500 font-bold uppercase text-[10.5px] tracking-wider">
                                    <th class="py-3 px-4">Key Label & Algorithm</th>
                                    <th class="py-3 px-4">SHA256 Fingerprint</th>
                                    <th class="py-3 px-4">Bit Length</th>
                                    <th class="py-3 px-4">Key Preview</th>
                                    <th class="py-3 px-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="k in keys" :key="k.id" class="hover:bg-slate-50/60 transition">
                                    
                                    <!-- Label & Type Badge -->
                                    <td class="py-3.5 px-4">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-7 h-7 rounded-md bg-slate-100 flex items-center justify-center text-slate-600 shrink-0">
                                                <KeyIcon class="w-4 h-4" />
                                            </div>
                                            <div>
                                                <span class="font-bold text-slate-900 block">{{ k.label }}</span>
                                                <span 
                                                    class="inline-block px-1.5 py-0.2 rounded text-[10px] font-mono font-bold uppercase mt-0.5"
                                                    :class="k.type.includes('ed25519') 
                                                        ? 'bg-purple-100 text-purple-800' 
                                                        : 'bg-blue-100 text-blue-800'"
                                                >
                                                    {{ k.type }}
                                                </span>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Fingerprint -->
                                    <td class="py-3.5 px-4 font-mono text-[11px] text-slate-600">
                                        <div class="flex items-center gap-1.5">
                                            <span class="truncate max-w-[220px]" :title="k.fingerprint">{{ k.fingerprint }}</span>
                                            <button 
                                                @click="copyToClipboard(k.fingerprint, 'fp_' + k.id)" 
                                                class="text-slate-400 hover:text-slate-700 cursor-pointer shrink-0"
                                                title="Copy Fingerprint"
                                            >
                                                <CheckIcon v-if="copiedField === 'fp_' + k.id" class="w-3.5 h-3.5 text-emerald-600" />
                                                <ClipboardDocumentIcon v-else class="w-3.5 h-3.5" />
                                            </button>
                                        </div>
                                    </td>

                                    <!-- Bit Length -->
                                    <td class="py-3.5 px-4 text-slate-600 font-bold text-[11px]">
                                        <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 text-[10.5px]">
                                            {{ k.bit_length }}
                                        </span>
                                    </td>

                                    <!-- Key Preview -->
                                    <td class="py-3.5 px-4 font-mono text-[11px] text-slate-500">
                                        {{ k.key_preview }}
                                    </td>

                                    <!-- Actions -->
                                    <td class="py-3.5 px-4 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <button 
                                                @click="viewingKey = k; showViewKeyModal = true" 
                                                class="p-1.5 hover:bg-slate-100 text-slate-600 rounded-md transition cursor-pointer"
                                                title="View Full Public Key"
                                            >
                                                <EyeIcon class="w-4 h-4" />
                                            </button>

                                            <button 
                                                @click="copyToClipboard(k.full_key, 'key_' + k.id)" 
                                                class="p-1.5 hover:bg-slate-100 text-slate-600 rounded-md transition cursor-pointer"
                                                title="Copy Full Key"
                                            >
                                                <CheckIcon v-if="copiedField === 'key_' + k.id" class="w-4 h-4 text-emerald-600" />
                                                <ClipboardDocumentIcon v-else class="w-4 h-4" />
                                            </button>

                                            <button 
                                                @click="confirmDelete(k)" 
                                                class="p-1.5 hover:bg-rose-50 text-slate-400 hover:text-rose-600 rounded-md transition cursor-pointer"
                                                title="Revoke / Delete Key"
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

            <!-- ==========================================
                 TAB 2: INTERACTIVE WEB TERMINAL & RUNNER
            ========================================== -->
            <div v-if="activeTab === 'terminal'" class="space-y-4">
                
                <!-- Quick Preset Action Bar -->
                <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-xs flex flex-wrap items-center justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-1.5">
                        <span class="text-[11px] font-bold text-slate-500 uppercase mr-1">Quick Presets:</span>
                        <button 
                            v-for="p in quickPresets" 
                            :key="p.cmd"
                            @click="runCommand(p.cmd)"
                            :disabled="terminalRunning"
                            class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-800 rounded-md text-[11px] font-mono font-bold transition cursor-pointer disabled:opacity-50 flex items-center gap-1"
                        >
                            <PlayIcon class="w-2.5 h-2.5 text-blue-600" />
                            <span>{{ p.label }}</span>
                        </button>
                    </div>

                    <div class="flex items-center gap-2">
                        <button 
                            @click="terminalHistory = []" 
                            class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-md text-xs font-bold transition cursor-pointer"
                        >
                            Clear Screen
                        </button>
                    </div>
                </div>

                <!-- High-Tech ANSI Terminal Box -->
                <div class="bg-slate-950 text-slate-100 rounded-xl border border-slate-800 shadow-xl overflow-hidden font-mono">
                    
                    <!-- Terminal Window Header -->
                    <div class="bg-slate-900/90 px-4 py-2.5 border-b border-slate-800 flex items-center justify-between text-xs">
                        <div class="flex items-center gap-2">
                            <div class="flex items-center gap-1.5">
                                <span class="w-3 h-3 rounded-full bg-rose-500/80 inline-block"></span>
                                <span class="w-3 h-3 rounded-full bg-amber-500/80 inline-block"></span>
                                <span class="w-3 h-3 rounded-full bg-emerald-500/80 inline-block"></span>
                            </div>
                            <span class="text-slate-400 font-bold ml-2 text-[11px]">
                                {{ username }}@{{ domain }}:~/public_html (Jailed Sandboxed Environment)
                            </span>
                        </div>

                        <div class="flex items-center gap-3 text-[11px] text-slate-400">
                            <span v-if="terminalRunning" class="flex items-center gap-1.5 text-amber-400 font-bold">
                                <ArrowPathIcon class="w-3.5 h-3.5 animate-spin" />
                                <span>Executing...</span>
                            </span>
                            <span v-else class="text-emerald-400 font-bold flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                <span>Online</span>
                            </span>
                        </div>
                    </div>

                    <!-- Terminal Output Screen -->
                    <div 
                        ref="terminalOutputRef"
                        class="p-4 h-96 overflow-y-auto space-y-3 text-xs leading-relaxed selection:bg-blue-600 selection:text-white"
                    >
                        <div v-for="(item, idx) in terminalHistory" :key="idx" class="space-y-1">
                            
                            <!-- System Prompt -->
                            <div v-if="item.type === 'system'" class="text-slate-400 whitespace-pre-wrap">
                                {{ item.text }}
                            </div>

                            <!-- Command Prompt -->
                            <div v-if="item.type === 'command'" class="flex items-start gap-2 text-emerald-400 font-bold">
                                <span class="text-slate-500 select-none">[{{ item.timestamp }}]</span>
                                <span class="text-blue-400 select-none">{{ username }}@cloud:~$</span>
                                <span class="text-white">{{ item.text }}</span>
                            </div>

                            <!-- Command Output -->
                            <div v-if="item.type === 'output'" class="pl-4 border-l-2" :class="item.exit_code === 0 ? 'border-slate-700 text-slate-200' : 'border-rose-500 text-rose-300'">
                                <pre class="whitespace-pre-wrap font-mono text-[11.5px]">{{ item.text }}</pre>
                                <div v-if="item.duration_ms !== undefined" class="text-[10px] text-slate-500 mt-1 flex items-center gap-2">
                                    <span>Exit: {{ item.exit_code }}</span>
                                    <span>•</span>
                                    <span>{{ item.duration_ms }}ms</span>
                                </div>
                            </div>

                        </div>

                        <!-- Typing Cursor Placeholder while running -->
                        <div v-if="terminalRunning" class="flex items-center gap-2 text-slate-400">
                            <span class="animate-pulse">▍</span>
                        </div>
                    </div>

                    <!-- Interactive Command Input Bar -->
                    <form @submit.prevent="runCommand()" class="bg-slate-900 border-t border-slate-800 p-2.5 flex items-center gap-2">
                        <span class="text-emerald-400 font-bold text-xs pl-2 select-none">❯</span>
                        <input 
                            v-model="terminalInput"
                            @keydown="handleKeyDown"
                            :disabled="terminalRunning"
                            type="text" 
                            placeholder="Type artisan, composer, git, npm or unix command..."
                            class="w-full bg-transparent border-0 text-white font-mono text-xs focus:ring-0 placeholder:text-slate-600 disabled:opacity-50"
                        />
                        <button 
                            type="submit" 
                            :disabled="terminalRunning || !terminalInput.trim()"
                            class="px-3 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded text-xs font-bold transition cursor-pointer disabled:opacity-30 shrink-0 flex items-center gap-1"
                        >
                            <PlayIcon class="w-3 h-3" />
                            <span>Run</span>
                        </button>
                    </form>

                </div>

            </div>

            <!-- ==========================================
                 TAB 3: IDE & CLIENT CONFIG SNIPPETS
            ========================================== -->
            <div v-if="activeTab === 'snippets'" class="space-y-4">
                
                <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs space-y-4">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">1-Click Developer Connection Snippets</h3>
                        <p class="text-xs text-slate-500">
                            Ready-to-use configuration files and commands for VS Code, OpenSSH, PuTTY, and Terminal.
                        </p>
                    </div>

                    <!-- Subtabs -->
                    <div class="flex border-b border-slate-200 gap-2">
                        <button 
                            @click="snippetTab = 'openssh'" 
                            class="px-3 py-1.5 text-xs font-bold border-b-2 cursor-pointer transition"
                            :class="snippetTab === 'openssh' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500'"
                        >
                            OpenSSH (~/.ssh/config)
                        </button>
                        <button 
                            @click="snippetTab = 'vscode'" 
                            class="px-3 py-1.5 text-xs font-bold border-b-2 cursor-pointer transition"
                            :class="snippetTab === 'vscode' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500'"
                        >
                            VS Code Remote-SSH
                        </button>
                        <button 
                            @click="snippetTab = 'powershell'" 
                            class="px-3 py-1.5 text-xs font-bold border-b-2 cursor-pointer transition"
                            :class="snippetTab === 'powershell' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500'"
                        >
                            Windows PowerShell / Mac / Linux
                        </button>
                    </div>

                    <!-- 1. OpenSSH ~/.ssh/config -->
                    <div v-if="snippetTab === 'openssh'" class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-slate-600">Append this to <code class="font-mono text-slate-900 font-bold">~/.ssh/config</code> on your local machine:</span>
                            <button 
                                @click="copyToClipboard(serverInfo.openssh_config, 'snip_ssh')" 
                                class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded text-xs font-bold flex items-center gap-1 cursor-pointer"
                            >
                                <CheckIcon v-if="copiedField === 'snip_ssh'" class="w-3.5 h-3.5 text-emerald-600" />
                                <ClipboardDocumentIcon v-else class="w-3.5 h-3.5" />
                                <span>{{ copiedField === 'snip_ssh' ? 'Copied' : 'Copy Config' }}</span>
                            </button>
                        </div>
                        <pre class="bg-slate-900 text-emerald-300 p-3.5 rounded-lg text-xs font-mono overflow-x-auto">{{ serverInfo.openssh_config }}</pre>
                        <p class="text-[11px] text-slate-500">Then simply run: <code class="font-mono font-bold text-slate-800">ssh {{ domain }}</code></p>
                    </div>

                    <!-- 2. VS Code Remote SSH -->
                    <div v-if="snippetTab === 'vscode'" class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-slate-600">VS Code Remote-SSH configuration payload:</span>
                            <button 
                                @click="copyToClipboard(serverInfo.vscode_config, 'snip_vsc')" 
                                class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded text-xs font-bold flex items-center gap-1 cursor-pointer"
                            >
                                <CheckIcon v-if="copiedField === 'snip_vsc'" class="w-3.5 h-3.5 text-emerald-600" />
                                <ClipboardDocumentIcon v-else class="w-3.5 h-3.5" />
                                <span>{{ copiedField === 'snip_vsc' ? 'Copied' : 'Copy JSON' }}</span>
                            </button>
                        </div>
                        <pre class="bg-slate-900 text-blue-300 p-3.5 rounded-lg text-xs font-mono overflow-x-auto">{{ serverInfo.vscode_config }}</pre>
                    </div>

                    <!-- 3. PowerShell / Terminal Command -->
                    <div v-if="snippetTab === 'powershell'" class="space-y-3">
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs font-bold text-slate-700">Windows PowerShell:</span>
                                <button 
                                    @click="copyToClipboard(powershellCmd, 'snip_ps')" 
                                    class="text-xs font-bold text-blue-600 hover:underline cursor-pointer"
                                >
                                    {{ copiedField === 'snip_ps' ? 'Copied!' : 'Copy' }}
                                </button>
                            </div>
                            <pre class="bg-slate-900 text-slate-100 p-3 rounded-lg text-xs font-mono">{{ powershellCmd }}</pre>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs font-bold text-slate-700">macOS / Linux Terminal:</span>
                                <button 
                                    @click="copyToClipboard(macLinuxCmd, 'snip_mac')" 
                                    class="text-xs font-bold text-blue-600 hover:underline cursor-pointer"
                                >
                                    {{ copiedField === 'snip_mac' ? 'Copied!' : 'Copy' }}
                                </button>
                            </div>
                            <pre class="bg-slate-900 text-slate-100 p-3 rounded-lg text-xs font-mono">{{ macLinuxCmd }}</pre>
                        </div>
                    </div>

                </div>

            </div>

            <!-- ==========================================
                 TAB 4: SECURITY & HARDENING
            ========================================== -->
            <div v-if="activeTab === 'security'" class="space-y-4">
                
                <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs space-y-4">
                    <div class="flex items-center gap-2">
                        <ShieldCheckIcon class="w-5 h-5 text-emerald-600" />
                        <h3 class="text-sm font-bold text-slate-900">Security Architecture & Audit Status</h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                        <div class="p-3.5 rounded-lg border border-slate-200 bg-slate-50/60 space-y-1.5">
                            <span class="font-bold text-slate-900 flex items-center gap-1.5">
                                <CheckIcon class="w-4 h-4 text-emerald-600" />
                                <span>Public Key Authentication Only</span>
                            </span>
                            <p class="text-slate-500">
                                Password-based SSH logins are disabled or strictly audited. Only authorized public keys stored in your isolated `.ssh/authorized_keys` are allowed.
                            </p>
                        </div>

                        <div class="p-3.5 rounded-lg border border-slate-200 bg-slate-50/60 space-y-1.5">
                            <span class="font-bold text-slate-900 flex items-center gap-1.5">
                                <CheckIcon class="w-4 h-4 text-emerald-600" />
                                <span>Jailed Chroot File Isolation</span>
                            </span>
                            <p class="text-slate-500">
                                Your SSH session is locked to your vhost home directory (<code class="font-mono text-slate-800">/var/www/vhosts/{{ username }}</code>). Other system users cannot be accessed.
                            </p>
                        </div>

                        <div class="p-3.5 rounded-lg border border-slate-200 bg-slate-50/60 space-y-1.5">
                            <span class="font-bold text-slate-900 flex items-center gap-1.5">
                                <CheckIcon class="w-4 h-4 text-emerald-600" />
                                <span>ED25519 Modern Cryptography</span>
                            </span>
                            <p class="text-slate-500">
                                High-speed EdDSA curve25519 keys are recommended for military-grade security and immune to side-channel timing attacks.
                            </p>
                        </div>

                        <div class="p-3.5 rounded-lg border border-slate-200 bg-slate-50/60 space-y-1.5">
                            <span class="font-bold text-slate-900 flex items-center gap-1.5">
                                <CheckIcon class="w-4 h-4 text-emerald-600" />
                                <span>Fail2ban & Rate Limiting</span>
                            </span>
                            <p class="text-slate-500">
                                Repeated failed authentication attempts trigger automatic firewall IP drops for 24 hours.
                            </p>
                        </div>
                    </div>
                </div>

            </div>

        </div>

        <!-- ==========================================
             MODALS
        ========================================== -->

        <!-- 1. Import Public Key Modal -->
        <div v-if="showAddModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-xl max-w-lg w-full p-5 shadow-2xl space-y-4 border border-slate-200">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="font-bold text-slate-900 text-sm flex items-center gap-1.5">
                        <KeyIcon class="w-4 h-4 text-blue-600" />
                        <span>Import OpenSSH Public Key</span>
                    </h3>
                    <button @click="showAddModal = false" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitAddKey" class="space-y-4 text-xs">
                    <div class="space-y-1.5">
                        <label class="block font-bold text-slate-700">Key Label / Comment</label>
                        <input 
                            v-model="addForm.label"
                            type="text" 
                            required 
                            placeholder="e.g. MacBook Pro M3 or Deployer Key"
                            class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 text-xs font-bold text-slate-900 focus:ring-1 focus:ring-blue-600 focus:border-blue-600"
                        />
                        <span v-if="addForm.errors.label" class="text-[11px] text-rose-600 font-bold block">{{ addForm.errors.label }}</span>
                    </div>

                    <div class="space-y-1.5">
                        <label class="block font-bold text-slate-700">Public Key Content</label>
                        <textarea 
                            v-model="addForm.public_key"
                            rows="5" 
                            required 
                            placeholder="ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAA... or ssh-rsa AAAAB3NzaC1yc2E..."
                            class="w-full bg-white border border-slate-300 rounded-lg p-3 text-xs font-mono text-slate-900 focus:ring-1 focus:ring-blue-600 focus:border-blue-600"
                        ></textarea>
                        <span v-if="addForm.errors.public_key" class="text-[11px] text-rose-600 font-bold block">{{ addForm.errors.public_key }}</span>
                    </div>

                    <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                        <button 
                            type="button" 
                            @click="showAddModal = false" 
                            class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button 
                            type="submit" 
                            :disabled="addForm.processing"
                            class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-bold shadow-xs transition cursor-pointer disabled:opacity-50"
                        >
                            {{ addForm.processing ? 'Authorizing...' : 'Authorize Key' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 2. Generate Key Pair Modal -->
        <div v-if="showGenerateModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-xl max-w-md w-full p-5 shadow-2xl space-y-4 border border-slate-200">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="font-bold text-slate-900 text-sm flex items-center gap-1.5">
                        <SparklesIcon class="w-4 h-4 text-blue-600" />
                        <span>Generate High-Security Key Pair</span>
                    </h3>
                    <button @click="showGenerateModal = false" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitGenerateKey" class="space-y-4 text-xs">
                    
                    <div class="space-y-1.5">
                        <label class="block font-bold text-slate-700">Key Name</label>
                        <input 
                            v-model="generateForm.label"
                            type="text" 
                            required 
                            class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 text-xs font-bold text-slate-900 focus:ring-1 focus:ring-blue-600 focus:border-blue-600 font-mono"
                        />
                    </div>

                    <!-- Algorithm Selection -->
                    <div class="space-y-1.5">
                        <label class="block font-bold text-slate-700">Algorithm Type</label>
                        <div class="grid grid-cols-2 gap-2">
                            <label 
                                class="border rounded-lg p-2.5 flex items-center gap-2 cursor-pointer transition"
                                :class="generateForm.key_type === 'ed25519' ? 'border-blue-600 bg-blue-50/50' : 'border-slate-200'"
                            >
                                <input type="radio" v-model="generateForm.key_type" value="ed25519" class="text-blue-600 focus:ring-0" />
                                <div>
                                    <span class="font-bold text-slate-900 block">ED25519</span>
                                    <span class="text-[10px] text-slate-500">Fast & Modern (Recommended)</span>
                                </div>
                            </label>

                            <label 
                                class="border rounded-lg p-2.5 flex items-center gap-2 cursor-pointer transition"
                                :class="generateForm.key_type === 'rsa' ? 'border-blue-600 bg-blue-50/50' : 'border-slate-200'"
                            >
                                <input type="radio" v-model="generateForm.key_type" value="rsa" class="text-blue-600 focus:ring-0" />
                                <div>
                                    <span class="font-bold text-slate-900 block">RSA 4096-bit</span>
                                    <span class="text-[10px] text-slate-500">Enterprise Legacy Standard</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Passphrase Optional -->
                    <div class="space-y-1.5">
                        <label class="block font-bold text-slate-700">Passphrase (Optional)</label>
                        <input 
                            v-model="generateForm.passphrase"
                            type="password" 
                            placeholder="Leave empty for passwordless login"
                            class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 text-xs text-slate-900 focus:ring-1 focus:ring-blue-600 focus:border-blue-600"
                        />
                    </div>

                    <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                        <button 
                            type="button" 
                            @click="showGenerateModal = false" 
                            class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button 
                            type="submit" 
                            :disabled="generateForm.processing"
                            class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-bold shadow-xs transition cursor-pointer disabled:opacity-50"
                        >
                            {{ generateForm.processing ? 'Generating...' : 'Generate & Download' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 3. 1-Time Private Key Download Modal -->
        <div v-if="showPrivateKeyModal" class="fixed inset-0 bg-slate-900/70 backdrop-blur-2xs flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-xl max-w-lg w-full p-6 shadow-2xl space-y-4 border border-slate-200">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center">
                            <CheckIcon class="w-5 h-5" />
                        </div>
                        <div>
                            <h3 class="font-bold text-slate-900 text-sm">Private Key Ready</h3>
                            <span class="text-[11px] text-amber-600 font-bold">Download now! This key will not be displayed again.</span>
                        </div>
                    </div>
                    <button @click="showPrivateKeyModal = false" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="space-y-2">
                    <pre class="bg-slate-950 text-emerald-400 p-3 rounded-lg text-[11px] font-mono overflow-y-auto max-h-48 border border-slate-800">{{ privateKeyContent }}</pre>
                </div>

                <div class="flex items-center justify-between pt-2 border-t border-slate-100">
                    <button 
                        @click="copyToClipboard(privateKeyContent, 'priv_key')" 
                        class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold flex items-center gap-1.5 cursor-pointer"
                    >
                        <CheckIcon v-if="copiedField === 'priv_key'" class="w-3.5 h-3.5 text-emerald-600" />
                        <ClipboardDocumentIcon v-else class="w-3.5 h-3.5" />
                        <span>{{ copiedField === 'priv_key' ? 'Copied' : 'Copy Key' }}</span>
                    </button>

                    <div class="flex gap-2">
                        <button 
                            @click="downloadPrivateKey(privateKeyContent, privateKeyFilename)" 
                            class="px-4 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold shadow-xs flex items-center gap-1.5 cursor-pointer"
                        >
                            <ArrowDownTrayIcon class="w-3.5 h-3.5" />
                            <span>Download .PEM File</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. View Full Public Key Modal -->
        <div v-if="showViewKeyModal && viewingKey" class="fixed inset-0 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-xl max-w-lg w-full p-5 shadow-2xl space-y-4 border border-slate-200">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h3 class="font-bold text-slate-900 text-sm">{{ viewingKey.label }}</h3>
                        <span class="text-[11px] font-mono text-slate-500">{{ viewingKey.type }} • {{ viewingKey.bit_length }}</span>
                    </div>
                    <button @click="showViewKeyModal = false" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="space-y-1.5">
                    <label class="font-bold text-slate-700 text-xs">Public Key</label>
                    <textarea 
                        readonly 
                        rows="6" 
                        class="w-full bg-slate-50 border border-slate-200 rounded-lg p-3 text-xs font-mono text-slate-800"
                    >{{ viewingKey.full_key }}</textarea>
                </div>

                <div class="space-y-1">
                    <label class="font-bold text-slate-700 text-xs">SHA256 Fingerprint</label>
                    <div class="font-mono text-xs text-slate-600 bg-slate-100 px-2.5 py-1.5 rounded-lg select-all">
                        {{ viewingKey.fingerprint }}
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                    <button 
                        @click="copyToClipboard(viewingKey.full_key, 'modal_key')" 
                        class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-bold shadow-xs flex items-center gap-1.5 cursor-pointer"
                    >
                        <CheckIcon v-if="copiedField === 'modal_key'" class="w-3.5 h-3.5" />
                        <ClipboardDocumentIcon v-else class="w-3.5 h-3.5" />
                        <span>{{ copiedField === 'modal_key' ? 'Copied!' : 'Copy Key' }}</span>
                    </button>
                    <button 
                        @click="showViewKeyModal = false" 
                        class="px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold cursor-pointer"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>

        <!-- 5. Delete Confirmation Modal -->
        <div v-if="showDeleteModal && deletingKey" class="fixed inset-0 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-xl max-w-sm w-full p-5 shadow-2xl space-y-4 border border-slate-200">
                <div class="flex items-center gap-3 text-rose-600">
                    <div class="w-10 h-10 rounded-full bg-rose-50 flex items-center justify-center shrink-0">
                        <ExclamationTriangleIcon class="w-6 h-6" />
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-900 text-sm">Revoke SSH Key?</h3>
                        <span class="text-xs text-slate-500">This key will no longer have access.</span>
                    </div>
                </div>

                <p class="text-xs text-slate-600">
                    Are you sure you want to revoke <strong class="text-slate-900">{{ deletingKey.label }}</strong>? Devices using this key will immediately lose access.
                </p>

                <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                    <button 
                        @click="showDeleteModal = false" 
                        class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold cursor-pointer"
                    >
                        Cancel
                    </button>
                    <button 
                        @click="executeDelete" 
                        class="px-4 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-xs font-bold shadow-xs cursor-pointer"
                    >
                        Revoke Key
                    </button>
                </div>
            </div>
        </div>

        <!-- 6. Change SSH Password Modal -->
        <div v-if="showPasswordModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-xl max-w-sm w-full p-5 shadow-2xl space-y-4 border border-slate-200">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="font-bold text-slate-900 text-sm flex items-center gap-1.5">
                        <KeyIcon class="w-4 h-4 text-blue-600" />
                        <span>Update SSH / SFTP Password</span>
                    </h3>
                    <button @click="showPasswordModal = false" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitPasswordChange" class="space-y-4 text-xs">
                    <div>
                        <span class="text-[11px] text-slate-500 block mb-1">Target Account: <strong class="text-slate-900 font-mono">{{ username }}</strong></span>
                    </div>

                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <label class="font-bold text-slate-700">New Password</label>
                            <button 
                                type="button" 
                                @click="fillRandomPassword" 
                                class="text-[10.5px] font-bold text-blue-600 hover:underline cursor-pointer flex items-center gap-1"
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
                            class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 text-xs font-mono font-bold text-slate-900 focus:ring-1 focus:ring-blue-600 focus:border-blue-600"
                        />
                        <span v-if="passwordForm.errors.password" class="text-[11px] text-rose-600 font-bold block">{{ passwordForm.errors.password }}</span>
                    </div>

                    <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                        <button 
                            type="button" 
                            @click="showPasswordModal = false" 
                            class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button 
                            type="submit" 
                            :disabled="passwordForm.processing"
                            class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-bold shadow-xs transition cursor-pointer disabled:opacity-50"
                        >
                            {{ passwordForm.processing ? 'Updating...' : 'Update Password' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </AuthenticatedLayout>
</template>
