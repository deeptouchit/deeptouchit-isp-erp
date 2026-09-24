<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import {
    EllipsisVerticalIcon,
    EyeIcon,
    PencilSquareIcon,
    ShieldCheckIcon,
    MagnifyingGlassIcon,
    ArrowPathIcon,
    WrenchScrewdriverIcon,
    KeyIcon,
    TrashIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    server: {
        type: Object,
        required: true,
    },
})

const emit = defineEmits([
    'open-maintenance',
    'open-host-key',
    'open-credentials',
    'open-delete',
])

const isOpen = ref(false)
const buttonRef = ref(null)
const menuStyle = ref({})

const calculatePosition = () => {
    if (!buttonRef.value) return
    const rect = buttonRef.value.getBoundingClientRect()
    const spaceBelow = window.innerHeight - rect.bottom
    const menuHeight = 340

    let top = rect.bottom + 4
    if (spaceBelow < menuHeight && rect.top > menuHeight) {
        top = rect.top - menuHeight - 4
    }

    menuStyle.value = {
        position: 'fixed',
        top: `${Math.max(10, top)}px`,
        right: `${Math.max(10, window.innerWidth - rect.right)}px`,
        zIndex: 99999,
    }
}

const toggleMenu = async (event) => {
    event.stopPropagation()
    isOpen.value = !isOpen.value
    if (isOpen.value) {
        calculatePosition()
        await nextTick()
        calculatePosition()
    }
}

const closeMenu = () => {
    isOpen.value = false
}

const onScrollOrResize = () => {
    if (isOpen.value) {
        calculatePosition()
    }
}

const handleGlobalClick = (event) => {
    if (isOpen.value && buttonRef.value && !buttonRef.value.contains(event.target)) {
        closeMenu()
    }
}

onMounted(() => {
    window.addEventListener('click', handleGlobalClick)
    window.addEventListener('scroll', onScrollOrResize, true)
    window.addEventListener('resize', onScrollOrResize)
})

onUnmounted(() => {
    window.removeEventListener('click', handleGlobalClick)
    window.removeEventListener('scroll', onScrollOrResize, true)
    window.removeEventListener('resize', onScrollOrResize)
})

const triggerVerify = () => {
    closeMenu()
    router.post(route('admin.servers.verify', props.server.id), {}, { preserveScroll: true })
}

const triggerDiscover = () => {
    closeMenu()
    router.post(route('admin.servers.discover', props.server.id), {}, { preserveScroll: true })
}

const triggerSync = () => {
    closeMenu()
    router.post(route('admin.servers.sync', props.server.id), {}, { preserveScroll: true })
}

const handleEmit = (eventName) => {
    closeMenu()
    emit(eventName)
}
</script>

