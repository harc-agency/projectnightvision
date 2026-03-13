<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    dream: {
        type: Object,
        required: true,
    },
    related: {
        type: Object,
        default: () => ({
            own: [],
            public: [],
        }),
    },
});

const activeSymbol = ref(null);
const isSymbolDrawerOpen = ref(false);
const isUpdatingVisibility = ref(false);
const isPollingAiAssets = ref(false);
const isReloadingAiAssets = ref(false);
const aiAssetPollTimer = ref(null);
const aiAssetPollAttempts = ref(0);

const page = usePage();
const AI_ASSET_POLL_INITIAL_DELAY_MS = 1200;
const AI_ASSET_POLL_INTERVAL_MS = 2500;
const AI_ASSET_POLL_MAX_ATTEMPTS = 24;

const symbolLookup = computed(() => {
    const lookup = {};

    (props.dream?.symbols || []).forEach((symbol) => {
        if (symbol?.symbol_key) {
            lookup[symbol.symbol_key] = symbol;
        }
    });

    return lookup;
});

const escapeHtml = (value) => {
    return value
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
};

const escapeRegExp = (value) => value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

const linkedDreamContent = computed(() => {
    const raw = props.dream?.dream_content || '';

    if (!raw) {
        return 'No dream content available.';
    }

    const symbols = [...(props.dream?.symbols || [])]
        .filter((symbol) => symbol?.title && symbol?.symbol_key)
        .sort(
            (a, b) => (b.title?.length || 0) - (a.title?.length || 0),
        );

    if (symbols.length === 0) {
        return escapeHtml(raw).replace(/\n/g, '<br />');
    }

    const symbolByLowerTitle = new Map(
        symbols.map((symbol) => [symbol.title.toLowerCase(), symbol]),
    );
    const pattern = symbols.map((symbol) => escapeRegExp(symbol.title)).join('|');
    const regex = new RegExp(`\\b(${pattern})\\b`, 'gi');

    const html = escapeHtml(raw).replace(regex, (matched) => {
        const symbol = symbolByLowerTitle.get(matched.toLowerCase());

        if (!symbol) {
            return matched;
        }

        const symbolKey = encodeURIComponent(symbol.symbol_key);

        return `<a href="/symbols/${symbolKey}" data-symbol-key="${symbol.symbol_key}" class="text-sky-300 underline underline-offset-2">${matched}</a>`;
    });

    return html.replace(/\n/g, '<br />');
});

const formattedDate = computed(() => {
    const source = props.dream?.dream_date || props.dream?.created_at;
    if (!source) {
        return 'Unknown date';
    }

    const parsed = new Date(source);
    if (Number.isNaN(parsed.getTime())) {
        return source;
    }

    return parsed.toLocaleDateString();
});

const formattedLocation = computed(() => {
    const manualLocation = props.dream?.dream_location;

    if (typeof manualLocation === 'string' && manualLocation.trim() !== '') {
        return manualLocation.trim();
    }

    const location = props.dream?.location;

    if (!location || typeof location !== 'object') {
        return 'Unknown';
    }

    const label = location.label || location.name;

    if (typeof label === 'string' && label.trim() !== '') {
        return label.trim();
    }

    const lat = Number(location.lat ?? location.latitude);
    const lng = Number(location.lng ?? location.lon ?? location.longitude);

    if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
        return 'Unknown';
    }

    return `${lat.toFixed(4)}, ${lng.toFixed(4)}`;
});

const relatedOwn = computed(() => {
    return Array.isArray(props.related?.own) ? props.related.own : [];
});

const relatedPublic = computed(() => {
    return Array.isArray(props.related?.public) ? props.related.public : [];
});

const isOwner = computed(() => {
    const currentUserId = page.props.auth?.user?.id;

    if (!currentUserId) {
        return false;
    }

    return Number(currentUserId) === Number(props.dream?.user_id);
});

const needsAiAssets = computed(() => {
    if (typeof props.dream?.ai_assets_pending === 'boolean') {
        return props.dream.ai_assets_pending;
    }

    return !props.dream?.analysis || !(props.dream?.symbols?.length > 0);
});

const shouldPollAiAssets = computed(() => {
    return isOwner.value && needsAiAssets.value;
});

const formatRelatedDate = (value) => {
    if (!value) {
        return 'Unknown date';
    }

    const parsed = new Date(value);
    if (Number.isNaN(parsed.getTime())) {
        return value;
    }

    return parsed.toLocaleDateString();
};

