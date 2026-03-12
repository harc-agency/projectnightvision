<script setup>
import { computed, onMounted, ref } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    stats: {
        type: Object,
        required: true,
    },
    points: {
        type: Array,
        required: true,
    },
});

const page = usePage();
const hoveredPoint = ref(null);
const globeError = ref('');

const safeSentiment = computed(() => ({
    positive: props.stats?.sentiment?.positive ?? 0,
    neutral: props.stats?.sentiment?.neutral ?? 0,
    negative: props.stats?.sentiment?.negative ?? 0,
}));

const totalPublicDreams = computed(() => props.stats?.total_public_dreams ?? 0);
const mappedPointsCount = computed(() => props.points?.length ?? 0);

const markerLocation = (point) => `${point.lat} ${point.lng}`;

const markerClass = (point) => {
    const sentiment = point?.sentiment || 'neutral';
    return `globe-marker globe-marker--${sentiment}`;
};

const handlePointClick = (point) => {
    if (!point) {
        return;
    }

    if (!page.props.auth?.user) {
        window.location.href = route('login');
        return;
    }

    window.location.href = route('dreams.show', { dream: point.id });
};

const clearHover = () => {
    hoveredPoint.value = null;
};

onMounted(() => {
    import('@/hyper-globe.min.js')
        .catch(() => {
            globeError.value = 'Failed to load globe renderer.';
        });
});
</script>

