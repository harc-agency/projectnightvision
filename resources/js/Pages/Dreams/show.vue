<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
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
const isRegeneratingAssets = ref(false);

const page = usePage();

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
    return !props.dream?.analysis || !(props.dream?.symbols?.length > 0);
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

const regenerateAssets = () => {
    if (!isOwner.value || isRegeneratingAssets.value) {
        return;
    }

    isRegeneratingAssets.value = true;

    router.post(
        route('dreams.generate-assets', { dream: props.dream.id }),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                window.setTimeout(() => {
                    router.reload({
                        only: ['dream', 'related'],
                    });
                    isRegeneratingAssets.value = false;
                }, 2500);
            },
        },
    );
};

onMounted(() => {
    window.addEventListener('keydown', onSymbolDrawerKeydown);
});

onBeforeUnmount(() => {
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
                                {{ dream.analysis || 'Analysis is not available yet.' }}
                            </p>

                            <button
                                v-if="isOwner && needsAiAssets"
                                type="button"
                                class="mt-4 inline-flex rounded-md border border-slate-600 px-3 py-2 text-xs font-medium text-slate-100 transition hover:bg-slate-800 disabled:opacity-60"
                                :disabled="isRegeneratingAssets"
                                @click="regenerateAssets"
                            >
                                {{ isRegeneratingAssets ? 'Generating Analysis + Symbols...' : 'Regenerate Analysis + Symbols' }}
                            </button>
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
                            </div>
                            <p v-else class="mt-3 text-sm text-slate-300">No symbols linked yet.</p>
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
</style>
