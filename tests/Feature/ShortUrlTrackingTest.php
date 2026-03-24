<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;
use App\Infrastructure\Persistence\Eloquent\Models\ShortUrlModel;
use PHPUnit\Framework\Attributes\Test;

class ShortUrlTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Redis::flushall();
    }

    #[Test]
    public function a_redirect_increments_all_redis_counters()
    {
        // Arrange
        $code = 'portifolio';
        ShortUrlModel::create([
            'id' => 100,
            'original_url' => 'https://google.com',
            'short_code' => $code,
            'clicks' => 0,
            'expires_at' => now()->addDays(7)
        ]);
        // Act
        $response = $this->get("/{$code}");
        // Assert
        $response->assertRedirect('https://google.com');
        $this->assertEquals(1, Redis::get("shorturl:clicks:total:{$code}"));
        $today = now()->format('Ymd');
        $countryData = Redis::hgetall("shorturl:country:{$code}:{$today}");
        $this->assertNotEmpty($countryData);
        $streamEntries = Redis::xrange('shorturl:clicks', '-', '+');
        $this->assertCount(1, $streamEntries);
        $firstEntry = current($streamEntries);
        $this->assertEquals($code, $firstEntry['code']);
    }
    #[Test]
    public function worker_updates_database_from_stream_data()
    {
        // Arrange
        $code = 'worker-test';
        $model = ShortUrlModel::create([
            'original_url' => 'https://laravel.com',
            'short_code' => $code,
            'clicks' => 0
        ]);
        $event = [
            'id-123' => [
                'code' => $code,
                'ip' => '8.8.8.8',
                'user_agent' => 'PHPUnit',
                'ts' => now()->timestamp
            ]
        ];
        // Act
        app(\App\Console\Commands\ProcessClickStream::class)->processEvents($event);
        // Assert
        $this->assertDatabaseHas('short_urls', [
            'short_code' => $code,
            'clicks' => 1
        ]);
        $this->assertDatabaseHas('clicks', [
            'short_url_id' => $model->id,
            'ip' => '8.8.8.8'
        ]);
    }
}
