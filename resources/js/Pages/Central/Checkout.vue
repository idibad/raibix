<script setup>
import { ref, computed, onMounted } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
    plan: {
        type: Object,
        default: () => ({})
    },
    allPlans: {
        type: Array,
        default: () => []
    },
});

// Fallback plans matching the landing page
const defaultPlans = [
    {
        id: 1,
        code: 'starter',
        name: 'Starter',
        tagline: 'Small schools',
        pricing_type: 'per_student',
        price_per_student: 20,
        minimum_monthly_bill: 2000,
        price_monthly: 2000,
        price_yearly: 24000,
        features: ['Student and staff records', 'Attendance management', 'Classes & timetables', 'Exams & report cards', 'Homework & announcements', 'Portals']
    },
    {
        id: 2,
        code: 'professional',
        name: 'Professional',
        tagline: 'Recommended',
        featured: true,
        pricing_type: 'per_student',
        price_per_student: 35,
        minimum_monthly_bill: 3500,
        price_monthly: 3500,
        price_yearly: 42000,
        features: ['Everything in Starter', 'Fee management & records', 'Parent–teacher communication', 'Assignments & progress', 'Reports & analytics', 'Priority support']
    },
    {
        id: 3,
        code: 'premium',
        name: 'Premium',
        tagline: 'Advanced',
        pricing_type: 'per_student',
        price_per_student: 55,
        minimum_monthly_bill: 5500,
        price_monthly: 5500,
        price_yearly: 66000,
        features: ['Everything in Professional', 'Advanced analytics', 'Configurable workflows', 'Advanced exam tools', 'Data import assistance', 'Enhanced support']
    }
];

const availablePlans = computed(() => {
    return (props.allPlans && props.allPlans.length > 0) ? props.allPlans : defaultPlans;
});

// Selected Plan, Estimated Students & Billing Cycle
const billingCycle = ref('monthly');
const estimatedStudents = ref(100);

const findInitialPlan = () => {
    if (props.plan?.id) return props.plan;
    if (props.allPlans?.length > 0) {
        return props.allPlans.find(p => p.code === 'professional') || props.allPlans[0];
    }
    return defaultPlans[1];
};

const selectedPlan = ref(findInitialPlan());

const switchPlan = (plan) => {
    selectedPlan.value = plan;
    form.plan_id = plan.id;
};

// Current dynamic price based on per-student pricing & minimum monthly bill
const monthlyCalculatedBase = computed(() => {
    if (!selectedPlan.value) return 2000;
    if (selectedPlan.value.pricing_type === 'custom') return 0;
    if (selectedPlan.value.pricing_type === 'flat') {
        return Number(selectedPlan.value.price_monthly || 0);
    }
    const rate = Number(selectedPlan.value.price_per_student || 20);
    const minBill = Number(selectedPlan.value.minimum_monthly_bill || 2000);
    const count = Math.max(1, Number(estimatedStudents.value || 100));
    return Math.max(minBill, count * rate);
});

const isMinimumFloorApplied = computed(() => {
    if (!selectedPlan.value || selectedPlan.value.pricing_type !== 'per_student') return false;
    const rate = Number(selectedPlan.value.price_per_student || 20);
    const minBill = Number(selectedPlan.value.minimum_monthly_bill || 2000);
    const count = Number(estimatedStudents.value || 0);
    return (count * rate) < minBill;
});

const currentPrice = computed(() => {
    if (!selectedPlan.value) return 0;
    if (selectedPlan.value.pricing_type === 'custom') return 0;
    if (billingCycle.value === 'yearly') {
        return Math.round(monthlyCalculatedBase.value * 12 * 0.8);
    }
    return Math.round(monthlyCalculatedBase.value);
});

const annualSavings = computed(() => {
    if (!selectedPlan.value || selectedPlan.value.pricing_type === 'custom') return 0;
    const monthlyTotal = monthlyCalculatedBase.value * 12;
    const yearlyTotal = Math.round(monthlyTotal * 0.8);
    return Math.max(0, monthlyTotal - yearlyTotal);
});

