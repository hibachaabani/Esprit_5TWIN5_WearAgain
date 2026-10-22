<?php

use App\Http\Middleware\VerifierRole;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // role:particulier, role:atelier, role:association, role:admin (plusieurs séparés par des virgules)
        $middleware->alias([
            'role' => VerifierRole::class,
        ]);

        // Derrière le proxy HTTPS de l'hébergeur (Render) : liens et redirections en https.
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Appels JavaScript (fetch) : les erreurs courantes répondent en français plutôt qu'en anglais.
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Vous n\'êtes plus connecté : reconnectez-vous pour continuer.'], 401);
            }
        });
        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            $messages = [
                419 => 'La page a expiré : rechargez-la puis réessayez.',
                429 => 'Trop de demandes en peu de temps : patientez un instant avant de réessayer.',
            ];

            if ($request->expectsJson() && isset($messages[$e->getStatusCode()])) {
                return response()->json(['message' => $messages[$e->getStatusCode()]], $e->getStatusCode(), $e->getHeaders());
            }
        });
    })->create();
