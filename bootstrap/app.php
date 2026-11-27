
<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

// ==================== CONFIGURACIÓN DE LA APLICACIÓN ==================== //

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__ . '/../routes/api.php',          // archivo de rutas API //
        commands: __DIR__ . '/../routes/console.php', // archivo de comandos //
        health: '/up',                                // ruta de verificación //
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'usuario.activo' => \App\Http\Middleware\EnsureAuthenticatedUser::class, // valida el usuario activo //
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        // ==================== FORMATO DE RESPUESTAS ==================== //

        $exceptions->shouldRenderJsonWhen(
            fn(Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // ==================== ERRORES DE AUTENTICACIÓN Y ACCESO ==================== //

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            return response()->json([
                'message' => 'No has iniciado sesión o tu token no es válido o ya caducó.',
            ], 401); // acceso no autenticado //
        });

        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            return response()->json([
                'message' => 'No tienes permiso para realizar esta acción.',
            ], 403); // acceso prohibido //
        });

        // ==================== ERRORES DE RUTAS Y MÉTODOS ==================== //

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'El recurso o la ruta solicitada no existe.',
                ], 404); // recurso no encontrado //
            }
        });

        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'El método HTTP no está permitido para esta ruta.',
                ], 405, $e->getHeaders()); // método no permitido //
            }
        });

        // ==================== LÍMITE DE SOLICITUDES ==================== //

        $exceptions->render(function (TooManyRequestsHttpException $e, Request $request) {
            return response()->json([
                'message' => 'Demasiados intentos. Espera un momento antes de intentar de nuevo.',
            ], 429, $e->getHeaders()); // demasiadas solicitudes //
        });
    })->create(); // crea la aplicación //
