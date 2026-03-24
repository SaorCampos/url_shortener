<?php

namespace App\Domain\ShortUrl\Repositories;

use App\Domain\ShortUrl\Entities\ShortUrl;

interface ShortUrlRepository
{
    public function save(ShortUrl $url): ShortUrl;
    public function findByCode(string $code): ?ShortUrl;
    public function findById(string $id): ?ShortUrl;
    public function findByOriginalUrl(string $url): ?ShortUrl;
}
