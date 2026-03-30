<?php

namespace App\Infrastructure\Cache;

use App\Domain\Analytics\Repositories\AnalyticsRepository;
use App\Domain\Shared\Cache\CacheService;

class CachedAnalyticsRepository implements AnalyticsRepository
{
    private const TTL_SHORT = 15;
    private const TTL_MEDIUM = 30;
    private const TTL_LONG = 60;

    public function __construct(
        private AnalyticsRepository $repository,
        private CacheService $cache
    ) {}

    public function getMinuteStats(string $urlId, int $minutes): array
    {
        return $this->cache->remember(
            "analytics:minutes:{$urlId}:{$minutes}",
            fn() => $this->repository->getMinuteStats($urlId, $minutes),
            self::TTL_LONG,
        );
    }
    public function getTopUrls(int $limit): array
    {
        return $this->cache->remember(
            "analytics:top:{$limit}",
            fn() => $this->repository->getTopUrls($limit),
            self::TTL_LONG,
        );
    }
    public function getCountryStats(string $urlId, int $days): array
    {
        return $this->cache->remember(
            "analytics:countries:{$urlId}:{$days}",
            fn() => $this->repository->getCountryStats($urlId, $days),
            self::TTL_LONG,
        );
    }
    public function getHourHeatmap(string $urlId): array
    {
        return $this->cache->remember(
            "analytics:heatmap:{$urlId}",
            fn() => $this->repository->getHourHeatmap($urlId),
            self::TTL_MEDIUM,
        );
    }
    public function getGeoPoints(string $urlId): array
    {
        return $this->cache->remember(
            "analytics:geopoints:{$urlId}",
            fn() => $this->repository->getGeoPoints($urlId),
            self::TTL_SHORT,
        );
    }
    public function getTrendingStats(int $minutes, int $offsetMinutes = 0): array
    {
        return $this->cache->remember(
            "analytics:trending:{$minutes}:{$offsetMinutes}",
            fn() => $this->repository->getTrendingStats($minutes, $offsetMinutes),
            self::TTL_LONG,
        );
    }
}
