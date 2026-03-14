<?php

namespace App\Providers\DependencyInjection;

use App\Domain\ShortUrl\Services\ShortCodeGenerator;
use App\Infrastructure\ShortUrl\Services\Base62ShortCodeGenerator;

class ServiceDependencyInjection extends DependencyInjection
{
    protected function repositoriesConfigurations(): array
    {
        return [];
    }

    protected function servicesConfiguration(): array
    {
        return [
            ShortCodeGenerator::class => Base62ShortCodeGenerator::class
        ];
    }
}
