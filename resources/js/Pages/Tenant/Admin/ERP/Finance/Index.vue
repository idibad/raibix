<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import TenantAdminLayout from '@/Layouts/TenantAdminLayout.vue';

const props = defineProps({
    vendors: Array,
    expenses: Array,
    refundRequests: Array,
    metrics: Object,
});

const activeTab = ref('metrics'); // 'metrics', 'expenses', 'vendors', 'refunds'
const showVendorModal = ref(false);
const showExpenseModal = ref(false);

const vendorForm = useForm({
    name: '',
    contact_person: '',
    phone: '',
    email: '',
    address: '',
});

const expenseForm = useForm({
    category: 'Operational',
    amount: 0,
    vendor_id: '',
    reference: '',
    date: new Date().toISOString().split('T')[0],
    notes: '',
});

const submitVendor = () => {
    vendorForm.post(route('tenant.admin.finance.vendors.store'), {
        onSuccess: () => {
            showVendorModal.value = false;
            vendorForm.reset();
        }
    });
};

const submitExpense = () => {
    expenseForm.post(route('tenant.admin.finance.expenses.store'), {
        onSuccess: () => {
            showExpenseModal.value = false;
            expenseForm.reset();
        }
    });
};

const updateRefundStatus = (id, status, notes = '') => {
    useForm({
        status: status,
        admin_notes: notes,
    }).post(route('tenant.admin.finance.refunds.update', id));
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-PK', { style: 'currency', currency: 'PKR', maximumFractionDigits: 0 }).format(amount);
};
</script>

