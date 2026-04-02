<?php

namespace App\Application\Analytics\Handlers;

use App\Application\Analytics\Queries\GetTrendingUrlsQuery;
use App\Domain\Analytics\Repositories\AnalyticsRepository;
use App\Domain\ShortUrl\Repositories\ShortUrlRepository;

class GetTrendingUrlsQueryHandler
{
    public function __construct(
        private AnalyticsRepository $analyticsRepo,
        private ShortUrlRepository $urlRepo
    ) {}

    public function handle(GetTrendingUrlsQuery $query): array
    {
        $currentHour = $this->analyticsRepo->getTrendingStats(60);
        $previousHour = $this->analyticsRepo->getTrendingStats(120, 60);
        $result = [];
        foreach ($currentHour as $urlId => $clicksNow) {
            if ($clicksNow < 3) continue;
            $clicksBefore = $previousHour[$urlId] ?? 0;
            $url = $this->urlRepo->findById($urlId);
            if (!$url) continue;
            $result[] = [
                'code'   => $url->shortCode(),
                'clicks' => $clicksNow,
                'trend'  => $this->calculateTrend($clicksNow, $clicksBefore),
                'viral'  => $this->detectSpike($urlId, $clicksNow)
            ];
        }
        usort($result, fn($a, $b) => $b['clicks'] <=> $a['clicks']);
        return array_slice($result, 0, 10);
    }

    private function calculateTrend(int $current, int $previous): string
    {
        if ($previous === 0) return $current > 0 ? '+100%' : '0%';
        $change = (($current - $previous) / $previous) * 100;
        return ($change >= 0 ? '+' : '') . round($change, 2) . '%';
    }
    private function detectSpike(string $urlId, int $clicksNow): bool
    {
        $avgPerMinute = $clicksNow / 60;
        $last5Mins = $this->analyticsRepo->getMinuteStats($urlId, 5);
        $recentClicks = array_sum(array_column($last5Mins, 'value'));
        return $recentClicks > ($avgPerMinute * 5 * 3);
    }
}
