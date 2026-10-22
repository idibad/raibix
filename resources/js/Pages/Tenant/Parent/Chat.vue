<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';

const props = defineProps({
    threads: Array,
    recipients: Array,
    current_user: Object,
});

const activeThread = ref(props.threads && props.threads.length > 0 ? props.threads[0] : null);
const showNewThreadModal = ref(false);

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
    replyForm.post(route('tenant.parent.chat.reply', activeThread.value.id), {
        onSuccess: () => {
            replyForm.reset();
            // Update active thread content locally to show the reply instantly
            const updated = props.threads.find(t => t.id === activeThread.value.id);
            if (updated) activeThread.value = updated;
        }
    });
};

const startThread = () => {
    // Find recipient's student ID
    const recipient = props.recipients.find(r => r.id === newThreadForm.recipient_id);
    if (recipient) {
        newThreadForm.student_id = recipient.student_id;
    }
    
    newThreadForm.post(route('tenant.parent.chat.store'), {
        onSuccess: () => {
            showNewThreadModal.value = false;
            newThreadForm.reset();
            if (props.threads && props.threads.length > 0) {
                activeThread.value = props.threads[0];
            }
        }
    });
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
    <Head title="Parent-Teacher Chats" />

    <div class="flex h-screen bg-[#F8F9FA] text-[#202124] font-sans">
        <!-- Sidebar layout links and dashboard back button -->
        <div class="w-80 border-r border-[#DADCE0] bg-white flex flex-col justify-between">
            <div class="flex flex-col min-h-0 flex-1">
                <div class="px-6 py-5 border-b border-[#DADCE0] flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <a href="/parent/dashboard" class="h-8 w-8 rounded-lg flex items-center justify-center text-gray-500 hover:bg-gray-100 hover:text-black">
                            <i class="fa-solid fa-arrow-left"></i>
                        </a>
                        <h1 class="font-bold text-base text-[#202124]">Conversations</h1>
                    </div>
                    <button @click="showNewThreadModal = true" class="h-8 w-8 bg-[#137333] hover:bg-[#0d5926] text-white rounded-full flex items-center justify-center shadow-xs" title="New Message">
                        <i class="fa-solid fa-plus text-xs"></i>
                    </button>
                </div>

                <!-- Threads list -->
                <div class="flex-1 overflow-y-auto divide-y divide-gray-100">
                    <div v-if="threads.length === 0" class="p-8 text-center text-xs text-gray-400">
                        No active conversation threads. Click the '+' button to message your child's teachers.
                    </div>
                    <div 
                        v-for="thread in threads" 
                        :key="thread.id"
                        @click="selectThread(thread)"
                        :class="activeThread?.id === thread.id ? 'bg-[#E6F4EA] border-l-4 border-[#137333]' : 'hover:bg-gray-50'"
                        class="p-4 cursor-pointer transition-all flex flex-col gap-1.5"
                    >
                        <div class="flex justify-between items-start">
                            <span class="font-bold text-xs text-gray-900 truncate">{{ thread.teacher_user?.name }}</span>
                            <span class="text-[10px] text-gray-400">{{ new Date(thread.updated_at).toLocaleDateString() }}</span>
                        </div>
                        <h4 class="font-semibold text-xs text-gray-700 truncate">{{ thread.subject }}</h4>
                        <p class="text-[11px] text-gray-400 truncate">Child: {{ thread.student?.user?.name }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chat Pane -->
        <div class="flex-1 flex flex-col justify-between bg-[#F8F9FA]">
            <!-- Header -->
            <div v-if="activeThread" class="px-8 py-5 bg-white border-b border-[#DADCE0] flex items-center justify-between">
                <div>
                    <h2 class="text-base font-bold text-[#202124]">{{ activeThread.subject }}</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Teacher: <strong>{{ activeThread.teacher_user?.name }}</strong> | Child: <strong>{{ activeThread.student?.user?.name }}</strong></p>
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
                        :class="msg.sender_id === current_user.id ? 'bg-[#137333] text-white rounded-br-none' : 'bg-white text-gray-800 rounded-bl-none border border-[#DADCE0]'"
                        class="max-w-md p-4 rounded-2xl shadow-xs space-y-1.5"
                    >
                        <p class="text-sm leading-relaxed whitespace-pre-wrap">{{ msg.message }}</p>
                        <span :class="msg.sender_id === current_user.id ? 'text-white/70' : 'text-gray-400'" class="block text-[9px] text-right">
                            {{ new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }}
                        </span>
                    </div>
                </div>
            </div>
            
            <div v-else class="flex-1 flex items-center justify-center text-sm text-gray-400 bg-white">
                Select a conversation thread or start a new one to communicate.
            </div>

            <!-- Footer Reply Form -->
            <div v-if="activeThread" class="p-6 bg-white border-t border-[#DADCE0]">
                <form @submit.prevent="sendReply" class="flex gap-4">
                    <textarea 
                        v-model="replyForm.message" 
                        rows="1" 
                        placeholder="Type your message here..." 
                        required 
                        class="flex-1 rounded-xl border-gray-300 text-sm focus:ring-[#137333] focus:border-[#137333] resize-none py-3"
                    ></textarea>
                    <button type="submit" class="px-6 bg-[#137333] hover:bg-[#0d5926] text-white font-semibold text-sm rounded-xl transition-all shadow-sm flex items-center justify-center">
                        <i class="fa-solid fa-paper-plane"></i>
                    </button>
                </form>
            </div>
        </div>

        <!-- Start Conversation Modal -->
        <div v-if="showNewThreadModal" class="fixed inset-0 z-50 flex justify-end bg-black/40 backdrop-blur-xs overflow-hidden">
            <div class="absolute inset-0" @click="showNewThreadModal = false"></div>
            <div class="bg-white w-full max-w-lg shadow-2xl flex flex-col h-full z-10 animate-slideLeft border-l border-gray-200">
                <div class="p-6 sm:p-8 flex-1 overflow-y-auto space-y-6">
                    <div class="flex justify-between items-center pb-4 border-b border-gray-100">
                        <h3 class="text-lg font-bold text-gray-900 font-Outfit">Start Conversation</h3>
                        <button @click="showNewThreadModal = false" class="h-8 w-8 rounded-full hover:bg-gray-100 font-bold text-gray-500 flex items-center justify-center cursor-pointer"><i class="fa-solid fa-xmark text-lg"></i></button>
                    </div>
                    <form @submit.prevent="startThread" class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Select Teacher (Recipient)</label>
                            <select v-model="newThreadForm.recipient_id" required class="w-full rounded-lg border-gray-300 text-sm">
                                <option value="">Choose teacher...</option>
                                <option v-for="recipient in recipients" :key="recipient.id" :value="recipient.id">
                                    {{ recipient.name }} (Teacher for {{ recipient.student_name }})
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Subject</label>
                            <input type="text" v-model="newThreadForm.subject" placeholder="Message Subject" required class="w-full rounded-lg border-gray-300 text-sm focus:ring-[#137333] focus:border-[#137333]" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Message Content</label>
                            <textarea v-model="newThreadForm.message" rows="4" placeholder="Describe your inquiry..." required class="w-full rounded-lg border-gray-300 text-sm focus:ring-[#137333] focus:border-[#137333]"></textarea>
                        </div>
                        <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                            <button type="button" @click="showNewThreadModal = false" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">Cancel</button>
                            <button type="submit" class="px-6 py-2 bg-[#137333] text-white rounded-lg text-sm font-semibold hover:bg-[#0d5926]">Send Message</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>
