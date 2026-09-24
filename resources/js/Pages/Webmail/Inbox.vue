<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3'
import {
    EnvelopeIcon,
    EnvelopeOpenIcon,
    InboxIcon,
    PaperAirplaneIcon,
    DocumentDuplicateIcon,
    TrashIcon,
    NoSymbolIcon,
    StarIcon,
    PlusIcon,
    MagnifyingGlassIcon,
    ArrowPathIcon,
    ArrowUturnLeftIcon,
    ArrowUturnRightIcon,
    XMarkIcon,
    ShieldCheckIcon,
    ArrowRightOnRectangleIcon,
    CheckCircleIcon,
    SparklesIcon,
    PrinterIcon,
    TagIcon,
    UserCircleIcon,
    Bars3Icon,
    ChevronDownIcon,
    ArrowsPointingOutIcon,
    ArrowsPointingInIcon,
    MinusIcon,
    CheckIcon,
    ClockIcon,
    FolderIcon,
    InformationCircleIcon
} from '@heroicons/vue/24/outline'
import { StarIcon as StarIconSolid } from '@heroicons/vue/24/solid'

const props = defineProps({
    account: {
        type: Object,
        default: () => ({
            id: 1,
            email: 'admin@domain.com',
            domain: 'domain.com',
            quota_mb: 2048,
            used_quota_mb: 1,
            used_percent: 1,
        }),
    },
    messages: {
        type: Array,
        default: () => [],
    },
})

const page = usePage()
const appName = computed(() => page.props.app_name || 'DeepTouch Host')
const appLogo = computed(() => page.props.app_logo || null)

// State
const activeFolder = ref('inbox')
const selectedMessage = ref(props.messages[0] || null)
const search = ref('')
const mobileSidebarOpen = ref(false)
const isRefreshing = ref(false)
const autoRefreshEnabled = ref(true)
const lastSyncTime = ref(new Date())
const showDetailsModal = ref(false)
const showUserDropdown = ref(false)

// Bulk Selection State
const selectedIds = ref([])

// Compose Modal State
const isComposeOpen = ref(false)
const isComposeMinimized = ref(false)
const isComposeMaximized = ref(false)
const showCcBcc = ref(false)

// Compose Form
const composeForm = useForm({
    to: '',
    cc: '',
    bcc: '',
    subject: '',
    body: '',
})

// Quick Reply Form
const quickReplyForm = useForm({
    to: '',
    subject: '',
    body: '',
})

// Toast Feedback
const feedbackMsg = ref('')
const showToast = (msg) => {
    feedbackMsg.value = msg
    setTimeout(() => {
        feedbackMsg.value = ''
    }, 4000)
}

// Watch incoming messages & preserve active selection
watch(() => props.messages, (newMessages) => {
    if (selectedMessage.value) {
        const updated = newMessages.find(m => m.id === selectedMessage.value.id)
        if (updated) {
            selectedMessage.value = updated
        }
    } else if (newMessages.length > 0) {
        selectedMessage.value = newMessages[0]
    }
}, { deep: true })

// Auto-Sync Polling Interval (10 seconds)
let syncInterval = null

const performRefresh = () => {
    isRefreshing.value = true
    router.reload({
        only: ['messages', 'account'],
        preserveScroll: true,
        preserveState: true,
        onFinish: () => {
            isRefreshing.value = false
            lastSyncTime.value = new Date()
        }
    })
}

onMounted(() => {
    if (autoRefreshEnabled.value) {
        syncInterval = setInterval(() => {
            performRefresh()
        }, 10000)
    }
})

onUnmounted(() => {
    if (syncInterval) {
        clearInterval(syncInterval)
    }
})

// Folder Definitions
const folders = [
    { id: 'inbox', name: 'Inbox', icon: InboxIcon },
    { id: 'starred', name: 'Starred', icon: StarIcon },
    { id: 'sent', name: 'Sent', icon: PaperAirplaneIcon },
    { id: 'drafts', name: 'Drafts', icon: DocumentDuplicateIcon },
    { id: 'spam', name: 'Junk / Spam', icon: NoSymbolIcon },
    { id: 'trash', name: 'Trash', icon: TrashIcon },
]

// Folder Unread Counts
const folderCounts = computed(() => {
    const counts = { inbox: 0, starred: 0, sent: 0, drafts: 0, spam: 0, trash: 0 }
    props.messages.forEach(m => {
        if (!m.is_read && m.folder === 'inbox') counts.inbox++
        if (m.is_starred) counts.starred++
        if (m.folder === 'drafts') counts.drafts++
        if (m.folder === 'trash') counts.trash++
        if (m.folder === 'spam') counts.spam++
    })
    return counts
})

// Filtered Messages
const filteredMessages = computed(() => {
    let list = props.messages || []

    if (activeFolder.value === 'starred') {
        list = list.filter(m => m.is_starred)
    } else {
        list = list.filter(m => m.folder === activeFolder.value)
    }

    if (search.value.trim()) {
        const q = search.value.toLowerCase().trim()
        list = list.filter(m =>
            (m.subject && m.subject.toLowerCase().includes(q)) ||
            (m.from_name && m.from_name.toLowerCase().includes(q)) ||
            (m.from_email && m.from_email.toLowerCase().includes(q)) ||
            (m.to && m.to.toLowerCase().includes(q)) ||
            (m.snippet && m.snippet.toLowerCase().includes(q))
        )
    }

    return list
})

// Bulk Selection Helpers
const isAllSelected = computed(() => {
    return filteredMessages.value.length > 0 && selectedIds.value.length === filteredMessages.value.length
})

const toggleSelectAll = () => {
    if (isAllSelected.value) {
        selectedIds.value = []
    } else {
        selectedIds.value = filteredMessages.value.map(m => m.id)
    }
}

const toggleSelectMessage = (id, event) => {
    if (event) event.stopPropagation()
    const index = selectedIds.value.indexOf(id)
    if (index > -1) {
        selectedIds.value.splice(index, 1)
    } else {
        selectedIds.value.push(id)
    }
}

