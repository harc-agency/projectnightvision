<template>
  <AuthenticatedLayout>
    <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 py-8">
      <!-- Full-Width Content -->
      <div>
        <!-- Dream Title and Add Context Button -->
        <div class="flex justify-between items-center mb-6">
          <div>
            <h2 class="text-2xl font-bold">{{ dream.title }}</h2>
            <div class="text-gray-400">{{ new Date(dream.dream_date).toLocaleDateString() }}</div>
          </div>
          <button
            @click="toggleDrawer"
            class="px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-md shadow hover:bg-blue-500 focus:outline-none focus:ring focus:ring-blue-300"
          >
            Add Context
          </button>
        </div>

        <!-- Dream Content -->
        <h2 class="text-2xl font-bold mb-4">Dream Content</h2>
        <div class="overflow-hidden rounded-lg bg-gray-800 shadow mb-6">
          <div class="px-4 py-5 sm:p-6">
            {{ dream.dream_content }}
          </div>
        </div>

        <!-- Dream Analysis -->
        <div class="flex items-center mb-4">
          <h2 class="text-2xl font-bold">Analysis</h2>
            <div
            class="ml-4 h-6 w-6 rounded-full relative"
            :style="{
              background: getGradient(dream.sentiment)
            }"
            :title="'Sentiment Analysis: ' + (dream.sentiment || 'Unknown')"
            >
            <span
              class="absolute bottom-8 left-1/2 transform -translate-x-1/2 w-max text-sm text-gray-100 bg-gray-700 rounded-md py-1 px-2 shadow-lg opacity-0 transition-opacity hover:opacity-100"
            >
              Sentiment: {{ dream.sentiment || 'Unknown' }}
            </span>
          </div>
        </div>
        <div class="overflow-hidden rounded-lg bg-gray-800 shadow">
          <div class="px-4 py-5 sm:p-6">
            {{ dream.analysis }}
          </div>
        </div>
      </div>
      <br>
      <!-- Dream Symbols -->
      <h2 class="text-2xl font-bold mb-4">Symbols</h2>
      <div class="overflow-hidden rounded-lg bg-gray-800 shadow mb-6">
        <div class="px-4 py-5 sm:p-6">
          <ul>
            <li v-for="symbol in dream.symbols" :key="symbol.id" class="mb-2">
              <span class="font-semibold text-white">{{ symbol.title }}:</span>
              <span class="text-gray-400">{{ symbol.description }}</span>
            </li>
          </ul>
        </div>
      </div>

      <!-- Drawer -->
      <div
        :class="drawerOpen ? 'translate-x-0' : 'translate-x-full'"
        class="fixed top-0 right-0 h-full w-80 bg-gray-800 shadow-lg transform transition-transform duration-300 ease-in-out z-50"
      >
        <div class="px-4 py-5 sm:p-6">
          <button
            @click="toggleDrawer"
            class="absolute top-4 right-4 text-gray-400 hover:text-gray-100"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
          <h3 class="text-lg font-semibold text-white mb-4">Add Context</h3>
          <form>
            <div class="mb-4">
              <label for="dream_date" class="block text-sm text-gray-300">Dream Date</label>
              <input
                type="date"
                id="dream_date"
                name="dream_date"
                class="mt-1 block w-full rounded-md bg-gray-700 border-gray-600 text-gray-300"
              />
            </div>
            <div class="mb-4">
              <label for="dream_location" class="block text-sm text-gray-300">Dream Location</label>
              <input
                type="text"
                id="dream_location"
                name="dream_location"
                class="mt-1 block w-full rounded-md bg-gray-700 border-gray-600 text-gray-300"
              />
            </div>
            <div class="mb-4">
              <label for="mood_before_sleep" class="block text-sm text-gray-300">Mood Before Sleep</label>
              <input
                type="text"
                id="mood_before_sleep"
                name="mood_before_sleep"
                class="mt-1 block w-full rounded-md bg-gray-700 border-gray-600 text-gray-300"
              />
            </div>
            <div class="mb-4">
              <label for="mood_after_waking" class="block text-sm text-gray-300">Mood After Waking</label>
              <input
                type="text"
                id="mood_after_waking"
                name="mood_after_waking"
                class="mt-1 block w-full rounded-md bg-gray-700 border-gray-600 text-gray-300"
              />
            </div>
            <div class="mb-4">
              <label for="intensity" class="block text-sm text-gray-300">Intensity</label>
              <input
                type="number"
                id="intensity"
                name="intensity"
                class="mt-1 block w-full rounded-md bg-gray-700 border-gray-600 text-gray-300"
              />
            </div>
            <div class="mb-4">
              <label for="sleep_quality" class="block text-sm text-gray-300">Sleep Quality</label>
              <input
                type="text"
                id="sleep_quality"
                name="sleep_quality"
                class="mt-1 block w-full rounded-md bg-gray-700 border-gray-600 text-gray-300"
              />
            </div>
            <div class="mb-4">
              <label for="sleep_duration" class="block text-sm text-gray-300">Sleep Duration</label>
              <input
                type="text"
                id="sleep_duration"
                name="sleep_duration"
                class="mt-1 block w-full rounded-md bg-gray-700 border-gray-600 text-gray-300"
              />
            </div>
            <div class="mb-4">
              <label for="location" class="block text-sm text-gray-300">Location</label>
              <input
                type="text"
                id="location"
                name="location"
                class="mt-1 block w-full rounded-md bg-gray-700 border-gray-600 text-gray-300"
              />
            </div>
            <div class="mb-4">
              <label for="weather" class="block text-sm text-gray-300">Weather</label>
              <input
                type="text"
                id="weather"
                name="weather"
                class="mt-1 block w-full rounded-md bg-gray-700 border-gray-600 text-gray-300"
              />
            </div>
            <button
              type="submit"
              class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-500"
            >
              Save Context
            </button>
          </form>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const drawerOpen = ref(false);

/**
 * Toggle the drawer visibility.
 */
const toggleDrawer = () => {
  drawerOpen.value = !drawerOpen.value;
};

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
  dream: {
    type: Object,
    required: true,
  },
});
</script>
