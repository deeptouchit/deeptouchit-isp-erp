<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import { 
    FolderIcon, 
    DocumentIcon, 
    ArrowLeftIcon, 
    ArrowUpIcon,
    HomeIcon,
    PlusIcon, 
    FolderPlusIcon, 
    DocumentPlusIcon,
    ArrowUpTrayIcon, 
    ArrowDownTrayIcon,
    PencilSquareIcon, 
    TrashIcon, 
    ArchiveBoxIcon, 
    ArrowPathIcon,
    XMarkIcon,
    CheckIcon,
    CodeBracketIcon,
    CommandLineIcon,
    ShieldCheckIcon,
    ExclamationTriangleIcon,
    MagnifyingGlassIcon,
    Squares2X2Icon,
    ListBulletIcon,
    KeyIcon,
    ArrowsRightLeftIcon,
    DocumentDuplicateIcon,
    PhotoIcon,
    EyeIcon,
    ChevronRightIcon,
    ArrowDownCircleIcon,
    ScissorsIcon,
    ClipboardDocumentCheckIcon,
    ClipboardDocumentIcon
} from '@heroicons/vue/24/outline'
import axios from 'axios'

const props = defineProps({
    subscriptions: {
        type: Array,
        default: () => []
    },
    currentSubscriptionId: {
        type: Number,
        default: null
    },
    initialPath: {
        type: String,
        default: '/'
    },
    initialFiles: {
        type: Object,
        default: () => ({ current_path: '/', items: [], total_items: 0, total_dirs: 0, total_files: 0, total_size: 0 })
    }
})

// Current state
const selectedSubId = ref(props.currentSubscriptionId || props.subscriptions[0]?.id || null)
const currentPath = ref(props.initialFiles?.current_path || props.initialPath || '/')
const files = ref(props.initialFiles?.items || [])
const dirSummary = ref({
    total_items: props.initialFiles?.total_items || 0,
    total_dirs: props.initialFiles?.total_dirs || 0,
    total_files: props.initialFiles?.total_files || 0,
    total_size: props.initialFiles?.total_size || 0,
})

const loading = ref(false)
const searchQuery = ref('')
const viewMode = ref('table') // 'table' | 'grid'
const notification = ref({ show: false, message: '', type: 'success' })

// Selection State
const selectedPaths = ref([])
const selectAll = ref(false)

// Clipboard state (Copy / Cut / Paste)
const clipboard = ref({
    action: null, // 'copy' | 'cut' | null
    items: [],    // Array of string paths
    itemNames: [], // Array of file names
})

// Context Menu State (Right-Click)
const contextMenu = ref({
    show: false,
    x: 0,
    y: 0,
    item: null, // Item object or null if clicked on empty canvas
})

// Modals state
const showUploadModal = ref(false)
const showCreateFolderModal = ref(false)
const showCreateFileModal = ref(false)
const showRenameModal = ref(false)
const showEditorModal = ref(false)
const showDeleteModal = ref(false)
const showUnzipModal = ref(false)
const showChmodModal = ref(false)
const showMoveCopyModal = ref(false)
const showMediaPreviewModal = ref(false)

// Active targets for modals
const selectedItem = ref(null)
const deleteTargetItem = ref(null)
const deleting = ref(false)
const unzipTargetItem = ref(null)
const unzipping = ref(false)
const newFolderName = ref('')
const newFileName = ref('')
const renameNewName = ref('')
const previewMediaItem = ref(null)

// Chmod modal state
const chmodForm = ref({ path: '', name: '', perms: '0755', recursive: false, saving: false })
// Move/Copy modal state
const moveCopyForm = ref({ source_path: '', target_dir: '', is_copy: false, saving: false })

// Code Editor state
const editorFile = ref({ 
    name: '', 
    path: '', 
    content: '', 
    loading: false, 
    saving: false, 
    isFullScreen: false,
    fontSize: 13,
    lineCount: 1 
})

// Upload state
const uploadFilesList = ref([])
const uploading = ref(false)
const uploadProgress = ref(0)
const isDragging = ref(false)

// Notification helper
const notify = (msg, type = 'success') => {
    notification.value = { show: true, message: msg, type }
    setTimeout(() => {
        notification.value.show = false
    }, 3500)
}

// Filtered Files list
const filteredFiles = computed(() => {
    if (!searchQuery.value.trim()) return files.value
    const q = searchQuery.value.toLowerCase().trim()
    return files.value.filter(f => f.name.toLowerCase().includes(q))
})

// Breadcrumb segments
const breadcrumbs = computed(() => {
    const parts = currentPath.value.split('/').filter(Boolean)
    const crumbs = [{ name: 'Root', path: '/' }]
    let cumulative = ''
    for (const part of parts) {
        cumulative += '/' + part
        crumbs.push({ name: part, path: cumulative })
    }
    return crumbs
})

// Load files from backend
const loadFiles = async (targetPath = null) => {
    loading.value = true
    selectedPaths.value = []
    selectAll.value = false
    closeContextMenu()
    if (targetPath !== null) {
        currentPath.value = targetPath
    }
    try {
        const resp = await axios.get(route('file.list'), {
            params: {
                subscription_id: selectedSubId.value,
                path: currentPath.value,
            }
        })
        currentPath.value = resp.data.current_path
        files.value = resp.data.items
        dirSummary.value = {
            total_items: resp.data.total_items ?? resp.data.items.length,
            total_dirs: resp.data.total_dirs ?? resp.data.items.filter(i => i.type === 'dir').length,
            total_files: resp.data.total_files ?? resp.data.items.filter(i => i.type === 'file').length,
            total_size: resp.data.total_size ?? 0,
        }
    } catch (err) {
        notify('Failed to load files: ' + (err.response?.data?.error || err.message), 'error')
    } finally {
        loading.value = false
    }
}

// Subscription switch
const switchSubscription = (id) => {
    selectedSubId.value = id
    currentPath.value = '/'
    loadFiles()
}

// Navigation helpers
const navigateTo = (item) => {
    closeContextMenu()
    if (item.type === 'dir') {
        loadFiles(item.path)
    } else if (item.is_editable) {
        openEditor(item)
    } else if (item.is_image || item.is_media) {
        openMediaPreview(item)
    } else {
        downloadFile(item)
    }
}

const goUp = () => {
    if (currentPath.value === '/') return
    const parts = currentPath.value.split('/').filter(Boolean)
    parts.pop()
    const parentPath = parts.length > 0 ? '/' + parts.join('/') : '/'
    loadFiles(parentPath)
}

const jumpToPublicHtml = () => {
    const currentSub = props.subscriptions.find(s => s.id === selectedSubId.value)
    if (currentSub) {
        loadFiles(`/${currentSub.domain}/public_html`)
    }
}

// Context Menu (Right-Click) Handlers
const openContextMenu = (e, item = null) => {
    e.preventDefault()
    e.stopPropagation()
    
    if (item && !selectedPaths.value.includes(item.path)) {
        selectedPaths.value = [item.path]
    }
    
    // Position bounded within window
    const menuWidth = 210
    const menuHeight = item ? 340 : 200
    const x = Math.min(e.clientX, window.innerWidth - menuWidth - 10)
    const y = Math.min(e.clientY, window.innerHeight - menuHeight - 10)

    contextMenu.value = {
        show: true,
        x: Math.max(10, x),
        y: Math.max(10, y),
        item: item,
    }
}

const closeContextMenu = () => {
    contextMenu.value.show = false
}

// Clipboard (Copy / Cut / Paste) Handlers
const handleCopy = (item = null) => {
    const targets = item ? [item.path] : (selectedPaths.value.length > 0 ? selectedPaths.value : [])
    if (targets.length === 0) return
    clipboard.value = {
        action: 'copy',
        items: [...targets],
        itemNames: targets.map(p => p.split('/').pop())
    }
    notify(`Copied ${targets.length} item(s) to clipboard.`)
    closeContextMenu()
}

