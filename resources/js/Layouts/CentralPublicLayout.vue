<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const props = defineProps({
    title: {
        type: String,
        default: 'Legal & Compliance'
    },
    subtitle: {
        type: String,
        default: ''
    },
    badge: {
        type: String,
        default: 'Official Document'
    },
    lastUpdated: {
        type: String,
        default: 'October 2026'
    }
});

const page = usePage();
const currentPath = computed(() => page.url.split('?')[0]);
</script>

<template>
    <div class="min-h-screen bg-[#FDFDFE] text-[#111318] font-['Poppins'] antialiased selection:bg-[#8B5CF6]/20 selection:text-[#2F6BFF] flex flex-col justify-between">
        
        <!-- ===================================================================
             TOP NAVIGATION BAR
             =================================================================== -->
        <header class="sticky top-0 z-50 w-full bg-white/95 backdrop-blur-md border-b border-[#E7E9EE]">
            <div class="mx-auto flex h-16 sm:h-18 max-w-[1240px] items-center justify-between px-6 sm:px-10">
                
                <!-- Brand Logo -->
                <div class="flex items-center gap-6">
                    <Link href="/" class="flex items-center gap-2 group" aria-label="Raibix Home">
                        <img 
                            src="/images/raibix.png" 
                            alt="Raibix Logo" 
                            class="h-6 sm:h-7 w-auto object-contain transition-transform duration-200 group-hover:scale-102"
                        />
                    </Link>

                    <!-- Primary Nav Links -->
                    <nav class="hidden md:flex items-center gap-5 text-xs font-semibold text-[#5B6270]">
                        <Link 
                            href="/security" 
                            class="transition-colors hover:text-[#111318]"
                            :class="{ 'text-[#2F6BFF] font-bold': currentPath.startsWith('/security') }"
                        >
                            Security Architecture
                        </Link>
                        <Link 
                            href="/privacy" 
                            class="transition-colors hover:text-[#111318]"
                            :class="{ 'text-[#2F6BFF] font-bold': currentPath.startsWith('/privacy') }"
                        >
                            Privacy Policy
                        </Link>
                        <Link 
                            href="/terms" 
                            class="transition-colors hover:text-[#111318]"
                            :class="{ 'text-[#2F6BFF] font-bold': currentPath.startsWith('/terms') }"
                        >
                            Terms of Service
                        </Link>
                    </nav>
                </div>

                <!-- Right Actions -->
                <div class="flex items-center gap-3 sm:gap-4">
                    <Link 
                        href="/super-admin/login" 
                        class="text-xs font-semibold tracking-tight text-[#5B6270] hover:text-[#111318] transition-colors"
                    >
                        Sign In
                    </Link>
                    <Link 
                        href="/checkout/essential" 
                        class="group inline-flex items-center gap-2 rounded-full bg-[#111318] hover:bg-[#2F6BFF] px-4 sm:px-5 py-2 sm:py-2.5 text-xs font-semibold tracking-tight text-white transition-all duration-200 cursor-pointer shadow-xs"
                    >
                        <span>Get Started</span>
                        <span class="raibix-dot h-1.5 w-1.5 transition-transform group-hover:scale-125"></span>
                    </Link>
                </div>
            </div>
        </header>

        <!-- ===================================================================
             DOCUMENT HEADER BANNER
             =================================================================== -->
        <section class="border-b border-[#E7E9EE] bg-white pt-10 sm:pt-14 pb-10 sm:pb-12 text-left">
            <div class="mx-auto max-w-[1240px] px-6 sm:px-10">
                <!-- Breadcrumbs & Document Switcher -->
                <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
                    <Link 
                        href="/" 
                        class="inline-flex items-center gap-2 text-xs font-semibold text-[#5B6270] hover:text-[#111318] transition-colors"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        <span>Back to Platform Overview</span>
                    </Link>

                    <!-- Document Quick Switcher Tabs -->
                    <div class="inline-flex items-center rounded-full bg-[#F3F4F6] p-1 text-[11px] font-semibold text-[#5B6270] border border-[#E7E9EE]">
                        <Link 
                            href="/privacy" 
                            class="px-3.5 py-1 rounded-full transition-all"
                            :class="currentPath.startsWith('/privacy') ? 'bg-white text-[#111318] shadow-xs font-bold' : 'hover:text-[#111318]'"
                        >
                            Privacy Policy
                        </Link>
                        <Link 
                            href="/terms" 
                            class="px-3.5 py-1 rounded-full transition-all"
                            :class="currentPath.startsWith('/terms') ? 'bg-white text-[#111318] shadow-xs font-bold' : 'hover:text-[#111318]'"
                        >
                            Terms of Service
                        </Link>
                        <Link 
                            href="/security" 
                            class="px-3.5 py-1 rounded-full transition-all"
                            :class="currentPath.startsWith('/security') ? 'bg-white text-[#111318] shadow-xs font-bold' : 'hover:text-[#111318]'"
                        >
                            Security & Privacy
                        </Link>
                    </div>
                </div>

                <div class="max-w-3xl space-y-3">
                    <div class="inline-flex items-center gap-2 rounded-full border border-[#E7E9EE] bg-[#FAFAFC] px-3 py-1 text-[11px] font-semibold uppercase tracking-wider text-[#5B6270]">
                        <span class="h-1.5 w-1.5 rounded-full bg-[#2F6BFF]"></span>
                        <span>{{ badge }}</span>
                    </div>

                    <h1 class="text-3xl sm:text-4xl md:text-5xl font-black tracking-tight text-[#111318] leading-[1.1]">
                        {{ title }}
                    </h1>

                    <p v-if="subtitle" class="text-sm sm:text-base text-[#5B6270] leading-relaxed font-normal pt-1">
                        {{ subtitle }}
                    </p>

                    <div class="pt-2 flex items-center gap-4 text-xs text-[#9CA3AF]">
                        <span>Effective & Last Revised: <strong class="text-[#5B6270]">{{ lastUpdated }}</strong></span>
                        <span>&bull;</span>
                        <span>Multi-Tenant SaaS Standard</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===================================================================
             MAIN CONTENT SLOT
             =================================================================== -->
        <main class="flex-1 py-10 sm:py-16">
            <div class="mx-auto max-w-[1240px] px-6 sm:px-10">
                <slot />
            </div>
        </main>

        <!-- ===================================================================
             FULL FOOTER
             =================================================================== -->
        <footer id="footer" class="border-t border-[#E7E9EE] bg-white pt-14 pb-12 text-left mt-auto">
            <div class="mx-auto max-w-[1240px] px-6 sm:px-10">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-10 pb-12 border-b border-[#E7E9EE]">
                    
                    <!-- Brand Column -->
                    <div class="md:col-span-5 space-y-4">
                        <Link href="/" aria-label="Raibix Home">
                            <img 
                                src="/images/raibix.png" 
                                alt="Raibix Logo" 
                                class="h-6 w-auto object-contain"
                            />
                        </Link>
                        <p class="max-w-sm text-xs sm:text-sm text-[#5B6270] leading-relaxed">
                            The unified school platform for modern campuses. Dedicated database isolation, automated 1-Bill national clearing, and role-based portals.
                        </p>
                    </div>

                    <!-- Navigation Links -->
                    <div class="md:col-span-3 space-y-3">
                        <div class="text-xs font-bold uppercase tracking-wider text-[#111318]">
                            Platform
                        </div>
                        <ul class="space-y-2 text-xs text-[#5B6270]">
                            <li><Link href="/#features" class="hover:text-[#111318] transition-colors">Features & Roles</Link></li>
                            <li><Link href="/#pricing" class="hover:text-[#111318] transition-colors">Campus Plans</Link></li>
                            <li><Link href="/#contact" class="hover:text-[#111318] transition-colors">Contact & FAQ</Link></li>
                            <li><Link href="/checkout/essential" class="hover:text-[#111318] transition-colors">Start Free Onboarding</Link></li>
                        </ul>
                    </div>

                    <!-- Portal & Support Links -->
                    <div class="md:col-span-4 space-y-3">
                        <div class="text-xs font-bold uppercase tracking-wider text-[#111318]">
                            Access & Support
                        </div>
                        <ul class="space-y-2 text-xs text-[#5B6270]">
                            <li><Link href="/super-admin/login" class="font-semibold text-[#111318] hover:text-[#2F6BFF]">Super Admin Console</Link></li>
                            <li>Direct Inquiries: <a href="mailto:admin@lmscloud.pk" class="hover:text-[#111318]">admin@lmscloud.pk</a></li>
                            <li>Phone: <a href="tel:+923001234567" class="hover:text-[#111318]">+92 300 1234567</a></li>
                            <li>Operations: Islamabad, Pakistan</li>
                        </ul>
                    </div>

                </div>

                <!-- Copyright & Legal -->
                <div class="pt-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-[#6B7280]">
                    <div>
                        © 2026 Raibix Platform. All rights reserved.
                    </div>
                    <div class="flex items-center gap-6">
                        <Link 
                            href="/privacy" 
                            class="transition-colors hover:text-[#111318]"
                            :class="{ 'text-[#111318] font-bold underline': currentPath.startsWith('/privacy') }"
                        >
                            Privacy Policy
                        </Link>
                        <Link 
                            href="/terms" 
                            class="transition-colors hover:text-[#111318]"
                            :class="{ 'text-[#111318] font-bold underline': currentPath.startsWith('/terms') }"
                        >
                            Terms of Service
                        </Link>
                        <Link 
                            href="/security" 
                            class="transition-colors hover:text-[#111318]"
                            :class="{ 'text-[#111318] font-bold underline': currentPath.startsWith('/security') }"
                        >
                            Security & Privacy
                        </Link>
                    </div>
                </div>
            </div>
        </footer>

    </div>
</template>
