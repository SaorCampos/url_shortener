<?php

namespace App\Infrastructure\Cache;

use Illuminate\Support\Facades\Redis;

class BloomFilterService
{
    private const KEY = 'shorturl:bloom';
    private const SIZE = 1000000;

    public function add(string $code): void
    {
        $index = abs(crc32($code)) % self::SIZE;
        Redis::setbit(self::KEY, $index, 1);
    }

    public function mightExist(string $code): bool
    {
        $index = abs(crc32($code)) % self::SIZE;
        $bit = Redis::getbit(self::KEY, $index);
        return (bool) $bit;
    }
}
