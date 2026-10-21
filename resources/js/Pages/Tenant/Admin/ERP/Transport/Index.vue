<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import TenantAdminLayout from '@/Layouts/TenantAdminLayout.vue';

const props = defineProps({
    vehicles: Array,
    routes: Array,
    allocations: Array,
    students: Array,
    staff: Array,
});

const activeTab = ref('routes'); // 'routes', 'vehicles', 'allocations'
const showVehicleModal = ref(false);
const showRouteModal = ref(false);
const showStopModal = ref(false);
const showAllocationModal = ref(false);

const selectedRouteId = ref(null);

const vehicleForm = useForm({
    vehicle_number: '',
    model_name: '',
    capacity: 30,
    driver_name: '',
    driver_phone: '',
});

const routeForm = useForm({
    route_name: '',
    vehicle_id: '',
    pickup_time: '07:30',
    drop_time: '14:00',
    monthly_fee: 0,
});

const stopForm = useForm({
    route_id: '',
    stop_name: '',
    pickup_time: '07:00',
});

const allocationForm = useForm({
    user_id: '',
    user_type: 'student',
    route_id: '',
    vehicle_id: '',
    pickup_point: '',
});

const submitVehicle = () => {
    vehicleForm.post(route('tenant.admin.transport.vehicles.store'), {
        onSuccess: () => {
            showVehicleModal.value = false;
            vehicleForm.reset();
        }
    });
};

const submitRoute = () => {
    routeForm.post(route('tenant.admin.transport.routes.store'), {
        onSuccess: () => {
            showRouteModal.value = false;
            routeForm.reset();
        }
    });
};

const openStopModal = (routeId) => {
    selectedRouteId.value = routeId;
    stopForm.route_id = routeId;
    showStopModal.value = true;
};

const submitStop = () => {
    stopForm.post(route('tenant.admin.transport.stops.store'), {
        onSuccess: () => {
            showStopModal.value = false;
            selectedRouteId.value = null;
            stopForm.reset();
        }
    });
};

const submitAllocation = () => {
    allocationForm.post(route('tenant.admin.transport.allocate'), {
        onSuccess: () => {
            showAllocationModal.value = false;
            allocationForm.reset();
        }
    });
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-PK', { style: 'currency', currency: 'PKR', maximumFractionDigits: 0 }).format(amount);
};
</script>

