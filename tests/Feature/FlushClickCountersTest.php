<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class FlushClickCountersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Redis::flushall();
    }

    public function test_it_removes_old_keys_and_keeps_recent_ones()
    {
        // Arrange
        $oldDate = now()->subDays(10)->format('YmdHi');
        $oldGeoDate = now()->subDays(10)->format('Ymd');
        $oldKeys = [
            "shorturl:clicks:minute:{$oldDate}",
            "shorturl:top:{$oldDate}",
            "shorturl:country:ABCDE:{$oldGeoDate}"
        ];
        foreach ($oldKeys as $key) {
            Redis::set($key, 1);
        }
        $recentDate = now()->format('YmdHi');
        $recentGeoDate = now()->format('Ymd');
        $recentKeys = [
            "shorturl:clicks:minute:{$recentDate}",
            "shorturl:country:ABCDE:{$recentGeoDate}"
        ];
        foreach ($recentKeys as $key) {
            Redis::set($key, 1);
        }
        // Act
        $this->artisan('shorturl:flush-clicks', ['--days' => 7])
             ->expectsOutput('Processo de limpeza finalizado.') // Ajuste conforme seu log
             ->assertExitCode(0);
        // Assert
        foreach ($oldKeys as $key) {
            $this->assertEquals(0, Redis::exists($key), "A chave antiga {$key} deveria ter sido removida.");
        }
        foreach ($recentKeys as $key) {
            $this->assertEquals(1, Redis::exists($key), "A chave recente {$key} deveria ter sido mantida.");
        }
    }
}
