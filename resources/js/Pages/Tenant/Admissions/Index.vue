<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, Link, usePage } from '@inertiajs/vue3';
import TenantAdminLayout from '@/Layouts/TenantAdminLayout.vue';

const props = defineProps({
    inquiries: Array,
    applications: Array,
    classes: Array,
    challans: Array,
});

const page = usePage();

// Toast notification state
const toastMessage = ref('');
const toastType = ref('success');
const showToast = ref(false);

const triggerToast = (msg, type = 'success') => {
    toastMessage.value = msg;
    toastType.value = type;
    showToast.value = true;
    setTimeout(() => {
        showToast.value = false;
    }, 4500);
};

// Listen to page flash messages
watch(() => page.props.flash, (flash) => {
    if (flash?.success) {
        triggerToast(flash.success, 'success');
    }
    if (flash?.error) {
        triggerToast(flash.error, 'error');
    }
}, { deep: true, immediate: true });

// Confirm Dialog State
const confirmDialog = ref({
    show: false,
    title: '',
    message: '',
    onConfirm: null,
});

const triggerConfirm = (title, message, callback) => {
    confirmDialog.value = {
        show: true,
        title,
        message,
        onConfirm: () => {
            callback();
            confirmDialog.value.show = false;
        }
    };
};

// Pipeline Step State
const activeStep = ref(1); // 1: Registration, 2: Interview, 3: Fee Challan, 4: Fee Verification, 5: Approval, 6: Student Creation

// Right Drawer slide-over state
const showDrawer = ref(false);
const drawerType = ref(''); // 'new_inquiry', 'new_app', 'view_app', 'enter_marks', 'verify_fee'
const selectedApplication = ref(null);
const selectedInquiry = ref(null);

// Forms
const inquiryForm = useForm({
    student_name: '',
    previous_school: '',
    desired_class_id: props.classes[0]?.id || '',
    parent_name: '',
    parent_phone: '',
    parent_email: '',
    notes: '',
});

const appForm = useForm({
    inquiry_id: null,
    student_name: '',
    class_id: props.classes[0]?.id || '',
    father_name: '',
    father_cnic: '',
    father_phone: '',
    mother_name: '',
    mother_cnic: '',
    test_date: new Date().toISOString().split('T')[0],
});

const meritForm = useForm({
    marks_obtained: 75,
    total_marks: 100,
});

// Helper for finding matching challan
const getChallanForApp = (appId) => {
    return props.challans.find(c => c.student?.admission_number === `ADM-TEMP-${appId}`);
};

// Filtered Lists per Pipeline Step
const filteredList = computed(() => {
    if (activeStep.value === 1) {
        // Registration: Inquiries & Applications with pending evaluation
        return {
            inquiries: props.inquiries.filter(i => i.status === 'new' || i.status === 'contacted'),
            applications: props.applications.filter(a => a.admission_status === 'applied')
        };
    } else if (activeStep.value === 2) {
        // Interview: Test pending evaluation
        return props.applications.filter(a => a.merit_status === 'pending');
    } else if (activeStep.value === 3) {
        // Fee Challan: merit approved but challan not verified paid yet
        return props.applications.filter(a => a.admission_status === 'fee_pending');
    } else if (activeStep.value === 4) {
        // Fee Verification: unpaid challans
        return props.applications.filter(a => {
            if (a.admission_status !== 'fee_pending') return false;
            const challan = getChallanForApp(a.id);
            return challan && challan.status !== 'paid';
        });
    } else if (activeStep.value === 5) {
        // Admission Approval: paid challan, pending final enrollment trigger
        return props.applications.filter(a => {
            if (a.admission_status === 'enrolled') return false;
            if (a.admission_status === 'fee_pending') {
                const challan = getChallanForApp(a.id);
                return challan && challan.status === 'paid';
            }
            return false;
        });
    } else if (activeStep.value === 6) {
        // Student Creation: Fully enrolled active students
        return props.applications.filter(a => a.admission_status === 'enrolled');
    }
    return [];
});

// Actions
const openNewInquiryDrawer = () => {
    inquiryForm.reset();
    drawerType.value = 'new_inquiry';
    showDrawer.value = true;
};

const openNewAppDrawer = (inq = null) => {
    appForm.reset();
    if (inq) {
        appForm.inquiry_id = inq.id;
        appForm.student_name = inq.student_name;
        appForm.class_id = inq.desired_class_id;
        appForm.father_name = inq.parent_name;
        appForm.father_phone = inq.parent_phone;
    }
    drawerType.value = 'new_app';
    showDrawer.value = true;
};

const openEnterMarksDrawer = (app) => {
    selectedApplication.value = app;
    meritForm.marks_obtained = app.test_marks_obtained || 75;
    meritForm.total_marks = app.test_total_marks || 100;
    drawerType.value = 'enter_marks';
    showDrawer.value = true;
};

const openVerifyFeeDrawer = (app) => {
    selectedApplication.value = app;
    drawerType.value = 'verify_fee';
    showDrawer.value = true;
};

