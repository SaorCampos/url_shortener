<?php

namespace App\Http\Controllers;

use App\Application\Bus\QueryBus;
use App\Application\ShortUrl\Queries\FindShortUrlByCodeQuery;
use App\Infrastructure\Cache\BloomFilterService;
use App\Infrastructure\Cache\HotUrlCache;
use Illuminate\Support\Facades\Redis;
use Stevebauman\Location\Facades\Location;

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
        $hour = $now->format('H');
        $ip = request()->header('X-Forwarded-For') ?? request()->ip();
        $position = null;
        try {
            $position = Location::get($ip);
        } catch (\Throwable $e) {}
        $lat = $position?->latitude;
        $lng = $position?->longitude;
        $country = $position?->countryCode ?? 'XX';
        Redis::pipeline(function ($pipe) use ($code, $minute, $now, $hour, $lat, $lng, $ip, $country) {
            // analytics
            $pipe->incr("shorturl:clicks:total:{$code}");
            $pipe->incr("shorturl:clicks:minute:{$code}:{$minute}");
            $pipe->expire("shorturl:clicks:minute:{$code}:{$minute}", 86400);
            $pipe->zincrby("shorturl:top", 1, $code);
            $pipe->zincrby("shorturl:top:{$minute}", 1, $code);
            $pipe->expire("shorturl:top:{$minute}", 86400);
            $pipe->hincrby("shorturl:heatmap:{$code}", $hour, 1);
            $pipe->hincrby("shorturl:country:{$code}", $country, 1);
            // GEO
            if ($lat && $lng) {
                $pipe->geoadd("shorturl:geo:{$code}", $lng, $lat, uniqid());
            }
            // stream
            $pipe->xadd('shorturl:clicks', '*', [
                'code' => $code,
                'ts' => $now->timestamp
            ]);
        });
    }
}
