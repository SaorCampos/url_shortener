<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Analytics\Repositories\AnalyticsRepository;
use App\Domain\Analytics\ValueObjects\CountryStats;
use App\Domain\Analytics\ValueObjects\GeoPoint;
use App\Domain\Analytics\ValueObjects\StatPoint;
use App\Domain\Analytics\ValueObjects\TopUrl;
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
            ->map(fn($item) => new StatPoint(
                label: (string) $item->label,
                value: (int) $item->value
            ))
            ->toArray();
    }
    public function getTopUrls(int $limit): array
    {
        return DB::table('short_urls')
            ->select('short_code as code', 'clicks')
            ->orderByDesc('clicks')
            ->limit($limit)
            ->get()
            ->map(fn($item) => new TopUrl(
                code: (string) $item->code,
                clicks: (int) $item->clicks
            ))
            ->toArray();
    }
    public function getCountryStats(string $urlId, int $days): array
    {
        return ClickModel::where('short_url_id', $urlId)
            ->where('created_at', '>=', now()->subDays($days))
            ->select('country_code as country', DB::raw('count(*) as clicks'))
            ->groupBy('country_code')
            ->get()
            ->map(fn($item) => new CountryStats(
                country: (string) $item->country,
                clicks: (int) $item->clicks
            ))
            ->toArray();
    }
    public function getHourHeatmap(string $urlId): array
    {
        $results = ClickModel::where('short_url_id', $urlId)
            ->select([
                DB::raw("to_char(created_at, 'HH24') as hour"),
                DB::raw("count(*) as clicks")
            ])
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();
        return $results->map(fn($item) => new StatPoint(
            label: $item->hour,
            value: (int) $item->clicks
        ))->toArray();
    }
    public function getGeoPoints(string $urlId): array
    {
        return ClickModel::where('short_url_id', $urlId)
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->select(['lat', 'lng', DB::raw('count(*) as intensity')])
            ->groupBy(['lat', 'lng'])
            ->get()
            ->map(fn($item) => new GeoPoint(
                lat: (float) $item->lat,
                lng: (float) $item->lng,
                intensity: (int) $item->intensity
            ))
            ->toArray();
    }
    public function getTrendingStats(int $minutes, int $offsetMinutes = 0): array
    {
        $query = ClickModel::query();
        $query->where('created_at', '>=', now()->subMinutes($minutes));
        if ($offsetMinutes > 0) {
            $query->where('created_at', '<', now()->subMinutes($offsetMinutes));
        }
        return $query->select('short_url_id', DB::raw('count(*) as clicks'))
            ->groupBy('short_url_id')
            ->get()
            ->pluck('clicks', 'short_url_id')
            ->toArray();
    }
}
