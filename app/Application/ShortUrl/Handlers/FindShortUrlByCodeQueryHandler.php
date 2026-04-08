<?php

namespace App\Application\ShortUrl\Handlers;

use App\Application\ShortUrl\Queries\FindShortUrlByCodeQuery;
use App\Domain\ShortUrl\Entities\ShortUrl;
use App\Domain\ShortUrl\Exceptions\UrlNotFoundException;
use App\Domain\ShortUrl\Repositories\ShortUrlRepository;

class FindShortUrlByCodeQueryHandler
{
    public function __construct(
        private ShortUrlRepository $repository
    ) {}

    public function handle(FindShortUrlByCodeQuery $query): ?ShortUrl
    {
        $shortUrl = $this->repository->findByCode($query->code);
        if(!$shortUrl) throw new UrlNotFoundException($query->code);
        return $shortUrl;
    }
}
