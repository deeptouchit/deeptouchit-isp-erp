<script setup>
import { ref, computed } from 'vue'
import { Head, router, Link, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import RefreshButton from '@/Components/UI/RefreshButton.vue'
import InfoCardsGrid from '@/Components/UI/InfoCardsGrid.vue'
import InfoCard from '@/Components/UI/InfoCard.vue'
import DataTableFilter from '@/Components/UI/DataTableFilter.vue'
import FilterSelect from '@/Components/UI/FilterSelect.vue'
import RowActionDropdown from '@/Components/UI/RowActionDropdown.vue'

import {
    PlusIcon,
    ArrowPathIcon,
    XMarkIcon,
    MegaphoneIcon,
    BellAlertIcon,
    WrenchIcon,
    PencilSquareIcon,
    TrashIcon,
    TagIcon,
    BoltIcon,
    CheckIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    announcements: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    stats: {
        type: Object,
        default: () => ({
            total_announcements: 0,
            published_count: 0,
            banner_count: 0,
            maintenance_count: 0,
            total_views: 0,
        }),
    },
    filters: {
        type: Object,
        default: () => ({ search: '', type: '', status: '' }),
    },
})

// Flash Feedback Toast
const feedbackMsg = ref('')

// Filter State
const search = ref(props.filters?.search || '')
const currentType = ref(props.filters?.type || 'all')
const currentStatus = ref(props.filters?.status || 'all')

const applyFilters = () => {
    router.get(route('admin.support.announcements'), {
        search: search.value || undefined,
        type: currentType.value !== 'all' ? currentType.value : undefined,
        status: currentStatus.value !== 'all' ? currentStatus.value : undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const selectType = (type) => {
    currentType.value = type
    applyFilters()
}

const resetFilters = () => {
    search.value = ''
    currentType.value = 'all'
    currentStatus.value = 'all'
    applyFilters()
}

// 1. CREATE / EDIT MODAL
const showModal = ref(false)
const isEditing = ref(false)
const editingAnnouncementId = ref(null)

const annForm = useForm({
    title: '',
    type: 'general',
    severity: 'info',
    target_audience: 'all',
    summary: '',
    content: '',
    is_published: true,
    is_pinned: false,
    show_banner: false,
})

const openCreateModal = () => {
    isEditing.value = false
    editingAnnouncementId.value = null
    annForm.reset()
    annForm.type = 'general'
    annForm.severity = 'info'
    annForm.target_audience = 'all'
    annForm.is_published = true
    showModal.value = true
}

const openEditModal = (ann) => {
    isEditing.value = true
    editingAnnouncementId.value = ann.id
    annForm.title = ann.title
    annForm.type = ann.type || 'general'
    annForm.severity = ann.severity || 'info'
    annForm.target_audience = ann.target_audience || 'all'
    annForm.summary = ann.summary || ''
    annForm.content = ann.content || ''
    annForm.is_published = Boolean(ann.is_published)
    annForm.is_pinned = Boolean(ann.is_pinned)
    annForm.show_banner = Boolean(ann.show_banner)
    showModal.value = true
}

const submitAnnForm = () => {
    if (isEditing.value) {
        annForm.put(route('admin.support.announcements.update', editingAnnouncementId.value), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false
                feedbackMsg.value = 'Announcement bulletin updated.'
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            }
        })
    } else {
        annForm.post(route('admin.support.announcements.store'), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false
                feedbackMsg.value = 'New client announcement published.'
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            }
        })
    }
}

