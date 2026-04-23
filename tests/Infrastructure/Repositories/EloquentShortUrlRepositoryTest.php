<?php

namespace Tests\Infrastructure\Repositories;

use App\Domain\ShortUrl\Entities\ShortUrl;
use App\Domain\ShortUrl\ValueObjects\ShortCode;
use App\Domain\ShortUrl\ValueObjects\ExpirationDate;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentShortUrlRepository;
use App\Infrastructure\Persistence\Eloquent\Models\ShortUrlModel;
use Illuminate\Support\Facades\Redis;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class EloquentShortUrlRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private EloquentShortUrlRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new EloquentShortUrlRepository();
        Redis::flushall();
    }

    public function test_it_saves_and_retrieves_a_short_url()
    {
        // Arrange
        $entity = new ShortUrl(
            id: (string) Str::ulid(),
            originalUrl: 'https://google.com',
            shortCode: ShortCode::from('GGL123'),
            clicks: 0,
            expiresAt: ExpirationDate::from(now()->addDays(1)->toDateTimeImmutable())
        );
        // Act
        $saved = $this->repository->save($entity);
        // Assert
        $this->assertDatabaseHas('short_urls', ['short_code' => 'GGL123']);
        $this->assertEquals('https://google.com', $saved->originalUrl());
    }
    public function test_it_returns_fresher_clicks_from_redis()
    {
        // Arrange
        $code = 'REDI12';
        ShortUrlModel::factory()->create([
            'short_code' => $code,
            'clicks' => 10
        ]);
        Redis::set("shorturl:clicks:total:{$code}", 25);
        // Act
        $entity = $this->repository->findByCode($code);
        // Assert
        $this->assertNotNull($entity);
        $this->assertEquals(25, $entity->clicks(), "O Repository deveria priorizar o contador do Redis.");
    }
    public function test_findById_on_existing_id_returns_shortUrl_entity()
    {
        // Arrange
        $id = (string) Str::ulid();
        ShortUrlModel::factory()->create([
            'id' => $id,
            'original_url' => 'https://laravel.com',
            'short_code' => 'TESTES'
        ]);
        // Act
        $entity = $this->repository->findById($id);
        // Assert
        $this->assertInstanceOf(ShortUrl::class, $entity);
        $this->assertEquals('https://laravel.com', $entity->originalUrl());

    }
    public function test_findByCode_on_non_existing_code_returns_null()
    {
        // Act
        $entity = $this->repository->findByCode('NONEXISTENT');
        // Assert
        $this->assertNull($entity);
    }
}
