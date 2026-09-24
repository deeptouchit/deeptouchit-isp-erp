<script setup>
import { ref, computed } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import RefreshButton from '@/Components/UI/RefreshButton.vue'
import InfoCardsGrid from '@/Components/UI/InfoCardsGrid.vue'
import InfoCard from '@/Components/UI/InfoCard.vue'
import DataTableFilter from '@/Components/UI/DataTableFilter.vue'
import RowActionDropdown from '@/Components/UI/RowActionDropdown.vue'

import {
    DocumentDuplicateIcon,
    ServerIcon,
    PlusIcon,
    TrashIcon,
    PencilSquareIcon,
    XMarkIcon,
    CheckIcon,
    ShieldCheckIcon,
    BoltIcon,
    StarIcon,
    ArrowLeftIcon,
    ArrowPathIcon
} from '@heroicons/vue/24/outline'
import { StarIcon as StarIconSolid } from '@heroicons/vue/24/solid'

const props = defineProps({
    templates: {
        type: Array,
        default: () => [],
    },
    zones: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({
            total_templates: 0,
            default_template_name: 'None',
            system_templates_count: 0,
            total_records: 0,
        }),
    },
})

// Toast Feedback
const feedbackMsg = ref('')
const search = ref('')

const filteredTemplates = computed(() => {
    let list = props.templates || []
    if (search.value.trim()) {
        const q = search.value.toLowerCase()
        list = list.filter(t =>
            t.name.toLowerCase().includes(q) ||
            (t.description || '').toLowerCase().includes(q)
        )
    }
    return list
})

// 1. CREATE / EDIT TEMPLATE MODAL
const showTemplateModal = ref(false)
const isEditing = ref(false)
const editingTemplateId = ref(null)

const templateForm = useForm({
    name: '',
    description: '',
    is_default: false,
    records: [],
})

const openCreateModal = () => {
    isEditing.value = false
    editingTemplateId.value = null
    templateForm.reset()
    templateForm.name = ''
    templateForm.description = ''
    templateForm.is_default = false
    templateForm.records = [
        { name: '@', type: 'A', content: '%ip%', ttl: 3600, priority: null },
        { name: 'www', type: 'CNAME', content: '%domain%.', ttl: 3600, priority: null },
        { name: 'mail', type: 'A', content: '%ip%', ttl: 3600, priority: null },
        { name: '@', type: 'MX', content: 'mail.%domain%.', ttl: 3600, priority: 10 },
        { name: '@', type: 'TXT', content: '"v=spf1 a mx ip4:%ip% ~all"', ttl: 3600, priority: null },
    ]
    showTemplateModal.value = true
}

const openEditModal = (tpl) => {
    isEditing.value = true
    editingTemplateId.value = tpl.id
    templateForm.name = tpl.name
    templateForm.description = tpl.description || ''
    templateForm.is_default = tpl.is_default
    templateForm.records = (tpl.records || []).map(r => ({
        name: r.name,
        type: r.type,
        content: r.content,
        ttl: r.ttl || 3600,
        priority: r.priority,
    }))
    showTemplateModal.value = true
}

const addRecordRow = () => {
    templateForm.records.push({
        name: '@',
        type: 'A',
        content: '%ip%',
        ttl: 3600,
        priority: null,
    })
}

const removeRecordRow = (idx) => {
    templateForm.records.splice(idx, 1)
}

const submitTemplate = () => {
    if (isEditing.value) {
        templateForm.put(route('admin.dns.templates.update', editingTemplateId.value), {
            preserveScroll: true,
            onSuccess: () => {
                showTemplateModal.value = false
                feedbackMsg.value = `Template '${templateForm.name}' updated.`
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            }
        })
    } else {
        templateForm.post(route('admin.dns.templates.store'), {
            preserveScroll: true,
            onSuccess: () => {
                showTemplateModal.value = false
                feedbackMsg.value = `Template '${templateForm.name}' created.`
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            }
        })
    }
}

