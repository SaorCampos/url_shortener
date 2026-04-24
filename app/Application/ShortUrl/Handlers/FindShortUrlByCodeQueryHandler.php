<?php

namespace App\Application\ShortUrl\Handlers;

use App\Application\ShortUrl\Queries\FindShortUrlByCodeQuery;
use App\Domain\ShortUrl\Entities\ShortUrl;
use App\Domain\ShortUrl\Exceptions\UrlNotFoundException;
use App\Domain\ShortUrl\Repositories\ShortUrlRepository;
use App\Infrastructure\Cache\BloomFilterService;

class FindShortUrlByCodeQueryHandler
{
    public function __construct(
        private ShortUrlRepository $repository,
        private BloomFilterService $bloomFilter
    ) {}

    public function handle(FindShortUrlByCodeQuery $query): ShortUrl
    {
        if (!$this->bloomFilter->mightExist("code:{$query->code}")) {
            throw new UrlNotFoundException($query->code);
        }
        $shortUrl = $this->repository->findByCode($query->code);
        if (!$shortUrl) {
            throw new UrlNotFoundException($query->code);
        }
        return $shortUrl;
    }
}
