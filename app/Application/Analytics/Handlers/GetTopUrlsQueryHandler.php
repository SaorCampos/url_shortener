<?php

namespace App\Application\Analytics\Handlers;

use App\Application\Analytics\Queries\GetTopUrlsQuery;
use App\Domain\Analytics\Repositories\AnalyticsRepository;

class GetTopUrlsQueryHandler
{
    public function __construct(
        private AnalyticsRepository $analyticsRepo
    ) {}

    public function handle(GetTopUrlsQuery $query): array
    {
        $topUrls = $this->analyticsRepo->getTopUrls($query->limit);
        return array_map(function ($item) {
            $item = (object) $item;
            return [
                'code'   => (string) $item->code,
                'clicks' => (int) $item->clicks
            ];
        }, $topUrls);
    }
}
