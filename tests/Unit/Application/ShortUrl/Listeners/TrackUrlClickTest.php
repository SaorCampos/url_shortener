<?php

namespace Tests\Unit\Application\ShortUrl\Listeners;

use App\Application\ShortUrl\Listeners\TrackUrlClick;
use App\Domain\ShortUrl\Events\ShortUrlAccessed;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;
use Mockery;

class TrackUrlClickTest extends TestCase
{
    public function test_handle_tracks_clicks_correctly_in_redis_pipeline()
    {
        $event = new ShortUrlAccessed(
            code: 'ABC123',
            ip: '177.92.7.1', // IP público para não cair no resolveIp aleatório
            userAgent: 'Mozilla/5.0',
            referer: 'https://google.com',
            timestamp: 1713974400 // 2024-04-24 16:00:00
        );
        $date = date('YmdHi', $event->timestamp);
        $listener = new TrackUrlClick();
        $pipeMock = Mockery::mock();
        $pipeMock->shouldReceive('incr')->once()->with("shorturl:clicks:total:ABC123");
        $pipeMock->shouldReceive('incr')->once()->with("shorturl:clicks:minute:ABC123:{$date}");
        $pipeMock->shouldReceive('expire')->once()->with("shorturl:clicks:minute:ABC123:{$date}", 86400);
        $pipeMock->shouldReceive('zincrby')->once()->with("shorturl:top", 1, 'ABC123');
        $pipeMock->shouldReceive('xadd')->once()->with('shorturl:clicks', '*', Mockery::on(function ($data) use ($event) {
            return $data['code'] === 'ABC123' && $data['ip'] === '177.92.7.1';
        }));
        Redis::shouldReceive('pipeline')
            ->once()
            ->andReturnUsing(function ($closure) use ($pipeMock) {
                return $closure($pipeMock);
            });
        $listener->handle($event);
    }

    public function test_resolve_ip_assigns_fake_ip_for_private_ranges()
    {
        // Evento com IP local (Private Range)
        $event = new ShortUrlAccessed(
            code: 'ABC123',
            ip: '127.0.0.1',
            userAgent: 'Mozilla/5.0',
            referer: null,
            timestamp: time()
        );
        $listener = new TrackUrlClick();
        $pipeMock = Mockery::mock()->shouldIgnoreMissing();
        Redis::shouldReceive('pipeline')->andReturnUsing(function ($closure) use ($pipeMock) {
            return $closure($pipeMock);
        });
        $pipeMock->shouldReceive('xadd')->once()->with(
            'shorturl:clicks',
            '*',
            Mockery::on(function ($data) {
                return $data['ip'] !== '127.0.0.1' && !empty($data['ip']);
            })
        );
        $listener->handle($event);
    }
}
