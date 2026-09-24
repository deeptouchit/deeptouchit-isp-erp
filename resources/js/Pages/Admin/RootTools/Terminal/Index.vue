<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import RefreshButton from '@/Components/UI/RefreshButton.vue'
import InfoCardsGrid from '@/Components/UI/InfoCardsGrid.vue'
import InfoCard from '@/Components/UI/InfoCard.vue'
import axios from 'axios'

import {
    CommandLineIcon,
    ServerIcon,
    ClockIcon,
    FolderIcon,
    TrashIcon,
    ArrowPathIcon,
    CheckIcon,
    PlayIcon,
    ShieldCheckIcon,
    CpuChipIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({
            hostname: 'deeptouchhost-node',
            kernel: '6.8.0-generic',
            distro: 'Ubuntu 24.04 LTS',
            current_user: 'root',
            shell: '/bin/bash',
            cwd: '/var/www/smarthost',
            uptime: '12d 4h',
            load_avg: '0.12, 0.08, 0.05',
            total_ram: '8.0 GB',
            free_ram: '4.2 GB',
            server_ip: '103.59.177.138',
            ssh_port: 22,
        }),
    },
    auditLogs: {
        type: Array,
        default: () => [],
    },
    presets: {
        type: Object,
        default: () => ({}),
    },
})

// Terminal State
const currentCwd = ref(props.stats.cwd || '/var/www/smarthost')
const commandInput = ref('')
const commandInputRef = ref(null)
const terminalScrollRef = ref(null)
const isExecuting = ref(false)
const feedbackMsg = ref('')

// Terminal Output Buffer
const terminalBuffer = ref([
    {
        type: 'banner',
        content: `DeepTouchHost Interactive Shell Console [Version 2.4.0]\nConnected to: ${props.stats.hostname} (${props.stats.server_ip})\nOperating System: ${props.stats.distro} | Kernel: ${props.stats.kernel}\nType 'help' or click presets below for rapid diagnostics.`,
        timestamp: new Date().toLocaleTimeString(),
    }
])

// Command History
const history = ref([])
const historyIndex = ref(-1)

// Presets
const defaultPresets = [
    { label: 'Uptime & Load', cmd: 'uptime' },
    { label: 'Disk Free (df -h)', cmd: 'df -h' },
    { label: 'Free Memory (free -m)', cmd: 'free -m' },
    { label: 'PHP-FPM Status', cmd: 'systemctl status php8.3-fpm --no-pager' },
    { label: 'Nginx Status', cmd: 'systemctl status nginx --no-pager' },
    { label: 'Top Processes', cmd: 'ps aux --sort=-%cpu | head -n 10' },
    { label: 'Network Ports', cmd: 'ss -tulpn' },
]

const scrollToBottom = () => {
    nextTick(() => {
        if (terminalScrollRef.value) {
            terminalScrollRef.value.scrollTop = terminalScrollRef.value.scrollHeight
        }
    })
}

const clearBuffer = () => {
    terminalBuffer.value = []
    feedbackMsg.value = 'Console buffer cleared.'
    setTimeout(() => { feedbackMsg.value = '' }, 2500)
}

const runPreset = (cmd) => {
    commandInput.value = cmd
    executeCommand()
}

const executeCommand = async () => {
    const rawCmd = commandInput.value.trim()
    if (!rawCmd) return

    history.value.push(rawCmd)
    historyIndex.value = -1

    terminalBuffer.value.push({
        type: 'command',
        cwd: currentCwd.value,
        command: rawCmd,
        timestamp: new Date().toLocaleTimeString(),
    })

    commandInput.value = ''
    isExecuting.value = true
    scrollToBottom()

    if (rawCmd === 'clear') {
        clearBuffer()
        isExecuting.value = false
        return
    }

    try {
        const response = await axios.post(route('admin.root-tools.terminal.execute'), {
            command: rawCmd,
            cwd: currentCwd.value,
        })

        terminalBuffer.value.push({
            type: 'output',
            output: response.data.output || '(No output returned)',
            exit_code: response.data.exit_code ?? 0,
            timestamp: new Date().toLocaleTimeString(),
        })

        if (response.data.cwd) {
            currentCwd.value = response.data.cwd
        }
    } catch (err) {
        terminalBuffer.value.push({
            type: 'error',
            output: err.response?.data?.message || err.message || 'Execution error encountered.',
            exit_code: 1,
            timestamp: new Date().toLocaleTimeString(),
        })
    } finally {
        isExecuting.value = false
        scrollToBottom()
        nextTick(() => {
            commandInputRef.value?.focus()
        })
    }
}

const navigateHistory = (direction) => {
    if (history.value.length === 0) return

    if (direction === 'up') {
        if (historyIndex.value === -1) {
            historyIndex.value = history.value.length - 1
        } else if (historyIndex.value > 0) {
            historyIndex.value--
        }
    } else if (direction === 'down') {
        if (historyIndex.value !== -1) {
            if (historyIndex.value < history.value.length - 1) {
                historyIndex.value++
            } else {
                historyIndex.value = -1
                commandInput.value = ''
                return
            }
        }
    }

    if (historyIndex.value !== -1) {
        commandInput.value = history.value[historyIndex.value]
    }
}

onMounted(() => {
    commandInputRef.value?.focus()
})
</script>

