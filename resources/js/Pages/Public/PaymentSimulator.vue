<script setup>
import { ref, computed } from 'vue'
import { Head, useForm, Link } from '@inertiajs/vue3'
import { 
    ShieldCheckIcon, 
    LockClosedIcon, 
    ArrowLeftIcon, 
    CheckCircleIcon,
    ExclamationTriangleIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    order: {
        type: Object,
        default: null
    },
    invoice: {
        type: Object,
        default: null
    },
    gateway: {
        type: Object,
        required: true
    }
})

const payableAmount = computed(() => {
    if (props.order) return Number(props.order.price || 0)
    if (props.invoice) return Number(props.invoice.total_amount || 0)
    return 0
})

const referenceNo = computed(() => {
    if (props.order) return props.order.order_no
    if (props.invoice) return props.invoice.invoice_no
    return 'REF-HOSTING'
})

const cancelUrl = computed(() => {
    if (props.order) return route('order.cancel')
    if (props.invoice) return route('billing.invoice.show', props.invoice.id)
    return route('order.checkout')
})

// bKash Checkout Stages: 1 = Number, 2 = OTP, 3 = PIN
const step = ref(1)
const bkashNumber = ref(props.order?.user_data?.phone || '01712345678')
const otpCode = ref('123456')
const pinCode = ref('')
const isLoading = ref(false)
const errorMsg = ref('')

const form = useForm({
    gateway: props.gateway.slug || 'bkash',
    transaction_id: 'TRX' + Math.random().toString(36).substring(2, 10).toUpperCase()
})

const handleNextStep = () => {
    errorMsg.value = ''
    if (step.value === 1) {
        if (!bkashNumber.value || bkashNumber.value.length < 11) {
            errorMsg.value = 'Please enter a valid 11-digit bKash account number.'
            return
        }
        isLoading.value = true
        setTimeout(() => {
            isLoading.value = false
            step.value = 2
        }, 700)
    } else if (step.value === 2) {
        if (!otpCode.value || otpCode.value.length < 4) {
            errorMsg.value = 'Please enter verification code.'
            return
        }
        isLoading.value = true
        setTimeout(() => {
            isLoading.value = false
            step.value = 3
        }, 700)
    } else if (step.value === 3) {
        if (!pinCode.value || pinCode.value.length < 4) {
            errorMsg.value = 'Please enter your bKash PIN.'
            return
        }
        isLoading.value = true
        
        // Submit confirmation: either order.confirm or billing.confirm
        if (props.order) {
            form.post(route('order.confirm'))
        } else if (props.invoice) {
            form.post(route('billing.confirm', props.invoice.id))
        }
    }
}
</script>

