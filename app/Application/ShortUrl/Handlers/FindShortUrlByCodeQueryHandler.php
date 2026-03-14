<?php

namespace App\Application\ShortUrl\Handlers;

use App\Application\ShortUrl\Queries\FindShortUrlByCodeQuery;
use App\Domain\Shared\Cache\CacheService;
use App\Domain\ShortUrl\Entities\ShortUrl;
use App\Domain\ShortUrl\Repositories\ShortUrlRepository;

class FindShortUrlByCodeQueryHandler
{
    public function __construct(
        private ShortUrlRepository $repository,
        private CacheService $cache
    ) {}

    public function handle(FindShortUrlByCodeQuery $query): ?ShortUrl
    {
        return $this->cache->remember(
            "shorturl:{$query->code}",
            function () use ($query) {
                return $this->repository->findByCode($query->code);
            },
            3600
        );
    }
}
