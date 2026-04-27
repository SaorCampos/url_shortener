<?php

namespace Tests\Feature;

use App\Infrastructure\Cache\BloomFilterService;
use App\Infrastructure\Persistence\Eloquent\Models\ShortUrlModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateShortUrlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Redis::flushall();
    }

    #[Test]
    public function it_creates_a_new_short_url_successfully()
    {
        // Arrange
        $payload = ['url' => 'https://google.com'];
        // Act
        $response = $this->postJson('api/short-urls', $payload);
        // Assert
        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'url',
                'short_code',
                'short_url',
                'clicks',
                'expires_at'
            ]);
        $code = $response->json('short_code');
        $this->assertDatabaseHas('short_urls', [
            'original_url' => 'https://google.com',
            'short_code' => $code
        ]);
        $this->assertEquals('https://google.com', Redis::get("shorturl:redirect:{$code}"));
        $bloomFilter = app(BloomFilterService::class);
        $this->assertTrue(
            $bloomFilter->mightExist("code:{$code}"),
            "O código deveria estar marcado no Bloom Filter"
        );
        $this->assertTrue(
            $bloomFilter->mightExist("url:https://google.com"),
            "A URL original deveria estar marcada no Bloom Filter"
        );
    }

    #[Test]
    public function it_returns_existing_url_instead_of_creating_duplicate()
    {
        // Arrange
        $url = 'https://laravel.com';
        $firstResponse = $this->postJson('api/short-urls', ['url' => $url]);
        $firstCode = $firstResponse->json('short_code');
        // Act
        $secondResponse = $this->postJson('api/short-urls', ['url' => $url]);
        $secondCode = $secondResponse->json('short_code');
        // Assert
        $this->assertEquals($firstCode, $secondCode);
        $this->assertCount(1, ShortUrlModel::all());
    }
    #[Test]
    public function it_validates_invalid_urls()
    {
        // Arrange
        $payload = ['url' => 'not-a-url'];
        // Act
        $response = $this->postJson('api/short-urls', $payload);
        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['url']);
    }

    #[Test]
    public function it_throttles_requests_after_limit()
    {
        // Arrange
        $payload = ['url' => 'https://example.com'];
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('api/short-urls', $payload)->assertStatus(201);
        }
        // Act
        $response = $this->postJson('api/short-urls', $payload);
        // Assert
        $response->assertStatus(429);
    }
}
