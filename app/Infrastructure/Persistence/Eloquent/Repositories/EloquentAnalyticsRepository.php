<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Analytics\Repositories\AnalyticsRepository;
use App\Infrastructure\Persistence\Eloquent\Models\ClickModel;
use Illuminate\Support\Facades\DB;

class EloquentAnalyticsRepository implements AnalyticsRepository
{
    public function getMinuteStats(string $urlId, int $minutes): array
    {
        return ClickModel::where('short_url_id', $urlId)
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->select([
                DB::raw("to_char(created_at, 'HH24:MI') as label"),
                DB::raw("count(*) as value")
            ])
            ->groupBy('label')
            ->orderBy('label')
            ->get()
            ->toArray();
    }

    public function getTopUrls(int $limit): array
    {
        return DB::table('short_urls')
            ->select('short_code as code', 'clicks')
            ->orderByDesc('clicks')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public function getCountryStats(string $urlId, int $days): array
    {
        return ClickModel::where('short_url_id', $urlId)
            ->where('created_at', '>=', now()->subDays($days))
            ->select('country_code as country', DB::raw('count(*) as clicks'))
            ->groupBy('country_code')
            ->get()
            ->toArray();
    }

    public function getHourHeatmap(string $urlId): array
    {
        return ClickModel::where('short_url_id', $urlId)
            ->select([
                DB::raw("to_char(created_at, 'HH24') as hour"),
                DB::raw("count(*) as clicks")
            ])
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->pluck('clicks', 'hour')
            ->toArray();
    }

    public function getGeoPoints(string $urlId): array
    {
        return ClickModel::where('short_url_id', $urlId)
            ->whereNotNull('ip')
            ->select('country_code', DB::raw('count(*) as intensity'))
            ->groupBy('country_code')
            ->get()
            ->toArray();
    }

    public function getTrendingStats(int $minutes): array
    {
        return ClickModel::where('created_at', '>=', now()->subMinutes($minutes))
            ->select('short_url_id', DB::raw('count(*) as clicks'))
            ->groupBy('short_url_id')
            ->get()
            ->pluck('clicks', 'short_url_id')
            ->toArray();
    }
}
