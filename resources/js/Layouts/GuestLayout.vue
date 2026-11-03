<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="flex min-h-screen flex-col items-center bg-gradient-to-br from-slate-50 to-slate-100 pt-10 sm:justify-center sm:pt-0 dark:from-slate-900 dark:to-slate-950">
        <div>
            <Link href="/" class="flex items-center gap-2">
                <span class="text-4xl">📓</span>
                <span class="text-xl font-semibold text-slate-800 dark:text-slate-100">Personal Hub</span>
            </Link>
        </div>

        <div class="mt-6 w-full overflow-hidden bg-white px-6 py-6 shadow-lg sm:max-w-md sm:rounded-2xl dark:bg-slate-800 dark:shadow-slate-950/50">
            <slot />
        </div>
    </div>
</template>
