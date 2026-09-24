<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PageHeader from '@/Components/UI/PageHeader.vue'
import { 
    ArrowLeftIcon, 
    PaperAirplaneIcon,
    ShieldCheckIcon,
    InformationCircleIcon,
    ChatBubbleLeftEllipsisIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    departments: {
        type: Array,
        default: () => []
    },
    subscriptions: {
        type: Array,
        default: () => []
    }
})

const form = useForm({
    subject: '',
    department: 'technical',
    priority: 'medium',
    message: '',
})

const submit = () => {
    form.post(route('tickets.store'))
}
</script>

<template>
    <Head title="Open Support Ticket - DeepTouch Host" />

    <AuthenticatedLayout>
        <div class="max-w-5xl mx-auto space-y-4">
            <!-- 1. Smart Minimal Page Header -->
            <PageHeader
                :breadcrumbs="[
                    { label: 'Support & Helpdesk', href: '#' },
                    { label: 'Support Tickets', href: route('tickets.index') },
                    { label: 'Open Support Ticket' }
                ]"
            >
                <template #actions>
                    <Link 
                        :href="route('tickets.index')" 
                        class="px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-[3px] text-xs flex items-center gap-1.5 border border-slate-200 shadow-2xs transition cursor-pointer"
                    >
                        <ArrowLeftIcon class="w-3.5 h-3.5 text-slate-500" />
                        <span>Back to Tickets</span>
                    </Link>
                </template>
            </PageHeader>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Left 2-Cols: Ticket Form -->
            <div class="lg:col-span-2 bg-white rounded-lg border border-slate-200 p-6 sm:p-8 shadow-xs">
                <form @submit.prevent="submit" class="space-y-5">
                    
                    <!-- Subject -->
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-700 mb-1.5">
                            Ticket Subject / Summary <span class="text-rose-500">*</span>
                        </label>
                        <input 
                            v-model="form.subject" 
                            type="text" 
                            required 
                            placeholder="e.g. Database connection timeout on WordPress site" 
                            class="w-full bg-slate-50/50 border border-slate-200 rounded text-xs px-3.5 py-2.5 text-slate-900 focus:ring-1 focus:ring-blue-600 focus:bg-white focus:outline-hidden" 
                        />
                        <p v-if="form.errors.subject" class="text-rose-500 text-[11px] mt-1">{{ form.errors.subject }}</p>
                    </div>

                    <!-- Department -->
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-700 mb-2">
                            Support Department <span class="text-rose-500">*</span>
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <label 
                                v-for="dept in departments" 
                                :key="dept.id"
                                :class="[form.department === dept.id ? 'border-blue-600 bg-blue-50/50 ring-1 ring-blue-600' : 'border-slate-200 hover:border-slate-300 bg-slate-50/50']"
                                class="p-3 rounded border flex flex-col justify-start cursor-pointer transition"
                            >
                                <input type="radio" v-model="form.department" :value="dept.id" class="sr-only" />
                                <span class="font-bold text-xs text-slate-900">{{ dept.name }}</span>
                                <span class="text-[10px] text-slate-500 mt-0.5 leading-relaxed">{{ dept.desc }}</span>
                            </label>
                        </div>
                        <p v-if="form.errors.department" class="text-rose-500 text-[11px] mt-1">{{ form.errors.department }}</p>
                    </div>

                    <!-- Priority Level -->
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-700 mb-2">
                            Urgency / Priority Level <span class="text-rose-500">*</span>
                        </label>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                            <label 
                                :class="[form.priority === 'low' ? 'border-blue-600 bg-blue-50/50 ring-1 ring-blue-600' : 'border-slate-200 hover:border-slate-300 bg-slate-50/50']"
                                class="p-2.5 rounded border flex flex-col items-center justify-center cursor-pointer transition text-center"
                            >
                                <input type="radio" v-model="form.priority" value="low" class="sr-only" />
                                <span class="font-bold text-xs text-slate-800">Low</span>
                                <span class="text-[10px] text-slate-500">General query</span>
                            </label>

                            <label 
                                :class="[form.priority === 'medium' ? 'border-blue-600 bg-blue-50/50 ring-1 ring-blue-600' : 'border-slate-200 hover:border-slate-300 bg-slate-50/50']"
                                class="p-2.5 rounded border flex flex-col items-center justify-center cursor-pointer transition text-center"
                            >
                                <input type="radio" v-model="form.priority" value="medium" class="sr-only" />
                                <span class="font-bold text-xs text-blue-700">Medium</span>
                                <span class="text-[10px] text-slate-500">Normal support</span>
                            </label>

                            <label 
                                :class="[form.priority === 'high' ? 'border-blue-600 bg-blue-50/50 ring-1 ring-blue-600' : 'border-slate-200 hover:border-slate-300 bg-slate-50/50']"
                                class="p-2.5 rounded border flex flex-col items-center justify-center cursor-pointer transition text-center"
                            >
                                <input type="radio" v-model="form.priority" value="high" class="sr-only" />
                                <span class="font-bold text-xs text-amber-700">High</span>
                                <span class="text-[10px] text-slate-500">Partial impact</span>
                            </label>

                            <label 
                                :class="[form.priority === 'critical' ? 'border-blue-600 bg-blue-50/50 ring-1 ring-blue-600' : 'border-slate-200 hover:border-slate-300 bg-slate-50/50']"
                                class="p-2.5 rounded border flex flex-col items-center justify-center cursor-pointer transition text-center"
                            >
                                <input type="radio" v-model="form.priority" value="critical" class="sr-only" />
                                <span class="font-bold text-xs text-rose-700">Critical</span>
                                <span class="text-[10px] text-slate-500">Service down</span>
                            </label>
                        </div>
                    </div>

                    <!-- Message -->
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-700 mb-1.5">
                            Detailed Description & Error Logs <span class="text-rose-500">*</span>
                        </label>
                        <textarea 
                            v-model="form.message" 
                            rows="6" 
                            required 
                            placeholder="Please provide full details:
