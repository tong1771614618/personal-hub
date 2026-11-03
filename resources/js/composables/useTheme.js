import { ref, watch } from 'vue';

// 全局单例：所有组件共享同一个 theme ref
const THEME_KEY = 'personal_hub_theme';

function getInitialTheme() {
    if (typeof window === 'undefined') return 'light';
    const saved = window.localStorage.getItem(THEME_KEY);
    if (saved === 'dark' || saved === 'light') return saved;
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

const theme = ref(getInitialTheme());

// 初始化时立刻应用到 document（避免 FOUC，需在 setup 前调用）
export function applyThemeOnDocument() {
    if (typeof document === 'undefined') return;
    if (theme.value === 'dark') {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
}

// 监听变化：自动同步 localStorage + DOM
watch(
    theme,
    (val) => {
        if (typeof window === 'undefined') return;
        window.localStorage.setItem(THEME_KEY, val);
        if (val === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    },
    { immediate: true },
);

export function useTheme() {
    const isDark = () => theme.value === 'dark';
    const toggle = () => {
        theme.value = theme.value === 'dark' ? 'light' : 'dark';
    };
    const setTheme = (val) => {
        if (val === 'dark' || val === 'light') theme.value = val;
    };
    return { theme, isDark, toggle, setTheme };
}

// 时段问候
export function getGreeting() {
    const h = new Date().getHours();
    if (h < 5) return { text: '夜深了', emoji: '🌙' };
    if (h < 11) return { text: '早安', emoji: '☀️' };
    if (h < 14) return { text: '中午好', emoji: '🌤️' };
    if (h < 18) return { text: '下午好', emoji: '🌞' };
    if (h < 22) return { text: '晚上好', emoji: '🌆' };
    return { text: '夜深了', emoji: '🌙' };
}