const sentimentClasses = computed(() => {
    if (props.dream.sentiment === 'positive') {
        return 'bg-emerald-500/20 text-emerald-200 border-emerald-400/40';
    }
    if (props.dream.sentiment === 'negative') {
        return 'bg-rose-500/20 text-rose-200 border-rose-400/40';
    }
    if (props.dream.sentiment === 'neutral') {
        return 'bg-sky-500/20 text-sky-200 border-sky-400/40';
    }

    return 'bg-slate-700/60 text-slate-200 border-slate-500/40';
});

const clearAiAssetPollTimer = () => {
    if (aiAssetPollTimer.value === null) {
        return;
    }

    window.clearTimeout(aiAssetPollTimer.value);
    aiAssetPollTimer.value = null;
};

const stopAiAssetPolling = (resetAttempts = false) => {
    clearAiAssetPollTimer();
    isPollingAiAssets.value = false;
    isReloadingAiAssets.value = false;

    if (resetAttempts) {
        aiAssetPollAttempts.value = 0;
    }
};

const beginAiAssetPolling = (
    delay = AI_ASSET_POLL_INITIAL_DELAY_MS,
    resetAttempts = false,
) => {
    if (!shouldPollAiAssets.value) {
        stopAiAssetPolling(resetAttempts);
        return;
    }

    if (resetAttempts) {
        aiAssetPollAttempts.value = 0;
    }

    if (aiAssetPollAttempts.value >= AI_ASSET_POLL_MAX_ATTEMPTS) {
        clearAiAssetPollTimer();
        isPollingAiAssets.value = false;
        return;
    }

    clearAiAssetPollTimer();
    isPollingAiAssets.value = true;

    aiAssetPollTimer.value = window.setTimeout(() => {
        if (!shouldPollAiAssets.value || isReloadingAiAssets.value) {
            return;
        }

        isReloadingAiAssets.value = true;
        aiAssetPollAttempts.value += 1;

        router.reload({
            only: ['dream', 'related'],
            preserveScroll: true,
            preserveState: true,
            onFinish: () => {
                isReloadingAiAssets.value = false;

                if (!shouldPollAiAssets.value) {
                    stopAiAssetPolling(true);
                    return;
                }

                if (aiAssetPollAttempts.value >= AI_ASSET_POLL_MAX_ATTEMPTS) {
                    clearAiAssetPollTimer();
                    isPollingAiAssets.value = false;
                    return;
                }

                beginAiAssetPolling(AI_ASSET_POLL_INTERVAL_MS);
            },
        });
    }, delay);
};

const openSymbolDrawer = (symbolKey) => {
    const symbol = symbolLookup.value[symbolKey];

    if (!symbol) {
        return;
    }

    activeSymbol.value = symbol;
    isSymbolDrawerOpen.value = true;
};

const closeSymbolDrawer = () => {
    isSymbolDrawerOpen.value = false;
};

const onDreamContentClick = (event) => {
    const symbolLink = event.target.closest('a[data-symbol-key]');

    if (!symbolLink) {
        return;
    }

    event.preventDefault();
    openSymbolDrawer(symbolLink.dataset.symbolKey);
};

const onSymbolDrawerKeydown = (event) => {
    if (event.key === 'Escape') {
        closeSymbolDrawer();
    }
};

const toggleVisibility = () => {
    if (!isOwner.value || isUpdatingVisibility.value) {
        return;
    }

    isUpdatingVisibility.value = true;

    router.patch(
        route('dreams.visibility', { dream: props.dream.id }),
        {
            is_public: !props.dream.is_public,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                isUpdatingVisibility.value = false;
            },
        },
    );
};

watch(
    shouldPollAiAssets,
    (value) => {
        if (!value) {
            stopAiAssetPolling(true);
            return;
        }

        if (isPollingAiAssets.value || isReloadingAiAssets.value) {
            return;
        }

        beginAiAssetPolling(AI_ASSET_POLL_INITIAL_DELAY_MS, true);
    },
    { immediate: true },
);

onMounted(() => {
    window.addEventListener('keydown', onSymbolDrawerKeydown);
});

onBeforeUnmount(() => {
    stopAiAssetPolling();
    window.removeEventListener('keydown', onSymbolDrawerKeydown);
});
</script>

