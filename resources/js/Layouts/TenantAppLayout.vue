<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { getAdminNavItems } from '@/Config/tenantAdminNav.js';

const props = defineProps({
    title: {
        type: String,
        default: ''
    },
    description: {
        type: String,
        default: ''
    },
    navItems: {
        type: Array,
        default: () => []
    },
    activeTab: {
        type: String,
        default: ''
    },
    roleTitle: {
        type: String,
        default: ''
    }
});

const emit = defineEmits(['tab-change']);

const page = usePage();
const tenant = computed(() => page.props.tenant || {});
const authUser = computed(() => page.props.auth?.user || {});
const authRole = computed(() => {
    if (props.roleTitle) return props.roleTitle;
    const r = page.props.auth?.role || page.props.auth?.user?.role || page.props.auth?.user?.user_type || 'User';
    return r.charAt(0).toUpperCase() + r.slice(1);
});

const academicSession = computed(() => {
    return tenant.value.academic_session || 'Session 2026-2027';
});

const logoBgStyle = computed(() => {
    const bgMode = tenant.value?.logo_bg || 'dark';
    if (bgMode === 'brand') {
        return {
            backgroundColor: tenant.value?.cover_color || '#1A73E8',
            borderColor: 'rgba(0,0,0,0.15)',
        };
    }
    if (bgMode === 'light') {
        return {
            backgroundColor: '#FFFFFF',
            borderColor: '#E5E7EB',
        };
    }
    // Default 'dark' (Midnight Slate) - optimal contrast for white & transparent logos
    return {
        backgroundColor: '#0F172A',
        borderColor: '#1E293B',
    };
});

const isMobileMenuOpen = ref(false);
const isSidebarCollapsed = ref(false);

onMounted(() => {
    const saved = localStorage.getItem('raibix_sidebar_collapsed');
    if (saved !== null) {
        isSidebarCollapsed.value = saved === 'true';
    }
});

const toggleSidebar = () => {
    isSidebarCollapsed.value = !isSidebarCollapsed.value;
    localStorage.setItem('raibix_sidebar_collapsed', isSidebarCollapsed.value ? 'true' : 'false');
};

const logout = () => {
    router.post('/logout');
};

const isOnDashboard = computed(() => {
    const path = page.url.split('?')[0];
    return path === '/admin/dashboard';
});

const effectiveNavItems = computed(() => {
    if (props.navItems && props.navItems.length > 0) {
        return props.navItems;
    }
    const role = (page.props.auth?.role || page.props.auth?.user?.role || page.props.auth?.user?.user_type || props.roleTitle || '').toLowerCase();
    const isAdminUser = ['admin', 'principal', 'vice_principal', 'accountant', 'registrar', 'receptionist', 'librarian'].includes(role) || page.url.startsWith('/admin');
    if (isAdminUser) {
        return getAdminNavItems({
            role: role || 'principal',
            defaultersCount: page.props.defaulters_count || page.props.defaulters?.length,
            pendingApprovalsCount: page.props.pending_payments_count || page.props.pending_payments?.length,
        });
    }
    return [];
});

const isItemActive = (item) => {
    if (isOnDashboard.value) {
        if (item.tab) {
            return props.activeTab === item.tab;
        }
        if (item.id && !item.href) {
            return props.activeTab === item.id;
        }
        return false;
    }
    if (item.href) {
        const itemPath = item.href.split('?')[0];
        const currentPath = page.url.split('?')[0];
        return currentPath === itemPath;
    }
    return false;
};

