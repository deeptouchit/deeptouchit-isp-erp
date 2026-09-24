<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'

import {
    ServerIcon,
    ArrowLeftIcon,
    PencilSquareIcon,
    CheckIcon,
    ShieldCheckIcon,
    CommandLineIcon,
    KeyIcon,
    ChevronDownIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    server: {
        type: Object,
        required: true,
    },
    serverGroups: {
        type: Array,
        default: () => [],
    },
    serverTypes: {
        type: Array,
        default: () => [],
    },
    environments: {
        type: Array,
        default: () => [],
    },
    authTypes: {
        type: Array,
        default: () => [],
    },
})

const form = useForm({
    name: props.server.name || '',
    hostname: props.server.hostname || '',
    ip_address: props.server.ip_address || '',
    primary_ip: props.server.primary_ip || '',
    ipv6: props.server.ipv6 || '',
    server_group_id: props.server.server_group_id || '',
    server_type: props.server.server_type || 'worker',
    environment: props.server.environment || 'production',
    ssh_port: props.server.ssh_port || 22,
    ssh_user: props.server.ssh_user || 'root',
    auth_type: props.server.auth_type || 'password',
    ssh_host_key_policy: props.server.ssh_host_key_policy || 'tofu',
})

const submit = () => {
    form.put(route('admin.servers.update', props.server.id))
}
</script>

