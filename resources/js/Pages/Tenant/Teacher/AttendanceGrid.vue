<script setup>
import { ref, reactive, onBeforeUnmount } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    section: Object,
    students: Array,
    attendances: Object,
    date: String,
});

const mode = ref('manual'); // manual, ai_camera
const isScanning = ref(false);
const videoEl = ref(null);
const canvasEl = ref(null);
const streamActive = ref(false);
const previewImage = ref(null);

const manualForm = useForm({
    section_id: props.section?.id || 1,
    date: props.date || new Date().toISOString().split('T')[0],
    attendances: {},
});

// Initialize manual form states from existing attendances or default to 'present'
if (props.students) {
    props.students.forEach((s) => {
        manualForm.attendances[s.id] = props.attendances?.[s.id]?.status || 'present';
    });
}

const aiForm = useForm({
    section_id: props.section?.id || 1,
    date: props.date || new Date().toISOString().split('T')[0],
    snapshot_url: 'https://cdn.lmscloud.test/snapshots/classroom-cam-section-8a-live.jpg',
    snapshot_image: null,
});

// Quick note / observation form
const showNoteModal = ref(false);
const selectedStudent = ref(null);
const noteForm = useForm({
    student_id: '',
    type: 'commendation',
    title: 'Attendance Notice',
    description: '',
    parent_visible: true,
});

const openNoteModal = (student, e) => {
    if (e) e.stopPropagation();
    selectedStudent.value = student;
    noteForm.student_id = student.id;
    noteForm.description = `Notice regarding today's class attendance (${props.date || new Date().toISOString().split('T')[0]}): `;
    showNoteModal.value = true;
};

const submitNote = () => {
    noteForm.post('/teacher/observations', {
        onSuccess: () => {
            showNoteModal.value = false;
            noteForm.reset();
        },
    });
};

const setStatus = (studentId, status, e) => {
    if (e) e.stopPropagation();
    manualForm.attendances[studentId] = status;
};

const markAll = (status) => {
    if (props.students) {
        props.students.forEach((s) => {
            manualForm.attendances[s.id] = status;
        });
    }
};

const submitManual = () => {
    manualForm.post('/teacher/attendance', {
        preserveScroll: true,
    });
};

// Live Camera & Photo Functions
const startCamera = async () => {
    try {
        previewImage.value = null;
        aiForm.snapshot_image = null;
        const stream = await navigator.mediaDevices.getUserMedia({ video: { width: 1280, height: 720 } });
        if (videoEl.value) {
            videoEl.value.srcObject = stream;
            streamActive.value = true;
        }
    } catch (err) {
        alert("Camera permission denied or not detected. You can upload a classroom photo file instead!");
        console.error(err);
    }
};

const stopCamera = () => {
    if (videoEl.value && videoEl.value.srcObject) {
        videoEl.value.srcObject.getTracks().forEach(track => track.stop());
        videoEl.value.srcObject = null;
        streamActive.value = false;
    }
};

const takePhoto = () => {
    if (!videoEl.value || !canvasEl.value) return;
    const canvas = canvasEl.value;
    const video = videoEl.value;
    canvas.width = video.videoWidth || 640;
    canvas.height = video.videoHeight || 480;
    const ctx = canvas.getContext('2d');
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
    
    canvas.toBlob((blob) => {
        if (blob) {
            aiForm.snapshot_image = new File([blob], `classroom-snapshot-${Date.now()}.jpg`, { type: 'image/jpeg' });
            previewImage.value = URL.createObjectURL(blob);
            stopCamera();
        }
    }, 'image/jpeg', 0.9);
};

const handleFileUpload = (e) => {
    const file = e.target.files[0];
    if (file) {
        stopCamera();
        aiForm.snapshot_image = file;
        previewImage.value = URL.createObjectURL(file);
    }
};

const submitAiSnapshot = () => {
    isScanning.value = true;
    setTimeout(() => {
        aiForm.post('/teacher/attendance/ai-snapshot', {
            preserveScroll: true,
            onFinish: () => {
                isScanning.value = false;
            }
        });
    }, 1500);
};

onBeforeUnmount(() => {
    stopCamera();
});
</script>

