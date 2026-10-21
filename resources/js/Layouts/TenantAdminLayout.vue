<script setup>
import { ref, computed } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';

const page = usePage();
const tenant = computed(() => page.props.tenant || {});
const current_user = computed(() => page.props.auth?.user || {});
const current_role = computed(() => page.props.auth?.role || 'Principal');

const isMobileMenuOpen = ref(false);
const isSidebarCollapsed = ref(false);

const isActive = (path, tab = null) => {
    if (tab) {
        return page.url.includes(`tab=${tab}`) || (page.url.replace(/\?.*$/, '') === '/admin/dashboard' && tab === 'overview' && !page.url.includes('tab='));
    }
    return page.url.startsWith(path) && !page.url.includes('tab=');
};

const canAccess = (moduleName) => {
    const role = current_user.value.user_type || 'admin';
    if (['admin', 'principal', 'vice_principal'].includes(role)) {
        return true;
    }
    if (role === 'librarian') {
        return ['library', 'calendar', 'notices', 'dashboard'].includes(moduleName);
    }
    if (role === 'accountant') {
        return ['finance', 'hr', 'fees', 'notices', 'dashboard'].includes(moduleName);
    }
    if (role === 'receptionist' || role === 'registrar') {
        return ['admissions', 'students', 'classes', 'notices', 'dashboard', 'calendar'].includes(moduleName);
    }
    return true;
};

const logout = () => {
    router.post('/admin/logout');
};
</script>