const handleCut = (item = null) => {
    const targets = item ? [item.path] : (selectedPaths.value.length > 0 ? selectedPaths.value : [])
    if (targets.length === 0) return
    clipboard.value = {
        action: 'cut',
        items: [...targets],
        itemNames: targets.map(p => p.split('/').pop())
    }
    notify(`Cut ${targets.length} item(s) to clipboard.`)
    closeContextMenu()
}

const handlePaste = async (targetDir = null) => {
    const destDir = targetDir || currentPath.value
    if (!clipboard.value.action || clipboard.value.items.length === 0) return
    const isCopy = clipboard.value.action === 'copy'
    loading.value = true
    closeContextMenu()

    try {
        for (const src of clipboard.value.items) {
            const endpoint = isCopy ? route('file.copy') : route('file.move')
            await axios.post(endpoint, {
                subscription_id: selectedSubId.value,
                source_path: src,
                target_dir: destDir
            })
        }
        notify(`Pasted ${clipboard.value.items.length} item(s) into "${destDir}".`)
        if (!isCopy) {
            clipboard.value = { action: null, items: [], itemNames: [] }
        }
        loadFiles()
    } catch (err) {
        notify(err.response?.data?.error || 'Paste failed.', 'error')
    } finally {
        loading.value = false
    }
}

const clearClipboard = () => {
    clipboard.value = { action: null, items: [], itemNames: [] }
    notify('Clipboard cleared.')
}

// Multi-select management
const toggleSelectAll = () => {
    if (selectAll.value) {
        selectedPaths.value = filteredFiles.value.map(f => f.path)
    } else {
        selectedPaths.value = []
    }
}

const toggleSelectItem = (path) => {
    const idx = selectedPaths.value.indexOf(path)
    if (idx > -1) {
        selectedPaths.value.splice(idx, 1)
    } else {
        selectedPaths.value.push(path)
    }
    selectAll.value = selectedPaths.value.length === filteredFiles.value.length && filteredFiles.value.length > 0
}

// Formatters
const formatSize = (bytes) => {
    if (!bytes || bytes === 0) return '—'
    const k = 1024
    const sizes = ['B', 'KB', 'MB', 'GB']
    const i = Math.floor(Math.log(bytes) / Math.log(k))
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i]
}

const formatDate = (timestamp) => {
    if (!timestamp) return '—'
    return new Date(timestamp * 1000).toLocaleDateString('en-US', {
        month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit'
    })
}

// Actions: Create Folder
const handleCreateFolder = async () => {
    if (!newFolderName.value.trim()) return
    try {
        await axios.post(route('file.create-folder'), {
            subscription_id: selectedSubId.value,
            path: currentPath.value,
            name: newFolderName.value.trim()
        })
        newFolderName.value = ''
        showCreateFolderModal.value = false
        notify('Folder created successfully.')
        loadFiles()
    } catch (err) {
        notify(err.response?.data?.error || 'Failed to create folder.', 'error')
    }
}

// Actions: Create File
const handleCreateFile = async () => {
    if (!newFileName.value.trim()) return
    try {
        await axios.post(route('file.create-file'), {
            subscription_id: selectedSubId.value,
            path: currentPath.value,
            name: newFileName.value.trim()
        })
        newFileName.value = ''
        showCreateFileModal.value = false
        notify('File created successfully.')
        loadFiles()
    } catch (err) {
        notify(err.response?.data?.error || 'Failed to create file.', 'error')
    }
}

// Actions: Rename
const openRename = (item) => {
    closeContextMenu()
    selectedItem.value = item
    renameNewName.value = item.name
    showRenameModal.value = true
}

const handleRename = async () => {
    if (!renameNewName.value.trim() || !selectedItem.value) return
    try {
        await axios.post(route('file.rename'), {
            subscription_id: selectedSubId.value,
            old_path: selectedItem.value.path,
            new_name: renameNewName.value.trim()
        })
        showRenameModal.value = false
        notify('Item renamed successfully.')
        loadFiles()
    } catch (err) {
        notify(err.response?.data?.error || 'Failed to rename item.', 'error')
    }
}

// Actions: Chmod Modal
const openChmod = (item) => {
    closeContextMenu()
    chmodForm.value = {
        path: item.path,
        name: item.name,
        perms: item.permissions || '0755',
        recursive: item.type === 'dir',
        saving: false
    }
    showChmodModal.value = true
}

const handleChmod = async () => {
    chmodForm.value.saving = true
    try {
        await axios.post(route('file.chmod'), {
            subscription_id: selectedSubId.value,
            path: chmodForm.value.path,
            permissions: chmodForm.value.perms,
            recursive: chmodForm.value.recursive
        })
        showChmodModal.value = false
        notify('Permissions updated.')
        loadFiles()
    } catch (err) {
        notify(err.response?.data?.error || 'Failed to change permissions.', 'error')
    } finally {
        chmodForm.value.saving = false
    }
}

// Actions: Move / Copy
const openMoveCopy = (item, isCopy = false) => {
    closeContextMenu()
    moveCopyForm.value = {
        source_path: item.path,
        target_dir: currentPath.value,
        is_copy: isCopy,
        saving: false
    }
    showMoveCopyModal.value = true
}

const handleMoveCopy = async () => {
    moveCopyForm.value.saving = true
    const endpoint = moveCopyForm.value.is_copy ? route('file.copy') : route('file.move')
    try {
        await axios.post(endpoint, {
            subscription_id: selectedSubId.value,
            source_path: moveCopyForm.value.source_path,
            target_dir: moveCopyForm.value.target_dir
        })
        showMoveCopyModal.value = false
        notify(moveCopyForm.value.is_copy ? 'Item copied.' : 'Item moved.')
        loadFiles()
    } catch (err) {
        notify(err.response?.data?.error || 'Operation failed.', 'error')
    } finally {
        moveCopyForm.value.saving = false
    }
}

// Actions: Delete Single / Bulk
const confirmDelete = (item) => {
    closeContextMenu()
    deleteTargetItem.value = item
    showDeleteModal.value = true
}

const executeDelete = async () => {
    deleting.value = true
    try {
        if (selectedPaths.value.length > 0 && !deleteTargetItem.value) {
            await axios.post(route('file.bulk-delete'), {
                subscription_id: selectedSubId.value,
                paths: selectedPaths.value
            })
            notify(`${selectedPaths.value.length} items deleted permanently.`)
        } else if (deleteTargetItem.value) {
            await axios.delete(route('file.delete'), {
                data: {
                    subscription_id: selectedSubId.value,
                    path: deleteTargetItem.value.path
                }
            })
            notify(`"${deleteTargetItem.value.name}" deleted permanently.`)
        }
        showDeleteModal.value = false
        deleteTargetItem.value = null
        selectedPaths.value = []
        loadFiles()
    } catch (err) {
        notify(err.response?.data?.error || 'Failed to delete item.', 'error')
    } finally {
        deleting.value = false
    }
}

// Actions: Bulk Zip
const handleBulkZip = async () => {
    if (selectedPaths.value.length === 0) return
    closeContextMenu()
    try {
        await axios.post(route('file.bulk-zip'), {
            subscription_id: selectedSubId.value,
            paths: selectedPaths.value,
            current_path: currentPath.value
        })
        notify('Selected items archived into ZIP.')
        selectedPaths.value = []
        loadFiles()
    } catch (err) {
        notify(err.response?.data?.error || 'Archiving failed.', 'error')
    }
}

