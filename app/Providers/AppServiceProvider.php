<?php

namespace App\Providers;

use App\Domain\Shared\Cache\CacheService;
use App\Domain\Shared\Services\IdGenerator;
use App\Domain\ShortUrl\Repositories\ShortUrlRepository;
use App\Domain\ShortUrl\Services\Base62Encoder;
use App\Domain\ShortUrl\Services\ShortCodeGenerator;
use App\Infrastructure\Cache\CachedShortUrlRepository;
use App\Infrastructure\Cache\HotUrlCache;
use App\Infrastructure\Cache\RedisService;
use App\Infrastructure\Ids\PoolIdGenerator;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentShortUrlRepository;
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
        $this->app->bind(
            ShortUrlRepository::class,
            function ($app) {
                return new CachedShortUrlRepository(
                    $app->make(EloquentShortUrlRepository::class),
                    $app->make(CacheService::class)
                );
            }
        );
        $this->app->singleton(HotUrlCache::class, function () {
            return new HotUrlCache(1000);
        });
    }
    private function registerServices(): void
    {
        $this->app->bind(
            ShortCodeGenerator::class,
            Base62Encoder::class
        );
        $this->app->singleton(
            CacheService::class,
            RedisService::class
        );
        $this->app->singleton(
            IdGenerator::class,
            PoolIdGenerator::class
        );
    }
}
