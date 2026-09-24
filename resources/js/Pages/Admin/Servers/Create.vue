<script setup>
import { ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'

import {
    ServerIcon,
    ArrowLeftIcon,
    ArrowRightIcon,
    ShieldCheckIcon,
    KeyIcon,
    CommandLineIcon,
    CheckCircleIcon,
    SparklesIcon,
    CpuChipIcon,
    ChevronDownIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
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

const currentStep = ref(1)

const form = useForm({
    name: '',
    hostname: '',
    ip_address: '',
    primary_ip: '',
    ipv6: '',
    server_group_id: '',
    server_type: 'worker',
    environment: 'production',
    ssh_port: 22,
    ssh_user: 'root',
    auth_type: 'password',
    ssh_password: '',
    ssh_key: '',
    ssh_host_key_policy: 'tofu',
})

const submit = () => {
    form.post(route('admin.servers.store'))
}

const nextStep = () => {
    if (currentStep.value < 5) currentStep.value++
}

const prevStep = () => {
    if (currentStep.value > 1) currentStep.value--
}
</script>

<template>
    <Head title="Add Server Node - DeepTouchHost" />

    <AdminLayout>
        <div class="w-full space-y-3.5">
            <!-- 1. Ultra-Clean Smart Page Header (Only Breadcrumbs & Back Button) -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Infrastructure', href: '#' },
                    { label: 'Servers & Nodes', href: route('admin.servers.index') },
                    { label: 'Add Server Node' }
                ]"
            >
                <template #actions>
                    <Link 
                        :href="route('admin.servers.index')" 
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <ArrowLeftIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>Back to List</span>
                    </Link>
                </template>
            </PageHeader>

            <!-- 2. Step Navigator (Sleek Micro Tabs) -->
            <div class="bg-white p-1 rounded-[4px] border border-[#E2E8F0] shadow-2xs grid grid-cols-5 gap-1">
                <button
                    v-for="(stepName, index) in ['1. Identity', '2. SSH Port', '3. Credentials', '4. Security', '5. Review']"
                    :key="index"
                    type="button"
                    :class="[
                        'py-1.5 px-2 text-[11px] font-bold rounded-[3px] transition-all flex items-center justify-center gap-1.5 cursor-pointer',
                        currentStep === index + 1
                            ? 'bg-blue-600 text-white shadow-2xs'
                            : currentStep > index + 1
                                ? 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100'
                                : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900'
                    ]"
                    @click="currentStep = index + 1"
                >
                    <CheckCircleIcon v-if="currentStep > index + 1" class="w-3.5 h-3.5 text-emerald-600 shrink-0" />
                    <span class="truncate">{{ stepName }}</span>
                </button>
            </div>

            <!-- 3. Smart Form Card Container -->
            <form @submit.prevent="submit" class="space-y-4">
                <!-- STEP 1: IDENTITY & CLUSTER -->
                <div v-show="currentStep === 1" class="bg-white p-5 rounded-[4px] border border-[#E2E8F0] shadow-2xs space-y-4">
                    <div class="flex items-center gap-2.5 pb-2.5 border-b border-slate-100">
                        <div class="w-7 h-7 rounded-[3px] bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100">
                            <ServerIcon class="w-4 h-4" />
                        </div>
                        <div>
                            <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">Server Identity & Network IP</h3>
                            <p class="text-[11px] text-slate-400">Specify node display name, FQDN hostname, IP address, and cluster placement.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-slate-700">Server Display Name <span class="text-rose-500">*</span></label>
                            <input 
                                v-model="form.name" 
                                type="text"
                                placeholder="e.g. Master Node 01 / Web Worker"
                                class="w-full px-3 py-1.5 text-xs font-medium rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition-all shadow-2xs"
                            />
                            <p v-if="form.errors.name" class="text-[10.5px] text-rose-600 font-semibold">{{ form.errors.name }}</p>
                        </div>

                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-slate-700">Fully Qualified Hostname <span class="text-rose-500">*</span></label>
                            <input 
                                v-model="form.hostname" 
                                type="text"
                                placeholder="e.g. node01.cloud.deeptouchhost.io"
                                class="w-full px-3 py-1.5 text-xs font-medium rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition-all shadow-2xs"
                            />
                            <p v-if="form.errors.hostname" class="text-[10.5px] text-rose-600 font-semibold">{{ form.errors.hostname }}</p>
                        </div>

                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-slate-700">Primary IP Address (IPv4) <span class="text-rose-500">*</span></label>
                            <input 
                                v-model="form.ip_address" 
                                type="text"
                                placeholder="e.g. 195.201.55.10"
                                class="w-full px-3 py-1.5 text-xs font-mono font-medium rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition-all shadow-2xs"
                            />
                            <p v-if="form.errors.ip_address" class="text-[10.5px] text-rose-600 font-semibold">{{ form.errors.ip_address }}</p>
                        </div>

                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-slate-700">IPv6 Address <span class="text-slate-400 font-normal">(Optional)</span></label>
                            <input 
                                v-model="form.ipv6" 
                                type="text"
                                placeholder="e.g. 2a01:4f8:c010:d56::1"
                                class="w-full px-3 py-1.5 text-xs font-mono font-medium rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition-all shadow-2xs"
                            />
                        </div>

                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-slate-700">Server Cluster Group</label>
                            <div class="relative">
                                <select
                                    v-model="form.server_group_id"
                                    class="w-full pl-3 pr-8 py-1.5 text-xs font-semibold rounded-[3px] border border-slate-200 bg-slate-50/50 hover:bg-white text-slate-700 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition-all appearance-none shadow-2xs cursor-pointer"
                                >
                                    <option value="">No Group (Default Cluster)</option>
                                    <option v-for="g in serverGroups" :key="g.id" :value="g.id">{{ g.name }} ({{ g.location || 'Global' }})</option>
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

                <!-- STEP 2: SSH PORT & USER -->
                <div v-show="currentStep === 2" class="bg-white p-5 rounded-[4px] border border-[#E2E8F0] shadow-2xs space-y-4">
                    <div class="flex items-center gap-2.5 pb-2.5 border-b border-slate-100">
                        <div class="w-7 h-7 rounded-[3px] bg-sky-50 text-sky-600 flex items-center justify-center font-bold border border-sky-100">
                            <CommandLineIcon class="w-4 h-4" />
                        </div>
                        <div>
                            <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">SSH Connectivity Endpoint</h3>
                            <p class="text-[11px] text-slate-400">Configure remote SSH daemon port and execution username.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-slate-700">SSH Daemon Port <span class="text-rose-500">*</span></label>
                            <input 
                                v-model="form.ssh_port" 
                                type="number"
                                placeholder="22"
                                class="w-full px-3 py-1.5 text-xs font-mono font-medium rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition-all shadow-2xs"
                            />
                            <p class="text-[10px] text-slate-400">Standard port is 22. Custom SSH ports are supported.</p>
                        </div>

                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-slate-700">SSH Username <span class="text-rose-500">*</span></label>
                            <input 
                                v-model="form.ssh_user" 
                                type="text"
                                placeholder="root"
                                class="w-full px-3 py-1.5 text-xs font-mono font-medium rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition-all shadow-2xs"
                            />
                            <p class="text-[10px] text-slate-400">Recommended: root (or user with sudoers privileges).</p>
                        </div>
                    </div>
                </div>

                <!-- STEP 3: AUTHENTICATION METHOD -->
                <div v-show="currentStep === 3" class="bg-white p-5 rounded-[4px] border border-[#E2E8F0] shadow-2xs space-y-4">
                    <div class="flex items-center gap-2.5 pb-2.5 border-b border-slate-100">
                        <div class="w-7 h-7 rounded-[3px] bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold border border-emerald-100">
                            <KeyIcon class="w-4 h-4" />
                        </div>
                        <div>
                            <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">Remote Authentication Credentials</h3>
                            <p class="text-[11px] text-slate-400">Select password or private key. Credentials will be hardware-encrypted at rest.</p>
                        </div>
                    </div>

                    <!-- Auth Mode Selector -->
                    <div class="grid grid-cols-2 gap-2.5 max-w-sm">
                        <button
                            type="button"
                            :class="[
                                'p-2.5 text-xs font-bold rounded-[3px] border transition-all text-center cursor-pointer',
                                form.auth_type === 'password'
                                    ? 'border-blue-600 bg-blue-50 text-blue-700 shadow-2xs'
                                    : 'border-slate-200 text-slate-600 hover:border-slate-300'
                            ]"
                            @click="form.auth_type = 'password'"
                        >
                            <KeyIcon class="w-4 h-4 mx-auto mb-1" />
                            <span>SSH Password</span>
                        </button>

                        <button
                            type="button"
                            :class="[
                                'p-2.5 text-xs font-bold rounded-[3px] border transition-all text-center cursor-pointer',
                                form.auth_type === 'key' || form.auth_type === 'ssh_key'
                                    ? 'border-blue-600 bg-blue-50 text-blue-700 shadow-2xs'
                                    : 'border-slate-200 text-slate-600 hover:border-slate-300'
                            ]"
                            @click="form.auth_type = 'key'"
                        >
                            <CommandLineIcon class="w-4 h-4 mx-auto mb-1" />
                            <span>SSH Private Key</span>
                        </button>
                    </div>

                    <div v-if="form.auth_type === 'password'" class="max-w-md space-y-1">
                        <label class="block text-xs font-bold text-slate-700">Root / User Password <span class="text-rose-500">*</span></label>
                        <input 
                            v-model="form.ssh_password" 
                            type="password"
                            placeholder="••••••••••••••••"
                            class="w-full px-3 py-1.5 text-xs rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition-all shadow-2xs"
                        />
                        <p v-if="form.errors.ssh_password" class="text-[10.5px] text-rose-600 font-semibold">{{ form.errors.ssh_password }}</p>
                    </div>

                    <div v-else class="space-y-1">
                        <label class="block text-xs font-bold text-slate-700">OpenSSH Private Key (RSA / ED25519) <span class="text-rose-500">*</span></label>
                        <textarea
                            v-model="form.ssh_key"
                            rows="4"
                            placeholder="-----BEGIN OPENSSH PRIVATE KEY-----&#10;...&#10;-----END OPENSSH PRIVATE KEY-----"
                            class="w-full text-xs font-mono rounded-[3px] border border-slate-200 bg-slate-50/50 text-slate-900 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 p-2.5 transition-all shadow-2xs"
                        ></textarea>
                        <p v-if="form.errors.ssh_key" class="text-[10.5px] text-rose-600 font-semibold">{{ form.errors.ssh_key }}</p>
                    </div>
                </div>

                <!-- STEP 4: HOST KEY POLICY -->
                <div v-show="currentStep === 4" class="bg-white p-5 rounded-[4px] border border-[#E2E8F0] shadow-2xs space-y-4">
                    <div class="flex items-center gap-2.5 pb-2.5 border-b border-slate-100">
                        <div class="w-7 h-7 rounded-[3px] bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold border border-indigo-100">
                            <ShieldCheckIcon class="w-4 h-4" />
                        </div>
                        <div>
                            <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">SSH Host Key Trust Policy</h3>
                            <p class="text-[11px] text-slate-400">Specify host fingerprint verification rules to prevent MitM attacks.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
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
                                <strong class="text-xs text-slate-900">Trust On First Use (TOFU - Recommended)</strong>
                            </div>
                            <p class="text-[10.5px] text-slate-500 mt-1.5 pl-5">
                                Host key observed on initial connection and pinned for all future operations.
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
                                <strong class="text-xs text-slate-900">Strict Host Key Pinning</strong>
                            </div>
                            <p class="text-[10.5px] text-slate-500 mt-1.5 pl-5">
                                Requires host key fingerprint to match before commands are executed.
                            </p>
                        </label>
                    </div>
                </div>

                <!-- STEP 5: REVIEW & CONFIRM -->
                <div v-show="currentStep === 5" class="bg-white p-5 rounded-[4px] border border-[#E2E8F0] shadow-2xs space-y-4">
                    <div class="flex items-center gap-2.5 pb-2.5 border-b border-slate-100">
                        <div class="w-7 h-7 rounded-[3px] bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold border border-emerald-100">
                            <SparklesIcon class="w-4 h-4" />
                        </div>
                        <div>
                            <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">Review & Confirm Node Provisioning</h3>
                            <p class="text-[11px] text-slate-400">Confirm parameters before initiating node registration and discovery.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5 bg-slate-50/80 p-3.5 rounded-[3px] border border-slate-200 text-xs">
                        <div>
                            <span class="text-slate-400 block text-[9.5px] uppercase font-bold">Node Name</span>
                            <strong class="text-slate-900 font-bold mt-0.5 block truncate">{{ form.name || '—' }}</strong>
                        </div>

                        <div>
                            <span class="text-slate-400 block text-[9.5px] uppercase font-bold">Hostname</span>
                            <strong class="text-slate-900 font-bold mt-0.5 block truncate">{{ form.hostname || '—' }}</strong>
                        </div>

                        <div>
                            <span class="text-slate-400 block text-[9.5px] uppercase font-bold">IP & Port</span>
                            <strong class="font-mono text-slate-900 font-bold mt-0.5 block">{{ form.ip_address || '—' }}:{{ form.ssh_port }}</strong>
                        </div>

                        <div>
                            <span class="text-slate-400 block text-[9.5px] uppercase font-bold">SSH Username</span>
                            <strong class="font-mono text-slate-900 font-bold mt-0.5 block">{{ form.ssh_user }}</strong>
                        </div>

                        <div>
                            <span class="text-slate-400 block text-[9.5px] uppercase font-bold">Authentication</span>
                            <strong class="text-slate-900 font-bold capitalize mt-0.5 block">{{ form.auth_type }}</strong>
                        </div>

                        <div>
                            <span class="text-slate-400 block text-[9.5px] uppercase font-bold">Security Policy</span>
                            <strong class="text-blue-600 uppercase font-bold mt-0.5 block">{{ form.ssh_host_key_policy }}</strong>
                        </div>
                    </div>
                </div>

                <!-- Form Controls Footer -->
                <div class="flex items-center justify-between pt-1">
                    <button
                        v-if="currentStep > 1"
                        type="button"
                        class="px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs border border-slate-200 shadow-2xs transition cursor-pointer flex items-center gap-1.5"
                        @click="prevStep"
                    >
                        <ArrowLeftIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>Previous</span>
                    </button>
                    <div v-else></div>

                    <button
                        v-if="currentStep < 5"
                        type="button"
                        class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] text-xs shadow-2xs transition cursor-pointer flex items-center gap-1.5"
                        @click="nextStep"
                    >
                        <span>Next Step</span>
                        <ArrowRightIcon class="w-3.5 h-3.5" />
                    </button>

                    <button
                        v-else
                        type="submit"
                        :disabled="form.processing"
                        class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-[3px] text-xs shadow-2xs transition cursor-pointer flex items-center gap-1.5 disabled:opacity-50"
                    >
                        <SparklesIcon class="w-3.5 h-3.5" />
                        <span>{{ form.processing ? 'Registering...' : 'Register & Verify Node' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
