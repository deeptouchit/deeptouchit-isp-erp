<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue'
import { EllipsisHorizontalIcon } from '@heroicons/vue/20/solid'

const isOpen = ref(false)
const buttonRef = ref(null)
const menuRef = ref(null)
const dropdownStyle = ref({ top: '0px', left: '0px', width: '160px' })

const updatePosition = async () => {
    if (!buttonRef.value) return
    const rect = buttonRef.value.getBoundingClientRect()
    const menuWidth = 160
    
    // Position right-aligned to button
    let left = rect.right - menuWidth
    let top = rect.bottom + 4

    // Prevent overflow outside window edges
    if (left < 10) left = 10
    if (left + menuWidth > window.innerWidth - 10) {
        left = window.innerWidth - menuWidth - 10
    }

    dropdownStyle.value = {
        top: `${Math.round(top)}px`,
        left: `${Math.round(left)}px`,
        width: `${menuWidth}px`
    }

    await nextTick()
    if (menuRef.value) {
        const menuHeight = menuRef.value.offsetHeight
        // If bottom overflow, flip upwards
        if (rect.bottom + menuHeight + 10 > window.innerHeight && rect.top - menuHeight - 4 > 0) {
            top = rect.top - menuHeight - 4
            dropdownStyle.value.top = `${Math.round(top)}px`
        }
    }
}

const toggle = async (e) => {
    e.stopPropagation()
    if (!isOpen.value) {
        isOpen.value = true
        await updatePosition()
    } else {
        isOpen.value = false
    }
}

const close = () => {
    isOpen.value = false
}

const handleClickOutside = (e) => {
    if (buttonRef.value && !buttonRef.value.contains(e.target)) {
        close()
    }
}

const handleScrollOrResize = () => {
    if (isOpen.value) {
        close()
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside)
    window.addEventListener('scroll', handleScrollOrResize, true)
    window.addEventListener('resize', handleScrollOrResize)
})

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside)
    window.removeEventListener('scroll', handleScrollOrResize, true)
    window.removeEventListener('resize', handleScrollOrResize)
})
</script>

<template>
    <div class="relative inline-flex items-center justify-center">
        <!-- Trigger Button (...) -->
        <button
            ref="buttonRef"
            type="button"
            @click="toggle"
            class="w-6.5 h-6.5 rounded-[3px] bg-white hover:bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-500 hover:text-slate-900 transition-colors shadow-2xs cursor-pointer focus:outline-none"
            :class="{ 'bg-blue-50 border-blue-300 ring-1 ring-blue-500 text-blue-600': isOpen }"
            title="Actions"
        >
            <EllipsisHorizontalIcon class="w-4 h-4" />
        </button>

        <!-- Teleported to body with fixed z-[9999] so NOTHING can clip it -->
        <Teleport to="body">
            <div
                v-if="isOpen"
                ref="menuRef"
                @click="close"
                class="fixed rounded-[4px] bg-white border border-slate-200 shadow-xl z-[9999] py-1 text-xs divide-y divide-slate-100 focus:outline-none"
                :style="dropdownStyle"
            >
                <slot />
            </div>
        </Teleport>
    </div>
</template>
