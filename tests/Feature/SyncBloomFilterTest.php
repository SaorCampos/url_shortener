<?php

namespace Tests\Feature;

use App\Infrastructure\Cache\BloomFilterService;
use App\Infrastructure\Persistence\Eloquent\Models\ShortUrlModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Mockery;
use Tests\TestCase;

class SyncBloomFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Redis::flushall();
    }

    public function test_it_syncs_database_codes_to_bloom_filter()
    {
        ShortUrlModel::factory()->count(5)->create();
        $bloomMock = Mockery::mock(BloomFilterService::class);
        $bloomMock->shouldReceive('add')->times(10);
        $this->app->instance(BloomFilterService::class, $bloomMock);
        $this->artisan('shorturl:sync-bloom')->assertExitCode(0);
    }
}
