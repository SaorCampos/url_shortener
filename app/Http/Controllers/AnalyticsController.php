<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Redis;

class AnalyticsController extends Controller
{
    public function analytics(string $code)
    {
        $now = now();
        $minutes = [];
        for ($i = 0; $i < 60; $i++) {
            $minute = $now->copy()->subMinutes($i)->format('YmdHi');
            $key = "shorturl:clicks:minute:{$code}:{$minute}";
            $minutes[$minute] = (int) Redis::get($key);
        }
        return response()->json([
            'code' => $code,
            'last_hour' => $minutes,
            'total' => (int) Redis::get("shorturl:clicks:total:{$code}")
        ]);
    }
    public function top()
    {
        $top = Redis::zrevrange("shorturl:top", 0, 9, 'WITHSCORES');
        return response()->json($top);
    }
}
