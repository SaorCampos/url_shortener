<?php

namespace App\Infrastructure\Services;

use App\Domain\ShortUrl\Services\ShortCodeGenerator;

class Base62ShortCodeGenerator implements ShortCodeGenerator
{
    private const ALPHABET =
        '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    public function generate(int $id): string
    {
        $base = strlen(self::ALPHABET);
        $code = '';
        while ($id > 0) {
            $code = self::ALPHABET[$id % $base] . $code;
            $id = intdiv($id, $base);
        }
        return $code ?: '0';
    }
}
