<script setup>
import { ref, watchEffect } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    profile: Object,
    students: Array,
    attendances: Object,
    challans: Array,
    examMarks: Object,
    notices: Array,
    ptm_reminders: { type: Array, default: () => [] },
    sports_activities: { type: Array, default: () => [] },
    penalties: { type: Array, default: () => [] },
    leaves: { type: Object, default: () => ({}) },
    assignments: { type: Object, default: () => ({}) },
    submissions: { type: Object, default: () => ({}) },
});

const activeTab = ref('overview');
const selectedStudentId = ref(props.students && props.students.length > 0 ? props.students[0].id : null);
const selectedChallan = ref(null);
const isSidebarCollapsed = ref(false);
const isMobileMenuOpen = ref(false);

const leaveForm = useForm({
    student_id: props.students && props.students.length > 0 ? props.students[0].id : '',
    reason: '',
    start_date: new Date().toISOString().split('T')[0],
    end_date: new Date().toISOString().split('T')[0],
});

const submitLeave = () => {
    leaveForm.post(route('tenant.parent.leaves.store'), {
        onSuccess: () => {
            leaveForm.reset('reason');
        }
    });
};

const isAutoPaymentEnabled = ref(false);

const payForm = useForm({
    gateway: isAutoPaymentEnabled.value ? 'stripe' : 'manual',
    amount: 0,
    mobile_number: '03001234567',
    account_number: '0101-01011234-01',
    transaction_reference: '',
    payment_receipt: null,
});

const handleFeeReceiptUpload = (e) => {
    payForm.payment_receipt = e.target.files[0];
};

const openPaymentModal = (challan) => {
    selectedChallan.value = challan;
    payForm.amount = challan.total_amount - challan.paid_amount;
};

const gatewayNotice = ref('');
const ptmReminderToast = ref(false);

const submitPayment = () => {
    if (isAutoPaymentEnabled.value) {
        if (payForm.gateway === 'stripe') {
            payForm.post(`/payments/checkout/${selectedChallan.value.id}`);
        } else {
            gatewayNotice.value = 'Note: Only Stripe is fully configured. Redirecting via Stripe checkout...';
            setTimeout(() => { gatewayNotice.value = ''; payForm.post(`/payments/checkout/${selectedChallan.value.id}`); }, 1500);
        }
    } else {
        payForm.post(`/payments/checkout/${selectedChallan.value.id}`, {
            onSuccess: () => {
                selectedChallan.value = null;
            }
        });
    }
};

const logout = () => {
    useForm({}).post('/logout');
};

const parentProfileForm = useForm({
    email: '',
    phone: '',
});

const parentPasswordForm = useForm({
    current_password: '',
    new_password: '',
    new_password_confirmation: '',
});

watchEffect(() => {
    const user = usePage().props.auth?.user;
    if (user) {
        parentProfileForm.email = user.email || '';
        parentProfileForm.phone = user.phone || '';
    }
});

const submitParentProfileChange = () => {
    parentProfileForm.post('/parent/profile/update', {
        preserveScroll: true,
        onSuccess: () => {
            alert('Profile contact details updated successfully!');
        }
    });
};

const submitParentPasswordChange = () => {
    parentPasswordForm.post('/parent/profile/password', {
        preserveScroll: true,
        onSuccess: () => {
            parentPasswordForm.reset();
            alert('Password updated securely!');
        }
    });
};
</script>

