<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, computed, onMounted, onErrorCaptured, nextTick } from 'vue';
import { api, DRINK_TYPES, standardDrinksText, drinkingLevelText, formatDate } from '@/api.js';

// ============ 全局状态 ============
const loading = ref(true);
const pageError = ref('');

onErrorCaptured((err) => {
    console.error('[Drinks] Vue error:', err);
    pageError.value = err.message || '页面渲染出错';
    return false; // 阻止冒泡，避免整页白屏
});

// ============ 表单 ============
const drinkType = ref('beer');
const amountMl = ref(DRINK_TYPES.beer.defaultMl);
const alcoholPct = ref(DRINK_TYPES.beer.defaultPct);
const note = ref('');
const location = ref('');
const companyCount = ref(null);
const submitting = ref(false);
const toast = ref({ show: false, text: '', kind: 'success' });
const customAmount = ref(false);
const amountInputRef = ref(null);

function focusCustomAmount() {
    customAmount.value = true;
    amountMl.value = null;
    nextTick(() => amountInputRef.value?.focus());
}

// 切换酒类时，自动填入默认值
function pickDrink(key) {
    drinkType.value = key;
    amountMl.value = DRINK_TYPES[key].defaultMl;
    alcoholPct.value = DRINK_TYPES[key].defaultPct;
    customAmount.value = false;
}

const pureAlcoholMl = computed(() => (amountMl.value * alcoholPct.value / 100).toFixed(1));
const standardDrinks = computed(() => (pureAlcoholMl.value / 10).toFixed(1));
const level = computed(() => drinkingLevelText(parseFloat(standardDrinks.value)));

async function submit() {
    submitting.value = true;
    try {
        await api.createDrink({
            drink_type: drinkType.value,
            amount_ml: parseInt(amountMl.value),
            alcohol_pct: parseFloat(alcoholPct.value),
            note: note.value || null,
            location: location.value || null,
            company_count: companyCount.value !== null && companyCount.value !== '' ? parseInt(companyCount.value) : null,
        });
        showToast(`✅ 已记录：${DRINK_TYPES[drinkType.value].label} ${amountMl.value}ml`, 'success');
        note.value = '';
        location.value = '';
        companyCount.value = null;
    } catch (e) {
        showToast(e.response?.data?.message || '记录失败', 'error');
    } finally {
        submitting.value = false;
    }
    // 无论成功失败都刷新数据
    try { await Promise.all([loadHistory(), loadStats()]); } catch (_) {}
}

function showToast(text, kind = 'success') {
    toast.value = { show: true, text, kind };
    setTimeout(() => { toast.value.show = false; }, 2500);
}

// ============ 历史 ============
const history = ref([]);
async function loadHistory() {
    try {
        history.value = await api.getDrinks();
    } catch (e) {
        console.error('[Drinks] loadHistory error:', e);
    }
}
async function remove(id) {
    if (!confirm('删除这条记录？')) return;
    try {
        await api.deleteDrink(id);
    } catch (e) {
        showToast('删除失败', 'error');
        return;
    }
    try { await Promise.all([loadHistory(), loadStats()]); } catch (_) {}
}

// ============ 统计 ============
const stats = ref(null);
async function loadStats() {
    try {
        stats.value = await api.getDrinkStats();
    } catch (e) {
        console.error('[Drinks] loadStats error:', e);
    }
}

// 30 天趋势柱状图的最大值（用于归一化高度）
const trendMax = computed(() => {
    if (!stats.value) return 1;
    return Math.max(1, ...stats.value.daily_trend.map(d => d.standard_drinks));
});

onMounted(async () => {
    try {
        await Promise.all([loadHistory(), loadStats()]);
    } catch (e) {
        console.error('[Drinks] onMounted error:', e);
        pageError.value = '加载数据失败，请刷新页面';
    } finally {
        loading.value = false;
    }
});

