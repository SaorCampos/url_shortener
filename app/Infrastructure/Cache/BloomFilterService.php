<?php

namespace App\Infrastructure\Cache;

use Illuminate\Support\Facades\Redis;

class BloomFilterService
{
    private const KEY = 'shorturl:bloom';
    private const SIZE = 1000000;

    public function add(string $value): void
    {
        try {
            foreach ($this->getIndices($value) as $index) {
                Redis::setbit(self::KEY, $index, 1);
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function mightExist(string $value): bool
    {
        try {
            foreach ($this->getIndices($value) as $index) {
                if (!Redis::getbit(self::KEY, $index)) {
                    return false;
                }
            }
            return true;
        } catch (\Throwable $e) {
            report($e);
            return true;
        }
    }

    private function getIndices(string $value): array
    {
        return [
            abs(crc32($value)) % self::SIZE,
            abs(hexdec(substr(md5($value), 0, 8))) % self::SIZE,
            abs(hexdec(substr(sha1($value), 0, 8))) % self::SIZE,
        ];
    }
}
