<?php

namespace App\Application\ShortUrl\Listeners;

use App\Domain\ShortUrl\Events\ShortUrlAccessed;
use Illuminate\Support\Facades\Redis;

class TrackUrlClick
{
    public function handle(ShortUrlAccessed $event): void
    {
        $code = $event->code;
        $ip = $this->resolveIp($event->ip);
        $date = date('YmdHi', $event->timestamp);
        try {
            Redis::pipeline(function ($pipe) use ($code, $event, $ip, $date) {
                $pipe->incr("shorturl:clicks:total:{$code}");
                $pipe->incr("shorturl:clicks:minute:{$code}:{$date}");
                $pipe->expire("shorturl:clicks:minute:{$code}:{$date}", 86400);
                $pipe->zincrby("shorturl:top", 1, $code);
                $pipe->xadd('shorturl:clicks', '*', [
                    'code' => $code,
                    'ip'   => $ip,
                    'ua'   => $event->userAgent,
                    'ref'  => $event->referer,
                    'ts'   => $event->timestamp,
                ]);
            });
        } catch (\Throwable $e) {
            report($e);
        }
    }
    private function isPrivateIp(string $ip): bool
    {
        return !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }
    private function resolveIp(string $ip): string
    {
        if ($this->isPrivateIp($ip)) {
            $ipsFake = [
                '177.92.7.1',
                '8.8.8.8',
                '2.20.141.0',
                '202.160.128.0',
                '105.107.107.107',
                '186.192.100.100',
                '189.10.10.10',
                '177.104.123.123',
                '103.103.103.103',
                '179.123.123.123',
            ];
            return $ipsFake[array_rand($ipsFake)];
        }
        return $ip;
    }
}
