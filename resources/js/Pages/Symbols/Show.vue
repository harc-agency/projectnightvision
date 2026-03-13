<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineProps({
    symbol: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <Head :title="symbol.title" />

    <AuthenticatedLayout>
        <div class="pnv-shell">
            <div class="pnv-header">
                <div>
                    <p class="pnv-eyebrow">Symbol Profile</p>
                    <p class="mt-2 text-sm text-slate-300">
                        Referenced by {{ symbol.dreams_count || 0 }} dream entries.
                    </p>
                </div>
                <Link
                    :href="route('symbols.index')"
                    class="rounded-md border border-slate-700 px-4 py-2 text-sm font-medium text-slate-200 transition hover:bg-slate-800"
                >
                    Back to Symbols
                </Link>
            </div>

            <section class="pnv-panel">
                <div class="pnv-panel-body grid gap-6 lg:grid-cols-[minmax(0,24rem)_1fr] lg:items-center">
                    <div class="flex aspect-square items-center justify-center rounded-xl border border-slate-800/70 bg-slate-950/30 p-4">
                        <img
                            v-if="symbol.featured_image"
                            :src="symbol.featured_image"
                            :alt="symbol.title"
                            class="max-h-full w-auto max-w-full rounded-sm object-contain"
                        >
                        <div
                            v-else
                            class="flex h-full w-full items-center justify-center rounded-lg border border-dashed border-slate-700 text-sm text-slate-400"
                        >
                            No image yet
                        </div>
                    </div>

                    <div>
                        <p class="pnv-eyebrow">Interpretation</p>
                        <h1 class="mt-2 text-4xl font-semibold leading-tight text-slate-100">{{ symbol.title }}</h1>
                        <p class="mt-4 text-sm leading-7 text-slate-200">
                            {{ symbol.description }}
                        </p>
                    </div>
                </div>
            </section>

            <section class="pnv-panel mt-4">
                <div class="pnv-panel-body">
                    <h3 class="text-xl font-semibold text-slate-100">Recent Linked Dreams</h3>

                    <div v-if="symbol.dreams?.length" class="mt-3 grid gap-3 md:grid-cols-2">
                        <article
                            v-for="dream in symbol.dreams"
                            :key="dream.id"
                            class="rounded-md border border-slate-700/70 bg-slate-800/60 p-3"
                        >
                            <Link
                                :href="route('dreams.show', { dream: dream.id })"
                                class="text-sm font-semibold text-sky-200 hover:text-sky-100"
                            >
                                {{ dream.title || 'Untitled Dream' }}
                            </Link>
                            <p class="mt-1 text-xs text-slate-300">
                                {{ dream.dream_date ? new Date(dream.dream_date).toLocaleDateString() : 'Unknown date' }}
                            </p>
                        </article>
                    </div>

                    <p v-else class="mt-3 text-sm text-slate-300">
                        No dreams currently linked to this symbol.
                    </p>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
