<?php

namespace App\Infrastructure\Cache;

use App\Domain\Shared\Cache\CacheService;
use App\Domain\ShortUrl\DTO\ShortUrlData;
use App\Domain\ShortUrl\Entities\ShortUrl;
use App\Domain\ShortUrl\Repositories\ShortUrlRepository;
use Illuminate\Support\Facades\Redis;

class CachedShortUrlRepository implements ShortUrlRepository
{
    private const DEFAULT_TTL = 3600;

    public function __construct(
        private ShortUrlRepository $repository,
        private CacheService $cache,
        private HotUrlCache $hotCache,
        private BloomFilterService $bloom
    ) {}

    public function save(ShortUrl $url): ShortUrl
    {
        $saved = $this->repository->save($url);
        $this->cache->forget($this->cacheKey($saved->shortCode()));
        $this->cache->forget($this->urlHashKey($saved->originalUrl()));
        return $saved;
    }

    public function findByCode(string $code): ?ShortUrl
    {
        $cachedUrl = $this->hotCache->get($code);
        if ($cachedUrl) {
            return ShortUrl::restore(new ShortUrlData(
                id: 'from-l1',
                originalUrl: $cachedUrl,
                shortCode: $code,
                clicks: 0,
                expiresAt: null
            ));
        }
        if (Redis::exists("shorturl:404:{$code}")) {
            return null;
        }
        if (!$this->bloom->mightExist("code:{$code}")) {
            return null;
        }
        $shortUrl = $this->cache->remember(
            $this->cacheKey($code),
            fn() => $this->repository->findByCode($code),
            self::DEFAULT_TTL
        );
        if (!$shortUrl) {
            Redis::setex("shorturl:404:{$code}", 3600, 1);
            return null;
        }
        $this->hotCache->put($code, $shortUrl->originalUrl());
        $this->syncRealTimeClicks($shortUrl, $code);
        return $shortUrl;
    }

    public function findByOriginalUrl(string $url): ?ShortUrl
    {
        $code = $this->cache->remember(
            $this->urlHashKey($url),
            function () use ($url) {
                $dbUrl = $this->repository->findByOriginalUrl($url);
                return $dbUrl ? $dbUrl->shortCode() : null;
            },
            self::DEFAULT_TTL
        );
        return $code ? $this->findByCode($code) : null;
    }
    public function findById(string $id): ?ShortUrl
    {
        return $this->cache->remember(
            "shorturl:id:{$id}",
            fn() => $this->repository->findById($id),
            self::DEFAULT_TTL
        );
    }

    private function syncRealTimeClicks(ShortUrl $entity, string $code): void
    {
        try {
            $realTimeClicks = (int) Redis::get("shorturl:clicks:total:{$code}");
            if ($realTimeClicks > $entity->clicks()) {
                $entity->updateClicks($realTimeClicks);
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }
    private function cacheKey(string $code): string
    {
        return "shorturl:{$code}";
    }
    private function urlHashKey(string $url): string
    {
        return "shorturl:url_hash:" . md5($url);
    }
}
