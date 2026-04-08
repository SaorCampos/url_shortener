<?php

namespace App\Application\Analytics\Handlers;

use App\Application\Analytics\Queries\GetCountriesQuery;
use App\Domain\Analytics\Repositories\AnalyticsRepository;
use App\Domain\ShortUrl\Exceptions\UrlNotFoundException;
use App\Domain\ShortUrl\Repositories\ShortUrlRepository;

class GetCountriesQueryHandler
{
    public function __construct(
        private AnalyticsRepository $analyticsRepo,
        private ShortUrlRepository $urlRepo,
    ) {}

    public function handle(GetCountriesQuery $query): array
    {
        $url = $this->urlRepo->findByCode($query->code);
        if (!$url) throw new UrlNotFoundException($query->code);
        return $this->analyticsRepo->getCountryStats($url->id(), $query->days);
    }
}
