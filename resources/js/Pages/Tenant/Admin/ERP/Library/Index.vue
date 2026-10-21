<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import TenantAdminLayout from '@/Layouts/TenantAdminLayout.vue';

const props = defineProps({
    books: Array,
    issues: Array,
    students: Array,
});

const activeTab = ref('catalog'); // 'catalog', 'issues'
const showBookModal = ref(false);
const showIssueModal = ref(false);
const showReturnModal = ref(false);

const selectedIssue = ref(null);

const bookForm = useForm({
    title: '',
    author: '',
    isbn: '',
    category: '',
    total_copies: 1,
    price: 0,
});

const issueForm = useForm({
    book_id: '',
    student_id: '',
    issue_date: new Date().toISOString().split('T')[0],
    due_date: new Date(Date.now() + 14 * 24 * 60 * 60 * 1000).toISOString().split('T')[0], // 14 days standard duration
});

const returnForm = useForm({
    return_date: new Date().toISOString().split('T')[0],
    fine_amount: 0,
});

const submitBook = () => {
    bookForm.post(route('tenant.admin.library.books.store'), {
        onSuccess: () => {
            showBookModal.value = false;
            bookForm.reset();
        }
    });
};

const submitIssue = () => {
    issueForm.post(route('tenant.admin.library.issue.store'), {
        onSuccess: () => {
            showIssueModal.value = false;
            issueForm.reset();
        }
    });
};

const openReturnModal = (issue) => {
    selectedIssue.value = issue;
    // Calculate late fine
    const dueDate = new Date(issue.due_date);
    const today = new Date();
    if (today > dueDate) {
        const diffTime = Math.abs(today - dueDate);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        returnForm.fine_amount = diffDays * 50; // 50 PKR per late day
    } else {
        returnForm.fine_amount = 0;
    }
    showReturnModal.value = true;
};

const submitReturn = () => {
    returnForm.post(route('tenant.admin.library.issue.return', selectedIssue.value.id), {
        onSuccess: () => {
            showReturnModal.value = false;
            selectedIssue.value = null;
            returnForm.reset();
        }
    });
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-PK', { style: 'currency', currency: 'PKR', maximumFractionDigits: 0 }).format(amount);
};
</script>

