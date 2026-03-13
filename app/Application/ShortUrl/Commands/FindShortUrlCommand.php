<?php

namespace App\Application\ShortUrl\Commands;

use App\Application\Bus\Command;

class FindShortUrlCommand implements Command
{
    public function __construct(
        public string $code
    ) {}
}
