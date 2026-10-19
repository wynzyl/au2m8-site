<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Middleware\ValidatePostSize;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectUsersTo(fn (Request $request): string => route('admin.company.edit'));

        /*
         * Check the request size once the session has started (so the "image too large" error can be
         * flashed) but before the CSRF check, which would otherwise fail first because PHP discards
         * the whole body, token included.
         */
        $middleware->remove(ValidatePostSize::class);
        $middleware->web(prepend: ValidatePostSize::class);
        $middleware->appendToPriorityList(StartSession::class, ValidatePostSize::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (HttpException $exception, Request $request): ?RedirectResponse {
            if ($exception->getStatusCode() === 419 && $request->is('admin/*', 'login', 'login/*', 'logout')) {
                return redirect()
                    ->route('login')
                    ->with('status', 'Your session expired. Please sign in again.');
            }

            return null;
        });

        /*
         * The size check runs before the auth middleware, so guests are sent to sign in here.
         */
        $exceptions->render(function (PostTooLargeException $exception, Request $request): ?RedirectResponse {
            if (! $request->is('admin/*')) {
                return null;
            }

            if ($request->user() === null) {
                return redirect()->route('login');
            }

            return back()->withErrors(['image' => 'The image must be 2 MB or less.']);
        });
    })->create();
