<?php

namespace Tests\Unit\Application\Analytics\Handlers;

use App\Application\Analytics\Handlers\GetTrendingUrlsQueryHandler;
use App\Application\Analytics\Queries\GetTrendingUrlsQuery;
use App\Domain\Analytics\Repositories\AnalyticsRepository;
use App\Domain\Analytics\ValueObjects\StatPoint;
use App\Domain\Analytics\ValueObjects\TrendingUrl;
use App\Domain\ShortUrl\Entities\ShortUrl;
use App\Domain\ShortUrl\Repositories\ShortUrlRepository;
use Tests\TestCase;
use Mockery;

class GetTrendingUrlsQueryHandlerTest extends TestCase
{
    private $analyticsRepo;
    private $urlRepo;
    private $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyticsRepo = Mockery::mock(AnalyticsRepository::class);
        $this->urlRepo = Mockery::mock(ShortUrlRepository::class);
        $this->handler = new GetTrendingUrlsQueryHandler($this->analyticsRepo, $this->urlRepo);
    }

    public function test_handle_calculates_trends_and_detects_spikes_correctly()
    {
        $this->analyticsRepo->shouldReceive('getTrendingStats')
            ->with(60)
            ->andReturn(['uuid-1' => 100, 'uuid-2' => 2]);
        $this->analyticsRepo->shouldReceive('getTrendingStats')
            ->with(120, 60)
            ->andReturn(['uuid-1' => 50]);
        $url = Mockery::mock(ShortUrl::class);
        $url->shouldReceive('shortCode')->andReturn('TRENDY');
        $this->urlRepo->shouldReceive('findById')->with('uuid-1')->andReturn($url);
        $this->analyticsRepo->shouldReceive('getMinuteStats')
            ->with('uuid-1', 5)
            ->andReturn([
                new StatPoint('10:01', 10),
                new StatPoint('10:02', 20),
            ]);
        $result = $this->handler->handle(new GetTrendingUrlsQuery());
        $this->assertCount(1, $result);
        $this->assertInstanceOf(TrendingUrl::class, $result[0]);
        $this->assertEquals('TRENDY', $result[0]->code);
        $this->assertEquals('+100%', $result[0]->trend);
        $this->assertTrue($result[0]->viral);
    }

    public function test_calculate_trend_with_zero_previous_clicks()
    {
        $this->analyticsRepo->shouldReceive('getTrendingStats')->with(60)->andReturn(['uuid-new' => 10]);
        $this->analyticsRepo->shouldReceive('getTrendingStats')->with(120, 60)->andReturn([]);
        $url = Mockery::mock(ShortUrl::class);
        $url->shouldReceive('shortCode')->andReturn('NEW');
        $this->urlRepo->shouldReceive('findById')->andReturn($url);
        $this->analyticsRepo->shouldReceive('getMinuteStats')->andReturn([]);
        $result = $this->handler->handle(new GetTrendingUrlsQuery());
        $this->assertEquals('+100%', $result[0]->trend);
    }
}
