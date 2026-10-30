<script setup>
import { ref, watchEffect, computed, onMounted, onUnmounted, watch } from 'vue';
import { Link, useForm, usePage, router } from '@inertiajs/vue3';
import TenantAppLayout from '@/Layouts/TenantAppLayout.vue';
import { getAdminNavTree } from '@/Config/tenantAdminNav.js';

const props = defineProps({
    tenant: Object,
    current_user: Object,
    current_role: { type: String, default: 'principal' },
    kpis: Object,
    library_stats: { type: Object, default: null },
    defaulters: Array,
    classes: Array,
    sections: Array,
    subjects: Array,
    notices: Array,
    users: Array,
    students: Array,
    inquiries: Array,
    applications: Array,
    timetables: Array,
    fee_structures: Array,
    fee_overrides: Array,
    staff_attendance_logs: Object,
    staff_leaves: Array,
    student_leaves: { type: Array, default: () => [] },
    exams: Array,
    sports_activities: { type: Array, default: () => [] },
    penalties: { type: Array, default: () => [] },
    finance_stats: { type: Object, default: () => ({}) },
    recent_transactions: { type: Array, default: () => [] },
    pending_vouchers: { type: Array, default: () => [] },
    all_challans: { type: Array, default: () => [] },
    pending_payments: { type: Array, default: () => [] },
    currentPlan: Object,
    saas_plans: Array,
    saas_transactions: Array,
    saas_referrals: Array,
    referral_code: String,
    trial_ends_at: String,
});

const getInitialTab = () => {
    if (typeof window !== 'undefined') {
        const params = new URLSearchParams(window.location.search);
        const urlTab = params.get('tab');
        if (urlTab) return urlTab;
    }
    return 'overview';
};

const activeTab = ref(getInitialTab());

const onPopState = () => {
    if (typeof window !== 'undefined') {
        const params = new URLSearchParams(window.location.search);
        const urlTab = params.get('tab') || 'overview';
        activeTab.value = urlTab;
    }
};

onMounted(() => {
    if (typeof window !== 'undefined') {
        const params = new URLSearchParams(window.location.search);
        const urlTab = params.get('tab');
        if (urlTab && urlTab !== activeTab.value) {
            activeTab.value = urlTab;
        }
        window.addEventListener('popstate', onPopState);
    }
});

onUnmounted(() => {
    if (typeof window !== 'undefined') {
        window.removeEventListener('popstate', onPopState);
    }
});

watch(activeTab, (newTab) => {
    if (typeof window !== 'undefined') {
        const url = new URL(window.location.href);
        if (url.searchParams.get('tab') !== newTab) {
            url.searchParams.set('tab', newTab);
            window.history.replaceState({}, '', url.toString());
        }
    }
});
const isSidebarCollapsed = ref(false);
const isMobileMenuOpen = ref(false);
const isExecutiveView = ref(props.current_role === 'Principal' || props.current_role === 'principal' || props.current_role === 'admin');

const selectedFeeReceipt = ref(null);

const approveFeePaymentReceipt = (id) => {
    if (confirm('Are you sure you want to approve this student fee payment receipt?')) {
        router.post(`/admin/payments/approve/${id}`, {}, {
            onSuccess: () => {
                // Flash message handles toast
            }
        });
    }
};

const rejectFeePaymentReceipt = (id) => {
    if (confirm('Are you sure you want to reject this student fee payment receipt?')) {
        router.post(`/admin/payments/reject/${id}`, {}, {
            onSuccess: () => {
                // Flash message handles toast
            }
        });
    }
};

// Platform Subscription renewal form
const renewalPlan = ref(props.saas_plans?.[0] || null);
const renewalCycle = ref('monthly');
const renewalForm = useForm({
    plan_id: '',
    billing_cycle: 'monthly',
    transaction_reference: '',
    payment_receipt: null,
});

const handleRenewalReceiptUpload = (e) => {
    renewalForm.payment_receipt = e.target.files[0];
};

const submitRenewal = () => {
    if (renewalPlan.value) {
        renewalForm.plan_id = renewalPlan.value.id;
    }
    renewalForm.billing_cycle = renewalCycle.value;
    
    renewalForm.post('/admin/subscription/renew', {
        onSuccess: () => {
            renewalForm.reset('transaction_reference', 'payment_receipt');
        }
    });
};

const logoForm = useForm({
    logo: null,
});
const logoPreviewUrl = ref(null);

const brandingForm = useForm({
    cover_color: props.tenant?.cover_color || '#1A73E8',
    secondary_color: props.tenant?.secondary_color || '#0F172A',
    accent_color: props.tenant?.accent_color || '#3B82F6',
    logo_bg: props.tenant?.logo_bg || 'dark',
    tagline: props.tenant?.tagline || 'Empowering Minds, Shaping Futures',
    hero_heading: props.tenant?.hero_heading || 'Empowering Minds, Shaping Futures',
    hero_description: props.tenant?.hero_description || '',
    about_vision: props.tenant?.about_vision || '',
    academics_description: props.tenant?.academics_description || '',
    phone: props.tenant?.phone || props.tenant?.data?.phone || props.tenant?.admin_phone || '',
    email: props.tenant?.email || props.tenant?.data?.email || props.tenant?.admin_email || '',
    address: props.tenant?.address || props.tenant?.data?.address || '',
});

// Logo display background mode options (Dark contrast, Brand primary, Clean light)
const logoBgOptions = [
    {
        id: 'dark',
        name: 'Midnight Slate',
        badge: 'Recommended for White Logos',
        desc: 'Dark high-contrast tile. White, gold, and transparent logos pop with maximum clarity.',
        bgClass: 'bg-[#0F172A]',
        borderClass: 'border-[#1E293B]',
    },
    {
        id: 'brand',
        name: 'School Brand Primary',
        badge: 'Dynamic Brand Match',
        desc: 'Tile colored with your chosen primary brand color for cohesive school identity.',
        bgClass: '',
        borderClass: 'border-black/20',
    },
    {
        id: 'light',
        name: 'Clean Light',
        badge: 'Classic Light',
        desc: 'Clean white background tile with soft border. Best for dark crests & full-color emblems.',
        bgClass: 'bg-white',
        borderClass: 'border-[#DADCE0]',
    },
];

const previewLogoBgStyle = computed(() => {
    const bgMode = brandingForm.logo_bg || 'dark';
    if (bgMode === 'brand') {
        return {
            backgroundColor: brandingForm.cover_color || '#1A73E8',
            borderColor: 'rgba(0,0,0,0.15)',
        };
    }
    if (bgMode === 'light') {
        return {
            backgroundColor: '#FFFFFF',
            borderColor: '#DADCE0',
        };
    }
    return {
        backgroundColor: '#0F172A',
        borderColor: '#1E293B',
    };
});

// Auto-extracted palette sampled directly from the logo via canvas
const autoExtractedPalette = ref({
    id: 'auto',
    name: 'Auto-Extracted from Logo',
    description: 'Directly sampled from dominant hues in your uploaded logo',
    primary: props.tenant?.cover_color || '#1A73E8',
    secondary: props.tenant?.secondary_color || '#0F172A',
    accent: props.tenant?.accent_color || '#3B82F6',
    isAuto: true,
});

// 4 Curated Educational Palettes
const curatedPalettes = [
    {
        id: 'royal_blue',
        name: 'Royal Scholastic',
        description: 'Vibrant academic blue paired with rich navy & gold amber',
        primary: '#1A73E8',
        secondary: '#0D47A1',
        accent: '#F59E0B',
    },
    {
        id: 'academic_navy',
        name: 'Prestige Navy & Gold',
        description: 'Traditional deep navy with gold crest accents',
        primary: '#0F172A',
        secondary: '#1E293B',
        accent: '#D97706',
    },
    {
        id: 'emerald_mint',
        name: 'Heritage Emerald',
        description: 'Rich forest green with fresh mint highlights',
        primary: '#059669',
        secondary: '#064E3B',
        accent: '#10B981',
    },
    {
        id: 'crimson_amber',
        name: 'Crimson Honors',
        description: 'Distinguished burgundy and warm amber gold',
        primary: '#BE123C',
        secondary: '#4C0519',
        accent: '#F59E0B',
    },
];

const selectedPaletteId = ref(props.tenant?.cover_color ? 'custom' : 'royal_blue');

const selectPalette = (palette) => {
    selectedPaletteId.value = palette.id;
    brandingForm.cover_color = palette.primary;
    brandingForm.secondary_color = palette.secondary;
    brandingForm.accent_color = palette.accent;
};

const isExtractingColors = ref(false);
const extractColorsFromLogo = () => {
    if (!logoForm.logo && !props.tenant?.logo && !usePage().props.tenant?.logo) {
        alert('Please upload or select a school logo first before extracting colors.');
        return;
    }
    const imgSource = logoPreviewUrl.value || props.tenant?.logo || usePage().props.tenant?.logo;
    if (!imgSource) return;
    isExtractingColors.value = true;
    const img = new Image();
    img.crossOrigin = 'Anonymous';
    img.src = imgSource;
    img.onload = () => {
        try {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            canvas.width = img.width || 120;
            canvas.height = img.height || 120;
            ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
            const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height).data;
            
            const colorCounts = {};
            let nonTransparentCount = 0;
            let brightCount = 0;

            for (let i = 0; i < imageData.length; i += 16) {
                const r = imageData[i];
                const g = imageData[i + 1];
                const b = imageData[i + 2];
                const a = imageData[i + 3];

                if (a < 128) continue;
                nonTransparentCount++;
                const brightness = (r + g + b) / 3;
                if (brightness > 220) brightCount++;

                if (brightness > 248 || brightness < 12) continue;
                const saturation = Math.max(r, g, b) - Math.min(r, g, b);
                if (saturation < 15 && brightness > 190) continue;

                const qr = Math.round(r / 32) * 32;
                const qg = Math.round(g / 32) * 32;
                const qb = Math.round(b / 32) * 32;
                const key = `${qr},${qg},${qb}`;

                if (!colorCounts[key]) {
                    colorCounts[key] = { count: 0, rgb: [qr, qg, qb] };
                }
                colorCounts[key].count++;
            }

            const sortedColors = Object.values(colorCounts).sort((a, b) => b.count - a.count);
            const toHex = ([r, g, b]) => '#' + [r, g, b].map(x => {
                const hex = Math.min(255, Math.max(0, x)).toString(16);
                return hex.length === 1 ? '0' + hex : hex;
            }).join('').toUpperCase();

            // If logo is white / predominantly light, automatically set logo_bg to dark
            const isWhiteLogo = nonTransparentCount > 0 && (brightCount / nonTransparentCount) > 0.6;
            if (isWhiteLogo) {
                brandingForm.logo_bg = 'dark';
            }

            if (sortedColors.length > 0) {
                const dominantRgb = sortedColors[0].rgb;
                const primaryColor = toHex(dominantRgb);

                let secondaryRgb = [15, 23, 42];
                if (sortedColors.length > 1) {
                    for (let j = 1; j < sortedColors.length; j++) {
                        const [r2, g2, b2] = sortedColors[j].rgb;
                        const dist = Math.abs(r2 - dominantRgb[0]) + Math.abs(g2 - dominantRgb[1]) + Math.abs(b2 - dominantRgb[2]);
                        if (dist > 70) {
                            secondaryRgb = [r2, g2, b2];
                            break;
                        }
                    }
                }

                let accentRgb = [59, 130, 246];
                if (sortedColors.length > 2) {
                    for (let k = 2; k < sortedColors.length; k++) {
                        const [r3, g3, b3] = sortedColors[k].rgb;
                        const dist1 = Math.abs(r3 - dominantRgb[0]) + Math.abs(g3 - dominantRgb[1]) + Math.abs(b3 - dominantRgb[2]);
                        const dist2 = Math.abs(r3 - secondaryRgb[0]) + Math.abs(g3 - secondaryRgb[1]) + Math.abs(b3 - secondaryRgb[2]);
                        if (dist1 > 50 && dist2 > 50) {
                            accentRgb = [r3, g3, b3];
                            break;
                        }
                    }
                }

                autoExtractedPalette.value.primary = primaryColor;
                autoExtractedPalette.value.secondary = toHex(secondaryRgb);
                autoExtractedPalette.value.accent = toHex(accentRgb);

                selectPalette(autoExtractedPalette.value);
            } else {
                // Monochrome/white logo fallback
                autoExtractedPalette.value.primary = '#1A73E8';
                autoExtractedPalette.value.secondary = '#0F172A';
                autoExtractedPalette.value.accent = '#F59E0B';
                selectPalette(autoExtractedPalette.value);
            }
        } catch (e) {
            console.error('Error extracting colors:', e);
        } finally {
            isExtractingColors.value = false;
        }
    };
    img.onerror = () => {
        isExtractingColors.value = false;
    };
};

const handleLogoSelect = (event) => {
    const file = event.target.files[0];
    if (file) {
        logoForm.logo = file;
        logoPreviewUrl.value = URL.createObjectURL(file);
        setTimeout(() => {
            extractColorsFromLogo();
        }, 150);
    }
};

const submitLogo = () => {
    logoForm.transform((data) => ({
        ...data,
        cover_color: brandingForm.cover_color,
        secondary_color: brandingForm.secondary_color,
        accent_color: brandingForm.accent_color,
        logo_bg: brandingForm.logo_bg || 'dark',
    })).post('/admin/settings/logo', {
        forceFormData: true,
        onSuccess: () => {
            logoForm.reset();
            logoPreviewUrl.value = null;
        },
    });
};

const submitBranding = () => {
    brandingForm.post('/admin/settings/branding', {
        preserveScroll: true,
    });
};

const removeLogo = () => {
    if (confirm('Are you sure you want to remove the custom school logo and revert to the default graduation cap icon?')) {
        useForm({}).delete('/admin/settings/logo');
    }
};

const logout = () => {
    useForm({}).post('/logout');
};

// ================= USER MANAGEMENT BY ROLE =================
const showAddUserModal = ref(false);
const showEditUserModal = ref(false);
const showBulkUserModal = ref(false);
const selectedUserForEdit = ref(null);
const userRoleFilter = ref('all');
const userSearchQuery = ref('');

const userForm = useForm({
    name: '',
    email: '',
    phone: '',
    cnic: '',
    user_type: 'teacher',
    qualification: '',
    homeroom_section_id: '',
    password: '',
});

const generateUserPassword = () => {
    const name = userForm.name || 'user';
    const cleanName = name.toLowerCase().replace(/[^a-z]/g, '');
    const namePart = cleanName.substring(0, 4) || 'user';
    const numLength = 8 - namePart.length;
    let numPart = '';
    for (let i = 0; i < numLength; i++) {
        numPart += Math.floor(Math.random() * 10);
    }
    userForm.password = namePart + numPart;
};

const openCreateUserModal = () => {
    userForm.reset();
    generateUserPassword();
    showAddUserModal.value = true;
};

const adminProfileForm = useForm({
    email: '',
    phone: '',
});

const adminPasswordForm = useForm({
    current_password: '',
    new_password: '',
    new_password_confirmation: '',
});

watchEffect(() => {
    const user = usePage().props.auth?.user;
    if (user) {
        adminProfileForm.email = user.email || '';
        adminProfileForm.phone = user.phone || '';
    }
});

const submitAdminProfileChange = () => {
    adminProfileForm.post('/admin/profile/update', {
        preserveScroll: true,
        onSuccess: () => {
            alert('Profile contact details updated successfully!');
        }
    });
};

const submitAdminPasswordChange = () => {
    adminPasswordForm.post('/admin/profile/password', {
        preserveScroll: true,
        onSuccess: () => {
            adminPasswordForm.reset();
            alert('Password updated securely!');
        }
    });
};

const editUserForm = useForm({
    name: '',
    email: '',
    phone: '',
    cnic: '',
    user_type: 'teacher',
    qualification: '',
    homeroom_section_id: '',
});

const bulkUserForm = useForm({
    csv_file: null,
});

const submitAddUser = () => {
    userForm.post('/admin/users', {
        onSuccess: () => {
            showAddUserModal.value = false;
            userForm.reset();
        },
    });
};

const openEditUser = (user) => {
    selectedUserForEdit.value = user;
    editUserForm.name = user.name;
    editUserForm.email = user.email;
    editUserForm.phone = user.phone;
    editUserForm.cnic = user.cnic;
    editUserForm.user_type = user.user_type;
    editUserForm.qualification = user.qualification;
    showEditUserModal.value = true;
};

const submitEditUser = () => {
    editUserForm.put(`/admin/users/${selectedUserForEdit.value.id}`, {
        onSuccess: () => {
            showEditUserModal.value = false;
        },
    });
};

const toggleUserStatus = (user) => {
    if (confirm(`Are you sure you want to ${user.status === 'active' ? 'deactivate' : 'reactivate'} account for ${user.name} (${user.user_type})? Historical data will be preserved.`)) {
        useForm({}).post(`/admin/users/${user.id}/toggle-status`);
    }
};

const resetUserPassword = (user) => {
    if (confirm(`Generate secure password reset token link for ${user.name} (${user.email})?`)) {
        useForm({}).post(`/admin/users/${user.id}/reset-password`);
    }
};

const deleteUser = (user) => {
    const reason = prompt(`Enter mandatory reason to hard-delete account (${user.name}):\n(Note: Allowed ONLY if zero historical records exist)`);
    if (reason) {
        useForm({ reason }).delete(`/admin/users/${user.id}`);
    }
};

const submitBulkUsers = () => {
    bulkUserForm.post('/admin/imports/users', {
        forceFormData: true,
        onSuccess: () => {
            showBulkUserModal.value = false;
            bulkUserForm.reset();
        },
    });
};

const showStudentCsvModal = ref(false);
const studentCsvForm = useForm({
    csv_file: null,
});
const submitStudentCsv = () => {
    studentCsvForm.post('/admin/imports/students', {
        forceFormData: true,
        onSuccess: () => {
            showStudentCsvModal.value = false;
            studentCsvForm.reset();
        },
    });
};

const showClassCsvModal = ref(false);
const classCsvForm = useForm({
    csv_file: null,
});
const submitClassCsv = () => {
    classCsvForm.post('/admin/imports/classes', {
        forceFormData: true,
        onSuccess: () => {
            showClassCsvModal.value = false;
            classCsvForm.reset();
        },
    });
};

const triggerOALevelPreset = () => {
    if (confirm('⚡ Setup British Curriculum O/A Level Hierarchy Preset?\n\nThis will instantly generate classes, sections, and subjects for:\n• O-Level Year 1 (I-GCSE)\n• O-Level Year 2 (I-GCSE)\n• O-Level Year 3 (I-GCSE)\n• A-Level AS (Year 1)\n• A-Level A2 (Year 2)\n\nProceed right now?')) {
        useForm({}).post('/admin/imports/preset-oa-levels');
    }
};

// ================= STUDENT MANAGEMENT CRUD & LIFECYCLE =================
const showAddStudentModal = ref(false);
const showEditStudentModal = ref(false);
const showStudentStatusModal = ref(false);
const showStudentTransferModal = ref(false);
const showBulkPromoteModal = ref(false);
const showDocumentVaultModal = ref(false);
const selectedStudent = ref(null);
const studentSearchQuery = ref('');
const studentStatusFilter = ref('All');

const studentForm = useForm({
    student_name: '',
    email: '',
    phone: '',
    admission_number: '',
    roll_number: '',
    b_form: '',
    date_of_birth: '',
    gender: 'male',
    admission_date: '',
    current_class_id: '',
    current_section_id: '',
    father_name: '',
    father_phone: '',
});

const editStudentForm = useForm({
    student_name: '',
    phone: '',
    roll_number: '',
    b_form: '',
    date_of_birth: '',
    gender: 'male',
    current_class_id: '',
    current_section_id: '',
});

const statusForm = useForm({
    status: 'Active',
    reason: '',
});

const transferForm = useForm({
    class_id: '',
    section_id: '',
});

const promoteForm = useForm({
    from_class_id: '',
    to_class_id: '',
    to_section_id: '',
    exception_student_ids: [],
});

const documentForm = useForm({
    title: '',
    document_type: 'Birth Certificate / B-Form',
    document_file: null,
});

const submitAddStudent = () => {
    studentForm.post('/admin/students', {
        onSuccess: () => {
            showAddStudentModal.value = false;
            studentForm.reset();
        },
    });
};

const openEditStudent = (student) => {
    selectedStudent.value = student;
    editStudentForm.student_name = student.user?.name || '';
    editStudentForm.phone = student.user?.phone || '';
    editStudentForm.roll_number = student.roll_number || '';
    editStudentForm.b_form = student.b_form || '';
    editStudentForm.date_of_birth = student.date_of_birth || '';
    editStudentForm.gender = student.gender || 'male';
    editStudentForm.current_class_id = student.current_class_id || '';
    editStudentForm.current_section_id = student.current_section_id || '';
    showEditStudentModal.value = true;
};

const submitEditStudent = () => {
    editStudentForm.put(`/admin/students/${selectedStudent.value.id}`, {
        onSuccess: () => {
            showEditStudentModal.value = false;
        },
    });
};

const openStudentStatus = (student) => {
    selectedStudent.value = student;
    statusForm.status = student.status || 'Active';
    statusForm.reason = '';
    showStudentStatusModal.value = true;
};

const submitStudentStatus = () => {
    statusForm.post(`/admin/students/${selectedStudent.value.id}/status`, {
        onSuccess: () => {
            showStudentStatusModal.value = false;
        },
    });
};

const openStudentTransfer = (student) => {
    selectedStudent.value = student;
    transferForm.class_id = student.current_class_id || '';
    transferForm.section_id = student.current_section_id || '';
    showStudentTransferModal.value = true;
};

const submitStudentTransfer = () => {
    transferForm.post(`/admin/students/${selectedStudent.value.id}/transfer`, {
        onSuccess: () => {
            showStudentTransferModal.value = false;
        },
    });
};

const submitBulkPromote = () => {
    promoteForm.post('/admin/students/bulk-promote', {
        onSuccess: () => {
            showBulkPromoteModal.value = false;
            promoteForm.reset();
        },
    });
};

const openDocumentVault = (student) => {
    selectedStudent.value = student;
    documentForm.reset();
    showDocumentVaultModal.value = true;
};

const handleDocumentFileSelect = (event) => {
    documentForm.document_file = event.target.files[0] || null;
};

const submitDocumentUpload = () => {
    documentForm.post(`/admin/students/${selectedStudent.value.id}/documents`, {
        forceFormData: true,
        onSuccess: () => {
            documentForm.reset();
        },
    });
};

const archiveStudent = (student) => {
    const reason = prompt(`Enter mandatory reason to soft-delete / archive ${student.user?.name || 'Student'}:`);
    if (reason) {
        useForm({ reason }).delete(`/admin/students/${student.id}`);
    }
};

// ================= ADMISSIONS REWORK =================
const enrollApplicant = (application) => {
    if (confirm(`Enroll applicant "${application.student_name}" (${application.application_code}) right now?\nThis will automatically provision student user credentials and generate the first tuition fee challan!`)) {
        useForm({}).post(`/admin/admissions/application/${application.id}/enroll`);
    }
};
const oneClickEnroll = enrollApplicant;

// ================= P1 #5 TIMETABLE BUILDER =================
const showTimetableModal = ref(false);
const timetableForm = useForm({
    id: null,
    section_id: '',
    subject_id: '',
    teacher_id: '',
    day_of_week: 'Monday',
    start_time: '08:00',
    end_time: '08:45',
    room_number: 'Room 101'
});

const submitTimetable = () => {
    timetableForm.post('/admin/timetables', {
        onSuccess: () => {
            showTimetableModal.value = false;
            timetableForm.reset();
        }
    });
};

const deleteTimetableSlot = (slot) => {
    if (confirm(`Schedule cancellation: Remove "${slot.subject?.name}" slot on ${slot.day_of_week} (${slot.start_time}-${slot.end_time})?`)) {
        useForm({}).delete(`/admin/timetables/${slot.id}`);
    }
};

// ================= P1 #6 FEE STRUCTURE & MONTHLY INVOICE ENGINE =================
const showFeeOverrideModal = ref(false);
const approveVoucher = (id) => {
    if (confirm("Verify bank receipt and mark this fee challan as FULLY PAID?")) {
        router.post(`/admin/payments/approve/${id}`, {}, { preserveScroll: true });
    }
};

const rejectVoucher = (id) => {
    const remarks = prompt("Enter reason for rejecting this uploaded receipt:", "Blurry image / Bank deposit not reflected in account");
    if (remarks) {
        router.post(`/admin/payments/reject/${id}`, { rejection_remarks: remarks }, { preserveScroll: true });
    }
};

const showMonthlyBillingModal = ref(false);

const classFeeForm = useForm({
    class_id: '',
    admission_fee: 5000,
    registration_fee: 1500,
    security_fee: 3000,
    monthly_tuition: 8500,
    annual_charges: 6000,
    lab_charges: 1200,
    transport_charges: 4500
});

const selectClassForFeeEdit = (cls) => {
    classFeeForm.class_id = cls.id;
    const existing = props.fee_structures?.find(f => f.class_id === cls.id);
    if (existing) {
        classFeeForm.admission_fee = existing.admission_fee;
        classFeeForm.registration_fee = existing.registration_fee;
        classFeeForm.security_fee = existing.security_fee;
        classFeeForm.monthly_tuition = existing.monthly_tuition;
        classFeeForm.annual_charges = existing.annual_charges;
        classFeeForm.lab_charges = existing.lab_charges;
        classFeeForm.transport_charges = existing.transport_charges;
    }
};

const submitClassFeeStructure = () => {
    classFeeForm.post('/admin/fees/structure/class', {
        preserveScroll: true
    });
};

const feeOverrideForm = useForm({
    student_id: '',
    fee_category: 'tuition',
    discount_percentage: 20,
    discount_amount: 0,
    override_reason: 'sibling_discount',
    is_active: true
});

const submitFeeOverride = () => {
    feeOverrideForm.post('/admin/fees/overrides', {
        onSuccess: () => {
            showFeeOverrideModal.value = false;
            feeOverrideForm.reset();
        }
    });
};

const deleteFeeOverride = (overrideId) => {
    if (confirm('Remove this student fee discount / override?')) {
        useForm({}).delete(`/admin/fees/overrides/${overrideId}`);
    }
};

const monthlyBillingForm = useForm({
    month_year: new Date().toISOString().slice(0, 7),
    issue_date: new Date().toISOString().slice(0, 10),
    due_date: new Date(Date.now() + 10 * 86400000).toISOString().slice(0, 10),
    include_classes: []
});

const submitMonthlyBilling = () => {
    if (confirm(`Generate recurring monthly fee challans across ALL active students for ${monthlyBillingForm.month_year}?\nThis will automatically apply sibling/scholarship discounts and add overdue balances.`)) {
        monthlyBillingForm.post('/admin/fees/generate-monthly', {
            onSuccess: () => {
                showMonthlyBillingModal.value = false;
            }
        });
    }
};

// ================= P1 #7 STAFF ATTENDANCE & LEAVE WORKFLOW =================
const showStaffCheckInModal = ref(false);
const showStaffLeaveModal = ref(false);

const staffCheckInForm = useForm({
    user_id: '',
    date: new Date().toISOString().slice(0, 10),
    status: 'present',
    check_in_time: '07:45',
    check_out_time: '14:30',
    remarks: 'Regular attendance logged'
});

const submitStaffCheckIn = () => {
    staffCheckInForm.post('/admin/staff-attendance/check-in', {
        onSuccess: () => {
            showStaffCheckInModal.value = false;
            staffCheckInForm.reset();
        }
    });
};

const staffLeaveForm = useForm({
    user_id: '',
    leave_type: 'casual',
    start_date: new Date().toISOString().slice(0, 10),
    end_date: new Date().toISOString().slice(0, 10),
    reason: 'Family event / Personal obligation'
});

const submitStaffLeave = () => {
    staffLeaveForm.post('/admin/staff-leaves', {
        onSuccess: () => {
            showStaffLeaveModal.value = false;
            staffLeaveForm.reset();
        }
    });
};

const updateLeaveApproval = (leaveId, status) => {
    const remarks = prompt(`Enter Principal's note / remarks for ${status.toUpperCase()}:`, `Leave ${status} by Principal.`);
    if (remarks !== null) {
        useForm({ status, admin_remarks: remarks }).post(`/admin/staff-leaves/${leaveId}/status`);
    }
};

const updateStudentLeaveApproval = (leaveId, status) => {
    const remarks = prompt(`Enter remarks for student leave ${status.toUpperCase()} (optional):`, '');
    if (remarks !== null) {
        useForm({ status, admin_remarks: remarks }).post(`/admin/student-leaves/${leaveId}/status`);
    }
};

// ================= P1 #9 EXAM GRADEBOOK & TERM REPORTS =================
const showCreateExamModal = ref(false);
const examForm = useForm({
    id: null,
    academic_year_id: 1,
    term_id: null,
    name: 'Mid-Term Examination 2026',
    start_date: new Date().toISOString().slice(0, 10),
    end_date: new Date(Date.now() + 7 * 86400000).toISOString().slice(0, 10),
    is_published: true
});

const submitExamDefinition = () => {
    examForm.post('/admin/exams', {
        onSuccess: () => {
            showCreateExamModal.value = false;
            examForm.reset();
        }
    });
};

const gradebookForm = useForm({
    exam_id: '',
    subject_id: '',
    marks: []
});

const loadGradebookStudents = () => {
    if (!gradebookForm.exam_id || !gradebookForm.subject_id) return;
    const selectedSub = props.subjects?.find(s => s.id == gradebookForm.subject_id);
    const classId = selectedSub?.class_id;
    if (!classId) return;

    const classStudents = props.students?.filter(st => st.current_class_id == classId && st.status === 'active') || [];
    gradebookForm.marks = classStudents.map(st => ({
        student_id: st.id,
        student_name: st.user?.name || `Student #${st.admission_number}`,
        roll_number: st.roll_number || st.admission_number,
        theory_marks: 85,
        practical_marks: 10,
        viva_marks: 5,
        teacher_remarks: 'Good understanding of concepts'
    }));
};

const submitBulkGradebook = () => {
    gradebookForm.post('/admin/exams/gradebook/bulk-save', {
        preserveScroll: true,
        onSuccess: () => {
            alert('Exam marks and automated grades (GPA/Letter Grades) computed and saved successfully!');
        }
    });
};

// ================= CLASSES, SECTIONS, NOTICES & DEFAULTERS HELPER FUNCTIONS =================
const showAddClassModal = ref(false);
const classForm = useForm({
    name: 'Grade 6',
    level: 'middle',
    numeric_grade: 6
});
const submitClass = () => {
    classForm.post('/admin/classes', {
        onSuccess: () => {
            showAddClassModal.value = false;
            classForm.reset();
        }
    });
};

const showAddSectionModal = ref(false);
const sectionForm = useForm({
    class_id: props.classes?.[0]?.id || '',
    name: 'A',
    room_number: 'Room 101',
    class_teacher_id: ''
});
const openAddSection = (classId) => {
    sectionForm.class_id = classId;
    showAddSectionModal.value = true;
};
const submitSection = () => {
    sectionForm.post('/admin/sections', {
        onSuccess: () => {
            showAddSectionModal.value = false;
            sectionForm.reset();
        }
    });
};

const showAddNoticeModal = ref(false);
const noticeForm = useForm({
    title: '',
    content: '',
    target_audience: 'all',
    target_class_id: null,
    category: 'General Circular',
    publish_date: new Date().toISOString().slice(0, 10),
    video_url: '',
    video_file: null,
    send_sms_alert: true,
    send_whatsapp_alert: true
});
const submitNotice = () => {
    noticeForm.post('/admin/notices', {
        onSuccess: () => {
            showAddNoticeModal.value = false;
            noticeForm.reset();
        }
    });
};

const remindDefaulter = (id) => {
    router.post(`/admin/defaulters/${id}/remind`);
};
const waiveDefaulter = (id) => {
    const discount = prompt('Enter discount / late fee waiver amount (in PKR):', '500');
    if (discount !== null) {
        useForm({ discount }).post(`/admin/defaulters/${id}/waive`);
    }
};

// Sports & Activities Management State
const showSportsModal = ref(false);
const editingSports = ref(null);
const sportsForm = useForm({
    name: '',
    category: 'Sports',
    schedule: '',
    venue: '',
    max_participants: 30,
    coach_user_id: ''
});

const openCreateSportsModal = () => {
    editingSports.value = null;
    sportsForm.reset();
    showSportsModal.value = true;
};

const openEditSportsModal = (activity) => {
    editingSports.value = activity;
    sportsForm.name = activity.name;
    sportsForm.category = activity.category;
    sportsForm.schedule = activity.schedule;
    sportsForm.venue = activity.venue;
    sportsForm.max_participants = activity.max_participants;
    sportsForm.coach_user_id = activity.coach_user_id || '';
    showSportsModal.value = true;
};

const submitSportsForm = () => {
    if (editingSports.value) {
        sportsForm.put(`/admin/sports/${editingSports.value.id}`, {
            onSuccess: () => { showSportsModal.value = false; sportsForm.reset(); }
        });
    } else {
        sportsForm.post('/admin/sports', {
            onSuccess: () => { showSportsModal.value = false; sportsForm.reset(); }
        });
    }
};

const deleteSports = (id) => {
    if (confirm('Are you sure you want to remove this sports activity?')) {
        router.delete(`/admin/sports/${id}`);
    }
};

// Disciplinary Penalties Management State
const showPenaltyModal = ref(false);
const penaltyForm = useForm({
    student_id: '',
    penalty_type: 'Disciplinary Violation',
    amount: 500,
    reason: '',
    due_date: new Date(Date.now() + 7 * 86400000).toISOString().slice(0, 10)
});

const openPenaltyModal = (studentId = '') => {
    penaltyForm.reset();
    if (studentId) penaltyForm.student_id = studentId;
    showPenaltyModal.value = true;
};

const submitPenaltyForm = () => {
    penaltyForm.post('/admin/penalties', {
        onSuccess: () => { showPenaltyModal.value = false; penaltyForm.reset(); }
    });
};

const updatePenaltyStatus = (id, newStatus) => {
    router.post(`/admin/penalties/${id}/status`, { status: newStatus });
};

const deletePenalty = (id) => {
    if (confirm('Delete this penalty record?')) {
        router.delete(`/admin/penalties/${id}`);
    }
};

const sendReminder = remindDefaulter;

// Modal Aliases for Header Quick Action Triggers
const showStudentModal = showAddStudentModal;
const showNoticeModal = showAddNoticeModal;
const showInquiryModal = ref(false);
const showClassModal = showAddClassModal;
const showFeeStructureModal = showMonthlyBillingModal;
const showExamModal = showCreateExamModal;

const downloadReport = (type) => {
    window.location.href = `/admin/api/reports/export?type=${type}`;
};

// ================= CHARTS AND GRAPHS DATA COMPUTATIONS =================
const hoveredBar = ref(null);

const maxStudents = computed(() => {
    if (!props.classes || props.classes.length === 0) return 10;
    return Math.max(...props.classes.map(c => c.students_count || 0), 10);
});

const chartHeight = 160;
const chartWidth = 400;
const padding = { top: 20, right: 10, bottom: 30, left: 40 };

const chartBars = computed(() => {
    if (!props.classes || props.classes.length === 0) return [];
    const count = props.classes.length;
    const barWidth = Math.max(12, Math.min(36, (chartWidth / count) * 0.6));
    const spacing = (chartWidth / count);
    
    return props.classes.map((c, index) => {
        const x = padding.left + (index * spacing) + (spacing - barWidth) / 2;
        const height = (c.students_count / maxStudents.value) * chartHeight;
        const y = padding.top + chartHeight - height;
        return {
            classId: c.id,
            name: c.name,
            studentsCount: c.students_count,
            x,
            y,
            width: barWidth,
            height,
            level: c.level
        };
    });
});

const chartYGridLines = computed(() => {
    const lines = [];
    const maxVal = maxStudents.value;
    for (let i = 0; i <= 4; i++) {
        const value = Math.round((maxVal / 4) * i);
        const y = padding.top + chartHeight - (i * (chartHeight / 4));
        lines.push({ value, y });
    }
    return lines;
});

