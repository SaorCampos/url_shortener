<?php

namespace Tests\Unit\Application\Analytics\Handlers;

use App\Application\Analytics\Handlers\GetTrendingUrlsQueryHandler;
use App\Application\Analytics\Queries\GetTrendingUrlsQuery;
use App\Domain\Analytics\Repositories\AnalyticsRepository;
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
        // 1. Mock das estatísticas de tendência (60 min vs 120-60 min)
        $this->analyticsRepo->shouldReceive('getTrendingStats')
            ->with(60)
            ->andReturn(['uuid-1' => 100, 'uuid-2' => 2]); // uuid-2 será ignorado (clicks < 3)
        $this->analyticsRepo->shouldReceive('getTrendingStats')
            ->with(120, 60)
            ->andReturn(['uuid-1' => 50]); // Crescimento de 100%
        // 2. Mock da Entidade ShortUrl
        $url = Mockery::mock(ShortUrl::class);
        $url->shouldReceive('shortCode')->andReturn('TRENDY');
        $this->urlRepo->shouldReceive('findById')->with('uuid-1')->andReturn($url);
        $this->analyticsRepo->shouldReceive('getMinuteStats')
            ->with('uuid-1', 5)
            ->andReturn([
                ['label' => '10:01', 'value' => 10],
                ['label' => '10:02', 'value' => 20], // Total 30 (> 25)
            ]);
        $result = $this->handler->handle(new GetTrendingUrlsQuery());
        $this->assertCount(1, $result);
        $this->assertEquals('TRENDY', $result[0]['code']);
        $this->assertEquals('+100%', $result[0]['trend']);
        $this->assertTrue($result[0]['viral']);
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
        $this->assertEquals('+100%', $result[0]['trend']);
    }
}
