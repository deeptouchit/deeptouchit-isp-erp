<script setup>
import { ref, computed, watch } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import RefreshButton from '@/Components/UI/RefreshButton.vue'
import InfoCardsGrid from '@/Components/UI/InfoCardsGrid.vue'
import InfoCard from '@/Components/UI/InfoCard.vue'
import DataTableFilter from '@/Components/UI/DataTableFilter.vue'
import RowActionDropdown from '@/Components/UI/RowActionDropdown.vue'

import {
    FolderIcon,
    DocumentIcon,
    DocumentTextIcon,
    PlusIcon,
    FolderPlusIcon,
    ArrowUpTrayIcon,
    ArrowDownTrayIcon,
    TrashIcon,
    PencilSquareIcon,
    ArrowPathIcon,
    XMarkIcon,
    CheckIcon,
    LockClosedIcon,
    ServerIcon,
    CodeBracketIcon,
    ArchiveBoxIcon,
    ChevronRightIcon,
    EyeIcon,
    ArrowUturnLeftIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    scope: {
        type: String,
        default: 'vhosts',
    },
    currentPath: {
        type: String,
        default: '',
    },
    breadcrumbs: {
        type: Array,
        default: () => [],
    },
    items: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({
            total_items: 0,
            directories_count: 0,
            files_count: 0,
            total_size: 0,
            total_size_formatted: '0 B',
            base_path: '/var/www/vhosts',
        }),
    },
    availableScopes: {
        type: Array,
        default: () => [],
    },
    subscriptions: {
        type: Array,
        default: () => [],
    },
})

// Search, Filter & Selection State
const search = ref('')
const selectedScope = ref(props.scope)
const selectedItems = ref([])
const feedbackMsg = ref('')

const filteredItems = computed(() => {
    if (!search.value.trim()) return props.items
    const s = search.value.toLowerCase().trim()
    return props.items.filter(i => i.name.toLowerCase().includes(s))
})

const isAllSelected = computed(() => {
    return filteredItems.value.length > 0 && selectedItems.value.length === filteredItems.value.length
})

const toggleSelectAll = () => {
    if (isAllSelected.value) {
        selectedItems.value = []
    } else {
        selectedItems.value = filteredItems.value.map(i => i.name)
    }
}

const toggleSelectItem = (name) => {
    const idx = selectedItems.value.indexOf(name)
    if (idx > -1) {
        selectedItems.value.splice(idx, 1)
    } else {
        selectedItems.value.push(name)
    }
}

// Navigation
const navigateTo = (path) => {
    router.get(
        route('admin.files.manager'),
        { scope: selectedScope.value, path: path },
        { preserveState: true }
    )
}

const changeScope = () => {
    router.get(
        route('admin.files.manager'),
        { scope: selectedScope.value, path: '' },
        { preserveState: false }
    )
}

// 1. CREATE FOLDER MODAL
const showFolderModal = ref(false)
const folderForm = useForm({
    scope: props.scope,
    path: props.currentPath,
    folder_name: '',
})

const openFolderModal = () => {
    folderForm.folder_name = ''
    folderForm.scope = selectedScope.value
    folderForm.path = props.currentPath
    showFolderModal.value = true
}

