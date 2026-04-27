<?php

namespace App\Domain\ShortUrl\Events;

class ShortUrlAccessed
{
    public function __construct(
        public readonly string $code,
        public readonly string $ip,
        public readonly ?string $userAgent,
        public readonly ?string $referer,
        public readonly int $timestamp
    ) {}
}
