<script setup>
import { ref, computed } from 'vue'
import { Head, router, Link, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import RefreshButton from '@/Components/UI/RefreshButton.vue'
import InfoCardsGrid from '@/Components/UI/InfoCardsGrid.vue'
import InfoCard from '@/Components/UI/InfoCard.vue'
import DataTableFilter from '@/Components/UI/DataTableFilter.vue'
import RowActionDropdown from '@/Components/UI/RowActionDropdown.vue'

import {
    PlusIcon,
    ArrowPathIcon,
    XMarkIcon,
    DocumentDuplicateIcon,
    PencilSquareIcon,
    TrashIcon,
    SparklesIcon,
    BoltIcon,
    ChatBubbleBottomCenterTextIcon,
    TagIcon,
    CheckIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    templates: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({
            total_templates: 0,
            technical_count: 0,
            billing_count: 0,
            total_usage_count: 0,
        }),
    },
    filters: {
        type: Object,
        default: () => ({ search: '', category: '' }),
    },
})

// Flash Feedback Toast
const feedbackMsg = ref('')

// Filter State
const search = ref(props.filters?.search || '')
const currentCategory = ref(props.filters?.category || 'all')

const applyFilters = () => {
    router.get(route('admin.support.canned-responses'), {
        search: search.value || undefined,
        category: currentCategory.value !== 'all' ? currentCategory.value : undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const selectCategory = (cat) => {
    currentCategory.value = cat
    applyFilters()
}

const resetFilters = () => {
    search.value = ''
    currentCategory.value = 'all'
    applyFilters()
}

// 1. CREATE / EDIT MODAL
const showModal = ref(false)
const isEditing = ref(false)
const editingTemplateId = ref(null)

const templateForm = useForm({
    title: '',
    category: 'technical',
    shortcut_code: '',
    content: '',
})

const openCreateModal = () => {
    isEditing.value = false
    editingTemplateId.value = null
    templateForm.reset()
    templateForm.category = currentCategory.value !== 'all' ? currentCategory.value : 'technical'
    templateForm.content = "Hello,\n\nThank you for contacting DeepTouchHost support.\n\nBest regards,\nOperations Team"
    showModal.value = true
}

const openEditModal = (tmpl) => {
    isEditing.value = true
    editingTemplateId.value = tmpl.id
    templateForm.title = tmpl.title
    templateForm.category = tmpl.category || 'technical'
    templateForm.shortcut_code = tmpl.shortcut_code || ''
    templateForm.content = tmpl.content || tmpl.message || ''
    showModal.value = true
}

const submitTemplateForm = () => {
    if (isEditing.value) {
        templateForm.put(route('admin.support.canned-responses.update', editingTemplateId.value), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false
                feedbackMsg.value = 'Template updated.'
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            }
        })
    } else {
        templateForm.post(route('admin.support.canned-responses.store'), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false
                feedbackMsg.value = 'New canned macro template added.'
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            }
        })
    }
}

// 2. DELETE MODAL
const showDeleteModal = ref(false)
const templateToDelete = ref(null)
const deleteForm = useForm({})

const openDeleteModal = (tmpl) => {
    templateToDelete.value = tmpl
    showDeleteModal.value = true
}