<template>
    <Head title="Transport Management" />

    <TenantAdminLayout>
        <template #header>
            <h2 class="text-xl font-bold text-[#202124] tracking-tight">Transport Management</h2>
        </template>

        <div class="space-y-6">
            <!-- Tabs -->
            <div class="flex border-b border-gray-200">
                <button @click="activeTab = 'routes'" :class="activeTab === 'routes' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="py-4 px-6 border-b-2 font-medium text-sm transition-all focus:outline-none">
                    Routes & Stops
                </button>
                <button @click="activeTab = 'vehicles'" :class="activeTab === 'vehicles' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="py-4 px-6 border-b-2 font-medium text-sm transition-all focus:outline-none">
                    Vehicle Fleet
                </button>
                <button @click="activeTab = 'allocations'" :class="activeTab === 'allocations' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="py-4 px-6 border-b-2 font-medium text-sm transition-all focus:outline-none">
                    Transport Allocations
                </button>
            </div>

            <!-- Tab 1: Routes & Stops -->
            <div v-if="activeTab === 'routes'" class="space-y-6">
                <div class="flex justify-between items-center">
                    <h3 class="font-bold text-gray-900 text-lg">Bus Routes</h3>
                    <button @click="showRouteModal = true" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold shadow-xs">
                        + Add Route
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div v-for="routeItem in routes" :key="routeItem.id" class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm space-y-4">
                        <div class="flex justify-between items-start">
                            <div>
                                <h4 class="text-base font-bold text-gray-900">{{ routeItem.route_name }}</h4>
                                <p class="text-xs text-gray-500 mt-1">Bus Number: {{ routeItem.vehicle?.vehicle_number || 'Unassigned' }}</p>
                            </div>
                            <span class="px-3 py-1 bg-indigo-50 border border-indigo-200 text-indigo-700 font-bold text-xs rounded-full">
                                {{ formatCurrency(routeItem.monthly_fee) }} / mo
                            </span>
                        </div>

                        <div class="flex text-xs text-gray-500 gap-4">
                            <span>Pickup: <strong class="text-gray-700">{{ routeItem.pickup_time || '-' }}</strong></span>
                            <span>Dropoff: <strong class="text-gray-700">{{ routeItem.drop_time || '-' }}</strong></span>
                        </div>

                        <!-- Stops list -->
                        <div class="border-t border-gray-100 pt-4 space-y-2">
                            <div class="flex justify-between items-center">
                                <h5 class="text-xs font-bold text-gray-500 uppercase tracking-wider">Designated Stops</h5>
                                <button @click="openStopModal(routeItem.id)" class="text-xs font-semibold text-indigo-600 hover:underline">+ Add Stop</button>
                            </div>
                            <ul v-if="routeItem.stops.length > 0" class="space-y-1">
                                <li v-for="stop in routeItem.stops" :key="stop.id" class="flex justify-between items-center bg-gray-50 p-2 rounded-lg text-xs">
                                    <span class="font-medium text-gray-800">{{ stop.stop_name }}</span>
                                    <span class="text-gray-500">Est. Time: {{ stop.pickup_time || '-' }}</span>
                                </li>
                            </ul>
                            <p v-else class="text-xs text-gray-400 italic">No stops added to this route yet.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 2: Vehicle Fleet -->
            <div v-if="activeTab === 'vehicles'" class="space-y-6">
                <div class="flex justify-between items-center">
                    <h3 class="font-bold text-gray-900 text-lg">Bus Fleets</h3>
                    <button @click="showVehicleModal = true" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold shadow-xs">
                        + Add Vehicle
                    </button>
                </div>

                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 font-semibold text-gray-600">Bus Registration Number</th>
                                <th class="px-6 py-3 font-semibold text-gray-600">Model Name</th>
                                <th class="px-6 py-3 font-semibold text-gray-600">Capacity</th>
                                <th class="px-6 py-3 font-semibold text-gray-600">Driver Name</th>
                                <th class="px-6 py-3 font-semibold text-gray-600">Driver Phone</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="vehicle in vehicles" :key="vehicle.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-semibold text-gray-900 font-mono">{{ vehicle.vehicle_number }}</td>
                                <td class="px-6 py-4 text-gray-700">{{ vehicle.model_name || '-' }}</td>
                                <td class="px-6 py-4 text-gray-700">{{ vehicle.capacity }} seats</td>
                                <td class="px-6 py-4 text-gray-900">{{ vehicle.driver_name }}</td>
                                <td class="px-6 py-4 text-gray-500 font-mono">{{ vehicle.driver_phone }}</td>
                            </tr>
                            <tr v-if="vehicles.length === 0">
                                <td colspan="5" class="px-6 py-8 text-center text-gray-400">No vehicles registered.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab 3: Transport Allocations -->
            <div v-if="activeTab === 'allocations'" class="space-y-6">
                <div class="flex justify-between items-center">
                    <h3 class="font-bold text-gray-900 text-lg">Active Allocations</h3>
                    <button @click="showAllocationModal = true" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold shadow-xs">
                        + Allocate Route Pass
                    </button>
                </div>

                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 font-semibold text-gray-600">User Type</th>
                                <th class="px-6 py-3 font-semibold text-gray-600">Name</th>
                                <th class="px-6 py-3 font-semibold text-gray-600">Assigned Route</th>
                                <th class="px-6 py-3 font-semibold text-gray-600">Bus No</th>
                                <th class="px-6 py-3 font-semibold text-gray-600">Pickup Stop</th>
                                <th class="px-6 py-3 font-semibold text-gray-600">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="alloc in allocations" :key="alloc.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 capitalize text-gray-500 text-xs">{{ alloc.user_type }}</td>
                                <td class="px-6 py-4 font-medium text-gray-900">{{ alloc.user?.name }}</td>
                                <td class="px-6 py-4 text-gray-700 font-semibold">{{ alloc.route?.route_name }}</td>
                                <td class="px-6 py-4 text-gray-500 font-mono">{{ alloc.vehicle?.vehicle_number || 'N/A' }}</td>
                                <td class="px-6 py-4 text-gray-700">{{ alloc.pickup_point }}</td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-0.5 bg-green-50 text-green-700 border border-green-200 text-xs font-semibold rounded-full">Active</span>
                                </td>
                            </tr>
                            <tr v-if="allocations.length === 0">
                                <td colspan="6" class="px-6 py-8 text-center text-gray-400">No transport allocations logged.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Add Vehicle Modal -->
        <div v-if="showVehicleModal" class="fixed inset-0 z-50 flex justify-end bg-black/40 backdrop-blur-xs overflow-hidden">
            <div class="absolute inset-0" @click="showVehicleModal = false"></div>
            <div class="bg-white w-full max-w-lg shadow-2xl flex flex-col h-full z-10 animate-slideLeft border-l border-gray-200">
                <div class="p-6 sm:p-8 flex-1 overflow-y-auto space-y-6">
                    <div class="flex justify-between items-center pb-4 border-b border-gray-100">
                        <h3 class="text-lg font-bold text-gray-900">Add Fleet Vehicle</h3>
                        <button @click="showVehicleModal = false" class="h-8 w-8 rounded-full hover:bg-gray-100 font-bold text-gray-500 flex items-center justify-center cursor-pointer"><i class="fa-solid fa-xmark text-lg"></i></button>
                    </div>
                    <form @submit.prevent="submitVehicle" class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Bus/Van Number Plate</label>
                            <input type="text" v-model="vehicleForm.vehicle_number" placeholder="e.g. LES-1234" required class="w-full rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Bus Model/Make</label>
                            <input type="text" v-model="vehicleForm.model_name" placeholder="e.g. Toyota Coaster 2024" class="w-full rounded-lg border-gray-300 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Capacity</label>
                            <input type="number" v-model="vehicleForm.capacity" required class="w-full rounded-lg border-gray-300 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Driver Name</label>
                            <input type="text" v-model="vehicleForm.driver_name" required class="w-full rounded-lg border-gray-300 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Driver Mobile Phone</label>
                            <input type="text" v-model="vehicleForm.driver_phone" required class="w-full rounded-lg border-gray-300 text-sm" />
                        </div>
                        <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                            <button type="button" @click="showVehicleModal = false" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">Cancel</button>
                            <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700">Save Vehicle</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Add Route Modal -->
        <div v-if="showRouteModal" class="fixed inset-0 z-50 flex justify-end bg-black/40 backdrop-blur-xs overflow-hidden">
            <div class="absolute inset-0" @click="showRouteModal = false"></div>
            <div class="bg-white w-full max-w-lg shadow-2xl flex flex-col h-full z-10 animate-slideLeft border-l border-gray-200">
                <div class="p-6 sm:p-8 flex-1 overflow-y-auto space-y-6">
                    <div class="flex justify-between items-center pb-4 border-b border-gray-100">
                        <h3 class="text-lg font-bold text-gray-900">Configure Route</h3>
                        <button @click="showRouteModal = false" class="h-8 w-8 rounded-full hover:bg-gray-100 font-bold text-gray-500 flex items-center justify-center cursor-pointer"><i class="fa-solid fa-xmark text-lg"></i></button>
                    </div>
                    <form @submit.prevent="submitRoute" class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Route Name</label>
                            <input type="text" v-model="routeForm.route_name" placeholder="e.g. Route A - Gulberg & DHA" required class="w-full rounded-lg border-gray-300 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Assign Fleet Vehicle</label>
                            <select v-model="routeForm.vehicle_id" class="w-full rounded-lg border-gray-300 text-sm">
                                <option value="">No Vehicle Assigned</option>
                                <option v-for="vehicle in vehicles" :key="vehicle.id" :value="vehicle.id">{{ vehicle.vehicle_number }} - {{ vehicle.driver_name }}</option>
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 mb-1">Morning Pickup Time</label>
                                <input type="time" v-model="routeForm.pickup_time" class="w-full rounded-lg border-gray-300 text-sm" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 mb-1">Afternoon Drop Time</label>
                                <input type="time" v-model="routeForm.drop_time" class="w-full rounded-lg border-gray-300 text-sm" />
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Monthly Service Fee (PKR)</label>
                            <input type="number" v-model="routeForm.monthly_fee" required class="w-full rounded-lg border-gray-300 text-sm" />
                        </div>
                        <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                            <button type="button" @click="showRouteModal = false" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">Cancel</button>
                            <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700">Save Route</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Add Stop Modal -->
        <div v-if="showStopModal" class="fixed inset-0 z-50 flex justify-end bg-black/40 backdrop-blur-xs overflow-hidden">
            <div class="absolute inset-0" @click="showStopModal = false; selectedRouteId = null"></div>
            <div class="bg-white w-full max-w-lg shadow-2xl flex flex-col h-full z-10 animate-slideLeft border-l border-gray-200">
                <div class="p-6 sm:p-8 flex-1 overflow-y-auto space-y-6">
                    <div class="flex justify-between items-center pb-4 border-b border-gray-100">
                        <h3 class="text-lg font-bold text-gray-900">Add Stop Point</h3>
                        <button @click="showStopModal = false; selectedRouteId = null" class="h-8 w-8 rounded-full hover:bg-gray-100 font-bold text-gray-500 flex items-center justify-center cursor-pointer"><i class="fa-solid fa-xmark text-lg"></i></button>
                    </div>
                    <form @submit.prevent="submitStop" class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Stop Point Address/Name</label>
                            <input type="text" v-model="stopForm.stop_name" placeholder="e.g. Model Town Main Gate" required class="w-full rounded-lg border-gray-300 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Estimated Pickup Time</label>
                            <input type="time" v-model="stopForm.pickup_time" class="w-full rounded-lg border-gray-300 text-sm" />
                        </div>
                        <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                            <button type="button" @click="showStopModal = false; selectedRouteId = null" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">Cancel</button>
                            <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700">Add Stop</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Allocate Transport Modal -->
        <div v-if="showAllocationModal" class="fixed inset-0 z-50 flex justify-end bg-black/40 backdrop-blur-xs overflow-hidden">
            <div class="absolute inset-0" @click="showAllocationModal = false"></div>
            <div class="bg-white w-full max-w-lg shadow-2xl flex flex-col h-full z-10 animate-slideLeft border-l border-gray-200">
                <div class="p-6 sm:p-8 flex-1 overflow-y-auto space-y-6">
                    <div class="flex justify-between items-center pb-4 border-b border-gray-100">
                        <h3 class="text-lg font-bold text-gray-900">Allocate Transport Pass</h3>
                        <button @click="showAllocationModal = false" class="h-8 w-8 rounded-full hover:bg-gray-100 font-bold text-gray-500 flex items-center justify-center cursor-pointer"><i class="fa-solid fa-xmark text-lg"></i></button>
                    </div>
                    <form @submit.prevent="submitAllocation" class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Passenger Type</label>
                            <select v-model="allocationForm.user_type" class="w-full rounded-lg border-gray-300 text-sm">
                                <option value="student">Student</option>
                                <option value="staff">Staff/Teacher</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Select Passenger</label>
                            <select v-if="allocationForm.user_type === 'student'" v-model="allocationForm.user_id" required class="w-full rounded-lg border-gray-300 text-sm">
                                <option value="">Choose student...</option>
                                <option v-for="student in students" :key="student.id" :value="student.user_id">
                                    {{ student.user?.name }} (Roll # {{ student.roll_number }})
                                </option>
                            </select>
                            <select v-else v-model="allocationForm.user_id" required class="w-full rounded-lg border-gray-300 text-sm">
                                <option value="">Choose staff...</option>
                                <option v-for="member in staff" :key="member.id" :value="member.id">
                                    {{ member.name }} ({{ member.user_type }})
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Assign Route</label>
                            <select v-model="allocationForm.route_id" required class="w-full rounded-lg border-gray-300 text-sm">
                                <option value="">Choose route...</option>
                                <option v-for="routeItem in routes" :key="routeItem.id" :value="routeItem.id">
                                    {{ routeItem.route_name }} ({{ formatCurrency(routeItem.monthly_fee) }} / mo)
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Assign Vehicle</label>
                            <select v-model="allocationForm.vehicle_id" class="w-full rounded-lg border-gray-300 text-sm">
                                <option value="">Let Route Assign Auto</option>
                                <option v-for="vehicle in vehicles" :key="vehicle.id" :value="vehicle.id">{{ vehicle.vehicle_number }} - {{ vehicle.driver_name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Pickup Stop Location</label>
                            <input type="text" v-model="allocationForm.pickup_point" placeholder="e.g. Stop #3 Model Town Gate" required class="w-full rounded-lg border-gray-300 text-sm" />
                        </div>
                        <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                            <button type="button" @click="showAllocationModal = false" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">Cancel</button>
                            <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700">Allocate Pass</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </TenantAdminLayout>
</template>
