<?php

namespace App\Domain\ShortUrl\Services;

interface ShortCodeGenerator
{
    public function encode(int $id): string;
}
