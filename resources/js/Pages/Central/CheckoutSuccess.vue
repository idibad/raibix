<script setup>
import { ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    tenant: Object,
    payment: Object,
});

const currentStep = ref(1);
const successMessage = ref('');
const copiedUrl = ref(false);

const getDomainSuffix = () => {
    const hostname = window.location.hostname;
    const ipPattern = /^(?:[0-9]{1,3}\.){3}[0-9]{1,3}$/;
    if (ipPattern.test(hostname)) {
        return `${hostname}.nip.io`;
    }
    if (hostname.includes('localhost') || hostname === '127.0.0.1') {
        return 'raibix.cloud';
    }
    return hostname;
};

const getLoginUrl = (role = 'admin') => {
    if (!props.tenant?.domain) return '/';
    const protocol = window.location.protocol || 'http:';
    let domain = props.tenant.domain.trim().toLowerCase().split(':')[0];
    if (!domain.includes('.')) {
        domain = `${domain}.${getDomainSuffix()}`;
    }
    const port = window.location.port ? `:${window.location.port}` : (window.location.hostname.includes('localhost') || domain.includes('localhost') ? ':8000' : '');
    return `${protocol}//${domain}${port}/login`;
};

const userForm = useForm({
    tenant_id: props.tenant?.id || '',
    name: '',
    email: '',
    phone: '',
    user_type: 'teacher',
    password: '',
});

