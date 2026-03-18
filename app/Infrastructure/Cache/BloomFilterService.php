<?php

namespace App\Infrastructure\Cache;

use Illuminate\Support\Facades\Redis;

class BloomFilterService
{
    private const KEY = 'shorturl:bloom';

    public function add(string $code): void
    {
        Redis::setbit(self::KEY, crc32($code) % 1000000, 1);
    }
    public function mightExist(string $code): bool
    {
        return Redis::getbit(self::KEY, crc32($code) % 1000000) === 1;
    }
}
