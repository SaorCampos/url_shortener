<?php

namespace App\Domain\Analytics\Repositories;

interface AnalyticsRepository
{
    public function getMinuteStats(string $urlId, int $minutes): array;
    public function getTopUrls(int $limit): array;
    public function getCountryStats(string $urlId, int $days): array;
    public function getHourHeatmap(string $urlId): array;
    public function getGeoPoints(string $urlId): array;
    public function getTrendingStats(int $minutes, int $offsetMinutes = 0): array;
}
