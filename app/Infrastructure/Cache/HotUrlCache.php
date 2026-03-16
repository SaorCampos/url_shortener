<?php

namespace App\Infrastructure\Cache;

class HotUrlCache
{
    private array $cache = [];
    private array $usage = [];

    public function __construct(
        private int $maxSize = 1000
    ) {}

    public function get(string $code): ?string
    {
        if (!isset($this->cache[$code])) {
            return null;
        }
        $this->usage[$code] = microtime(true);
        return $this->cache[$code];
    }
    public function put(string $code, string $url): void
    {
        if (count($this->cache) >= $this->maxSize) {
            $this->evict();
        }
        $this->cache[$code] = $url;
        $this->usage[$code] = microtime(true);
    }

    private function evict(): void
    {
        asort($this->usage);
        $oldest = array_key_first($this->usage);
        unset($this->cache[$oldest]);
        unset($this->usage[$oldest]);
    }
}
