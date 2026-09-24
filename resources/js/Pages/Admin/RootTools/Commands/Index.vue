<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import RefreshButton from '@/Components/UI/RefreshButton.vue'
import InfoCardsGrid from '@/Components/UI/InfoCardsGrid.vue'
import InfoCard from '@/Components/UI/InfoCard.vue'
import DataTableFilter from '@/Components/UI/DataTableFilter.vue'
import FilterSelect from '@/Components/UI/FilterSelect.vue'
import axios from 'axios'

import {
    CommandLineIcon,
    ArrowPathIcon,
    CpuChipIcon,
    ClockIcon,
    CheckIcon,
    PlayIcon,
    ShieldCheckIcon,
    ServerIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({
            kernel: 'Linux 6.8.0',
            arch: 'x86_64',
            uptime: 'unknown',
            load_1m: '0.12',
            load_5m: '0.08',
            presets_count: 12,
            total_executions: 0,
        }),
    },
    presets: {
        type: Array,
        default: () => [],
    },
    history: {
        type: Array,
        default: () => [],
    },
})

// Active Category
const activeCategory = ref('all')
const categories = ['all', 'Storage & Disks', 'Memory & Kernel', 'Network & Sockets', 'Security & SSH', 'Hosting Daemons']

const filteredPresets = computed(() => {
    if (activeCategory.value === 'all') {
        return props.presets
    }
    return props.presets.filter(p => p.category === activeCategory.value)
})

// Runner State
const command = ref('uptime -p && free -h -w')
const cwd = ref('/var/www/smarthost')
const isRunning = ref(false)
const feedbackMsg = ref('')

const executionResult = ref({
    command: 'uptime -p && free -h -w',
    cwd: '/var/www/smarthost',
    exit_code: 0,
    duration_ms: 12,
    output: '# DeepTouchHost Diagnostic Command Console ready.\n# Choose a preset or input custom command below.',
    timestamp: new Date().toLocaleTimeString(),
})

// Run Command
const runCommand = async (cmdToRun = null) => {
    const targetCmd = (cmdToRun || command.value).trim()
    if (!targetCmd || isRunning.value) return

    command.value = targetCmd
    isRunning.value = true

    try {
        const res = await axios.post(route('admin.root-tools.commands.execute'), {
            command: targetCmd,
            cwd: cwd.value,
        })
        executionResult.value = {
            command: res.data.command,
            cwd: res.data.cwd,
            exit_code: res.data.exit_code,
            duration_ms: res.data.duration_ms,
            output: res.data.output,
            timestamp: new Date().toLocaleTimeString(),
        }
        feedbackMsg.value = `Command executed successfully in ${res.data.duration_ms}ms.`
        setTimeout(() => { feedbackMsg.value = '' }, 3000)
    } catch (err) {
        executionResult.value = {
            command: targetCmd,
            cwd: cwd.value,
            exit_code: 1,
            duration_ms: 0,
            output: err.response?.data?.message || err.message || 'Execution error.',
            timestamp: new Date().toLocaleTimeString(),
        }
    } finally {
        isRunning.value = false
    }
}
</script>

