<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import TenantAdminLayout from '@/Layouts/TenantAdminLayout.vue';

const props = defineProps({
    hostels: Array,
    rooms: Array,
    allocations: Array,
    students: Array,
});

const activeTab = ref('hostels'); // 'hostels', 'rooms', 'allocations'
const showHostelModal = ref(false);
const showRoomModal = ref(false);
const showAllocationModal = ref(false);
const showVacateModal = ref(false);

const selectedAllocation = ref(null);

const hostelForm = useForm({
    name: '',
    type: 'boys',
    address: '',
    warden_name: '',
    warden_phone: '',
});

const roomForm = useForm({
    hostel_id: '',
    room_number: '',
    capacity: 4,
    rent_amount: 0,
});

const allocationForm = useForm({
    student_id: '',
    room_id: '',
    allocation_date: new Date().toISOString().split('T')[0],
});

const vacateForm = useForm({
    vacate_date: new Date().toISOString().split('T')[0],
});

const submitHostel = () => {
    hostelForm.post(route('tenant.admin.hostels.store'), {
        onSuccess: () => {
            showHostelModal.value = false;
            hostelForm.reset();
        }
    });
};

const submitRoom = () => {
    roomForm.post(route('tenant.admin.hostels.rooms.store'), {
        onSuccess: () => {
            showRoomModal.value = false;
            roomForm.reset();
        }
    });
};

const submitAllocation = () => {
    allocationForm.post(route('tenant.admin.hostels.allocate'), {
        onSuccess: () => {
            showAllocationModal.value = false;
            allocationForm.reset();
        }
    });
};

const openVacateModal = (alloc) => {
    selectedAllocation.value = alloc;
    showVacateModal.value = true;
};

const submitVacate = () => {
    vacateForm.post(route('tenant.admin.hostels.vacate', selectedAllocation.value.id), {
        onSuccess: () => {
            showVacateModal.value = false;
            selectedAllocation.value = null;
            vacateForm.reset();
        }
    });
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-PK', { style: 'currency', currency: 'PKR', maximumFractionDigits: 0 }).format(amount);
};
</script>

