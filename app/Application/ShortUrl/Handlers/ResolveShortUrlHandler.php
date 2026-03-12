<?php

namespace App\Application\ShortUrl\Handlers;

use App\Domain\ShortUrl\Repositories\ShortUrlRepository;

class ResolveShortUrlHandler
{
    public function __construct(
        private ShortUrlRepository $repository
    ) {}

    public function handle(string $code): ?string
    {
        $shortUrl = $this->repository->findByCode($code);
        if (!$shortUrl) {
            return null;
        }
        return $shortUrl->originalUrl();
    }
    public function redirect(
        string $code,
        ResolveShortUrlHandler $handler
    ) {
        $url = $handler->handle($code);
        if (!$url) {
            abort(404);
        }
        return redirect()->away($url);
    }
}
