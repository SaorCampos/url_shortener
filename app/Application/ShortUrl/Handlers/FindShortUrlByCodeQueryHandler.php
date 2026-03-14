<?php

namespace App\Application\ShortUrl\Handlers;

use App\Application\ShortUrl\Queries\FindShortUrlByCodeQuery;
use App\Domain\ShortUrl\Repositories\ShortUrlRepository;
use App\Domain\ShortUrl\Entities\ShortUrl;

class FindShortUrlByCodeQueryHandler
{
    public function __construct(
        private ShortUrlRepository $repository
    ) {}

    public function handle(FindShortUrlByCodeQuery $query): ?ShortUrl
    {
        return $this->repository->findByCode($query->code);
    }
}
