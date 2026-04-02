<?php

namespace App\Application\Analytics\Handlers;

use App\Application\Analytics\Queries\GetUrlStatsQuery;
use App\Domain\Analytics\Repositories\AnalyticsRepository;
use App\Domain\ShortUrl\Repositories\ShortUrlRepository;

class GetUrlStatsQueryHandler
{
    public function __construct(
        private AnalyticsRepository $analyticsRepo,
        private ShortUrlRepository $urlRepo
    ) {}

    public function handle(GetUrlStatsQuery $query): array
    {
        $url = $this->urlRepo->findByCode($query->code);
        if (!$url) throw new \Exception("URL not found");
        $stats = $this->analyticsRepo->getMinuteStats($url->id(), 60);
        return [
            'code' => $query->code,
            'labels' => array_column($stats, 'label'),
            'values' => array_column($stats, 'value'),
            'total' => $url->clicks()
        ];
    }
}
