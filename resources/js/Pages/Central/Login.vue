<script setup>
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';


const form = useForm({
    email: '',
    password: '',
    remember: true,
});

const showPassword = ref(false);

const submit = () => {
    form.post('/super-admin/login', {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <div class="min-h-screen bg-[#F8F9FA] text-[#202124] selection:bg-[#E8F0FE] selection:text-[#1A73E8] flex flex-col justify-center items-center px-4 py-12 font-sans relative">
        <!-- Top Back Link -->
        <div class="absolute top-6 left-6 sm:top-8 sm:left-8">
            <Link 
                href="/" 
                class="inline-flex items-center space-x-2 text-xs font-semibold text-[#5F6368] hover:text-[#1A73E8] transition-colors group"
            >
                <i class="fa-solid fa-arrow-left text-xs group-hover:-translate-x-1 transition-transform mr-1.5"></i>
                <span>Return to Public Portal</span>
            </Link>
        </div>

        <!-- Minimal Center Container -->
        <div class="w-full max-w-[400px] space-y-8">
            <!-- Brand & Header -->
            <div class="text-center space-y-3">
                <div class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-white border border-[#DADCE0] shadow-2xs text-[#1A73E8] text-xl font-bold mb-1">
                    <i class="fa-solid fa-bolt text-lg"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-[#202124]">
                        Super Admin Portal
                    </h1>
                    <p class="text-xs text-[#5F6368] mt-1">
                        Sign in to manage platform infrastructure
                    </p>
                </div>
            </div>

            <!-- Main Minimal Card -->
            <div class="bg-white rounded-2xl border border-[#DADCE0] p-7 sm:p-8 shadow-xs space-y-6">
                <form @submit.prevent="submit" class="space-y-4">
                    <!-- Email -->
                    <div>
                        <label class="block text-xs font-semibold text-[#3C4043] mb-1.5">
                            Email address
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-[#5F6368]">
                                <i class="fa-solid fa-envelope text-xs"></i>
                            </div>
                            <input 
                                v-model="form.email" 
                                type="email" 
                                required
                                placeholder="name@excellence.com"
                                class="w-full rounded-xl border border-[#DADCE0] bg-white pl-9 pr-4 py-2.5 text-sm text-[#202124] placeholder-[#5F6368]/60 focus:border-[#1A73E8] focus:outline-none focus:ring-3 focus:ring-[#1A73E8]/15 transition-all"
                                :class="{ 'border-[#C5221F] focus:border-[#C5221F] focus:ring-[#C5221F]/15': form.errors.email }"
                            />
                        </div>
                        <div v-if="form.errors.email" class="mt-1 text-xs font-medium text-[#C5221F]">{{ form.errors.email }}</div>
                    </div>

                    <!-- Password -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-xs font-semibold text-[#3C4043]">
                                Password
                            </label>
                        </div>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-[#5F6368]">
                                <i class="fa-solid fa-lock text-xs"></i>
                            </div>
                            <input 
                                v-model="form.password" 
                                :type="showPassword ? 'text' : 'password'" 
                                required
                                placeholder="********"
                                class="w-full rounded-xl border border-[#DADCE0] bg-white pl-9 pr-10 py-2.5 text-sm text-[#202124] placeholder-[#5F6368]/60 focus:border-[#1A73E8] focus:outline-none focus:ring-3 focus:ring-[#1A73E8]/15 transition-all"
                                :class="{ 'border-[#C5221F] focus:border-[#C5221F] focus:ring-[#C5221F]/15': form.errors.password }"
                            />
                            <button 
                                @click="showPassword = !showPassword" 
                                type="button" 
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-[#5F6368] hover:text-[#202124] transition-colors cursor-pointer"
                                :title="showPassword ? 'Hide password' : 'Show password'"
                            >
                                <i v-if="showPassword" class="fa-solid fa-eye-slash text-xs"></i>
                                <i v-else class="fa-solid fa-eye text-xs"></i>
                            </button>
                        </div>
                        <div v-if="form.errors.password" class="mt-1 text-xs font-medium text-[#C5221F]">{{ form.errors.password }}</div>
                    </div>

                    <!-- Remember -->
                    <div class="flex items-center justify-between pt-1">
                        <label class="flex items-center space-x-2 text-xs text-[#5F6368] cursor-pointer">
                            <input 
                                v-model="form.remember" 
                                type="checkbox" 
                                class="rounded border-[#DADCE0] text-[#1A73E8] focus:ring-[#1A73E8] h-4 w-4 cursor-pointer" 
                            />
                            <span>Remember this device</span>
                        </label>
                    </div>

                    <!-- Submit -->
                    <button 
                        type="submit" 
                        :disabled="form.processing"
                        class="w-full rounded-xl bg-[#202124] px-4 py-2.5 text-sm font-semibold text-white shadow-2xs hover:bg-black active:scale-[0.99] disabled:opacity-50 transition-all flex items-center justify-center space-x-2 cursor-pointer mt-2"
                    >
                        <span>{{ form.processing ? 'Signing in...' : 'Sign In' }}</span>
                    </button>
                </form>
            </div>

            <!-- Minimal Footer -->
            <div class="text-center text-[11px] text-[#5F6368] space-y-1">
                <p>Protected by {{ $page.props.platformName || 'Excellence Cloud' }} Security</p>
                <p class="text-[#DADCE0]">&copy; 2026 {{ $page.props.platformName || 'Excellence LMS' }}</p>
            </div>
        </div>
    </div>
</template>
