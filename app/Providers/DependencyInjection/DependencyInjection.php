<?php

namespace App\Providers\DependencyInjection;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Collection;

abstract class DependencyInjection
{
    abstract protected function repositoriesConfigurations(): array;
    abstract protected function servicesConfiguration(): array;

    public function __construct(
        protected Application $app
    ) {}

    public static function providers(Application $app): Collection
    {
        return collect([
            new RepositoryDependencyInjection($app),
            new ServiceDependencyInjection($app)
        ]);
    }

    public function configure(): void
    {
        $configurations = array_merge(
            $this->repositoriesConfigurations(),
            $this->servicesConfiguration()
        );
        foreach ($configurations as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }
    }
}
