<template>
  <AuthenticatedLayout>

    <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">


      <div class="flex justify-between">
        <h2 class="text-2xl font-bold mb-4">{{ dream.title }}</h2>
        <div class="text-gray-400">
          {{ dream.dream_date }}
        </div>
      </div>

      <h2 class="text-2xl font-bold mb-4">Dream Content</h2>

      <div class="overflow-hidden rounded-lg bg-gray-800 shadow mb-4">
        <div class="px-4 py-5 sm:p-6">
          {{ dream.dream_content }}
        </div>
      </div>
      <h2 class="text-2xl font-bold mb-4">Analysis</h2>
      <div class="overflow-hidden rounded-lg bg-gray-800 shadow dark">
        <div class="px-4 py-5 sm:p-6">
          {{ dream.analysis }}
        </div>
      </div>


    </div>


  </AuthenticatedLayout>

</template>

<script setup>

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
defineProps({
  dream: {
    type: Object,
    required: true
  }
});

</script>

<style scoped></style>
