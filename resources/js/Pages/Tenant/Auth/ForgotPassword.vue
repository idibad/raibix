<script setup>
import { computed } from 'vue';
import { useForm, Link, usePage } from '@inertiajs/vue3';

const props = defineProps({
    tenant: Object,
    status: String,
});

const page = usePage();

const logoBgStyle = computed(() => {
    const bgMode = page.props.tenant?.logo_bg || props.tenant?.logo_bg || 'dark';
    if (bgMode === 'brand') {
        return {
            backgroundColor: page.props.tenant?.cover_color || props.tenant?.cover_color || '#1A73E8',
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

const form = useForm({
    email: '',
});

const submit = () => {
    form.post('/forgot-password');
};
</script>

<template>
    <div class="relative flex min-h-screen items-center justify-center overflow-hidden bg-[#F8F9FA] px-4 text-[#202124] selection:bg-[#E8F0FE] selection:text-[#1A73E8]">
        <div class="google-card z-10 w-full max-w-md rounded-2xl border border-[#DADCE0] bg-white p-8 shadow-sm">
            <!-- Header -->
            <div class="text-center">
                <div 
                    v-if="$page.props.tenant?.logo || tenant?.logo" 
                    class="mx-auto flex h-20 min-w-[80px] max-w-[220px] items-center justify-center overflow-hidden rounded-2xl p-2.5 border shadow-sm transition-all"
                    :style="logoBgStyle"
                >
                    <img 
                        :src="$page.props.tenant?.logo || tenant?.logo" 
                        :alt="tenant?.name || 'School Logo'" 
                        class="max-h-full max-w-full w-auto h-auto object-contain filter drop-shadow-[0_1px_3px_rgba(0,0,0,0.25)]" 
                    />
                </div>
                <div v-else class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-[#E8F0FE] font-bold text-2xl text-[#1A73E8] border border-[#D2E3FC] shadow-2xs">
                    <i class="fa-solid fa-key"></i>
                </div>
                <h1 class="mt-4 text-2xl font-bold tracking-tight text-[#202124]">
                    Password Assistance
                </h1>
                <p class="mt-1 text-sm text-[#5F6368]">
                    Enter your registered email address to receive a secure password reset link.
                </p>
            </div>

            <!-- Status Alert -->
            <div v-if="status" class="mt-5 rounded-xl bg-[#E6F4EA] border border-[#CEEAD6] p-4 text-xs font-semibold text-[#137333] flex items-start gap-2.5">
                <i class="fa-solid fa-circle-check text-sm mt-0.5 shrink-0"></i>
                <span>{{ status }}</span>
            </div>

            <form @submit.prevent="submit" class="mt-6 space-y-4">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-[#5F6368] mb-1.5">Registered Email Address</label>
                    <input 
                        v-model="form.email"
                        type="email" 
                        required 
                        placeholder="Registered Email Address"
                        class="w-full rounded-lg border border-[#DADCE0] bg-white px-4 py-3 text-sm text-[#202124] placeholder-[#5F6368] focus:border-[#1A73E8] focus:outline-none focus:ring-2 focus:ring-[#1A73E8]/20 transition-all"
                    />
                </div>

                <div v-if="form.errors.email" class="rounded-lg bg-[#FCE8E6] border border-[#FAD2CF] p-3 text-xs text-[#C5221F]">
                    {{ form.errors.email }}
                </div>

                <button 
                    type="submit" 
                    :disabled="form.processing"
                    class="w-full rounded-full bg-[#1A73E8] py-3 text-sm font-medium text-white shadow-sm transition-all hover:bg-[#1557B0] active:scale-[0.98] disabled:opacity-50 mt-4 cursor-pointer"
                >
                    <span v-if="form.processing">Dispatching Reset Link...</span>
                    <span v-else>Send Password Reset Link <i class="fa-solid fa-paper-plane ml-1"></i></span>
                </button>

                <div class="text-center pt-2">
                    <Link :href="route('tenant.login')" class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#1A73E8] hover:underline">
                        <i class="fa-solid fa-arrow-left text-2xs"></i>
                        <span>Return to Sign In</span>
                    </Link>
                </div>
            </form>
        </div>
    </div>
</template>
