<?php

namespace Tests\Unit\Application\ShortUrl\Handlers;

use App\Application\ShortUrl\Handlers\FindShortUrlByCodeQueryHandler;
use App\Application\ShortUrl\Queries\FindShortUrlByCodeQuery;
use App\Domain\ShortUrl\Entities\ShortUrl;
use App\Domain\ShortUrl\Exceptions\UrlNotFoundException;
use App\Domain\ShortUrl\Repositories\ShortUrlRepository;
use App\Infrastructure\Cache\BloomFilterService;
use Tests\TestCase;
use Mockery;

class FindShortUrlByCodeQueryHandlerTest extends TestCase
{
    private $repository;
    private $bloomFilter;
    private $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = Mockery::mock(ShortUrlRepository::class);
        $this->bloomFilter = Mockery::mock(BloomFilterService::class);
        $this->handler = new FindShortUrlByCodeQueryHandler($this->repository, $this->bloomFilter);
    }

    public function test_should_throw_exception_immediately_if_bloom_filter_returns_false()
    {
        $code = 'NOT000';
        $query = new FindShortUrlByCodeQuery($code);
        $this->bloomFilter->shouldReceive('mightExist')
            ->with("code:{$code}")
            ->andReturn(false);
        $this->repository->shouldNotReceive('findByCode');
        $this->expectException(UrlNotFoundException::class);
        $this->handler->handle($query);
    }

    public function test_should_return_short_url_if_bloom_filter_and_repository_confirm_existence()
    {
        $code = 'FOUND1';
        $query = new FindShortUrlByCodeQuery($code);
        $expectedEntity = Mockery::mock(ShortUrl::class);
        $this->bloomFilter->shouldReceive('mightExist')->andReturn(true);
        $this->repository->shouldReceive('findByCode')->with($code)->andReturn($expectedEntity);
        $result = $this->handler->handle($query);
        $this->assertSame($expectedEntity, $result);
    }
    public function test_should_handle_bloom_filter_false_positive()
    {
        $code = 'GHOST1';
        $query = new FindShortUrlByCodeQuery($code);
        $this->bloomFilter->shouldReceive('mightExist')->andReturn(true);
        $this->repository->shouldReceive('findByCode')->with($code)->andReturn(null);
        $this->expectException(UrlNotFoundException::class);
        $this->handler->handle($query);
    }
}
