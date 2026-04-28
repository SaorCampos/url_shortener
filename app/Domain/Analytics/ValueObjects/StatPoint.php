<?php

namespace App\Domain\Analytics\ValueObjects;

readonly class StatPoint {
    public function __construct(
        public string $label,
        public int $value
    ) {}
}
