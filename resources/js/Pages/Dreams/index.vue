<template>
  <AuthenticatedLayout>
    <div class="py-24 sm:py-32">
      <div class="mx-auto max-w-7xl px-6 lg:px-8">
        <div class="mx-auto max-w-2xl text-center">
          <h2 class="text-balance text-4xl font-semibold tracking-tight text-white sm:text-5xl">Dream Entries</h2>
          <p class="mt-2 text-lg/8 text-gray-300">Explore and manage your dream entries here.</p>
        </div>
        <div class="mx-auto mt-16 grid max-w-2xl auto-rows-fr grid-cols-1 gap-8 sm:mt-20 lg:mx-0 lg:max-w-none lg:grid-cols-3">
          <article
            v-for="dream in dreams"
            :key="dream.id"
            :style="{
              background: getGradient(dream.sentiment)
            }"
            class="relative isolate flex flex-col justify-end overflow-hidden rounded-2xl px-8 pb-8 pt-80 sm:pt-48 lg:pt-80"
          >
            <div class="absolute inset-0 -z-10 rounded-2xl ring-1 ring-inset ring-gray-900/10" />
            <div class="flex flex-wrap items-center gap-y-1 overflow-hidden text-sm/6 text-gray-300">
                <time :datetime="dream.date" class="mr-8">{{ new Date(dream.created_at).toLocaleDateString() || 'Unknown date' }}</time>
            </div>
            <h3 class="mt-3 text-lg/6 font-semibold text-white">
              <a :href="`/dreams/${dream.id}`">
                <span class="absolute inset-0" />
                {{ dream.title }}
              </a>
            </h3>
          </article>
        </div>
      </div>
    </div>

    <!-- Sticky Add Dream Button -->
    <div class="fixed bottom-6 right-6">
      <a
        href="/dreams/create"
        class="flex items-center justify-center px-6 py-3 bg-blue-600 text-white text-lg font-semibold rounded-full shadow hover:bg-blue-500 focus:outline-none focus:ring focus:ring-blue-300"
      >
        Add Dream
      </a>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const placeholderAuthorImage =
  'https://via.placeholder.com/64x64.png?text=Author';

/**
 * Get the appropriate gradient based on the dream sentiment.
 * @param {string} sentiment - The sentiment of the dream.
 * @returns {string} - The CSS gradient background.
 */
const getGradient = (sentiment) => {
  if (sentiment === 'negative') {
    return 'linear-gradient(to right, #240b36, #c31432)';
  } else if (sentiment === 'positive') {
    return 'linear-gradient(to right, #92FE9D, #00C9FF)';
  } else if (sentiment === 'neutral') {
    return 'linear-gradient(to right, #3498db, #2c3e50)';
  }
  return 'linear-gradient(to right, #1d2b64, #f8cdda)'; // Default gradient
};

defineProps({
  dreams: {
    type: Array,
    required: true,
  },
});
</script>
