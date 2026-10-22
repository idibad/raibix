<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import TenantAdminLayout from '@/Layouts/TenantAdminLayout.vue';

const props = defineProps({
    settings: Object,
    academic_years: Array,
});

const activeTab = ref('modules'); // 'modules', 'academics'

// Modules Configuration form
const form = useForm({
    enable_library: props.settings.enable_library,
    enable_transport: props.settings.enable_transport,
    enable_hostels: props.settings.enable_hostels,
    enable_inventory: props.settings.enable_inventory,
    enable_hr: props.settings.enable_hr,
    enable_finance: props.settings.enable_finance,
    enable_calendar: props.settings.enable_calendar,
    enable_certificates: props.settings.enable_certificates,
});

const saveSettings = () => {
    form.post(route('tenant.admin.settings_erp.update'));
};

// Academic Years management forms
const yearForm = useForm({
    name: '',
    start_date: '',
    end_date: '',
});

const showYearModal = ref(false);
const showTermModal = ref(false);
const selectedYearId = ref(null);

const termForm = useForm({
    academic_year_id: '',
    name: '',
    start_date: '',
    end_date: '',
});

const submitYear = () => {
    yearForm.post(route('tenant.admin.academic_years.store'), {
        onSuccess: () => {
            showYearModal.value = false;
            yearForm.reset();
        }
    });
};

const openTermModal = (yearId) => {
    selectedYearId.value = yearId;
    termForm.academic_year_id = yearId;
    showTermModal.value = true;
};

const submitTerm = () => {
    termForm.post(route('tenant.admin.terms.store'), {
        onSuccess: () => {
            showTermModal.value = false;
            selectedYearId.value = null;
            termForm.reset();
        }
    });
};

const toggleYearStatus = (id) => {
    useForm({}).post(route('tenant.admin.academic_years.toggle', id));
};
</script>