const submitDelete = () => {
    if (!templateToDelete.value) return
    deleteForm.delete(route('admin.support.canned-responses.destroy', templateToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false
            feedbackMsg.value = 'Canned template deleted.'
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

const copyContent = (content) => {
    navigator.clipboard.writeText(content)
    feedbackMsg.value = 'Template body copied to clipboard.'
    setTimeout(() => { feedbackMsg.value = '' }, 2500)
}
</script>

<template>
    <Head title="Canned Responses - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Support', href: '#' },
                    { label: 'Customer Support', href: route('admin.tickets.index') },
                    { label: 'Canned Response Macro Templates' }
                ]"
            >
                <template #actions>
                    <Link
                        :href="route('admin.tickets.index')"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <ArrowLeftIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>All Tickets</span>
                    </Link>

                    <button 
                        type="button" 
                        @click="openCreateModal"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <PlusIcon class="w-3.5 h-3.5" />
                        <span>Create Canned Template</span>
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
                    title="Response Macros"
                    :value="String(stats.total_templates || templates.length || 0)"
                    badge="Templates"
                    badgeType="info"
                    color="blue"
                    :icon="ChatBubbleBottomCenterTextIcon"
                />

                <InfoCard
                    title="Technical Answers"
                    :value="String(stats.technical_count || 0)"
                    badge="Support"
                    badgeType="success"
                    color="emerald"
                    :icon="SparklesIcon"
                />

                <InfoCard
                    title="Billing Answers"
                    :value="String(stats.billing_count || 0)"
                    badge="Finance"
                    badgeType="info"
                    color="purple"
                    :icon="TagIcon"
                />

                <InfoCard
                    title="Total Macro Uses"
                    :value="String(stats.total_usage_count || 0)"
                    badge="Redemptions"
                    badgeType="info"
                    color="sky"
                    :icon="BoltIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Category Tabs -->
            <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        v-for="cat in ['all', 'technical', 'billing', 'general', 'sales']"
                        :key="cat"
                        type="button"
                        @click="selectCategory(cat)"
                        :class="currentCategory === cat ? 'bg-blue-600 text-white' : 'bg-white text-slate-700 hover:bg-slate-50 border border-slate-200'"
                        class="px-3 py-1.5 rounded-[3px] font-bold text-xs uppercase font-mono transition cursor-pointer shadow-2xs"
                    >
                        {{ cat }}
                    </button>
                </div>
            </div>

            <!-- 4. Standard Filter Toolbar -->
            <DataTableFilter
                v-model="search"
                searchPlaceholder="Search template title, shortcut code, or content..."
                @search="applyFilters"
                @filter="applyFilters"
                @reset="resetFilters"
            />

            <!-- 5. Standard Data Table -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Template Title</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Category</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Shortcut Trigger</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Snippet Preview</th>
                                <th class="py-2.5 px-3 w-24">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(tmpl, index) in templates" :key="tmpl.id || index" class="hover:bg-blue-50/30 transition">
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-bold text-slate-900">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs border border-blue-100 shrink-0">
                                            <ChatBubbleBottomCenterTextIcon class="w-3.5 h-3.5" />
                                        </div>
                                        <span>{{ tmpl.title }}</span>
                                    </div>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase bg-slate-100 text-slate-700 border border-slate-200 font-mono">
                                        {{ tmpl.category || 'Technical' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono font-bold text-blue-700 text-[11px]">
                                    {{ tmpl.shortcut_code ? '!' + tmpl.shortcut_code : '-' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono text-[11px] text-slate-600 max-w-sm truncate" :title="tmpl.content || tmpl.message">
                                    {{ tmpl.content || tmpl.message }}
                                </td>
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1">
                                        <button 
                                            type="button" 
                                            @click="copyContent(tmpl.content || tmpl.message)"
                                            class="px-2 py-1 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs border border-slate-200 shadow-2xs transition cursor-pointer"
                                            title="Copy Content"
                                        >
                                            Copy 📋
                                        </button>

                                        <RowActionDropdown>
                                            <button
                                                type="button"
                                                @click="openEditModal(tmpl)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <PencilSquareIcon class="w-3.5 h-3.5 text-slate-500" />
                                                <span>Edit Template</span>
                                            </button>

                                            <button
                                                type="button"
                                                @click="openDeleteModal(tmpl)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-rose-600 hover:bg-rose-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <TrashIcon class="w-3.5 h-3.5" />
                                                <span>Delete Template</span>
                                            </button>
                                        </RowActionDropdown>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!templates || templates.length === 0">
                                <td colspan="6" class="py-12 text-center text-slate-400 font-sans">
                                    No canned response templates found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- CREATE / EDIT MODAL -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <ChatBubbleBottomCenterTextIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            {{ isEditing ? 'Edit Canned Macro' : 'Create Canned Response' }}
                        </h3>
                    </div>
                    <button @click="showModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitTemplateForm" class="p-4 space-y-3 text-xs">
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Macro Title / Label <span class="text-rose-500">*</span></label>
                        <input v-model="templateForm.title" type="text" required placeholder="DNS Propagation Explanation" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Category</label>
                            <select v-model="templateForm.category" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                                <option value="technical">Technical</option>
                                <option value="billing">Billing</option>
                                <option value="general">General</option>
                                <option value="sales">Sales</option>
                            </select>
                        </div>

                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Shortcut Trigger</label>
                            <input v-model="templateForm.shortcut_code" type="text" placeholder="dns" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Response Message Body <span class="text-rose-500">*</span></label>
                        <textarea v-model="templateForm.content" rows="4" required class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"></textarea>
                    </div>

                    <div class="px-4 py-2.5 -mx-4 -mb-4 mt-4 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end gap-2 text-xs">
                        <button
                            type="button"
                            @click="showModal = false"
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

        <!-- DELETE MODAL -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-rose-50 text-rose-600 flex items-center justify-center font-bold border border-rose-100">
                            <TrashIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Delete Canned Template
                        </h3>
                    </div>
                    <button @click="showDeleteModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-2.5 text-xs">
                    <p class="text-slate-600">
                        Are you sure you want to remove template <strong class="text-slate-900 font-mono">[{{ templateToDelete?.title }}]</strong>?
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
