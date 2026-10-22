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
// 1. BOTTOM NAVIGATION & ACTIVE SECTION OBSERVER
// =============================================================================
const activeSection = ref('features');
const footerInView = ref(false);

const scrollToSection = (id) => {
    const el = document.getElementById(id);
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'start' });
        activeSection.value = id;
    }
};

let observer = null;
const setupSectionObserver = () => {
    const sectionIds = ['features', 'pricing', 'contact'];
    const elements = sectionIds.map(id => document.getElementById(id)).filter(Boolean);

    observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting && entry.intersectionRatio >= 0.25) {
                activeSection.value = entry.target.id;
            }
        });
    }, {
        threshold: [0.25, 0.5, 0.75],
        rootMargin: '-10% 0px -40% 0px'
    });

    elements.forEach(el => observer.observe(el));

    // Also observe footer to hide/dim bottom bar
    const footerEl = document.getElementById('footer');
    if (footerEl) {
        const footerObserver = new IntersectionObserver((entries) => {
            footerInView.value = entries[0].isIntersecting;
        }, { threshold: 0.1 });
        footerObserver.observe(footerEl);
    }
};

// =============================================================================
// 2. HERO SEMICIRCLE PARALLAX
// =============================================================================
const heroRef = ref(null);
const heroParallax = ref({ x: 0, y: 0 });
const heroTarget = ref({ x: 0, y: 0 });
let heroRaf = null;

const handleHeroPointer = (e) => {
    if (!heroRef.value) return;
    const rect = heroRef.value.getBoundingClientRect();
    const x = (e.clientX - rect.left) - rect.width / 2;
    const y = (e.clientY - rect.top) - rect.height / 2;
    heroTarget.value = {
        x: Math.max(-12, Math.min(12, x * 0.02)),
        y: Math.max(-10, Math.min(10, y * 0.02))
    };
};

const handleHeroLeave = () => {
    heroTarget.value = { x: 0, y: 0 };
};

const updateHeroPhysics = () => {
    heroParallax.value.x += (heroTarget.value.x - heroParallax.value.x) * 0.08;
    heroParallax.value.y += (heroTarget.value.y - heroParallax.value.y) * 0.08;
    heroRaf = requestAnimationFrame(updateHeroPhysics);
};

// =============================================================================
// 3. FOUR USER ROLES (STATIC FEATURE CARDS)
// =============================================================================
const roles = [
    {
        id: 'admin',
        title: 'School Admin',
        description: 'Manage school operations, institutional records, staff access, and administrative workflows.'
    },
    {
        id: 'teacher',
        title: 'Teacher',
        description: 'Access academic information, manage classroom responsibilities, and coordinate everyday teaching activities.'
    },
    {
        id: 'parent',
        title: 'Parent',
        description: 'Stay informed through access to relevant school updates, academic information, and student progress.'
    },
    {
        id: 'student',
        title: 'Student',
        description: 'Access learning resources, academic information, timetables, and relevant student services.'
    }
];

// =============================================================================
// 4. SCHOOL VIEW MOCKUPS & SUBDOMAIN CHECK
// =============================================================================
const schoolSubdomainInput = ref('');
const checkingSubdomain = ref(false);
const subdomainAvailable = ref(true);
const activeMockupView = ref('desktop'); // 'desktop' or 'mobile'

const checkSubdomain = async () => {
    const raw = schoolSubdomainInput.value.toLowerCase().trim().replace(/[^a-z0-9-]/g, '');
    schoolSubdomainInput.value = raw;
    if (!raw) {
        subdomainAvailable.value = true;
        return;
    }
    checkingSubdomain.value = true;
    try {
        const response = await axios.get(`/api/check-subdomain?subdomain=${raw}`);
        subdomainAvailable.value = response.data.available ?? true;
    } catch (e) {
        subdomainAvailable.value = true;
    } finally {
        checkingSubdomain.value = false;
    }
};

// =============================================================================
// 5. ANIMATED INTELLIGENCE CORE LABELS
// =============================================================================
const coreFeatures = [
    {
        id: 'admin',
        label: 'Administration',
        color: '#8B5CF6'
    },
    {
        id: 'academics',
        label: 'Academic Workflows',
        color: '#2F6BFF'
    },
    {
        id: 'attendance',
        label: 'Attendance',
        color: '#10B981'
    },
    {
        id: 'billing',
        label: 'Financial Operations',
        color: '#F59E0B'
    },
    {
        id: 'security',
        label: 'Secure Data',
        color: '#6366F1'
    },
    {
        id: 'access',
        label: 'Connected Access',
        color: '#EC4899'
    }
];

// =============================================================================
// 6. PRICING TIERS
// =============================================================================
const isAnnual = ref(false);
const defaultPlans = [
    {
        id: 1,
        code: 'starter',
        name: 'Starter',
        price_monthly: 20000,
        price_yearly: 192000,
        description: 'Core infrastructure for single-branch institutions.',
        max_students: 300,
        features: [
            'Single-Tenant Isolated Database Container',
            'Full Student & Guardian Information System',
            'Daily Attendance & RFID Turnstile Webhook',
            'Automated Fee Voucher Generation',
            'Student & Parent Dedicated Portals',
            'Standard Email Support'
        ]
    },
    {
        id: 2,
        code: 'essential',
        name: 'Essential',
        price_monthly: 40000,
        price_yearly: 384000,
        description: 'Complete operational automation with 1-Bill national clearing.',
        max_students: 1000,
        featured: true,
        features: [
            'All capabilities in Starter',
            'Direct 1-Bill National Bank Clearing Integration',
            'Facial Recognition Real-Time Attendance Sync',
            'Full Examination & Gradebook',
            'Instant Parent SMS Notification Dispatcher',
            'Dedicated Campus Subdomain (school.raibix.cloud)',
            'Priority 4-Hour Support SLA'
        ]
    },
    {
        id: 3,
        code: 'signature',
        name: 'Signature',
        price_monthly: 50000,
        price_yearly: 480000,
        description: 'Multi-branch campus management with custom white-label domain.',
        max_students: 5000,
        features: [
            'All capabilities in Essential',
            'Multi-Branch Consolidated Reporting & Audit',
            'Custom Domain Mapping with SSL',
            'QR-Verifiable Digital Transcripts',
            'Dedicated Technical Account Manager',
            '24/7 Priority Emergency Phone Support',
            'Historical Data Migration Assistance'
        ]
    }
];

const plansList = computed(() => {
    if (props.plans && props.plans.length >= 3) {
        return props.plans;
    }
    return defaultPlans;
});

const getDisplayPrice = (plan) => {
    if (isAnnual.value) {
        return plan.price_yearly ? Math.round(plan.price_yearly / 12) : Math.round(plan.price_monthly * 0.8);
    }
    return plan.price_monthly || 0;
};

// =============================================================================
// 7. EXPERT MODAL STATE
// =============================================================================
const expertModalOpen = ref(false);
const expertForm = ref({ name: '', phone: '', institution: '' });
const expertSubmitted = ref(false);
const expertSubmitting = ref(false);

const submitExpert = async () => {
    if (!expertForm.value.name || !expertForm.value.phone) return;
    expertSubmitting.value = true;
    try {
        await axios.post('/inquiries', {
            name: expertForm.value.name,
            phone: expertForm.value.phone,
            institution: expertForm.value.institution || 'Direct Portal Request',
            type: 'expert_call',
        });
        expertSubmitted.value = true;
        setTimeout(() => {
            expertModalOpen.value = false;
            expertSubmitted.value = false;
            expertForm.value = { name: '', phone: '', institution: '' };
        }, 2200);
    } catch (e) {
        console.error('Failed to submit expert request:', e);
    } finally {
        expertSubmitting.value = false;
    }
};

