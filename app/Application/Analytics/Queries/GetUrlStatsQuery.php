<?php

namespace App\Application\Analytics\Queries;

class GetUrlStatsQuery
{
    public function __construct(
        public string $code,
    ) {}
}
