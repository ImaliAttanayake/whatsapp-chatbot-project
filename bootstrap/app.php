<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'logout',
        ]);

        $middleware->alias([
            'superadmin' => \App\Http\Middleware\SuperAdminMiddleware::class,
            'chat.visible' => \App\Http\Middleware\EnsureChatIsVisibleToUser::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // A stale login form (browser back/bfcache, or a reloaded POST) submits an
        // expired CSRF token. Send the user back to a freshly rendered form instead
        // of a dead-end 419 page that re-submits the same dead token on reload.
        // (Laravel converts TokenMismatchException to a 419 HttpException before
        // render callbacks run, so match on the status code.)
        $exceptions->render(function (HttpExceptionInterface $e, $request) {
            if ($e->getStatusCode() !== 419 || $request->expectsJson()) {
                return null;
            }

            return redirect()
                ->to($request->path() === 'login' ? route('login') : url()->previous())
                ->withInput($request->except('password', '_token'))
                ->with('status', 'Your session expired. Please try again.');
        });
    })->create();
