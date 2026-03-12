<?php

namespace App\Infrastructure\Services;

use App\Domain\ShortUrl\Services\ShortCodeGenerator;

class Base62ShortCodeGenerator implements ShortCodeGenerator
{
    private const ALPHABET =
    '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    public function generate(int $length = 6): string
    {
        $alphabet = self::ALPHABET;
        $max = strlen($alphabet) - 1;
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $alphabet[random_int(0, $max)];
        }
        return $code;
    }
}
