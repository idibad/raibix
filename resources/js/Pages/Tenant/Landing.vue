<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    school: {
        type: Object,
        default: () => ({})
    },
    classes: {
        type: Array,
        default: () => []
    },
    stats: {
        type: Object,
        default: () => ({})
    }
});

const form = useForm({
    student_name: '',
    previous_school: '',
    desired_class_id: '',
    parent_name: '',
    parent_phone: '',
    parent_email: '',
    notes: ''
});

const showMobileMenu = ref(false);
const activeFaq = ref(0);
const activePortalTab = ref('student');

const schoolType = computed(() => (props.school?.type || 'School').toString());

const clientLogoBrightness = ref(props.school?.logo_brightness || null);

// Predictable Navbar Theme:
// If the logo is light -> navbar is dark!
// If the logo is dark  -> navbar is light!
const isDarkNavbar = computed(() => {
    // 1. Explicit setting chosen by the Principal
    const pref = props.school?.navbar_theme || props.school?.logo_bg;
    if (pref === 'dark') return true;
    if (pref === 'light') return false;

    // 2. Automated contrast:
    // Light logo -> Dark navbar!
    // Dark logo -> Light navbar!
    const brightness = clientLogoBrightness.value || props.school?.logo_brightness;
    if (brightness === 'light') return true;
    if (brightness === 'dark') return false;

    // Default to clean light navbar
    return false;
});

const logoBg = computed(() => props.school?.logo_bg || 'auto');
const logoBgStyle = computed(() => {
    if (logoBg.value === 'dark' || (logoBg.value === 'auto' && isDarkNavbar.value)) {
        return { backgroundColor: '#0F172A', borderColor: '#1E293B' };
    }
    if (logoBg.value === 'brand') {
        return { backgroundColor: primaryColor.value, borderColor: 'rgba(0,0,0,0.15)' };
    }
    return { backgroundColor: '#FFFFFF', borderColor: '#E2E8F0' };
});

const contactPhone = computed(() => props.school?.phone || null);
const contactEmail = computed(() => props.school?.email || null);
const contactAddress = computed(() => props.school?.address || null);

const extractedColors = ref(null);
const primaryColor = computed(() => extractedColors.value?.primary || props.school?.cover_color || '#1A73E8');
const secondaryColor = computed(() => extractedColors.value?.secondary || props.school?.secondary_color || '#0F172A');
const highlightColor = computed(() => extractedColors.value?.accent || props.school?.accent_color || '#3B82F6');
const accentColor = computed(() => primaryColor.value);

onMounted(() => {
    // Auto-detect logo brightness & extract color palette
    const hasCustomColors = props.school?.cover_color && props.school.cover_color !== '#1A73E8';
    if (props.school?.logo) {
        const img = new Image();
        img.crossOrigin = 'Anonymous';
        img.src = props.school.logo;
        img.onload = () => {
            try {
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');
                canvas.width = img.width || 120;
                canvas.height = img.height || 120;
                ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height).data;
                
                let totalLum = 0;
                let solidPixels = 0;
                const counts = {};

                for (let i = 0; i < imageData.length; i += 16) {
                    const alpha = imageData[i + 3];
                    if (alpha < 80) continue; // Skip transparent pixels

                    const r = imageData[i];
                    const g = imageData[i + 1];
                    const b = imageData[i + 2];
                    const lum = 0.299 * r + 0.587 * g + 0.114 * b;
                    totalLum += lum;
                    solidPixels++;

                    if (lum <= 245 && lum >= 15) {
                        const qr = Math.round(r / 32) * 32;
                        const qg = Math.round(g / 32) * 32;
                        const qb = Math.round(b / 32) * 32;
                        const key = `${qr},${qg},${qb}`;
                        counts[key] = (counts[key] || { count: 0, rgb: [qr, qg, qb] });
                        counts[key].count++;
                    }
                }

                // If brightness was not already established from backend, calculate it client-side
                if (solidPixels > 0 && !clientLogoBrightness.value) {
                    const avgLuminance = totalLum / solidPixels;
                    // Luminance > 130 is classified as Light logo (requires Dark navbar)
                    clientLogoBrightness.value = avgLuminance > 130 ? 'light' : 'dark';
                }

                if (!hasCustomColors) {
                    const sorted = Object.values(counts).sort((a, b) => b.count - a.count);
                    if (sorted.length > 0) {
                        const toHex = ([r, g, b]) => '#' + [r, g, b].map(x => Math.min(255, Math.max(0, x)).toString(16).padStart(2, '0')).join('').toUpperCase();
                        const primary = toHex(sorted[0].rgb);
                        let secondary = '#0F172A';
                        let accent = '#3B82F6';
                        if (sorted.length > 1) {
                            for (let j = 1; j < sorted.length; j++) {
                                const [r2, g2, b2] = sorted[j].rgb;
                                if (Math.abs(r2 - sorted[0].rgb[0]) + Math.abs(g2 - sorted[0].rgb[1]) + Math.abs(b2 - sorted[0].rgb[2]) > 60) {
                                    secondary = toHex(sorted[j].rgb);
                                    break;
                                }
                            }
                        }
                        if (sorted.length > 2) {
                            for (let k = 2; k < sorted.length; k++) {
                                accent = toHex(sorted[k].rgb);
                                break;
                            }
                        }
                        extractedColors.value = { primary, secondary, accent };
                    }
                }
            } catch (e) {
                console.error('Canvas image analysis error:', e);
            }
        };
    }
});