<template>
    <Head title="Library Management" />

    <TenantAdminLayout>
        <template #header>
            <h2 class="text-xl font-bold text-[#202124] tracking-tight">Library Management</h2>
        </template>

        <div class="space-y-6">
            <!-- Tabs -->
            <div class="flex border-b border-gray-200">
                <button @click="activeTab = 'catalog'" :class="activeTab === 'catalog' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="py-4 px-6 border-b-2 font-medium text-sm transition-all focus:outline-none">
                    Book Catalog
                </button>
                <button @click="activeTab = 'issues'" :class="activeTab === 'issues' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="py-4 px-6 border-b-2 font-medium text-sm transition-all focus:outline-none">
                    Issued Volumes
                </button>
            </div>

            <!-- Tab 1: Book Catalog -->
            <div v-if="activeTab === 'catalog'" class="space-y-6">
                <div class="flex justify-between items-center">
                    <h3 class="font-bold text-gray-900 text-lg">School Books Inventory</h3>
                    <button @click="showBookModal = true" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold shadow-xs">
                        + Add New Book
                    </button>
                </div>

                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 font-semibold text-gray-600">ISBN</th>
                                <th class="px-6 py-3 font-semibold text-gray-600">Book Title</th>
                                <th class="px-6 py-3 font-semibold text-gray-600">Author</th>
                                <th class="px-6 py-3 font-semibold text-gray-600">Category</th>
                                <th class="px-6 py-3 font-semibold text-gray-600">Price</th>
                                <th class="px-6 py-3 font-semibold text-gray-600 text-center">Available Copies</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="book in books" :key="book.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-xs font-mono text-gray-500">{{ book.isbn || '-' }}</td>
                                <td class="px-6 py-4 font-semibold text-gray-900">{{ book.title }}</td>
                                <td class="px-6 py-4 text-gray-700">{{ book.author || '-' }}</td>
                                <td class="px-6 py-4 text-gray-500">{{ book.category || '-' }}</td>
                                <td class="px-6 py-4 text-gray-600 font-semibold">{{ formatCurrency(book.price) }}</td>
                                <td class="px-6 py-4 text-center font-bold text-indigo-600">
                                    {{ book.available_copies }} / {{ book.total_copies }}
                                </td>
                            </tr>
                            <tr v-if="books.length === 0">
                                <td colspan="6" class="px-6 py-8 text-center text-gray-400">No books found in catalogue.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab 2: Issued Volumes -->
            <div v-if="activeTab === 'issues'" class="space-y-6">
                <div class="flex justify-between items-center">
                    <h3 class="font-bold text-gray-900 text-lg">Active Book Checkouts</h3>
                    <button @click="showIssueModal = true" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold shadow-xs">
                        + Issue Book Volume
                    </button>
                </div>

                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 font-semibold text-gray-600">Issued Book</th>
                                <th class="px-6 py-3 font-semibold text-gray-600">Student Borrower</th>
                                <th class="px-6 py-3 font-semibold text-gray-600">Issue Date</th>
                                <th class="px-6 py-3 font-semibold text-gray-600">Due Date</th>
                                <th class="px-6 py-3 font-semibold text-gray-600">Returned Date</th>
                                <th class="px-6 py-3 font-semibold text-gray-600">Fine Paid</th>
                                <th class="px-6 py-3 font-semibold text-gray-600">Status</th>
                                <th class="px-6 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="issue in issues" :key="issue.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-semibold text-gray-900">{{ issue.book?.title }}</td>
                                <td class="px-6 py-4 text-gray-700">{{ issue.student?.user?.name }}</td>
                                <td class="px-6 py-4 text-gray-500">{{ new Date(issue.issue_date).toLocaleDateString() }}</td>
                                <td class="px-6 py-4 text-gray-500">{{ new Date(issue.due_date).toLocaleDateString() }}</td>
                                <td class="px-6 py-4 text-gray-500">
                                    {{ issue.return_date ? new Date(issue.return_date).toLocaleDateString() : '-' }}
                                </td>
                                <td class="px-6 py-4 font-semibold text-red-500">
                                    {{ issue.fine_amount > 0 ? formatCurrency(issue.fine_amount) : '-' }}
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="{
                                        'bg-amber-50 text-amber-700 border-amber-200': issue.status === 'issued',
                                        'bg-green-50 text-green-700 border-green-200': issue.status === 'returned',
                                        'bg-red-50 text-red-700 border-red-200': issue.status === 'overdue',
                                    }" class="px-2.5 py-1 text-xs font-semibold rounded-full border capitalize">
                                        {{ issue.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button v-if="issue.status === 'issued'" @click="openReturnModal(issue)" class="px-2.5 py-1.5 bg-emerald-600 text-white rounded text-xs font-semibold hover:bg-emerald-700">
                                        Return Book
                                    </button>
                                    <span v-else class="text-xs text-gray-400">Returned</span>
                                </td>
                            </tr>
                            <tr v-if="issues.length === 0">
                                <td colspan="8" class="px-6 py-8 text-center text-gray-400">No book issue transactions logged.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Add Book Modal -->
        <div v-if="showBookModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs">
            <div class="bg-white rounded-2xl border border-gray-200 max-w-md w-full p-6 shadow-xl">
                <div class="flex justify-between items-center pb-4 border-b border-gray-100 mb-4">
                    <h3 class="text-lg font-bold text-gray-900">Add Book to Library Catalogue</h3>
                    <button @click="showBookModal = false" class="text-gray-400 hover:text-gray-600 text-2xl font-bold">&times;</button>
                </div>
                <form @submit.prevent="submitBook" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Book Title</label>
                        <input type="text" v-model="bookForm.title" required class="w-full rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Author Name</label>
                        <input type="text" v-model="bookForm.author" class="w-full rounded-lg border-gray-300 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">ISBN / Barcode</label>
                        <input type="text" v-model="bookForm.isbn" class="w-full rounded-lg border-gray-300 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Category / Genre</label>
                        <input type="text" v-model="bookForm.category" class="w-full rounded-lg border-gray-300 text-sm" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Total Copies</label>
                            <input type="number" v-model="bookForm.total_copies" required class="w-full rounded-lg border-gray-300 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Price / Value</label>
                            <input type="number" v-model="bookForm.price" required class="w-full rounded-lg border-gray-300 text-sm" />
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <button type="button" @click="showBookModal = false" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">Cancel</button>
                        <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700">Save Book</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Issue Book Modal -->
        <div v-if="showIssueModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs">
            <div class="bg-white rounded-2xl border border-gray-200 max-w-md w-full p-6 shadow-xl">
                <div class="flex justify-between items-center pb-4 border-b border-gray-100 mb-4">
                    <h3 class="text-lg font-bold text-gray-900">Issue Book Volume</h3>
                    <button @click="showIssueModal = false" class="text-gray-400 hover:text-gray-600 text-2xl font-bold">&times;</button>
                </div>
                <form @submit.prevent="submitIssue" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Select Book</label>
                        <select v-model="issueForm.book_id" required class="w-full rounded-lg border-gray-300 text-sm">
                            <option value="">Choose book...</option>
                            <option v-for="book in books" :key="book.id" :value="book.id" :disabled="book.available_copies <= 0">
                                {{ book.title }} ({{ book.available_copies }} left)
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Select Student Borrower</label>
                        <select v-model="issueForm.student_id" required class="w-full rounded-lg border-gray-300 text-sm">
                            <option value="">Choose student...</option>
                            <option v-for="student in students" :key="student.id" :value="student.id">
                                {{ student.user?.name }} (Roll # {{ student.roll_number }})
                            </option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Issue Date</label>
                            <input type="date" v-model="issueForm.issue_date" required class="w-full rounded-lg border-gray-300 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Due Date</label>
                            <input type="date" v-model="issueForm.due_date" required class="w-full rounded-lg border-gray-300 text-sm" />
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <button type="button" @click="showIssueModal = false" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">Cancel</button>
                        <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700">Issue Book</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Return Book Modal -->
        <div v-if="showReturnModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs">
            <div class="bg-white rounded-2xl border border-gray-200 max-w-md w-full p-6 shadow-xl">
                <div class="flex justify-between items-center pb-4 border-b border-gray-100 mb-4">
                    <h3 class="text-lg font-bold text-gray-900">Return Book Volume</h3>
                    <button @click="showReturnModal = false; selectedIssue = null" class="text-gray-400 hover:text-gray-600 text-2xl font-bold">&times;</button>
                </div>
                <form @submit.prevent="submitReturn" class="space-y-4">
                    <p class="text-sm text-gray-500">Confirming return of <strong>{{ selectedIssue?.book?.title }}</strong> by student <strong>{{ selectedIssue?.student?.user?.name }}</strong>.</p>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Return Date</label>
                        <input type="date" v-model="returnForm.return_date" required class="w-full rounded-lg border-gray-300 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Late Return Fine (PKR)</label>
                        <input type="number" v-model="returnForm.fine_amount" class="w-full rounded-lg border-gray-300 text-sm" />
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <button type="button" @click="showReturnModal = false; selectedIssue = null" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">Cancel</button>
                        <button type="submit" class="px-6 py-2 bg-emerald-600 text-white rounded-lg text-sm font-semibold hover:bg-emerald-700">Confirm Return</button>
                    </div>
                </form>
            </div>
        </div>
    </TenantAdminLayout>
</template>
