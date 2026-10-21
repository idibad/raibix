<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import TenantAdminLayout from '@/Layouts/TenantAdminLayout.vue';

const props = defineProps({
    staff: Array,
    salaryStructures: Object,
    payrolls: Array,
});

const activeTab = ref('structures'); // 'structures', 'payroll'
const showSalaryModal = ref(false);
const selectedUser = ref(null);

const salaryForm = useForm({
    user_id: '',
    base_salary: 0,
    allowances: [],
    deductions: [],
});

const payrollForm = useForm({
    month: new Date().getMonth() + 1,
    year: new Date().getFullYear(),
});

const newAllowance = ref({ name: '', amount: 0 });
const newDeduction = ref({ name: '', amount: 0 });

const openSalaryModal = (user) => {
    selectedUser.value = user;
    const existing = props.salaryStructures[user.id] || {};
    salaryForm.user_id = user.id;
    salaryForm.base_salary = existing.base_salary || 0;
    salaryForm.allowances = existing.allowances ? JSON.parse(JSON.stringify(existing.allowances)) : [];
    salaryForm.deductions = existing.deductions ? JSON.parse(JSON.stringify(existing.deductions)) : [];
    showSalaryModal.value = true;
};

const closeSalaryModal = () => {
    showSalaryModal.value = false;
    selectedUser.value = null;
    salaryForm.reset();
};

const addAllowance = () => {
    if (newAllowance.value.name && newAllowance.value.amount > 0) {
        salaryForm.allowances.push({ ...newAllowance.value });
        newAllowance.value = { name: '', amount: 0 };
    }
};

const removeAllowance = (index) => {
    salaryForm.allowances.splice(index, 1);
};

const addDeduction = () => {
    if (newDeduction.value.name && newDeduction.value.amount > 0) {
        salaryForm.deductions.push({ ...newDeduction.value });
        newDeduction.value = { name: '', amount: 0 };
    }
};

const removeDeduction = (index) => {
    salaryForm.deductions.splice(index, 1);
};

const saveSalaryStructure = () => {
    salaryForm.post(route('tenant.admin.hr.salary_structures.store'), {
        onSuccess: () => closeSalaryModal(),
    });
};

const generatePayroll = () => {
    payrollForm.post(route('tenant.admin.hr.payroll.generate'), {
        onSuccess: () => {
            payrollForm.reset();
        }
    });
};

const payPayslip = (payslipId) => {
    useForm({}).post(route('tenant.admin.hr.payslips.pay', payslipId));
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-PK', { style: 'currency', currency: 'PKR', maximumFractionDigits: 0 }).format(amount);
};
</script>

