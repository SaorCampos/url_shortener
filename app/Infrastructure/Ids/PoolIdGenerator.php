<?php

namespace App\Infrastructure\Ids;

use App\Domain\Shared\Services\IdGenerator;
use Illuminate\Support\Str;
class PoolIdGenerator implements IdGenerator
{
    public function generate(): string
    {
        return (string) Str::ulid();
    }
}
