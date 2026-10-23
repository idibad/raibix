<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useForm, Link, Head, usePage, router } from '@inertiajs/vue3';

const props = defineProps({
    initialRequires2fa: {
        type: Boolean,
        default: false
    },
    twoFactorEmail: {
        type: String,
        default: ''
    }
});

const page = usePage();

// If user already has an active Central Super Admin session, immediately redirect to dashboard
onMounted(() => {
    if (page.props.auth?.user && !page.props.tenant) {
        const userType = page.props.auth.user.user_type;
        if (!userType || !['student', 'parent', 'teacher', 'staff', 'librarian', 'accountant', 'registrar'].includes(userType)) {
            router.visit('/super-admin');
        }
    }
});

// Determine whether we are in credentials step or 2FA challenge step
const step = ref(props.initialRequires2fa || page.props.flash?.requires_2fa ? '2fa' : 'credentials');

const activeTwoFactorEmail = computed(() => {
    return page.props.flash?.two_factor_email || props.twoFactorEmail || form.email;
});

// Watch props and flash updates to stay in sync
watch(() => props.initialRequires2fa, (requires) => {
    if (requires) {
        step.value = '2fa';
    } else {
        step.value = 'credentials';
    }
});

watch(() => page.props.flash?.requires_2fa, (requires) => {
    if (requires) {
        step.value = '2fa';
    }
}, { immediate: true });

// Step 1 Form: Email & Password
const form = useForm({
    email: '',
    password: '',
    remember: true,
});

const showPassword = ref(false);

const submitCredentials = () => {
    form.post('/super-admin/login', {
        onSuccess: () => {
            step.value = '2fa';
        },
        onFinish: () => form.reset('password'),
    });
};

