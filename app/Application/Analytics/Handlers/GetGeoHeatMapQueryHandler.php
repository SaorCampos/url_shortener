<?php

namespace App\Application\Analytics\Handlers;

use App\Application\Analytics\Queries\GetGeoHeatMapQuery;
use App\Domain\Analytics\Repositories\AnalyticsRepository;
use App\Domain\ShortUrl\Repositories\ShortUrlRepository;

class GetGeoHeatMapQueryHandler
{
    public function __construct(
        private AnalyticsRepository $analyticsRepo,
        private ShortUrlRepository $urlRepo,
    ) {}

    public function handle(GetGeoHeatMapQuery $query): array
    {
        $url = $this->urlRepo->findByCode($query->code);
        if (!$url) throw new \Exception("URL not found");
        return $this->analyticsRepo->getGeoPoints($url->id());
    }
}
