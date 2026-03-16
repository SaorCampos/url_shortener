<?php

namespace App\Infrastructure\Ids;

use App\Domain\Shared\Services\IdGenerator;
use Illuminate\Support\Facades\Redis;

class PoolIdGenerator implements IdGenerator
{
    public function generate(): int
    {
        $id = Redis::lpop('shorturl:id_pool');
        if (!$id) {
            throw new \RuntimeException('ID pool empty');
        }
        return (int) $id;
    }
}