<template>
    <Head :title="dream.title || 'Dream Detail'" />

    <AuthenticatedLayout>
        <div class="pnv-shell">
            <div class="pnv-header">
                <div>
                    <p class="pnv-eyebrow">Dream Detail</p>
                    <h1 class="pnv-title">{{ dream.title || 'Untitled Dream' }}</h1>
                    <p class="pnv-subtitle">Captured on {{ formattedDate }}</p>
                </div>
                <Link
                    :href="route('dreams.index')"
                    class="rounded-md border border-slate-700 px-4 py-2 text-sm font-medium text-slate-200 transition hover:bg-slate-800"
                >
                    Back to Dreams
                </Link>
            </div>

            <div class="grid gap-4 lg:grid-cols-3">
                <div class="space-y-4 lg:col-span-2">
                    <section class="pnv-panel">
                        <div class="pnv-panel-body">
                            <h2 class="text-2xl font-semibold text-slate-100">Dream Content</h2>
                            <div
                                class="dream-content mt-3 text-sm leading-7 text-slate-200"
                                v-html="linkedDreamContent"
                                @click="onDreamContentClick"
                            />
                        </div>
                    </section>

                    <section class="pnv-panel">
                        <div class="pnv-panel-body">
                            <div class="mb-3 flex items-center justify-between gap-3">
                                <h2 class="text-2xl font-semibold text-slate-100">Analysis</h2>
                                <span
                                    :class="[
                                        'inline-flex rounded-md border px-2 py-1 text-xs font-medium',
                                        sentimentClasses,
                                    ]"
                                >
                                    {{ dream.sentiment || 'unclassified' }}
                                </span>
                            </div>
                            <p class="text-sm leading-7 text-slate-200">
                                {{ dream.analysis || (isPollingAiAssets ? 'Generating analysis. This page will refresh automatically.' : 'Analysis is not available yet.') }}
                            </p>

                        </div>
                    </section>

                    <section v-if="dream.dream_audio_url" class="pnv-panel">
                        <div class="pnv-panel-body">
                            <h2 class="text-2xl font-semibold text-slate-100">Submitted Audio</h2>
                            <p class="mt-2 text-sm text-slate-300">
                                Replay the original recording tied to this dream entry.
                            </p>
                            <audio
                                :src="dream.dream_audio_url"
                                controls
                                preload="metadata"
                                class="mt-4 w-full"
                            />
                        </div>
                    </section>

                    <section class="pnv-panel">
                        <div class="pnv-panel-body">
                            <h2 class="text-2xl font-semibold text-slate-100">Related Dreams</h2>
                            <div class="mt-4 grid gap-4 xl:grid-cols-2">
                                <div>
                                    <h3 class="text-sm font-semibold uppercase tracking-[0.14em] text-slate-400">
                                        Yours
                                    </h3>
                                    <div v-if="relatedOwn.length" class="mt-3 space-y-3">
                                        <article
                                            v-for="item in relatedOwn"
                                            :key="`own-${item.id}`"
                                            class="rounded-md border border-slate-700/70 bg-slate-800/60 p-3"
                                        >
                                            <Link
                                                :href="route('dreams.show', { dream: item.id })"
                                                class="text-sm font-semibold text-sky-200 hover:text-sky-100"
                                            >
                                                {{ item.title || 'Untitled Dream' }}
                                            </Link>
                                            <p class="mt-1 text-xs leading-5 text-slate-300">{{ item.match_summary }}</p>
                                            <p class="mt-1 text-xs text-slate-400">
                                                {{ formatRelatedDate(item.dream_date) }}
                                            </p>
                                        </article>
                                    </div>
                                    <p v-else class="mt-3 text-sm text-slate-300">No related dreams yet.</p>
                                </div>

                                <div>
                                    <h3 class="text-sm font-semibold uppercase tracking-[0.14em] text-slate-400">
                                        Public
                                    </h3>
                                    <div v-if="relatedPublic.length" class="mt-3 space-y-3">
                                        <article
                                            v-for="item in relatedPublic"
                                            :key="`public-${item.id}`"
                                            class="rounded-md border border-slate-700/70 bg-slate-800/60 p-3"
                                        >
                                            <Link
                                                :href="route('dreams.show', { dream: item.id })"
                                                class="text-sm font-semibold text-sky-200 hover:text-sky-100"
                                            >
                                                {{ item.title || 'Untitled Dream' }}
                                            </Link>
                                            <p class="mt-1 text-xs leading-5 text-slate-300">{{ item.match_summary }}</p>
                                            <p class="mt-1 text-xs text-slate-400">
                                                {{ formatRelatedDate(item.dream_date) }}
                                            </p>
                                        </article>
                                    </div>
                                    <p v-else class="mt-3 text-sm text-slate-300">No related public dreams yet.</p>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                <aside class="space-y-4">
                    <section class="pnv-panel">
                        <div class="pnv-panel-body">
                            <h3 class="text-xl font-semibold text-slate-100">Symbols</h3>
                            <div v-if="dream.symbols?.length" class="mt-3 space-y-3">
                                <article
                                    v-for="symbol in dream.symbols"
                                    :key="symbol.id"
                                    class="rounded-md border border-slate-700/70 bg-slate-800/70 p-3"
                                >
                                    <img
                                        v-if="symbol.featured_image"
                                        :src="symbol.featured_image"
                                        :alt="symbol.title"
                                        class="mb-3 h-24 w-full rounded-md object-cover"
                                    />
                                    <button
                                        type="button"
                                        class="text-left text-sm font-semibold text-sky-200 hover:text-sky-100"
                                        @click="openSymbolDrawer(symbol.symbol_key)"
                                    >
                                        {{ symbol.title }}
                                    </button>
                                    <p class="mt-1 text-xs leading-6 text-slate-300">
                                        {{ symbol.description }}
                                    </p>
                                </article>
                                <p v-if="isPollingAiAssets" class="flex items-center gap-2 text-sm text-slate-300">
                                    <span class="symbol-loader__dot" aria-hidden="true" />
                                    Generating remaining symbol assets. This panel will refresh automatically.
                                </p>
                            </div>
                            <div v-else-if="isPollingAiAssets" class="mt-3 space-y-3">
                                <div class="symbol-loader rounded-md border border-slate-700/70 bg-slate-800/55 p-3">
                                    <div class="symbol-loader__row">
                                        <span class="symbol-loader__thumb" aria-hidden="true" />
                                        <div class="min-w-0 flex-1 space-y-2">
                                            <span class="symbol-loader__line symbol-loader__line--title" aria-hidden="true" />
                                            <span class="symbol-loader__line symbol-loader__line--body" aria-hidden="true" />
                                            <span class="symbol-loader__line symbol-loader__line--body-short" aria-hidden="true" />
                                        </div>
                                    </div>
                                </div>
                                <div class="symbol-loader rounded-md border border-slate-700/70 bg-slate-800/45 p-3">
                                    <div class="symbol-loader__row">
                                        <span class="symbol-loader__thumb" aria-hidden="true" />
                                        <div class="min-w-0 flex-1 space-y-2">
                                            <span class="symbol-loader__line symbol-loader__line--title" aria-hidden="true" />
                                            <span class="symbol-loader__line symbol-loader__line--body-short" aria-hidden="true" />
                                        </div>
                                    </div>
                                </div>
                                <p class="flex items-center gap-2 text-sm text-slate-300">
                                    <span class="symbol-loader__dot" aria-hidden="true" />
                                    Generating symbols. This panel will refresh automatically.
                                </p>
                            </div>
                            <p v-else class="mt-3 text-sm text-slate-300">
                                No symbols linked yet.
                            </p>
                        </div>
                    </section>

                    <section class="pnv-panel">
                        <div class="pnv-panel-body">
                            <h3 class="text-xl font-semibold text-slate-100">Metadata</h3>
                            <dl class="mt-3 space-y-2 text-sm text-slate-300">
                                <div class="flex items-center justify-between gap-3">
                                    <dt>Theme</dt>
                                    <dd class="text-slate-200">{{ dream.overall_theme || 'Unknown' }}</dd>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <dt>Sentiment</dt>
                                    <dd class="text-slate-200">{{ dream.sentiment || 'Unknown' }}</dd>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <dt>Public</dt>
                                    <dd class="text-slate-200">{{ dream.is_public ? 'Yes' : 'No' }}</dd>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <dt>Location</dt>
                                    <dd class="text-right text-slate-200">{{ formattedLocation }}</dd>
                                </div>
                            </dl>

                            <button
                                v-if="isOwner"
                                type="button"
                                class="mt-4 inline-flex rounded-md border border-slate-600 px-3 py-2 text-xs font-medium text-slate-100 transition hover:bg-slate-800 disabled:opacity-60"
                                :disabled="isUpdatingVisibility"
                                @click="toggleVisibility"
                            >
                                {{ isUpdatingVisibility
                                    ? 'Updating...'
                                    : dream.is_public
                                      ? 'Make Private'
                                      : 'Share Publicly' }}
                            </button>
                        </div>
                    </section>
                </aside>
            </div>
        </div>

        <transition name="drawer-fade">
            <div v-if="isSymbolDrawerOpen && activeSymbol" class="fixed inset-0 z-50">
                <button
                    type="button"
                    class="absolute inset-0 bg-slate-950/75"
                    aria-label="Close symbol drawer"
                    @click="closeSymbolDrawer"
                />

                <aside class="absolute right-0 top-0 h-full w-full max-w-md border-l border-slate-700 bg-slate-900 shadow-2xl">
                    <div class="flex h-full flex-col">
                        <div class="border-b border-slate-700/70 p-5">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="pnv-eyebrow">Symbol Detail</p>
                                    <h3 class="mt-2 text-2xl font-semibold text-slate-100">{{ activeSymbol.title }}</h3>
                                </div>
                                <button
                                    type="button"
                                    class="rounded-md border border-slate-700 px-3 py-1.5 text-xs font-medium text-slate-200 hover:bg-slate-800"
                                    @click="closeSymbolDrawer"
                                >
                                    Close
                                </button>
                            </div>
                        </div>

                        <div class="flex-1 overflow-y-auto p-5">
                            <img
                                v-if="activeSymbol.featured_image"
                                :src="activeSymbol.featured_image"
                                :alt="activeSymbol.title"
                                class="mb-4 h-48 w-full rounded-md object-cover"
                            />
                            <p class="text-sm leading-7 text-slate-200">
                                {{ activeSymbol.description || 'No description available yet.' }}
                            </p>

                            <Link
                                :href="route('symbols.show', { symbol: activeSymbol.symbol_key })"
                                class="mt-4 inline-flex rounded-md border border-slate-600 px-3 py-2 text-sm font-medium text-sky-200 hover:bg-slate-800"
                            >
                                Open Full Symbol Page
                            </Link>
                        </div>
                    </div>
                </aside>
            </div>
        </transition>
    </AuthenticatedLayout>