<template>
    <div class="relative inline-block text-left">
        <button
            ref="buttonRef"
            type="button"
            class="p-1.5 rounded-md text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors focus:outline-none cursor-pointer"
            :class="{ 'bg-slate-100 text-slate-700': isOpen }"
            title="Server Actions"
            @click="toggleMenu"
        >
            <EllipsisVerticalIcon class="w-4 h-4" />
        </button>

        <!-- Teleport Menu to Body so it is never clipped by table overflow -->
        <Teleport to="body">
            <div 
                v-if="isOpen" 
                class="fixed inset-0 z-[99998] bg-transparent"
                @click="closeMenu"
            ></div>

            <transition
                enter-active-class="transition duration-100 ease-out"
                enter-from-class="transform scale-95 opacity-0"
                enter-to-class="transform scale-100 opacity-100"
                leave-active-class="transition duration-75 ease-in"
                leave-from-class="transform scale-100 opacity-100"
                leave-to-class="transform scale-95 opacity-0"
            >
                <div
                    v-if="isOpen"
                    :style="menuStyle"
                    class="w-56 rounded-lg bg-white shadow-lg border border-slate-200 p-1 divide-y divide-slate-100 focus:outline-none text-xs font-medium animate-in fade-in zoom-in-95 duration-100 select-none"
                    @click.stop
                >
                    <!-- Group 1: Navigation -->
                    <div class="p-0.5 space-y-0.5">
                        <Link
                            :href="route('admin.servers.show', server.id)"
                            class="flex items-center gap-2 px-2.5 py-1.5 text-slate-700 hover:bg-[#EDE8FC] hover:text-[#673DE6] rounded-md transition-colors font-semibold"
                            @click="closeMenu"
                        >
                            <EyeIcon class="w-3.5 h-3.5 text-slate-400" />
                            <span>View Control Center</span>
                        </Link>

                        <Link
                            :href="route('admin.servers.edit', server.id)"
                            class="flex items-center gap-2 px-2.5 py-1.5 text-slate-700 hover:bg-[#EDE8FC] hover:text-[#673DE6] rounded-md transition-colors font-semibold"
                            @click="closeMenu"
                        >
                            <PencilSquareIcon class="w-3.5 h-3.5 text-slate-400" />
                            <span>Edit Configuration</span>
                        </Link>
                    </div>

                    <!-- Group 2: Remote Node Operations -->
                    <div class="p-0.5 space-y-0.5">
                        <button
                            type="button"
                            class="w-full flex items-center gap-2 px-2.5 py-1.5 text-slate-700 hover:bg-[#EDE8FC] hover:text-[#673DE6] rounded-md transition-colors text-left font-semibold cursor-pointer"
                            @click="triggerVerify"
                        >
                            <ShieldCheckIcon class="w-3.5 h-3.5 text-slate-400" />
                            <span>Verify Connectivity</span>
                        </button>

                        <button
                            type="button"
                            class="w-full flex items-center gap-2 px-2.5 py-1.5 text-slate-700 hover:bg-[#EDE8FC] hover:text-[#673DE6] rounded-md transition-colors text-left font-semibold cursor-pointer"
                            @click="triggerDiscover"
                        >
                            <MagnifyingGlassIcon class="w-3.5 h-3.5 text-slate-400" />
                            <span>Discover Hardware</span>
                        </button>

                        <button
                            type="button"
                            class="w-full flex items-center gap-2 px-2.5 py-1.5 text-slate-700 hover:bg-[#EDE8FC] hover:text-[#673DE6] rounded-md transition-colors text-left font-semibold cursor-pointer"
                            @click="triggerSync"
                        >
                            <ArrowPathIcon class="w-3.5 h-3.5 text-slate-400" />
                            <span>Synchronize Node</span>
                        </button>
                    </div>

                    <!-- Group 3: Maintenance & Credentials -->
                    <div class="p-0.5 space-y-0.5">
                        <button
                            type="button"
                            class="w-full flex items-center gap-2 px-2.5 py-1.5 text-slate-700 hover:bg-[#EDE8FC] hover:text-[#673DE6] rounded-md transition-colors text-left font-semibold cursor-pointer"
                            @click="handleEmit('open-maintenance')"
                        >
                            <WrenchScrewdriverIcon class="w-3.5 h-3.5 text-amber-500" />
                            <span>{{ server?.status === 'maintenance' ? 'Disable Maintenance' : 'Enable Maintenance' }}</span>
                        </button>

                        <button
                            type="button"
                            class="w-full flex items-center gap-2 px-2.5 py-1.5 text-slate-700 hover:bg-[#EDE8FC] hover:text-[#673DE6] rounded-md transition-colors text-left font-semibold cursor-pointer"
                            @click="handleEmit('open-host-key')"
                        >
                            <ShieldCheckIcon class="w-3.5 h-3.5 text-[#673DE6]" />
                            <span>Approve SSH Host Key</span>
                        </button>

                        <button
                            type="button"
                            class="w-full flex items-center gap-2 px-2.5 py-1.5 text-slate-700 hover:bg-[#EDE8FC] hover:text-[#673DE6] rounded-md transition-colors text-left font-semibold cursor-pointer"
                            @click="handleEmit('open-credentials')"
                        >
                            <KeyIcon class="w-3.5 h-3.5 text-slate-400" />
                            <span>Rotate Credentials</span>
                        </button>
                    </div>

                    <!-- Group 4: Decommission Node -->
                    <div class="p-0.5">
                        <button
                            type="button"
                            class="w-full flex items-center gap-2 px-2.5 py-1.5 text-rose-600 hover:bg-rose-50 hover:text-rose-700 rounded-md transition-colors text-left font-semibold cursor-pointer"
                            @click="handleEmit('open-delete')"
                        >
                            <TrashIcon class="w-3.5 h-3.5 text-rose-500" />
                            <span>Decommission Node</span>
                        </button>
                    </div>
                </div>
            </transition>
        </Teleport>
    </div>
</template>
