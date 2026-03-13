<?php

namespace App\Application\ShortUrl\Handlers;

use App\Application\ShortUrl\Commands\FindShortUrlCommand;
use App\Domain\ShortUrl\Entities\ShortUrl;
use App\Domain\ShortUrl\Repositories\ShortUrlRepository;

class FindShortUrlHandler
{
    public function __construct(
        private ShortUrlRepository $repository,
    ) {}
    public function handle(FindShortUrlCommand $command): ?ShortUrl
    {
        return $this->repository->findByCode($command->code);
    }
}
