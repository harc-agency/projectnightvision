<script setup>
import { ref, onMounted } from 'vue'; // Import necessary Vue Composition API functions
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Chart, registerables } from 'chart.js'; // Import Chart.js

// Register Chart.js components globally
Chart.register(...registerables);

// Define the props passed from the controller
defineProps({
    // radialChartData: Object, // Data passed from Laravel via Inertia
});

// Ref for the canvas element
// const radialChart = ref(null);

onMounted(() => {
    // Ensure radialChartData is available and has labels
    // if (!radialChartData || !radialChartData.labels || !radialChartData.labels.length) {
    //     console.warn('No data available for the radial chart.');
    //     return;
    // }

  
});
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Radial Chart Section -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <h3 class="text-lg font-medium mb-4">Dream Insights</h3>
                        <!-- Use the ref for the canvas -->
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
