/**
 * Role-based access permission helper for tenant admin modules.
 */
export function canAdminAccess(moduleName, role = 'admin') {
    const r = (role || 'admin').toLowerCase();
    if (['admin', 'principal', 'vice_principal'].includes(r)) {
        return true;
    }
    if (r === 'librarian') {
        return ['dashboard', 'overview', 'notices', 'calendar', 'library'].includes(moduleName);
    }
    if (r === 'accountant') {
        return ['dashboard', 'overview', 'fees', 'defaulters', 'fee_approvals', 'finance', 'hr', 'notices'].includes(moduleName);
    }
    if (r === 'registrar' || r === 'receptionist') {
        return ['dashboard', 'overview', 'admissions', 'students', 'classes', 'certificates', 'notices', 'calendar'].includes(moduleName);
    }
    return false;
}

/**
 * Returns the unified flat navigation items for the Principal/Admin portal.
 * Clean, simple, non-nested list matching the original dashboard design.
 */
export function getAdminNavItems(options = {}) {
    const {
        role = 'principal',
        canAccess = (mod) => canAdminAccess(mod, role),
        defaultersCount = null,
        pendingApprovalsCount = null,
        pendingInquiriesCount = null,
    } = options;

    const items = [];

    // Core / Overview
    if (canAccess('dashboard') || canAccess('overview')) {
        items.push({ id: 'overview', label: 'Command Center', icon: 'fa-solid fa-gauge-high', tab: 'overview', href: '/admin/dashboard?tab=overview' });
    }

    // Academics & Students
    if (canAccess('students')) {
        items.push({ id: 'students', label: 'Students', icon: 'fa-solid fa-graduation-cap', tab: 'students', href: '/admin/dashboard?tab=students' });
    }
    if (canAccess('users')) {
        items.push({ id: 'users', label: 'Staff & Users', icon: 'fa-solid fa-users', tab: 'users', href: '/admin/dashboard?tab=users' });
    }
    if (canAccess('admissions')) {
        items.push({ 
            id: 'admissions', 
            label: 'Admissions', 
            icon: 'fa-solid fa-clipboard-list', 
            tab: 'admissions', 
            href: '/admin/dashboard?tab=admissions',
            badge: pendingInquiriesCount ? `${pendingInquiriesCount}` : null,
            badgeClass: 'bg-[#EEF3FF] text-[#2F6BFF] font-semibold'
        });
    }
    if (canAccess('classes')) {
        items.push({ id: 'classes', label: 'Classes & Sections', icon: 'fa-solid fa-chalkboard-user', tab: 'classes', href: '/admin/dashboard?tab=classes' });
    }
    if (canAccess('timetables')) {
        items.push({ id: 'timetables', label: 'Timetables', icon: 'fa-solid fa-table-cells', tab: 'timetables', href: '/admin/dashboard?tab=timetables' });
    }
    if (canAccess('fees')) {
        items.push({ id: 'fees', label: 'Fee Structures', icon: 'fa-solid fa-receipt', tab: 'fees', href: '/admin/dashboard?tab=fees' });
        items.push({ 
            id: 'defaulters', 
            label: 'Defaulters', 
            icon: 'fa-solid fa-triangle-exclamation', 
            tab: 'defaulters', 
            href: '/admin/dashboard?tab=defaulters',
            badge: defaultersCount ? `${defaultersCount}` : null,
            badgeClass: 'bg-[#FEF2F2] text-[#991B1B] font-semibold'
        });
        items.push({ 
            id: 'fee_approvals', 
            label: 'Fee Approvals', 
            icon: 'fa-solid fa-stamp', 
            tab: 'fee_approvals', 
            href: '/admin/dashboard?tab=fee_approvals',
            badge: pendingApprovalsCount ? `${pendingApprovalsCount}` : null,
            badgeClass: 'bg-[#FEF3C7] text-[#92400E] font-semibold'
        });
    }
    if (canAccess('staff_attendance')) {
        items.push({ id: 'staff_attendance', label: 'Staff Attendance', icon: 'fa-solid fa-calendar-check', tab: 'staff_attendance', href: '/admin/dashboard?tab=staff_attendance' });
    }
    if (canAccess('exams')) {
        items.push({ id: 'exams', label: 'Exams & Grades', icon: 'fa-solid fa-award', tab: 'exams', href: '/admin/dashboard?tab=exams' });
    }
    if (canAccess('notices')) {
        items.push({ id: 'notices', label: 'Notice Board', icon: 'fa-solid fa-bullhorn', tab: 'notices', href: '/admin/dashboard?tab=notices' });
    }
    if (canAccess('sports')) {
        items.push({ id: 'sports', label: 'Sports & Clubs', icon: 'fa-solid fa-trophy', tab: 'sports', href: '/admin/dashboard?tab=sports' });
    }
    if (canAccess('penalties')) {
        items.push({ id: 'penalties', label: 'Penalties & Fines', icon: 'fa-solid fa-gavel', tab: 'penalties', href: '/admin/dashboard?tab=penalties' });
    }

    // ERP Modules (Always present in the menu, never disappears)
    if (canAccess('hr')) {
        items.push({ id: 'erp_hr', label: 'HR & Payroll', icon: 'fa-solid fa-users-gear', href: '/admin/hr' });
    }
    if (canAccess('finance')) {
        items.push({ id: 'erp_finance', label: 'Finance & Accounts', icon: 'fa-solid fa-vault', href: '/admin/finance-reports' });
    }
    if (canAccess('library')) {
        items.push({ id: 'erp_library', label: 'Library Catalog', icon: 'fa-solid fa-book-open', href: '/admin/library' });
    }
    if (canAccess('transport')) {
        items.push({ id: 'erp_transport', label: 'Transport', icon: 'fa-solid fa-bus', href: '/admin/transport' });
    }
    if (canAccess('hostels')) {
        items.push({ id: 'erp_hostels', label: 'Hostels', icon: 'fa-solid fa-bed', href: '/admin/hostels' });
    }
    if (canAccess('inventory')) {
        items.push({ id: 'erp_inventory', label: 'Inventory', icon: 'fa-solid fa-boxes-stacked', href: '/admin/inventory' });
    }
    if (canAccess('calendar')) {
        items.push({ id: 'erp_calendar', label: 'Academic Calendar', icon: 'fa-solid fa-calendar-days', href: '/admin/calendar' });
    }
    if (canAccess('certificates')) {
        items.push({ id: 'erp_certificates', label: 'Certificates & IDs', icon: 'fa-solid fa-id-card', href: '/admin/certificates' });
    }

    // System & Settings
    if (canAccess('reports')) {
        items.push({ id: 'reports', label: 'Reports Export', icon: 'fa-solid fa-file-export', tab: 'reports', href: '/admin/dashboard?tab=reports' });
    }
    if (canAccess('subscription')) {
        items.push({ id: 'subscription', label: 'Platform License', icon: 'fa-solid fa-shield-halved', tab: 'subscription', href: '/admin/dashboard?tab=subscription' });
    }
    if (canAccess('settings')) {
        items.push({ id: 'settings', label: 'Settings', icon: 'fa-solid fa-gear', tab: 'settings', href: '/admin/dashboard?tab=settings' });
    }
    if (canAccess('settings') || canAccess('settings_erp')) {
        items.push({ id: 'erp_settings', label: 'ERP Settings', icon: 'fa-solid fa-sliders', href: '/admin/settings-erp' });
    }

    return items;
}

// Alias for backwards compatibility
export const getAdminNavTree = getAdminNavItems;
