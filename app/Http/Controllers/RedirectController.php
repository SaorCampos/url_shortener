<?php

namespace App\Http\Controllers;

use App\Application\Bus\QueryBus;
use App\Application\ShortUrl\Queries\FindShortUrlByCodeQuery;

class RedirectController extends Controller
{
    public function __construct(
        private QueryBus $queryBus
    ) {}

    public function __invoke(string $code)
    {
        $shortUrl = $this->queryBus->dispatch(
            new FindShortUrlByCodeQuery($code)
        );
        if (!$shortUrl) {
            abort(404);
        }
        return redirect()->away($shortUrl->originalUrl());
    }
}
