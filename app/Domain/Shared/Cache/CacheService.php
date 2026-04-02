<?php

namespace App\Domain\Shared\Cache;

interface CacheService
{
    public function get(string $key): mixed;
    public function set(string $key, mixed $value, int $ttl): void;
    public function remember(string $key, callable $callback, int $ttl): mixed;
    public function forget(string $key): void;
}
