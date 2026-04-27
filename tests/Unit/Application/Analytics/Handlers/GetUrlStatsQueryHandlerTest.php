<?php

namespace Tests\Unit\Application\Analytics\Handlers;

use App\Application\Analytics\Handlers\GetUrlStatsQueryHandler;
use App\Application\Analytics\Queries\GetUrlStatsQuery;
use App\Domain\Analytics\Repositories\AnalyticsRepository;
use App\Domain\ShortUrl\Entities\ShortUrl;
use App\Domain\ShortUrl\Exceptions\UrlNotFoundException;
use App\Domain\ShortUrl\Repositories\ShortUrlRepository;
use Tests\TestCase;
use Mockery;

class GetUrlStatsQueryHandlerTest extends TestCase
{
    public function test_handle_returns_formatted_stats_for_charts()
    {
        $urlRepo = Mockery::mock(ShortUrlRepository::class);
        $analyticsRepo = Mockery::mock(AnalyticsRepository::class);
        $handler = new GetUrlStatsQueryHandler($analyticsRepo, $urlRepo);
        $code = 'CHART123';
        $url = Mockery::mock(ShortUrl::class);
        $url->shouldReceive('id')->andReturn('uuid-999');
        $url->shouldReceive('clicks')->andReturn(500);
        $urlRepo->shouldReceive('findByCode')
            ->once()
            ->with($code)
            ->andReturn($url);
        $rawStats = [
            ['label' => '10:00', 'value' => 10],
            ['label' => '10:01', 'value' => 15],
            ['label' => '10:02', 'value' => 5],
        ];
        $analyticsRepo->shouldReceive('getMinuteStats')
            ->once()
            ->with('uuid-999', 60)
            ->andReturn($rawStats);
        $result = $handler->handle(new GetUrlStatsQuery($code));
        $this->assertEquals($code, $result['code']);
        $this->assertEquals(['10:00', '10:01', '10:02'], $result['labels']);
        $this->assertEquals([10, 15, 5], $result['values']);
        $this->assertEquals(500, $result['total']);
    }

    public function test_handle_throws_exception_if_url_invalid()
    {
        $urlRepo = Mockery::mock(ShortUrlRepository::class);
        $analyticsRepo = Mockery::mock(AnalyticsRepository::class);
        $handler = new GetUrlStatsQueryHandler($analyticsRepo, $urlRepo);
        $urlRepo->shouldReceive('findByCode')->andReturn(null);
        $this->expectException(UrlNotFoundException::class);
        $handler->handle(new GetUrlStatsQuery('MISSING'));
    }
}
