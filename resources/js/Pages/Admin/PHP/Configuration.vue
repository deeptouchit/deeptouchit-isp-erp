<script setup>
import { ref, computed } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import RefreshButton from '@/Components/UI/RefreshButton.vue'
import InfoCardsGrid from '@/Components/UI/InfoCardsGrid.vue'
import InfoCard from '@/Components/UI/InfoCard.vue'
import DataTableFilter from '@/Components/UI/DataTableFilter.vue'
import FilterSelect from '@/Components/UI/FilterSelect.vue'

import {
    CpuChipIcon,
    ShieldCheckIcon,
    ArrowLeftIcon,
    WrenchScrewdriverIcon,
    BoltIcon,
    AdjustmentsHorizontalIcon,
    CircleStackIcon,
    CheckIcon,
    DocumentTextIcon,
    ArrowUturnLeftIcon,
    CodeBracketIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    directives: {
        type: Object,
        default: () => ({}),
    },
    rawIni: {
        type: Object,
        default: () => ({
            custom_ini_path: '',
            custom_ini_content: '',
            main_ini_path: '',
            main_ini_content: '',
        }),
    },
    supportedDirectives: {
        type: Object,
        default: () => ({}),
    },
    stats: {
        type: Object,
        default: () => ({
            total_directives: 0,
            customized_count: 0,
            default_count: 0,
            fpm_running: 'unknown',
            memory_limit: '512M',
            upload_max: '256M',
            max_exec: '300',
        }),
    },
    selectedVersion: {
        type: String,
        default: '8.3',
    },
    installedVersions: {
        type: Array,
        default: () => [],
    },
    defaultVersion: {
        type: String,
        default: '8.3',
    },
})

// Toast Feedback
const feedbackMsg = ref('')

// Active Mode (Guided Form vs Raw INI Editor)
const activeMode = ref('guided')

// Guided Form State
const formValues = ref({})
const initializeFormValues = () => {
    const vals = {}
    for (const [key, item] of Object.entries(props.directives)) {
        vals[key] = item.value
    }
    formValues.value = vals
}
initializeFormValues()

// Raw INI Form State
const rawForm = useForm({
    version: props.selectedVersion,
    raw_content: props.rawIni?.custom_ini_content || '',
})

// Guided Submit Form
const configForm = useForm({
    version: props.selectedVersion,
    settings: {},
})

// Filter State
const search = ref('')
const selectedSection = ref('all')

const switchVersion = (version) => {
    router.get(route('admin.php.configuration'), { version }, {
        preserveState: false,
        preserveScroll: true,
    })
}

// Group Directives by Category / Section
const sections = {
    'Resource Limits': ['memory_limit', 'max_execution_time', 'max_input_time', 'max_input_vars'],
    'File Uploads': ['upload_max_filesize', 'post_max_size', 'file_uploads', 'max_file_uploads'],
    'Error & Logging': ['display_errors', 'display_startup_errors', 'error_reporting', 'log_errors', 'error_log'],
    'Security & Paths': ['allow_url_fopen', 'allow_url_include', 'disable_functions', 'expose_php', 'open_basedir'],
    'Session & Cache': ['session.gc_maxlifetime', 'session.cookie_lifetime', 'session.save_handler', 'date.timezone'],
    'Zend OPcache Engine': ['opcache.enable', 'opcache.memory_consumption', 'opcache.interned_strings_buffer', 'opcache.max_accelerated_files', 'opcache.revalidate_freq', 'opcache.validate_timestamps']
}

const filteredSections = computed(() => {
    const q = search.value.trim().toLowerCase()
    const result = {}

    for (const [sectionName, dirList] of Object.entries(sections)) {
        if (selectedSection.value !== 'all' && selectedSection.value !== sectionName) {
            continue
        }

        const filteredDirectives = dirList.filter(dKey => {
            const item = props.directives[dKey]
            if (!item) return false

            if (!q) return true
            return dKey.toLowerCase().includes(q) || 
                (item.description || '').toLowerCase().includes(q) ||
                String(formValues.value[dKey] || '').toLowerCase().includes(q)
        })

        if (filteredDirectives.length > 0) {
            result[sectionName] = filteredDirectives
        }
    }

    return result
})

const resetFilters = () => {
    search.value = ''
    selectedSection.value = 'all'
}

