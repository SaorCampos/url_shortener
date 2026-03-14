<?php

namespace App\Application\Bus;

class QueryBus
{
    public function dispatch(object $query)
    {
       $handler = $this->resolveHandler($query);
        return app($handler)->handle($query);
    }

    private function resolveHandler(object $query): string
    {
        $queryClass = get_class($query);
        return str_replace(
            'Queries',
            'Handlers',
            $queryClass
        ) . 'Handler';
    }
}
