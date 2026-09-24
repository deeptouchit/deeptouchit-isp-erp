<script setup>
import { Link } from '@inertiajs/vue3'
import { ServerIcon } from '@heroicons/vue/24/outline'
</script>

<template>
    <div class="flex min-h-screen flex-col items-center bg-gradient-to-b from-slate-50 via-violet-50/30 to-white pt-10 sm:justify-center sm:pt-0 font-sans text-slate-800 selection:bg-violet-600 selection:text-white">
        <!-- Logo -->
        <div class="mb-4 text-center">
            <Link :href="route('home')" class="inline-flex items-center gap-2.5">
                <div class="w-11 h-11 rounded-2xl bg-violet-600 flex items-center justify-center shadow-lg shadow-violet-600/30 text-white">
                    <ServerIcon class="w-7 h-7" />
                </div>
                <span class="text-2xl font-black tracking-tight text-slate-900">
                    DeepTouch<span class="text-violet-600">Host</span>
                </span>
            </Link>
        </div>

        <div class="w-full overflow-hidden bg-white p-8 shadow-xl shadow-slate-200/60 sm:max-w-md sm:rounded-3xl border border-slate-200">
            <slot />
        </div>
    </div>
</template>