// Step 2 Form: 2FA Verification
const twoFactorForm = useForm({
    code: '',
});

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

    <div class="min-h-screen bg-[#FAFAFC] text-[#111318] flex flex-col justify-between font-sans">
        
        <!-- Top Navbar with Logo on Top Left -->
        <header class="w-full max-w-6xl mx-auto px-6 py-6 flex items-center justify-between">
            <Link href="/" class="flex items-center">
                <img 
                    src="/images/raibix.png" 
                    alt="Raibix" 
                    class="h-7 w-auto object-contain"
                />
            </Link>

            <Link 
                href="/" 
                class="text-xs font-semibold text-[#5B6270] hover:text-[#111318] transition-colors flex items-center gap-1.5"
            >
                <i class="fa-solid fa-arrow-left text-[10px]"></i>
                <span>Back to Home</span>
            </Link>
        </header>

        <!-- Main Form Section -->
        <main class="flex-1 flex flex-col justify-center items-center px-4 py-8">
            <div class="w-full max-w-[400px] space-y-6">

                <!-- Header Title -->
                <div class="space-y-1 text-center">
                    <h1 class="text-2xl font-bold tracking-tight text-[#111318]">
                        {{ step === '2fa' ? 'Two-Factor Verification' : 'Super Admin Sign In' }}
                    </h1>
                    <p class="text-xs text-[#5B6270]">
                        {{ step === '2fa' ? `Enter the 6-digit code sent to ${activeTwoFactorEmail}` : 'Enter your credentials to access the central platform.' }}
                    </p>
                </div>

                <!-- Single Clean Login Card -->
                <div class="bg-white rounded-2xl border border-[#E6E8EE] p-7 sm:p-8 shadow-xs space-y-5">
                    
                    <!-- Flash Messages -->
                    <div v-if="$page.props.flash?.success" class="rounded-xl bg-emerald-50 border border-emerald-200 p-3 text-xs font-medium text-emerald-700 flex items-center gap-2">
                        <i class="fa-solid fa-circle-check shrink-0 text-xs"></i>
                        <span>{{ $page.props.flash.success }}</span>
                    </div>

                    <div v-if="$page.props.flash?.error" class="rounded-xl bg-red-50 border border-red-200 p-3 text-xs font-medium text-red-600 flex items-center gap-2">
                        <i class="fa-solid fa-circle-exclamation shrink-0 text-xs"></i>
                        <span>{{ $page.props.flash.error }}</span>
                    </div>

                    <!-- STEP 1: CREDENTIALS -->
                    <div v-if="step === 'credentials'">
                        <form @submit.prevent="submitCredentials" class="space-y-4">
                            <!-- Email -->
                            <div>
                                <label class="block text-xs font-semibold text-[#111318] mb-1.5">
                                    Email address
                                </label>
                                <input 
                                    v-model="form.email" 
                                    type="email" 
                                    required
                                    autocomplete="username"
                                    placeholder="admin@raibix.com"
                                    class="w-full rounded-xl border border-[#E6E8EE] bg-white px-3.5 py-2.5 text-xs text-[#111318] placeholder-[#8F94A0] focus:border-[#2F6BFF] focus:outline-none focus:ring-2 focus:ring-[#2F6BFF]/10 transition-all font-medium"
                                    :class="{ 'border-red-400 bg-red-50/30': form.errors.email }"
                                />
                                <div v-if="form.errors.email" class="mt-1 text-xs text-red-600">
                                    {{ form.errors.email }}
                                </div>
                            </div>

                            <!-- Password -->
                            <div>
                                <label class="block text-xs font-semibold text-[#111318] mb-1.5">
                                    Password
                                </label>
                                <div class="relative">
                                    <input 
                                        v-model="form.password" 
                                        :type="showPassword ? 'text' : 'password'" 
                                        required
                                        autocomplete="current-password"
                                        placeholder="Enter your password"
                                        class="w-full rounded-xl border border-[#E6E8EE] bg-white px-3.5 pr-10 py-2.5 text-xs text-[#111318] placeholder-[#8F94A0] focus:border-[#2F6BFF] focus:outline-none focus:ring-2 focus:ring-[#2F6BFF]/10 transition-all font-medium"
                                        :class="{ 'border-red-400 bg-red-50/30': form.errors.password }"
                                    />
                                    <button 
                                        @click="showPassword = !showPassword" 
                                        type="button" 
                                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-[#5B6270] hover:text-[#111318] cursor-pointer"
                                        tabindex="-1"
                                    >
                                        <i v-if="showPassword" class="fa-regular fa-eye-slash text-xs"></i>
                                        <i v-else class="fa-regular fa-eye text-xs"></i>
                                    </button>
                                </div>
                                <div v-if="form.errors.password" class="mt-1 text-xs text-red-600">
                                    {{ form.errors.password }}
                                </div>
                            </div>

                            <!-- Remember Me Checkbox -->
                            <div class="flex items-center pt-0.5">
                                <label class="flex items-center space-x-2 text-xs text-[#5B6270] cursor-pointer select-none">
                                    <input 
                                        v-model="form.remember" 
                                        type="checkbox" 
                                        class="rounded border-[#E6E8EE] text-[#2F6BFF] focus:ring-[#2F6BFF] h-3.5 w-3.5 cursor-pointer" 
                                    />
                                    <span>Remember me</span>
                                </label>
                            </div>

                            <!-- Submit Button -->
                            <button 
                                type="submit" 
                                :disabled="form.processing"
                                class="w-full rounded-xl bg-[#111318] hover:bg-[#2F6BFF] px-4 py-2.5 text-xs font-semibold text-white shadow-2xs disabled:opacity-50 transition-colors cursor-pointer"
                            >
                                <span v-if="form.processing">Signing in...</span>
                                <span v-else>Sign in</span>
                            </button>
                        </form>
                    </div>

                    <!-- STEP 2: 2FA CODE -->
                    <div v-else-if="step === '2fa'" class="space-y-4">
                        <form @submit.prevent="submit2fa" class="space-y-4">
                            <div>
                                <label class="block text-xs font-semibold text-[#111318] mb-1.5 text-center">
                                    Verification code
                                </label>
                                <input 
                                    v-model="twoFactorForm.code"
                                    type="text" 
                                    maxlength="6"
                                    required 
                                    autofocus
                                    inputmode="numeric"
                                    pattern="[0-9]*"
                                    placeholder="••••••"
                                    class="w-full rounded-xl border border-[#E6E8EE] bg-white py-3 text-center text-xl font-mono font-bold tracking-[0.3em] text-[#111318] placeholder-[#8F94A0] focus:border-[#2F6BFF] focus:outline-none focus:ring-2 focus:ring-[#2F6BFF]/10 transition-all"
                                    :class="{ 'border-red-400 bg-red-50/30': twoFactorForm.errors.code }"
                                />
                                <div v-if="twoFactorForm.errors.code" class="mt-1.5 text-xs text-red-600 text-center font-medium">
                                    {{ twoFactorForm.errors.code }}
                                </div>
                            </div>

                            <!-- Verify Button -->
                            <button 
                                type="submit" 
                                :disabled="twoFactorForm.processing || twoFactorForm.code.length < 6"
                                class="w-full rounded-xl bg-[#111318] hover:bg-[#2F6BFF] px-4 py-2.5 text-xs font-semibold text-white shadow-2xs disabled:opacity-50 transition-colors cursor-pointer"
                            >
                                <span v-if="twoFactorForm.processing">Verifying...</span>
                                <span v-else>Verify & Continue</span>
                            </button>

                            <!-- Actions Row -->
                            <div class="flex items-center justify-between pt-1 text-xs font-medium text-[#5B6270]">
                                <button 
                                    type="button" 
                                    @click="resend2fa"
                                    class="hover:text-[#2F6BFF] hover:underline cursor-pointer"
                                >
                                    Resend code
                                </button>
                                
                                <button 
                                    type="button" 
                                    @click="cancel2fa"
                                    class="hover:text-red-600 hover:underline cursor-pointer"
                                >
                                    Back to sign in
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Footer -->
                <div class="text-center text-xs text-[#8F94A0]">
                    &copy; 2026 Raibix Platform. All rights reserved.
                </div>
            </div>
        </main>

        <div class="h-6"></div>
    </div>
</template>
