<script setup>
import { computed } from 'vue';
import { useForm, usePage, Link } from '@inertiajs/vue3';

const props = defineProps({
    tenant: Object,
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const page = usePage();

const logoBgStyle = computed(() => {
    const bgMode = page.props.tenant?.logo_bg || props.tenant?.logo_bg || 'auto';
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
                <!-- If logo uploaded: show ONLY the uploaded logo image, no redundant text name -->
                <div 
                    v-if="$page.props.tenant?.logo || tenant?.logo" 
                    class="mx-auto flex h-20 items-center justify-center mb-2"
                >
                    <img 
                        :src="$page.props.tenant?.logo || tenant?.logo" 
                        :alt="tenant?.name || 'School Logo'" 
                        class="max-h-20 max-w-[260px] w-auto h-auto object-contain" 
                    />
                </div>
                <!-- If NO logo uploaded: show graduation cap icon + school name -->
                <div v-else class="mx-auto flex flex-col items-center">
                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-[#E8F0FE] font-bold text-2xl text-[#1A73E8] border border-[#D2E3FC] shadow-2xs mb-2">
                        <i class="fa-solid fa-graduation-cap"></i>
                    </div>
                    <h1 class="text-2xl font-bold tracking-tight text-[#202124]">
                        {{ $page.props.tenant?.name || tenant?.name || $page.props.platformName || 'School Portal' }}
                    </h1>
                </div>
                <p class="mt-1 text-xs font-semibold uppercase tracking-wider text-[#5F6368]">
                    School Unified Portal Access
                </p>
            </div>

            <!-- Flash Success Message -->
            <div v-if="$page.props.flash?.success" class="mt-4 rounded-xl bg-[#E6F4EA] border border-[#CEEAD6] p-3.5 text-xs font-semibold text-[#137333] flex items-center gap-2">
                <i class="fa-solid fa-circle-check text-sm shrink-0"></i>
                <span>{{ $page.props.flash.success }}</span>
            </div>

            <form @submit.prevent="submit" class="mt-6 space-y-4">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-[#5F6368] mb-1.5">Email / Portal ID</label>
                    <input 
                        v-model="form.email"
                        type="email" 
                        required 
                        placeholder="Email Address"
                        class="w-full rounded-lg border border-[#DADCE0] bg-white px-4 py-3 text-sm text-[#202124] placeholder-[#5F6368] focus:border-[#1A73E8] focus:outline-none focus:ring-2 focus:ring-[#1A73E8]/20 transition-all"
                    />
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-[#5F6368] mb-1.5">Password</label>
                    <input 
                        v-model="form.password"
                        type="password" 
                        required 
                        placeholder="Password"
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
                    <Link href="/forgot-password" class="text-[#1A73E8] hover:underline font-medium">Forgot password?</Link>
                </div>

                <button 
                    type="submit" 
                    :disabled="form.processing"
                    class="w-full rounded-full bg-[#1A73E8] py-3 text-sm font-medium text-white shadow-sm transition-all hover:bg-[#1557B0] active:scale-[0.98] disabled:opacity-50 mt-4 cursor-pointer"
                >
                    <span v-if="form.processing">Authenticating...</span>
                    <span v-else>Sign In to Portal <i class="fa-solid fa-arrow-right ml-1"></i></span>
                </button>
            </form>

            <div v-if="$page.props.tenant?.phone || $page.props.tenant?.email" class="mt-6 pt-4 border-t border-[#DADCE0] text-center text-2xs text-[#5F6368]">
                <span>Need portal help? Contact office: </span>
                <a v-if="$page.props.tenant?.phone" :href="'tel:' + $page.props.tenant.phone" class="font-semibold text-[#202124] hover:underline">{{ $page.props.tenant.phone }}</a>
                <span v-if="$page.props.tenant?.phone && $page.props.tenant?.email"> • </span>
                <a v-if="$page.props.tenant?.email" :href="'mailto:' + $page.props.tenant.email" class="text-[#1A73E8] hover:underline font-semibold">{{ $page.props.tenant.email }}</a>
            </div>
        </div>
    </div>
</template>
