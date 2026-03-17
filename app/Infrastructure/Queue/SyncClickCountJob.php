<?php

namespace App\Infrastructure\Queue;

use Illuminate\Support\Facades\Redis;
use App\Domain\ShortUrl\Repositories\ShortUrlRepository;

class SyncClickCountJob
{
    public function __construct(
        private string $code
    ) {}

    public function handle(ShortUrlRepository $repository): void
    {
        $key = "shorturl:clicks:{$this->code}";
        $clicks = Redis::get($key);
        if (!$clicks) {
            return;
        }
        $shortUrl = $repository->findByCode($this->code);
        if (!$shortUrl) {
            return;
        }
        $shortUrl->incrementClicks((int) $clicks);
        $repository->save($shortUrl);
        Redis::del($key);
    }
}
