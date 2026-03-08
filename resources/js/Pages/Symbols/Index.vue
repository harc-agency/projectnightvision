<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineProps({
    symbols: {
        type: Array,
        required: true,
    },
});
</script>

<template>
    <Head title="Symbols" />

    <AuthenticatedLayout>
        <div class="pnv-shell">
            <div class="pnv-header">
                <div>
                    <p class="pnv-eyebrow">Symbol Database</p>
                    <h1 class="pnv-title">Dream Symbols</h1>
                    <p class="pnv-subtitle">
                        Explore recurring motifs and meanings linked across dream entries.
                    </p>
                </div>
                <Link
                    :href="route('library')"
                    class="rounded-md border border-slate-700 px-4 py-2 text-sm font-medium text-slate-200 transition hover:bg-slate-800"
                >
                    Open Public Library
                </Link>
            </div>

            <div v-if="symbols.length" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <article
                    v-for="symbol in symbols"
                    :key="symbol.id"
                    class="pnv-panel transition hover:border-slate-600 hover:bg-slate-900/85"
                >
                    <div class="pnv-panel-body">
                        <div class="flex items-start gap-4">
                            <img
                                v-if="symbol.featured_image"
                                class="h-16 w-16 rounded-md object-cover"
                                :src="symbol.featured_image"
                                :alt="symbol.title"
                            >
                            <div
                                v-else
                                class="flex h-16 w-16 items-center justify-center rounded-md border border-slate-700 bg-slate-800 text-xs text-slate-400"
                            >
                                No image
                            </div>

                            <div class="min-w-0 flex-1">
                                <h2 class="text-xl font-semibold text-slate-100">
                                    <Link
                                        :href="route('symbols.show', { symbol: symbol.symbol_key })"
                                        class="hover:text-sky-200"
                                    >
                                        {{ symbol.title }}
                                    </Link>
                                </h2>
                                <p class="mt-2 text-sm leading-6 text-slate-300">
                                    {{ symbol.description }}
                                </p>
                            </div>
                        </div>
                    </div>
                </article>
            </div>

            <div v-else class="pnv-panel">
                <div class="pnv-panel-body">
                    <h2 class="text-xl font-semibold text-slate-100">No symbols yet</h2>
                    <p class="mt-2 text-sm text-slate-300">
                        Symbols will populate as dreams are analyzed.
                    </p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
