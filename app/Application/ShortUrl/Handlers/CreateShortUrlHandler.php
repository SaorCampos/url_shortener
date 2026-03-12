<?php

namespace App\Application\ShortUrl\Handlers;

use App\Application\ShortUrl\Commands\CreateShortUrlCommand;
use App\Domain\ShortUrl\Entities\ShortUrl;
use App\Domain\ShortUrl\Repositories\ShortUrlRepository;
use App\Domain\ShortUrl\Services\ShortCodeGenerator;

class CreateShortUrlHandler
{
    public function __construct(
        private ShortUrlRepository $repository,
        private ShortCodeGenerator $shortCodeGenerator,
    ) {}

    public function handle(CreateShortUrlCommand $command): ShortUrl
    {
        $shortUrl = ShortUrl::create(
            $command->url,
            ''
        );
        $shortUrl = $this->repository->save($shortUrl);
        $code = $this->shortCodeGenerator->generate($shortUrl->id());
        $shortUrl = new ShortUrl(
            $shortUrl->id(),
            $shortUrl->originalUrl(),
            $code,
            $shortUrl->clicks()
        );
        return $this->repository->save($shortUrl);
    }
}
