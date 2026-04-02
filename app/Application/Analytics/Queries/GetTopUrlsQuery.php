<?php

namespace App\Application\Analytics\Queries;

class GetTopUrlsQuery
{
    public function __construct(
        public int $limit = 10
    ) {}
}
