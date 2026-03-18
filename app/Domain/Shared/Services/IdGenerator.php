<?php

namespace App\Domain\Shared\Services;

interface IdGenerator
{
    public function generate(): int;
}
