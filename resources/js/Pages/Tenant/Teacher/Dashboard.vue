<script setup>
import { ref, computed, watchEffect } from 'vue';
import { Link, useForm, usePage, router } from '@inertiajs/vue3';
import TenantAppLayout from '@/Layouts/TenantAppLayout.vue';

const props = defineProps({
    todayClasses: Array,
    sections: Array,
    subjects: Array,
    assignments: Array,
    materials: Array,
    exams: Array,
    students: Array,
    myLeaves: Array,
    observations: Array,
    student_leaves: { type: Array, default: () => [] },
    sports_activities: { type: Array, default: () => [] },
    penalties: { type: Array, default: () => [] },
    notices: { type: Array, default: () => [] },
});

const activeTab = ref('timetable');
const showMaterialModal = ref(false);
const showAssignmentModal = ref(false);
const showSubmissionsModal = ref(false);
const activeAssignmentForReview = ref(null);
const activeSubmissionForGrade = ref(null);
const isEvaluatingId = ref(null);
const gradeForm = useForm({
    obtained_marks: '',
    teacher_remarks: '',
});

const openSubmissionsModal = (assignment) => {
    activeAssignmentForReview.value = assignment;
    showSubmissionsModal.value = true;
};

const openGradeModal = (sub) => {
    activeSubmissionForGrade.value = sub;
    gradeForm.obtained_marks = sub.obtained_marks || (sub.ai_suggested_marks !== null ? sub.ai_suggested_marks : '');
    gradeForm.teacher_remarks = sub.teacher_remarks || sub.ai_feedback || '';
};

const triggerAiEvaluation = (submission) => {
    isEvaluatingId.value = submission.id;
    router.post(`/teacher/submissions/${submission.id}/ai-evaluate`, {}, {
        onFinish: () => { isEvaluatingId.value = null; }
    });
};

const submitGrade = () => {
    if (!activeSubmissionForGrade.value) return;
    gradeForm.post(`/teacher/submissions/${activeSubmissionForGrade.value.id}/grade`, {
        onSuccess: () => {
            activeSubmissionForGrade.value = null;
            gradeForm.reset();
        },
    });
};

const showObservationModal = ref(false);
const showLeaveModal = ref(false);
const isSidebarCollapsed = ref(false);
const isMobileMenuOpen = ref(false);

// Material form
const materialForm = useForm({
    subject_id: props.todayClasses?.[0]?.subject_id || '',
    title: '',
    description: '',
    file_type: 'pdf',
    file_url: '',
    file_upload: null,
});

const submitMaterial = () => {
    materialForm.post('/teacher/materials', {
        onSuccess: () => {
            showMaterialModal.value = false;
            materialForm.reset();
        },
    });
};

// Assignment form
const assignmentForm = useForm({
    subject_id: props.subjects?.[0]?.id || '',
    section_id: props.sections?.[0]?.id || '',
    title: '',
    description: '',
    due_date: new Date(Date.now() + 7 * 86400000).toISOString().split('T')[0],
    total_marks: 100,
    attachment_url: '',
    attachment_file: null,
    ai_evaluation_enabled: true,
    external_links: [{ title: '', url: '' }],
});

const addExternalLink = () => {
    assignmentForm.external_links.push({ title: '', url: '' });
};

const removeExternalLink = (index) => {
    if (assignmentForm.external_links.length > 1) {
        assignmentForm.external_links.splice(index, 1);
    } else {
        assignmentForm.external_links[0] = { title: '', url: '' };
    }
};

const submitAssignment = () => {
    assignmentForm.post('/teacher/assignments', {
        onSuccess: () => {
            showAssignmentModal.value = false;
            assignmentForm.reset();
            assignmentForm.external_links = [{ title: '', url: '' }];
        },
    });
};

// Gradebook marks form
const selectedExamId = ref(props.exams?.[0]?.id || '');
const selectedSubjectId = ref(props.subjects?.[0]?.id || '');
const marksForm = useForm({
    exam_id: '',
    subject_id: '',
    marks: {},
});

watchEffect(() => {
    if (props.students && props.students.length > 0) {
        props.students.forEach(std => {
            if (!marksForm.marks[std.id]) {
                marksForm.marks[std.id] = {
                    theory: 75,
                    practical: 40,
                    viva: 18,
                };
            }
        });
    }
});

const showSuccessToast = ref(false);
const successToastMsg = ref('');

const triggerToast = (msg) => {
    successToastMsg.value = msg;
    showSuccessToast.value = true;
    setTimeout(() => { showSuccessToast.value = false; }, 4000);
};

const submitMarks = () => {
    marksForm.exam_id = selectedExamId.value;
    marksForm.subject_id = selectedSubjectId.value;
    marksForm.post('/teacher/marks', {
        onSuccess: () => {
            triggerToast('Gradebook marks saved and GPA/Letter grades recalculated automatically!');
        },
    });
};

// Observation form
const observationForm = useForm({
    student_id: props.students?.[0]?.id || '',
    type: 'commendation',
    title: '',
    description: '',
    parent_visible: true,
});

const submitObservation = () => {
    observationForm.post('/teacher/observations', {
        onSuccess: () => {
            showObservationModal.value = false;
            observationForm.reset();
        },
    });
};

// Leave request form
const leaveForm = useForm({
    leave_type: 'casual',
    start_date: new Date().toISOString().split('T')[0],
    end_date: new Date(Date.now() + 86400000).toISOString().split('T')[0],
    reason: '',
});

const submitLeave = () => {
    leaveForm.post('/teacher/leaves', {
        onSuccess: () => {
            showLeaveModal.value = false;
            leaveForm.reset();
        },
    });
};

const approveStudentLeave = (leaveId, status) => {
    const remarks = prompt(`Enter remarks for student leave ${status.toUpperCase()} (optional):`, '');
    if (remarks !== null) {
        useForm({ status, admin_remarks: remarks }).post(`/teacher/student-leaves/${leaveId}/status`);
    }
};

const logout = () => {
    useForm({}).post('/logout');
};

const teacherProfileForm = useForm({
    email: '',
    phone: '',
});

const teacherPasswordForm = useForm({
    current_password: '',
    new_password: '',
    new_password_confirmation: '',
});

watchEffect(() => {
    const user = usePage().props.auth?.user;
    if (user) {
        teacherProfileForm.email = user.email || '';
        teacherProfileForm.phone = user.phone || '';
    }
});

const submitTeacherProfileChange = () => {
    teacherProfileForm.post('/teacher/profile/update', {
        preserveScroll: true,
        onSuccess: () => {
            alert('Profile contact details updated successfully!');
        }
    });
};

const submitTeacherPasswordChange = () => {
    teacherPasswordForm.post('/teacher/profile/password', {
        preserveScroll: true,
        onSuccess: () => {
            teacherPasswordForm.reset();
            alert('Password updated securely!');
        }
    });
};

// Teacher Sports & Activities State
const showTeacherSportsModal = ref(false);
const teacherSportsForm = useForm({
    name: '',
    category: 'Sports',
    schedule: '',
    venue: '',
    max_participants: 30,
    status: 'Upcoming',
});
const submitTeacherSports = () => {
    teacherSportsForm.post('/teacher/sports', {
        onSuccess: () => { showTeacherSportsModal.value = false; teacherSportsForm.reset(); }
    });
};

// Teacher Penalty State
const showTeacherPenaltyModal = ref(false);
const teacherPenaltyForm = useForm({
    student_id: '',
    penalty_type: 'Disciplinary Violation',
    amount: 200,
    reason: '',
    due_date: new Date(Date.now() + 7 * 86400000).toISOString().split('T')[0]
});
const submitTeacherPenalty = () => {
    teacherPenaltyForm.post('/teacher/penalties', {
        onSuccess: () => { showTeacherPenaltyModal.value = false; teacherPenaltyForm.reset(); }
    });
};

const pendingSubmissionsCount = computed(() => {
    let count = 0;
    props.assignments?.forEach(a => {
        const unreviewed = a.submissions?.filter(s => s.obtained_marks === null || s.obtained_marks === undefined)?.length || 0;
        count += unreviewed;
    });
    return count;
});

const pendingStudentLeavesCount = computed(() => {
    return props.student_leaves?.filter(l => l.status === 'pending')?.length || 0;
});

const navItems = computed(() => [
    { id: 'timetable', label: "Today's Timetable", icon: 'fa-solid fa-calendar-day' },
    { id: 'classes', label: 'My Classes & Attendance', icon: 'fa-solid fa-chalkboard-user', href: '/teacher/attendance' },
    { 
        id: 'assignments', 
        label: 'Assignments & Grading', 
        icon: 'fa-solid fa-clipboard-check',
        badge: pendingSubmissionsCount.value > 0 ? `${pendingSubmissionsCount.value} to grade` : null,
        badgeClass: 'bg-[#FEF3C7] text-[#92400E] font-semibold'
    },
    { id: 'materials', label: 'Learning Materials (LMS)', icon: 'fa-solid fa-book-open' },
    { id: 'gradebook', label: 'Exam Gradebook', icon: 'fa-solid fa-award' },
    { id: 'observations', label: 'Student Observations', icon: 'fa-solid fa-eye' },
    { 
        id: 'leaves', 
        label: 'Leave Requests', 
        icon: 'fa-solid fa-calendar-minus',
        badge: pendingStudentLeavesCount.value > 0 ? `${pendingStudentLeavesCount.value} pending` : null,
        badgeClass: 'bg-[#FEF3C7] text-[#92400E] font-semibold'
    },
    { id: 'notices', label: 'School Notices', icon: 'fa-solid fa-bullhorn' },
    { id: 'sports', label: 'Sports & Clubs', icon: 'fa-solid fa-trophy' },
    { id: 'penalties', label: 'Penalties & Fines', icon: 'fa-solid fa-gavel' },
    { id: 'chat', label: 'Parent Chat', icon: 'fa-solid fa-comments', href: '/teacher/chat' },
    { id: 'settings', label: 'Account Settings', icon: 'fa-solid fa-gear' },
]);

const currentTabTitle = computed(() => {
    switch (activeTab.value) {
        case 'timetable': return 'Daily Classroom Schedule';
        case 'materials': return 'Learning Materials (LMS)';
        case 'assignments': return 'Active Assignments & Grading';
        case 'gradebook': return 'Examination Gradebook & Marks';
        case 'observations': return 'Student Behavioral Observations';
        case 'leaves': return 'Leave Management';
        case 'sports': return 'Sports & Extracurriculars';
        case 'penalties': return 'Student Disciplinary Penalties';
        case 'notices': return 'School Notice Board';
        case 'settings': return 'Teacher Account Settings';
        default: return 'Teaching Workspace';
    }
});

const currentTabDescription = computed(() => {
    switch (activeTab.value) {
        case 'timetable': return 'Your scheduled periods, room assignments, and daily teaching rhythm.';
        case 'materials': return 'Upload lectures, notes, syllabus guides, and worksheets for your classes.';
        case 'assignments': return 'Create coursework, review student submissions, and trigger AI grading evaluation.';
        case 'gradebook': return 'Input Theory, Practical, and Viva exam marks for automated GPA reports.';
        case 'observations': return 'Log behavioral commendations or warnings visible to parents and administration.';
        case 'leaves': return 'Submit personal leave requests and authorize student absence applications.';
        case 'sports': return 'Manage extracurricular sports teams and club rosters.';
        case 'penalties': return 'Record student disciplinary warnings and violation fines.';
        case 'notices': return 'Official announcements and institutional circulars posted by school administration.';
        case 'settings': return 'Manage your login credentials, profile details, and account password.';
        default: return '';
    }
});
</script>

