<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'login.required' => \App\Http\Middleware\RequireLogin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
        // Sesión/token expirado (CSRF) -> mandar a login sin mostrar error 419
        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, $request) {
            return redirect()->route('login')
                ->with('mensaje', 'Tu sesión expiró, vuelve a iniciar sesión.');
        });

        // Por si el middleware login.required detecta que ya no hay sesión activa
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            return redirect()->route('login');
        });
    })->create();
