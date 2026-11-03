<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import { api } from '@/api.js';

const me = computed(() => usePage().props.auth.user);

// 分类标签
const tags = ref([]);
const newTag = ref({ name: '', color: '#6366f1' });
const presetColors = ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#ec4899', '#64748b'];

// 用户管理（admin only）
const users = ref([]);
const newUser = ref({ name: '', email: '', password: '', role: 'member' });

async function loadTags() {
    tags.value = await api.getTags();
}

async function addTag() {
    if (!newTag.value.name.trim()) return;
    await api.createTag(newTag.value);
    newTag.value = { name: '', color: '#6366f1' };
    await loadTags();
}

async function deleteTag(id) {
    if (!confirm('删除此分类？')) return;
    await api.deleteTag(id);
    await loadTags();
}

async function loadUsers() {
    try {
        users.value = await api.getUsers();
    } catch (e) {}
}

async function addUser() {
    if (!newUser.value.email) return;
    try {
        await api.createUser(newUser.value);
        newUser.value = { name: '', email: '', password: '', role: 'member' };
        await loadUsers();
    } catch (e) {
        alert(e.response?.data?.message || '创建失败');
    }
}

async function toggleRole(user) {
    const newRole = user.role === 'admin' ? 'member' : 'admin';
    try {
        await api.updateUserRole(user.id, newRole);
        await loadUsers();
    } catch (e) {
        alert(e.response?.data?.message || '操作失败');
    }
}

async function deleteUser(id) {
    if (!confirm('删除该用户？其任务/条目会一起删除')) return;
    await api.deleteUser(id);
    await loadUsers();
}

onMounted(async () => {
    await loadTags();
    await loadUsers();
});
</script>

<template>
    <Head title="Settings" />
    <AuthenticatedLayout>
        <div class="mx-auto max-w-3xl px-4 py-6 sm:px-6 lg:px-8 space-y-6">
            <div class="mb-2">
                <h1 class="text-2xl font-semibold text-slate-900 dark:text-slate-50">⚙️ 设置</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">管理分类标签和用户</p>
            </div>

            <!-- 分类标签 -->
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                <h2 class="text-base font-semibold text-slate-700 dark:text-slate-200 mb-4">分类标签</h2>

                <div class="flex flex-wrap gap-2 mb-4">
                    <div
                        v-for="tag in tags"
                        :key="tag.id"
                        class="group flex items-center gap-2 rounded-full px-3 py-1.5 text-sm text-white shadow-sm"
                        :style="{ backgroundColor: tag.color }"
                    >
                        <span>{{ tag.name }}</span>
                        <button @click="deleteTag(tag.id)" class="opacity-70 hover:opacity-100 text-xs">✕</button>
                    </div>
                    <div v-if="tags.length === 0" class="text-sm text-slate-400 dark:text-slate-500">还没有分类标签</div>
                </div>

                <div class="flex items-center gap-2 flex-wrap">
                    <input
                        v-model="newTag.name"
                        placeholder="新分类名称"
                        class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm flex-1 min-w-[150px] focus:outline-none focus:border-indigo-400 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                        @keydown.enter="addTag"
                    />
                    <div class="flex gap-1">
                        <button
                            v-for="c in presetColors"
                            :key="c"
                            @click="newTag.color = c"
                            :class="['h-6 w-6 rounded-full transition', newTag.color === c ? 'ring-2 ring-offset-2 ring-slate-400 dark:ring-offset-slate-800' : '']"
                            :style="{ backgroundColor: c }"
                        ></button>
                    </div>
                    <button @click="addTag" class="rounded-lg bg-indigo-600 px-4 py-1.5 text-sm text-white hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600">添加</button>
                </div>
            </section>

            <!-- 用户管理 -->
            <section v-if="me?.role === 'admin'" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                <h2 class="text-base font-semibold text-slate-700 dark:text-slate-200 mb-4">用户管理</h2>

                <ul class="space-y-2 mb-5">
                    <li
                        v-for="u in users"
                        :key="u.id"
                        class="flex items-center gap-3 rounded-xl border border-slate-100 p-3 dark:border-slate-700"
                    >
                        <div class="h-9 w-9 rounded-full bg-gradient-to-br from-indigo-400 to-purple-500 flex items-center justify-center text-white text-sm font-semibold">
                            {{ u.name?.charAt(0)?.toUpperCase() || 'U' }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-slate-800 dark:text-slate-100 truncate">{{ u.name }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 truncate">{{ u.email }}</p>
                        </div>
                        <span
                            :class="[
                                'text-xs px-2 py-0.5 rounded-full',
                                u.role === 'admin' ? 'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300',
                            ]"
                        >{{ u.role }}</span>
                        <button
                            @click="toggleRole(u)"
                            class="text-xs text-indigo-600 hover:underline dark:text-indigo-400"
                        >{{ u.role === 'admin' ? '降级' : '升级' }}</button>
                        <button
                            @click="deleteUser(u.id)"
                            :disabled="u.id === me?.id"
                            class="text-xs text-red-500 hover:underline disabled:opacity-30"
                        >删除</button>
                    </li>
                </ul>

                <div class="border-t border-slate-100 dark:border-slate-700 pt-4">
                    <h3 class="text-sm font-medium text-slate-600 dark:text-slate-300 mb-2">添加用户</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                        <input v-model="newUser.name" placeholder="姓名" class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100" />
                        <input v-model="newUser.email" placeholder="邮箱" type="email" class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100" />
                        <input v-model="newUser.password" placeholder="密码（至少6位）" type="password" class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100" />
                        <select v-model="newUser.role" class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100">
                            <option value="member">成员</option>
                            <option value="admin">管理员</option>
                        </select>
                    </div>
                    <button @click="addUser" class="mt-2 rounded-lg bg-indigo-600 px-4 py-1.5 text-sm text-white hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600">创建用户</button>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
