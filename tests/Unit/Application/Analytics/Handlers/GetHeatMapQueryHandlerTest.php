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
        $code = 'HOT123';
        $url = Mockery::mock(ShortUrl::class);
        $url->shouldReceive('id')->andReturn('uuid-heat-789');
        $url->shouldReceive('clicks')->andReturn(200);
        $urlRepo->shouldReceive('findByCode')
            ->once()
            ->with($code)
            ->andReturn($url);
        $expectedHeatmap = [
            ['label' => '00', 'value' => 5],
            ['label' => '12', 'value' => 45],
            ['label' => '18', 'value' => 120],
            ['label' => '22', 'value' => 30],
        ];

        $analyticsRepo->shouldReceive('getHourHeatmap')
            ->once()
            ->with('uuid-heat-789')
            ->andReturn($expectedHeatmap);
        $result = $handler->handle(new GetHeatMapQuery($code));
        $this->assertEquals($code, $result['code']);
        $this->assertEquals(['00', '12', '18', '22'], $result['labels']);
        $this->assertEquals([5, 45, 120, 30], $result['values']);
        $this->assertEquals(200, $result['total']);
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
