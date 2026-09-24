<template>
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6">
            <h3 class="text-lg font-semibold text-slate-800 mb-4">Upload File</h3>
            
            <div 
                class="border-2 border-dashed border-slate-200 rounded-xl p-8 text-center cursor-pointer hover:border-blue-500 transition-colors"
                @click="$refs.fileInput.click()"
            >
                <input ref="fileInput" type="file" class="hidden" @change="onFileChange" />
                <p class="text-sm text-slate-600 font-medium">
                    {{ selectedFileName || 'Click to browse or drag file here' }}
                </p>
                <p class="text-xs text-slate-400 mt-1">Maximum upload size: 256MB</p>
            </div>

            <div class="flex justify-end gap-2 mt-6">
                <button @click="$emit('close')" type="button" class="px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 rounded-lg">
                    Cancel
                </button>
                <button 
                    @click="upload" 
                    :disabled="!file || uploading"
                    type="button" 
                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50 rounded-lg shadow-sm"
                >
                    {{ uploading ? 'Uploading...' : 'Upload' }}
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import axios from 'axios'

const props = defineProps({
    currentPath: { type: String, default: '/' }
})

const emit = defineEmits(['close', 'uploaded'])
const file = ref(null)
const selectedFileName = ref('')
const uploading = ref(false)

const onFileChange = (e) => {
    if (e.target.files.length > 0) {
        file.value = e.target.files[0]
        selectedFileName.value = file.value.name
    }
}

const upload = async () => {
    if (!file.value) return
    uploading.value = true

    const formData = new FormData()
    formData.append('file', file.value)
    formData.append('path', props.currentPath)

    try {
        await axios.post('/file-manager/upload', formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        })
        emit('uploaded')
        emit('close')
    } catch (err) {
        alert('Upload failed: ' + (err.response?.data?.message || err.message))
    } finally {
        uploading.value = false
    }
}
</script>