const openViewAppDrawer = (app) => {
    selectedApplication.value = app;
    drawerType.value = 'view_app';
    showDrawer.value = true;
};

const submitInquiry = () => {
    inquiryForm.post(route('tenant.admissions.inquiry.store'), {
        onSuccess: () => {
            showDrawer.value = false;
            inquiryForm.reset();
        },
    });
};

const submitApp = () => {
    appForm.post(route('tenant.admissions.application.store'), {
        onSuccess: () => {
            showDrawer.value = false;
            appForm.reset();
        },
    });
};

const submitMerit = () => {
    meritForm.post(route('tenant.admissions.application.merit', selectedApplication.value.id), {
        onSuccess: () => {
            showDrawer.value = false;
            meritForm.reset();
        },
    });
};

const verifyFeeManual = (challanId) => {
    triggerConfirm(
        'Manual Fee Verification',
        'Verify that the parent has paid the Admission Fee Challan at the accounts counter and proceed to enroll student?',
        () => {
            showDrawer.value = false;
            useForm({}).post(route('tenant.admin.admissions.challans.pay_manual', challanId));
        }
    );
};

const enrollStudentFinal = (id) => {
    triggerConfirm(
        'Approve Enrollment',
        'Enroll this qualified candidate and generate official portal credentials?',
        () => {
            useForm({}).post(route('tenant.admissions.application.enroll', id));
        }
    );
};
</script>

