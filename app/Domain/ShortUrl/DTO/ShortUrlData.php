<?php

namespace App\Domain\ShortUrl\DTO;

use App\Domain\ShortUrl\ValueObjects\ExpirationDate;

readonly class ShortUrlData
{
    public function __construct(
        public int $id,
        public string $originalUrl,
        public string $shortCode,
        public int $clicks,
        public ?ExpirationDate $expiresAt
    ) {}
}
