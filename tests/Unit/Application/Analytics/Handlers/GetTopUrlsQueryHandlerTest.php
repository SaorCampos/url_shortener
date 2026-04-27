<?php

namespace Tests\Unit\Application\Analytics\Handlers;

use App\Application\Analytics\Handlers\GetTopUrlsQueryHandler;
use App\Application\Analytics\Queries\GetTopUrlsQuery;
use App\Domain\Analytics\Repositories\AnalyticsRepository;
use Tests\TestCase;
use Mockery;

class GetTopUrlsQueryHandlerTest extends TestCase
{
    public function test_handle_returns_formatted_top_urls()
    {
        $analyticsRepo = Mockery::mock(AnalyticsRepository::class);
        $handler = new GetTopUrlsQueryHandler($analyticsRepo);
        $rawStats = [
            ['code' => 'ABC123', 'clicks' => '150'],
            ['code' => 'XYZ789', 'clicks' => 85],
        ];
        $analyticsRepo->shouldReceive('getTopUrls')
            ->once()
            ->with(10)
            ->andReturn($rawStats);
        $result = $handler->handle(new GetTopUrlsQuery(limit: 10));
        $this->assertCount(2, $result);
        $this->assertSame('ABC123', $result[0]['code']);
        $this->assertSame(150, $result[0]['clicks']); // Inteiro!
        $this->assertIsInt($result[1]['clicks']);
    }

    public function test_handle_returns_empty_array_when_no_data_exists()
    {
        $analyticsRepo = Mockery::mock(AnalyticsRepository::class);
        $handler = new GetTopUrlsQueryHandler($analyticsRepo);
        $analyticsRepo->shouldReceive('getTopUrls')->andReturn([]);
        $result = $handler->handle(new GetTopUrlsQuery(limit: 5));
        $this->assertEquals([], $result);
    }
}
