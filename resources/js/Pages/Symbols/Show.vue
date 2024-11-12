<template>
    <AuthenticatedLayout>
        <div class="symbol">
            
            <h1 class="text-white">{{ symbol.title }}</h1>

            <p class="text-white">{{ symbol.description }}</p>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineProps({
        symbol: {
                type: Array,
                required: true
        }
});
</script>