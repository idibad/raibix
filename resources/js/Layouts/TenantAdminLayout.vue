<script setup>
import { ref, computed } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';

const page = usePage();
const tenant = computed(() => page.props.tenant || {});
const current_user = computed(() => page.props.auth?.user || {});
const current_role = computed(() => {
    const r = page.props.auth?.role || current_user.value?.role || current_user.value?.user_type || 'Principal';
    return r.charAt(0).toUpperCase() + r.slice(1);
});

const academicSession = computed(() => {
    return tenant.value.academic_session || 'Session 2026-2027';
});

const isMobileMenuOpen = ref(false);
const isSidebarCollapsed = ref(false);

const isActive = (path, tab = null) => {
    if (tab) {
        return page.url.includes(`tab=${tab}`) || (page.url.replace(/\?.*$/, '') === '/admin/dashboard' && tab === 'overview' && !page.url.includes('tab='));
    }
    return page.url.startsWith(path) && !page.url.includes('tab=');
};

const canAccess = (moduleName) => {
    const role = (current_user.value.user_type || 'admin').toLowerCase();
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

const userInitials = computed(() => {
    const name = current_user.value?.name || current_role.value || 'Admin';
    const parts = name.trim().split(' ');
    if (parts.length >= 2) {
        return (parts[0][0] + parts[1][0]).toUpperCase();
    }
    return name.slice(0, 2).toUpperCase();
});

const logout = () => {
    router.post('/logout');
};
</script>

<template>
    <div class="flex min-h-screen bg-[#F8F9FA] text-[#0B0D14] font-sans antialiased selection:bg-[#EEF3FF] selection:text-[#2F6BFF]">
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

        <!-- Sidebar Navigation -->
        <aside 
            :class="[
                isSidebarCollapsed ? 'lg:w-[72px]' : 'lg:w-64',
                isMobileMenuOpen ? 'translate-x-0 w-64' : '-translate-x-full lg:translate-x-0',
                'fixed lg:sticky top-0 z-50 h-screen border-r border-[#E5E7EB] bg-white flex flex-col justify-between flex-shrink-0 transition-all duration-200'
            ]"
        >
            <div class="flex flex-col min-h-0 flex-1">
                <!-- Brand Header -->
                <div class="h-16 flex items-center justify-between px-4 border-b border-[#E5E7EB]">
                    <div class="flex items-center gap-3 min-w-0">
                        <div v-if="tenant?.logo" class="h-9 w-9 rounded-xl border border-[#E5E7EB] bg-white p-1 flex items-center justify-center flex-shrink-0 overflow-hidden shadow-2xs">
                            <img :src="tenant?.logo" :alt="tenant?.name || 'School Logo'" class="max-h-full max-w-full object-contain" />
                        </div>
                        <div v-else class="h-9 w-9 rounded-xl border border-[#E5E7EB] bg-[#F8F9FA] flex items-center justify-center flex-shrink-0 text-[#0B0D14] shadow-2xs">
                            <span class="raibix-dot h-3 w-3"></span>
                        </div>
                        <div v-if="!isSidebarCollapsed || isMobileMenuOpen" class="min-w-0 truncate">
                            <h1 class="text-xs font-semibold tracking-tight text-[#0B0D14] truncate">{{ tenant?.name || 'School Portal' }}</h1>
                            <div class="flex items-center gap-1.5 mt-0.5">
                                <span class="raibix-dot h-1.5 w-1.5"></span>
                                <p class="text-3xs font-medium text-[#5B6270] uppercase tracking-wider truncate">{{ current_role }}</p>
                            </div>
                        </div>
                    </div>
                    <!-- Collapse Button -->
                    <button 
                        @click="isMobileMenuOpen ? (isMobileMenuOpen = false) : (isSidebarCollapsed = !isSidebarCollapsed)"
                        class="h-7 w-7 rounded-lg text-[#5B6270] hover:text-[#0B0D14] hover:bg-[#F8F9FA] transition-colors flex items-center justify-center flex-shrink-0"
                        :title="isMobileMenuOpen ? 'Close Menu' : (isSidebarCollapsed ? 'Expand Sidebar' : 'Collapse Sidebar')"
                    >
                        <i :class="isMobileMenuOpen ? 'fa-solid fa-xmark' : (isSidebarCollapsed ? 'fa-solid fa-chevron-right' : 'fa-solid fa-chevron-left')" class="text-xs"></i>
                    </button>
                </div>

                <!-- Side Menu Links -->
                <nav class="p-2 space-y-1 overflow-y-auto flex-1 custom-scrollbar">
                    <!-- Overview -->
                    <Link 
                        v-if="canAccess('dashboard')"
                        href="/admin/dashboard?tab=overview"
                        :title="(isSidebarCollapsed && !isMobileMenuOpen) ? 'Overview' : ''"
                        :class="[
                            isActive('/admin/dashboard', 'overview') ? 'bg-[#F4F5F8] text-[#0B0D14] font-semibold' : 'text-[#5B6270] hover:bg-[#F8F9FA] hover:text-[#0B0D14] font-normal',
                            (isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center px-2 py-2.5' : 'justify-between px-3 py-2'
                        ]"
                        class="w-full flex items-center rounded-xl text-xs transition-colors group cursor-pointer"
                    >
                        <div class="flex items-center" :class="(isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center' : 'gap-2.5 min-w-0'">
                            <span v-if="isActive('/admin/dashboard', 'overview')" class="raibix-dot h-1.5 w-1.5 flex-shrink-0"></span>
                            <span v-else-if="!isSidebarCollapsed || isMobileMenuOpen" class="h-1.5 w-1.5 flex-shrink-0 opacity-0"></span>
                            <span class="flex-shrink-0 flex items-center justify-center w-4 text-xs"><i class="fa-solid fa-house"></i></span>
                            <span v-if="!isSidebarCollapsed || isMobileMenuOpen" class="truncate">Overview</span>
                        </div>
                    </Link>

                    <!-- Users -->
                    <Link 
                        v-if="canAccess('users')"
                        href="/admin/dashboard?tab=users"
                        :title="(isSidebarCollapsed && !isMobileMenuOpen) ? 'User Management' : ''"
                        :class="[
                            isActive('/admin/dashboard', 'users') ? 'bg-[#F4F5F8] text-[#0B0D14] font-semibold' : 'text-[#5B6270] hover:bg-[#F8F9FA] hover:text-[#0B0D14] font-normal',
                            (isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center px-2 py-2.5' : 'justify-between px-3 py-2'
                        ]"
                        class="w-full flex items-center rounded-xl text-xs transition-colors group cursor-pointer"
                    >
                        <div class="flex items-center" :class="(isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center' : 'gap-2.5 min-w-0'">
                            <span v-if="isActive('/admin/dashboard', 'users')" class="raibix-dot h-1.5 w-1.5 flex-shrink-0"></span>
                            <span v-else-if="!isSidebarCollapsed || isMobileMenuOpen" class="h-1.5 w-1.5 flex-shrink-0 opacity-0"></span>
                            <span class="flex-shrink-0 flex items-center justify-center w-4 text-xs"><i class="fa-solid fa-users"></i></span>
                            <span v-if="!isSidebarCollapsed || isMobileMenuOpen" class="truncate">User Management</span>
                        </div>
                    </Link>

                    <!-- Students -->
                    <Link 
                        v-if="canAccess('students')"
                        href="/admin/dashboard?tab=students"
                        :title="(isSidebarCollapsed && !isMobileMenuOpen) ? 'Student Management' : ''"
                        :class="[
                            isActive('/admin/dashboard', 'students') ? 'bg-[#F4F5F8] text-[#0B0D14] font-semibold' : 'text-[#5B6270] hover:bg-[#F8F9FA] hover:text-[#0B0D14] font-normal',
                            (isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center px-2 py-2.5' : 'justify-between px-3 py-2'
                        ]"
                        class="w-full flex items-center rounded-xl text-xs transition-colors group cursor-pointer"
                    >
                        <div class="flex items-center" :class="(isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center' : 'gap-2.5 min-w-0'">
                            <span v-if="isActive('/admin/dashboard', 'students')" class="raibix-dot h-1.5 w-1.5 flex-shrink-0"></span>
                            <span v-else-if="!isSidebarCollapsed || isMobileMenuOpen" class="h-1.5 w-1.5 flex-shrink-0 opacity-0"></span>
                            <span class="flex-shrink-0 flex items-center justify-center w-4 text-xs"><i class="fa-solid fa-graduation-cap"></i></span>
                            <span v-if="!isSidebarCollapsed || isMobileMenuOpen" class="truncate">Students</span>
                        </div>
                    </Link>

                    <!-- Admissions -->
                    <Link 
                        v-if="canAccess('admissions')"
                        href="/admin/dashboard?tab=admissions"
                        :title="(isSidebarCollapsed && !isMobileMenuOpen) ? 'Admissions' : ''"
                        :class="[
                            isActive('/admin/dashboard', 'admissions') ? 'bg-[#F4F5F8] text-[#0B0D14] font-semibold' : 'text-[#5B6270] hover:bg-[#F8F9FA] hover:text-[#0B0D14] font-normal',
                            (isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center px-2 py-2.5' : 'justify-between px-3 py-2'
                        ]"
                        class="w-full flex items-center rounded-xl text-xs transition-colors group cursor-pointer"
                    >
                        <div class="flex items-center" :class="(isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center' : 'gap-2.5 min-w-0'">
                            <span v-if="isActive('/admin/dashboard', 'admissions')" class="raibix-dot h-1.5 w-1.5 flex-shrink-0"></span>
                            <span v-else-if="!isSidebarCollapsed || isMobileMenuOpen" class="h-1.5 w-1.5 flex-shrink-0 opacity-0"></span>
                            <span class="flex-shrink-0 flex items-center justify-center w-4 text-xs"><i class="fa-solid fa-clipboard-list"></i></span>
                            <span v-if="!isSidebarCollapsed || isMobileMenuOpen" class="truncate">Admissions</span>
                        </div>
                    </Link>

                    <!-- Classes -->
                    <Link 
                        v-if="canAccess('classes')"
                        href="/admin/dashboard?tab=classes"
                        :title="(isSidebarCollapsed && !isMobileMenuOpen) ? 'Classes & Sections' : ''"
                        :class="[
                            isActive('/admin/dashboard', 'classes') ? 'bg-[#F4F5F8] text-[#0B0D14] font-semibold' : 'text-[#5B6270] hover:bg-[#F8F9FA] hover:text-[#0B0D14] font-normal',
                            (isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center px-2 py-2.5' : 'justify-between px-3 py-2'
                        ]"
                        class="w-full flex items-center rounded-xl text-xs transition-colors group cursor-pointer"
                    >
                        <div class="flex items-center" :class="(isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center' : 'gap-2.5 min-w-0'">
                            <span v-if="isActive('/admin/dashboard', 'classes')" class="raibix-dot h-1.5 w-1.5 flex-shrink-0"></span>
                            <span v-else-if="!isSidebarCollapsed || isMobileMenuOpen" class="h-1.5 w-1.5 flex-shrink-0 opacity-0"></span>
                            <span class="flex-shrink-0 flex items-center justify-center w-4 text-xs"><i class="fa-solid fa-chalkboard-user"></i></span>
                            <span v-if="!isSidebarCollapsed || isMobileMenuOpen" class="truncate">Classes</span>
                        </div>
                    </Link>

                    <!-- HR & Payroll -->
                    <Link 
                        v-if="canAccess('hr')"
                        href="/admin/hr"
                        :title="(isSidebarCollapsed && !isMobileMenuOpen) ? 'HR & Payroll' : ''"
                        :class="[
                            isActive('/admin/hr') ? 'bg-[#F4F5F8] text-[#0B0D14] font-semibold' : 'text-[#5B6270] hover:bg-[#F8F9FA] hover:text-[#0B0D14] font-normal',
                            (isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center px-2 py-2.5' : 'justify-between px-3 py-2'
                        ]"
                        class="w-full flex items-center rounded-xl text-xs transition-colors group cursor-pointer"
                    >
                        <div class="flex items-center" :class="(isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center' : 'gap-2.5 min-w-0'">
                            <span v-if="isActive('/admin/hr')" class="raibix-dot h-1.5 w-1.5 flex-shrink-0"></span>
                            <span v-else-if="!isSidebarCollapsed || isMobileMenuOpen" class="h-1.5 w-1.5 flex-shrink-0 opacity-0"></span>
                            <span class="flex-shrink-0 flex items-center justify-center w-4 text-xs"><i class="fa-solid fa-users-gear"></i></span>
                            <span v-if="!isSidebarCollapsed || isMobileMenuOpen" class="truncate">HR & Payroll</span>
                        </div>
                    </Link>

                    <!-- Finance & Refunds -->
                    <Link 
                        v-if="canAccess('finance')"
                        href="/admin/finance-reports"
                        :title="(isSidebarCollapsed && !isMobileMenuOpen) ? 'Finance & Accounts' : ''"
                        :class="[
                            isActive('/admin/finance-reports') ? 'bg-[#F4F5F8] text-[#0B0D14] font-semibold' : 'text-[#5B6270] hover:bg-[#F8F9FA] hover:text-[#0B0D14] font-normal',
                            (isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center px-2 py-2.5' : 'justify-between px-3 py-2'
                        ]"
                        class="w-full flex items-center rounded-xl text-xs transition-colors group cursor-pointer"
                    >
                        <div class="flex items-center" :class="(isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center' : 'gap-2.5 min-w-0'">
                            <span v-if="isActive('/admin/finance-reports')" class="raibix-dot h-1.5 w-1.5 flex-shrink-0"></span>
                            <span v-else-if="!isSidebarCollapsed || isMobileMenuOpen" class="h-1.5 w-1.5 flex-shrink-0 opacity-0"></span>
                            <span class="flex-shrink-0 flex items-center justify-center w-4 text-xs"><i class="fa-solid fa-vault"></i></span>
                            <span v-if="!isSidebarCollapsed || isMobileMenuOpen" class="truncate">Finance</span>
                        </div>
                    </Link>

                    <!-- ID Cards & Certificates -->
                    <Link 
                        v-if="canAccess('certificates')"
                        href="/admin/certificates"
                        :title="(isSidebarCollapsed && !isMobileMenuOpen) ? 'Certificates' : ''"
                        :class="[
                            isActive('/admin/certificates') ? 'bg-[#F4F5F8] text-[#0B0D14] font-semibold' : 'text-[#5B6270] hover:bg-[#F8F9FA] hover:text-[#0B0D14] font-normal',
                            (isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center px-2 py-2.5' : 'justify-between px-3 py-2'
                        ]"
                        class="w-full flex items-center rounded-xl text-xs transition-colors group cursor-pointer"
                    >
                        <div class="flex items-center" :class="(isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center' : 'gap-2.5 min-w-0'">
                            <span v-if="isActive('/admin/certificates')" class="raibix-dot h-1.5 w-1.5 flex-shrink-0"></span>
                            <span v-else-if="!isSidebarCollapsed || isMobileMenuOpen" class="h-1.5 w-1.5 flex-shrink-0 opacity-0"></span>
                            <span class="flex-shrink-0 flex items-center justify-center w-4 text-xs"><i class="fa-solid fa-id-card"></i></span>
                            <span v-if="!isSidebarCollapsed || isMobileMenuOpen" class="truncate">Certificates</span>
                        </div>
                    </Link>

                    <!-- Library Management -->
                    <Link 
                        v-if="canAccess('library')"
                        href="/admin/library"
                        :title="(isSidebarCollapsed && !isMobileMenuOpen) ? 'Library' : ''"
                        :class="[
                            isActive('/admin/library') ? 'bg-[#F4F5F8] text-[#0B0D14] font-semibold' : 'text-[#5B6270] hover:bg-[#F8F9FA] hover:text-[#0B0D14] font-normal',
                            (isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center px-2 py-2.5' : 'justify-between px-3 py-2'
                        ]"
                        class="w-full flex items-center rounded-xl text-xs transition-colors group cursor-pointer"
                    >
                        <div class="flex items-center" :class="(isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center' : 'gap-2.5 min-w-0'">
                            <span v-if="isActive('/admin/library')" class="raibix-dot h-1.5 w-1.5 flex-shrink-0"></span>
                            <span v-else-if="!isSidebarCollapsed || isMobileMenuOpen" class="h-1.5 w-1.5 flex-shrink-0 opacity-0"></span>
                            <span class="flex-shrink-0 flex items-center justify-center w-4 text-xs"><i class="fa-solid fa-book-open"></i></span>
                            <span v-if="!isSidebarCollapsed || isMobileMenuOpen" class="truncate">Library</span>
                        </div>
                    </Link>

                    <!-- Transport Management -->
                    <Link 
                        v-if="canAccess('transport')"
                        href="/admin/transport"
                        :title="(isSidebarCollapsed && !isMobileMenuOpen) ? 'Transport' : ''"
                        :class="[
                            isActive('/admin/transport') ? 'bg-[#F4F5F8] text-[#0B0D14] font-semibold' : 'text-[#5B6270] hover:bg-[#F8F9FA] hover:text-[#0B0D14] font-normal',
                            (isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center px-2 py-2.5' : 'justify-between px-3 py-2'
                        ]"
                        class="w-full flex items-center rounded-xl text-xs transition-colors group cursor-pointer"
                    >
                        <div class="flex items-center" :class="(isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center' : 'gap-2.5 min-w-0'">
                            <span v-if="isActive('/admin/transport')" class="raibix-dot h-1.5 w-1.5 flex-shrink-0"></span>
                            <span v-else-if="!isSidebarCollapsed || isMobileMenuOpen" class="h-1.5 w-1.5 flex-shrink-0 opacity-0"></span>
                            <span class="flex-shrink-0 flex items-center justify-center w-4 text-xs"><i class="fa-solid fa-bus"></i></span>
                            <span v-if="!isSidebarCollapsed || isMobileMenuOpen" class="truncate">Transport</span>
                        </div>
                    </Link>

                    <!-- Hostel Management -->
                    <Link 
                        v-if="canAccess('hostels')"
                        href="/admin/hostels"
                        :title="(isSidebarCollapsed && !isMobileMenuOpen) ? 'Hostels' : ''"
                        :class="[
                            isActive('/admin/hostels') ? 'bg-[#F4F5F8] text-[#0B0D14] font-semibold' : 'text-[#5B6270] hover:bg-[#F8F9FA] hover:text-[#0B0D14] font-normal',
                            (isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center px-2 py-2.5' : 'justify-between px-3 py-2'
                        ]"
                        class="w-full flex items-center rounded-xl text-xs transition-colors group cursor-pointer"
                    >
                        <div class="flex items-center" :class="(isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center' : 'gap-2.5 min-w-0'">
                            <span v-if="isActive('/admin/hostels')" class="raibix-dot h-1.5 w-1.5 flex-shrink-0"></span>
                            <span v-else-if="!isSidebarCollapsed || isMobileMenuOpen" class="h-1.5 w-1.5 flex-shrink-0 opacity-0"></span>
                            <span class="flex-shrink-0 flex items-center justify-center w-4 text-xs"><i class="fa-solid fa-bed"></i></span>
                            <span v-if="!isSidebarCollapsed || isMobileMenuOpen" class="truncate">Hostels</span>
                        </div>
                    </Link>

                    <!-- Inventory Management -->
                    <Link 
                        v-if="canAccess('inventory')"
                        href="/admin/inventory"
                        :title="(isSidebarCollapsed && !isMobileMenuOpen) ? 'Inventory' : ''"
                        :class="[
                            isActive('/admin/inventory') ? 'bg-[#F4F5F8] text-[#0B0D14] font-semibold' : 'text-[#5B6270] hover:bg-[#F8F9FA] hover:text-[#0B0D14] font-normal',
                            (isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center px-2 py-2.5' : 'justify-between px-3 py-2'
                        ]"
                        class="w-full flex items-center rounded-xl text-xs transition-colors group cursor-pointer"
                    >
                        <div class="flex items-center" :class="(isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center' : 'gap-2.5 min-w-0'">
                            <span v-if="isActive('/admin/inventory')" class="raibix-dot h-1.5 w-1.5 flex-shrink-0"></span>
                            <span v-else-if="!isSidebarCollapsed || isMobileMenuOpen" class="h-1.5 w-1.5 flex-shrink-0 opacity-0"></span>
                            <span class="flex-shrink-0 flex items-center justify-center w-4 text-xs"><i class="fa-solid fa-boxes-stacked"></i></span>
                            <span v-if="!isSidebarCollapsed || isMobileMenuOpen" class="truncate">Inventory</span>
                        </div>
                    </Link>

                    <!-- Academic Calendar -->
                    <Link 
                        v-if="canAccess('calendar')"
                        href="/admin/calendar"
                        :title="(isSidebarCollapsed && !isMobileMenuOpen) ? 'Academic Calendar' : ''"
                        :class="[
                            isActive('/admin/calendar') ? 'bg-[#F4F5F8] text-[#0B0D14] font-semibold' : 'text-[#5B6270] hover:bg-[#F8F9FA] hover:text-[#0B0D14] font-normal',
                            (isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center px-2 py-2.5' : 'justify-between px-3 py-2'
                        ]"
                        class="w-full flex items-center rounded-xl text-xs transition-colors group cursor-pointer"
                    >
                        <div class="flex items-center" :class="(isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center' : 'gap-2.5 min-w-0'">
                            <span v-if="isActive('/admin/calendar')" class="raibix-dot h-1.5 w-1.5 flex-shrink-0"></span>
                            <span v-else-if="!isSidebarCollapsed || isMobileMenuOpen" class="h-1.5 w-1.5 flex-shrink-0 opacity-0"></span>
                            <span class="flex-shrink-0 flex items-center justify-center w-4 text-xs"><i class="fa-solid fa-calendar-days"></i></span>
                            <span v-if="!isSidebarCollapsed || isMobileMenuOpen" class="truncate">Calendar</span>
                        </div>
                    </Link>

                    <!-- Settings -->
                    <Link 
                        v-if="canAccess('settings')"
                        href="/admin/settings-erp"
                        :title="(isSidebarCollapsed && !isMobileMenuOpen) ? 'Settings' : ''"
                        :class="[
                            isActive('/admin/settings-erp') ? 'bg-[#F4F5F8] text-[#0B0D14] font-semibold' : 'text-[#5B6270] hover:bg-[#F8F9FA] hover:text-[#0B0D14] font-normal',
                            (isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center px-2 py-2.5' : 'justify-between px-3 py-2'
                        ]"
                        class="w-full flex items-center rounded-xl text-xs transition-colors group cursor-pointer"
                    >
                        <div class="flex items-center" :class="(isSidebarCollapsed && !isMobileMenuOpen) ? 'justify-center' : 'gap-2.5 min-w-0'">
                            <span v-if="isActive('/admin/settings-erp')" class="raibix-dot h-1.5 w-1.5 flex-shrink-0"></span>
                            <span v-else-if="!isSidebarCollapsed || isMobileMenuOpen" class="h-1.5 w-1.5 flex-shrink-0 opacity-0"></span>
                            <span class="flex-shrink-0 flex items-center justify-center w-4 text-xs"><i class="fa-solid fa-gear"></i></span>
                            <span v-if="!isSidebarCollapsed || isMobileMenuOpen" class="truncate">Settings</span>
                        </div>
                    </Link>
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
                            <p class="text-xs font-semibold text-[#0B0D14] truncate">{{ current_user?.name || 'Administrator' }}</p>
                            <p class="text-3xs text-[#5B6270] truncate capitalize">{{ current_role }}</p>
                        </div>
                    </div>

                    <button 
                        @click="logout"
                        :title="'Sign Out'"
                        class="h-7 w-7 rounded-lg text-[#5B6270] hover:text-[#DC2626] hover:bg-[#FEE2E2]/40 transition-colors flex items-center justify-center flex-shrink-0"
                    >
                        <i class="fa-solid fa-arrow-right-from-bracket text-xs"></i>
                    </button>
                </div>
            </div>
        </aside>

        <!-- Main Workspace -->
        <main class="flex-1 overflow-x-hidden min-h-screen flex flex-col min-w-0">
            <!-- Top Bar Header -->
            <header class="sticky top-0 z-30 h-16 bg-white/95 backdrop-blur-md border-b border-[#E5E7EB] px-4 sm:px-6 lg:px-8 flex items-center justify-between gap-4">
                <div class="flex items-center gap-3 min-w-0">
                    <button 
                        @click="isMobileMenuOpen = true"
                        class="lg:hidden h-8 w-8 rounded-lg text-[#5B6270] hover:text-[#0B0D14] hover:bg-[#F8F9FA] transition-colors border border-[#E5E7EB] flex items-center justify-center flex-shrink-0"
                        aria-label="Open navigation menu"
                    >
                        <i class="fa-solid fa-bars text-sm"></i>
                    </button>

                    <div class="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-2xs font-medium bg-[#F8F9FA] text-[#0B0D14] border border-[#E5E7EB]">
                        <span class="raibix-dot h-1.5 w-1.5"></span>
                        <span>{{ academicSession }}</span>
                    </div>

                    <div class="min-w-0">
                        <slot name="header"></slot>
                    </div>
                </div>

                <div class="flex items-center gap-3 flex-shrink-0">
                    <slot name="actions"></slot>
                </div>
            </header>

            <!-- Flash Notification Banner -->
            <div v-if="$page.props.flash?.success || $page.props.flash?.error" class="px-4 sm:px-6 lg:px-8 pt-4">
                <div :class="$page.props.flash?.success ? 'bg-[#F0FDF4] border-[#86EFAC] text-[#166534]' : 'bg-[#FEF2F2] border-[#FCA5A5] text-[#991B1B]'" class="rounded-xl border px-4 py-3 shadow-2xs flex items-center justify-between text-xs font-medium">
                    <div class="flex items-center gap-2.5">
                        <i :class="$page.props.flash?.success ? 'fa-solid fa-circle-check' : 'fa-solid fa-triangle-exclamation'" class="text-sm"></i>
                        <span>{{ $page.props.flash?.success || $page.props.flash?.error }}</span>
                    </div>
                </div>
            </div>

            <!-- Main Content Area -->
            <div class="p-4 sm:p-6 lg:p-8 space-y-6 flex-1 max-w-[1440px] w-full mx-auto">
                <slot></slot>
            </div>

            <!-- Clean Footer -->
            <footer class="border-t border-[#E5E7EB] bg-white py-4 px-4 sm:px-6 lg:px-8 mt-auto text-2xs text-[#5B6270]">
                <div class="max-w-[1440px] mx-auto flex flex-col sm:flex-row items-center justify-between gap-2 text-center sm:text-left">
                    <div class="flex items-center gap-2">
                        <span>{{ tenant?.name || 'Raibix' }}</span>
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
        </main>
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