<template>
    <Head title="ERP Settings & Session Configurations" />

    <TenantAdminLayout>
        <template #header>
            <h2 class="text-xl font-bold text-[#202124] tracking-tight">ERP Settings & Setup</h2>
        </template>

        <div class="space-y-6">
            <!-- Tabs -->
            <div class="flex border-b border-gray-200">
                <button @click="activeTab = 'modules'" :class="activeTab === 'modules' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="py-4 px-6 border-b-2 font-medium text-sm transition-all focus:outline-none">
                    ERP Modules Activation
                </button>
                <button @click="activeTab = 'academics'" :class="activeTab === 'academics' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="py-4 px-6 border-b-2 font-medium text-sm transition-all focus:outline-none">
                    Academic Years & Terms
                </button>
            </div>

            <!-- Tab 1: Module Toggles -->
            <div v-if="activeTab === 'modules'" class="max-w-2xl bg-white rounded-2xl border border-gray-200 p-6 shadow-sm space-y-6">
                <div>
                    <h3 class="text-lg font-bold text-gray-900">ERP Module Enable/Disable Toggles</h3>
                    <p class="text-xs text-gray-500 mt-1">Select which ERP modules are active for this school campus.</p>
                </div>

                <form @submit.prevent="saveSettings" class="space-y-6">
                    <div class="divide-y divide-gray-100">
                        <div class="py-4 flex items-center justify-between">
                            <div>
                                <label class="font-bold text-gray-900 text-sm">Library Management Module</label>
                                <p class="text-xs text-gray-500 mt-0.5">Enables book catalog registries and checkout logs.</p>
                            </div>
                            <input type="checkbox" v-model="form.enable_library" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 w-5 h-5" />
                        </div>

                        <div class="py-4 flex items-center justify-between">
                            <div>
                                <label class="font-bold text-gray-900 text-sm">Transport Management Module</label>
                                <p class="text-xs text-gray-500 mt-0.5">Enables vehicle fleets registries, routes stops and allocated passes.</p>
                            </div>
                            <input type="checkbox" v-model="form.enable_transport" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 w-5 h-5" />
                        </div>

                        <div class="py-4 flex items-center justify-between">
                            <div>
                                <label class="font-bold text-gray-900 text-sm">Hostel Boarding Module</label>
                                <p class="text-xs text-gray-500 mt-0.5">Enables room capacities configuration and checkin/vacate allocations.</p>
                            </div>
                            <input type="checkbox" v-model="form.enable_hostels" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 w-5 h-5" />
                        </div>

                        <div class="py-4 flex items-center justify-between">
                            <div>
                                <label class="font-bold text-gray-900 text-sm">Inventory & Asset Management</label>
                                <p class="text-xs text-gray-500 mt-0.5">Enables stock registries, classifications, and staff issuance logs.</p>
                            </div>
                            <input type="checkbox" v-model="form.enable_inventory" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 w-5 h-5" />
                        </div>

                        <div class="py-4 flex items-center justify-between">
                            <div>
                                <label class="font-bold text-gray-900 text-sm">HR & Payroll Module</label>
                                <p class="text-xs text-gray-500 mt-0.5">Enables staff salary structures config, payroll generation, and payslips.</p>
                            </div>
                            <input type="checkbox" v-model="form.enable_hr" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 w-5 h-5" />
                        </div>

                        <div class="py-4 flex items-center justify-between">
                            <div>
                                <label class="font-bold text-gray-900 text-sm">Finance & Expense Reports</label>
                                <p class="text-xs text-gray-500 mt-0.5">Enables vendor details database, expense ledger, and refunds.</p>
                            </div>
                            <input type="checkbox" v-model="form.enable_finance" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 w-5 h-5" />
                        </div>

                        <div class="py-4 flex items-center justify-between">
                            <div>
                                <label class="font-bold text-gray-900 text-sm">Academic Calendar Module</label>
                                <p class="text-xs text-gray-500 mt-0.5">Enables event listings, parent notifications, and school closures.</p>
                            </div>
                            <input type="checkbox" v-model="form.enable_calendar" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 w-5 h-5" />
                        </div>

                        <div class="py-4 flex items-center justify-between">
                            <div>
                                <label class="font-bold text-gray-900 text-sm">Certificates Issuance</label>
                                <p class="text-xs text-gray-500 mt-0.5">Enables custom character/appreciation certificate templates.</p>
                            </div>
                            <input type="checkbox" v-model="form.enable_certificates" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 w-5 h-5" />
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-semibold text-sm shadow-sm transition-all">
                            Apply Configurations
                        </button>
                    </div>
                </form>
            </div>

            <!-- Tab 2: Academic Years & Terms -->
            <div v-if="activeTab === 'academics'" class="space-y-6">
                <div class="flex justify-between items-center">
                    <div>
                        <h3 class="font-bold text-gray-900 text-lg">Academic Sessions & Terms</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Setup academic calendars, semesters, and select active sessions.</p>
                    </div>
                    <button @click="showYearModal = true" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold shadow-xs">
                        + Create Academic Year
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div v-for="year in academic_years" :key="year.id" class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm space-y-4">
                        <div class="flex justify-between items-start">
                            <div>
                                <h4 class="text-base font-bold text-gray-900">{{ year.name }}</h4>
                                <p class="text-xs text-gray-500 mt-1">Duration: {{ new Date(year.start_date).toLocaleDateString() }} - {{ new Date(year.end_date).toLocaleDateString() }}</p>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span :class="year.is_active ? 'bg-green-50 text-green-700 border-green-200' : 'bg-gray-50 text-gray-500 border-gray-200'" class="px-2.5 py-1 text-xs font-semibold rounded-full border">
                                    {{ year.is_active ? 'Active Session' : 'Inactive' }}
                                </span>
                                <button @click="toggleYearStatus(year.id)" class="text-xs text-indigo-600 hover:underline">
                                    Toggle Active
                                </button>
                            </div>
                        </div>

                        <!-- Terms of this academic year -->
                        <div class="border-t border-gray-100 pt-4 space-y-2">
                            <div class="flex justify-between items-center">
                                <h5 class="text-xs font-bold text-gray-500 uppercase tracking-wider">Academic Terms / Exams Session</h5>
                                <button @click="openTermModal(year.id)" class="text-xs font-semibold text-indigo-600 hover:underline">+ Add Term</button>
                            </div>
                            <ul v-if="year.terms.length > 0" class="space-y-1">
                                <li v-for="term in year.terms" :key="term.id" class="flex justify-between items-center bg-gray-50 p-2.5 rounded-lg text-xs border border-gray-100">
                                    <span class="font-semibold text-gray-800">{{ term.name }}</span>
                                    <span class="text-gray-500">
                                        {{ new Date(term.start_date).toLocaleDateString() }} - {{ new Date(term.end_date).toLocaleDateString() }}
                                    </span>
                                </li>
                            </ul>
                            <p v-else class="text-xs text-gray-400 italic">No academic terms defined for this session.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Academic Year Modal -->
        <div v-if="showYearModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs">
            <div class="bg-white rounded-2xl border border-gray-200 max-w-md w-full p-6 shadow-xl">
                <div class="flex justify-between items-center pb-4 border-b border-gray-100 mb-4">
                    <h3 class="text-lg font-bold text-gray-900">Create Academic Year Session</h3>
                    <button @click="showYearModal = false" class="text-gray-400 hover:text-gray-600 text-2xl font-bold">&times;</button>
                </div>
                <form @submit.prevent="submitYear" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Session Name</label>
                        <input type="text" v-model="yearForm.name" placeholder="Academic Session Name" required class="w-full rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Start Date</label>
                            <input type="date" v-model="yearForm.start_date" required class="w-full rounded-lg border-gray-300 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">End Date</label>
                            <input type="date" v-model="yearForm.end_date" required class="w-full rounded-lg border-gray-300 text-sm" />
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <button type="button" @click="showYearModal = false" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">Cancel</button>
                        <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700">Save Session</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Create Term Modal -->
        <div v-if="showTermModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs">
            <div class="bg-white rounded-2xl border border-gray-200 max-w-md w-full p-6 shadow-xl">
                <div class="flex justify-between items-center pb-4 border-b border-gray-100 mb-4">
                    <h3 class="text-lg font-bold text-gray-900">Define Academic Term</h3>
                    <button @click="showTermModal = false; selectedYearId = null" class="text-gray-400 hover:text-gray-600 text-2xl font-bold">&times;</button>
                </div>
                <form @submit.prevent="submitTerm" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Term Name</label>
                        <input type="text" v-model="termForm.name" placeholder="Term / Semester Name" required class="w-full rounded-lg border-gray-300 text-sm" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Start Date</label>
                            <input type="date" v-model="termForm.start_date" required class="w-full rounded-lg border-gray-300 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">End Date</label>
                            <input type="date" v-model="termForm.end_date" required class="w-full rounded-lg border-gray-300 text-sm" />
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <button type="button" @click="showTermModal = false; selectedYearId = null" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">Cancel</button>
                        <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700">Save Term</button>
                    </div>
                </form>
            </div>
        </div>
    </TenantAdminLayout>
</template>
