<?php

declare(strict_types=1);

use App\Exceptions\IdempotencyEnvelopeTooLargeException;
use App\Http\Middleware\JwtAuthenticate;
use App\Http\Middleware\TenantContext;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Nexus\ApprovalOperations\Exceptions\ApprovalTemplateNotFoundException;
use Nexus\ApprovalOperations\Exceptions\OperationalApprovalDeniedException;
use Nexus\ApprovalOperations\Exceptions\OperationalApprovalNotFoundException;
use Nexus\ApprovalOperations\Exceptions\OperationalApprovalWorkflowMissingException;
use Nexus\Laravel\Idempotency\Http\IdempotencyMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api/v1',
    )
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('idempotency:cleanup')->daily();
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'jwt.auth' => JwtAuthenticate::class,
            'tenant' => TenantContext::class,
            'idempotency' => IdempotencyMiddleware::class,
        ]);

        $middleware->api(prepend: [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->renderable(function (\Throwable $e, \Illuminate\Http\Request $request) {
            if (!$request->is('api/*') && !$request->wantsJson()) {
                return null;
            }

            if ($e instanceof ApprovalTemplateNotFoundException || $e instanceof OperationalApprovalNotFoundException) {
                return response()->json(['error' => 'Resource not found'], 404);
            }

            if ($e instanceof OperationalApprovalDeniedException) {
                return response()->json(['error' => 'Policy denied'], 403);
            }

            if ($e instanceof OperationalApprovalWorkflowMissingException) {
                return response()->json(['error' => 'Operational approval instance is incomplete'], 500);
            }

            if ($e instanceof \Illuminate\Validation\ValidationException) {
                return response()->json([
                    'error' => 'Validation failed',
                    'details' => $e->errors(),
                ], 422);
            }

            if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                return response()->json(['error' => 'Resource not found'], 404);
            }

            if ($e instanceof \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException) {
                return response()->json(['error' => 'Method not allowed'], 405);
            }

            if ($e instanceof IdempotencyEnvelopeTooLargeException) {
                return response()->json([
                    'error' => 'Response too large to store for replay',
                    'code' => 'idempotency_envelope_too_large',
                ], 500);
            }
        });
    })->create();
