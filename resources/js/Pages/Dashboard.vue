<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineProps({
    dreamsCount: {
        type: Number,
        default: 0,
    },
});
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
                        Quick overview of your dream activity and analysis pipeline.
                    </p>
                </div>
                <Link
                    :href="route('dreams.create')"
                    class="rounded-md bg-sky-500 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-sky-400"
                >
                    Submit Dream
                </Link>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <article class="pnv-panel">
                    <div class="pnv-panel-body">
                        <p class="text-xs uppercase tracking-[0.16em] text-slate-400">Dream Entries</p>
                        <p class="mt-3 text-4xl font-semibold text-slate-100">{{ dreamsCount }}</p>
                    </div>
                </article>

                <article class="pnv-panel md:col-span-2">
                    <div class="pnv-panel-body">
                        <h2 class="text-xl font-semibold text-slate-100">Dream Insights</h2>
                        <p class="mt-2 text-sm text-slate-300">
                            Your personalized insight visualizations will appear here as your dataset grows.
                        </p>
                    </div>
                </article>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
