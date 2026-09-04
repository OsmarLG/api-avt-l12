<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Las vistas del importador usan sesión: un invitado va al login del importador.
        // El resto de la app es API stateless, así que se deja sin destino y responde 401.
        $middleware->redirectGuestsTo(
            fn (Request $request) => $request->is('importador', 'importador/*')
                ? route('imports.login')
                : null
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
