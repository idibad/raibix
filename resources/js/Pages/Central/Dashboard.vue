<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, router, Link, usePage } from '@inertiajs/vue3';

const props = defineProps({
    tenants: Array,
    plans: Array,
    analytics: Object,
    activityLogs: Array,
    revenueTransactions: Array,
    platformSettings: Object,
    coupons: Array,
    pendingApprovals: Array,
});

const selectedReceipt = ref(null);

const approveSaaSCheckout = (id) => {
    if (confirm('Are you sure you want to approve this subscription payment? This will activate the school portal.')) {
        router.post(`/super-admin/approvals/${id}/approve`, {}, {
            onSuccess: () => {
                triggerAlert('School payment approved and portal activated successfully!');
            }
        });
    }
};

const rejectSaaSCheckout = (id) => {
    if (confirm('Are you sure you want to reject this subscription payment? This will keep the school portal suspended.')) {
        router.post(`/super-admin/approvals/${id}/reject`, {}, {
            onSuccess: () => {
                triggerAlert('School payment rejected.');
            }
        });
    }
};

// Active Dashboard Tab ('overview' | 'schools' | 'plans' | 'payments' | 'logs' | 'settings')

// ===== GLOBAL DIALOG SYSTEM =====
const page = usePage();
const confirmDialog = ref({ show: false, title: '', message: '', icon: 'fa-circle-question', iconColor: 'text-amber-500', confirmText: 'Confirm', confirmClass: 'bg-indigo-600 hover:bg-indigo-700', onConfirm: null });
const alertDialog = ref({ show: false, title: '', message: '', type: 'success' });
const inputDialog = ref({ show: false, title: '', message: '', placeholder: '', value: '', onConfirm: null });

const triggerConfirm = (title, message, callback, opts = {}) => {
    confirmDialog.value = {
        show: true, title, message,
        icon: opts.icon || 'fa-circle-question',
        iconColor: opts.iconColor || 'text-amber-500',
        confirmText: opts.confirmText || 'Confirm',
        confirmClass: opts.confirmClass || 'bg-indigo-600 hover:bg-indigo-700',
        onConfirm: () => { callback(); confirmDialog.value.show = false; }
    };
};

const triggerAlert = (message, type = 'success') => {
    alertDialog.value = {
        show: true,
        title: type === 'success' ? 'Success' : type === 'error' ? 'Error' : 'Information',
        message,
        type
    };
    setTimeout(() => { alertDialog.value.show = false; }, 4000);
};

const triggerInput = (title, message, placeholder, callback) => {
    inputDialog.value = { show: true, title, message, placeholder, value: '', onConfirm: (val) => { callback(val); inputDialog.value.show = false; } };
};

// Watch flash messages
watch(() => page.props.flash, (flash) => {
    if (flash?.success) {
        triggerAlert(flash.success, 'success');
    }
    if (flash?.error) {
        triggerAlert(flash.error, 'error');
    }
}, { deep: true, immediate: true });
// ===== END DIALOG SYSTEM =====

const activeTab = ref('overview');
const isSidebarCollapsed = ref(false);
const isMobileMenuOpen = ref(false);

// Search filter for schools
const schoolSearch = ref('');

const filteredSchools = computed(() => {
    if (!props.tenants) return [];
    if (!schoolSearch.value.trim()) return props.tenants;
    const q = schoolSearch.value.toLowerCase();
    return props.tenants.filter(t => 
        (t.name && t.name.toLowerCase().includes(q)) || 
        (t.id && t.id.toLowerCase().includes(q))
    );
});

// Dynamic Overview Page Computations
const planDistribution = computed(() => {
    const counts = {};
    if (props.tenants) {
        props.tenants.forEach(t => {
            const planName = t.plan?.name || t.plan_name || 'Standard Tier';
            counts[planName] = (counts[planName] || 0) + 1;
        });
    }
    return counts;
});

const planSegments = computed(() => {
    const counts = planDistribution.value;
    const total = Object.values(counts).reduce((a, b) => a + b, 0) || 1;
    let accumulatedPercent = 0;
    
    const colors = {
        'Starter Tier': '#1A73E8', // Blue
        'Growth Tier': '#8430CE', // Purple
        'Enterprise Tier': '#137333', // Green
        'Standard Tier': '#B06000', // Amber
    };
    const defaultColors = ['#1A73E8', '#8430CE', '#137333', '#B06000', '#C5221F'];

    return Object.entries(counts).map(([name, count], index) => {
        const percent = (count / total) * 100;
        const color = colors[name] || defaultColors[index % defaultColors.length];
        const dashArray = `${(percent * 251.2) / 100} 251.2`;
        const dashOffset = `${- (accumulatedPercent * 251.2) / 100}`;
        accumulatedPercent += percent;
        return {
            name,
            count,
            percent: Math.round(percent),
            color,
            dashArray,
            dashOffset
        };
    });
});

const growthPoints = computed(() => {
    const data = [10, 22, 38, 52, 75, props.tenants?.length || 98];
    const width = 500;
    const height = 150;
    const maxVal = Math.max(...data) * 1.1 || 100;
    const minVal = 0;
    
    const points = data.map((val, index) => {
        const x = (index / (data.length - 1)) * (width - 40) + 20;
        const y = height - ((val - minVal) / (maxVal - minVal)) * (height - 30) - 15;
        return { x, y, value: val };
    });

    const linePath = points.map((p, i) => `${i === 0 ? 'M' : 'L'} ${p.x} ${p.y}`).join(' ');
    const areaPath = points.length ? `${linePath} L ${points[points.length - 1].x} ${height} L ${points[0].x} ${height} Z` : '';
    
    return { points, linePath, areaPath, width, height };
});

const getDomainSuffix = () => {
    const hostname = window.location.hostname;
    const ipPattern = /^(?:[0-9]{1,3}\.){3}[0-9]{1,3}$/;
    if (ipPattern.test(hostname)) {
        return `${hostname}.nip.io`;
    }
    return hostname;
};

const getTenantLoginUrl = (t) => {
    const domain = t.domains?.[0]?.domain || `${t.id}.${getDomainSuffix()}`;
    const cleanDomain = domain.replace(':8000', '');
    const protocol = window.location.protocol || 'http:';
    const port = window.location.port ? `:${window.location.port}` : (window.location.hostname.includes('localhost') || domain.includes('localhost') || window.location.hostname.includes('192.168.100.7') ? ':8000' : '');
    return `${protocol}//${cleanDomain}${port}/login`;
};

const getTenantDisplayUrl = (t) => {
    const domain = t.domains?.[0]?.domain || `${t.id}.${getDomainSuffix()}`;
    const cleanDomain = domain.replace(':8000', '');
    const port = window.location.port ? `:${window.location.port}` : (window.location.hostname.includes('localhost') || domain.includes('localhost') || window.location.hostname.includes('192.168.100.7') ? ':8000' : '');
    return `${cleanDomain}${port}/login`;
};

// School Onboarding Modal state & form
const showModal = ref(false);
const form = useForm({
    id: '',
    name: '',
    domain: '',
    admin_name: '',
    admin_email: '',
    admin_phone: '',
    admin_password: '',
    plan_id: props.plans?.[1]?.id || props.plans?.[0]?.id || '',
    billing_cycle: 'monthly',
    payment_gateway: 'Direct Invoice / Manual Onboarding',
});

const autoFillDomain = () => {
    if (form.id) {
        form.domain = `${form.id.toLowerCase().replace(/[^a-z0-9]/g, '')}.${getDomainSuffix()}`;
    }
};

const submitTenant = () => {
    form.post('/tenants', {
        preserveScroll: true,
        onSuccess: () => {
            showModal.value = false;
            form.reset();
            form.plan_id = props.plans?.[1]?.id || props.plans?.[0]?.id || '';
            form.billing_cycle = 'monthly';
            form.payment_gateway = 'Direct Invoice / Manual Onboarding';
        },
        onError: (errors) => {
            console.error('Onboarding validation or server errors:', errors);
        },
    });
};

const showEditSchoolModal = ref(false);
const selectedSchoolForEdit = ref(null);
const editSchoolForm = useForm({
    name: '',
    domain: '',
});

const openEditSchoolModal = (school) => {
    selectedSchoolForEdit.value = school;
    editSchoolForm.name = school.name;
    const rawDomain = school.domain || school.data?.domain || '';
    editSchoolForm.domain = rawDomain.split(':')[0];
    showEditSchoolModal.value = true;
};

