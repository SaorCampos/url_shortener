<?php

namespace App\Providers\DependencyInjection;

use App\Domain\ShortUrl\Repositories\ShortUrlRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentShortUrlRepository;
use App\Providers\DependencyInjection\DependencyInjection;

class RepositoryDependencyInjection extends DependencyInjection
{
     protected function repositoriesConfigurations(): array
    {
        return [
            [ShortUrlRepository::class, EloquentShortUrlRepository::class]
        ];
    }

    protected function servicesConfiguration(): array
    {
        return [];
    }
}