1. Exact domain or URL affected
2. Error messages or status codes
3. Steps to reproduce the issue" 
                            class="w-full bg-slate-50/50 border border-slate-200 rounded text-xs p-3.5 text-slate-900 focus:ring-1 focus:ring-blue-600 focus:bg-white focus:outline-hidden font-mono leading-relaxed"
                        ></textarea>
                        <p v-if="form.errors.message" class="text-rose-500 text-[11px] mt-1">{{ form.errors.message }}</p>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex justify-end items-center gap-2.5 pt-4 border-t border-slate-100">
                        <Link 
                            :href="route('tickets.index')" 
                            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded text-xs font-bold transition cursor-pointer"
                        >
                            Cancel
                        </Link>
                        <button 
                            type="submit" 
                            :disabled="form.processing" 
                            class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded text-xs font-bold shadow-xs transition cursor-pointer disabled:opacity-50 flex items-center gap-1.5"
                        >
                            <span>{{ form.processing ? 'Opening Ticket...' : 'Submit Support Ticket' }}</span>
                            <PaperAirplaneIcon class="w-3.5 h-3.5" />
                        </button>
                    </div>

                </form>
            </div>

            <!-- Right 1-Col: Tips & SLAs -->
            <div class="space-y-4">
                
                <!-- Tips Card -->
                <div class="bg-white rounded-lg border border-slate-200 p-5 shadow-xs space-y-3">
                    <div class="flex items-center gap-2 text-slate-900 font-bold text-xs uppercase tracking-wider">
                        <InformationCircleIcon class="w-4 h-4 text-blue-600" />
                        <span>Quick Tips For Fast Help</span>
                    </div>
                    <ul class="text-xs text-slate-600 space-y-2.5 list-disc pl-4 leading-relaxed">
                        <li>Mention the exact <strong>domain name</strong> experiencing the issue.</li>
                        <li>Copy & paste any <strong>PHP error codes</strong> or stack traces.</li>
                        <li>If database related, specify the <strong>database name</strong>.</li>
                        <li>Never share your root or personal billing passwords.</li>
                    </ul>
                </div>

                <!-- 24/7 SLA Card -->
                <div class="bg-gradient-to-br from-blue-50/70 to-indigo-50/50 rounded-lg border border-blue-100 p-5 shadow-xs space-y-2.5">
                    <span class="text-xs font-bold uppercase tracking-wider text-blue-700 block">Response Commitment</span>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Our cloud operations center monitors ticket queues 24/7/365. Critical outages receive immediate engineering attention within minutes.
                    </p>
                    <div class="pt-2 border-t border-blue-100 text-[11px] text-slate-500 flex items-center gap-1.5">
                        <ShieldCheckIcon class="w-4 h-4 text-emerald-600 shrink-0" />
                        <span>Support Desk: help@deeptouchit.com</span>
                    </div>
                </div>

            </div>

        </div>
        </div>
    </AuthenticatedLayout>
</template>

