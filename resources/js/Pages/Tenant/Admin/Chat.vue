<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';

const props = defineProps({
    threads: Array,
    recipients: Array,
    current_user: Object,
});

const activeThread = ref(props.threads && props.threads.length > 0 ? props.threads[0] : null);
const showNewThreadModal = ref(false);
const searchTerm = ref('');
const filterRole = ref('all'); // all, teacher, parent

const replyForm = useForm({
    message: '',
});

const newThreadForm = useForm({
    recipient_id: '',
    student_id: '',
    subject: '',
    message: '',
});

const selectThread = (thread) => {
    activeThread.value = thread;
};

const sendReply = () => {
    if (!replyForm.message.trim()) return;
    replyForm.post(`/admin/chat/${activeThread.value.id}/reply`, {
        preserveScroll: true,
        onSuccess: () => {
            replyForm.reset();
            const updated = props.threads.find(t => t.id === activeThread.value.id);
            if (updated) activeThread.value = updated;
        }
    });
};

const startThread = () => {
    const recipient = props.recipients.find(r => r.id === newThreadForm.recipient_id);
    if (recipient) {
        newThreadForm.student_id = recipient.student_id;
    }
    
    newThreadForm.post('/admin/chat', {
        onSuccess: () => {
            showNewThreadModal.value = false;
            newThreadForm.reset();
            if (props.threads && props.threads.length > 0) {
                activeThread.value = props.threads[0];
            }
            router.reload({ only: ['threads'] });
        }
    });
};

const filteredThreads = computed(() => {
    return props.threads.filter(t => {
        const otherUser = t.parent_user_id === props.current_user.id ? t.teacher_user : t.parent_user;
        const matchesSearch = otherUser?.name?.toLowerCase().includes(searchTerm.value.toLowerCase()) ||
                              t.subject?.toLowerCase().includes(searchTerm.value.toLowerCase());
        
        if (filterRole.value === 'all') return matchesSearch;
        if (filterRole.value === 'teacher') return matchesSearch && otherUser?.user_type !== 'parent';
        if (filterRole.value === 'parent') return matchesSearch && otherUser?.user_type === 'parent';
        return matchesSearch;
    });
});

const getRecipientLabel = (thread) => {
    if (thread.parent_user_id === props.current_user.id) {
        return thread.teacher_user?.name + " (Teacher)";
    }
    return thread.parent_user?.name + " (Parent)";
};

// Poll every 3 seconds to fetch new messages and threads in the background
let pollInterval = null;
onMounted(() => {
    pollInterval = setInterval(() => {
        router.reload({ only: ['threads'], preserveScroll: true, preserveState: true });
    }, 3000);
});

onUnmounted(() => {
    if (pollInterval) {
        clearInterval(pollInterval);
    }
});

watch(() => props.threads, (newThreads) => {
    if (activeThread.value) {
        const updated = newThreads.find(t => t.id === activeThread.value.id);
        if (updated) {
            activeThread.value = updated;
        }
    }
}, { deep: true });
</script>

