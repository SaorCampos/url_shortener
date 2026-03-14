<?php

namespace App\Domain\Shared\Cache;

interface CacheService
{
    public function get(string $key): mixed;
    public function set(string $key, mixed $value, int $ttl = 3600): void;
    public function remember(string $key, callable $callback, int $ttl = 3600): mixed;
    public function forget(string $key): void;
}