// Domain Suffix helper
const getDomainSuffix = () => {
    const hostname = window.location.hostname;
    const ipPattern = /^(?:[0-9]{1,3}\.){3}[0-9]{1,3}$/;
    if (ipPattern.test(hostname)) return `${hostname}.nip.io`;
    if (hostname.includes('localhost') || hostname === '127.0.0.1') return 'raibix.cloud';
    return hostname;
};

// Subdomain live availability
const isCheckingSubdomain = ref(false);
const subdomainStatus = ref(null);
let checkTimeout = null;

const urlParams = new URLSearchParams(window.location.search);
const initialDomainSlug = urlParams.get('domain') || '';
const refCode = urlParams.get('ref') || '';
const showPassword = ref(false);
const showCouponInput = ref(false);

// Form
const form = useForm({
    id: initialDomainSlug ? initialDomainSlug.toLowerCase().replace(/[^a-z0-9-]/g, '') : '',
    name: '',
    domain: '',
    admin_name: '',
    admin_email: '',
    admin_phone: '',
    admin_password: '',
    plan_id: selectedPlan.value?.id || 2,
    estimated_students: estimatedStudents.value,
    billing_cycle: 'monthly',
    payment_gateway: 'manual',
    payment_receipt: null,
    mobile_number: '',
    account_number: '',
    coupon_code: '',
    referral_code: refCode,
});

if (form.id) {
    form.domain = `${form.id}.${getDomainSuffix()}`;
}

// Receipt File Handling
const receiptFileName = ref('');
const receiptFileSize = ref('');
const receiptInputRef = ref(null);
const receiptUploadError = ref('');

const handleReceiptUpload = (e) => {
    receiptUploadError.value = '';
    const file = e.target.files?.[0];
    if (!file) return;

    if (!file.type.match(/^image\/(png|jpeg|jpg|webp)$/i)) {
        receiptUploadError.value = 'Please select a valid image (PNG, JPG, WebP).';
        return;
    }

    if (file.size > 4 * 1024 * 1024) {
        receiptUploadError.value = 'Receipt image must be under 4MB.';
        return;
    }

    form.payment_receipt = file;
    receiptFileName.value = file.name;
    receiptFileSize.value = (file.size / (1024 * 1024)).toFixed(2) + ' MB';
};

const removeReceipt = () => {
    form.payment_receipt = null;
    receiptFileName.value = '';
    receiptFileSize.value = '';
    receiptUploadError.value = '';
    if (receiptInputRef.value) receiptInputRef.value.value = '';
};

// Clipboard
const copiedField = ref(null);
const copyToClipboard = (text, field) => {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text);
        copiedField.value = field;
        setTimeout(() => { copiedField.value = null; }, 2000);
    }
};

// Subdomain live debounce check
const handleIdInput = () => {
    form.id = form.id.toLowerCase().replace(/[^a-z0-9-]/g, '');
    form.domain = form.id ? `${form.id}.${getDomainSuffix()}` : '';
    subdomainStatus.value = null;

    clearTimeout(checkTimeout);
    if (form.id.length < 2) return;

    isCheckingSubdomain.value = true;
    checkTimeout = setTimeout(async () => {
        try {
            const res = await axios.get('/api/check-subdomain', { params: { q: form.id } });
            const matches = res.data.results || [];
            const isTaken = matches.some(item => {
                const itemDomain = (item.domain || '').toLowerCase();
                return itemDomain.startsWith(form.id + '.') || itemDomain === form.domain;
            });
            subdomainStatus.value = isTaken ? 'taken' : 'available';
        } catch {
            subdomainStatus.value = null;
        } finally {
            isCheckingSubdomain.value = false;
        }
    }, 250);
};

const handleNameInput = () => {
    if (!form.id) {
        const slug = form.name.toLowerCase().replace(/[^a-z0-9]/g, '').substring(0, 18);
        if (slug) {
            form.id = slug;
            handleIdInput();
        }
    }
};

const submitCheckout = () => {
    form.billing_cycle = billingCycle.value;
    if (selectedPlan.value) form.plan_id = selectedPlan.value.id;
    if (form.id && !form.domain) form.domain = `${form.id}.${getDomainSuffix()}`;

    form.post('/checkout', {
        preserveScroll: true,
        onError: () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    });
};

