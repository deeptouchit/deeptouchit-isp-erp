<script setup>
import { ref, computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { 
    XMarkIcon,
    ServerIcon
} from '@heroicons/vue/24/outline'
import ClientSidebar from '@/Components/Client/ClientSidebar.vue'
import ClientTopNavbar from '@/Components/Client/ClientTopNavbar.vue'
import ClientFooter from '@/Components/Client/ClientFooter.vue'

const page = usePage()
const mobileSidebarOpen = ref(false)
const isCollapsed = ref(false)

const user = computed(() => page.props.auth?.user || {})
const currentSubscription = computed(() => page.props.currentSubscription || page.props.subscription || {})
</script>

<template>
    <div class="min-h-screen bg-slate-50 flex flex-col font-sans text-slate-700 antialiased">
        <div class="flex flex-1">
            <!-- Modular Client Sidebar (Hosting Owner UI Style) -->
            <ClientSidebar :user="user" v-model:is-collapsed="isCollapsed" />

            <!-- Mobile Drawer Overlay -->
            <div 
                v-if="mobileSidebarOpen" 
                class="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-xs lg:hidden"
                @click="mobileSidebarOpen = false"
            >
                <div 
                    class="w-64 h-full bg-white shadow-xl flex flex-col"
                    @click.stop
                >
                    <div class="p-3.5 border-b border-slate-200 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-[4px] bg-blue-600 text-white flex items-center justify-center font-bold text-sm">
                                <ServerIcon class="w-4 h-4" />
                            </div>
                            <span class="text-xs font-black text-slate-900">Client Portal</span>
                        </div>
                        <button @click="mobileSidebarOpen = false" class="p-1 text-slate-500 hover:text-slate-800 rounded-[3px] cursor-pointer">
                            <XMarkIcon class="w-5 h-5" />
                        </button>
                    </div>
                    <div class="flex-1 overflow-y-auto">
                        <ClientSidebar :user="user" :is-collapsed="false" />
                    </div>
                </div>
            </div>

            <!-- Main Content Area -->
            <div class="flex-1 flex flex-col min-w-0">
                <!-- Impersonation Notice Banner -->
                <div v-if="$page.props.auth?.is_impersonating" class="bg-amber-400 text-slate-900 px-4 py-2 text-xs font-bold flex items-center justify-between shadow-xs sticky top-0 z-30">
                    <div class="flex items-center gap-2">
                        <span class="px-1.5 py-0.5 rounded-[2px] bg-slate-950 text-amber-300 text-[10px] font-black uppercase">Impersonating</span>
                        <span>You are logged in as <strong>{{ user.name }}</strong> ({{ user.email }}).</span>
                    </div>
                    <Link :href="route('impersonation.stop')" method="post" as="button" class="px-3 py-1 bg-slate-950 text-white rounded-[3px] text-[11px] font-bold hover:bg-slate-800 transition cursor-pointer shadow-xs">
                        Return to Admin ➔
                    </Link>
                </div>

                <!-- World-Class Client Top Navigation Bar -->
                <ClientTopNavbar 
                    :user="user"
                    :current-subscription="currentSubscription"
                    v-model:is-collapsed="isCollapsed"
                    @toggle-mobile-sidebar="mobileSidebarOpen = true"
                />

                <!-- Header Slot -->
                <div v-if="$slots.header" class="bg-white border-b border-slate-200 px-4 sm:px-6 py-4">
                    <slot name="header" />
                </div>

                <!-- Main Content Slot -->
                <main class="flex-1 p-4 sm:p-6 space-y-6">
                    <slot />
                </main>

                <!-- Client Footer Component (Admin Style) -->
                <ClientFooter />
            </div>
        </div>
    </div>
</template>