<template>
    <Head title="HR & Payroll Management" />
    
    <TenantAdminLayout>
        <template #header>
            <h2 class="text-xl font-bold text-[#202124] tracking-tight">HR & Payroll</h2>
        </template>

        <div class="space-y-6">
            <!-- Tabs Navigation -->
            <div class="flex border-b border-gray-200">
                <button 
                    @click="activeTab = 'structures'" 
                    :class="activeTab === 'structures' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="py-4 px-6 border-b-2 font-medium text-sm transition-all focus:outline-none"
                >
                    Salary Structures
                </button>
                <button 
                    @click="activeTab = 'payroll'" 
                    :class="activeTab === 'payroll' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="py-4 px-6 border-b-2 font-medium text-sm transition-all focus:outline-none"
                >
                    Payroll Runs
                </button>
            </div>

            <!-- Tab 1: Salary Structures -->
            <div v-if="activeTab === 'structures'" class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h3 class="font-semibold text-gray-900">Staff Salary Registry</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 font-semibold text-gray-600">Staff Name</th>
                                <th class="px-6 py-3 font-semibold text-gray-600">Role</th>
                                <th class="px-6 py-3 font-semibold text-gray-600">Base Salary</th>
                                <th class="px-6 py-3 font-semibold text-gray-600">Net Salary</th>
                                <th class="px-6 py-3 font-semibold text-gray-600">Status</th>
                                <th class="px-6 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="user in staff" :key="user.id" class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 font-medium text-gray-900">{{ user.name }}</td>
                                <td class="px-6 py-4 text-gray-500 capitalize">{{ user.user_type }}</td>
                                <td class="px-6 py-4 text-gray-900 font-semibold">
                                    {{ salaryStructures[user.id] ? formatCurrency(salaryStructures[user.id].base_salary) : 'Not Configured' }}
                                </td>
                                <td class="px-6 py-4 text-indigo-600 font-bold">
                                    {{ salaryStructures[user.id] ? formatCurrency(salaryStructures[user.id].net_salary) : 'Not Configured' }}
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="salaryStructures[user.id] ? 'bg-green-50 text-green-700 border-green-200' : 'bg-amber-50 text-amber-700 border-amber-200'" class="px-2.5 py-1 text-xs font-semibold rounded-full border">
                                        {{ salaryStructures[user.id] ? 'Configured' : 'Pending Setup' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button 
                                        @click="openSalaryModal(user)"
                                        class="px-4 py-2 text-xs font-semibold text-indigo-600 hover:text-white hover:bg-indigo-600 border border-indigo-600 rounded-lg transition-all"
                                    >
                                        Configure Salary
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab 2: Payroll Runs -->
            <div v-if="activeTab === 'payroll'" class="space-y-6">
                <!-- Generate Payroll Section -->
                <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
                    <h3 class="font-bold text-gray-900 mb-4 text-lg">Generate Monthly Payroll</h3>
                    <form @submit.prevent="generatePayroll" class="flex flex-wrap items-end gap-4">
                        <div class="w-48">
                            <label class="block text-xs font-semibold text-gray-500 mb-2">Payroll Month</label>
                            <select v-model="payrollForm.month" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                <option v-for="m in 12" :key="m" :value="m">
                                    {{ new Date(2026, m - 1).toLocaleString('default', { month: 'long' }) }}
                                </option>
                            </select>
                        </div>
                        <div class="w-48">
                            <label class="block text-xs font-semibold text-gray-500 mb-2">Payroll Year</label>
                            <select v-model="payrollForm.year" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                <option v-for="y in [2025, 2026, 2027, 2028]" :key="y" :value="y">{{ y }}</option>
                            </select>
                        </div>
                        <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-semibold text-sm shadow-sm transition-all">
                            Generate Payroll Run
                        </button>
                    </form>
                </div>

                <!-- Payroll Run Lists -->
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <h3 class="font-semibold text-gray-900">Historical Payroll Runs</h3>
                    </div>
                    <div class="divide-y divide-gray-200">
                        <div v-for="payroll in payrolls" :key="payroll.id" class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h4 class="font-bold text-gray-900 text-base">
                                        {{ new Date(2026, payroll.month - 1).toLocaleString('default', { month: 'long' }) }} {{ payroll.year }} Run
                                    </h4>
                                    <p class="text-xs text-gray-500 mt-1">Generated: {{ new Date(payroll.created_at).toLocaleDateString() }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-bold text-indigo-600 text-lg">{{ formatCurrency(payroll.total_amount) }}</p>
                                    <span :class="payroll.status === 'paid' ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800'" class="px-2.5 py-0.5 text-xs font-semibold rounded-full mt-2 inline-block capitalize">
                                        {{ payroll.status }}
                                    </span>
                                </div>
                            </div>

                            <!-- Payslips list for this run -->
                            <div class="bg-gray-50 rounded-xl overflow-hidden border border-gray-200">
                                <table class="min-w-full divide-y divide-gray-200 text-left text-xs">
                                    <thead class="bg-gray-100">
                                        <tr>
                                            <th class="px-4 py-2.5 font-semibold text-gray-600">Employee</th>
                                            <th class="px-4 py-2.5 font-semibold text-gray-600">Basic Pay</th>
                                            <th class="px-4 py-2.5 font-semibold text-gray-600">Allowances</th>
                                            <th class="px-4 py-2.5 font-semibold text-gray-600">Deductions</th>
                                            <th class="px-4 py-2.5 font-semibold text-gray-600">Net Pay</th>
                                            <th class="px-4 py-2.5 font-semibold text-gray-600">Status</th>
                                            <th class="px-4 py-2.5 text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 bg-white">
                                        <tr v-for="slip in payroll.payslips" :key="slip.id" class="hover:bg-gray-50">
                                            <td class="px-4 py-3 font-medium text-gray-900">{{ slip.user ? slip.user.name : 'Unknown' }}</td>
                                            <td class="px-4 py-3 text-gray-700">{{ formatCurrency(slip.basic_pay) }}</td>
                                            <td class="px-4 py-3 text-green-600">+{{ formatCurrency(slip.allowances) }}</td>
                                            <td class="px-4 py-3 text-red-600">-{{ formatCurrency(slip.deductions) }}</td>
                                            <td class="px-4 py-3 text-gray-900 font-bold">{{ formatCurrency(slip.net_pay) }}</td>
                                            <td class="px-4 py-3">
                                                <span :class="slip.status === 'paid' ? 'text-green-600 font-semibold' : 'text-amber-600 font-semibold'">
                                                    {{ slip.status }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <button 
                                                    v-if="slip.status !== 'paid'"
                                                    @click="payPayslip(slip.id)"
                                                    class="px-3 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded font-semibold text-xs shadow-xs transition-all"
                                                >
                                                    Mark Paid
                                                </button>
                                                <span v-else class="text-xs text-gray-400 font-medium">Fully Settled</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Configure Salary Structure Modal -->
        <div v-if="showSalaryModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs">
            <div class="bg-white rounded-2xl border border-gray-200 max-w-2xl w-full p-6 shadow-xl max-h-[90vh] overflow-y-auto">
                <div class="flex justify-between items-center pb-4 border-b border-gray-100 mb-6">
                    <h3 class="text-lg font-bold text-gray-900">Salary Structure for {{ selectedUser?.name }}</h3>
                    <button @click="closeSalaryModal" class="text-gray-400 hover:text-gray-600 text-2xl font-bold">&times;</button>
                </div>

                <div class="space-y-6">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-2">Base Salary (PKR)</label>
                        <input type="number" v-model="salaryForm.base_salary" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>

                    <!-- Allowances Section -->
                    <div class="border border-gray-100 bg-gray-50/50 rounded-xl p-4">
                        <h4 class="font-bold text-gray-900 text-sm mb-3">Allowances</h4>
                        <div class="flex gap-2 mb-3">
                            <input type="text" placeholder="Allowance Name (e.g. Medical)" v-model="newAllowance.name" class="flex-1 rounded-lg border-gray-300 text-sm" />
                            <input type="number" placeholder="Amount" v-model="newAllowance.amount" class="w-32 rounded-lg border-gray-300 text-sm" />
                            <button type="button" @click="addAllowance" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold">+</button>
                        </div>
                        <ul class="space-y-2">
                            <li v-for="(allowance, i) in salaryForm.allowances" :key="i" class="flex justify-between items-center bg-white p-2 rounded-lg border border-gray-200 text-sm">
                                <span>{{ allowance.name }}: <span class="font-semibold text-green-600">{{ formatCurrency(allowance.amount) }}</span></span>
                                <button type="button" @click="removeAllowance(i)" class="text-red-500 hover:text-red-700 font-semibold text-xs">Remove</button>
                            </li>
                        </ul>
                    </div>

                    <!-- Deductions Section -->
                    <div class="border border-gray-100 bg-gray-50/50 rounded-xl p-4">
                        <h4 class="font-bold text-gray-900 text-sm mb-3">Deductions</h4>
                        <div class="flex gap-2 mb-3">
                            <input type="text" placeholder="Deduction Name (e.g. Tax)" v-model="newDeduction.name" class="flex-1 rounded-lg border-gray-300 text-sm" />
                            <input type="number" placeholder="Amount" v-model="newDeduction.amount" class="w-32 rounded-lg border-gray-300 text-sm" />
                            <button type="button" @click="addDeduction" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold">+</button>
                        </div>
                        <ul class="space-y-2">
                            <li v-for="(deduction, i) in salaryForm.deductions" :key="i" class="flex justify-between items-center bg-white p-2 rounded-lg border border-gray-200 text-sm">
                                <span>{{ deduction.name }}: <span class="font-semibold text-red-600">{{ formatCurrency(deduction.amount) }}</span></span>
                                <button type="button" @click="removeDeduction(i)" class="text-red-500 hover:text-red-700 font-semibold text-xs">Remove</button>
                            </li>
                        </ul>
                    </div>

                    <div class="flex justify-end gap-3 border-t border-gray-100 pt-4">
                        <button type="button" @click="closeSalaryModal" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm font-semibold hover:bg-gray-50">Cancel</button>
                        <button type="button" @click="saveSalaryStructure" class="px-6 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700">Save Structure</button>
                    </div>
                </div>
            </div>
        </div>
    </TenantAdminLayout>
</template>
