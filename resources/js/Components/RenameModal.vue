<template>
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6">
            <h3 class="text-lg font-semibold text-slate-800 mb-4">Rename Item</h3>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-1">New Name</label>
                <input 
                    v-model="newName" 
                    type="text" 
                    class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"
                    autofocus
                />
            </div>

            <div class="flex justify-end gap-2 mt-6">
                <button @click="$emit('close')" type="button" class="px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 rounded-lg">
                    Cancel
                </button>
                <button 
                    @click="rename" 
                    :disabled="!newName || loading"
                    type="button" 
                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50 rounded-lg shadow-sm"
                >
                    {{ loading ? 'Renaming...' : 'Rename' }}
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const props = defineProps({
    file: { type: Object, default: () => ({}) }
})

const emit = defineEmits(['close', 'renamed'])
const newName = ref('')
const loading = ref(false)

onMounted(() => {
    newName.value = props.file?.name || ''
})

const rename = async () => {
    if (!newName.value || !props.file?.path) return
    loading.value = true

    try {
        await axios.post('/file-manager/rename', {
            old_path: props.file.path,
            new_name: newName.value
        })
        emit('renamed')
        emit('close')
    } catch (err) {
        alert('Rename failed: ' + (err.response?.data?.message || err.message))
    } finally {
        loading.value = false
    }
}
</script>
