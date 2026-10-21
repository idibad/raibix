<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
    plans: {
        type: Array,
        default: () => []
    },
    stats: {
        type: Object,
        default: () => ({ total_schools: 48 })
    }
});

// =============================================================================
// THEME STATE (LIGHT / DARK)
// =============================================================================
const isDark = ref(false);

const toggleTheme = () => {
    isDark.value = !isDark.value;
    if (isDark.value) {
        document.documentElement.classList.add('dark');
        document.documentElement.setAttribute('data-theme', 'dark');
        localStorage.setItem('raibix_theme', 'dark');
    } else {
        document.documentElement.classList.remove('dark');
        document.documentElement.setAttribute('data-theme', 'light');
        localStorage.setItem('raibix_theme', 'light');
    }
};

onMounted(() => {
    isDark.value = document.documentElement.classList.contains('dark') || 
                   document.documentElement.getAttribute('data-theme') === 'dark';
});

// =============================================================================
// PRICING SYSTEM & ACTIVE CARD LOGIC
// =============================================================================
const isAnnual = ref(false);
const activePlanIndex = ref(1); // Default to Essential (index 1)

const defaultPlans = [
    {
        id: 1,
        name: 'Starter Plan',
        code: 'starter',
        max_students: 300,
        price_monthly: 20000,
        price_yearly: 192000,
        features: [
            'Core SIS & Student Dossiers',
            'Biometric & RFID Gate Clocking',
            'Automated Fee Voucher Generation',
            'Role-Isolated Portals (Web & Mobile)'
        ]
    },
    {
        id: 2,
        name: 'Essential Plan',
        code: 'essential',
        max_students: 1000,
        price_monthly: 40000,
        price_yearly: 384000,
        features: [
            'Everything in Starter',
            '1-Bill Automated National Clearing',
            'AI Face Recognition Attendance',
            'Full Examination & Gradebook Engine',
            'Parent SMS & WhatsApp Broadcasts',
            'Autonomous Campus Subdomain'
        ]
    },
    {
        id: 3,
        name: 'Signature Plan',
        code: 'signature',
        max_students: 5000,
        price_monthly: 50000,
        price_yearly: 480000,
        features: [
            'Everything in Essential',
            'All 16 Extended ERP Modules',
            'Full Academic Ledger & QR Transcripts',
            'Custom Domain & Dedicated TLS Routing',
            'Direct Dedicated Account Manager',
            '24/7 Priority Emergency Support'
        ]
    }
];

const plansList = computed(() => {
    if (props.plans && props.plans.length >= 3) {
        return props.plans;
    }
    return defaultPlans;
});

const getPlanPrice = (plan) => {
    if (!plan) return 0;
    if (isAnnual.value) {
        return plan.price_yearly || (plan.price_monthly ? Math.round(plan.price_monthly * 12 * 0.8) : 0);
    }
    return plan.price_monthly || 0;
};

// =============================================================================
// HERO ORB POINTER & SCROLL PARALLAX
// =============================================================================
const heroContainerRef = ref(null);
const orbPointerOffset = ref({ x: 0, y: 0 });
const orbTargetOffset = ref({ x: 0, y: 0 });
const orbScrollTranslate = ref(0);
let heroRafId = null;

const handleHeroPointerMove = (e) => {
    if (!heroContainerRef.value) return;
    const rect = heroContainerRef.value.getBoundingClientRect();
    const relX = (e.clientX - rect.left) - rect.width / 2;
    const relY = (e.clientY - rect.top) - rect.height / 2;
    
    // Damped bounded range: max +/- 18px
    orbTargetOffset.value = {
        x: Math.max(-18, Math.min(18, relX * 0.04)),
        y: Math.max(-18, Math.min(18, relY * 0.04))
    };
};

const handleHeroPointerLeave = () => {
    orbTargetOffset.value = { x: 0, y: 0 };
};

const updateHeroPhysics = () => {
    // Lerp with standard ease factor
    orbPointerOffset.value.x += (orbTargetOffset.value.x - orbPointerOffset.value.x) * 0.1;
    orbPointerOffset.value.y += (orbTargetOffset.value.y - orbPointerOffset.value.y) * 0.1;
    heroRafId = requestAnimationFrame(updateHeroPhysics);
};

const handleScroll = () => {
    const scrollY = window.scrollY;
    // Parallax on hero orb: subtle downward translate + slight scale
    if (scrollY < 900) {
        orbScrollTranslate.value = scrollY * 0.15;
    }
};

// =============================================================================
// FLOATING PILL NAV & INTERSECTION OBSERVER
// =============================================================================
const activeSection = ref('hero');
const isFooterVisible = ref(false);

const navItems = [
    { id: 'hero', label: 'Overview' },
    { id: 'story', label: 'Story' },
    { id: 'roles', label: 'Roles' },
    { id: 'core', label: 'The Core' },
    { id: 'pricing', label: 'Pricing' }
];

let sectionObserver = null;
let footerObserver = null;

// =============================================================================
// LIVE SUBDOMAIN CHECKER (SECTION 5 UTILITY)
// =============================================================================
const subdomainQuery = ref('');
const subdomainStatus = ref(null);
const isSearchingSubdomain = ref(false);
let searchDebounce = null;

const checkSubdomainAvailability = () => {
    const q = subdomainQuery.value.trim().toLowerCase().replace(/[^a-z0-9-]/g, '');
    subdomainQuery.value = q;
    
    if (q.length < 2) {
        subdomainStatus.value = null;
        isSearchingSubdomain.value = false;
        return;
    }

    clearTimeout(searchDebounce);
    isSearchingSubdomain.value = true;
    searchDebounce = setTimeout(async () => {
        try {
            const res = await axios.get('/api/check-subdomain', { params: { q } });
            const list = res.data.results || [];
            const isTaken = list.some(item => {
                const dom = (item.domain || '').toLowerCase();
                return dom.startsWith(q + '.') || dom === q;
            });
            subdomainStatus.value = isTaken ? 'taken' : 'available';
        } catch (e) {
            subdomainStatus.value = null;
        } finally {
            isSearchingSubdomain.value = false;
        }
    }, 350);
};

// =============================================================================
// FAQS ACCORDION
// =============================================================================
const activeFaq = ref(0);
const faqs = [
    {
        q: 'How does dedicated multi-tenant isolation protect our school?',
        a: 'Each campus receives an autonomous, physically isolated database container. Unlike shared tables, no student, financial, or staff record is ever co-mingled with another school, eliminating data leaks by architectural design.'
    },
    {
        q: 'Can we connect our existing school domain and custom brand colours?',
        a: 'Yes. Every campus receives an automatic [school].raibix.cloud subdomain on signup, and Essential or Signature tiers can map their existing custom domain (e.g. portal.yourschool.edu.pk) with automated zero-configuration SSL.'
    },
    {
        q: 'How does 1-Bill national clearing settle fee vouchers automatically?',
        a: 'Every generated student fee challan carries a standardized 1-Bill consumer ID. When guardians pay via any Pakistani mobile banking app, ATM, or OTC outlet, payment reconciles into your school ledger within 3 seconds.'
    },
    {
        q: 'What is the onboarding process and manual payment verification?',
        a: 'After selecting your plan, transfer the subscription fee to our verified bank account and upload your receipt screenshot. Our platform operations team verifies the transaction, activating your campus within 2 hours.'
    },
    {
        q: 'Can staff and parents access the system from their smartphones?',
        a: 'Yes. Raibix provides responsive web portals and native role-tailored interfaces for Administrators, Teachers, Parents, and Students, complete with instant SMS and WhatsApp notifications.'
    }
];

const toggleFaq = (index) => {
    activeFaq.value = activeFaq.value === index ? -1 : index;
};