async function retryLoad() {
    loading.value = true;
    pageError.value = '';
    try {
        await Promise.all([loadHistory(), loadStats()]);
    } catch (e) {
        pageError.value = '加载数据失败，请刷新页面';
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <Head title="Drinks" />
    <AuthenticatedLayout>
        <div class="mx-auto max-w-5xl px-4 py-6 sm:px-6 lg:px-8">
            <div class="mb-5">
                <h1 class="text-2xl font-semibold text-slate-900 dark:text-slate-50">🍺 喝酒记录</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">快速记一杯，看看本月喝了多少</p>
            </div>

            <!-- Toast -->
            <Transition name="toast">
                <div
                    v-if="toast.show"
                    :class="[
                        'fixed top-20 right-6 z-50 rounded-xl px-4 py-2.5 text-sm shadow-lg',
                        toast.kind === 'error' ? 'bg-red-500 text-white' : 'bg-emerald-500 text-white',
                    ]"
                >{{ toast.text }}</div>
            </Transition>

            <!-- Loading -->
            <div v-if="loading" class="flex items-center justify-center py-20">
                <div class="h-8 w-8 animate-spin rounded-full border-4 border-amber-200 border-t-amber-500"></div>
                <span class="ml-3 text-sm text-slate-500 dark:text-slate-400">加载中…</span>
            </div>

            <!-- Error -->
            <div v-else-if="pageError" class="rounded-xl border border-red-200 bg-red-50 p-6 text-center dark:border-red-800 dark:bg-red-900/20">
                <p class="text-sm text-red-600 dark:text-red-400">{{ pageError }}</p>
                <button @click="retryLoad" class="mt-3 rounded-lg bg-red-500 px-4 py-1.5 text-sm text-white hover:bg-red-600">重试</button>
            </div>

            <template v-else>
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                <h2 class="text-sm font-semibold text-slate-500 uppercase tracking-wider mb-3 dark:text-slate-400">记一杯</h2>

                <!-- 酒类选择 -->
                <div class="grid grid-cols-4 sm:grid-cols-8 gap-2 mb-4">
                    <button
                        v-for="(meta, key) in DRINK_TYPES"
                        :key="key"
                        @click="pickDrink(key)"
                        :class="[
                            'flex flex-col items-center gap-1 rounded-xl p-2 text-xs font-medium transition',
                            drinkType === key
                                ? meta.color + ' ring-2 ring-offset-1 ring-current dark:ring-offset-slate-800'
                                : 'bg-slate-50 text-slate-600 hover:bg-slate-100 dark:bg-slate-700 dark:text-slate-300 dark:hover:bg-slate-600',
                        ]"
                    >
                        <span class="text-2xl">{{ meta.emoji }}</span>
                        <span>{{ meta.label }}</span>
                    </button>
                </div>

                <!-- 容量 / 酒精度 -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div>
                        <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">容量 (ml)</label>
                        <div class="flex items-center gap-2">
                            <input
                                ref="amountInputRef"
                                v-model.number="amountMl"
                                type="number"
                                min="1"
                                max="5000"
                                class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:border-indigo-400 focus:bg-white focus:outline-none dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                            />
                        </div>
                        <!-- 常用容量快捷按钮 -->
                        <div class="flex gap-1 mt-1.5 flex-wrap">
                            <button
                                v-for="ml in [100, 200, 330, 500, 750]"
                                :key="ml"
                                @click="amountMl = ml; customAmount = false"
                                :class="[
                                    'px-2 py-0.5 rounded text-xs transition',
                                    amountMl === ml && !customAmount
                                        ? 'bg-indigo-600 text-white dark:bg-indigo-500'
                                        : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-300',
                                ]"
                            >{{ ml }}</button>
                            <button
                                @click="focusCustomAmount"
                                :class="[
                                    'px-2 py-0.5 rounded text-xs transition',
                                    customAmount
                                        ? 'bg-indigo-600 text-white dark:bg-indigo-500'
                                        : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-300',
                                ]"
                            >自定义</button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">
                            酒精度：{{ alcoholPct }}%
                        </label>
                        <input
                            v-model.number="alcoholPct"
                            type="range"
                            min="0"
                            max="100"
                            step="0.5"
                            class="w-full accent-indigo-600 dark:accent-indigo-400"
                        />
                        <input
                            v-model.number="alcoholPct"
                            type="number"
                            step="0.5"
                            min="0"
                            max="100"
                            class="mt-1 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-1 text-xs focus:border-indigo-400 focus:outline-none dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                        />
                    </div>

                    <div>
                        <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">本次摄入</label>
                        <div class="rounded-lg bg-slate-50 p-3 dark:bg-slate-900">
                            <p class="text-xs text-slate-500 dark:text-slate-400">纯酒精</p>
                            <p class="text-lg font-semibold text-slate-800 dark:text-slate-100">{{ pureAlcoholMl }}<span class="text-sm font-normal text-slate-500"> ml</span></p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">标准杯</p>
                            <p class="text-lg font-semibold text-slate-800 dark:text-slate-100">
                                {{ standardDrinks }}
                                <span :class="['text-xs font-normal ml-1', level.color]">· {{ level.text }}</span>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- 可选字段 -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
                    <input v-model="note" placeholder="备注（可选，如：和老王聊天很开心）" class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:border-indigo-400 focus:bg-white focus:outline-none dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100" />
                    <input v-model="location" placeholder="地点（可选，如：酒吧）" class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:border-indigo-400 focus:bg-white focus:outline-none dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100" />
                    <input v-model.number="companyCount" type="number" min="0" max="99" placeholder="同饮人数（可选）" class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:border-indigo-400 focus:bg-white focus:outline-none dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100" />
                </div>

                <button
                    @click="submit"
                    :disabled="submitting"
                    class="w-full rounded-xl bg-gradient-to-r from-amber-500 to-orange-500 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:from-amber-600 hover:to-orange-600 disabled:opacity-50"
                >
                    {{ submitting ? '记录中…' : `🍻 记一杯 ${DRINK_TYPES[drinkType].label}` }}
                </button>
            </section>

            <!-- 统计卡片 -->
            <section v-if="stats" class="mt-6 grid grid-cols-2 lg:grid-cols-4 gap-3">
                <div class="rounded-2xl bg-gradient-to-br from-amber-500 to-orange-500 p-4 text-white shadow-md">
                    <p class="text-xs uppercase tracking-wider opacity-80">本周</p>
                    <p class="mt-1 text-2xl font-bold">{{ stats.ranges.week.count }}<span class="text-sm font-normal"> 次</span></p>
                    <p class="text-xs opacity-90">{{ standardDrinksText(stats.ranges.week.standard_drinks) }} 标准杯</p>
                </div>
                <div class="rounded-2xl bg-gradient-to-br from-rose-500 to-pink-600 p-4 text-white shadow-md">
                    <p class="text-xs uppercase tracking-wider opacity-80">本月</p>
                    <p class="mt-1 text-2xl font-bold">{{ stats.ranges.month.count }}<span class="text-sm font-normal"> 次</span></p>
                    <p class="text-xs opacity-90">{{ standardDrinksText(stats.ranges.month.standard_drinks) }} 标准杯</p>
                    <p class="text-xs opacity-75 mt-0.5">喝酒 {{ stats.drinking_days_this_month }} 天</p>
                </div>
                <div class="rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 p-4 text-white shadow-md">
                    <p class="text-xs uppercase tracking-wider opacity-80">今年</p>
                    <p class="mt-1 text-2xl font-bold">{{ stats.ranges.year.count }}<span class="text-sm font-normal"> 次</span></p>
                    <p class="text-xs opacity-90">{{ standardDrinksText(stats.ranges.year.standard_drinks) }} 标准杯</p>
                </div>
                <div class="rounded-2xl bg-gradient-to-br from-slate-700 to-slate-900 p-4 text-white shadow-md">
                    <p class="text-xs uppercase tracking-wider opacity-80">累计</p>
                    <p class="mt-1 text-2xl font-bold">{{ stats.ranges.lifetime.count }}<span class="text-sm font-normal"> 次</span></p>
                    <p class="text-xs opacity-90">纯酒精 {{ stats.ranges.lifetime.pure_alcohol_ml }}ml</p>
                </div>
            </section>

            <!-- 按酒类分布 + 30 天趋势 -->
            <section v-if="stats" class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-4">
                <!-- 按类型分布 -->
                <div class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                    <h2 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3 dark:text-slate-400">本月按酒类</h2>
                    <div v-if="stats.by_type.length === 0" class="text-sm text-slate-400 dark:text-slate-500">本月还没有记录</div>
                    <ul v-else class="space-y-2">
                        <li
                            v-for="row in stats.by_type"
                            :key="row.type"
                            class="flex items-center gap-3"
                        >
                            <span class="text-xl">{{ row.emoji }}</span>
                            <span class="text-sm text-slate-700 dark:text-slate-200 w-16">{{ row.label }}</span>
                            <div class="flex-1 h-2 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                                <div
                                    class="h-full bg-gradient-to-r from-amber-400 to-orange-500"
                                    :style="{ width: (row.standard_drinks / Math.max(1, ...stats.by_type.map(b => b.standard_drinks)) * 100) + '%' }"
                                ></div>
                            </div>
                            <span class="text-xs text-slate-500 dark:text-slate-400 w-20 text-right">
                                {{ row.count }} 次 · {{ row.standard_drinks }} 杯
                            </span>
                        </li>
                    </ul>
                </div>

                <!-- 近 30 天趋势 -->
                <div class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                    <h2 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3 dark:text-slate-400">近 30 天</h2>
                    <div class="flex items-end gap-0.5 h-24">
                        <div
                            v-for="day in stats.daily_trend"
                            :key="day.date"
                            class="flex-1 flex flex-col items-center justify-end group relative"
                            :title="`${day.date}: ${day.standard_drinks} 标准杯`"
                        >
                            <div
                                class="w-full rounded-t transition-all"
                                :class="day.standard_drinks > 0 ? 'bg-gradient-to-t from-amber-400 to-orange-500' : 'bg-slate-100 dark:bg-slate-700'"
                                :style="{ height: (day.standard_drinks / trendMax * 100) + '%' , minHeight: '2px' }"
                            ></div>
                            <div class="opacity-0 group-hover:opacity-100 absolute -top-8 bg-slate-900 text-white text-xs rounded px-1.5 py-0.5 whitespace-nowrap pointer-events-none">
                                {{ day.date.slice(5) }} · {{ day.standard_drinks }} 杯
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-between mt-1 text-xs text-slate-400 dark:text-slate-500">
                        <span>{{ stats.daily_trend[0]?.date.slice(5) }}</span>
                        <span>{{ stats.daily_trend[stats.daily_trend.length - 1]?.date.slice(5) }}</span>
                    </div>
                </div>
            </section>

            <!-- 历史记录 -->
            <section class="mt-6">
                <h2 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3 dark:text-slate-400">
                    最近记录 · {{ history.length }} 条
                </h2>
                <div v-if="history.length === 0" class="rounded-xl border border-dashed border-slate-300 bg-white/60 py-12 text-center dark:border-slate-700 dark:bg-slate-800/30">
                    <div class="text-5xl mb-2">🍻</div>
                    <p class="text-sm text-slate-500 dark:text-slate-400">还没有记录，先来一杯？</p>
                </div>
                <ul v-else class="space-y-2">
                    <li
                        v-for="drink in history"
                        :key="drink.id"
                        class="group flex items-center gap-3 rounded-xl bg-white p-3 shadow-sm border border-slate-100 dark:bg-slate-800 dark:border-slate-700"
                    >
                        <span class="text-2xl">{{ DRINK_TYPES[drink.drink_type]?.emoji || '🍹' }}</span>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap mb-0.5">
                                <span :class="['text-xs px-2 py-0.5 rounded-full', DRINK_TYPES[drink.drink_type]?.color || 'bg-slate-100']">
                                    {{ DRINK_TYPES[drink.drink_type]?.label || drink.drink_type }}
                                </span>
                                <span class="text-xs text-slate-400 dark:text-slate-500">
                                    {{ drink.amount_ml }}ml · {{ drink.alcohol_pct }}% · {{ drink.pure_alcohol_ml }}ml 酒精
                                </span>
                                <span :class="['text-xs', drinkingLevelText(drink.standard_drinks).color]">
                                    · {{ standardDrinksText(drink.standard_drinks) }} · {{ drinkingLevelText(drink.standard_drinks).text }}
                                </span>
                                <span class="text-xs text-slate-400 dark:text-slate-500 ml-auto">{{ formatDate(drink.drank_at) }}</span>
                            </div>
                            <div class="flex items-center gap-2 flex-wrap text-xs text-slate-500 dark:text-slate-400">
                                <span v-if="drink.location">📍 {{ drink.location }}</span>
                                <span v-if="drink.company_count">👥 {{ drink.company_count }}人</span>
                                <span v-if="drink.note" class="italic">"{{ drink.note }}"</span>
                            </div>
                        </div>
                        <button
                            @click="remove(drink.id)"
                            class="opacity-0 group-hover:opacity-100 text-slate-400 hover:text-red-500 text-xs transition"
                        >删除</button>
                    </li>
                </ul>
            </section>
            </template>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.toast-enter-active, .toast-leave-active { transition: all 0.3s ease; }
.toast-enter-from, .toast-leave-to { opacity: 0; transform: translateY(-10px); }
</style>
