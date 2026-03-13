<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    insights: {
        type: Object,
        default: () => ({}),
    },
});

const summary = computed(() => props.insights?.summary ?? {});
const headline = computed(() => props.insights?.headline ?? {});
const analysisStatus = computed(() => props.insights?.analysis_status ?? {});
const sentiment = computed(() => props.insights?.sentiment ?? {});
const themes = computed(() => (Array.isArray(props.insights?.themes) ? props.insights.themes : []));
const symbols = computed(() => (Array.isArray(props.insights?.symbols) ? props.insights.symbols : []));
const locations = computed(() => props.insights?.locations ?? {});
const related = computed(() => props.insights?.related ?? {});

const hasDreams = computed(() => Boolean(props.insights?.has_dreams));

const summaryCards = computed(() => ([
    {
        label: 'Dream Entries',
        value: summary.value.total_dreams ?? 0,
        hint: `${summary.value.public_dreams ?? 0} public`,
    },
    {
        label: 'Analyzed',
        value: summary.value.analyzed_dreams ?? 0,
        hint: `${summary.value.pending_analysis ?? 0} pending`,
    },
    {
        label: 'Current Streak',
        value: summary.value.current_streak_days ?? 0,
        hint: 'consecutive entry days',
    },
    {
        label: 'Last 30 Days',
        value: summary.value.entries_last_30_days ?? 0,
        hint: `${summary.value.dreams_with_symbols ?? 0} fully symbolized`,
    },
]));

const sentimentBars = computed(() => {
    const trend = Array.isArray(sentiment.value?.trend) ? sentiment.value.trend : [];
    const maxTotal = Math.max(...trend.map((item) => item?.total ?? 0), 1);

    return trend.map((item) => {
        const total = item?.total ?? 0;
        const height = total > 0 ? Math.max((total / maxTotal) * 100, 16) : 10;

        return {
            ...item,
            total,
            height: `${height}%`,
        };
    });
});

const maxThemeCount = computed(() => Math.max(...themes.value.map((item) => item.count ?? 0), 1));
const maxSymbolCount = computed(() => Math.max(...symbols.value.map((item) => item.count ?? 0), 1));

const sentimentDirectionCopy = computed(() => {
    if (headline.value.sentiment_direction_30d === 'up') {
        return 'more positive than the previous 30 days';
    }

    if (headline.value.sentiment_direction_30d === 'down') {
        return 'more negative than the previous 30 days';
    }

    return 'steady versus the previous 30 days';
});

const cadenceHighlights = computed(() => ([
    {
        label: 'Last 7 days',
        value: summary.value.entries_last_7_days ?? 0,
    },
    {
        label: 'Average gap',
        value: `${summary.value.average_gap_days ?? 0} days`,
    },
    {
        label: 'Most active day',
        value: summary.value.most_common_weekday ?? 'Not enough data',
    },
    {
        label: 'Last entry',
        value: formatDate(summary.value.last_entry_at),
    },
]));

const distributionCards = computed(() => ([
    {
        label: 'Positive',
        value: sentiment.value?.distribution_30d?.positive ?? 0,
        tone: 'bg-emerald-500/15 text-emerald-200 border-emerald-400/30',
    },
    {
        label: 'Neutral',
        value: sentiment.value?.distribution_30d?.neutral ?? 0,
        tone: 'bg-sky-500/15 text-sky-200 border-sky-400/30',
    },
    {
        label: 'Negative',
        value: sentiment.value?.distribution_30d?.negative ?? 0,
        tone: 'bg-rose-500/15 text-rose-200 border-rose-400/30',
    },
]));

function formatPercent(value) {
    return `${Math.round((Number(value) || 0) * 100)}%`;
}

