<?php

namespace App\Domain\Analytics\ValueObjects;

readonly class TopUrl {
    public function __construct(
        public string $code,
        public int $clicks
    ) {}
}
