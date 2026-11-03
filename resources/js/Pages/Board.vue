<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import draggable from 'vuedraggable';
import { api, TASK_STATUSES, PRIORITIES, formatDate } from '@/api.js';

const columns = ref({ backlog: [], todo: [], in_progress: [], done: [] });
const expandedEpic = ref({});
const showForm = ref(false);
const editingId = ref(null);
const editForm = ref({ title: '', description: '', priority: 'normal', due_date: '' });
const form = ref({ title: '', description: '', type: 'task', priority: 'normal', due_date: '', parent_id: null });
const submitting = ref(false);

const statusKeys = Object.keys(TASK_STATUSES);

async function load() {
    columns.value = await api.getTasks();
}

async function createTask() {
    if (!form.value.title.trim()) return;
    submitting.value = true;
    try {
        await api.createTask(form.value);
        form.value = { title: '', description: '', type: 'task', priority: 'normal', due_date: '', parent_id: null };
        showForm.value = false;
        await load();
    } finally {
        submitting.value = false;
    }
}

async function onDragEnd(evt) {
    const task = evt.item.__draggable_context?.element;
    if (!task) return;
    const newStatus = evt.to.dataset.status;
    if (!newStatus || task.status === newStatus) return;
    task.status = newStatus;

    let duration_minutes = null;
    if (newStatus === 'done') {
        const ans = prompt('这次花了多少分钟？（可留空）', '');
        if (ans && !isNaN(parseInt(ans))) duration_minutes = parseInt(ans);
    }

    try {
        await api.updateTaskStatus(task.id, { status: newStatus, duration_minutes });
        await load();
    } catch (e) {
        alert('状态更新失败');
        await load();
    }
}

async function deleteTask(id) {
    if (!confirm('删除这个任务？')) return;
    await api.deleteTask(id);
    await load();
}

function toggleEpic(id) {
    expandedEpic.value[id] = !expandedEpic.value[id];
}

function startEdit(task) {
    editingId.value = task.id;
    editForm.value = {
        title: task.title,
        description: task.description || '',
        priority: task.priority,
        due_date: task.due_date ? task.due_date.slice(0, 10) : '',
    };
}

async function saveEdit(task) {
    try {
        await api.updateTask(task.id, editForm.value);
        editingId.value = null;
        await load();
    } catch (e) {
        alert('保存失败');
    }
}

function cancelEdit() {
    editingId.value = null;
}

onMounted(load);
</script>

