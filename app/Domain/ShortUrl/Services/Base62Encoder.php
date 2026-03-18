<?php

namespace App\Domain\ShortUrl\Services;

class Base62Encoder implements ShortCodeGenerator
{
    private const ALPHABET = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    public function encode(int $number): string
    {
        if ($number === 0) {
            return str_repeat('0', 6);
        }
        $base = strlen(self::ALPHABET);
        $result = '';
        while ($number > 0) {
            $result = self::ALPHABET[$number % $base] . $result;
            $number = intdiv($number, $base);
        }
        return str_pad($result, 6, '0', STR_PAD_LEFT);
    }
}