const feeDoughnutData = computed(() => {
    const collected = props.kpis?.fee_collected_month || 0;
    const outstanding = props.kpis?.outstanding_fees || 0;
    const total = collected + outstanding;
    
    const r = 50;
    const circ = 2 * Math.PI * r; // ~314.159
    
    if (total === 0) {
        return [
            { label: 'Collected', value: 0, percentage: '0.0', strokeDasharray: `${circ}`, strokeDashoffset: `${circ}`, angle: 0, color: '#10B981' },
            { label: 'Outstanding', value: 0, percentage: '0.0', strokeDasharray: `${circ}`, strokeDashoffset: `${circ}`, angle: 0, color: '#E11D48' }
        ];
    }
    
    const colPercent = (collected / total) * 100;
    const outPercent = (outstanding / total) * 100;
    
    const colOffset = circ * (1 - collected / total);
    const outOffset = circ * (1 - outstanding / total);
    
    const colAngle = 0;
    const outAngle = (collected / total) * 360;
    
    return [
        {
            label: 'Collected',
            value: collected,
            percentage: colPercent.toFixed(1),
            strokeDasharray: `${circ}`,
            strokeDashoffset: `${colOffset}`,
            angle: colAngle,
            color: '#10B981',
        },
        {
            label: 'Outstanding',
            value: outstanding,
            percentage: outPercent.toFixed(1),
            strokeDasharray: `${circ}`,
            strokeDashoffset: `${outOffset}`,
            angle: outAngle,
            color: '#E11D48',
        }
    ];
});

const collectionPercentage = computed(() => {
    const target = props.finance_stats?.monthly_target || 0;
    const collected = props.kpis?.fee_collected_month || 0;
    if (target === 0) return collected > 0 ? 100 : 0;
    return Math.round((collected / target) * 100);
});

const userRole = computed(() => {
    const r = (props.current_role || props.current_user?.user_type || 'principal').toLowerCase();
    return r;
});

const canAccess = (moduleName) => {
    const role = userRole.value;
    if (['admin', 'principal', 'vice_principal'].includes(role)) {
        return true;
    }
    if (role === 'librarian') {
        return ['dashboard', 'overview', 'notices', 'calendar', 'library'].includes(moduleName);
    }
    if (role === 'accountant') {
        return ['dashboard', 'overview', 'fees', 'defaulters', 'fee_approvals', 'finance', 'hr', 'notices'].includes(moduleName);
    }
    if (role === 'registrar' || role === 'receptionist') {
        return ['dashboard', 'overview', 'admissions', 'students', 'classes', 'certificates', 'notices', 'calendar'].includes(moduleName);
    }
    return false;
};

const navItems = computed(() => {
    return getAdminNavTree({
        role: userRole.value || props.current_role,
        canAccess: canAccess,
        defaultersCount: props.defaulters?.length,
        pendingApprovalsCount: props.pending_payments?.length,
        pendingInquiriesCount: props.inquiries?.filter(i => i.status === 'pending')?.length,
    });
});

const pendingActionsCount = computed(() => {
    let count = 0;
    if (props.pending_payments?.length) count += props.pending_payments.length;
    if (props.student_leaves?.filter(l => l.status === 'pending')?.length) count += props.student_leaves.filter(l => l.status === 'pending').length;
    if (props.staff_leaves?.filter(l => l.status === 'pending')?.length) count += props.staff_leaves.filter(l => l.status === 'pending').length;
    if (props.inquiries?.filter(i => i.status === 'pending')?.length) count += props.inquiries.filter(i => i.status === 'pending').length;
    return count;
});

const currentTabTitle = computed(() => {
    switch (activeTab.value) {
        case 'overview': return 'Institutional Command Center';
        case 'users': return 'Staff & User Management';
        case 'students': return 'Student Directory & Lifecycle';
        case 'admissions': return 'Admissions & Inquiries';
        case 'classes': return 'Class & Section Rosters';
        case 'timetables': return 'Academic Timetables';
        case 'fees': return 'Fee Structures & Invoices';
        case 'defaulters': return 'Overdue Accounts & Defaulters';
        case 'fee_approvals': return 'Fee Payment Approvals';
        case 'staff_attendance': return 'Staff Attendance & Leaves';
        case 'exams': return 'Exams & Gradebook';
        case 'sports': return 'Sports & Extracurriculars';
        case 'penalties': return 'Disciplinary & Penalties';
        case 'notices': return 'Notice Board';
        case 'reports': return 'Database Reports & Export';
        case 'subscription': return 'Platform License';
        case 'settings': return 'School Branding & Settings';
        default: return 'Institutional Command Center';
    }
});