// Actions: Code Editor
const openEditor = async (file) => {
    closeContextMenu()
    editorFile.value = {
        name: file.name,
        path: file.path,
        content: '',
        loading: true,
        saving: false,
        isFullScreen: false,
        fontSize: 13,
        lineCount: 1
    }
    showEditorModal.value = true
    try {
        const resp = await axios.get(route('file.edit'), {
            params: {
                subscription_id: selectedSubId.value,
                path: file.path
            }
        })
        editorFile.value.content = resp.data.content
        editorFile.value.lineCount = (resp.data.content.match(/\n/g) || []).length + 1
    } catch (err) {
        notify(err.response?.data?.error || 'Failed to open file in editor.', 'error')
        showEditorModal.value = false
    } finally {
        editorFile.value.loading = false
    }
}

const handleSaveFile = async () => {
    editorFile.value.saving = true
    try {
        await axios.post(route('file.save'), {
            subscription_id: selectedSubId.value,
            path: editorFile.value.path,
            content: editorFile.value.content
        })
        notify('File saved successfully.')
    } catch (err) {
        notify(err.response?.data?.error || 'Failed to save file.', 'error')
    } finally {
        editorFile.value.saving = false
    }
}

// Media Preview Modal
const openMediaPreview = (file) => {
    closeContextMenu()
    previewMediaItem.value = file
    showMediaPreviewModal.value = true
}

// Actions: Download
const downloadFile = (file) => {
    closeContextMenu()
    const url = route('file.download', {
        subscription_id: selectedSubId.value,
        path: file.path
    })
    window.open(url, '_blank')
}

// Actions: UNZIP
const confirmUnzip = (file) => {
    closeContextMenu()
    unzipTargetItem.value = file
    showUnzipModal.value = true
}

const executeUnzip = async () => {
    if (!unzipTargetItem.value) return
    unzipping.value = true
    try {
        await axios.post(route('file.unzip'), {
            subscription_id: selectedSubId.value,
            path: unzipTargetItem.value.path
        })
        notify(`Archive "${unzipTargetItem.value.name}" extracted successfully.`)
        showUnzipModal.value = false
        unzipTargetItem.value = null
        loadFiles()
    } catch (err) {
        notify(err.response?.data?.error || 'Extraction failed.', 'error')
    } finally {
        unzipping.value = false
    }
}

const handleZip = async (item) => {
    closeContextMenu()
    try {
        await axios.post(route('file.zip'), {
            subscription_id: selectedSubId.value,
            path: item.path
        })
        notify('ZIP archive created.')
        loadFiles()
    } catch (err) {
        notify(err.response?.data?.error || 'ZIP creation failed.', 'error')
    }
}

// Actions: Upload
const onFilesSelected = (e) => {
    uploadFilesList.value = Array.from(e.target.files)
}

const handleDrop = (e) => {
    isDragging.value = false
    if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
        uploadFilesList.value = Array.from(e.dataTransfer.files)
        showUploadModal.value = true
    }
}

const handleUpload = async () => {
    if (uploadFilesList.value.length === 0) return
    uploading.value = true
    uploadProgress.value = 0
    const formData = new FormData()
    formData.append('path', currentPath.value)
    if (selectedSubId.value) {
        formData.append('subscription_id', selectedSubId.value)
    }
    for (let i = 0; i < uploadFilesList.value.length; i++) {
        formData.append('files[]', uploadFilesList.value[i])
    }

    try {
        await axios.post(route('file.upload'), formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
            onUploadProgress: (progressEvent) => {
                if (progressEvent.total) {
                    uploadProgress.value = Math.round((progressEvent.loaded * 100) / progressEvent.total)
                }
            }
        })
        showUploadModal.value = false
        uploadFilesList.value = []
        notify('Files uploaded successfully.')
        loadFiles()
    } catch (err) {
        const errorMsg = err.response?.data?.error || err.response?.data?.message || (err.response?.data?.errors ? Object.values(err.response.data.errors).flat().join(', ') : 'Upload failed.')
        notify(errorMsg, 'error')
    } finally {
        uploading.value = false
    }
}

// Global click & key listeners
const handleGlobalClick = () => {
    if (contextMenu.value.show) {
        closeContextMenu()
    }
}

const handleKeyDown = (e) => {
    if (e.key === 'Escape') {
        closeContextMenu()
    }
    if ((e.ctrlKey || e.metaKey) && e.key === 's' && showEditorModal.value) {
        e.preventDefault()
        handleSaveFile()
    }
    if ((e.ctrlKey || e.metaKey) && e.key === 'c' && selectedPaths.value.length > 0 && !showEditorModal.value) {
        handleCopy()
    }
    if ((e.ctrlKey || e.metaKey) && e.key === 'x' && selectedPaths.value.length > 0 && !showEditorModal.value) {
        handleCut()
    }
    if ((e.ctrlKey || e.metaKey) && e.key === 'v' && clipboard.value.items.length > 0 && !showEditorModal.value) {
        handlePaste()
    }
}

onMounted(() => {
    window.addEventListener('click', handleGlobalClick)
    window.addEventListener('keydown', handleKeyDown)
    if (files.value.length === 0) {
        loadFiles()
    }
})

onUnmounted(() => {
    window.removeEventListener('click', handleGlobalClick)
    window.removeEventListener('keydown', handleKeyDown)
})
</script>