<template>
    <Head :title="`Pay ${referenceNo} - DeepTouch Cloud Checkout`" />

    <div class="min-h-screen bg-slate-900 text-slate-100 flex flex-col items-center justify-center p-4 selection:bg-pink-500 selection:text-white">
        
        <!-- Header Brand & Cancel Action -->
        <div class="w-full max-w-md mb-4 flex items-center justify-between">
            <Link :href="cancelUrl" class="text-xs text-slate-400 hover:text-rose-400 flex items-center gap-1.5 transition font-medium">
                <ArrowLeftIcon class="w-3.5 h-3.5" />
                <span>Cancel Payment & Return</span>
            </Link>
            <div class="flex items-center gap-1 text-[11px] text-emerald-400 font-bold">
                <ShieldCheckIcon class="w-4 h-4" />
                <span>256-Bit SSL Encrypted</span>
            </div>
        </div>

        <!-- Main Card -->
        <div class="w-full max-w-md bg-white text-slate-800 rounded-2xl shadow-2xl overflow-hidden border border-slate-100">
            
            <!-- bKash Pink Header Banner -->
            <div v-if="gateway.slug === 'bkash'" class="bg-[#E2136E] p-6 text-white text-center relative">
                <div class="inline-block bg-white text-[#E2136E] px-3 py-1 rounded-full text-xs font-black tracking-wider uppercase mb-2 shadow-xs">
                    bKash Payment Gateway
                </div>
                <h2 class="text-xl font-black tracking-tight">Merchant Checkout</h2>
                <p class="text-xs text-pink-100 mt-1 font-mono">Order Ref: {{ referenceNo }}</p>
                <div class="mt-4 pt-3 border-t border-pink-400/40 flex items-center justify-between text-xs">
                    <span class="text-pink-100">Total Payable:</span>
                    <span class="text-xl font-black font-mono">৳{{ payableAmount.toLocaleString() }} BDT</span>
                </div>
            </div>

            <!-- Generic Header for other Gateways -->
            <div v-else class="bg-blue-600 p-6 text-white text-center">
                <div class="inline-block bg-white text-blue-600 px-3 py-1 rounded-full text-xs font-black uppercase mb-2">
                    {{ gateway.name || 'Payment Gateway' }}
                </div>
                <h2 class="text-xl font-black">Checkout & Pay</h2>
                <div class="mt-4 pt-3 border-t border-blue-400/40 flex items-center justify-between text-xs">
                    <span class="text-blue-100">Amount:</span>
                    <span class="text-xl font-black font-mono">৳{{ payableAmount.toLocaleString() }}</span>
                </div>
            </div>

            <!-- Body Content -->
            <div class="p-6 sm:p-7 space-y-5">
                
                <!-- Notice Badge -->
                <div class="p-3.5 rounded-xl bg-pink-50 border border-pink-200/80 text-xs text-slate-700 flex items-start gap-2.5">
                    <LockClosedIcon class="w-4 h-4 text-[#E2136E] shrink-0 mt-0.5" />
                    <div>
                        <p class="font-bold text-slate-900">Secure Pre-Payment Verification</p>
                        <p class="text-[11px] text-slate-500 mt-0.5">
                            Your account and hosting subscription will <strong>ONLY</strong> be created after payment confirmation. If cancelled or unpaid, no account is created.
                        </p>
                    </div>
                </div>

                <!-- Error Notice -->
                <div v-if="errorMsg" class="p-3 rounded-lg bg-rose-50 border border-rose-200 text-xs font-bold text-rose-700 flex items-center gap-2">
                    <ExclamationTriangleIcon class="w-4 h-4 shrink-0 text-rose-600" />
                    <span>{{ errorMsg }}</span>
                </div>

                <!-- Form Step 1: Account Number -->
                <div v-if="step === 1" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                            Your bKash Account Number
                        </label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 font-bold text-slate-400 text-xs">+88</span>
                            <input 
                                v-model="bkashNumber"
                                type="tel" 
                                maxlength="11"
                                placeholder="017XXXXXXXX" 
                                class="w-full pl-12 pr-4 py-2.5 bg-slate-50 border border-slate-300 rounded-lg font-mono font-bold text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#E2136E]/30 focus:border-[#E2136E] transition"
                            />
                        </div>
                        <p class="text-[10px] text-slate-400 mt-1">By clicking confirm, you agree to the merchant terms.</p>
                    </div>

                    <button 
                        type="button"
                        @click="handleNextStep"
                        :disabled="isLoading"
                        class="w-full py-3 px-4 rounded-lg bg-[#E2136E] hover:bg-[#c90f61] text-white font-black text-xs uppercase tracking-wider shadow-md shadow-[#E2136E]/30 transition cursor-pointer flex items-center justify-center gap-2 disabled:opacity-50"
                    >
                        <span>{{ isLoading ? 'Processing...' : 'Confirm bKash Account' }}</span>
                    </button>
                </div>

                <!-- Form Step 2: OTP Verification -->
                <div v-else-if="step === 2" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                            Verification Code (OTP)
                        </label>
                        <input 
                            v-model="otpCode"
                            type="text" 
                            maxlength="6"
                            placeholder="Enter 6-digit OTP" 
                            class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-lg font-mono font-bold text-center text-lg tracking-widest text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#E2136E]/30 focus:border-[#E2136E] transition"
                        />
                        <p class="text-[11px] text-slate-500 text-center mt-2">
                            A verification code has been sent to <span class="font-mono font-bold text-slate-900">{{ bkashNumber }}</span>
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <button 
                            type="button"
                            @click="step = 1"
                            class="w-1/3 py-2.5 px-3 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition cursor-pointer"
                        >
                            Back
                        </button>
                        <button 
                            type="button"
                            @click="handleNextStep"
                            :disabled="isLoading"
                            class="w-2/3 py-2.5 px-4 rounded-lg bg-[#E2136E] hover:bg-[#c90f61] text-white font-black text-xs uppercase tracking-wider shadow-md shadow-[#E2136E]/30 transition cursor-pointer flex items-center justify-center gap-2 disabled:opacity-50"
                        >
                            <span>{{ isLoading ? 'Verifying...' : 'Verify OTP' }}</span>
                        </button>
                    </div>
                </div>

                <!-- Form Step 3: Secret PIN -->
                <div v-else-if="step === 3" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                            Enter bKash PIN
                        </label>
                        <input 
                            v-model="pinCode"
                            type="password" 
                            maxlength="5"
                            placeholder="•••••" 
                            class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-lg font-mono font-bold text-center text-xl tracking-widest text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#E2136E]/30 focus:border-[#E2136E] transition"
                        />
                        <p class="text-[10px] text-slate-400 text-center mt-1">Your PIN is encrypted and safe.</p>
                    </div>

                    <div class="flex items-center gap-3">
                        <button 
                            type="button"
                            @click="step = 2"
                            class="w-1/3 py-2.5 px-3 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition cursor-pointer"
                        >
                            Back
                        </button>
                        <button 
                            type="button"
                            @click="handleNextStep"
                            :disabled="isLoading"
                            class="w-2/3 py-3 px-4 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs uppercase tracking-wider shadow-md shadow-emerald-600/30 transition cursor-pointer flex items-center justify-center gap-2 disabled:opacity-50"
                        >
                            <span v-if="isLoading">Processing Payment...</span>
                            <span v-else class="flex items-center gap-1.5">
                                <CheckCircleIcon class="w-4 h-4" />
                                Confirm ৳{{ payableAmount.toLocaleString() }}
                            </span>
                        </button>
                    </div>
                </div>

            </div>

            <!-- Footer Secure Footprint -->
            <div class="bg-slate-50 p-3.5 border-t border-slate-100 text-center text-[11px] text-slate-400">
                <span>Authorized Merchant: <strong>DeepTouch IT Ltd</strong> • 24/7 Monitoring</span>
            </div>

        </div>

    </div>
</template>