// =============================================================================
// CONTACT FORM
// =============================================================================
const contactForm = ref({
    name: '',
    email: '',
    message: ''
});
const contactErrors = ref({});
const contactSubmitting = ref(false);
const contactSuccess = ref(false);

const submitContact = () => {
    contactErrors.value = {};
    if (!contactForm.value.name.trim()) {
        contactErrors.value.name = 'Please provide your full name.';
    }
    if (!contactForm.value.email.trim() || !contactForm.value.email.includes('@')) {
        contactErrors.value.email = 'Please provide a valid official email address.';
    }
    if (!contactForm.value.message.trim()) {
        contactErrors.value.message = 'Please provide a message or inquiry details.';
    }

    if (Object.keys(contactErrors.value).length > 0) return;

    contactSubmitting.value = true;
    setTimeout(() => {
        contactSubmitting.value = false;
        contactSuccess.value = true;
        contactForm.value = { name: '', email: '', message: '' };
        setTimeout(() => {
            contactSuccess.value = false;
        }, 8000);
    }, 1200);
};

// =============================================================================
// LIFECYCLE HOOKS & OBSERVERS
// =============================================================================
onMounted(() => {
    heroRafId = requestAnimationFrame(updateHeroPhysics);
    window.addEventListener('scroll', handleScroll, { passive: true });

    // Section Observer for bottom floating nav
    const sectionIds = ['hero', 'story', 'roles', 'core', 'pricing'];
    const sections = sectionIds.map(id => document.getElementById(id)).filter(Boolean);

    sectionObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                activeSection.value = entry.target.id;
            }
        });
    }, { rootMargin: '-40% 0px -40% 0px' });

    sections.forEach(s => sectionObserver.observe(s));

    // Footer Observer to hide floating pill nav
    const footerEl = document.getElementById('footer');
    if (footerEl) {
        footerObserver = new IntersectionObserver(([entry]) => {
            isFooterVisible.value = entry.isIntersecting;
        }, { threshold: 0.15 });
        footerObserver.observe(footerEl);
    }
});

onUnmounted(() => {
    if (heroRafId) cancelAnimationFrame(heroRafId);
    window.removeEventListener('scroll', handleScroll);
    if (sectionObserver) sectionObserver.disconnect();
    if (footerObserver) footerObserver.disconnect();
});
</script>

