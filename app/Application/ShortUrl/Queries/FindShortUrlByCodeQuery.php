<?php

namespace App\Application\ShortUrl\Queries;

class FindShortUrlByCodeQuery
{
    public function __construct(
        public readonly string $code
    ) {}
}
