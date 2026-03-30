<?php

namespace App\Application\Analytics\Queries;

class GetHeatMapQuery
{
    public function __construct(
        public string $code
    ) {}
}
