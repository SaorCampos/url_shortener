<?php

namespace App\Domain\ShortUrl\Exceptions;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UrlNotFoundException extends NotFoundHttpException
{
    public function __construct(string $code)
    {
        parent::__construct("URL '{$code}' not found.");
    }
}
