<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    subdomain: {
        type: String,
        default: () => window.location.host
    },
    reason: {
        type: String,
        default: 'not_found' // 'not_found', 'suspended', 'inactive', 'page_not_found', 'error'
    },
    status: {
        type: String,
        default: null
    },
    tenantName: {
        type: String,
        default: null
    },
    centralUrl: {
        type: String,
        default: 'http://localhost:8000'
    },
    message: {
        type: String,
        default: null
    }
});

const searchQuery = ref('');
const isSearching = ref(false);
const searchResults = ref([]);
const hasSearched = ref(false);
const showDiagnostics = ref(false);
const clientInfo = ref({
    userAgent: '',
    timestamp: new Date().toISOString(),
    ipPlaceholder: 'Client Session / Auto-Detected'
});

onMounted(() => {
    if (typeof window !== 'undefined') {
        clientInfo.value.userAgent = window.navigator.userAgent;
        clientInfo.value.timestamp = new Date().toLocaleString();
    }
});

const isSuspendedOrInactive = computed(() => {
    return ['suspended', 'inactive', 'disabled', 'deactivated', 'pending'].includes(props.reason?.toLowerCase()) ||
           ['suspended', 'inactive', 'disabled', 'deactivated', 'pending'].includes(props.status?.toLowerCase());
});

const errorDetails = computed(() => {
    if (isSuspendedOrInactive.value) {
        const displayStatus = props.status ? props.status.toUpperCase() : 'SUSPENDED';
        return {
            badge: `PORTAL ${displayStatus}`,
            badgeClass: 'bg-amber-50 text-amber-800 border-amber-200/80 shadow-sm shadow-amber-500/5',
            iconClass: 'fa-solid fa-shield-halved text-amber-600',
            iconBgClass: 'bg-gradient-to-br from-amber-50 to-orange-100/80 border-amber-200/60 shadow-inner',
            title: `${props.tenantName || 'School'} Portal Temporarily Inactive`,
            description: props.message || `This school portal subscription is currently marked as '${props.status || props.reason}'. Administrative, teacher, and student logins are paused until service is resumed.`,
            helpChecklist: [
                'If you are a student, parent, or teacher, please contact your school administration or main office for scheduling updates.',
                'If you are the School Principal or IT Administrator, please check your billing invoice status or contact Central Platform Support.',
                'Data integrity is fully preserved during suspension; no records or files have been removed.'
            ],
            errorCode: `ERR_TENANT_${displayStatus}`
        };
    }

    if (props.reason === 'page_not_found') {
        return {
            badge: '404 PAGE NOT FOUND',
            badgeClass: 'bg-blue-50 text-blue-800 border-blue-200/80 shadow-sm shadow-blue-500/5',
            iconClass: 'fa-solid fa-compass text-blue-600',
            iconBgClass: 'bg-gradient-to-br from-blue-50 to-indigo-100/80 border-blue-200/60 shadow-inner',
            title: `Page Not Found on ${props.tenantName || 'Portal'}`,
            description: props.message || `The link or address you requested does not exist on this school portal, or it may have been moved to another section.`,
            helpChecklist: [
                'Verify that the URL path in the browser address bar is correct.',
                'Return to your portal homepage or use the side navigation menu to find your page.',
                'If you clicked a bookmark that no longer works, please update your saved link.'
            ],
            errorCode: 'ERR_PORTAL_PAGE_NOT_FOUND'
        };
    }

    // Default: 'not_found' (subdomain error or typo)
    return {
        badge: 'SUBDOMAIN UNRESOLVED',
        badgeClass: 'bg-rose-50 text-rose-800 border-rose-200/80 shadow-sm shadow-rose-500/5',
        iconClass: 'fa-solid fa-cloud-slash text-rose-600',
        iconBgClass: 'bg-gradient-to-br from-rose-50 to-red-100/80 border-rose-200/60 shadow-inner',
        title: 'School Subdomain Not Found',
        description: props.message || `We could not locate an active school portal registered at "${props.subdomain}". This usually happens due to a typing mistake in the web address or an unassigned domain name.`,
        helpChecklist: [
            `Check the spelling of your school name in the address bar (${props.subdomain}).`,
            'Use our instant Portal Finder below to search for your school by name or code.',
            'If your school portal was registered in the last few minutes, DNS and SSL routing may take up to 2-3 minutes to propagate fully.'
        ],
        errorCode: 'ERR_TENANT_DOMAIN_UNRESOLVED'
    };
});

