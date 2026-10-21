<script setup>
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    tenant: Object,
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <div class="relative flex min-h-screen items-center justify-center overflow-hidden bg-[#F8F9FA] px-4 text-[#202124] selection:bg-[#E8F0FE] selection:text-[#1A73E8]">
        <div class="google-card z-10 w-full max-w-md rounded-2xl border border-[#DADCE0] bg-white p-8 shadow-sm">
            <!-- Header -->
            <div class="text-center">
                <div v-if="$page.props.tenant?.logo || tenant?.logo" class="mx-auto flex h-20 w-20 items-center justify-center overflow-hidden rounded-2xl bg-white p-1.5 border border-[#DADCE0] shadow-sm">
                    <img :src="$page.props.tenant?.logo || tenant?.logo" :alt="tenant?.name || 'School Logo'" class="max-h-full max-w-full object-contain" />
                </div>
                <div v-else class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-[#E8F0FE] font-bold text-2xl text-[#1A73E8] border border-[#D2E3FC] shadow-2xs">
                    <i class="fa-solid fa-graduation-cap"></i>
                </div>
                <h1 class="mt-4 text-2xl font-bold tracking-tight text-[#202124]">
                    {{ $page.props.tenant?.name || tenant?.name || $page.props.platformName || 'School Portal' }}
                </h1>
                <p class="mt-1 text-sm text-[#5F6368]">
                    School Unified Portal Access
                </p>
            </div>

            <form @submit.prevent="submit" class="mt-6 space-y-4">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-[#5F6368] mb-1.5">Email / Portal ID</label>
                    <input 
                        v-model="form.email"
                        type="email" 
                        required 
                        placeholder="user@school.test"
                        class="w-full rounded-lg border border-[#DADCE0] bg-white px-4 py-3 text-sm text-[#202124] placeholder-[#5F6368] focus:border-[#1A73E8] focus:outline-none focus:ring-2 focus:ring-[#1A73E8]/20 transition-all"
                    />
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-[#5F6368] mb-1.5">Password</label>
                    <input 
                        v-model="form.password"
                        type="password" 
                        required 
                        placeholder="********"
                        class="w-full rounded-lg border border-[#DADCE0] bg-white px-4 py-3 text-sm text-[#202124] placeholder-[#5F6368] focus:border-[#1A73E8] focus:outline-none focus:ring-2 focus:ring-[#1A73E8]/20 transition-all"
                    />
                </div>

                <div v-if="form.errors.email" class="rounded-lg bg-[#FCE8E6] border border-[#FAD2CF] p-3 text-xs text-[#C5221F]">
                    {{ form.errors.email }}
                </div>

                <div class="flex items-center justify-between text-xs">
                    <label class="flex items-center space-x-2 cursor-pointer text-[#5F6368]">
                        <input v-model="form.remember" type="checkbox" class="rounded border-[#DADCE0] bg-white text-[#1A73E8] focus:ring-0" />
                        <span>Remember credentials</span>
                    </label>
                    <a href="#" class="text-[#1A73E8] hover:underline font-medium">Forgot password?</a>
                </div>

                <button 
                    type="submit" 
                    :disabled="form.processing"
                    class="w-full rounded-full bg-[#1A73E8] py-3 text-sm font-medium text-white shadow-sm transition-all hover:bg-[#1557B0] active:scale-[0.98] disabled:opacity-50 mt-4"
                >
                    <span v-if="form.processing">Authenticating...</span>
                    <span v-else>Sign In to Portal <i class="fa-solid fa-arrow-right ml-1"></i></span>
                </button>
            </form>
        </div>
    </div>
</template>