const submitInquiry = () => {
    form.post('/api/v1/inquiries', {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};

const portals = [
    {
        id: 'student',
        title: 'Student Portal',
        subtitle: 'For active students & learners',
        icon: 'fa-user-graduate',
        color: 'from-blue-500 to-indigo-600',
        badge: 'Learner Access',
        features: [
            'Daily timetable, lecture notes & homework',
            'Live attendance tracking & tardiness alerts',
            'Download terminal examination report cards',
            'Digital exam date-sheets & syllabus updates'
        ]
    },
    {
        id: 'parent',
        title: 'Parent / Guardian Portal',
        subtitle: 'Stay connected with your child’s progress',
        icon: 'fa-users',
        color: 'from-emerald-500 to-teal-600',
        badge: 'Family Connect',
        features: [
            'Real-time fee ledger & online challan payments',
            'Automated SMS / WhatsApp attendance alerts',
            'Direct messaging with class teachers & principal',
            'Comprehensive academic growth trend analytics'
        ]
    },
    {
        id: 'teacher',
        title: 'Faculty / Teacher Portal',
        subtitle: 'Academic management & grading suite',
        icon: 'fa-chalkboard-user',
        color: 'from-amber-500 to-orange-600',
        badge: 'Academic Staff',
        features: [
            'One-click digital attendance grid recording',
            'Automated gradebook & exam mark entry',
            'Upload lesson plans, homework assignments & quizzes',
            'Instant student performance feedback reports'
        ]
    },
    {
        id: 'admin',
        title: 'Admin / Principal Portal',
        subtitle: 'Executive governance & operations',
        icon: 'fa-building-columns',
        color: 'from-purple-600 to-indigo-700',
        badge: 'Campus Command',
        features: [
            'Comprehensive financial accounting & revenue dashboard',
            'Multi-branch admissions & inquiry pipeline tracker',
            'Staff HR, attendance, payroll & leave management',
            'Automated regulatory compliance & audit trails'
        ]
    }
];

const faqs = [
    {
        question: `How do I submit an admission application for ${props.school?.name || 'this school'}?`,
        answer: 'Simply complete the quick Admission Inquiry Form at the bottom of this page. Once submitted, our admissions counselor will review your application, schedule a campus visit or entry assessment, and guide you through the enrollment procedure.'
    },
    {
        question: 'How do parents and students get their portal login credentials?',
        answer: 'Upon official enrollment and fee confirmation, our automated system generates secure login credentials (Portal ID & Password) and sends them directly to the registered parent email address and SMS/WhatsApp phone number.'
    },
    {
        question: 'Can I pay school fees via online bank transfer or mobile banking?',
        answer: 'Yes! Through the Parent Portal, families can view itemized fee vouchers, generate 1-Link / Kuickpay digital challans, and complete payments instantly via debit card, bank app, JazzCash, or Easypaisa.'
    },
    {
        question: 'What curriculum and co-curricular programs are offered?',
        answer: `${props.school?.name || 'Our campus'} offers a rigorous, holistic academic foundation aligned with international standards, complemented by STEM robotics, sports academies, debate clubs, and leadership development.`
    }
];
</script>

<template>
    <div 
        class="min-h-screen bg-[#F8FAFC] text-slate-800 font-sans antialiased selection:bg-blue-600 selection:text-white"
        :style="{ 
            '--school-primary': primaryColor,
            '--school-secondary': secondaryColor,
            '--school-accent': highlightColor
        }"
    >
        <!-- Ambient Mesh Background -->
        <div class="fixed inset-0 overflow-hidden pointer-events-none z-0">
            <div class="absolute -top-[30%] -left-[10%] w-[65vw] h-[65vw] rounded-full opacity-15 blur-[120px]" :style="{ background: primaryColor }"></div>
            <div class="absolute top-[40%] -right-[15%] w-[55vw] h-[55vw] rounded-full opacity-10 blur-[130px]" :style="{ background: secondaryColor }"></div>
            <div class="absolute -bottom-[20%] left-[20%] w-[50vw] h-[50vw] rounded-full opacity-10 blur-[140px]" :style="{ background: highlightColor }"></div>
        </div>

        <!-- Top Navigation Header -->
        <header 
            class="sticky top-0 z-50 backdrop-blur-xl transition-all duration-300"
            :class="isDarkNavbar 
                ? 'bg-[#0B0F19]/95 border-b border-slate-800/90 shadow-lg' 
                : 'bg-white/95 border-b border-slate-200/80 shadow-2xs'"
        >
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
                <!-- Brand Logo & Name -->
                <Link href="/" class="flex items-center gap-3.5 group min-w-0 max-w-[65%] sm:max-w-none">
                    <!-- Clean Logo (Directly on Navbar, No Awkward Tile Container) -->
                    <div v-if="school.logo" class="flex items-center flex-shrink-0">
                        <img 
                            :src="school.logo" 
                            :alt="school.name || 'School Logo'" 
                            class="h-10 sm:h-12 w-auto max-w-[160px] sm:max-w-[220px] object-contain transition-transform duration-200 group-hover:scale-105" 
                        />
                    </div>
                    <div 
                        v-else 
                        class="h-11 w-11 rounded-2xl flex items-center justify-center text-xl font-black text-white shadow-md transition-transform duration-300 group-hover:scale-105 flex-shrink-0" 
                        :style="{ background: accentColor }"
                    >
                        <i class="fa-solid fa-graduation-cap"></i>
                    </div>

                    <!-- School Name, Type & Tagline (Always Visible!) -->
                    <div class="min-w-0 truncate">
                        <div class="flex items-center gap-2">
                            <span 
                                class="text-lg sm:text-xl font-black tracking-tight transition-colors truncate"
                                :class="isDarkNavbar ? 'text-white group-hover:text-blue-400' : 'text-slate-900 group-hover:text-blue-600'"
                            >
                                {{ school.name || 'Campus Portal' }}
                            </span>
                            <span 
                                class="hidden sm:inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider flex-shrink-0"
                                :class="isDarkNavbar ? 'bg-white/10 text-slate-200 border border-white/15' : 'bg-blue-50 text-blue-700 border border-blue-200/60'"
                            >
                                {{ schoolType }}
                            </span>
                        </div>
                        <p 
                            class="text-xs font-medium truncate max-w-[200px] sm:max-w-md mt-0.5"
                            :class="isDarkNavbar ? 'text-slate-400' : 'text-slate-500'"
                        >
                            {{ school.tagline || 'Official Academic Portal' }}
                        </p>
                    </div>
                </Link>

                <!-- Desktop Navigation Links -->
                <nav 
                    class="hidden md:flex items-center gap-7 text-sm font-semibold transition-colors"
                    :class="isDarkNavbar ? 'text-slate-300' : 'text-slate-600'"
                >
                    <a href="#about" :class="isDarkNavbar ? 'hover:text-white' : 'hover:text-blue-600'" class="transition-colors py-1">About Campus</a>
                    <a href="#portals" :class="isDarkNavbar ? 'hover:text-white' : 'hover:text-blue-600'" class="transition-colors py-1">Portal Gateways</a>
                    <a href="#stats" :class="isDarkNavbar ? 'hover:text-white' : 'hover:text-blue-600'" class="transition-colors py-1">Excellence Metrics</a>
                    <a href="#faq" :class="isDarkNavbar ? 'hover:text-white' : 'hover:text-blue-600'" class="transition-colors py-1">FAQ</a>
                </nav>

                <!-- Desktop Action Buttons -->
                <div class="hidden sm:flex items-center gap-3">
                    <Link 
                        href="/login" 
                        :class="isDarkNavbar 
                            ? 'border-white/20 bg-white/10 text-white hover:bg-white/20 hover:border-white/30' 
                            : 'border-slate-300/80 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-400 hover:text-slate-900'"
                        class="px-5 py-2.5 rounded-full border text-sm font-bold shadow-2xs transition-all flex items-center gap-2 active:scale-95"
                    >
                        <i class="fa-solid fa-lock text-xs" :class="isDarkNavbar ? 'text-blue-400' : 'text-blue-600'"></i>
                        <span>Portal Login</span>
                    </Link>
                    <a 
                        href="#admission-inquiry" 
                        class="px-6 py-2.5 rounded-full text-white text-sm font-bold shadow-md hover:shadow-lg transition-all flex items-center gap-2 hover:scale-[1.02] active:scale-98"
                        :style="{ background: accentColor }"
                    >
                        <span>+ Admission Inquiry</span>
                        <i class="fa-solid fa-arrow-right text-xs opacity-80"></i>
                    </a>
                </div>

                <!-- Mobile Menu Button -->
                <button 
                    @click="showMobileMenu = !showMobileMenu"
                    :class="isDarkNavbar 
                        ? 'text-slate-300 hover:text-white hover:bg-white/10' 
                        : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'"
                    class="md:hidden p-2 rounded-xl transition-colors"
                >
                    <i :class="showMobileMenu ? 'fa-solid fa-xmark text-xl' : 'fa-solid fa-bars text-xl'"></i>
                </button>
            </div>

            <!-- Mobile Dropdown Menu -->
            <div 
                v-if="showMobileMenu" 
                :class="isDarkNavbar 
                    ? 'border-t border-slate-800 bg-[#0B0F19]/98 text-white' 
                    : 'border-t border-slate-200 bg-white/98 text-slate-800'"
                class="md:hidden backdrop-blur-xl px-4 pt-3 pb-6 space-y-3 animate-fadeIn shadow-xl"
            >
                <div 
                    class="flex flex-col space-y-2 text-sm font-semibold"
                    :class="isDarkNavbar ? 'text-slate-200' : 'text-slate-700'"
                >
                    <a @click="showMobileMenu = false" href="#about" :class="isDarkNavbar ? 'hover:bg-white/10' : 'hover:bg-slate-100'" class="px-3 py-2 rounded-lg">About Campus</a>
                    <a @click="showMobileMenu = false" href="#portals" :class="isDarkNavbar ? 'hover:bg-white/10' : 'hover:bg-slate-100'" class="px-3 py-2 rounded-lg">Portal Gateways</a>
                    <a @click="showMobileMenu = false" href="#stats" :class="isDarkNavbar ? 'hover:bg-white/10' : 'hover:bg-slate-100'" class="px-3 py-2 rounded-lg">Excellence Metrics</a>
                    <a @click="showMobileMenu = false" href="#faq" :class="isDarkNavbar ? 'hover:bg-white/10' : 'hover:bg-slate-100'" class="px-3 py-2 rounded-lg">FAQ</a>
                </div>
                <div :class="isDarkNavbar ? 'border-t border-slate-800' : 'border-t border-slate-100'" class="pt-3 flex flex-col gap-2.5">
                    <Link 
                        href="/login" 
                        :class="isDarkNavbar 
                            ? 'border border-white/20 bg-white/10 text-white hover:bg-white/20' 
                            : 'border border-slate-300 bg-white text-slate-800 hover:bg-slate-50'"
                        class="w-full py-3 rounded-xl text-center text-sm font-bold shadow-2xs flex items-center justify-center gap-2"
                    >
                        <i class="fa-solid fa-lock" :class="isDarkNavbar ? 'text-blue-400' : 'text-blue-600'"></i>
                        <span>Access Portal Login</span>
                    </Link>
                    <a 
                        @click="showMobileMenu = false"
                        href="#admission-inquiry" 
                        class="w-full py-3 rounded-xl text-white text-center text-sm font-bold shadow-md flex items-center justify-center gap-2"
                        :style="{ background: accentColor }"
                    >
                        <span>Submit Admission Inquiry</span>
                    </a>
                </div>
            </div>
        </header>

        <main class="relative z-10">
            <!-- Hero Showcase Section -->
            <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-10 pb-16 lg:pt-16 lg:pb-24">
                <div class="relative rounded-[36px] overflow-hidden shadow-2xl border border-white/20 p-8 sm:p-12 lg:p-16 bg-slate-900 text-white"
                     :style="{
                         background: `linear-gradient(135deg, ${primaryColor} 0%, ${secondaryColor} 75%, #000000 100%)`
                     }"
                >
                    <!-- Background Ambient Glowing Mesh -->
                    <div class="absolute -right-20 -bottom-20 w-96 h-96 rounded-full bg-white/10 blur-[100px] pointer-events-none"></div>
                    <div class="absolute top-0 right-1/3 w-80 h-80 rounded-full bg-blue-400/15 blur-[90px] pointer-events-none"></div>

                    <div class="relative z-10 grid lg:grid-cols-12 gap-12 items-center">
                        <div class="lg:col-span-7 space-y-6">
                            <!-- Admission Badge -->
                            <div class="inline-flex items-center gap-2.5 px-4 py-1.5 rounded-full bg-white/15 backdrop-blur-md border border-white/25 text-xs sm:text-sm font-semibold text-white shadow-inner">
                                <span class="flex h-2 w-2 relative">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-400"></span>
                                </span>
                                <span>Admissions Open for 2026–2027 Academic Year</span>
                            </div>

                            <!-- Title -->
                            <h1 class="text-3xl sm:text-5xl lg:text-6xl font-black tracking-tight leading-[1.15] text-white">
                                {{ school.hero_heading || school.tagline || 'Empowering Minds, Shaping Futures with Care & Excellence.' }}
                            </h1>

                            <!-- Description -->
                            <p class="text-base sm:text-lg lg:text-xl text-white/85 leading-relaxed max-w-2xl font-normal">
                                <template v-if="school.hero_description">{{ school.hero_description }}</template>
                                <template v-else>Welcome to <span class="font-bold text-white underline decoration-blue-400/80 underline-offset-4">{{ school.name || 'our institution' }}</span>. We provide an inspiring academic foundation, state-of-the-art digital infrastructure, holistic character building, and real-time parent connection through our dedicated cloud campus OS.</template>
                            </p>

                            <!-- Hero Actions -->
                            <div class="pt-3 flex flex-wrap items-center gap-4">
                                <a 
                                    href="#admission-inquiry" 
                                    class="px-8 py-4 rounded-full bg-white text-slate-900 text-sm sm:text-base font-extrabold shadow-xl hover:bg-slate-100 hover:scale-105 transition-all flex items-center gap-2.5"
                                >
                                    <span>Apply for Admission Now</span>
                                    <i class="fa-solid fa-arrow-right text-blue-600 text-sm"></i>
                                </a>
                                <Link 
                                    href="/login" 
                                    class="px-8 py-4 rounded-full bg-white/15 hover:bg-white/25 border border-white/30 backdrop-blur-md text-white text-sm sm:text-base font-bold transition-all flex items-center gap-2.5 hover:border-white/50"
                                >
                                    <i class="fa-solid fa-right-to-bracket text-sm opacity-90"></i>
                                    <span>Access Campus Portal</span>
                                </Link>
                            </div>

                            <!-- Key Feature Pills -->
                            <div class="pt-6 border-t border-white/15 flex flex-wrap items-center gap-5 text-xs sm:text-sm font-medium text-white/80">
                                <div class="flex items-center gap-2">
                                    <i class="fa-solid fa-circle-check text-emerald-400"></i>
                                    <span>100% Digital Classrooms</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <i class="fa-solid fa-circle-check text-emerald-400"></i>
                                    <span>Real-Time SMS/App Alerts</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <i class="fa-solid fa-circle-check text-emerald-400"></i>
                                    <span>Verified Academic Merit</span>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Interactive Quick Portal Access Card -->
                        <div class="lg:col-span-5">
                            <div class="rounded-3xl bg-white/10 backdrop-blur-2xl border border-white/20 p-6 sm:p-8 shadow-2xl relative">
                                <div class="flex items-center justify-between pb-5 border-b border-white/15">
                                    <div>
                                        <span class="text-xs font-bold uppercase tracking-widest text-blue-300">Quick Gateway</span>
                                        <h3 class="text-lg font-bold text-white mt-0.5">Choose Your Portal Role</h3>
                                    </div>
                                    <div class="h-10 w-10 rounded-2xl bg-white/15 flex items-center justify-center text-white text-lg">
                                        <i class="fa-solid fa-shield-halved"></i>
                                    </div>
                                </div>

                                <!-- Role Buttons -->
                                <div class="mt-5 grid grid-cols-2 gap-3.5">
                                    <Link 
                                        href="/login" 
                                        class="p-4 rounded-2xl bg-white/10 hover:bg-white/20 border border-white/15 hover:border-white/40 transition-all text-left group"
                                    >
                                        <div class="h-10 w-10 rounded-xl bg-blue-500/30 flex items-center justify-center text-blue-300 mb-3 group-hover:scale-110 transition-transform">
                                            <i class="fa-solid fa-user-graduate text-lg"></i>
                                        </div>
                                        <p class="font-bold text-white text-sm">Student Portal</p>
                                        <p class="text-xs text-white/65 mt-0.5">Learner dashboard →</p>
                                    </Link>

                                    <Link 
                                        href="/login" 
                                        class="p-4 rounded-2xl bg-white/10 hover:bg-white/20 border border-white/15 hover:border-white/40 transition-all text-left group"
                                    >
                                        <div class="h-10 w-10 rounded-xl bg-emerald-500/30 flex items-center justify-center text-emerald-300 mb-3 group-hover:scale-110 transition-transform">
                                            <i class="fa-solid fa-users text-lg"></i>
                                        </div>
                                        <p class="font-bold text-white text-sm">Parent Portal</p>
                                        <p class="text-xs text-white/65 mt-0.5">Challans & alerts →</p>
                                    </Link>

                                    <Link 
                                        href="/login" 
                                        class="p-4 rounded-2xl bg-white/10 hover:bg-white/20 border border-white/15 hover:border-white/40 transition-all text-left group"
                                    >
                                        <div class="h-10 w-10 rounded-xl bg-amber-500/30 flex items-center justify-center text-amber-300 mb-3 group-hover:scale-110 transition-transform">
                                            <i class="fa-solid fa-chalkboard-user text-lg"></i>
                                        </div>
                                        <p class="font-bold text-white text-sm">Teacher Portal</p>
                                        <p class="text-xs text-white/65 mt-0.5">Attendance & grades →</p>
                                    </Link>

                                    <Link 
                                        href="/login" 
                                        class="p-4 rounded-2xl bg-white/10 hover:bg-white/20 border border-white/15 hover:border-white/40 transition-all text-left group"
                                    >
                                        <div class="h-10 w-10 rounded-xl bg-purple-500/30 flex items-center justify-center text-purple-300 mb-3 group-hover:scale-110 transition-transform">
                                            <i class="fa-solid fa-building-columns text-lg"></i>
                                        </div>
                                        <p class="font-bold text-white text-sm">Admin Portal</p>
                                        <p class="text-xs text-white/65 mt-0.5">Management suite →</p>
                                    </Link>
                                </div>

                                <div class="mt-6 pt-4 border-t border-white/10 flex items-center justify-end text-xs text-white/70">
                                    <Link href="/login" class="font-bold text-white hover:underline">All Portals Sign In →</Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Live Campus Excellence Metrics Section -->
            <section id="stats" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                    <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200/80 shadow-sm hover:shadow-md transition-shadow flex items-center gap-5">
                        <div class="h-14 w-14 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-2xl shrink-0 font-bold shadow-2xs">
                            <i class="fa-solid fa-user-graduate"></i>
                        </div>
                        <div>
                            <p class="text-3xl sm:text-4xl font-black text-slate-900">{{ stats.students || 0 }}<span class="text-blue-600">+</span></p>
                            <p class="text-xs sm:text-sm font-semibold text-slate-500 mt-0.5">Enrolled Students</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200/80 shadow-sm hover:shadow-md transition-shadow flex items-center gap-5">
                        <div class="h-14 w-14 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-2xl shrink-0 font-bold shadow-2xs">
                            <i class="fa-solid fa-chalkboard-user"></i>
                        </div>
                        <div>
                            <p class="text-3xl sm:text-4xl font-black text-slate-900">{{ stats.staff || 0 }}<span class="text-emerald-600">+</span></p>
                            <p class="text-xs sm:text-sm font-semibold text-slate-500 mt-0.5">Qualified Staff</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200/80 shadow-sm hover:shadow-md transition-shadow flex items-center gap-5">
                        <div class="h-14 w-14 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-2xl shrink-0 font-bold shadow-2xs">
                            <i class="fa-solid fa-layer-group"></i>
                        </div>
                        <div>
                            <p class="text-3xl sm:text-4xl font-black text-slate-900">{{ stats.classes || 0 }}</p>
                            <p class="text-xs sm:text-sm font-semibold text-slate-500 mt-0.5">Active Classes / Sections</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200/80 shadow-sm hover:shadow-md transition-shadow flex items-center gap-5">
                        <div class="h-14 w-14 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center text-2xl shrink-0 font-bold shadow-2xs">
                            <i class="fa-solid fa-star"></i>
                        </div>
                        <div>
                            <p class="text-3xl sm:text-4xl font-black text-slate-900 flex items-center">4.9<span class="text-purple-600 ml-1.5"><i class="fa-solid fa-star text-2xl"></i></span></p>
                            <p class="text-xs sm:text-sm font-semibold text-slate-500 mt-0.5">Parent Satisfaction</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- About & Campus Attributes Section -->
            <section id="about" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16">
                <div class="grid lg:grid-cols-12 gap-12 items-center">
                    <div class="lg:col-span-6 space-y-6">
                        <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-blue-100/80 text-blue-700 font-bold text-xs uppercase tracking-wider">
                            <i class="fa-solid fa-building-flag"></i>
                            <span>Why Families Choose Us</span>
                        </div>
                        <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 leading-tight">
                            Holistic Education Powered by Next-Gen Digital Infrastructure
                        </h2>
                        <p class="text-base sm:text-lg text-slate-600 leading-relaxed">
                            <template v-if="school.about_vision">{{ school.about_vision }}</template>
                            <template v-else>At <span class="font-bold text-slate-900">{{ school.name || 'our school' }}</span>, we combine experienced, compassionate educators with state-of-the-art campus technology. Parents never have to wonder how their child is doing—our portal keeps you informed 24/7.</template>
                        </p>

                        <!-- Feature Checklist -->
                        <div class="grid sm:grid-cols-2 gap-4 pt-2">
                            <div class="flex items-start gap-3 p-3.5 rounded-2xl bg-white border border-slate-200/80 shadow-2xs">
                                <div class="h-9 w-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0 font-bold">
                                    <i class="fa-solid fa-microchip"></i>
                                </div>
                                <div>
                                    <p class="font-bold text-slate-900 text-sm">Smart Attendance</p>
                                    <p class="text-xs text-slate-500 mt-0.5">Instant notification on student check-in.</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3 p-3.5 rounded-2xl bg-white border border-slate-200/80 shadow-2xs">
                                <div class="h-9 w-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 font-bold">
                                    <i class="fa-solid fa-receipt"></i>
                                </div>
                                <div>
                                    <p class="font-bold text-slate-900 text-sm">Digital Challans</p>
                                    <p class="text-xs text-slate-500 mt-0.5">Pay fees easily via 1-Link, card or mobile.</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3 p-3.5 rounded-2xl bg-white border border-slate-200/80 shadow-2xs">
                                <div class="h-9 w-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0 font-bold">
                                    <i class="fa-solid fa-file-shield"></i>
                                </div>
                                <div>
                                    <p class="font-bold text-slate-900 text-sm">Live Report Cards</p>
                                    <p class="text-xs text-slate-500 mt-0.5">Detailed gradebook & progress charts.</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3 p-3.5 rounded-2xl bg-white border border-slate-200/80 shadow-2xs">
                                <div class="h-9 w-9 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center shrink-0 font-bold">
                                    <i class="fa-solid fa-message"></i>
                                </div>
                                <div>
                                    <p class="font-bold text-slate-900 text-sm">Direct Messaging</p>
                                    <p class="text-xs text-slate-500 mt-0.5">Seamless teacher-parent communication.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Campus Profile Card -->
                    <div class="lg:col-span-6">
                        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xl p-6 sm:p-8 space-y-6 relative overflow-hidden">
                            <div class="absolute top-0 right-0 h-32 w-32 bg-blue-500/5 rounded-bl-full pointer-events-none"></div>

                            <div class="flex items-center gap-4 pb-6 border-b border-slate-100">
                                <div 
                                    class="h-16 w-16 rounded-2xl flex items-center justify-center text-2xl font-black overflow-hidden shrink-0 border border-slate-200 shadow-2xs p-1.5"
                                    :style="school.logo ? logoBgStyle : { background: '#F1F5F9' }"
                                >
                                    <img 
                                        v-if="school.logo" 
                                        :src="school.logo" 
                                        :alt="school.name" 
                                        class="max-h-full max-w-full w-auto h-auto object-contain filter drop-shadow-[0_1px_2px_rgba(0,0,0,0.22)]" 
                                    />
                                    <i v-else class="fa-solid fa-school text-blue-600"></i>
                                </div>
                                <div>
                                    <h3 class="text-xl font-black text-slate-900">{{ school.name || 'Campus Profile' }}</h3>
                                    <p class="text-sm font-medium text-slate-500">{{ schoolType }} • Established {{ school.established || 'Recently' }}</p>
                                </div>
                            </div>

                            <ul class="space-y-4 text-sm">
                                <li class="flex items-start gap-3.5">
                                    <span class="mt-0.5 flex h-8 w-8 items-center justify-center rounded-xl bg-blue-50 text-blue-600 shrink-0 font-bold">
                                        <i class="fa-solid fa-location-dot"></i>
                                    </span>
                                    <div>
                                        <p class="font-bold text-slate-900">Campus Location</p>
                                        <p class="text-slate-600 mt-0.5">{{ contactAddress || (school.name ? `${school.name} Main Campus` : 'Main Campus Premises') }}</p>
                                    </div>
                                </li>

                                <li class="flex items-start gap-3.5">
                                    <span class="mt-0.5 flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 shrink-0 font-bold">
                                        <i class="fa-solid fa-phone"></i>
                                    </span>
                                    <div>
                                        <p class="font-bold text-slate-900">Direct Contact & Support</p>
                                        <a v-if="contactPhone" :href="'tel:' + contactPhone" class="text-slate-600 hover:text-blue-600 font-medium transition-colors mt-0.5 block">{{ contactPhone }}</a>
                                        <p v-else class="text-slate-600 mt-0.5">Admissions Office (Monday–Saturday)</p>
                                    </div>
                                </li>

                                <li class="flex items-start gap-3.5">
                                    <span class="mt-0.5 flex h-8 w-8 items-center justify-center rounded-xl bg-purple-50 text-purple-600 shrink-0 font-bold">
                                        <i class="fa-solid fa-envelope"></i>
                                    </span>
                                    <div>
                                        <p class="font-bold text-slate-900">Admissions Email</p>
                                        <a v-if="contactEmail" :href="'mailto:' + contactEmail" class="text-slate-600 hover:text-blue-600 font-medium transition-colors mt-0.5 block">{{ contactEmail }}</a>
                                        <p v-else class="text-slate-600 mt-0.5">Admissions & Inquiries Desk</p>
                                    </div>
                                </li>
                            </ul>

                            <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
                                <span class="text-xs font-semibold text-slate-400">Looking for directions or tour?</span>
                                <a href="#admission-inquiry" class="text-xs font-bold text-blue-600 hover:underline">Schedule Campus Visit →</a>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Comprehensive Portal Gateway Section -->
            <section id="portals" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
                <div class="text-center max-w-3xl mx-auto mb-12">
                    <span class="inline-block text-xs font-bold uppercase tracking-widest text-blue-600 bg-blue-50 px-3.5 py-1.5 rounded-full mb-3 border border-blue-200/60">
                        Multi-Stakeholder Access
                    </span>
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
                        One Unified Cloud Portal for Every Role
                    </h2>
                    <p class="text-base text-slate-600 mt-3">
                        Whether you are a student submitting assignments, a parent checking attendance, or a teacher grading exams, your personalized portal workspace is right here.
                    </p>
                </div>

                <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div 
                        v-for="portal in portals" 
                        :key="portal.id"
                        class="bg-white rounded-3xl border border-slate-200/80 shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col justify-between overflow-hidden group hover:-translate-y-1.5"
                    >
                        <div class="p-6 sm:p-7 space-y-4">
                            <!-- Header & Badge -->
                            <div class="flex items-center justify-between">
                                <div class="h-12 w-12 rounded-2xl bg-slate-900 text-white flex items-center justify-center text-xl shadow-md group-hover:scale-110 transition-transform">
                                    <i :class="['fa-solid', portal.icon]"></i>
                                </div>
                                <span class="text-[11px] font-extrabold uppercase tracking-wider px-2.5 py-1 rounded-full bg-slate-100 text-slate-600">
                                    {{ portal.badge }}
                                </span>
                            </div>

                            <div>
                                <h3 class="text-lg font-black text-slate-900 group-hover:text-blue-600 transition-colors">{{ portal.title }}</h3>
                                <p class="text-xs font-medium text-slate-500 mt-1">{{ portal.subtitle }}</p>
                            </div>

                            <ul class="space-y-2.5 pt-2 text-xs text-slate-600">
                                <li v-for="feat in portal.features" :key="feat" class="flex items-start gap-2">
                                    <i class="fa-solid fa-check text-blue-600 mt-0.5 shrink-0 font-bold"></i>
                                    <span>{{ feat }}</span>
                                </li>
                            </ul>
                        </div>

                        <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end">
                            <Link 
                                href="/login" 
                                class="inline-flex items-center gap-1.5 text-xs font-black text-blue-600 hover:text-blue-800 transition-colors"
                            >
                                <span>Login Now</span>
                                <i class="fa-solid fa-arrow-right text-[10px] group-hover:translate-x-1 transition-transform"></i>
                            </Link>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Admission Inquiry & Application Hub -->
            <section id="admission-inquiry" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-24">
                <div class="rounded-[36px] bg-white border border-slate-200 shadow-2xl overflow-hidden grid lg:grid-cols-12">
                    <!-- Left Sidebar Info -->
                    <div class="lg:col-span-5 bg-slate-900 text-white p-8 sm:p-12 flex flex-col justify-between relative overflow-hidden"
                         :style="{ background: `linear-gradient(145deg, ${primaryColor} 0%, ${secondaryColor} 85%, #000000 100%)` }"
                    >
                        <div class="space-y-6 relative z-10">
                            <span class="inline-block text-xs font-bold uppercase tracking-widest px-3 py-1 rounded-full bg-white/20 backdrop-blur-md">
                                Online Admission Portal
                            </span>
                            <h2 class="text-3xl sm:text-4xl font-black text-white leading-tight">
                                Start Your Child’s Journey Today
                            </h2>
                            <p class="text-sm sm:text-base text-white/85 leading-relaxed font-normal">
                                Submit your inquiry in under 2 minutes. Our Admissions Counselor will verify class seat availability and schedule an orientation or evaluation assessment.
                            </p>

                            <div class="space-y-4 pt-4 border-t border-white/15 text-xs sm:text-sm">
                                <div class="flex items-center gap-3">
                                    <div class="h-8 w-8 rounded-full bg-white/20 flex items-center justify-center font-bold">1</div>
                                    <p class="font-semibold text-white">Fill out basic student & class details</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="h-8 w-8 rounded-full bg-white/20 flex items-center justify-center font-bold">2</div>
                                    <p class="font-semibold text-white">Admissions office schedules orientation</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="h-8 w-8 rounded-full bg-white/20 flex items-center justify-center font-bold">3</div>
                                    <p class="font-semibold text-white">Instant portal credentials upon enrollment</p>
                                </div>
                            </div>
                        </div>

                        <div class="pt-8 border-t border-white/15 mt-8 relative z-10">
                            <p class="text-xs text-white/70">Need urgent assistance?</p>
                            <a v-if="contactPhone" :href="'tel:' + contactPhone" class="text-sm font-bold text-white mt-0.5 block hover:underline">
                                <i class="fa-solid fa-phone mr-1.5"></i> {{ contactPhone }}
                            </a>
                            <p v-else class="text-sm font-bold text-white mt-0.5">
                                <i class="fa-solid fa-headset mr-1.5"></i> Admissions Help Desk
                            </p>
                        </div>

                        <div class="absolute -right-16 -bottom-16 w-64 h-64 rounded-full bg-white/10 blur-[60px] pointer-events-none"></div>
                    </div>

                    <!-- Right Form Area -->
                    <div class="lg:col-span-7 p-8 sm:p-12 bg-white">
                        <div class="mb-6 pb-4 border-b border-slate-100 flex items-center justify-between">
                            <div>
                                <h3 class="text-2xl font-black text-slate-900">Admission Inquiry Form</h3>
                                <p class="text-xs sm:text-sm text-slate-500 mt-1">Please ensure contact phone number is active for WhatsApp / SMS updates.</p>
                            </div>
                            <span class="text-xs font-bold text-emerald-600 bg-emerald-50 px-3 py-1.5 rounded-full border border-emerald-200 hidden sm:inline-block">
                                <i class="fa-solid fa-bolt mr-1"></i> Fast-Track Review
                            </span>
                        </div>

                        <!-- Success Banner -->
                        <div v-if="form.recentlySuccessful" class="mb-6 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-start gap-3 animate-fadeIn">
                            <i class="fa-solid fa-circle-check text-emerald-600 text-lg mt-0.5"></i>
                            <div>
                                <p class="font-bold">Inquiry Submitted Successfully!</p>
                                <p class="text-xs text-emerald-700 mt-0.5">We have registered your request. Our admissions team will contact you shortly via phone or email.</p>
                            </div>
                        </div>

                        <!-- Error Banner -->
                        <div v-if="Object.keys(form.errors).length > 0" class="mb-6 p-4 rounded-2xl bg-red-50 border border-red-200 text-red-800 text-sm">
                            <p class="font-bold flex items-center gap-2"><i class="fa-solid fa-triangle-exclamation"></i> Please review the following errors:</p>
                            <ul class="list-disc list-inside text-xs mt-1 space-y-1">
                                <li v-for="(errMsg, field) in form.errors" :key="field">{{ errMsg }}</li>
                            </ul>
                        </div>

                        <form @submit.prevent="submitInquiry" class="space-y-5">
                            <!-- Student & Class Section -->
                            <div class="grid sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                        Student Full Name <span class="text-red-500">*</span>
                                    </label>
                                    <input 
                                        v-model="form.student_name" 
                                        type="text" 
                                        required 
                                        placeholder="Student Full Name" 
                                        class="w-full rounded-xl border border-slate-300 bg-slate-50/50 px-4 py-3 text-sm font-medium text-slate-900 placeholder-slate-400 focus:bg-white focus:border-blue-600 focus:outline-none focus:ring-4 focus:ring-blue-600/15 transition-all"
                                    />
                                </div>

                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                        Desired Class / Grade <span class="text-red-500">*</span>
                                    </label>
                                    <select 
                                        v-model="form.desired_class_id" 
                                        required 
                                        class="w-full rounded-xl border border-slate-300 bg-slate-50/50 px-4 py-3 text-sm font-medium text-slate-900 focus:bg-white focus:border-blue-600 focus:outline-none focus:ring-4 focus:ring-blue-600/15 transition-all"
                                    >
                                        <option value="" disabled>Select target grade...</option>
                                        <option v-for="cls in classes" :key="cls.id" :value="cls.id">
                                            {{ cls.name }}
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <!-- Parent Details -->
                            <div class="grid sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                        Parent / Guardian Name <span class="text-red-500">*</span>
                                    </label>
                                    <input 
                                        v-model="form.parent_name" 
                                        type="text" 
                                        required 
                                        placeholder="Parent / Guardian Name" 
                                        class="w-full rounded-xl border border-slate-300 bg-slate-50/50 px-4 py-3 text-sm font-medium text-slate-900 placeholder-slate-400 focus:bg-white focus:border-blue-600 focus:outline-none focus:ring-4 focus:ring-blue-600/15 transition-all"
                                    />
                                </div>

                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                        Active Phone Number <span class="text-red-500">*</span>
                                    </label>
                                    <input 
                                        v-model="form.parent_phone" 
                                        type="tel" 
                                        required 
                                        placeholder="Active Phone Number" 
                                        class="w-full rounded-xl border border-slate-300 bg-slate-50/50 px-4 py-3 text-sm font-medium text-slate-900 placeholder-slate-400 focus:bg-white focus:border-blue-600 focus:outline-none focus:ring-4 focus:ring-blue-600/15 transition-all"
                                    />
                                </div>
                            </div>

                            <!-- Email & Previous School -->
                            <div class="grid sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                        Email Address <span class="text-slate-400 font-normal">(Optional)</span>
                                    </label>
                                    <input 
                                        v-model="form.parent_email" 
                                        type="email" 
                                        placeholder="Email Address" 
                                        class="w-full rounded-xl border border-slate-300 bg-slate-50/50 px-4 py-3 text-sm font-medium text-slate-900 placeholder-slate-400 focus:bg-white focus:border-blue-600 focus:outline-none focus:ring-4 focus:ring-blue-600/15 transition-all"
                                    />
                                </div>

                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                        Previous School <span class="text-slate-400 font-normal">(Optional)</span>
                                    </label>
                                    <input 
                                        v-model="form.previous_school" 
                                        type="text" 
                                        placeholder="Previous School / Institution" 
                                        class="w-full rounded-xl border border-slate-300 bg-slate-50/50 px-4 py-3 text-sm font-medium text-slate-900 placeholder-slate-400 focus:bg-white focus:border-blue-600 focus:outline-none focus:ring-4 focus:ring-blue-600/15 transition-all"
                                    />
                                </div>
                            </div>

                            <!-- Notes -->
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                    Additional Notes / Preferred Start Term
                                </label>
                                <textarea 
                                    v-model="form.notes" 
                                    rows="3" 
                                    placeholder="Tell us about your child's academic background, special interests, or any questions for our counselor." 
                                    class="w-full rounded-xl border border-slate-300 bg-slate-50/50 px-4 py-3 text-sm font-medium text-slate-900 placeholder-slate-400 focus:bg-white focus:border-blue-600 focus:outline-none focus:ring-4 focus:ring-blue-600/15 transition-all"
                                ></textarea>
                            </div>

                            <!-- Submit Button -->
                            <div class="pt-2 flex items-center justify-end">
                                <button 
                                    type="submit" 
                                    :disabled="form.processing"
                                    class="w-full sm:w-auto px-8 py-3.5 rounded-full text-white font-black text-sm shadow-lg hover:shadow-xl transition-all disabled:opacity-50 flex items-center justify-center gap-2 hover:scale-102 active:scale-98"
                                    :style="{ background: accentColor }"
                                >
                                    <span v-if="form.processing"><i class="fa-solid fa-spinner fa-spin mr-2"></i> Submitting...</span>
                                    <span v-else>Submit Admission Inquiry <i class="fa-solid fa-paper-plane ml-1.5"></i></span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </section>

            <!-- FAQ Section -->
            <section id="faq" class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
                <div class="text-center mb-10">
                    <span class="text-xs font-bold uppercase tracking-widest text-blue-600 bg-blue-50 px-3.5 py-1.5 rounded-full border border-blue-200/60">
                        Got Questions?
                    </span>
                    <h2 class="text-3xl font-extrabold text-slate-900 mt-2">Frequently Asked Questions</h2>
                </div>

                <div class="space-y-4">
                    <div 
                        v-for="(faq, idx) in faqs" 
                        :key="idx"
                        class="rounded-2xl border border-slate-200 bg-white overflow-hidden shadow-2xs transition-all"
                    >
                        <button 
                            @click="activeFaq = activeFaq === idx ? -1 : idx"
                            class="w-full p-5 text-left font-bold text-slate-900 flex items-center justify-between hover:bg-slate-50 transition-colors"
                        >
                            <span>{{ faq.question }}</span>
                            <span class="h-8 w-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-600 text-sm shrink-0 ml-3">
                                <i :class="activeFaq === idx ? 'fa-solid fa-minus' : 'fa-solid fa-plus'"></i>
                            </span>
                        </button>
                        <div v-show="activeFaq === idx" class="px-5 pb-5 text-sm text-slate-600 leading-relaxed border-t border-slate-100 pt-3">
                            {{ faq.answer }}
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer class="bg-slate-900 text-white border-t border-slate-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- Col 1: School Info -->
                <div class="md:col-span-2 space-y-4">
                    <div class="flex items-center gap-3">
                        <div v-if="school.logo" class="flex items-center flex-shrink-0">
                            <img 
                                :src="school.logo" 
                                :alt="school.name || 'School Logo'" 
                                class="h-10 w-auto max-w-[140px] object-contain" 
                                :class="isDarkNavbar ? '' : 'bg-white/95 p-1 rounded-lg border border-slate-700'"
                            />
                        </div>
                        <div 
                            v-else 
                            class="h-10 w-10 rounded-xl flex items-center justify-center text-lg font-bold text-white flex-shrink-0" 
                            :style="{ background: accentColor }"
                        >
                            <i class="fa-solid fa-graduation-cap"></i>
                        </div>
                        <span class="text-lg font-black tracking-tight text-white">{{ school.name || 'Campus Portal' }}</span>
                    </div>
                    <p class="text-xs text-slate-400 max-w-md leading-relaxed">
                        {{ school.tagline || 'Excellence in education, character development, and future readiness.' }}
                    </p>
                    <div class="flex items-center gap-3 text-slate-400 text-sm pt-2">
                        <a href="#" class="h-9 w-9 rounded-xl bg-slate-800 flex items-center justify-center hover:text-white transition-colors"><i class="fa-brands fa-facebook-f"></i></a>
                        <a href="#" class="h-9 w-9 rounded-xl bg-slate-800 flex items-center justify-center hover:text-white transition-colors"><i class="fa-brands fa-twitter"></i></a>
                        <a href="#" class="h-9 w-9 rounded-xl bg-slate-800 flex items-center justify-center hover:text-white transition-colors"><i class="fa-brands fa-instagram"></i></a>
                        <a href="#" class="h-9 w-9 rounded-xl bg-slate-800 flex items-center justify-center hover:text-white transition-colors"><i class="fa-brands fa-youtube"></i></a>
                    </div>
                </div>

                <!-- Col 2: Quick Links -->
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">Portal Quick Access</h4>
                    <ul class="space-y-2 text-xs font-semibold text-slate-300">
                        <li><Link href="/login" class="hover:text-white transition-colors">Student Login</Link></li>
                        <li><Link href="/login" class="hover:text-white transition-colors">Parent Challan Access</Link></li>
                        <li><Link href="/login" class="hover:text-white transition-colors">Teacher Gradebook</Link></li>
                        <li><Link href="/login" class="hover:text-white transition-colors">Principal Dashboard</Link></li>
                    </ul>
                </div>

                <!-- Col 3: Contact -->
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">Official School Contact</h4>
                    <ul class="space-y-2.5 text-xs text-slate-300">
                        <li v-if="contactPhone" class="flex items-start gap-2">
                            <i class="fa-solid fa-phone mt-0.5 text-blue-400"></i>
                            <a :href="'tel:' + contactPhone" class="hover:text-white transition-colors">{{ contactPhone }}</a>
                        </li>
                        <li v-if="contactEmail" class="flex items-start gap-2">
                            <i class="fa-solid fa-envelope mt-0.5 text-blue-400"></i>
                            <a :href="'mailto:' + contactEmail" class="hover:text-white transition-colors">{{ contactEmail }}</a>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-location-dot mt-0.5 text-blue-400"></i>
                            <span>{{ contactAddress || (school.name ? `${school.name} Campus` : 'Main Campus Premises') }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-slate-800 py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 gap-4">
                <p>© {{ new Date().getFullYear() }} {{ school.name || 'Campus Portal' }}. All rights reserved.</p>
                <div class="flex items-center gap-2">
                    <span>Powered by</span>
                    <span class="font-bold text-slate-300">{{ $page.props.platformName || 'Excellence LMS Cloud OS' }}</span>
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-4px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fadeIn {
    animation: fadeIn 0.2s ease-out forwards;
}
</style>
