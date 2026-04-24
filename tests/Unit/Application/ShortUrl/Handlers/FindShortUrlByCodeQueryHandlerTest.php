<?php

namespace Tests\Unit\Application\ShortUrl\Handlers;

use App\Application\ShortUrl\Handlers\FindShortUrlByCodeQueryHandler;
use App\Application\ShortUrl\Queries\FindShortUrlByCodeQuery;
use App\Domain\ShortUrl\Entities\ShortUrl;
use App\Domain\ShortUrl\Exceptions\UrlNotFoundException;
use App\Domain\ShortUrl\Repositories\ShortUrlRepository;
use Tests\TestCase;
use Mockery;

class FindShortUrlByCodeQueryHandlerTest extends TestCase
{
    private $repository;
    private $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = Mockery::mock(ShortUrlRepository::class);
        $this->handler = new FindShortUrlByCodeQueryHandler($this->repository);
    }

    public function test_should_throw_exception_if_repository_returns_null()
    {
        $code = 'NOT000';
        $query = new FindShortUrlByCodeQuery($code);
        $this->repository->shouldReceive('findByCode')
            ->once()
            ->with($code)
            ->andReturn(null);
        $this->expectException(UrlNotFoundException::class);
        $this->handler->handle($query);
    }

    public function test_should_return_short_url_if_repository_finds_it()
    {
        $code = 'FOUND1';
        $query = new FindShortUrlByCodeQuery($code);
        $expectedEntity = Mockery::mock(ShortUrl::class);
        $this->repository->shouldReceive('findByCode')
            ->once()
            ->with($code)
            ->andReturn($expectedEntity);
        $result = $this->handler->handle($query);
        $this->assertSame($expectedEntity, $result);
    }
}
