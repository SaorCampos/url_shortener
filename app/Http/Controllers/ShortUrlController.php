<?php

namespace App\Http\Controllers;


use App\Application\Bus\CommandBus;
use App\Application\Bus\QueryBus;
use App\Application\ShortUrl\Commands\CreateShortUrlCommand;
use App\Application\ShortUrl\Queries\FindShortUrlByCodeQuery;
use App\Http\Controllers\Controller;
use App\Http\Request\CreateShortUrlRequest;
use Symfony\Component\HttpFoundation\Response;

class ShortUrlController extends Controller
{
    public function __construct(
        private CommandBus $commandBus,
        private QueryBus $queryBus
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
        $shortUrl = $this->queryBus->dispatch(
            new FindShortUrlByCodeQuery($code)
        );
        if (!$shortUrl) {
            return response()->json(['message' => 'Short URL not found'], 404);
        }
        return redirect($shortUrl->originalUrl());
    }
}