// Save Guided Form
const submitGuidedForm = () => {
    configForm.version = props.selectedVersion
    configForm.settings = { ...formValues.value }

    configForm.post(route('admin.php.update-config'), {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = `php.ini for PHP ${props.selectedVersion} saved and FPM reloaded.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// Save Raw INI
const submitRawForm = () => {
    rawForm.version = props.selectedVersion
    rawForm.post(route('admin.php.save-raw-ini'), {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = `Raw 99-custom.ini for PHP ${props.selectedVersion} saved and FPM reloaded.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// Reset to Defaults
const resetToDefaults = () => {
    if (confirm(`Reset all custom php.ini directives for PHP ${props.selectedVersion} back to factory defaults?`)) {
        useForm({ version: props.selectedVersion }).post(route('admin.php.reset-ini'), {
            preserveScroll: true,
            onSuccess: () => {
                feedbackMsg.value = `php.ini reset to system defaults for PHP ${props.selectedVersion}.`
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            }
        })
    }
}
</script>

<template>
    <Head :title="`php.ini Directives (PHP ${selectedVersion}) - DeepTouchHost`" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'PHP Manager', href: route('admin.php.index') },
                    { label: 'Configuration' }
                ]"
            >
                <template #actions>
                    <Link
                        :href="route('admin.php.index')"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <ArrowLeftIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>PHP Overview</span>
                    </Link>

                    <!-- Version Switcher -->
                    <div class="flex items-center gap-1.5 bg-white px-2.5 py-1.5 rounded-[3px] border border-slate-200 shadow-2xs text-xs">
                        <span class="font-bold text-slate-500">PHP Version:</span>
                        <select 
                            :value="selectedVersion"
                            @change="switchVersion($event.target.value)"
                            class="bg-blue-50 border border-blue-200 text-xs font-bold text-blue-700 rounded-[2px] py-0.5 px-2 focus:ring-1 focus:ring-blue-500 cursor-pointer outline-none"
                        >
                            <option v-for="ver in installedVersions" :key="ver" :value="ver">
                                PHP {{ ver }}
                            </option>
                        </select>
                    </div>

                    <!-- Mode Toggle (Guided vs Raw) -->
                    <div class="flex items-center bg-slate-100 p-0.5 rounded-[3px] border border-slate-200">
                        <button
                            type="button"
                            @click="activeMode = 'guided'"
                            :class="activeMode === 'guided' ? 'bg-white font-bold text-slate-900 shadow-2xs' : 'text-slate-500 hover:text-slate-800'"
                            class="px-2 py-1 text-xs rounded-[2px] transition cursor-pointer"
                        >
                            Guided Form
                        </button>
                        <button
                            type="button"
                            @click="activeMode = 'raw'"
                            :class="activeMode === 'raw' ? 'bg-white font-bold text-slate-900 shadow-2xs' : 'text-slate-500 hover:text-slate-800'"
                            class="px-2 py-1 text-xs rounded-[2px] transition cursor-pointer flex items-center gap-1"
                        >
                            <CodeBracketIcon class="w-3.5 h-3.5" />
                            <span>Raw INI</span>
                        </button>
                    </div>

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
                    title="Memory Limit"
                    :value="stats.memory_limit || '512M'"
                    badge="RAM"
                    badgeType="info"
                    color="blue"
                    :icon="CpuChipIcon"
                />

                <InfoCard
                    title="Max Upload Size"
                    :value="stats.upload_max || '256M'"
                    badge="Upload"
                    badgeType="success"
                    color="emerald"
                    :icon="BoltIcon"
                />

                <InfoCard
                    title="Max Execution Time"
                    :value="`${stats.max_exec || '300'}s`"
                    badge="Timeout"
                    badgeType="info"
                    color="sky"
                    :icon="AdjustmentsHorizontalIcon"
                />

                <InfoCard
                    title="Customized Directives"
                    :value="`${stats.customized_count || 0} / ${stats.total_directives || 0}`"
                    badge="Active"
                    badgeType="success"
                    color="purple"
                    :icon="WrenchScrewdriverIcon"
                />
            </InfoCardsGrid>

            <!-- GUIDED FORM MODE -->
            <div v-if="activeMode === 'guided'" class="space-y-3.5">
                <!-- 3. Standard Filter Toolbar -->
                <DataTableFilter
                    v-model="search"
                    searchPlaceholder="Search directive key (e.g. memory_limit, opcache, upload_max)..."
                    @search="() => {}"
                    @filter="() => {}"
                    @reset="resetFilters"
                >
                    <FilterSelect
                        v-model="selectedSection"
                        label="Directive Category"
                        :options="[
                            { label: 'All Directive Categories', value: 'all' },
                            ...Object.keys(sections).map(s => ({ label: s, value: s }))
                        ]"
                        placeholder="All Categories"
                    />
                </DataTableFilter>

                <!-- Categorized Directives Container -->
                <form @submit.prevent="submitGuidedForm" class="space-y-3.5">
                    <div 
                        v-for="(dirKeys, sectionName) in filteredSections" 
                        :key="sectionName"
                        class="bg-white rounded-[4px] border border-[#E2E8F0] p-4 shadow-2xs space-y-3"
                    >
                        <div class="flex items-center gap-2 pb-2 border-b border-slate-100">
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span>
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900">{{ sectionName }}</h3>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div 
                                v-for="dKey in dirKeys" 
                                :key="dKey"
                                class="p-3 rounded-[3px] border border-slate-200 bg-slate-50/40 space-y-1"
                            >
                                <div class="flex items-center justify-between">
                                    <label class="text-xs font-bold text-slate-900 font-mono">{{ dKey }}</label>
                                    <span v-if="props.directives[dKey]?.is_custom" class="px-1.5 py-0.2 rounded-[2px] text-[9px] font-bold uppercase bg-blue-50 text-blue-700 border border-blue-200">
                                        Custom
                                    </span>
                                </div>
                                <p class="text-[10.5px] text-slate-500">{{ props.directives[dKey]?.description }}</p>

                                <div class="pt-1">
                                    <!-- Select Dropdown -->
                                    <select
                                        v-if="props.directives[dKey]?.type === 'select'"
                                        v-model="formValues[dKey]"
                                        class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-white text-slate-900 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    >
                                        <option v-for="opt in props.directives[dKey]?.options" :key="opt" :value="opt">{{ opt }}</option>
                                    </select>

                                    <!-- Boolean Toggle -->
                                    <select
                                        v-else-if="props.directives[dKey]?.type === 'boolean'"
                                        v-model="formValues[dKey]"
                                        class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-white text-slate-900 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    >
                                        <option value="On">On</option>
                                        <option value="Off">Off</option>
                                    </select>

                                    <!-- Standard Input -->
                                    <input
                                        v-else
                                        v-model="formValues[dKey]"
                                        type="text"
                                        class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-white text-slate-900 font-mono focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Action Bar -->
                    <div class="bg-white rounded-[4px] border border-[#E2E8F0] p-3 shadow-2xs flex items-center justify-between">
                        <button
                            type="button"
                            @click="resetToDefaults"
                            class="px-3 py-1.5 bg-white hover:bg-slate-50 text-rose-600 font-bold rounded-[3px] border border-slate-200 text-xs transition cursor-pointer flex items-center gap-1 shadow-2xs"
                        >
                            <ArrowUturnLeftIcon class="w-3.5 h-3.5" />
                            <span>Reset to Factory Defaults</span>
                        </button>

                        <button
                            type="submit"
                            :disabled="configForm.processing"
                            class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] text-xs transition shadow-2xs cursor-pointer disabled:opacity-50"
                        >
                            {{ configForm.processing ? 'Saving...' : 'Save php.ini & Reload FPM' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- RAW INI FILE EDITOR MODE -->
            <div v-else class="bg-white rounded-[4px] border border-[#E2E8F0] p-4 shadow-2xs space-y-3.5">
                <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900">Raw Override File Editor</h3>
                        <p class="text-[11px] text-slate-400 font-mono">{{ rawIni.custom_ini_path }}</p>
                    </div>
                    <span class="text-[10px] text-slate-400 font-mono">Managed by DeepTouchHost</span>
                </div>

                <form @submit.prevent="submitRawForm" class="space-y-3">
                    <textarea
                        v-model="rawForm.raw_content"
                        rows="18"
                        class="w-full p-3 rounded-[3px] bg-slate-900 text-emerald-400 font-mono text-xs border border-slate-800 focus:outline-none focus:ring-1 focus:ring-blue-500 shadow-inner leading-relaxed"
                        placeholder="; Custom PHP INI Directives&#10;memory_limit = 512M&#10;upload_max_filesize = 256M"
                    ></textarea>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                        <button
                            type="submit"
                            :disabled="rawForm.processing"
                            class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] text-xs transition shadow-2xs cursor-pointer disabled:opacity-50"
                        >
                            {{ rawForm.processing ? 'Saving File...' : 'Save Raw INI & Reload FPM' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
