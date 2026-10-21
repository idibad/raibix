<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';

const props = defineProps({
    student: Object,
    timetable: Object,
    timetableSlots: Array,
    materials: Array,
    bookmarkedIds: { type: Array, default: () => [] },
    assignments: Array,
    examMarks: Array,
    examSchedule: Array,
    cgpa: [Number, String],
    attendances: Array,
    attendanceSummary: Object,
    leaves: Array,
    notices: Array,
    feeChallans: Array,
    libraryIssues: Array,
    transportRoute: Object,
    sports_activities: { type: Array, default: () => [] },
    penalties: { type: Array, default: () => [] },
    certificates: { type: Array, default: () => [] },
});

const activeTab = ref('overview');
const isSidebarCollapsed = ref(false);
const isMobileMenuOpen = ref(false);
const isNotificationsOpen = ref(false);

// Timetable state
const timetableMode = ref('day'); // 'day' vs 'week'
const daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
const currentDayName = new Date().toLocaleDateString('en-US', { weekday: 'long' });
const selectedDay = ref(daysOfWeek.includes(currentDayName) ? currentDayName : 'Monday');

// Assignment state
const assignmentFilter = ref('all'); // 'all', 'pending', 'submitted', 'overdue'
const selectedAssignment = ref(null);
const selectedAppealAssignment = ref(null);
const selectedQuestionAssignment = ref(null);

// Study Notes & Syllabus state
const materialTab = ref('notes'); // 'notes' vs 'syllabus'
const materialSearch = ref('');
const materialFilterSubject = ref('all');
const bookmarkedIds = ref([...props.bookmarkedIds]);

watch(() => props.bookmarkedIds, (newVals) => {
    if (newVals) bookmarkedIds.value = [...newVals];
}, { immediate: true });

const toggleBookmark = (id) => {
    const index = bookmarkedIds.value.indexOf(id);
    if (index === -1) {
        bookmarkedIds.value.push(id);
    } else {
        bookmarkedIds.value.splice(index, 1);
    }
    useForm({}).post(`/student/materials/${id}/bookmark`, {
        preserveScroll: true
    });
};

// Notice state
const noticeFilter = ref('all'); // 'all', 'Exam Schedule', 'Holiday Announcement', 'Event Notice', 'General Circular'

// Modals
const showLeaveModal = ref(false);
const showIdCardModal = ref(false);
const showPasswordModal = ref(false);

// Forms
const subForm = useForm({
    submission_text: '',
    file_url: '',
    submission_file: null,
});

const appealForm = useForm({
    appeal_reason: '',
});

const questionForm = useForm({
    message: '',
});

const leaveForm = useForm({
    start_date: '',
    end_date: '',
    reason: '',
});

const profileForm = useForm({
    phone: props.student?.user?.phone || '',
    email: props.student?.user?.email || '',
    photo: null,
});

const passwordForm = useForm({
    current_password: '',
    new_password: '',
    new_password_confirmation: '',
});

// Actions
const openSubmitModal = (assignment) => {
    selectedAssignment.value = assignment;
    subForm.submission_text = assignment.user_submission?.submission_text || '';
    subForm.file_url = assignment.user_submission?.file_url || '';
    subForm.submission_file = null;
};

const submitAssignment = () => {
    if (!selectedAssignment.value) return;
    subForm.post(`/student/assignments/${selectedAssignment.value.id}/submit`, {
        onSuccess: () => {
            selectedAssignment.value = null;
            subForm.reset();
        },
    });
};

const openAppealModal = (assignment) => {
    selectedAppealAssignment.value = assignment;
    appealForm.appeal_reason = assignment.user_submission?.appeal_reason || '';
};

const submitAppeal = () => {
    if (!selectedAppealAssignment.value) return;
    appealForm.post(`/student/assignments/${selectedAppealAssignment.value.id}/appeal`, {
        onSuccess: () => {
            selectedAppealAssignment.value = null;
            appealForm.reset();
        },
    });
};

const openQuestionModal = (assignment) => {
    selectedQuestionAssignment.value = assignment;
    questionForm.message = '';
};

const submitQuestion = () => {
    if (!selectedQuestionAssignment.value) return;
    questionForm.post(`/student/assignments/${selectedQuestionAssignment.value.id}/question`, {
        onSuccess: () => {
            questionForm.reset();
        },
    });
};

const markNoticeRead = (notice) => {
    if (notice.is_read) return;
    useForm({}).post(`/student/notices/${notice.id}/read`, {
        onSuccess: () => {
            notice.is_read = true;
        }
    });
};

const submitLeaveRequest = () => {
    leaveForm.post('/student/leaves', {
        onSuccess: () => {
            showLeaveModal.value = false;
            leaveForm.reset();
        },
    });
};

const submitProfileUpdate = () => {
    profileForm.post('/student/profile/update', {
        forceFormData: true,
        onSuccess: () => {
            // Updated
        },
    });
};

const submitPasswordChange = () => {
    passwordForm.post('/student/profile/password', {
        onSuccess: () => {
            showPasswordModal.value = false;
            passwordForm.reset();
        },
    });
};

const logout = () => {
    useForm({}).post('/logout');
};

// Computed Helpers
const filteredAssignments = computed(() => {
    if (!props.assignments) return [];
    if (assignmentFilter.value === 'all') return props.assignments;
    return props.assignments.filter(a => a.status_flag === assignmentFilter.value);
});

const filteredNotices = computed(() => {
    if (!props.notices) return [];
    if (noticeFilter.value === 'all') return props.notices;
    return props.notices.filter(n => (n.category || 'General Circular') === noticeFilter.value);
});

const filteredMaterials = computed(() => {
    if (!props.materials) return [];
    return props.materials.filter(m => {
        const matchesSearch = !materialSearch.value || 
            m.title?.toLowerCase().includes(materialSearch.value.toLowerCase()) || 
            m.description?.toLowerCase().includes(materialSearch.value.toLowerCase());
        const matchesSubject = materialFilterSubject.value === 'all' || 
            (m.subject?.id?.toString() === materialFilterSubject.value.toString());
        return matchesSearch && matchesSubject;
    });
});

const subjectsList = computed(() => {
    const map = new Map();
    props.materials?.forEach(m => {
        if (m.subject) map.set(m.subject.id, m.subject);
    });
    return Array.from(map.values());
});

const currentPeriod = computed(() => {
    if (!props.timetableSlots) return null;
    const now = new Date();
    const nowMin = now.getHours() * 60 + now.getMinutes();
    return props.timetableSlots.find(slot => {
        if (slot.day_of_week?.toLowerCase() !== currentDayName.toLowerCase()) return false;
        if (!slot.start_time || !slot.end_time) return false;
        const [sh, sm] = slot.start_time.split(':').map(Number);
        const [eh, em] = slot.end_time.split(':').map(Number);
        return nowMin >= (sh * 60 + sm) && nowMin <= (eh * 60 + em);
    });
});

const nextPeriod = computed(() => {
    if (!props.timetableSlots) return null;
    const now = new Date();
    const nowMin = now.getHours() * 60 + now.getMinutes();
    return props.timetableSlots.find(slot => {
        if (slot.day_of_week?.toLowerCase() !== currentDayName.toLowerCase()) return false;
        if (!slot.start_time) return false;
        const [sh, sm] = slot.start_time.split(':').map(Number);
        return (sh * 60 + sm) > nowMin;
    });
});

const notificationsList = computed(() => {
    const list = [];
    if ((props.attendanceSummary?.percentage ?? 100) < 75) {
        list.push({
            id: 'att-warn',
            type: 'warning',
            title: 'Critical Attendance Alert',
            message: `Your attendance is currently ${props.attendanceSummary.percentage}%. Minimum 75% required for exam eligibility.`,
            time: 'Immediate Action Required',
            actionTab: 'attendance'
        });
    }
    const overdue = props.assignments?.filter(a => a.status_flag === 'overdue') || [];
    if (overdue.length > 0) {
        list.push({
            id: 'asn-overdue',
            type: 'danger',
            title: 'Overdue Assignments',
            message: `You have ${overdue.length} missed assignment deadline(s) requiring urgent action.`,
            time: 'Deadline Missed',
            actionTab: 'assignments'
        });
    }
    const unread = props.notices?.filter(n => !n.is_read) || [];
    if (unread.length > 0) {
        list.push({
            id: 'not-unread',
            type: 'info',
            title: 'New School Circulars',
            message: `There are ${unread.length} unread announcement(s) on the Notice Board.`,
            time: 'Recent Update',
            actionTab: 'notices'
        });
    }
    if (props.examSchedule && props.examSchedule.length > 0) {
        list.push({
            id: 'exm-sched',
            type: 'primary',
            title: 'Exam Date Sheet Published',
            message: `Upcoming exam: ${props.examSchedule[0].subject?.name} on ${props.examSchedule[0].exam_date}.`,
            time: 'Exam Schedule',
            actionTab: 'exams'
        });
    }
    return list;
});
</script>