<template>
    <TenantAdminLayout>
        <template #header>
            <h2 class="text-xl font-bold text-[#202124] tracking-tight">Admissions Pipeline</h2>
        </template>

        <template #actions>
            <div class="flex items-center space-x-2.5">
                <button @click="openNewInquiryDrawer" class="rounded-full bg-white border border-[#DADCE0] px-4 py-2.5 text-xs font-semibold text-[#1A73E8] hover:bg-[#F8F9FA] transition-all shadow-xs">
                    + Register Inquiry
                </button>
                <button @click="openNewAppDrawer(null)" class="rounded-full bg-[#1A73E8] px-4 py-2.5 text-xs font-semibold text-white shadow-sm hover:bg-[#1557B0] transition-all">
                    + Submit Application
                </button>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Pipeline Horizontal Step Indicator -->
            <div class="bg-white rounded-2xl border border-[#DADCE0] p-6 shadow-xs">
                <h3 class="text-xs font-bold text-[#5F6368] uppercase tracking-wider mb-4">6-Stage Admission Flow Pipeline</h3>
                <div class="grid grid-cols-6 gap-2">
                    <button 
                        @click="activeStep = 1"
                        :class="activeStep === 1 ? 'border-l-4 border-indigo-600 bg-indigo-50/50 text-indigo-700' : 'border-l-4 border-transparent bg-gray-50 text-gray-600 hover:bg-gray-100'"
                        class="p-3 text-left rounded-r-lg transition-all focus:outline-none"
                    >
                        <span class="block text-xxs font-bold text-gray-400">STAGE 1</span>
                        <span class="font-bold text-xs">Registration</span>
                    </button>

                    <button 
                        @click="activeStep = 2"
                        :class="activeStep === 2 ? 'border-l-4 border-indigo-600 bg-indigo-50/50 text-indigo-700' : 'border-l-4 border-transparent bg-gray-50 text-gray-600 hover:bg-gray-100'"
                        class="p-3 text-left rounded-r-lg transition-all focus:outline-none"
                    >
                        <span class="block text-xxs font-bold text-gray-400">STAGE 2</span>
                        <span class="font-bold text-xs">Interview / Test</span>
                    </button>

                    <button 
                        @click="activeStep = 3"
                        :class="activeStep === 3 ? 'border-l-4 border-indigo-600 bg-indigo-50/50 text-indigo-700' : 'border-l-4 border-transparent bg-gray-50 text-gray-600 hover:bg-gray-100'"
                        class="p-3 text-left rounded-r-lg transition-all focus:outline-none"
                    >
                        <span class="block text-xxs font-bold text-gray-400">STAGE 3</span>
                        <span class="font-bold text-xs">Fee Challan</span>
                    </button>

                    <button 
                        @click="activeStep = 4"
                        :class="activeStep === 4 ? 'border-l-4 border-indigo-600 bg-indigo-50/50 text-indigo-700' : 'border-l-4 border-transparent bg-gray-50 text-gray-600 hover:bg-gray-100'"
                        class="p-3 text-left rounded-r-lg transition-all focus:outline-none"
                    >
                        <span class="block text-xxs font-bold text-gray-400">STAGE 4</span>
                        <span class="font-bold text-xs">Fee Verification</span>
                    </button>

                    <button 
                        @click="activeStep = 5"
                        :class="activeStep === 5 ? 'border-l-4 border-indigo-600 bg-indigo-50/50 text-indigo-700' : 'border-l-4 border-transparent bg-gray-50 text-gray-600 hover:bg-gray-100'"
                        class="p-3 text-left rounded-r-lg transition-all focus:outline-none"
                    >
                        <span class="block text-xxs font-bold text-gray-400">STAGE 5</span>
                        <span class="font-bold text-xs">Approval Signoff</span>
                    </button>

                    <button 
                        @click="activeStep = 6"
                        :class="activeStep === 6 ? 'border-l-4 border-indigo-600 bg-indigo-50/50 text-indigo-700' : 'border-l-4 border-transparent bg-gray-50 text-gray-600 hover:bg-gray-100'"
                        class="p-3 text-left rounded-r-lg transition-all focus:outline-none"
                    >
                        <span class="block text-xxs font-bold text-gray-400">STAGE 6</span>
                        <span class="font-bold text-xs">Student Created</span>
                    </button>
                </div>
            </div>

            <!-- Stage 1 View: Registration -->
            <div v-if="activeStep === 1" class="space-y-6">
                <!-- Inquiries Block -->
                <div class="bg-white rounded-2xl border border-[#DADCE0] p-6 shadow-xs space-y-4">
                    <div class="flex justify-between items-center">
                        <h3 class="font-bold text-gray-900 text-base">Stage 1.1: Admission Inquiries</h3>
                        <span class="text-xs text-gray-500 font-medium">Pending Conversion to Application</span>
                    </div>
                    <div class="overflow-x-auto rounded-xl border border-[#DADCE0]">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-[#F8F9FA]">
                                <tr class="text-xs text-[#5F6368] font-bold border-b border-[#DADCE0] uppercase">
                                    <th class="py-3.5 px-4">Code</th>
                                    <th class="py-3.5 px-4">Student Name</th>
                                    <th class="py-3.5 px-4">Desired Class</th>
                                    <th class="py-3.5 px-4">Parent Details</th>
                                    <th class="py-3.5 px-4">Date Registered</th>
                                    <th class="py-3.5 px-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#E8EAED] text-gray-800">
                                <tr v-for="inq in filteredList.inquiries" :key="inq.id" class="hover:bg-[#F8F9FA]/80">
                                    <td class="py-4 px-4 font-mono font-bold text-indigo-600">{{ inq.inquiry_code }}</td>
                                    <td class="py-4 px-4 font-bold">{{ inq.student_name }}</td>
                                    <td class="py-4 px-4">{{ inq.desired_class?.name }}</td>
                                    <td class="py-4 px-4">{{ inq.parent_name }} <span class="block text-xs font-mono text-gray-500">{{ inq.parent_phone }}</span></td>
                                    <td class="py-4 px-4 text-gray-500">{{ new Date(inq.created_at).toLocaleDateString() }}</td>
                                    <td class="py-4 px-4 text-right">
                                        <button @click="openNewAppDrawer(inq)" class="rounded-full bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-1.5 text-xs font-bold shadow-xs">
                                            Convert to Application
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="filteredList.inquiries.length === 0">
                                    <td colspan="6" class="py-8 text-center text-gray-400">No active inquires in registry.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Applications Block -->
                <div class="bg-white rounded-2xl border border-[#DADCE0] p-6 shadow-xs space-y-4">
                    <h3 class="font-bold text-gray-900 text-base">Stage 1.2: Submitted Formal Applications</h3>
                    <div class="overflow-x-auto rounded-xl border border-[#DADCE0]">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-[#F8F9FA]">
                                <tr class="text-xs text-[#5F6368] font-bold border-b border-[#DADCE0] uppercase">
                                    <th class="py-3.5 px-4">Code</th>
                                    <th class="py-3.5 px-4">Student Name</th>
                                    <th class="py-3.5 px-4">Class</th>
                                    <th class="py-3.5 px-4">Father / CNIC</th>
                                    <th class="py-3.5 px-4">Test Date</th>
                                    <th class="py-3.5 px-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#E8EAED] text-gray-800">
                                <tr v-for="app in filteredList.applications" :key="app.id" class="hover:bg-[#F8F9FA]/80">
                                    <td class="py-4 px-4 font-mono font-bold text-indigo-600">{{ app.application_code }}</td>
                                    <td class="py-4 px-4 font-bold">{{ app.student_name }}</td>
                                    <td class="py-4 px-4">{{ app.school_class?.name }}</td>
                                    <td class="py-4 px-4">{{ app.father_name }} <span class="block text-xs font-mono text-gray-500">{{ app.father_phone }}</span></td>
                                    <td class="py-4 px-4 text-gray-500">{{ app.test_date || '-' }}</td>
                                    <td class="py-4 px-4 text-right">
                                        <button @click="openViewAppDrawer(app)" class="rounded-full bg-gray-100 hover:bg-gray-200 text-gray-700 px-3.5 py-1.5 text-xs font-bold">
                                            View Details
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="filteredList.applications.length === 0">
                                    <td colspan="6" class="py-8 text-center text-gray-400">No applications pending test evaluations.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Stage 2 View: Interview / Test Evaluation -->
            <div v-if="activeStep === 2" class="bg-white rounded-2xl border border-[#DADCE0] p-6 shadow-xs space-y-4">
                <h3 class="font-bold text-gray-900 text-base">Stage 2: Enter Interview & Admission Test Marks</h3>
                <div class="overflow-x-auto rounded-xl border border-[#DADCE0]">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-[#F8F9FA]">
                            <tr class="text-xs text-[#5F6368] font-bold border-b border-[#DADCE0] uppercase">
                                <th class="py-3.5 px-4">Code</th>
                                <th class="py-3.5 px-4">Student Name</th>
                                <th class="py-3.5 px-4">Class</th>
                                <th class="py-3.5 px-4">Father / CNIC</th>
                                <th class="py-3.5 px-4">Current Status</th>
                                <th class="py-3.5 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E8EAED] text-gray-800">
                            <tr v-for="app in filteredList" :key="app.id" class="hover:bg-[#F8F9FA]/80">
                                <td class="py-4 px-4 font-mono font-bold text-indigo-600">{{ app.application_code }}</td>
                                <td class="py-4 px-4 font-bold">{{ app.student_name }}</td>
                                <td class="py-4 px-4">{{ app.school_class?.name }}</td>
                                <td class="py-4 px-4">{{ app.father_name }} <span class="block text-xs font-mono text-gray-500">{{ app.father_phone }}</span></td>
                                <td class="py-4 px-4">
                                    <span class="rounded-full bg-amber-50 text-amber-700 px-2.5 py-1 text-xxs font-bold uppercase border border-amber-200">
                                        {{ app.merit_status }}
                                    </span>
                                </td>
                                <td class="py-4 px-4 text-right">
                                    <button @click="openEnterMarksDrawer(app)" class="rounded-full bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-1.5 text-xs font-bold shadow-xs">
                                        Evaluate Marks & Remarks <i class="fa-solid fa-chevron-right ml-1"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="filteredList.length === 0">
                                <td colspan="6" class="py-8 text-center text-gray-400">No applicants pending interview evaluation.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Stage 3 View: Fee Challan -->
            <div v-if="activeStep === 3" class="bg-white rounded-2xl border border-[#DADCE0] p-6 shadow-xs space-y-4">
                <h3 class="font-bold text-gray-900 text-base">Stage 3: Generated Admission Fee Challans</h3>
                <div class="overflow-x-auto rounded-xl border border-[#DADCE0]">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-[#F8F9FA]">
                            <tr class="text-xs text-[#5F6368] font-bold border-b border-[#DADCE0] uppercase">
                                <th class="py-3.5 px-4">Code</th>
                                <th class="py-3.5 px-4">Student Name</th>
                                <th class="py-3.5 px-4">Challan Number</th>
                                <th class="py-3.5 px-4">Admission Fee</th>
                                <th class="py-3.5 px-4">Total Amount</th>
                                <th class="py-3.5 px-4">Payment Status</th>
                                <th class="py-3.5 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E8EAED] text-gray-800">
                            <tr v-for="app in filteredList" :key="app.id" class="hover:bg-[#F8F9FA]/80">
                                <td class="py-4 px-4 font-mono font-bold text-indigo-600">{{ app.application_code }}</td>
                                <td class="py-4 px-4 font-bold">{{ app.student_name }}</td>
                                <td class="py-4 px-4 font-mono text-gray-700">
                                    {{ getChallanForApp(app.id)?.challan_number || 'Generating...' }}
                                </td>
                                <td class="py-4 px-4 font-semibold text-gray-900">
                                    PKR {{ getChallanForApp(app.id)?.admission_fee || '0' }}
                                </td>
                                <td class="py-4 px-4 font-bold text-[#1A73E8]">
                                    PKR {{ getChallanForApp(app.id)?.total_amount || '0' }}
                                </td>
                                <td class="py-4 px-4">
                                    <span :class="getChallanForApp(app.id)?.status === 'paid' ? 'bg-green-50 text-green-700 border-green-200' : 'bg-red-50 text-red-700 border-red-200'" class="rounded-full px-2.5 py-1 text-xxs font-bold uppercase border">
                                        {{ getChallanForApp(app.id)?.status || 'unpaid' }}
                                    </span>
                                </td>
                                <td class="py-4 px-4 text-right">
                                    <a :href="route('tenant.admin.fees.challans.print', getChallanForApp(app.id)?.id)" target="_blank" v-if="getChallanForApp(app.id)" class="rounded-full bg-white border border-[#DADCE0] px-4 py-1.5 text-xs font-bold text-[#1A73E8] hover:bg-[#F8F9FA] inline-flex items-center gap-1">
                                        <i class="fa-solid fa-print"></i> <span>Print Challan</span>
                                    </a>
                                </td>
                            </tr>
                            <tr v-if="filteredList.length === 0">
                                <td colspan="7" class="py-8 text-center text-gray-400">No active challans generated in current step.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Stage 4 View: Fee Verification -->
            <div v-if="activeStep === 4" class="bg-white rounded-2xl border border-[#DADCE0] p-6 shadow-xs space-y-4">
                <h3 class="font-bold text-gray-900 text-base">Stage 4: Admission Fee Payments Verification</h3>
                <div class="overflow-x-auto rounded-xl border border-[#DADCE0]">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-[#F8F9FA]">
                            <tr class="text-xs text-[#5F6368] font-bold border-b border-[#DADCE0] uppercase">
                                <th class="py-3.5 px-4">Code</th>
                                <th class="py-3.5 px-4">Student Name</th>
                                <th class="py-3.5 px-4">Challan Number</th>
                                <th class="py-3.5 px-4">Total Amount Due</th>
                                <th class="py-3.5 px-4">Verifications</th>
                                <th class="py-3.5 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E8EAED] text-gray-800">
                            <tr v-for="app in filteredList" :key="app.id" class="hover:bg-[#F8F9FA]/80">
                                <td class="py-4 px-4 font-mono font-bold text-indigo-600">{{ app.application_code }}</td>
                                <td class="py-4 px-4 font-bold">{{ app.student_name }}</td>
                                <td class="py-4 px-4 font-mono text-gray-500">{{ getChallanForApp(app.id)?.challan_number }}</td>
                                <td class="py-4 px-4 font-extrabold text-[#1A73E8]">PKR {{ getChallanForApp(app.id)?.total_amount }}</td>
                                <td class="py-4 px-4">
                                    <span class="rounded-full bg-red-50 text-red-700 px-2.5 py-1 text-xxs font-bold uppercase border border-red-200">
                                        Awaiting Payment
                                    </span>
                                </td>
                                <td class="py-4 px-4 text-right">
                                    <button @click="openVerifyFeeDrawer(app)" class="rounded-full bg-green-600 hover:bg-green-700 text-white px-4 py-1.5 text-xs font-bold shadow-xs">
                                        Verify Cash Payment <i class="fa-solid fa-circle-check ml-1"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="filteredList.length === 0">
                                <td colspan="6" class="py-8 text-center text-gray-400">No admission challans awaiting manual verification.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Stage 5 View: Approval Signoff -->
            <div v-if="activeStep === 5" class="bg-white rounded-2xl border border-[#DADCE0] p-6 shadow-xs space-y-4">
                <h3 class="font-bold text-gray-900 text-base">Stage 5: Final Admissions Signoff Approval</h3>
                <div class="overflow-x-auto rounded-xl border border-[#DADCE0]">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-[#F8F9FA]">
                            <tr class="text-xs text-[#5F6368] font-bold border-b border-[#DADCE0] uppercase">
                                <th class="py-3.5 px-4">Code</th>
                                <th class="py-3.5 px-4">Student Name</th>
                                <th class="py-3.5 px-4">Class</th>
                                <th class="py-3.5 px-4">Challan Reference</th>
                                <th class="py-3.5 px-4">Merit Evaluated</th>
                                <th class="py-3.5 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E8EAED] text-gray-800">
                            <tr v-for="app in filteredList" :key="app.id" class="hover:bg-[#F8F9FA]/80">
                                <td class="py-4 px-4 font-mono font-bold text-indigo-600">{{ app.application_code }}</td>
                                <td class="py-4 px-4 font-bold">{{ app.student_name }}</td>
                                <td class="py-4 px-4">{{ app.school_class?.name }}</td>
                                <td class="py-4 px-4 font-mono text-xs text-green-700">
                                    {{ getChallanForApp(app.id)?.challan_number }} (Verified Paid)
                                </td>
                                <td class="py-4 px-4 font-bold">
                                    {{ app.test_marks_obtained }} / {{ app.test_total_marks }}
                                </td>
                                <td class="py-4 px-4 text-right">
                                    <button @click="enrollStudentFinal(app.id)" class="rounded-full bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 text-xs font-bold shadow-xs">
                                        Approve & Enroll Student <i class="fa-solid fa-user-plus ml-1"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="filteredList.length === 0">
                                <td colspan="6" class="py-8 text-center text-gray-400">No paid applicants pending final signoff.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Stage 6 View: Student Created -->
            <div v-if="activeStep === 6" class="bg-white rounded-2xl border border-[#DADCE0] p-6 shadow-xs space-y-4">
                <h3 class="font-bold text-gray-900 text-base">Stage 6: Enrolled Students & Accounts</h3>
                <div class="overflow-x-auto rounded-xl border border-[#DADCE0]">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-[#F8F9FA]">
                            <tr class="text-xs text-[#5F6368] font-bold border-b border-[#DADCE0] uppercase">
                                <th class="py-3.5 px-4">Code</th>
                                <th class="py-3.5 px-4">Student Name</th>
                                <th class="py-3.5 px-4">Class</th>
                                <th class="py-3.5 px-4">Father / Phone</th>
                                <th class="py-3.5 px-4">Enrollment Date</th>
                                <th class="py-3.5 px-4">Account Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E8EAED] text-gray-800">
                            <tr v-for="app in filteredList" :key="app.id" class="hover:bg-[#F8F9FA]/80">
                                <td class="py-4 px-4 font-mono font-bold text-indigo-600">{{ app.application_code }}</td>
                                <td class="py-4 px-4 font-bold">{{ app.student_name }}</td>
                                <td class="py-4 px-4">{{ app.school_class?.name }}</td>
                                <td class="py-4 px-4">{{ app.father_name }} <span class="block text-xs font-mono text-gray-500">{{ app.father_phone }}</span></td>
                                <td class="py-4 px-4 text-gray-500">{{ new Date(app.updated_at).toLocaleDateString() }}</td>
                                <td class="py-4 px-4">
                                    <span class="rounded-full bg-green-50 text-green-700 px-2.5 py-1 text-xxs font-bold uppercase border border-green-200">
                                        Account Active
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="filteredList.length === 0">
                                <td colspan="6" class="py-8 text-center text-gray-400">No student enrollment logs found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- RIGHT SIDE SLIDE-OVER DRAWER (FULL HEIGHT) -->
        <div v-if="showDrawer" class="fixed inset-0 z-50 flex justify-end">
            <!-- Backdrop Overlay with blur -->
            <div @click="showDrawer = false" class="absolute inset-0 bg-black/40 backdrop-blur-xs transition-opacity duration-300"></div>

            <!-- Drawer Panel Container -->
            <div class="relative w-full max-w-lg bg-white h-full shadow-2xl flex flex-col transform transition-transform duration-300 ease-in-out border-l border-[#DADCE0]">
                <!-- Header -->
                <div class="flex justify-between items-center px-6 py-5 border-b border-[#DADCE0] bg-[#F8F9FA]">
                    <h3 class="text-base font-bold text-[#202124]">
                        <span v-if="drawerType === 'new_inquiry'">Register Admission Inquiry</span>
                        <span v-else-if="drawerType === 'new_app'">Submit Formal Application</span>
                        <span v-else-if="drawerType === 'enter_marks'">Evaluate Test Marks & Merit</span>
                        <span v-else-if="drawerType === 'verify_fee'">Manual Fee Verification</span>
                        <span v-else-if="drawerType === 'view_app'">Application Details</span>
                    </h3>
                    <button @click="showDrawer = false" class="h-8 w-8 text-[#5F6368] hover:text-[#202124] hover:bg-gray-100 rounded-full flex items-center justify-center transition-colors">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <!-- Form / Details Body Content -->
                <div class="flex-1 overflow-y-auto p-6 space-y-6 text-sm text-[#202124]">
                    
                    <!-- Form 1: New Inquiry -->
                    <form v-if="drawerType === 'new_inquiry'" @submit.prevent="submitInquiry" class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-1.5">Candidate Student Name</label>
                            <input v-model="inquiryForm.student_name" type="text" placeholder="e.g. Kamran Khan" required class="w-full rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500" />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-1.5">Desired Class Grade</label>
                            <select v-model="inquiryForm.desired_class_id" required class="w-full rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-1.5">Previous School Attended</label>
                            <input v-model="inquiryForm.previous_school" type="text" placeholder="e.g. Beaconhouse School System" class="w-full rounded-lg border-gray-300 text-sm" />
                        </div>
                        <div class="border-t border-gray-100 pt-4 space-y-4">
                            <h4 class="font-bold text-xs text-gray-700 uppercase tracking-wider">Parent/Guardian Contact</h4>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 mb-1.5">Parent Full Name</label>
                                <input v-model="inquiryForm.parent_name" type="text" placeholder="e.g. Khan Mohammad" required class="w-full rounded-lg border-gray-300 text-sm" />
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 mb-1.5">Mobile Phone</label>
                                    <input v-model="inquiryForm.parent_phone" type="text" placeholder="03001234567" required class="w-full rounded-lg border-gray-300 text-sm" />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 mb-1.5">Email Address</label>
                                    <input v-model="inquiryForm.parent_email" type="email" placeholder="name@domain.com" class="w-full rounded-lg border-gray-300 text-sm" />
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-1.5">Follow-up Notes / Remarks</label>
                            <textarea v-model="inquiryForm.notes" rows="3" placeholder="Add custom request or follow-up details..." class="w-full rounded-lg border-gray-300 text-sm"></textarea>
                        </div>

                        <!-- Footer Actions -->
                        <div class="flex justify-end gap-3 pt-6 border-t border-gray-100">
                            <button type="button" @click="showDrawer = false" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">Cancel</button>
                            <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700">Save Inquiry</button>
                        </div>
                    </form>

                    <!-- Form 2: New Application -->
                    <form v-if="drawerType === 'new_app'" @submit.prevent="submitApp" class="space-y-4">
                        <div v-if="appForm.inquiry_id" class="p-3 bg-indigo-50 border border-indigo-100 text-indigo-700 rounded-lg text-xs font-semibold">
                            Converting Inquiry reference profile.
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-1.5">Student Full Name</label>
                            <input v-model="appForm.student_name" type="text" required class="w-full rounded-lg border-gray-300 text-sm focus:ring-indigo-500" />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-1.5">Target Class Grade</label>
                            <select v-model="appForm.class_id" required class="w-full rounded-lg border-gray-300 text-sm">
                                <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-1.5">Scheduled Test/Interview Date</label>
                            <input v-model="appForm.test_date" type="date" required class="w-full rounded-lg border-gray-300 text-sm" />
                        </div>

                        <div class="border-t border-gray-100 pt-4 space-y-4">
                            <h4 class="font-bold text-xs text-gray-700 uppercase tracking-wider">Family Information</h4>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 mb-1.5">Father Name</label>
                                    <input v-model="appForm.father_name" type="text" required class="w-full rounded-lg border-gray-300 text-sm" />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 mb-1.5">Father Phone</label>
                                    <input v-model="appForm.father_phone" type="text" required class="w-full rounded-lg border-gray-300 text-sm" />
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 mb-1.5">Father CNIC</label>
                                <input v-model="appForm.father_cnic" type="text" placeholder="e.g. 42101-1234567-1" class="w-full rounded-lg border-gray-300 text-sm" />
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 mb-1.5">Mother Name</label>
                                    <input v-model="appForm.mother_name" type="text" class="w-full rounded-lg border-gray-300 text-sm" />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 mb-1.5">Mother CNIC</label>
                                    <input v-model="appForm.mother_cnic" type="text" class="w-full rounded-lg border-gray-300 text-sm" />
                                </div>
                            </div>
                        </div>

                        <!-- Footer Actions -->
                        <div class="flex justify-end gap-3 pt-6 border-t border-gray-100">
                            <button type="button" @click="showDrawer = false" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">Cancel</button>
                            <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700">Submit Application</button>
                        </div>
                    </form>

                    <!-- Form 3: Enter Marks -->
                    <form v-if="drawerType === 'enter_marks'" @submit.prevent="submitMerit" class="space-y-4">
                        <div class="p-4 bg-gray-50 rounded-xl space-y-1.5 border border-gray-100">
                            <div class="text-xs text-gray-400">Applicant Candidate:</div>
                            <div class="font-bold text-base text-gray-900">{{ selectedApplication?.student_name }}</div>
                            <div class="text-xs text-gray-500">Applying for Class: {{ selectedApplication?.school_class?.name }}</div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-1.5">Admission Test Marks Obtained</label>
                            <input v-model="meritForm.marks_obtained" type="number" step="0.5" required class="w-full rounded-lg border-gray-300 text-sm" />
                        </div>
                        
                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-1.5">Total Marks</label>
                            <input v-model="meritForm.total_marks" type="number" step="1" required class="w-full rounded-lg border-gray-300 text-sm" />
                        </div>

                        <div class="p-3 bg-amber-50 border border-amber-100 rounded-lg text-xs text-amber-700 space-y-1">
                            <div class="font-bold">Merit Condition:</div>
                            <div>Candidates scoring 50% or above are marked as approved, automatically generating admission challans.</div>
                        </div>

                        <!-- Footer Actions -->
                        <div class="flex justify-end gap-3 pt-6 border-t border-gray-100">
                            <button type="button" @click="showDrawer = false" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">Cancel</button>
                            <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700">Compute Merit Decision</button>
                        </div>
                    </form>

                    <!-- Form 4: Manual Verify Fee -->
                    <div v-if="drawerType === 'verify_fee'" class="space-y-6">
                        <div class="p-4 bg-gray-50 rounded-xl space-y-1 border border-gray-100">
                            <div class="text-xs text-gray-400">Verification Profile:</div>
                            <div class="font-bold text-base text-gray-900">{{ selectedApplication?.student_name }}</div>
                            <div class="text-xs text-gray-500">Class: {{ selectedApplication?.school_class?.name }}</div>
                        </div>

                        <div class="space-y-3 bg-white border border-gray-200 rounded-xl p-4 shadow-xxs">
                            <h4 class="font-bold text-xs uppercase text-gray-400 tracking-wider">Admission Challan Breakdowns</h4>
                            <div class="flex justify-between py-1 border-b border-gray-50">
                                <span class="text-gray-500">Challan Number:</span>
                                <span class="font-mono font-bold">{{ getChallanForApp(selectedApplication?.id)?.challan_number }}</span>
                            </div>
                            <div class="flex justify-between py-1 border-b border-gray-50">
                                <span class="text-gray-500">Admission Fee:</span>
                                <span class="font-semibold text-gray-900">PKR {{ getChallanForApp(selectedApplication?.id)?.admission_fee }}</span>
                            </div>
                            <div class="flex justify-between py-1 border-b border-gray-50">
                                <span class="text-gray-500">Registration Fee:</span>
                                <span class="font-semibold text-gray-900">PKR {{ getChallanForApp(selectedApplication?.id)?.registration_fee }}</span>
                            </div>
                            <div class="flex justify-between py-1 border-b border-gray-50">
                                <span class="text-gray-500">Security Deposit:</span>
                                <span class="font-semibold text-gray-900">PKR {{ getChallanForApp(selectedApplication?.id)?.security_fee }}</span>
                            </div>
                            <div class="flex justify-between py-1 border-b border-gray-50">
                                <span class="text-gray-500">First Month Tuition:</span>
                                <span class="font-semibold text-gray-900">PKR {{ getChallanForApp(selectedApplication?.id)?.tuition_fee }}</span>
                            </div>
                            <div class="flex justify-between py-2 text-base font-bold text-gray-900 pt-3">
                                <span>Grand Total:</span>
                                <span class="text-[#1A73E8]">PKR {{ getChallanForApp(selectedApplication?.id)?.total_amount }}</span>
                            </div>
                        </div>

                        <div class="p-3.5 bg-green-50 border border-green-100 rounded-lg text-xs text-green-800 space-y-1">
                            <div class="font-bold">Auto-Enrollment Action:</div>
                            <div>Verifying paid status manually immediately activates the student's portal credentials and assigns homeroom classrooms.</div>
                        </div>

                        <!-- Footer Actions -->
                        <div class="flex justify-end gap-3 pt-6 border-t border-gray-100">
                            <button type="button" @click="showDrawer = false" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">Close</button>
                            <button @click="verifyFeeManual(getChallanForApp(selectedApplication?.id)?.id)" class="px-6 py-2 bg-green-600 text-white rounded-lg text-sm font-semibold hover:bg-green-700 flex items-center gap-1">
                                <i class="fa-solid fa-circle-check"></i> Verify Cash Payment & Enroll
                            </button>
                        </div>
                    </div>

                    <!-- View Application Details -->
                    <div v-if="drawerType === 'view_app'" class="space-y-5">
                        <div class="p-4 bg-gray-50 rounded-xl space-y-1 border border-gray-100 text-center">
                            <div class="text-xs text-gray-400">Application Code:</div>
                            <div class="font-mono font-bold text-lg text-indigo-600">{{ selectedApplication?.application_code }}</div>
                        </div>

                        <div class="space-y-3">
                            <h4 class="font-bold text-xs uppercase text-gray-400 tracking-wider">Candidate Details</h4>
                            <div class="grid grid-cols-2 gap-4 py-2 border-b border-gray-50">
                                <div>
                                    <div class="text-xs text-gray-400">Student Name</div>
                                    <div class="font-bold text-gray-900">{{ selectedApplication?.student_name }}</div>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-400">Target Grade</div>
                                    <div class="font-bold text-gray-900">{{ selectedApplication?.school_class?.name }}</div>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4 py-2 border-b border-gray-50">
                                <div>
                                    <div class="text-xs text-gray-400">Father's Name</div>
                                    <div class="font-bold text-gray-900">{{ selectedApplication?.father_name }}</div>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-400">Father's Phone</div>
                                    <div class="font-bold text-gray-900 font-mono">{{ selectedApplication?.father_phone }}</div>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4 py-2 border-b border-gray-50">
                                <div>
                                    <div class="text-xs text-gray-400">Mother's Name</div>
                                    <div class="font-bold text-gray-900">{{ selectedApplication?.mother_name || '-' }}</div>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-400">Father's CNIC</div>
                                    <div class="font-bold text-gray-900 font-mono">{{ selectedApplication?.father_cnic || '-' }}</div>
                                </div>
                            </div>
                            <div class="py-2 border-b border-gray-50">
                                <div class="text-xs text-gray-400">Admission Stage Status</div>
                                <div class="font-bold text-indigo-600 uppercase text-xs mt-0.5">{{ selectedApplication?.admission_status }}</div>
                            </div>
                        </div>

                        <!-- Footer Actions -->
                        <div class="flex justify-end gap-3 pt-6 border-t border-gray-100">
                            <button type="button" @click="showDrawer = false" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">Close</button>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- Custom Beautiful Alert Dialog (confirm overlay) -->
        <div v-if="confirmDialog.show" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs">
            <div class="bg-white rounded-2xl border border-[#DADCE0] p-6 max-w-md w-full shadow-2xl space-y-4">
                <h3 class="text-base font-bold text-gray-900">{{ confirmDialog.title }}</h3>
                <p class="text-xs text-gray-500 leading-relaxed">{{ confirmDialog.message }}</p>
                <div class="flex justify-end space-x-3 pt-2 border-t border-gray-50">
                    <button @click="confirmDialog.show = false" class="px-4 py-2 border border-[#DADCE0] rounded-full text-xs font-semibold text-gray-600 hover:bg-[#F8F9FA]">
                        Cancel
                    </button>
                    <button @click="confirmDialog.onConfirm" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-full text-xs font-semibold shadow-xs">
                        Confirm & Proceed
                    </button>
                </div>
            </div>
        </div>

        <!-- Toast Notifications Toast-Banner -->
        <div v-if="showToast" class="fixed bottom-5 right-5 z-50 transform transition-all duration-300">
            <div :class="toastType === 'success' ? 'bg-emerald-600 border-emerald-500 text-white' : 'bg-red-600 border-red-500 text-white'" class="flex items-center gap-3 px-5 py-3.5 rounded-xl border shadow-xl">
                <i :class="toastType === 'success' ? 'fa-solid fa-circle-check' : 'fa-solid fa-triangle-exclamation'" class="text-lg"></i>
                <span class="text-xs font-semibold">{{ toastMessage }}</span>
            </div>
        </div>
    </TenantAdminLayout>
</template>