<template>
    <Head title="Finance & Expense Management" />

    <TenantAdminLayout>
        <template #header>
            <h2 class="text-xl font-bold text-[#202124] tracking-tight">Finance & Expenses</h2>
        </template>

        <div class="space-y-6">
            <!-- Tabs -->
            <div class="flex border-b border-gray-200">
                <button @click="activeTab = 'metrics'" :class="activeTab === 'metrics' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="py-4 px-6 border-b-2 font-medium text-sm transition-all focus:outline-none">
                    Financial Summary
                </button>
                <button @click="activeTab = 'expenses'" :class="activeTab === 'expenses' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="py-4 px-6 border-b-2 font-medium text-sm transition-all focus:outline-none">
                    Expenses Registry
                </button>
                <button @click="activeTab = 'vendors'" :class="activeTab === 'vendors' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="py-4 px-6 border-b-2 font-medium text-sm transition-all focus:outline-none">
                    Vendors
                </button>
                <button @click="activeTab = 'refunds'" :class="activeTab === 'refunds' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="py-4 px-6 border-b-2 font-medium text-sm transition-all focus:outline-none">
                    Refund Requests
                </button>
            </div>

            <!-- Tab 1: Financial Summary & Metrics -->
            <div v-if="activeTab === 'metrics'" class="space-y-6">
                <!-- Metrics Grid -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-xs">
                        <p class="text-xs font-semibold text-gray-400 uppercase">Monthly Collection</p>
                        <h4 class="text-2xl font-bold text-gray-900 mt-2">{{ formatCurrency(metrics.monthly_collection) }}</h4>
                        <p class="text-xs text-gray-500 mt-1">Goal: {{ formatCurrency(metrics.monthly_target) }}</p>
                    </div>
                    <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-xs">
                        <p class="text-xs font-semibold text-gray-400 uppercase">Outstanding Student Fees</p>
                        <h4 class="text-2xl font-bold text-red-600 mt-2">{{ formatCurrency(metrics.total_outstanding) }}</h4>
                        <p class="text-xs text-gray-500 mt-1">Pending Challan balance</p>
                    </div>
                    <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-xs">
                        <p class="text-xs font-semibold text-gray-400 uppercase">YTD Collection</p>
                        <h4 class="text-2xl font-bold text-emerald-600 mt-2">{{ formatCurrency(metrics.ytd_collection) }}</h4>
                        <p class="text-xs text-gray-500 mt-1">Calendar year {{ new Date().getFullYear() }}</p>
                    </div>
                    <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-xs">
                        <p class="text-xs font-semibold text-gray-400 uppercase">Collection Progress</p>
                        <h4 class="text-2xl font-bold text-indigo-600 mt-2">{{ metrics.collection_percentage }}%</h4>
                        <div class="w-full bg-gray-100 rounded-full h-2.5 mt-2">
                            <div class="bg-indigo-600 h-2.5 rounded-full transition-all duration-500" :style="{ width: metrics.collection_percentage + '%' }"></div>
                        </div>
                    </div>
                </div>

                <!-- Recent Paid Challans -->
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <h3 class="font-semibold text-gray-900">Recent Paid Challans (Last 20)</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 font-semibold text-gray-600">Challan No</th>
                                    <th class="px-6 py-3 font-semibold text-gray-600">Student</th>
                                    <th class="px-6 py-3 font-semibold text-gray-600">Amount Paid</th>
                                    <th class="px-6 py-3 font-semibold text-gray-600">Payment Date</th>
                                    <th class="px-6 py-3 font-semibold text-gray-600">Payment Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr v-for="challan in metrics.recent_transactions" :key="challan.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 font-semibold text-gray-900">{{ challan.challan_number }}</td>
                                    <td class="px-6 py-4 text-gray-700">{{ challan.student?.user?.name }}</td>
                                    <td class="px-6 py-4 text-emerald-600 font-bold">{{ formatCurrency(challan.paid_amount) }}</td>
                                    <td class="px-6 py-4 text-gray-500">{{ new Date(challan.updated_at).toLocaleDateString() }}</td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 bg-green-50 text-green-700 border border-green-200 text-xs font-semibold rounded-full">Paid</span>
                                    </td>
                                </tr>
                                <tr v-if="metrics.recent_transactions.length === 0">
                                    <td colspan="5" class="px-6 py-8 text-center text-gray-400">No recently paid challans found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Tab 2: Expenses Registry -->
            <div v-if="activeTab === 'expenses'" class="space-y-6">
                <div class="flex justify-between items-center">
                    <h3 class="font-bold text-gray-900 text-lg">School Expense Ledger</h3>
                    <button @click="showExpenseModal = true" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold shadow-xs flex items-center space-x-1">
                        <span>+ Record Expense</span>
                    </button>
                </div>

                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 font-semibold text-gray-600">Category</th>
                                <th class="px-6 py-3 font-semibold text-gray-600">Amount</th>
                                <th class="px-6 py-3 font-semibold text-gray-600">Vendor</th>
                                <th class="px-6 py-3 font-semibold text-gray-600">Reference</th>
                                <th class="px-6 py-3 font-semibold text-gray-600">Date</th>
                                <th class="px-6 py-3 font-semibold text-gray-600">Notes</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="expense in expenses" :key="expense.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-medium text-gray-900">{{ expense.category }}</td>
                                <td class="px-6 py-4 text-red-600 font-bold">{{ formatCurrency(expense.amount) }}</td>
                                <td class="px-6 py-4 text-gray-700">{{ expense.vendor ? expense.vendor.name : 'N/A' }}</td>
                                <td class="px-6 py-4 text-gray-500 font-mono">{{ expense.reference || 'N/A' }}</td>
                                <td class="px-6 py-4 text-gray-500">{{ new Date(expense.date).toLocaleDateString() }}</td>
                                <td class="px-6 py-4 text-gray-500 max-w-xs truncate">{{ expense.notes || '-' }}</td>
                            </tr>
                            <tr v-if="expenses.length === 0">
                                <td colspan="6" class="px-6 py-8 text-center text-gray-400">No expenses recorded yet.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab 3: Vendors -->
            <div v-if="activeTab === 'vendors'" class="space-y-6">
                <div class="flex justify-between items-center">
                    <h3 class="font-bold text-gray-900 text-lg">Suppliers & Vendors</h3>
                    <button @click="showVendorModal = true" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold shadow-xs">
                        + Register Vendor
                    </button>
                </div>

                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 font-semibold text-gray-600">Vendor Name</th>
                                <th class="px-6 py-3 font-semibold text-gray-600">Contact Person</th>
                                <th class="px-6 py-3 font-semibold text-gray-600">Phone</th>
                                <th class="px-6 py-3 font-semibold text-gray-600">Email</th>
                                <th class="px-6 py-3 font-semibold text-gray-600">Address</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="vendor in vendors" :key="vendor.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-medium text-gray-900">{{ vendor.name }}</td>
                                <td class="px-6 py-4 text-gray-700">{{ vendor.contact_person || '-' }}</td>
                                <td class="px-6 py-4 text-gray-500">{{ vendor.phone || '-' }}</td>
                                <td class="px-6 py-4 text-gray-500">{{ vendor.email || '-' }}</td>
                                <td class="px-6 py-4 text-gray-500 max-w-xs truncate">{{ vendor.address || '-' }}</td>
                            </tr>
                            <tr v-if="vendors.length === 0">
                                <td colspan="5" class="px-6 py-8 text-center text-gray-400">No vendors registered yet.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab 4: Refund Requests -->
            <div v-if="activeTab === 'refunds'" class="space-y-6">
                <h3 class="font-bold text-gray-900 text-lg">Fee Refund Requests</h3>
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 font-semibold text-gray-600">Student</th>
                                <th class="px-6 py-3 font-semibold text-gray-600">Fee Challan</th>
                                <th class="px-6 py-3 font-semibold text-gray-600">Refund Amount</th>
                                <th class="px-6 py-3 font-semibold text-gray-600">Reason</th>
                                <th class="px-6 py-3 font-semibold text-gray-600">Status</th>
                                <th class="px-6 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="refund in refundRequests" :key="refund.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-medium text-gray-900">{{ refund.student?.user?.name }}</td>
                                <td class="px-6 py-4 text-gray-500 font-mono">{{ refund.fee_challan?.challan_number || '-' }}</td>
                                <td class="px-6 py-4 font-bold text-red-600">{{ formatCurrency(refund.amount) }}</td>
                                <td class="px-6 py-4 text-gray-700">{{ refund.reason }}</td>
                                <td class="px-6 py-4">
                                    <span :class="{
                                        'bg-amber-50 text-amber-700 border-amber-200': refund.status === 'pending',
                                        'bg-blue-50 text-blue-700 border-blue-200': refund.status === 'approved',
                                        'bg-green-50 text-green-700 border-green-200': refund.status === 'refunded',
                                        'bg-red-50 text-red-700 border-red-200': refund.status === 'rejected',
                                    }" class="px-2.5 py-1 text-xs font-semibold rounded-full border capitalize">
                                        {{ refund.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right space-x-2">
                                    <button v-if="refund.status === 'pending'" @click="updateRefundStatus(refund.id, 'approved', 'Approved by administrator')" class="px-2.5 py-1.5 bg-blue-600 text-white rounded text-xs font-semibold hover:bg-blue-700">
                                        Approve
                                    </button>
                                    <button v-if="refund.status === 'approved'" @click="updateRefundStatus(refund.id, 'refunded', 'Disbursed')" class="px-2.5 py-1.5 bg-green-600 text-white rounded text-xs font-semibold hover:bg-green-700">
                                        Mark Refunded
                                    </button>
                                    <button v-if="refund.status === 'pending'" @click="updateRefundStatus(refund.id, 'rejected', 'Rejected')" class="px-2.5 py-1.5 bg-red-600 text-white rounded text-xs font-semibold hover:bg-red-700">
                                        Reject
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="refundRequests.length === 0">
                                <td colspan="6" class="px-6 py-8 text-center text-gray-400">No refund requests logged.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Add Vendor Modal -->
        <div v-if="showVendorModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs">
            <div class="bg-white rounded-2xl border border-gray-200 max-w-md w-full p-6 shadow-xl">
                <div class="flex justify-between items-center pb-4 border-b border-gray-100 mb-4">
                    <h3 class="text-lg font-bold text-gray-900 font-Outfit">Register New Vendor</h3>
                    <button @click="showVendorModal = false" class="text-gray-400 hover:text-gray-600 text-2xl font-bold">&times;</button>
                </div>
                <form @submit.prevent="submitVendor" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Vendor/Company Name</label>
                        <input type="text" v-model="vendorForm.name" required class="w-full rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Contact Person</label>
                        <input type="text" v-model="vendorForm.contact_person" class="w-full rounded-lg border-gray-300 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Phone Number</label>
                        <input type="text" v-model="vendorForm.phone" class="w-full rounded-lg border-gray-300 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Email Address</label>
                        <input type="email" v-model="vendorForm.email" class="w-full rounded-lg border-gray-300 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Office Address</label>
                        <textarea v-model="vendorForm.address" rows="3" class="w-full rounded-lg border-gray-300 text-sm"></textarea>
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <button type="button" @click="showVendorModal = false" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">Cancel</button>
                        <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700">Save Vendor</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Add Expense Modal -->
        <div v-if="showExpenseModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs">
            <div class="bg-white rounded-2xl border border-gray-200 max-w-md w-full p-6 shadow-xl">
                <div class="flex justify-between items-center pb-4 border-b border-gray-100 mb-4">
                    <h3 class="text-lg font-bold text-gray-900 font-Outfit">Record New Expense</h3>
                    <button @click="showExpenseModal = false" class="text-gray-400 hover:text-gray-600 text-2xl font-bold">&times;</button>
                </div>
                <form @submit.prevent="submitExpense" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Category</label>
                        <select v-model="expenseForm.category" required class="w-full rounded-lg border-gray-300 text-sm">
                            <option value="Operational">Operational Expenses</option>
                            <option value="Salaries">Staff Salaries/Wages</option>
                            <option value="Utility Bills">Electricity, Gas & Water Utility Bills</option>
                            <option value="Stationery">Books & Stationery Supplies</option>
                            <option value="Maintenance">Building Repairs & Maintenance</option>
                            <option value="Events">School Events & Sports Supplies</option>
                            <option value="Other">Other Miscellaneous Expenses</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Expense Amount (PKR)</label>
                        <input type="number" v-model="expenseForm.amount" required class="w-full rounded-lg border-gray-300 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Payee Vendor</label>
                        <select v-model="expenseForm.vendor_id" class="w-full rounded-lg border-gray-300 text-sm">
                            <option value="">Direct/No Registered Vendor</option>
                            <option v-for="vendor in vendors" :key="vendor.id" :value="vendor.id">{{ vendor.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Reference Number / Receipt ID</label>
                        <input type="text" v-model="expenseForm.reference" class="w-full rounded-lg border-gray-300 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Date of Expense</label>
                        <input type="date" v-model="expenseForm.date" required class="w-full rounded-lg border-gray-300 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Expense Notes / Rationale</label>
                        <textarea v-model="expenseForm.notes" rows="3" class="w-full rounded-lg border-gray-300 text-sm"></textarea>
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <button type="button" @click="showExpenseModal = false" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">Cancel</button>
                        <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700">Record Expense</button>
                    </div>
                </form>
            </div>
        </div>
    </TenantAdminLayout>
</template>
