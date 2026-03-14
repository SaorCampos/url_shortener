<?php

namespace App\Infrastructure\Cache;

use Illuminate\Support\Facades\Redis;
use App\Domain\Shared\Cache\CacheService;

class RedisService implements CacheService
{
    private string $prefix = 'url_shortener:';

    private function key(string $key): string
    {
        return $this->prefix . $key;
    }

    public function get(string $key): mixed
    {
        $value = Redis::get($this->key($key));
        return $value ? json_decode($value, true) : null;
    }
    public function set(string $key, mixed $value, int $ttl = 3600): void
    {
        Redis::setex(
            $this->key($key),
            $ttl,
            json_encode($value)
        );
    }
    public function remember(string $key, callable $callback, int $ttl = 3600):mixed
    {
        $cached = Redis::get($key);
        if ($cached) {
            return unserialize($cached);
        }
        $value = $callback();
        Redis::setex(
            $key,
            $ttl,
            serialize($value)
        );
        return $value;
    }
    public function forget(string $key): void
    {
        Redis::del($this->key($key));
    }
}
