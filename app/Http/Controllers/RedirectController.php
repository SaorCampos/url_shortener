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
        if ($url = $this->hotCache->get($code)) {
            $this->trackClick($code);
            return redirect()->away($url);
        }
        [$url, $negative] = Redis::pipeline(function ($pipe) use ($code) {
            $pipe->get("shorturl:redirect:{$code}");
            $pipe->exists("shorturl:404:{$code}");
        });
        if ($url) {
            $this->hotCache->put($code, $url);
            $this->trackClick($code);
            return redirect()->away($url);
        }
        if ($negative) {
            abort(404);
        }
        if (!$this->bloomFilter->mightExist($code)) {
            abort(404);
        }
        $shortUrl = $this->queryBus->dispatch(
            new FindShortUrlByCodeQuery($code)
        );
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
        $minute = $now->format('YmdHi');
        Redis::pipeline(function ($pipe) use ($code, $minute, $now) {
            // total
            $pipe->incr("shorturl:clicks:total:{$code}");
            // bucket per minute
            $pipe->incr("shorturl:clicks:minute:{$code}:{$minute}");
            $pipe->expire("shorturl:clicks:minute:{$code}:{$minute}", 86400);
            // ranking global
            $pipe->zincrby("shorturl:top", 1, $code);
            // ranking per minute
            $pipe->zincrby("shorturl:top:{$minute}", 1, $code);
            $pipe->expire("shorturl:top:{$minute}", 86400);
            // stream (async persistence)
            $pipe->xadd('shorturl:clicks', '*', [
                'code' => $code,
                'ts' => $now->timestamp
            ]);
        });
    }
}