<template>
    <Head title="Board" />
    <AuthenticatedLayout>
        <div class="px-4 py-6 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h1 class="text-2xl font-semibold text-slate-900 dark:text-slate-50">📋 任务看板</h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">拖拽卡片切换状态</p>
                </div>
                <button
                    @click="showForm = !showForm"
                    class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 transition"
                >
                    {{ showForm ? '取消' : '+ 新任务' }}
                </button>
            </div>

            <!-- 新建表单 -->
            <div v-if="showForm" class="mb-5 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <input v-model="form.title" placeholder="任务标题" class="rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-indigo-400 focus:outline-none dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 md:col-span-2" />
                    <textarea v-model="form.description" placeholder="描述（可选）" rows="2" class="rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-indigo-400 focus:outline-none dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 md:col-span-2"></textarea>
                    <select v-model="form.type" class="rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100">
                        <option value="epic">史诗 Epic</option>
                        <option value="task">任务 Task</option>
                        <option value="subtask">子任务</option>
                    </select>
                    <select v-model="form.priority" class="rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100">
                        <option v-for="(meta, key) in PRIORITIES" :key="key" :value="key">{{ meta.label }}</option>
                    </select>
                    <input v-model="form.due_date" type="date" class="rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100" />
                    <button @click="createTask" :disabled="submitting" class="rounded-lg bg-indigo-600 text-white px-4 py-2 text-sm hover:bg-indigo-700 disabled:opacity-50 dark:bg-indigo-500 dark:hover:bg-indigo-600">
                        创建
                    </button>
                </div>
            </div>

            <!-- 看板 -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div
                    v-for="status in statusKeys"
                    :key="status"
                    class="rounded-2xl bg-slate-100 p-3 min-h-[400px] dark:bg-slate-800/50"
                >
                    <div :class="['flex items-center justify-between rounded-lg px-3 py-2 mb-3', TASK_STATUSES[status].header]">
                        <h3 class="text-sm font-semibold text-slate-700">
                            {{ TASK_STATUSES[status].label }}
                        </h3>
                        <span class="text-xs text-slate-600 bg-white/60 rounded-full px-2 py-0.5 dark:bg-slate-900/40 dark:text-slate-300">
                            {{ columns[status]?.length || 0 }}
                        </span>
                    </div>

                    <draggable
                        :list="columns[status]"
                        group="tasks"
                        item-key="id"
                        :data-status="status"
                        class="space-y-2 min-h-[200px]"
                        ghost-class="opacity-30"
                        @end="onDragEnd"
                    >
                        <template #item="{ element: task }">
                            <div
                                class="group rounded-xl bg-white p-3 shadow-sm border border-slate-100 hover:shadow-md transition dark:bg-slate-800 dark:border-slate-700"
                                :class="{ 'cursor-move': editingId !== task.id }"
                            >
                                <!-- 编辑模式 -->
                                <div v-if="editingId === task.id" class="space-y-2">
                                    <input v-model="editForm.title" class="w-full rounded-lg border border-slate-300 px-2 py-1.5 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100" />
                                    <textarea v-model="editForm.description" rows="2" class="w-full rounded-lg border border-slate-300 px-2 py-1.5 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"></textarea>
                                    <div class="flex gap-2">
                                        <select v-model="editForm.priority" class="rounded-lg border border-slate-300 px-2 py-1 text-xs dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100">
                                            <option v-for="(meta, key) in PRIORITIES" :key="key" :value="key">{{ meta.label }}</option>
                                        </select>
                                        <input v-model="editForm.due_date" type="date" class="rounded-lg border border-slate-300 px-2 py-1 text-xs dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100" />
                                    </div>
                                    <div class="flex justify-end gap-2">
                                        <button @click="cancelEdit" class="text-xs px-2 py-1 rounded text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-700">取消</button>
                                        <button @click="saveEdit(task)" class="text-xs px-3 py-1 rounded bg-indigo-600 text-white hover:bg-indigo-700">保存</button>
                                    </div>
                                </div>

                                <!-- 显示模式 -->
                                <div v-else @dblclick="startEdit(task)">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2 mb-1">
                                                <span
                                                    v-if="task.type === 'epic'"
                                                    @click.stop="toggleEpic(task.id)"
                                                    class="text-xs cursor-pointer"
                                                >
                                                    {{ expandedEpic[task.id] ? '▼' : '▶' }}
                                                </span>
                                                <span
                                                    :class="[
                                                        'text-xs px-1.5 py-0.5 rounded font-medium',
                                                        task.type === 'epic' ? 'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-300' :
                                                        task.type === 'subtask' ? 'bg-slate-100 text-slate-500 dark:bg-slate-700 dark:text-slate-300' :
                                                        'bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-300',
                                                    ]"
                                                >{{ task.type === 'epic' ? 'EPIC' : task.type === 'subtask' ? 'SUB' : 'TASK' }}</span>
                                                <span :class="['text-xs font-semibold', PRIORITIES[task.priority].color]">
                                                    {{ PRIORITIES[task.priority].label }}
                                                </span>
                                            </div>
                                            <h4 class="text-sm font-medium text-slate-800 break-words dark:text-slate-100">{{ task.title }}</h4>
                                            <p v-if="task.description" class="mt-1 text-xs text-slate-500 line-clamp-2 dark:text-slate-400">{{ task.description }}</p>
                                            <div class="mt-2 flex items-center gap-2 text-xs text-slate-400 dark:text-slate-500">
                                                <span v-if="task.due_date">📅 {{ formatDate(task.due_date) }}</span>
                                                <span v-if="task.children?.length">{{ task.children.length }} 子任务</span>
                                            </div>
                                        </div>
                                        <div class="opacity-0 group-hover:opacity-100 flex flex-col gap-1 transition">
                                            <button @click.stop="startEdit(task)" class="text-slate-400 hover:text-indigo-500 text-xs">✎</button>
                                            <button @click.stop="deleteTask(task.id)" class="text-slate-400 hover:text-red-500 text-xs">✕</button>
                                        </div>
                                    </div>

                                    <!-- Epic 展开子任务 -->
                                    <div v-if="task.type === 'epic' && expandedEpic[task.id] && task.children?.length" class="mt-3 ml-3 border-l-2 border-slate-200 dark:border-slate-700 pl-3 space-y-1">
                                        <div v-for="sub in task.children" :key="sub.id" class="text-xs text-slate-600 dark:text-slate-300 flex items-center gap-2">
                                            <span :class="['h-1.5 w-1.5 rounded-full', sub.status === 'done' ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-slate-600']"></span>
                                            <span :class="{ 'line-through text-slate-400 dark:text-slate-500': sub.status === 'done' }">{{ sub.title }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </draggable>

                    <!-- 列空状态 -->
                    <div
                        v-if="!columns[status] || columns[status].length === 0"
                        class="rounded-lg border border-dashed border-slate-300 dark:border-slate-700 py-8 text-center text-xs text-slate-400 dark:text-slate-500"
                    >
                        拖到这里 / 点击"+ 新任务"
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
