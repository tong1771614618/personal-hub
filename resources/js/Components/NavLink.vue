<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: { type: String, required: true },
    active: { type: Boolean },
});

const classes = computed(() =>
    props.active
        ? 'inline-flex items-center px-3 pt-1 pb-2.5 border-b-2 border-indigo-500 text-sm font-medium text-indigo-600 dark:text-indigo-400 focus:outline-none transition'
        : 'inline-flex items-center px-3 pt-1 pb-2.5 border-b-2 border-transparent text-sm font-medium text-slate-600 hover:text-slate-900 hover:border-slate-300 dark:text-slate-400 dark:hover:text-slate-100 dark:hover:border-slate-600 transition',
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
