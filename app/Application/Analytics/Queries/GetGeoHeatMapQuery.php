<?php

namespace App\Application\Analytics\Queries;

class GetGeoHeatMapQuery
{
    public function __construct(
        public string $code
    ) {}
}
