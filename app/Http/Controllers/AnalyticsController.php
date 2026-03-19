<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Redis;

class AnalyticsController extends Controller
{
    public function analytics(string $code)
    {
        $now = now();
        $labels = [];
        $values = [];
        for ($i = 59; $i >= 0; $i--) {
            $time = $now->copy()->subMinutes($i);
            $minuteKey = $time->format('YmdHi');
            $labels[] = $time->format('H:i');
            $values[] = (int) Redis::get(
                "shorturl:clicks:minute:{$code}:{$minuteKey}"
            );
        }
        return response()->json([
            'code' => $code,
            'labels' => $labels,
            'values' => $values,
            'total' => (int) Redis::get("shorturl:clicks:total:{$code}")
        ]);
    }
    public function top()
    {
        $raw = Redis::zrevrange("shorturl:top", 0, 9, true);
        $result = [];
        foreach ($raw as $code => $score) {
            $result[] = [
                'code' => (string) $code,
                'clicks' => (int) $score
            ];
        }
        return response()->json($result);
    }
    public function topLastHour()
    {
        $now = now();
        $aggregate = [];
        for ($i = 0; $i < 60; $i++) {
            $minute = $now->copy()->subMinutes($i)->format('YmdHi');
            $top = Redis::zrevrange("shorturl:top:{$minute}", 0, 9, 'WITHSCORES');
            foreach ($top as $code => $score) {
                $aggregate[$code] = ($aggregate[$code] ?? 0) + (int) ($score ?? 0);
            }
        }
        arsort($aggregate);
        return response()->json(array_slice($aggregate, 0, 10, true));
    }
}
