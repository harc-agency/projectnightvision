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
                    <h1 class="pnv-title">{{ symbol.title }}</h1>
                    <p class="pnv-subtitle">
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

            <div class="grid gap-4 lg:grid-cols-3">
                <section class="pnv-panel lg:col-span-2">
                    <div class="pnv-panel-body">
                        <img
                            v-if="symbol.featured_image"
                            :src="symbol.featured_image"
                            :alt="symbol.title"
                            class="mb-4 h-56 w-full rounded-md object-cover"
                        >
                        <h2 class="text-2xl font-semibold text-slate-100">Interpretation</h2>
                        <p class="mt-3 text-sm leading-7 text-slate-200">
                            {{ symbol.description }}
                        </p>
                    </div>
                </section>

                <section class="pnv-panel">
                    <div class="pnv-panel-body">
                        <h3 class="text-xl font-semibold text-slate-100">Details</h3>
                        <dl class="mt-3 space-y-2 text-sm text-slate-300">
                            <div class="flex items-center justify-between gap-3">
                                <dt>Key</dt>
                                <dd class="text-slate-200">{{ symbol.symbol_key }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt>Linked dreams</dt>
                                <dd class="text-slate-200">{{ symbol.dreams_count || 0 }}</dd>
                            </div>
                        </dl>
                    </div>
                </section>
            </div>

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