const currentTabDescription = computed(() => {
    switch (activeTab.value) {
        case 'overview': return 'Real-time overview of institutional operations, student attendance, and fee collection.';
        case 'users': return 'Manage faculty, administrative personnel, and role-based permissions.';
        case 'students': return 'Maintain student enrollment profiles, documents, and academic status.';
        case 'admissions': return 'Track public inquiries, applications, merit evaluation, and enrollments.';
        case 'classes': return 'Monitor classroom rosters, teacher assignments, and capacity limits.';
        case 'timetables': return 'Classroom period schedules and teacher timetable matrix.';
        case 'fees': return 'Fee categories, monthly invoice generation, and student scholarship overrides.';
        case 'defaulters': return 'Track overdue fee challans, dispatch payment reminders, and manage late fees.';
        case 'fee_approvals': return 'Verify student bank deposits and approve or reject payment receipts.';
        case 'staff_attendance': return 'Staff daily attendance records and leave authorization requests.';
        case 'exams': return 'Examination terms, mark entry sheets, and report card generation.';
        case 'sports': return 'Athletic activities, teams, and tournament rosters.';
        case 'penalties': return 'Student disciplinary warnings, fines, and resolution history.';
        case 'notices': return 'Publish and broadcast circulars across teachers, students, and parents.';
        case 'reports': return 'Export school data tables to CSV for offline analysis and auditing.';
        case 'subscription': return 'School SaaS platform tier, billing cycle, and license renewal.';
        case 'settings': return 'Configure school logo, portal branding, and payment gateway credentials.';
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
        @tab-change="tab => activeTab = tab"
        role-title="Principal"
    >
        <template #header-actions>
            <button v-if="activeTab === 'users'" @click="openCreateUserModal" class="px-3.5 py-2 rounded-xl bg-[#0B0D14] hover:bg-[#2F6BFF] text-white text-xs font-semibold shadow-2xs transition-colors flex items-center gap-1.5 cursor-pointer">
                <i class="fa-solid fa-plus text-2xs"></i>
                <span>Add Staff User</span>
            </button>
            <button v-if="activeTab === 'students'" @click="showAddStudentModal = true" class="px-3.5 py-2 rounded-xl bg-[#0B0D14] hover:bg-[#2F6BFF] text-white text-xs font-semibold shadow-2xs transition-colors flex items-center gap-1.5 cursor-pointer">
                <i class="fa-solid fa-user-plus text-2xs"></i>
                <span>Add Student</span>
            </button>
            <button v-if="activeTab === 'notices'" @click="showAddNoticeModal = true" class="px-3.5 py-2 rounded-xl bg-[#0B0D14] hover:bg-[#2F6BFF] text-white text-xs font-semibold shadow-2xs transition-colors flex items-center gap-1.5 cursor-pointer">
                <i class="fa-solid fa-bullhorn text-2xs"></i>
                <span>Post Circular</span>
            </button>
            <button v-if="activeTab === 'admissions'" @click="router.visit('/admin/admissions')" class="px-3.5 py-2 rounded-xl bg-[#0B0D14] hover:bg-[#2F6BFF] text-white text-xs font-semibold shadow-2xs transition-colors flex items-center gap-1.5 cursor-pointer">
                <i class="fa-solid fa-plus text-2xs"></i>
                <span>New Inquiry</span>
            </button>
            <button v-if="activeTab === 'classes'" @click="showAddClassModal = true" class="px-3.5 py-2 rounded-xl bg-[#0B0D14] hover:bg-[#2F6BFF] text-white text-xs font-semibold shadow-2xs transition-colors flex items-center gap-1.5 cursor-pointer">
                <i class="fa-solid fa-plus text-2xs"></i>
                <span>Create Class</span>
            </button>
            <button v-if="activeTab === 'timetables'" @click="showTimetableModal = true" class="px-3.5 py-2 rounded-xl bg-[#0B0D14] hover:bg-[#2F6BFF] text-white text-xs font-semibold shadow-2xs transition-colors flex items-center gap-1.5 cursor-pointer">
                <i class="fa-solid fa-plus text-2xs"></i>
                <span>Schedule Period</span>
            </button>
            <button v-if="activeTab === 'fees'" @click="showMonthlyBillingModal = true" class="px-3.5 py-2 rounded-xl bg-[#0B0D14] hover:bg-[#2F6BFF] text-white text-xs font-semibold shadow-2xs transition-colors flex items-center gap-1.5 cursor-pointer">
                <i class="fa-solid fa-plus text-2xs"></i>
                <span>Generate Monthly Billing</span>
            </button>
            <button v-if="activeTab === 'exams'" @click="showCreateExamModal = true" class="px-3.5 py-2 rounded-xl bg-[#0B0D14] hover:bg-[#2F6BFF] text-white text-xs font-semibold shadow-2xs transition-colors flex items-center gap-1.5 cursor-pointer">
                <i class="fa-solid fa-plus text-2xs"></i>
                <span>Create Exam Term</span>
            </button>
            <button v-if="activeTab === 'sports'" @click="showSportsModal = true" class="px-3.5 py-2 rounded-xl bg-[#0B0D14] hover:bg-[#2F6BFF] text-white text-xs font-semibold shadow-2xs transition-colors flex items-center gap-1.5 cursor-pointer">
                <i class="fa-solid fa-plus text-2xs"></i>
                <span>New Activity</span>
            </button>
            <button v-if="activeTab === 'penalties'" @click="showPenaltyModal = true" class="px-3.5 py-2 rounded-xl bg-[#0B0D14] hover:bg-[#2F6BFF] text-white text-xs font-semibold shadow-2xs transition-colors flex items-center gap-1.5 cursor-pointer">
                <i class="fa-solid fa-gavel text-2xs"></i>
                <span>Issue Penalty</span>
            </button>
        </template>

        <!-- ================= TAB: OVERVIEW (Institutional Command Center) ================= -->
        <div v-if="activeTab === 'overview'" class="space-y-6">
            <!-- Action Items Requiring Attention -->
            <div v-if="pendingActionsCount > 0" class="rounded-2xl border border-[#FDE68A] bg-[#FFFBEB] p-4 sm:p-5 shadow-2xs">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <span class="h-8 w-8 rounded-xl bg-[#F59E0B] text-white flex items-center justify-center flex-shrink-0 text-xs shadow-2xs">
                            <i class="fa-solid fa-bell"></i>
                        </span>
                        <div>
                            <h3 class="text-xs font-bold text-[#92400E] uppercase tracking-wider">Action Items Requiring Attention</h3>
                            <p class="text-xs text-[#B45309] mt-0.5">{{ pendingActionsCount }} operational item(s) pending administrative authorization or review.</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <button 
                            v-if="pending_payments?.length" 
                            @click="activeTab = 'fee_approvals'" 
                            class="px-3 py-1.5 rounded-lg bg-white border border-[#FDE68A] text-[#92400E] hover:bg-[#FEF3C7] text-xs font-semibold shadow-2xs transition-colors flex items-center gap-1.5 cursor-pointer"
                        >
                            <span class="raibix-dot h-1.5 w-1.5"></span>
                            <span>{{ pending_payments.length }} Fee Slip(s)</span>
                        </button>
                        <button 
                            v-if="student_leaves?.filter(l => l.status === 'pending')?.length" 
                            @click="activeTab = 'staff_attendance'" 
                            class="px-3 py-1.5 rounded-lg bg-white border border-[#FDE68A] text-[#92400E] hover:bg-[#FEF3C7] text-xs font-semibold shadow-2xs transition-colors flex items-center gap-1.5 cursor-pointer"
                        >
                            <span class="raibix-dot h-1.5 w-1.5"></span>
                            <span>{{ student_leaves.filter(l => l.status === 'pending').length }} Student Leave(s)</span>
                        </button>
                        <button 
                            v-if="staff_leaves?.filter(l => l.status === 'pending')?.length" 
                            @click="activeTab = 'staff_attendance'" 
                            class="px-3 py-1.5 rounded-lg bg-white border border-[#FDE68A] text-[#92400E] hover:bg-[#FEF3C7] text-xs font-semibold shadow-2xs transition-colors flex items-center gap-1.5 cursor-pointer"
                        >
                            <span class="raibix-dot h-1.5 w-1.5"></span>
                            <span>{{ staff_leaves.filter(l => l.status === 'pending').length }} Staff Leave(s)</span>
                        </button>
                        <button 
                            v-if="inquiries?.filter(i => i.status === 'pending')?.length" 
                            @click="activeTab = 'admissions'" 
                            class="px-3 py-1.5 rounded-lg bg-white border border-[#FDE68A] text-[#92400E] hover:bg-[#FEF3C7] text-xs font-semibold shadow-2xs transition-colors flex items-center gap-1.5 cursor-pointer"
                        >
                            <span class="raibix-dot h-1.5 w-1.5"></span>
                            <span>{{ inquiries.filter(i => i.status === 'pending').length }} Inquiries</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Executive Overview Cards (Actual Data Only) -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Total Active Students -->
                <div class="rounded-2xl p-5 bg-white border border-[#E5E7EB] shadow-2xs flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-2xs font-bold uppercase tracking-wider text-[#5B6270]">Total Students</span>
                        <span class="h-8 w-8 rounded-xl bg-[#F8F9FA] text-[#0B0D14] border border-[#E5E7EB] flex items-center justify-center text-xs">
                            <i class="fa-solid fa-graduation-cap"></i>
                        </span>
                    </div>
                    <div class="mt-4 flex items-baseline justify-between">
                        <span class="text-2xl font-bold tracking-tight text-[#0B0D14]">{{ kpis?.total_students || 0 }}</span>
                        <span class="text-3xs font-semibold text-[#166534] bg-[#F0FDF4] border border-[#BBF7D0] px-2 py-0.5 rounded-full">Active</span>
                    </div>
                    <div class="mt-2 text-3xs text-[#5B6270]">
                        Across {{ classes?.length || 0 }} classes & {{ sections?.length || 0 }} sections
                    </div>
                </div>

                <!-- Daily Attendance Rate -->
                <div class="rounded-2xl p-5 bg-white border border-[#E5E7EB] shadow-2xs flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-2xs font-bold uppercase tracking-wider text-[#5B6270]">Today's Attendance</span>
                        <span class="h-8 w-8 rounded-xl bg-[#F8F9FA] text-[#0B0D14] border border-[#E5E7EB] flex items-center justify-center text-xs">
                            <i class="fa-solid fa-calendar-check"></i>
                        </span>
                    </div>
                    <div class="mt-4 flex items-baseline justify-between">
                        <span class="text-2xl font-bold tracking-tight text-[#0B0D14]">{{ kpis?.attendance_rate || 0 }}%</span>
                        <span class="text-3xs font-semibold text-[#166534] bg-[#F0FDF4] border border-[#BBF7D0] px-2 py-0.5 rounded-full">Verified</span>
                    </div>
                    <div class="mt-2 text-3xs text-[#5B6270]">
                        Session {{ tenant?.academic_session || '2026-2027' }}
                    </div>
                </div>

                <!-- Fee Collection (Month) -->
                <div v-if="canAccess('fees')" class="rounded-2xl p-5 bg-white border border-[#E5E7EB] shadow-2xs flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-2xs font-bold uppercase tracking-wider text-[#5B6270]">Fee Collection</span>
                        <span class="h-8 w-8 rounded-xl bg-[#F8F9FA] text-[#0B0D14] border border-[#E5E7EB] flex items-center justify-center text-xs">
                            <i class="fa-solid fa-receipt"></i>
                        </span>
                    </div>
                    <div class="mt-4 flex items-baseline justify-between">
                        <span class="text-2xl font-bold tracking-tight text-[#0B0D14]">PKR {{ (kpis?.fee_collected_month || 0).toLocaleString() }}</span>
                        <span class="text-3xs font-semibold text-[#2F6BFF] bg-[#EEF3FF] border border-[#D2E3FC] px-2 py-0.5 rounded-full">{{ collectionPercentage }}%</span>
                    </div>
                    <div class="mt-2 text-3xs text-[#5B6270]">
                        Target: PKR {{ (finance_stats?.monthly_target || 0).toLocaleString() }}
                    </div>
                </div>

                <!-- Outstanding Accounts -->
                <div v-if="canAccess('fees')" class="rounded-2xl p-5 bg-white border border-[#E5E7EB] shadow-2xs flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-2xs font-bold uppercase tracking-wider text-[#5B6270]">Outstanding Dues</span>
                        <span class="h-8 w-8 rounded-xl bg-[#FEF2F2] text-[#DC2626] border border-[#FECACA] flex items-center justify-center text-xs">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </span>
                    </div>
                    <div class="mt-4 flex items-baseline justify-between">
                        <span class="text-2xl font-bold tracking-tight text-[#991B1B]">PKR {{ (kpis?.outstanding_fees || 0).toLocaleString() }}</span>
                        <span class="text-3xs font-semibold text-[#991B1B] bg-[#FEF2F2] border border-[#FECACA] px-2 py-0.5 rounded-full">{{ defaulters?.length || 0 }} Accounts</span>
                    </div>
                    <div class="mt-2 text-3xs text-[#5B6270]">
                        Overdue student fee challans
                    </div>
                </div>

                <!-- Alternate Librarian Summary -->
                <div v-else-if="library_stats" class="rounded-2xl p-5 bg-white border border-[#E5E7EB] shadow-2xs flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-2xs font-bold uppercase tracking-wider text-[#5B6270]">Library Catalog</span>
                        <span class="h-8 w-8 rounded-xl bg-[#F8F9FA] text-[#0B0D14] border border-[#E5E7EB] flex items-center justify-center text-xs">
                            <i class="fa-solid fa-book-open"></i>
                        </span>
                    </div>
                    <div class="mt-4 flex items-baseline justify-between">
                        <span class="text-2xl font-bold tracking-tight text-[#0B0D14]">{{ library_stats.total_books }}</span>
                        <span class="text-3xs font-semibold text-[#166534] bg-[#F0FDF4] px-2 py-0.5 rounded-full">{{ library_stats.issued_books }} Issued</span>
                    </div>
                    <div class="mt-2 text-3xs text-[#5B6270]">
                        Books in circulation
                    </div>
                </div>
            </div>

            <!-- Quick Administrative Actions Toolbar -->
            <div class="rounded-2xl border border-[#E5E7EB] bg-white p-4 shadow-2xs">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <span class="raibix-dot h-2 w-2"></span>
                        <span class="text-xs font-semibold text-[#0B0D14]">Common Administrative Actions</span>
                    </div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <button v-if="canAccess('admissions')" @click="activeTab = 'admissions'" class="px-3 py-1.5 rounded-xl bg-[#F8F9FA] hover:bg-[#F4F5F8] border border-[#E5E7EB] text-xs font-semibold text-[#0B0D14] transition-colors flex items-center gap-1.5 shadow-2xs cursor-pointer">
                            <i class="fa-solid fa-user-plus text-2xs"></i>
                            <span>Admit Student</span>
                        </button>
                        <button v-if="canAccess('notices')" @click="showAddNoticeModal = true" class="px-3 py-1.5 rounded-xl bg-[#F8F9FA] hover:bg-[#F4F5F8] border border-[#E5E7EB] text-xs font-semibold text-[#0B0D14] transition-colors flex items-center gap-1.5 shadow-2xs cursor-pointer">
                            <i class="fa-solid fa-bullhorn text-2xs"></i>
                            <span>Post Circular</span>
                        </button>
                        <button v-if="canAccess('fees')" @click="activeTab = 'fees'" class="px-3 py-1.5 rounded-xl bg-[#F8F9FA] hover:bg-[#F4F5F8] border border-[#E5E7EB] text-xs font-semibold text-[#0B0D14] transition-colors flex items-center gap-1.5 shadow-2xs cursor-pointer">
                            <i class="fa-solid fa-file-invoice-dollar text-2xs"></i>
                            <span>Generate Invoices</span>
                        </button>
                        <button v-if="canAccess('exams')" @click="activeTab = 'exams'" class="px-3 py-1.5 rounded-xl bg-[#F8F9FA] hover:bg-[#F4F5F8] border border-[#E5E7EB] text-xs font-semibold text-[#0B0D14] transition-colors flex items-center gap-1.5 shadow-2xs cursor-pointer">
                            <i class="fa-solid fa-award text-2xs"></i>
                            <span>Exam Terms</span>
                        </button>
                        <button v-if="canAccess('defaulters')" @click="activeTab = 'defaulters'" class="px-3 py-1.5 rounded-xl bg-[#F8F9FA] hover:bg-[#F4F5F8] border border-[#E5E7EB] text-xs font-semibold text-[#991B1B] transition-colors flex items-center gap-1.5 shadow-2xs cursor-pointer">
                            <i class="fa-solid fa-paper-plane text-2xs"></i>
                            <span>Defaulter Reminders</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Main Split Sections: School Operations & Finance / Campus Pulse -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- Left: School Operations & Class Rosters -->
                <div class="rounded-2xl border border-[#E5E7EB] bg-white p-5 shadow-2xs space-y-4">
                    <div class="flex items-center justify-between border-b border-[#E5E7EB] pb-3">
                        <div>
                            <h3 class="text-sm font-bold text-[#0B0D14]">Class Enrollment & Capacity</h3>
                            <p class="text-2xs text-[#5B6270]">Student distribution across grade levels</p>
                        </div>
                        <button @click="activeTab = 'classes'" class="text-2xs font-semibold text-[#2F6BFF] hover:underline flex items-center gap-1 cursor-pointer">
                            <span>All Classes</span>
                            <i class="fa-solid fa-arrow-right text-3xs"></i>
                        </button>
                    </div>

                    <div class="space-y-2 max-h-72 overflow-y-auto pr-1 custom-scrollbar">
                        <div v-for="c in classes" :key="c.id" class="flex items-center justify-between rounded-xl bg-[#F8F9FA] p-3 border border-[#E5E7EB]">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-xs text-[#0B0D14]">{{ c.name }}</span>
                                    <span class="text-3xs uppercase tracking-wider text-[#5B6270] bg-white px-1.5 py-0.5 rounded border border-[#E5E7EB]">{{ c.level }}</span>
                                </div>
                                <p class="text-3xs text-[#5B6270] mt-0.5 truncate">
                                    {{ c.sections?.length || 0 }} section(s) • {{ c.subjects?.length || 0 }} subject(s)
                                </p>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <span class="inline-flex items-center gap-1 rounded-full bg-white border border-[#E5E7EB] px-2.5 py-1 text-2xs font-semibold text-[#0B0D14] shadow-2xs">
                                    <span class="raibix-dot h-1 w-1"></span>
                                    <span>{{ c.students_count }} Students</span>
                                </span>
                            </div>
                        </div>
                        <div v-if="!classes || classes.length === 0" class="py-8 text-center text-xs text-[#5B6270]">
                            No classes configured yet.
                        </div>
                    </div>
                </div>

                <!-- Right: Financial Pulse or Academic Roster -->
                <div v-if="canAccess('fees')" class="rounded-2xl border border-[#E5E7EB] bg-white p-5 shadow-2xs space-y-4">
                    <div class="flex items-center justify-between border-b border-[#E5E7EB] pb-3">
                        <div>
                            <h3 class="text-sm font-bold text-[#0B0D14]">Monthly Collection Pulse</h3>
                            <p class="text-2xs text-[#5B6270]">Collection progress against monthly target</p>
                        </div>
                        <button @click="activeTab = 'defaulters'" class="text-2xs font-semibold text-[#2F6BFF] hover:underline flex items-center gap-1 cursor-pointer">
                            <span>Overdue List</span>
                            <i class="fa-solid fa-arrow-right text-3xs"></i>
                        </button>
                    </div>

                    <!-- Target Progress Bar -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between text-2xs font-semibold text-[#0B0D14]">
                            <span>PKR {{ (kpis?.fee_collected_month || 0).toLocaleString() }} collected</span>
                            <span>{{ collectionPercentage }}% of PKR {{ (finance_stats?.monthly_target || 0).toLocaleString() }}</span>
                        </div>
                        <div class="w-full bg-[#F4F5F8] rounded-full h-2 overflow-hidden border border-[#E5E7EB]">
                            <div class="bg-[#10B981] h-2 rounded-full transition-all duration-500" :style="{ width: Math.min(collectionPercentage, 100) + '%' }"></div>
                        </div>
                    </div>

                    <!-- Overdue Accounts Table -->
                    <div class="space-y-2 pt-2">
                        <h4 class="text-2xs font-bold uppercase tracking-wider text-[#5B6270]">Priority Overdue Challans</h4>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="border-b border-[#E5E7EB] bg-[#F8F9FA] text-3xs font-semibold uppercase tracking-wider text-[#5B6270]">
                                        <th class="py-2 px-2.5">Challan #</th>
                                        <th class="py-2 px-2.5">Student</th>
                                        <th class="py-2 px-2.5">Overdue Amount</th>
                                        <th class="py-2 px-2.5 text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#E5E7EB]">
                                    <tr v-if="!defaulters || defaulters.length === 0">
                                        <td colspan="4" class="py-6 text-center text-2xs text-[#5B6270]">
                                            No overdue student challans recorded.
                                        </td>
                                    </tr>
                                    <tr v-for="d in defaulters?.slice(0, 4)" :key="d.id" class="hover:bg-[#F8F9FA]/80">
                                        <td class="py-2 px-2.5 font-mono text-3xs font-bold text-[#0B0D14]">{{ d.challan_number }}</td>
                                        <td class="py-2 px-2.5">
                                            <span class="font-medium text-[#0B0D14]">{{ d.student?.user?.name || 'Student' }}</span>
                                            <span class="block text-3xs text-[#5B6270]">{{ d.student?.current_class?.name }}</span>
                                        </td>
                                        <td class="py-2 px-2.5 font-bold text-[#991B1B]">PKR {{ (d.total_amount - d.paid_amount).toLocaleString() }}</td>
                                        <td class="py-2 px-2.5 text-right">
                                            <button @click="remindDefaulter(d.id)" class="px-2 py-1 rounded bg-[#FEF2F2] hover:bg-[#FEE2E2] text-[#991B1B] text-3xs font-semibold border border-[#FECACA] transition-colors cursor-pointer">
                                                Remind
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Right Alternate for Non-Finance (Librarian/Registrar) -->
                <div v-else class="rounded-2xl border border-[#E5E7EB] bg-white p-5 shadow-2xs space-y-4">
                    <div class="flex items-center justify-between border-b border-[#E5E7EB] pb-3">
                        <div>
                            <h3 class="text-sm font-bold text-[#0B0D14]">Institutional Notice Board</h3>
                            <p class="text-2xs text-[#5B6270]">Official school updates and announcements</p>
                        </div>
                        <button @click="activeTab = 'notices'" class="text-2xs font-semibold text-[#2F6BFF] hover:underline flex items-center gap-1 cursor-pointer">
                            <span>All Notices</span>
                            <i class="fa-solid fa-arrow-right text-3xs"></i>
                        </button>
                    </div>
                    <div class="space-y-2 max-h-72 overflow-y-auto pr-1 custom-scrollbar">
                        <div v-for="n in notices?.slice(0, 5)" :key="n.id" class="p-3 rounded-xl bg-[#F8F9FA] border border-[#E5E7EB] space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-xs text-[#0B0D14]">{{ n.title }}</span>
                                <span class="text-3xs px-2 py-0.5 rounded-full bg-white border border-[#E5E7EB] text-[#5B6270] uppercase font-semibold">{{ n.target_audience }}</span>
                            </div>
                            <p class="text-2xs text-[#5B6270] line-clamp-2">{{ n.content }}</p>
                            <p class="text-3xs text-[#5B6270] pt-1">{{ n.publish_date }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pending Manual Fee Payment Slips Table (When Available) -->
            <div v-if="canAccess('fees') && pending_payments && pending_payments.length > 0" class="rounded-2xl border border-[#E5E7EB] bg-white p-5 shadow-2xs space-y-4">
                <div class="flex items-center justify-between border-b border-[#E5E7EB] pb-3">
                    <div class="flex items-center gap-2">
                        <span class="raibix-dot h-2 w-2"></span>
                        <h3 class="text-sm font-bold text-[#0B0D14]">Pending Fee Deposit Slip Approvals</h3>
                    </div>
                    <button @click="activeTab = 'fee_approvals'" class="text-2xs font-semibold text-[#2F6BFF] hover:underline flex items-center gap-1 cursor-pointer">
                        <span>Manage All</span>
                        <i class="fa-solid fa-arrow-right text-3xs"></i>
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-[#E5E7EB] bg-[#F8F9FA] text-3xs font-semibold uppercase tracking-wider text-[#5B6270]">
                                <th class="py-2.5 px-3">Student</th>
                                <th class="py-2.5 px-3">Challan #</th>
                                <th class="py-2.5 px-3">Amount</th>
                                <th class="py-2.5 px-3">Reference / Txn ID</th>
                                <th class="py-2.5 px-3">Deposit Receipt</th>
                                <th class="py-2.5 px-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E5E7EB]">
                            <tr v-for="p in pending_payments.slice(0, 5)" :key="p.id" class="hover:bg-[#F8F9FA]/80">
                                <td class="py-3 px-3">
                                    <span class="font-semibold text-[#0B0D14]">{{ p.student_name }}</span>
                                    <span class="block text-3xs text-[#5B6270]">{{ p.class_name }} - {{ p.section_name }}</span>
                                </td>
                                <td class="py-3 px-3 font-mono text-3xs font-bold text-[#0B0D14]">{{ p.challan_number }}</td>
                                <td class="py-3 px-3 font-bold text-[#0B0D14]">PKR {{ (p.amount || 0).toLocaleString() }}</td>
                                <td class="py-3 px-3 font-mono text-3xs text-[#5B6270]">{{ p.transaction_reference || 'N/A' }}</td>
                                <td class="py-3 px-3">
                                    <a v-if="p.receipt_path" :href="'/storage/' + p.receipt_path" target="_blank" class="text-2xs font-semibold text-[#2F6BFF] hover:underline flex items-center gap-1">
                                        <i class="fa-solid fa-arrow-up-right-from-square text-3xs"></i>
                                        <span>View Slip</span>
                                    </a>
                                    <span v-else class="text-3xs text-[#5B6270]">No receipt</span>
                                </td>
                                <td class="py-3 px-3 text-right space-x-2">
                                    <button @click="approveFeePaymentReceipt(p.id)" class="px-3 py-1 rounded-lg bg-[#F0FDF4] hover:bg-[#DCFCE7] text-[#166534] border border-[#BBF7D0] text-2xs font-semibold transition-colors cursor-pointer shadow-2xs">
                                        Approve
                                    </button>
                                    <button @click="rejectFeePaymentReceipt(p.id)" class="px-3 py-1 rounded-lg bg-[#FEF2F2] hover:bg-[#FEE2E2] text-[#991B1B] border border-[#FECACA] text-2xs font-semibold transition-colors cursor-pointer shadow-2xs">
                                        Reject
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Official School Circulars Feed -->
            <div class="rounded-2xl border border-[#E5E7EB] bg-white p-5 shadow-2xs space-y-4">
                <div class="flex items-center justify-between border-b border-[#E5E7EB] pb-3">
                    <div class="flex items-center gap-2">
                        <span class="raibix-dot h-2 w-2"></span>
                        <h3 class="text-sm font-bold text-[#0B0D14]">Campus Announcements & Circulars</h3>
                    </div>
                    <button @click="activeTab = 'notices'" class="text-2xs font-semibold text-[#2F6BFF] hover:underline flex items-center gap-1 cursor-pointer">
                        <span>Manage Notices</span>
                        <i class="fa-solid fa-arrow-right text-3xs"></i>
                    </button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                    <div v-for="notice in notices?.slice(0, 6)" :key="notice.id" class="p-3.5 rounded-xl bg-[#F8F9FA] border border-[#E5E7EB] flex flex-col justify-between space-y-2">
                        <div>
                            <div class="flex items-center justify-between gap-2">
                                <span class="font-bold text-xs text-[#0B0D14] truncate" :title="notice.title">{{ notice.title }}</span>
                                <span class="text-3xs px-2 py-0.5 rounded-full bg-white border border-[#E5E7EB] text-[#5B6270] font-semibold uppercase flex-shrink-0">{{ notice.target_audience }}</span>
                            </div>
                            <p class="text-2xs text-[#5B6270] line-clamp-2 mt-1">{{ notice.content }}</p>
                        </div>
                        <div class="text-3xs text-[#5B6270] flex items-center justify-between pt-1 border-t border-[#E5E7EB]">
                            <span>{{ notice.publish_date || 'Recent' }}</span>
                            <span v-if="notice.creator?.name">By {{ notice.creator.name }}</span>
                        </div>
                    </div>
                    <div v-if="!notices || notices.length === 0" class="col-span-full py-8 text-center text-xs text-[#5B6270]">
                        No announcements published yet.
                    </div>
                </div>
            </div>
        </div>

        <!-- ================= TAB: USER MANAGEMENT BY ROLE ================= -->
                <div v-if="activeTab === 'users'" class="space-y-6 animate-fadeIn">
                    <!-- Top Action Bar & Filter -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-[#DADCE0] shadow-2xs">
                        <div class="flex flex-wrap items-center gap-2">
                            <button 
                                @click="userRoleFilter = 'all'" 
                                :class="userRoleFilter === 'all' ? 'bg-[#1A73E8] text-white font-bold' : 'bg-[#F8F9FA] text-[#5F6368] hover:bg-[#E8F0FE] hover:text-[#1A73E8] font-medium'"
                                class="px-4 py-2 rounded-xl text-xs transition-all shadow-2xs"
                            >All Staff & Users ({{ users?.length || 0 }})</button>
                            <button 
                                @click="userRoleFilter = 'teacher'" 
                                :class="userRoleFilter === 'teacher' ? 'bg-[#1A73E8] text-white font-bold' : 'bg-[#F8F9FA] text-[#5F6368] hover:bg-[#E8F0FE] hover:text-[#1A73E8] font-medium'"
                                class="px-4 py-2 rounded-xl text-xs transition-all shadow-2xs"
                            >Teachers</button>
                            <button 
                                @click="userRoleFilter = 'admin'" 
                                :class="userRoleFilter === 'admin' ? 'bg-[#1A73E8] text-white font-bold' : 'bg-[#F8F9FA] text-[#5F6368] hover:bg-[#E8F0FE] hover:text-[#1A73E8] font-medium'"
                                class="px-4 py-2 rounded-xl text-xs transition-all shadow-2xs"
                            >Admins & VPs</button>
                            <button 
                                @click="userRoleFilter = 'accountant'" 
                                :class="userRoleFilter === 'accountant' ? 'bg-[#1A73E8] text-white font-bold' : 'bg-[#F8F9FA] text-[#5F6368] hover:bg-[#E8F0FE] hover:text-[#1A73E8] font-medium'"
                                class="px-4 py-2 rounded-xl text-xs transition-all shadow-2xs"
                            >Accountants</button>
                            <button 
                                @click="userRoleFilter = 'registrar'" 
                                :class="userRoleFilter === 'registrar' ? 'bg-[#1A73E8] text-white font-bold' : 'bg-[#F8F9FA] text-[#5F6368] hover:bg-[#E8F0FE] hover:text-[#1A73E8] font-medium'"
                                class="px-4 py-2 rounded-xl text-xs transition-all shadow-2xs"
                            >Registrars / Front Desk</button>
                        </div>
                        <div class="flex items-center gap-3">
                            <input 
                                v-model="userSearchQuery" 
                                type="text" 
                                placeholder="Search name, email, phone..." 
                                class="rounded-xl border border-[#DADCE0] bg-[#F8F9FA] px-4 py-2 text-xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#1A73E8]"
                            />
                            <button 
                                @click="showAddUserModal = true"
                                class="rounded-xl bg-[#1A73E8] px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-[#1557B0] transition-all flex items-center gap-1.5"
                            >
                                <span>+ Add User by Role</span>
                            </button>
                            <button @click="showBulkUserModal = true" class="rounded-xl bg-[#F8F9FA] border border-[#DADCE0] px-4 py-2 text-xs font-bold text-[#3C4043] hover:bg-[#F1F3F4] transition-all flex items-center space-x-2">
                                <i class="fa-solid fa-file-import"></i>
                                <span>Bulk Import CSV</span>
                            </button>
                        </div>
                    </div>

                    <!-- User Directory Table -->
                    <div class="google-card overflow-hidden rounded-2xl border border-[#DADCE0] bg-white shadow-2xs">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-sm min-w-[750px]">
                                <thead>
                                    <tr class="border-b border-[#DADCE0] bg-[#F8F9FA] text-xs font-bold uppercase tracking-wider text-[#5F6368]">
                                        <th class="py-4 px-6">User / Staff Name</th>
                                        <th class="py-4 px-6">Role & Assignment</th>
                                        <th class="py-4 px-6">Contact Info</th>
                                        <th class="py-4 px-6">Account Status</th>
                                        <th class="py-4 px-6 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#E8EAED]">
                                    <tr v-if="!users || users.length === 0">
                                        <td colspan="5" class="py-12 text-center text-sm text-[#5F6368]">No staff accounts configured. Click "+ Add User by Role" above to get started.</td>
                                    </tr>
                                    <tr 
                                        v-for="u in users?.filter(u => (userRoleFilter === 'all' || u.user_type === userRoleFilter) && (!userSearchQuery || u.name.toLowerCase().includes(userSearchQuery.toLowerCase()) || (u.email && u.email.toLowerCase().includes(userSearchQuery.toLowerCase()))))" 
                                        :key="u.id" 
                                        class="hover:bg-[#F8F9FA]/80 transition-colors"
                                    >
                                        <td class="py-4 px-6">
                                            <div class="flex items-center space-x-3">
                                                <div class="h-10 w-10 rounded-full bg-[#E8F0FE] border border-[#D2E3FC] flex items-center justify-center font-bold text-[#1A73E8] uppercase flex-shrink-0 text-sm">
                                                    {{ u.name.substring(0, 2) }}
                                                </div>
                                                <div>
                                                    <div class="font-bold text-[#202124] text-sm">{{ u.name }}</div>
                                                    <div class="text-2xs text-[#5F6368] font-mono">ID: #{{ u.id }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-4 px-6">
                                            <span 
                                                :class="[
                                                    u.user_type === 'admin' ? 'bg-[#FCE8E6] text-[#C5221F] border-[#FAD2CF]' :
                                                    u.user_type === 'teacher' ? 'bg-[#E8F0FE] text-[#1A73E8] border-[#D2E3FC]' :
                                                    u.user_type === 'accountant' ? 'bg-[#FEF7E0] text-[#B06000] border-[#FEEFC3]' :
                                                    'bg-[#E6F4EA] text-[#137333] border-[#CEEAD6]'
                                                ]"
                                                class="inline-flex items-center rounded-full px-3 py-1 text-xs font-bold border uppercase tracking-wide"
                                            >
                                                {{ u.user_type }}
                                            </span>
                                            <div v-if="u.qualification" class="text-2xs text-[#5F6368] mt-1 font-medium"><i class="fa-solid fa-graduation-cap mr-1"></i> {{ u.qualification }}</div>
                                        </td>
                                        <td class="py-4 px-6">
                                            <div class="text-xs font-semibold text-[#202124]">{{ u.email || 'No email' }}</div>
                                            <div class="text-2xs text-[#5F6368]">{{ u.phone || u.cnic || 'No phone provided' }}</div>
                                        </td>
                                        <td class="py-4 px-6">
                                            <span 
                                                :class="u.status === 'active' ? 'bg-[#E6F4EA] text-[#137333]' : 'bg-[#FCE8E6] text-[#C5221F]'"
                                                class="rounded-full px-2.5 py-1 text-xs font-bold inline-block"
                                            >
                                                <i class="fa-solid fa-circle text-[6px] mr-1"></i> {{ u.status ? u.status.toUpperCase() : 'ACTIVE' }}
                                            </span>
                                        </td>
                                        <td class="py-4 px-6 text-right space-x-2">
                                            <button 
                                                @click="openEditUser(u)" 
                                                class="p-1.5 rounded-lg text-[#1A73E8] hover:bg-[#E8F0FE] transition-colors"
                                                title="Edit User Profile"
                                            ><i class="fa-solid fa-pen"></i></button>
                                            <button 
                                                @click="toggleUserStatus(u)" 
                                                class="p-1.5 rounded-lg text-[#B06000] hover:bg-[#FEF7E0] transition-colors"
                                                :title="u.status === 'active' ? 'Deactivate Account' : 'Reactivate Account'"
                                            ><i class="fa-solid fa-rotate"></i></button>
                                            <button 
                                                @click="resetUserPassword(u)" 
                                                class="p-1.5 rounded-lg text-[#137333] hover:bg-[#E6F4EA] transition-colors"
                                                title="Send Password Reset Link"
                                            ><i class="fa-solid fa-key"></i></button>
                                            <button 
                                                @click="deleteUser(u)" 
                                                class="p-1.5 rounded-lg text-[#C5221F] hover:bg-[#FCE8E6] transition-colors"
                                                title="Hard Delete Account (If No Historical Data)"
                                            ><i class="fa-solid fa-trash"></i></button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- ================= TAB: STUDENT MANAGEMENT CRUD & LIFECYCLE ================= -->
                <div v-if="activeTab === 'students'" class="space-y-6 animate-fadeIn">
                    <!-- Top Actions Bar -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-[#DADCE0] shadow-2xs">
                        <div class="flex flex-wrap items-center gap-2">
                            <button 
                                @click="studentStatusFilter = 'All'" 
                                :class="studentStatusFilter === 'All' ? 'bg-[#1A73E8] text-white font-bold' : 'bg-[#F8F9FA] text-[#5F6368] hover:bg-[#E8F0FE] font-medium'"
                                class="px-3.5 py-1.5 rounded-xl text-xs transition-all shadow-2xs"
                            >All Students ({{ students?.length || 0 }})</button>
                            <button 
                                @click="studentStatusFilter = 'Active'" 
                                :class="studentStatusFilter === 'Active' ? 'bg-[#137333] text-white font-bold' : 'bg-[#F8F9FA] text-[#137333] hover:bg-[#E6F4EA] font-medium'"
                                class="px-3.5 py-1.5 rounded-xl text-xs transition-all shadow-2xs"
                            >Active</button>
                            <button 
                                @click="studentStatusFilter = 'On Leave'" 
                                :class="studentStatusFilter === 'On Leave' ? 'bg-[#B06000] text-white font-bold' : 'bg-[#F8F9FA] text-[#B06000] hover:bg-[#FEF7E0] font-medium'"
                                class="px-3.5 py-1.5 rounded-xl text-xs transition-all shadow-2xs"
                            >On Leave</button>
                            <button 
                                @click="studentStatusFilter = 'Suspended'" 
                                :class="studentStatusFilter === 'Suspended' ? 'bg-[#C5221F] text-white font-bold' : 'bg-[#F8F9FA] text-[#C5221F] hover:bg-[#FCE8E6] font-medium'"
                                class="px-3.5 py-1.5 rounded-xl text-xs transition-all shadow-2xs"
                            >Suspended / Expelled</button>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <input 
                                v-model="studentSearchQuery" 
                                type="text" 
                                placeholder="Search student, admission #, roll #..." 
                                class="rounded-xl border border-[#DADCE0] bg-[#F8F9FA] px-3.5 py-2 text-xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#1A73E8]"
                            />
                            <button 
                                @click="showAddStudentModal = true"
                                class="rounded-xl bg-[#1A73E8] px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-[#1557B0] transition-all"
                            >
                                + Enroll Student
                            </button>
                            <button @click="showStudentCsvModal = true" class="rounded-xl bg-[#F8F9FA] border border-[#DADCE0] px-4 py-2 text-xs font-bold text-[#3C4043] hover:bg-[#F1F3F4] transition-all flex items-center space-x-1.5">
                                <i class="fa-solid fa-file-import"></i>
                                <span>Import CSV</span>
                            </button>
                            <button @click="showBulkPromoteModal = true" class="rounded-xl bg-[#137333] text-white px-4 py-2 text-xs font-bold shadow-2xs hover:bg-[#0d5625] transition-all flex items-center gap-1.5">
                                <i class="fa-solid fa-angles-up"></i>
                                <span>Bulk Promote</span>
                            </button>
                        </div>
                    </div>

                    <!-- Student Roster Grid -->
                    <div class="google-card overflow-hidden rounded-2xl border border-[#DADCE0] bg-white shadow-2xs">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-sm min-w-[850px]">
                                <thead>
                                    <tr class="border-b border-[#DADCE0] bg-[#F8F9FA] text-xs font-bold uppercase tracking-wider text-[#5F6368]">
                                        <th class="py-4 px-6">Student & Roll #</th>
                                        <th class="py-4 px-6">Admission Info</th>
                                        <th class="py-4 px-6">Class & Section</th>
                                        <th class="py-4 px-6">Parent & Phone</th>
                                        <th class="py-4 px-6">Status & Audit</th>
                                        <th class="py-4 px-6 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#E8EAED]">
                                    <tr v-if="!students || students.length === 0">
                                        <td colspan="6" class="py-12 text-center text-sm text-[#5F6368]">No student profiles enrolled yet. Click "+ Enroll Student" above.</td>
                                    </tr>
                                    <tr 
                                        v-for="s in students?.filter(s => (studentStatusFilter === 'All' || s.status === studentStatusFilter || (studentStatusFilter === 'Suspended' && ['Suspended', 'Expelled', 'Transferred Out'].includes(s.status))) && (!studentSearchQuery || (s.user?.name && s.user.name.toLowerCase().includes(studentSearchQuery.toLowerCase())) || (s.admission_number && s.admission_number.toLowerCase().includes(studentSearchQuery.toLowerCase())) || (s.roll_number && s.roll_number.toLowerCase().includes(studentSearchQuery.toLowerCase()))))" 
                                        :key="s.id" 
                                        class="hover:bg-[#F8F9FA]/80 transition-colors"
                                    >
                                        <td class="py-4 px-6">
                                            <div class="flex items-center space-x-3">
                                                <div class="h-10 w-10 rounded-full bg-[#E8F0FE] border border-[#D2E3FC] flex items-center justify-center font-bold text-[#1A73E8] uppercase flex-shrink-0 text-sm">
                                                    {{ s.user?.name ? s.user.name.substring(0, 2) : 'ST' }}
                                                </div>
                                                <div>
                                                    <div class="font-bold text-[#202124] text-sm">{{ s.user?.name || 'Student' }}</div>
                                                    <div class="text-2xs font-semibold text-[#1A73E8] bg-[#E8F0FE] px-2 py-0.5 rounded inline-block mt-0.5">Roll: {{ s.roll_number || 'Pending' }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-4 px-6 font-mono text-xs font-semibold text-[#5F6368]">
                                            <div>Adm: <span class="text-[#202124] font-bold">{{ s.admission_number }}</span></div>
                                            <div class="text-2xs mt-0.5">{{ s.admission_date || 'N/A' }}</div>
                                        </td>
                                        <td class="py-4 px-6">
                                            <div class="font-bold text-xs text-[#202124]">{{ s.current_class?.name || 'Class Assigned' }}</div>
                                            <div class="text-2xs text-[#5F6368] font-medium">{{ s.current_section?.name || 'No Section' }}</div>
                                        </td>
                                        <td class="py-4 px-6">
                                            <div class="text-xs font-bold text-[#202124]">{{ s.parent?.father_name || s.parent?.mother_name || 'Parent Profile' }}</div>
                                            <div class="text-2xs text-[#5F6368] font-mono">{{ s.parent?.father_phone || s.user?.phone || 'No contact' }}</div>
                                        </td>
                                        <td class="py-4 px-6">
                                            <button 
                                                @click="openStudentStatus(s)"
                                                :class="[
                                                    s.status === 'Active' ? 'bg-[#E6F4EA] text-[#137333] border-[#CEEAD6]' :
                                                    s.status === 'On Leave' ? 'bg-[#FEF7E0] text-[#B06000] border-[#FEEFC3]' :
                                                    'bg-[#FCE8E6] text-[#C5221F] border-[#FAD2CF]'
                                                ]"
                                                class="rounded-full px-3 py-1 text-xs font-bold border hover:shadow-xs transition-all flex items-center gap-1.5"
                                                title="Click to Change Status & Record Audit Reason"
                                            >
                                                <span><i class="fa-solid fa-circle text-[6px] mr-1"></i> {{ s.status || 'Active' }}</span>
                                                <i class="fa-solid fa-arrow-up-right-from-square text-2xs ml-1 opacity-75"></i>
                                            </button>
                                        </td>
                                        <td class="py-4 px-6 text-right space-x-1.5">
                                            <button 
                                                @click="openEditStudent(s)" 
                                                class="p-1.5 rounded-lg text-[#1A73E8] hover:bg-[#E8F0FE] transition-colors"
                                                title="Edit Demographics & Contact"
                                            ><i class="fa-solid fa-pen mr-1"></i> Profile</button>
                                            <button 
                                                @click="openStudentTransfer(s)" 
                                                class="p-1.5 rounded-lg text-[#B06000] hover:bg-[#FEF7E0] transition-colors"
                                                title="Transfer Section or Class"
                                            ><i class="fa-solid fa-shuffle mr-1"></i> Transfer</button>
                                            <button 
                                                @click="openDocumentVault(s)" 
                                                class="p-1.5 rounded-lg text-[#137333] hover:bg-[#E6F4EA] transition-colors relative"
                                                title="Document Vault (B-Form, SLC, Certificates)"
                                            >
                                                <i class="fa-solid fa-folder mr-1"></i> Docs
                                                <span v-if="s.documents?.length" class="absolute -top-1 -right-1 bg-[#1A73E8] text-white rounded-full h-4 w-4 text-[9px] flex items-center justify-center font-bold">{{ s.documents.length }}</span>
                                            </button>
                                            <button 
                                                @click="archiveStudent(s)" 
                                                class="p-1.5 rounded-lg text-[#C5221F] hover:bg-[#FCE8E6] transition-colors"
                                                title="Soft Delete / Archive Student"
                                            ><i class="fa-solid fa-trash"></i></button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- ================= TAB: ADMISSIONS REWORK ================= -->
                <div v-if="activeTab === 'admissions'" class="space-y-8 animate-fadeIn">
                    <!-- Informative Banner on Multi-Source Pipeline -->
                    <div class="rounded-2xl bg-gradient-to-r from-[#1A73E8] to-[#1557B0] p-6 text-white shadow-md flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="space-y-1">
                            <h3 class="text-lg font-extrabold flex items-center gap-2">
                                <i class="fa-solid fa-paper-plane"></i>
                                <span>Unified Admissions Pipeline</span>
                            </h3>
                            <p class="text-xs text-white/90 max-w-2xl">
                                Admin/Principal oversight of walk-in inquiries and online application submissions (<code class="bg-white/20 px-1.5 py-0.5 rounded text-2xs">POST /api/v1/inquiries</code>). One-click enrollment auto-provisions student credentials and issues the initial fee challan.
                            </p>
                        </div>
                        <div class="flex items-center gap-3 flex-shrink-0">
                            <Link href="/admin/admissions" class="text-xs font-bold text-[#1A73E8] hover:underline flex items-center gap-1">
                                <span>Open Full Admissions Suite</span>
                                <i class="fa-solid fa-arrow-right"></i>
                            </Link>
                        </div>
                    </div>

                    <!-- Pipeline Applications Quick Grid -->
                    <div class="google-card rounded-2xl border border-[#DADCE0] bg-white p-6 shadow-2xs space-y-4">
                        <div class="flex items-center justify-between border-b border-[#DADCE0] pb-4">
                            <div>
                                <h4 class="text-base font-extrabold text-[#202124]">Recent Applications & Merit Status</h4>
                                <p class="text-xs text-[#5F6368]">Applications awaiting test marks evaluation or enrollment provisioning.</p>
                            </div>
                            <span class="rounded-full bg-[#E8F0FE] text-[#1A73E8] px-3 py-1 text-xs font-bold">{{ applications?.length || 0 }} Applications</span>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-sm min-w-[700px]">
                                <thead>
                                    <tr class="border-b border-[#DADCE0] bg-[#F8F9FA] text-xs font-bold uppercase tracking-wider text-[#5F6368]">
                                        <th class="py-3 px-4">Application #</th>
                                        <th class="py-3 px-4">Student & Class</th>
                                        <th class="py-3 px-4">Father Contact</th>
                                        <th class="py-3 px-4">Test & Merit</th>
                                        <th class="py-3 px-4 text-right">Enrollment Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#E8EAED]">
                                    <tr v-if="!applications || applications.length === 0">
                                        <td colspan="5" class="py-12 text-center text-sm text-[#5F6368]">No applications pending right now. Click "Open Full Admissions Suite" above to log walk-in applications.</td>
                                    </tr>
                                    <tr v-for="app in applications" :key="app.id" class="hover:bg-[#F8F9FA]/80">
                                        <td class="py-3 px-4 font-mono text-xs font-bold text-[#1A73E8]">{{ app.application_code }}</td>
                                        <td class="py-3 px-4">
                                            <div class="font-bold text-[#202124]">{{ app.student_name }}</div>
                                            <div class="text-2xs text-[#5F6368]">Class: {{ app.tenant_class?.name }}</div>
                                        </td>
                                        <td class="py-3 px-4 text-xs font-medium">{{ app.father_name }} ({{ app.father_phone }})</td>
                                        <td class="py-3 px-4">
                                            <span 
                                                :class="[
                                                    app.merit_status === 'approved' ? 'bg-[#E6F4EA] text-[#137333]' :
                                                    app.merit_status === 'rejected' ? 'bg-[#FCE8E6] text-[#C5221F]' :
                                                    'bg-[#FEF7E0] text-[#B06000]'
                                                ]"
                                                class="rounded-full px-2.5 py-0.5 text-xs font-bold uppercase inline-block"
                                            >
                                                {{ app.merit_status || 'Under Review' }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-right">
                                            <button v-if="!app.is_enrolled" @click="enrollApplicant(app)" class="rounded-xl bg-[#137333] text-white px-3 py-1.5 text-xs font-bold shadow-2xs hover:bg-[#0d5625] transition-all flex items-center gap-1 inline-flex">
                                                <i class="fa-solid fa-user-check"></i>
                                                <span>One-Click Enroll</span>
                                            </button>
                                            <span v-else class="text-xs font-bold text-[#137333] bg-[#E6F4EA] px-3 py-1 rounded-full flex items-center gap-1 inline-flex"><i class="fa-solid fa-check"></i> <span>Enrolled</span></span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- ================= TAB: CLASSES ================= -->
                <div v-if="activeTab === 'classes'" class="space-y-6 animate-fadeIn">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-3xl border border-[#DADCE0] shadow-sm">
                        <div>
                            <h3 class="text-xl font-extrabold text-[#202124] flex items-center gap-2"><i class="fa-solid fa-book text-[#1A73E8]"></i> <span>Class & Section Hierarchy</span></h3>
                            <p class="text-xs text-[#5F6368] mt-1">Manage academic grade levels, assign sections, and designate homeroom class teachers.</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <button @click="showClassCsvModal = true" class="inline-flex items-center gap-1.5 bg-[#F8F9FA] border border-[#DADCE0] hover:bg-[#F1F3F4] text-[#3C4043] px-4 py-2 rounded-full font-bold text-xs transition-all flex-shrink-0">
                                <i class="fa-solid fa-file-import"></i>
                                <span>Import Classes CSV</span>
                            </button>
                            <button @click="triggerOALevelPreset" class="inline-flex items-center gap-1.5 bg-gradient-to-r from-[#8430CE] to-[#6B21A8] hover:opacity-90 text-white px-4 py-2 rounded-full font-bold text-xs shadow-sm transition-all flex-shrink-0">
                                <i class="fa-solid fa-bolt"></i>
                                <span>Setup O/A Level Preset</span>
                            </button>
                            <button @click="showAddClassModal = true" class="inline-flex items-center gap-2 bg-[#1A73E8] hover:bg-[#1557B0] text-white px-5 py-2 rounded-full font-bold text-xs shadow-sm transition-all active:scale-95 flex-shrink-0">
                                <span>+ Add New Grade Level</span>
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 md:grid-cols-3">
                        <div v-for="c in classes" :key="c.id" class="google-card rounded-2xl border border-[#DADCE0] bg-white p-6 shadow-2xs flex flex-col justify-between space-y-4">
                            <div>
                                <div class="flex items-center justify-between">
                                    <span class="rounded-full bg-[#E8F0FE] px-3 py-1 text-xs font-bold text-[#1A73E8] uppercase">{{ c.level }}</span>
                                    <button @click="router.delete('/admin/classes/' + c.id)" class="text-[#C5221F] hover:bg-[#FCE8E6] p-1.5 rounded-lg transition-colors text-xs font-bold" title="Delete Class"><i class="fa-solid fa-trash"></i></button>
                                </div>
                                <h3 class="mt-3 text-xl font-extrabold text-[#202124]">{{ c.name }}</h3>
                                <p class="mt-1 text-xs text-[#5F6368]">Total Enrolled: {{ c.students_count }} Students</p>
                            </div>

                            <div class="space-y-2 border-t border-[#DADCE0] pt-3">
                                <div class="flex items-center justify-between text-2xs font-extrabold uppercase text-[#5F6368]">
                                    <span>Sections ({{ c.sections?.length || 0 }})</span>
                                    <button @click="openAddSection(c.id)" class="text-[#1A73E8] hover:underline">+ Add Section</button>
                                </div>
                                <div v-if="!c.sections || c.sections.length === 0" class="text-2xs text-[#5F6368] italic">No sections created.</div>
                                <div v-for="sec in c.sections" :key="sec.id" class="flex items-center justify-between bg-[#F8F9FA] px-3 py-2 rounded-xl border border-[#E8EAED] text-xs font-bold">
                                    <div>
                                        <span class="text-[#202124]">Section {{ sec.name }}</span>
                                        <span v-if="sec.room_number" class="text-2xs text-[#5F6368] ml-1">({{ sec.room_number }})</span>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <span v-if="sec.class_teacher" class="text-2xs font-medium text-[#137333]"><i class="fa-solid fa-graduation-cap mr-1"></i> {{ sec.class_teacher.name }}</span>
                                        <button @click="router.delete('/admin/sections/' + sec.id)" class="text-[#C5221F] hover:text-[#A50E0E]" title="Remove Section"><i class="fa-solid fa-xmark"></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ================= TAB: DEFAULTERS ================= -->
                <div v-if="activeTab === 'defaulters'" class="space-y-6 animate-fadeIn">
                    <div class="google-card overflow-hidden rounded-2xl border border-[#DADCE0] bg-white shadow-2xs">
                        <div class="p-6 border-b border-[#DADCE0] flex items-center justify-between">
                            <div>
                                <h3 class="text-base font-bold text-[#202124]">All Overdue Fee Accounts</h3>
                                <p class="text-xs text-[#5F6368]">Student challans that have passed the payment deadline.</p>
                            </div>
                            <span class="rounded-full bg-[#FCE8E6] text-[#C5221F] px-3 py-1 text-xs font-bold border border-[#FAD2CF]">{{ defaulters?.length || 0 }} Defaulters</span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-sm min-w-[750px]">
                                <thead>
                                    <tr class="border-b border-[#DADCE0] bg-[#F8F9FA] text-xs font-bold uppercase tracking-wider text-[#5F6368]">
                                        <th class="py-4 px-6">Challan Number</th>
                                        <th class="py-4 px-6">Student Name</th>
                                        <th class="py-4 px-6">Class / Section</th>
                                        <th class="py-4 px-6">Due Amount</th>
                                        <th class="py-4 px-6">Status</th>
                                        <th class="py-4 px-6 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#E8EAED]">
                                    <tr v-if="!defaulters || defaulters.length === 0">
                                        <td colspan="6" class="py-12 text-center text-sm text-[#5F6368]">No overdue challans found. All fee accounts are up to date!</td>
                                    </tr>
                                    <tr v-for="d in defaulters" :key="d.id" class="hover:bg-[#F8F9FA]/80">
                                        <td class="py-4 px-6 font-mono text-xs font-bold text-[#1A73E8]">{{ d.challan_number }}</td>
                                        <td class="py-4 px-6 font-bold text-[#202124]">{{ d.student?.user?.name || 'Student' }}</td>
                                        <td class="py-4 px-6">{{ d.student?.current_class?.name }} - {{ d.student?.current_section?.name }}</td>
                                        <td class="py-4 px-6 font-extrabold text-[#C5221F]">PKR {{ (d.total_amount - d.paid_amount).toLocaleString() }}</td>
                                        <td class="py-4 px-6">
                                            <span class="rounded-full bg-[#FCE8E6] px-3 py-1 text-xs font-bold text-[#C5221F] border border-[#FAD2CF]">
                                                {{ d.status.toUpperCase() }}
                                            </span>
                                        </td>
                                        <td class="py-4 px-6 text-right space-x-2">
                                            <button @click="remindDefaulter(d.id)" class="bg-[#1A73E8] hover:bg-[#1557B0] text-white px-3 py-1.5 rounded-xl font-bold text-2xs shadow-2xs transition-all inline-flex items-center gap-1"><i class="fa-solid fa-comment-sms"></i> <span>Remind SMS</span></button>
                                            <button @click="waiveDefaulter(d.id)" class="bg-[#E6F4EA] hover:bg-[#CEEAD6] text-[#137333] px-3 py-1.5 rounded-xl font-bold text-2xs transition-all border border-[#CEEAD6] inline-flex items-center gap-1"><i class="fa-solid fa-tag"></i> <span>Waive/Discount</span></button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- ================= TAB: NOTICES ================= -->
                <div v-if="activeTab === 'notices'" class="space-y-6 animate-fadeIn">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-3xl border border-[#DADCE0] shadow-sm">
                        <div>
                            <h3 class="text-xl font-extrabold text-[#202124] flex items-center gap-2"><i class="fa-solid fa-bullhorn text-[#1A73E8]"></i> <span>School Notice Board</span></h3>
                            <p class="text-xs text-[#5F6368] mt-1">Broadcast digital announcements with optional SMS and WhatsApp alert integration.</p>
                        </div>
                        <button @click="showAddNoticeModal = true" class="inline-flex items-center gap-2 bg-[#1A73E8] hover:bg-[#1557B0] text-white px-5 py-2.5 rounded-full font-bold text-xs shadow-sm transition-all active:scale-95 flex-shrink-0">
                            <span>+ Publish Announcement</span>
                        </button>
                    </div>

                    <div class="google-card rounded-2xl border border-[#DADCE0] bg-white p-6 shadow-2xs space-y-4">
                        <h3 class="text-base font-bold text-[#202124]">Published Announcements</h3>
                        <div class="space-y-4">
                            <div v-if="!notices || notices.length === 0" class="py-12 text-center text-sm text-[#5F6368]">No notices published right now. Click "+ Publish Announcement" above to broadcast.</div>
                            <div v-for="n in notices" :key="n.id" class="rounded-2xl bg-[#F8F9FA] p-5 border border-[#DADCE0] space-y-2 relative group hover:shadow-xs transition-shadow">
                                <div class="flex justify-between items-center text-xs">
                                    <div class="flex items-center space-x-2">
                                        <span class="rounded-full bg-[#E8F0FE] px-3 py-1 font-bold text-[#1A73E8] uppercase">{{ n.target_audience }}</span>
                                        <span v-if="n.category" class="rounded-full bg-[#F1F3F4] px-2.5 py-0.5 font-semibold text-[#5F6368]">{{ n.category }}</span>
                                    </div>
                                    <div class="flex items-center space-x-3">
                                        <span class="text-[#5F6368] font-semibold">{{ n.publish_date }}</span>
                                        <button @click="router.delete('/admin/notices/' + n.id)" class="text-[#C5221F] hover:bg-[#FCE8E6] p-1.5 rounded-lg transition-colors font-bold text-xs" title="Delete Announcement"><i class="fa-solid fa-trash"></i></button>
                                    </div>
                                </div>
                                <h4 class="font-extrabold text-[#202124] text-base">{{ n.title }}</h4>
                                <p class="text-sm text-[#202124]/90">{{ n.content }}</p>
                                <div v-if="n.video_url || n.video_file_path" class="pt-2 flex flex-col gap-2">
                                    <video v-if="n.video_file_path" controls :src="n.video_file_path" class="w-full max-h-64 rounded-xl border border-[#DADCE0] shadow-xs"></video>
                                    <a v-if="n.video_url" :href="n.video_url" target="_blank" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-[#E8F0FE] text-[#1A73E8] font-bold text-xs hover:bg-[#D2E3FC] self-start"><i class="fa-brands fa-youtube text-red-600 text-sm"></i> <span>Watch Attached Notice Video</span></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ================= TAB: TIMETABLE BUILDER ================= -->
                <div v-if="activeTab === 'timetables'" class="space-y-6 animate-fadeIn">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-3xl border border-[#DADCE0] shadow-sm">
                        <div>
                            <h3 class="text-xl font-extrabold text-[#202124] flex items-center gap-2.5">
                                <span><i class="fa-solid fa-calendar-days mr-1.5"></i> Academic Timetable Builder</span>
                                
                            </h3>
                            <p class="text-xs text-[#5F6368] mt-1">Schedule periods, assign homerooms, and prevent teacher double-booking with real-time conflict detection.</p>
                        </div>
                        <button @click="showTimetableModal = true" class="inline-flex items-center gap-2 bg-[#1A73E8] hover:bg-[#1557B0] text-white px-5 py-2.5 rounded-full font-bold text-xs shadow-sm transition-all active:scale-95 flex-shrink-0">
                            <span>+ Schedule Class Slot</span>
                        </button>
                    </div>

                    <div class="bg-white rounded-3xl border border-[#DADCE0] shadow-sm overflow-hidden p-6">
                        <div v-if="!timetables || timetables.length === 0" class="py-12 text-center text-[#5F6368] space-y-3">
                            <div class="text-4xl text-[#1A73E8]"><i class="fa-solid fa-calendar-week"></i></div>
                            <div class="font-bold text-sm text-[#202124]">No Timetable Slots Scheduled Yet</div>
                            <p class="text-xs max-w-md mx-auto">Click "+ Schedule Class Slot" above to begin mapping out periods and assigning subjects to homeroom teachers.</p>
                        </div>
                        <div v-else class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="border-b border-[#DADCE0] bg-[#F8F9FA] text-2xs uppercase tracking-wider font-extrabold text-[#5F6368]">
                                        <th class="py-3.5 px-4">Class & Section</th>
                                        <th class="py-3.5 px-4">Subject</th>
                                        <th class="py-3.5 px-4">Assigned Teacher</th>
                                        <th class="py-3.5 px-4">Day of Week</th>
                                        <th class="py-3.5 px-4">Time Period</th>
                                        <th class="py-3.5 px-4">Room #</th>
                                        <th class="py-3.5 px-4 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#DADCE0] text-xs font-medium text-[#202124]">
                                    <tr v-for="t in timetables" :key="t.id" class="hover:bg-[#F8F9FA]/80 transition-colors">
                                        <td class="py-3.5 px-4 font-bold text-[#1A73E8]">
                                            {{ t.section?.tenantClass?.name || 'Class' }} — {{ t.section?.name || 'Section' }}
                                        </td>
                                        <td class="py-3.5 px-4 font-bold text-[#202124]">{{ t.subject?.name || 'Subject' }}</td>
                                        <td class="py-3.5 px-4 text-[#5F6368] flex items-center gap-1.5">
                                            <span class="h-2 w-2 rounded-full bg-[#137333]"></span>
                                            <span>{{ t.teacher?.name || 'Unassigned' }}</span>
                                        </td>
                                        <td class="py-3.5 px-4">
                                            <span class="inline-block px-2.5 py-0.5 rounded-full text-2xs font-extrabold bg-[#E8F0FE] text-[#1A73E8]">{{ t.day_of_week }}</span>
                                        </td>
                                        <td class="py-3.5 px-4 font-mono text-[#5F6368]">{{ t.start_time }} - {{ t.end_time }}</td>
                                        <td class="py-3.5 px-4 text-[#5F6368]">{{ t.room_number || 'Room 101' }}</td>
                                        <td class="py-3.5 px-4 text-right">
                                            <button @click="deleteTimetableSlot(t)" class="text-[#C5221F] hover:bg-[#FCE8E6] px-2.5 py-1 rounded-lg transition-colors font-bold text-2xs inline-flex items-center gap-1"><i class="fa-solid fa-trash"></i> <span>Remove</span></button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- ================= TAB: FEE STRUCTURE & INVOICES ================= -->
                <div v-if="activeTab === 'fees'" class="space-y-6 animate-fadeIn">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-3xl border border-[#DADCE0] shadow-sm">
                        <div>
                            <h3 class="text-xl font-extrabold text-[#202124] flex items-center gap-2.5">
                                <i class="fa-solid fa-credit-card text-[#137333]"></i>
                                <span>Fee Structures & Recurring Invoices</span>
                                
                            </h3>
                            <p class="text-xs text-[#5F6368] mt-1">Configure tuition, apply sibling discounts or scholarships, and bulk generate monthly fee challans.</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <button @click="showFeeOverrideModal = true" class="bg-[#F8F9FA] hover:bg-[#F1F3F4] text-[#202124] border border-[#DADCE0] px-4 py-2.5 rounded-full font-bold text-xs transition-all flex-shrink-0">
                                + Student Discount / Override
                            </button>
                            <button @click="showMonthlyBillingModal = true" class="bg-[#137333] hover:bg-[#0D652D] text-white px-5 py-2.5 rounded-full font-bold text-xs shadow-sm transition-all active:scale-95 flex-shrink-0 inline-flex items-center gap-1.5">
                                <i class="fa-solid fa-file-invoice-dollar"></i>
                                <span>Generate Monthly Challans</span>
                            </button>
                        </div>
                    </div>

                    <!-- Grid: Class Fee Structure vs Student Overrides -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Class Fee Structures Card -->
                        <div class="bg-white rounded-3xl border border-[#DADCE0] shadow-sm p-6 space-y-4">
                            <div class="flex items-center justify-between border-b border-[#DADCE0] pb-3">
                                <h4 class="font-extrabold text-sm text-[#202124]">Standard Class Fee Structures</h4>
                                <span class="text-2xs text-[#5F6368] font-semibold">Select class to update tariffs</span>
                            </div>
                            <div v-if="!classes || classes.length === 0" class="text-xs text-[#5F6368] py-6 text-center">No classes provisioned yet.</div>
                            <div v-else class="space-y-3 max-h-[420px] overflow-y-auto pr-1">
                                <div v-for="cls in classes" :key="cls.id" class="rounded-2xl border border-[#DADCE0] p-4 hover:border-[#1A73E8] transition-all bg-[#F8F9FA]/40 flex items-center justify-between">
                                    <div>
                                        <div class="font-extrabold text-sm text-[#202124]">{{ cls.name }}</div>
                                        <div class="text-2xs text-[#5F6368] mt-0.5 font-medium">
                                            Tuition: <span class="font-bold text-[#137333]">Rs. {{ fee_structures?.find(f => f.class_id === cls.id)?.monthly_tuition || 8500 }}</span>/mo |
                                            Lab: Rs. {{ fee_structures?.find(f => f.class_id === cls.id)?.lab_charges || 1200 }}
                                        </div>
                                    </div>
                                    <button @click="selectClassForFeeEdit(cls)" class="bg-white hover:bg-[#E8F0FE] text-[#1A73E8] border border-[#DADCE0] px-3.5 py-1.5 rounded-xl font-bold text-2xs transition-colors">
                                        Edit Structure
                                    </button>
                                </div>
                            </div>

                            <!-- Form to edit class structure -->
                            <form v-if="classFeeForm.class_id" @submit.prevent="submitClassFeeStructure" class="mt-4 pt-4 border-t border-[#DADCE0] space-y-3 bg-[#F8F9FA] p-4 rounded-2xl">
                                <div class="font-bold text-xs text-[#1A73E8] flex justify-between items-center">
                                    <span>Updating Fee Structure for Class ID: {{ classFeeForm.class_id }}</span>
                                    <button type="button" @click="classFeeForm.class_id = ''" class="text-2xs text-[#5F6368] hover:underline flex items-center gap-1"><i class="fa-solid fa-xmark"></i> <span>Close</span></button>
                                </div>
                                <div class="grid grid-cols-2 gap-3 text-xs font-medium">
                                    <div>
                                        <label class="block text-2xs font-bold mb-1">Monthly Tuition (Rs.)</label>
                                        <input v-model="classFeeForm.monthly_tuition" type="number" required class="w-full rounded-xl border border-[#DADCE0] px-3 py-1.5 text-xs font-bold" />
                                    </div>
                                    <div>
                                        <label class="block text-2xs font-bold mb-1">Lab Charges (Rs.)</label>
                                        <input v-model="classFeeForm.lab_charges" type="number" required class="w-full rounded-xl border border-[#DADCE0] px-3 py-1.5 text-xs font-bold" />
                                    </div>
                                    <div>
                                        <label class="block text-2xs font-bold mb-1">Transport Charges (Rs.)</label>
                                        <input v-model="classFeeForm.transport_charges" type="number" required class="w-full rounded-xl border border-[#DADCE0] px-3 py-1.5 text-xs font-bold" />
                                    </div>
                                    <div>
                                        <label class="block text-2xs font-bold mb-1">Admission Fee (Rs.)</label>
                                        <input v-model="classFeeForm.admission_fee" type="number" required class="w-full rounded-xl border border-[#DADCE0] px-3 py-1.5 text-xs font-bold" />
                                    </div>
                                </div>
                                <div class="flex justify-end pt-1">
                                    <button type="submit" :disabled="classFeeForm.processing" class="bg-[#1A73E8] hover:bg-[#1557B0] text-white px-4 py-1.5 rounded-xl font-bold text-2xs shadow-xs inline-flex items-center gap-1">
                                        <i class="fa-solid fa-check"></i>
                                        <span>Save Class Tariff</span>
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Student Fee Overrides Card -->
                        <div class="bg-white rounded-3xl border border-[#DADCE0] shadow-sm p-6 space-y-4">
                            <div class="flex items-center justify-between border-b border-[#DADCE0] pb-3">
                                <h4 class="font-extrabold text-sm text-[#202124]">Active Student Overrides & Scholarships</h4>
                                <span class="bg-[#E8F0FE] text-[#1A73E8] text-2xs font-bold px-2.5 py-0.5 rounded-full">{{ fee_overrides?.length || 0 }} Active</span>
                            </div>
                            <div v-if="!fee_overrides || fee_overrides.length === 0" class="py-10 text-center text-[#5F6368] space-y-2">
                                <div class="text-3xl text-[#1A73E8]"><i class="fa-solid fa-tags"></i></div>
                                <div class="font-bold text-xs">No Student Overrides Configured</div>
                                <p class="text-2xs max-w-xs mx-auto">Click "+ Student Discount / Override" to apply sibling discounts or merit scholarships.</p>
                            </div>
                            <div v-else class="space-y-3 max-h-[460px] overflow-y-auto pr-1">
                                <div v-for="ov in fee_overrides" :key="ov.id" class="rounded-2xl border border-[#DADCE0] p-4 bg-white flex items-center justify-between shadow-2xs hover:shadow-sm transition-all">
                                    <div>
                                        <div class="font-bold text-xs text-[#202124]">{{ ov.student_name }} <span class="text-[#5F6368] font-normal">({{ ov.roll_number || ov.admission_number }})</span></div>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span class="inline-block px-2 py-0.5 rounded text-2xs font-extrabold uppercase bg-[#FEF7E0] text-[#B06000]">{{ ov.override_reason?.replace('_', ' ') }}</span>
                                            <span class="text-2xs font-bold text-[#137333]">
                                                {{ ov.discount_percentage > 0 ? `${ov.discount_percentage}% OFF` : `Rs. ${ov.discount_amount} OFF` }} ({{ ov.fee_category }})
                                            </span>
                                        </div>
                                    </div>
                                    <button @click="deleteFeeOverride(ov.id)" class="text-[#C5221F] hover:bg-[#FCE8E6] p-2 rounded-lg font-bold text-2xs transition-colors" title="Delete Override"><i class="fa-solid fa-trash"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ================= TAB: STAFF ATTENDANCE & LEAVES ================= -->
                <div v-if="activeTab === 'staff_attendance'" class="space-y-6 animate-fadeIn">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-3xl border border-[#DADCE0] shadow-sm">
                        <div>
                            <h3 class="text-xl font-extrabold text-[#202124] flex items-center gap-2.5">
                                <i class="fa-solid fa-stopwatch text-[#B06000]"></i>
                                <span>Staff Attendance & Leave Approval Workflow</span>
                                
                            </h3>
                            <p class="text-xs text-[#5F6368] mt-1">Mark daily check-ins, approve staff leave applications, and track attendance percentage for payroll calculations.</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <button @click="showStaffLeaveModal = true" class="bg-[#F8F9FA] hover:bg-[#F1F3F4] text-[#202124] border border-[#DADCE0] px-4 py-2.5 rounded-full font-bold text-xs transition-all flex-shrink-0">
                                + Log Leave Request
                            </button>
                            <button @click="showStaffCheckInModal = true" class="bg-[#1A73E8] hover:bg-[#1557B0] text-white px-5 py-2.5 rounded-full font-bold text-xs shadow-sm transition-all active:scale-95 flex-shrink-0 inline-flex items-center gap-1.5">
                                <i class="fa-solid fa-check"></i>
                                <span>Mark Staff Attendance</span>
                            </button>
                        </div>
                    </div>

                    <!-- Grid: Leave Requests Queue vs Staff Roster -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Leave Approval Queue -->
                        <div class="lg:col-span-2 bg-white rounded-3xl border border-[#DADCE0] shadow-sm p-6 space-y-4">
                            <div class="flex items-center justify-between border-b border-[#DADCE0] pb-3">
                                <h4 class="font-extrabold text-sm text-[#202124] flex items-center gap-1.5"><i class="fa-solid fa-clipboard-list text-[#B06000]"></i> <span>Staff Leave Requests & Principal Approvals</span></h4>
                                <span class="bg-[#FEF7E0] text-[#B06000] text-2xs font-bold px-2.5 py-0.5 rounded-full">
                                    {{ staff_leaves?.filter(l => l.status === 'pending').length || 0 }} Pending Review
                                </span>
                            </div>
                            <div v-if="!staff_leaves || staff_leaves.length === 0" class="py-12 text-center text-[#5F6368] space-y-2">
                                <div class="text-3xl text-[#137333]"><i class="fa-solid fa-umbrella-beach"></i></div>
                                <div class="font-bold text-xs">No Staff Leave Requests Logged</div>
                                <p class="text-2xs max-w-xs mx-auto">When staff submit leave requests (casual, sick, annual), they will appear here for Principal approval.</p>
                            </div>
                            <div v-else class="space-y-3.5 max-h-[500px] overflow-y-auto pr-1">
                                <div v-for="l in staff_leaves" :key="l.id" class="rounded-2xl border border-[#DADCE0] p-4 bg-[#F8F9FA]/50 hover:bg-white transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-2xs">
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2">
                                            <span class="font-extrabold text-sm text-[#202124]">{{ l.staff_name }}</span>
                                            <span class="text-2xs uppercase font-bold bg-[#E8F0FE] text-[#1A73E8] px-2 py-0.5 rounded-full">{{ l.role }}</span>
                                            <span :class="[
                                                l.status === 'approved' ? 'bg-[#E6F4EA] text-[#137333]' : (l.status === 'rejected' ? 'bg-[#FCE8E6] text-[#C5221F]' : 'bg-[#FEF7E0] text-[#B06000]'),
                                                'px-2 py-0.5 rounded-full text-2xs font-extrabold uppercase'
                                            ]">{{ l.status }}</span>
                                        </div>
                                        <div class="text-xs font-semibold text-[#202124]/90">
                                            {{ l.leave_type?.toUpperCase() }} Leave: {{ l.start_date }} to {{ l.end_date }} ({{ l.days_count }} Days)
                                        </div>
                                        <div class="text-2xs text-[#5F6368] italic">Reason: "{{ l.reason }}"</div>
                                        <div v-if="l.admin_remarks" class="text-2xs text-[#1A73E8] font-semibold mt-1">Principal Note: {{ l.admin_remarks }}</div>
                                    </div>
                                    <div v-if="l.status === 'pending'" class="flex items-center gap-2 flex-shrink-0">
                                        <button @click="updateLeaveApproval(l.id, 'approved')" class="bg-[#E6F4EA] hover:bg-[#CEEAD6] text-[#137333] px-3.5 py-1.5 rounded-xl font-extrabold text-2xs transition-colors shadow-2xs inline-flex items-center gap-1">
                                            <i class="fa-solid fa-check"></i>
                                            <span>Approve</span>
                                        </button>
                                        <button @click="updateLeaveApproval(l.id, 'rejected')" class="bg-[#FCE8E6] hover:bg-[#FAD2CF] text-[#C5221F] px-3.5 py-1.5 rounded-xl font-extrabold text-2xs transition-colors shadow-2xs inline-flex items-center gap-1">
                                            <i class="fa-solid fa-xmark"></i>
                                            <span>Reject</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Student Leave Requests Panel -->
                        <div class="lg:col-span-3 bg-white rounded-3xl border border-[#DADCE0] shadow-sm p-6 space-y-4">
                            <div class="flex items-center justify-between border-b border-[#DADCE0] pb-3">
                                <h4 class="font-extrabold text-sm text-[#202124] flex items-center gap-1.5"><i class="fa-solid fa-child-reaching text-[#8430CE]"></i> <span>Student Leave Requests (Parent &amp; Self-Submitted)</span></h4>
                                <span class="bg-[#F3E8FD] text-[#8430CE] text-2xs font-bold px-2.5 py-0.5 rounded-full">
                                    {{ student_leaves?.filter(l => l.status === 'pending').length || 0 }} Pending
                                </span>
                            </div>
                            <div v-if="!student_leaves || student_leaves.length === 0" class="py-10 text-center text-[#5F6368] space-y-2">
                                <div class="text-3xl text-[#8430CE]"><i class="fa-solid fa-child"></i></div>
                                <div class="font-bold text-xs">No Student Leave Requests Yet</div>
                                <p class="text-2xs max-w-xs mx-auto">Student and parent-submitted leave requests appear here for Principal approval.</p>
                            </div>
                            <div v-else class="space-y-3 max-h-[400px] overflow-y-auto pr-1">
                                <div v-for="sl in student_leaves" :key="sl.id" class="rounded-2xl border border-[#DADCE0] p-4 bg-[#F8F9FA]/50 hover:bg-white transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-2xs">
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="font-extrabold text-sm text-[#202124]">{{ sl.student?.user?.name || 'Student' }}</span>
                                            <span class="text-2xs uppercase font-bold bg-[#E8F0FE] text-[#1A73E8] px-2 py-0.5 rounded-full">{{ sl.student?.current_class?.name || 'N/A' }} {{ sl.student?.current_section?.name ? '— ' + sl.student.current_section.name : '' }}</span>
                                            <span :class="[
                                                sl.status === 'approved' ? 'bg-[#E6F4EA] text-[#137333]' : (sl.status === 'rejected' ? 'bg-[#FCE8E6] text-[#C5221F]' : 'bg-[#FEF7E0] text-[#B06000]'),
                                                'px-2 py-0.5 rounded-full text-2xs font-extrabold uppercase'
                                            ]">{{ sl.status }}</span>
                                        </div>
                                        <div class="text-xs font-semibold text-[#202124]/90">
                                            <i class="fa-regular fa-calendar text-[#8430CE] mr-1"></i> {{ sl.start_date }} &rarr; {{ sl.end_date }}
                                        </div>
                                        <div class="text-2xs text-[#5F6368] italic">"{{ sl.reason }}"</div>
                                        <div v-if="sl.admin_remarks" class="text-2xs text-[#1A73E8] font-semibold mt-1">Principal Note: {{ sl.admin_remarks }}</div>
                                    </div>
                                    <div v-if="sl.status === 'pending'" class="flex items-center gap-2 flex-shrink-0">
                                        <button @click="updateStudentLeaveApproval(sl.id, 'approved')" class="bg-[#E6F4EA] hover:bg-[#CEEAD6] text-[#137333] px-3.5 py-1.5 rounded-xl font-extrabold text-2xs transition-colors shadow-2xs inline-flex items-center gap-1">
                                            <i class="fa-solid fa-check"></i> <span>Approve</span>
                                        </button>
                                        <button @click="updateStudentLeaveApproval(sl.id, 'rejected')" class="bg-[#FCE8E6] hover:bg-[#FAD2CF] text-[#C5221F] px-3.5 py-1.5 rounded-xl font-extrabold text-2xs transition-colors shadow-2xs inline-flex items-center gap-1">
                                            <i class="fa-solid fa-xmark"></i> <span>Reject</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Daily Attendance Roster Summary -->
                        <div class="bg-white rounded-3xl border border-[#DADCE0] shadow-sm p-6 space-y-4 lg:col-span-3">
                            <div class="border-b border-[#DADCE0] pb-3">
                                <h4 class="font-extrabold text-sm text-[#202124]">Staff Roster Check-in Status</h4>
                                <p class="text-2xs text-[#5F6368]">Today's check-in log across faculty</p>
                            </div>
                            <div class="space-y-3 max-h-[500px] overflow-y-auto pr-1 text-xs">
                                <div v-for="u in users?.filter(u => ['teacher', 'admin', 'accountant', 'registrar', 'librarian'].includes(u.user_type))" :key="u.id" class="flex items-center justify-between py-2 border-b border-[#DADCE0]/60 last:border-0">
                                    <div class="min-w-0">
                                        <div class="font-bold text-[#202124] truncate">{{ u.name }}</div>
                                        <div class="text-2xs text-[#5F6368] capitalize">{{ u.user_type }}</div>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <span v-if="staff_attendance_logs && staff_attendance_logs[u.id]" :class="[
                                            staff_attendance_logs[u.id].status === 'present' ? 'bg-[#E6F4EA] text-[#137333]' : 'bg-[#FEF7E0] text-[#B06000]',
                                            'px-2.5 py-1 rounded-full text-2xs font-extrabold uppercase'
                                        ]">
                                            {{ staff_attendance_logs[u.id].status }}
                                        </span>
                                        <span v-else class="bg-[#F1F3F4] text-[#5F6368] px-2.5 py-1 rounded-full text-2xs font-semibold uppercase">
                                            Unmarked
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ================= TAB: EXAM GRADEBOOK & TERM REPORTS ================= -->
                <div v-if="activeTab === 'exams'" class="space-y-6 animate-fadeIn">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-3xl border border-[#DADCE0] shadow-sm">
                        <div>
                            <h3 class="text-xl font-extrabold text-[#202124] flex items-center gap-2.5">
                                <i class="fa-solid fa-medal text-[#8430CE]"></i>
                                <span>Full Exam Gradebook & Term Report Cards</span>
                                
                            </h3>
                            <p class="text-xs text-[#5F6368] mt-1">Define term examination schedules, enter subject marks matrix, and auto-compute GPA & letter grades.</p>
                        </div>
                        <button @click="showCreateExamModal = true" class="bg-[#8430CE] hover:bg-[#681EAA] text-white px-5 py-2.5 rounded-full font-bold text-xs shadow-sm transition-all active:scale-95 flex-shrink-0">
                            + Create Exam Term Session
                        </button>
                    </div>

                    <!-- Gradebook Selector & Bulk Entry Card -->
                    <div class="bg-white rounded-3xl border border-[#DADCE0] shadow-sm p-6 sm:p-8 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 border-b border-[#DADCE0] pb-6">
                            <div>
                                <label class="block text-2xs font-bold text-[#5F6368] uppercase tracking-wider mb-1">1. Select Examination Session</label>
                                <select v-model="gradebookForm.exam_id" @change="loadGradebookStudents" class="w-full rounded-xl border border-[#DADCE0] px-4 py-2.5 text-xs font-bold text-[#202124] focus:ring-2 focus:ring-[#8430CE]">
                                    <option value="">-- Choose Exam Term --</option>
                                    <option v-for="ex in exams" :key="ex.id" :value="ex.id">{{ ex.name }} ({{ ex.start_date }})</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-2xs font-bold text-[#5F6368] uppercase tracking-wider mb-1">2. Select Subject Matrix</label>
                                <select v-model="gradebookForm.subject_id" @change="loadGradebookStudents" class="w-full rounded-xl border border-[#DADCE0] px-4 py-2.5 text-xs font-bold text-[#202124] focus:ring-2 focus:ring-[#8430CE]">
                                    <option value="">-- Choose Subject --</option>
                                    <option v-for="sub in subjects" :key="sub.id" :value="sub.id">{{ sub.tenantClass?.name || 'Class' }} — {{ sub.name }}</option>
                                </select>
                            </div>
                            <div class="flex items-end">
                                <button @click="loadGradebookStudents" type="button" :disabled="!gradebookForm.exam_id || !gradebookForm.subject_id" class="w-full bg-[#E8F0FE] hover:bg-[#D2E3FC] text-[#1A73E8] py-2.5 rounded-xl font-bold text-xs transition-all disabled:opacity-50 inline-flex items-center justify-center gap-1.5">
                                    <i class="fa-solid fa-rotate"></i>
                                    <span>Load Class Roster Matrix</span>
                                </button>
                            </div>
                        </div>

                        <!-- Roster Marks Input Matrix -->
                        <div v-if="!gradebookForm.marks || gradebookForm.marks.length === 0" class="py-12 text-center text-[#5F6368] space-y-2">
                            <div class="text-4xl text-[#8430CE]"><i class="fa-solid fa-chart-column"></i></div>
                            <div class="font-bold text-sm text-[#202124]">No Students Loaded into Gradebook Matrix</div>
                            <p class="text-xs max-w-md mx-auto">Select an Exam Term and Subject above, then click "Load Class Roster Matrix" to input marks and calculate grades automatically.</p>
                        </div>
                        <form v-else @submit.prevent="submitBulkGradebook" class="space-y-6">
                            <div class="overflow-x-auto">
                                <table class="w-full text-left border-collapse">
                                    <thead>
                                        <tr class="border-b border-[#DADCE0] bg-[#F8F9FA] text-2xs uppercase tracking-wider font-extrabold text-[#5F6368]">
                                            <th class="py-3.5 px-4">Student Demographics</th>
                                            <th class="py-3.5 px-4 w-28">Theory (100)</th>
                                            <th class="py-3.5 px-4 w-28">Practical</th>
                                            <th class="py-3.5 px-4 w-28">Viva</th>
                                            <th class="py-3.5 px-4">Remarks / Notes</th>
                                            <th class="py-3.5 px-4 text-right">Auto Grade</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-[#DADCE0] text-xs font-medium text-[#202124]">
                                        <tr v-for="(row, idx) in gradebookForm.marks" :key="row.student_id" class="hover:bg-[#F8F9FA]/60">
                                            <td class="py-3 px-4 font-bold text-[#1A73E8]">
                                                {{ row.student_name }}
                                                <div class="text-2xs font-normal text-[#5F6368]">Roll #: {{ row.roll_number }}</div>
                                            </td>
                                            <td class="py-3 px-4">
                                                <input v-model="row.theory_marks" type="number" step="0.5" min="0" max="100" class="w-20 rounded-lg border border-[#DADCE0] px-2.5 py-1.5 text-xs font-bold text-center focus:ring-2 focus:ring-[#8430CE]" />
                                            </td>
                                            <td class="py-3 px-4">
                                                <input v-model="row.practical_marks" type="number" step="0.5" min="0" class="w-20 rounded-lg border border-[#DADCE0] px-2.5 py-1.5 text-xs font-bold text-center focus:ring-2 focus:ring-[#8430CE]" />
                                            </td>
                                            <td class="py-3 px-4">
                                                <input v-model="row.viva_marks" type="number" step="0.5" min="0" class="w-20 rounded-lg border border-[#DADCE0] px-2.5 py-1.5 text-xs font-bold text-center focus:ring-2 focus:ring-[#8430CE]" />
                                            </td>
                                            <td class="py-3 px-4">
                                                <input v-model="row.teacher_remarks" type="text" placeholder="Conduct & performance notes" class="w-full rounded-lg border border-[#DADCE0] px-3 py-1.5 text-xs" />
                                            </td>
                                            <td class="py-3 px-4 text-right">
                                                <span :class="[
                                                    (row.theory_marks + row.practical_marks + row.viva_marks) >= 80 ? 'bg-[#E6F4EA] text-[#137333]' : ((row.theory_marks + row.practical_marks + row.viva_marks) >= 50 ? 'bg-[#E8F0FE] text-[#1A73E8]' : 'bg-[#FCE8E6] text-[#C5221F]'),
                                                    'px-3 py-1 rounded-full text-2xs font-extrabold inline-block shadow-2xs'
                                                ]">
                                                    {{ (row.theory_marks + row.practical_marks + row.viva_marks) >= 90 ? 'A+ (4.0)' : ((row.theory_marks + row.practical_marks + row.viva_marks) >= 80 ? 'A (3.7)' : ((row.theory_marks + row.practical_marks + row.viva_marks) >= 70 ? 'B (3.0)' : ((row.theory_marks + row.practical_marks + row.viva_marks) >= 60 ? 'C (2.0)' : 'D / F'))) }}
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="flex justify-end pt-4 border-t border-[#DADCE0]">
                                <button type="submit" :disabled="gradebookForm.processing" class="bg-[#8430CE] hover:bg-[#681EAA] text-white px-8 py-3 rounded-full font-bold text-xs shadow-md transition-all active:scale-95 disabled:opacity-50 inline-flex items-center gap-2">
                                    <i class="fa-solid fa-check"></i>
                                    <span>{{ gradebookForm.processing ? 'Saving & Computing Grades...' : 'Save Gradebook Marks & Auto-Compute GPA' }}</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- ================= TAB: REPORTS & ANALYTICS ================= -->
                <div v-if="activeTab === 'reports'" class="space-y-8 animate-fadeIn">
                    <div class="google-card rounded-2xl border border-[#DADCE0] bg-white p-6 sm:p-8 shadow-2xs">
                        <div class="flex items-center justify-between border-b border-[#DADCE0] pb-5 mb-6">
                            <div>
                                <h3 class="text-lg font-extrabold text-[#202124] flex items-center gap-2">
                                    <i class="fa-solid fa-chart-line text-[#8430CE]"></i>
                                    <span>Centralized Reporting Module</span>
                                </h3>
                                <p class="text-xs text-[#5F6368] mt-1">Exportable, filterable, and printable reports across all school domains.</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            
                            <!-- Student Reports -->
                            <div class="rounded-2xl border border-[#DADCE0] bg-[#F8F9FA] p-6 flex flex-col shadow-xs transition-all hover:shadow-md">
                                <h4 class="text-base font-extrabold text-[#202124] flex items-center gap-2 mb-2">
                                    <i class="fa-solid fa-user-graduate text-[#1A73E8]"></i> Student Reports
                                </h4>
                                <p class="text-xs text-[#5F6368] mb-4">Demographics, enrollment, and lifecycle tracking.</p>
                                <div class="mt-auto space-y-3">
                                    <div class="relative">
                                        <select id="studentReportType" class="w-full appearance-none text-sm border-2 border-[#DADCE0] rounded-xl shadow-xs focus:border-[#1A73E8] focus:ring-4 focus:ring-[#1A73E8]/10 bg-white hover:border-[#1A73E8]/50 transition-all py-3 pl-4 pr-10 cursor-pointer font-medium text-[#202124]">
                                            <option value="student_directory">Student Directory</option>
                                            <option value="enrollment_report">Enrollment Report</option>
                                            <option value="admission_report">Admission Report</option>
                                            <option value="active_students">Active Students</option>
                                            <option value="graduated_students">Graduated Students</option>
                                            <option value="suspended_students">Suspended Students</option>
                                            <option value="attendance_report">Attendance Report</option>
                                            <option value="academic_performance">Academic Performance</option>
                                            <option value="student_history">Student History</option>
                                            <option value="student_documents">Student Documents</option>
                                        </select>
                                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-[#5F6368]">
                                            <i class="fa-solid fa-chevron-down text-xs"></i>
                                        </div>
                                    </div>
                                    <button type="button" @click="downloadReport($event.target.closest('.mt-auto').querySelector('select').value)" class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-[#1A73E8] py-2 text-sm font-bold text-white hover:bg-[#1557B0] transition-all cursor-pointer">
                                        <i class="fa-solid fa-file-export"></i> Export to CSV
                                    </button>
                                </div>
                            </div>

                            <!-- Fee Reports -->
                            <div class="rounded-2xl border border-[#DADCE0] bg-[#F8F9FA] p-6 flex flex-col shadow-xs transition-all hover:shadow-md">
                                <h4 class="text-base font-extrabold text-[#202124] flex items-center gap-2 mb-2">
                                    <i class="fa-solid fa-money-bill-wave text-[#137333]"></i> Fee Reports
                                </h4>
                                <p class="text-xs text-[#5F6368] mb-4">Financials, collections, and overdue accounts.</p>
                                <div class="mt-auto space-y-3">
                                    <div class="relative">
                                        <select id="feeReportType" class="w-full appearance-none text-sm border-2 border-[#DADCE0] rounded-xl shadow-xs focus:border-[#137333] focus:ring-4 focus:ring-[#137333]/10 bg-white hover:border-[#137333]/50 transition-all py-3 pl-4 pr-10 cursor-pointer font-medium text-[#202124]">
                                            <option value="total_collection">Total Collection</option>
                                            <option value="outstanding_fees">Outstanding Fees</option>
                                            <option value="overdue_payments">Overdue Payments</option>
                                            <option value="total_issued_challans">Total Issued Challans</option>
                                            <option value="paid_challans">Paid Challans</option>
                                            <option value="pending_challans">Pending Challans</option>
                                            <option value="daily_collection">Daily Collection</option>
                                            <option value="monthly_collection">Monthly Collection</option>
                                            <option value="annual_collection">Annual Collection</option>
                                            <option value="fee_collection_trends">Fee Collection Trends</option>
                                            <option value="scholarship_reports">Scholarship Reports</option>
                                            <option value="late_fee_reports">Late Fee Reports</option>
                                            <option value="revenue_analytics">Revenue Analytics</option>
                                        </select>
                                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-[#5F6368]">
                                            <i class="fa-solid fa-chevron-down text-xs"></i>
                                        </div>
                                    </div>
                                    <button type="button" @click="downloadReport($event.target.closest('.mt-auto').querySelector('select').value)" class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-[#137333] py-2 text-sm font-bold text-white hover:bg-[#0D5323] transition-all cursor-pointer">
                                        <i class="fa-solid fa-file-export"></i> Export to CSV
                                    </button>
                                </div>
                            </div>

                            <!-- Staff Reports -->
                            <div class="rounded-2xl border border-[#DADCE0] bg-[#F8F9FA] p-6 flex flex-col shadow-xs transition-all hover:shadow-md">
                                <h4 class="text-base font-extrabold text-[#202124] flex items-center gap-2 mb-2">
                                    <i class="fa-solid fa-user-tie text-[#E94235]"></i> Staff Reports
                                </h4>
                                <p class="text-xs text-[#5F6368] mb-4">Directory, payroll, and teacher performance.</p>
                                <div class="mt-auto space-y-3">
                                    <div class="relative">
                                        <select id="staffReportType" class="w-full appearance-none text-sm border-2 border-[#DADCE0] rounded-xl shadow-xs focus:border-[#E94235] focus:ring-4 focus:ring-[#E94235]/10 bg-white hover:border-[#E94235]/50 transition-all py-3 pl-4 pr-10 cursor-pointer font-medium text-[#202124]">
                                            <option value="staff_directory">Staff Directory</option>
                                            <option value="teacher_report">Teacher Report</option>
                                            <option value="staff_attendance_report">Attendance Report</option>
                                            <option value="leave_report">Leave Report</option>
                                            <option value="department_report">Department Report</option>
                                            <option value="payroll_ready">Payroll-ready Summaries</option>
                                        </select>
                                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-[#5F6368]">
                                            <i class="fa-solid fa-chevron-down text-xs"></i>
                                        </div>
                                    </div>
                                    <button type="button" @click="downloadReport($event.target.closest('.mt-auto').querySelector('select').value)" class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-[#E94235] py-2 text-sm font-bold text-white hover:bg-[#C5221F] transition-all cursor-pointer">
                                        <i class="fa-solid fa-file-export"></i> Export to CSV
                                    </button>
                                </div>
                            </div>

                            <!-- Class Reports -->
                            <div class="rounded-2xl border border-[#DADCE0] bg-[#F8F9FA] p-6 flex flex-col shadow-xs transition-all hover:shadow-md">
                                <h4 class="text-base font-extrabold text-[#202124] flex items-center gap-2 mb-2">
                                    <i class="fa-solid fa-chalkboard-user text-[#F29900]"></i> Class Reports
                                </h4>
                                <p class="text-xs text-[#5F6368] mb-4">Capacities, subject allocations, and schedules.</p>
                                <div class="mt-auto space-y-3">
                                    <div class="relative">
                                        <select id="classReportType" class="w-full appearance-none text-sm border-2 border-[#DADCE0] rounded-xl shadow-xs focus:border-[#F29900] focus:ring-4 focus:ring-[#F29900]/10 bg-white hover:border-[#F29900]/50 transition-all py-3 pl-4 pr-10 cursor-pointer font-medium text-[#202124]">
                                            <option value="class_strength">Class Strength</option>
                                            <option value="subject_allocation">Subject Allocation</option>
                                            <option value="class_attendance_summary">Attendance Summary</option>
                                            <option value="performance_summary">Performance Summary</option>
                                            <option value="timetable_report">Timetable Report</option>
                                            <option value="promotion_report">Promotion Report</option>
                                        </select>
                                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-[#5F6368]">
                                            <i class="fa-solid fa-chevron-down text-xs"></i>
                                        </div>
                                    </div>
                                    <button type="button" @click="downloadReport($event.target.closest('.mt-auto').querySelector('select').value)" class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-[#F29900] py-2 text-sm font-bold text-white hover:bg-[#D58300] transition-all cursor-pointer">
                                        <i class="fa-solid fa-file-export"></i> Export to CSV
                                    </button>
                                </div>
                            </div>

                            <!-- Examination Reports -->
                            <div class="rounded-2xl border border-[#DADCE0] bg-[#F8F9FA] p-6 flex flex-col shadow-xs transition-all hover:shadow-md">
                                <h4 class="text-base font-extrabold text-[#202124] flex items-center gap-2 mb-2">
                                    <i class="fa-solid fa-file-contract text-[#8430CE]"></i> Examination
                                </h4>
                                <p class="text-xs text-[#5F6368] mb-4">Results, grade distributions, and performance.</p>
                                <div class="mt-auto space-y-3">
                                    <div class="relative">
                                        <select id="examReportType" class="w-full appearance-none text-sm border-2 border-[#DADCE0] rounded-xl shadow-xs focus:border-[#8430CE] focus:ring-4 focus:ring-[#8430CE]/10 bg-white hover:border-[#8430CE]/50 transition-all py-3 pl-4 pr-10 cursor-pointer font-medium text-[#202124]">
                                            <option value="marks_reports">Marks Reports</option>
                                            <option value="result_analysis">Result Analysis</option>
                                            <option value="subject_performance">Subject Performance</option>
                                            <option value="grade_distribution">Grade Distribution</option>
                                            <option value="class_comparison">Class Comparison</option>
                                            <option value="pass_fail_statistics">Pass/Fail Statistics</option>
                                        </select>
                                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-[#5F6368]">
                                            <i class="fa-solid fa-chevron-down text-xs"></i>
                                        </div>
                                    </div>
                                    <button type="button" @click="downloadReport($event.target.closest('.mt-auto').querySelector('select').value)" class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-[#8430CE] py-2 text-sm font-bold text-white hover:bg-[#6824A4] transition-all cursor-pointer">
                                        <i class="fa-solid fa-file-export"></i> Export to CSV
                                    </button>
                                </div>
                            </div>

                            <!-- Administrative Reports -->
                            <div class="rounded-2xl border border-[#DADCE0] bg-[#F8F9FA] p-6 flex flex-col shadow-xs transition-all hover:shadow-md">
                                <h4 class="text-base font-extrabold text-[#202124] flex items-center gap-2 mb-2">
                                    <i class="fa-solid fa-shield-halved text-[#5F6368]"></i> Admin Logs
                                </h4>
                                <p class="text-xs text-[#5F6368] mb-4">System health, audit trails, and pipelines.</p>
                                <div class="mt-auto space-y-3">
                                    <div class="relative">
                                        <select id="adminReportType" class="w-full appearance-none text-sm border-2 border-[#DADCE0] rounded-xl shadow-xs focus:border-[#5F6368] focus:ring-4 focus:ring-[#5F6368]/10 bg-white hover:border-[#5F6368]/50 transition-all py-3 pl-4 pr-10 cursor-pointer font-medium text-[#202124]">
                                            <option value="admission_pipeline">Admission Pipeline</option>
                                            <option value="interview_report">Interview Report</option>
                                            <option value="teacher_recruitment">Teacher Recruitment</option>
                                            <option value="user_activity_logs">User Activity Logs</option>
                                            <option value="login_history">Login History</option>
                                            <option value="audit_logs">Audit Logs</option>
                                            <option value="payment_gateway_transactions">Gateway Transactions</option>
                                            <option value="system_health">System Health Reports</option>
                                        </select>
                                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-[#5F6368]">
                                            <i class="fa-solid fa-chevron-down text-xs"></i>
                                        </div>
                                    </div>
                                    <button type="button" @click="downloadReport($event.target.closest('.mt-auto').querySelector('select').value)" class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-[#5F6368] py-2 text-sm font-bold text-white hover:bg-[#3C4043] transition-all cursor-pointer">
                                        <i class="fa-solid fa-file-export"></i> Export to CSV
                                    </button>
                                </div>
                            </div>

                            <!-- Certificates Generator -->
                            <div class="rounded-2xl border border-[#DADCE0] bg-[#F8F9FA] p-6 flex flex-col md:col-span-2 lg:col-span-3">
                                <h4 class="text-base font-extrabold text-[#202124] flex items-center gap-2 mb-2">
                                    <i class="fa-solid fa-certificate text-[#F59E0B]"></i> Automated Certificates
                                </h4>
                                <p class="text-xs text-[#5F6368] mb-4">Auto-generated branded certificates for students. You can generate these directly from a student's profile action menu.</p>
                                <div class="flex flex-wrap gap-2.5 mb-4">
                                    <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-bold bg-[#FEF7E0] text-[#B06000] border border-[#FDE293] shadow-sm"><i class="fa-solid fa-file-lines mr-1.5"></i> Student Leaving Certificate (SLC)</span>
                                    <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-bold bg-[#FEF7E0] text-[#B06000] border border-[#FDE293] shadow-sm"><i class="fa-solid fa-file-lines mr-1.5"></i> Character Certificate</span>
                                    <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-bold bg-[#FEF7E0] text-[#B06000] border border-[#FDE293] shadow-sm"><i class="fa-solid fa-file-lines mr-1.5"></i> Bonafide Certificate</span>
                                    <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-bold bg-[#FEF7E0] text-[#B06000] border border-[#FDE293] shadow-sm"><i class="fa-solid fa-file-invoice mr-1.5"></i> Fee Certificate / Letter</span>
                                </div>
                                <div class="mt-auto">
                                    <Link :href="route('tenant.admin.certificates.index')" class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 py-2.5 text-xs font-bold text-white transition-all shadow-xs">
                                        <i class="fa-solid fa-folder-open"></i> Open Certificate Center
                                    </Link>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- ================= TAB: FEE APPROVALS ================= -->
                <div v-if="activeTab === 'fee_approvals'" class="space-y-6 animate-fadeIn">
                    <div class="google-card rounded-2xl border border-[#DADCE0] bg-white p-6 shadow-2xs">
                        <div class="flex items-center justify-between border-b border-[#DADCE0] pb-4 mb-4">
                            <div>
                                <h3 class="text-base font-extrabold text-[#202124]">Student Fee Slip Approvals</h3>
                                <p class="text-xs text-[#5F6368] mt-0.5">Verify bank transfers and cash receipt screenshots uploaded by parents/students.</p>
                            </div>
                            <span class="rounded-full bg-[#E8F0FE] px-3 py-1 text-xs font-bold text-[#1A73E8]">
                                {{ pending_payments ? pending_payments.length : 0 }} Awaiting Verification
                            </span>
                        </div>

                        <div v-if="!pending_payments || pending_payments.length === 0" class="py-12 text-center text-[#5F6368] text-sm">
                            <i class="fa-solid fa-circle-check text-4xl text-[#137333] mb-3 block"></i>
                            <p class="font-bold text-[#202124]">All payments verified!</p>
                            <p class="text-xs mt-1">There are no student fee receipts currently awaiting approval.</p>
                        </div>

                        <div v-else class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="border-b border-[#DADCE0] text-[#5F6368] font-bold">
                                        <th class="py-3 px-4">Student Info</th>
                                        <th class="py-3 px-4">Challan Number</th>
                                        <th class="py-3 px-4">Amount</th>
                                        <th class="py-3 px-4">Reference No</th>
                                        <th class="py-3 px-4">Receipt Screenshot</th>
                                        <th class="py-3 px-4 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#E8EAED] font-medium text-[#202124]">
                                    <tr v-for="pay in pending_payments" :key="pay.id" class="hover:bg-[#F8F9FA]/50">
                                        <td class="py-4 px-4">
                                            <div class="font-bold text-sm text-[#1A73E8]">{{ pay.student_name }}</div>
                                            <div class="text-3xs text-[#5F6368] font-mono">Roll: {{ pay.roll_number }} • {{ pay.class_name }} - {{ pay.section_name }}</div>
                                        </td>
                                        <td class="py-4 px-4 font-mono font-bold">{{ pay.challan_number }} ({{ pay.month_year }})</td>
                                        <td class="py-4 px-4 font-bold text-sm text-[#137333]">PKR {{ pay.amount.toLocaleString() }}</td>
                                        <td class="py-4 px-4 font-mono font-bold">{{ pay.transaction_reference }}</td>
                                        <td class="py-4 px-4">
                                            <button 
                                                v-if="pay.receipt_path"
                                                type="button"
                                                @click="selectedFeeReceipt = pay.receipt_path"
                                                class="rounded-lg bg-[#F1F3F4] hover:bg-[#E8EAED] px-3 py-1.5 font-bold text-[#1A73E8] inline-flex items-center gap-1"
                                            >
                                                <i class="fa-solid fa-image"></i>
                                                <span>View Slip</span>
                                            </button>
                                            <span v-else class="text-[#C5221F] font-bold">No Image</span>
                                        </td>
                                        <td class="py-4 px-4 text-right space-x-2">
                                            <button 
                                                @click="approveFeePaymentReceipt(pay.id)"
                                                class="rounded-full bg-[#137333] hover:bg-[#0D652D] px-4 py-2 font-bold text-white shadow-sm inline-flex items-center gap-1"
                                            >
                                                <i class="fa-solid fa-check"></i> Approve
                                            </button>
                                            <button 
                                                @click="rejectFeePaymentReceipt(pay.id)"
                                                class="rounded-full bg-[#C5221F] hover:bg-[#A51D19] px-4 py-2 font-bold text-white shadow-sm inline-flex items-center gap-1"
                                            >
                                                <i class="fa-solid fa-xmark"></i> Reject
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Fee Receipt Modal Dialog -->
                <div v-if="selectedFeeReceipt" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4">
                    <div class="bg-white rounded-2xl max-w-2xl w-full p-6 shadow-2xl flex flex-col relative max-h-[85vh]">
                        <div class="flex justify-between items-center border-b border-[#DADCE0] pb-3 mb-4">
                            <h3 class="font-bold text-[#202124] text-sm uppercase tracking-wider flex items-center gap-1.5">
                                <i class="fa-solid fa-file-invoice-dollar"></i> Student Fee Receipt Slip Verification
                            </h3>
                            <button @click="selectedFeeReceipt = null" class="h-8 w-8 rounded-full hover:bg-[#F8F9FA] font-bold text-[#5F6368] flex items-center justify-center cursor-pointer"><i class="fa-solid fa-xmark text-lg"></i></button>
                        </div>
                        <div class="flex-1 overflow-y-auto flex items-center justify-center bg-[#F8F9FA] rounded-xl border border-[#DADCE0] p-2">
                            <img :src="selectedFeeReceipt" class="max-h-[60vh] object-contain rounded-lg shadow-sm" alt="Student Fee Slip" />
                        </div>
                        <div class="mt-4 flex justify-end">
                            <button @click="selectedFeeReceipt = null" class="rounded-full bg-[#1A73E8] px-6 py-2.5 font-bold text-white shadow-sm hover:bg-[#1557B0]">Close Viewer</button>
                        </div>
                    </div>
                </div>

                <!-- ================= TAB: PLATFORM SUBSCRIPTION ================= -->
                <div v-if="activeTab === 'subscription'" class="space-y-6 animate-fadeIn">
                    <!-- Current Plan status card -->
                    <div class="google-card rounded-2xl border border-[#DADCE0] bg-white p-6 shadow-2xs">
                        <h3 class="text-base font-extrabold text-[#202124] mb-4 flex items-center gap-2">
                            <i class="fa-solid fa-key text-[#1A73E8]"></i>
                            <span>LMS Cloud Platform License &amp; Billing</span>
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="p-4 rounded-xl bg-[#F8F9FA] border border-[#DADCE0]">
                                <span class="text-3xs font-bold text-[#5F6368] uppercase tracking-wider block">Current Plan</span>
                                <span class="text-lg font-black text-[#1A73E8] block mt-1">{{ currentPlan ? currentPlan.name : 'Starter' }}</span>
                                <span class="text-2xs text-[#5F6368] block mt-1">{{ currentPlan?.tagline || 'Small schools' }}</span>
                            </div>
                            <div class="p-4 rounded-xl bg-[#F8F9FA] border border-[#DADCE0]">
                                <span class="text-3xs font-bold text-[#5F6368] uppercase tracking-wider block">Pricing Model</span>
                                <span class="text-lg font-black text-[#202124] block mt-1">{{ currentPlan?.pricing_type === 'custom' ? 'Custom' : ('PKR ' + (currentPlan?.price_per_student || 20) + ' / student') }}</span>
                                <span class="text-2xs text-[#5F6368] block mt-1">Min. bill: PKR {{ (currentPlan?.minimum_monthly_bill || 2000).toLocaleString() }}/mo</span>
                            </div>
                            <div class="p-4 rounded-xl bg-[#F8F9FA] border border-[#DADCE0]">
                                <span class="text-3xs font-bold text-[#5F6368] uppercase tracking-wider block">License Status</span>
                                <span :class="tenant.status === 'active' ? 'bg-[#E6F4EA] text-[#137333] border-[#CEEAD6]' : 'bg-[#FEF7E0] text-[#B06000] border-[#FDE293]'" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold border mt-2">
                                    <i :class="tenant.status === 'active' ? 'fa-solid fa-circle-check mr-1' : 'fa-solid fa-hourglass-half mr-1'"></i>
                                    {{ tenant.status === 'active' ? 'Licensed (Active)' : 'Pending Approval' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- License Renewal form -->
                        <div class="google-card rounded-2xl border border-[#DADCE0] bg-white p-6 shadow-2xs space-y-4">
                            <h3 class="text-sm font-extrabold text-[#202124] uppercase tracking-wider border-b border-[#DADCE0] pb-2">Renew or Upgrade Subscription</h3>
                            
                            <form @submit.prevent="submitRenewal" class="space-y-4 text-xs">
                                <div>
                                    <label class="block text-2xs font-bold text-[#202124] mb-1.5">Select Subscription Plan</label>
                                    <select v-model="renewalPlan" required class="w-full rounded-xl border border-[#DADCE0] bg-white px-3.5 py-2.5 focus:border-[#1A73E8] focus:outline-none">
                                        <option v-for="p in saas_plans" :key="p.id" :value="p">{{ p.name }} ({{ p.pricing_type === 'per_student' ? ('PKR ' + p.price_per_student + '/student/mo') : 'Custom Volume' }})</option>
                                    </select>
                                </div>

                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-2xs font-bold text-[#202124] mb-1.5">Billing Cycle</label>
                                        <select v-model="renewalCycle" class="w-full rounded-xl border border-[#DADCE0] bg-white px-3.5 py-2.5 focus:border-[#1A73E8] focus:outline-none">
                                            <option value="monthly">Monthly</option>
                                            <option value="yearly">Yearly (20% Discount)</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-2xs font-bold text-[#202124] mb-1.5">Amount Due Today</label>
                                        <div class="w-full rounded-xl bg-[#F8F9FA] border border-[#DADCE0] px-3.5 py-2.5 font-bold text-sm text-[#137333]">
                                            PKR {{ renewalPlan ? (renewalCycle === 'yearly' ? Math.round(renewalPlan.price_monthly * 12 * 0.8) : renewalPlan.price_monthly).toLocaleString() : '0' }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Central Platform Bank Details -->
                                <div class="rounded-xl bg-[#E8F0FE]/40 p-4 border border-[#D2E3FC] space-y-2">
                                    <span class="text-3xs font-extrabold text-[#1A73E8] uppercase tracking-wider block">Central Platform Deposit Account</span>
                                    <p class="text-3xs text-[#5F6368]">Transfer subscription fee to the central account and enter the reference ID below:</p>
                                    <div class="grid grid-cols-2 gap-1 text-[10px] font-bold text-[#202124]">
                                        <div><span class="text-3xs text-[#5F6368] block">Bank Name</span> Meezan Bank Ltd</div>
                                        <div><span class="text-3xs text-[#5F6368] block">Account Title</span> LMS Cloud Pakistan</div>
                                        <div><span class="text-3xs text-[#5F6368] block">Account No</span> 1201-0982-12001</div>
                                        <div><span class="text-3xs text-[#5F6368] block">IBAN</span> PK00MEZN01201098212001</div>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-2xs font-bold text-[#202124] mb-1.5">Transaction reference ID <span class="text-[#C5221F]">*</span></label>
                                    <input 
                                        v-model="renewalForm.transaction_reference" 
                                        type="text" 
                                        required 
                                        placeholder="Enter Bank Transfer Reference or TID" 
                                        class="w-full rounded-xl border border-[#DADCE0] bg-white px-3.5 py-2.5 focus:border-[#1A73E8] focus:outline-none font-mono" 
                                    />
                                </div>

                                <div>
                                    <label class="block text-2xs font-bold text-[#202124] mb-1.5">Upload Receipt Slip screenshot <span class="text-[#C5221F]">*</span></label>
                                    <input 
                                        type="file" 
                                        @change="handleRenewalReceiptUpload"
                                        accept="image/*"
                                        required 
                                        class="w-full file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-3xs file:font-bold file:bg-[#E8F0FE] file:text-[#1A73E8] hover:file:bg-[#D2E3FC] cursor-pointer" 
                                    />
                                </div>

                                <button 
                                    type="submit" 
                                    :disabled="renewalForm.processing"
                                    class="w-full rounded-full bg-[#1A73E8] hover:bg-[#1557B0] py-3 text-xs font-bold text-white shadow-sm flex items-center justify-center gap-1.5"
                                >
                                    <i class="fa-solid fa-cloud-arrow-up"></i>
                                    <span>Submit Renewal Slip</span>
                                </button>
                            </form>
                        </div>

                        <!-- Historical subscription transactions -->
                        <div class="google-card rounded-2xl border border-[#DADCE0] bg-white p-6 shadow-2xs space-y-4">
                            <h3 class="text-sm font-extrabold text-[#202124] uppercase tracking-wider border-b border-[#DADCE0] pb-2">Platform Subscription History</h3>
                            <div class="space-y-3 max-h-[360px] overflow-y-auto pr-1">
                                <div v-if="!saas_transactions || saas_transactions.length === 0" class="py-6 text-center text-xs text-[#5F6368]">
                                    No past renewal transactions found.
                                </div>
                                <div v-for="txn in saas_transactions" :key="txn.id" class="p-3 bg-[#F8F9FA] rounded-xl border border-[#DADCE0] flex items-center justify-between text-xs font-medium">
                                    <div>
                                        <p class="font-extrabold text-[#202124]">{{ txn.plan_name }}</p>
                                        <p class="text-3xs text-[#5F6368] font-mono mt-0.5">{{ txn.transaction_code }} • Ref: {{ txn.reference_no }}</p>
                                        <p class="text-3xs text-[#5F6368] mt-0.5">{{ txn.created_at || txn.date }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-black text-[#137333]">PKR {{ txn.amount.toLocaleString() }}</p>
                                        <span :class="txn.status === 'paid' ? 'bg-[#E6F4EA] text-[#137333]' : txn.status === 'pending' ? 'bg-[#FEF7E0] text-[#B06000]' : 'bg-[#FCE8E6] text-[#C5221F]'" class="inline-block px-1.5 py-0.5 rounded text-[9px] font-bold uppercase mt-1">
                                            {{ txn.status }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ================= TAB: SETTINGS & BRANDING ================= -->
                <div v-if="activeTab === 'settings'" class="space-y-8 animate-fadeIn">
                    <!-- 1. School Crest & Logo Customization -->
                    <div class="google-card rounded-2xl border border-[#DADCE0] bg-white p-6 sm:p-8 shadow-2xs">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                            <!-- Logo Upload & Contrast Container Section -->
                            <div class="space-y-5">
                                <div>
                                    <h4 class="text-base font-extrabold text-[#202124] flex items-center gap-2">
                                        <i class="fa-solid fa-shield-halved text-[#1A73E8]"></i>
                                        <span>Official School Crest & Logo</span>
                                    </h4>
                                    <p class="text-xs text-[#5F6368] mt-1">
                                        Upload your school crest or logo in any aspect ratio (square, crest, or wide banner). Displays across the portal navigation header and login screen.
                                    </p>
                                </div>

                                <form @submit.prevent="submitLogo" class="space-y-5">
                                    <!-- Upload & Dynamic Preview Box -->
                                    <div class="rounded-2xl border-2 border-dashed border-[#DADCE0] bg-[#F8F9FA] p-6 text-center transition-all hover:border-[#1A73E8]/50">
                                        <!-- Logo Display Container -->
                                        <div class="mb-4 flex flex-col items-center">
                                            <div 
                                                v-if="logoPreviewUrl || $page.props.tenant?.logo || tenant?.logo" 
                                                class="h-28 min-w-[120px] max-w-[280px] rounded-2xl border p-3 shadow-md flex items-center justify-center overflow-hidden transition-all duration-300"
                                                :style="previewLogoBgStyle"
                                            >
                                                <img 
                                                    :src="logoPreviewUrl || $page.props.tenant?.logo || tenant?.logo" 
                                                    class="max-h-full max-w-full w-auto h-auto object-contain filter drop-shadow-[0_1px_3px_rgba(0,0,0,0.25)]" 
                                                    alt="School Logo" 
                                                />
                                            </div>
                                            <div v-else class="h-20 w-20 rounded-2xl bg-[#E8F0FE] border border-[#D2E3FC] flex items-center justify-center text-3xl text-[#1A73E8]">
                                                <i class="fa-solid fa-graduation-cap"></i>
                                            </div>

                                            <span v-if="logoPreviewUrl" class="mt-2.5 text-xs font-semibold text-[#1A73E8] bg-[#E8F0FE] px-3 py-1 rounded-full border border-[#D2E3FC]">
                                                New file ready for upload
                                            </span>
                                            <span v-else-if="$page.props.tenant?.logo || tenant?.logo" class="mt-2.5 text-xs font-semibold text-[#137333] bg-[#E6F4EA] px-3 py-1 rounded-full border border-[#CEEAD6]">
                                                Active custom logo
                                            </span>
                                            <span v-else class="mt-2.5 text-xs font-medium text-[#5F6368]">
                                                Default icon in use
                                            </span>
                                        </div>

                                        <label class="cursor-pointer inline-flex items-center justify-center rounded-full bg-white border border-[#DADCE0] px-5 py-2.5 text-xs font-bold text-[#1A73E8] shadow-2xs hover:bg-[#E8F0FE] hover:border-[#D2E3FC] transition-all">
                                            <i class="fa-solid fa-folder-open mr-2"></i>
                                            <span>{{ logoPreviewUrl ? 'Change Selected File' : 'Browse & Select Logo File' }}</span>
                                            <input type="file" @change="handleLogoSelect" accept="image/png,image/jpeg,image/jpg,image/webp,image/svg+xml" class="hidden" />
                                        </label>
                                        <p class="mt-2 text-2xs text-[#5F6368]">Supports PNG, JPG, WEBP, or SVG in any dimension up to 4MB.</p>
                                    </div>

                                    <!-- Logo Container Contrast Background Selection (Fixes white logos) -->
                                    <div class="space-y-2.5 pt-1">
                                        <div class="flex items-center justify-between">
                                            <label class="block text-xs font-bold uppercase tracking-wider text-[#202124]">
                                                Logo Backdrop Tile Style
                                            </label>
                                            <span class="text-3xs text-[#5F6368]">Optimizes contrast for white logos</span>
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                                            <button 
                                                type="button"
                                                v-for="opt in logoBgOptions" 
                                                :key="opt.id"
                                                @click="brandingForm.logo_bg = opt.id"
                                                :class="[
                                                    brandingForm.logo_bg === opt.id 
                                                        ? 'border-[#1A73E8] ring-2 ring-[#1A73E8]/30 bg-[#F4F8FE]' 
                                                        : 'border-[#DADCE0] bg-white hover:border-[#1A73E8]/40',
                                                    'p-3 rounded-xl border text-left transition-all cursor-pointer flex flex-col justify-between'
                                                ]"
                                            >
                                                <div class="flex items-center justify-between">
                                                    <span 
                                                        class="h-4 w-4 rounded-full border border-black/10 shadow-2xs flex-shrink-0"
                                                        :style="opt.id === 'brand' ? { backgroundColor: brandingForm.cover_color } : {}"
                                                        :class="opt.bgClass"
                                                    ></span>
                                                    <i v-if="brandingForm.logo_bg === opt.id" class="fa-solid fa-circle-check text-xs text-[#1A73E8]"></i>
                                                </div>
                                                <div class="mt-2">
                                                    <div class="text-xs font-bold text-[#202124]">{{ opt.name }}</div>
                                                    <div class="text-3xs text-[#5F6368] mt-0.5 leading-tight">{{ opt.badge }}</div>
                                                </div>
                                            </button>
                                        </div>
                                    </div>

                                    <div v-if="logoForm.errors.logo" class="rounded-xl bg-[#FCE8E6] border border-[#FAD2CF] p-3 text-xs font-medium text-[#C5221F]">
                                        {{ logoForm.errors.logo }}
                                    </div>

                                    <!-- Actions -->
                                    <div class="flex items-center gap-3 pt-2">
                                        <button
                                            type="submit"
                                            :disabled="!logoForm.logo || logoForm.processing"
                                            class="flex-1 rounded-full bg-[#1A73E8] py-3 px-6 text-xs font-bold text-white shadow-sm hover:bg-[#1557B0] transition-all active:scale-[0.98] disabled:opacity-40 disabled:pointer-events-none flex items-center justify-center gap-2 cursor-pointer"
                                        >
                                            <i class="fa-solid fa-cloud-arrow-up"></i>
                                            <span>{{ logoForm.processing ? 'Uploading Logo...' : 'Upload & Apply Logo' }}</span>
                                        </button>
                                        <button
                                            v-if="$page.props.tenant?.logo || tenant?.logo"
                                            type="button"
                                            @click="removeLogo"
                                            class="rounded-full bg-[#FCE8E6] border border-[#FAD2CF] py-3 px-5 text-xs font-bold text-[#C5221F] hover:bg-[#FAD2CF] transition-all flex items-center gap-1.5 cursor-pointer"
                                        >
                                            <i class="fa-solid fa-trash"></i>
                                            <span>Remove</span>
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- Live Branding Preview Column -->
                            <div class="rounded-2xl bg-[#F8F9FA] border border-[#DADCE0] p-6 space-y-5 flex flex-col justify-between">
                                <div class="space-y-4">
                                    <div class="flex items-center justify-between border-b border-[#DADCE0] pb-3">
                                        <h4 class="text-xs font-extrabold uppercase tracking-wider text-[#5F6368]">
                                            Live Header & Login Preview
                                        </h4>
                                        <span class="text-3xs font-semibold text-[#1A73E8] bg-[#E8F0FE] px-2 py-0.5 rounded-full">
                                            Interactive
                                        </span>
                                    </div>

                                    <!-- Simulated Portal Sidebar Header Preview -->
                                    <div class="space-y-1.5">
                                        <span class="text-3xs font-bold uppercase tracking-wider text-[#5F6368]">Portal Sidebar Header</span>
                                        <div class="h-16 flex items-center justify-between px-3.5 border border-[#E5E7EB] bg-white rounded-xl shadow-2xs">
                                            <div class="flex items-center gap-2.5 min-w-0 flex-1">
                                                <div 
                                                    :style="previewLogoBgStyle" 
                                                    class="h-10 min-w-[40px] max-w-[110px] px-2 py-1 rounded-xl border flex items-center justify-center overflow-hidden shadow-2xs"
                                                >
                                                    <img 
                                                        v-if="logoPreviewUrl || $page.props.tenant?.logo || tenant?.logo" 
                                                        :src="logoPreviewUrl || $page.props.tenant?.logo || tenant?.logo" 
                                                        class="max-h-full max-w-full w-auto h-auto object-contain filter drop-shadow-[0_1px_2px_rgba(0,0,0,0.22)]" 
                                                        alt="Logo Preview" 
                                                    />
                                                    <span v-else class="raibix-dot h-2.5 w-2.5"></span>
                                                </div>
                                                <div class="min-w-0 flex-1 truncate">
                                                    <div class="text-xs font-bold text-[#0B0D14] truncate">
                                                        {{ $page.props.tenant?.name || tenant?.name || 'School Name' }}
                                                    </div>
                                                    <div class="flex items-center gap-1.5 mt-0.5">
                                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                                        <span class="text-3xs font-semibold text-[#5B6270] uppercase tracking-wider">Principal</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="h-7 w-7 rounded-lg text-[#5B6270] bg-[#F8F9FA] flex items-center justify-center border border-[#E5E7EB]">
                                                <i class="fa-solid fa-chevron-left text-[10px]"></i>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Simulated Login Screen Card Preview -->
                                    <div class="space-y-1.5">
                                        <span class="text-3xs font-bold uppercase tracking-wider text-[#5F6368]">Login Screen Card</span>
                                        <div class="rounded-xl bg-white border border-[#DADCE0] p-4 shadow-xs text-center space-y-3">
                                            <div class="flex flex-col items-center">
                                                <div 
                                                    :style="previewLogoBgStyle" 
                                                    class="h-16 min-w-[70px] max-w-[180px] p-2 rounded-2xl border shadow-sm flex items-center justify-center overflow-hidden mb-2"
                                                >
                                                    <img 
                                                        v-if="logoPreviewUrl || $page.props.tenant?.logo || tenant?.logo" 
                                                        :src="logoPreviewUrl || $page.props.tenant?.logo || tenant?.logo" 
                                                        class="max-h-full max-w-full w-auto h-auto object-contain filter drop-shadow-[0_1px_3px_rgba(0,0,0,0.25)]" 
                                                        alt="Login preview logo" 
                                                    />
                                                    <div v-else class="h-10 w-10 rounded-xl bg-[#E8F0FE] flex items-center justify-center text-xl text-[#1A73E8]">
                                                        <i class="fa-solid fa-graduation-cap"></i>
                                                    </div>
                                                </div>
                                                <div class="font-bold text-sm text-[#202124] truncate max-w-xs">
                                                    {{ $page.props.tenant?.name || tenant?.name || $page.props.platformName || 'School Portal' }}
                                                </div>
                                                <div class="text-3xs text-[#5F6368]">School Unified Portal Access</div>
                                            </div>

                                            <div 
                                                class="w-full py-2 rounded-full text-xs font-bold text-white shadow-xs transition-colors"
                                                :style="{ backgroundColor: brandingForm.cover_color || '#1A73E8' }"
                                            >
                                                Sign In to Portal
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Best practices callout -->
                                    <div class="rounded-xl bg-[#E8F0FE]/60 border border-[#D2E3FC] p-3 text-xs text-[#1A73E8] space-y-1">
                                        <p class="font-bold flex items-center gap-1.5 text-2xs">
                                            <i class="fa-solid fa-lightbulb text-[#B06000]"></i> 
                                            <span>Pro-Tip for School Crests:</span>
                                        </p>
                                        <p class="text-3xs text-[#202124]/80">
                                            White and transparent logos are automatically backed with Midnight Slate for pristine contrast. You can switch to School Brand Primary or Clean Light above.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Color Palette & Theme Selection (After Logo Upload) -->
                    <div class="google-card rounded-2xl border border-[#DADCE0] bg-white p-6 sm:p-8 shadow-2xs space-y-6">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-[#DADCE0] pb-5">
                            <div>
                                <h4 class="text-base font-extrabold text-[#202124] flex items-center gap-2">
                                    <i class="fa-solid fa-palette text-[#1A73E8]"></i>
                                    <span>Brand Color Palette Options</span>
                                </h4>
                                <p class="text-xs text-[#5F6368] mt-1">
                                    Colors are automatically sampled from your logo. If the auto-selected scheme is not ideal, select one of the curated academic palettes below.
                                </p>
                            </div>
                            <button 
                                type="button"
                                @click="extractColorsFromLogo"
                                :disabled="isExtractingColors"
                                class="rounded-full bg-[#E8F0FE] hover:bg-[#D2E3FC] text-[#1A73E8] border border-[#D2E3FC] px-4 py-2 text-xs font-bold transition-all inline-flex items-center gap-1.5 cursor-pointer shadow-2xs self-start sm:self-auto"
                            >
                                <i class="fa-solid fa-wand-magic-sparkles text-xs" :class="{ 'animate-spin': isExtractingColors }"></i>
                                <span>{{ isExtractingColors ? 'Extracting...' : 'Re-sample Logo Colors' }}</span>
                            </button>
                        </div>

                        <!-- 4/5 Interactive Palette Cards -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                            <!-- Card 1: Auto-Extracted from Logo -->
                            <button
                                type="button"
                                @click="selectPalette(autoExtractedPalette)"
                                :class="[
                                    selectedPaletteId === autoExtractedPalette.id 
                                        ? 'border-[#1A73E8] ring-2 ring-[#1A73E8]/30 bg-[#F4F8FE]' 
                                        : 'border-[#DADCE0] bg-white hover:border-[#1A73E8]/40',
                                    'p-4 rounded-2xl border text-left transition-all cursor-pointer flex flex-col justify-between relative shadow-2xs'
                                ]"
                            >
                                <div class="flex items-center justify-between w-full">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-[#E8F0FE] text-[#1A73E8] border border-[#D2E3FC]">
                                        <i class="fa-solid fa-wand-magic-sparkles text-[9px]"></i>
                                        <span>Auto Extracted</span>
                                    </span>
                                    <i v-if="selectedPaletteId === autoExtractedPalette.id" class="fa-solid fa-circle-check text-[#1A73E8] text-sm"></i>
                                </div>

                                <div class="my-3">
                                    <div class="font-bold text-xs text-[#202124]">{{ autoExtractedPalette.name }}</div>
                                    <p class="text-3xs text-[#5F6368] mt-0.5 leading-tight">{{ autoExtractedPalette.description }}</p>
                                </div>

                                <!-- Color swatches -->
                                <div class="flex items-center gap-2 pt-2 border-t border-black/5">
                                    <div class="flex items-center -space-x-1.5">
                                        <span class="h-6 w-6 rounded-full border-2 border-white shadow-2xs" :style="{ backgroundColor: autoExtractedPalette.primary }" title="Primary"></span>
                                        <span class="h-6 w-6 rounded-full border-2 border-white shadow-2xs" :style="{ backgroundColor: autoExtractedPalette.secondary }" title="Secondary"></span>
                                        <span class="h-6 w-6 rounded-full border-2 border-white shadow-2xs" :style="{ backgroundColor: autoExtractedPalette.accent }" title="Accent"></span>
                                    </div>
                                    <span class="text-3xs font-mono text-[#5F6368] font-bold">{{ autoExtractedPalette.primary }}</span>
                                </div>
                            </button>

                            <!-- Curated Palettes (2, 3, 4, 5) -->
                            <button
                                type="button"
                                v-for="pal in curatedPalettes"
                                :key="pal.id"
                                @click="selectPalette(pal)"
                                :class="[
                                    selectedPaletteId === pal.id 
                                        ? 'border-[#1A73E8] ring-2 ring-[#1A73E8]/30 bg-[#F4F8FE]' 
                                        : 'border-[#DADCE0] bg-white hover:border-[#1A73E8]/40',
                                    'p-4 rounded-2xl border text-left transition-all cursor-pointer flex flex-col justify-between relative shadow-2xs'
                                ]"
                            >
                                <div class="flex items-center justify-between w-full">
                                    <span class="text-3xs font-bold uppercase tracking-wider text-[#5F6368]">Curated Theme</span>
                                    <i v-if="selectedPaletteId === pal.id" class="fa-solid fa-circle-check text-[#1A73E8] text-sm"></i>
                                </div>

                                <div class="my-3">
                                    <div class="font-bold text-xs text-[#202124]">{{ pal.name }}</div>
                                    <p class="text-3xs text-[#5F6368] mt-0.5 leading-tight">{{ pal.description }}</p>
                                </div>

                                <!-- Color swatches -->
                                <div class="flex items-center gap-2 pt-2 border-t border-black/5">
                                    <div class="flex items-center -space-x-1.5">
                                        <span class="h-6 w-6 rounded-full border-2 border-white shadow-2xs" :style="{ backgroundColor: pal.primary }" title="Primary"></span>
                                        <span class="h-6 w-6 rounded-full border-2 border-white shadow-2xs" :style="{ backgroundColor: pal.secondary }" title="Secondary"></span>
                                        <span class="h-6 w-6 rounded-full border-2 border-white shadow-2xs" :style="{ backgroundColor: pal.accent }" title="Accent"></span>
                                    </div>
                                    <span class="text-3xs font-mono text-[#5F6368] font-bold">{{ pal.primary }}</span>
                                </div>
                            </button>
                        </div>

                        <!-- Fine-tuning custom inputs & save form -->
                        <div class="rounded-xl border border-[#DADCE0] bg-[#F8F9FA] p-5 space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold uppercase tracking-wider text-[#202124]">
                                    Active Color Swatches & Hex Codes
                                </span>
                                <span class="text-3xs text-[#5F6368]">Modify values directly for exact hex matching</span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <!-- Primary Cover Color -->
                                <div class="rounded-xl bg-white border border-[#DADCE0] p-3 flex items-center gap-3">
                                    <input 
                                        type="color" 
                                        v-model="brandingForm.cover_color" 
                                        class="h-9 w-9 rounded-lg border border-black/10 cursor-pointer p-0.5" 
                                    />
                                    <div class="flex-1 min-w-0">
                                        <label class="block text-3xs font-bold uppercase text-[#5F6368]">Primary Color</label>
                                        <input 
                                            type="text" 
                                            v-model="brandingForm.cover_color" 
                                            class="w-full text-xs font-mono font-bold text-[#202124] focus:outline-none uppercase" 
                                        />
                                    </div>
                                </div>

                                <!-- Secondary Color -->
                                <div class="rounded-xl bg-white border border-[#DADCE0] p-3 flex items-center gap-3">
                                    <input 
                                        type="color" 
                                        v-model="brandingForm.secondary_color" 
                                        class="h-9 w-9 rounded-lg border border-black/10 cursor-pointer p-0.5" 
                                    />
                                    <div class="flex-1 min-w-0">
                                        <label class="block text-3xs font-bold uppercase text-[#5F6368]">Secondary Accent</label>
                                        <input 
                                            type="text" 
                                            v-model="brandingForm.secondary_color" 
                                            class="w-full text-xs font-mono font-bold text-[#202124] focus:outline-none uppercase" 
                                        />
                                    </div>
                                </div>

                                <!-- Accent Color -->
                                <div class="rounded-xl bg-white border border-[#DADCE0] p-3 flex items-center gap-3">
                                    <input 
                                        type="color" 
                                        v-model="brandingForm.accent_color" 
                                        class="h-9 w-9 rounded-lg border border-black/10 cursor-pointer p-0.5" 
                                    />
                                    <div class="flex-1 min-w-0">
                                        <label class="block text-3xs font-bold uppercase text-[#5F6368]">Highlight Accent</label>
                                        <input 
                                            type="text" 
                                            v-model="brandingForm.accent_color" 
                                            class="w-full text-xs font-mono font-bold text-[#202124] focus:outline-none uppercase" 
                                        />
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end pt-2">
                                <button
                                    type="button"
                                    @click="submitBranding"
                                    :disabled="brandingForm.processing"
                                    class="rounded-full bg-[#1A73E8] hover:bg-[#1557B0] py-2.5 px-6 text-xs font-bold text-white shadow-xs transition-all flex items-center gap-2 cursor-pointer"
                                >
                                    <i class="fa-solid fa-check text-xs"></i>
                                    <span>{{ brandingForm.processing ? 'Saving...' : 'Apply & Save Color Palette' }}</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Official School Contact Information -->
                    <div class="google-card rounded-2xl border border-[#DADCE0] bg-white p-6 sm:p-8 shadow-sm space-y-6 mb-10">
                        <div class="flex items-center justify-between border-b border-[#DADCE0] pb-5">
                            <div>
                                <h4 class="text-lg font-extrabold text-[#202124] flex items-center gap-2">
                                    <i class="fa-solid fa-address-book text-[#1A73E8]"></i>
                                    <span>Official School Contact Information</span>
                                </h4>
                                <p class="text-xs text-[#5F6368] mt-1">Configure your official school helpline, public contact email, and campus address displayed across all portals, landing pages, and fee challans.</p>
                            </div>
                        </div>

                        <form @submit.prevent="submitBranding" class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-bold text-[#202124] flex items-center gap-1.5">
                                        <i class="fa-solid fa-phone text-[#1A73E8] text-2xs"></i>
                                        <span>Official Admissions / Contact Phone</span>
                                    </label>
                                    <input 
                                        type="text" 
                                        v-model="brandingForm.phone" 
                                        placeholder="+92 300 1234567 or (051) 1234567" 
                                        class="w-full rounded-xl border border-[#DADCE0] px-4 py-2.5 text-sm focus:border-[#1A73E8] focus:ring-1 focus:ring-[#1A73E8]" 
                                    />
                                    <p class="text-[11px] text-[#5F6368]">Displayed in the public landing page header, footer, contact section, and student fee vouchers.</p>
                                </div>

                                <div class="space-y-1.5">
                                    <label class="block text-xs font-bold text-[#202124] flex items-center gap-1.5">
                                        <i class="fa-solid fa-envelope text-[#1A73E8] text-2xs"></i>
                                        <span>Official Inquiries / Admissions Email</span>
                                    </label>
                                    <input 
                                        type="email" 
                                        v-model="brandingForm.email" 
                                        placeholder="info@yourschool.edu.pk" 
                                        class="w-full rounded-xl border border-[#DADCE0] px-4 py-2.5 text-sm focus:border-[#1A73E8] focus:ring-1 focus:ring-[#1A73E8]" 
                                    />
                                    <p class="text-[11px] text-[#5F6368]">Primary public inbox for parent inquiries, admissions, and system notifications.</p>
                                </div>

                                <div class="space-y-1.5 md:col-span-2">
                                    <label class="block text-xs font-bold text-[#202124] flex items-center gap-1.5">
                                        <i class="fa-solid fa-location-dot text-[#1A73E8] text-2xs"></i>
                                        <span>Campus Physical Address</span>
                                    </label>
                                    <textarea 
                                        v-model="brandingForm.address" 
                                        rows="2" 
                                        placeholder="Main Campus Street, Sector / Block, City, Province / State" 
                                        class="w-full rounded-xl border border-[#DADCE0] px-4 py-2.5 text-sm focus:border-[#1A73E8] focus:ring-1 focus:ring-[#1A73E8]"
                                    ></textarea>
                                    <p class="text-[11px] text-[#5F6368]">Full postal address of the campus displayed on the website and printed documents.</p>
                                </div>
                            </div>

                            <div class="flex justify-end pt-5 border-t border-[#DADCE0]">
                                <button 
                                    type="submit" 
                                    :disabled="brandingForm.processing" 
                                    class="rounded-full bg-[#1A73E8] py-2.5 px-7 text-xs font-bold text-white hover:bg-[#1557B0] transition-colors flex items-center gap-2 cursor-pointer"
                                >
                                    <i class="fa-solid fa-check text-xs"></i>
                                    <span>{{ brandingForm.processing ? 'Saving...' : 'Save Contact Information' }}</span>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Landing Page Content CMS -->
                    <div class="google-card rounded-2xl border border-[#DADCE0] bg-white p-6 sm:p-8 shadow-sm space-y-6 mb-10">
                        <div class="flex items-center justify-between border-b border-[#DADCE0] pb-5">
                            <div>
                                <h4 class="text-lg font-extrabold text-[#202124] flex items-center gap-2">
                                    <i class="fa-solid fa-pen-nib text-[#1A73E8]"></i>
                                    <span>Landing Page Content (CMS)</span>
                                </h4>
                                <p class="text-xs text-[#5F6368] mt-1">Manage the text displayed on the public landing page.</p>
                            </div>
                        </div>

                        <form @submit.prevent="submitBranding" class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-bold text-[#202124]">Hero Heading</label>
                                    <input type="text" v-model="brandingForm.hero_heading" placeholder="Empowering Minds..." class="w-full rounded-xl border border-[#DADCE0] px-4 py-2.5 text-sm focus:border-[#1A73E8] focus:ring-1 focus:ring-[#1A73E8]" />
                                </div>
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-bold text-[#202124]">School Tagline</label>
                                    <input type="text" v-model="brandingForm.tagline" placeholder="School Tagline" class="w-full rounded-xl border border-[#DADCE0] px-4 py-2.5 text-sm focus:border-[#1A73E8] focus:ring-1 focus:ring-[#1A73E8]" />
                                </div>
                                <div class="space-y-1.5 md:col-span-2">
                                    <label class="block text-xs font-bold text-[#202124]">Hero Description</label>
                                    <textarea v-model="brandingForm.hero_description" rows="2" class="w-full rounded-xl border border-[#DADCE0] px-4 py-2.5 text-sm focus:border-[#1A73E8] focus:ring-1 focus:ring-[#1A73E8]"></textarea>
                                </div>
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-bold text-[#202124]">About / Vision</label>
                                    <textarea v-model="brandingForm.about_vision" rows="4" class="w-full rounded-xl border border-[#DADCE0] px-4 py-2.5 text-sm focus:border-[#1A73E8] focus:ring-1 focus:ring-[#1A73E8]"></textarea>
                                </div>
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-bold text-[#202124]">Academics Description</label>
                                    <textarea v-model="brandingForm.academics_description" rows="4" class="w-full rounded-xl border border-[#DADCE0] px-4 py-2.5 text-sm focus:border-[#1A73E8] focus:ring-1 focus:ring-[#1A73E8]"></textarea>
                                </div>
                            </div>
                            <div class="flex justify-end pt-5 border-t border-[#DADCE0]">
                                <button type="submit" :disabled="brandingForm.processing" class="rounded-full bg-[#1A73E8] py-3 px-8 text-xs font-bold text-white hover:bg-[#1557B0]">
                                    <i class="fa-solid fa-check mr-2"></i> Save Landing Page Content
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Personal Account Settings -->
                    <div class="google-card rounded-2xl border border-[#DADCE0] bg-white p-6 sm:p-8 shadow-sm space-y-6 mb-10">
                        <div class="flex items-center justify-between border-b border-[#DADCE0] pb-5">
                            <div>
                                <h4 class="text-lg font-extrabold text-[#202124] flex items-center gap-2">
                                    <i class="fa-solid fa-user-gear text-[#1A73E8]"></i>
                                    <span>Personal Account & Password Settings</span>
                                </h4>
                                <p class="text-xs text-[#5F6368] mt-1">Update your own principal administrator email, contact number, or update password securely.</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <!-- Contact Details -->
                            <form @submit.prevent="submitAdminProfileChange" class="space-y-4">
                                <h5 class="text-xs font-bold uppercase tracking-wider text-[#5F6368]">Contact Details</h5>
                                <div>
                                    <label class="block text-2xs font-bold text-[#202124] mb-1">Email Address</label>
                                    <input type="email" v-model="adminProfileForm.email" required class="w-full rounded-xl border border-[#DADCE0] px-3.5 py-2 text-xs focus:ring-2 focus:ring-[#1A73E8]" />
                                    <span v-if="adminProfileForm.errors.email" class="text-2xs text-[#C5221F] font-bold block mt-1">{{ adminProfileForm.errors.email }}</span>
                                </div>
                                <div>
                                    <label class="block text-2xs font-bold text-[#202124] mb-1">Phone Number</label>
                                    <input type="text" v-model="adminProfileForm.phone" class="w-full rounded-xl border border-[#DADCE0] px-3.5 py-2 text-xs focus:ring-2 focus:ring-[#1A73E8]" />
                                    <span v-if="adminProfileForm.errors.phone" class="text-2xs text-[#C5221F] font-bold block mt-1">{{ adminProfileForm.errors.phone }}</span>
                                </div>
                                <div class="pt-2">
                                    <button type="submit" :disabled="adminProfileForm.processing" class="rounded-full bg-[#1A73E8] px-5 py-2 text-xs font-bold text-white shadow-2xs hover:bg-[#1557B0] transition-all cursor-pointer">
                                        {{ adminProfileForm.processing ? 'Saving...' : 'Update Details' }}
                                    </button>
                                </div>
                            </form>

                            <!-- Password Update -->
                            <form @submit.prevent="submitAdminPasswordChange" class="space-y-4 border-t md:border-t-0 md:border-l border-[#DADCE0] pt-6 md:pt-0 md:pl-8">
                                <h5 class="text-xs font-bold uppercase tracking-wider text-[#5F6368]">Change Password</h5>
                                <div>
                                    <label class="block text-2xs font-bold text-[#202124] mb-1">Current Password</label>
                                    <input type="password" v-model="adminPasswordForm.current_password" required class="w-full rounded-xl border border-[#DADCE0] px-3.5 py-2 text-xs focus:ring-2 focus:ring-[#1A73E8]" />
                                    <span v-if="adminPasswordForm.errors.current_password" class="text-2xs text-[#C5221F] font-bold block mt-1">{{ adminPasswordForm.errors.current_password }}</span>
                                </div>
                                <div>
                                    <label class="block text-2xs font-bold text-[#202124] mb-1">New Password</label>
                                    <input type="password" v-model="adminPasswordForm.new_password" required class="w-full rounded-xl border border-[#DADCE0] px-3.5 py-2 text-xs focus:ring-2 focus:ring-[#1A73E8]" />
                                    <span v-if="adminPasswordForm.errors.new_password" class="text-2xs text-[#C5221F] font-bold block mt-1">{{ adminPasswordForm.errors.new_password }}</span>
                                </div>
                                <div>
                                    <label class="block text-2xs font-bold text-[#202124] mb-1">Confirm New Password</label>
                                    <input type="password" v-model="adminPasswordForm.new_password_confirmation" required class="w-full rounded-xl border border-[#DADCE0] px-3.5 py-2 text-xs focus:ring-2 focus:ring-[#1A73E8]" />
                                </div>
                                <div class="pt-2">
                                    <button type="submit" :disabled="adminPasswordForm.processing" class="rounded-full bg-[#1A73E8] px-5 py-2 text-xs font-bold text-white shadow-2xs hover:bg-[#1557B0] transition-all cursor-pointer">
                                        {{ adminPasswordForm.processing ? 'Changing Password...' : 'Change Password' }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- ================= TAB: FINANCE & ACCOUNTS ================= -->
                <div v-if="activeTab === 'finance'" class="space-y-8 animate-fadeIn">
                    <!-- Executive Finance KPI Cards -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div class="google-card rounded-2xl border border-[#DADCE0] bg-white p-6 shadow-2xs flex items-center justify-between">
                            <div>
                                <p class="text-xs font-bold text-[#5F6368] uppercase tracking-wider">YTD Revenue Collection</p>
                                <p class="text-2xl font-black text-[#137333] mt-1">PKR {{ (finance_stats?.total_collected_ytd || 0).toLocaleString() }}</p>
                                <span class="text-[11px] font-medium text-[#5F6368] mt-1 inline-block">Total processed across all terms</span>
                            </div>
                            <div class="h-12 w-12 rounded-2xl bg-[#E6F4EA] text-[#137333] flex items-center justify-center text-xl flex-shrink-0 shadow-xs">
                                <i class="fa-solid fa-money-bill-trend-up"></i>
                            </div>
                        </div>

                        <div class="google-card rounded-2xl border border-[#DADCE0] bg-white p-6 shadow-2xs flex items-center justify-between">
                            <div>
                                <p class="text-xs font-bold text-[#5F6368] uppercase tracking-wider">Current Month Collection</p>
                                <p class="text-2xl font-black text-[#1A73E8] mt-1">PKR {{ (finance_stats?.monthly_collection || 0).toLocaleString() }}</p>
                                <span class="text-[11px] font-semibold text-[#1A73E8] mt-1 inline-block">Target: PKR {{ (finance_stats?.monthly_target || 0).toLocaleString() }}</span>
                            </div>
                            <div class="h-12 w-12 rounded-2xl bg-[#E8F0FE] text-[#1A73E8] flex items-center justify-center text-xl flex-shrink-0 shadow-xs">
                                <i class="fa-solid fa-calendar-check"></i>
                            </div>
                        </div>

                        <div class="google-card rounded-2xl border border-[#DADCE0] bg-white p-6 shadow-2xs flex flex-col justify-between">
                            <div class="flex items-center justify-between">
                                <p class="text-xs font-bold text-[#5F6368] uppercase tracking-wider">Collection Efficiency Rate</p>
                                <span class="text-sm font-black text-[#8430CE]">{{ finance_stats?.collection_percentage || 100 }}%</span>
                            </div>
                            <div class="w-full bg-[#E8EAED] rounded-full h-2.5 mt-3 overflow-hidden">
                                <div class="bg-[#8430CE] h-2.5 rounded-full transition-all duration-500" :style="{ width: `${Math.min(finance_stats?.collection_percentage || 100, 100)}%` }"></div>
                            </div>
                            <span class="text-[11px] text-[#5F6368] mt-2">Challan realization percentage</span>
                        </div>

                        <div class="google-card rounded-2xl border border-[#DADCE0] bg-white p-6 shadow-2xs flex items-center justify-between border-l-4 border-l-[#C5221F]">
                            <div>
                                <p class="text-xs font-bold text-[#C5221F] uppercase tracking-wider">Outstanding Overdue Dues</p>
                                <p class="text-2xl font-black text-[#C5221F] mt-1">PKR {{ (finance_stats?.total_outstanding || 0).toLocaleString() }}</p>
                                <button @click="activeTab = 'defaulters'" class="text-[11px] font-bold text-[#1A73E8] hover:underline mt-1 inline-flex items-center gap-1">
                                    <span>Review {{ defaulters?.length || 0 }} defaulters</span>
                                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                                </button>
                            </div>
                            <div class="h-12 w-12 rounded-2xl bg-[#FCE8E6] text-[#C5221F] flex items-center justify-center text-xl flex-shrink-0 shadow-xs">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Financial Actions -->
                    <div class="google-card rounded-2xl border border-[#DADCE0] bg-white p-6 shadow-2xs flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div>
                            <h3 class="text-base font-extrabold text-[#202124]">Fee & Billing Management</h3>
                            <p class="text-xs text-[#5F6368]">Configure recurring class fee structures, issue monthly digital challans, or trigger overdue SMS/WhatsApp follow-ups.</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            <button @click="activeTab = 'fees'" class="rounded-full border border-[#DADCE0] bg-[#F8F9FA] px-4 py-2 text-xs font-bold text-[#202124] hover:bg-[#E8EAED] transition-all flex items-center gap-2 shadow-2xs">
                                <i class="fa-solid fa-file-invoice text-[#1A73E8]"></i>
                                <span>Fee Structures</span>
                            </button>
                            <button @click="activeTab = 'defaulters'" class="rounded-full border border-[#DADCE0] bg-[#F8F9FA] px-4 py-2 text-xs font-bold text-[#202124] hover:bg-[#E8EAED] transition-all flex items-center gap-2 shadow-2xs">
                                <i class="fa-solid fa-bell text-[#C5221F]"></i>
                                <span>Defaulter Follow-ups</span>
                            </button>
                        </div>
                    </div>

                    <!-- Recent Financial Ledger Transactions Table -->
                    <div class="google-card rounded-2xl border border-[#DADCE0] bg-white p-6 sm:p-8 shadow-2xs">
                        <div class="flex items-center justify-between border-b border-[#DADCE0] pb-4 mb-6">
                            <div>
                                <h3 class="text-lg font-extrabold text-[#202124] flex items-center gap-2">
                                    <i class="fa-solid fa-receipt text-[#10B981]"></i>
                                    <span>Recent Paid Transactions Ledger</span>
                                </h3>
                                <p class="text-xs text-[#5F6368]">Live audit trail of fee realizations and digital bank/cash receipts.</p>
                            </div>
                        </div>

                        <div v-if="recent_transactions?.length" class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="border-b border-[#DADCE0] text-[#5F6368] text-xs uppercase font-bold tracking-wider bg-[#F8F9FA]">
                                        <th class="py-3 px-4">Receipt ID</th>
                                        <th class="py-3 px-4">Student</th>
                                        <th class="py-3 px-4">Class</th>
                                        <th class="py-3 px-4">Challan Month</th>
                                        <th class="py-3 px-4">Paid Amount</th>
                                        <th class="py-3 px-4">Realized Date</th>
                                        <th class="py-3 px-4">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#DADCE0] text-sm">
                                    <tr v-for="tx in recent_transactions" :key="tx.id" class="hover:bg-[#F8F9FA] transition-all">
                                        <td class="py-3 px-4 font-mono text-xs font-bold text-[#5F6368]">#RCP-{{ tx.id }}</td>
                                        <td class="py-3 px-4">
                                            <div class="font-bold text-[#202124]">{{ tx.student?.user?.name || 'Student' }}</div>
                                            <div class="text-xs text-[#5F6368]">Roll: {{ tx.student?.roll_number || 'N/A' }}</div>
                                        </td>
                                        <td class="py-3 px-4 font-medium text-[#202124]">{{ tx.student?.current_class?.name || 'Class' }}</td>
                                        <td class="py-3 px-4 font-mono text-xs">{{ tx.month_year }}</td>
                                        <td class="py-3 px-4 font-black text-[#137333]">PKR {{ parseFloat(tx.paid_amount || 0).toLocaleString() }}</td>
                                        <td class="py-3 px-4 text-xs text-[#5F6368]">{{ tx.paid_date ? new Date(tx.paid_date).toLocaleDateString() : 'N/A' }}</td>
                                        <td class="py-3 px-4">
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-[#E6F4EA] text-[#137333]">
                                                <i class="fa-solid fa-circle-check"></i>
                                                <span>Paid Realized</span>
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-else class="text-center py-12 text-[#5F6368]">
                            <i class="fa-solid fa-file-invoice text-3xl text-[#DADCE0] mb-2"></i>
                            <p class="text-sm font-medium">No realized transactions recorded yet for this billing cycle.</p>
                        </div>
                    </div>

                    <!-- Pending Manual Bank Receipt Verification Table -->
                    <div v-if="pending_vouchers?.length" class="google-card rounded-2xl border-2 border-[#F59E0B] bg-white p-6 sm:p-8 shadow-md">
                        <div class="flex items-center justify-between border-b border-[#DADCE0] pb-4 mb-6">
                            <div>
                                <h3 class="text-lg font-extrabold text-[#202124] flex items-center gap-2">
                                    <i class="fa-solid fa-clock text-[#F59E0B]"></i>
                                    <span>Pending Manual Bank Receipt Verifications ({{ pending_vouchers.length }})</span>
                                </h3>
                                <p class="text-xs text-[#5F6368]">Student-uploaded bank deposit slips or online payment screenshots waiting for staff reconciliation.</p>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="border-b border-[#DADCE0] text-[#5F6368] text-xs uppercase font-bold tracking-wider bg-[#FEF7E0]">
                                        <th class="py-3 px-4">Proof Photo</th>
                                        <th class="py-3 px-4">Challan #</th>
                                        <th class="py-3 px-4">Student</th>
                                        <th class="py-3 px-4">Class</th>
                                        <th class="py-3 px-4">Amount</th>
                                        <th class="py-3 px-4">Remarks</th>
                                        <th class="py-3 px-4 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#DADCE0] text-sm">
                                    <tr v-for="pv in pending_vouchers" :key="pv.id" class="hover:bg-[#FEF7E0]/40 transition-all">
                                        <td class="py-3 px-4">
                                            <a v-if="pv.proof_image_path" :href="pv.proof_image_path" target="_blank" class="block w-12 h-12 rounded-lg border overflow-hidden shadow-xs hover:opacity-80">
                                                <img :src="pv.proof_image_path" class="w-full h-full object-cover" alt="Proof" />
                                            </a>
                                            <span v-else class="text-xs text-[#5F6368]">No Image</span>
                                        </td>
                                        <td class="py-3 px-4 font-mono text-xs font-bold text-[#1A73E8]">{{ pv.challan_number }}</td>
                                        <td class="py-3 px-4 font-bold text-[#202124]">{{ pv.student?.user?.name }}</td>
                                        <td class="py-3 px-4 text-xs">{{ pv.student?.current_class?.name }}</td>
                                        <td class="py-3 px-4 font-extrabold text-[#C5221F]">PKR {{ pv.total_amount.toLocaleString() }}</td>
                                        <td class="py-3 px-4 text-xs text-[#5F6368] max-w-xs truncate">{{ pv.proof_remarks || 'Pending check' }}</td>
                                        <td class="py-3 px-4 text-right space-x-2 whitespace-nowrap">
                                            <button @click="approveVoucher(pv.id)" class="rounded-xl bg-[#137333] hover:bg-[#0D652D] text-white px-3.5 py-1.5 text-xs font-bold shadow-2xs transition-all inline-flex items-center gap-1">
                                                <i class="fa-solid fa-check"></i> Approve & Mark Paid
                                            </button>
                                            <button @click="rejectVoucher(pv.id)" class="rounded-xl bg-[#FCE8E6] hover:bg-[#FAD2CF] text-[#C5221F] px-3 py-1.5 text-xs font-bold transition-all inline-flex items-center gap-1">
                                                <i class="fa-solid fa-xmark"></i> Reject
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- All Generated Invoices & 3-Copy Print Directory -->
                    <div class="google-card rounded-2xl border border-[#DADCE0] bg-white p-6 sm:p-8 shadow-2xs">
                        <div class="flex items-center justify-between border-b border-[#DADCE0] pb-4 mb-6">
                            <div>
                                <h3 class="text-lg font-extrabold text-[#202124] flex items-center gap-2">
                                    <i class="fa-solid fa-print text-[#1A73E8]"></i>
                                    <span>All Generated Challans & 3-Copy Print Directory</span>
                                </h3>
                                <p class="text-xs text-[#5F6368]">Directly print 3-copy printable bank/student/school invoices or verify consumer numbers.</p>
                            </div>
                        </div>

                        <div v-if="all_challans?.length" class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="border-b border-[#DADCE0] text-[#5F6368] text-xs uppercase font-bold tracking-wider bg-[#F8F9FA]">
                                        <th class="py-3 px-4">Challan #</th>
                                        <th class="py-3 px-4">1Bill Consumer #</th>
                                        <th class="py-3 px-4">Student</th>
                                        <th class="py-3 px-4">Class</th>
                                        <th class="py-3 px-4">Month</th>
                                        <th class="py-3 px-4">Amount</th>
                                        <th class="py-3 px-4">Status</th>
                                        <th class="py-3 px-4 text-right">Print Option</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#DADCE0] text-sm">
                                    <tr v-for="ch in all_challans" :key="ch.id" class="hover:bg-[#F8F9FA] transition-all">
                                        <td class="py-3 px-4 font-mono text-xs font-bold text-[#1A73E8]">{{ ch.challan_number }}</td>
                                        <td class="py-3 px-4 font-mono text-xs font-bold text-emerald-700">{{ ch.voucher_consumer_number || ch.challan_number }}</td>
                                        <td class="py-3 px-4 font-bold text-[#202124]">{{ ch.student?.user?.name }}</td>
                                        <td class="py-3 px-4 text-xs">{{ ch.student?.current_class?.name }}</td>
                                        <td class="py-3 px-4 font-mono text-xs uppercase">{{ ch.month_year }}</td>
                                        <td class="py-3 px-4 font-extrabold text-[#202124]">PKR {{ ch.total_amount.toLocaleString() }}</td>
                                        <td class="py-3 px-4">
                                            <span :class="{
                                                'bg-[#E6F4EA] text-[#137333] border-[#CEEAD6]': ch.status === 'paid',
                                                'bg-[#FEF7E0] text-[#B06000] border-[#FEEFC3]': ch.status === 'partial' || ch.verification_status === 'verification_pending',
                                                'bg-[#FCE8E6] text-[#C5221F] border-[#FAD2CF]': ch.status === 'unpaid' || ch.status === 'overdue'
                                            }" class="px-2.5 py-0.5 rounded-full text-2xs font-bold uppercase border">
                                                {{ ch.verification_status === 'verification_pending' ? 'Pending Check' : ch.status }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-right whitespace-nowrap">
                                            <a :href="'/challans/' + ch.id + '/print'" target="_blank" class="rounded-xl bg-[#1A73E8] hover:bg-[#1557B0] text-white px-3.5 py-1.5 text-xs font-bold shadow-2xs inline-flex items-center gap-1.5">
                                                <i class="fa-solid fa-print"></i> <span>3-Copy Voucher</span>
                                            </a>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- ================= TAB: SPORTS & EXTRACURRICULAR ACTIVITIES ================= -->
                <div v-if="activeTab === 'sports'" class="space-y-8 animate-fadeIn">
                    <div class="google-card rounded-2xl border border-[#DADCE0] bg-white p-6 sm:p-8 shadow-2xs">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-[#DADCE0] pb-6 mb-6">
                            <div>
                                <h3 class="text-lg font-extrabold text-[#202124] flex items-center gap-2">
                                    <i class="fa-solid fa-trophy text-[#F59E0B]"></i>
                                    <span>Sports & Extracurricular Activities Directory</span>
                                </h3>
                                <p class="text-xs text-[#5F6368]">Create school sports teams, debate societies, and club rosters with assigned faculty coaches.</p>
                            </div>
                            <button @click="openCreateSportsModal()" class="rounded-full bg-[#1A73E8] px-5 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-[#1557B0] transition-all flex items-center gap-2 self-start sm:self-auto">
                                <i class="fa-solid fa-plus"></i>
                                <span>Create New Activity / Team</span>
                            </button>
                        </div>

                        <div v-if="sports_activities?.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div v-for="activity in sports_activities" :key="activity.id" class="rounded-2xl border border-[#DADCE0] bg-[#F8F9FA] p-5 shadow-xs hover:shadow-md transition-all flex flex-col justify-between relative overflow-hidden group">
                                <div class="absolute top-0 left-0 right-0 h-1.5" :class="activity.category === 'Sports' ? 'bg-[#F59E0B]' : activity.category === 'Debate' ? 'bg-[#8430CE]' : 'bg-[#1A73E8]'"></div>
                                <div>
                                    <div class="flex items-start justify-between gap-2 mt-2">
                                        <div>
                                            <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider mb-2" :class="activity.category === 'Sports' ? 'bg-[#FEF7E0] text-[#B06000]' : activity.category === 'Debate' ? 'bg-[#F3E8FD] text-[#8430CE]' : 'bg-[#E8F0FE] text-[#1A73E8]'">
                                                {{ activity.category || 'Sports' }}
                                            </span>
                                            <h4 class="text-base font-extrabold text-[#202124]">{{ activity.name }}</h4>
                                        </div>
                                        <div class="flex items-center gap-1 opacity-80 group-hover:opacity-100 transition-all">
                                            <button @click="openEditSportsModal(activity)" class="p-1.5 rounded-lg hover:bg-white text-[#5F6368] hover:text-[#1A73E8] transition-all" title="Edit Activity">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button @click="deleteSports(activity.id)" class="p-1.5 rounded-lg hover:bg-white text-[#5F6368] hover:text-[#C5221F] transition-all" title="Delete Activity">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="mt-4 space-y-2 text-xs text-[#5F6368]">
                                        <div class="flex items-center gap-2">
                                            <i class="fa-solid fa-clock w-4 text-[#1A73E8]"></i>
                                            <span>{{ activity.schedule || 'Schedule TBA' }}</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <i class="fa-solid fa-location-dot w-4 text-[#E11D48]"></i>
                                            <span>{{ activity.venue || 'School Campus / Field' }}</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <i class="fa-solid fa-user-tie w-4 text-[#10B981]"></i>
                                            <span class="font-semibold text-[#202124]">Coach: {{ activity.coach?.name || 'Faculty Unassigned' }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-5 pt-4 border-t border-[#DADCE0] flex items-center justify-between">
                                    <div class="text-xs">
                                        <span class="font-bold text-[#202124]">{{ activity.participants?.length || 0 }}</span>
                                        <span class="text-[#5F6368]"> / {{ activity.max_participants || 30 }} Participants</span>
                                    </div>
                                    <span class="text-[11px] font-bold text-[#10B981] bg-[#E6F4EA] px-2.5 py-1 rounded-full">
                                        Active Roster
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-12 text-[#5F6368]">
                            <i class="fa-solid fa-basketball text-3xl text-[#DADCE0] mb-2"></i>
                            <p class="text-sm font-medium">No sports or extracurricular activities created yet. Click "Create New Activity / Team" to get started.</p>
                        </div>
                    </div>
                </div>

                <!-- ================= TAB: DISCIPLINARY & PENALTIES SYSTEM ================= -->
                <div v-if="activeTab === 'penalties'" class="space-y-8 animate-fadeIn">
                    <div class="google-card rounded-2xl border border-[#DADCE0] bg-white p-6 sm:p-8 shadow-2xs">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-[#DADCE0] pb-6 mb-6">
                            <div>
                                <h3 class="text-lg font-extrabold text-[#202124] flex items-center gap-2">
                                    <i class="fa-solid fa-gavel text-[#E11D48]"></i>
                                    <span>Student Disciplinary & Penalty Ledger</span>
                                </h3>
                                <p class="text-xs text-[#5F6368]">Issue formal disciplinary notices, library fines, or behavioral conduct penalties with transparent audit tracking.</p>
                            </div>
                            <button @click="openPenaltyModal()" class="rounded-full bg-[#E11D48] px-5 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-[#BE123C] transition-all flex items-center gap-2 self-start sm:self-auto">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                                <span>Issue Penalty / Fine</span>
                            </button>
                        </div>

                        <div v-if="penalties?.length" class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="border-b border-[#DADCE0] text-[#5F6368] text-xs uppercase font-bold tracking-wider bg-[#F8F9FA]">
                                        <th class="py-3 px-4">ID</th>
                                        <th class="py-3 px-4">Student Info</th>
                                        <th class="py-3 px-4">Violation Type</th>
                                        <th class="py-3 px-4">Amount</th>
                                        <th class="py-3 px-4">Reason / Notes</th>
                                        <th class="py-3 px-4">Issued By</th>
                                        <th class="py-3 px-4">Status</th>
                                        <th class="py-3 px-4 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#DADCE0] text-sm">
                                    <tr v-for="penalty in penalties" :key="penalty.id" class="hover:bg-[#F8F9FA] transition-all">
                                        <td class="py-3 px-4 font-mono text-xs font-bold text-[#5F6368]">#PN-{{ penalty.id }}</td>
                                        <td class="py-3 px-4">
                                            <div class="font-bold text-[#202124]">{{ penalty.student?.user?.name || 'Student' }}</div>
                                            <div class="text-xs text-[#5F6368]">Roll: {{ penalty.student?.roll_number || 'N/A' }}</div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <span class="inline-block px-2.5 py-1 rounded-lg text-xs font-bold bg-[#FCE8E6] text-[#C5221F]">
                                                {{ penalty.penalty_type }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 font-black text-[#202124]">PKR {{ parseFloat(penalty.amount || 0).toLocaleString() }}</td>
                                        <td class="py-3 px-4 text-xs text-[#5F6368] max-w-xs truncate" :title="penalty.reason">{{ penalty.reason }}</td>
                                        <td class="py-3 px-4 text-xs font-medium text-[#202124]">{{ penalty.issued_by?.name || 'Staff' }}</td>
                                        <td class="py-3 px-4">
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold"
                                                :class="penalty.status === 'Resolved' || penalty.status === 'Paid' ? 'bg-[#E6F4EA] text-[#137333]' : penalty.status === 'Waived' ? 'bg-[#E8F0FE] text-[#1A73E8]' : 'bg-[#FCE8E6] text-[#C5221F]'">
                                                <i class="fa-solid" :class="penalty.status === 'Resolved' || penalty.status === 'Paid' ? 'fa-circle-check' : penalty.status === 'Waived' ? 'fa-flag' : 'fa-circle-exclamation'"></i>
                                                <span>{{ penalty.status }}</span>
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-right space-x-1">
                                            <select @change="updatePenaltyStatus(penalty.id, $event.target.value)" :value="penalty.status" class="text-xs font-bold rounded-lg border border-[#DADCE0] px-2 py-1 bg-white hover:border-[#1A73E8]">
                                                <option value="Issued">Issued</option>
                                                <option value="Resolved">Resolved</option>
                                                <option value="Waived">Waived</option>
                                                <option value="Paid">Paid</option>
                                            </select>
                                            <button @click="deletePenalty(penalty.id)" class="p-1.5 rounded-lg text-[#5F6368] hover:text-[#C5221F] hover:bg-[#FCE8E6] transition-all" title="Delete Penalty">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-else class="text-center py-12 text-[#5F6368]">
                            <i class="fa-solid fa-shield-halved text-3xl text-[#DADCE0] mb-2"></i>
                            <p class="text-sm font-medium">No disciplinary penalties or fines currently issued. Clean conduct record across the student body.</p>
                        </div>
                    </div>
                </div>
        
        <!-- ================= MODAL: ADD USER BY ROLE ================php = -->
        <div v-if="showAddUserModal" class="fixed inset-0 z-50 flex justify-end bg-black/40 backdrop-blur-xs overflow-hidden">
            <div class="absolute inset-0" @click="showAddUserModal = false"></div>
            <div class="bg-white w-full max-w-lg shadow-2xl flex flex-col h-full z-10 animate-slideLeft border-l border-[#DADCE0]">
                <div class="p-6 sm:p-8 flex-1 overflow-y-auto space-y-6">
                    <div class="flex items-center justify-between border-b border-[#DADCE0] pb-4">
                        <div>
                            <h3 class="text-lg font-extrabold text-[#202124]">Add User / Staff Member</h3>
                            <p class="text-xs text-[#5F6368]">Enter name, email, and set their password manually.</p>
                        </div>
                        <button @click="showAddUserModal = false" class="h-8 w-8 rounded-full hover:bg-[#F8F9FA] font-bold text-[#5F6368] flex items-center justify-center"><i class="fa-solid fa-xmark"></i></button>
                    </div>

                    <form @submit.prevent="submitAddUser" class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-[#202124] mb-1">Full Name *</label>
                            <input v-model="userForm.name" type="text" required placeholder="Full Name" class="w-full rounded-xl border border-[#DADCE0] px-4 py-2.5 text-xs focus:ring-2 focus:ring-[#1A73E8]" />
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-[#202124] mb-1">Email Address *</label>
                                <input v-model="userForm.email" type="email" required placeholder="Email Address" class="w-full rounded-xl border border-[#DADCE0] px-4 py-2.5 text-xs focus:ring-2 focus:ring-[#1A73E8]" />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-[#202124] mb-1">Phone Number</label>
                                <input v-model="userForm.phone" type="text" placeholder="Phone Number" class="w-full rounded-xl border border-[#DADCE0] px-4 py-2.5 text-xs focus:ring-2 focus:ring-[#1A73E8]" />
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-[#202124] mb-1">Role Selector *</label>
                            <select v-model="userForm.user_type" required class="w-full rounded-xl border border-[#DADCE0] px-4 py-2.5 text-xs font-bold text-[#1A73E8] focus:ring-2 focus:ring-[#1A73E8]">
                                <option value="teacher">Teacher</option>
                                <option value="admin">Principal / Vice Principal (Administrative)</option>
                                <option value="accountant">Accountant / Bursar</option>
                                <option value="registrar">Registrar / Front Desk</option>
                                <option value="librarian">Librarian</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-[#202124] mb-1">Password *</label>
                            <div class="flex gap-2">
                                <input v-model="userForm.password" type="text" required placeholder="Enter secure password" class="w-full rounded-xl border border-[#DADCE0] px-4 py-2.5 text-xs focus:ring-2 focus:ring-[#1A73E8]" />
                                <button type="button" @click="generateUserPassword" class="bg-[#F1F3F4] hover:bg-[#E8EAED] text-[#3C4043] border border-[#DADCE0] px-4 py-2.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap cursor-pointer">
                                    <i class="fa-solid fa-wand-magic-sparkles mr-1"></i> Generate
                                </button>
                            </div>
                            <span v-if="userForm.errors.password" class="text-2xs text-[#C5221F] font-bold block mt-1">{{ userForm.errors.password }}</span>
                        </div>

                        <!-- Dynamic Role Fields for Teacher -->
                        <div v-if="userForm.user_type === 'teacher'" class="rounded-2xl bg-[#E8F0FE]/50 border border-[#D2E3FC] p-4 space-y-3">
                            <div class="text-xs font-bold text-[#1A73E8]">Teacher Specific Assignment</div>
                            <div>
                                <label class="block text-2xs font-bold text-[#202124] mb-1">Academic Qualification & Subject Specialties</label>
                                <input v-model="userForm.qualification" type="text" placeholder="Academic Qualification & Specialties" class="w-full rounded-xl border border-[#DADCE0] bg-white px-3.5 py-2 text-xs focus:ring-2 focus:ring-[#1A73E8]" />
                            </div>
                            <div>
                                <label class="block text-2xs font-bold text-[#202124] mb-1">Homeroom Section Assignment (Optional)</label>
                                <select v-model="userForm.homeroom_section_id" class="w-full rounded-xl border border-[#DADCE0] bg-white px-3.5 py-2 text-xs">
                                    <option value="">-- No Homeroom Section Assigned --</option>
                                    <option v-for="sec in sections" :key="sec.id" :value="sec.id">{{ sec.tenantClass?.name || sec.class?.name || 'Class' }} - {{ sec.name }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="rounded-xl bg-[#F8F9FA] p-3.5 text-2xs text-[#5F6368] space-y-1">
                            <p class="font-bold text-[#202124] flex items-center gap-1.5"><i class="fa-solid fa-envelope text-[#1A73E8]"></i> <span>Welcome Email Notification:</span></p>
                            <p>A welcome email with these portal credentials and login link will be sent to the user immediately.</p>
                        </div>

                        <div class="flex justify-end space-x-3 pt-4 border-t border-[#DADCE0]">
                            <button type="button" @click="showAddUserModal = false" class="rounded-full px-5 py-2.5 text-xs font-bold text-[#5F6368] hover:bg-[#F8F9FA]">Cancel</button>
                            <button type="submit" :disabled="userForm.processing" class="rounded-full bg-[#1A73E8] px-6 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-[#1557B0] disabled:opacity-50 inline-flex items-center gap-1.5 cursor-pointer">
                                <i class="fa-solid fa-check"></i>
                                <span>{{ userForm.processing ? 'Provisioning...' : 'Create Staff Member' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ================= MODAL: EDIT USER ================= -->
        <div v-if="showEditUserModal" class="fixed inset-0 z-50 flex justify-end bg-black/40 backdrop-blur-xs overflow-hidden">
            <div class="absolute inset-0" @click="showEditUserModal = false"></div>
            <div class="bg-white w-full max-w-lg shadow-2xl flex flex-col h-full z-10 animate-slideLeft border-l border-[#DADCE0]">
                <div class="p-6 sm:p-8 flex-1 overflow-y-auto space-y-6">
                    <div class="flex items-center justify-between border-b border-[#DADCE0] pb-4">
                        <div>
                            <h3 class="text-lg font-extrabold text-[#202124]">Edit Staff Profile</h3>
                            <p class="text-xs text-[#5F6368]">Update demographic information and role attributes.</p>
                        </div>
                        <button @click="showEditUserModal = false" class="h-8 w-8 rounded-full hover:bg-[#F8F9FA] font-bold text-[#5F6368] flex items-center justify-center"><i class="fa-solid fa-xmark"></i></button>
                    </div>

                    <form @submit.prevent="submitEditUser" class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-[#202124] mb-1">Full Name *</label>
                            <input v-model="editUserForm.name" type="text" required class="w-full rounded-xl border border-[#DADCE0] px-4 py-2.5 text-xs focus:ring-2 focus:ring-[#1A73E8]" />
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-[#202124] mb-1">Email Address *</label>
                                <input v-model="editUserForm.email" type="email" required class="w-full rounded-xl border border-[#DADCE0] px-4 py-2.5 text-xs focus:ring-2 focus:ring-[#1A73E8]" />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-[#202124] mb-1">Phone Number</label>
                                <input v-model="editUserForm.phone" type="text" class="w-full rounded-xl border border-[#DADCE0] px-4 py-2.5 text-xs focus:ring-2 focus:ring-[#1A73E8]" />
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-[#202124] mb-1">Assigned Role *</label>
                            <select v-model="editUserForm.user_type" required class="w-full rounded-xl border border-[#DADCE0] px-4 py-2.5 text-xs font-bold text-[#1A73E8] focus:ring-2 focus:ring-[#1A73E8]">
                                <option value="teacher">Teacher</option>
                                <option value="admin">Principal / Vice Principal (Administrative)</option>
                                <option value="accountant">Accountant / Bursar</option>
                                <option value="registrar">Registrar / Front Desk</option>
                                <option value="librarian">Librarian</option>
                            </select>
                        </div>
                        <div v-if="editUserForm.user_type === 'teacher'">
                            <label class="block text-xs font-bold text-[#202124] mb-1">Qualification / Specialties</label>
                            <input v-model="editUserForm.qualification" type="text" class="w-full rounded-xl border border-[#DADCE0] px-4 py-2.5 text-xs focus:ring-2 focus:ring-[#1A73E8]" />
                        </div>
                        <div class="flex justify-end space-x-3 pt-4 border-t border-[#DADCE0]">
                            <button type="button" @click="showEditUserModal = false" class="rounded-full px-5 py-2.5 text-xs font-bold text-[#5F6368] hover:bg-[#F8F9FA]">Cancel</button>
                            <button type="submit" :disabled="editUserForm.processing" class="rounded-full bg-[#1A73E8] px-6 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-[#1557B0] disabled:opacity-50 inline-flex items-center gap-1.5"><i class="fa-solid fa-check"></i> <span>Save Changes</span></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ================= MODAL: BULK IMPORT USERS ================= -->
                <div v-if="showBulkUserModal" class="fixed inset-0 z-50 flex justify-end bg-black/40 backdrop-blur-xs overflow-hidden">
            <div class="absolute inset-0" @click="showBulkUserModal = false"></div>
            <div class="bg-white w-full max-w-md shadow-2xl flex flex-col h-full z-10 animate-slideLeft border-l border-[#DADCE0]">
                <div class="p-6 sm:p-8 flex-1 overflow-y-auto space-y-6">
                    <div class="flex items-center justify-between border-b border-[#DADCE0] pb-4">
                    <div>
                        <h3 class="text-lg font-extrabold text-[#202124]">Bulk Staff CSV Import</h3>
                        <p class="text-xs text-[#5F6368]">Ingest up to 500 staff accounts at once.</p>
                    </div>
                    <button @click="showBulkUserModal = false" class="h-8 w-8 rounded-full hover:bg-[#F8F9FA] font-bold text-[#5F6368] flex items-center justify-center"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <form @submit.prevent="submitBulkUsers" class="space-y-4">
                    <div class="rounded-2xl border-2 border-dashed border-[#DADCE0] bg-[#F8F9FA] p-6 text-center">
                        <span class="text-2xl text-[#1A73E8]"><i class="fa-solid fa-file-csv"></i></span>
                        <p class="mt-2 text-xs font-bold text-[#202124]">Select or drag staff CSV file</p>
                        <input type="file" @change="event => bulkUserForm.csv_file = event.target.files[0]" accept=".csv" required class="mt-3 block w-full text-2xs text-[#5F6368] file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-[#E8F0FE] file:text-[#1A73E8] hover:file:bg-[#D2E3FC]" />
                        <div class="flex justify-between items-center text-2xs text-[#5F6368] bg-[#F8F9FA] p-3 rounded-xl mt-4">
                            <span>Need a starting point?</span>
                            <a :href="route('admin.imports.template', 'users')" class="text-[#1A73E8] font-bold hover:underline"><i class="fa-solid fa-download mr-1"></i> Download Template</a>
                        </div>
                    </div>
                    <div class="flex justify-end space-x-3 pt-4 border-t border-[#DADCE0]">
                        <button type="button" @click="showBulkUserModal = false" class="rounded-full px-5 py-2.5 text-xs font-bold text-[#5F6368]">Cancel</button>
                        <button type="submit" :disabled="!bulkUserForm.csv_file || bulkUserForm.processing" class="rounded-full bg-[#1A73E8] px-6 py-2.5 text-xs font-bold text-white shadow-sm disabled:opacity-40">Import CSV</button>
                    </div>
                </form>
                </div>
            </div>
        </div>

        <!-- ================= MODAL: BULK IMPORT STUDENTS & PARENTS CSV ================= -->
                <div v-if="showStudentCsvModal" class="fixed inset-0 z-50 flex justify-end bg-black/40 backdrop-blur-xs overflow-hidden">
            <div class="absolute inset-0" @click="showStudentCsvModal = false"></div>
            <div class="bg-white w-full max-w-md shadow-2xl flex flex-col h-full z-10 animate-slideLeft border-l border-[#DADCE0]">
                <div class="p-6 sm:p-8 flex-1 overflow-y-auto space-y-6">
                    <div class="flex items-center justify-between border-b border-[#DADCE0] pb-4">
                    <div>
                        <h3 class="text-lg font-extrabold text-[#202124]">Bulk Student CSV Onboarding</h3>
                        <p class="text-xs text-[#5F6368]">Instantly register students with classes & parents.</p>
                    </div>
                    <button @click="showStudentCsvModal = false" class="h-8 w-8 rounded-full hover:bg-[#F8F9FA] font-bold text-[#5F6368] flex items-center justify-center"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <form @submit.prevent="submitStudentCsv" class="space-y-4">
                    <div class="rounded-2xl border-2 border-dashed border-[#DADCE0] bg-[#F8F9FA] p-6 text-center">
                        <span class="text-2xl text-[#1A73E8]"><i class="fa-solid fa-file-csv"></i></span>
                        <p class="mt-2 text-xs font-bold text-[#202124]">Select or drag students CSV file</p>
                        <input type="file" @change="event => studentCsvForm.csv_file = event.target.files[0]" accept=".csv" required class="mt-3 block w-full text-2xs text-[#5F6368] file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-[#E8F0FE] file:text-[#1A73E8] hover:file:bg-[#D2E3FC]" />
                        <div class="flex justify-between items-center text-2xs text-[#5F6368] bg-[#F8F9FA] p-3 rounded-xl mt-4">
                            <span>Need a starting point?</span>
                            <a :href="route('admin.imports.template', 'students')" class="text-[#1A73E8] font-bold hover:underline"><i class="fa-solid fa-download mr-1"></i> Download Template</a>
                        </div>
                    </div>
                    <div class="flex justify-end space-x-3 pt-4 border-t border-[#DADCE0]">
                        <button type="button" @click="showStudentCsvModal = false" class="rounded-full px-5 py-2.5 text-xs font-bold text-[#5F6368]">Cancel</button>
                        <button type="submit" :disabled="!studentCsvForm.csv_file || studentCsvForm.processing" class="rounded-full bg-[#1A73E8] px-6 py-2.5 text-xs font-bold text-white shadow-sm disabled:opacity-40">Import Students CSV</button>
                    </div>
                </form>
                </div>
            </div>
        </div>

        <!-- ================= MODAL: BULK IMPORT CLASSES & SUBJECTS CSV ================= -->
                <div v-if="showClassCsvModal" class="fixed inset-0 z-50 flex justify-end bg-black/40 backdrop-blur-xs overflow-hidden">
            <div class="absolute inset-0" @click="showClassCsvModal = false"></div>
            <div class="bg-white w-full max-w-md shadow-2xl flex flex-col h-full z-10 animate-slideLeft border-l border-[#DADCE0]">
                <div class="p-6 sm:p-8 flex-1 overflow-y-auto space-y-6">
                    <div class="flex items-center justify-between border-b border-[#DADCE0] pb-4">
                    <div>
                        <h3 class="text-lg font-extrabold text-[#202124]">Bulk Academic Hierarchy CSV</h3>
                        <p class="text-xs text-[#5F6368]">Import classes, sections & subjects simultaneously.</p>
                    </div>
                    <button @click="showClassCsvModal = false" class="h-8 w-8 rounded-full hover:bg-[#F8F9FA] font-bold text-[#5F6368] flex items-center justify-center"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <form @submit.prevent="submitClassCsv" class="space-y-4">
                    <div class="rounded-2xl border-2 border-dashed border-[#DADCE0] bg-[#F8F9FA] p-6 text-center">
                        <span class="text-2xl text-[#8430CE]"><i class="fa-solid fa-file-csv"></i></span>
                        <p class="mt-2 text-xs font-bold text-[#202124]">Select or drag classes & subjects CSV</p>
                        <input type="file" @change="event => classCsvForm.csv_file = event.target.files[0]" accept=".csv" required class="mt-3 block w-full text-2xs text-[#5F6368] file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-[#F3E8FD] file:text-[#8430CE] hover:file:bg-[#E9D5FF]" />
                        <div class="flex justify-between items-center text-2xs text-[#5F6368] bg-[#F8F9FA] p-3 rounded-xl mt-4">
                            <span>Need a starting point?</span>
                            <a :href="route('admin.imports.template', 'classes')" class="text-[#8430CE] font-bold hover:underline"><i class="fa-solid fa-download mr-1"></i> Download Template</a>
                        </div>
                    </div>
                    <div class="flex justify-end space-x-3 pt-4 border-t border-[#DADCE0]">
                        <button type="button" @click="showClassCsvModal = false" class="rounded-full px-5 py-2.5 text-xs font-bold text-[#5F6368]">Cancel</button>
                        <button type="submit" :disabled="!classCsvForm.csv_file || classCsvForm.processing" class="rounded-full bg-[#8430CE] px-6 py-2.5 text-xs font-bold text-white shadow-sm disabled:opacity-40">Import Classes CSV</button>
                    </div>
                </form>
                </div>
            </div>
        </div>

        <!-- ================= MODAL: MANUAL STUDENT ENROLLMENT ================= -->
                <div v-if="showAddStudentModal" class="fixed inset-0 z-50 flex justify-end bg-black/40 backdrop-blur-xs overflow-hidden">
            <div class="absolute inset-0" @click="showAddStudentModal = false"></div>
            <div class="bg-white w-full max-w-2xl shadow-2xl flex flex-col h-full z-10 animate-slideLeft border-l border-[#DADCE0]">
                <div class="p-6 sm:p-8 flex-1 overflow-y-auto space-y-6">
                    <div class="flex items-center justify-between border-b border-[#DADCE0] pb-4">
                    <div>
                        <h3 class="text-lg font-extrabold text-[#202124]">Manual Student Enrollment</h3>
                        <p class="text-xs text-[#5F6368]">Direct admission entry bypassing test pipeline.</p>
                    </div>
                    <button @click="showAddStudentModal = false" class="h-8 w-8 rounded-full hover:bg-[#F8F9FA] font-bold text-[#5F6368] flex items-center justify-center"><i class="fa-solid fa-xmark"></i></button>
                </div>

                <form @submit.prevent="submitAddStudent" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-[#202124] mb-1">Student Full Name *</label>
                            <input v-model="studentForm.student_name" type="text" required placeholder="Student Full Name" class="w-full rounded-xl border border-[#DADCE0] px-4 py-2.5 text-xs focus:ring-2 focus:ring-[#1A73E8]" />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-[#202124] mb-1">Admission Number *</label>
                            <input v-model="studentForm.admission_number" type="text" required placeholder="Admission Number" class="w-full rounded-xl border border-[#DADCE0] px-4 py-2.5 text-xs font-mono font-bold text-[#1A73E8] focus:ring-2 focus:ring-[#1A73E8]" />
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-[#202124] mb-1">Class Assigned *</label>
                            <select v-model="studentForm.current_class_id" required class="w-full rounded-xl border border-[#DADCE0] px-3.5 py-2.5 text-xs font-bold">
                                <option value="">-- Select Class --</option>
                                <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }} ({{ c.level }})</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-[#202124] mb-1">Section Assigned</label>
                            <select v-model="studentForm.current_section_id" class="w-full rounded-xl border border-[#DADCE0] px-3.5 py-2.5 text-xs">
                                <option value="">-- No Section Yet --</option>
                                <option v-for="sec in sections?.filter(s => !studentForm.current_class_id || s.class_id == studentForm.current_class_id)" :key="sec.id" :value="sec.id">{{ sec.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-[#202124] mb-1">Roll Number</label>
                            <input v-model="studentForm.roll_number" type="text" placeholder="Roll Number" class="w-full rounded-xl border border-[#DADCE0] px-3.5 py-2.5 text-xs" />
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-[#202124] mb-1">Date of Birth</label>
                            <input v-model="studentForm.date_of_birth" type="date" class="w-full rounded-xl border border-[#DADCE0] px-3.5 py-2.5 text-xs" />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-[#202124] mb-1">Gender *</label>
                            <select v-model="studentForm.gender" required class="w-full rounded-xl border border-[#DADCE0] px-3.5 py-2.5 text-xs">
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-[#202124] mb-1">B-Form / CNIC</label>
                            <input v-model="studentForm.b_form" type="text" placeholder="B-Form / CNIC Number" class="w-full rounded-xl border border-[#DADCE0] px-3.5 py-2.5 text-xs" />
                        </div>
                    </div>

                    <!-- Father / Guardian Linking -->
                    <div class="rounded-2xl bg-[#F8F9FA] border border-[#DADCE0] p-4 space-y-3">
                        <div class="text-xs font-extrabold text-[#202124] uppercase">Father / Guardian Profile Linking</div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-2xs font-bold text-[#202124] mb-1">Father's Name *</label>
                                <input v-model="studentForm.father_name" type="text" required placeholder="Father / Guardian Name" class="w-full rounded-xl border border-[#DADCE0] bg-white px-3.5 py-2 text-xs" />
                            </div>
                            <div>
                                <label class="block text-2xs font-bold text-[#202124] mb-1">Father's Phone Number *</label>
                                <input v-model="studentForm.father_phone" type="text" required placeholder="Father Phone Number" class="w-full rounded-xl border border-[#DADCE0] bg-white px-3.5 py-2 text-xs" />
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 pt-4 border-t border-[#DADCE0]">
                        <button type="button" @click="showAddStudentModal = false" class="rounded-full px-5 py-2.5 text-xs font-bold text-[#5F6368] hover:bg-[#F8F9FA]">Cancel</button>
                        <button type="submit" :disabled="studentForm.processing" class="rounded-full bg-[#1A73E8] px-6 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-[#1557B0] disabled:opacity-50 inline-flex items-center gap-1.5"><i class="fa-solid fa-check"></i> <span>Complete Enrollment</span></button>
                    </div>
                </form>
                </div>
            </div>
        </div>

        <!-- ================= MODAL: EDIT STUDENT PROFILE ================= -->
                <div v-if="showEditStudentModal" class="fixed inset-0 z-50 flex justify-end bg-black/40 backdrop-blur-xs overflow-hidden">
            <div class="absolute inset-0" @click="showEditStudentModal = false"></div>
            <div class="bg-white w-full max-w-lg shadow-2xl flex flex-col h-full z-10 animate-slideLeft border-l border-[#DADCE0]">
                <div class="p-6 sm:p-8 flex-1 overflow-y-auto space-y-6">
                    <div class="flex items-center justify-between border-b border-[#DADCE0] pb-4">
                    <div>
                        <h3 class="text-lg font-extrabold text-[#202124]">Edit Student Demographics</h3>
                        <p class="text-xs text-[#5F6368]">Update personal profile details.</p>
                    </div>
                    <button @click="showEditStudentModal = false" class="h-8 w-8 rounded-full hover:bg-[#F8F9FA] font-bold text-[#5F6368] flex items-center justify-center"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <form @submit.prevent="submitEditStudent" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-[#202124] mb-1">Student Full Name *</label>
                        <input v-model="editStudentForm.student_name" type="text" required class="w-full rounded-xl border border-[#DADCE0] px-4 py-2.5 text-xs focus:ring-2 focus:ring-[#1A73E8]" />
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-[#202124] mb-1">Roll Number</label>
                            <input v-model="editStudentForm.roll_number" type="text" class="w-full rounded-xl border border-[#DADCE0] px-4 py-2.5 text-xs focus:ring-2 focus:ring-[#1A73E8]" />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-[#202124] mb-1">B-Form / CNIC</label>
                            <input v-model="editStudentForm.b_form" type="text" class="w-full rounded-xl border border-[#DADCE0] px-4 py-2.5 text-xs focus:ring-2 focus:ring-[#1A73E8]" />
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-[#202124] mb-1">Date of Birth</label>
                            <input v-model="editStudentForm.date_of_birth" type="date" class="w-full rounded-xl border border-[#DADCE0] px-4 py-2.5 text-xs" />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-[#202124] mb-1">Gender *</label>
                            <select v-model="editStudentForm.gender" required class="w-full rounded-xl border border-[#DADCE0] px-4 py-2.5 text-xs">
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex justify-end space-x-3 pt-4 border-t border-[#DADCE0]">
                        <button type="button" @click="showEditStudentModal = false" class="rounded-full px-5 py-2.5 text-xs font-bold text-[#5F6368] hover:bg-[#F8F9FA]">Cancel</button>
                        <button type="submit" :disabled="editStudentForm.processing" class="rounded-full bg-[#1A73E8] px-6 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-[#1557B0] disabled:opacity-50 inline-flex items-center gap-1.5"><i class="fa-solid fa-check"></i> <span>Update Profile</span></button>
                    </div>
                </form>
                </div>
            </div>
        </div>

        <!-- ================= MODAL: CHANGE STUDENT STATUS & IMMUTABLE AUDIT LOG ================= -->
                <div v-if="showStudentStatusModal" class="fixed inset-0 z-50 flex justify-end bg-black/40 backdrop-blur-xs overflow-hidden">
            <div class="absolute inset-0" @click="showStudentStatusModal = false"></div>
            <div class="bg-white w-full max-w-lg shadow-2xl flex flex-col h-full z-10 animate-slideLeft border-l border-[#DADCE0]">
                <div class="p-6 sm:p-8 flex-1 overflow-y-auto space-y-6">
                    <div class="flex items-center justify-between border-b border-[#DADCE0] pb-4">
                    <div>
                        <h3 class="text-lg font-extrabold text-[#202124]">Student Status Audit Transition</h3>
                        <p class="text-xs text-[#5F6368]">Every lifecycle change is logged to an immutable trail.</p>
                    </div>
                    <button @click="showStudentStatusModal = false" class="h-8 w-8 rounded-full hover:bg-[#F8F9FA] font-bold text-[#5F6368] flex items-center justify-center"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <form @submit.prevent="submitStudentStatus" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-[#202124] mb-1">New Status *</label>
                        <select v-model="statusForm.status" required class="w-full rounded-xl border border-[#DADCE0] px-4 py-3 text-sm font-extrabold text-[#1A73E8] focus:ring-2 focus:ring-[#1A73E8]">
                            <option value="Active">Active (Enrolled & Attending)</option>
                            <option value="On Leave">On Leave (Temporary Medical / Family Leave)</option>
                            <option value="Suspended">Suspended (Disciplinary Action)</option>
                            <option value="Transferred Out">Transferred Out (Issued School Leaving Certificate)</option>
                            <option value="Graduated">Graduated (Alumni)</option>
                            <option value="Expelled">Expelled</option>
                            <option value="Inactive/Dropped">Inactive / Dropped</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-[#202124] mb-1">Mandatory Audit Reason / Notes *</label>
                        <textarea v-model="statusForm.reason" required rows="3" placeholder="Reason for status change (e.g. transfer, fee clearance, or graduation)..." class="w-full rounded-xl border border-[#DADCE0] px-4 py-2.5 text-xs focus:ring-2 focus:ring-[#1A73E8]"></textarea>
                    </div>

                    <!-- Past Status Logs History -->
                    <div v-if="selectedStudent?.status_logs?.length" class="rounded-2xl bg-[#F8F9FA] border border-[#DADCE0] p-4 space-y-2 max-h-48 overflow-y-auto">
                        <div class="text-2xs font-extrabold uppercase text-[#5F6368]">Immutable Audit Trail History</div>
                        <div v-for="log in selectedStudent.status_logs" :key="log.id" class="border-b border-[#DADCE0] pb-2 last:border-none last:pb-0">
                            <div class="flex justify-between items-center text-2xs font-bold">
                                <span class="text-[#1A73E8]">{{ log.old_status || 'Initial' }} → {{ log.new_status }}</span>
                                <span class="text-[#5F6368]">{{ log.created_at?.substring(0, 10) }}</span>
                            </div>
                            <p class="text-2xs text-[#202124] mt-0.5 font-mono">"{{ log.reason }}"</p>
                            <div class="text-[10px] text-[#5F6368]">Logged by: {{ log.changed_by?.name || 'Admin' }}</div>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 pt-4 border-t border-[#DADCE0]">
                        <button type="button" @click="showStudentStatusModal = false" class="rounded-full px-5 py-2.5 text-xs font-bold text-[#5F6368] hover:bg-[#F8F9FA]">Cancel</button>
                        <button type="submit" :disabled="!statusForm.reason || statusForm.processing" class="rounded-full bg-[#1A73E8] px-6 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-[#1557B0] disabled:opacity-50 inline-flex items-center gap-1.5"><i class="fa-solid fa-check"></i> <span>Commit Transition</span></button>
                    </div>
                </form>
                </div>
            </div>
        </div>

        <!-- ================= MODAL: SECTION TRANSFER ================= -->
                <div v-if="showStudentTransferModal" class="fixed inset-0 z-50 flex justify-end bg-black/40 backdrop-blur-xs overflow-hidden">
            <div class="absolute inset-0" @click="showStudentTransferModal = false"></div>
            <div class="bg-white w-full max-w-md shadow-2xl flex flex-col h-full z-10 animate-slideLeft border-l border-[#DADCE0]">
                <div class="p-6 sm:p-8 flex-1 overflow-y-auto space-y-6">
                    <div class="flex items-center justify-between border-b border-[#DADCE0] pb-4">
                    <div>
                        <h3 class="text-lg font-extrabold text-[#202124]">Transfer Class or Section</h3>
                        <p class="text-xs text-[#5F6368]">Move student between academic divisions.</p>
                    </div>
                    <button @click="showStudentTransferModal = false" class="h-8 w-8 rounded-full hover:bg-[#F8F9FA] font-bold text-[#5F6368] flex items-center justify-center"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <form @submit.prevent="submitStudentTransfer" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-[#202124] mb-1">Target Class Level *</label>
                        <select v-model="transferForm.class_id" required class="w-full rounded-xl border border-[#DADCE0] px-4 py-2.5 text-xs font-bold">
                            <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }} ({{ c.level }})</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-[#202124] mb-1">Target Section *</label>
                        <select v-model="transferForm.section_id" class="w-full rounded-xl border border-[#DADCE0] px-4 py-2.5 text-xs">
                            <option value="">-- No Section --</option>
                            <option v-for="sec in sections?.filter(s => !transferForm.class_id || s.class_id == transferForm.class_id)" :key="sec.id" :value="sec.id">{{ sec.name }}</option>
                        </select>
                    </div>
                    <div class="flex justify-end space-x-3 pt-4 border-t border-[#DADCE0]">
                        <button type="button" @click="showStudentTransferModal = false" class="rounded-full px-5 py-2.5 text-xs font-bold text-[#5F6368] hover:bg-[#F8F9FA]">Cancel</button>
                        <button type="submit" :disabled="transferForm.processing" class="rounded-full bg-[#1A73E8] px-6 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-[#1557B0] disabled:opacity-50 inline-flex items-center gap-1.5"><i class="fa-solid fa-check"></i> <span>Confirm Transfer</span></button>
                    </div>
                </form>
                </div>
            </div>
        </div>

        <!-- ================= MODAL: END-OF-YEAR BULK PROMOTION ================= -->
                <div v-if="showBulkPromoteModal" class="fixed inset-0 z-50 flex justify-end bg-black/40 backdrop-blur-xs overflow-hidden">
            <div class="absolute inset-0" @click="showBulkPromoteModal = false"></div>
            <div class="bg-white w-full max-w-lg shadow-2xl flex flex-col h-full z-10 animate-slideLeft border-l border-[#DADCE0]">
                <div class="p-6 sm:p-8 flex-1 overflow-y-auto space-y-6">
                    <div class="flex items-center justify-between border-b border-[#DADCE0] pb-4">
                    <div>
                        <h3 class="text-lg font-extrabold text-[#202124]">End-of-Year Bulk Promotion</h3>
                        <p class="text-xs text-[#5F6368]">Promote entire class level with exception grid.</p>
                    </div>
                    <button @click="showBulkPromoteModal = false" class="h-8 w-8 rounded-full hover:bg-[#F8F9FA] font-bold text-[#5F6368] flex items-center justify-center"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <form @submit.prevent="submitBulkPromote" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-[#202124] mb-1">From Class *</label>
                            <select v-model="promoteForm.from_class_id" required class="w-full rounded-xl border border-[#DADCE0] px-3.5 py-2.5 text-xs font-bold text-[#C5221F]">
                                <option value="">-- Current Grade --</option>
                                <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-[#202124] mb-1">Promote To Class *</label>
                            <select v-model="promoteForm.to_class_id" required class="w-full rounded-xl border border-[#DADCE0] px-3.5 py-2.5 text-xs font-bold text-[#137333]">
                                <option value="">-- Next Grade --</option>
                                <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-[#202124] mb-1">Target Section (Optional)</label>
                            <select v-model="promoteForm.to_section_id" class="w-full rounded-xl border border-[#DADCE0] px-3.5 py-2.5 text-xs font-bold text-[#1A73E8]">
                                <option value="">-- Unassigned --</option>
                                <option v-for="sec in sections?.filter(s => s.class_id == promoteForm.to_class_id || !promoteForm.to_class_id)" :key="sec.id" :value="sec.id">{{ sec.tenant_class?.name || 'Sec' }} - {{ sec.name }}</option>
                            </select>
                        </div>
                    </div>

                    <!-- Exception Grid (Hold back / Repeaters) -->
                    <div v-if="promoteForm.from_class_id" class="rounded-2xl bg-[#F8F9FA] border border-[#DADCE0] p-4 space-y-3 max-h-56 overflow-y-auto">
                        <div class="text-xs font-bold text-[#202124] flex items-center justify-between">
                            <span>Exempt / Hold-Back Students (Will NOT Promote)</span>
                            <span class="text-2xs text-[#C5221F] font-semibold">{{ promoteForm.exception_student_ids.length }} Selected</span>
                        </div>
                        <div class="space-y-2">
                            <label v-for="stu in students?.filter(s => s.current_class_id == promoteForm.from_class_id && ['Active', 'On Leave'].includes(s.status))" :key="stu.id" class="flex items-center space-x-2 text-xs bg-white p-2 rounded-xl border border-[#DADCE0] cursor-pointer hover:border-[#1A73E8]">
                                <input type="checkbox" :value="stu.id" v-model="promoteForm.exception_student_ids" class="rounded border-[#DADCE0] text-[#1A73E8]" />
                                <span class="font-bold">{{ stu.user?.name || 'Student' }}</span>
                                <span class="text-2xs text-[#5F6368] font-mono">(Roll: {{ stu.roll_number || 'N/A' }} | {{ stu.status }})</span>
                            </label>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 pt-4 border-t border-[#DADCE0]">
                        <button type="button" @click="showBulkPromoteModal = false" class="rounded-full px-5 py-2.5 text-xs font-bold text-[#5F6368] hover:bg-[#F8F9FA]">Cancel</button>
                        <button type="submit" :disabled="!promoteForm.from_class_id || !promoteForm.to_class_id || promoteForm.processing" class="rounded-full bg-[#137333] px-6 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-[#0d5625] disabled:opacity-40 inline-flex items-center gap-1.5"><i class="fa-solid fa-angles-up"></i> <span>Execute Bulk Promotion</span></button>
                    </div>
                </form>
                </div>
            </div>
        </div>

        <!-- ================= MODAL: STUDENT DOCUMENT VAULT ================= -->
                <div v-if="showDocumentVaultModal" class="fixed inset-0 z-50 flex justify-end bg-black/40 backdrop-blur-xs overflow-hidden">
            <div class="absolute inset-0" @click="showDocumentVaultModal = false"></div>
            <div class="bg-white w-full max-w-xl shadow-2xl flex flex-col h-full z-10 animate-slideLeft border-l border-[#DADCE0]">
                <div class="p-6 sm:p-8 flex-1 overflow-y-auto space-y-6">
                    <div class="flex items-center justify-between border-b border-[#DADCE0] pb-4">
                    <div>
                        <h3 class="text-lg font-extrabold text-[#202124]">Document Vault — {{ selectedStudent?.user?.name }}</h3>
                        <p class="text-xs text-[#5F6368]">Secure repository for certificates, B-Forms, and medical files.</p>
                    </div>
                    <button @click="showDocumentVaultModal = false" class="h-8 w-8 rounded-full hover:bg-[#F8F9FA] font-bold text-[#5F6368] flex items-center justify-center"><i class="fa-solid fa-xmark"></i></button>
                </div>

                <!-- Existing Vault Documents List -->
                <div class="space-y-3 max-h-56 overflow-y-auto">
                    <div v-if="!selectedStudent?.documents || selectedStudent.documents.length === 0" class="py-6 text-center text-xs text-[#5F6368] bg-[#F8F9FA] rounded-2xl border border-[#DADCE0]">
                        No files in student vault yet. Upload required documents below.
                    </div>
                    <div v-for="doc in selectedStudent?.documents" :key="doc.id" class="flex items-center justify-between rounded-2xl bg-[#F8F9FA] p-3.5 border border-[#DADCE0]">
                        <div class="flex items-center space-x-3">
                            <span class="text-2xl text-[#1A73E8]"><i class="fa-solid fa-file-lines"></i></span>
                            <div>
                                <div class="font-bold text-xs text-[#202124]">{{ doc.title }}</div>
                                <div class="text-2xs text-[#1A73E8] font-semibold flex items-center gap-1.5"><span>{{ doc.document_type }}</span> <i class="fa-solid fa-circle text-[5px]"></i> <span>Uploaded on {{ doc.created_at?.substring(0, 10) }}</span></div>
                            </div>
                        </div>
                        <a :href="doc.file_url" target="_blank" class="rounded-xl bg-white border border-[#DADCE0] px-3 py-1.5 text-2xs font-bold text-[#1A73E8] hover:bg-[#E8F0FE] transition-all flex items-center gap-1"><span>Download / View</span> <i class="fa-solid fa-arrow-up-right-from-square"></i></a>
                    </div>
                </div>

                <!-- New Document Upload Form -->
                <form @submit.prevent="submitDocumentUpload" class="space-y-4 pt-4 border-t border-[#DADCE0]">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-[#5F6368]">Upload New Document</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-2xs font-bold text-[#202124] mb-1">Document Title *</label>
                            <input v-model="documentForm.title" type="text" required placeholder="Document Title (e.g. B-Form, Certificate)" class="w-full rounded-xl border border-[#DADCE0] px-3.5 py-2 text-xs" />
                        </div>
                        <div>
                            <label class="block text-2xs font-bold text-[#202124] mb-1">Category / Type *</label>
                            <select v-model="documentForm.document_type" required class="w-full rounded-xl border border-[#DADCE0] px-3.5 py-2 text-xs font-bold">
                                <option value="Birth Certificate / B-Form">Birth Certificate / B-Form</option>
                                <option value="School Leaving Certificate (SLC)">School Leaving Certificate (SLC)</option>
                                <option value="Medical Certificate">Medical Certificate</option>
                                <option value="Father CNIC Copy">Father CNIC Copy</option>
                                <option value="Passport Photo">Passport Photo</option>
                                <option value="General Document">General Document</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-2xs font-bold text-[#202124] mb-1">File Attachment (PDF, PNG, JPG up to 8MB) *</label>
                        <input type="file" @change="handleDocumentFileSelect" accept=".pdf,.png,.jpg,.jpeg,.webp" required class="w-full text-2xs text-[#5F6368] file:mr-3 file:py-1.5 file:px-3 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-[#E8F0FE] file:text-[#1A73E8]" />
                    </div>
                    <div class="flex justify-end space-x-3 pt-2">
                        <button type="button" @click="showDocumentVaultModal = false" class="rounded-full px-5 py-2 text-xs font-bold text-[#5F6368]">Done</button>
                        <button type="submit" :disabled="!documentForm.document_file || documentForm.processing" class="rounded-full bg-[#1A73E8] px-5 py-2 text-xs font-bold text-white shadow-sm disabled:opacity-40 inline-flex items-center gap-1.5"><i class="fa-solid fa-cloud-arrow-up"></i> <span>Upload to Vault</span></button>
                    </div>
                </form>
                </div>
            </div>
        </div>

        <!-- ================= MODAL: TIMETABLE SLOT SCHEDULER ================= -->
                <div v-if="showTimetableModal" class="fixed inset-0 z-50 flex justify-end bg-black/40 backdrop-blur-xs overflow-hidden">
            <div class="absolute inset-0" @click="showTimetableModal = false"></div>
            <div class="bg-white w-full max-w-lg shadow-2xl flex flex-col h-full z-10 animate-slideLeft border-l border-[#DADCE0]">
                <div class="p-6 sm:p-8 flex-1 overflow-y-auto space-y-6">
                    <div class="flex items-center justify-between border-b border-[#DADCE0] pb-4">
                    <div>
                        <h3 class="text-lg font-extrabold text-[#202124]">Schedule Timetable Slot</h3>
                        <p class="text-xs text-[#5F6368]">Assign period, day, subject, and teacher with automatic conflict check.</p>
                    </div>
                    <button @click="showTimetableModal = false" class="h-8 w-8 rounded-full hover:bg-[#F8F9FA] font-bold text-[#5F6368] flex items-center justify-center"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <form @submit.prevent="submitTimetable" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-[#202124] mb-1">Target Section / Homeroom *</label>
                        <select v-model="timetableForm.section_id" required class="w-full rounded-xl border border-[#DADCE0] px-4 py-2.5 text-xs font-bold focus:ring-2 focus:ring-[#1A73E8]">
                            <option value="">-- Choose Section --</option>
                            <option v-for="sec in sections" :key="sec.id" :value="sec.id">{{ sec.tenantClass?.name || sec.class?.name || 'Class' }} — Section {{ sec.name }}</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-[#202124] mb-1">Subject *</label>
                            <select v-model="timetableForm.subject_id" required class="w-full rounded-xl border border-[#DADCE0] px-4 py-2.5 text-xs font-bold focus:ring-2 focus:ring-[#1A73E8]">
                                <option value="">-- Choose Subject --</option>
                                <option v-for="sub in subjects" :key="sub.id" :value="sub.id">{{ sub.name }} ({{ sub.type }})</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-[#202124] mb-1">Teacher *</label>
                            <select v-model="timetableForm.teacher_id" required class="w-full rounded-xl border border-[#DADCE0] px-4 py-2.5 text-xs font-bold focus:ring-2 focus:ring-[#1A73E8]">
                                <option value="">-- Assign Teacher --</option>
                                <option v-for="t in users?.filter(u => u.user_type === 'teacher')" :key="t.id" :value="t.id">{{ t.name }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-2xs font-bold text-[#202124] mb-1">Day of Week *</label>
                            <select v-model="timetableForm.day_of_week" required class="w-full rounded-xl border border-[#DADCE0] px-3 py-2 text-xs font-bold">
                                <option value="Monday">Monday</option>
                                <option value="Tuesday">Tuesday</option>
                                <option value="Wednesday">Wednesday</option>
                                <option value="Thursday">Thursday</option>
                                <option value="Friday">Friday</option>
                                <option value="Saturday">Saturday</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-2xs font-bold text-[#202124] mb-1">Start Time *</label>
                            <input v-model="timetableForm.start_time" type="time" required class="w-full rounded-xl border border-[#DADCE0] px-3 py-2 text-xs font-mono" />
                        </div>
                        <div>
                            <label class="block text-2xs font-bold text-[#202124] mb-1">End Time *</label>
                            <input v-model="timetableForm.end_time" type="time" required class="w-full rounded-xl border border-[#DADCE0] px-3 py-2 text-xs font-mono" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-[#202124] mb-1">Room / Lab Number</label>
                        <input v-model="timetableForm.room_number" type="text" placeholder="Room 101 or Computer Lab A" class="w-full rounded-xl border border-[#DADCE0] px-4 py-2.5 text-xs" />
                    </div>
                    <div class="rounded-xl bg-[#E8F0FE]/70 border border-[#D2E3FC] p-3 text-2xs text-[#1A73E8]">
                        <p class="font-bold flex items-center gap-1.5"><i class="fa-solid fa-shield-halved text-[#1A73E8]"></i> <span>Intelligent Conflict Guard Active:</span></p>
                        <p>If this teacher or room is already booked during this period on this day, the system will alert you and prevent overlapping schedules.</p>
                    </div>
                    <div class="flex justify-end space-x-3 pt-4 border-t border-[#DADCE0]">
                        <button type="button" @click="showTimetableModal = false" class="rounded-full px-5 py-2.5 text-xs font-bold text-[#5F6368]">Cancel</button>
                        <button type="submit" :disabled="timetableForm.processing" class="rounded-full bg-[#1A73E8] px-6 py-2.5 text-xs font-bold text-white shadow-sm disabled:opacity-50 inline-flex items-center gap-1.5"><i class="fa-solid fa-check"></i> <span>Schedule Slot</span></button>
                    </div>
                </form>
                </div>
            </div>
        </div>

        <!-- ================= MODAL: FEE OVERRIDE / SCHOLARSHIP ================= -->
                <div v-if="showFeeOverrideModal" class="fixed inset-0 z-50 flex justify-end bg-black/40 backdrop-blur-xs overflow-hidden">
            <div class="absolute inset-0" @click="showFeeOverrideModal = false"></div>
            <div class="bg-white w-full max-w-lg shadow-2xl flex flex-col h-full z-10 animate-slideLeft border-l border-[#DADCE0]">
                <div class="p-6 sm:p-8 flex-1 overflow-y-auto space-y-6">
                    <div class="flex items-center justify-between border-b border-[#DADCE0] pb-4">
                    <div>
                        <h3 class="text-lg font-extrabold text-[#202124]">Apply Student Fee Override</h3>
                        <p class="text-xs text-[#5F6368]">Assign sibling discount, merit scholarship, or staff child benefit.</p>
                    </div>
                    <button @click="showFeeOverrideModal = false" class="h-8 w-8 rounded-full hover:bg-[#F8F9FA] font-bold text-[#5F6368] flex items-center justify-center"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <form @submit.prevent="submitFeeOverride" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-[#202124] mb-1">Select Student *</label>
                        <select v-model="feeOverrideForm.student_id" required class="w-full rounded-xl border border-[#DADCE0] px-4 py-2.5 text-xs font-bold">
                            <option value="">-- Choose Student --</option>
                            <option v-for="st in students" :key="st.id" :value="st.id">{{ st.user?.name || `Student #${st.admission_number}` }} ({{ st.roll_number || st.admission_number }})</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-[#202124] mb-1">Fee Category *</label>
                            <select v-model="feeOverrideForm.fee_category" required class="w-full rounded-xl border border-[#DADCE0] px-4 py-2.5 text-xs font-bold">
                                <option value="tuition">Monthly Tuition</option>
                                <option value="lab">Lab Charges</option>
                                <option value="transport">Transport Charges</option>
                                <option value="annual">Annual Charges</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-[#202124] mb-1">Discount Reason *</label>
                            <select v-model="feeOverrideForm.override_reason" required class="w-full rounded-xl border border-[#DADCE0] px-4 py-2.5 text-xs font-bold text-[#137333]">
                                <option value="sibling_discount">Sibling Discount</option>
                                <option value="merit_scholarship">Merit Scholarship</option>
                                <option value="staff_child">Staff Child Concession</option>
                                <option value="financial_aid">Need-based Financial Aid</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-[#202124] mb-1">Percentage Discount (%)</label>
                            <input v-model="feeOverrideForm.discount_percentage" type="number" min="0" max="100" class="w-full rounded-xl border border-[#DADCE0] px-4 py-2.5 text-xs font-bold" />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-[#202124] mb-1">Fixed Amount Discount (Rs.)</label>
                            <input v-model="feeOverrideForm.discount_amount" type="number" min="0" class="w-full rounded-xl border border-[#DADCE0] px-4 py-2.5 text-xs font-bold" />
                        </div>
                    </div>
                    <div class="flex justify-end space-x-3 pt-4 border-t border-[#DADCE0]">
                        <button type="button" @click="showFeeOverrideModal = false" class="rounded-full px-5 py-2.5 text-xs font-bold text-[#5F6368]">Cancel</button>
                        <button type="submit" :disabled="feeOverrideForm.processing" class="rounded-full bg-[#137333] px-6 py-2.5 text-xs font-bold text-white shadow-sm disabled:opacity-50 inline-flex items-center gap-1.5"><i class="fa-solid fa-check"></i> <span>Apply Concession</span></button>
                    </div>
                </form>
                </div>
            </div>
        </div>

        <!-- ================= MODAL: GENERATE MONTHLY CHALLANS ================= -->
                <div v-if="showMonthlyBillingModal" class="fixed inset-0 z-50 flex justify-end bg-black/40 backdrop-blur-xs overflow-hidden">
            <div class="absolute inset-0" @click="showMonthlyBillingModal = false"></div>
            <div class="bg-white w-full max-w-md shadow-2xl flex flex-col h-full z-10 animate-slideLeft border-l border-[#DADCE0]">
                <div class="p-6 sm:p-8 flex-1 overflow-y-auto space-y-6">
                    <div class="flex items-center justify-between border-b border-[#DADCE0] pb-4">
                    <div>
                        <h3 class="text-lg font-extrabold text-[#202124]">Generate Monthly Challans</h3>
                        <p class="text-xs text-[#5F6368]">Create fee invoices for all active students.</p>
                    </div>
                    <button @click="showMonthlyBillingModal = false" class="h-8 w-8 rounded-full hover:bg-[#F8F9FA] font-bold text-[#5F6368] flex items-center justify-center"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <form @submit.prevent="submitMonthlyBilling" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-[#202124] mb-1">Billing Month</label>
                        <input v-model="monthlyBillingForm.month_year" type="month" required class="w-full rounded-xl border border-[#DADCE0] px-4 py-2.5 text-xs font-bold" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-[#202124] mb-1">Issue Date</label>
                            <input v-model="monthlyBillingForm.issue_date" type="date" required class="w-full rounded-xl border border-[#DADCE0] px-4 py-2.5 text-xs font-bold" />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-[#202124] mb-1">Due Date</label>
                            <input v-model="monthlyBillingForm.due_date" type="date" required class="w-full rounded-xl border border-[#DADCE0] px-4 py-2.5 text-xs font-bold" />
                        </div>
                    </div>
                    <div class="flex justify-end space-x-3 pt-4 border-t border-[#DADCE0]">
                        <button type="button" @click="showMonthlyBillingModal = false" class="rounded-full px-5 py-2.5 text-xs font-bold text-[#5F6368]">Cancel</button>
                        <button type="submit" :disabled="monthlyBillingForm.processing" class="rounded-full bg-[#137333] px-6 py-2.5 text-xs font-bold text-white shadow-sm disabled:opacity-50 inline-flex items-center gap-1.5"><i class="fa-solid fa-file-invoice-dollar"></i> <span>Generate Now</span></button>
                    </div>
                </form>
                </div>
            </div>
        </div>

        <!-- ================= MODAL: STAFF CHECK-IN ================= -->
                <div v-if="showStaffCheckInModal" class="fixed inset-0 z-50 flex justify-end bg-black/40 backdrop-blur-xs overflow-hidden">
            <div class="absolute inset-0" @click="showStaffCheckInModal = false"></div>
            <div class="bg-white w-full max-w-md shadow-2xl flex flex-col h-full z-10 animate-slideLeft border-l border-[#DADCE0]">
                <div class="p-6 sm:p-8 flex-1 overflow-y-auto space-y-6">
                    <div class="flex items-center justify-between border-b border-[#DADCE0] pb-4">
                    <div>
                        <h3 class="text-lg font-extrabold text-[#202124]">Mark Staff Attendance</h3>
                        <p class="text-xs text-[#5F6368]">Log daily check-in status for faculty or admin.</p>
                    </div>
                    <button @click="showStaffCheckInModal = false" class="h-8 w-8 rounded-full hover:bg-[#F8F9FA] font-bold text-[#5F6368] flex items-center justify-center"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <form @submit.prevent="submitStaffCheckIn" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-[#202124] mb-1">Select Staff Member *</label>
                        <select v-model="staffCheckInForm.user_id" required class="w-full rounded-xl border border-[#DADCE0] px-4 py-2.5 text-xs font-bold">
                            <option value="">-- Choose Faculty / Admin --</option>
                            <option v-for="u in users?.filter(u => ['teacher', 'admin', 'accountant', 'registrar', 'librarian'].includes(u.user_type))" :key="u.id" :value="u.id">{{ u.name }} ({{ u.user_type }})</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-2xs font-bold text-[#202124] mb-1">Date *</label>
                            <input v-model="staffCheckInForm.date" type="date" required class="w-full rounded-xl border border-[#DADCE0] px-3 py-2 text-xs font-mono" />
                        </div>
                        <div>
                            <label class="block text-2xs font-bold text-[#202124] mb-1">Attendance Status *</label>
                            <select v-model="staffCheckInForm.status" required class="w-full rounded-xl border border-[#DADCE0] px-3 py-2 text-xs font-bold uppercase">
                                <option value="present">Present</option>
                                <option value="absent">Absent</option>
                                <option value="late">Late Arrival</option>
                                <option value="half_day">Half Day</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-2xs font-bold text-[#202124] mb-1">Check-in Time</label>
                            <input v-model="staffCheckInForm.check_in_time" type="time" class="w-full rounded-xl border border-[#DADCE0] px-3 py-2 text-xs font-mono" />
                        </div>
                        <div>
                            <label class="block text-2xs font-bold text-[#202124] mb-1">Check-out Time</label>
                            <input v-model="staffCheckInForm.check_out_time" type="time" class="w-full rounded-xl border border-[#DADCE0] px-3 py-2 text-xs font-mono" />
                        </div>
                    </div>
                    <div class="flex justify-end space-x-3 pt-4 border-t border-[#DADCE0]">
                        <button type="button" @click="showStaffCheckInModal = false" class="rounded-full px-5 py-2.5 text-xs font-bold text-[#5F6368]">Cancel</button>
                        <button type="submit" :disabled="staffCheckInForm.processing" class="rounded-full bg-[#1A73E8] px-6 py-2.5 text-xs font-bold text-white shadow-sm disabled:opacity-50 inline-flex items-center gap-1.5"><i class="fa-solid fa-check"></i> <span>Mark Attendance</span></button>
                    </div>
                </form>
                </div>
            </div>
        </div>

        <!-- ================= MODAL: STAFF LEAVE APPLICATION ================= -->
                <div v-if="showStaffLeaveModal" class="fixed inset-0 z-50 flex justify-end bg-black/40 backdrop-blur-xs overflow-hidden">
            <div class="absolute inset-0" @click="showStaffLeaveModal = false"></div>
            <div class="bg-white w-full max-w-md shadow-2xl flex flex-col h-full z-10 animate-slideLeft border-l border-[#DADCE0]">
                <div class="p-6 sm:p-8 flex-1 overflow-y-auto space-y-6">
                    <div class="flex items-center justify-between border-b border-[#DADCE0] pb-4">
                    <div>
                        <h3 class="text-lg font-extrabold text-[#202124]">Log Leave Request</h3>
                        <p class="text-xs text-[#5F6368]">Submit leave for Principal review & approval.</p>
                    </div>
                    <button @click="showStaffLeaveModal = false" class="h-8 w-8 rounded-full hover:bg-[#F8F9FA] font-bold text-[#5F6368] flex items-center justify-center"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <form @submit.prevent="submitStaffLeave" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-[#202124] mb-1">Staff Member *</label>
                        <select v-model="staffLeaveForm.user_id" required class="w-full rounded-xl border border-[#DADCE0] px-4 py-2.5 text-xs font-bold">
                            <option value="">-- Choose Faculty / Admin --</option>
                            <option v-for="u in users?.filter(u => ['teacher', 'admin', 'accountant', 'registrar', 'librarian'].includes(u.user_type))" :key="u.id" :value="u.id">{{ u.name }} ({{ u.user_type }})</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-[#202124] mb-1">Leave Category *</label>
                        <select v-model="staffLeaveForm.leave_type" required class="w-full rounded-xl border border-[#DADCE0] px-4 py-2.5 text-xs font-bold uppercase text-[#B06000]">
                            <option value="casual">Casual Leave</option>
                            <option value="sick">Sick / Medical Leave</option>
                            <option value="annual">Annual Leave</option>
                            <option value="maternity">Maternity Leave</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-2xs font-bold text-[#202124] mb-1">Start Date *</label>
                            <input v-model="staffLeaveForm.start_date" type="date" required class="w-full rounded-xl border border-[#DADCE0] px-3 py-2 text-xs font-mono" />
                        </div>
                        <div>
                            <label class="block text-2xs font-bold text-[#202124] mb-1">End Date *</label>
                            <input v-model="staffLeaveForm.end_date" type="date" required class="w-full rounded-xl border border-[#DADCE0] px-3 py-2 text-xs font-mono" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-[#202124] mb-1">Reason for Leave *</label>
                        <textarea v-model="staffLeaveForm.reason" rows="2" required placeholder="Provide clear reason for leave absence..." class="w-full rounded-xl border border-[#DADCE0] p-3 text-xs"></textarea>
                    </div>
                    <div class="flex justify-end space-x-3 pt-4 border-t border-[#DADCE0]">
                        <button type="button" @click="showStaffLeaveModal = false" class="rounded-full px-5 py-2.5 text-xs font-bold text-[#5F6368]">Cancel</button>
                        <button type="submit" :disabled="staffLeaveForm.processing" class="rounded-full bg-[#B06000] px-6 py-2.5 text-xs font-bold text-white shadow-sm disabled:opacity-50 inline-flex items-center gap-1.5"><i class="fa-solid fa-check"></i> <span>Submit Application</span></button>
                    </div>
                </form>
                </div>
            </div>
        </div>

        <!-- ================= MODAL: CREATE EXAM TERM SESSION ================= -->
                <div v-if="showCreateExamModal" class="fixed inset-0 z-50 flex justify-end bg-black/40 backdrop-blur-xs overflow-hidden">
            <div class="absolute inset-0" @click="showCreateExamModal = false"></div>
            <div class="bg-white w-full max-w-md shadow-2xl flex flex-col h-full z-10 animate-slideLeft border-l border-[#DADCE0]">
                <div class="p-6 sm:p-8 flex-1 overflow-y-auto space-y-6">
                    <div class="flex items-center justify-between border-b border-[#DADCE0] pb-4">
                    <div>
                        <h3 class="text-lg font-extrabold text-[#202124]">Create Examination Session</h3>
                        <p class="text-xs text-[#5F6368]">Configure exam term, dates, and publishing status.</p>
                    </div>
                    <button @click="showCreateExamModal = false" class="h-8 w-8 rounded-full hover:bg-[#F8F9FA] font-bold text-[#5F6368] flex items-center justify-center"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <form @submit.prevent="submitExamDefinition" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-[#202124] mb-1">Examination Term Name *</label>
                        <input v-model="examForm.name" type="text" required placeholder="Examination Term Name" class="w-full rounded-xl border border-[#DADCE0] px-4 py-2.5 text-xs font-bold" />
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-2xs font-bold text-[#202124] mb-1">Start Date *</label>
                            <input v-model="examForm.start_date" type="date" required class="w-full rounded-xl border border-[#DADCE0] px-3 py-2 text-xs font-mono" />
                        </div>
                        <div>
                            <label class="block text-2xs font-bold text-[#202124] mb-1">End Date *</label>
                            <input v-model="examForm.end_date" type="date" required class="w-full rounded-xl border border-[#DADCE0] px-3 py-2 text-xs font-mono" />
                        </div>
                    </div>
                    <div class="flex items-center gap-2 pt-1">
                        <input v-model="examForm.is_published" type="checkbox" id="pub_check" class="rounded text-[#8430CE] focus:ring-[#8430CE] h-4 w-4" />
                        <label for="pub_check" class="text-xs font-bold text-[#202124]">Publish Exam Session & Enable Gradebook Entry Immediately</label>
                    </div>
                    <div class="flex justify-end space-x-3 pt-4 border-t border-[#DADCE0]">
                        <button type="button" @click="showCreateExamModal = false" class="rounded-full px-5 py-2.5 text-xs font-bold text-[#5F6368]">Cancel</button>
                    </div>
                </form>
                </div>
            </div>
        </div>

        <!-- ================= MODAL: ADD CLASS ================= -->
                <div v-if="showAddClassModal" class="fixed inset-0 z-50 flex justify-end bg-black/40 backdrop-blur-xs overflow-hidden">
            <div class="absolute inset-0" @click="showAddClassModal = false"></div>
            <div class="bg-white w-full max-w-md shadow-2xl flex flex-col h-full z-10 animate-slideLeft border-l border-[#DADCE0]">
                <div class="p-6 sm:p-8 flex-1 overflow-y-auto space-y-6">
                    <div class="flex items-center justify-between border-b border-[#DADCE0] pb-4">
                    <div>
                        <h3 class="text-lg font-extrabold text-[#202124]">Add Academic Grade Level</h3>
                        <p class="text-xs text-[#5F6368]">Create a new class hierarchy for student enrollment.</p>
                    </div>
                    <button @click="showAddClassModal = false" class="h-8 w-8 rounded-full hover:bg-[#F8F9FA] font-bold text-[#5F6368] flex items-center justify-center"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <form @submit.prevent="submitClass" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-[#202124] mb-1">Class Name *</label>
                        <input v-model="classForm.name" type="text" required placeholder="Class / Grade Name" class="w-full rounded-xl border border-[#DADCE0] px-4 py-2.5 text-xs font-bold" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-[#202124] mb-1">Academic Level Category *</label>
                        <select v-model="classForm.level" required class="w-full rounded-xl border border-[#DADCE0] px-4 py-2.5 text-xs font-bold uppercase">
                            <option value="junior">Junior / Pre-School</option>
                            <option value="primary">Primary (Grades 1-5)</option>
                            <option value="middle">Middle (Grades 6-8)</option>
                            <option value="matric">Matriculation (Grades 9-10)</option>
                            <option value="o_levels">O-Levels / I-GCSE (Years 1-3)</option>
                            <option value="college">Intermediate / FSc (Grades 11-12)</option>
                            <option value="a_levels">A-Levels / AS & A2 (Years 1-2)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-[#202124] mb-1">Numeric Sort Index (0-12) *</label>
                        <input v-model="classForm.numeric_grade" type="number" min="0" max="15" required class="w-full rounded-xl border border-[#DADCE0] px-4 py-2.5 text-xs font-bold" />
                    </div>
                    <div class="flex justify-end space-x-3 pt-4 border-t border-[#DADCE0]">
                        <button type="button" @click="showAddClassModal = false" class="rounded-full px-5 py-2.5 text-xs font-bold text-[#5F6368]">Cancel</button>
                        <button type="submit" :disabled="classForm.processing" class="rounded-full bg-[#1A73E8] px-6 py-2.5 text-xs font-bold text-white shadow-sm disabled:opacity-50 inline-flex items-center gap-1.5"><i class="fa-solid fa-check"></i> <span>Save Class</span></button>
                    </div>
                </form>
                </div>
            </div>
        </div>

        <!-- ================= MODAL: ADD SECTION ================= -->
                <div v-if="showAddSectionModal" class="fixed inset-0 z-50 flex justify-end bg-black/40 backdrop-blur-xs overflow-hidden">
            <div class="absolute inset-0" @click="showAddSectionModal = false"></div>
            <div class="bg-white w-full max-w-md shadow-2xl flex flex-col h-full z-10 animate-slideLeft border-l border-[#DADCE0]">
                <div class="p-6 sm:p-8 flex-1 overflow-y-auto space-y-6">
                    <div class="flex items-center justify-between border-b border-[#DADCE0] pb-4">
                    <div>
                        <h3 class="text-lg font-extrabold text-[#202124]">Create Section</h3>
                        <p class="text-xs text-[#5F6368]">Assign section letter and homeroom teacher.</p>
                    </div>
                    <button @click="showAddSectionModal = false" class="h-8 w-8 rounded-full hover:bg-[#F8F9FA] font-bold text-[#5F6368] flex items-center justify-center"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <form @submit.prevent="submitSection" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-[#202124] mb-1">Section Name *</label>
                        <input v-model="sectionForm.name" type="text" required placeholder="Section Name" class="w-full rounded-xl border border-[#DADCE0] px-4 py-2.5 text-xs font-bold" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-[#202124] mb-1">Room Number / Location</label>
                        <input v-model="sectionForm.room_number" type="text" placeholder="Room Number / Location" class="w-full rounded-xl border border-[#DADCE0] px-4 py-2.5 text-xs font-bold" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-[#202124] mb-1">Class Teacher (Optional)</label>
                        <select v-model="sectionForm.class_teacher_id" class="w-full rounded-xl border border-[#DADCE0] px-4 py-2.5 text-xs font-bold">
                            <option value="">-- No Homeroom Teacher --</option>
                            <option v-for="u in users?.filter(u => u.user_type === 'teacher')" :key="u.id" :value="u.id">{{ u.name }}</option>
                        </select>
                    </div>
                    <div class="flex justify-end space-x-3 pt-4 border-t border-[#DADCE0]">
                        <button type="button" @click="showAddSectionModal = false" class="rounded-full px-5 py-2.5 text-xs font-bold text-[#5F6368]">Cancel</button>
                        <button type="submit" :disabled="sectionForm.processing" class="rounded-full bg-[#1A73E8] px-6 py-2.5 text-xs font-bold text-white shadow-sm disabled:opacity-50 inline-flex items-center gap-1.5"><i class="fa-solid fa-check"></i> <span>Save Section</span></button>
                    </div>
                </form>
                </div>
            </div>
        </div>

        <!-- ================= MODAL: ADD NOTICE / CIRCULAR ================= -->
                <div v-if="showAddNoticeModal" class="fixed inset-0 z-50 flex justify-end bg-black/40 backdrop-blur-xs overflow-hidden">
            <div class="absolute inset-0" @click="showAddNoticeModal = false"></div>
            <div class="bg-white w-full max-w-lg shadow-2xl flex flex-col h-full z-10 animate-slideLeft border-l border-[#DADCE0]">
                <div class="p-6 sm:p-8 flex-1 overflow-y-auto space-y-6">
                    <div class="flex items-center justify-between border-b border-[#DADCE0] pb-4">
                    <div>
                        <h3 class="text-lg font-extrabold text-[#202124]">Publish School Notice</h3>
                        <p class="text-xs text-[#5F6368]">Broadcast circular across student, parent, and teacher portals.</p>
                    </div>
                    <button @click="showAddNoticeModal = false" class="h-8 w-8 rounded-full hover:bg-[#F8F9FA] font-bold text-[#5F6368] flex items-center justify-center"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <form @submit.prevent="submitNotice" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-[#202124] mb-1">Notice Title *</label>
                        <input v-model="noticeForm.title" type="text" required placeholder="Notice Title" class="w-full rounded-xl border border-[#DADCE0] px-4 py-2.5 text-xs font-bold" />
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-[#202124] mb-1">Target Audience *</label>
                            <select v-model="noticeForm.target_audience" required class="w-full rounded-xl border border-[#DADCE0] px-3 py-2.5 text-xs font-bold uppercase">
                                <option value="all">All School (Staff + Students + Parents)</option>
                                <option value="teachers">Faculty & Staff Only</option>
                                <option value="students">Students & Parents Only</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-[#202124] mb-1">Category</label>
                            <input v-model="noticeForm.category" type="text" placeholder="Notice Category" class="w-full rounded-xl border border-[#DADCE0] px-3 py-2.5 text-xs font-bold" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-[#202124] mb-1">Notice Content / Announcement Details *</label>
                        <textarea v-model="noticeForm.content" rows="4" required placeholder="Enter full circular announcement text..." class="w-full rounded-xl border border-[#DADCE0] p-3 text-xs font-medium"></textarea>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 bg-[#E8F0FE]/40 p-3 rounded-xl border border-[#D2E3FC]">
                        <div>
                            <label class="block text-2xs font-bold text-[#1A73E8] mb-1"><i class="fa-solid fa-video mr-1"></i> Video URL / YouTube Link</label>
                            <input v-model="noticeForm.video_url" type="url" placeholder="https://www.youtube.com/watch?v=..." class="w-full rounded-xl bg-white border border-[#DADCE0] px-3 py-2 text-xs font-mono" />
                        </div>
                        <div>
                            <label class="block text-2xs font-bold text-[#1A73E8] mb-1"><i class="fa-solid fa-file-video mr-1"></i> Or Upload MP4/WebM Video</label>
                            <input @input="noticeForm.video_file = $event.target.files[0]" type="file" accept="video/mp4,video/webm,video/quicktime" class="w-full rounded-xl bg-white border border-[#DADCE0] p-1.5 text-2xs font-semibold" />
                        </div>
                    </div>
                    <div class="flex items-center gap-4 pt-1 bg-[#F8F9FA] p-3 rounded-xl border border-[#E8EAED]">
                        <div class="flex items-center gap-1.5">
                            <input v-model="noticeForm.send_sms_alert" type="checkbox" id="sms_check" class="rounded text-[#1A73E8] focus:ring-[#1A73E8]" />
                            <label for="sms_check" class="text-2xs font-bold text-[#202124]">Trigger SMS Alert</label>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <input v-model="noticeForm.send_whatsapp_alert" type="checkbox" id="wa_check" class="rounded text-[#137333] focus:ring-[#137333]" />
                            <label for="wa_check" class="text-2xs font-bold text-[#202124]">Trigger WhatsApp Cloud API Alert</label>
                        </div>
                    </div>
                    <div class="flex justify-end space-x-3 pt-4 border-t border-[#DADCE0]">
                        <button type="button" @click="showAddNoticeModal = false" class="rounded-full px-5 py-2.5 text-xs font-bold text-[#5F6368]">Cancel</button>
                        <button type="submit" :disabled="noticeForm.processing" class="rounded-full bg-[#1A73E8] px-6 py-2.5 text-xs font-bold text-white shadow-sm disabled:opacity-50 inline-flex items-center gap-1.5"><i class="fa-solid fa-check"></i> <span>Publish Broadcast</span></button>
                    </div>
                </form>
                </div>
            </div>
        </div>

        <!-- ================= MODAL: CREATE / EDIT SPORTS ACTIVITY ================= -->
                <div v-if="showSportsModal" class="fixed inset-0 z-50 flex justify-end bg-black/40 backdrop-blur-xs overflow-hidden">
            <div class="absolute inset-0" @click="showSportsModal = false"></div>
            <div class="bg-white w-full max-w-lg shadow-2xl flex flex-col h-full z-10 animate-slideLeft border-l border-[#DADCE0]">
                <div class="p-6 sm:p-8 flex-1 overflow-y-auto space-y-6">
                    <div class="flex items-center justify-between border-b border-[#DADCE0] pb-4">
                    <h3 class="text-lg font-extrabold text-[#202124] flex items-center gap-2">
                        <i class="fa-solid fa-trophy text-[#F59E0B]"></i>
                        <span>{{ editingSports ? 'Edit Extracurricular Activity' : 'Create Extracurricular / Sports Team' }}</span>
                    </h3>
                    <button @click="showSportsModal = false" class="text-[#5F6368] hover:text-[#202124]"><i class="fa-solid fa-xmark text-lg"></i></button>
                </div>

                <form @submit.prevent="submitSportsForm" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-[#202124] mb-1">Activity / Team Name *</label>
                        <input v-model="sportsForm.name" type="text" required placeholder="Activity / Team Name" class="w-full rounded-xl border border-[#DADCE0] px-3 py-2 text-xs font-bold" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-[#202124] mb-1">Category</label>
                            <select v-model="sportsForm.category" class="w-full rounded-xl border border-[#DADCE0] px-3 py-2 text-xs font-bold">
                                <option value="Sports">Sports</option>
                                <option value="Debate">Debate</option>
                                <option value="Arts">Arts</option>
                                <option value="STEM">STEM & Robotics</option>
                                <option value="Literature">Literature</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-[#202124] mb-1">Max Roster Capacity</label>
                            <input v-model="sportsForm.max_participants" type="number" min="1" max="500" class="w-full rounded-xl border border-[#DADCE0] px-3 py-2 text-xs font-bold" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-[#202124] mb-1">Schedule / Day & Time</label>
                            <input v-model="sportsForm.schedule" type="text" placeholder="Schedule / Day & Time" class="w-full rounded-xl border border-[#DADCE0] px-3 py-2 text-xs font-bold" />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-[#202124] mb-1">Venue / Location</label>
                            <input v-model="sportsForm.venue" type="text" placeholder="Venue / Location" class="w-full rounded-xl border border-[#DADCE0] px-3 py-2 text-xs font-bold" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-[#202124] mb-1">Assign Faculty Coach / Supervisor</label>
                        <select v-model="sportsForm.coach_user_id" class="w-full rounded-xl border border-[#DADCE0] px-3 py-2 text-xs font-bold">
                            <option value="">-- Unassigned --</option>
                            <option v-for="u in users?.filter(usr => usr.user_type === 'teacher' || usr.user_type === 'staff')" :key="u.id" :value="u.id">
                                {{ u.name }} ({{ u.user_type }})
                            </option>
                        </select>
                    </div>
                    <div class="flex justify-end space-x-3 pt-4 border-t border-[#DADCE0]">
                        <button type="button" @click="showSportsModal = false" class="rounded-full px-5 py-2 text-xs font-bold text-[#5F6368]">Cancel</button>
                        <button type="submit" :disabled="sportsForm.processing" class="rounded-full bg-[#1A73E8] px-6 py-2 text-xs font-bold text-white shadow-sm disabled:opacity-50 inline-flex items-center gap-1.5">
                            <i class="fa-solid fa-check"></i>
                            <span>{{ editingSports ? 'Update Activity' : 'Create Activity' }}</span>
                        </button>
                    </div>
                </form>
                </div>
            </div>
        </div>

        <!-- ================= MODAL: ISSUE STUDENT PENALTY / FINE ================= -->
                <div v-if="showPenaltyModal" class="fixed inset-0 z-50 flex justify-end bg-black/40 backdrop-blur-xs overflow-hidden">
            <div class="absolute inset-0" @click="showPenaltyModal = false"></div>
            <div class="bg-white w-full max-w-lg shadow-2xl flex flex-col h-full z-10 animate-slideLeft border-l border-[#DADCE0]">
                <div class="p-6 sm:p-8 flex-1 overflow-y-auto space-y-6">
                    <div class="flex items-center justify-between border-b border-[#DADCE0] pb-4">
                    <h3 class="text-lg font-extrabold text-[#202124] flex items-center gap-2">
                        <i class="fa-solid fa-gavel text-[#E11D48]"></i>
                        <span>Issue Disciplinary Penalty / Fine</span>
                    </h3>
                    <button @click="showPenaltyModal = false" class="text-[#5F6368] hover:text-[#202124]"><i class="fa-solid fa-xmark text-lg"></i></button>
                </div>

                <form @submit.prevent="submitPenaltyForm" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-[#202124] mb-1">Select Student *</label>
                        <select v-model="penaltyForm.student_id" required class="w-full rounded-xl border border-[#DADCE0] px-3 py-2 text-xs font-bold">
                            <option value="" disabled>-- Select Student --</option>
                            <option v-for="s in students" :key="s.id" :value="s.id">
                                {{ s.user?.name || 'Student' }} (Roll: {{ s.roll_number || 'N/A' }}, Class: {{ s.current_class?.name || 'N/A' }})
                            </option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-[#202124] mb-1">Violation Type *</label>
                            <select v-model="penaltyForm.penalty_type" required class="w-full rounded-xl border border-[#DADCE0] px-3 py-2 text-xs font-bold">
                                <option value="Disciplinary Violation">Disciplinary Violation</option>
                                <option value="Library Book Damage/Loss">Library Book Damage/Loss</option>
                                <option value="Late Return Fine">Late Return Fine</option>
                                <option value="Property Damage">Property Damage</option>
                                <option value="Uniform / Dress Code">Uniform / Dress Code</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-[#202124] mb-1">Amount (PKR)</label>
                            <input v-model="penaltyForm.amount" type="number" min="0" step="50" class="w-full rounded-xl border border-[#DADCE0] px-3 py-2 text-xs font-bold" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-[#202124] mb-1">Detailed Reason / Incident Summary *</label>
                        <textarea v-model="penaltyForm.reason" rows="3" required placeholder="Describe the violation or incident details..." class="w-full rounded-xl border border-[#DADCE0] p-3 text-xs font-medium"></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-[#202124] mb-1">Due Date for Settlement</label>
                        <input v-model="penaltyForm.due_date" type="date" class="w-full rounded-xl border border-[#DADCE0] px-3 py-2 text-xs font-bold" />
                    </div>
                    <div class="flex justify-end space-x-3 pt-4 border-t border-[#DADCE0]">
                        <button type="button" @click="showPenaltyModal = false" class="rounded-full px-5 py-2 text-xs font-bold text-[#5F6368]">Cancel</button>
                        <button type="submit" :disabled="penaltyForm.processing" class="rounded-full bg-[#E11D48] px-6 py-2 text-xs font-bold text-white shadow-sm disabled:opacity-50 inline-flex items-center gap-1.5">
                            <i class="fa-solid fa-gavel"></i>
                            <span>Issue Penalty</span>
                        </button>
                    </div>
                </form>
                </div>
            </div>
        </div>
    </TenantAppLayout>
</template>
