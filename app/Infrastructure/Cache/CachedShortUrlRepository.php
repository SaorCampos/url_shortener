<?php

namespace App\Infrastructure\Cache;

use App\Domain\Shared\Cache\CacheService;
use App\Domain\ShortUrl\Entities\ShortUrl;
use App\Domain\ShortUrl\Repositories\ShortUrlRepository;
use Illuminate\Support\Facades\Redis;

class CachedShortUrlRepository implements ShortUrlRepository
{
    public function __construct(
        private ShortUrlRepository $repository,
        private CacheService $cache
    ) {}

    public function save(ShortUrl $url): ShortUrl
    {
        $saved = $this->repository->save($url);

        $this->cache->forget(
            $this->cacheKey($saved->shortCode())
        );

        return $saved;
    }
    public function findByCode(string $code): ?ShortUrl
    {
        $shortUrl = $this->cache->remember(
            $this->cacheKey($code),
            fn() => $this->repository->findByCode($code),
            3600
        );
        if (!$shortUrl) return null;
        $liveClicks = (int) Redis::get("shorturl:clicks:total:{$code}");
        if ($liveClicks > $shortUrl->clicks()) {
            $shortUrl->incrementClicks($liveClicks - $shortUrl->clicks());
        }
        return $shortUrl;
    }
    public function findById(int $id): ?ShortUrl
    {
        return $this->repository->findById($id);
    }
    private function cacheKey(string $code): string
    {
        return "shorturl:{$code}";
    }
}
