<?php

namespace App\Domain\Analytics\Repositories;

use App\Domain\Analytics\ValueObjects\CountryStats;
use App\Domain\Analytics\ValueObjects\GeoPoint;
use App\Domain\Analytics\ValueObjects\StatPoint;
use App\Domain\Analytics\ValueObjects\TopUrl;

interface AnalyticsRepository
{
    /** @return StatPoint[] */
    public function getMinuteStats(string $urlId, int $minutes): array;
    /** @return TopUrl[] */
    public function getTopUrls(int $limit): array;
    /** @return CountryStats[] */
    public function getCountryStats(string $urlId, int $days): array;
    /** @return StatPoint[] */
    public function getHourHeatmap(string $urlId): array;
    /** @return GeoPoint[] */
    public function getGeoPoints(string $urlId): array;
    public function getTrendingStats(int $minutes, int $offsetMinutes = 0): array;
}
