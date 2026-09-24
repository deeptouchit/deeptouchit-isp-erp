<template>
    <div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex flex-wrap gap-2 justify-between items-center bg-slate-50/50">
            <div class="flex items-center gap-2 flex-1 min-w-[200px]">
                <button 
                    @click="goBack" 
                    :disabled="currentPath === '/'"
                    class="p-2 hover:bg-slate-200/60 disabled:opacity-30 rounded-lg transition-colors"
                >
                    <ArrowLeftIcon class="w-5 h-5 text-slate-600" />
                </button>
                <div class="flex-1 bg-white border border-slate-200 rounded-lg px-3 py-1.5 text-sm font-mono text-slate-700 truncate shadow-inner">
                    {{ currentPath }}
                </div>
            </div>
            
            <div class="flex gap-2">
                <button 
                    @click="showUploadModal = true" 
                    class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg shadow-sm transition-colors"
                >
                    <PlusIcon class="w-4 h-4" /> Upload
                </button>
                <button 
                    @click="createFolderPrompt" 
                    class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 text-sm font-medium rounded-lg shadow-sm transition-colors"
                >
                    <FolderPlusIcon class="w-4 h-4 text-slate-500" /> New Folder
                </button>
            </div>
        </div>
        
        <div class="p-4 min-h-[320px]">
            <div v-if="loading" class="text-center py-16">
                <Spinner class="w-8 h-8 animate-spin mx-auto text-blue-600" />
                <p class="text-slate-400 text-sm mt-3 font-medium">Loading files...</p>
            </div>
            
            <div v-else-if="files.length === 0" class="text-center py-16 text-slate-400">
                <FolderIcon class="w-16 h-16 mx-auto text-slate-300 stroke-1" />
                <p class="mt-2 text-sm">No files or folders in this directory</p>
            </div>
            
            <div v-else class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 text-slate-400 text-xs uppercase font-semibold">
                            <th class="py-2.5 px-3">Name</th>
                            <th class="py-2.5 px-3">Size</th>
                            <th class="py-2.5 px-3">Modified</th>
                            <th class="py-2.5 px-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <tr 
                            v-for="file in files" 
                            :key="file.path" 
                            class="hover:bg-slate-50/80 transition-colors group cursor-pointer"
                        >
                            <td class="py-2.5 px-3" @click="file.type === 'dir' ? navigate(file) : editFile(file)">
                                <div class="flex items-center gap-2.5 font-medium text-slate-700">
                                    <component 
                                        :is="file.type === 'dir' ? FolderIcon : DocumentIcon" 
                                        :class="file.type === 'dir' ? 'text-amber-500 fill-amber-500/20' : 'text-slate-400'" 
                                        class="w-5 h-5 flex-shrink-0" 
                                    />
                                    <span class="hover:text-blue-600 transition-colors">{{ file.name }}</span>
                                </div>
                            </td>
                            <td class="py-2.5 px-3 text-slate-500 text-xs">{{ formatSize(file.size) }}</td>
                            <td class="py-2.5 px-3 text-slate-400 text-xs">{{ formatDate(file.modified) }}</td>
                            <td class="py-2.5 px-3 text-right">
                                <div class="flex justify-end gap-1 opacity-80 group-hover:opacity-100">
                                    <button 
                                        @click.stop="openRenameModal(file)" 
                                        title="Rename" 
                                        class="p-1.5 hover:bg-slate-200/60 rounded-lg text-slate-500 transition-colors"
                                    >
                                        <PencilIcon class="w-4 h-4" />
                                    </button>
                                    <button 
                                        @click.stop="deleteFile(file)" 
                                        title="Delete" 
                                        class="p-1.5 hover:bg-rose-50 text-rose-600 rounded-lg transition-colors"
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
        
        <!-- Upload Modal -->
        <UploadModal 
            v-if="showUploadModal" 
            :current-path="currentPath" 
            @close="showUploadModal = false" 
            @uploaded="refreshFiles" 
        />
        
        <!-- Rename Modal -->
        <RenameModal 
            v-if="showRenameModal" 
            :file="selectedFile" 
            @close="showRenameModal = false" 
            @renamed="refreshFiles" 
        />
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { 
    ArrowLeftIcon, PlusIcon, FolderPlusIcon, 
    FolderIcon, DocumentIcon, PencilIcon, TrashIcon 
} from '@heroicons/vue/24/outline'
import Spinner from '@/Components/Spinner.vue'
import UploadModal from '@/Components/UploadModal.vue'
import RenameModal from '@/Components/RenameModal.vue'
import axios from 'axios'

const currentPath = ref('/')
const files = ref([])
const loading = ref(false)
const showUploadModal = ref(false)
const showRenameModal = ref(false)
const selectedFile = ref(null)

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
        month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'
    })
}

const navigate = (dir) => {
    currentPath.value = dir.path
    loadFiles()
}

const goBack = () => {
    if (currentPath.value === '/') return
    const parts = currentPath.value.split('/').filter(Boolean)
    parts.pop()
    currentPath.value = '/' + parts.join('/')
    loadFiles()
}

const loadFiles = async () => {
    loading.value = true
    try {
        const response = await axios.get('/file-manager/browse', {
            params: { path: currentPath.value }
        })
        files.value = response.data
    } catch (error) {
        console.error('Failed to load files:', error)
    } finally {
        loading.value = false
    }
}

const createFolderPrompt = async () => {
    const folderName = prompt('Enter folder name:')
    if (!folderName) return

    try {
        await axios.post('/file-manager/create-folder', {
            path: currentPath.value,
            name: folderName
        })
        loadFiles()
    } catch (err) {
        alert('Failed to create folder: ' + err.message)
    }
}

const openRenameModal = (file) => {
    selectedFile.value = file
    showRenameModal.value = true
}

const deleteFile = async (file) => {
    if (!confirm(`Are you sure you want to delete ${file.name}?`)) return

    try {
        await axios.delete('/file-manager/delete', {
            data: { path: file.path }
        })
        loadFiles()
    } catch (err) {
        alert('Delete failed: ' + err.message)
    }
}

const editFile = (file) => {
    console.log('Editing file:', file)
}

const refreshFiles = () => loadFiles()

onMounted(loadFiles)
</script>
