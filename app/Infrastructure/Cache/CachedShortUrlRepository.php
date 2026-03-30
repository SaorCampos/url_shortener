<?php

namespace App\Infrastructure\Cache;

use App\Domain\Shared\Cache\CacheService;
use App\Domain\ShortUrl\Entities\ShortUrl;
use App\Domain\ShortUrl\Repositories\ShortUrlRepository;
use Illuminate\Support\Facades\Redis;

class CachedShortUrlRepository implements ShortUrlRepository
{
    private const DEFAULT_TTL = 3600;

    public function __construct(
        private ShortUrlRepository $repository,
        private CacheService $cache
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
        $shortUrl = $this->cache->remember(
            $this->cacheKey($code),
            fn() => $this->repository->findByCode($code),
            self::DEFAULT_TTL
        );
        if ($shortUrl instanceof ShortUrl) {
            $this->syncRealTimeClicks($shortUrl, $code);
        }
        return $shortUrl;
    }

    public function findByOriginalUrl(string $url): ?ShortUrl
    {
        $code = $this->cache->remember(
            $this->urlHashKey($url),
            function() use ($url) {
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
        $realTimeClicks = (int) Redis::get("shorturl:clicks:total:{$code}");
        if ($realTimeClicks > $entity->clicks()) {
            $entity->updateClicks($realTimeClicks);
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
