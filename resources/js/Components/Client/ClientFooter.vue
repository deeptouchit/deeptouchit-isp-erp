<script setup>
import { computed } from 'vue'
import { usePage, Link } from '@inertiajs/vue3'
import { 
    ClockIcon, 
    ArrowTopRightOnSquareIcon,
    ShieldCheckIcon,
    LifebuoyIcon,
    ServerIcon
} from '@heroicons/vue/24/outline'

const page = usePage()

const copyrightText = computed(() => page.props.copyright_text || '© 2026 DeepTouch IT Ltd. All rights reserved.')
const panelVersion = computed(() => page.props.panel_version || 'v2.4.0-PRO')
const serverTime = computed(() => page.props.server_time || 'UTC')
const helpUrl = computed(() => page.props.help_url || 'https://help.deeptouchit.com')
</script>

<template>
    <footer class="bg-white border-t border-[#E2E8F0] px-4 sm:px-8 py-3.5 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500 select-none flex-shrink-0 mt-auto">
        <!-- Left: Copyright & Client Portal System Badge -->
        <div class="flex flex-wrap items-center gap-2.5 text-center sm:text-left">
            <span class="font-medium text-slate-600">{{ copyrightText }}</span>
            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-[3px] bg-slate-100 border border-slate-200 text-[10.5px] font-mono font-semibold text-slate-600">
                {{ panelVersion }}
            </span>
            <span class="hidden md:inline-flex items-center gap-1 text-[11px] text-emerald-600 font-medium bg-emerald-50/80 px-2 py-0.5 rounded-[3px] border border-emerald-200">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>Client Portal Active</span>
            </span>
        </div>

        <!-- Right: Server Time, Security, 24/7 Support Desk, Documentation -->
        <div class="flex flex-wrap items-center gap-4 text-xs font-medium text-slate-500">
            <!-- Server Clock -->
            <div class="hidden sm:flex items-center gap-1 text-slate-600 font-mono text-[11px]">
                <ClockIcon class="w-3.5 h-3.5 text-slate-400" />
                <span>{{ serverTime }}</span>
            </div>

            <!-- SSL & Security Link -->
            <Link 
                :href="route('security.ssl')" 
                class="hover:text-blue-600 transition inline-flex items-center gap-1 text-slate-600"
            >
                <ShieldCheckIcon class="w-3.5 h-3.5 text-slate-400" />
                <span>SSL & Security</span>
            </Link>

            <!-- Support Desk Link -->
            <Link 
                :href="route('tickets.index')" 
                class="hover:text-blue-600 transition inline-flex items-center gap-1 text-slate-600"
            >
                <LifebuoyIcon class="w-3.5 h-3.5 text-slate-400" />
                <span>24/7 Support Desk</span>
            </Link>

            <!-- Documentation Link -->
            <a 
                :href="helpUrl" 
                target="_blank" 
                rel="noopener noreferrer" 
                class="hover:text-blue-600 transition inline-flex items-center gap-1 text-slate-600"
            >
                <span>Documentation</span>
                <ArrowTopRightOnSquareIcon class="w-3 h-3 text-slate-400" />
            </a>
        </div>
    </footer>
</template>