<template>
    <Head title="Dream Globe" />

    <AuthenticatedLayout>
        <div class="pnv-shell">
            <div class="pnv-header">
                <div>
                    <p class="pnv-eyebrow">Global Patterning</p>
                    <h1 class="pnv-title">Dream Globe</h1>
                    <p class="pnv-subtitle">
                        Interactive land-and-sea globe with click-ready public dream markers.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <Link
                        :href="page.props.auth?.user ? route('dreams.create') : route('login')"
                        class="rounded-md bg-sky-500 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-sky-400"
                    >
                        {{ page.props.auth?.user ? 'Submit Dream' : 'Log In to Submit' }}
                    </Link>
                    <Link
                        :href="route('library')"
                        class="rounded-md border border-slate-700 px-4 py-2 text-sm font-medium text-slate-200 transition hover:bg-slate-800"
                    >
                        Open Library
                    </Link>
                </div>
            </div>

            <div class="grid gap-4 lg:grid-cols-3">
                <section class="pnv-panel lg:col-span-2">
                    <div class="pnv-panel-body">
                        <h2 class="text-2xl font-semibold text-slate-100">Signal Map</h2>

                        <div class="globe-wrap mt-4">
                            <hyper-globe
                                v-if="mappedPointsCount && !globeError"
                                id="dream-globe"
                                data-location="20 0"
                                data-version="1"
                                class="hyper-globe"
                                style="--preview-color: #020617; max-width: 720px; --globe-scale: 0.9; --globe-damping: 0.65; --map-density: 0.6; --map-height: 0.22; --point-size: 0.95; --point-color: #94a3b8; --backside-opacity: 0.18; --backside-transition: 0.18; --marker-size: 0.8; --autorotate: false; --globe-draggable: true; --line-thickness: 0; --text-size: 0;"
                            >
                                <a
                                    v-for="point in points"
                                    :key="`marker-${point.id}`"
                                    slot="markers"
                                    :class="markerClass(point)"
                                    :data-location="markerLocation(point)"
                                    :title="point.title || 'Untitled Dream'"
                                    href="#"
                                    @mouseenter="hoveredPoint = point"
                                    @mouseleave="clearHover"
                                    @click.prevent="handlePointClick(point)"
                                />
                            </hyper-globe>

                            <div v-else class="globe-empty">
                                {{
                                    globeError
                                        ? globeError
                                        : 'No location-tagged dreams yet.'
                                }}
                            </div>

                            <div v-if="hoveredPoint" class="globe-tooltip">
                                <p class="text-sm font-semibold text-slate-100">
                                    {{ hoveredPoint.title || 'Untitled Dream' }}
                                </p>
                                <p v-if="hoveredPoint.location_label" class="mt-1 text-xs text-slate-300">
                                    {{ hoveredPoint.location_label }}
                                </p>
                                <p class="mt-1 text-xs text-slate-300">
                                    {{ hoveredPoint.theme || 'No theme' }} • {{ hoveredPoint.sentiment || 'neutral' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="space-y-4">
                    <article class="pnv-panel">
                        <div class="pnv-panel-body grid grid-cols-2 gap-3">
                            <div>
                                <p class="text-xs uppercase tracking-[0.12em] text-slate-400">Public Dreams</p>
                                <p class="mt-2 text-3xl font-semibold text-slate-100">
                                    {{ totalPublicDreams }}
                                </p>
                            </div>
                            <div class="space-y-1 text-sm text-slate-300">
                                <p>Positive: {{ safeSentiment.positive }}</p>
                                <p>Neutral: {{ safeSentiment.neutral }}</p>
                                <p>Negative: {{ safeSentiment.negative }}</p>
                                <p class="pt-1 text-slate-200">Mapped: {{ mappedPointsCount }}</p>
                            </div>
                        </div>
                    </article>

                    <article class="pnv-panel">
                        <div class="pnv-panel-body">
                            <h3 class="text-xl font-semibold text-slate-100">Top Symbols</h3>
                            <div class="mt-3 space-y-2">
                                <div
                                    v-for="symbol in stats.symbols"
                                    :key="symbol.symbol_key"
                                    class="flex items-center justify-between rounded-md border border-slate-700/70 bg-slate-800/60 px-3 py-2 text-sm"
                                >
                                    <span class="text-slate-100">{{ symbol.title }}</span>
                                    <span class="text-slate-300">{{ symbol.count }}</span>
                                </div>
                                <p v-if="!stats.symbols?.length" class="text-sm text-slate-300">
                                    No symbol data yet.
                                </p>
                            </div>
                        </div>
                    </article>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.globe-wrap {
    position: relative;
    width: 100%;
    display: flex;
    justify-content: center;
    align-items: center;
}

.hyper-globe {
    display: block;
    width: 100%;
    max-width: 720px;
    min-height: 360px;
}

.hyper-globe:not(:defined) {
    visibility: hidden;
}

.hyper-globe:not(:defined) > * {
    display: none;
}

.globe-empty {
    position: absolute;
    inset: auto 0 1.25rem 0;
    text-align: center;
    font-size: 0.875rem;
    color: #94a3b8;
}

.globe-tooltip {
    position: absolute;
    left: 1rem;
    bottom: 1rem;
    max-width: 18rem;
    border-radius: 0.5rem;
    border: 1px solid rgba(148, 163, 184, 0.35);
    background: rgba(15, 23, 42, 0.86);
    padding: 0.6rem 0.75rem;
    backdrop-filter: blur(2px);
}

.globe-marker {
    display: block;
    width: 0.6rem;
    height: 0.6rem;
    border-radius: 9999px;
    border: 2px solid rgba(226, 232, 240, 0.92);
    background: #38bdf8;
    box-shadow: 0 0 0 2px rgba(15, 23, 42, 0.55), 0 0 14px rgba(56, 189, 248, 0.65);
    text-decoration: none;
    pointer-events: auto;
}

.globe-marker--positive {
    background: #22c55e;
    box-shadow: 0 0 0 2px rgba(15, 23, 42, 0.55), 0 0 14px rgba(34, 197, 94, 0.65);
}

.globe-marker--neutral {
    background: #38bdf8;
    box-shadow: 0 0 0 2px rgba(15, 23, 42, 0.55), 0 0 14px rgba(56, 189, 248, 0.65);
}

.globe-marker--negative {
    background: #f97316;
    box-shadow: 0 0 0 2px rgba(15, 23, 42, 0.55), 0 0 14px rgba(249, 115, 22, 0.65);
}
</style>