<template>
    <Head title="Hostel Management" />

    <TenantAdminLayout>
        <template #header>
            <h2 class="text-xl font-bold text-[#202124] tracking-tight">Hostel Management</h2>
        </template>

        <div class="space-y-6">
            <!-- Tabs -->
            <div class="flex border-b border-gray-200">
                <button @click="activeTab = 'hostels'" :class="activeTab === 'hostels' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="py-4 px-6 border-b-2 font-medium text-sm transition-all focus:outline-none">
                    Hostel Blocks
                </button>
                <button @click="activeTab = 'rooms'" :class="activeTab === 'rooms' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="py-4 px-6 border-b-2 font-medium text-sm transition-all focus:outline-none">
                    Rooms Registry
                </button>
                <button @click="activeTab = 'allocations'" :class="activeTab === 'allocations' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="py-4 px-6 border-b-2 font-medium text-sm transition-all focus:outline-none">
                    Room Allocations
                </button>
            </div>

            <!-- Tab 1: Hostel Blocks -->
            <div v-if="activeTab === 'hostels'" class="space-y-6">
                <div class="flex justify-between items-center">
                    <h3 class="font-bold text-gray-900 text-lg">Boarding Facilities</h3>
                    <button @click="showHostelModal = true" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold shadow-xs">
                        + Add Hostel Block
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div v-for="hostel in hostels" :key="hostel.id" class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm space-y-4">
                        <div class="flex justify-between items-start">
                            <div>
                                <h4 class="text-base font-bold text-gray-900">{{ hostel.name }}</h4>
                                <p class="text-xs text-gray-500 mt-1 capitalize">Type: {{ hostel.type }} hostel</p>
                            </div>
                            <span class="px-2.5 py-1 bg-indigo-50 border border-indigo-200 text-indigo-700 font-bold text-xs rounded-full">
                                {{ hostel.rooms?.length || 0 }} Rooms
                            </span>
                        </div>

                        <div class="text-xs text-gray-600 space-y-1 pt-2 border-t border-gray-100">
                            <p>Warden: <strong class="text-gray-800">{{ hostel.warden_name || 'N/A' }}</strong></p>
                            <p>Warden Phone: <strong class="text-gray-800">{{ hostel.warden_phone || 'N/A' }}</strong></p>
                            <p>Address: <span class="text-gray-500">{{ hostel.address || '-' }}</span></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 2: Rooms Registry -->
            <div v-if="activeTab === 'rooms'" class="space-y-6">
                <div class="flex justify-between items-center">
                    <h3 class="font-bold text-gray-900 text-lg">Hostel Rooms Catalogue</h3>
                    <button @click="showRoomModal = true" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold shadow-xs">
                        + Add Room
                    </button>
                </div>

                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 font-semibold text-gray-600">Hostel Block</th>
                                <th class="px-6 py-3 font-semibold text-gray-600">Room Number</th>
                                <th class="px-6 py-3 font-semibold text-gray-600">Bed Capacity</th>
                                <th class="px-6 py-3 font-semibold text-gray-600">Monthly Rent</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="room in rooms" :key="room.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-semibold text-gray-900">{{ room.hostel?.name }}</td>
                                <td class="px-6 py-4 text-gray-700 font-mono">{{ room.room_number }}</td>
                                <td class="px-6 py-4 text-gray-700">{{ room.capacity }} beds</td>
                                <td class="px-6 py-4 text-indigo-600 font-bold">{{ formatCurrency(room.rent_amount) }}</td>
                            </tr>
                            <tr v-if="rooms.length === 0">
                                <td colspan="4" class="px-6 py-8 text-center text-gray-400">No rooms configured.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab 3: Room Allocations -->
            <div v-if="activeTab === 'allocations'" class="space-y-6">
                <div class="flex justify-between items-center">
                    <h3 class="font-bold text-gray-900 text-lg">Student Occupancy Allocations</h3>
                    <button @click="showAllocationModal = true" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold shadow-xs">
                        + Allocate Room
                    </button>
                </div>

                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 font-semibold text-gray-600">Student Boarder</th>
                                <th class="px-6 py-3 font-semibold text-gray-600">Hostel Block</th>
                                <th class="px-6 py-3 font-semibold text-gray-600">Room No</th>
                                <th class="px-6 py-3 font-semibold text-gray-600">Allocated Date</th>
                                <th class="px-6 py-3 font-semibold text-gray-600">Vacated Date</th>
                                <th class="px-6 py-3 font-semibold text-gray-600">Status</th>
                                <th class="px-6 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="alloc in allocations" :key="alloc.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-semibold text-gray-900">{{ alloc.student?.user?.name }}</td>
                                <td class="px-6 py-4 text-gray-700">{{ alloc.room?.hostel?.name }}</td>
                                <td class="px-6 py-4 text-gray-500 font-mono">{{ alloc.room?.room_number }}</td>
                                <td class="px-6 py-4 text-gray-500">{{ new Date(alloc.allocation_date).toLocaleDateString() }}</td>
                                <td class="px-6 py-4 text-gray-500">
                                    {{ alloc.vacate_date ? new Date(alloc.vacate_date).toLocaleDateString() : '-' }}
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="alloc.status === 'active' ? 'bg-green-50 text-green-700 border-green-200' : 'bg-gray-100 text-gray-700 border-gray-300'" class="px-2.5 py-1 text-xs font-semibold rounded-full border capitalize">
                                        {{ alloc.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button v-if="alloc.status === 'active'" @click="openVacateModal(alloc)" class="px-2.5 py-1.5 bg-red-600 text-white rounded text-xs font-semibold hover:bg-red-700">
                                        Vacate Room
                                    </button>
                                    <span v-else class="text-xs text-gray-400">Vacated</span>
                                </td>
                            </tr>
                            <tr v-if="allocations.length === 0">
                                <td colspan="7" class="px-6 py-8 text-center text-gray-400">No room allocations recorded.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Add Hostel Modal -->
        <div v-if="showHostelModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs">
            <div class="bg-white rounded-2xl border border-gray-200 max-w-md w-full p-6 shadow-xl">
                <div class="flex justify-between items-center pb-4 border-b border-gray-100 mb-4">
                    <h3 class="text-lg font-bold text-gray-900 font-Outfit">Add Hostel Block</h3>
                    <button @click="showHostelModal = false" class="text-gray-400 hover:text-gray-600 text-2xl font-bold">&times;</button>
                </div>
                <form @submit.prevent="submitHostel" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Hostel Block Name</label>
                        <input type="text" v-model="hostelForm.name" placeholder="e.g. Iqbal Hall" required class="w-full rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Hostel Type</label>
                        <select v-model="hostelForm.type" class="w-full rounded-lg border-gray-300 text-sm">
                            <option value="boys">Boys Hostel</option>
                            <option value="girls">Girls Hostel</option>
                            <option value="co-ed">Co-Ed Hostel</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Warden Name</label>
                        <input type="text" v-model="hostelForm.warden_name" class="w-full rounded-lg border-gray-300 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Warden Contact Phone</label>
                        <input type="text" v-model="hostelForm.warden_phone" class="w-full rounded-lg border-gray-300 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Address / Location</label>
                        <textarea v-model="hostelForm.address" rows="3" class="w-full rounded-lg border-gray-300 text-sm"></textarea>
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <button type="button" @click="showHostelModal = false" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">Cancel</button>
                        <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700">Save Hostel</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Add Room Modal -->
        <div v-if="showRoomModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs">
            <div class="bg-white rounded-2xl border border-gray-200 max-w-md w-full p-6 shadow-xl">
                <div class="flex justify-between items-center pb-4 border-b border-gray-100 mb-4">
                    <h3 class="text-lg font-bold text-gray-900 font-Outfit">Add Hostel Room</h3>
                    <button @click="showRoomModal = false" class="text-gray-400 hover:text-gray-600 text-2xl font-bold">&times;</button>
                </div>
                <form @submit.prevent="submitRoom" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Select Hostel Block</label>
                        <select v-model="roomForm.hostel_id" required class="w-full rounded-lg border-gray-300 text-sm">
                            <option value="">Choose block...</option>
                            <option v-for="hostel in hostels" :key="hostel.id" :value="hostel.id">{{ hostel.name }} ({{ hostel.type }})</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Room Number / ID</label>
                        <input type="text" v-model="roomForm.room_number" placeholder="e.g. Room 104" required class="w-full rounded-lg border-gray-300 text-sm" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Bed Capacity</label>
                            <input type="number" v-model="roomForm.capacity" required class="w-full rounded-lg border-gray-300 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Monthly Rent Fee (PKR)</label>
                            <input type="number" v-model="roomForm.rent_amount" required class="w-full rounded-lg border-gray-300 text-sm" />
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <button type="button" @click="showRoomModal = false" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">Cancel</button>
                        <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700">Save Room</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Allocate Room Modal -->
        <div v-if="showAllocationModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs">
            <div class="bg-white rounded-2xl border border-gray-200 max-w-md w-full p-6 shadow-xl">
                <div class="flex justify-between items-center pb-4 border-b border-gray-100 mb-4">
                    <h3 class="text-lg font-bold text-gray-900">Allocate Boarder Room</h3>
                    <button @click="showAllocationModal = false" class="text-gray-400 hover:text-gray-600 text-2xl font-bold">&times;</button>
                </div>
                <form @submit.prevent="submitAllocation" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Select Student Boarder</label>
                        <select v-model="allocationForm.student_id" required class="w-full rounded-lg border-gray-300 text-sm">
                            <option value="">Choose student...</option>
                            <option v-for="student in students" :key="student.id" :value="student.id">
                                {{ student.user?.name }} (Roll # {{ student.roll_number }})
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Select Hostel & Room</label>
                        <select v-model="allocationForm.room_id" required class="w-full rounded-lg border-gray-300 text-sm">
                            <option value="">Choose room...</option>
                            <option v-for="room in rooms" :key="room.id" :value="room.id">
                                {{ room.hostel?.name }} - Room {{ room.room_number }} (PKR {{ room.rent_amount }}/mo)
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Check-in Date</label>
                        <input type="date" v-model="allocationForm.allocation_date" required class="w-full rounded-lg border-gray-300 text-sm" />
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <button type="button" @click="showAllocationModal = false" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">Cancel</button>
                        <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700">Allocate Room</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Vacate Room Modal -->
        <div v-if="showVacateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs">
            <div class="bg-white rounded-2xl border border-gray-200 max-w-md w-full p-6 shadow-xl">
                <div class="flex justify-between items-center pb-4 border-b border-gray-100 mb-4">
                    <h3 class="text-lg font-bold text-gray-900">Vacate Hostel Room</h3>
                    <button @click="showVacateModal = false; selectedAllocation = null" class="text-gray-400 hover:text-gray-600 text-2xl font-bold">&times;</button>
                </div>
                <form @submit.prevent="submitVacate" class="space-y-4">
                    <p class="text-sm text-gray-500">Confirming checkout for student <strong>{{ selectedAllocation?.student?.user?.name }}</strong> from room <strong>{{ selectedAllocation?.room?.room_number }}</strong>.</p>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Vacate Date</label>
                        <input type="date" v-model="vacateForm.vacate_date" required class="w-full rounded-lg border-gray-300 text-sm" />
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <button type="button" @click="showVacateModal = false; selectedAllocation = null" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">Cancel</button>
                        <button type="submit" class="px-6 py-2 bg-red-600 text-white rounded-lg text-sm font-semibold hover:bg-red-700">Confirm Checkout</button>
                    </div>
                </form>
            </div>
        </div>
    </TenantAdminLayout>
</template>
