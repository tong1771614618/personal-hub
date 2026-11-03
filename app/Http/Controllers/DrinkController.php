<?php

namespace App\Http\Controllers;

use App\Models\Drink;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DrinkController extends Controller
{
    public function index(Request $request)
    {
        $drinks = Drink::forUser($request->user()->id)
            ->orderByDesc('drank_at')
            ->limit(200)
            ->get();

        return response()->json($drinks);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'drink_type' => 'required|string|in:' . implode(',', array_keys(Drink::TYPES)),
            'drink_name' => 'nullable|string|max:100',
            'amount_ml' => 'required|integer|min:1|max:10000',
            'alcohol_pct' => 'required|numeric|min:0|max:100',
            'note' => 'nullable|string|max:500',
            'location' => 'nullable|string|max:100',
            'company_count' => 'nullable|integer|min:0|max:99',
            'drank_at' => 'nullable|date',
        ]);

        $data['user_id'] = $request->user()->id;
        $data['pure_alcohol_ml'] = round($data['amount_ml'] * $data['alcohol_pct'] / 100, 2);
        $data['standard_drinks'] = round($data['pure_alcohol_ml'] / Drink::ML_PER_STANDARD_DRINK, 2);
        $data['drank_at'] = $data['drank_at'] ?? now();

        $drink = Drink::create($data);
        return response()->json($drink, 201);
    }

    public function destroy(Request $request, Drink $drink)
    {
        if ($drink->user_id !== $request->user()->id) {
            abort(403, 'Not your drink');
        }
        $drink->delete();
        return response()->json(['ok' => true]);
    }

    /**
     * 统计聚合：本月 / 本周 / 今年 / 按类型 / 近 30 天每日趋势 / 累计
     */
    public function stats(Request $request)
    {
        $userId = $request->user()->id;
        $now = Carbon::now();

        $weekStart = $now->copy()->startOfWeek(Carbon::MONDAY);
        $monthStart = $now->copy()->startOfMonth();
        $yearStart = $now->copy()->startOfYear();

        // 按范围聚合
        $aggRange = function ($from, $to) use ($userId) {
            $row = Drink::forUser($userId)
                ->whereBetween('drank_at', [$from, $to])
                ->selectRaw('COUNT(*) as count, COALESCE(SUM(pure_alcohol_ml),0) as pure_ml, COALESCE(SUM(standard_drinks),0) as std_drinks, COALESCE(SUM(amount_ml),0) as total_ml')
                ->first();
            return [
                'count' => (int) $row->count,
                'pure_alcohol_ml' => round((float) $row->pure_ml, 1),
                'standard_drinks' => round((float) $row->std_drinks, 1),
                'total_ml' => (int) $row->total_ml,
            ];
        };

        $thisWeek = $aggRange($weekStart, $now);
        $thisMonth = $aggRange($monthStart, $now);
        $thisYear = $aggRange($yearStart, $now);

        // 按酒类分布（本月）
        $byType = Drink::forUser($userId)
            ->where('drank_at', '>=', $monthStart)
            ->select('drink_type',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(pure_alcohol_ml) as pure_ml'),
                DB::raw('SUM(standard_drinks) as std_drinks')
            )
            ->groupBy('drink_type')
            ->get()
            ->map(function ($r) {
                $meta = Drink::TYPES[$r->drink_type] ?? ['label' => $r->drink_type, 'emoji' => '🍹'];
                return [
                    'type' => $r->drink_type,
                    'label' => $meta['label'],
                    'emoji' => $meta['emoji'],
                    'count' => (int) $r->count,
                    'pure_alcohol_ml' => round((float) $r->pure_ml, 1),
                    'standard_drinks' => round((float) $r->std_drinks, 1),
                ];
            });

        // 近 30 天每日趋势（按日聚合，无记录补 0）
        $daily = Drink::forUser($userId)
            ->where('drank_at', '>=', $now->copy()->subDays(29)->startOfDay())
            ->selectRaw('DATE(drank_at) as d, COUNT(*) as count, SUM(standard_drinks) as std_drinks')
            ->groupByRaw('DATE(drank_at)')
            ->get()
            ->keyBy('d');

        $trend = [];
        for ($i = 29; $i >= 0; $i--) {
            $d = $now->copy()->subDays($i)->toDateString();
            $row = $daily->get($d);
            $trend[] = [
                'date' => $d,
                'count' => $row ? (int) $row->count : 0,
                'standard_drinks' => $row ? round((float) $row->std_drinks, 1) : 0,
            ];
        }

        // 累计总览
        $lifetime = $aggRange($yearStart->copy()->subYears(10), $now);

        // 本月喝酒天数
        $drinkingDaysThisMonth = Drink::forUser($userId)
            ->where('drank_at', '>=', $monthStart)
            ->selectRaw('COUNT(DISTINCT DATE(drank_at)) as c')
            ->value('c') ?? 0;

        return response()->json([
            'ranges' => [
                'week' => $thisWeek,
                'month' => $thisMonth,
                'year' => $thisYear,
                'lifetime' => $lifetime,
            ],
            'drinking_days_this_month' => (int) $drinkingDaysThisMonth,
            'by_type' => $byType,
            'daily_trend' => $trend,
        ]);
    }
}
