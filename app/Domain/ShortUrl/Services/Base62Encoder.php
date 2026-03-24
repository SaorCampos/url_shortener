<?php

namespace App\Domain\ShortUrl\Services;

class Base62Encoder implements ShortCodeGenerator
{

    public function generate(string $url): string
    {
        $hash = md5($url);
        return substr(base64_encode(hex2bin($hash)), 0, 6);
    }
}
