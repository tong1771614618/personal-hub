<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import { api, ENTRY_TYPES, relativeTime, minutesToText } from '@/api.js';
import { getGreeting } from '@/composables/useTheme.js';

const content = ref('');
const type = ref('thought');
const categoryTag = ref('');
const resourceUrl = ref('');
const submitting = ref(false);
const errorMsg = ref('');

const todayEntries = ref([]);
const recentEntries = ref([]);
const tags = ref([]);

const greeting = getGreeting();

const today = computed(() => {
    const d = new Date();
    return d.toLocaleDateString('zh-CN', { year: 'numeric', month: 'long', day: 'numeric', weekday: 'long' });
});

async function loadData() {
    const [entRes, tagList] = await Promise.all([
        api.getEntries({ per_page: 100 }),
        api.getTags(),
    ]);
    tags.value = tagList;

    const all = entRes.data || [];
    const todayStr = new Date().toDateString();
    todayEntries.value = all.filter(e => new Date(e.happened_at).toDateString() === todayStr);
    recentEntries.value = all.slice(0, 30);
}

async function submit() {
    errorMsg.value = '';
    if (!content.value.trim()) {
        errorMsg.value = '写点什么再提交';
        return;
    }
    submitting.value = true;
    try {
        const payload = { type: type.value, content: content.value.trim() };
        if (categoryTag.value) payload.category_tag = categoryTag.value;
        if (type.value === 'resource' && resourceUrl.value) payload.resource_url = resourceUrl.value;

        await api.createEntry(payload);
        content.value = '';
        categoryTag.value = '';
        resourceUrl.value = '';
        await loadData();
    } catch (e) {
        errorMsg.value = e.response?.data?.message || '提交失败';
    } finally {
        submitting.value = false;
    }
}

async function remove(id) {
    if (!confirm('删除这条记录？')) return;
    await api.deleteEntry(id);
    await loadData();
}

function pickType(t) { type.value = t; }

onMounted(loadData);
</script>

<template>
    <Head title="Capture" />
    <AuthenticatedLayout>
        <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
            <!-- 问候 -->
            <div class="mb-6">
                <h1 class="text-3xl font-semibold text-slate-900 dark:text-slate-50">
                    <span class="mr-2">{{ greeting.emoji }}</span>{{ greeting.text }}
                </h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ today }}</p>
            </div>

            <!-- 快速输入框 -->
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                <div class="flex flex-wrap gap-2 mb-3">
                    <button
                        v-for="(meta, key) in ENTRY_TYPES"
                        :key="key"
                        @click="pickType(key)"
                        :class="[
                            'px-3 py-1.5 rounded-full text-xs font-medium transition',
                            type === key
                                ? meta.color + ' dark:bg-opacity-20 ring-2 ring-offset-1 ring-current dark:ring-offset-slate-800'
                                : 'bg-slate-100 text-slate-500 hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-300 dark:hover:bg-slate-600',
                        ]"
                    >
                        {{ meta.label }}
                    </button>
                </div>

                <textarea
                    v-model="content"
                    rows="3"
                    placeholder="说点什么…一个念头、今天学了啥、做了个梦、收藏个链接…"
                    class="w-full resize-none rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 placeholder-slate-400 focus:border-indigo-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-100 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 dark:placeholder-slate-500 dark:focus:border-indigo-500 dark:focus:bg-slate-950 dark:focus:ring-indigo-900/40"
                    @keydown.meta.enter="submit"
                    @keydown.ctrl.enter="submit"
                ></textarea>

                <input
                    v-if="type === 'resource'"
                    v-model="resourceUrl"
                    type="url"
                    placeholder="https://..."
                    class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2 text-sm focus:border-indigo-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-100 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-indigo-500 dark:focus:bg-slate-950"
                />

                <div class="mt-3 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <select
                            v-model="categoryTag"
                            class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs text-slate-600 focus:outline-none dark:border-slate-600 dark:bg-slate-900 dark:text-slate-300"
                        >
                            <option value="">无分类</option>
                            <option v-for="t in tags" :key="t.id" :value="t.name">{{ t.name }}</option>
                        </select>
                        <span class="hidden sm:inline text-xs text-slate-400 dark:text-slate-500">⌘/Ctrl + Enter 发送</span>
                    </div>
                    <button
                        @click="submit"
                        :disabled="submitting"
                        class="rounded-xl bg-indigo-600 px-5 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-700 disabled:opacity-50 dark:bg-indigo-500 dark:hover:bg-indigo-600"
                    >
                        {{ submitting ? '提交中…' : '记一下' }}
                    </button>
                </div>
                <p v-if="errorMsg" class="mt-2 text-xs text-red-500">{{ errorMsg }}</p>
            </div>

            <!-- 今日条目 -->
            <section class="mt-8">
                <h2 class="mb-3 text-xs font-semibold text-slate-500 uppercase tracking-wider dark:text-slate-400">
                    今日 · {{ todayEntries.length }} 条
                </h2>
                <div v-if="todayEntries.length === 0" class="rounded-xl border border-dashed border-slate-300 bg-white/60 py-14 text-center dark:border-slate-700 dark:bg-slate-800/30">
                    <div class="text-5xl mb-2">✨</div>
                    <p class="text-sm text-slate-500 dark:text-slate-400">今天还没有记录，来一条？</p>
                </div>
                <ul v-else class="space-y-2">
                    <li
                        v-for="entry in todayEntries"
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
            </section>

            <!-- 近期条目 -->
            <section class="mt-8">
                <h2 class="mb-3 text-xs font-semibold text-slate-500 uppercase tracking-wider dark:text-slate-400">近期</h2>
                <ul v-if="recentEntries.length" class="space-y-2">
                    <li
                        v-for="entry in recentEntries.filter(e => !todayEntries.find(t => t.id === e.id))"
                        :key="entry.id"
                        class="flex items-start gap-3 rounded-xl bg-white/70 p-3 border border-slate-100 dark:bg-slate-800/50 dark:border-slate-700"
                    >
                        <span :class="['mt-1.5 h-2 w-2 shrink-0 rounded-full', ENTRY_TYPES[entry.type].dot]"></span>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1 text-xs flex-wrap">
                                <span :class="['px-2 py-0.5 rounded-full', ENTRY_TYPES[entry.type].color]">{{ ENTRY_TYPES[entry.type].label }}</span>
                                <span v-if="entry.category_tag" class="text-slate-500 dark:text-slate-400">#{{ entry.category_tag }}</span>
                                <span class="text-slate-400 dark:text-slate-500 ml-auto">{{ relativeTime(entry.happened_at) }}</span>
                            </div>
                            <p class="text-sm text-slate-700 whitespace-pre-wrap break-words line-clamp-3 dark:text-slate-200">{{ entry.content }}</p>
                        </div>
                    </li>
                </ul>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
