<?php

namespace Tests\Unit\Application\Analytics\Handlers;

use App\Application\Analytics\Handlers\GetCountriesQueryHandler;
use App\Application\Analytics\Queries\GetCountriesQuery;
use App\Domain\Analytics\Repositories\AnalyticsRepository;
use App\Domain\ShortUrl\Entities\ShortUrl;
use App\Domain\ShortUrl\Exceptions\UrlNotFoundException;
use App\Domain\ShortUrl\Repositories\ShortUrlRepository;
use Tests\TestCase;
use Mockery;

class GetCountriesQueryHandlerTest extends TestCase
{
    public function test_handle_returns_stats_when_url_exists()
    {
        $urlRepo = Mockery::mock(ShortUrlRepository::class);
        $analyticsRepo = Mockery::mock(AnalyticsRepository::class);
        $handler = new GetCountriesQueryHandler($analyticsRepo, $urlRepo);
        $url = Mockery::mock(ShortUrl::class);
        $url->shouldReceive('id')->andReturn('uuid-123');
        $urlRepo->shouldReceive('findByCode')->with('ABC123')->andReturn($url);
        $expectedStats = [['country' => 'BR', 'clicks' => 50]];
        $analyticsRepo->shouldReceive('getCountryStats')
            ->with('uuid-123', 7)
            ->andReturn($expectedStats);
        $result = $handler->handle(new GetCountriesQuery('ABC123', 7));
        $this->assertEquals($expectedStats, $result);
    }

    public function test_handle_throws_exception_if_url_not_found()
    {
        $urlRepo = Mockery::mock(ShortUrlRepository::class);
        $analyticsRepo = Mockery::mock(AnalyticsRepository::class);
        $handler = new GetCountriesQueryHandler($analyticsRepo, $urlRepo);
        $urlRepo->shouldReceive('findByCode')->andReturn(null);
        $this->expectException(UrlNotFoundException::class);
        $handler->handle(new GetCountriesQuery('INVALID', 7));
    }
}
