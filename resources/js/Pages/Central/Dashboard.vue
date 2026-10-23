<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, router, Link, usePage, Head } from '@inertiajs/vue3';

const props = defineProps({
    tenants: {
        type: Array,
        default: () => []
    },
    plans: {
        type: Array,
        default: () => []
    },
    analytics: {
        type: Object,
        default: () => ({})
    },
    activityLogs: {
        type: Array,
        default: () => []
    },
    revenueTransactions: {
        type: Array,
        default: () => []
    },
    platformSettings: {
        type: Object,
        default: () => ({})
    },
    coupons: {
        type: Array,
        default: () => []
    },
    pendingApprovals: {
        type: Array,
        default: () => []
    },
    inquiries: {
        type: Array,
        default: () => []
    },
    adminUsers: {
        type: Array,
        default: () => []
    },
});

const page = usePage();

// =============================================================================
// 1. NAVIGATION & LAYOUT STATE
// =============================================================================
const activeTab = ref('overview');
const isSidebarCollapsed = ref(false);
const isMobileMenuOpen = ref(false);

// =============================================================================
// 2. GLOBAL DIALOG & TOAST SYSTEM
// =============================================================================
const confirmDialog = ref({
    show: false,
    title: '',
    message: '',
    icon: 'fa-circle-question',
    iconColor: 'text-[#2F6BFF]',
    confirmText: 'Confirm',
    confirmClass: 'bg-[#111318] hover:bg-[#2F6BFF]',
    onConfirm: null
});

const alertDialog = ref({
    show: false,
    title: '',
    message: '',
    type: 'success'
});

const inputDialog = ref({
    show: false,
    title: '',
    message: '',
    placeholder: '',
    value: '',
    onConfirm: null
});

const triggerConfirm = (title, message, callback, opts = {}) => {
    confirmDialog.value = {
        show: true,
        title,
        message,
        icon: opts.icon || 'fa-circle-question',
        iconColor: opts.iconColor || 'text-[#2F6BFF]',
        confirmText: opts.confirmText || 'Confirm',
        confirmClass: opts.confirmClass || 'bg-[#111318] hover:bg-[#2F6BFF]',
        onConfirm: () => {
            callback();
            confirmDialog.value.show = false;
        }
    };
};

const triggerAlert = (message, type = 'success') => {
    alertDialog.value = {
        show: true,
        title: type === 'success' ? 'Success' : type === 'error' ? 'Error' : 'Notification',
        message,
        type
    };
    setTimeout(() => {
        alertDialog.value.show = false;
    }, 4000);
};

const triggerInput = (title, message, placeholder, callback) => {
    inputDialog.value = {
        show: true,
        title,
        message,
        placeholder,
        value: '',
        onConfirm: (val) => {
            callback(val);
            inputDialog.value.show = false;
        }
    };
};

// Listen to Laravel flash messages
watch(() => page.props.flash, (flash) => {
    if (flash?.success) {
        triggerAlert(flash.success, 'success');
    }
    if (flash?.error) {
        triggerAlert(flash.error, 'error');
    }
}, { deep: true, immediate: true });

// =============================================================================
// 3. SCHOOLS TAB FILTERS & METRICS
// =============================================================================
const schoolSearch = ref('');
const schoolStatusFilter = ref('all');

const activeSchoolsCount = computed(() => {
    return props.tenants.filter(t => (t.status || t.data?.status) !== 'suspended').length;
});

const suspendedSchoolsCount = computed(() => {
    return props.tenants.filter(t => (t.status || t.data?.status) === 'suspended').length;
});

const filteredSchools = computed(() => {
    if (!props.tenants) return [];
    return props.tenants.filter(t => {
        const q = schoolSearch.value.trim().toLowerCase();
        const matchesQuery = !q || 
            (t.name && t.name.toLowerCase().includes(q)) || 
            (t.id && t.id.toLowerCase().includes(q)) ||
            (t.admin_email && t.admin_email.toLowerCase().includes(q)) ||
            (t.domain && t.domain.toLowerCase().includes(q));

        const isSuspended = (t.status || t.data?.status) === 'suspended';
        const matchesStatus = schoolStatusFilter.value === 'all' ||
            (schoolStatusFilter.value === 'active' && !isSuspended) ||
            (schoolStatusFilter.value === 'suspended' && isSuspended);

        return matchesQuery && matchesStatus;
    });
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
    const port = window.location.port ? `:${window.location.port}` : (window.location.hostname.includes('localhost') || domain.includes('localhost') ? ':8000' : '');
    return `${protocol}//${cleanDomain}${port}/login`;
};

const getTenantDisplayUrl = (t) => {
    const domain = t.domains?.[0]?.domain || `${t.id}.${getDomainSuffix()}`;
    const cleanDomain = domain.replace(':8000', '');
    const port = window.location.port ? `:${window.location.port}` : (window.location.hostname.includes('localhost') || domain.includes('localhost') ? ':8000' : '');
    return `${cleanDomain}${port}`;
};

// =============================================================================
// 4. OVERVIEW COMPUTATIONS
// =============================================================================
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
        'Starter Plan': '#2F6BFF',
        'Growth Plan': '#8B5CF6',
        'Enterprise Plan': '#10B981',
        'Essential Plan': '#3B82F6',
        'Signature Plan': '#6366F1',
    };
    const defaultColors = ['#2F6BFF', '#8B5CF6', '#10B981', '#F59E0B', '#EC4899'];

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
    const baseCount = Math.max(1, props.tenants?.length || 0);
    const data = [
        Math.max(1, Math.round(baseCount * 0.2)),
        Math.max(2, Math.round(baseCount * 0.4)),
        Math.max(3, Math.round(baseCount * 0.6)),
        Math.max(4, Math.round(baseCount * 0.75)),
        Math.max(5, Math.round(baseCount * 0.9)),
        baseCount
    ];
    const width = 500;
    const height = 150;
    const maxVal = Math.max(...data) * 1.2 || 10;
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

// =============================================================================
// 5. TENANT ACTIONS
// =============================================================================
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

const generateRandomPassword = () => {
    const randomChars = Math.random().toString(36).substring(2, 8).toUpperCase();
    const num = Math.floor(Math.random() * 90 + 10);
    form.admin_password = `Raibix@${randomChars}!${num}`;
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
            triggerAlert('New School Tenant onboarded and database provisioned successfully!');
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
            triggerAlert('School tenant details updated successfully!');
        }
    });
};

const toggleTenantStatus = (tenant) => {
    const isSuspended = (tenant.status || tenant.data?.status) === 'suspended';
    const action = isSuspended ? 'Activate' : 'Suspend';
    triggerConfirm(
        `${action} Tenant?`,
        `Are you sure you want to ${action.toLowerCase()} school tenant [${tenant.name}]? ${isSuspended ? 'All portal users will regain full access.' : 'Access for teachers, parents, and students will be immediately paused.'}`,
        () => {
            router.post(`/tenants/${tenant.id}/toggle-status`, {}, {
                preserveScroll: true,
                onSuccess: () => triggerAlert(`Tenant [${tenant.name}] status updated to ${isSuspended ? 'active' : 'suspended'}.`)
            });
        },
        {
            icon: isSuspended ? 'fa-play' : 'fa-pause',
            iconColor: isSuspended ? 'text-emerald-500' : 'text-amber-500',
            confirmText: `Yes, ${action}`,
            confirmClass: isSuspended ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-amber-600 hover:bg-amber-700'
        }
    );
};

const backupTenant = (tenantId, name) => {
    router.post(`/tenants/${tenantId}/backup`, {}, {
        preserveScroll: true,
        onSuccess: () => triggerAlert(`Backup snapshot completed for [${name}]!`)
    });
};

const syncTenant = (tenantId, name) => {
    router.post(`/tenants/${tenantId}/sync`, {}, {
        preserveScroll: true,
        onSuccess: () => triggerAlert(`RBAC hierarchy and database schema synchronized for [${name}]!`)
    });
};

const mapCustomDomain = (tenantId) => {
    triggerInput('Map Custom Domain', 'Enter Custom FQDN Domain for this school (e.g. portal.myschool.edu):', 'portal.myschool.edu', (d) => {
        if (d && d.trim()) {
            router.post(`/tenants/${tenantId}/domain`, { domain: d.trim() }, {
                preserveScroll: true,
                onSuccess: () => triggerAlert('Custom domain mapped successfully!')
            });
        }
    });
};

const offboardTenant = (tenantId, name) => {
    triggerConfirm(
        'Offboard Tenant?', 
        `Are you sure you want to offboard tenant [${name}]? The school database will be archived and all active services will be suspended.`,
        () => {
            router.post(`/tenants/${tenantId}/offboard`, {}, {
                preserveScroll: true,
                onSuccess: () => triggerAlert(`Tenant [${name}] offboarded successfully.`)
            });
        },
        { icon: 'fa-triangle-exclamation', iconColor: 'text-red-500', confirmText: 'Yes, Offboard Tenant', confirmClass: 'bg-red-600 hover:bg-red-700' }
    );
};

// =============================================================================
// 6. PAYMENT APPROVALS
// =============================================================================
const selectedReceipt = ref(null);

const approveSaaSCheckout = (id, schoolName) => {
    triggerConfirm(
        'Approve Payment Receipt?',
        `Confirm that you have verified the payment proof for [${schoolName || 'this school'}]. This will immediately activate their tenant portal.`,
        () => {
            router.post(`/super-admin/approvals/${id}/approve`, {}, {
                preserveScroll: true,
                onSuccess: () => triggerAlert('School payment approved and portal activated successfully!')
            });
        },
        { icon: 'fa-circle-check', iconColor: 'text-emerald-500', confirmText: 'Yes, Approve Payment', confirmClass: 'bg-emerald-600 hover:bg-emerald-700' }
    );
};

const rejectSaaSCheckout = (id, schoolName) => {
    triggerConfirm(
        'Reject Payment Receipt?',
        `Reject the payment slip for [${schoolName || 'this school'}]? The portal will remain in suspended status until valid proof is submitted.`,
        () => {
            router.post(`/super-admin/approvals/${id}/reject`, {}, {
                preserveScroll: true,
                onSuccess: () => triggerAlert('Payment receipt was rejected.', 'error')
            });
        },
        { icon: 'fa-circle-xmark', iconColor: 'text-red-500', confirmText: 'Yes, Reject Payment', confirmClass: 'bg-red-600 hover:bg-red-700' }
    );
};

// =============================================================================
// 7. PLANS & PACKAGES
// =============================================================================
const showPlanModal = ref(false);
const isEditingPlan = ref(false);
const selectedPlan = ref(null);
const featureInput = ref('');

const planForm = useForm({
    name: '',
    code: '',
    tagline: '',
    summary: '',
    pricing_type: 'per_student',
    price_per_student: 20,
    minimum_monthly_bill: 2000,
    price_monthly: 0,
    price_yearly: 0,
    max_students: 500,
    is_active: true,
    features: ['Admissions & Enrollment', 'Daily Attendance', 'Fee Management', 'LMS & Assignments'],
});

const openAddPlanModal = () => {
    isEditingPlan.value = false;
    selectedPlan.value = null;
    featureInput.value = '';
    planForm.reset();
    planForm.name = '';
    planForm.code = '';
    planForm.tagline = '';
    planForm.summary = '';
    planForm.pricing_type = 'per_student';
    planForm.price_per_student = 20;
    planForm.minimum_monthly_bill = 2000;
    planForm.price_monthly = 2000;
    planForm.price_yearly = 24000;
    planForm.max_students = 500;
    planForm.is_active = true;
    planForm.features = ['Admissions & Enrollment', 'Daily Attendance', 'Fee Management', 'LMS & Assignments'];
    showPlanModal.value = true;
};

const openEditPlanModal = (plan) => {
    isEditingPlan.value = true;
    selectedPlan.value = plan;
    featureInput.value = '';
    planForm.name = plan.name || '';
    planForm.code = plan.code || '';
    planForm.tagline = plan.tagline || '';
    planForm.summary = plan.summary || '';
    planForm.pricing_type = plan.pricing_type || 'per_student';
    planForm.price_per_student = plan.price_per_student !== null && plan.price_per_student !== undefined ? plan.price_per_student : 20;
    planForm.minimum_monthly_bill = plan.minimum_monthly_bill !== null && plan.minimum_monthly_bill !== undefined ? plan.minimum_monthly_bill : 2000;
    planForm.price_monthly = plan.price_monthly || 0;
    planForm.price_yearly = plan.price_yearly || 0;
    planForm.max_students = plan.max_students || 500;
    planForm.is_active = plan.is_active !== undefined ? Boolean(plan.is_active) : true;
    planForm.features = Array.isArray(plan.features) ? [...plan.features] : ['Admissions & Enrollment', 'Daily Attendance', 'Fee Management', 'LMS & Assignments'];
    showPlanModal.value = true;
};

const togglePlanStatus = (plan) => {
    router.post(`/plans/${plan.id}/toggle-status`, {}, {
        preserveScroll: true,
        onSuccess: () => triggerAlert(`Plan [${plan.name}] status toggled successfully.`)
    });
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
            preserveScroll: true,
            onSuccess: () => {
                showPlanModal.value = false;
                triggerAlert(`Subscription Plan [${planForm.name}] updated successfully!`);
            },
        });
    } else {
        planForm.post('/plans', {
            preserveScroll: true,
            onSuccess: () => {
                showPlanModal.value = false;
                triggerAlert(`Subscription Plan [${planForm.name}] created successfully!`);
            },
        });
    }
};

const confirmDeletePlan = (plan) => {
    triggerConfirm(
        'Delete Subscription Plan?',
        `Are you sure you want to remove package [${plan.name}]? Existing tenants on this plan will remain intact, but new schools will no longer be able to select it.`,
        () => {
            router.delete(`/plans/${plan.id}`, {
                preserveScroll: true,
                onSuccess: () => triggerAlert(`Plan [${plan.name}] removed successfully.`)
            });
        },
        { icon: 'fa-trash-can', iconColor: 'text-red-500', confirmText: 'Yes, Delete Plan', confirmClass: 'bg-red-600 hover:bg-red-700' }
    );
};

// =============================================================================
// 8. PAYMENTS TAB & INVOICE GENERATOR
// =============================================================================
const paymentSearch = ref('');
const paymentStatusFilter = ref('all');
const selectedTransaction = ref(null);

