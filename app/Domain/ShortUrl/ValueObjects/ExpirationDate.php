<?php

namespace App\Domain\ShortUrl\ValueObjects;

use DateTimeImmutable;

class ExpirationDate
{
    public function __construct(
        private DateTimeImmutable $value
    ) {}

    public static function inDays(int $days): self
    {
        return new self(new DateTimeImmutable("+{$days} days"));
    }
    public static function from(?DateTimeImmutable $date): ?self
    {
        return $date ? new self($date) : null;
    }

    public function isExpired(): bool
    {
        return new DateTimeImmutable() > $this->value;
    }
    public function value(): DateTimeImmutable
    {
        return $this->value;
    }
}
