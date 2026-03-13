<?php

namespace App\Http\Controllers;

use App\Application\ShortUrl\Commands\CreateShortUrlCommand;
use App\Application\ShortUrl\Commands\FindShortUrlCommand;
use App\Application\ShortUrl\Handlers\CreateShortUrlHandler;
use App\Application\ShortUrl\Handlers\FindShortUrlHandler;
use App\Http\Request\CreateShortUrlRequest;
use Symfony\Component\HttpFoundation\Response;

class ShortUrlController extends Controller
{
    public function __construct(
        private CreateShortUrlHandler $createShortUrlHandler,
        private FindShortUrlHandler $findShortUrlHandler,
    )
    {}

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
            'short_url' => url('/'.$shortUrl->shortCode())
        ], 201);
    }
    public function findByCode(string $code): Response
    {
        $command = new FindShortUrlCommand(
            $code
        );
        $shortUrl = $this->findShortUrlHandler->handle($command);
        if (!$shortUrl) {
            return response()->json(['message' => 'Short URL not found'], 404);
        }
        return redirect($shortUrl->originalUrl());
    }
}
