<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import TenantAppLayout from '@/Layouts/TenantAppLayout.vue';

const page = usePage();
const authRole = computed(() => {
    const r = page.props.auth?.role || page.props.auth?.user?.role || page.props.auth?.user?.user_type || 'Principal';
    return r.charAt(0).toUpperCase() + r.slice(1);
});
</script>

<template>
    <TenantAppLayout :role-title="authRole">
        <template v-if="$slots.header" #header>
            <slot name="header"></slot>
        </template>
        
        <template v-if="$slots.actions" #header-actions>
            <slot name="actions"></slot>
        </template>

        <slot></slot>
    </TenantAppLayout>
</template>