<template>
    <div class="flex min-h-screen bg-[#F8F9FA] text-[#202124] selection:bg-[#E8F0FE] selection:text-[#1A73E8] font-sans">
        <!-- Mobile Sidebar Backdrop -->
        <div 
            v-if="isMobileMenuOpen" 
            @click="isMobileMenuOpen = false"
            class="fixed inset-0 z-40 bg-black/40 backdrop-blur-xs lg:hidden transition-opacity duration-300"
        ></div>

        <!-- Sidebar Navigation -->
        <aside 
            :class="[
                isSidebarCollapsed ? 'lg:w-20' : 'lg:w-64',
                isMobileMenuOpen ? 'translate-x-0 w-64' : '-translate-x-full lg:translate-x-0',
                'fixed lg:sticky top-0 z-50 h-screen border-r border-[#DADCE0] bg-white flex flex-col justify-between flex-shrink-0 transition-all duration-300 shadow-xl lg:shadow-none'
            ]"
        >
            <div class="flex flex-col min-h-0 flex-1">
                <!-- Brand Header -->
                <div class="flex items-center justify-between px-4 sm:px-6 py-5 border-b border-[#DADCE0] min-h-[73px]">
                    <div class="flex items-center space-x-3 min-w-0 overflow-hidden">
                        <div v-if="tenant?.logo" class="flex h-10 w-10 flex-shrink-0 items-center justify-center overflow-hidden rounded-xl bg-white p-0.5 border border-[#DADCE0] shadow-sm">
                            <img :src="tenant?.logo" :alt="tenant?.name" class="max-h-full max-w-full object-contain" />
                        </div>
                        <div v-else class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-[#E8F0FE] font-bold text-[#1A73E8] text-lg border border-[#D2E3FC] shadow-sm">
                            <i class="fa-solid fa-graduation-cap"></i>
                        </div>
                        <div v-if="!isSidebarCollapsed || isMobileMenuOpen" class="min-w-0 transition-all duration-200">
                            <h1 class="font-bold text-base tracking-tight text-[#202124] truncate">{{ tenant?.name || 'School Portal' }}</h1>
                            <p class="text-xs text-[#5F6368] truncate capitalize">{{ current_role || 'Principal' }} Dashboard</p>
                        </div>
                    </div>
                    <!-- Collapse / Close Button -->
                    <button 
                        @click="isMobileMenuOpen ? (isMobileMenuOpen = false) : (isSidebarCollapsed = !isSidebarCollapsed)"
                        class="h-8 w-8 rounded-lg flex items-center justify-center text-[#5F6368] hover:bg-[#F8F9FA] hover:text-[#202124] transition-colors flex-shrink-0"
                        :title="isMobileMenuOpen ? 'Close Menu' : (isSidebarCollapsed ? 'Expand Sidebar' : 'Collapse Sidebar')"
                    >
                        <span class="text-base font-bold"><i :class="isSidebarCollapsed ? 'fa-solid fa-arrow-right' : 'fa-solid fa-arrow-left'"></i></span>
                    </button>
                </div>

                <!-- Side Menu Links -->
                <nav class="p-3 sm:p-4 space-y-1 overflow-y-auto flex-1">
                    <!-- Overview -->
                    <Link 
                        v-if="canAccess('dashboard')"
                        href="/admin/dashboard?tab=overview"
                        :title="(isSidebarCollapsed && !isMobileMenuOpen) ? 'Overview' : ''"
                        :class="[
                            isActive('/admin/dashboard', 'overview') ? 'bg-[#E8F0FE] text-[#1A73E8] font-bold shadow-xs' : 'text-[#5F6368] hover:bg-[#F8F9FA] hover:text-[#202124] font-medium',
                            (isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center px-2 py-3' : 'justify-between px-4 py-3'
                        ]"
                        class="w-full flex items-center rounded-xl text-sm transition-all"
                    >
                        <div class="flex items-center" :class="(isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center' : 'space-x-3'">
                            <span class="text-lg flex-shrink-0 flex items-center justify-center w-6"><i class="fa-solid fa-house text-base"></i></span>
                            <span v-if="!isSidebarCollapsed || isMobileMenuOpen" class="truncate">Overview</span>
                        </div>
                    </Link>

                    <!-- Users -->
                    <Link 
                        v-if="canAccess('users')"
                        href="/admin/dashboard?tab=users"
                        :title="(isSidebarCollapsed && !isMobileMenuOpen) ? 'User Management' : ''"
                        :class="[
                            isActive('/admin/dashboard', 'users') ? 'bg-[#E8F0FE] text-[#1A73E8] font-bold shadow-xs' : 'text-[#5F6368] hover:bg-[#F8F9FA] hover:text-[#202124] font-medium',
                            (isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center px-2 py-3' : 'justify-between px-4 py-3'
                        ]"
                        class="w-full flex items-center rounded-xl text-sm transition-all"
                    >
                        <div class="flex items-center" :class="(isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center' : 'space-x-3'">
                            <span class="text-lg flex-shrink-0 flex items-center justify-center w-6"><i class="fa-solid fa-users text-base"></i></span>
                            <span v-if="!isSidebarCollapsed || isMobileMenuOpen" class="truncate">User Management</span>
                        </div>
                    </Link>

                    <!-- Students -->
                    <Link 
                        v-if="canAccess('students')"
                        href="/admin/dashboard?tab=students"
                        :title="(isSidebarCollapsed && !isMobileMenuOpen) ? 'Student Management' : ''"
                        :class="[
                            isActive('/admin/dashboard', 'students') ? 'bg-[#E8F0FE] text-[#1A73E8] font-bold shadow-xs' : 'text-[#5F6368] hover:bg-[#F8F9FA] hover:text-[#202124] font-medium',
                            (isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center px-2 py-3' : 'justify-between px-4 py-3'
                        ]"
                        class="w-full flex items-center rounded-xl text-sm transition-all"
                    >
                        <div class="flex items-center" :class="(isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center' : 'space-x-3'">
                            <span class="text-lg flex-shrink-0 flex items-center justify-center w-6"><i class="fa-solid fa-graduation-cap text-base"></i></span>
                            <span v-if="!isSidebarCollapsed || isMobileMenuOpen" class="truncate">Student Management</span>
                        </div>
                    </Link>

                    <!-- Admissions -->
                    <Link 
                        v-if="canAccess('admissions')"
                        href="/admin/dashboard?tab=admissions"
                        :title="(isSidebarCollapsed && !isMobileMenuOpen) ? 'Admissions' : ''"
                        :class="[
                            isActive('/admin/dashboard', 'admissions') ? 'bg-[#E8F0FE] text-[#1A73E8] font-bold shadow-xs' : 'text-[#5F6368] hover:bg-[#F8F9FA] hover:text-[#202124] font-medium',
                            (isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center px-2 py-3' : 'justify-between px-4 py-3'
                        ]"
                        class="w-full flex items-center rounded-xl text-sm transition-all"
                    >
                        <div class="flex items-center" :class="(isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center' : 'space-x-3'">
                            <span class="text-lg flex-shrink-0 flex items-center justify-center w-6"><i class="fa-solid fa-clipboard-list text-base"></i></span>
                            <span v-if="!isSidebarCollapsed || isMobileMenuOpen" class="truncate">Admissions Pipeline</span>
                        </div>
                    </Link>

                    <!-- Classes -->
                    <Link 
                        v-if="canAccess('classes')"
                        href="/admin/dashboard?tab=classes"
                        :title="(isSidebarCollapsed && !isMobileMenuOpen) ? 'Classes & Sections' : ''"
                        :class="[
                            isActive('/admin/dashboard', 'classes') ? 'bg-[#E8F0FE] text-[#1A73E8] font-bold shadow-xs' : 'text-[#5F6368] hover:bg-[#F8F9FA] hover:text-[#202124] font-medium',
                            (isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center px-2 py-3' : 'justify-between px-4 py-3'
                        ]"
                        class="w-full flex items-center rounded-xl text-sm transition-all"
                    >
                        <div class="flex items-center" :class="(isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center' : 'space-x-3'">
                            <span class="text-lg flex-shrink-0 flex items-center justify-center w-6"><i class="fa-solid fa-chalkboard-user text-base"></i></span>
                            <span v-if="!isSidebarCollapsed || isMobileMenuOpen" class="truncate">Classes & Sections</span>
                        </div>
                    </Link>

                    <!-- HR & Payroll -->
                    <Link 
                        v-if="canAccess('hr')"
                        href="/admin/hr"
                        :title="(isSidebarCollapsed && !isMobileMenuOpen) ? 'HR & Payroll' : ''"
                        :class="[
                            isActive('/admin/hr') ? 'bg-[#E8F0FE] text-[#1A73E8] font-bold shadow-xs' : 'text-[#5F6368] hover:bg-[#F8F9FA] hover:text-[#202124] font-medium',
                            (isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center px-2 py-3' : 'justify-between px-4 py-3'
                        ]"
                        class="w-full flex items-center rounded-xl text-sm transition-all"
                    >
                        <div class="flex items-center" :class="(isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center' : 'space-x-3'">
                            <span class="text-lg flex-shrink-0 flex items-center justify-center w-6"><i class="fa-solid fa-users-gear text-base text-[#1A73E8]"></i></span>
                            <span v-if="!isSidebarCollapsed || isMobileMenuOpen" class="truncate">HR & Payroll</span>
                        </div>
                    </Link>

                    <!-- Finance & Refunds -->
                    <Link 
                        v-if="canAccess('finance')"
                        href="/admin/finance-reports"
                        :title="(isSidebarCollapsed && !isMobileMenuOpen) ? 'Finance & Refunds' : ''"
                        :class="[
                            isActive('/admin/finance-reports') ? 'bg-[#E8F0FE] text-[#1A73E8] font-bold shadow-xs' : 'text-[#5F6368] hover:bg-[#F8F9FA] hover:text-[#202124] font-medium',
                            (isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center px-2 py-3' : 'justify-between px-4 py-3'
                        ]"
                        class="w-full flex items-center rounded-xl text-sm transition-all"
                    >
                        <div class="flex items-center" :class="(isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center' : 'space-x-3'">
                            <span class="text-lg flex-shrink-0 flex items-center justify-center w-6"><i class="fa-solid fa-vault text-base text-[#10B981]"></i></span>
                            <span v-if="!isSidebarCollapsed || isMobileMenuOpen" class="truncate">Finance & Refunds</span>
                        </div>
                    </Link>

                    <!-- ID Cards & Certificates -->
                    <Link 
                        v-if="canAccess('certificates')"
                        href="/admin/certificates"
                        :title="(isSidebarCollapsed && !isMobileMenuOpen) ? 'ID Cards & Certificates' : ''"
                        :class="[
                            isActive('/admin/certificates') ? 'bg-[#E8F0FE] text-[#1A73E8] font-bold shadow-xs' : 'text-[#5F6368] hover:bg-[#F8F9FA] hover:text-[#202124] font-medium',
                            (isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center px-2 py-3' : 'justify-between px-4 py-3'
                        ]"
                        class="w-full flex items-center rounded-xl text-sm transition-all"
                    >
                        <div class="flex items-center" :class="(isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center' : 'space-x-3'">
                            <span class="text-lg flex-shrink-0 flex items-center justify-center w-6"><i class="fa-solid fa-id-card text-base text-[#8430CE]"></i></span>
                            <span v-if="!isSidebarCollapsed || isMobileMenuOpen" class="truncate">Certificates & ID Cards</span>
                        </div>
                    </Link>

                    <!-- Library Management -->
                    <Link 
                        v-if="canAccess('library')"
                        href="/admin/library"
                        :title="(isSidebarCollapsed && !isMobileMenuOpen) ? 'Library Management' : ''"
                        :class="[
                            isActive('/admin/library') ? 'bg-[#E8F0FE] text-[#1A73E8] font-bold shadow-xs' : 'text-[#5F6368] hover:bg-[#F8F9FA] hover:text-[#202124] font-medium',
                            (isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center px-2 py-3' : 'justify-between px-4 py-3'
                        ]"
                        class="w-full flex items-center rounded-xl text-sm transition-all"
                    >
                        <div class="flex items-center" :class="(isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center' : 'space-x-3'">
                            <span class="text-lg flex-shrink-0 flex items-center justify-center w-6"><i class="fa-solid fa-book-open text-base text-[#F59E0B]"></i></span>
                            <span v-if="!isSidebarCollapsed || isMobileMenuOpen" class="truncate">Library Management</span>
                        </div>
                    </Link>

                    <!-- Transport Management -->
                    <Link 
                        v-if="canAccess('transport')"
                        href="/admin/transport"
                        :title="(isSidebarCollapsed && !isMobileMenuOpen) ? 'Transport Management' : ''"
                        :class="[
                            isActive('/admin/transport') ? 'bg-[#E8F0FE] text-[#1A73E8] font-bold shadow-xs' : 'text-[#5F6368] hover:bg-[#F8F9FA] hover:text-[#202124] font-medium',
                            (isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center px-2 py-3' : 'justify-between px-4 py-3'
                        ]"
                        class="w-full flex items-center rounded-xl text-sm transition-all"
                    >
                        <div class="flex items-center" :class="(isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center' : 'space-x-3'">
                            <span class="text-lg flex-shrink-0 flex items-center justify-center w-6"><i class="fa-solid fa-bus text-base text-[#3C4043]"></i></span>
                            <span v-if="!isSidebarCollapsed || isMobileMenuOpen" class="truncate">Transport Management</span>
                        </div>
                    </Link>

                    <!-- Hostel Management -->
                    <Link 
                        v-if="canAccess('hostels')"
                        href="/admin/hostels"
                        :title="(isSidebarCollapsed && !isMobileMenuOpen) ? 'Hostel Management' : ''"
                        :class="[
                            isActive('/admin/hostels') ? 'bg-[#E8F0FE] text-[#1A73E8] font-bold shadow-xs' : 'text-[#5F6368] hover:bg-[#F8F9FA] hover:text-[#202124] font-medium',
                            (isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center px-2 py-3' : 'justify-between px-4 py-3'
                        ]"
                        class="w-full flex items-center rounded-xl text-sm transition-all"
                    >
                        <div class="flex items-center" :class="(isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center' : 'space-x-3'">
                            <span class="text-lg flex-shrink-0 flex items-center justify-center w-6"><i class="fa-solid fa-bed text-base text-[#137333]"></i></span>
                            <span v-if="!isSidebarCollapsed || isMobileMenuOpen" class="truncate">Hostel Management</span>
                        </div>
                    </Link>

                    <!-- Inventory Management -->
                    <Link 
                        v-if="canAccess('inventory')"
                        href="/admin/inventory"
                        :title="(isSidebarCollapsed && !isMobileMenuOpen) ? 'Inventory & Assets' : ''"
                        :class="[
                            isActive('/admin/inventory') ? 'bg-[#E8F0FE] text-[#1A73E8] font-bold shadow-xs' : 'text-[#5F6368] hover:bg-[#F8F9FA] hover:text-[#202124] font-medium',
                            (isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center px-2 py-3' : 'justify-between px-4 py-3'
                        ]"
                        class="w-full flex items-center rounded-xl text-sm transition-all"
                    >
                        <div class="flex items-center" :class="(isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center' : 'space-x-3'">
                            <span class="text-lg flex-shrink-0 flex items-center justify-center w-6"><i class="fa-solid fa-boxes-stacked text-base text-[#0284C7]"></i></span>
                            <span v-if="!isSidebarCollapsed || isMobileMenuOpen" class="truncate">Inventory & Assets</span>
                        </div>
                    </Link>

                    <!-- Academic Calendar -->
                    <Link 
                        v-if="canAccess('calendar')"
                        href="/admin/calendar"
                        :title="(isSidebarCollapsed && !isMobileMenuOpen) ? 'Academic Calendar' : ''"
                        :class="[
                            isActive('/admin/calendar') ? 'bg-[#E8F0FE] text-[#1A73E8] font-bold shadow-xs' : 'text-[#5F6368] hover:bg-[#F8F9FA] hover:text-[#202124] font-medium',
                            (isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center px-2 py-3' : 'justify-between px-4 py-3'
                        ]"
                        class="w-full flex items-center rounded-xl text-sm transition-all"
                    >
                        <div class="flex items-center" :class="(isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center' : 'space-x-3'">
                            <span class="text-lg flex-shrink-0 flex items-center justify-center w-6"><i class="fa-solid fa-calendar-check text-base text-[#D946EF]"></i></span>
                            <span v-if="!isSidebarCollapsed || isMobileMenuOpen" class="truncate">Academic Calendar</span>
                        </div>
                    </Link>

                    <!-- Settings (RBAC, Localization) -->
                    <Link 
                        v-if="canAccess('settings')"
                        href="/admin/settings-erp"
                        :title="(isSidebarCollapsed && !isMobileMenuOpen) ? 'System Settings' : ''"
                        :class="[
                            isActive('/admin/settings-erp') ? 'bg-[#E8F0FE] text-[#1A73E8] font-bold shadow-xs' : 'text-[#5F6368] hover:bg-[#F8F9FA] hover:text-[#202124] font-medium',
                            (isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center px-2 py-3' : 'justify-between px-4 py-3'
                        ]"
                        class="w-full flex items-center rounded-xl text-sm transition-all"
                    >
                        <div class="flex items-center" :class="(isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center' : 'space-x-3'">
                            <span class="text-lg flex-shrink-0 flex items-center justify-center w-6"><i class="fa-solid fa-gear text-base text-[#475569]"></i></span>
                            <span v-if="!isSidebarCollapsed || isMobileMenuOpen" class="truncate">System Settings</span>
                        </div>
                    </Link>

                </nav>
            </div>

            <!-- Sidebar Footer / Sign Out -->
            <div class="p-3 sm:p-4 border-t border-[#DADCE0] bg-[#F8F9FA] space-y-3">
                <div class="flex items-center" :class="(isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center' : 'space-x-3 px-2'">
                    <div class="h-9 w-9 rounded-full bg-[#E8F0FE] text-[#1A73E8] flex-shrink-0 flex items-center justify-center font-bold text-sm shadow-sm border border-[#D2E3FC] uppercase">
                        {{ (current_user?.name || current_role || 'P').substring(0, 2) }}
                    </div>
                    <div v-if="!isSidebarCollapsed || isMobileMenuOpen" class="flex-1 min-w-0">
                        <p class="text-xs font-bold text-[#202124] truncate">{{ current_user?.name || 'Management Office' }}</p>
                        <p class="text-2xs text-[#5F6368] truncate capitalize">{{ current_role || 'Principal' }} Portal</p>
                    </div>
                </div>
                <button 
                    @click="logout"
                    :title="(isSidebarCollapsed && !isMobileMenuOpen) ? 'Sign Out' : ''"
                    :class="(isSidebarCollapsed && !isMobileMenuOpen) ? 'py-2.5 px-2' : 'py-2 space-x-1.5'"
                    class="w-full rounded-xl bg-white border border-[#DADCE0] text-xs font-semibold text-[#C5221F] hover:bg-[#FCE8E6]/50 transition-colors shadow-2xs flex items-center justify-center"
                >
                    <i class="fa-solid fa-arrow-right-from-bracket text-base"></i>
                    <span v-if="!isSidebarCollapsed || isMobileMenuOpen">Sign Out</span>
                </button>
            </div>
        </aside>

        <!-- Main Workspace -->
        <main class="flex-1 overflow-x-hidden min-h-screen flex flex-col min-w-0">
            <!-- Top Bar Header -->
            <header class="sticky top-0 z-30 bg-white/95 backdrop-blur-md border-b border-[#DADCE0] px-4 sm:px-6 lg:px-8 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center min-w-0">
                    <button 
                        @click="isMobileMenuOpen = true"
                        class="lg:hidden mr-3 p-2 rounded-xl text-[#5F6368] hover:bg-[#F8F9FA] hover:text-[#202124] transition-colors border border-[#DADCE0] shadow-2xs flex items-center justify-center"
                        aria-label="Open Sidebar Menu"
                    >
                        <i class="fa-solid fa-bars text-lg"></i>
                    </button>
                    <div class="min-w-0">
                        <slot name="header">
                            <h2 class="text-lg sm:text-xl font-bold text-[#202124] capitalize truncate">
                                School Portal
                            </h2>
                        </slot>
                    </div>
                </div>

                <div class="flex items-center space-x-3 self-end sm:self-auto flex-shrink-0">
                    <slot name="actions"></slot>
                </div>
            </header>

            <!-- Flash Notification Banner -->
            <div v-if="$page.props.flash?.success || $page.props.flash?.error" class="px-4 sm:px-6 lg:px-8 pt-4">
                <div :class="$page.props.flash?.success ? 'bg-[#E6F4EA] border-[#CEEAD6] text-[#137333]' : 'bg-[#FCE8E6] border-[#FAD2CF] text-[#C5221F]'" class="rounded-2xl border p-4 shadow-xs flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <i :class="$page.props.flash?.success ? 'fa-solid fa-check' : 'fa-solid fa-triangle-exclamation'" class="text-lg"></i>
                        <span class="text-sm font-bold">{{ $page.props.flash?.success || $page.props.flash?.error }}</span>
                    </div>
                </div>
            </div>

            <!-- Main Content Area -->
            <div class="p-4 sm:p-6 lg:p-8 space-y-6 sm:space-y-8 flex-1">
                <slot></slot>
            </div>
        </main>
    </div>
</template>
