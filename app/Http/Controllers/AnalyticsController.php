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
        $currentMinutes = [];
        $previousMinutes = [];
        // last 60 min
        for ($i = 0; $i < 60; $i++) {
            $currentMinutes[] = $now->copy()->subMinutes($i)->format('YmdHi');
        }
        // 60 min previous
        for ($i = 60; $i < 120; $i++) {
            $previousMinutes[] = $now->copy()->subMinutes($i)->format('YmdHi');
        }
        $current = $this->aggregateMinutes($currentMinutes);
        $previous = $this->aggregateMinutes($previousMinutes);
        $result = [];
        foreach ($current as $code => $clicksNow) {
            if ($clicksNow < 3) continue;
            $clicksBefore = $previous[$code] ?? 0;
            $trend = $this->calculateTrend($clicksNow, $clicksBefore);
            $viral = $this->detectSpike($code);
            $result[] = [
                'code' => $code,
                'clicks' => $clicksNow,
                'trend' => $trend,
                'viral' => $viral
            ];
        }
        // order by clicks
        usort($result, fn($a, $b) => $b['clicks'] <=> $a['clicks']);
        // add position
        foreach ($result as $i => $item) {
            $result[$i]['position'] = $i + 1;
        }
        return response()->json(array_slice($result, 0, 10));
    }


    private function aggregateMinutes(array $minutes): array
    {
        $aggregate = [];
        foreach ($minutes as $minute) {
            $top = Redis::zrevrange("shorturl:top:{$minute}", 0, 9, true);
            foreach ($top as $code => $score) {
                $aggregate[(string)$code] = ($aggregate[$code] ?? 0) + (int)$score;
            }
        }
        return $aggregate;
    }
    private function calculateTrend(int $current, int $previous): string
    {
        if ($previous === 0) {
            return $current > 0 ? '+100%' : '0%';
        }
        $change = (($current - $previous) / $previous) * 100;
        return ($change >= 0 ? '+' : '') . round($change, 1) . '%';
    }
    private function detectSpike(string $code): bool
    {
        $now = now();
        $last5 = 0;
        for ($i = 0; $i < 5; $i++) {
            $minute = $now->copy()->subMinutes($i)->format('YmdHi');
            $last5 += (int) Redis::get("shorturl:clicks:minute:{$code}:{$minute}");
        }
        $last60 = 0;
        for ($i = 0; $i < 60; $i++) {
            $minute = $now->copy()->subMinutes($i)->format('YmdHi');
            $last60 += (int) Redis::get("shorturl:clicks:minute:{$code}:{$minute}");
        }
        $avg = $last60 / 60;
        return $avg > 0 && $last5 > ($avg * 3);
    }
}
