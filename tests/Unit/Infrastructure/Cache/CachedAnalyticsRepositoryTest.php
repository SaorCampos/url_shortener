<?php

namespace Tests\Unit\Infrastructure\Cache;

use App\Domain\Analytics\Repositories\AnalyticsRepository;
use App\Domain\Shared\Cache\CacheService;
use App\Infrastructure\Cache\CachedAnalyticsRepository;
use Tests\TestCase;
use Mockery;
use Mockery\MockInterface;

class CachedAnalyticsRepositoryTest extends TestCase
{
    private AnalyticsRepository|MockInterface $innerRepository;
    private CacheService|MockInterface $cacheService;
    private CachedAnalyticsRepository $cachedRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->innerRepository = Mockery::mock(AnalyticsRepository::class);
        $this->cacheService = Mockery::mock(CacheService::class);
        $this->cachedRepository = new CachedAnalyticsRepository(
            $this->innerRepository,
            $this->cacheService
        );
    }

    public function test_getMinuteStats_uses_correct_cache_params()
    {
        $urlId = 'url-123';
        $minutes = 15;
        $expected = [['label' => '10:00', 'value' => 5]];
        $this->cacheService->shouldReceive('remember')
            ->once()
            ->with("analytics:minutes:{$urlId}:{$minutes}", Mockery::any(), 5)
            ->andReturn($expected);
        $result = $this->cachedRepository->getMinuteStats($urlId, $minutes);
        $this->assertEquals($expected, $result);
    }

    public function test_getTopUrls_uses_correct_cache_params()
    {
        $limit = 5;
        $this->cacheService->shouldReceive('remember')
            ->once()
            ->with("analytics:top:{$limit}", Mockery::any(), 60) // TTL_LONG
            ->andReturn([]);
        $this->cachedRepository->getTopUrls($limit);
    }

    public function test_getCountryStats_uses_correct_cache_params()
    {
        $urlId = 'url-123';
        $days = 30;
        $this->cacheService->shouldReceive('remember')
            ->once()
            ->with("analytics:countries:{$urlId}:{$days}", Mockery::any(), 60) // TTL_LONG
            ->andReturn([]);
        $this->cachedRepository->getCountryStats($urlId, $days);
    }

    public function test_getHourHeatmap_uses_correct_cache_params()
    {
        $urlId = 'url-123';
        $this->cacheService->shouldReceive('remember')
            ->once()
            ->with("analytics:heatmap:{$urlId}", Mockery::any(), 30) // TTL_MEDIUM
            ->andReturn([]);
        $this->cachedRepository->getHourHeatmap($urlId);
    }

    public function test_getGeoPoints_uses_correct_cache_params()
    {
        $urlId = 'url-123';
        $this->cacheService->shouldReceive('remember')
            ->once()
            ->with("analytics:geopoints:{$urlId}", Mockery::any(), 15) // TTL_SHORT
            ->andReturn([]);
        $this->cachedRepository->getGeoPoints($urlId);
    }

    public function test_getTrendingStats_uses_correct_cache_params()
    {
        $minutes = 60;
        $offset = 10;
        $this->cacheService->shouldReceive('remember')
            ->once()
            ->with("analytics:trending:{$minutes}:{$offset}", Mockery::any(), 60) // TTL_LONG
            ->andReturn([]);
        $this->cachedRepository->getTrendingStats($minutes, $offset);
    }
}
