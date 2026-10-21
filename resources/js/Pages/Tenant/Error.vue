<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    message: String,
});
</script>

<template>
    <div class="flex min-h-screen items-center justify-center bg-[#F8F9FA] px-4 text-[#202124] selection:bg-[#E8F0FE] selection:text-[#1A73E8]">
        <div class="google-card max-w-md w-full rounded-2xl border border-[#DADCE0] bg-white p-8 text-center shadow-sm space-y-6">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-[#FCE8E6] text-3xl border border-[#FAD2CF] text-[#C5221F]">
                <i class="fa-solid fa-triangle-exclamation text-2xl"></i>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-[#202124]">Notice / Access Information</h1>
            <p class="text-sm text-[#5F6368] leading-relaxed">{{ message || 'You do not have permission to view this page or your session expired.' }}</p>
            <div class="pt-2">
                <Link href="/" class="inline-flex items-center gap-1.5 rounded-full bg-[#1A73E8] px-6 py-2.5 text-sm font-medium text-white hover:bg-[#1557B0] transition-all shadow-sm">
                    <span>Return to Portal Home</span> <i class="fa-solid fa-arrow-right"></i>
                </Link>
            </div>
        </div>
    </div>
</template>
