<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import TenantAdminLayout from '@/Layouts/TenantAdminLayout.vue';

const props = defineProps({
    events: Array,
});

const showEventModal = ref(false);

const eventForm = useForm({
    title: '',
    description: '',
    start_date: new Date().toISOString().split('T')[0],
    end_date: new Date().toISOString().split('T')[0],
    type: 'event',
    is_school_off: false,
});

const submitEvent = () => {
    eventForm.post(route('tenant.admin.calendar.store'), {
        onSuccess: () => {
            showEventModal.value = false;
            eventForm.reset();
        }
    });
};

const deleteEvent = (id) => {
    if (confirm('Are you sure you want to delete this event?')) {
        useForm({}).delete(route('tenant.admin.calendar.destroy', id));
    }
};
</script>

<template>
    <Head title="Academic Calendar" />

    <TenantAdminLayout>
        <template #header>
            <h2 class="text-xl font-bold text-[#202124] tracking-tight">Academic Calendar</h2>
        </template>

        <div class="space-y-6">
            <div class="flex justify-between items-center">
                <h3 class="font-bold text-gray-900 text-lg">School Events Calendar</h3>
                <button @click="showEventModal = true" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold shadow-xs">
                    + Add Calendar Event
                </button>
            </div>

            <!-- Events List -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div v-for="event in events" :key="event.id" class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm flex flex-col justify-between space-y-4">
                    <div class="space-y-2">
                        <div class="flex justify-between items-start">
                            <h4 class="text-base font-bold text-gray-900">{{ event.title }}</h4>
                            <span :class="{
                                'bg-red-50 text-red-700 border-red-200': event.type === 'holiday',
                                'bg-purple-50 text-purple-700 border-purple-200': event.type === 'exam',
                                'bg-blue-50 text-blue-700 border-blue-200': event.type === 'ptm',
                                'bg-green-50 text-green-700 border-green-200': event.type === 'event',
                                'bg-gray-50 text-gray-700 border-gray-200': event.type === 'other',
                            }" class="px-2.5 py-0.5 text-xs font-semibold rounded-full border capitalize">
                                {{ event.type }}
                            </span>
                        </div>
                        <p class="text-sm text-gray-600">{{ event.description || 'No description provided.' }}</p>
                    </div>

                    <div class="flex items-center justify-between border-t border-gray-100 pt-4 text-xs text-gray-500">
                        <div class="flex items-center space-x-4">
                            <span>Starts: <strong class="text-gray-700">{{ new Date(event.start_date).toLocaleDateString() }}</strong></span>
                            <span>Ends: <strong class="text-gray-700">{{ new Date(event.end_date).toLocaleDateString() }}</strong></span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span v-if="event.is_school_off" class="px-2 py-0.5 bg-red-100 text-red-800 text-[10px] font-bold rounded-sm uppercase">School Off</span>
                            <button @click="deleteEvent(event.id)" class="text-red-500 hover:text-red-700 font-semibold">Delete</button>
                        </div>
                    </div>
                </div>
                
                <div v-if="events.length === 0" class="col-span-2 bg-white rounded-2xl border border-gray-200 p-12 text-center text-gray-400">
                    No academic events scheduled yet.
                </div>
            </div>
        </div>

        <!-- Add Event Modal -->
        <div v-if="showEventModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs">
            <div class="bg-white rounded-2xl border border-gray-200 max-w-md w-full p-6 shadow-xl">
                <div class="flex justify-between items-center pb-4 border-b border-gray-100 mb-4">
                    <h3 class="text-lg font-bold text-gray-900 font-Outfit">Create Calendar Event</h3>
                    <button @click="showEventModal = false" class="text-gray-400 hover:text-gray-600 text-2xl font-bold">&times;</button>
                </div>
                <form @submit.prevent="submitEvent" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Event Title</label>
                        <input type="text" v-model="eventForm.title" placeholder="e.g. Annual Parent-Teacher Meeting" required class="w-full rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Event Type</label>
                        <select v-model="eventForm.type" class="w-full rounded-lg border-gray-300 text-sm">
                            <option value="event">School Event</option>
                            <option value="holiday">Official Holiday</option>
                            <option value="exam">Examination Session</option>
                            <option value="ptm">Parent-Teacher Meeting (PTM)</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Start Date</label>
                            <input type="date" v-model="eventForm.start_date" required class="w-full rounded-lg border-gray-300 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">End Date</label>
                            <input type="date" v-model="eventForm.end_date" required class="w-full rounded-lg border-gray-300 text-sm" />
                        </div>
                    </div>
                    <div class="flex items-center space-x-2 pt-2">
                        <input type="checkbox" id="school_off" v-model="eventForm.is_school_off" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                        <label for="school_off" class="text-sm font-medium text-gray-700">Declare school closed for this event</label>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Event Description</label>
                        <textarea v-model="eventForm.description" rows="3" class="w-full rounded-lg border-gray-300 text-sm"></textarea>
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <button type="button" @click="showEventModal = false" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">Cancel</button>
                        <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700">Save Event</button>
                    </div>
                </form>
            </div>
        </div>
    </TenantAdminLayout>
</template>
