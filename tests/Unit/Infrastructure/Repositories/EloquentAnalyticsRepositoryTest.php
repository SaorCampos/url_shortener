<?php

namespace Tests\Unit\Infrastructure\Repositories;

use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentAnalyticsRepository;
use App\Infrastructure\Persistence\Eloquent\Models\ClickModel;
use App\Infrastructure\Persistence\Eloquent\Models\ShortUrlModel;
use App\Domain\Analytics\ValueObjects\StatPoint;
use App\Domain\Analytics\ValueObjects\CountryStats;
use App\Domain\Analytics\ValueObjects\GeoPoint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EloquentAnalyticsRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private EloquentAnalyticsRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new EloquentAnalyticsRepository();
    }

    public function test_it_gets_minute_stats_grouped_by_time()
    {
        // Arrange
        $url = ShortUrlModel::factory()->create();
        $time = now()->startOfMinute();
        ClickModel::factory()->create(['short_url_id' => $url->id, 'created_at' => $time]);
        ClickModel::factory()->create(['short_url_id' => $url->id, 'created_at' => $time]);
        ClickModel::factory()->create(['short_url_id' => $url->id, 'created_at' => $time->copy()->subMinute()]);
        // Act
        $stats = $this->repository->getMinuteStats($url->id, 10);
        // Assert
        $this->assertCount(2, $stats);
        $this->assertInstanceOf(StatPoint::class, $stats[0]);
        $this->assertEquals(2, $stats[1]->value);
    }

    public function test_it_retrieves_top_urls_ordered_by_clicks()
    {
        // Arrange
        ShortUrlModel::factory()->create(['short_code' => 'TOP001', 'clicks' => 100]);
        ShortUrlModel::factory()->create(['short_code' => 'TOP002', 'clicks' => 500]);
        // Act
        $top = $this->repository->getTopUrls(2);
        // Assert
        $this->assertCount(2, $top);
        $this->assertEquals('TOP002', $top[0]->code);
        $this->assertEquals(500, $top[0]->clicks);
    }
    public function test_it_calculates_country_stats()
    {
        // Arrange
        $url = ShortUrlModel::factory()->create();
        ClickModel::factory()->count(3)->create(['short_url_id' => $url->id, 'country_code' => 'BR']);
        ClickModel::factory()->count(1)->create(['short_url_id' => $url->id, 'country_code' => 'US']);
        // Act
        $stats = $this->repository->getCountryStats($url->id, 7);
        $brStats = collect($stats)->firstWhere('country', 'BR');
        // Assert
        $this->assertInstanceOf(CountryStats::class, $brStats);
        $this->assertEquals(3, $brStats->clicks);
    }
    public function test_it_gets_hour_heatmap_grouped_by_hour()
    {
        // Arrange
        $url = ShortUrlModel::factory()->create();
        $time = now()->startOfHour();
        ClickModel::factory(2)->create(['short_url_id' => $url->id, 'created_at' => $time]);
        ClickModel::factory()->create(['short_url_id' => $url->id, 'created_at' => $time->copy()->subHour()]);
        ClickModel::factory()->create(['short_url_id' => $url->id, 'created_at' => $time->copy()->subHours(2)]);
        // Act
        $hourHeatMap = $this->repository->getHourHeatMap($url->id);
        // Assert
        $this->assertCount(3, $hourHeatMap);
        $this->assertInstanceOf(StatPoint::class, $hourHeatMap[0]);
        $this->assertEquals(2, $hourHeatMap[2]->value);
    }
    public function test_it_returns_correct_geo_points_structure()
    {
        // Arrange
        $url = ShortUrlModel::factory()->create();
        ClickModel::factory()->create([
            'short_url_id' => $url->id,
            'lat' => -23.55,
            'lng' => -46.63
        ]);
        // Act
        $points = $this->repository->getGeoPoints($url->id);
        // Assert
        $this->assertInstanceOf(GeoPoint::class, $points[0]);
        $this->assertIsFloat($points[0]->lat);
        $this->assertIsInt($points[0]->intensity);
        $this->assertEquals(-23.55, $points[0]->lat);
    }
    public function test_it_filters_trending_stats_by_time_window()
    {
        // Arrange
        $url = ShortUrlModel::factory()->create();
        ClickModel::factory()->create(['short_url_id' => $url->id, 'created_at' => now()->subMinutes(2)]);
        // Act
        $stats = $this->repository->getTrendingStats(10);
        // Assert
        $this->assertArrayHasKey($url->id, $stats);
        $this->assertEquals(1, $stats[$url->id]);
    }
}
