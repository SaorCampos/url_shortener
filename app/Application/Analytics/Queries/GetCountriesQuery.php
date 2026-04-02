<?php

namespace App\Application\Analytics\Queries;

class GetCountriesQuery
{
    public function __construct(
        public string $code,
        public int $days = 30
    ) {}
}
