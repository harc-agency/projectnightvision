<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    dreams: {
        type: Array,
        required: true,
    },
});

const page = usePage();

const isAuthenticated = computed(() => !!page.props.auth?.user);

const sentimentClasses = (sentiment) => {
    if (sentiment === 'positive') {
        return 'bg-emerald-500/20 text-emerald-200 border-emerald-400/40';
    }

    if (sentiment === 'negative') {
        return 'bg-rose-500/20 text-rose-200 border-rose-400/40';
    }

    return 'bg-sky-500/20 text-sky-200 border-sky-400/40';
};

const formatDate = (value) => {
    if (!value) {
        return 'Unknown date';
    }

    const parsed = new Date(value);

    if (Number.isNaN(parsed.getTime())) {
        return value;
    }

    return parsed.toLocaleDateString();
};
</script>

<template>
    <Head title="Dream Library" />

    <AuthenticatedLayout>
        <div class="pnv-shell">
            <div class="pnv-header">
                <div>
                    <p class="pnv-eyebrow">Public Archive</p>
                    <h1 class="pnv-title">Dream Library</h1>
                    <p class="pnv-subtitle">
                        Browse dreams that users chose to share publicly with the community.
                    </p>
                </div>
                <Link
                    :href="isAuthenticated ? route('dreams.create') : route('login')"
                    class="rounded-md bg-sky-500 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-sky-400"
                >
                    {{ isAuthenticated ? 'Submit Dream' : 'Log In to Submit' }}
                </Link>
            </div>

            <div v-if="dreams.length" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <article
                    v-for="dream in dreams"
                    :key="dream.id"
                    class="pnv-panel transition hover:border-slate-600 hover:bg-slate-900/85"
                >
                    <div class="pnv-panel-body">
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <time class="text-xs uppercase tracking-[0.12em] text-slate-400">
                                {{ formatDate(dream.dream_date) }}
                            </time>
                            <span
                                :class="[
                                    'inline-flex rounded-md border px-2 py-1 text-xs font-medium capitalize',
                                    sentimentClasses(dream.sentiment),
                                ]"
                            >
                                {{ dream.sentiment || 'neutral' }}
                            </span>
                        </div>

                        <h2 class="text-xl font-semibold text-slate-100">
                            {{ dream.title || 'Untitled Dream' }}
                        </h2>

                        <p class="mt-1 text-xs uppercase tracking-[0.12em] text-slate-400">
                            Shared by {{ dream.author_name || 'Anonymous' }}
                        </p>

                        <p class="mt-3 text-sm leading-6 text-slate-300">
                            {{ dream.excerpt || 'No dream content available.' }}
                        </p>

                        <div v-if="dream.symbols?.length" class="mt-4 flex flex-wrap gap-2">
                            <Link
                                v-for="symbol in dream.symbols"
                                :key="`${dream.id}-${symbol.symbol_key}`"
                                :href="isAuthenticated ? route('symbols.show', { symbol: symbol.symbol_key }) : route('login')"
                                class="rounded-full border border-slate-600/80 px-2.5 py-1 text-xs text-slate-200 hover:bg-slate-800"
                            >
                                {{ symbol.title }}
                            </Link>
                        </div>

                        <p v-if="dream.overall_theme" class="mt-4 text-sm text-slate-300">
                            Theme: <span class="text-slate-100">{{ dream.overall_theme }}</span>
                        </p>

                        <div class="mt-5">
                            <Link
                                :href="isAuthenticated ? route('dreams.show', { dream: dream.id }) : route('login')"
                                class="text-sm font-medium text-sky-200 hover:text-sky-100"
                            >
                                {{ isAuthenticated ? 'View Dream Detail' : 'Log In to View Detail' }}
                            </Link>
                        </div>
                    </div>
                </article>
            </div>

            <div v-else class="pnv-panel">
                <div class="pnv-panel-body">
                    <h2 class="text-xl font-semibold text-slate-100">No public dreams yet</h2>
                    <p class="mt-2 text-sm text-slate-300">
                        Shared dreams will appear here once users mark entries as public.
                    </p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
