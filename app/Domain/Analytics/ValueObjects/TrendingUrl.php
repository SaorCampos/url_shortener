<?php

namespace App\Domain\Analytics\ValueObjects;

readonly class TrendingUrl
{
    public function __construct(
        public string $code,
        public int $clicks,
        public string $trend,
        public bool $viral
    ) {}

    public static function fromRaw(string $code, int $clicks, string $trend, bool $viral): self
    {
        return new self($code, $clicks, $trend, $viral);
    }
}
