<?php

namespace App\Http\Controllers;

use App\Application\ShortUrl\Commands\CreateShortUrlCommand;
use App\Application\ShortUrl\Handlers\CreateShortUrlHandler;
use App\Application\ShortUrl\Handlers\FindShortUrlByCodeHandler;
use App\Application\ShortUrl\Handlers\FindShortUrlHandler;
use App\Application\ShortUrl\Queries\FindShortUrlByCodeQuery;
use App\Http\Request\CreateShortUrlRequest;
use Symfony\Component\HttpFoundation\Response;

class ShortUrlController extends Controller
{
    public function __construct(
        private CreateShortUrlHandler $createShortUrlHandler,
        private FindShortUrlHandler $findShortUrlHandler,
        private FindShortUrlByCodeHandler $findShortUrlByCodeHandler
    ) {}

    public function create(CreateShortUrlRequest $request): Response
    {
        $command = new CreateShortUrlCommand(
            $request->input('url')
        );
        $shortUrl = $this->createShortUrlHandler->handle($command);
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
        $shortUrl = $this->findShortUrlByCodeHandler->handle($query);
        if (!$shortUrl) {
            return response()->json(['message' => 'Short URL not found'], 404);
        }
        return redirect($shortUrl->originalUrl());
    }
    public function redirect(string $code)
    {
        $query = new FindShortUrlByCodeQuery($code);
        $shortUrl = $this->findShortUrlByCodeHandler->handle($query);
        if (!$shortUrl) {
            abort(404);
        }
        return redirect()->away($shortUrl->originalUrl());
    }
}