<template>
    <Head title="Principal Inbox & Communications" />

    <div class="flex h-screen bg-[#F8F9FA] text-[#202124] font-sans">
        <!-- Sidebar -->
        <div class="w-85 border-r border-[#DADCE0] bg-white flex flex-col justify-between">
            <div class="flex flex-col min-h-0 flex-1">
                <!-- Header -->
                <div class="px-6 py-5 border-b border-[#DADCE0] space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <a href="/admin/dashboard" class="h-8 w-8 rounded-full flex items-center justify-center text-gray-500 hover:bg-gray-100 transition-colors">
                                <i class="fa-solid fa-arrow-left"></i>
                            </a>
                            <h1 class="font-extrabold text-base text-[#202124]">Principal Inbox</h1>
                        </div>
                        <button @click="showNewThreadModal = true" class="h-9 w-9 bg-[#1A73E8] hover:bg-[#1557B0] text-white rounded-full flex items-center justify-center shadow-xs transition-colors" title="New Message">
                            <i class="fa-solid fa-plus text-sm"></i>
                        </button>
                    </div>

                    <!-- Search -->
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                            <i class="fa-solid fa-magnifying-glass text-xs"></i>
                        </span>
                        <input 
                            v-model="searchTerm" 
                            type="text" 
                            placeholder="Search chats or subjects..." 
                            class="w-full pl-9 pr-4 py-2 text-xs rounded-xl border border-[#DADCE0] bg-[#F8F9FA] focus:bg-white focus:ring-1 focus:ring-[#1A73E8] focus:outline-none"
                        />
                    </div>

                    <!-- Role Filters -->
                    <div class="flex bg-[#F8F9FA] p-1 rounded-xl border border-[#DADCE0] text-xs">
                        <button 
                            @click="filterRole = 'all'" 
                            :class="filterRole === 'all' ? 'bg-white font-bold text-[#1A73E8] shadow-2xs' : 'text-[#5F6368]'"
                            class="flex-1 py-1.5 rounded-lg text-center transition-all cursor-pointer"
                        >All</button>
                        <button 
                            @click="filterRole = 'teacher'" 
                            :class="filterRole === 'teacher' ? 'bg-white font-bold text-[#1A73E8] shadow-2xs' : 'text-[#5F6368]'"
                            class="flex-1 py-1.5 rounded-lg text-center transition-all cursor-pointer"
                        >Teachers</button>
                        <button 
                            @click="filterRole = 'parent'" 
                            :class="filterRole === 'parent' ? 'bg-white font-bold text-[#1A73E8] shadow-2xs' : 'text-[#5F6368]'"
                            class="flex-1 py-1.5 rounded-lg text-center transition-all cursor-pointer"
                        >Parents</button>
                    </div>
                </div>

                <!-- Threads list -->
                <div class="flex-1 overflow-y-auto divide-y divide-gray-100">
                    <div v-if="filteredThreads.length === 0" class="p-8 text-center text-xs text-gray-400">
                        No conversations found matching filters.
                    </div>
                    <div 
                        v-for="thread in filteredThreads" 
                        :key="thread.id"
                        @click="selectThread(thread)"
                        :class="activeThread?.id === thread.id ? 'bg-[#E8F0FE] border-l-4 border-[#1A73E8]' : 'hover:bg-[#F8F9FA]/60'"
                        class="p-4 cursor-pointer transition-all flex flex-col gap-1"
                    >
                        <div class="flex justify-between items-start">
                            <span class="font-bold text-xs text-gray-900 truncate">{{ getRecipientLabel(thread) }}</span>
                            <span class="text-[10px] text-gray-400 font-mono">{{ new Date(thread.updated_at).toLocaleDateString() }}</span>
                        </div>
                        <h4 class="font-semibold text-xs text-gray-700 truncate">{{ thread.subject }}</h4>
                        <p class="text-[11px] text-[#5F6368] truncate">Student Case: {{ thread.student?.user?.name || 'General' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chat Pane -->
        <div class="flex-1 flex flex-col justify-between bg-[#F8F9FA]">
            <!-- Header -->
            <div v-if="activeThread" class="px-8 py-5 bg-white border-b border-[#DADCE0] flex items-center justify-between">
                <div>
                    <h2 class="text-base font-extrabold text-[#202124]">{{ activeThread.subject }}</h2>
                    <p class="text-xs text-gray-500 mt-0.5">
                        Communication with: <strong>{{ getRecipientLabel(activeThread) }}</strong> 
                        | Student: <strong>{{ activeThread.student?.user?.name }}</strong>
                    </p>
                </div>
            </div>

            <!-- Messages Stream -->
            <div v-if="activeThread" class="flex-1 p-8 overflow-y-auto space-y-4">
                <div 
                    v-for="msg in activeThread.messages" 
                    :key="msg.id"
                    :class="msg.sender_id === current_user.id ? 'justify-end' : 'justify-start'"
                    class="flex"
                >
                    <div 
                        :class="msg.sender_id === current_user.id ? 'bg-[#1A73E8] text-white rounded-br-none' : 'bg-white text-gray-800 rounded-bl-none border border-[#DADCE0]'"
                        class="max-w-md p-4 rounded-2xl shadow-2xs space-y-1.5"
                    >
                        <p class="text-sm leading-relaxed whitespace-pre-wrap font-medium">{{ msg.message }}</p>
                        <span :class="msg.sender_id === current_user.id ? 'text-white/70' : 'text-gray-400'" class="block text-[9px] text-right font-mono">
                            {{ new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }}
                        </span>
                    </div>
                </div>
            </div>
            
            <div v-else class="flex-1 flex items-center justify-center text-sm text-gray-400 bg-white">
                Select a thread from the inbox sidebar or start a new conversation.
            </div>

            <!-- Footer Reply Form -->
            <div v-if="activeThread" class="p-6 bg-white border-t border-[#DADCE0]">
                <form @submit.prevent="sendReply" class="flex gap-4">
                    <textarea 
                        v-model="replyForm.message" 
                        rows="1" 
                        placeholder="Type your official message here..." 
                        required 
                        class="flex-1 rounded-xl border-[#DADCE0] text-sm focus:ring-1 focus:ring-[#1A73E8] focus:border-[#1A73E8] resize-none py-3 px-4"
                    ></textarea>
                    <button type="submit" class="px-6 bg-[#1A73E8] hover:bg-[#1557B0] text-white font-bold text-sm rounded-xl transition-all shadow-sm flex items-center justify-center">
                        <i class="fa-solid fa-paper-plane"></i>
                    </button>
                </form>
            </div>
        </div>

        <!-- NEW CONVERSATION MODAL -->
        <div v-if="showNewThreadModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/45 backdrop-blur-xs p-4">
            <div class="google-card w-full max-w-md rounded-2xl border border-[#DADCE0] bg-white p-6 space-y-4 shadow-xl">
                <div class="flex justify-between items-center border-b border-[#DADCE0] pb-3">
                    <h3 class="font-bold text-[#202124] text-sm flex items-center gap-1.5"><i class="fa-solid fa-message text-[#1A73E8]"></i> <span>New Message Thread</span></h3>
                    <button @click="showNewThreadModal = false" class="text-gray-400 hover:text-black font-bold text-lg"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <form @submit.prevent="startThread" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-bold text-[#5F6368] uppercase tracking-wider mb-1">Recipient (Teacher or Parent) *</label>
                        <select v-model="newThreadForm.recipient_id" required class="w-full rounded-xl bg-white border border-[#DADCE0] px-3.5 py-2.5 focus:ring-1 focus:ring-[#1A73E8]">
                            <option value="">-- Select Contact --</option>
                            <option v-for="r in recipients" :key="r.id" :value="r.id">{{ r.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-[#5F6368] uppercase tracking-wider mb-1">Subject Topic *</label>
                        <input v-model="newThreadForm.subject" type="text" placeholder="Message Subject" required class="w-full rounded-xl bg-white border border-[#DADCE0] px-3.5 py-2.5 focus:ring-1 focus:ring-[#1A73E8]" />
                    </div>
                    <div>
                        <label class="block font-bold text-[#5F6368] uppercase tracking-wider mb-1">Initial Message Text *</label>
                        <textarea v-model="newThreadForm.message" rows="4" placeholder="Type message context..." required class="w-full rounded-xl bg-white border border-[#DADCE0] p-3.5 focus:ring-1 focus:ring-[#1A73E8]"></textarea>
                    </div>
                    <div class="pt-3 flex justify-end space-x-3 border-t border-[#DADCE0]">
                        <button type="button" @click="showNewThreadModal = false" class="rounded-full px-5 py-2 text-xs font-bold text-[#5F6368] hover:bg-[#F8F9FA]">Cancel</button>
                        <button type="submit" class="rounded-full bg-[#1A73E8] hover:bg-[#1557B0] text-white px-6 py-2.5 font-bold shadow-sm">Send Initial Message</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
