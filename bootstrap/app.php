<?php

use App\Domain\ShortUrl\Exceptions\UrlNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        api: __DIR__ . '/../routes/api.php',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->validateCsrfTokens(except: [
            //
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (UrlNotFoundException $e, Request $request) {
            // Lógica de "Negative Cache" para evitar brute force em URLs que deram 404
            $code = str_replace(['URL \'', '\' not found.'], '', $e->getMessage());
            Redis::setex("shorturl:404:{$code}", 3600, 1);

            if ($request->is('api/*')) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'error' => 'URL_NOT_FOUND'
                ], 404);
            }
        });
    })
    ->create();
