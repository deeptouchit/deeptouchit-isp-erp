<script setup>
import { ref } from 'vue'
import { GlobeAltIcon, ServerIcon, BoltIcon, ShieldCheckIcon } from '@heroicons/vue/24/outline'

const activeLocation = ref('dhaka')

const locations = [
    {
        id: 'dhaka',
        city: 'Dhaka, Bangladesh (BDIX)',
        country: 'Bangladesh',
        latency: '1-5 ms',
        peering: '10 Gbps BDIX & Local IX',
        storage: 'Pure PCIe 4.0 NVMe SSD',
        desc: 'Ultra-low latency for Bangladeshi visitors, local eCommerce, and corporate portals.',
        badge: 'Recommended for Bangladesh'
    },
    {
        id: 'singapore',
        city: 'Singapore (Asia Pacific Edge)',
        country: 'Singapore',
        latency: '30-45 ms',
        peering: '100 Gbps Tier-1 Global Transit',
        storage: 'Enterprise NVMe Enterprise RAID',
        desc: 'Ideal for regional Asian audience, SaaS platforms, and multi-country applications.',
        badge: 'Asia Hub'
    },
    {
        id: 'usa',
        city: 'Virginia / Dallas, USA (North America)',
        country: 'United States',
        latency: '180-220 ms',
        peering: 'Global Anycast Cloudflare Edge',
        storage: 'High-IOPS NVMe Cloud Cluster',
        desc: 'Best for international global traffic, US-targeted blogs, and global exports.',
        badge: 'Global Transit'
    }
]
</script>

<template>
    <div class="w-full bg-white border border-slate-200 rounded-2xl p-6 sm:p-8 shadow-xs">
        <div class="text-center max-w-3xl mx-auto mb-6">
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-bold mb-2">
                <GlobeAltIcon class="w-3.5 h-3.5" />
                <span>Enterprise Tier-3 Data Center Network</span>
            </div>
            <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                Lightning Fast Speeds Powered by <span class="text-emerald-600">10Gbps BDIX</span> & Global Edge
            </h2>
            <p class="text-xs sm:text-sm text-slate-600 mt-1">
                Choose the nearest datacenter location to give your website visitors instantaneous page loading.
            </p>
        </div>

        <!-- Location Selector Tabs -->
        <div class="flex flex-wrap justify-center gap-2 mb-6">
            <button
                v-for="loc in locations"
                :key="loc.id"
                type="button"
                @click="activeLocation = loc.id"
                :class="[
                    'px-3.5 py-2 rounded-lg text-xs font-bold transition flex items-center gap-1.5 cursor-pointer',
                    activeLocation === loc.id
                        ? 'bg-blue-600 text-white shadow-xs'
                        : 'bg-slate-100 text-slate-700 hover:bg-slate-200 border border-slate-200'
                ]"
            >
                <ServerIcon class="w-3.5 h-3.5" />
                <span>{{ loc.city }}</span>
            </button>
        </div>

        <!-- Active Datacenter Card (Light) -->
        <div
            v-for="loc in locations"
            :key="loc.id"
            v-show="activeLocation === loc.id"
            class="grid grid-cols-1 md:grid-cols-3 gap-5 bg-slate-50 border border-slate-200 rounded-xl p-5"
        >
            <div class="md:col-span-2 space-y-2.5">
                <div class="flex items-center gap-2">
                    <span class="text-sm sm:text-base font-black text-slate-900">{{ loc.city }}</span>
                    <span class="text-[9px] font-bold px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded-full">
                        {{ loc.badge }}
                    </span>
                </div>
                <p class="text-xs text-slate-600 leading-relaxed">{{ loc.desc }}</p>

                <div class="grid grid-cols-2 gap-3 pt-2">
                    <div class="p-2.5 bg-white rounded-lg border border-slate-200">
                        <span class="text-[9px] uppercase font-bold text-slate-500 block">Average Latency:</span>
                        <span class="text-xs sm:text-sm font-black text-emerald-600">{{ loc.latency }}</span>
                    </div>
                    <div class="p-2.5 bg-white rounded-lg border border-slate-200">
                        <span class="text-[9px] uppercase font-bold text-slate-500 block">Peering & Backbone:</span>
                        <span class="text-xs sm:text-sm font-black text-blue-600">{{ loc.peering }}</span>
                    </div>
                </div>
            </div>

            <div class="flex flex-col justify-center items-center bg-white border border-slate-200 rounded-lg p-4 text-center">
                <div class="w-10 h-10 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center mb-2">
                    <BoltIcon class="w-5 h-5" />
                </div>
                <span class="text-[10px] font-bold text-slate-500">Storage Architecture</span>
                <span class="text-xs sm:text-sm font-black text-slate-900 mt-0.5">{{ loc.storage }}</span>
                <span class="text-[10px] text-emerald-600 font-semibold mt-1">✓ 99.99% SLA Uptime</span>
            </div>
        </div>
    </div>
</template>
