<?php

namespace App\Application\ShortUrl\Commands;

use DateTimeImmutable;

class CreateShortUrlCommand
{
    public function __construct(
        public string $url,
        public ?DateTimeImmutable $expiresAt = null
    ) {}
}
