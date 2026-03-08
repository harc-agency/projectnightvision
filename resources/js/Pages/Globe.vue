<script setup>
import { computed, onBeforeUnmount, onMounted, ref, useTemplateRef } from 'vue';
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

const globeContainer = useTemplateRef('globeContainer');
const globeCanvas = useTemplateRef('globeCanvas');
const hoveredPoint = ref(null);

const safeSentiment = computed(() => ({
    positive: props.stats?.sentiment?.positive ?? 0,
    neutral: props.stats?.sentiment?.neutral ?? 0,
    negative: props.stats?.sentiment?.negative ?? 0,
}));

const totalPublicDreams = computed(() => props.stats?.total_public_dreams ?? 0);
const mappedPointsCount = computed(() => props.points?.length ?? 0);

let ctx = null;
let animationFrame = 0;
let resizeObserver = null;
let rotation = 0;
let projectedPoints = [];

const rgbBySentiment = {
    positive: [34, 197, 94],
    neutral: [56, 189, 248],
    negative: [249, 115, 22],
};

const toRadians = (degrees) => (degrees * Math.PI) / 180;

const latLngToVector = (lat, lng) => {
    const latRad = toRadians(lat);
    const lngRad = toRadians(lng);

    return {
        x: Math.cos(latRad) * Math.cos(lngRad),
        y: Math.sin(latRad),
        z: Math.cos(latRad) * Math.sin(lngRad),
    };
};

const rotateY = (vector, angle) => {
    const cos = Math.cos(angle);
    const sin = Math.sin(angle);

    return {
        x: vector.x * cos + vector.z * sin,
        y: vector.y,
        z: -vector.x * sin + vector.z * cos,
    };
};

const setupCanvasSize = () => {
    const canvas = globeCanvas.value;
    const container = globeContainer.value;
    if (!canvas || !container) {
        return;
    }

    const size = Math.max(280, Math.min(container.clientWidth, 560));
    const dpr = window.devicePixelRatio || 1;

    canvas.width = Math.floor(size * dpr);
    canvas.height = Math.floor(size * dpr);
    canvas.style.width = `${size}px`;
    canvas.style.height = `${size}px`;

    ctx = canvas.getContext('2d');
    if (!ctx) {
        return;
    }
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
};

const drawSphereBase = (centerX, centerY, radius) => {
    if (!ctx) {
        return;
    }

    const gradient = ctx.createRadialGradient(
        centerX - radius * 0.35,
        centerY - radius * 0.35,
        radius * 0.08,
        centerX,
        centerY,
        radius,
    );
    gradient.addColorStop(0, 'rgba(125, 211, 252, 0.46)');
    gradient.addColorStop(0.34, 'rgba(30, 64, 175, 0.20)');
    gradient.addColorStop(1, 'rgba(2, 6, 23, 0.96)');

    ctx.beginPath();
    ctx.arc(centerX, centerY, radius, 0, Math.PI * 2);
    ctx.fillStyle = gradient;
    ctx.fill();

    ctx.beginPath();
    ctx.arc(centerX, centerY, radius, 0, Math.PI * 2);
    ctx.strokeStyle = 'rgba(148, 163, 184, 0.36)';
    ctx.lineWidth = 1;
    ctx.stroke();
};

const drawSampledCurve = (samples, front) => {
    if (!ctx) {
        return;
    }

    ctx.beginPath();
    let drawing = false;

    samples.forEach((sample) => {
        const visible = front ? sample.z >= 0 : sample.z < 0;
        if (!visible) {
            drawing = false;
            return;
        }

        if (!drawing) {
            ctx.moveTo(sample.x, sample.y);
            drawing = true;
        } else {
            ctx.lineTo(sample.x, sample.y);
        }
    });

    ctx.strokeStyle = front ? 'rgba(125, 211, 252, 0.24)' : 'rgba(125, 211, 252, 0.08)';
    ctx.lineWidth = front ? 1 : 0.8;
    ctx.stroke();
};

const drawWireframe = (centerX, centerY, radius) => {
    const meridians = 12;
    const parallels = [-60, -30, 0, 30, 60];

    for (let i = 0; i < meridians; i += 1) {
        const longitude = (360 / meridians) * i - 180;
        const samples = [];

        for (let lat = -88; lat <= 88; lat += 4) {
            const base = latLngToVector(lat, longitude);
            const rotated = rotateY(base, rotation);
            samples.push({
                x: centerX + rotated.x * radius,
                y: centerY - rotated.y * radius,
                z: rotated.z,
            });
        }

        drawSampledCurve(samples, false);
        drawSampledCurve(samples, true);
    }

    parallels.forEach((latitude) => {
        const samples = [];

        for (let lng = -180; lng <= 180; lng += 4) {
            const base = latLngToVector(latitude, lng);
            const rotated = rotateY(base, rotation);
            samples.push({
                x: centerX + rotated.x * radius,
                y: centerY - rotated.y * radius,
                z: rotated.z,
            });
        }

        drawSampledCurve(samples, false);
        drawSampledCurve(samples, true);
    });
};

