<?php

namespace App\Infrastructure\Cache;

use App\Domain\Analytics\Repositories\AnalyticsRepository;
use App\Domain\Shared\Cache\CacheService;

class CachedAnalyticsRepository implements AnalyticsRepository
{
    public function __construct(
        private AnalyticsRepository $repository,
        private CacheService $cache
    ) {}

    public function getMinuteStats(string $urlId, int $minutes): array
    {
        return $this->cache->remember(
            "analytics:minutes:{$urlId}:{$minutes}",
            fn() => $this->repository->getMinuteStats($urlId, $minutes),
            3600,
        );
    }
    public function getTopUrls(int $limit): array
    {
        return $this->cache->remember(
            "analytics:top:{$limit}",
            fn() => $this->repository->getTopUrls($limit),
            3600,
        );
    }
    public function getCountryStats(string $urlId, int $days): array
    {
        return $this->cache->remember(
            "analytics:countries:{$urlId}:{$days}",
            fn() => $this->repository->getCountryStats($urlId, $days),
            3600,
        );
    }
    public function getHourHeatmap(string $urlId): array
    {
        return $this->cache->remember(
            "analytics:heatmap:{$urlId}",
            fn() => $this->repository->getHourHeatmap($urlId),
            3600,
        );
    }
    public function getGeoPoints(string $urlId): array
    {
        return $this->cache->remember(
            "analytics:geopoints:{$urlId}",
            fn() => $this->repository->getGeoPoints($urlId),
            3600,
        );
    }
    public function getTrendingStats(int $minutes): array
    {
        return $this->cache->remember(
            "analytics:trending:{$minutes}",
            fn() => $this->repository->getTrendingStats($minutes),
            3600,
        );
    }
}
