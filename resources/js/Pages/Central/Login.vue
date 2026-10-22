<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, Link, Head, usePage, router } from '@inertiajs/vue3';

const props = defineProps({
    initialRequires2fa: {
        type: Boolean,
        default: false
    },
    twoFactorEmail: {
        type: String,
        default: ''
    },
    twoFactorHint: {
        type: String,
        default: ''
    }
});

const page = usePage();

// Determine whether we are in credentials step or 2FA challenge step
const step = ref(props.initialRequires2fa || page.props.flash?.requires_2fa ? '2fa' : 'credentials');

const activeTwoFactorEmail = computed(() => {
    return page.props.flash?.two_factor_email || props.twoFactorEmail || form.email;
});

const activeTwoFactorHint = computed(() => {
    return page.props.flash?.two_factor_hint || props.twoFactorHint || '';
});

// Watch flash message updates
watch(() => page.props.flash, (flash) => {
    if (flash?.requires_2fa) {
        step.value = '2fa';
    }
}, { deep: true, immediate: true });

// Step 1 Form: Email & Password
const form = useForm({
    email: '',
    password: '',
    remember: true,
});

const showPassword = ref(false);

const fillDemoCredentials = () => {
    form.email = 'testadmin@lms.com';
    form.password = 'testadmin';
};

const submitCredentials = () => {
    form.post('/super-admin/login', {
        onSuccess: () => {
            if (page.props.flash?.requires_2fa) {
                step.value = '2fa';
            }
        },
        onFinish: () => form.reset('password'),
    });
};

// Step 2 Form: 2FA Verification
const twoFactorForm = useForm({
    code: '',
});

const fill2faHint = () => {
    if (activeTwoFactorHint.value) {
        twoFactorForm.code = activeTwoFactorHint.value;
    }
};

const submit2fa = () => {
    twoFactorForm.post('/super-admin/login/verify-2fa', {
        onError: () => {
            twoFactorForm.code = '';
        }
    });
};

const resend2fa = () => {
    router.post('/super-admin/login/resend-2fa', {}, {
        preserveScroll: true
    });
};

const cancel2fa = () => {
    router.post('/super-admin/login/cancel-2fa', {}, {
        onSuccess: () => {
            step.value = 'credentials';
            twoFactorForm.reset();
        }
    });
};
</script>

