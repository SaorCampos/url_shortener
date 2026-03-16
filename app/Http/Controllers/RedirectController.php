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
        if (!$shortUrl) {
            Redis::setex("shorturl:404:{$code}", 3600, 1);
            abort(404);
        }
        if ($shortUrl->isExpired()) {
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
        Redis::incr("shorturl:clicks:{$code}");
    }
}
