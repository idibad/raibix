<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import TenantAdminLayout from '@/Layouts/TenantAdminLayout.vue';

const props = defineProps({
    categories: Array,
    items: Array,
    issues: Array,
    staff: Array,
});

const activeTab = ref('items'); // 'items', 'categories', 'issues'
const showCategoryModal = ref(false);
const showItemModal = ref(false);
const showIssueModal = ref(false);

const categoryForm = useForm({
    name: '',
    description: '',
});

const itemForm = useForm({
    item_name: '',
    category_id: '',
    quantity: 1,
    room_location: '',
    condition: 'good',
    unit_cost: 0,
});

const issueForm = useForm({
    item_id: '',
    user_id: '',
    quantity: 1,
    issue_date: new Date().toISOString().split('T')[0],
});

const submitCategory = () => {
    categoryForm.post(route('tenant.admin.inventory.categories.store'), {
        onSuccess: () => {
            showCategoryModal.value = false;
            categoryForm.reset();
        }
    });
};

const submitItem = () => {
    itemForm.post(route('tenant.admin.inventory.items.store'), {
        onSuccess: () => {
            showItemModal.value = false;
            itemForm.reset();
        }
    });
};

const submitIssue = () => {
    issueForm.post(route('tenant.admin.inventory.issue.store'), {
        onSuccess: () => {
            showIssueModal.value = false;
            issueForm.reset();
        }
    });
};

const updateIssueStatus = (id, status) => {
    useForm({
        status: status,
        return_date: new Date().toISOString().split('T')[0],
    }).post(route('tenant.admin.inventory.issues.status', id));
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-PK', { style: 'currency', currency: 'PKR', maximumFractionDigits: 0 }).format(amount);
};
</script>