// =============================================================================
// 8. FAQS & CONTACT FORM
// =============================================================================
const activeFaqIndex = ref(0);
const faqs = [
    {
        question: 'How does dedicated database isolation protect our school?',
        answer: 'Unlike shared-database software that pools multiple clients into one SQL table with a tenant ID column, Raibix provisions a physically isolated database container for every campus. Your student records, financial vouchers, and staff files are completely segregated with zero risk of cross-tenant data leaks.'
    },
    {
        question: 'How does 1-Bill national clearing settle fee vouchers automatically?',
        answer: 'Each generated fee voucher is assigned a standardized 1-Bill consumer number. Parents pay directly from any Pakistani banking mobile app, ATM, or teller. The transaction settles into your school ledger within three seconds without manual bank statement reconciliation.'
    },
    {
        question: 'Can we connect existing biometric turnstiles and cameras?',
        answer: 'Yes. Raibix provides REST webhook listeners compatible with leading biometric and facial recognition hardware (ZKTeco, Hikvision, Dahua). Once a turnstile clocks a student or teacher, our system instantly logs attendance and dispatches parent alerts.'
    },
    {
        question: 'What is the onboarding and payment verification process?',
        answer: 'Select your preferred plan, reserve your school subdomain, and complete payment via direct bank transfer. Once you upload your receipt screenshot, our operations team verifies the record and activates your dedicated campus workspace.'
    },
    {
        question: 'Can we use our own custom school domain?',
        answer: 'Yes. The Essential and Signature tiers support custom domain mapping (such as portal.yourschool.edu.pk) with automated SSL certificate management.'
    }
];

const toggleFaq = (index) => {
    activeFaqIndex.value = activeFaqIndex.value === index ? -1 : index;
};

const contactForm = ref({
    name: '',
    email: '',
    institution: '',
    message: ''
});
const contactErrors = ref({});
const contactSubmitting = ref(false);
const contactSuccess = ref(false);

const submitContact = async () => {
    contactErrors.value = {};
    if (!contactForm.value.name.trim()) contactErrors.value.name = 'Full name is required';
    if (!contactForm.value.email.trim() || !contactForm.value.email.includes('@')) {
        contactErrors.value.email = 'Valid work email is required';
    }
    if (!contactForm.value.institution.trim()) contactErrors.value.institution = 'School or institution name is required';
    if (!contactForm.value.message.trim()) contactErrors.value.message = 'Please provide an inquiry message';

    if (Object.keys(contactErrors.value).length > 0) return;

    contactSubmitting.value = true;
    try {
        await axios.post('/inquiries', {
            name: contactForm.value.name,
            email: contactForm.value.email,
            institution: contactForm.value.institution,
            message: contactForm.value.message,
            type: 'contact',
        });
        contactSuccess.value = true;
        contactForm.value = { name: '', email: '', institution: '', message: '' };
        setTimeout(() => { contactSuccess.value = false; }, 8000);
    } catch (e) {
        contactErrors.value.general = 'Failed to submit inquiry. Please try again.';
    } finally {
        contactSubmitting.value = false;
    }
};

// =============================================================================
// LIFECYCLE
// =============================================================================
onMounted(() => {
    heroRaf = requestAnimationFrame(updateHeroPhysics);
    setupSectionObserver();
});

onUnmounted(() => {
    if (heroRaf) cancelAnimationFrame(heroRaf);
    if (observer) observer.disconnect();
});
</script>

