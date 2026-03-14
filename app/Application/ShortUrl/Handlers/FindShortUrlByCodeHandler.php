<?php

namespace App\Application\ShortUrl\Handlers;

use App\Application\ShortUrl\Queries\FindShortUrlByCodeQuery;
use App\Domain\ShortUrl\Entities\ShortUrl;
use App\Domain\ShortUrl\Repositories\ShortUrlRepository;
use App\Infrastructure\Cache\RedisService;

class FindShortUrlByCodeHandler
{
    public function __construct(
        private ShortUrlRepository $repository,
        private RedisService $redis
    ) {}

    public function handle(FindShortUrlByCodeQuery $query): ?ShortUrl
    {
        $cacheKey = "short:{$query->code}";
        $cached = $this->redis->get($cacheKey);
        if ($cached) {
            return ShortUrl::restore(
                0,
                $cached,
                $query->code,
                0
            );
        }
        $shortUrl = $this->repository->findByCode($query->code);
        if (!$shortUrl) {
            return null;
        }
        $this->redis->set(
            $cacheKey,
            $shortUrl->originalUrl()
        );
        return $shortUrl;
    }
}
