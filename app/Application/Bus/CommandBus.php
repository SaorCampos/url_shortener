<?php

namespace App\Application\Bus;

use Illuminate\Contracts\Container\Container;

class CommandBus
{
    public function __construct(
        private Container $container
    ) {}

    public function dispatch(Command $command)
    {
        $handlerClass = $this->resolveHandler($command);
        $handler = $this->container->make($handlerClass);
        return $handler->handle($command);
    }

    private function resolveHandler(Command $command): string
    {
        return str_replace(
            'Commands',
            'Handlers',
            str_replace('Command', 'Handler', get_class($command))
        );
    }
}
