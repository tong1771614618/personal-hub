<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import { api, formatDate, minutesToText, ENTRY_TYPES } from '@/api.js';

const data = ref(null);
const loading = ref(true);

async function load() {
    loading.value = true;
    try {
        data.value = await api.getWeeklyReview();
    } finally {
        loading.value = false;
    }
}

onMounted(load);
</script>

<template>
    <Head title="Review" />
    <AuthenticatedLayout>
        <div class="mx-auto max-w-4xl px-4 py-6 sm:px-6 lg:px-8">
            <div class="mb-6">
                <h1 class="text-2xl font-semibold text-slate-900 dark:text-slate-50">📅 周回顾</h1>
                <p v-if="data" class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                    {{ formatDate(data.range.from) }} — {{ formatDate(data.range.to) }}
                </p>
            </div>

            <div v-if="loading" class="text-center py-16 text-slate-400 dark:text-slate-500">
                <div class="inline-block h-8 w-8 animate-spin rounded-full border-4 border-slate-300 border-t-indigo-600"></div>
                <p class="mt-3 text-sm">加载中…</p>
            </div>

            <template v-else-if="data">
                <!-- 数字卡片 -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                    <div class="rounded-2xl bg-gradient-to-br from-indigo-500 to-indigo-600 p-5 text-white shadow-md">
                        <p class="text-xs uppercase tracking-wider opacity-80">完成任务</p>
                        <p class="mt-2 text-3xl font-bold">{{ data.totals.completed_count }}</p>
                    </div>
                    <div class="rounded-2xl bg-gradient-to-br from-emerald-500 to-emerald-600 p-5 text-white shadow-md">
                        <p class="text-xs uppercase tracking-wider opacity-80">记录条目</p>
                        <p class="mt-2 text-3xl font-bold">{{ data.totals.entry_count }}</p>
                    </div>
                    <div class="rounded-2xl bg-gradient-to-br from-amber-500 to-orange-600 p-5 text-white shadow-md">
                        <p class="text-xs uppercase tracking-wider opacity-80">投入时长</p>
                        <p class="mt-2 text-2xl font-bold">{{ minutesToText(data.totals.total_minutes) || '—' }}</p>
                    </div>
                </div>

                <!-- 按类型分布 -->
                <section class="mb-6">
                    <h2 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3 dark:text-slate-400">按类型</h2>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                        <div
                            v-for="(meta, key) in ENTRY_TYPES"
                            :key="key"
                            class="rounded-xl bg-white p-4 border border-slate-100 dark:bg-slate-800 dark:border-slate-700"
                        >
                            <div class="flex items-center gap-2">
                                <span :class="['h-2 w-2 rounded-full', meta.dot]"></span>
                                <span class="text-sm text-slate-700 dark:text-slate-200">{{ meta.label }}</span>
                            </div>
                            <p class="mt-2 text-xl font-semibold text-slate-800 dark:text-slate-100">
                                {{ data.by_type[key]?.count || 0 }}
                                <span class="text-xs text-slate-400 dark:text-slate-500 font-normal ml-1">
                                    {{ data.by_type[key]?.total_minutes ? '· ' + minutesToText(data.by_type[key].total_minutes) : '' }}
                                </span>
                            </p>
                        </div>
                    </div>
                </section>

                <!-- 按分类标签 -->
                <section class="mb-6">
                    <h2 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3 dark:text-slate-400">按分类</h2>
                    <div v-if="data.by_tag.length === 0" class="text-sm text-slate-400 dark:text-slate-500">本周暂无带分类的条目</div>
                    <ul v-else class="space-y-2">
                        <li
                            v-for="row in data.by_tag"
                            :key="row.category_tag"
                            class="flex items-center justify-between rounded-xl bg-white p-3 border border-slate-100 dark:bg-slate-800 dark:border-slate-700"
                        >
                            <span class="text-sm text-slate-700 dark:text-slate-200">#{{ row.category_tag }}</span>
                            <span class="text-sm text-slate-600 dark:text-slate-300">
                                {{ row.count }} 条 · {{ minutesToText(row.total_minutes) || '0 分钟' }}
                            </span>
                        </li>
                    </ul>
                </section>

                <!-- 完成的任务 -->
                <section class="mb-6">
                    <h2 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3 dark:text-slate-400">本周完成的任务</h2>
                    <div v-if="data.completed_tasks.length === 0" class="rounded-xl border border-dashed border-slate-300 dark:border-slate-700 py-8 text-center text-sm text-slate-400 dark:text-slate-500">
                        🎯 本周还没有完成的任务，加油
                    </div>
                    <ul v-else class="space-y-2">
                        <li
                            v-for="task in data.completed_tasks"
                            :key="task.id"
                            class="flex items-center gap-2 rounded-xl bg-white p-3 border border-slate-100 dark:bg-slate-800 dark:border-slate-700"
                        >
                            <span class="text-emerald-500 text-lg">✓</span>
                            <span class="text-sm text-slate-700 dark:text-slate-200 flex-1">{{ task.title }}</span>
                            <span v-if="task.duration_minutes" class="text-xs text-slate-400 dark:text-slate-500">{{ minutesToText(task.duration_minutes) }}</span>
                            <span class="text-xs text-slate-400 dark:text-slate-500">{{ formatDate(task.completed_at) }}</span>
                        </li>
                    </ul>
                </section>

                <!-- 收藏的资源 -->
                <section>
                    <h2 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3 dark:text-slate-400">本周收藏的资源</h2>
                    <div v-if="data.resources.length === 0" class="text-sm text-slate-400 dark:text-slate-500">本周没有收藏</div>
                    <ul v-else class="space-y-2">
                        <li
                            v-for="res in data.resources"
                            :key="res.id"
                            class="rounded-xl bg-white p-3 border border-slate-100 dark:bg-slate-800 dark:border-slate-700"
                        >
                            <p class="text-sm text-slate-700 dark:text-slate-200">{{ res.content }}</p>
                            <a v-if="res.resource_url" :href="res.resource_url" target="_blank" class="text-xs text-indigo-600 hover:underline break-all dark:text-indigo-400">
                                {{ res.resource_url }}
                            </a>
                        </li>
                    </ul>
                </section>
            </template>
        </div>
    </AuthenticatedLayout>
</template>
