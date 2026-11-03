<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class TaskController extends Controller
{
    /**
     * 看板数据：按 status 分组的 roots + 预加载 children
     */
    public function index(Request $request)
    {
        $tasks = Task::forUser($request->user()->id)
            ->roots()
            ->with(['children' => fn ($q) => $q->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get()
            ->groupBy('status');

        // 确保四个状态都有键
        $board = collect(Task::STATUSES)->mapWithKeys(fn ($s) => [
            $s => $tasks->get($s, collect()),
        ]);

        return response()->json($board);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'in:epic,task,subtask',
            'priority' => 'in:low,normal,high,urgent',
            'due_date' => 'nullable|date',
            'parent_id' => 'nullable|exists:tasks,id',
        ]);

        $data['user_id'] = $request->user()->id;
        $data['status'] = 'todo';
        $data['type'] = $data['type'] ?? 'task';
        $data['priority'] = $data['priority'] ?? 'normal';

        // 子任务自动继承父任务的部分属性
        if (! empty($data['parent_id'])) {
            $parent = Task::forUser($request->user()->id)->findOrFail($data['parent_id']);
            $data['type'] = 'subtask';
        }

        $data['sort_order'] = Task::forUser($request->user()->id)
                ->where('status', 'todo')
                ->max('sort_order') + 1;

        $task = Task::create($data);
        $task->load('children');
        return response()->json($task, 201);
    }

    public function show(Request $request, Task $task)
    {
        $this->authorizeOwner($request, $task);
        $task->load(['children', 'entries']);
        return response()->json($task);
    }

    public function update(Request $request, Task $task)
    {
        $this->authorizeOwner($request, $task);

        $data = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'in:epic,task,subtask',
            'priority' => 'in:low,normal,high,urgent',
            'due_date' => 'nullable|date',
            'parent_id' => 'nullable|exists:tasks,id',
            'sort_order' => 'sometimes|integer|min:0',
        ]);

        $task->update($data);
        $task->load('children');
        return response()->json($task);
    }

    /**
     * 看板拖拽状态流转 + 完成时自动写 entry(type=do)
     */
    public function updateStatus(Request $request, Task $task)
    {
        $this->authorizeOwner($request, $task);

        $data = $request->validate([
            'status' => 'required|in:backlog,todo,in_progress,done',
            'duration_minutes' => 'nullable|integer|min:0',
        ]);

        $newStatus = $data['status'];
        $oldStatus = $task->status;

        $updateData = ['status' => $newStatus];

        // 进入 in_progress 自动记 started_at
        if ($newStatus === 'in_progress' && $oldStatus !== 'in_progress' && ! $task->started_at) {
            $updateData['started_at'] = now();
        }

        // 进入 done 自动记 completed_at + 写 entry
        if ($newStatus === 'done' && $oldStatus !== 'done') {
            $updateData['completed_at'] = now();
            if (! empty($data['duration_minutes'])) {
                $updateData['duration_minutes'] = $data['duration_minutes'];
            }
            $task->entries()->create([
                'user_id' => $request->user()->id,
                'type' => 'do',
                'content' => '完成任务：' . $task->title,
                'duration_minutes' => $data['duration_minutes'] ?? $task->duration_minutes,
                'happened_at' => now(),
            ]);
        }

        $task->update($updateData);
        $task->load('children');
        return response()->json($task);
    }

    public function destroy(Request $request, Task $task)
    {
        $this->authorizeOwner($request, $task);
        $task->delete();
        return response()->json(['ok' => true]);
    }

    private function authorizeOwner(Request $request, Task $task): void
    {
        if ($task->user_id !== $request->user()->id) {
            abort(403, 'Not your task');
        }
    }
}
