<?php

namespace App\Http\Controllers;

use App\Application\Bus\CommandBus;
use App\Application\ShortUrl\Commands\CreateShortUrlCommand;
use App\Application\ShortUrl\Handlers\FindShortUrlByCodeQueryHandler;
use App\Application\ShortUrl\Handlers\FindShortUrlHandler;
use App\Application\ShortUrl\Queries\FindShortUrlByCodeQuery;
use App\Http\Request\CreateShortUrlRequest;
use Symfony\Component\HttpFoundation\Response;

class ShortUrlController extends Controller
{
    public function __construct(
        private CommandBus $commandBus,
        private FindShortUrlHandler $findShortUrlHandler,
        private FindShortUrlByCodeQueryHandler $findShortUrlByCodeQueryHandler
    ) {}

    public function create(CreateShortUrlRequest $request): Response
    {
        $shortUrl = $this->commandBus->dispatch(
            new CreateShortUrlCommand($request->url)
        );
        return response()->json([
            'id' => $shortUrl->id(),
            'url' => $shortUrl->originalUrl(),
            'short_code' => $shortUrl->shortCode(),
            'short_url' => url('/' . $shortUrl->shortCode())
        ], 201);
    }
    public function findByCode(string $code): Response
    {
        $query = new FindShortUrlByCodeQuery($code);
        $shortUrl = $this->findShortUrlHandler->handle($query);
        if (!$shortUrl) {
            return response()->json(['message' => 'Short URL not found'], 404);
        }
        return redirect($shortUrl->originalUrl());
    }
    public function redirect(string $code)
    {
        $query = new FindShortUrlByCodeQuery($code);
        $shortUrl = $this->findShortUrlByCodeQueryHandler->handle($query);
        if (!$shortUrl) {
            abort(404);
        }
        return redirect()->away($shortUrl->originalUrl());
    }
}
