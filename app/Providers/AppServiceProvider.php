<?php

namespace App\Providers;

use App\Domain\ShortUrl\Repositories\ShortUrlRepository;
use App\Infrastructure\Cache\CachedShortUrlRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentShortUrlRepository;
use App\Providers\DependencyInjection\DependencyInjection;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        DependencyInjection::providers($this->app)->each(fn($di) => $di->configure());

        $this->app->bind(
            ShortUrlRepository::class,
            function ($app) {
                return new CachedShortUrlRepository(
                    new EloquentShortUrlRepository()
                );
            }
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
