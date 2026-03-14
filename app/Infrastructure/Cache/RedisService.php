<?php

namespace App\Infrastructure\Cache;

use Illuminate\Support\Facades\Redis;

class RedisService
{
    public function get(string $key): ?string
    {
        return Redis::get($key);
    }

    public function set(string $key, string $value, int $ttl = 3600): void
    {
        Redis::setex($key, $ttl, $value);
    }
}
