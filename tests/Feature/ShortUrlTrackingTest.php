<?php

namespace Tests\Feature;

use App\Console\Commands\ProcessClicksStream;
use App\Domain\ShortUrl\Events\ShortUrlAccessed;
use App\Infrastructure\Cache\BloomFilterService;
use App\Infrastructure\Persistence\Eloquent\Models\ShortUrlModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Stevebauman\Location\Facades\Location;
use Stevebauman\Location\Position;
use Tests\TestCase;

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
        config(['queue.default' => 'sync']);
        $code = 'abc123';
        $mockPosition = Mockery::mock(Position::class);
        $mockPosition->countryCode = 'BR';
        $mockPosition->latitude = -23.55;
        $mockPosition->longitude = -46.63;
        Location::shouldReceive('get')->andReturn($mockPosition);
        ShortUrlModel::create([
            'id' => (string)Str::ulid(),
            'original_url' => 'https://google.com',
            'short_code' => $code,
            'clicks' => 0,
            'expires_at' => now()->addDays(7)
        ]);
        app(BloomFilterService::class)->add("code:{$code}");
        // Act
        $response = $this->get("/{$code}");
        $response->assertRedirect('https://google.com');
        $streamEntries = Redis::xrange('shorturl:clicks', '-', '+');
        $this->assertNotEmpty($streamEntries, "ERRO: O Listener não gravou no Redis Stream 'shorturl:clicks'. Verifique se o Evento está disparando.");
        $firstEntryId = array_key_first($streamEntries);
        $firstEventFields = $streamEntries[$firstEntryId];
        $timestamp = $firstEventFields['ts'] ?? $firstEventFields['timestamp'] ?? null;
        $eventDate = date('Ymd', (int)$timestamp);
        app(ProcessClicksStream::class)->processEvents($streamEntries);
        // Assert
        $countryData = Redis::hgetall("shorturl:country:{$code}:{$eventDate}");
        $this->assertArrayHasKey('BR', $countryData, "O counter por país não foi criado no Redis.");
        $this->assertEquals(1, $countryData['BR']);
    }
    #[Test]
    public function worker_updates_database_from_stream_data(): void
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
        app(ProcessClicksStream::class)->processEvents($event);
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
    #[Test]
    public function it_returns_404_if_code_is_not_in_bloom_filter()
    {
        // Arrange
        config(['session.driver' => 'array']);
        config(['cache.default' => 'array']);
        $code = 'notino';
        $mockBloom = Mockery::mock(BloomFilterService::class);
        $mockBloom->shouldReceive('mightExist')->with("code:{$code}")->andReturn(false);
        $this->app->instance(BloomFilterService::class, $mockBloom);
        Redis::partialMock()
            ->shouldReceive('exists')
            ->with("shorturl:404:{$code}")
            ->andReturn(false);
        config(['session.driver' => 'array']);
        config(['cache.default' => 'array']);
        // Act
        $response = $this->get("/{$code}");
        // Assert
        $response->assertStatus(404);
    }
    #[Test]
    public function it_uses_negative_cache_to_prevent_db_queries_for_invalid_urls()
    {
        // Arrange
        $code = 'none01';
        Redis::setex("shorturl:404:{$code}", 3600, 1);
        // Act
        $response = $this->get("/{$code}");
        // Assert
        $response->assertStatus(404);
        $this->assertEmpty(Redis::xrange('shorturl:clicks', '-', '+'));
    }
    #[Test]
    public function it_returns_404_if_url_is_expired()
    {
        // Arrange
        $code = 'oldurl';
        ShortUrlModel::create([
            'original_url' => 'https://expired.com',
            'short_code' => $code,
            'clicks' => 0,
            'expires_at' => now()->subDay()
        ]);
        app(BloomFilterService::class)->add("code:{$code}");
        // Act
        $response = $this->get("/{$code}");
        // Assert
        $response->assertStatus(404);
        $this->assertEquals(1, Redis::get("shorturl:404:{$code}"));
    }
    #[Test]
    public function it_still_redirects_if_redis_is_offline()
    {
        // Arrange
        config(['session.driver' => 'array']);
        config(['cache.default' => 'array']);
        Event::fake([ShortUrlAccessed::class]);
        $code = 'redoff';
        ShortUrlModel::create([
            'id' => (string)Str::ulid(),
            'original_url' => 'https://resilient.com',
            'short_code' => $code,
            'clicks' => 0
        ]);
        Redis::shouldReceive('connection')->andReturnSelf();
        Redis::shouldReceive('getbit', 'get', 'exists', 'setex', 'del', 'pipeline', 'zincrby', 'xadd', 'eval')
            ->zeroOrMoreTimes()
            ->andReturnUsing(function () {
                throw new \Exception('Redis Offline');
            });
        // Act
        $response = $this->get("/{$code}");
        // Assert
        $response->assertRedirect('https://resilient.com');
    }
    #[Test]
    public function worker_ignores_private_ips_for_geolocation()
    {
        // Arrange
        $code = 'privat';
        $event = [
            'id-999' => [
                'code' => $code,
                'ip' => '127.0.0.1',
                'ua' => 'PHPUnit',
                'ts' => now()->timestamp
            ]
        ];
        // Act
        app(ProcessClicksStream::class)->processEvents($event);

        // Assert
        $today = now()->format('Ymd');
        $countryData = Redis::hgetall("shorturl:country:{$code}:{$today}");
        $this->assertEmpty($countryData);
    }
    #[Test]
    public function it_returns_404_for_invalid_code_formats()
    {
        $this->get('/abc12')->assertStatus(404);
        $this->get('/abc1234')->assertStatus(404);
        $this->get('/abc-12')->assertStatus(404);
        $this->get('/abc 12')->assertStatus(404);
    }
}