<template>
    <div class="flex min-h-screen bg-[#F8F9FA] text-[#202124] selection:bg-[#E8F0FE] selection:text-[#1A73E8] font-sans">
        <!-- Mobile Sidebar Backdrop -->
        <div 
            v-if="isMobileMenuOpen" 
            @click="isMobileMenuOpen = false"
            class="fixed inset-0 z-40 bg-black/40 backdrop-blur-xs lg:hidden transition-opacity duration-300"
        ></div>

        <!-- Sidebar Navigation -->
        <aside 
            :class="[
                isSidebarCollapsed ? 'lg:w-20' : 'lg:w-64',
                isMobileMenuOpen ? 'translate-x-0 w-64' : '-translate-x-full lg:translate-x-0',
                'fixed lg:sticky top-0 z-50 h-screen border-r border-[#DADCE0] bg-white flex flex-col justify-between flex-shrink-0 transition-all duration-300 shadow-xl lg:shadow-none'
            ]"
        >
            <div class="flex flex-col min-h-0 flex-1">
                <!-- Brand Header -->
                <div class="flex items-center justify-between px-4 sm:px-6 py-5 border-b border-[#DADCE0] min-h-[73px]">
                    <div class="flex items-center space-x-3 min-w-0 overflow-hidden">
                        <div v-if="$page.props.tenant?.logo" class="flex h-10 w-10 flex-shrink-0 items-center justify-center overflow-hidden rounded-xl bg-white p-0.5 border border-[#DADCE0] shadow-sm">
                            <img :src="$page.props.tenant.logo" :alt="$page.props.tenant.name" class="max-h-full max-w-full object-contain" />
                        </div>
                        <div v-else class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-[#E8F0FE] font-bold text-[#1A73E8] text-lg border border-[#D2E3FC] shadow-sm">
                            <i class="fa-solid fa-graduation-cap"></i>
                        </div>
                        <div v-if="!isSidebarCollapsed || isMobileMenuOpen" class="min-w-0 transition-all duration-200">
                            <h1 class="font-bold text-base tracking-tight text-[#202124] truncate">{{ $page.props.tenant?.name || 'Student Portal' }}</h1>
                            <p class="text-xs text-[#5F6368] truncate">{{ student?.current_class?.name || 'Academic Suite' }} — Sec {{ student?.current_section?.name || 'A' }}</p>
                        </div>
                    </div>
                    <button 
                        @click="isMobileMenuOpen ? (isMobileMenuOpen = false) : (isSidebarCollapsed = !isSidebarCollapsed)"
                        class="h-8 w-8 rounded-lg flex items-center justify-center text-[#5F6368] hover:bg-[#F8F9FA] hover:text-[#202124] transition-colors flex-shrink-0"
                    >
                        <span v-if="isMobileMenuOpen" class="text-lg font-bold"><i class="fa-solid fa-xmark"></i></span>
                        <span v-else class="text-base font-bold hidden lg:inline-block">{{ isSidebarCollapsed ? '→' : '←' }}</span>
                        <span v-if="!isMobileMenuOpen" class="text-lg font-bold lg:hidden"><i class="fa-solid fa-xmark"></i></span>
                    </button>
                </div>

                <!-- Side Menu Links -->
                <nav class="p-3 sm:p-4 space-y-1 overflow-y-auto flex-1">
                    <button 
                        @click="activeTab = 'overview'; isMobileMenuOpen = false"
                        :class="[
                            activeTab === 'overview' ? 'bg-[#E8F0FE] text-[#1A73E8] font-bold shadow-2xs' : 'text-[#5F6368] hover:bg-[#F8F9FA] hover:text-[#202124] font-medium',
                            (isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center px-2 py-3' : 'justify-between px-4 py-3'
                        ]"
                        class="w-full flex items-center rounded-xl text-sm transition-all"
                    >
                        <div class="flex items-center" :class="(isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center' : 'space-x-3'">
                            <span class="text-lg flex-shrink-0 text-[#1A73E8]"><i class="fa-solid fa-house"></i></span>
                            <span v-if="!isSidebarCollapsed || isMobileMenuOpen" class="truncate">Overview & What's Due</span>
                        </div>
                    </button>

                    <button 
                        @click="activeTab = 'timetable'; isMobileMenuOpen = false"
                        :class="[
                            activeTab === 'timetable' ? 'bg-[#E8F0FE] text-[#1A73E8] font-bold shadow-2xs' : 'text-[#5F6368] hover:bg-[#F8F9FA] hover:text-[#202124] font-medium',
                            (isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center px-2 py-3' : 'justify-between px-4 py-3'
                        ]"
                        class="w-full flex items-center rounded-xl text-sm transition-all relative"
                    >
                        <div class="flex items-center" :class="(isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center' : 'space-x-3'">
                            <span class="text-lg flex-shrink-0 text-[#1A73E8]"><i class="fa-solid fa-calendar-days"></i></span>
                            <span v-if="!isSidebarCollapsed || isMobileMenuOpen" class="truncate">My Timetable</span>
                        </div>
                        <span v-if="(!isSidebarCollapsed || isMobileMenuOpen) && currentPeriod" class="bg-[#E6F4EA] text-[#137333] text-2xs font-extrabold px-2 py-0.5 rounded-full border border-[#CEEAD6]">In Class</span>
                    </button>

                    <button 
                        @click="activeTab = 'notices'; isMobileMenuOpen = false"
                        :class="[
                            activeTab === 'notices' ? 'bg-[#E8F0FE] text-[#1A73E8] font-bold shadow-2xs' : 'text-[#5F6368] hover:bg-[#F8F9FA] hover:text-[#202124] font-medium',
                            (isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center px-2 py-3' : 'justify-between px-4 py-3'
                        ]"
                        class="w-full flex items-center rounded-xl text-sm transition-all relative"
                    >
                        <div class="flex items-center" :class="(isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center' : 'space-x-3'">
                            <span class="text-lg flex-shrink-0 text-[#1A73E8]"><i class="fa-solid fa-bullhorn"></i></span>
                            <span v-if="!isSidebarCollapsed || isMobileMenuOpen" class="truncate">Notice Board</span>
                        </div>
                        <span v-if="(!isSidebarCollapsed || isMobileMenuOpen) && notices?.filter(n => !n.is_read).length > 0" class="bg-[#FCE8E6] text-[#C5221F] text-xs font-bold px-2 py-0.5 rounded-full">{{ notices.filter(n => !n.is_read).length }}</span>
                    </button>

                    <button 
                        @click="activeTab = 'assignments'; isMobileMenuOpen = false"
                        :class="[
                            activeTab === 'assignments' ? 'bg-[#E8F0FE] text-[#1A73E8] font-bold shadow-2xs' : 'text-[#5F6368] hover:bg-[#F8F9FA] hover:text-[#202124] font-medium',
                            (isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center px-2 py-3' : 'justify-between px-4 py-3'
                        ]"
                        class="w-full flex items-center rounded-xl text-sm transition-all relative"
                    >
                        <div class="flex items-center" :class="(isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center' : 'space-x-3'">
                            <span class="text-lg flex-shrink-0 text-[#1A73E8]"><i class="fa-solid fa-pen-to-square"></i></span>
                            <span v-if="!isSidebarCollapsed || isMobileMenuOpen" class="truncate">Assignments</span>
                        </div>
                        <span v-if="!isSidebarCollapsed || isMobileMenuOpen" class="bg-[#F3E8FD] text-[#8430CE] text-xs font-bold px-2 py-0.5 rounded-full">{{ assignments?.filter(a => a.status_flag === 'pending').length || 0 }}</span>
                    </button>

                    <button 
                        @click="activeTab = 'materials'; isMobileMenuOpen = false"
                        :class="[
                            activeTab === 'materials' ? 'bg-[#E8F0FE] text-[#1A73E8] font-bold shadow-2xs' : 'text-[#5F6368] hover:bg-[#F8F9FA] hover:text-[#202124] font-medium',
                            (isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center px-2 py-3' : 'justify-between px-4 py-3'
                        ]"
                        class="w-full flex items-center rounded-xl text-sm transition-all relative"
                    >
                        <div class="flex items-center" :class="(isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center' : 'space-x-3'">
                            <span class="text-lg flex-shrink-0 text-[#1A73E8]"><i class="fa-solid fa-folder"></i></span>
                            <span v-if="!isSidebarCollapsed || isMobileMenuOpen" class="truncate">Study Notes & Syllabus</span>
                        </div>
                    </button>

                    <button 
                        @click="activeTab = 'attendance'; isMobileMenuOpen = false"
                        :class="[
                            activeTab === 'attendance' ? 'bg-[#E8F0FE] text-[#1A73E8] font-bold shadow-2xs' : 'text-[#5F6368] hover:bg-[#F8F9FA] hover:text-[#202124] font-medium',
                            (isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center px-2 py-3' : 'justify-between px-4 py-3'
                        ]"
                        class="w-full flex items-center rounded-xl text-sm transition-all relative"
                    >
                        <div class="flex items-center" :class="(isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center' : 'space-x-3'">
                            <span class="text-lg flex-shrink-0 text-[#1A73E8]"><i class="fa-solid fa-calendar-check"></i></span>
                            <span v-if="!isSidebarCollapsed || isMobileMenuOpen" class="truncate">Attendance & Leaves</span>
                        </div>
                        <span v-if="!isSidebarCollapsed || isMobileMenuOpen" :class="attendanceSummary?.percentage < 75 ? 'bg-[#FCE8E6] text-[#C5221F]' : 'bg-[#E6F4EA] text-[#137333]'" class="text-xs font-bold px-2 py-0.5 rounded-full">{{ attendanceSummary?.percentage || 100 }}%</span>
                    </button>

                    <button 
                        @click="activeTab = 'exams'; isMobileMenuOpen = false"
                        :class="[
                            activeTab === 'exams' ? 'bg-[#E8F0FE] text-[#1A73E8] font-bold shadow-2xs' : 'text-[#5F6368] hover:bg-[#F8F9FA] hover:text-[#202124] font-medium',
                            (isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center px-2 py-3' : 'justify-between px-4 py-3'
                        ]"
                        class="w-full flex items-center rounded-xl text-sm transition-all relative"
                    >
                        <div class="flex items-center" :class="(isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center' : 'space-x-3'">
                            <span class="text-lg flex-shrink-0 text-[#1A73E8]"><i class="fa-solid fa-chart-column"></i></span>
                            <span v-if="!isSidebarCollapsed || isMobileMenuOpen" class="truncate">Exams & Report Cards</span>
                        </div>
                    </button>

                    <button 
                        @click="activeTab = 'fees'; isMobileMenuOpen = false"
                        :class="[
                            activeTab === 'fees' ? 'bg-[#E8F0FE] text-[#1A73E8] font-bold shadow-2xs' : 'text-[#5F6368] hover:bg-[#F8F9FA] hover:text-[#202124] font-medium',
                            (isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center px-2 py-3' : 'justify-between px-4 py-3'
                        ]"
                        class="w-full flex items-center rounded-xl text-sm transition-all relative"
                    >
                        <div class="flex items-center" :class="(isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center' : 'space-x-3'">
                            <span class="text-lg flex-shrink-0 text-[#1A73E8]"><i class="fa-solid fa-credit-card"></i></span>
                            <span v-if="!isSidebarCollapsed || isMobileMenuOpen" class="truncate">My Fee Status</span>
                        </div>
                    </button>

                    <button 
                        @click="activeTab = 'profile'; isMobileMenuOpen = false"
                        :class="[
                            activeTab === 'profile' ? 'bg-[#E8F0FE] text-[#1A73E8] font-bold shadow-2xs' : 'text-[#5F6368] hover:bg-[#F8F9FA] hover:text-[#202124] font-medium',
                            (isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center px-2 py-3' : 'justify-between px-4 py-3'
                        ]"
                        class="w-full flex items-center rounded-xl text-sm transition-all relative"
                    >
                        <div class="flex items-center" :class="(isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center' : 'space-x-3'">
                            <span class="text-lg flex-shrink-0 text-[#1A73E8]"><i class="fa-solid fa-gear"></i></span>
                            <span v-if="!isSidebarCollapsed || isMobileMenuOpen" class="truncate">Profile & ID Card</span>
                        </div>
                    </button>

                    <!-- Certificates Nav Item -->
                    <button 
                        @click="activeTab = 'certificates'; isMobileMenuOpen = false"
                        :class="[
                            activeTab === 'certificates' ? 'bg-[#E8F0FE] text-[#1A73E8] font-bold shadow-2xs' : 'text-[#5F6368] hover:bg-[#F8F9FA] hover:text-[#202124] font-medium',
                            (isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center px-2 py-3' : 'justify-between px-4 py-3'
                        ]"
                        class="w-full flex items-center rounded-xl text-sm transition-all relative"
                    >
                        <div class="flex items-center" :class="(isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center' : 'space-x-3'">
                            <span class="text-lg flex-shrink-0 text-[#1A73E8]"><i class="fa-solid fa-award"></i></span>
                            <span v-if="!isSidebarCollapsed || isMobileMenuOpen" class="truncate">My Certificates</span>
                        </div>
                        <span v-if="(!isSidebarCollapsed || isMobileMenuOpen) && certificates?.length > 0" class="bg-[#E6F4EA] text-[#137333] text-xs font-bold px-2 py-0.5 rounded-full">{{ certificates.length }}</span>
                    </button>
                </nav>
            </div>

            <!-- Sidebar Footer / Sign Out -->
            <div class="p-3 sm:p-4 border-t border-[#DADCE0] bg-[#F8F9FA] space-y-3">
                <div class="flex items-center" :class="(isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center' : 'space-x-3 px-2'">
                    <div v-if="student?.photo_url" class="h-9 w-9 rounded-full overflow-hidden border border-[#DADCE0] flex-shrink-0">
                        <img :src="student.photo_url" alt="Avatar" class="h-full w-full object-cover" />
                    </div>
                    <div v-else class="h-9 w-9 rounded-full bg-[#1A73E8] text-white flex-shrink-0 flex items-center justify-center font-bold text-sm shadow-sm">
                        {{ student?.user?.name?.slice(0, 2)?.toUpperCase() || 'ST' }}
                    </div>
                    <div v-if="!isSidebarCollapsed || isMobileMenuOpen" class="flex-1 min-w-0">
                        <p class="text-xs font-bold text-[#202124] truncate">{{ student?.user?.name || 'Student Name' }}</p>
                        <p class="text-2xs text-[#5F6368] truncate">Roll #: {{ student?.roll_number || '101' }}</p>
                    </div>
                </div>
                <button 
                    @click="logout"
                    :class="(isSidebarCollapsed && !isMobileMenuOpen) ? 'py-2.5 px-2' : 'py-2 space-x-1.5'"
                    class="w-full rounded-xl bg-white border border-[#DADCE0] text-xs font-semibold text-[#C5221F] hover:bg-[#FCE8E6]/50 transition-colors shadow-2xs flex items-center justify-center"
                >
                    <span class="text-sm"><i class="fa-solid fa-right-from-bracket"></i></span>
                    <span v-if="!isSidebarCollapsed || isMobileMenuOpen">Sign Out</span>
                </button>
            </div>
        </aside>

        <!-- Main Workspace -->
        <main class="flex-1 overflow-x-hidden min-h-screen flex flex-col min-w-0">
            <!-- Top Bar Header -->
            <header class="sticky top-0 z-30 bg-white/95 backdrop-blur-md border-b border-[#DADCE0] px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between gap-4">
                <div class="flex items-center min-w-0">
                    <button 
                        @click="isMobileMenuOpen = true"
                        class="lg:hidden mr-3 p-2 rounded-xl text-[#5F6368] hover:bg-[#F8F9FA] hover:text-[#202124] transition-colors border border-[#DADCE0] shadow-2xs flex items-center justify-center"
                    >
                        <span class="text-lg"><i class="fa-solid fa-bars"></i></span>
                    </button>
                    <div class="min-w-0">
                        <h2 class="text-lg sm:text-xl font-bold text-[#202124] capitalize truncate">
                            {{ activeTab === 'overview' ? 'Academic Summary & What\'s Due' : 
                               activeTab === 'timetable' ? 'My Timetable & Weekly Schedule' :
                               activeTab === 'notices' ? 'Notice Board & Announcements' :
                               activeTab === 'assignments' ? 'Assignment Portfolio & Submission History' : 
                               activeTab === 'materials' ? 'Study Notes & Course Syllabus' : 
                               activeTab === 'attendance' ? 'Self-Service Attendance & Leave Requests' : 
                               activeTab === 'exams' ? 'Official Exam Gradebook & Report Cards' :
                               activeTab === 'fees' ? 'Read-Only Fee Visibility & Challans' :
                               activeTab === 'certificates' ? 'My Awarded Certificates' :
                               'Profile, Digital ID & Account Settings' }}
                        </h2>
                        <p class="text-xs text-[#5F6368] hidden sm:block">
                            {{ activeTab === 'overview' ? 'Unified dashboard of upcoming deadlines, live CGPA, and critical school updates.' : 
                               activeTab === 'timetable' ? 'Live period tracking, room numbers, and assigned subject teachers.' :
                               activeTab === 'notices' ? 'Circulars, holiday schedules, and targeted school announcements with unread indicators.' :
                               activeTab === 'assignments' ? 'Track pending homework, upload solution files, request re-evaluations, and ask teachers questions.' : 
                               activeTab === 'materials' ? 'Download teacher slides, worksheets, bookmark study items, and explore curriculum units.' : 
                               activeTab === 'attendance' ? 'Monitor eligibility rates, review daily check-ins, and submit leave requests online.' : 
                               activeTab === 'exams' ? 'View date sheets, detailed subject marks breakdowns, and download term PDF report cards.' :
                               activeTab === 'fees' ? 'Review challan status and upcoming due dates (payment actions managed via Parent portal).' :
                               'Manage personal contact details, account security, passwords, and preview digital ID card.' }}
                        </p>
                    </div>
                </div>

                <!-- Top Bar Quick Actions -->
                <div class="flex items-center space-x-3">
                    <button 
                        @click="showIdCardModal = true"
                        class="hidden sm:flex items-center space-x-1.5 rounded-xl bg-[#E8F0FE] border border-[#D2E3FC] px-3.5 py-2 text-xs font-bold text-[#1A73E8] hover:bg-[#D2E3FC] transition-colors shadow-2xs"
                    >
                        <span><i class="fa-solid fa-id-card mr-1"></i> Digital ID</span>
                    </button>

                    <!-- Notifications Bell Dropdown -->
                    <div class="relative">
                        <button 
                            @click="isNotificationsOpen = !isNotificationsOpen"
                            class="h-10 w-10 rounded-xl border border-[#DADCE0] bg-white flex items-center justify-center text-[#5F6368] hover:bg-[#F8F9FA] hover:text-[#202124] transition-colors relative shadow-2xs"
                        >
                            <span class="text-lg"><i class="fa-solid fa-bell"></i></span>
                            <span v-if="notificationsList.length > 0" class="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-[#C5221F] text-2xs font-bold text-white shadow-sm">
                                {{ notificationsList.length }}
                            </span>
                        </button>

                        <div v-if="isNotificationsOpen" class="absolute right-0 mt-2 w-80 sm:w-96 rounded-2xl border border-[#DADCE0] bg-white p-4 shadow-2xl z-50 space-y-3">
                            <div class="flex justify-between items-center border-b border-[#DADCE0] pb-2">
                                <h4 class="font-bold text-sm text-[#202124] flex items-center gap-1.5"><i class="fa-solid fa-bell text-[#1A73E8]"></i> <span>In-App Notifications Center</span></h4>
                                <span class="text-2xs font-semibold text-[#5F6368]">{{ notificationsList.length }} Alerts</span>
                            </div>
                            <div v-if="notificationsList.length === 0" class="py-6 text-center text-xs text-[#5F6368]">
                                You're all caught up! No urgent alerts right now.
                            </div>
                            <div v-else class="max-h-80 overflow-y-auto space-y-2">
                                <div 
                                    v-for="n in notificationsList" 
                                    :key="n.id" 
                                    @click="activeTab = n.actionTab; isNotificationsOpen = false"
                                    class="p-3 rounded-xl cursor-pointer transition-all border text-xs space-y-1"
                                    :class="{
                                        'bg-[#FEF7E0]/60 border-[#FEEFC3] text-[#B06000]': n.type === 'warning',
                                        'bg-[#FCE8E6]/60 border-[#FAD2CF] text-[#C5221F]': n.type === 'danger',
                                        'bg-[#E8F0FE]/60 border-[#D2E3FC] text-[#1A73E8]': n.type === 'info' || n.type === 'primary',
                                        'bg-[#E6F4EA]/60 border-[#CEEAD6] text-[#137333]': n.type === 'success',
                                    }"
                                >
                                    <div class="flex justify-between font-extrabold items-center">
                                        <span class="flex items-center gap-1.5">
                                            <i :class="n.type === 'warning' ? 'fa-solid fa-triangle-exclamation' : n.type === 'danger' ? 'fa-solid fa-circle-exclamation' : n.type === 'info' ? 'fa-solid fa-bullhorn' : 'fa-solid fa-calendar-days'"></i>
                                            <span>{{ n.title }}</span>
                                        </span>
                                        <span class="text-2xs font-mono opacity-80">{{ n.time }}</span>
                                    </div>
                                    <p class="font-medium opacity-90">{{ n.message }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main Content Container -->
            <div class="p-4 sm:p-6 lg:p-8 space-y-6 sm:space-y-8 flex-1">
                
                <!-- ================= TAB: OVERVIEW & WHAT'S DUE ================= -->
                <div v-if="activeTab === 'overview'" class="space-y-8 animate-fadeIn">
                    <!-- Critical Low Attendance Banner (P0 #3) -->
                    <div v-if="attendanceSummary?.percentage < 75" class="rounded-2xl bg-[#FCE8E6] border-2 border-[#C5221F] p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-[#C5221F] shadow-sm animate-pulse">
                        <div class="flex items-center space-x-3.5">
                            <span class="text-2xl text-[#C5221F]"><i class="fa-solid fa-triangle-exclamation"></i></span>
                            <div>
                                <h4 class="font-extrabold text-sm sm:text-base">Eligibility Warning: Low Attendance Rate ({{ attendanceSummary?.percentage }}%)</h4>
                                <p class="text-xs sm:text-sm font-medium">Your attendance has dropped below the school's 75% threshold required for exam entry. Please contact your class teacher or submit a medical leave application immediately.</p>
                            </div>
                        </div>
                        <button @click="activeTab = 'attendance'" class="rounded-xl bg-[#C5221F] text-white px-4 py-2 text-xs font-bold whitespace-nowrap hover:bg-[#A50E0E] transition-colors shadow-2xs">
                            Apply for Leave →
                        </button>
                    </div>

                    <!-- Consolidated "What's Due" Deadlines Widget (P1 #11) -->
                    <div class="google-card rounded-2xl p-6 border-2 border-[#1A73E8]/30 bg-gradient-to-br from-white to-[#E8F0FE]/30 shadow-sm space-y-4">
                        <div class="flex items-center justify-between border-b border-[#DADCE0] pb-3">
                            <div class="flex items-center space-x-2.5">
                                <span class="text-xl text-[#1A73E8]"><i class="fa-solid fa-bullseye"></i></span>
                                <h3 class="text-base font-extrabold text-[#202124]">What's Due — Upcoming Deadlines & Priority Action List</h3>
                            </div>
                            <span class="text-xs font-bold text-[#1A73E8] bg-[#E8F0FE] px-3 py-1 rounded-full border border-[#D2E3FC]">Sorted by Urgency</span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- Due Assignments Card -->
                            <div class="rounded-xl bg-white p-4 border border-[#DADCE0] shadow-2xs space-y-3">
                                <div class="flex justify-between items-center text-xs font-extrabold text-[#8430CE]">
                                    <span class="inline-flex items-center gap-1.5"><i class="fa-solid fa-pen-to-square"></i> <span>Assignments Due Soon</span></span>
                                    <span class="bg-[#F3E8FD] px-2 py-0.5 rounded-full">{{ assignments?.filter(a => a.status_flag === 'pending').length || 0 }}</span>
                                </div>
                                <div v-if="!assignments?.filter(a => a.status_flag === 'pending').length" class="text-xs text-[#5F6368] italic py-2">
                                    All homework completed on time!
                                </div>
                                <div v-for="a in assignments?.filter(a => a.status_flag === 'pending').slice(0, 2)" :key="a.id" class="text-xs bg-[#F8F9FA] p-2.5 rounded-lg border border-[#DADCE0] flex justify-between items-center">
                                    <div class="truncate pr-2">
                                        <strong class="text-[#202124] block truncate">{{ a.title }}</strong>
                                        <span class="text-2xs text-[#C5221F] font-bold">Due: {{ a.due_date }}</span>
                                    </div>
                                    <button @click="openSubmitModal(a)" class="rounded-lg bg-[#1A73E8] text-white px-2.5 py-1 text-2xs font-bold flex-shrink-0 hover:bg-[#1557B0]">
                                        Submit
                                    </button>
                                </div>
                            </div>

                            <!-- Upcoming Exams Card -->
                            <div class="rounded-xl bg-white p-4 border border-[#DADCE0] shadow-2xs space-y-3">
                                <div class="flex justify-between items-center text-xs font-extrabold text-[#1A73E8]">
                                    <span class="inline-flex items-center gap-1.5"><i class="fa-solid fa-calendar-days"></i> <span>Upcoming Exam Schedule</span></span>
                                    <span class="bg-[#E8F0FE] px-2 py-0.5 rounded-full">{{ examSchedule?.length || 0 }}</span>
                                </div>
                                <div v-if="!examSchedule?.length" class="text-xs text-[#5F6368] italic py-2">
                                    No published date sheet right now.
                                </div>
                                <div v-for="ex in examSchedule?.slice(0, 2)" :key="ex.id" class="text-xs bg-[#F8F9FA] p-2.5 rounded-lg border border-[#DADCE0] flex justify-between items-center">
                                    <div>
                                        <strong class="text-[#202124] block">{{ ex.subject?.name }}</strong>
                                        <span class="text-2xs text-[#5F6368]">{{ ex.exam_date }} ({{ ex.start_time }})</span>
                                    </div>
                                    <span class="text-2xs font-bold text-[#137333] bg-[#E6F4EA] px-2 py-0.5 rounded border border-[#CEEAD6]">Scheduled</span>
                                </div>
                            </div>

                            <!-- Live Timetable & Next Period Card -->
                            <div class="rounded-xl bg-white p-4 border border-[#DADCE0] shadow-2xs space-y-3">
                                <div class="flex justify-between items-center text-xs font-extrabold text-[#137333]">
                                    <span class="inline-flex items-center gap-1.5"><i class="fa-solid fa-bolt"></i> <span>Today's Live Class Status</span></span>
                                    <span class="bg-[#E6F4EA] px-2 py-0.5 rounded-full">{{ currentDayName }}</span>
                                </div>
                                <div v-if="currentPeriod" class="p-2.5 rounded-lg bg-[#E6F4EA]/80 border border-[#CEEAD6] text-xs text-[#137333]">
                                    <span class="text-2xs font-extrabold uppercase tracking-wider block">Currently In Session:</span>
                                    <strong class="text-sm font-black block text-[#202124] mt-0.5">{{ currentPeriod.subject?.name }}</strong>
                                    <span class="text-2xs font-medium">{{ currentPeriod.start_time }} - {{ currentPeriod.end_time }} | Room: {{ currentPeriod.room_number || '101' }} | Prof: {{ currentPeriod.teacher?.name || 'Assigned Teacher' }}</span>
                                </div>
                                <div v-else-if="nextPeriod" class="p-2.5 rounded-lg bg-[#E8F0FE] border border-[#D2E3FC] text-xs text-[#1A73E8]">
                                    <span class="text-2xs font-extrabold uppercase tracking-wider block">Up Next:</span>
                                    <strong class="text-sm font-black block text-[#202124] mt-0.5">{{ nextPeriod.subject?.name }}</strong>
                                    <span class="text-2xs font-medium">Starts at {{ nextPeriod.start_time }} | Room: {{ nextPeriod.room_number || '101' }}</span>
                                </div>
                                <div v-else class="text-xs text-[#5F6368] italic py-2">
                                    No more periods scheduled for today.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- KPI Cards Row -->
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="google-card rounded-2xl p-6 relative overflow-hidden flex items-center justify-between border border-[#DADCE0] bg-white shadow-2xs">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-[#5F6368]">Academic CGPA</p>
                                <h3 class="text-4xl font-extrabold text-[#202124] mt-2">{{ cgpa || 3.85 }} <span class="text-sm font-normal text-[#5F6368]">/ 4.00</span></h3>
                            </div>
                            <div class="h-14 w-14 rounded-2xl bg-[#E8F0FE] flex items-center justify-center text-2xl font-bold text-[#1A73E8] border border-[#D2E3FC]">
                                <i class="fa-solid fa-trophy"></i>
                            </div>
                        </div>

                        <div @click="activeTab = 'attendance'" class="google-card rounded-2xl p-6 relative overflow-hidden flex items-center justify-between border border-[#DADCE0] bg-white shadow-2xs cursor-pointer hover:border-[#137333] transition-all">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-[#5F6368]">Attendance Rate</p>
                                <h3 class="text-3xl font-extrabold text-[#137333] mt-2">{{ attendanceSummary?.percentage ?? 100 }}%</h3>
                            </div>
                            <div class="h-14 w-14 rounded-2xl bg-[#E6F4EA] flex items-center justify-center text-2xl border border-[#CEEAD6] text-[#137333]">
                                <i class="fa-solid fa-bolt"></i>
                            </div>
                        </div>

                        <div @click="activeTab = 'assignments'" class="google-card rounded-2xl p-6 relative overflow-hidden flex items-center justify-between border border-[#DADCE0] bg-white shadow-2xs cursor-pointer hover:border-[#8430CE] transition-all">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-[#5F6368]">Active Assignments</p>
                                <h3 class="text-3xl font-extrabold text-[#202124] mt-2">{{ assignments?.length || 0 }}</h3>
                            </div>
                            <div class="h-14 w-14 rounded-2xl bg-[#F3E8FD] flex items-center justify-center text-2xl border border-[#E9D2FD] text-[#8430CE]">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </div>
                        </div>

                        <div @click="activeTab = 'fees'" class="google-card rounded-2xl p-6 relative overflow-hidden flex items-center justify-between border border-[#DADCE0] bg-white shadow-2xs cursor-pointer hover:border-[#B06000] transition-all">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-[#5F6368]">Challan Status</p>
                                <h3 class="text-3xl font-extrabold text-[#202124] mt-2">{{ feeChallans?.filter(f => f.status !== 'paid').length || 0 }} <span class="text-xs font-semibold text-[#B06000] bg-[#FEF7E0] px-2.5 py-0.5 rounded-full border border-[#FEEFC3]">Unpaid</span></h3>
                            </div>
                            <div class="h-14 w-14 rounded-2xl bg-[#FEF7E0] flex items-center justify-center text-2xl border border-[#FEEFC3] text-[#B06000]">
                                <i class="fa-solid fa-credit-card"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Notice Board Feed (With Read/Unread Indicators & Category Pills) -->
                    <div class="google-card rounded-2xl p-6 border border-[#DADCE0] bg-white shadow-2xs space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-[#DADCE0] pb-3">
                            <div>
                                <h3 class="text-base font-bold text-[#202124] flex items-center gap-2"><i class="fa-solid fa-thumbtack text-[#1A73E8]"></i> <span>Notice Board & Targeted Announcements</span></h3>
                                <p class="text-xs text-[#5F6368]">Includes exam date sheets, holiday circulars, and class-wide bulletins.</p>
                            </div>
                            <button @click="activeTab = 'notices'" class="text-xs font-bold text-[#1A73E8] hover:underline">View All Notices →</button>
                        </div>
                        <div v-if="!notices || notices.length === 0" class="py-6 text-center text-xs text-[#5F6368] bg-[#F8F9FA] rounded-xl border border-dashed border-[#DADCE0]">
                            No class announcements or school notices posted at this time.
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div v-for="n in notices?.slice(0, 4)" :key="n.id" class="rounded-xl p-4 border transition-all space-y-2" :class="n.is_read ? 'bg-[#F8F9FA] border-[#DADCE0]' : 'bg-white border-[#1A73E8] shadow-sm'">
                                <div class="flex justify-between items-start">
                                    <div class="flex items-center space-x-2">
                                        <span v-if="!n.is_read" class="h-2.5 w-2.5 rounded-full bg-[#1A73E8] flex-shrink-0" title="Unread Notice"></span>
                                        <span class="text-2xs font-extrabold uppercase px-2 py-0.5 rounded border" :class="n.category === 'Exam Schedule' ? 'bg-[#FCE8E6] text-[#C5221F] border-[#FAD2CF]' : n.category === 'Holiday Announcement' ? 'bg-[#E6F4EA] text-[#137333] border-[#CEEAD6]' : 'bg-[#E8F0FE] text-[#1A73E8] border-[#D2E3FC]'">
                                            {{ n.category || 'General Circular' }}
                                        </span>
                                    </div>
                                    <span class="text-2xs font-mono bg-white px-2 py-0.5 rounded border border-[#DADCE0] text-[#5F6368]">{{ n.publish_date }}</span>
                                </div>
                                <h4 class="font-bold text-[#202124] text-sm">{{ n.title }}</h4>
                                <p class="text-xs text-[#5F6368] line-clamp-2">{{ n.content }}</p>
                                <div class="pt-2 flex justify-end">
                                    <button v-if="!n.is_read" @click="markNoticeRead(n)" class="text-2xs font-bold text-[#1A73E8] hover:underline inline-flex items-center gap-1"><span>Mark as Read</span> <i class="fa-solid fa-check"></i></button>
                                    <span v-else class="text-2xs font-semibold text-[#137333] inline-flex items-center gap-1"><span>Read</span> <i class="fa-solid fa-check"></i></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ================= TAB: MY TIMETABLE (P0 #1) ================= -->
                <div v-if="activeTab === 'timetable'" class="space-y-6 animate-fadeIn">
                    <div class="google-card rounded-2xl p-6 border border-[#DADCE0] bg-white shadow-2xs space-y-6">
                        <!-- Mode Selector & Day Tabs -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-[#DADCE0] pb-4">
                            <div>
                                <h3 class="font-bold text-base text-[#202124] flex items-center gap-2"><i class="fa-solid fa-calendar-days text-[#1A73E8]"></i> <span>Assigned Class Schedule & Period Roster</span></h3>
                                <p class="text-xs text-[#5F6368]">Synced with Admin Timetable Builder | Section {{ student?.current_section?.name || 'A' }}</p>
                            </div>
                            <div class="flex items-center space-x-2">
                                <button 
                                    @click="timetableMode = 'day'" 
                                    :class="timetableMode === 'day' ? 'bg-[#1A73E8] text-white font-bold' : 'bg-[#F8F9FA] text-[#5F6368] hover:text-[#202124] font-medium'"
                                    class="px-4 py-2 rounded-xl text-xs transition-all border border-[#DADCE0]"
                                >
                                    Daily View
                                </button>
                                <button 
                                    @click="timetableMode = 'week'" 
                                    :class="timetableMode === 'week' ? 'bg-[#1A73E8] text-white font-bold' : 'bg-[#F8F9FA] text-[#5F6368] hover:text-[#202124] font-medium'"
                                    class="px-4 py-2 rounded-xl text-xs transition-all border border-[#DADCE0]"
                                >
                                    Full Weekly Roster
                                </button>
                            </div>
                        </div>

                        <!-- Day Selector when in Daily mode -->
                        <div v-if="timetableMode === 'day'" class="flex space-x-2 overflow-x-auto pb-2">
                            <button 
                                v-for="day in daysOfWeek" 
                                :key="day"
                                @click="selectedDay = day"
                                :class="selectedDay === day ? 'bg-[#E8F0FE] border-[#1A73E8] text-[#1A73E8] font-bold shadow-2xs' : 'bg-[#F8F9FA] border-[#DADCE0] text-[#5F6368] hover:bg-white'"
                                class="px-5 py-2.5 rounded-xl text-xs border transition-all flex items-center space-x-2 flex-shrink-0"
                            >
                                <span>{{ day }}</span>
                                <span v-if="day === currentDayName" class="h-2 w-2 rounded-full bg-[#137333]"></span>
                            </button>
                        </div>

                        <!-- Daily View List -->
                        <div v-if="timetableMode === 'day'" class="space-y-3">
                            <div v-if="!timetable?.[selectedDay] || timetable[selectedDay].length === 0" class="py-12 text-center text-sm text-[#5F6368] bg-[#F8F9FA] rounded-2xl border border-dashed border-[#DADCE0]">
                                No periods scheduled for {{ selectedDay }}.
                            </div>
                            <div 
                                v-for="slot in timetable?.[selectedDay] || []" 
                                :key="slot.id" 
                                class="rounded-2xl p-5 border transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-4"
                                :class="{
                                    'bg-[#E6F4EA] border-2 border-[#137333] shadow-md': slot.day_of_week === currentDayName && currentPeriod?.id === slot.id,
                                    'bg-[#E8F0FE] border-2 border-[#1A73E8]': slot.day_of_week === currentDayName && nextPeriod?.id === slot.id && currentPeriod?.id !== slot.id,
                                    'bg-[#F8F9FA] border-[#DADCE0]': !(slot.day_of_week === currentDayName && (currentPeriod?.id === slot.id || nextPeriod?.id === slot.id))
                                }"
                            >
                                <div class="flex items-center space-x-4">
                                    <div class="px-3.5 py-2 rounded-xl font-mono text-xs font-black text-center min-w-[110px] border shadow-2xs"
                                        :class="slot.day_of_week === currentDayName && currentPeriod?.id === slot.id ? 'bg-[#137333] text-white border-[#137333]' : 'bg-white text-[#202124] border-[#DADCE0]'"
                                    >
                                        {{ slot.start_time }} - {{ slot.end_time }}
                                    </div>
                                    <div>
                                        <div class="flex items-center space-x-2">
                                            <h4 class="font-extrabold text-base text-[#202124]">{{ slot.subject?.name || 'Subject' }}</h4>
                                            <span v-if="slot.day_of_week === currentDayName && currentPeriod?.id === slot.id" class="bg-[#137333] text-white text-2xs font-extrabold px-2.5 py-0.5 rounded-full uppercase tracking-wider animate-pulse inline-flex items-center gap-1">
                                                <i class="fa-solid fa-circle text-[8px]"></i> <span>Current Period</span>
                                            </span>
                                            <span v-else-if="slot.day_of_week === currentDayName && nextPeriod?.id === slot.id" class="bg-[#1A73E8] text-white text-2xs font-extrabold px-2.5 py-0.5 rounded-full uppercase tracking-wider inline-flex items-center gap-1">
                                                <i class="fa-solid fa-circle text-[8px]"></i> <span>Next Up</span>
                                            </span>
                                        </div>
                                        <p class="text-xs text-[#5F6368] font-medium mt-1 flex items-center gap-2 flex-wrap"><span><i class="fa-solid fa-chalkboard-user text-[#1A73E8]"></i> Teacher: {{ slot.teacher?.name || 'Assigned Faculty' }}</span> <span>|</span> <span><i class="fa-solid fa-location-dot text-[#C5221F]"></i> Room: {{ slot.room_number || 'Room 101' }}</span></p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="text-xs font-semibold bg-white border border-[#DADCE0] px-3 py-1.5 rounded-xl text-[#5F6368]">
                                        Section {{ student?.current_section?.name || 'A' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Weekly Matrix Roster View -->
                        <div v-if="timetableMode === 'week'" class="overflow-x-auto">
                            <table class="w-full text-left text-xs border-collapse border border-[#DADCE0]">
                                <thead>
                                    <tr class="bg-[#F8F9FA] border-b border-[#DADCE0] font-extrabold uppercase text-[#5F6368]">
                                        <th class="py-3 px-4 border-r border-[#DADCE0]">Day</th>
                                        <th class="py-3 px-4">Periods & Subjects Schedule</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#DADCE0]">
                                    <tr v-for="day in daysOfWeek" :key="day" class="hover:bg-[#F8F9FA]/50">
                                        <td class="py-4 px-4 font-extrabold text-[#202124] border-r border-[#DADCE0] w-32 align-top bg-[#F8F9FA]">
                                            {{ day }}
                                            <span v-if="day === currentDayName" class="block text-2xs text-[#137333] font-bold">Today</span>
                                        </td>
                                        <td class="py-4 px-4">
                                            <div v-if="!timetable?.[day] || timetable[day].length === 0" class="text-[#5F6368] italic">No class periods.</div>
                                            <div v-else class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                                                <div v-for="slot in timetable[day]" :key="slot.id" class="p-3 rounded-xl border border-[#DADCE0] bg-white shadow-2xs space-y-1">
                                                    <div class="flex justify-between font-mono font-bold text-2xs text-[#1A73E8]">
                                                        <span>{{ slot.start_time }} - {{ slot.end_time }}</span>
                                                        <span>Rm: {{ slot.room_number || '101' }}</span>
                                                    </div>
                                                    <strong class="text-sm text-[#202124] block">{{ slot.subject?.name }}</strong>
                                                    <span class="text-2xs text-[#5F6368] block">Prof. {{ slot.teacher?.name }}</span>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- ================= TAB: NOTICE BOARD (P0 #2) ================= -->
                <div v-if="activeTab === 'notices'" class="space-y-6 animate-fadeIn">
                    <div class="google-card rounded-2xl p-6 border border-[#DADCE0] bg-white shadow-2xs space-y-6">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-[#DADCE0] pb-4">
                            <div>
                                <h3 class="font-bold text-base text-[#202124] flex items-center gap-2"><i class="fa-solid fa-bullhorn text-[#1A73E8]"></i> <span>School Circulars & Notice Feed</span></h3>
                                <p class="text-xs text-[#5F6368]">Stay updated with official school schedules, exam bulletins, and holiday alerts.</p>
                            </div>
                            <!-- Category Filter Tabs -->
                            <div class="flex flex-wrap gap-2">
                                <button 
                                    v-for="cat in ['all', 'Exam Schedule', 'Holiday Announcement', 'Event Notice', 'General Circular']" 
                                    :key="cat"
                                    @click="noticeFilter = cat"
                                    :class="noticeFilter === cat ? 'bg-[#1A73E8] text-white font-bold shadow-2xs' : 'bg-[#F8F9FA] text-[#5F6368] hover:text-[#202124] border border-[#DADCE0] font-medium'"
                                    class="px-3 py-1.5 rounded-xl text-xs transition-all capitalize"
                                >
                                    {{ cat === 'all' ? 'All Categories' : cat }}
                                </button>
                            </div>
                        </div>

                        <div v-if="!filteredNotices || filteredNotices.length === 0" class="py-16 text-center text-sm text-[#5F6368] bg-[#F8F9FA] rounded-2xl border border-dashed border-[#DADCE0]">
                            No notices found matching the selected category.
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div 
                                v-for="n in filteredNotices" 
                                :key="n.id" 
                                class="rounded-2xl p-6 border transition-all flex flex-col justify-between space-y-4 shadow-sm"
                                :class="n.is_read ? 'bg-[#F8F9FA] border-[#DADCE0]' : 'bg-white border-2 border-[#1A73E8] ring-4 ring-[#E8F0FE]'"
                            >
                                <div class="space-y-3">
                                    <div class="flex justify-between items-start">
                                        <div class="flex items-center space-x-2">
                                            <span v-if="!n.is_read" class="h-3 w-3 rounded-full bg-[#1A73E8] animate-ping" title="Unread Notice"></span>
                                            <span class="text-xs font-extrabold uppercase px-3 py-1 rounded-full border" :class="n.category === 'Exam Schedule' ? 'bg-[#FCE8E6] text-[#C5221F] border-[#FAD2CF]' : n.category === 'Holiday Announcement' ? 'bg-[#E6F4EA] text-[#137333] border-[#CEEAD6]' : 'bg-[#E8F0FE] text-[#1A73E8] border-[#D2E3FC]'">
                                                {{ n.category || 'General Circular' }}
                                            </span>
                                        </div>
                                        <span class="text-xs font-mono bg-white px-2.5 py-1 rounded border border-[#DADCE0] text-[#5F6368] font-bold">{{ n.publish_date }}</span>
                                    </div>
                                    <h4 class="font-extrabold text-[#202124] text-lg">{{ n.title }}</h4>
                                    <p class="text-sm text-[#5F6368] leading-relaxed whitespace-pre-line">{{ n.content }}</p>
                                </div>
                                <div class="pt-3 border-t border-[#DADCE0] flex items-center justify-between">
                                    <span class="text-2xs font-semibold text-[#5F6368]">Issued By: {{ n.creator?.name || 'School Administration' }}</span>
                                    <button 
                                        v-if="!n.is_read" 
                                        @click="markNoticeRead(n)" 
                                        class="rounded-xl bg-[#1A73E8] text-white px-4 py-1.5 text-xs font-bold shadow-sm hover:bg-[#1557B0] transition-colors"
                                    >
                                        <i class="fa-solid fa-check mr-1.5"></i> Acknowledge & Mark Read
                                    </button>
                                    <span v-else class="text-xs font-bold text-[#137333] bg-[#E6F4EA] px-3 py-1 rounded-full border border-[#CEEAD6] inline-flex items-center gap-1.5">
                                        <i class="fa-solid fa-check"></i> <span>Read & Acknowledged</span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ================= TAB: ASSIGNMENTS PORTFOLIO & HISTORY (P1 #6, #7, #8, #9) ================= -->
                <div v-if="activeTab === 'assignments'" class="space-y-6 animate-fadeIn">
                    <div class="google-card rounded-2xl p-6 border border-[#DADCE0] bg-white shadow-2xs space-y-6">
                        <!-- Header & Filter Tabs -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-[#DADCE0] pb-4">
                            <div>
                                <h3 class="font-bold text-base text-[#202124] flex items-center gap-2"><i class="fa-solid fa-pen-to-square text-[#1A73E8]"></i> <span>Assignment Portfolio, Homework Upload & Q&A</span></h3>
                                <p class="text-xs text-[#5F6368]">Track homework tasks, upload real document solutions, request grade appeals, and message teachers.</p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <button 
                                    v-for="filter in ['all', 'pending', 'submitted', 'overdue']" 
                                    :key="filter"
                                    @click="assignmentFilter = filter"
                                    :class="assignmentFilter === filter ? 'bg-[#1A73E8] text-white font-bold shadow-2xs' : 'bg-[#F8F9FA] text-[#5F6368] hover:text-[#202124] border border-[#DADCE0] font-medium'"
                                    class="px-3.5 py-1.5 rounded-xl text-xs transition-all capitalize flex items-center space-x-1.5"
                                >
                                    <span class="inline-flex items-center gap-1.5"><i :class="filter === 'pending' ? 'fa-solid fa-clock' : filter === 'submitted' ? 'fa-solid fa-check' : filter === 'overdue' ? 'fa-solid fa-triangle-exclamation' : ''" v-if="filter !== 'all'"></i> <span>{{ filter === 'all' ? 'All Assignments' : filter === 'pending' ? 'Active & Pending' : filter === 'submitted' ? 'Submitted History' : 'Overdue Missed' }}</span></span>
                                    <span class="bg-white/20 px-1.5 py-0.2 rounded-full text-2xs">{{ filter === 'all' ? assignments?.length : assignments?.filter(a => a.status_flag === filter).length }}</span>
                                </button>
                            </div>
                        </div>

                        <div v-if="!filteredAssignments || filteredAssignments.length === 0" class="py-16 text-center text-sm text-[#5F6368] bg-[#F8F9FA] rounded-2xl border border-dashed border-[#DADCE0]">
                            No assignments found matching this filter status.
                        </div>

                        <div class="space-y-6">
                            <div v-for="a in filteredAssignments" :key="a.id" class="rounded-2xl bg-[#F8F9FA] p-6 border border-[#DADCE0] space-y-4 transition-all hover:border-[#1A73E8]/50">
                                <div class="flex justify-between items-start flex-wrap gap-3">
                                    <div>
                                        <div class="flex items-center space-x-2.5">
                                            <span class="text-xs font-bold uppercase tracking-wider px-2.5 py-1 rounded-md border inline-flex items-center gap-1.5"
                                                :class="a.status_flag === 'submitted' ? 'bg-[#E6F4EA] text-[#137333] border-[#CEEAD6]' : a.status_flag === 'overdue' ? 'bg-[#FCE8E6] text-[#C5221F] border-[#FAD2CF]' : 'bg-[#FEF7E0] text-[#B06000] border-[#FEEFC3]'"
                                            >
                                                <i :class="a.status_flag === 'submitted' ? 'fa-solid fa-check' : a.status_flag === 'overdue' ? 'fa-solid fa-triangle-exclamation' : 'fa-solid fa-clock'"></i>
                                                <span>{{ a.status_flag === 'submitted' ? 'Submitted' : a.status_flag === 'overdue' ? 'Overdue Deadline' : 'Pending Homework' }}</span>
                                            </span>
                                            <span class="text-xs font-semibold text-[#5F6368]">{{ a.subject?.name }} | Due: {{ a.due_date }}</span>
                                        </div>
                                        <h4 class="font-extrabold text-[#202124] text-xl mt-2">{{ a.title }}</h4>
                                        <p class="text-sm text-[#5F6368] mt-1">{{ a.description }}</p>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <a 
                                            v-if="a.attachment_file || a.attachment_url" 
                                            :href="a.attachment_file ? '/storage/' + a.attachment_file : a.attachment_url" 
                                            target="_blank" 
                                            class="rounded-xl bg-[#E8F0FE] border border-[#D2E3FC] px-4 py-2 text-xs font-bold text-[#1A73E8] hover:bg-[#D2E3FC] transition-colors inline-flex items-center space-x-1.5 shadow-2xs"
                                        >
                                            <i class="fa-solid fa-download"></i> <span>Download Teacher Worksheet</span>
                                        </a>
                                        <span class="text-sm font-black bg-white border border-[#DADCE0] px-3.5 py-2 rounded-xl text-[#202124] shadow-2xs">{{ a.total_marks }} Marks</span>
                                    </div>
                                </div>

                                <!-- Submission Status / Evaluation Display -->
                                <div v-if="a.user_submission" class="rounded-xl bg-[#E6F4EA] border border-[#CEEAD6] p-5 text-sm space-y-3 text-[#137333]">
                                    <div class="flex justify-between font-bold flex-wrap gap-2 items-center border-b border-[#CEEAD6] pb-2">
                                        <div class="flex items-center space-x-2">
                                            <span class="inline-flex items-center gap-1.5"><i class="fa-solid fa-check"></i> <span>Submitted Solution</span></span>
                                            <!-- Teacher Override vs AI Graded Badge (P1 #7) -->
                                            <span v-if="a.user_submission.reviewed_by_teacher_id" class="bg-[#8430CE] text-white text-2xs font-extrabold px-2.5 py-0.5 rounded-full uppercase inline-flex items-center gap-1.5">
                                                <i class="fa-solid fa-chalkboard-user"></i> <span>Reviewed by Teacher Override</span>
                                            </span>
                                            <span v-else-if="a.user_submission.ai_graded" class="bg-[#1A73E8] text-white text-2xs font-extrabold px-2.5 py-0.5 rounded-full uppercase inline-flex items-center gap-1.5">
                                                <i class="fa-solid fa-robot"></i> <span>Evaluated by AI</span>
                                            </span>
                                        </div>
                                        <div class="flex items-center space-x-4">
                                            <a v-if="a.user_submission.file_url" :href="a.user_submission.file_url" target="_blank" class="underline text-xs font-bold text-[#1A73E8] bg-white px-3 py-1 rounded-lg border border-[#D2E3FC]">
                                                <i class="fa-solid fa-file-lines mr-1.5"></i> Re-Download Your File: {{ a.user_submission.submission_file_name || 'Solution Document' }} &↗
                                            </a>
                                            <span class="text-base font-black bg-white px-3 py-1 rounded-xl border border-[#CEEAD6] text-[#137333]">Score: {{ a.user_submission.obtained_marks || a.user_submission.ai_suggested_marks || '94' }} / {{ a.total_marks }}</span>
                                        </div>
                                    </div>
                                    <p class="text-[#202124] italic">"{{ a.user_submission.teacher_remarks || a.user_submission.ai_feedback || 'Well-structured analysis and accurate step-by-step reasoning.' }}"</p>
                                    
                                    <!-- Grade Appeal Section (P1 #7) -->
                                    <div class="pt-2 border-t border-[#CEEAD6]/50 flex justify-between items-center text-xs">
                                        <span v-if="a.user_submission.appeal_requested" class="text-[#B06000] font-bold bg-[#FEF7E0] px-3 py-1 rounded-lg border border-[#FEEFC3] inline-flex items-center gap-1.5">
                                            <i class="fa-solid fa-scale-balanced"></i> <span>Grade Re-evaluation Requested (Status: {{ a.user_submission.appeal_status || 'Under Teacher Review' }})</span>
                                        </span>
                                        <span v-else class="text-[#5F6368]">Disagree with evaluation or feedback?</span>
                                        <button v-if="!a.user_submission.appeal_requested" @click="openAppealModal(a)" class="font-bold text-[#8430CE] hover:underline bg-[#F3E8FD] px-3 py-1 rounded-lg border border-[#E9D2FD] inline-flex items-center gap-1.5">
                                            <i class="fa-solid fa-scale-balanced"></i> <span>Request Teacher Re-evaluation &rarr;</span>
                                        </button>
                                    </div>
                                </div>
                                <div v-else class="flex justify-between items-center pt-3 border-t border-[#DADCE0]">
                                    <span v-if="a.status_flag === 'overdue'" class="text-xs font-bold text-[#C5221F] inline-flex items-center gap-1.5"><i class="fa-solid fa-triangle-exclamation"></i> <span>Deadline passed. You can still submit late work for partial grading.</span></span>
                                    <span v-else class="text-xs font-medium text-[#5F6368]">Upload your completed homework PDF or solution text below.</span>
                                    <button @click="openSubmitModal(a)" class="rounded-full bg-[#1A73E8] px-6 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-[#1557B0] transition-all flex items-center space-x-1.5">
                                        <i class="fa-solid fa-upload"></i> <span>Submit Homework Solution &rarr;</span>
                                    </button>
                                </div>

                                <!-- Ask-a-Question Q&A Thread Bar (P1 #9) -->
                                <div class="pt-3 border-t border-[#DADCE0] bg-white rounded-xl p-4 border border-[#DADCE0] space-y-3">
                                    <div class="flex justify-between items-center">
                                        <h5 class="text-xs font-extrabold text-[#202124] flex items-center space-x-1.5">
                                            <span class="inline-flex items-center gap-1.5"><i class="fa-solid fa-comments text-[#1A73E8]"></i> <span>Teacher Q&A Thread</span></span>
                                            <span class="bg-[#F8F9FA] px-2 py-0.5 rounded text-2xs">{{ a.questions?.length || 0 }} messages</span>
                                        </h5>
                                        <button @click="openQuestionModal(a)" class="text-xs font-bold text-[#1A73E8] hover:underline">
                                            + Ask Teacher a Question
                                        </button>
                                    </div>
                                    <div v-if="a.questions && a.questions.length > 0" class="space-y-2 max-h-48 overflow-y-auto pt-2">
                                        <div v-for="q in a.questions" :key="q.id" class="p-2.5 rounded-xl text-xs flex flex-col space-y-1" :class="q.sender_type === 'student' ? 'bg-[#E8F0FE]/60 border border-[#D2E3FC] ml-8' : 'bg-[#F8F9FA] border border-[#DADCE0] mr-8'">
                                            <div class="flex justify-between font-bold text-2xs" :class="q.sender_type === 'student' ? 'text-[#1A73E8]' : 'text-[#202124]'">
                                                <span>{{ q.sender_type === 'student' ? 'You' : (q.teacher?.name || 'Subject Teacher') }}</span>
                                                <span class="font-mono opacity-80">{{ new Date(q.created_at).toLocaleDateString() }}</span>
                                            </div>
                                            <p class="text-[#202124] font-medium">{{ q.message }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ================= TAB: STUDY NOTES & SYLLABUS (P2 #13, #18) ================= -->
                <div v-if="activeTab === 'materials'" class="space-y-6 animate-fadeIn">
                    <div class="google-card rounded-2xl p-6 border border-[#DADCE0] bg-white shadow-2xs space-y-6">
                        <!-- Sub-tabs: Study Notes vs Curriculum Syllabus -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-[#DADCE0] pb-4">
                            <div>
                                <h3 class="font-bold text-base text-[#202124] flex items-center gap-2"><i class="fa-solid fa-folder text-[#1A73E8]"></i> <span>Study Notes, Worksheets & Course Syllabus</span></h3>
                                <p class="text-xs text-[#5F6368]">Search slides, bookmark critical files for exam prep, and explore term curriculum.</p>
                            </div>
                            <div class="flex items-center space-x-2">
                                <button 
                                    @click="materialTab = 'notes'" 
                                    :class="materialTab === 'notes' ? 'bg-[#1A73E8] text-white font-bold' : 'bg-[#F8F9FA] text-[#5F6368] hover:text-[#202124] font-medium'"
                                    class="px-4 py-2 rounded-xl text-xs transition-all border border-[#DADCE0]"
                                >
                                    <i class="fa-solid fa-book mr-1.5"></i> Study Materials & Worksheets
                                </button>
                                <button 
                                    @click="materialTab = 'syllabus'" 
                                    :class="materialTab === 'syllabus' ? 'bg-[#1A73E8] text-white font-bold' : 'bg-[#F8F9FA] text-[#5F6368] hover:text-[#202124] font-medium'"
                                    class="px-4 py-2 rounded-xl text-xs transition-all border border-[#DADCE0]"
                                >
                                    <i class="fa-solid fa-book-open mr-1.5"></i> Course Syllabus View
                                </button>
                            </div>
                        </div>

                        <!-- Study Notes View -->
                        <div v-if="materialTab === 'notes'" class="space-y-4">
                            <!-- Search & Subject Filter Bar -->
                            <div class="flex flex-col sm:flex-row gap-3">
                                <div class="flex-1 relative">
                                    <input v-model="materialSearch" type="text" placeholder="Search by title or topic keyword..." class="w-full rounded-xl bg-[#F8F9FA] border border-[#DADCE0] px-4 py-2.5 text-xs focus:border-[#1A73E8] focus:outline-none" />
                                </div>
                                <select v-model="materialFilterSubject" class="rounded-xl bg-[#F8F9FA] border border-[#DADCE0] px-4 py-2.5 text-xs font-semibold text-[#202124] focus:border-[#1A73E8] focus:outline-none">
                                    <option value="all">All Subjects</option>
                                    <option v-for="sub in subjectsList" :key="sub.id" :value="sub.id">{{ sub.name }}</option>
                                </select>
                            </div>

                            <div v-if="!filteredMaterials || filteredMaterials.length === 0" class="py-16 text-center text-sm text-[#5F6368] bg-[#F8F9FA] rounded-2xl border border-dashed border-[#DADCE0]">
                                No study notes found matching your search query.
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div v-for="m in filteredMaterials" :key="m.id" class="flex flex-col justify-between rounded-2xl bg-[#F8F9FA] p-5 border border-[#DADCE0] transition-all hover:border-[#1A73E8]">
                                    <div class="flex items-start justify-between">
                                        <div class="flex items-start space-x-3.5">
                                            <span class="text-3xl mt-1 text-[#1A73E8]"><i :class="m.file_type === 'video' ? 'fa-solid fa-video' : m.file_type === 'ppt' ? 'fa-solid fa-chart-pie' : 'fa-solid fa-file-lines'"></i></span>
                                            <div>
                                                <div class="flex items-center space-x-2">
                                                    <span class="text-2xs font-extrabold uppercase bg-white border px-2 py-0.5 rounded text-[#1A73E8]">{{ m.subject?.name }}</span>
                                                    <span class="text-2xs font-mono text-[#5F6368]">{{ m.file_type.toUpperCase() }}</span>
                                                </div>
                                                <h5 class="font-extrabold text-[#202124] text-base mt-1.5">{{ m.title }}</h5>
                                                <p class="text-xs text-[#5F6368] mt-1 line-clamp-2">{{ m.description || 'Teacher uploaded lecture material for class study.' }}</p>
                                            </div>
                                        </div>
                                        <button @click="toggleBookmark(m.id)" class="p-1 hover:scale-110 transition-transform" :title="bookmarkedIds.includes(m.id) ? 'Bookmarked for Exam Prep' : 'Save for Later'">
                                            <i :class="bookmarkedIds.includes(m.id) ? 'fa-solid fa-star text-[#B06000] text-xl' : 'fa-regular fa-star text-[#5F6368] text-xl'"></i>
                                        </button>
                                    </div>
                                    <div class="pt-4 mt-3 border-t border-[#DADCE0] flex items-center justify-between">
                                        <span class="text-2xs text-[#5F6368]">Uploaded by: {{ m.teacher?.name || 'Subject Teacher' }}</span>
                                        <a :href="m.file_url" target="_blank" class="rounded-xl bg-[#1A73E8] px-4 py-1.5 text-xs font-bold text-white shadow-2xs hover:bg-[#1557B0] transition-colors">
                                            Download File ↗
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Curriculum Syllabus View (P2 #13) -->
                        <div v-if="materialTab === 'syllabus'" class="space-y-6">
                            <div v-if="subjectsList.length === 0" class="py-12 text-center text-sm text-[#5F6368]">No subjects assigned to your class section.</div>
                            <div v-for="sub in subjectsList" :key="sub.id" class="rounded-2xl bg-[#F8F9FA] p-6 border border-[#DADCE0] space-y-4">
                                <div class="flex justify-between items-center border-b border-[#DADCE0] pb-3">
                                    <h4 class="font-extrabold text-[#202124] text-lg flex items-center gap-2"><i class="fa-solid fa-book-open text-[#1A73E8]"></i> <span>{{ sub.name }} — Official Term Syllabus</span></h4>
                                    <span class="text-xs font-bold bg-[#E6F4EA] text-[#137333] border border-[#CEEAD6] px-3 py-1 rounded-full">Term 1 Curriculum</span>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                                    <div class="bg-white p-4 rounded-xl border border-[#DADCE0] space-y-1">
                                        <strong class="text-[#1A73E8] uppercase tracking-wider text-2xs block">Unit 1: Core Fundamentals</strong>
                                        <p class="text-[#202124] font-semibold">Foundational theory, key principles, terminology, and foundational equations.</p>
                                        <span class="text-2xs text-[#5F6368] block pt-1 inline-flex items-center gap-1"><i class="fa-solid fa-check"></i> <span>Status: Completed in Class</span></span>
                                    </div>
                                    <div class="bg-white p-4 rounded-xl border border-[#DADCE0] space-y-1">
                                        <strong class="text-[#8430CE] uppercase tracking-wider text-2xs block">Unit 2: Applied Methodology & Lab</strong>
                                        <p class="text-[#202124] font-semibold">Practical experiments, case analysis, empirical data collection, and report structuring.</p>
                                        <span class="text-2xs text-[#137333] font-bold block pt-1 inline-flex items-center gap-1"><i class="fa-solid fa-circle text-[8px]"></i> <span>Status: In Progress</span></span>
                                    </div>
                                    <div class="bg-white p-4 rounded-xl border border-[#DADCE0] space-y-1">
                                        <strong class="text-[#B06000] uppercase tracking-wider text-2xs block">Unit 3: Advanced Synthesis & Review</strong>
                                        <p class="text-[#202124] font-semibold">Integrative problem solving, past paper review, and term evaluation prep.</p>
                                        <span class="text-2xs text-[#5F6368] block pt-1 inline-flex items-center gap-1"><i class="fa-solid fa-clock"></i> <span>Status: Upcoming Before Finals</span></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ================= TAB: ATTENDANCE & LEAVE APPLICATION (P0 #3) ================= -->
                <div v-if="activeTab === 'attendance'" class="space-y-6 animate-fadeIn">
                    <!-- Summary Stats Card Row -->
                    <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
                        <div class="google-card rounded-2xl p-5 bg-white border border-[#DADCE0] shadow-2xs">
                            <span class="text-2xs font-bold uppercase tracking-wider text-[#5F6368]">Total Logged</span>
                            <h3 class="text-2xl font-extrabold text-[#202124] mt-1">{{ attendanceSummary?.total_days || 0 }}</h3>
                        </div>
                        <div class="google-card rounded-2xl p-5 bg-[#E6F4EA]/60 border border-[#CEEAD6] shadow-2xs">
                            <span class="text-2xs font-bold uppercase tracking-wider text-[#137333]">Present Rate</span>
                            <h3 class="text-2xl font-extrabold text-[#137333] mt-1">{{ attendanceSummary?.percentage ?? 100 }}%</h3>
                        </div>
                        <div class="google-card rounded-2xl p-5 bg-[#FCE8E6]/60 border border-[#FAD2CF] shadow-2xs">
                            <span class="text-2xs font-bold uppercase tracking-wider text-[#C5221F]">Absent Days</span>
                            <h3 class="text-2xl font-extrabold text-[#C5221F] mt-1">{{ attendanceSummary?.absent_days || 0 }}</h3>
                        </div>
                        <div class="google-card rounded-2xl p-5 bg-[#F3E8FD]/60 border border-[#E9D2FD] shadow-2xs">
                            <span class="text-2xs font-bold uppercase tracking-wider text-[#8430CE]">Late Check-ins</span>
                            <h3 class="text-2xl font-extrabold text-[#8430CE] mt-1">{{ attendanceSummary?.late_days || 0 }}</h3>
                        </div>
                        <div class="google-card rounded-2xl p-5 bg-[#FEF7E0]/60 border border-[#FEEFC3] shadow-2xs col-span-2 sm:col-span-1">
                            <span class="text-2xs font-bold uppercase tracking-wider text-[#B06000]">Leave Days</span>
                            <h3 class="text-2xl font-extrabold text-[#B06000] mt-1">{{ attendanceSummary?.leave_days || 0 }}</h3>
                        </div>
                    </div>

                    <!-- Low Attendance Warning Banner -->
                    <div v-if="attendanceSummary?.percentage < 75" class="rounded-2xl bg-[#FCE8E6] border-2 border-[#C5221F] p-5 text-[#C5221F] flex items-center justify-between">
                        <div>
                            <h4 class="font-extrabold text-base flex items-center gap-2"><i class="fa-solid fa-triangle-exclamation"></i> <span>Critical Notice: Exam Eligibility Risk</span></h4>
                            <p class="text-xs font-semibold mt-0.5">Your current attendance rate ({{ attendanceSummary?.percentage }}%) is below the mandatory 75% threshold required to sit for final examinations.</p>
                        </div>
                    </div>

                    <!-- Attendance Log & Leave Applications Grid -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Daily Calendar Roster -->
                        <div class="google-card rounded-2xl p-6 border border-[#DADCE0] bg-white shadow-2xs space-y-4 lg:col-span-2">
                            <div class="flex items-center justify-between border-b border-[#DADCE0] pb-3">
                                <h3 class="font-bold text-base text-[#202124] flex items-center gap-2"><i class="fa-solid fa-calendar-check text-[#1A73E8]"></i> <span>Daily Check-in Roster</span></h3>
                                <span class="text-xs font-semibold text-[#5F6368]">Last 60 Days Record</span>
                            </div>
                            <div v-if="!attendances || attendances.length === 0" class="py-12 text-center text-sm text-[#5F6368]">No attendance records found yet for your profile.</div>
                            <div v-else class="overflow-x-auto max-h-[420px] overflow-y-auto">
                                <table class="w-full text-left text-xs">
                                    <thead class="sticky top-0 bg-white border-b border-[#DADCE0] font-bold uppercase tracking-wider text-[#5F6368]">
                                        <tr>
                                            <th class="py-3 px-4">Date</th>
                                            <th class="py-3 px-4">Status</th>
                                            <th class="py-3 px-4">Verification Mode</th>
                                            <th class="py-3 px-4">Remarks</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-[#DADCE0]">
                                        <tr v-for="att in attendances" :key="att.id" class="hover:bg-[#F8F9FA]">
                                            <td class="py-3.5 px-4 font-mono font-bold text-[#202124]">{{ att.date?.split('T')[0] || att.date }}</td>
                                            <td class="py-3.5 px-4">
                                                <span :class="{
                                                    'bg-[#E6F4EA] text-[#137333] border-[#CEEAD6]': att.status === 'present',
                                                    'bg-[#FCE8E6] text-[#C5221F] border-[#FAD2CF]': att.status === 'absent',
                                                    'bg-[#F3E8FD] text-[#8430CE] border-[#E9D2FD]': att.status === 'late',
                                                    'bg-[#FEF7E0] text-[#B06000] border-[#FEEFC3]': att.status === 'leave',
                                                }" class="rounded-full px-3 py-1 text-2xs font-extrabold uppercase border">
                                                    {{ att.status }}
                                                </span>
                                            </td>
                                            <td class="py-3.5 px-4 font-medium text-[#5F6368]">
                                                <i :class="att.marked_by_ai ? 'fa-solid fa-robot mr-1 text-[#1A73E8]' : 'fa-solid fa-hand-pointer mr-1 text-[#5F6368]'"></i>
                                                {{ att.marked_by_ai ? 'AI Vision Camera' : 'Teacher Roster Grid' }}
                                            </td>
                                            <td class="py-3.5 px-4 text-[#5F6368]">{{ att.remarks || '-' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Leave Application Form Widget (P0 #3) -->
                        <div class="google-card rounded-2xl p-6 border border-[#DADCE0] bg-white shadow-2xs space-y-4">
                            <div class="flex items-center justify-between border-b border-[#DADCE0] pb-3">
                                <h3 class="font-bold text-base text-[#202124] flex items-center gap-2"><i class="fa-solid fa-file-pen text-[#1A73E8]"></i> <span>Apply for Leave</span></h3>
                                <button @click="showLeaveModal = true" class="rounded-xl bg-[#1A73E8] text-white px-3.5 py-1.5 text-xs font-bold shadow-2xs hover:bg-[#1557B0]">
                                    + New Request
                                </button>
                            </div>
                            
                            <h4 class="text-xs font-bold uppercase tracking-wider text-[#5F6368]">Your Recent Leave Applications</h4>
                            <div v-if="!leaves || leaves.length === 0" class="py-8 text-center text-xs text-[#5F6368] bg-[#F8F9FA] rounded-xl border border-dashed border-[#DADCE0]">
                                No leave requests submitted yet.
                            </div>
                            <div v-else class="space-y-3 max-h-80 overflow-y-auto">
                                <div v-for="l in leaves" :key="l.id" class="p-3.5 rounded-xl bg-[#F8F9FA] border border-[#DADCE0] space-y-1.5 text-xs">
                                    <div class="flex justify-between font-bold">
                                        <span class="text-[#202124]">{{ l.start_date?.split('T')[0] }} to {{ l.end_date?.split('T')[0] }}</span>
                                        <span :class="{
                                            'bg-[#FEF7E0] text-[#B06000] border-[#FEEFC3]': l.status === 'pending',
                                            'bg-[#E6F4EA] text-[#137333] border-[#CEEAD6]': l.status === 'approved',
                                            'bg-[#FCE8E6] text-[#C5221F] border-[#FAD2CF]': l.status === 'rejected',
                                        }" class="px-2 py-0.5 rounded text-2xs uppercase font-extrabold border">
                                            {{ l.status }}
                                        </span>
                                    </div>
                                    <p class="text-[#5F6368] italic">{{ l.reason }}</p>
                                    <div v-if="l.admin_remarks" class="pt-1 text-2xs font-semibold text-[#202124] border-t border-[#DADCE0]">
                                        Admin Note: "{{ l.admin_remarks }}"
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ================= TAB: EXAMS & REPORT CARDS (P0 #5) ================= -->
                <div v-if="activeTab === 'exams'" class="space-y-6 animate-fadeIn">
                    <div class="google-card rounded-2xl p-6 border border-[#DADCE0] bg-white shadow-2xs space-y-6">
                        <!-- Header & Overall CGPA Badge -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-[#DADCE0] pb-4">
                            <div>
                                <h3 class="font-bold text-base text-[#202124] flex items-center gap-2"><i class="fa-solid fa-chart-column text-[#1A73E8]"></i> <span>Official Exam Gradebook, Date Sheet & Report Cards</span></h3>
                                <p class="text-xs text-[#5F6368]">Complete subject mark breakdowns, date sheets, and printable PDF term reports.</p>
                            </div>
                            <div class="flex items-center space-x-3">
                                <span class="text-sm font-black bg-[#E8F0FE] text-[#1A73E8] border border-[#D2E3FC] px-4 py-2 rounded-xl shadow-2xs">
                                    Overall CGPA: {{ cgpa || 3.85 }} / 4.00
                                </span>
                            </div>
                        </div>

                        <!-- Date Sheet / Exam Schedule Section (P0 #5) -->
                        <div class="space-y-3">
                            <h4 class="font-extrabold text-sm text-[#202124] flex items-center gap-2">
                                <i class="fa-solid fa-calendar-days text-[#1A73E8]"></i> <span>Published Exam Date Sheet & Room Allocations</span>
                            </h4>
                            <div v-if="!examSchedule || examSchedule.length === 0" class="py-6 text-center text-xs text-[#5F6368] bg-[#F8F9FA] rounded-xl border border-dashed border-[#DADCE0]">
                                No exam schedule or date sheet published for your class right now.
                            </div>
                            <div v-else class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                                <div v-for="es in examSchedule" :key="es.id" class="rounded-xl bg-[#F8F9FA] p-4 border border-[#DADCE0] space-y-2">
                                    <div class="flex justify-between items-center text-2xs font-mono font-bold text-[#1A73E8]">
                                        <span>{{ es.exam_date }}</span>
                                        <span>{{ es.start_time }} - {{ es.end_time }}</span>
                                    </div>
                                    <h5 class="font-black text-sm text-[#202124]">{{ es.subject?.name }}</h5>
                                    <div class="flex justify-between items-center text-2xs text-[#5F6368] pt-1 border-t border-[#DADCE0]">
                                        <span>{{ es.exam?.name }}</span>
                                        <span class="font-bold text-[#137333]">Max Marks: {{ es.max_theory_marks }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Subject-wise Marks Breakdown (P0 #5) -->
                        <div class="space-y-4 pt-4 border-t border-[#DADCE0]">
                            <div class="flex justify-between items-center">
                                <h4 class="font-extrabold text-sm text-[#202124] flex items-center gap-2"><i class="fa-solid fa-clipboard-list text-[#1A73E8]"></i> <span>Subject-wise Result Breakdown & Term GPA</span></h4>
                                <a 
                                    v-if="examMarks?.length > 0" 
                                    :href="`/student/exams/${examMarks[0].exam_id || 1}/report-card?download=pdf`" 
                                    target="_blank" 
                                    class="rounded-xl bg-[#1A73E8] text-white px-4 py-2 text-xs font-bold shadow-sm hover:bg-[#1557B0] transition-colors inline-flex items-center space-x-1.5"
                                >
                                    <i class="fa-solid fa-file-pdf"></i> <span>Download Official Report Card (PDF) &↗</span>
                                </a>
                            </div>

                            <div v-if="!examMarks || examMarks.length === 0" class="py-12 text-center text-sm text-[#5F6368] bg-[#F8F9FA] rounded-xl border border-dashed border-[#DADCE0]">
                                No term exam marks published by your teachers yet.
                            </div>
                            <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div v-for="em in examMarks" :key="em.id" class="rounded-2xl bg-[#F8F9FA] p-5 border border-[#DADCE0] space-y-3">
                                    <div class="flex justify-between items-center border-b border-[#DADCE0] pb-2.5">
                                        <div>
                                            <h4 class="font-extrabold text-[#202124] text-base">{{ em.subject?.name || 'Subject' }}</h4>
                                            <span class="text-2xs font-bold text-[#5F6368]">{{ em.exam?.name || 'Term Examination' }}</span>
                                        </div>
                                        <span class="rounded-full bg-[#137333] text-white px-3.5 py-1 text-xs font-black uppercase shadow-2xs">{{ em.grade || 'A' }} (GPA {{ em.gpa || 4.0 }})</span>
                                    </div>
                                    <div class="grid grid-cols-3 gap-2 text-center text-xs pt-1 font-semibold text-[#5F6368]">
                                        <div class="bg-white p-2.5 rounded-xl border border-[#DADCE0]">Theory: <span class="font-black text-[#202124] block mt-0.5">{{ em.theory_marks_obtained ?? em.theory ?? 0 }}</span></div>
                                        <div class="bg-white p-2.5 rounded-xl border border-[#DADCE0]">Practical: <span class="font-black text-[#202124] block mt-0.5">{{ em.practical_marks_obtained ?? em.practical ?? 0 }}</span></div>
                                        <div class="bg-white p-2.5 rounded-xl border border-[#DADCE0]">Viva: <span class="font-black text-[#202124] block mt-0.5">{{ em.viva_marks_obtained ?? em.viva ?? 0 }}</span></div>
                                    </div>
                                    <div class="flex justify-between items-center text-xs pt-2 border-t border-[#DADCE0] font-extrabold text-[#202124]">
                                        <span>Total Obtained Score:</span>
                                        <span class="text-sm font-black text-[#1A73E8]">{{ em.total_marks_obtained ?? (parseFloat(em.theory_marks_obtained||0) + parseFloat(em.practical_marks_obtained||0) + parseFloat(em.viva_marks_obtained||0)) }} / 100 Marks</span>
                                    </div>
                                    <p v-if="em.teacher_remarks || em.remarks" class="text-xs italic text-[#5F6368] bg-white p-3 rounded-xl border border-[#DADCE0] shadow-2xs flex items-center gap-2"><i class="fa-solid fa-comment-dots text-[#1A73E8]"></i> <span>Teacher Feedback: "{{ em.teacher_remarks || em.remarks }}"</span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ================= TAB: FEE VISIBILITY READ-ONLY (P1 #10) ================= -->
                <div v-if="activeTab === 'fees'" class="space-y-6 animate-fadeIn">
                    <div class="google-card rounded-2xl p-6 border border-[#DADCE0] bg-white shadow-2xs space-y-6">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-[#DADCE0] pb-4">
                            <div>
                                <h3 class="font-bold text-base text-[#202124] flex items-center gap-2"><i class="fa-solid fa-credit-card text-[#1A73E8]"></i> <span>Fee Challans & Online Payments</span></h3>
                                <p class="text-xs text-[#5F6368]">Track your tuition, lab, and transport fee challans. You can instantly pay dues online via Stripe or JazzCash.</p>
                            </div>
                            <span class="bg-[#FEF7E0] text-[#B06000] border border-[#FEEFC3] text-xs font-extrabold px-3.5 py-1.5 rounded-xl">
                                Read-Only Student View
                            </span>
                        </div>

                        <div v-if="!feeChallans || feeChallans.length === 0" class="py-16 text-center text-sm text-[#5F6368] bg-[#F8F9FA] rounded-2xl border border-dashed border-[#DADCE0]">
                            No fee challans issued to your account at this time.
                        </div>
                        <div v-else class="space-y-4">
                            <div v-for="fc in feeChallans" :key="fc.id" class="rounded-2xl p-6 border transition-all flex flex-col md:flex-row md:items-center justify-between gap-4" :class="fc.status === 'paid' ? 'bg-[#F8F9FA] border-[#DADCE0]' : 'bg-white border-2 border-[#B06000]/40 shadow-sm'">
                                <div class="space-y-2">
                                    <div class="flex items-center space-x-2.5">
                                        <span class="text-xs font-black uppercase px-3 py-1 rounded-full border inline-flex items-center gap-1.5"
                                            :class="fc.status === 'paid' ? 'bg-[#E6F4EA] text-[#137333] border-[#CEEAD6]' : fc.status === 'partial' ? 'bg-[#E8F0FE] text-[#1A73E8] border-[#D2E3FC]' : 'bg-[#FCE8E6] text-[#C5221F] border-[#FAD2CF]'"
                                        >
                                            <i :class="fc.status === 'paid' ? 'fa-solid fa-check' : fc.status === 'partial' ? 'fa-solid fa-clock' : 'fa-solid fa-triangle-exclamation'"></i>
                                            <span>{{ fc.status === 'paid' ? 'Fully Paid' : fc.status === 'partial' ? 'Partially Paid' : 'Unpaid / Due' }}</span>
                                        </span>
                                        <span class="font-mono text-xs font-bold text-[#5F6368]">{{ fc.challan_number }}</span>
                                    </div>
                                    <h4 class="font-black text-[#202124] text-lg">{{ fc.fee_type?.toUpperCase() || 'MONTHLY' }} TUITION CHALLAN — {{ fc.month_year }}</h4>
                                    <p class="text-xs text-[#5F6368] font-medium">Issue Date: {{ fc.issue_date }} | Due Date: <strong class="text-[#C5221F]">{{ fc.due_date }}</strong></p>
                                </div>
                                <div class="text-left md:text-right space-y-1 border-t md:border-t-0 pt-3 md:pt-0 border-[#DADCE0]">
                                    <span class="text-2xs font-bold text-[#5F6368] uppercase block">Total Challan Amount</span>
                                    <h3 class="text-2xl font-black text-[#202124]">PKR {{ fc.total_amount }}</h3>
                                    <span v-if="fc.paid_amount > 0" class="text-xs text-[#137333] font-bold block">Paid: PKR {{ fc.paid_amount }}</span>
                                    <span v-if="fc.status !== 'paid'" class="text-2xs text-[#C5221F] font-bold block mb-2">Remaining: PKR {{ fc.total_amount - fc.paid_amount }}</span>
                                    <div class="pt-2 flex items-center justify-end gap-2">
                                        <a :href="'/challans/' + fc.id + '/print'" target="_blank" class="rounded-full bg-white border border-[#DADCE0] px-4 py-2 text-xs font-bold text-[#1A73E8] hover:bg-[#F8F9FA] inline-flex items-center gap-1.5 transition-all">
                                            <i class="fa-solid fa-print"></i> <span>Print</span>
                                        </a>
                                        <Link v-if="fc.status !== 'paid'" :href="`/payments/checkout/${fc.id}`" method="post" as="button" class="rounded-full bg-[#137333] px-5 py-2 text-xs font-bold text-white shadow-sm hover:bg-[#0d5926] inline-flex items-center gap-1.5 transition-all">
                                            <i class="fa-solid fa-bolt"></i> <span>Pay Online</span>
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ================= TAB: MY CERTIFICATES ================= -->
                <div v-if="activeTab === 'certificates'" class="space-y-6 animate-fadeIn">
                    <div class="google-card rounded-2xl border border-[#DADCE0] bg-gradient-to-r from-[#F59E0B] to-[#D97706] p-6 text-white shadow-md flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="space-y-1 max-w-2xl">
                            <span class="text-2xs font-bold uppercase tracking-wider bg-white/20 px-2.5 py-0.5 rounded-full">Achievement Vault</span>
                            <h3 class="text-xl font-extrabold mt-1">My Awarded Certificates</h3>
                            <p class="text-xs text-white/90">Certificates and awards issued by the school for academic excellence, sports achievements, and extracurricular participation.</p>
                        </div>
                        <div class="bg-white/10 p-4 rounded-2xl backdrop-blur-xs border border-white/20 text-center sm:min-w-[140px]">
                            <span class="text-2xs font-bold text-white/80 uppercase">Total Earned</span>
                            <span class="block text-3xl font-extrabold text-white mt-0.5">{{ certificates?.length || 0 }}</span>
                        </div>
                    </div>

                    <div class="google-card rounded-2xl border border-[#DADCE0] bg-white p-6 shadow-2xs space-y-4">
                        <h3 class="text-base font-bold text-[#202124]">Certificate Gallery</h3>
                        <div v-if="!certificates || certificates.length === 0" class="py-16 text-center space-y-3">
                            <div class="text-5xl text-[#F59E0B]"><i class="fa-solid fa-award"></i></div>
                            <div class="font-bold text-sm text-[#202124]">No Certificates Yet</div>
                            <p class="text-xs text-[#5F6368] max-w-xs mx-auto">Certificates awarded by the school will appear here. Keep up your excellent work!</p>
                        </div>
                        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div v-for="cert in certificates" :key="cert.id" class="rounded-2xl border border-[#DADCE0] p-5 bg-gradient-to-br from-[#FEF7E0] to-[#FFFBF0] hover:shadow-md transition-all space-y-3 group">
                                <!-- Certificate Icon -->
                                <div class="flex items-center justify-between">
                                    <div class="h-12 w-12 rounded-xl bg-[#F59E0B]/20 flex items-center justify-center text-[#D97706] text-2xl">
                                        <i class="fa-solid fa-certificate"></i>
                                    </div>
                                    <span class="text-2xs font-bold text-[#B06000] bg-[#FEF7E0] border border-[#FDE68A] px-2 py-0.5 rounded-full uppercase">{{ cert.template?.type || 'Academic' }}</span>
                                </div>
                                <!-- Certificate Details -->
                                <div>
                                    <h4 class="font-extrabold text-[#202124] text-sm leading-snug">{{ cert.template?.name || cert.certificate_name || 'Certificate of Achievement' }}</h4>
                                    <p class="text-2xs text-[#5F6368] mt-1">{{ cert.template?.description || 'Awarded for outstanding achievement.' }}</p>
                                </div>
                                <!-- Footer -->
                                <div class="pt-2 border-t border-[#DADCE0] flex items-center justify-between">
                                    <span class="text-2xs text-[#5F6368] font-semibold"><i class="fa-solid fa-calendar text-[#F59E0B] mr-1"></i>{{ cert.issue_date ? new Date(cert.issue_date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }) : 'Issued' }}</span>
                                    <button @click="window.print()" class="rounded-full bg-[#F59E0B] hover:bg-[#D97706] text-white px-3 py-1 text-2xs font-bold transition-all flex items-center gap-1 shadow-sm">
                                        <i class="fa-solid fa-print"></i>
                                        <span>Print</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ================= TAB: PROFILE, ID CARD & SETTINGS (P0 #4, P2 #14) ================= -->
                <div v-if="activeTab === 'profile'" class="space-y-6 animate-fadeIn">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Profile Card & Avatar Upload -->
                        <div class="google-card rounded-2xl p-6 border border-[#DADCE0] bg-white shadow-2xs space-y-6 lg:col-span-2">
                            <div class="flex items-center justify-between border-b border-[#DADCE0] pb-4">
                                <h3 class="font-bold text-base text-[#202124] flex items-center gap-2"><i class="fa-solid fa-user text-[#1A73E8]"></i> <span>Personal Profile & Guardian Information</span></h3>
                                <span class="text-xs font-semibold text-[#137333] bg-[#E6F4EA] px-3 py-1 rounded-full border border-[#CEEAD6]">Verified Student</span>
                            </div>

                            <div class="flex flex-col sm:flex-row items-center gap-6">
                                <div class="relative">
                                    <div v-if="student?.photo_url" class="h-24 w-24 rounded-2xl overflow-hidden border-2 border-[#1A73E8] shadow-md">
                                        <img :src="student.photo_url" alt="Student Photo" class="h-full w-full object-cover" />
                                    </div>
                                    <div v-else class="h-24 w-24 rounded-2xl bg-[#E8F0FE] text-[#1A73E8] flex items-center justify-center text-3xl font-black border-2 border-[#D2E3FC]">
                                        {{ student?.user?.name?.slice(0, 2)?.toUpperCase() || 'ST' }}
                                    </div>
                                    <button @click="$refs.photoInput.click()" class="absolute -bottom-2 -right-2 bg-[#1A73E8] text-white p-2 rounded-xl shadow-md hover:bg-[#1557B0] transition-colors text-xs" title="Change Photo">
                                        <i class="fa-solid fa-camera"></i>
                                    </button>
                                    <input ref="photoInput" type="file" @change="e => profileForm.photo = e.target.files[0]" class="hidden" accept="image/*" />
                                </div>
                                <div class="space-y-1 text-center sm:text-left">
                                    <h4 class="text-xl font-black text-[#202124]">{{ student?.user?.name }}</h4>
                                    <p class="text-xs text-[#5F6368] font-mono">Admission No: <strong class="text-[#202124]">{{ student?.admission_number || 'ADM-2026-001' }}</strong> | Roll No: <strong class="text-[#202124]">{{ student?.roll_number || '101' }}</strong></p>
                                    <p class="text-xs text-[#1A73E8] font-bold">Class {{ student?.current_class?.name }} — Section {{ student?.current_section?.name }}</p>
                                </div>
                            </div>

                            <form @submit.prevent="submitProfileUpdate" class="space-y-4 pt-4 border-t border-[#DADCE0]">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                                    <div>
                                        <label class="block font-bold text-[#5F6368] uppercase tracking-wider mb-1">Personal Email Address</label>
                                        <input v-model="profileForm.email" type="email" placeholder="student@school.com" class="w-full rounded-xl bg-[#F8F9FA] border border-[#DADCE0] px-4 py-2.5 text-[#202124] font-medium focus:border-[#1A73E8] focus:outline-none" />
                                    </div>
                                    <div>
                                        <label class="block font-bold text-[#5F6368] uppercase tracking-wider mb-1">Personal Contact Number</label>
                                        <input v-model="profileForm.phone" type="text" placeholder="+92 300 1234567" class="w-full rounded-xl bg-[#F8F9FA] border border-[#DADCE0] px-4 py-2.5 text-[#202124] font-medium focus:border-[#1A73E8] focus:outline-none" />
                                    </div>
                                </div>
                                <div class="flex justify-end pt-2">
                                    <button type="submit" :disabled="profileForm.processing" class="rounded-xl bg-[#1A73E8] text-white px-6 py-2.5 text-xs font-bold shadow-sm hover:bg-[#1557B0] transition-all flex items-center justify-center">
                                        <i class="fa-solid fa-floppy-disk mr-1.5" v-if="!profileForm.processing"></i>
                                        <span>{{ profileForm.processing ? 'Saving Changes...' : 'Save Contact & Photo Details' }}</span>
                                    </button>
                                </div>
                            </form>

                            <div class="pt-4 border-t border-[#DADCE0] space-y-3">
                                <h4 class="font-bold text-xs uppercase tracking-wider text-[#5F6368] flex items-center gap-1.5"><i class="fa-solid fa-users text-[#1A73E8]"></i> <span>Guardian Information (Read-Only)</span></h4>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs bg-[#F8F9FA] p-4 rounded-xl border border-[#DADCE0]">
                                    <div>
                                        <span class="block text-2xs text-[#5F6368] font-bold">Father's Name</span>
                                        <strong class="text-[#202124]">{{ student?.parent?.father_name || 'Guardian Name' }}</strong>
                                    </div>
                                    <div>
                                        <span class="block text-2xs text-[#5F6368] font-bold">Father's Contact Phone</span>
                                        <strong class="text-[#202124]">{{ student?.parent?.father_phone || '+92 300 0000000' }}</strong>
                                    </div>
                                    <div>
                                        <span class="block text-2xs text-[#5F6368] font-bold">Residential Address</span>
                                        <strong class="text-[#202124]">{{ student?.parent?.address || 'Registered School Address' }}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Account Security & Quick Actions -->
                        <div class="space-y-6">
                            <!-- Digital ID Preview Widget -->
                            <div class="google-card rounded-2xl p-6 border border-[#DADCE0] bg-gradient-to-br from-[#1A73E8] to-[#1557B0] text-white shadow-md space-y-4 text-center">
                                <div class="flex justify-between items-center text-2xs font-extrabold uppercase tracking-widest opacity-80 border-b border-white/20 pb-2">
                                    <span>Digital Student Identity</span>
                                    <span>Verified ID</span>
                                </div>
                                <div class="py-2">
                                    <div class="h-16 w-16 rounded-full bg-white text-[#1A73E8] font-black text-xl flex items-center justify-center mx-auto shadow-md">
                                        {{ student?.user?.name?.slice(0, 2)?.toUpperCase() || 'ST' }}
                                    </div>
                                    <h4 class="font-black text-lg mt-3">{{ student?.user?.name }}</h4>
                                    <p class="text-xs opacity-90 font-mono">ID: {{ student?.admission_number || 'ADM-1001' }}</p>
                                    <span class="inline-block mt-2 bg-white/20 px-3 py-1 rounded-full text-2xs font-bold uppercase">Class {{ student?.current_class?.name || '10' }} — Sec {{ student?.current_section?.name || 'A' }}</span>
                                </div>
                                <button @click="showIdCardModal = true" class="w-full rounded-xl bg-white text-[#1A73E8] py-2.5 text-xs font-bold hover:bg-[#F8F9FA] transition-colors shadow-sm flex items-center justify-center">
                                    <i class="fa-solid fa-id-card mr-1.5"></i> Preview & Print ID Card &rarr;
                                </button>
                            </div>

                            <!-- Change Password Card (P0 #4) -->
                            <div class="google-card rounded-2xl p-6 border border-[#DADCE0] bg-white shadow-2xs space-y-4">
                                <div class="flex items-center justify-between border-b border-[#DADCE0] pb-3">
                                    <h4 class="font-bold text-sm text-[#202124] flex items-center gap-2"><i class="fa-solid fa-lock text-[#1A73E8]"></i> <span>Account Security & Password Change</span></h4>
                                    <button @click="showPasswordModal = true" class="rounded-xl bg-[#F3E8FD] text-[#8430CE] border border-[#E9D2FD] px-3.5 py-1.5 text-xs font-bold hover:bg-[#E9D2FD] transition-colors">
                                        Change Password
                                    </button>
                                </div>
                                <p class="text-xs text-[#5F6368] leading-relaxed">Ensure your portal password is strong and updated periodically. Forgot password recovery is handled via your school administrator or registered email link.</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </main>

        <!-- ================= MODALS ================= -->

        <!-- Submit Assignment Solution Modal -->
        <div v-if="selectedAssignment" class="fixed inset-0 z-50 flex justify-end bg-black/40 backdrop-blur-xs overflow-hidden">
            <div class="absolute inset-0" @click="selectedAssignment = null"></div>
            <div class="bg-white w-full max-w-lg shadow-2xl flex flex-col h-full z-10 animate-slideLeft border-l border-[#DADCE0]">
                <div class="p-6 sm:p-8 flex-1 overflow-y-auto space-y-6">
                    <div class="flex justify-between items-center border-b border-[#DADCE0] pb-3">
                        <div>
                            <h3 class="font-black text-base text-[#202124]">Submit Homework Solution</h3>
                            <p class="text-2xs text-[#5F6368]">{{ selectedAssignment.title }}</p>
                        </div>
                        <button @click="selectedAssignment = null" class="h-8 w-8 rounded-full hover:bg-[#F8F9FA] font-bold text-[#5F6368] flex items-center justify-center cursor-pointer"><i class="fa-solid fa-xmark text-lg"></i></button>
                    </div>
                    <form @submit.prevent="submitAssignment" class="space-y-4 text-xs">
                        <div>
                            <label class="block font-bold text-[#5F6368] uppercase tracking-wider mb-1">Upload Solution File (PDF, DOCX, ZIP, Image)</label>
                            <input type="file" @change="e => subForm.submission_file = e.target.files[0]" class="w-full rounded-xl bg-[#F8F9FA] border border-[#DADCE0] px-3.5 py-2 focus:border-[#1A73E8] focus:outline-none file:mr-3 file:py-1 file:px-3 file:rounded-full file:border-0 file:text-2xs file:font-bold file:bg-[#E8F0FE] file:text-[#1A73E8] hover:file:bg-[#D2E3FC]" />
                        </div>
                        <div>
                            <label class="block font-bold text-[#5F6368] uppercase tracking-wider mb-1">Or External URL / Drive Link (Optional)</label>
                            <input v-model="subForm.file_url" type="text" placeholder="https://..." class="w-full rounded-xl bg-white border border-[#DADCE0] px-3.5 py-2.5 text-[#202124] focus:border-[#1A73E8] focus:outline-none" />
                        </div>
                        <div>
                            <label class="block font-bold text-[#5F6368] uppercase tracking-wider mb-1">Solution Notes / Reasoning Explanation</label>
                            <textarea v-model="subForm.submission_text" rows="3" placeholder="Explain your answer or steps taken..." class="w-full rounded-xl bg-white border border-[#DADCE0] p-3.5 text-[#202124] focus:border-[#1A73E8] focus:outline-none"></textarea>
                        </div>
                        <div class="pt-3 flex justify-end space-x-3 border-t border-[#DADCE0]">
                            <button type="button" @click="selectedAssignment = null" class="rounded-xl px-5 py-2 text-xs font-bold text-[#5F6368] hover:bg-[#F8F9FA]">Cancel</button>
                            <button type="submit" :disabled="subForm.processing" class="rounded-xl bg-[#1A73E8] px-6 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-[#1557B0] transition-all flex items-center justify-center">
                                <i class="fa-solid fa-upload mr-1.5" v-if="!subForm.processing"></i>
                                <span>{{ subForm.processing ? 'Uploading & AI Grading...' : 'Submit Solution for AI Evaluation' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Grade Re-evaluation Appeal Modal (P1 #7) -->
        <div v-if="selectedAppealAssignment" class="fixed inset-0 z-50 flex justify-end bg-black/40 backdrop-blur-xs overflow-hidden">
            <div class="absolute inset-0" @click="selectedAppealAssignment = null"></div>
            <div class="bg-white w-full max-w-lg shadow-2xl flex flex-col h-full z-10 animate-slideLeft border-l border-[#DADCE0]">
                <div class="p-6 sm:p-8 flex-1 overflow-y-auto space-y-6">
                    <div class="flex justify-between items-center border-b border-[#DADCE0] pb-3">
                        <div>
                            <h3 class="font-black text-base text-[#202124]">Request Teacher Re-evaluation</h3>
                            <p class="text-2xs text-[#5F6368]">{{ selectedAppealAssignment.title }}</p>
                        </div>
                        <button @click="selectedAppealAssignment = null" class="h-8 w-8 rounded-full hover:bg-[#F8F9FA] font-bold text-[#5F6368] flex items-center justify-center cursor-pointer"><i class="fa-solid fa-xmark text-lg"></i></button>
                    </div>
                    <form @submit.prevent="submitAppeal" class="space-y-4 text-xs">
                        <div class="p-3 bg-[#FEF7E0] border border-[#FEEFC3] rounded-xl text-[#B06000]">
                            <span class="font-bold block">Current Score: {{ selectedAppealAssignment.user_submission?.obtained_marks || selectedAppealAssignment.user_submission?.ai_suggested_marks }} / {{ selectedAppealAssignment.total_marks }}</span>
                            <p class="text-2xs mt-0.5">Your request will be routed directly to your subject teacher for manual review and grade override.</p>
                        </div>
                        <div>
                            <label class="block font-bold text-[#5F6368] uppercase tracking-wider mb-1">Reason for Re-evaluation / Appeal</label>
                            <textarea v-model="appealForm.appeal_reason" rows="4" required placeholder="State clearly why you believe your solution warrants higher points or manual review..." class="w-full rounded-xl bg-white border border-[#DADCE0] p-3.5 text-[#202124] focus:border-[#1A73E8] focus:outline-none"></textarea>
                        </div>
                        <div class="pt-3 flex justify-end space-x-3 border-t border-[#DADCE0]">
                            <button type="button" @click="selectedAppealAssignment = null" class="rounded-xl px-5 py-2 text-xs font-bold text-[#5F6368] hover:bg-[#F8F9FA]">Cancel</button>
                            <button type="submit" :disabled="appealForm.processing" class="rounded-xl bg-[#8430CE] px-6 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-[#6A25A6] transition-all flex items-center justify-center">
                                <i class="fa-solid fa-scale-balanced mr-1.5" v-if="!appealForm.processing"></i>
                                <span>{{ appealForm.processing ? 'Submitting Request...' : 'Submit Re-evaluation Request' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Ask-a-Question Modal (P1 #9) -->
        <div v-if="selectedQuestionAssignment" class="fixed inset-0 z-50 flex justify-end bg-black/40 backdrop-blur-xs overflow-hidden">
            <div class="absolute inset-0" @click="selectedQuestionAssignment = null"></div>
            <div class="bg-white w-full max-w-lg shadow-2xl flex flex-col h-full z-10 animate-slideLeft border-l border-[#DADCE0]">
                <div class="p-6 sm:p-8 flex-1 overflow-y-auto space-y-6">
                    <div class="flex justify-between items-center border-b border-[#DADCE0] pb-3">
                        <div>
                            <h3 class="font-black text-base text-[#202124]">Ask Teacher a Question</h3>
                            <p class="text-2xs text-[#5F6368]">{{ selectedQuestionAssignment.title }}</p>
                        </div>
                        <button @click="selectedQuestionAssignment = null" class="h-8 w-8 rounded-full hover:bg-[#F8F9FA] font-bold text-[#5F6368] flex items-center justify-center cursor-pointer"><i class="fa-solid fa-xmark text-lg"></i></button>
                    </div>
                    <form @submit.prevent="submitQuestion" class="space-y-4 text-xs">
                        <div>
                            <label class="block font-bold text-[#5F6368] uppercase tracking-wider mb-1">Your Question or Clarification</label>
                            <textarea v-model="questionForm.message" rows="4" required placeholder="Type your question about homework steps, rubric, or deadline..." class="w-full rounded-xl bg-white border border-[#DADCE0] p-3.5 text-[#202124] focus:border-[#1A73E8] focus:outline-none"></textarea>
                        </div>
                        <div class="pt-3 flex justify-end space-x-3 border-t border-[#DADCE0]">
                            <button type="button" @click="selectedQuestionAssignment = null" class="rounded-xl px-5 py-2 text-xs font-bold text-[#5F6368] hover:bg-[#F8F9FA]">Cancel</button>
                            <button type="submit" :disabled="questionForm.processing" class="rounded-xl bg-[#1A73E8] px-6 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-[#1557B0] transition-all flex items-center justify-center">
                                <i class="fa-solid fa-comment-dots mr-1.5" v-if="!questionForm.processing"></i>
                                <span>{{ questionForm.processing ? 'Sending...' : 'Send Question to Teacher' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Request Leave Modal (P0 #3) -->
        <div v-if="showLeaveModal" class="fixed inset-0 z-50 flex justify-end bg-black/40 backdrop-blur-xs overflow-hidden">
            <div class="absolute inset-0" @click="showLeaveModal = false"></div>
            <div class="bg-white w-full max-w-lg shadow-2xl flex flex-col h-full z-10 animate-slideLeft border-l border-[#DADCE0]">
                <div class="p-6 sm:p-8 flex-1 overflow-y-auto space-y-6">
                    <div class="flex justify-between items-center border-b border-[#DADCE0] pb-3">
                        <h3 class="font-black text-base text-[#202124]">Submit Leave Application</h3>
                        <button @click="showLeaveModal = false" class="h-8 w-8 rounded-full hover:bg-[#F8F9FA] font-bold text-[#5F6368] flex items-center justify-center cursor-pointer"><i class="fa-solid fa-xmark text-lg"></i></button>
                    </div>
                    <form @submit.prevent="submitLeaveRequest" class="space-y-4 text-xs">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block font-bold text-[#5F6368] uppercase tracking-wider mb-1">Start Date</label>
                                <input v-model="leaveForm.start_date" type="date" required class="w-full rounded-xl bg-[#F8F9FA] border border-[#DADCE0] px-3 py-2 text-[#202124] focus:border-[#1A73E8] focus:outline-none" />
                            </div>
                            <div>
                                <label class="block font-bold text-[#5F6368] uppercase tracking-wider mb-1">End Date</label>
                                <input v-model="leaveForm.end_date" type="date" required class="w-full rounded-xl bg-[#F8F9FA] border border-[#DADCE0] px-3 py-2 text-[#202124] focus:border-[#1A73E8] focus:outline-none" />
                            </div>
                        </div>
                        <div>
                            <label class="block font-bold text-[#5F6368] uppercase tracking-wider mb-1">Reason for Leave Request</label>
                            <textarea v-model="leaveForm.reason" rows="3" required placeholder="Medical leave, family emergency, or personal event..." class="w-full rounded-xl bg-white border border-[#DADCE0] p-3.5 text-[#202124] focus:border-[#1A73E8] focus:outline-none"></textarea>
                        </div>
                        <div class="pt-3 flex justify-end space-x-3 border-t border-[#DADCE0]">
                            <button type="button" @click="showLeaveModal = false" class="rounded-xl px-5 py-2 text-xs font-bold text-[#5F6368] hover:bg-[#F8F9FA]">Cancel</button>
                            <button type="submit" :disabled="leaveForm.processing" class="rounded-xl bg-[#1A73E8] px-6 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-[#1557B0] transition-all flex items-center justify-center">
                                <i class="fa-solid fa-file-pen mr-1.5" v-if="!leaveForm.processing"></i>
                                <span>{{ leaveForm.processing ? 'Submitting...' : 'Submit Leave Request' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Change Password Modal (P0 #4) -->
        <div v-if="showPasswordModal" class="fixed inset-0 z-50 flex justify-end bg-black/40 backdrop-blur-xs overflow-hidden">
            <div class="absolute inset-0" @click="showPasswordModal = false"></div>
            <div class="bg-white w-full max-w-lg shadow-2xl flex flex-col h-full z-10 animate-slideLeft border-l border-[#DADCE0]">
                <div class="p-6 sm:p-8 flex-1 overflow-y-auto space-y-6">
                    <div class="flex justify-between items-center border-b border-[#DADCE0] pb-3">
                        <h3 class="font-black text-base text-[#202124]">Change Account Password</h3>
                        <button @click="showPasswordModal = false" class="h-8 w-8 rounded-full hover:bg-[#F8F9FA] font-bold text-[#5F6368] flex items-center justify-center cursor-pointer"><i class="fa-solid fa-xmark text-lg"></i></button>
                    </div>
                    <form @submit.prevent="submitPasswordChange" class="space-y-4 text-xs">
                        <div>
                            <label class="block font-bold text-[#5F6368] uppercase tracking-wider mb-1">Current Password</label>
                            <input v-model="passwordForm.current_password" type="password" required class="w-full rounded-xl bg-[#F8F9FA] border border-[#DADCE0] px-4 py-2.5 text-[#202124] focus:border-[#1A73E8] focus:outline-none" />
                            <span v-if="passwordForm.errors.current_password" class="text-2xs text-[#C5221F] font-bold block mt-1">{{ passwordForm.errors.current_password }}</span>
                        </div>
                        <div>
                            <label class="block font-bold text-[#5F6368] uppercase tracking-wider mb-1">New Password (Min 8 Characters)</label>
                            <input v-model="passwordForm.new_password" type="password" required minlength="8" class="w-full rounded-xl bg-[#F8F9FA] border border-[#DADCE0] px-4 py-2.5 text-[#202124] focus:border-[#1A73E8] focus:outline-none" />
                        </div>
                        <div>
                            <label class="block font-bold text-[#5F6368] uppercase tracking-wider mb-1">Confirm New Password</label>
                            <input v-model="passwordForm.new_password_confirmation" type="password" required minlength="8" class="w-full rounded-xl bg-[#F8F9FA] border border-[#DADCE0] px-4 py-2.5 text-[#202124] focus:border-[#1A73E8] focus:outline-none" />
                        </div>
                        <div class="pt-3 flex justify-end space-x-3 border-t border-[#DADCE0]">
                            <button type="button" @click="showPasswordModal = false" class="rounded-xl px-5 py-2 text-xs font-bold text-[#5F6368] hover:bg-[#F8F9FA]">Cancel</button>
                            <button type="submit" :disabled="passwordForm.processing" class="rounded-xl bg-[#1A73E8] px-6 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-[#1557B0] transition-all flex items-center justify-center">
                                <i class="fa-solid fa-lock mr-1.5" v-if="!passwordForm.processing"></i>
                                <span>{{ passwordForm.processing ? 'Updating...' : 'Update Password' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Digital Student ID Card Modal (P2 #14) -->
        <div v-if="showIdCardModal" class="fixed inset-0 z-50 flex justify-end bg-black/40 backdrop-blur-xs overflow-hidden">
            <div class="absolute inset-0" @click="showIdCardModal = false"></div>
            <div class="bg-white w-full max-w-sm shadow-2xl flex flex-col h-full z-10 animate-slideLeft border-l border-[#DADCE0]">
                <!-- ID Card Header -->
                <div class="bg-gradient-to-r from-[#1A73E8] to-[#1557B0] p-6 text-white text-center relative">
                    <div class="flex items-center justify-center space-x-2 mb-2">
                        <span class="text-2xl"><i class="fa-solid fa-graduation-cap"></i></span>
                        <h4 class="font-black text-sm tracking-wider uppercase">{{ $page.props.tenant?.name || 'Academic Institute' }}</h4>
                    </div>
                    <span class="text-2xs uppercase tracking-widest bg-white/20 px-3 py-0.5 rounded-full font-bold">Official Student ID</span>
                </div>
                <!-- ID Card Body -->
                <div class="p-6 text-center space-y-4 flex-1 overflow-y-auto">
                    <div class="h-24 w-24 rounded-2xl overflow-hidden border-4 border-white shadow-lg mx-auto bg-white relative">
                        <img v-if="student?.photo_url" :src="student.photo_url" alt="Student Avatar" class="h-full w-full object-cover" />
                        <div v-else class="h-full w-full bg-[#E8F0FE] text-[#1A73E8] flex items-center justify-center font-black text-3xl">
                            {{ student?.user?.name?.slice(0, 2)?.toUpperCase() || 'ST' }}
                        </div>
                    </div>
                    <div>
                        <h3 class="font-black text-xl text-[#202124]">{{ student?.user?.name }}</h3>
                        <p class="text-xs font-bold text-[#1A73E8]">Class {{ student?.current_class?.name || '10' }} — Sec {{ student?.current_section?.name || 'A' }}</p>
                    </div>
                    <div class="bg-[#F8F9FA] rounded-2xl p-4 border border-[#DADCE0] text-2xs space-y-2 text-left font-mono">
                        <div class="flex justify-between">
                            <span class="text-[#5F6368] font-bold">Admission #:</span>
                            <strong class="text-[#202124]">{{ student?.admission_number || 'ADM-1001' }}</strong>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-[#5F6368] font-bold">Roll Number:</span>
                            <strong class="text-[#202124]">{{ student?.roll_number || '101' }}</strong>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-[#5F6368] font-bold">Emergency Phone:</span>
                            <strong class="text-[#202124]">{{ student?.parent?.father_phone || '+92 300 0000000' }}</strong>
                        </div>
                    </div>
                    <!-- Barcode simulation -->
                    <div class="py-2 border-t border-[#DADCE0] flex flex-col items-center">
                        <div class="h-10 w-full bg-[#202124] opacity-80 rounded flex items-center justify-center tracking-[0.3em] font-mono text-white text-2xs overflow-hidden">
                            ||| | |||| || | || |||| | ||| || ||| | ||
                        </div>
                        <span class="text-[9px] font-mono text-[#5F6368] mt-1">{{ student?.admission_number || 'ADM-2026-0001' }}</span>
                    </div>
                </div>
                <!-- ID Card Footer -->
                <div class="bg-[#F8F9FA] p-4 border-t border-[#DADCE0] flex justify-between items-center text-xs">
                    <button @click="showIdCardModal = false" class="font-bold text-[#5F6368] hover:text-[#202124] px-3 py-1">Close</button>
                    <button @click="window.print()" class="rounded-xl bg-[#1A73E8] text-white px-4 py-1.5 font-bold shadow-sm hover:bg-[#1557B0] flex items-center justify-center">
                        <i class="fa-solid fa-print mr-1.5"></i> Print Card
                    </button>
                </div>
            </div>
        </div>

    </div>
</template>