function formatDate(value) {
    if (!value) {
        return 'No entries yet';
    }

    let parsed;

    if (/^\d{4}-\d{2}-\d{2}$/.test(value)) {
        const [year, month, day] = value.split('-').map((part) => Number(part));
        parsed = new Date(year, month - 1, day);
    } else {
        parsed = new Date(value);
    }

    if (Number.isNaN(parsed.getTime())) {
        return value;
    }

    return parsed.toLocaleDateString(undefined, {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}

function formatSignedDelta(value) {
    const number = Number(value) || 0;

    if (number > 0) {
        return `+${number}`;
    }

    return `${number}`;
}

function deltaToneClass(value) {
    if ((Number(value) || 0) > 0) {
        return 'border-emerald-400/30 bg-emerald-500/15 text-emerald-200';
    }

    if ((Number(value) || 0) < 0) {
        return 'border-rose-400/30 bg-rose-500/15 text-rose-200';
    }

    return 'border-slate-600 bg-slate-800/80 text-slate-300';
}

function directionToneClass(value) {
    if (value === 'up') {
        return 'border-emerald-400/30 bg-emerald-500/15 text-emerald-200';
    }

    if (value === 'down') {
        return 'border-rose-400/30 bg-rose-500/15 text-rose-200';
    }

    return 'border-slate-600 bg-slate-800/80 text-slate-300';
}

function sentimentClass(value) {
    if (value === 'positive') {
        return 'border-emerald-400/30 bg-emerald-500/15 text-emerald-200';
    }

    if (value === 'negative') {
        return 'border-rose-400/30 bg-rose-500/15 text-rose-200';
    }

    return 'border-sky-400/30 bg-sky-500/15 text-sky-200';
}

function progressWidth(value, max) {
    const safeMax = Math.max(Number(max) || 0, 1);
    const width = ((Number(value) || 0) / safeMax) * 100;

    return `${Math.max(width, value > 0 ? 10 : 0)}%`;
}
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <div class="pnv-shell">
            <div class="pnv-header">
                <div>
                    <p class="pnv-eyebrow">Night Vision</p>
                    <h1 class="pnv-title">Dashboard</h1>
                    <p class="pnv-subtitle">
                        Personalized patterns across your archive, AI analysis coverage, and the signals that are repeating most.
                    </p>
                </div>
                <Link
                    :href="route('dreams.create')"
                    class="rounded-md bg-sky-500 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-sky-400"
                >
                    Submit Dream
                </Link>
            </div>

            <div v-if="!hasDreams" class="pnv-panel overflow-hidden">
                <div class="relative">
                    <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(56,189,248,0.22),transparent_40%),radial-gradient(circle_at_bottom_right,rgba(249,115,22,0.18),transparent_34%)]" />
                    <div class="pnv-panel-body relative">
                        <p class="text-xs uppercase tracking-[0.16em] text-slate-400">Personalized Insights</p>
                        <h2 class="mt-3 text-3xl font-semibold text-slate-100">
                            Start logging dreams to reveal recurring themes, symbols, and mood shifts.
                        </h2>
                        <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-300">
                            Once you have a few entries, this dashboard will surface recurring symbols, trend sentiment over time,
                            spot cadence patterns, and show where AI analysis still needs to catch up.
                        </p>
                        <div class="mt-6 flex flex-wrap gap-3">
                            <Link
                                :href="route('dreams.create')"
                                class="rounded-md bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-white"
                            >
                                Submit your first dream
                            </Link>
                            <Link
                                :href="route('dreams.index')"
                                class="rounded-md border border-slate-700 px-4 py-2 text-sm font-medium text-slate-200 transition hover:bg-slate-800"
                            >
                                Open archive
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <template v-else>
                <div class="grid gap-4 xl:grid-cols-[minmax(0,1.7fr)_minmax(320px,1fr)]">
                    <article class="pnv-panel overflow-hidden">
                        <div class="relative">
                            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(56,189,248,0.18),transparent_42%),radial-gradient(circle_at_80%_20%,rgba(251,146,60,0.14),transparent_30%),linear-gradient(135deg,rgba(15,23,42,0.8),rgba(2,6,23,0.2))]" />
                            <div class="pnv-panel-body relative">
                                <p class="text-xs uppercase tracking-[0.16em] text-slate-400">Personalized Insight</p>
                                <h2 class="mt-3 max-w-4xl text-3xl font-semibold leading-tight text-slate-100">
                                    {{ headline.message }}
                                </h2>
                                <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-300">
                                    Your most recent archive patterns suggest sentiment is
                                    <span class="text-slate-100">{{ sentimentDirectionCopy }}</span>.
                                </p>
                                <div class="mt-6 flex flex-wrap gap-2">
                                    <span
                                        v-if="headline.primary_theme"
                                        class="inline-flex items-center rounded-full border border-slate-600 bg-slate-950/60 px-3 py-1 text-xs font-medium uppercase tracking-[0.12em] text-slate-200"
                                    >
                                        Top theme: {{ headline.primary_theme }}
                                    </span>
                                    <span
                                        v-for="symbol in headline.top_symbols || []"
                                        :key="`headline-symbol-${symbol}`"
                                        class="inline-flex items-center rounded-full border border-slate-600 bg-slate-950/60 px-3 py-1 text-xs font-medium uppercase tracking-[0.12em] text-slate-200"
                                    >
                                        Recurring symbol: {{ symbol }}
                                    </span>
                                    <span
                                        class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-medium uppercase tracking-[0.12em]"
                                        :class="directionToneClass(headline.sentiment_direction_30d)"
                                    >
                                        30-day sentiment: {{ headline.sentiment_direction_30d || 'steady' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </article>

                    <article class="pnv-panel">
                        <div class="pnv-panel-body">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-xs uppercase tracking-[0.16em] text-slate-400">Analysis Status</p>
                                    <h2 class="mt-2 text-2xl font-semibold text-slate-100">Pipeline Coverage</h2>
                                </div>
                                <span class="rounded-full border border-slate-700 bg-slate-950/70 px-3 py-1 text-xs uppercase tracking-[0.14em] text-slate-300">
                                    {{ formatPercent(analysisStatus.analysis_coverage_ratio) }} analyzed
                                </span>
                            </div>

                            <div class="mt-6 space-y-5">
                                <div>
                                    <div class="mb-2 flex items-center justify-between gap-4 text-sm">
                                        <span class="text-slate-200">Theme + sentiment analysis</span>
                                        <span class="text-slate-400">
                                            {{ analysisStatus.analyzed_dreams ?? 0 }}/{{ summary.total_dreams ?? 0 }}
                                        </span>
                                    </div>
                                    <div class="h-2 rounded-full bg-slate-800">
                                        <div
                                            class="h-2 rounded-full bg-gradient-to-r from-sky-400 via-cyan-300 to-emerald-300"
                                            :style="{ width: formatPercent(analysisStatus.analysis_coverage_ratio) }"
                                        />
                                    </div>
                                </div>

                                <div>
                                    <div class="mb-2 flex items-center justify-between gap-4 text-sm">
                                        <span class="text-slate-200">Symbol coverage</span>
                                        <span class="text-slate-400">
                                            {{ analysisStatus.dreams_with_symbols ?? 0 }}/{{ summary.total_dreams ?? 0 }}
                                        </span>
                                    </div>
                                    <div class="h-2 rounded-full bg-slate-800">
                                        <div
                                            class="h-2 rounded-full bg-gradient-to-r from-orange-300 via-amber-300 to-yellow-200"
                                            :style="{ width: formatPercent(analysisStatus.symbol_coverage_ratio) }"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6 grid gap-3 sm:grid-cols-2">
                                <div class="rounded-lg border border-slate-700/80 bg-slate-950/60 p-4">
                                    <p class="text-xs uppercase tracking-[0.14em] text-slate-400">Pending Analysis</p>
                                    <p class="mt-2 text-2xl font-semibold text-slate-100">
                                        {{ analysisStatus.pending_analysis ?? 0 }}
                                    </p>
                                </div>
                                <div class="rounded-lg border border-slate-700/80 bg-slate-950/60 p-4">
                                    <p class="text-xs uppercase tracking-[0.14em] text-slate-400">Pending Symbol Links</p>
                                    <p class="mt-2 text-2xl font-semibold text-slate-100">
                                        {{ analysisStatus.pending_symbol_links ?? 0 }}
                                    </p>
                                </div>
                            </div>

                            <p class="mt-5 text-sm text-slate-300">
                                Latest analyzed entry:
                                <span v-if="analysisStatus.latest_analyzed_title" class="text-slate-100">
                                    {{ analysisStatus.latest_analyzed_title }}
                                </span>
                                <span v-else class="text-slate-100">No analyzed entries yet</span>
                                <span v-if="analysisStatus.latest_analyzed_entry_at" class="text-slate-400">
                                    · {{ formatDate(analysisStatus.latest_analyzed_entry_at) }}
                                </span>
                            </p>
                        </div>
                    </article>
                </div>

                <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <article
                        v-for="card in summaryCards"
                        :key="card.label"
                        class="pnv-panel transition hover:border-slate-600 hover:bg-slate-900/85"
                    >
                        <div class="pnv-panel-body">
                            <p class="text-xs uppercase tracking-[0.16em] text-slate-400">{{ card.label }}</p>
                            <p class="mt-3 text-4xl font-semibold text-slate-100">{{ card.value }}</p>
                            <p class="mt-2 text-sm text-slate-300">{{ card.hint }}</p>
                        </div>
                    </article>
                </div>

                <div class="mt-4 grid gap-4 xl:grid-cols-[minmax(0,1.45fr)_minmax(320px,1fr)]">
                    <article class="pnv-panel">
                        <div class="pnv-panel-body">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-xs uppercase tracking-[0.16em] text-slate-400">Sentiment Trend</p>
                                    <h2 class="mt-2 text-2xl font-semibold text-slate-100">Last 8 Weeks</h2>
                                </div>
                                <span class="rounded-full border border-slate-700 bg-slate-950/70 px-3 py-1 text-xs uppercase tracking-[0.14em] text-slate-300">
                                    {{ sentiment.classified_count_30d ?? 0 }} classified in 30 days
                                </span>
                            </div>

                            <div class="mt-5 grid gap-3 sm:grid-cols-3">
                                <div
                                    v-for="card in distributionCards"
                                    :key="card.label"
                                    class="rounded-lg border px-4 py-3"
                                    :class="card.tone"
                                >
                                    <p class="text-xs uppercase tracking-[0.14em]">{{ card.label }}</p>
                                    <p class="mt-2 text-2xl font-semibold">{{ card.value }}</p>
                                </div>
                            </div>

                            <div class="mt-6 rounded-xl border border-slate-700/80 bg-slate-950/70 p-4">
                                <div class="flex h-64 items-end gap-3">
                                    <div
                                        v-for="bar in sentimentBars"
                                        :key="bar.period_start"
                                        class="flex min-w-0 flex-1 flex-col items-center gap-2"
                                    >
                                        <span class="text-xs text-slate-500">{{ bar.total }}</span>
                                        <div
                                            class="flex w-full items-end justify-center rounded-lg border border-slate-700/70 bg-slate-900/80 px-1 py-1"
                                            style="height: 220px;"
                                        >
                                            <div
                                                class="flex w-full flex-col justify-end overflow-hidden rounded-md bg-slate-800/90"
                                                :style="{ height: bar.height }"
                                            >
                                                <div
                                                    class="w-full bg-emerald-400/90"
                                                    :style="{ height: `${bar.total ? (bar.positive / bar.total) * 100 : 0}%` }"
                                                />
                                                <div
                                                    class="w-full bg-sky-400/90"
                                                    :style="{ height: `${bar.total ? (bar.neutral / bar.total) * 100 : 0}%` }"
                                                />
                                                <div
                                                    class="w-full bg-rose-400/90"
                                                    :style="{ height: `${bar.total ? (bar.negative / bar.total) * 100 : 0}%` }"
                                                />
                                            </div>
                                        </div>
                                        <span class="text-xs text-slate-500">{{ bar.label }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>

                    <article class="pnv-panel">
                        <div class="pnv-panel-body">
                            <p class="text-xs uppercase tracking-[0.16em] text-slate-400">Recurring Themes</p>
                            <h2 class="mt-2 text-2xl font-semibold text-slate-100">What Keeps Returning</h2>

                            <div v-if="themes.length" class="mt-6 space-y-5">
                                <div
                                    v-for="theme in themes"
                                    :key="theme.name"
                                    class="rounded-lg border border-slate-700/70 bg-slate-950/60 p-4"
                                >
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <h3 class="text-lg font-semibold text-slate-100">{{ theme.name }}</h3>
                                            <p class="mt-1 text-sm text-slate-400">
                                                {{ theme.count }} dreams · {{ formatPercent(theme.share) }} of analyzed entries
                                            </p>
                                        </div>
                                        <span
                                            class="inline-flex rounded-full border px-2.5 py-1 text-xs font-medium"
                                            :class="deltaToneClass(theme.delta_vs_prev_30d)"
                                        >
                                            {{ formatSignedDelta(theme.delta_vs_prev_30d) }} vs prior 30d
                                        </span>
                                    </div>

                                    <div class="mt-4 h-2 rounded-full bg-slate-800">
                                        <div
                                            class="h-2 rounded-full bg-gradient-to-r from-sky-400 via-cyan-300 to-emerald-300"
                                            :style="{ width: progressWidth(theme.count, maxThemeCount) }"
                                        />
                                    </div>

                                    <p class="mt-3 text-xs uppercase tracking-[0.14em] text-slate-500">
                                        Last seen {{ formatDate(theme.last_seen_at) }}
                                    </p>
                                </div>
                            </div>

                            <p v-else class="mt-6 text-sm text-slate-300">
                                Themes will appear here once more entries have been analyzed.
                            </p>
                        </div>
                    </article>
                </div>

                <div class="mt-4 grid gap-4 lg:grid-cols-2 xl:grid-cols-3">
                    <article class="pnv-panel">
                        <div class="pnv-panel-body">
                            <p class="text-xs uppercase tracking-[0.16em] text-slate-400">Recurring Symbols</p>
                            <h2 class="mt-2 text-2xl font-semibold text-slate-100">Most Persistent Motifs</h2>

                            <div v-if="symbols.length" class="mt-6 space-y-4">
                                <div
                                    v-for="symbol in symbols"
                                    :key="symbol.symbol_key"
                                    class="rounded-lg border border-slate-700/70 bg-slate-950/60 p-4"
                                >
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="min-w-0">
                                            <h3 class="truncate text-lg font-semibold text-slate-100">
                                                <Link
                                                    :href="route('symbols.show', { symbol: symbol.symbol_key })"
                                                    class="hover:text-sky-200"
                                                >
                                                    {{ symbol.title }}
                                                </Link>
                                            </h3>
                                            <p class="mt-1 text-sm text-slate-400">
                                                {{ symbol.count }} dreams · {{ formatPercent(symbol.share) }} of your archive
                                            </p>
                                        </div>
                                        <div class="flex flex-wrap justify-end gap-2">
                                            <span
                                                v-if="symbol.is_new"
                                                class="rounded-full border border-amber-400/30 bg-amber-500/15 px-2.5 py-1 text-xs font-medium text-amber-100"
                                            >
                                                new
                                            </span>
                                            <span
                                                class="rounded-full border px-2.5 py-1 text-xs font-medium"
                                                :class="directionToneClass(symbol.trend)"
                                            >
                                                {{ symbol.trend }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="mt-4 h-2 rounded-full bg-slate-800">
                                        <div
                                            class="h-2 rounded-full bg-gradient-to-r from-orange-300 via-amber-300 to-yellow-200"
                                            :style="{ width: progressWidth(symbol.count, maxSymbolCount) }"
                                        />
                                    </div>

                                    <p class="mt-3 text-xs uppercase tracking-[0.14em] text-slate-500">
                                        Last seen {{ formatDate(symbol.last_seen_at) }}
                                    </p>
                                </div>
                            </div>

                            <p v-else class="mt-6 text-sm text-slate-300">
                                Symbols will populate here as AI linking completes.
                            </p>
                        </div>
                    </article>

                    <article class="pnv-panel">
                        <div class="pnv-panel-body">
                            <p class="text-xs uppercase tracking-[0.16em] text-slate-400">Cadence</p>
                            <h2 class="mt-2 text-2xl font-semibold text-slate-100">How You Journal</h2>

                            <div class="mt-6 grid gap-3 sm:grid-cols-2">
                                <div
                                    v-for="item in cadenceHighlights"
                                    :key="item.label"
                                    class="rounded-lg border border-slate-700/80 bg-slate-950/60 p-4"
                                >
                                    <p class="text-xs uppercase tracking-[0.14em] text-slate-400">{{ item.label }}</p>
                                    <p class="mt-2 text-xl font-semibold text-slate-100">{{ item.value }}</p>
                                </div>
                            </div>

                            <div class="mt-6 rounded-lg border border-slate-700/80 bg-slate-950/60 p-4">
                                <p class="text-sm leading-6 text-slate-300">
                                    You logged
                                    <span class="text-slate-100">{{ summary.entries_last_30_days ?? 0 }}</span>
                                    dream{{ (summary.entries_last_30_days ?? 0) === 1 ? '' : 's' }} in the last 30 days, with
                                    a current streak of
                                    <span class="text-slate-100">{{ summary.current_streak_days ?? 0 }}</span>
                                    consecutive day{{ (summary.current_streak_days ?? 0) === 1 ? '' : 's' }}.
                                </p>
                            </div>
                        </div>
                    </article>

                    <article v-if="locations.show_card" class="pnv-panel">
                        <div class="pnv-panel-body">
                            <p class="text-xs uppercase tracking-[0.16em] text-slate-400">Location Patterns</p>
                            <h2 class="mt-2 text-2xl font-semibold text-slate-100">Where Dreams Cluster</h2>

                            <div class="mt-6 grid gap-3 sm:grid-cols-2">
                                <div class="rounded-lg border border-slate-700/80 bg-slate-950/60 p-4">
                                    <p class="text-xs uppercase tracking-[0.14em] text-slate-400">Tagged entries</p>
                                    <p class="mt-2 text-2xl font-semibold text-slate-100">{{ locations.tagged_count ?? 0 }}</p>
                                </div>
                                <div class="rounded-lg border border-slate-700/80 bg-slate-950/60 p-4">
                                    <p class="text-xs uppercase tracking-[0.14em] text-slate-400">Coverage rate</p>
                                    <p class="mt-2 text-2xl font-semibold text-slate-100">{{ formatPercent(locations.tagged_rate) }}</p>
                                </div>
                            </div>

                            <div class="mt-6 space-y-4">
                                <div
                                    v-for="location in locations.top || []"
                                    :key="location.label"
                                    class="rounded-lg border border-slate-700/70 bg-slate-950/60 p-4"
                                >
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="min-w-0">
                                            <h3 class="truncate text-lg font-semibold text-slate-100">{{ location.label }}</h3>
                                            <p class="mt-1 text-sm text-slate-400">
                                                {{ location.count }} tagged dream{{ location.count === 1 ? '' : 's' }}
                                            </p>
                                        </div>
                                        <span class="rounded-full border border-slate-600 bg-slate-900 px-2.5 py-1 text-xs text-slate-300">
                                            {{ formatDate(location.last_seen_at) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>

                <article class="pnv-panel mt-4">
                    <div class="pnv-panel-body">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs uppercase tracking-[0.16em] text-slate-400">Related Patterns</p>
                                <h2 class="mt-2 text-2xl font-semibold text-slate-100">Dreams To Revisit</h2>
                            </div>
                            <span class="rounded-full border border-slate-700 bg-slate-950/70 px-3 py-1 text-xs uppercase tracking-[0.14em] text-slate-300">
                                Based on {{ related.basis_title || 'your latest dream' }}
                            </span>
                        </div>

                        <div v-if="(related.revisit || []).length" class="mt-6 grid gap-4 md:grid-cols-3">
                            <article
                                v-for="dream in related.revisit || []"
                                :key="dream.id"
                                class="rounded-xl border border-slate-700/70 bg-slate-950/60 p-5 transition hover:border-slate-600 hover:bg-slate-900/80"
                            >
                                <div class="flex items-start justify-between gap-4">
                                    <div class="min-w-0">
                                        <p class="text-xs uppercase tracking-[0.14em] text-slate-400">
                                            {{ formatDate(dream.dream_date) }}
                                        </p>
                                        <h3 class="mt-2 truncate text-xl font-semibold text-slate-100">
                                            <Link
                                                :href="route('dreams.show', { dream: dream.id })"
                                                class="hover:text-sky-200"
                                            >
                                                {{ dream.title }}
                                            </Link>
                                        </h3>
                                    </div>
                                    <span
                                        class="rounded-full border px-2.5 py-1 text-xs font-medium capitalize"
                                        :class="sentimentClass(dream.sentiment)"
                                    >
                                        {{ dream.sentiment }}
                                    </span>
                                </div>

                                <p class="mt-4 text-sm leading-6 text-slate-300">
                                    {{ dream.match_summary }}
                                </p>
                            </article>
                        </div>

                        <p v-else class="mt-6 text-sm text-slate-300">
                            Related recommendations will appear once the archive has more overlapping themes and symbols.
                        </p>
                    </div>
                </article>
            </template>
        </div>
    </AuthenticatedLayout>
</template>
