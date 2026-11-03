<?php

namespace App\Http\Controllers;

use App\Models\Entry;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReviewController extends Controller
{
    /**
     * 周回顾：本周完成的 tasks + 按 type 聚合的 entries + 按 category_tag 聚合的时长
     */
    public function weekly(Request $request)
    {
        $weekStart = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $weekEnd = Carbon::now()->endOfWeek(Carbon::SUNDAY);
        $userId = $request->user()->id;

        // 本周完成的任务
        $completedTasks = Task::forUser($userId)
            ->whereBetween('completed_at', [$weekStart, $weekEnd])
            ->orderByDesc('completed_at')
            ->get(['id', 'title', 'type', 'priority', 'completed_at', 'duration_minutes']);

        // 按 type 聚合 entries 条数 + 总时长
        $byType = Entry::forUser($userId)
            ->whereBetween('happened_at', [$weekStart, $weekEnd])
            ->selectRaw("type, COUNT(*) as count, COALESCE(SUM(duration_minutes),0) as total_minutes")
            ->groupBy('type')
            ->get()
            ->keyBy('type');

        // 按 category_tag 聚合时长
        $byTag = Entry::forUser($userId)
            ->whereBetween('happened_at', [$weekStart, $weekEnd])
            ->whereNotNull('category_tag')
            ->selectRaw("category_tag, COUNT(*) as count, COALESCE(SUM(duration_minutes),0) as total_minutes")
            ->groupBy('category_tag')
            ->orderByDesc('total_minutes')
            ->get();

        // 本周收藏的资源 (type=resource)
        $resources = Entry::forUser($userId)
            ->byType('resource')
            ->whereBetween('happened_at', [$weekStart, $weekEnd])
            ->orderByDesc('happened_at')
            ->get(['id', 'content', 'resource_url', 'category_tag', 'happened_at']);

        return response()->json([
            'range' => [
                'from' => $weekStart->toDateString(),
                'to' => $weekEnd->toDateString(),
            ],
            'completed_tasks' => $completedTasks,
            'by_type' => $byType,
            'by_tag' => $byTag,
            'resources' => $resources,
            'totals' => [
                'completed_count' => $completedTasks->count(),
                'entry_count' => $byType->sum('count'),
                'total_minutes' => $byType->sum('total_minutes'),
            ],
        ]);
    }
}
