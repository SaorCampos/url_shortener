<?php

namespace App\Application\ShortUrl\Handlers;

use App\Application\ShortUrl\Commands\CreateShortUrlCommand;
use App\Domain\Shared\Services\IdGenerator;
use App\Domain\ShortUrl\Entities\ShortUrl;
use App\Domain\ShortUrl\Repositories\ShortUrlRepository;
use App\Domain\ShortUrl\Services\Base62Encoder;
use App\Infrastructure\Cache\BloomFilterService;
use Illuminate\Support\Facades\Redis;

class CreateShortUrlCommandHandler
{
    public function __construct(
        private ShortUrlRepository $repository,
        private Base62Encoder $encoder,
        private IdGenerator $idGenerator,
        private BloomFilterService $bloomFilter,
    ) {}

    public function handle(CreateShortUrlCommand $command): ShortUrl
    {
        $id = $this->idGenerator->generate();
        $obfuscatedId = ($id * 2654435761) & 0xFFFFFFFF;
        $code = $this->encoder->encode($obfuscatedId);
        $shortUrl = ShortUrl::create(
            $id,
            $command->url,
            $code,
            $command->expiresAt
        );
        $shortUrl = $this->repository->save($shortUrl);
        Redis::setex(
            "shorturl:redirect:{$code}",
            86400,
            $command->url
        );
        $this->bloomFilter->add($code);
        return $shortUrl;
    }
}
