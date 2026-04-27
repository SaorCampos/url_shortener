<?php

namespace Tests\Feature;


use App\Infrastructure\Cache\BloomFilterService;
use App\Infrastructure\Persistence\Eloquent\Models\ClickModel;
use App\Infrastructure\Persistence\Eloquent\Models\ShortUrlModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private $url;

    protected function setUp(): void
    {
        parent::setUp();
        Redis::flushall();
        $code = 'ABC123';
        $this->url = ShortUrlModel::create([
            'id' => (string) Str::ulid(),
            'original_url' => 'https://example.com',
            'short_code' => $code,
            'clicks' => 0
        ]);
        app(BloomFilterService::class)->add("code:{$code}");
    }
    protected function allowInBloomFilter(string $code): void
    {
        app(BloomFilterService::class)->add("code:{$code}");
    }

    #[Test]
    public function it_returns_main_analytics_stats()
    {
        // Arrange
        ClickModel::factory()->count(5)->create(['short_url_id' => (string)$this->url->id]);
        $code = (string) $this->url->short_code;
        $this->url->increment('clicks', 5);
        // Act
        $response = $this->getJson("/api/analytics/{$code}");
        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure(['code', 'labels', 'values', 'total'])
            ->assertJson(['total' => 5]);
    }

    #[Test]
    public function it_returns_top_urls_by_total_clicks()
    {
        // Arrange
        $code2 = 'GOG456';
        $url2 = ShortUrlModel::create([
            'id' => (string) Str::ulid(),
            'original_url' => 'https://google.com',
            'short_code' => $code2,
            'clicks' => 100
        ]);
        $this->allowInBloomFilter($code2);
        // Act
        $response = $this->getJson("/api/analytics-top-day");
        // Assert
        $response->assertStatus(200)
            ->assertJsonFragment(['code' => 'GOG456', 'clicks' => 100])
            ->assertJsonFragment(['code' => 'ABC123', 'clicks' => 0]);
    }

    #[Test]
    public function it_detects_trending_and_viral_urls_in_the_last_hour()
    {
        // Arrange
        ClickModel::factory()->count(10)->create([
            'short_url_id' => $this->url->id,
            'created_at' => now()
        ]);
        // Act
        $response = $this->getJson("/api/analytics-top-hour");
        // Assert
        $response->assertStatus(200)
            ->assertJsonFragment([
                'code' => (string) $this->url->short_code,
                'viral' => true
            ]);
    }

    #[Test]
    public function it_returns_country_distribution()
    {
        // Arrange
        ClickModel::factory()->create(['short_url_id' => $this->url->id, 'country_code' => 'BR']);
        ClickModel::factory()->create(['short_url_id' => $this->url->id, 'country_code' => 'BR']);
        ClickModel::factory()->create(['short_url_id' => $this->url->id, 'country_code' => 'US']);
        // Act
        $response = $this->getJson("/api/analytics-countries/{$this->url->short_code}");
        // Assert
        $response->assertStatus(200)
            ->assertJsonFragment(['country' => 'BR', 'clicks' => 2])
            ->assertJsonFragment(['country' => 'US', 'clicks' => 1]);
    }

    #[Test]
    public function it_returns_geographic_heatmap_points()
    {
        // Arrange
        ClickModel::factory()->create([
            'short_url_id' => $this->url->id,
            'lat' => -3.73,
            'lng' => -38.52
        ]);
        // Act
        $response = $this->getJson("/api/analytics-geo/{$this->url->short_code}");
        // Assert
        $response->assertStatus(200)
            ->assertJsonFragment([
                'lat' => -3.73,
                'lng' => -38.52,
                'intensity' => 1
            ]);
    }

    #[Test]
    public function it_returns_404_when_analytics_code_does_not_exist()
    {
        // Act
        $response = $this->getJson("/api/analytics/INVALID");
        // Assert
        $response->assertStatus(404);
    }
}
