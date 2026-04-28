<?php

namespace App\Domain\Analytics\ValueObjects;

readonly class GeoPoint {
    public function __construct(
        public float $lat,
        public float $lng,
        public int $intensity
    ) {}
}
