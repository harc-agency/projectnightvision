<template>
    <AuthenticatedLayout>

        <div class="border-b border-gray-200 px-4 py-5 sm:px-6">
                <div class="-ml-4 -mt-4 flex flex-wrap items-center justify-between sm:flex-nowrap">
                        <div class="ml-4 mt-4">
                                <h3 class="text-base font-semibold text-gray-300">Symbols</h3>
                                <p class="mt-1 text-sm text-gray-500">Explore and manage your symbols here.</p>
                        </div>
                </div>

                <ul role="list" class="grid grid-cols-1 gap-6 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                        <li v-for="symbol in symbols" :key="symbol.id" class="col-span-1 flex flex-col divide-y divide-gray-700 rounded-lg bg-gray-800 text-center shadow">
                                <div class="flex flex-1 flex-col p-8" :v-tippy="symbol.title">
                                        <img v-if="symbol.image" class="mx-auto h-32 w-32 shrink-0 rounded-full" :src="symbol.image" alt="">
                                        <div v-else v-tippy="'Add Image!'" class="mx-auto mt-4 h-32 w-32 shrink-0 rounded-full bg-gray-700 text-gray-300 flex items-center justify-center">
                                                <svg class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                                </svg>
                                        </div>

                                        <div class="flex items">

                                                <NavLink
                                                        :href="route('symbols.show', { symbol: symbol.symbol_key })"
                                                        :active="route().current('symbols.show', { id: symbol.symbol_key })"
                                                        class="mt-6 text-sm font-medium text-gray-300"
                                                >
                                                        {{ symbol.title }}
                                                </NavLink>
                                        </div>
                                </div>
                        </li>
                </ul>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link } from '@inertiajs/vue3';

defineProps({
        symbols: {
                type: Array,
                required: true
        }
});
</script>