// 2. TOGGLE PUBLISHED
const togglePublish = (ann) => {
    router.post(route('admin.support.announcements.toggle', ann.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            feedbackMsg.value = 'Announcement visibility updated.'
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 3. DELETE MODAL
const showDeleteModal = ref(false)
const annToDelete = ref(null)
const deleteForm = useForm({})

const openDeleteModal = (ann) => {
    annToDelete.value = ann
    showDeleteModal.value = true
}

const submitDelete = () => {
    if (!annToDelete.value) return
    deleteForm.delete(route('admin.support.announcements.destroy', annToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false
            feedbackMsg.value = 'Announcement deleted.'
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}
</script>

<template>
    <Head title="Announcements - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Support', href: '#' },
                    { label: 'Customer Support', href: route('admin.tickets.index') },
                    { label: 'Client Broadcast Advisories & Announcements' }
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
                        <span>Create Announcement</span>
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
                    title="Total Advisories"
                    :value="String(stats.total_announcements || announcements.data?.length || 0)"
                    badge="Bulletins"
                    badgeType="info"
                    color="blue"
                    :icon="MegaphoneIcon"
                />

                <InfoCard
                    title="Published Live"
                    :value="String(stats.published_count || announcements.data?.filter(a => a.is_published).length || 0)"
                    badge="Active"
                    badgeType="success"
                    color="emerald"
                    :icon="CheckIcon"
                />

                <InfoCard
                    title="Global Banners"
                    :value="String(stats.banner_count || 0)"
                    badge="Alert Banners"
                    badgeType="warning"
                    color="purple"
                    :icon="BellAlertIcon"
                />

                <InfoCard
                    title="Maintenance Notices"
                    :value="String(stats.maintenance_count || 0)"
                    badge="Scheduled"
                    badgeType="info"
                    color="sky"
                    :icon="WrenchIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Type Tabs -->
            <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        v-for="t in ['all', 'general', 'maintenance', 'incident', 'feature']"
                        :key="t"
                        type="button"
                        @click="selectType(t)"
                        :class="currentType === t ? 'bg-blue-600 text-white' : 'bg-white text-slate-700 hover:bg-slate-50 border border-slate-200'"
                        class="px-3 py-1.5 rounded-[3px] font-bold text-xs uppercase font-mono transition cursor-pointer shadow-2xs"
                    >
                        {{ t }}
                    </button>
                </div>
            </div>

            <!-- 4. Standard Filter Toolbar -->
            <DataTableFilter
                v-model="search"
                searchPlaceholder="Search announcement headline, summary, or content..."
                @search="applyFilters"
                @filter="applyFilters"
                @reset="resetFilters"
            >
                <FilterSelect
                    v-model="currentStatus"
                    label="Publication Status"
                    :options="[
                        { label: 'All Statuses', value: 'all' },
                        { label: 'Published Live', value: 'published' },
                        { label: 'Draft / Hidden', value: 'draft' }
                    ]"
                    placeholder="All Statuses"
                />
            </DataTableFilter>

            <!-- 5. Standard Data Table -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-12">#</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Headline Title</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Category Type</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Severity</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Banner Alert</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Published</th>
                                <th class="py-2.5 px-3 border-r border-slate-200">Created At</th>
                                <th class="py-2.5 px-3 w-28">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <tr v-for="(ann, index) in announcements.data" :key="ann.id || index" class="hover:bg-blue-50/30 transition">
                                <td class="py-2.5 px-3 border-r border-slate-100 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-bold text-slate-900 max-w-sm truncate" :title="ann.title">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs border border-blue-100 shrink-0">
                                            <MegaphoneIcon class="w-3.5 h-3.5" />
                                        </div>
                                        <span>{{ ann.title }}</span>
                                    </div>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase bg-slate-100 text-slate-700 border border-slate-200 font-mono">
                                        {{ ann.type || 'General' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="[
                                            ann.severity === 'critical' || ann.severity === 'danger' ? 'bg-rose-50 text-rose-700 border-rose-200' :
                                            ann.severity === 'warning' ? 'bg-amber-50 text-amber-700 border-amber-200' :
                                            'bg-blue-50 text-blue-700 border-blue-200'
                                        ]"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono"
                                    >
                                        {{ ann.severity || 'info' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[11px]">
                                    <span v-if="ann.show_banner" class="text-amber-600 font-bold">Banner 🔔</span>
                                    <span v-else class="text-slate-400">-</span>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <span 
                                        :class="ann.is_published ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-500 border-slate-200'"
                                        class="px-2 py-0.5 rounded-[3px] text-[10px] font-bold uppercase border font-mono"
                                    >
                                        {{ ann.is_published ? 'Published' : 'Draft' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[10.5px] text-slate-500">
                                    {{ ann.created_at ? new Date(ann.created_at).toLocaleDateString() : 'Recent' }}
                                </td>
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1">
                                        <RowActionDropdown>
                                            <button
                                                type="button"
                                                @click="openEditModal(ann)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <PencilSquareIcon class="w-3.5 h-3.5 text-slate-500" />
                                                <span>Edit Bulletin</span>
                                            </button>

                                            <button
                                                type="button"
                                                @click="togglePublish(ann)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <BoltIcon class="w-3.5 h-3.5 text-slate-500" />
                                                <span>{{ ann.is_published ? 'Unpublish (Draft)' : 'Publish Live' }}</span>
                                            </button>

                                            <button
                                                type="button"
                                                @click="openDeleteModal(ann)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-rose-600 hover:bg-rose-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <TrashIcon class="w-3.5 h-3.5" />
                                                <span>Delete Bulletin</span>
                                            </button>
                                        </RowActionDropdown>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!announcements.data || announcements.data.length === 0">
                                <td colspan="8" class="py-12 text-center text-slate-400 font-sans">
                                    No client announcements published.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- CREATE / EDIT MODAL -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-lg overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <MegaphoneIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            {{ isEditing ? 'Edit Client Announcement' : 'Publish Broadcast Advisory' }}
                        </h3>
                    </div>
                    <button @click="showModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitAnnForm" class="p-4 space-y-3 text-xs">
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Advisory Title <span class="text-rose-500">*</span></label>
                        <input v-model="annForm.title" type="text" required placeholder="Scheduled Maintenance Window: Node US-EAST-01" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Category Type</label>
                            <select v-model="annForm.type" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                                <option value="general">General Update</option>
                                <option value="maintenance">Scheduled Maintenance</option>
                                <option value="incident">Service Incident</option>
                                <option value="feature">New Product Feature</option>
                            </select>
                        </div>

                        <div class="space-y-1">
                            <label class="block font-bold text-slate-700">Severity Level</label>
                            <select v-model="annForm.severity" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                                <option value="info">Informational (Blue)</option>
                                <option value="warning">Warning Notice (Amber)</option>
                                <option value="critical">Critical Urgent (Red)</option>
                            </select>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Brief Summary</label>
                        <input v-model="annForm.summary" type="text" placeholder="Short description for list previews..." class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Advisory Content / Message <span class="text-rose-500">*</span></label>
                        <textarea v-model="annForm.content" rows="4" required placeholder="Detailed information for customer portal..." class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"></textarea>
                    </div>

                    <div class="flex flex-wrap items-center gap-4 pt-1">
                        <div class="flex items-center gap-1.5">
                            <input v-model="annForm.is_published" id="ann_pub" type="checkbox" class="rounded-[2px] text-blue-600 focus:ring-blue-500 cursor-pointer" />
                            <label for="ann_pub" class="text-xs text-slate-700 font-medium cursor-pointer">Publish live to portal</label>
                        </div>

                        <div class="flex items-center gap-1.5">
                            <input v-model="annForm.show_banner" id="ann_ban" type="checkbox" class="rounded-[2px] text-blue-600 focus:ring-blue-500 cursor-pointer" />
                            <label for="ann_ban" class="text-xs text-slate-700 font-medium cursor-pointer">Show as top banner alert</label>
                        </div>
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
                            :disabled="annForm.processing"
                            class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                        >
                            {{ annForm.processing ? 'Saving...' : (isEditing ? 'Save Changes' : 'Publish Advisory') }}
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
                            Delete Advisory Announcement
                        </h3>
                    </div>
                    <button @click="showDeleteModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="p-4 space-y-2.5 text-xs">
                    <p class="text-slate-600">
                        Are you sure you want to delete announcement <strong class="text-slate-900 font-mono">[{{ annToDelete?.title }}]</strong>?
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
                        {{ deleteForm.processing ? 'Deleting...' : 'Delete Announcement' }}
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
