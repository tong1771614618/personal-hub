<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { useTheme } from '@/composables/useTheme.js';
import ThemeToggle from '@/Components/ThemeToggle.vue';

const { isDark } = useTheme();

defineProps({
    canLogin: { type: Boolean },
    canRegister: { type: Boolean },
});
</script>

<template>
    <Head title="Personal Hub" />
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-indigo-50 to-slate-100 dark:from-slate-950 dark:via-slate-900 dark:to-indigo-950">
        <header class="flex items-center justify-between px-6 py-5 sm:px-10">
            <Link href="/" class="flex items-center gap-2">
                <span class="text-2xl">📓</span>
                <span class="text-lg font-semibold text-slate-800 dark:text-slate-100">Personal Hub</span>
            </Link>
            <div class="flex items-center gap-3">
                <ThemeToggle />
                <Link
                    v-if="canLogin"
                    :href="route('login')"
                    class="rounded-lg px-4 py-2 text-sm font-medium text-slate-700 hover:text-slate-900 dark:text-slate-300 dark:hover:text-slate-100"
                >
                    登录
                </Link>
                <Link
                    v-if="canRegister"
                    :href="route('register')"
                    class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600"
                >
                    注册
                </Link>
            </div>
        </header>

        <main class="flex flex-col items-center justify-center px-6 pt-16 pb-24 text-center sm:pt-24">
            <div class="text-7xl mb-6">📓</div>
            <h1 class="text-4xl sm:text-5xl font-bold text-slate-900 dark:text-slate-50 tracking-tight">
                Personal Hub
            </h1>
            <p class="mt-4 max-w-lg text-lg text-slate-600 dark:text-slate-300">
                你的随手记、任务看板、周回顾，全部在一个地方。
            </p>

            <div class="mt-10 flex gap-3">
                <Link
                    :href="canRegister ? route('register') : route('login')"
                    class="rounded-xl bg-indigo-600 px-6 py-3 text-base font-medium text-white shadow-lg hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600"
                >
                    开始使用 →
                </Link>
            </div>

            <div class="mt-20 grid grid-cols-1 sm:grid-cols-3 gap-6 max-w-3xl">
                <div class="rounded-2xl bg-white/70 dark:bg-slate-800/60 p-6 text-left shadow-sm border border-slate-100 dark:border-slate-700">
                    <div class="text-3xl mb-3">📝</div>
                    <h3 class="text-base font-semibold text-slate-800 dark:text-slate-100">Capture 随手记</h3>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">想法、梦境、学习心得、收藏链接，一秒记一条。</p>
                </div>
                <div class="rounded-2xl bg-white/70 dark:bg-slate-800/60 p-6 text-left shadow-sm border border-slate-100 dark:border-slate-700">
                    <div class="text-3xl mb-3">📋</div>
                    <h3 class="text-base font-semibold text-slate-800 dark:text-slate-100">Board 任务看板</h3>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">四列看板拖拽切换状态，完成自动记录耗时。</p>
                </div>
                <div class="rounded-2xl bg-white/70 dark:bg-slate-800/60 p-6 text-left shadow-sm border border-slate-100 dark:border-slate-700">
                    <div class="text-3xl mb-3">📅</div>
                    <h3 class="text-base font-semibold text-slate-800 dark:text-slate-100">Review 周回顾</h3>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">本周做了什么、学了什么、投入了多少时间。</p>
                </div>
            </div>
        </main>
    </div>
</template>
