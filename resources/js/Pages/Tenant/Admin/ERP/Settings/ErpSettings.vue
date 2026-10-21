<script setup>
import { Head } from '@inertiajs/vue3';
import TenantAdminLayout from '@/Layouts/TenantAdminLayout.vue';
</script>

<template>
    <Head title="Settings Management" />
    
    <TenantAdminLayout>
        <template #header>
            <h2 class="text-lg sm:text-xl font-bold text-[#202124] capitalize truncate">
                Settings Management
            </h2>
        </template>
        
        <template #actions>
            <button class="rounded-full bg-[#1A73E8] px-5 py-2.5 text-xs font-semibold text-white shadow-sm hover:bg-[#1557B0] transition-all flex items-center space-x-2">
                <span>+</span>
                <span>Add New</span>
            </button>
        </template>
        
        <div class="rounded-2xl border border-[#DADCE0] bg-white p-6 sm:p-8 shadow-xs text-center flex flex-col items-center justify-center py-20">
            <div class="h-16 w-16 bg-[#E8F0FE] text-[#1A73E8] rounded-full flex items-center justify-center text-2xl mb-4 shadow-sm border border-[#D2E3FC]">
                <i class="fa-solid fa-layer-group"></i>
            </div>
            <h3 class="text-xl font-bold text-[#202124] mb-2">Settings Module Active</h3>
            <p class="text-sm text-[#5F6368] max-w-md mx-auto">
                The database tables for Settings (e.g. salary, inventory, transport, etc.) have been successfully migrated. 
                Use this space to build out the full CRUD interface for your school.
            </p>
        </div>
    </TenantAdminLayout>
</template>
