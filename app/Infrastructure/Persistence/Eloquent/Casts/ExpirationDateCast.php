<?php

namespace App\Infrastructure\Persistence\Eloquent\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use App\Domain\ShortUrl\ValueObjects\ExpirationDate;
use DateTimeImmutable;
use DateTimeInterface;

class ExpirationDateCast implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes): ?ExpirationDate
    {
        if (!$value) {
            return null;
        }
        return ExpirationDate::from(new DateTimeImmutable($value));
    }

    public function set($model, string $key, $value, array $attributes): ?string
    {
        if (!$value) {
            return null;
        }
        if ($value instanceof ExpirationDate) {
            return $value->value()->format('Y-m-d H:i:s');
        }
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }
        if (is_string($value)) {
            return (new DateTimeImmutable($value))->format('Y-m-d H:i:s');
        }
        throw new \InvalidArgumentException('Invalid ExpirationDate: ' . get_debug_type($value));
    }
}
