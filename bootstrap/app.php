<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Dashboard auth uses Sanctum bearer tokens (not cookie sessions), so statefulApi()
        // would incorrectly require CSRF on public POST routes like /api/v1/contact.
        // cPanel / reverse proxy: set TRUSTED_PROXIES=true in .env so HTTPS & URL::forceScheme work
        if (filter_var(env('TRUSTED_PROXIES', false), FILTER_VALIDATE_BOOLEAN)) {
            $middleware->trustProxies(at: '*');
        }
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
        });
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => 'Validation failed.',
                    'errors'  => $e->errors(),
                ], 422);
            }
        });

        // 500/HTML error responses skip CORS middleware — add Allow-Origin so the browser shows the real status/body
        $exceptions->respond(function (SymfonyResponse $response, \Throwable $e, Request $request) {
            if (!$request->is('api/*')) {
                return $response;
            }
            $origin = (string) $request->headers->get('Origin');
            if ($origin === '') {
                return $response;
            }
            $allowed = array_values(array_filter(config('cors.allowed_origins', [])));
            $patterns = config('cors.allowed_origins_patterns', []);
            $ok = in_array($origin, $allowed, true);
            if (!$ok) {
                foreach ($patterns as $pattern) {
                    if (is_string($pattern) && preg_match($pattern, $origin) === 1) {
                        $ok = true;
                        break;
                    }
                }
            }
            if ($ok) {
                $response->headers->set('Access-Control-Allow-Origin', $origin);
                $response->headers->set('Access-Control-Allow-Credentials', 'true');
                $response->headers->set('Access-Control-Allow-Methods', '*');
                $response->headers->set('Access-Control-Allow-Headers', '*');
                $response->headers->set('Vary', 'Origin');
            }
            return $response;
        });
    })->create();
