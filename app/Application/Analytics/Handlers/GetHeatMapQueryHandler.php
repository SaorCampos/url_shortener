<?php

namespace App\Application\Analytics\Handlers;

use App\Application\Analytics\Queries\GetHeatMapQuery;
use App\Domain\Analytics\Repositories\AnalyticsRepository;
use App\Domain\ShortUrl\Exceptions\UrlNotFoundException;
use App\Domain\ShortUrl\Repositories\ShortUrlRepository;

class GetHeatMapQueryHandler
{
    public function __construct(
        private AnalyticsRepository $analyticsRepo,
        private ShortUrlRepository $urlRepo
    ) {}

    public function handle(GetHeatMapQuery $query): array
    {
        $url = $this->urlRepo->findByCode($query->code);
        if (!$url) throw new UrlNotFoundException($query->code);
        $stats = $this->analyticsRepo->getHourHeatmap($url->id());
        return [
            'code' => $query->code,
            'labels' => array_column($stats, 'label'),
            'values' => array_column($stats, 'value'),
            'total' => $url->clicks()
        ];
    }
}
