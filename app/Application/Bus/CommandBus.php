<?php

namespace App\Application\Bus;

class CommandBus
{
    public function dispatch(object $command)
    {
       $handler = $this->resolveHandler($command);
        return app($handler)->handle($command);
    }

    private function resolveHandler(object $command): string
    {
        $commandClass = get_class($command);
        return str_replace(
            'Commands',
            'Handlers',
            $commandClass
        ) . 'Handler';
    }
}
