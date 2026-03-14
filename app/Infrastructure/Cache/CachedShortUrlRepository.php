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

        $this->cache->forget(
            $this->cacheKey($saved->shortCode())
        );

        return $saved;
    }
    public function findByCode(string $code): ?ShortUrl
    {
        return $this->cache->remember(
            $this->cacheKey($code),
            fn () => $this->repository->findByCode($code),
            3600
        );
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