const handleNavClick = (item, event) => {
    if (item.onClick) {
        event?.preventDefault?.();
        item.onClick(item);
        if (isMobileMenuOpen.value) isMobileMenuOpen.value = false;
        return;
    }

    if (isOnDashboard.value && item.tab) {
        event?.preventDefault?.();
        emit('tab-change', item.tab);
        if (typeof window !== 'undefined') {
            const url = new URL(window.location.href);
            url.searchParams.set('tab', item.tab);
            window.history.replaceState({}, '', url.toString());
        }
        if (isMobileMenuOpen.value) isMobileMenuOpen.value = false;
        return;
    }

    if (isOnDashboard.value && item.id && !item.href) {
        event?.preventDefault?.();
        emit('tab-change', item.id);
        if (isMobileMenuOpen.value) isMobileMenuOpen.value = false;
        return;
    }

    if (isMobileMenuOpen.value) {
        isMobileMenuOpen.value = false;
    }
};

const userInitials = computed(() => {
    const name = authUser.value?.name || authRole.value || 'User';
    const parts = name.trim().split(' ');
    if (parts.length >= 2) {
        return (parts[0][0] + parts[1][0]).toUpperCase();
    }
    return name.slice(0, 2).toUpperCase();
});
</script>

<template>
    <div class="min-h-screen bg-[#F8F9FA] text-[#0B0D14] font-sans antialiased flex flex-col selection:bg-[#EEF3FF] selection:text-[#2F6BFF]">
        <!-- Mobile Sidebar Backdrop -->
        <transition
            enter-active-class="transition-opacity duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div 
                v-if="isMobileMenuOpen" 
                @click="isMobileMenuOpen = false"
                class="fixed inset-0 z-40 bg-black/30 backdrop-blur-xs lg:hidden"
                aria-hidden="true"
            ></div>
        </transition>

        <div class="flex flex-1 min-h-screen">
            <!-- Sidebar -->
            <aside 
                :class="[
                    isSidebarCollapsed ? 'lg:w-[72px]' : 'lg:w-64',
                    isMobileMenuOpen ? 'translate-x-0 w-64' : '-translate-x-full lg:translate-x-0',
                    'fixed lg:sticky top-0 z-50 h-screen border-r border-[#E5E7EB] bg-white flex flex-col justify-between flex-shrink-0 transition-all duration-200 ease-in-out'
                ]"
                aria-label="Main Navigation"
            >
                <div class="flex flex-col min-h-0 flex-1">
                    <!-- Brand Header -->
                    <div class="h-16 flex items-center justify-between px-3.5 border-b border-[#E5E7EB]">
                        <div class="flex items-center gap-2.5 min-w-0 flex-1">
                            <!-- School or Raibix Icon Container -->
                            <div 
                                v-if="tenant?.logo" 
                                :class="[
                                    isSidebarCollapsed && !isMobileMenuOpen 
                                        ? 'h-10 w-10 p-1 mx-auto' 
                                        : 'h-10 min-w-[40px] max-w-[110px] px-2 py-1',
                                    'rounded-xl border flex items-center justify-center flex-shrink-0 overflow-hidden shadow-2xs transition-all duration-200'
                                ]"
                                :style="logoBgStyle"
                            >
                                <img 
                                    :src="tenant.logo" 
                                    :alt="tenant.name || 'School Logo'" 
                                    class="max-h-full max-w-full w-auto h-auto object-contain transition-transform duration-200 filter drop-shadow-[0_1px_2px_rgba(0,0,0,0.22)]" 
                                />
                            </div>
                            <div 
                                v-else 
                                class="h-10 w-10 rounded-xl border border-[#E5E7EB] bg-[#F8F9FA] flex items-center justify-center flex-shrink-0 text-[#0B0D14] shadow-2xs font-bold text-xs"
                                :class="isSidebarCollapsed && !isMobileMenuOpen ? 'mx-auto' : ''"
                            >
                                <span class="raibix-dot h-2.5 w-2.5"></span>
                            </div>

                            <!-- School Name + Role Badge -->
                            <div v-if="!isSidebarCollapsed || isMobileMenuOpen" class="min-w-0 flex-1 truncate">
                                <h1 class="text-xs font-bold tracking-tight text-[#0B0D14] truncate" :title="tenant?.name || 'School Portal'">
                                    {{ tenant?.name || 'School Portal' }}
                                </h1>
                                <div class="flex items-center gap-1.5 mt-0.5">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 flex-shrink-0"></span>
                                    <p class="text-3xs font-semibold text-[#5B6270] uppercase tracking-wider truncate">
                                        {{ authRole }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Collapse toggle for desktop / Close for mobile -->
                        <button 
                            @click="isMobileMenuOpen ? (isMobileMenuOpen = false) : toggleSidebar()"
                            class="h-7 w-7 rounded-lg text-[#5B6270] hover:text-[#0B0D14] hover:bg-[#F8F9FA] transition-colors flex items-center justify-center flex-shrink-0 cursor-pointer ml-1"
                            :title="isMobileMenuOpen ? 'Close Menu' : (isSidebarCollapsed ? 'Expand Sidebar' : 'Collapse Sidebar')"
                            :aria-label="isSidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'"
                        >
                            <i :class="isMobileMenuOpen ? 'fa-solid fa-xmark' : (isSidebarCollapsed ? 'fa-solid fa-chevron-right' : 'fa-solid fa-chevron-left')" class="text-xs"></i>
                        </button>
                    </div>

                    <!-- Navigation Items List (Simple, Flat, Unified) -->
                    <nav class="p-2 space-y-1 overflow-y-auto flex-1 custom-scrollbar">
                        <template v-for="item in effectiveNavItems" :key="item.id || item.label">
                            <component 
                                :is="(isOnDashboard && item.tab) ? 'button' : (item.href ? Link : 'button')"
                                :href="(!isOnDashboard || !item.tab) ? (item.href || undefined) : undefined"
                                @click="handleNavClick(item, $event)"
                                :title="(isSidebarCollapsed && !isMobileMenuOpen) ? item.label : ''"
                                :class="[
                                    isItemActive(item)
                                        ? 'bg-[#F4F5F8] text-[#0B0D14] font-semibold' 
                                        : 'text-[#5B6270] hover:bg-[#F8F9FA] hover:text-[#0B0D14] font-normal',
                                    (isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center px-2 py-2.5' : 'justify-between px-3 py-2',
                                    'w-full flex items-center rounded-xl text-xs transition-colors group relative cursor-pointer'
                                ]"
                            >
                                <div class="flex items-center" :class="(isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center' : 'gap-2.5 min-w-0'">
                                    <!-- Intelligence Dot for active item -->
                                    <span 
                                        v-if="isItemActive(item)" 
                                        class="raibix-dot h-1.5 w-1.5 flex-shrink-0"
                                        aria-hidden="true"
                                    ></span>
                                    <span 
                                        v-else-if="!isSidebarCollapsed || isMobileMenuOpen" 
                                        class="h-1.5 w-1.5 flex-shrink-0 opacity-0"
                                    ></span>

                                    <!-- Icon -->
                                    <span class="flex-shrink-0 flex items-center justify-center w-4 text-center text-xs text-[#5B6270] group-hover:text-[#0B0D14] transition-colors">
                                        <i :class="item.icon || 'fa-solid fa-circle-dot'"></i>
                                    </span>

                                    <!-- Label -->
                                    <span v-if="!isSidebarCollapsed || isMobileMenuOpen" class="truncate text-left">
                                        {{ item.label }}
                                    </span>
                                </div>

                                <!-- Badge / Count -->
                                <span 
                                    v-if="item.badge && (!isSidebarCollapsed || isMobileMenuOpen)" 
                                    :class="item.badgeClass || 'bg-[#EEF3FF] text-[#2F6BFF] font-semibold'"
                                    class="text-3xs px-1.5 py-0.5 rounded-full flex-shrink-0"
                                >
                                    {{ item.badge }}
                                </span>
                            </component>
                        </template>
                    </nav>
                </div>

                <!-- Sidebar Footer User Profile -->
                <div class="p-3 border-t border-[#E5E7EB] bg-white">
                    <div class="flex items-center" :class="(isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center' : 'justify-between gap-2'">
                        <div class="flex items-center min-w-0" :class="(isSidebarCollapsed && !isMobileMenuOpen) ? '' : 'gap-2.5'">
                            <div class="h-8 w-8 rounded-full bg-[#111318] text-white flex-shrink-0 flex items-center justify-center text-2xs font-bold tracking-tight shadow-2xs">
                                {{ userInitials }}
                            </div>
                            <div v-if="!isSidebarCollapsed || isMobileMenuOpen" class="min-w-0 truncate">
                                <p class="text-xs font-semibold text-[#0B0D14] truncate" :title="authUser?.name || 'User'">
                                    {{ authUser?.name || 'Account' }}
                                </p>
                                <p class="text-3xs text-[#5B6270] truncate capitalize">
                                    {{ authRole }}
                                </p>
                            </div>
                        </div>

                        <!-- Sign Out Button -->
                        <button 
                            @click="logout"
                            :title="'Sign Out (' + (authUser?.email || '') + ')'"
                            class="h-7 w-7 rounded-lg text-[#5B6270] hover:text-[#DC2626] hover:bg-[#FEE2E2]/40 transition-colors flex items-center justify-center flex-shrink-0"
                            aria-label="Sign out"
                        >
                            <i class="fa-solid fa-arrow-right-from-bracket text-xs"></i>
                        </button>
                    </div>
                </div>
            </aside>

            <!-- Main Content Container -->
            <div class="flex-1 min-w-0 flex flex-col">
                <!-- Top Navigation Bar -->
                <header class="sticky top-0 z-30 h-16 bg-white/95 backdrop-blur-md border-b border-[#E5E7EB] px-4 sm:px-6 lg:px-8 flex items-center justify-between gap-4">
                    <!-- Left: Mobile Menu Button + Academic Session Indicator -->
                    <div class="flex items-center gap-3 min-w-0">
                        <button 
                            @click="isMobileMenuOpen = true"
                            class="lg:hidden h-8 w-8 rounded-lg text-[#5B6270] hover:text-[#0B0D14] hover:bg-[#F8F9FA] transition-colors border border-[#E5E7EB] flex items-center justify-center flex-shrink-0"
                            aria-label="Open navigation menu"
                        >
                            <i class="fa-solid fa-bars text-sm"></i>
                        </button>

                        <!-- Academic Session Badge -->
                        <div class="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-2xs font-medium bg-[#F8F9FA] text-[#0B0D14] border border-[#E5E7EB]">
                            <span class="raibix-dot h-1.5 w-1.5"></span>
                            <span>{{ academicSession }}</span>
                        </div>

                        <!-- Breadcrumb / Section context for Mobile -->
                        <div class="sm:hidden truncate">
                            <span class="text-xs font-semibold text-[#0B0D14]">{{ title }}</span>
                        </div>
                    </div>

                    <!-- Center / Custom Extra Slot (e.g. Parent Child Switcher) -->
                    <div class="flex-1 max-w-xl mx-auto flex items-center justify-center min-w-0">
                        <slot name="top-bar-extra"></slot>
                    </div>

                    <!-- Right: Quick Actions & Profile -->
                    <div class="flex items-center gap-2 sm:gap-3 flex-shrink-0">
                        <!-- Intelligence Core Indicator Badge -->
                        <div class="hidden md:flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-white border border-[#E5E7EB] text-2xs text-[#5B6270]">
                            <span class="raibix-dot h-1.5 w-1.5 animate-pulse"></span>
                            <span class="font-medium text-[#0B0D14]">Raibix Core</span>
                            <span class="text-3xs text-[#5B6270]">Online</span>
                        </div>

                        <!-- User Profile Pill -->
                        <div class="flex items-center gap-2 pl-2 border-l border-[#E5E7EB]">
                            <div class="h-8 w-8 rounded-full bg-[#F4F5F8] border border-[#E5E7EB] text-[#0B0D14] flex items-center justify-center text-2xs font-bold shadow-2xs">
                                {{ userInitials }}
                            </div>
                            <div class="hidden lg:block text-left">
                                <p class="text-xs font-semibold text-[#0B0D14] leading-tight truncate max-w-[120px]">
                                    {{ authUser?.name || 'Account' }}
                                </p>
                                <p class="text-3xs font-medium text-[#5B6270] uppercase tracking-wider">
                                    {{ authRole }}
                                </p>
                            </div>
                        </div>
                    </div>
                </header>

                <!-- Page Header (if title, description, #header, or #header-actions provided) -->
                <div v-if="title || $slots['header'] || $slots['header-actions']" class="bg-white border-b border-[#E5E7EB] px-4 sm:px-6 lg:px-8 py-5">
                    <div class="max-w-[1440px] mx-auto flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="min-w-0">
                            <slot name="header">
                                <div class="flex items-center gap-2">
                                    <h2 class="text-lg sm:text-xl font-bold tracking-tight text-[#0B0D14]">
                                        {{ title }}
                                    </h2>
                                    <span class="raibix-dot h-1.5 w-1.5"></span>
                                </div>
                                <p v-if="description" class="text-xs text-[#5B6270] mt-0.5 truncate max-w-2xl">
                                    {{ description }}
                                </p>
                            </slot>
                        </div>
                        <div v-if="$slots['header-actions']" class="flex items-center gap-2.5 flex-wrap flex-shrink-0">
                            <slot name="header-actions"></slot>
                        </div>
                    </div>
                </div>

                <!-- Flash Message Toast Notification -->
                <div v-if="$page.props.flash?.success || $page.props.flash?.error || $page.props.flash?.message" class="px-4 sm:px-6 lg:px-8 pt-4 max-w-[1440px] w-full mx-auto">
                    <div 
                        :class="[
                            $page.props.flash?.error 
                                ? 'bg-[#FEF2F2] border-[#FCA5A5] text-[#991B1B]' 
                                : 'bg-[#F0FDF4] border-[#86EFAC] text-[#166534]',
                            'rounded-xl border px-4 py-3 shadow-2xs flex items-center justify-between text-xs font-medium'
                        ]"
                    >
                        <div class="flex items-center gap-2.5">
                            <i :class="$page.props.flash?.error ? 'fa-solid fa-triangle-exclamation' : 'fa-solid fa-circle-check'" class="text-sm"></i>
                            <span>{{ $page.props.flash?.success || $page.props.flash?.error || $page.props.flash?.message }}</span>
                        </div>
                    </div>
                </div>

                <!-- Main Content Body -->
                <main class="flex-1 p-4 sm:p-6 lg:px-8 max-w-[1440px] w-full mx-auto space-y-6">
                    <slot></slot>
                </main>

                <!-- Clean Footer -->
                <footer class="border-t border-[#E5E7EB] bg-white py-4 px-4 sm:px-6 lg:px-8 mt-auto text-2xs text-[#5B6270]">
                    <div class="max-w-[1440px] mx-auto flex flex-col sm:flex-row items-center justify-between gap-2 text-center sm:text-left">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span>{{ tenant?.name || 'Raibix' }}</span>
                            <template v-if="tenant?.phone">
                                <span>•</span>
                                <span class="inline-flex items-center gap-1 text-[#5B6270]">
                                    <i class="fa-solid fa-phone text-3xs"></i>
                                    <span>{{ tenant.phone }}</span>
                                </span>
                            </template>
                            <span>•</span>
                            <span class="inline-flex items-center gap-1">
                                <span class="raibix-dot h-1 w-1"></span>
                                <span>Powered by Raibix</span>
                            </span>
                        </div>
                        <div class="text-[#5B6270]">
                            Academic Year {{ academicSession }}
                        </div>
                    </div>
                </footer>
            </div>
        </div>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #E5E7EB;
    border-radius: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #D1D5DB;
}
</style>
