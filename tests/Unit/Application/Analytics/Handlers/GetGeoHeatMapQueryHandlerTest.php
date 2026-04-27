<?php

namespace Tests\Unit\Application\Analytics\Handlers;

use App\Application\Analytics\Handlers\GetGeoHeatMapQueryHandler;
use App\Application\Analytics\Queries\GetGeoHeatMapQuery;
use App\Domain\Analytics\Repositories\AnalyticsRepository;
use App\Domain\ShortUrl\Entities\ShortUrl;
use App\Domain\ShortUrl\Exceptions\UrlNotFoundException;
use App\Domain\ShortUrl\Repositories\ShortUrlRepository;
use Tests\TestCase;
use Mockery;

class GetGeoHeatMapQueryHandlerTest extends TestCase
{
    public function test_handle_returns_geo_points_when_url_exists()
    {
        $urlRepo = Mockery::mock(ShortUrlRepository::class);
        $analyticsRepo = Mockery::mock(AnalyticsRepository::class);
        $handler = new GetGeoHeatMapQueryHandler($analyticsRepo, $urlRepo);
        $url = Mockery::mock(ShortUrl::class);
        $url->shouldReceive('id')->andReturn('uuid-geo-456');
        $urlRepo->shouldReceive('findByCode')
            ->once()
            ->with('GEO123')
            ->andReturn($url);
        $expectedPoints = [
            ['lat' => -23.5505, 'lng' => -46.6333, 'intensity' => 10],
            ['lat' => -22.9068, 'lng' => -43.1729, 'intensity' => 5]
        ];
        $analyticsRepo->shouldReceive('getGeoPoints')
            ->once()
            ->with('uuid-geo-456')
            ->andReturn($expectedPoints);
        $result = $handler->handle(new GetGeoHeatMapQuery('GEO123'));
        $this->assertEquals($expectedPoints, $result);
        $this->assertCount(2, $result);
    }

    public function test_handle_throws_exception_if_url_does_not_exist()
    {
        $urlRepo = Mockery::mock(ShortUrlRepository::class);
        $analyticsRepo = Mockery::mock(AnalyticsRepository::class);
        $handler = new GetGeoHeatMapQueryHandler($analyticsRepo, $urlRepo);
        $urlRepo->shouldReceive('findByCode')->andReturn(null);
        $this->expectException(UrlNotFoundException::class);
        $handler->handle(new GetGeoHeatMapQuery('MISSING'));
    }
}
