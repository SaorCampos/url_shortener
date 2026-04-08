<?php

namespace App\Domain\ShortUrl\Services;

class Base62Encoder
{
    private const ALPHABET = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    public function generate(string $url): string
    {
        $hash = hash('crc32b', $url);
        $integer = hexdec($hash);
        return $this->encode($integer);
    }

    private function encode(int $number): string
    {
        $res = '';
        while ($number > 0) {
            $res = self::ALPHABET[$number % 62] . $res;
            $number = intdiv($number, 62);
        }
        return str_pad($res, 6, '0', STR_PAD_LEFT);
    }
}
