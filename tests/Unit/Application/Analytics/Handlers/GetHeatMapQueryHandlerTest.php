<?php

namespace Tests\Unit\Application\Analytics\Handlers;

use App\Application\Analytics\Handlers\GetHeatMapQueryHandler;
use App\Application\Analytics\Queries\GetHeatMapQuery;
use App\Domain\Analytics\Repositories\AnalyticsRepository;
use App\Domain\ShortUrl\Entities\ShortUrl;
use App\Domain\ShortUrl\Exceptions\UrlNotFoundException;
use App\Domain\ShortUrl\Repositories\ShortUrlRepository;
use Tests\TestCase;
use Mockery;

class GetHeatMapQueryHandlerTest extends TestCase
{
    public function test_handle_returns_heatmap_data_when_url_exists()
    {
        $urlRepo = Mockery::mock(ShortUrlRepository::class);
        $analyticsRepo = Mockery::mock(AnalyticsRepository::class);
        $handler = new GetHeatMapQueryHandler($analyticsRepo, $urlRepo);
        $url = Mockery::mock(ShortUrl::class);
        $url->shouldReceive('id')->andReturn('uuid-heat-789');
        $urlRepo->shouldReceive('findByCode')
            ->once()
            ->with('HOT123')
            ->andReturn($url);
                $expectedHeatmap = [
            '00' => 5,
            '12' => 45,
            '18' => 120,
            '22' => 30
        ];
        $analyticsRepo->shouldReceive('getHourHeatmap')
            ->once()
            ->with('uuid-heat-789')
            ->andReturn($expectedHeatmap);
        $result = $handler->handle(new GetHeatMapQuery('HOT123'));
        $this->assertEquals($expectedHeatmap, $result);
        $this->assertArrayHasKey('18', $result);
        $this->assertEquals(120, $result['18']);
    }

    public function test_handle_throws_exception_if_url_not_found()
    {
        $urlRepo = Mockery::mock(ShortUrlRepository::class);
        $analyticsRepo = Mockery::mock(AnalyticsRepository::class);
        $handler = new GetHeatMapQueryHandler($analyticsRepo, $urlRepo);
        $urlRepo->shouldReceive('findByCode')->andReturn(null);
        $this->expectException(UrlNotFoundException::class);
        $handler->handle(new GetHeatMapQuery('MISSING'));
    }
}