let searchTimeout = null;
const performSearch = async () => {
    if (!searchQuery.value || searchQuery.value.trim().length < 2) {
        searchResults.value = [];
        hasSearched.value = false;
        return;
    }

    isSearching.value = true;
    hasSearched.value = true;

    try {
        const cleanQuery = encodeURIComponent(searchQuery.value.trim());
        const endpoint = `${props.centralUrl.replace(/\/$/, '')}/api/check-subdomain?q=${cleanQuery}`;
        const res = await fetch(endpoint, {
            headers: {
                'Accept': 'application/json'
            }
        });
        if (res.ok) {
            const data = await res.json();
            searchResults.value = data.results || [];
        } else {
            searchResults.value = [];
        }
    } catch (e) {
        console.error('Portal finder search failed:', e);
        searchResults.value = [];
    } finally {
        isSearching.value = false;
    }
};

const onSearchInput = () => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(performSearch, 300);
};

const reloadPage = () => {
    window.location.reload();
};
</script>

<template>
    <div class="min-h-screen bg-gradient-to-b from-[#F8F9FA] via-[#F1F3F4] to-[#E8EAED] text-[#202124] flex flex-col justify-between selection:bg-[#E8F0FE] selection:text-[#1A73E8] relative overflow-hidden font-sans">
        <!-- Background Ambient Glow Accents -->
        <div class="absolute -top-40 -left-40 w-96 h-96 bg-blue-400/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-40 -right-40 w-96 h-96 bg-rose-400/10 rounded-full blur-3xl pointer-events-none"></div>

        <!-- Top Header / Navigation Bar -->
        <header class="w-full max-w-5xl mx-auto px-6 py-6 flex items-center justify-between border-b border-[#DADCE0]/60 relative z-10">
            <a :href="centralUrl" class="flex items-center gap-3 group focus:outline-none focus:ring-2 focus:ring-blue-500 rounded-lg p-1">
                <div class="h-10 w-10 rounded-xl bg-gradient-to-br from-[#1A73E8] to-[#1557B0] flex items-center justify-center text-white shadow-md shadow-blue-500/20 group-hover:scale-105 transition-transform duration-200">
                    <i class="fa-solid fa-graduation-cap text-lg"></i>
                </div>
                <div class="flex flex-col">
                    <span class="font-bold text-base tracking-tight text-[#202124] group-hover:text-[#1A73E8] transition-colors">Raibix Platform</span>
                    <span class="text-[11px] font-medium text-[#5F6368]">School Management Platform</span>
                </div>
            </a>

            <div class="flex items-center gap-3">
                <a :href="centralUrl" class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-xs font-semibold bg-white border border-[#DADCE0] text-[#3C4043] hover:bg-[#F8F9FA] hover:border-[#1A73E8] hover:text-[#1A73E8] transition-all shadow-sm">
                    <i class="fa-solid fa-house text-xs"></i>
                    <span>Central Home</span>
                </a>
                <button @click="reloadPage" class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-xs font-semibold bg-[#E8F0FE] text-[#1A73E8] hover:bg-[#D2E3FC] transition-all shadow-sm">
                    <i class="fa-solid fa-rotate-right text-xs"></i>
                    <span>Retry Access</span>
                </button>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="w-full max-w-3xl mx-auto px-6 py-10 flex-1 flex flex-col justify-center relative z-10">
            <!-- Error Card Container -->
            <div class="bg-white/95 backdrop-blur-md rounded-3xl border border-[#DADCE0] shadow-xl shadow-gray-900/5 p-8 sm:p-10 transition-all duration-300 hover:shadow-2xl hover:border-blue-200 space-y-8">
                
                <!-- Status Badge & Icon -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-[#F1F3F4] pb-6">
                    <div class="flex items-center gap-4">
                        <div :class="[errorDetails.iconBgClass, 'h-16 w-16 rounded-2xl border flex items-center justify-center text-2xl shrink-0 transition-transform hover:scale-105 duration-300']">
                            <i :class="errorDetails.iconClass"></i>
                        </div>
                        <div>
                            <span :class="[errorDetails.badgeClass, 'inline-block px-3 py-1 rounded-full text-[11px] font-bold tracking-wider border mb-1.5 uppercase']">
                                <i class="fa-solid fa-circle-exclamation mr-1 text-[10px]"></i> {{ errorDetails.badge }}
                            </span>
                            <h1 class="text-2xl sm:text-3xl font-extrabold text-[#202124] tracking-tight">{{ errorDetails.title }}</h1>
                        </div>
                    </div>

                    <div class="bg-[#F8F9FA] border border-[#E8EAED] rounded-xl px-4 py-2.5 text-right sm:self-center">
                        <div class="text-[10px] font-semibold uppercase text-[#5F6368] tracking-wider">Subdomain Host</div>
                        <div class="text-xs font-mono font-bold text-[#1A73E8] break-all max-w-[200px] sm:max-w-xs truncate" :title="subdomain">{{ subdomain }}</div>
                    </div>
                </div>

                <!-- Explanation Box -->
                <div class="space-y-4 text-[#3C4043] leading-relaxed">
                    <p class="text-base sm:text-lg font-normal bg-[#F8F9FA] p-4.5 rounded-2xl border border-[#E8EAED] text-[#202124]">
                        {{ errorDetails.description }}
                    </p>

                    <!-- Help Checklist -->
                    <div class="space-y-2.5 pt-2">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-[#5F6368] flex items-center gap-1.5">
                            <i class="fa-solid fa-list-check text-[#1A73E8]"></i> What can you check right now?
                        </h3>
                        <ul class="space-y-2">
                            <li v-for="(item, index) in errorDetails.helpChecklist" :key="index" class="flex items-start gap-3 text-sm text-[#3C4043] bg-white p-3 rounded-xl border border-[#DADCE0]/50 shadow-2xs">
                                <span class="h-5 w-5 rounded-full bg-[#E8F0FE] text-[#1A73E8] flex items-center justify-center text-xs font-bold shrink-0 mt-0.5">{{ index + 1 }}</span>
                                <span>{{ item }}</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Interactive Portal Finder / School Search (Shown primarily when domain is unresolved or page not found) -->
                <div v-if="!isSuspendedOrInactive" class="bg-gradient-to-br from-[#F8F9FA] to-[#E8F0FE]/40 rounded-2xl border border-[#DADCE0] p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-bold text-[#202124] flex items-center gap-2">
                                <i class="fa-solid fa-magnifying-glass-location text-[#1A73E8]"></i>
                                Instant School Portal Finder
                            </h3>
                            <p class="text-xs text-[#5F6368]">Type your school's name or code below to find your correct active portal URL.</p>
                        </div>
                    </div>

                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#5F6368]">
                            <i class="fa-solid fa-search text-sm"></i>
                        </div>
                        <input 
                            type="text"
                            v-model="searchQuery"
                            @input="onSearchInput"
                            placeholder="e.g. Beaconhouse, City School, Oxford, or school code..."
                            class="w-full pl-10 pr-10 py-3 bg-white border border-[#DADCE0] rounded-xl text-sm text-[#202124] placeholder-[#80868B] focus:outline-none focus:border-[#1A73E8] focus:ring-2 focus:ring-[#1A73E8]/20 transition-all shadow-inner"
                        />
                        <div v-if="isSearching" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-[#1A73E8]">
                            <i class="fa-solid fa-circle-notch fa-spin text-sm"></i>
                        </div>
                        <button v-else-if="searchQuery" @click="searchQuery = ''; searchResults = []; hasSearched = false" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-[#5F6368] hover:text-[#202124] transition-colors">
                            <i class="fa-solid fa-xmark text-sm"></i>
                        </button>
                    </div>

                    <!-- Search Results -->
                    <div v-if="hasSearched" class="space-y-2 pt-1">
                        <div v-if="searchResults.length > 0" class="space-y-2">
                            <div class="text-[11px] font-semibold text-[#1A73E8] uppercase tracking-wider">Matching Schools Found:</div>
                            <div v-for="school in searchResults" :key="school.domain" class="flex items-center justify-between bg-white p-3.5 rounded-xl border border-[#DADCE0] hover:border-[#1A73E8] hover:shadow-md transition-all group">
                                <div class="flex items-center gap-3">
                                    <div class="h-9 w-9 rounded-lg bg-[#E8F0FE] text-[#1A73E8] flex items-center justify-center font-bold text-sm shrink-0 group-hover:bg-[#1A73E8] group-hover:text-white transition-colors">
                                        <i class="fa-solid fa-school"></i>
                                    </div>
                                    <div>
                                        <div class="font-bold text-sm text-[#202124] group-hover:text-[#1A73E8] transition-colors">{{ school.name }}</div>
                                        <div class="text-xs font-mono text-[#5F6368] flex items-center gap-1">
                                            <i class="fa-solid fa-link text-[10px]"></i> {{ school.domain }}
                                        </div>
                                    </div>
                                </div>
                                <a :href="school.url" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-[#1A73E8] text-white text-xs font-semibold hover:bg-[#1557B0] transition-all shadow-sm shrink-0">
                                    <span>Go to Portal</span>
                                    <i class="fa-solid fa-arrow-right-long text-[11px]"></i>
                                </a>
                            </div>
                        </div>
                        <div v-else-if="!isSearching" class="text-center py-4 text-xs text-[#5F6368] bg-white/60 rounded-xl border border-dashed border-[#DADCE0]">
                            <i class="fa-solid fa-circle-info mr-1 text-amber-500"></i>
                            No active portal found matching "{{ searchQuery }}". Double check your school registration with your administration.
                        </div>
                    </div>
                </div>

                <!-- Collapsible System Diagnostics for Tech Support -->
                <div class="border-t border-[#F1F3F4] pt-6">
                    <button 
                        @click="showDiagnostics = !showDiagnostics"
                        class="flex items-center justify-between w-full text-xs font-semibold text-[#5F6368] hover:text-[#202124] transition-colors py-1 focus:outline-none"
                    >
                        <span class="flex items-center gap-2">
                            <i class="fa-solid fa-terminal text-[#1A73E8]"></i>
                            <span>Technical Diagnostics & Error Codes</span>
                        </span>
                        <i :class="['fa-solid transition-transform duration-200', showDiagnostics ? 'fa-chevron-up' : 'fa-chevron-down']"></i>
                    </button>

                    <div v-show="showDiagnostics" class="mt-3 p-4 bg-[#202124] text-[#E8EAED] rounded-2xl font-mono text-xs space-y-2.5 shadow-inner overflow-x-auto transition-all">
                        <div class="flex justify-between border-b border-gray-700 pb-2">
                            <span class="text-gray-400">Error Code:</span>
                            <span class="text-rose-400 font-bold">{{ errorDetails.errorCode }}</span>
                        </div>
                        <div class="flex justify-between border-b border-gray-700 pb-2">
                            <span class="text-gray-400">Requested Subdomain:</span>
                            <span class="text-amber-300 font-bold break-all">{{ subdomain }}</span>
                        </div>
                        <div class="flex justify-between border-b border-gray-700 pb-2">
                            <span class="text-gray-400">Resolution Status:</span>
                            <span class="text-gray-200">{{ reason ? reason.toUpperCase() : 'UNKNOWN' }}</span>
                        </div>
                        <div class="flex justify-between border-b border-gray-700 pb-2">
                            <span class="text-gray-400">Timestamp:</span>
                            <span class="text-gray-300">{{ clientInfo.timestamp }}</span>
                        </div>
                        <div class="space-y-1 pt-1">
                            <span class="text-gray-400 block">User Agent:</span>
                            <span class="text-gray-400 text-[11px] block break-all leading-normal">{{ clientInfo.userAgent || 'Server Side Rendered' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Primary Call to Action Buttons -->
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-4 border-t border-[#F1F3F4]">
                    <a :href="centralUrl" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-full bg-gradient-to-r from-[#1A73E8] to-[#1557B0] text-white text-sm font-semibold shadow-md shadow-blue-500/20 hover:shadow-lg hover:shadow-blue-500/30 hover:-translate-y-0.5 transition-all duration-200">
                        <i class="fa-solid fa-arrow-left"></i>
                        <span>Back to Central SaaS Portal</span>
                    </a>

                    <div class="flex items-center gap-3 w-full sm:w-auto justify-end">
                        <button @click="reloadPage" class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-1.5 px-5 py-3 rounded-full bg-[#F8F9FA] border border-[#DADCE0] text-[#3C4043] text-sm font-medium hover:bg-[#E8F0FE] hover:border-[#1A73E8] hover:text-[#1A73E8] transition-all shadow-xs">
                            <i class="fa-solid fa-rotate text-xs"></i>
                            <span>Reload</span>
                        </button>
                        <a :href="`mailto:support@lmscloud.test?subject=Portal%20Access%20Help%20for%20${subdomain}`" class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-1.5 px-5 py-3 rounded-full bg-[#F8F9FA] border border-[#DADCE0] text-[#3C4043] text-sm font-medium hover:bg-[#FCE8E6] hover:border-[#FAD2CF] hover:text-[#C5221F] transition-all shadow-xs">
                            <i class="fa-solid fa-headset text-xs"></i>
                            <span>Help Desk</span>
                        </a>
                    </div>
                </div>

            </div>
        </main>

        <!-- Footer -->
        <footer class="w-full max-w-5xl mx-auto px-6 py-6 text-center text-xs text-[#5F6368] border-t border-[#DADCE0]/40 relative z-10 flex flex-col sm:flex-row items-center justify-between gap-3">
            <div>
                &copy; {{ new Date().getFullYear() }} Raibix. All rights reserved.
            </div>
            <div class="flex items-center gap-4">
                <a :href="centralUrl" class="hover:text-[#1A73E8] transition-colors">Platform Status</a>
                <span>&bull;</span>
                <a :href="`${centralUrl}/#features`" class="hover:text-[#1A73E8] transition-colors">Documentation</a>
                <span>&bull;</span>
                <a :href="`mailto:support@lmscloud.test`" class="hover:text-[#1A73E8] transition-colors">Support</a>
            </div>
        </footer>
    </div>
</template>