<template>
    <div class="min-h-screen bg-[var(--color-page)] text-[var(--color-ink)] selection:bg-[#8B5CF6]/20 selection:text-[#2F6BFF] font-sans antialiased">
        
        <!-- ===================================================================
             STICKY TOP NAVBAR
             Translucent blur, logo with dot left, Get Started pill right
             =================================================================== -->
        <header class="sticky top-0 z-50 w-full border-b border-[var(--color-line)] bg-[var(--color-page)]/80 backdrop-blur-md transition-colors duration-200">
            <div class="mx-auto flex h-16 max-w-[1120px] items-center justify-between px-6">
                <!-- Brand Logo: Wordmark with Intelligence Core Dot -->
                <Link href="/" class="group flex items-center gap-2.5 text-xl font-extrabold tracking-tight text-[var(--color-ink)]">
                    <span class="tracking-tight font-extrabold">Raibix</span>
                    <span class="raibix-dot h-2.5 w-2.5 transition-transform duration-300 group-hover:scale-125"></span>
                </Link>

                <!-- Right Actions: Dark mode toggle & Get Started Pill -->
                <div class="flex items-center gap-4">
                    <!-- Light / Dark Mode Toggle -->
                    <button 
                        @click="toggleTheme" 
                        type="button" 
                        class="flex h-9 w-9 items-center justify-center rounded-full border border-[var(--color-line)] bg-[var(--color-page)] text-[var(--color-slate)] transition-colors hover:text-[var(--color-ink)] cursor-pointer"
                        :title="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
                        :aria-label="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
                    >
                        <!-- Sun Icon -->
                        <svg v-if="isDark" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <!-- Moon Icon -->
                        <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                    </button>

                    <!-- Get Started Pill -->
                    <Link 
                        href="/#pricing" 
                        class="raibix-btn-hover hidden sm:inline-flex items-center gap-2 rounded-full bg-[var(--color-blue)] px-5 py-2 text-xs font-semibold text-white shadow-xs transition-colors hover:bg-[var(--color-blue-hover)] cursor-pointer"
                    >
                        <span>Get Started</span>
                        <span class="h-1.5 w-1.5 rounded-full bg-white/90"></span>
                    </Link>
                </div>
            </div>
        </header>

        <!-- ===================================================================
             STICKY FLOATING BOTTOM PILL NAV
             Glass pill, 5 links, active link highlights, hides in footer area
             =================================================================== -->
        <nav 
            :class="[
                'fixed bottom-6 left-1/2 -translate-x-1/2 z-40 transition-all duration-300 pointer-events-auto',
                isFooterVisible ? 'opacity-0 translate-y-6 pointer-events-none' : 'opacity-100 translate-y-0'
            ]"
            aria-label="Page navigation"
        >
            <div class="flex items-center gap-1 rounded-full border border-[var(--color-line)] bg-[var(--color-page)]/85 px-3 py-1.5 shadow-xl backdrop-blur-lg">
                <a 
                    v-for="item in navItems" 
                    :key="item.id" 
                    :href="'#' + item.id"
                    :class="[
                        'relative rounded-full px-3 py-1 text-xs font-semibold transition-all duration-200 cursor-pointer',
                        activeSection === item.id 
                            ? 'text-[var(--color-blue)] font-bold' 
                            : 'text-[var(--color-slate)] hover:text-[var(--color-ink)]'
                    ]"
                >
                    <span>{{ item.label }}</span>
                    <span 
                        v-if="activeSection === item.id" 
                        class="raibix-dot absolute -bottom-0.5 left-1/2 -translate-x-1/2 h-1 w-1"
                    ></span>
                </a>
            </div>
        </nav>

        <main>
            <!-- ===============================================================
                 SECTION 1: HERO
                 Centred bold headline, one-line sub-heading, dome gradient orb
                 =============================================================== -->
            <section 
                id="hero" 
                ref="heroContainerRef"
                @mousemove="handleHeroPointerMove"
                @mouseleave="handleHeroPointerLeave"
                class="relative min-h-[85vh] flex flex-col items-center justify-start overflow-hidden pt-24 sm:pt-32 pb-48 text-center"
            >
                <!-- Hero Content (Above Orb) -->
                <div class="relative z-10 mx-auto max-w-[1120px] px-6 flex flex-col items-center">
                    
                    <!-- Eyebrow Badge -->
                    <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-[var(--color-line)] bg-[var(--color-mist)] px-3.5 py-1 text-[13px] font-bold tracking-[0.08em] uppercase text-[var(--color-blue)]">
                        <span class="raibix-dot h-2 w-2"></span>
                        <span>School Operating System</span>
                    </div>

                    <!-- Display Headline (64-80 / 1.04 / 800 / -0.03em, <= 10 words) -->
                    <h1 class="mx-auto max-w-4xl text-4xl sm:text-6xl md:text-7xl lg:text-[76px] font-extrabold tracking-[-0.03em] leading-[1.04] text-[var(--color-ink)]">
                        School management, orchestrated by one core.
                    </h1>

                    <!-- One-line sub-heading (Measure <= 560px, slate) -->
                    <p class="mt-6 max-w-[560px] text-base sm:text-lg leading-[1.6] text-[var(--color-slate)]">
                        Autonomous campus databases, national 1-Bill clearing, and role-native portals for modern institutions.
                    </p>

                    <!-- CTAs -->
                    <div class="mt-8 flex flex-wrap items-center justify-center gap-4">
                        <Link 
                            href="/#pricing" 
                            class="raibix-btn-hover inline-flex items-center gap-2.5 rounded-full bg-[var(--color-blue)] px-7 py-3 text-sm font-semibold text-white shadow-lg transition-colors hover:bg-[var(--color-blue-hover)] cursor-pointer"
                        >
                            <span>Explore Plans</span>
                            <span class="h-1.5 w-1.5 rounded-full bg-white/90"></span>
                        </Link>
                        <a 
                            href="#roles" 
                            class="raibix-btn-hover inline-flex items-center gap-2 rounded-full border border-[var(--color-line)] bg-[var(--color-page)] px-6 py-3 text-sm font-semibold text-[var(--color-ink)] transition-colors hover:bg-[var(--color-mist)] cursor-pointer"
                        >
                            <span>View Roles</span>
                        </a>
                    </div>
                </div>

                <!-- HERO ORB (Rising from bottom edge as a dome, partly cropped behind text) -->
                <div 
                    class="pointer-events-none absolute bottom-[-280px] sm:bottom-[-340px] md:bottom-[-420px] left-1/2 z-0 -translate-x-1/2 transition-transform duration-300 ease-out"
                    :style="{
                        transform: `translate(calc(-50% + ${orbPointerOffset.x}px), ${orbPointerOffset.y + orbScrollTranslate}px)`
                    }"
                >
                    <div class="raibix-orb-large animate-orb-breathing h-[640px] w-[640px] sm:h-[820px] sm:w-[820px] md:h-[980px] md:w-[980px]"></div>
                </div>
            </section>

            <!-- ===============================================================
                 SECTION 2: STORY + GRAPHICS (2/3 + 1/3)
                 Equal height, aligned edges, real photography with brand overlay
                 =============================================================== -->
            <section id="story" class="py-20 md:py-28 bg-[var(--color-page)]">
                <div class="mx-auto max-w-[1120px] px-6">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-stretch">
                        
                        <!-- Left Card (2/3): Photography with smooth violet-gradient overlay -->
                        <div class="lg:col-span-8 relative min-h-[420px] rounded-2xl overflow-hidden border border-[var(--color-line)] flex flex-col justify-end p-8 sm:p-12 group">
                            <!-- High quality authentic photo -->
                            <img 
                                src="/images/campus_student_hero.png" 
                                alt="Modern campus administrator with student records" 
                                class="absolute inset-0 h-full w-full object-cover object-center transition-transform duration-700 ease-out group-hover:scale-105"
                                loading="lazy"
                            />
                            <!-- Smooth gradient overlay fading from left (brand violet / dark ink -> transparent) -->
                            <div class="absolute inset-0 bg-gradient-to-r from-[#0B0D14]/90 via-[#0B0D14]/70 to-transparent"></div>

                            <!-- Readable Content on Overlay -->
                            <div class="relative z-10 max-w-lg space-y-4">
                                <div class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-semibold text-white/90 backdrop-blur-sm border border-white/15">
                                    <span class="raibix-dot h-1.5 w-1.5"></span>
                                    <span>Database Boundary</span>
                                </div>
                                <h2 class="text-2xl sm:text-3xl md:text-4xl font-extrabold tracking-tight text-white leading-tight">
                                    Built for schools that refuse data compromise.
                                </h2>
                                <p class="text-sm sm:text-base text-white/80 leading-relaxed">
                                    Every student record, fee ledger, and classroom interaction stays strictly isolated in your campus database.
                                </p>
                                <div class="pt-2">
                                    <a 
                                        href="#core" 
                                        class="raibix-btn-hover inline-flex items-center gap-2 rounded-full bg-white text-[var(--color-ink)] px-5 py-2.5 text-xs font-bold transition-colors hover:bg-[var(--color-mist)] cursor-pointer"
                                    >
                                        <span>Explore the Architecture</span>
                                        <span class="raibix-dot h-1.5 w-1.5"></span>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Right Narrow Card (1/3): Refined data graphics with dot as live indicator -->
                        <div class="lg:col-span-4 rounded-2xl border border-[var(--color-line)] bg-[var(--color-card-bg)] p-6 sm:p-8 flex flex-col justify-between">
                            <!-- Card Header -->
                            <div>
                                <div class="flex items-center justify-between border-b border-[var(--color-line)] pb-4">
                                    <div class="text-xs font-bold uppercase tracking-[0.08em] text-[var(--color-blue)]">
                                        Live Telemetry
                                    </div>
                                    <div class="flex items-center gap-2 text-xs font-medium text-[var(--color-slate)]">
                                        <span class="raibix-dot h-2 w-2 animate-pulse"></span>
                                        <span>Synchronized</span>
                                    </div>
                                </div>

                                <!-- Key Telemetry Insight Tiles -->
                                <div class="mt-6 space-y-4">
                                    <div class="rounded-xl border border-[var(--color-line)] bg-[var(--color-mist)] p-4">
                                        <div class="text-[11px] font-semibold text-[var(--color-slate)] uppercase tracking-wider">Attendance Sync</div>
                                        <div class="mt-1 flex items-baseline justify-between">
                                            <span class="text-2xl font-extrabold text-[var(--color-ink)]">99.98%</span>
                                            <span class="text-xs font-semibold text-[var(--color-success)]">&uarr; Biometric</span>
                                        </div>
                                    </div>

                                    <div class="rounded-xl border border-[var(--color-line)] bg-[var(--color-mist)] p-4">
                                        <div class="text-[11px] font-semibold text-[var(--color-slate)] uppercase tracking-wider">1-Bill Settlement</div>
                                        <div class="mt-1 flex items-baseline justify-between">
                                            <span class="text-2xl font-extrabold text-[var(--color-ink)]">&lt; 3.0s</span>
                                            <span class="text-xs font-semibold text-[var(--color-blue)]">Automated</span>
                                        </div>
                                    </div>

                                    <div class="rounded-xl border border-[var(--color-line)] bg-[var(--color-mist)] p-4">
                                        <div class="text-[11px] font-semibold text-[var(--color-slate)] uppercase tracking-wider">Cloud Latency</div>
                                        <div class="mt-1 flex items-baseline justify-between">
                                            <span class="text-2xl font-extrabold text-[var(--color-ink)]">28 ms</span>
                                            <span class="text-xs font-semibold text-[var(--color-slate)]">Zero packet loss</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Animated Mini Sparkline Chart -->
                            <div class="mt-6 border-t border-[var(--color-line)] pt-4">
                                <div class="flex items-center justify-between text-xs text-[var(--color-slate)] mb-2">
                                    <span>Weekly Ledger Velocity</span>
                                    <span class="font-mono text-[var(--color-ink)] font-semibold">100% Reconciled</span>
                                </div>
                                <svg class="w-full h-12 overflow-visible" viewBox="0 0 200 40">
                                    <defs>
                                        <linearGradient id="chartGradient" x1="0" y1="0" x2="1" y2="0">
                                            <stop offset="0%" stop-color="#8B5CF6" />
                                            <stop offset="100%" stop-color="#2F6BFF" />
                                        </linearGradient>
                                    </defs>
                                    <path d="M 0 32 Q 25 10 50 24 T 100 18 T 150 8 T 200 14" fill="none" stroke="url(#chartGradient)" stroke-width="2.5" stroke-linecap="round" />
                                    <circle cx="200" cy="14" r="4" fill="#2F6BFF" />
                                </svg>
                            </div>
                        </div>

                    </div>
                </div>
            </section>

            <!-- ===============================================================
                 SECTION 3: INFINITE LOGO MARQUEE
                 Full-width band of "Raibix●" repeated, large, on mist
                 =============================================================== -->
            <section class="py-10 bg-[var(--color-mist)] border-y border-[var(--color-line)] overflow-hidden relative">
                <!-- Edge fade masks -->
                <div class="pointer-events-none absolute inset-y-0 left-0 w-24 sm:w-40 bg-gradient-to-r from-[var(--color-mist)] to-transparent z-10"></div>
                <div class="pointer-events-none absolute inset-y-0 right-0 w-24 sm:w-40 bg-gradient-to-l from-[var(--color-mist)] to-transparent z-10"></div>

                <div class="animate-marquee flex items-center">
                    <!-- Repeated 8 times for ultra-smooth loop -->
                    <div v-for="i in 12" :key="i" class="flex items-center mx-6 sm:mx-10 whitespace-nowrap">
                        <span class="text-3xl sm:text-4xl md:text-5xl font-extrabold tracking-tight text-[var(--color-ink)]">Raibix</span>
                        <span class="raibix-dot mx-5 sm:mx-7 h-4 w-4 sm:h-5 sm:w-5"></span>
                    </div>
                </div>
            </section>

            <!-- ===============================================================
                 SECTION 4: ROLES
                 Quarter orb top-left, Eyebrow + title, 4 tall cards
                 =============================================================== -->
            <section id="roles" class="relative py-28 bg-[var(--color-page)] overflow-hidden">
                <!-- Quarter of the main orb in top-left background (low opacity, slow parallax) -->
                <div class="pointer-events-none absolute -top-40 -left-40 z-0 opacity-25">
                    <div class="raibix-orb-large h-[540px] w-[540px]"></div>
                </div>

                <div class="relative z-10 mx-auto max-w-[1120px] px-6">
                    <!-- Section Header -->
                    <div class="max-w-xl">
                        <div class="text-[13px] font-bold tracking-[0.08em] uppercase text-[var(--color-blue)]">
                            ROLES
                        </div>
                        <h2 class="mt-3 text-3xl sm:text-4xl font-extrabold tracking-tight text-[var(--color-ink)]">
                            Engineered for every campus stakeholder.
                        </h2>
                        <p class="mt-3 text-base text-[var(--color-slate)] leading-relaxed">
                            Distinct, role-tailored workflows that remove operational friction across all departments.
                        </p>
                    </div>

                    <!-- Four Tall Cards: Admin, Teacher, Parent, Student -->
                    <div class="mt-14 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                        
                        <!-- Role 1: Admin -->
                        <div class="raibix-card-hover rounded-2xl border border-[var(--color-line)] bg-[var(--color-card-bg)] p-6 flex flex-col justify-between cursor-default">
                            <div>
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold uppercase tracking-wider text-[var(--color-blue)]">01</span>
                                    <span class="raibix-dot h-2 w-2"></span>
                                </div>
                                <h3 class="mt-4 text-xl font-bold text-[var(--color-ink)]">School Admin</h3>
                                <p class="mt-2 text-sm text-[var(--color-slate)] leading-relaxed">
                                    Autonomous campus oversight and complete database sovereignty.
                                </p>
                            </div>

                            <!-- Role Vignette: Multi-campus switcher & stats -->
                            <div class="mt-8 rounded-xl border border-[var(--color-line)] bg-[var(--color-mist)] p-3.5 space-y-2">
                                <div class="flex items-center justify-between text-xs font-semibold text-[var(--color-ink)]">
                                    <span>Campus Selector</span>
                                    <span class="text-[10px] text-[var(--color-blue)] font-mono">DHA Campus</span>
                                </div>
                                <div class="h-1.5 w-full rounded-full bg-[var(--color-line)] overflow-hidden">
                                    <div class="h-full bg-[var(--color-blue)] rounded-full w-[88%]"></div>
                                </div>
                                <div class="flex justify-between text-[11px] text-[var(--color-slate)]">
                                    <span>Roster Load</span>
                                    <span class="font-mono text-[var(--color-ink)] font-medium">88% Capacity</span>
                                </div>
                            </div>
                        </div>

                        <!-- Role 2: Teacher -->
                        <div class="raibix-card-hover rounded-2xl border border-[var(--color-line)] bg-[var(--color-card-bg)] p-6 flex flex-col justify-between cursor-default">
                            <div>
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold uppercase tracking-wider text-[var(--color-blue)]">02</span>
                                    <span class="raibix-dot h-2 w-2"></span>
                                </div>
                                <h3 class="mt-4 text-xl font-bold text-[var(--color-ink)]">Teacher</h3>
                                <p class="mt-2 text-sm text-[var(--color-slate)] leading-relaxed">
                                    Instant attendance, AI grading assistance, and timetable sync.
                                </p>
                            </div>

                            <!-- Role Vignette: Live attendance roster snippet -->
                            <div class="mt-8 rounded-xl border border-[var(--color-line)] bg-[var(--color-mist)] p-3.5 space-y-2 text-xs">
                                <div class="flex items-center justify-between font-semibold text-[var(--color-ink)]">
                                    <span>Class 8-A Attendance</span>
                                    <span class="text-[10px] text-[var(--color-success)] font-medium">28/30 Present</span>
                                </div>
                                <div class="flex items-center gap-1.5 pt-1">
                                    <span class="h-2 w-2 rounded-full bg-[var(--color-success)]"></span>
                                    <span class="text-[11px] text-[var(--color-slate)]">Biometric Auto-Verified</span>
                                </div>
                            </div>
                        </div>

                        <!-- Role 3: Parent -->
                        <div class="raibix-card-hover rounded-2xl border border-[var(--color-line)] bg-[var(--color-card-bg)] p-6 flex flex-col justify-between cursor-default">
                            <div>
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold uppercase tracking-wider text-[var(--color-blue)]">03</span>
                                    <span class="raibix-dot h-2 w-2"></span>
                                </div>
                                <h3 class="mt-4 text-xl font-bold text-[var(--color-ink)]">Parent</h3>
                                <p class="mt-2 text-sm text-[var(--color-slate)] leading-relaxed">
                                    Standardized 1-Bill vouchers and real-time gate departure alerts.
                                </p>
                            </div>

                            <!-- Role Vignette: 1-Bill Voucher Clearance Badge -->
                            <div class="mt-8 rounded-xl border border-[var(--color-line)] bg-[var(--color-mist)] p-3.5 space-y-2 text-xs">
                                <div class="flex items-center justify-between font-semibold text-[var(--color-ink)]">
                                    <span class="font-mono text-[11px]">1-BILL: 1009-8821</span>
                                    <span class="rounded bg-emerald-500/10 px-1.5 py-0.5 text-[10px] font-bold text-emerald-600">PAID</span>
                                </div>
                                <div class="text-[11px] text-[var(--color-slate)]">
                                    Reconciled via Bank App instant API
                                </div>
                            </div>
                        </div>

                        <!-- Role 4: Student -->
                        <div class="raibix-card-hover rounded-2xl border border-[var(--color-line)] bg-[var(--color-card-bg)] p-6 flex flex-col justify-between cursor-default">
                            <div>
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold uppercase tracking-wider text-[var(--color-blue)]">04</span>
                                    <span class="raibix-dot h-2 w-2"></span>
                                </div>
                                <h3 class="mt-4 text-xl font-bold text-[var(--color-ink)]">Student</h3>
                                <p class="mt-2 text-sm text-[var(--color-slate)] leading-relaxed">
                                    Single sign-on for timetable, study assignments, and transcripts.
                                </p>
                            </div>

                            <!-- Role Vignette: Lecture timeline badge -->
                            <div class="mt-8 rounded-xl border border-[var(--color-line)] bg-[var(--color-mist)] p-3.5 space-y-2 text-xs">
                                <div class="flex items-center justify-between font-semibold text-[var(--color-ink)]">
                                    <span>Next: Mathematics</span>
                                    <span class="text-[10px] text-[var(--color-blue)] font-mono">10:15 AM</span>
                                </div>
                                <div class="text-[11px] text-[var(--color-slate)]">
                                    Room 204 &bull; Terminal Exam Marks Posted
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </section>

            <!-- ===============================================================
                 SECTION 5: "IT ALL FEELS YOURS"
                 Left: Headline + Subdomain input. Right: Overlapping mockups
                 =============================================================== -->
            <section class="py-24 bg-[var(--color-mist)] border-y border-[var(--color-line)] overflow-hidden">
                <div class="mx-auto max-w-[1120px] px-6">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                        
                        <!-- Left: Headline + Domain Checker CTA -->
                        <div class="lg:col-span-5 space-y-6">
                            <div class="text-[13px] font-bold tracking-[0.08em] uppercase text-[var(--color-blue)]">
                                BRANDED SUBDOMAINS
                            </div>
                            <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-[var(--color-ink)] leading-tight">
                                It all feels yours.
                            </h2>
                            <p class="text-base text-[var(--color-slate)] leading-relaxed">
                                Each subscribing campus receives its own branded landing page and portal on a dedicated subdomain with school crests, colours, and custom links.
                            </p>

                            <!-- Interactive Subdomain Availability Utility -->
                            <div class="rounded-xl border border-[var(--color-line)] bg-[var(--color-page)] p-4 space-y-3">
                                <label class="block text-xs font-semibold text-[var(--color-ink)]">
                                    Check your campus subdomain availability:
                                </label>
                                <div class="flex items-center rounded-lg border border-[var(--color-line)] bg-[var(--color-mist)] px-3 py-2 text-sm">
                                    <input 
                                        v-model="subdomainQuery"
                                        @input="checkSubdomainAvailability"
                                        type="text" 
                                        placeholder="e.g. beaconhouse"
                                        class="flex-1 bg-transparent font-mono text-sm text-[var(--color-ink)] placeholder-[var(--color-slate)] focus:outline-none"
                                    />
                                    <span class="font-mono text-xs text-[var(--color-slate)] select-none">.raibix.cloud</span>
                                </div>

                                <!-- Live Availability Badge -->
                                <div class="flex items-center justify-between text-xs pt-1">
                                    <div v-if="isSearchingSubdomain" class="text-[var(--color-slate)]">
                                        Checking registry...
                                    </div>
                                    <div v-else-if="subdomainStatus === 'available'" class="flex items-center gap-1.5 text-[var(--color-success)] font-semibold">
                                        <span class="h-2 w-2 rounded-full bg-[var(--color-success)]"></span>
                                        <span>Available for your campus</span>
                                    </div>
                                    <div v-else-if="subdomainStatus === 'taken'" class="flex items-center gap-1.5 text-[var(--color-error)] font-semibold">
                                        <span class="h-2 w-2 rounded-full bg-[var(--color-error)]"></span>
                                        <span>Domain already active</span>
                                    </div>
                                    <div v-else class="text-[var(--color-slate)] text-[11px]">
                                        Instant provisioning upon verification
                                    </div>

                                    <Link 
                                        :href="subdomainQuery ? `/checkout/essential?domain=${subdomainQuery}` : '/checkout/essential'"
                                        class="text-xs font-bold text-[var(--color-blue)] hover:underline"
                                    >
                                        Claim now &rarr;
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <!-- Right: Overlapping Desktop Browser and Phone Mockups -->
                        <div class="lg:col-span-7 relative flex justify-center items-center py-6">
                            
                            <!-- Desktop Browser Mockup -->
                            <div class="w-full max-w-lg rounded-2xl border border-[var(--color-line)] bg-[var(--color-page)] shadow-2xl overflow-hidden animate-float-1">
                                <!-- Browser Chrome -->
                                <div class="flex items-center gap-2 border-b border-[var(--color-line)] bg-[var(--color-mist)] px-4 py-2.5">
                                    <div class="flex items-center gap-1.5">
                                        <span class="h-2.5 w-2.5 rounded-full bg-rose-400"></span>
                                        <span class="h-2.5 w-2.5 rounded-full bg-amber-400"></span>
                                        <span class="h-2.5 w-2.5 rounded-full bg-emerald-400"></span>
                                    </div>
                                    <!-- URL Bar -->
                                    <div class="mx-auto flex w-3/4 items-center justify-center rounded-md border border-[var(--color-line)] bg-[var(--color-page)] px-3 py-1 font-mono text-[11px] text-[var(--color-slate)]">
                                        <span class="text-[var(--color-blue)] font-semibold mr-1">https://</span>
                                        <span>beaconhouse.raibix.cloud</span>
                                    </div>
                                </div>

                                <!-- Browser Content: School's Branded Page Preview -->
                                <div class="p-6 space-y-5 bg-[var(--color-page)]">
                                    <div class="flex items-center justify-between border-b border-[var(--color-line)] pb-4">
                                        <div class="flex items-center gap-3">
                                            <div class="h-8 w-8 rounded-lg bg-blue-900 text-white flex items-center justify-center font-bold text-xs">
                                                BSS
                                            </div>
                                            <div>
                                                <div class="text-xs font-bold text-[var(--color-ink)]">Beaconhouse Campus</div>
                                                <div class="text-[10px] text-[var(--color-slate)]">Lahore Central Region</div>
                                            </div>
                                        </div>
                                        <span class="rounded-full bg-emerald-500/10 px-2.5 py-0.5 text-[10px] font-semibold text-emerald-600">
                                            Admissions Open
                                        </span>
                                    </div>

                                    <div class="rounded-xl bg-gradient-to-r from-blue-950 to-blue-900 p-5 text-white">
                                        <div class="text-xs font-semibold text-blue-200">Session 2026&ndash;2027</div>
                                        <div class="text-lg font-bold mt-1">Excellence in Academics &amp; Character</div>
                                        <div class="mt-3 flex gap-2">
                                            <span class="rounded-md bg-white/20 px-2.5 py-1 text-[11px] font-medium backdrop-blur-xs">Online Application</span>
                                            <span class="rounded-md bg-white/10 px-2.5 py-1 text-[11px] font-medium backdrop-blur-xs">Parent Portal</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Overlapping Phone Mockup (Positioned bottom right) -->
                            <div class="absolute -bottom-6 -right-2 sm:right-6 w-48 sm:w-56 rounded-3xl border-2 border-[var(--color-line)] bg-[var(--color-page)] shadow-2xl p-3 animate-float-2">
                                <!-- Phone Header -->
                                <div class="mx-auto h-3 w-16 rounded-full bg-[var(--color-line)] mb-2"></div>
                                <div class="rounded-2xl border border-[var(--color-line)] bg-[var(--color-mist)] p-3 space-y-2 text-left">
                                    <div class="flex items-center justify-between text-[10px] text-[var(--color-slate)]">
                                        <span>Parent Portal</span>
                                        <span class="raibix-dot h-1.5 w-1.5"></span>
                                    </div>
                                    <div class="font-bold text-xs text-[var(--color-ink)]">Ayaan Khan (Class 7)</div>
                                    <div class="rounded-lg bg-[var(--color-page)] p-2 border border-[var(--color-line)] text-[10px]">
                                        <div class="text-[var(--color-slate)]">Fee Challan (Oct)</div>
                                        <div class="font-bold text-emerald-600 mt-0.5">Paid &bull; Verified</div>
                                    </div>
                                </div>
                            </div>

                        </div>

                    </div>
                </div>
            </section>

            <!-- ===============================================================
                 SECTION 6: THE CORE
                 Centred gradient core orb, halo rings, floating cards (1 word each)
                 =============================================================== -->
            <section id="core" class="py-28 bg-[var(--color-page)] relative overflow-hidden text-center">
                <div class="mx-auto max-w-[1120px] px-6">
                    
                    <!-- Eyebrow + Title -->
                    <div class="mx-auto max-w-xl">
                        <div class="text-[13px] font-bold tracking-[0.08em] uppercase text-[var(--color-blue)]">
                            THE CORE
                        </div>
                        <h2 class="mt-3 text-3xl sm:text-4xl font-extrabold tracking-tight text-[var(--color-ink)]">
                            The Intelligence Core.
                        </h2>
                        <p class="mt-3 text-base text-[var(--color-slate)] leading-relaxed">
                            Six platform capabilities harmonized through a single autonomous engine.
                        </p>
                    </div>

                    <!-- The Core Orb with Halo Rings & Connected Floating Cards -->
                    <div class="relative mx-auto mt-20 flex h-[480px] sm:h-[560px] max-w-3xl items-center justify-center">
                        
                        <!-- Concentric Hairline Halo Rings -->
                        <div class="pointer-events-none absolute inset-0 flex items-center justify-center">
                            <div class="h-[280px] w-[280px] sm:h-[340px] sm:w-[340px] rounded-full border border-[var(--color-line)] opacity-80"></div>
                            <div class="absolute h-[380px] w-[380px] sm:h-[460px] sm:w-[460px] rounded-full border border-[var(--color-line)] opacity-60"></div>
                            <div class="absolute h-[480px] w-[480px] sm:h-[560px] sm:w-[560px] rounded-full border border-[var(--color-line)] opacity-40"></div>
                        </div>

                        <!-- Central Gradient Core Orb -->
                        <div class="relative z-10">
                            <div class="raibix-orb-large animate-orb-breathing h-36 w-36 sm:h-44 sm:w-44 shadow-2xl"></div>
                        </div>

                        <!-- FLOATING CARD 1: Admissions (Rounded Rectangle, Pastel Violet Tint) -->
                        <div class="animate-float-1 absolute top-8 sm:top-12 left-4 sm:left-12 z-20">
                            <div class="rounded-xl border border-violet-200/80 bg-violet-50/90 dark:bg-violet-950/40 dark:border-violet-800/40 px-4 py-2 text-xs sm:text-sm font-bold text-violet-700 dark:text-violet-300 shadow-sm backdrop-blur-xs">
                                Admissions
                            </div>
                        </div>

                        <!-- FLOATING CARD 2: Attendance (Circle, Pastel Blue Tint) -->
                        <div class="animate-float-2 absolute top-6 sm:top-10 right-6 sm:right-16 z-20">
                            <div class="flex h-16 w-16 sm:h-20 sm:w-20 items-center justify-center rounded-full border border-blue-200/80 bg-blue-50/90 dark:bg-blue-950/40 dark:border-blue-800/40 text-xs sm:text-sm font-bold text-blue-700 dark:text-blue-300 shadow-sm backdrop-blur-xs">
                                Attendance
                            </div>
                        </div>

                        <!-- FLOATING CARD 3: Billing (Pill, Pastel Mint Tint) -->
                        <div class="animate-float-3 absolute bottom-20 sm:bottom-24 left-2 sm:left-8 z-20">
                            <div class="rounded-full border border-emerald-200/80 bg-emerald-50/90 dark:bg-emerald-950/40 dark:border-emerald-800/40 px-5 py-2 text-xs sm:text-sm font-bold text-emerald-700 dark:text-emerald-300 shadow-sm backdrop-blur-xs">
                                Billing
                            </div>
                        </div>

                        <!-- FLOATING CARD 4: Academics (Triangle / Soft Polygon, Pastel Amber Tint) -->
                        <div class="animate-float-4 absolute bottom-16 sm:bottom-20 right-4 sm:right-12 z-20">
                            <div class="rounded-2xl border border-amber-200/80 bg-amber-50/90 dark:bg-amber-950/40 dark:border-amber-800/40 px-5 py-2.5 text-xs sm:text-sm font-bold text-amber-700 dark:text-amber-300 shadow-sm backdrop-blur-xs">
                                Academics
                            </div>
                        </div>

                        <!-- FLOATING CARD 5: Portals (Square, Pastel Rose Tint) -->
                        <div class="animate-float-1 absolute top-1/2 -translate-y-1/2 -left-2 sm:left-2 z-20">
                            <div class="rounded-xl border border-rose-200/80 bg-rose-50/90 dark:bg-rose-950/40 dark:border-rose-800/40 px-4 py-3 text-xs sm:text-sm font-bold text-rose-700 dark:text-rose-300 shadow-sm backdrop-blur-xs">
                                Portals
                            </div>
                        </div>

                        <!-- FLOATING CARD 6: Ledger (Pill, Pastel Indigo Tint) -->
                        <div class="animate-float-3 absolute top-1/2 -translate-y-1/2 -right-2 sm:right-2 z-20">
                            <div class="rounded-full border border-indigo-200/80 bg-indigo-50/90 dark:bg-indigo-950/40 dark:border-indigo-800/40 px-5 py-2 text-xs sm:text-sm font-bold text-indigo-700 dark:text-indigo-300 shadow-sm backdrop-blur-xs">
                                Ledger
                            </div>
                        </div>

                    </div>
                </div>
            </section>

            <!-- ===============================================================
                 SECTION 7: PRICING
                 Container (24px radius) holding Starter, Essential, Signature
                 Dot top-left, hover becomes active, Essential default active
                 Sanctioned gradient box below container
                 =============================================================== -->
            <section id="pricing" class="py-24 bg-[var(--color-page)]">
                <div class="mx-auto max-w-[1120px] px-6">
                    
                    <!-- Eyebrow + Section Title -->
                    <div class="text-center max-w-xl mx-auto">
                        <div class="text-[13px] font-bold tracking-[0.08em] uppercase text-[var(--color-blue)]">
                            PRICING
                        </div>
                        <h2 class="mt-3 text-3xl sm:text-4xl font-extrabold tracking-tight text-[var(--color-ink)]">
                            Predictable, transparent investment.
                        </h2>
                        <p class="mt-3 text-base text-[var(--color-slate)]">
                            Select the tier calibrated to your school size and operational breadth.
                        </p>

                        <!-- Monthly / Annual Toggle -->
                        <div class="mt-8 inline-flex items-center rounded-full border border-[var(--color-line)] bg-[var(--color-mist)] p-1 text-xs">
                            <button 
                                @click="isAnnual = false"
                                type="button" 
                                :class="[
                                    'rounded-full px-5 py-2 font-semibold transition-all cursor-pointer',
                                    !isAnnual ? 'bg-[var(--color-page)] text-[var(--color-ink)] shadow-xs' : 'text-[var(--color-slate)] hover:text-[var(--color-ink)]'
                                ]"
                            >
                                Monthly Billing
                            </button>
                            <button 
                                @click="isAnnual = true"
                                type="button" 
                                :class="[
                                    'rounded-full px-5 py-2 font-semibold transition-all flex items-center gap-1.5 cursor-pointer',
                                    isAnnual ? 'bg-[var(--color-page)] text-[var(--color-ink)] shadow-xs' : 'text-[var(--color-slate)] hover:text-[var(--color-ink)]'
                                ]"
                            >
                                <span>Annual Billing</span>
                                <span class="rounded-full bg-[var(--color-blue)]/10 px-2 py-0.5 text-[10px] font-bold text-[var(--color-blue)]">Save 20%</span>
                            </button>
                        </div>
                    </div>

                    <!-- Single Closed Container (24px radius, mist background, inset depth) -->
                    <div class="mt-14 rounded-[24px] border border-[var(--color-line)] bg-[var(--color-mist)] p-6 sm:p-10 shadow-[inset_0_2px_4px_rgba(0,0,0,0.03),0_20px_40px_-15px_rgba(0,0,0,0.06)]">
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-stretch">
                            
                            <!-- 3 Plan Cards: Starter, Essential, Signature -->
                            <div 
                                v-for="(plan, idx) in plansList" 
                                :key="plan.id || idx"
                                @mouseenter="activePlanIndex = idx"
                                @click="activePlanIndex = idx"
                                :class="[
                                    'rounded-2xl p-7 sm:p-8 flex flex-col justify-between transition-all duration-300 cursor-pointer',
                                    activePlanIndex === idx 
                                        ? 'bg-[var(--color-card-bg)] border-2 border-[var(--color-blue)] shadow-[0_16px_36px_-8px_rgba(47,107,255,0.18)] -translate-y-2 z-10' 
                                        : 'bg-[var(--color-card-bg)]/80 border border-[var(--color-line)] opacity-85 hover:opacity-100 hover:border-[var(--color-line)]'
                                ]"
                            >
                                <div>
                                    <!-- Top Left Dot on Every Card -->
                                    <div class="flex items-center justify-between">
                                        <span class="raibix-dot h-3 w-3"></span>
                                        <span class="rounded-full border border-[var(--color-line)] bg-[var(--color-mist)] px-2.5 py-0.5 text-[11px] font-semibold text-[var(--color-slate)]">
                                            Up to {{ plan.max_students }} students
                                        </span>
                                    </div>

                                    <!-- Plan Title & Price -->
                                    <h3 class="mt-5 text-2xl font-extrabold text-[var(--color-ink)]">
                                        {{ plan.name.replace(' Plan', '') }}
                                    </h3>

                                    <div class="mt-4 flex items-baseline gap-1">
                                        <span class="text-xs font-semibold text-[var(--color-slate)] uppercase">PKR</span>
                                        <span class="text-4xl font-extrabold tracking-tight text-[var(--color-ink)]">
                                            {{ getPlanPrice(plan).toLocaleString() }}
                                        </span>
                                        <span class="text-xs text-[var(--color-slate)]">
                                            / {{ isAnnual ? 'year' : 'month' }}
                                        </span>
                                    </div>

                                    <!-- Features List -->
                                    <ul class="mt-8 space-y-3 border-t border-[var(--color-line)] pt-6 text-xs text-[var(--color-slate)]">
                                        <li 
                                            v-for="(feature, fIdx) in plan.features" 
                                            :key="fIdx"
                                            class="flex items-start gap-2.5"
                                        >
                                            <svg class="h-4 w-4 shrink-0 text-[var(--color-blue)] mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                            </svg>
                                            <span class="text-[var(--color-ink)] font-medium leading-relaxed">{{ feature }}</span>
                                        </li>
                                    </ul>
                                </div>

                                <!-- Action Button routing to checkout -->
                                <div class="mt-8 pt-4">
                                    <Link 
                                        :href="`/checkout/${plan.code}`"
                                        :class="[
                                            'w-full inline-flex items-center justify-center gap-2 rounded-xl py-3 text-xs font-bold transition-all duration-200 cursor-pointer',
                                            activePlanIndex === idx 
                                                ? 'bg-[var(--color-blue)] text-white hover:bg-[var(--color-blue-hover)] shadow-md' 
                                                : 'border border-[var(--color-line)] bg-[var(--color-mist)] text-[var(--color-ink)] hover:bg-[var(--color-page)]'
                                        ]"
                                    >
                                        <span>Get {{ plan.name.replace(' Plan', '') }}</span>
                                        <span class="raibix-dot h-1.5 w-1.5"></span>
                                    </Link>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Sanctioned Exception 2: Full-width Gradient Box with wave-line texture -->
                    <div class="mt-8 relative overflow-hidden rounded-2xl p-8 sm:p-10 text-white bg-gradient-to-r from-[#8B5CF6] to-[#2F6BFF] shadow-xl">
                        <!-- Organic wave-line texture -->
                        <div class="pointer-events-none absolute inset-0 opacity-15">
                            <svg class="h-full w-full" preserveAspectRatio="none" viewBox="0 0 800 200">
                                <path d="M 0 100 Q 200 40 400 100 T 800 100 L 800 200 L 0 200 Z" fill="white" />
                                <path d="M 0 140 Q 250 80 500 140 T 800 140 L 800 200 L 0 200 Z" fill="white" opacity="0.5" />
                            </svg>
                        </div>

                        <div class="relative z-10 flex flex-col sm:flex-row items-center justify-between gap-6">
                            <div>
                                <h3 class="text-xl sm:text-2xl font-bold tracking-tight">
                                    Not sure which suits your school?
                                </h3>
                                <p class="text-sm text-white/85 mt-1">
                                    Our school systems architects provide tailored structural consultations.
                                </p>
                            </div>
                            <a 
                                href="tel:+923001234567" 
                                class="raibix-btn-hover shrink-0 inline-flex items-center gap-2 rounded-full bg-white px-6 py-3 text-xs font-bold text-[var(--color-ink)] shadow-md transition-colors hover:bg-[var(--color-mist)] cursor-pointer"
                            >
                                <svg class="h-3.5 w-3.5 text-[var(--color-blue)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                                <span>Call an expert</span>
                            </a>
                        </div>
                    </div>

                </div>
            </section>

            <!-- ===============================================================
                 SECTION 8: FAQS + CONTACT
                 Left: Accordion (+ rotating to x). Hairline divider. Right: Contact form
                 =============================================================== -->
            <section class="py-24 bg-[var(--color-page)] border-t border-[var(--color-line)]">
                <div class="mx-auto max-w-[1120px] px-6">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-16">
                        
                        <!-- Left Column: FAQ Accordion -->
                        <div class="lg:col-span-6 space-y-6">
                            <div>
                                <div class="text-[13px] font-bold tracking-[0.08em] uppercase text-[var(--color-blue)]">
                                    ANSWERS
                                </div>
                                <h2 class="mt-2 text-2xl sm:text-3xl font-extrabold tracking-tight text-[var(--color-ink)]">
                                    Frequently answered questions.
                                </h2>
                            </div>

                            <div class="divide-y divide-[var(--color-line)] border-y border-[var(--color-line)]">
                                <div 
                                    v-for="(faq, fIdx) in faqs" 
                                    :key="fIdx"
                                    class="py-4"
                                >
                                    <button 
                                        @click="toggleFaq(fIdx)" 
                                        type="button" 
                                        class="flex w-full items-center justify-between text-left text-sm font-semibold text-[var(--color-ink)] hover:text-[var(--color-blue)] transition-colors cursor-pointer"
                                        :aria-expanded="activeFaq === fIdx"
                                    >
                                        <span class="pr-4">{{ faq.q }}</span>
                                        <span 
                                            :class="[
                                                'flex h-6 w-6 shrink-0 items-center justify-center text-lg font-light transition-transform duration-300',
                                                activeFaq === fIdx ? 'rotate-45 text-[var(--color-blue)]' : 'rotate-0 text-[var(--color-slate)]'
                                            ]"
                                        >
                                            +
                                        </span>
                                    </button>

                                    <!-- Animated Accordion Height -->
                                    <div 
                                        v-show="activeFaq === fIdx" 
                                        class="mt-3 text-xs leading-relaxed text-[var(--color-slate)] transition-all duration-300"
                                    >
                                        {{ faq.a }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Contact Form -->
                        <div class="lg:col-span-6 space-y-6">
                            <div>
                                <div class="text-[13px] font-bold tracking-[0.08em] uppercase text-[var(--color-blue)]">
                                    CONTACT
                                </div>
                                <h2 class="mt-2 text-2xl sm:text-3xl font-extrabold tracking-tight text-[var(--color-ink)]">
                                    Speak directly with leadership.
                                </h2>
                            </div>

                            <!-- Success Banner -->
                            <div v-if="contactSuccess" class="rounded-xl border border-emerald-500/20 bg-emerald-500/10 p-4 text-xs font-semibold text-emerald-600 flex items-center gap-2">
                                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                <span>Inquiry delivered. A solutions director will respond shortly.</span>
                            </div>

                            <form @submit.prevent="submitContact" class="space-y-4">
                                <!-- Name Input -->
                                <div>
                                    <label class="block text-xs font-semibold text-[var(--color-ink)] mb-1">
                                        Full Name
                                    </label>
                                    <input 
                                        v-model="contactForm.name"
                                        type="text" 
                                        placeholder="Dr. Arshad Khan"
                                        class="w-full rounded-lg border border-[var(--color-line)] bg-[var(--color-input-bg)] px-3.5 py-2.5 text-sm text-[var(--color-ink)] placeholder-[var(--color-slate)] focus:border-[var(--color-blue)] focus:outline-none transition-colors"
                                    />
                                    <div v-if="contactErrors.name" class="mt-1 text-xs text-[var(--color-error)]">
                                        {{ contactErrors.name }}
                                    </div>
                                </div>

                                <!-- Email Input -->
                                <div>
                                    <label class="block text-xs font-semibold text-[var(--color-ink)] mb-1">
                                        Official Email Address
                                    </label>
                                    <input 
                                        v-model="contactForm.email"
                                        type="email" 
                                        placeholder="principal@yourschool.edu.pk"
                                        class="w-full rounded-lg border border-[var(--color-line)] bg-[var(--color-input-bg)] px-3.5 py-2.5 text-sm text-[var(--color-ink)] placeholder-[var(--color-slate)] focus:border-[var(--color-blue)] focus:outline-none transition-colors"
                                    />
                                    <div v-if="contactErrors.email" class="mt-1 text-xs text-[var(--color-error)]">
                                        {{ contactErrors.email }}
                                    </div>
                                </div>

                                <!-- Message Input -->
                                <div>
                                    <label class="block text-xs font-semibold text-[var(--color-ink)] mb-1">
                                        Inquiry Message
                                    </label>
                                    <textarea 
                                        v-model="contactForm.message"
                                        rows="4" 
                                        placeholder="Describe your student capacity, campus branches, and migration timeline..."
                                        class="w-full rounded-lg border border-[var(--color-line)] bg-[var(--color-input-bg)] px-3.5 py-2.5 text-sm text-[var(--color-ink)] placeholder-[var(--color-slate)] focus:border-[var(--color-blue)] focus:outline-none transition-colors"
                                    ></textarea>
                                    <div v-if="contactErrors.message" class="mt-1 text-xs text-[var(--color-error)]">
                                        {{ contactErrors.message }}
                                    </div>
                                </div>

                                <!-- Submit Button -->
                                <button 
                                    type="submit" 
                                    :disabled="contactSubmitting"
                                    class="raibix-btn-hover w-full rounded-lg bg-[var(--color-ink)] hover:bg-[var(--color-blue)] px-6 py-3 text-xs font-semibold text-white transition-colors duration-200 disabled:opacity-50 cursor-pointer flex items-center justify-center gap-2"
                                >
                                    <span v-if="contactSubmitting">Transmitting inquiry...</span>
                                    <span v-else class="flex items-center gap-2">
                                        <span>Send Message</span>
                                        <span class="raibix-dot h-1.5 w-1.5"></span>
                                    </span>
                                </button>
                            </form>
                        </div>

                    </div>
                </div>
            </section>
        </main>

        <!-- ===================================================================
             SECTION 9: FOOTER
             Logo + short description, links, contact with line icons, copyright
             =================================================================== -->
        <footer id="footer" class="border-t border-[var(--color-line)] bg-[var(--color-mist)] py-14 text-xs text-[var(--color-slate)]">
            <div class="mx-auto max-w-[1120px] px-6">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-10 pb-12 border-b border-[var(--color-line)]">
                    
                    <!-- Left Column: Logo + Short description -->
                    <div class="md:col-span-5 space-y-3">
                        <Link href="/" class="flex items-center gap-2 text-lg font-extrabold text-[var(--color-ink)]">
                            <span>Raibix</span>
                            <span class="raibix-dot h-2 w-2"></span>
                        </Link>
                        <p class="max-w-sm text-xs leading-relaxed text-[var(--color-slate)]">
                            The intelligence core for progressive educational institutions. Dedicated tenant containers, biometric sync, and national 1-Bill clearing.
                        </p>
                    </div>

                    <!-- Middle Column: Navigation Links -->
                    <div class="md:col-span-3 space-y-2">
                        <div class="font-bold uppercase tracking-wider text-[var(--color-ink)] text-[11px] mb-3">
                            Platform Navigation
                        </div>
                        <ul class="space-y-2">
                            <li><a href="#hero" class="hover:text-[var(--color-ink)] transition-colors">Overview</a></li>
                            <li><a href="#story" class="hover:text-[var(--color-ink)] transition-colors">Architecture &amp; Story</a></li>
                            <li><a href="#roles" class="hover:text-[var(--color-ink)] transition-colors">Stakeholder Roles</a></li>
                            <li><a href="#core" class="hover:text-[var(--color-ink)] transition-colors">The Intelligence Core</a></li>
                            <li><a href="#pricing" class="hover:text-[var(--color-ink)] transition-colors">Subscription Pricing</a></li>
                            <li><Link href="/super-admin/login" class="hover:text-[var(--color-ink)] transition-colors">Super Admin Portal</Link></li>
                        </ul>
                    </div>

                    <!-- Right Column: Contact Details with Clean Line Icons -->
                    <div class="md:col-span-4 space-y-3">
                        <div class="font-bold uppercase tracking-wider text-[var(--color-ink)] text-[11px] mb-3">
                            Headquarters &amp; Support
                        </div>
                        <ul class="space-y-2.5">
                            <li class="flex items-center gap-2.5">
                                <svg class="h-4 w-4 shrink-0 text-[var(--color-blue)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                <a href="mailto:admin@lmscloud.pk" class="hover:text-[var(--color-ink)] transition-colors">admin@lmscloud.pk</a>
                            </li>
                            <li class="flex items-center gap-2.5">
                                <svg class="h-4 w-4 shrink-0 text-[var(--color-blue)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                                <a href="tel:+923001234567" class="hover:text-[var(--color-ink)] transition-colors">+92 300 1234567</a>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <svg class="h-4 w-4 shrink-0 text-[var(--color-blue)] mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span>Platform Engineering HQ, Islamabad, Pakistan</span>
                            </li>
                        </ul>
                    </div>

                </div>

                <!-- Bottom Row: Copyright left, Privacy policy right -->
                <div class="mt-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs">
                    <div class="flex items-center gap-2">
                        <span class="raibix-dot h-1.5 w-1.5"></span>
                        <span>&copy; 2026 Raibix Technologies. All rights reserved.</span>
                    </div>
                    <div class="flex items-center gap-6">
                        <a href="#" class="hover:text-[var(--color-ink)] transition-colors">Privacy Policy</a>
                        <a href="#" class="hover:text-[var(--color-ink)] transition-colors">Terms of Service</a>
                        <a href="#" class="hover:text-[var(--color-ink)] transition-colors">Security Architecture</a>
                    </div>
                </div>
            </div>
        </footer>

    </div>
</template>
