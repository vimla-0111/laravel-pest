<?php

use App\Console\Commands\StartApp;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        channels: __DIR__ . '/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $e, Request $request) {
            // Handle JSON response
            if ($request->is('api/*') || $request->expectsJson()) {

                // Handle 404s specifically if needed
                if ($e instanceof NotFoundHttpException) {
                    return response()->json(['error' => 'Not Found'], 404);
                }
                if ($e instanceof \Illuminate\Broadcasting\BroadcastException) {
                    // Return 200 so the app doesn't "crash", just logs it.
                    return response()->json(['status' => 'Message Saved (Offline Mode)'], 200);
                }
                // Handle Server Errors
                return response()->json([
                    'status' => 'error',
                    'message' => app()->hasDebugModeEnabled() ? $e->getMessage() : 'Something went wrong!'
                ], 500);
            }

            // Return null to let Laravel handle standard web views
            return null;
        });
    })->withCommands([
        StartApp::class,
    ])->create();
