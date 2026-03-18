<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class FillIdPool extends Command
{
    protected $signature = 'shorturl:fill-id-pool';
    protected $description = 'Fill Redis ID pool for short URLs';

    private const POOL_KEY = 'shorturl:id_pool';
    private const POOL_SIZE = 10000;

    public function handle()
    {
        while (true) {
            $size = Redis::llen(self::POOL_KEY);
            if ($size >= self::POOL_SIZE) {
                sleep(5);
                continue;
            }
            $needed = self::POOL_SIZE - $size;
            $max = Redis::incrby('shorturl:id', $needed);
            $start = $max - $needed + 1;
            for ($i = $start; $i <= $max; $i++) {
                Redis::rpush(self::POOL_KEY, $i);
            }
            $this->info("Filled {$needed} IDs into pool");
        }
    }
}
