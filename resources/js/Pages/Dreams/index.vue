<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    dreams: {
        type: Array,
        required: true,
    },
});

const sentimentLabel = (sentiment) => {
    if (!sentiment) {
        return 'Unclassified';
    }

    return sentiment.charAt(0).toUpperCase() + sentiment.slice(1);
};

const sentimentClasses = (sentiment) => {
    if (sentiment === 'positive') {
        return 'bg-emerald-500/20 text-emerald-200 border-emerald-400/40';
    }
    if (sentiment === 'negative') {
        return 'bg-rose-500/20 text-rose-200 border-rose-400/40';
    }
    if (sentiment === 'neutral') {
        return 'bg-sky-500/20 text-sky-200 border-sky-400/40';
    }

    return 'bg-slate-700/60 text-slate-200 border-slate-500/40';
};
</script>

<template>
    <Head title="Dreams" />

    <AuthenticatedLayout>
        <div class="pnv-shell">
            <div class="pnv-header">
                <div>
                    <p class="pnv-eyebrow">Dream Archive</p>
                    <h1 class="pnv-title">Dream Entries</h1>
                    <p class="pnv-subtitle">
                        Review past submissions, sentiment, and generated interpretations.
                    </p>
                </div>
                <Link
                    :href="route('dreams.create')"
                    class="rounded-md bg-sky-500 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-sky-400"
                >
                    Add Dream
                </Link>
            </div>

            <div v-if="dreams.length" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <article
                    v-for="dream in props.dreams"
                    :key="dream.id"
                    class="pnv-panel transition hover:border-slate-600 hover:bg-slate-900/85"
                >
                    <div class="pnv-panel-body">
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <time class="text-xs uppercase tracking-[0.12em] text-slate-400">
                                {{ new Date(dream.created_at).toLocaleDateString() }}
                            </time>
                            <span
                                :class="[
                                    'inline-flex rounded-md border px-2 py-1 text-xs font-medium',
                                    sentimentClasses(dream.sentiment),
                                ]"
                            >
                                {{ sentimentLabel(dream.sentiment) }}
                            </span>
                        </div>

                        <h2 class="text-xl font-semibold text-slate-100">
                            <Link :href="route('dreams.show', { dream: dream.id })" class="hover:text-sky-200">
                                {{ dream.title || 'Untitled Dream' }}
                            </Link>
                        </h2>

                        <p class="mt-2 text-xs uppercase tracking-[0.12em] text-slate-400">
                            {{ dream.is_public ? 'Public' : 'Private' }}
                        </p>

                        <p class="mt-3 max-h-24 overflow-hidden text-sm leading-6 text-slate-300">
                            {{ dream.dream_content || 'No dream content available.' }}
                        </p>
                    </div>
                </article>
            </div>

            <div v-else class="pnv-panel">
                <div class="pnv-panel-body">
                    <h2 class="text-xl font-semibold text-slate-100">No dreams yet</h2>
                    <p class="mt-2 text-sm text-slate-300">
                        Submit your first dream to start building your private library.
                    </p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