<template>
    <Head title="Web File Manager - DeepTouch Cloud" />

    <AuthenticatedLayout>
        <div 
            class="max-w-7xl mx-auto space-y-4"
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop.prevent="handleDrop"
        >
            <!-- 1. Page Header with Subscription & Domain Selector -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Files', href: '#' },
                    { label: 'Web File Manager' }
                ]"
                :show-refresh="true"
                @refreshed="loadFiles()"
            >
                <template #actions>
                    <div v-if="subscriptions.length > 0" class="flex items-center gap-2">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider hidden sm:inline">Active Jail:</span>
                        <select 
                            v-model="selectedSubId" 
                            @change="switchSubscription(selectedSubId)"
                            class="bg-white border border-slate-300 rounded-[3px] px-2.5 py-1.5 text-xs font-bold text-slate-800 shadow-2xs focus:ring-1 focus:ring-blue-600 focus:border-blue-600"
                        >
                            <option v-for="sub in subscriptions" :key="sub.id" :value="sub.id">
                                {{ sub.domain }} (User: {{ sub.username }})
                            </option>
                        </select>
                    </div>
                </template>
            </PageHeader>

            <!-- 2. Toast Notification -->
            <div 
                v-if="notification.show"
                :class="[
                    'p-3 rounded-[3px] text-xs font-bold flex items-center justify-between shadow-2xs transition-all duration-300',
                    notification.type === 'error' ? 'bg-rose-50 border border-rose-200 text-rose-800' : 'bg-emerald-50 border border-emerald-200 text-emerald-800'
                ]"
            >
                <span>{{ notification.message }}</span>
                <button @click="notification.show = false" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                    <XMarkIcon class="w-4 h-4" />
                </button>
            </div>

            <!-- 3. Clipboard Floating Bar (When Items are Copied / Cut) -->
            <div 
                v-if="clipboard.items.length > 0"
                class="p-2.5 px-4 bg-slate-900 text-white rounded-lg shadow-xl border border-slate-800 flex items-center justify-between gap-3 text-xs font-mono animate-fade-in"
            >
                <div class="flex items-center gap-2">
                    <ClipboardDocumentCheckIcon class="w-4 h-4 text-emerald-400" />
                    <span>
                        <strong>{{ clipboard.items.length }}</strong> item(s) in clipboard 
                        ({{ clipboard.action === 'cut' ? 'Cut / Move' : 'Copied' }}): 
                        <span class="text-slate-300 truncate max-w-xs inline-block align-bottom">{{ clipboard.itemNames.join(', ') }}</span>
                    </span>
                </div>

                <div class="flex items-center gap-2">
                    <button 
                        @click="handlePaste()"
                        class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded font-bold shadow-2xs flex items-center gap-1 transition cursor-pointer"
                    >
                        <ClipboardDocumentIcon class="w-3.5 h-3.5" />
                        <span>Paste into "{{ currentPath }}"</span>
                    </button>

                    <button 
                        @click="clearClipboard"
                        class="p-1 text-slate-400 hover:text-white rounded transition cursor-pointer"
                        title="Cancel / Clear Clipboard"
                    >
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>
            </div>

            <!-- 4. Main File Manager Container -->
            <div 
                class="bg-white rounded-lg border border-slate-200 shadow-2xs overflow-hidden select-none"
                @contextmenu.prevent="openContextMenu($event, null)"
            >
                
                <!-- Main Action & Creation Toolbar -->
                <div class="p-3 border-b border-slate-200 bg-slate-50/80 flex flex-wrap items-center justify-between gap-2.5">
                    
                    <!-- Left: Navigation Controls & Quick Bookmarks -->
                    <div class="flex items-center gap-1.5">
                        <button 
                            @click="goUp" 
                            :disabled="currentPath === '/'"
                            title="Up One Directory Level"
                            class="p-2 bg-white hover:bg-slate-100 disabled:opacity-40 text-slate-700 rounded-[3px] border border-slate-200 text-xs font-bold transition cursor-pointer shadow-2xs"
                        >
                            <ArrowUpIcon class="w-4 h-4" />
                        </button>

                        <button 
                            @click="loadFiles('/')"
                            title="Home / Root Directory"
                            class="p-2 bg-white hover:bg-slate-100 text-slate-700 rounded-[3px] border border-slate-200 text-xs font-bold transition cursor-pointer shadow-2xs"
                        >
                            <HomeIcon class="w-4 h-4" />
                        </button>

                        <button 
                            @click="jumpToPublicHtml"
                            title="Jump directly to public_html document root"
                            class="px-3 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-[3px] border border-blue-200 text-xs font-bold transition cursor-pointer flex items-center gap-1 shadow-2xs"
                        >
                            <GlobeAltIcon class="w-3.5 h-3.5" />
                            <span>public_html</span>
                        </button>

                        <button 
                            @click="loadFiles()"
                            :disabled="loading"
                            title="Reload Directory"
                            class="p-2 bg-white hover:bg-slate-100 text-slate-700 rounded-[3px] border border-slate-200 text-xs font-bold transition cursor-pointer shadow-2xs"
                        >
                            <ArrowPathIcon class="w-4 h-4" :class="{ 'animate-spin': loading }" />
                        </button>
                    </div>

                    <!-- Right: Creation, Upload & Search Tools -->
                    <div class="flex items-center gap-2">
                        
                        <!-- Search Filter -->
                        <div class="relative">
                            <MagnifyingGlassIcon class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2" />
                            <input 
                                type="text" 
                                v-model="searchQuery" 
                                placeholder="Filter files..." 
                                class="pl-8 pr-2.5 py-1.5 text-xs bg-white border border-slate-300 rounded-[3px] focus:ring-blue-500 focus:border-blue-500 w-36 sm:w-48 shadow-2xs"
                            />
                        </div>

                        <!-- View Mode Toggle -->
                        <div class="flex items-center bg-white border border-slate-200 rounded-[3px] p-0.5 shadow-2xs">
                            <button 
                                @click="viewMode = 'table'"
                                class="p-1 rounded-[2px] transition cursor-pointer"
                                :class="viewMode === 'table' ? 'bg-slate-900 text-white' : 'text-slate-500 hover:text-slate-800'"
                                title="Table View"
                            >
                                <ListBulletIcon class="w-3.5 h-3.5" />
                            </button>
                            <button 
                                @click="viewMode = 'grid'"
                                class="p-1 rounded-[2px] transition cursor-pointer"
                                :class="viewMode === 'grid' ? 'bg-slate-900 text-white' : 'text-slate-500 hover:text-slate-800'"
                                title="Grid View"
                            >
                                <Squares2X2Icon class="w-3.5 h-3.5" />
                            </button>
                        </div>

                        <!-- Upload Button -->
                        <button 
                            @click="showUploadModal = true"
                            class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold flex items-center gap-1.5 shadow-2xs transition cursor-pointer"
                        >
                            <ArrowUpTrayIcon class="w-4 h-4" />
                            <span>Upload</span>
                        </button>

                        <!-- New File -->
                        <button 
                            @click="showCreateFileModal = true"
                            class="px-2.5 py-1.5 bg-white hover:bg-slate-100 text-slate-700 rounded-[3px] border border-slate-300 text-xs font-bold flex items-center gap-1 shadow-2xs transition cursor-pointer hidden sm:flex"
                        >
                            <DocumentPlusIcon class="w-4 h-4 text-slate-500" />
                            <span>File</span>
                        </button>

                        <!-- New Folder -->
                        <button 
                            @click="showCreateFolderModal = true"
                            class="px-2.5 py-1.5 bg-white hover:bg-slate-100 text-slate-700 rounded-[3px] border border-slate-300 text-xs font-bold flex items-center gap-1 shadow-2xs transition cursor-pointer"
                        >
                            <FolderPlusIcon class="w-4 h-4 text-amber-500" />
                            <span>Folder</span>
                        </button>

                    </div>

                </div>

                <!-- Breadcrumb Path Bar & Directory Quick Stats -->
                <div class="px-4 py-2 bg-slate-100/70 border-b border-slate-200 flex flex-wrap items-center justify-between gap-2 text-xs font-mono">
                    <div class="flex items-center gap-1 overflow-x-auto text-slate-600">
                        <span class="text-slate-400 select-none">Path:</span>
                        <template v-for="(crumb, idx) in breadcrumbs" :key="crumb.path">
                            <button 
                                @click="loadFiles(crumb.path)"
                                class="hover:text-blue-600 hover:underline font-bold transition cursor-pointer truncate"
                                :class="idx === breadcrumbs.length - 1 ? 'text-blue-700 font-black' : 'text-slate-700'"
                            >
                                {{ crumb.name }}
                            </button>
                            <span v-if="idx < breadcrumbs.length - 1" class="text-slate-400 select-none">/</span>
                        </template>
                    </div>

                    <!-- Directory Summary -->
                    <div class="flex items-center gap-3 text-[11px] text-slate-500">
                        <span><strong>{{ dirSummary.total_dirs }}</strong> Folders</span>
                        <span>•</span>
                        <span><strong>{{ dirSummary.total_files }}</strong> Files</span>
                        <span>•</span>
                        <span><strong>{{ formatSize(dirSummary.total_size) }}</strong></span>
                    </div>
                </div>

                <!-- Bulk Selection Banner -->
                <div 
                    v-if="selectedPaths.length > 0"
                    class="px-4 py-2 bg-blue-50 border-b border-blue-200 flex items-center justify-between gap-3 text-xs"
                >
                    <span class="font-bold text-blue-900 font-mono">
                        {{ selectedPaths.length }} item(s) selected
                    </span>

                    <div class="flex items-center gap-2">
                        <button 
                            @click="handleCopy()"
                            class="px-2.5 py-1 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-[3px] text-xs font-bold shadow-2xs flex items-center gap-1 transition cursor-pointer"
                        >
                            <DocumentDuplicateIcon class="w-3.5 h-3.5 text-blue-600" />
                            <span>Copy</span>
                        </button>

                        <button 
                            @click="handleCut()"
                            class="px-2.5 py-1 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-[3px] text-xs font-bold shadow-2xs flex items-center gap-1 transition cursor-pointer"
                        >
                            <ScissorsIcon class="w-3.5 h-3.5 text-amber-600" />
                            <span>Cut</span>
                        </button>

                        <button 
                            @click="handleBulkZip"
                            class="px-2.5 py-1 bg-white hover:bg-slate-100 text-purple-700 border border-purple-200 rounded-[3px] text-xs font-bold shadow-2xs flex items-center gap-1 transition cursor-pointer"
                        >
                            <ArchiveBoxIcon class="w-3.5 h-3.5" />
                            <span>Compress (ZIP)</span>
                        </button>

                        <button 
                            @click="deleteTargetItem = null; showDeleteModal = true"
                            class="px-2.5 py-1 bg-rose-600 hover:bg-rose-700 text-white rounded-[3px] text-xs font-bold shadow-2xs flex items-center gap-1 transition cursor-pointer"
                        >
                            <TrashIcon class="w-3.5 h-3.5" />
                            <span>Delete</span>
                        </button>
                    </div>
                </div>

                <!-- File Browser Area -->
                <div class="min-h-[420px] relative">
                    
                    <!-- Loading State Spinner -->
                    <div v-if="loading" class="absolute inset-0 bg-white/70 backdrop-blur-2xs flex items-center justify-center z-10">
                        <div class="flex flex-col items-center gap-2">
                            <ArrowPathIcon class="w-8 h-8 text-blue-600 animate-spin" />
                            <span class="text-xs font-bold text-slate-700">Loading Directory...</span>
                        </div>
                    </div>

                    <!-- Empty Directory -->
                    <div v-if="!loading && filteredFiles.length === 0" class="p-16 text-center text-slate-500 text-xs">
                        <FolderIcon class="w-12 h-12 text-slate-300 mx-auto mb-2" />
                        <p class="font-bold text-slate-700">This directory is empty</p>
                        <p class="text-[11px] text-slate-400 mt-0.5">Right-click anywhere or use toolbar to create files or folders.</p>
                    </div>

                    <!-- TABLE VIEW MODE -->
                    <div v-else-if="viewMode === 'table'" class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-700">
                            <thead class="bg-slate-50/90 text-slate-500 font-bold uppercase tracking-wider text-[10.5px] border-b border-slate-200 select-none">
                                <tr>
                                    <th class="py-2.5 px-3 w-8">
                                        <input 
                                            type="checkbox" 
                                            v-model="selectAll" 
                                            @change="toggleSelectAll"
                                            class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                        />
                                    </th>
                                    <th class="py-2.5 px-4">Name</th>
                                    <th class="py-2.5 px-4 w-28">Size</th>
                                    <th class="py-2.5 px-4 w-40">Last Modified</th>
                                    <th class="py-2.5 px-4 w-28">Permissions</th>
                                    <th class="py-2.5 px-4 w-36 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr 
                                    v-for="item in filteredFiles" 
                                    :key="item.path"
                                    class="hover:bg-slate-50/80 transition-colors group cursor-pointer"
                                    :class="{ 'bg-blue-50/40': selectedPaths.includes(item.path) }"
                                    @dblclick="navigateTo(item)"
                                    @contextmenu.prevent="openContextMenu($event, item)"
                                >
                                    <!-- Checkbox -->
                                    <td class="py-2 px-3" @click.stop>
                                        <input 
                                            type="checkbox" 
                                            :checked="selectedPaths.includes(item.path)"
                                            @change="toggleSelectItem(item.path)"
                                            class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                        />
                                    </td>

                                    <!-- Name & Icon -->
                                    <td class="py-2 px-4 font-mono font-medium">
                                        <div class="flex items-center gap-2.5">
                                            <FolderIcon v-if="item.type === 'dir'" class="w-4 h-4 text-amber-500 fill-amber-500/20 shrink-0" />
                                            <PhotoIcon v-else-if="item.is_image" class="w-4 h-4 text-emerald-600 shrink-0" />
                                            <ArchiveBoxIcon v-else-if="item.is_archive" class="w-4 h-4 text-purple-600 shrink-0" />
                                            <DocumentIcon v-else class="w-4 h-4 text-slate-400 shrink-0" />

                                            <button 
                                                @click="navigateTo(item)"
                                                class="hover:text-blue-600 hover:underline text-left truncate font-bold"
                                                :class="item.type === 'dir' ? 'text-slate-900 font-bold' : 'text-slate-700'"
                                            >
                                                {{ item.name }}
                                            </button>
                                        </div>
                                    </td>

                                    <!-- Size -->
                                    <td class="py-2 px-4 font-mono text-[11px] text-slate-500">
                                        {{ formatSize(item.size) }}
                                    </td>

                                    <!-- Modified -->
                                    <td class="py-2 px-4 font-mono text-[11px] text-slate-500">
                                        {{ formatDate(item.modified) }}
                                    </td>

                                    <!-- Permissions -->
                                    <td class="py-2 px-4 font-mono text-[11px]">
                                        <button 
                                            @click.stop="openChmod(item)"
                                            class="hover:text-blue-600 hover:underline text-slate-600 cursor-pointer"
                                            title="Click to change permissions"
                                        >
                                            {{ item.permissions || '0755' }}
                                        </button>
                                    </td>

                                    <!-- Actions Hover Toolbar -->
                                    <td class="py-2 px-4 text-right" @click.stop>
                                        <div class="flex items-center justify-end gap-1 opacity-80 group-hover:opacity-100 transition">
                                            
                                            <!-- Edit -->
                                            <button 
                                                v-if="item.is_editable"
                                                @click="openEditor(item)"
                                                class="p-1 hover:bg-slate-200 text-slate-600 hover:text-blue-600 rounded transition cursor-pointer"
                                                title="Edit Code / File"
                                            >
                                                <PencilSquareIcon class="w-3.5 h-3.5" />
                                            </button>

                                            <!-- Preview Image -->
                                            <button 
                                                v-if="item.is_image || item.is_media"
                                                @click="openMediaPreview(item)"
                                                class="p-1 hover:bg-slate-200 text-slate-600 hover:text-emerald-600 rounded transition cursor-pointer"
                                                title="Preview Media"
                                            >
                                                <EyeIcon class="w-3.5 h-3.5" />
                                            </button>

                                            <!-- Unzip -->
                                            <button 
                                                v-if="item.is_archive"
                                                @click="confirmUnzip(item)"
                                                class="p-1 hover:bg-slate-200 text-purple-600 hover:text-purple-800 rounded transition cursor-pointer"
                                                title="Extract Archive"
                                            >
                                                <ArrowDownCircleIcon class="w-3.5 h-3.5" />
                                            </button>

                                            <!-- Zip -->
                                            <button 
                                                v-if="!item.is_archive"
                                                @click="handleZip(item)"
                                                class="p-1 hover:bg-slate-200 text-slate-600 hover:text-purple-600 rounded transition cursor-pointer"
                                                title="Compress to ZIP"
                                            >
                                                <ArchiveBoxIcon class="w-3.5 h-3.5" />
                                            </button>

                                            <!-- Download -->
                                            <button 
                                                v-if="item.type === 'file'"
                                                @click="downloadFile(item)"
                                                class="p-1 hover:bg-slate-200 text-slate-600 hover:text-slate-900 rounded transition cursor-pointer"
                                                title="Download File"
                                            >
                                                <ArrowDownTrayIcon class="w-3.5 h-3.5" />
                                            </button>

                                            <!-- Rename -->
                                            <button 
                                                @click="openRename(item)"
                                                class="p-1 hover:bg-slate-200 text-slate-600 hover:text-slate-900 rounded transition cursor-pointer"
                                                title="Rename"
                                            >
                                                <ArrowsRightLeftIcon class="w-3.5 h-3.5" />
                                            </button>

                                            <!-- Delete -->
                                            <button 
                                                @click="confirmDelete(item)"
                                                class="p-1 hover:bg-rose-50 text-slate-400 hover:text-rose-600 rounded transition cursor-pointer"
                                                title="Delete permanently"
                                            >
                                                <TrashIcon class="w-3.5 h-3.5" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- GRID VIEW MODE -->
                    <div v-else class="p-4 grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 gap-3">
                        <div 
                            v-for="item in filteredFiles" 
                            :key="item.path"
                            class="p-3 bg-white rounded-lg border border-slate-200 hover:border-blue-300 hover:shadow-md transition-all flex flex-col items-center justify-between text-center gap-2 group relative cursor-pointer"
                            :class="{ 'border-blue-500 bg-blue-50/30 ring-1 ring-blue-500': selectedPaths.includes(item.path) }"
                            @click="toggleSelectItem(item.path)"
                            @dblclick="navigateTo(item)"
                            @contextmenu.prevent="openContextMenu($event, item)"
                        >
                            <div class="w-12 h-12 rounded-lg flex items-center justify-center shrink-0">
                                <FolderIcon v-if="item.type === 'dir'" class="w-10 h-10 text-amber-500 fill-amber-500/20" />
                                <PhotoIcon v-else-if="item.is_image" class="w-9 h-9 text-emerald-600" />
                                <ArchiveBoxIcon v-else-if="item.is_archive" class="w-9 h-9 text-purple-600" />
                                <DocumentIcon v-else class="w-9 h-9 text-slate-400" />
                            </div>

                            <div class="w-full">
                                <span class="font-bold text-xs text-slate-800 truncate block font-mono">{{ item.name }}</span>
                                <span class="text-[10px] text-slate-400 font-mono block mt-0.5">{{ formatSize(item.size) }}</span>
                            </div>
                        </div>
                    </div>

                </div>

            </div>

        </div>

        <!-- 5. DESKTOP-GRADE CUSTOM CONTEXT MENU (RIGHT-CLICK) -->
        <div 
            v-if="contextMenu.show"
            class="fixed z-50 bg-white border border-slate-200 rounded-lg shadow-2xl py-1 w-52 text-xs font-medium text-slate-700 font-sans divide-y divide-slate-100 animate-fade-in select-none"
            :style="{ left: contextMenu.x + 'px', top: contextMenu.y + 'px' }"
            @click.stop
        >
            <!-- TARGET ITEM MENU -->
            <template v-if="contextMenu.item">
                <div class="px-3 py-1.5 bg-slate-50/80 font-mono font-bold text-slate-800 text-[11px] truncate flex items-center gap-1.5">
                    <FolderIcon v-if="contextMenu.item.type === 'dir'" class="w-3.5 h-3.5 text-amber-500 shrink-0" />
                    <DocumentIcon v-else class="w-3.5 h-3.5 text-blue-500 shrink-0" />
                    <span class="truncate">{{ contextMenu.item.name }}</span>
                </div>

                <div class="py-1">
                    <!-- Open / Navigate -->
                    <button 
                        v-if="contextMenu.item.type === 'dir'"
                        @click="navigateTo(contextMenu.item)"
                        class="w-full px-3 py-1.5 text-left hover:bg-blue-50 hover:text-blue-700 flex items-center gap-2 cursor-pointer transition"
                    >
                        <FolderIcon class="w-4 h-4 text-amber-500" />
                        <span>Open Folder</span>
                    </button>

                    <!-- Edit Code -->
                    <button 
                        v-if="contextMenu.item.is_editable"
                        @click="openEditor(contextMenu.item)"
                        class="w-full px-3 py-1.5 text-left hover:bg-blue-50 hover:text-blue-700 flex items-center gap-2 cursor-pointer transition"
                    >
                        <PencilSquareIcon class="w-4 h-4 text-blue-600" />
                        <span>Edit in Code Studio</span>
                    </button>

                    <!-- Preview Media -->
                    <button 
                        v-if="contextMenu.item.is_image || contextMenu.item.is_media"
                        @click="openMediaPreview(contextMenu.item)"
                        class="w-full px-3 py-1.5 text-left hover:bg-blue-50 hover:text-blue-700 flex items-center gap-2 cursor-pointer transition"
                    >
                        <EyeIcon class="w-4 h-4 text-emerald-600" />
                        <span>Preview Media</span>
                    </button>

                    <!-- Download -->
                    <button 
                        v-if="contextMenu.item.type === 'file'"
                        @click="downloadFile(contextMenu.item)"
                        class="w-full px-3 py-1.5 text-left hover:bg-blue-50 hover:text-blue-700 flex items-center gap-2 cursor-pointer transition"
                    >
                        <ArrowDownTrayIcon class="w-4 h-4 text-slate-500" />
                        <span>Download</span>
                    </button>
                </div>

                <!-- Clipboard Operations (Copy, Cut, Paste) -->
                <div class="py-1">
                    <button 
                        @click="handleCopy(contextMenu.item)"
                        class="w-full px-3 py-1.5 text-left hover:bg-blue-50 hover:text-blue-700 flex items-center justify-between cursor-pointer transition"
                    >
                        <div class="flex items-center gap-2">
                            <DocumentDuplicateIcon class="w-4 h-4 text-slate-500" />
                            <span>Copy</span>
                        </div>
                        <span class="text-[10px] text-slate-400 font-mono">Ctrl+C</span>
                    </button>

                    <button 
                        @click="handleCut(contextMenu.item)"
                        class="w-full px-3 py-1.5 text-left hover:bg-blue-50 hover:text-blue-700 flex items-center justify-between cursor-pointer transition"
                    >
                        <div class="flex items-center gap-2">
                            <ScissorsIcon class="w-4 h-4 text-amber-600" />
                            <span>Cut</span>
                        </div>
                        <span class="text-[10px] text-slate-400 font-mono">Ctrl+X</span>
                    </button>

                    <button 
                        v-if="clipboard.items.length > 0"
                        @click="handlePaste(contextMenu.item.type === 'dir' ? contextMenu.item.path : currentPath)"
                        class="w-full px-3 py-1.5 text-left hover:bg-blue-50 hover:text-blue-700 flex items-center justify-between cursor-pointer transition text-blue-600 font-bold"
                    >
                        <div class="flex items-center gap-2">
                            <ClipboardDocumentIcon class="w-4 h-4 text-blue-600" />
                            <span>Paste</span>
                        </div>
                        <span class="text-[10px] text-blue-400 font-mono">Ctrl+V</span>
                    </button>
                </div>

                <!-- Archive / Extract -->
                <div class="py-1">
                    <button 
                        v-if="contextMenu.item.is_archive"
                        @click="confirmUnzip(contextMenu.item)"
                        class="w-full px-3 py-1.5 text-left hover:bg-purple-50 hover:text-purple-700 flex items-center gap-2 cursor-pointer transition font-bold text-purple-700"
                    >
                        <ArrowDownCircleIcon class="w-4 h-4" />
                        <span>Extract (Unzip)</span>
                    </button>

                    <button 
                        v-if="!contextMenu.item.is_archive"
                        @click="handleZip(contextMenu.item)"
                        class="w-full px-3 py-1.5 text-left hover:bg-purple-50 hover:text-purple-700 flex items-center gap-2 cursor-pointer transition"
                    >
                        <ArchiveBoxIcon class="w-4 h-4 text-purple-600" />
                        <span>Compress to ZIP</span>
                    </button>
                </div>

                <!-- Advanced & Management -->
                <div class="py-1">
                    <button 
                        @click="openRename(contextMenu.item)"
                        class="w-full px-3 py-1.5 text-left hover:bg-blue-50 hover:text-blue-700 flex items-center gap-2 cursor-pointer transition"
                    >
                        <ArrowsRightLeftIcon class="w-4 h-4 text-slate-500" />
                        <span>Rename</span>
                    </button>

                    <button 
                        @click="openChmod(contextMenu.item)"
                        class="w-full px-3 py-1.5 text-left hover:bg-blue-50 hover:text-blue-700 flex items-center gap-2 cursor-pointer transition"
                    >
                        <KeyIcon class="w-4 h-4 text-slate-500" />
                        <span>Change Permissions</span>
                    </button>
                </div>

                <!-- Delete -->
                <div class="py-1">
                    <button 
                        @click="confirmDelete(contextMenu.item)"
                        class="w-full px-3 py-1.5 text-left hover:bg-rose-50 hover:text-rose-600 flex items-center gap-2 cursor-pointer transition text-rose-600 font-bold"
                    >
                        <TrashIcon class="w-4 h-4" />
                        <span>Delete Permanently</span>
                    </button>
                </div>
            </template>

            <!-- EMPTY CANVAS / DIRECTORY MENU -->
            <template v-else>
                <div class="px-3 py-1.5 bg-slate-50/80 font-mono font-bold text-slate-800 text-[11px] truncate">
                    Directory Actions: {{ currentPath }}
                </div>

                <div class="py-1">
                    <button 
                        @click="showCreateFileModal = true; closeContextMenu()"
                        class="w-full px-3 py-1.5 text-left hover:bg-blue-50 hover:text-blue-700 flex items-center gap-2 cursor-pointer transition"
                    >
                        <DocumentPlusIcon class="w-4 h-4 text-blue-600" />
                        <span>New File</span>
                    </button>

                    <button 
                        @click="showCreateFolderModal = true; closeContextMenu()"
                        class="w-full px-3 py-1.5 text-left hover:bg-blue-50 hover:text-blue-700 flex items-center gap-2 cursor-pointer transition"
                    >
                        <FolderPlusIcon class="w-4 h-4 text-amber-500" />
                        <span>New Folder</span>
                    </button>

                    <button 
                        @click="showUploadModal = true; closeContextMenu()"
                        class="w-full px-3 py-1.5 text-left hover:bg-blue-50 hover:text-blue-700 flex items-center gap-2 cursor-pointer transition"
                    >
                        <ArrowUpTrayIcon class="w-4 h-4 text-blue-600" />
                        <span>Upload Files</span>
                    </button>
                </div>

                <div class="py-1" v-if="clipboard.items.length > 0">
                    <button 
                        @click="handlePaste()"
                        class="w-full px-3 py-1.5 text-left hover:bg-blue-50 hover:text-blue-700 flex items-center gap-2 cursor-pointer transition font-bold text-blue-600"
                    >
                        <ClipboardDocumentIcon class="w-4 h-4" />
                        <span>Paste {{ clipboard.items.length }} Item(s)</span>
                    </button>
                </div>

                <div class="py-1">
                    <button 
                        @click="loadFiles()"
                        class="w-full px-3 py-1.5 text-left hover:bg-slate-100 flex items-center gap-2 cursor-pointer transition"
                    >
                        <ArrowPathIcon class="w-4 h-4 text-slate-500" />
                        <span>Refresh Directory</span>
                    </button>
                </div>
            </template>
        </div>

        <!-- MODAL 1: Upload Files Modal -->
        <div v-if="showUploadModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-lg max-w-lg w-full p-5 shadow-2xl space-y-4 border border-slate-200">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2">
                        <ArrowUpTrayIcon class="w-5 h-5 text-blue-600" />
                        <h3 class="font-bold text-slate-900 text-sm">Upload Files to Server</h3>
                    </div>
                    <button @click="showUploadModal = false" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="space-y-3">
                    <div class="text-xs text-slate-500 font-mono">
                        Destination: <strong class="text-slate-800">{{ currentPath }}</strong>
                    </div>

                    <label class="border-2 border-dashed border-slate-300 hover:border-blue-500 rounded-lg p-6 flex flex-col items-center justify-center gap-2 bg-slate-50 cursor-pointer transition">
                        <ArrowUpTrayIcon class="w-8 h-8 text-slate-400" />
                        <span class="text-xs font-bold text-slate-700">Click to select files, or drag & drop here</span>
                        <span class="text-[11px] text-slate-400">Max size up to 500 MB per file</span>
                        <input type="file" multiple @change="onFilesSelected" class="hidden" />
                    </label>

                    <div v-if="uploadFilesList.length > 0" class="space-y-1 max-h-36 overflow-y-auto text-xs font-mono">
                        <div v-for="(file, idx) in uploadFilesList" :key="idx" class="flex items-center justify-between p-1.5 bg-slate-100 rounded">
                            <span class="truncate max-w-[280px]">{{ file.name }}</span>
                            <span class="text-slate-500">{{ formatSize(file.size) }}</span>
                        </div>
                    </div>

                    <div v-if="uploading" class="space-y-1">
                        <div class="flex justify-between text-xs font-mono">
                            <span>Uploading...</span>
                            <span>{{ uploadProgress }}%</span>
                        </div>
                        <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                            <div class="bg-blue-600 h-full transition-all duration-200" :style="{ width: uploadProgress + '%' }"></div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button 
                        @click="showUploadModal = false" 
                        class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-[3px] text-xs font-bold cursor-pointer"
                    >
                        Cancel
                    </button>
                    <button 
                        @click="handleUpload" 
                        :disabled="uploading || uploadFilesList.length === 0"
                        class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[3px] text-xs font-bold shadow-2xs transition cursor-pointer disabled:opacity-50 flex items-center gap-1"
                    >
                        <ArrowUpTrayIcon class="w-3.5 h-3.5" />
                        <span>{{ uploading ? 'Uploading...' : 'Start Upload' }}</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- MODAL 2: Full Code Editor Modal -->
        <div v-if="showEditorModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-2xs flex items-center justify-center p-3 z-50">
            <div 
                class="bg-slate-900 text-slate-100 rounded-lg shadow-2xl border border-slate-800 flex flex-col transition-all duration-200"
                :class="editorFile.isFullScreen ? 'w-full h-full' : 'max-w-5xl w-full h-[88vh]'"
            >
                <div class="px-4 py-2.5 bg-slate-950 border-b border-slate-800 flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-2">
                        <CodeBracketIcon class="w-4 h-4 text-blue-400" />
                        <span class="text-xs font-bold text-slate-200 font-mono">{{ editorFile.name }}</span>
                        <span class="text-[10px] text-slate-500 font-mono hidden sm:inline">({{ editorFile.path }})</span>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="text-[11px] text-slate-400 font-mono hidden sm:inline">Ctrl+S to save</span>
                        
                        <button 
                            @click="editorFile.isFullScreen = !editorFile.isFullScreen"
                            class="px-2 py-1 bg-slate-800 hover:bg-slate-700 rounded text-[11px] font-mono text-slate-300 cursor-pointer"
                        >
                            {{ editorFile.isFullScreen ? 'Exit Fullscreen' : 'Fullscreen' }}
                        </button>

                        <button 
                            @click="handleSaveFile"
                            :disabled="editorFile.saving"
                            class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded text-xs font-bold shadow-2xs flex items-center gap-1 transition cursor-pointer disabled:opacity-50"
                        >
                            <CheckIcon class="w-3.5 h-3.5" />
                            <span>{{ editorFile.saving ? 'Saving...' : 'Save File' }}</span>
                        </button>

                        <button @click="showEditorModal = false" class="text-slate-400 hover:text-white p-1 cursor-pointer">
                            <XMarkIcon class="w-4 h-4" />
                        </button>
                    </div>
                </div>

                <div class="flex-1 relative overflow-hidden bg-slate-950">
                    <div v-if="editorFile.loading" class="absolute inset-0 flex items-center justify-center">
                        <ArrowPathIcon class="w-8 h-8 text-blue-500 animate-spin" />
                    </div>
                    <textarea 
                        v-else
                        v-model="editorFile.content"
                        class="w-full h-full p-4 font-mono text-xs bg-slate-950 text-slate-100 resize-none border-0 focus:ring-0 focus:outline-none leading-relaxed select-text"
                        :style="{ fontSize: editorFile.fontSize + 'px' }"
                        spellcheck="false"
                    ></textarea>
                </div>

                <div class="px-4 py-1.5 bg-slate-950 border-t border-slate-800 text-[10.5px] font-mono text-slate-400 flex items-center justify-between shrink-0">
                    <span>Lines: {{ (editorFile.content.match(/\n/g) || []).length + 1 }} • Characters: {{ editorFile.content.length }}</span>
                    <span>UTF-8 • UNIX (LF)</span>
                </div>
            </div>
        </div>

        <!-- MODAL 3: Create Folder Modal -->
        <div v-if="showCreateFolderModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-lg max-w-sm w-full p-5 shadow-2xl space-y-4 border border-slate-200">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                    <h3 class="font-bold text-slate-900 text-sm">Create New Folder</h3>
                    <button @click="showCreateFolderModal = false" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-slate-700">Folder Name</label>
                    <input 
                        type="text" 
                        v-model="newFolderName" 
                        placeholder="e.g. assets or backup"
                        class="w-full text-xs font-mono rounded-[3px] border-slate-300 bg-white py-2 px-2.5"
                        @keyup.enter="handleCreateFolder"
                    />
                </div>
                <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                    <button @click="showCreateFolderModal = false" class="px-3 py-1.5 bg-slate-100 text-slate-700 rounded-[3px] text-xs font-bold cursor-pointer">Cancel</button>
                    <button @click="handleCreateFolder" class="px-4 py-1.5 bg-blue-600 text-white rounded-[3px] text-xs font-bold cursor-pointer">Create</button>
                </div>
            </div>
        </div>

        <!-- MODAL 4: Create File Modal -->
        <div v-if="showCreateFileModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-lg max-w-sm w-full p-5 shadow-2xl space-y-4 border border-slate-200">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                    <h3 class="font-bold text-slate-900 text-sm">Create New File</h3>
                    <button @click="showCreateFileModal = false" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-slate-700">File Name</label>
                    <input 
                        type="text" 
                        v-model="newFileName" 
                        placeholder="e.g. index.php or .htaccess"
                        class="w-full text-xs font-mono rounded-[3px] border-slate-300 bg-white py-2 px-2.5"
                        @keyup.enter="handleCreateFile"
                    />
                </div>
                <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                    <button @click="showCreateFileModal = false" class="px-3 py-1.5 bg-slate-100 text-slate-700 rounded-[3px] text-xs font-bold cursor-pointer">Cancel</button>
                    <button @click="handleCreateFile" class="px-4 py-1.5 bg-blue-600 text-white rounded-[3px] text-xs font-bold cursor-pointer">Create File</button>
                </div>
            </div>
        </div>

        <!-- MODAL 5: Rename Modal -->
        <div v-if="showRenameModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-lg max-w-sm w-full p-5 shadow-2xl space-y-4 border border-slate-200">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                    <h3 class="font-bold text-slate-900 text-sm">Rename Item</h3>
                    <button @click="showRenameModal = false" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-slate-700">New Name</label>
                    <input 
                        type="text" 
                        v-model="renameNewName" 
                        class="w-full text-xs font-mono rounded-[3px] border-slate-300 bg-white py-2 px-2.5"
                        @keyup.enter="handleRename"
                    />
                </div>
                <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                    <button @click="showRenameModal = false" class="px-3 py-1.5 bg-slate-100 text-slate-700 rounded-[3px] text-xs font-bold cursor-pointer">Cancel</button>
                    <button @click="handleRename" class="px-4 py-1.5 bg-blue-600 text-white rounded-[3px] text-xs font-bold cursor-pointer">Rename</button>
                </div>
            </div>
        </div>

        <!-- MODAL 6: Permissions (chmod) Modal -->
        <div v-if="showChmodModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-lg max-w-sm w-full p-5 shadow-2xl space-y-4 border border-slate-200 font-mono">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2.5 font-sans">
                    <div class="flex items-center gap-2">
                        <KeyIcon class="w-4 h-4 text-blue-600" />
                        <h3 class="font-bold text-slate-900 text-sm">Change Permissions</h3>
                    </div>
                    <button @click="showChmodModal = false" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <div class="space-y-3 text-xs">
                    <div class="text-slate-600 font-sans">
                        Item: <strong class="text-slate-900 font-mono">{{ chmodForm.name }}</strong>
                    </div>

                    <div class="space-y-1">
                        <label class="block font-bold text-slate-700 font-sans">Octal Permission Value</label>
                        <input 
                            type="text" 
                            v-model="chmodForm.perms" 
                            placeholder="0755"
                            class="w-full text-xs font-mono font-bold rounded-[3px] border-slate-300 bg-white py-2 px-2.5"
                        />
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <button type="button" @click="chmodForm.perms = '0755'" class="px-2 py-1 bg-slate-100 rounded text-[11px] font-bold cursor-pointer">0755 (Directory)</button>
                        <button type="button" @click="chmodForm.perms = '0644'" class="px-2 py-1 bg-slate-100 rounded text-[11px] font-bold cursor-pointer">0644 (File)</button>
                        <button type="button" @click="chmodForm.perms = '0777'" class="px-2 py-1 bg-rose-50 text-rose-700 rounded text-[11px] font-bold cursor-pointer">0777 (Public Write)</button>
                    </div>

                    <label class="flex items-center gap-2 pt-1 font-sans cursor-pointer">
                        <input type="checkbox" v-model="chmodForm.recursive" class="rounded border-slate-300 text-blue-600" />
                        <span class="text-slate-700">Apply recursively to subdirectories</span>
                    </label>
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-slate-100 font-sans">
                    <button @click="showChmodModal = false" class="px-3 py-1.5 bg-slate-100 text-slate-700 rounded-[3px] text-xs font-bold cursor-pointer">Cancel</button>
                    <button @click="handleChmod" :disabled="chmodForm.saving" class="px-4 py-1.5 bg-blue-600 text-white rounded-[3px] text-xs font-bold cursor-pointer disabled:opacity-50">
                        {{ chmodForm.saving ? 'Saving...' : 'Apply Permissions' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- MODAL 7: Delete Confirmation Modal -->
        <div v-if="showDeleteModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-lg max-w-md w-full p-5 shadow-2xl space-y-4 border border-slate-200">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2">
                        <TrashIcon class="w-5 h-5 text-rose-600" />
                        <h3 class="font-bold text-slate-900 text-sm">Delete Confirmation</h3>
                    </div>
                    <button @click="showDeleteModal = false" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <p class="text-xs text-slate-600">
                    Are you sure you want to permanently delete 
                    <strong class="text-rose-600 font-mono">{{ deleteTargetItem ? deleteTargetItem.name : `${selectedPaths.length} selected items` }}</strong>?
                    This action cannot be undone.
                </p>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button @click="showDeleteModal = false" class="px-3 py-1.5 bg-slate-100 text-slate-700 rounded-[3px] text-xs font-bold cursor-pointer">Cancel</button>
                    <button @click="executeDelete" :disabled="deleting" class="px-4 py-1.5 bg-rose-600 text-white rounded-[3px] text-xs font-bold cursor-pointer disabled:opacity-50">
                        {{ deleting ? 'Deleting...' : 'Delete Permanently' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- MODAL 8: Unzip Modal -->
        <div v-if="showUnzipModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-lg max-w-sm w-full p-5 shadow-2xl space-y-4 border border-slate-200">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                    <h3 class="font-bold text-slate-900 text-sm">Extract Archive</h3>
                    <button @click="showUnzipModal = false" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>
                <p class="text-xs text-slate-600">
                    Extract <strong class="text-slate-900 font-mono">{{ unzipTargetItem?.name }}</strong> into <code class="font-mono text-blue-600">{{ currentPath }}</code>?
                </p>
                <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                    <button @click="showUnzipModal = false" class="px-3 py-1.5 bg-slate-100 text-slate-700 rounded-[3px] text-xs font-bold cursor-pointer">Cancel</button>
                    <button @click="executeUnzip" :disabled="unzipping" class="px-4 py-1.5 bg-purple-600 text-white rounded-[3px] text-xs font-bold cursor-pointer disabled:opacity-50">
                        {{ unzipping ? 'Extracting...' : 'Extract Files' }}
                    </button>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>
