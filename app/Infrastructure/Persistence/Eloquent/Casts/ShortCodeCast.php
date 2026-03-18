<?php

namespace App\Infrastructure\Persistence\Eloquent\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use App\Domain\ShortUrl\ValueObjects\ShortCode;

class ShortCodeCast implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes): ShortCode
    {
        return ShortCode::from($value);
    }
    public function set($model, string $key, $value, array $attributes): string
    {
        if ($value instanceof ShortCode) {
            return $value->value();
        }
        return (string) $value;
    }
}