<template>
    <Head title="Terminal Console - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'System', href: '#' },
                    { label: 'Root Tools', href: route('admin.root-tools.terminal') },
                    { label: 'Web SSH Interactive Terminal Console' }
                ]"
            >
                <template #actions>
                    <Link
                        :href="route('admin.root-tools.processes')"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <CpuChipIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>Process Manager</span>
                    </Link>

                    <button 
                        type="button" 
                        @click="clearBuffer"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <TrashIcon class="w-3.5 h-3.5" />
                        <span>Clear Buffer</span>
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
                    title="Node Hostname"
                    :value="stats.hostname || 'deeptouchhost-node'"
                    badge="Active"
                    badgeType="success"
                    color="blue"
                    :icon="ServerIcon"
                />

                <InfoCard
                    title="OS & Kernel"
                    :value="stats.distro || 'Ubuntu 24.04'"
                    badge="Linux"
                    badgeType="info"
                    color="emerald"
                    :icon="CpuChipIcon"
                />

                <InfoCard
                    title="User & Shell"
                    :value="`${stats.current_user || 'root'} (${stats.shell || 'bash'})`"
                    badge="Root"
                    badgeType="warning"
                    color="purple"
                    :icon="ShieldCheckIcon"
                />

                <InfoCard
                    title="Memory & Load"
                    :value="`${stats.free_ram || '4.2 GB'} free`"
                    badge="Healthy"
                    badgeType="success"
                    color="sky"
                    :icon="ClockIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Quick Diagnostics Presets Bar -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] p-2.5 shadow-2xs flex flex-wrap items-center gap-2">
                <span class="text-xs font-bold text-slate-700 uppercase tracking-wide flex items-center gap-1.5 font-mono">
                    <CommandLineIcon class="w-3.5 h-3.5 text-blue-600" />
                    Quick Presets:
                </span>
                <button
                    v-for="p in defaultPresets"
                    :key="p.cmd"
                    type="button"
                    @click="runPreset(p.cmd)"
                    class="px-2.5 py-1 bg-slate-50 hover:bg-blue-50 text-slate-700 hover:text-blue-700 font-mono text-xs font-bold rounded-[3px] border border-slate-200 transition cursor-pointer shadow-2xs"
                >
                    {{ p.label }}
                </button>
            </div>

            <!-- 4. Interactive Terminal Console Screen -->
            <div class="bg-slate-950 rounded-[4px] border border-slate-800 shadow-xl overflow-hidden font-mono text-xs text-slate-100 flex flex-col h-[520px]">
                <!-- Terminal Top Titlebar -->
                <div class="bg-slate-900 px-3 py-2 border-b border-slate-800 flex items-center justify-between text-[11px] text-slate-400 select-none">
                    <div class="flex items-center gap-2">
                        <div class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-rose-500/80"></span>
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-500/80"></span>
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500/80"></span>
                        </div>
                        <span class="font-bold text-slate-300 ml-2">root@{{ stats.hostname }}: {{ currentCwd }}</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span>SSH Port: {{ stats.ssh_port || 22 }}</span>
                        <span class="text-emerald-400 font-bold">● CONNECTED</span>
                    </div>
                </div>

                <!-- Scrollable Terminal Body -->
                <div ref="terminalScrollRef" class="p-3 flex-1 overflow-y-auto space-y-2 select-text leading-relaxed">
                    <div v-for="(entry, index) in terminalBuffer" :key="index">
                        <!-- Banner Entry -->
                        <div v-if="entry.type === 'banner'" class="text-slate-400 whitespace-pre-wrap border-b border-slate-800 pb-2 mb-2">
                            {{ entry.content }}
                        </div>

                        <!-- Command Entry -->
                        <div v-else-if="entry.type === 'command'" class="flex items-center gap-1 text-emerald-400 font-bold">
                            <span class="text-blue-400">root@{{ stats.hostname }}:{{ entry.cwd }}$</span>
                            <span>{{ entry.command }}</span>
                        </div>

                        <!-- Output Entry -->
                        <div v-else-if="entry.type === 'output'" class="text-slate-200 whitespace-pre-wrap pl-2 border-l-2 border-slate-700 my-1 font-mono">
                            {{ entry.output }}
                        </div>

                        <!-- Error Entry -->
                        <div v-else-if="entry.type === 'error'" class="text-rose-400 whitespace-pre-wrap pl-2 border-l-2 border-rose-500 my-1 font-mono">
                            {{ entry.output }}
                        </div>
                    </div>

                    <!-- Live Running Spinner -->
                    <div v-if="isExecuting" class="flex items-center gap-2 text-amber-400">
                        <ArrowPathIcon class="w-3.5 h-3.5 animate-spin" />
                        <span>Executing command in subprocess shell...</span>
                    </div>
                </div>

                <!-- Terminal Prompt Input Line -->
                <div class="bg-slate-900/90 border-t border-slate-800 p-2.5 flex items-center gap-2">
                    <span class="text-blue-400 font-bold shrink-0">root@{{ stats.hostname }}:{{ currentCwd }}$</span>
                    <input
                        ref="commandInputRef"
                        v-model="commandInput"
                        type="text"
                        :disabled="isExecuting"
                        @keydown.enter.prevent="executeCommand"
                        @keydown.up.prevent="navigateHistory('up')"
                        @keydown.down.prevent="navigateHistory('down')"
                        placeholder="Enter Linux command (e.g. ls -la, htop, df -h, systemctl status)..."
                        class="flex-1 bg-transparent text-emerald-300 font-mono text-xs focus:outline-none placeholder:text-slate-600"
                    />
                    <button
                        type="button"
                        @click="executeCommand"
                        :disabled="isExecuting || !commandInput.trim()"
                        class="px-3 py-1 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white font-bold rounded-[2px] transition cursor-pointer text-xs"
                    >
                        Run
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
