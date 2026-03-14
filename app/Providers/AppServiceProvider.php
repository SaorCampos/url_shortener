<?php

namespace App\Providers;

use App\Domain\Shared\Cache\CacheService;
use App\Domain\ShortUrl\Repositories\ShortUrlRepository;
use App\Domain\ShortUrl\Services\ShortCodeGenerator;
use App\Infrastructure\Cache\CachedShortUrlRepository;
use App\Infrastructure\Cache\RedisService;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentShortUrlRepository;
use App\Infrastructure\ShortUrl\Services\Base62ShortCodeGenerator;
use App\Providers\DependencyInjection\DependencyInjection;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->registerRepositories();
        $this->registerServices();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }

    private function registerRepositories(): void
    {
        $this->app->bind(
            ShortUrlRepository::class,
            CachedShortUrlRepository::class
        );
    }
    private function registerServices(): void
    {
        $this->app->bind(
            ShortCodeGenerator::class,
            Base62ShortCodeGenerator::class
        );
        $this->app->singleton(
            CacheService::class,
            RedisService::class
        );
    }
}