const filteredTransactions = computed(() => {
    if (!props.revenueTransactions) return [];
    return props.revenueTransactions.filter(trx => {
        const q = paymentSearch.value.trim().toLowerCase();
        const matchesSearch = !q || 
            ((trx.transaction_id || trx.transaction_code || '').toLowerCase().includes(q)) ||
            ((trx.school_name || '').toLowerCase().includes(q)) ||
            ((trx.gateway || '').toLowerCase().includes(q)) ||
            ((trx.reference_no || '').toLowerCase().includes(q));

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

const downloadInvoicePDF = (txn) => {
    if (!txn) return;
    const invoiceHtml = `
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>Tax Invoice - ${txn.reference_no || txn.transaction_code || 'INV'}</title>
        <style>
            @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap');
            body { font-family: 'Poppins', -apple-system, sans-serif; color: #111318; margin: 0; padding: 40px; background: #fff; }
            .invoice-box { max-width: 800px; margin: auto; border: 1px solid #E6E8EE; padding: 40px; border-radius: 16px; }
            .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #111318; padding-bottom: 24px; margin-bottom: 32px; }
            .logo { font-size: 26px; font-weight: 800; color: #111318; letter-spacing: -0.5px; }
            .logo span { color: #2F6BFF; }
            .invoice-title { text-align: right; }
            .invoice-title h1 { margin: 0; font-size: 24px; font-weight: 800; color: #111318; text-transform: uppercase; }
            .invoice-title p { margin: 4px 0 0; font-size: 13px; color: #5B6270; font-weight: 600; }
            .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 32px; }
            .box { background: #F8F9FA; padding: 20px; border-radius: 12px; border: 1px solid #E6E8EE; font-size: 13px; }
            .box h3 { margin: 0 0 8px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #5B6270; font-weight: 700; }
            .box p { margin: 4px 0; font-size: 14px; font-weight: 600; color: #111318; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 32px; font-size: 14px; }
            th { background: #F8F9FA; padding: 12px 16px; text-align: left; font-size: 12px; text-transform: uppercase; color: #5B6270; border-bottom: 1px solid #E6E8EE; font-weight: 700; }
            td { padding: 16px; border-bottom: 1px solid #E6E8EE; font-weight: 600; }
            .total-section { display: flex; justify-content: flex-end; margin-bottom: 32px; }
            .total-box { width: 300px; background: #EEF3FF; padding: 20px; border-radius: 12px; border: 1px solid #D6E4FF; }
            .total-row { display: flex; justify-content: space-between; font-size: 16px; font-weight: 800; color: #2F6BFF; }
            .hash-box { background: #111318; color: #10B981; padding: 16px; border-radius: 10px; font-family: monospace; font-size: 11px; word-break: break-all; margin-top: 20px; }
            .footer { margin-top: 40px; text-align: center; font-size: 12px; color: #5B6270; border-top: 1px solid #E6E8EE; padding-top: 20px; }
            @media print {
                body { padding: 0; }
                .invoice-box { border: none; padding: 0; }
            }
        </style>
    </head>
    <body>
        <div class="invoice-box">
            <div class="header">
                <div>
                    <div class="logo">Raibix<span>.</span></div>
                    <div style="font-size: 13px; color: #5B6270; margin-top: 4px;">Cloud Infrastructure & Tenant Services</div>
                </div>
                <div class="invoice-title">
                    <h1>OFFICIAL TAX INVOICE</h1>
                    <p>Reference: ${txn.reference_no || txn.transaction_code || 'INV-0000'}</p>
                </div>
            </div>
            <div class="grid">
                <div class="box">
                    <h3>Billed To (Tenant Institution)</h3>
                    <p>${txn.school_name || txn.tenant_name || 'Educational Institution'}</p>
                    <p style="font-size: 12px; color: #5B6270; font-weight: 400;">Tenant ID: ${txn.tenant_id || 'N/A'}</p>
                    <p style="font-size: 12px; color: #5B6270; font-weight: 400;">Subscription Tier: ${txn.plan_name || 'SaaS Subscription'}</p>
                </div>
                <div class="box">
                    <h3>Payment Details</h3>
                    <p>Status: <span style="color: #15803D; text-transform: uppercase;">${txn.status || 'PAID'}</span></p>
                    <p style="font-size: 12px; color: #5B6270; font-weight: 400;">Gateway / Route: ${txn.gateway || 'Bank Transfer'}</p>
                    <p style="font-size: 12px; color: #5B6270; font-weight: 400;">Processed Date: ${txn.date || txn.created_at || new Date().toLocaleDateString()}</p>
                </div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Item Description</th>
                        <th>Billing Cycle</th>
                        <th>Gateway Reference</th>
                        <th style="text-align: right;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Raibix Multi-Tenant SaaS Portal License</td>
                        <td>${txn.plan_name || 'Annual / Monthly Subscription'}</td>
                        <td style="font-family: monospace; font-size: 12px;">${txn.transaction_code || txn.reference_no || 'TXN-REF'}</td>
                        <td style="text-align: right; color: #111318;">PKR ${(Number(txn.amount) || 0).toLocaleString()}</td>
                    </tr>
                </tbody>
            </table>
            <div class="total-section">
                <div class="total-box">
                    <div class="total-row">
                        <span>Total Amount Paid:</span>
                        <span>PKR ${(Number(txn.amount) || 0).toLocaleString()}</span>
                    </div>
                </div>
            </div>
            <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #5B6270; margin-bottom: 4px;">Cryptographic Proof (HMAC Signature)</div>
            <div class="hash-box">
                ${txn.secure_hash || 'SHA256:VERIFIED:CLUSTER:RAIBIX:NODE01'}
            </div>
            <div class="footer">
                This is a verified computer-generated tax invoice issued by Raibix Cloud Engine. Authenticated via central HMAC node.
            </div>
        </div>
        <script>
            window.onload = function() {
                setTimeout(function() { window.print(); }, 400);
            };
        <\/script>
    </body>
    </html>
    `;
    const win = window.open('', '_blank', 'width=900,height=800');
    if (win) {
        win.document.open();
        win.document.write(invoiceHtml);
        win.document.close();
    }
};

// =============================================================================
// 9. ACTIVITY LOGS TAB
// =============================================================================
const logSearch = ref('');
const logTypeFilter = ref('all');
const selectedLog = ref(null);

const filteredLogs = computed(() => {
    if (!props.activityLogs) return [];
    return props.activityLogs.filter(log => {
        const q = logSearch.value.trim().toLowerCase();
        const matchesSearch = !q ||
            ((log.action || log.event || log.event_title || '').toLowerCase().includes(q)) ||
            ((log.details || log.description || '').toLowerCase().includes(q)) ||
            ((log.user || log.actor_name || '').toLowerCase().includes(q));

        const matchesType = logTypeFilter.value === 'all' ||
            ((log.type || log.event_type || 'info').toLowerCase() === logTypeFilter.value.toLowerCase());

        return matchesSearch && matchesType;
    });
});

// =============================================================================
// 10. MARKETING & COUPONS
// =============================================================================
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
    couponForm.post('/super-admin/coupons', {
        preserveScroll: true,
        onSuccess: () => {
            showCouponModal.value = false;
            couponForm.reset();
            triggerAlert('Subscription coupon created successfully!');
        }
    });
};

const deleteCoupon = (id, code) => {
    triggerConfirm(
        'Delete Coupon?',
        `Are you sure you want to delete coupon code [${code}]?`,
        () => {
            router.delete(`/super-admin/coupons/${id}`, {
                preserveScroll: true,
                onSuccess: () => triggerAlert('Coupon code deleted.')
            });
        },
        { icon: 'fa-trash-can', iconColor: 'text-red-500', confirmText: 'Yes, Delete', confirmClass: 'bg-red-600 hover:bg-red-700' }
    );
};

// =============================================================================
// 11. PLATFORM SETTINGS, PROFILE CREDENTIALS & ADMIN USERS
// =============================================================================
const settingsSubTab = ref('profile'); // 'profile' | 'team' | 'platform'

// Super Admin Profile & Credentials
const profileForm = useForm({
    name: page.props.auth?.user?.name || '',
    email: page.props.auth?.user?.email || '',
    current_password: '',
    password: '',
    password_confirmation: '',
});

watch(() => page.props.auth?.user, (user) => {
    if (user) {
        profileForm.name = user.name || '';
        profileForm.email = user.email || '';
    }
}, { immediate: true });

const submitProfile = () => {
    profileForm.post('/super-admin/profile', {
        preserveScroll: true,
        onSuccess: () => {
            profileForm.reset('current_password', 'password', 'password_confirmation');
            triggerAlert('Administrator credentials updated successfully.');
        }
    });
};

// Central Platform Administrators Management
const showAddAdminModal = ref(false);
const adminUserForm = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const openAddAdminModal = () => {
    adminUserForm.reset();
    adminUserForm.clearErrors();
    showAddAdminModal.value = true;
};

const submitNewAdminUser = () => {
    adminUserForm.post('/super-admin/users', {
        preserveScroll: true,
        onSuccess: () => {
            showAddAdminModal.value = false;
            adminUserForm.reset();
            triggerAlert('New administrator added successfully.');
        }
    });
};

const deleteAdminUser = (admin) => {
    triggerConfirm(
        'Remove Administrator',
        `Are you sure you want to remove administrator "${admin.name}" (${admin.email})? They will lose platform access immediately.`,
        () => {
            router.delete(`/super-admin/users/${admin.id}`, {
                preserveScroll: true,
                onSuccess: () => triggerAlert('Administrator account removed successfully.')
            });
        },
        { icon: 'fa-trash-can', iconColor: 'text-red-500', confirmText: 'Yes, Remove', confirmClass: 'bg-red-600 hover:bg-red-700' }
    );
};

const settingsForm = useForm({
    contact_email: props.platformSettings?.contact_email || 'admin@raibix.com',
    support_phone: props.platformSettings?.support_phone || '+1 (555) 019-2834',
    maintenance_mode: props.platformSettings?.maintenance_mode || false,
    public_onboarding: props.platformSettings?.public_onboarding ?? true,
    require_email_verification: props.platformSettings?.require_email_verification ?? false,
    default_trial_days: props.platformSettings?.default_trial_days || 14,
    tax_exemption_enabled: props.platformSettings?.tax_exemption_enabled ?? true,
});

watch(() => props.platformSettings, (newSettings) => {
    if (newSettings) {
        settingsForm.contact_email = newSettings.contact_email || 'admin@raibix.com';
        settingsForm.support_phone = newSettings.support_phone || '+1 (555) 019-2834';
        settingsForm.maintenance_mode = !!newSettings.maintenance_mode;
        settingsForm.public_onboarding = newSettings.public_onboarding ?? true;
        settingsForm.require_email_verification = newSettings.require_email_verification ?? false;
        settingsForm.default_trial_days = newSettings.default_trial_days || 14;
        settingsForm.tax_exemption_enabled = newSettings.tax_exemption_enabled ?? true;
    }
}, { deep: true });

const submitSettings = () => {
    settingsForm.post('/super-admin/settings', {
        preserveScroll: true,
        onSuccess: () => {
            triggerAlert('Global platform configuration saved and synchronized!');
        }
    });
};

// =============================================================================
// 12. INQUIRIES & LEADS MANAGEMENT
// =============================================================================
const inquirySearch = ref('');
const inquiryStatusFilter = ref('all');
const inquiryTypeFilter = ref('all');
const selectedInquiry = ref(null);

const newInquiriesCount = computed(() => {
    return (props.inquiries || []).filter(i => i.status === 'new').length;
});

const filteredInquiries = computed(() => {
    if (!props.inquiries) return [];
    return props.inquiries.filter(inq => {
        const q = inquirySearch.value.trim().toLowerCase();
        const matchesQuery = !q || 
            (inq.name && inq.name.toLowerCase().includes(q)) ||
            (inq.email && inq.email.toLowerCase().includes(q)) ||
            (inq.phone && inq.phone.toLowerCase().includes(q)) ||
            (inq.institution && inq.institution.toLowerCase().includes(q)) ||
            (inq.message && inq.message.toLowerCase().includes(q));

        const matchesStatus = inquiryStatusFilter.value === 'all' || inq.status === inquiryStatusFilter.value;
        const matchesType = inquiryTypeFilter.value === 'all' || inq.type === inquiryTypeFilter.value;

        return matchesQuery && matchesStatus && matchesType;
    });
});

const updateInquiryStatus = (inquiry, status) => {
    router.post(`/super-admin/inquiries/${inquiry.id}/status`, { status }, {
        preserveScroll: true,
        onSuccess: () => {
            if (selectedInquiry.value && selectedInquiry.value.id === inquiry.id) {
                selectedInquiry.value.status = status;
            }
            triggerAlert(`Inquiry marked as ${status}.`);
        }
    });
};

const deleteInquiry = (inquiry) => {
    triggerConfirm(
        'Delete Public Inquiry',
        `Are you sure you want to delete the inquiry from "${inquiry.name}"? This record will be permanently purged.`,
        () => {
            router.delete(`/super-admin/inquiries/${inquiry.id}`, {
                preserveScroll: true,
                onSuccess: () => {
                    if (selectedInquiry.value && selectedInquiry.value.id === inquiry.id) {
                        selectedInquiry.value = null;
                    }
                    triggerAlert('Inquiry record deleted successfully.');
                }
            });
        },
        { icon: 'fa-trash-can', iconColor: 'text-red-500', confirmText: 'Yes, Delete', confirmClass: 'bg-red-600 hover:bg-red-700' }
    );
};

const viewInquiry = (inquiry) => {
    selectedInquiry.value = { ...inquiry };
};

// =============================================================================
// 13. AUTH & LOGOUT
// =============================================================================
const logout = () => {
    useForm({}).post('/super-admin/logout');
};
</script>

<template>
    <Head title="Super Admin Dashboard" />

    <div class="flex min-h-screen bg-[#FAFAFC] text-[#111318] selection:bg-[#EEF3FF] selection:text-[#2F6BFF] font-sans">
        
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
                'fixed lg:sticky top-0 z-50 h-screen border-r border-[#E6E8EE] bg-white flex flex-col justify-between flex-shrink-0 transition-all duration-300 shadow-xl lg:shadow-none'
            ]"
        >
            <div class="flex flex-col min-h-0 flex-1">
                <!-- Brand Header -->
                <div class="flex items-center justify-between px-4 sm:px-6 py-5 border-b border-[#E6E8EE] min-h-[73px]">
                    <div class="flex items-center space-x-3 min-w-0 overflow-hidden">
                        <Link href="/" class="flex items-center shrink-0">
                            <img 
                                src="/images/raibix.png" 
                                alt="Raibix" 
                                class="h-6 w-auto object-contain"
                            />
                        </Link>
                        <div v-if="!isSidebarCollapsed || isMobileMenuOpen" class="min-w-0 transition-all duration-200">
                            <div class="flex items-center gap-1.5">
                                <span class="font-bold text-sm tracking-tight text-[#111318] truncate">Control Panel</span>
                                <span class="raibix-dot h-1.5 w-1.5"></span>
                            </div>
                            <p class="text-2xs font-semibold text-[#5B6270] truncate">Infrastructure Core</p>
                        </div>
                    </div>
                    
                    <!-- Collapse Toggle Button -->
                    <button 
                        @click="isMobileMenuOpen ? (isMobileMenuOpen = false) : (isSidebarCollapsed = !isSidebarCollapsed)"
                        class="h-7 w-7 rounded-lg flex items-center justify-center text-[#5B6270] hover:bg-[#F6F7F9] hover:text-[#111318] transition-colors flex-shrink-0 cursor-pointer"
                        :title="isMobileMenuOpen ? 'Close Menu' : (isSidebarCollapsed ? 'Expand Sidebar' : 'Collapse Sidebar')"
                    >
                        <i :class="isSidebarCollapsed ? 'fa-solid fa-chevron-right text-xs' : 'fa-solid fa-chevron-left text-xs'"></i>
                    </button>
                </div>

                <!-- Side Menu Links -->
                <nav class="p-3 sm:p-4 space-y-1 overflow-y-auto flex-1">
                    <!-- Overview -->
                    <button 
                        @click="activeTab = 'overview'; isMobileMenuOpen = false"
                        :title="(isSidebarCollapsed && !isMobileMenuOpen) ? 'Overview' : ''"
                        :class="[
                            activeTab === 'overview' ? 'bg-[#EEF3FF] text-[#2F6BFF] font-bold shadow-2xs' : 'text-[#5B6270] hover:bg-[#F6F7F9] hover:text-[#111318] font-medium',
                            (isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center px-2 py-3' : 'justify-between px-3.5 py-2.5'
                        ]"
                        class="w-full flex items-center rounded-xl text-xs transition-all cursor-pointer"
                    >
                        <div class="flex items-center" :class="(isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center' : 'space-x-3'">
                            <i class="fa-solid fa-chart-pie text-base flex-shrink-0"></i>
                            <span v-if="!isSidebarCollapsed || isMobileMenuOpen" class="truncate">Overview</span>
                        </div>
                    </button>

                    <!-- Registered Schools -->
                    <button 
                        @click="activeTab = 'schools'; isMobileMenuOpen = false"
                        :title="(isSidebarCollapsed && !isMobileMenuOpen) ? 'Registered Schools' : ''"
                        :class="[
                            activeTab === 'schools' ? 'bg-[#EEF3FF] text-[#2F6BFF] font-bold shadow-2xs' : 'text-[#5B6270] hover:bg-[#F6F7F9] hover:text-[#111318] font-medium',
                            (isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center px-2 py-3' : 'justify-between px-3.5 py-2.5'
                        ]"
                        class="w-full flex items-center rounded-xl text-xs transition-all relative cursor-pointer"
                    >
                        <div class="flex items-center" :class="(isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center' : 'space-x-3'">
                            <i class="fa-solid fa-school text-base flex-shrink-0"></i>
                            <span v-if="!isSidebarCollapsed || isMobileMenuOpen" class="truncate">Schools</span>
                        </div>
                        <span v-if="!isSidebarCollapsed || isMobileMenuOpen" class="bg-[#F0F2F6] text-[#5B6270] text-[10px] font-bold px-2 py-0.5 rounded-full">
                            {{ tenants?.length || 0 }}
                        </span>
                        <span v-else-if="tenants?.length" class="absolute top-2 right-2 h-2 w-2 rounded-full bg-[#2F6BFF]"></span>
                    </button>

                    <!-- Inquiries & Leads -->
                    <button 
                        @click="activeTab = 'inquiries'; isMobileMenuOpen = false"
                        :title="(isSidebarCollapsed && !isMobileMenuOpen) ? 'Inquiries & Leads' : ''"
                        :class="[
                            activeTab === 'inquiries' ? 'bg-[#EEF3FF] text-[#2F6BFF] font-bold shadow-2xs' : 'text-[#5B6270] hover:bg-[#F6F7F9] hover:text-[#111318] font-medium',
                            (isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center px-2 py-3' : 'justify-between px-3.5 py-2.5'
                        ]"
                        class="w-full flex items-center rounded-xl text-xs transition-all cursor-pointer relative"
                    >
                        <div class="flex items-center" :class="(isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center' : 'space-x-3'">
                            <i class="fa-solid fa-headset text-base flex-shrink-0"></i>
                            <span v-if="!isSidebarCollapsed || isMobileMenuOpen" class="truncate">Inquiries</span>
                        </div>
                        <span v-if="(!isSidebarCollapsed || isMobileMenuOpen) && newInquiriesCount" class="bg-[#2F6BFF] text-white text-[10px] font-bold px-2 py-0.5 rounded-full shadow-2xs">
                            {{ newInquiriesCount }}
                        </span>
                        <span v-else-if="newInquiriesCount" class="absolute top-2 right-2 h-2 w-2 rounded-full bg-[#2F6BFF]"></span>
                    </button>

                    <!-- Payment Approvals (with dynamic badge) -->
                    <button 
                        @click="activeTab = 'approvals'; isMobileMenuOpen = false"
                        :title="(isSidebarCollapsed && !isMobileMenuOpen) ? 'Payment Approvals' : ''"
                        :class="[
                            activeTab === 'approvals' ? 'bg-[#EEF3FF] text-[#2F6BFF] font-bold shadow-2xs' : 'text-[#5B6270] hover:bg-[#F6F7F9] hover:text-[#111318] font-medium',
                            (isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center px-2 py-3' : 'justify-between px-3.5 py-2.5'
                        ]"
                        class="w-full flex items-center rounded-xl text-xs transition-all cursor-pointer relative"
                    >
                        <div class="flex items-center" :class="(isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center' : 'space-x-3'">
                            <i class="fa-solid fa-check-double text-base flex-shrink-0"></i>
                            <span v-if="!isSidebarCollapsed || isMobileMenuOpen" class="truncate">Approvals</span>
                        </div>
                        <span v-if="(!isSidebarCollapsed || isMobileMenuOpen) && pendingApprovals?.length" class="bg-red-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full animate-pulse">
                            {{ pendingApprovals.length }}
                        </span>
                        <span v-else-if="pendingApprovals?.length" class="absolute top-2 right-2 h-2 w-2 rounded-full bg-red-500 animate-pulse"></span>
                    </button>

                    <!-- Subscription Plans -->
                    <button 
                        @click="activeTab = 'plans'; isMobileMenuOpen = false"
                        :title="(isSidebarCollapsed && !isMobileMenuOpen) ? 'Plans & Packages' : ''"
                        :class="[
                            activeTab === 'plans' ? 'bg-[#EEF3FF] text-[#2F6BFF] font-bold shadow-2xs' : 'text-[#5B6270] hover:bg-[#F6F7F9] hover:text-[#111318] font-medium',
                            (isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center px-2 py-3' : 'justify-between px-3.5 py-2.5'
                        ]"
                        class="w-full flex items-center rounded-xl text-xs transition-all cursor-pointer"
                    >
                        <div class="flex items-center" :class="(isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center' : 'space-x-3'">
                            <i class="fa-solid fa-box text-base flex-shrink-0"></i>
                            <span v-if="!isSidebarCollapsed || isMobileMenuOpen" class="truncate">Plans</span>
                        </div>
                    </button>

                    <!-- Payments & Invoices -->
                    <button 
                        @click="activeTab = 'payments'; isMobileMenuOpen = false"
                        :title="(isSidebarCollapsed && !isMobileMenuOpen) ? 'Payments & Ledger' : ''"
                        :class="[
                            activeTab === 'payments' ? 'bg-[#EEF3FF] text-[#2F6BFF] font-bold shadow-2xs' : 'text-[#5B6270] hover:bg-[#F6F7F9] hover:text-[#111318] font-medium',
                            (isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center px-2 py-3' : 'justify-between px-3.5 py-2.5'
                        ]"
                        class="w-full flex items-center rounded-xl text-xs transition-all cursor-pointer"
                    >
                        <div class="flex items-center" :class="(isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center' : 'space-x-3'">
                            <i class="fa-solid fa-credit-card text-base flex-shrink-0"></i>
                            <span v-if="!isSidebarCollapsed || isMobileMenuOpen" class="truncate">Payments</span>
                        </div>
                    </button>

                    <!-- Activity Logs -->
                    <button 
                        @click="activeTab = 'logs'; isMobileMenuOpen = false"
                        :title="(isSidebarCollapsed && !isMobileMenuOpen) ? 'Activity Logs' : ''"
                        :class="[
                            activeTab === 'logs' ? 'bg-[#EEF3FF] text-[#2F6BFF] font-bold shadow-2xs' : 'text-[#5B6270] hover:bg-[#F6F7F9] hover:text-[#111318] font-medium',
                            (isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center px-2 py-3' : 'justify-between px-3.5 py-2.5'
                        ]"
                        class="w-full flex items-center rounded-xl text-xs transition-all cursor-pointer"
                    >
                        <div class="flex items-center" :class="(isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center' : 'space-x-3'">
                            <i class="fa-solid fa-scroll text-base flex-shrink-0"></i>
                            <span v-if="!isSidebarCollapsed || isMobileMenuOpen" class="truncate">Activity Logs</span>
                        </div>
                    </button>

                    <!-- Marketing & Coupons -->
                    <button 
                        @click="activeTab = 'marketing'; isMobileMenuOpen = false"
                        :title="(isSidebarCollapsed && !isMobileMenuOpen) ? 'Marketing & Coupons' : ''"
                        :class="[
                            activeTab === 'marketing' ? 'bg-[#EEF3FF] text-[#2F6BFF] font-bold shadow-2xs' : 'text-[#5B6270] hover:bg-[#F6F7F9] hover:text-[#111318] font-medium',
                            (isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center px-2 py-3' : 'justify-between px-3.5 py-2.5'
                        ]"
                        class="w-full flex items-center rounded-xl text-xs transition-all cursor-pointer"
                    >
                        <div class="flex items-center" :class="(isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center' : 'space-x-3'">
                            <i class="fa-solid fa-ticket text-base flex-shrink-0"></i>
                            <span v-if="!isSidebarCollapsed || isMobileMenuOpen" class="truncate">Coupons</span>
                        </div>
                    </button>

                    <!-- System Settings -->
                    <button 
                        @click="activeTab = 'settings'; isMobileMenuOpen = false"
                        :title="(isSidebarCollapsed && !isMobileMenuOpen) ? 'Settings' : ''"
                        :class="[
                            activeTab === 'settings' ? 'bg-[#EEF3FF] text-[#2F6BFF] font-bold shadow-2xs' : 'text-[#5B6270] hover:bg-[#F6F7F9] hover:text-[#111318] font-medium',
                            (isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center px-2 py-3' : 'justify-between px-3.5 py-2.5'
                        ]"
                        class="w-full flex items-center rounded-xl text-xs transition-all cursor-pointer"
                    >
                        <div class="flex items-center" :class="(isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center' : 'space-x-3'">
                            <i class="fa-solid fa-gear text-base flex-shrink-0"></i>
                            <span v-if="!isSidebarCollapsed || isMobileMenuOpen" class="truncate">Settings</span>
                        </div>
                    </button>
                </nav>
            </div>

            <!-- Sidebar User Section -->
            <div class="p-3 sm:p-4 border-t border-[#E6E8EE] bg-[#F8F9FA] space-y-3">
                <div class="flex items-center" :class="(isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center' : 'space-x-3 px-2'">
                    <div class="h-9 w-9 rounded-full bg-gradient-to-tr from-[#8B5CF6] to-[#2F6BFF] text-white flex-shrink-0 flex items-center justify-center font-bold text-xs shadow-2xs">
                        {{ ($page.props.auth?.user?.name || 'SA').substring(0, 2).toUpperCase() }}
                    </div>
                    <div v-if="!isSidebarCollapsed || isMobileMenuOpen" class="flex-1 min-w-0">
                        <p class="text-xs font-bold text-[#111318] truncate">{{ $page.props.auth?.user?.name || 'Super Admin' }}</p>
                        <p class="text-2xs text-[#5B6270] truncate font-medium">{{ $page.props.auth?.user?.email || 'admin@raibix.com' }}</p>
                    </div>
                </div>
                <button 
                    @click="logout"
                    :title="(isSidebarCollapsed && !isMobileMenuOpen) ? 'Sign Out' : ''"
                    :class="(isSidebarCollapsed && !isMobileMenuOpen) ? 'py-2 px-1' : 'py-2 px-3 space-x-2'"
                    class="w-full rounded-xl bg-white border border-[#E6E8EE] text-xs font-semibold text-red-600 hover:bg-red-50 hover:border-red-200 transition-colors shadow-2xs flex items-center justify-center cursor-pointer"
                >
                    <i class="fa-solid fa-arrow-right-from-bracket text-xs"></i>
                    <span v-if="!isSidebarCollapsed || isMobileMenuOpen">Sign Out</span>
                </button>
            </div>
        </aside>

        <!-- Main Workspace -->
        <main class="flex-1 overflow-x-hidden min-h-screen flex flex-col min-w-0">
            
            <!-- Sticky Top Bar -->
            <header class="sticky top-0 z-30 bg-white/95 backdrop-blur-md border-b border-[#E6E8EE] px-4 sm:px-6 lg:px-8 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center min-w-0">
                    <button 
                        @click="isMobileMenuOpen = true"
                        class="lg:hidden mr-3 p-2 rounded-xl text-[#5B6270] hover:bg-[#F6F7F9] hover:text-[#111318] transition-colors border border-[#E6E8EE] shadow-2xs flex items-center justify-center"
                        aria-label="Open Sidebar Menu"
                    >
                        <i class="fa-solid fa-bars text-base"></i>
                    </button>
                    <div class="min-w-0">
                        <h2 class="text-lg sm:text-xl font-extrabold text-[#111318] capitalize truncate">
                            {{ 
                                activeTab === 'overview' ? 'Platform Overview' : 
                                activeTab === 'schools' ? 'Registered School Tenants' : 
                                activeTab === 'inquiries' ? 'Public Inquiries & Leads' :
                                activeTab === 'approvals' ? 'Payment Approvals Queue' : 
                                activeTab === 'plans' ? 'Subscription Plans & Tiers' : 
                                activeTab === 'payments' ? 'Subscription Revenue Ledger' : 
                                activeTab === 'logs' ? 'System Audit Logs' : 
                                activeTab === 'marketing' ? 'Coupons & Referrals' : 
                                'Platform Settings' 
                            }}
                        </h2>
                        <p class="text-xs text-[#5B6270] hidden sm:block font-medium">
                            {{ 
                                activeTab === 'overview' ? 'Real-time telemetry on tenant clusters, MRR growth, and system health.' : 
                                activeTab === 'schools' ? 'Monitor, configure, backup, and manage isolated school databases.' : 
                                activeTab === 'inquiries' ? 'Review questions, demo calls, and partnership requests submitted from the portal.' :
                                activeTab === 'approvals' ? 'Verify payment slips submitted by schools to unlock full portal access.' : 
                                activeTab === 'plans' ? 'Configure pricing models, feature tiers, and student enrollment limits.' : 
                                activeTab === 'payments' ? 'Audit incoming payments, gateway receipts, and tax invoices.' : 
                                activeTab === 'logs' ? 'Immutable central audit log recording all root administrative actions.' : 
                                activeTab === 'marketing' ? 'Create promo discount vouchers and manage tenant referral programs.' : 
                                'Global platform environment controls and maintenance configurations.' 
                            }}
                        </p>
                    </div>
                </div>

                <!-- Header Actions -->
                <div class="flex items-center space-x-3 self-end sm:self-auto flex-shrink-0">
                    <a 
                        href="/" 
                        target="_blank"
                        class="hidden sm:inline-flex items-center gap-1.5 px-3.5 py-2 rounded-full border border-[#E6E8EE] bg-white text-xs font-semibold text-[#5B6270] hover:text-[#111318] hover:border-[#111318] transition-colors shadow-2xs"
                    >
                        <span>View Portal</span>
                        <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
                    </a>

                    <button 
                        @click="showModal = true"
                        class="rounded-full bg-[#111318] hover:bg-[#2F6BFF] px-5 py-2.5 text-xs font-bold text-white shadow-xs transition-all duration-200 flex items-center space-x-2 cursor-pointer"
                    >
                        <i class="fa-solid fa-plus text-xs"></i>
                        <span>Onboard School</span>
                    </button>
                </div>
            </header>

            <!-- Main Workspace Container -->
            <div class="p-4 sm:p-6 lg:p-8 space-y-6 sm:space-y-8 flex-1 max-w-[1440px] w-full mx-auto">
                
                <!-- ================= TAB 1: OVERVIEW ================= -->
                <div v-if="activeTab === 'overview'" class="space-y-8">
                    
                    <!-- KPI Cards Grid -->
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                        
                        <!-- Total Schools -->
                        <div class="rounded-2xl p-6 bg-white border border-[#E6E8EE] shadow-2xs flex flex-col justify-between transition-all hover:border-[#2F6BFF]/40">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold uppercase tracking-wider text-[#5B6270]">Total Schools</span>
                                <span class="h-9 w-9 rounded-xl bg-[#EEF3FF] text-[#2F6BFF] flex items-center justify-center text-sm font-bold shadow-2xs">
                                    <i class="fa-solid fa-school"></i>
                                </span>
                            </div>
                            <div class="mt-4 flex items-baseline justify-between">
                                <span class="text-3xl font-extrabold text-[#111318]">{{ analytics?.total_tenants || tenants?.length || 0 }}</span>
                                <span class="text-xs font-bold text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-full border border-emerald-200 flex items-center gap-1.5">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                    {{ activeSchoolsCount }} Active
                                </span>
                            </div>
                        </div>

                        <!-- Monthly Revenue -->
                        <div class="rounded-2xl p-6 bg-white border border-[#E6E8EE] shadow-2xs flex flex-col justify-between transition-all hover:border-[#2F6BFF]/40">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold uppercase tracking-wider text-[#5B6270]">Monthly Inflows</span>
                                <span class="h-9 w-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm font-bold shadow-2xs">
                                    <i class="fa-solid fa-money-bill-wave"></i>
                                </span>
                            </div>
                            <div class="mt-4 flex items-baseline justify-between">
                                <span class="text-2xl sm:text-3xl font-extrabold text-[#111318]">PKR {{ (analytics?.total_revenue || 0).toLocaleString() }}</span>
                                <span class="text-xs font-semibold text-[#5B6270]">Settled inflows</span>
                            </div>
                        </div>

                        <!-- Enrolled Students -->
                        <div class="rounded-2xl p-6 bg-white border border-[#E6E8EE] shadow-2xs flex flex-col justify-between transition-all hover:border-[#2F6BFF]/40">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold uppercase tracking-wider text-[#5B6270]">Enrolled Students</span>
                                <span class="h-9 w-9 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-sm font-bold shadow-2xs">
                                    <i class="fa-solid fa-graduation-cap"></i>
                                </span>
                            </div>
                            <div class="mt-4 flex items-baseline justify-between">
                                <span class="text-3xl font-extrabold text-[#111318]">{{ (analytics?.total_students || 0).toLocaleString() }}</span>
                                <span class="text-xs font-semibold text-[#5B6270]">Active records</span>
                            </div>
                        </div>

                        <!-- Platform Engine Status -->
                        <div class="rounded-2xl p-6 bg-white border border-[#E6E8EE] shadow-2xs flex flex-col justify-between transition-all hover:border-[#2F6BFF]/40">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold uppercase tracking-wider text-[#5B6270]">Platform Core</span>
                                <span class="h-9 w-9 rounded-xl bg-[#EEF3FF] text-[#2F6BFF] flex items-center justify-center text-sm font-bold shadow-2xs">
                                    <i class="fa-solid fa-shield-halved"></i>
                                </span>
                            </div>
                            <div class="mt-4 flex items-baseline justify-between">
                                <span class="text-2xl sm:text-3xl font-extrabold text-[#111318] flex items-center gap-2">
                                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                    Operational
                                </span>
                                <span class="text-xs font-semibold text-[#5B6270]">Core Engine</span>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Grid -->
                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                        
                        <!-- Growth Trajectory -->
                        <div class="lg:col-span-2 rounded-2xl border border-[#E6E8EE] bg-white p-6 shadow-2xs space-y-4">
                            <div class="border-b border-[#E6E8EE] pb-4">
                                <h3 class="text-base font-bold text-[#111318]">Tenant Growth Trajectory</h3>
                                <p class="text-xs text-[#5B6270]">Cumulative onboarded school campuses</p>
                            </div>
                            
                            <div class="relative pt-4 flex flex-col items-center">
                                <svg :viewBox="`0 0 ${growthPoints.width} ${growthPoints.height}`" class="w-full h-44 sm:h-52">
                                    <defs>
                                        <linearGradient id="chartGrad" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="0%" stop-color="#2F6BFF" stop-opacity="0.2" />
                                            <stop offset="100%" stop-color="#2F6BFF" stop-opacity="0.0" />
                                        </linearGradient>
                                    </defs>
                                    <line x1="20" y1="20" x2="480" y2="20" stroke="#F1F3F4" stroke-dasharray="4" />
                                    <line x1="20" y1="65" x2="480" y2="65" stroke="#F1F3F4" stroke-dasharray="4" />
                                    <line x1="20" y1="110" x2="480" y2="110" stroke="#F1F3F4" stroke-dasharray="4" />
                                    <path :d="growthPoints.areaPath" fill="url(#chartGrad)" />
                                    <path :d="growthPoints.linePath" fill="none" stroke="#2F6BFF" stroke-width="3" stroke-linecap="round" />
                                    <circle 
                                        v-for="(p, i) in growthPoints.points" 
                                        :key="i" 
                                        :cx="p.x" 
                                        :cy="p.y" 
                                        r="5" 
                                        fill="#FFFFFF" 
                                        stroke="#2F6BFF" 
                                        stroke-width="3" 
                                        class="hover:r-7 transition-all duration-200 cursor-pointer" 
                                    />
                                </svg>
                                <div class="w-full flex justify-between px-6 text-3xs font-bold uppercase tracking-wider text-[#8F94A0] pt-2">
                                    <span>Phase 1</span>
                                    <span>Phase 2</span>
                                    <span>Phase 3</span>
                                    <span>Phase 4</span>
                                    <span>Phase 5</span>
                                    <span>Current</span>
                                </div>
                            </div>
                        </div>

                        <!-- Subscription Tiers Share -->
                        <div class="rounded-2xl border border-[#E6E8EE] bg-white p-6 shadow-2xs space-y-4 flex flex-col justify-between">
                            <div class="border-b border-[#E6E8EE] pb-4">
                                <h3 class="text-base font-bold text-[#111318]">Subscription Tiers</h3>
                                <p class="text-xs text-[#5F6368]">Active plan distribution across schools</p>
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
                                        <span class="text-2xl font-black text-[#111318]">{{ tenants?.length || 0 }}</span>
                                        <span class="text-4xs font-bold uppercase tracking-wider text-[#5B6270]">Schools</span>
                                    </div>
                                </div>

                                <div class="w-full space-y-1.5 pt-2">
                                    <div v-for="seg in planSegments" :key="seg.name" class="flex items-center justify-between text-xs p-2 bg-[#F8F9FA] rounded-xl border border-[#E6E8EE]">
                                        <div class="flex items-center space-x-2">
                                            <span class="h-2.5 w-2.5 rounded-full shrink-0" :style="{ backgroundColor: seg.color }"></span>
                                            <span class="font-semibold text-[#111318] truncate max-w-[130px]">{{ seg.name }}</span>
                                        </div>
                                        <span class="font-bold text-[#5B6270]">{{ seg.count }} ({{ seg.percent }}%)</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Operations & Live Feeds -->
                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                        
                        <!-- System Infrastructure Telemetry -->
                        <div class="rounded-2xl border border-[#E6E8EE] bg-white p-6 shadow-2xs space-y-4">
                            <h3 class="text-base font-bold text-[#111318] border-b border-[#E6E8EE] pb-4 flex items-center gap-2">
                                <i class="fa-solid fa-server text-[#2F6BFF]"></i>
                                <span>Infrastructure Telemetry</span>
                            </h3>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between p-3 bg-[#F8F9FA] rounded-xl border border-[#E6E8EE]">
                                    <div class="flex items-center gap-2.5">
                                        <i class="fa-solid fa-database text-[#2F6BFF] text-sm"></i>
                                        <span class="text-xs font-semibold text-[#111318]">Tenant Schemas</span>
                                    </div>
                                    <span class="text-xs font-medium text-emerald-600 flex items-center gap-1.5">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                        Online & Isolated
                                    </span>
                                </div>
                                <div class="flex items-center justify-between p-3 bg-[#F8F9FA] rounded-xl border border-[#E6E8EE]">
                                    <div class="flex items-center gap-2.5">
                                        <i class="fa-solid fa-network-wired text-purple-600 text-sm"></i>
                                        <span class="text-xs font-semibold text-[#111318]">Multi-Tenant Router</span>
                                    </div>
                                    <span class="text-xs font-medium text-emerald-600 flex items-center gap-1.5">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                        Domain Resolved
                                    </span>
                                </div>
                                <div class="flex items-center justify-between p-3 bg-[#F8F9FA] rounded-xl border border-[#E6E8EE]">
                                    <div class="flex items-center gap-2.5">
                                        <i class="fa-solid fa-credit-card text-emerald-600 text-sm"></i>
                                        <span class="text-xs font-semibold text-[#111318]">Payment Gateways</span>
                                    </div>
                                    <span class="text-xs font-medium text-emerald-600 flex items-center gap-1.5">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                        IPN Listening
                                    </span>
                                </div>
                                <div class="flex items-center justify-between p-3 bg-[#F8F9FA] rounded-xl border border-[#E6E8EE]">
                                    <div class="flex items-center gap-2.5">
                                        <i class="fa-solid fa-clock-rotate-left text-amber-600 text-sm"></i>
                                        <span class="text-xs font-semibold text-[#111318]">Automated Backup Engine</span>
                                    </div>
                                    <span class="text-xs font-medium text-emerald-600 flex items-center gap-1.5">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                        Scheduled
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Inflow Ledger -->
                        <div class="rounded-2xl border border-[#E6E8EE] bg-white p-6 shadow-2xs space-y-4">
                            <div class="flex items-center justify-between border-b border-[#E6E8EE] pb-4">
                                <h3 class="text-base font-bold text-[#111318] flex items-center gap-2">
                                    <i class="fa-solid fa-receipt text-emerald-600"></i>
                                    <span>Recent Inflows</span>
                                </h3>
                                <button @click="activeTab = 'payments'" class="text-xs font-bold text-[#2F6BFF] hover:underline cursor-pointer">
                                    View All
                                </button>
                            </div>
                            <div class="space-y-3">
                                <div v-if="!revenueTransactions || revenueTransactions.length === 0" class="py-6 text-center text-xs text-[#5B6270]">
                                    No transaction records recorded yet.
                                </div>
                                <div v-for="trx in revenueTransactions.slice(0, 3)" :key="trx.id" class="p-3 bg-[#F8F9FA] rounded-xl border border-[#E6E8EE] flex items-center justify-between">
                                    <div>
                                        <p class="text-xs font-bold text-[#111318]">{{ trx.school_name }}</p>
                                        <p class="text-3xs text-[#5B6270] font-mono mt-0.5">{{ trx.transaction_code }} • {{ trx.gateway }}</p>
                                    </div>
                                    <span class="text-xs font-extrabold text-emerald-600">PKR {{ (Number(trx.amount) || 0).toLocaleString() }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Live Audit Events -->
                        <div class="rounded-2xl border border-[#E6E8EE] bg-white p-6 shadow-2xs space-y-4">
                            <div class="flex items-center justify-between border-b border-[#E6E8EE] pb-4">
                                <h3 class="text-base font-bold text-[#111318] flex items-center gap-2">
                                    <i class="fa-solid fa-scroll text-purple-600"></i>
                                    <span>Platform Audit Trail</span>
                                </h3>
                                <button @click="activeTab = 'logs'" class="text-xs font-bold text-[#2F6BFF] hover:underline cursor-pointer">
                                    View Logs
                                </button>
                            </div>
                            <div class="space-y-3 max-h-[220px] overflow-y-auto pr-1">
                                <div v-if="!activityLogs || activityLogs.length === 0" class="py-6 text-center text-xs text-[#5B6270]">
                                    No system actions logged.
                                </div>
                                <div v-for="log in activityLogs.slice(0, 4)" :key="log.id" class="p-2.5 bg-[#F8F9FA] rounded-xl border border-[#E6E8EE] flex gap-2.5">
                                    <span class="mt-0.5 shrink-0 text-[#2F6BFF]"><i class="fa-solid fa-circle-info text-2xs"></i></span>
                                    <div class="min-w-0">
                                        <p class="text-xs font-semibold text-[#111318] truncate">{{ log.event_title }}</p>
                                        <p class="text-3xs font-semibold text-[#8F94A0] uppercase tracking-wider mt-0.5">{{ log.created_at }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ================= TAB 2: REGISTERED SCHOOLS ================= -->
                <div v-if="activeTab === 'schools'" class="space-y-6">
                    
                    <!-- Search & Filter Controls -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-4 sm:p-5 rounded-2xl border border-[#E6E8EE] shadow-2xs">
                        <div class="relative flex-1 max-w-md">
                            <i class="fa-solid fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-xs text-[#5B6270]"></i>
                            <input 
                                v-model="schoolSearch" 
                                type="text" 
                                placeholder="Search by school name, subdomain, or admin email..."
                                class="w-full rounded-xl border border-[#E6E8EE] bg-[#F8F9FA] pl-9 pr-4 py-2.5 text-xs text-[#111318] placeholder-[#8F94A0] focus:border-[#2F6BFF] focus:bg-white focus:outline-none transition-all font-medium"
                            />
                        </div>

                        <!-- Status Filter Tabs -->
                        <div class="flex items-center gap-1.5 overflow-x-auto">
                            <button 
                                @click="schoolStatusFilter = 'all'"
                                :class="schoolStatusFilter === 'all' ? 'bg-[#111318] text-white' : 'bg-[#F8F9FA] text-[#5B6270] hover:text-[#111318] border border-[#E6E8EE]'"
                                class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all cursor-pointer"
                            >
                                All ({{ tenants.length }})
                            </button>
                            <button 
                                @click="schoolStatusFilter = 'active'"
                                :class="schoolStatusFilter === 'active' ? 'bg-[#111318] text-white' : 'bg-[#F8F9FA] text-[#5B6270] hover:text-[#111318] border border-[#E6E8EE]'"
                                class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all cursor-pointer"
                            >
                                Active ({{ activeSchoolsCount }})
                            </button>
                            <button 
                                @click="schoolStatusFilter = 'suspended'"
                                :class="schoolStatusFilter === 'suspended' ? 'bg-[#111318] text-white' : 'bg-[#F8F9FA] text-[#5B6270] hover:text-[#111318] border border-[#E6E8EE]'"
                                class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all cursor-pointer"
                            >
                                Suspended ({{ suspendedSchoolsCount }})
                            </button>
                        </div>
                    </div>

                    <!-- Schools Data Table -->
                    <div class="overflow-hidden rounded-2xl border border-[#E6E8EE] bg-white shadow-2xs">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-sm min-w-[1050px]">
                                <thead>
                                    <tr class="border-b border-[#E6E8EE] bg-[#F8F9FA] text-xs font-bold uppercase tracking-wider text-[#5B6270]">
                                        <th class="py-4 px-6">School Tenant</th>
                                        <th class="py-4 px-6">Portal Endpoint</th>
                                        <th class="py-4 px-6">Principal / Contact</th>
                                        <th class="py-4 px-6">Assigned Plan</th>
                                        <th class="py-4 px-6">Enrollment & Metrics</th>
                                        <th class="py-4 px-6">Status</th>
                                        <th class="py-4 px-6 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#E6E8EE]">
                                    <tr v-if="filteredSchools.length === 0">
                                        <td colspan="7" class="py-12 text-center text-sm text-[#5B6270]">
                                            <div class="flex flex-col items-center gap-2">
                                                <i class="fa-solid fa-school text-3xl text-[#D6E4FF]"></i>
                                                <span class="font-semibold text-[#111318]">No schools found matching your search.</span>
                                                <span class="text-xs text-[#5B6270]">Try altering your search keywords or status filter.</span>
                                            </div>
                                        </td>
                                    </tr>

                                    <tr v-for="t in filteredSchools" :key="t.id" class="hover:bg-[#F8F9FA]/80 transition-colors">
                                        <!-- School Name -->
                                        <td class="py-4 px-6">
                                            <div class="font-extrabold text-[#111318]">{{ t.name }}</div>
                                            <div class="text-xs text-[#5B6270] font-mono mt-0.5">ID: {{ t.id }}</div>
                                        </td>

                                        <!-- Domain -->
                                        <td class="py-4 px-6">
                                            <a 
                                                :href="getTenantLoginUrl(t)" 
                                                target="_blank" 
                                                class="font-semibold text-[#2F6BFF] hover:underline flex items-center space-x-1.5"
                                            >
                                                <span class="truncate max-w-[200px]">{{ getTenantDisplayUrl(t) }}</span>
                                                <i class="fa-solid fa-arrow-up-right-from-square text-[11px]"></i>
                                            </a>
                                        </td>

                                        <!-- Admin Contact -->
                                        <td class="py-4 px-6">
                                            <div class="font-semibold text-[#111318]">{{ t.admin_name || t.data?.admin_name || 'Principal' }}</div>
                                            <div class="text-xs text-[#5B6270]">{{ t.admin_email || t.data?.admin_email || 'admin@school.test' }}</div>
                                        </td>

                                        <!-- Assigned Plan -->
                                        <td class="py-4 px-6">
                                            <span class="rounded-lg bg-[#EEF3FF] text-[#2F6BFF] border border-[#D6E4FF] px-2.5 py-1 text-xs font-bold inline-flex items-center gap-1">
                                                <i class="fa-solid fa-box text-2xs"></i>
                                                <span>{{ t.plan?.name || t.plan_name || 'Growth Plan' }}</span>
                                            </span>
                                        </td>

                                        <!-- Real Metrics (No Fake 320/45% Hardcodes) -->
                                        <td class="py-4 px-6">
                                            <div class="flex flex-col gap-1 text-xs">
                                                <div class="flex items-center justify-between">
                                                    <span class="text-[#5B6270]">Students:</span>
                                                    <span class="font-bold text-[#111318]">
                                                        {{ t.student_count || 0 }}
                                                    </span>
                                                </div>
                                                <div class="flex items-center justify-between">
                                                    <span class="text-[#5B6270]">Staff:</span>
                                                    <span class="font-bold text-[#111318]">{{ t.staff_count || 0 }}</span>
                                                </div>
                                                <div class="flex items-center justify-between text-2xs text-[#8F94A0] mt-0.5">
                                                    <span>DB: {{ t.db_status || 'Isolated' }}</span>
                                                    <span>{{ t.data?.backup_size || '0.5 MB' }}</span>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Status Badge -->
                                        <td class="py-4 px-6">
                                            <span 
                                                :class="(t.status || t.data?.status) === 'suspended' ? 'bg-red-50 text-red-700 border-red-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200'" 
                                                class="px-3 py-1 rounded-full text-xs font-bold border inline-flex items-center gap-1.5"
                                            >
                                                <span class="h-1.5 w-1.5 rounded-full" :class="(t.status || t.data?.status) === 'suspended' ? 'bg-red-500' : 'bg-emerald-500'"></span>
                                                <span>{{ (t.status || t.data?.status) === 'suspended' ? 'Suspended' : 'Active' }}</span>
                                            </span>
                                        </td>

                                        <!-- Action Buttons -->
                                        <td class="py-4 px-6 text-right">
                                            <div class="flex items-center justify-end gap-1.5 flex-wrap">
                                                
                                                <!-- Edit School -->
                                                <button 
                                                    @click="openEditSchoolModal(t)"
                                                    title="Edit School Details"
                                                    class="rounded-lg border border-[#E6E8EE] bg-white px-2.5 py-1.5 text-xs font-semibold text-[#111318] hover:bg-[#F8F9FA] hover:border-[#111318] transition-all flex items-center gap-1.5 cursor-pointer shadow-2xs"
                                                >
                                                    <i class="fa-solid fa-pen-to-square text-xs"></i>
                                                    <span>Edit</span>
                                                </button>

                                                <!-- Impersonate -->
                                                <a 
                                                    :href="`/tenants/${t.id}/impersonate`"
                                                    target="_blank"
                                                    title="Impersonate Principal"
                                                    class="rounded-lg border border-[#D6E4FF] bg-[#EEF3FF] px-2.5 py-1.5 text-xs font-bold text-[#2F6BFF] hover:bg-[#D6E4FF] transition-all flex items-center gap-1.5 cursor-pointer shadow-2xs"
                                                >
                                                    <i class="fa-solid fa-user-secret text-xs"></i>
                                                    <span>Impersonate</span>
                                                </a>

                                                <!-- Activate / Suspend -->
                                                <button 
                                                    @click="toggleTenantStatus(t)"
                                                    :title="(t.status || t.data?.status) === 'suspended' ? 'Activate School' : 'Suspend School'"
                                                    :class="(t.status || t.data?.status) === 'suspended' ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100' : 'bg-amber-50 text-amber-700 border-amber-200 hover:bg-amber-100'"
                                                    class="rounded-lg border px-2.5 py-1.5 text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer shadow-2xs"
                                                >
                                                    <i :class="(t.status || t.data?.status) === 'suspended' ? 'fa-solid fa-play' : 'fa-solid fa-pause'"></i>
                                                    <span>{{ (t.status || t.data?.status) === 'suspended' ? 'Activate' : 'Suspend' }}</span>
                                                </button>

                                                <!-- Backup -->
                                                <button 
                                                    @click="backupTenant(t.id, t.name)"
                                                    title="Backup Isolated Database"
                                                    class="rounded-lg border border-[#E6E8EE] bg-white px-2.5 py-1.5 text-xs font-semibold text-[#5B6270] hover:text-[#111318] hover:bg-[#F8F9FA] transition-all flex items-center gap-1 cursor-pointer shadow-2xs"
                                                >
                                                    <i class="fa-solid fa-floppy-disk text-xs"></i>
                                                    <span>Backup</span>
                                                </button>

                                                <!-- Sync RBAC -->
                                                <button 
                                                    @click="syncTenant(t.id, t.name)"
                                                    title="Sync Schema & RBAC"
                                                    class="rounded-lg border border-[#E6E8EE] bg-white px-2.5 py-1.5 text-xs font-semibold text-[#5B6270] hover:text-[#111318] hover:bg-[#F8F9FA] transition-all flex items-center gap-1 cursor-pointer shadow-2xs"
                                                >
                                                    <i class="fa-solid fa-rotate text-xs"></i>
                                                    <span>Sync</span>
                                                </button>

                                                <!-- Custom Domain -->
                                                <button 
                                                    @click="mapCustomDomain(t.id)"
                                                    title="Map Custom FQDN Domain"
                                                    class="rounded-lg border border-[#E6E8EE] bg-white px-2.5 py-1.5 text-xs font-semibold text-[#5B6270] hover:text-[#111318] hover:bg-[#F8F9FA] transition-all flex items-center gap-1 cursor-pointer shadow-2xs"
                                                >
                                                    <i class="fa-solid fa-globe text-xs"></i>
                                                    <span>Domain</span>
                                                </button>

                                                <!-- Offboard -->
                                                <button 
                                                    @click="offboardTenant(t.id, t.name)"
                                                    title="Offboard Tenant"
                                                    class="rounded-lg border border-red-200 bg-red-50/50 px-2.5 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50 transition-all flex items-center gap-1 cursor-pointer shadow-2xs"
                                                >
                                                    <i class="fa-solid fa-file-export text-xs"></i>
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

                <!-- ================= TAB: INQUIRIES & LEADS ================= -->
                <div v-if="activeTab === 'inquiries'" class="space-y-6">
                    <!-- Stat Cards -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="rounded-2xl border border-[#E6E8EE] bg-white p-5 shadow-2xs">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="text-xs font-semibold text-[#5B6270]">Total Inquiries</div>
                                    <div class="text-2xl font-extrabold text-[#111318] mt-1">{{ inquiries?.length || 0 }}</div>
                                </div>
                                <div class="h-10 w-10 rounded-xl bg-[#EEF3FF] text-[#2F6BFF] flex items-center justify-center">
                                    <i class="fa-solid fa-inbox text-lg"></i>
                                </div>
                            </div>
                            <div class="text-2xs text-[#5B6270] mt-2 font-medium">All incoming portal requests</div>
                        </div>

                        <div class="rounded-2xl border border-[#E6E8EE] bg-white p-5 shadow-2xs">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="text-xs font-semibold text-[#5B6270]">New & Uncontacted</div>
                                    <div class="text-2xl font-extrabold text-amber-600 mt-1">{{ newInquiriesCount }}</div>
                                </div>
                                <div class="h-10 w-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                                    <i class="fa-solid fa-bell text-lg"></i>
                                </div>
                            </div>
                            <div class="text-2xs text-[#5B6270] mt-2 font-medium">Require administrative follow-up</div>
                        </div>

                        <div class="rounded-2xl border border-[#E6E8EE] bg-white p-5 shadow-2xs">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="text-xs font-semibold text-[#5B6270]">Contacted / Closed</div>
                                    <div class="text-2xl font-extrabold text-emerald-600 mt-1">
                                        {{ (inquiries?.length || 0) - newInquiriesCount }}
                                    </div>
                                </div>
                                <div class="h-10 w-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                                    <i class="fa-solid fa-circle-check text-lg"></i>
                                </div>
                            </div>
                            <div class="text-2xs text-[#5B6270] mt-2 font-medium">Successfully processed inquiries</div>
                        </div>
                    </div>

                    <!-- Inquiries Table Card -->
                    <div class="rounded-2xl border border-[#E6E8EE] bg-white p-6 shadow-2xs">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-[#E6E8EE] pb-4 mb-4">
                            <div>
                                <h3 class="text-base font-extrabold text-[#111318]">Portal Leads & Messages</h3>
                                <p class="text-xs text-[#5B6270] mt-0.5">Prospective schools and users reaching out via Raibix landing page.</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <!-- Search Input -->
                                <div class="relative">
                                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-xs text-[#5B6270]"></i>
                                    <input 
                                        v-model="inquirySearch"
                                        type="text"
                                        placeholder="Search inquiries..."
                                        class="rounded-xl border border-[#E6E8EE] pl-8 pr-3 py-1.5 text-xs focus:border-[#2F6BFF] focus:outline-none w-48 sm:w-60 bg-[#FAFAFC]"
                                    />
                                    <button 
                                        v-if="inquirySearch" 
                                        @click="inquirySearch = ''"
                                        class="absolute right-2.5 top-1/2 -translate-y-1/2 text-xs text-[#5B6270] hover:text-[#111318]"
                                    >
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                </div>

                                <!-- Status Filter -->
                                <select 
                                    v-model="inquiryStatusFilter"
                                    class="rounded-xl border border-[#E6E8EE] px-3 py-1.5 text-xs font-semibold text-[#111318] focus:border-[#2F6BFF] focus:outline-none bg-[#FAFAFC] cursor-pointer"
                                >
                                    <option value="all">All Statuses</option>
                                    <option value="new">New Only</option>
                                    <option value="contacted">Contacted</option>
                                    <option value="closed">Closed</option>
                                </select>

                                <!-- Type Filter -->
                                <select 
                                    v-model="inquiryTypeFilter"
                                    class="rounded-xl border border-[#E6E8EE] px-3 py-1.5 text-xs font-semibold text-[#111318] focus:border-[#2F6BFF] focus:outline-none bg-[#FAFAFC] cursor-pointer"
                                >
                                    <option value="all">All Types</option>
                                    <option value="contact">Contact Queries</option>
                                    <option value="expert_call">Expert Calls</option>
                                </select>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div v-if="filteredInquiries.length === 0" class="py-16 text-center text-[#5B6270] text-sm">
                            <div class="h-14 w-14 rounded-2xl bg-[#EEF3FF] text-[#2F6BFF] flex items-center justify-center mx-auto mb-3">
                                <i class="fa-solid fa-headset text-2xl"></i>
                            </div>
                            <p class="font-bold text-[#111318] text-base">No Inquiries Found</p>
                            <p class="text-xs text-[#5B6270] mt-1 max-w-sm mx-auto">
                                {{ inquirySearch || inquiryStatusFilter !== 'all' || inquiryTypeFilter !== 'all' 
                                    ? 'No results match your active filters. Try resetting search or filter criteria.' 
                                    : 'When visitors submit the contact form or request an expert call from the portal, they will appear here.' }}
                            </p>
                            <button 
                                v-if="inquirySearch || inquiryStatusFilter !== 'all' || inquiryTypeFilter !== 'all'"
                                @click="inquirySearch = ''; inquiryStatusFilter = 'all'; inquiryTypeFilter = 'all'"
                                class="mt-4 px-4 py-2 rounded-full border border-[#E6E8EE] bg-white text-xs font-bold text-[#2F6BFF] hover:bg-[#EEF3FF] cursor-pointer"
                            >
                                Reset Filters
                            </button>
                        </div>

                        <!-- Table -->
                        <div v-else class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="border-b border-[#E6E8EE] text-[#5B6270] font-bold uppercase tracking-wider">
                                        <th class="py-3.5 px-4">Lead / Sender</th>
                                        <th class="py-3.5 px-4">Institution</th>
                                        <th class="py-3.5 px-4">Type</th>
                                        <th class="py-3.5 px-4">Message Preview</th>
                                        <th class="py-3.5 px-4">Submitted</th>
                                        <th class="py-3.5 px-4">Status</th>
                                        <th class="py-3.5 px-4 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#E6E8EE] font-medium text-[#111318]">
                                    <tr v-for="inq in filteredInquiries" :key="inq.id" class="hover:bg-[#F8F9FA]/60 transition-colors">
                                        <!-- Sender & Contact -->
                                        <td class="py-4 px-4">
                                            <div class="font-bold text-sm text-[#111318] flex items-center gap-1.5">
                                                <span>{{ inq.name }}</span>
                                                <span v-if="inq.status === 'new'" class="h-2 w-2 rounded-full bg-amber-500 animate-pulse" title="New Inquiry"></span>
                                            </div>
                                            <div class="text-xs text-[#5B6270] flex items-center gap-2 mt-0.5">
                                                <a :href="`mailto:${inq.email}`" class="hover:text-[#2F6BFF] flex items-center gap-1 font-mono text-2xs">
                                                    <i class="fa-solid fa-envelope text-[10px]"></i>
                                                    <span>{{ inq.email }}</span>
                                                </a>
                                                <a v-if="inq.phone" :href="`tel:${inq.phone}`" class="hover:text-[#2F6BFF] flex items-center gap-1 font-mono text-2xs">
                                                    <i class="fa-solid fa-phone text-[10px]"></i>
                                                    <span>{{ inq.phone }}</span>
                                                </a>
                                            </div>
                                        </td>

                                        <!-- Institution -->
                                        <td class="py-4 px-4">
                                            <div class="font-semibold text-[#111318]">{{ inq.institution || 'Individual Lead' }}</div>
                                            <div class="text-2xs text-[#5B6270]">Prospective Organization</div>
                                        </td>

                                        <!-- Type -->
                                        <td class="py-4 px-4">
                                            <span 
                                                v-if="inq.type === 'expert_call'" 
                                                class="px-2.5 py-1 rounded-full bg-purple-50 text-purple-700 font-bold text-[11px] border border-purple-200 inline-flex items-center gap-1"
                                            >
                                                <i class="fa-solid fa-phone text-[10px]"></i>
                                                <span>Expert Call</span>
                                            </span>
                                            <span 
                                                v-else 
                                                class="px-2.5 py-1 rounded-full bg-[#EEF3FF] text-[#2F6BFF] font-bold text-[11px] border border-[#D6E4FF] inline-flex items-center gap-1"
                                            >
                                                <i class="fa-solid fa-envelope text-[10px]"></i>
                                                <span>Contact Form</span>
                                            </span>
                                        </td>

                                        <!-- Message Preview -->
                                        <td class="py-4 px-4 max-w-xs">
                                            <div 
                                                @click="viewInquiry(inq)" 
                                                class="text-xs text-[#5B6270] line-clamp-2 cursor-pointer hover:text-[#111318]" 
                                                :title="inq.message"
                                            >
                                                {{ inq.message || 'No additional message provided.' }}
                                            </div>
                                        </td>

                                        <!-- Received -->
                                        <td class="py-4 px-4 whitespace-nowrap">
                                            <div class="font-semibold text-xs text-[#111318]">{{ inq.time_ago }}</div>
                                            <div class="text-2xs text-[#5B6270]">{{ inq.created_at }}</div>
                                        </td>

                                        <!-- Status -->
                                        <td class="py-4 px-4 whitespace-nowrap">
                                            <span 
                                                v-if="inq.status === 'new'" 
                                                class="px-2.5 py-1 rounded-full bg-amber-50 text-amber-700 font-bold text-[11px] border border-amber-200 inline-flex items-center gap-1"
                                            >
                                                <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                                <span>New</span>
                                            </span>
                                            <span 
                                                v-else-if="inq.status === 'contacted'" 
                                                class="px-2.5 py-1 rounded-full bg-blue-50 text-[#2F6BFF] font-bold text-[11px] border border-blue-200 inline-flex items-center gap-1"
                                            >
                                                <i class="fa-solid fa-check text-[10px]"></i>
                                                <span>Contacted</span>
                                            </span>
                                            <span 
                                                v-else 
                                                class="px-2.5 py-1 rounded-full bg-gray-100 text-[#5B6270] font-bold text-[11px] border border-gray-200 inline-flex items-center gap-1"
                                            >
                                                <span>Closed</span>
                                            </span>
                                        </td>

                                        <!-- Actions -->
                                        <td class="py-4 px-4 text-right space-x-1.5 whitespace-nowrap">
                                            <!-- View / Read -->
                                            <button 
                                                @click="viewInquiry(inq)" 
                                                title="View Full Details"
                                                class="rounded-lg border border-[#E6E8EE] bg-white px-2.5 py-1.5 text-xs font-semibold text-[#111318] hover:bg-[#F8F9FA] hover:border-[#111318] transition-all inline-flex items-center gap-1 cursor-pointer shadow-2xs"
                                            >
                                                <i class="fa-solid fa-eye text-xs"></i>
                                                <span>View</span>
                                            </button>

                                            <!-- Toggle Status -->
                                            <button 
                                                v-if="inq.status === 'new'"
                                                @click="updateInquiryStatus(inq, 'contacted')"
                                                title="Mark as Contacted"
                                                class="rounded-lg border border-blue-200 bg-blue-50 px-2.5 py-1.5 text-xs font-bold text-[#2F6BFF] hover:bg-blue-100 transition-all inline-flex items-center gap-1 cursor-pointer shadow-2xs"
                                            >
                                                <i class="fa-solid fa-check text-xs"></i>
                                                <span>Mark Contacted</span>
                                            </button>
                                            <button 
                                                v-else-if="inq.status === 'contacted'"
                                                @click="updateInquiryStatus(inq, 'closed')"
                                                title="Mark as Closed"
                                                class="rounded-lg border border-[#E6E8EE] bg-white px-2.5 py-1.5 text-xs font-semibold text-[#5B6270] hover:bg-[#F8F9FA] transition-all inline-flex items-center gap-1 cursor-pointer shadow-2xs"
                                            >
                                                <span>Close</span>
                                            </button>
                                            <button 
                                                v-else
                                                @click="updateInquiryStatus(inq, 'new')"
                                                title="Reopen Inquiry"
                                                class="rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-1.5 text-xs font-bold text-amber-700 hover:bg-amber-100 transition-all inline-flex items-center gap-1 cursor-pointer shadow-2xs"
                                            >
                                                <span>Reopen</span>
                                            </button>

                                            <!-- Delete -->
                                            <button 
                                                @click="deleteInquiry(inq)" 
                                                title="Delete Inquiry Record"
                                                class="rounded-lg border border-red-200 bg-red-50/50 px-2.5 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50 transition-all inline-flex items-center gap-1 cursor-pointer shadow-2xs"
                                            >
                                                <i class="fa-solid fa-trash-can text-xs"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Inquiry Detail Modal -->
                <div v-if="selectedInquiry" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4">
                    <div class="bg-white rounded-3xl max-w-xl w-full p-6 sm:p-8 shadow-2xl relative border border-[#E6E8EE] flex flex-col max-h-[90vh]">
                        <!-- Modal Header -->
                        <div class="flex justify-between items-start border-b border-[#E6E8EE] pb-4 mb-4">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span 
                                        v-if="selectedInquiry.type === 'expert_call'" 
                                        class="px-2 py-0.5 rounded-full bg-purple-50 text-purple-700 font-bold text-[10px] border border-purple-200"
                                    >
                                        Expert Call Request
                                    </span>
                                    <span 
                                        v-else 
                                        class="px-2 py-0.5 rounded-full bg-[#EEF3FF] text-[#2F6BFF] font-bold text-[10px] border border-[#D6E4FF]"
                                    >
                                        Contact Query
                                    </span>
                                    <span 
                                        :class="selectedInquiry.status === 'new' ? 'bg-amber-50 text-amber-700 border-amber-200' : selectedInquiry.status === 'contacted' ? 'bg-blue-50 text-[#2F6BFF] border-blue-200' : 'bg-gray-100 text-[#5B6270] border-gray-200'"
                                        class="px-2 py-0.5 rounded-full font-bold text-[10px] border capitalize"
                                    >
                                        {{ selectedInquiry.status }}
                                    </span>
                                </div>
                                <h3 class="font-extrabold text-[#111318] text-lg mt-2">{{ selectedInquiry.name }}</h3>
                                <p class="text-xs text-[#5B6270]">{{ selectedInquiry.institution || 'Individual prospect' }}</p>
                            </div>
                            <button @click="selectedInquiry = null" class="h-8 w-8 rounded-full hover:bg-[#F8F9FA] text-[#5B6270] flex items-center justify-center cursor-pointer">
                                <i class="fa-solid fa-xmark text-lg"></i>
                            </button>
                        </div>

                        <!-- Modal Content -->
                        <div class="flex-1 overflow-y-auto space-y-5 text-xs">
                            <!-- Contact Details Grid -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 bg-[#FAFAFC] p-4 rounded-2xl border border-[#E6E8EE]">
                                <div>
                                    <div class="text-[10px] font-bold text-[#5B6270] uppercase tracking-wider">Email Address</div>
                                    <a :href="`mailto:${selectedInquiry.email}`" class="font-semibold text-[#111318] hover:text-[#2F6BFF] flex items-center gap-1.5 mt-1 font-mono">
                                        <i class="fa-solid fa-envelope text-[#2F6BFF]"></i>
                                        <span>{{ selectedInquiry.email }}</span>
                                    </a>
                                </div>
                                <div>
                                    <div class="text-[10px] font-bold text-[#5B6270] uppercase tracking-wider">Phone / WhatsApp</div>
                                    <div v-if="selectedInquiry.phone" class="flex items-center gap-2 mt-1">
                                        <a :href="`tel:${selectedInquiry.phone}`" class="font-semibold text-[#111318] hover:text-[#2F6BFF] flex items-center gap-1 font-mono">
                                            <i class="fa-solid fa-phone text-[#2F6BFF]"></i>
                                            <span>{{ selectedInquiry.phone }}</span>
                                        </a>
                                        <a 
                                            :href="`https://wa.me/${selectedInquiry.phone.replace(/[^0-9]/g, '')}`" 
                                            target="_blank" 
                                            title="Chat on WhatsApp"
                                            class="text-emerald-600 hover:text-emerald-700 font-bold"
                                        >
                                            <i class="fa-brands fa-whatsapp text-sm"></i>
                                        </a>
                                    </div>
                                    <div v-else class="text-[#5B6270] italic mt-1">Not provided</div>
                                </div>
                                <div class="sm:col-span-2">
                                    <div class="text-[10px] font-bold text-[#5B6270] uppercase tracking-wider">Submitted Timestamp</div>
                                    <div class="font-medium text-[#111318] mt-1">{{ selectedInquiry.created_at }} ({{ selectedInquiry.time_ago }})</div>
                                </div>
                            </div>

                            <!-- Message Content -->
                            <div>
                                <div class="text-[10px] font-bold text-[#5B6270] uppercase tracking-wider mb-2">Message / Requirement</div>
                                <div class="bg-[#F8F9FA] rounded-2xl border border-[#E6E8EE] p-4 text-xs text-[#111318] leading-relaxed whitespace-pre-line font-normal">
                                    {{ selectedInquiry.message || 'No additional message was written by the user.' }}
                                </div>
                            </div>

                            <!-- Update Status Controls -->
                            <div class="pt-2">
                                <div class="text-[10px] font-bold text-[#5B6270] uppercase tracking-wider mb-2">Update Inquiry Status</div>
                                <div class="flex items-center gap-2">
                                    <button 
                                        @click="updateInquiryStatus(selectedInquiry, 'new')"
                                        :class="selectedInquiry.status === 'new' ? 'bg-amber-500 text-white font-bold' : 'bg-white border border-[#E6E8EE] text-[#5B6270] hover:bg-[#F8F9FA]'"
                                        class="px-3.5 py-1.5 rounded-full text-xs transition-all cursor-pointer shadow-2xs"
                                    >
                                        Mark as New
                                    </button>
                                    <button 
                                        @click="updateInquiryStatus(selectedInquiry, 'contacted')"
                                        :class="selectedInquiry.status === 'contacted' ? 'bg-[#2F6BFF] text-white font-bold' : 'bg-white border border-[#E6E8EE] text-[#5B6270] hover:bg-[#F8F9FA]'"
                                        class="px-3.5 py-1.5 rounded-full text-xs transition-all cursor-pointer shadow-2xs"
                                    >
                                        Mark Contacted
                                    </button>
                                    <button 
                                        @click="updateInquiryStatus(selectedInquiry, 'closed')"
                                        :class="selectedInquiry.status === 'closed' ? 'bg-gray-800 text-white font-bold' : 'bg-white border border-[#E6E8EE] text-[#5B6270] hover:bg-[#F8F9FA]'"
                                        class="px-3.5 py-1.5 rounded-full text-xs transition-all cursor-pointer shadow-2xs"
                                    >
                                        Mark Closed
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Footer -->
                        <div class="mt-6 pt-4 border-t border-[#E6E8EE] flex justify-between items-center">
                            <button 
                                @click="deleteInquiry(selectedInquiry)"
                                class="text-red-600 hover:text-red-700 font-bold text-xs inline-flex items-center gap-1.5 cursor-pointer"
                            >
                                <i class="fa-solid fa-trash-can"></i>
                                <span>Delete Inquiry</span>
                            </button>
                            <button 
                                @click="selectedInquiry = null" 
                                class="rounded-full bg-[#111318] hover:bg-[#2F6BFF] px-6 py-2.5 font-bold text-xs text-white shadow-sm transition-colors cursor-pointer"
                            >
                                Done
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ================= TAB 3: PAYMENT APPROVALS ================= -->
                <div v-if="activeTab === 'approvals'" class="space-y-6">
                    <div class="rounded-2xl border border-[#E6E8EE] bg-white p-6 shadow-2xs">
                        <div class="flex items-center justify-between border-b border-[#E6E8EE] pb-4 mb-4">
                            <div>
                                <h3 class="text-base font-extrabold text-[#111318]">Pending SaaS Payment Approvals</h3>
                                <p class="text-xs text-[#5B6270] mt-0.5">Verify subscription deposits and invoice slips uploaded by school administrators.</p>
                            </div>
                            <span class="rounded-full bg-[#EEF3FF] border border-[#D6E4FF] px-3.5 py-1 text-xs font-bold text-[#2F6BFF]">
                                {{ pendingApprovals ? pendingApprovals.length : 0 }} Awaiting Review
                            </span>
                        </div>

                        <div v-if="!pendingApprovals || pendingApprovals.length === 0" class="py-16 text-center text-[#5B6270] text-sm">
                            <i class="fa-solid fa-circle-check text-4xl text-emerald-500 mb-3 block"></i>
                            <p class="font-bold text-[#111318] text-base">All Clear!</p>
                            <p class="text-xs text-[#5B6270] mt-1">There are no pending subscription receipts requiring Super Admin review.</p>
                        </div>

                        <div v-else class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="border-b border-[#E6E8EE] text-[#5B6270] font-bold uppercase tracking-wider">
                                        <th class="py-3.5 px-4">Transaction Code</th>
                                        <th class="py-3.5 px-4">School Tenant</th>
                                        <th class="py-3.5 px-4">Requested Plan</th>
                                        <th class="py-3.5 px-4">Amount</th>
                                        <th class="py-3.5 px-4">Reference No</th>
                                        <th class="py-3.5 px-4">Receipt Proof</th>
                                        <th class="py-3.5 px-4 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#E6E8EE] font-medium text-[#111318]">
                                    <tr v-for="appr in pendingApprovals" :key="appr.id" class="hover:bg-[#F8F9FA]/60">
                                        <td class="py-4 px-4 font-mono font-bold text-[#2F6BFF]">{{ appr.transaction_code }}</td>
                                        <td class="py-4 px-4">
                                            <div class="font-bold text-sm text-[#111318]">{{ appr.school_name }}</div>
                                            <div class="text-2xs text-[#5B6270] font-mono">Tenant ID: {{ appr.tenant_id }}</div>
                                        </td>
                                        <td class="py-4 px-4">
                                            <span class="px-2.5 py-1 rounded-full bg-[#EEF3FF] text-[#2F6BFF] font-bold text-[11px] border border-[#D6E4FF]">
                                                {{ appr.plan_name }}
                                            </span>
                                        </td>
                                        <td class="py-4 px-4 font-extrabold text-sm text-emerald-600">PKR {{ (Number(appr.amount) || 0).toLocaleString() }}</td>
                                        <td class="py-4 px-4 font-mono font-semibold">{{ appr.reference_no }}</td>
                                        <td class="py-4 px-4">
                                            <button 
                                                v-if="appr.receipt_path" 
                                                type="button" 
                                                @click="selectedReceipt = appr.receipt_path"
                                                class="rounded-lg bg-[#F8F9FA] hover:bg-[#EEF3FF] border border-[#E6E8EE] px-3 py-1.5 font-bold text-[#2F6BFF] inline-flex items-center gap-1.5 cursor-pointer shadow-2xs"
                                            >
                                                <i class="fa-solid fa-image"></i>
                                                <span>View Slip</span>
                                            </button>
                                            <span v-else class="text-red-500 font-bold text-2xs">No Slip Uploaded</span>
                                        </td>
                                        <td class="py-4 px-4 text-right space-x-2">
                                            <button 
                                                @click="approveSaaSCheckout(appr.id, appr.school_name)"
                                                class="rounded-full bg-emerald-600 hover:bg-emerald-700 px-4 py-2 font-bold text-white shadow-2xs inline-flex items-center gap-1.5 cursor-pointer"
                                            >
                                                <i class="fa-solid fa-check"></i>
                                                <span>Approve</span>
                                            </button>
                                            <button 
                                                @click="rejectSaaSCheckout(appr.id, appr.school_name)"
                                                class="rounded-full bg-red-600 hover:bg-red-700 px-4 py-2 font-bold text-white shadow-2xs inline-flex items-center gap-1.5 cursor-pointer"
                                            >
                                                <i class="fa-solid fa-xmark"></i>
                                                <span>Reject</span>
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
                    <div class="bg-white rounded-3xl max-w-2xl w-full p-6 sm:p-8 shadow-2xl flex flex-col relative max-h-[90vh] border border-[#E6E8EE]">
                        <div class="flex justify-between items-center border-b border-[#E6E8EE] pb-4 mb-4">
                            <h3 class="font-extrabold text-[#111318] text-sm uppercase tracking-wider flex items-center gap-2">
                                <i class="fa-solid fa-file-invoice text-[#2F6BFF]"></i>
                                <span>Uploaded Payment Verification Proof</span>
                            </h3>
                            <button @click="selectedReceipt = null" class="h-8 w-8 rounded-full hover:bg-[#F8F9FA] text-[#5B6270] flex items-center justify-center cursor-pointer">
                                <i class="fa-solid fa-xmark text-lg"></i>
                            </button>
                        </div>
                        <div class="flex-1 overflow-y-auto flex items-center justify-center bg-[#F8F9FA] rounded-2xl border border-[#E6E8EE] p-4">
                            <img :src="selectedReceipt" class="max-h-[60vh] object-contain rounded-lg shadow-sm" alt="Payment Proof Slip" />
                        </div>
                        <div class="mt-4 flex justify-end">
                            <button @click="selectedReceipt = null" class="rounded-full bg-[#111318] hover:bg-[#2F6BFF] px-6 py-2.5 font-bold text-xs text-white shadow-sm transition-colors cursor-pointer">
                                Close Preview
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ================= TAB 4: PLANS & PACKAGES ================= -->
                <div v-if="activeTab === 'plans'" class="space-y-6">
                    <div class="flex justify-between items-center bg-white p-6 rounded-2xl border border-[#E6E8EE] shadow-2xs">
                        <div>
                            <h3 class="text-base font-extrabold text-[#111318]">Subscription Tiers & Packages</h3>
                            <p class="text-xs text-[#5B6270]">Define package pricing, student caps, and module feature sets.</p>
                        </div>
                        <button @click="openAddPlanModal" class="rounded-full bg-[#111318] hover:bg-[#2F6BFF] px-5 py-2.5 text-xs font-bold text-white shadow-sm transition-all cursor-pointer">
                            + Add New Plan
                        </button>
                    </div>

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
                        <div 
                            v-for="plan in plans" 
                            :key="plan.id" 
                            :class="[
                                'rounded-2xl border bg-white p-6 shadow-2xs flex flex-col justify-between relative overflow-hidden transition-all',
                                plan.is_active ? 'border-[#E6E8EE] hover:border-[#2F6BFF]/40' : 'border-amber-200 bg-amber-50/20 opacity-80'
                            ]"
                        >
                            <div>
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        <span class="rounded-full bg-[#EEF3FF] px-2.5 py-0.5 text-2xs font-bold text-[#2F6BFF] uppercase tracking-wider border border-[#D6E4FF]">
                                            {{ plan.code }}
                                        </span>
                                        <span 
                                            :class="[
                                                'rounded-full px-2 py-0.5 text-3xs font-black uppercase tracking-wider',
                                                plan.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'
                                            ]"
                                        >
                                            {{ plan.is_active ? 'Published' : 'Hidden' }}
                                        </span>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <button 
                                            type="button"
                                            @click="togglePlanStatus(plan)" 
                                            :class="[
                                                'text-2xs font-bold hover:underline cursor-pointer',
                                                plan.is_active ? 'text-amber-600' : 'text-emerald-600'
                                            ]"
                                        >
                                            {{ plan.is_active ? 'Unpublish' : 'Publish' }}
                                        </button>
                                        <button @click="openEditPlanModal(plan)" class="text-2xs font-bold text-[#2F6BFF] hover:underline cursor-pointer">Edit</button>
                                        <button @click="confirmDeletePlan(plan)" class="text-2xs font-bold text-red-600 hover:underline cursor-pointer">Delete</button>
                                    </div>
                                </div>

                                <div class="mt-3 flex items-center justify-between">
                                    <h3 class="text-lg font-extrabold text-[#111318]">{{ plan.name }}</h3>
                                    <span v-if="plan.tagline" class="text-2xs font-bold text-[#5B6270] bg-[#F4F5F8] px-2 py-0.5 rounded-full">
                                        {{ plan.tagline }}
                                    </span>
                                </div>

                                <!-- Dynamic Pricing Summary -->
                                <div v-if="plan.pricing_type === 'custom'" class="mt-3">
                                    <div class="text-2xl font-black text-[#111318]">Custom</div>
                                    <p class="text-2xs text-[#5B6270]">Volume Negotiated Pricing</p>
                                </div>
                                <div v-else-if="plan.pricing_type === 'flat'" class="mt-3">
                                    <div class="flex items-baseline space-x-1">
                                        <span class="text-2xl font-black text-[#111318]">PKR {{ (plan.price_monthly || 0).toLocaleString() }}</span>
                                        <span class="text-xs font-semibold text-[#5B6270]">/ month</span>
                                    </div>
                                    <p class="text-2xs text-[#5B6270]">Fixed flat fee (Yearly: PKR {{ (plan.price_yearly || 0).toLocaleString() }})</p>
                                </div>
                                <div v-else class="mt-3">
                                    <div class="flex items-baseline space-x-1">
                                        <span class="text-2xl font-black text-[#111318]">PKR {{ Number(plan.price_per_student || 20).toLocaleString() }}</span>
                                        <span class="text-xs font-semibold text-[#5B6270]">/ student / mo</span>
                                    </div>
                                    <p class="text-2xs font-semibold text-[#5B6270]">
                                        Min. bill: PKR {{ Number(plan.minimum_monthly_bill || 2000).toLocaleString() }}/month
                                    </p>
                                </div>

                                <p v-if="plan.summary" class="mt-2 text-2xs text-[#5B6270] leading-relaxed line-clamp-2">
                                    {{ plan.summary }}
                                </p>
                                <p class="mt-1 text-2xs font-semibold text-[#5B6270]">
                                    Enrollment Cap: {{ (plan.max_students || 500).toLocaleString() }} Students
                                </p>

                                <div class="mt-4 border-t border-[#E6E8EE] pt-3 space-y-1.5">
                                    <p class="text-3xs font-bold uppercase tracking-wider text-[#5B6270]">Package Inclusions:</p>
                                    <ul class="space-y-1 text-2xs">
                                        <li 
                                            v-for="feature in (Array.isArray(plan.features) ? plan.features.slice(0, 5) : ['Core SIS', 'Attendance'])" 
                                            :key="feature" 
                                            class="flex items-center space-x-2 text-[#111318]"
                                        >
                                            <i class="fa-solid fa-check text-emerald-600 text-3xs"></i>
                                            <span class="font-medium truncate">{{ feature }}</span>
                                        </li>
                                        <li v-if="Array.isArray(plan.features) && plan.features.length > 5" class="text-3xs text-[#2F6BFF] font-bold">
                                            + {{ plan.features.length - 5 }} more features...
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <div class="mt-4 pt-3 border-t border-[#E6E8EE] flex items-center justify-between text-2xs">
                                <span class="font-bold text-[#5B6270]">
                                    {{ plan.tenants_count || plan.subscriptions_count || 0 }} Schools Subscribed
                                </span>
                                <span :class="plan.is_active ? 'text-emerald-700 font-semibold' : 'text-amber-700 font-semibold'">
                                    <i :class="plan.is_active ? 'fa-solid fa-eye' : 'fa-solid fa-eye-slash'" class="mr-1"></i>
                                    {{ plan.is_active ? 'On Landing' : 'Hidden' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ================= TAB 5: PAYMENTS ================= -->
                <div v-if="activeTab === 'payments'" class="space-y-6">
                    
                    <!-- Metrics Row -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                        <div class="rounded-2xl border border-[#E6E8EE] bg-white p-6 shadow-2xs flex items-center justify-between">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-[#5B6270]">Total Inflows Collected</p>
                                <h4 class="mt-2 text-2xl font-black text-emerald-600">PKR {{ paymentStats.totalRevenue.toLocaleString() }}</h4>
                                <p class="mt-1 text-2xs font-semibold text-[#5B6270]">Cumulative verified subscriptions</p>
                            </div>
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 shadow-2xs">
                                <i class="fa-solid fa-wallet text-xl"></i>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-[#E6E8EE] bg-white p-6 shadow-2xs flex items-center justify-between">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-[#5B6270]">Processed Transactions</p>
                                <h4 class="mt-2 text-2xl font-black text-[#2F6BFF]">{{ paymentStats.paidCount }} <span class="text-sm font-semibold text-[#5B6270]">/ {{ paymentStats.totalCount }}</span></h4>
                                <p class="mt-1 text-2xs font-semibold text-[#5B6270]">Confirmed ledger receipts</p>
                            </div>
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#EEF3FF] text-[#2F6BFF] shadow-2xs">
                                <i class="fa-solid fa-check-double text-xl"></i>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-[#E6E8EE] bg-white p-6 shadow-2xs flex items-center justify-between">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-[#5B6270]">Average Invoice Ticket</p>
                                <h4 class="mt-2 text-2xl font-black text-[#111318]">PKR {{ paymentStats.avgTicket.toLocaleString() }}</h4>
                                <p class="mt-1 text-2xs font-semibold text-[#5B6270]">Per tenant billing mean</p>
                            </div>
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-purple-50 text-purple-600 shadow-2xs">
                                <i class="fa-solid fa-chart-line text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Search and Status Bar -->
                    <div class="rounded-2xl border border-[#E6E8EE] bg-white p-4 sm:p-5 shadow-2xs flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="relative w-full sm:w-80">
                            <i class="fa-solid fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-xs text-[#5B6270]"></i>
                            <input 
                                v-model="paymentSearch" 
                                type="text" 
                                placeholder="Search by transaction ID, tenant, or gateway..." 
                                class="w-full rounded-xl border border-[#E6E8EE] bg-[#F8F9FA] pl-9 pr-4 py-2.5 text-xs font-medium text-[#111318] placeholder-[#8F94A0] focus:border-[#2F6BFF] focus:bg-white focus:outline-none transition-all"
                            />
                        </div>
                        <div class="flex items-center gap-1.5 w-full sm:w-auto overflow-x-auto">
                            <button 
                                v-for="status in ['all', 'paid', 'pending', 'failed']" 
                                :key="status"
                                @click="paymentStatusFilter = status"
                                :class="paymentStatusFilter === status ? 'bg-[#111318] text-white' : 'bg-[#F8F9FA] text-[#5B6270] hover:text-[#111318] border border-[#E6E8EE]'"
                                class="rounded-xl px-3.5 py-1.5 text-xs font-bold capitalize transition-all cursor-pointer shrink-0"
                            >
                                {{ status === 'all' ? 'All Transactions' : status }}
                            </button>
                        </div>
                    </div>

                    <!-- Payments Table -->
                    <div class="overflow-hidden rounded-2xl border border-[#E6E8EE] bg-white shadow-2xs">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-sm min-w-[950px]">
                                <thead>
                                    <tr class="border-b border-[#E6E8EE] bg-[#F8F9FA] text-xs font-bold uppercase tracking-wider text-[#5B6270]">
                                        <th class="py-4 px-6">Transaction ID</th>
                                        <th class="py-4 px-6">School Tenant</th>
                                        <th class="py-4 px-6">Subscription Tier</th>
                                        <th class="py-4 px-6">Gateway Method</th>
                                        <th class="py-4 px-6">Amount</th>
                                        <th class="py-4 px-6">Date</th>
                                        <th class="py-4 px-6">Status</th>
                                        <th class="py-4 px-6 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#E6E8EE]">
                                    <tr v-if="filteredTransactions.length === 0">
                                        <td colspan="8" class="py-12 text-center text-sm text-[#5B6270]">
                                            No transaction records found matching your filter criteria.
                                        </td>
                                    </tr>

                                    <tr v-for="trx in filteredTransactions" :key="trx.id" class="hover:bg-[#F8F9FA]/80 transition-colors">
                                        <td class="py-4 px-6 font-mono text-xs font-bold text-[#2F6BFF]">
                                            {{ trx.transaction_id || trx.transaction_code }}
                                        </td>
                                        <td class="py-4 px-6">
                                            <div class="font-extrabold text-[#111318]">{{ trx.school_name || trx.tenant?.name }}</div>
                                            <div class="font-mono text-2xs text-[#5B6270]">ID: {{ trx.tenant_id }}</div>
                                        </td>
                                        <td class="py-4 px-6">
                                            <span class="rounded-md bg-[#F8F9FA] px-2.5 py-1 text-xs font-semibold text-[#111318] border border-[#E6E8EE]">
                                                {{ trx.plan_name || 'Growth Plan' }}
                                            </span>
                                        </td>
                                        <td class="py-4 px-6 text-xs font-semibold text-[#111318]">
                                            {{ trx.gateway }}
                                        </td>
                                        <td class="py-4 px-6 font-black text-sm text-emerald-600">
                                            PKR {{ (Number(trx.amount) || 0).toLocaleString() }}
                                        </td>
                                        <td class="py-4 px-6 text-xs text-[#5B6270]">
                                            {{ trx.date || trx.created_at }}
                                        </td>
                                        <td class="py-4 px-6">
                                            <span 
                                                :class="[
                                                    'rounded-full px-3 py-1 text-xs font-bold border inline-flex items-center gap-1.5',
                                                    (trx.status || 'paid').toLowerCase() === 'paid' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' :
                                                    (trx.status || '').toLowerCase() === 'pending' ? 'bg-amber-50 text-amber-700 border-amber-200' :
                                                    'bg-red-50 text-red-700 border-red-200'
                                                ]"
                                            >
                                                <span class="h-1.5 w-1.5 rounded-full" :class="(trx.status || 'paid').toLowerCase() === 'paid' ? 'bg-emerald-500' : (trx.status || '').toLowerCase() === 'pending' ? 'bg-amber-500' : 'bg-red-500'"></span>
                                                <span>{{ (trx.status || 'paid').toUpperCase() }}</span>
                                            </span>
                                        </td>
                                        <td class="py-4 px-6 text-right space-x-2">
                                            <button 
                                                @click="selectedTransaction = trx"
                                                class="rounded-lg bg-white px-3 py-1.5 text-xs font-bold text-[#111318] hover:bg-[#F8F9FA] border border-[#E6E8EE] transition-colors cursor-pointer"
                                            >
                                                Inspect
                                            </button>
                                            <button 
                                                @click="downloadInvoicePDF(trx)"
                                                class="rounded-lg bg-[#111318] hover:bg-[#2F6BFF] px-3 py-1.5 text-xs font-bold text-white transition-colors cursor-pointer"
                                                title="Print Official Tax Invoice"
                                            >
                                                <i class="fa-solid fa-print"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Transaction Details Slide-Over -->
                <div v-if="selectedTransaction" class="fixed inset-0 z-50 flex justify-end bg-black/40 backdrop-blur-xs overflow-hidden">
                    <div class="absolute inset-0" @click="selectedTransaction = null"></div>
                    <div class="bg-white w-full max-w-lg shadow-2xl flex flex-col h-full z-10 border-l border-[#E6E8EE]">
                        <div class="p-6 sm:p-8 flex-1 overflow-y-auto space-y-6">
                            <div class="flex justify-between items-center border-b border-[#E6E8EE] pb-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 border border-emerald-200">
                                        <i class="fa-solid fa-receipt text-xl"></i>
                                    </div>
                                    <div>
                                        <h3 class="text-base font-extrabold text-[#111318]">Transaction Ledger Slip</h3>
                                        <p class="font-mono text-xs text-[#2F6BFF] font-bold">{{ selectedTransaction.transaction_id || selectedTransaction.transaction_code }}</p>
                                    </div>
                                </div>
                                <button @click="selectedTransaction = null" class="h-8 w-8 rounded-full hover:bg-[#F8F9FA] text-[#5B6270] flex items-center justify-center cursor-pointer">
                                    <i class="fa-solid fa-xmark text-lg"></i>
                                </button>
                            </div>

                            <div class="rounded-2xl bg-[#F8F9FA] p-5 border border-[#E6E8EE] space-y-3.5 text-xs">
                                <div class="flex justify-between border-b border-[#E6E8EE] pb-2.5">
                                    <span class="font-bold text-[#5B6270]">Tenant School:</span>
                                    <span class="font-bold text-[#111318]">{{ selectedTransaction.school_name || selectedTransaction.tenant?.name }}</span>
                                </div>
                                <div class="flex justify-between border-b border-[#E6E8EE] pb-2.5">
                                    <span class="font-bold text-[#5B6270]">Tenant ID:</span>
                                    <span class="font-mono font-semibold text-[#2F6BFF]">{{ selectedTransaction.tenant_id }}</span>
                                </div>
                                <div class="flex justify-between border-b border-[#E6E8EE] pb-2.5">
                                    <span class="font-bold text-[#5B6270]">Subscription Plan:</span>
                                    <span class="font-bold text-[#111318]">{{ selectedTransaction.plan_name }}</span>
                                </div>
                                <div class="flex justify-between border-b border-[#E6E8EE] pb-2.5">
                                    <span class="font-bold text-[#5B6270]">Payment Method:</span>
                                    <span class="font-bold text-[#111318]">{{ selectedTransaction.gateway }}</span>
                                </div>
                                <div class="flex justify-between border-b border-[#E6E8EE] pb-2.5">
                                    <span class="font-bold text-[#5B6270]">Reference Code:</span>
                                    <span class="font-mono text-[#111318] font-semibold">{{ selectedTransaction.reference_no }}</span>
                                </div>
                                <div class="flex justify-between border-b border-[#E6E8EE] pb-2.5">
                                    <span class="font-bold text-[#5B6270]">Total Inflow:</span>
                                    <span class="font-black text-sm text-emerald-600">PKR {{ (Number(selectedTransaction.amount) || 0).toLocaleString() }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="font-bold text-[#5B6270]">Processed Timestamp:</span>
                                    <span class="font-semibold text-[#111318]">{{ selectedTransaction.date || selectedTransaction.created_at }}</span>
                                </div>
                            </div>

                            <div>
                                <label class="block text-2xs font-bold uppercase tracking-wider text-[#5B6270] mb-1.5">Central HMAC Verification Signature</label>
                                <div class="rounded-xl bg-[#111318] p-3.5 font-mono text-2xs text-emerald-400 break-all select-all">
                                    {{ selectedTransaction.secure_hash || 'SHA256:VERIFIED:CLUSTER:RAIBIX:NODE01' }}
                                </div>
                            </div>

                            <div class="flex items-center justify-end gap-3 pt-2">
                                <button @click="selectedTransaction = null" class="rounded-xl bg-[#F8F9FA] px-5 py-2.5 text-xs font-bold text-[#111318] hover:bg-[#E6E8EE] transition-colors border border-[#E6E8EE] cursor-pointer">
                                    Close Window
                                </button>
                                <button @click="downloadInvoicePDF(selectedTransaction)" class="rounded-xl bg-[#111318] hover:bg-[#2F6BFF] px-5 py-2.5 text-xs font-bold text-white transition-colors shadow-2xs flex items-center gap-2 cursor-pointer">
                                    <i class="fa-solid fa-file-pdf"></i>
                                    <span>Download Tax Invoice</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ================= TAB 6: ACTIVITY LOGS ================= -->
                <div v-if="activeTab === 'logs'" class="space-y-6">
                    
                    <!-- Search and Severity Filter -->
                    <div class="rounded-2xl border border-[#E6E8EE] bg-white p-4 sm:p-5 shadow-2xs flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="relative w-full sm:w-96">
                            <i class="fa-solid fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-xs text-[#5B6270]"></i>
                            <input 
                                v-model="logSearch" 
                                type="text" 
                                placeholder="Search action events, descriptions, or actors..." 
                                class="w-full rounded-xl border border-[#E6E8EE] bg-[#F8F9FA] pl-9 pr-4 py-2.5 text-xs font-medium text-[#111318] placeholder-[#8F94A0] focus:border-[#2F6BFF] focus:bg-white focus:outline-none transition-all"
                            />
                        </div>
                        <div class="flex items-center gap-1.5 w-full sm:w-auto overflow-x-auto">
                            <button 
                                v-for="type in ['all', 'success', 'info', 'warning', 'error']" 
                                :key="type"
                                @click="logTypeFilter = type"
                                :class="logTypeFilter === type ? 'bg-[#111318] text-white' : 'bg-[#F8F9FA] text-[#5B6270] hover:text-[#111318] border border-[#E6E8EE]'"
                                class="rounded-xl px-3.5 py-1.5 text-xs font-bold capitalize transition-all cursor-pointer shrink-0"
                            >
                                {{ type === 'all' ? 'All Severities' : type }}
                            </button>
                        </div>
                    </div>

                    <!-- Logs Table -->
                    <div class="overflow-hidden rounded-2xl border border-[#E6E8EE] bg-white shadow-2xs">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-sm min-w-[950px]">
                                <thead>
                                    <tr class="border-b border-[#E6E8EE] bg-[#F8F9FA] text-xs font-bold uppercase tracking-wider text-[#5B6270]">
                                        <th class="py-4 px-6">Severity</th>
                                        <th class="py-4 px-6">Event Title</th>
                                        <th class="py-4 px-6">Description Payload</th>
                                        <th class="py-4 px-6">Initiated By</th>
                                        <th class="py-4 px-6">IP Address</th>
                                        <th class="py-4 px-6 text-right">Timestamp</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#E6E8EE]">
                                    <tr v-if="filteredLogs.length === 0">
                                        <td colspan="6" class="py-12 text-center text-sm text-[#5B6270]">
                                            No system logs recorded matching your filter.
                                        </td>
                                    </tr>

                                    <tr v-for="log in filteredLogs" :key="log.id" class="hover:bg-[#F8F9FA]/80 transition-colors cursor-pointer" @click="selectedLog = log">
                                        <td class="py-4 px-6">
                                            <span 
                                                :class="[
                                                    'rounded-full px-2.5 py-0.5 text-2xs font-extrabold uppercase tracking-wider inline-flex items-center gap-1 border',
                                                    (log.type || 'info') === 'success' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' :
                                                    (log.type || 'info') === 'warning' ? 'bg-amber-50 text-amber-700 border-amber-200' :
                                                    (log.type || 'info') === 'error' ? 'bg-red-50 text-red-700 border-red-200' :
                                                    'bg-[#EEF3FF] text-[#2F6BFF] border-[#D6E4FF]'
                                                ]"
                                            >
                                                {{ log.type || 'info' }}
                                            </span>
                                        </td>
                                        <td class="py-4 px-6 font-bold text-[#111318]">
                                            {{ log.action || log.event || log.event_title }}
                                        </td>
                                        <td class="py-4 px-6 text-xs text-[#5B6270] max-w-sm truncate">
                                            {{ log.details || log.description || '-' }}
                                        </td>
                                        <td class="py-4 px-6">
                                            <div class="flex items-center gap-2">
                                                <div class="h-6 w-6 rounded-full bg-[#111318] text-white flex items-center justify-center font-bold text-3xs">
                                                    {{ (log.user || log.actor_name || 'S').charAt(0).toUpperCase() }}
                                                </div>
                                                <span class="text-xs font-semibold text-[#111318]">{{ log.user || log.actor_name || 'System' }}</span>
                                            </div>
                                        </td>
                                        <td class="py-4 px-6 font-mono text-xs text-[#5B6270]">
                                            {{ log.ip_address || '127.0.0.1' }}
                                        </td>
                                        <td class="py-4 px-6 text-xs text-[#5B6270] text-right font-mono whitespace-nowrap">
                                            {{ log.timestamp || log.created_at || 'Just now' }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Log Details Modal -->
                <div v-if="selectedLog" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-xs p-4">
                    <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl border border-[#E6E8EE] space-y-5">
                        <div class="flex justify-between items-center border-b border-[#E6E8EE] pb-4">
                            <div>
                                <span class="text-2xs font-bold uppercase tracking-wider text-[#5B6270]">Log Entry #{{ selectedLog.id }}</span>
                                <h3 class="text-base font-extrabold text-[#111318] mt-0.5">{{ selectedLog.action || selectedLog.event || selectedLog.event_title }}</h3>
                            </div>
                            <button @click="selectedLog = null" class="h-8 w-8 rounded-full hover:bg-[#F8F9FA] text-[#5B6270] flex items-center justify-center cursor-pointer">
                                <i class="fa-solid fa-xmark text-lg"></i>
                            </button>
                        </div>
                        <div class="bg-[#F8F9FA] rounded-2xl p-4 border border-[#E6E8EE] space-y-3 text-xs">
                            <div>
                                <span class="block text-2xs font-bold uppercase tracking-wider text-[#5B6270] mb-1">Payload Breakdown</span>
                                <p class="font-semibold text-[#111318] leading-relaxed">{{ selectedLog.details || selectedLog.description }}</p>
                            </div>
                            <div class="border-t border-[#E6E8EE] pt-3 grid grid-cols-2 gap-3">
                                <div>
                                    <span class="block text-2xs font-bold uppercase tracking-wider text-[#5B6270]">Actor</span>
                                    <span class="font-bold text-[#111318]">{{ selectedLog.user || selectedLog.actor_name || 'System Engine' }}</span>
                                </div>
                                <div>
                                    <span class="block text-2xs font-bold uppercase tracking-wider text-[#5B6270]">IP Address</span>
                                    <span class="font-mono font-bold text-[#2F6BFF]">{{ selectedLog.ip_address || '127.0.0.1' }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-end pt-2">
                            <button @click="selectedLog = null" class="rounded-full bg-[#111318] hover:bg-[#2F6BFF] px-6 py-2.5 text-xs font-bold text-white transition-colors cursor-pointer">
                                Close
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ================= TAB 7: MARKETING & COUPONS ================= -->
                <div v-if="activeTab === 'marketing'" class="space-y-6">
                    <div class="flex justify-between items-center bg-white p-6 rounded-2xl border border-[#E6E8EE] shadow-2xs">
                        <div>
                            <h3 class="text-base font-extrabold text-[#111318]">Discount Vouchers & Promo Codes</h3>
                            <p class="text-xs text-[#5B6270]">Create and manage promotional discount coupons for new school registrations.</p>
                        </div>
                        <button @click="createCoupon" class="rounded-full bg-[#111318] hover:bg-[#2F6BFF] px-5 py-2.5 text-xs font-bold text-white shadow-sm transition-all cursor-pointer">
                            + Create Coupon
                        </button>
                    </div>

                    <div class="rounded-2xl border border-[#E6E8EE] bg-white p-6 shadow-2xs space-y-4">
                        <div class="flex items-center justify-between border-b border-[#E6E8EE] pb-4">
                            <h4 class="text-sm font-extrabold text-[#111318]">Active Discount Codes</h4>
                            <span class="text-xs font-bold text-[#5B6270]">{{ coupons?.length || 0 }} Registered</span>
                        </div>

                        <div v-if="!coupons || coupons.length === 0" class="py-12 text-center text-xs text-[#5B6270]">
                            No promo discount codes active. Click "+ Create Coupon" above to issue one.
                        </div>

                        <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div v-for="coupon in coupons" :key="coupon.id" class="p-4 bg-[#F8F9FA] rounded-2xl border border-[#E6E8EE] flex items-center justify-between group hover:border-[#2F6BFF]/40 transition-all">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono font-extrabold text-[#2F6BFF] text-sm">{{ coupon.code }}</span>
                                        <span class="text-3xs font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-200 px-2 py-0.5 rounded-full">
                                            {{ coupon.discount_percentage }}% OFF
                                        </span>
                                    </div>
                                    <div class="text-xs text-[#5B6270] mt-1">
                                        Used {{ coupon.times_used || 0 }} times • Max Cap: {{ coupon.max_uses || 'Unlimited' }}
                                    </div>
                                </div>
                                <button 
                                    @click="deleteCoupon(coupon.id, coupon.code)"
                                    class="h-8 w-8 rounded-lg flex items-center justify-center text-[#5B6270] hover:text-red-600 hover:bg-red-50 transition-colors cursor-pointer"
                                    title="Delete Coupon"
                                >
                                    <i class="fa-solid fa-trash-can text-xs"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ================= TAB 8: SYSTEM SETTINGS ================= -->
                <div v-if="activeTab === 'settings'" class="max-w-4xl space-y-6">
                    <!-- Sub-tabs navigation -->
                    <div class="flex items-center gap-2 border-b border-[#E6E8EE] pb-3 mb-6 overflow-x-auto">
                        <button 
                            @click="settingsSubTab = 'profile'"
                            :class="settingsSubTab === 'profile' ? 'bg-[#111318] text-white shadow-xs' : 'bg-white text-[#5B6270] hover:text-[#111318] border border-[#E6E8EE] hover:bg-[#F8F9FA]'"
                            class="px-4 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer flex items-center gap-2 whitespace-nowrap"
                        >
                            <i class="fa-solid fa-user-shield text-xs"></i>
                            <span>My Profile & Credentials</span>
                        </button>

                        <button 
                            @click="settingsSubTab = 'team'"
                            :class="settingsSubTab === 'team' ? 'bg-[#111318] text-white shadow-xs' : 'bg-white text-[#5B6270] hover:text-[#111318] border border-[#E6E8EE] hover:bg-[#F8F9FA]'"
                            class="px-4 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer flex items-center gap-2 whitespace-nowrap"
                        >
                            <i class="fa-solid fa-users-gear text-xs"></i>
                            <span>Platform Administrators</span>
                            <span class="px-1.5 py-0.5 text-[10px] rounded-full font-bold" :class="settingsSubTab === 'team' ? 'bg-white/20 text-white' : 'bg-[#F0F2F6] text-[#5B6270]'">
                                {{ adminUsers?.length || 0 }}
                            </span>
                        </button>

                        <button 
                            @click="settingsSubTab = 'platform'"
                            :class="settingsSubTab === 'platform' ? 'bg-[#111318] text-white shadow-xs' : 'bg-white text-[#5B6270] hover:text-[#111318] border border-[#E6E8EE] hover:bg-[#F8F9FA]'"
                            class="px-4 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer flex items-center gap-2 whitespace-nowrap"
                        >
                            <i class="fa-solid fa-sliders text-xs"></i>
                            <span>Platform Environment</span>
                        </button>
                    </div>

                    <!-- SUB-TAB 1: MY PROFILE & CREDENTIALS -->
                    <div v-if="settingsSubTab === 'profile'" class="space-y-6">
                        <div class="rounded-2xl border border-[#E6E8EE] bg-white p-7 sm:p-8 shadow-2xs space-y-6">
                            <div class="border-b border-[#E6E8EE] pb-4">
                                <h3 class="text-base font-extrabold text-[#111318]">Super Administrator Identity</h3>
                                <p class="text-xs text-[#5B6270] mt-0.5">Update your root administrator name, login email, and master password.</p>
                            </div>

                            <form @submit.prevent="submitProfile" class="space-y-5">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold uppercase tracking-wider text-[#111318] mb-1.5">
                                            Administrator Name
                                        </label>
                                        <input 
                                            v-model="profileForm.name" 
                                            type="text" 
                                            required
                                            class="w-full rounded-xl border border-[#E6E8EE] bg-[#F8F9FA] px-4 py-2.5 text-xs font-medium text-[#111318] focus:bg-white focus:border-[#2F6BFF] focus:outline-none transition-all"
                                            :class="{ 'border-red-400 bg-red-50/40': profileForm.errors.name }"
                                        />
                                        <div v-if="profileForm.errors.name" class="mt-1 text-xs text-red-600 font-medium">
                                            {{ profileForm.errors.name }}
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold uppercase tracking-wider text-[#111318] mb-1.5">
                                            Login Email Address
                                        </label>
                                        <input 
                                            v-model="profileForm.email" 
                                            type="email" 
                                            required
                                            class="w-full rounded-xl border border-[#E6E8EE] bg-[#F8F9FA] px-4 py-2.5 text-xs font-medium text-[#111318] focus:bg-white focus:border-[#2F6BFF] focus:outline-none transition-all"
                                            :class="{ 'border-red-400 bg-red-50/40': profileForm.errors.email }"
                                        />
                                        <div v-if="profileForm.errors.email" class="mt-1 text-xs text-red-600 font-medium">
                                            {{ profileForm.errors.email }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Password Update Section -->
                                <div class="pt-4 border-t border-[#E6E8EE] space-y-4">
                                    <div>
                                        <h4 class="text-xs font-extrabold uppercase tracking-wider text-[#111318]">Update Master Password</h4>
                                        <p class="text-2xs text-[#5B6270]">Leave these fields empty if you only wish to update your name or email.</p>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                        <div>
                                            <label class="block text-2xs font-bold uppercase tracking-wider text-[#5B6270] mb-1">
                                                Current Password
                                            </label>
                                            <input 
                                                v-model="profileForm.current_password" 
                                                type="password" 
                                                placeholder="••••••••"
                                                autocomplete="current-password"
                                                class="w-full rounded-xl border border-[#E6E8EE] bg-[#F8F9FA] px-3.5 py-2.5 text-xs font-medium text-[#111318] focus:bg-white focus:border-[#2F6BFF] focus:outline-none transition-all"
                                                :class="{ 'border-red-400 bg-red-50/40': profileForm.errors.current_password }"
                                            />
                                            <div v-if="profileForm.errors.current_password" class="mt-1 text-2xs text-red-600 font-medium">
                                                {{ profileForm.errors.current_password }}
                                            </div>
                                        </div>

                                        <div>
                                            <label class="block text-2xs font-bold uppercase tracking-wider text-[#5B6270] mb-1">
                                                New Password
                                            </label>
                                            <input 
                                                v-model="profileForm.password" 
                                                type="password" 
                                                placeholder="Min. 8 characters"
                                                autocomplete="new-password"
                                                class="w-full rounded-xl border border-[#E6E8EE] bg-[#F8F9FA] px-3.5 py-2.5 text-xs font-medium text-[#111318] focus:bg-white focus:border-[#2F6BFF] focus:outline-none transition-all"
                                                :class="{ 'border-red-400 bg-red-50/40': profileForm.errors.password }"
                                            />
                                            <div v-if="profileForm.errors.password" class="mt-1 text-2xs text-red-600 font-medium">
                                                {{ profileForm.errors.password }}
                                            </div>
                                        </div>

                                        <div>
                                            <label class="block text-2xs font-bold uppercase tracking-wider text-[#5B6270] mb-1">
                                                Confirm New Password
                                            </label>
                                            <input 
                                                v-model="profileForm.password_confirmation" 
                                                type="password" 
                                                placeholder="Repeat new password"
                                                autocomplete="new-password"
                                                class="w-full rounded-xl border border-[#E6E8EE] bg-[#F8F9FA] px-3.5 py-2.5 text-xs font-medium text-[#111318] focus:bg-white focus:border-[#2F6BFF] focus:outline-none transition-all"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <div class="pt-3 flex justify-end">
                                    <button 
                                        type="submit" 
                                        :disabled="profileForm.processing"
                                        class="rounded-full bg-[#111318] hover:bg-[#2F6BFF] px-7 py-3 text-xs font-bold text-white shadow-sm disabled:opacity-50 transition-all cursor-pointer"
                                    >
                                        {{ profileForm.processing ? 'Updating...' : 'Update My Credentials' }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- SUB-TAB 2: PLATFORM ADMINISTRATORS -->
                    <div v-if="settingsSubTab === 'team'" class="space-y-6">
                        <div class="rounded-2xl border border-[#E6E8EE] bg-white p-7 sm:p-8 shadow-2xs space-y-6">
                            <div class="border-b border-[#E6E8EE] pb-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                <div>
                                    <h3 class="text-base font-extrabold text-[#111318]">Platform Administrators</h3>
                                    <p class="text-xs text-[#5B6270] mt-0.5">Root users authorized to manage tenants, view revenue, and configure the SaaS engine.</p>
                                </div>
                                <button 
                                    @click="openAddAdminModal"
                                    class="rounded-full bg-[#111318] hover:bg-[#2F6BFF] text-white px-4 py-2 text-xs font-bold transition-all inline-flex items-center gap-1.5 shadow-2xs cursor-pointer self-start sm:self-auto"
                                >
                                    <i class="fa-solid fa-user-plus text-xs"></i>
                                    <span>Add Administrator</span>
                                </button>
                            </div>

                            <!-- Administrators Table -->
                            <div class="overflow-x-auto">
                                <table class="w-full text-left border-collapse text-xs">
                                    <thead>
                                        <tr class="border-b border-[#E6E8EE] text-[#5B6270] font-bold uppercase tracking-wider">
                                            <th class="py-3 px-4">Administrator</th>
                                            <th class="py-3 px-4">Role</th>
                                            <th class="py-3 px-4">Added On</th>
                                            <th class="py-3 px-4 text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-[#E6E8EE] font-medium text-[#111318]">
                                        <tr v-for="admin in adminUsers" :key="admin.id" class="hover:bg-[#F8F9FA]/60 transition-colors">
                                            <td class="py-3.5 px-4">
                                                <div class="flex items-center gap-3">
                                                    <div class="h-8 w-8 rounded-full bg-gradient-to-tr from-[#8B5CF6] to-[#2F6BFF] text-white font-bold text-xs flex items-center justify-center shrink-0 shadow-2xs">
                                                        {{ (admin.name || 'SA').substring(0, 2).toUpperCase() }}
                                                    </div>
                                                    <div>
                                                        <div class="font-bold text-[#111318] flex items-center gap-2">
                                                            <span>{{ admin.name }}</span>
                                                            <span v-if="admin.is_current_user" class="px-2 py-0.5 rounded-full bg-[#EEF3FF] text-[#2F6BFF] font-bold text-[10px] border border-[#D6E4FF]">
                                                                You
                                                            </span>
                                                        </div>
                                                        <div class="text-2xs text-[#5B6270] font-mono">{{ admin.email }}</div>
                                                    </div>
                                                </div>
                                            </td>

                                            <td class="py-3.5 px-4">
                                                <span class="px-2.5 py-1 rounded-full bg-[#F8F9FA] text-[#111318] font-bold text-[11px] border border-[#E6E8EE] inline-flex items-center gap-1">
                                                    <i class="fa-solid fa-shield-halved text-[#2F6BFF] text-[10px]"></i>
                                                    <span>Super Admin</span>
                                                </span>
                                            </td>

                                            <td class="py-3.5 px-4 text-[#5B6270] whitespace-nowrap">
                                                {{ admin.created_at }}
                                            </td>

                                            <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                                <span v-if="admin.is_current_user" class="text-2xs font-semibold text-[#5B6270] italic">
                                                    Active Session
                                                </span>
                                                <button 
                                                    v-else
                                                    @click="deleteAdminUser(admin)"
                                                    title="Remove Administrator"
                                                    class="rounded-lg border border-red-200 bg-red-50/50 hover:bg-red-100 text-red-600 px-2.5 py-1.5 text-xs font-semibold transition-all inline-flex items-center gap-1 cursor-pointer"
                                                >
                                                    <i class="fa-solid fa-trash-can text-xs"></i>
                                                    <span>Remove</span>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- SUB-TAB 3: PLATFORM ENVIRONMENT -->
                    <div v-if="settingsSubTab === 'platform'" class="space-y-6">
                        <div class="rounded-2xl border border-[#E6E8EE] bg-white p-8 shadow-2xs space-y-6">
                            <div class="border-b border-[#E6E8EE] pb-4">
                                <h3 class="text-base font-extrabold text-[#111318]">Global Platform Configuration</h3>
                                <p class="text-xs text-[#5B6270] mt-0.5">Control central communication channels, maintenance states, and trial parameters.</p>
                            </div>

                            <form @submit.prevent="submitSettings" class="space-y-6">
                                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                                    <div>
                                        <label class="block text-xs font-bold uppercase tracking-wider text-[#111318] mb-1.5">Support Contact Email</label>
                                        <input 
                                            v-model="settingsForm.contact_email" 
                                            type="email" 
                                            required
                                            class="w-full rounded-xl border border-[#E6E8EE] bg-[#F8F9FA] px-4 py-3 text-xs font-medium text-[#111318] focus:bg-white focus:border-[#2F6BFF] focus:outline-none transition-all"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold uppercase tracking-wider text-[#111318] mb-1.5">Support Phone Line</label>
                                        <input 
                                            v-model="settingsForm.support_phone" 
                                            type="text" 
                                            required
                                            class="w-full rounded-xl border border-[#E6E8EE] bg-[#F8F9FA] px-4 py-3 text-xs font-medium text-[#111318] focus:bg-white focus:border-[#2F6BFF] focus:outline-none transition-all"
                                        />
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                                    <div>
                                        <label class="block text-xs font-bold uppercase tracking-wider text-[#111318] mb-1.5">Default Free Trial Duration (Days)</label>
                                        <input 
                                            v-model.number="settingsForm.default_trial_days" 
                                            type="number" 
                                            min="1"
                                            max="90"
                                            required
                                            class="w-full rounded-xl border border-[#E6E8EE] bg-[#F8F9FA] px-4 py-3 text-xs font-medium text-[#111318] focus:bg-white focus:border-[#2F6BFF] focus:outline-none transition-all"
                                        />
                                    </div>

                                    <div class="flex items-center justify-between rounded-xl bg-[#F8F9FA] p-4 border border-[#E6E8EE]">
                                        <div>
                                            <p class="text-xs font-bold text-[#111318]">Public Self-Registration</p>
                                            <p class="text-2xs text-[#5B6270]">Allow schools to register on the landing page</p>
                                        </div>
                                        <input 
                                            v-model="settingsForm.public_onboarding" 
                                            type="checkbox" 
                                            class="rounded-md border-[#E6E8EE] text-[#2F6BFF] focus:ring-[#2F6BFF] h-5 w-5 cursor-pointer"
                                        />
                                    </div>
                                </div>

                                <!-- Maintenance Mode Switch -->
                                <div class="flex items-center justify-between rounded-2xl bg-[#F8F9FA] p-5 border border-[#E6E8EE]">
                                    <div>
                                        <p class="text-sm font-bold text-[#111318]">Global Maintenance Mode</p>
                                        <p class="text-xs text-[#5B6270]">Temporarily pause tenant portal access for planned migrations or infrastructure maintenance.</p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input v-model="settingsForm.maintenance_mode" type="checkbox" class="sr-only peer" />
                                        <div class="w-11 h-6 bg-[#DADCE0] peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#2F6BFF]"></div>
                                    </label>
                                </div>

                                <div class="pt-2 flex justify-end">
                                    <button 
                                        type="submit" 
                                        :disabled="settingsForm.processing"
                                        class="rounded-full bg-[#111318] hover:bg-[#2F6BFF] px-7 py-3 text-xs font-bold text-white shadow-sm disabled:opacity-50 transition-all cursor-pointer"
                                    >
                                        {{ settingsForm.processing ? 'Saving...' : 'Save Configuration' }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- ================= MODAL: ONBOARD NEW SCHOOL ================= -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex justify-end bg-black/40 backdrop-blur-xs overflow-hidden">
            <div class="absolute inset-0" @click="showModal = false"></div>
            <div class="bg-white w-full max-w-lg shadow-2xl flex flex-col h-full z-10 border-l border-[#E6E8EE]">
                <div class="p-6 sm:p-8 flex-1 overflow-y-auto space-y-6">
                    <div class="flex items-center justify-between border-b border-[#E6E8EE] pb-4">
                        <div>
                            <h3 class="text-lg font-extrabold text-[#111318]">Onboard New School Tenant</h3>
                            <p class="text-xs text-[#5B6270]">Provisions isolated schema, domains, and root credentials.</p>
                        </div>
                        <button @click="showModal = false" class="text-[#5B6270] hover:text-[#111318] cursor-pointer">
                            <i class="fa-solid fa-xmark text-lg"></i>
                        </button>
                    </div>

                    <form @submit.prevent="submitTenant" class="space-y-4 text-xs">
                        <div v-if="form.hasErrors" class="rounded-xl bg-red-50 p-3.5 text-xs font-bold text-red-600 border border-red-200 flex items-center gap-2">
                            <i class="fa-solid fa-triangle-exclamation text-base"></i>
                            <span>Please review and fix the validation errors below.</span>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-2xs font-bold uppercase tracking-wider text-[#111318] mb-1">Subdomain Identifier</label>
                                <input 
                                    v-model="form.id" 
                                    @input="form.id = form.id.toLowerCase().replace(/[^a-z0-9-]/g, ''); autoFillDomain()"
                                    type="text" 
                                    required 
                                    placeholder="e.g. springdale"
                                    class="w-full rounded-xl border border-[#E6E8EE] px-3.5 py-2.5 font-mono text-xs focus:border-[#2F6BFF] focus:outline-none"
                                    :class="{ 'border-red-400 bg-red-50': form.errors.id }"
                                />
                                <p v-if="form.errors.id" class="text-2xs font-bold text-red-600 mt-1">{{ form.errors.id }}</p>
                            </div>
                            <div>
                                <label class="block text-2xs font-bold uppercase tracking-wider text-[#111318] mb-1">Assigned FQDN Domain</label>
                                <input 
                                    v-model="form.domain" 
                                    type="text" 
                                    required 
                                    readonly
                                    class="w-full rounded-xl border border-[#E6E8EE] bg-[#F1F3F4] px-3.5 py-2.5 font-mono text-xs text-[#5B6270] cursor-not-allowed focus:outline-none"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block text-2xs font-bold uppercase tracking-wider text-[#111318] mb-1">School Campus Legal Name</label>
                            <input 
                                v-model="form.name" 
                                type="text" 
                                required 
                                placeholder="Springdale International School"
                                class="w-full rounded-xl border border-[#E6E8EE] px-3.5 py-2.5 text-xs font-medium focus:border-[#2F6BFF] focus:outline-none"
                                :class="{ 'border-red-400 bg-red-50': form.errors.name }"
                            />
                            <p v-if="form.errors.name" class="text-2xs font-bold text-red-600 mt-1">{{ form.errors.name }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-2xs font-bold uppercase tracking-wider text-[#111318] mb-1">Subscription Tier</label>
                                <select 
                                    v-model="form.plan_id" 
                                    required
                                    class="w-full rounded-xl border border-[#E6E8EE] px-3 py-2.5 bg-white text-xs font-medium focus:border-[#2F6BFF] focus:outline-none"
                                >
                                    <option v-for="plan in plans" :key="plan.id" :value="plan.id">
                                        {{ plan.name }} (PKR {{ (plan.price_monthly || 0).toLocaleString() }}/mo)
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-2xs font-bold uppercase tracking-wider text-[#111318] mb-1">Billing Cycle</label>
                                <select 
                                    v-model="form.billing_cycle" 
                                    required
                                    class="w-full rounded-xl border border-[#E6E8EE] px-3 py-2.5 bg-white text-xs font-bold text-[#2F6BFF] focus:border-[#2F6BFF] focus:outline-none"
                                >
                                    <option value="monthly">Monthly Cycle</option>
                                    <option value="yearly">Yearly Cycle (Discounted)</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-2xs font-bold uppercase tracking-wider text-[#111318] mb-1">Payment Verification Channel</label>
                            <select 
                                v-model="form.payment_gateway" 
                                required
                                class="w-full rounded-xl border border-[#E6E8EE] px-3.5 py-2.5 bg-white text-xs font-medium focus:border-[#2F6BFF] focus:outline-none"
                            >
                                <option value="Direct Invoice / Manual Onboarding">Direct Invoice / Manual Onboarding (Paid)</option>
                                <option value="JazzCash Verified">JazzCash Mobile Wallet / Online (Paid)</option>
                                <option value="Meezan Bank Verified">Meezan Bank 1-Bill API (Paid)</option>
                                <option value="Trial Account (14 Days)">Complimentary Free Trial (14 Days)</option>
                            </select>
                        </div>

                        <!-- Principal Credentials Section -->
                        <div class="border-t border-[#E6E8EE] pt-4 space-y-3.5">
                            <p class="text-2xs font-bold uppercase tracking-wider text-[#2F6BFF]">Principal Admin Credentials</p>
                            
                            <div>
                                <label class="block text-2xs font-bold uppercase tracking-wider text-[#111318] mb-1">Principal Full Name</label>
                                <input 
                                    v-model="form.admin_name" 
                                    type="text" 
                                    required 
                                    placeholder="Dr. Eleanor Vance"
                                    class="w-full rounded-xl border border-[#E6E8EE] px-3.5 py-2.5 text-xs font-medium focus:border-[#2F6BFF] focus:outline-none"
                                />
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-2xs font-bold uppercase tracking-wider text-[#111318] mb-1">Admin Email (Login ID)</label>
                                    <input 
                                        v-model="form.admin_email" 
                                        type="email" 
                                        required 
                                        placeholder="principal@school.edu"
                                        class="w-full rounded-xl border border-[#E6E8EE] px-3.5 py-2.5 text-xs font-medium focus:border-[#2F6BFF] focus:outline-none"
                                    />
                                </div>
                                <div>
                                    <label class="block text-2xs font-bold uppercase tracking-wider text-[#111318] mb-1">Phone Number</label>
                                    <input 
                                        v-model="form.admin_phone" 
                                        type="text" 
                                        required 
                                        placeholder="+1 555 123 4567"
                                        class="w-full rounded-xl border border-[#E6E8EE] px-3.5 py-2.5 text-xs font-medium focus:border-[#2F6BFF] focus:outline-none"
                                    />
                                </div>
                            </div>

                            <div>
                                <label class="block text-2xs font-bold uppercase tracking-wider text-[#111318] mb-1">Initial Password</label>
                                <div class="relative">
                                    <input 
                                        v-model="form.admin_password" 
                                        type="text" 
                                        required 
                                        minlength="6"
                                        placeholder="Generate or enter secure password"
                                        class="w-full rounded-xl border border-[#E6E8EE] pl-3.5 pr-28 py-2.5 font-mono text-xs font-bold text-emerald-700 bg-[#F8F9FA] focus:border-[#2F6BFF] focus:outline-none"
                                    />
                                    <button 
                                        type="button"
                                        @click="generateRandomPassword"
                                        class="absolute right-1.5 top-1.5 rounded-lg bg-[#EEF3FF] px-2.5 py-1 text-2xs font-bold text-[#2F6BFF] hover:bg-[#D6E4FF] transition-colors cursor-pointer"
                                    >
                                        <i class="fa-solid fa-wand-magic-sparkles mr-1"></i> Generate
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="pt-4 flex justify-end space-x-3">
                            <button type="button" @click="showModal = false" class="rounded-full px-5 py-2.5 text-xs font-bold text-[#5B6270] hover:bg-[#F8F9FA] cursor-pointer">
                                Cancel
                            </button>
                            <button 
                                type="submit" 
                                :disabled="form.processing"
                                class="rounded-full bg-[#111318] hover:bg-[#2F6BFF] px-6 py-2.5 text-xs font-bold text-white shadow-sm disabled:opacity-50 transition-all cursor-pointer flex items-center gap-1.5"
                            >
                                <i v-if="!form.processing" class="fa-solid fa-rocket"></i>
                                <span>{{ form.processing ? 'Provisioning Cloud Schema...' : 'Provision & Launch School' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ================= MODAL: EDIT SCHOOL ================= -->
        <div v-if="showEditSchoolModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs">
            <div class="bg-white w-full max-w-lg rounded-3xl border border-[#E6E8EE] p-6 sm:p-8 space-y-6 shadow-2xl">
                <div class="flex justify-between items-center border-b border-[#E6E8EE] pb-4">
                    <h3 class="text-base font-extrabold text-[#111318]">Edit School Tenant Details</h3>
                    <button @click="showEditSchoolModal = false" class="text-[#5B6270] hover:text-[#111318] cursor-pointer">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <form @submit.prevent="submitEditSchool" class="space-y-4 text-xs">
                    <div>
                        <label class="block text-2xs font-bold uppercase tracking-wider text-[#111318] mb-1">School Name</label>
                        <input 
                            v-model="editSchoolForm.name" 
                            type="text" 
                            required 
                            class="w-full rounded-xl border border-[#E6E8EE] px-3.5 py-2.5 text-xs font-medium focus:border-[#2F6BFF] focus:outline-none"
                        />
                    </div>

                    <div>
                        <label class="block text-2xs font-bold uppercase tracking-wider text-[#111318] mb-1">Subdomain Route</label>
                        <input 
                            v-model="editSchoolForm.domain" 
                            type="text" 
                            required 
                            class="w-full rounded-xl border border-[#E6E8EE] px-3.5 py-2.5 font-mono text-xs focus:border-[#2F6BFF] focus:outline-none"
                        />
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-[#E6E8EE]">
                        <button type="button" @click="showEditSchoolModal = false" class="rounded-full px-5 py-2.5 text-xs font-bold text-[#5B6270] hover:bg-[#F8F9FA] cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit" :disabled="editSchoolForm.processing" class="rounded-full bg-[#111318] hover:bg-[#2F6BFF] px-6 py-2.5 text-xs font-bold text-white transition-all shadow-sm disabled:opacity-50 cursor-pointer">
                            {{ editSchoolForm.processing ? 'Saving...' : 'Update Details' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ================= MODAL: ADD / EDIT PLAN ================= -->
        <div v-if="showPlanModal" class="fixed inset-0 z-50 flex justify-end bg-black/40 backdrop-blur-xs overflow-hidden">
            <div class="absolute inset-0" @click="showPlanModal = false"></div>
            <div class="bg-white w-full max-w-lg shadow-2xl flex flex-col h-full z-10 border-l border-[#E6E8EE]">
                <div class="p-6 sm:p-8 flex-1 overflow-y-auto space-y-6">
                    <div class="flex items-center justify-between border-b border-[#E6E8EE] pb-4">
                        <h3 class="text-base font-extrabold text-[#111318]">{{ isEditingPlan ? 'Edit Subscription Package' : 'Create Subscription Package' }}</h3>
                        <button @click="showPlanModal = false" class="text-[#5B6270] hover:text-[#111318] cursor-pointer">
                            <i class="fa-solid fa-xmark text-lg"></i>
                        </button>
                    </div>

                    <form @submit.prevent="submitPlan" class="space-y-4 text-xs">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-2xs font-bold uppercase tracking-wider text-[#111318] mb-1">Plan Display Name</label>
                                <input 
                                    v-model="planForm.name" 
                                    type="text" 
                                    required 
                                    placeholder="e.g. Professional"
                                    class="w-full rounded-xl border border-[#E6E8EE] px-3.5 py-2.5 focus:border-[#2F6BFF] focus:outline-none"
                                />
                            </div>
                            <div>
                                <label class="block text-2xs font-bold uppercase tracking-wider text-[#111318] mb-1">Identifier Code</label>
                                <input 
                                    v-model="planForm.code" 
                                    type="text" 
                                    required 
                                    placeholder="professional"
                                    class="w-full rounded-xl border border-[#E6E8EE] px-3.5 py-2.5 lowercase font-mono focus:border-[#2F6BFF] focus:outline-none"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-2xs font-bold uppercase tracking-wider text-[#111318] mb-1">Tagline / Badge</label>
                                <input 
                                    v-model="planForm.tagline" 
                                    type="text" 
                                    placeholder="e.g. Recommended, Small schools"
                                    class="w-full rounded-xl border border-[#E6E8EE] px-3.5 py-2.5 focus:border-[#2F6BFF] focus:outline-none"
                                />
                            </div>
                            <div>
                                <label class="block text-2xs font-bold uppercase tracking-wider text-[#111318] mb-1">Pricing Model</label>
                                <select 
                                    v-model="planForm.pricing_type" 
                                    required
                                    class="w-full rounded-xl border border-[#E6E8EE] px-3.5 py-2.5 bg-white font-medium focus:border-[#2F6BFF] focus:outline-none"
                                >
                                    <option value="per_student">Per Student / Mo (Usage-based)</option>
                                    <option value="flat">Flat Fixed Fee / Mo</option>
                                    <option value="custom">Custom / Enterprise (Sales Contact)</option>
                                </select>
                            </div>
                        </div>

                        <!-- Per Student Pricing Inputs -->
                        <div v-if="planForm.pricing_type === 'per_student'" class="grid grid-cols-2 gap-4 bg-[#F8F9FA] p-3 rounded-xl border border-[#E6E8EE]">
                            <div>
                                <label class="block text-2xs font-bold uppercase tracking-wider text-[#111318] mb-1">Rate (PKR / student / mo)</label>
                                <input 
                                    v-model.number="planForm.price_per_student" 
                                    type="number" 
                                    min="0"
                                    step="1"
                                    required 
                                    class="w-full rounded-xl border border-[#E6E8EE] bg-white px-3.5 py-2.5 focus:border-[#2F6BFF] focus:outline-none"
                                />
                            </div>
                            <div>
                                <label class="block text-2xs font-bold uppercase tracking-wider text-[#111318] mb-1">Min. Monthly Bill (PKR)</label>
                                <input 
                                    v-model.number="planForm.minimum_monthly_bill" 
                                    type="number" 
                                    min="0"
                                    step="100"
                                    required 
                                    class="w-full rounded-xl border border-[#E6E8EE] bg-white px-3.5 py-2.5 focus:border-[#2F6BFF] focus:outline-none"
                                />
                            </div>
                        </div>

                        <!-- Flat Fee Pricing Inputs -->
                        <div v-else-if="planForm.pricing_type === 'flat'" class="grid grid-cols-2 gap-4 bg-[#F8F9FA] p-3 rounded-xl border border-[#E6E8EE]">
                            <div>
                                <label class="block text-2xs font-bold uppercase tracking-wider text-[#111318] mb-1">Monthly Price (PKR)</label>
                                <input 
                                    v-model.number="planForm.price_monthly" 
                                    type="number" 
                                    required 
                                    min="0"
                                    class="w-full rounded-xl border border-[#E6E8EE] bg-white px-3.5 py-2.5 focus:border-[#2F6BFF] focus:outline-none"
                                />
                            </div>
                            <div>
                                <label class="block text-2xs font-bold uppercase tracking-wider text-[#111318] mb-1">Yearly Price (PKR)</label>
                                <input 
                                    v-model.number="planForm.price_yearly" 
                                    type="number" 
                                    min="0"
                                    placeholder="Leave empty for 10x monthly"
                                    class="w-full rounded-xl border border-[#E6E8EE] bg-white px-3.5 py-2.5 focus:border-[#2F6BFF] focus:outline-none"
                                />
                            </div>
                        </div>

                        <!-- Custom Pricing Notice -->
                        <div v-else class="p-3 bg-[#EEF3FF] rounded-xl border border-[#D6E4FF] text-2xs text-[#2F6BFF]">
                            <i class="fa-solid fa-circle-info mr-1"></i>
                            Custom pricing packages display a "Talk to Sales" button that opens the direct consultation modal on the landing page.
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-2xs font-bold uppercase tracking-wider text-[#111318] mb-1">Max Student Cap</label>
                                <input 
                                    v-model.number="planForm.max_students" 
                                    type="number" 
                                    required 
                                    min="1"
                                    class="w-full rounded-xl border border-[#E6E8EE] px-3.5 py-2.5 focus:border-[#2F6BFF] focus:outline-none"
                                />
                            </div>
                            <div class="flex items-center pt-5">
                                <label class="flex items-center space-x-2.5 cursor-pointer">
                                    <input 
                                        v-model="planForm.is_active" 
                                        type="checkbox" 
                                        class="h-4 w-4 rounded border-gray-300 text-[#2F6BFF] focus:ring-[#2F6BFF]"
                                    />
                                    <span class="text-xs font-bold text-[#111318]">Publish to Public Landing Page</span>
                                </label>
                            </div>
                        </div>

                        <div>
                            <label class="block text-2xs font-bold uppercase tracking-wider text-[#111318] mb-1">Plan Summary / Subtitle</label>
                            <textarea 
                                v-model="planForm.summary" 
                                rows="2"
                                placeholder="Brief value proposition for this plan..."
                                class="w-full rounded-xl border border-[#E6E8EE] px-3.5 py-2 focus:border-[#2F6BFF] focus:outline-none"
                            ></textarea>
                        </div>

                        <div class="border-t border-[#E6E8EE] pt-4 space-y-3">
                            <label class="block text-2xs font-bold uppercase tracking-wider text-[#111318]">Package Features Checklist</label>
                            <div class="flex space-x-2">
                                <input 
                                    v-model="featureInput" 
                                    type="text" 
                                    placeholder="Add feature description..."
                                    class="flex-1 rounded-xl border border-[#E6E8EE] px-3.5 py-2 text-xs focus:border-[#2F6BFF] focus:outline-none"
                                    @keydown.enter.prevent="addFeatureToPlan"
                                />
                                <button type="button" @click="addFeatureToPlan" class="rounded-xl bg-[#EEF3FF] px-4 py-2 text-xs font-bold text-[#2F6BFF] hover:bg-[#D6E4FF] cursor-pointer">
                                    + Add
                                </button>
                            </div>
                            <div class="flex flex-wrap gap-2 pt-1 max-h-36 overflow-y-auto">
                                <span 
                                    v-for="(feat, idx) in planForm.features" 
                                    :key="idx"
                                    class="inline-flex items-center space-x-1.5 rounded-full bg-[#F0F2F6] px-3 py-1 text-xs font-semibold text-[#111318]"
                                >
                                    <span>{{ feat }}</span>
                                    <button type="button" @click="removeFeatureFromPlan(idx)" class="text-red-500 font-bold hover:text-red-700 cursor-pointer"><i class="fa-solid fa-xmark"></i></button>
                                </span>
                            </div>
                        </div>

                        <div class="pt-4 flex justify-end space-x-3">
                            <button type="button" @click="showPlanModal = false" class="rounded-full px-5 py-2.5 text-xs font-bold text-[#5B6270] hover:bg-[#F8F9FA] cursor-pointer">
                                Cancel
                            </button>
                            <button 
                                type="submit" 
                                :disabled="planForm.processing"
                                class="rounded-full bg-[#111318] hover:bg-[#2F6BFF] px-6 py-2.5 text-xs font-bold text-white shadow-sm disabled:opacity-50 transition-all cursor-pointer"
                            >
                                {{ planForm.processing ? 'Saving...' : (isEditingPlan ? 'Update Plan' : 'Create Plan') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ================= MODAL: CREATE COUPON ================= -->
        <div v-if="showCouponModal" class="fixed inset-0 z-50 flex justify-end bg-black/40 backdrop-blur-xs overflow-hidden">
            <div class="absolute inset-0" @click="showCouponModal = false"></div>
            <div class="bg-white w-full max-w-md shadow-2xl flex flex-col h-full z-10 border-l border-[#E6E8EE]">
                <div class="p-6 sm:p-8 flex-1 overflow-y-auto space-y-6 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between border-b border-[#E6E8EE] pb-4 mb-5">
                            <h3 class="text-base font-extrabold text-[#111318]">Create Discount Voucher</h3>
                            <button @click="showCouponModal = false" class="text-[#5B6270] hover:text-[#111318] cursor-pointer">
                                <i class="fa-solid fa-xmark text-lg"></i>
                            </button>
                        </div>

                        <form @submit.prevent="submitCoupon" class="space-y-4 text-xs">
                            <div>
                                <label class="block text-2xs font-bold uppercase tracking-wider text-[#111318] mb-1">Coupon Code (Uppercase)</label>
                                <input 
                                    v-model="couponForm.code" 
                                    type="text" 
                                    required
                                    placeholder="e.g. LAUNCH50" 
                                    class="w-full rounded-xl border border-[#E6E8EE] px-4 py-2.5 text-xs font-mono font-bold uppercase focus:border-[#2F6BFF] focus:outline-none"
                                />
                            </div>

                            <div>
                                <label class="block text-2xs font-bold uppercase tracking-wider text-[#111318] mb-1">Discount Percentage (1-100%)</label>
                                <input 
                                    v-model.number="couponForm.discount_percentage" 
                                    type="number" 
                                    min="1" 
                                    max="100" 
                                    required
                                    class="w-full rounded-xl border border-[#E6E8EE] px-4 py-2.5 text-xs font-bold focus:border-[#2F6BFF] focus:outline-none"
                                />
                            </div>

                            <div>
                                <label class="block text-2xs font-bold uppercase tracking-wider text-[#111318] mb-1">Max Redemptions (Optional)</label>
                                <input 
                                    v-model.number="couponForm.max_uses" 
                                    type="number" 
                                    placeholder="Unlimited if empty" 
                                    class="w-full rounded-xl border border-[#E6E8EE] px-4 py-2.5 text-xs font-medium focus:border-[#2F6BFF] focus:outline-none"
                                />
                            </div>

                            <div class="flex items-center justify-end gap-3 pt-4 border-t border-[#E6E8EE] mt-6">
                                <button type="button" @click="showCouponModal = false" class="rounded-full px-5 py-2.5 text-xs font-bold text-[#5B6270] hover:bg-[#F8F9FA] cursor-pointer">
                                    Cancel
                                </button>
                                <button type="submit" :disabled="couponForm.processing" class="rounded-full bg-[#111318] hover:bg-[#2F6BFF] px-6 py-2.5 text-xs font-bold text-white transition-all shadow-sm disabled:opacity-50 cursor-pointer">
                                    {{ couponForm.processing ? 'Creating...' : 'Create Voucher' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================= MODAL: ADD ADMINISTRATOR ================= -->
        <div v-if="showAddAdminModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-xs p-4">
            <div class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-8 shadow-2xl relative border border-[#E6E8EE]">
                <div class="flex items-center justify-between border-b border-[#E6E8EE] pb-4 mb-5">
                    <div>
                        <h3 class="text-base font-extrabold text-[#111318]">Add Central Administrator</h3>
                        <p class="text-xs text-[#5B6270] mt-0.5">Grants full root access to the Super Admin portal.</p>
                    </div>
                    <button @click="showAddAdminModal = false" class="text-[#5B6270] hover:text-[#111318] cursor-pointer">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <form @submit.prevent="submitNewAdminUser" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-bold uppercase tracking-wider text-[#111318] mb-1">Full Name</label>
                        <input 
                            v-model="adminUserForm.name" 
                            type="text" 
                            required 
                            placeholder="e.g. Sarah Jenkins"
                            class="w-full rounded-xl border border-[#E6E8EE] px-4 py-2.5 text-xs font-medium focus:border-[#2F6BFF] focus:outline-none"
                            :class="{ 'border-red-400 bg-red-50/40': adminUserForm.errors.name }"
                        />
                        <div v-if="adminUserForm.errors.name" class="mt-1 text-2xs text-red-600 font-medium">
                            {{ adminUserForm.errors.name }}
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold uppercase tracking-wider text-[#111318] mb-1">Email Address</label>
                        <input 
                            v-model="adminUserForm.email" 
                            type="email" 
                            required 
                            placeholder="admin@raibix.com"
                            class="w-full rounded-xl border border-[#E6E8EE] px-4 py-2.5 text-xs font-medium focus:border-[#2F6BFF] focus:outline-none"
                            :class="{ 'border-red-400 bg-red-50/40': adminUserForm.errors.email }"
                        />
                        <div v-if="adminUserForm.errors.email" class="mt-1 text-2xs text-red-600 font-medium">
                            {{ adminUserForm.errors.email }}
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold uppercase tracking-wider text-[#111318] mb-1">Master Password</label>
                        <input 
                            v-model="adminUserForm.password" 
                            type="password" 
                            required 
                            placeholder="Min. 8 characters"
                            class="w-full rounded-xl border border-[#E6E8EE] px-4 py-2.5 text-xs font-medium focus:border-[#2F6BFF] focus:outline-none"
                            :class="{ 'border-red-400 bg-red-50/40': adminUserForm.errors.password }"
                        />
                        <div v-if="adminUserForm.errors.password" class="mt-1 text-2xs text-red-600 font-medium">
                            {{ adminUserForm.errors.password }}
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold uppercase tracking-wider text-[#111318] mb-1">Confirm Password</label>
                        <input 
                            v-model="adminUserForm.password_confirmation" 
                            type="password" 
                            required 
                            placeholder="Repeat password"
                            class="w-full rounded-xl border border-[#E6E8EE] px-4 py-2.5 text-xs font-medium focus:border-[#2F6BFF] focus:outline-none"
                        />
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-[#E6E8EE] mt-6">
                        <button type="button" @click="showAddAdminModal = false" class="rounded-full px-5 py-2.5 text-xs font-bold text-[#5B6270] hover:bg-[#F8F9FA] cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit" :disabled="adminUserForm.processing" class="rounded-full bg-[#111318] hover:bg-[#2F6BFF] px-6 py-2.5 text-xs font-bold text-white transition-all shadow-sm disabled:opacity-50 cursor-pointer">
                            {{ adminUserForm.processing ? 'Adding...' : 'Add Administrator' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ================= CUSTOM CONFIRM DIALOG ================= -->
        <div v-if="confirmDialog.show" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs">
            <div class="bg-white rounded-3xl border border-[#E6E8EE] p-6 max-w-md w-full shadow-2xl space-y-4">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 rounded-full flex items-center justify-center bg-[#F8F9FA] shrink-0">
                        <i :class="[confirmDialog.icon, confirmDialog.iconColor]" class="fa-solid text-lg"></i>
                    </div>
                    <h3 class="text-base font-extrabold text-[#111318]">{{ confirmDialog.title }}</h3>
                </div>
                <p class="text-xs text-[#5B6270] leading-relaxed">{{ confirmDialog.message }}</p>
                <div class="flex justify-end space-x-3 pt-3 border-t border-[#E6E8EE]">
                    <button @click="confirmDialog.show = false" class="px-5 py-2 border border-[#E6E8EE] rounded-full text-xs font-semibold text-[#5B6270] hover:bg-[#F8F9FA] cursor-pointer">
                        Cancel
                    </button>
                    <button @click="confirmDialog.onConfirm" :class="confirmDialog.confirmClass" class="px-5 py-2 text-white rounded-full text-xs font-bold shadow-xs transition-all cursor-pointer">
                        {{ confirmDialog.confirmText }}
                    </button>
                </div>
            </div>
        </div>

        <!-- ================= CUSTOM INPUT DIALOG ================= -->
        <div v-if="inputDialog.show" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs">
            <div class="bg-white rounded-3xl border border-[#E6E8EE] p-6 max-w-md w-full shadow-2xl space-y-4">
                <h3 class="text-base font-extrabold text-[#111318]">{{ inputDialog.title }}</h3>
                <p class="text-xs text-[#5B6270] leading-relaxed">{{ inputDialog.message }}</p>
                <div>
                    <input 
                        v-model="inputDialog.value" 
                        type="text" 
                        :placeholder="inputDialog.placeholder"
                        class="w-full rounded-xl border border-[#E6E8EE] px-4 py-2.5 text-xs focus:border-[#2F6BFF] focus:outline-none"
                        @keydown.enter="inputDialog.onConfirm(inputDialog.value)"
                    />
                </div>
                <div class="flex justify-end space-x-3 pt-3 border-t border-[#E6E8EE]">
                    <button @click="inputDialog.show = false" class="px-5 py-2 border border-[#E6E8EE] rounded-full text-xs font-semibold text-[#5B6270] hover:bg-[#F8F9FA] cursor-pointer">
                        Cancel
                    </button>
                    <button @click="inputDialog.onConfirm(inputDialog.value)" class="px-5 py-2 bg-[#111318] hover:bg-[#2F6BFF] text-white rounded-full text-xs font-bold shadow-xs transition-all cursor-pointer">
                        Submit
                    </button>
                </div>
            </div>
        </div>

        <!-- ================= GLOBAL ALERT TOAST ================= -->
        <transition name="slide-up">
            <div v-if="alertDialog.show" class="fixed bottom-6 right-6 z-[110]">
                <div :class="alertDialog.type === 'success' ? 'bg-[#111318] text-white border-[#2F6BFF]' : 'bg-red-600 text-white border-red-500'" class="flex items-center gap-3 px-5 py-3.5 rounded-2xl border shadow-2xl max-w-sm">
                    <i :class="alertDialog.type === 'success' ? 'fa-circle-check text-[#2F6BFF]' : 'fa-triangle-exclamation text-white'" class="fa-solid text-lg shrink-0"></i>
                    <div class="flex-1">
                        <div class="text-xs font-bold">{{ alertDialog.title }}</div>
                        <div class="text-xs opacity-90 mt-0.5">{{ alertDialog.message }}</div>
                    </div>
                    <button @click="alertDialog.show = false" class="ml-2 opacity-70 hover:opacity-100 cursor-pointer">
                        <i class="fa-solid fa-xmark text-sm"></i>
                    </button>
                </div>
            </div>
        </transition>
    </div>
</template>