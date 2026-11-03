<?php

namespace App\Http\Controllers;

use App\Models\Entry;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class EntryController extends Controller
{
    /**
     * 条目流：按 happened_at 倒序，支持 type / tag / 日期范围筛选
     */
    public function index(Request $request)
    {
        $query = Entry::forUser($request->user()->id)
            ->with('task:id,title')
            ->orderByDesc('happened_at');

        if ($type = $request->query('type')) {
            $query->byType($type);
        }
        if ($tag = $request->query('tag')) {
            $query->where('category_tag', $tag);
        }
        if ($from = $request->query('from')) {
            $query->where('happened_at', '>=', Carbon::parse($from)->startOfDay());
        }
        if ($to = $request->query('to')) {
            $query->where('happened_at', '<=', Carbon::parse($to)->endOfDay());
        }

        $perPage = (int) $request->query('per_page', 50);
        return response()->json($query->paginate($perPage));
    }

    /**
     * 快速记录：content + type 必填，其余可选
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => 'required|in:thought,dream,learn,do,idea,resource',
            'content' => 'required|string|max:10000',
            'category_tag' => 'nullable|string|max:50',
            'resource_url' => 'nullable|url|max:2000',
            'task_id' => 'nullable|exists:tasks,id',
            'duration_minutes' => 'nullable|integer|min:0',
            'happened_at' => 'nullable|date',
        ]);

        $data['user_id'] = $request->user()->id;
        $data['happened_at'] = $data['happened_at'] ?? now();

        // task_id 需验证归属
        if (! empty($data['task_id'])) {
            $task = \App\Models\Task::forUser($request->user()->id)->find($data['task_id']);
            if (! $task) {
                abort(422, 'Task not owned by you');
            }
        }

        $entry = Entry::create($data);
        $entry->load('task:id,title');
        return response()->json($entry, 201);
    }

    public function update(Request $request, Entry $entry)
    {
        if ($entry->user_id !== $request->user()->id) {
            abort(403, 'Not your entry');
        }

        $data = $request->validate([
            'type' => 'in:thought,dream,learn,do,idea,resource',
            'content' => 'sometimes|required|string|max:10000',
            'category_tag' => 'nullable|string|max:50',
            'resource_url' => 'nullable|url|max:2000',
            'task_id' => 'nullable|exists:tasks,id',
            'duration_minutes' => 'nullable|integer|min:0',
            'happened_at' => 'nullable|date',
        ]);

        $entry->update($data);
        $entry->load('task:id,title');
        return response()->json($entry);
    }

    public function destroy(Request $request, Entry $entry)
    {
        if ($entry->user_id !== $request->user()->id) {
            abort(403, 'Not your entry');
        }
        $entry->delete();
        return response()->json(['ok' => true]);
    }
}