<template>
    <Head title="Super Admin Login" />

    <div class="min-h-screen bg-[#FAFAFC] text-[#111318] selection:bg-[#EEF3FF] selection:text-[#2F6BFF] flex flex-col justify-between font-sans relative overflow-hidden">
        
        <!-- Ambient Decorative Orb Background Glows -->
        <div class="pointer-events-none absolute -top-40 left-1/2 -translate-x-1/2 w-[720px] h-[720px] rounded-full bg-gradient-to-b from-[#2F6BFF]/12 via-[#8B5CF6]/8 to-transparent blur-3xl opacity-70"></div>
        <div class="pointer-events-none absolute -bottom-48 -right-24 w-[480px] h-[480px] rounded-full bg-gradient-to-tr from-[#2F6BFF]/10 via-[#38BDF8]/10 to-transparent blur-3xl opacity-60"></div>
        <div class="pointer-events-none absolute top-1/3 -left-32 w-[380px] h-[380px] rounded-full bg-gradient-to-br from-[#8B5CF6]/10 to-transparent blur-3xl opacity-50"></div>

        <!-- Top Header Navigation -->
        <header class="relative z-10 w-full max-w-7xl mx-auto px-6 py-6 sm:py-8 flex items-center justify-between">
            <Link 
                href="/" 
                class="inline-flex items-center space-x-2 text-xs font-semibold text-[#5B6270] hover:text-[#111318] transition-colors group px-3.5 py-2 rounded-full bg-white/80 border border-[#E6E8EE] backdrop-blur-xs hover:border-[#111318] shadow-2xs cursor-pointer"
            >
                <i class="fa-solid fa-arrow-left text-xs group-hover:-translate-x-1 transition-transform mr-1"></i>
                <span>Return to Public Portal</span>
            </Link>

            <div class="flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white/80 border border-[#E6E8EE] backdrop-blur-xs text-xs font-medium text-[#5B6270] shadow-2xs">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                </span>
                <span class="font-semibold text-[#111318]">Node 01</span>
                <span class="text-[#8F94A0]">•</span>
                <span>Operational</span>
            </div>
        </header>

        <!-- Main Card Section -->
        <main class="relative z-10 flex-1 flex flex-col justify-center items-center px-4 py-8 sm:py-12">
            <div class="w-full max-w-[440px] space-y-6">

                <!-- Brand Header -->
                <div class="text-center space-y-3">
                    <Link href="/" class="inline-flex items-center justify-center group mb-1">
                        <img 
                            src="/images/raibix.png" 
                            alt="Raibix Logo" 
                            class="h-8 sm:h-9 w-auto object-contain transition-transform duration-200 group-hover:scale-105"
                        />
                    </Link>
                    
                    <div class="space-y-1">
                        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#EEF3FF] border border-[#D6E4FF] text-[11px] font-bold text-[#2F6BFF] uppercase tracking-wider">
                            <span class="raibix-dot h-1.5 w-1.5"></span>
                            <span>{{ step === '2fa' ? '2-Factor Security Challenge' : 'Root Infrastructure Access' }}</span>
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-[#111318]">
                            {{ step === '2fa' ? 'Enter Security Code' : 'Super Admin Portal' }}
                        </h1>
                        <p class="text-xs sm:text-sm text-[#5B6270] max-w-sm mx-auto">
                            {{ step === '2fa' ? 'Verify your identity to authenticate to the cloud control center.' : 'Sign in to manage school tenant clusters, billing pipelines, and edge services.' }}
                        </p>
                    </div>
                </div>

                <!-- Main Glass/Raibix Card -->
                <div class="bg-white/95 backdrop-blur-md rounded-3xl border border-[#E6E8EE] p-7 sm:p-9 shadow-[0_20px_50px_-20px_rgba(47,107,255,0.12)] space-y-6">
                    
                    <!-- Flash / Global Alerts -->
                    <div v-if="$page.props.flash?.success" class="rounded-xl bg-emerald-50 border border-emerald-200 p-3.5 text-xs font-semibold text-emerald-700 flex items-center gap-2">
                        <i class="fa-solid fa-circle-check shrink-0 text-sm"></i>
                        <span>{{ $page.props.flash.success }}</span>
                    </div>

                    <div v-if="$page.props.flash?.error" class="rounded-xl bg-red-50 border border-red-200 p-3.5 text-xs font-semibold text-red-600 flex items-center gap-2">
                        <i class="fa-solid fa-circle-exclamation shrink-0 text-sm"></i>
                        <span>{{ $page.props.flash.error }}</span>
                    </div>

                    <!-- STEP 1: CREDENTIALS (EMAIL + PASSWORD) -->
                    <div v-if="step === 'credentials'">
                        <form @submit.prevent="submitCredentials" class="space-y-4">
                            <!-- Email Input -->
                            <div>
                                <label class="block text-xs font-bold text-[#111318] uppercase tracking-wider mb-2">
                                    Root Administrator Email
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#5B6270]">
                                        <i class="fa-regular fa-envelope text-sm"></i>
                                    </div>
                                    <input 
                                        v-model="form.email" 
                                        type="email" 
                                        required
                                        placeholder="admin@raibix.com"
                                        class="w-full rounded-xl border border-[#E6E8EE] bg-[#F8F9FA] pl-10 pr-4 py-3 text-sm text-[#111318] placeholder-[#8F94A0] focus:bg-white focus:border-[#2F6BFF] focus:outline-none focus:ring-4 focus:ring-[#2F6BFF]/10 transition-all font-medium"
                                        :class="{ 'border-red-400 bg-red-50/50 focus:border-red-500 focus:ring-red-500/10': form.errors.email }"
                                    />
                                </div>
                                <div v-if="form.errors.email" class="mt-1.5 text-xs font-medium text-red-600 flex items-center gap-1">
                                    <i class="fa-solid fa-circle-exclamation text-2xs"></i>
                                    <span>{{ form.errors.email }}</span>
                                </div>
                            </div>

                            <!-- Password Input -->
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <label class="block text-xs font-bold text-[#111318] uppercase tracking-wider">
                                        Master Password
                                    </label>
                                </div>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#5B6270]">
                                        <i class="fa-solid fa-lock text-sm"></i>
                                    </div>
                                    <input 
                                        v-model="form.password" 
                                        :type="showPassword ? 'text' : 'password'" 
                                        required
                                        placeholder="••••••••••••"
                                        class="w-full rounded-xl border border-[#E6E8EE] bg-[#F8F9FA] pl-10 pr-11 py-3 text-sm text-[#111318] placeholder-[#8F94A0] focus:bg-white focus:border-[#2F6BFF] focus:outline-none focus:ring-4 focus:ring-[#2F6BFF]/10 transition-all font-medium"
                                        :class="{ 'border-red-400 bg-red-50/50 focus:border-red-500 focus:ring-red-500/10': form.errors.password }"
                                    />
                                    <button 
                                        @click="showPassword = !showPassword" 
                                        type="button" 
                                        class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-[#5B6270] hover:text-[#111318] transition-colors cursor-pointer"
                                        :title="showPassword ? 'Hide password' : 'Show password'"
                                        tabindex="-1"
                                    >
                                        <i v-if="showPassword" class="fa-regular fa-eye-slash text-sm"></i>
                                        <i v-else class="fa-regular fa-eye text-sm"></i>
                                    </button>
                                </div>
                                <div v-if="form.errors.password" class="mt-1.5 text-xs font-medium text-red-600 flex items-center gap-1">
                                    <i class="fa-solid fa-circle-exclamation text-2xs"></i>
                                    <span>{{ form.errors.password }}</span>
                                </div>
                            </div>

                            <!-- Remember Me & Autofill -->
                            <div class="flex items-center justify-between pt-1">
                                <label class="flex items-center space-x-2 text-xs text-[#5B6270] cursor-pointer select-none">
                                    <input 
                                        v-model="form.remember" 
                                        type="checkbox" 
                                        class="rounded-md border-[#E6E8EE] text-[#2F6BFF] focus:ring-[#2F6BFF] h-4 w-4 cursor-pointer" 
                                    />
                                    <span class="font-medium">Remember workstation</span>
                                </label>

                                <button 
                                    type="button" 
                                    @click="fillDemoCredentials"
                                    class="text-xs font-semibold text-[#2F6BFF] hover:text-[#1E54DC] hover:underline cursor-pointer inline-flex items-center gap-1"
                                >
                                    <i class="fa-solid fa-wand-magic-sparkles text-2xs"></i>
                                    <span>Autofill Demo</span>
                                </button>
                            </div>

                            <!-- Submit Button -->
                            <button 
                                type="submit" 
                                :disabled="form.processing"
                                class="w-full rounded-xl bg-[#111318] hover:bg-[#2F6BFF] px-4 py-3.5 text-sm font-bold text-white shadow-sm active:scale-[0.99] disabled:opacity-50 transition-all duration-200 flex items-center justify-center space-x-2 cursor-pointer mt-3 group"
                            >
                                <span v-if="form.processing">Verifying Credentials...</span>
                                <span v-else class="flex items-center gap-2">
                                    <span>Continue to 2FA Verification</span>
                                    <i class="fa-solid fa-arrow-right text-xs transition-transform group-hover:translate-x-1"></i>
                                </span>
                            </button>
                        </form>
                    </div>

                    <!-- STEP 2: TWO-FACTOR AUTHENTICATION (2FA) -->
                    <div v-else-if="step === '2fa'" class="space-y-5 animate-fadeIn">
                        
                        <!-- Account Badge -->
                        <div class="p-3.5 rounded-2xl bg-[#EEF3FF] border border-[#D6E4FF] flex items-center justify-between">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <div class="h-8 w-8 rounded-xl bg-white border border-[#D6E4FF] flex items-center justify-center text-[#2F6BFF] font-bold text-xs shrink-0 shadow-2xs">
                                    <i class="fa-solid fa-shield-halved"></i>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs font-bold text-[#111318] truncate">{{ activeTwoFactorEmail }}</p>
                                    <p class="text-2xs text-[#5B6270]">6-digit challenge active</p>
                                </div>
                            </div>
                            <button 
                                v-if="activeTwoFactorHint" 
                                @click="fill2faHint" 
                                class="px-2.5 py-1 rounded-lg bg-white border border-[#D6E4FF] text-2xs font-bold text-[#2F6BFF] hover:bg-[#2F6BFF] hover:text-white transition-colors cursor-pointer shadow-2xs"
                                title="Click to fill security code"
                            >
                                Paste: {{ activeTwoFactorHint }}
                            </button>
                        </div>

                        <form @submit.prevent="submit2fa" class="space-y-4">
                            <div>
                                <label class="block text-xs font-bold text-[#111318] uppercase tracking-wider mb-2 text-center">
                                    Enter 6-Digit Security OTP
                                </label>
                                
                                <div class="relative">
                                    <input 
                                        v-model="twoFactorForm.code"
                                        type="text" 
                                        maxlength="6"
                                        required 
                                        autofocus
                                        placeholder="••••••"
                                        class="w-full rounded-2xl border border-[#E6E8EE] bg-[#F8F9FA] py-3.5 text-center text-2xl font-mono font-extrabold tracking-[0.4em] text-[#111318] placeholder-[#8F94A0] focus:bg-white focus:border-[#2F6BFF] focus:outline-none focus:ring-4 focus:ring-[#2F6BFF]/10 transition-all"
                                        :class="{ 'border-red-400 bg-red-50/50 focus:border-red-500 focus:ring-red-500/10': twoFactorForm.errors.code }"
                                    />
                                </div>
                                
                                <div v-if="twoFactorForm.errors.code" class="mt-2 text-xs font-medium text-red-600 text-center flex items-center justify-center gap-1">
                                    <i class="fa-solid fa-circle-exclamation text-2xs"></i>
                                    <span>{{ twoFactorForm.errors.code }}</span>
                                </div>
                            </div>

                            <!-- Verify Button -->
                            <button 
                                type="submit" 
                                :disabled="twoFactorForm.processing || twoFactorForm.code.length < 6"
                                class="w-full rounded-xl bg-[#111318] hover:bg-[#2F6BFF] px-4 py-3.5 text-sm font-bold text-white shadow-sm active:scale-[0.99] disabled:opacity-50 transition-all duration-200 flex items-center justify-center space-x-2 cursor-pointer group"
                            >
                                <span v-if="twoFactorForm.processing">Verifying 2FA Token...</span>
                                <span v-else class="flex items-center gap-2">
                                    <i class="fa-solid fa-lock text-xs"></i>
                                    <span>Verify & Launch Session</span>
                                </span>
                            </button>

                            <!-- Actions Row -->
                            <div class="flex items-center justify-between pt-2 text-xs font-semibold text-[#5B6270]">
                                <button 
                                    type="button" 
                                    @click="resend2fa"
                                    class="hover:text-[#2F6BFF] hover:underline cursor-pointer flex items-center gap-1"
                                >
                                    <i class="fa-solid fa-rotate-right text-2xs"></i>
                                    <span>Resend Code</span>
                                </button>
                                
                                <button 
                                    type="button" 
                                    @click="cancel2fa"
                                    class="hover:text-red-600 hover:underline cursor-pointer flex items-center gap-1"
                                >
                                    <i class="fa-solid fa-arrow-left text-2xs"></i>
                                    <span>Change Account</span>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Trust / Architecture Badges -->
                    <div class="pt-2 border-t border-[#E6E8EE]/80 grid grid-cols-3 gap-2 text-center">
                        <div class="p-2 rounded-lg bg-[#F8F9FA] text-[10px] text-[#5B6270] font-semibold flex flex-col items-center gap-1">
                            <i class="fa-solid fa-shield-halved text-[#2F6BFF]"></i>
                            <span>Isolated DB</span>
                        </div>
                        <div class="p-2 rounded-lg bg-[#F8F9FA] text-[10px] text-[#5B6270] font-semibold flex flex-col items-center gap-1">
                            <i class="fa-solid fa-key text-[#2F6BFF]"></i>
                            <span>2FA Enforced</span>
                        </div>
                        <div class="p-2 rounded-lg bg-[#F8F9FA] text-[10px] text-[#5B6270] font-semibold flex flex-col items-center gap-1">
                            <i class="fa-solid fa-fingerprint text-[#2F6BFF]"></i>
                            <span>TLS 1.3 Active</span>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="text-center text-xs text-[#5B6270] space-y-1">
                    <p class="font-medium">Protected by Raibix Enterprise Security Architecture</p>
                    <p class="text-[11px] text-[#8F94A0]">&copy; 2026 Raibix Platform. All rights reserved.</p>
                </div>
            </div>
        </main>

        <div class="h-6 sm:h-8"></div>
    </div>
</template>