<?php

namespace App\Infrastructure\Cache;

use App\Domain\ShortUrl\Repositories\ShortUrlRepository;
use App\Domain\ShortUrl\Entities\ShortUrl;
use App\Domain\Shared\Cache\CacheService;

class CachedShortUrlRepository implements ShortUrlRepository
{
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
        $key = $this->cacheKey($code);
        $cached = $this->cache->get($key);
        if ($cached !== null) {
            return $cached;
        }
        $dbUrl = $this->repository->findByCode($code);
        if ($dbUrl) {
            $this->cache->set($key, $dbUrl, 3600);
        }
        return $dbUrl;
    }
    public function findById(string $id): ?ShortUrl
    {
        return $this->repository->findById($id);
    }
    public function findByOriginalUrl(string $url): ?ShortUrl
    {
        $key = $this->urlHashKey($url);
        $code = $this->cache->get($key);
        if (!$code) {
            $dbUrl = $this->repository->findByOriginalUrl($url);
            if (!$dbUrl) {
                return null;
            }
            $code = $dbUrl->shortCode();
            $this->cache->set($key, $code, 3600);
        }
        return $this->findByCode($code);
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
