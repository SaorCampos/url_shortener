<?php

namespace App\Domain\Analytics\ValueObjects;

readonly class CountryStats{
    public function __construct(
        public string $country,
        public int $clicks
    )
    {}
}
