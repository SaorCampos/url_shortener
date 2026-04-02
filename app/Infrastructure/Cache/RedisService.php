<?php

namespace App\Infrastructure\Cache;

use Illuminate\Support\Facades\Redis;
use App\Domain\Shared\Cache\CacheService;

class RedisService implements CacheService
{
    private string $prefix = 'url_shortener:';

    private function key(string $key): string
    {
        if (str_starts_with($key, $this->prefix)) {
            return $key;
        }
        return $this->prefix . $key;
    }
    public function get(string $key): mixed
    {
        $value = Redis::get($this->key($key));
        return $value ? unserialize($value) : null;
    }
    public function set(string $key, mixed $value, int $ttl): void
    {
        Redis::setex(
            $this->key($key),
            $ttl,
            serialize($value)
        );
    }
    public function remember(string $key, callable $callback, int $ttl): mixed
    {
        $value = $this->get($key);
        if ($value !== null) {
            return $value;
        }
        $value = $callback();
        if ($value !== null) {
            $this->set($key, $value, $ttl);
        }
        return $value;
    }
    public function forget(string $key): void
    {
        Redis::del($this->key($key));
    }
}
