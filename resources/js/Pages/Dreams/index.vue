<template>
  <AuthenticatedLayout>

    <div class="border-b border-gray-200   px-4 py-5 sm:px-6"    >
        <div class="-ml-4 -mt-4 flex flex-wrap items-center justify-between sm:flex-nowrap">
            <div class="ml-4 mt-4">
                <h3 class="text-base font-semibold  ">Dream Entries</h3>
                <p class="mt-1 text-sm text-gray-500">Explore and manage your dream entries here.</p>
            </div>

            <div class="ml-4 my-4 sm:my-10 shrink-0 ">
                <Link :href="route('dreams.create')" class="relative inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                    Add New Dream
                </Link>
            </div>
        </div>

        <ul role="list" class="grid grid-cols-1 gap-6 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
            <li v-for="dream in dreams" :key="dream.id" class="col-span-1 flex flex-col divide-y divide-gray-700 rounded-lg bg-gray-800 text-center shadow">
                <div class="flex flex-1 flex-col p-8"
                :v-tippy="dream.title"
                >
                    <img v-if="dream.image" class="mx-auto h-32 w-32 shrink-0 rounded-full" :src="dream.image" alt="">
                    <div v-else
                    v-tippy="'Add Image!'"
                        class="mx-auto mt-4 h-32 w-32 shrink-0 rounded-full bg-gray-700 text-gray-300 flex items-center justify-center">
                        <svg

                        class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg></div>

                     <div class="flex items">
                        <h3 class="mt-6 text-sm font-medium text-gray-300">{{ dream.title }}</h3>
                    </div>
                </div>
                <div>
                    <div class="-mt-px flex divide-x divide-gray-700">
                        <div class="flex w-0 flex-1">
                            <a :href="`/dreams/${dream.id}`" class="relative -mr-px inline-flex w-0 flex-1 items-center justify-center gap-x-3 rounded-bl-lg border border-transparent py-4 text-sm font-semibold text-gray-300">
                                <svg class="h-5 w-5 text-gray-300" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
                                    <path d="M10 2a8 8 0 1 0 0 16 8 8 0 0 0 0-16ZM8.707 11.707a1 1 0 0 1-1.414-1.414L9.586 8 7.293 5.707a1 1 0 0 1 1.414-1.414L11 6.586l2.293-2.293a1 1 0 0 1 1.414 1.414L12.414 8l2.293 2.293a1 1 0 0 1-1.414 1.414L11 9.414l-2.293 2.293Z" />
                                </svg>
                                View
                            </a>
                        </div>
                        <div class="-ml-px flex w-0 flex-1">
                            <a :href="`/dreams/${dream.id}/edit`" class="relative inline-flex w-0 flex-1 items-center justify-center gap-x-3 rounded-br-lg border border-transparent py-4 text-sm font-semibold text-gray-300">
                                <svg class="h-5 w-5 text-gray-300" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
                                    <path d="M17.414 2.586a2 2 0 0 0-2.828 0L13 4.172l2.828 2.828 1.586-1.586a2 2 0 0 0 0-2.828ZM2 13.172V16h2.828l8.586-8.586-2.828-2.828L2 13.172Z" />
                                </svg>
                                Edit
                            </a>
                        </div>
                    </div>
                </div>
            </li>
        </ul>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link } from '@inertiajs/vue3';

import { Card, CardHeader, CardTitle, CardContent, CardFooter } from '@/components/ui/card';

defineProps({
    dreams: {
        type: Array,
        required: true
    }
});


</script>


