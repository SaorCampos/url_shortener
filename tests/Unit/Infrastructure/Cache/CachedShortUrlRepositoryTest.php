<?php

namespace Tests\Unit\Infrastructure\Cache;

use App\Domain\Shared\Cache\CacheService;
use App\Domain\ShortUrl\Entities\ShortUrl;
use App\Domain\ShortUrl\Repositories\ShortUrlRepository;
use App\Infrastructure\Cache\CachedShortUrlRepository;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;
use Mockery;
use Mockery\MockInterface;

class CachedShortUrlRepositoryTest extends TestCase
{
    private ShortUrlRepository|MockInterface $innerRepository;
    private CacheService|MockInterface $cacheService;
    private CachedShortUrlRepository $cachedRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->innerRepository = Mockery::mock(ShortUrlRepository::class);
        $this->cacheService = Mockery::mock(CacheService::class);
        $this->cachedRepository = new CachedShortUrlRepository(
            $this->innerRepository,
            $this->cacheService
        );
    }

    public function test_findByCode_should_return_from_cache_if_available()
    {
        $code = 'ABC123';
        $expectedEntity = Mockery::mock(ShortUrl::class);
        $expectedEntity->shouldReceive('clicks')->andReturn(10);
        $expectedEntity->shouldReceive('shortCode')->andReturn($code);
        Redis::shouldReceive('get')
            ->with("shorturl:clicks:total:{$code}")
            ->andReturn(10);
        $this->cacheService->shouldReceive('remember')
            ->once()
            ->with("shorturl:{$code}", Mockery::any(), 3600)
            ->andReturn($expectedEntity);
        $this->innerRepository->shouldNotReceive('findByCode');
        $result = $this->cachedRepository->findByCode($code);
        $this->assertSame($expectedEntity, $result);
    }

    public function test_findByCode_should_sync_clicks_even_when_cached()
    {
        $code = 'CLICK1';
        $entity = Mockery::mock(ShortUrl::class);
        $entity->shouldReceive('clicks')->andReturn(10);
        $entity->shouldReceive('shortCode')->andReturn($code);
        Redis::shouldReceive('get')->andReturn(50);
        $entity->shouldReceive('updateClicks')->once()->with(50);
        $this->cacheService->shouldReceive('remember')->andReturn($entity);
        $this->cachedRepository->findByCode($code);
    }

    public function test_save_should_invalidate_cache()
    {
        $entity = Mockery::mock(ShortUrl::class);
        $entity->shouldReceive('shortCode')->andReturn('NEW123');
        $entity->shouldReceive('originalUrl')->andReturn('https://test.com');
        $this->innerRepository->shouldReceive('save')
            ->once()
            ->with($entity)
            ->andReturn($entity);
        $this->cacheService->shouldReceive('forget')
            ->once()
            ->with("shorturl:NEW123");
        $this->cacheService->shouldReceive('forget')
            ->once()
            ->with("shorturl:url_hash:" . md5('https://test.com'));
        $this->cachedRepository->save($entity);
    }
}