<template>
    <Head title="Command Runner - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'System', href: '#' },
                    { label: 'Root Tools', href: route('admin.root-tools.terminal') },
                    { label: 'Root Diagnostic Command Runner & Batch Tasks' }
                ]"
            >
                <template #actions>
                    <Link
                        :href="route('admin.root-tools.terminal')"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <CommandLineIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>Interactive Terminal</span>
                    </Link>

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
                    title="Linux Kernel"
                    :value="stats.kernel || 'Linux 6.8.0'"
                    badge="Kernel"
                    badgeType="info"
                    color="blue"
                    :icon="CpuChipIcon"
                />

                <InfoCard
                    title="Architecture"
                    :value="stats.arch || 'x86_64'"
                    badge="POSIX"
                    badgeType="success"
                    color="emerald"
                    :icon="ServerIcon"
                />

                <InfoCard
                    title="Diagnostic Presets"
                    :value="String(stats.presets_count || presets.length || 12)"
                    badge="Presets"
                    badgeType="info"
                    color="purple"
                    :icon="CommandLineIcon"
                />

                <InfoCard
                    title="Load Average"
                    :value="`${stats.load_1m || '0.12'} (1m)`"
                    badge="Normal"
                    badgeType="success"
                    color="sky"
                    :icon="ClockIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Preset Category Tabs -->
            <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        v-for="cat in categories"
                        :key="cat"
                        type="button"
                        @click="activeCategory = cat"
                        :class="activeCategory === cat ? 'bg-blue-600 text-white' : 'bg-white text-slate-700 hover:bg-slate-50 border border-slate-200'"
                        class="px-3 py-1.5 rounded-[3px] font-bold text-xs uppercase font-mono transition cursor-pointer shadow-2xs"
                    >
                        {{ cat }}
                    </button>
                </div>
            </div>

            <!-- 4. Preset Buttons Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2">
                <button
                    v-for="p in filteredPresets"
                    :key="p.command"
                    type="button"
                    @click="runCommand(p.command)"
                    :disabled="isRunning"
                    class="p-2.5 bg-white hover:bg-blue-50/50 border border-[#E2E8F0] hover:border-blue-300 rounded-[4px] shadow-2xs text-left transition cursor-pointer flex flex-col justify-between"
                >
                    <div>
                        <span class="block text-xs font-bold text-slate-900 leading-tight">{{ p.name }}</span>
                        <span class="block text-[10.5px] text-slate-500 font-mono mt-0.5 truncate">{{ p.command }}</span>
                    </div>
                    <div class="mt-2 flex items-center justify-between text-[10px] text-slate-400 font-mono">
                        <span>{{ p.category }}</span>
                        <span class="text-blue-600 font-bold">Run ▶</span>
                    </div>
                </button>
            </div>

            <!-- 5. Interactive Execution Box & Output Console -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden space-y-3 p-4">
                <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide flex items-center gap-2 font-mono">
                    <CommandLineIcon class="w-4 h-4 text-blue-600" />
                    Execute Custom Shell Command
                </h3>

                <div class="flex items-center gap-2">
                    <input
                        v-model="command"
                        type="text"
                        :disabled="isRunning"
                        @keydown.enter.prevent="runCommand()"
                        placeholder="Enter Linux shell command (e.g. df -h, systemctl status nginx, iostat)..."
                        class="flex-1 px-3 py-2 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"
                    />
                    <button
                        type="button"
                        @click="runCommand()"
                        :disabled="isRunning || !command.trim()"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white font-bold rounded-[3px] text-xs transition shadow-2xs cursor-pointer flex items-center gap-1.5"
                    >
                        <PlayIcon v-if="!isRunning" class="w-3.5 h-3.5" />
                        <ArrowPathIcon v-else class="w-3.5 h-3.5 animate-spin" />
                        <span>{{ isRunning ? 'Executing...' : 'Execute' }}</span>
                    </button>
                </div>

                <!-- Terminal Execution Result Screen -->
                <div class="bg-slate-950 rounded-[3px] border border-slate-800 p-3 font-mono text-xs text-slate-200 space-y-2">
                    <div class="flex items-center justify-between text-[11px] text-slate-400 border-b border-slate-800 pb-2">
                        <span class="text-emerald-400 font-bold">root@node:{{ executionResult.cwd }}$ {{ executionResult.command }}</span>
                        <div class="flex items-center gap-3">
                            <span>Exit: <strong :class="executionResult.exit_code === 0 ? 'text-emerald-400' : 'text-rose-400'">{{ executionResult.exit_code }}</strong></span>
                            <span>{{ executionResult.duration_ms }}ms</span>
                        </div>
                    </div>
                    <pre class="whitespace-pre-wrap max-h-72 overflow-y-auto leading-relaxed text-slate-300">{{ executionResult.output }}</pre>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