</template>

<style scoped>
.dream-content :deep(a:hover) {
    color: #bae6fd;
}

.drawer-fade-enter-active,
.drawer-fade-leave-active {
    transition: opacity 0.2s ease;
}

.drawer-fade-enter-from,
.drawer-fade-leave-to {
    opacity: 0;
}

.symbol-loader {
    position: relative;
    overflow: hidden;
}

.symbol-loader::after {
    content: '';
    position: absolute;
    inset: 0;
    transform: translateX(-100%);
    background: linear-gradient(
        90deg,
        transparent 0%,
        rgba(186, 230, 253, 0.05) 35%,
        rgba(186, 230, 253, 0.18) 50%,
        rgba(186, 230, 253, 0.05) 65%,
        transparent 100%
    );
    animation: symbol-shimmer 1.8s ease-in-out infinite;
}

.symbol-loader__row {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
}

.symbol-loader__thumb,
.symbol-loader__line,
.symbol-loader__dot {
    display: block;
    background: linear-gradient(180deg, rgba(148, 163, 184, 0.32), rgba(148, 163, 184, 0.16));
}

.symbol-loader__thumb {
    height: 4.75rem;
    width: 4.75rem;
    flex-shrink: 0;
    border-radius: 0.5rem;
}

.symbol-loader__line {
    height: 0.75rem;
    width: 100%;
    border-radius: 9999px;
}

.symbol-loader__line--title {
    width: 58%;
    height: 0.9rem;
}

.symbol-loader__line--body {
    width: 92%;
}

.symbol-loader__line--body-short {
    width: 68%;
}

.symbol-loader__dot {
    height: 0.6rem;
    width: 0.6rem;
    flex-shrink: 0;
    border-radius: 9999px;
    background: #7dd3fc;
    box-shadow: 0 0 0 0 rgba(125, 211, 252, 0.5);
    animation: symbol-pulse 1.4s ease-out infinite;
}

@keyframes symbol-shimmer {
    100% {
        transform: translateX(100%);
    }
}

@keyframes symbol-pulse {
    0% {
        transform: scale(0.95);
        box-shadow: 0 0 0 0 rgba(125, 211, 252, 0.5);
    }

    70% {
        transform: scale(1);
        box-shadow: 0 0 0 10px rgba(125, 211, 252, 0);
    }

    100% {
        transform: scale(0.95);
        box-shadow: 0 0 0 0 rgba(125, 211, 252, 0);
    }
}
</style>
