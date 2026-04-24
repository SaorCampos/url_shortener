<?php

namespace App\Application\ShortUrl\Handlers;

use App\Application\ShortUrl\Commands\CreateShortUrlCommand;
use App\Domain\Shared\Services\IdGenerator;
use App\Domain\ShortUrl\Entities\ShortUrl;
use App\Domain\ShortUrl\Repositories\ShortUrlRepository;
use App\Domain\ShortUrl\Services\Base62Encoder;
use App\Infrastructure\Cache\BloomFilterService;
use Illuminate\Database\UniqueConstraintViolationException;
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
        if ($this->bloomFilter->mightExist("url:{$command->url}")) {
            $existing = $this->repository->findByOriginalUrl($command->url);
            if ($existing) return $existing;
        }
        $id = $this->idGenerator->generate();
        $code = $this->encoder->generate($command->url);
        if ($this->bloomFilter->mightExist("code:{$code}")) {
            $existingByCode = $this->repository->findByCode($code);
            if ($existingByCode) return $existingByCode;
        }
        $shortUrl = ShortUrl::create($id, $command->url, $code);
        try {
            $this->repository->save($shortUrl);
        } catch (UniqueConstraintViolationException $e) {
            return $this->repository->findByCode($code);
        }
        Redis::setex("shorturl:redirect:{$code}", 86400, $command->url);
        $this->bloomFilter->add("url:{$command->url}");
        $this->bloomFilter->add("code:{$code}");
        return $shortUrl;
    }
}
