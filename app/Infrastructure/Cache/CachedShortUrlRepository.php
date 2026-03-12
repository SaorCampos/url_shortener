<?php

namespace App\Infrastructure\Cache;

use App\Domain\ShortUrl\Repositories\ShortUrlRepository;
use App\Domain\ShortUrl\Entities\ShortUrl;
use Illuminate\Support\Facades\Cache;

class CachedShortUrlRepository implements ShortUrlRepository
{
    public function __construct(
        private ShortUrlRepository $repository
    ) {}

    public function save(ShortUrl $url): ShortUrl
    {
        $saved = $this->repository->save($url);
        Cache::forget($this->cacheKey($saved->shortCode()));
        return $saved;
    }
    public function findByCode(string $code): ?ShortUrl
    {
        return Cache::remember(
            $this->cacheKey($code),
            3600,
            fn() => $this->repository->findByCode($code)
        );
    }
    public function findById(int $id): ?ShortUrl
    {
        return $this->repository->findById($id);
    }

    private function cacheKey(string $code): string
    {
        return "short_url:{$code}";
    }
}