<template>
    <Head :title="`Edit ${server.name} - DeepTouchHost`" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Infrastructure', href: '#' },
                    { label: 'Servers & Nodes', href: route('admin.servers.index') },
                    { label: server.name, href: route('admin.servers.show', server.id) },
                    { label: 'Edit Configuration' }
                ]"
            >
                <template #actions>
                    <Link 
                        :href="route('admin.servers.show', server.id)" 
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <ArrowLeftIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>Cancel</span>
                    </Link>

                    <button 
                        type="submit" 
                        form="edit-server-form"
                        :disabled="form.processing"
                        class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] text-xs flex items-center gap-1.5 shadow-2xs transition cursor-pointer disabled:opacity-50"
                    >
                        <CheckIcon class="w-3.5 h-3.5" />
                        <span>{{ form.processing ? 'Saving...' : 'Save Changes' }}</span>
                    </button>
                </template>
            </PageHeader>

            <!-- 2. Form Container -->
            <form id="edit-server-form" @submit.prevent="submit" class="space-y-4">
                <!-- Card 1: Node Identification & Network Routing -->
                <div class="bg-white p-5 rounded-[4px] border border-[#E2E8F0] shadow-2xs space-y-4">
                    <div class="flex items-center gap-2.5 pb-2.5 border-b border-slate-100">
                        <div class="w-7 h-7 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <ServerIcon class="w-4 h-4" />
                        </div>
                        <div>
                            <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">Node Identification & Routing</h3>
                            <p class="text-[11px] text-slate-400">Update node name, hostname, IP addressing, and cluster group.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-slate-700">Display Name <span class="text-rose-500">*</span></label>
                            <input 
                                v-model="form.name" 
                                type="text"
                                class="w-full px-3 py-1.5 text-xs font-medium rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition-all shadow-2xs"
                            />
                            <p v-if="form.errors.name" class="text-[10.5px] text-rose-600 font-semibold">{{ form.errors.name }}</p>
                        </div>

                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-slate-700">Fully Qualified Hostname <span class="text-rose-500">*</span></label>
                            <input 
                                v-model="form.hostname" 
                                type="text"
                                class="w-full px-3 py-1.5 text-xs font-medium rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition-all shadow-2xs"
                            />
                            <p v-if="form.errors.hostname" class="text-[10.5px] text-rose-600 font-semibold">{{ form.errors.hostname }}</p>
                        </div>

                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-slate-700">Primary IP Address <span class="text-rose-500">*</span></label>
                            <input 
                                v-model="form.ip_address" 
                                type="text"
                                class="w-full px-3 py-1.5 text-xs font-mono font-medium rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition-all shadow-2xs"
                            />
                            <p v-if="form.errors.ip_address" class="text-[10.5px] text-rose-600 font-semibold">{{ form.errors.ip_address }}</p>
                        </div>

                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-slate-700">IPv6 Address <span class="text-slate-400 font-normal">(Optional)</span></label>
                            <input 
                                v-model="form.ipv6" 
                                type="text"
                                class="w-full px-3 py-1.5 text-xs font-mono font-medium rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition-all shadow-2xs"
                            />
                        </div>

                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-slate-700">Server Cluster Group</label>
                            <div class="relative">
                                <select
                                    v-model="form.server_group_id"
                                    class="w-full pl-3 pr-8 py-1.5 text-xs font-semibold rounded-[3px] border border-slate-200 bg-slate-50/50 hover:bg-white text-slate-700 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition-all appearance-none shadow-2xs cursor-pointer"
                                >
                                    <option value="">No Group (Default Node)</option>
                                    <option v-for="g in serverGroups" :key="g.id" :value="g.id">{{ g.name }}</option>
                                </select>
                                <ChevronDownIcon class="w-3.5 h-3.5 text-slate-400 absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none" />
                            </div>
                        </div>

                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-slate-700">Environment</label>
                            <div class="relative">
                                <select
                                    v-model="form.environment"
                                    class="w-full pl-3 pr-8 py-1.5 text-xs font-semibold rounded-[3px] border border-slate-200 bg-slate-50/50 hover:bg-white text-slate-700 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition-all appearance-none shadow-2xs cursor-pointer uppercase"
                                >
                                    <option v-for="env in environments" :key="env" :value="env">{{ env }}</option>
                                </select>
                                <ChevronDownIcon class="w-3.5 h-3.5 text-slate-400 absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Remote SSH Daemon Endpoint -->
                <div class="bg-white p-5 rounded-[4px] border border-[#E2E8F0] shadow-2xs space-y-4">
                    <div class="flex items-center gap-2.5 pb-2.5 border-b border-slate-100">
                        <div class="w-7 h-7 rounded-[3px] bg-sky-50 text-sky-600 flex items-center justify-center font-bold border border-sky-100">
                            <CommandLineIcon class="w-4 h-4" />
                        </div>
                        <div>
                            <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">Remote SSH Daemon Configuration</h3>
                            <p class="text-[11px] text-slate-400">Update execution port, user privileges, and host key pinning trust policy.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-slate-700">SSH Daemon Port <span class="text-rose-500">*</span></label>
                            <input 
                                v-model="form.ssh_port" 
                                type="number"
                                class="w-full px-3 py-1.5 text-xs font-mono font-medium rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition-all shadow-2xs"
                            />
                        </div>

                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-slate-700">SSH Username <span class="text-rose-500">*</span></label>
                            <input 
                                v-model="form.ssh_user" 
                                type="text"
                                class="w-full px-3 py-1.5 text-xs font-mono font-medium rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition-all shadow-2xs"
                            />
                        </div>

                        <div class="space-y-1 sm:col-span-2">
                            <label class="block text-xs font-bold text-slate-700">Host Key Pinning Policy</label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-0.5">
                                <label
                                    :class="[
                                        'p-3.5 rounded-[4px] border cursor-pointer transition-all block shadow-2xs',
                                        form.ssh_host_key_policy === 'tofu'
                                            ? 'border-blue-600 bg-blue-50/50'
                                            : 'border-slate-200 hover:border-slate-300 bg-white'
                                    ]"
                                >
                                    <div class="flex items-center gap-2">
                                        <input v-model="form.ssh_host_key_policy" type="radio" value="tofu" class="text-blue-600 focus:ring-blue-500" />
                                        <strong class="text-xs text-slate-900">Trust On First Use (TOFU)</strong>
                                    </div>
                                    <p class="text-[10.5px] text-slate-500 mt-1 pl-5">
                                        Observes host key fingerprint and pins for subsequent operations.
                                    </p>
                                </label>

                                <label
                                    :class="[
                                        'p-3.5 rounded-[4px] border cursor-pointer transition-all block shadow-2xs',
                                        form.ssh_host_key_policy === 'strict'
                                            ? 'border-blue-600 bg-blue-50/50'
                                            : 'border-slate-200 hover:border-slate-300 bg-white'
                                    ]"
                                >
                                    <div class="flex items-center gap-2">
                                        <input v-model="form.ssh_host_key_policy" type="radio" value="strict" class="text-blue-600 focus:ring-blue-500" />
                                        <strong class="text-xs text-slate-900">Strict Pinning Policy</strong>
                                    </div>
                                    <p class="text-[10.5px] text-slate-500 mt-1 pl-5">
                                        Strictly verifies fingerprint before running any server commands.
                                    </p>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions Footer -->
                <div class="flex items-center justify-end gap-2.5 pt-1">
                    <Link 
                        :href="route('admin.servers.show', server.id)" 
                        class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <span>Cancel</span>
                    </Link>

                    <button 
                        type="submit" 
                        :disabled="form.processing"
                        class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] text-xs flex items-center gap-1.5 shadow-2xs transition cursor-pointer disabled:opacity-50"
                    >
                        <CheckIcon class="w-3.5 h-3.5" />
                        <span>{{ form.processing ? 'Saving...' : 'Save Changes' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
