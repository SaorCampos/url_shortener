<?php

namespace App\Domain\ShortUrl\Services;

interface ShortCodeGenerator
{
    public function generate(int $length = 6): string;
}