// Custom Confirmation Dialog State
const confirmModal = ref({
    isOpen: false,
    title: '',
    description: '',
    confirmText: 'Delete',
    cancelText: 'Cancel',
    isDanger: true,
    isProcessing: false,
    onConfirm: null,
})

const openConfirmDialog = ({ title, description, confirmText = 'Delete', isDanger = true, onConfirm }) => {
    confirmModal.value = {
        isOpen: true,
        title,
        description,
        confirmText,
        cancelText: 'Cancel',
        isDanger,
        isProcessing: false,
        onConfirm,
    }
}

const handleModalConfirm = () => {
    if (confirmModal.value.onConfirm) {
        confirmModal.value.isProcessing = true
        confirmModal.value.onConfirm(() => {
            confirmModal.value.isOpen = false
            confirmModal.value.isProcessing = false
        })
    }
}

// Bulk Actions Dispatch
const isBulkProcessing = ref(false)
const executeBulkAction = (action) => {
    if (selectedIds.value.length === 0) return

    if (action === 'delete') {
        const isTrash = activeFolder.value === 'trash'
        openConfirmDialog({
            title: isTrash ? 'Permanently Delete Selected Messages?' : 'Move Selected Messages to Trash?',
            description: isTrash
                ? `You are about to permanently delete ${selectedIds.value.length} messages. This action cannot be undone.`
                : `Are you sure you want to move ${selectedIds.value.length} messages to Trash?`,
            confirmText: isTrash ? 'Delete Permanently' : 'Move to Trash',
            isDanger: true,
            onConfirm: (done) => {
                isBulkProcessing.value = true
                router.post(route('webmail.bulk-action'), {
                    ids: selectedIds.value,
                    action: 'delete'
                }, {
                    preserveScroll: true,
                    preserveState: true,
                    onSuccess: () => {
                        const count = selectedIds.value.length
                        if (selectedMessage.value && selectedIds.value.includes(selectedMessage.value.id)) {
                            selectedMessage.value = null
                        }
                        selectedIds.value = []
                        isBulkProcessing.value = false
                        showToast(isTrash ? `${count} messages deleted permanently` : `${count} messages moved to Trash`)
                        performRefresh()
                        done()
                    },
                    onError: () => {
                        isBulkProcessing.value = false
                        done()
                    },
                    onFinish: () => done()
                })
            }
        })
        return
    }

    isBulkProcessing.value = true
    router.post(route('webmail.bulk-action'), {
        ids: selectedIds.value,
        action: action
    }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            const count = selectedIds.value.length
            selectedIds.value = []
            isBulkProcessing.value = false
            if (action === 'mark_read') showToast(`${count} messages marked as read`)
            if (action === 'mark_unread') showToast(`${count} messages marked as unread`)
            if (action === 'star') showToast(`${count} messages starred`)
            if (action === 'unstar') showToast(`${count} messages unstarred`)
            performRefresh()
        },
        onError: () => {
            isBulkProcessing.value = false
        }
    })
}

// Avatar Color Generator based on email initial
const getAvatarColor = (name) => {
    const colors = [
        'bg-purple-600 text-white',
        'bg-indigo-600 text-white',
        'bg-blue-600 text-white',
        'bg-emerald-600 text-white',
        'bg-rose-600 text-white',
        'bg-amber-600 text-white',
        'bg-cyan-600 text-white',
    ]
    let hash = 0
    for (let i = 0; i < (name || '').length; i++) {
        hash = name.charCodeAt(i) + ((hash << 5) - hash)
    }
    return colors[Math.abs(hash) % colors.length]
}

// Select message & mark read
const selectMessage = (msg) => {
    selectedMessage.value = msg
    if (!msg.is_read) {
        msg.is_read = true
        router.post(route('webmail.mark-read', msg.id), { is_read: true }, {
            preserveScroll: true,
            preserveState: true,
        })
    }
}

// Toggle Star
const toggleStar = (msg, event) => {
    if (event) event.stopPropagation()
    msg.is_starred = !msg.is_starred
    router.post(route('webmail.toggle-star', msg.id), {}, {
        preserveScroll: true,
        preserveState: true,
    })
}

// Mark as Read / Unread
const toggleReadStatus = (msg) => {
    msg.is_read = !msg.is_read
    router.post(route('webmail.mark-read', msg.id), { is_read: msg.is_read }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            showToast(msg.is_read ? 'Marked as read' : 'Marked as unread')
        }
    })
}

// Delete Message
const deleteMessage = (msg) => {
    const isTrash = msg.folder === 'trash'
    if (isTrash) {
        openConfirmDialog({
            title: 'Delete Message Permanently?',
            description: `Are you sure you want to permanently delete "${msg.subject || 'this message'}"? This action cannot be undone.`,
            confirmText: 'Delete Permanently',
            isDanger: true,
            onConfirm: (done) => {
                router.delete(route('webmail.delete-message', msg.id), {
                    preserveScroll: true,
                    preserveState: true,
                    onSuccess: () => {
                        showToast('Message deleted permanently')
                        if (selectedMessage.value?.id === msg.id) {
                            const remaining = filteredMessages.value.filter(m => m.id !== msg.id)
                            selectedMessage.value = remaining[0] || null
                        }
                        done()
                    },
                    onFinish: () => done()
                })
            }
        })
    } else {
        router.delete(route('webmail.delete-message', msg.id), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                showToast('Message moved to Trash')
                if (selectedMessage.value?.id === msg.id) {
                    const remaining = filteredMessages.value.filter(m => m.id !== msg.id)
                    selectedMessage.value = remaining[0] || null
                }
            }
        })
    }
}

// Empty Trash
const emptyTrash = () => {
    openConfirmDialog({
        title: 'Empty Trash Folder?',
        description: 'All messages in the Trash folder will be permanently deleted. This action cannot be undone.',
        confirmText: 'Empty Trash Now',
        isDanger: true,
        onConfirm: (done) => {
            router.post(route('webmail.empty-trash'), {}, {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    showToast('Trash emptied successfully')
                    selectedMessage.value = null
                    done()
                },
                onFinish: () => done()
            })
        }
    })
}