const drawDreamPoints = (centerX, centerY, radius) => {
    if (!ctx) {
        return;
    }

    projectedPoints = [];

    props.points.forEach((point) => {
        const base = latLngToVector(point.lat, point.lng);
        const rotated = rotateY(base, rotation);

        if (rotated.z < -0.05) {
            return;
        }

        const x = centerX + rotated.x * radius;
        const y = centerY - rotated.y * radius;
        const depth = Math.max(0, Math.min(1, (rotated.z + 1) / 2));
        const dotRadius = 2 + depth * 4;
        const alpha = 0.4 + depth * 0.6;
        const [r, g, b] = rgbBySentiment[point.sentiment] || rgbBySentiment.neutral;

        ctx.beginPath();
        ctx.arc(x, y, dotRadius, 0, Math.PI * 2);
        ctx.fillStyle = `rgba(${r}, ${g}, ${b}, ${alpha})`;
        ctx.shadowColor = `rgba(${r}, ${g}, ${b}, ${0.8 * alpha})`;
        ctx.shadowBlur = 8;
        ctx.fill();
        ctx.shadowBlur = 0;

        projectedPoints.push({
            ...point,
            x,
            y,
            radius: Math.max(6, dotRadius + 2),
        });
    });
};

const renderGlobe = () => {
    if (!ctx || !globeCanvas.value) {
        return;
    }

    const canvas = globeCanvas.value;
    const width = canvas.clientWidth;
    const height = canvas.clientHeight;
    const centerX = width / 2;
    const centerY = height / 2;
    const radius = Math.min(width, height) * 0.43;

    ctx.clearRect(0, 0, width, height);
    drawSphereBase(centerX, centerY, radius);
    drawWireframe(centerX, centerY, radius);
    drawDreamPoints(centerX, centerY, radius);

    rotation += 0.0035;
    animationFrame = window.requestAnimationFrame(renderGlobe);
};

const handlePointerMove = (event) => {
    const canvas = globeCanvas.value;
    if (!canvas || projectedPoints.length === 0) {
        hoveredPoint.value = null;
        if (canvas) {
            canvas.style.cursor = 'default';
        }
        return;
    }

    const rect = canvas.getBoundingClientRect();
    const x = event.clientX - rect.left;
    const y = event.clientY - rect.top;

    let nearest = null;
    let nearestDistance = Infinity;

    projectedPoints.forEach((point) => {
        const distance = Math.hypot(point.x - x, point.y - y);
        if (distance <= point.radius && distance < nearestDistance) {
            nearest = point;
            nearestDistance = distance;
        }
    });

    hoveredPoint.value = nearest;
    canvas.style.cursor = nearest ? 'pointer' : 'default';
};

const handlePointClick = () => {
    if (!hoveredPoint.value) {
        return;
    }

    if (!page.props.auth?.user) {
        window.location.href = route('login');
        return;
    }

    window.location.href = route('dreams.show', { dream: hoveredPoint.value.id });
};

const clearHover = () => {
    hoveredPoint.value = null;
    if (globeCanvas.value) {
        globeCanvas.value.style.cursor = 'default';
    }
};

onMounted(() => {
    setupCanvasSize();
    renderGlobe();

    if (globeContainer.value) {
        resizeObserver = new ResizeObserver(() => {
            setupCanvasSize();
        });
        resizeObserver.observe(globeContainer.value);
    }
});

onBeforeUnmount(() => {
    if (animationFrame) {
        window.cancelAnimationFrame(animationFrame);
    }
    if (resizeObserver) {
        resizeObserver.disconnect();
    }
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
                        Spinning geospatial view of public dreams that include location coordinates.
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

                        <div ref="globeContainer" class="globe-wrap mt-4">
                            <canvas
                                ref="globeCanvas"
                                class="globe-canvas"
                                @mousemove="handlePointerMove"
                                @mouseleave="clearHover"
                                @click="handlePointClick"
                            />

                            <div v-if="!mappedPointsCount" class="globe-empty">
                                No location-tagged dreams yet.
                            </div>

                            <div v-else-if="hoveredPoint" class="globe-tooltip">
                                <p class="text-sm font-semibold text-slate-100">
                                    {{ hoveredPoint.title || 'Untitled Dream' }}
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
                            <h3 class="text-xl font-semibold text-slate-100">Top Themes</h3>
                            <div class="mt-3 space-y-2">
                                <div
                                    v-for="theme in stats.themes"
                                    :key="theme.theme"
                                    class="flex items-center justify-between rounded-md border border-slate-700/70 bg-slate-800/60 px-3 py-2 text-sm"
                                >
                                    <span class="text-slate-100">{{ theme.theme }}</span>
                                    <span class="text-slate-300">{{ theme.count }}</span>
                                </div>
                                <p v-if="!stats.themes.length" class="text-sm text-slate-300">
                                    No themed dream data yet.
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
}

.globe-canvas {
    display: block;
    max-width: 100%;
    border-radius: 9999px;
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
</style>
