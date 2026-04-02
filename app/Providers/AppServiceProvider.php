<?php

namespace App\Providers;

use App\Domain\Analytics\Repositories\AnalyticsRepository;
use App\Domain\Shared\Cache\CacheService;
use App\Domain\Shared\Services\IdGenerator;
use App\Domain\ShortUrl\Repositories\ShortUrlRepository;
use App\Domain\ShortUrl\Services\Base62Encoder;
use App\Domain\ShortUrl\Services\ShortCodeGenerator;
use App\Infrastructure\Cache\CachedAnalyticsRepository;
use App\Infrastructure\Cache\CachedShortUrlRepository;
use App\Infrastructure\Cache\HotUrlCache;
use App\Infrastructure\Cache\RedisService;
use App\Infrastructure\Ids\PoolIdGenerator;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentAnalyticsRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentShortUrlRepository;
use Illuminate\Http\Request;
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
        if (app()->environment('local')) {
            Request::setTrustedProxies(
                ['0.0.0.0/0', '127.0.0.1'],
                Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_HOST
            );
        }
    }

    private function registerRepositories(): void
    {
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
        $this->app->singleton(AnalyticsRepository::class, function ($app) {
            return new CachedAnalyticsRepository(
                $app->make(EloquentAnalyticsRepository::class),
                $app->make(CacheService::class)
            );
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
