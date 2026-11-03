import axios from 'axios';

// axios 已经在 bootstrap.js 里设置了 X-Requested-With
// Laravel 会自动识别 Accept: application/json 返回 JSON
axios.defaults.headers.common['Accept'] = 'application/json';

export const api = {
    // Tasks
    getTasks: () => axios.get('/api/tasks').then(r => r.data),
    createTask: (data) => axios.post('/api/tasks', data).then(r => r.data),
    updateTask: (id, data) => axios.put(`/api/tasks/${id}`, data).then(r => r.data),
    updateTaskStatus: (id, data) => axios.patch(`/api/tasks/${id}/status`, data).then(r => r.data),
    deleteTask: (id) => axios.delete(`/api/tasks/${id}`).then(r => r.data),

    // Entries
    getEntries: (params = {}) => axios.get('/api/entries', { params }).then(r => r.data),
    createEntry: (data) => axios.post('/api/entries', data).then(r => r.data),
    updateEntry: (id, data) => axios.put(`/api/entries/${id}`, data).then(r => r.data),
    deleteEntry: (id) => axios.delete(`/api/entries/${id}`).then(r => r.data),

    // Category tags
    getTags: () => axios.get('/api/category-tags').then(r => r.data),
    createTag: (data) => axios.post('/api/category-tags', data).then(r => r.data),
    deleteTag: (id) => axios.delete(`/api/category-tags/${id}`).then(r => r.data),

    // Review
    getWeeklyReview: () => axios.get('/api/review/weekly').then(r => r.data),

    // Drinks
    getDrinks: () => axios.get('/api/drinks').then(r => r.data),
    createDrink: (data) => axios.post('/api/drinks', data).then(r => r.data),
    deleteDrink: (id) => axios.delete(`/api/drinks/${id}`).then(r => r.data),
    getDrinkStats: () => axios.get('/api/drinks/stats').then(r => r.data),

    // Users (admin)
    getUsers: () => axios.get('/api/users').then(r => r.data),
    createUser: (data) => axios.post('/api/users', data).then(r => r.data),
    updateUserRole: (id, role) => axios.patch(`/api/users/${id}/role`, { role }).then(r => r.data),
    deleteUser: (id) => axios.delete(`/api/users/${id}`).then(r => r.data),
};

// Entry type 配色与中文标签
export const ENTRY_TYPES = {
    thought:  { label: '想法', color: 'bg-purple-100 text-purple-700',  dot: 'bg-purple-500' },
    dream:    { label: '梦',   color: 'bg-pink-100 text-pink-700',     dot: 'bg-pink-500' },
    learn:    { label: '学习', color: 'bg-blue-100 text-blue-700',     dot: 'bg-blue-500' },
    do:       { label: '完成', color: 'bg-green-100 text-green-700',   dot: 'bg-green-500' },
    idea:     { label: '灵感', color: 'bg-yellow-100 text-yellow-700', dot: 'bg-yellow-500' },
    resource: { label: '资源', color: 'bg-indigo-100 text-indigo-700', dot: 'bg-indigo-500' },
};

export const TASK_STATUSES = {
    backlog:     { label: '待定', color: 'bg-slate-100 text-slate-700',    header: 'bg-slate-200' },
    todo:        { label: '待办', color: 'bg-blue-100 text-blue-700',      header: 'bg-blue-200' },
    in_progress: { label: '进行', color: 'bg-amber-100 text-amber-700',    header: 'bg-amber-200' },
    done:        { label: '完成', color: 'bg-emerald-100 text-emerald-700',header: 'bg-emerald-200' },
};

export const PRIORITIES = {
    low:    { label: '低', color: 'text-slate-500' },
    normal: { label: '中', color: 'text-slate-700' },
    high:   { label: '高', color: 'text-orange-600' },
    urgent: { label: '急', color: 'text-red-600' },
};

export const DRINK_TYPES = {
    beer:     { label: '啤酒',   emoji: '🍺', defaultPct: 5.0,  defaultMl: 500, color: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300' },
    wine:     { label: '红酒',   emoji: '🍷', defaultPct: 13.0, defaultMl: 150, color: 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300' },
    sake:     { label: '清酒',   emoji: '🍶', defaultPct: 15.0, defaultMl: 180, color: 'bg-stone-100 text-stone-700 dark:bg-stone-800 dark:text-stone-200' },
    soju:     { label: '烧酒',   emoji: '🥃', defaultPct: 17.0, defaultMl: 360, color: 'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-300' },
    baijiu:   { label: '白酒',   emoji: '🥃', defaultPct: 52.0, defaultMl: 100, color: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300' },
    whisky:   { label: '威士忌', emoji: '🥃', defaultPct: 40.0, defaultMl: 60,  color: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300' },
    cocktail: { label: '鸡尾酒', emoji: '🍸', defaultPct: 12.0, defaultMl: 200, color: 'bg-fuchsia-100 text-fuchsia-700 dark:bg-fuchsia-900/30 dark:text-fuchsia-300' },
    other:    { label: '其他',   emoji: '🍹', defaultPct: 10.0, defaultMl: 200, color: 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-200' },
};

// 标准杯数（1 标准杯 = 10ml 纯酒精，WHO 标准）
export function standardDrinksText(n) {
    const v = Number(n);
    if (!v) return '—';
    return v.toFixed(1) + ' 杯';
}

// 把酒量等级（标准杯）映射为提示文案（按单次饮酒）
export function drinkingLevelText(stdDrinks) {
    const n = Number(stdDrinks) || 0;
    if (n <= 0) return { text: '—', color: 'text-slate-400' };
    if (n < 2) return { text: '小酌', color: 'text-emerald-600 dark:text-emerald-400' };
    if (n < 5) return { text: '适量', color: 'text-blue-600 dark:text-blue-400' };
    if (n < 8) return { text: '偏多', color: 'text-amber-600 dark:text-amber-400' };
    return { text: '过量', color: 'text-red-600 dark:text-red-400' };
}

export function formatDateTime(dt) {
    if (!dt) return '';
    const d = new Date(dt);
    return d.toLocaleString('zh-CN', { month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' });
}

export function formatDate(dt) {
    if (!dt) return '';
    return new Date(dt).toLocaleDateString('zh-CN');
}

export function relativeTime(dt) {
    if (!dt) return '';
    const diff = (Date.now() - new Date(dt).getTime()) / 1000;
    if (diff < 60) return '刚刚';
    if (diff < 3600) return Math.floor(diff / 60) + ' 分钟前';
    if (diff < 86400) return Math.floor(diff / 3600) + ' 小时前';
    if (diff < 604800) return Math.floor(diff / 86400) + ' 天前';
    return formatDate(dt);
}

export function minutesToText(m) {
    if (!m) return '';
    if (m < 60) return `${m}分钟`;
    const h = Math.floor(m / 60);
    const mm = m % 60;
    return mm ? `${h}小时${mm}分钟` : `${h}小时`;
}
