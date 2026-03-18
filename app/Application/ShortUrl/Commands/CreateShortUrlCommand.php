<?php

namespace App\Application\ShortUrl\Commands;


class CreateShortUrlCommand
{
    public function __construct(
        public string $url,
    ) {}
}
