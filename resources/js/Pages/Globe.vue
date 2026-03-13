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
const selectedPoint = ref(null);
const globeError = ref('');
const markerPalette = {
    positive: '#38bdf8',
    neutral: '#94a3b8',
    negative: '#ef4444',
};

const authUser = computed(() => page.props.auth?.user ?? null);

const safeSentiment = computed(() => ({
    positive: props.stats?.sentiment?.positive ?? 0,
    neutral: props.stats?.sentiment?.neutral ?? 0,
    negative: props.stats?.sentiment?.negative ?? 0,
}));

const totalPublicDreams = computed(() => props.stats?.total_public_dreams ?? 0);
const mappedPointsCount = computed(() => props.points?.length ?? 0);
const topSymbols = computed(() => Array.isArray(props.stats?.symbols) ? props.stats.symbols : []);
const selectedSymbols = computed(() => Array.isArray(selectedPoint.value?.symbols) ? selectedPoint.value.symbols : []);
const selectedSymbolKeys = computed(() => new Set(selectedSymbols.value.map((symbol) => symbol.symbol_key)));
const activeInfoPoint = computed(() => selectedPoint.value ?? hoveredPoint.value);

const displaySymbols = computed(() => {
    const seen = new Set();
    const topSymbolLookup = new Map(topSymbols.value.map((symbol) => [symbol.symbol_key, symbol]));
    const symbols = [];

    selectedSymbols.value.forEach((symbol) => {
        if (!symbol?.symbol_key || seen.has(symbol.symbol_key)) {
            return;
        }

        const topSymbol = topSymbolLookup.get(symbol.symbol_key);

        symbols.push({
            symbol_key: symbol.symbol_key,
            title: symbol.title,
            count: topSymbol?.count ?? null,
        });

        seen.add(symbol.symbol_key);
    });

    topSymbols.value.forEach((symbol) => {
        if (!symbol?.symbol_key || seen.has(symbol.symbol_key)) {
            return;
        }

        symbols.push(symbol);
        seen.add(symbol.symbol_key);
    });

    return symbols;
});

const activeDreamHref = computed(() => {
    if (!activeInfoPoint.value) {
        return route('globe');
    }

    return authUser.value
        ? route('dreams.show', { dream: activeInfoPoint.value.id })
        : route('login');
});

const activeDreamLinkLabel = computed(() => (authUser.value ? 'Open Dream' : 'Log In to Open'));

const markerLocation = (point) => `${point.lat} ${point.lng}`;

const normalizeSentiment = (sentiment) => {
    if (sentiment === 'positive' || sentiment === 'negative') {
        return sentiment;
    }

    return 'neutral';
};

const sentimentLabel = (sentiment) => {
    const normalized = normalizeSentiment(sentiment);

    return normalized.charAt(0).toUpperCase() + normalized.slice(1);
};

const pointSummary = (point) => {
    if (!point) {
        return '';
    }

    const pieces = [sentimentLabel(point.sentiment)];

    if (point.theme) {
        pieces.push(point.theme);
    }

    const symbolCount = point.symbols?.length ?? 0;

    if (symbolCount > 0) {
        pieces.push(`${symbolCount} symbol${symbolCount === 1 ? '' : 's'}`);
    }

    return pieces.join(' • ');
};

const markerClass = (point) => {
    const classes = ['globe-marker', `globe-marker--${normalizeSentiment(point?.sentiment)}`];

    if (selectedPoint.value?.id === point?.id) {
        classes.push('globe-marker--active');
    }

    return classes.join(' ');
};

const buildMarkerImage = (fillColor, isActive = false) => {
    const accentStroke = isActive ? '#f8fafc' : '#020617';
    const accentRing = isActive
        ? '<circle cx="12" cy="12" r="10.25" fill="none" stroke="#f8fafc" stroke-width="2" opacity="0.95" />'
        : '';
    const svg = `
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
            ${accentRing}
            <circle cx="12" cy="12" r="8.5" fill="${fillColor}" stroke="${accentStroke}" stroke-width="2.25" />
        </svg>
    `.trim();

    return `url("data:image/svg+xml,${encodeURIComponent(svg)}")`;
};

const markerStyle = (point) => {
    const sentiment = normalizeSentiment(point?.sentiment);
    const isActive = selectedPoint.value?.id === point?.id;

    return {
        '--marker-image': buildMarkerImage(markerPalette[sentiment] ?? markerPalette.neutral, isActive),
        '--marker-size': isActive ? '0.92' : '0.8',
    };
};

const isSelectedSymbol = (symbol) => selectedSymbolKeys.value.has(symbol?.symbol_key);