<template>
    <div class="min-h-screen bg-[#FFFFFF] text-[#111318] font-['Poppins'] antialiased selection:bg-[#8B5CF6]/20 selection:text-[#2F6BFF] pb-24 sm:pb-32">

        <!-- ===================================================================
             1. MAIN NAVBAR (STICKY AT TOP)
             Actual Raibix Logo (Left) + Single "Get Started" Button (Right)
             =================================================================== -->
        <header class="sticky top-0 z-50 w-full bg-white/95 backdrop-blur-md border-b border-[#E7E9EE]">
            <div class="mx-auto flex h-16 sm:h-18 max-w-[1240px] items-center justify-between px-6 sm:px-10">
                
                <!-- Left: Actual Raibix Logo Image from Project -->
                <Link href="/" class="flex items-center gap-2 group" aria-label="Raibix Home">
                    <img 
                        src="/images/raibix.png" 
                        alt="Raibix Logo" 
                        class="h-6 sm:h-7 w-auto object-contain transition-transform duration-200 group-hover:scale-102"
                    />
                </Link>

                <!-- Right: Single Get Started Button -->
                <div class="flex items-center gap-4">
                    <Link 
                        href="/super-admin/login" 
                        class="hidden sm:inline-block text-xs font-semibold tracking-tight text-[#5B6270] hover:text-[#111318] transition-colors"
                    >
                        Sign In
                    </Link>
                    <Link 
                        href="/checkout/essential" 
                        class="group inline-flex items-center gap-2 rounded-full bg-[#111318] hover:bg-[#2F6BFF] px-5 py-2.5 text-xs font-semibold tracking-tight text-white transition-all duration-200 cursor-pointer shadow-xs"
                    >
                        <span>Get Started</span>
                        <span class="raibix-dot h-1.5 w-1.5 transition-transform group-hover:scale-125"></span>
                    </Link>
                </div>
            </div>
        </header>

        <main>
            <!-- ===============================================================
                 2. HERO — A LIVING INTELLIGENCE CORE
                 Heading <= 4 words: "One Core. Every Campus."
                 Supporting text: "Every essential school operation, connected through one intelligent platform."
                 Two CTAs: Get Started and Explore Platform.
                 Defining semicircle: Large, filled gradient shape with smooth organic flow.
                 =============================================================== -->
            <section 
                id="hero"
                ref="heroRef"
                @mousemove="handleHeroPointer"
                @mouseleave="handleHeroLeave"
                class="relative flex flex-col justify-start overflow-hidden pt-8 sm:pt-14 pb-0 text-center bg-white h-[calc(100dvh-4rem)] sm:h-[calc(100dvh-4.5rem)] min-h-[580px] max-h-[940px]"
            >
                <!-- Hero Content (Centered, Bold, <= 4 words) -->
                <div class="relative z-20 mx-auto max-w-[1240px] px-6 sm:px-10 flex flex-col items-center pt-2 sm:pt-4">
                    
                    <!-- Heading: <= 4 words -->
                    <h1 class="max-w-4xl text-4xl sm:text-6xl md:text-7xl font-black tracking-tight leading-[1.02] text-[#111318]">
                        One Core.<br class="hidden sm:inline" />
                        Every Campus<span class="raibix-dot inline-block h-3 w-3 sm:h-4 sm:w-4 md:h-5 md:w-5 ml-1.5 align-baseline"></span>
                    </h1>

                    <!-- Concise Supporting Text (Two lines on desktop) -->
                    <p class="mt-3 sm:mt-4 max-w-lg text-sm sm:text-base text-[#5B6270] font-normal leading-relaxed">
                        Every essential school operation, connected through one intelligent platform.
                    </p>

                    <!-- Primary & Secondary CTAs (No more than two buttons) -->
                    <div class="mt-5 sm:mt-6 flex flex-wrap items-center justify-center gap-3.5">
                        <Link 
                            href="/checkout/essential" 
                            class="group inline-flex items-center gap-2.5 rounded-full bg-[#111318] hover:bg-[#2F6BFF] px-7 py-3.5 text-xs sm:text-sm font-bold text-white transition-all duration-200 cursor-pointer shadow-sm"
                        >
                            <span>Get Started</span>
                            <span class="raibix-dot h-2 w-2 transition-transform group-hover:scale-125"></span>
                        </Link>
                        <button 
                            @click="scrollToSection('features')"
                            type="button" 
                            class="inline-flex items-center gap-2 rounded-full border border-[#E7E9EE] hover:border-[#111318] bg-white px-6 py-3.5 text-xs sm:text-sm font-semibold text-[#111318] transition-colors cursor-pointer"
                        >
                            <span>Explore Platform</span>
                        </button>
                    </div>
                </div>

                <!-- Horizon Circle: Seamless Dome Emerging from Bottom (Scaled and positioned to keep heading purely on white) -->
                <div 
                    class="absolute bottom-0 left-1/2 pointer-events-none z-10 w-[420px] sm:w-[600px] md:w-[760px] lg:w-[860px] aspect-square select-none transition-transform duration-300 ease-out"
                    :style="{ 
                        transform: `translate(calc(-50% + ${heroParallax.x}px), calc(54% + ${heroParallax.y}px))` 
                    }"
                >
                    <!-- Inner Circle Container with Multi-Layered Animated Gradient Mesh -->
                    <div class="w-full h-full rounded-full relative overflow-hidden flex items-center justify-center">
                        
                        <!-- Layer 1: Ambient Slowly Rotating Conic Gradient -->
                        <div class="absolute -inset-10 bg-[conic-gradient(from_0deg,#8B5CF6_0%,#2F6BFF_30%,#38BDF8_50%,#8B5CF6_70%,#6366F1_100%)] opacity-35 mix-blend-color-dodge filter blur-2xl animate-orb-spin"></div>
                        
                        <!-- Layer 2: Organic Floating Violet/Purple Pulse -->
                        <div class="absolute top-[8%] left-[22%] w-[60%] h-[60%] rounded-full bg-radial from-[#8B5CF6] via-[#7C3AED]/70 to-transparent filter blur-2xl animate-orb-blob-1"></div>
                        
                        <!-- Layer 3: Organic Floating Electric Blue Pulse -->
                        <div class="absolute bottom-[10%] right-[15%] w-[68%] h-[68%] rounded-full bg-radial from-[#2F6BFF] via-[#3B82F6]/75 to-transparent filter blur-2xl animate-orb-blob-2"></div>
                        
                        <!-- Layer 4: Original Shared Raibix Orb Asset with Shadow & Rim -->
                        <img 
                            src="/images/raibix_orb.png" 
                            alt="Raibix Intelligence Core Horizon" 
                            class="w-full h-full object-contain relative z-10 opacity-80 mix-blend-multiply animate-orb-horizon drop-shadow-[0_-25px_60px_rgba(139,92,246,0.35)]"
                        />
                        
                        <!-- Layer 5: Top Shimmer Wave Overlay -->
                        <div class="absolute inset-0 z-20 rounded-full bg-gradient-to-tr from-[#7C3AED]/20 via-[#2F6BFF]/15 to-[#8B5CF6]/20 mix-blend-overlay animate-gradient-flow pointer-events-none"></div>
                    </div>
                </div>
            </section>

            <!-- ===============================================================
                 3. FEATURED SHOWCASE — SIMPLIFY AND STRENGTHEN
                 75% / 25% clean two-column composition.
                 Left (75%): Authentic photo, gradient overlay, single clear message.
                 Right (25%): Refined, distinctive Intelligence Core detail.
                 =============================================================== -->
            <section class="py-16 sm:py-24 bg-white border-t border-[#E7E9EE]">
                <div class="mx-auto max-w-[1240px] px-6 sm:px-10">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-stretch">
                        
                        <!-- Left 75%: Featured Education Image Area (lg:col-span-9) -->
                        <div class="lg:col-span-9 relative rounded-2xl sm:rounded-3xl overflow-hidden border border-[#E7E9EE] flex flex-col justify-end p-8 sm:p-14 min-h-[480px] bg-[#111318]">
                            <!-- Authentic Education Photograph -->
                            <img 
                                src="/images/focused_classroom_study_session.png" 
                                alt="Focused classroom study session" 
                                class="absolute inset-0 h-full w-full object-cover object-center opacity-85"
                                loading="lazy"
                            />
                            <!-- Horizontal gradient overlay: strongest on left, fading toward right -->
                            <div class="absolute inset-0 bg-gradient-to-r from-[#0F1117]/95 via-[#0F1117]/75 to-transparent"></div>

                            <!-- Content on left side of image -->
                            <div class="relative z-10 max-w-lg space-y-4 text-left">
                                <div class="text-[11px] font-bold tracking-widest uppercase text-white/70">
                                    RAIBIX FOR SCHOOLS
                                </div>
                                <h2 class="text-3xl sm:text-4xl md:text-5xl font-black text-white leading-tight">
                                    Built for Every Campus.
                                </h2>
                                <p class="text-sm sm:text-base text-white/85 leading-relaxed font-normal">
                                    Connect your school's essential operations in one dependable space.
                                </p>
                                <div class="pt-2">
                                    <button 
                                        @click="scrollToSection('features')"
                                        type="button" 
                                        class="inline-flex items-center gap-2 rounded-full bg-white text-[#111318] hover:bg-[#F3F4F6] px-6 py-3.5 text-xs font-bold transition-all cursor-pointer shadow-sm"
                                    >
                                        <span>Explore the Platform</span>
                                        <span class="raibix-dot h-1.5 w-1.5"></span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Right 25%: Enhanced Intelligence Core Artifact (lg:col-span-3) -->
                        <div class="lg:col-span-3 rounded-2xl sm:rounded-3xl border border-[#E7E9EE] bg-[#FBFBFC] hover:border-[#111318] transition-colors duration-300 p-8 sm:p-9 flex flex-col justify-between text-left shadow-2xs">
                            <div class="space-y-6">
                                
                                <!-- Top Miniature Core Visual & Status -->
                                <div class="flex items-center justify-between">
                                    <div class="relative flex items-center justify-center">
                                        <div class="h-12 w-12 rounded-full border border-[#E7E9EE] bg-white shadow-xs flex items-center justify-center">
                                            <div class="h-6 w-6 rounded-full bg-gradient-to-tr from-[#8B5CF6] to-[#2F6BFF] shadow-xs flex items-center justify-center animate-pulse">
                                                <span class="h-1.5 w-1.5 rounded-full bg-white"></span>
                                            </div>
                                        </div>
                                    </div>
                                    <span class="inline-flex items-center gap-1.5 text-[10px] font-mono font-bold tracking-wider uppercase text-[#2F6BFF] bg-[#2F6BFF]/10 px-2.5 py-1 rounded-full">
                                        <span class="raibix-dot h-1.5 w-1.5 animate-pulse"></span>
                                        ACTIVE
                                    </span>
                                </div>

                                <!-- Editorial Heading & Text -->
                                <div class="space-y-2.5">
                                    <div class="text-[10px] font-bold uppercase tracking-widest text-[#5B6270]">
                                        Architecture
                                    </div>
                                    <h3 class="text-xl sm:text-2xl font-black tracking-tight text-[#111318] leading-tight">
                                        One Connected Core
                                    </h3>
                                    <p class="text-xs sm:text-sm text-[#5B6270] leading-relaxed">
                                        One foundation for connected campus operations.
                                    </p>
                                </div>

                                <!-- Architectural Micro-Points -->
                                <div class="space-y-2 pt-2 border-t border-[#E7E9EE]">
                                    <div class="flex items-center gap-2 text-[11px] font-medium text-[#111318]">
                                        <span class="h-1.5 w-1.5 rounded-full bg-[#8B5CF6]"></span>
                                        <span>Isolated Database Schema</span>
                                    </div>
                                    <div class="flex items-center gap-2 text-[11px] font-medium text-[#111318]">
                                        <span class="h-1.5 w-1.5 rounded-full bg-[#2F6BFF]"></span>
                                        <span>1-Bill National Clearing</span>
                                    </div>
                                    <div class="flex items-center gap-2 text-[11px] font-medium text-[#111318]">
                                        <span class="h-1.5 w-1.5 rounded-full bg-[#10B981]"></span>
                                        <span>Real-Time Gate Sync</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Footer Status -->
                            <div class="pt-6 border-t border-[#E7E9EE] flex items-center justify-between text-[11px] font-semibold text-[#5B6270]">
                                <span>Zero Shared Tables</span>
                                <span class="raibix-dot h-1.5 w-1.5"></span>
                            </div>
                        </div>

                    </div>
                </div>
            </section>

            <!-- ===============================================================
                 4. INFINITE LOGO MARQUEE
                 Continuously scrolling horizontal strip using actual logo file
                 Seamless looping, consistent sizing, pause on hover.
                 =============================================================== -->
            <section class="border-y border-[#E7E9EE] bg-white py-8 overflow-hidden select-none" aria-label="Raibix Brand Marquee">
                <div class="flex w-max animate-marquee space-x-16 sm:space-x-24">
                    <!-- Duplicated identical instances for seamless infinite loop -->
                    <div v-for="n in 2" :key="n" class="flex items-center space-x-16 sm:space-x-24 shrink-0">
                        <div v-for="i in 10" :key="i" class="flex items-center space-x-16 sm:space-x-24 shrink-0">
                            <img 
                                src="/images/raibix.png" 
                                alt="Raibix" 
                                class="h-7 sm:h-8 w-auto object-contain opacity-85 hover:opacity-100 transition-opacity"
                            />
                            <span class="h-1.5 w-1.5 rounded-full bg-[#E5E7EB]"></span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ===============================================================
                 5. FEATURES — FOUR USER ROLES
                 Top-left circular gradient element.
                 Heading: "One Platform. Every Role."
                 Supporting text: "Everything your campus needs, organized around the people who make it work."
                 Four clean, static feature cards in a single row on desktop.
                 =============================================================== -->
            <section id="features" class="relative py-24 sm:py-32 bg-white overflow-hidden scroll-mt-20">
                
                <!-- Circular Gradient Emerging from Top-Left Corner -->
                <div class="pointer-events-none absolute -top-32 -left-32 w-80 h-80 sm:w-[460px] sm:h-[460px] rounded-full bg-gradient-to-br from-[#8B5CF6]/15 via-[#2F6BFF]/10 to-transparent blur-2xl"></div>

                <div class="relative z-10 mx-auto max-w-[1240px] px-6 sm:px-10">
                    
                    <!-- Section Header -->
                    <div class="max-w-2xl text-left mb-14">
                        <span class="text-xs font-bold uppercase tracking-wider text-[#2F6BFF]">Built for Every Role</span>
                        <h2 class="mt-2 text-3xl sm:text-5xl font-black tracking-tight text-[#111318] leading-[1.05]">
                            One Platform. Every Role.
                        </h2>
                        <p class="mt-4 text-base text-[#5B6270]">
                            Everything your campus needs, organized around the people who make it work.
                        </p>
                    </div>

                    <!-- Four Clean Static Feature Cards -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 items-stretch">
                        <div 
                            v-for="role in roles" 
                            :key="role.id"
                            class="rounded-2xl border border-[#E7E9EE] bg-[#FBFBFC] hover:border-[#111318] p-7 sm:p-8 flex flex-col justify-between text-left transition-colors duration-200"
                        >
                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <span class="raibix-dot h-2 w-2"></span>
                                </div>
                                <h3 class="text-xl font-black text-[#111318] tracking-tight">
                                    {{ role.title }}
                                </h3>
                                <p class="text-xs sm:text-sm text-[#5B6270] leading-relaxed">
                                    {{ role.description }}
                                </p>
                            </div>
                        </div>
                    </div>

                </div>
            </section>

            <!-- ===============================================================
                 6. SCHOOL VIEW — REALISTIC DESKTOP & MOBILE MOCKUPS
                 Left (35-40%): Eyebrow "YOUR SCHOOL. YOUR SPACE.",
                                Heading "A Platform That Feels Yours.",
                                Description & Primary CTA.
                 Right (60-65%): Realistic desktop browser frame + overlapping mobile device
                                 displaying actual hmschool portal screenshots.
                 =============================================================== -->
            <section class="py-20 sm:py-32 bg-white border-t border-[#E7E9EE] overflow-hidden">
                <div class="mx-auto max-w-[1240px] px-6 sm:px-10">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-16 items-center">
                        
                        <!-- Left Column: 35-40% Width (lg:col-span-5) -->
                        <div class="lg:col-span-5 text-left space-y-6">
                            <div class="space-y-3">
                                <div class="text-[11px] font-bold tracking-widest uppercase text-[#2F6BFF] flex items-center gap-2">
                                    <span class="raibix-dot h-1.5 w-1.5"></span>
                                    <span>YOUR SCHOOL. YOUR SPACE.</span>
                                </div>
                                <h2 class="text-3xl sm:text-4xl md:text-5xl font-black tracking-tight text-[#111318] leading-[1.08]">
                                    A Platform That Feels Yours.
                                </h2>
                                <p class="text-sm sm:text-base text-[#5B6270] leading-relaxed max-w-md">
                                    Your school's identity, presented through its own dedicated digital space. Raibix powers the experience behind it.
                                </p>
                            </div>

                            <div class="pt-2">
                                <Link 
                                    href="/checkout/essential" 
                                    class="group inline-flex items-center gap-2.5 rounded-full bg-[#111318] hover:bg-[#2F6BFF] text-white px-7 py-3.5 text-xs sm:text-sm font-bold transition-all shadow-sm cursor-pointer"
                                >
                                    <span>Explore Your School Portal</span>
                                    <span class="raibix-dot h-1.5 w-1.5 transition-transform group-hover:scale-125"></span>
                                </Link>
                            </div>
                        </div>

                        <!-- Right Column: 60-65% Width (lg:col-span-7) Realistic Device Mockups -->
                        <div class="lg:col-span-7 relative flex items-center justify-center pt-4 sm:pt-0">
                            
                            <!-- Soft subtle background aura -->
                            <div class="pointer-events-none absolute -top-12 -right-12 w-80 h-80 sm:w-96 sm:h-96 rounded-full bg-gradient-to-tr from-[#8B5CF6]/10 via-[#2F6BFF]/10 to-transparent blur-3xl -z-10"></div>

                            <img 
                                src="/images/5b8b531d-7d3e-42a5-a323-5095dd071203.png" 
                                alt="School Portal Multi-Device Mockup" 
                                class="w-full h-auto object-contain select-none"
                                loading="lazy"
                            />

                        </div>

                    </div>
                </div>
            </section>

            <!-- ===============================================================
                 7. BENEFITS — ANIMATED INTELLIGENCE CORE
                 Centered Intelligence Core section matching hero visual language.
                 Reuses exact same 5-layer animated gradient circle as Hero.
                 Symmetrical SVG connection lines passing behind the circle.
                 Clean, balanced secondary feature labels around the Core.
                 =============================================================== -->
            <section class="py-24 sm:py-32 bg-white border-t border-[#E7E9EE] overflow-hidden">
                <div class="mx-auto max-w-[1240px] px-6 sm:px-10">
                    
                    <!-- Heading & Copy -->
                    <div class="max-w-2xl text-left mb-14">
                        <div class="text-[11px] font-bold tracking-widest uppercase text-[#2F6BFF] flex items-center gap-2">
                            <span class="raibix-dot h-1.5 w-1.5"></span>
                            <span>CORE INTEGRATION</span>
                        </div>
                        <h2 class="mt-2 text-3xl sm:text-5xl font-black tracking-tight text-[#111318] leading-[1.05]">
                            Intelligence at the Center.
                        </h2>
                        <p class="mt-4 text-base text-[#5B6270]">
                            One connected foundation for the essential functions of your institution.
                        </p>
                    </div>

                    <!-- Energy-Line Canvas & Central Animated Core Circle -->
                    <div class="relative w-full rounded-3xl border border-[#E7E9EE] bg-[#FBFBFC] p-6 sm:p-12 lg:p-16 overflow-hidden min-h-[480px] sm:min-h-[540px] lg:min-h-[580px] flex items-center justify-center select-none">
                        
                        <!-- SVG Connection Lines: Symmetrical, curved near circle, flattening towards labels, passing strictly behind circle -->
                        <div class="absolute inset-0 pointer-events-none z-10 flex items-center justify-center">
                            <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid meet" viewBox="0 0 1200 600" fill="none">
                                <defs>
                                    <!-- Left Connection Line Gradient: Violet near center to Electric Blue -->
                                    <linearGradient id="coreLineLeft" x1="100%" y1="50%" x2="0%" y2="50%">
                                        <stop offset="0%" stop-color="#8B5CF6" stop-opacity="0.45" />
                                        <stop offset="60%" stop-color="#2F6BFF" stop-opacity="0.35" />
                                        <stop offset="100%" stop-color="#2F6BFF" stop-opacity="0.2" />
                                    </linearGradient>

                                    <!-- Right Connection Line Gradient: Violet near center to Electric Blue -->
                                    <linearGradient id="coreLineRight" x1="0%" y1="50%" x2="100%" y2="50%">
                                        <stop offset="0%" stop-color="#8B5CF6" stop-opacity="0.45" />
                                        <stop offset="60%" stop-color="#2F6BFF" stop-opacity="0.35" />
                                        <stop offset="100%" stop-color="#2F6BFF" stop-opacity="0.2" />
                                    </linearGradient>

                                    <!-- Technical Orbit Ring Gradient -->
                                    <linearGradient id="ringGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                        <stop offset="0%" stop-color="#8B5CF6" stop-opacity="0.2" />
                                        <stop offset="100%" stop-color="#2F6BFF" stop-opacity="0.2" />
                                    </linearGradient>
                                </defs>

                                <!-- Concentric Precision Orbit Rings Behind Center Circle -->
                                <circle cx="600" cy="300" r="190" stroke="url(#ringGrad)" stroke-width="1" stroke-dasharray="4 6" opacity="0.6" />
                                <circle cx="600" cy="300" r="235" stroke="#E7E9EE" stroke-width="1" opacity="0.5" />

                                <!-- 6 Balanced Symmetrical Connection Paths -->
                                <!-- 1. Top-Left: Administration -->
                                <path d="M 500 250 C 400 210, 260 140, 80 140" stroke="url(#coreLineLeft)" stroke-width="1.5" stroke-linecap="round" />
                                <circle cx="80" cy="140" r="3" fill="#8B5CF6" opacity="0.7" />

                                <!-- 2. Mid-Left: Academic Workflows -->
                                <path d="M 460 300 C 360 300, 220 300, 60 300" stroke="url(#coreLineLeft)" stroke-width="1.5" stroke-linecap="round" />
                                <circle cx="60" cy="300" r="3" fill="#2F6BFF" opacity="0.7" />

                                <!-- 3. Bottom-Left: Attendance -->
                                <path d="M 500 350 C 400 390, 260 460, 80 460" stroke="url(#coreLineLeft)" stroke-width="1.5" stroke-linecap="round" />
                                <circle cx="80" cy="460" r="3" fill="#10B981" opacity="0.7" />

                                <!-- 4. Top-Right: Financial Operations -->
                                <path d="M 700 250 C 800 210, 940 140, 1120 140" stroke="url(#coreLineRight)" stroke-width="1.5" stroke-linecap="round" />
                                <circle cx="1120" cy="140" r="3" fill="#F59E0B" opacity="0.7" />

                                <!-- 5. Mid-Right: Secure Data -->
                                <path d="M 740 300 C 840 300, 980 300, 1140 300" stroke="url(#coreLineRight)" stroke-width="1.5" stroke-linecap="round" />
                                <circle cx="1140" cy="300" r="3" fill="#6366F1" opacity="0.7" />

                                <!-- 6. Bottom-Right: Connected Access -->
                                <path d="M 700 350 C 800 390, 940 460, 1120 460" stroke="url(#coreLineRight)" stroke-width="1.5" stroke-linecap="round" />
                                <circle cx="1120" cy="460" r="3" fill="#EC4899" opacity="0.7" />
                            </svg>
                        </div>

                        <!-- Central Intelligence Core Circle (Exact same 5-layer animated gradient mesh as Hero) -->
                        <div class="relative z-20 flex flex-col items-center justify-center">
                            
                            <!-- Subtle outer glow aura -->
                            <div class="pointer-events-none absolute -inset-6 sm:-inset-10 rounded-full bg-gradient-to-tr from-[#8B5CF6]/25 via-[#2F6BFF]/20 to-transparent blur-2xl -z-10 animate-pulse"></div>

                            <!-- Perfectly Circular Frame with Soft Border & Drop Shadow -->
                            <div class="w-48 h-48 sm:w-60 sm:h-60 md:w-72 md:h-72 lg:w-80 lg:h-80 rounded-full p-2 sm:p-2.5 bg-white/95 shadow-[0_25px_60px_-15px_rgba(47,107,255,0.25)] border border-[#E7E9EE] flex items-center justify-center relative">
                                
                                <!-- Exact Multi-Layer Animated Gradient Circle from Hero -->
                                <div class="w-full h-full rounded-full relative overflow-hidden flex items-center justify-center">
                                    <!-- Layer 1: Ambient Slowly Rotating Conic Gradient -->
                                    <div class="absolute -inset-10 bg-[conic-gradient(from_0deg,#8B5CF6_0%,#2F6BFF_30%,#38BDF8_50%,#8B5CF6_70%,#6366F1_100%)] opacity-35 mix-blend-color-dodge filter blur-2xl animate-orb-spin"></div>
                                    
                                    <!-- Layer 2: Organic Floating Violet/Purple Pulse -->
                                    <div class="absolute top-[8%] left-[22%] w-[60%] h-[60%] rounded-full bg-radial from-[#8B5CF6] via-[#7C3AED]/70 to-transparent filter blur-2xl animate-orb-blob-1"></div>
                                    
                                    <!-- Layer 3: Organic Floating Electric Blue Pulse -->
                                    <div class="absolute bottom-[10%] right-[15%] w-[68%] h-[68%] rounded-full bg-radial from-[#2F6BFF] via-[#3B82F6]/75 to-transparent filter blur-2xl animate-orb-blob-2"></div>
                                    
                                    <!-- Layer 4: Original Shared Raibix Orb Asset with Shadow & Rim -->
                                    <img 
                                        src="/images/raibix_orb.png" 
                                        alt="Raibix Intelligence Core" 
                                        class="w-full h-full object-contain relative z-10 opacity-80 mix-blend-multiply animate-orb-horizon drop-shadow-[0_15px_35px_rgba(139,92,246,0.25)]"
                                    />
                                    
                                    <!-- Layer 5: Top Shimmer Wave Overlay -->
                                    <div class="absolute inset-0 z-20 rounded-full bg-gradient-to-tr from-[#7C3AED]/20 via-[#2F6BFF]/15 to-[#8B5CF6]/20 mix-blend-overlay animate-gradient-flow pointer-events-none"></div>
                                </div>
                            </div>

                            <!-- Mobile / Tablet Feature Labels (clean responsive pills beneath the circle) -->
                            <div class="mt-8 flex lg:hidden flex-wrap items-center justify-center gap-2.5 sm:gap-3 max-w-lg">
                                <div 
                                    v-for="feature in coreFeatures" 
                                    :key="feature.id"
                                    class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white border border-[#E7E9EE] text-xs font-semibold text-[#111318] shadow-2xs"
                                >
                                    <span class="h-2 w-2 rounded-full shrink-0" :style="{ backgroundColor: feature.color }"></span>
                                    <span>{{ feature.label }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Desktop Feature Labels: Positioned at terminals of connection lines -->
                        <div class="hidden lg:block">
                            <!-- 1. Top-Left: Administration -->
                            <div class="absolute top-[23%] left-4 xl:left-8 -translate-y-1/2 z-30 inline-flex items-center gap-2.5 px-4 py-2 rounded-full bg-white/95 backdrop-blur-xs border border-[#E7E9EE] hover:border-[#111318]/30 hover:shadow-xs transition-all duration-200 cursor-default">
                                <span class="h-2 w-2 rounded-full shrink-0 bg-[#8B5CF6]"></span>
                                <span class="text-xs font-semibold text-[#111318]">Administration</span>
                            </div>

                            <!-- 2. Mid-Left: Academic Workflows -->
                            <div class="absolute top-[50%] left-2 xl:left-5 -translate-y-1/2 z-30 inline-flex items-center gap-2.5 px-4 py-2 rounded-full bg-white/95 backdrop-blur-xs border border-[#E7E9EE] hover:border-[#111318]/30 hover:shadow-xs transition-all duration-200 cursor-default">
                                <span class="h-2 w-2 rounded-full shrink-0 bg-[#2F6BFF]"></span>
                                <span class="text-xs font-semibold text-[#111318]">Academic Workflows</span>
                            </div>

                            <!-- 3. Bottom-Left: Attendance -->
                            <div class="absolute top-[77%] left-4 xl:left-8 -translate-y-1/2 z-30 inline-flex items-center gap-2.5 px-4 py-2 rounded-full bg-white/95 backdrop-blur-xs border border-[#E7E9EE] hover:border-[#111318]/30 hover:shadow-xs transition-all duration-200 cursor-default">
                                <span class="h-2 w-2 rounded-full shrink-0 bg-[#10B981]"></span>
                                <span class="text-xs font-semibold text-[#111318]">Attendance</span>
                            </div>

                            <!-- 4. Top-Right: Financial Operations -->
                            <div class="absolute top-[23%] right-4 xl:right-8 -translate-y-1/2 z-30 inline-flex items-center gap-2.5 px-4 py-2 rounded-full bg-white/95 backdrop-blur-xs border border-[#E7E9EE] hover:border-[#111318]/30 hover:shadow-xs transition-all duration-200 cursor-default">
                                <span class="h-2 w-2 rounded-full shrink-0 bg-[#F59E0B]"></span>
                                <span class="text-xs font-semibold text-[#111318]">Financial Operations</span>
                            </div>

                            <!-- 5. Mid-Right: Secure Data -->
                            <div class="absolute top-[50%] right-2 xl:right-5 -translate-y-1/2 z-30 inline-flex items-center gap-2.5 px-4 py-2 rounded-full bg-white/95 backdrop-blur-xs border border-[#E7E9EE] hover:border-[#111318]/30 hover:shadow-xs transition-all duration-200 cursor-default">
                                <span class="h-2 w-2 rounded-full shrink-0 bg-[#6366F1]"></span>
                                <span class="text-xs font-semibold text-[#111318]">Secure Data</span>
                            </div>

                            <!-- 6. Bottom-Right: Connected Access -->
                            <div class="absolute top-[77%] right-4 xl:right-8 -translate-y-1/2 z-30 inline-flex items-center gap-2.5 px-4 py-2 rounded-full bg-white/95 backdrop-blur-xs border border-[#E7E9EE] hover:border-[#111318]/30 hover:shadow-xs transition-all duration-200 cursor-default">
                                <span class="h-2 w-2 rounded-full shrink-0 bg-[#EC4899]"></span>
                                <span class="text-xs font-semibold text-[#111318]">Connected Access</span>
                            </div>
                        </div>

                    </div>

                </div>
            </section>

            <!-- ===============================================================
                 8. PRICING — THREE PLANS IN ONE CONTAINER
                 Heading: "Find Your Campus Plan."
                 Copy: "Choose the plan that matches your institution's needs."
                 ONE large enclosed container with 3 cards side-by-side:
                 Starter, Essential (Active / Highlighted), Signature.
                 Hierarchy: Raibix dot, Plan Name, Price, Features, CTA.
                 Compact horizontal panel beneath: "Not Sure Which Plan Fits?"
                 =============================================================== -->
            <section id="pricing" class="py-24 sm:py-32 bg-white border-t border-[#E7E9EE] scroll-mt-20">
                <div class="mx-auto max-w-[1240px] px-6 sm:px-10">
                    
                    <!-- Section Heading & Annual/Monthly Switch -->
                    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-14 text-left">
                        <div>
                            <span class="text-xs font-bold uppercase tracking-wider text-[#2F6BFF]">Campus Subscriptions</span>
                            <h2 class="mt-2 text-3xl sm:text-5xl font-black tracking-tight text-[#111318] leading-[1.05]">
                                Find Your Campus Plan.
                            </h2>
                            <p class="mt-3 text-base text-[#5B6270]">
                                Choose the plan that matches your institution's needs.
                            </p>
                        </div>

                        <!-- Billing Toggle -->
                        <div class="flex items-center gap-2 rounded-full border border-[#E7E9EE] bg-[#FBFBFC] p-1.5 self-start">
                            <button 
                                @click="isAnnual = false"
                                type="button" 
                                :class="[
                                    'px-5 py-2 rounded-full text-xs font-bold transition-all cursor-pointer',
                                    !isAnnual ? 'bg-[#111318] text-white shadow-xs' : 'text-[#5B6270] hover:text-[#111318]'
                                ]"
                            >
                                Monthly
                            </button>
                            <button 
                                @click="isAnnual = true"
                                type="button" 
                                :class="[
                                    'px-5 py-2 rounded-full text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5',
                                    isAnnual ? 'bg-[#111318] text-white shadow-xs' : 'text-[#5B6270] hover:text-[#111318]'
                                ]"
                            >
                                <span>Annual</span>
                                <span class="rounded-full bg-[#15803D] text-white text-[10px] px-2 py-0.5 font-extrabold">Save 20%</span>
                            </button>
                        </div>
                    </div>

                    <!-- ONE Large Enclosed Pricing Container (As specified) -->
                    <div class="rounded-3xl border border-[#E7E9EE] bg-[#F7F8FA] p-6 sm:p-10 space-y-10">
                        
                        <!-- Three Plans Side by Side Inside Container -->
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 sm:gap-8 items-stretch">
                            
                            <div 
                                v-for="plan in plansList" 
                                :key="plan.id"
                                :class="[
                                    'rounded-2xl p-8 flex flex-col justify-between text-left transition-all duration-200',
                                    plan.featured 
                                        ? 'bg-white border border-[#111318] shadow-md ring-1 ring-[#111318]' 
                                        : 'bg-[#FBFBFC] border border-[#E7E9EE]'
                                ]"
                            >
                                <div>
                                    <!-- 1. Small Circular Dot with Raibix Gradient -->
                                    <div class="flex items-center justify-between pb-4 border-b border-[#E7E9EE]">
                                        <div class="flex items-center gap-2.5">
                                            <span class="raibix-dot h-2.5 w-2.5"></span>
                                            <!-- 2. Prominent Plan Name -->
                                            <span class="text-sm font-extrabold text-[#111318] tracking-tight">{{ plan.name }}</span>
                                        </div>
                                        <span v-if="plan.featured" class="text-[10px] font-bold uppercase tracking-wider text-[#2F6BFF] bg-[#2F6BFF]/10 px-2 py-0.5 rounded-full">
                                            Recommended
                                        </span>
                                    </div>

                                    <!-- 3. Actual Price & Billing Frequency -->
                                    <div class="mt-6 flex items-baseline gap-2">
                                        <span class="text-4xl sm:text-5xl font-black text-[#111318] tracking-tight">
                                            {{ Math.round(getDisplayPrice(plan) / 1000) }}k
                                        </span>
                                        <span class="text-xs font-semibold text-[#6B7280]">
                                            PKR / month
                                        </span>
                                    </div>

                                    <p class="mt-2 text-xs text-[#5B6270] leading-relaxed">
                                        {{ plan.description }}
                                    </p>

                                    <!-- 4. Concise List of Included Features -->
                                    <div class="mt-6 space-y-2.5 pt-5 border-t border-[#E7E9EE]">
                                        <div 
                                            v-for="(feature, fIdx) in plan.features" 
                                            :key="fIdx"
                                            class="flex items-start gap-2.5 text-xs text-[#111318]"
                                        >
                                            <span class="mt-1 h-1 w-1 rounded-full bg-[#111318] shrink-0"></span>
                                            <span class="leading-relaxed">{{ feature }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- 5. Clear CTA -->
                                <div class="mt-8 pt-5 border-t border-[#E7E9EE]">
                                    <Link 
                                        :href="`/checkout/${plan.code}`"
                                        :class="[
                                            'w-full flex items-center justify-center gap-2 rounded-full py-3.5 text-xs font-bold transition-all duration-200 cursor-pointer',
                                            plan.featured 
                                                ? 'bg-[#111318] hover:bg-[#2F6BFF] text-white shadow-xs' 
                                                : 'bg-white hover:bg-[#111318] hover:text-white text-[#111318] border border-[#E7E9EE]'
                                        ]"
                                    >
                                        <span>Get {{ plan.name }}</span>
                                        <span v-if="plan.featured" class="raibix-dot h-1.5 w-1.5"></span>
                                    </Link>
                                </div>
                            </div>

                        </div>

                        <!-- Compact Horizontal Expert Assistance Panel (Beneath the 3 Cards) -->
                        <div class="rounded-2xl border border-[#E7E9EE] bg-white p-6 sm:p-8 flex flex-col md:flex-row md:items-center justify-between gap-6 text-left shadow-2xs">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="raibix-dot h-2 w-2"></span>
                                    <h3 class="text-base sm:text-lg font-bold text-[#111318]">
                                        Not Sure Which Plan Fits?
                                    </h3>
                                </div>
                                <p class="text-xs sm:text-sm text-[#5B6270]">
                                    Let's find the right starting point for your institution.
                                </p>
                            </div>

                            <button 
                                @click="expertModalOpen = true"
                                type="button" 
                                class="inline-flex items-center gap-2 rounded-full bg-[#111318] hover:bg-[#2F6BFF] text-white px-6 py-3 text-xs font-bold transition-colors cursor-pointer shrink-0 shadow-xs"
                            >
                                <span>Talk to an Expert</span>
                                <span class="raibix-dot h-1.5 w-1.5"></span>
                            </button>
                        </div>

                    </div>

                </div>
            </section>

            <!-- ===============================================================
                 9. FAQS & CONTACT FORM
                 Left column: Frequently Asked Questions accordion.
                 Right column: Contact Us with Full Name, Work Email, School, Message.
                 =============================================================== -->
            <section id="contact" class="py-24 sm:py-32 bg-white border-t border-[#E7E9EE] scroll-mt-20">
                <div class="mx-auto max-w-[1240px] px-6 sm:px-10">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-16 text-left">
                        
                        <!-- Left Column: Frequently Asked Questions -->
                        <div class="lg:col-span-6 space-y-8">
                            <div>
                                <span class="text-xs font-bold uppercase tracking-wider text-[#2F6BFF]">Clarity & Trust</span>
                                <h2 class="mt-2 text-3xl sm:text-4xl font-black tracking-tight text-[#111318]">
                                    Frequently Asked Questions
                                </h2>
                            </div>

                            <div class="space-y-4">
                                <div 
                                    v-for="(faq, fIdx) in faqs" 
                                    :key="fIdx"
                                    class="border-b border-[#E7E9EE] pb-4"
                                >
                                    <button 
                                        @click="toggleFaq(fIdx)"
                                        type="button"
                                        class="w-full flex items-center justify-between text-left py-2 font-bold text-sm sm:text-base text-[#111318] cursor-pointer group"
                                    >
                                        <span class="pr-4 group-hover:text-[#2F6BFF] transition-colors">{{ faq.question }}</span>
                                        <span class="text-lg font-light text-[#6B7280] shrink-0 transition-transform duration-200">
                                            {{ activeFaqIndex === fIdx ? '×' : '+' }}
                                        </span>
                                    </button>
                                    <div 
                                        v-if="activeFaqIndex === fIdx"
                                        class="mt-2 text-xs sm:text-sm text-[#5B6270] leading-relaxed pr-6 animate-fadeIn"
                                    >
                                        {{ faq.answer }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Contact Us Form -->
                        <div class="lg:col-span-6 lg:border-l lg:border-[#E7E9EE] lg:pl-16 space-y-8">
                            <div>
                                <span class="text-xs font-bold uppercase tracking-wider text-[#2F6BFF]">Direct Campus Inquiries</span>
                                <h2 class="mt-2 text-3xl sm:text-4xl font-black tracking-tight text-[#111318]">
                                    Let's Talk About Your Campus.
                                </h2>
                                <p class="mt-2 text-xs sm:text-sm text-[#5B6270]">
                                    Have a question or need help choosing a plan? Send us a message.
                                </p>
                            </div>

                            <form @submit.prevent="submitContact" class="space-y-5">
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-[#111318] mb-1.5">
                                        Full Name
                                    </label>
                                    <input 
                                        v-model="contactForm.name" 
                                        type="text" 
                                        placeholder="Full Name"
                                        class="w-full rounded-xl border border-[#E7E9EE] bg-[#FBFBFC] px-4 py-3 text-xs sm:text-sm text-[#111318] focus:border-[#111318] focus:bg-white focus:outline-none transition-colors"
                                    />
                                    <p v-if="contactErrors.name" class="mt-1 text-xs text-[#B91C1C]">{{ contactErrors.name }}</p>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-[#111318] mb-1.5">
                                        Work Email
                                    </label>
                                    <input 
                                        v-model="contactForm.email" 
                                        type="email" 
                                        placeholder="Work Email Address"
                                        class="w-full rounded-xl border border-[#E7E9EE] bg-[#FBFBFC] px-4 py-3 text-xs sm:text-sm text-[#111318] focus:border-[#111318] focus:bg-white focus:outline-none transition-colors"
                                    />
                                    <p v-if="contactErrors.email" class="mt-1 text-xs text-[#B91C1C]">{{ contactErrors.email }}</p>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-[#111318] mb-1.5">
                                        School or Institution
                                    </label>
                                    <input 
                                        v-model="contactForm.institution" 
                                        type="text" 
                                        placeholder="School or Institution Name"
                                        class="w-full rounded-xl border border-[#E7E9EE] bg-[#FBFBFC] px-4 py-3 text-xs sm:text-sm text-[#111318] focus:border-[#111318] focus:bg-white focus:outline-none transition-colors"
                                    />
                                    <p v-if="contactErrors.institution" class="mt-1 text-xs text-[#B91C1C]">{{ contactErrors.institution }}</p>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-[#111318] mb-1.5">
                                        Message
                                    </label>
                                    <textarea 
                                        v-model="contactForm.message" 
                                        rows="4" 
                                        placeholder="Tell us about student count, turnstiles, and timeline..."
                                        class="w-full rounded-xl border border-[#E7E9EE] bg-[#FBFBFC] px-4 py-3 text-xs sm:text-sm text-[#111318] focus:border-[#111318] focus:bg-white focus:outline-none transition-colors resize-none"
                                    ></textarea>
                                    <p v-if="contactErrors.message" class="mt-1 text-xs text-[#B91C1C]">{{ contactErrors.message }}</p>
                                </div>

                                <div>
                                    <button 
                                        type="submit" 
                                        :disabled="contactSubmitting"
                                        class="w-full rounded-xl bg-[#111318] hover:bg-[#2F6BFF] py-3.5 text-xs sm:text-sm font-bold text-white transition-colors cursor-pointer flex items-center justify-center gap-2"
                                    >
                                        <span v-if="!contactSubmitting">Send Inquiry</span>
                                        <span v-else>Transmitting...</span>
                                        <span class="raibix-dot h-1.5 w-1.5"></span>
                                    </button>
                                </div>

                                <div v-if="contactSuccess" class="rounded-xl border border-[#15803D]/20 bg-[#15803D]/10 p-3 text-xs text-[#15803D] font-semibold text-center">
                                    Message received. A campus systems specialist will respond within two hours.
                                </div>
                            </form>
                        </div>

                    </div>
                </div>
            </section>

        </main>

        <!-- ===================================================================
             10. FOOTER
             Compact, clean, white background.
             Actual Raibix Logo, brand description, navigation links,
             sign-in, Privacy Policy, Terms, Copyright.
             =================================================================== -->
        <footer id="footer" class="border-t border-[#E7E9EE] bg-white pt-14 pb-12 text-left">
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
                            <li><button @click="scrollToSection('features')" class="hover:text-[#111318] transition-colors cursor-pointer">Features & Roles</button></li>
                            <li><button @click="scrollToSection('pricing')" class="hover:text-[#111318] transition-colors cursor-pointer">Campus Plans</button></li>
                            <li><button @click="scrollToSection('contact')" class="hover:text-[#111318] transition-colors cursor-pointer">Contact & FAQ</button></li>
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
                        <Link href="/privacy" class="hover:text-[#111318] transition-colors">Privacy Policy</Link>
                        <Link href="/terms" class="hover:text-[#111318] transition-colors">Terms of Service</Link>
                        <Link href="/security" class="hover:text-[#111318] transition-colors">Security & Privacy</Link>
                    </div>
                </div>
            </div>
        </footer>

        <!-- ===================================================================
             1. BOTTOM NAVIGATION BAR (FIXED AT BOTTOM OF VIEWPORT)
             Slim, translucent or white, fine border.
             3 Items: Features, Pricing, Contact.
             Active section indicated with subtle visual state using the Raibix gradient dot.
             Never obstructs content; safe areas supported.
             =================================================================== -->
        <nav 
            v-show="!footerInView"
            class="fixed bottom-6 left-1/2 -translate-x-1/2 z-40 bg-white/90 backdrop-blur-md border border-[#E7E9EE] shadow-lg rounded-full px-3 py-1.5 flex items-center gap-1 sm:gap-2 transition-all duration-300"
            aria-label="Bottom Quick Navigation"
        >
            <button 
                @click="scrollToSection('features')"
                type="button" 
                :class="[
                    'flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-semibold transition-all duration-200 cursor-pointer',
                    activeSection === 'features' 
                        ? 'bg-[#111318] text-white shadow-2xs' 
                        : 'text-[#5B6270] hover:text-[#111318]'
                ]"
            >
                <span v-if="activeSection === 'features'" class="raibix-dot h-1.5 w-1.5"></span>
                <span>Features</span>
            </button>

            <button 
                @click="scrollToSection('pricing')"
                type="button" 
                :class="[
                    'flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-semibold transition-all duration-200 cursor-pointer',
                    activeSection === 'pricing' 
                        ? 'bg-[#111318] text-white shadow-2xs' 
                        : 'text-[#5B6270] hover:text-[#111318]'
                ]"
            >
                <span v-if="activeSection === 'pricing'" class="raibix-dot h-1.5 w-1.5"></span>
                <span>Pricing</span>
            </button>

            <button 
                @click="scrollToSection('contact')"
                type="button" 
                :class="[
                    'flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-semibold transition-all duration-200 cursor-pointer',
                    activeSection === 'contact' 
                        ? 'bg-[#111318] text-white shadow-2xs' 
                        : 'text-[#5B6270] hover:text-[#111318]'
                ]"
            >
                <span v-if="activeSection === 'contact'" class="raibix-dot h-1.5 w-1.5"></span>
                <span>Contact</span>
            </button>
        </nav>

        <!-- ===================================================================
             EXPERT CONSULTATION MODAL (TRIGGERED BY "TALK TO AN EXPERT")
             =================================================================== -->
        <div v-if="expertModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 sm:p-8 text-left border border-[#E7E9EE] shadow-2xl space-y-5">
                <div class="flex items-center justify-between border-b border-[#E7E9EE] pb-3">
                    <div class="flex items-center gap-2">
                        <span class="raibix-dot h-2 w-2"></span>
                        <span class="text-sm font-bold text-[#111318]">Talk to an Expert</span>
                    </div>
                    <button @click="expertModalOpen = false" class="text-lg font-light text-[#6B7280] hover:text-[#111318] cursor-pointer">×</button>
                </div>

                <div v-if="!expertSubmitted" class="space-y-4">
                    <p class="text-xs text-[#5B6270]">
                        Let's find the right starting point for your institution. Our solutions architect will contact you.
                    </p>

                    <div>
                        <label class="block text-[11px] font-bold uppercase text-[#111318] mb-1">Your Full Name</label>
                        <input v-model="expertForm.name" type="text" placeholder="Full Name" class="w-full rounded-lg border border-[#E7E9EE] p-2.5 text-xs text-[#111318] focus:outline-none" />
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold uppercase text-[#111318] mb-1">Mobile / WhatsApp</label>
                        <input v-model="expertForm.phone" type="tel" placeholder="Mobile / WhatsApp Number" class="w-full rounded-lg border border-[#E7E9EE] p-2.5 text-xs text-[#111318] focus:outline-none" />
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold uppercase text-[#111318] mb-1">School / Institution</label>
                        <input v-model="expertForm.institution" type="text" placeholder="School / Institution Name" class="w-full rounded-lg border border-[#E7E9EE] p-2.5 text-xs text-[#111318] focus:outline-none" />
                    </div>

                    <button 
                        @click="submitExpert" 
                        type="button" 
                        class="w-full rounded-full bg-[#111318] hover:bg-[#2F6BFF] py-3 text-xs font-bold text-white transition-colors cursor-pointer"
                    >
                        Schedule Free Consultation
                    </button>
                </div>

                <div v-else class="text-center py-6 space-y-2">
                    <span class="raibix-dot h-4 w-4 mx-auto"></span>
                    <div class="text-sm font-bold text-[#111318]">Consultation Scheduled</div>
                    <p class="text-xs text-[#5B6270]">An institutional solutions architect will call within 30 minutes.</p>
                </div>
            </div>
        </div>

    </div>
</template>
