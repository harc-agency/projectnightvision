<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
    },
});

const classes = computed(() =>
    props.active
        ? 'inline-flex items-center rounded-md border border-sky-400/50 bg-sky-500/15 px-3 py-1.5 text-sm font-medium leading-5 text-sky-100 transition duration-150 ease-in-out'
        : 'inline-flex items-center rounded-md border border-transparent px-3 py-1.5 text-sm font-medium leading-5 text-slate-300 transition duration-150 ease-in-out hover:border-slate-600 hover:bg-slate-800/70 hover:text-white',
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
