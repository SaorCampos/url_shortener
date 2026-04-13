<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Models\ShortUrlModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Stevebauman\Location\Facades\Location;
use Stevebauman\Location\Position;
use Tests\TestCase;

class ProcessClickStreamTest extends TestCase
{
    use RefreshDatabase;

    private const STREAM = 'shorturl:clicks';

    protected function setUp(): void
    {
        parent::setUp();
        Redis::flushall();
        Location::shouldReceive('get')
            ->andReturn((object)[
                'countryCode' => 'BR',
                'latitude' => -3.73,
                'longitude' => -38.52
            ]);
    }

    public function test_it_processes_clicks_from_stream_to_database()
    {
        $url = ShortUrlModel::factory()->create([
            'short_code' => 'TEST12',
            'clicks' => 0
        ]);

        // Criamos a instância real da classe que o pacote usa
        $position = new Position();
        $position->countryCode = 'BR';
        $position->latitude = -3.73;
        $position->longitude = -38.52;

        // Mockamos a Facade para retornar essa instância específica
        Location::shouldReceive('get')
            ->once()
            ->with('200.147.67.142')
            ->andReturn($position);

        Redis::xadd(self::STREAM, '*', [
            'code' => 'TEST12',
            'ip' => '200.147.67.142',
            'ts' => now()->timestamp,
            'ua' => 'Mozilla/5.0',
            'ref' => 'https://google.com'
        ]);

        // Forçamos o Laravel a resolver o comando do zero
        $this->artisan('shorturl:abc-teste', ['--once' => true]);

        $this->assertDatabaseHas('clicks', [
            'short_url_id' => $url->id,
            'ip'           => '200.147.67.142',
            'country_code' => 'BR'
        ]);
    }
    public function test_it_detects_viral_status_after_worker_processing()
    {
        ShortUrlModel::factory()->create(['short_code' => 'VIRAL1']);
        for ($i = 0; $i < 2; $i++) {
            Redis::xadd('shorturl:clicks', '*', [
                'code' => 'VIRAL1',
                'ip' => "192.168.1.$i",
                'ts' => now()->subHours(2)->timestamp
            ]);
        }
        for ($i = 0; $i < 20; $i++) {
            Redis::xadd('shorturl:clicks', '*', [
                'code' => 'VIRAL1',
                'ip' => "10.0.0.$i",
                'ts' => now()->timestamp
            ]);
        }
        $this->artisan('shorturl:abc-teste', ['--once' => true])
            ->assertExitCode(0);
        $response = $this->getJson("/api/analytics-top-hour");
        $response->assertStatus(200)
            ->assertJsonFragment([
                'code' => 'VIRAL1',
                'viral' => true
            ]);
    }
}
