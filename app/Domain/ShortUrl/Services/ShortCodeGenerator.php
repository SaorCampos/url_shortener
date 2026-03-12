<?php

namespace App\Domain\ShortUrl\Services;

interface ShortCodeGenerator
{
    public function generate(int $id): string;
}