// Compose Triggers
const openCompose = (to = '', subject = '', body = '') => {
    composeForm.to = to
    composeForm.subject = subject
    composeForm.body = body
    isComposeOpen.value = true
    isComposeMinimized.value = false
}

const sendEmail = () => {
    composeForm.post(route('webmail.send'), {
        preserveScroll: true,
        onSuccess: () => {
            isComposeOpen.value = false
            composeForm.reset()
            showToast('Email dispatched successfully via SMTP Relay')
            performRefresh()
        }
    })
}

// Quick Reply
const submitQuickReply = () => {
    if (!selectedMessage.value || !quickReplyForm.body.trim()) return
    quickReplyForm.to = selectedMessage.value.from_email
    quickReplyForm.subject = selectedMessage.value.subject.startsWith('Re:')
        ? selectedMessage.value.subject
        : `Re: ${selectedMessage.value.subject}`

    quickReplyForm.post(route('webmail.send'), {
        preserveScroll: true,
        onSuccess: () => {
            quickReplyForm.reset()
            showToast('Reply dispatched successfully')
            performRefresh()
        }
    })
}

// Print Message
const printMessage = () => {
    window.print()
}

// Sign out
const logout = () => {
    router.post(route('webmail.logout'))
}
</script>

<template>
    <Head title="Webmail - DeepTouch Host" />

    <div class="h-screen w-screen flex flex-col bg-slate-100 dark:bg-slate-950 font-sans text-slate-800 dark:text-slate-100 overflow-hidden select-text">

        <!-- TOP NAVIGATION BAR (Hostinger Pro Style) -->
        <header class="h-14 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 px-4 flex items-center justify-between shrink-0 z-30 shadow-xs">
            <!-- Left Brand -->
            <div class="flex items-center gap-3 w-60">
                <button
                    @click="mobileSidebarOpen = !mobileSidebarOpen"
                    class="md:hidden p-1.5 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-md cursor-pointer"
                >
                    <Bars3Icon class="w-5 h-5" />
                </button>
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-md bg-[#673de6] text-white flex items-center justify-center font-bold text-base shadow-xs tracking-tight">
                        <EnvelopeIcon class="w-5 h-5" />
                    </div>
                    <div>
                        <div class="flex items-center gap-1.5">
                            <span class="font-bold text-slate-900 dark:text-white text-sm tracking-tight">Webmail</span>
                            <span class="px-1.5 py-0.2 text-[10px] font-semibold bg-[#673de6]/10 text-[#673de6] dark:bg-[#673de6]/20 dark:text-[#a78bfa] rounded-[3px]">PRO</span>
                        </div>
                        <p class="text-[11px] text-slate-400 dark:text-slate-500 truncate max-w-[140px] leading-none mt-0.5">
                            {{ account.domain || (account.email ? account.email.split('@')[1] : 'deeptouchit.com') }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Center Global Search -->
            <div class="flex-1 max-w-2xl px-4 hidden sm:block">
                <div class="relative">
                    <MagnifyingGlassIcon class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search messages, contacts, subjects..."
                        class="w-full pl-10 pr-10 py-1.5 text-xs bg-slate-100 dark:bg-slate-800/80 border border-transparent focus:border-[#673de6] dark:focus:border-[#673de6] focus:bg-white dark:focus:bg-slate-900 text-slate-800 dark:text-slate-100 rounded-md placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-[#673de6] transition-all shadow-2xs"
                    />
                    <button
                        v-if="search"
                        @click="search = ''"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer"
                    >
                        <XMarkIcon class="w-3.5 h-3.5" />
                    </button>
                    <span v-else class="hidden md:inline-block absolute right-3 top-1/2 -translate-y-1/2 text-[10px] text-slate-400 border border-slate-300 dark:border-slate-700 px-1 rounded-[2px] bg-white dark:bg-slate-800 font-mono">/</span>
                </div>
            </div>

            <!-- Right Status & Controls -->
            <div class="flex items-center gap-2 sm:gap-3">
                <!-- Sync Indicator & Refresh -->
                <div class="flex items-center gap-1.5 bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/60 px-2.5 py-1 rounded-md text-[11px]">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span class="hidden md:inline text-slate-500 dark:text-slate-400">Live Sync</span>
                    <button
                        @click="performRefresh"
                        title="Sync now"
                        class="text-slate-500 hover:text-[#673de6] dark:hover:text-[#a78bfa] transition-colors ml-0.5 cursor-pointer"
                    >
                        <ArrowPathIcon class="w-3.5 h-3.5" :class="{ 'animate-spin text-[#673de6]': isRefreshing }" />
                    </button>
                </div>

                <!-- Storage Pill -->
                <div class="hidden lg:flex items-center gap-2 px-2.5 py-1 bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/60 rounded-md text-[11px] text-slate-600 dark:text-slate-300">
                    <span>{{ account.used_quota_mb || 1 }}MB / {{ account.quota_mb }}MB</span>
                    <div class="w-12 h-1.5 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                        <div
                            class="h-full bg-[#673de6]"
                            :style="{ width: `${Math.min(100, account.used_percent || 1)}%` }"
                        ></div>
                    </div>
                </div>

                <!-- User Profile & Dropdown -->
                <div class="relative">
                    <button
                        @click="showUserDropdown = !showUserDropdown"
                        class="flex items-center gap-2 p-1 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-md transition-colors cursor-pointer"
                    >
                        <div class="w-7 h-7 rounded-md bg-[#673de6] text-white flex items-center justify-center font-bold text-xs shadow-xs">
                            {{ account.email.charAt(0).toUpperCase() }}
                        </div>
                        <span class="hidden sm:inline font-medium text-xs text-slate-700 dark:text-slate-200 max-w-[120px] truncate">{{ account.email }}</span>
                        <ChevronDownIcon class="w-3 h-3 text-slate-400" />
                    </button>

                    <!-- Dropdown Menu -->
                    <div
                        v-if="showUserDropdown"
                        @click="showUserDropdown = false"
                        class="fixed inset-0 z-40"
                    ></div>
                    <div
                        v-if="showUserDropdown"
                        class="absolute right-0 mt-2 w-64 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-md shadow-lg py-1 z-50 text-xs text-slate-700 dark:text-slate-200"
                    >
                        <div class="px-3.5 py-2.5 border-b border-slate-100 dark:border-slate-800">
                            <p class="font-semibold text-slate-900 dark:text-white truncate">{{ account.email }}</p>
                            <p class="text-[11px] text-slate-400 mt-0.5">Quota: {{ account.used_quota_mb || 1 }} MB of {{ account.quota_mb }} MB</p>
                        </div>
                        <div class="py-1">
                            <button
                                @click="performRefresh"
                                class="w-full text-left px-3.5 py-2 hover:bg-slate-50 dark:hover:bg-slate-800 flex items-center gap-2 cursor-pointer"
                            >
                                <ArrowPathIcon class="w-4 h-4 text-slate-400" />
                                <span>Check New Emails</span>
                            </button>
                            <button
                                @click="openCompose()"
                                class="w-full text-left px-3.5 py-2 hover:bg-slate-50 dark:hover:bg-slate-800 flex items-center gap-2 cursor-pointer"
                            >
                                <PlusIcon class="w-4 h-4 text-slate-400" />
                                <span>Compose New Message</span>
                            </button>
                        </div>
                        <div class="border-t border-slate-100 dark:border-slate-800 py-1">
                            <button
                                @click="logout"
                                class="w-full text-left px-3.5 py-2 text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/30 flex items-center gap-2 font-medium cursor-pointer"
                            >
                                <ArrowRightOnRectangleIcon class="w-4 h-4" />
                                <span>Sign Out</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- MAIN 3-COLUMN WORKSPACE -->
        <div class="flex-1 flex overflow-hidden relative">

            <!-- COLUMN 1: FOLDER NAVIGATION SIDEBAR (Width: 220px) -->
            <aside
                :class="[
                    'w-56 bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 flex flex-col shrink-0 transition-transform duration-200 z-20',
                    'md:static md:translate-x-0',
                    mobileSidebarOpen ? 'fixed inset-y-14 left-0 translate-x-0 shadow-xl' : 'fixed inset-y-14 left-0 -translate-x-full'
                ]"
            >
                <!-- Compose Button (Hostinger Purple Accent) -->
                <div class="p-3">
                    <button
                        @click="openCompose()"
                        class="w-full py-2.5 px-4 bg-[#673de6] hover:bg-[#522bbb] active:scale-[0.99] text-white font-semibold text-xs rounded-md shadow-xs flex items-center justify-center gap-2 transition-all cursor-pointer"
                    >
                        <PlusIcon class="w-4 h-4 stroke-[2.5]" />
                        <span>Compose</span>
                    </button>
                </div>

                <!-- Folder Navigation List -->
                <nav class="flex-1 px-2 space-y-0.5 overflow-y-auto">
                    <button
                        v-for="folder in folders"
                        :key="folder.id"
                        @click="activeFolder = folder.id; mobileSidebarOpen = false; selectedIds = []"
                        :class="[
                            'w-full flex items-center justify-between px-3 py-2 text-xs rounded-md font-medium transition-colors cursor-pointer',
                            activeFolder === folder.id
                                ? 'bg-[#f5f2fd] text-[#673de6] dark:bg-[#673de6]/15 dark:text-[#a78bfa] font-semibold'
                                : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/60'
                        ]"
                    >
                        <div class="flex items-center gap-2.5 truncate">
                            <component :is="folder.icon" class="w-4 h-4 shrink-0" :class="activeFolder === folder.id ? 'text-[#673de6] dark:text-[#a78bfa]' : 'text-slate-400 dark:text-slate-500'" />
                            <span class="truncate">{{ folder.name }}</span>
                        </div>
                        <span
                            v-if="folderCounts[folder.id]"
                            :class="[
                                'px-1.5 py-0.2 text-[10px] rounded-[3px] font-semibold',
                                folder.id === 'inbox'
                                    ? 'bg-[#673de6] text-white'
                                    : 'bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300'
                            ]"
                        >
                            {{ folderCounts[folder.id] }}
                        </span>
                    </button>

                    <!-- Empty Trash Action -->
                    <div v-if="activeFolder === 'trash' && folderCounts.trash > 0" class="pt-2 px-1">
                        <button
                            @click="emptyTrash"
                            class="w-full py-1.5 px-2.5 text-[11px] font-medium text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-900/50 rounded-md hover:bg-rose-100 dark:hover:bg-rose-900/50 flex items-center justify-center gap-1.5 transition-colors cursor-pointer"
                        >
                            <TrashIcon class="w-3.5 h-3.5" />
                            <span>Empty Trash</span>
                        </button>
                    </div>
                </nav>

                <!-- Bottom System Details & Storage -->
                <div class="p-3 border-t border-slate-100 dark:border-slate-800/80 bg-slate-50/50 dark:bg-slate-900/40 text-[11px]">
                    <div class="flex items-center justify-between text-slate-500 dark:text-slate-400 mb-1">
                        <span class="font-medium">Storage</span>
                        <span>{{ Math.min(100, account.used_percent || 1) }}%</span>
                    </div>
                    <div class="w-full h-1.5 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden mb-2">
                        <div class="h-full bg-[#673de6]" :style="{ width: `${Math.min(100, account.used_percent || 1)}%` }"></div>
                    </div>
                    <p class="text-[10px] text-slate-400 dark:text-slate-500 truncate">
                        {{ account.used_quota_mb || 1 }} MB of {{ account.quota_mb }} MB
                    </p>
                </div>
            </aside>

            <!-- COLUMN 2: EMAIL LIST PANE (Width: 360px - 400px) -->
            <section
                :class="[
                    'w-full md:w-88 lg:w-96 bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 flex flex-col shrink-0',
                    selectedMessage && 'hidden md:flex'
                ]"
            >
                <!-- Email List Header & Bulk Toolbar -->
                <div class="p-2.5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50/70 dark:bg-slate-900/60 min-h-[46px]">
                    <!-- When No Items Selected: Standard Header -->
                    <div v-if="selectedIds.length === 0" class="flex items-center justify-between w-full">
                        <div class="flex items-center gap-2.5">
                            <input
                                type="checkbox"
                                :checked="isAllSelected"
                                @change="toggleSelectAll"
                                title="Select All"
                                class="w-3.5 h-3.5 rounded-[3px] border-slate-300 dark:border-slate-700 text-[#673de6] focus:ring-[#673de6] cursor-pointer"
                            />
                            <div>
                                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-200">
                                    {{ activeFolder }}
                                </h2>
                                <p class="text-[10px] text-slate-400 dark:text-slate-500">
                                    {{ filteredMessages.length }} {{ filteredMessages.length === 1 ? 'message' : 'messages' }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-1">
                            <button
                                @click="performRefresh"
                                title="Refresh messages"
                                class="p-1.5 text-slate-500 hover:text-[#673de6] dark:hover:text-[#a78bfa] rounded-md hover:bg-slate-200/50 dark:hover:bg-slate-800 transition-colors cursor-pointer"
                            >
                                <ArrowPathIcon class="w-4 h-4" :class="{ 'animate-spin': isRefreshing }" />
                            </button>
                        </div>
                    </div>

                    <!-- When Items Selected: Bulk Actions Toolbar (Hostinger Pro Style) -->
                    <div v-else class="flex items-center justify-between w-full bg-[#f5f2fd] dark:bg-[#673de6]/15 -m-2.5 p-2.5 rounded-t-none">
                        <div class="flex items-center gap-2">
                            <input
                                type="checkbox"
                                :checked="isAllSelected"
                                @change="toggleSelectAll"
                                class="w-3.5 h-3.5 rounded-[3px] border-slate-300 dark:border-slate-700 text-[#673de6] focus:ring-[#673de6] cursor-pointer"
                            />
                            <span class="text-xs font-bold text-[#673de6] dark:text-[#a78bfa]">
                                {{ selectedIds.length }} Selected
                            </span>
                        </div>

                        <!-- Bulk Action Buttons -->
                        <div class="flex items-center gap-1">
                            <!-- Bulk Mark as Read -->
                            <button
                                @click="executeBulkAction('mark_read')"
                                title="Mark as Read"
                                class="p-1.5 text-slate-700 dark:text-slate-200 hover:bg-white dark:hover:bg-slate-800 rounded-md transition-colors cursor-pointer"
                            >
                                <EnvelopeOpenIcon class="w-4 h-4" />
                            </button>

                            <!-- Bulk Mark as Unread -->
                            <button
                                @click="executeBulkAction('mark_unread')"
                                title="Mark as Unread"
                                class="p-1.5 text-slate-700 dark:text-slate-200 hover:bg-white dark:hover:bg-slate-800 rounded-md transition-colors cursor-pointer"
                            >
                                <EnvelopeIcon class="w-4 h-4" />
                            </button>

                            <!-- Bulk Star -->
                            <button
                                @click="executeBulkAction('star')"
                                title="Star Selected"
                                class="p-1.5 text-slate-700 dark:text-slate-200 hover:text-amber-500 hover:bg-white dark:hover:bg-slate-800 rounded-md transition-colors cursor-pointer"
                            >
                                <StarIcon class="w-4 h-4" />
                            </button>

                            <!-- Bulk Delete -->
                            <button
                                @click="executeBulkAction('delete')"
                                title="Delete Selected"
                                class="p-1.5 text-rose-600 dark:text-rose-400 hover:bg-white dark:hover:bg-slate-800 rounded-md transition-colors cursor-pointer"
                            >
                                <TrashIcon class="w-4 h-4" />
                            </button>

                            <!-- Clear Selection -->
                            <button
                                @click="selectedIds = []"
                                title="Clear Selection"
                                class="p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-md cursor-pointer"
                            >
                                <XMarkIcon class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Messages List Scroll -->
                <div class="flex-1 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800/60">
                    <!-- Empty State -->
                    <div
                        v-if="filteredMessages.length === 0"
                        class="p-8 text-center flex flex-col items-center justify-center text-slate-400 dark:text-slate-500 h-64"
                    >
                        <InboxIcon class="w-10 h-10 mb-2 stroke-1 text-slate-300 dark:text-slate-600" />
                        <p class="text-xs font-medium">No messages in {{ activeFolder }}</p>
                        <p class="text-[11px] text-slate-400 mt-1">Your mail folder is currently clean</p>
                    </div>

                    <!-- Message Card Item (Hostinger Style with Bulk Checkbox) -->
                    <div
                        v-for="msg in filteredMessages"
                        :key="msg.id"
                        @click="selectMessage(msg)"
                        :class="[
                            'p-3 transition-colors cursor-pointer relative group flex gap-2.5',
                            selectedMessage?.id === msg.id
                                ? 'bg-[#f5f2fd] dark:bg-[#1e1b4b]/40 border-l-4 border-[#673de6]'
                                : selectedIds.includes(msg.id)
                                    ? 'bg-purple-50/60 dark:bg-purple-950/20 border-l-4 border-purple-400'
                                    : 'hover:bg-slate-50 dark:hover:bg-slate-800/50 border-l-4 border-transparent',
                            !msg.is_read && 'font-semibold'
                        ]"
                    >
                        <!-- Row Checkbox (Visible on hover or when selected) & Avatar Initial -->
                        <div class="shrink-0 flex flex-col items-center gap-1.5 mt-0.5">
                            <div class="relative w-7 h-7 flex items-center justify-center">
                                <!-- Checkbox -->
                                <input
                                    type="checkbox"
                                    :checked="selectedIds.includes(msg.id)"
                                    @click="toggleSelectMessage(msg.id, $event)"
                                    class="w-4 h-4 rounded-[3px] border-slate-300 dark:border-slate-700 text-[#673de6] focus:ring-[#673de6] cursor-pointer"
                                />
                            </div>

                            <button
                                @click="toggleStar(msg, $event)"
                                class="text-slate-300 dark:text-slate-600 hover:text-amber-400 transition-colors cursor-pointer"
                            >
                                <StarIconSolid v-if="msg.is_starred" class="w-3.5 h-3.5 text-amber-400" />
                                <StarIcon v-else class="w-3.5 h-3.5" />
                            </button>
                        </div>

                        <!-- Message Content Header & Snippet -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between mb-0.5">
                                <span
                                    :class="[
                                        'text-xs truncate max-w-[150px]',
                                        !msg.is_read ? 'font-bold text-slate-900 dark:text-white' : 'text-slate-700 dark:text-slate-300 font-normal'
                                    ]"
                                >
                                    {{ msg.from_name || msg.from_email }}
                                </span>
                                <span class="text-[10px] text-slate-400 shrink-0 font-mono">{{ msg.date }}</span>
                            </div>

                            <p
                                :class="[
                                    'text-xs truncate mb-0.5',
                                    !msg.is_read ? 'font-semibold text-slate-900 dark:text-slate-100' : 'text-slate-800 dark:text-slate-200'
                                ]"
                            >
                                {{ msg.subject }}
                            </p>

                            <p class="text-[11px] text-slate-500 dark:text-slate-400 line-clamp-2 leading-tight">
                                {{ msg.snippet }}
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- COLUMN 3: EMAIL VIEW PANE (Full remaining space) -->
            <main class="flex-1 bg-white dark:bg-slate-900 flex flex-col min-w-0 overflow-hidden">
                <!-- If No Message Selected -->
                <div
                    v-if="!selectedMessage"
                    class="flex-1 flex flex-col items-center justify-center text-slate-400 dark:text-slate-600 p-8 text-center"
                >
                    <div class="w-16 h-16 rounded-md bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 mb-3 shadow-2xs">
                        <EnvelopeIcon class="w-8 h-8" />
                    </div>
                    <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-300">Select an email to read</h3>
                    <p class="text-xs text-slate-400 mt-1 max-w-sm">
                        Choose any message from the list on the left to read its full contents and reply.
                    </p>
                </div>

                <!-- When Message is Selected -->
                <div v-else class="flex-1 flex flex-col h-full overflow-hidden">

                    <!-- Reading Action Toolbar (Hostinger Style) -->
                    <div class="h-12 border-b border-slate-200 dark:border-slate-800 px-4 flex items-center justify-between bg-slate-50/50 dark:bg-slate-900/50 shrink-0">
                        <!-- Left Actions -->
                        <div class="flex items-center gap-1">
                            <!-- Mobile Back Button -->
                            <button
                                @click="selectedMessage = null"
                                class="md:hidden mr-1 p-1.5 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-800 rounded-md cursor-pointer"
                            >
                                <ArrowUturnLeftIcon class="w-4 h-4" />
                            </button>

                            <button
                                @click="openCompose(selectedMessage.from_email, `Re: ${selectedMessage.subject}`)"
                                title="Reply"
                                class="px-2.5 py-1 text-xs font-medium text-slate-700 dark:text-slate-200 hover:bg-slate-200/60 dark:hover:bg-slate-800 rounded-md flex items-center gap-1.5 transition-colors cursor-pointer"
                            >
                                <ArrowUturnLeftIcon class="w-3.5 h-3.5 text-slate-500" />
                                <span class="hidden sm:inline">Reply</span>
                            </button>

                            <button
                                @click="openCompose(selectedMessage.from_email, `Fwd: ${selectedMessage.subject}`, `\n\n--- Forwarded Message ---\nFrom: ${selectedMessage.from_email}\nSubject: ${selectedMessage.subject}\n\n${selectedMessage.body}`)"
                                title="Forward"
                                class="px-2.5 py-1 text-xs font-medium text-slate-700 dark:text-slate-200 hover:bg-slate-200/60 dark:hover:bg-slate-800 rounded-md flex items-center gap-1.5 transition-colors cursor-pointer"
                            >
                                <ArrowUturnRightIcon class="w-3.5 h-3.5 text-slate-500" />
                                <span class="hidden sm:inline">Forward</span>
                            </button>

                            <div class="h-4 w-px bg-slate-200 dark:bg-slate-700 mx-1"></div>

                            <button
                                @click="deleteMessage(selectedMessage)"
                                title="Delete Message"
                                class="p-1.5 text-slate-600 dark:text-slate-300 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-slate-200/60 dark:hover:bg-slate-800 rounded-md transition-colors cursor-pointer"
                            >
                                <TrashIcon class="w-4 h-4" />
                            </button>

                            <button
                                @click="toggleReadStatus(selectedMessage)"
                                :title="selectedMessage.is_read ? 'Mark as Unread' : 'Mark as Read'"
                                class="p-1.5 text-slate-600 dark:text-slate-300 hover:text-[#673de6] hover:bg-slate-200/60 dark:hover:bg-slate-800 rounded-md transition-colors cursor-pointer"
                            >
                                <EnvelopeIcon class="w-4 h-4" />
                            </button>

                            <button
                                @click="toggleStar(selectedMessage)"
                                title="Star"
                                class="p-1.5 text-slate-600 dark:text-slate-300 hover:text-amber-400 hover:bg-slate-200/60 dark:hover:bg-slate-800 rounded-md transition-colors cursor-pointer"
                            >
                                <StarIconSolid v-if="selectedMessage.is_starred" class="w-4 h-4 text-amber-400" />
                                <StarIcon v-else class="w-4 h-4" />
                            </button>
                        </div>

                        <!-- Right Utilities -->
                        <div class="flex items-center gap-1">
                            <button
                                @click="printMessage"
                                title="Print Email"
                                class="p-1.5 text-slate-600 dark:text-slate-300 hover:bg-slate-200/60 dark:hover:bg-slate-800 rounded-md transition-colors cursor-pointer"
                            >
                                <PrinterIcon class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    <!-- Email Reading Content Scroll -->
                    <div class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-6">

                        <!-- Subject Title & Badge -->
                        <div class="border-b border-slate-100 dark:border-slate-800 pb-4">
                            <div class="flex items-start justify-between gap-4">
                                <h1 class="text-lg sm:text-xl font-bold text-slate-900 dark:text-white leading-snug">
                                    {{ selectedMessage.subject }}
                                </h1>
                                <span class="shrink-0 px-2 py-0.5 text-[10px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 rounded-[3px] uppercase">
                                    {{ selectedMessage.folder }}
                                </span>
                            </div>
                        </div>

                        <!-- Sender & Recipient Information Card -->
                        <div class="flex items-start justify-between gap-3 bg-slate-50/70 dark:bg-slate-800/40 p-3 rounded-md border border-slate-200/60 dark:border-slate-700/50">
                            <div class="flex items-start gap-3">
                                <div
                                    :class="[
                                        'w-9 h-9 rounded-[4px] flex items-center justify-center font-bold text-xs shadow-xs',
                                        getAvatarColor(selectedMessage.from_name || selectedMessage.from_email)
                                    ]"
                                >
                                    {{ (selectedMessage.from_name || selectedMessage.from_email || '?').charAt(0).toUpperCase() }}
                                </div>
                                <div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="font-bold text-xs text-slate-900 dark:text-white">
                                            {{ selectedMessage.from_name || selectedMessage.from_email }}
                                        </span>
                                        <span class="text-xs text-slate-500 dark:text-slate-400 font-mono">
                                            &lt;{{ selectedMessage.from_email }}&gt;
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-2 mt-0.5 text-[11px] text-slate-500 dark:text-slate-400">
                                        <span>to <strong>{{ selectedMessage.to || account.email }}</strong></span>
                                        <span>•</span>
                                        <span class="flex items-center gap-1 text-emerald-600 dark:text-emerald-400">
                                            <ShieldCheckIcon class="w-3.5 h-3.5" />
                                            <span>TLS Encrypted</span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="text-xs font-medium text-slate-700 dark:text-slate-300 font-mono">{{ selectedMessage.date }}</p>
                                <p class="text-[10px] text-slate-400 mt-0.5">{{ selectedMessage.size_kb || 1 }} KB</p>
                            </div>
                        </div>

                        <!-- Email Body (Sanitized & Styled) -->
                        <div class="prose dark:prose-invert max-w-none text-xs sm:text-sm text-slate-800 dark:text-slate-100 leading-relaxed min-h-[160px] select-text cursor-text">
                            <div v-html="selectedMessage.body"></div>
                        </div>

                        <!-- Quick Reply Box (Hostinger Style) -->
                        <div class="pt-6 border-t border-slate-100 dark:border-slate-800">
                            <div class="bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 rounded-md p-3">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs font-semibold text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                                        <ArrowUturnLeftIcon class="w-3.5 h-3.5 text-[#673de6]" />
                                        <span>Quick Reply to {{ selectedMessage.from_email }}</span>
                                    </span>
                                </div>
                                <textarea
                                    v-model="quickReplyForm.body"
                                    rows="3"
                                    placeholder="Type your reply here..."
                                    class="w-full text-xs p-2.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-md focus:outline-none focus:border-[#673de6] text-slate-800 dark:text-slate-100 placeholder-slate-400"
                                ></textarea>
                                <div class="flex items-center justify-between mt-2.5">
                                    <span class="text-[11px] text-slate-400">
                                        Dispatched securely via Google SMTP Relay
                                    </span>
                                    <button
                                        @click="submitQuickReply"
                                        :disabled="quickReplyForm.processing || !quickReplyForm.body.trim()"
                                        class="px-4 py-1.5 bg-[#673de6] hover:bg-[#522bbb] disabled:opacity-50 text-white font-semibold text-xs rounded-md shadow-xs flex items-center gap-1.5 transition-all cursor-pointer"
                                    >
                                        <PaperAirplaneIcon class="w-3.5 h-3.5" />
                                        <span>{{ quickReplyForm.processing ? 'Sending...' : 'Send Reply' }}</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </main>

        </div>

        <!-- FLOATING COMPOSE DIALOG (Hostinger Style) -->
        <div
            v-if="isComposeOpen"
            :class="[
                'fixed z-50 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 shadow-2xl rounded-t-md transition-all flex flex-col',
                isComposeMaximized
                    ? 'inset-4 rounded-md'
                    : isComposeMinimized
                        ? 'bottom-0 right-6 w-72 h-10'
                        : 'bottom-0 right-6 w-full max-w-xl h-[520px]'
            ]"
        >
            <!-- Modal Header -->
            <div class="h-10 px-3.5 bg-slate-100 dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 rounded-t-md flex items-center justify-between shrink-0 select-none">
                <div class="flex items-center gap-2">
                    <div class="w-2.5 h-2.5 rounded-full bg-[#673de6]"></div>
                    <span class="text-xs font-bold text-slate-800 dark:text-slate-100">New Message</span>
                </div>
                <div class="flex items-center gap-1">
                    <button
                        @click="isComposeMinimized = !isComposeMinimized"
                        class="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-[2px] cursor-pointer"
                    >
                        <MinusIcon class="w-3.5 h-3.5" />
                    </button>
                    <button
                        @click="isComposeMaximized = !isComposeMaximized; isComposeMinimized = false"
                        class="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-[2px] cursor-pointer"
                    >
                        <ArrowsPointingOutIcon v-if="!isComposeMaximized" class="w-3.5 h-3.5" />
                        <ArrowsPointingInIcon v-else class="w-3.5 h-3.5" />
                    </button>
                    <button
                        @click="isComposeOpen = false"
                        class="p-1 text-slate-400 hover:text-rose-500 rounded-[2px] cursor-pointer"
                    >
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>
            </div>

            <!-- Modal Content (Hidden when Minimized) -->
            <div v-if="!isComposeMinimized" class="flex-1 flex flex-col overflow-hidden p-3 space-y-2">
                <!-- To field -->
                <div class="flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 pb-1.5">
                    <span class="text-xs font-semibold text-slate-400 w-12">To:</span>
                    <input
                        v-model="composeForm.to"
                        type="email"
                        placeholder="recipient@example.com"
                        class="flex-1 text-xs bg-transparent border-none focus:outline-none text-slate-800 dark:text-slate-100 placeholder-slate-400"
                        required
                    />
                    <button
                        @click="showCcBcc = !showCcBcc"
                        type="button"
                        class="text-[11px] text-[#673de6] dark:text-[#a78bfa] hover:underline cursor-pointer"
                    >
                        {{ showCcBcc ? 'Hide Cc/Bcc' : 'Cc / Bcc' }}
                    </button>
                </div>

                <!-- Cc & Bcc fields -->
                <div v-if="showCcBcc" class="space-y-1.5 border-b border-slate-100 dark:border-slate-800 pb-1.5">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-semibold text-slate-400 w-12">Cc:</span>
                        <input
                            v-model="composeForm.cc"
                            type="text"
                            placeholder="cc@example.com"
                            class="flex-1 text-xs bg-transparent border-none focus:outline-none text-slate-800 dark:text-slate-100 placeholder-slate-400"
                        />
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-semibold text-slate-400 w-12">Bcc:</span>
                        <input
                            v-model="composeForm.bcc"
                            type="text"
                            placeholder="bcc@example.com"
                            class="flex-1 text-xs bg-transparent border-none focus:outline-none text-slate-800 dark:text-slate-100 placeholder-slate-400"
                        />
                    </div>
                </div>

                <!-- Subject field -->
                <div class="flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 pb-1.5">
                    <span class="text-xs font-semibold text-slate-400 w-12">Subject:</span>
                    <input
                        v-model="composeForm.subject"
                        type="text"
                        placeholder="Subject..."
                        class="flex-1 text-xs bg-transparent border-none focus:outline-none text-slate-800 dark:text-slate-100 placeholder-slate-400 font-medium"
                        required
                    />
                </div>

                <!-- Body field -->
                <div class="flex-1 flex flex-col min-h-0 pt-1">
                    <textarea
                        v-model="composeForm.body"
                        placeholder="Write your email here..."
                        class="w-full flex-1 text-xs p-2 bg-transparent border-none focus:outline-none text-slate-800 dark:text-slate-100 placeholder-slate-400 resize-none font-sans"
                        required
                    ></textarea>
                </div>

                <!-- Bottom Action Bar -->
                <div class="pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-2">
                        <button
                            @click="sendEmail"
                            :disabled="composeForm.processing || !composeForm.to || !composeForm.subject"
                            class="px-5 py-2 bg-[#673de6] hover:bg-[#522bbb] disabled:opacity-50 text-white font-semibold text-xs rounded-md shadow-xs flex items-center gap-2 transition-all cursor-pointer"
                        >
                            <PaperAirplaneIcon class="w-3.5 h-3.5 stroke-2" />
                            <span>{{ composeForm.processing ? 'Sending...' : 'Send' }}</span>
                        </button>
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            @click="isComposeOpen = false; composeForm.reset()"
                            title="Discard Draft"
                            class="p-1.5 text-slate-400 hover:text-rose-600 rounded-md transition-colors cursor-pointer"
                        >
                            <TrashIcon class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- CUSTOM CONFIRMATION MODAL (Hostinger Pro Style) -->
        <div
            v-if="confirmModal.isOpen"
            class="fixed inset-0 z-60 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
        >
            <div
                class="w-full max-w-md bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg shadow-2xl overflow-hidden animate-in fade-in zoom-in-95 duration-150"
            >
                <div class="p-5">
                    <div class="flex items-start gap-3.5">
                        <div class="w-9 h-9 rounded-md bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0">
                            <TrashIcon class="w-5 h-5 stroke-[2]" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                                {{ confirmModal.title }}
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1.5 leading-relaxed">
                                {{ confirmModal.description }}
                            </p>
                        </div>
                    </div>
                </div>
                <div class="px-5 py-3 bg-slate-50 dark:bg-slate-800/60 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-2.5">
                    <button
                        @click="confirmModal.isOpen = false"
                        :disabled="confirmModal.isProcessing"
                        class="px-3.5 py-2 text-xs font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-200/60 dark:hover:bg-slate-700 rounded-md transition-colors cursor-pointer"
                    >
                        {{ confirmModal.cancelText }}
                    </button>
                    <button
                        @click="handleModalConfirm"
                        :disabled="confirmModal.isProcessing"
                        class="px-4 py-2 text-xs font-semibold text-white bg-rose-600 hover:bg-rose-700 disabled:opacity-50 rounded-md shadow-xs flex items-center gap-1.5 transition-all cursor-pointer"
                    >
                        <TrashIcon class="w-3.5 h-3.5" />
                        <span>{{ confirmModal.isProcessing ? 'Processing...' : confirmModal.confirmText }}</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Toast Notification Banner -->
        <transition
            enter-active-class="transform ease-out duration-300 transition"
            enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
            enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
            leave-active-class="transition ease-in duration-100"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="feedbackMsg"
                class="fixed bottom-5 left-1/2 -translate-x-1/2 z-50 bg-slate-900 text-white text-xs py-2 px-4 rounded-md shadow-lg flex items-center gap-2 border border-slate-700"
            >
                <CheckCircleIcon class="w-4 h-4 text-emerald-400" />
                <span>{{ feedbackMsg }}</span>
            </div>
        </transition>

    </div>
</template>

<style scoped>
/* Scrollbar styling for a sleek Hostinger feel */
::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}
::-webkit-scrollbar-track {
    background: transparent;
}
::-webkit-scrollbar-thumb {
    background: rgba(148, 163, 184, 0.4);
    border-radius: 3px;
}
::-webkit-scrollbar-thumb:hover {
    background: rgba(148, 163, 184, 0.6);
}
</style>