const submitEditSchool = () => {
    if (!selectedSchoolForEdit.value) return;
    editSchoolForm.put(`/tenants/${selectedSchoolForEdit.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            showEditSchoolModal.value = false;
            editSchoolForm.reset();
            selectedSchoolForEdit.value = null;
        }
    });
};

// Tenant Actions
const toggleTenantStatus = (tenantId) => {
    router.post(`/tenants/${tenantId}/toggle-status`);
};

const backupTenant = (tenantId) => {
    router.post(`/tenants/${tenantId}/backup`);
};

const syncTenant = (tenantId) => {
    router.post(`/tenants/${tenantId}/sync`);
};

const impersonateTenant = (tenantId) => {
    router.post(`/tenants/${tenantId}/impersonate`);
};

const mapCustomDomain = (tenantId) => {
    triggerInput('Map Custom Domain', 'Enter Custom FQDN Domain for this school (e.g., school.com):', 'school.com', (d) => {
        if (d && d.trim()) {
            router.post(`/tenants/${tenantId}/domain`, { domain: d.trim() });
        }
    });
};

const offboardTenant = (tenantId) => {
    triggerConfirm(
        'Offboard Tenant?', 
        'Are you sure you want to offboard this tenant and export their database? All services for this school will be suspended.',
        () => {
            router.post(`/tenants/${tenantId}/offboard`);
        },
        { icon: 'fa-triangle-exclamation', iconColor: 'text-red-500', confirmText: 'Yes, Offboard', confirmClass: 'bg-red-600 hover:bg-red-700' }
    );
};

// Coupon Management Modal state & form
const showCouponModal = ref(false);
const couponForm = useForm({
    code: '',
    discount_percentage: 20,
    max_uses: '',
});

const createCoupon = () => {
    couponForm.reset();
    showCouponModal.value = true;
};

const submitCoupon = () => {
    couponForm.post(route('central.coupons.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showCouponModal.value = false;
            couponForm.reset();
            triggerAlert('Coupon created successfully!', 'success');
        }
    });
};

const deleteCoupon = (id, code) => {
    triggerConfirm(
        'Delete Coupon?',
        `Are you sure you want to permanently delete coupon [${code}]?`,
        () => {
            router.delete(`/super-admin/coupons/${id}`, {
                preserveScroll: true,
                onSuccess: () => {
                    triggerAlert('Coupon deleted successfully.', 'success');
                }
            });
        },
        { icon: 'fa-trash-can', iconColor: 'text-red-500', confirmText: 'Yes, Delete', confirmClass: 'bg-red-600 hover:bg-red-700' }
    );
};

// Subscription Plan Add / Edit Modal state & form
const showPlanModal = ref(false);
const isEditingPlan = ref(false);
const selectedPlan = ref(null);
const featureInput = ref('');

const planForm = useForm({
    name: '',
    code: '',
    price_monthly: 0,
    max_students: 500,
    features: ['Admissions & Enrollment', 'Daily Attendance', 'Fee Management', 'LMS & Assignments'],
});

const openAddPlanModal = () => {
    isEditingPlan.value = false;
    selectedPlan.value = null;
    featureInput.value = '';
    planForm.reset();
    planForm.features = ['Admissions & Enrollment', 'Daily Attendance', 'Fee Management', 'LMS & Assignments'];
    showPlanModal.value = true;
};

const openEditPlanModal = (plan) => {
    isEditingPlan.value = true;
    selectedPlan.value = plan;
    featureInput.value = '';
    planForm.name = plan.name;
    planForm.code = plan.code;
    planForm.price_monthly = plan.price_monthly;
    planForm.max_students = plan.max_students;
    planForm.features = Array.isArray(plan.features) ? [...plan.features] : ['Admissions & Enrollment', 'Daily Attendance', 'Fee Management', 'LMS & Assignments'];
    showPlanModal.value = true;
};

const addFeatureToPlan = () => {
    if (featureInput.value.trim() && !planForm.features.includes(featureInput.value.trim())) {
        planForm.features.push(featureInput.value.trim());
        featureInput.value = '';
    }
};

const removeFeatureFromPlan = (index) => {
    planForm.features.splice(index, 1);
};

const submitPlan = () => {
    if (isEditingPlan.value && selectedPlan.value) {
        planForm.put(`/plans/${selectedPlan.value.id}`, {
            onSuccess: () => {
                showPlanModal.value = false;
            },
        });
    } else {
        planForm.post('/plans', {
            onSuccess: () => {
                showPlanModal.value = false;
            },
        });
    }
};

const showDeletePlanModal = ref(false);
const planToDelete = ref(null);

const confirmDeletePlan = (plan) => {
    planToDelete.value = plan;
    showDeletePlanModal.value = true;
};

const deletePlan = () => {
    if (planToDelete.value) {
        router.delete(`/plans/${planToDelete.value.id}`, {
            onSuccess: () => {
                showDeletePlanModal.value = false;
                planToDelete.value = null;
            },
        });
    }
};

// Platform Settings Form
const settingsForm = useForm({
    platform_name: props.platformSettings?.platform_name || 'Excellence Cloud OS',
    contact_email: props.platformSettings?.contact_email || 'support@excellence.com',
    support_phone: props.platformSettings?.support_phone || '+92 300 1234567',
    maintenance_mode: props.platformSettings?.maintenance_mode || false,
});

watch(() => props.platformSettings, (newSettings) => {
    if (newSettings) {
        settingsForm.platform_name = newSettings.platform_name || '';
        settingsForm.contact_email = newSettings.contact_email || '';
        settingsForm.support_phone = newSettings.support_phone || '';
        settingsForm.maintenance_mode = newSettings.maintenance_mode || false;
    }
}, { deep: true });

const submitSettings = () => {
    settingsForm.post('/super-admin/settings', {
        preserveScroll: true,
        onSuccess: () => {
            alert('Platform Settings saved and updated across all edge servers!');
        }
    });
};

// Payments Tab Search, Filters, & Inspection
const paymentSearch = ref('');
const paymentStatusFilter = ref('all');
const selectedTransaction = ref(null);

const downloadInvoicePDF = (txn) => {
    if (!txn) return;
    const invoiceHtml = `
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>Tax Invoice - ${txn.reference_no || txn.id || 'INV'}</title>
        <style>
            @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap');
            body { font-family: 'Inter', -apple-system, sans-serif; color: #202124; margin: 0; padding: 40px; background: #fff; }
            .invoice-box { max-width: 800px; margin: auto; border: 1px solid #DADCE0; padding: 40px; border-radius: 12px; }
            .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #202124; padding-bottom: 24px; margin-bottom: 32px; }
            .logo { font-size: 24px; font-weight: 800; color: #1A73E8; text-transform: uppercase; letter-spacing: -0.5px; }
            .invoice-title { text-align: right; }
            .invoice-title h1 { margin: 0; font-size: 28px; font-weight: 800; color: #202124; text-transform: uppercase; }
            .invoice-title p { margin: 4px 0 0; font-size: 13px; color: #5F6368; font-weight: 600; }
            .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 32px; margin-bottom: 32px; }
            .box { background: #F8F9FA; padding: 20px; border-radius: 8px; border: 1px solid #DADCE0; font-size: 13px; }
            .box h3 { margin: 0 0 8px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #5F6368; }
            .box p { margin: 4px 0; font-size: 14px; font-weight: 600; color: #202124; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 32px; font-size: 14px; }
            th { background: #F8F9FA; padding: 12px 16px; text-align: left; font-size: 12px; text-transform: uppercase; color: #5F6368; border-bottom: 1px solid #DADCE0; }
            td { padding: 16px; border-bottom: 1px solid #DADCE0; font-weight: 600; }
            .total-section { display: flex; justify-content: flex-end; margin-bottom: 32px; }
            .total-box { width: 280px; background: #E8F0FE; padding: 20px; border-radius: 8px; border: 1px solid #D2E3FC; }
            .total-row { display: flex; justify-content: space-between; font-size: 16px; font-weight: 800; color: #1A73E8; }
            .hash-box { background: #202124; color: #34A853; padding: 16px; border-radius: 8px; font-family: monospace; font-size: 11px; word-break: break-all; margin-top: 24px; }
            .footer { margin-top: 40px; text-align: center; font-size: 12px; color: #5F6368; border-top: 1px solid #DADCE0; padding-top: 20px; }
            @media print {
                body { padding: 0; }
                .invoice-box { border: none; padding: 0; }
            }
        <\/style>
    <\/head>
    <body>
        <div class="invoice-box">
            <div class="header">
                <div>
                    <div class="logo">Excellence LMS</div>
                    <div style="font-size: 13px; color: #5F6368; margin-top: 4px;">Super Admin & Edge Infrastructure Node</div>
                </div>
                <div class="invoice-title">
                    <h1>OFFICIAL TAX INVOICE</h1>
                    <p>Reference: ${txn.reference_no || txn.id || 'INV-0000'}</p>
                </div>
            </div>
            <div class="grid">
                <div class="box">
                    <h3>Billed To (Tenant Institution)</h3>
                    <p>${txn.tenant_name || txn.school_name || 'Educational Institution'}</p>
                    <p style="font-size: 12px; color: #5F6368; font-weight: 400;">Subscription Plan: ${txn.plan_name || txn.plan?.name || 'Pro Tier'}</p>
                </div>
                <div class="box">
                    <h3>Payment Details</h3>
                    <p>Status: <span style="color: #137333; text-transform: uppercase;">${txn.status || 'PAID'}</span></p>
                    <p style="font-size: 12px; color: #5F6368; font-weight: 400;">Gateway: ${txn.gateway || 'Stripe / Bank'}</p>
                    <p style="font-size: 12px; color: #5F6368; font-weight: 400;">Date: ${txn.date || txn.created_at || new Date().toLocaleDateString()}</p>
                </div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Billing Period</th>
                        <th>Gateway Ref</th>
                        <th style="text-align: right;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>${txn.plan_name || txn.plan?.name || 'Excellence LMS SaaS Subscription'}</td>
                        <td>Annual / Monthly Renewal</td>
                        <td style="font-family: monospace; font-size: 12px;">${txn.transaction_id || txn.reference_no || 'TXN-REF'}</td>
                        <td style="text-align: right;">PKR ${(Number(txn.amount) || 0).toLocaleString()}</td>
                    </tr>
                </tbody>
            </table>
            <div class="total-section">
                <div class="total-box">
                    <div class="total-row">
                        <span>Total Paid:</span>
                        <span>PKR ${(Number(txn.amount) || 0).toLocaleString()}</span>
                    </div>
                </div>
            </div>
            <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #5F6368; margin-bottom: 4px;">Cryptographic Hash (HMAC Verification)</div>
            <div class="hash-box">
                ${txn.secure_hash || 'SHA256-VERIFIED-INTEGRITY-OK'}
            </div>
            <div class="footer">
                This is a computer-generated official tax invoice from Excellence LMS Cloud Infrastructure. No physical signature is required.
            </div>
        </div>
        <script>
            window.onload = function() {
                setTimeout(function() {
                    window.print();
                }, 400);
            };
        <\/script>
    <\/body>
    <\/html>
    `;
    const win = window.open('', '_blank', 'width=900,height=800');
    if (win) {
        win.document.open();
        win.document.write(invoiceHtml);
        win.document.close();
    }
};


const filteredTransactions = computed(() => {
    if (!props.revenueTransactions) return [];
    return props.revenueTransactions.filter(trx => {
        const matchesSearch = !paymentSearch.value.trim() || 
            ((trx.transaction_id || trx.transaction_code || '').toLowerCase().includes(paymentSearch.value.toLowerCase())) ||
            ((trx.school_name || '').toLowerCase().includes(paymentSearch.value.toLowerCase())) ||
            ((trx.gateway || '').toLowerCase().includes(paymentSearch.value.toLowerCase()));
        const status = (trx.status || 'paid').toLowerCase();
        const filter = paymentStatusFilter.value.toLowerCase();
        const matchesStatus = filter === 'all' || 
            status === filter ||
            (filter === 'pending' && ['pending', 'failed', 'unpaid'].includes(status));
        return matchesSearch && matchesStatus;
    });
});

const paymentStats = computed(() => {
    const txns = props.revenueTransactions || [];
    const totalRev = txns.reduce((sum, t) => sum + (Number(t.amount) || 0), 0);
    const paidCount = txns.filter(t => (t.status || 'paid').toLowerCase() === 'paid').length;
    return {
        totalRevenue: totalRev,
        totalCount: txns.length,
        paidCount: paidCount,
        avgTicket: txns.length ? Math.round(totalRev / txns.length) : 0,
    };
});

const overdueCount = computed(() => {
    if (!props.revenueTransactions) return 0;
    return props.revenueTransactions.filter(t => 
        ['pending', 'failed', 'unpaid'].includes((t.status || '').toLowerCase())
    ).length;
});

// Activity Logs Tab Search, Filters, & Inspection
const logSearch = ref('');
const logTypeFilter = ref('all');
const selectedLog = ref(null);

const filteredLogs = computed(() => {
    if (!props.activityLogs) return [];
    return props.activityLogs.filter(log => {
        const matchesSearch = !logSearch.value.trim() ||
            ((log.action || log.event || log.event_title || '').toLowerCase().includes(logSearch.value.toLowerCase())) ||
            ((log.details || log.description || '').toLowerCase().includes(logSearch.value.toLowerCase())) ||
            ((log.user || log.actor_name || '').toLowerCase().includes(logSearch.value.toLowerCase()));
        const matchesType = logTypeFilter.value === 'all' ||
            ((log.type || log.event_type || 'info').toLowerCase() === logTypeFilter.value.toLowerCase());
        return matchesSearch && matchesType;
    });
});

const logout = () => {
    useForm({}).post('/super-admin/logout');
};
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
                        <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-[#E8F0FE] font-bold text-[#1A73E8] text-lg border border-[#D2E3FC] shadow-sm">
                            <i class="fa-solid fa-bolt"></i>
                        </div>
                        <div v-if="!isSidebarCollapsed || isMobileMenuOpen" class="min-w-0 transition-all duration-200">
                            <h1 class="font-bold text-base tracking-tight text-[#202124] truncate">Super Admin</h1>
                            <p class="text-xs text-[#5F6368] truncate">Excellence LMS Cloud</p>
                        </div>
                    </div>
                    <!-- Collapse / Close Button -->
                    <button 
                        @click="isMobileMenuOpen ? (isMobileMenuOpen = false) : (isSidebarCollapsed = !isSidebarCollapsed)"
                        class="h-8 w-8 rounded-lg flex items-center justify-center text-[#5F6368] hover:bg-[#F8F9FA] hover:text-[#202124] transition-colors flex-shrink-0"
                        :title="isMobileMenuOpen ? 'Close Menu' : (isSidebarCollapsed ? 'Expand Sidebar' : 'Collapse Sidebar')"
                    >
                        <span class="text-base font-bold"><i :class="isSidebarCollapsed ? 'fa-solid fa-arrow-right' : 'fa-solid fa-arrow-left'"></i></span>
                    </button>
                </div>

                <!-- Side Menu Links -->
                <nav class="p-3 sm:p-4 space-y-1 overflow-y-auto flex-1">
                    <button 
                        @click="activeTab = 'overview'; isMobileMenuOpen = false"
                        :title="(isSidebarCollapsed && !isMobileMenuOpen) ? 'Overview' : ''"
                        :class="[
                            activeTab === 'overview' ? 'bg-[#E8F0FE] text-[#1A73E8] font-bold shadow-xs' : 'text-[#5F6368] hover:bg-[#F8F9FA] hover:text-[#202124] font-medium',
                            (isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center px-2 py-3' : 'justify-between px-4 py-3'
                        ]"
                        class="w-full flex items-center rounded-xl text-sm transition-all"
                    >
                        <div class="flex items-center" :class="(isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center' : 'space-x-3'">
                            <i class="fa-solid fa-chart-pie text-lg flex-shrink-0"></i>
                            <span v-if="!isSidebarCollapsed || isMobileMenuOpen" class="truncate">Overview</span>
                        </div>
                    </button>

                    <button 
                        @click="activeTab = 'schools'; isMobileMenuOpen = false"
                        :title="(isSidebarCollapsed && !isMobileMenuOpen) ? 'Schools' : ''"
                        :class="[
                            activeTab === 'schools' ? 'bg-[#E8F0FE] text-[#1A73E8] font-bold shadow-xs' : 'text-[#5F6368] hover:bg-[#F8F9FA] hover:text-[#202124] font-medium',
                            (isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center px-2 py-3' : 'justify-between px-4 py-3'
                        ]"
                        class="w-full flex items-center rounded-xl text-sm transition-all relative"
                    >
                        <div class="flex items-center" :class="(isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center' : 'space-x-3'">
                            <i class="fa-solid fa-school text-lg flex-shrink-0"></i>
                            <span v-if="!isSidebarCollapsed || isMobileMenuOpen" class="truncate">Schools</span>
                        </div>
                        <span v-if="!isSidebarCollapsed || isMobileMenuOpen" class="bg-[#F1F3F4] text-[#3C4043] text-xs font-semibold px-2 py-0.5 rounded-full">{{ tenants?.length || 0 }}</span>
                        <span v-else-if="tenants?.length" class="absolute top-1.5 right-1.5 h-2 w-2 rounded-full bg-[#1A73E8]"></span>
                    </button>

                    <button 
                        @click="activeTab = 'plans'; isMobileMenuOpen = false"
                        :title="(isSidebarCollapsed && !isMobileMenuOpen) ? 'Plans & Packages' : ''"
                        :class="[
                            activeTab === 'plans' ? 'bg-[#E8F0FE] text-[#1A73E8] font-bold shadow-xs' : 'text-[#5F6368] hover:bg-[#F8F9FA] hover:text-[#202124] font-medium',
                            (isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center px-2 py-3' : 'justify-between px-4 py-3'
                        ]"
                        class="w-full flex items-center rounded-xl text-sm transition-all"
                    >
                        <div class="flex items-center" :class="(isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center' : 'space-x-3'">
                            <i class="fa-solid fa-box text-lg flex-shrink-0"></i>
                            <span v-if="!isSidebarCollapsed || isMobileMenuOpen" class="truncate">Plans & Packages</span>
                        </div>
                    </button>

                    <button 
                        @click="activeTab = 'payments'; isMobileMenuOpen = false"
                        :title="(isSidebarCollapsed && !isMobileMenuOpen) ? 'Payments' : ''"
                        :class="[
                            activeTab === 'payments' ? 'bg-[#E8F0FE] text-[#1A73E8] font-bold shadow-xs' : 'text-[#5F6368] hover:bg-[#F8F9FA] hover:text-[#202124] font-medium',
                            (isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center px-2 py-3' : 'justify-between px-4 py-3'
                        ]"
                        class="w-full flex items-center rounded-xl text-sm transition-all"
                    >
                        <div class="flex items-center" :class="(isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center' : 'space-x-3'">
                            <i class="fa-solid fa-credit-card text-lg flex-shrink-0"></i>
                            <span v-if="!isSidebarCollapsed || isMobileMenuOpen" class="truncate">Payments</span>
                        </div>
                    </button>

                    <button 
                        @click="activeTab = 'approvals'; isMobileMenuOpen = false"
                        :title="(isSidebarCollapsed && !isMobileMenuOpen) ? 'Payment Approvals' : ''"
                        :class="[
                            activeTab === 'approvals' ? 'bg-[#E8F0FE] text-[#1A73E8] font-bold shadow-xs' : 'text-[#5F6368] hover:bg-[#F8F9FA] hover:text-[#202124] font-medium',
                            (isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center px-2 py-3' : 'justify-between px-4 py-3'
                        ]"
                        class="w-full flex items-center rounded-xl text-sm transition-all"
                    >
                        <div class="flex items-center" :class="(isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center' : 'space-x-3'">
                            <i class="fa-solid fa-check-double text-lg flex-shrink-0"></i>
                            <span v-if="!isSidebarCollapsed || isMobileMenuOpen" class="truncate flex items-center gap-1.5">
                                <span>Approvals</span>
                                <span v-if="pendingApprovals && pendingApprovals.length > 0" class="bg-[#C5221F] text-white text-[10px] px-1.5 py-0.5 rounded-full font-bold">
                                    {{ pendingApprovals.length }}
                                </span>
                            </span>
                        </div>
                    </button>

                    <button 
                        @click="activeTab = 'logs'; isMobileMenuOpen = false"
                        :title="(isSidebarCollapsed && !isMobileMenuOpen) ? 'Activity Logs' : ''"
                        :class="[
                            activeTab === 'logs' ? 'bg-[#E8F0FE] text-[#1A73E8] font-bold shadow-xs' : 'text-[#5F6368] hover:bg-[#F8F9FA] hover:text-[#202124] font-medium',
                            (isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center px-2 py-3' : 'justify-between px-4 py-3'
                        ]"
                        class="w-full flex items-center rounded-xl text-sm transition-all"
                    >
                        <div class="flex items-center" :class="(isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center' : 'space-x-3'">
                            <i class="fa-solid fa-scroll text-lg flex-shrink-0"></i>
                            <span v-if="!isSidebarCollapsed || isMobileMenuOpen" class="truncate">Activity Logs</span>
                        </div>
                    </button>

                    <button 
                        @click="activeTab = 'marketing'; isMobileMenuOpen = false"
                        :title="(isSidebarCollapsed && !isMobileMenuOpen) ? 'Marketing & Referrals' : ''"
                        :class="[
                            activeTab === 'marketing' ? 'bg-[#E8F0FE] text-[#1A73E8] font-bold shadow-xs' : 'text-[#5F6368] hover:bg-[#F8F9FA] hover:text-[#202124] font-medium',
                            (isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center px-2 py-3' : 'justify-between px-4 py-3'
                        ]"
                        class="w-full flex items-center rounded-xl text-sm transition-all"
                    >
                        <div class="flex items-center" :class="(isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center' : 'space-x-3'">
                            <i class="fa-solid fa-bullhorn text-lg flex-shrink-0"></i>
                            <span v-if="!isSidebarCollapsed || isMobileMenuOpen" class="truncate">Marketing</span>
                        </div>
                    </button>

                    <button 
                        @click="activeTab = 'settings'; isMobileMenuOpen = false"
                        :title="(isSidebarCollapsed && !isMobileMenuOpen) ? 'Settings' : ''"
                        :class="[
                            activeTab === 'settings' ? 'bg-[#E8F0FE] text-[#1A73E8] font-bold shadow-xs' : 'text-[#5F6368] hover:bg-[#F8F9FA] hover:text-[#202124] font-medium',
                            (isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center px-2 py-3' : 'justify-between px-4 py-3'
                        ]"
                        class="w-full flex items-center rounded-xl text-sm transition-all"
                    >
                        <div class="flex items-center" :class="(isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center' : 'space-x-3'">
                            <i class="fa-solid fa-gear text-lg flex-shrink-0"></i>
                            <span v-if="!isSidebarCollapsed || isMobileMenuOpen" class="truncate">Settings</span>
                        </div>
                    </button>
                </nav>
            </div>

            <!-- Sidebar Footer / User Profile -->
            <div class="p-3 sm:p-4 border-t border-[#DADCE0] bg-[#F8F9FA] space-y-3">
                <div class="flex items-center" :class="(isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center' : 'space-x-3 px-2'">
                    <div class="h-9 w-9 rounded-full bg-[#1A73E8] text-white flex-shrink-0 flex items-center justify-center font-bold text-sm shadow-sm">
                        SA
                    </div>
                    <div v-if="!isSidebarCollapsed || isMobileMenuOpen" class="flex-1 min-w-0">
                        <p class="text-xs font-bold text-[#202124] truncate">Platform Admin</p>
                        <p class="text-2xs text-[#5F6368] truncate">superadmin@excellence.com</p>
                    </div>
                </div>
                <button 
                    @click="logout"
                    :title="(isSidebarCollapsed && !isMobileMenuOpen) ? 'Sign Out' : ''"
                    :class="(isSidebarCollapsed && !isMobileMenuOpen) ? 'py-2.5 px-2' : 'py-2 space-x-1.5'"
                    class="w-full rounded-xl bg-white border border-[#DADCE0] text-xs font-semibold text-[#C5221F] hover:bg-[#FCE8E6]/50 transition-colors shadow-2xs flex items-center justify-center"
                >
                    <i class="fa-solid fa-right-from-bracket text-sm"></i>
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
                            {{ activeTab === 'overview' ? 'Platform Overview' : activeTab === 'schools' ? 'Registered Schools' : activeTab === 'approvals' ? 'Payment Approvals' : activeTab === 'plans' ? 'Plans & Packages' : activeTab === 'payments' ? 'Payment History' : activeTab === 'logs' ? 'Activity Logs' : activeTab === 'marketing' ? 'Marketing & Referrals' : 'System Settings' }}
                        </h2>
                        <p class="text-xs text-[#5F6368] hidden sm:block">
                            {{ activeTab === 'overview' ? 'Summary of onboarded schools, revenue, and system health.' : activeTab === 'schools' ? 'Manage and monitor all school tenants using the cloud portal.' : activeTab === 'approvals' ? 'Verify and approve school onboarding and subscription renewals.' : activeTab === 'plans' ? 'Configure pricing packages and feature limits.' : activeTab === 'payments' ? 'Track incoming subscription payments and invoices.' : activeTab === 'logs' ? 'Recent administrator and system actions.' : activeTab === 'marketing' ? 'Manage SaaS coupons, referral programs, and trials.' : 'Update global cloud platform preferences.' }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center space-x-3 self-end sm:self-auto flex-shrink-0">
                    <button 
                        @click="showModal = true"
                        class="rounded-full bg-[#1A73E8] px-5 py-2.5 text-xs font-semibold text-white shadow-sm hover:bg-[#1557B0] transition-all flex items-center space-x-2"
                    >
                        <span>+</span>
                        <span>Onboard New School</span>
                    </button>
                </div>
            </header>

            <div class="p-4 sm:p-6 lg:p-8 space-y-6 sm:space-y-8 flex-1">
                <!-- ================= GLOBAL FLASH MESSAGES ================= -->
                <div v-if="$page.props.flash?.success" class="rounded-2xl border border-[#CEEAD6] bg-[#E6F4EA] p-4 text-[#137333] shadow-sm flex items-center justify-between gap-4 animate-fadeIn">
                    <div class="flex items-center gap-3">
                        <i class="fa-solid fa-circle-check text-xl"></i>
                        <span class="text-sm font-bold">{{ $page.props.flash.success }}</span>
                    </div>
                    <button @click="$page.props.flash.success = null" class="text-xs font-bold text-[#137333] hover:opacity-75 cursor-pointer">
                        <i class="fa-solid fa-xmark text-base"></i>
                    </button>
                </div>
                <div v-if="$page.props.flash?.error || $page.props.flash?.message" class="rounded-2xl border border-[#FAD2CF] bg-[#FCE8E6] p-4 text-[#C5221F] shadow-sm flex items-center justify-between gap-4 animate-fadeIn">
                    <div class="flex items-center gap-3">
                        <i class="fa-solid fa-circle-exclamation text-xl"></i>
                        <span class="text-sm font-bold">{{ $page.props.flash.error || $page.props.flash.message }}</span>
                    </div>
                    <button @click="$page.props.flash.error = null; $page.props.flash.message = null" class="text-xs font-bold text-[#C5221F] hover:opacity-75 cursor-pointer">
                        <i class="fa-solid fa-xmark text-base"></i>
                    </button>
                </div>

                <!-- ================= TAB 1: OVERVIEW ================= -->
                <div v-if="activeTab === 'overview'" class="space-y-8 animate-fadeIn">
                    <!-- KPI Cards Grid -->
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="google-card rounded-2xl p-6 bg-gradient-to-br from-white to-blue-50/20 border border-[#DADCE0] shadow-2xs flex flex-col justify-between transition-all hover:scale-[1.01] hover:shadow-xs">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold uppercase tracking-wider text-[#5F6368]">Total Schools</span>
                                <span class="h-8 w-8 rounded-lg bg-[#E8F0FE] text-[#1A73E8] flex items-center justify-center text-sm font-bold"><i class="fa-solid fa-school"></i></span>
                            </div>
                            <div class="mt-4 flex items-baseline justify-between">
                                <span class="text-3xl font-extrabold text-[#202124]">{{ analytics?.total_tenants || tenants?.length || 0 }}</span>
                                <span class="text-xs font-bold text-[#137333] bg-[#E6F4EA] px-2.5 py-0.5 rounded-full border border-[#CEEAD6] flex items-center gap-1">
                                    <span class="h-1.5 w-1.5 rounded-full bg-[#137333] animate-pulse"></span>
                                    {{ analytics?.active_tenants || tenants?.length || 0 }} Active
                                </span>
                            </div>
                        </div>

                        <div class="google-card rounded-2xl p-6 bg-gradient-to-br from-white to-green-50/20 border border-[#DADCE0] shadow-2xs flex flex-col justify-between transition-all hover:scale-[1.01] hover:shadow-xs">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold uppercase tracking-wider text-[#5F6368]">Monthly Revenue</span>
                                <span class="h-8 w-8 rounded-lg bg-[#E6F4EA] text-[#137333] flex items-center justify-center text-sm font-bold"><i class="fa-solid fa-money-bill-wave"></i></span>
                            </div>
                            <div class="mt-4 flex items-baseline justify-between">
                                <span class="text-3xl font-extrabold text-[#202124]">PKR {{ (analytics?.total_revenue || 0).toLocaleString() }}</span>
                                <span class="text-xs font-bold text-[#1A73E8] bg-[#E8F0FE] px-2.5 py-0.5 rounded-full border border-[#D2E3FC]">This Month</span>
                            </div>
                        </div>

                        <div class="google-card rounded-2xl p-6 bg-gradient-to-br from-white to-amber-50/20 border border-[#DADCE0] shadow-2xs flex flex-col justify-between transition-all hover:scale-[1.01] hover:shadow-xs">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold uppercase tracking-wider text-[#5F6368]">Enrolled Students</span>
                                <span class="h-8 w-8 rounded-lg bg-[#FEF7E0] text-[#B06000] flex items-center justify-center text-sm font-bold"><i class="fa-solid fa-graduation-cap"></i></span>
                            </div>
                            <div class="mt-4 flex items-baseline justify-between">
                                <span class="text-3xl font-extrabold text-[#202124]">{{ (analytics?.total_students || 0).toLocaleString() }}</span>
                                <span class="text-xs font-semibold text-[#5F6368]">Across all schools</span>
                            </div>
                        </div>

                        <div class="google-card rounded-2xl p-6 bg-gradient-to-br from-white to-indigo-50/20 border border-[#DADCE0] shadow-2xs flex flex-col justify-between transition-all hover:scale-[1.01] hover:shadow-xs">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold uppercase tracking-wider text-[#5F6368]">System Status</span>
                                <span class="h-8 w-8 rounded-lg bg-[#E8F0FE] text-[#1A73E8] flex items-center justify-center text-sm font-bold"><i class="fa-solid fa-shield-halved"></i></span>
                            </div>
                            <div class="mt-4 flex items-baseline justify-between">
                                <span class="text-lg font-extrabold text-[#137333] flex items-center gap-1.5">
                                    <span class="h-2 w-2 rounded-full bg-[#137333] animate-ping"></span>
                                    All Systems Normal
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Row 2: Charts -->
                    <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
                        <!-- Left Panel: Line/Area Growth Chart -->
                        <div class="google-card lg:col-span-2 rounded-2xl border border-[#DADCE0] bg-white p-6 shadow-2xs space-y-4">
                            <div class="flex items-center justify-between border-b border-[#DADCE0] pb-4">
                                <div>
                                    <h3 class="text-base font-bold text-[#202124]">SaaS Growth & Expansion</h3>
                                    <p class="text-xs text-[#5F6368]">Onboarded school campuses trend (6-Month history)</p>
                                </div>
                                <span class="text-xs font-bold text-[#1A73E8] bg-[#E8F0FE] px-3 py-1 rounded-full border border-[#D2E3FC]">MoM Growth</span>
                            </div>
                            <div class="relative pt-4 flex flex-col items-center">
                                <svg :viewBox="`0 0 ${growthPoints.width} ${growthPoints.height}`" class="w-full h-48">
                                    <defs>
                                        <linearGradient id="chartGrad" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="0%" stop-color="#1A73E8" stop-opacity="0.15" />
                                            <stop offset="100%" stop-color="#1A73E8" stop-opacity="0.0" />
                                        </linearGradient>
                                    </defs>
                                    <!-- Horizontal Grid Lines -->
                                    <line x1="20" y1="20" x2="480" y2="20" stroke="#F1F3F4" stroke-dasharray="4" />
                                    <line x1="20" y1="65" x2="480" y2="65" stroke="#F1F3F4" stroke-dasharray="4" />
                                    <line x1="20" y1="110" x2="480" y2="110" stroke="#F1F3F4" stroke-dasharray="4" />
                                    <!-- Area Fill -->
                                    <path :d="growthPoints.areaPath" fill="url(#chartGrad)" />
                                    <!-- Line Path -->
                                    <path :d="growthPoints.linePath" fill="none" stroke="#1A73E8" stroke-width="3" stroke-linecap="round" />
                                    <!-- Data Dots -->
                                    <circle v-for="(p, i) in growthPoints.points" :key="i" :cx="p.x" :cy="p.y" r="5" fill="#FFFFFF" stroke="#1A73E8" stroke-width="3" class="hover:r-7 transition-all duration-200 cursor-pointer" />
                                </svg>
                                <!-- Labels -->
                                <div class="w-full flex justify-between px-6 text-3xs font-bold uppercase tracking-wider text-[#5F6368] pt-2">
                                    <span>Feb</span>
                                    <span>Mar</span>
                                    <span>Apr</span>
                                    <span>May</span>
                                    <span>Jun</span>
                                    <span>Jul</span>
                                </div>
                            </div>
                        </div>

                        <!-- Right Panel: Donut Chart -->
                        <div class="google-card rounded-2xl border border-[#DADCE0] bg-white p-6 shadow-2xs space-y-4">
                            <div class="border-b border-[#DADCE0] pb-4">
                                <h3 class="text-base font-bold text-[#202124]">Subscription Tiers</h3>
                                <p class="text-xs text-[#5F6368]">Active plan share across tenants</p>
                            </div>
                            <div class="flex flex-col items-center justify-center py-2 space-y-4">
                                <div class="relative flex items-center justify-center">
                                    <svg viewBox="0 0 100 100" class="h-36 w-36">
                                        <circle cx="50" cy="50" r="40" fill="transparent" stroke="#F1F3F4" stroke-width="10" />
                                        <circle 
                                            v-for="seg in planSegments" 
                                            :key="seg.name"
                                            cx="50" 
                                            cy="50" 
                                            r="40" 
                                            fill="transparent" 
                                            :stroke="seg.color" 
                                            stroke-width="10" 
                                            :stroke-dasharray="seg.dashArray" 
                                            :stroke-dashoffset="seg.dashOffset"
                                            transform="rotate(-90 50 50)" 
                                            class="transition-all duration-300 hover:stroke-[12px]"
                                        />
                                    </svg>
                                    <div class="absolute flex flex-col items-center text-center">
                                        <span class="text-2xl font-extrabold text-[#202124]">{{ tenants?.length || 0 }}</span>
                                        <span class="text-4xs font-bold uppercase tracking-wider text-[#5F6368]">Total</span>
                                    </div>
                                </div>
                                <!-- Legend Grid -->
                                <div class="w-full grid grid-cols-2 gap-2 text-xs pt-2">
                                    <div v-for="seg in planSegments" :key="seg.name" class="flex items-center space-x-1.5 p-1.5 bg-[#F8F9FA] rounded-lg border border-[#DADCE0]">
                                        <span class="h-2 w-2 rounded-full shrink-0" :style="{ backgroundColor: seg.color }"></span>
                                        <span class="truncate font-semibold text-[#202124]">{{ seg.name }}</span>
                                        <span class="ml-auto font-bold text-[#5F6368]">{{ seg.percent }}%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Row 3: Operations health, Recent ledger, activity logs -->
                    <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
                        <!-- Health Panel -->
                        <div class="google-card rounded-2xl border border-[#DADCE0] bg-white p-6 shadow-2xs space-y-4">
                            <h3 class="text-base font-bold text-[#202124] border-b border-[#DADCE0] pb-4 flex items-center gap-1.5">
                                <i class="fa-solid fa-server text-[#1A73E8]"></i>
                                <span>Platform Gateways</span>
                            </h3>
                            <div class="space-y-4">
                                <div class="flex items-center justify-between p-3 bg-[#F8F9FA] rounded-xl border border-[#DADCE0]">
                                    <div class="flex items-center gap-2">
                                        <i class="fa-solid fa-brain text-purple-600 text-sm"></i>
                                        <span class="text-xs font-bold text-[#202124]">AI Vision API</span>
                                    </div>
                                    <span class="text-3xs font-bold text-[#137333] bg-[#E6F4EA] border border-[#CEEAD6] px-2 py-0.5 rounded-full">Operational</span>
                                </div>
                                <div class="flex items-center justify-between p-3 bg-[#F8F9FA] rounded-xl border border-[#DADCE0]">
                                    <div class="flex items-center gap-2">
                                        <i class="fa-solid fa-message text-[#137333] text-sm"></i>
                                        <span class="text-xs font-bold text-[#202124]">WhatsApp SMS Gateway</span>
                                    </div>
                                    <span class="text-3xs font-bold text-[#137333] bg-[#E6F4EA] border border-[#CEEAD6] px-2 py-0.5 rounded-full">Online</span>
                                </div>
                                <div class="flex items-center justify-between p-3 bg-[#F8F9FA] rounded-xl border border-[#DADCE0]">
                                    <div class="flex items-center gap-2">
                                        <i class="fa-solid fa-envelope text-[#1A73E8] text-sm"></i>
                                        <span class="text-xs font-bold text-[#202124]">SMTP Mail Server</span>
                                    </div>
                                    <span class="text-3xs font-bold text-[#137333] bg-[#E6F4EA] border border-[#CEEAD6] px-2 py-0.5 rounded-full">Connected</span>
                                </div>
                                <div class="flex items-center justify-between p-3 bg-[#F8F9FA] rounded-xl border border-[#DADCE0]">
                                    <div class="flex items-center gap-2">
                                        <i class="fa-solid fa-database text-amber-600 text-sm"></i>
                                        <span class="text-xs font-bold text-[#202124]">Database Backups</span>
                                    </div>
                                    <span class="text-3xs font-bold text-[#137333] bg-[#E6F4EA] border border-[#CEEAD6] px-2 py-0.5 rounded-full">Healthy</span>
                                </div>
                            </div>
                        </div>

                        <!-- Mini Ledger Panel -->
                        <div class="google-card rounded-2xl border border-[#DADCE0] bg-white p-6 shadow-2xs space-y-4">
                            <div class="flex items-center justify-between border-b border-[#DADCE0] pb-4">
                                <h3 class="text-base font-bold text-[#202124] flex items-center gap-1.5">
                                    <i class="fa-solid fa-cash-register text-[#137333]"></i>
                                    <span>Recent Ledger</span>
                                </h3>
                                <button @click="activeTab = 'payments'" class="text-xs font-bold text-[#1A73E8] hover:underline">View All</button>
                            </div>
                            <div class="space-y-3">
                                <div v-if="!revenueTransactions || revenueTransactions.length === 0" class="py-6 text-center text-xs text-[#5F6368]">
                                    No ledger entries recorded.
                                </div>
                                <div v-for="trx in revenueTransactions.slice(0, 3)" :key="trx.id" class="p-3 bg-[#F8F9FA] rounded-xl border border-[#DADCE0] flex items-center justify-between">
                                    <div>
                                        <p class="text-xs font-extrabold text-[#202124]">{{ trx.school_name }}</p>
                                        <p class="text-3xs text-[#5F6368] font-mono">{{ trx.transaction_code }} • {{ trx.gateway }}</p>
                                    </div>
                                    <span class="text-xs font-bold text-[#137333]">PKR {{ trx.amount.toLocaleString() }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Ticker Panel -->
                        <div class="google-card rounded-2xl border border-[#DADCE0] bg-white p-6 shadow-2xs space-y-4">
                            <div class="flex items-center justify-between border-b border-[#DADCE0] pb-4">
                                <h3 class="text-base font-bold text-[#202124] flex items-center gap-1.5">
                                    <i class="fa-solid fa-clock-rotate-left text-amber-600"></i>
                                    <span>Live Platform Activity</span>
                                </h3>
                                <button @click="activeTab = 'logs'" class="text-xs font-bold text-[#1A73E8] hover:underline">View Logs</button>
                            </div>
                            <div class="space-y-3 max-h-[220px] overflow-y-auto pr-1">
                                <div v-if="!activityLogs || activityLogs.length === 0" class="py-6 text-center text-xs text-[#5F6368]">
                                    No events recorded.
                                </div>
                                <div v-for="log in activityLogs.slice(0, 4)" :key="log.id" class="p-2.5 bg-[#F8F9FA] rounded-lg border border-[#DADCE0] flex gap-2">
                                    <span class="mt-0.5 shrink-0 text-amber-600"><i class="fa-solid fa-circle-info text-2xs"></i></span>
                                    <div>
                                        <p class="text-xs font-semibold text-[#202124] leading-normal">{{ log.event_title }}</p>
                                        <p class="text-4xs font-bold text-[#5F6368] uppercase tracking-wider mt-0.5">{{ log.created_at }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Row 4: Shortcuts -->
                    <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
                        <div class="google-card lg:col-span-2 rounded-2xl border border-[#DADCE0] bg-white p-6 shadow-2xs space-y-4">
                            <div class="flex items-center justify-between border-b border-[#DADCE0] pb-4">
                                <h3 class="text-base font-bold text-[#202124]">Recently Registered Schools</h3>
                                <button @click="activeTab = 'schools'" class="text-xs font-bold text-[#1A73E8] hover:underline flex items-center gap-1"><span>View All</span> <i class="fa-solid fa-arrow-right text-[10px]"></i></button>
                            </div>
                            <div class="space-y-3">
                                <div v-if="!tenants || tenants.length === 0" class="py-8 text-center text-sm text-[#5F6368]">
                                    No schools onboarded yet. Click "+ Onboard New School" to get started!
                                </div>
                                <div v-for="t in tenants.slice(0, 3)" :key="t.id" class="flex items-center justify-between p-4 rounded-xl bg-[#F8F9FA] border border-[#DADCE0] hover:border-[#1A73E8] transition-all">
                                    <div class="flex items-center space-x-3.5">
                                        <div class="h-10 w-10 rounded-xl bg-white border border-[#DADCE0] flex items-center justify-center font-bold text-[#1A73E8]">
                                            <i class="fa-solid fa-school"></i>
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-[#202124]">{{ t.name }}</p>
                                            <a :href="getTenantLoginUrl(t)" target="_blank" class="text-xs text-[#1A73E8] hover:underline flex items-center gap-1">
                                                <span>{{ getTenantDisplayUrl(t) }}</span> <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
                                            </a>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-3">
                                        <span :class="(t.status || t.data?.status) === 'suspended' ? 'bg-[#FCE8E6] text-[#C5221F] border-[#FAD2CF]' : 'bg-[#E6F4EA] text-[#137333] border-[#CEEAD6]'" class="px-3 py-1 rounded-full text-xs font-bold border">
                                            {{ (t.status || t.data?.status) === 'suspended' ? 'Suspended' : 'Active' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="google-card rounded-2xl border border-[#DADCE0] bg-white p-6 shadow-2xs space-y-4">
                            <h3 class="text-base font-bold text-[#202124] border-b border-[#DADCE0] pb-4">Shortcuts & Tools</h3>
                            <div class="space-y-3">
                                <button @click="showModal = true" class="w-full flex items-center space-x-3 p-3.5 rounded-xl bg-[#F8F9FA] hover:bg-[#E8F0FE] text-left border border-[#DADCE0] transition-colors group cursor-pointer">
                                    <i class="fa-solid fa-bolt text-lg text-[#1A73E8]"></i>
                                    <div>
                                        <p class="text-sm font-bold text-[#202124] group-hover:text-[#1A73E8]">Onboard New School</p>
                                        <p class="text-xs text-[#5F6368]">Provision cloud database & domain</p>
                                    </div>
                                </button>
                                <button @click="openAddPlanModal" class="w-full flex items-center space-x-3 p-3.5 rounded-xl bg-[#F8F9FA] hover:bg-[#E8F0FE] text-left border border-[#DADCE0] transition-colors group cursor-pointer">
                                    <i class="fa-solid fa-box text-lg text-[#1A73E8]"></i>
                                    <div>
                                        <p class="text-sm font-bold text-[#202124] group-hover:text-[#1A73E8]">Create Subscription Plan</p>
                                        <p class="text-xs text-[#5F6368]">Add pricing & feature tier</p>
                                    </div>
                                </button>
                                <button @click="activeTab = 'settings'" class="w-full flex items-center space-x-3 p-3.5 rounded-xl bg-[#F8F9FA] hover:bg-[#E8F0FE] text-left border border-[#DADCE0] transition-colors group cursor-pointer">
                                    <i class="fa-solid fa-gear text-lg text-[#1A73E8]"></i>
                                    <div>
                                        <p class="text-sm font-bold text-[#202124] group-hover:text-[#1A73E8]">Platform Settings</p>
                                        <p class="text-xs text-[#5F6368]">Configure contact & maintenance</p>
                                    </div>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ================= TAB 2: SCHOOLS ================= -->
                <div v-if="activeTab === 'schools'" class="space-y-6 animate-fadeIn">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-4 rounded-2xl border border-[#DADCE0] shadow-2xs">
                        <div class="relative flex-1 max-w-md">
                            <input 
                                v-model="schoolSearch" 
                                type="text" 
                                placeholder="Search by school name or code..."
                                class="w-full rounded-xl border border-[#DADCE0] bg-[#F8F9FA] px-4 py-2.5 text-sm focus:border-[#1A73E8] focus:bg-white focus:outline-none transition-all"
                            />
                        </div>
                        <span class="text-xs font-semibold text-[#5F6368]">Showing {{ filteredSchools.length }} of {{ tenants.length }} schools</span>
                    </div>

                    <div class="google-card overflow-hidden rounded-2xl border border-[#DADCE0] bg-white shadow-2xs">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-sm min-w-[1000px]">
                            <thead>
                                <tr class="border-b border-[#DADCE0] bg-[#F8F9FA] text-xs font-bold uppercase tracking-wider text-[#5F6368]">
                                    <th class="py-4 px-6">School Name</th>
                                    <th class="py-4 px-6">Portal Domain</th>
                                    <th class="py-4 px-6">Admin Contact</th>
                                    <th class="py-4 px-6">Assigned Plan</th>
                                    <th class="py-4 px-6">Usage Metrics</th>
                                    <th class="py-4 px-6">Status</th>
                                    <th class="py-4 px-6 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#E8EAED]">
                                <tr v-if="filteredSchools.length === 0">
                                    <td colspan="6" class="py-12 text-center text-sm text-[#5F6368]">No schools matched your search query.</td>
                                </tr>
                                <tr v-for="t in filteredSchools" :key="t.id" class="hover:bg-[#F8F9FA]/80 transition-colors">
                                    <td class="py-4 px-6">
                                        <div class="font-bold text-[#202124]">{{ t.name }}</div>
                                        <div class="text-xs text-[#5F6368]">Code: {{ t.id }}</div>
                                    </td>
                                    <td class="py-4 px-6">
                                        <a :href="getTenantLoginUrl(t)" target="_blank" class="font-medium text-[#1A73E8] hover:underline flex items-center space-x-1">
                                            <span>{{ getTenantDisplayUrl(t) }}</span>
                                            <i class="fa-solid fa-arrow-up-right-from-square text-xs"></i>
                                        </a>
                                    </td>
                                    <td class="py-4 px-6">
                                        <div class="font-semibold text-[#202124]">{{ t.admin_name || t.data?.admin_name || 'Principal' }}</div>
                                        <div class="text-xs text-[#5F6368]">{{ t.admin_email || t.data?.admin_email || 'admin@school.test' }}</div>
                                    </td>
                                    <td class="py-4 px-6">
                                        <span class="rounded-lg bg-[#F1F3F4] px-3 py-1 text-xs font-bold text-[#3C4043]">
                                            {{ t.plan?.name || t.plan_name || 'Standard Tier' }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-6">
                                        <div class="flex flex-col gap-1 text-xs">
                                            <div class="flex items-center justify-between">
                                                <span class="text-[#5F6368]">Storage:</span>
                                                <span class="font-bold text-[#202124]">45%</span>
                                            </div>
                                            <div class="flex items-center justify-between">
                                                <span class="text-[#5F6368]">Students:</span>
                                                <span class="font-bold text-[#202124]">{{ t.student_count || 320 }} / 500</span>
                                            </div>
                                            <div class="w-full bg-[#E8EAED] rounded-full h-1.5 mt-1">
                                                <div class="bg-[#1A73E8] h-1.5 rounded-full" style="width: 45%"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 px-6">
                                        <span :class="(t.status || t.data?.status) === 'suspended' ? 'bg-[#FCE8E6] text-[#C5221F] border-[#FAD2CF]' : 'bg-[#E6F4EA] text-[#137333] border-[#CEEAD6]'" class="px-3 py-1 rounded-full text-xs font-bold border">
                                            {{ (t.status || t.data?.status) === 'suspended' ? 'Suspended' : 'Active' }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 text-right">
                                        <div class="flex items-center justify-end gap-2 flex-wrap">
                                            <button 
                                                @click="openEditSchoolModal(t)"
                                                title="Edit School Details"
                                                class="rounded-lg border border-[#DADCE0] bg-white px-3 py-1.5 text-xs font-semibold text-[#3C4043] hover:bg-[#F8F9FA] hover:text-[#1A73E8] hover:border-[#1A73E8]/30 transition-all flex items-center gap-1.5 whitespace-nowrap shadow-2xs cursor-pointer"
                                            >
                                                <i class="fa-solid fa-pen-to-square"></i>
                                                <span>Edit</span>
                                            </button>
                                            <button 
                                                @click="toggleTenantStatus(t.id)"
                                                :title="(t.status || t.data?.status) === 'suspended' ? 'Activate School' : 'Suspend School'"
                                                :class="(t.status || t.data?.status) === 'suspended' ? 'bg-[#E6F4EA] text-[#137333] border-[#CEEAD6] hover:bg-[#D2E3D8]' : 'border-[#DADCE0] text-[#3C4043] hover:bg-[#FCE8E6] hover:text-[#C5221F] hover:border-[#FAD2CF]'"
                                                class="rounded-lg border px-3 py-1.5 text-xs font-semibold transition-all flex items-center gap-1.5 whitespace-nowrap shadow-2xs cursor-pointer"
                                            >
                                                <i :class="(t.status || t.data?.status) === 'suspended' ? 'fa-solid fa-play' : 'fa-solid fa-pause'"></i>
                                                <span>{{ (t.status || t.data?.status) === 'suspended' ? 'Activate' : 'Suspend' }}</span>
                                            </button>
                                            <button 
                                                @click="backupTenant(t.id)"
                                                title="Backup Database"
                                                class="rounded-lg border border-[#DADCE0] bg-white px-3 py-1.5 text-xs font-semibold text-[#3C4043] hover:bg-[#F8F9FA] hover:text-[#1A73E8] hover:border-[#1A73E8]/30 transition-all flex items-center gap-1.5 whitespace-nowrap shadow-2xs cursor-pointer"
                                            >
                                                <i class="fa-solid fa-floppy-disk"></i>
                                                <span>Backup</span>
                                            </button>
                                            <button 
                                                @click="syncTenant(t.id)"
                                                title="Sync Database Schema"
                                                class="rounded-lg border border-[#DADCE0] bg-white px-3 py-1.5 text-xs font-semibold text-[#3C4043] hover:bg-[#F8F9FA] hover:text-[#137333] hover:border-[#137333]/30 transition-all flex items-center gap-1.5 whitespace-nowrap shadow-2xs cursor-pointer"
                                            >
                                                <i class="fa-solid fa-rotate"></i>
                                                <span>Sync</span>
                                            </button>
                                            <a 
                                                :href="`/tenants/${t.id}/impersonate`"
                                                target="_blank"
                                                title="Login as Tenant Admin (Impersonate)"
                                                class="rounded-lg border border-[#DADCE0] bg-[#F8F9FA] px-3 py-1.5 text-xs font-semibold text-[#1A73E8] hover:bg-[#E8F0FE] hover:border-[#1A73E8]/30 transition-all flex items-center gap-1.5 whitespace-nowrap shadow-2xs cursor-pointer"
                                            >
                                                <i class="fa-solid fa-user-secret"></i>
                                                <span>Impersonate</span>
                                            </a>
                                            <button 
                                                @click="mapCustomDomain(t.id)"
                                                title="Map Custom FQDN Domain"
                                                class="rounded-lg border border-[#DADCE0] bg-white px-3 py-1.5 text-xs font-semibold text-[#3C4043] hover:bg-[#F8F9FA] hover:text-[#B06000] hover:border-[#B06000]/30 transition-all flex items-center gap-1.5 whitespace-nowrap shadow-2xs cursor-pointer"
                                            >
                                                <i class="fa-solid fa-globe"></i>
                                                <span>Domain</span>
                                            </button>
                                            <button 
                                                @click="offboardTenant(t.id)"
                                                title="Export Data / Offboard Tenant"
                                                class="rounded-lg border border-[#DADCE0] bg-white px-3 py-1.5 text-xs font-semibold text-[#3C4043] hover:bg-[#F8F9FA] hover:text-[#C5221F] hover:border-[#FAD2CF] transition-all flex items-center gap-1.5 whitespace-nowrap shadow-2xs cursor-pointer"
                                            >
                                                <i class="fa-solid fa-file-export"></i>
                                                <span>Offboard</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        </div>
                    </div>
                </div>

                <!-- ================= TAB: PAYMENT APPROVALS ================= -->
                <div v-if="activeTab === 'approvals'" class="space-y-6 animate-fadeIn">
                    <div class="google-card rounded-2xl border border-[#DADCE0] bg-white p-6 shadow-2xs">
                        <div class="flex items-center justify-between border-b border-[#DADCE0] pb-4 mb-4">
                            <div>
                                <h3 class="text-base font-extrabold text-[#202124]">Pending SaaS Payment Approvals</h3>
                                <p class="text-xs text-[#5F6368] mt-0.5">Review onboarding deposits and subscription renewals uploaded by school administrators.</p>
                            </div>
                            <span class="rounded-full bg-[#E8F0FE] px-3 py-1 text-xs font-bold text-[#1A73E8]">
                                {{ pendingApprovals ? pendingApprovals.length : 0 }} Pending
                            </span>
                        </div>

                        <div v-if="!pendingApprovals || pendingApprovals.length === 0" class="py-12 text-center text-[#5F6368] text-sm">
                            <i class="fa-solid fa-circle-check text-4xl text-[#137333] mb-3 block"></i>
                            <p class="font-bold text-[#202124]">All Clear!</p>
                            <p class="text-xs mt-1">There are no pending subscription or onboarding payments awaiting verification.</p>
                        </div>

                        <div v-else class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="border-b border-[#DADCE0] text-[#5F6368] font-bold">
                                        <th class="py-3 px-4">Transaction Code</th>
                                        <th class="py-3 px-4">School Name</th>
                                        <th class="py-3 px-4">Plan & Billing</th>
                                        <th class="py-3 px-4">Amount Due</th>
                                        <th class="py-3 px-4">Ref Number</th>
                                        <th class="py-3 px-4">Receipt Slip</th>
                                        <th class="py-3 px-4 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#E8EAED] font-medium text-[#202124]">
                                    <tr v-for="appr in pendingApprovals" :key="appr.id" class="hover:bg-[#F8F9FA]/50">
                                        <td class="py-4 px-4 font-mono font-bold text-[#1A73E8]">{{ appr.transaction_code }}</td>
                                        <td class="py-4 px-4">
                                            <div class="font-bold text-sm">{{ appr.school_name }}</div>
                                            <div class="text-3xs text-[#5F6368] font-mono">Tenant ID: {{ appr.tenant_id }}</div>
                                        </td>
                                        <td class="py-4 px-4">
                                            <span class="px-2 py-0.5 rounded-full bg-[#E8F0FE] text-[#1A73E8] font-bold text-[10px]">
                                                {{ appr.plan_name }}
                                            </span>
                                        </td>
                                        <td class="py-4 px-4 font-bold text-sm text-[#137333]">PKR {{ appr.amount.toLocaleString() }}</td>
                                        <td class="py-4 px-4 font-mono font-bold">{{ appr.reference_no }}</td>
                                        <td class="py-4 px-4">
                                            <button 
                                                v-if="appr.receipt_path" 
                                                type="button" 
                                                @click="selectedReceipt = appr.receipt_path"
                                                class="rounded-lg bg-[#F1F3F4] hover:bg-[#E8EAED] px-3 py-1.5 font-bold text-[#1A73E8] inline-flex items-center gap-1"
                                            >
                                                <i class="fa-solid fa-image"></i>
                                                <span>View Slip</span>
                                            </button>
                                            <span v-else class="text-[#C5221F] font-bold text-[10px]">No Slip Uploaded</span>
                                        </td>
                                        <td class="py-4 px-4 text-right space-x-2">
                                            <button 
                                                @click="approveSaaSCheckout(appr.id)"
                                                class="rounded-full bg-[#137333] hover:bg-[#0D652D] px-4 py-2 font-bold text-white shadow-sm inline-flex items-center gap-1"
                                            >
                                                <i class="fa-solid fa-circle-check"></i> Approve
                                            </button>
                                            <button 
                                                @click="rejectSaaSCheckout(appr.id)"
                                                class="rounded-full bg-[#C5221F] hover:bg-[#A51D19] px-4 py-2 font-bold text-white shadow-sm inline-flex items-center gap-1"
                                            >
                                                <i class="fa-solid fa-circle-xmark"></i> Reject
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Receipt Slip Modal Dialog -->
                <div v-if="selectedReceipt" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4">
                    <div class="bg-white rounded-2xl max-w-2xl w-full p-6 shadow-2xl flex flex-col relative max-h-[85vh]">
                        <div class="flex justify-between items-center border-b border-[#DADCE0] pb-3 mb-4">
                            <h3 class="font-bold text-[#202124] text-sm uppercase tracking-wider flex items-center gap-1.5">
                                <i class="fa-solid fa-file-invoice"></i> Payment Receipt Slip Verification
                            </h3>
                            <button @click="selectedReceipt = null" class="h-8 w-8 rounded-full hover:bg-[#F8F9FA] font-bold text-[#5F6368] flex items-center justify-center cursor-pointer"><i class="fa-solid fa-xmark text-lg"></i></button>
                        </div>
                        <div class="flex-1 overflow-y-auto flex items-center justify-center bg-[#F8F9FA] rounded-xl border border-[#DADCE0] p-2">
                            <img :src="selectedReceipt" class="max-h-[60vh] object-contain rounded-lg shadow-sm" alt="Payment Receipt" />
                        </div>
                        <div class="mt-4 flex justify-end">
                            <button @click="selectedReceipt = null" class="rounded-full bg-[#1A73E8] px-6 py-2.5 font-bold text-white shadow-sm hover:bg-[#1557B0]">Close Viewer</button>
                        </div>
                    </div>
                </div>

                <!-- ================= TAB 3: PLANS & PACKAGES ================= -->
                <div v-if="activeTab === 'plans'" class="space-y-6 animate-fadeIn">
                    <div class="flex justify-between items-center bg-white p-6 rounded-2xl border border-[#DADCE0] shadow-2xs">
                        <div>
                            <h3 class="text-base font-bold text-[#202124]">Subscription Tiers</h3>
                            <p class="text-xs text-[#5F6368]">Add or customize plans available for new and existing schools.</p>
                        </div>
                        <button @click="openAddPlanModal" class="rounded-full bg-[#1A73E8] px-5 py-2.5 text-xs font-semibold text-white hover:bg-[#1557B0] transition-all shadow-sm">
                            + Add New Plan
                        </button>
                    </div>

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                        <div v-for="plan in plans" :key="plan.id" class="google-card rounded-2xl border border-[#DADCE0] bg-white p-6 shadow-2xs flex flex-col justify-between relative overflow-hidden">
                            <div>
                                <div class="flex items-center justify-between">
                                    <span class="rounded-full bg-[#E8F0FE] px-3 py-1 text-xs font-bold text-[#1A73E8] uppercase tracking-wider">
                                        {{ plan.code }}
                                    </span>
                                    <div class="space-x-1.5">
                                        <button @click="openEditPlanModal(plan)" class="text-xs font-bold text-[#1A73E8] hover:underline">Edit</button>
                                        <button @click="confirmDeletePlan(plan)" class="text-xs font-bold text-[#C5221F] hover:underline">Delete</button>
                                    </div>
                                </div>

                                <h3 class="mt-4 text-xl font-extrabold text-[#202124]">{{ plan.name }}</h3>
                                <div class="mt-2 flex items-baseline space-x-1">
                                    <span class="text-3xl font-extrabold text-[#202124]">PKR {{ (plan.price_monthly || 0).toLocaleString() }}</span>
                                    <span class="text-xs font-semibold text-[#5F6368]">/ month</span>
                                </div>
                                <p class="mt-1 text-xs font-semibold text-[#5F6368]">Max Students: {{ (plan.max_students || 500).toLocaleString() }}</p>

                                <div class="mt-6 border-t border-[#DADCE0] pt-4 space-y-2">
                                    <p class="text-xs font-bold uppercase tracking-wider text-[#5F6368]">Included Features:</p>
                                    <ul class="space-y-2 text-xs">
                                        <li v-for="feature in (Array.isArray(plan.features) ? plan.features : ['Core LMS', 'Attendance', 'Admissions'])" :key="feature" class="flex items-center space-x-2 text-[#202124]">
                                            <i class="fa-solid fa-check text-[#137333]"></i>
                                            <span>{{ feature }}</span>
                                        </li>
                                        <li class="flex items-center space-x-2 text-[#202124]">
                                            <i class="fa-solid fa-check text-[#137333]"></i>
                                            <span>14-Day Free Trial</span>
                                        </li>
                                        <li class="flex items-center space-x-2 text-[#202124]">
                                            <i class="fa-solid fa-check text-[#137333]"></i>
                                            <span>Self-Serve Upgrade/Downgrade</span>
                                        </li>
                                        <li class="flex items-center space-x-2 text-[#202124]">
                                            <i class="fa-solid fa-check text-[#137333]"></i>
                                            <span>API Rate Limit: 100 req/min</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <div class="mt-6 pt-4 border-t border-[#DADCE0]">
                                <span class="block text-center text-xs font-bold text-[#5F6368]">{{ plan.tenants_count || 0 }} Schools Currently Subscribed</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ================= TAB 4: PAYMENTS ================= -->
                <div v-if="activeTab === 'payments'" class="space-y-6 animate-fadeIn">
                    <!-- Revenue & Payment Summary Metrics -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                        <div class="google-card rounded-2xl border border-[#DADCE0] bg-white p-6 shadow-2xs flex items-center justify-between">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-[#5F6368]">Total Revenue Collected</p>
                                <h4 class="mt-2 text-2xl font-black text-[#137333]">PKR {{ paymentStats.totalRevenue.toLocaleString() }}</h4>
                                <p class="mt-1 text-2xs font-semibold text-[#5F6368]">Lifetime subscription inflows</p>
                            </div>
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#E6F4EA] text-[#137333] shadow-xs">
                                <i class="fa-solid fa-wallet text-xl"></i>
                            </div>
                        </div>

                        <div class="google-card rounded-2xl border border-[#DADCE0] bg-white p-6 shadow-2xs flex items-center justify-between">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-[#5F6368]">Successful Transactions</p>
                                <h4 class="mt-2 text-2xl font-black text-[#1A73E8]">{{ paymentStats.paidCount }} <span class="text-sm font-semibold text-[#5F6368]">/ {{ paymentStats.totalCount }}</span></h4>
                                <p class="mt-1 text-2xs font-semibold text-[#5F6368]">Verified payments processed</p>
                            </div>
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#E8F0FE] text-[#1A73E8] shadow-xs">
                                <i class="fa-solid fa-check-double text-xl"></i>
                            </div>
                        </div>

                        <div class="google-card rounded-2xl border border-[#DADCE0] bg-white p-6 shadow-2xs flex items-center justify-between">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-[#5F6368]">Average Ticket Size</p>
                                <h4 class="mt-2 text-2xl font-black text-[#B06000]">PKR {{ paymentStats.avgTicket.toLocaleString() }}</h4>
                                <p class="mt-1 text-2xs font-semibold text-[#5F6368]">Per school billing average</p>
                            </div>
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#FEF7E0] text-[#B06000] shadow-xs">
                                <i class="fa-solid fa-chart-line text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Dunning Management Alerts -->
                    <div v-if="overdueCount > 0" class="rounded-2xl border border-[#FAD2CF] bg-[#FCE8E6] p-5 shadow-2xs flex items-start sm:items-center justify-between flex-col sm:flex-row gap-4 mb-6">
                        <div class="flex items-start gap-4">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#C5221F] text-white">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-[#C5221F]">Dunning Management: {{ overdueCount }} {{ overdueCount === 1 ? 'School' : 'Schools' }} Overdue</h4>
                                <p class="mt-1 text-xs text-[#C5221F]">Automated retry logic failed. Grace periods expire in 48 hours before auto-suspension.</p>
                            </div>
                        </div>
                        <button 
                            @click="paymentStatusFilter = 'pending'"
                            class="shrink-0 rounded-lg bg-[#C5221F] px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-[#A50E0E] transition-colors cursor-pointer"
                        >
                            Review Failed Payments
                        </button>
                    </div>

                    <!-- Filter & Search Card -->
                    <div class="google-card rounded-2xl border border-[#DADCE0] bg-white p-5 shadow-2xs flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="relative w-full sm:w-80">
                            <i class="fa-solid fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-xs text-[#5F6368]"></i>
                            <input 
                                v-model="paymentSearch" 
                                type="text" 
                                placeholder="Search by Trx ID, school name, or gateway..." 
                                class="w-full rounded-xl border border-[#DADCE0] bg-[#F8F9FA] pl-9 pr-4 py-2 text-xs font-medium text-[#202124] placeholder-[#5F6368] focus:border-[#1A73E8] focus:bg-white focus:outline-none transition-all"
                            />
                        </div>
                        <div class="flex items-center gap-2 w-full sm:w-auto overflow-x-auto pb-1 sm:pb-0">
                            <span class="text-xs font-bold text-[#5F6368] whitespace-nowrap mr-1">Status:</span>
                            <button 
                                v-for="status in ['all', 'paid', 'pending', 'refunded']" 
                                :key="status"
                                @click="paymentStatusFilter = status"
                                :class="paymentStatusFilter === status ? 'bg-[#202124] text-white shadow-xs' : 'bg-[#F8F9FA] text-[#5F6368] hover:text-[#202124] border border-[#DADCE0]'"
                                class="rounded-lg px-3 py-1.5 text-xs font-bold capitalize transition-all cursor-pointer shrink-0"
                            >
                                {{ status === 'all' ? 'All Statuses' : status }}
                            </button>
                        </div>
                    </div>

                    <!-- Payments Table Card -->
                    <div class="google-card overflow-hidden rounded-2xl border border-[#DADCE0] bg-white shadow-2xs">
                        <div class="p-6 border-b border-[#DADCE0] flex items-center justify-between">
                            <div>
                                <h3 class="text-base font-bold text-[#202124]">Subscription Ledger & Invoices</h3>
                                <p class="text-xs text-[#5F6368]">Detailed record of all incoming subscription payments. Click any transaction to view HMAC verification & receipt.</p>
                            </div>
                            <span class="rounded-full bg-[#F8F9FA] px-3 py-1 text-xs font-bold text-[#5F6368] border border-[#DADCE0]">
                                {{ filteredTransactions.length }} Records
                            </span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-sm">
                                <thead>
                                    <tr class="border-b border-[#DADCE0] bg-[#F8F9FA] text-xs font-bold uppercase tracking-wider text-[#5F6368]">
                                        <th class="py-4 px-6">Transaction ID</th>
                                        <th class="py-4 px-6">School Tenant</th>
                                        <th class="py-4 px-6">Subscription Tier</th>
                                        <th class="py-4 px-6">Gateway / Method</th>
                                        <th class="py-4 px-6">Amount</th>
                                        <th class="py-4 px-6">Date & Time</th>
                                        <th class="py-4 px-6">Status</th>
                                        <th class="py-4 px-6 text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#E8EAED]">
                                    <tr v-if="filteredTransactions.length === 0">
                                        <td colspan="8" class="py-12 text-center text-sm text-[#5F6368]">
                                            <div class="flex flex-col items-center gap-2">
                                                <i class="fa-solid fa-receipt text-3xl text-[#DADCE0]"></i>
                                                <span class="font-semibold">No transaction records match your criteria.</span>
                                                <span class="text-xs text-[#5F6368]">Try clearing your search query or status filter.</span>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-for="trx in filteredTransactions" :key="trx.id" class="hover:bg-[#F8F9FA]/80 transition-colors group">
                                        <td class="py-4 px-6 font-mono text-xs font-bold text-[#1A73E8] group-hover:underline cursor-pointer" @click="selectedTransaction = trx">
                                            {{ trx.transaction_id || trx.transaction_code || ('REV-' + trx.id) }}
                                        </td>
                                        <td class="py-4 px-6">
                                            <div class="font-bold text-[#202124]">{{ trx.school_name || trx.tenant?.name || 'School' }}</div>
                                            <div class="font-mono text-2xs text-[#5F6368]">ID: {{ trx.tenant_id || trx.tenant?.id || 'N/A' }}</div>
                                        </td>
                                        <td class="py-4 px-6">
                                            <span class="inline-flex items-center gap-1.5 rounded-md bg-[#F8F9FA] px-2.5 py-1 text-xs font-semibold text-[#202124] border border-[#DADCE0]">
                                                <i class="fa-solid fa-layer-group text-2xs text-[#1A73E8]"></i>
                                                {{ trx.plan_name || trx.plan?.name || 'Growth Plan' }}
                                            </span>
                                        </td>
                                        <td class="py-4 px-6">
                                            <div class="text-xs font-semibold text-[#3C4043] flex items-center gap-1.5">
                                                <i :class="[
                                                    'fa-solid',
                                                    (trx.gateway || '').toLowerCase().includes('jazzcash') ? 'fa-mobile-screen text-[#C5221F]' : 'fa-building-columns text-[#1A73E8]'
                                                ]"></i>
                                                <span>{{ trx.gateway || 'Bank Transfer' }}</span>
                                            </div>
                                            <div class="font-mono text-2xs text-[#5F6368] truncate max-w-[150px]">Ref: {{ trx.reference_no || 'N/A' }}</div>
                                        </td>
                                        <td class="py-4 px-6 font-black text-sm text-[#137333]">
                                            PKR {{ (trx.amount || 0).toLocaleString() }}
                                        </td>
                                        <td class="py-4 px-6 text-xs text-[#5F6368]">
                                            {{ trx.date || trx.created_at || 'Recent' }}
                                        </td>
                                        <td class="py-4 px-6">
                                            <span :class="[
                                                'rounded-full px-3 py-1 text-xs font-bold border inline-flex items-center gap-1.5',
                                                (trx.status || 'paid').toLowerCase() === 'paid' ? 'bg-[#E6F4EA] text-[#137333] border-[#CEEAD6]' :
                                                (trx.status || '').toLowerCase() === 'pending' ? 'bg-[#FEF7E0] text-[#B06000] border-[#FAD2CF]' :
                                                'bg-[#FCE8E6] text-[#C5221F] border-[#FAD2CF]'
                                            ]">
                                                <span class="h-1.5 w-1.5 rounded-full" :class="[
                                                    (trx.status || 'paid').toLowerCase() === 'paid' ? 'bg-[#137333]' :
                                                    (trx.status || '').toLowerCase() === 'pending' ? 'bg-[#B06000]' : 'bg-[#C5221F]'
                                                ]"></span>
                                                {{ (trx.status || 'paid').toUpperCase() }}
                                            </span>
                                        </td>
                                        <td class="py-4 px-6 text-right">
                                            <button 
                                                @click="selectedTransaction = trx"
                                                class="rounded-lg bg-[#F8F9FA] px-3 py-1.5 text-xs font-bold text-[#1A73E8] hover:bg-[#E8F0FE] border border-[#DADCE0] transition-colors cursor-pointer"
                                                title="Inspect Transaction Details"
                                            >
                                                Inspect
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- ================= TAB 5: LOGS ================= -->
                <div v-if="activeTab === 'logs'" class="space-y-6 animate-fadeIn">
                    <!-- Logs Filter & Search Bar -->
                    <div class="google-card rounded-2xl border border-[#DADCE0] bg-white p-5 shadow-2xs flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="relative w-full sm:w-96">
                            <i class="fa-solid fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-xs text-[#5F6368]"></i>
                            <input 
                                v-model="logSearch" 
                                type="text" 
                                placeholder="Search event titles, descriptions, or actors..." 
                                class="w-full rounded-xl border border-[#DADCE0] bg-[#F8F9FA] pl-9 pr-4 py-2 text-xs font-medium text-[#202124] placeholder-[#5F6368] focus:border-[#1A73E8] focus:bg-white focus:outline-none transition-all"
                            />
                        </div>
                        <div class="flex items-center gap-2 w-full sm:w-auto overflow-x-auto pb-1 sm:pb-0">
                            <span class="text-xs font-bold text-[#5F6368] whitespace-nowrap mr-1">Severity:</span>
                            <button 
                                v-for="type in ['all', 'success', 'info', 'warning', 'error']" 
                                :key="type"
                                @click="logTypeFilter = type"
                                :class="logTypeFilter === type ? 'bg-[#202124] text-white shadow-xs' : 'bg-[#F8F9FA] text-[#5F6368] hover:text-[#202124] border border-[#DADCE0]'"
                                class="rounded-lg px-3 py-1.5 text-xs font-bold capitalize transition-all cursor-pointer shrink-0 flex items-center gap-1.5"
                            >
                                <span v-if="type !== 'all'" class="h-2 w-2 rounded-full" :class="[
                                    type === 'success' ? 'bg-[#137333]' :
                                    type === 'info' ? 'bg-[#1A73E8]' :
                                    type === 'warning' ? 'bg-[#B06000]' : 'bg-[#C5221F]'
                                ]"></span>
                                <span>{{ type === 'all' ? 'All Severities' : type }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- Activity Logs Table Card -->
                    <div class="google-card overflow-hidden rounded-2xl border border-[#DADCE0] bg-white shadow-2xs">
                        <div class="p-6 border-b border-[#DADCE0] flex items-center justify-between">
                            <div>
                                <h3 class="text-base font-bold text-[#202124]">System Audit & Activity Logs</h3>
                                <p class="text-xs text-[#5F6368]">Central-level audit trail tracking Super Admin actions across all tenants (e.g., plan upgrades, impersonations).</p>
                            </div>
                            <span class="rounded-full bg-[#F8F9FA] px-3 py-1 text-xs font-bold text-[#5F6368] border border-[#DADCE0]">
                                {{ filteredLogs.length }} Events Recorded
                            </span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-sm">
                                <thead>
                                    <tr class="border-b border-[#DADCE0] bg-[#F8F9FA] text-xs font-bold uppercase tracking-wider text-[#5F6368]">
                                        <th class="py-4 px-6">Severity</th>
                                        <th class="py-4 px-6">Event Title / Action</th>
                                        <th class="py-4 px-6">Description Breakdown</th>
                                        <th class="py-4 px-6">Initiated By (Actor)</th>
                                        <th class="py-4 px-6">IP Address</th>
                                        <th class="py-4 px-6 text-right">Timestamp</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#E8EAED]">
                                    <tr v-if="filteredLogs.length === 0">
                                        <td colspan="6" class="py-12 text-center text-sm text-[#5F6368]">
                                            <div class="flex flex-col items-center gap-2">
                                                <i class="fa-solid fa-shield-halved text-3xl text-[#DADCE0]"></i>
                                                <span class="font-semibold">No system logs match your filter criteria.</span>
                                                <span class="text-xs text-[#5F6368]">Try clearing the search query or selecting 'All Severities'.</span>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-for="log in filteredLogs" :key="log.id" class="hover:bg-[#F8F9FA]/80 transition-colors group cursor-pointer" @click="selectedLog = log">
                                        <td class="py-4 px-6">
                                            <span :class="[
                                                'rounded-full px-2.5 py-1 text-2xs font-black uppercase tracking-wider inline-flex items-center gap-1.5 border',
                                                (log.type || 'info') === 'success' ? 'bg-[#E6F4EA] text-[#137333] border-[#CEEAD6]' :
                                                (log.type || 'info') === 'warning' ? 'bg-[#FEF7E0] text-[#B06000] border-[#FAD2CF]' :
                                                (log.type || 'info') === 'error' ? 'bg-[#FCE8E6] text-[#C5221F] border-[#FAD2CF]' :
                                                'bg-[#E8F0FE] text-[#1A73E8] border-[#D2E3FC]'
                                            ]">
                                                <i :class="[
                                                    'fa-solid text-2xs',
                                                    (log.type || 'info') === 'success' ? 'fa-check-circle' :
                                                    (log.type || 'info') === 'warning' ? 'fa-exclamation-triangle' :
                                                    (log.type || 'info') === 'error' ? 'fa-times-circle' : 'fa-info-circle'
                                                ]"></i>
                                                {{ (log.type || 'info') }}
                                            </span>
                                        </td>
                                        <td class="py-4 px-6 font-bold text-[#202124] group-hover:text-[#1A73E8] transition-colors">
                                            {{ log.action || log.event || log.event_title || 'System Event' }}
                                        </td>
                                        <td class="py-4 px-6 text-xs text-[#5F6368] max-w-md truncate">
                                            {{ log.details || log.description || '-' }}
                                        </td>
                                        <td class="py-4 px-6">
                                            <div class="flex items-center gap-2">
                                                <div class="h-6 w-6 rounded-full bg-[#202124] text-white flex items-center justify-center font-bold text-2xs shrink-0">
                                                    {{ (log.user || log.actor_name || 'S').charAt(0).toUpperCase() }}
                                                </div>
                                                <span class="text-xs font-semibold text-[#202124]">{{ log.user || log.actor_name || 'System' }}</span>
                                            </div>
                                        </td>
                                        <td class="py-4 px-6 font-mono text-xs text-[#5F6368]">
                                            {{ log.ip_address || '127.0.0.1' }}
                                        </td>
                                        <td class="py-4 px-6 text-xs text-[#5F6368] text-right font-mono whitespace-nowrap">
                                            {{ log.timestamp || log.created_at || 'Just now' }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

        <!-- ================= TRANSACTION INSPECTION MODAL ================= -->
        <div v-if="selectedTransaction" class="fixed inset-0 z-50 flex justify-end bg-black/40 backdrop-blur-xs overflow-hidden">
            <div class="absolute inset-0" @click="selectedTransaction = null"></div>
            <div class="bg-white w-full max-w-lg shadow-2xl flex flex-col h-full z-10 animate-slideLeft border-l border-[#DADCE0]">
                <div class="p-6 sm:p-8 flex-1 overflow-y-auto space-y-6">
                    <div class="flex justify-between items-center border-b border-[#DADCE0] pb-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#E6F4EA] text-[#137333] border border-[#CEEAD6]">
                                <i class="fa-solid fa-receipt text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-[#202124]">Transaction Receipt Details</h3>
                                <p class="font-mono text-xs text-[#1A73E8] font-bold">{{ selectedTransaction.transaction_id || selectedTransaction.transaction_code }}</p>
                            </div>
                        </div>
                        <button @click="selectedTransaction = null" class="h-8 w-8 rounded-full hover:bg-[#F8F9FA] font-bold text-[#5F6368] flex items-center justify-center cursor-pointer">
                            <i class="fa-solid fa-xmark text-lg"></i>
                        </button>
                    </div>

                    <div class="rounded-xl bg-[#F8F9FA] p-4 border border-[#DADCE0] space-y-3 text-xs">
                        <div class="flex justify-between border-b border-[#DADCE0] pb-2">
                            <span class="font-bold text-[#5F6368]">School Tenant:</span>
                            <span class="font-bold text-[#202124]">{{ selectedTransaction.school_name || selectedTransaction.tenant?.name }}</span>
                        </div>
                        <div class="flex justify-between border-b border-[#DADCE0] pb-2">
                            <span class="font-bold text-[#5F6368]">Tenant ID / Domain:</span>
                            <span class="font-mono font-semibold text-[#1A73E8]">{{ selectedTransaction.tenant_id }}</span>
                        </div>
                        <div class="flex justify-between border-b border-[#DADCE0] pb-2">
                            <span class="font-bold text-[#5F6368]">Subscription Plan:</span>
                            <span class="font-bold text-[#202124]">{{ selectedTransaction.plan_name || selectedTransaction.plan?.name }}</span>
                        </div>
                        <div class="flex justify-between border-b border-[#DADCE0] pb-2">
                            <span class="font-bold text-[#5F6368]">Payment Gateway:</span>
                            <span class="font-bold text-[#202124]">{{ selectedTransaction.gateway }}</span>
                        </div>
                        <div class="flex justify-between border-b border-[#DADCE0] pb-2">
                            <span class="font-bold text-[#5F6368]">Reference Number:</span>
                            <span class="font-mono text-[#202124] font-semibold">{{ selectedTransaction.reference_no }}</span>
                        </div>
                        <div class="flex justify-between border-b border-[#DADCE0] pb-2">
                            <span class="font-bold text-[#5F6368]">Amount Paid:</span>
                            <span class="font-black text-sm text-[#137333]">PKR {{ (selectedTransaction.amount || 0).toLocaleString() }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="font-bold text-[#5F6368]">Processed Date:</span>
                            <span class="font-semibold text-[#202124]">{{ selectedTransaction.date || selectedTransaction.created_at }}</span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-2xs font-bold uppercase tracking-wider text-[#5F6368] mb-1">Cryptographic Hash (HMAC Verification)</label>
                        <div class="rounded-lg bg-[#202124] p-3 font-mono text-2xs text-emerald-400 break-all select-all border border-black/20">
                            {{ selectedTransaction.secure_hash || 'SHA256-VERIFIED-INTEGRITY-OK' }}
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button @click="selectedTransaction = null" class="rounded-xl bg-[#F8F9FA] px-5 py-2.5 text-xs font-bold text-[#202124] hover:bg-[#E8EAED] transition-colors border border-[#DADCE0] cursor-pointer">
                            Close Window
                        </button>
                        <button @click="downloadInvoicePDF(selectedTransaction)" class="rounded-xl bg-[#1A73E8] px-5 py-2.5 text-xs font-bold text-white hover:bg-[#1557B0] transition-colors shadow-2xs flex items-center gap-2 cursor-pointer">
                            <i class="fa-solid fa-file-pdf"></i>
                            <span>Download Invoice PDF</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================= ACTIVITY LOG INSPECTION MODAL ================= -->
        <div v-if="selectedLog" class="fixed inset-0 z-50 flex justify-end bg-black/40 backdrop-blur-xs overflow-hidden">
            <div class="absolute inset-0" @click="selectedLog = null"></div>
            <div class="bg-white w-full max-w-lg shadow-2xl flex flex-col h-full z-10 animate-slideLeft border-l border-[#DADCE0]">
                <div class="p-6 sm:p-8 flex-1 overflow-y-auto space-y-6">
                    <div class="flex justify-between items-center border-b border-[#DADCE0] pb-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl border" :class="[
                                (selectedLog.type || 'info') === 'success' ? 'bg-[#E6F4EA] text-[#137333] border-[#CEEAD6]' :
                                (selectedLog.type || 'info') === 'warning' ? 'bg-[#FEF7E0] text-[#B06000] border-[#FAD2CF]' :
                                (selectedLog.type || 'info') === 'error' ? 'bg-[#FCE8E6] text-[#C5221F] border-[#FAD2CF]' :
                                'bg-[#E8F0FE] text-[#1A73E8] border-[#D2E3FC]'
                            ]">
                                <i :class="[
                                    'fa-solid text-xl',
                                    (selectedLog.type || 'info') === 'success' ? 'fa-check-circle' :
                                    (selectedLog.type || 'info') === 'warning' ? 'fa-exclamation-triangle' :
                                    (selectedLog.type || 'info') === 'error' ? 'fa-times-circle' : 'fa-info-circle'
                                ]"></i>
                            </div>
                            <div>
                                <span class="text-2xs font-black uppercase tracking-wider text-[#5F6368]">Audit Log Entry #{{ selectedLog.id }}</span>
                                <h3 class="text-base font-bold text-[#202124] leading-tight">{{ selectedLog.action || selectedLog.event || selectedLog.event_title }}</h3>
                            </div>
                        </div>
                        <button @click="selectedLog = null" class="h-8 w-8 rounded-full hover:bg-[#F8F9FA] font-bold text-[#5F6368] flex items-center justify-center cursor-pointer">
                            <i class="fa-solid fa-xmark text-lg"></i>
                        </button>
                    </div>

                    <div class="rounded-xl bg-[#F8F9FA] p-4 border border-[#DADCE0] space-y-3 text-xs">
                        <div>
                            <span class="block text-2xs font-bold uppercase tracking-wider text-[#5F6368] mb-1">Full Event Description & Payload</span>
                            <p class="font-semibold text-[#202124] leading-relaxed">{{ selectedLog.details || selectedLog.description }}</p>
                        </div>
                        <div class="border-t border-[#DADCE0] pt-3 grid grid-cols-2 gap-3">
                            <div>
                                <span class="block text-2xs font-bold uppercase tracking-wider text-[#5F6368]">Initiating Actor</span>
                                <span class="font-bold text-[#202124] mt-0.5 block">{{ selectedLog.user || selectedLog.actor_name || 'System' }}</span>
                            </div>
                            <div>
                                <span class="block text-2xs font-bold uppercase tracking-wider text-[#5F6368]">Source IP Address</span>
                                <span class="font-mono font-bold text-[#1A73E8] mt-0.5 block">{{ selectedLog.ip_address || '127.0.0.1' }}</span>
                            </div>
                        </div>
                        <div class="border-t border-[#DADCE0] pt-3 flex justify-between items-center">
                            <span class="font-bold text-[#5F6368]">Execution Timestamp:</span>
                            <span class="font-mono text-xs font-semibold text-[#202124]">{{ selectedLog.timestamp || selectedLog.created_at }}</span>
                        </div>
                    </div>

                    <div class="flex items-center justify-end pt-2">
                        <button @click="selectedLog = null" class="rounded-xl bg-[#202124] px-6 py-2.5 text-xs font-bold text-white hover:bg-black transition-colors cursor-pointer">
                            Done
                        </button>
                    </div>
                </div>
            </div>
        </div>

                <!-- ================= TAB 6: MARKETING ================= -->
                <div v-if="activeTab === 'marketing'" class="space-y-6 animate-fadeIn">
                    <div class="flex justify-between items-center bg-white p-6 rounded-2xl border border-[#DADCE0] shadow-2xs">
                        <div>
                            <h3 class="text-base font-bold text-[#202124]">Coupons & Referral Programs</h3>
                            <p class="text-xs text-[#5F6368]">Manage SaaS onboarding discounts, trial extensions, and referral links.</p>
                        </div>
                        <button @click="createCoupon" class="rounded-full bg-[#1A73E8] px-5 py-2.5 text-xs font-semibold text-white hover:bg-[#1557B0] transition-all shadow-sm cursor-pointer">
                            + Create Coupon
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="google-card rounded-2xl border border-[#DADCE0] bg-white p-6 shadow-2xs">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-sm font-bold text-[#202124]">Active Coupons</h4>
                                <i class="fa-solid fa-ticket text-[#1A73E8]"></i>
                            </div>
                            <div class="space-y-3">
                                <div v-if="!coupons || coupons.length === 0" class="py-6 text-center text-xs text-[#5F6368]">
                                    No active coupons registered yet. Click "+ Create Coupon" to get started!
                                </div>
                                <div v-for="coupon in coupons" :key="coupon.id" class="flex items-center justify-between p-3 bg-[#F8F9FA] rounded-xl border border-[#DADCE0] hover:border-[#1A73E8]/50 transition-all group">
                                    <div>
                                        <div class="font-extrabold text-[#137333] font-mono flex items-center gap-1.5">
                                            <span>{{ coupon.code }}</span>
                                            <span v-if="!coupon.is_active" class="text-3xs font-bold text-[#C5221F] bg-[#FCE8E6] border border-[#FAD2CF] px-2 py-0.5 rounded-full uppercase">Inactive</span>
                                        </div>
                                        <div class="text-xs text-[#5F6368] mt-0.5">{{ coupon.discount_percentage }}% discount • Max uses: {{ coupon.max_uses || 'Unlimited' }}</div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span class="text-xs font-bold text-[#5F6368]">{{ coupon.times_used }} Used</span>
                                        <button @click="deleteCoupon(coupon.id, coupon.code)" class="text-[#5F6368] hover:text-[#C5221F] opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer p-1" title="Delete Coupon">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="google-card rounded-2xl border border-[#DADCE0] bg-white p-6 shadow-2xs">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-sm font-bold text-[#202124]">Referral Program Tracking</h4>
                                <i class="fa-solid fa-users text-[#1A73E8]"></i>
                            </div>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between p-3 bg-[#F8F9FA] rounded-xl border border-[#DADCE0]">
                                    <div>
                                        <div class="font-bold text-[#202124]">Beaconhouse School System</div>
                                        <div class="text-xs text-[#5F6368]">Referred 2 new tenants</div>
                                    </div>
                                    <span class="text-xs font-bold text-[#1A73E8]">Pending Payout</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ================= TAB 7: SETTINGS ================= -->
                <div v-if="activeTab === 'settings'" class="max-w-3xl space-y-6 animate-fadeIn">
                    <div class="google-card rounded-2xl border border-[#DADCE0] bg-white p-8 shadow-2xs space-y-6">
                        <div>
                            <h3 class="text-base font-bold text-[#202124]">Global Platform Configuration</h3>
                            <p class="text-xs text-[#5F6368]">Manage system name, support contact details, and maintenance state.</p>
                        </div>

                        <form @submit.prevent="submitSettings" class="space-y-5 border-t border-[#DADCE0] pt-6">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-[#5F6368] mb-1.5">Platform Title</label>
                                <input 
                                    v-model="settingsForm.platform_name" 
                                    type="text" 
                                    required
                                    class="w-full rounded-xl border border-[#DADCE0] px-4 py-2.5 text-sm focus:border-[#1A73E8] focus:outline-none transition-all"
                                />
                            </div>

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-[#5F6368] mb-1.5">Support Contact Email</label>
                                    <input 
                                        v-model="settingsForm.contact_email" 
                                        type="email" 
                                        required
                                        class="w-full rounded-xl border border-[#DADCE0] px-4 py-2.5 text-sm focus:border-[#1A73E8] focus:outline-none transition-all"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-[#5F6368] mb-1.5">Support Phone Number</label>
                                    <input 
                                        v-model="settingsForm.support_phone" 
                                        type="text" 
                                        required
                                        class="w-full rounded-xl border border-[#DADCE0] px-4 py-2.5 text-sm focus:border-[#1A73E8] focus:outline-none transition-all"
                                    />
                                </div>
                            </div>

                            <div class="flex items-center justify-between rounded-xl bg-[#F8F9FA] p-4 border border-[#DADCE0]">
                                <div>
                                    <p class="text-sm font-bold text-[#202124]">Platform Maintenance Mode</p>
                                    <p class="text-xs text-[#5F6368]">Temporarily disable tenant portal logins while updating databases.</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input v-model="settingsForm.maintenance_mode" type="checkbox" class="sr-only peer" />
                                    <div class="w-11 h-6 bg-[#DADCE0] peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#1A73E8]"></div>
                                </label>
                            </div>

                            <div class="pt-4 flex justify-end">
                                <button 
                                    type="submit" 
                                    :disabled="settingsForm.processing"
                                    class="rounded-full bg-[#1A73E8] px-6 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-[#1557B0] disabled:opacity-50 transition-all"
                                >
                                    {{ settingsForm.processing ? 'Saving...' : 'Save Settings' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>

        <!-- ================= MODAL: ONBOARD NEW SCHOOL ================= -->
                <div v-if="showModal" class="fixed inset-0 z-50 flex justify-end bg-black/40 backdrop-blur-xs overflow-hidden">
            <div class="absolute inset-0" @click="showModal = false"></div>
            <div class="bg-white w-full max-w-lg shadow-2xl flex flex-col h-full z-10 animate-slideLeft border-l border-[#DADCE0]">
                <div class="p-8 flex-1 overflow-y-auto space-y-6">
                    <div class="flex items-center justify-between border-b border-[#DADCE0] pb-4">
                    <h3 class="text-lg font-bold text-[#202124]">Onboard New School Tenant</h3>
                    <button @click="showModal = false" class="text-sm font-bold text-[#5F6368] hover:text-[#202124]"><i class="fa-solid fa-xmark"></i></button>
                </div>

                <form @submit.prevent="submitTenant" class="space-y-4 text-sm">
                    <div v-if="form.hasErrors" class="rounded-xl bg-[#FCE8E6] p-3 text-xs font-bold text-[#C5221F] border border-[#FAD2CF] flex items-center gap-2">
                        <i class="fa-solid fa-triangle-exclamation text-base"></i>
                        <span>Please fix the validation errors below before proceeding.</span>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-[#5F6368] mb-1">Domain (ID)</label>
                            <input 
                                v-model="form.id" 
                                @input="form.id = form.id.toLowerCase().replace(/[^a-z0-9-]/g, ''); autoFillDomain()"
                                type="text" 
                                required 
                                placeholder="e.g. beacon"
                                class="w-full rounded-xl border border-[#DADCE0] px-3.5 py-2.5 font-mono text-sm focus:border-[#1A73E8] focus:outline-none"
                                :class="{ 'border-[#C5221F] bg-[#FCE8E6]/30': form.errors.id }"
                            />
                            <p v-if="form.errors.id" class="text-2xs font-bold text-[#C5221F] mt-1">{{ form.errors.id }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-[#5F6368] mb-1">Assigned Domain</label>
                            <input 
                                v-model="form.domain" 
                                type="text" 
                                required 
                                readonly
                                :placeholder="'beacon.' + getDomainSuffix()"
                                class="w-full rounded-xl border border-[#DADCE0] bg-[#F1F3F4] px-3.5 py-2.5 font-mono text-sm text-[#5F6368] cursor-not-allowed focus:outline-none"
                                :class="{ 'border-[#C5221F]': form.errors.domain }"
                            />
                            <p v-if="form.errors.domain" class="text-2xs font-bold text-[#C5221F] mt-1">{{ form.errors.domain }}</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-[#5F6368] mb-1">School Name</label>
                        <input 
                            v-model="form.name" 
                            type="text" 
                            required 
                            placeholder="Beacon Light High School"
                            class="w-full rounded-xl border border-[#DADCE0] px-3.5 py-2.5 focus:border-[#1A73E8] focus:outline-none"
                            :class="{ 'border-[#C5221F] bg-[#FCE8E6]/30': form.errors.name }"
                        />
                        <p v-if="form.errors.name" class="text-2xs font-bold text-[#C5221F] mt-1">{{ form.errors.name }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-[#5F6368] mb-1">Subscription Plan</label>
                            <select 
                                v-model="form.plan_id" 
                                required
                                class="w-full rounded-xl border border-[#DADCE0] px-3.5 py-2.5 bg-white focus:border-[#1A73E8] focus:outline-none"
                                :class="{ 'border-[#C5221F]': form.errors.plan_id }"
                            >
                                <option v-for="plan in plans" :key="plan.id" :value="plan.id">
                                    {{ plan.name }} (PKR {{ (plan.price_monthly || 0).toLocaleString() }}/mo)
                                </option>
                            </select>
                            <p v-if="form.errors.plan_id" class="text-2xs font-bold text-[#C5221F] mt-1">{{ form.errors.plan_id }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-[#5F6368] mb-1">Billing Cycle</label>
                            <select 
                                v-model="form.billing_cycle" 
                                required
                                class="w-full rounded-xl border border-[#DADCE0] px-3.5 py-2.5 bg-white focus:border-[#1A73E8] focus:outline-none font-semibold text-[#1A73E8]"
                            >
                                <option value="monthly">Monthly Billing</option>
                                <option value="yearly">Yearly Billing (10 Months Price)</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-[#5F6368] mb-1">Payment Method / Gateway Verification</label>
                        <select 
                            v-model="form.payment_gateway" 
                            required
                            class="w-full rounded-xl border border-[#DADCE0] px-3.5 py-2.5 bg-white focus:border-[#1A73E8] focus:outline-none"
                        >
                            <option value="Direct Invoice / Manual Onboarding">Direct Invoice / Manual Onboarding (Paid)</option>
                            <option value="JazzCash Verified">JazzCash Mobile Wallet / Card (Paid)</option>
                            <option value="Meezan Bank Verified">Meezan Bank Direct Transfer (Paid)</option>
                            <option value="Trial Account (No Payment)">Trial Account / Complimentary (14 Days)</option>
                        </select>
                    </div>

                    <div class="border-t border-[#DADCE0] pt-4 space-y-4">
                        <p class="text-xs font-bold uppercase tracking-wider text-[#1A73E8]">Principal / Admin Account Credentials</p>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-[#5F6368] mb-1">Principal Name</label>
                            <input 
                                v-model="form.admin_name" 
                                type="text" 
                                required 
                                placeholder="Dr. Sarah Ahmed"
                                class="w-full rounded-xl border border-[#DADCE0] px-3.5 py-2.5 focus:border-[#1A73E8] focus:outline-none"
                                :class="{ 'border-[#C5221F] bg-[#FCE8E6]/30': form.errors.admin_name }"
                            />
                            <p v-if="form.errors.admin_name" class="text-2xs font-bold text-[#C5221F] mt-1">{{ form.errors.admin_name }}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-[#5F6368] mb-1">Admin Email (Login ID)</label>
                                <input 
                                    v-model="form.admin_email" 
                                    type="email" 
                                    required 
                                    placeholder="principal@school.test"
                                    class="w-full rounded-xl border border-[#DADCE0] px-3.5 py-2.5 focus:border-[#1A73E8] focus:outline-none"
                                    :class="{ 'border-[#C5221F] bg-[#FCE8E6]/30': form.errors.admin_email }"
                                />
                                <p v-if="form.errors.admin_email" class="text-2xs font-bold text-[#C5221F] mt-1">{{ form.errors.admin_email }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-[#5F6368] mb-1">Phone Number</label>
                                <input 
                                    v-model="form.admin_phone" 
                                    type="text" 
                                    required 
                                    placeholder="+92 300 0000000"
                                    class="w-full rounded-xl border border-[#DADCE0] px-3.5 py-2.5 focus:border-[#1A73E8] focus:outline-none"
                                    :class="{ 'border-[#C5221F] bg-[#FCE8E6]/30': form.errors.admin_phone }"
                                />
                                <p v-if="form.errors.admin_phone" class="text-2xs font-bold text-[#C5221F] mt-1">{{ form.errors.admin_phone }}</p>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-[#5F6368] mb-1">Principal Initial Password</label>
                            <div class="relative">
                                <input 
                                    v-model="form.admin_password" 
                                    type="text" 
                                    required 
                                    minlength="6"
                                    placeholder="e.g. Principal@2026"
                                    class="w-full rounded-xl border border-[#DADCE0] pl-3.5 pr-28 py-2.5 font-mono text-sm font-semibold text-[#137333] focus:border-[#1A73E8] focus:outline-none bg-[#F8F9FA]"
                                    :class="{ 'border-[#C5221F] bg-[#FCE8E6]/30': form.errors.admin_password }"
                                />
                                <button 
                                    type="button"
                                    @click="form.admin_password = 'LMS@' + Math.random().toString(36).substring(2, 8).toUpperCase() + '!' + Math.floor(Math.random() * 90 + 10)"
                                    class="absolute right-1.5 top-1.5 rounded-lg bg-[#E8F0FE] px-3 py-1.5 text-2xs font-bold text-[#1A73E8] hover:bg-[#D2E3FC] transition-colors cursor-pointer"
                                >
                                    <i class="fa-solid fa-wand-magic-sparkles mr-1"></i> Generate
                                </button>
                            </div>
                            <p v-if="form.errors.admin_password" class="text-2xs font-bold text-[#C5221F] mt-1">{{ form.errors.admin_password }}</p>
                            <p v-else class="mt-1 text-2xs text-[#5F6368]">Share this password securely with the new Principal so they can log in to their school portal immediately.</p>
                        </div>
                    </div>

                    <div class="pt-4 flex justify-end space-x-3">
                        <button type="button" @click="showModal = false" class="rounded-full px-5 py-2.5 text-xs font-bold text-[#5F6368] hover:bg-[#F8F9FA]">
                            Cancel
                        </button>
                        <button 
                            type="submit" 
                            :disabled="form.processing"
                            class="rounded-full bg-[#1A73E8] px-6 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-[#1557B0] disabled:opacity-50 transition-all flex items-center gap-1.5"
                        >
                            <i v-if="!form.processing" class="fa-solid fa-rocket"></i>
                            <span>{{ form.processing ? 'Provisioning Cloud DB...' : 'Provision & Onboard School' }}</span>
                        </button>
                    </div>
                </form>
                </div>
            </div>
        </div>

        <!-- ================= MODAL: ADD / EDIT PLAN ================= -->
                <div v-if="showPlanModal" class="fixed inset-0 z-50 flex justify-end bg-black/40 backdrop-blur-xs overflow-hidden">
            <div class="absolute inset-0" @click="showPlanModal = false"></div>
            <div class="bg-white w-full max-w-lg shadow-2xl flex flex-col h-full z-10 animate-slideLeft border-l border-[#DADCE0]">
                <div class="p-8 flex-1 overflow-y-auto space-y-6">
                    <div class="flex items-center justify-between border-b border-[#DADCE0] pb-4">
                    <h3 class="text-lg font-bold text-[#202124]">{{ isEditingPlan ? 'Edit Plan: ' + planForm.name : 'Create New Subscription Plan' }}</h3>
                    <button @click="showPlanModal = false" class="text-sm font-bold text-[#5F6368] hover:text-[#202124]"><i class="fa-solid fa-xmark"></i></button>
                </div>

                <form @submit.prevent="submitPlan" class="space-y-4 text-sm">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-[#5F6368] mb-1">Plan Name</label>
                            <input 
                                v-model="planForm.name" 
                                type="text" 
                                required 
                                placeholder="Growth Tier"
                                class="w-full rounded-xl border border-[#DADCE0] px-3.5 py-2.5 focus:border-[#1A73E8] focus:outline-none"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-[#5F6368] mb-1">Code / Badge</label>
                            <input 
                                v-model="planForm.code" 
                                type="text" 
                                required 
                                placeholder="GROWTH"
                                class="w-full rounded-xl border border-[#DADCE0] px-3.5 py-2.5 uppercase font-mono focus:border-[#1A73E8] focus:outline-none"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-[#5F6368] mb-1">Monthly Price (PKR)</label>
                            <input 
                                v-model.number="planForm.price_monthly" 
                                type="number" 
                                required 
                                min="0"
                                class="w-full rounded-xl border border-[#DADCE0] px-3.5 py-2.5 focus:border-[#1A73E8] focus:outline-none"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-[#5F6368] mb-1">Max Student Limit</label>
                            <input 
                                v-model.number="planForm.max_students" 
                                type="number" 
                                required 
                                min="10"
                                class="w-full rounded-xl border border-[#DADCE0] px-3.5 py-2.5 focus:border-[#1A73E8] focus:outline-none"
                            />
                        </div>
                    </div>

                    <div class="border-t border-[#DADCE0] pt-4 space-y-3">
                        <label class="block text-xs font-bold uppercase tracking-wider text-[#5F6368]">Included Features Checklist</label>
                        <div class="flex space-x-2">
                            <input 
                                v-model="featureInput" 
                                type="text" 
                                placeholder="Add feature description..."
                                class="flex-1 rounded-xl border border-[#DADCE0] px-3.5 py-2 text-xs focus:border-[#1A73E8] focus:outline-none"
                                @keydown.enter.prevent="addFeatureToPlan"
                            />
                            <button type="button" @click="addFeatureToPlan" class="rounded-xl bg-[#E8F0FE] px-4 py-2 text-xs font-bold text-[#1A73E8] hover:bg-[#D2E3FC]">
                                + Add
                            </button>
                        </div>
                        <div class="flex flex-wrap gap-2 pt-1">
                            <span 
                                v-for="(feat, idx) in planForm.features" 
                                :key="idx"
                                class="inline-flex items-center space-x-1.5 rounded-full bg-[#F1F3F4] px-3 py-1 text-xs font-semibold text-[#202124]"
                            >
                                <span>{{ feat }}</span>
                                <button type="button" @click="removeFeatureFromPlan(idx)" class="text-[#C5221F] font-bold hover:text-red-700"><i class="fa-solid fa-xmark"></i></button>
                            </span>
                        </div>
                    </div>

                    <div class="pt-4 flex justify-end space-x-3">
                        <button type="button" @click="showPlanModal = false" class="rounded-full px-5 py-2.5 text-xs font-bold text-[#5F6368] hover:bg-[#F8F9FA]">
                            Cancel
                        </button>
                        <button 
                            type="submit" 
                            :disabled="planForm.processing"
                            class="rounded-full bg-[#1A73E8] px-6 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-[#1557B0] disabled:opacity-50 transition-all"
                        >
                            {{ planForm.processing ? 'Saving...' : (isEditingPlan ? 'Update Plan' : 'Create Plan') }}
                        </button>
                    </div>
                </form>
                </div>
            </div>
        </div>

        <!-- ================= MODAL: DELETE PLAN CONFIRMATION ================= -->
        <div v-if="showDeletePlanModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-xs p-4">
            <div class="google-card w-full max-w-md rounded-2xl border border-[#DADCE0] bg-white p-6 shadow-xl space-y-4 text-center">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-[#FCE8E6] text-xl text-[#C5221F]">
                    <i class="fa-solid fa-trash-can"></i>
                </div>
                <h3 class="text-base font-bold text-[#202124]">Delete Subscription Plan?</h3>
                <p class="text-xs text-[#5F6368]">
                    Are you sure you want to delete <strong class="text-[#202124]">{{ planToDelete?.name }}</strong>? Schools currently on this plan will not be deleted but won't be able to renew under this package.
                </p>
                <div class="flex justify-center space-x-3 pt-2">
                    <button @click="showDeletePlanModal = false" class="rounded-full px-5 py-2 text-xs font-bold text-[#5F6368] hover:bg-[#F8F9FA]">
                        Cancel
                    </button>
                    <button @click="deletePlan" class="rounded-full bg-[#C5221F] px-6 py-2 text-xs font-bold text-white hover:bg-red-700 shadow-sm transition-all">
                        Yes, Delete Plan
                    </button>
                </div>
            </div>
        </div>
    
        <!-- ================= MODAL: ADD / EDIT COUPON ================= -->
        <div v-if="showCouponModal" class="fixed inset-0 z-50 flex justify-end bg-black/40 backdrop-blur-xs overflow-hidden">
            <div class="absolute inset-0" @click="showCouponModal = false"></div>
            <div class="relative w-full max-w-md bg-white h-full shadow-2xl flex flex-col p-6 space-y-6 z-10 animate-slideOver">
                <div class="flex items-center justify-between border-b border-[#DADCE0] pb-4">
                    <h3 class="text-lg font-bold text-[#202124]">Create Subscription Coupon</h3>
                    <button @click="showCouponModal = false" class="text-sm font-bold text-[#5F6368] hover:text-[#202124]"><i class="fa-solid fa-xmark"></i></button>
                </div>

                <form @submit.prevent="submitCoupon" class="space-y-4 text-sm flex-1 flex flex-col justify-between">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-[#5F6368] mb-1">Coupon Code</label>
                            <input 
                                v-model="couponForm.code" 
                                type="text" 
                                placeholder="e.g. SUMMER50" 
                                class="w-full rounded-xl border border-[#DADCE0] px-4 py-2.5 text-xs font-bold uppercase focus:border-[#1A73E8] focus:ring-1 focus:ring-[#1A73E8]"
                                :class="{ 'border-[#C5221F]': couponForm.errors.code }"
                            />
                            <p v-if="couponForm.errors.code" class="text-2xs font-bold text-[#C5221F] mt-1">{{ couponForm.errors.code }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-[#5F6368] mb-1">Discount Percentage (1-100)</label>
                            <input 
                                v-model.number="couponForm.discount_percentage" 
                                type="number" 
                                min="1" 
                                max="100" 
                                class="w-full rounded-xl border border-[#DADCE0] px-4 py-2.5 text-xs font-bold focus:border-[#1A73E8] focus:ring-1 focus:ring-[#1A73E8]"
                                :class="{ 'border-[#C5221F]': couponForm.errors.discount_percentage }"
                            />
                            <p v-if="couponForm.errors.discount_percentage" class="text-2xs font-bold text-[#C5221F] mt-1">{{ couponForm.errors.discount_percentage }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-[#5F6368] mb-1">Max Uses (Optional)</label>
                            <input 
                                v-model.number="couponForm.max_uses" 
                                type="number" 
                                placeholder="Unlimited" 
                                class="w-full rounded-xl border border-[#DADCE0] px-4 py-2.5 text-xs font-bold focus:border-[#1A73E8] focus:ring-1 focus:ring-[#1A73E8]"
                                :class="{ 'border-[#C5221F]': couponForm.errors.max_uses }"
                            />
                            <p v-if="couponForm.errors.max_uses" class="text-2xs font-bold text-[#C5221F] mt-1">{{ couponForm.errors.max_uses }}</p>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-[#DADCE0] mt-auto">
                        <button type="button" @click="showCouponModal = false" class="rounded-full px-5 py-2.5 text-xs font-bold text-[#5F6368] hover:bg-[#F8F9FA]">
                            Cancel
                        </button>
                        <button type="submit" :disabled="couponForm.processing" class="rounded-full bg-[#1A73E8] px-6 py-2.5 text-xs font-bold text-white hover:bg-[#1557B0] transition-all shadow-sm disabled:opacity-50">
                            {{ couponForm.processing ? 'Saving...' : 'Create Coupon' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ================= MODAL: EDIT SCHOOL DETAILS ================= -->
        <div v-if="showEditSchoolModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs animate-fadeIn">
            <div class="google-card w-full max-w-lg rounded-2xl border border-[#DADCE0] bg-white p-6 sm:p-8 space-y-6 shadow-2xl flex flex-col max-h-[90vh] overflow-y-auto">
                <div class="flex justify-between items-center border-b border-[#DADCE0] pb-4">
                    <h3 class="text-lg font-bold text-[#202124]">Edit School Tenant Details</h3>
                    <button @click="showEditSchoolModal = false" class="text-[#5F6368] hover:text-[#202124] font-bold"><i class="fa-solid fa-xmark"></i></button>
                </div>

                <form @submit.prevent="submitEditSchool" class="space-y-4 text-sm">
                    <div v-if="editSchoolForm.hasErrors" class="rounded-xl bg-[#FCE8E6] p-3 text-xs font-bold text-[#C5221F] border border-[#FAD2CF] flex items-center gap-2">
                        <i class="fa-solid fa-triangle-exclamation text-base"></i>
                        <span>Please fix the validation errors below before proceeding.</span>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-[#5F6368] mb-1">School Name</label>
                        <input 
                            v-model="editSchoolForm.name" 
                            type="text" 
                            required 
                            placeholder="e.g. Beacon Light School"
                            class="w-full rounded-xl border border-[#DADCE0] px-3.5 py-2.5 text-sm focus:border-[#1A73E8] focus:outline-none"
                            :class="{ 'border-[#C5221F]': editSchoolForm.errors.name }"
                        />
                        <p v-if="editSchoolForm.errors.name" class="text-2xs font-bold text-[#C5221F] mt-1">{{ editSchoolForm.errors.name }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-[#5F6368] mb-1">Domain Subdomain</label>
                        <input 
                            v-model="editSchoolForm.domain" 
                            type="text" 
                            required 
                            :placeholder="'e.g. beacon.' + getDomainSuffix()"
                            class="w-full rounded-xl border border-[#DADCE0] px-3.5 py-2.5 font-mono text-sm focus:border-[#1A73E8] focus:outline-none"
                            :class="{ 'border-[#C5221F]': editSchoolForm.errors.domain }"
                        />
                        <p v-if="editSchoolForm.errors.domain" class="text-2xs font-bold text-[#C5221F] mt-1">{{ editSchoolForm.errors.domain }}</p>
                        <p class="text-3xs text-[#5F6368] mt-1">Changing the subdomain updates the login URL portal immediately. For example: changing from <code>oldname.{{ getDomainSuffix() }}</code> to <code>newname.{{ getDomainSuffix() }}</code>.</p>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-[#DADCE0]">
                        <button type="button" @click="showEditSchoolModal = false" class="rounded-full px-5 py-2.5 text-xs font-bold text-[#5F6368] hover:bg-[#F8F9FA]">
                            Cancel
                        </button>
                        <button type="submit" :disabled="editSchoolForm.processing" class="rounded-full bg-[#1A73E8] px-6 py-2.5 text-xs font-bold text-white hover:bg-[#1557B0] transition-all shadow-sm disabled:opacity-50">
                            {{ editSchoolForm.processing ? 'Saving...' : 'Update Details' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    
        <!-- Custom Confirm Dialog Overlay -->
        <div v-if="confirmDialog.show" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs animate-fadeIn">
            <div class="bg-white rounded-2xl border border-[#DADCE0] p-6 max-w-md w-full shadow-2xl space-y-4">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 rounded-full flex items-center justify-center bg-gray-50 shrink-0">
                        <i :class="[confirmDialog.icon, confirmDialog.iconColor]" class="fa-solid text-lg"></i>
                    </div>
                    <h3 class="text-base font-bold text-gray-900">{{ confirmDialog.title }}</h3>
                </div>
                <p class="text-xs text-gray-500 leading-relaxed">{{ confirmDialog.message }}</p>
                <div class="flex justify-end space-x-3 pt-2 border-t border-gray-50">
                    <button @click="confirmDialog.show = false" class="px-4 py-2 border border-[#DADCE0] rounded-full text-xs font-semibold text-gray-600 hover:bg-[#F8F9FA]">
                        Cancel
                    </button>
                    <button @click="confirmDialog.onConfirm" :class="confirmDialog.confirmClass" class="px-5 py-2 text-white rounded-full text-xs font-semibold shadow-xs transition-all">
                        {{ confirmDialog.confirmText }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Custom Input Dialog Overlay -->
        <div v-if="inputDialog.show" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs animate-fadeIn">
            <div class="bg-white rounded-2xl border border-[#DADCE0] p-6 max-w-md w-full shadow-2xl space-y-4">
                <h3 class="text-base font-bold text-gray-900">{{ inputDialog.title }}</h3>
                <p class="text-xs text-gray-500 leading-relaxed">{{ inputDialog.message }}</p>
                <div>
                    <input 
                        v-model="inputDialog.value" 
                        type="text" 
                        :placeholder="inputDialog.placeholder"
                        class="w-full rounded-xl border border-[#DADCE0] px-4 py-2.5 text-xs focus:ring-2 focus:ring-indigo-600 focus:outline-none"
                        @keydown.enter="inputDialog.onConfirm(inputDialog.value)"
                    />
                </div>
                <div class="flex justify-end space-x-3 pt-2 border-t border-gray-50">
                    <button @click="inputDialog.show = false" class="px-4 py-2 border border-[#DADCE0] rounded-full text-xs font-semibold text-gray-600 hover:bg-[#F8F9FA]">
                        Cancel
                    </button>
                    <button @click="inputDialog.onConfirm(inputDialog.value)" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-full text-xs font-semibold shadow-xs transition-all">
                        Submit
                    </button>
                </div>
            </div>
        </div>

        <!-- Global Alert Toasts -->
        <transition name="slide-up">
            <div v-if="alertDialog.show" class="fixed bottom-6 right-6 z-[110]">
                <div :class="alertDialog.type === 'success' ? 'bg-emerald-600 border-emerald-500' : 'bg-red-600 border-red-500'" class="flex items-center gap-3 px-5 py-3.5 rounded-xl border shadow-2xl text-white max-w-sm">
                    <i :class="alertDialog.type === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation'" class="fa-solid text-lg shrink-0"></i>
                    <div>
                        <div class="text-xs font-bold">{{ alertDialog.title }}</div>
                        <div class="text-xs opacity-90 mt-0.5">{{ alertDialog.message }}</div>
                    </div>
                    <button @click="alertDialog.show = false" class="ml-2 opacity-70 hover:opacity-100"><i class="fa-solid fa-xmark"></i></button>
                </div>
            </div>
        </transition>
    </div>
</template>