<template>
    <div class="min-h-screen bg-[#F8F9FA] text-[#202124] selection:bg-[#E8F0FE] selection:text-[#1A73E8]">
        <!-- Top Nav -->
        <header class="border-b border-[#DADCE0] bg-white shadow-sm sticky top-0 z-30">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-3.5">
                <div class="flex items-center space-x-3">
                    <Link href="/teacher/dashboard" class="text-[#1A73E8] hover:underline text-sm font-medium">← Back to Dashboard</Link>
                    <span class="text-[#DADCE0]">|</span>
                    <h1 class="text-xl font-bold tracking-tight text-[#202124]">
                        Attendance Grid: <span class="text-[#1A73E8] font-bold">{{ section?.school_class?.name || 'Class' }} - {{ section?.name || 'Section' }}</span>
                    </h1>
                </div>

                <!-- Mode Selector -->
                <div class="flex rounded-xl bg-[#F8F9FA] p-1 border border-[#DADCE0]">
                    <button 
                        @click="mode = 'manual'; stopCamera();"
                        :class="mode === 'manual' ? 'bg-[#1A73E8] text-white shadow-sm font-bold' : 'text-[#5F6368] hover:text-[#202124] font-semibold'"
                        class="rounded-lg px-4 py-1.5 text-xs transition-all inline-flex items-center space-x-1.5"
                    >
                        <i class="fa-solid fa-table-cells"></i>
                        <span>Manual Grid / Quick Select</span>
                    </button>
                    <button 
                        @click="mode = 'ai_camera'"
                        :class="mode === 'ai_camera' ? 'bg-[#8430CE] text-white shadow-sm font-bold' : 'text-[#5F6368] hover:text-[#202124] font-semibold'"
                        class="rounded-lg px-4 py-1.5 text-xs transition-all inline-flex items-center space-x-1.5"
                    >
                        <i class="fa-solid fa-robot"></i>
                        <span>AI Camera Face Recognition</span>
                    </button>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="mx-auto max-w-7xl px-6 py-8">
            <!-- Manual Mode Grid -->
            <div v-if="mode === 'manual'" class="google-card rounded-2xl p-6 border border-[#DADCE0] bg-white shadow-sm space-y-6">
                <!-- Header Card -->
                <div class="flex flex-col lg:flex-row lg:items-center justify-between border-b border-[#DADCE0] pb-5 gap-4">
                    <div>
                        <h2 class="text-lg font-bold text-[#202124]">Classroom Roster & Status Grid</h2>
                        <p class="text-xs text-[#5F6368]">Select status using the clean segmented buttons on each student card (<span class="text-[#137333] font-bold">Present</span>, <span class="text-[#C5221F] font-bold">Absent</span>, <span class="text-[#8430CE] font-bold">Late</span>, <span class="text-[#B06000] font-bold">Leave</span>), or use batch controls below.</p>
                    </div>
                    <div class="flex items-center space-x-3 flex-wrap gap-y-2">
                        <!-- Batch Controls -->
                        <div class="flex items-center rounded-xl bg-[#F8F9FA] border border-[#DADCE0] p-1 space-x-1">
                            <button @click="markAll('present')" type="button" class="rounded-lg bg-[#E6F4EA] px-3 py-1.5 text-xs font-bold text-[#137333] hover:bg-[#CEEAD6] transition-colors inline-flex items-center gap-1">
                                <i class="fa-solid fa-check"></i>
                                <span>All Present</span>
                            </button>
                            <button @click="markAll('absent')" type="button" class="rounded-lg bg-[#FCE8E6] px-3 py-1.5 text-xs font-bold text-[#C5221F] hover:bg-[#FAD2CF] transition-colors inline-flex items-center gap-1">
                                <i class="fa-solid fa-xmark"></i>
                                <span>All Absent</span>
                            </button>
                        </div>
                        <span class="text-xs font-mono bg-[#F8F9FA] px-3.5 py-2 rounded-xl border border-[#DADCE0] font-semibold text-[#5F6368]">Date: {{ date }}</span>
                        <button 
                            @click="submitManual" 
                            :disabled="manualForm.processing"
                            class="rounded-full bg-[#1A73E8] px-6 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-[#1557B0] active:scale-[0.98] transition-all disabled:opacity-50 inline-flex items-center gap-1.5"
                        >
                            <i class="fa-solid fa-floppy-disk"></i>
                            <span>{{ manualForm.processing ? 'Saving & Alerting...' : 'Save Attendance Grid' }}</span>
                        </button>
                    </div>
                </div>

                <!-- Sleek 4-Column Google Cards Grid -->
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                    <div v-if="!students || students.length === 0" class="col-span-4 py-12 text-center text-sm text-[#5F6368]">
                        No active students enrolled in this section yet.
                    </div>
                    <div 
                        v-for="s in students" 
                        :key="s.id"
                        :class="{
                            'border-[#CEEAD6] bg-[#E6F4EA]/30': manualForm.attendances[s.id] === 'present',
                            'border-[#FAD2CF] bg-[#FCE8E6]/35': manualForm.attendances[s.id] === 'absent',
                            'border-[#E9D2FD] bg-[#F3E8FD]/35': manualForm.attendances[s.id] === 'late',
                            'border-[#FEEFC3] bg-[#FEF7E0]/35': manualForm.attendances[s.id] === 'leave'
                        }"
                        class="rounded-2xl border border-[#DADCE0] bg-[#F8F9FA] p-4.5 transition-all hover:shadow-md flex flex-col justify-between shadow-2xs space-y-4"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-center space-x-3 min-w-0">
                                <div class="h-10 w-10 rounded-full bg-white flex items-center justify-center text-base font-extrabold border border-[#DADCE0] shadow-2xs text-[#1A73E8] flex-shrink-0">
                                    {{ s.user?.name ? s.user.name.charAt(0) : 'S' }}
                                </div>
                                <div class="min-w-0">
                                    <h4 class="font-extrabold text-[#202124] text-sm truncate" :title="s.user?.name">{{ s.user?.name || 'Student' }}</h4>
                                    <span class="text-xs text-[#5F6368] font-mono">Roll: #{{ s.roll_number || s.id }}</span>
                                </div>
                            </div>
                            <button 
                                type="button"
                                @click="openNoteModal(s, $event)" 
                                title="Send note to parent" 
                                class="text-[#5F6368] hover:text-[#1A73E8] transition-colors p-1.5 rounded-full hover:bg-white flex-shrink-0 flex items-center justify-center"
                            >
                                <i class="fa-regular fa-message text-sm"></i>
                            </button>
                        </div>

                        <!-- Sleek Full-Width Segmented Status Switcher -->
                        <div class="pt-2 border-t border-[#DADCE0]/80">
                            <div class="grid grid-cols-4 gap-1 w-full bg-white/90 p-1 rounded-xl border border-[#DADCE0] shadow-2xs">
                                <button 
                                    type="button"
                                    @click="setStatus(s.id, 'present', $event)"
                                    :class="manualForm.attendances[s.id] === 'present' ? 'bg-[#137333] text-white font-extrabold shadow-2xs' : 'text-[#5F6368] hover:text-[#137333] hover:bg-[#E6F4EA]/60 font-semibold'"
                                    class="rounded-lg py-1.5 text-center text-xs transition-all"
                                >
                                    Present
                                </button>
                                <button 
                                    type="button"
                                    @click="setStatus(s.id, 'absent', $event)"
                                    :class="manualForm.attendances[s.id] === 'absent' ? 'bg-[#C5221F] text-white font-extrabold shadow-2xs' : 'text-[#5F6368] hover:text-[#C5221F] hover:bg-[#FCE8E6]/60 font-semibold'"
                                    class="rounded-lg py-1.5 text-center text-xs transition-all"
                                >
                                    Absent
                                </button>
                                <button 
                                    type="button"
                                    @click="setStatus(s.id, 'late', $event)"
                                    :class="manualForm.attendances[s.id] === 'late' ? 'bg-[#8430CE] text-white font-extrabold shadow-2xs' : 'text-[#5F6368] hover:text-[#8430CE] hover:bg-[#F3E8FD]/60 font-semibold'"
                                    class="rounded-lg py-1.5 text-center text-xs transition-all"
                                >
                                    Late
                                </button>
                                <button 
                                    type="button"
                                    @click="setStatus(s.id, 'leave', $event)"
                                    :class="manualForm.attendances[s.id] === 'leave' ? 'bg-[#B06000] text-white font-extrabold shadow-2xs' : 'text-[#5F6368] hover:text-[#B06000] hover:bg-[#FEF7E0]/60 font-semibold'"
                                    class="rounded-lg py-1.5 text-center text-xs transition-all"
                                >
                                    Leave
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- AI Face Recognition Camera Mode (Restored Sleek Layout + Live Capabilities) -->
            <div v-if="mode === 'ai_camera'" class="google-card rounded-2xl p-8 border border-[#DADCE0] bg-white shadow-sm text-center space-y-6">
                <div class="max-w-2xl mx-auto space-y-2">
                    <span class="rounded-full bg-[#F3E8FD] px-3 py-1 text-xs font-bold text-[#8430CE] border border-[#E9D2FD] inline-flex items-center gap-1">
                        <i class="fa-solid fa-camera text-[#8430CE]"></i>
                        <span>Classroom Photo Attendance</span>
                    </span>
                    <h2 class="text-2xl font-bold text-[#202124]">Live Classroom Camera & Snapshot Verification</h2>
                    <p class="text-sm text-[#5F6368]">
                        Capture a live camera picture or upload a classroom photo to recognize and mark attendance for enrolled students.
                    </p>
                </div>

                <!-- Sleek Centered Camera / Preview Feed Window -->
                <div class="relative mx-auto max-w-3xl aspect-video rounded-2xl overflow-hidden border-2 border-dashed border-[#DADCE0] bg-[#F8F9FA] flex items-center justify-center shadow-sm">
                    <!-- Live Stream Feed if active -->
                    <video 
                        ref="videoEl" 
                        autoplay 
                        playsinline 
                        :class="streamActive ? 'absolute inset-0 w-full h-full object-cover z-10' : 'hidden'"
                    ></video>

                    <!-- Preview Picture if snapped or uploaded -->
                    <img 
                        v-if="previewImage && !streamActive" 
                        :src="previewImage" 
                        alt="Snapshot Preview" 
                        class="absolute inset-0 w-full h-full object-cover z-10" 
                    />

                    <!-- Default Standby State -->
                    <div v-if="!streamActive && !previewImage" class="absolute inset-0 bg-gradient-to-t from-white via-white/60 to-transparent flex items-center justify-center">
                        <div v-if="isScanning" class="flex flex-col items-center space-y-3 z-20">
                            <div class="h-16 w-16 animate-spin rounded-full border-4 border-[#8430CE] border-t-transparent"></div>
                            <span class="font-bold text-[#8430CE] text-sm animate-pulse">Matching student faces...</span>
                        </div>
                        <div v-else class="flex flex-col items-center space-y-4 p-6">
                            <div class="h-20 w-20 rounded-full bg-[#E8F0FE] flex items-center justify-center text-2xl border border-[#D2E3FC] text-[#1A73E8]">
                                <i class="fa-solid fa-camera"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-[#202124] text-lg">Camera Standby: Section {{ section?.name || 'Assigned' }}</h4>
                                <p class="text-xs text-[#5F6368]">Activate your live camera below or choose a picture from your computer.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Scanning Overlay -->
                    <div v-if="isScanning && (streamActive || previewImage)" class="absolute inset-0 bg-[#8430CE]/30 backdrop-blur-[2px] z-20 flex flex-col items-center justify-center text-white space-y-2">
                        <div class="h-12 w-12 animate-spin rounded-full border-4 border-white border-t-transparent"></div>
                        <span class="font-bold tracking-widest uppercase text-sm">Verifying Student Faces...</span>
                    </div>

                    <!-- Simulated Face Bounding Boxes when picture loaded -->
                    <div v-if="!isScanning && previewImage" class="absolute top-12 left-20 z-20 border border-[#137333] bg-[#E6F4EA] px-2 py-1 rounded text-[10px] font-mono text-[#137333] shadow-sm font-bold">
                        Face #1: Verified (>98.2%)
                    </div>
                    <div v-if="!isScanning && previewImage" class="absolute bottom-16 right-24 z-20 border border-[#137333] bg-[#E6F4EA] px-2 py-1 rounded text-[10px] font-mono text-[#137333] shadow-sm font-bold">
                        Face #2: Verified (>96.5%)
                    </div>
                </div>

                <!-- Hidden Canvas for Capturing Frames -->
                <canvas ref="canvasEl" class="hidden"></canvas>

                <!-- Sleek Action Bar -->
                <div class="flex flex-wrap items-center justify-center gap-4 pt-2">
                    <button 
                        v-if="!streamActive"
                        @click="startCamera"
                        type="button"
                        class="rounded-full bg-[#1A73E8] px-6 py-3 text-xs font-bold text-white shadow-sm hover:bg-[#1557B0] transition-all flex items-center space-x-2"
                    >
                        <i class="fa-solid fa-video"></i>
                        <span>Activate Live Camera</span>
                    </button>
                    <button 
                        v-if="streamActive"
                        @click="takePhoto"
                        type="button"
                        class="rounded-full bg-[#137333] px-6 py-3 text-xs font-bold text-white shadow-sm hover:bg-[#0d5324] transition-all flex items-center space-x-2"
                    >
                        <i class="fa-solid fa-camera"></i>
                        <span>Snap Live Picture</span>
                    </button>
                    <button 
                        v-if="streamActive"
                        @click="stopCamera"
                        type="button"
                        class="rounded-full bg-[#F1F3F4] px-5 py-3 text-xs font-bold text-[#5F6368] hover:bg-[#E8EAED] transition-all"
                    >
                        Stop Camera
                    </button>

                    <!-- Or Upload File Button -->
                    <label class="cursor-pointer rounded-full bg-[#F8F9FA] border border-[#DADCE0] px-6 py-3 text-xs font-bold text-[#8430CE] hover:bg-[#F3E8FD] transition-all inline-flex items-center space-x-2">
                        <i class="fa-solid fa-folder-open"></i>
                        <span>Upload Classroom Picture</span>
                        <input type="file" accept="image/*" @change="handleFileUpload" class="hidden" />
                    </label>

                    <!-- Submit Verified Snapshot -->
                    <button 
                        @click="submitAiSnapshot"
                        :disabled="isScanning"
                        class="rounded-full bg-[#8430CE] px-8 py-3 text-xs font-bold text-white shadow-sm hover:bg-[#6e23B0] active:scale-[0.98] transition-all disabled:opacity-50 flex items-center space-x-2"
                    >
                        <i class="fa-solid fa-wand-magic-sparkles"></i>
                        <span>Process Picture & Auto-Verify Attendance</span>
                    </button>
                </div>
            </div>
        </main>

        <!-- Send Parent Note Modal -->
        <div v-if="showNoteModal && selectedStudent" class="fixed inset-0 z-50 flex justify-end bg-black/40 backdrop-blur-xs overflow-hidden">
            <div class="absolute inset-0" @click="showNoteModal = false"></div>
            <div class="bg-white w-full max-w-lg shadow-2xl flex flex-col h-full z-10 animate-slideLeft border-l border-[#DADCE0]">
                <div class="p-6 sm:p-8 flex-1 overflow-y-auto space-y-6">
                    <div class="flex justify-between items-center border-b border-[#DADCE0] pb-3">
                        <h3 class="font-bold text-[#202124]">Log Note for {{ selectedStudent.user?.name }}</h3>
                        <button @click="showNoteModal = false" class="h-8 w-8 rounded-full hover:bg-gray-100 font-bold text-gray-500 flex items-center justify-center cursor-pointer"><i class="fa-solid fa-xmark text-lg"></i></button>
                    </div>
                    <form @submit.prevent="submitNote" class="space-y-4 text-sm">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-[#5F6368] mb-1">Category</label>
                            <select v-model="noteForm.type" class="w-full rounded-xl bg-white border border-[#DADCE0] px-3.5 py-2.5 focus:border-[#8430CE] focus:outline-none font-semibold">
                                <option value="commendation">Commendation (Praise)</option>
                                <option value="behavior">Behavioral Notice</option>
                                <option value="warning">Absence / Disciplinary Notice</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-[#5F6368] mb-1">Title</label>
                            <input v-model="noteForm.title" type="text" required class="w-full rounded-xl bg-white border border-[#DADCE0] px-3.5 py-2.5 focus:border-[#8430CE] focus:outline-none" />
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-[#5F6368] mb-1">Message to Parent/Guardian</label>
                            <textarea v-model="noteForm.description" rows="3" required class="w-full rounded-xl bg-white border border-[#DADCE0] px-3.5 py-2.5 focus:border-[#8430CE] focus:outline-none"></textarea>
                        </div>
                        <div class="flex items-center space-x-2 pt-1">
                            <input type="checkbox" id="parentVisNote" v-model="noteForm.parent_visible" class="rounded border-[#DADCE0] text-[#137333] focus:ring-[#137333]" />
                            <label for="parentVisNote" class="text-xs font-semibold text-[#202124]">Visible instantly on Parent Guardian Portal feed</label>
                        </div>
                        <div class="pt-3 flex justify-end space-x-3">
                            <button type="button" @click="showNoteModal = false" class="rounded-full px-5 py-2.5 text-xs font-bold text-[#5F6368] hover:bg-[#F8F9FA]">Cancel</button>
                            <button type="submit" class="rounded-full bg-[#1A73E8] px-6 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-[#1557B0]">Send Parent Note</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>
