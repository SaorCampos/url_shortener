<?php

namespace App\Http\Controllers;

use App\Application\Analytics\Queries\GetCountriesQuery;
use App\Application\Analytics\Queries\GetGeoHeatMapQuery;
use App\Application\Analytics\Queries\GetHeatMapQuery;
use App\Application\Analytics\Queries\GetTopUrlsQuery;
use App\Application\Analytics\Queries\GetTrendingUrlsQuery;
use App\Application\Analytics\Queries\GetUrlStatsQuery;
use App\Application\Bus\QueryBus;

class AnalyticsController extends Controller
{
    public function __construct(
        private QueryBus $queryBus,
    )
    {}

    public function analytics(string $code)
    {
        $analytics = $this->queryBus->dispatch(new GetUrlStatsQuery($code));
        return response()->json($analytics);
    }
    public function top()
    {
        $top = $this->queryBus->dispatch(new GetTopUrlsQuery());
        return response()->json($top);
    }
    public function topLastHour()
    {
        $trending = $this->queryBus->dispatch(new GetTrendingUrlsQuery());
        return response()->json($trending);
    }
    public function countries(string $code)
    {
        $contries = $this->queryBus->dispatch(new GetCountriesQuery($code));
        return response()->json($contries);
    }
    public function heatmap(string $code)
    {
        $heatmap = $this->queryBus->dispatch(new GetHeatMapQuery($code));
        return response()->json($heatmap);
    }
    public function geoHeatmap(string $code)
    {
        $geoHeatmap = $this->queryBus->dispatch(new GetGeoHeatMapQuery($code));
        return response()->json($geoHeatmap);
    }
}
