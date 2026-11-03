<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, watch, computed } from 'vue';
import { api, ENTRY_TYPES, relativeTime, minutesToText, formatDate } from '@/api.js';

const entries = ref([]);
const tags = ref([]);
const pagination = ref({ current_page: 1, last_page: 1 });

const filters = ref({ type: '', tag: '', from: '', to: '' });

// 按日期分组（今天 / 昨天 / 本周 / 更早）
const grouped = computed(() => {
    const now = new Date();
    const todayStr = now.toDateString();
    const yestDate = new Date(now); yestDate.setDate(now.getDate() - 1);
    const yestStr = yestDate.toDateString();
    const weekStart = new Date(now); weekStart.setDate(now.getDate() - 7);

    const groups = { today: [], yesterday: [], week: [], older: [] };
    entries.value.forEach(e => {
        const d = new Date(e.happened_at);
        const ds = d.toDateString();
        if (ds === todayStr) groups.today.push(e);
        else if (ds === yestStr) groups.yesterday.push(e);
        else if (d >= weekStart) groups.week.push(e);
        else groups.older.push(e);
    });
    return groups;
});

async function load(page = 1) {
    const params = { per_page: 50, page };
    if (filters.value.type) params.type = filters.value.type;
    if (filters.value.tag) params.tag = filters.value.tag;
    if (filters.value.from) params.from = filters.value.from;
    if (filters.value.to) params.to = filters.value.to;
    const res = await api.getEntries(params);
    entries.value = res.data;
    pagination.value = { current_page: res.current_page, last_page: res.last_page };
}

async function loadTags() {
    tags.value = await api.getTags();
}

function resetFilters() {
    filters.value = { type: '', tag: '', from: '', to: '' };
    load(1);
}

watch(filters, () => load(1), { deep: true });

async function remove(id) {
    if (!confirm('删除？')) return;
    await api.deleteEntry(id);
    load(pagination.value.current_page);
}

onMounted(async () => {
    await loadTags();
    await load();
});
</script>

<template>
    <Head title="Stream" />
    <AuthenticatedLayout>
        <div class="mx-auto max-w-4xl px-4 py-6 sm:px-6 lg:px-8">
            <div class="mb-5">
                <h1 class="text-2xl font-semibold text-slate-900 dark:text-slate-50">🌊 条目流</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">所有记录按时间倒序</p>
            </div>

            <!-- 筛选栏 -->
            <div class="mb-5 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                <div class="flex flex-wrap gap-3 items-end">
                    <div>
                        <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">类型</label>
                        <select v-model="filters.type" class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100">
                            <option value="">全部</option>
                            <option v-for="(meta, key) in ENTRY_TYPES" :key="key" :value="key">{{ meta.label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">分类</label>
                        <select v-model="filters.tag" class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100">
                            <option value="">全部</option>
                            <option v-for="t in tags" :key="t.id" :value="t.name">{{ t.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">从</label>
                        <input v-model="filters.from" type="date" class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100" />
                    </div>
                    <div>
                        <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">至</label>
                        <input v-model="filters.to" type="date" class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100" />
                    </div>
                    <button @click="resetFilters" class="rounded-lg bg-slate-100 px-3 py-1.5 text-sm text-slate-600 hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-300 dark:hover:bg-slate-600">重置</button>
                </div>
            </div>

            <!-- 空状态 -->
            <div v-if="entries.length === 0" class="rounded-xl border border-dashed border-slate-300 bg-white/60 py-16 text-center dark:border-slate-700 dark:bg-slate-800/30">
                <div class="text-5xl mb-2">📭</div>
                <p class="text-sm text-slate-500 dark:text-slate-400">没有找到符合条件的条目</p>
            </div>

            <!-- 按组渲染 -->
            <div v-else class="space-y-6">
                <section v-for="(group, key) in { today: grouped.today, yesterday: grouped.yesterday, week: grouped.week, older: grouped.older }" :key="key">
                    <template v-if="group.length > 0">
                        <h2 class="mb-2 text-xs font-semibold text-slate-500 uppercase tracking-wider dark:text-slate-400">
                            {{ key === 'today' ? '今天' : key === 'yesterday' ? '昨天' : key === 'week' ? '近 7 天' : '更早' }}
                            · {{ group.length }}
                        </h2>
                        <ul class="space-y-2">
                            <li
                                v-for="entry in group"
                                :key="entry.id"
                                class="group flex items-start gap-3 rounded-xl bg-white p-4 shadow-sm border border-slate-100 dark:bg-slate-800 dark:border-slate-700"
                            >
                                <span :class="['mt-1.5 h-2 w-2 shrink-0 rounded-full', ENTRY_TYPES[entry.type].dot]"></span>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-1 flex-wrap">
                                        <span :class="['text-xs px-2 py-0.5 rounded-full', ENTRY_TYPES[entry.type].color]">
                                            {{ ENTRY_TYPES[entry.type].label }}
                                        </span>
                                        <span v-if="entry.category_tag" class="text-xs text-slate-500 dark:text-slate-400">#{{ entry.category_tag }}</span>
                                        <span v-if="entry.duration_minutes" class="text-xs text-slate-400 dark:text-slate-500">· {{ minutesToText(entry.duration_minutes) }}</span>
                                        <span v-if="entry.task" class="text-xs text-slate-400 dark:text-slate-500">→ {{ entry.task.title }}</span>
                                        <span class="text-xs text-slate-400 dark:text-slate-500 ml-auto">{{ relativeTime(entry.happened_at) }}</span>
                                    </div>
                                    <p class="text-sm text-slate-800 whitespace-pre-wrap break-words dark:text-slate-100">{{ entry.content }}</p>
                                    <a
                                        v-if="entry.resource_url"
                                        :href="entry.resource_url"
                                        target="_blank"
                                        class="mt-1 inline-block text-xs text-indigo-600 hover:underline truncate max-w-full dark:text-indigo-400"
                                    >{{ entry.resource_url }}</a>
                                </div>
                                <button
                                    @click="remove(entry.id)"
                                    class="opacity-0 group-hover:opacity-100 text-slate-400 hover:text-red-500 text-xs transition"
                                >删除</button>
                            </li>
                        </ul>
                    </template>
                </section>
            </div>

            <!-- 分页 -->
            <div v-if="pagination.last_page > 1" class="mt-6 flex justify-center gap-2">
                <button
                    v-for="p in pagination.last_page"
                    :key="p"
                    @click="load(p)"
                    :class="[
                        'px-3 py-1 rounded-lg text-sm',
                        p === pagination.current_page
                            ? 'bg-indigo-600 text-white dark:bg-indigo-500'
                            : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-700',
                    ]"
                >{{ p }}</button>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