const handlePointClick = (point) => {
    if (!point) {
        return;
    }

    selectedPoint.value = selectedPoint.value?.id === point.id ? null : point;
    hoveredPoint.value = point;
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
                        :href="authUser ? route('dreams.create') : route('login')"
                        class="rounded-md bg-sky-500 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-sky-400"
                    >
                        {{ authUser ? 'Submit Dream' : 'Log In to Submit' }}
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
                                    :style="markerStyle(point)"
                                    :data-location="markerLocation(point)"
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

                            <div v-if="activeInfoPoint" class="globe-tooltip">
                                <p v-if="activeInfoPoint.location_label" class="text-sm font-semibold text-slate-100">
                                    {{ activeInfoPoint.location_label }}
                                </p>
                                <p class="mt-1 text-xs text-slate-300">
                                    {{ pointSummary(activeInfoPoint) }}
                                </p>
                                <div class="globe-tooltip__actions">
                                    <p class="text-[11px] uppercase tracking-[0.16em] text-slate-400">
                                        {{ selectedPoint ? 'Symbols highlighted on the right' : 'Click to inspect symbols' }}
                                    </p>
                                    <Link :href="activeDreamHref" class="globe-tooltip__link">
                                        {{ activeDreamLinkLabel }}
                                    </Link>
                                </div>
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
                            <p v-if="selectedPoint" class="mt-2 text-xs uppercase tracking-[0.14em] text-sky-300">
                                Symbols from the selected dream are highlighted.
                            </p>
                            <div class="mt-3 space-y-2">
                                <div
                                    v-for="symbol in displaySymbols"
                                    :key="symbol.symbol_key"
                                    :class="[
                                        'symbol-row',
                                        { 'symbol-row--active': isSelectedSymbol(symbol) },
                                    ]"
                                >
                                    <div class="min-w-0">
                                        <p class="truncate text-slate-100">{{ symbol.title }}</p>
                                        <p
                                            v-if="isSelectedSymbol(symbol)"
                                            class="mt-1 text-[11px] uppercase tracking-[0.14em] text-sky-300"
                                        >
                                            Selected dream
                                        </p>
                                    </div>
                                    <span class="text-slate-300">
                                        {{ symbol.count ?? (isSelectedSymbol(symbol) ? 'Selected' : '') }}
                                    </span>
                                </div>
                                <p
                                    v-if="selectedPoint && !selectedSymbols.length && displaySymbols.length"
                                    class="text-sm text-slate-300"
                                >
                                    This dream has no linked symbols yet.
                                </p>
                                <p v-if="!displaySymbols.length" class="text-sm text-slate-300">
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

.globe-tooltip__actions {
    margin-top: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
}

.globe-tooltip__link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    border-radius: 9999px;
    background: rgba(56, 189, 248, 0.18);
    padding: 0.4rem 0.75rem;
    font-size: 0.75rem;
    font-weight: 600;
    color: rgb(125 211 252);
    text-decoration: none;
    transition: background-color 140ms ease, color 140ms ease;
}

.globe-tooltip__link:hover {
    background: rgba(56, 189, 248, 0.28);
    color: rgb(224 242 254);
}

.globe-marker {
    display: block;
    width: 0.55rem;
    height: 0.55rem;
    border-radius: 9999px;
    border: 1.5px solid rgba(226, 232, 240, 0.92);
    background: #94a3b8;
    box-shadow: 0 0 0 2px rgba(15, 23, 42, 0.55), 0 0 12px rgba(148, 163, 184, 0.55);
    text-decoration: none;
    pointer-events: auto;
    transition: transform 140ms ease, box-shadow 140ms ease, background-color 140ms ease;
}

.globe-marker:hover {
    transform: scale(1.12);
}

.globe-marker--positive {
    background: #38bdf8;
    box-shadow: 0 0 0 2px rgba(15, 23, 42, 0.55), 0 0 14px rgba(56, 189, 248, 0.72);
}

.globe-marker--neutral {
    background: #94a3b8;
    box-shadow: 0 0 0 2px rgba(15, 23, 42, 0.55), 0 0 12px rgba(148, 163, 184, 0.55);
}

.globe-marker--negative {
    background: #ef4444;
    box-shadow: 0 0 0 2px rgba(15, 23, 42, 0.55), 0 0 14px rgba(239, 68, 68, 0.72);
}

.globe-marker--active {
    transform: scale(1.28);
    box-shadow: 0 0 0 3px rgba(226, 232, 240, 0.92), 0 0 22px rgba(226, 232, 240, 0.38);
}

.symbol-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    border-radius: 0.75rem;
    border: 1px solid rgba(51, 65, 85, 0.72);
    background: rgba(30, 41, 59, 0.6);
    padding: 0.7rem 0.9rem;
    font-size: 0.875rem;
    transition: border-color 140ms ease, background-color 140ms ease, box-shadow 140ms ease;
}

.symbol-row--active {
    border-color: rgba(56, 189, 248, 0.72);
    background: rgba(14, 165, 233, 0.12);
    box-shadow: inset 0 0 0 1px rgba(56, 189, 248, 0.2);
}

@media (max-width: 640px) {
    .globe-tooltip__actions {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>
