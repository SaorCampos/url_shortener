<?php

namespace App\Application\ShortUrl\Handlers;

use App\Application\ShortUrl\Commands\CreateShortUrlCommand;
use App\Domain\ShortUrl\Entities\ShortUrl;
use App\Domain\ShortUrl\Repositories\ShortUrlRepository;
use App\Domain\ShortUrl\Services\ShortCodeGenerator;

class CreateShortUrlCommandHandler
{
    public function __construct(
        private ShortUrlRepository $repository,
        private ShortCodeGenerator $shortCodeGenerator,
    ) {}

    public function handle(CreateShortUrlCommand $command): ShortUrl
    {
        $code = $this->shortCodeGenerator->generate();
        $shortUrl = ShortUrl::create(
            $command->url,
            $code
        );
        return $this->repository->save($shortUrl);
    }
}