const submitFolder = () => {
    folderForm.post(route('admin.files.manager.create-folder'), {
        preserveScroll: true,
        onSuccess: () => {
            showFolderModal.value = false
            feedbackMsg.value = `Folder '${folderForm.folder_name}' created.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 2. CREATE FILE MODAL
const showFileModal = ref(false)
const fileForm = useForm({
    scope: props.scope,
    path: props.currentPath,
    file_name: '',
})

const openFileModal = () => {
    fileForm.file_name = ''
    fileForm.scope = selectedScope.value
    fileForm.path = props.currentPath
    showFileModal.value = true
}

const submitFile = () => {
    fileForm.post(route('admin.files.manager.create-file'), {
        preserveScroll: true,
        onSuccess: () => {
            showFileModal.value = false
            feedbackMsg.value = `File '${fileForm.file_name}' created.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 3. UPLOAD FILE MODAL
const showUploadModal = ref(false)
const uploadForm = useForm({
    scope: props.scope,
    path: props.currentPath,
    files: [],
})

const openUploadModal = () => {
    uploadForm.files = []
    uploadForm.scope = selectedScope.value
    uploadForm.path = props.currentPath
    showUploadModal.value = true
}

const handleFileSelect = (e) => {
    uploadForm.files = Array.from(e.target.files)
}

const submitUpload = () => {
    uploadForm.post(route('admin.files.manager.upload'), {
        preserveScroll: true,
        onSuccess: () => {
            showUploadModal.value = false
            feedbackMsg.value = `Files uploaded successfully.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 4. IN-BROWSER CODE EDITOR MODAL
const showEditorModal = ref(false)
const editorItem = ref(null)
const editorContent = ref('')
const isSavingEditor = ref(false)
const isLoadingEditor = ref(false)

const openEditor = async (item) => {
    editorItem.value = item
    editorContent.value = 'Loading file contents...'
    showEditorModal.value = true
    isLoadingEditor.value = true

    try {
        const res = await fetch(`${route('admin.files.manager.edit')}?scope=${selectedScope.value}&path=${props.currentPath}&file=${item.name}`)
        const data = await res.json()
        editorContent.value = data.content || ''
    } catch (e) {
        editorContent.value = 'Failed to load file contents.'
    } finally {
        isLoadingEditor.value = false
    }
}

const saveEditor = async () => {
    if (!editorItem.value) return
    isSavingEditor.value = true

    try {
        const res = await fetch(route('admin.files.manager.save'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify({
                scope: selectedScope.value,
                path: props.currentPath,
                file: editorItem.value.name,
                content: editorContent.value,
            })
        })
        const data = await res.json()
        if (data.success) {
            feedbackMsg.value = `File '${editorItem.value.name}' saved.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    } catch (e) {
        alert('Failed to save file.')
    } finally {
        isSavingEditor.value = false
    }
}

// 5. RENAME MODAL
const showRenameModal = ref(false)
const renameItem = ref(null)
const renameForm = useForm({
    scope: props.scope,
    path: props.currentPath,
    old_name: '',
    new_name: '',
})

const openRenameModal = (item) => {
    renameItem.value = item
    renameForm.scope = selectedScope.value
    renameForm.path = props.currentPath
    renameForm.old_name = item.name
    renameForm.new_name = item.name
    showRenameModal.value = true
}

const submitRename = () => {
    renameForm.post(route('admin.files.manager.rename'), {
        preserveScroll: true,
        onSuccess: () => {
            showRenameModal.value = false
            feedbackMsg.value = `Renamed to '${renameForm.new_name}'.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 6. PERMISSIONS (CHMOD) MODAL
const showPermModal = ref(false)
const permItem = ref(null)
const permForm = useForm({
    scope: props.scope,
    path: props.currentPath,
    name: '',
    permissions: '0755',
})

const openPermModal = (item) => {
    permItem.value = item
    permForm.scope = selectedScope.value
    permForm.path = props.currentPath
    permForm.name = item.name
    permForm.permissions = item.permissions || '0755'
    showPermModal.value = true
}

const submitPerm = () => {
    permForm.post(route('admin.files.manager.permissions'), {
        preserveScroll: true,
        onSuccess: () => {
            showPermModal.value = false
            feedbackMsg.value = `Permissions for '${permForm.name}' updated.`
            setTimeout(() => { feedbackMsg.value = '' }, 4000)
        }
    })
}

// 7. COMPRESS & EXTRACT
const compressSelected = () => {
    const itemsToCompress = selectedItems.value.length > 0 ? selectedItems.value : (filteredItems.value.map(i => i.name))
    if (!itemsToCompress.length) return
    const zipName = prompt('Enter archive name (.zip or .tar.gz):', 'archive.zip')
    if (zipName) {
        useForm({
            scope: selectedScope.value,
            path: props.currentPath,
            items: itemsToCompress,
            archive_name: zipName,
        }).post(route('admin.files.manager.compress'), {
            preserveScroll: true,
            onSuccess: () => {
                feedbackMsg.value = `Archive '${zipName}' created.`
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            }
        })
    }
}

const extractItem = (item) => {
    if (confirm(`Extract '${item.name}' into current directory?`)) {
        useForm({
            scope: selectedScope.value,
            path: props.currentPath,
            archive_name: item.name,
        }).post(route('admin.files.manager.extract'), {
            preserveScroll: true,
            onSuccess: () => {
                feedbackMsg.value = `Archive '${item.name}' extracted.`
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            }
        })
    }
}

// 8. DELETE ITEMS
const deleteItem = (item) => {
    if (confirm(`Are you sure you want to delete '${item.name}'?`)) {
        useForm({
            scope: selectedScope.value,
            path: props.currentPath,
            items: [item.name],
        }).delete(route('admin.files.manager.delete'), {
            preserveScroll: true,
            onSuccess: () => {
                feedbackMsg.value = `'${item.name}' deleted.`
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            }
        })
    }
}

const deleteSelected = () => {
    if (!selectedItems.value.length) return
    if (confirm(`Delete ${selectedItems.value.length} selected items?`)) {
        useForm({
            scope: selectedScope.value,
            path: props.currentPath,
            items: selectedItems.value,
        }).delete(route('admin.files.manager.delete'), {
            preserveScroll: true,
            onSuccess: () => {
                selectedItems.value = []
                feedbackMsg.value = `Selected items deleted.`
                setTimeout(() => { feedbackMsg.value = '' }, 4000)
            }
        })
    }
}
</script>

<template>
    <Head title="File Manager - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Services', href: '#' },
                    { label: 'Files & Storage', href: route('admin.files.manager') },
                    { label: 'Enterprise File Manager' }
                ]"
            >
                <template #actions>
                    <!-- Scope Switcher -->
                    <div class="flex items-center gap-1.5 bg-white px-2.5 py-1.5 rounded-[3px] border border-slate-200 shadow-2xs text-xs">
                        <span class="font-bold text-slate-500">Root Scope:</span>
                        <select 
                            v-model="selectedScope"
                            @change="changeScope"
                            class="bg-blue-50 border border-blue-200 text-xs font-bold text-blue-700 rounded-[2px] py-0.5 px-2 focus:ring-1 focus:ring-blue-500 cursor-pointer outline-none"
                        >
                            <option value="vhosts">Virtual Hosts (/var/www/vhosts)</option>
                            <option value="system">Web Root (/var/www/html)</option>
                            <option value="backups">Backups (/var/backups)</option>
                            <option value="logs">System Logs (/var/log)</option>
                        </select>
                    </div>

                    <button 
                        type="button" 
                        @click="openFolderModal"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <FolderPlusIcon class="w-3.5 h-3.5 text-blue-600" />
                        <span>New Folder</span>
                    </button>

                    <button 
                        type="button" 
                        @click="openFileModal"
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <PlusIcon class="w-3.5 h-3.5 text-blue-600" />
                        <span>New File</span>
                    </button>

                    <button 
                        type="button" 
                        @click="openUploadModal"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                    >
                        <ArrowUpTrayIcon class="w-3.5 h-3.5" />
                        <span>Upload Files</span>
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
                    title="Directory Items"
                    :value="String(stats.total_items || items.length || 0)"
                    badge="Objects"
                    badgeType="info"
                    color="blue"
                    :icon="FolderIcon"
                />

                <InfoCard
                    title="Folder Subdirectories"
                    :value="String(stats.directories_count || 0)"
                    badge="Directories"
                    badgeType="success"
                    color="emerald"
                    :icon="FolderPlusIcon"
                />

                <InfoCard
                    title="Document Files"
                    :value="String(stats.files_count || 0)"
                    badge="Files"
                    badgeType="info"
                    color="purple"
                    :icon="DocumentTextIcon"
                />

                <InfoCard
                    title="Directory Size"
                    :value="stats.total_size_formatted || '0 B'"
                    badge="Calculated"
                    badgeType="warning"
                    color="sky"
                    :icon="ServerIcon"
                />
            </InfoCardsGrid>

            <!-- 3. Dynamic Breadcrumb & Path Jump Bar -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] p-2.5 shadow-2xs flex flex-wrap items-center justify-between gap-2 text-xs">
                <div class="flex items-center gap-1.5 font-mono text-slate-700">
                    <span class="text-slate-400">Path:</span>
                    <button 
                        type="button" 
                        @click="navigateTo('')"
                        class="px-2 py-0.5 rounded-[2px] bg-slate-100 hover:bg-slate-200 text-blue-700 font-bold cursor-pointer"
                    >
                        /
                    </button>
                    <template v-for="(crumb, ci) in breadcrumbs" :key="ci">
                        <span class="text-slate-300">/</span>
                        <button 
                            type="button" 
                            @click="navigateTo(crumb.path)"
                            class="px-2 py-0.5 rounded-[2px] hover:bg-slate-100 text-blue-700 font-bold cursor-pointer"
                        >
                            {{ crumb.name }}
                        </button>
                    </template>
                </div>

                <!-- Batch Actions -->
                <div v-if="selectedItems.length > 0" class="flex items-center gap-2">
                    <span class="font-bold text-blue-700 text-[11px]">{{ selectedItems.length }} items selected</span>
                    <button type="button" @click="compressSelected" class="px-2 py-1 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-[2px] font-bold text-[11px] cursor-pointer">Zip 🗜️</button>
                    <button type="button" @click="deleteSelected" class="px-2 py-1 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-[2px] font-bold text-[11px] cursor-pointer">Delete Selected 🗑️</button>
                </div>
            </div>

            <!-- 4. Standard Filter Toolbar -->
            <DataTableFilter
                v-model="search"
                searchPlaceholder="Search files and folders in current directory..."
                @search="() => {}"
                @filter="() => {}"
                @reset="search = ''"
            />

            <!-- 5. Standard Data Table -->
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-center border-collapse text-xs whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50/90 border-b border-slate-200 text-[10.5px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
                                <th class="py-2.5 px-3 border-r border-slate-200 w-10">
                                    <input type="checkbox" :checked="isAllSelected" @change="toggleSelectAll" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer" />
                                </th>
                                <th class="py-2.5 px-3 border-r border-slate-200 text-left">Object Name</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 w-24">Size</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 w-24">Permissions</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 w-28">Owner : Group</th>
                                <th class="py-2.5 px-3 border-r border-slate-200 w-36">Last Modified</th>
                                <th class="py-2.5 px-3 w-28">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium whitespace-nowrap">
                            <!-- Parent Directory Step Up -->
                            <tr v-if="currentPath" class="hover:bg-slate-50/50 transition">
                                <td class="py-2 px-3 border-r border-slate-100 text-center"></td>
                                <td colspan="6" class="py-2 px-3 text-left whitespace-nowrap">
                                    <button 
                                        type="button" 
                                        @click="navigateTo(currentPath.split('/').slice(0, -1).join('/'))"
                                        class="inline-flex items-center gap-2 font-bold text-blue-600 hover:text-blue-800 font-mono text-xs cursor-pointer"
                                    >
                                        <ArrowUturnLeftIcon class="w-3.5 h-3.5" />
                                        <span>.. (Parent Directory)</span>
                                    </button>
                                </td>
                            </tr>

                            <tr v-for="(item, index) in filteredItems" :key="item.name || index" class="hover:bg-blue-50/30 transition">
                                <!-- Checkbox -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center">
                                    <input type="checkbox" :checked="selectedItems.includes(item.name)" @change="toggleSelectItem(item.name)" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer" />
                                </td>

                                <!-- Name & Icon -->
                                <td class="py-2.5 px-3 border-r border-slate-100 text-left whitespace-nowrap font-mono">
                                    <div class="flex items-center gap-2">
                                        <div 
                                            :class="item.is_dir ? 'bg-amber-50 text-amber-600 border-amber-200' : 'bg-blue-50 text-blue-600 border-blue-200'"
                                            class="w-6 h-6 rounded-[3px] flex items-center justify-center font-bold text-xs border shrink-0"
                                        >
                                            <FolderIcon v-if="item.is_dir" class="w-3.5 h-3.5" />
                                            <ArchiveBoxIcon v-else-if="item.is_archive" class="w-3.5 h-3.5 text-purple-600" />
                                            <DocumentTextIcon v-else class="w-3.5 h-3.5" />
                                        </div>

                                        <button 
                                            v-if="item.is_dir"
                                            type="button" 
                                            @click="navigateTo(item.path || (currentPath ? `${currentPath}/${item.name}` : item.name))"
                                            class="font-bold text-slate-900 hover:text-blue-600 hover:underline cursor-pointer"
                                        >
                                            {{ item.name }}
                                        </button>
                                        <button
                                            v-else-if="item.is_editable"
                                            type="button"
                                            @click="openEditor(item)"
                                            class="text-slate-800 hover:text-blue-600 hover:underline cursor-pointer"
                                        >
                                            {{ item.name }}
                                        </button>
                                        <span v-else class="text-slate-800">{{ item.name }}</span>
                                    </div>
                                </td>

                                <!-- Size -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[10.5px] text-slate-500">
                                    {{ item.is_dir ? '-' : item.size_formatted }}
                                </td>

                                <!-- Permissions -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[10.5px] text-slate-600">
                                    <button @click="openPermModal(item)" class="hover:underline hover:text-blue-600 cursor-pointer">
                                        {{ item.permissions || '0755' }}
                                    </button>
                                </td>

                                <!-- Owner -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[10.5px] text-slate-500">
                                    {{ item.owner || 'www-data' }}:{{ item.group || 'www-data' }}
                                </td>

                                <!-- Modified -->
                                <td class="py-2.5 px-3 border-r border-slate-100 whitespace-nowrap text-center font-mono text-[10.5px] text-slate-400">
                                    {{ item.modified_at || '-' }}
                                </td>

                                <!-- Action -->
                                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1">
                                        <button 
                                            v-if="item.is_editable"
                                            type="button" 
                                            @click="openEditor(item)"
                                            class="px-2 py-1 bg-white hover:bg-slate-50 text-blue-700 font-bold rounded-[3px] text-xs border border-slate-200 shadow-2xs transition cursor-pointer"
                                            title="Edit File"
                                        >
                                            Edit ✏️
                                        </button>
                                        <button 
                                            v-else-if="item.is_dir"
                                            type="button" 
                                            @click="navigateTo(item.path || (currentPath ? `${currentPath}/${item.name}` : item.name))"
                                            class="px-2 py-1 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs border border-slate-200 shadow-2xs transition cursor-pointer"
                                            title="Open Folder"
                                        >
                                            Open 📂
                                        </button>

                                        <RowActionDropdown>
                                            <button
                                                v-if="item.is_editable"
                                                type="button"
                                                @click="openEditor(item)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <CodeBracketIcon class="w-3.5 h-3.5 text-slate-500" />
                                                <span>Edit in Code Editor</span>
                                            </button>

                                            <button
                                                v-if="item.is_archive"
                                                type="button"
                                                @click="extractItem(item)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-purple-700 hover:bg-purple-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <ArchiveBoxIcon class="w-3.5 h-3.5 text-purple-500" />
                                                <span>Extract Archive Here</span>
                                            </button>

                                            <a
                                                v-if="!item.is_dir"
                                                :href="`${route('admin.files.manager.download')}?scope=${selectedScope}&path=${currentPath}&file=${item.name}`"
                                                download
                                                class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <ArrowDownTrayIcon class="w-3.5 h-3.5 text-slate-500" />
                                                <span>Download File</span>
                                            </a>

                                            <button
                                                type="button"
                                                @click="openRenameModal(item)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <PencilSquareIcon class="w-3.5 h-3.5 text-slate-500" />
                                                <span>Rename Object</span>
                                            </button>

                                            <button
                                                type="button"
                                                @click="openPermModal(item)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <LockClosedIcon class="w-3.5 h-3.5 text-slate-500" />
                                                <span>Change Permissions (chmod)</span>
                                            </button>

                                            <button
                                                type="button"
                                                @click="deleteItem(item)"
                                                class="w-full text-left px-3 py-1.5 text-xs text-rose-600 hover:bg-rose-50 flex items-center gap-2 font-medium cursor-pointer"
                                            >
                                                <TrashIcon class="w-3.5 h-3.5" />
                                                <span>Delete Object</span>
                                            </button>
                                        </RowActionDropdown>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!filteredItems || filteredItems.length === 0">
                                <td colspan="7" class="py-12 text-center text-slate-400">
                                    Empty directory.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 1. CREATE FOLDER MODAL -->
        <div v-if="showFolderModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <FolderPlusIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Create New Folder
                        </h3>
                    </div>
                    <button @click="showFolderModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitFolder" class="p-4 space-y-3 text-xs">
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Folder Name <span class="text-rose-500">*</span></label>
                        <input v-model="folderForm.folder_name" type="text" required placeholder="e.g. public_html or assets" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    </div>

                    <div class="px-4 py-2.5 -mx-4 -mb-4 mt-4 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end gap-2 text-xs">
                        <button
                            type="button"
                            @click="showFolderModal = false"
                            class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="folderForm.processing"
                            class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                        >
                            {{ folderForm.processing ? 'Creating...' : 'Create Folder' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 2. CREATE FILE MODAL -->
        <div v-if="showFileModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <PlusIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Create New File
                        </h3>
                    </div>
                    <button @click="showFileModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitFile" class="p-4 space-y-3 text-xs">
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">File Name <span class="text-rose-500">*</span></label>
                        <input v-model="fileForm.file_name" type="text" required placeholder="e.g. index.php or .htaccess" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    </div>

                    <div class="px-4 py-2.5 -mx-4 -mb-4 mt-4 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end gap-2 text-xs">
                        <button
                            type="button"
                            @click="showFileModal = false"
                            class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="fileForm.processing"
                            class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                        >
                            {{ fileForm.processing ? 'Creating...' : 'Create File' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 3. UPLOAD MODAL -->
        <div v-if="showUploadModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <ArrowUpTrayIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Upload Files to {{ currentPath || '/' }}
                        </h3>
                    </div>
                    <button @click="showUploadModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitUpload" class="p-4 space-y-3 text-xs">
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Select Files <span class="text-rose-500">*</span></label>
                        <input type="file" multiple @change="handleFileSelect" required class="w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-[3px] file:border-0 file:text-xs file:font-bold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer" />
                    </div>

                    <div class="px-4 py-2.5 -mx-4 -mb-4 mt-4 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end gap-2 text-xs">
                        <button
                            type="button"
                            @click="showUploadModal = false"
                            class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="uploadForm.processing"
                            class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                        >
                            {{ uploadForm.processing ? 'Uploading...' : 'Start Upload' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 4. IN-BROWSER CODE EDITOR MODAL -->
        <div v-if="showEditorModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/70 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-2xl w-full max-w-4xl h-[85vh] flex flex-col overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/80">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <CodeBracketIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide font-mono">
                            Editing: {{ editorItem?.name }}
                        </h3>
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="saveEditor"
                            :disabled="isSavingEditor"
                            class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] text-xs shadow-2xs transition cursor-pointer disabled:opacity-50"
                        >
                            {{ isSavingEditor ? 'Saving...' : 'Save File' }}
                        </button>
                        <button @click="showEditorModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                            <XMarkIcon class="w-4 h-4" />
                        </button>
                    </div>
                </div>

                <div class="flex-1 p-2 bg-slate-900 overflow-hidden">
                    <textarea 
                        v-model="editorContent" 
                        class="w-full h-full p-3 bg-transparent text-emerald-400 font-mono text-xs focus:outline-none resize-none leading-relaxed"
                        spellcheck="false"
                    ></textarea>
                </div>

                <div class="px-4 py-2 bg-slate-800 border-t border-slate-700 flex items-center justify-between text-[11px] font-mono text-slate-400">
                    <span>Path: {{ currentPath ? `${currentPath}/${editorItem?.name}` : editorItem?.name }}</span>
                    <span>UTF-8 • UNIX (LF)</span>
                </div>
            </div>
        </div>

        <!-- 5. RENAME MODAL -->
        <div v-if="showRenameModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <PencilSquareIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Rename Object
                        </h3>
                    </div>
                    <button @click="showRenameModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitRename" class="p-4 space-y-3 text-xs">
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">New Object Name <span class="text-rose-500">*</span></label>
                        <input v-model="renameForm.new_name" type="text" required class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                    </div>

                    <div class="px-4 py-2.5 -mx-4 -mb-4 mt-4 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end gap-2 text-xs">
                        <button
                            type="button"
                            @click="showRenameModal = false"
                            class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="renameForm.processing"
                            class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                        >
                            {{ renameForm.processing ? 'Renaming...' : 'Rename' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 6. PERMISSIONS (CHMOD) MODAL -->
        <div v-if="showPermModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-[4px] border border-[#E2E8F0] shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <LockClosedIcon class="w-3.5 h-3.5" />
                        </div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                            Change Permissions: {{ permItem?.name }}
                        </h3>
                    </div>
                    <button @click="showPermModal = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitPerm" class="p-4 space-y-3 text-xs">
                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700">Octal Permissions (chmod) <span class="text-rose-500">*</span></label>
                        <input v-model="permForm.permissions" type="text" required placeholder="0755 or 0644" class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500" />
                        <span class="text-[10px] text-slate-400 block">Recommended: Directories (0755), Standard Files (0644), Configs (0600)</span>
                    </div>

                    <div class="px-4 py-2.5 -mx-4 -mb-4 mt-4 bg-slate-50/70 border-t border-slate-100 flex items-center justify-end gap-2 text-xs">
                        <button
                            type="button"
                            @click="showPermModal = false"
                            class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] border border-slate-200 shadow-2xs transition cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="permForm.processing"
                            class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] transition shadow-2xs cursor-pointer disabled:opacity-50"
                        >
                            {{ permForm.processing ? 'Updating...' : 'Set Permissions' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