<template>
    <Head title="Inventory & Asset Management" />

    <TenantAdminLayout>
        <template #header>
            <h2 class="text-xl font-bold text-[#202124] tracking-tight">Inventory & Assets</h2>
        </template>

        <div class="space-y-6">
            <!-- Tabs -->
            <div class="flex border-b border-gray-200">
                <button @click="activeTab = 'items'" :class="activeTab === 'items' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="py-4 px-6 border-b-2 font-medium text-sm transition-all focus:outline-none">
                    Asset Catalogue
                </button>
                <button @click="activeTab = 'categories'" :class="activeTab === 'categories' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="py-4 px-6 border-b-2 font-medium text-sm transition-all focus:outline-none">
                    Inventory Categories
                </button>
                <button @click="activeTab = 'issues'" :class="activeTab === 'issues' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="py-4 px-6 border-b-2 font-medium text-sm transition-all focus:outline-none">
                    Issued Assets
                </button>
            </div>

            <!-- Tab 1: Asset Catalogue -->
            <div v-if="activeTab === 'items'" class="space-y-6">
                <div class="flex justify-between items-center">
                    <h3 class="font-bold text-gray-900 text-lg">School Assets Registry</h3>
                    <button @click="showItemModal = true" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold shadow-xs">
                        + Add Asset Item
                    </button>
                </div>

                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 font-semibold text-gray-600">Item Name</th>
                                <th class="px-6 py-3 font-semibold text-gray-600">Category</th>
                                <th class="px-6 py-3 font-semibold text-gray-600">Stock Qty</th>
                                <th class="px-6 py-3 font-semibold text-gray-600">Location</th>
                                <th class="px-6 py-3 font-semibold text-gray-600">Condition</th>
                                <th class="px-6 py-3 font-semibold text-gray-600">Unit Cost</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="item in items" :key="item.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-semibold text-gray-900">{{ item.item_name }}</td>
                                <td class="px-6 py-4 text-gray-500">{{ item.category }}</td>
                                <td class="px-6 py-4 text-gray-700 font-bold">{{ item.quantity }} pcs</td>
                                <td class="px-6 py-4 text-gray-500">{{ item.room_location || '-' }}</td>
                                <td class="px-6 py-4">
                                    <span :class="{
                                        'bg-green-50 text-green-700 border-green-200': item.condition === 'good',
                                        'bg-amber-50 text-amber-700 border-amber-200': item.condition === 'maintenance_required',
                                        'bg-red-50 text-red-700 border-red-200': item.condition === 'broken',
                                    }" class="px-2.5 py-1 text-xs font-semibold rounded-full border capitalize">
                                        {{ item.condition.replace('_', ' ') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-gray-900 font-semibold">{{ formatCurrency(item.unit_cost) }}</td>
                            </tr>
                            <tr v-if="items.length === 0">
                                <td colspan="6" class="px-6 py-8 text-center text-gray-400">No assets recorded.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab 2: Inventory Categories -->
            <div v-if="activeTab === 'categories'" class="space-y-6">
                <div class="flex justify-between items-center">
                    <h3 class="font-bold text-gray-900 text-lg">Inventory Classifications</h3>
                    <button @click="showCategoryModal = true" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold shadow-xs">
                        + Add Category
                    </button>
                </div>

                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 font-semibold text-gray-600">Category Name</th>
                                <th class="px-6 py-3 font-semibold text-gray-600">Description</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="cat in categories" :key="cat.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-semibold text-gray-900">{{ cat.name }}</td>
                                <td class="px-6 py-4 text-gray-500">{{ cat.description || '-' }}</td>
                            </tr>
                            <tr v-if="categories.length === 0">
                                <td colspan="2" class="px-6 py-8 text-center text-gray-400">No categories created yet.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab 3: Issued Assets -->
            <div v-if="activeTab === 'issues'" class="space-y-6">
                <div class="flex justify-between items-center">
                    <h3 class="font-bold text-gray-900 text-lg">Staff Asset Issuances</h3>
                    <button @click="showIssueModal = true" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold shadow-xs">
                        + Issue Asset Item
                    </button>
                </div>

                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 font-semibold text-gray-600">Asset Item</th>
                                <th class="px-6 py-3 font-semibold text-gray-600">Issued To</th>
                                <th class="px-6 py-3 font-semibold text-gray-600">Quantity</th>
                                <th class="px-6 py-3 font-semibold text-gray-600">Issue Date</th>
                                <th class="px-6 py-3 font-semibold text-gray-600">Return Date</th>
                                <th class="px-6 py-3 font-semibold text-gray-600">Status</th>
                                <th class="px-6 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="issue in issues" :key="issue.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-semibold text-gray-900">{{ issue.item?.item_name }}</td>
                                <td class="px-6 py-4 text-gray-700">{{ issue.user?.name }} ({{ issue.user?.user_type }})</td>
                                <td class="px-6 py-4 text-gray-900 font-semibold">{{ issue.quantity }} pcs</td>
                                <td class="px-6 py-4 text-gray-500">{{ new Date(issue.issue_date).toLocaleDateString() }}</td>
                                <td class="px-6 py-4 text-gray-500">
                                    {{ issue.return_date ? new Date(issue.return_date).toLocaleDateString() : '-' }}
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="{
                                        'bg-blue-50 text-blue-700 border-blue-200': issue.status === 'issued',
                                        'bg-green-50 text-green-700 border-green-200': issue.status === 'returned',
                                        'bg-red-50 text-red-700 border-red-200': issue.status === 'lost',
                                    }" class="px-2.5 py-1 text-xs font-semibold rounded-full border capitalize">
                                        {{ issue.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right space-x-2">
                                    <button v-if="issue.status === 'issued'" @click="updateIssueStatus(issue.id, 'returned')" class="px-2.5 py-1.5 bg-emerald-600 text-white rounded text-xs font-semibold hover:bg-emerald-700">
                                        Return
                                    </button>
                                    <button v-if="issue.status === 'issued'" @click="updateIssueStatus(issue.id, 'lost')" class="px-2.5 py-1.5 bg-red-600 text-white rounded text-xs font-semibold hover:bg-red-700">
                                        Mark Lost
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="issues.length === 0">
                                <td colspan="7" class="px-6 py-8 text-center text-gray-400">No asset issue records found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Add Category Modal -->
        <div v-if="showCategoryModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs">
            <div class="bg-white rounded-2xl border border-gray-200 max-w-md w-full p-6 shadow-xl">
                <div class="flex justify-between items-center pb-4 border-b border-gray-100 mb-4">
                    <h3 class="text-lg font-bold text-gray-900">Add Inventory Category</h3>
                    <button @click="showCategoryModal = false" class="text-gray-400 hover:text-gray-600 text-2xl font-bold">&times;</button>
                </div>
                <form @submit.prevent="submitCategory" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Category Name</label>
                        <input type="text" v-model="categoryForm.name" placeholder="Category Name" required class="w-full rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Description</label>
                        <textarea v-model="categoryForm.description" rows="3" class="w-full rounded-lg border-gray-300 text-sm"></textarea>
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <button type="button" @click="showCategoryModal = false" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">Cancel</button>
                        <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700">Save Category</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Add Item Modal -->
        <div v-if="showItemModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs">
            <div class="bg-white rounded-2xl border border-gray-200 max-w-md w-full p-6 shadow-xl">
                <div class="flex justify-between items-center pb-4 border-b border-gray-100 mb-4">
                    <h3 class="text-lg font-bold text-gray-900">Add Asset Item</h3>
                    <button @click="showItemModal = false" class="text-gray-400 hover:text-gray-600 text-2xl font-bold">&times;</button>
                </div>
                <form @submit.prevent="submitItem" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Item Name</label>
                        <input type="text" v-model="itemForm.item_name" placeholder="Item Name" required class="w-full rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Category</label>
                        <select v-model="itemForm.category_id" required class="w-full rounded-lg border-gray-300 text-sm">
                            <option value="">Choose category...</option>
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Quantity</label>
                            <input type="number" v-model="itemForm.quantity" required class="w-full rounded-lg border-gray-300 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Unit Cost (PKR)</label>
                            <input type="number" v-model="itemForm.unit_cost" required class="w-full rounded-lg border-gray-300 text-sm" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Condition Status</label>
                        <select v-model="itemForm.condition" class="w-full rounded-lg border-gray-300 text-sm">
                            <option value="good">Good / Functional</option>
                            <option value="maintenance_required">Requires Maintenance</option>
                            <option value="broken">Broken / Out of Service</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Room Location</label>
                        <input type="text" v-model="itemForm.room_location" placeholder="Room / Location" class="w-full rounded-lg border-gray-300 text-sm" />
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <button type="button" @click="showItemModal = false" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">Cancel</button>
                        <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700">Save Asset</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Issue Asset Modal -->
        <div v-if="showIssueModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs">
            <div class="bg-white rounded-2xl border border-gray-200 max-w-md w-full p-6 shadow-xl">
                <div class="flex justify-between items-center pb-4 border-b border-gray-100 mb-4">
                    <h3 class="text-lg font-bold text-gray-900 font-Outfit">Issue Asset Item</h3>
                    <button @click="showIssueModal = false" class="text-gray-400 hover:text-gray-600 text-2xl font-bold">&times;</button>
                </div>
                <form @submit.prevent="submitIssue" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Select Asset Item</label>
                        <select v-model="issueForm.item_id" required class="w-full rounded-lg border-gray-300 text-sm">
                            <option value="">Choose item...</option>
                            <option v-for="item in items" :key="item.id" :value="item.id" :disabled="item.quantity <= 0">
                                {{ item.item_name }} ({{ item.quantity }} available)
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Issue to Employee</label>
                        <select v-model="issueForm.user_id" required class="w-full rounded-lg border-gray-300 text-sm">
                            <option value="">Choose staff...</option>
                            <option v-for="member in staff" :key="member.id" :value="member.id">
                                {{ member.name }} ({{ member.user_type }})
                            </option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Quantity</label>
                            <input type="number" v-model="issueForm.quantity" required class="w-full rounded-lg border-gray-300 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Issue Date</label>
                            <input type="date" v-model="issueForm.issue_date" required class="w-full rounded-lg border-gray-300 text-sm" />
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <button type="button" @click="showIssueModal = false" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">Cancel</button>
                        <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700">Issue Asset</button>
                    </div>
                </form>
            </div>
        </div>
    </TenantAdminLayout>
</template>