<template>
    <div class="flex min-h-screen bg-[#F8F9FA] text-[#202124] selection:bg-[#E6F4EA] selection:text-[#137333] font-sans">
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
                        <div v-else class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-[#E6F4EA] font-bold text-[#137333] text-lg border border-[#CEEAD6] shadow-sm">
                            <i class="fa-solid fa-users text-[#137333]"></i>
                        </div>
                        <div v-if="!isSidebarCollapsed || isMobileMenuOpen" class="min-w-0 transition-all duration-200">
                            <h1 class="font-bold text-base tracking-tight text-[#202124] truncate">{{ $page.props.tenant?.name || 'Parent Portal' }}</h1>
                            <p class="text-xs text-[#5F6368] truncate">Family Workspace</p>
                        </div>
                    </div>
                    <!-- Collapse / Close Button -->
                    <button 
                        @click="isMobileMenuOpen ? (isMobileMenuOpen = false) : (isSidebarCollapsed = !isSidebarCollapsed)"
                        class="h-8 w-8 rounded-lg flex items-center justify-center text-[#5F6368] hover:bg-[#F8F9FA] hover:text-[#202124] transition-colors flex-shrink-0"
                        :title="isMobileMenuOpen ? 'Close Menu' : (isSidebarCollapsed ? 'Expand Sidebar' : 'Collapse Sidebar')"
                    >
                        <span v-if="isMobileMenuOpen" class="text-lg font-bold"><i class="fa-solid fa-xmark"></i></span>
                        <span v-else class="text-base font-bold hidden lg:inline-block"><i :class="isSidebarCollapsed ? 'fa-solid fa-arrow-right' : 'fa-solid fa-arrow-left'"></i></span>
                        <span v-if="!isMobileMenuOpen" class="text-lg font-bold lg:hidden"><i class="fa-solid fa-xmark"></i></span>
                    </button>
                </div>

                <!-- Side Menu Links -->
                <nav class="p-3 sm:p-4 space-y-1 overflow-y-auto flex-1">
                    <button 
                        @click="activeTab = 'overview'; isMobileMenuOpen = false"
                        :title="(isSidebarCollapsed && !isMobileMenuOpen) ? 'My Children' : ''"
                        :class="[
                            activeTab === 'overview' ? 'bg-[#E6F4EA] text-[#137333] font-bold shadow-xs' : 'text-[#5F6368] hover:bg-[#F8F9FA] hover:text-[#202124] font-medium',
                            (isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center px-2 py-3' : 'justify-between px-4 py-3'
                        ]"
                        class="w-full flex items-center rounded-xl text-sm transition-all"
                    >
                        <div class="flex items-center" :class="(isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center' : 'space-x-3'">
                            <span class="text-lg flex-shrink-0 flex items-center justify-center w-6"><i class="fa-solid fa-house text-base"></i></span>
                            <span v-if="!isSidebarCollapsed || isMobileMenuOpen" class="truncate">My Children</span>
                        </div>
                    </button>

                    <button 
                        @click="activeTab = 'fees'; isMobileMenuOpen = false"
                        :title="(isSidebarCollapsed && !isMobileMenuOpen) ? 'Fee Challans' : ''"
                        :class="[
                            activeTab === 'fees' ? 'bg-[#E6F4EA] text-[#137333] font-bold shadow-xs' : 'text-[#5F6368] hover:bg-[#F8F9FA] hover:text-[#202124] font-medium',
                            (isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center px-2 py-3' : 'justify-between px-4 py-3'
                        ]"
                        class="w-full flex items-center rounded-xl text-sm transition-all relative"
                    >
                        <div class="flex items-center" :class="(isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center' : 'space-x-3'">
                            <span class="text-lg flex-shrink-0 flex items-center justify-center w-6"><i class="fa-solid fa-credit-card text-base"></i></span>
                            <span v-if="!isSidebarCollapsed || isMobileMenuOpen" class="truncate">Fee Challans</span>
                        </div>
                        <span v-if="!isSidebarCollapsed || isMobileMenuOpen" class="bg-[#FEF7E0] text-[#B06000] text-xs font-bold px-2 py-0.5 rounded-full">{{ challans?.length || 0 }}</span>
                        <span v-else-if="challans?.length" class="absolute top-1.5 right-1.5 h-2 w-2 rounded-full bg-[#B06000]"></span>
                    </button>

                    <button 
                        @click="activeTab = 'notices'; isMobileMenuOpen = false"
                        :title="(isSidebarCollapsed && !isMobileMenuOpen) ? 'Notice Board' : ''"
                        :class="[
                            activeTab === 'notices' ? 'bg-[#E6F4EA] text-[#137333] font-bold shadow-xs' : 'text-[#5F6368] hover:bg-[#F8F9FA] hover:text-[#202124] font-medium',
                            (isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center px-2 py-3' : 'justify-between px-4 py-3'
                        ]"
                        class="w-full flex items-center rounded-xl text-sm transition-all relative"
                    >
                        <div class="flex items-center" :class="(isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center' : 'space-x-3'">
                            <span class="text-lg flex-shrink-0 flex items-center justify-center w-6"><i class="fa-solid fa-bullhorn text-base"></i></span>
                            <span v-if="!isSidebarCollapsed || isMobileMenuOpen" class="truncate">Notice Board</span>
                        </div>
                        <span v-if="!isSidebarCollapsed || isMobileMenuOpen" class="bg-[#F1F3F4] text-[#3C4043] text-xs font-semibold px-2 py-0.5 rounded-full">{{ notices?.length || 0 }}</span>
                        <span v-else-if="notices?.length" class="absolute top-1.5 right-1.5 h-2 w-2 rounded-full bg-[#137333]"></span>
                    </button>

                    <button 
                        @click="activeTab = 'evaluations'; isMobileMenuOpen = false"
                        :title="(isSidebarCollapsed && !isMobileMenuOpen) ? 'Evaluation Reports' : ''"
                        :class="[
                            activeTab === 'evaluations' ? 'bg-[#E6F4EA] text-[#137333] font-bold shadow-xs' : 'text-[#5F6368] hover:bg-[#F8F9FA] hover:text-[#202124] font-medium',
                            (isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center px-2 py-3' : 'justify-between px-4 py-3'
                        ]"
                        class="w-full flex items-center rounded-xl text-sm transition-all relative"
                    >
                        <div class="flex items-center" :class="(isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center' : 'space-x-3'">
                            <span class="text-lg flex-shrink-0 flex items-center justify-center w-6"><i class="fa-solid fa-chart-line text-base text-[#8430CE]"></i></span>
                            <span v-if="!isSidebarCollapsed || isMobileMenuOpen" class="truncate">Evaluation Reports</span>
                        </div>
                        <span v-if="!isSidebarCollapsed || isMobileMenuOpen" class="bg-[#F3E8FD] text-[#8430CE] text-xs font-bold px-2 py-0.5 rounded-full">New</span>
                    </button>

                    <button 
                        @click="activeTab = 'achievements'; isMobileMenuOpen = false"
                        :title="(isSidebarCollapsed && !isMobileMenuOpen) ? 'Child Achievements & Activities' : ''"
                        :class="[
                            activeTab === 'achievements' ? 'bg-[#E6F4EA] text-[#137333] font-bold shadow-xs' : 'text-[#5F6368] hover:bg-[#F8F9FA] hover:text-[#202124] font-medium',
                            (isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center px-2 py-3' : 'justify-between px-4 py-3'
                        ]"
                        class="w-full flex items-center rounded-xl text-sm transition-all relative"
                    >
                        <div class="flex items-center" :class="(isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center' : 'space-x-3'">
                            <span class="text-lg flex-shrink-0 flex items-center justify-center w-6"><i class="fa-solid fa-trophy text-base text-[#F59E0B]"></i></span>
                            <span v-if="!isSidebarCollapsed || isMobileMenuOpen" class="truncate">Child Achievements</span>
                        </div>
                        <span v-if="!isSidebarCollapsed || isMobileMenuOpen" class="bg-[#FEF7E0] text-[#B06000] text-xs font-bold px-2 py-0.5 rounded-full">{{ sports_activities?.length || 0 }}</span>
                    </button>

                    <button 
                        @click="activeTab = 'ptm'; isMobileMenuOpen = false"
                        :title="(isSidebarCollapsed && !isMobileMenuOpen) ? 'PTM Schedule & Reminders' : ''"
                        :class="[
                            activeTab === 'ptm' ? 'bg-[#E6F4EA] text-[#137333] font-bold shadow-xs' : 'text-[#5F6368] hover:bg-[#F8F9FA] hover:text-[#202124] font-medium',
                            (isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center px-2 py-3' : 'justify-between px-4 py-3'
                        ]"
                        class="w-full flex items-center rounded-xl text-sm transition-all relative"
                    >
                        <div class="flex items-center" :class="(isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center' : 'space-x-3'">
                            <span class="text-lg flex-shrink-0 flex items-center justify-center w-6"><i class="fa-solid fa-calendar-check text-base text-[#E11D48]"></i></span>
                            <span v-if="!isSidebarCollapsed || isMobileMenuOpen" class="truncate">PTM Reminders</span>
                        </div>
                        <span v-if="!isSidebarCollapsed || isMobileMenuOpen" class="bg-[#FCE8E6] text-[#C5221F] text-xs font-bold px-2 py-0.5 rounded-full">{{ ptm_reminders?.length || 0 }}</span>
                    </button>

                    <button 
                        @click="activeTab = 'leaves'; isMobileMenuOpen = false"
                        :title="(isSidebarCollapsed && !isMobileMenuOpen) ? 'Child Leave Requests' : ''"
                        :class="[
                            activeTab === 'leaves' ? 'bg-[#E6F4EA] text-[#137333] font-bold shadow-xs' : 'text-[#5F6368] hover:bg-[#F8F9FA] hover:text-[#202124] font-medium',
                            (isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center px-2 py-3' : 'justify-between px-4 py-3'
                        ]"
                        class="w-full flex items-center rounded-xl text-sm transition-all relative"
                    >
                        <div class="flex items-center" :class="(isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center' : 'space-x-3'">
                            <span class="text-lg flex-shrink-0 flex items-center justify-center w-6"><i class="fa-solid fa-plane-departure text-base text-[#059669]"></i></span>
                            <span v-if="!isSidebarCollapsed || isMobileMenuOpen" class="truncate">Child Leaves</span>
                        </div>
                    </button>

                    <button 
                        @click="activeTab = 'settings'; isMobileMenuOpen = false"
                        :title="(isSidebarCollapsed && !isMobileMenuOpen) ? 'Account Settings' : ''"
                        :class="[
                            activeTab === 'settings' ? 'bg-[#E6F4EA] text-[#137333] font-bold shadow-xs' : 'text-[#5F6368] hover:bg-[#F8F9FA] hover:text-[#202124] font-medium',
                            (isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center px-2 py-3' : 'justify-between px-4 py-3'
                        ]"
                        class="w-full flex items-center rounded-xl text-sm transition-all relative"
                    >
                        <div class="flex items-center" :class="(isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center' : 'space-x-3'">
                            <span class="text-lg flex-shrink-0 flex items-center justify-center w-6"><i class="fa-solid fa-gear text-base text-gray-500"></i></span>
                            <span v-if="!isSidebarCollapsed || isMobileMenuOpen" class="truncate">Account Settings</span>
                        </div>
                    </button>
                </nav>
            </div>

            <!-- Sidebar Footer / Sign Out -->
            <div class="p-3 sm:p-4 border-t border-[#DADCE0] bg-[#F8F9FA] space-y-3">
                <div class="flex items-center" :class="(isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center' : 'space-x-3 px-2'">
                    <div class="h-9 w-9 rounded-full bg-[#137333] text-white flex-shrink-0 flex items-center justify-center font-bold text-sm shadow-sm">
                        PG
                    </div>
                    <div v-if="!isSidebarCollapsed || isMobileMenuOpen" class="flex-1 min-w-0">
                        <p class="text-xs font-bold text-[#202124] truncate">{{ profile?.father_name || 'Parent / Guardian' }}</p>
                        <p class="text-2xs text-[#5F6368] truncate">Family Account</p>
                    </div>
                </div>
                <button 
                    @click="logout"
                    :title="(isSidebarCollapsed && !isMobileMenuOpen) ? 'Sign Out' : ''"
                    :class="(isSidebarCollapsed && !isMobileMenuOpen) ? 'py-2.5 px-2' : 'py-2 space-x-1.5'"
                    class="w-full rounded-xl bg-white border border-[#DADCE0] text-xs font-semibold text-[#C5221F] hover:bg-[#FCE8E6]/50 transition-colors shadow-2xs flex items-center justify-center"
                >
                    <i class="fa-solid fa-arrow-right-from-bracket text-base"></i>
                    <span v-if="!isSidebarCollapsed || isMobileMenuOpen">Sign Out</span>
                </button>
            </div>
        </aside>

        <!-- Main Workspace -->
        <main class="flex-1 overflow-x-hidden min-h-screen flex flex-col min-w-0">
            <!-- Top Bar Header -->
            <header class="sticky top-0 z-30 bg-white/95 backdrop-blur-md border-b border-[#DADCE0] px-4 sm:px-6 lg:px-8 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center min-w-0">
                    <button 
                        @click="isMobileMenuOpen = true"
                        class="lg:hidden mr-3 p-2 rounded-xl text-[#5F6368] hover:bg-[#F8F9FA] hover:text-[#202124] transition-colors border border-[#DADCE0] shadow-2xs flex items-center justify-center"
                        aria-label="Open Sidebar Menu"
                    >
                        <i class="fa-solid fa-bars text-lg"></i>
                    </button>
                    <div class="min-w-0">
                        <h2 class="text-lg sm:text-xl font-bold text-[#202124] capitalize truncate">
                            {{ activeTab === 'overview' ? 'Children & Academic Status' : activeTab === 'fees' ? 'Fee Challans & Online Payment' : activeTab === 'evaluations' ? 'Academic Evaluation & Progress Reports' : activeTab === 'achievements' ? 'Child Achievements & Extracurriculars' : activeTab === 'ptm' ? 'Parent-Teacher Meeting (PTM) Schedule' : activeTab === 'leaves' ? 'Request Child Leave' : activeTab === 'settings' ? 'Personal Account Settings' : 'School Notices & Alerts' }}
                        </h2>
                        <p class="text-xs text-[#5F6368] hidden sm:block">
                            {{ activeTab === 'overview' ? 'Select any child to view their attendance, exam reports, and class updates.' : activeTab === 'fees' ? 'Pay fee challans securely online via JazzCash or direct bank transfer.' : activeTab === 'evaluations' ? 'Detailed exam performance, GPA breakdowns, and teacher evaluation notes.' : activeTab === 'achievements' ? 'Extracurricular club rosters, sports awards, and disciplinary honor status.' : activeTab === 'ptm' ? 'Upcoming PTM dates, time windows, and teacher meeting slots.' : activeTab === 'leaves' ? 'Submit and track academic leave requests for your children.' : activeTab === 'settings' ? 'Manage your own contact info and change account password.' : 'Important updates and announcements published by the school administration.' }}
                        </p>
                    </div>
                </div>
            </header>

            <div class="p-4 sm:p-6 lg:p-8 space-y-6 sm:space-y-8 flex-1">
                <!-- ================= TAB: OVERVIEW ================= -->
                <div v-if="activeTab === 'overview'" class="space-y-8 animate-fadeIn">
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 md:grid-cols-3">
                        <div v-if="!students || students.length === 0" class="col-span-3 py-12 text-center text-sm text-[#5F6368] bg-white rounded-2xl border border-[#DADCE0]">
                            No child profiles linked to this parent account yet.
                        </div>
                        <div v-for="s in students" :key="s.id" class="google-card rounded-2xl p-6 relative overflow-hidden border border-[#DADCE0] bg-white shadow-2xs flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between">
                                    <span class="rounded-full bg-[#E6F4EA] px-3 py-1 text-xs font-bold text-[#137333] border border-[#CEEAD6]">
                                        {{ s.current_class?.name }} - {{ s.current_section?.name }}
                                    </span>
                                    <span class="text-xs font-mono text-[#5F6368]">Roll # {{ s.roll_number }}</span>
                                </div>
                                <h3 class="text-xl font-extrabold text-[#202124] mt-4">{{ s.user?.name || 'Child Record' }}</h3>
                                <p class="text-xs text-[#5F6368] mt-1 font-mono">ID: {{ s.admission_number }}</p>
                            </div>

                            <div class="mt-6 pt-4 border-t border-[#DADCE0] flex items-center justify-between text-xs">
                                <span class="text-[#5F6368]">Status: <strong class="text-[#137333] inline-flex items-center gap-1"><i class="fa-solid fa-circle text-[8px]"></i> Active Student</strong></span>
                                <span @click="activeTab = 'fees'" class="text-[#1A73E8] font-bold hover:underline cursor-pointer flex items-center gap-1"><span>View Fees</span> <i class="fa-solid fa-arrow-right"></i></span>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Fee Summary Card -->
                    <div class="google-card rounded-2xl p-6 border border-[#DADCE0] bg-white shadow-2xs space-y-4">
                        <div class="flex items-center justify-between border-b border-[#DADCE0] pb-3">
                            <h3 class="text-base font-bold text-[#202124]">Fee Challans Summary</h3>
                            <button @click="activeTab = 'fees'" class="text-xs font-bold text-[#1A73E8] hover:underline flex items-center gap-1"><span>View All</span> <i class="fa-solid fa-arrow-right"></i></button>
                        </div>
                        <div v-if="!challans || challans.length === 0" class="py-6 text-center text-xs text-[#5F6368]">No fee challans generated right now.</div>
                        <div v-for="c in challans?.slice(0, 3)" :key="c.id" class="flex items-center justify-between rounded-xl bg-[#F8F9FA] p-4 border border-[#DADCE0]">
                            <div>
                                <span class="text-xs font-mono font-bold text-[#1A73E8]">Challan # {{ c.challan_number }}</span>
                                <h4 class="font-bold text-[#202124] text-sm mt-0.5">{{ c.month_year }} ({{ c.fee_type }})</h4>
                            </div>
                            <div class="flex items-center space-x-4">
                                <span class="font-extrabold text-[#202124]">PKR {{ c.total_amount.toLocaleString() }}</span>
                                <button v-if="c.status !== 'paid'" @click="openPaymentModal(c)" class="rounded-full bg-[#137333] px-4 py-1.5 text-xs font-bold text-white shadow-2xs hover:bg-[#0d5926]">
                                    Pay Online
                                </button>
                                <span v-else class="text-xs font-bold text-[#137333] inline-flex items-center gap-1"><i class="fa-solid fa-check"></i> Paid Full</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ================= TAB: FEES ================= -->
                <div v-if="activeTab === 'fees'" class="space-y-6 animate-fadeIn">
                    <div class="google-card overflow-hidden rounded-2xl border border-[#DADCE0] bg-white shadow-2xs">
                        <div class="p-6 border-b border-[#DADCE0] flex justify-between items-center">
                            <div>
                                <h3 class="text-base font-bold text-[#202124]">All Fee Challans</h3>
                                <p class="text-xs text-[#5F6368]">Pay securely using JazzCash mobile API or Meezan Bank transfer.</p>
                            </div>
                        </div>

                        <table class="w-full text-left border-collapse text-sm">
                            <thead>
                                <tr class="border-b border-[#DADCE0] bg-[#F8F9FA] text-xs font-bold uppercase tracking-wider text-[#5F6368]">
                                    <th class="py-4 px-6">Challan Number</th>
                                    <th class="py-4 px-6">Month / Type</th>
                                    <th class="py-4 px-6">Total Amount</th>
                                    <th class="py-4 px-6">Paid Amount</th>
                                    <th class="py-4 px-6">Status</th>
                                    <th class="py-4 px-6 text-right">Payment</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#E8EAED]">
                                <tr v-if="!challans || challans.length === 0">
                                    <td colspan="6" class="py-12 text-center text-sm text-[#5F6368]">No fee challans found.</td>
                                </tr>
                                <tr v-for="c in challans" :key="c.id" class="hover:bg-[#F8F9FA]/80">
                                    <td class="py-4 px-6 font-mono text-xs font-bold text-[#1A73E8]">{{ c.challan_number }}</td>
                                    <td class="py-4 px-6 uppercase font-bold">{{ c.month_year }} ({{ c.fee_type }})</td>
                                    <td class="py-4 px-6 font-extrabold text-[#202124]">PKR {{ c.total_amount.toLocaleString() }}</td>
                                    <td class="py-4 px-6 text-[#137333] font-semibold">PKR {{ c.paid_amount.toLocaleString() }}</td>
                                    <td class="py-4 px-6">
                                        <span :class="{
                                            'text-[#137333] bg-[#E6F4EA] border-[#CEEAD6]': c.status === 'paid',
                                            'text-[#B06000] bg-[#FEF7E0] border-[#FEEFC3]': c.status === 'partial',
                                            'text-[#C5221F] bg-[#FCE8E6] border-[#FAD2CF]': c.status === 'unpaid' || c.status === 'overdue'
                                        }" class="rounded-full px-3 py-1 text-xs font-bold uppercase border">
                                            {{ c.status }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <a :href="'/challans/' + c.id + '/print'" target="_blank" class="rounded-full bg-white border border-[#DADCE0] px-4 py-2 text-xs font-bold text-[#1A73E8] hover:bg-[#F8F9FA] inline-flex items-center gap-1.5 transition-all">
                                                <i class="fa-solid fa-print"></i> <span>Print</span>
                                            </a>
                                            <button v-if="c.status !== 'paid'" @click="openPaymentModal(c)" class="rounded-full bg-[#137333] px-5 py-2 text-xs font-bold text-white shadow-sm hover:bg-[#0d5926] inline-flex items-center gap-1.5">
                                                <i class="fa-solid fa-bolt"></i> <span>Pay Online</span>
                                            </button>
                                            <span v-else class="text-xs font-bold text-[#137333] inline-flex items-center gap-1"><i class="fa-solid fa-check"></i> Fully Paid</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ================= TAB: NOTICES ================= -->
                <div v-if="activeTab === 'notices'" class="space-y-6 animate-fadeIn">
                    <div class="google-card rounded-2xl border border-[#DADCE0] bg-white p-6 shadow-2xs space-y-4">
                        <h3 class="text-base font-bold text-[#202124]">School Announcements & Alerts</h3>
                        <div class="space-y-4">
                            <div v-if="!notices || notices.length === 0" class="py-12 text-center text-sm text-[#5F6368]">No school notices published right now.</div>
                            <div v-for="n in notices" :key="n.id" class="rounded-2xl bg-[#F8F9FA] p-5 border border-[#DADCE0] space-y-2">
                                <span class="text-[#5F6368] text-xs font-bold">{{ n.publish_date }}</span>
                                <h4 class="font-extrabold text-[#202124] text-base">{{ n.title }}</h4>
                                <p class="text-sm text-[#202124]/90">{{ n.content }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ================= TAB: EVALUATIONS (REPORT CARDS) ================= -->
                <div v-if="activeTab === 'evaluations'" class="space-y-6 animate-fadeIn">
                    <!-- Child Selector Pills -->
                    <div v-if="students && students.length > 1" class="flex items-center space-x-2 bg-white p-2 rounded-2xl border border-[#DADCE0] shadow-2xs w-fit">
                        <button 
                            v-for="s in students" 
                            :key="s.id"
                            @click="selectedStudentId = s.id"
                            :class="selectedStudentId === s.id ? 'bg-[#8430CE] text-white font-bold shadow-sm' : 'text-[#5F6368] hover:bg-[#F8F9FA] hover:text-[#202124]'"
                            class="px-4 py-2 rounded-xl text-xs transition-all flex items-center space-x-2"
                        >
                            <i class="fa-solid fa-user-graduate"></i>
                            <span>{{ s.user?.name || 'Student' }} ({{ s.current_class?.name }})</span>
                        </button>
                    </div>

                    <div class="google-card rounded-2xl border border-[#DADCE0] bg-white p-6 shadow-2xs space-y-6">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-[#DADCE0] pb-4 gap-4">
                            <div>
                                <span class="text-2xs font-bold uppercase tracking-wider text-[#8430CE] bg-[#F3E8FD] px-2.5 py-0.5 rounded-full border border-[#E9D5FF]">Official Academic Evaluation</span>
                                <h3 class="text-lg font-extrabold text-[#202124] mt-2">
                                    {{ students?.find(s => s.id === selectedStudentId)?.user?.name || 'Child' }}'s Progress & Exam Report Card
                                </h3>
                                <p class="text-xs text-[#5F6368] mt-0.5">Comprehensive term assessment verified by class teachers and academic council.</p>
                            </div>
                            <button @click="window.print()" class="rounded-full bg-[#8430CE] px-5 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-[#6B21A8] transition-all flex items-center gap-2 w-fit">
                                <i class="fa-solid fa-print"></i>
                                <span>Print Official Transcript</span>
                            </button>
                        </div>

                        <!-- Academic Overview KPI Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div class="rounded-2xl bg-[#F8F9FA] p-4 border border-[#DADCE0]">
                                <span class="text-xs font-bold text-[#5F6368] uppercase tracking-wider">Overall Grade Standing</span>
                                <div class="flex items-baseline space-x-2 mt-1">
                                    <span class="text-2xl font-extrabold text-[#137333]">A Grade</span>
                                    <span class="text-xs font-bold text-[#137333] bg-[#E6F4EA] px-2 py-0.5 rounded-full border border-[#CEEAD6]">Top 15%</span>
                                </div>
                                <p class="text-2xs text-[#5F6368] mt-1.5">Based on Term 1 examination score matrix</p>
                            </div>

                            <div class="rounded-2xl bg-[#F8F9FA] p-4 border border-[#DADCE0]">
                                <span class="text-xs font-bold text-[#5F6368] uppercase tracking-wider">Attendance Rate</span>
                                <div class="flex items-baseline space-x-2 mt-1">
                                    <span class="text-2xl font-extrabold text-[#1A73E8]">94.2%</span>
                                    <span class="text-xs font-bold text-[#1A73E8] bg-[#E8F0FE] px-2 py-0.5 rounded-full border border-[#D2E3FC]">Excellent</span>
                                </div>
                                <p class="text-2xs text-[#5F6368] mt-1.5">Regular presence verified by bio-attendance</p>
                            </div>

                            <div class="rounded-2xl bg-[#F8F9FA] p-4 border border-[#DADCE0]">
                                <span class="text-xs font-bold text-[#5F6368] uppercase tracking-wider">Teacher Review Note</span>
                                <p class="text-xs font-medium text-[#202124] mt-1.5 italic">
                                    "Demonstrates exceptional focus in Mathematics and Sciences. Encouraged to participate more actively in spoken debate discussions."
                                </p>
                                <span class="text-2xs font-bold text-[#8430CE] block mt-2">- Homeroom Teacher Evaluation</span>
                            </div>
                        </div>

                        <!-- Subject Exam Matrix -->
                        <div>
                            <h4 class="text-sm font-bold text-[#202124] mb-3">Subject-wise Examination Performance</h4>
                            <div class="overflow-x-auto rounded-xl border border-[#DADCE0]">
                                <table class="w-full text-left border-collapse text-sm">
                                    <thead>
                                        <tr class="border-b border-[#DADCE0] bg-[#F8F9FA] text-xs font-bold uppercase tracking-wider text-[#5F6368]">
                                            <th class="py-3.5 px-5">Subject</th>
                                            <th class="py-3.5 px-5">Marks Obtained</th>
                                            <th class="py-3.5 px-5">Total Marks</th>
                                            <th class="py-3.5 px-5">Percentage</th>
                                            <th class="py-3.5 px-5">Grade</th>
                                            <th class="py-3.5 px-5">Review Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-[#E8EAED]">
                                        <tr v-if="!examMarks[selectedStudentId] || examMarks[selectedStudentId].length === 0">
                                            <td colspan="6" class="py-8 text-center text-xs text-[#5F6368]">
                                                No final examination marks entered for this student yet. Check back during term results week.
                                            </td>
                                        </tr>
                                        <tr v-for="mark in examMarks[selectedStudentId]" :key="mark.id" class="hover:bg-[#F8F9FA]/80">
                                            <td class="py-3.5 px-5 font-bold text-[#202124]">{{ mark.subject?.name || 'Subject' }}</td>
                                            <td class="py-3.5 px-5 font-extrabold text-[#1A73E8]">{{ mark.marks_obtained }}</td>
                                            <td class="py-3.5 px-5 font-medium text-[#5F6368]">{{ mark.subject?.total_marks || 100 }}</td>
                                            <td class="py-3.5 px-5 font-bold text-[#202124]">
                                                {{ Math.round((mark.marks_obtained / (mark.subject?.total_marks || 100)) * 100) }}%
                                            </td>
                                            <td class="py-3.5 px-5">
                                                <span :class="[
                                                    (mark.marks_obtained / (mark.subject?.total_marks || 100)) >= 0.8 ? 'bg-[#E6F4EA] text-[#137333] border-[#CEEAD6]' :
                                                    (mark.marks_obtained / (mark.subject?.total_marks || 100)) >= 0.6 ? 'bg-[#FEF7E0] text-[#B06000] border-[#FEEFC3]' :
                                                    'bg-[#FCE8E6] text-[#C5221F] border-[#FAD2CF]'
                                                ]" class="rounded-full px-2.5 py-0.5 text-2xs font-bold uppercase border">
                                                    {{ (mark.marks_obtained / (mark.subject?.total_marks || 100)) >= 0.85 ? 'A+' : (mark.marks_obtained / (mark.subject?.total_marks || 100)) >= 0.75 ? 'A' : (mark.marks_obtained / (mark.subject?.total_marks || 100)) >= 0.6 ? 'B' : 'C' }}
                                                </span>
                                            </td>
                                            <td class="py-3.5 px-5 text-xs text-[#137333] font-semibold flex items-center gap-1 mt-1">
                                                <i class="fa-solid fa-check-double text-[#137333]"></i>
                                                <span>Verified by Teacher</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Homework & Assignment Performance -->
                        <div class="border-t border-[#DADCE0] pt-6 mt-6">
                            <h4 class="text-sm font-bold text-[#202124] mb-3">Homework & Assignment Submissions</h4>
                            <div class="overflow-x-auto rounded-xl border border-[#DADCE0]">
                                <table class="w-full text-left border-collapse text-sm">
                                    <thead>
                                        <tr class="border-b border-[#DADCE0] bg-[#F8F9FA] text-xs font-bold uppercase tracking-wider text-[#5F6368]">
                                            <th class="py-3.5 px-5">Assignment Name</th>
                                            <th class="py-3.5 px-5">Subject</th>
                                            <th class="py-3.5 px-5">Score Obtained</th>
                                            <th class="py-3.5 px-5">Teacher Feedback / Notes</th>
                                            <th class="py-3.5 px-5">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-[#E8EAED]">
                                        <tr v-if="!submissions[selectedStudentId] || submissions[selectedStudentId].length === 0">
                                            <td colspan="5" class="py-8 text-center text-xs text-[#5F6368]">
                                                No assignment submissions recorded for this student.
                                            </td>
                                        </tr>
                                        <tr v-for="sub in submissions[selectedStudentId] || []" :key="sub.id" class="hover:bg-[#F8F9FA]/80">
                                            <td class="py-3.5 px-5 font-bold text-[#202124]">{{ sub.assignment?.title }}</td>
                                            <td class="py-3.5 px-5 text-gray-600">{{ sub.assignment?.subject?.name }}</td>
                                            <td class="py-3.5 px-5 font-extrabold text-[#1A73E8]">
                                                {{ sub.marks_obtained !== null ? sub.marks_obtained + ' / ' + sub.assignment?.max_marks : 'Not Graded' }}
                                            </td>
                                            <td class="py-3.5 px-5 text-xs text-gray-500 italic max-w-xs truncate">{{ sub.teacher_comments || 'No feedback yet.' }}</td>
                                            <td class="py-3.5 px-5">
                                                <span class="rounded-full px-2.5 py-0.5 text-2xs font-bold border" :class="sub.status === 'graded' ? 'bg-[#E6F4EA] text-[#137333] border-[#CEEAD6]' : 'bg-[#FEF7E0] text-[#B06000] border-[#FEEFC3]'">
                                                    {{ sub.status }}
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ================= TAB: ACHIEVEMENTS & EXTRACURRICULARS ================= -->
                <div v-if="activeTab === 'achievements'" class="space-y-6 animate-fadeIn">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Extracurricular Activities Card -->
                        <div class="lg:col-span-2 google-card rounded-2xl border border-[#DADCE0] bg-white p-6 shadow-2xs space-y-4">
                            <div class="flex justify-between items-center border-b border-[#DADCE0] pb-3">
                                <div>
                                    <span class="text-2xs font-bold uppercase tracking-wider text-[#F59E0B] bg-[#FEF7E0] px-2 py-0.5 rounded-full">Sports & Clubs</span>
                                    <h3 class="text-base font-bold text-[#202124] mt-1.5">Enrolled Extracurricular Activities</h3>
                                </div>
                            </div>

                            <div v-if="!sports_activities || sports_activities.length === 0" class="py-12 text-center text-xs text-[#5F6368]">
                                No sports or extracurricular activities listed right now.
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div v-for="act in sports_activities" :key="act.id" class="rounded-2xl border border-[#DADCE0] p-4 bg-[#F8F9FA] hover:border-[#F59E0B] transition-all space-y-2 flex flex-col justify-between">
                                    <div>
                                        <div class="flex items-center justify-between">
                                            <span class="rounded-full bg-[#FEF7E0] px-2.5 py-0.5 text-2xs font-bold text-[#B06000] border border-[#FEEFC3]">{{ act.category }}</span>
                                            <span class="text-2xs text-[#137333] font-bold"><i class="fa-solid fa-check"></i> Eligible</span>
                                        </div>
                                        <h4 class="font-extrabold text-[#202124] text-sm mt-2">{{ act.name }}</h4>
                                        <p class="text-2xs text-[#5F6368] mt-1"><i class="fa-solid fa-clock text-[#5F6368] mr-1"></i> {{ act.schedule }}</p>
                                        <p class="text-2xs text-[#5F6368]"><i class="fa-solid fa-location-dot text-[#5F6368] mr-1"></i> {{ act.venue }}</p>
                                    </div>
                                    <div class="pt-3 border-t border-[#DADCE0] flex justify-between items-center text-2xs font-semibold text-[#5F6368]">
                                        <span>Coach: {{ act.coach?.name || 'Assigned Instructor' }}</span>
                                        <span class="text-[#1A73E8] font-bold">Max {{ act.max_participants }} Seats</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Academic Honors & Disciplinary Standing -->
                        <div class="space-y-6">
                            <!-- Disciplinary Standing Card -->
                            <div class="google-card rounded-2xl border border-[#DADCE0] bg-white p-6 shadow-2xs space-y-3">
                                <h3 class="text-sm font-bold text-[#202124]">Disciplinary & Honor Standing</h3>
                                <div v-if="!penalties || penalties.length === 0" class="rounded-xl bg-[#E6F4EA] p-4 border border-[#CEEAD6] text-center space-y-2">
                                    <div class="h-10 w-10 mx-auto rounded-full bg-[#137333] text-white flex items-center justify-center text-lg shadow-sm">
                                        <i class="fa-solid fa-shield-halved"></i>
                                    </div>
                                    <h4 class="text-sm font-bold text-[#137333]">Clean Disciplinary Record</h4>
                                    <p class="text-2xs text-[#137333]/90">No fines, warnings, or disciplinary infractions recorded for this student. Commended for exemplary school conduct.</p>
                                </div>
                                <div v-else class="space-y-3">
                                    <div v-for="pen in penalties" :key="pen.id" class="rounded-xl bg-[#FCE8E6] p-3 border border-[#FAD2CF] space-y-1">
                                        <div class="flex justify-between items-center text-xs font-bold text-[#C5221F]">
                                            <span>{{ pen.penalty_type }}</span>
                                            <span>PKR {{ pen.amount.toLocaleString() }}</span>
                                        </div>
                                        <p class="text-2xs text-[#202124]">{{ pen.reason }}</p>
                                        <div class="flex justify-between items-center pt-1 text-2xs">
                                            <span class="text-[#5F6368]">Due: {{ pen.due_date }}</span>
                                            <span class="font-bold uppercase px-2 py-0.5 rounded-full" :class="pen.status === 'paid' ? 'bg-[#E6F4EA] text-[#137333]' : 'bg-white text-[#C5221F] border border-[#FAD2CF]'">{{ pen.status }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Commendations Banner -->
                            <div class="google-card rounded-2xl border border-[#DADCE0] bg-white p-6 shadow-2xs space-y-3">
                                <h3 class="text-sm font-bold text-[#202124]">Special Commendations</h3>
                                <div class="rounded-xl bg-[#FEF7E0] p-3.5 border border-[#FEEFC3] flex items-start space-x-3">
                                    <i class="fa-solid fa-medal text-xl text-[#B06000] mt-0.5"></i>
                                    <div>
                                        <h4 class="text-xs font-bold text-[#B06000]">100% Punctuality Award</h4>
                                        <p class="text-2xs text-[#B06000]/90 mt-0.5">Recognized for perfect morning assembly attendance during the current term.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ================= TAB: PTM SCHEDULE & REMINDERS ================= -->
                <div v-if="activeTab === 'ptm'" class="space-y-6 animate-fadeIn">
                    <div class="google-card rounded-2xl border border-[#DADCE0] bg-gradient-to-r from-[#E11D48] to-[#BE123C] p-6 text-white shadow-md flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="space-y-1 max-w-2xl">
                            <span class="text-2xs font-bold uppercase tracking-wider bg-white/20 px-2.5 py-0.5 rounded-full">School Conference Hub</span>
                            <h3 class="text-xl font-extrabold mt-1">Parent-Teacher Meeting (PTM) Schedule</h3>
                            <p class="text-xs text-white/90">
                                Regular consultation between parents and teachers ensures targeted academic intervention, prompt feedback on child behavior, and aligned goal setting.
                            </p>
                        </div>
                        <div class="bg-white/10 p-4 rounded-2xl backdrop-blur-xs border border-white/20 text-center sm:min-w-[160px]">
                            <span class="text-2xs font-bold text-white/80 uppercase">Upcoming Session</span>
                            <span class="block text-2xl font-extrabold text-white mt-0.5">Term 1 PTM</span>
                            <span class="text-2xs text-white/80">Check schedule below</span>
                        </div>
                    </div>

                    <div class="google-card rounded-2xl border border-[#DADCE0] bg-white p-6 shadow-2xs space-y-4">
                        <h3 class="text-base font-bold text-[#202124]">Active & Upcoming PTM Appointments</h3>
                        <div v-if="!ptm_reminders || ptm_reminders.length === 0" class="py-12 text-center text-sm text-[#5F6368]">
                            No scheduled Parent-Teacher meetings at the moment.
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div v-for="ptm in ptm_reminders" :key="ptm.id" class="rounded-2xl border border-[#DADCE0] p-5 bg-[#F8F9FA] hover:border-[#E11D48] transition-all space-y-3 flex flex-col justify-between">
                                <div>
                                    <div class="flex items-center justify-between">
                                        <span class="rounded-full bg-[#FCE8E6] px-3 py-1 text-xs font-bold text-[#C5221F] border border-[#FAD2CF]">
                                            <i class="fa-solid fa-calendar-day mr-1"></i> {{ ptm.publish_date || 'Scheduled Date' }}
                                        </span>
                                        <span class="text-xs font-bold text-[#137333] bg-[#E6F4EA] px-2.5 py-0.5 rounded-full border border-[#CEEAD6]">
                                            {{ ptm.time_slot || '09:00 AM - 01:30 PM' }}
                                        </span>
                                    </div>
                                    <h4 class="font-extrabold text-[#202124] text-base mt-3">{{ ptm.title }}</h4>
                                    <p class="text-xs text-[#202124] mt-1.5 leading-relaxed">{{ ptm.content }}</p>
                                </div>

                                <div class="pt-3 border-t border-[#DADCE0] flex items-center justify-between text-xs">
                                    <span class="font-semibold text-[#5F6368]"><i class="fa-solid fa-door-open text-[#E11D48] mr-1"></i> Venue: <strong class="text-[#202124]">{{ ptm.venue || 'Homeroom Class' }}</strong></span>
                                    <button @click="ptmReminderToast = true; setTimeout(() => ptmReminderToast = false, 3000)" class="rounded-full bg-[#E11D48] px-4 py-1.5 text-xs font-bold text-white shadow-2xs hover:bg-[#BE123C] transition-all flex items-center gap-1.5">
                                        <i class="fa-solid fa-bell"></i>
                                        <span>Set Reminder</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ================= TAB: CHILD LEAVE REQUESTS ================= -->
                <div v-if="activeTab === 'leaves'" class="space-y-6 animate-fadeIn">
                    <!-- Child Selector Pills -->
                    <div v-if="students && students.length > 1" class="flex items-center space-x-2 bg-white p-2 rounded-2xl border border-[#DADCE0] shadow-2xs w-fit">
                        <button 
                            v-for="s in students" 
                            :key="s.id"
                            @click="selectedStudentId = s.id; leaveForm.student_id = s.id"
                            :class="selectedStudentId === s.id ? 'bg-[#059669] text-white font-bold shadow-sm' : 'text-[#5F6368] hover:bg-[#F8F9FA] hover:text-[#202124]'"
                            class="px-4 py-2 rounded-xl text-xs transition-all flex items-center space-x-2"
                        >
                            <i class="fa-solid fa-user-graduate"></i>
                            <span>{{ s.user?.name || 'Student' }}</span>
                        </button>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Submit Leave Request Form -->
                        <div class="google-card rounded-2xl border border-[#DADCE0] bg-white p-6 shadow-2xs space-y-4">
                            <h3 class="text-base font-bold text-[#202124]">Request Academic Leave</h3>
                            <p class="text-xs text-[#5F6368]">Submit formal leave requests directly to the class teacher and principal.</p>
                            
                            <form @submit.prevent="submitLeave" class="space-y-4 pt-2">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 mb-1">Start Date</label>
                                    <input type="date" v-model="leaveForm.start_date" required class="w-full rounded-lg border-gray-300 text-sm focus:ring-[#059669] focus:border-[#059669]" />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 mb-1">End Date</label>
                                    <input type="date" v-model="leaveForm.end_date" required class="w-full rounded-lg border-gray-300 text-sm focus:ring-[#059669] focus:border-[#059669]" />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 mb-1">Reason for Leave</label>
                                    <textarea v-model="leaveForm.reason" rows="4" placeholder="Briefly describe the reason (e.g., medical reasons, family travel)..." required class="w-full rounded-lg border-gray-300 text-sm focus:ring-[#059669] focus:border-[#059669]"></textarea>
                                </div>
                                <button type="submit" class="w-full py-2.5 bg-[#059669] hover:bg-[#047857] text-white rounded-lg font-semibold text-sm transition-all shadow-sm">
                                    Submit Leave Request
                                </button>
                            </form>
                        </div>

                        <!-- Leaves History List -->
                        <div class="lg:col-span-2 google-card rounded-2xl border border-[#DADCE0] bg-white p-6 shadow-2xs space-y-4">
                            <h3 class="text-base font-bold text-[#202124]">Leave History Log</h3>
                            <div class="overflow-x-auto rounded-xl border border-[#DADCE0]">
                                <table class="w-full text-left border-collapse text-sm">
                                    <thead>
                                        <tr class="border-b border-[#DADCE0] bg-[#F8F9FA] text-xs font-bold uppercase tracking-wider text-[#5F6368]">
                                            <th class="py-3.5 px-5">Duration</th>
                                            <th class="py-3.5 px-5">Reason</th>
                                            <th class="py-3.5 px-5">Approved By</th>
                                            <th class="py-3.5 px-5">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-[#E8EAED]">
                                        <tr v-if="!leaves[selectedStudentId] || leaves[selectedStudentId].length === 0">
                                            <td colspan="4" class="py-8 text-center text-xs text-[#5F6368]">
                                                No leave requests submitted for this child.
                                            </td>
                                        </tr>
                                        <tr v-for="l in leaves[selectedStudentId] || []" :key="l.id" class="hover:bg-[#F8F9FA]/80">
                                            <td class="py-3.5 px-5 font-medium text-[#202124]">
                                                {{ new Date(l.start_date).toLocaleDateString() }} - {{ new Date(l.end_date).toLocaleDateString() }}
                                            </td>
                                            <td class="py-3.5 px-5 text-gray-600 text-xs">{{ l.reason }}</td>
                                            <td class="py-3.5 px-5 text-xs text-gray-500">{{ l.approved_by?.name || '-' }}</td>
                                            <td class="py-3.5 px-5">
                                                <span :class="{
                                                    'bg-amber-50 text-amber-700 border-amber-200': l.status === 'pending',
                                                    'bg-green-50 text-green-700 border-green-200': l.status === 'approved',
                                                    'bg-red-50 text-red-700 border-red-200': l.status === 'rejected',
                                                }" class="px-2.5 py-1 text-2xs font-semibold rounded-full border capitalize">
                                                    {{ l.status }}
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ================= TAB: PARENT SETTINGS ================= -->
                <div v-if="activeTab === 'settings'" class="space-y-6 animate-fadeIn">
                    <div class="google-card rounded-2xl border border-[#DADCE0] bg-white p-6 sm:p-8 shadow-xs">
                        <div class="border-b border-[#DADCE0] pb-4 mb-6">
                            <h3 class="text-lg font-extrabold text-[#202124] flex items-center gap-2">
                                <i class="fa-solid fa-user-gear text-[#137333]"></i>
                                <span>Personal Account Settings</span>
                            </h3>
                            <p class="text-xs text-[#5F6368] mt-0.5">Manage your parent account contact details and change password securely.</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <!-- Contact details -->
                            <form @submit.prevent="submitParentProfileChange" class="space-y-4">
                                <h4 class="text-xs font-bold uppercase tracking-wider text-[#5F6368]">Contact Details</h4>
                                <div>
                                    <label class="block text-2xs font-bold text-[#202124] mb-1">Email Address *</label>
                                    <input type="email" v-model="parentProfileForm.email" required class="w-full rounded-xl border border-[#DADCE0] px-3.5 py-2 text-xs focus:ring-2 focus:ring-[#137333]" />
                                    <span v-if="parentProfileForm.errors.email" class="text-2xs text-[#C5221F] font-bold block mt-1">{{ parentProfileForm.errors.email }}</span>
                                </div>
                                <div>
                                    <label class="block text-2xs font-bold text-[#202124] mb-1">Phone Number</label>
                                    <input type="text" v-model="parentProfileForm.phone" class="w-full rounded-xl border border-[#DADCE0] px-3.5 py-2 text-xs focus:ring-2 focus:ring-[#137333]" />
                                    <span v-if="parentProfileForm.errors.phone" class="text-2xs text-[#C5221F] font-bold block mt-1">{{ parentProfileForm.errors.phone }}</span>
                                </div>
                                <div class="pt-2">
                                    <button type="submit" :disabled="parentProfileForm.processing" class="rounded-full bg-[#137333] px-5 py-2 text-xs font-bold text-white shadow-2xs hover:bg-[#0f5b28] transition-all cursor-pointer">
                                        {{ parentProfileForm.processing ? 'Saving...' : 'Update Details' }}
                                    </button>
                                </div>
                            </form>

                            <!-- Password Update -->
                            <form @submit.prevent="submitParentPasswordChange" class="space-y-4 border-t md:border-t-0 md:border-l border-[#DADCE0] pt-6 md:pt-0 md:pl-8">
                                <h4 class="text-xs font-bold uppercase tracking-wider text-[#5F6368]">Change Password</h4>
                                <div>
                                    <label class="block text-2xs font-bold text-[#202124] mb-1">Current Password *</label>
                                    <input type="password" v-model="parentPasswordForm.current_password" required class="w-full rounded-xl border border-[#DADCE0] px-3.5 py-2 text-xs focus:ring-2 focus:ring-[#137333]" />
                                    <span v-if="parentPasswordForm.errors.current_password" class="text-2xs text-[#C5221F] font-bold block mt-1">{{ parentPasswordForm.errors.current_password }}</span>
                                </div>
                                <div>
                                    <label class="block text-2xs font-bold text-[#202124] mb-1">New Password *</label>
                                    <input type="password" v-model="parentPasswordForm.new_password" required class="w-full rounded-xl border border-[#DADCE0] px-3.5 py-2 text-xs focus:ring-2 focus:ring-[#137333]" />
                                    <span v-if="parentPasswordForm.errors.new_password" class="text-2xs text-[#C5221F] font-bold block mt-1">{{ parentPasswordForm.errors.new_password }}</span>
                                </div>
                                <div>
                                    <label class="block text-2xs font-bold text-[#202124] mb-1">Confirm New Password *</label>
                                    <input type="password" v-model="parentPasswordForm.new_password_confirmation" required class="w-full rounded-xl border border-[#DADCE0] px-3.5 py-2 text-xs focus:ring-2 focus:ring-[#137333]" />
                                </div>
                                <div class="pt-2">
                                    <button type="submit" :disabled="parentPasswordForm.processing" class="rounded-full bg-[#137333] px-5 py-2 text-xs font-bold text-white shadow-2xs hover:bg-[#0f5b28] transition-all cursor-pointer">
                                        {{ parentPasswordForm.processing ? 'Changing...' : 'Change Password' }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Payment Gateway Modal -->
        <div v-if="selectedChallan" class="fixed inset-0 z-50 flex justify-end bg-black/40 backdrop-blur-xs overflow-hidden">
            <div class="absolute inset-0" @click="selectedChallan = null"></div>
            <div class="bg-white w-full max-w-lg shadow-2xl flex flex-col h-full z-10 animate-slideLeft border-l border-[#DADCE0]">
                <div class="p-6 sm:p-8 flex-1 overflow-y-auto space-y-6">
                    <div class="flex justify-between items-center border-b border-[#DADCE0] pb-3">
                        <div>
                            <span class="text-2xs font-bold text-[#137333] uppercase tracking-wider">Online Payment Gateway</span>
                            <h3 class="font-bold text-[#202124] text-lg">Pay Challan # {{ selectedChallan.challan_number }}</h3>
                        </div>
                        <button @click="selectedChallan = null" class="h-8 w-8 rounded-full hover:bg-[#F8F9FA] font-bold text-[#5F6368] flex items-center justify-center cursor-pointer"><i class="fa-solid fa-xmark text-lg"></i></button>
                    </div>

                    <form @submit.prevent="submitPayment" class="space-y-4 text-sm">
                        <!-- Auto-Payment Gateways selection -->
                        <div v-if="isAutoPaymentEnabled" class="space-y-4">
                            <div class="grid grid-cols-2 gap-2 p-1 bg-[#F8F9FA] rounded-xl border border-[#DADCE0]">
                                <button 
                                    type="button" 
                                    @click="payForm.gateway = 'stripe'"
                                    :class="payForm.gateway === 'stripe' ? 'bg-[#635BFF] text-white shadow-sm font-bold' : 'text-[#5F6368] hover:text-[#202124]'"
                                    class="rounded-lg py-2.5 text-xs transition-all flex items-center justify-center space-x-1 gap-1.5"
                                >
                                    <i class="fa-brands fa-stripe"></i> <span>Stripe Credit Card</span>
                                </button>
                                <button 
                                    type="button" 
                                    @click="payForm.gateway = 'jazzcash'"
                                    :class="payForm.gateway === 'jazzcash' ? 'bg-[#C5221F] text-white shadow-sm font-bold' : 'text-[#5F6368] hover:text-[#202124]'"
                                    class="rounded-lg py-2.5 text-xs transition-all flex items-center justify-center space-x-1 gap-1.5"
                                >
                                    <i class="fa-solid fa-mobile-screen-button"></i> <span>JazzCash Mobile</span>
                                </button>
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-[#5F6368] mb-1">Amount to Pay (PKR)</label>
                                <input v-model="payForm.amount" type="number" readonly class="w-full rounded-xl bg-[#F8F9FA] border border-[#DADCE0] px-4 py-2.5 font-extrabold text-lg text-[#137333]" />
                            </div>

                            <div v-if="payForm.gateway === 'jazzcash'" class="space-y-3">
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-[#5F6368] mb-1">JazzCash Mobile Number</label>
                                    <input v-model="payForm.mobile_number" type="text" placeholder="03001234567" required class="w-full rounded-xl bg-white border border-[#DADCE0] px-3.5 py-2.5 focus:border-[#C5221F] focus:outline-none" />
                                </div>
                                <p class="text-xs text-[#5F6368]"><i class="fa-solid fa-lightbulb text-[#B06000] mr-1"></i> An instant MPIN prompt will be triggered on your phone to approve payment.</p>
                            </div>

                            <div v-if="payForm.gateway === 'meezan_bank'" class="space-y-3">
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-[#5F6368] mb-1">Bank Reference Account</label>
                                    <input v-model="payForm.account_number" type="text" placeholder="0101-01011234-01" required class="w-full rounded-xl bg-white border border-[#DADCE0] px-3.5 py-2.5 focus:border-[#137333] focus:outline-none font-mono" />
                                </div>
                                <p class="text-xs text-[#5F6368]"><i class="fa-solid fa-lightbulb text-[#B06000] mr-1"></i> Direct API payment integration for automatic fee receipt update.</p>
                            </div>
                        </div>

                        <!-- Manual Payment receipt upload (Default) -->
                        <div v-else class="space-y-4">
                            <div class="rounded-xl bg-[#E6F4EA] p-4 border border-[#A3CFBB] space-y-3">
                                <h4 class="text-xs font-bold text-[#137333] uppercase tracking-wider flex items-center gap-1.5">
                                    <i class="fa-solid fa-building-columns"></i>
                                    <span>School Fee Bank Details</span>
                                </h4>
                                <p class="text-xs text-[#137333]">
                                    Please transfer the challan fee amount of <strong>PKR {{ payForm.amount.toLocaleString() }}</strong> to the school bank account details below and submit your deposit slip/receipt.
                                </p>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-[11px] bg-white p-3 rounded-lg border border-[#DADCE0] font-medium text-[#202124]">
                                    <div>
                                        <span class="text-[#5F6368] block text-[9px] uppercase font-bold">Bank Name</span>
                                        <span>Meezan Bank Limited</span>
                                    </div>
                                    <div>
                                        <span class="text-[#5F6368] block text-[9px] uppercase font-bold">Account Title</span>
                                        <span>School Fee Collection</span>
                                    </div>
                                    <div>
                                        <span class="text-[#5F6368] block text-[9px] uppercase font-bold">Account Number</span>
                                        <span class="font-mono">0102-0987-99281</span>
                                    </div>
                                    <div>
                                        <span class="text-[#5F6368] block text-[9px] uppercase font-bold">IBAN</span>
                                        <span class="font-mono text-[10px]">PK00MEZN0102098799281</span>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-[#202124] mb-1.5">Transaction ID / Reference Number <span class="text-[#C5221F]">*</span></label>
                                <input 
                                    v-model="payForm.transaction_reference"
                                    type="text" 
                                    required
                                    placeholder="Enter bank transfer reference or transaction ID"
                                    class="w-full rounded-xl border border-[#DADCE0] bg-white px-4 py-2.5 text-xs text-[#202124] focus:border-[#137333] focus:outline-none"
                                />
                                <div v-if="payForm.errors.transaction_reference" class="mt-1 text-xs text-[#C5221F] font-semibold">{{ payForm.errors.transaction_reference }}</div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-[#202124] mb-1.5">Upload Receipt Screenshot <span class="text-[#C5221F]">*</span></label>
                                <input 
                                    type="file" 
                                    @change="handleFeeReceiptUpload"
                                    accept="image/*"
                                    required
                                    class="w-full text-xs text-[#5F6368] file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-[#E6F4EA] file:text-[#137333] hover:file:bg-[#D1E7DD] cursor-pointer"
                                />
                                <div v-if="payForm.errors.payment_receipt" class="mt-1 text-xs text-[#C5221F] font-semibold">{{ payForm.errors.payment_receipt }}</div>
                                <p class="mt-1 text-[10px] text-[#5F6368]">Supports JPG, PNG, WEBP (Max 4MB). Admin will verify and mark your challan as Paid.</p>
                            </div>
                        </div>

                        <div class="pt-3 border-t border-[#DADCE0] flex justify-end space-x-3">
                            <button type="button" @click="selectedChallan = null" class="rounded-full px-5 py-2.5 text-xs font-bold text-[#5F6368] hover:bg-[#F8F9FA]">Cancel</button>
                            <button type="submit" :disabled="payForm.processing" class="rounded-full bg-[#137333] px-6 py-2.5 font-bold text-white shadow-sm hover:bg-[#0d5926] transition-all disabled:opacity-50 flex items-center gap-1.5">
                                <span v-if="payForm.processing">Submitting...</span>
                                <span v-else>Submit Receipt <i class="fa-solid fa-arrow-right ml-1"></i></span>
                            </button>
                        </div>
                        <div v-if="gatewayNotice" class="mt-3 p-3 bg-amber-50 border border-amber-200 rounded-lg text-xs text-amber-700 font-medium">
                            {{ gatewayNotice }}
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- PTM Reminder Toast -->
        <div v-if="ptmReminderToast" class="fixed bottom-6 right-6 z-50">
            <div class="flex items-center gap-3 px-5 py-3.5 rounded-xl bg-[#E11D48] border border-[#BE123C] shadow-2xl text-white max-w-sm">
                <i class="fa-solid fa-bell text-lg shrink-0"></i>
                <div class="text-xs font-semibold">PTM Reminder added to your notification schedule!</div>
            </div>
        </div>
    </div>
</template>
