<?php

namespace App\Application\ShortUrl\Commands;

use App\Application\Bus\Command;

class CreateShortUrlCommand implements Command
{
    public function __construct(
        public string $url
    ) {}
}
