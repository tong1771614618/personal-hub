<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: { type: String, required: true },
    active: { type: Boolean },
});

const classes = computed(() =>
    props.active
        ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-indigo-500 text-start text-base font-medium text-indigo-600 bg-indigo-50 dark:text-indigo-400 dark:bg-indigo-900/20'
        : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-slate-600 hover:text-slate-900 hover:bg-slate-50 hover:border-slate-300 dark:text-slate-400 dark:hover:text-slate-100 dark:hover:bg-slate-800 dark:hover:border-slate-600 transition',
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
