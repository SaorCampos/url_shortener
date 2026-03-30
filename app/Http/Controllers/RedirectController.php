<?php

namespace App\Http\Controllers;

use App\Application\Bus\QueryBus;
use App\Application\ShortUrl\Queries\FindShortUrlByCodeQuery;
use App\Infrastructure\Cache\BloomFilterService;
use App\Infrastructure\Cache\HotUrlCache;
use Illuminate\Support\Facades\Redis;

class RedirectController extends Controller
{
    public function __construct(
        private QueryBus $queryBus,
        private BloomFilterService $bloomFilter,
        private HotUrlCache $hotCache
    ) {}

    public function __invoke(string $code)
    {
        // 1. L1: HotCache (Octane)
        if ($url = $this->hotCache->get($code)) {
            $this->trackClick($code);
            return redirect()->away($url);
        }
        // 2. L2: Redis Cache
        [$url, $negative] = Redis::pipeline(function ($pipe) use ($code) {
            $pipe->get("shorturl:redirect:{$code}");
            $pipe->exists("shorturl:404:{$code}");
        });
        if ($url) {
            $this->hotCache->put($code, $url);
            $this->trackClick($code);
            return redirect()->away($url);
        }
        if ($negative) abort(404);
        // 3. Bloom Filter
        if (Redis::exists('shorturl:bloom') && !$this->bloomFilter->mightExist($code)) {
            abort(404);
        }
        // 4. L3: Database
        $shortUrl = $this->queryBus->dispatch(new FindShortUrlByCodeQuery($code));
        if (!$shortUrl || $shortUrl->isExpired()) {
            Redis::setex("shorturl:404:{$code}", 3600, 1);
            abort(404);
        }
        $url = $shortUrl->originalUrl();
        Redis::setex("shorturl:redirect:{$code}", 86400, $url);
        $this->hotCache->put($code, $url);
        $this->trackClick($code);
        return redirect()->away($url);
    }

    private function trackClick(string $code): void
    {
        $now = now();
        $ip = request()->ip();
        if (app()->environment('local') && $this->isPrivateIp($ip)) {
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
            $ip = $ipsFake[array_rand($ipsFake)];
        }
        Redis::pipeline(function ($pipe) use ($code, $now, $ip) {
            $pipe->incr("shorturl:clicks:total:{$code}");
            $pipe->incr("shorturl:clicks:minute:{$code}:" . $now->format('YmdHi'));
            $pipe->expire("shorturl:clicks:minute:{$code}:" . $now->format('YmdHi'), 86400);
            $pipe->zincrby("shorturl:top", 1, $code);
            $pipe->xadd('shorturl:clicks', '*', [
                'code' => $code,
                'ip' => $ip,
                'ua' => request()->userAgent(),
                'ref' => request()->header('Referer'),
                'ts' => $now->timestamp,
            ]);
        });
    }
    private function isPrivateIp(string $ip): bool
    {
        return !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }
}