const generatePassword = () => {
    const chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789!@#$';
    let pass = '';
    for (let i = 0; i < 10; i++) {
        pass += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    userForm.password = pass;
};

const submitAddUser = () => {
    userForm.post('/checkout/add-user', {
        preserveScroll: true,
        onSuccess: () => {
            successMessage.value = `Staff account for "${userForm.name}" created successfully.`;
            userForm.reset();
            userForm.tenant_id = props.tenant?.id || '';
            userForm.user_type = 'teacher';
            setTimeout(() => {
                successMessage.value = '';
            }, 6000);
        }
    });
};

const copyUrl = () => {
    const url = getLoginUrl();
    if (window.navigator?.clipboard) {
        window.navigator.clipboard.writeText(url);
        copiedUrl.value = true;
        setTimeout(() => { copiedUrl.value = false; }, 2500);
    }
};
</script>

<template>
    <div class="min-h-screen bg-[#FFFFFF] text-[#111318] font-['Poppins'] antialiased selection:bg-[#8B5CF6]/20 selection:text-[#2F6BFF] flex flex-col justify-between items-center px-6 py-10 sm:py-14">
        
        <div class="w-full max-w-3xl space-y-8">
            
            <!-- Top brand header -->
            <div class="flex items-center justify-between border-b border-[#E7E9EE] pb-4">
                <Link href="/" class="flex items-center gap-2 group" aria-label="Raibix Home">
                    <img 
                        src="/images/raibix.png" 
                        alt="Raibix Logo" 
                        class="h-6 w-auto object-contain transition-transform duration-200 group-hover:scale-102"
                    />
                </Link>
                <Link 
                    href="/" 
                    class="text-xs font-semibold text-[#5B6270] hover:text-[#111318] transition-colors flex items-center gap-1.5"
                >
                    <span>&larr; Return to Raibix Home</span>
                </Link>
            </div>

            <!-- Main Container -->
            <div class="rounded-3xl border border-[#E7E9EE] bg-[#F7F8FA] p-6 sm:p-10 shadow-sm space-y-8">
                
                <!-- Stepper Header -->
                <div class="flex items-center justify-between pb-5 border-b border-[#E7E9EE]">
                    <div class="flex items-center gap-2.5">
                        <span class="raibix-dot h-2.5 w-2.5"></span>
                        <h2 class="text-xs font-bold uppercase tracking-wider text-[#2F6BFF]">
                            {{ currentStep === 1 ? 'Step 1: Subscription & Credentials' : currentStep === 2 ? 'Step 2: Add Initial Staff Accounts' : 'Step 3: Dedicated Campus Subdomain' }}
                        </h2>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <div 
                            v-for="step in 3" 
                            :key="step" 
                            :class="[
                                'h-1.5 w-7 rounded-full transition-all duration-300',
                                step <= currentStep ? 'bg-[#111318]' : 'bg-[#E7E9EE]'
                            ]"
                        ></div>
                    </div>
                </div>

                <!-- STEP 1: Registration Summary & Pending Verification Notice -->
                <div v-if="currentStep === 1" class="space-y-6 text-center">
                    
                    <div class="flex justify-center">
                        <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-amber-500/10 text-amber-600 border border-amber-500/20">
                            <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <div class="text-[11px] font-bold tracking-widest uppercase text-[#2F6BFF]">
                            ORDER RECEIVED
                        </div>
                        <h1 class="text-2xl sm:text-4xl font-black text-[#111318] tracking-tight">
                            Order Submitted &bull; Verification Pending
                        </h1>
                        <p class="text-xs sm:text-sm text-[#5B6270] max-w-lg mx-auto leading-relaxed">
                            Your payment receipt has been registered. Your campus portal will be activated once payment is verified.
                        </p>
                    </div>

                    <!-- Credential Details Panel -->
                    <div class="rounded-2xl border border-[#E7E9EE] bg-white p-6 text-left space-y-4 shadow-2xs">
                        <div class="flex items-center justify-between border-b border-[#E7E9EE] pb-3">
                            <span class="text-xs font-bold text-[#111318]">Root Administrator Account</span>
                            <span class="text-[11px] font-mono text-[#5B6270]">{{ tenant?.id }}</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                            <div>
                                <span class="text-[10px] font-bold text-[#5B6270] uppercase tracking-wider block">Campus Subdomain</span>
                                <span class="font-mono font-bold text-[#111318] mt-0.5 block truncate">{{ tenant?.domain }}</span>
                            </div>
                            <div>
                                <span class="text-[10px] font-bold text-[#5B6270] uppercase tracking-wider block">Login Email</span>
                                <span class="font-mono font-bold text-[#111318] mt-0.5 block truncate">{{ tenant?.admin_email }}</span>
                            </div>
                            <div>
                                <span class="text-[10px] font-bold text-[#5B6270] uppercase tracking-wider block">Password</span>
                                <span class="font-mono text-[#5B6270] mt-0.5 block">[Password set during checkout]</span>
                            </div>
                            <div>
                                <span class="text-[10px] font-bold text-[#5B6270] uppercase tracking-wider block">Settlement Status</span>
                                <span class="inline-flex items-center gap-1.5 font-bold text-amber-600 mt-0.5">
                                    <span class="h-2 w-2 rounded-full bg-amber-500 animate-pulse"></span>
                                    <span>Receipt under manual review</span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 flex flex-col sm:flex-row items-center justify-center gap-4">
                        <button 
                            @click="currentStep = 2"
                            class="raibix-btn-hover w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-full bg-[#111318] hover:bg-[#2F6BFF] px-8 py-3.5 text-xs font-bold text-white transition-all cursor-pointer shadow-sm"
                        >
                            <span>Next: Add Initial Staff Accounts</span>
                            <span class="raibix-dot h-1.5 w-1.5"></span>
                        </button>
                    </div>
                </div>

                <!-- STEP 2: Add Initial Users / Staff -->
                <div v-if="currentStep === 2" class="space-y-6">
                    <div class="text-center space-y-1">
                        <div class="text-[11px] font-bold tracking-widest uppercase text-[#2F6BFF]">
                            STAFF ACCOUNTS
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-black text-[#111318] tracking-tight">
                            Create Initial Staff Accounts
                        </h1>
                        <p class="text-xs text-[#5B6270]">
                            Add key faculty or staff to your campus portal. You can also skip this and add staff later from the admin dashboard.
                        </p>
                    </div>

                    <!-- Success Alert -->
                    <div v-if="successMessage" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-xs text-emerald-700 font-bold flex items-center gap-2">
                        <span class="h-2 w-2 rounded-full bg-emerald-600"></span>
                        <span>{{ successMessage }}</span>
                    </div>

                    <form @submit.prevent="submitAddUser" class="space-y-5 text-left bg-white p-6 rounded-2xl border border-[#E7E9EE] shadow-2xs">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-[#111318] mb-1.5">Full Name *</label>
                                <input v-model="userForm.name" type="text" required placeholder="e.g. Sir Tariq Mehmood" class="w-full rounded-xl border border-[#E7E9EE] bg-[#FBFBFC] px-4 py-2.5 text-xs text-[#111318] focus:border-[#2F6BFF] focus:outline-none" />
                                <span v-if="userForm.errors.name" class="text-xs text-red-600 font-semibold block mt-1">{{ userForm.errors.name }}</span>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-[#111318] mb-1.5">Official Email Address *</label>
                                <input v-model="userForm.email" type="email" required placeholder="tariq@yourschool.edu.pk" class="w-full rounded-xl border border-[#E7E9EE] bg-[#FBFBFC] px-4 py-2.5 text-xs text-[#111318] focus:border-[#2F6BFF] focus:outline-none" />
                                <span v-if="userForm.errors.email" class="text-xs text-red-600 font-semibold block mt-1">{{ userForm.errors.email }}</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-[#111318] mb-1.5">Phone Number</label>
                                <input v-model="userForm.phone" type="text" placeholder="0300 1234567" class="w-full rounded-xl border border-[#E7E9EE] bg-[#FBFBFC] px-4 py-2.5 text-xs text-[#111318] focus:border-[#2F6BFF] focus:outline-none" />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-[#111318] mb-1.5">Role Allocation *</label>
                                <select v-model="userForm.user_type" required class="w-full rounded-xl border border-[#E7E9EE] bg-[#FBFBFC] px-4 py-2.5 text-xs text-[#111318] focus:border-[#2F6BFF] focus:outline-none font-semibold">
                                    <option value="teacher">Teacher (Classroom Faculty)</option>
                                    <option value="admin">Principal / Vice Principal</option>
                                    <option value="accountant">Accountant / Bursar</option>
                                    <option value="registrar">Registrar / Admissions</option>
                                    <option value="librarian">Librarian</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-[#111318] mb-1.5">Password *</label>
                            <div class="flex gap-2">
                                <input v-model="userForm.password" type="text" required placeholder="Create temporary login password" class="w-full rounded-xl border border-[#E7E9EE] bg-[#FBFBFC] px-4 py-2.5 text-xs font-mono text-[#111318] focus:border-[#2F6BFF] focus:outline-none" />
                                <button type="button" @click="generatePassword" class="shrink-0 rounded-xl border border-[#E7E9EE] bg-white px-4 py-2.5 text-xs font-bold text-[#111318] hover:bg-[#F3F4F6] cursor-pointer shadow-2xs">
                                    Generate
                                </button>
                            </div>
                            <span v-if="userForm.errors.password" class="text-xs text-red-600 font-semibold block mt-1">{{ userForm.errors.password }}</span>
                        </div>

                        <div class="flex items-center justify-between pt-4 border-t border-[#E7E9EE]">
                            <button 
                                type="button" 
                                @click="currentStep = 3"
                                class="rounded-full px-5 py-2.5 text-xs font-bold text-[#5B6270] hover:text-[#111318] cursor-pointer"
                            >
                                Skip to Portal &rarr;
                            </button>
                            <button 
                                type="submit" 
                                :disabled="userForm.processing"
                                class="raibix-btn-hover rounded-full bg-[#111318] hover:bg-[#2F6BFF] px-6 py-2.5 text-xs font-bold text-white shadow-sm disabled:opacity-50 cursor-pointer"
                            >
                                {{ userForm.processing ? 'Creating Account...' : 'Create Account' }}
                            </button>
                        </div>
                    </form>
                </div>

                <!-- STEP 3: Subdomain URL & Launch Notice -->
                <div v-if="currentStep === 3" class="space-y-6 text-center">
                    <div class="flex justify-center">
                        <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-[#2F6BFF]/10 text-[#2F6BFF] border border-[#2F6BFF]/20">
                            <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <div class="text-[11px] font-bold tracking-widest uppercase text-[#2F6BFF]">
                            CAMPUS PORTAL
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-black text-[#111318] tracking-tight">
                            Campus Subdomain Ready
                        </h1>
                        <p class="text-xs text-[#5B6270]">
                            Bookmark your dedicated institutional portal address below.
                        </p>
                    </div>

                    <div class="rounded-2xl bg-white border border-[#E7E9EE] p-5 text-left space-y-2 shadow-2xs">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-[#5B6270] block">Campus Portal Address</span>
                        <div class="flex items-center justify-between bg-[#FBFBFC] px-4 py-3 rounded-xl border border-[#E7E9EE]">
                            <span class="font-mono text-xs sm:text-sm font-bold text-[#2F6BFF] truncate">{{ getLoginUrl() }}</span>
                            <button 
                                @click="copyUrl"
                                class="text-xs font-bold text-[#111318] hover:text-[#2F6BFF] ml-2 cursor-pointer shrink-0"
                            >
                                {{ copiedUrl ? 'Copied' : 'Copy' }}
                            </button>
                        </div>
                    </div>

                    <div class="space-y-3 pt-2">
                        <a 
                            :href="getLoginUrl()" 
                            target="_blank"
                            rel="noopener noreferrer"
                            class="raibix-btn-hover w-full inline-flex items-center justify-center gap-2 rounded-full bg-[#111318] hover:bg-[#2F6BFF] px-8 py-3.5 text-xs sm:text-sm font-bold text-white shadow-sm cursor-pointer"
                        >
                            <span>Open Campus Portal</span>
                            <span class="raibix-dot h-1.5 w-1.5"></span>
                        </a>
                        <p class="text-[11px] text-[#5B6270]">
                            Your portal will unlock all administrative features immediately upon manual payment receipt verification.
                        </p>
                    </div>
                </div>

            </div>

            <div class="text-center text-xs text-[#5B6270]">
                <p>&copy; 2026 Raibix Technologies. All rights reserved.</p>
            </div>
        </div>

    </div>
</template>