<template>
    <TenantAppLayout
        :title="currentTabTitle"
        :description="currentTabDescription"
        :nav-items="navItems"
        :active-tab="activeTab"
        role-title="Teacher"
        @tab-change="tab => activeTab = tab"
    >
        <template #header-actions>
            <Link 
                href="/teacher/attendance"
                class="px-3.5 py-2 rounded-xl bg-[#0B0D14] hover:bg-[#2F6BFF] text-white text-xs font-semibold shadow-2xs transition-colors inline-flex items-center gap-1.5 cursor-pointer"
            >
                <i class="fa-solid fa-camera text-2xs"></i>
                <span>Mark Attendance</span>
            </Link>
            <button 
                v-if="activeTab === 'materials'"
                @click="showMaterialModal = true"
                class="px-3.5 py-2 rounded-xl bg-[#0B0D14] hover:bg-[#2F6BFF] text-white text-xs font-semibold shadow-2xs transition-colors flex items-center gap-1.5 cursor-pointer"
            >
                <i class="fa-solid fa-plus text-2xs"></i>
                <span>Upload Material</span>
            </button>
            <button 
                v-if="activeTab === 'assignments'"
                @click="showAssignmentModal = true"
                class="px-3.5 py-2 rounded-xl bg-[#0B0D14] hover:bg-[#2F6BFF] text-white text-xs font-semibold shadow-2xs transition-colors flex items-center gap-1.5 cursor-pointer"
            >
                <i class="fa-solid fa-plus text-2xs"></i>
                <span>New Assignment</span>
            </button>
            <button 
                v-if="activeTab === 'observations'"
                @click="showObservationModal = true"
                class="px-3.5 py-2 rounded-xl bg-[#0B0D14] hover:bg-[#2F6BFF] text-white text-xs font-semibold shadow-2xs transition-colors flex items-center gap-1.5 cursor-pointer"
            >
                <i class="fa-solid fa-plus text-2xs"></i>
                <span>Log Observation</span>
            </button>
            <button 
                v-if="activeTab === 'leaves'"
                @click="showLeaveModal = true"
                class="px-3.5 py-2 rounded-xl bg-[#0B0D14] hover:bg-[#2F6BFF] text-white text-xs font-semibold shadow-2xs transition-colors flex items-center gap-1.5 cursor-pointer"
            >
                <i class="fa-solid fa-plus text-2xs"></i>
                <span>Apply for Leave</span>
            </button>
            <button 
                v-if="activeTab === 'sports'"
                @click="showTeacherSportsModal = true"
                class="px-3.5 py-2 rounded-xl bg-[#0B0D14] hover:bg-[#2F6BFF] text-white text-xs font-semibold shadow-2xs transition-colors flex items-center gap-1.5 cursor-pointer"
            >
                <i class="fa-solid fa-plus text-2xs"></i>
                <span>New Activity</span>
            </button>
            <button 
                v-if="activeTab === 'penalties'"
                @click="showTeacherPenaltyModal = true"
                class="px-3.5 py-2 rounded-xl bg-[#0B0D14] hover:bg-[#2F6BFF] text-white text-xs font-semibold shadow-2xs transition-colors flex items-center gap-1.5 cursor-pointer"
            >
                <i class="fa-solid fa-plus text-2xs"></i>
                <span>Issue Penalty</span>
            </button>
        </template>

        <!-- ================= TAB: TIMETABLE ================= -->
                <div v-if="activeTab === 'timetable'" class="space-y-6">
                    <!-- Action Required Banner for Teacher -->
                    <div v-if="pendingSubmissionsCount > 0 || pendingStudentLeavesCount > 0" class="rounded-2xl border border-[#FDE68A] bg-[#FFFBEB] p-4 sm:p-5 shadow-2xs">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <span class="h-8 w-8 rounded-xl bg-[#F59E0B] text-white flex items-center justify-center flex-shrink-0 text-xs shadow-2xs">
                                    <i class="fa-solid fa-bell"></i>
                                </span>
                                <div>
                                    <h3 class="text-xs font-bold text-[#92400E] uppercase tracking-wider">Classroom Tasks Pending Attention</h3>
                                    <p class="text-xs text-[#B45309] mt-0.5">You have active submissions or student absence requests awaiting your review.</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <button 
                                    v-if="pendingSubmissionsCount > 0" 
                                    @click="activeTab = 'assignments'" 
                                    class="px-3 py-1.5 rounded-lg bg-white border border-[#FDE68A] text-[#92400E] hover:bg-[#FEF3C7] text-xs font-semibold shadow-2xs transition-colors flex items-center gap-1.5 cursor-pointer"
                                >
                                    <span class="raibix-dot h-1.5 w-1.5"></span>
                                    <span>{{ pendingSubmissionsCount }} Submission(s) to Grade</span>
                                </button>
                                <button 
                                    v-if="pendingStudentLeavesCount > 0" 
                                    @click="activeTab = 'leaves'" 
                                    class="px-3 py-1.5 rounded-lg bg-white border border-[#FDE68A] text-[#92400E] hover:bg-[#FEF3C7] text-xs font-semibold shadow-2xs transition-colors flex items-center gap-1.5 cursor-pointer"
                                >
                                    <span class="raibix-dot h-1.5 w-1.5"></span>
                                    <span>{{ pendingStudentLeavesCount }} Student Leave(s)</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Teacher Metric Overview -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="rounded-2xl p-5 bg-white border border-[#E5E7EB] shadow-2xs flex flex-col justify-between">
                            <div class="flex items-center justify-between">
                                <span class="text-2xs font-bold uppercase tracking-wider text-[#5B6270]">Today's Periods</span>
                                <span class="h-8 w-8 rounded-xl bg-[#F8F9FA] text-[#0B0D14] border border-[#E5E7EB] flex items-center justify-center text-xs">
                                    <i class="fa-solid fa-calendar-day"></i>
                                </span>
                            </div>
                            <div class="mt-4 flex items-baseline justify-between">
                                <span class="text-2xl font-bold tracking-tight text-[#0B0D14]">{{ todayClasses?.length || 0 }}</span>
                                <span class="text-3xs font-semibold text-[#166534] bg-[#F0FDF4] border border-[#BBF7D0] px-2 py-0.5 rounded-full">Scheduled</span>
                            </div>
                            <div class="mt-2 text-3xs text-[#5B6270]">
                                Across assigned sections
                            </div>
                        </div>

                        <div class="rounded-2xl p-5 bg-white border border-[#E5E7EB] shadow-2xs flex flex-col justify-between">
                            <div class="flex items-center justify-between">
                                <span class="text-2xs font-bold uppercase tracking-wider text-[#5B6270]">My Classes</span>
                                <span class="h-8 w-8 rounded-xl bg-[#F8F9FA] text-[#0B0D14] border border-[#E5E7EB] flex items-center justify-center text-xs">
                                    <i class="fa-solid fa-chalkboard-user"></i>
                                </span>
                            </div>
                            <div class="mt-4 flex items-baseline justify-between">
                                <span class="text-2xl font-bold tracking-tight text-[#0B0D14]">{{ sections?.length || 0 }}</span>
                                <span class="text-3xs font-semibold text-[#2F6BFF] bg-[#EEF3FF] border border-[#D2E3FC] px-2 py-0.5 rounded-full">Sections</span>
                            </div>
                            <div class="mt-2 text-3xs text-[#5B6270]">
                                {{ subjects?.length || 0 }} subject curriculum(s)
                            </div>
                        </div>

                        <div class="rounded-2xl p-5 bg-white border border-[#E5E7EB] shadow-2xs flex flex-col justify-between">
                            <div class="flex items-center justify-between">
                                <span class="text-2xs font-bold uppercase tracking-wider text-[#5B6270]">Coursework Tasks</span>
                                <span class="h-8 w-8 rounded-xl bg-[#F8F9FA] text-[#0B0D14] border border-[#E5E7EB] flex items-center justify-center text-xs">
                                    <i class="fa-solid fa-clipboard-check"></i>
                                </span>
                            </div>
                            <div class="mt-4 flex items-baseline justify-between">
                                <span class="text-2xl font-bold tracking-tight text-[#0B0D14]">{{ assignments?.length || 0 }}</span>
                                <span class="text-3xs font-semibold text-[#92400E] bg-[#FEF3C7] border border-[#FDE68A] px-2 py-0.5 rounded-full">{{ pendingSubmissionsCount }} To Grade</span>
                            </div>
                            <div class="mt-2 text-3xs text-[#5B6270]">
                                Active student assignments
                            </div>
                        </div>

                        <div class="rounded-2xl p-5 bg-white border border-[#E5E7EB] shadow-2xs flex flex-col justify-between">
                            <div class="flex items-center justify-between">
                                <span class="text-2xs font-bold uppercase tracking-wider text-[#5B6270]">LMS Materials</span>
                                <span class="h-8 w-8 rounded-xl bg-[#F8F9FA] text-[#0B0D14] border border-[#E5E7EB] flex items-center justify-center text-xs">
                                    <i class="fa-solid fa-book-open"></i>
                                </span>
                            </div>
                            <div class="mt-4 flex items-baseline justify-between">
                                <span class="text-2xl font-bold tracking-tight text-[#0B0D14]">{{ materials?.length || 0 }}</span>
                                <span class="text-3xs font-semibold text-[#166534] bg-[#F0FDF4] border border-[#BBF7D0] px-2 py-0.5 rounded-full">Uploaded</span>
                            </div>
                            <div class="mt-2 text-3xs text-[#5B6270]">
                                Lectures, guides & worksheets
                            </div>
                        </div>
                    </div>

                    <!-- Timetable Sessions Header -->
                    <div class="flex items-center justify-between bg-white p-4 rounded-2xl border border-[#E5E7EB] shadow-2xs">
                        <div class="flex items-center gap-2">
                            <span class="raibix-dot h-2 w-2"></span>
                            <span class="text-xs font-bold text-[#0B0D14]">Today's Teaching Schedule</span>
                        </div>
                        <span class="rounded-full bg-[#F8F9FA] border border-[#E5E7EB] px-3 py-1 text-2xs font-semibold text-[#5B6270]">
                            {{ new Date().toLocaleDateString('en-US', { weekday: 'long', month: 'short', day: 'numeric' }) }}
                        </span>
                    </div>

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                        <div v-if="!todayClasses || todayClasses.length === 0" class="col-span-3 py-12 text-center text-sm text-[#5F6368] bg-white rounded-2xl border border-[#DADCE0]">
                            No scheduled timetable sessions found for today. Use "Mark Attendance" to pick any section!
                        </div>
                        <div v-for="tc in todayClasses" :key="tc.id" class="google-card rounded-2xl bg-white p-6 border border-[#DADCE0] flex flex-col justify-between shadow-2xs">
                            <div>
                                <span class="text-xs font-bold text-[#8430CE] uppercase tracking-wider bg-[#F3E8FD] px-2.5 py-1 rounded-md">{{ tc.start_time }} - {{ tc.end_time }}</span>
                                <h4 class="text-xl font-extrabold text-[#202124] mt-3">{{ tc.subject?.name }}</h4>
                                <p class="text-sm text-[#5F6368] font-medium mt-1">{{ tc.section?.school_class?.name }} - {{ tc.section?.name }}</p>
                            </div>
                            <div class="mt-6 pt-4 border-t border-[#DADCE0] flex justify-between items-center">
                                <span class="text-xs text-[#5F6368]">Room: {{ tc.room_number || 'Lab 101' }}</span>
                                <Link :href="`/teacher/attendance?section_id=${tc.section_id}`" class="rounded-full bg-[#E8F0FE] border border-[#D2E3FC] px-4 py-1.5 text-xs font-bold text-[#1A73E8] hover:bg-[#D2E3FC] transition-colors">
                                    Mark Attendance →
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ================= TAB: MATERIALS ================= -->
                <div v-if="activeTab === 'materials'" class="space-y-6 animate-fadeIn">
                    <div class="google-card overflow-hidden rounded-2xl border border-[#DADCE0] bg-white shadow-2xs">
                        <div class="p-6 border-b border-[#DADCE0] flex justify-between items-center">
                            <div>
                                <h3 class="text-base font-bold text-[#202124]">Uploaded Files & Lectures</h3>
                                <p class="text-xs text-[#5F6368]">Accessible to students in your assigned classes.</p>
                            </div>
                            <button @click="showMaterialModal = true" class="rounded-full bg-[#8430CE] px-4 py-2 text-xs font-bold text-white">
                                + Upload File
                            </button>
                        </div>

                        <table class="w-full text-left border-collapse text-sm">
                            <thead>
                                <tr class="border-b border-[#DADCE0] bg-[#F8F9FA] text-xs font-bold uppercase tracking-wider text-[#5F6368]">
                                    <th class="py-4 px-6">File Title</th>
                                    <th class="py-4 px-6">Subject</th>
                                    <th class="py-4 px-6">Type</th>
                                    <th class="py-4 px-6 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#E8EAED]">
                                <tr v-if="!materials || materials.length === 0">
                                    <td colspan="4" class="py-12 text-center text-sm text-[#5F6368]">No materials uploaded yet.</td>
                                </tr>
                                <tr v-for="m in materials" :key="m.id" class="hover:bg-[#F8F9FA]/80">
                                    <td class="py-4 px-6 font-bold text-[#202124]">{{ m.title }}</td>
                                    <td class="py-4 px-6">{{ m.subject?.name }}</td>
                                    <td class="py-4 px-6">
                                        <span class="rounded-lg bg-[#F1F3F4] px-2.5 py-1 text-xs font-bold uppercase">{{ m.file_type }}</span>
                                    </td>
                                    <td class="py-4 px-6 text-right">
                                        <a :href="m.file_url" target="_blank" class="text-xs font-bold text-[#1A73E8] hover:underline">Download ↗</a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ================= TAB: ASSIGNMENTS ================= -->
                <div v-if="activeTab === 'assignments'" class="space-y-6 animate-fadeIn">
                    <div class="google-card rounded-2xl border border-[#DADCE0] bg-white p-6 shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <h3 class="text-base font-bold text-[#202124]">Active Assignments & Homework Tasks</h3>
                            <p class="text-xs text-[#5F6368]">Create homework tasks, attach worksheets/documents, and view AI grading feedback for students.</p>
                        </div>
                        <button @click="showAssignmentModal = true" class="rounded-full bg-[#8430CE] px-5 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-[#6e23B0] transition-all flex items-center space-x-2 flex-shrink-0">
                            <span>+</span>
                            <span>Create Assignment</span>
                        </button>
                    </div>

                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 md:grid-cols-3">
                        <div v-if="!assignments || assignments.length === 0" class="col-span-3 py-16 text-center bg-white rounded-2xl border border-[#DADCE0] shadow-2xs space-y-3">
                            <div class="text-3xl text-[#8430CE]"><i class="fa-solid fa-pen-to-square"></i></div>
                            <h4 class="text-base font-bold text-[#202124]">No Assignments Published Yet</h4>
                            <p class="text-xs text-[#5F6368] max-w-md mx-auto">Click below to create your first homework assignment or attach a problem set document for your students.</p>
                            <div class="pt-2">
                                <button @click="showAssignmentModal = true" class="rounded-full bg-[#8430CE] px-6 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-[#6e23B0] transition-all inline-flex items-center space-x-2">
                                    <span>+</span>
                                    <span>Create First Homework Assignment</span>
                                </button>
                            </div>
                        </div>
                        <div v-for="a in assignments" :key="a.id" class="google-card rounded-2xl bg-white p-6 border border-[#DADCE0] flex flex-col justify-between shadow-2xs">
                            <div>
                                <div class="flex justify-between items-start gap-2">
                                    <span class="text-xs font-bold text-[#C5221F] uppercase bg-[#FCE8E6] px-2.5 py-1 rounded-md">Due: {{ a.due_date }}</span>
                                    <a v-if="a.attachment_url" :href="a.attachment_url" target="_blank" class="rounded-lg bg-[#E8F0FE] px-2.5 py-1 text-2xs font-bold text-[#1A73E8] hover:underline flex items-center gap-1">
                                        <i class="fa-solid fa-paperclip"></i>
                                        <span>Document</span>
                                    </a>
                                </div>
                                <h4 class="text-lg font-extrabold text-[#202124] mt-3">{{ a.title }}</h4>
                                <p class="text-xs text-[#5F6368] mt-1">{{ a.subject?.name }} | Total Marks: {{ a.total_marks }}</p>
                                <p class="text-xs text-[#3C4043] mt-2 line-clamp-2">{{ a.description }}</p>
                            </div>
                            <div class="mt-6 pt-4 border-t border-[#DADCE0] flex justify-between items-center">
                                <button @click="openSubmissionsModal(a)" class="rounded-full bg-[#E8F0FE] hover:bg-[#D2E3FC] px-3.5 py-1.5 text-xs font-bold text-[#1A73E8] transition-colors inline-flex items-center gap-1.5 shadow-2xs">
                                    <i class="fa-solid fa-users-viewfinder"></i>
                                    <span>Review Submissions ({{ a.submissions?.length || 0 }})</span>
                                </button>
                                <span class="text-2xs font-bold text-[#5F6368]">{{ a.section?.school_class?.name || 'Assigned' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ================= TAB: GRADEBOOK / EXAM MARKS ENTRY ================= -->
                <div v-if="activeTab === 'gradebook'" class="space-y-6 animate-fadeIn">
                    <div class="google-card rounded-2xl bg-white p-6 border border-[#DADCE0] shadow-2xs space-y-6">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-[#DADCE0] pb-6">
                            <div>
                                <h3 class="text-base font-bold text-[#202124]">Examination Gradebook & Marks Matrix</h3>
                                <p class="text-xs text-[#5F6368]">Input student theory, practical, and viva marks. Grades and GPAs calculate automatically.</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-3">
                                <select v-model="selectedExamId" class="rounded-xl border border-[#DADCE0] bg-[#F8F9FA] px-3.5 py-2 text-xs font-semibold text-[#202124] focus:outline-none focus:border-[#8430CE]">
                                    <option value="" disabled>Select Term Exam</option>
                                    <option v-for="e in exams" :key="e.id" :value="e.id">{{ e.name }}</option>
                                    <option v-if="!exams || exams.length === 0" value="1">Mid-Term Examination 2026</option>
                                </select>
                                <select v-model="selectedSubjectId" class="rounded-xl border border-[#DADCE0] bg-[#F8F9FA] px-3.5 py-2 text-xs font-semibold text-[#202124] focus:outline-none focus:border-[#8430CE]">
                                    <option value="" disabled>Select Subject</option>
                                    <option v-for="sub in subjects" :key="sub.id" :value="sub.id">{{ sub.name }} ({{ sub.school_class?.name || 'Class' }})</option>
                                    <option v-if="!subjects || subjects.length === 0" value="1">Mathematics</option>
                                </select>
                                <button @click="submitMarks" class="rounded-full bg-[#8430CE] px-6 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-[#6e23B0] transition-all inline-flex items-center gap-1.5">
                                    <i class="fa-solid fa-floppy-disk"></i>
                                    <span>Save Marks to Gradebook</span>
                                </button>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-sm">
                                <thead>
                                    <tr class="border-b border-[#DADCE0] bg-[#F8F9FA] text-2xs font-bold uppercase tracking-wider text-[#5F6368]">
                                        <th class="py-3 px-4">Student Name & Roll</th>
                                        <th class="py-3 px-4 w-32">Theory (/100)</th>
                                        <th class="py-3 px-4 w-32">Practical (/50)</th>
                                        <th class="py-3 px-4 w-32">Viva (/20)</th>
                                        <th class="py-3 px-4 text-center">Total</th>
                                        <th class="py-3 px-4 text-center">Grade</th>
                                        <th class="py-3 px-4 text-center">GPA</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#E8EAED]">
                                    <tr v-if="!students || students.length === 0">
                                        <td colspan="7" class="py-12 text-center text-sm text-[#5F6368]">No enrolled active students found in this section to grade.</td>
                                    </tr>
                                    <tr v-for="std in students" :key="std.id" class="hover:bg-[#F8F9FA]/60">
                                        <td class="py-3 px-4">
                                            <div class="font-bold text-[#202124]">{{ std.user?.name || 'Student Name' }}</div>
                                            <div class="text-2xs text-[#5F6368]">Roll: #{{ std.roll_number || std.id }} | {{ std.current_class?.name || std.currentClass?.name }}</div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <input 
                                                type="number" 
                                                min="0" max="100" 
                                                :value="marksForm.marks[std.id]?.theory ?? 75"
                                                @input="e => { if (!marksForm.marks[std.id]) marksForm.marks[std.id] = { theory: 0, practical: 0, viva: 0 }; marksForm.marks[std.id].theory = parseFloat(e.target.value) || 0; }"
                                                class="w-full rounded-lg border border-[#DADCE0] px-2.5 py-1.5 text-xs font-semibold focus:border-[#8430CE] focus:outline-none"
                                            />
                                        </td>
                                        <td class="py-3 px-4">
                                            <input 
                                                type="number" 
                                                min="0" max="50" 
                                                :value="marksForm.marks[std.id]?.practical ?? 40"
                                                @input="e => { if (!marksForm.marks[std.id]) marksForm.marks[std.id] = { theory: 0, practical: 0, viva: 0 }; marksForm.marks[std.id].practical = parseFloat(e.target.value) || 0; }"
                                                class="w-full rounded-lg border border-[#DADCE0] px-2.5 py-1.5 text-xs font-semibold focus:border-[#8430CE] focus:outline-none"
                                            />
                                        </td>
                                        <td class="py-3 px-4">
                                            <input 
                                                type="number" 
                                                min="0" max="20" 
                                                :value="marksForm.marks[std.id]?.viva ?? 18"
                                                @input="e => { if (!marksForm.marks[std.id]) marksForm.marks[std.id] = { theory: 0, practical: 0, viva: 0 }; marksForm.marks[std.id].viva = parseFloat(e.target.value) || 0; }"
                                                class="w-full rounded-lg border border-[#DADCE0] px-2.5 py-1.5 text-xs font-semibold focus:border-[#8430CE] focus:outline-none"
                                            />
                                        </td>
                                        <td class="py-3 px-4 text-center font-extrabold text-[#202124]">
                                            {{ ((marksForm.marks[std.id]?.theory ?? 75) + (marksForm.marks[std.id]?.practical ?? 40) + (marksForm.marks[std.id]?.viva ?? 18)).toFixed(0) }}
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <span class="rounded-md bg-[#E6F4EA] px-2.5 py-1 text-xs font-extrabold text-[#137333]">
                                                {{ ((marksForm.marks[std.id]?.theory ?? 75) + (marksForm.marks[std.id]?.practical ?? 40) + (marksForm.marks[std.id]?.viva ?? 18)) >= 130 ? 'A+' : ((marksForm.marks[std.id]?.theory ?? 75) + (marksForm.marks[std.id]?.practical ?? 40) + (marksForm.marks[std.id]?.viva ?? 18)) >= 110 ? 'A' : 'B' }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-center font-bold text-[#8430CE]">
                                            {{ ((marksForm.marks[std.id]?.theory ?? 75) + (marksForm.marks[std.id]?.practical ?? 40) + (marksForm.marks[std.id]?.viva ?? 18)) >= 130 ? '4.00' : ((marksForm.marks[std.id]?.theory ?? 75) + (marksForm.marks[std.id]?.practical ?? 40) + (marksForm.marks[std.id]?.viva ?? 18)) >= 110 ? '3.70' : '3.00' }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- ================= TAB: OBSERVATIONS & PARENT NOTES ================= -->
                <div v-if="activeTab === 'observations'" class="space-y-6 animate-fadeIn">
                    <div class="google-card overflow-hidden rounded-2xl border border-[#DADCE0] bg-white shadow-2xs">
                        <div class="p-6 border-b border-[#DADCE0] flex justify-between items-center">
                            <div>
                                <h3 class="text-base font-bold text-[#202124]">Log Behavioral & Academic Observations</h3>
                                <p class="text-xs text-[#5F6368]">Notes logged here instantly sync to the Parent Guardian portal feed.</p>
                            </div>
                            <button @click="showObservationModal = true" class="rounded-full bg-[#137333] px-5 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-[#0d5324] transition-all">
                                + Log Observation Note
                            </button>
                        </div>

                        <table class="w-full text-left border-collapse text-sm">
                            <thead>
                                <tr class="border-b border-[#DADCE0] bg-[#F8F9FA] text-xs font-bold uppercase tracking-wider text-[#5F6368]">
                                    <th class="py-4 px-6">Student</th>
                                    <th class="py-4 px-6">Category</th>
                                    <th class="py-4 px-6">Observation Title & Details</th>
                                    <th class="py-4 px-6 text-center">Parent Visible</th>
                                    <th class="py-4 px-6 text-right">Date</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#E8EAED]">
                                <tr v-if="!observations || observations.length === 0">
                                    <td colspan="5" class="py-12 text-center text-sm text-[#5F6368]">No student observations logged yet. Click "+ Log Observation Note" to add one!</td>
                                </tr>
                                <tr v-for="obs in observations" :key="obs.id" class="hover:bg-[#F8F9FA]/80">
                                    <td class="py-4 px-6 font-bold text-[#202124]">{{ obs.student?.user?.name || 'Student' }}</td>
                                    <td class="py-4 px-6">
                                        <span :class="obs.type === 'commendation' ? 'bg-[#E6F4EA] text-[#137333]' : obs.type === 'warning' ? 'bg-[#FCE8E6] text-[#C5221F]' : 'bg-[#FEF7E0] text-[#B06000]'" class="rounded-lg px-2.5 py-1 text-xs font-extrabold uppercase tracking-wider">
                                            {{ obs.type }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-6">
                                        <div class="font-bold text-[#202124]">{{ obs.title }}</div>
                                        <div class="text-xs text-[#5F6368] mt-0.5 line-clamp-1">{{ obs.description }}</div>
                                    </td>
                                    <td class="py-4 px-6 text-center">
                                        <span v-if="obs.parent_visible" class="rounded-full bg-[#E8F0FE] text-[#1A73E8] px-2.5 py-0.5 text-2xs font-bold inline-flex items-center gap-1"><i class="fa-solid fa-check"></i> <span>Visible</span></span>
                                        <span v-else class="rounded-full bg-[#F1F3F4] text-[#5F6368] px-2.5 py-0.5 text-2xs font-bold">Internal Only</span>
                                    </td>
                                    <td class="py-4 px-6 text-right text-xs text-[#5F6368]">{{ new Date(obs.created_at).toLocaleDateString() }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ================= TAB: LEAVE APPLICATIONS ================= -->
                <div v-if="activeTab === 'leaves'" class="space-y-6 animate-fadeIn">
                    <div class="google-card overflow-hidden rounded-2xl border border-[#DADCE0] bg-white shadow-2xs">
                        <div class="p-6 border-b border-[#DADCE0] flex justify-between items-center">
                            <div>
                                <h3 class="text-base font-bold text-[#202124]">Staff Leave Applications & Substitute Tracking</h3>
                                <p class="text-xs text-[#5F6368]">Submit applications for Principal approval. Approved leaves trigger automatic substitute assignment.</p>
                            </div>
                            <button @click="showLeaveModal = true" class="rounded-full bg-[#B06000] px-5 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-[#8c4c00] transition-all">
                                + Submit Leave Request
                            </button>
                        </div>

                        <table class="w-full text-left border-collapse text-sm">
                            <thead>
                                <tr class="border-b border-[#DADCE0] bg-[#F8F9FA] text-xs font-bold uppercase tracking-wider text-[#5F6368]">
                                    <th class="py-4 px-6">Leave Type</th>
                                    <th class="py-4 px-6">Duration Range</th>
                                    <th class="py-4 px-6">Reason / Remarks</th>
                                    <th class="py-4 px-6 text-center">Status</th>
                                    <th class="py-4 px-6 text-right">Submitted On</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#E8EAED]">
                                <tr v-if="!myLeaves || myLeaves.length === 0">
                                    <td colspan="5" class="py-12 text-center text-sm text-[#5F6368]">No leave applications submitted yet.</td>
                                </tr>
                                <tr v-for="l in myLeaves" :key="l.id" class="hover:bg-[#F8F9FA]/80">
                                    <td class="py-4 px-6 font-bold text-[#202124] capitalize">{{ l.leave_type }} Leave</td>
                                    <td class="py-4 px-6 text-xs font-semibold text-[#3C4043]">{{ l.start_date }} → {{ l.end_date }}</td>
                                    <td class="py-4 px-6 text-xs text-[#5F6368] max-w-xs truncate">{{ l.reason }}</td>
                                    <td class="py-4 px-6 text-center">
                                        <span :class="l.status === 'approved' ? 'bg-[#E6F4EA] text-[#137333]' : l.status === 'rejected' ? 'bg-[#FCE8E6] text-[#C5221F]' : 'bg-[#FEF7E0] text-[#B06000]'" class="rounded-full px-3 py-1 text-xs font-bold uppercase tracking-wider">
                                            {{ l.status }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 text-right text-xs text-[#5F6368]">{{ new Date(l.created_at || Date.now()).toLocaleDateString() }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Student Leave Requests for This Class Section -->
                    <div class="google-card rounded-3xl border border-[#DADCE0] bg-white p-6 shadow-xs space-y-4">
                        <div class="flex items-center justify-between border-b border-[#DADCE0] pb-3">
                            <div>
                                <h3 class="text-base font-bold text-[#202124] flex items-center gap-2"><i class="fa-solid fa-child-reaching text-[#8430CE]"></i> <span>Student Leave Requests (Your Sections)</span></h3>
                                <p class="text-xs text-[#5F6368] mt-0.5">Leave requests submitted by students and parents in your sections. Approve or reject below.</p>
                            </div>
                            <span class="bg-[#F3E8FD] text-[#8430CE] text-xs font-bold px-3 py-1 rounded-full">{{ student_leaves?.filter(sl => sl.status === 'pending').length || 0 }} Pending</span>
                        </div>
                        <div v-if="!student_leaves || student_leaves.length === 0" class="py-10 text-center text-[#5F6368] space-y-2">
                            <div class="text-3xl text-[#8430CE]"><i class="fa-solid fa-child"></i></div>
                            <div class="font-bold text-xs">No Student Leave Requests For Your Sections</div>
                        </div>
                        <div v-else class="space-y-3 max-h-[400px] overflow-y-auto pr-1">
                            <div v-for="sl in student_leaves" :key="sl.id" class="rounded-2xl border border-[#DADCE0] p-4 bg-[#F8F9FA]/50 hover:bg-white transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-2xs">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="font-extrabold text-sm text-[#202124]">{{ sl.student?.user?.name || 'Student' }}</span>
                                        <span class="text-2xs font-bold bg-[#E8F0FE] text-[#1A73E8] px-2 py-0.5 rounded-full">{{ sl.student?.current_class?.name || '' }} {{ sl.student?.current_section?.name ? '— ' + sl.student.current_section.name : '' }}</span>
                                        <span :class="[
                                            sl.status === 'approved' ? 'bg-[#E6F4EA] text-[#137333]' : (sl.status === 'rejected' ? 'bg-[#FCE8E6] text-[#C5221F]' : 'bg-[#FEF7E0] text-[#B06000]'),
                                            'px-2 py-0.5 rounded-full text-2xs font-extrabold uppercase'
                                        ]">{{ sl.status }}</span>
                                    </div>
                                    <div class="text-xs text-[#202124]/80 font-semibold"><i class="fa-regular fa-calendar text-[#8430CE] mr-1"></i> {{ sl.start_date }} → {{ sl.end_date }}</div>
                                    <div class="text-2xs text-[#5F6368] italic">"{{ sl.reason }}"</div>
                                    <div v-if="sl.admin_remarks" class="text-2xs text-[#1A73E8] font-semibold">Note: {{ sl.admin_remarks }}</div>
                                </div>
                                <div v-if="sl.status === 'pending'" class="flex items-center gap-2 flex-shrink-0">
                                    <button @click="approveStudentLeave(sl.id, 'approved')" class="bg-[#E6F4EA] hover:bg-[#CEEAD6] text-[#137333] px-3.5 py-1.5 rounded-xl font-extrabold text-2xs transition-colors shadow-2xs inline-flex items-center gap-1">
                                        <i class="fa-solid fa-check"></i> <span>Approve</span>
                                    </button>
                                    <button @click="approveStudentLeave(sl.id, 'rejected')" class="bg-[#FCE8E6] hover:bg-[#FAD2CF] text-[#C5221F] px-3.5 py-1.5 rounded-xl font-extrabold text-2xs transition-colors shadow-2xs inline-flex items-center gap-1">
                                        <i class="fa-solid fa-xmark"></i> <span>Reject</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ================= TAB: SPORTS & ACTIVITIES ================= -->
                <div v-if="activeTab === 'sports'" class="space-y-6 animate-fadeIn p-4 sm:p-6 lg:p-8">
                    <div class="google-card rounded-3xl border border-[#DADCE0] bg-white p-6 shadow-xs">
                        <div class="flex items-center justify-between border-b border-[#DADCE0] pb-4 mb-4">
                            <div>
                                <h3 class="text-lg font-extrabold text-[#202124] flex items-center gap-2">
                                    <i class="fa-solid fa-trophy text-[#F59E0B]"></i>
                                    <span>Sports Teams & Extracurricular Clubs Roster</span>
                                </h3>
                                <p class="text-xs text-[#5F6368] mt-0.5">Extracurricular activities and sports teams managed or supervised across the school.</p>
                            </div>
                            <button @click="showTeacherSportsModal = true" class="rounded-full bg-[#F59E0B] hover:bg-[#D97706] text-white px-5 py-2 text-xs font-bold transition-all shadow-sm inline-flex items-center gap-1.5">
                                <i class="fa-solid fa-plus"></i>
                                <span>Create New Club / Team</span>
                            </button>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-sm">
                                <thead>
                                    <tr class="border-b border-[#DADCE0] bg-[#F8F9FA] text-2xs font-bold uppercase tracking-wider text-[#5F6368]">
                                        <th class="py-3 px-4">Activity / Team</th>
                                        <th class="py-3 px-4">Category</th>
                                        <th class="py-3 px-4">Schedule</th>
                                        <th class="py-3 px-4">Venue</th>
                                        <th class="py-3 px-4">Capacity</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#E8EAED]">
                                    <tr v-if="!sports_activities || sports_activities.length === 0">
                                        <td colspan="5" class="py-12 text-center text-sm text-[#5F6368]">No sports teams or clubs created yet.</td>
                                    </tr>
                                    <tr v-for="act in sports_activities" :key="act.id" class="hover:bg-[#F8F9FA]/60">
                                        <td class="py-3 px-4 font-bold text-[#202124]">{{ act.name }}</td>
                                        <td class="py-3 px-4">
                                            <span class="px-2.5 py-1 rounded-full text-2xs font-bold uppercase tracking-wider bg-[#FEF7E0] text-[#B06000]">{{ act.category || 'Sports' }}</span>
                                        </td>
                                        <td class="py-3 px-4 text-xs font-medium text-[#3C4043]">{{ act.schedule || 'N/A' }}</td>
                                        <td class="py-3 px-4 text-xs font-medium text-[#3C4043]">{{ act.venue || 'N/A' }}</td>
                                        <td class="py-3 px-4 font-semibold text-[#202124]">{{ act.max_participants ? `${act.max_participants} Max` : 'Unlimited' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- ================= TAB: DISCIPLINARY PENALTIES ================= -->
                <div v-if="activeTab === 'penalties'" class="space-y-6 animate-fadeIn p-4 sm:p-6 lg:p-8">
                    <div class="google-card rounded-3xl border border-[#DADCE0] bg-white p-6 shadow-xs">
                        <div class="flex items-center justify-between border-b border-[#DADCE0] pb-4 mb-4">
                            <div>
                                <h3 class="text-lg font-extrabold text-[#202124] flex items-center gap-2">
                                    <i class="fa-solid fa-gavel text-[#E11D48]"></i>
                                    <span>Student Disciplinary Records & Penalties</span>
                                </h3>
                                <p class="text-xs text-[#5F6368] mt-0.5">Issue penalties or log rule violations for accountability and parent review.</p>
                            </div>
                            <button @click="showTeacherPenaltyModal = true" class="rounded-full bg-[#E11D48] hover:bg-[#BE123C] text-white px-5 py-2 text-xs font-bold transition-all shadow-sm inline-flex items-center gap-1.5">
                                <i class="fa-solid fa-plus"></i>
                                <span>Issue Student Penalty</span>
                            </button>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-sm">
                                <thead>
                                    <tr class="border-b border-[#DADCE0] bg-[#F8F9FA] text-2xs font-bold uppercase tracking-wider text-[#5F6368]">
                                        <th class="py-3 px-4">Student</th>
                                        <th class="py-3 px-4">Violation Type</th>
                                        <th class="py-3 px-4">Amount</th>
                                        <th class="py-3 px-4">Reason / Details</th>
                                        <th class="py-3 px-4">Status</th>
                                        <th class="py-3 px-4 text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#E8EAED]">
                                    <tr v-if="!penalties || penalties.length === 0">
                                        <td colspan="6" class="py-12 text-center text-sm text-[#5F6368]">No disciplinary penalties issued yet.</td>
                                    </tr>
                                    <tr v-for="p in penalties" :key="p.id" class="hover:bg-[#F8F9FA]/60">
                                        <td class="py-3 px-4">
                                            <div class="font-bold text-[#202124]">{{ p.student?.user?.name || 'Student' }}</div>
                                            <div class="text-2xs text-[#5F6368]">Roll: #{{ p.student?.roll_number || 'N/A' }}</div>
                                        </td>
                                        <td class="py-3 px-4 font-bold text-[#202124]">{{ p.penalty_type }}</td>
                                        <td class="py-3 px-4 font-extrabold text-[#E11D48]">PKR {{ p.amount || 0 }}</td>
                                        <td class="py-3 px-4 text-xs text-[#3C4043] max-w-xs truncate">{{ p.reason }}</td>
                                        <td class="py-3 px-4">
                                            <span :class="p.status === 'Paid' ? 'bg-[#E6F4EA] text-[#137333]' : p.status === 'Waived' ? 'bg-[#F1F3F4] text-[#5F6368]' : 'bg-[#FCE8E6] text-[#C5221F]'" class="px-2.5 py-1 rounded-full text-2xs font-bold uppercase tracking-wider inline-block">
                                                {{ p.status || 'Pending' }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-right">
                                            <button v-if="p.status === 'Pending' || p.status === 'Issued'" @click="router.post(`/teacher/penalties/${p.id}/status`, { status: 'Waived' })" class="rounded-full bg-[#F1F3F4] hover:bg-[#E8EAED] px-3 py-1 text-2xs font-bold text-[#5F6368] transition-all">
                                                Waive
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- ================= TAB: TEACHER SETTINGS ================= -->
                <!-- ================= TAB: NOTICE BOARD ================= -->
                <div v-if="activeTab === 'notices'" class="space-y-6 animate-fadeIn">
                    <div class="google-card rounded-2xl border border-[#DADCE0] bg-white p-6 shadow-2xs">
                        <div class="border-b border-[#DADCE0] pb-4 mb-4">
                            <h3 class="text-base font-bold text-[#202124] flex items-center gap-2">
                                <i class="fa-solid fa-bullhorn text-[#8430CE]"></i>
                                <span>Official School Notices & Bulletins</span>
                            </h3>
                            <p class="text-xs text-[#5F6368]">Announcements and updates broadcasted by the administration.</p>
                        </div>

                        <div v-if="!notices || notices.length === 0" class="py-12 text-center text-sm text-[#5F6368]">
                            No notices published for staff at this time.
                        </div>
                        <div v-else class="space-y-4">
                            <div v-for="n in notices" :key="n.id" class="rounded-xl border border-[#DADCE0] bg-[#F8F9FA] p-5 space-y-2">
                                <div class="flex justify-between items-start gap-4">
                                    <h4 class="font-extrabold text-sm text-[#202124]">{{ n.title }}</h4>
                                    <span class="rounded-full bg-white border border-[#DADCE0] px-2.5 py-0.5 text-2xs font-semibold text-[#5F6368] whitespace-nowrap">
                                        {{ n.publish_date || new Date(n.created_at).toLocaleDateString() }}
                                    </span>
                                </div>
                                <p class="text-xs text-[#3C4043] leading-relaxed whitespace-pre-wrap">{{ n.content }}</p>
                                <div class="pt-2 flex justify-between items-center text-[10px] text-[#5F6368] border-t border-[#E8EAED]">
                                    <span>Target Audience: <span class="capitalize font-bold">{{ n.target_audience }}</span></span>
                                    <span>Posted By: <strong>{{ n.creator?.name || 'Administrator' }}</strong></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="activeTab === 'settings'" class="space-y-6 animate-fadeIn p-4 sm:p-6 lg:p-8">
                    <div class="google-card rounded-3xl border border-[#DADCE0] bg-white p-6 sm:p-8 shadow-xs">
                        <div class="border-b border-[#DADCE0] pb-4 mb-6">
                            <h3 class="text-lg font-extrabold text-[#202124] flex items-center gap-2">
                                <i class="fa-solid fa-user-gear text-[#8430CE]"></i>
                                <span>Personal Account Settings</span>
                            </h3>
                            <p class="text-xs text-[#5F6368] mt-0.5">Manage your own contact info and change account password securely.</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <!-- Contact Details Form -->
                            <form @submit.prevent="submitTeacherProfileChange" class="space-y-4">
                                <h4 class="text-xs font-bold uppercase tracking-wider text-[#5F6368]">Contact Details</h4>
                                <div>
                                    <label class="block text-2xs font-bold text-[#202124] mb-1">Email Address *</label>
                                    <input type="email" v-model="teacherProfileForm.email" required class="w-full rounded-xl border border-[#DADCE0] px-3.5 py-2 text-xs focus:ring-2 focus:ring-[#8430CE]" />
                                    <span v-if="teacherProfileForm.errors.email" class="text-2xs text-[#C5221F] font-bold block mt-1">{{ teacherProfileForm.errors.email }}</span>
                                </div>
                                <div>
                                    <label class="block text-2xs font-bold text-[#202124] mb-1">Phone Number</label>
                                    <input type="text" v-model="teacherProfileForm.phone" class="w-full rounded-xl border border-[#DADCE0] px-3.5 py-2 text-xs focus:ring-2 focus:ring-[#8430CE]" />
                                    <span v-if="teacherProfileForm.errors.phone" class="text-2xs text-[#C5221F] font-bold block mt-1">{{ teacherProfileForm.errors.phone }}</span>
                                </div>
                                <div class="pt-2">
                                    <button type="submit" :disabled="teacherProfileForm.processing" class="rounded-full bg-[#8430CE] px-5 py-2 text-xs font-bold text-white shadow-2xs hover:bg-[#6e23B0] transition-all cursor-pointer">
                                        {{ teacherProfileForm.processing ? 'Saving...' : 'Update Details' }}
                                    </button>
                                </div>
                            </form>

                            <!-- Password Update Form -->
                            <form @submit.prevent="submitTeacherPasswordChange" class="space-y-4 border-t md:border-t-0 md:border-l border-[#DADCE0] pt-6 md:pt-0 md:pl-8">
                                <h4 class="text-xs font-bold uppercase tracking-wider text-[#5F6368]">Change Password</h4>
                                <div>
                                    <label class="block text-2xs font-bold text-[#202124] mb-1">Current Password *</label>
                                    <input type="password" v-model="teacherPasswordForm.current_password" required class="w-full rounded-xl border border-[#DADCE0] px-3.5 py-2 text-xs focus:ring-2 focus:ring-[#8430CE]" />
                                    <span v-if="teacherPasswordForm.errors.current_password" class="text-2xs text-[#C5221F] font-bold block mt-1">{{ teacherPasswordForm.errors.current_password }}</span>
                                </div>
                                <div>
                                    <label class="block text-2xs font-bold text-[#202124] mb-1">New Password *</label>
                                    <input type="password" v-model="teacherPasswordForm.new_password" required class="w-full rounded-xl border border-[#DADCE0] px-3.5 py-2 text-xs focus:ring-2 focus:ring-[#8430CE]" />
                                    <span v-if="teacherPasswordForm.errors.new_password" class="text-2xs text-[#C5221F] font-bold block mt-1">{{ teacherPasswordForm.errors.new_password }}</span>
                                </div>
                                <div>
                                    <label class="block text-2xs font-bold text-[#202124] mb-1">Confirm New Password *</label>
                                    <input type="password" v-model="teacherPasswordForm.new_password_confirmation" required class="w-full rounded-xl border border-[#DADCE0] px-3.5 py-2 text-xs focus:ring-2 focus:ring-[#8430CE]" />
                                </div>
                                <div class="pt-2">
                                    <button type="submit" :disabled="teacherPasswordForm.processing" class="rounded-full bg-[#8430CE] px-5 py-2 text-xs font-bold text-white shadow-2xs hover:bg-[#6e23B0] transition-all cursor-pointer">
                                        {{ teacherPasswordForm.processing ? 'Changing...' : 'Change Password' }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

        <!-- Upload Material Modal -->
        <div v-if="showMaterialModal" class="fixed inset-0 z-50 flex justify-end bg-black/40 backdrop-blur-xs overflow-hidden">
            <div class="absolute inset-0" @click="showMaterialModal = false"></div>
            <div class="bg-white w-full max-w-lg shadow-2xl flex flex-col h-full z-10 animate-slideLeft border-l border-[#DADCE0]">
                <div class="p-6 sm:p-8 flex-1 overflow-y-auto space-y-6">
                    <div class="flex justify-between items-center border-b border-[#DADCE0] pb-3">
                        <h3 class="font-bold text-[#202124]">Upload Course Material</h3>
                        <button @click="showMaterialModal = false" class="h-8 w-8 rounded-full hover:bg-gray-100 font-bold text-gray-500 flex items-center justify-center cursor-pointer"><i class="fa-solid fa-xmark text-lg"></i></button>
                    </div>
                    <form @submit.prevent="submitMaterial" class="space-y-4 text-sm">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-[#5F6368] mb-1">Subject</label>
                            <select v-model="materialForm.subject_id" required class="w-full rounded-xl bg-white border border-[#DADCE0] px-3.5 py-2.5 focus:border-[#8430CE] focus:outline-none">
                                <option v-for="tc in todayClasses" :key="tc.subject_id" :value="tc.subject_id">{{ tc.subject?.name }}</option>
                                <option value="1">General Science / CS</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-[#5F6368] mb-1">Title</label>
                            <input v-model="materialForm.title" type="text" placeholder="Study Material Title" required class="w-full rounded-xl bg-white border border-[#DADCE0] px-3.5 py-2.5 focus:border-[#8430CE] focus:outline-none" />
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-[#5F6368] mb-1">File Type</label>
                                <select v-model="materialForm.file_type" required class="w-full rounded-xl bg-white border border-[#DADCE0] px-3.5 py-2.5 focus:border-[#8430CE] focus:outline-none">
                                    <option value="pdf">PDF Document</option>
                                    <option value="ppt">PowerPoint Slides / Word</option>
                                    <option value="video">Recorded Lecture / Video</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-[#5F6368] mb-1">External Link (Optional)</label>
                                <input v-model="materialForm.file_url" type="text" placeholder="https://cdn..." class="w-full rounded-xl bg-white border border-[#DADCE0] px-3.5 py-2.5 focus:border-[#8430CE] focus:outline-none" />
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-[#5F6368] mb-1">Upload Document File (PDF, Word, PPT, Image)</label>
                            <input type="file" @change="e => materialForm.file_upload = e.target.files[0]" class="w-full rounded-xl bg-[#F8F9FA] border border-[#DADCE0] px-3.5 py-2 text-xs focus:border-[#8430CE] focus:outline-none file:mr-3 file:py-1 file:px-3 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-[#E8F0FE] file:text-[#1A73E8] hover:file:bg-[#D2E3FC]" />
                            <p class="text-2xs text-[#5F6368] mt-1">Select a document file from your computer or enter an external link above.</p>
                        </div>
                        <div class="pt-3 flex justify-end space-x-3">
                            <button type="button" @click="showMaterialModal = false" class="rounded-full px-5 py-2.5 text-xs font-bold text-[#5F6368] hover:bg-[#F8F9FA]">Cancel</button>
                            <button type="submit" class="rounded-full bg-[#8430CE] px-6 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-[#6e23B0]">Upload Material</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Create Assignment Modal -->
        <div v-if="showAssignmentModal" class="fixed inset-0 z-50 flex justify-end bg-black/40 backdrop-blur-xs overflow-hidden">
            <div class="absolute inset-0" @click="showAssignmentModal = false"></div>
            <div class="bg-white w-full max-w-lg shadow-2xl flex flex-col h-full z-10 animate-slideLeft border-l border-[#DADCE0]">
                <div class="p-6 sm:p-8 flex-1 overflow-y-auto space-y-6">
                    <div class="flex justify-between items-center border-b border-[#DADCE0] pb-3">
                        <h3 class="font-bold text-[#202124]">Create Homework Assignment</h3>
                        <button @click="showAssignmentModal = false" class="h-8 w-8 rounded-full hover:bg-gray-100 font-bold text-gray-500 flex items-center justify-center cursor-pointer"><i class="fa-solid fa-xmark text-lg"></i></button>
                    </div>
                    <form @submit.prevent="submitAssignment" class="space-y-4 text-sm">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-[#5F6368] mb-1">Subject</label>
                                <select v-model="assignmentForm.subject_id" required class="w-full rounded-xl bg-white border border-[#DADCE0] px-3.5 py-2.5 focus:border-[#8430CE] focus:outline-none">
                                    <option v-for="sub in subjects" :key="sub.id" :value="sub.id">{{ sub.name }}</option>
                                    <option v-if="!subjects || subjects.length === 0" value="1">Mathematics</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-[#5F6368] mb-1">Section</label>
                                <select v-model="assignmentForm.section_id" required class="w-full rounded-xl bg-white border border-[#DADCE0] px-3.5 py-2.5 focus:border-[#8430CE] focus:outline-none">
                                    <option v-for="sec in sections" :key="sec.id" :value="sec.id">{{ sec.school_class?.name }} - {{ sec.name }}</option>
                                    <option v-if="!sections || sections.length === 0" value="1">Class 10 - Section A</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-[#5F6368] mb-1">Title</label>
                            <input v-model="assignmentForm.title" type="text" placeholder="Assignment Title" required class="w-full rounded-xl bg-white border border-[#DADCE0] px-3.5 py-2.5 focus:border-[#8430CE] focus:outline-none" />
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-[#5F6368] mb-1">Instructions / Description</label>
                            <textarea v-model="assignmentForm.description" rows="3" placeholder="Provide detailed instructions..." required class="w-full rounded-xl bg-white border border-[#DADCE0] px-3.5 py-2.5 focus:border-[#8430CE] focus:outline-none"></textarea>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-[#5F6368] mb-1">Due Date</label>
                                <input v-model="assignmentForm.due_date" type="date" required class="w-full rounded-xl bg-white border border-[#DADCE0] px-3.5 py-2.5 focus:border-[#8430CE] focus:outline-none" />
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-[#5F6368] mb-1">Total Marks</label>
                                <input v-model="assignmentForm.total_marks" type="number" min="10" max="500" required class="w-full rounded-xl bg-white border border-[#DADCE0] px-3.5 py-2.5 focus:border-[#8430CE] focus:outline-none" />
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-[#5F6368] mb-1">Attach Worksheet Document (PDF, Word, Zip, Image)</label>
                            <input type="file" @change="e => assignmentForm.attachment_file = e.target.files[0]" class="w-full rounded-xl bg-[#F8F9FA] border border-[#DADCE0] px-3.5 py-2 text-xs focus:border-[#8430CE] focus:outline-none file:mr-3 file:py-1 file:px-3 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-[#E8F0FE] file:text-[#1A73E8] hover:file:bg-[#D2E3FC]" />
                        </div>
                        <div class="space-y-2 bg-[#F8F9FA] p-3 rounded-2xl border border-[#DADCE0]">
                            <div class="flex items-center justify-between">
                                <label class="block text-xs font-extrabold uppercase tracking-wider text-[#5F6368]">
                                    <i class="fa-solid fa-link text-[#8430CE] mr-1"></i>
                                    External Resource Links & Reference Tabs
                                </label>
                                <button type="button" @click="addExternalLink" class="rounded-full bg-[#E8F0FE] hover:bg-[#D2E3FC] text-[#1A73E8] px-3 py-1 text-xs font-bold inline-flex items-center gap-1 shadow-2xs transition-all">
                                    <i class="fa-solid fa-plus"></i>
                                    <span>Add Link</span>
                                </button>
                            </div>
                            <div v-for="(link, index) in assignmentForm.external_links" :key="index" class="flex items-center gap-2 pt-1">
                                <input v-model="link.title" type="text" placeholder="Title" class="w-1/3 rounded-xl bg-white border border-[#DADCE0] px-3 py-2 text-xs font-semibold focus:border-[#8430CE] focus:outline-none" />
                                <input v-model="link.url" type="url" placeholder="https://..." class="flex-1 rounded-xl bg-white border border-[#DADCE0] px-3 py-2 text-xs font-mono focus:border-[#8430CE] focus:outline-none" />
                                <button type="button" @click="removeExternalLink(index)" class="p-2 rounded-xl text-[#5F6368] hover:text-[#C5221F] hover:bg-[#FCE8E6] transition-all" title="Remove link">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2 pt-1">
                            <input type="checkbox" id="aiEval" v-model="assignmentForm.ai_evaluation_enabled" class="rounded border-[#DADCE0] text-[#8430CE] focus:ring-[#8430CE]" />
                            <label for="aiEval" class="text-xs font-semibold text-[#202124]">Enable AI Auto-Evaluation & Instant Feedback</label>
                        </div>
                        <div class="pt-3 flex justify-end space-x-3">
                            <button type="button" @click="showAssignmentModal = false" class="rounded-full px-5 py-2.5 text-xs font-bold text-[#5F6368] hover:bg-[#F8F9FA]">Cancel</button>
                            <button type="submit" class="rounded-full bg-[#8430CE] px-6 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-[#6e23B0]">Publish Assignment</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Submissions Review Modal -->
        <div v-if="showSubmissionsModal && activeAssignmentForReview" class="fixed inset-0 z-50 flex justify-end bg-black/40 backdrop-blur-xs overflow-hidden">
            <div class="absolute inset-0" @click="showSubmissionsModal = false"></div>
            <div class="bg-white w-full max-w-4xl shadow-2xl flex flex-col h-full z-10 animate-slideLeft border-l border-[#DADCE0]">
                <div class="p-6 sm:p-8 flex-1 overflow-y-auto space-y-6 font-Outfit">
                    <div class="flex justify-between items-start border-b border-[#DADCE0] pb-4">
                        <div>
                            <span class="text-xs font-bold uppercase tracking-wider text-[#8430CE] bg-[#F3E8FD] px-2.5 py-1 rounded-md">Submissions Review</span>
                            <h3 class="text-xl font-black text-[#202124] mt-1">{{ activeAssignmentForReview.title }}</h3>
                            <p class="text-xs text-[#5F6368] mt-0.5">{{ activeAssignmentForReview.subject?.name }} • Total Marks: {{ activeAssignmentForReview.total_marks }}</p>
                        </div>
                        <button @click="showSubmissionsModal = false" class="h-8 w-8 rounded-full hover:bg-gray-100 font-bold text-gray-500 flex items-center justify-center cursor-pointer"><i class="fa-solid fa-xmark text-lg"></i></button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-sm">
                            <thead>
                                <tr class="border-b border-[#DADCE0] bg-[#F8F9FA] text-2xs font-bold uppercase tracking-wider text-[#5F6368]">
                                    <th class="py-3 px-4">Student</th>
                                    <th class="py-3 px-4">Submitted File / Text</th>
                                    <th class="py-3 px-4">Status</th>
                                    <th class="py-3 px-4">Obtained Marks</th>
                                    <th class="py-3 px-4 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#E8EAED]">
                                <tr v-if="!activeAssignmentForReview.submissions || activeAssignmentForReview.submissions.length === 0">
                                    <td colspan="5" class="py-12 text-center text-sm text-[#5F6368]">No students have submitted this assignment yet.</td>
                                </tr>
                                <tr v-for="sub in activeAssignmentForReview.submissions" :key="sub.id" class="hover:bg-[#F8F9FA]/60">
                                    <td class="py-3 px-4">
                                        <div class="font-bold text-[#202124]">{{ sub.student?.user?.name || 'Student' }}</div>
                                        <div class="text-2xs text-[#5F6368]">Roll: #{{ sub.student?.roll_number || sub.student_id }}</div>
                                    </td>
                                    <td class="py-3 px-4 max-w-xs">
                                        <div v-if="sub.file_url" class="mb-1">
                                            <a :href="sub.file_url" target="_blank" class="text-xs font-bold text-[#1A73E8] hover:underline inline-flex items-center gap-1">
                                                <i class="fa-solid fa-file-arrow-down"></i>
                                                <span class="truncate">{{ sub.submission_file_name || 'Download Attachment' }}</span>
                                            </a>
                                        </div>
                                        <p v-if="sub.submission_text" class="text-xs text-[#3C4043] line-clamp-2 bg-[#F8F9FA] p-2 rounded-lg border border-[#E8EAED]">{{ sub.submission_text }}</p>
                                        <span v-if="!sub.file_url && !sub.submission_text" class="text-xs text-[#5F6368] italic">No text or attachment provided.</span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="flex flex-col gap-1 items-start">
                                            <span :class="sub.status === 'graded' ? 'bg-[#E6F4EA] text-[#137333]' : 'bg-[#FEF7E0] text-[#B06000]'" class="px-2.5 py-0.5 rounded-full text-2xs font-bold uppercase tracking-wider inline-block">
                                                {{ sub.status || 'submitted' }}
                                            </span>
                                            <span v-if="sub.ai_suggested_marks !== null && sub.ai_suggested_marks !== undefined" class="bg-[#F3E8FD] text-[#8430CE] px-2 py-0.5 rounded-md text-2xs font-extrabold flex items-center gap-1" title="AI Suggested Grade Ready for Teacher Finalization">
                                                <i class="fa-solid fa-wand-magic-sparkles"></i>
                                                <span>AI Evaluated: {{ sub.ai_suggested_marks }} / {{ activeAssignmentForReview.total_marks }}</span>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 font-bold text-[#202124]">
                                        <span v-if="sub.obtained_marks !== null && sub.obtained_marks !== undefined" class="text-[#137333]">{{ sub.obtained_marks }} / {{ activeAssignmentForReview.total_marks }}</span>
                                        <span v-else-if="sub.ai_suggested_marks !== null && sub.ai_suggested_marks !== undefined" class="text-[#8430CE] font-bold text-xs">
                                            Pending Final Review (AI: {{ sub.ai_suggested_marks }})
                                        </span>
                                        <span v-else class="text-[#5F6368] font-normal italic">Not Graded</span>
                                    </td>
                                    <td class="py-3 px-4 text-right space-x-2">
                                        <button 
                                            v-if="activeAssignmentForReview.ai_evaluation_enabled && sub.ai_suggested_marks === null && sub.ai_suggested_marks === undefined"
                                            @click="triggerAiEvaluation(sub)"
                                            :disabled="isEvaluatingId === sub.id"
                                            class="rounded-full bg-[#E8F0FE] hover:bg-[#D2E3FC] text-[#1A73E8] px-3 py-1.5 text-xs font-bold transition-all inline-flex items-center gap-1 disabled:opacity-50"
                                        >
                                            <i class="fa-solid fa-wand-magic-sparkles" :class="isEvaluatingId === sub.id ? 'animate-spin' : ''"></i>
                                            <span>{{ isEvaluatingId === sub.id ? 'AI Analyzing...' : 'Run AI Grading' }}</span>
                                        </button>
                                        <button @click="openGradeModal(sub)" class="rounded-full bg-[#8430CE] hover:bg-[#6e23B0] px-4 py-1.5 text-xs font-bold text-white transition-all shadow-2xs">
                                            {{ sub.status === 'graded' ? 'Edit Final Grade' : sub.ai_suggested_marks !== null && sub.ai_suggested_marks !== undefined ? 'Review AI & Finalize' : 'Grade & Review' }}
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="pt-4 border-t border-[#DADCE0] flex justify-end">
                        <button @click="showSubmissionsModal = false" class="rounded-full bg-[#F1F3F4] hover:bg-[#E8EAED] px-6 py-2 text-xs font-bold text-[#3C4043]">Close Review</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grade & Teacher Review Single Submission Modal -->
        <div v-if="activeSubmissionForGrade && activeAssignmentForReview" class="fixed inset-0 z-50 flex justify-end bg-black/40 backdrop-blur-xs overflow-hidden">
            <div class="absolute inset-0" @click="activeSubmissionForGrade = null"></div>
            <div class="bg-white w-full max-w-lg shadow-2xl flex flex-col h-full z-10 animate-slideLeft border-l border-[#DADCE0]">
                <div class="p-6 sm:p-8 flex-1 overflow-y-auto space-y-6">
                    <div class="flex justify-between items-center border-b border-[#DADCE0] pb-3">
                        <div>
                            <span class="text-[10px] font-black uppercase tracking-wider text-[#8430CE] bg-[#F3E8FD] px-2 py-0.5 rounded">Teacher-Led Evaluation</span>
                            <h3 class="font-extrabold text-[#202124] text-lg mt-1">Finalize Student Grade</h3>
                            <p class="text-xs text-[#5F6368]">{{ activeSubmissionForGrade.student?.user?.name }} • Roll: #{{ activeSubmissionForGrade.student?.roll_number }}</p>
                        </div>
                        <button @click="activeSubmissionForGrade = null" class="h-8 w-8 rounded-full hover:bg-gray-100 font-bold text-gray-500 flex items-center justify-center cursor-pointer"><i class="fa-solid fa-xmark text-lg"></i></button>
                    </div>

                    <!-- Teacher AI Evaluation Review Box -->
                    <div v-if="activeSubmissionForGrade.ai_suggested_marks !== null && activeSubmissionForGrade.ai_suggested_marks !== undefined" class="rounded-2xl border border-[#D8B4F8] bg-[#F3E8FD] p-4 space-y-2 font-Outfit">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2 font-extrabold text-[#8430CE] text-xs uppercase tracking-wider">
                                <i class="fa-solid fa-wand-magic-sparkles"></i>
                                <span>AI Assistant Suggested Evaluation</span>
                            </div>
                            <span class="bg-[#8430CE] text-white text-xs font-black px-2.5 py-0.5 rounded-full">
                                Suggested: {{ activeSubmissionForGrade.ai_suggested_marks }} / {{ activeAssignmentForReview.total_marks }}
                            </span>
                        </div>
                        <p class="text-xs text-[#3C4043] leading-relaxed bg-white/80 p-3 rounded-xl border border-[#D8B4F8]/50 font-medium font-sans">
                            {{ activeSubmissionForGrade.ai_feedback || 'AI analysis completed without specific remarks.' }}
                        </p>
                        <p class="text-[11px] text-[#8430CE] italic">
                            <i class="fa-solid fa-circle-info mr-1"></i> As the instructor, review the AI suggestion above and verify or adjust the marks before publishing to the student.
                        </p>
                    </div>

                    <form @submit.prevent="submitGrade" class="space-y-4 text-sm">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-[#5F6368] mb-1">
                                Final Obtained Marks (Max: {{ activeAssignmentForReview.total_marks }}) <span class="text-red-500">*</span>
                            </label>
                            <input v-model="gradeForm.obtained_marks" type="number" step="0.5" min="0" :max="activeAssignmentForReview.total_marks" required class="w-full rounded-xl bg-white border border-[#DADCE0] px-3.5 py-2.5 focus:border-[#8430CE] focus:outline-none font-black text-xl text-[#202124]" />
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-[#5F6368] mb-1">Teacher Remarks & Final Feedback to Student</label>
                            <textarea v-model="gradeForm.teacher_remarks" rows="4" placeholder="Write final feedback on student's performance, strengths, or areas to improve..." class="w-full rounded-xl bg-white border border-[#DADCE0] px-3.5 py-2.5 focus:border-[#8430CE] focus:outline-none text-xs font-medium"></textarea>
                        </div>
                        <div class="pt-3 flex justify-end space-x-3 font-Outfit">
                            <button type="button" @click="activeSubmissionForGrade = null" class="rounded-full px-5 py-2.5 text-xs font-bold text-[#5F6368] hover:bg-[#F8F9FA]">Cancel</button>
                            <button type="submit" :disabled="gradeForm.processing" class="rounded-full bg-[#137333] hover:bg-[#0d5324] px-6 py-2.5 text-xs font-bold text-white shadow-sm disabled:opacity-50 inline-flex items-center gap-1.5">
                                <i class="fa-solid fa-check-double"></i>
                                <span v-if="gradeForm.processing">Saving...</span>
                                <span v-else>Finalize & Publish to Student</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Log Observation Modal -->
        <div v-if="showObservationModal" class="fixed inset-0 z-50 flex justify-end bg-black/40 backdrop-blur-xs overflow-hidden">
            <div class="absolute inset-0" @click="showObservationModal = false"></div>
            <div class="bg-white w-full max-w-lg shadow-2xl flex flex-col h-full z-10 animate-slideLeft border-l border-[#DADCE0]">
                <div class="p-6 sm:p-8 flex-1 overflow-y-auto space-y-6">
                    <div class="flex justify-between items-center border-b border-[#DADCE0] pb-3">
                        <h3 class="font-bold text-[#202124]">Log Student Observation Note</h3>
                        <button @click="showObservationModal = false" class="h-8 w-8 rounded-full hover:bg-gray-100 font-bold text-gray-500 flex items-center justify-center cursor-pointer"><i class="fa-solid fa-xmark text-lg"></i></button>
                    </div>
                    <form @submit.prevent="submitObservation" class="space-y-4 text-sm">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-[#5F6368] mb-1">Student</label>
                            <select v-model="observationForm.student_id" required class="w-full rounded-xl bg-white border border-[#DADCE0] px-3.5 py-2.5 focus:border-[#8430CE] focus:outline-none">
                                <option v-for="std in students" :key="std.id" :value="std.id">{{ std.user?.name }} (#{{ std.roll_number || std.id }})</option>
                                <option v-if="!students || students.length === 0" value="1">Ali Khan</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-[#5F6368] mb-1">Category Type</label>
                            <select v-model="observationForm.type" required class="w-full rounded-xl bg-white border border-[#DADCE0] px-3.5 py-2.5 focus:border-[#8430CE] focus:outline-none">
                                <option value="commendation">Commendation (Positive Praise)</option>
                                <option value="behavior">Behavioral Notice</option>
                                <option value="academic_alert">Academic Warning</option>
                                <option value="warning">Disciplinary Alert</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-[#5F6368] mb-1">Title</label>
                            <input v-model="observationForm.title" type="text" placeholder="Observation Title" required class="w-full rounded-xl bg-white border border-[#DADCE0] px-3.5 py-2.5 focus:border-[#8430CE] focus:outline-none" />
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-[#5F6368] mb-1">Detailed Observation Notes</label>
                            <textarea v-model="observationForm.description" rows="3" placeholder="Provide context and notes for parent review..." required class="w-full rounded-xl bg-white border border-[#DADCE0] px-3.5 py-2.5 focus:border-[#8430CE] focus:outline-none"></textarea>
                        </div>
                        <div class="flex items-center space-x-2 pt-1">
                            <input type="checkbox" id="parentVis" v-model="observationForm.parent_visible" class="rounded border-[#DADCE0] text-[#137333] focus:ring-[#137333]" />
                            <label for="parentVis" class="text-xs font-semibold text-[#202124]">Visible on Parent Guardian Portal Feed</label>
                        </div>
                        <div class="pt-3 flex justify-end space-x-3">
                            <button type="button" @click="showObservationModal = false" class="rounded-full px-5 py-2.5 text-xs font-bold text-[#5F6368] hover:bg-[#F8F9FA]">Cancel</button>
                            <button type="submit" class="rounded-full bg-[#137333] px-6 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-[#0d5324]">Log & Notify</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Submit Leave Application Modal -->
        <div v-if="showLeaveModal" class="fixed inset-0 z-50 flex justify-end bg-black/40 backdrop-blur-xs overflow-hidden">
            <div class="absolute inset-0" @click="showLeaveModal = false"></div>
            <div class="bg-white w-full max-w-lg shadow-2xl flex flex-col h-full z-10 animate-slideLeft border-l border-[#DADCE0]">
                <div class="p-6 sm:p-8 flex-1 overflow-y-auto space-y-6">
                    <div class="flex justify-between items-center border-b border-[#DADCE0] pb-3">
                        <h3 class="font-bold text-[#202124]">Submit Leave Request</h3>
                        <button @click="showLeaveModal = false" class="h-8 w-8 rounded-full hover:bg-gray-100 font-bold text-gray-500 flex items-center justify-center cursor-pointer"><i class="fa-solid fa-xmark text-lg"></i></button>
                    </div>
                    <form @submit.prevent="submitLeave" class="space-y-4 text-sm">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-[#5F6368] mb-1">Leave Type</label>
                            <select v-model="leaveForm.leave_type" required class="w-full rounded-xl bg-white border border-[#DADCE0] px-3.5 py-2.5 focus:border-[#8430CE] focus:outline-none">
                                <option value="casual">Casual Leave</option>
                                <option value="sick">Sick / Medical Leave</option>
                                <option value="annual">Annual Leave</option>
                                <option value="emergency">Emergency / Urgent Leave</option>
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-[#5F6368] mb-1">Start Date</label>
                                <input v-model="leaveForm.start_date" type="date" required class="w-full rounded-xl bg-white border border-[#DADCE0] px-3.5 py-2.5 focus:border-[#8430CE] focus:outline-none" />
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-[#5F6368] mb-1">End Date</label>
                                <input v-model="leaveForm.end_date" type="date" required class="w-full rounded-xl bg-white border border-[#DADCE0] px-3.5 py-2.5 focus:border-[#8430CE] focus:outline-none" />
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-[#5F6368] mb-1">Reason for Leave</label>
                            <textarea v-model="leaveForm.reason" rows="3" placeholder="Briefly explain your reason for substitute allocation..." required class="w-full rounded-xl bg-white border border-[#DADCE0] px-3.5 py-2.5 focus:border-[#8430CE] focus:outline-none"></textarea>
                        </div>
                        <div class="pt-3 flex justify-end space-x-3">
                            <button type="button" @click="showLeaveModal = false" class="rounded-full px-5 py-2.5 text-xs font-bold text-[#5F6368] hover:bg-[#F8F9FA]">Cancel</button>
                            <button type="submit" class="rounded-full bg-[#B06000] px-6 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-[#8c4c00]">Submit Application</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ================= MODAL: CREATE SPORTS ACTIVITY (TEACHER) ================= -->
        <div v-if="showTeacherSportsModal" class="fixed inset-0 z-50 flex justify-end bg-black/40 backdrop-blur-xs overflow-hidden">
            <div class="absolute inset-0" @click="showTeacherSportsModal = false"></div>
            <div class="bg-white w-full max-w-lg shadow-2xl flex flex-col h-full z-10 animate-slideLeft border-l border-[#DADCE0]">
                <div class="p-6 sm:p-8 flex-1 overflow-y-auto space-y-6">
                    <div class="flex items-center justify-between border-b border-[#DADCE0] pb-4">
                        <h3 class="text-lg font-extrabold text-[#202124] flex items-center gap-2 font-Outfit">
                            <i class="fa-solid fa-trophy text-[#F59E0B]"></i>
                            <span>Create Extracurricular / Sports Club</span>
                        </h3>
                        <button @click="showTeacherSportsModal = false" class="h-8 w-8 rounded-full hover:bg-gray-100 font-bold text-gray-500 flex items-center justify-center cursor-pointer"><i class="fa-solid fa-xmark text-lg"></i></button>
                    </div>

                    <form @submit.prevent="submitTeacherSports" class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-[#202124] mb-1">Activity / Club Name *</label>
                            <input v-model="teacherSportsForm.name" type="text" required placeholder="Activity / Team Name" class="w-full rounded-xl border border-[#DADCE0] px-3 py-2 text-xs font-bold" />
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-[#202124] mb-1">Category</label>
                                <select v-model="teacherSportsForm.category" class="w-full rounded-xl border border-[#DADCE0] px-3 py-2 text-xs font-bold">
                                    <option value="Sports">Sports</option>
                                    <option value="STEM">STEM & Robotics</option>
                                    <option value="Debate">Debate</option>
                                    <option value="Arts">Arts</option>
                                    <option value="Literature">Literature</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-[#202124] mb-1">Max Roster Capacity</label>
                                <input v-model="teacherSportsForm.max_participants" type="number" min="1" max="500" class="w-full rounded-xl border border-[#DADCE0] px-3 py-2 text-xs font-bold" />
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-[#202124] mb-1">Schedule / Day & Time</label>
                                <input v-model="teacherSportsForm.schedule" type="text" placeholder="Schedule / Day & Time" class="w-full rounded-xl border border-[#DADCE0] px-3 py-2 text-xs font-bold" />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-[#202124] mb-1">Venue / Room</label>
                                <input v-model="teacherSportsForm.venue" type="text" placeholder="Venue / Room" class="w-full rounded-xl border border-[#DADCE0] px-3 py-2 text-xs font-bold" />
                            </div>
                        </div>
                        <div class="flex justify-end space-x-3 pt-4 border-t border-[#DADCE0] font-Outfit">
                            <button type="button" @click="showTeacherSportsModal = false" class="rounded-full px-5 py-2 text-xs font-bold text-[#5F6368] hover:bg-[#F8F9FA]">Cancel</button>
                            <button type="submit" :disabled="teacherSportsForm.processing" class="rounded-full bg-[#F59E0B] px-6 py-2 text-xs font-bold text-white shadow-sm disabled:opacity-50 inline-flex items-center gap-1.5">
                                <i class="fa-solid fa-check"></i>
                                <span>Create Activity</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ================= MODAL: ISSUE PENALTY (TEACHER) ================= -->
        <div v-if="showTeacherPenaltyModal" class="fixed inset-0 z-50 flex justify-end bg-black/40 backdrop-blur-xs overflow-hidden">
            <div class="absolute inset-0" @click="showTeacherPenaltyModal = false"></div>
            <div class="bg-white w-full max-w-lg shadow-2xl flex flex-col h-full z-10 animate-slideLeft border-l border-[#DADCE0]">
                <div class="p-6 sm:p-8 flex-1 overflow-y-auto space-y-6">
                    <div class="flex items-center justify-between border-b border-[#DADCE0] pb-4">
                        <h3 class="text-lg font-extrabold text-[#202124] flex items-center gap-2 font-Outfit">
                            <i class="fa-solid fa-gavel text-[#E11D48]"></i>
                            <span>Issue Disciplinary Penalty / Fine</span>
                        </h3>
                        <button @click="showTeacherPenaltyModal = false" class="h-8 w-8 rounded-full hover:bg-gray-100 font-bold text-gray-500 flex items-center justify-center cursor-pointer"><i class="fa-solid fa-xmark text-lg"></i></button>
                    </div>

                    <form @submit.prevent="submitTeacherPenalty" class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-[#202124] mb-1">Select Student *</label>
                            <select v-model="teacherPenaltyForm.student_id" required class="w-full rounded-xl border border-[#DADCE0] px-3 py-2 text-xs font-bold">
                                <option value="" disabled>-- Select Student --</option>
                                <option v-for="s in students" :key="s.id" :value="s.id">
                                    {{ s.user?.name || 'Student' }} (Roll: {{ s.roll_number || 'N/A' }}, Class: {{ s.current_class?.name || 'N/A' }})
                                </option>
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-[#202124] mb-1">Violation Type *</label>
                                <select v-model="teacherPenaltyForm.penalty_type" required class="w-full rounded-xl border border-[#DADCE0] px-3 py-2 text-xs font-bold">
                                    <option value="Disciplinary Violation">Disciplinary Violation</option>
                                    <option value="Library Book Damage/Loss">Library Book Damage/Loss</option>
                                    <option value="Late Return Fine">Late Return Fine</option>
                                    <option value="Property Damage">Property Damage</option>
                                    <option value="Uniform / Dress Code">Uniform / Dress Code</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-[#202124] mb-1">Amount (PKR)</label>
                                <input v-model="teacherPenaltyForm.amount" type="number" min="0" step="50" class="w-full rounded-xl border border-[#DADCE0] px-3 py-2 text-xs font-bold" />
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-[#202124] mb-1">Detailed Reason / Incident Summary *</label>
                            <textarea v-model="teacherPenaltyForm.reason" rows="3" required placeholder="Describe the violation or incident details..." class="w-full rounded-xl border border-[#DADCE0] p-3 text-xs font-medium"></textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-[#202124] mb-1">Due Date</label>
                            <input v-model="teacherPenaltyForm.due_date" type="date" class="w-full rounded-xl border border-[#DADCE0] px-3 py-2 text-xs font-bold" />
                        </div>
                        <div class="flex justify-end space-x-3 pt-4 border-t border-[#DADCE0] font-Outfit">
                            <button type="button" @click="showTeacherPenaltyModal = false" class="rounded-full px-5 py-2 text-xs font-bold text-[#5F6368] hover:bg-[#F8F9FA]">Cancel</button>
                            <button type="submit" :disabled="teacherPenaltyForm.processing" class="rounded-full bg-[#E11D48] px-6 py-2 text-xs font-bold text-white shadow-sm disabled:opacity-50 inline-flex items-center gap-1.5">
                                <i class="fa-solid fa-gavel"></i>
                                <span>Issue Penalty</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Toast Notification -->
        <div v-if="showSuccessToast" class="fixed bottom-6 right-6 z-50 animate-slideUp">
            <div class="flex items-center gap-3 px-5 py-3.5 rounded-xl bg-emerald-600 border border-emerald-500 shadow-2xl text-white max-w-sm">
                <i class="fa-solid fa-circle-check text-lg shrink-0"></i>
                <div class="text-xs font-semibold">{{ successToastMsg }}</div>
                <button @click="showSuccessToast = false" class="ml-2 opacity-70 hover:opacity-100"><i class="fa-solid fa-xmark"></i></button>
            </div>
        </div>
    </TenantAppLayout>
</template>