onMounted(() => {
    if (form.id) handleIdInput();
});
</script>

<template>
    <div class="min-h-screen bg-white text-[#111318] font-['Poppins'] antialiased selection:bg-[#8B5CF6]/20 selection:text-[#2F6BFF] pb-20">

        <!-- ===================================================================
             1. MINIMAL HEADER
             =================================================================== -->
        <header class="sticky top-0 z-50 w-full bg-white/95 backdrop-blur-md border-b border-[#E7E9EE]">
            <div class="mx-auto flex h-16 max-w-[1040px] items-center justify-between px-6">
                <Link href="/" class="flex items-center gap-2 group" aria-label="Raibix Home">
                    <img 
                        src="/images/raibix.png" 
                        alt="Raibix" 
                        class="h-6 w-auto object-contain transition-transform duration-200 group-hover:scale-102"
                    />
                </Link>

                <div class="flex items-center gap-5 text-xs">
                    <Link 
                        href="/#pricing" 
                        class="font-semibold text-[#5B6270] hover:text-[#111318] transition-colors"
                    >
                        &larr; Back to Pricing
                    </Link>
                </div>
            </div>
        </header>

        <!-- ===================================================================
             2. CLEAN TWO-COLUMN CHECKOUT
             Left: Compact Order Summary
             Right: Streamlined Onboarding Form
             =================================================================== -->
        <main class="mx-auto max-w-[1040px] px-6 py-8 sm:py-12">
            
            <!-- Global Form Errors Alert -->
            <div v-if="Object.keys(form.errors).length > 0" class="mb-8 rounded-2xl border border-red-200 bg-red-50 p-4 text-xs text-red-700">
                <div class="font-bold mb-1">Please fix the following:</div>
                <ul class="list-disc list-inside space-y-0.5">
                    <li v-for="(err, key) in form.errors" :key="key">{{ err }}</li>
                </ul>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-start">
                
                <!-- ===============================================================
                     ORDER SUMMARY (STICKY COMPACT SIDEBAR)
                     =============================================================== -->
                <aside class="lg:col-span-5 order-2 lg:order-1 lg:sticky lg:top-24 space-y-5">
                    
                    <div class="rounded-2xl border border-[#E7E9EE] bg-[#FBFBFC] p-6 space-y-5">
                        
                        <!-- Header & Selected Plan -->
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="raibix-dot h-2 w-2"></span>
                                <span class="font-extrabold text-sm text-[#111318]">{{ selectedPlan?.name }}</span>
                            </div>
                            <span class="text-[11px] font-bold text-[#2F6BFF]">
                                PKR {{ selectedPlan?.price_per_student || 20 }}/student/mo
                            </span>
                        </div>

                        <!-- Tier Switcher (Clean compact buttons) -->
                        <div class="grid grid-cols-3 gap-1.5 rounded-xl border border-[#E7E9EE] bg-white p-1 text-xs">
                            <button 
                                v-for="p in availablePlans.filter(p => p.code !== 'enterprise')" 
                                :key="p.id" 
                                type="button" 
                                @click="switchPlan(p)"
                                :class="[
                                    'py-1.5 rounded-lg text-center font-bold transition-all cursor-pointer text-[11px]',
                                    selectedPlan?.id === p.id 
                                        ? 'bg-[#111318] text-white shadow-2xs' 
                                        : 'text-[#5B6270] hover:text-[#111318]'
                                ]"
                            >
                                {{ p.name }}
                            </button>
                        </div>

                        <!-- Estimated Students Input & Rate Calculation -->
                        <div class="space-y-2 pt-2 border-t border-[#E7E9EE]">
                            <div class="flex items-center justify-between">
                                <label class="text-xs font-bold text-[#111318]">Estimated Students</label>
                                <span class="text-[11px] text-[#5B6270]">
                                    Min. PKR {{ (selectedPlan?.minimum_monthly_bill || 2000).toLocaleString() }}/mo
                                </span>
                            </div>
                            <div class="flex items-center gap-2">
                                <input 
                                    v-model.number="estimatedStudents" 
                                    @input="form.estimated_students = estimatedStudents"
                                    type="number" 
                                    min="10" 
                                    max="25000" 
                                    step="10"
                                    class="w-full rounded-xl border border-[#E7E9EE] bg-white px-3.5 py-2 text-xs font-bold text-[#111318] focus:border-[#2F6BFF] focus:outline-none transition-all"
                                />
                                <span class="text-xs font-medium text-[#5B6270] shrink-0">Students</span>
                            </div>
                            <p v-if="isMinimumFloorApplied" class="text-[11px] text-[#5B6270] leading-snug">
                                Minimum monthly bill guarantee of PKR {{ (selectedPlan?.minimum_monthly_bill || 2000).toLocaleString() }}/month applied.
                            </p>
                        </div>

                        <!-- Billing Frequency Toggle (Monthly vs Annual) -->
                        <div class="flex items-center rounded-xl border border-[#E7E9EE] bg-white p-1 text-xs">
                            <button 
                                type="button" 
                                @click="billingCycle = 'monthly'"
                                :class="[
                                    'flex-1 py-1.5 rounded-lg font-bold transition-all text-center cursor-pointer',
                                    billingCycle === 'monthly' ? 'bg-[#111318] text-white shadow-2xs' : 'text-[#5B6270] hover:text-[#111318]'
                                ]"
                            >
                                Monthly
                            </button>
                            <button 
                                type="button" 
                                @click="billingCycle = 'yearly'"
                                :class="[
                                    'flex-1 py-1.5 rounded-lg font-bold transition-all text-center cursor-pointer flex items-center justify-center gap-1',
                                    billingCycle === 'yearly' ? 'bg-[#111318] text-white shadow-2xs' : 'text-[#5B6270] hover:text-[#111318]'
                                ]"
                            >
                                <span>Annual</span>
                                <span class="rounded-full bg-[#15803D] text-white text-[9px] px-1.5 py-0.2 font-extrabold">-20%</span>
                            </button>
                        </div>

                        <!-- Minimal Price Breakdown -->
                        <div class="space-y-2 pt-3 border-t border-[#E7E9EE] text-xs">
                            <div class="flex justify-between text-[#5B6270]">
                                <span>Rate / Student</span>
                                <span class="text-[#111318] font-medium">PKR {{ selectedPlan?.price_per_student || 20 }}/mo</span>
                            </div>
                            <div class="flex justify-between text-[#5B6270]">
                                <span>Student Count</span>
                                <span class="text-[#111318] font-medium">{{ estimatedStudents || 100 }}</span>
                            </div>
                            <div class="flex justify-between text-[#5B6270]">
                                <span>Monthly Base</span>
                                <span class="text-[#111318] font-medium">PKR {{ monthlyCalculatedBase.toLocaleString() }}</span>
                            </div>
                            <div class="flex justify-between text-[#5B6270]">
                                <span>Subscription Period</span>
                                <span class="text-[#111318] font-medium">{{ billingCycle === 'yearly' ? '12 Months' : '1 Month' }}</span>
                            </div>
                            <div v-if="billingCycle === 'yearly' && annualSavings > 0" class="flex justify-between text-[#15803D] font-semibold">
                                <span>Annual 20% Discount</span>
                                <span>- PKR {{ annualSavings.toLocaleString() }}</span>
                            </div>
                            <div class="flex justify-between text-[#5B6270]">
                                <span>Database Container Setup</span>
                                <span class="text-[#10B981] font-medium">Included</span>
                            </div>

                            <!-- Total -->
                            <div class="flex items-baseline justify-between pt-3 border-t border-[#E7E9EE]">
                                <span class="text-xs font-bold text-[#111318] uppercase tracking-wider">Total Due Today</span>
                                <span class="text-2xl font-black text-[#111318]">
                                    PKR {{ currentPrice.toLocaleString() }}
                                </span>
                            </div>
                        </div>

                        <!-- 3 Direct Bullets -->
                        <div class="pt-3 border-t border-[#E7E9EE] space-y-1.5 text-[11px] text-[#5B6270]">
                            <div class="flex items-center gap-2">
                                <span class="raibix-dot h-1.5 w-1.5"></span>
                                <span>Dedicated campus database</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="raibix-dot h-1.5 w-1.5"></span>
                                <span>1-Bill banking integration &amp; daily vouchers</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="raibix-dot h-1.5 w-1.5"></span>
                                <span>Automated SSL &amp; custom subdomain</span>
                            </div>
                        </div>

                    </div>

                </aside>

                <!-- ===============================================================
                     MAIN ONBOARDING & PAYMENT FORM
                     Focused, uncluttered, effortlessly fillable in 30 seconds
                     =============================================================== -->
                <section class="lg:col-span-7 order-1 lg:order-2">
                    
                    <form @submit.prevent="submitCheckout" class="space-y-8">
                        
                        <!-- 1. CAMPUS IDENTITY -->
                        <div class="space-y-4">
                            <div>
                                <h2 class="text-base font-extrabold text-[#111318]">
                                    1. Campus Information
                                </h2>
                                <p class="text-xs text-[#5B6270]">
                                    Institutional name and campus subdomain.
                                </p>
                            </div>

                            <div class="space-y-3.5">
                                <!-- School Name -->
                                <div>
                                    <label class="block text-xs font-semibold text-[#111318] mb-1">
                                        Campus / School Name <span class="text-red-500">*</span>
                                    </label>
                                    <input 
                                        v-model="form.name" 
                                        @input="handleNameInput"
                                        type="text" 
                                        placeholder="Campus / School Name"
                                        required
                                        class="w-full rounded-xl border border-[#E7E9EE] bg-white px-3.5 py-2.5 text-xs sm:text-sm text-[#111318] placeholder-[#9CA3AF] focus:border-[#2F6BFF] focus:ring-2 focus:ring-[#2F6BFF]/10 focus:outline-none transition-all"
                                    />
                                    <div v-if="form.errors.name" class="mt-1 text-xs text-red-600 font-semibold">{{ form.errors.name }}</div>
                                </div>

                                <!-- Subdomain Handle with Clean Integrated Prefix/Suffix -->
                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <label class="block text-xs font-semibold text-[#111318]">
                                            Dedicated Subdomain <span class="text-red-500">*</span>
                                        </label>
                                        <span v-if="isCheckingSubdomain" class="text-[11px] text-[#9CA3AF]">Checking...</span>
                                        <span v-else-if="subdomainStatus === 'available'" class="text-[11px] text-[#15803D] font-bold flex items-center gap-1">
                                            <span class="h-1.5 w-1.5 rounded-full bg-[#15803D]"></span> Available
                                        </span>
                                        <span v-else-if="subdomainStatus === 'taken'" class="text-[11px] text-red-600 font-bold flex items-center gap-1">
                                            <span class="h-1.5 w-1.5 rounded-full bg-red-600"></span> Taken
                                        </span>
                                    </div>

                                    <div class="flex items-center rounded-xl border border-[#E7E9EE] bg-white focus-within:border-[#2F6BFF] focus-within:ring-2 focus-within:ring-[#2F6BFF]/10 overflow-hidden transition-all">
                                        <span class="px-3 text-xs text-[#9CA3AF] select-none bg-[#FBFBFC] border-r border-[#E7E9EE] py-2.5">https://</span>
                                        <input 
                                            v-model="form.id" 
                                            @input="handleIdInput" 
                                            type="text" 
                                            placeholder="school-slug" 
                                            required 
                                            class="flex-1 px-3 py-2.5 text-xs sm:text-sm text-[#111318] font-mono focus:outline-none"
                                        />
                                        <span class="px-3 text-xs text-[#9CA3AF] select-none bg-[#FBFBFC] border-l border-[#E7E9EE] py-2.5">.{{ getDomainSuffix() }}</span>
                                    </div>
                                    <div v-if="form.errors.id" class="mt-1 text-xs text-red-600 font-semibold">{{ form.errors.id }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-[#E7E9EE]"></div>

                        <!-- 2. ADMINISTRATOR ACCOUNT -->
                        <div class="space-y-4">
                            <div>
                                <h2 class="text-base font-extrabold text-[#111318]">
                                    2. Administrator Account
                                </h2>
                                <p class="text-xs text-[#5B6270]">
                                    Master login credentials for the school principal or registrar.
                                </p>
                            </div>

                            <div class="space-y-3.5">
                                <!-- Admin Name -->
                                <div>
                                    <label class="block text-xs font-semibold text-[#111318] mb-1">
                                        Principal / Administrator Full Name <span class="text-red-500">*</span>
                                    </label>
                                    <input 
                                        v-model="form.admin_name" 
                                        type="text" 
                                        placeholder="Administrator Full Name"
                                        required
                                        class="w-full rounded-xl border border-[#E7E9EE] bg-white px-3.5 py-2.5 text-xs sm:text-sm text-[#111318] placeholder-[#9CA3AF] focus:border-[#2F6BFF] focus:ring-2 focus:ring-[#2F6BFF]/10 focus:outline-none transition-all"
                                    />
                                    <div v-if="form.errors.admin_name" class="mt-1 text-xs text-red-600 font-semibold">{{ form.errors.admin_name }}</div>
                                </div>

                                <!-- Email & Phone -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                                    <div>
                                        <label class="block text-xs font-semibold text-[#111318] mb-1">
                                            Work Email Address <span class="text-red-500">*</span>
                                        </label>
                                        <input 
                                            v-model="form.admin_email" 
                                            type="email" 
                                            placeholder="Work Email Address"
                                            required
                                            class="w-full rounded-xl border border-[#E7E9EE] bg-white px-3.5 py-2.5 text-xs sm:text-sm text-[#111318] placeholder-[#9CA3AF] focus:border-[#2F6BFF] focus:ring-2 focus:ring-[#2F6BFF]/10 focus:outline-none transition-all"
                                        />
                                        <div v-if="form.errors.admin_email" class="mt-1 text-xs text-red-600 font-semibold">{{ form.errors.admin_email }}</div>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold text-[#111318] mb-1">
                                            Mobile / WhatsApp Number <span class="text-red-500">*</span>
                                        </label>
                                        <input 
                                            v-model="form.admin_phone" 
                                            type="text" 
                                            placeholder="Mobile / WhatsApp Number"
                                            required
                                            class="w-full rounded-xl border border-[#E7E9EE] bg-white px-3.5 py-2.5 text-xs sm:text-sm text-[#111318] placeholder-[#9CA3AF] focus:border-[#2F6BFF] focus:ring-2 focus:ring-[#2F6BFF]/10 focus:outline-none transition-all"
                                        />
                                        <div v-if="form.errors.admin_phone" class="mt-1 text-xs text-red-600 font-semibold">{{ form.errors.admin_phone }}</div>
                                    </div>
                                </div>

                                <!-- Password (Zero default value) -->
                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <label class="block text-xs font-semibold text-[#111318]">
                                            Master Password <span class="text-red-500">*</span>
                                        </label>
                                        <span class="text-[11px] text-[#9CA3AF]">Min. 6 characters</span>
                                    </div>
                                    <div class="relative">
                                        <input 
                                            v-model="form.admin_password" 
                                            :type="showPassword ? 'text' : 'password'"
                                            placeholder="Master Password"
                                            required
                                            minlength="6"
                                            autocomplete="new-password"
                                            class="w-full rounded-xl border border-[#E7E9EE] bg-white px-3.5 py-2.5 pr-14 font-mono text-xs sm:text-sm text-[#111318] placeholder-[#9CA3AF] focus:border-[#2F6BFF] focus:ring-2 focus:ring-[#2F6BFF]/10 focus:outline-none transition-all"
                                        />
                                        <button 
                                            type="button" 
                                            @click="showPassword = !showPassword"
                                            class="absolute right-3.5 top-1/2 -translate-y-1/2 text-xs font-bold text-[#5B6270] hover:text-[#111318] cursor-pointer select-none"
                                            tabindex="-1"
                                        >
                                            {{ showPassword ? 'Hide' : 'Show' }}
                                        </button>
                                    </div>
                                    <div v-if="form.errors.admin_password" class="mt-1 text-xs text-red-600 font-semibold">{{ form.errors.admin_password }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-[#E7E9EE]"></div>

                        <!-- 3. PAYMENT & VERIFICATION -->
                        <div class="space-y-4">
                            <div>
                                <h2 class="text-base font-extrabold text-[#111318]">
                                    3. Bank Transfer &amp; Receipt
                                </h2>
                                <p class="text-xs text-[#5B6270]">
                                    Transfer the exact total to our Meezan Bank account and attach your receipt.
                                </p>
                            </div>

                            <!-- Meezan Bank Compact Card -->
                            <div class="rounded-2xl border border-[#E7E9EE] bg-[#FBFBFC] p-4 sm:p-5 space-y-3.5">
                                <div class="flex items-center justify-between pb-3 border-b border-[#E7E9EE]">
                                    <div class="flex items-center gap-2">
                                        <span class="raibix-dot h-2 w-2"></span>
                                        <span class="font-extrabold text-xs text-[#111318]">Meezan Bank Limited</span>
                                    </div>
                                    <span class="font-extrabold text-xs text-[#2F6BFF]">
                                        PKR {{ currentPrice.toLocaleString() }}
                                    </span>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                                    <div>
                                        <span class="block text-[10px] font-bold uppercase text-[#9CA3AF]">Account Title</span>
                                        <span class="font-bold text-[#111318] text-xs">Raibix Technologies</span>
                                    </div>
                                    <div>
                                        <span class="block text-[10px] font-bold uppercase text-[#9CA3AF]">Account Number</span>
                                        <div class="flex items-center gap-2">
                                            <span class="font-mono font-bold text-[#111318]">1201-0982-12001</span>
                                            <button type="button" @click="copyToClipboard('1201098212001', 'acc')" class="text-[11px] font-bold text-[#2F6BFF] hover:underline cursor-pointer">
                                                {{ copiedField === 'acc' ? 'Copied' : 'Copy' }}
                                            </button>
                                        </div>
                                    </div>
                                    <div class="sm:col-span-2 pt-2 border-t border-[#E7E9EE]">
                                        <span class="block text-[10px] font-bold uppercase text-[#9CA3AF]">IBAN Number</span>
                                        <div class="flex items-center gap-2">
                                            <span class="font-mono font-bold text-[#111318] truncate">PK00MEZN01201098212001</span>
                                            <button type="button" @click="copyToClipboard('PK00MEZN01201098212001', 'iban')" class="text-[11px] font-bold text-[#2F6BFF] hover:underline cursor-pointer shrink-0">
                                                {{ copiedField === 'iban' ? 'Copied' : 'Copy' }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Streamlined Receipt Upload -->
                            <div class="space-y-2">
                                <label class="block text-xs font-semibold text-[#111318]">
                                    Payment Receipt Screenshot <span class="text-red-500">*</span>
                                </label>

                                <div v-if="!receiptFileName" class="flex items-center gap-3">
                                    <label class="inline-flex items-center gap-2 rounded-xl border border-[#E7E9EE] bg-white hover:bg-[#FBFBFC] px-4 py-2.5 text-xs font-semibold text-[#111318] cursor-pointer shadow-2xs transition-colors">
                                        <svg class="h-4 w-4 text-[#2F6BFF]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                        </svg>
                                        <span>Choose Receipt Screenshot</span>
                                        <input 
                                            ref="receiptInputRef" 
                                            type="file" 
                                            @change="handleReceiptUpload" 
                                            accept="image/png,image/jpeg,image/webp" 
                                            class="hidden" 
                                        />
                                    </label>
                                    <span class="text-[11px] text-[#9CA3AF]">PNG, JPG &le; 4MB</span>
                                </div>

                                <div v-else class="flex items-center justify-between rounded-xl border border-[#E7E9EE] bg-[#FBFBFC] px-3.5 py-2.5 text-xs">
                                    <div class="flex items-center gap-2 truncate max-w-sm">
                                        <span class="h-2 w-2 rounded-full bg-[#15803D] shrink-0"></span>
                                        <span class="font-bold text-[#111318] truncate">{{ receiptFileName }}</span>
                                        <span class="text-[10px] text-[#9CA3AF] font-mono">({{ receiptFileSize }})</span>
                                    </div>
                                    <button type="button" @click="removeReceipt" class="text-xs text-red-600 font-bold hover:underline cursor-pointer ml-2">
                                        Remove
                                    </button>
                                </div>

                                <div v-if="receiptUploadError" class="text-xs text-red-600 font-semibold">{{ receiptUploadError }}</div>
                                <div v-if="form.errors.payment_receipt" class="text-xs text-red-600 font-semibold">{{ form.errors.payment_receipt }}</div>
                            </div>

                            <!-- Optional Promo Code Link -->
                            <div class="pt-1">
                                <button 
                                    type="button" 
                                    @click="showCouponInput = !showCouponInput" 
                                    class="text-xs font-semibold text-[#2F6BFF] hover:underline cursor-pointer"
                                >
                                    {{ showCouponInput ? '− Hide Promo Code' : '+ Add Promo Code' }}
                                </button>
                                <div v-if="showCouponInput" class="mt-2 max-w-xs">
                                    <input 
                                        v-model="form.coupon_code" 
                                        type="text" 
                                        placeholder="Promo Code" 
                                        class="w-full rounded-xl border border-[#E7E9EE] px-3 py-2 text-xs uppercase text-[#111318] focus:border-[#2F6BFF] focus:outline-none"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- 4. SUBMIT ACTION -->
                        <div class="pt-4 space-y-3">
                            <button 
                                type="submit" 
                                :disabled="form.processing || subdomainStatus === 'taken'"
                                class="raibix-btn-hover group w-full rounded-full bg-[#111318] hover:bg-[#2F6BFF] px-8 py-3.5 text-xs sm:text-sm font-bold text-white transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 cursor-pointer shadow-sm"
                            >
                                <span v-if="form.processing" class="flex items-center gap-2">
                                    <svg class="h-4 w-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span>Provisioning Campus Environment...</span>
                                </span>
                                <span v-else class="flex items-center gap-2">
                                    <span>Complete Order &mdash; PKR {{ currentPrice.toLocaleString() }}</span>
                                    <span class="raibix-dot h-1.5 w-1.5 transition-transform group-hover:scale-125"></span>
                                </span>
                            </button>

                            <p class="text-[11px] text-[#9CA3AF] text-center leading-relaxed">
                                By completing this order, you agree to the 
                                <Link href="/terms" target="_blank" class="text-[#5B6270] underline hover:text-[#111318]">Terms of Service</Link> 
                                and 
                                <Link href="/privacy" target="_blank" class="text-[#5B6270] underline hover:text-[#111318]">Privacy Policy</Link>.
                                Your school portal is initialized immediately and unlocked upon receipt verification.
                            </p>
                        </div>

                    </form>

                </section>

            </div>

        </main>

        <!-- ===================================================================
             3. MOBILE STICKY BOTTOM CHECKOUT BAR
             =================================================================== -->
        <div class="lg:hidden fixed bottom-0 inset-x-0 z-40 border-t border-[#E7E9EE] bg-white/95 backdrop-blur-md p-4 flex items-center justify-between gap-4 shadow-md">
            <div>
                <div class="text-[10px] text-[#5B6270]">Total Due</div>
                <div class="text-sm font-black text-[#111318]">
                    PKR {{ currentPrice.toLocaleString() }}
                </div>
            </div>
            <button 
                type="button" 
                @click="submitCheckout"
                :disabled="form.processing || subdomainStatus === 'taken'"
                class="rounded-full bg-[#111318] hover:bg-[#2F6BFF] px-6 py-2.5 text-xs font-bold text-white transition-all disabled:opacity-50 cursor-pointer flex items-center gap-1.5"
            >
                <span>Complete Order</span>
                <span class="raibix-dot h-1.5 w-1.5"></span>
            </button>
        </div>

    </div>
</template>