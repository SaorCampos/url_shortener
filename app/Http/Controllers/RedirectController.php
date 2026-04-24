<?php

namespace App\Http\Controllers;

use App\Application\Bus\QueryBus;
use App\Application\ShortUrl\Queries\FindShortUrlByCodeQuery;
use App\Domain\ShortUrl\Events\ShortUrlAccessed;
use App\Domain\ShortUrl\Exceptions\UrlNotFoundException;
use Throwable;

class RedirectController extends Controller
{
    public function __construct(private QueryBus $queryBus) {}

    public function __invoke(string $code)
    {
        try {
            $shortUrl = $this->queryBus->dispatch(new FindShortUrlByCodeQuery($code));
            if ($shortUrl->isExpired()) {
                abort(404);
            }
            event(new ShortUrlAccessed(
                code: $code,
                ip: request()->ip(),
                userAgent: request()->userAgent(),
                referer: request()->header('Referer'),
                timestamp: now()->timestamp
            ));
            return redirect()->away($shortUrl->originalUrl());
        } catch (UrlNotFoundException $e) {
            abort(404);
        } catch (Throwable $e) {
            report($e);
            abort(500);
        }
    }
}