// 2. SET DEFAULT
const setDefault = (tpl) => {
    router.post(route('admin.dns.templates.set-default', tpl.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = `Template '${tpl.name}' set as default.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 3. DUPLICATE
const duplicateTemplate = (tpl) => {
    router.post(route('admin.dns.templates.duplicate', tpl.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = `Template cloned as '${tpl.name} (Copy)'.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 4. APPLY TO ZONE MODAL
const showApplyModal = ref(false)
const templateToApply = ref(null)
const applyForm = useForm({
    dns_zone_id: props.zones[0]?.id || '',
    overwrite: false,
})

const openApplyModal = (tpl) => {
    templateToApply.value = tpl
    applyForm.dns_zone_id = props.zones[0]?.id || ''
    applyForm.overwrite = false
    showApplyModal.value = true
}

const submitApply = () => {
    if (!templateToApply.value) return
    applyForm.post(route('admin.dns.templates.apply', templateToApply.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showApplyModal.value = false
            feedbackMsg.value = `Template applied to selected zone.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 5. DELETE TEMPLATE
const showDeleteModal = ref(false)
const templateToDelete = ref(null)
const deleteForm = useForm({})

const openDeleteModal = (tpl) => {
    templateToDelete.value = tpl
    showDeleteModal.value = true
}

const submitDelete = () => {
    if (!templateToDelete.value) return
    deleteForm.delete(route('admin.dns.templates.destroy', templateToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false
            feedbackMsg.value = `Template deleted.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}
</script>

<template>
    <Head title="DNS Zone Templates - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Services', href: '#' },
                    { label: 'DNS Management', href: route('admin.dns.zones') },
                    { label: 'Zone Templates & Blueprints' }
                ]"
            >
                <template #actions>
                    <Link
                        :href="route('admin.dns.zones')"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <ArrowLeftIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>DNS Zones</span>
                    </Link>

                    <button 
                        type="button" 
                        @click="openCreateModal"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <PlusIcon class="w-3.5 h-3.5" />
                        <span>Create Template</span>
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
                    title="DNS Zone Templates"
                    :value="String(stats.total_templates || templates.length || 0)"
                    badge="Templates"
                    badgeType="info"
                    color="blue"
                    :icon="DocumentDuplicateIcon"
                />

                <InfoCard
                    title="Default Template"
                    :value="stats.default_template_name || 'Standard Web'"
                    badge="Active Auto"
                    badgeType="success"
                    color="emerald"
                    :icon="StarIconSolid"
                />

                <InfoCard
                    title="System Blueprints"
                    :value="String(stats.system_templates_count || 0)"
                    badge="Built-in"
                    badgeType="info"
                    color="purple"
                    :icon="ServerIcon"
                />

                <InfoCard
                    title="Total Schema Rules"
                    :value="String(stats.total_records || 0)"
                    badge="Record Schema"
                    badgeType="info"
                    color="sky"
                    :icon="ShieldCheckIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Standard Filter Toolbar -->
            <DataTableFilter
                v-model="search"
                searchPlaceholder="Search template name or description..."
                @search="() => {}"
                @filter="() => {}"
                @reset="search = ''"
            />

            <!-- 4. Standard Data Table -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Template Name</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Description & Use Case</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Records Count</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Sample Records Included</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Default</th>
                                <th class="py-2.5 px-3 w-28">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(tpl, index) in filteredTemplates" :key="tpl.id || index" class="hover:bg-blue-50/30 transition">
                                <!-- # -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>

                                <!-- Name -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-bold text-slate-900">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs border border-blue-100 shrink-0">
                                            <DocumentDuplicateIcon class="w-3.5 h-3.5" />
                                        </div>
                                        <span>{{ tpl.name }}</span>
                                    </div>
                                </td>

                                <!-- Description -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap text-slate-600 max-w-sm truncate" :title="tpl.description">
                                    {{ tpl.description || 'Standard DNS template rules.' }}
                                </td>

                                <!-- Count -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono font-bold text-slate-900">
                                    {{ tpl.records?.length || 0 }}
                                </td>

                                <!-- Sample -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono text-[10.5px] text-slate-500">
                                    <span v-for="(r, ri) in (tpl.records || []).slice(0, 4)" :key="ri" class="inline-block mr-1.5 px-1.5 py-0.2 bg-slate-50 rounded border border-slate-200">
                                        {{ r.name }} ({{ r.type }})
                                    </span>
                                    <span v-if="(tpl.records || []).length > 4" class="text-slate-400">+{{ tpl.records.length - 4 }} more</span>
                                </td>

                                <!-- Default -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <button 
                                        type="button" 
                                        @click="setDefault(tpl)"
                                        class="inline-flex items-center gap-1 cursor-pointer"
                                        :title="tpl.is_default ? 'Default for new domains' : 'Click to make default'"
                                    >
                                        <StarIconSolid v-if="tpl.is_default" class="w-4 h-4 text-amber-500" />
                                        <StarIcon v-else class="w-4 h-4 text-slate-300 hover:text-amber-500 transition" />
                                    </button>
                                </td>

                                <!-- Action -->
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1">
                                        <button 
                                            type="button" 
                                            @click="openApplyModal(tpl)"
                                            class="px-2 py-1 bg-white hover:bg-slate-50 text-blue-700 font-bold rounded-[3px] text-xs border border-slate-200 shadow-2xs transition cursor-pointer"
                                            title="Apply Template to Zone"
                                        >
                                            Apply ⚡
                                        </button>

                                        <RowActionDropdown>
                                            <button
                                                type="button"
                                                @click="openEditModal(tpl)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <PencilSquareIcon class="w-3.5 h-3.5 text-slate-500" />
                                                <span>Edit Template Blueprint</span>
                                            </button>

                                            <button
                                                type="button"
                                                @click="duplicateTemplate(tpl)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <DocumentDuplicateIcon class="w-3.5 h-3.5 text-slate-500" />
                                                <span>Duplicate Blueprint</span>
                                            </button>

                                            <button
                                                type="button"
                                                @click="openDeleteModal(tpl)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-rose-600 hover:bg-rose-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <TrashIcon class="w-3.5 h-3.5" />
                                                <span>Delete Template</span>
                                            </button>
                                        </RowActionDropdown>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!filteredTemplates || filteredTemplates.length === 0">
                                <td colspan="7" class="py-12 text-center text-slate-400">
                                    No DNS templates configured.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 1. CREATE / EDIT TEMPLATE MODAL -->
        <div v-if="showTemplateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-2xl overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <DocumentDuplicateIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            {{ isEditing ? 'Edit DNS Template' : 'Create DNS Zone Template' }}
                        </h3>
                    </div>
                    <button @click="showTemplateModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitTemplate" class="p-4 space-y-3.5 text-xs">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Template Name <span class="text-rose-500">*</span></label>
                            <input v-model="templateForm.name" type="text" required placeholder="e.g. Standard Web & Mail" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                        </div>

                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Description</label>
                            <input v-model="templateForm.description" type="text" placeholder="Short summary..." class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                        </div>
                    </div>

                    <!-- Macro Instructions -->
                    <div class="p-2 rounded-[3px] bg-blue-50/60 border border-blue-100 text-[11px] text-blue-800 flex items-center justify-between font-mono">
                        <span>Placeholders: <strong>%ip%</strong> = Server IPv4 | <strong>%domain%</strong> = Target Zone FQDN</span>
                        <button type="button" @click="addRecordRow" class="text-xs font-bold text-blue-700 hover:underline cursor-pointer">+ Add Record Row</button>
                    </div>

                    <!-- Records Table -->
                    <div class="border border-slate-200 rounded-[3px] overflow-hidden max-h-60 overflow-y-auto">
                        <table class="w-full text-center border-collapse text-xs">
                            <thead class="bg-slate-50 border-b border-slate-200 text-[10px] font-bold uppercase text-slate-600">
                                <tr>
                                    <th class="py-1.5 px-2 text-left w-24">Host</th>
                                    <th class="py-1.5 px-2 w-20">Type</th>
                                    <th class="py-1.5 px-2 text-left">Value / Target</th>
                                    <th class="py-1.5 px-2 w-16">TTL</th>
                                    <th class="py-1.5 px-2 w-16">Prio</th>
                                    <th class="py-1.5 px-2 w-8"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 font-mono text-[11px]">
                                <tr v-for="(row, idx) in templateForm.records" :key="idx">
                                    <td class="p-1">
                                        <input v-model="row.name" type="text" required class="w-full px-1.5 py-1 text-xs border border-slate-200 rounded-[2px]" />
                                    </td>
                                    <td class="p-1">
                                        <select v-model="row.type" class="w-full px-1 py-1 text-xs border border-slate-200 rounded-[2px] font-bold">
                                            <option value="A">A</option>
                                            <option value="AAAA">AAAA</option>
                                            <option value="CNAME">CNAME</option>
                                            <option value="MX">MX</option>
                                            <option value="TXT">TXT</option>
                                            <option value="NS">NS</option>
                                        </select>
                                    </td>
                                    <td class="p-1">
                                        <input v-model="row.content" type="text" required class="w-full px-1.5 py-1 text-xs border border-slate-200 rounded-[2px]" />
                                    </td>
                                    <td class="p-1">
                                        <input v-model.number="row.ttl" type="number" class="w-full px-1 py-1 text-xs border border-slate-200 rounded-[2px] text-center" />
                                    </td>
                                    <td class="p-1">
                                        <input v-model.number="row.priority" type="number" class="w-full px-1 py-1 text-xs border border-slate-200 rounded-[2px] text-center" />
                                    </td>
                                    <td class="p-1">
                                        <button type="button" @click="removeRecordRow(idx)" class="text-rose-500 hover:text-rose-700 font-bold cursor-pointer">✕</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="px-4 py-2.5 -mx-4 -mb-4 mt-4 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end gap-2 text-xs">
                        <button
                            type="button"
                            @click="showTemplateModal = false"
                            class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="templateForm.processing"
                            class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                        >
                            {{ templateForm.processing ? 'Saving...' : (isEditing ? 'Save Changes' : 'Create Template') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 2. APPLY TEMPLATE MODAL -->
        <div v-if="showApplyModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <BoltIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Apply Template: {{ templateToApply?.name }}
                        </h3>
                    </div>
                    <button @click="showApplyModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitApply" class="p-4 space-y-3 text-xs">
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Target DNS Zone <span class="text-rose-500">*</span></label>
                        <select v-model="applyForm.dns_zone_id" required class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                            <option v-for="z in zones" :key="z.id" :value="z.id">
                                {{ z.domain }}
                            </option>
                        </select>
                    </div>

                    <div class="flex items-center gap-2 pt-1">
                        <input v-model="applyForm.overwrite" id="overwrite_records" type="checkbox" class="rounded border-slate-300 text-rose-600 focus:ring-rose-500 cursor-pointer" />
                        <label for="overwrite_records" class="text-slate-700 font-medium cursor-pointer">Overwrite existing duplicate records</label>
                    </div>

                    <div class="px-4 py-2.5 -mx-4 -mb-4 mt-4 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end gap-2 text-xs">
                        <button
                            type="button"
                            @click="showApplyModal = false"
                            class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="applyForm.processing"
                            class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                        >
                            {{ applyForm.processing ? 'Applying...' : 'Apply Blueprint' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 3. DELETE MODAL -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-rose-50 text-rose-600 flex items-center justify-center font-bold border border-rose-100">
                            <TrashIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Delete DNS Template
                        </h3>
                    </div>
                    <button @click="showDeleteModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-2.5 text-xs">
                    <p class="text-slate-600">
                        Are you sure you want to delete template <strong class="text-slate-900 font-mono">[{{ templateToDelete?.name }}]</strong>?
                    </p>
                </div>

                <div class="px-4 py-2.5 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end gap-2 text-xs">
                    <button
                        type="button"
                        @click="showDeleteModal = false"
                        class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="submitDelete"
                        :disabled="deleteForm.processing"
                        class="px-3.5 py-1.5 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                    >
                        {{ deleteForm.processing ? 'Deleting...' : 'Delete Template' }}
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
